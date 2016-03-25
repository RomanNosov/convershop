<?php

$model = new waModel();

try {
    $model->query("SELECT * FROM `contacts_view` WHERE 0");
    $model->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `type` `type` varchar(32) NULL DEFAULT NULL");
    $model->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `name` `name` varchar(255) NULL DEFAULT NULL");
    $model->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `hash` `hash` text NULL");
    $model->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `parent_id` `parent_id`  int(11) NOT NULL DEFAULT 0");
    $model->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `sort` `sort` int(11) NOT NULL DEFAULT 0");
    $model->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `create_datetime` `create_datetime` DATETIME NOT NULL");
    $model->exec("ALTER TABLE `contacts_view` CHANGE COLUMN `shared` `shared` tinyint(11) NOT NULL DEFAULT 0");    
} catch (waException $e) {
    $model->exec("CREATE TABLE IF NOT EXISTS `contacts_view` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `type` varchar(32) NULL DEFAULT NULL,
      `name` varchar(255) NULL DEFAULT NULL,
      `hash` text NULL,
      `parent_id` int(11) NOT NULL DEFAULT 0,
      `sort` int(11) NOT NULL DEFAULT 0,
      `create_datetime` DATETIME NOT NULL,
      `contact_id` int(11) NOT NULL,
      `shared` tinyint(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
}