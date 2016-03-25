<?php

class logsBackendDownloadController extends waController
{
    public function execute()
    {
        $path = waRequest::get('path');
        $full_path = logsHelper::getAbsolutePath($path);
        $available = logsHelper::checkPath($full_path, false);
        if ($available) {
            $file_name = basename($full_path);
            waFiles::readFile($full_path, $file_name);
        } else {
            $this->redirect(wa()->getAppUrl());
        }
    }
}
