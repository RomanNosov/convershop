<?php

$m = new contactsNotificationEventsModel();
$info = $m->describe();
if (empty($info['id']['autoincrement'])) {
    $m->deleteById(0);
    $sql = "ALTER TABLE contacts_notification_events CHANGE id id INT(11) NOT NULL AUTO_INCREMENT";
    $m->exec($sql);
}