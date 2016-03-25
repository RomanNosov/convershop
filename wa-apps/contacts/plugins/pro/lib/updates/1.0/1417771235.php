<?php

$m = new waModel();

$m->exec("UPDATE `contacts_event` 
    SET end_datetime = NULL
    WHERE end_datetime IS NOT NULL AND end_datetime < start_datetime");
