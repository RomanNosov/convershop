<?php


// ////////////////////////
// Duplicated in install.php
// ////////////////////////

$cm = new waContactCategoryModel();
$csm = new waContactCategoriesModel();
$vm = new contactsViewModel();
$vmc = new contactsViewContactsModel();

$sort = $vm->select('MAX(sort)')->where('shared = 1')->fetchField();
if ($sort === false) {
    $sort = 0;
} else {
    $sort = (int) $sort + 1;
}

// move user-created catetories to lists
$category_view_map = array();
foreach (
    $cm->select('*')
        ->where("(system_id IS NULL OR system_id = '') AND (app_id IS NULL OR app_id = '')")
        ->query()
    as $category)
{
    $view_id = $vm->insert(array(
        'type' => 'list',
        'name' => $category['name'],
        'sort' => $sort++,
        'create_datetime' => date('Y-m-d H:i:s'),
        'contact_id' => 0,
        'shared' => 1,
        'count' => $category['cnt'],
        'icon' => $category['icon']
    ));
    $category_view_map[$category['id']] = $view_id;
}

foreach ($category_view_map as $category_id => $view_id)
{
    $data = array();
    foreach ($csm->select('contact_id')->where("category_id = {$category_id}")->query() as $item) {
        $data[] = array( 'contact_id' => $item['contact_id'], 'view_id' => $view_id );
    }
    if ($data) {
        $vmc->multipleInsert($data);
        $csm->deleteByField('category_id', $category_id);
    }
    unset($data);
}

$cm->deleteById(array_keys($category_view_map));
unset($category_view_map);

// link system AND app categories with lists
foreach (
    $cm->select('*')
        ->where("(system_id IS NOT NULL AND system_id != '') OR (app_id IS NOT NULL AND app_id != '')")
        ->query()
    as $category)
{
    $view_id = $vm->insert(array(
        'type' => 'list',
        'name' => null,
        'hash' => "/category/{$category['id']}/",
        'sort' => $sort++,
        'create_datetime' => date('Y-m-d H:i:s'),
        'contact_id' => 0,
        'shared' => 1,
        'count' => $category['cnt'],
        'icon' => $category['icon']
    ));
}