<?php

class contactsProPluginBackendSidebarAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'cloud_html' => $this->getCloud(),
            'is_super_admin' => wa()->getUser()->isAdmin(),
            'is_admin' => wa()->getUser()->getRights('contacts', 'backend') > 1,
            'counters' => array(
                'notes' => $this->notesCounters()
            )
        ) + $this->getViews());
    }
    
    public function getViews()
    {
        $view_model = new contactsViewModel();
        $shared = array();
        $my = array();
        
        $all_views = $view_model->getAllViews(null, true);
        contactsViewModel::setIcons($all_views);
        
        foreach ($all_views as $view) {
            if (!$view['shared']) {
                $my[$view['id']] = $view;
            } else {
                $shared[$view['id']] = $view;
            }
        }
        return array(
            'shared_views' => $shared,
            'my_views' => $my
        );
    }
    
    public function notesCounters()
    {
        $nm = new contactsNotesModel();
        return array(
            'all' => $nm->countAll()
        );
    }
    
    public function getCloud()
    {
        return contactsProHelper::chainViewAction(new contactsProPluginBackendTagsCloudAction());
    }
}
