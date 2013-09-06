CREATE TABLE IF NOT EXISTS ajxp_feed (
  id int(11) NOT NULL AUTO_INCREMENT,
  edate int(11) NOT NULL,
  etype varchar(12) NOT NULL,
  htype varchar(32) NOT NULL,
  user_id varchar(255) NOT NULL,
  repository_id varchar(33) NOT NULL,
  user_group varchar(500),
  repository_scope varchar(50),
  repository_owner varchar(255),
  content longblob NOT NULL,
  PRIMARY KEY (id),
  KEY edate (edate,etype,htype,user_id,repository_id)
)
