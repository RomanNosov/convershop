<?php

return array(
    'login' => array(
        'title' => 'Логин',
        'value' => 'apitest',
        'description' => 'Введите полученный логин.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'password' => array(
        'title' => 'Пароль',
        'value' => 'apitest',
        'description' => 'Введите полученный пароль.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'ikn' => array(
        'title' => 'ИКН',
        'value' => '9990003041',
        'description' => 'Введите полученный ИКН.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'sandbox' => array(
        'value' => '1',
        'title' => 'Тестовый режим',
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'city' => array(
        'title' => 'Город',
        'value' => 'Москва',
        'description' => 'Введите название города пункта отправления.',
        'control_type' => waHtmlControl::SELECT,
    ),
    'rates' => array(
        'value' =>
        array(
            '4.2' => 175,
            '7.2' => 199,
            '9.6' => 215,
            '17.3' => 240,
            '30.2' => 289,
            '50.4' => 399,
        ),
        'title' => 'Тарифы',
        'control_type' => 'RatesControl',
    ),
    'zones' => array(
        'value' => array(
            '-1' => 0.0,
            '0' => 8.0,
            '1' => 10.1,
            '2' => 16.8,
            '3' => 29.8,
            '4' => 43.2,
            '5' => 86.0,
            '6' => 117.0,
            '7' => 147.4,
            '8' => 201.0,
        ),
        'title' => 'Магистральные перевозки',
        'control_type' => 'ZonesRatesControl',
    ),
    'amount_free_delivery' => array(
        'value' => 9999999,
        'title' => 'Сумма заказа для бесплатной доставки',
        'description' => 'Если сумма заказа превышает указанное число, стоимость доставки будет равна 0',
        'control_type' => waHtmlControl::INPUT,
    ),
    'default_price' => array(
        'value' => 300,
        'title' => 'Стоимость доставки по умолчанию',
        'description' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
);

//EOF