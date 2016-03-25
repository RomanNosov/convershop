<?php

return array(
    'name' => 'Shop',                                // _wp('Shop')
    'joins' => array(
        ':tbl_customer' => array(
            'table' => 'shop_customer'
        ),
        ':tbl_order' => array(
            'table' => 'shop_order'
        )
    ),
    'items' => array(
        'placed_orders' => array(
            'name' => 'Placed orders',        // _wp('Placed orders')
            'multi' => true,
            'items' => array(
                'period' => array(
                    'name' => 'Period',           // _wp('Period')
                    'items' => array(
                        ':period' => array(
                            'name' => 'select a period',
                            'where' => array(
                                ':between' => "DATE(:tbl_order.create_datetime) >= ':0' AND DATE(:tbl_order.create_datetime) <= ':1'",
                                ':gt' => "DATE(:tbl_order.create_datetime) >= ':?'",
                                ':lt' => "DATE(:tbl_order.create_datetime) <= ':?'",
                            )
                        )
                    )
                ),
                'status' => array(
                    'name'  => 'Current state',      // _wp('Current state')
                    'readonly' => true,
                    'items' => array(
                        ':values' => array(
                            'class' => 'contactsSearchShopOrderStatesValues'
                        )
                    )
                ),
                'payment_method' => array(
                    'name' => 'Payment method',       // _wp('Payment method')
                    'readonly' => true,
                    'items' => array(
                        ':values' => array(
                            'join' => array(
                                'table' => 'shop_order_params',
                                'on' => ':table.order_id = :tbl_order.id'
                            ),
                            'class' => 'contactsSearchShopSPMethodsValues',
                            'options' => array(
                                'type' => 'payment'
                            )
                        )
                    )
                ),
                'shipment_method' => array(
                    'name' => 'Shipment method',        // _wp('Shipment method')
                    'readonly' => true,
                    'items' => array(
                        ':values' => array(
                            'join' => array(
                                'table' => 'shop_order_params',
                                'on' => ':table.order_id = :tbl_order.id'
                            ),
                            'class' => 'contactsSearchShopSPMethodsValues',
                            'options' => array(
                                'type' => 'shipping'
                            )
                        )
                    )
                )
            )
        ),
        'purchased_product' => array(
            'name' => 'Purchased product',        // _wp('Purchased product')
            'multi' => true,
            'items' => array(
                'period' => array(
                    'name' => 'Period',           // _wp('Period')
                    'items' => array(
                        ':period' => array(
                            'name' => 'select a period',
                            'where' => array(
                                ':between' => "DATE(:tbl_order.create_datetime) >= ':0' AND DATE(:tbl_order.create_datetime) <= ':1'",
                                ':gt' => "DATE(:tbl_order.create_datetime) >= ':?'",
                                ':lt' => "DATE(:tbl_order.create_datetime) <= ':?'",
                            )
                        )
                    )
                ),
                'product' => array(
                    'name' => 'Product',          // _wp('Product'),
                    'items' => array(
                        ':values' => array(
                            'autocomplete' => 1,
                            'class' => 'contactsSearchShopProductValues'
                        )
                    )
                ),
                'status' => array(
                    'name'  => 'Current state',      // _wp('Current state')
                    'readonly' => true,
                    'items' => array(
                        ':values' => array(
                            'class' => 'contactsSearchShopOrderStatesValues'
                        )
                    )
                )
            )
        ),
        'customers' => array(
            'name' => 'Customers',
            'multi' => true,
            'items' => array(
                'total_spent' => array(
                    'name' => 'Total spent',                // _wp('Total spent')
                    ':class' => 'contactsSearchShopTotalSpentItem',
                ),
                'payed_orders' => array(
                    'name' => 'Count only paid orders',     // _wp('Count only paid orders')
                    'checkbox' => true,
                    'where' => array(
                        '=' => array(
                            '1' => ':tbl_order.paid_date IS NOT NULL'
                        )
                    )
                ),
                'number_of_orders' => array(
                    'name' => 'Number of orders',       // _wp('Number of orders')
                    ':class' => 'contactsSearchShopNumberOfOrdersItem'
                ),
                'last_order_datetime' => array(
                    'name' => 'Last order',                 // _wp('Last order')
                    ':class' => 'contactsSearchShopOrderDatetimeItem',
                    'options' => array('type' => 'last')
                ),
                'first_order_datetime' => array(
                    'name' => 'First order',                // _wp('First order')
                    ':class' => 'contactsSearchShopOrderDatetimeItem',
                    'options' => array('type' => 'first')
                ),
                'coupon' => array(
                    'name' => 'Discount',                   // _wp('Discount')
                    ':class' => 'contactsSearchShopCouponItem'
                ),
                'referer' => array(
                    'name' => 'Referer',                    // _wp('Referer')
                    ':class' => 'contactsSearchShopRefererItem'
                ),
                'storefront' => array(
                    'name' => 'Storefront',             // _wp('Storefront')
                    ':class' => 'contactsSearchShopStorefrontItem'
                ),
                'utm_campaign' => array(
                    'name' => 'UTM campaign',
                    ':class' => 'contactsSearchShopUtmCampaignItem'
                )
            )
        ),
    )
);