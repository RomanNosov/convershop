<?php

class contactsProPluginEventsDeleteController extends waJsonController
{
    public function execute()
    {
        
        if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $id = $this->getRequest()->request('id', null, waRequest::TYPE_INT);
        $m = new contactsEventModel();
        if (!$m->getById($id)) {
            throw new waException(_w('Event not found'), 404);
        }
        $m->delete($id);
    }
}