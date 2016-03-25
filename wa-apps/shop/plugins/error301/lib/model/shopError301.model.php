<?php
class shopError301Model extends waModel
{
    public $table = 'shop_error301';
	
	public function deleteHistoryByID($ids, $type)
	{
		if($type == 'x')
		{
			foreach($ids as $id)
				$this->exec('DELETE FROM '.$this->table.' WHERE `url` LIKE ? AND `type` = ?', $id."#%", $type[0]);
		}
		else
		{
			foreach($ids as $id)
				$this->exec('DELETE FROM '.$this->table.' WHERE `id` = ? AND `type` = ?', (int)$id, $type[0]);
		}
	}
	
	public function getItem($arUrl, $modesearch = 'all', $type_product_id = 0)
	{
		$url = $arUrl[count($arUrl) - 1];
		
		if($modesearch == 'all')
		{
			$subquery = '';
			if(is_array($type_product_id))
			{
				$subquery = ' AND (`shop_product`.`type_id` = '.implode(" OR `shop_product`.`type_id` =", $type_product_id).')';
			}	
			
			if(count($arUrl) > 1)
			{
				$preurl = $arUrl[count($arUrl) - 2];			
				
				/*если это текущий url продукта и текущий url страницы*/
				$result = $this->query("SELECT `shop_product_pages`.`url` as `page`, `shop_product`.`url` as `url`, `shop_category`.`url` as `cat_url`, `shop_category`.`full_url` as `cat_url_full` FROM `shop_product_pages` RIGHT JOIN `shop_product` ON (`shop_product_pages`.`product_id` = `shop_product`.`id`) RIGHT JOIN `shop_category` ON (`shop_product`.`category_id` = `shop_category`.`id`) WHERE `shop_product`.`url` = s:preurl AND `shop_product_pages`.`url` = s:url".$subquery.";", array('url' => $url, 'preurl' => $preurl))->fetch();	
				
				/*если это текущий url продукта и старый url страницы*/
				if(!isset($result['page']))
				{
					$result = $this->query("SELECT `shop_product_pages`.`url` as `page`, `shop_product`.`url` as `url`, `shop_category`.`url` as `cat_url`, `shop_category`.`full_url` as `cat_url_full` FROM `".$this->table."` RIGHT JOIN `shop_product` ON (`shop_product`.`id` = LEFT(`".$this->table."`.`url`, INSTR(`".$this->table."`.`url`,'#')-1)) RIGHT JOIN `shop_category` ON (`shop_product`.`category_id` = `shop_category`.`id`) RIGHT JOIN `shop_product_pages` ON (`".$this->table."`.`id` = `shop_product_pages`.`id`) WHERE `".$this->table."`.`type` = 'x' AND `".$this->table."`.`url` LIKE s:url AND `shop_product`.`url` = s:preurl".$subquery.";", array('url' => "%#".$url, 'preurl' => $preurl))->fetch();
				}
				
				/*если это старый url продукта и старый url страницы*/
				if(!isset($result['page']))
				{
					$result = $this->query("SELECT `shop_product_pages`.`url` as `page`, `shop_product`.`url` as `url`, `shop_category`.`url` as `cat_url`, `shop_category`.`full_url` as `cat_url_full` FROM `".$this->table."` RIGHT JOIN `".$this->table."` as `".$this->table."_2` ON (`".$this->table."_2`.`id` = LEFT(`".$this->table."`.`url`, INSTR(`".$this->table."`.`url`,'#')-1)) RIGHT JOIN `shop_product` ON (`shop_product`.`id` = LEFT(`".$this->table."`.`url`, INSTR(`".$this->table."`.`url`,'#')-1)) RIGHT JOIN `shop_category` ON (`shop_product`.`category_id` = `shop_category`.`id`) RIGHT JOIN `shop_product_pages` ON (`".$this->table."`.`id` = `shop_product_pages`.`id`) WHERE (`".$this->table."`.`type` = 'x' AND `".$this->table."`.`url` LIKE s:url AND `".$this->table."_2`.`url` = s:preurl)".$subquery.";", array('url' => "%#".$url, 'preurl' => $preurl))->fetch();
				}
			}
			
			/*если это текущий url продукта*/
			if(!isset($result['url']))
			{
				$result = $this->query("SELECT `shop_product`.`url` as `url`, `shop_category`.`url` as `cat_url`, `shop_category`.`full_url` as `cat_url_full` FROM `shop_product` RIGHT JOIN `shop_category` ON (`shop_product`.`category_id` = `shop_category`.`id`) WHERE `shop_product`.`url` = s:url".$subquery.";", array('url' => $url))->fetch();
			}
		
			/*если это старый url продукта*/
			if(!isset($result['url']))
			{
				$result = $this->query("SELECT `shop_product`.`url` as `url`, `shop_category`.`url` as `cat_url`, `shop_category`.`full_url` as `cat_url_full` FROM `".$this->table."` RIGHT JOIN `shop_product` ON (`".$this->table."`.`id` = `shop_product`.`id`) RIGHT JOIN `shop_category` ON (`shop_product`.`category_id` = `shop_category`.`id`) WHERE `".$this->table."`.`url` =  s:url".$subquery." AND `".$this->table."`.`type` = 'p';", array('url' => $url))->fetch();
			}	
		}
		
		/*если это текущий url категории*/
		if(!isset($result['url']))
		{
			$result = $this->query("SELECT `shop_category`.`url` as `cat_url`, `shop_category`.`full_url` as `cat_url_full` FROM `shop_category` WHERE `shop_category`.`url` =  s:url;", array('url' => $url))->fetch();
		}
		
		/*если это старый url категории*/
		if(!isset($result['cat_url']))
		{
			$result = $this->query("SELECT `shop_category`.`url` as `cat_url`, `shop_category`.`full_url` as `cat_url_full` FROM `".$this->table."` RIGHT JOIN `shop_category` ON (`".$this->table."`.`id` = `shop_category`.`id`) WHERE `".$this->table."`.`url` = s:url AND `".$this->table."`.`type` = 'c';", array('url' => $url))->fetch();
		}
		
		return $result;
	}
	
	public function index()
	{
		$this->exec("INSERT IGNORE INTO `".$this->table."` SELECT `id`, 'c' as `type`, `url` FROM `shop_category`;");
		$this->exec("INSERT IGNORE INTO `".$this->table."` SELECT `id`, 'p' as `type`, `url` FROM `shop_product`;");
		$this->exec("INSERT IGNORE INTO `".$this->table."` SELECT `id`, 'x' as `type`, CONCAT(`product_id`,'#',`url`) as `url` FROM `shop_product_pages`;");
	}
}
