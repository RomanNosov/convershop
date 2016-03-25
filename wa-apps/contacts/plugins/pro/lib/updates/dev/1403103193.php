<?php

$model = new waModel();
try {
    $model->query("SELECT create_contact_id FROM contacts_event WHERE 0");
    $model->exec("ALTER TABLE contacts_event CHANGE COLUMN create_contact_id contact_id INT(11) NOT NULL DEFAULT 0");
} catch (waDbException $e) {
}

try {
    $model->query("SELECT create_contact_id FROM contacts_form WHERE 0");
    $model->exec("ALTER TABLE contacts_form CHANGE COLUMN create_contact_id contact_id INT(11) NOT NULL DEFAULT 0");
} catch (waDbException $e) {
}