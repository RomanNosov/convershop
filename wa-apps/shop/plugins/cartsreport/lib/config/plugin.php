<?php
return array (
  'name' => _wp('Products in shopping carts'),
  'description' => _wp('Plugin adds new reports to the store.'),
  'img' => 'img/cartsreport.png',
  'version' => '1.0.0',
  'vendor' => '972539',
  'handlers' => 
  array (
      'order_action.create' => 'orderActionCreate',
      'frontend_checkout'   => 'frontendCheckout',
      'backend_reports'     => 'backendReports',
      'frontend_cart'       => 'frontendCart',
      'cart_delete'         => 'cartDelete',
      'cart_add'            => 'cartAdd',
  ),
);
