<?php

class contactsProHelper
{
    public static $person_main_fields = array(
        'name', 'title', 'firstname', 'middlename', 'lastname', 'jobtitle', 'company'
    );

    public static $company_main_fields = array('name', 'company');

    public static $disabled_fields = array(
        'person' => array('categories'),
        'company' => array('categories')
    );

    public static $noneditable_fields = array(
        'sex', 'email', 'phone', 'birthday', 'im', 'socialnetwork', 'address', 'url', 'timezone', 'locale'
    );

    public static function ensureCustomFieldsExists()
    {
        $custom_fields_file = wa()->getConfig()->getConfigPath('custom_fields.php', true, 'contacts');
        if (file_exists($custom_fields_file)) {
            return;
        }

        $system_fields_file = wa()->getConfig()->getPath('system', 'contact/data/fields');
        waFiles::copy($system_fields_file, $custom_fields_file);

        // enable main fields for person
        $sort = 0;
        foreach (self::$person_main_fields as $f_id) {
            $field = waContactFields::get($f_id, 'all');
            if ($field) {
                waContactFields::updateField($field);
                waContactFields::enableField($field, 'person', $sort);
                $sort += 1;
            }
        }

        // enable main fields for company
        $sort = 0;
        foreach (self::$company_main_fields as $f_id) {
            $field = waContactFields::get($f_id, 'all');
            if ($field) {
                if ($f_id === 'company') {
                    $field->setParameter('required', true);
                } else if ($f_id === 'name') { // because company is its name
                    $field->setParameter('required', false);
                }
                waContactFields::updateField($field);
                waContactFields::enableField($field, 'company', $sort);
                $sort += 1;
            }
        }

        // disable fields
        foreach (self::$disabled_fields as $type => $fields) {
            foreach ($fields as $f_id) {
                $field = waContactFields::get($f_id, 'all');
                if ($field) {
                    waContactFields::enableField($field, $type);
                    waContactFields::disableField($field, $type);
                }
            }
        }
    }

    public static function ensureAndressLatAndLngExists()
    {
        // enable lan and lng subfields of address field
        $field = waContactFields::get('address', 'all');
        if ($field) {
            $found_lng = false;
            $found_lat = false;
            $fields = (array) $field->getParameter('fields');
            foreach ($fields as $fld) {
                if ($fld->getId() == 'lng') {
                    $found_lng = true;
                    continue;
                }
                if ($fld->getId() == 'lat') {
                    $found_lat = true;
                    continue;
                }
                if ($found_lng && $found_lat) {
                    break;
                }
            }
            if (!$found_lat) {
                $fields[] = new waContactHiddenField('lat', 'Latitude');
            }
            if (!$found_lng) {
                $fields[] = new waContactHiddenField('lng', 'Longitude');
            }
            if (!$found_lat || !$found_lng) {
                $field->setParameter('fields', $fields);
                waContactFields::updateField($field);
            }
        }
    }

    public static function getAllFields()
    {
        $all_fields = waContactFields::getAll('all', true);
        $all_fields_order_file = wa()->getConfig()->getConfigPath('all_fields_order.php', true, 'contacts');
        if (file_exists($all_fields_order_file)) {
            $order = include($all_fields_order_file);
            $res = array();
            $del = false;
            foreach ($order as $f_id) {
                if (isset($all_fields[$f_id])) {
                    $res[$f_id] = $all_fields[$f_id];
                    unset($all_fields[$f_id]);
                } else {
                    $del = true;
                    unset($order[$f_id]);
                }
            }
            foreach ($all_fields as $f_id => $f) {
                $res[$f_id] = $all_fields[$f_id];
            }
            if ($del) {
                waUtils::varExportToFile($order, $all_fields_order_file, true);
            }
            return $res;
        } else {
            $order = array_keys($all_fields);
            waUtils::varExportToFile($order, $all_fields_order_file, true);
            return $all_fields;
        }
    }

