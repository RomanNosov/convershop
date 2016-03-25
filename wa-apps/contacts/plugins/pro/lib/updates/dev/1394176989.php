<?php

$model = new waModel();

try {
    $model->query("SELECT * FROM `contacts_view_count` WHERE 0");
    $model->exec("ALTER TABLE `contacts_view_count` CHANGE COLUMN `count` `count` int(11) NOT NULL DEFAULT 0");
} catch (waException $e) {
    $model->exec("CREATE TABLE IF NOT EXISTS `contacts_view_count` (
      `view_id` int(11) NOT NULL,
      `contact_id` int(11) NOT NULL,
      `count` int(11) NOT NULL DEFAULT 0,
      PRIMARY KEY (`view_id`, `contact_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
}