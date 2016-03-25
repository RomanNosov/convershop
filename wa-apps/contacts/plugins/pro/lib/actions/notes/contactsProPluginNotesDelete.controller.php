<?php

class contactsProPluginNotesDeleteController extends waJsonController
{
    public function execute()
    {
        $note_id = $this->getRequest()->post('id');
        if (!$note_id) {
            throw new waException(_wp("Unknown note"));
        }
        
        $m = new contactsNotesModel();
        $note = $m->getById($note_id);
        
        if (!wa()->getUser()->getRights('contacts', 'edit')) {
            $contact = new waContact($note['contact_id']);
            if (!$contact->exists()) {
                throw new waException(_wp("Unknown contact"));
            }

            if ($contact['create_contact_id'] != wa()->getUser()->getId()) {
                throw new waRightsException(_w('Access denied'));
            }
        }
        
        if ($note) {
            $m->deleteById($note_id);
            $this->logAction('note_delete', null, $note['contact_id'], $this->getUser()->getId());
        }
        
        if ($this->getRequest()->request('counter')) {
            $this->response['counters'] = array(
                'all' => $m->countAll()
            );
        }
    }
}