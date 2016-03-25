<?php

$model = new waModel();

try {
    $model->query("SELECT * FROM `contacts_notes` WHERE 0");
} catch (waException $e) {
    $model->exec("CREATE TABLE IF NOT EXISTS `contacts_notes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `contact_id` int(11) NOT NULL,
      `create_contact_id` int(11) NOT NULL,
      `create_datetime` DATETIME NOT NULL,
      `text` text NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
}