    public static function saveFieldsOrder($fields, $type = 'all')
    {
        $item = reset($fields);
        if (!is_string($item)) {
            $fields = array_keys($fields);
        }

        if ($type == 'all') {
            $all_fields_order_file = wa()->getConfig()->getConfigPath('all_fields_order.php', true, 'contacts');
            waUtils::varExportToFile($fields, $all_fields_order_file, true);
            return;
        }

        if ($type == 'person' || $type == 'company') {
            $main_fields = $type == 'person' ? self::$person_main_fields : self::$company_main_fields;
            foreach ($main_fields as $id) {
                $k = in_array($id, $fields);
                if ($k !== false) {
                    unset($fields[$k]);
                }
            }

            // enable main fields + set order
            $sort = 0;
            foreach ($main_fields as $id) {
                $field = waContactFields::get($id, 'all');
                if ($field) {
                    waContactFields::updateField($field);
                    waContactFields::enableField($field, $type, $sort);
                    $sort += 1;
                }
            }

            // enable other fields + set order
            foreach ($fields as $id) {
                $field = waContactFields::get($id, 'all');
                if ($field) {
                    waContactFields::updateField($field);
                    waContactFields::enableField($field, $type, $sort);
                    $sort += 1;
                }
            }
        }
    }

    public static function deleteField($field)
    {
        if (is_string($field)) {
            $field_id = $field;
            $field = waContactFields::get($field, 'all');
            if ($field) {
                if (contactsProHelper::isEnabledSearchingByField($field_id)) {
                    contactsProHelper::disableSearchingByField($field_id);
                }
                waContactFields::deleteField($field_id);
            }
        } else {
            $field_id = $field->getId();
            $field = waContactFields::get($field, 'all');
            if ($field) {
                if (contactsProHelper::isEnabledSearchingByField($field_id)) {
                    contactsProHelper::disableSearchingByField($field_id);
                }
                waContactFields::deleteField($field_id);
            }
        }
        if ($field_id) {
            $all_fields_order_file = wa()->getConfig()->getConfigPath('all_fields_order.php', true, 'contacts');
            $order = include($all_fields_order_file);
            $k = array_search($field_id, $order);
            if ($k !== false) {
                unset($order[$k]);
                waUtils::varExportToFile($order, $all_fields_order_file, true);
            }
        }
    }

    public static function getAllFieldsOrder()
    {
        return array_keys(self::getAllFields());
    }

    public static function saveAllFieldsOrder($fields) {
        self::saveFieldsOrder($fields, 'all');
    }

    public static function savePersonFieldsOrder($fields) {
        self::saveFieldsOrder($fields, 'person');
    }

    public static function saveCompanyFieldsOrder($fields) {
        self::saveFieldsOrder($fields, 'company');
    }

    public static function getSearchConditions($type = 'name')
    {
        $files = array(
            wa('contacts')->getConfig()->getConfigPath('search/search.php'),
            wa('contacts')->getConfig()->getPluginPath('pro').'/lib/config/search/search.php'
        );
        $conds = array();
        foreach ($files as $file) {
            if (file_exists($file)) {
                if ($type === 'name') {
                    foreach (include($file) as $k => $item) {
                        $conds[$k] = $item['name'];
                    }
                    return $conds;
                } else {
                    return include($file);
                }
            }
        }
        return $conds;
    }

    /**
     *
     * @param int|waContactField $field
     */
    public static function isEnabledSearchingByField($field)
    {
        if (is_object($field) && $field instanceof waContactField) {
            $field_id = $field->getId();
        } else {
            $field_id = $field;
        }
        $item = contactsSearchHelper::getItem("contact_info.{$field_id}");
        return $item !== null;
    }


