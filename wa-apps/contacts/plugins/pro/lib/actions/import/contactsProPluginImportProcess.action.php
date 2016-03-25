<?php

class contactsProPluginImportProcessAction extends waViewAction
{
    protected $countries;
    protected $loc_country_names;
    protected $regions;
    
    /**
     *
     * @var waCountryModel
     */
    protected $country_model;
    
    /**
     *
     * @var waRegionModel
     */
    protected $region_model;
    
    /**
     *
     * @var array
     */
    protected $locales;
    
    /**
     *
     * @var array
     */
    protected $timezones;

    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        // input file and csv reader setup
        $file = $this->getStorage()->read('import/file');
        $csv = new waCSV($this->getRequest()->post('first_line'), $this->getRequest()->post('delimiter'), false, $file);
        if ( ( $e = $this->getRequest()->post('encoding'))) {
            if (strtolower($e) != 'utf-8') {
                $csv->setEncoding($e);
            }
        }

        // Close session to avoid blocking other requests
        $this->getStorage()->close();

        $time = date('Y-m-d H:i:s');
        $fields = $this->getRequest()->post('fields');
        $group_id = $this->getRequest()->post('group_id');

        // Active fields for contact types
        $companyActive = $this->flat(waContactFields::getAll('company'));
        $personActive = $this->flat(waContactFields::getAll('person'));
        if (isset($personActive['birthday'])) {
            $personActive['birth_year'] = null;
            $personActive['birth_month'] = null;
            $personActive['birth_day'] = null;
        }

        // Ignore inactive fields and skipped csv columns
        foreach ($fields as $k => $f) {
            if ( ( $p = strpos($f, '.'))) {
                $f = substr($f, 0, $p);
            }
            if (!$f || (!isset($companyActive[$f]) && !isset($personActive[$f]))) {
                unset($fields[$k]);
            }
        }

        // Special fields to allow in the loop
        $companyActive['name'] = true;
        $companyActive['create_method'] = true;
        $companyActive['is_company'] = true;
        $personActive['create_method'] = true;
        $personActive['is_company'] = true;

