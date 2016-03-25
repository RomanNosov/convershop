<?php

// collection fields of type 'Menu' added in field constructor

$menu_fields = array();
$all_fields = waContactFields::getAll('all', true);
foreach ($all_fields as $fld_id => $fld) {
    if (!in_array($fld_id, contactsProHelper::$noneditable_fields) && 
            !in_array($fld_id, contactsProHelper:: $person_main_fields) && 
            !in_array($fld_id, contactsProHelper::$company_main_fields) && 
            !in_array($fld_id, contactsProHelper::$disabled_fields['person']) && 
            !in_array($fld_id, contactsProHelper::$disabled_fields['company']) && 
            $fld instanceof waContactSelectField
    ) 
    {
        $options = $fld->getOptions();
        
        $is_numeric_ar = false;
        foreach ($options as $opt_id => $opt_val) {
            if (is_numeric($opt_id)) {
                $is_numeric_ar = true;
                break;
            }
        }
        if ($is_numeric_ar) {
            $menu_fields[$fld_id] = $fld;
        }
    }
}

$m = new waModel();

// update in DB
foreach ($menu_fields as $fld_id => $fld) {
    $options = $fld->getOptions();
    foreach ($options as $opt_id => $opt_val) {
        $m->exec(
            "UPDATE `wa_contact_data` 
            SET value = :new_value
            WHERE field = :field AND value = :old_value", 
            array(
                'field' => $fld_id,
                'old_value' => $opt_id,
                'new_value' => $opt_val
            )
        );
    }
}

// update field options
foreach ($menu_fields as $fld_id => $fld) 
{
    $options = array();
    foreach ($fld->getOptions() as $opt_val) {
        $options[$opt_val] = $opt_val;
    }
    $fld->setParameter('options', $options);
    waContactFields::updateField($fld);
}