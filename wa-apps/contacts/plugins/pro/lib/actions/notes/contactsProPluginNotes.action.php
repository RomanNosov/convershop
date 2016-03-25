<?php

class contactsProPluginNotesAction extends waViewAction
{
    public function execute()
    {
        $m = new contactsNotesModel();
        $offset = $this->getRequest()->request('offset', 0, waRequest::TYPE_INT);
        $limit = $this->getRequest()->request('count', 30, waRequest::TYPE_INT);
        $query = $this->getQuery();
        $order = $this->getRequest()->request('order', 0, waRequest::TYPE_INT);
        
        $result = $m->searchNotes(array(
            'offset' => $offset,
            'limit' => $limit,
            'query' => $query,
            'sort' => 'create_datetime ' . ($order ? 'ASC' : 'DESC')
        ));
        
        $this->view->assign(array(
            'notes' => $result['notes'],
            'total_count' => $result['count'],
            'params' => array(
                'offset' => $offset,
                'count' => $limit
            ),
            'query' => $query,
            'order' => $order
        ));
    }
    
    public function getQuery()
    {
        $str = $this->getRequest()->request('query', '', waRequest::TYPE_STRING_TRIM);
        
        $escapedAmp = 'ESCAPED_AMPERSAND';
        while(FALSE !== strpos($str, $escapedAmp)) {
            $escapedAmp .= rand(0, 9);
        }
        $query = str_replace('\\&', $escapedAmp, $str);
        
        $q = array();
        if ($query) {
            foreach (explode('&', $query) as $s) {
                $p = explode('=', $s);
                if (empty($p[1])) {
                    continue;
                }
                $p[1] = trim($p[1]);
                if (!$p[1]) {
                    continue;
                }
                $q[$p[0]] = array();
                foreach (explode(' ', $p[1]) as $t) {
                    $t = urldecode(trim($t));
                    if (!$t) {
                        continue;
                    }
                    $q[$p[0]][] = $t;
                }
                if ($q[$p[0]]) {
                    $q[$p[0]] = implode(' ', $q[$p[0]]);
                }
            }
        }
        foreach ($q as &$v) {
            $v = str_replace($escapedAmp, '&', $v);
        }
        unset($v);
        
        return $q;
    }
}