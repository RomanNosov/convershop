<?php

class shopWorkflowChangeToCustomStatusAction extends shopWorkflowShipAction {

    public function execute($params = null) {
        if ($message_text = waRequest::post('message_text')) {
            $order_id = waRequest::post('id');
            $shopOrderParamsModel = new shopOrderParamsModel();
            $shopOrderParamsModel->setOne($order_id, 'custom_status', $message_text);
             return true;
        } else {
            return true;
        }
    }

}