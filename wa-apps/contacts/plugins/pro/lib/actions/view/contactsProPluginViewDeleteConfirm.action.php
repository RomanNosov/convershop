<?php

class contactsProPluginViewDeleteConfirmAction extends waViewAction
{
    public function execute()
    {
        $id = $this->getRequest()->request('id');
        $m = new contactsViewModel();
        $view = $m->get($id);
        if (!$view) {
            throw new waException(_wp("Unknown view"));
        }
        $this->view->assign(array(
            'view' => $view
        ));
    }
}