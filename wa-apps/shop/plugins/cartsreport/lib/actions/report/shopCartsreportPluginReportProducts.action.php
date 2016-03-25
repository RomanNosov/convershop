<?php

class shopCartsreportPluginReportProductsAction extends waViewAction {

    public function  execute()
    {
        $this->view->assign(array(
            'data' => $this->getData(),
        ));
    }

    private function getData()
    {
        /**
         * @todo
         */
        $on_page = 10;
        $data = array();

        $m = new shopCartItemsModel();
        $sql = "SELECT SUM(quantity) as qty, product_id FROM shop_cart_items WHERE ".$this->getTimeQuery().
            ' GROUP BY product_id ORDER BY qty DESC';


        $items = $m->query($sql)->fetchAll('product_id', true);
        if($items) {
            $other = 0;

            if(count($items) > $on_page) {

                $items1 = array();
                $i = 0;
                foreach($items as $product_id => $quantity) {
                    if($i++ < $on_page) {
                        $items1[$product_id] = $quantity;
                    } else {
                        $other += $quantity;
                    }
                }
                $items = $items1;
                unset($items1);
            }

            $pm = new shopProductModel();
            $products = $pm->select('id, name')->where('id IN(?)', array(array_keys($items)))->fetchAll('id', true);


            foreach ($items  as $product_id => $quantity) {
                $data[] = array(
                    'label' => ifset($products[$product_id], _wp('(no name)')),
                    'value' => $quantity,
                    'id' => $product_id
                );
            }
            if($other) {
                $data[] = array(
                    'label' => _wp('Other...'),
                    'value' => $other,
                    'id' => 'other'
                );
            }
        }

        return $data;
    }

    private function getTimeQuery()
    {
        $days = waRequest::get('timeframe');

        $where = 'type = "product" ';

        if(($days == 'custom') && waRequest::get('from') && waRequest::get('to')) {
            $from = date('Y-m-d 00:00:00', waRequest::get('from'));
            $to = date('Y-m-d 23:59:59', waRequest::get('to'));
            $where .= 'AND create_datetime BETWEEN \''.$from. '\' AND \''.$to."'";
        } elseif((int)$days) {
            $where .= 'AND create_datetime > (NOW() - interval '.((int)$days).' day)';
        }

        return $where;
    }
}