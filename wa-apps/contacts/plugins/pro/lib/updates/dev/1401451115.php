<?php

$model = new waModel();
try {
    $model->query("SELECT parent_id FROM contacts_view WHERE 0");
    $model->exec("ALTER TABLE contacts_view DROP parent_id");
} catch (waException $e) {
    
}