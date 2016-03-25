<?php

/**
 * @property string $test_mode
 * @property string $api_key
 * @property string $zip
 * @property string $length
 * @property string $height
 * @property string $width
 */
class variantsShipping extends waShipping {

    /**
     * @var string
     */
    private $currency = 'RUB';

    /**
     *
     * @return string ISO3 currency code or array of ISO3 codes
     */
    public function allowedCurrency() {
        return $this->currency;
    }

    /**
     *
     * @return string Weight units or array of weight units
     */
    public function allowedWeightUnit() {
        return 'kg';
    }

    /**
     * @return array|string
     */
    protected function calculate() {

        $user_ip = getenv('REMOTE_ADDR');
        $geo = unserialize(file_get_contents("http://www.geoplugin.net/php.gp?ip=$user_ip"));
        
        // суммарная стоимость отправления
        $total = $this->getTotalPrice();
        $delivery = $total <= 2500;

//            if($delivery) switch($data->deltype){
//                case 'msc1': $totalWithDiscount += 300; break;
//                case 'msc2': $totalWithDiscount += 300; break;
//                case 'spb1': $totalWithDiscount += 300; break;
//                case 'spb2': $totalWithDiscount += 200; break;
//                case 'post': $totalWithDiscount += 400; break; 
//                case 'regcour': $totalWithDiscount += 300; break; 
//                case 'regpick': $totalWithDiscount += 200; break;
//                //case 'msc1': $totalWithDiscount += 0; break;
//                //case 'msc2': $totalWithDiscount += 0; break;
//                //case 'spb1': $totalWithDiscount += 0; break;
//                //case 'spb2': $totalWithDiscount += 0; break;
//                //case 'post': $totalWithDiscount += 0; break; 
//                //case 'regcour': $totalWithDiscount += 300/*450*/; break; 
//                //case 'regpick': $totalWithDiscount += 200/*350*/; break;
//            } else switch($data->deltype){
//                case 'msc1': $totalWithDiscount += 0; break;
//                case 'msc2': $totalWithDiscount += 0; break;
//                case 'spb1': $totalWithDiscount += 0; break;
//                case 'spb2': $totalWithDiscount += 0; break;
//                case 'post': $totalWithDiscount += 0; break; 
//                case 'regcour': $totalWithDiscount += 300/*450*/; break; 
//                case 'regpick': $totalWithDiscount += 200/*350*/; break;
//            }
//            
//            $tmp_address_type = array(
//                'msc1' => "Москва - курьер, в пределах МКАД " . ($delivery ? "( 300 рублей )" : "( бесплатно )"),
//                'msc2' => "Москва - курьер, за МКАД " . ($delivery ? "( 300 рублей + 30 рублей/км )" : "( 30 рублей/км )"), // "( бесплатно )"),
//                'spb1' => "СПб - курьер" . ($delivery ? "( 300 рублей )" : "( бесплатно )"),
//                'spb2' => "СПб - самовывоз" . ($delivery ? "( 200 рублей )" : "( бесплатно )"),
//                'post' => "Почта" . ($delivery ? "( 400 рублей )" : "( бесплатно )"),
//                'regcour' => "Регионы - курьер" . ($delivery ? "( 300 рублей )" /*"( 450 рублей )"*/ : "( 300 рублей )"),
//                'regpick' => "Регионы - самовывоз" . ($delivery ? "( 200 рублей )" /*"( 350 рублей )"*/ : "( 200 рублей )")
//            );
//            

//        $path = wa()->getConfig()->getPath('log');
//        waFiles::create($path.'/shop/axiomus.log');
//        waLog::log("calculate", 'shop/axiomus.log');   

        
       
//$checkout_data = $this->getStorage()->get('shop/checkout');
 //        $sh_id = $checkout_data['shipping']['rate_id'];
        $user = strpos($_SERVER["REQUEST_URI"], "webasyst") === false;
        
        // if ($geo["geoplugin_city"] == "Saint Petersburg" && $user) {

        //     $del = array(
        //         'spb1' => array(
        //             'name' => "СПб - курьер", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? '300 рублей' : '300 рублей'),
        //             'rate' => ($delivery ? '300 рублей' : '300 рублей'), //точная стоимость доставки
        //         ),
        //         'spb2' => array(
        //             'name' => "СПб - самовывоз", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? '200 рублей' : '200 рублей'),
        //             'rate' => ($delivery ? '200 рублей' : '200 рублей'), //точная стоимость доставки
        //         )
        //     );

        // } else

        // if ($geo["geoplugin_city"] == "Moscow" && $user) {

        //     $del = array(
        //         'msc1' => array(
        //             'name' => "Москва - курьер, в пределах МКАД", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? "300 рублей" : "300 рублей"),
        //             'rate' => ($delivery ? "300 рублей" : "300 рублей"), //точная стоимость доставки
        //         ),
        //         'msc2' => array(
        //             'name' => "Москва - курьер, за МКАД", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? '300 рублей + 30 рублей/км' : '300 рублей + 30 рублей/км'),
        //             'rate' => ($delivery ? '300 рублей + 30 рублей/км' : '300 рублей + 30 рублей/км'), //точная стоимость доставки
        //         ),
        //         'msc3' => array(
        //             'name' => "Москва - самовывоз", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? '0 рублей' : '0 рублей'),
        //             'rate' => ($delivery ? '0 рублей' : '0 рублей'), //точная стоимость доставки
        //         )
        //     );

        // } else if ($geo["geoplugin_city"] && $user) {

        //     $del = array(
        //         'post' => array(
        //             'name' => "Почта", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? '400 рублей' : '400 рублей'),
        //             'rate' => ($delivery ? '400 рублей' : '400 рублей'), //точная стоимость доставки
        //         ),
        //         'regcour' => array(
        //             'name' => "Регионы - курьер", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? '300 рублей' : '300 рублей'),
        //             'rate' => ($delivery ? '300 рублей' : '300 рублей'), //точная стоимость доставки
        //         ),
        //         'regpick' => array(
        //             'name' => "Регионы - самовывоз", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
        //             'description' => '', //необязательное описание варианта  доставки
        //             'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
        //             'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
        //             'rate_orig' => ($delivery ? '300 рублей' : '300 рублей'),
        //             'rate' => ($delivery ? '300 рублей' : '300 рублей'), //точная стоимость доставки
        //         )
        //     );

        // } else {

            $del = array(
                'msc1' => array(
                    'name' => "Москва - курьер, в пределах МКАД", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->msc1 . " рублей" : $this->msc1 . " рублей"),
                    'rate' => ($delivery ? $this->msc1 . " рублей" : $this->msc1 . " рублей"), //точная стоимость доставки
                ),
                'msc2' => array(
                    'name' => "Москва - курьер, за МКАД", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->msc2 . ' рублей + ' . $this->msc2km . ' рублей/км' : $this->msc2 . ' рублей + ' . $this->msc2km . ' рублей/км'),
                    'rate' => ($delivery ? $this->msc2 . ' рублей + ' . $this->msc2km . ' рублей/км' : $this->msc2 . ' рублей + ' . $this->msc2km . ' рублей/км'), //точная стоимость доставки
                ),
                'msc3' => array(
                    'name' => "Москва - самовывоз", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->msc3 . ' рублей' : $this->msc3 . ' рублей'),
                    'rate' => ($delivery ? $this->msc3 . ' рублей' : $this->msc3 . ' рублей'), //точная стоимость доставки
                ),
                'spb1' => array(
                    'name' => "СПб - курьер", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->spb1 . ' рублей' : $this->spb1 . ' рублей'),
                    'rate' => ($delivery ? $this->spb1 . ' рублей' : $this->spb1 . ' рублей'), //точная стоимость доставки
                ),
                'spb2' => array(
                    'name' => "СПб - самовывоз", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->spb2 . ' рублей' : $this->spb2 . ' рублей'),
                    'rate' => ($delivery ? $this->spb2 . ' рублей' : $this->spb2 . ' рублей'), //точная стоимость доставки
                ),
                'post' => array(
                    'name' => "Почта", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->post . ' рублей' : $this->post . ' рублей'),
                    'rate' => ($delivery ? $this->post . ' рублей' : $this->post . ' рублей'), //точная стоимость доставки
                ),
                'regcour' => array(
                    'name' => "Регионы - курьер", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->regcour . ' рублей' : $this->regcour . ' рублей'),
                    'rate' => ($delivery ? $this->regcour . ' рублей' : $this->regcour . ' рублей'), //точная стоимость доставки
                ),
                'regpick' => array(
                    'name' => "Регионы - самовывоз", //название варианта доставки, например, “Наземный  транспорт”, “Авиа”, “Express Mail” и т. д.
                    'description' => '', //необязательное описание варианта  доставки
                    'est_delivery' => '', //произвольная строка, содержащая  информацию о примерном времени доставки
                    'currency' => $this->currency, //ISO3-код валюты, в которой рассчитана  стоимость  доставки
                    'rate_orig' => ($delivery ? $this->regpick . ' рублей' : $this->regpick . ' рублей'),
                    'rate' => ($delivery ? $this->regpick . ' рублей' : $this->regpick . ' рублей'), //точная стоимость доставки
                )
            );

        // }
        
        return $del;
    }

}
