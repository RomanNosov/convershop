<?php

class contactsNotificationBirthdaysModel extends waModel
{
    protected $table = 'contacts_notification_birthdays';
  
    public function getSubscribers($only_id = true, $for_today = true)
    {
        if ($only_id) {
            if ($for_today) {
                $datetime = date('Y-m-d');
                $ids = array_keys($this->select('DISTINCT contact_id')->
                        where("DATE(datetime) < '{$datetime}'")->
                                fetchAll('contact_id'));
            } else {
                $ids = array_keys($this->select('DISTINCT contact_id')->fetchAll('contact_id'));            
            }
            return $ids;
        }
        $col = new contactsCollection();
        if ($for_today) {
            $datetime = date('Y-m-d');
            $col->addJoin($this->table, null, "DATE(:table.datetime) < '{$datetime}'");
        } else {
            $col->addJoin($this->table);
        }
        $count = $col->count();
        return $col->getContacts('id,name,locale,email,timezone', 0, $count);
    }
    
    public function getNotifications($contact_id, $offset = 0, $limit = 30)
    {
        $contact_id = (int) $contact_id;
        $offset = (int) $offset;
        $limit = (int) $limit;
        $sql_t = "SELECT c.*, nb.prior, STR_TO_DATE(CONCAT(YEAR(DATE_ADD(DATE(NOW()), INTERVAL nb.prior DAY)), '-', c.birth_month, '-', c.birth_day), '%Y-%m-%d') AS datetime 
        FROM `contacts_notification_birthdays` nb
        JOIN `wa_contact` c ON IF(nb.birthday_contact_id IS NULL, 1, nb.birthday_contact_id = c.id) AND c.birth_day IS NOT NULL AND c.birth_day != '' AND c.birth_month IS NOT NULL AND c.birth_month != '' 
        WHERE nb.contact_id = '{$contact_id}' AND STR_TO_DATE(CONCAT(YEAR(DATE_ADD(DATE(NOW()), INTERVAL nb.prior DAY)), '-', c.birth_month, '-', c.birth_day), '%Y-%m-%d') = 
            DATE_ADD(DATE(NOW()), INTERVAL nb.prior DAY) 
        GROUP BY c.id
        :order
        :limit";
        
        $items = $this->query($q = 
            str_replace(
                array(':order', ':limit'), 
                array('ORDER BY datetime, c.name', "LIMIT {$offset}, {$limit}"), 
                $sql_t
            ))->fetchAll();
        
        foreach ($items as &$r) {
            $r['name'] = waContactNameField::formatName($r);
        }
        unset($r);
        
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
    
    public function getContacts($notification, $offset = 0, $limit = 30)
    {
        if (is_numeric($notification)) {
            $notification = $this->getById($notification);
        }
        $sql_t = "SELECT :fields, STR_TO_DATE(CONCAT(YEAR(NOW()), '-', birth_month, '-', birth_day), '%Y-%m-%d') AS datetime 
            FROM `wa_contact` 
            WHERE birth_day IS NOT NULL AND birth_day != '' AND birth_month IS NOT NULL AND birth_month != '' ";
        if ($notification['prior']) {
            $sql_t .= "AND STR_TO_DATE(CONCAT(YEAR(NOW()), '-', birth_month, '-', birth_day), '%Y-%m-%d') = 
                    DATE_ADD(DATE(NOW()), INTERVAL {$notification['prior']} DAY) ";
        } else {
            $sql_t .= "AND STR_TO_DATE(CONCAT(YEAR(NOW()), '-', birth_month, '-', birth_day), '%Y-%m-%d') = DATE(NOW())";
        }
        if ($notification['birthday_contact_id'] !== null) {
            $sql_t .= "AND id = {$notification['birthday_contact_id']} ";
        }
        $sql_t .= ":limit";
        
        $items = $this->query(
            str_replace(
                array(':fields', ':limit'), 
                array('*', "LIMIT {$offset}, {$limit}"), 
                $sql_t
            ))->fetchAll();
        foreach ($items as &$r) {
            $r['contact_id'] = $r['id'];
            $r['contact'] = $r;
            $r['contact']['photo_url_20'] = waContact::getPhotoUrl($r['id'], $r['id'] ? $r['photo'] : null, 20, 20, $r['is_company'] ? 'company' : 'person');
            $r['contact']['name'] = waContactNameField::formatName($r);
            $r['subject_contact_id'] = null;
            $r['subject_contact'] = null;
            $r['type_name'] = _wp('Birthday');
            $r['type'] = 'birthday';
        }
        unset($r);
        
        $count = $this->query(
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
    
    protected function workupRecords(&$records)
    {      
        $contact_ids = array();
        foreach ($records as $r) {
            if ($r['birthday_contact_id']) {
                $contact_ids[] = $r['birthday_contact_id'];
            }
        }
        $contacts = array();
        if ($contact_ids) {
            $contacts_ids = array_unique($contact_ids);
            $col = new contactsCollection('id/' . implode(',', $contacts_ids));
            $contacts = $col->getContacts('id,firstname,lastname,middlename,photo_url_20', 0, count($contacts_ids));
        }
        foreach ($records as &$r) {
            if (isset($contacts[$r['birthday_contact_id']])) {
                $r['contact'] = $contacts[$r['birthday_contact_id']];
                $r['contact']['name'] = waContactNameField::formatName($r['contact']);
            } else {
                $r['contact'] = null;
            }
            $str = '';
            if ($r['prior']) {
                if ($r['prior'] == 1) {
                    $str = _wp('оne day before');
                } else if ($r['prior'] == 2) {
                    $str = _wp('2 days before');
                } else if ($r['prior'] == 7) {
                    $str = _wp('one week before');
                } else if ($r['prior'] == 30) {
                    $str = _wp('one month before');
                } else {
                    $str = _wp('%d day before', '%d days before', $r['prior']);
                }
            } else {
                $str = _wp('оn a birthday');
            }
            $r['prior_str'] = $str;
        }
        unset($r);
    }
    
    public function getNotificationItems($contact_id)
    {
        $notifications = $this->getByField('contact_id', $contact_id, true);
        $this->workupRecords($notifications);        
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
        $data['datetime'] = date("Y-m-d H:i:s", strtotime("-1 days"));  // for sending today if need
        return $this->insert($data);
    }
    
    public function deleteByContacts($contact_ids) 
    {
        $this->deleteByField('contact_id', array_map('intval', (array) $contact_ids));
    }
    
}
