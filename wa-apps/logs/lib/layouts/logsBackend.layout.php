<?php

class logsBackendLayout extends waLayout
{
    public function execute()
    {
        $this->executeAction('navigation', new logsBackendNavigationAction());
        $loc = array(
            'cancel',
            'Delete',
            'Delete file',
            'OK',
            'Settings',
            'nothing received',
        );
        $loc = array_flip($loc);
        foreach ($loc as $key => &$string) {
            $string = _w($key);
        }
        unset($string);
        $this->view->assign('loc', $loc);
        $this->view->assign('action', waRequest::get('action'));
    }
}
