<?php

class contactsProPluginSearchShopItemAction extends waViewAction
{
    protected $params;
    public function __construct($params = null) {
        $this->params = $params;
        return parent::__construct($params);
    }
    public function execute()
    {
        $this->view->assign(array(
            'uniqid' => uniqid('contacts_pro_shop_search')
        ) + $this->params);
    }
}