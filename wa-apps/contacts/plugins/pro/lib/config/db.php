<?php

return array(
    'contacts_notes' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'create_contact_id' => array('int', 11, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'text' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'contacts_view' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'type' => array('varchar', 32, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 1),
        'hash' => array('text'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'shared' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'count' => array('int', 11, 'null' => 0, 'default' => '0'),
        'icon' => array('varchar', 255, 'null' => 1),
        'category_id' => array('int', 11, 'null' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'contacts_form' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'locale' => array('varchar', 5),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'contacts_form_params' => array(
        'form_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('form_id', 'name'),
        ),
    ),
    'contacts_event' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 1),
        'location' => array('varchar', 255, 'null' => 1),
        'start_datetime' => array('datetime', 'null' => 1),
        'end_datetime' => array('datetime', 'null' => 1),
        'repeat' => array('enum', "'day','week','month','year'", 'null' => 1),
        'description' => array('text'),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'create_datetime' => array('datetime', 'null' => 1),
        ':keys' => array(
            'PRIMARY' => 'id'
        )
    ),
    'contacts_event_contacts' => array(
        'event_id' => array('int', 11, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('event_id', 'contact_id')
        )
    ),
    'contacts_notification_logs' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'log_app_id' => array('varchar', 32, 'null' => 1),
        'log_action' => array('varchar', 255, 'null' => 1),
        'log_contact_id' => array('int', 11, 'null' => 1),
        'log_subject_contact_id' => array('int', 11, 'null' => 1),
        'datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id'
        )
    ),
    'contacts_notification_birthdays' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'birthday_contact_id' => array('int', 11, 'null' => 1),
        'prior' => array('tinyint', 2, 'null' => 0, 'default' => 0),
        'datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id'
        )
    ),
    'contacts_notification_events' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'event_id' => array('int', 11, 'null' => 0),
        'prior_days' => array('tinyint', 2, 'null' => 0, 'default' => 0),
        'prior_minutes' => array('int', 11, 'null' => 1),
        'datetime' => array('datetime', 'null' => 1),
        ':keys' => array(
            'PRIMARY' =>  'id',
            'event_id' => array('event_id', 'contact_id', 'unique' => 1)
       )
    ),
    'contacts_signup_temp' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'hash' => array('varchar', 100, 'null' => 0, 'default' => ''),
        'data' => array('text', 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'hash' => 'hash',
        ),
    ),
    
    'contacts_tag' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'count' => array('int', 11, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id'
        )
    ),

    'contacts_contact_tags' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'tag_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('contact_id', 'tag_id')
        )
    )
    
);