CREATE TABLE IF NOT EXISTS `statistics` (
	`id`       int(11) NOT NULL auto_increment,
	`lifetime` int(11) NOT NULL,
	`period`   int(11) NOT NULL,
	`data`     varchar(256) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

