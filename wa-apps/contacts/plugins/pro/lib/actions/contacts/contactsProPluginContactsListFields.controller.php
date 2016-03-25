<?php

class contactsProPluginContactsListFieldsController extends waJsonController
{
    public function execute()
    {
        $metric_fields = contactsSearchHelper::getMetricFields(
            (array) $this->getRequest()->post('custom_fields', array())
        );
        $contacts = array();
        foreach ($this->getRequest()->post('contacts', array()) as $c_id) {
            $c_id = (int) $c_id;
            $contacts[$c_id] = array('id' => $c_id);
        }
        $view_id = $this->getRequest()->post('view_id', null, waRequest::TYPE_INT);
        if ($view_id) {
            $m = new contactsViewModel();
            $m->addMetrics($view_id, array_keys($metric_fields));
        }
        
        contactsSearchHelper::addMetrics($contacts, $metric_fields);
        $this->response['fields'] = $metric_fields;
        $this->response['contacts'] = $contacts;
    }
}

// EOF