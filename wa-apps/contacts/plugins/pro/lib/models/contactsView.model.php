<?php

class contactsViewModel extends waModel
{
    protected $table = 'contacts_view';


    public function add($type = 'category', $hash = '', $name = '', $contacts = null)
    {
        $default = array(
            'contact_id' => waSystem::getInstance()->getUser()->getId(),
            'create_datetime' => date('Y-m-d H:i:s')
        );
        $args = func_get_args();
        if (count($args) === 1 && is_array($args[0])) {
            $data = $args[0];
            $type = isset($data['type']) ? $data['type'] : 'category';
        } else {
            $data = array(
                'type' => $type,
                'hash' => $hash,
                'name' => $name,
            );
        }
        $data = array_merge($default, $data);

        $sort = $this->select('MAX(sort)')->where('shared = 0')->fetchField() + 1;
        $data['sort'] = $sort;

        if ($type === 'category') {
            $cm = new waContactCategoryModel();
            $category_id = $cm->insert(array(
                'name' => ifset($data['name'], ''),
                'system_id' => null,
                'app_id' => null,
                'icon' => ifset($data['icon']),
                'cnt' => count($contacts)
            ));
            if ($contacts) {
                $ccm = new waContactCategoriesModel();
                $ccm->add($contacts, $category_id);
            }
            unset($data['name']);
            unset($data['hash']);
            $data['category_id'] = $category_id;
            $id = $this->insert($data);
        } else {
            $id = $this->insert($data);
        }
        return $id;
    }

    public function update($id, $data = array())
    {
        $view = $this->getById($id);
        if ($view) {
            $contact_id = wa()->getUser()->getId();
            if ($view['contact_id'] == $contact_id || $view['shared'] == '1') {
                if (isset($data['shared']) && $data['shared'] == '0') {
                    $data['contact_id'] = $contact_id;
                }
                if ($view['type'] === 'category') {

                    $category_data = array();
                    foreach (array(
                        'name' => 'name', 'icon' => 'icon', 'count' => 'cnt'
                    ) as $f1 => $f2) {
                        if (!empty($data[$f1])) {
                            $category_data[$f2] = $data[$f1];
                            unset($data[$f1]);
                        }
                    }
                    $cm = new waContactCategoryModel();
                    if ($category_data && $cm->getById($view['category_id'])) {
                        $cm->updateById($view['category_id'], $category_data);
                    }
                }
                if ($data) {
                    $this->updateById($id, $data);
                }
                if (isset($data['shared']) && $view['shared'] != $data['shared']) {
                    $this->move($id);
                }
            }
        }
    }

    public function addMetrics($id, $metrics = array())
    {
        $view = $this->getById($id);
        if ($view) {
            $hash = trim($view['hash'], '/');
            if (!$hash) {
                $hash = 'list/' . $id;
            }
            $offset = $view['type'] === 'search' ? 3 : 2;
            $hash_ar = explode('/', $hash);
            if (!empty($hash_ar[$offset - 1])) {
                $hash_ar[$offset] = implode('&', $metrics);
                if (!$hash_ar[$offset]) {
                    unset($hash_ar[$offset]);
                }
                $hash = trim(implode('/', $hash_ar), '/');
                if ($hash) {
                    $hash = "/{$hash}/";
                } else {
                    $hash = null;
                }
                $this->update($id, array('hash' => $hash));
            }
        }
    }

