<?php

class logsBackendActions extends waViewActions
{
    public function preExecute()
    {
        $this->setLayout(new logsBackendLayout());
        $this->view->assign('can_delete_files', $this->getRights('delete_files'));
    }

    public function defaultAction()
    {
        if (strpos(waRequest::server('HTTP_REFERER'), wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl().'/logs') !== 0) {
            //on first app access, show latest updated files
            $this->redirect('?action=files&mode=updatetime');
        } else {
            //otherwise default view mode is root log directory
            $this->execute('directory');
        }
    }

    public function directoryAction()
    {
        $path = waRequest::get('path');
        $this->view->assign('items', logsHelper::getDirectory($path));
    }

    public function fileAction()
    {
        $path = waRequest::get('path');
        $page = waRequest::get('page', null, 'int');
        $file = logsHelper::getFile(array(
            'path' => $path,
            'page' => $page,
        ));
        if ($page !== null && ($page < 1 || $page > $file['page_count'])) {
            $this->redirect('?action=file&path='.$path);
        } else {
            $this->view->assign('file', $file);
        }
    }

    public function filesAction()
    {
        $mode = waRequest::get('mode');
        $method = 'getFilesBy'.ucfirst($mode);
        if (method_exists('logsHelper', $method)) {
            $this->view->assign('items', logsHelper::$method());
        } else {
            $this->redirect(wa()->getAppUrl());
        }
    }
}
