<?php

class contactsProPluginContactsInfoTagsAction extends waViewAction
{
    public function __construct($params = null) {
        parent::__construct($params);
        if (empty($this->params['contact_id'])) {
            $this->params['contact_id'] = wa()->getRequest()->request('contact_id', null, waRequest::TYPE_INT);
        }
    }
    public function execute()
    {
        $cr = new contactsRightsModel();
        $contact_id = $this->params['contact_id'];
        $can_edit = $cr->getRight(null, $contact_id) !== 'read';
        
        $rtm = new contactsContactTagsModel();
        $tags = $rtm->getTags($contact_id);
        foreach ($tags as &$tag) {
            $tag = htmlspecialchars($tag);
        }
        unset($tag);
        
        $tm = new contactsTagModel();
        $all_tags = $tm->getAll('id');
        
        $this->view->assign(array(
            'contact_id' => $contact_id,
            'can_edit' => $can_edit,
            'tags' => $tags,
            'all_tags' => $all_tags,
            'app_url' =>  wa()->getAppUrl('contacts')
        ));
    }
}

// EOF
