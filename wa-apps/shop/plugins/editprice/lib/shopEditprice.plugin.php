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
class shopEditpricePlugin extends shopPlugin
{
    public function backendProducts()
    {
        // check rights
        if (!wa()->getUser()->isAdmin('shop') && !wa()->getUser()->getRights('shop', 'type.%')) {
            return;
        }
        /**
         * @var waSmarty3View $view
         */
        $view = wa()->getView();
        $view->assign('plugin_url', $this->getPluginStaticUrl());
        $view->assign('plugin_version', waSystemConfig::isDebug() ? time() : ifset($this->info['version'], '1.0'));
        $round = $this->getSettings('round', '');
        $view->assign('round', strlen($round) ? $round : '');
        $int_round = $this->getSettings('int_round', '');
        $view->assign('int_round', strlen($int_round) ? $int_round : '');
        $view->assign('percent', (int)$this->getSettings('percent'));
        $view->assign('currency', $this->getSettings('currency'));

        if (!wa()->getSetting('use_product_currency', '', 'shop')) {
            $view->assign('primary_currency', wa()->getConfig()->getCurrency());
        }
        $view->assign('currencies', wa('shop')->getConfig()->getCurrencies());
        $strings = array();
        foreach (array(
            'Are you sure you want to cancel all changes?',
            'SKU code',
            'Apply',
            'cancel',
            'An unexpected error occurred while saving data',
            'Published',
            'Available for purchase',
            'Compare price',
            'Purchase price',
            'Bulk editing of prices',
                 ) as $str) {
            $strings[$str] = _wp($str);
        }
        $view->assign('editprice_strings', $strings);
        return array(
            'toolbar_section' => $view->fetch($this->path.'/templates/Toolbar.html')
        );
    }
}