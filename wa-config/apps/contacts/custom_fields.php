<?php
return array (
  0 => 
  waContactAddressField::__set_state(array(
     'id' => 'address',
     'options' => 
    array (
      'multi' => true,
      'ext' => 
      array (
        'work' => 'work',
        'home' => 'home',
        'shipping' => 'shipping',
        'billing' => 'billing',
      ),
      'storage' => 'data',
      'fields' => 
      array (
        'region' => 
        waContactRegionField::__set_state(array(
           'rm' => NULL,
           'id' => 'region',
           'options' => 
          array (
            'storage' => 'data',
          ),
           'name' => 
          array (
            'en_US' => 'State',
          ),
           '_type' => 'waContactRegionField',
        )),
        'kod-regiona' => 
        waContactSelectField::__set_state(array(
           'validate_range' => true,
           'id' => 'kod-regiona',
           'options' => 
          array (
            'app_id' => 'shop',
            'storage' => 'data',
            'options' => 
            array (
              '!none!' => '!none!',
            ),
            'required' => '1',
          ),
           'name' => 
          array (
            'en_US' => 'Код региона',
          ),
           '_type' => 'waContactSelectField',
        )),
        'city' => 
        waContactStringField::__set_state(array(
           'id' => 'city',
           'options' => 
          array (
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
          ),
           'name' => 
          array (
            'en_US' => 'City',
          ),
           '_type' => 'waContactStringField',
        )),
        'kod-goroda' => 
        waContactSelectField::__set_state(array(
           'validate_range' => true,
           'id' => 'kod-goroda',
           'options' => 
          array (
            'app_id' => 'shop',
            'storage' => 'data',
            'options' => 
            array (
              '!none!' => '!none!',
            ),
            'required' => '1',
          ),
           'name' => 
          array (
            'en_US' => 'Код города',
          ),
           '_type' => 'waContactSelectField',
        )),
        'punkt-vydachi' => 
        waContactStringField::__set_state(array(
           'id' => 'punkt-vydachi',
           'options' => 
          array (
            'app_id' => 'shop',
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'app_id' => 'shop',
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
            'required' => '1',
          ),
           'name' => 
          array (
            'en_US' => 'Пункт выдачи',
          ),
           '_type' => 'waContactStringField',
        )),
        'kod-punkta-vyda' => 
        waContactSelectField::__set_state(array(
           'validate_range' => true,
           'id' => 'kod-punkta-vyda',
           'options' => 
          array (
            'app_id' => 'shop',
            'storage' => 'data',
            'options' => 
            array (
              '!none!' => '!none!',
            ),
            'required' => '1',
          ),
           'name' => 
          array (
            'en_US' => 'Код пункта выдачи',
          ),
           '_type' => 'waContactSelectField',
        )),
        'zip' => 
        waContactStringField::__set_state(array(
           'id' => 'zip',
           'options' => 
          array (
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
          ),
           'name' => 
          array (
            'en_US' => 'ZIP',
          ),
           '_type' => 'waContactStringField',
        )),
        'street' => 
        waContactStringField::__set_state(array(
           'id' => 'street',
           'options' => 
          array (
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
          ),
           'name' => 
          array (
            'en_US' => 'Street address',
          ),
           '_type' => 'waContactStringField',
        )),
        'dom' => 
        waContactStringField::__set_state(array(
           'id' => 'dom',
           'options' => 
          array (
            'app_id' => 'shop',
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'app_id' => 'shop',
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
            'required' => '1',
          ),
           'name' => 
          array (
            'en_US' => 'Дом',
          ),
           '_type' => 'waContactStringField',
        )),
        'kvartira' => 
        waContactStringField::__set_state(array(
           'id' => 'kvartira',
           'options' => 
          array (
            'app_id' => 'shop',
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'app_id' => 'shop',
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
            'required' => '1',
          ),
           'name' => 
          array (
            'en_US' => 'Квартира',
          ),
           '_type' => 'waContactStringField',
        )),
        'kommentariy' => 
        waContactStringField::__set_state(array(
           'id' => 'kommentariy',
           'options' => 
          array (
            'app_id' => 'shop',
            'storage' => 'waContactDataStorage',
            'input_height' => 5,
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'app_id' => 'shop',
                'storage' => 'waContactDataStorage',
                'input_height' => 5,
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
            'required' => '',
          ),
           'name' => 
          array (
            'en_US' => 'Комментарий',
          ),
           '_type' => 'waContactStringField',
        )),
        'country' => 
        waContactCountryField::__set_state(array(
           'model' => NULL,
           'validate_range' => true,
           'id' => 'country',
           'options' => 
          array (
            'defaultOption' => 'Select country',
            'storage' => 'data',
            'formats' => 
            array (
              'value' => 
              waContactCountryFormatter::__set_state(array(
                 '_type' => 'waContactCountryFormatter',
                 'options' => NULL,
              )),
            ),
          ),
           'name' => 
          array (
            'en_US' => 'Country',
          ),
           '_type' => 'waContactCountryField',
        )),
        'lng' => 
        waContactHiddenField::__set_state(array(
           'id' => 'lng',
           'options' => 
          array (
            'storage' => 'data',
          ),
           'name' => 
          array (
            'en_US' => 'Longitude',
          ),
           '_type' => 'waContactHiddenField',
        )),
        'lat' => 
        waContactHiddenField::__set_state(array(
           'id' => 'lat',
           'options' => 
          array (
            'storage' => 'data',
          ),
           'name' => 
          array (
            'en_US' => 'Latitude',
          ),
           '_type' => 'waContactHiddenField',
        )),
      ),
      'formats' => 
      array (
        'js' => 
        waContactAddressOneLineFormatter::__set_state(array(
           '_type' => 'waContactAddressOneLineFormatter',
           'options' => NULL,
        )),
        'forMap' => 
        waContactAddressForMapFormatter::__set_state(array(
           '_type' => 'waContactAddressForMapFormatter',
           'options' => NULL,
        )),
      ),
      'allow_self_edit' => false,
      'required' => 
      array (
      ),
      'unique' => false,
    ),
     'name' => 
    array (
      'en_US' => 'Address',
    ),
     '_type' => 'waContactAddressField',
  )),
);
