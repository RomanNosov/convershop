<?php

class shopOrderSendToAxiomusController extends waJsonController
{
    public function execute()
    {
        header('Content-type: application/json');

        $order = $this->getOrder();

        // if (isset($_GET["getSMS"])) {
        //     $result = $this->getSMSText($order);
        // } else {
            $result = $this->send($order);
        // }

        $this->response = $result;
    }

    function isWeekend($date) {
        return (date('N', strtotime($date)) >= 6);
    }

    function endDate($d, $next = -1) {

        if ($next == -1) {
            $next = $d;
        }

        $date = date('Y-m-d', strtotime(" +$next day"));

        if ($this->isWeekend($date)) {
            return $this->endDate($d, ++$next);
        }

        $d += $next - $d;

        return date('Y-m-d', strtotime(" +$d day"));
    }

    public function send($order)
    {
        $deliveryType = $order["params"]["shipping_rate_id"];

        if ($deliveryType == "msc3") {
            return array( "message" => "Ну и зачем?");
        }

        $deliveryFullType = "";
        $shipping = $order["shipping"];
        $discount = $order["discount"] / ($order["total"] - $shipping) * 100;
        $items = "";
        $itemsCount = 0;
        $nocash = $order["params"]["payment_plugin"] != "cash" && $order["params"]["payment_plugin"] != null;
        $total = $nocash ? 0 : $shipping;

        foreach ($order["items"] as $id => $item) {
            $price = $nocash ? 0 : $item['price'] - $item['price'] / 100 * $discount;
            $price = round($price);
            $total += $price * $item["quantity"];

            for ($i = 0; $i < $item["quantity"]; $i++) {
                $items .= "<item name=\"".str_replace("\"", "\\\"", $item['name'])."\" weight=\"0.500\" quantity=\"1\" price=\"$price\" />";
                $itemsCount++;
            }

            if ($deliveryType == 'msc1' || $deliveryType == 'msc2' || $deliveryType == 'spb1' || $deliveryType == 'spb2') {
                $items .= "<item name=\"примерка за каждую отказную\"  weight=\"0.010\" quantity=\"1\" price=\"100.00\" />";
                $total += $nocash ? 0 : 100;
                $itemsCount++;
            }
        }

        $dateAttr  = $deliveryType == 'post' || $deliveryType == 'postp' || $deliveryType == 'spb2' 
            ? 'b_date' : 'd_date';

        $edateAttr = $deliveryType == 'spb2' 
            ? ' e_date="'.$this->endDate(5).'"' : '';
            // ? ' e_date="'.date('Y-m-d',strtotime(' +3 day')).'"' : '';

        $ukey = $deliveryType != 'post' && $deliveryType != 'postp' && $deliveryType != 'regcour' && $deliveryType != 'regpick' 
            ? '6420f1097a8c77ba1d7dc18df838d094' : 'c624bfca565f09efb8481fa02d2baced';
        
        $mkad = $deliveryType == 'msc2' 
            ? ' from_mkad="0"' : '';// $deliveryType == 'msc1' ? ' from_mkad="1"' : ($deliveryType == 'msc2' ? ' from_mkad="0"' : '');
    
        if ($deliveryType == 'spb1' || $deliveryType == 'spb2') {

            // switch (date('D')) {

            //     case 'Fri': 
            //         $ddate = date('Y-m-d',strtotime(' +5 day')); 
            //         break;

            //     case 'Sat': 
            //         $ddate = date('Y-m-d',strtotime(' +4 day'));
            //         break;

            //     case 'Sun': 
            //         $ddate = date('Y-m-d',strtotime(' +3 day')); 
            //         break;

            //     default: 
            //         $ddate = date('Y-m-d',strtotime(' +2 day'));
            // } 
            
            $ddate = $this->endDate(2);

            // $ddate = date('D') == 'Fri' ?  : (date('D') == 'Sat' ? date('Y-m-d',strtotime(' +4 day')) : date('Y-m-d',strtotime(($deliveryType == 'spb1' ? ' +2 day' : ' +1 day'))));
        } else  {

            // switch (date('D')) {

            //     case 'Fri': 
            //         $ddate = date('Y-m-d',strtotime(' +4 day')); 
            //         break;

            //     case 'Sat': 
            //         $ddate = date('Y-m-d',strtotime(' +3 day')); 
            //         break;

            //     case 'Sun': 
            //         $ddate = date('Y-m-d',strtotime(' +2 day')); 
            //         break;

            //     default: 
            //         $ddate = date('Y-m-d',strtotime(' +1 day'));
            // }
            
            $ddate = $this->endDate(1); 
            
            // $ddate = date('D') == 'Fri' ? date('Y-m-d',strtotime(' +3 day')) : (date('D') == 'Sat' ? date('Y-m-d',strtotime(' +2 day')) : date('Y-m-d',strtotime(($deliveryType == 'spb1' ? ' +2 day' : ' +1 day'))));
        }

        if (30 == date('d') * 1 && date('m') * 1 == 4 
            || 1 <= date('d') * 1 && date('d') * 1 <= 4 && date('m') * 1 == 5) {

            $ddate = date("Y")."-05-05";
        }

        if (8 <= date('d') * 1 && date('d') * 1 <= 11 && date('m') * 1 == 5) {
            $ddate = date("Y")."-05-12";
        }

        if (11 <= date('d') * 1 && date('d') * 1 <= 15 && date('m') * 1 == 6) {
            $ddate = date("Y")."-06-16";
        }

        switch ($deliveryType) {

            case 'spb2':
                $deliveryFullType = 'new_carry';
                break;

            case 'post':
                $deliveryFullType = 'new_post';
                break;

            case 'regpick':
                $deliveryFullType = 'new_region_pickup';
                break;

            case 'regcour':
                $deliveryFullType = 'new_region_courier';
                break;

            default:
                $deliveryFullType = 'new';
        }

        $cityAttr = $deliveryType == 'msc1' || $deliveryType == 'msc2'
            ? ' city="0"' : ( $deliveryType == 'spb1' ? ' city="1"' : "");

        $office = $deliveryType == 'spb2' 
            ? ' office="2"' : '';

        $postType = $deliveryType == 'post'
            ? ' post_type="2"' : '';

        $desc = $order["params"]["shipping_address.kommentariy"];

        $desc .= ($deliveryType == 'msc1' || $deliveryType == 'msc2') 
            ? "\n\nПредварительно позвонить! При частичном отказе брать 100 рублей за каждую коробку – услуга «Примерка». При отказе совсем брать товар, стоимость доставки 300 рублей." : '';

        if ($deliveryType == "regcour") {
            $desc .= "\n\nКонтактный email: " . $order["contact"]["email"];
        }
        
        $sms = " sms=\"".$order["contact"]["phone"]."\"";

        if ($deliveryType == 'regcour' || $deliveryType == 'regpick') {
            $checksum = md5('2839'.'u'.$itemsCount.$itemsCount);
        } else {
            $checksum = md5(($deliveryType == 'post' || $deliveryType == 'postp' ?  '2839' : '2838').'u'.$itemsCount.$itemsCount);
        }

        $region = $order["params"]["shipping_address.region"];
        $regionCode = array_key_exists("shipping_address.kod-regiona1", $order["params"])
            ? $order["params"]["shipping_address.kod-regiona1"] : (
                array_key_exists("shipping_address.kod-regiona", $order["params"])
                    ? $order["params"]["shipping_address.kod-regiona"] : "");

        $city = $order["params"]["shipping_address.city"];
        $cityCode = array_key_exists("shipping_address.kod-goroda5", $order["params"])
            ? $order["params"]["shipping_address.kod-goroda5"] : (
                array_key_exists("shipping_address.kod-goroda", $order["params"])
                    ? $order["params"]["shipping_address.kod-goroda"] : "");

        $pointCode = array_key_exists("shipping_address.kod-punkta6", $order["params"])
            ? $order["params"]["shipping_address.kod-punkta6"] : (
                array_key_exists("shipping_address.kod-punkta-vyda", $order["params"])
                    ? $order["params"]["shipping_address.kod-punkta-vyda"] : "");

        $zip = $order["params"]["shipping_address.zip"];
        $street = $order["params"]["shipping_address.street"];
        $house = $order["params"]["shipping_address.dom"];
        $apartment = $order["params"]["shipping_address.kvartira"];
        $address = $street." дом ".$house." кв ".$apartment;

        $street = str_replace("\"", "\\\"", $street);
        $house = str_replace("\"", "\\\"", $house);
        $apartment = str_replace("\"", "\\\"", $apartment);
        $address = str_replace("\"", "\\\"", $address);

        $xdoc  = "<?xml version='1.0' standalone='yes'?>";
        $xdoc .= "<singleorder>";
        $xdoc .= "<mode>$deliveryFullType</mode>";
        $xdoc .= "<auth ukey=\"$ukey\" checksum=\"$checksum\" />";

        if ($deliveryType == 'regpick') {
            $xdoc .= "<order inner_id=\"".$order["id"]."\" name=\"".$order["contact"]["name"]."\"$office $dateAttr=\"$ddate\"$edateAttr b_time=\"10:00\" e_time=\"18:00\" incl_deliv_sum=\"$shipping\">";
        } else {
            $xdoc .= "<order$sms inner_id=\"".$order["id"]."\" name=\"".$order["contact"]["name"]."\"$office address=\"$address\"$mkad $dateAttr=\"$ddate\"$edateAttr ".(("b_time=\"10:00\" e_time=\"".($deliveryType != "spb1" ? "18:00" : "15:00")."\""))." incl_deliv_sum=\"$shipping\"$cityAttr places=\"1\"$postType>";
        }
        
        if ($deliveryType == 'post') {
            $xdoc .= "<address index=\"$zip\" region=\"$region\" area=\"$city\" p_address=\"$address\" />";
        }
        else if($deliveryType == 'regcour') {
            $xdoc .= "<address region_code=\"$regionCode\" city_code=\"$cityCode\" index=\"$zip\" street=\"$street\" house=\"$house\" apartment=\"$apartment\" />";
        }
        else if($deliveryType == 'regpick') {
            $xdoc .= "<address office_code=\"$pointCode\" />";
        }

        $xdoc .= "<contacts>тел. ".$order["contact"]["phone"]."</contacts>";

        if ($deliveryType != 'post' || $deliveryType != 'postp' || $deliveryType != 'regcour' || $deliveryType != 'regpick') {
            $xdoc .= "<description>$desc</description>";
        } 

        if ($deliveryType == 'spb2') {
            $xdoc .= "<services cash=\"yes\" cheque=\"no\" />";
        }
        else if ($deliveryType == 'post') {
            $xdoc .= "<services valuation=\"no\" fragile=\"no\" cod=\"yes\" />";  
        }
        else if($deliveryType == 'regpick' || $deliveryType == 'regcour') {
            $xdoc .= "<services cheque=\"yes\" not_open=\"yes\" extrapack=\"yes\" big=\"yes\" />";  
        }
        else {
            $xdoc .= "<services cash=\"yes\" cheque=\"no\" selsize=\"no\" />";
        }

        $xdoc .= "<items>";
        $xdoc .= $items;
        $xdoc .= "</items>";

        if ($deliveryType == 'msc1' || $deliveryType == 'msc2' || $deliveryType == 'spb1' || $deliveryType == 'spb2') {
            $xdoc .= "<delivset return_price=\"300.00\" above_sum=\"100000.00\" above_price=\"300.00\"><below below_sum=\"5000.00\" price=\"300.00\" /></delivset>";
        }

        $xdoc .= "</order>";
        $xdoc .= "</singleorder>";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".urlencode($xdoc)); // add POST fields
        $result = curl_exec($ch); // run the whole process
        curl_close($ch);
        
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML($result);

        foreach ($doc->documentElement->childNodes as $_okay) {

            if ($_okay->tagName != "auth") 
                continue;

            $okay = $_okay->textContent;

            $model = new waModel();
            $model->exec("DELETE FROM shop_order_params WHERE order_id = ".$order["id"]." AND name = \"axiomus_okay\"");
            $model->exec("INSERT INTO shop_order_params (order_id, name, value) VALUES (".$order["id"].", \"axiomus_okay\", \"".$okay."\")");

            $model->exec("DELETE FROM shop_order_params WHERE order_id = ".$order["id"]." AND name = \"axiomus_updated\"");
            $model->exec("INSERT INTO shop_order_params (order_id, name, value) VALUES (".$order["id"].", \"axiomus_updated\", \"".time()."\")");

            break;
        }

        return array(
            "request" => $xdoc,
            "order"   => $order,
            "response" => $result,
            "okay"    => $okay
        );
    }    

