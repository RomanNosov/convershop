<?php

class contactsProPluginExportAction extends waViewAction
{
    public function execute()
    {
        $fields = contactsProHelper::getImportExportFields();
        $m = new waContactModel();
        $this->view->assign(array(
            'counters' => array(
                'all' => $this->getRequest()->request('all_count', 0, waRequest::TYPE_INT),
                'selected' => $this->getRequest()->request('selected_count', 0, waRequest::TYPE_INT)
            ),
            'fields' => $fields
        ));
    }
}

// EOF