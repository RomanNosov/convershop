<?php
/**
 * Created by PhpStorm.
 * User: Nikita
 * Date: 05.03.2016
 * Time: 21:35
 */

class shopShippingDescriptionPlugin extends shopPlugin{
    public static $selfInfo = array(
        'id' => 'shippingDescription',
        'app_id' => 'shop'
    );

    public function saveSettings($settings = array()){
        if(isset($settings['rates'])) {
            $rates = $settings['rates'];
            unset($settings['rates']);
            $settings['shippingData'] = array();
            $settings['shippingOrder'] = array();
            foreach ($rates['method_id'] as $mKey => $m) {
                $rate = $rates['rate_id'][$mKey];
                $settings['shippingOrder'][] = array('id' => $m, 'rate' => $rate);
                if(!isset($settings['shippingData'][$m])){
                    $settings['shippingData'][$m] = array();
                }
                $settings['shippingData'][$m][$rate] = array(
                    'description' => $rates['description'][$mKey],
                    'active' => isset($rates['active'][$m.$rate]),
                    'enabled' => isset($rates['enabled'][$m.$rate]),
                    'discount' => $rates['discount'][$mKey],
                    'discount_text' => $rates['discount_text'][$mKey]
                );
            }
        }
        if(isset($settings['payment'])) {
            $payments = $settings['payment'];
            unset($settings['payment']);
            $settings['payData'] = array();
            foreach($payments['method_id'] as $pKey => $p){
                $settings['payData'][$p] = array('discount' => $payments['discount'][$pKey], 'discount_text' => $payments['discount_text'][$pKey]);
            }
        }
        if(isset($settings['contact_info'])){
            $contactInfo = $settings['contact_info'];
            unset($settings['contact_info']);
            $settings['contactInfo'] = array();
            foreach($contactInfo['discount'] as $key => $ci){
                $settings['contactInfo'][$key] = array('discount' => $ci, 'discount_text' => $contactInfo['discount_text'][$key]);
            }
        }
        parent::saveSettings($settings);
    }

    /**
     * Возвращает html-код с последними фото
     * @param array $shippingMethods
     * @return mixed|string
     */
    public static function getControl(array $shippingMethods = array()){
        $plugin = new self(static::$selfInfo);
        $settings = $plugin->getSettings();
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
        $view->assign('emailData', json_encode($settings['contactInfo']['email']));
        $view->assign('phoneData', json_encode($settings['contactInfo']['phone']));
        $view->assign('methods', $shippingMethods);
        $view->assign('selfInfo', static::$selfInfo);
        return $view->fetch('wa-apps/'.static::$selfInfo['app_id'].'/plugins/'.static::$selfInfo['id'].'/templates/default.html');
    }
}