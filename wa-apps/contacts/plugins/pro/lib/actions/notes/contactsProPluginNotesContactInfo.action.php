<?php

class contactsProPluginNotesContactInfoAction extends waViewAction
{
    public function execute()
    {
        $app_id = 'contacts';
        
        $contact_id = $this->getRequest()->get('id');
        if (!$contact_id) {
            throw new waException(_wp("Unknown contact"));
        }
        $contact = new waContact($contact_id);
        if (!$contact->exists()) {
            throw new waException(_wp("Unknown contact"));
        }
        
        if (!wa()->getUser()->getRights('contacts', 'edit') && $contact['create_contact_id'] != wa()->getUser()->getId()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $model = new contactsNotesModel();
        $notes = $model->getByContactId($contact_id);
        foreach ($notes as &$n) {
            $creator = new waContact($n['create_contact_id']);
            if ($creator->exists()) {
                $n['creator'] = array(
                    'id' => $creator->getId(),
                    'name' => $creator->getName(),
                    'photo' => $creator->getPhoto(20)
                );
            } else {
                $n['creator'] = array();
            }
        }
        unset($n);

        $this->view->assign(array(
            'contact_id' => $contact_id,
            'notes' => $notes,
            'app_url' =>  wa()->getAppUrl($app_id)
        ));
        
    }
}

// EOF