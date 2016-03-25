<?php

class contactsProPluginEventsDeleteNotificationController extends waJsonController
{
    public function execute()
    {
        if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $id = $this->getRequest()->request('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException(_w("Unknown notification"));
        }
        $category = $this->getRequest()->request('category', null, waRequest::TYPE_STRING_TRIM);
        $m = contactsEvents::getModel($category);
        if (!$m) {
            throw new waException(_w("Unknown category of events"));
        }
        $m->deleteById($id);
    }
}