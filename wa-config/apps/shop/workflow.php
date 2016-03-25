<?php
return array (
  'states' => 
  array (
    'dostavka-zhdet-k' => 
    array (
      'name' => 'Доставка ждет клиента',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss flag-red',
      ),
      'available_actions' => 
      array (
        0 => 'klient-proinformirovan',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'new' => 
    array (
      'name' => 'Новый',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'pay',
        2 => 'edit',
        3 => 'delete',
        4 => 'complete',
        5 => 'changetocustomstatus',
        6 => 'message',
        7 => 'net-v-nalichii',
        8 => 'ne-dozvonilis',
        9 => 'zakaz-otmenen',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'novye-moskva' => 
    array (
      'name' => 'Новый Москва',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'edit',
        1 => 'complete',
        2 => 'sendcustomermessage',
        3 => 'changetocustomstatus',
        4 => 'message',
        5 => 'net-v-nalichii',
        6 => 'ne-dozvonilis',
        7 => 'zakaz-otmenen',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'samovyvoz-moskva' => 
    array (
      'name' => 'Самовывоз Москва',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'edit',
        1 => 'complete',
        2 => 'sendcustomermessage',
        3 => 'changetocustomstatus',
        4 => 'message',
        5 => 'net-v-nalichii',
        6 => 'ne-dozvonilis',
        7 => 'zakaz-otmenen',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'processing' => 
    array (
      'name' => 'Обработка Axiomus',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'pay',
        2 => 'ship',
        3 => 'edit',
        4 => 'delete',
        5 => 'complete',
        6 => 'comment',
        7 => 'sendcustomermessage',
        8 => 'changetocustomstatus',
        9 => 'message',
        10 => 'ne-dozvonilis',
        11 => 'otmenen-aksiomus',
        12 => 'polnyy-otkaz-aksiomus',
        13 => 'chastichnyy-otkaz-aksiomus',
      ),
    ),
    'ne-dozvonilis' => 
    array (
      'name' => 'Не дозвонились',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#080000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'delete',
        1 => 'restore',
        2 => 'complete',
        3 => 'net-v-nalichii',
        4 => 'ne-dozvonilis',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'paid' => 
    array (
      'name' => 'Оплачен с карты',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'ship',
        1 => 'refund',
        2 => 'complete',
        3 => 'comment',
        4 => 'sendcustomermessage',
        5 => 'changetocustomstatus',
        6 => 'message',
        7 => 'net-v-nalichii',
        8 => 'v-aksiomus',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'not-available' => 
    array (
      'name' => 'Нет в наличии',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'pay',
        2 => 'ship',
        3 => 'refund',
        4 => 'edit',
        5 => 'delete',
        6 => 'restore',
        7 => 'complete',
        8 => 'comment',
        9 => 'sendcustomermessage',
        10 => 'changetocustomstatus',
        11 => 'message',
        12 => 'net-v-nalichii',
        13 => 'ne-dozvonilis',
        14 => 'zakaz-otmenen',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'otmenit-zakaz' => 
    array (
      'name' => 'Заказ отменен',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'restore',
        1 => 'zakaz-otmenen',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'custom' => 
    array (
      'name' => 'Произвольный',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'pay',
        2 => 'ship',
        3 => 'refund',
        4 => 'edit',
        5 => 'delete',
        6 => 'complete',
        7 => 'comment',
        8 => 'sendcustomermessage',
        9 => 'changetocustomstatus',
        10 => 'message',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'deleted' => 
    array (
      'name' => 'Удален',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#aaaaaa',
        ),
        'icon' => 'icon16 ss trash',
      ),
      'available_actions' => 
      array (
        0 => 'restore',
        1 => 'sendcustomermessage',
        2 => 'changetocustomstatus',
        3 => 'message',
        4 => 'net-v-nalichii',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'shipped' => 
    array (
      'name' => 'Отправлен Аксиомус',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'refund',
        1 => 'delete',
        2 => 'complete',
        3 => 'comment',
        4 => 'sendcustomermessage',
        5 => 'changetocustomstatus',
        6 => 'message',
        7 => 'ne-dozvonilis',
        8 => 'zakaz-otmenen',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'completed' => 
    array (
      'name' => 'Выполнен Аксиомус',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'refund',
        1 => 'delete',
        2 => 'complete',
        3 => 'comment',
        4 => 'sendcustomermessage',
        5 => 'changetocustomstatus',
        6 => 'message',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'partial_revert' => 
    array (
      'name' => 'Частичный отказ Аксиомус',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'refund',
        1 => 'comment',
        2 => 'sendcustomermessage',
        3 => 'changetocustomstatus',
        4 => 'message',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'refunded' => 
    array (
      'name' => 'Отменен Аксиомус',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss refunded',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'refund',
        2 => 'restore',
        3 => 'comment',
        4 => 'sendcustomermessage',
        5 => 'changetocustomstatus',
        6 => 'message',
        7 => 'net-v-nalichii',
        8 => 'ne-dozvonilis',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'full_revert' => 
    array (
      'name' => 'Полный отказ Аксиомус',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss refunded',
      ),
      'available_actions' => 
      array (
        0 => 'refund',
        1 => 'comment',
        2 => 'sendcustomermessage',
        3 => 'changetocustomstatus',
        4 => 'message',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'error_del' => 
    array (
      'name' => 'Ошибка отправки в Аксиомус',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#fb2c23',
        ),
        'icon' => 'icon16 ss refunded',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'pay',
        2 => 'ship',
        3 => 'refund',
        4 => 'edit',
        5 => 'delete',
        6 => 'restore',
        7 => 'complete',
        8 => 'comment',
        9 => 'sendcustomermessage',
        10 => 'changetocustomstatus',
        11 => 'message',
        12 => 'net-v-nalichii',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'pozvonili-klient' => 
    array (
      'name' => 'Позвонили клиенту',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#ccc',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'raznoe' => 
    array (
      'name' => 'Разное',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#ccc',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'refund',
      ),
      'classname' => 'shopWorkflowState',
    ),
    'vozvrat' => 
    array (
      'name' => 'Возврат',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#ccc',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
      ),
      'classname' => 'shopWorkflowState',
    ),
    'ozhidaet-oplaty-' => 
    array (
      'name' => 'Ожидает оплаты с карты',
      'options' => 
      array (
        'style' => 
        array (
          'color' => '#000000',
        ),
        'icon' => 'icon16 ss new',
      ),
      'available_actions' => 
      array (
        0 => 'process',
        1 => 'pay',
        2 => 'v-aksiomus',
      ),
      'classname' => 'shopWorkflowState',
    ),
  ),
  'actions' => 
  array (
    'create' => 
    array (
      'classname' => 'shopWorkflowCreateAction',
      'name' => 'Создать',
      'options' => 
      array (
        'log_record' => 'Заказ оформлен',
      ),
      'state' => 'new',
    ),
    'process' => 
    array (
      'classname' => 'shopWorkflowProcessAction',
      'name' => 'В обработку',
      'options' => 
      array (
        'log_record' => 'Заказ подтвержден и принят в обработку',
        'button_class' => 'green',
      ),
      'state' => 'processing',
    ),
    'pay' => 
    array (
      'classname' => 'shopWorkflowPayAction',
      'name' => 'Оплачен',
      'options' => 
      array (
        'log_record' => 'Заказ оплачен',
        'button_class' => 'yellow',
      ),
      'state' => 'paid',
    ),
    'ship' => 
    array (
      'classname' => 'shopWorkflowShipAction',
      'name' => 'Отправлен',
      'options' => 
      array (
        'log_record' => 'Заказ отправлен',
        'button_class' => 'blue',
      ),
      'state' => 'shipped',
    ),
    'refund' => 
    array (
      'classname' => 'shopWorkflowRefundAction',
      'name' => 'Возврат',
      'options' => 
      array (
        'log_record' => 'Возврат',
        'button_class' => 'red',
      ),
      'state' => 'refunded',
    ),
    'edit' => 
    array (
      'classname' => 'shopWorkflowEditAction',
      'name' => 'Редактировать заказ',
      'options' => 
      array (
        'position' => 'top',
        'icon' => 'edit',
        'log_record' => 'Заказ отредактирован',
      ),
    ),
    'delete' => 
    array (
      'classname' => 'shopWorkflowDeleteAction',
      'name' => 'Удалить',
      'options' => 
      array (
        'icon' => 'delete',
        'log_record' => 'Заказ удален',
      ),
      'state' => 'deleted',
    ),
    'restore' => 
    array (
      'classname' => 'shopWorkflowRestoreAction',
      'name' => 'Восстановить',
      'options' => 
      array (
        'icon' => 'restore',
        'log_record' => 'Заказ восстановлен',
        'button_class' => 'green',
      ),
    ),
    'complete' => 
    array (
      'classname' => 'shopWorkflowCompleteAction',
      'name' => 'Выполнен',
      'options' => 
      array (
        'log_record' => 'Заказ выполнен',
        'button_class' => 'purple',
      ),
      'state' => 'completed',
    ),
    'comment' => 
    array (
      'classname' => 'shopWorkflowCommentAction',
      'name' => 'Добавить комментарий',
      'options' => 
      array (
        'position' => 'bottom',
        'icon' => 'add',
        'button_class' => 'inline-link',
        'log_record' => 'Добавлен комментарий к заказу',
      ),
    ),
    'callback' => 
    array (
      'classname' => 'shopWorkflowCallbackAction',
      'name' => 'Ответ платежной системы (callback)',
      'options' => 
      array (
        'log_record' => 'Ответ платежной системы (callback)',
      ),
    ),
    'sendcustomermessage' => 
    array (
      'classname' => 'shopWorkflowSendCustomerMessageAction',
      'name' => 'Отправка сообщения клиенту',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'dddddd',
      ),
    ),
    'changetocustomstatus' => 
    array (
      'classname' => 'shopWorkflowChangeToCustomStatusAction',
      'name' => 'Произвольный статус',
      'state' => 'custom',
    ),
    'message' => 
    array (
      'classname' => 'shopWorkflowMessageAction',
      'name' => 'Написать клиенту',
      'options' => 
      array (
        'position' => 'top',
        'icon' => 'email',
        'log_record' => 'Сообщение отправлено',
      ),
    ),
    'net-v-nalichii' => 
    array (
      'name' => 'Нет в наличии',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'd7562a',
      ),
      'state' => 'not-available',
      'classname' => 'shopWorkflowAction',
      'id' => 'net-v-nalichii',
    ),
    'ne-dozvonilis' => 
    array (
      'name' => 'Не дозвонились',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'ddd',
      ),
      'classname' => 'shopWorkflowAction',
      'id' => 'ne-dozvonilis',
      'state' => 'ne-dozvonilis',
    ),
    'zakaz-otmenen' => 
    array (
      'name' => 'Заказ отменен',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'ddd',
      ),
      'classname' => 'shopWorkflowAction',
      'id' => 'zakaz-otmenen',
      'state' => 'otmenit-zakaz',
    ),
    'v-aksiomus' => 
    array (
      'name' => 'В Аксиомус',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'ddd',
      ),
      'state' => 'processing',
      'classname' => 'shopWorkflowAction',
      'id' => 'v-aksiomus',
    ),
    'otmenen-aksiomus' => 
    array (
      'name' => 'Отменен Аксиомус',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'ddd',
      ),
      'state' => 'refunded',
      'classname' => 'shopWorkflowAction',
      'id' => 'otmenen-aksiomus',
    ),
    'polnyy-otkaz-aksiomus' => 
    array (
      'name' => 'Полный отказ Аксиомус',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'ddd',
      ),
      'state' => 'full_revert',
      'classname' => 'shopWorkflowAction',
      'id' => 'polnyy-otkaz-aksiomus',
    ),
    'chastichnyy-otkaz-aksiomus' => 
    array (
      'name' => 'Частичный отказ Аксиомус',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'ddd',
      ),
      'state' => 'partial_revert',
      'classname' => 'shopWorkflowAction',
      'id' => 'chastichnyy-otkaz-aksiomus',
    ),
    'klient-proinformirovan' => 
    array (
      'name' => 'Клиент проинформирован',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'efcf00',
      ),
      'state' => 'pozvonili-klient',
      'classname' => 'shopWorkflowAction',
      'id' => 'klient-proinformirovan',
    ),
    'novyy' => 
    array (
      'name' => 'Новый',
      'options' => 
      array (
        'position' => '',
        'button_class' => '',
        'border_color' => 'ddd',
      ),
      'state' => 'new',
      'classname' => 'shopWorkflowAction',
      'id' => 'novyy',
    ),
  ),
);
