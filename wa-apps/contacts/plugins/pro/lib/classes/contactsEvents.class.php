<?php

class contactsEvents {

    /**
     *
     * @var waModel
     */
    protected $model;

    /**
     *
     * @var contactsEventModel
     */
    protected $event_model;


    protected $filter;
    protected $spec_filters;
    protected $category;
    protected $filter_as_assoc = array();
    protected $contacts = array();

    protected $fields_map = array(
        'log' => array(
            'contact_id' => 'contact_id',
            'datetime' => 'datetime',
            'type' => 'action',
            'app_id' => 'app_id',
            'subject_contact_id' => 'subject_contact_id'
        ),
        'birthday' => array(
            'contact_id' => 'l.id',
            'datetime' => "STR_TO_DATE(CONCAT(YEAR(NOW()), '-', l.birth_month, '-', l.birth_day), '%Y-%m-%d')",
            'type' => "'l.birthday'",
            'month' => 'l.birth_month',
            'name' => 'l.name'
        ),
        'event' => array(
            'contact_id' => 'ec.contact_id',
            'datetime' => 'l.start_datetime',
            'type' => 'l.name',
            'event_id' => 'l.id',
            'start_datetime' => 'l.start_datetime',
            'end_datetime' => 'l.end_datetime',
            'month' => 'MONTH(l.start_datetime)',
            'repeat' => 'l.repeat'
        )
    );

    protected $tables_map = array(
        'log' => 'wa_log',
        'event' => 'contacts_event',
        'birthday' => 'wa_contact'
    );

    protected $joins_map = array(
        'event' => array(
            'ec' => array(
                'left' => 1,
                'table' => 'contacts_event_contacts',
                'on' => 'l.id = ec.event_id'
            )
        )
    );

    protected $filters_map = array(
        'birthday' => 'l.birth_month IS NOT NULL AND l.birth_day IS NOT NULL'
    );

    protected static $categories = array('log', 'event', 'birthday');

    public function __construct($filter = array(), $category = 'log') {
        $this->model = new waModel();

        if (in_array($category, self::$categories)) {
            $this->category = $category;
        } else {
            $this->category = 'log';
        }

        // leap year
        if (!date('L', time())) {
            $this->fields_map['birthday']['datetime'] =
                "STR_TO_DATE(CONCAT(YEAR(NOW()), '-', IF(l.birth_month = 2 AND l.birth_day = 29, 3, l.birth_month), '-', IF(l.birth_month = 2 AND l.birth_day = 29, 1, l.birth_day)), '%Y-%m-%d')";
        }

        $this->filter_as_assoc = $filter;
        $this->filter = array();
        $this->parseFilter($filter);
    }

