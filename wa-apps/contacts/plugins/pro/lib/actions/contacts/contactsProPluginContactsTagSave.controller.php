<?php


class contactsProPluginContactsTagSaveController extends waJsonController
{
    public function execute()
    {        
        $contact_id = waRequest::request('contact_id', null, waRequest::TYPE_INT);
      
        $cr = new contactsRightsModel();
        $can_edit = $cr->getRight(null, $contact_id) !== 'read';
        if (!$can_edit) {
            throw new waRightsException(_w('Access denied'));
        }
        
        if ($contact_id) {
            $c = new waContact($contact_id);
            if ($c->exists()) {
                $tags = array_map('trim', (array) waRequest::request('tag', array()));
                $tag_model = new contactsTagModel();
                $ids = $tag_model->getIds($tags);
                $rtm = new contactsContactTagsModel();
                $rtm->assign($contact_id, $ids);
            }
        }
    }
}

