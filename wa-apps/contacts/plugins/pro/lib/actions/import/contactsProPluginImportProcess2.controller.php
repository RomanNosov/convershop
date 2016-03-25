<?php

/** For more info about magic behaviour of this controller
  * see waLongActionController docs. */
class contactsProPluginImportProcess2Controller extends waLongActionController
{
    /** Model for custom DB access
      * @var waUserGroupsModel */
    private $model = null;

    /** explode(' ', microtime()) at the start of init() or restore() */
    private $timeStart = 0;

    /** Input file descriptor */
    private $input = null;

    /** Called only once when a new export is requested. Checks parameters. */
    protected function preInit() {
        
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        if (! ( $file = $this->getStorage()->read('import/file'))) {
            throw new waException('No input file name in session storage.');
        }

        $file = waSystem::getInstance()->getTempPath()."/".$file;
        if (!is_readable($file)) {
            throw new waException('Input file does not exist or is not readable: '.$file);
        }
        return true;
    }

    /** Called only once when a new export is requested */
    protected function init() {
        $this->timeStart = explode(' ', microtime());
        $this->model = new waUserGroupsModel();
        $this->data = array(
            // number of rows added to database by the end of last successfully completed step
            'rowsAdded' => 0,

            // number of rows that were not added to database because of validation errors
            'rowsRejected' => 0,

            // total input file size
            'fileSize' => 0,

            // file position to start reading next row from
            'nextRow' => 0,

            // Delimeter between fields
            'delimeter' => $this->getRequest()->post('delimiter', waRequest::TYPE_STRING, ','),

            // whether or not to process the first line
            'firstLine' => !!$this->getRequest()->post('first_line'),

            // maps csv columns to contact fields: array(csvCol => fldId[.ext])
            'fields' => $this->getRequest()->post('fields'),

            // Total number of columns in CSV file, equal to count($this->data['fields'])
            'totalCols' => 0,

            // File to read data from
            'inputFilename' => waSystem::getInstance()->getTempPath()."/".$this->getStorage()->read('import/file'),

            // Input file encoding
            'inputEncoding' => strtolower($this->getRequest()->post('encoding', waRequest::TYPE_STRING, 'utf-8')),

            // Group to add contacts to
            'groupId' => $this->getRequest()->post('group_id', waRequest::TYPE_INT, null),

            // Import start time (mysql NOW() is not used anyway so it's safe to use time() here)
            'importStartedAt' => date('Y-m-d H:i:s'),
        );

        $this->data['totalCols'] = count($this->data['fields']);
        foreach($this->data['fields'] as $k => $v) {
            if (!$v) {
                unset($this->data['fields'][$k]);
            }
        }

        if (!$this->data['inputEncoding']) {
            $this->data['inputEncoding'] = 'utf-8';
        }

        $this->data['fileSize'] = filesize($this->data['inputFilename']);
        $this->input = fopen($this->data['inputFilename'], 'r');
    }

    /** Called to restore data when old script exceeds max exec time */
    protected function restore() {
        $this->timeStart = explode(' ', microtime());
        $this->model = new waUserGroupsModel();
        $this->input = fopen($this->data['inputFilename'], 'r');
    }

    /** Checks if there are more contacts to save. */
    protected function isDone() {
        return $this->data['fileSize'] <= $this->data['nextRow'];
    }

