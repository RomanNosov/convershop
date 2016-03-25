<?php

class contactsProPluginViewSaveController extends waJsonController
{
    public function execute()
    {
        $id = $this->getRequest()->request('id', 0, 'int');
        $data = $this->getData();
        if ($id) {
            $this->update($id, $data);
        } else {
            $this->addToView();
        }
    }

    public function getData()
    {
        return array(
            'name' => $this->getRequest()->request('name', '', waRequest::TYPE_STRING_TRIM),
            'icon' => $this->getRequest()->request('icon', null),
            'shared' => $this->getRequest()->request('shared', 0, waRequest::TYPE_INT)
        );
    }

    public function update($id, $data)
    {
        $view_model = new contactsViewModel();
        $view_model->update($id, $data);
        $view = $view_model->get($id);
        $this->response = $view;
    }

    public function addToView()
    {
        $contacts = waRequest::post('contacts', array(), 'array_int');
        $views = waRequest::post('views', array(), 'array_int');
        $views_count = count($views);
        $name = waRequest::post('name');
        $icon = waRequest::post('icon');

        if ($name) {
            $view_model = new contactsViewModel();
            $id = $view_model->add('category', null, $name, $contacts);
            $view_model->update($id, array('icon' => $icon));
            $views_count += 1;
            $this->response['view'] = array(
                'id' => $id,
                'name' => $name,
                'icon' => $icon,
                'count' => count($contacts)
            );
            $views[] = $id;
        }

        $m = new contactsViewModel();
        $m->addTo($views, $contacts);

        $this->response['message'] = _wp("%d contact has been added", "%d contacts have been added", count($contacts));
        $this->response['message'] .= ' ';
        $this->response['message'] .= _wp("to %d list", "to %d lists", $views_count);

        $counters = array();
        if ($views) {
            $m->updateCount($views);
            $counters = array_values($m->getViews($views));
        }

        $this->response['counters'] = $counters;
    }

}