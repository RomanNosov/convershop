<?php

// update search config

waFiles::delete(wa('contacts')->getConfig()->getConfigPath('search/search.php'));

$fields = contactsProHelper::getAllFields();
foreach ($fields as $field_id => $field) {
    
    $pf = waContactFields::get($field_id, 'person');
    $cf = waContactFields::get($field_id, 'company');
    
    if (!$pf && !$cf && contactsProHelper::isEnabledSearchingByField($field)) {
        contactsProHelper::disableSearchingByField($field);
    } else  if (($pf || $cf) && !contactsProHelper::isEnabledSearchingByField($field)) {
        contactsProHelper::enableSearchingByField($field);
    }
}

contactsProHelper::sortFieldsInSearchConfig($fields);