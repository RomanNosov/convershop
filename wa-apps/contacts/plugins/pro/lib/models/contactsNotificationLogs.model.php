<?php

class contactsNotificationLogsModel extends waModel
{
    protected $table = 'contacts_notification_logs';

    public function getSubscribers($only_id = true)
    {
        $ids = array_keys($this->select('DISTINCT contact_id')->fetchAll('contact_id'));
        if ($only_id) {
            return $ids;
        }
        if (!$ids) {
            return array();
        }
        $col = new contactsCollection('id/' . implode(',', $ids));
        $count = $col->count();
        return $col->getContacts('id,name,locale,email,timezone', 0, $count);
    }

    public function getNotifications($contact_id, $offset = 0, $limit = 30)
    {
        $contact_id = (int) $contact_id;
        $offset = (int) $offset;
        $limit = (int) $limit;
        $sql_t = "SELECT l.* FROM `contacts_notification_logs` nl
            JOIN wa_log l ON
                IF(nl.log_app_id IS NULL, 1, nl.log_app_id = l.app_id) AND
                IF(nl.log_action IS NULL, 1, nl.log_action = l.action) AND
                IF(nl.log_contact_id IS NULL, 1, nl.log_contact_id = l.contact_id) AND
                IF(nl.log_subject_contact_id IS NULL, 1, nl.log_subject_contact_id = l.subject_contact_id)
            WHERE nl.contact_id = {$contact_id} AND l.datetime > nl.datetime
            GROUP BY l.app_id, l.action, l.contact_id, l.subject_contact_id
            :order
            :limit";
        $items = $this->query(
            str_replace(
                array(':order', ':limit'),
                array('ORDER BY l.datetime DESC', "LIMIT {$offset}, {$limit}"),
                $sql_t
            ))->fetchAll();
        $this->workupRecords($items);

        $count = $this->query(
                "SELECT COUNT(*) FROM (" . str_replace(
                    array(':order', ':limit'),
                    array('', ''),
                    $sql_t
                ) . ") t")->fetchField();

        return array(
            'items' => $items,
            'count' => $count
        );
    }


    public function getLogRecords($notification, $offset= 0, $limit = 30)
    {
        if (is_numeric($notification)) {
            $notification = $this->getById($notification);
        }
        $offset = (int) $offset;
        $limit = (int) $limit;

        $lm = new waLogModel();
        $tbl = $lm->getTableName();
        $sql_t = "SELECT :fields FROM `{$tbl}` WHERE
            app_id = ".
                        ($notification['log_app_id'] === null ? "'webasyst'" :
                            "'{$notification['log_app_id']}'")." AND
            action = '{$notification['log_action']}' AND ".

                        ($notification['log_contact_id'] !== null ?
                            " AND contact_id = '{$notification['log_contact_id']}' AND " : "").

                        ($notification['log_subject_contact_id'] !== null ?
                            " AND subject_contact_id = '{$notification['log_subject_contact_id']}' AND " : "")."

            datetime > '{$notification['datetime']}'
            :limit";
        $items = $lm->query(
            str_replace(
                array(':fields', ':limit'),
                array('*', "LIMIT {$offset}, {$limit}"),
                $sql_t
            ))->fetchAll();
        $this->workupRecords($items);

        $count = $lm->query(
                str_replace(
                    array(':fields', ':limit'),
                    array('COUNT(*)', ''),
                    $sql_t
                ))->fetchField();

        return array(
            'items' => $items,
            'count' => $count
        );
    }

    private function workupRecords(&$records, $with_prefix = false)
    {
        $contact_ids = array();
        $apps = array();
        foreach ($records as $r) {
            if (!$with_prefix) {
                if ($r['contact_id']) {
                    $contact_ids[] = $r['contact_id'];
                }
                if ($r['subject_contact_id']) {
                    $contact_ids[] = $r['subject_contact_id'];
                }
                $apps[] = (string) $r['app_id'];
            } else {
                if ($r['log_contact_id']) {
                    $contact_ids[] = $r['log_contact_id'];
                }
                if ($r['log_subject_contact_id']) {
                    $contact_ids[] = $r['log_subject_contact_id'];
                }
                $apps[] = (string) $r['log_app_id'];
            }
        }
        $apps = array_unique($apps);
        $logs = array();
        $all_apps = contactsProHelper::getApps();
        foreach ($apps as $app_id) {
            if ($app_id && isset($all_apps[$app_id])) {
                $logs[$app_id] = wa($app_id)->getConfig()->getLogActions(true);
            } else {
                $logs[""] = wa()->getConfig()->getLogActions(true);
            }
        }
        $contact_ids = array_unique($contact_ids);
        $collection = new waContactsCollection('id/'.implode(',', $contact_ids));
        $contacts = $collection->getContacts('id,name,firstname,middlename,lastname,photo_url_20', 0, count($contact_ids));

        foreach ($records as &$r) {
            if (!$with_prefix) {
                if (isset($contacts[$r['contact_id']])) {
                    $r['contact'] = $contacts[$r['contact_id']];
                    $r['contact']['name'] = waContactNameField::formatName($r['contact']);
                } else {
                    $r['contact'] = array();
                }
                if (isset($contacts[$r['subject_contact_id']])) {
                    $r['subject_contact'] = $contacts[$r['subject_contact_id']];
                    $r['subject_contact']['name'] = waContactNameField::formatName($r['subject_contact']);
                }
                $app_id = $r['app_id'] ? $r['app_id'] : "";
                if (isset($logs[$r['app_id']][$r['action']]['name'])) {
                    $r['type_name'] = $logs[$r['app_id']][$r['action']]['name'];
                } else {
                    $r['type_name'] = "{$r['app_id']}, {$r['action']}";
                }
            } else {
                if (isset($contacts[$r['log_contact_id']])) {
                    $r['contact'] = $contacts[$r['log_contact_id']];
                    $r['contact']['name'] = waContactNameField::formatName($r['contact']);
                } else {
                    $r['contact'] = null;
                }
                if (isset($contacts[$r['log_subject_contact_id']])) {
                    $r['subject_contact'] = $contacts[$r['log_subject_contact_id']];
                    $r['subject_contact']['name'] = waContactNameField::formatName($r['subject_contact']);
                } else {
                    $r['subject_contact'] = null;
                }
                if ($r['log_action'] !== null) {
                    $app_id = $r['log_app_id'] ? $r['log_app_id'] : "";
                    if (isset($logs[$r['log_app_id']][$r['log_action']]['name'])) {
                        $r['action_name'] = $logs[$r['log_app_id']][$r['log_action']]['name'];
                    } else {
                        if ($app_id) {
                            $r['action_name'] = "{$app_id}, {$r['log_action']}";
                        } else {
                            $r['action_name'] = $r['log_action'];
                        }
                    }
                } else {
                    $r['action_name'] = "";
                }
            }
        }
        unset($r);
    }

    public function getNotificationItems($contact_id)
    {
        $notifications = $this->getByField('contact_id', $contact_id, true);
        $this->workupRecords($notifications, true);
        return $notifications;
    }

    public function getNotificationItem($id)
    {
        $notifications = $this->getByField('id', $id, 'id');
        $this->workupRecords($notifications, true);
        return $notifications[$id];
    }

    public function add($data)
    {
        $data['contact_id'] = wa()->getUser()->getId();
        $data['datetime'] = date('Y-m-d H:i:s');
        return $this->insert($data);
    }

    public function deleteByContacts($contact_ids)
    {
        $this->deleteByField('contact_id', array_map('intval', (array) $contact_ids));
    }

}
