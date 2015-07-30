#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_dixeasylogin_openid tinytext,
	tx_dixeasylogin_identifiers tinytext
);

#
# Table structure for table 'tx_dixeasylogin_identifiers'
#
CREATE TABLE tx_dixeasylogin_identifiers (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	user text,
	conn_type varchar(255) DEFAULT '' NOT NULL,
	conn_name varchar(255) DEFAULT '' NOT NULL,
	identifier varchar(255) DEFAULT '' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=InnoDB;