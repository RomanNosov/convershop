<?php

return array(
    'name' => _wp('Detailed report'),
    'description' => _wp('Detailed report for sales and profit'),
    'vendor'=>1005676,
    'version'=>'1.3',
    'shop_settings' => FALSE,
    'frontend'    => FALSE,
    'img'=>'img/icon.png',
    'handlers' => array(
        'backend_reports' => 'backendReports',
    ),
    'locale' => array('en_US', 'ru_RU')
);
