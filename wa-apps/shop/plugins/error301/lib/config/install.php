<?php
$plugin_id = array('shop', 'error301');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'update_time', time());
$db = new shopError301Model();
$db->query("ALTER TABLE `".$db->table."` ADD UNIQUE INDEX `unqkey` (`type`, `url`);");     