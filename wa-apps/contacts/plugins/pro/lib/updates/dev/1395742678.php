<?php

$m = new contactsViewModel();

$sort = 0;
$q = $m->query("SELECT * FROM `contacts_view` ORDER BY contact_id,shared,sort");
foreach ($q->fetchAll() as $item) {
    $m->updateById($item['id'], array(
        'sort' => $sort++
    ));
}
