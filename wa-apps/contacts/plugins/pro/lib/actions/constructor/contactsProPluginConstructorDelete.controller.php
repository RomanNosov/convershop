<?php

class contactsProPluginConstructorDeleteController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        if (! ( $id = $this->getRequest()->post('id'))) {
            $this->errors[] = 'No id.';
            return;
        }
        
        // Is user allowed to delete this field?
        if (waContactFields::isSystemField($id)) {
            $this->errors[] = 'Unable to delete protected system field.';
            return;
        }
        
        $f = waContactFields::get($id, 'all');
        if (!$f) {
            $this->errors[] = 'No such field.';
            return;
        }
        contactsProHelper::deleteField($id);
        $this->response = 'done';
    }
}

// EOF