<?php

class shopError301PluginSettingsAction extends waViewAction
{
    public function execute()
    {
		$model_settings = new waAppSettingsModel();
        $status = $model_settings->get($key = array('shop', 'error301'));         
        $this->view->assign('status', isset($status['status']) ? $status['status'] : 0);
	}   
}