<?php

class contactsProPluginSettingsRegionsAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $cm = new waCountryModel();
        $rm = new waRegionModel();

        $country = waRequest::request('country');
        if ($country && waRequest::post()) {
            $this->saveFromPost($rm, $cm, $country);
        }

        $countries = $cm->all();

        if (!$country || empty($countries[$country])) {
            $country = wa()->getSetting('country');
        }
        if (!$country || empty($countries[$country])) {
            $fav = null;
            foreach ($countries as $k => $country) {
                if ($country['fav_sort'] !== null) {
                    $fav = $k;
                    break;
                }
            }
            $country = $fav ? $fav : key($countries);
        }

        $regions = $country ? $rm->getByCountry($country) : array();

        $this->view->assign(array(
            'countries' => $cm->allWithFav($countries),
            'country' => ifset($countries[$country], $cm->getEmptyRow()),
            'regions' => $regions
        ));
    }
    
    protected function saveFromPost($rm, $cm, $country)
    {
        if (waRequest::post('fav')) {
            $region = waRequest::post('region');
            $fav_sort = waRequest::post('fav_sort');
            if ($fav_sort === '') {
                $fav_sort = null;
            }
            if ($region) {
                $rm->updateByField(array(
                    'country_iso3' => $country,
                    'code' => $region,
                ), array(
                    'fav_sort' => $fav_sort,
                ));
            } else {
                $cm->updateByField('iso3letter', $country, array(
                    'fav_sort' => $fav_sort,
                ));
            }
            echo json_encode(array(
                'status' => 'ok',
                'data' => 'ok',
            ));
            exit;
        }

        $region_codes = waRequest::post('region_codes');
        if (!$region_codes || !is_array($region_codes)) {
            $region_codes = array();
        }
        $region_names = waRequest::post('region_names');
        if (!$region_names || !is_array($region_names)) {
            $region_names = array();
        }
        $region_favs = waRequest::post('region_favs');
        if (!$region_favs || !is_array($region_favs)) {
            $region_favs = array();
        }

        $regions = array();
        foreach($region_codes as $i => $code) {
            $code = trim($code);
            $name = trim(ifempty($region_names[$i], ''));
            $fav = trim(ifempty($region_favs[$i], ''));
            if (!$name || !$code) {
                continue;
            }
            $regions[$code] = empty($fav) ? $name : array(
                'name' => $name,
                'fav_sort' => $fav,
            );
        }

        $rm->saveForCountry($country, $regions);

        $country_fav = waRequest::post('country_fav', null, 'int');
        $cm->updateByField('iso3letter', $country, array(
            'fav_sort' => ifempty($country_fav),
        ));
    }
    
}

// EOF
