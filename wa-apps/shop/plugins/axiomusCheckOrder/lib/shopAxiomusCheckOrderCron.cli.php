<?php

class shopAxiomusCheckOrderCronCli extends waCliController {

    public function execute() {
        $shopOrderLogModel = new shopOrderLogModel();
        // для всех заказов нужно отравить запрос статуса
        $shopOrderParamsModel = new shopOrderParamsModel();
        $orderLogs = $shopOrderLogModel->getByField('action_id', 'process', true);
        $orders = array();
        foreach ($orderLogs as $log) {
            $auth = $shopOrderParamsModel->getOne($log['order_id'], 'axiomus.auth');
             $code = (int)$shopOrderParamsModel->getOne($log['order_id'], 'axiomus.code');
            if ($auth != null && $code < 90) {
                $orders[] = $log['order_id'];
            }
        }
        // список уникальный номеров заказов,которые в обработке
        $uniq_orders = array_unique($orders);
        foreach ($uniq_orders as $id) {
            $auth = $shopOrderParamsModel->getOne($id, 'axiomus.auth');
            if ($auth != null) {
                $xdoc = "<?xml version='1.0' standalone='yes'?>";
                $xdoc .= "<singleorder>";
                $xdoc .= "<mode>status</mode>";
                $xdoc .= "<okey>$auth</okey>";
                $xdoc .= "</singleorder>";
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "http://www.axiomus.ru/test/api_xml_test.php"); //"http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
                curl_setopt($ch, CURLOPT_POST, 1); // set POST method
                curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($xdoc)); // add POST fields
                $result = curl_exec($ch); // run the whole process
                curl_close($ch);
                //var_dump($result);
                //print_r($result);
                //print_r($xdoc);
                $xmlRes = simplexml_load_string($result);
                if ($xmlRes != null) {
                    $statusText = (string) $xmlRes->status;
                    $attrib = $xmlRes->status[0]->attributes();
                    $resultCode = (int) $attrib['code'];
                    $resultCode = 100;
                    if ($resultCode >= 90) {
                        $shopOrderParamsModel->setOne($id, 'axiomus.code', $resultCode);
                        $shopOrderParamsModel->setOne($id, 'axiomus.error', null);

                        // Получен финальный статус, изменение статуса резервации на нужный
                        if ($resultCode == 90) {
                            // 90: 'отмена'
                            $this->changeStatus('refunded', $id);
                        }
//                        90: 'отмена'
//                        100: 'выполнен'
                        if ($resultCode == 100) {
                            $this->changeStatus('completed', $id);
                        }
//                        105: 'отправлен'
                        if ($resultCode == 105) {
                            $this->changeStatus('shipped', $id);
                        }
//                        107: 'вручен'
                        if ($resultCode == 107) {
                            $this->changeStatus('processed', $id);
                        }
//                        110: 'частичный отказ'
                        if ($resultCode == 110) {
                            $this->changeStatus('partial_revert', $id);
                        }
//                        120: 'полный отказ'
                        if ($resultCode == 120) {
                            $this->changeStatus('full_revert', $id);
                        }
                    }
                }
            }
        }




        die("Выполнился скрипт shopAxiomusCheckOrderCronCli");
    }

    public function changeStatus($newStatus, $order_id) {
        // update status to error
        $order_model = new shopOrderModel();
        $order = $order_model->getById($order_id);

        $data = array();
        $data['order_id'] = $order_id;
        $data['action_id'] = $newStatus;

        $new_state_id = $newStatus;

        $data['before_state_id'] = $order['state_id'];
        if ($new_state_id) {
            $data['after_state_id'] = $new_state_id;
        } else {
            $data['after_state_id'] = $order['state_id'];
        }

        $order_log_model = new shopOrderLogModel();
        $data['id'] = $order_log_model->add($data);

        $update = array();
        $update['update_datetime'] = date('Y-m-d H:i:s');
        $data['update'] = $update;

        if ($new_state_id) {
            $update['state_id'] = $new_state_id;
        }
        $order_model->updateById($order['id'], $update);

        $order_params_model = new shopOrderParamsModel();
        if (isset($update['params'])) {
            $order_params_model->set($order['id'], $update['params'], false);
        }
    }

    public function sendRQtoAxiomus($param) {

        //        $path = wa()->getConfig()->getPath('log');
        //        waFiles::create($path . '/shop/axiomus.log');
        //        waLog::log("sendRQtoAxiomus", 'shop/axiomus.log');
        //        //waLog::log($param, 'shop/axiomus.log');    
        //        //$pathh = realpath('regions_1.xml');
        ////echo $pathh;
        //
        //        $ch = curl_init();
        //
        //        curl_setopt($ch, CURLOPT_URL, "http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
        //        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        //        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        //        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($xdoc)); // add POST fields
        //        $rt = curl_exec($ch); // run the whole process
        //        curl_close($ch);
        //        //var_dump($result);
        //        header("Content-type: text/xml; charset=utf-8");
        //        print_r($rt);
        //
        //        file_put_contents($path . '/shop/regions_1.xml', $rt);
        //
        //        $doc = new DOMDocument('1.0', 'UTF-8');
        //        $e = $doc->load($path . '/shop/regions_1.xml');
        //        echo $e;
        //        $res = array();
        //        $waCountryModel = new waCountryModel();
        //        $waRegionModel = new waRegionModel();
        //        $root = $doc->documentElement;
        //        $j = 10;
        //        $fileds = array();
        $waContactFieldValuesModel = new waContactFieldValuesModel();
        //        foreach ($root->childNodes as $region) {
        //            unset($res);
        //            //echo $region->;
        //            //if(strpos(mb_convert_case($region->getAttribute("name"), MB_CASE_LOWER, "UTF-8"),mb_convert_case($_POST['q'], MB_CASE_LOWER, "UTF-8")) !== false)
        //            if ($region->hasAttributes() != false) {
        //                $j = $j + 1;
        //                //$res[] = array("name" => $region->getAttribute("name"), "code" => $region->getAttribute("region_code"));
        //                //$waCountryModel->saveRegion($region->getAttribute("name"), $region->getAttribute("region_code"), $j);
        //                $curr = $region->getElementsByTagName("courier");
        //                foreach ($curr->item(0)->childNodes as $city) {
        //                    if($city->hasAttributes() != false) {
        //                    //$res[] = array("name" => 'г. ' . getCityByCode($city->getAttribute("city_code"), $region) . ' ' . $city->textContent, "code" => $city->getAttribute("office_code"));
        //                    $cityName =  $city->nodeValue;
        //                       //$waRegionModel->saveCity($cityName, $city->getAttribute("city_code"), $region->getAttribute("region_code"));
        //                    }                    
        //                }
        ////                <pickup>
        ////            <office office_code="32" city_code="220">ул. Чкалова д. 70</office>
        ////        </pickup>
        //                $pickup = $region->getElementsByTagName("pickup");
        //                foreach ($pickup->item(0)->childNodes as $office) {
        //                    if($office->hasAttributes() != false) {
        //                    //$res[] = array("name" => 'г. ' . getCityByCode($city->getAttribute("city_code"), $region) . ' ' . $city->textContent, "code" => $city->getAttribute("office_code"));
        //                    $pickupAddr =  $office->nodeValue;
        //                    $cName = $this->getCityByCode($office->getAttribute("city_code"), $region);
        //                    $res[] = array('parent_field'=> 'address:country',
        //                        'parent_value'=>$region->getAttribute("region_code"),
        //                        'field'=>'address:punkt-vydachi',
        //                        "value" => 'г. ' . $cName . ' ' . $pickupAddr,
        //                        'addition_value' => $office->getAttribute("office_code")
        //                        );
        //                  //      $waRegionModel->saveCity($cityName, $city->getAttribute("city_code"), $region->getAttribute("region_code"));
        //                    
        //                    
        //                    }                    
        //                }
        //                
        //                $waContactFieldValuesModel->save(array('add' => $res));
        //            }
        //        }



        $shopOrderItemsModel = new shopOrderItemsModel();
        $shopOrderParamsModel = new shopOrderParamsModel();
        $order_id = $param['order_id'];
        $orderInfo = $shopOrderParamsModel->get($order_id, true);
        if($orderInfo['shipping_rate_id'] === 'delivery') {
            return false;
        }
        $tmp_items = $shopOrderItemsModel->getItems($order_id);


        $shopOrderModel = new shopOrderModel();
        $order = $shopOrderModel->getOrder($order_id, true);

        $contact = $order['contact'];

        $waCountryModel = new waCountryModel();
        $region = $waCountryModel->name($orderInfo['shipping_address.country']);
        $waRegionModel = new waRegionModel();
        //$cityName = $waRegionModel->get($orderInfo['shipping_address.country'], $orderInfo['shipping_address.region']);
        $city_code = $orderInfo['shipping_address.region'];
        $pick_code = $waContactFieldValuesModel->getAdditionValue($orderInfo['shipping_address.punkt-vydachi']);

        echo 'sendRQtoAxiomus';

        // ======================================
        //$cart = $db['_orders']->select('id=', $_POST['id']);
        //$cart = $cart[0];
        //$addr = json_decode($cart['address'], true);

        $items = "";
        $icol = 0;
        $isum = 0;
        // $tmp_items = json_decode($cart['cart'], true);
        foreach ($tmp_items as $id => $col) {

            $price = $col['price'];
            //            $id = $id[0];
            //            $itm = $db['_products']->select('id=', $id);
            //            $itm = $itm[0];
            //
        //            if ($sz * 1 < 30) {
            //                $szs = json_decode($itm['params'], true);
            //                $szs = $szs[0]['values'];
            //
        //                $sz = $szs[$sz * 1]['value'];
            //            }

            $name = $col['name'];
            //discout
            //$price = $prc - $prc / 100 * $cart['discount'];

            for ($i = 0; $i < $col['quantity'] * 1; $i++) {
                $isum += $price;
                $items .= "<item name=\"$name \"  weight=\"0.500\" quantity=\"1\" price=\"$price\" />";
                $icol++;
            }

            if ($orderInfo['shipping_rate_id'] == 'msc1' || $orderInfo['shipping_rate_id'] == 'msc2' || $orderInfo['shipping_rate_id'] == 'spb1' || $orderInfo['shipping_rate_id'] == 'spb2') {
                $items .= "<item name=\"примерка за каждую отказную\"  weight=\"0.010\" quantity=\"1\" price=\"100.00\" />";
                $isum += 100;
                $icol++;
            }
        }

        $date_attr = $orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp' || $orderInfo['shipping_rate_id'] == 'spb2' ? 'b_date' : 'd_date';
        $edate_attr = $orderInfo['shipping_rate_id'] == 'spb2' ? ' e_date="' . date('Y-m-d', strtotime(' +3 day')) . '"' : '';
        $ukey = 'XXcd208495d565ef66e7dff9f98764XX'; //$orderInfo['shipping_rate_id'] != 'post' && $orderInfo['shipping_rate_id'] != 'postp' && $orderInfo['shipping_rate_id'] != 'regcour' && $orderInfo['shipping_rate_id'] != 'regpick' ? '6420f1097a8c77ba1d7dc18df838d094' : 'c624bfca565f09efb8481fa02d2baced';
        $mkad = $orderInfo['shipping_rate_id'] == 'msc2' ? ' from_mkad="0"' : ''; // $orderInfo['shipping_rate_id'] == 'msc1' ? ' from_mkad="1"' : ($orderInfo['shipping_rate_id'] == 'msc2' ? ' from_mkad="0"' : '');
        if ($orderInfo['shipping_rate_id'] == 'spb1')
            switch (date('D')) {
                case 'Fri': $ddate = date('Y-m-d', strtotime(' +5 day'));
                    break;
                case 'Sat': $ddate = date('Y-m-d', strtotime(' +4 day'));
                    break;
                case 'Sun': $ddate = date('Y-m-d', strtotime(' +3 day'));
                    break;
                default: $ddate = date('Y-m-d', strtotime(' +2 day'));
            }//$ddate = date('D') == 'Fri' ?  : (date('D') == 'Sat' ? date('Y-m-d',strtotime(' +4 day')) : date('Y-m-d',strtotime(($orderInfo['shipping_rate_id'] == 'spb1' ? ' +2 day' : ' +1 day'))));
        else
            switch (date('D')) {
                case 'Fri': $ddate = date('Y-m-d', strtotime(' +4 day'));
                    break;
                case 'Sat': $ddate = date('Y-m-d', strtotime(' +3 day'));
                    break;
                case 'Sun': $ddate = date('Y-m-d', strtotime(' +2 day'));
                    break;
                default: $ddate = date('Y-m-d', strtotime(' +1 day'));
            }//$ddate = date('D') == 'Fri' ? date('Y-m-d',strtotime(' +3 day')) : (date('D') == 'Sat' ? date('Y-m-d',strtotime(' +2 day')) : date('Y-m-d',strtotime(($orderInfo['shipping_rate_id'] == 'spb1' ? ' +2 day' : ' +1 day'))));

        if (30 == date('d') * 1 && date('m') * 1 == 4 || 1 <= date('d') * 1 && date('d') * 1 <= 4 && date('m') * 1 == 5)
            $ddate = "2014-05-05";
        if (8 <= date('d') * 1 && date('d') * 1 <= 11 && date('m') * 1 == 5)
            $ddate = "2014-05-12";
        if (11 <= date('d') * 1 && date('d') * 1 <= 15 && date('m') * 1 == 6)
            $ddate = "2014-06-16";

        if ($isum <= 2500) {
            if ($orderInfo['shipping_rate_id'] == 'msc1' || $orderInfo['shipping_rate_id'] == 'msc2' || $orderInfo['shipping_rate_id'] == 'spb1')
                $deliv = 300;
            else if ($orderInfo['shipping_rate_id'] == 'spb2')
                $deliv = 200;
            else if ($orderInfo['shipping_rate_id'] == 'postp')
                $deliv = 400;
            else if ($orderInfo['shipping_rate_id'] == 'regcour')
                $deliv = 300; //450;
            else if ($orderInfo['shipping_rate_id'] == 'regpick')
                $deliv = 200; //350;
            else
                $deliv = 0;
        } else {
            if ($orderInfo['shipping_rate_id'] == 'regcour')
                $deliv = 300; //450;
            else if ($orderInfo['shipping_rate_id'] == 'regpick')
                $deliv = 200; //350;
            else
                $deliv = 0;
        }

        if ($orderInfo['shipping_rate_id'] == 'spb2')
            $pmode = 'new_carry';
        else if ($orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp')
            $pmode = 'new_post';
        else if ($orderInfo['shipping_rate_id'] == 'regcour')
            $pmode = 'new_region_courier';
        else if ($orderInfo['shipping_rate_id'] == 'regpick')
            $pmode = 'new_region_pickup';
        else
            $pmode = 'new';

        $city = ($orderInfo['shipping_rate_id'] == 'msc1' || $orderInfo['shipping_rate_id'] == 'msc2') ? ' city="0"' : ($orderInfo['shipping_rate_id'] == 'spb1' ? ' city="1"' : "");
        $office = $orderInfo['shipping_rate_id'] == 'spb2' ? ' office="2"' : '';
        $postType = $orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp' ? ' post_type="2"' : '';
        $desc = ($orderInfo['shipping_rate_id'] == 'msc1' || $orderInfo['shipping_rate_id'] == 'msc2') ? "метро ${$orderInfo['shipping_address.metro']}. Предварительно позвонить! При частичном отказе брать 100 рублей за каждую коробку – услуга «Примерка». При отказе совсем брать товар, стоимость доставки 300 рублей." : ($orderInfo['shipping_rate_id'] == 'spb1' ? "метро ${$orderInfo['shipping_address.metro']}" : '');
        //$contact['phone'] = preg_replace('/[\+\(\)\s-]/','',$contact['phone']);
        $phone = '+' . $contact['phone'];
        $sms = (preg_match('/^\+7\s\(9[0-9]{2}\)\s[0-9]{3}-[0-9]{4}$/i', $phone) === 1) ? " sms=\"" . preg_replace('/[\+\(\)\s-]/', '', $phone) . "\"" : '';

        $isum += $deliv * 1;

        $uid = '92'; //'2839'
        if ($orderInfo['shipping_rate_id'] == 'regcour' || $orderInfo['shipping_rate_id'] == 'regpick')
            $checksum = md5($uid . 'u' . $icol . $icol);
        else
            $checksum = md5(($orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp' ? $uid : $uid) . 'u' . $icol . $icol);
        //else $checksum = md5(($orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp' ?  '2839' : '2838').$icol.$icol.$isum.$ddate.($orderInfo['shipping_rate_id'] == 'spb2' || $orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp'?'':' 10:00').($orderInfo['shipping_rate_id'] == 'spb2' ? 'yes/no' : ($orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp' ? 'no/no/yes' : 'yes/no/no')));

        $xdoc = "<? xml version = '1.0' standalone = 'yes' ?>";
        $xdoc .= "<singleorder>";
        $xdoc .= "<mode>$pmode</mode>";
        $xdoc .= "<auth ukey=\"$ukey\" checksum=\"$checksum\" />";
        $address = $orderInfo['shipping_address.street'] . " дом " . $orderInfo['shipping_address.dom'] . " кв. " . $orderInfo['shipping_address.kvartira'];
        if ($orderInfo['shipping_rate_id'] == 'regpick')
            $xdoc .= "<order inner_id=\"${order_id}\" name=\"${contact['name']}\"$office $date_attr=\"$ddate\"$edate_attr b_time=\"10:00\" e_time=\"18:00\" incl_deliv_sum=\"$deliv\">";
        else
            $xdoc .= "<order$sms inner_id=\"${order_id}\" name=\"${contact['name']}\"$office address=\"$address\"$mkad $date_attr=\"$ddate\"$edate_attr " . (("b_time=\"10:00\" e_time=\"" . ($orderInfo['shipping_rate_id'] != "spb1" ? "18:00" : "15:00") . "\"")) . " incl_deliv_sum=\"$deliv\"$city places=\"1\"$postType>";

        if ($orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp')
            $xdoc .= "<address index=\"${orderInfo['shipping_address.zip']}\" region=\"${region}\" area=\"${orderInfo['shipping_address.city']}\" p_address=\"$address\" />";
        else if ($orderInfo['shipping_rate_id'] == 'regcour')
            $xdoc .= "<address region_code=\"${orderInfo['shipping_address.country']}\" city_code=\"${city_code}\" index=\"${orderInfo['shipping_address.zip']}\" street=\"${orderInfo['shipping_address.street']}\" house=\"${orderInfo['shipping_address.dom']}\" apartment=\"${orderInfo['shipping_address.kvartira']}\" />";
        else if ($orderInfo['shipping_rate_id'] == 'regpick')
            $xdoc .= "<address office_code=\"${pick_code}\" />";

        $xdoc .= "<contacts>тел. ${phone}</contacts>";

        if ($orderInfo['shipping_rate_id'] != 'post' || $orderInfo['shipping_rate_id'] != 'postp' || $orderInfo['shipping_rate_id'] != 'regcour' || $orderInfo['shipping_rate_id'] != 'regpick')
            $xdoc .= "<description>$desc</description>";

        if ($orderInfo['shipping_rate_id'] == 'spb2')
            $xdoc .= "<services cash=\"yes\" cheque=\"no\" />";
        else if ($orderInfo['shipping_rate_id'] == 'post' || $orderInfo['shipping_rate_id'] == 'postp')
            $xdoc .= "<services valuation=\"no\" fragile=\"no\" cod=\"yes\" />";
        else if ($orderInfo['shipping_rate_id'] == 'regpick' || $orderInfo['shipping_rate_id'] == 'regcour')
            $xdoc .= "<services cheque=\"yes\" not_open=\"yes\" extrapack=\"yes\" big=\"yes\" />";
        else
            $xdoc .= "<services cash=\"yes\" cheque=\"no\" selsize=\"no\" />";

        $xdoc .= "<items>";
        $xdoc .= $items;
        $xdoc .= "</items>";
        if ($orderInfo['shipping_rate_id'] == 'msc1' || $orderInfo['shipping_rate_id'] == 'msc2' || $orderInfo['shipping_rate_id'] == 'spb1' || $orderInfo['shipping_rate_id'] == 'spb2')
            $xdoc .= "<delivset return_price=\"300.00\" above_sum=\"100000.00\" above_price=\"300.00\"><below below_sum=\"2500.00\" price=\"300.00\" /></delivset>";
        $xdoc .= "</order>";
        $xdoc .= "</singleorder>";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://axiomus.ru/test/api_xml_test.php"); //"http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($xdoc)); // add POST fields
        $result = curl_exec($ch); // run the whole process
        curl_close($ch);
        var_dump($result);

        print_r($result);
        print_r($xdoc);

        $xmlRes = simplexml_load_string($result);
        $auth = (string) $xmlRes->auth;
        $attrib = $xmlRes->auth[0]->attributes();
        $objectid = (string) $attrib['objectid'];

        $attrib = $xmlRes->status[0]->attributes();
        $resultCode = (string) $attrib['code'];
        $axiPrice = (string) $attrib['price'];

        $shopOrderParamsModel->setOne($order_id, 'axiomus.objectid', $objectid);
        $shopOrderParamsModel->setOne($order_id, 'axiomus.auth', $auth);
        $shopOrderParamsModel->setOne($order_id, 'axiomus.code', $resultCode);
        $shopOrderParamsModel->setOne($order_id, 'axiomus.price', $axiPrice);


        return $html;
    }

}
