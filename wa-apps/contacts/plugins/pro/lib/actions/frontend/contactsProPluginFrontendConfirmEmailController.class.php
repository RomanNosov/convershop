<?php

class contactsProPluginFrontendConfirmEmailController extends waViewAction
{
    public function execute()
    {
        $hash = waRequest::param('hash');
        if (!$hash) {
            throw new waException("Page not found", 404);
        }

        $cst = new contactsSignupTempModel();
        $data = $cst->getByHash($hash);
        $contact_id = null;

        if (!$data) {
            throw new waException("Page not found", 404);
        }

        $data = unserialize($data['data']);

        $contact = new waContact();
        if (!$errors = $contact->save($data['data'], true)) {
            $cst->deleteByHash($hash);
            if (!empty($data['data']['email'])) {

            }
            // after sign up callback

            $sfp = new contactsFormParamsModel();
            $form_params = $sfp->get($data['form']);

            $log_params = json_encode(array(
                'form_id' => $data['form']
            ));
            
            $this->logAction('signup', $log_params, null, $contact->getId());
            
            // try auth new contact
            if (empty($form_params['auth_after']) || $form_params['auth_after'] === 'customer_portal') {
                // try auth new contact
                try {
                    wa()->getAuth()->auth($contact);
                } catch (waException $e) {
                    $this->errors = array('auth' => $e->getMessage());
                }
                $signup_url = wa()->getRouteUrl('site/my/');
                $this->redirect($signup_url.'?emailconfirmed=1');
            } else {
                try {
                    wa()->getAuth()->auth($contact);
                } catch (waException $e) {
                    $this->errors = array('auth' => $e->getMessage());
                }
                if (empty($form_params['redirect_after_auth'])) {
                    $signup_url = wa()->getUrl(true);
                } else {
                    $signup_url = $form_params['redirect_after_auth'];
                }
                wa()->getResponse()->redirect($signup_url);
            }
        }

        $this->redirect(wa()->getAppUrl('site'));
    }
}