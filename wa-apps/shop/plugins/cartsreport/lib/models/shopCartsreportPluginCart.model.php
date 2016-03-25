<?php


class shopCartsreportPluginCartModel extends waModel
{
    protected $id    = 'code';
    protected $table = 'shop_cartsreport_plugin_cart';

    public function getReportData($where, $offset, $on_page, $all = false)
    {
        $sql = 'SELECT c.* FROM '.$this->getTableName().' c ';
        $sql .= 'WHERE '.$where.' ';
        $sql .= 'ORDER BY edit_datetime DESC LIMIT '.$offset.','.$on_page;

        $data = $this->query($sql)->fetchAll('code');

        if(!empty($data)) {
            $items_helper = new shopCartsreportPluginCartProducts();

            foreach($data as &$d) {

                $d['items'] = $items_helper->getByCode($d['code']);
            }
        }

        $res = array();
        $res['carts'] = $data;
        $res['total'] = $data ? $this->query('SELECT COUNT(*) FROM '.$this->getTableName().' WHERE '.$where)->fetchField() : 0;
        return $res;
    }

}