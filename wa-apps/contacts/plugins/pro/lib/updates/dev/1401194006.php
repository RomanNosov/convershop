<?php

$model = new waModel();

try {
    $model->query("SELECT icon FROM contacts_view WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE contacts_view ADD COLUMN icon VARCHAR(255) NULL DEFAULT NULL");
}