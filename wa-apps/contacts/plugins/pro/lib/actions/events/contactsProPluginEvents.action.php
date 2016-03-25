<?php

class contactsProPluginEventsAction extends waViewAction
{
    /**
     * @var contactsEvents
     */
    protected $events;

    protected $filter;


    public function __construct($params = null) {
        parent::__construct($params);
        $filter = $this->getFilter();
        if (isset($filter['period']) && is_array($filter['period'])) {
            $filter['period'][0] = date('Y-m-d 00:00:00', strtotime($filter['period'][0]));
            $filter['period'][1] = date('Y-m-d 23:59:59', strtotime($filter['period'][1]));
        }
        $this->events = new contactsEvents($filter, $this->getCategory());
    }

    public function execute()
    {
        $filter = $this->getFilter();
        $filter_values = $this->getFilterValues();

        if (!$this->getRequest()->request('contact_info_tab')) {
            if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
                throw new waRightsException(_w('Access denied'));
            }
        } else {
            if (!contactsProHelper::hasAccessToContactActivity(ifset($filter['contact_id'], 0))) {
                throw new waRightsException(_w('Access denied'));
            }
        }

        $offset = $this->getRequest()->request('offset', 0, waRequest::TYPE_INT);
        $limit = $this->getRequest()->request('count', 30, waRequest::TYPE_INT);

        $category = $this->getCategory();

        $records_data = array();
        $calendar_data = array();
        $activity_data = array();
        $users_data = array();
        $actions_data = array();

        if ($category === 'log') {
            $records_data = $this->getLogRecordsData($offset, $limit);
            $activity_data = $this->getActivityData();
            $users_data = $this->getUsersData();
            $actions_data = $this->getActionsData();
        } else {
            $calendar_data = $this->getCalendarData();
        }

        $custom_period = null;
        if (is_array($filter['period'])) {
            $custom_period = array();
            foreach ($filter['period'] as $datetime) {
                $custom_period[] = date('d.m.Y', strtotime($datetime));
            }
            $custom_period = implode('&mdash;', $custom_period);
        }

        $this->view->assign(array(
            'period' => 'month',
            'filter' => $filter,
            'custom_period' => $custom_period,
            'filter_values' => $filter_values,
            'records_data' => array(
                'data' => $records_data,
                'params' => $this->getParams()
            ),
            'calendar_data' => $calendar_data,
            'category' => $category,
            'users_data' => $users_data,
            'actions_data' => $actions_data,
            'activity_data' => $activity_data,
            'notification_settings' => array(
                'log' => $this->getNotificationLogsSettings(),
                'birthday' => $this->getNotificationBirthdaysSettings()
            ),
            //'counters' => contactsEvents::sidebarCounters()
        ));

