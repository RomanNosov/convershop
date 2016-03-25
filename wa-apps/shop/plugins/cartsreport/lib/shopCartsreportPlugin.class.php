<?php

class shopCartsreportPlugin extends shopPlugin
{


    public function  backendReports()
    {
        /**
         * @var waSmarty3View $view
         */
        $view = wa()->getView();

        $view->assign('report', $this->getSettings('report'));
        return array(
            'menu_li' => $view->fetch($this->path.'/templates/hooks/backendReports.html')
        );
    }



    public function cartDelete($item)
    {

        $cart = new shopCart();
        if(!$cart->count()) {
            $m = new shopCartsreportPluginCartModel();
            $m->deleteByField('code', $cart->getCode());
        }

        return null;
    }


    public function cartAdd($item)
    {
        $cart = new shopCart();
        $m = new shopCartsreportPluginCartModel();

        $m->insert(array(
            'code' => $cart->getCode(),
            'edit_datetime' => date('Y-m-d H:i:s'),
        ), 1);

        return null;
    }


    public function frontendCart()
    {
        $cart = new shopCart();
        $m = new shopCartsreportPluginCartModel();
        $m->insert(array(
            'code' => $cart->getCode(),
            'cart' => 1,
            'edit_datetime' => date('Y-m-d H:i:s'),
        ),1);

        return '';
    }

    public function frontendCheckout($info)
    {
        $step = ifempty($info['step'],'');

        if($step == 'success') {
            return '';
        }
        $cart = new shopCart();
        $m = new shopCartsreportPluginCartModel();
        $m->insert(array(
            'code' => $cart->getCode(),
            'checkout.'.$step => 1,
            'edit_datetime' => date('Y-m-d H:i:s'),
        ),1);

        return '';
    }



    public function orderActionCreate($order)
    {
        $cart = new shopCart();
        $code = $cart->getCode();
        $m = new shopCartsreportPluginCartModel();
        $m->deleteById($code);
    }
}
