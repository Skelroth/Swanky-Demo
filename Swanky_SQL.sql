-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Host: 203.124.112.160
-- Generation Time: Sep 05, 2013 at 08:11 PM
-- Server version: 5.0.96
-- PHP Version: 5.1.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `Swanky`
--
CREATE DATABASE `Swanky` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `Swanky`;

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_comments`
--

CREATE TABLE `SWANK_couch_comments` (
  `id` int(11) NOT NULL auto_increment,
  `tpl_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) default NULL,
  `name` tinytext,
  `email` varchar(128) default NULL,
  `link` varchar(255) default NULL,
  `ip_addr` varchar(100) default NULL,
  `date` datetime default NULL,
  `data` text,
  `approved` tinyint(4) default '0',
  PRIMARY KEY  (`id`),
  KEY `SWANK_couch_comments_Index01` (`date`),
  KEY `SWANK_couch_comments_Index02` (`page_id`,`approved`,`date`),
  KEY `SWANK_couch_comments_Index03` (`tpl_id`,`approved`,`date`),
  KEY `SWANK_couch_comments_Index04` (`approved`,`date`),
  KEY `SWANK_couch_comments_Index05` (`tpl_id`,`page_id`,`approved`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `SWANK_couch_comments`
--


-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_data_numeric`
--

CREATE TABLE `SWANK_couch_data_numeric` (
  `page_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `value` decimal(65,2) default '0.00',
  PRIMARY KEY  (`page_id`,`field_id`),
  KEY `SWANK_couch_data_numeric_Index01` (`value`),
  KEY `SWANK_couch_data_numeric_Index02` (`field_id`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `SWANK_couch_data_numeric`
--


-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_data_text`
--

CREATE TABLE `SWANK_couch_data_text` (
  `page_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `value` longtext,
  `search_value` text,
  PRIMARY KEY  (`page_id`,`field_id`),
  KEY `SWANK_couch_data_text_Index01` (`search_value`(255)),
  KEY `SWANK_couch_data_text_Index02` (`field_id`,`search_value`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `SWANK_couch_data_text`
--


-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_fields`
--

CREATE TABLE `SWANK_couch_fields` (
  `id` int(11) NOT NULL auto_increment,
  `template_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) default NULL,
  `k_desc` varchar(255) default NULL,
  `k_type` varchar(128) NOT NULL,
  `hidden` int(1) default NULL,
  `search_type` varchar(20) default 'text',
  `k_order` int(11) default NULL,
  `data` longtext,
  `default_data` longtext,
  `required` int(1) default NULL,
  `deleted` int(1) default NULL,
  `validator` varchar(255) default NULL,
  `validator_msg` text,
  `k_separator` varchar(20) default NULL,
  `val_separator` varchar(20) default NULL,
  `opt_values` text,
  `opt_selected` tinytext,
  `toolbar` varchar(20) default NULL,
  `custom_toolbar` text,
  `css` text,
  `custom_styles` text,
  `maxlength` int(11) default NULL,
  `height` int(11) default NULL,
  `width` int(11) default NULL,
  `k_group` varchar(128) default NULL,
  `collapsed` int(1) default NULL,
  `assoc_field` varchar(128) default NULL,
  `crop` int(1) default '0',
  `enforce_max` int(1) default '1',
  `quality` int(11) default NULL,
  `show_preview` int(1) default '0',
  `preview_width` int(11) default NULL,
  `preview_height` int(11) default NULL,
  `no_xss_check` int(1) default '0',
  `rtl` int(1) default '0',
  `body_id` tinytext,
  `body_class` tinytext,
  `disable_uploader` int(1) default '0',
  `_html` text COMMENT 'Internal',
  `dynamic` text,
  `custom_params` text,
  PRIMARY KEY  (`id`),
  KEY `SWANK_couch_fields_index01` (`k_group`,`k_order`,`id`),
  KEY `SWANK_couch_fields_Index02` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `SWANK_couch_fields`
--


-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_folders`
--

CREATE TABLE `SWANK_couch_folders` (
  `id` int(11) NOT NULL auto_increment,
  `pid` int(11) default '-1',
  `template_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) default NULL,
  `k_desc` mediumtext,
  `image` text,
  `access_level` int(11) default '0',
  `weight` int(11) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `SWANK_couch_folders_Index02` (`template_id`,`name`),
  KEY `SWANK_couch_folders_Index01` (`template_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `SWANK_couch_folders`
--


-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_fulltext`
--

CREATE TABLE `SWANK_couch_fulltext` (
  `page_id` int(11) NOT NULL,
  `title` varchar(255) default NULL,
  `content` text,
  PRIMARY KEY  (`page_id`),
  FULLTEXT KEY `SWANK_couch_fulltext_Index01` (`title`),
  FULLTEXT KEY `SWANK_couch_fulltext_Index02` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `SWANK_couch_fulltext`
--

INSERT INTO `SWANK_couch_fulltext` VALUES(1, 'Default page for index.php * PLEASE CHANGE THIS TITLE *', '');
INSERT INTO `SWANK_couch_fulltext` VALUES(5, 'Default page for tournaments.php * PLEASE CHANGE THIS TITLE *', '');
INSERT INTO `SWANK_couch_fulltext` VALUES(6, 'Default page for users.php * PLEASE CHANGE THIS TITLE *', '');

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_levels`
--

CREATE TABLE `SWANK_couch_levels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  `title` varchar(100) default NULL,
  `k_level` int(11) default '0',
  `disabled` int(11) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `SWANK_couch_levels_index01` (`k_level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `SWANK_couch_levels`
--

INSERT INTO `SWANK_couch_levels` VALUES(1, 'superadmin', 'Super Admin', 10, 0);
INSERT INTO `SWANK_couch_levels` VALUES(2, 'admin', 'Administrator', 7, 0);
INSERT INTO `SWANK_couch_levels` VALUES(3, 'authenticated_user_special', 'Authenticated User (Special)', 4, 0);
INSERT INTO `SWANK_couch_levels` VALUES(4, 'authenitcated_user', 'Authenticated User', 2, 0);
INSERT INTO `SWANK_couch_levels` VALUES(5, 'unauthenticated_user', 'Everybody', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_pages`
--

CREATE TABLE `SWANK_couch_pages` (
  `id` int(11) NOT NULL auto_increment,
  `template_id` int(11) NOT NULL,
  `parent_id` int(11) default '0',
  `page_title` varchar(255) default NULL,
  `page_name` varchar(255) default NULL,
  `creation_date` datetime default '0000-00-00 00:00:00',
  `modification_date` datetime default '0000-00-00 00:00:00',
  `publish_date` datetime default '0000-00-00 00:00:00',
  `status` int(11) default NULL,
  `is_master` int(1) default '0',
  `page_folder_id` int(11) default '-1',
  `access_level` int(11) default '0',
  `comments_count` int(11) default '0',
  `comments_open` int(1) default '1',
  `nested_parent_id` int(11) default '-1',
  `weight` int(11) default '0',
  `show_in_menu` int(1) default '1',
  `menu_text` varchar(255) default NULL,
  `is_pointer` int(1) default '0',
  `pointer_link` text,
  `pointer_link_detail` text,
  `open_external` int(1) default '0',
  `masquerades` int(1) default '0',
  `strict_matching` int(1) default '0',
  `file_name` varchar(260) default NULL,
  `file_ext` varchar(20) default NULL,
  `file_size` int(11) default '0',
  `file_meta` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `SWANK_couch_pages_Index03` (`template_id`,`page_name`),
  KEY `SWANK_couch_pages_Index01` (`template_id`,`publish_date`),
  KEY `SWANK_couch_pages_Index02` (`template_id`,`page_folder_id`,`publish_date`),
  KEY `SWANK_couch_pages_Index04` (`template_id`,`modification_date`),
  KEY `SWANK_couch_pages_Index05` (`template_id`,`page_folder_id`,`modification_date`),
  KEY `SWANK_couch_pages_Index06` (`template_id`,`page_folder_id`,`page_name`),
  KEY `SWANK_couch_pages_Index07` (`template_id`,`comments_count`),
  KEY `SWANK_couch_pages_Index08` (`template_id`,`page_title`),
  KEY `SWANK_couch_pages_Index09` (`template_id`,`page_folder_id`,`page_title`),
  KEY `SWANK_couch_pages_Index10` (`template_id`,`page_folder_id`,`comments_count`),
  KEY `SWANK_couch_pages_Index11` (`template_id`,`parent_id`,`modification_date`),
  KEY `SWANK_couch_pages_Index12` (`parent_id`,`modification_date`),
  KEY `SWANK_couch_pages_Index13` (`template_id`,`is_pointer`,`masquerades`,`pointer_link_detail`(255)),
  KEY `SWANK_couch_pages_Index14` (`template_id`,`file_name`(255)),
  KEY `SWANK_couch_pages_Index15` (`template_id`,`page_folder_id`,`file_name`(255)),
  KEY `SWANK_couch_pages_Index16` (`template_id`,`file_ext`,`file_name`(255)),
  KEY `SWANK_couch_pages_Index17` (`template_id`,`page_folder_id`,`file_ext`,`file_name`(255)),
  KEY `SWANK_couch_pages_Index18` (`template_id`,`file_size`),
  KEY `SWANK_couch_pages_Index19` (`template_id`,`page_folder_id`,`file_size`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `SWANK_couch_pages`
--

INSERT INTO `SWANK_couch_pages` VALUES(1, 1, 0, 'Default page for index.php * PLEASE CHANGE THIS TITLE *', 'default-page-for-index-php-please-change-this-title', '2013-06-25 14:42:34', '2013-06-25 17:04:51', '2013-06-25 14:42:34', NULL, 1, -1, 0, 0, 1, -1, 0, 1, NULL, 0, NULL, NULL, 0, 0, 0, NULL, NULL, 0, NULL);
INSERT INTO `SWANK_couch_pages` VALUES(5, 4, 0, 'Default page for tournaments.php * PLEASE CHANGE THIS TITLE *', 'default-page-for-tournaments-php-please-change-this-title', '2013-06-25 17:39:55', '2013-06-25 18:07:13', '2013-06-25 17:39:55', NULL, 1, -1, 0, 0, 1, -1, 0, 1, NULL, 0, NULL, NULL, 0, 0, 0, NULL, NULL, 0, NULL);
INSERT INTO `SWANK_couch_pages` VALUES(6, 5, 0, 'Default page for users.php * PLEASE CHANGE THIS TITLE *', 'default-page-for-users-php-please-change-this-title', '2013-07-04 02:59:03', '2013-07-04 02:59:12', '2013-07-04 02:59:03', NULL, 1, -1, 7, 0, 1, -1, 0, 1, NULL, 0, NULL, NULL, 0, 0, 0, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_relations`
--

CREATE TABLE `SWANK_couch_relations` (
  `pid` int(11) NOT NULL,
  `fid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `weight` int(11) default '0',
  PRIMARY KEY  (`pid`,`fid`,`cid`),
  KEY `SWANK_couch_relations_Index01` (`pid`,`fid`,`weight`),
  KEY `SWANK_couch_relations_Index02` (`fid`,`cid`,`weight`),
  KEY `SWANK_couch_relations_Index03` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `SWANK_couch_relations`
--


-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_settings`
--

CREATE TABLE `SWANK_couch_settings` (
  `k_key` varchar(255) NOT NULL,
  `k_value` longtext,
  PRIMARY KEY  (`k_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `SWANK_couch_settings`
--

INSERT INTO `SWANK_couch_settings` VALUES('k_couch_version', '1.3.5');
INSERT INTO `SWANK_couch_settings` VALUES('nonce_secret_key', 'IiPGDOIgwluFZD1WPQv1f7ZafzJ8AAXIicOmrX2oIXt80VugcqIRY7rD7Bc7Czfl');
INSERT INTO `SWANK_couch_settings` VALUES('secret_key', 'cJha6q37DRibb0AtNTD2cz9Bl66cel0RuHr1yVzBcidOIdIWwMp9MYbxvHa9tAqo');

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_templates`
--

CREATE TABLE `SWANK_couch_templates` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) default NULL,
  `clonable` int(1) default '0',
  `executable` int(1) default '1',
  `title` varchar(255) default NULL,
  `access_level` int(11) default '0',
  `commentable` int(1) default '0',
  `hidden` int(1) default '0',
  `k_order` int(11) default '0',
  `dynamic_folders` int(1) default '0',
  `nested_pages` int(1) default '0',
  `gallery` int(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `SWANK_couch_templates_Index01` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `SWANK_couch_templates`
--

INSERT INTO `SWANK_couch_templates` VALUES(1, 'index.php', '', 0, 1, NULL, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `SWANK_couch_templates` VALUES(4, 'tournaments.php', '', 0, 1, NULL, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `SWANK_couch_templates` VALUES(5, 'users.php', '', 0, 1, NULL, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_couch_users`
--

CREATE TABLE `SWANK_couch_users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(128) NOT NULL,
  `title` varchar(255) default NULL,
  `password` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `activation_key` varchar(64) default NULL,
  `registration_date` datetime default NULL,
  `access_level` int(11) default '0',
  `disabled` int(11) default '0',
  `system` int(11) default '0',
  `last_failed` bigint(11) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `SWANK_couch_users_email` (`email`),
  UNIQUE KEY `SWANK_couch_users_name` (`name`),
  KEY `SWANK_couch_users_activation_key` (`activation_key`),
  KEY `SWANK_couch_users_index01` (`access_level`),
  KEY `SWANK_couch_users_index02` (`access_level`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `SWANK_couch_users`
--

INSERT INTO `SWANK_couch_users` VALUES(1, 'skelroth', 'skelroth', '$P$BZAdqLgK/vHrz0lXoTBBPxOH/Q/CTd1', 'jordan.mcgreen@gmail.com', '', '2013-06-25 14:37:06', 10, 0, 1, 1372872279);

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_servers`
--

CREATE TABLE `SWANK_servers` (
  `type` varchar(8) NOT NULL,
  `name` varchar(15) NOT NULL,
  `purpose` varchar(20) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `listname` varchar(20) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `spare1` varchar(1) NOT NULL,
  `spare2` varchar(1) NOT NULL,
  `ID` int(3) NOT NULL auto_increment,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

--
-- Dumping data for table `SWANK_servers`
--

INSERT INTO `SWANK_servers` VALUES('q', 'q', 'q', '3.3.3.3', 'sad', 'Admin', '', '', 17);
INSERT INTO `SWANK_servers` VALUES('File', 'ALPHA', 'Files/Games', '1.1.1.1', 'ALPHA', 'Skelroth', '', '', 16);

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_support`
--

CREATE TABLE `SWANK_support` (
  `description` varchar(700) NOT NULL,
  `location` varchar(60) NOT NULL,
  `urgency` varchar(200) NOT NULL,
  `submitted` varchar(30) NOT NULL,
  `order` tinyint(2) NOT NULL auto_increment,
  `complete` int(1) NOT NULL,
  PRIMARY KEY  (`order`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

--
-- Dumping data for table `SWANK_support`
--

INSERT INTO `SWANK_support` VALUES('yeeyee', '12', '..Take your time..', 'Skelroth', 12, 0);

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_tournament`
--

CREATE TABLE `SWANK_tournament` (
  `game` varchar(30) NOT NULL,
  `name_type` varchar(15) NOT NULL,
  `teams_players` varchar(4) NOT NULL,
  `stance` varchar(15) NOT NULL,
  `start` time NOT NULL,
  `winner` varchar(15) NOT NULL,
  `creator` varchar(25) NOT NULL,
  `ID` int(11) NOT NULL auto_increment,
  `description` varchar(700) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=33 ;

--
-- Dumping data for table `SWANK_tournament`
--

INSERT INTO `SWANK_tournament` VALUES('CoD:4', 'FFA', '1/16', '[UNOFFICIAL]', '22:50:00', '', 'Admin', 28, 'Call of Duty 4 Modern Warfare: Free For All match, capacity for 16 players. Every man for themselves and stay away from my washing machine!');
INSERT INTO `SWANK_tournament` VALUES('Yes', '1', '1', '[UNOFFICIAL]', '11:57:00', '', 'Admin', 30, 'dsa');
INSERT INTO `SWANK_tournament` VALUES('Blur', 'FFA', '1/16', '[UNOFFICIAL]', '18:50:00', '', 'Admin', 29, 'Blur: All out free for all racing! See you at the finish line!');
INSERT INTO `SWANK_tournament` VALUES('ewr', 'wrewr', 'wer', '[UNOFFICIAL]', '03:02:00', '', 'Admin', 32, '');

-- --------------------------------------------------------

--
-- Table structure for table `SWANK_user_detail`
--

CREATE TABLE `SWANK_user_detail` (
  `ID` int(3) NOT NULL auto_increment,
  `alias` varchar(30) NOT NULL,
  `cpu` varchar(40) NOT NULL,
  `gpu` varchar(40) NOT NULL,
  `operating_system` varchar(15) NOT NULL,
  `wins` int(3) NOT NULL,
  `loss` int(3) NOT NULL,
  `entered` int(3) NOT NULL,
  `fname` varchar(20) NOT NULL,
  `lname` varchar(30) NOT NULL,
  `email` varchar(50) NOT NULL,
  `seatnumber` int(3) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

--
-- Dumping data for table `SWANK_user_detail`
--

INSERT INTO `SWANK_user_detail` VALUES(1, 'Skelroth', 'i7 2600k @ 4.9GHz', '7950 3GB', 'Win 7 - 64bit', 4, 1, 5, 'Jordan', 'Mc', 'fake@email.com', 1);
INSERT INTO `SWANK_user_detail` VALUES(2, 'Marblez', 'i7 2500k @ 3.8GHz', 'AMD 7770', 'Win 7 - 64bit', 5, 2, 7, 'Mitchell', 'Mc', 'fake@email.com', 3);
INSERT INTO `SWANK_user_detail` VALUES(13, 'Final_Test', 'CPU_NAME', 'GPU_NAME', 'OS_NAME', 0, 0, 0, 'F_NAME', 'L_NAME', 'fake@email.com', 4);
INSERT INTO `SWANK_user_detail` VALUES(14, 'Admin', 'AdminCPU', 'AdminGPU', 'Admin_OS', 0, 0, 0, 'Administrator', 'Smith', 'admin@email.com', 0);
INSERT INTO `SWANK_user_detail` VALUES(15, 'ConfigZero', 'AMD FX-6100 black edition', 'XFX Radeon HD 7770 black edition', 'Windows 7 64', 0, 0, 0, '', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tz_members`
--

CREATE TABLE `tz_members` (
  `id` int(11) NOT NULL auto_increment,
  `usr` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `pass` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `email` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `regIP` varchar(15) collate utf8_unicode_ci NOT NULL default '',
  `dt` datetime NOT NULL default '0000-00-00 00:00:00',
  `admin` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `usr` (`usr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=19 ;

--
-- Dumping data for table `tz_members`
--

INSERT INTO `tz_members` VALUES(1, 'Skelroth', 'd3fe098bbc3714dc40a914b8f3ef1890', 'fake@email.com', '58.167.206.189', '2013-06-25 00:48:46', 1);
INSERT INTO `tz_members` VALUES(17, 'FITESully', '2aabe8c3a6d4247c2dcd595d8ef67689', 'sully@fites.net', '98.235.232.192', '2013-08-23 16:34:59', 0);
INSERT INTO `tz_members` VALUES(15, 'Tachaeon', 'a4c9c8191501f0b67167f3b7b39de042', 'tachaeon@gmail.com', '107.199.20.209', '2013-08-23 09:12:14', 0);
INSERT INTO `tz_members` VALUES(13, 'Admin', 'a262d465b7727ff5bbe3fea8e1b34971', 'fake@email.com', '101.103.145.233', '2013-07-18 16:18:32', 1);
INSERT INTO `tz_members` VALUES(14, 'Regular', '7f854253817e241c6479e9939fb42b3e', 'fake@email.com', '101.103.145.233', '2013-07-18 16:20:47', 0);
INSERT INTO `tz_members` VALUES(16, 'ConfigZero', '8f63c0992633be501abcbae9503793af', 'marshalltheo86@hotmail.com', '75.139.180.166', '2013-08-23 12:43:57', 0);
INSERT INTO `tz_members` VALUES(11, 'Marblez', 'a75b84548fd1c23ef97bdf230e4960dd', 'fake@email.com', '101.103.145.233', '2013-07-18 13:34:53', 0);
INSERT INTO `tz_members` VALUES(18, 'garfi3ld', '64454cbf8791934051a8880b2dd74c6c', 'badz24@msn.com', '71.66.229.116', '2013-08-23 23:26:50', 0);
