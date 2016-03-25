<?php 

class shopYmproductreviewsPlugin extends shopPlugin
{
    protected static $plugin;
    
    public function __construct($info)
    {
        parent::__construct($info);
        if(!self::$plugin) {
            self::$plugin = &$this;
        }
    }
    
    public static function getThisPlugin()
    {
        if(self::$plugin) {
            return self::$plugin;
        } else {
            return wa()->getPlugin('ymproductreviews'); 
        }       
    }
    
    public function backendProductEdit($product)
    {
        $link = '';
        if($product->ym_model_id) {
            $link = '<a style="margin-left:10px;" target="_blank" href="http://market.yandex.ru/product/'.$product->ym_model_id.'/reviews/">market.yandex.ru</a>';
        }
        $html = '<div class="field">
                    <div class="name">Яндекс.Маркет product_id</div>
                    <div class="value no-shift">
                        <input type="text" name="product[ym_model_id]" value="'.$product->ym_model_id.'" class="bold numerical" style="width:100px;">'.$link.'<br/>
                        <span class="hint">http://market.yandex.ru/product/[xxxxxxx]/reviews/</span><br/>
                        <span class="hint">xxxxxxx - числовое значение product_id</span>
                        
                    </div>
                </div>';
        return array('basics' => $html);
    }
    
    public function frontendProduct($product)
    {
        if($this->getSettings('default_output')) {
            $html = self::display($product['ym_model_id']);
            return array('block' => $html);
        }
    }
    
    
    
    public static function display($ym_model_id)
    {

        $tmp_path = 'plugins/ymproductreviews/templates/Ymproductreviews.html';
        
        $plugin = self::getThisPlugin();

        if($plugin->getSettings('status')) {
            $view = wa()->getView();
            $error = null;
            try {
                $reviews = $plugin->getYandexMarketReviews($ym_model_id);
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            $reviews_list = array();
            if(isset($reviews['modelOpinions']['opinion'])) {
                $reviews_list = $reviews['modelOpinions']['opinion'];
                foreach($reviews_list as &$review) {
                    $review['date'] = date('d-m-Y', $review['date'] / 1000);
                }                   

                if($reviews['modelOpinions']['count']) {
$pages = $reviews['modelOpinions']['total']/$reviews['modelOpinions']['count'];
} else {
$pages = 1;
}
                $view->assign('pages', $pages);
            }
            
            
            
            
            
            $view->assign('ym_model_id', $ym_model_id);
            $view->assign('error', $error);
            $view->assign('ym_reviews', $reviews_list);
            
            $template_path = wa()->getDataPath($tmp_path, false, 'shop', true);
            if(!file_exists($template_path)) {
                $template_path = wa()->getAppPath($tmp_path,  'shop');
            }
            
    		$html = $view->fetch($template_path);
            return $html;
        }
    }
    
    public function getYandexMarketReviews($model_id = null,$page = 1)
    {
        if(!$model_id) {
            return false;
        }

        $settings = $this->getSettings();
        $auth_key = $settings['auth_key'];
        $params = array('count' => '', /*'grade' => '',*/ 'sort' => '', 'how' => '','page' => 'page='.$page);
        
        foreach($params as $name => &$param) {
            if(isset($settings[$name])) {
                $param = $name.'='.$settings[$name];
            }            
        }      
        
        if(!$auth_key) {
            throw new waException('Ошибка. Не указан Яндекс.Маркет ключ авторизации');
        }
        
        $url = "https://api.content.market.yandex.ru/v1/model/$model_id/opinion.json?".implode('&',$params);    
        $headers = array(
              "Host: api.content.market.yandex.ru",
              "Accept: */*",
              "Authorization: $auth_key"
        );

        $cache = new waSerializeCache(base64_encode($this->app_id.$this->id.'.'.'getYandexMarketReviews.'.$url.'.'.$auth_key));
        if($cache && $cache->isCached()) {
            $reviews = $cache->get();
        } else {
            $reviews = $this->sendRequest($url,null,$headers,'GET');

            
            if ($reviews && $cache) {
                $cache->set($reviews);
            }
        }

        
        if(isset($reviews['errors'])) {
            throw new waException('Ошибка. '.implode(', ',$reviews['errors']));
        }

        return $reviews;
    }
    
    protected function sendRequest($url, $data = null, $headers = null, $method = 'POST')
    {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new waException('PHP расширение cURL не доступно');
        }

        if (!($ch = curl_init())) {
            throw new waException('curl init error');
        }
        
        if (curl_errno($ch) != 0) {
            throw new waException('Ошибка инициализации curl: '.curl_errno($ch));
        }
        
        @curl_setopt($ch, CURLOPT_URL, $url);
        if($headers) {
            @curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
        }
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($method == 'POST') {
            @curl_setopt($ch, CURLOPT_POST, 1);
            @curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = @curl_exec($ch);
        $app_error = null;
        if (curl_errno($ch) != 0) {
            $app_error = 'Ошибка curl: '.curl_error($ch);
        }
        curl_close($ch);
        if ($app_error) {
            throw new waException($app_error);
        }
        if (empty($response)) {
            throw new waException('Пустой ответ от сервера');
        }
        
        $json = json_decode($response,true);
        
        $return = json_decode($response,true);
        if(!is_array($return)) {
            return $response;
        } else {
            return $return;
        }
    }    
}
