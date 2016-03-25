<?php
// add private routing
$path = wa()->getConfig()->getPath('config', 'routing');
if (file_exists($path) && is_writable($path)) {
    $routing = include($path);

    $contacts_route = array(
        'url' => 'contacts/*',
        'app' => 'contacts',
        'private' => '1'
    );

    foreach ($routing as $domain => $routes) {
        if (is_array($routes)) {
            $route_id = 0;
            $exist = false;
            foreach ($routes as $r_id => $r) {
                if (is_numeric($r_id) && $r_id > $route_id) {
                    $route_id = $r_id;
                }
                if (isset($r['app']) && $r['app'] === 'contacts') {
                    $exist = true;
                }
            }
            $route_id++;

            if (!$exist) {
                $routing[$domain] = array($route_id => $contacts_route) + $routing[$domain];
            }
        }
    }

    waUtils::varExportToFile($routing, $path);
}

$mod = new waModel();

$mod->exec("CREATE TABLE IF NOT EXISTS contacts_signup_temp (
                id INT(11) NOT NULL AUTO_INCREMENT,
                hash VARCHAR(100) NOT NULL,
                data TEXT NOT NULL,
                create_datetime DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX hash (hash ASC))
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8");