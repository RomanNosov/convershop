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
class shopEditPricePluginSaveController extends waJsonController
{

    protected function toFloat($str)
    {
        return (float)str_replace(array(',', ' '), array('.', ''), $str);
    }

    public function execute()
    {
        $data = waRequest::post('data');
        if (!$data) {
            $this->response = array();
            return;
        }

        $product_model = new shopProductModel();
        $products = $product_model->select('id,sku_id,count,currency,sku_type,status')->where('id IN (i:ids)', array('ids' => array_keys($data)))->fetchAll('id');
        $primary_currency = wa()->getConfig()->getCurrency();

        $skus_model = new shopProductSkusModel();
        $rows = $skus_model->select('*')->where('product_id IN (i:ids)', array('ids' => array_keys($data)))->fetchAll();
        $old_data = array();
        foreach ($rows as $row) {
            $product_id = $row['product_id'];
            unset($row['product_id']);
            $sku_id = $row['id'];
            unset($row['id']);
            $old_data[$product_id][$sku_id] = $row;
        }

        $product_stocks_model = new shopProductStocksModel();
        $rows = $product_stocks_model->getByField('product_id', array_keys($data), true);
        foreach ($rows as $row) {
            // ignore trash rows (deleted skus)
            if (isset($old_data[$row['product_id']][$row['sku_id']])) {
                $old_data[$row['product_id']][$row['sku_id']]['stock_id'] = $row['stock_id'];
            }
        }

        $stocks_log_model = new shopProductStocksLogModel();
        shopProductStocksLogModel::setContext('plugin_editprice', _wp('Quick price editing'));

        foreach ($data as $product_id => $product_data) {
            // product not exists
            if (!isset($products[$product_id])) {
                continue;
            }
            if (isset($product_data['currency'])) {
                $currency = $product_data['currency'];
                unset($product_data['currency']);
            } else {
                $currency = $products[$product_id]['currency'];
            }

            if (isset($product_data['sku_id'])) {
                $product_sku_id = $product_data['sku_id'];
                unset($product_data['sku_id']);
            } else {
                $product_sku_id = $products[$product_id]['sku_id'];
            }

            if (isset($product_data['status'])) {
                $product_status = $product_data['status'];
                unset($product_data['status']);
            } else {
                $product_status = $products[$product_id]['status'];
            }

            // incorrect data
            if (count($product_data) != count($old_data[$product_id])) {
                $product_data = $old_data[$product_id];
            }

            $is_changed = ($product_sku_id == $products[$product_id]['sku_id'] &&
                $product_status == $products[$product_id]['status']) ? false: true;

            $old_currency = $products[$product_id]['currency'];
            $prices = $compare_prices = array();
            $count = false;
            $update_skus = array();
            foreach ($product_data as $sku_id => $sku) {
                $price = $this->toFloat($sku['price']);
                $prices[$sku_id] = $price;
                $update = array();
                if ($price != (float)$old_data[$product_id][$sku_id]['price'] || $currency != $old_currency) {
                    $is_changed = true;
                    $update['price'] = $price;
                    // save sku
                    if ($currency == $primary_currency) {
                        $update['primary_price'] = $price;
                    } else {
                        $update['primary_price'] = shop_currency($price, $currency, $primary_currency, false);
                    }
                }
                if (isset($sku['compare_price'])) {
                    $compare_price = $this->toFloat($sku['compare_price']);
                    $compare_prices[$sku_id] = $compare_price;
                    if ($compare_price != (float)$old_data[$product_id][$sku_id]['compare_price']) {
                        $is_changed = true;
                        $update['compare_price'] = $compare_price;
                    }
                }
                if (isset($sku['purchase_price'])) {
                    if ($this->toFloat($sku['purchase_price']) != (float)$old_data[$product_id][$sku_id]['purchase_price']) {
                        $is_changed = true;
                        $update['purchase_price'] = $this->toFloat($sku['purchase_price']);
                    }
                }

                if (isset($sku['available'])) {
                    if ($old_data[$product_id][$sku_id]['available'] != $sku['available']) {
                        $is_changed = true;
                        $update['available'] = $sku['available'];
                    }
                }

                if (array_key_exists('count', $sku)) {
                    if (trim($sku['count']) === '') {
                        $sku['count'] = null;
                    }
                    if ($sku['count'] !== $old_data[$product_id][$sku_id]['count']) {
                        $is_changed = true;
                        $update['count'] = $sku['count'];
                    }
                    if ($old_data[$product_id][$sku_id]['available'] || !empty($update['available'])) {
                        if ($count === false) {
                            $count = $sku['count'];
                        } elseif ($sku['count'] !== null && $count !== null) {
                            $count += $sku['count'];
                        } else {
                            $count = null;
                        }
                    }
                }
                if (isset($sku['sku'])) {
                    $update['sku'] = $sku['sku'];
                }
                $update_skus[$sku_id] = $old_data[$product_id][$sku_id];
                if ($update) {
                    foreach ($update as $k => $v) {
                        $update_skus[$sku_id][$k] = $v;
                    }
                    if ($old_data[$product_id][$sku_id]['virtual']) {
                        $update['virtual'] = 0;
                    }
                    $skus_model->updateById($sku_id, $update);

                    // update stock
                    if (array_key_exists('count', $update)) {
                        if (!empty($old_data[$product_id][$sku_id]['stock_id'])) {
                            $where = array('sku_id' => $sku_id, 'stock_id' => $old_data[$product_id][$sku_id]['stock_id']);
                            if ($update['count'] === null) {
                                $product_stocks_model->deleteByField($where);
                            } else {
                                $product_stocks_model->updateByField($where, array('count' => $update['count']));
                            }
                            $stock_id = $old_data[$product_id][$sku_id]['stock_id'];
                        } else {
                            $stock_id = null;
                        }
                        // save to stock log
                        $stocks_log_model->add(array(
                            'product_id' => $product_id,
                            'sku_id' => $sku_id,
                            'stock_id' => $stock_id,
                            'before_count' => $old_data[$product_id][$sku_id]['count'],
                            'after_count' => $update['count'],
                        ));
                    }
                }
            }
            $min_price = min($prices);
            $max_price = max($prices);
            $price = $prices[$product_sku_id];
            if ($is_changed) {
                // save product
                $update = array(
                    'price' => shop_currency($price, $currency, $primary_currency, false),
                    'currency' => $currency,
                    'min_price' => shop_currency($min_price, $currency, $primary_currency, false),
                    'max_price' => shop_currency($max_price, $currency, $primary_currency, false),
                );
                if ($product_sku_id != $products[$product_id]['sku_id']) {
                    $update['sku_id'] = $product_sku_id;
                }
                if ($product_status != $products[$product_id]['status']) {
                    $update['status'] = $product_status;
                }

                if ($products[$product_id]['sku_type']) {
                    $update['base_price_selectable'] = $update['price'];
                }
                if ($count !== false) {
                    $update['count'] = $count;
                }
                if ($compare_prices) {
                    $update['compare_price'] = shop_currency($compare_prices[$product_sku_id], $currency, $primary_currency, false);
                }
                $product_model->updateById($product_id, $update);

                // save event
                foreach ($update_skus as $sku_id => $s) {
                    $update_skus[$sku_id]['id'] = $sku_id;
                }
                $event_params = array('data' => array('skus' => $update_skus, 'sku_id' => $product_sku_id),
                    'id' => $product_id, 'instance' => new shopProduct($product_id));
                wa()->event('product_save', $event_params);
            }
            if ($count !== false) {
                $this->response[$product_id]['count'] = $count;
            }
            $this->response[$product_id]['status'] = $product_status ? true : false;
            if ($min_price == $max_price) {
                $this->response[$product_id]['price'] = shop_currency($price, $currency, $primary_currency);
            } else {
                $this->response[$product_id]['price'] = shop_currency($min_price, $currency, $primary_currency).'..'.
                    shop_currency($max_price, $currency, $primary_currency);
            }
        }
    }
}