<?php 

	require_once 'lib/Adempiere.php';
	
	class Conservazione {
		
		private $pdv;
		
		public function run() {
			
			$a = new Adempiere();
			
			$this->verifica();
			
			Util::printErrorAndExit();
			
			$this->elaboraMesi();
			
			Util::printErrorAndExit();
		
			//$this->saveXml();
			
		}
		
		/**
		 * Elaborazione dei mesi per la conservazione
		 */
		private function elaboraMesi() {
			
			try {
				
				$this->elaboraMese("01");
				$this->elaboraMese("02");
				$this->elaboraMese("03");
				$this->elaboraMese("04");
				$this->elaboraMese("05");
				$this->elaboraMese("06");
				$this->elaboraMese("07");
				$this->elaboraMese("08");
				$this->elaboraMese("09");
				$this->elaboraMese("10");
				$this->elaboraMese("11");
				$this->elaboraMese("12");
				
				
			} catch (Exception $e) {
				Util::addError(e);
			}
			
		}
		
		private function elaboraMese($month) {
			
			$this->initXml(Config::year_storage, $month);
			
			$this->elaboraAcquisti($month);
			$this->elaboraVendite($month);
			
			$this->saveXml(Config::year_storage, $month);
			
		}
		
		/**
		 * Elaborazione di un mese per la conservazione
		 * @param unknown $month
		 */
		private function elaboraAcquisti($month) {
			
			$sql = "select i.c_invoice_id"
					 . "		from c_invoice i "
					 . "		  join C_DOCTYPE d on i.C_DOCTYPE_ID = d.C_DOCTYPE_ID "
					 . "	   where i.DOCSTATUS = 'CO' "
					 . "		 and i.ad_client_id = " . Config::ad_client_id
					 . "		 and i.ad_org_id = " . Config::ad_org_id
					 . "		 and to_char(i.vatledgerdate, 'yyyy') = '".Config::year_storage."'"
					 . "		 and to_char(i.vatledgerdate, 'mm') = '".$month."'"
					 . "		 and i.C_DOCTYPE_ID NOT IN (1000050)"
					 . "		 and d.docbasetype in ('APC', 'API')"
					 . "		 and i.vatledgerno is not null"
					 . "    order by 1";
			
			foreach(DB::query($sql) as $row) {
				
				$i = DB::getInvoice($row["C_INVOICE_ID"]);
				$b = DB::getBPartner($i["C_BPARTNER_ID"]);
				
				$nomeFileDest = constant("Config::prefix_{$i["C_DOCTYPE_ID"]}") . "[" . $i["DOCUMENTNO"] . "]-[" . $i["DATEINVOICED"] . "].pdf";
				$dest = Util::getDaConservare(substr($i["VATLEDGERDATE"], 0, 4), substr($i["VATLEDGERDATE"], 5, 2));
				
				if (file_exists("$dest/$nomeFileDest")) {
				 	if (filesize("$dest/$nomeFileDest")==0)
				 		unlink("$dest/$nomeFileDest");
			 	}
				
				if (!file_exists("$dest/$nomeFileDest")) {
					$fileSource = $this->getNomeFileSource($i, $b);
					$source = Util::getArchivio($i["C_DOCTYPE_ID"], substr($i["VATLEDGERDATE"], 0, 4));
					$this->copyfile("$source/$fileSource", "$dest/$nomeFileDest");
					echo "File copiato: $nomeFileDest\n";
				}
				
				if (filesize("$dest/$nomeFileDest")==0)
					Util::addError("File [$nomeFileDest] esistente con dimensione 0\n");
				
				$this->addFileToXml($i, $b, $dest, $nomeFileDest);
				
			}
			
		}
		
		/**
		 * Inizializzazione file xml
		 * @param unknown $year
		 * @param unknown $month
		 */
		private function initXml($year, $month) {
			
			$this->pdv = array();
			$this->pdv["pdvid"] = "PDF-FATTURE-$year-$month";
			$this->pdv["docClass"] = array();
			$this->pdv["docClass"]["@value"] = "5045__Fatture";
			$this->pdv["docClass"]["@attributes"]["namespace"] = "conservazione.docExt";
			$this->pdv["files"]["file"] = array();
			
		}
		
		/**
		 * Aggiunta di un file fattura al file xml
		 * @param unknown $i
		 * @param unknown $_file
		 * @param unknown $hash
		 */
		private function addFileToXml($i, $b, $dest, $nomeFileDest) {
		
			//Element file = doc.createElement("file");
			//files.appendChild(file);
			
			$file = array();
			
			$file["docid"] = $i["DOCUMENTNO"];
			$file["filename"] = $nomeFileDest;
			$file["mimetype"] = "application/pdf";
			$file["closingDate"] = $i["UPDATED"];
			$hash = base64_encode(hash_file("sha256", "$dest/$nomeFileDest", true));
			$file["hash"]["@attributes"]["algorithm"] = "SHA-256";
			$file["hash"]["value"] = $hash;
			
			$file["metadata"]["mandatory"]["singlemetadata"][] = $this->getSingleMetaData("conservazione.doc", "dataDocumentoTributario", $i["DATEINVOICED"]);
			$file["metadata"]["mandatory"]["singlemetadata"][] = $this->getSingleMetaData("conservazione.doc", "oggettodocumento", $i["POREFERENCE"] . " " . $i["DATEINVOICED"]);
			
			$complexmetadata = array();
			$complexmetadata["@attributes"]["namespace"] = "conservazione.doc";
			$complexmetadata["@attributes"]["name"] = "soggettoproduttore";
			$complexmetadata["@attributes"]["namespaceNode"] = "conservazione.soggetti";
			$complexmetadata["@attributes"]["nodeName"] = "soggetto";
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "codicefiscale", "01975730225");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "cognome", "");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "denominazione", "Pointec Srl");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "nome", "");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "partitaiva", "01975730225");
			$file["metadata"]["mandatory"]["complexmetadata"][] = $complexmetadata;
			
			$complexmetadata = array();
			$complexmetadata["@attributes"]["namespace"] = "conservazione.doc";
			$complexmetadata["@attributes"]["name"] = "destinatario";
			$complexmetadata["@attributes"]["namespaceNode"] = "conservazione.soggetti";
			$complexmetadata["@attributes"]["nodeName"] = "soggetto";
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "codicefiscale", $b["FISCALCODE"]);
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "cognome", "");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "denominazione", $b["NAME"]);
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "nome", "");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "partitaiva", $b["TAXID"]);
			$file["metadata"]["mandatory"]["complexmetadata"][] = $complexmetadata;
			
			$complexmetadata = array();
			$complexmetadata["@attributes"]["namespace"] = "conservazione.doc";
			$complexmetadata["@attributes"]["name"] = "soggettotributario";
			$complexmetadata["@attributes"]["namespaceNode"] = "conservazione.soggetti";
			$complexmetadata["@attributes"]["nodeName"] = "soggetto";
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "codicefiscale", "01975730225");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "cognome", "");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "denominazione", "Pointec Srl");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "nome", "");
			$complexmetadata["singlemetadata"][] = $this->getSingleMetaData("conservazione.soggetti", "partitaiva", "01975730225");
			$file["metadata"]["mandatory"]["complexmetadata"][] = $complexmetadata;
			
			$this->pdv["files"]["file"][] = $file;
		
		}
		
		/**
		 * Creazione di un singleMetaData
		 * @param unknown $namespace
		 * @param unknown $name
		 * @param unknown $value
		 */
		private function getSingleMetaData($namespace, $name, $value) {
			
			$singlemetadata = array();
			$singlemetadata["namespace"] = $namespace;
			$singlemetadata["name"] = $name;
			$singlemetadata["value"] = $value;
			return $singlemetadata;
		}
		
		/**
		 * Salvataggio del file xml
		 */
		private function saveXml($year, $month) {
			
			$xml = Array2XML::createXML('PDV', $this->pdv);
			//echo $xml->saveXML();
			$xml->save(Config::base_path . "/" . Config::conservazione . "/" . $year . "/fatture/" . $month . "/IPDV-FATTURE-$year-$month.xml");
			//print_r($this->pdv);
		}
		
		/**
		 * Verifica delle informazioni prima della conservazione
		 */
		private function verifica() {
			
			try {
			
				// Passivo
				$sql = "select i.c_invoice_id"
					 . "		from c_invoice i "
					 . "		  join C_DOCTYPE d on i.C_DOCTYPE_ID = d.C_DOCTYPE_ID "
					 . "	   where i.DOCSTATUS = 'CO' "
					 . "		 and i.ad_client_id = " . Config::ad_client_id
					 . "		 and i.ad_org_id = " . Config::ad_org_id
					 . "		 and to_char(i.vatledgerdate, 'yyyy') = ".Config::year_storage
					 . "		 and i.C_DOCTYPE_ID NOT IN (1000050)"
					 . "		 and d.docbasetype in ('APC', 'API')"
					 . "		 and i.vatledgerno is not null"
					 . "    order by 1";
			
				foreach(DB::query($sql) as $row) {
																				
					$i = DB::getInvoice($row["C_INVOICE_ID"]);
					$b = DB::getBPartner($i["C_BPARTNER_ID"]);
					
					$fileSource = $this->getNomeFileSource($i, $b);
					
					$description = "archiviati\n" . $fileSource;
					
					if ($i["DESCRIPTION"] != $description) {
						
						Util::addError("La descrizione differisce dal nome del file generato [{$i["DOCUMENTNO"]}]\n");
						
					}
					
					$source = Util::getArchivio($i["C_DOCTYPE_ID"], substr($i["VATLEDGERDATE"], 0, 4));
					
					if (!file_exists("$source/$fileSource")) {
						
						Util::addError("[{$i["DOCUMENTNO"]}] File non esistente: $source/$fileSource");
						
					}
																				
				}
				
				// Attivo
				$sql = "select i.c_invoice_id"
						. "		from c_invoice i "
						. "		  join C_DOCTYPE d on i.C_DOCTYPE_ID = d.C_DOCTYPE_ID "
						. "	   where i.DOCSTATUS = 'CO' "
						. "		 and i.ad_client_id = " . Config::ad_client_id
						. "		 and i.ad_org_id = " . Config::ad_org_id
						. "		 and to_char(i.vatledgerdate, 'yyyy') = ".Config::year_storage
						. "		 and i.C_DOCTYPE_ID NOT IN (1000053, 1000052)"
						. "		 and d.docbasetype in ('ARC', 'ARI')"
						. "		 and i.vatledgerno is not null"
						. "    order by 1";
				
				foreach(DB::query($sql) as $row) {
					
					$i = DB::getInvoice($row["C_INVOICE_ID"]);
					
					$source = Util::getArchivio($i["C_DOCTYPE_ID"], substr($i["VATLEDGERDATE"], 0, 4));
					$fileSource = $i["DOCUMENTNO"] . ".pdf";
					
					if (!file_exists("$source/$fileSource")) {
					
						Util::addError("[{$i["DOCUMENTNO"]}] File non esistente: $source/$fileSource");
					
					}					
					
				}
				
				// Verifica buchi note credito
				$sql = "select min(documentno) min_documentno, max(documentno) max_documentno"
						. "		from c_invoice i "
						. "		  join C_DOCTYPE d on i.C_DOCTYPE_ID = d.C_DOCTYPE_ID "
						. "	   where i.DOCSTATUS = 'CO' "
						. "		 and i.ad_client_id = " . Config::ad_client_id
						. "		 and i.ad_org_id = " . Config::ad_org_id
						. "		 and to_char(i.vatledgerdate, 'yyyy') = ".Config::year_storage
						. "		 and i.C_DOCTYPE_ID IN (1000004)"
						. "		 and d.docbasetype in ('ARC', 'ARI')"
						. "		 and i.vatledgerno is not null";
				
				$row = DB::query($sql)->fetch();
				
				$prefix = "N".substr(Config::year_storage, 2, 2)."-";
				$min = str_replace($prefix, "", $row["MIN_DOCUMENTNO"]);
				$max = str_replace($prefix, "", $row["MAX_DOCUMENTNO"]);
				
				$source = Util::getArchivio(1000004, Config::year_storage);
				
				for ($i=$min;$i<=$max;$i++) {
					
					$fileSource = $prefix.str_pad($i, 5, "0", STR_PAD_LEFT).".pdf";
					
					if (!file_exists("$source/$fileSource"))							
						Util::addError("[BUCHI] File non esistente: $source/$fileSource");
					
				}
				
				// Verifica buchi fatture
				$sql = "select min(documentno) min_documentno, max(documentno) max_documentno"
						. "		from c_invoice i "
						. "		  join C_DOCTYPE d on i.C_DOCTYPE_ID = d.C_DOCTYPE_ID "
						. "	   where i.DOCSTATUS = 'CO' "
						. "		 and i.ad_client_id = " . Config::ad_client_id
						. "		 and i.ad_org_id = " . Config::ad_org_id
						. "		 and to_char(i.vatledgerdate, 'yyyy') = ".Config::year_storage
						. "		 and i.C_DOCTYPE_ID IN (1000002)"
						. "		 and d.docbasetype in ('ARC', 'ARI')"
						. "		 and i.vatledgerno is not null";

				$row = DB::query($sql)->fetch();

				$prefix = substr(Config::year_storage, 2, 2)."-";
				$min = str_replace($prefix, "", $row["MIN_DOCUMENTNO"]);
				$max = str_replace($prefix, "", $row["MAX_DOCUMENTNO"]);

				$source = Util::getArchivio(1000002, Config::year_storage);

				for ($i=$min;$i<=$max;$i++) {
						
					$fileSource = $prefix.str_pad($i, 5, "0", STR_PAD_LEFT).".pdf";
						
					if (!file_exists("$source/$fileSource"))							
						Util::addError("[BUCHI] File non esistente: $source/$fileSource");
						
				}
				
				// Verifica buchi fatture ecommerce
				$sql = "select min(documentno) min_documentno, max(documentno) max_documentno"
						. "		from c_invoice i "
						. "		  join C_DOCTYPE d on i.C_DOCTYPE_ID = d.C_DOCTYPE_ID "
						. "	   where i.DOCSTATUS = 'CO' "
						. "		 and i.ad_client_id = " . Config::ad_client_id
						. "		 and i.ad_org_id = " . Config::ad_org_id
						. "		 and to_char(i.vatledgerdate, 'yyyy') = ".Config::year_storage
						. "		 and i.C_DOCTYPE_ID IN (1000042)"
						. "		 and d.docbasetype in ('ARC', 'ARI')"
						. "		 and i.vatledgerno is not null";

					$row = DB::query($sql)->fetch();

					$prefix = 'EC'.Config::year_storage."-";
					$min = str_replace($prefix, "", $row["MIN_DOCUMENTNO"]);
					$max = str_replace($prefix, "", $row["MAX_DOCUMENTNO"]);

					$source = Util::getArchivio(1000042, Config::year_storage);

					for ($i=$min;$i<=$max;$i++) {

						$fileSource = $prefix.str_pad($i, 5, "0", STR_PAD_LEFT).".pdf";

						if (!file_exists("$source/$fileSource"))
							Util::addError("[BUCHI] File non esistente: $source/$fileSource");

					}
				
			
			} catch (Exception $e) {
				Util::addError(e);
			}
			
		}
		
		/**
		 * Costruzione nome file sorgente per le fatture di acquisto
		 * @param unknown $i
		 * @param unknown $b
		 * @return string
		 */
		private function getNomeFileSource($i, $b) {
			
			$file = "[{$i["VATLEDGERNO"]}]-[{$i["VATLEDGERDATE"]}]-["
			. ereg_replace("[^A-Za-z0-9?!]", "_", $b["NAME"])
			. "]-[{$i["DATEINVOICED"]}]-["
			. ereg_replace("[^A-Za-z0-9?!]", "_", $i["POREFERENCE"])
			. "].pdf";
			
			return $file;
			
		}
		
		private function elaboraVendite($month) {
			
			$sql = "select i.c_invoice_id"
				 . "		from c_invoice i "
				 . "		  join C_DOCTYPE d on i.C_DOCTYPE_ID = d.C_DOCTYPE_ID "
				 . "	   where i.DOCSTATUS = 'CO' "
				 . "		 and i.ad_client_id = " . Config::ad_client_id
				 . "		 and i.ad_org_id = " . Config::ad_org_id
				 . "		 and to_char(i.vatledgerdate, 'yyyy') = ".Config::year_storage
				 . "		 and to_char(i.vatledgerdate, 'mm') = '".$month."'"
				 . "		 and i.C_DOCTYPE_ID NOT IN (1000053, 1000052)"
				 . "		 and d.docbasetype in ('ARC', 'ARI')"
				 . "		 and i.vatledgerno is not null"
				 . "    order by 1";
			
			 foreach(DB::query($sql) as $row) {
			 		
			 	$i = DB::getInvoice($row["C_INVOICE_ID"]);
			 	$b = DB::getBPartner($i["C_BPARTNER_ID"]);
			 		
			 	$nomeFileDest = constant("Config::prefix_{$i["C_DOCTYPE_ID"]}") . "[" . $i["DOCUMENTNO"] . "]-[" . $i["DATEINVOICED"] . "].pdf";
			 	$dest = Util::getDaConservare(substr($i["VATLEDGERDATE"], 0, 4), substr($i["VATLEDGERDATE"], 5, 2));
			 	
			 	if (file_exists("$dest/$nomeFileDest")) {
				 	if (filesize("$dest/$nomeFileDest")==0)
				 		unlink("$dest/$nomeFileDest");
			 	}
			 	
			 	if (!file_exists("$dest/$nomeFileDest")) {
			 		
			 		$fileSource = $i["DOCUMENTNO"] . ".pdf";
			 		$source = Util::getArchivio($i["C_DOCTYPE_ID"], substr($i["VATLEDGERDATE"], 0, 4));
			 		
			 		$this->copyfile("$source/$fileSource", "$dest/$nomeFileDest");
			 		echo "File copiato: $nomeFileDest\n";
			 	}
			 	
			 	if (filesize("$dest/$nomeFileDest")==0)
			 		Util::addError("File [$nomeFileDest] esistente con dimensione 0\n");
			 	
			 	$this->addFileToXml($i, $b, $dest, $nomeFileDest);
			 		
			 }
		}
		
		private function copyfile($source, $dest) {
			
			//if (!file_exists("/tmp/$nomeFileDest")) {
			$cmd = "gs -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -sOutputFile=\"$dest\" \"$source\"";
			//echo "$cmd\n";
			$response = shell_exec($cmd);
			//}
			
			
		    			
		}
		
	}
	
	$c = new Conservazione();
	$c->run();

?>