    protected function parseFilter($filter = array())
    {
        // prepare for parsing
        if (empty($filter['period'])) {
            $filter['period'] = 'lifetime';
        }
        $parsed_filter = array();
        foreach ($filter as $k => $v) {
            if (in_array($k, array('contact_id', 'type', 'app_id'))) {
                $parsed_filter[] = "{$k}={$v}";
            } else if ($k === 'period' && (is_array($v) || in_array($v, array('month', 'year', 'week', 'lifetime', 'today')))) {
                if (is_string($v)) {
                    $parsed_filter[] = '_period_='.$v;
                } else {
                    $parsed_filter[] = array(
                        '_period_' => $v
                    );
                }
            }
        }
        // parsing itself
        foreach ($parsed_filter as $k => &$f) {
            if (!$f) {
                unset($parsed_filter[$k]);
            }
            if (is_string($f)) {
                $f = explode('=', $f);
                if (empty($f[1])) {
                    unset($parsed_filter[$k]);
                }
            } else {
                $f = array(key($f), reset($f));
            }

            $timezone_offset = 0;
            $user = wa()->getUser();
            if ($user['timezone']) {
                $user_offset = wao(new DateTime("now", new DateTimeZone($user['timezone'])))->getOffset();
                $server_offset = wao(new DateTime("now", new DateTimeZone(date_default_timezone_get())))->getOffset();
                $timezone_offset = $user_offset - $server_offset;
            }
            if ($f[0] === '_period_') {
                $time = time();
                $this->spec_filters[$f[0]] = $f[1];
                $val = '';
                $fld = 'datetime';
                if ($this->category === 'log') {
                    $datetime_field = $this->getField($fld);
                    if ($f[1] === 'month') {
                        $val = "{$datetime_field} >= '".date('Y-m-d 00:00:00', $time - 30*24*60*60)."'";
                    } else if ($f[1] === 'year') {
                        $val = "{$datetime_field} >= '".date('Y-m-d 00:00:00', $time - 365*24*60*60)."'";
                    } else if ($f[1] === 'week') {
                        $val = "{$datetime_field} >= '".date('Y-m-d 00:00:00', $time - 7*24*60*60)."'";
                    } else if ($f[1] === 'today') {
//                        if ($timezone_offset) {
//                            $val = "DATE_ADD({$datetime_field}, INTERVAL {$timezone_offset} SECOND) >= '" . date('Y-m-d 00:00:00', $time) . "'";
//                        } else {
//
//                        }
                        $val = "{$datetime_field} >= '".date('Y-m-d 00:00:00', $time)."'";
                    } else if  (is_array($f[1])) {
                        $start = ifset($f[1][0], date('Y-m-d 00:00:00'));
                        $end = ifset($f[1][1], date('Y-m-d 23:59:59'));
//                        if ($timezone_offset) {
//                            $val = "DATE_ADD({$datetime_field}, INTERVAL {$timezone_offset} SECOND) >= '{$start}' AND
//                            DATE_ADD({$datetime_field}, INTERVAL {$timezone_offset} SECOND) <= '{$end}'";
//                        } else {
//
//                        }
                        $val = "{$datetime_field} >= '{$start}' AND {$datetime_field} <= '{$end}'";
                    } else if (isset($parsed_filter[$k])) {
                        unset($parsed_filter[$k]);
                    }
                } else if ($this->category === 'birthday') {
                    $datetime_field = $this->getField($fld);
                    if ($f[1] === 'month') {
                        $val = "{$datetime_field} >= '".date('Y-m-d 00:00:00', $time - 30*24*60*60)."' AND {$datetime_field} <= '".date('Y-m-d 00:00:00', $time)."'";
                    } else if ($f[1] === 'year') {
                        $val = "{$datetime_field} >= '".date('Y-m-d 00:00:00', $time - 365*24*60*60)."' AND {$datetime_field} <= '".date('Y-m-d 00:00:00', $time)."'";
                    } else if ($f[1] === 'today') {
                        $val = "{$datetime_field} = '".date('Y-m-d')."'";
                    } else if  (is_array($f[1])) {
                        $start = ifset($f[1][0], date('Y-m-d 00:00:00'));
                        $end = ifset($f[1][1], date('Y-m-d 23:59:59'));
                        $val = "{$datetime_field} >= '{$start}' AND {$datetime_field} <= '{$end}'";
                    } else if (isset($parsed_filter[$k])) {
                        unset($parsed_filter[$k]);
                    }
                } else {            // event
                    $start_datetime_field = $this->getField('start_datetime');
                    $end_datetime_field = $this->getField('end_datetime');
                    $val = array();

                    if ($f[1] === 'month') {
                        $_30_24 = 30*24*60*60;
                        $s = "'" . date('Y-m-d H:00:00', $time) . "'";
                        $e = "'" . date('Y-m-d H:00:00', $time + $_30_24) . "'";
                        $val[] = "({$start_datetime_field} >= {$s} AND {$start_datetime_field} < {$e} AND {$end_datetime_field} IS NULL)";
                        $val[] = "({$start_datetime_field} < {$e} AND {$end_datetime_field} >= {$s} AND {$start_datetime_field} <= {$end_datetime_field})";
                    } else if ($f[1] === 'year') {
                        $_365_24 = 365*24*60*60;
                        $s = "'" . date('Y-m-d H:00:00', $time) . "'";
                        $e = "'" . date('Y-m-d H:00:00', $time + $_365_24) . "'";
                        $val[] = "({$start_datetime_field} >= {$s} AND {$start_datetime_field} < {$e} AND {$end_datetime_field} IS NULL)";
                        $val[] = "({$start_datetime_field} < {$e} AND {$end_datetime_field} >= {$s} AND {$start_datetime_field} <= {$end_datetime_field})";
                    } else if ($f[1] === 'today') {
                        $_24 = 24*60*60;
                        $s = "'" . date('Y-m-d H:00:00', $time) . "'";
                        $e = "'" . date('Y-m-d H:00:00', $time + $_24) . "'";
                        $val[] = "({$start_datetime_field} >= {$s} AND {$start_datetime_field} < {$e} AND {$end_datetime_field} IS NULL)";
                        $val[] = "({$start_datetime_field} < {$e} AND {$end_datetime_field} >= {$s} AND {$start_datetime_field} <= {$end_datetime_field})";
                    } else if  (is_array($f[1])) {
                        $s = "'" . ifset($f[1][0], date('Y-m-d 00:00:00')) . "'";
                        $e = "'" . ifset($f[1][1], date('Y-m-d 23:59:59')) . "'";
                        $val[] = "({$start_datetime_field} >= {$s} AND {$start_datetime_field} <= {$e} AND {$end_datetime_field} IS NULL)";
                        $val[] = "({$start_datetime_field} <= {$e} AND {$end_datetime_field} >= {$s} AND {$start_datetime_field} <= {$end_datetime_field})";
                    } else if (isset($parsed_filter[$k])) {
                        unset($parsed_filter[$k]);
                    }
                    $val[] = "(l.repeat IS NOT NULL)";
                    $val = '(' . implode(' OR ', $val) . ')';
                }
                $f = $val;
            } else if ($f[0] === 'type') {
                $type_field = $this->getField('type');
                $f[1] = $this->model->escape($f[1]);
                $f = "{$type_field} = '{$f[1]}'";
            } else if ($f[0] === 'app_id' && $this->category === 'log') {
                if ($f[1] === null) {
                    $f = "l.app_id IS NULL OR l.app_id = ''";
                } else {
                    $f[1] = $this->model->escape($f[1]);
                    $f = "l.app_id = '{$f[1]}'";
                }
            } else if ($f[0] === 'contact_id' && $this->category === 'event') {
                $f[1] = $this->model->escape($f[1]);
                $f = "(ec.contact_id = '{$f[1]}' OR l.contact_id = '{$f[1]}')";
            } else {
                $field = $this->getField($f[0]);
                if ($field !== null) {
                    $f[1] = trim($f[1], '\'"');
                    $f[1] = "'".$this->model->escape($f[1])."'";
                    $f[0] = $field;
                    $f = implode('=', $f);
                }
            }
        }
        unset($f);
        $this->filter = array_merge($this->filter, $parsed_filter);
        if (empty($this->filter)) {
            $this->filter[] = "1";
        } else {
            $k = array_search("1", $this->filter);
            if ($k !== false) {
                unset($this->filter[$k]);
            }
        }
        return $parsed_filter;
    }

