/*
SQLyog Ultimate v12.2.6 (64 bit)
MySQL - 8.0.20 : Database - autoban
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`autoban` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */ /*!80016 DEFAULT ENCRYPTION='N' */;

/*Table structure for table `ab_analyzer_status` */

CREATE TABLE `ab_analyzer_status` (
  `flagmode` tinyint unsigned NOT NULL,
  `begin` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `finish` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`flagmode`,`begin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*Table structure for table `allvisits` */

CREATE TABLE `allvisits` (
  `ip` int unsigned DEFAULT '0',
  `date` int unsigned NOT NULL,
  `url` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  `siteid` int unsigned NOT NULL,
  `ownervisit` tinyint(1) NOT NULL DEFAULT '0',
  `agent` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  `refer` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  `method` tinyint unsigned NOT NULL DEFAULT '0',
  KEY `ip` (`ip`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*Table structure for table `ban_agents` */

CREATE TABLE `ban_agents` (
  `robotname` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


CREATE TABLE `blacklistip` (
  `siteid` int unsigned NOT NULL,
  `ip` int unsigned DEFAULT '0',
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ban_count` tinyint(3) unsigned zerofill NOT NULL DEFAULT '000',
  `reason` tinyint unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `siteidkey` (`siteid`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*Table structure for table `search_robots` */

CREATE TABLE `search_robots` (
  `robotname` varchar(64) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*Data for the table `search_robots` */

insert  into `search_robots`(`robotname`) values 

('Googlebot'),

('SEOkicks-Robot'),

('YandexBot'),

('Mail.RU_Bot');



/*Table structure for table `sites` */

CREATE TABLE `sites` (
  `uid` varchar(36) COLLATE utf8_bin NOT NULL,
  `active` tinyint unsigned NOT NULL DEFAULT '0',
  `description` varchar(64) COLLATE utf8_bin NOT NULL,
  `userid` int unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `disabled` tinyint unsigned NOT NULL DEFAULT '0',
  `siteid` int unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `siteid` (`siteid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*Table structure for table `users` */

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(64) COLLATE utf8_bin NOT NULL,
  `password` varchar(60) COLLATE utf8_bin NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastvisit` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint NOT NULL DEFAULT '0',
  `disabled` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`login`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*Table structure for table `users_activations` */

CREATE TABLE `users_activations` (
  `authkey` varchar(36) COLLATE utf8_bin NOT NULL,
  `used` tinyint DEFAULT '0',
  `userid` int unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`authkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
