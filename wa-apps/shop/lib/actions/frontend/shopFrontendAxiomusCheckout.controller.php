<?php

class shopFrontendAxiomusCheckoutController extends waJsonController
{
    protected static $config;
    private $productFixes = array(
        "ALL" => "All",
        "CONVERSE" => "Converse",
        "КЕДЫ" => "Кеды",
        "CHUCK TAYLOR" => "Chuck Taylor",
        "GREEN" => "Green",
        "BLK" => "Blk",
        "WHITE" => "White",
        "GOLD" => "Gold",
        "OX" => "Ox",
        "EUROPEAN" => "European",
        "HI" => "Hi",
        "MEN" => "Men",
        "LEATHER" => "Leather",
        "BROWN" => "Brown",
        "ХАКИ" => "Хаки",
        "ONE" => "One",
        "AS" => "as",
        "NEW" => "New",
        "BALANCE" => "Balance",
        "ML" => "ml",
        "UKC" => "ukc",
        "SGW" => "sgw",
        "M" => "m",
        "SJR" => "sjr",
        "SJK" => "sjk",
        "AG" => "ag",
        "AN" => "an",
        "AY" => "ay",
        "AR" => "ar",
        "STAR" => "star",
        "GERMANY" => "Germany",
        "ARMY" => "army",
        "FRED PERRY" => "Fred Perry",
        "B" => "b",
        "SLIP" => "slip",
        "CT" => "ct",
        "BLUE" => "blue",
        "NAVY" => "navy",
        "ТОНКАЯ" => "тонкая",
        "ПОДОШВА" => "подошва",
        "YELLOW" => "yellow",
        "LT" => "lt",
        "MOON" => "moon",
        "DARK" => "dark",
        "C" => "c",
        "NIKE" => "Nike",
        "AIR" => "Air",
        "FORCE" => "Force",
        "LOW" => "low",
        "LEO" => "leo",
        "MB" => "mb",
        "BLACK" => "black",
        "SCOTTISH" => "scottish",
        "CELL" => "cell",
        "ХИТ" => "",
        "ПРОДАЖ" => "",
        "GANT" => "gant",
        "VANS" => "Vans",
        "RED" => "red",
        "QER" => "qer",
        "SCQ" => "scq",
        "EDX" => "edx",
        "ВОЗМОЖНЫ" => "возможны",
        "ВАРИАНТЫ" => "варианты",
        "Who!" => "Who",
        "LEGEND" => "legend",
        "Q" => "q",
        "СКИДКА" => ""
    );

    public static function _getConfig()
    {
        if (self::$config === null) {
            $file = wa()->getConfig()->getConfigPath('workflow.php', true, 'shop');
            if (!file_exists($file)) {
                $file = wa()->getConfig()->getAppsPath('shop', 'lib/config/data/workflow.php');
            }
            if (file_exists($file)) {
                self::$config = include($file);
                foreach (self::$config['states'] as &$data) {
                    if (!isset($data['classname'])) {
                        $data['classname'] = 'shopWorkflowState';
                    }
                }
                unset($data);
            } else {
                self::$config = array();
            }
        }
        return self::$config;
    }

    private function getStatusByText($text)
    {
        if ($text == "") {
            return $text;
        }

        $config = $this->_getConfig();

        foreach ($config["states"] as $name => $state) {
            if ($state["name"] == $text) {
                return $name;
            }
        }

        return "";
    }

    private function fixProductField($field) {

        foreach ($this->productFixes as $break => $fix) {
            $field = preg_replace("/(^|[^\w])$break([^\w]|$)/", "$1$fix$2", $field);
        }

        return $field;
    }

