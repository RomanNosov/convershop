<?php

$vm = new contactsViewModel();
$vm->exec("UPDATE contacts_view SET hash = NULL WHERE hash IS NOT NULL AND SUBSTRING(hash, 1, 6) = '/list/'");