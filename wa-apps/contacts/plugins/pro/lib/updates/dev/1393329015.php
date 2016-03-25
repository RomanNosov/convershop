<?php

// make social network after im in all fields stream (e.g. fields constructor)

$order = array();
foreach (contactsProHelper::getAllFieldsOrder() as $field_id) {
    if ($field_id == 'socialnetwork') {
        continue;
    }
    if ($field_id == 'im') {
        $order[] = 'im';
        $field = waContactFields::get('socialnetwork', 'person');
        if ($field) {
            $order[] = 'socialnetwork';
        }
    } else {
        $order[] = $field_id;
    }
}

if ($order) {
    contactsProHelper::saveAllFieldsOrder($order);
}
