<?php

class contactsProPlugin extends waPlugin
{
    public function backendAssets()
    {

        // plugin statics
        contactsProHelper::addBlockJs(array(
            'js/search.js',
            'js/contacts.js',
            'js/period.dialog.js',
            'js/inline.editable.widget.js',
        ), 'js/compiled/contacts-pro.js');

        $this->addCss('css/contacts.css');
        $this->addJs('js/jquery.color-2.1.2.min.js');
        $this->addJs('js/timepicker/jquery.timepicker.min.js');
        $this->addCss('js/timepicker/jquery.timepicker.css');

        // tags input
        $wa_url = wa()->getRootUrl(true);
        wa()->getResponse()->addCss("{$wa_url}wa-content/js/jquery-plugins/jquery-tagsinput/jquery.tagsinput.css", false);
        wa()->getResponse()->addJs("{$wa_url}wa-content/js/jquery-plugins/jquery-tagsinput/jquery.tagsinput.min.js", false);

        // l10n
        $loc = json_encode($this->loc());
        return "<script>$.wa.locale = $.extend($.wa.locale, {$loc});</script>";
    }

    public function backendSidebar()
    {
        return contactsProHelper::chainViewAction(new contactsProPluginBackendSidebarAction());
    }

    public function backendTemplates()
    {
        $view = wa()->getView();
        $view->assign(array(
            'icons' => contactsProHelper::getViewIcons()
        ));
        return $view->fetch('plugins/pro/templates/actions/backend/include.templates.html');
    }

    protected function getViews()
    {
        $view_model = new contactsViewModel();
        $views = array(
            array(),    // shared
            array()     // others
        );
        foreach ($view_model->getAllViews() as $view) {
            $views[(int) !$view['shared']][] = $view;
        }
        $this->view->assign(array(
            'views' => $views,
            'is_admin' => wa()->getUser()->isAdmin()
        ));
    }


    public function profileTab($params)
    {
        $contact_id = $params;
        $tabs = array();
        $contact = new waContact($contact_id);

        if (contactsProHelper::hasAccessToContactActivity($contact_id)) {
            $activity_tab = $this->profileActivityTab($contact_id);
            if ($activity_tab) {
                $tabs['activity'] = $activity_tab;
            }
        }

        if ($contact->exists()) {
            if (wa()->getUser()->getRights('contacts', 'edit') || $contact['create_contact_id'] == wa()->getUser()->getId()) {
                $tabs['notes'] = $this->profileNotesTab($contact_id);
            }

            if (wa()->getUser()->getRights('contacts', 'backend') > 1) {
                $tabs['events'] = $this->profileEventsTab($contact_id);
                if (empty($tabs['events'])) {
                    unset($tabs['events']);
                }
            }

        }

        return $tabs;
    }

    public function backendLastViewContext($params)
    {
        $hash = trim(ifset($params['hash']), '#/');
        $sort = ifset($params['sort'], '');
        $order = ifset($params['order'], 'ASC');
        $offset = ifset($params['offset'], 0);
        $collection = null;
        if (substr($hash, 0, 23) === 'contacts/import/results') {
            $hash = str_replace('contacts/import/results', 'import', $hash);
            $collection = new contactsCollection($hash);
        } else if (substr($hash, 0, 15) === 'contacts/search') {
            // parse advanced search
            $hash = substr($hash, 16);
            $hash = urldecode($hash);
            $collection = new waContactsCollection('prosearch/' . $hash);
        }

        if ($collection === null) {
            return null;
        }

        if ($sort) {
            if (strpos($sort, ".") === false) {
                $sort = "c.{$sort}";
            }
            $collection->orderBy($sort, $order);
        }

        $total_count = $collection->count();
        $ids = array_keys($collection->getContacts('id', max($offset - 1, 0), 3));

        $prev = null;
        $next = null;

        if ($offset > 0) {
            $prev = ifset($ids[0]);
            if ($offset < $total_count - 1) {
                $next = ifset($ids[2]);
            }
        } else {
            if ($offset < $total_count - 1) {
                $next = ifset($ids[1]);
            }
        }
        return array(
            'total_count' => $total_count,
            'offset' => $offset,
            'prev' => $prev,
            'next' => $next
        );

    }

    public function backendContactInfo($params)
    {
        $contact_id = wa()->getUser()->getId();
        if (!empty($params['contact_id'])) {
            $contact_id = $params['contact_id'];
        }

        $res = array();

        $cr = new contactsRightsModel();
        if ($cr->getRight(null, $contact_id) !== 'read') {
            $asm = new waAppSettingsModel();
            if (!$asm->get('contacts', 'tags_disabled')) {
                $res['before_header'] = contactsProHelper::chainViewAction(
                    new contactsProPluginContactsInfoTagsAction(array(
                      'contact_id' => $contact_id
                    ))
                );
            }
        }

        return $res;

    }

