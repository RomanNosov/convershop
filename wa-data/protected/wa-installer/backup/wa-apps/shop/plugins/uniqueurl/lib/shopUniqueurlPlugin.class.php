<?php

class shopUniqueurlPlugin extends shopPlugin
{

	public function backendProduct($product)
	{
		$model = new shopProductModel();
		$count = $model->countByField(array('url'=>$product["url"]/*,'category_id'=>$product["category_id"]*/));

		if ($count > 1)
		{
			$count--;
			return array('title_suffix' => "<span style='font-size: 0.5em; color: rgb(219, 0, 0);'>(неуникальный адрес, <a href='#/products/hash=uniqueurl\\{$product['url']}'>ещё $count</a>)</span>");
		}
	}

	public function productsCollection($params)
	{
	    $collection = $params['collection'];

	    $hash = $collection->getHash();

		if (strpos($hash['0'], 'uniqueurl\\')===0 && wa()->getUser()->getId())
	    {
	    	$hash_arr = explode('\\', $hash['0']);
	    	if (sizeof($hash_arr)==2)
	    	{
	    		$model = new shopProductModel();
				$count = $model->countByField(array('url'=>$hash_arr['1']));
				if ($count < 2)
					return null;

	    		$collection->addWhere("url ='".($hash_arr['1'])."'");
	    		$collection->addTitle('Товары с неуникальным адресом'/* . $hash_arr['1'] .'"'*/);
	    	}
	    	return true;
	    }
	    else
	    {
		    if ($hash['0'] !== 'uniqueurl' || !wa()->getUser()->getId()) {
		        return null;
		    }

		    /* Старый вариант, "неоптимальный" */
			// $collection->addWhere("url IN (SELECT url FROM shop_product GROUP BY url HAVING COUNT(id) > 1)");

			/* Новый вариант по советам вебасиста */
			$waModel = new waModel();
			$data = $waModel->query("SELECT GROUP_CONCAT(id) as ids FROM shop_product GROUP BY url HAVING COUNT(*) > 1")->fetchAll();
			$ids = "0";
			foreach ($data as $item) {
				$ids .= ','.$item['ids'];
			}
			$collection->addWhere("id IN (".$ids.")");
			/* Новый вариант по советам вебасиста */


		    if ($params['auto_title']) {
		        $collection->addTitle('Товары с неуникальным адресом');
		    }
		    return true;
		}
	}

	 public function backendProducts($param)
	 {
	 	$collection = new shopProductsCollection('uniqueurl');
	 	$count = $collection->count();
	 	return array(
            'sidebar_top_li' => '<li id="uniqueurl-"><span class="count">'.$count.'</span><a href="#/products/hash=uniqueurl"><i class="icon16 folder"></i>'.'Неуникальные адреса'.'</a></li>',
        );
	 }

}