    public function get($id) {
        $view = $this->getById($id);
        if (!$view) {
            return array();
        }
        $offset = $view['type'] === 'search' ? 3 : 2;
        $hash = trim($view['hash'], '/');
        $hash_ar = explode('/', $hash);
        $fields = array();
        if (!empty($hash_ar[$offset])) {
            $fields = explode('&', $hash_ar[$offset]);
        }
        $view['fields'] = $fields;
        if ($view['type'] === 'category') {
            $cm = new waContactCategoryModel();
            $category = $cm->getById($view['category_id']);
            $category['count'] = $category['cnt'];
            unset($category['cnt'], $category['id']);
            $view = array_merge($view, $category);
            $view['app_name'] = '';
            if (!$category['system_id'] && $category['app_id']) {
                if (wa()->appExists($category['app_id'])) {
                    $app = wa()->getAppInfo($category['app_id']);
                    $view['app_name'] = $app['name'];
                }
            } else if ($category['system_id']) {
                if (wa()->appExists($category['system_id'])) {
                    $app = wa()->getAppInfo($category['system_id']);
                    $view['app_name'] = $app['name'];
                }
            }
        }
        return $view;
    }

    public function updateName($id, $name) {
        $this->updateById($id, array('name' => $name));
    }

    public function updateCount($view_id = null, $count = null)
    {
        if ($count !== null) {
            $this->updateById($view_id, array(
                'count' => $count
            ));
        } else {
            $where = "1";
            if ($view_id) {
                $view_id = array_map('intval', (array) $view_id);
                $where = "v.id IN(" . implode(',', $view_id) . ")";
            }

             if ($view_id) {
                 $view_id = array_map('intval', (array) $view_id);
                 $categories = $this->select('category_id')
                            ->where("id IN(:view_id) AND type = 'category'", array(
                                'view_id' => $view_id
                            ))->fetchAll(null, true);
                 if ($categories) {
                     $where = "c.id IN(" . implode(',', $categories) . ")";
                    $sql = "
                UPDATE `wa_contact_category` t JOIN (
                    SELECT c.id, COUNT(*) count
                    FROM `wa_contact_category` c
                    JOIN `wa_contact_categories` cc ON c.id = cc.category_id
                    WHERE {$where}
                    GROUP BY c.id
                ) r ON t.id = r.id
                SET t.cnt = r.count
                WHERE t.id = r.id
                ";
                     $this->exec($sql);
                 }
             }

        }
    }

    public function move($id, $before_id = null)
    {
        $item = $this->getById($id);
        if (!$item) {
            return false;
        }
        if (!$before_id) {
            $sort = $this->select('MAX(sort)')->
                where('shared = :0',
                    array(
                        $item['shared']
                    )
                )->fetchField() + 1;
        } else {
            $before = $this->getById($before_id);
            if (!$before) {
                return false;
            }
            $sort = $before['sort'];
            if ($item['shared']) {
                if (!$this->exec(
                    "UPDATE `{$this->table}` SET sort = sort + 1 WHERE sort >= :0 AND shared = 1",
                    array(
                        $sort
                    )))
                {
                    return false;
                }
            } else {
                $contact_id = array(
                    wa()->getUser()->getId()
                );
                if (wa()->getUser()->isAdmin()) {
                    $contact_id[] = 0;
                }
                if (!$this->exec(
                    "UPDATE `{$this->table}` SET sort = sort + 1 WHERE sort >= :0 AND shared = 0 AND contact_id IN (:1)",
                    array(
                        $sort,
                        $contact_id
                    )))
                {
                    return false;
                }
            }
        }
        if (!$this->exec(
            "UPDATE `{$this->table}` SET sort = :0 WHERE id = :1",
            array(
                $sort,
                $item['id']
            )
        ))
        {
            return false;
        }

        return true;
    }

    public function canEdit($id) {
        $allowed = $this->filterByAllowedForEdit((array) $id);
        if ($allowed) {
            return true;
        }
        return false;
    }

    public function filterByAllowedForEdit(array $id)
    {
        $views = $this->getByField('id', $id, 'id');
        $user = wa()->getUser();
        $is_admin = $user->isAdmin();
        $contact_id = $user->getId();
        $edit_right = $user->getRights('contacts', 'edit');
        $filtered = array();
        foreach ($views as $view) {
            if ($is_admin || $edit_right || $view['shared'] || $contact_id == $view['contact_id']) {
                $filtered[] = $view['id'];
            }
        }
        return $filtered;
    }

