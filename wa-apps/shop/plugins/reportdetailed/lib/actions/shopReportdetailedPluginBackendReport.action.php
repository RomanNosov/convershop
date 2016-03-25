<?php

class shopReportdetailedPluginBackendReportAction extends waViewAction
{
    public function execute()
    {
        $dates = $this->getDates();
        $default_currency = wa()->getConfig()->getCurrency();
        $model = new shopReportdetailedpluginModel();
        $workflow = new shopWorkflow();

        $user_params = $this->getUserParams();

        $stat = $model->getStat($dates, $user_params);

        $result_data = $this->getData($stat, $user_params['columns'], $dates['group']);

        $stat_total = $model->getStatTotal($stat, $result_data['count']);

        $this->view->assign('currency', $default_currency);
        $this->view->assign('columns', $this->getColumns());
        $this->view->assign('states', $workflow->getAllStates());
        $this->view->assign('dates', $dates);
        $plugin_model = new shopPluginModel();
        $db_shipping = $plugin_model->listPlugins(shopPluginModel::TYPE_SHIPPING, array());
        $db_payment = $plugin_model->listPlugins(shopPluginModel::TYPE_PAYMENT, array());
        $db_shipping['deleted'] = array('name'=>_wp('Deleted'));
        $db_shipping['none'] = array('name'=>_wp('None'));
        $db_payment['deleted'] = array('name'=>_wp('Deleted'));
        $db_payment['none'] = array('name'=>_wp('None'));
        $this->view->assign('shipping', $db_shipping);
        $this->view->assign('payment', $db_payment);

        $this->view->assign('user_columns', $user_params['columns']);
        $this->view->assign('user_states', $user_params['states']);
        $this->view->assign('user_date', $user_params['dates']);
        $this->view->assign('user_shipping', $user_params['shipping']);
        $this->view->assign('user_payment', $user_params['payment']);

        $this->view->assign('stat', $result_data['table_data']);
        $this->view->assign('stat_total', $stat_total);
        $this->view->assign('profit_data', $result_data['chart_data']);
        $this->view->assign('scale_interval', $result_data['interval']);
        $this->view->assign('min', $result_data['min']);
        $this->view->assign('max', $result_data['max']);
        //$this->view->assign('ticks', $result_data['ticks']);
    }

    private function getDates(){
        $params = shopReportsSalesAction::getTimeframeParams();
        if(!isset($params[3])){
            $params[3] = array();
        }
        $dates = array_combine(array('start_date', 'end_date', 'group', 'details'), $params);
        if(waRequest::post('set_params')>0){
            $dates = array(
                'start_date' => waRequest::post('start_date', '', 'string'),
                'end_date' => waRequest::post('end_date', '', 'string'),
                'group' => waRequest::post('group', 'days', 'string'),
                'details' => array(),
            );
        }
        return $dates;
    }

    private function getUserParams(){
        $setting_model = new waAppSettingsModel();
        //применяем настройки статусов
        $workflow = new shopWorkflow();
        $states = $workflow->getAllStates();
        $user_states = json_decode($setting_model->get(array('shop', 'reportdetailed'), 'states'), true);
        $show_states = array();
        if($user_states&&count($user_states)>0){
            foreach($states as $key=>$state){
                if(isset($user_states[$key])){
                    $show_states[$key] = $state;
                }
            }
        }
        else{
            $show_states =  $states;
        }
        //применяем настройки столбцов
        $user_columns = json_decode($setting_model->get(array('shop', 'reportdetailed'), 'columns'), true);

        $columns = $this->getColumns();

        $show_columns = array();
        if($user_columns&&count($user_columns)>0){
            foreach($columns as $key=>$column){
                if(isset($user_columns[$key])||'order_date' == $key){
                    $show_columns[$key] = $column;
                }
            }
        }
        else{
            $show_columns =  $columns;
        }
        $user_date = $setting_model->get(array('shop', 'reportdetailed'), 'order_date');
        if($user_date!='paid_date'){
            $user_date = 'create_datetime';
        }
        $plugin_model = new shopPluginModel();
        $db_shipping = $plugin_model->listPlugins(shopPluginModel::TYPE_SHIPPING, array());
        $db_payment = $plugin_model->listPlugins(shopPluginModel::TYPE_PAYMENT, array());
        $db_shipping['deleted'] = array('name'=>_wp('Deleted'));
        $db_shipping['none'] = array('name'=>_wp('None'));
        $db_payment['deleted'] = array('name'=>_wp('Deleted'));
        $db_payment['none'] = array('name'=>_wp('None'));
        $user_shipping = json_decode($setting_model->get(array('shop', 'reportdetailed'), 'shipping'), true);
        $user_payment = json_decode($setting_model->get(array('shop', 'reportdetailed'), 'payment'), true);
        $show_shipping = array();
        if($user_shipping&&count($user_shipping)>0){
            foreach($user_shipping as $id=>$row){
                if(isset($db_shipping[$id])){
                    $show_shipping[$id] = $id;
                }
            }
        }
        else{
            $show_shipping =  $db_shipping;
        }
        $show_payment = array();
        if($user_payment&&count($user_payment)>0){
            foreach($user_payment as $id=>$row){
                if(isset($db_payment[$id])){
                    $show_payment[$id] = $id;
                }
            }
        }
        else{
            $show_payment =  $db_payment;
        }
        return array(
            'states'=>$show_states,
            'columns'=>$show_columns,
            'dates'=>$user_date,
            'shipping'=>$show_shipping,
            'payment'=>$show_payment,
        );
    }

