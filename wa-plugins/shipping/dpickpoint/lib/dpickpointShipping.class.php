<?php

/* @property  string login
 * @property  string password
 * @property  string ikn
 * @property  bool sandbox
 * @property  string city
 * @property  array rates
 * @property  array zones
 * @property  float amount_free_delivery
 * @property  float default_price
 * @property  dpickpointPickPoint PickPoint */
class dpickpointShipping extends waShipping {

    protected $PickPoint;

    protected function initControls() {
        $this->registerControl('RatesControl')
                ->registerControl('ZonesRatesControl');

        parent::initControls();
    }

    protected function init() {
        $autoload = waAutoload::getInstance();
        $autoload->add('dpickpointPickPoint', "wa-plugins/shipping/dpickpoint/lib/classes/dpickpointPickPoint.class.php");
        $this->PickPoint = new dpickpointPickPoint();
        parent::init();
    }

    protected function getZoneRate($zone = 0) {
        if (isset($this->zones[$zone]))
            return $this->zones[$zone];
        else
            return false;
    }

    protected function getRate($weight = 0, $volume = 0) {
        $volume_weight = $volume / 5000;
        $weight = max($weight, $volume_weight);
        $price = 0;


        foreach ($this->rates as $rate_weight => $rate_price) {
            if ($rate_weight > $weight) {
                $price = $rate_price;
                break;
            }
        }

        return $price;
    }

    protected function getDeliveryCost($weight = null, $zone = null) {
        if (!$weight) {
            $weight = 1;
        }
        $rate_price = $this->getRate($weight);
        if (!$rate_price) {
            throw new waException('Не удалось рассчитать стоимость. Вес отправления превышает допустимый');
        }
        $zone_rate = $this->getZoneRate($zone);

        if ($zone_rate > 0) {
            $price = $rate_price + $zone_rate * $weight;
        } else {
            $price = $rate_price;
        }
        $price *= 1.25;


        return $price;
    }
    
