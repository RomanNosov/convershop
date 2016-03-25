<?php

$model = new waModel();

try {
    $model->query("SELECT * FROM `contacts_log_notifications` WHERE 0");
    $model->exec("ALTER TABLE `contacts_log_notifications` RENAME `contacts_notification_logs`");
} catch (waDbException $e) {
    
}
