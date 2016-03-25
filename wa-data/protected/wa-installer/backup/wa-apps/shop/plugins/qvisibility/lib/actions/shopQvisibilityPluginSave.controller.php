<?php
class shopQvisibilityPluginSaveController extends waJsonController
{
	protected $status;
	
    public function execute()
    {
		$this->status = waRequest::post('status');
		if ($this->status === null) {
            return;
        }
	
		$product_model = new shopProductModel();
        $hash = waRequest::post('hash', '');
        if (!$hash) {
            $product_ids = waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT);
            if (!$product_ids) {
                return;
            }
            $products = $product_model->select('id,type_id')->where('id IN (i:ids)', array('ids' => $product_ids))->fetchAll('id');
			$this->setStatus($products);
        } else {
            $collection = new shopQvisibilityProductsCollection($hash);
            $offset = 0;
            $count = 100;
            $total_count = $collection->count();
            while ($offset < $total_count) {
                $products = $collection->getProducts('id,type_id', $offset, $count);
                $this->setStatus($products);
                $offset += count($products);
            }
        }
    }
	
	protected function setStatus($products) {
		$model = new shopProductModel();
		$pids = array();
		foreach ($products as $product) {
			$pids[] = $product['id'];
		}
		
		$model->updateById($pids, array(
			'status' => $this->status
		));
	}
}