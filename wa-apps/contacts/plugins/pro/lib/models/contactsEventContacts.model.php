<?php

class contactsEventContactsModel extends waModel
{
    protected $table = 'contacts_event_contacts';
    
    public function set($event_id, $contact_id) {
        $old_contact_id = array_keys($this->getByField(array(
            'event_id' => (int) $event_id
        ), 'contact_id'));
        $contact_id = array_map('intval', (array) $contact_id);
        $remove_contact_id = array_diff($old_contact_id, $contact_id);
        $this->deleteByField(array(
            'event_id' => (int) $event_id,
            'contact_id' => $remove_contact_id
        ));
        $this->add($event_id, $contact_id);
    }
    
    public function add($event_id, $contact_id) {
        $data = array();
        $event_id = array_map('intval', (array) $event_id);
        $contact_id = array_map('intval', (array) $contact_id);
        foreach ($event_id as $e_id) {
            foreach ($contact_id as $c_id) {
                $data[] = "{$e_id}, {$c_id}";
            }
        }
        if ($data) {
            $sql = "INSERT IGNORE INTO {$this->table} (event_id, contact_id) VALUES (".implode('), (', $data).")";
            return $this->query($sql);
        }
        return true;
    }
    
    public function deleteByContacts($contact_ids) 
    {
        $this->deleteByField('contact_id', array_map('intval', (array) $contact_ids));
    }
    
}

// EOF