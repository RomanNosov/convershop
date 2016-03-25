<?php

return array(
    'name' => 'Axi',
    'version' => '1.0',
    
    // соответствие событие => обработчик (название метода в классе плагина)
    'handlers' => array(
      'order_action.process' => 'sendRQtoAxiomus',
    ),
    
    // остальные параметры — необязательные
    'img' => 'img/plugin.png', // иконка (будет показываться в Инсталлере) размером 16x16
    'description' => 'ОПИСАНИЕ ПЛАГИНА Ax'
);