<?php

class contactsProPluginPersonalSignupSaveController extends waJsonController
{
    public function execute()
    {
        $domain = waRequest::post('domain');
        $fields = waRequest::post('fields');
        $params = waRequest::post('params');
        $default_fields = array(
            'firstname',
            'lastname',
            '',
            'email' => array('required' => true),
            'password' => array('required' => true),
        );

//        $domain_config[$domain] = wa('site')->getAuthConfig();

        $domain_config_path = wa('site')->getConfig()->getPath('config', 'auth');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        $domain_config[$domain]['params'] = $params;

        if (!$domain_config[$domain]) {
            $domain_config[$domain]['fields'] = $default_fields;
        }
        else {
            $domain_config[$domain]['fields'] = array();
        }

        foreach ($fields as $field_id => $field) {
            $domain_config[$domain]['fields'][$field_id] = $field;
        }
        $domain_config[$domain]['fields']['password'] = array('required' => true);
        wa('site')->getConfig()->setAuth($domain_config);
    }
}