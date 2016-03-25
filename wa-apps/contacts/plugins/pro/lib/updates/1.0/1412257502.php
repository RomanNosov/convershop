<?php

$vm = new contactsViewModel();
$cm = new waContactCategoryModel();
$ccm= new waContactCategoriesModel();

try {
    
    $vm->query('SELECT * FROM contacts_view_contacts WHERE 0');
    
    foreach ($vm->select('*')->where("type = 'list'")->fetchAll() as $view) {
        $category_id = $cm->insert(array(
            'name' => $view['name'],
            'system_id' => null,
            'app_id' => null,
            'icon' => $view['icon'],
            'cnt' => $view['count']
        ));
        $ccm->query("INSERT IGNORE INTO `wa_contact_categories` (category_id, contact_id) 
            SELECT {$category_id}, contact_id FROM `contacts_view_contacts` WHERE view_id = {$view['id']}");
        $vm->updateById($view['id'], array(
            'name' => null,
            'hash' => null,
            'type' => 'category',
            'category_id' => $category_id
        ));
    }

    $vm->query('ALTER TABLE `contacts_view` CHANGE COLUMN `type` `type` VARCHAR(32) NOT NULL');
    $vm->exec('DROP TABLE`contacts_view_contacts`');
} catch (waDbException $e) {
    
}

$app_id = 'contacts';

$_files = array();

// rm actions
$_files[wa($app_id)->getAppPath().'/plugins/pro/lib/actions/'] = array(
    'list'
);

// rm templates
$_files[wa($app_id)->getAppPath().'/plugins/pro/templates/actions/'] = array(
    'list'
);

// rm models
$_files[wa($app_id)->getAppPath().'/plugins/pro/lib/models/'] = array(
    'contactsViewContacts.model.php'
);

foreach ($_files as $path => $fls) {
    foreach ($fls as $f) {
        $_file = $path . $f;
        if (file_exists($_file)) {
            waFiles::delete($_file, true);
        }
    }
}