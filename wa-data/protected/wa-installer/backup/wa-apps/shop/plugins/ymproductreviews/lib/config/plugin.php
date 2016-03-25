<?php 

return array(
    'name' => 'Отзывы о товаре с Яндекс.Маркета',
    'description' => 'Вывод отзывов в карточке товара',
    'vendor'=> '903438',
    'version'=> '1.0.8',
    'img'=>'img/ymproductreviews.png',
    'shop_settings' => true,
    'frontend'    => true,
    'handlers' => array(
        'backend_product_edit' => 'backendProductEdit',
        'frontend_product' => 'frontendProduct',
    )
);