    /**
     *
     * @param int|waContactField $field
     */
    public static function enableSearchingByField($field)
    {
        if (is_string($field)) {
            $field = waContactFields::get($field, 'all');
        }
        if ($field instanceof waContactField) {
            $field_id = $field->getId();
            $storage = $field->getStorage();
            $table = null;
            if ($storage instanceof waContactStorage) {
                $model = $storage->getModel();
                if ($model instanceof waModel) {
                    $table = $model->getTableName();
                }
            }
            if ($table === 'wa_contact_data') {
                $sql_t = "(SELECT COUNT(*) FROM `{$table}` WHERE contact_id = c.id AND field = '{$field_id}') :comparation ";
                $config = array(
                    'field_id' => $field_id,
                );
                $config['items'] = array(
                    'blank' => array(
                        'name' => 'Empty',
                        'where' => str_replace(':comparation', "= 0", $sql_t)
                    ),
                    'not_blank' => array(
                        'name' => 'Not empty',
                        'where' => str_replace(':comparation', "> 0", $sql_t)
                    ),
                    ':sep' => array(),
                    ':values' => array(
                        "autocomplete" => "AND value LIKE '%:term%'",
                        "limit" => 10,
                        "sql" => "SELECT value, value AS name, COUNT(*) count
                    FROM {$table}
                    WHERE field = '{$field_id}' :autocomplete
                    GROUP BY value
                    ORDER BY count DESC
                    LIMIT :limit",
                        "count" => "SELECT COUNT(DISTINCT value) FROM `{$table}` WHERE field = '{$field_id}'"
                    )
                );
                contactsSearchHelper::updateItem("contact_info.{$field_id}", $config);
            }
        }
    }

    public static function disableSearchingByField($field)
    {
        if (is_object($field) && $field instanceof waContactField) {
            $field_id = $field->getId();
        } else {
            $field_id = $field;
        }
        contactsSearchHelper::removeItem("contact_info.{$field_id}");
    }

    public static function sortFieldsInSearchConfig($fields)
    {
        $field_ids = self::$person_main_fields + self::$company_main_fields;
        foreach ($fields as $field) {
            if (is_string($field)) {
                $field_ids[] = $field;
            } else {
                $field_ids[] = $field->getId();
            }
        }
        contactsSearchHelper::sortItems(array_unique($field_ids), 'contact_info');
    }

    public static function getImportExportFields($fields = null)
    {
        if ($fields === null) {
            $fields = waContactFields::getInfo('enabled');
            unset($fields['name']);
        }
        $data = array();
        foreach($fields as $fieldId => $fieldInfo) {

            if ($fieldInfo['type'] === 'Hidden') {
                continue;
            }

            // Helper array to fill in first
            $opts = array(/*
                ...,
                ext (may be single '') => array(
                    ..., subfieldId (may be single ''), ...
                ),
                ...
            */);

            // add extensions
            $opts[''] = array();
            if(isset($fieldInfo['ext']) && $fieldInfo['ext']) {
                foreach($fieldInfo['ext'] as $k => $v) {
                    $opts[$k] = array();
                }
            }

            // add subfields (or a single '', if no subfields)
            foreach($opts as &$o) {
                if (isset($fieldInfo['fields']) && $fieldInfo['fields']) {
                    foreach($fieldInfo['fields'] as $k => $v) {
                        if ($v['type'] === 'Hidden') {
                            continue;
                        }
                        $o[] = $k;
                    }
                } else {
                    $o[] = '';
                }
            }
            unset($o);

            // Fill $fieldInfo['options'] using $opts
            $fieldInfo['options'] = array(/* value => human-readable name */);
            foreach($opts as $ext => $o) {
                foreach($o as $subfield) {
                    $value = $fieldId;
                    $name = $fieldInfo['name'];
                    if ($subfield) {
                        $value .= ':'.$subfield;
                    }
                    if ($ext) {
                        $value .= '.'.$ext;
                        $name .= ' - '.$fieldInfo['ext'][$ext];
                    }
                    if ($subfield) {
                        $name .= ': '.$fieldInfo['fields'][$subfield]['name'];
                    }

                    $fieldInfo['options'][$value] = $name;
                }
            }
            $data[$fieldId] = $fieldInfo;
        }
        return $data;
    }

