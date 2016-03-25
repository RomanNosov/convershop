<?php

class contactsProPluginSearchAction extends waViewAction
{
    public function execute()
    {
        $hash = $this->getHash();
        $config = contactsSearchHelper::getConfig();
        $type = $this->getRequest()->request('type', 'simple');
        $hash_ar = contactsSearchHelper::parseHash($hash);
        $items = array();

        $app_id = 'contacts';

        $conds = $hash_ar['conds'];

        if ($conds) {
            foreach ($conds as $section_id => $cond) {
                foreach ($cond as $item_id => $item_cond_ar) {
                    if (!$this->isNumericArray($item_cond_ar)) {
                        $item_cond_ar = array($item_cond_ar);
                    }
                    foreach ($item_cond_ar as $item_cond) {
                        if (isset($item_cond['val'])) {
                            $h = "{$section_id}.{$item_id}{$item_cond['op']}" . contactsSearchHelper::escape($item_cond['val']);
                        } else {
                            $h = array();
                            foreach ($item_cond as $key => $val_item) {
                                if (isset($val_item['val'])) {
                                    $h[] = "{$section_id}.{$item_id}.{$key}{$val_item['op']}{$val_item['val']}";
                                } else {
                                    foreach ($val_item as $k => $vl_item) {
                                        if (!isset($vl_item['val'])) {
                                            continue;
                                        }
                                        $h[] = "{$section_id}.{$item_id}.{$key}.{$k}{$vl_item['op']}{$vl_item['val']}";
                                    }
                                }
                            }
                            $h = implode('&', $h);
                        }
                        $it = contactsSearchHelper::getItem("{$section_id}.{$item_id}", $h, array(
                            'unwrap_values' => array(
                                'when_readonly' => true
                            ),
                            'count' => false
                        ));
                        $items[] = array(
                            'item_id' => $it['id'],
                            'item' => $it,
                            'count' => '',
                            'conds' => $item_cond
                        );
                    }
                }
            }

        } else {
            // default items
            $item_ids = contactsSearchHelper::getContactItems();
            if (!$item_ids) {
                $item_ids = array(
                    'contact_info.name',
                    'contact_info.email',
                    'contact_info.phone',
                );
            }
            $items = $this->getItems($item_ids);
            if (!$items) {
                $item_ids = array(
                    'contact_info.name',
                    'contact_info.email',
                    'contact_info.phone',
                );
                $items = $this->getItems($item_ids);
            }
            if ($items) {
                $item_ids = array();
                foreach ($items as $item) {
                    $item_ids[] = $item['item_id'];
                }
                contactsSearchHelper::setContactItems($item_ids);
            } else {
                contactsSearchHelper::delContactItems();
            }
        }

        $this->view->assign(array(
            'config' => $config,
            'items' => $items,
            'type' => $type,
            'hash' => $hash,
            'app_url' =>  wa()->getAppUrl($app_id),
            'static_url' => wa()->getAppStaticUrl($app_id),
            'sidebar_map' => $this->getSidebarMap(),
            'lang' => substr(wa()->getLocale(), 0, 2)
        ));

        $plugin_path = wa($app_id)->getConfig()->getPluginPath('pro');
        $template_path = 'templates/actions/search/Search.html';
        $this->setTemplate("{$plugin_path}/{$template_path}");

    }

    public function isNumericArray($ar)
    {
        return is_array($ar) && count(array_filter(array_keys($ar), "is_numeric")) === count($ar);
    }

    public function getItems($item_ids)
    {
        $items = array();
        foreach ($item_ids as $item_id) {
            $item = contactsSearchHelper::getItem($item_id, null, array(
                'unwrap_values' => array(
                    'when_readonly' => true
                ),
                'count' => false
            ));
            if ($item) {
                $items[] = array(
                    'item_id' => $item_id,
                    'item' => $item,
                    'conds' => array()
                );
            }
        }
        return $items;
    }

    public function getHash()
    {
        return urldecode($this->getRequest()->request('hash'));
    }

    public function getSidebarMap()
    {
        $contact = wa()->getUser();
        $map = $contact->getSettings('contacts', 'prosearch_sidebar', '');
        if ($map) {
            $map = array_fill_keys(explode(',', $map), 1);
        } else {
            $map = array();
        }
        if (!$map) {
            $map['contact_info'] = 1;
        }
        return $map;
    }

}