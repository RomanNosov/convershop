<?php
return array(
    'shop_cartsreport_plugin_cart' => array(
        'code' => array('varchar', 32, 'null' => 0),
        'edit_datetime' => array('datetime', 'null' => 0),
        'checkout.contactinfo' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        'checkout.shipping' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        'checkout.payment' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        'checkout.confirmation' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        'cart' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'code',
            'edit_datetime' => 'edit_datetime',
        ),
    ),
);
