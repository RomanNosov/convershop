<?php

class contactsProPluginFilterAddAction extends waViewAction
{
    public function execute()
    {
        $name = $this->getName();
        $this->view->assign(array(
            'name' => $name
        ));
    }
    
    public function getName()
    {
        return "Filter";
    }
}