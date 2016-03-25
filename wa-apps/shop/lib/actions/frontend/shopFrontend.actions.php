<?php
class shopFrontendActions extends waJsonActions{
	function resultAction(){
        $order_model = new shopOrderModel();
        $req = waRequest::request();
        $sig = $req['sp_sig'];
        unset($req['sp_sig'],$req['module'],$req['action']);
        ksort($req);
        $order = $order_model->getById($req['sp_order_id']);
        $secretKey = 'ec3705053b9cefc30150a7ac7b2837ff';
        $salt = 'supersalt';
        header("Content-type: text/xml");
        if (isset($req['sp_order_id']) === false  || !($order = $order_model->getById($req['sp_order_id'])) || $req['sp_result'] === false || md5(';'.join(';', $req).';'.$secretKey) != $sig) {
            die('<?xml version="1.0" encoding="utf-8"?>
                    <response>
                    <sp_description>Не удалось обработать платёж</sp_description>
                    <sp_salt>'.$salt.'</sp_salt>
                    <sp_status>error</sp_status>
                    <sp_sig>'.md5(';Не удалось обработать платёж;'.$salt.';error;'.$secretKey).'</sp_sig>
                    </response>');
        }
        if($req['sp_result'] == '1'){
    		$order_model->updateById($order['id'], array('state_id' => 'paid', 'sp_payment_id' => $req['sp_payment_id'], 'sp_payment_status' => 'succeed'));
        } else {
            $order_model->updateById($order['id'], array('sp_payment_status' => 'cancelled'));
        }
        die('<?xml version="1.0" encoding="utf-8"?>
                <response>
                <sp_salt>'.$salt.'</sp_salt>
                <sp_status>ok</sp_status>
                <sp_sig>'.md5(';'.$salt.';ok;'.$secretKey).'</sp_sig>
                </response>');
	}
}