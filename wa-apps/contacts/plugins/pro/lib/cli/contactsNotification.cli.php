<?php

class contactsNotificationCli extends waCliController
{
    /**
     *
     * @var waAppSettingsModel
     */
    protected $asm;

    protected $limit = 25;

    protected $contacts;

    protected $from;

    protected $backend_url;
    protected $domain;


    public function __construct() {
        $this->asm = new waAppSettingsModel();

        $from_email = $this->asm->get('webasyst', 'sender');
        if (!$from_email) {
            $from_email = $this->asm->get('webasyst', 'email');
        }
        $from_name = $this->asm->get('webasyst', 'name');
        if ($from_email) {
            $this->from = array($from_email => $from_name);
        }

        $this->backend_url = '';
        $domains = array_values(wa()->getRouting()->getDomains());
        if ($domains) {
            $domain = trim($domains[0], '/');
            $this->domain = $domain;
            $this->backend_url = array($domain);
            $backend_url = trim(wa('contacts')->getConfig()->getBackendUrl(), '/');
            if ($backend_url) {
                $this->backend_url[] = $backend_url;
            }
            $this->backend_url[] = 'contacts/';
            $this->backend_url = 'http://' . implode('/', $this->backend_url);
        }
    }
    public function execute()
    {
        if ($this->from) {
            $type = waRequest::param('t');
            if (!$type) {
                waRequest::param('type');
            }
            if (!$type) {
                $type = 'log';
            }
            if ($type === 'log' || $type === 'logs') {
                $this->sendNotificationLogs();
            } else if ($type === 'birthday' || $type === 'birthdays') {
                $this->sendNotificationBirthdays();
            } else if ($type === 'event' || $type === 'events') {
                $this->sendNotificationEvents();
            }
        }
    }

    public function sendNotificationLogs()
    {
        $csm = new waContactSettingsModel();
        wa()->pushActivePlugin('pro', 'contacts');
        $send_contacts = array();
        $nlm = new contactsNotificationLogsModel();

        $this->asm->set('contacts', 'last_notification_logs_cli', time());

        foreach ($nlm->getSubscribers(false) as $contact_id => $contact_info) {

            $to_email = '';
            if (!empty($contact_info['email'])) {
                $to_email = reset($contact_info['email']);
            }
            if (!$to_email) {
                continue;
            }

            $backend_url = $csm->getOne($contact_id, 'webasyst', 'backend_url');
            $data = $nlm->getNotifications($contact_id, 0, $this->limit);
            if ($data['items']) {

                $loc = $contact_info['locale'] ? $contact_info['locale'] : 'en_US';
                if ($loc !== 'en_US') {
                    waLocale::loadByDomain(array('contacts', 'pro'), $loc);
                    $apps = array();
                    foreach ($data['items'] as $item) {
                        if ($item['app_id']) {
                            $apps[] = $item['app_id'];
                        }
                    }
                    $apps = array_unique($apps);
                    foreach ($apps as $app_id) {
                        waLocale::loadByDomain($app_id, $loc);
                    }
                }

                $html = '';
                foreach ($data['items'] as $item) {
                    $row = $this->formatDatetime(
                            $item['datetime'],
                            !empty($contact_info['timezone']) ? $contact_info['timezone'] : null,
                            $loc,
                            'fulldatetime'
                    ) . " ";

                    if (!empty($item['contact'])) {
                        $name = htmlspecialchars($item['contact']['name']);
                        $url = $backend_url ? $backend_url . 'contacts/' : $this->backend_url;
                        $row .= "<a href='{$url}#/contact/{$item['contact_id']}/' style='display: inline-block;'>{$name}</a> ";
                    } else {
                        $row .= _wp('contact not found') . ", id={$item['contact_id']} ";
                    }
                    if ($item['app_id']) {
                        $row .= htmlspecialchars(_wd($item['app_id'], $item['type_name']));
                    } else {
                        $row .= htmlspecialchars($item['type_name']);
                    }
                    $row .= " ";
                    if (!empty($item['subject_contact'])) {
                        $name = htmlspecialchars($item['subject_contact']['name']);
                        $url = $backend_url ? $backend_url . 'contacts/' : $this->backend_url;
                        $row .= "<a href='{$url}#/contact/{$item['subject_contact_id']}/' style='display: inline-block;'>{$name}</a> ";
                    }

                    $html .= trim($row) . "<br>";
                }

                $m = _wp('You receive this message because you are signed up for notifications about <a href=":href" target="_blank">user activity</a>.');
                $m = str_replace(":href", $url . '#/events/all/', $m);
                $html .= "<br>" . $m;

                $html .= "<br><br>--<br>";

                $html .= $this->asm->get('webasyst', 'name');

                $to_name = htmlspecialchars(ifset($contact_info['name'], ''));
                $to = array($to_email => $to_name);
                if ($this->send($to, _wp('User activity'), $html)) {
                    $send_contacts[] = $contact_id;
                    $nlm->updateByField(array('contact_id' => $contact_id), array(
                        'datetime' => date('Y-m-d H:i:s')
                    ));
                }
            }
        }

        $event_params = array(
            'type' => 'log',
            'plugin' => 'pro',
            'contacts' => $send_contacts
        );
        wa('contacts')->event('notification_send', $event_params);

    }

