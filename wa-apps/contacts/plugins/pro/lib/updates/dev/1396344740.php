<?php

$m = new waModel();

try {
    $m->query("SELECT count FROM `contacts_view`");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `contacts_view` ADD COLUMN `count` INT(11) NOT NULL DEFAULT 0");
}

try {
    $m->select("SELECT * FROM `contacts_view_count` WHERE 0");
    $m->exec("DROP TABLE `contacts_view_count`");
} catch (waDbException $e) {
    
}

try {
    $m->select("SELECT * FROM `contacts_list` WHERE 0");
    $m->exec("DROP TABLE `contacts_list`");
} catch (waDbException $e) {
    
}

try {
    $m->select("SELECT * FROM `contacts_contact_lists` WHERE 0");
    $m->exec("DROP TABLE `contacts_contact_lists`");
} catch (waDbException $e) {
    
}