<?php


// enable main fields for person AND sort it in specified order
$sort = 0;
foreach (array('name', 'title', 'firstname', 'middlename', 'lastname', 'jobtitle', 'company') as $f_id) {
    $field = waContactFields::get($f_id, 'all');
    if ($field) {
        waContactFields::updateField($field);
        waContactFields::enableField($field, 'person', $sort);
        $sort += 1;
    }
}