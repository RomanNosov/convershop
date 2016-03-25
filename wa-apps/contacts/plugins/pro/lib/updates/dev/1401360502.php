<?php
$model = new waModel();

$model->exec("CREATE TABLE IF NOT EXISTS contacts_form (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  create_datetime datetime NOT NULL,
  contact_id int(11) NOT NULL,
  locale varchar(5) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

$model->exec("CREATE TABLE IF NOT EXISTS contacts_form_params (
  form_id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  value text NOT NULL,
  PRIMARY KEY (form_id,name)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");