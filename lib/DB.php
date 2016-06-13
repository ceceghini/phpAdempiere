<?php 

class DB {
	
	private static $instance = NULL;
	
	public static function initialize() {
		if (!isset(self::$instance)) {
			
			$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
			//$pdo_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "ALTER SESSION SET NLS_DATE_FORMAT='yyyy-mm-dd";
			
			$tns = "
			(DESCRIPTION =
			    (ADDRESS_LIST =
			      (ADDRESS = (PROTOCOL = TCP)(HOST = ".Config::get("db_host").")(PORT = 1521))
			    )
			    (CONNECT_DATA =
			      (SERVICE_NAME = ".Config::get("db").")
			    )
			  )
			       ";
			self::$instance = new PDO("oci:dbname=".$tns,Config::get("db_user"),Config::get("db_password"), $pdo_options);
			
			$sql = "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD'";
			self::$instance->exec($sql);			
			
		}
	}
	
	public static function query($sql) {
		return self::$instance->query($sql);
	}
	
	public static function execute($sql) {
		self::$instance->exec($sql);
	}
	
	public static function getInvoice($c_invoice_id) {
		$sql = "select * from c_invoice where c_invoice_id = $c_invoice_id";
		return self::$instance->query($sql)->fetch();
	}
	
	public static function getBPartner($c_bparnter_id) {
		$sql = "select * from c_bpartner where c_bpartner_id = $c_bparnter_id";
		return self::$instance->query($sql)->fetch();
	}
	
}

?>