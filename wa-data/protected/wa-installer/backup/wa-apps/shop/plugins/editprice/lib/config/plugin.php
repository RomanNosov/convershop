<?php

/**
 * @copyright 2013-2015 wa-apps.ru wa-apps.com
 *
 * RU
 * @author wa-apps.ru <info@wa-apps.ru>
 * @license Webasyst License http://www.webasyst.ru/terms/#eula
 * @link http://www.webasyst.ru/store/plugin/shop/editptice/
 *
 * EN
 * @license Webasyst License http://www.webasyst.com/terms/#eula
 * @author wa-apps.com <info@wa-apps.com>
 * @link http://www.webasyst.com/store/plugin/shop/editptice/
 */
return array(
    'name' => /*_wp*/('Quick price editing'),
    'description' => /*_wp*/('Allows to edit products prices straight from the list'),
    'version'=>'2.0',
    'vendor' => 809114,
    'img'=>'img/editprice.png',
    'handlers' => array(
        'backend_products' => 'backendProducts'
    ),
);
