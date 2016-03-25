<?php

class contactsProPluginFormSaveController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }

        $form_id = waRequest::post('current_form_id', 0, 'int');
        $form = waRequest::post('form');
        $form_params = waRequest::post('params');
        $form_fields = $this->getFields($form_params);

        if (empty($form_params['confirm_email_from'])) {
            $form_params['confirm_email_from'] = waRequest::post('confirm_email_from', '');
        }

        if ($form_id === -1) {
            $form_id = false;
        }

        $cpf = new contactsFormModel();
        $cpfp = new contactsFormParamsModel();

        if ($form_id) {
            $form_id = $cpf->save($form_id, $form);
        } else {
            $form_id = $cpf->save($form_id, $form);
            $this->logAction('create_signup_form');
        }

        $form_params['fields'] = $form_fields;
        $cpfp->set($form_id, $form_params);

        $this->response = $form_id;
    }

    public function getFields(&$form_params = array())
    {
        $post_fields = array();
        $fields = contactsProHelper::getFormFields();
        foreach ($fields as $field_id => $field) {
            if (!empty($form_params[$field_id])) {
                $val = json_decode($form_params[$field_id], true);
                if ($val && is_array($val)) {
                    $post_fields[$field_id] = $val;
                }
                unset($form_params[$field_id]);
            }
        }
        return $post_fields;
    }

}