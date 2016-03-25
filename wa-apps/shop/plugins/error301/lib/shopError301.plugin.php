<?php 
class shopError301Plugin extends shopPlugin
{
	static public function index()
	{
		$model = new shopError301Model();
		$model->index();
	}
	
	public function categorySave($category)
	{
		$model = new shopError301Model();
		$data = array(
			"id" => $category['id'],
			"type" => "c",
			"url" => $category['url'],
		);
		$model->insert($data, 1);
	}
	
	public function categoryDelete($item)
	{
		$model = new shopError301Model();
		$model->deleteHistoryByID(array($item['id']), "c");
	}
	
	public function pageSave()
	{
		$get = waRequest::get();
		
		if(isset($get['action']) AND isset($get['module']) AND $get['action'] == 'pageSave' AND $get['module'] == 'product')
		{
			$model = new shopError301Model();
			if(isset($get['id']) AND $get['id'] > 0)
			{
				$post = waRequest::post();
				$data = array(
					"id" => $get['id'],
					"type" => "x",
					"url" => $get['product_id']."#".$post['info']['url'],
				);
				$model->insert($data, 1);
			}
			else
			{
				$model->exec("INSERT IGNORE INTO `".$model->table."` SELECT `id`, 'x' as `type`, CONCAT(`product_id`,'#',`url`) as `url` FROM `shop_product_pages`;");
			}
		}
	}
	
	public function productSave($params)
	{
		$model = new shopError301Model();
		$data = array(
			"id" => $params['data']['id'],
			"type" => "p",
			"url" => $params['data']['url'],
		);
		$model->insert($data, 1);
	}
	
	public function productDelete($ids)
	{
		$model = new shopError301Model();
		$model->deleteHistoryByID($ids['ids'], "p");
		$model->deleteHistoryByID($ids['ids'], "x");
	}
	
	public function frontendError($params)
	{
		$model_settings = new waAppSettingsModel();
        $status = $model_settings->get($key = array('shop', 'error301'));
		
		if ($params->getCode() == 404 AND isset($status['status']) AND $status['status']==1)
		{
            $redirect = $this->getRedirect();		
			
			if($redirect)
				wa()->getResponse()->redirect($redirect, 301);
        }
	}
	
	public function getRedirect()
	{
		$routing = wa()->getRouting();
		$curRouting = $routing->dispatch();
		$url = $routing->getCurrentUrl();
		
		$expUrl = explode("/", trim($url, "/"));
		$modesearch = $expUrl[0] == 'category' ? 'category' : 'all'; 
		
		$reviews = '';
		if($expUrl[count($expUrl) - 1] == 'reviews')
		{
			$reviews = 'reviews/';
			unset($expUrl[count($expUrl) - 1]);			
		}		

		$model = new shopError301Model();
		$item = $model->getItem($expUrl, $modesearch, $curRouting['type_id']);
		
		$base = wa()->getRouteUrl('shop/frontend', array(), true);
		
		if(isset($item['url']))
		{
			$page = isset($item['page']) ? $item['page']."/" : "";
			
			if($curRouting['url_type'] == 0)
				$newUrl = $item['url']."/".$page;
			elseif($curRouting['url_type'] == 1)
				$newUrl = "product/".$item['url']."/".$page;
			elseif($curRouting['url_type'] == 2)
				$newUrl = $item['cat_url_full']."/".$item['url']."/".$page;
		}
		elseif(isset($item['cat_url']))
		{
			if($curRouting['url_type'] == 0)
				$newUrl = "category/".$item['cat_url_full']."/";
			elseif($curRouting['url_type'] == 1)
				$newUrl = "category/".$item['cat_url']."/";
			elseif($curRouting['url_type'] == 2)
				$newUrl = $item['cat_url_full']."/";
		}
		
		if(isset($newUrl))
			return $base.$newUrl.$reviews;
		else 
			return false;		
	}
}