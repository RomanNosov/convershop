<?php

class logsRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('delete_files', _w('Can delete files'), 'checkbox');
    }
}
