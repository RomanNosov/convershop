<?php

class contactsProPluginFrontendSignupController extends waJsonController
{
    private $signup_form;

    public function execute()
    {
        $iframe = waRequest::request('iframe', 0);
        $this->signup_form = waRequest::request('form_id', 0);
        if (!$this->signup_form) {
            $this->errors[] = 'No signup form ID';
        }
        $sf = new contactsFormModel();
        $sfp = new contactsFormParamsModel();
        $this->signup_form = $sf->getById($this->signup_form);
        $this->signup_form['params'] = $sfp->get($this->signup_form['id']);

        // try sign up
        if ($contact = $this->signup(waRequest::request('data', array()))) {

            $log_params = json_encode(array(
                'form_id' => $this->signup_form
            ));

            $this->logAction('signup', $log_params, null, $contact->getId());

            // try auth new contact
            try {
                wa()->getAuth()->auth($contact);
            } catch (waException $e) {
                $this->errors = array('auth' => $e->getMessage());
            }
        }

        if (!$this->errors) {
            if ($this->signup_form['params']['after_submit'] == 'redirect') {
                $this->response = array($this->signup_form['params']['after_submit'] => $this->signup_form['params']['redirect_after_submit']);
            } else {
                $this->response = array($this->signup_form['params']['after_submit'] => $this->signup_form['params']['html_after_submit']);
            }
        }
    }

    private function signup($data)
    {
        // check exists contacts
        $auth = wa()->getAuth();

        $is_error = false;

        $form_fields = $this->signup_form['params']['fields'];

        // check passwords
        if (isset($data['password'])) {
            if ($data['password'] !== $data['password_confirm']) {
                $this->errors['password'] = array();
                $this->errors['password_confirm'] = array(
                    _ws('Passwords do not match')
                );
                $is_error = true;
            } elseif (!$data['password'] && isset($form_fields['password']['required'])) {
                $this->errors['password'] = array();
                $this->errors['password_confirm'][] = _ws('Password can not be empty.');
                $is_error = true;
            }
        }
/*
        // birthday empty val fix
        if(isset($data['birthday']) && is_array($data['birthday']['value'])) {
            foreach ($data['birthday']['value'] as $bd_id => $bd_val) {
                if(strlen($bd_val) === 0) {
                    $data['birthday']['value'][$bd_id] = null;
                }
            }
        }
*/
        $contact = new waContact();
        foreach ($data as $fld_id => $fld) {
            $fld_tmp = waContactFields::get($fld_id);
            if (!empty($form_fields[$fld_id]['required']) && strlen($fld_id) === 0) {
                $this->errors[$fld_id] = array(
                    sprintf(_ws("%s is required"), $this->getFieldName($fld_id))
                );
                $is_error = true;
            } else {
                if ($fld_tmp) {
                    $errors = $fld_tmp->validate($fld_tmp->set($contact, $data[$fld_id], array()));
                    if (!$errors) {
                        continue;
                    }
                    if (!is_array($errors)) {
                        $errors = array($errors);
                    }
                    if (empty($this->errors[$fld_id])) {
                        $this->errors[$fld_id] = array();
                    }
                    if (empty($this->errors[$fld_id])) {
                        $this->errors[$fld_id] = $errors;
                    } else {
                        $this->errors[$fld_id] = array_merge($this->errors[$fld_id], $errors);
                    }
                    $is_error = true;
                }
            }
        }

        $name_fields = array('firstname', 'middlename', 'lastname');
        $name_fields_and_email = array_merge($name_fields, array('email'));


        if (!$is_error) {
            if (contactsProHelper::atLeastOneNotEmpty($form_fields, $name_fields) &&
                    !empty($form_fields['email']) &&
                    contactsProHelper::allEmpty($data, $name_fields_and_email))
            {
                $this->errors['name,email'] = _wp('At least one of these fields must be filled.');
                $is_error = true;
            }
        }

        if (!$is_error) {
            if (contactsProHelper::allEmpty($form_fields, $name_fields) && !empty($form_fields['email']) && empty($data['email'])) {
                $this->errors['email'] = _wp('This field is required.');
                $is_error = true;
            }
        }

        if (!$is_error && empty($data['email'])) {
            $name_fields_count = 0;
            $name_fields_existed = array();
            foreach ($name_fields as $field_id) {
                if (!empty($form_fields[$field_id])) {
                    $name_fields_count += 1;
                    $name_fields_existed[] = $field_id;
                }
            }
            if ($name_fields_count == 1) {
                $field_id = $name_fields_existed[0];
                if (empty($data[$field_id])) {
                    $this->errors['name'] =  _wp('This field is required.');
                    $is_error = true;
                }
            }
            if (!$is_error && $name_fields_count > 1) {
                if (contactsProHelper::allEmpty($data, $name_fields_existed)) {
                    $this->errors['name'] = _wp('At least one of these fields must be filled.');
                    $is_error = true;
                }
            }
        }

        // check if user exist and set unconfirmed status for email
        if (!$is_error && isset($data['email']) && $data['email']) {
            $contact = $auth->getByLogin($data['email']);
            if ($contact) {
                $this->errors['email'] = array(
                    sprintf(_ws('User with the same %s is already registered'), $this->getFieldName('email'))
                );
                $is_error = true;
            }
            $data['email'] = array('value' => $data['email'], 'status' => 'unconfirmed');
        }

        // check captcha
        if (isset($this->signup_form['params']['signup_captcha']) && $this->signup_form['params']['signup_captcha']) {
            if (!wa()->getCaptcha()->isValid()) {
                $this->errors['captcha'] = _ws('Invalid captcha');
                $is_error = true;
            }
        }

        if ($is_error) {
            return false;
        }

        // remove password_confirm field
        unset($data['password_confirm']);
        // set advansed data
        $data['create_method'] = 'signup';
        $data['create_ip'] = waRequest::getIp();
        $data['create_user_agent'] = waRequest::getUserAgent();
        // try save contact
        // save contact data to temp table and send confirmation email
        if (!empty($this->signup_form['params']['confirm_mail'])) {
            $this->sendConfirmationLink(array(
                    'form' => $this->signup_form['id'],
                    'data' => $data
                ));
        } else {
            $contact = new waContact();
            if (!$this->errors = $contact->save($data, true)) {
                if (!empty($data['email'])) {

                }

                return $contact;
            }

            if (contactsProHelper::allEmpty($form_fields, $name_fields_and_email) && isset($this->errors['name'])) {
                if (!empty($form_fields['company'])) {
                    $this->errors['company'] = _wp('This field is required.');
                    unset($this->errors['name']);
                } else {
                    $this->errors['name'] = _w('Can not send the form without name or email fields.');
                }
            }
        }

        return false;
    }

