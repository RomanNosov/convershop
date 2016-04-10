<?php
class shopOrderPayActions extends waJsonActions{
    private $secretKey = 'ec3705053b9cefc30150a7ac7b2837ff';
    private $salt = 'supersalt';

	function sendCheckAction(){
		$order_model = new shopOrderModel();
        $order = $this->checkRequest();
        if(waRequest::issetPost('sum') === false){
            $this->response = array('result' => false, 'message' => 'Сумма не определена');
            return;
        }
        $customer_contact = new waContact($order['contact_id']);
        $customer_phone = $customer_contact->get('phone');
        $customer_name = $customer_contact->get('name');
        $customer_contact = $customer_contact->get('email');
        $params = array(
        	'sp_amount' => waRequest::post('sum'),
            'sp_description' => 'Оплата заказа №'.$order['id'],
            'sp_order_id' => $order['id'],
            'sp_user_name' => $customer_name,
            'sp_user_phone' => $customer_phone[0]['value'],
            'sp_user_contact_email' => $customer_contact[0]['value'],
            'sp_payment_system' => 'CARDPSB'
            // 'sp_payment_system' => 'TESTCARD'
        );
        $orderChangeArray = array('state_id' => 'ozhidaet-oplaty-');
        if($order['total'] != $params['sp_amount']){
            $orderChangeArray['total'] = $params['sp_amount'];
        }
        $link = $this->makeLink('payment', $params);
        $body = $order_model->query('SELECT value FROM shop_notification_params WHERE notification_id = 13 AND `name` = "body" LIMIT 1');
        $body = $body->fetchAll();
        $subject = $order_model->query('SELECT value FROM shop_notification_params WHERE notification_id = 13 AND `name` = "subject" LIMIT 1');
        $subject = $subject->fetchAll();
        $subject = str_replace('{$order.id}', $order['id'], $subject[0]['value']);
        $body = str_replace('{$order.id}', $order['id'], $body[0]['value']);
        $body = str_replace('{$link}', $link, $body);
        $mail_message = new waMailMessage($subject, $body);
        $mail_message->setFrom('convershop-chet@mail.ru', 'Поддержка Convershop.ru');
        // $mail_message->setTo('neket313@gmail.com');
        $mail_message->setTo($customer_contact[0]['value']);
        $result = $mail_message->send();
        if($result){
            $order_model->updateById($order['id'], $orderChangeArray);
            $smsText = $order_model->query('SELECT value FROM shop_notification_params WHERE notification_id = 14 AND `name` = "text" LIMIT 1');
            $smsText = $smsText->fetchAll();
            $smsText = str_replace('{$order.id}', $order['id'], $smsText[0]['value']);
            $smsText = str_replace('{$link}', $this->cropLink($link), $smsText);
            $this->sendSMS($smsText, $customer_phone[0]['value']);
        }
        $this->response = $result;
	}

    function payBackAction(){
//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);
        $order = $this->checkRequest();
        $result = $order['sp_payment_id'];
        if($order['sp_payment_id'] === null || $order['state_id'] != 'paid'){
            $this->response = array('result' => false, 'message' => 'Заказ не оплачивался через SimplePay');
            return;
        }
        $link = $this->makeLink(
            'refund', 
            array('sp_description' => 'Возвращён менеджером','sp_payment_id' => $order['sp_payment_id'])
        );
        $ch = curl_init($link);

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30
        ));
        $result = json_decode(json_encode(simplexml_load_string(curl_exec($ch))), true);
        curl_close($ch);

        if(!isset($result['sp_sig'])){
            $this->response = array('result' => false, 'message' => 'Нет подписи SimplePay');
            return;
        }
        $sig = $result['sp_sig'];
        unset($result['sp_sig']);
        ksort($result);
        if (md5('refund;'.join(';', $result).';'.$this->secretKey) != $sig) {
            $this->response = array('result' => false, 'message' => 'Не верный ответ от SimplePay');
            return;
        }
        if($result['sp_status'] === 'error'){
            $this->response = array('result' => false, 'message' => $result['sp_error_description']);
            return;
        } else {
            (new shopOrderModel())->updateById($order['id'], array('state_id' => 'vozvrat', 'sp_payment_id' => null));
            $this->response = array('result' => true, 'message' => 'Платёж успешно возвращён клиенту');
            return;
        }
    }

    private function checkRequest(){
        $model = new shopOrderModel();
        if (waRequest::issetPost('id') === false  || !($order = $model->getById(waRequest::post('id')))) {
            $this->response = array('result' => false, 'message' => 'Заказ не найден');
            return;
        }
        return $order;
    }

    private function makeLink($script, $params){
        $params['sp_salt'] = $this->salt;
        $params['sp_outlet_id'] = 453;
        ksort($params);
        $linkParams = array();
        foreach ($params as $key => $value) {
            $linkParams[] = $key.'='.$value;
        }
        return 'http://api.simplepay.pro/sp/'.$script.'?'.join('&', $linkParams).'&sp_sig='.md5($script.';'.join(';', $params).';'.$this->secretKey);
    }

    private function sendSMS($text, $phone){
        $ch = curl_init("http://sms.ru/sms/send");
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => array(
                "api_id" =>  "A81601B4-2C66-0517-F78C-7507B11D7193",
                "to"     =>  $phone,
                "text"   =>  $text,
                // 'translit' => 1
            )));
        $body = curl_exec($ch);
        curl_close($ch);
    }

    private function cropLink($link){
        $ch = curl_init("http://qps.ru/api?url=".$link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $cropedLink = curl_exec($ch);
        curl_close($ch);
        return $cropedLink;
    }
}