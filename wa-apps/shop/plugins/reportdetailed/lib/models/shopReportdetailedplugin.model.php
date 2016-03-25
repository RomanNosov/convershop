<?php

class shopReportdetailedpluginModel extends waModel
{
    public function getStat($dates, $params){
        // считаем продажи
        $default_dates = array('start_date'=>NULL, 'end_date'=>NULL, 'group'=>'days');
        $dates = array_merge($default_dates, $dates);
        $where = 'true';
        $user_date = $params['dates'];
        $states = $params['states'];
        if($user_date!='paid_date'){
            $user_date = 'create_datetime';
        }
        $order_date = "o.$user_date";
        if($dates['group'] == 'months'){
            $date_str = "DATE_FORMAT($order_date, '%Y-%m-01')";
        }
        elseif($dates['group'] == 'weeks'){
            $date_str = "STR_TO_DATE(CONCAT(YEARWEEK($order_date, 1),'1'),'%x%v%w')";
        }
        else{
            $date_str = "o.$user_date";
        }
        if($dates['start_date']){
            $where .= " AND $order_date >= DATE('".$dates['start_date']."')";
        }
        if($dates['end_date']){
            $where .= " AND $order_date < DATE('".($dates['end_date'])."') + INTERVAL 1 day";
        }
        if(count($states)>0){
            $where .= " AND o.state_id IN ('".implode("', '", array_keys($states))."')";
        }
        $plugin_model = new shopPluginModel();
        if(count($params['shipping'])>0){
            $tmp_where = "false";
            if(isset($params['shipping']['none'])){
                unset($params['shipping']['none']);
                $tmp_where .= " OR op1.value IS NULL";
            }
            if(isset($params['shipping']['deleted'])){
                unset($params['shipping']['deleted']);
                $db_shipping = $plugin_model->listPlugins(shopPluginModel::TYPE_SHIPPING, array());
                $tmp_where .= " OR op1.value NOT IN ('".implode("', '", array_keys($db_shipping))."')";
            }
            $tmp_where .= " OR op1.value IN ('".implode("', '", array_keys($params['shipping']))."')";
            $where .= " AND ( $tmp_where )";
        }
        if(count($params['payment'])>0){
            $tmp_where = "false";
            if(isset($params['payment']['none'])){
                unset($params['payment']['none']);
                $tmp_where .= " OR op2.value IS NULL";
            }
            if(isset($params['payment']['deleted'])){
                unset($params['payment']['deleted']);
                $db_payment = $plugin_model->listPlugins(shopPluginModel::TYPE_PAYMENT, array());
                $tmp_where .= " OR op2.value NOT IN ('".implode("', '", array_keys($db_payment))."')";
            }
            $tmp_where .= " OR op2.value IN ('".implode("', '", array_keys($params['payment']))."')";
            $where .= " AND ( $tmp_where )";
        }

        $sql = "SELECT o.id
                FROM shop_order o
                LEFT JOIN shop_order_params op1 on o.id = op1.order_id and op1.name = 'shipping_id'
                LEFT JOIN shop_order_params op2 on o.id = op2.order_id and op2.name = 'payment_id'
                WHERE $where";
        $ids = array_keys($this->query($sql)->fetchAll('id'));
        $ids[] = 0;
        $where = "o.id IN (".implode(', ', $ids).")";

        $sql = "SELECT
                    DATE($date_str) AS order_date,
                    SUM(o.total*o.rate) AS total,
                    SUM(o.shipping*o.rate) AS shipping,
                    SUM(o.discount*o.rate) AS discount,
                    SUM(o.tax*o.rate) AS tax,
                    COUNT(o.id) AS `count`
                FROM shop_order o
                LEFT JOIN shop_order_params op1 on o.id = op1.order_id and op1.name = 'shipping_id'
                LEFT JOIN shop_order_params op2 on o.id = op2.order_id and op2.name = 'payment_id'
                WHERE $where
                GROUP BY order_date";
        $result = $this->query($sql)->fetchAll('order_date');

        //считаем закупку
        $sql = "SELECT
                    DATE($date_str) AS order_date,
                    SUM(IF(oi.purchase_price > 0, oi.purchase_price*o.rate, ps.purchase_price*pcur.rate)*oi.quantity) AS purchase,
                    COUNT(oi.id) AS items_count
                    FROM shop_order o
                    JOIN shop_order_items AS oi ON oi.order_id=o.id
                    JOIN shop_product AS p ON oi.product_id=p.id
                    JOIN shop_product_skus AS ps ON oi.sku_id=ps.id
                    JOIN shop_currency AS pcur ON pcur.code=p.currency
                    WHERE oi.type='product'
                    AND $where
                    GROUP BY order_date";
        $result2 = $this->query($sql)->fetchAll('order_date');
        foreach($result as $date=>&$row){
            $row['items_count'] = 0;
            if(isset($result2[$date]['items_count'])){
                $row['items_count'] = $result2[$date]['items_count'];
            }
            $row['purchase'] = 0;
            if(isset($result2[$date]['purchase'])){
                $row['purchase'] = $result2[$date]['purchase'];
            }
            $this->updateRow($row);
        }
        return $result;
    }

    public function getStatTotal($stat, $days_count = 1){
        $result = array();
        if(count($stat)>0){
            foreach($stat as $row){
                foreach($row as $k => $element){
                    if(!isset($result[$k]))$result[$k] = array('total'=>0);
                    $result[$k]['total'] += $element;
                }
            }
        }
        foreach($result as $key=>$data){
            $result[$key]['average'] = round($data['total']/$days_count*100)/100;
        }
        if(isset($result['count']['total'])&&($result['count']['total']>0)){
            $result['average_bill'] = array(
                'total' => $result['total']['total']/$result['count']['total'],
                'average' => $result['total']['total']/$result['count']['total']
            );
        }
        else{
            $result['average_bill'] = array(
                'total' => null,
                'average' => 0
            );
        }
        return $result;
    }

    private function updateRow(&$row){
        $row['profit'] = $row['total'] - $row['purchase'] - $row['shipping'] - $row['tax'];
        $row['average_bill'] = $row['total']/$row['count'];
    }
}