    public function insert($data, $type = 0) {
        $data = array_merge(array(
            'sort' => 0,
            'shared' => 0
        ), $data);
        $id = parent::insert($data, $type);
        return $id;
    }

    public function getViews($view_id)
    {
        $fields = array();
        foreach (array_keys($this->getMetadata()) as $field_id) {
            if (in_array($field_id, array('name', 'count', 'icon'))) {
                $fields[] = "IF(cv.type = 'category', cc." . ($field_id === 'count' ? 'cnt' : $field_id) . ", cv.{$field_id}) AS {$field_id}";
            } else {
                $fields[] = 'cv.' . $field_id;
            }
        }
        $fields[] = 'cc.system_id';
        $fields[] = 'cc.app_id';
        $sql = "SELECT " . implode(',', $fields) . " FROM contacts_view cv
                LEFT JOIN wa_contact_category cc ON cv.category_id = cc.id
                WHERE cv.id IN (:view_id) ORDER BY shared DESC, sort";
        return $this->query($sql, array(
            'view_id' => $view_id
        ))->fetchAll('id');
    }

    public function getAllViews($type = null, $sync_with_categories = false)
    {
        if ($sync_with_categories) {
            $this->syncWithCategories();
        }
        $contact_id = array(
            wa()->getUser()->getId()
        );
        if (wa()->getUser()->isAdmin()) {
            $contact_id[] = 0;
        }

        $fields = '*';
        if ($type === null || in_array('category', (array) $type)) {
            $fields = array();
            foreach (array_keys($this->getMetadata()) as $field_id) {
                if (in_array($field_id, array('name', 'count', 'icon'))) {
                    $fields[] = "IF(cv.type = 'category', cc." . ($field_id === 'count' ? 'cnt' : $field_id) . ", cv.{$field_id}) AS {$field_id}";
                } else {
                    $fields[] = 'cv.' . $field_id;
                }
            }
            $fields[] = 'cc.system_id';
            $fields[] = 'cc.app_id';

            $fields = implode(',', $fields);
        }

        if ($type === null) {

            $sql = "SELECT " . $fields . " FROM contacts_view cv LEFT JOIN wa_contact_category cc ON cv.category_id = cc.id
                WHERE shared > 0 OR contact_id IN (:contact_id) ORDER BY shared DESC, sort";
            $views = $this->query($sql, array(
                'contact_id' => $contact_id
            ))->fetchAll('id');

        } else {
            if (in_array('category', (array) $type)) {
                $sql = "SELECT " . $fields . " FROM contacts_view cv LEFT JOIN wa_contact_category cc ON cv.category_id = cc.id
                    WHERE type IN(:type) AND (contact_id IN (:contact_id) OR shared > 0) ORDER BY shared DESC, sort";
                $views = $this->query($sql, array(
                    'type' => $type,
                    'contact_id' => $contact_id
                ))->fetchAll('id');

            } else {
                $views = $this->select('*')->where(
                    "type IN(:type) AND (contact_id IN (:contact_id) OR shared > 0)", array(
                        'type' => $type,
                        'contact_id' => $contact_id
                    )
                )->order('shared DESC,sort')->fetchAll('id');
            }
        }

        $this->workupViews($views);
        return $views;
    }

    private function workupViews(&$views)
    {
        foreach ($views as &$view) {
            if ($view['type'] === 'category') {
                if (!empty($view['system_id']) && wa()->appExists($view['system_id'])) {
                    $app = wa()->getAppInfo($view['system_id']);
                    $view['name'] = $app['name'];
                    $view['icon'] = wa()->getRootUrl(true).$app['icon'][16];
                }
            }
        }
    }

    public function deleteByContacts($contact_ids)
    {
        $view_ids = array_keys($this->getByField('contact_id', $contact_ids, 'id'));
    }