    /** Validates a bunch of rows from $this->input starting from $this->data['nextRow'].
      * Valid rows are inserted into database. Incorrect rows are written to $this->fd
      * Increases $this->data['nextRow'] accordingly. */
    protected function step() {
        // Each step takes ~3 seconds
        $stepStart = explode(' ', microtime());

        $fields = $this->data['fields'];
        $processed = 0;

        // Time limit in seconds for the whole Runner.
        // Not really required but makes things smooth and more responsive overall.
        if ($this->max_exec_time) {
            $volTimeLimit = $this->newProcess ? min(10, $this->max_exec_time / 2) : $this->max_exec_time;
        } else {
            $volTimeLimit = $this->newProcess ? 10 : 250;
        }

        // Number of lines written to database since the start of import may differ
        // from $this->data['rowsAdded'] if last Runner failed in the middle of step()
        // so we check it out.
        $sql = "SELECT COUNT(*)
                FROM wa_contact
                WHERE create_app_id='contacts'
                    AND create_method='import'
                    AND create_contact_id=:userId
                    AND create_datetime>=:importStart";
                    $rowsReallyAdded = $this->model->query($sql, array(
                        'userId' => waSystem::getInstance()->getUser()->getId(),
                        'importStart' => $this->data['importStartedAt'],
                    ))->fetchField();

        $skip = 0; // number of lines to skip (counting only lines that pass validation)

        if ($rowsReallyAdded < $this->data['rowsAdded']) {
            // This should not happen.
            throw new waException('2 imports detected running at the same time ('.$rowsReallyAdded.' < '.$this->data['rowsAdded'].').');
        } else if ($rowsReallyAdded > $this->data['rowsAdded']) {
            $skip = $rowsReallyAdded - $this->data['rowsAdded'];
        }

        fseek($this->input, $this->data['nextRow']);

        // Skip the first line in file if asked to
        if ($this->data['nextRow'] == 0 && !$this->data['firstLine']) {
            fgetcsv($this->input, 0, $this->data['delimeter']);
        }

        // Maps fields (and/or subfields) to CSV columns. Only set up if needed.
        $fieldToCol = null;

        // Loop through CSV validating each line until EOF found or time's up.
        do {
            if (! ( $csvLine = fgetcsv($this->input, 0, $this->data['delimeter']))) {
                break; // end of input
            }

            // convert encoding if necessary
            if ($this->data['inputEncoding'] != 'utf-8') {
                foreach($csvLine as &$v) {
                    if ($v) {
                        @$v = iconv($this->data['inputEncoding'], 'utf-8//IGNORE', $v);
                    }
                }
                unset($v);
            }

            $c = new waContact();
            $errors = null;
            foreach($this->data['fields'] as $csvCol => $id) {
                $value = isset($csvLine[$csvCol]) ? $csvLine[$csvCol] : '';
                if (strpos($id, '.') === FALSE && strpos($id, ':') === FALSE) {
                    $c->add($id, $value);
                }
            }
            $c['create_method'] = 'import';
            $c['is_company'] = 0;

            // No name?
            if (!$c['firstname'] && !$c['middlename'] && !$c['lastname']) {
                // Is it a company?
                if ($c['company']) {
                    $c['is_company'] = 1;
                } else
                // Is it a person with unknown name but known email?
                if ($c->get('email', 'default')) {
                    // Use the first email as a name
                    $firstname = $c->get('email', 'default');
                    if (FALSE !== ( $p = strpos($firstname, '@'))) {
                        $firstname = substr($firstname, 0, $p);
                    }
                    $c['firstname'] = $firstname;
                } else // Okay, I give up. No name for the contact = error
                {
                    $errors = 'name_required';
                }
            }

            if (!$errors) {
                $errors = $c->validate();
            }
            
            if ($errors) {
                // Validation failed
                $this->data['rowsRejected']++;

                // Is this a first line in errors file? Output the headers then
                if (ftell($this->fd) <= 0) {
                    $errorLine = array_fill(0, $this->data['totalCols'], _w('<not imported>'));
                    foreach($this->data['fields'] as $csvCol => $id) {
                        $subfield = $ext = '';
                        if (FALSE !== ( $p = strpos($id, '.'))) {
                            $ext = substr($id, $p+1);
                            $id = substr($id, 0, $p);
                        }
                        if (FALSE !== ( $p = strpos($id, ':'))) {
                            $subfield = substr($id, $p+1);
                            $id = substr($id, 0, $p);
                        }

                        if (! ( $f = waContactFields::get($id))) {
                            continue;
                        }
                        $info = $f->getInfo();
                        $errorLine[$csvCol] = $f->getName();

                        if ($ext && isset($info['ext'][$ext])) {
                            $ext = $info['ext'][$ext];
                        }
                        if ($ext) {
                            $errorLine[$csvCol] .= ' - '.$ext;
                        }
                        if ($subfield && isset($info['fields'][$subfield])) {
                            $errorLine[$csvCol] .= ': '.$info['fields'][$subfield]['name'];
                        }
                    }
                    fputcsv($this->fd, $errorLine);
                }

                // Add a new error column to $csvLine and write it to $this->fd
                $csvLine[$this->data['totalCols']] = _w('This line was not imported');
                fputcsv($this->fd, $csvLine);

                // Each error should go to corresponding CSV column.
                // That's why we need a helper array to map 
                // [fieldId][subfield][sort] to csv column index
                if (!$fieldToCol) {
                    /*
                        // for composite fields:
                        $fieldToCol[field_id][subfield_id][sort] = csv_col

                        // for everything else:
                        $fieldToCol[field_id][sort] = csv_col
                        
                        Non-multi fields are pretended to be multi
                        with only a single field copy.
                    */
                    $fieldToCol = array();
                    foreach($this->data['fields'] as $csvCol => $id) {
                        // remove possible ext from $id
                        if (FALSE !== ( $p = strpos($id, '.'))) {
                            $id = substr($id, 0, $p);
                        }

                        // Is it composite?
                        if (FALSE !== ( $p = strpos($id, ':'))) {
                            $subfieldId = substr($id, $p+1);
                            $id = substr($id, 0, $p);

                            if (!isset($fieldToCol[$id][$subfieldId])) {
                                $fieldToCol[$id][$subfieldId] = array();
                            }
                            $fieldToCol[$id][$subfieldId][] = $csvCol;
                            continue;
                        }

                        // no composite
                        if (!isset($fieldToCol[$id])) {
                            $fieldToCol[$id] = array();
                        }
                        $fieldToCol[$id][] = $csvCol;
                    }
                }
                
                // CSV line with errors in corresponding columns
                $errorLine = array_fill(0, $this->data['totalCols'], '');
                $errorLine[$this->data['totalCols']] = _w('Error details shown in columns');
                
                // If no name found for this contact it's a separate error case.
                // Need to set one error message to several columns.
                if ($errors == 'name_required') {
                    $cnt = 0;
                    foreach(array('firstname', 'middlename', 'lastname', 'company', 'email') as $fld) {
                        $cnt += isset($fieldToCol[$fld]) ? count($fieldToCol[$fld]) : 0;
                    }
                    $msg = $cnt > 1 ? _w('One of these fields must have a value') : _w('This field is required'); 
                    foreach(array('firstname', 'middlename', 'lastname', 'company', 'email') as $fld) {
                        if (isset($fieldToCol[$fld])) {
                            foreach($fieldToCol[$fld] as $sort => $col) {
                                $errorLine[$fieldToCol[$fld][$sort]] = $msg;
                            }
                        }
                    }
                    $errors = array();
                }

                /*
                    At last everything is prepared to loop through errors adding them to $errorLine
                    
                    $errors = array(field id => errors for this field)

                    To complicate matters more, errors for different types of fields
                    are in different forms:
                    - For simple fields:      a string.
                    - For multi fields:       list of strings.
                    - For composite fields:   array(subfieldId => string)
                    - Multi-composite fields: list of composite error arrays
                */
                foreach($errors as $fld => $fldErr) {
                    // If it's not a multi field we pretend it is,
                    // but only a single copy of it
                    if (!is_array($fldErr) || !isset($fldErr[0])) {
                        $fldErr = array($fldErr);
                    }
                    
                    // Is it an error for a field that is not imported at all?
                    // Can happen for required fields.
                    if (!isset($fieldToCol[$fld])) {
                        foreach($fldErr as &$err) {
                            if (is_array($err)) {
                                $e = '';
                                foreach($err as $k => $v) {
                                    $e .= ($e ? ($this->data['delimeter'] == ';' ? ',' : ';') : '')." $k: $v"; // could be more human-readable here...
                                }
                                $err = $e;
                            }
                        }
                        unset($err);
                        $errorLine[] = $fld.': '.implode($this->data['delimeter'] == ';' ? ',' : ';', $fldErr);
                        continue;
                    }

                    // loop through copies of a multi field and add each error 
                    // to coresponding csv column
                    foreach($fldErr as $sort => $err) {
                        // Is it a composite field?
                        if(is_array($err)) {
                            foreach($err as $subfield => $e) {
                                $errorLine[$fieldToCol[$fld][$subfield][$sort]] = $e;
                            }
                            continue;
                        }

                        // no composite
                        $errorLine[$fieldToCol[$fld][$sort]] = $err;
                    }
                }
                
                fputcsv($this->fd, $errorLine);
            } else {
                // line is valid
                $this->data['rowsAdded']++;
                if (!$skip) {
                    $c->save();

                    // It is possible that Runner fails between saving contact and
                    // adding it to a group. We cannot reliably use transactions
                    // since they're not throughoutly supported, so we just pray for this
                    // to never happen...

                    // Add newly created contact to group, if needed
                    if ($this->data['groupId']) {
                        $this->model->insert(array(
                            'contact_id' => $c->getId(),
                            'group_id' => $this->data['groupId'],
                        ));
                    }
                } else {
                    $skip--;
                }
            }

            $timeEnd = explode(' ', microtime());

            // Check if this Runner is about to exceed its time limit
            if ($volTimeLimit < ($timeEnd[0] + $timeEnd[1] - $this->timeStart[0] - $this->timeStart[1])) {
                $this->data['nextRow'] = ftell($this->input);
                return false;
            }

            // Check if this step is about to exceed it's time limit
        } while (3 > ($timeEnd[0] + $timeEnd[1] - $stepStart[0] - $stepStart[1]));

        $this->data['nextRow'] = ftell($this->input);
        return true;
    }

