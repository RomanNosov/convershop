<?php

class logsBackendDeleteController extends waJsonController
{
    public function execute()
    {
        $deleted = false;
        if ($this->getRights('delete_files')) {
            $path = waRequest::post('path');
            if ($path) {
                $full_path = logsHelper::getAbsolutePath($path);
                if (!is_dir($full_path)) {
                    $available = logsHelper::checkPath($full_path, false);
                    if ($available) {
                        $deleted = waFiles::delete($full_path);
                    }
                }
            }
        }
        if ($deleted) {
            $update_total_size = waRequest::get('update_size', 0, waRequest::TYPE_INT) == 1;
            $this->response['total_size'] = $update_total_size ? logsHelper::getTotalLogsSize() : '';
        } else {
            $this->errors[] = _wp('File cannot be deleted');
        }
    }
}
