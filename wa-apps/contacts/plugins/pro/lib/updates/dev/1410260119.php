<?php

// rm actions
$path = wa()->getAppPath().'/plugins/pro/lib/actions/';
$_files = array(
    'search/contactsProPluginSearchCountByCond.controller.php',
    'search/contactsProPluginSearch.controller.php',
    'search/contactsProPluginSearchHistoryDelete.controller.php',
    'search/contactsProPluginSearchHistory.action.php',
    'search/contactsProPluginSearchAllValues.action.php',
    'search/contactsProPluginSearchAllValues.controller.php',
    'search/contactsProPluginSearchAutocomplete.controller.php',
    'search/contactsProPluginNotesAdd.controller.php',
    'view/contactsProPluginViewViews.action.php',
    'view/contactsProPluginViewDelete.action.php',
    'contacts/contactsProPluginContactsAbcIndex.action.php',
    'list/contactsProPluginListDelete.controller.action.php',
    'events/contactsProPluginEventsFilters.controller.php',
    'events/contactsProPluginEventsAdd.action.php',
    'contacts/contactsProPluginContactsImport.action.php',
    'contacts/contactsProPluginContactsImportProcess.action.php',
    'contacts/contactsProPluginContactsImportProcess2.controller.php',
    'contacts/contactsProPluginContactsImportUpload.action.php',
    'contacts/contactsProPluginContactsImportUpload.controller.php'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file);
    }
}

// rm models
$path = wa()->getAppPath().'/plugins/pro/lib/models/';
$_files = array(
    'contactsLogNotifications.model.php'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file);
    }
}

// rm templates
$path = wa()->getAppPath().'/plugins/pro/templates/actions/';
$_files = array(
    'search/SearchHistory.html',
    'search/SearchAllValues.html',
    'view/ViewViews.html',
    'contacts/ContactsAbcIndex.html',
    'view/ViewDelete.html',
    'events/EventsAdd.html',
    'contacts/ContactsImport.html',
    'contacts/ContactsImportProcess.html',
    'contacts/ContactsImportUpload.html'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file);
    }
}

// rm classes
$path = wa()->getAppPath().'/plugins/pro/lib/classes/';
$_files = array(
    'contactsSearchCollection.class.php',
    'contactsCollection.class.php'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file);
    }
}

// rm imgs
$path = wa()->getAppPath().'/plugins/pro/img/';
$_files = array(
    'edit-column.png'
);
foreach ($_files as $f) {
    $_file = $path . $f;
    if (file_exists($_file)) {
        waFiles::delete($_file);
    }
}