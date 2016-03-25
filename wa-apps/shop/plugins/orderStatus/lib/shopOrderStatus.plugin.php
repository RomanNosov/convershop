<?php

/**
 * @property string $test_mode
 * @property string $api_key
 * @property string $zip
 * @property string $length
 * @property string $height
 * @property string $width
 */
class shopOrderStatusPlugin extends shopPlugin {

    public function getError($order) {

        $error_text = $order['params']['axiomus.error'];
        $error_code = $order['params']['axiomus.code'];
        $data = array();
        if ($error_text != null) {
            $data = array(
                'info_section' => '<h2 style="color:red" >Ответ от axiomus:' . $error_text . ' ---Код ответа:' . $error_code . '</h2><br/>', //фрагмент HTML-кода
            );
        }
        return $data;
    }

}
