<?php

require_once __DIR__ . '/vendor/autoload.php';

class shopGdeposylkaUpdateCli extends waCliController
{
    protected $model;
    protected $client;
    protected $orders;
        
    public function execute()
    {
        // подготовка        
        $this->model = new waModel();                
        $this->client = new GdePosylka\Client\Client('3f61820a4779aa96eb5aa11d00c085895e69e9178437b22792b2817263089456a18686df295b39bc');
        
        // добавляем недостающие треки в слежку
        $this->addTracks();
        
        // проверяем почтовые статусы
        $this->getStatus();
        
        // удаляем ненужные треки из слежки
        $this->deleteTracks();
        
    }
    
    protected function addTracks()
    {
        echo "Первый вход \n";          
                
        // получаем список на проверку
        // (нефинальные, непочтовые, с треком)
        $this->orders = $this->model->query("SELECT o.id, o.state_id, o.comment, p.value AS track 
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
                                 AND p.name = \"axiomus_postcode\"")->fetchAll();
        
        //$s_array = var_export($this->orders, true);
        //echo "orders : \n" , $s_array , "\n";
        
        // получаем список треков в слежке
        $trackList = $this->client->getTrackingList('default')->getTrackings();
        $trackIndex = array();
        foreach ($trackList as $track){
            $trackIndex[$track->getTrackingNumber()] = "";            
        }
        
        $s_array = var_export($trackIndex, true);
        echo "trackIndex : \n" , $s_array , "\n";
        //file_put_contents("./axiomus_orders_array.php", $s_array);
            
        echo "добавлено : \n";
        
        // обходим проверку
        foreach($this->orders as $order){                    
            $trackingNumber = $order['track'];
            $orderNumber = $order['id'];
            // проверяем - есть ли проверка в слежке
            if (!array_key_exists($trackingNumber,$trackIndex)){        
                // если нет - добавляем
                $couriersResponse = $this->client->detectCourier($trackingNumber)->getCouriers();
                $courierSlug = current($couriersResponse)->getSlug();
                $fields = new GdePosylka\Client\TrackingFields();
                $fields->setTitle("s$orderNumber"); // Fields are optional
                $track = $this->client->addTracking($courierSlug, $trackingNumber, $fields);
                
                $s_array = var_export($track, true);
                echo "      трэк : \n", $trackingNumber, "  =>  " , $s_array , "\n";
            }
        }       
    }
    
    protected function getStatus()
    {
        echo "\n\n Второй вход \n\n";
        
        // получаем статусы 
        foreach($this->orders as $order){                    
            
            $trackingNumber = $order['track'];            
            $couriersResponse = $this->client->detectCourier($trackingNumber)->getCouriers();
            $courierSlug = current($couriersResponse)->getSlug();
            $trackInfo = $this->client->getTrackingInfo($courierSlug, $trackingNumber);            
            
            echo " проверка : ", $order['id'] , " - ", $trackingNumber, "\n";

            // обходим все контрольные точки 
            foreach ($trackInfo->getCheckpoints() as $checkpoint) {            
                // если находим, что посылка прибыла - ставим статус - прибыло
                if($checkpoint->getStatus() == 'arrived'){               
                    $comment = "Почтовое отправление " . $trackingNumber . "\n" . $checkpoint->getMessage() . "\n" . $order['comment'];
                    $state_id = "dostavka-zhdet-k";
                    $this->model->exec("UPDATE shop_order o SET o.state_id = \"$state_id\", o.comment = \"$comment\" WHERE o.id = \"".$order['id']."\"");
                    echo " доставлено \n";
                    break;
                }                
            }
            
            
        }        
    }
    
    protected function deleteTracks()
    {        
        echo "\n\n Третий вход \n\n";
        
        // получаем список треков в слежке
        $trackList = $this->client->getTrackingList('default')->getTrackings();        
        
        //$s_array = var_export($trackList, true);
        //echo "tracks : \n" , $s_array , "\n";
        
        // проверяем - какой у трека статус в заказе
        foreach ($trackList as $track){
            $trackingNumber = $track->getTrackingNumber();
            $orders = $this->model->query("SELECT o.id, o.state_id, o.comment, p.value AS track 
                                 FROM shop_order_params p
                                 LEFT JOIN shop_order o ON o.id = p.order_id
                                 WHERE  p.name = \"axiomus_postcode\"
                                 AND p.value = \"".$trackingNumber."\"")->fetchAll();
            
            // если статус - финальный - удаляем из слежки
            if (isset($orders)){            
                $order = current($orders);
                echo "track : " , $order['id'] ," ", $order['state_id'] ," ", $trackingNumber , " ";
                if (isset($order)){
                    $state = $order['state_id'];
                    if (($state == 'deleted')||
                        ($state == 'completed')||
                        ($state == 'refunded')||
                        ($state == 'full_revert')||
                        ($state == 'partial_revert')||
                        ($state == 'otmenit-zakaz')){      
                        
                        echo " - delete";
                        $couriersResponse = $this->client->detectCourier($trackingNumber)->getCouriers();
                        $courierSlug = current($couriersResponse)->getSlug();
                        $this->client->deleteTracking($courierSlug, $trackingNumber);
                    }
                }
                echo "\n";
            }
        }
    }
}
