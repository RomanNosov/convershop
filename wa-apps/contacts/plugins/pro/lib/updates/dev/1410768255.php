<?php

$m = new waModel();

$sql = "ALTER TABLE `contacts_notification_logs` CHANGE COLUMN `log_action` `log_action` VARCHAR(255) NULL DEFAULT NULL";
$m->query($sql);