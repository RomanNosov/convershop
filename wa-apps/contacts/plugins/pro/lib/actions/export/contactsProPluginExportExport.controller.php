<?php

class contactsProPluginExportExportController extends waLongActionController
{
    /** @var waDbResultIterator */
    private $contactData = null;
    /** @var waDbResultIterator */
    private $contactEmails = null;
    /** @var waDbResultIterator */
    private $contacts = null;

    private $flds;
    private $hash;
    
    /**
     *
     * @var waModel
     */
    private $model = null;
    
    // explode(' ', microtime()) at the start of init() or restore(); 
    private $timeStart = 0;
    
    /** Called only once when a new export is requested. Checks parameters. */ 
    protected function preInit() {
        
        // parse and check parameters
        $fields = $this->getFields();
        if (!$fields || !is_array($fields)) {
            throw new waException('Wrong format for fields.');
        }
        
        $this->countries = array();
        $this->regions = array();
        foreach ($fields as $field) {
            if ($field['id'] === 'address') {
                if (isset($field['subfield']['id']) && $field['subfield']['id'] === 'country' && empty($this->countries)) {
                    $this->countries = wao(new waCountryModel())->getAll('iso3letter');
                }
                if (isset($field['subfield']['id']) && $field['subfield']['id'] === 'region' && empty($this->regions)) {
                    foreach (wao(new waRegionModel())->getAll() as $item) {
                        $this->regions[$item['country_iso3']] = ifset($this->regions[$item['country_iso3']], array());
                        $this->regions[$item['country_iso3']][$item['code']] = $item;
                    }
                }
            }
        }
        
        $this->flds = $fields;        
        $this->hash = $this->getRequest()->post('hash', '', waRequest::TYPE_STRING_TRIM);
        
        return true;
    }
    
    /** Called only once when a new export is requested */ 
    protected function init() {
        $this->timeStart = explode(' ', microtime());
        
        //$this->model = new contactsContactListsModel();
        $this->model = new waModel();
        $this->data = array(
            // contacts are processed in order of their found_sort field. This is the last successfully processed.
            'lastFoundSort' => -1,

            // Total contacts to export (set by _performDataSearch)
            'totalRows' => 0,
            
            // Already written to $this->fd
            'processedRows' => 0,
            
            // Fields to export
            'fields' => $this->flds,

            // collection hash
            'hash' => $this->hash,
            
            // Delimeter between fields
            'delimeter' => ';',
            
            'countries' => $this->countries,
            
            'regions' => $this->regions
            
        );
        
        if ( ( $d = $this->getRequest()->post('delimeter'))) {
            $this->data['delimeter'] = $d;
        }

        $this->_performIdSearch($this->data['hash']);
        $this->_performDataSearch($this->data['fields'], $this->data['lastFoundSort']);

        // output csv header
        $line = array();
        foreach($this->data['fields'] as $f) {
            $line[] = $f['full_name'];
        }
        fputcsv($this->fd, $line, $this->data['delimeter']);
    }
    
    /** Called to restore data when old script exceeds max exec time */
    protected function restore() {
        $this->timeStart = explode(' ', microtime());
        //$this->model = new contactsContactListsModel();
        $this->model = new waModel();
        $this->_performIdSearch($this->data['hash'], $this->data['lastFoundSort']);
        $this->_performDataSearch($this->data['fields'], $this->data['lastFoundSort']);        
    }

    /** Checks if there are more contacts to save. */
    protected function isDone() {
        return $this->data['totalRows'] <= $this->data['processedRows'];
    }

