<?php

class contactsProPluginConstructorTransliterateController extends contactsProPluginConstructorSaveController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $names = $this->getRequest()->post('name', "");
        if (empty($names)) {
            $this->response = "";
        } else {
            $this->response = $this->getTransliteratedId($names);
        }
    }

}