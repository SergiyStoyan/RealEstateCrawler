
CREATE TABLE IF NOT EXISTS `alert_clients` (
  `id` int AUTO_INCREMENT,
  `create_time` datetime NOT NULL,
  `name` varchar(32) NOT NULL,
  `emails` varchar(512) NOT NULL,
  `type` enum('gold','platinum') NOT NULL, 
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
CREATE TABLE IF NOT EXISTS `alert_filters` (
  `id` int AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `filter` varchar(512) NOT NULL,
  `state` enum ('active','archived') NOT NULL,
  `create_time` datetime NOT NULL,
  `last_start_time` datetime,
  `last_end_time` datetime,
  `comment` varchar(512),
  PRIMARY KEY (`id`),
  UNIQUE KEY `filter_key` (`client_id`, `filter`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    
CREATE TABLE IF NOT EXISTS `alert_notifications` (
  `try_count` tinyint(3) unsigned NOT NULL,
  `filter_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `_state` enum('new','sent','omitted','error') NOT NULL,
  `found_time` datetime NOT NULL,
  `sent_time` datetime DEFAULT NULL,
  `product_id` varchar(256) NOT NULL,
  `product_change_time` datetime NOT NULL,
  `product_town` varchar(32) NOT NULL,
  `product_status` varchar(256) NOT NULL,
  `product_features` varchar(256) NOT NULL,
  `product_postcode` varchar(10) NOT NULL,
  `product_agent` varchar(256) NOT NULL,
  `product_url` varchar(256) NOT NULL,
  `product_image_path` varchar(256) NOT NULL,
  `product_description` varchar(256) NOT NULL,
  `product_price` int(11) NOT NULL,
  `matched_types` varchar(256) NOT NULL,
  PRIMARY KEY (`client_id`,`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;