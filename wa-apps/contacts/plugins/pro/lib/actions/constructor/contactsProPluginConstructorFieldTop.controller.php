<?php

class contactsProPluginConstructorFieldTopController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $m = new waAppSettingsModel();
        if ($this->getRequest()->request('tags')) {
            $m->del('contacts', 'tags_disabled');
        } else {
            $m->set('contacts', 'tags_disabled', 1);
        }
        
        $id = $this->getRequest()->post('id', 0);

        contactsProHelper::ensureCustomFieldsExists();
        
        $fields_file = wa()->getConfig()->getConfigPath('custom_fields.php', true, 'contacts');
        if (!file_exists($fields_file)) {
            $this->errors[] = "There are not custom fields";
            return;
        }
        
        $fields = include($fields_file);
        
        $field_index = -1;
        foreach ($fields as $i => &$field) {
            /**
         * @var waContactField $field
         */
            $info = $field->getInfo();
            $field->prepareVarExport();
            if ($id && in_array($info['id'], $id)) {
                $field_index++;
                $field->setParameter('top', 1);
            }
            else {
                $field->setParameter('top', 0);
            }
        }
        unset($field);
        
        waUtils::varExportToFile(array_values($fields), $fields_file, true);
        
        $this->response = 'done';
    }
}

// EOF