<?php 

class Config {
	
	private static $parm = array (
		'db_user' => 'adempiere',
		'db_password' => 'adempiere',
		'db' => 'xe',
		'db_host' => 'localhost',
	);
	
	const ad_client_id = 1000002;
	const ad_org_id = 1000002;
	
	const year_storage = 2015;	// Anno di riferimento per l'archiviazione e conservazione sostitutiva
	
	const base_path = "/opt/owncloud/pointec/files";
	const webdav_path = "https://contabilita.pointec.it/remote.php/webdav";
	const webdav_user = "pointec";
	const webdav_password = "pariedispari";
	const archivio = "archivio";
	const conservazione = "conservazione";
		
	const fatture_1000005 = "fatture acquisti";
	const fatture_1000006 = "fatture acquisti";
	const fatture_1000046 = "fatture acquisti";
	const fatture_999775  = "fatture intra";
	const fatture_1000056 = "fatture extra";
	const fatture_1000042 = "fatture vendita ecommerce";
	const fatture_1000002 = "fatture vendita ordinarie";
	const fatture_1000004 = "fatture vendita ordinarie";
	
	const prefix_1000005 = "AP-FATTU-";
	const prefix_1000006 = "AP-NCRED-";
	const prefix_1000046 = "AP-PARCE-";
	const prefix_999775  = "AP-INTRA-";
	const prefix_1000056 = "AP-EXTRA-";
	const prefix_1000002 = "AC-FATTU-";
	const prefix_1000004 = "AC-NCRED-";
	const prefix_1000042 = "AC-FATEC-";
	
	public static function get($k) {
		return self::$parm[$k];
	}
	
}

?>