    public function execute()
    {
        header('Content-type: application/json');

        // if (waRequest::get('type') == "fixCatalog") {
        //     $model = new waModel();

        //     $products = $model->query("SELECT p.id, p.name, p.description FROM shop_product p WHERE 1");
        //     $products = $products->fetchAll();

        //     foreach ($products as &$product) {
        //         $product["name"]        = $this->fixProductField($product["name"]);
        //         $product["description"] = $this->fixProductField($product["description"]);

        //         $model->exec("UPDATE shop_product p SET p.name = s:0, p.description = s:1 WHERE p.id = s:2", $product["name"], $product["description"], $product["id"]);
        //     }

        //     $this->response = $products;
        // }
        
        if (waRequest::post('type') == "regions") {
            $this->response = $this->getRegions();
        }

        if (waRequest::post('type') == "courierCities") {
            $this->response = $this->getCourierCities(waRequest::post('region'));
        }

        if (waRequest::post('type') == "pickupPoints") {
            $this->response = $this->getPickupPoints(waRequest::post('region'));
        }

        if (waRequest::get('type') == "update_Status_Old") {
            $model = new waModel();
            $date = date('Y-m-d H:i:s', strtotime("-1 month")); 
            header('Content-Type: text/plain; charset=utf-8');

            $orders = $model->query("SELECT p.*
                FROM shop_order_params p
                WHERE p.name = \"axiomus_okay\" 
                    AND (SELECT count(*) FROM shop_order_params pp WHERE pp.order_id = p.order_id AND pp.name = \"axiomus_updated\") = 0");
            $orders = $orders->fetchAll();

            foreach ($orders as $order) {
                $model->exec("INSERT INTO shop_order_params (order_id, name, value) VALUES (".$order["order_id"].", \"axiomus_updated\", \"".(time() - 650)."\")");
            }

            $orders = $model->query("SELECT o.id, o.state_id, p.value AS axiomus_okay, p2.value AS axiomus_updated 
                FROM shop_order o 
                LEFT JOIN shop_order_params p ON p.order_id = o.id 
                LEFT JOIN shop_order_params p2 ON p2.order_id = o.id 
                WHERE o.state_id <> \"deleted\" AND o.state_id <> \"completed\" 
                    AND o.state_id <> \"refunded\" AND o.state_id <> \"full_revert\" 
                    AND o.state_id <> \"otmenit-zakaz\"
                    AND p.name = \"axiomus_okay\" 
		    AND p2.name = \"axiomus_updated\" ");
            $orders = $orders->fetchAll();

            echo "\nCurrent time: "  . date('Y-m-d H:i:s', time()) . " (" . time() . "), Count of orders: " . count($orders) . "\n\n";

            for ($i = 0; $i < count($orders); $i++) {

                $order = $orders[$i];

                echo "id: " . $order["id"] . ", time: " . date('Y-m-d H:i:s', $order["axiomus_updated"]) . " (" . $order["axiomus_updated"] . "), okay: " . $order["axiomus_okay"] . "\n";
                $status = $this->getStatusByText($ax_status = $this->_getStatus($order["axiomus_okay"]));

                if ($ax_status == "repeat") {
                    $i--;
                    echo "\nRepeating ";
                    continue;
                }

                if ($status == "") {
                    // $status = "completed";
                    echo "\n";
                    continue;
                }

                echo "\tstatus " . $order["state_id"] . " -> " . $status . "\n\n";
                $model->exec("UPDATE shop_order o SET o.state_id = \"$status\" WHERE o.id = \"".$order["id"]."\"");

                $model->exec("DELETE FROM shop_order_params WHERE order_id = ".$order["id"]." AND name = \"axiomus_updated\"");
                $model->exec("INSERT INTO shop_order_params (order_id, name, value) VALUES (".$order["id"].", \"axiomus_updated\", \"".time()."\")");

                if ($ax_status == "Выполнен Аксиомус") {
                    $this->setCompetedStatus($order["id"]);
                }
            }

            echo "\n\n\n\n * END OF PROCESSING * \n\n\n\n";

            $this->response = true;
        }

        if (waRequest::get('type') == "updateAllStatus") {
            $model = new waModel();
            $date = date('Y-m-d H:i:s', strtotime("-1 month")); 
            header('Content-Type: text/plain; charset=utf-8');

            $orders = $model->query("SELECT p.*
                FROM shop_order_params p
                WHERE p.name = \"axiomus_okay\" 
                    AND (SELECT count(*) FROM shop_order_params pp WHERE pp.order_id = p.order_id AND pp.name = \"axiomus_updated\") = 0");
            $orders = $orders->fetchAll();

            foreach ($orders as $order) {
                $model->exec("INSERT INTO shop_order_params (order_id, name, value) VALUES (".$order["order_id"].", \"axiomus_updated\", \"".(time() - 650)."\")");
            }


	    $orders = $model->query("SELECT o.id, o.state_id, p.value AS axiomus_okay, p2.value AS axiomus_updated 
                FROM shop_order o 
                LEFT JOIN shop_order_params p ON p.order_id = o.id 
                LEFT JOIN shop_order_params p2 ON p2.order_id = o.id 
                WHERE p.name = \"axiomus_okay\" 
				  AND p2.name = \"axiomus_updated\"" );
            $orders = $orders->fetchAll();


            echo "\nCurrent time: "  . date('Y-m-d H:i:s', time()) . " (" . time() . "), Count of orders: " . count($orders) . "\n\n";

            for ($i = 0; $i < count($orders); $i++) {

                $order = $orders[$i];

                echo "id: " . $order["id"] . ", time: " . date('Y-m-d H:i:s', $order["axiomus_updated"]) . " (" . $order["axiomus_updated"] . "), okay: " . $order["axiomus_okay"] . "\n";
                $status = $this->getStatusByText($ax_status = $this->_getStatus($order["axiomus_okay"]));

                if ($ax_status == "repeat") {
                    $i--;
                    echo "\nRepeating ";
                    continue;
                }

                if ($status == "") {
                    // $status = "completed";
                    echo "\n";
                    continue;
                }

                echo "\tstatus " . $order["state_id"] . " -> " . $status . "\n\n";
                $model->exec("UPDATE shop_order o SET o.state_id = \"$status\" WHERE o.id = \"".$order["id"]."\"");

                $model->exec("DELETE FROM shop_order_params WHERE order_id = ".$order["id"]." AND name = \"axiomus_updated\"");
                $model->exec("INSERT INTO shop_order_params (order_id, name, value) VALUES (".$order["id"].", \"axiomus_updated\", \"".time()."\")");

                if ($ax_status == "Выполнен Аксиомус") {
                    $this->setCompetedStatus($order["id"]);
                }
            }

            echo "\n\n\n\n * END OF PROCESSING * \n\n\n\n";

            $this->response = true;
        }
		
		if (waRequest::get('type') == "updateStatusPack") {

			// берем модель
			$model = new waModel();
			$date = date('Y-m-d H:i:s', strtotime("-1 month")); 
			header('Content-Type: text/plain; charset=utf-8');

			// получаем все нефинальные заказы
			$orders = $model->query("SELECT o.id, o.state_id, o.comment, p.value AS axiomus_okay 
				FROM shop_order o 
				LEFT JOIN shop_order_params p ON p.order_id = o.id                 
				WHERE o.state_id <> \"deleted\" 
				AND o.state_id <> \"completed\" 
				AND o.state_id <> \"refunded\" 
				AND o.state_id <> \"full_revert\"
				AND o.state_id <> \"partial_revert\" 
				AND o.state_id <> \"otmenit-zakaz\"
				AND p.name = \"axiomus_okay\"");
			$orders = $orders->fetchAll();           
 
			// создаем новый массив для быстрого доступа
			$indexes = array();
			foreach ($orders as $order) {
				$order["state_new"] = "";
				$order["post_code"] = "";
				$indexes[$order["id"]] = $order;
			}
			
			// сообщаем число заказов и время начала исполнения
			echo "\nCurrent time: "  . date('Y-m-d H:i:s', time()) . " (" . time() . "), Count of orders: " . count($orders) . "\n\n";

			// готовим документ запрос
			$xdoc = "<?xml version='1.0' standalone='yes'?><singleorder><mode>status_list</mode><okeylist>\n";
			foreach ($orders as $order) {
				$xdoc .= "<okey>".$order["axiomus_okay"]."</okey>\n";
			}
			$xdoc .= "</okeylist></singleorder>";
	
			// сохраняем запрос в файл
			//file_put_contents("./orders.cache.xml", $xdoc);
			
			// отправляем запрос в аксиомус
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
			curl_setopt($ch, CURLOPT_POST, 1); // set POST method
			curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".urlencode($xdoc)); // add POST fields
			$result = curl_exec($ch); // run the whole process
			curl_close($ch);
	
			// сохраняем ответ в файл
			//file_put_contents("./response.cache.xml", $result);
			// загружаем ответ из файла
			//$result = file_get_contents("./response.cache.xml");
	
			// обрабатываем ответ
			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->loadXML($result);
			$nodes_list = $doc->getElementsByTagName("okey");
			foreach ($nodes_list as $node){
				$order_id = $node->attributes->getNamedItem("inner_id")->value;
				$code = (int)$node->attributes->getNamedItem("status_code")->value;
				$tracking = $node->attributes->getNamedItem("tracking")->value;
				$status = $this->getStatusByCode($code);	
				echo $order_id, ' ', $code,' ',$status,' ',$node->nodeValue, PHP_EOL;
				// добавляем новый статус и номер почтового отправления
				$indexes[$order_id]["state_new"] = $status;
				if ($tracking != null){
					$indexes[$order_id]["post_code"] = $tracking;
				}
			}
			
			// отладка содержимого массива indexes
			//ksort($indexes);
			//$s_array = var_export($indexes, true);
			//file_put_contents("./axiomus_orders_array.php", $s_array);
			
			
			// пишем статусы в базу
			foreach($indexes as $order){				
				$order_id = $order['id'];
				$status = $order['state_new'];
				// проверяем, что статус можно изменить
				if( ($status != "") &&  // статус не окончательный
					($status != "deleted") && 
					($status != "completed") && 
					($status != "refunded") && 
					($status != "full_revert")&&
					($status != "partial_revert")&& 
					($status != "otmenit-zakaz")){
					if( ($order['state_id'] == "dostavka-zhdet-k") ||
						($order['state_id'] == "pozvonili-klient")){
							$status = $order['state_id'];
					}
				}		
				$post_code = $order['post_code'];
				if ($status != ""){
					$model->exec("UPDATE shop_order o SET o.state_id = \"$status\" WHERE o.id = \"$order_id\"");
				}
				// пишем номер почтового отправления
				if ($post_code != ""){
					$model->exec("DELETE FROM shop_order_params WHERE order_id = \"$order_id\" AND name = \"axiomus_postcode\"");
					$model->exec("INSERT INTO shop_order_params (order_id, name, value) VALUES (\"$order_id\", \"axiomus_postcode\", \"$post_code\")");
				}
			}
			
			// сообщаем об окончании выполнения запроса
			echo "\n\n\n\n * END OF PROCESSING * \n\n\n\n";
			$this->response = true;
		}

		if (waRequest::get('type') == "updatePosthubStatus") {

			// начало выполнения запросов
			echo "\n\n * PostHub * \n\n";
			
			// берем модель
			$model = new waModel();
			$date = date('Y-m-d H:i:s', strtotime("-1 month")); 
			header('Content-Type: text/plain; charset=utf-8');

			// получаем все нефинальные и недоставленные заказы, у которых есть почтовый код
			$orders = $model->query("SELECT o.id, o.state_id, o.comment, p.value AS post_code 
									FROM shop_order o 
									LEFT JOIN shop_order_params p ON p.order_id = o.id                 
									WHERE o.state_id <> \"deleted\" 
									  AND o.state_id <> \"completed\" 
									  AND o.state_id <> \"refunded\" 
									  AND o.state_id <> \"full_revert\"
									  AND o.state_id <> \"partial_revert\" 
									  AND o.state_id <> \"otmenit-zakaz\"  
									  AND o.state_id <> \"dostavka-zhdet-k\"
									  AND o.state_id <> \"pozvonili-klient\"
									  AND p.name = \"axiomus_postcode\"");
			$orders = $orders->fetchAll();           
			
			// обходим заказы и получаем их почтовые статусы
			foreach ($orders as $order) {
				sleep(5);
				echo "\nPostHub: ",$order['id']," ", $order['post_code'];
				$comment = $this->checkPosthubStatus($order['post_code']);
				if ($comment != null){ // если заказ доставлен, пишем в базу новый статус
					$comment = "Почтовое отправление " . $order['post_code'] . "\n" . $comment . "\n" . $order['comment'];
					$state_id = "dostavka-zhdet-k";
					$model->exec("UPDATE shop_order o SET o.state_id = \"$state_id\", o.comment = \"$comment\" WHERE o.id = \"".$order['id']."\"");
					echo " \n",$comment; 
				}
			}
			echo "\n";
			$this->response = true;
		}
		
		if (waRequest::get('type') == "updateRegions") {
			
			$xdoc = "<?xml version='1.0' standalone='yes'?><singleorder><mode>get_regions</mode><auth ukey=\"6420f1097a8c77ba1d7dc18df838d094\" /></singleorder>";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
			curl_setopt($ch, CURLOPT_POST, 1); // set POST method
			curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".urlencode($xdoc)); // add POST fields
			$result = curl_exec($ch); // run the whole process
			curl_close($ch);
			file_put_contents("./regions.cache.xml", $result);
			
			echo "\n\n Regions updated \n\n";
			$this->response = true;
		}
		
		if (waRequest::get('type') == "clearDB") {
			
			$model = new waModel();

			// выбрать пустые
			$skus = $model->query("SELECT s.*
								   FROM shop_product_skus s
								   WHERE s.name = \"\" ");
            $skus = $skus->fetchAll();
			
			// обойти что выбрали и назначить артикулы
			foreach ($skus as $sku) {			
				
				// получаем номер товара
				$skuid = $sku["id"];
				$pid = $sku["product_id"];
				
				// получаем все артикулы этого товара
				$psk = $model->query("SELECT s.*
									   FROM shop_product_skus s
									   WHERE s.product_id = $pid ");
				$psk = $psk->fetchAll();
				
				echo $sku["id"], " ", $pid ," : " , count($psk), "\n";
				foreach ($psk as $ps) {
					echo "\t", $ps["id"], " ", $pid, " ", $ps["name"],"\n";
				}
				
				// если общее число артикулов больше единицы,				
				if (count($psk) > 1){
					
					// удаляем текущий пустой артикул
					$model->exec("DELETE FROM shop_product_skus WHERE id = $skuid ");
					echo "\t delete $skuid \n";
					
					// снова выбираем все артикулы 
					$psk = $model->query("SELECT s.*
									   FROM shop_product_skus s
									   WHERE s.product_id = $pid ");
					$psk = $psk->fetchAll();
					$csk = count($psk);
						
					// и выставляем по умолчанию первый попавшийся
					foreach ($psk as $ps) {
						
						$dskuid = $ps[id];
						$model->exec("UPDATE shop_product p 
									  SET p.sku_id = $dskuid ,
										  p.sku_count = $csk, 
										  p.compare_price = p.compare_price_selectable
									  WHERE ( p.id = $pid ) AND ( p.price > 0 )  ");
						echo "\t product $pid, sku $dskuid, count $csk \n";
						break;
					}
				}
            }
			
			echo "\n\n", count($skus) ," sku \n\n";
			$this->response = true;
		}
    }
	
	private function checkPosthubStatus($posthub_id)
	{
		// получаем статус постхаба
		header('Content-Type: text/plain; charset=utf-8');
		$url = "https://posthub.ru/fasttrack/index.php?track=".$posthub_id;
		echo $url;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		$result = curl_exec($ch); // run the whole process
		curl_close($ch);
		
		// разбираем документ
		$doc = new DOMDocument('1.0', 'UTF-8');
		$doc->loadHTML($result);
		$tbody = $doc->getElementsByTagName('tbody');
		$tabl = $tbody->item(0);
		$rows = $tabl->getElementsByTagName('tr');
		 
		// обходим строки
		foreach ($rows as $row){
			$cols = $row->getElementsByTagName('td');
			$pdate = $cols->item(1)->textContent;
			$pstatus = $cols->item(2)->textContent;
			
			// проверяем, что доставлено
			$str_find = "/Прибыло в место вручения/iU";
			//$str_find = "/Партионный/iU";
			if (preg_match($str_find, $pstatus)){
				return $pdate." ".$pstatus;
			} 
		}
		return null;
	}

    private function getStatusByCode($code)
    {
        switch ($code) {

            case 0:
            case 1:
            case 2:
            case 5:
            case 6:
            case 20:
            case 30:
            case 80:
            case 81:
                return "processing";

            case 4:
            case 70:
            case 105:
                return "shipped";

            case 100:
            case 107:
                return "completed";

            case 110:
                return "partial_revert";

            case 120:
                return "full_revert";

            case -10:
            case 3:
            case 90:
                return "refunded";
				
            default:
                return "processing";
        }
    }

    private function _getStatus($axiomus_okay)
    {
        $xdoc = "<?xml version='1.0' standalone='yes'?><singleorder><mode>status</mode><okey>$axiomus_okay</okey></singleorder>";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".urlencode($xdoc)); // add POST fields
        $result = curl_exec($ch); // run the whole process
        curl_close($ch);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML($result);

        $status = "";

        foreach ($doc->documentElement->childNodes as $_status) {

            if ($_status->tagName != "status") 
                continue;

            $status = $_status->textContent;

            break;
        }

        sleep(1);

        echo "\tstatus on axiomus: " .  $status . "\n";

        switch (strtolower($status)) {

            case "товар на складе":
            case "укомплектован":
            case "в обработке":
            case "в процессе":
            case "перенос доставки":
            case "комплектация":
            case "сортировка":
            case "предотказ":
            case "предотмена":
                return "Обработка Axiomus";


            case "отправлен":
            case "исполнение":
            case "поступил на ПВЗ":
                return "Отправлен Аксиомус";


            case "выполнен=вручен":
            case "вручен":
            case "выполнен":
                return "Выполнен Аксиомус";


            case "частичный отказ":
                return "Частичный отказ Аксиомус";


            case "полный отказ":
                return "Полный отказ Аксиомус";


            case "отменен":
            case "отмена":
            case "отклонена":
            case "нет товара":
                return "Отменен Аксиомус";


            case "server too busy. retry \"status\" request after 1 seconds.":
                return "repeat";
            default:
                return "";

        }

        return $status;
    }

    public function setCompetedStatus($order_id) {

        $action_id = "complete";

        $workflow = new shopWorkflow();
        $action = $workflow->getActionById($action_id);
        $result = $action->run($order_id);

        // counters
        $order_model = new shopOrderModel();
        $state_counters = $order_model->getStateCounters();

        // update app coutner
        wa('shop')->getConfig()->setCount($state_counters['new']);
    }

    private function _getRegions()
    {
        $cache = "./regions.cache.xml";
        $doc = new DOMDocument('1.0', 'UTF-8');

        if (file_exists($cache)) {
            $doc->load($cache);
            return $doc;  
        }

        $xdoc = "<?xml version='1.0' standalone='yes'?><singleorder><mode>get_regions</mode><auth ukey=\"6420f1097a8c77ba1d7dc18df838d094\" /></singleorder>";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://www.axiomus.ru/hydra/api_xml.php"); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".urlencode($xdoc)); // add POST fields
        $result = curl_exec($ch); // run the whole process
        curl_close($ch);
        
        $doc->loadXML($result);
        file_put_contents($cache, $result);

        return $doc;
    }

    private function getRegions()
    {
        $doc = $this->_getRegions();
        $res = "";

        foreach ($doc->documentElement->childNodes as $region) {

            if ($region->tagName != "region") 
                continue;

            $res .= "<option value=\"".$region->getAttribute("region_code")."\">".$region->getAttribute("name")."</option>";
        }

        return $res;
    }

    private function getCourierCities($region_code)
    {
        $doc = $this->_getRegions();
        $res = "";

        foreach ($doc->documentElement->childNodes as $region) {

            if ($region->tagName != "region" || $region->getAttribute("region_code") != $region_code) 
                continue;

            foreach ($region->getElementsByTagName("courier")->item(0)->childNodes as $city) {
                
                $res .= "<option value=\"".$city->getAttribute("city_code")."\">".$city->textContent."</option>";
            }  
        }

        return $res;
    }

    private function getCityByCode($nodes, $code)
    {
        foreach ($nodes as $city) {
            if ($city->getAttribute("city_code") == $code) 
                return $city->textContent;
        }
    }

    private function getPickupPoints($region_code)
    {
        $doc = $this->_getRegions();
        $res = "";

        foreach ($doc->documentElement->childNodes as $region) {

            if ($region->tagName != "region" || $region->getAttribute("region_code") != $region_code) 
                continue;

            foreach ($region->getElementsByTagName("pickup")->item(0)->childNodes as $city) {
                
                $res .= "<option value=\"".$city->getAttribute("office_code")."\">г. ".$this->getCityByCode($region->getElementsByTagName("courier")->item(0)->childNodes, $city->getAttribute("city_code")).' '.$city->textContent."</option>";
            }  
        }

        return $res;
    }    
}
