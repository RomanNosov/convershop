<?php 
$plugin_id = array('shop', 'ymproductreviews');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');
$app_settings_model->set($plugin_id, 'default_output', '1');
$app_settings_model->set($plugin_id, 'auth_key', '');
$app_settings_model->set($plugin_id, 'count', '10');
//$app_settings_model->set($plugin_id, 'grade', '');
$app_settings_model->set($plugin_id, 'sort', 'date');
$app_settings_model->set($plugin_id, 'how', 'desc');

$model = new waModel();
try {
    $sql = 'SELECT `ym_model_id` FROM `shop_product` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE  `shop_product` ADD  `ym_model_id` INT(11) DEFAULT NULL AFTER  `id` , ADD INDEX (  `ym_model_id` )';
    $model->query($sql);
}