    private function getColumns(){
        //["#3b7dc0", "#129d0e", "#a38717", "#ac3562", "#1ba17a", "#87469f", "#6b6b6b", "#686190", "#b2b000", "#00b1ab", "#76b300"],
        $columns = array();
        $columns['order_date'] = array('title'=>_wp('Date'), 'type'=>'date');
        $columns['count'] = array('title'=>_wp('Orders count'), 'total' => true, 'type'=>'count', 'color' => '#686190');
        $columns['items_count'] = array('title'=>_wp('Products count'), 'total' => true, 'type'=>'count', 'color' => '#b2b000');
        $columns['total'] = array('title'=>_wp('Sales'),'type'=>'price', 'total' => true, 'color' => '#3b7dc0');
        $columns['average_bill'] = array('title'=>_wp('Average bill'),'type'=>'price', 'total' => true, 'color' => '#6b6b6b');
        $columns['profit'] = array('title'=>_wp('Profit'),'type'=>'price', 'total' => true, 'color' => '#129d0e');
        $columns['purchase'] = array('title'=>_wp('Purchase'),'type'=>'price', 'total' => true, 'color' => '#a38717');
        $columns['shipping'] = array('title'=>_wp('Shipping'),'type'=>'price', 'total' => true, 'color' => '#ac3562');
        $columns['discount'] = array('title'=>_wp('Discount'),'type'=>'price', 'total' => true, 'color' => '#1ba17a');
        $columns['tax'] = array('title'=>_wp('Tax'),'type'=>'price', 'total' => true, 'color' => '#87469f');
        return $columns;
    }

    private function getData($stat, $show_columns, $group_by){
        $table_data = array();
        $dates = $this->getDates();
        $columns = $this->getColumns();
        if($dates['start_date']){
            $time_start = strtotime($dates['start_date']);
        }
        else{
            if(count($stat)>0){
                reset($stat);
                $time_start = strtotime(key($stat));
            }
            else{
                $time_start = time();
            }
        }
        if($dates['end_date']){
            $time_end = strtotime($dates['end_date']);
        }
        else{
            $time_end = time();
        }
        if($group_by=='months'){
            $count = abs((date('Y', $time_end) - date('Y', $time_start))*12 + (date('m', $time_end) - date('m', $time_start)))+1;
        }
        elseif($group_by=='weeks'){
            $monday_start = strtotime('last sunday', $time_start);
            $monday_end = strtotime('last sunday', $time_end);
            $count = round($monday_end - $monday_start)/(60*60*24*7)+1;
        }
        else{
            $count = floor(($time_end - $time_start)/(60*60*24))+1;
        }
        $profit_data = array();
        $column_counter = 0;
        $graph_start = '';
        $graph_end = '';
        $ticks = array();
        for($i = 0; $i<$count; $i++){
            if($group_by=='months'){
                $date = strtotime("+$i MONTH", $time_start);
                $date = date("Y-m-01", $date);
            }
            elseif($group_by=='weeks'){
                $date = strtotime('monday this week', $time_start);
                $date = strtotime("+$i WEEK", $date);
                $date = date("Y-m-d", $date);
            }
            else{
                $date = strtotime("+$i DAY", $time_start);
                $date = date("Y-m-d", $date);
            }
            if($graph_start=='')$graph_start = $date;
            $graph_end = $date;
            $table_data[$date] = array(
                'order_date'=>$date
            );
            if(isset($stat[$date])){
                $table_data[$date] = $stat[$date];
            }
            foreach($show_columns as $c_key=>$c){
                if(isset($c['type'])&&(($c['type']=='price')||($c['type']=='count'))){
                    $line = array();
                    $line[] = $date;
                    $ticks[$date] = 1;
                    if(isset($stat[$date])){
                        $line[] = (float)$stat[$date][$c_key];
                    }
                    else{
                        $line[] = 0;
                    }
                    if(!isset($profit_data[$c_key])){
                        $profit_data[$c_key] = array(
                            'key'=>'graph_'.$column_counter,
                            'params'=>$columns[$c_key],
                            'values'=>array()
                        );
                        $column_counter++;
                    }
                    $profit_data[$c_key]['values'][] = $line;
                }
            }
        }

        $scale = ceil($count/31);
        $interval = '';
        $interval .= $scale;
        if ($dates['group'] == 'months'){
            $interval .= ' months';
        }
        elseif ($dates['group'] == 'weeks'){
            $interval .= ' weeks';
        }
        else{
            $interval .= ' days';
        }
        return array(
            'count' => $count,
            'chart_data' => $profit_data,
            'table_data' => $table_data,
            'interval' => $interval,
            'min' => $graph_start,
            'max' => $graph_end,
            //'ticks' => array_keys($ticks),
        );
    }

}
