<?php

/**
 * Created by PhpStorm.
 * User: Nikita
 * Date: 03.04.2016
 * Time: 11:22
 */
class shopShippingDescriptionPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plug = wa(shopShippingDescriptionPlugin::$selfInfo['app_id'])->getPlugin(shopShippingDescriptionPlugin::$selfInfo['id']);
        $plugin = new shopShippingDescriptionPlugin(shopShippingDescriptionPlugin::$selfInfo);
        $settings = $plugin->getSettings();
        $plugin_model = new shopPluginModel();
        $methods = $plugin_model->listPlugins('shipping');
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
        foreach ($methods as $id => $m) {
            $methods[$id]['rates'] = shopShipping::getPlugin(null, $id)->getRates($items, $address, (new shopCart())->total());
            $methods[$id]['total_r_count'] = sizeof($methods[$id]['rates']);
        }
        $newMethods = $methods;
        foreach($settings['shippingOrder'] as $so) {
            unset($newMethods[$so['id']]['rates'][$so['rate']]);
            if (count($newMethods[$so['id']]['rates']) === 0) {
                unset($newMethods[$so['id']]);
            }
        }
        $this->view->assign('settings', $settings);
        $this->view->assign('methods', $methods);
        $this->view->assign('newMethods', $newMethods);
        $this->view->assign('paymentPlugins', $plugin_model->listPlugins(shopPluginModel::TYPE_PAYMENT, array('all' => true, )));
        $this->view->assign('selfInfo', shopShippingDescriptionPlugin::$selfInfo);
        $this->view->assign('shippingData', $settings['shippingData']);
        $this->view->assign('shippingOrder', $settings['shippingOrder']);
        $this->view->assign('paymentData', $settings['paymentData']);
        $this->view->assign('contactInfoData', $settings['contactInfo']);
        $this->view->assign('pluginName', $plug->getName());
    }
}