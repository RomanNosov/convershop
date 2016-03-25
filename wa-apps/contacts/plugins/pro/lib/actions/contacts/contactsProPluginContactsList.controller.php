<?php

/** Everything that shows lists of contacts uses this controller. */
class contactsProPluginContactsListController extends waJsonController
{
    protected $offset;
    protected $limit;
    protected $sort;
    protected $order;
    protected $filters;

    public function __construct() {
        $this->offset = waRequest::post('offset', 0, 'int');
        $this->limit = waRequest::post('limit', 20, 'int');
        if (!$this->limit) {
            $this->limit = 30;
        }
        $this->order = '';
        $this->sort = waRequest::post('sort');
        if ($this->sort) {
            $this->order = waRequest::post('order', 1, 'int') ? ' ASC' : ' DESC';
        }
    }

    public function execute()
    {
        if ( ( $query = trim(waRequest::post('query'), '/'))) {
            $query = urldecode($query);
            if (strpos($query, '/') === false) {
                $h = $hash = 'search/'.$query;
            } else {
                $h = $hash = $query;
                if (substr($hash, 0, 14) == 'import/results') {
                    $h = str_replace('import/results', 'import', $hash);
                }
            }
        } else {
            $h = $hash = '';
        }

        $h = str_replace('\\/', 'ESCAPE_SLASH', $h);
        $h_parts = explode('/', $h, 3);
        foreach ($h_parts as &$hp) {
            $hp = str_replace('ESCAPE_SLASH', '\/', $hp);
        }
        unset($hp);
        
        $can_change_search_conditions = true;
        $can_save_as_filter = true;

        if ($h_parts[0] === 'search') {
            if (count($h_parts) <= 2) {
                $h_parts[0] = 'prosearch';
                if (!empty($h_parts[1])) {
                    $h_parts[1] = urldecode($h_parts[1]);
                }
            } else {
                array_shift($h_parts);
                $can_change_search_conditions = false;
                $can_save_as_filter = false;
            }
            $h = implode('/', $h_parts);
        }

        $collection = new contactsCollection($h);

        $this->response['fields'] = array();
        $fields = '*,photo_url_32,photo_url_96';
        if ($h_parts[0] === 'users' || $h_parts[0] === 'group') {
            if (!wa()->getUser()->isAdmin()) {
                throw new waRightsException(_w('Access denied'));
            }
            $fields .= ',_access';
            $this->response['fields']['_access'] = array(
                'id' => '_access',
                'name' => _w('Access'),
                'type' => 'Access',
                'vertical' => true
            );
        }

        $collection->orderBy($this->sort, $this->order);
        $this->response['count'] = $collection->count();

        $view = waRequest::post('view');

        if ($view == 'list') {
            // Preload info to cache to avoid excess DB access
            $cm = new waCountryModel();
            $cm->preload();

            $this->response['fields'] = array_merge($this->response['fields'], contactsHelper::getFieldsDescription(array(
                'title',
                'name',
                'photo',
                'firstname',
                'middlename',
                'lastname',
                'locale',
                'timezone',
                'jobtitle',
                'company',
                'sex',
                'company_contact_id'
            ), true));
        }

        $this->response['contacts'] = $collection->getContacts($fields, $this->offset, $this->limit);

        $this->response['contacts'] = array_values($this->response['contacts']);
        $this->workupContacts($this->response['contacts'], $this->offset);

        if ($view == 'list') {
            // Need to format field values correctly for this view.
            foreach($this->response['contacts'] as &$cdata) {
                $c = new waContact($cdata['id']);
                $c->setCache($cdata);
                $data = $c->load('list,js') + $cdata;
                contactsHelper::normalzieContactFieldValues($data, waContactFields::getInfo($c['is_company'] ? 'company' : 'person', true));
                if (isset($data['photo'])) {
                    $data['photo'] = $c->getPhoto();
                }
                $c->removeCache(array_keys($cdata));
                $cdata = $data;
            }
            unset($cdata);
        } else {
            foreach ($this->response['contacts'] as &$cdata) {
                $cdata['name'] = waContactNameField::formatName($cdata);
                if ($cdata['name'] == $cdata['id']) {
                    $cdata['name'] = false;
                }
            }
            unset($cdata);
        }

        if ($view == 'map') {
            foreach ($this->response['contacts'] as &$cdata) {
                $c = new waContact($cdata['id']);
                $cdata['address'] = $c->get('address');
            }
            unset($cdata);
            $this->response['geolocations_stats'] = $this->getGeoLocationsStats($this->response['contacts']);
        }

        $title = $collection->getTitle();

        $hm = new contactsHistoryModel();

        if ($hash) {
            $type = explode('/', $hash);
            $hash = substr($hash, 0, 1) == '/' ? $hash : '/contacts/'.$hash;
            $type = $type[0];

            // if search query looks like a quick search then remove field name from header
            if ($type == 'search' && preg_match('~^/contacts/search/(name\*=[^/]*|email\*=[^/]*@[^/]*)/?$~i', $hash)) {
                $title = preg_replace("~^[^=]+=~", '', $title);
            }

            // save history
            if ($type == 'search') {
                $hm->save($hash, $title, $type, $this->response['count']);
                $this->logAction('search');
            }

            // Information about system category in categories view
            if (substr($hash, 0, 19) === '/contacts/category/') {
                $category_id = (int) substr($hash, 19);
                $cm = new waContactCategoryModel();
                $category = $cm->getById($category_id);
                if ($category && $category['system_id']) {
                    $this->response['system_category'] = $category['system_id'];
                }
            }
        }

        // Update history in user's browser
        $this->response['history'] = $hm->get();
        $this->response['title'] = $title;
        $this->response['info'] = $this->getInfo($collection);
        $this->response['highlight_terms'] = ifset($this->response['info']['highlight_terms'], array());
        foreach ($this->response['highlight_terms'] as &$t) {
            $t = htmlspecialchars($t);
        }
        unset($t);


//        $custom_fields = (array) $this->getRequest()->request('custom_fields', array());
//        if (!empty($this->response['info']['fields'])) {
//            $custom_fields = array_merge($custom_fields, $this->response['info']['fields']);
//        }
//
//        $metric_fields = contactsSearchHelper::getMetricFields($custom_fields);
//        $this->response['fields'] = array_merge($this->response['fields'], $metric_fields);
//        contactsSearchHelper::addMetrics($this->response['contacts'], $metric_fields);
//
//        $this->response['metrics'] = contactsSearchHelper::getMetrics();

        if ($this->response['count'] > 0) {
            $this->response['count_html'] = _wp(
                '<strong>%d</strong> contact found',
                '<strong>%d</strong> contacts found',
                $this->response['count']
            );
        } else {
            $this->response['count_html'] = _wp('No contacts found');
        }

        $user = $this->getUser();
        $this->response['user'] = array(
            'id' => $user->getId(),
            'is_admin' => $user->isAdmin()
        );

//        if ($h_parts[0] === 'prosearch' ||
//                ($h_parts[0] === 'view' &&
//                    ($this->response['info']['type'] !== 'category' ||
//                        (!$this->response['info']['system_id'] && !$this->response['info']['app_id'])
//                    )
//                )
//            )
//        {
//            $this->response['icons'] = contactsProHelper::getViewIcons();
//        }

        if (!$h) {
            $this->response['abc'] = $this->getAbcIndex();
            foreach ($this->response['contacts'] as &$c) {
                $c['first_letter'] = mb_strtoupper(mb_substr($c['name'], 0, 1));
            }
            unset($c);
        }

        $this->response['hash_ar'] = $h_parts;
        $this->response['hash'] = $hash;
        $this->response['can_change_search_conditions'] = $can_change_search_conditions;
        $this->response['can_save_as_filter'] = $can_save_as_filter;

    }

