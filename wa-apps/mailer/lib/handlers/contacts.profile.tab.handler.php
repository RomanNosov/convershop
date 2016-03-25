<?php

class mailerContactsProfileTabHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $statuses = array(
            mailerMessageModel::STATUS_CONTACTS,
            mailerMessageModel::STATUS_SENDING,
            mailerMessageModel::STATUS_SENDING_PAUSED,
            mailerMessageModel::STATUS_SENDING_ERROR,
            mailerMessageModel::STATUS_SENT,
        );

        $contact_id = $params;
        $mm = new mailerMessageModel();
        $counters = $mm->countByRecipient($contact_id, $statuses, true);

        $um = new mailerUnsubscriberModel();
        $unsubscribe_emails = $um->getByContact($contact_id);

        $old_app = wa()->getApp();
        wa('mailer')->setActive('mailer');

        $title_counters = array(
            0 => 0,
            1 => 0,
            2 => 0
        );
        foreach ($counters as $status => $count) {
            if (in_array($status, array(3, 4))) {
                $title_counters[0] += $count;
            } else if (in_array($status, array(1, 2))) {
                $title_counters[1] += $count;
            } else if (in_array($status, array(-4,-3,-2,-1))) {
                $title_counters[2] += $count;
            }
        }
        ksort($title_counters);

        $all_zeros = true;
        foreach ($title_counters as $count) {
            if ($count > 0) {
                $all_zeros = false;
                break;
            }
        }

        if ($all_zeros) {
            waSystem::setActive($old_app);
            return array();
        }

        $colors = array(
            0 => '#080', 1 => 'black', 2 => 'red'
        );
        foreach ($colors as $k => $color) {
            if (!$title_counters[$k]) {
                unset($title_counters[$k]);
            } else {
                $title_counters[$k] = "<span style='color: {$color} !important;'>{$title_counters[$k]}</span>";
            }
        }

        $title = _w('Campaigns') . ' (' . ($title_counters ? implode('/', $title_counters) : 0) . ')';
        if ($unsubscribe_emails) {
            $title .= ' <i class="icon16 status-red-tiny"></i>';
        }

        $result = array();
        $result[] = array(
            'title' => $title,
            'count' => null,
            'url' => wa()->getAppUrl('mailer').'?module=backend&action=contactTab&id='.$contact_id,
            'html' => ''
        );

        waSystem::setActive($old_app);

        return $result;
    }
}
