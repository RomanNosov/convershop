<?php

class contactsProPluginFrontendGetSignupFormJsAction extends waViewAction
{
    public function execute()
    {
        $form_id = waRequest::param('form_id', 0);
        $no_js = waRequest::param('no_js', 0);

        $form_id = ifset($this->params['form_id'], $form_id);
        $no_js = ifset($this->params['no_js'], $no_js);

        $cf = new contactsFormModel();
        $signup_form = $cf->getById($form_id);
        if (!$signup_form) {
            return false;
        }

        $cfp = new contactsFormParamsModel();
        $signup_form_params = $cfp->get($form_id);

        $uniqid = 'contactspro' . md5(serialize($signup_form));

        $this->view->assign('form_id', $form_id);
        $this->view->assign('no_js', $no_js);
        $this->view->assign('params', $signup_form_params);
        $this->view->assign('uniqid', $uniqid);

        if (!$no_js) {
            wa()->getResponse()->addHeader('Content-type', 'text/javascript; charset=utf-8');
        }
    }
} 