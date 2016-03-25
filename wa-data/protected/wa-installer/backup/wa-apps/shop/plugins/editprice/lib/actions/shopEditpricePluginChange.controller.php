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
class shopEditpricePluginChangeController extends waJsonController
{
    protected $change;
    protected $change_currency;
    protected $change_set_compare;
    /**
     * @var shopProductModel
     */
    protected $product_model;
    /**
     * @var shopProductSkusModel
     */
    protected $skus_model;

    public function execute()
    {
        $change_type = waRequest::post('type');
        $this->change = $this->toFloat(waRequest::post("change"));
        if (!$change_type) {
            $this->change = -$this->change;
        }
        $this->change_currency = waRequest::post('currency');
        $this->change_set_compare = waRequest::post('set_compare');

        $clear_compare = waRequest::post('clear_compare');

        $this->product_model = new shopProductModel();
        $this->skus_model = new shopProductSkusModel();

        if (!$this->change && !$clear_compare) {
            return;
        }

        $hash = waRequest::post('hash', '');
        if (!$hash) {
            $product_ids = waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT);
            if (!$product_ids) {
                return;
            }
            if ($clear_compare) {
                $this->product_model->updateById($product_ids, array('compare_price' => 0));
                $this->skus_model->updateByField('product_id', $product_ids, array('compare_price' => 0));
            }
            if ($this->change) {
                $products = $this->product_model->select('id,price,sku_id,currency')->where('id IN (i:ids)', array('ids' => $product_ids))->fetchAll('id');
                $this->changePrice($products);
            }
        } else {
            $collection = new shopProductsCollection(urldecode($hash));
            $offset = 0;
            $count = 100;
            $total_count = $collection->count();
            $collection->orderBy('id');
            while ($offset < $total_count) {
                $products = $collection->getProducts('id,price,sku_id,currency,name,url', $offset, $count);
                if ($clear_compare && $products) {
                    $this->product_model->updateById(array_keys($products), array('compare_price' => 0));
                    $this->skus_model->updateByField('product_id', array_keys($products), array('compare_price' => 0));
                }
                if ($this->change) {
                    $this->changePrice($products);
                }
                $offset += count($products);
            }
        }
    }

    /**
     * @param array $products
     */
    protected function changePrice($products)
    {
        if (!$products) {
            return;
        }
        $primary_currency = wa()->getConfig()->getCurrency();
        // update skus
        $skus = $this->skus_model->select('id,product_id,price')->where('product_id IN (i:ids)', array('ids' => array_keys($products)))->fetchAll('id');
        foreach ($skus as $sku) {
            $p = $products[$sku['product_id']];
            $update = array();
            $update['price'] = $this->getPrice($sku['price'], $p['currency']);
            if ($this->change_set_compare) {
                $update['compare_price'] = $sku['price'];
            }
            if ($p['currency'] == $primary_currency) {
                $update['primary_price'] = $update['price'];
            } else {
                $update['primary_price'] = shop_currency($update['price'], $p['currency'], $primary_currency, false);
            }
            $this->skus_model->updateById($sku['id'], $update);

            // update product prices
            if (!isset($p['min_price']) || $update['primary_price'] < $p['min_price']) {
                $products[$sku['product_id']]['min_price'] = $update['primary_price'];
            }
            if (!isset($p['max_price']) || $update['primary_price'] > $p['max_price']) {
                $products[$sku['product_id']]['max_price'] = $update['primary_price'];
            }

            if ($p['sku_id'] == $sku['id']) {
                $products[$sku['product_id']]['price'] = $update['primary_price'];
                if ($this->change_set_compare) {
                    if ($p['currency'] == $primary_currency) {
                        $products[$sku['product_id']]['compare_price'] = $update['compare_price'];
                    } else {
                        $products[$sku['product_id']]['compare_price'] = shop_currency($update['compare_price'], $p['currency'], $primary_currency, false);
                    }
                }
            }
        }
        // update products
        foreach ($products as $p) {
            if (!isset($p['min_price'])) {
                continue;
            }
            $update = array(
                'price' => $p['price'],
                'min_price' => $p['min_price'],
                'max_price' => $p['max_price'],
            );
            if ($this->change_set_compare) {
                $update['compare_price'] = $p['compare_price'];
            }
            $this->product_model->updateById($p['id'], $update);
        }
    }

    /**
     * @param float $price
     * @param string $currency
     * @return float
     */
    protected function getPrice($price, $currency)
    {
        if ($this->change_currency == '%') {
            $price += $price * $this->change / 100;
            if (($round = waRequest::post('round', '')) !== '') {
                $round = (int)$round;
                if ($round >= 0) {
                    $price = round($price, $round);
                } else {
                    $k = pow(10, -$round);
                    $price = round($price / $k) * $k;
                }
            }
        } else {
            if ($this->change_currency == $currency) {
                $price += $this->change;
            } else {
                $price += shop_currency($this->change, $this->change_currency, $currency, false);
            }
        }
        return $price;
    }

    /**
     * @param string $str
     * @return float
     */
    protected function toFloat($str)
    {
        return (float)str_replace(array(',', ' '), array('.', ''), $str);
    }
}