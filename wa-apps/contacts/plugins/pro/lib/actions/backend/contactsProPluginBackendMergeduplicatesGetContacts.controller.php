<?php

class contactsProPluginBackendMergeduplicatesGetContactsController extends waJsonController
{
    public function execute()
    {
        $field = $this->getRequest()->request('field');
        $value = $this->getRequest()->request('value');
        
        $contacts = array();
        
        if (in_array($field, array('name', 'email', 'phone'))) {
            $q = "{$field}={$value}";
            $col = new contactsCollection("search/{$q}");
            $count = $col->count();
            $col->orderBy('create_datetime', 'DESC');
            $contacts = array_keys($col->getContacts('id', 0, $count));
            if (count($contacts) < 2) {
                return;
            }
            if ($this->getRequest()->request('master_slaves')) {
                $this->response = array(
                    'master' => $contacts[0],
                    'slaves' => array_slice($contacts, 1)
                );
                return;
            }
        }
        
        $this->response['contacts'] = $contacts;
    }
}

// EOF
