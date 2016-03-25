<?php

class dpickpointPickPoint {

    protected $test_url = 'http://e-solution.pickpoint.ru/apitest/';
    protected $url = 'http://e-solution.pickpoint.ru/api/';
    protected $sandbox = false;

    public function __construct($sandbox = false) {
        $this->sandbox = $sandbox;
    }

    protected function getUrl() {
        if ($this->sandbox) {
            return $this->test_url;
        } else {
            return $this->url;
        }
    }

    public function setMode($sandbox) {
        $this->sandbox = $sandbox;
    }

    protected function sendRequest($url, $data = null, $method = 'POST') {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new waException('PHP расширение cURL не доступно');
        }

        if (!($ch = curl_init())) {
            throw new waException('curl init error');
        }

        if (curl_errno($ch) != 0) {
            throw new waException('Ошибка инициализации curl: ' . curl_errno($ch));
        }

        $data = json_encode($data);
        $headers = array("Content-Type: application/json");

        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == 'POST') {
            @curl_setopt($ch, CURLOPT_POST, 1);
            @curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = @curl_exec($ch);
        $app_error = null;
        if (curl_errno($ch) != 0) {
            $app_error = 'Ошибка curl: ' . curl_error($ch);
        }
        curl_close($ch);
        if ($app_error) {
            throw new waException($app_error);
        }
        if (empty($response)) {
            throw new waException('Пустой ответ от сервера');
        }

        $json = json_decode($response, true);

        $return = json_decode($response, true);
        if (!is_array($return)) {
            return $response;
        } else {
            return $return;
        }
    }

    public function login($login, $password) {
        $url = $this->getUrl() . '/login';
        $data = array('Login' => $login, 'Password' => $password);

        $return = $this->sendRequest($url, $data);

        if ($return['ErrorMessage']) {
            throw new Exception($return['ErrorMessage']);
        } else {
            return $return['SessionId'];
        }
    }

    public function logout($SessionId) {
        $url = $this->getUrl() . '/logout';
        $data = array('SessionId' => $SessionId);

        $return = $this->sendRequest($url, $data);
        return $return['Success'];
    }

    public function createSending($SessionId, $Sendings) {
        $url = $this->getUrl() . '/createsending';
        $data = array('SessionId' => $SessionId, 'Sendings' => $Sendings);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function CreateShipment($SessionId, $Sendings) {
        $url = $this->getUrl() . '/createsending';
        $data = array('SessionId' => $SessionId, 'Sendings' => $Sendings);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function makeReturn($SessionId, $InvoiceNumber) {
        $url = $this->getUrl() . '/makereturn';
        $data = array('SessionId' => $SessionId, 'InvoiceNumber' => $InvoiceNumber);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function getReturnInvoicesList($SessionId, $DateFrom, $DatetTo) {
        $url = $this->getUrl() . '/getreturninvoiceslist';
        $data = array('SessionId' => $SessionId, 'DateFrom' => $DateFrom, 'DatetTo' => $DatetTo);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function trackSending($SessionId, $InvoiceNumber) {
        $url = $this->getUrl() . '/tracksending';
        $data = array('SessionId' => $SessionId, 'InvoiceNumber' => $InvoiceNumber);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function sendingInfo($SessionId, $InvoiceNumber) {
        $url = $this->getUrl() . '/sendinginfo';
        $data = array('SessionId' => $SessionId, 'InvoiceNumber' => $InvoiceNumber);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function getDeliveryCost($SessionId, $Sendings) {
        $url = $this->getUrl() . '/getdeliverycost';
        $data = array('SessionId' => $SessionId, 'Sendings' => $Sendings);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function courier($SessionId, $IKN, $SenderCode, $City, $Address, $FIO, $Phone, $Date) {
        $url = $this->getUrl() . '/courier';
        $data = array('SessionId' => $SessionId, 'IKN' => $IKN, 'SenderCode' => $SenderCode, 'City' => $City,
            'Address' => $Address, 'FIO' => $FIO, 'Phone' => $Phone, 'Date' => $Date);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function makeReestr($SessionId, $Invoices) {
        $url = $this->getUrl() . '/makereestr';
        $data = array('SessionId' => $SessionId, 'Invoices' => $Invoices);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function makeReestrNumber($SessionId, $Invoices) {
        $url = $this->getUrl() . '/makereestrnumber';
        $data = array('SessionId' => $SessionId, 'Invoices' => $Invoices);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function makeLabel($SessionId, $Invoices) {
        $url = $this->getUrl() . '/makelabel';
        $data = array('SessionId' => $SessionId, 'Invoices' => $Invoices);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function cityList() {
        $url = $this->getUrl() . '/citylist';
        $return = $this->sendRequest($url, null, 'GET');
        return $return;
    }

    public function postamatList() {
        $url = $this->getUrl() . '/postamatlist';
        $return = $this->sendRequest($url, null, 'GET');
        return $return;
    }

    public function getZone($SessionId, $FromCity, $ToPT = null) {
        $url = $this->getUrl() . '/getzone';
        $data = array('SessionId' => $SessionId, 'FromCity' => $FromCity, 'ToPT' => $ToPT);

        $return = $this->sendRequest($url, $data);

        return $return;
    }

    public function getStates() {
        $url = $this->getUrl() . '/getstates';
        $return = $this->sendRequest($url, null, 'GET');
        return $return;
    }

}
