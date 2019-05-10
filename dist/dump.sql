ALTER TABLE `page_regular` CHANGE `created` `created_at` TIMESTAMP DEFAULT current_timestamp;
ALTER TABLE `page_regular` CHANGE `updated` `updated_at` TIMESTAMP null ON UPDATE CURRENT_TIMESTAMP;

/** 02.10.2017 */

alter table `page_regular` add column `rating` smallint unsigned not null default 0 after `description`;

/** 05.10.2017 */

alter table `page_regular` add column `is_menu` tinyint(1) unsigned not null default 0 after `description`;
alter table `page_regular` add column `menu_title` varchar(128) null after `meta_keywords`;

/** 26.11.2017 */

CREATE TABLE `banner` (
  `banner_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `image` char(32) NOT NULL,
  `type` char(32) NOT NULL,
  `title` varchar(512) NOT NULL,
  `href` varchar(512) NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`banner_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

# [mysqld] sql_mode = "NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,TRADITIONAL"

/** 21.12.2017 */

CREATE TABLE `subscription` (
  `subscription_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `filter` text NOT NULL,
  `code` char(32) NOT NULL,
  `is_confirmed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`subscription_id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `ix_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/** 01.02.2018 */

CREATE TABLE `page_custom` (
  `page_custom_id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `uri` varchar(1024) NOT NULL,
  `uri_hash` char(32) NOT NULL,
  `name` varchar(1024) DEFAULT NULL,
  `meta_title` varchar(1024) DEFAULT NULL,
  `meta_description` varchar(4096) DEFAULT NULL,
  `meta_keywords` varchar(2048) DEFAULT NULL,
  `title` varchar(4096) DEFAULT NULL,
  `body` mediumtext,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`page_custom_id`),
  UNIQUE KEY `uri_hash` (`uri_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/** 16.02.2018 */

alter table `banner` change `href` `link` varchar(512) NOT NULL;

/** 24.02.2018 */

alter table `banner` change `title` `caption` varchar(512) NULL,
    change `link` `link` varchar(512) null;

/** 15.03.2018 */

rename table quote to contact;
alter table contact change quote_id contact_id int(5) unsigned not null auto_increment;

rename table subscription to subscribe;
alter table subscribe change subscription_id subscribe_id smallint(5) unsigned not null auto_increment;

/** 22.03.2018 */

alter table page_custom change title h1  varchar(4096) DEFAULT NULL;
alter table page_regular change title h1  varchar(4096) DEFAULT NULL;

/** 25.09.2018 */

alter table `page_custom` change `uri` `uri` varchar(512) NOT NULL;
alter table `page_custom` change `name` `name` varchar(512) NOT NULL;
alter table `page_custom` change `meta_title` `meta_title` varchar(2048) DEFAULT NULL;
alter table `page_custom` change `h1` `h1` varchar(2048) DEFAULT NULL;
alter table `page_custom` add column `params` varchar(2048) DEFAULT NULL after `body`;
alter table `page_custom` add column `params_hash` char(32) DEFAULT NULL after `params`;
alter table `page_custom` drop key `uri_hash`;
alter table `page_custom` add unique key `uk_uri`(`uri_hash`);

/** 09.10.2018 */

SET tx_isolation = 'READ-COMMITTED';
SET GLOBAL tx_isolation = 'READ-COMMITTED';