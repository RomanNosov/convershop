<?php

return array(
    'name' => 'PickPoint',
    'description' => 'Расчет стоимости доставки через http://pickpoint.ru/',
    'icon' => 'img/dpickpoint16.png',
    'logo' => 'img/dpickpoint.png',
    'version' => '1.2.1',
    'vendor' => '985310',
    // соответствие событие => обработчик (название метода в классе плагина)
    'handlers' => array(
        'order_action.process' => 'sendOrder',
    ),
);
