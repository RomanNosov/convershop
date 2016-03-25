<?php

// shop.purchased_product.product=<sku_id> => shop.purchased_product.product=sku_id:<sku_id>

if (wa()->appExists('shop')) {
    $vm = new contactsViewModel();
    $h = 'shop.purchased_product.product';
    foreach ($vm->query("SELECT * FROM `contacts_view` WHERE type = 'search' AND hash LIKE '%{$h}=%'") as $view)
    {
        $hash = $view['hash'];
        $pattern = preg_quote($h.'=');
        $hash = preg_replace('/'.$pattern.'([\d]+)/', "{$h}=sku_id:$1", $hash);
        $vm->updateById($view['id'], array(
            'hash' => $hash
        ));
    }
}
