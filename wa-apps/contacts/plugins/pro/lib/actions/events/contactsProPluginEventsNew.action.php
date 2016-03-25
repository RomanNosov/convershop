<?php

class contactsProPluginEventsNewAction extends waViewAction
{
    /**
     *
     * @var contactsEvents
     */
    private $events;
    
    public function __construct($params = null) {
        parent::__construct($params);
        $this->events = new contactsEvents();
    }
    
    public function execute()
    {
        if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $datetime = $this->getRequest()->request('datetime', null);
        if ($datetime) {
            $this->view->assign(array(
                'log_records_data' => array(
                    'items' => $this->getLogRecords($datetime)
                )
            ));
        }
    }
    
    public function getLogRecords($datetime)
    {
        $filter = array(
            'period' => array(
                date('Y-m-d H:i:s', strtotime($datetime) + 1),
                date('Y-m-d H:i:s')
            )
        );
        $this->events->setFilter($filter);
        return $this->events->getRecords(0, $this->events->getTotalCount());
    }
}

// EOF