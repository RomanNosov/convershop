<?php

/**
 * Storage for unsubscribers.
 * By design, it is possible to unsubscribe from different lists separately (list_id),
 * or from all lists at once (list_id=0).
 * Currently there's no UI for lists management, so app uses list_id=0 only.
 */
class mailerUnsubscriberModel extends waModel
{
    protected $table = 'mailer_unsubscriber';

    public function countListView($search)
    {
        $where_sql = '';
        if ($search) {
            $where_sql = "WHERE u.email LIKE '%".$this->escape($search, 'like')."%'";
        }

        $sql = "SELECT COUNT(*)
                FROM {$this->table} AS u
                {$where_sql}";
        return $this->query($sql)->fetchField();
    }

    public function getListView($search, $start, $limit, $order)
    {
        // Search condition
        $where_sql = '';
        if ($search) {
            $where_sql = "WHERE u.email LIKE '%".$this->escape($search, 'like')."%'";
        }

        // Limit
        $limit_sql = '';
        if ($limit) {
            $limit = (int) $limit;
            if ($start) {
                $limit_sql = "LIMIT {$start}, {$limit}";
            } else {
                $limit_sql = "LIMIT {$limit}";
            }
        }

        // Order
        $possible_order = array(
            'email' => 'u.email',
            'datetime' => 'u.datetime',
            '!email' => 'u.email DESC',
            '!datetime' => 'u.datetime DESC',
        );
        if (!$order || empty($possible_order[$order])) {
            $order = key($possible_order);
        }
        $order_sql = "ORDER BY ".$possible_order[$order];

        $sql = "SELECT u.*
                FROM {$this->table} AS u
                {$where_sql}
                {$order_sql}
                {$limit_sql}";
        return $this->query($sql)->fetchAll('email');
    }

    public function getByContact($contact_id, $with_lists = false) {
        $contact = new waContact($contact_id);
        if (!$contact->exists()) {
            return array();
        }
        $emails = $contact->get('email', 'value');
        $unsubscribe_emails = $this->getByField(array(
            'email' => $emails
        ), true);

        if ($with_lists) {
            $list_ids = array();
            foreach ($unsubscribe_emails as $item) {
                if ($item['list_id'] > 0) {
                    $list_ids[] = $item['list_id'];
                }
            }
            $msl = new mailerSubscribeListModel();
            $lists = $msl->getById($list_ids);
            foreach ($unsubscribe_emails as &$item) {
                if (isset($lists[$item['list_id']])) {
                    $item['list'] = $lists[$item['list_id']];
                }
            }
            unset($item);
        }

        return $unsubscribe_emails;

    }

}

