<?php

class contactsProPluginImportUploadAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $file = $this->getStorage()->read('import/file');
        $csv = new waCSV(true, null, false, $file);
        if ($e = $this->getRequest()->get('encoding')) {
            if (strtolower($e) != 'utf-8') {
                $csv->setEncoding($e);
            }
        }
        $info = null;
        try {
            $info = $csv->getInfo();
        } catch (Exception $e) {
            // file doesn't exist
        }
        $this->view->assign('csv', $info);
        $this->view->assign('group_id', $this->getRequest()->get('group_id'));

        $fields = waContactFields::getInfo('enabled');
        unset($fields['name']);
        $this->view->assign('fieldInfo', $fields);
        
        // Array of options for <select> to show above CSV columns
        $fields = contactsProHelper::getImportExportFields($fields);
        $this->view->assign('fields', $fields);
    }
}

// EOF