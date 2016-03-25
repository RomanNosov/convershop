<?php

class contactsProPluginPersonalSignupAction extends waViewAction
{
    public function execute()
    {
        $domain = waRequest::get('domain');
        $fields = array();
        $must_have_fields = array(
            'email' => array(
                'required' => true,
                'disabled' => true
            ),
            'password' => array(
                'required' => true,
                'disabled' => true
            ),
        );
        $default_fields = array(
            'firstname',
            'lastname',
            '',
        ) + $must_have_fields;

        // include auth.php
        $domain_config_path = wa('site')->getConfig()->getPath('config', 'auth');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        // fields for this form (or default fields)
        $config_fields = isset($domain_config[$domain]['fields']) ? $domain_config[$domain]['fields'] : $default_fields;
        $separators = 0;
        foreach ($config_fields as $fiels_name => $field) {
            $fld_name = is_array($field) ? $fiels_name : $field;
            $fld_name = strlen($fld_name) ? $fld_name : $separators++;
            $enable_fields[$fld_name] = $field;
        }

        $contacts_fields = waContactFields::getAll('person', true);
        foreach ($contacts_fields as $fiels_name => $field) {
            $name = $field->getName();
            if ($name && $fiels_name !== 'name') {
                $available_fields[$fiels_name] = array(
                    'id' => $fiels_name,
                    'name' => $name,
                    'checked' => array_key_exists($fiels_name, $enable_fields),
                    'disabled' => false,
                );
                // only for 'must have' fields
                if (in_array($fiels_name, $must_have_fields)) {
                    $available_fields[$fiels_name]['disabled'] = true;
                    $available_fields[$fiels_name]['checked'] = true;
                    // if we don't have 'must have' fields - let's add'em
                    if (!array_key_exists($fiels_name, $enable_fields)) {
                        $enable_fields[$fiels_name] = $available_fields[$fiels_name];
                    }
                }
            }
        }

        $this->view->assign('domain', $domain);
        $this->view->assign('enable_fields', $enable_fields);
        $this->view->assign('available_fields', $available_fields);
        $this->view->assign('fields', $fields);
        $this->view->assign('params', isset($domain_config[$domain]['params']) ? $domain_config[$domain]['params'] : array());
    }
}