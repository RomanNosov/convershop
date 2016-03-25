<?php

// clean phones, because import didn't filter
$m = new waContactDataModel();
foreach ($m->query("
    SELECT d.id, d.value FROM wa_contact_data d 
    JOIN wa_contact c ON d.contact_id = c.id
    WHERE c.create_method = 'import' AND d.field = 'phone'
    ") as $item) 
{
    $m->updateById($item['id'], array(
        'value' => preg_replace('/[^\d]+/', '', $item['value'])
    ));
}
