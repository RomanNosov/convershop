<?php

class contactsProPluginConstructorMainAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $this->view->assign(array(
            'fields' => $this->getFields(),
            'locale' => $this->getLocale(),
            'other_locales' => $this->getOtherLocales(),
            'field_types' => $this->getFieldTypes(),
            'type' => 'person',
            'tags_disabled' => $this->appSettings('tags_disabled')
        ));

    }
    
    protected function getFields()
    {
        $contactFields = array();        
        $fields = contactsProHelper::getAllFields();
        if (isset($fields['company_contact_id'])) {
            unset($fields['company_contact_id']);
        }
        
        $loc = waLocale::getLocale();
        $field_types = $this->getFieldTypes();
        
        foreach ($fields as $field_id => $field) {
            /**
             * @var waContactField $field
             */
            $contactFields[$field_id] = $field->getInfo();
            if (method_exists($field, 'getOptions')) {
                $contactFields[$field_id]['options'] = $field->getOptions();
            }
            else {
                $contactFields[$field_id]['options'] = null;
            }

            // if this field is 'system' and we can't edit or delete it
            if (in_array($field_id, contactsProHelper::$noneditable_fields)) {
                $contactFields[$field_id]['editable'] = false;
                foreach(waLocale::getAll() as $locale_id) {
                    $contactFields[$field_id]['localized_names'][$locale_id] = $field->getName($locale_id);
                }
            } else
            {
                $contactFields[$field_id]['editable'] = true;
                $contactFields[$field_id]['localized_names'] = $field->getParameter('localized_names');
            }

            $contactFields[$field_id]['storage'] = $field->getParameter('storage');
            $contactFields[$field_id]['top'] = $field->getParameter('top');

            $contactFields[$field_id]['my_profile'] = $field->getParameter('my_profile');
            if (!$contactFields[$field_id]['my_profile']) {
                $contactFields[$field_id]['my_profile'] = '0';  // editable
            }

            if ($field instanceof waContactLocaleField) {
                $contactFields[$field_id]['type'] = 'Language';
            }
            if ($field instanceof waContactTimezoneField) {
                $contactFields[$field_id]['type'] = 'Timezone';
            }
            
            if (isset($field_types[$contactFields[$field_id]['type']])) {
                $contactFields[$field_id]['type_name'] = $field_types[$contactFields[$field_id]['type']];
            }            
        }
        
        // for holding order
        $main_fields = array();
        $other_fields = array();
        
        foreach($contactFields as $id => $data) {
            if ($id == 'name') {
                continue;
            }
            
            $fcp = waContactFields::get($id, 'all')->getParameter('fconstructor');
            if ($fcp && !is_array($fcp)) {
                $fcp = array($fcp);
            }
            if (!$fcp) {
                $fcp = array();
            }
            $fcp = array_flip($fcp);
            if (isset($fcp['hidden'])) {
                continue;
            }
            
            $pf = waContactFields::get($id, 'person');
            $cf = waContactFields::get($id, 'company');
            
            if ($pf instanceof waContactCompositeField || $cf instanceof waContactCompositeField) {
                $unique = 'n/a';
            } else {
                $unique = $pf ? $pf->getParameter('unique') : ($cf ? $cf->getParameter('unique') : false);
            }
            
            if (in_array($id, array('title', 'firstname', 'middlename', 'lastname', 'company', 'jobtitle'))) {
                $p_field = &$main_fields[$id];
            } else {
                $p_field = &$other_fields[];
            }

            $p_field = array(
                'name' => $data['name'], 
                'id' => $id,
                'type' => $data['type'],
                'type_name' => ifset($data['type_name']),
                'multi' => $data['multi'],
                'options' => $data['options'],
                'editable' => $data['editable'],
                'unique' => $unique,
                'storage' => $data['storage'],
                'pStatus' => $pf ? ($pf->getParameter('required') ? 'required' : 'enabled') : 'disabled',
                'cStatus' => $cf ? ($cf->getParameter('required') ? 'required' : 'enabled') : 'disabled',
                'localized_names' => $data['localized_names'],
                'my_profile' => $data['my_profile'],
                'top' => $data['top'],
                'icon' => ''
            );
            
            if ($id == 'email') {
                $p_field['icon'] = '<i class="icon16 email"></i>';
            } else if ($id == 'phone') {
                $p_field['icon'] = '<i class="icon16 phone"></i>';
            } else if ($id == 'im') {
                $p_field['icon'] = '<i class="icon16 im"></i>';
            }
            
            unset($p_field);
            
        }
        return array(
            'main' => $main_fields,
            'other' => $other_fields
        );
        
    }
    
    public function getLocale()
    {
        $l = wa()->getLocale();
        $ls = $this->getConfig()->getLocales('name_region');
        return array(
            'id' => $l,
            'name_region' => $ls[$l]
        );
    }
    
    public function getOtherLocales()
    {
        $l = wa()->getLocale();
        $ls = $this->getConfig()->getLocales('name_region');        
        $ols = array();
        unset($ls[$l]);
        foreach ($ls as $id => $nr) {
            $ols[] = array(
                'id' => $id,
                'name_region' => $nr
            );
        }
        return $ols;
    }
    
    public function getFieldTypes() 
    {
        return array(
            'String' => _wp('single line text'),
            'Text' => _wp('paragraph text'),
            'Number' => _wp('number'),
            'Select' => _wp('drop down'),
        );
    }
}

// EOF