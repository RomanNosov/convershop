<?php
return array (
  'contact_info' => 
  array (
    'name' => 'Contact info',
    'items' => 
    array (
      'name' => 
      array (
        'name' => 'Name',
        'title' => false,
        'children' => 1,
        'items' => 
        array (
          'name' => 
          array (
            'field_id' => 'name',
          ),
          'title' => 
          array (
            'field_id' => 'title',
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => 'c.title = \'\'',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => 'c.title != \'\'',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 'AND title LIKE \':term%\'',
                'limit' => 10,
                'sql' => 'SELECT title AS name, COUNT(*) count
                            FROM wa_contact
                            WHERE title != \'\' :autocomplete
                            GROUP BY title
                            ORDER BY count DESC
                            LIMIT :limit',
                'count' => 'SELECT COUNT(DISTINCT title) FROM wa_contact WHERE title != \'\' :autocomplete',
              ),
            ),
          ),
          'firstname' => 
          array (
            'field_id' => 'firstname',
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => 'c.firstname = \'\'',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => 'c.firstname != \'\'',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 'AND firstname LIKE \':term%\'',
                'limit' => 10,
                'sql' => 'SELECT firstname AS name, COUNT(*) count
                            FROM wa_contact
                            WHERE firstname != \'\' :autocomplete
                            GROUP BY firstname
                            ORDER BY count DESC
                            LIMIT :limit',
                'count' => 'SELECT COUNT(DISTINCT firstname) FROM wa_contact WHERE firstname != \'\' :autocomplete',
              ),
            ),
          ),
          'middlename' => 
          array (
            'field_id' => 'middlename',
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => 'c.middlename = \'\'',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => 'c.middlename != \'\'',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 'AND middlename LIKE \':term%\'',
                'limit' => 10,
                'sql' => 'SELECT middlename AS name, COUNT(*) count
                            FROM wa_contact
                            WHERE middlename != \'\' :autocomplete
                            GROUP BY middlename
                            ORDER BY count DESC
                            LIMIT :limit',
                'count' => 'SELECT COUNT(DISTINCT middlename) FROM wa_contact WHERE middlename != \'\' :autocomplete',
              ),
            ),
          ),
          'lastname' => 
          array (
            'field_id' => 'lastname',
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => 'c.lastname = \'\'',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => 'c.lastname != \'\'',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 'AND lastname LIKE \':term%\'',
                'limit' => 10,
                'sql' => 'SELECT lastname AS name, COUNT(*) count
                            FROM wa_contact
                            WHERE lastname != \'\' :autocomplete
                            GROUP BY lastname
                            ORDER BY count DESC
                            LIMIT :limit',
                'count' => 'SELECT COUNT(DISTINCT lastname) FROM wa_contact WHERE lastname != \'\' :autocomplete',
              ),
            ),
          ),
        ),
      ),
      'jobtitle' => 
      array (
        'field_id' => 'jobtitle',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => 'c.jobtitle = \'\'',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => 'c.jobtitle != \'\'',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 'AND jobtitle LIKE \':term%\'',
            'limit' => 10,
            'sql' => 'SELECT jobtitle AS name, COUNT(*) count
                    FROM wa_contact
                    WHERE jobtitle != \'\' :autocomplete
                    GROUP BY jobtitle
                    ORDER BY count DESC
                    LIMIT :limit',
            'count' => 'SELECT COUNT(DISTINCT jobtitle) FROM wa_contact WHERE jobtitle != \'\' :autocomplete',
          ),
        ),
      ),
      'company' => 
      array (
        'field_id' => 'company',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => 'c.company = \'\'',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => 'c.company != \'\'',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 'AND company LIKE \':term%\'',
            'limit' => 10,
            'sql' => 'SELECT company AS name, COUNT(*) count
                    FROM wa_contact
                    WHERE company != \'\' :autocomplete
                    GROUP BY company
                    ORDER BY count DESC
                    LIMIT :limit',
            'count' => 'SELECT COUNT(DISTINCT company) FROM wa_contact WHERE company != \'\' :autocomplete',
          ),
        ),
      ),
      'sex' => 
      array (
        'field_id' => 'sex',
        'readonly' => true,
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => 'c.sex IS NULL',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => 'c.sex IS NOT NULL',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 1,
            'sql' => 'SELECT sex AS name, sex AS value, COUNT(*) AS count
                    FROM wa_contact
                    WHERE sex IS NOT NULL
                    GROUP BY sex',
          ),
        ),
      ),
      'email' => 
      array (
        'field_id' => 'email',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => '(SELECT COUNT(*) FROM wa_contact_emails WHERE contact_id = c.id) = 0',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => '(SELECT COUNT(*) FROM wa_contact_emails WHERE contact_id = c.id) > 0',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 'WHERE email LIKE \'%:term%\'',
            'limit' => 10,
            'sql' => 'SELECT email AS name
                    FROM wa_contact_emails
                    :autocomplete
                    LIMIT :limit',
            'count' => 'SELECT COUNT(DISTINCT email) FROM wa_contact_emails :autocomplete',
          ),
        ),
      ),
      'phone' => 
      array (
        'field_id' => 'phone',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'phone\') = 0',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'phone\') > 0',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 'AND value LIKE \'%:term%\'',
            'limit' => 10,
            'sql' => 'SELECT value AS name, COUNT(*) count
                    FROM wa_contact_data
                    WHERE field = \'phone\' :autocomplete
                    GROUP BY value
                    ORDER BY count DESC
                    LIMIT :limit',
            'count' => 'SELECT COUNT(DISTINCT value) FROM wa_contact_data WHERE field = \'phone\' :autocomplete',
          ),
        ),
      ),
      'im' => 
      array (
        'field_id' => 'im',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => '(SELECT COUNT(*) FROM `wa_contact_data` WHERE contact_id = c.id AND field = \'im\') = 0 ',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => '(SELECT COUNT(*) FROM `wa_contact_data` WHERE contact_id = c.id AND field = \'im\') > 0 ',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 'AND value LIKE \'%:term%\'',
            'limit' => 10,
            'sql' => 'SELECT value, value AS name, COUNT(*) count
                    FROM wa_contact_data
                    WHERE field = \'im\' :autocomplete
                    GROUP BY value
                    ORDER BY count DESC
                    LIMIT :limit',
            'count' => 'SELECT COUNT(DISTINCT value) FROM `wa_contact_data` WHERE field = \'im\'',
          ),
        ),
      ),
      'address' => 
      array (
        'field_id' => 'address',
        'items' => 
        array (
          'street' => 
          array (
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:street\') = 0',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'adress:street\') > 0',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 'AND value LIKE \'%:term%\'',
                'limit' => 10,
                'sql' => 'SELECT value AS name, COUNT(DISTINCT contact_id) count
                            FROM wa_contact_data
                            WHERE field = \'address:street\' :autocomplete
                            GROUP BY value
                            ORDER BY count DESC
                            LIMIT :limit',
                'count' => 'SELECT COUNT(DISTINCT value)
                            FROM wa_contact_data
                            WHERE field = \'address:street\' :autocomplete',
              ),
            ),
          ),
          'city' => 
          array (
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:city\') = 0',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:city\') > 0',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 'AND value LIKE \'%:term%\'',
                'limit' => 10,
                'sql' => 'SELECT value AS name, COUNT(DISTINCT contact_id) count
                        FROM wa_contact_data
                        WHERE field = \'address:city\' :autocomplete
                        GROUP BY value
                        ORDER BY count DESC
                        LIMIT :limit',
                'count' => 'SELECT COUNT(DISTINCT value)
                            FROM wa_contact_data
                            WHERE field = \'address:city\' :autocomplete',
              ),
            ),
          ),
          'region' => 
          array (
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:region\') = 0',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:region\') > 0',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 1,
                'class' => 'contactsSearchRegionValues',
              ),
            ),
          ),
          'country' => 
          array (
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:country\') = 0',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:country\') > 0',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 1,
                'class' => 'contactsSearchCountryValues',
              ),
            ),
          ),
          'zip' => 
          array (
            'items' => 
            array (
              'blank' => 
              array (
                'name' => 'Empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:zip\') = 0',
              ),
              'not_blank' => 
              array (
                'name' => 'Not empty',
                'where' => '(SELECT COUNT(*) FROM wa_contact_data WHERE contact_id = c.id AND field = \'address:zip\') > 0',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'autocomplete' => 'AND value LIKE \':term%\'',
                'limit' => 10,
                'sql' => 'SELECT value AS name, COUNT(DISTINCT contact_id) count
                        FROM wa_contact_data
                        WHERE field = \'address:zip\'
                        GROUP BY value
                        ORDER BY count DESC
                        LIMIT :limit',
                'count' => 'SELECT COUNT(DISTINCT value)
                            FROM wa_contact_data
                            WHERE field = \'address:zip\' :autocomplete',
              ),
            ),
          ),
        ),
      ),
      'url' => 
      array (
        'field_id' => 'url',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => '(SELECT COUNT(*) FROM `wa_contact_data` WHERE contact_id = c.id AND field = \'url\') = 0 ',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => '(SELECT COUNT(*) FROM `wa_contact_data` WHERE contact_id = c.id AND field = \'url\') > 0 ',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 'AND value LIKE \'%:term%\'',
            'limit' => 10,
            'sql' => 'SELECT value, value AS name, COUNT(*) count
                    FROM wa_contact_data
                    WHERE field = \'url\' :autocomplete
                    GROUP BY value
                    ORDER BY count DESC
                    LIMIT :limit',
            'count' => 'SELECT COUNT(DISTINCT value) FROM `wa_contact_data` WHERE field = \'url\'',
          ),
        ),
      ),
      'birthday' => 
      array (
        'field_id' => 'birthday',
        'readonly' => 1,
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => 'c.birth_day IS NULL AND c.birth_month IS NULL AND c.birth_year IS NULL',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => 'c.birth_day IS NOT NULL OR c.birth_month IS NOT NULL OR c.birth_year IS NOT NULL',
          ),
          ':sep' => 
          array (
          ),
          'today' => 
          array (
            'name' => 'today',
            'where' => 'c.birth_day = DAY(NOW()) AND c.birth_month = MONTH(NOW())',
          ),
          'today_or_tomorrow' => 
          array (
            'name' => 'today or tomorrow',
            'where' => '(c.birth_day = DAY(NOW()) OR c.birth_day = DAY(DATE_ADD(NOW(), INTERVAL 1 DAY))) AND c.birth_month = MONTH(NOW())',
          ),
          'week' => 
          array (
            'name' => 'in the nearest week',
            'where' => 'c.birth_day IS NOT NULL AND c.birth_month IS NOT NULL AND
                    STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') >= NOW() AND (
                        STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') <= DATE_ADD(NOW(), INTERVAL 7 DAY) OR
                        STR_TO_DATE(CONCAT(YEAR(DATE_ADD(NOW(), INTERVAL 1 YEAR)), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') <= DATE_ADD(NOW(), INTERVAL 7 DAY))',
          ),
          'month' => 
          array (
            'name' => 'in the nearest month',
            'where' => 'c.birth_day IS NOT NULL AND c.birth_month IS NOT NULL AND
                    STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') >= NOW() AND (
                        STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') <= DATE_ADD(NOW(), INTERVAL 30 DAY) OR
                        STR_TO_DATE(CONCAT(YEAR(DATE_ADD(NOW(), INTERVAL 1 YEAR)), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') <= DATE_ADD(NOW(), INTERVAL 30 DAY))',
          ),
          ':period' => 
          array (
            'name' => 'select a period',
            'where' => 
            array (
              ':between' => 'c.birth_day IS NOT NULL AND c.birth_month IS NOT NULL AND
                            STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') >= \':0\' AND
                                STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') <= \':1\'',
              ':gt' => 'c.birth_day IS NOT NULL AND c.birth_month IS NOT NULL AND
                            STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') >= \':?\'',
              ':lt' => 'c.birth_day IS NOT NULL AND c.birth_month IS NOT NULL AND
                            STR_TO_DATE(CONCAT(YEAR(NOW()), \'-\', c.birth_month, \'-\', c.birth_day), \'%Y-%m-%d\') <= \':?\'',
            ),
          ),
        ),
      ),
      'locale' => 
      array (
        'field_id' => 'locale',
        'readonly' => true,
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => 'c.locale IS NULL OR c.locale = \'\'',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => 'c.locale IS NOT NULL AND c.locale != \'\'',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 1,
            'sql' => 'SELECT locale AS name, locale AS value, COUNT(*) AS count
                            FROM wa_contact
                            WHERE locale IS NOT NULL AND locale != \'\'
                            GROUP BY locale',
          ),
        ),
      ),
      'timezone' => 
      array (
        'field_id' => 'timezone',
        'readonly' => true,
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => 'c.timezone IS NULL OR c.timezone = \'\'',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => 'c.timezone IS NOT NULL AND c.timezone != \'\'',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'sql' => 'SELECT timezone AS name, timezone AS value, COUNT(*) AS count
                                FROM wa_contact
                                WHERE timezone IS NOT NULL AND timezone != \'\'
                                GROUP BY timezone',
          ),
        ),
      ),
      'socialnetwork' => 
      array (
        'field_id' => 'socialnetwork',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => '(SELECT COUNT(*) FROM `wa_contact_data` WHERE contact_id = c.id AND field = \'socialnetwork\') = 0 ',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => '(SELECT COUNT(*) FROM `wa_contact_data` WHERE contact_id = c.id AND field = \'socialnetwork\') > 0 ',
          ),
          ':sep' => 
          array (
          ),
          ':values' => 
          array (
            'autocomplete' => 'AND value LIKE \'%:term%\'',
            'limit' => 10,
            'sql' => 'SELECT value, value AS name, COUNT(*) count
                    FROM wa_contact_data
                    WHERE field = \'socialnetwork\' :autocomplete
                    GROUP BY value
                    ORDER BY count DESC
                    LIMIT :limit',
            'count' => 'SELECT COUNT(DISTINCT value) FROM `wa_contact_data` WHERE field = \'socialnetwork\'',
          ),
        ),
      ),
      'about' => 
      array (
        'field_id' => 'about',
        'items' => 
        array (
          'blank' => 
          array (
            'name' => 'Empty',
            'where' => 'c.about IS NULL OR c.about = \'\'',
          ),
          'not_blank' => 
          array (
            'name' => 'Not empty',
            'where' => 'c.about IS NOT NULL AND c.about != \'\'',
          ),
          ':values' => 
          array (
            'autocomplete' => 'WHERE about LIKE \'%:term%\'',
            'limit' => 10,
            'sql' => 'SELECT about AS name
                            FROM wa_contact
                            :autocomplete
                            LIMIT :limit',
          ),
        ),
      ),
      'contact_type' => 
      array (
        'name' => 'Contact type',
        'readonly' => true,
        'items' => 
        array (
          'person' => 
          array (
            'name' => 'Person',
            'where' => 'c.is_company = 0',
          ),
          'company' => 
          array (
            'name' => 'Company',
            'where' => 'c.is_company = 1',
          ),
        ),
      ),
      'creating' => 
      array (
        'name' => 'Creating method and date',
        'multi' => true,
        'items' => 
        array (
          'method' => 
          array (
            'name' => 'Method',
            'readonly' => true,
            'items' => 
            array (
              ':values' => 
              array (
                'class' => 'contactsSearchCreateMethodValues',
              ),
            ),
          ),
          'date' => 
          array (
            'name' => 'Date',
            'items' => 
            array (
              ':period' => 
              array (
                'name' => 'select a period',
                'where' => 
                array (
                  ':between' => 'c.create_datetime IS NOT NULL AND DATE(c.create_datetime) >= \':0\' AND DATE(c.create_datetime) <= \':1\'',
                  ':gt' => 'c.create_datetime IS NOT NULL AND DATE(c.create_datetime) >= \':?\'',
                  ':lt' => 'c.create_datetime IS NOT NULL AND DATE(c.create_datetime) <= \':?\'',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  'activity' => 
  array (
    'name' => 'Activity',
    'items' => 
    array (
      'action_by' => 
      array (
        'name' => 'Performed an action',
        'multi' => true,
        'join' => 
        array (
          'table' => 'wa_log',
        ),
        'items' => 
        array (
          'action' => 
          array (
            'name' => 'Action',
            'readonly' => true,
            'skip_first_space' => true,
            'items' => 
            array (
              'any_action' => 
              array (
                'name' => 'any action',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'class' => 'contactsSearchActivityActionValues',
              ),
            ),
          ),
          'period' => 
          array (
            'name' => 'Period',
            'items' => 
            array (
              ':period' => 
              array (
                'name' => '',
                'where' => 
                array (
                  ':between' => 'DATE(:parent_table.datetime) >= \':0\' AND DATE(:parent_table.datetime) <= \':1\'',
                  ':gt' => 'DATE(:parent_table.datetime) >= \':?\'',
                  ':lt' => 'DATE(:parent_table.datetime) <= \':?\'',
                ),
              ),
            ),
          ),
        ),
      ),
      'action_to' => 
      array (
        'name' => 'Applied an action to',
        'multi' => true,
        'join' => 
        array (
          'table' => 'wa_log',
          'on' => ':table.subject_contact_id = c.id',
        ),
        'items' => 
        array (
          'action' => 
          array (
            'name' => 'Action',
            'readonly' => true,
            'skip_first_space' => true,
            'items' => 
            array (
              'any_action' => 
              array (
                'name' => 'any action',
              ),
              ':sep' => 
              array (
              ),
              ':values' => 
              array (
                'class' => 'contactsSearchActivityActionValues',
                'options' => 
                array (
                  'subject' => true,
                ),
              ),
            ),
          ),
          'period' => 
          array (
            'name' => 'Period',
            'items' => 
            array (
              ':period' => 
              array (
                'name' => '',
                'where' => 
                array (
                  ':between' => 'DATE(:parent_table.datetime) >= \':0\' AND DATE(:parent_table.datetime) <= \':1\'',
                  ':gt' => 'DATE(:parent_table.datetime) >= \':?\'',
                  ':lt' => 'DATE(:parent_table.datetime) <= \':?\'',
                ),
              ),
            ),
          ),
        ),
      ),
      'event_participants' => 
      array (
        'name' => 'Event participants',
        'multi' => true,
        'joins' => 
        array (
          ':tbl_event_contacts' => 
          array (
            'table' => 'contacts_event_contacts',
          ),
          ':tbl_event' => 
          array (
            'table' => 'contacts_event',
            'on' => ':tbl_event_contacts.event_id = :table.id',
          ),
        ),
        'items' => 
        array (
          'event' => 
          array (
            'name' => 'Event',
            'items' => 
            array (
              ':values' => 
              array (
                'autocomplete' => 'WHERE name LIKE \'%:term%\'',
                'sql' => 'SELECT DISTINCT ce.id AS value, ce.name FROM contacts_event ce
                                JOIN contacts_event_contacts cec ON ce.id = cec.event_id
                                :autocomplete',
                'where' => 
                array (
                  '=' => ':tbl_event.id = :value',
                  '*=' => ':tbl_event.name LIKE \'%:value%\'',
                ),
              ),
            ),
          ),
          'period' => 
          array (
            'name' => 'Period',
            'items' => 
            array (
              ':period' => 
              array (
                'name' => 'select a period',
                'where' => 
                array (
                  ':between' => '(:tbl_event.end_datetime IS NULL AND DATE(:tbl_event.start_datetime) >= \':0\' AND DATE(:tbl_event.start_datetime) <= \':1\') OR (:tbl_event.end_datetime IS NOT NULL AND NOT (DATE(:tbl_event.end_datetime) < \':0\' and DATE(:tbl_event.start_datetime) > \':1\') )',
                  ':gt' => '(:tbl_event.end_datetime IS NULL AND DATE(:tbl_event.start_datetime) >= \':?\') OR (:tbl_event.end_datetime IS NOT NULL AND NOT DATE(:tbl_event.end_datetime) < \':?\')',
                  ':lt' => '(:tbl_event.end_datetime IS NULL AND DATE(:tbl_event.start_datetime) <= \':?\') OR (:tbl_event.end_datetime IS NOT NULL AND NOT DATE(:tbl_event.start_datetime) > \':?\')',
                ),
              ),
            ),
          ),
        ),
      ),
      'access' => 
      array (
        'name' => 'Access',
        'readonly' => true,
        'items' => 
        array (
          'forbidden' => 
          array (
            'name' => 'Forbidden',
            'where' => 'c.is_user=-1',
          ),
          'customer_portal' => 
          array (
            'name' => 'Customer portal only',
            'where' => 'c.is_user=0',
          ),
          'backend' => 
          array (
            'name' => 'Backend',
            'where' => 'c.is_user=1',
          ),
        ),
      ),
      'status' => 
      array (
        'name' => 'Status',
        'readonly' => true,
        'items' => 
        array (
          'online' => 
          array (
            'name' => 'Online',
            'join' => 
            array (
              'table' => 'wa_login_log',
              'on' => 'c.id = :table.contact_id',
              'where' => 'c.last_datetime IS NOT NULL AND
                    UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(c.last_datetime) < \'300\' AND :table.datetime_out IS NULL',
            ),
            'group_by' => 1,
          ),
          'offline' => 
          array (
            'name' => 'Offline',
            'where' => 'c.last_datetime IS NULL OR
                    UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(c.last_datetime) >= \'300\'',
          ),
          'never_login' => 
          array (
            'name' => 'Never logged in',
            'where' => 'c.last_datetime IS NULL',
          ),
        ),
      ),
    ),
  ),
  'mailer' => 
  array (
    'name' => 'Mailer',
    'items' => 
    array (
      'recipients' => 
      array (
        'name' => 'Received campaigns',
        'multi' => true,
        'join' => 
        array (
          'table' => 'mailer_message_log',
        ),
        'group_by' => 1,
        'items' => 
        array (
          'campaign' => 
          array (
            'name' => 'Campaign',
            'readonly' => true,
            'items' => 
            array (
              ':values' => 
              array (
                'sql' => 'SELECT DISTINCT id AS value, CONCAT(id, \': \', subject) AS name
                        FROM `mailer_message`
                        WHERE finished_datetime IS NOT NULL
                        ORDER BY send_datetime DESC',
                'where' => ':parent_table.message_id IN (:items)',
              ),
            ),
          ),
          'status' => 
          array (
            'name' => 'Status',
            'readonly' => true,
            'items' => 
            array (
              0 => 
              array (
                'value' => ':bounced',
                'name' => 'Bounced',
              ),
              1 => 
              array (
                'value' => ':unknown',
                'name' => 'Unknown',
              ),
              2 => 
              array (
                'value' => ':read',
                'name' => 'Read',
              ),
              3 => 
              array (
                'value' => ':unsubscribed',
                'name' => 'Unsubscribed',
              ),
            ),
            'where' => 
            array (
              '=' => 
              array (
                ':bounced' => ':parent_table.status IN (-4,-3,-2,-1)',
                ':unknown' => ':parent_table.status IN (0,1)',
                ':read' => ':parent_table.status IN (2,3,4)',
                ':unsubscribed' => ':parent_table.status IN (5)',
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  'shop' => 
  array (
    'name' => 'Shop',
    'joins' => 
    array (
      ':tbl_customer' => 
      array (
        'table' => 'shop_customer',
      ),
      ':tbl_order' => 
      array (
        'table' => 'shop_order',
      ),
    ),
    'items' => 
    array (
      'placed_orders' => 
      array (
        'name' => 'Placed orders',
        'multi' => true,
        'items' => 
        array (
          'period' => 
          array (
            'name' => 'Period',
            'items' => 
            array (
              ':period' => 
              array (
                'name' => 'select a period',
                'where' => 
                array (
                  ':between' => 'DATE(:tbl_order.create_datetime) >= \':0\' AND DATE(:tbl_order.create_datetime) <= \':1\'',
                  ':gt' => 'DATE(:tbl_order.create_datetime) >= \':?\'',
                  ':lt' => 'DATE(:tbl_order.create_datetime) <= \':?\'',
                ),
              ),
            ),
          ),
          'status' => 
          array (
            'name' => 'Current state',
            'readonly' => true,
            'items' => 
            array (
              ':values' => 
              array (
                'class' => 'contactsSearchShopOrderStatesValues',
              ),
            ),
          ),
          'payment_method' => 
          array (
            'name' => 'Payment method',
            'readonly' => true,
            'items' => 
            array (
              ':values' => 
              array (
                'join' => 
                array (
                  'table' => 'shop_order_params',
                  'on' => ':table.order_id = :tbl_order.id',
                ),
                'class' => 'contactsSearchShopSPMethodsValues',
                'options' => 
                array (
                  'type' => 'payment',
                ),
              ),
            ),
          ),
          'shipment_method' => 
          array (
            'name' => 'Shipment method',
            'readonly' => true,
            'items' => 
            array (
              ':values' => 
              array (
                'join' => 
                array (
                  'table' => 'shop_order_params',
                  'on' => ':table.order_id = :tbl_order.id',
                ),
                'class' => 'contactsSearchShopSPMethodsValues',
                'options' => 
                array (
                  'type' => 'shipping',
                ),
              ),
            ),
          ),
        ),
      ),
      'purchased_product' => 
      array (
        'name' => 'Purchased product',
        'multi' => true,
        'items' => 
        array (
          'period' => 
          array (
            'name' => 'Period',
            'items' => 
            array (
              ':period' => 
              array (
                'name' => 'select a period',
                'where' => 
                array (
                  ':between' => 'DATE(:tbl_order.create_datetime) >= \':0\' AND DATE(:tbl_order.create_datetime) <= \':1\'',
                  ':gt' => 'DATE(:tbl_order.create_datetime) >= \':?\'',
                  ':lt' => 'DATE(:tbl_order.create_datetime) <= \':?\'',
                ),
              ),
            ),
          ),
          'product' => 
          array (
            'name' => 'Product',
            'items' => 
            array (
              ':values' => 
              array (
                'autocomplete' => 1,
                'class' => 'contactsSearchShopProductValues',
              ),
            ),
          ),
          'status' => 
          array (
            'name' => 'Current state',
            'readonly' => true,
            'items' => 
            array (
              ':values' => 
              array (
                'class' => 'contactsSearchShopOrderStatesValues',
              ),
            ),
          ),
        ),
      ),
      'customers' => 
      array (
        'name' => 'Customers',
        'multi' => true,
        'items' => 
        array (
          'total_spent' => 
          array (
            'name' => 'Total spent',
            ':class' => 'contactsSearchShopTotalSpentItem',
          ),
          'payed_orders' => 
          array (
            'name' => 'Count only paid orders',
            'checkbox' => true,
            'where' => 
            array (
              '=' => 
              array (
                1 => ':tbl_order.paid_date IS NOT NULL',
              ),
            ),
          ),
          'number_of_orders' => 
          array (
            'name' => 'Number of orders',
            ':class' => 'contactsSearchShopNumberOfOrdersItem',
          ),
          'last_order_datetime' => 
          array (
            'name' => 'Last order',
            ':class' => 'contactsSearchShopOrderDatetimeItem',
            'options' => 
            array (
              'type' => 'last',
            ),
          ),
          'first_order_datetime' => 
          array (
            'name' => 'First order',
            ':class' => 'contactsSearchShopOrderDatetimeItem',
            'options' => 
            array (
              'type' => 'first',
            ),
          ),
          'coupon' => 
          array (
            'name' => 'Discount',
            ':class' => 'contactsSearchShopCouponItem',
          ),
          'referer' => 
          array (
            'name' => 'Referer',
            ':class' => 'contactsSearchShopRefererItem',
          ),
          'storefront' => 
          array (
            'name' => 'Storefront',
            ':class' => 'contactsSearchShopStorefrontItem',
          ),
          'utm_campaign' => 
          array (
            'name' => 'UTM campaign',
            ':class' => 'contactsSearchShopUtmCampaignItem',
          ),
        ),
      ),
    ),
  ),
);
