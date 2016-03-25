<?php

$model = new waModel();
try {
    $model->query('DROP TABLE shop_cartsreport_plugin_cart');
} catch (waDbException $e) {
    waLog::log('Unable to drop "shop_cartsreport_plugin_cart" table.');
}
