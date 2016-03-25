<?php

$app_id = 'contacts';

$_files = array();

// rm actions
$_files[wa($app_id)->getAppPath().'/plugins/pro/lib/actions/'] = array(
    'form/contactsProPluginFormField.action.php',
    'form/contactsProPluginFormFieldDelete.controller.php',
    'form/contactsProPluginFormFieldSave.controller.php'
);

// rm templates
$_files[wa($app_id)->getAppPath().'/plugins/pro/templates/actions/'] = array(
    'form/FormField.html'
);

foreach ($_files as $path => $fls) {
    foreach ($fls as $f) {
        $_file = $path . $f;
        if (file_exists($_file)) {
            waFiles::delete($_file, true);
        }
    }
}