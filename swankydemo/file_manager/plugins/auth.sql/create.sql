CREATE TABLE IF NOT EXISTS `ajxp_users` (
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `groupPath` varchar(255) NULL,
  PRIMARY KEY  (`login`)
)