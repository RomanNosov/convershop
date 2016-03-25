<?php
class shopError301PluginSettingsSaveController extends waJsonController {
    
    public function execute()
    {
        $plugin_id = array('shop', 'error301');
		$error301 = waRequest::post('status');
		
		$application_settings_model = new waAppSettingsModel();
        $application_settings_model->set($plugin_id, 'status', isset($error301) ? 1 : 0);

		if(isset($error301))
			shopError301Plugin::index();
    }
}