    public function delete($id, $type = null)
    {
        $item = $this->getById($id);
        if ($type !== null && $item['type'] != $type) {
            return false;
        }
        if (!$item) {
            return false;
        }
        $user = wa()->getUser();
        if ($item['contact_id'] == $user->getId() || $user->isAdmin()) {
            $this->deleteById($id);
        }
    }

    public static function setIcons(&$views)
    {
        foreach ($views as &$view) {
            $icon = '';
            if ($view['icon']) {
                $icon = $view['icon'];
            } else if ($view['type'] === 'search') {
                $icon = 'search';
            } else if ($view['type'] === 'category') {
                $icon = 'contact';
            } else {
                $icon = 'folder';
            }
            if ($icon) {
                $icon = trim($icon);
                if (strpos($icon, 'http://') === 0 || strpos($icon, 'https://') === 0) {
                    $icon = "<img class='c-app16x16icon-menu-v' src='{$icon}'>";
                } else {
                    $icon = "<i class='icon16 {$icon}'></i>";
                }
            }

            $view['icon'] = $icon;
        }
    }

    public function syncWithCategories()
    {

        $sql = "DELETE cv
                FROM `contacts_view` cv
                LEFT JOIN `wa_contact_category` cc ON cv.category_id = cc.id
                WHERE cv.category_id IS NOT NULL AND cc.id IS NULL";
        $this->query($sql);

        $sql = "SELECT cc.id
            FROM `wa_contact_category` cc
            LEFT JOIN `contacts_view` cv ON cv.category_id = cc.id
            WHERE cv.category_id IS NULL AND cc.system_id IS NULL AND cc.app_id IS NOT NULL";

        $sort = 0;
        $category_ids = $this->query($sql)->fetchAll(null, true);
        if ($category_ids) {
            $sort = $this->select('MAX(sort)')->where('shared = 1')->fetchField();
            if ($sort === false) {
                $sort = 0;
            } else {
                $sort = (int) $sort + 1;
            }
        }

        $insert_data = array();
        foreach ($category_ids as $category_id) {
            $insert_data[] = array(
                'type' => 'category',
                'sort' => $sort,
                'create_datetime' => date('Y-m-d H:i:s'),
                'contact_id' => 0,
                'shared' => 1,
                'category_id' => $category_id
            );
        }
        $this->multipleInsert($insert_data);
    }

    public function addTo($view_id, $contact_id)
    {
        $view_id = array_map('intval', (array) $view_id);
        $contact_id = array_map('intval', (array) $contact_id);
        $view_id = $this->filterByAllowedForEdit($view_id);

        $categories = $this->select('category_id')->where("id IN(:view_id) AND type = 'category'", array(
            'view_id' => $view_id
        ))->fetchAll(null, true);
        if ($categories) {
            $cm = new waContactCategoryModel();
            $categories = $cm->select('id')->
                where("id IN(:id) AND system_id IS NULL", array(
                    'id' => $categories
                ))->fetchAll(null, true);
            if ($categories) {
                $ccm = new waContactCategoriesModel();
                $ccm->add($contact_id, $categories);
            }
        }
    }

    public function deleteFrom($view_id, $contact_id)
    {
        $view_id = array_map('intval', (array) $view_id);
        $contact_id = array_map('intval', (array) $contact_id);
        $view_id = $this->filterByAllowedForEdit($view_id);

        $categories = $this->select('category_id')->where("id IN(:view_id) AND type = 'category'", array(
            'view_id' => $view_id
        ))->fetchAll(null, true);
        if ($categories) {
            $cm = new waContactCategoryModel();
            $categories = $cm->select('id')->
                where("id IN(:id) AND system_id IS NULL", array(
                    'id' => $categories
                ))->fetchAll(null, true);
            if ($categories) {
                $ccm = new waContactCategoriesModel();
                $ccm->remove($contact_id, $categories);
            }
        }
    }

}