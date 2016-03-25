<?php

class contactsProPluginFilterSaveController extends waJsonController
{
    public function execute()
    {
        $id = $this->getRequest()->request('id', 0, 'int');
        $data = $this->getData();
        $view_model = new contactsViewModel();
        if ($id) {
            if ($data) {
                $view_model->updateById($id, $data);
            }
        } else {
            $data['type'] = 'search';
            $id = $view_model->add($data);
        }
        $this->response = $view_model->getById($id);
    }
    
    public function getData()
    {
        $q = $this->getRequest()->request('hash', null);
        return array(
            'name' => $this->getRequest()->request('name', '', waRequest::TYPE_STRING_TRIM),
            'icon' => $this->getRequest()->request('icon', null),
            'count' => $this->getRequest()->request('count', 0, 'int'),
            'shared' => $this->getRequest()->request('shared', 0, 'int'),
            'hash' => "/contacts/prosearch/{$q}"
        );
    }
}