    protected function getFilter($sql = true) {
        if (!$sql) {
            return $this->filter;
        } else {
            return implode(" AND ", $this->filter);
        }
    }

    public function addFilter($filter = array())
    {
        if ($filter) {
            $this->parseFilter($filter);
        }
    }

    public function clearFilter()
    {
        $this->filter = array();
    }

    public function setFilter($filter = array()) {
        $this->clearFilter();
        $this->addFilter($filter);
    }

    public function getTotalCount()
    {
        $where = $this->getFilter();
        $sql = $this->getSQL('COUNT(*)');
        //waLog::log("getTotalCount-" . date('Y-m-d H:i:s') . "{$sql} AND ({$where})", 'contacts_events.log');
        return $this->model->query("{$sql} AND ({$where})")->fetchField();
    }

    /**
     *
     * @param type $offset
     * @param type $limit
     * @param type $own_where TODO: quick solution, delete it in future
     * @return type
     */
    public function getRecords($offset = 0, $limit = 30, $own_where = '')
    {
        $where = $this->getFilter();
        $sql = $this->getSQL();

        $order = 'ORDER BY datetime DESC';
        if ($this->category === 'birthday') {
            $order .= ',name';
        }
        $sql = "{$sql} AND ({$where})
        {$order}
        LIMIT {$offset}, {$limit}";

        //waLog::log("getRecords-" . date("Y-m-d H:i:s") . "{$sql}", 'contacts_events.log');

        $records = $this->model->query($sql)->fetchAll();

        if (!$records) {
            return array();
        }
        $this->workupRecords($records);
        return $records;
    }