    private function getFieldName($field_id)
    {
        $field = waContactFields::get($field_id);
        if (!empty($this->signup_form['params']['fields'][$field_id]['caption'])) {
            $field_name = $this->signup_form['params']['fields'][$field_id]['caption'];
        } else if ($field) {
            $field_name = $field->getName();
        } else {
            $field_name = ucfirst($field_id);
        }
        return $field_name;
    }

    private function sendConfirmationLink($data)
    {
        $email = $data['data']['email']['value'];
        if (!$email) {
            return;
        }
        $to = ""; //$data['name'];
        $subject = htmlspecialchars($this->signup_form['params']['confirm_mail_subject']);
        $body = $this->signup_form['params']['confirm_mail_body'];

        $confirmation_hash = hash('md5', time() . 'rfb2:asdgasrb adasas d4wg/.`w' . mt_rand() . mt_rand() . mt_rand());
        $confirm_url = wa()->getRouteUrl('contacts/confirmemail/hash', array('hash' => $confirmation_hash), true);

        $body = str_replace('{EMAIL_CONFIRM_URL}', $confirm_url, $body);

//        $hash = substr($confirmation_hash, 0, 16).$unconfirmed_email['id'].substr($confirmation_hash, -16);

        $send = false;
        try {
            $m = new waMailMessage($subject, $body);
            $m->setFrom(
                !empty($this->signup_form['params']['confirm_email_from']) && $this->signup_form['params']['confirm_email_from'] !== 'default' ?
                    $this->signup_form['params']['confirm_email_from'] :
                    waMail::getDefaultFrom()
            );
            $m->setTo($email, $to);
            $send = (bool)$m->send();
        } catch (Exception $e) {
        }
        if ($send) {
            $cst = new contactsSignupTempModel();
            $cst->save($confirmation_hash, $data);
        }
        return $send;
    }

    public function display()
    {
        $callback = waRequest::get('callback') ? waRequest::get('callback') : false;

        if ($callback) {
            ob_start();
        }

        parent::display();

        if ($callback) {
            $data = ob_get_contents();
            ob_end_clean();
            echo $callback."(".$data.")";
        }
    }
}