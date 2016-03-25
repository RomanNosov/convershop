<?php 

class shopYmproductreviewsPluginFrontendAction extends waViewAction  {

    public function execute()
    {
        $ym_model_id = waRequest::param('ym_model_id');
        $page = waRequest::post('page');
        $tmp_path = 'plugins/ymproductreviews/templates/Ymproductreviews.html';
        
        $plugin = shopYmproductreviewsPlugin::getThisPlugin();

        if($plugin->getSettings('status')) {
            $view = wa()->getView();
            $error = null;
            try {
                $reviews = $plugin->getYandexMarketReviews($ym_model_id, $page);
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            $reviews_list = array();
            if(isset($reviews['modelOpinions']['opinion'])) {
                $reviews_list = $reviews['modelOpinions']['opinion'];
                foreach($reviews_list as &$review) {
                    $review['date'] = date('d-m-Y', $review['date'] / 1000);
                }                  
                $pages = $reviews['modelOpinions']['total']/$reviews['modelOpinions']['count'];
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
            echo $html;
            exit;
        }
        
    }
}