    public static function chainViewAction($action)
    {
        if (is_string($action) && class_exists($action)) {
            $action = new $action();
        }
        if (!($action instanceof waViewAction)) {
            return null;
        }
        $view = wa()->getView();
        $vars = $view->getVars();
        $html = $action->display();
        $view->clearAllAssign();
        $view->assign($vars);
        return $html;
    }

    public static function getSignupFormFields($form_params, $absolute = 0)
    {
        $fields = array();
        // for all saved fields
        foreach ($form_params['fields'] as $field_id => $field) {
            $placeholder = ifset($field['placeholder'], "");
            if (strpos($field_id, '.')) {
                $field_id_parts = explode('.', $field_id);
                $id = $field_id_parts[0];
                $field['ext'] = $field_id_parts[1];
            } else {
                $id = $field_id;
            }
            // will get class instance
            $f = waContactFields::get($id);
            $field_params = array(
                'namespace'=>'data'
            );

            $field_class = array();
            $field_attrs = array(
                'placeholder="'.htmlspecialchars($placeholder).'" '.(!empty($field['required']) ? "" : ""),
            );

            if ($f instanceof waContactEmailField) {
                $field_class[] = 'wa-email-input';
            }
            if (!empty($field['required'])) {
                $field_class[] = 'wa-required-input';
            }

            $field_attrs[] = 'class="' . implode(' ', $field_class) . '"';
            $field_attrs = implode(' ', $field_attrs);

            if ($f) {
                $fields[$field_id] = array($f, $field);
                // if label in DB is empty - get current field name
                $fields[$field_id][1]['caption'] = ifset($field['caption'], $f->getName(null, true));
            } elseif ($field_id == 'password') {
                // password field
                $fields[$field_id] = array(new waContactPasswordField($field_id, _ws('Password')), $field);
                $fields[$field_id][1]['html'] = $fields[$field_id][0]->getHTML($field_params, $field_attrs);
                if (empty($fields[$field_id][1]['caption'])) {
                    $fields[$field_id][1]['caption'] =  _ws('Password');
                }

                // and confirm password field
                $field_id .= '_confirm';
                $fields[$field_id] = array(new waContactPasswordField($field_id, _ws('Confirm password')), $field);
                $fields[$field_id][1]['caption'] =  _ws('Confirm password');
            }
            if ($field_id === 'address' && $absolute) {
                $field_params['xhr_url'] = wa()->getRouteUrl('/frontend/regions', array(), true);
                $field_params['xhr_cross_domain'] = true;
            }
            $fields[$field_id][1]['html'] = preg_replace('|<p>(.*)</p>|', '$1', $fields[$field_id][0]->getHTML($field_params, $field_attrs));
        }

        return $fields;
    }

    public static function signUpForm($form_id, $include_css = 0, $absolute = 0, $iframe = 0)
    {
        $cf = new contactsFormModel();
        $signup_form = $cf->getById($form_id);
        if (!$signup_form) {
            return false;
        }

        $cfp = new contactsFormParamsModel();
        $signup_form_params = $cfp->get($form_id);

        $old_app = wa()->getApp();
        wa('contacts', true);

        $signup_form_params['fields'] = self::getSignupFormFields($signup_form_params, $absolute);

        $view = wa()->getView();

        $uniqid = 'contactspro' . md5(serialize($cf->getById($form_id)));

        $view->assign('form', $signup_form);
        $view->assign('uniqid', $uniqid);
        $view->assign('params', $signup_form_params);

        $view->assign('include_css', $include_css);
        $view->assign('absolute', $absolute);
        $view->assign('iframe', $iframe);

        waLocale::loadByDomain('webasyst');

        $form_html = $view->fetch('plugins/pro/templates/forms/signup_form.html');

        wa($old_app, true);

        return $form_html;
    }

