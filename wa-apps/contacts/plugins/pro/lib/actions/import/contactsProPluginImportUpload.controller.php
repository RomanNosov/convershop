<?php

class contactsProPluginImportUploadController extends waViewController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        if ($this->getRequest()->getMethod() == 'post') {
            $csv = new waCSV(false, null, false);
            $csv->setEncoding($this->getRequest()->post('encode', null, 'utf-8'));
    
               $type = $this->getRequest()->post('type');
               try {
                if ($type == 1) {
                    $content = $this->getRequest()->post('content');
                    if (!trim($content)) {
                        throw new Exception(_w("Incorrect import data format"));
                    }
                    $file = $csv->saveContent($content);
    
                } elseif ($type == 2) {
                    $file = $csv->upload("csv");
                }
                // Save for the next actions
                $this->getStorage()->write('import/file', $file);
               } catch (Exception $e) {
                   echo $e->getMessage();
               }
        } else {
            $this->executeAction(new contactsProPluginImportUploadAction());
        }
    }
}