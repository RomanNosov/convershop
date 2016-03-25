<?php

// DISABLE FIELDS
$disabled_fields = array(
    'person' => array('categories'),
    'company' => array('categories')
);
foreach ($disabled_fields as $type => $fields) {
    foreach ($fields as $f_id) {
        $field = waContactFields::get($f_id, 'all');
        if ($field) {
            waContactFields::disableField($field, $type);
        }
    }
}