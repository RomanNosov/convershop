<?php

// UPDATE FOR www.webasyst.com, where all tables from contacts_full exists

$m = new contactsViewModel();

// UPDATE types of old fields
try {
    $m->query("SELECT * FROM `contacts_view` WHERE 0");
    $m->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `type` `type` varchar(32) NULL DEFAULT NULL");
    $m->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `name` `name` varchar(255) NULL DEFAULT NULL");
    $m->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `hash` `hash` text NULL");
    $m->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `parent_id` `parent_id`  int(11) NOT NULL DEFAULT 0");
    $m->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `sort` `sort` int(11) NOT NULL DEFAULT 0");
    $m->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `create_datetime` `create_datetime` DATETIME NOT NULL");
    $m->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `shared` `shared` tinyint(11) NOT NULL DEFAULT 0");    
} catch (waDbException $e) {
    
}

// correcting sort
$sort = 0;
$q = $m->query("SELECT * FROM `contacts_view` ORDER BY contact_id,shared,sort");
foreach ($q->fetchAll() as $item) {
    $m->updateById($item['id'], array(
        'sort' => $sort++
    ));
}

// add new field: count
try {
    $m->query("SELECT count FROM `contacts_view`");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `contacts_view` ADD COLUMN `count` INT(11) NOT NULL DEFAULT 0");
}

// add new field: icon
try {
    $m->query("SELECT icon FROM contacts_view WHERE 0");
} catch (waException $e) {
    $m->exec("ALTER TABLE contacts_view ADD COLUMN icon VARCHAR(255) NULL DEFAULT NULL");
}

// drop old field: parent_id
try {
    $m->query("SELECT parent_id FROM contacts_view WHERE 0");
    $m->exec("ALTER TABLE contacts_view DROP parent_id");
} catch (waException $e) {
    
}

// clean hash 
$m->exec("UPDATE contacts_view SET hash = NULL WHERE hash IS NOT NULL AND SUBSTRING(hash, 1, 6) = '/list/'");

