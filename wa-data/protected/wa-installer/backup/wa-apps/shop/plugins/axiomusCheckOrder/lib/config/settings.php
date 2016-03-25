<?php

return array(
    'test_mode' => array(
        'value'        => false,
        'title'        => /*_wp*/('Test environment'),
        'description'  => /*_wp*/('Enable to run Axiomus module in test environment. Disable when moving to production.'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'uid' => array(
        'value' => '',
        'title' => /*_wp*/('Axiomus uid'),
        'description' => /*_wp*/('ваш личный uid можете получить axiomus.ru '),
        'control_type' => waHtmlControl::INPUT
    ),
    'ukey' => array(
        'value' => '',
        'title' => /*_wp*/('Axiomus ukey'),
        'description' => /*_wp*/('ваш личный ukey можете получить axiomus.ru '),
        'control_type' => waHtmlControl::INPUT
    ),
    'url' => array(
        'value' => '',
        'title' => /*_wp*/('URL'),
        'description' => 'Url для тестовых запросов: http://axiomus.ru/test/api_xml_test.php',
        'control_type' => waHtmlControl::INPUT
    ),
    
);
//EOF
