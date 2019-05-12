CREATE TABLE `page_regular` (
  `page_regular_id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `key` varchar(64) NOT NULL,
  `meta_title` varchar(1024) DEFAULT NULL,
  `meta_description` varchar(4096) DEFAULT NULL,
  `meta_keywords` varchar(2048) DEFAULT NULL,
  `menu_title` varchar(128) DEFAULT NULL,
  `h1` varchar(4096) DEFAULT NULL,
  `description` varchar(4096) DEFAULT NULL,
  `is_menu` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `rating` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`page_regular_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

insert into page_regular(`key`,meta_title,meta_description,meta_keywords,menu_title,h1,description,is_menu,rating,created_at) values('index','{site} - еще один core сайт','Описание {site} сайта','{site},сайт','главная','{site} - еще один core сайт','Описание {core} сайта',1,100,NOW());
insert into page_regular(`key`,meta_title,meta_description,meta_keywords,menu_title,h1,description,is_menu,rating,created_at) values('contacts','контакты',null,null,'контакты',null,null,1,0,NOW());

CREATE TABLE `banner` (
  `banner_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `image` char(32) NOT NULL,
  `type` char(32) NOT NULL,
  `caption` varchar(512) DEFAULT NULL,
  `link` varchar(512) DEFAULT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `site_id` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`banner_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `contact` (
  `contact_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `email` varchar(256) NOT NULL,
  `body` varchar(2056) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`contact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `redirect` (
  `redirect_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `uri_from` varchar(512) NOT NULL,
  `uri_to` varchar(512) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`redirect_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `subscribe` (
  `subscribe_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `filter` text NOT NULL,
  `code` char(32) NOT NULL,
  `is_confirmed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`subscribe_id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `ix_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user` (
  `user_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(28) NOT NULL,
  `password` varchar(32) NOT NULL,
  `role` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `page_custom` (
  `page_custom_id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `uri` varchar(512) NOT NULL,
  `uri_hash` char(32) NOT NULL,
  `name` varchar(1024) DEFAULT NULL,
  `meta_title` varchar(2048) DEFAULT NULL,
  `meta_description` varchar(4096) DEFAULT NULL,
  `meta_keywords` varchar(2048) DEFAULT NULL,
  `h1` varchar(2048) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `params` varchar(2048) DEFAULT NULL,
  `params_hash` char(32) DEFAULT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`page_custom_id`),
  UNIQUE KEY `uk_uri` (`uri_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

