<?php

return array(
    'name' => 'Order Status',
    'version' => '1.0',
    
    // соответствие событие => обработчик (название метода в классе плагина)
    'handlers' => array(
      'backend_order' => 'getError',
    ),    
    'description' => 'ОПИСАНИЕ ПЛАГИНА Ax'
);