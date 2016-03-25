<?php

$vm = new contactsViewModel();

try {
    $vm->query("SELECT category_id FROM contacts_view WHERE 0");
} catch (waException $e) {
    $vm->exec("ALTER TABLE contacts_view ADD COLUMN category_id INT(11) NULL DEFAULT NULL");
    $vm->exec("ALTER TABLE contacts_view ADD INDEX `category_id` (`category_id`)");
}

$vm->query("SELECT category_id FROM contacts_view WHERE 0");
foreach ($vm->select('*')->where("hash LIKE '%category/%'")->fetchAll() as $view) {
    $hash = trim($view['hash'], '/');
    $category_id = substr($hash, 9);
    $vm->exec("UPDATE contacts_view SET category_id = '{$category_id}', hash = NULL, type = 'category' WHERE id = '{$view['id']}'");
}