    private function profileActivityTab($contact_id)
    {
        $events = new contactsEvents(array(
            'contact_id' => $contact_id
        ));

        $count = $events->getTotalCount();
        if (!$count) {
            return null;
        }

        return array(
            'id' => 'activity',
            'hash' => 'activity',
            'html' => '',
            'url' => wa()->getAppUrl('contacts').'?plugin=pro&module=events&contact_info_tab=1&category=log&query=contact_id%3D'.$contact_id,
            'count' => 0,
            'title' => _wp('Activity') . ' <span class="tab-count">('.$count.')</span>'
        );
    }

    private function profileNotesTab($contact_id)
    {
        $model = new contactsNotesModel();
        $count = $model->countByField('contact_id', $contact_id);
        return array(
            'id' => 'notes',
            'hash' => 'notes',
            'html' => '',
            'url' => wa()->getAppUrl('contacts').'?plugin=pro&module=notes&action=contactInfo&id='.$contact_id,
            'count' => 0,
            'title' => _wp('Notes').' <span class="tab-count">('.$count.')</span>',
        );
    }

    private function profileEventsTab($contact_id)
    {
        $cem = new contactsEventModel();
        $cecm = new contactsEventContactsModel();
        $count_cem = $cem->countByField(array(
            'contact_id' => $contact_id
        ));
        $count_cecm = $cecm->countByField(array(
            'contact_id' => $contact_id
        ));
        $count = $count_cem + $count_cecm;

        if ($count) {
            $query = urlencode('contact_id=' . $contact_id);
            return array(
                'id' => 'events',
                'hash' => 'events',
                'html' => '',
                'url' => wa()->getAppUrl('contacts') . '?plugin=pro&module=eventsContactTabEvent&query=' . $query,
                'count' => 0,
                'title' => _wp('Events') . ' <span class="tab-count">(' . $count . ')</span>'
            );
        } else {
            return array();
        }
    }

    public function searchForm()
    {
        return trim(wao(new contactsProPluginSearchAction())->display());
    }

    private function loc()
    {
        $strings = array();
        foreach(array(
            'New person', 'New company', 'Note', 'New note', 'Event', 'New event',
            'Import...', 'All notes', 'Are you sure?', 'Delete field', 'Disable field',
            'Field constructor', 'activities', 'Display a column', 'Email address is not specified',
            'Name of list', 'Icon', 'This list can see', 'only me', 'all users', 'Show all fields',
            'Search contacts', 'Save as filter', 'All events', 'Change search conditions',
            'You can grant access to your account backend to any existing contact. To do so, <a href="#/contacts/search/">find a contact</a> and then customize access rights on Account tab.',
            'Or <a href="#/contacts/add/">create a new contact</a> and customize access rights on Account tab.',
            'Required field', 'No search conditions specified', 'Map', '<no-name>',
            'Webasyst uses Google Maps service to identify geolocation associated with a contact address. This occurs when you add or edit contacts. However, we do not send requests to Google while you perform bulk operations, e.g. contact import, because of Google limits.Also contacts added before Webasyst Contacts Pro plugin installation do not have geolocation info in their records.',
            'ADVICE: To update geolocation for any contact, open it, enter a valid address, and click Save button.',
            'Why?', 'Close', 'Why some contacts do not have geolocation info', 'No users in this group.',
            'To add users to group, go to <a href="#/users/all/">All users</a>, select them, and click <strong>Actions with selected / Add to group</strong>.',
            'New list',
        ) as $s) {
            $strings[$s] = _wp($s);
        }
        return $strings;
    }

    public function delete($ids)
    {

        $fm = new contactsFormModel();
        $fm->updateByField('contact_id', $ids, array('contact_id' => 0));

        $em = new contactsEventModel();
        $em->updateByField('contact_id', $ids, array('contact_id' => 0));

        $ctm = new contactsContactTagsModel();
        $has_tags = $ctm->countByField(array(
            'contact_id' => $ids
        )) > 0;

        foreach (array(
            new contactsNotesModel(),
            new contactsNotificationBirthdaysModel(),
            new contactsNotificationLogsModel(),
            new contactsNotificationEventsModel(),
            new contactsViewModel(),
            new contactsEventContactsModel(),
            $ctm
        ) as $m)
        {
            $m->deleteByContacts($ids);
        }

        if ($has_tags) {
            $tag_model = new contactsTagModel();
            $tag_model->recount();
        }

    }

