<?php

class shopFastdeleteimagesPlugin extends shopPlugin {

	public function backend_product_edit($product)
	{
		$output = array();
		$view = wa()->getView();
		$html = $view->fetch(wa()->getAppPath('plugins/fastdeleteimages', 'shop').'/templates/patch.html');
		$output['images'] = $html;
		return $output;
    }

}