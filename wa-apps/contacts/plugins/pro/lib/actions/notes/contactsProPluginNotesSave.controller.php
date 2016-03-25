<?php

class contactsProPluginNotesSaveController extends waJsonController
{
    /**
     *
     * @var contactsNotesModel
     */
    private $model;

    public function __construct() {
        $this->model = new contactsNotesModel();
    }

    public function execute()
    {
        // note id
        $id = $this->getRequest()->post('id');

        $contact_id = $this->getRequest()->post('contact_id');
        if (!$id && !$contact_id) {
            throw new waException(_wp("Unknown contact"));
        }

        if (!$contact_id) {
            $note = $this->getNote($id);
            $contact_id = ifset($note['contact_id'], 0);
        }

        $contact = new waContact($contact_id);
        if (!$contact->exists()) {
            throw new waException(_wp("Unknown contact"));
        }
        if (!wa()->getUser()->getRights('contacts', 'edit') && $contact['create_contact_id'] != wa()->getUser()->getId()) {
            throw new waRightsException(_w('Access denied'));
        }

        $text = $this->getRequest()->post('text', null, waRequest::TYPE_STRING_TRIM);
        if (!$text && $text !== '0') {
            throw new waException(_wp("Empty text"));
        }

        if (!$id) {
            $this->response = $this->addNote(array(
                'contact_id' => $contact_id,
                'text' => $text
            ));
        } else {
            $this->response = $this->editNote($id, array(
                'text' => $text
            ));
        }
    }

    public function addNote($data)
    {
        $note_id = $this->model->add($data['contact_id'], $data['text']);
        $note = $this->getNote($note_id);
        $this->logAction('note_add', null, $data['contact_id'], $note['create_contact_id']);
        return $note;
    }

    public function editNote($id, $data)
    {
        $this->model->edit($id, $data['text']);
        $note = $this->getNote($id);
        $this->logAction('note_edit', null, $note['contact_id'], $note['create_contact_id']);
        return $note;
    }

    public function getNote($id)
    {
        $note = $this->model->getById($id);
        $creator = new waContact($note['create_contact_id']);
        $note['creator'] = array(
            'id' => $creator->getId(),
            'name' => htmlspecialchars($creator->getName()),
            'photo' => $creator->getPhoto(20)
        );
        $note['create_datetime_str'] = waDateTime::format('humandatetime', $note['create_datetime']);
        $note['text'] = htmlspecialchars($note['text']);
        return $note;
    }
}