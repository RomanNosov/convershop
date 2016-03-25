<?php

// rm actions
$path = wa()->getAppPath().'/plugins/pro/lib/actions/';
$_files = array(
    'eventlog/',
    'overview/',
    'explore/',
    'backend/contactsProPluginBackendEventlog.action.php'
);

foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file, true);
    }
}


// rm templates
$path = wa()->getAppPath().'/plugins/pro/templates/actions/';
$_files = array(
    'eventlog/',
    'overview/',
    'backend/BackendEventlog.html',
    'explore/',
    'log/'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file, true);
    }
}

// rm classes
$path = wa()->getAppPath().'/plugins/pro/lib/classes/';
$_files = array(
    'contactsEventLog.class.php',
    'contactsOverview.class.php',
    'contactsSearchActivityActionValues.class.php',
    'contactsSearchCountryValues.class.php',
    'contactsSearchCreateMethodValues.class.php',
    'contactsSearchHelper.class.php',
    'contactsSearchRegionValues.class.php'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file, true);
    }
}

// rm js
$path = wa()->getAppPath().'/plugins/pro/js/';
$_files = array(
    'eventlog.js'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file, true);
    }
}

// rm imgs
$path = wa()->getAppPath().'/plugins/pro/img/';
$_files = array(
    'icon/cheque-pen.png',
    'im.png'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file, true);
    }
}

// rm lib/config files
$path = wa()->getAppPath().'/plugins/pro/lib/config/';
$_files = array(
    'overview.php'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file, true);
    }
}