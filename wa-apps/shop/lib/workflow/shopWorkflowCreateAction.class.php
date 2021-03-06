<?php

class shopWorkflowCreateAction extends shopWorkflowAction
{

    /**
     * @param array $data
     * @return waContact
     */
    protected function getContact($data)
    {
        if (isset($data['contact'])) {
            if (is_numeric($data['contact'])) {
                return new waContact($data['contact']);
            } else {
                /**
                 * @var waContact $contact
                 */
                $contact = $data['contact'];
                if (!$contact->getId()) {
                    /**
                     * @var shopConfig $shop_config
                     */
                    $shop_config = wa('shop')->getConfig();
                    $auth_config = wa()->getAuthConfig();
                    if (!empty($auth_config['params']['confirm_email']) &&
                        $shop_config->getGeneralSettings('guest_checkout') == 'merge_email' &&
                        $contact->get('email', 'default')) {
                        // try find exists contact by email
                        $contact_emails_model = new waContactEmailsModel();
                        $contact_id = $contact_emails_model->getMainContactMyEmail($contact->get('email', 'default'));
                        if ($contact_id) {
                            $contact_data = $contact->load();
                            $contact = new waContact($contact_id);
                            foreach ($contact_data as $k => $v) {
                                $contact->set($k, $v);
                            }
                        }
                        $contact->save();
                    } else {
                        // create new contact
                        $contact->save();
                    }
                    // if user has been created
                    if ($contact['password']) {
                        $signup_action = new shopSignupAction();
                        $signup_action->send($contact);

                        /**
                         * @event signup
                         * @param waContact $contact
                         */
                        wa()->event('signup', $contact);
                    }
                }
                return $contact;
            }
        } else {
            return wa()->getUser();
        }
    }

    public function execute($data = null)
    {
        if (wa()->getEnv() == 'frontend') {

            // Now we are in frontend, so fill stock_id for items. Stock_id get from storefront-settings
            // But:
            //   - some skus may have not any stock
            //   - stock_id from storefront isn't setted (empty)

            $sku_ids = array();
            foreach ($data['items'] as $item) {
                if ($item['type'] == 'product') {
                    $sku_ids[] = (int) $item['sku_id'];
                }
            }
            $product_stocks_model = new shopProductStocksModel();
            $sku_ids = $product_stocks_model->filterSkusByNoStocks($sku_ids);
            $sku_ids_map = array_fill_keys($sku_ids, true);

            // storefront stock-id
            $stock_id = waRequest::param('stock_id');
            $stock_model = new shopStockModel();
            if (!$stock_id || !$stock_model->stockExists($stock_id)) {
                $stock_id = $stock_model->select('id')->order('sort')->limit(1)->fetchField();
            }

            foreach ($data['items'] as &$item) {
                if ($item['type'] == 'product') {
                    if (!isset($sku_ids_map[$item['sku_id']])) {    // have stocks
                        $item['stock_id'] = $stock_id;
                    }
                }
            }
        }
        if (!empty($data['currency'])) {
            $currency = $data['currency'];
        } else {
            $currency = wa('shop')->getConfig()->getCurrency(false);
        }
        $rate_model = new shopCurrencyModel();
        $row = $rate_model->getById($currency);
        $rate = $row['rate'];

        // Save contact
        $contact = $this->getContact($data);

        // Calculate subtotal, taking currency convertion into account
        $subtotal = 0;
        foreach ($data['items'] as &$item) {
            if ($currency != $item['currency']) {
                $item['price'] = shop_currency($item['price'], $item['currency'], null, false);
                if (!empty($item['purchase_price'])) {
                    $item['purchase_price'] = shop_currency($item['purchase_price'], $item['currency'], null, false);
                }
                $item['currency'] = $currency;
            }
            $subtotal += $item['price'] * $item['quantity'];
        }
        unset($item);

        // Calculate discount, unless already set
        if ($data['discount'] === '') {
            $data['total'] = $subtotal;
            $data['discount_description'] = null;
            $data['discount'] = shopDiscounts::apply($data, $data['discount_description']);
        } else if (empty($data['discount_description']) && !empty($data['discount'])) {
            $data['discount_description'] = sprintf_wp('Discount specified manually during order creation: %s', shop_currency($data['discount'], $currency, $currency));
        }

        // Calculate taxes
        $shipping_address = $contact->getFirst('address.shipping');
        if (!$shipping_address) {
            $shipping_address = $contact->getFirst('address');
        }
        $billing_address = $contact->getFirst('address.billing');
        if (!$billing_address) {
            $billing_address = $contact->getFirst('address');
        }
        $discount_rate = $subtotal ? ($data['discount'] / $subtotal) : 0;
        $taxes = shopTaxes::apply($data['items'], array(
            'shipping' => isset($shipping_address['data']) ? $shipping_address['data'] : array(),
            'billing' => isset($billing_address['data']) ? $billing_address['data'] : array(),
            'discount_rate' => $discount_rate
        ));
        $tax = $tax_included = 0;
        foreach ($taxes as $t) {
            if (isset($t['sum'])) {
                $tax += $t['sum'];
            }
            if (isset($t['sum_included'])) {
                $tax_included += $t['sum_included'];
            }
        }

        $order = array(
            'state_id' => 'new',
            'total' => $subtotal - $data['discount'] + $data['shipping'] + $tax,
            'currency' => $currency,
            'rate' => $rate,
            'tax' => $tax_included + $tax,
            'discount' => $data['discount'],
            'shipping' => $data['shipping'],
            'comment' => isset($data['comment']) ? $data['comment'] : ''
        );
        $order['contact_id'] = $contact->getId();

        // Add contact to 'shop' category
        $contact->addToCategory('shop');

        // Save order
        $order_model = new shopOrderModel();
        $order_id = $order_model->insert($order);

        // Create record in shop_customer, or update existing record
        $scm = new shopCustomerModel();
        $scm->updateFromNewOrder($order['contact_id'], $order_id, ifset($data['params']['referer_host']));

        // save items
        $items_model = new shopOrderItemsModel();
        $parent_id = null;
        foreach ($data['items'] as $item) {
            $item['order_id'] = $order_id;
            if ($item['type'] == 'product') {
                $parent_id = $items_model->insert($item);
            } elseif ($item['type'] == 'service') {
                $item['parent_id'] = $parent_id;
                $items_model->insert($item);
            }
        }

        // Order params
        if (empty($data['params'])) {
            $data['params'] = array();
        }
        $data['params']['auth_code'] = self::generateAuthCode($order_id);
        $data['params']['auth_pin'] = self::generateAuthPin();

        // Save params
        $params_model = new shopOrderParamsModel();
        $params_model->set($order_id, $data['params']);

        // Write discounts description to order log
        if (!empty($data['discount_description']) && !empty($data['discount']) && empty($data['skip_description'])) {
            $order_log_model = new shopOrderLogModel();
            $order_log_model->add(array(
                'order_id' => $order_id,
                'contact_id' => $order['contact_id'],
                'before_state_id' => $order['state_id'],
                'after_state_id' => $order['state_id'],
                'text' => $data['discount_description'],
                'action_id' => '',
            ));
        }

        $log_model = new waLogModel();
        $log_model->add('order_create', $order_id, null, $order['contact_id']);

        return array(
            'order_id' => $order_id,
            'contact_id' => wa()->getEnv() == 'frontend' ? $contact->getId() : wa()->getUser()->getId()
        );
    }

