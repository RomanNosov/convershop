<?php

die('asd');

class shopOrderSendCheckMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $order_id = $this->post('id', true);
        $sum = $this->post('sum', true);
        die(var_dump($sum));

        $order_model = new shopOrderModel();
        if (!$order_model->getById($order_id)) {
            throw new waAPIException('invalid_param', 'Order not found', 404);
        }

    }
}