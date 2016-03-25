<?php

class contactsProPluginEventsBlockListAction extends waViewAction
{
    /**
     *
     * @var contactsEvents
     */
    private $events;
    
    public function __construct($params = null) {
        parent::__construct($params);
        $this->events = new contactsEvents($this->getRequest()->get('filter', array()));
    }
    
    public function execute()
    {
        
        if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $name = $this->getRequest()->get('name');
        $offset = $this->getRequest()->get('offset', 0, 'int');
        $limit = $this->getRequest()->get('limit', 10, 'int');
        
        $data = array();
        if ($name == 'users') {
            $data = $this->getUsersData($offset, $limit);
        } else if ($name == 'actions') {
            $data = $this->getActionsData($offset, $limit);
        }
        $this->view->assign(array(
            'data' => $data,
            'name' => $name,
            'limit' => $limit
        ));
    }
    
    public function getUsersData($offset, $limit)
    {
        $users = $this->events->getUsers($offset, $limit);
        $total_count = $this->events->getUsersCount();
        return array(
            'items' => $users,
            'total_count' => $total_count,
            'offset' => $offset
        );
    }
    
    public function getActionsData($offset, $limit)
    {
        $actions = $this->events->getActions($offset, $limit);
        $total_count = $this->events->getActionsCount();
        return array(
            'items' => $actions,
            'total_count' => $total_count,
            'offset' => $offset
        );
    }
}

// EOF