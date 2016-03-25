<?php

class contactsProPluginSearchConditionController extends waController
{  
    public function execute()
    {
        $id = $this->getRequest()->get('id');
        $op = $this->getRequest()->request('op');
        
        $user = wa()->getUser();
        $app_id = 'contacts';
        $name = 'search_form_items';

        if ($id) {
            if ($op === 'delete') {
                contactsSearchHelper::delContactItems($id);
                return;
            } else if ($op === 'remember') {
                contactsSearchHelper::setContactItems($id);
                return;
            } else if ($op === 'collapse_section' || $op === 'expand_section') {
                $map = $user->getSettings('contacts', 'prosearch_sidebar');
                if ($map) {
                    $map = array_fill_keys(explode(',', $map), 1);
                } else {
                    $map = array();
                }
                if ($op === 'collapse_section' && isset($map[$id])) {
                    unset($map[$id]);
                }
                if ($op === 'expand_section') {
                    $map[$id] = 1;
                }
                if (!$map) {
                    $user->delSettings('contacts', 'prosearch_sidebar');
                } else {
                    $user->setSettings('contacts', 'prosearch_sidebar', array_keys($map));
                }
                return;                
            }
        }
        
        echo wao(new contactsProPluginSearchConditionAction())->display();
        
    }
}