    /** Writes to $this->fd a bunch of next $this->contacts 
      * using data from $this->contactData, $this->contactEmails.
      * Increments $this->data['processedRows'] for each processed row. */
    protected function step() {        
        // Each step takes ~3 seconds
        $stepStart = explode(' ', microtime());

        $fields = $this->data['fields'];
        $processed = 0;
        
        // Time limit in seconds for the whole Runner
        if ($this->max_exec_time) {
            $volTimeLimit = $this->newProcess ? min(10, $this->max_exec_time / 2) : $this->max_exec_time; 
        } else {
            $volTimeLimit = $this->newProcess ? 10 : 250; 
        }
        
        do {
            if (! ( $c = $this->_nextContact())) {
                break;
            }
            
            $line = array();
            $map = array();     // for multiple fields, save sort for avoid doubles
            
            foreach($fields as $i => $f) {
                $f_id = $f['id'];                
                $line[$i] = '';
                
                switch($f['source']) {
                    case 'info':
                        if ($f_id === 'birthday') {                            
                            $y = $c['contact']['birth_year'];
                            $m = $c['contact']['birth_month'];
                            $d = $c['contact']['birth_day'];
                            if (is_numeric($m) && $m > 0) {
                                $m = $m < 10 ? "0{$m}" : $m;
                            } else {
                                $m = '';
                            }
                            if (is_numeric($d) && $d > 0) {
                                $d = $d < 10 ? "0{$d}" : $d;
                            } else {
                                $d = '';
                            }
                            if (!is_numeric($y) && $y > 0) {
                                $y = '';
                            }
                            $line[$i] = implode('.', array($d, $m, $y));
                        } else {
                            $v = $c['contact'][$f_id];
                            if (!empty($f['options']) && isset($f['options'][$v])) {
                                $line[$i] = $f['options'][$v];
                            } else {
                                $line[$i] = $v;
                            }
                        }
                        break;
                    case 'data':
                        if (!empty($c['data'][$f_id])) {
                            $found = false;
                            foreach($c['data'][$f_id] as $val) {
                                if ($val['ext'] === $f['ext'] && !isset($map[$f['full_id']][$val['sort']])) {
                                    if (!empty($f['options']) && isset($f['options'][$val['value']])) {
                                        $line[$i] = $f['options'][$val['value']];
                                    } else {
                                        $line[$i] = isset($val['loc_value']) ? $val['loc_value'] : $val['value'];
                                        if ($f['fld']) {
                                            $line[$i] = $f['fld']->format($line[$i], 'value');
                                        }
                                    }
                                    $map[$f['full_id']][$val['sort']] = true;
                                    $found = true;
                                    break;
                                }
                            }
                            // if no ext and no found, search first proper
                            if (!$f['ext'] && !$found) {
                                foreach($c['data'][$f_id] as $val) {
                                    if (!isset($map[$f['full_id']][$val['sort']])) {
                                        if (!empty($f['options']) && isset($f['options'][$val['value']])) {
                                            $line[$i] = $f['options'][$val['value']];
                                        } else {
                                            $line[$i] = isset($val['loc_value']) ? $val['loc_value'] : $val['value'];
                                            if ($f['fld']) {
                                                $line[$i] = $f['fld']->format($line[$i], 'value');
                                            }
                                        }
                                        //$map[$f['full_id']][$val['sort']] = true;
                                        break;
                                    }
                                }
                            }
                        } else if (!empty($c['data'][$f['full_id']])) {
                            $found = false;
                            foreach($c['data'][$f['full_id']] as $val) {
                                if ($val['ext'] === $f['ext'] && !isset($map[$f['full_id']][$val['sort']])) {
                                    if (!empty($f['options']) && isset($f['options'][$val['value']])) {
                                        $line[$i] = $f['options'][$val['value']];
                                    } else {
                                        $line[$i] = isset($val['loc_value']) ? $val['loc_value'] : $val['value'];
                                    }
                                    $map[$f['full_id']][$val['sort']] = true;
                                    $found = true;
                                    break;
                                }
                            }
                            // if no ext and no found, search first proper
                            if (!$f['ext'] && !$found) {
                                foreach($c['data'][$f['full_id']] as $val) {
                                    if (!isset($map[$f['full_id']][$val['sort']])) {
                                        if (!empty($f['options']) && isset($f['options'][$val['value']])) {
                                            $line[$i] = $f['options'][$val['value']];
                                        } else {
                                            $line[$i] = isset($val['loc_value']) ? $val['loc_value'] : $val['value'];
                                        }
                                        //$map[$f['full_id']][$val['sort']] = true;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'email':
                        $found = false;
                        foreach($c['emails'] as $email) {
                            if ($email['ext'] === $f['ext'] && !isset($map[$f['full_id']][$email['sort']])) {
                                $line[$i] = $email['email'];
                                $map[$f['full_id']][$email['sort']] = true;
                                $found = true;
                                break;
                            }
                        }
                        if (!$f['ext'] && !$found) {
                            foreach ($c['emails'] as $email) {
                                if (!isset($map[$f['full_id']][$email['sort']])) {
                                    $line[$i] = $email['email'];
                                    //$map[$f['full_id']][$email['sort']] = true;
                                    break;
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }
            }

            fputcsv($this->fd, $line, $this->data['delimeter']);

            $lastFoundSort = $c['found_sort'];
            $processed++;
            $timeEnd = explode(' ', microtime());
            
            if ($volTimeLimit < ($timeEnd[0] + $timeEnd[1] - $this->timeStart[0] - $this->timeStart[1])) {
                $this->data['processedRows'] += $processed;
                $this->data['lastFoundSort'] = $lastFoundSort;
                return false;
            }
        } while (3 > ($timeEnd[0] + $timeEnd[1] - $stepStart[0] - $stepStart[1]));
        
        $this->data['processedRows'] += $processed;
        $this->data['lastFoundSort'] = $lastFoundSort;
        return !!$c; // if $c is null then there are no more unprocessed contacts in $this->contacts
    }

    /**
     * @param waDbResultIterator $iterator
     * @return mixed
     */
    private function _currentId($iterator) {
        $a = $iterator->current();
        return $a['contact_id'];
    }

    /**
     * @param waDbResultIterator $iterator
     * @return mixed
     */
    private function _currentFoundSort($iterator) {
        $a = $iterator->current();
        return $a['found_sort'];
    }
    
    /** Return all available data for next contact or null if no more contacts available. */
    private function _nextContact() {
        $result = array(
            'id' => null,
            'contact' => array(
                // 'field' => 'value'
            ),
            'emails' => array(
                /*array(
                    'email' => ...
                    'ext' => ...
                )*/
            ),
            'data' => array(
                /*
                field => array(
                    ...,
                    sort => array(
                        'field' => ...,
                        'ext' => ...,
                        'value' => ...,
                        'sort' => ...,
                    ),
                    ...
                    
                ),
                */
            ),
            'found_sort' => null,
        );
        
        $found_sort = false;
        if ($this->contacts !== null) {
            if(!$this->contacts->valid()) {
                return null; // end of data
            }
            $result['contact'] = $this->contacts->current();
            $result['id'] = $result['contact']['contact_id'];
            $found_sort = $result['contact']['found_sort'];
            $this->contacts->next();
        }
        
        if (!$found_sort) { // no data from wa_contact needed, so no query has been done; need other ways to determine $found_sort and $result['id']  
            if ($this->contactData !== null && $this->contactData->valid()) {
                $found_sort = $this->_currentFoundSort($this->contactData);
                $result['id'] = $this->_currentId($this->contactData);
            }

            if ($this->contactEmails !== null && $this->contactEmails->valid()) {
                if ($found_sort) {
                    $fs = $this->_currentFoundSort($this->contactEmails);
                    if($fs < $found_sort) {
                        $found_sort = $fs;
                        $result['id'] = $this->_currentId($this->contactEmails);
                    }
                } else {
                    $found_sort = $this->_currentFoundSort($this->contactEmails);
                    $result['id'] = $this->_currentId($this->contactEmails);
                }
            }

            if (!$found_sort) {
                return null; // end of data
            }
        }

        $result['found_sort'] = (int) $found_sort;
        
        if ($this->contactData !== null && $this->contactData->valid()) {
            if ($found_sort > $this->_currentFoundSort($this->contactData)) {
                throw new waException('$found_sort > contact_data.found_sort, this must never happen.');
            }
            while($this->contactData->valid() && $found_sort == $this->_currentFoundSort($this->contactData)) {
                $row = $this->contactData->current();
                $field = $row['field'];
                if (!isset($result['data'][$field])) {
                    $result['data'][$field] = array();
                }
                $result['data'][$field][$row['sort']] = $row;
                $this->contactData->next();
            }
            foreach($result['data'] as $field => &$arr) {
                ksort($arr);
            }
            unset($arr);
            
            if (isset($result['data']['address:country'])) {
                foreach ($result['data']['address:country'] as $sort => $addr_country) {
                    $country = $addr_country['value'];
                    if (isset($this->data['countries'][$country])) {
                        $result['data']['address:country'][$sort]['loc_value'] = _ws($this->data['countries'][$country]['name']);
                    }
                    if (isset($result['data']['address:region'][$sort])) {
                        $region = $result['data']['address:region'][$sort]['value'];
                        if (isset($this->data['regions'][$country][$region])) {
                            $result['data']['address:region'][$sort]['loc_value'] = $this->data['regions'][$country][$region]['name'];
                        }
                    }
                }
            }
            
        }

        if ($this->contactEmails !== null && $this->contactEmails->valid()) {
            if ($found_sort > $this->_currentFoundSort($this->contactEmails)) {
                throw new waException('$found_sort > contact_data.found_sort, this must never happen.');
            }
            while($this->contactEmails->valid() && $found_sort == $this->_currentFoundSort($this->contactEmails)) {
                $result['emails'][] = $this->contactEmails->current();
                $this->contactEmails->next();
            }
        }
        
        return $result;
    }
    
    /** Return some info from $this->data to user. Other class variables are not available. */
    protected function info() {
        echo json_encode(array(
            'processId' => $this->processId,
            'total' => $this->data['totalRows'],
            'done' => $this->data['processedRows'],
            'ready' => false,
        ));
    } 
    

    /** Return file to browser */
    protected function finish($filename) {
                
        if (!$this->getRequest()->get('file') && !$this->getRequest()->post('file')) {
            // lost messenger
            echo json_encode(array(
                'processId' => $this->processId,
                'total' => $this->data['totalRows'], 
                'done' => $this->data['processedRows'],
                'ready' => true,
            ));
            return false;
        }
        waFiles::readfile($filename, 'exported_contacts.csv', false);
        return true;
    }

    /** Create (or empty if exists) a temporary table `ids_found` and fill contact ids there. */
    private function _performIdSearch($hash, $lastFoundSort = -1) {
        
        foreach (array(
            // Temporary table to store ids
            "CREATE TEMPORARY TABLE IF NOT EXISTS ids_found (
            found_id BIGINT UNSIGNED NOT NULL,
            found_sort BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            UNIQUE(found_id)
        )",
            // Clear table
            "DELETE FROM ids_found"
            
        ) as $sql)
        {
            $this->model->exec($sql);
        }

        $collection = new contactsCollection($hash);
        $collection->saveToTable('ids_found', array('found_id' => 'id'));
                
        // remove everything that we've already processed
        $sql = "DELETE FROM ids_found WHERE found_sort < :lastFoundSort";
        $this->model->exec($sql, array('lastFoundSort' => $lastFoundSort));
    }
    
    /** Initializes dbResult objects: $this->contacts, $this->contactData, $this->contactEmails
      * using a temporary table `ids_found`. Sets $this->data['totalRows'] if it's <= 0. */
    private function _performDataSearch($fields, $lastFoundSort=-1) {
                
        //
        // All data must be sorted by i.found_sort.
        //
        // contact fields we need
        $select = 'i.found_sort, i.found_id AS contact_id';
        foreach($this->data['fields'] as $field) {
            if($field['source'] == 'info') {
                if (!$field['parts']) {
                    $select .= ", {$field['id']}";
                } else {
                    foreach ($field['parts'] as $part) {
                        $select .= ", {$part}";
                    }
                }
            }
        }
        
        // abligatory fields
        $select .= ", is_company";
        
        // Total contacts found
        if ($this->data['totalRows'] <= 0) {
            $sql = "SELECT COUNT(*) AS num FROM ids_found";
            $this->data['totalRows'] = $this->model->query($sql)->fetchField('num');
        }
        
        //
        // Contacts
        //
        $sql = "SELECT $select
                FROM ids_found AS i 
                    JOIN wa_contact AS c
                        ON i.found_id=c.id
                WHERE i.found_sort > :lastFoundSort
                    AND i.found_sort < :limit
                ORDER BY i.found_sort";

        $this->contacts = $this->model->query($sql, array('lastFoundSort' => $lastFoundSort, 'limit' => $lastFoundSort + 30000))->getIterator();
        
        //
        // contact_data
        //
        $sql = "SELECT i.found_sort, cd.contact_id, cd.field, cd.ext, cd.value, cd.sort  
                FROM ids_found AS i
                    JOIN wa_contact_data AS cd
                        ON i.found_id=cd.contact_id
                WHERE i.found_sort > :lastFoundSort
                    AND i.found_sort < :limit
                ORDER BY i.found_sort, cd.sort";
            
        $this->contactData = $this->model->query($sql, array('lastFoundSort' => $lastFoundSort, 'limit' => $lastFoundSort + 30000))->getIterator();
        
        //
        // contact_emails
        //
        $sql = "SELECT i.found_sort, ce.contact_id, ce.email, ce.ext, ce.sort
                FROM ids_found AS i
                    JOIN wa_contact_emails AS ce
                        ON i.found_id=ce.contact_id
                WHERE i.found_sort > :lastFoundSort
                    AND i.found_sort < :limit
                ORDER BY i.found_sort, ce.sort, ce.id";

        $this->contactEmails = $this->model->query($sql, array('lastFoundSort' => $lastFoundSort, 'limit' => $lastFoundSort + 30000))->getIterator();
    }
    
    private function getFields()
    {
        $all_fields = waContactFields::getAll('enabled');

        $field_id_sort = array();
        $fields = array();
        foreach ($this->getRequest()->request('field_id') as $field_id) {
            
            $sort = ifset($field_id_sort[$field_id], 0);
            
            $fld_id = $field_id;
            $ext = '';
            if (strstr($field_id, '.') !== false) {
                list($fld_id, $ext) = explode('.', $field_id, 2);
            }
            $subfld_id = '';
            if (strstr($fld_id, ':') !== false) {
                list($fld_id, $subfld_id) = explode(':', $fld_id, 2);
            }
            if (!isset($all_fields[$fld_id])) {
                continue;
            }
            $fld = $all_fields[$fld_id];
            
            $multi = $fld->isMulti();
            $composite = $fld instanceof waContactCompositeField;

            $field = array_merge($fld->getInfo(), array(
                'id' => $fld_id,
                'full_id' => $fld_id,
                'name' => $fld->getName(),
                'full_name' => $fld->getName() . ($ext ? " - {$ext}" : ''),
                'source' => $fld->getStorage(true),
                'multi' => $multi,
                'composite' => $composite,
                'subfield_id' => array(),
                'parts' => $fld_id === 'birthday' ? $fld->getParts(true) : array(),
                'fld' => null,      // instance of contactField if formatting is required
                'ext' => $ext,
                'sort' => $sort         // this sort use for multiple fields when ext are equals
            ));
            
            if ($composite) {
                $subfields = $fld->getFields();
                if (!isset($subfields[$subfld_id])) {
                    continue;
                }
                $subfld = $subfields[$subfld_id];
                $field['subfield'] = array(
                    'id' => $subfld_id,
                    'name' => $subfld->getName()
                );
                $field['full_name'] .= ": {$subfld_id}";
                $field['full_id'] .= ":{$subfld_id}";
            }
            
            // these fields for which formatting is required
            if ($fld_id === 'phone') {
                $field['fld'] = $fld;
            }
            
            $fields[] = $field;
            
        }
        
        return $fields;
    }
    
}

// EOF