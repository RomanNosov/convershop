<?php
/**
 * Created by PhpStorm.
 * User: Nikita
 * Date: 05.03.2016
 * Time: 21:35
 */

class shopShippingDescriptionPlugin extends shopPlugin{
    protected static $selfInfo = [
        'id' => 'shippingDescription',
        'app_id' => 'shop'
    ];

    public static function getSettingsControl(){
        $model = new shopPluginModel();
        $plugins = $model->listPlugins(shopPluginModel::TYPE_SHIPPING, array('all' => true, ));
        $rates = [];
        foreach($plugins as $pl_id => $pl){
            $plugin_model = new shopPluginModel();
            $plugin_info = $plugin_model->getById($pl_id);
            $plugin = shopShipping::getPlugin($plugin_info['plugin'], $pl_id);
            $rates[$pl_id] = $plugin->getRates(null, null, null);
        }
    }

    /**
     * Возвращает html-код с последними фото
     * @param array $shippingMethods
     * @return mixed|string
     */
    public static function getControl(array $shippingMethods = array()){
        $settings = (new self(static::$selfInfo))->getSettings();
        $settings['shippingOrder'] = [
            ['id' => '4', 'rate' => 'msc3'],
            ['id' => '8', 'rate' => 'delivery'],
            ['id' => '4', 'rate' => 'msc1'],
            ['id' => '4', 'rate' => 'msc2'],
            ['id' => '4', 'rate' => 'post']];
        $settings['shippingData'] = [
            4 => [
                'msc1' => [
                    'description' => 'Удобно если заказ нужно доставить в офис',
                    'discount' => 0.5,
                    'discount_text' => 'Ваша скидка {discount} выбирете способ оплаты',
                    'disabled' => false
                ],
                'msc2' => [
                    'description' => '',
                    'discount' => 0.5,
                    'discount_text' => 'Ваша скидка {discount} выбирете способ оплаты',
                    'disabled' => false
                ],
                'msc3' => [
                    'description' => 'м.Авиамоторная,ул.5-я Кабельная,дом2, ТЦ Спортекс. точный адрес будет в письме',
                    'discount' => 0,
                    'discount_text' => '',
                    'disabled' => false
                ],
                'post' => [
                    'description' => 'Долго,дорого,скрытые платежи 3-6%, сами знаете все...',
                    'discount' => 0.5,
                    'discount_text' => 'Ваша скидка {discount} выбирете способ оплаты',
                    'disabled' => true
                ]
            ],
            8 => [
                'delivery' => [
                    'description' => 'Быстро, дешево, удобно, без скрытых платежей',
                    'discount' => 0.5,
                    'discount_text' => 'Ваша скидка {discount} выбирете способ оплаты',
                    'disabled' => false
                ]
            ]
        ];
        $settings['payData'] = [
            7 => [
                'discount' => 1.5,
                'discount_text' => 'Ваша скидка {discount}, еще 0.5% за номер телефона.'
            ],
            5 => [
                'discount' => 0,
                'discount_text' => 'Пфффф... нормально же все было. Хотете на 1.5% больше - выберите оплату картой'
            ]
        ];
        $settings['phone'] = [
            'discount' => 0.5,
            'discount_text' => 'Ваша скидка {discount}, введите email и получите еще 0.5%.'
        ];
        $settings['email'] = [
            'discount' => 0.5,
            'discount_text' => 'Ваша скидка {discount}, перейдите на шаг 2'
        ];
        if(!$settings['active']){
            return '';
        }
        $shipping = new shopCheckoutShipping();
        $address = $shipping->getAddress();
        foreach ($address as $v) {
            if ($v) {
                $address = array();
                break;
            }
        }
        if (!$address) {
            $shopSettings = wa('shop')->getConfig()->getCheckoutSettings();
            if ($shopSettings['contactinfo']['fields']['address']) {
                foreach ($shopSettings['contactinfo']['fields']['address']['fields'] as $k => $f) {
                    if (!empty($f['value'])) {
                        $address[$k] = $f['value'];
                    }
                }
            }
        }
        $items = $shipping->getItems();
        foreach ($shippingMethods as $id => $m) {
            $shippingMethods[$id]['rates'] = shopShipping::getPlugin(null, $id)->getRates($items, $address, (new shopCart())->total());
        }
        $view = wa()->getView();
        $view->assign('data', $settings['shippingData']);
        $view->assign('shippingOrder', $settings['shippingOrder']);
        $view->assign('payData', json_encode($settings['payData']));
        $view->assign('emailData', json_encode($settings['email']));
        $view->assign('phoneData', json_encode($settings['phone']));
        $view->assign('methods', $shippingMethods);
        $view->assign('selfInfo', static::$selfInfo);
        return $view->fetch('wa-apps/'.static::$selfInfo['app_id'].'/plugins/'.static::$selfInfo['id'].'/templates/default.html');
    }
}