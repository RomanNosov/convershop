<?php

return array(
    'name' => 'Mailer',                 // _wp('Mailer')
    'items' => array(
        'recipients' => array(
            'name' => 'Received campaigns',       // _wp('Received campaigns')
            'multi' => true,
            'join' => array(
                'table' => 'mailer_message_log'
            ),
            'group_by' => 1,
            'items' => array(
                'campaign' => array(
                    'name' => 'Campaign',         // _wp('Campaign')
                    'readonly' => true,
                    'items' => array(
                        ':values' => array(
                            'sql' => "SELECT DISTINCT id AS value, CONCAT(id, ': ', subject) AS name
                        FROM `mailer_message`
                        WHERE finished_datetime IS NOT NULL
                        ORDER BY send_datetime DESC",
                            'where' => ':parent_table.message_id IN (:items)'
                        )
                    )
                ),
                'status' => array(
                    'name' => 'Status',           // _wp('Status')
                    'readonly' => true,
                    'items' => array(
                        array('value' => ':bounced', 'name' => 'Bounced'),      // _wp('Bounced')
                        array('value' => ':unknown', 'name' => 'Unknown'),      // _wp('Unknown')
                        array('value' => ':read', 'name' => 'Read'),                // _wp('Read')
                        array('value' => ':unsubscribed', 'name' => 'Unsubscribed'),    // _wp('Unsubscribed')
                    ),
                    'where' => array(
                        '=' => array(
                            ':bounced' => ':parent_table.status IN (-4,-3,-2,-1)',
                            ':unknown' => ':parent_table.status IN (0,1)',
                            ':read' => ':parent_table.status IN (2,3,4)',
                            ':unsubscribed' => ':parent_table.status IN (5)'
                        )
                    )
                )
            )
        ),
    )
);