    public function workupContacts(&$contacts, $offset = 0)
    {
        if (!$contacts) {
            return array();
        }
        $contact_fields = array(
            array_keys(waContactFields::getAll('person', true)),
            array_keys(waContactFields::getAll('company', true)),
        );
        $contact_ids = array();
        foreach ($contacts as $i => &$c) {
            $fields = $contact_fields[intval($c['is_company'])];
            $data = array(
                'id' => $c['id']
            );
            foreach ($fields as $fld_id) {
                if (array_key_exists($fld_id, $c)) {
                    $data[$fld_id] = $c[$fld_id];
                    unset($c[$fld_id]);
                }
            }
            $c = array_merge($data, $c);
            $c['offset'] = $offset + $i;
            $contact_ids[] = $c['id'];
        }
        unset($c);
    }

    public function getInfo($collection)
    {
        $info = $collection->getInfo();
        return $info;
    }

    public function getAbcIndex()
    {
        $model = new waModel();
        $sql = "SELECT UPPER(SUBSTRING(name, 1, 1)) letter, COUNT(id) count FROM `wa_contact`
            GROUP BY letter
            ORDER BY letter";
        $abc = $model->query($sql)->fetchAll();
        $offset = 0;
        foreach ($abc as &$item) {
            $item['offset'] = $offset;
            $offset += $item['count'];
            if ($item['letter'] === 'Ё') {
                $item['letter'] = 'Е';
            }
        }
        unset($item);
        return $abc;
    }

    public function getGeoLocationsStats($contacts)
    {
        $contacts_count = 0;
        $points_count = 0;
        foreach ($contacts as $c) {
            if (!empty($c['address'])) {
                $first = false;
                foreach ($c['address'] as $address) {
                    if (!empty($address['data']['lat']) && !empty($address['data']['lng'])) {
                        $points_count += 1;
                        if (!$first) {
                            $contacts_count += 1;
                            $first = true;
                        }
                    }
                }
            }
        }
        $message = '';
        if (!$contacts_count) {
            $message = _wp('No contacts in this list have geolocation info.');
            $message .= ' <a class="%CLASS%" href="%HREF%">' . _wp('Why?') . '</a>';
        } else {
            $message = _wp('Snown %d location', 'Snown %d locations', $points_count);
            $message .= ' ';
            $message .= _wp('for %d contact', 'for %d contacts', $contacts_count);
            if (count($contacts) - $contacts_count > 0) {
                $message .= ' (';
                $message .= _wp('%d contact do not have geolocation info.', '%d contacts do not have geolocation info.', count($contacts) - $contacts_count);
                $message .= ' <a class="%CLASS%" href="%HREF%">' . _wp('Why?') . '</a>)';
            }
        }
        return array(
            'contacts_count' => $contacts_count,
            'points_count' => $points_count,
            'message' => $message
        );
    }

}

// EOF