        if ($this->getRequest()->request('contact_info_tab')) {
            $this->view->assign('url', wa()->getAppUrl('contacts'));
            $this->setTemplate('EventsContactInfo');
        }

    }

    public function getCategory()
    {
        return $this->getRequest()->request('category', 'log', waRequest::TYPE_STRING_TRIM);
    }


    public function getFilter()
    {
        if ($this->filter === null) {
            $filter = $this->parseQuery($this->getRequest()->request('query', '', waRequest::TYPE_STRING_TRIM));
            if (empty($filter['period'])) {
                $filter['period'] = 'lifetime';
            }
            if (isset($filter['type']) && $this->getCategory() === 'log') {
                $p = explode('.', $filter['type']);
                if (!isset($p[1])) {
                    $app_id = null;
                    $type = $p[0];
                } else {
                    $app_id = $p[0];
                    $type = $p[1];
                }
                $filter['type'] = $type;
                $filter['app_id'] = $app_id;
            }

            if (isset($filter['period']) && !in_array($filter['period'],  array('lifetime', 'today', 'month', 'week'))) {
                $filter['period'] = explode(' - ', $filter['period']);
                $start = strtotime($filter['period'][0]);
                $end = strtotime($filter['period'][1]);
                if ($end < $start) {
                    $filter['period'][1] = date('Y-m-d', $start);
                }
            }

            $this->filter = $filter;
        }

        return $this->filter;
    }

    public function parseQuery($query) {
        $q = array();
        $query_ar = explode('&', $query);
        foreach ($query_ar as $t) {
            $p = explode('=', $t, 2);
            if ($p[0] && $p[1]) {
                $q[$p[0]] = $p[1];
            }
        }
        return $q;
    }

    public function getFilterValues()
    {
        $filter = $this->getFilter();
        $apps = contactsProHelper::getApps();
        foreach ($filter as $k => $val) {
            if ($k === 'contact_id') {
                $m = new waContact($val);
                $filter[$k] = $m['name'];
            } else if ($k === 'type') {
                $category = $this->getCategory();
                $filter[$k] = $val;
                if ($category === 'log') {
                    $app_id = isset($filter['app_id']) ? $filter['app_id'] : null;
                    $type = $val;
                    if ($app_id && isset($apps[$app_id])) {
                        $logs = wa($app_id)->getConfig()->getLogActions(true);
                    } else {
                        $logs = wa()->getConfig()->getLogActions(true);
                    }
                    if (isset($logs[$type]['name'])) {
                        $filter[$k] = $logs[$type]['name'];
                    }
                }
            }
        }
        return $filter;
    }

    public function getLogRecordsData($offset = 0, $limit = 30)
    {
        return array(
            'items' => $this->events->getRecords($offset, $limit),
            'total_count' => $this->events->getTotalCount()
        );
    }

    public function getActivityData()
    {
        $activity = $this->events->getActivity();
        $total_count = $this->events->getActivityCount();

        return array(
            'items' => $activity,
            'total_count' => $total_count
        );
    }

    public function getUsersData()
    {
        $filter = $this->getFilter();
        $users = $this->events->getUsers(0, 30);
        $total_count = $this->events->getUsersCount();

        if (!$total_count && isset($filter['contact_id'])) {
            $contact = new waContact($filter['contact_id']);
            return array(
                'items' => array(
                    array(
                        'id' => $contact['id'],
                        'name' => $contact['name'],
                        'count' => 0
                    )
                ),
                'total_count' => 1,
                'offset' => 0
            );
        }

        return array(
            'items' => $users,
            'total_count' => $total_count,
            'offset' => 0
        );
    }

    public function getActionsData()
    {
        $actions = $this->events->getActions(0, 30);
        $total_count = $this->events->getActionsCount();
        $filter = $this->getFilter();
        if (!$total_count && isset($filter['type'])) {
            $id = $filter['type'];

            $app_name = 'webasyst';
            if (isset($filter['app_id'])) {
                $apps = contactsProHelper::getApps();
                if (isset($apps[$filter['app_id']])) {
                    $logs = wa($filter['app_id'])->getConfig()->getLogActions(true);
                    $id = "{$filter['app_id']}.{$id}";
                    if (isset($apps[$filter['app_id']])) {
                        $app_name = $apps[$filter['app_id']]['name'];
                    } else {
                        $app_name = $filter['app_id'];
                    }
                } else {
                    $app_name = $filter['app_id'];
                }
            } else {
                $logs = wa()->getConfig()->getLogActions(true);
            }
            $name = $id;
            if (isset($logs[$filter['type']])) {
                $name = $logs[$filter['type']]['name'];
            }
            return array(
                'items' => array(
                    array(
                        'id' => $id,
                        'name' => $name . " ({$app_name})",
                        'count' => 0
                    )
                ),
                'total_count' => 1,
                'offset' => 0
            );
        }
        return array(
            'items' => $actions,
            'total_count' => $total_count,
            'offset' => 0
        );
    }

    public function getParams()
    {
        return array(
            'offset' => $this->getRequest()->request('offset', 0, 'int'),
            'sort' => $this->getRequest()->request('sort', 'datetime'),
            'order' => $this->getRequest()->request('order', 0, 'int') ? 1 : 0,
            'count' => $this->getRequest()->request('count', 30, 'int')
        );
    }

    public function getCalendarData()
    {
        $filter = $this->getFilter();
        $month_year_date = strtotime((!empty($filter['year']) ? $filter['year'] : date("Y")) . '-' . (!empty($filter['month']) ? $filter['month'] : date("m")));
        $month_year_date = $month_year_date >= 0 ? $month_year_date : 0;
        $months = array(
            1  => _ws('Jan'),
            2  => _ws('Feb'),
            3  => _ws('Mar'),
            4  => _ws('Apr'),
            5  => _ws('May'),
            6  => _ws('Jun'),
            7  => _ws('Jul'),
            8  => _ws('Aug'),
            9  => _ws('Sep'),
            10 => _ws('Oct'),
            11 => _ws('Nov'),
            12 => _ws('Dec')
        );
        $full_name_months = array(
            1  => _ws('January'),
            2  => _ws('February'),
            3  => _ws('March'),
            4  => _ws('April'),
            5  => _ws('May'),
            6  => _ws('June'),
            7  => _ws('July'),
            8  => _ws('August'),
            9  => _ws('September'),
            10 => _ws('October'),
            11 => _ws('November'),
            12 => _ws('December')
        );

        $days_count = date("t", $month_year_date);
        // Numeric representation of the day of the week
        $first_day = date("w", $month_year_date);
        $last_day = date("w", strtotime(date("Y-m-{$days_count}", $month_year_date)));

        // first day is 'Sunday'
        if (waLocale::getFirstDay() == 7) {
            $first_day += 1;
            $last_day += 1;
        }
        $first_day = ($first_day == 0) ? 6 : $first_day - 1;
        $last_day = ($last_day == 0) ? 0 : 7 - $last_day;
        $date_start = strtotime("-".$first_day." days", $month_year_date);
        $date_end = strtotime("+".($days_count + $last_day)." days", $month_year_date);
        $date_start = $date_start >= 0 ? $date_start : 0;

        $current_date_start = $date_start;
        $data = array();
        while ($date_end > $current_date_start) {
            $week = (int)date("W", $current_date_start);
            $day = (int)date("w", $current_date_start);

            if (waLocale::getFirstDay() == 7 && $day == 0) {
                $week = (int)date("W", strtotime("+1 week", $current_date_start));
            }

            if (!isset($data[$week])) {
                $data[$week] = array();
            }
            $data[$week][$day] = array(
                "date"  => array(
                    'day'   => date("j", $current_date_start),
                    'month' => date("n", $current_date_start),
                    'date'  => date("Y-m-d", $current_date_start),
                ),
                "items" => array(),
                "count" => 0
            );
            $current_date_start = strtotime("+1 days", $current_date_start);
        }

        $category = $this->getCategory();

        $extra_filter = '';
        if ($category === 'event') {
            $extra_filter = "l.start_datetime >= '" .
                    (!empty($filter['year']) ? $filter['year'] : date('Y')) .
                    "-01-01' AND l.start_datetime <= '" . ((!empty($filter['year']) ? $filter['year'] : date("Y")) + 1) . "-01-01'";
        }

        $month_counters = array();
        foreach ($this->events->getGroupedCounters(
            'month',
            $extra_filter) as $item) {
            if ($item['count']) {
                $month_counters[$item['month']] = $item['count'];
            }
        }

        $current_month = date('n', $month_year_date);

        $info = array();
        if ($category === 'birthday') {
            $this->prepareBirthdaysCalendarData($data, $date_start, $date_end, $info);
        } else if ($category === 'event') {
            $info = array('current_month' => $current_month);
            $this->prepareEventsCalendarData($data, $date_start, $date_end, $info);
        }

        return array(
            'params' => array(
                'months' => $months,
                'month_counters' => $month_counters,
                'current_month' => $current_month,
                'full_name_months' => $full_name_months,
                'week_first_sunday' => waLocale::getFirstDay() == 7,
                'today' => date("j"),
                'today_month' => empty($filter['year']) || $filter['year'] == date('Y') ? date("n") : null,
                'today_week' => date('W'),
                'info' => $info
            ),
            'data' => $data
        );

    }

    protected function prepareEventsCalendarData(&$data, $date_start, $date_end, &$info)
    {
        $filter = $this->getFilter();
        $fltr = $filter;
        $fltr['period'] = array(
            date("Y-m-d 00:00:00", $date_start),
            date("Y-m-d 00:00:00", strtotime("-1 days", $date_end))
        );
        $this->events->setFilter($fltr);
        $limit = $this->events->getTotalCount();
        $events = array();
        foreach ($this->events->getRecords(0, $limit) as $item) {
            $item['start_datetime'] = waDateTime::format('fulldatetime', $item['start_datetime']);
            if ($item['end_datetime']) {
                $item['end_datetime'] = waDateTime::format('fulldatetime', $item['end_datetime']);
            }
	$event_id = $item['event_id'];
	if (!isset($events[$event_id])) {
                $item['contacts_count'] = 1;
                $events[$event_id] = $item;
	} else {
                $events[$event_id]['contacts_count'] += 1;
	}
        }
        $current_month = $info['current_month'];
        $info['count'] = array(
            'start' => 0,
            'continue' => 0
        );
        $cur_year = !empty($filter['year']) ? $filter['year'] : date('Y');
        $next_year = $this->events->getNextYear($cur_year);
        $prev_year = $this->events->getPrevYear($cur_year);
        $info['cur_year'] = $cur_year;
        $info['next_year'] = $next_year;
        $info['prev_year'] = $prev_year;

        $events_with_repeats = array();
        foreach ($events as $event) {
            if (!$event['repeat']) {
                $events_with_repeats[] = $event;
            } else {
                $start_datetime = strtotime($event['start_datetime']);
                $end_datetime = null;
                $diff = null;
                if ($event['end_datetime']) {
                    $end_datetime = $event['end_datetime'];
                    $diff = strtotime($end_datetime) - $start_datetime;
                }
                if (($event['repeat'] === 'day' && (!$diff || $diff <  24*60*60)) ||
                        ($event['repeat'] === 'week' && (!$diff || $diff < 7*24*60*60)))
                {
                    $step = $event['repeat'] === 'day' ? 1 : 7;
                    for ($datetime = $start_datetime; $datetime <= $date_end; $datetime = strtotime("+{$step} days", $datetime)) {
                        if ($datetime >= $date_start) {
                            $Y = date('Y', $datetime);
                            $m = date('m', $datetime);
                            $d = date('d', $datetime);
                            $H = date('H', $start_datetime);
                            $i = date('i', $start_datetime);
                            $s = date('s', $start_datetime);
                            $ev = $event;
                            $ev['start_datetime'] = "{$Y}-{$m}-{$d} {$H}:{$i}:{$s}";
                            if ($ev['end_datetime']) {
                                $ev['end_datetime'] = date("Y-m-d H:i:s", strtotime($ev['start_datetime']) + $diff);
                            }
                            $events_with_repeats[] = $ev;
                        }
                    }
                } else if ($event['repeat'] === 'month' && (!$diff || $diff < 31*24*60*60)) {
                    for ($k = 0; $k < 3; $k += 1) {
                        $n = date('n', $start_datetime);
                        $carry = false;
                        if ($n + $k > 12) {
                            $carry = true;
                            $n += $k;
                            $n -= 12;
                        } else {
                            $n += $k;
                        }
                        $Y = date('Y', $start_datetime) + ($carry ? 1 : 0);
                        $m = $n < 10 ? "0{$n}" : $n;
                        $d = date('d', $start_datetime);
                        $l = date('L', strtotime("{$Y}-01-01"));
                        if ($d == 31) {
                            if ($m === '02') {
                                $d = $l ? 29 : 28;
                            } else if (!in_array($m, array('01', '03', '05', '07', '08', '10', '12'))) {
                                $d = 30;
                            }
                        } else if ($d == 30 || $d == 29) {
                            if ($m === '02') {
                                $d = $l ? 29 : 28;
                            }
                        }
                        $H = date('H', $start_datetime);
                        $i = date('i', $start_datetime);
                        $s = date('s', $start_datetime);
                        $ev = $event;
                        $ev['start_datetime'] = "{$Y}-{$m}-{$d} {$H}:{$i}:{$s}";
                        if (strtotime($ev['start_datetime']) >= $date_start && strtotime($ev['start_datetime']) <= $date_end) {
                            if ($ev['end_datetime']) {
                                $ev['end_datetime'] = date("Y-m-d H:i:s", strtotime($ev['start_datetime']) + $diff);
                            }
                            $events_with_repeats[] = $ev;
                        }
                    }
                } else if ($event['repeat'] === 'year' && (!$diff  || $diff < 366*24*60*60)) {
                    for ($k = 0; $k < 2; $k += 1) {
                        $Y = date('Y', $start_datetime) + $k;
                        $m = date('m', $start_datetime);
                        $d = date('d', $start_datetime);
                        $l = date('L', strtotime("{$Y}-01-01"));
                        if ($d == 29 && $m == '02') {
                            $d = $l ? 29 : 28;
                        }
                        $H = date('H', $start_datetime);
                        $i = date('i', $start_datetime);
                        $s = date('s', $start_datetime);
                        $ev = $event;
                        $ev['start_datetime'] = "{$Y}-{$m}-{$d} {$H}:{$i}:{$s}";
                        if (strtotime($ev['start_datetime']) >= $date_start && strtotime($ev['start_datetime']) <= $date_end) {
                            if ($ev['end_datetime']) {
                                $ev['end_datetime'] = date("Y-m-d H:i:s", strtotime($ev['start_datetime']) + $diff);
                            }
                            $events_with_repeats[] = $ev;
                        }
                    }
                } else {
                    $events_with_repeats[] = $event;
                }
            }
        }
        foreach ($events_with_repeats as $event) {
            $start_datetime = strtotime($event['start_datetime']);
            if ($start_datetime < $date_start) {
                $start_datetime = $date_start;
            }
            $end_datetime = $event['end_datetime'] ? strtotime($event['end_datetime']) : $start_datetime;
            for ($datetime = $start_datetime; $datetime <= $end_datetime && $datetime < $date_end ; $datetime = strtotime("+1 days", $datetime)) {
                $week = (int)date("W", $datetime);
                $day = (int)date("w", $datetime);
                if (waLocale::getFirstDay() == 7 && $day == 0) {
                    $week = (int)date("W", strtotime("+1 week", $datetime));
                }
                if (!isset($data[$week][$day]['items'][$datetime])) {
                    $data[$week][$day]['items'][$datetime] = array();
                }
                $data[$week][$day]['items'][$datetime][$event['event_id']] = $event;
                $data[$week][$day]['count'] += 1;

                $month = (int)date('n', $datetime);
                if ($month == $current_month && !isset($event['in_current_month'])) {
                    $event['in_current_month'] = true;
                    if ($datetime === $start_datetime) {
                        $info['count']['start']++;
                    } else {
                        $info['count']['continue']++;
                    }
                }
            }
        }
        foreach ($data as &$week) {
            foreach ($week as &$day) {
                ksort($day['items']);
                $all_items = array();
                foreach ($day['items'] as $datetime => $items) {
                    ksort($items);
                    foreach ($items as $item) {
                        $item['time'] = date('H:i', $datetime);
                        $all_items[] = $item;
                    }
                }
                $day['items'] = $all_items;
            }
            unset($day);
        }
        unset($week);
    }

    protected function prepareBirthdaysCalendarData(&$data, $date_start, $date_end, &$info)
    {
        $filter = $this->getFilter();
        $month_year_date = strtotime(date("Y") . '-' . (!empty($filter['month']) ? $filter['month'] : date("m")));
        $items_per_day = 8;
        $fltr = $filter;
        $fltr['period'] = array(
            date("Y-m-d 00:00:00", $date_start),
            date("Y-m-d 00:00:00", strtotime("-1 days", $date_end))
        );
        $this->events->setFilter($fltr);
        $limit = $this->events->getTotalCount();
        if ($limit < 1000) {
            foreach ($this->events->getRecords(0, $limit) as $item) {
                $week = (int)date("W", strtotime($item['datetime']));
                $day = (int)date("w", strtotime($item['datetime']));
                if (waLocale::getFirstDay() == 7 && $day == 0) {
                    $week = (int)date("W", strtotime("+1 week", strtotime($item['datetime'])));
                }
                if ($data[$week][$day]["count"] < $items_per_day) {
                    $data[$week][$day]["items"][] = $item;
                }
                $data[$week][$day]["count"] += 1;
            }
        } else {
            foreach ($this->events->getGroupedCounters() as $item) {
                $week = (int)date("W", strtotime($item['datetime']));
                $day = (int)date("w", strtotime($item['datetime']));
                if (waLocale::getFirstDay() == 7 && $day == 0) {
                    $week = (int)date("W", strtotime("+1 week", strtotime($item['datetime'])));
                }
                $data[$week][$day]["count"] = $item['count'];
            }
            $today = date('Y-m-d 00:00:00');
            if (date('n', $month_year_date) == date('n')) {
                $week = (int)date("W", strtotime($today));
                $day = (int)date("w", strtotime($today));
                if (waLocale::getFirstDay() == 7 && $day == 0) {
                    $week = (int)date("W", strtotime("+1 week", strtotime($today)));
                }
                if ($data[$week][$day]['count'] > 0) {
                    $fltr = $filter;
                    $fltr['period'] = array($today, $today);
                    $this->events->setFilter($fltr);
                    $limit = $this->events->getTotalCount();
                    $data[$week][$day]['items'] = $this->events->getRecords(0, $items_per_day);
                }
            }
        }
    }

    public function getNotificationLogsSettings()
    {
        $nlm = new contactsNotificationLogsModel();
        $notifications = $nlm->getNotificationItems(wa()->getUser()->getId());
        $logs = array(
            "" => array()
        );
        $apps = array_keys(contactsProHelper::getApps());

        foreach ($apps as $app_id) {
            if ($app_id && $app_id !== 'contacts_full') {
                try {
                    $logs[$app_id] = wa($app_id)->getConfig()->getLogActions(true, true);
                    $logs[""] = array_merge($logs[""], wa($app_id)->getConfig()->getSystemLogActions());
                } catch (waException $e) {}
            }
        }

        $plain_logs = array();
        $names = array();
        foreach ($logs as $app_id => $log) {
            foreach ($log as $l_id => $l) {
                $name = isset($l['name']) ? $l['name'] : $l_id;
                $names[] = $name;
                $plain_logs[] = array(
                    'app_id' => $app_id,
                    'id' => $l_id
                );
            }
        }
        asort($names);
        $logs = array();
        foreach ($names as $k => $name) {
            $plain_logs[$k]['name'] = $name;
            $logs[] = $plain_logs[$k];
        }
        $asm = new waAppSettingsModel();
        return array(
            'notifications' => $notifications,
            'logs' => array_values($logs),
            'last_notification_cli' => $asm->get('contacts', 'last_notification_logs_cli'),
            'cli_command' => 'php '.wa()->getConfig()->getRootPath().'/cli.php contacts notification -t log'
        );
    }

    public function getNotificationBirthdaysSettings()
    {
        $asm = new waAppSettingsModel();
        $nbm = new contactsNotificationBirthdaysModel();
        $notifications = $nbm->getNotificationItems(wa()->getUser()->getId());
        return array(
            'notifications' => $notifications,
            'last_notification_cli' => $asm->get('contacts', 'last_notification_birthdays_cli'),
            'cli_command' => 'php '.wa()->getConfig()->getRootPath().'/cli.php contacts notification -t birthday'
        );
    }
}