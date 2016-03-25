<?php

class logsBackendNavigationAction extends waViewAction
{
    public function execute()
    {
        if ($path = waRequest::get('path')) {
            $this->view->assign('breadcrumbs', logsHelper::getBreadcrumbs($path));
        } else {
            $action = waRequest::get('action');
            $mode = waRequest::get('mode', '');
            $view_modes = array(
                array(
                    'action' => '',
                    'mode'   => '',
                    'url'    => 'logs/',
                    'title'  => _w('View files by directory'),
                    'sort'   => 0,
                    'icon'   => 'folders',
                ),
                array(
                    'action' => 'files',
                    'mode'   => 'updatetime',
                    'url'    => 'logs/?action=files&mode=updatetime',
                    'title'  => _w('Sort files by update time'),
                    'sort'   => 1,
                    'icon'   => 'bytime',
                ),
                array(
                    'action' => 'files',
                    'mode'   => 'size',
                    'url'    => 'logs/?action=files&mode=size',
                    'title'  => _w('Sort files by size'),
                    'sort'   => 2,
                    'icon'   => 'bysize',
                ),
            );
            foreach ($view_modes as &$view_mode) {
                $view_mode['selected'] = $view_mode['action'] == $action && $view_mode['mode'] == $mode;
            }
            usort($view_modes, create_function(
                '$a, $b',
                'if ($a["selected"] != $b["selected"]) {
                    return $b["selected"] ? 1 : -1;
                } else {
                    return $a["sort"] < $b["sort"] ? -1 : 1;
                }'
            ));
            $this->view->assign('view_modes', $view_modes);
            $this->view->assign('total_size', logsHelper::getTotalLogsSize());
        }
    }
}
