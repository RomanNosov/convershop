<?php

class contactsNotesModel extends waModel
{
    protected $table = 'contacts_notes';    
    
    public function add($contact_id, $text) {
        return $this->insert(array(
            'contact_id' => $contact_id,
            'create_contact_id' => wa()->getUser()->getId(),
            'create_datetime' => date('Y-m-d H:i:s'),
            'text' => $text
        ));
    }
    
    public function edit($note_id, $text) {
        return $this->updateById($note_id, array(
            'create_contact_id' => wa()->getUser()->getId(),
            'create_datetime' => date('Y-m-d H:i:s'),
            'text' => $text
        ));
    }
    
    public function getByContactId($contact_id)
    {
        return $this->select('*')->
                where('contact_id=i:contact_id', array('contact_id' => $contact_id))->
                order('create_datetime DESC')->
                fetchAll('id');
    }
    
    public function searchNotes($options = array())
    {
        $options = array_merge(array(
            'offset' => 0,
            'limit' => 30,
            'query' => '',
            'sort' => 'create_datetime DESC'
        ), $options);
        
        $offset = (int) $options['offset'];
        $limit = (int) $options['limit'];
        $query = $options['query'];
        
        $where = array();
        if ($query) {
            foreach ($query as $k => $val) {
                $where[$k] = array();
                foreach (explode(' ', $val) as $v) {
                    $v = trim($this->escape($v, 'like'));
                    if (!$v) {
                        continue;
                    }
                    if ($k === 'name') {
                        $where[$k][] = "name LIKE '%$v%'";
                    } else if ($k === 'text') {
                        $where[$k][] = "text LIKE '%$v%'";
                    }
                }
                if ($where[$k]) {
                    $where[$k] = implode(' AND ', $where[$k]);
                    if ($k === 'name') {
                        $where[$k] = "contact_id IN ( SELECT id FROM wa_contact WHERE {$where[$k]} )";
                    }
                } else {
                    unset($where[$k]);
                }
            }
        }
        
        if ($where) {
            $where = 'WHERE '.implode(' AND ', $where);
        } else {
            $where = '';
        }
        
        $sql_t = "SELECT @F FROM `{$this->table}` {$where} ORDER BY {$options['sort']} @L";
        
        $notes = $this->query(
            str_replace(
                array('@F', '@L'), 
                array('*', "LIMIT {$offset}, {$limit}"), 
                $sql_t
        ))->fetchAll();
        
        $count = $this->query(
            str_replace(
                array('@F', '@L'), 
                array('COUNT(*)', ''), 
                $sql_t
        ))->fetchField();
        
        $contact_ids = array();
        foreach ($notes as $note) {
            $contact_ids[] = $note['contact_id'];
            $contact_ids[] = $note['create_contact_id'];
        }
        $contact_ids = array_unique($contact_ids);
        $contacts = array();
        if ($contact_ids) {
            $collection = new contactsCollection('id/' . implode(',', $contact_ids));
            $contacts = $collection->getContacts('id,name,firstname,middlename,lastname,company,is_company,email,photo_url_20', 0, count($contact_ids));
        }
        
        foreach ($notes as &$note) {
            if (isset($contacts[$note['contact_id']])) {
                $note['contact'] = $contacts[$note['contact_id']];
                $note['contact']['name'] = waContactNameField::formatName($note['contact']);
            } else {
                $note['contact'] = array();
            }
            if (isset($contacts[$note['create_contact_id']])) {
                $note['create_contact'] = $contacts[$note['create_contact_id']];
                $note['create_contact']['name'] = waContactNameField::formatName($note['create_contact']);
            } else {
                $note['create_contact'] = array();
            }
        }
        unset($note);
        
        return array(
            'notes' => $notes,
            'count' => $count
        );
    }
    
    public function deleteByContacts($contact_ids)
    {
        $this->deleteByField('contact_id', array_map('intval', (array) $contact_ids));
    }
    
}

// EOF