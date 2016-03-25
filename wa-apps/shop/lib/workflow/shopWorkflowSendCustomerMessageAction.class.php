<?php

class shopWorkflowSendCustomerMessageAction extends shopWorkflowShipAction {

    public function execute($params = null) {
        if ($message_text = waRequest::post('message_text')) {
            return array(
                'text' => 'Клиенту отправлено сообщение: ' . $message_text, //эта строка будет записана в лог действий с заказом
                'params' => array(//это массив параметров, которые будут доступны в шаблоне email-уведомления, отправляемого при выполнении действия
                'message_text' => $message_text, //в данном случае нужно включить введенный текст в сообщение, которое отправится клиенту
                ),
            );
        } else {
            return true;
        }
    }

}