    public static function getSignupJsCode($form_id)
    {
        $params = array(
            'form_id' => $form_id,
            'no_js' => 1
        );
        return $code = trim(wao(new contactsProPluginFrontendGetSignupFormJsAction($params))->display());
    }

    public static function paginator($params = array(), $id = null, $style = '')
    {
        $default_params = array(
            'count' => 30,
            'offset' => 0,
            'total_count_text' => '',
            'total_count' => 30
        );
        $params = array_merge($default_params, $params);
        $id = $id ? $id : ('paginator-' . rand());

        $html = '';

        $type = wa('contacts')->getConfig('contacts')->getOption('paginator_type');

        $html .= "<div class='block paging contact-paginator' id='{$id}' style='{$style}'>";
        if ($params['total_count'] > 30) {
            $html .= "<span class='c-page-num'>" . _w('Show') . ' ';
            $html .= "  <select class='items-per-page'>";
            foreach (array(30, 50, 100, 200, 500) as $n) {
                $html .= "  <option value='{$n}' " . ($params['count'] == $n ? 'selected' : '') . ">{$n}</option>";
            }
            $html .= "  </select> " . _w('items on a page');
            $html .= "</span>";
        }
        if ($type === 'page') {
            $html .= "<span>{$params['total_count_text']} <span class='total'>{$params['total_count']}</span></span>";
        }
        $pages = ceil($params['total_count'] / $params['count']);
        if ($pages > 1) {
            $html .= '<span class="pages">';
            if ($type === 'page') {
                $html .= _w('Pages') . ': ';
            }
            $p = ceil($params['offset'] / $params['count']) + 1;

            if ($type === 'page') {
                $f = 0;
                for ($i = 1; $i < $pages; $i += 1) {
                    if (abs($p - $i) < 2 || $i < 2 || $pages - $i < 1) {
                        $html .= "<a class='" . ($i == $p ? 'selected' : '') . "' href='javascript:void(0);' data-offset='" . (($i - 1) * $params['count']) . "'>{$i}</a>";
                        $f = 0;
                    } else if ($f++ < 3) {
                        $html .= '.';
                    }
                }
            } else {
                $html .= ($params['offset'] + 1). '&mdash;' . (min($params['total_count'], $params['offset'] + $params['count']));
                $html .= ' ' . _w('of') . ' ' . $params['total_count'];
            }

            if ($p > 1) {
                $html .= "<a href='javascript:void(0);' data-offset='" . (($p - 2) * $params['count']) ."' class='prevnext'><i class='icon10 larr'></i>"._w('prev')."</a>";
            }
            if ($p < $pages) {
                $html .= "<a href='javascript:void(0);' data-offset='" . ($p * $params['count']) . "' class='prevnext'>"._w('next')."<i class='icon10 rarr'></i></a>";
            }
            $html .= '</span>';
        } else if ($type !== 'page') {
            $html .= min($params['offset'] + 1, $params['total_count']) . '&mdash;' . (min($params['total_count'], $params['offset'] + $params['count']));
            $html .= ' ' . _w('of') . ' ' . $params['total_count'];
        }
        $html .= '</div>';

        $html .= "<script>";
        $html .=    "$(function() {";
        $html .=        "$('#{$id}').off('click.contact_paginator', '.pages a').on('click.contact_paginator', '.pages a', function() {";
        $html .=            "$('#{$id}').trigger('choose_page', [$(this).data('offset')]);";
        $html .=        "});";
        $html .=    "});";
        $html .= "</script>";

        return $html;

    }

