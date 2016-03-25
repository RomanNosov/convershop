<?php

// fix custom_fields locale

$fields = array(
    array(
        'id' => 'timezone',
        'name' => array('en_US' => 'Time zone')
    ),
    array(
        'id' => 'locale',
        'name' => array('en_US' => 'Language')
    )
);

foreach($fields as $field){
    $fld = waContactFields::get($field['id']);
    if ($fld) {
        $locales = $fld->getParameter('localized_names');
        $locales = array_merge($locales, $field['name']);
        $fld->setParameter('localized_names', $locales);
        waContactFields::updateField($fld);
    }
}