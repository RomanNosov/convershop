<?php

class contactsProPluginEventsSaveNotificationController extends waJsonController
{
    public function execute()
    {
        if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $category = $this->getRequest()->request('category', null, waRequest::TYPE_STRING_TRIM);
        /**
         * @param contactsNotificationLogsModel or contactsNotificationBirthdaysModel
         */
        $m = contactsEvents::getModel($category);
        if (!$m) {
            throw new waException(_w("Unknown category of events"));
        }
        $data = $this->getData();
        $id = $m->add($data);
        $this->response['notification'] = $m->getNotificationItem($id);
        
        if ($id) {
            $event_params = array(
                'type' => $category,    // log|birthday ...
                'id' => $id,
                'plugin' => 'pro'
            );
            wa('contacts')->event('notification_save', $event_params);
        }
        
    }
    
    public function getData()
    {
        $data = array();
        $data_r = $this->getRequest()->request('notification');
        foreach ($data_r as $k => $v) {
            $v = trim($v);
            if ($k === 'log_action') {
                $app_id = null;
                $action = null;
                if (strpos($v, '.') !== false) {
                    $action = explode('.', $v);
                    $app_id = $action[0];
                    $action = $action[1];
                } else {
                    $action = $v;
                }
                $data['log_app_id'] = !empty($app_id) ? $app_id : null;
                $data['log_action'] = !empty($action) ? $action : null;
            } else if (!empty($v)) {
                if (in_array($k, array('log_contact_id', 'log_subject_contact_id', 'birthday_contact_id', 'prior'))) {
                    $v = (int) $v;
                }
                $data[$k] = $v;
            }
        }
        return $data;
    }
}