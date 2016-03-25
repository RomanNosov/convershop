<?php

class logsBackendGetmoreController extends waJsonController
{
    public function execute()
    {
        $file = logsHelper::getFile(array(
            'path'       => waRequest::post('path'),
            'first_line' => waRequest::post('first_line', 0, 'int'),
            'last_line'  => waRequest::post('last_line', 0, 'int'),
            'direction'  => waRequest::post('direction'),
            'check'      => false,
        ));

        if ($file['error']) {
            $this->errors[] = $file['error'];
            $this->response['redirect_url'] = $file['return_url'];
        } else {
            $template = wa()->getAppPath('templates/actions/backend/BackendGetmore.html');
            $view = wa()->getView();
            $view->assign('html', $file['contents']);
            $html = $view->fetch($template);
            if (strlen($html)) {
                $this->response = array(
                    'contents'   => $view->fetch($template),
                    'first_line' => $file['first_line'],
                    'last_line'  => $file['last_line'],
                );
            } else {
                $this->response = array(
                    'contents' => null,
                );
            }
        }
    }
}
