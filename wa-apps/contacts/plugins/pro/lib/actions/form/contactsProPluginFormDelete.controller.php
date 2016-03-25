<?php

/**
 * Deletes Form
 */
class contactsProPluginFormDeleteController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }

        $form_id = waRequest::post('id', 0, 'int');

        $mf = new contactsFormModel();
        $mf->delete($form_id);

        $mfp = new contactsFormParamsModel();
        $mfp->set($form_id, null);

        $this->response = $form_id;
    }
}