    public function sendNotificationBirthdays()
    {
        $csm = new waContactSettingsModel();
        wa()->pushActivePlugin('pro', 'contacts');
        $send_contacts = array();
        $nbm = new contactsNotificationBirthdaysModel();

        $this->asm->set('contacts', 'last_notification_birthdays_cli', time());

        foreach ($nbm->getSubscribers(false) as $contact_id => $contact_info) {

            $to_email = '';
            if (!empty($contact_info['email'])) {
                $to_email = reset($contact_info['email']);
            }
            if (!$to_email) {
                continue;
            }

            $backend_url = $csm->getOne($contact_id, 'webasyst', 'backend_url');
            $notifications = $nbm->getNotifications($contact_id, 0, 1000);

            $data = array();
            if ($notifications['items']) {
                foreach ($notifications['items'] as $item) {
                    if (!isset($data[$item['datetime']])) {
                        $data[$item['datetime']] = array(
                            'items' => array(),
                            'count' => 0
                        );
                    }
                    $p = &$data[$item['datetime']];
                    if ($p['count'] < 8) {
                        $p['items'][] = $item;
                    }
                    $p['count'] += 1;
                    unset($p);
                }
            }

            if ($data) {

                $loc = $contact_info['locale'] ? $contact_info['locale'] : 'en_US';
                if ($loc !== 'en_US') {
                    waLocale::loadByDomain(array('contacts', 'pro'), $loc);
                    waLocale::loadByDomain('webasyst', $loc);
                }

                $html = '';
                $datetime = null;
                foreach ($data as $datetime => $notifications) {
                    $html .= $this->formatDatetime($datetime,
                            $contact_info['timezone'] ?
                                $contact_info['timezone'] :
                                date_default_timezone_get(),
                            $loc);
                    $html .= '<br>';
                    foreach ($notifications['items'] as $item) {
                        $row = '';
                        $name = htmlspecialchars($item['name']);
                        $url = $backend_url ? $backend_url . 'contacts/' : $this->backend_url;
                        $row .= "<a href='{$url}#/contact/{$item['id']}/' style='display: inline-block;'>{$name}</a> ";
                        if ($item['birth_day'] && $item['birth_month'] && $item['birth_year']) {
                            if ($item['prior']) {
                                $y = date('Y', strtotime("+ {$item['prior']} day"));
                            } else {
                                $y = date('Y');
                            }
                            $age = $y - $item['birth_year'];
                            if ($age > 0) {
                                $row .= _wp('turning') . ' ' . $age;
                            }
                        }
                        $row .= '<br>';
                        $html .= trim($row);
                    }
                    if ($notifications['count'] > 8) {
                        $html .= '...<br>';
                        $html .= _wp('Total') . ': ' . $notifications['count'];
                        $html .= '<br>';
                    }
                    $html .= '<br>';
                }

                $m = _wp('You receive this message because you are signed up for notifications about <a href=":href" target="_blank">birthdays</a>.');
                $m = str_replace(":href", $url . '#/events/all/0/0/30/birthday/', $m);
                $html .= "<br>" . $m;

                $html .= "<br><br>--<br>";

                $html .= $this->asm->get('webasyst', 'name');

                $to_name = htmlspecialchars(ifset($contact_info['name'], ''));
                $to = array($to_email => $to_name);
                if ($this->send($to, _wp('Birthdays'), $html)) {
                    $send_contacts[] = $contact_id;
                    $nbm->updateByField(array('contact_id' => $contact_id), array(
                        'datetime' => date('Y-m-d H:i:s')
                    ));
                }

            }
        }

        $event_params = array(
            'type' => 'birthday',
            'plugin' => 'pro',
            'contacts' => $send_contacts
        );

        wa('contacts')->event('notification_send', $event_params);
    }

