<?php

class contactsProPluginFrontendGetSignupFormHtmlAction extends waViewAction
{
    public function execute()
    {
        $form_id = waRequest::param('form_id', 0);
        $absolute = waRequest::param('absolute', false);
        $include_css = waRequest::param('css', false);
        $iframe = waRequest::param('iframe', false);

        $cf = new contactsFormModel();
        $signup_form = $cf->getById($form_id);
        if (!$signup_form) {
            return false;
        }

//        $cfp = new contactsFormParamsModel();
//        $signup_form_params = $cfp->get($form_id);

        $form_html = contactsProHelper::signUpForm($form_id, $include_css, $absolute, $iframe);

        $this->view->assign('form_html', $form_html);

        wa()->getResponse()->addHeader('P3P', 'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
    }
} 