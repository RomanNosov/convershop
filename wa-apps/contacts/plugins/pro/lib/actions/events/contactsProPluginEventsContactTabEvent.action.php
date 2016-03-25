<?php

class contactsProPluginEventsContactTabEventAction extends contactsProPluginEventsAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
    }

    public function getCategory()
    {
        return 'event';
    }

    public function execute() {
        parent::execute();
        $filter = $this->getFilter();
        $this->view->assign(array(
            'contact_id' => $filter['contact_id'],
            'action_url' => wa()->getAppUrl('contacts') . '?plugin=pro&module=eventsContactTabEvent'
        ));
    }

}