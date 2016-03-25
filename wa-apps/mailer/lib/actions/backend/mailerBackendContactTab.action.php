<?php

class mailerBackendContactTabAction extends waViewAction
{
    public function execute()
    {
        $contact_id = waRequest::get('id', null, waRequest::TYPE_INT);

        $this->view->assign(array(
            'subscriptions' => $this->getSubscriptions($contact_id),
            'campaigns' => $this->getCampaigns($contact_id),
            'unsubscribe_emails' => $this->getUnsubscribeEmails($contact_id),
            'uniqid' => uniqid()
        ));
    }

    public function getSubscriptions($contact_id)
    {
        $sm = new mailerSubscriberModel();
        return $sm->getByContact($contact_id, true);
    }

    public function getCampaigns($contact_id)
    {
        $statuses = array(
            mailerMessageModel::STATUS_CONTACTS,
            mailerMessageModel::STATUS_SENDING,
            mailerMessageModel::STATUS_SENDING_PAUSED,
            mailerMessageModel::STATUS_SENDING_ERROR,
            mailerMessageModel::STATUS_SENT,
        );
        $mm = new mailerMessageModel();
        $campaigns = $mm->getByRecipient($contact_id, $statuses);

        foreach ($campaigns as &$campaign) {
            $campaign['finished_datetime_formatted'] = $this->formatListDate($campaign['finished_datetime']);
            $campaign['mm_status'] = $campaign['status'];
            $campaign['status'] = $campaign['mml_status'];
            $campaign['error_class'] = $campaign['mml_error_class'];
            $campaign['error'] = $campaign['mml_error'];
        }
        unset($campaign);

        $wa_app_url = wa()->getAppUrl(null, true);
        $attrs = "href='" . $wa_app_url . "#/campaigns/letter/:ID'";
        mailerMessageLogModel::colorizeStatuses($campaigns, 'a', $attrs);
        foreach ($campaigns as &$campaign) {
            $campaign['status_text'] = str_replace(':ID', $campaign['id'], $campaign['status_text']);
        }
        unset($campaign);



        return $campaigns;
    }

    public function formatListDate($dt)
    {
        if (!$dt) {
            return '';
        }
        if(!wa_is_int($dt)) {
            $ts = strtotime($dt);
        } else {
            $ts = $dt;
            $dt = date('Y-m-d H:i:s', $ts);
        }

        if (date('Y-m-d', $ts) == date('Y-m-d')) {
            return _ws('Today').' '.waDateTime::format('time', $dt, wa()->getUser()->getTimezone());
        } else if (date('Y-m-d', $ts) == date('Y-m-d', time() - 3600*24)) {
            return _ws('Yesterday').' '.waDateTime::format('time', $dt, wa()->getUser()->getTimezone());
        } else {
            return waDateTime::format('humandate', $dt, wa()->getUser()->getTimezone());
        }
    }

    public function getUnsubscribeEmails($contact_id)
    {
        $um = new mailerUnsubscriberModel();
        return $um->getByContact($contact_id, true);
    }

}
