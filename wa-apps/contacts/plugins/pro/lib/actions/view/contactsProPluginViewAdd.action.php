<?php

class contactsProPluginViewAddAction extends waViewAction
{
    public function execute()
    {        
        $view_model = new contactsViewModel();
        $views = array(
            'common' => array(),
            'my' => array()
        );
        
        $current_view_id = $this->getRequest()->request('view_id', null, waRequest::TYPE_INT);
        
        $contact_id = wa()->getUser()->getId();
        
        $all_lists = $view_model->getAllViews(array('category'));
        contactsViewModel::setIcons($all_lists);
        
        foreach ($all_lists as $view) {
            if (($view['type'] === 'category' && !empty($view['system_id'])) || $view['id'] == $current_view_id) {
                continue;
            }
            $views[$view['contact_id'] == $contact_id && !$view['shared'] ? 'my' : 'common'][] = $view;
        }
        $this->view->assign(array(
            'views' => $views
        ));
    }
}