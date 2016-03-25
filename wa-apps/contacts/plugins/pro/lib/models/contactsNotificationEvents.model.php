<?php

class contactsNotificationEventsModel extends waModel
{
    protected $table = 'contacts_notification_events';

    public function getSubscribers($only_id = true)
    {
        $ids = array_keys($this->select('DISTINCT contact_id')->fetchAll('contact_id'));
        if ($only_id) {
            return $ids;
        }
        $col = new contactsCollection('id/' . implode(',', $ids));
        $count = $col->count();
        return $col->getContacts('id,name,locale,email,timezone', 0, $count);
    }

    public function getNotifications($contact_id, $offset = 0, $limit = 1000)
    {
        $contact_id = (int) $contact_id;
        $offset = (int) $offset;
        $limit = (int) $limit;

        $res = $this->query("
        SELECT *, ne.id AS notification_id, ne.contact_id, e.contact_id AS author_contact_id
        FROM `contacts_notification_events` ne
            JOIN `contacts_event` e ON ne.event_id = e.id
        WHERE ne.contact_id = {$contact_id} AND  NOT (e.repeat IS NULL AND ne.datetime IS NOT NULL)
        LIMIT {$offset}, {$limit}
    ");

        $items = array();
        foreach ($res as $item) {
            $actual = false;
            if (!$item['repeat'] && $item['datetime'] === null) {
                if ($item['prior_minutes']) {
                    $time_to_event = strtotime($item['start_datetime']) - time();
                    $prior_minutes_time = $item['prior_minutes'] * 60;
                    if ($time_to_event <= $prior_minutes_time) {
                        $actual = true;
                    }
                } else {
                    // in day of event
                    if (!$item['prior_days']) {
                        if (date('Y-m-d', strtotime($item['start_datetime'])) === date('Y-m-d')) {
                            $actual = true;
                        }
                    } else {
                        $time_to_event = strtotime($item['start_datetime']) - time();
                        $prior_time = $item['prior_days'] * 24*60*60;
                        if ($time_to_event <= $prior_time) {
                            $actual = true;
                        }
                    }
                }
            } else if ($item['repeat'] && strtotime(date('Y-m-d', strtotime($item['datetime']))) < strtotime(date('Y-m-d', time()))) {  // today not send yet
                // Y-m-d H:i:s
                $start = array(
                    'Y' => date('Y', strtotime($item['start_datetime'])),
                    'm' => date('m', strtotime($item['start_datetime'])),
                    'd' => date('d', strtotime($item['start_datetime'])),
                    'H' => date('H', strtotime($item['start_datetime'])),
                    'i' => date('i', strtotime($item['start_datetime'])),
                    's' => date('s', strtotime($item['start_datetime'])),
                    'N' => date('N', strtotime($item['start_datetime']))       // week day
                );
                $start_dt = strtotime($item['start_datetime']);
                $end = null;
                $end_dt = null;
                $diff_dt = null;
                if ($item['end_datetime'] !== null) {
                    $end = array(
                        'Y' => date('Y', strtotime($item['end_datetime'])),
                        'm' => date('m', strtotime($item['end_datetime'])),
                        'd' => date('d', strtotime($item['end_datetime'])),
                        'H' => date('H', strtotime($item['end_datetime'])),
                        'i' => date('i', strtotime($item['end_datetime'])),
                        's' => date('s', strtotime($item['end_datetime']))
                    );
                    $end_dt = strtotime($item['end_datetime']);
                    $diff_dt = $end_dt - $start_dt;
                }

                // DAY REPEAT
                if ($item['repeat'] === 'day' && (!$diff_dt || $diff_dt < 24*60*60)) {  // diff period must be less than 24 hours

                    // make start_datetime depending on the kind of event: first time or repeated
                    if (time() > strtotime($item['start_datetime'])) {
                        $start['Y'] = date('Y');
                        $start['m'] = date('m');
                        $start['d'] = date('d');
                    }
                    $start_datetime = "{$start['Y']}-{$start['m']}-{$start['d']} {$start['H']}:{$start['i']}:{$start['s']}";


                    if ($item['prior_minutes']) {
                        $time_to_event = strtotime($start_datetime) - time();
                        $prior_minutes_time = $item['prior_minutes'] * 60;

                        if ($time_to_event < 0) {       // event is passed
                            $time_from_last_sent = time() - strtotime($item['datetime']);   // but sending could be missed
                            if ($time_to_event >= -2 * $prior_minutes_time && $time_from_last_sent > 2 * $prior_minutes_time) {
                                $actual = true;
                            }
                        } else if ($time_to_event <= $prior_minutes_time) {
                            $actual = true;
                        }

                    } else if (!$item['prior_days']) {  // in day of event
                        if (date('Y-m-d', $start_datetime) === date('Y-m-d')) {
                            $actual = true;
                        }
                    }

                }

                // WEEK REPEAT
                if ($item['repeat'] === 'week' && (!$diff_dt || $diff_dt < 7*24*60*60)) {    // diff period must be less than 7 days

                    // make start_datetime depending on the kind of event: first time or repeated
                    $start_datetime = "{$start['Y']}-{$start['m']}-{$start['d']} {$start['H']}:{$start['i']}:{$start['s']}";
                    if (time() > strtotime($start_datetime)) {
                        // find nearest date with the same day of week
                        for ($i = 0; $i < 7; $i += 1) {
                            $time = strtotime("+{$i} day");
                            if ($start['N'] === date('N', $time)) {
                                $y = date('Y', $time);
                                $m = date('m', $time);
                                $d = date('d', $time);
                                $start_datetime = "{$y}-{$m}-{$d} {$start['H']}:{$start['i']}:{$start['s']}";
                                break;
                            }
                        }
                    }

                    if ($item['prior_minutes']) {
                        $time_to_event = strtotime($start_datetime) - time();
                        $prior_minutes_time = $item['prior_minutes'] * 60;
                        if ($time_to_event <= $prior_minutes_time) {
                            $actual = true;
                        }
                    } else {
                        if (!$item['prior_days']) {  // in day of event
                            if (date('Y-m-d', strtotime($start_datetime)) === date('Y-m-d')) {
                                $actual = true;
                            }
                        } else if ($item['prior_days'] < 7) {
                            $time_to_event = strtotime($start_datetime) - time();
                            $prior_time = $item['prior_days'] * 24*60*60;

                            if ($time_to_event < 0) {       // event is passed
                                $time_from_last_sent = time() - strtotime($item['datetime']);   // but sending could be missed
                                if ($time_to_event >= -2 * $prior_time && $time_from_last_sent > 2 * $prior_time) {
                                    $actual = true;
                                }
                            } else if ($time_to_event <= $prior_time) {
                                $actual = true;
                            }

                        }
                    }
                }

                // MONTH REPEAT
                if ($item['repeat'] === 'month' && (!$diff_dt || $diff_dt < 31*24*60*60)) {     // diff period must be less than 31 days

                    // make start_datetime depending on the kind of event: first time or repeated
                    $start_datetime = "{$start['Y']}-{$start['m']}-{$start['d']} {$start['H']}:{$start['i']}:{$start['s']}";
                    if (time() > strtotime($item['start_datetime'])) {
                        $y = date('Y');
                        $m = date('m');
                        $l = date('L');
                        if ($start['d'] == 31) {
                            if ($m === '02') {
                                $start['d'] = $l ? 29 : 28;
                            } else if (!in_array($m, array('01', '03', '05', '07', '08', '10', '12'))) {
                                $start['d'] = 30;
                            }
                        } else if ($start['d'] == 30 || $start['d'] == 29) {
                            if ($m === '02') {
                                $start['d'] = $l ? 29 : 28;
                            }
                        }
                        $start_datetime = "{$y}-{$m}-{$start['d']} {$start['H']}:{$start['i']}:{$start['s']}";
                    }

                    if ($item['prior_minutes']) {
                        $time_to_event = strtotime($start_datetime) - time();
                        $prior_minutes_time = $item['prior_minutes'] * 60;
                        if ($time_to_event <= $prior_minutes_time) {
                            $actual = true;
                        }
                    } else {
                        if (!$item['prior_days']) {  // in day of event
                            if (date('Y-m-d', strtotime($start_datetime)) === date('Y-m-d')) {
                                $actual = true;
                            }
                        } else if ($item['prior_days'] < 31) {
                            $time_to_event = strtotime($start_datetime) - time();
                            $prior_time = $item['prior_days'] * 24*60*60;

                            if ($time_to_event < 0) {       // event is passed
                                $time_from_last_sent = time() - strtotime($item['datetime']);   // but sending could be missed
                                if ($time_to_event >= -2 * $prior_time && $time_from_last_sent > 2 * $prior_time) {
                                    $actual = true;
                                }
                            } else if ($time_to_event <= $prior_time) {
                                $actual = true;
                            }
                        }
                    }

                }

                // YEAR REPEAT
                if ($item['repeat'] === 'year' && (!$diff_dt || $diff_dt < 366*24*60*60)) {     // diff period must be less than 366 days

                    // make start_datetime depending on the kind of event: first time or repeated
                    $start_datetime = "{$start['Y']}-{$start['m']}-{$start['d']} {$start['H']}:{$start['i']}:{$start['s']}";
                    if (time() > strtotime($item['start_datetime'])) {
                        $y = date('Y');
                        $l = date('L');
                        if ($start['d'] == 29 && $start['m'] == '02') {
                            $start['d'] = $l ? 29 : 28;
                        }
                        $start_datetime = "{$y}-{$start['m']}-{$start['d']} {$start['H']}:{$start['i']}:{$start['s']}";
                    }

                    if ($item['prior_minutes']) {
                        $time_to_event = strtotime($start_datetime) - time();
                        $prior_minutes_time = $item['prior_minutes'] * 60;
                        if ($time_to_event <= $prior_minutes_time) {
                            $actual = true;
                        }
                    } else {
                        if (!$item['prior_days']) {  // in day of event
                            if (date('Y-m-d', strtotime($start_datetime)) === date('Y-m-d')) {
                                $actual = true;
                            }
                        } else if ($item['prior_days'] < 366) {
                            $time_to_event = strtotime($start_datetime) - time();
                            $prior_time = $item['prior_days'] * 24*60*60;

                            if ($time_to_event < 0) {       // event is passed
                                $time_from_last_sent = time() - strtotime($item['datetime']);   // but sending could be missed
                                if ($time_to_event >= -2 * $prior_time && $time_from_last_sent > 2 * $prior_time) {
                                    $actual = true;
                                }
                            } else if ($time_to_event <= $prior_time) {
                                $actual = true;
                            }

                        }
                    }

                }
            }
            if ($actual) {
                $items[] = $item;
            }
        }
        return array(
            'items' => $items,
            'count' => count($items)
        );
    }

    public function getNotificationItems($contact_id)
    {
        $notifications = $this->getByField('contact_id', $contact_id, true);
        return $notifications;
    }

    public function getNotificationItemByEvent($event_id, $contact_id = null)
    {
        $contact_id = $contact_id ? $contact_id : wa()->getUser()->getId();
        $notifications = $this->getByField(array(
            'event_id' => $event_id,
            'contact_id' => $contact_id
        ), 'id');
        if (empty($notifications)) {
            return $this->getEmptyRow();
        } else {
            $ids = array_keys($notifications);
            $id = $ids[0];
            $ids = array_slice($ids, 1);
            if ($ids) {
                $this->deleteByField(array(
                    'id' => $ids
                ));
            }
            return $notifications[$id];
        }
    }

    public function getNotificationItem($id)
    {
        $notifications = $this->getByField('id', $id, 'id');
        return $notifications[$id];
    }

    public function add($data)
    {
        $data['contact_id'] = wa()->getUser()->getId();
        if (!$data['event_id']) {
            return false;
        }
        return $this->insert($data);
    }

    public function update($id, $data)
    {
        return $this->updateById($id, $data);
    }

    public function delete($id)
    {
        return $this->deleteById($id);
    }

    public function deleteByContacts($contact_ids)
    {
        $this->deleteByField('contact_id', array_map('intval', (array) $contact_ids));
    }

}
