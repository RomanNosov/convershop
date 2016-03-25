<?php

$model = new waModel();

$sql = "
CREATE TABLE IF NOT EXISTS contacts_notification_logs (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `contact_id` int(11) NOT NULL DEFAULT 0,
    `log_app_id` varchar(32) NULL DEFAULT NULL,
    `log_action` varchar(255) NOT NULL,
    `log_contact_id` int(11) NULL DEFAULT NULL,
    `log_subject_contact_id` int(11) NULL DEFAULT NULL,
    `datetime` datetime NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$model->exec($sql);

$sql = "
CREATE TABLE IF NOT EXISTS contacts_notification_birthdays (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `contact_id` int(11) NOT NULL DEFAULT 0,
    `birthday_contact_id` int(11) NULL DEFAULT NULL,
    `prior` tinyint(2) NOT NULL DEFAULT 0,
    `datetime` datetime NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$model->exec($sql);

$sql = "
CREATE TABLE IF NOT EXISTS contacts_notification_events (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `contact_id` int(11) NOT NULL DEFAULT 0,
    `event_id` int(11) NOT NULL DEFAULT 0,
    `prior_days` tinyint(2) NOT NULL DEFAULT 0,
    `prior_minutes` int(11) NULL DEFAULT NULL,
    `datetime` datetime NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `event_id` (`event_id`,`contact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$model->exec($sql);