    /** Return some info from $this->data to user. Other class variables are not available. */
    protected function info($get = FALSE) {
        $result = array(
            'processId' => $this->processId,
            'total' => $this->data['fileSize'],
            'done' => $this->data['nextRow'],
            'rowsAdded' => $this->data['rowsAdded'],
            'rowsRejected' => $this->data['rowsRejected'],
            'timeStart' => $this->data['importStartedAt'],
            'ready' => FALSE,
        );
        if ($get) {
            return $result;
        }

        echo json_encode($result);
    }

    /** Return file to browser */
    protected function finish($filename) {
        if (!$this->getRequest()->get('file') && !$this->getRequest()->post('file')) {
            // lost messenger
            $result = $this->info(TRUE);
            $result['ready'] = TRUE;
            echo json_encode($result);
            return FALSE;
        }

        // Save last successfull import start time in current user settings
        // and remove old import history
        if ($this->data['rowsAdded'] > 0) {
            $time = $this->data['importStartedAt'];
            $this->getUser()->setSettings('contacts', 'import', $time);
            $history = new contactsHistoryModel();
            $history->prune(0, 'import');
            
            $col = new contactsCollection("import/{$time}");
            $count = $col->count();
            $title = $col->getTitle();
            
            if ($history->save("/contacts/import/result/{$time}", $title, 'import', $count)) {
                //$this->logAction('import');
            }
            
        }

        // If there were errors then send file to browser
        if ($this->data['rowsRejected']) {
            waFiles::readfile($filename, 'import_errors.csv', FALSE);
        }

        // Remove temporary file we read data from
        if(is_writable($this->data['inputFilename'])) {
            @unlink($this->data['inputFilename']);
        }

        return TRUE;
    }
}

// EOF
