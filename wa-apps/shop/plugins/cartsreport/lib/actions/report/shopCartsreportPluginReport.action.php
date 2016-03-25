<?php

class shopCartsreportPluginReportAction extends waViewAction {

    public function  execute()
    {
        /**
         * @todo
         */
        $on_page = 25;

        $start = microtime(true);

        $model = new shopCartsreportPluginCartModel();
        $where = $this->getTimeQuery();

        $page = waRequest::get('page', 1, waRequest::TYPE_INT);
        if($page < 1) $page = 1;
        $offset = ($page - 1) * $on_page;

        $data = $model->getReportData($where, $offset, $on_page);
        $pages_total = ceil($data['total'] / $on_page);


        $path = $this->getConfig()->getPluginPath('carts').'/lib/config/plugin.php';
        $carts_plugin = file_exists($path);

        $this->view->assign(array(
            'data' => $data,
            'pages_total' => $pages_total,
            'carts_plugin' => $carts_plugin,
            'generated' => microtime(true)-$start,
            'lang' => substr(wa()->getLocale(), 0, 2)
        ));


    }

    private function getTimeQuery()
    {
        $days = waRequest::get('timeframe');

        if(($days == 'custom') && waRequest::get('from') && waRequest::get('to')) {
            $from = date('Y-m-d 00:00:00', waRequest::get('from'));
            $to = date('Y-m-d 23:59:59', waRequest::get('to'));
            $where = 'edit_datetime BETWEEN \''.$from. '\' AND \''.$to."'";
        } elseif((int)$days) {
            $where = 'edit_datetime > (NOW() - interval '.((int)$days).' day)';
        } else {
            $where = '1';
        }

        return $where;
    }
}