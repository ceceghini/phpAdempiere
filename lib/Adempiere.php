<?php 

require_once 'Config.php';
require_once 'Util.php';
require_once 'DB.php';
require_once 'Array2XML.php';

class Adempiere {
	
	function __construct() {
		
		//Util::setDebug();
		
		DB::initialize();
		
		clearstatcache();
		
	}
	
}

?>