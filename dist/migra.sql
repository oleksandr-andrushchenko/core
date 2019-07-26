CREATE TABLE `page` (
  `page_id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `key` varchar(64) DEFAULT NULL,
  `uri` varchar(512) NOT NULL,
  `uri_hash` char(32) NOT NULL,
  `name` varchar(512) DEFAULT NULL,
  `meta_title` varchar(4096) DEFAULT NULL,
  `meta_description` varchar(4096) DEFAULT NULL,
  `meta_keywords` varchar(4096) DEFAULT NULL,
  `menu_title` varchar(128) DEFAULT NULL,
  `h1` varchar(4096) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `description` varchar(4096) DEFAULT NULL,
  `rating` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_menu` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_suggestion` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


insert into page(`key`,meta_title,meta_description,meta_keywords,menu_title,h1,description,is_menu,rating,created_at,updated_at,uri,uri_hash) select `key`,meta_title,meta_description,meta_keywords,menu_title,h1,description,is_menu,rating,created_at,updated_at,`key`,md5(`key`) from page_regular;
update `page` set is_active = 1;
update `page` set `uri` = '/', `uri_hash` = md5('/') where `key` = 'index';

drop table page_regular;
drop table page_custom;

alter table `user` change role role_id tinyint(1) unsigned NOT NULL DEFAULT 0;

CREATE TABLE `rbac` (
  `rbac_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` tinyint(1) unsigned DEFAULT NULL,
  `user_id` int(5) unsigned DEFAULT NULL,
  `permission_id` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`rbac_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

update `user` set `role_id` = 2 where `role_id` = 1;
update `user` set `role_id` = 1 where `role_id` = 3;

truncate table rbac;
insert into rbac(`role_id`, `permission_id`) values(1, 1), (2, 5), (2, 7), (2, 8), (2, 10), (2, 11), (3, 2), (3, 4), (4, 9), (4, 13);