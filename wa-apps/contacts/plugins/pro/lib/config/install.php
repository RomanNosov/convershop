<?php

$cm = new waContactCategoryModel();
$vm = new contactsViewModel();

$sort = $vm->select('MAX(sort)')->where('shared = 1')->fetchField();
if ($sort === false) {
    $sort = 0;
} else {
    $sort = (int) $sort + 1;
}

foreach ($cm->getAll() as $category) {
    // check existing first
    if (!$vm->select('id')->where("hash = '/category/{$category['id']}/' OR category_id = {$category['id']}")->fetchField()) {
        $vm->insert(array(
            'type' => 'category',
            'name' => null,
            'hash' => null,
            'sort' => $sort++,
            'create_datetime' => date('Y-m-d H:i:s'),
            'contact_id' => 0,
            'shared' => 1,
            'count' => null,
            'icon' => null,
            'category_id' => $category['id']
        ));
    }
}


// DISABLE FIELDS
$disabled_fields = array(
    'person' => array('categories'),
    'company' => array('categories')
);
foreach ($disabled_fields as $type => $fields) {
    foreach ($fields as $f_id) {
        $field = waContactFields::get($f_id, 'all');
        if ($field) {
            waContactFields::enableField($field, $type);        // this method create <person|company>_fields_order.php file if it doesn't exist
            waContactFields::disableField($field, $type);
        }
    }
}

// add private routing
$path = wa()->getConfig()->getPath('config', 'routing');
if (file_exists($path) && is_writable($path)) {
    $routing = include($path);

    $contacts_route = array(
        'url' => 'contacts/*',
        'app' => 'contacts',
        'private' => '1'
    );

    foreach ($routing as $domain => $routes) {
        if (is_array($routes)) {
            $route_id = 0;
            $exist = false;
            foreach ($routes as $r_id => $r) {
                if (is_numeric($r_id) && $r_id > $route_id) {
                    $route_id = $r_id;
                }
                if (isset($r['app']) && $r['app'] === 'contacts') {
                    $exist = true;
                }
            }
            $route_id++;

            if (!$exist) {
                $routing[$domain] = array($route_id => $contacts_route) + $routing[$domain];
            }
        }
    }

    waUtils::varExportToFile($routing, $path);
}

// insert custom fields to search bar (by update search.php config)

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


// clean search history
$history = new contactsHistoryModel();
$history->deleteByField('type', 'search');