    public function sendNotificationEvents()
    {
        $csm = new waContactSettingsModel();
        wa()->pushActivePlugin('pro', 'contacts');
        $send_contacts = array();

        $this->asm->set('contacts', 'last_notification_events_cli', time());

        $nem = new contactsNotificationEventsModel();
        $ecm = new contactsEventContactsModel();

        $contacts = array();
        $cm = new waContactModel();

        foreach ($nem->getSubscribers(false) as $contact_id => $contact_info)
        {
            $to_email = '';
            if (!empty($contact_info['email'])) {
                $to_email = reset($contact_info['email']);
            }
            if (!$to_email) {
                continue;
            }

            $backend_url = $csm->getOne($contact_id, 'webasyst', 'backend_url');
            $notifications = $nem->getNotifications($contact_id, 0, 1000);

            $loc = $contact_info['locale'] ? $contact_info['locale'] : 'en_US';
            if ($loc !== 'en_US') {
                waLocale::loadByDomain(array('contacts', 'pro'), $loc);
                waLocale::loadByDomain('webasyst', $loc);
            }

            $link_to_event = _wp('<a href=":href" target="_blank">Event details</a>');
            $subject = _wp('Scheduled event');

            foreach ($notifications['items'] as $item) {

                $html = '';
                if (trim($item['name'])) {
                    $html .= htmlspecialchars(trim($item['name'])) . "<br>";
                }
                if (trim($item['location'])) {
                    $html .= htmlspecialchars(trim($item['location'])) . "<br>";
                }

                $tz = $contact_info['timezone'] ?  $contact_info['timezone'] : date_default_timezone_get();
                $start = $this->formatDatetime($item['start_datetime'], $tz, $loc, 'datetime') . "<br>";
                $start = str_replace('00:00', '', $start);
                $html .= $start;

                $participant_ids = array_keys($ecm->getByField(array(
                    'event_id' => $item['event_id']
                ), 'contact_id'));
                if ($participant_ids) {
                    $contact_ids = array();
                    foreach ($participant_ids as $c_id) {
                        if (!isset($contacts[$c_id])) {
                            $contact_ids[] = $c_id;
                        }
                    }
                    if ($contact_ids) {
                        foreach ($cm->select('id,name,firstname,middlename,lastname,is_company')->where("id IN(".  implode(',', $contact_ids). ")")
                                ->fetchAll('id') as $c)
                        {
                            $contacts[$c['id']] = $c;
                        }
                    }
                    unset($contact_ids);

                    //$html .= _wp('Participants') . ': ';
                    $names = array();
                    foreach ($participant_ids as $c_id) {
                         $names[] = waContactNameField::formatName($contacts[$c_id]);
                    }
                    $html .= implode(', ', $names) . "<br>";
                    unset($names);
                }

                if (trim($item['description'])) {
                    $html .= htmlspecialchars(trim($item['description'])) . "<br>";
                }

                $url = $backend_url ? $backend_url . 'contacts/' : $this->backend_url;
                $month_numeric = date('n');
                $m = str_replace(":href", $url . "/#/events/all/0/0/30/event/month={$month_numeric}/event_id={$item['event_id']}/", $link_to_event);
                $html .= "<br>" . $m;

                $html .= "<br><br>--<br>";

                $html .= $this->asm->get('webasyst', 'name');

                $to_name = htmlspecialchars(ifset($contact_info['name'], ''));
                $to = array($to_email => $to_name);
                if ($this->send($to, $subject, $html)) {
                    $send_contacts[] = $contact_id;
                    $nem->updateByField(array('id' => $item['notification_id']), array(
                        'datetime' => date('Y-m-d H:i:s')
                    ));
                }

            }

            $event_params = array(
                'type' => 'event',
                'plugin' => 'pro',
                'contacts' => $send_contacts
            );

            wa('contacts')->event('notification_send', $event_params);

        }
    }

    public function formatDatetime($datetime, $timezone, $loc, $format = 'humandate')
    {
        $formatted = '';
        if ($format === 'humandate') {
            if (date('Y-m-d', strtotime($datetime)) === date('Y-m-d')) {
                $formatted .= _ws('Today') . ', ';
            } else if (date('Y-m-d', strtotime($datetime)) === date('Y-m-d', strtotime('+1 days'))) {
                $formatted .= _ws('Tomorrow') . ', ';
            }
            $formatted .= waDateTime::date(waDateTime::getFormat($format, $loc), strtotime($datetime), $timezone, $loc);
        } else {
            $formatted .= waDateTime::date(waDateTime::getFormat($format, $loc), $datetime, $timezone, $loc);
        }
        return $formatted;
    }

    public function send($to, $subject, $body)
    {
        // Send the message
        $message = new waMailMessage($subject, $body);
        $message->setTo($to);
        $message->setFrom(key($this->from), reset($this->from));
        return $message->send();
    }
}