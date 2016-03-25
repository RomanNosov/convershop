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
class shopEditpricePluginSkusController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');

        if (wa()->getSetting('use_product_currency', '', 'shop')) {
            /**
             * @var shopConfig $shop_config
             */
            $shop_config = wa('shop')->getConfig();
            $currencies = $shop_config->getCurrencies();
            foreach ($currencies as $c) {
                $this->response['currencies'][] = $c['code'];
            }
        }

        $stock_model = new shopStockModel();
        $this->response['stocks'] = $stock_model->select('id,name')->fetchAll();

        /**
         * @var shopEditpricePlugin $plugin
         */
        $plugin = wa()->getPlugin('editprice');

        $skus_model = new shopProductSkusModel();
        $sql = "SELECT s.id, s.product_id, s.name, s.sku, s.price, ";
        if ($plugin->getSettings('purchase_price')) {
            $sql .= "s.purchase_price, ";
        }
        if ($plugin->getSettings('compare_price')) {
            $sql .= "s.compare_price, ";
        }
        $sql .= "s.count, s.available, p.currency, p.sku_id AS default_sku_id FROM ".$skus_model->getTableName()." s
        JOIN shop_product p ON s.product_id = p.id
        WHERE s.product_id IN (i:ids)
        ORDER BY s.sort";
        $rows = $skus_model->query($sql, array('ids' => (array)$id));
        $skus = array();
        foreach ($rows as $row) {
            $row['name'] = htmlspecialchars($row['name']);
            $row['price'] = (float)$row['price'];
            if ($plugin->getSettings('purchase_price')) {
                $row['purchase_price'] = (float)$row['purchase_price'];
            }
            if ($plugin->getSettings('compare_price')) {
                $row['compare_price'] = (float)$row['compare_price'];
            }
            $product_id = $row['product_id'];
            if ($row['default_sku_id'] == $row['id']) {
                $row['default'] = 1;
            }
            unset($row['product_id']);
            unset($row['default_sku_id']);
            $skus[$product_id][] = $row;
        }
        if ($plugin->getSettings('sku')) {
            $this->response['edit_sku'] = 1;
        }
        if ($plugin->getSettings('status')) {
            $this->response['edit_status'] = 1;
        }
        if ($plugin->getSettings('available')) {
            $this->response['edit_available'] = 1;
        }
        $this->response['skus'] = $skus;
    }
}