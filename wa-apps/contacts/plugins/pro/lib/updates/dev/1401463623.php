<?php

$model = new waModel();

$model->exec("CREATE TABLE IF NOT EXISTS contacts_event (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255),
  `location` varchar(255),
  `start_datetime` datetime,
  `end_datetime` datetime,
  `repeat` enum('day','week','month','year'),
  `description` text,
  `contact_id` int(11) NOT NULL DEFAULT 0,
  `create_datetime` datetime, PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

$model->exec("CREATE TABLE IF NOT EXISTS contacts_event_contacts (
  `event_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL, 
  PRIMARY KEY (`event_id`, `contact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");