    public function postExecute($order_id = null, $result = null)
    {
        $order_id = $result['order_id'];

        $data = is_array($result) ? $result : array();
        $data['order_id'] = $order_id;
        $data['action_id'] = $this->getId();

        $data['before_state_id'] = '';
        $data['after_state_id'] = 'new';

        $order_log_model = new shopOrderLogModel();
        $order_log_model->add($data);

        /**
         * @event order_action.create
         */
        wa('shop')->event('order_action.create', $data);

        $order_model = new shopOrderModel();
        $order = $order_model->getById($order_id);
        $params_model = new shopOrderParamsModel();
        $order['params'] = $params_model->get($order_id);
        // send notifications
        shopNotifications::send('order.'.$this->getId(), array(
            'order' => $order,
            'customer' => new waContact($order['contact_id']),
            'status' => $this->getWorkflow()->getStateById($data['after_state_id'])->getName(),
            'action_data' => $data
        ));

        // Update stock count, but take into account 'update_stock_count_on_create_order'-setting
        $app_settings_model = new waAppSettingsModel();
        if ($app_settings_model->get('shop', 'update_stock_count_on_create_order')) {
            // for logging changes in stocks
            shopProductStocksLogModel::setContext(
                shopProductStocksLogModel::TYPE_ORDER,
                /*_w*/('Order %s was placed'),
                array(
                    'order_id' => $order_id
                )
            );
            $order_model->reduceProductsFromStocks($order_id);

            shopProductStocksLogModel::clearContext();
        }

        return $order_id;
    }

    /** Random string to authorize user from email link */
    public static function generateAuthCode($order_id)
    {
        $md5 = md5(uniqid($order_id, true).mt_rand().mt_rand().mt_rand());
        return substr($md5, 0, 16).$order_id.substr($md5, 16);
    }

    /** Random 4-digit code to authorize user from email link */
    public static function generateAuthPin()
    {
        return (string) mt_rand(1000, 9999);
    }
}
