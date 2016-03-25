<?php

class contactsProPluginImportAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $this->view->assign('encoding', $this->getEncodingList());
        
        $group_model = new waGroupModel();
        $groups = $group_model->getNames();
        $this->view->assign('groups', $groups);
    }
    
    protected function getEncodingList()
    {
        $encoding = mb_list_encodings();
        $list = array();
        foreach ($encoding as $k => $v) {
            if ($k > 10) {
                $list[$v] = $v;
            }
        }
        natcasesort($list);
        return $list;
    }    
}