    public function sendOrder(&$params){
        $order_id = $params['order_id'];
        $shopOrderParamsModel = new shopOrderParamsModel();
        $orderInfo = $shopOrderParamsModel->get($order_id, true);
        if (isset($orderInfo['shipping_params_pickpoint_id'])) {
            $pickpoint_id = $orderInfo['shipping_params_pickpoint_id'];
            $shopOrderItemsModel = new shopOrderItemsModel();
            $items = $shopOrderItemsModel->getItems($order_id);
            $shopOrderModel = new shopOrderModel();
            $order = $shopOrderModel->getOrder($order_id, true);
            $contact = new waContact($order['contact_id']);
            try {
                $this->PickPoint->setMode($this->sandbox);
                $SessionId = $this->PickPoint->login($this->login, $this->password);
                $phone = $contact->get('phone');
                $email = $contact->get('email');
                $data = array(
                    'EDTN' => $order_id,
                    'IKN' => $this->ikn,
                    'Invoice' => array(
                        'SenderCode' => $order['contact_id'],
                        'Description' => 'Обувь',
                        'RecipientName' => $contact->get('name'),
                        'PostamatNumber' => $pickpoint_id,
                        'MobilePhone' => '+' . $phone[0]['value'],
                        'Email' => $email[0]['value'],
                        'PostageType' => $order['state_id'] == 'paid' ? '10001' : '10003',
                        'GettingType' => 102,
                        'PayType' => 1,
                        'Width' => 0,
                        'Height' => 0,
                        'Depth' => 0,
                        'Sum' => $order['state_id'] == 'paid' ? 0 : $order['total'],
                    )
                );
//                die(json_encode($data));
                $response = $this->PickPoint->createSending($SessionId, array($data));
                if($response['CreatedSendings']){
                    $params['html'] = 'Заказ успешно отправлен. Номер отправления PickPoint: '.$response['CreatedSendings'][0]['InvoiceNumber'];
                    return $response['CreatedSendings'];
                } else {
                    $params['html'] = 'Заказ не удалось отправить ('.$response['RejectedSendings'][0]['ErrorCode'].'): '.$response['RejectedSendings'][0]['ErrorMessage'];
                    return $response['RejectedSendings'];
                }

            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $params['html'] = 'Не выбран постомат';
            return 'Не выбран постомат';
        }
    }

    public function calculate() {
        $session = wa()->getStorage();
        $plugin_model = new shopPluginModel();
        $plugin = $plugin_model->getByField('plugin', 'dpickpoint');

        $post = waRequest::post('shipping_'.$plugin['id']) ? waRequest::post('shipping_'.$plugin['id']) : array();
        $pickpoint_id = isset($post['pickpoint_id']) ? $post['pickpoint_id'] : $session->read('pickpoint_id');
        $zone = isset($post['pickpoint_zone']) ? $post['pickpoint_zone'] : $session->read('pickpoint_zone');
        $pickpoint_address = isset($post['pickpoint_address']) ? $post['pickpoint_address'] : $session->read('pickpoint_address');


        if ($pickpoint_id) {

            $session->write('pickpoint_id', $pickpoint_id);
            $session->write('pickpoint_zone', $zone);
            $session->write('pickpoint_address', $pickpoint_address);


            try {


                $weight = $this->getTotalWeight();
                $price = $this->getDeliveryCost($weight, $zone);

                $total = $this->getTotalPrice();
                if ($total > $this->amount_free_delivery) {
                    $price = 0;
                }

                $this->PickPoint->setMode($this->sandbox);
                $SessionId = $this->PickPoint->login($this->login, $this->password);
                $response = $this->PickPoint->getZone($SessionId, $this->city, $pickpoint_id);

                if (isset($response['Zones'][0])) {
                    $min = $response['Zones'][0]['DeliveryMin'];
                    $max = $response['Zones'][0]['DeliveryMax'];
                    if ($min == $max) {

                        if ($min == 1) {
                            $day = "день";
                        } elseif ($min > 1 && $min < 5) {
                            $day = "дня";
                        } else {
                            $day = "дней";
                        }
                        $est_delivery = " $min $day";
                    } else {
                        $est_delivery = " от $min до $max дней";
                    }
                } else {
                    $est_delivery = "Примерно 1 неделя";
                }


                return array(
                    'delivery' => array(
                        'est_delivery' => $est_delivery,
                        'currency' => 'RUB',
                        'rate' => $price,
                        'description' => null,
                    ),
                );
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            return array(
                'delivery' => array(
                    'rate' => null,
                    'comment' => 'Выберите постомат'
                ),
            );
        }
    }

    public function allowedCurrency() {
        return 'RUB';
    }

    public function allowedWeightUnit() {
        return 'kg';
    }

    public function customFields(waOrder $order) {
        return array('MControl' => array(
                'control_type' => waHtmlControl::CUSTOM,
                'title' => 'Адрес доставки',
                'description' => null,
                'callback' => array('dpickpointShipping', 'selectAddressTpl')
        ));
    }

    public static function selectAddressTpl() {
        $view = wa()->getView();

        $plugin_model = new shopPluginModel();
        $plugin = $plugin_model->getByField('plugin', 'dpickpoint');

        $settings = new shopPluginSettingsModel();
        $city = $settings->getByField(array('id' => $plugin['id'], 'name' => 'city'));

        $session = wa()->getStorage();

        $pickpoint_address = $session->read('pickpoint_address');

        $view->assign('plugin_id', $plugin['id']);
        $view->assign('from_city', $city['value']);
        $view->assign('pickpoint_id', $session->read('pickpoint_id'));
        $view->assign('pickpoint_zone', $session->read('pickpoint_zone'));
        $view->assign('pickpoint_address', $pickpoint_address);
        $out = $view->fetch(waConfig::get('wa_path_plugins') . '/shipping/dpickpoint/templates/SelectAddress.html');
        return $out;
    }

    public function getSettingsHTML($params = array()) {
        $cities = $this->PickPoint->cityList();

        $params['options']['city'] = array();
        foreach ($cities as $city) {
            $params['options']['city'][$city['Name']] = $city['Name'] . ', ' . $city['RegionName'];
        }
        return parent::getSettingsHTML($params);
    }

    public function requestedAddressFields() {

        return array(
            'zip' => array('cost' => true),
            'country' => array('cost' => true),
            'city' => array('cost' => true),
            'street' => array('cost' => true),
            'address' => array('cost' => true),
        );
    }

    public static function settingZonesRatesControl($name, $params = array()) {

        foreach ($params as $field => $param) {
            if (strpos($field, 'wrapper')) {
                unset($params[$field]);
            }
        }
        $control = '';
        if (!isset($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }

        $zones = $params['value'];

        waHtmlControl::addNamespace($params, $name);
        $control .= '<table class="zebra">';
        $params['description_wrapper'] = '%s';
        $currency = waCurrency::getInfo('RUB');
        $params['title_wrapper'] = '%s';
        $params['control_wrapper'] = '<tr title="%3$s"><td>%1$s</td><td>&rarr;</td><td>%2$s ' . $currency['sign'] . ' за каждый кг</td></tr>';

        foreach ($zones as $id => $zone) {
            $params['value'] = $zone;
            if ($id == -1) {
                $params['title'] = 'Москва';
            } elseif ($id == 0) {
                $params['title'] = 'СПб';
            } else {
                $params['title'] = "Зона $id";
            }

            $control .= waHtmlControl::getControl(waHtmlControl::INPUT, $id, $params);
        }
        $control .= "</table>";

        return $control;
    }

    public static function settingRatesControl($name, $params = array()) {

        foreach ($params as $field => $param) {
            if (strpos($field, 'wrapper')) {
                unset($params[$field]);
            }
        }
        $control = '';
        if (!isset($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }

        $rates = $params['value'];

        waHtmlControl::addNamespace($params, $name);
        $control .= '<table class="zebra">';
        $params['description_wrapper'] = '%s';
        $currency = waCurrency::getInfo('RUB');
        $params['title_wrapper'] = '%s';
        $params['control_wrapper'] = '<tr title="%3$s"><td>%1$s</td><td>&rarr;</td><td>%2$s ' . $currency['sign'] . '</td></tr>';
        foreach ($rates as $id => $rate) {
            $params['value'] = $rate;
            $params['title'] = "Вес до $id кг";

            $control .= waHtmlControl::getControl(waHtmlControl::INPUT, $id, $params);
        }
        $control .= "</table>";

        return $control;
    }

}
