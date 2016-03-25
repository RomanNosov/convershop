<?php

class contactsProPluginConstructorDeleteConfirmAction extends waViewAction
{
    protected $models = array();
    
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $id = waRequest::get('id');        
        $hash = "/search/{$id}!=";
        $collection = new waContactsCollection($hash);
        $count = $collection->count();
        $field = waContactFields::get($id);

        $this->view->assign(array(
            'id' => $id,
            'name' => $field->getName(null, true),
            'count' => $count
        ));
    }    
}

// EOF