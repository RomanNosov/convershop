<?php

class logsBackendSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $settings = waRequest::post('settings', array(), waRequest::TYPE_ARRAY);
        if (strlen($error = logsHelper::setPhpLogSetting(ifset($settings['php_log'], false)))) {
            $this->errors[] = $error;
        }
    }
}
