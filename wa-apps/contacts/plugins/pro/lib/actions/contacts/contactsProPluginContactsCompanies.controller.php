<?php

class contactsProPluginContactsCompaniesController extends waJsonController
{
    public function execute()
    {
        $offset = $this->getRequest()->request('offset', 0, 'int');
        $limit = $this->getRequest()->request('limit', 10, 'int');
        $term = $this->getRequest()->request('term', null, waRequest::TYPE_STRING_TRIM);
        
        $hash = '';
        if ($term) {
            $hash = '/search/company*='.$term;
        }
        
        $collection = new contactsCollection($hash);
        $collection->addWhere("is_company = 1 AND (company IS NOT NULL AND company != '')");
        $collection->orderBy('c.company');
        $term_safe = htmlspecialchars($term);
        $contacts = $collection->getContacts('id,company', $offset, $limit);
        foreach ($contacts as $c) {
            $c['value'] = $c['company'];
            $c['label'] = $this->prepare($c['value'], $term_safe);
            $this->response[] = $c;
        }
    }    
    
    public function display()
    {
        $this->getResponse()->sendHeaders();
        echo json_encode($this->response);
    }
    
    protected function prepare($str, $term_safe)
    {
        $str = htmlspecialchars($str);
        $reg = array();
        foreach (preg_split("/\s+/", $term_safe) as $t) {
            $t = trim($t);
            if ($t) {
                $reg[] = preg_quote($t, '~');
            }
        }
        if ($reg) {
            $reg = implode('|', $reg);
            $str = preg_replace('~('.$reg.')~ui', '<span class="bold highlighted">\1</span>', $str);
        }
        return $str;
    }
    
}

// EOF