        /**
     *
     * @param string $by
     * @param string $extra_filter SQL part
     * @return type
     */
    public function getGroupedCounters($by = 'day', $extra_filter = '')
    {
        $where = $this->getFilter();

        $fields = array('COUNT(*)', 'datetime');
        if ($this->category === 'event') {
            $fields = array('COUNT(DISTINCT l.id)');
        }
        $groupby = 'datetime';
        if ($by === 'month') {
            $fields[1] = 'month';
            $groupby = 'month';
        }

        if ($extra_filter) {
            if ($where) {
                $where = "({$where}) AND {$extra_filter}";
            } else {
                $where = $extra_filter;
            }
        }

        $sql = $this->getSQL($fields, false, $where, $groupby);

        //waLog::log("getGroupedCounters-" . date("Y-m-d H:i:s") . "{$sql}", 'contacts_events.log');
        $records = $this->model->query($sql)->fetchAll();

        if (!$records) {
            return array();
        }
        return $records;
    }

    public function getSQL($fields = null, $distinct = false, $where = null, $groupby = false, $joins = array())
    {
        if (!$fields) {
            $fields = array('contact_id', 'datetime', 'type', 'app_id', 'subject_contact_id');
            if ($this->category === 'event') {
                $fields[] = 'event_id';
                $fields[] = 'start_datetime';
                $fields[] = 'end_datetime';
                $fields[] = 'repeat';
            }
            if ($this->category === 'birthday') {
                $fields[] = 'name';
            }
        } else {
            if ($fields !== 'COUNT(*)') {
                $fields = (array) $fields;
            }
        }
        if ($fields !== 'COUNT(*)' || $distinct) {
            $fm = $this->fields_map;
            foreach ($fm as $ta => $flds) {
                $nflds = array();
                foreach ($fields as $f) {
                    if (isset($flds[$f])) {
                        $nflds[$f] = "{$flds[$f]} AS `{$f}`";
                    } else if (substr($f, 0, 6) === 'COUNT(') {
                        $nflds[$f] = "{$f} AS count";
                    } else if ($f === 'month' && $this->category === 'birthday') {
                        if (!date('L', time())) {
                            $nflds[$f] = "IF(l.birth_month = 2 AND l.birth_day = 29, 3, l.birth_month) AS birth_month";
                        } else {
                            $nflds[$f] = "l.birth_month";
                        }
                    } else {
                        $nflds[$f] = "NULL AS `{$f}`";
                    }
                }
                $fm[$ta] = implode(', ', $nflds);
            }
        }
        $table = $this->getTableName();
        if ($fields !== 'COUNT(*)') {
            $distinct = $distinct ? 'DISTINCT' : '';
            $sql = "SELECT {$distinct} {$fm[$this->category]} FROM `{$table}` l ";
        } else {
            if ($distinct) {
                $sql = "SELECT COUNT(DISTINCT {$fm[$this->category]}) FROM `{$table}` l ";
            } else {
                $sql = "SELECT COUNT(*) FROM `{$table}` l ";
            }
        }
        if (isset($this->joins_map[$this->category])) {
            foreach ($this->joins_map[$this->category] as $al => $join) {
                $sql .= (!empty($join['left']) ? "LEFT JOIN " : "JOIN ") . "`{$join['table']}` {$al} ON {$join['on']} ";
            }
        }
        if ($joins) {
            foreach ($joins as $al => $join) {
                if (is_numeric($al)) {
                    $al = "t{$al}";
                }
                $sql .= (!empty($join['left']) ? "LEFT JOIN " : "JOIN ") . "`{$join['table']}` {$al} ON {$join['on']} ";
            }
        }
        if (isset($this->filters_map[$this->category])) {
            $sql .= "WHERE " . $this->filters_map[$this->category] . " ";
        } else {
            $sql .= "WHERE 1 ";
        }
        if ($where) {
            $sql .= "AND ({$where}) ";
        }
        if ($groupby) {
            if (isset($this->fields_map[$this->category][$groupby])) {
                $groupby = $this->fields_map[$this->category][$groupby];
            }
            $sql .=  "GROUP BY {$groupby} ";
        }
        return $sql;
    }

