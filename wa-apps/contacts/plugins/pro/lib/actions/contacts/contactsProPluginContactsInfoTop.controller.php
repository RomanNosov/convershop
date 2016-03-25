<?php

class contactsProPluginContactsInfoTopController extends waJsonController
{
    public function execute()
    {
        $id = (int) $this->getRequest()->get('id');
        if (!$id) {
            throw new waException("Unknown contact");
        }
        
        $contact = new waContact($id);
        $this->response = $contact->getTopFields();
        
    }    
}

// EOF
