<?php

class contactsProPluginFormAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }

        $default_fields = array(
            'firstname',
            'lastname',
            'email',
            'password',
        );

        $cpf = new contactsFormModel();
        $all_forms = $cpf->getAll('id');
        $current_form_id = waRequest::get('current_form_id', 0);
        $enabled_fields = array();
        $params = array('fields' => array());

        // no form id specified => get first form in db
        if (!$current_form_id) {
            $first_form = reset($all_forms);
            if (!$first_form) {
                $this->view->assign('no_forms', true);
            } else {
                $current_form_id = $first_form['id'];
                $this->view->assign('first_form_id', $current_form_id);
            }
        }

        // edit form
        if ($current_form_id > 0) {
            $form = $cpf->getById($current_form_id);
            $this->view->assign('current_form', $form);

            $cpfp = new contactsFormParamsModel();
            $params = $cpfp->get($current_form_id);
            $this->view->assign('params', $params);
            $params['fields'] = ifset($params['fields'], array());
            $this->view->assign('enable_fields', $params['fields']);
        }

        $available_fields = contactsProHelper::getFormFields();
        foreach ($available_fields as $field_name => $field) {
            $name = $field->getName();
            $placeholder = ""; $checked = false;
            if ($current_form_id == -1) {
                $checked = in_array($field_name, $default_fields);
            } else {
                if (array_key_exists($field_name, $params['fields'])) {
                    $checked = true;
                    $placeholder = ifset($params['fields'][$field_name]['placeholder'], "");
                }
            }
            $field_params = array();
            $attrs = 'disabled="disabled" placeholder="'.$placeholder.'"';
            $html = $field->getHtml($field_params, $attrs);
            $available_fields[$field_name] = array(
                'id' => $field_name,
                'name' => $name,
                'placeholder' => '',
                'html' => $html,
                'checked' => $checked,
                'placeholder_need' =>
                    ($field instanceof waContactSelectField
                        || $field instanceof waContactBirthdayField || $field instanceof waContactAddressField
                        ) ? false : true,
            );
            $enabled_fields[$field_name] = isset($params['fields'][$field_name]) ? contactsProHelper::arrayMerge($params['fields'][$field_name], $available_fields[$field_name]) : $available_fields[$field_name];
        }

        $enabled_fields = contactsProHelper::filterNotEmpty(contactsProHelper::arrayMerge($params['fields'], $enabled_fields));


        $auth = wa()->getAuthConfig();
        $this->view->assign('from', !empty($params['confirm_email_from']) && $params['confirm_email_from'] !== 'default' ? $params['confirm_email_from'] : '');
        $this->view->assign('personal_portal_available', !empty($auth['app']));
        $this->view->assign('backend_url', wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl(false) . '/');
        $this->view->assign('available_fields', $available_fields);
        $this->view->assign('enabled_fields', $enabled_fields);
        $this->view->assign('current_form_id', $current_form_id);
        $this->view->assign('forms_list', $all_forms);
    }

}
