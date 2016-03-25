<?php

/**
 * @author  
 * @link 
 */
return array(
    'name' => 'Гдепосылка',
    'description' => 'Получение статусов почтовых отправлений',    
    'version' => '1.0',
    'frontend' => true,    
    'handlers' => array(
        'order_action.create' => 'orderActionCreate',
    ),
);
//EOF
