<?php 

	class Util {
		
		private static $_errors = Array();
		private static $_hasErrors = false;
		
		/**
		 * Restituisce la directory di archivio
		 * @param unknown $c_doctype_id
		 * @param unknown $year
		 * @return string
		 */
		public static function getArchivio($c_doctype_id, $year) {
		
			$type = "fatture_" . $c_doctype_id;
						
			$path = Config::base_path . "/" . Config::archivio . "/" . $year . "/" . constant("Config::$type");
			
			self::checkPath($path);
			
			return $path;
		
		}
		
		/**
		 * Resituisce la directory di conservazione
		 * @param unknown $c_doctype_id
		 * @param unknown $year
		 * @return string
		 */
		public static function getDaConservare($year, $month) {
		
			$path = Config::base_path . "/" . Config::conservazione . "/" . $year . "/fatture/" . $month;
			
			self::checkPath($path);
				
			return $path;
		
		}
		
		/**
		 * Verifica se il percorso esiste altrimenti lo crea
		 * @param unknown $path
		 */
		private static function checkPath($path) {
			
			if (!file_exists($path)) {
				mkdir($path, 0755, true);
				echo "Directory creata [$path]\n";
			}
			
			
						
		}
		
		/**
		 * Aggiunta di un messaggio di errore
		 * @param unknown $message
		 */
		public static function addError($message) {
			
			self::$_errors[] = $message;
			self::$_hasErrors = true;
			
		}
		
		/**
		 * Stampa degli eventuali errori con relativa uscita dalla procedura
		 */
		public static function printErrorAndExit() {
		
			if (self::$_hasErrors) {
				
				echo("#################### ERRORE ########################\n");
				echo implode("\n", self::$_errors)."\n";
				echo("####################################################\n");
					
				Exit(-1);
				
			}
		
		}
		
	}

?>