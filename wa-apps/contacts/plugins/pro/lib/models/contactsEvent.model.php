<?php

class contactsEventModel extends waModel
{
    protected $table = 'contacts_event';    
    
    protected function beforeSave($data)
    {
        $format = 'fulldatetime';
        $f = waDateTime::getFormat('fulldatetime');
        if (!empty($data['start_datetime'])) {
            $data['start_datetime'] = waDateTime::parse($format, date($f, strtotime($data['start_datetime'])));
        }
        if (!empty($data['end_datetime'])) {
            $data['end_datetime'] = waDateTime::parse($format, date($f, strtotime($data['end_datetime'])));
        }
        return $data;
    }


    public function add($data) {
        if (empty($data['start_datetime'])) {
            return false;
        }
        $data = $this->beforeSave($data);
        $id = $this->insert(array_merge($data, array(
            'contact_id' => wa()->getUser()->getId(),
            'create_datetime' => date('Y-m-d H:i:s')
        )));
        if (!$id) {
            return false;
        }
        if (!empty($data['contact_id'])) {
            $contact_id = array_map('intval', (array) $data['contact_id']);
            $m = new contactsEventContactsModel();
            $m->add($id, $contact_id);
        }
        return $id;
    }
    
    public function edit($id, $data) {
        $data = $this->beforeSave($data);
        $this->updateById($id, array_merge($data, array(
            'contact_id' => wa()->getUser()->getId(),
            'create_datetime' => date('Y-m-d H:i:s')
        )));
        $contact_id = array_map('intval', (array) ifempty($data['contact_id'], array()));
        $m = new contactsEventContactsModel();
        $m->set($id, $contact_id);
    }
    
    public function getEvent($id) {
        $event = $this->getById($id);
        if (!$event) {
            $event = $this->getEmptyRow();
            $event['contacts'] = array();
        } else {
            $m = new contactsEventContactsModel();
            $contact_ids = $m->getByField('event_id', $id, 'contact_id');
            $contacts = array();
            if ($contact_ids) {
                $col = new contactsCollection('id/' . implode(',', array_keys($contact_ids)));
                $contacts = $col->getContacts('id,firstname,lastname,middlename,name,photo_url_20', 0, $col->count());
            }
            $event['contacts'] = $contacts;
        }
        return $event;
    }
    
    public function delete($id)
    {
        $id = array_map('intval', (array) $id);
        $models = array(
            new contactsEventContactsModel(),
            new contactsNotificationEventsModel()
        );
        $events = $this->getByField('id', $id, 'id');
        foreach ($events as $event) {
            foreach ($models as $m) {
                $m->deleteByField('event_id', $event['id']);
            }
            $this->deleteById($event['id']);
        }
    }
}

// EOF