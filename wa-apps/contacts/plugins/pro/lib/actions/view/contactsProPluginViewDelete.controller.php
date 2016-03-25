<?php

class contactsProPluginViewDeleteController extends waJsonController
{
    public function execute()
    {
        $id = $this->getRequest()->post('id');
        $m = new contactsViewModel();
        $m->delete($id);
    }
}