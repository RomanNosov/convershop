<?php

return array(
    'compare_price' => array(
        'title' => /*_wp*/('Edit compare price'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'purchase_price' => array(
        'title' => /*_wp*/('Edit purchase price'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'sku' => array(
        'title' => /*_wp*/('Edit SKU Code'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'currency' => array(
        'title' => /*_wp*/('Bulk editing currency'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'status' => array(
        'title' => /*_wp*/('Edit product status'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'available' => array(
        'title' => /*_wp*/('Edit "Available for purchase" for SKU'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'percent' => array(
        'title' => /*_wp*/('Percentage change'),
        'description' => /*_wp*/('By default, you can specify the factor by which we must multiply all prices, <br>If the tick is enabled, instead of the factor will be percentage change'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'round' => array(
        'title' => /*_wp*/('The number of decimal places when rounding (when multiplied)'),
        'description' => /*_wp*/('Specify 0 to round to the nearest whole'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'int_round' => array(
        'title' => /*_wp*/('Rounding up (when multiplied)'),
        'description' => /*_wp*/('If you specify 99, all prices will end at 99 (in this case, the prices shall be rounded to the nearest whole machine) <br> If unsure, it is best to not check this setting'),
        'control_type' => waHtmlControl::INPUT,
    ),
);