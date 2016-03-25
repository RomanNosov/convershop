<?php

class contactsProPluginConstructorSaveController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_wp('Access denied.'));
        }

        if (! ( $id = $this->getRequest()->post('id'))) {
            $this->errors[] = _wp('Empty id.');
            return;
        }

        contactsProHelper::ensureCustomFieldsExists();

        foreach (array('firstname', 'middlename', 'lastname', 'email', 'phone') as $f_id) {
            $field = waContactFields::get($f_id, 'all');
            if ($field) {
                $field->setParameter('my_profile', 2);
                waContactFields::updateField($field);
            }
        }

        switch($id) {
            case '#new':    // Add new custom field
                if (! ( $field = $this->getUpdatedField())) {
                    return;
                }
                waContactFields::createField($field);
                break;
            default:
                if (! ( $field = waContactFields::get($id, 'all'))) {
                    $this->errors[] = _wp('Unknown field:').' '.$id;
                    return;
                }
                if (! ( $field = $this->getUpdatedField($field))) {
                    return;
                }
                if (!in_array($id, contactsProHelper::$noneditable_fields)) {
                    waContactFields::updateField($field);
                }
                break;
        }
        $enable = $this->getRequest()->post('enable', -1);
        $field_types = (array)$this->getRequest()->post('type');
        $types = array('person', 'company');

        if ($enable == "true") {
            $field_types = array('person');
        }
        elseif ($enable == 'false') {
            $field_types = array();
        }
        // email field is always enebled for person
        elseif ($field instanceof waContactEmailField) {
            $field_types = array_unique(array_merge($field_types, array('person')));
        }

        foreach ($types as $type) {
            if (in_array($type, $field_types)) {
                waContactFields::enableField($field, $type);
            } else {
                waContactFields::disableField($field, $type);
            }
        }

        if ($field_types) {
            if (!contactsProHelper::isEnabledSearchingByField($field)) {
                contactsProHelper::enableSearchingByField($field);
            }
        } else {
            if (contactsProHelper::isEnabledSearchingByField($field)) {
                contactsProHelper::disableSearchingByField($field);
            }
        }

        $this->response = 'done';
    }

    protected function getTransliteratedId($names)
    {
        $id = null;

        if (!empty($names['en_US'])) {
            $id = strtolower($this->transliterate($names['en_US']));
        } else {
            $id = strtolower($this->transliterate(reset($names)));
        }

        return $id;
    }

    public function getUpdatedField($field = null) {

        $names = $this->getRequest()->post('name');
        $id = trim($this->getRequest()->post('id_val'));
        $ftype = $this->getRequest()->post('ftype');
        //$unique = $this->getRequest()->post('unique');
        $unique = 0;

        if (!is_array($names) || !$names) {
            if ($field) {
                $names = array();
            } else {
                $this->errors[] = 'Wrong names: must be a non-empty array.';
                return false;
            }
        }

        if ($names) {
            $locales = waSystem::getInstance()->getConfig()->getLocales('name');
            $n = array();
            foreach($names as $l => $value) {
                if (!isset($locales[$l])) {
                    $this->errors[] = sprintf(_wp('Unknown locale: %s'), $l);
                    return false;
                }
                if (!empty($value)) {
                    $n[$l] = (string) $value;
                }
            }
            $names = $n;
            if (empty($names)) {
                $this->errors[] = array(wa()->getLocale() => _wp('Required field'));
                return false;
            }
        }

        if ($field) {
            $id = $field->getId();
        } else {
            if (strlen($id) === 0) {
                $this->errors[] = array("id_val" => _wp('Required field'));
                return false;
            }
            if (preg_match('/[^a-z_0-9]/i', $id)) {
                $this->errors[] = array('id_val' => _wp('Only English alphanumeric, hyphen and underline symbols are allowed'));
                return false;
            }
            // field id exists
            if (null !== waContactFields::isSystemField($id)) {
                $this->errors[] = _wp('This ID is already in use');
                return false;
            }

            switch($ftype) {
                case "String":
                    $field = new waContactStringField($id, $names);
                    break;
                case "Date":
                    $field = new waContactDateField($id, $names);
                    break;
                case "Number":
                    $field = new waContactNumberField($id, $names);
                    break;
                case "Phone":
                    $field = new waContactPhoneField($id, $names);
                    break;
                case "Url":
                    $field = new waContactUrlField($id, $names);
                    break;
                case "Text":
                    $field = new waContactTextField($id, $names);
                    break;
                case "Select":
                    $options = array_map( 'trim', array_filter( explode("\r\n", $this->getRequest()->post('select_field_value')) ) );
                    $field = new waContactSelectField($id, $names, array(
                        'options' => $options
                    ));
                    break;
                default:
                    $this->errors[] = _wp('Unknown field type:').' '.$ftype;
                    return false;
            }
        }

        if ($names && !waContactFields::isSystemField($id) && !in_array($id, contactsProHelper::$noneditable_fields)) {
            $field->setParameter('localized_names', $names);
        }

        if ($this->getRequest()->post('select_field_value') && $field->getParameter('storage') === 'data') {
            $opts = array_map('trim', array_filter(explode("\r\n", $this->getRequest()->post('select_field_value'))));
            if (!empty($opts)) {
                $select_options = array();
                foreach ($opts as $val) {
                    $select_options[$val] = $val;
                }
                $field->setParameter('options', $select_options);
            }
        }

        if ($unique && !in_array($id, array('name', 'title', 'firstname', 'middlename', 'lastname', 'company')) && !($field instanceof waContactCompositeField)) {
            // Check for duplicates in $field
            $dupl = $field->getStorage()->duplNum($field);

            if ($dupl) {
                $msg = sprintf(_wp('We have found %d duplicate for this field', 'We have found %d duplicates for this field'), $dupl);
                $msg = str_replace(array('[', ']'), array('<a href="'.wa_url().'webasyst/contacts/#/contacts/duplicates/'.$field->getId().'/">', '</a>'), $msg);
                $this->errors[] = $msg;
                return false;
            }

            $field->setParameter('unique', !!$unique);
        } else {
            $field->setParameter('unique', false);
        }

        $my_profile = $this->getRequest()->post('my_profile', '0');
        if (!in_array($my_profile, array('0', '1', '2'))) {
            $my_profile = '0';
        }
        $field->setParameter('my_profile', $my_profile);

        if (!$this->errors) {
            return $field;
        }
        return false;
    }

    public function transliterate($str, $strict = true)
    {
        if (empty($str)) {
            return "";
        }
        $str = preg_replace('/\s+/u', '_', $str);
        if ($str) {
            foreach (waLocale::getAll() as $lang) {
                $str = waLocale::transliterate($str, $lang);
            }
        }
        $str = preg_replace('/[^a-zA-Z0-9_-]+/', '', $str);
        if ($strict && !$str) {
            $str = date('Ymd');
        }
        return strtolower($str);
    }
}