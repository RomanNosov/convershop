<?php

$model = new waModel();

try {
    $model->query("SELECT * FROM contacts_notification_events WHERE 0");
    $model->exec("ALTER TABLE contacts_notification_events CHANGE COLUMN `datetime` `datetime` DATETIME NULL DEFAULT NULL");
    $model->exec("ALTER TABLE contacts_notification_events ADD UNIQUE KEY `event_id` (`event_id`, `contact_id`)");
} catch (waDbException $e) {
    
}