<?php

$m = new contactsViewModel();

foreach ($m->getAll() as $view) {
    $hash = trim($view['hash'], '/');
    $hash_ar = explode('/', $hash);
    if (isset($hash_ar[0]) && $hash_ar[0] === 'category') {
        $m->updateById($view['id'], array('name' => null));
    }
}