        $errors = array();
        $rows = 0;      // rows count
        $contacts = 0;  // successfully imported contacts
        $contact_model = new waContactModel();
        $contact_groups_model = new waUserGroupsModel();
        waLocale::getAll();
        while ( ( $data = $csv->import(1))) {
            $rows++;
            $row = array_shift($data);
            $info = array(); // values for wa_contact
            $data = array(); // values for wa_contact_data and wa_contact_emails
            foreach ($fields as $i => $f) {
                $row[$i] = isset($row[$i]) ? trim($row[$i]) : '';
                
                if ($f === 'birthday') {
                    if ($row[$i]) {
                        foreach (waContactBirthdayField::parse($row[$i]) as $k => $v) {
                            if ($v !== null) {
                                $info['birth_'.$k] = $v;
                            }
                        }
                        if (!empty($info['birth_year']) && $info['birth_year'] < 100) {
                            $cur_year = date('y');
                            if ($info['birth_year'] > $cur_year) {
                                $info['birth_year'] = '19' . $info['birth_year'];
                            } else {
                                $info['birth_year'] = '20' . $info['birth_year'];
                            }
                            if (!empty($info['birth_month']) && !empty($info['birth_day'])) {
                                $bdt = strtotime("{$info['birth_year']}-{$info['birth_month']}-{$info['birth_day']}");
                                if (time() < $bdt) {
                                    $info['birth_year']{0} = '1';
                                    $info['birth_year']{1} = '9';
                                }
                            }
                        }
                    }
                } else if ($f === 'locale') {
                    $info[$f] = $this->getLocale($row[$i]);
                } else if ($f === 'timezone') {
                    $info[$f] = $this->getTimezone($row[$i]);
                } else {
                    if ($contact_model->fieldExists($f)) {
                        $info[$f] = $row[$i];
                    } else {
                        $f = explode(".", $f, 2);
                        $field = $f[0];
                        
                        $fld = waContactFields::get($field);
                        if ($fld && $fld instanceof waContactSelectField) {
                            $options = $fld->getOptions();
                            if (!isset($options[$row[$i]])) {
                                $row_val = mb_strtolower($row[$i]);
                                foreach ($options as $opt_id => $opt) {
                                    if (mb_strtolower($opt) === $row_val) {
                                        $row[$i] = $opt_id;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        $ext = isset($f[1]) ? $f[1] : '';
                        $data[$field][$ext][] = $row[$i];
                    }
                }
            }

            // Special fields
            $info['create_method'] = 'import';
            $info['is_company'] = 0;
            $info['name'] = $this->formatName($info);
            
            // Is it a company?
            if (!$info['name'] && isset($info['company']) && $info['company']) {
                $info['name'] = $info['company'];
                $info['is_company'] = 1;
            }

            // Is it a person with unknown name but known email?
            if (!$info['name'] && isset($data['email']) && $data['email']) {
                // Use the first email as a name
                $firstname = reset(reset($data['email']));
                if (FALSE !== ( $p = strpos($firstname, '@'))) {
                    $firstname = substr($firstname, 0, $p);
                }
                $info['name'] = $info['firstname'] = $firstname;
            }

            // Everyone needs a name!
            if (!$info['name']) {
                $errors[$rows] = _w("At least one of the primary fields (first, middle, lastname, company and email) must be filled.");
                continue;
            }
            
            // Now we know whether contact is a person or a company. Remove unused fields.
            $info = array_intersect_key($info, $info['is_company'] ? $companyActive : $personActive);

            $data = array_intersect_key($data, $info['is_company'] ? $companyActive : $personActive);
            
            // Insert into wa_contact
            if ($contact_id = $contact_model->insert($info)) {
                $contacts++;
            } else {
                $errors[$rows] = _w("Error inserting to DB");
                continue;
            }

            // Add to group
            if ($group_id) {
                $contact_groups_model->insert(array(
                    'contact_id' => $contact_id,
                    'group_id' => $group_id
                ));
            }

            // Insert into wa_contact_emails
            if (!empty($data['email'])) {
                $values = array();
                $sort = 0;
                foreach ($data['email'] as $ext => $ext_fields) {
                    $ext = $contact_model->escape($ext);
                    foreach ($ext_fields as $value) {
                        if (!$value) {
                            continue;
                        }
                        $value = $contact_model->escape(trim(strtolower($value)));
                        $values[] = "($contact_id, '$ext', $sort, '$value')";
                        $sort += 1;
                    }
                }
                if ($values) {
                    $sql = "INSERT INTO wa_contact_emails (contact_id, ext, sort, email) VALUES ".implode(',', $values);
                    $contact_model->exec($sql);
                }
                unset($data['email']);
            }

            // Insert into wa_contact_data
            if ($data) {
                $values = array();
                $country_regions = array();
                foreach ($data as $field => $data_field) {
                    $field = $contact_model->escape($field);
                    $sort = 0;
                    foreach ($data_field as $ext => $ext_fields) {
                        $ext = $contact_model->escape($ext);
                        foreach ($ext_fields as $value) {
                            if (!$value) {
                                continue;
                            }
                            if ($field === 'address:country' || $field === 'address:region') {
                                $country_regions[$sort][$field] = array(
                                    'ext' => $ext,
                                    'value' => $value
                                );
                            } else {
                                $value = $contact_model->escape(trim($value));
                                if ($field === 'phone') {
                                    $value = preg_replace('/[^\d]+/', '', $value);
                                }
                                $values[] = "($contact_id, '$field', '$ext', $sort, '$value')";
                            }
                            $sort += 1;
                        }
                    }
                }
                // choose country and region
                foreach ($country_regions as $sort => $cr_item) {
                    
                    $ext = $cr_item['address:country']['ext'];
                    
                    $country = '';
                    if (isset($cr_item['address:country']['value'])) {
                        $country = $this->getCountry($cr_item['address:country']['value']);
                    }
                    if ($country) {
                        $val = $country['iso3letter'];
                        $values[] = "($contact_id, 'address:country', '$ext', $sort, '$val')";
                        
                        if (isset($cr_item['address:region']['value'])) {
                            $region = $this->getRegion($cr_item['address:region']['value'], $country['iso3letter']);
                            if ($region) {
                                $val = $region['code'];
                                $values[] = "($contact_id, 'address:region', '$ext', $sort, '$val')";
                            } else {
                                $val = $contact_model->escape(trim($cr_item['address:region']['value']));
                                $values[] = "($contact_id, 'address:region', '$ext', $sort, '$val')";
                            }
                        }
                    } else {
                        if (isset($cr_item['address:country']['value'])) {
                            $val = $contact_model->escape(trim($cr_item['address:country']['value']));
                            $values[] = "($contact_id, 'address:country', '$ext', $sort, '$val')";
                        }
                        if (isset($cr_item['address:region']['value'])) {
                            $val = $contact_model->escape(trim($cr_item['address:region']['value']));
                            $values[] = "($contact_id, 'address:region', '$ext', $sort, '$val')";
                        }
                    }
                }
                if ($values) {
                    $sql = "INSERT INTO wa_contact_data (contact_id, field, ext, sort, value) VALUES ".implode(',', $values);
                    $contact_model->exec($sql);
                }
            }
        }

        // Save last successfull import start time in current user settings
        // and remove old import history
        if ($contacts) {
            $this->getUser()->setSettings('contacts', 'import', $time);
            $history = new contactsHistoryModel();
            $history->prune(0, 'import');
            
            $col = new contactsCollection("import/{$time}");
            $count = $col->count();
            $title = $col->getTitle();
            
            if ($history->save("/contacts/import/results/{$time}", $title, 'import', $count)) {
                //$this->logAction('import');
            }
            
        }

        // Remove temporary file
        $path = $csv->getPath($file);
        @unlink($path);

        // Template variables
        $this->view->assign('time', $time);
        $this->view->assign('contact_id', $this->getUser()->getId());
        $this->view->assign('contacts', $contacts);
        $this->view->assign('errors', $errors);
        if ($group_id) {
            $group_model = new waGroupModel();
            $this->view->assign('group', $group_model->getById($group_id));
        }
    }
    
    public function flat($fields)
    {
        $data = array();
        foreach ($fields as $f_id => $fld) {
            if ($fld instanceof waContactCompositeField) {
                foreach ($fld->getFields() as $sf_id => $sfld) {
                    $data["{$f_id}:{$sf_id}"] = true;
                }
            } else {
                $data[$f_id] = true;
            }
        }
        return $data;
    }
    
    public function getCountry($name)
    {
        $name = trim($name);
        if ($this->countries === null) {
            if (!$this->country_model) {
                $this->country_model = new waCountryModel();
            }
            $this->countries = $this->country_model->getAll('name');
            if (wa()->getUser()->getLocale() !== 'en_EN') {
                foreach ($this->countries as $country) {
                    $this->loc_country_names[_ws($country['name'])] = $country['name'];
                }
            }
        }
        if (isset($this->countries[$name])) {
            return $this->countries[$name];
        } else if (isset($this->loc_country_names[$name])) {
            return $this->countries[$this->loc_country_names[$name]];
        } else {
            foreach ($this->countries as $country_name => $country) {
                if (preg_match('/^' . preg_quote($country_name) . '$/iu', $name)) {
                    return $country;
                }
            }
            foreach ($this->loc_country_names as $loc_name => $country_name) {
                if (preg_match('/^' . preg_quote($loc_name) . '$/iu', $name)) {
                    return $this->countries[$country_name];
                }
            }
        }
        return null;
    }
    
    public function getRegion($name, $country_iso3)
    {
        $name = trim($name);
        if (!$this->region_model) {
            $this->region_model = new waRegionModel();
        }
        if (!isset($this->regions[$country_iso3])) {
            $this->regions[$country_iso3] = $this->region_model->getByField('country_iso3', $country_iso3, 'name');
        }
        $regions = $this->regions[$country_iso3];
        if (isset($regions[$name])) {
            return $regions[$name];
        } else {
            foreach ($regions as $region_name => $region) {
                if (preg_match('/^' . preg_quote($region_name) . '$/iu', $name)) {
                    return $region;
                }
            }
        }
        return null;
    }
    
    public function getLocale($str)
    {
        $str = trim($str);
        if (!$str) {
            return '';
        }
        if ($this->locales === null) {
            $this->locales = waLocale::getAll(true);
        }
        if (isset($this->locales[$str])) {
            return $str;
        } else {
            $str = mb_strtolower($str);
            foreach ($this->locales as $loc => $val) {
                if ($str === mb_strtolower($loc)) {
                    return $loc;
                } else if ($str === mb_strtolower(ifset($val['iso3'], ''))) {
                    return $loc;
                } else if ($str === mb_strtolower(ifset($val['name'], ''))) {
                    return $loc;
                } else if ($str === mb_strtolower(ifset($val['region'], ''))) {
                    return $loc;
                } else if ($str === mb_strtolower($val['name'] . " ({$val['region']})")) {
                    return $loc;
                }
            }
        }
        return $str;
    }
    
    public function getTimezone($str)
    {
        $str = trim($str);
        if (!$str) {
            return '';
        }
        if ($this->timezones === null) {
            $this->timezones = wa()->getDateTime()->getTimezones();
        }
        if (isset($this->timezones[$str])) {
            return $str;
        } else {
            $str_l = strtolower($str);
            $str_n = 0;
            if (is_numeric($str)) {
                if (mb_substr($str, 0, 1) === '−') {
                    $str_n = 0 - intval(mb_substr($str, 1, 2));
                } else {
                    $str_n = intval(mb_substr($str, 0, 3));
                }
            }
            foreach ($this->timezones as $tz => $val) {
                if (is_numeric($str)) {
                    $n = 0;
                    if (mb_substr($val, 0, 1) === '−') {
                        $n = 0 - intval(mb_substr($val, 1, 2));
                    } else {
                        $n = intval(mb_substr($val, 0, 3));
                    }
                    if ($n === $str_n) {
                        return $tz;
                    }
                } else if ($str_l === strtolower($tz)) {
                    return $tz;
                }
            }
        }
        return $str;
    }
    
    public function formatName($contact) 
    {
        $fst = trim(ifset($contact['firstname'], ''));
        $mdl = trim(ifset($contact['middlename'], ''));
        $lst = trim(ifset($contact['lastname'], ''));
        $cmp = trim(ifset($contact['company'], ''));
        
        $name = array();
        if ($fst || $fst === '0' || $mdl || $mdl === '0' || $lst || $lst === '0') 
        {
            $name[] = $lst;
            $name[] = $fst;
            $name[] = $mdl;
        } 
        else if ($cmp || $cmp === '0')
        {
            $name[] = $cmp;
        }
        foreach ($name as $i => $n) {
            if (!$n && $n !== '0') { 
                unset($name[$i]);
            }
        }
        return trim(implode(' ', $name));
    }
    
}

// EOF
