<?php

class contactsProPluginConstructorFieldMoveController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $field_ids = $this->getRequest()->post('field_ids');
        if (!$field_ids) {
            $this->errors[] = "No field ids";
            return;
        }
        
        $fields_order = contactsProHelper::getAllFieldsOrder();
        if ($field_ids == $fields_order) {
            return;
        }
        
        $person_fields = array();
        $company_fields = array();
        
        foreach ($field_ids as $id) {
            $pf = waContactFields::get($id, 'person');
            $cf = waContactFields::get($id, 'company');
            $pStatus = $pf ? ($pf->getParameter('required') ? 'required' : 'enabled') : 'disabled';
            $cStatus = $cf ? ($cf->getParameter('required') ? 'required' : 'enabled') : 'disabled';
            if ($pStatus != 'disabled') {
                $person_fields[] = $id;
            }
            if ($cStatus != 'disabled') {
                $company_fields[] = $id;
            }
        }
        
        contactsProHelper::saveAllFieldsOrder($field_ids);        
        contactsProHelper::savePersonFieldsOrder($person_fields);
        contactsProHelper::saveCompanyFieldsOrder($company_fields);
        
        contactsProHelper::sortFieldsInSearchConfig($field_ids);
        
        $this->response = 'done';
    }
}

// EOF