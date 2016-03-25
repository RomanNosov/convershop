<?php

class contactsProPluginNotesAddAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'edit' => wa()->getUser()->getRights('contacts', 'edit')
        ));
    }
}