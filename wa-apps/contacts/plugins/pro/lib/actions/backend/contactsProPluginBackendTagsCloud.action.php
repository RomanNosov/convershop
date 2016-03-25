<?php

class contactsProPluginBackendTagsCloudAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'cloud' => $this->getCloud()
        ));
    }
    
    public function getCloud()
    {
        if (!$this->appSettings('tags_disabled')) {
            $tm = new contactsTagModel();
            return $tm->getCloud();
        } else {
            return array();
        }
    }
}
