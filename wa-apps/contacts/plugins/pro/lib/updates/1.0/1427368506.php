<?php

$model = new contactsViewModel();
$prefix = '/contacts/prosearch/';
$prefix_len = strlen($prefix);
$sql = "SELECT * FROM `contacts_view` WHERE type = 'search' AND hash LIKE '%mailer.recipients.status%'";
foreach ($model->query($sql) as $view) {
    $hash = $view['hash'];
    if (substr($hash, 0, $prefix_len) === $prefix) {
        $pattern = "!mailer\.recipients\.status=(\-?[\d]+)!";
        if (preg_match($pattern, $hash, $match)) {
            $status = null;
            switch ($match[1]) {
                case '-2':
                    $status = ':bounced';
                    //preg_replace($pattern, "mailer.recipients.status=:bounced", $hash);
                    break;
                case '1':
                    $status = ':unknown';
                    break;
                case '3':
                    $status = ':read';
                    break;
                case '5':
                    $status = ':unsubscribed';
                    break;
                default:
                    $status = null;
                    break;
            }
            if ($status) {
                $hash = preg_replace($pattern, "mailer.recipients.status={$status}", $hash);
                $model->updateById($view['id'], array(
                    'hash' => $hash
                ));
            }
        }
    }
}