    public function getOrder()
    {
        $id = (int) waRequest::get('order_id');
        if (!$id) {
            return array();
        }
        $order = $this->_getOrder($id);
        if (!$order) {
            $id = shopHelper::decodeOrderId($id);
            $order = $this->_getOrder($id);
            if (!$order) {
                return array();
            }
        }
        return $order;
    }

    private function _getOrder($id)
    {
        $order = $this->getModel()->getOrder($id);
        if (!$order) {
            return false;
        }
        $workflow = new shopWorkflow();
        $order['state'] = $workflow->getStateById($order['state_id']);
        $order = shopHelper::workupOrders($order, true);

        $sku_ids = array();
        $stock_ids = array();
        foreach ($order['items'] as $item) {
            if ($item['stock_id']) {
                $stock_ids[] = $item['stock_id'];
            }
            if ($item['sku_id']) {
                $sku_ids[] = $item['sku_id'];
            }
        }
        $sku_ids = array_unique($sku_ids);
        $stock_ids = array_unique($stock_ids);
        
        // extend items by stocks
        $stocks = $this->getStocks($stock_ids);
        foreach ($order['items'] as &$item) {
            if (!empty($stocks[$item['stock_id']])) {
                $item['stock'] = $stocks[$item['stock_id']];
            }
        }
        unset($item);

        $skus = $this->getSkus($sku_ids);
        $sku_stocks = $this->getSkuStocks($sku_ids);
        
        foreach ($order['items'] as &$item) {
            // product and existing sku
            if (isset($skus[$item['sku_id']])) {
                $s = $skus[$item['sku_id']];
                $item["size_name"] = $s["name"];
                
                // for that counts that lower than low_count-thresholds show icon
                
                if ($s['count'] !== null) {
                    if (isset($item['stock'])) {
                        if (isset($sku_stocks[$s['id']][$item['stock']['id']])) {
                            $count = $sku_stocks[$s['id']][$item['stock']['id']]['count'];
                            if ($count <= $item['stock']['low_count']) {
                                $item['stock_icon'] = shopHelper::getStockCountIcon($count, $item['stock']['id'], true);
                            }
                        }
                    } else if ($s['count'] <= shopStockModel::LOW_DEFAULT) {
                        $item['stock_icon'] = shopHelper::getStockCountIcon($s['count'], null, true);
                    }
                }
            }
        }
        unset($item);

        return $order;

    }
    
    public function getSkus($sku_ids)
    {
        if (!$sku_ids) {
            return array();
        }
        $model = new shopProductSkusModel();
        return $model->getByField('id', $sku_ids, 'id');
    }
    
    public function getStocks($stock_ids)
    {
        if (!$stock_ids) {
            return array();
        }
        $model = new shopStockModel();
        return $model->getById($stock_ids);
    }

    /**
     * @return shopOrderModel
     */
    public function getModel()
    {
        if ($this->model === null) {
            $this->model = new shopOrderModel();
        }
        return $this->model;
    }
    
    public function getSkuStocks($sku_ids)
    {
        if (!$sku_ids) {
            return array();
        }
        $model = new shopProductStocksModel();
        return $model->getBySkuId($sku_ids);
    }

}