    protected function workupRecords(&$records)
    {
        $contact_ids = array();
        $apps = array();
        foreach ($records as $r) {
            $contact_ids[] = $r['contact_id'];
            $contact_ids[] = $r['subject_contact_id'];
            if ($this->category === 'log') {
                $apps[] = $r['app_id'];
            }
        }
        $apps = array_unique($apps);
        $all_apps = contactsProHelper::getApps();
        $logs = array();
        foreach ($apps as $app_id) {
            if ($app_id && isset($all_apps[$app_id])) {
                $logs[$app_id] = wa($app_id)->getConfig()->getLogActions(true);
            } else {
                $logs[""] = wa()->getConfig()->getLogActions(true);
            }
        }
        $contact_ids = array_unique($contact_ids);
        $contacts = $this->getContacts($contact_ids);

        $now_year = date('Y');

        foreach ($records as &$r) {
            if (isset($contacts[$r['contact_id']])) {
                $r['contact'] = $contacts[$r['contact_id']];
            } else {
                $r['contact'] = array();
            }
            if ($this->category === 'log' && isset($contacts[$r['subject_contact_id']])) {
                $r['subject_contact'] = $contacts[$r['subject_contact_id']];
            } else {
                $r['subject_contact'] = array();
            }
            if ($this->category === 'log') {
                $app_id = $r['app_id'] ? $r['app_id'] : "";
                if (isset($logs[$r['app_id']][$r['type']]['name'])) {
                    $r['type_name'] = $logs[$r['app_id']][$r['type']]['name'];
                } else {
                    $r['type_name'] = "{$r['app_id']}, {$r['type']}";
                }
            } else {
                $r['type_name'] = $r['type'];
            }
            if ($this->category === 'birthday') {
                if (!empty($r['contact']['birth_year']) && $r['contact']['birth_year'] < $now_year) {
                    $r['contact']['age'] = $now_year - $r['contact']['birth_year'];
                }
            }
        }
        unset($r);
    }

    protected function getContacts($contact_ids)
    {
        $contact_ids = array_diff($contact_ids, array_keys($this->contacts));
        $chunk_size = 20;
        $count = count($contact_ids);
        for ($i = 0; $i < $count; $i += $chunk_size) {
            $collection = new waContactsCollection('id/'.implode(',', array_slice($contact_ids, $i, $chunk_size)));
            $contacts = $collection->getContacts(
                'id,name,firstname,middlename,lastname,photo_url_20,birth_day,birth_month,birth_year',
                0,
                $chunk_size
            );
            foreach ($contacts as $c) {
                $c['name'] = waContactNameField::formatName($c);
                $this->contacts[$c['id']] = $c;
            }
        }
        return $this->contacts;
    }