    public function merge($params)
    {
        if (empty($params['id'])) {
            return;
        }
        $master_id = (int) $params['id'];
        if (!$master_id) {
            return;
        }
        if (!empty($params['contacts'])) {
            $contact_ids = array_map('intval', $params['contacts']);

            $nm = new contactsNotesModel();
            $nm->updateByField('contact_id', $contact_ids, array('contact_id' => $master_id));

            $fm = new contactsFormModel();
            $fm->updateByField('contact_id', $contact_ids, array('contact_id' => $master_id));

            $em = new contactsEventModel();
            $em->updateByField('contact_id', $contact_ids, array('contact_id' => $master_id));

            $ctm = new contactsContactTagsModel();
            $tag_ids = array_keys($ctm->getTags($contact_ids + array($master_id)));
            $ctm->assign($master_id, $tag_ids);
            $ctm->deleteByField('contact_id', $contact_ids);

            foreach (array(
                new contactsViewModel(),
                new contactsEventContactsModel(),
                new contactsNotificationLogsModel(),
                new contactsNotificationBirthdaysModel(),
                new contactsNotificationEventsModel(),
            ) as $m) {
                $m->deleteByContacts($contact_ids);
            }

        }
    }

    public function contactsCollection(&$params)
    {
        /**
        * @var waContactsCollection
        */
        $collection = $params['collection'];
        $hash = $collection->getHash();

        $processed = false;

        if ($hash) {
            switch ($hash[0]) {
                case 'prosearch':
                    contactsSearchHelper::prepare($collection, isset($hash[1]) ? $hash[1] : '', $params['auto_title']);
                    $processed = true;
                    break;
                case 'view':
                    contactsProHelper::viewPrepeare($collection, isset($hash[1]) ? $hash[1] : '', $params['auto_title']);
                    $processed = true;
                    break;
                case 'import':
                    contactsProHelper::importPrepare($collection, isset($hash[1]) ? $hash[1] : '', $params['auto_title']);
                    $processed = true;
                    break;
                case 'tag':
                    contactsProHelper::tagPrepare($collection, isset($hash[1]) ? $hash[1] : '', $params['auto_title']);
                    $processed = true;
                default:
                    $processed = false;
                    break;
            }
        }
        return $processed;
    }

    public function shopBackendCustomersList(&$params)
    {
        if (!empty($params['hash'])) {
            $hash = $params['hash'];
            $url = wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl()."/contacts/";
            if (strpos($hash, 'search/') === 0) {

                // sanitize
                $hash = trim(trim(str_replace('search/', '', $hash)), '/');

                if (strstr($hash, 'app.total_spent') !== false) {
                    $hash = preg_replace("/app\.total_spent(>|<|=|<=|>=)([\d\-\.]+)/", "shop.customers.total_spent$1$2&shop.customers.payed_orders=1", $hash);
                }

                // decoding
                foreach (array(
                    'app.orders_total_sum' => 'shop.customers.total_spent',
                    'app.payment_method' => 'shop.placed_orders.payment_method',
                    'app.shipment_method' => 'shop.placed_orders.shipment_method',
                    'app.product' => 'shop.purchased_product.product',
                    'app.order_datetime' => 'shop.purchased_product.period.period',
                    'app.' => 'shop.customers.'
                ) as $from => $to)
                {
                    $hash = str_replace($from, $to, $hash);
                }

                // cause we need customers not just contacts
                if (strstr($hash, 'shop.') === false) {
                    $prefix = 'shop.placed_orders.period.period<='.date('Y-m-d');
                    $hash .= $hash ? ('&' . $prefix) : $prefix;
                }

                $hash = str_replace('/', '\\\\\\\/', $hash);

               return array(
                   'top_li' => '<input type="button" onclick="location.href=\''.$url.'#/contacts/search/'.$hash.'/1/\'" value="'._wp('Open in Contacts').'">',
                );
            } else if (preg_match('/^([a-z_0-9]*)\//', $hash, $match)) {
                $url .= str_replace($match[1] . '/', "#contacts/search/shop_customers\\\/{$match[1]}=", $hash);
                return array(
                    'top_li' => '<input type="button" onclick="location.href=\''.$url.'/1/\'" value="'._wp('Open in Contacts').'">',
                );
            } else {
                $url .= '#contacts/search/shop_customers\\\/' . $hash;
                return array(
                    'top_li' => '<input type="button" onclick="location.href=\''.$url.'/1/\'" value="'._wp('Open in Contacts').'">',
                );
            }
        }
        return null;
    }

}