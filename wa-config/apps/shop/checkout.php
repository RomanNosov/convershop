<?php
return array (
  'shipping' => 
  array (
    'name' => 'Доставка',
    'prompt_type' => '1',
  ),
  'payment' => true,
  'contactinfo' => 
  array (
    'name' => 'Контактная информация',
    'fields' => 
    array (
      'email' => 
      array (
        'localized_names' => 'Email',
        'required' => '1',
      ),
      'phone' => 
      array (
        'localized_names' => 'Телефон',
        'required' => '1',
      ),
      'address' => 
      array (
        'localized_names' => 'Адрес',
        'fields' => 
        array (
          'region' => 
          array (
            'localized_names' => 'Регион',
            'required' => '1',
          ),
          'kod-regiona' => 
          array (
            'required' => '1',
          ),
          'city' => 
          array (
            'localized_names' => 'Город',
            'required' => '1',
          ),
          'kod-goroda' => 
          array (
            'required' => '1',
          ),
          'punkt-vydachi' => 
          array (
            'required' => '1',
          ),
          'kod-punkta-vyda' => 
          array (
            'required' => '1',
          ),
          'zip' => 
          array (
            'localized_names' => 'Индекс',
            'required' => '1',
          ),
          'street' => 
          array (
            'localized_names' => 'Улица',
            'required' => '1',
          ),
          'dom' => 
          array (
            'required' => '1',
          ),
          'kvartira' => 
          array (
            'required' => '1',
          ),
          'kommentariy' => 
          array (
            'required' => '',
          ),
        ),
      ),
    ),
  ),
);