    protected function getField($name, $with_alias = false)
    {
        if (!isset($this->fields_map[$this->category][$name])) {
            return null;
        }
        return $this->fields_map[$this->category][$name] . ($with_alias ? " AS {$name}" : "");
    }

    protected function getTableName()
    {
        return $this->tables_map[$this->category];
    }

    public function getActivity()
    {
        $table = $this->getTableName();
        $time = time();

        $activity = array();
        $select = array();
        $order = array();
        $groupby = array();

        $split = '';

        $where = $this->getFilter();

        $period = isset($this->spec_filters['_period_']) ? $this->spec_filters['_period_'] : 'month';
        $datetime_field = $this->getField('datetime');
        if ($period === 'month') {
            $m = 24*60*60;
            for ($i = 30; $i >= 0; $i -= 1) {
                $d = date("Y-m-d", time() - $i*$m);
                $activity[$d] = array('date' => $d, 'count' => 0);
            }
            $select[] = "DATE({$datetime_field}) date";
            $order[] = "DATE({$datetime_field})";
            $groupby[] = "date";
        } else if ($period === 'year') {
            $m = 24*60*60;
            for ($i = 365; $i >= 0; $i -= 1) {
                $d = date("Y-m-d", time() - $i*$m);
                $activity[$d] = array('date' => $d, 'count' => 0);
            }
            $select[] = "DATE({$datetime_field}) date";
            $order[] = "DATE({$datetime_field})";
            $groupby[] = "date";
        } else if ($period === 'week') {
            // group by day
            $m = 24*60*60;
            for ($i = 7; $i >= 0; $i -= 1) {
                $d = date("Y-m-d", $time - $i * $m);
                $activity[$d] = array('date' => $d, 'count' => 0);
            }
            $select[] = "DATE({$datetime_field}) date";
            $order[] = "DATE({$datetime_field})";
            $groupby[] = "date";
        } else if ($period === 'today') {
            $m = 60*60;
            for ($i = 24; $i >= 0; $i -= 1) {
                $d = date("Y-m-d H", $time - $i*$m);
                $activity[$d] = array('date' => $d, 'count' => 0);
            }
            $select[] = "DATE_FORMAT({$datetime_field}, '%Y-%m-%d %H') date";
            $order[] = "DATE_FORMAT({$datetime_field}, '%Y-%m-%d %H')";
            $groupby[] = "date";
        } else if ($period === 'lifetime' || is_array($period)) {
            if ($period === 'lifetime') {
                $extremums = $this->model->query(
                    "
                SELECT
                    MIN({$datetime_field}) min,
                    MAX({$datetime_field}) max
                FROM `{$table}` l
            ")->fetchAssoc();

                $min = $extremums['min'];
                $max = $extremums['max'];
                $diff = strtotime($max) - strtotime($min);
            } else {
                $max = $period[1];
                $min = $period[0];
                $diff = strtotime($max) - strtotime($min);
            }
            $time = strtotime($max);
            if ($diff < 24*60*60) {
                $split = 'hour';
                // group by hour
                $m = 60*60;
                for ($i = 24; $i >= 0; $i -= 1) {
                    $t = $time - $i*$m;
                    $d = date("Y-m-d H", $t);
                    $activity[$d] = array('date' => $d, 'count' => 0, 'timestamp' => $t);
                }
                $select[] = "DATE_FORMAT({$datetime_field}, '%Y-%m-%d %H') date";
                $order[] = "DATE_FORMAT({$datetime_field}, '%Y-%m-%d %H')";
                $groupby[] = "date";
            } else if ($diff < 7*24*60*60) {
                $split = 'hour';
                // group by hour
                $m = 24*60*60;
                for ($i = 7; $i >= 0; $i -= 1) {
                    $t = $time - $i*$m;
                    $d = date("Y-m-d H", $t);
                    $activity[$d] = array('date' => $d, 'count' => 0, 'timestamp' => $t);
                }
                $select[] = "DATE_FORMAT({$datetime_field}, '%Y-%m-%d %H') date";
                $order[] = "DATE_FORMAT({$datetime_field}, '%Y-%m-%d %H')";
                $groupby[] = "date";

            } else if ($diff < 366*24*60*60) {
                $split = 'day';
                // group by day
                $m = 24*60*60;
                $n_of_days = round($diff / $m);
                for ($i = $n_of_days; $i >= 0; $i -= 1) {
                    $t = $time - $i * $m;
                    $d = date("Y-m-d", $t);
                    $activity[$d] = array('date' => $d, 'count' => 0, 'timestamp' => $t);
                }
                $select[] = "DATE({$datetime_field}) date";
                $order[] = "DATE({$datetime_field})";
                $groupby[] = "date";
            } else {
                $split = 'week';
                // group by week
                $m = 7*24*60*60;
                $n_of_weeks = round($diff / $m);
                for ($i = $n_of_weeks; $i >= 0; $i -= 1) {
                    $t = $time - $i * $m;
                    $d = date("Y-W", $t);
                    $activity[$d] = array('date' => $d, 'count' => 0, 'ext' => date("Y-m-d", $time - $i * $m), 'timestamp' => $t);
                }
                $select[] = "DATE_FORMAT({$datetime_field}, '%Y-%u') date";
                $order[] = "DATE_FORMAT({$datetime_field}, '%Y-%u')";
                $groupby[] = "date";
            }
        }

        $where = $this->getFilter();
        $select[] = "COUNT(*) count";
        $select = implode(', ', $select);

        $sql = "SELECT {$select}
        FROM `{$table}` l ";

        if (isset($this->joins_map[$this->category])) {
            foreach ($this->joins_map[$this->category] as $al => $join) {
                $sql .= "JOIN `{$join['table']}` {$al} ON {$join['on']} ";
            }
        }
        if (isset($this->filters_map[$this->category])) {
            $sql .= "WHERE " . $this->filters_map[$this->category] . " ";
        } else {
            $sql .= "WHERE 1 ";
        }

        if ($groupby) {
            $groupby = implode(', ', $groupby);
            $groupby = "GROUP BY {$groupby}";
        } else {
            $groupby = "";
        }
        if ($order) {
            $order = implode(', ', $order);
            $order = "ORDER BY {$order}";
        } else {
            $order = "";
        }

        $sql .= " AND ({$where}) {$groupby} {$order}";
        foreach ($this->model->query($sql)->fetchAll() as $it)
        {
            if (isset($activity[$it['date']])) {
                $activity[$it['date']] = array_merge($activity[$it['date']], $it);
            }
        }

        if ($split === 'week') {
            foreach ($activity as &$a) {
                $a['date'] = $a['ext'];
                unset($a['ext']);
            }
            unset($a);
        }

        foreach ($activity as &$a) {
            $a = array_values($a);
        }
        unset($a);

        return array_values($activity);
    }

    public function getActivityCount()
    {
        return $this->getTotalCount();
    }

    public function getUsers($offset = 0, $limit = 10)
    {
        $offset = (int) $offset;
        $limit = (int) $limit;
        $where = $this->getFilter();
        $sql = $this->getSQL(array('contact_id', 'COUNT(*)'), false, $where, 'contact_id', array(
            'c' => array(
                'table' => 'wa_contact',
                'on' => 'c.id = l.contact_id'
            )
        ));

        $users = $this->model->query("{$sql} ORDER BY count DESC LIMIT {$offset}, {$limit}")->fetchAll();
        if (!$users) {
            return array();
        }

        $contact_ids = array();
        foreach ($users as $u) {
            $contact_ids[] = $u['contact_id'];
        }

        $contact_ids = array_unique($contact_ids);
        $contacts = $this->getContacts($contact_ids);

        $data = array();

        foreach ($users as &$u) {
            $u['id'] = $u['contact_id'];
            $u['name'] = ifset($contacts[$u['contact_id']]['name'], '');
            $data[] = $u;
        }
        return $data;
    }

    public function getUsersCount()
    {
        $where = $this->getFilter();
        $sql = $this->getSQL('contact_id', true, $where);
        return $this->model->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchField();
    }

    public function getActions($offset = 0, $limit = 10)
    {
        if ($this->category == 'log') {
            $groupby = 'l.action, l.app_id';
        } else {
            $groupby = 'type';
        }
        $where = $this->getFilter();
        $sql = $this->getSQL(array('type', 'app_id', 'COUNT(*)'), false, $where, $groupby);
        $data = array();
        $offset = (int) $offset;
        $limit = (int) $limit;

        $apps = contactsProHelper::getApps();

        foreach ($this->model->query("{$sql} ORDER BY count DESC LIMIT {$offset}, {$limit}") as $item) {
            $type = $item['type'];
            $app_id = $item['app_id'];
            $name = $type;
            if ($this->category === 'log') {
                $id = "{$app_id}.{$type}";
                if ($app_id && isset($apps[$app_id])) {
                    $logs = wa($app_id)->getConfig()->getLogActions(true);
                } else {
                    $logs = wa()->getConfig()->getLogActions(true);
                }
                if (isset($logs[$type]['name'])) {
                    $name = $logs[$type]['name'];
                }
            } else {
                $id = $type;
            }

            $app_name = $app_id;
            if ($app_id) {
                if (isset($apps[$app_id])) {
                    $app_name = $apps[$app_id]['name'];
                }
            }

            $data[] = array(
                'id' => $id,
                'name' => $name . " ({$app_name})",
                'value' => $name,
                'count' => $item['count']
            );
        }
        return $data;
    }

    public function getActionsCount()
    {
        $where = $this->getFilter();
        $sql = $this->getSQL('type', true, $where);
        return $this->model->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchField();
    }

    public function getNextYear($current_year)
    {
        $current_year = (int) $current_year;
        if ($this->category === 'event') {
            $check_repeated = !!$this->model->query(
                "SELECT id FROM `contacts_event`
            WHERE `repeat` IS NOT NULL AND YEAR(start_datetime) <= {$current_year} LIMIT 1")->
                        fetchField();
            if ($check_repeated) {
                return $current_year + 1;
            } else {
                return $this->model->query("SELECT MIN(YEAR(start_datetime)) FROM `contacts_event` WHERE YEAR(start_datetime) > {$current_year}")->fetchField();
            }
        }
        return null;
    }

    public function getPrevYear($current_year)
    {
        $current_year = (int) $current_year;
        if ($this->category === 'event') {
            $check_repeated = !!$this->model->query(
                    "SELECT id FROM `contacts_event`
                WHERE `repeat` IS NOT NULL AND YEAR(start_datetime) < {$current_year} LIMIT 1")->
                            fetchField();
            if ($check_repeated) {
                return $current_year - 1;
            } else {
                return $this->model->query("SELECT MAX(YEAR(start_datetime)) FROM `contacts_event` WHERE YEAR(start_datetime) < {$current_year}")->fetchField();
            }

        }
        return null;
    }

    /**
     * @param string $category
     * @return waModel
     */
    public static function getModel($category)
    {
        if (!in_array($category, self::$categories)) {
            return null;
        }
        if ($category === 'log') {
            return new contactsNotificationLogsModel();
        } else if ($category === 'birthday') {
            return new contactsNotificationBirthdaysModel();
        }
    }

    public static function sidebarCounters()
    {
        $counters = array();
        foreach (array('log', 'birthday', 'event') as $category) {
            $events = new self(array('period' => 'today'), $category);
            if ($category !== 'event') {
                $counters[$category] = $events->getTotalCount();
            } else {
                $counters[$category] = $events->getActionsCount();
            }
        }
        return $counters;
    }

}
