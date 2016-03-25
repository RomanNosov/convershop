<?php

class contactsProPluginViewDeleteFromController extends waJsonController
{
    public function execute()
    {
        $contacts = waRequest::post('contacts', array(), 'array_int');
        $view_id = waRequest::post('view_id', 0, 'int');

        $m = new contactsViewModel();
        if (!$m->canEdit($view_id)) {
            throw new waRightsException(_wp('Access denied'));
        }
        
        $m->deleteFrom($view_id, $contacts);

        $contacts = count($contacts);
        $this->response['message'] = sprintf(_w("%d contact has been removed", "%d contacts have been removed", $contacts), $contacts);
        $this->response['message'] .= ' ';
        $this->response['message'] .= _w("from a list");
    }
}