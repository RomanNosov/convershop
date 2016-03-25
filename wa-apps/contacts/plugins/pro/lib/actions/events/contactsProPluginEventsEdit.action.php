<?php

class contactsProPluginEventsEditAction extends waViewAction
{
    public function execute()
    {
        if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
            throw new waRightsException(_w('Access denied'));
        }

        $id = wa()->getRequest()->request('id', null, waRequest::TYPE_INT);
        $em = new contactsEventModel();
        $event = $em->getEvent($id);

        if ($event['start_datetime']) {
            $event['start_datetime'] = waDateTime::format('fulldatetime', $event['start_datetime']);
        }
        if ($event['end_datetime']) {
            $event['end_datetime'] = waDateTime::format('fulldatetime', $event['end_datetime']);
        }

        $this->view->assign('event', $event);

        $nem = new contactsNotificationEventsModel();
        $this->view->assign('notification', $nem->getNotificationItemByEvent($event['id']));

        $asm = new waAppSettingsModel();
        $this->view->assign('notification_settings', array(
            'last_notification_cli' => $asm->get('contacts', 'last_notification_events_cli'),
            'cli_command' => 'php '.wa()->getConfig()->getRootPath().'/cli.php contacts notification -t event'
        ));

        $this->view->assign('lang', substr(wa()->getUser()->getLocale(), 0, 2));

    }
}