    public static function viewPrepeare(waContactsCollection $collection, $id, $auto_title = true)
    {
        $view_model = new contactsViewModel();
        $view = $view_model->get($id);

        if ($view) {
            $collection->setUpdateCount(array(
                'model' => $view_model,
                'id' => $id,
                'count' => $view['count']
            ));
            $title = '';
            if ($auto_title) {
                $title = $view['name'];
            }
            if ($view['type'] === 'category') {
                $collection->setHash('category/' . $view['category_id']);
            } else {
                $collection->setHash($view['hash']);
            }
            $collection->prepare(false, true);

            $view['auto_title'] = $collection->getTitle() ? $collection->getTitle() : '';
            if ($title) {
                $collection->setTitle($title);
            }
            $view['contact'] = array();
            if ($view['contact_id']) {
                $contact = new waContact($view['contact_id']);
                $view['contact'] = array(
                    'name' => $contact['name']
                );
            }
            $view['create_datetime_str'] = waDateTime::format('humandatetime', $view['create_datetime']);

            $hash_ar = explode('/', trim($view['hash'], '/'));
            if ($view['type'] === 'search') {
                $view['search_hash'] = isset($hash_ar[2]) ? $hash_ar[2] : '';
                $view['highlight_terms'] = array();
            }
            $collection->setInfo($view);
        }
    }

    public static function importPrepare(waContactsCollection $collection, $start_time, $auto_title = true)
    {
        if ($auto_title) {
            $collection->setTitle(_wp('Import').' ('.waDateTime::format('dtime', $start_time, wa()->getUser()->getTimezone()).')');
        }
        $m = new waModel();
        $collection->addWhere("c.create_method = 'import'");
        $collection->addWhere('c.create_contact_id = '.wa()->getUser()->getId());
        $collection->addWhere("c.create_datetime >= '".$m->escape($start_time)."'");
    }

    public static function tagPrepare(waContactsCollection $collection, $tag_id, $auto_title)
    {
        if ($tag_id) {
            $tag_id = (int) $tag_id;
            $al = $collection->addJoin('contacts_contact_tags');
            $collection->addWhere("{$al}.tag_id={$tag_id}");
            if ($auto_title) {
                $m = new contactsTagModel();
                $tag = $m->getById($tag_id);
                if ($tag) {
                    $collection->setTitle(_wp('Tag') . '=' . $tag['name']);
                } else {
                    $collection->setTitle(_wp('Tag') . '=' . $tag_id);
                }
            }
        } else {
            $collection->addWhere('0');
        }
    }


    public static function getApps()
    {
        static $apps = null;

        if ($apps === null) {
            $apps = array();
            foreach (wa()->getApps() as $app_id => $app) {
                if (wa()->appExists($app_id)) {
                    $apps[$app_id] = $app;
                }
            }
        }

        return $apps;
    }

