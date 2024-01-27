<?php


$table_setup = <<<EOD
CREATE TABLE settings (
	id smallint(6) unsigned NOT NULL auto_increment,
	install_time_utc timestamp NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB;


CREATE TABLE products (
	id smallint(6) unsigned NOT NULL auto_increment,
	comment varchar(150) NULL,
	out_message varchar(144) NULL,
	out_message_uuid tinyint(1) DEFAULT 0,
	ask_amount int(25),
	respond_amount int(25),
	port smallint(6) NULL,
	status tinyint(1) NULL,
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;


CREATE TABLE i_addresses (
	id smallint(6) unsigned NOT NULL auto_increment,	
	iaddr varchar(400) NULL,
	ask_amount int(25), 
	comment varchar(400) NULL,
	port smallint(6) NULL,
	product_id int(25),
	status tinyint(1) NULL,	
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE incoming (
	id smallint(6) unsigned NOT NULL auto_increment,
	txid varchar(150) NULL,	
	buyer_address varchar(100) NULL,
	amount int(25) unsigned,
	port smallint(6) NULL,
	processed tinyint(1) DEFAULT 0,
	block_height int(15),
	time_utc timestamp NULL,
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE responses (
	id smallint(6) unsigned NOT NULL auto_increment,
	incoming_id smallint(6) unsigned NOT NULL,
	txid varchar(150) NULL,
	type varchar(150) NULL,
	buyer_address varchar(100) NULL,
	out_amount int(25) unsigned,
	port smallint(6) NULL,	
	out_message varchar(144) NULL,
	out_message_uuid tinyint(1) DEFAULT 0,
	time_utc timestamp NULL,
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;


EOD;




$result = $pdo->query("SHOW TABLES LIKE 'products'");
if($result !== false && $result->rowCount() > 0){	
}else{
	//create tables
	$result = $pdo->query($table_setup);
	//save time installed
	$given = new DateTime();
	$given->setTimezone(new DateTimeZone("UTC"));
	$query='INSERT INTO settings (
		install_time_utc
		)
		VALUES
		(?)
		';	
	
	$array=array(
		$given->format("Y-m-d H:i:s")
		);				
			
	$stmt=$pdo->prepare($query);
	$stmt->execute($array);		
	
	
}
unset($table_setup);
unset($result);

?>