    /**
     * Standadrt array_merge doesn't work here, because
     * values in the input array with numeric keys will be
     * renumbered with incrementing keys starting from zero in the result array.
     * @see php http://php.net/manual/en/function.array-merge.php
     *
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    public static function arrayMerge($arr1, $arr2 /*, etc */)
    {
        $res = array();
        if (func_num_args() > 0) {
            foreach (func_get_args() as $arr) {
                if (is_array($arr)) {
                    foreach ($arr as $k => $v) {
                        $res[$k] = $v;
                    }
                }
            }
        }
        return $res;
    }

    public static function hasAccessToContactActivity($contact_id)
    {
        if (wa()->getUser()->isAdmin()) {
            return true;
        }
        $contact = new waContact($contact_id);
        if ($contact->exists() && !$contact->isAdmin()) {
            return true;
        }
        return false;
    }

    public static function addBlockJs($files = array(), $compile_file)
    {
        $app_id = 'contacts';
        if (!SystemConfig::isDebug()) {
            $path = wa()->getConfig($app_id)->getPluginPath('pro');
            $compile_file_path = $path . '/' . $compile_file;

            $files_combine = array();
            $mtime = file_exists($compile_file_path) ? filemtime($compile_file_path) : 0;

            $r = true;
            foreach ($files as $f) {
                $files_combine[] = $f;
                if ($mtime && filemtime($path.'/'.$f) > $mtime) {
                    $mtime = 0;
                }
            }

            if ($files_combine && !$mtime && waFiles::create($compile_file_path)) {
                // check Google Closure Compiler
                // https://developers.google.com/closure/compiler/docs/gettingstarted_app

                if ($compiler = waSystemConfig::systemOption('js_compiler')) {
                    $cmd = 'java -jar "'.$compiler.'"';
                    foreach ($files_combine as $file) {
                        $cmd .= ' --js "' . $path . '/' . $file . '"';
                    }
                    $cmd .= ' --js_output_file "'. $compile_file_path .'"';
                    system($cmd,$res);
                    $r = !$res;
                } else {
                    $r = false;
                }

                if(!$r) {
                    $data = "";
                    foreach ($files_combine as $file) {
                        $data .= file_get_contents($path . '/' . $file).";\n";
                    }
                    $r = @file_put_contents($compile_file_path, $data);
                }
            }

            if ($r && $files_combine) {
                wa()->getResponse()->addJs('plugins/pro/' . $compile_file, $app_id);
                return;
            }

        } else {
            foreach ($files as $f) {
                wa()->getResponse()->addJs('plugins/pro/' . $f, $app_id);
            }
        }
    }

    /**
     * Use from backend for form editor
     * @return type
     */
    public static function getFormFields()
    {
        $fields = array(
            'main' => array(),
            'other' => array(),
            'specials' => array()
        );
        $all_fields = waContactFields::getAll('person', true);
        if (isset($all_fields['company_contact_id'])) {
            unset($all_fields['company_contact_id']);
        }
        if (isset($all_fields['name'])) {
            unset($all_fields['name']);
        }
        $disabled_fields = array_fill_keys(contactsProHelper::$disabled_fields['person'], true);
        $main_fields = array_fill_keys(contactsProHelper::$person_main_fields, true);
        foreach ($all_fields as $fld_id => $fld) {
            if (isset($disabled_fields[$fld_id]) || !$fld) {
                continue;
            }
            if (isset($main_fields[$fld_id])) {
                $fields['main'][$fld_id] = $all_fields[$fld_id];
            } else {
                $fields['other'][$fld_id] = $all_fields[$fld_id];
            }
        }

        // PASSWORD IS ALWAYS DOWN

        $fields['specials']['password'] = new waContactPasswordField('password', 'Password');
        return self::filterNotEmpty(contactsProHelper::arrayMerge($fields['main'], $fields['other'], $fields['specials']));
    }

    public static function filterNotEmpty($array)
    {
        $res = array();
        foreach ($array as $k => $v) {
            if (!empty($v)) {
                $res[$k] = $v;
            }
        }
        return $res;
    }

    public static function allEmpty($array, $keys = array())
    {
        foreach ($keys as $key) {
            if (!empty($array[$key])) {
                return false;
            }
        }
        return true;
    }

    public static function atLeastOneNotEmpty($array, $keys = array())
    {
        return !self::allEmpty($array, $keys);
    }

    public static function getViewIcons()
    {
        return array(
            'contact',
            'user',
            'folder',
            'notebook',
            'lock',
            'lock-unlocked',
            'broom',
            'star',
            'livejournal',
            'contact',
            'lightning',
            'light-bulb',
            'pictures',
            'reports',
            'books',
            'marker',
            'lens',
            'alarm-clock',
            'animal-monkey',
            'anchor',
            'bean',
            'car',
            'disk',
            'cookie',
            'burn',
            'clapperboard',
            'bug',
            'clock',
            'cup',
            'home',
            'fruit',
            'luggage',
            'guitar',
            'smiley',
            'sport-soccer',
            'target',
            'medal',
            'phone',
            'store',
            'basket',
            'pencil',
            'lifebuoy',
            'screen'
        );
    }

}