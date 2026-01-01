<?php

	//header "standard" pour avoir les constantes qui vont bien ainsi que 
	//l'accès au log d'application
	//include '../../php/environnement_liens.php';
	//include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
	
	require_once(_CLASSPATH_."DataBaseConnection.class.php");
	require_once(_CLASSPATH_."Database.class.php");
	require_once(_CLASSPATH_."../models/DataExportModel.class.php");
	require_once(_CLASSPATH_."../php/edw_function.php");
	require_once(_CLASSPATH_."../api/xpert/class/TaProduct.class.php");
	require_once(_CLASSPATH_."../api/xpert/class/Indicator.class.php");
	require_once(_CLASSPATH_."../api/xpert/class/APIDataExport.class.php");
	require_once(_CLASSPATH_."../api/xpert/class/NetworkElement.class.php");
	require_once(_CLASSPATH_."../class/api/ApiConnection.class.php");
	
	
	/**
  	 * Cette classe sert de biseau
	 * entre TAProduct et le complex type Products 
 	 * représentant le tableau de TAProduct
	 */
	class Products {
		public $list;
	}
	
	/**
	 * Cette classe sert de biseau
	 * entre TAProduct et le complex type Products 
 	 * représentant le tableau d'Indicator 
 	 */
	class Indicators {
		
		public $list;
	}
	
	/**
	 * Cette classe définie un tableau 
	 * de chaîne de caractère.
	 */
	class StringArray{
		public $list;
	}
	
	/**
	 * Cette classe définie un tableau 
	 * d'élément réseau.
	 *
	 */
	class NetworkElements{
		public $list;
	}
	
	/**
	 * Cette classe définie
	 * un tableau de dataexports
	 */
	class APIDataExports {
		public $list;
	}
	
	class XpertApi {
				
		/**
		 * List of error code
		 */
		const eOk = "eOk";
		const eNotConnected = "eNotConnected";
		const eProductIdNotFound = "eProductIdNotFound";
		const eAggregationLevelNotFound = "eAgreggationLevelNotFound";
		const eNotAllowed = "eNotAllowed";
		const eDataExportIdNotFound = "eDataExportIdNotFound";
		const eInvalidKpiOrRaw = "eInvalidKpiOrRaw"; 
		const eInternalError = "eInternalError";	
		
		/**
		 * Le type correspondant au export crée avec l'API 
		 */
		const iExportAPIType = 3;
		
		/**
		 * à true si une session à été ouverte
		 */
		private $m_connection = null;
		
		/**
		 * Vérifie que l'on est bien connecté
		 * @return true si la session est établie, false sinon
		 */
		private function isConnected(){
			if($this->m_connection != null){				
				return $this->m_connection->isConnectionActiv();
			}else{
				return false;
			}
		}
		
		/**
		 * Connection to the API
		 * 
		 * @param $sLogin user login
		 * @param $sPassword user password
		 * @return Error code 
		 */
		public function connection($sLogin,$sPassword){
			Log::getLog()->begin("connection($sLogin,\$sPassword)");
			$errorCode = XpertApi::eNotConnected;
			try{
				
				$this->m_connection = new ApiConnection($sLogin, $sPassword);
				if($this->m_connection->getConnectionState() == true){
					//on vérifie qu'on que le login passé est un login astellia
					if($this->m_connection->getConnectionState() == API_CONNECTION_ASTELLIA_ADMIN){
						$errorCode = XpertApi::eOk;
					}
				}
							
			}catch(Exception $e){
				Log::getLog()->error($e->getMessage());
			}
						
			if($errorCode == XpertApi::eNotConnected){
				$this->m_connection = null;
			}		
			
			Log::getLog()->end();
			
			return $errorCode;
		}
		
		
		/**
		 * Return the products list. 
		 * 
		 * @return TAProduct[], Error code 
		 * 
		 */
		public function getProducts($dummy1){
			Log::getLog()->begin("getProducts()");
					
			$result = new Products;
			$eErrorCode = XpertApi::eNotConnected;
			$result = null;
			if($this->isConnected() == true){
				try{
					$db =  Database::getConnection();
					$productList = $db->getAll('SELECT sdp_id, sdp_label, sdp_ip_address, sdp_directory, sdp_master_topo, sdp_ssh_user, sdp_ssh_password, sdp_ssh_port '.
								'FROM sys_definition_product '.
								'WHERE sdp_on_off = 1 ');
					
					//Log::getLog()->debug(print_r($productList, true));
					
					//$result = array();
					foreach($productList as  $productItem){
						$result->list[] = new TAProduct($productItem['sdp_id'], $productItem['sdp_label'], $productItem['sdp_ip_address'], $productItem['sdp_directory'], $productItem['sdp_master_topo'], $productItem['sdp_ssh_user'], $productItem['sdp_ssh_password'], $productItem['sdp_ssh_port'], 0);										
					}
					
					$eErrorCode = XpertApi::eOk;
					
				}catch(Exception $e){
					Log::getLog()->error($e->getMessage());
				}
			}
		
			Log::getLog()->end();
			return array($result,$eErrorCode);
			
		}
		
		/**
		 * Disconnection of the API
		 */
		public function disconnection($dummy2){
			Log::getLog()->begin("disconnection()");
			$this->m_connection = null;
			Log::getLog()->end();
		}
		
		
		
		/**
		 * Retourne la liste des KPI et des compteurs activé et déployé
		 * 
		 * @param integer $iProductId requested product id, "" current product
		 * return array(Indicators[], eErrorCode) liste des indicateurs, code d'erreur
		 * @param string $sFamily famille pour lesquelles l'indicateur 
		 * @param boolean $bDetailled ajoute la formule et le commentaire
		 * @return Indicator[], eErrorCode
		 */
		public function getKpiAndCounter($iProductId, $sFamily, $bDetailled){
			Log::getLog()->begin("getKpiAndCounter($iProductId, $sFamily, $bDetailled)");
			$eErrorCode = XpertApi::eNotConnected;
			$result = new Indicators();
			
			if($this->isConnected() == true){
				$db =  Database::getConnection($iProductId);

				$detailledField = "";
				if($bDetailled){
					$detailledField = ", sys_definition_kpi.kpi_formula as kpi_formula, sys_definition_kpi.comment as comment ";
				}
				
				$query = "SELECT sys_definition_kpi.id_ligne as id_ligne, sys_definition_kpi.kpi_label as kpi_label, sys_definition_kpi.kpi_name as kpi_name ".$detailledField ;
				$query .= "FROM sys_definition_kpi ";
				$query .= "INNER JOIN sys_definition_group_table ON sys_definition_group_table.edw_group_table=sys_definition_kpi.edw_group_table "; 
				$query .= "WHERE sys_definition_kpi.visible = 1 AND sys_definition_kpi.on_off=1 AND sys_definition_group_table.family='".$sFamily."' ";
				$query .= "ORDER BY kpi_label, kpi_name";
								
				//Log::getLog()->debug($query);
				$kpiList = $db->getAll($query);
				
				
				//$kpiList = get_kpi($sFamily, $iProductId, $bDetailled);
				//Log::getLog()->debug(print_r($kpiList, true));
				foreach($kpiList as $kpi){
					if($bDetailled){
						$result->list[] = new Indicator($kpi["id_ligne"], $kpi["kpi_name"],$kpi["kpi_label"], "kpi", $kpi["kpi_formula"], $kpi["comment"]);
					}else{
						$result->list[] = new Indicator($kpi["id_ligne"], $kpi["kpi_name"],$kpi["kpi_label"], "kpi");
					}
				}
				
				if($bDetailled){
					$detailledField = ",sys_field_reference.edw_agregation_formula as edw_agregation_formula, sys_field_reference.comment as comment ";
				}

				$query = "SELECT sys_field_reference.id_ligne as id_ligne, sys_field_reference.edw_field_name_label as edw_field_name_label, sys_field_reference.edw_field_name as edw_field_name "; 
				$query .= $detailledField;
				$query .= "FROM sys_field_reference ";
				$query .= "INNER JOIN sys_definition_group_table ON sys_definition_group_table.edw_group_table=sys_field_reference.edw_group_table ";
				$query .= "WHERE sys_field_reference.visible = 1 AND (sys_field_reference.on_off=1 OR new_field = 1) AND sys_definition_group_table.family='".$sFamily."' "; 
				$query .= "ORDER BY edw_field_name_label,edw_field_name";
								
				//Log::getLog()->debug($query);
				$rawList = $db->getAll($query);
				
				//$rawList = get_counter($sFamily, $iProductId, $bDetailled);
				//Log::getLog()->debug(print_r($rawList, true));
				foreach($rawList as $raw){
					if($bDetailled){
						$result->list[] = new Indicator($raw["id_ligne"], $raw["edw_field_name_label"], $raw["edw_field_name"], "raw", $raw["edw_agregation_formula"], $raw["comment"]);
					}else{
						$result->list[] = new Indicator($raw["id_ligne"], $raw["edw_field_name_label"], $raw["edw_field_name"], "raw");
					}
				}
				
				$eErrorCode = XpertApi::eOk;
			}
			
			Log::getLog()->end();
			
			return array($result, $eErrorCode);
		}
		
		/**
		 * Retourne la liste des familles dans le produit spécifié
		 * 
		 * @param interger $iProductId
		 * @return Family[], eErrorCode 
		 */
		public function getFamily($iProductId){
			Log::getLog()->begin('getFamily('.$iProductId.')');
			$eErrorCode = XpertApi::eNotConnected;
			$result = new StringArray();
			if($this->isConnected() == true){
				$db = Database::getConnection($iProductId);
				$familyList = $db->getAll('SELECT family FROM sys_definition_categorie');
				foreach($familyList as $family){
					$result->list[] = $family['family'];
				}
				$eErrorCode = XpertApi::eOk;
			}
			Log::getLog()->end();
			return array($result, $eErrorCode);
		}
		
		public function getTimeAggregation($iProductId){
			Log::getLog()->begin('getTimeAggregation('.$iProductId.')');
			$eErrorCode = XpertApi::eNotConnected;
			$result = new StringArray();
			if($this->isConnected() == true){
				$timeAggregations = getTaLabelList($iProductId);
				foreach($timeAggregations as $key => $value){
					$result->list[] = $key;
				}				
				$eErrorCode = XpertApi::eOk;
			}
			Log::getLog()->end();
			return array($result, $eErrorCode);
		}
		
		/**
		 *  Retoune la liste des niveaux d'agrégation réseau
		 * 
		 *  @param integer $iProductId requested product id
		 *  @param string 
		 *  @param string[] $aggregationLevels list of aggregation levels
		 *  @return Array of network elemenet Error Code
		 */
		 public function getNetworkAggregationLevels($iProductId, $sFamily){
			Log::getLog()->begin("getNetworkAggregationLevels($iProductId, $sFamily)");
		 	$eErrorCode = XpertApi::eNotConnected;
			$result = new StringArray();
			if($this->isConnected() == true){
				$db =  Database::getConnection($iProductId);			
				$naList = $db->getAll('SELECT agregation_name FROM sys_definition_network_agregation where family = \''.$sFamily.'\' ORDER BY agregation_rank');
				foreach($naList as $na){
					$result->list[] = $na['agregation_name'];
				}
				$eErrorCode = XpertApi::eOk;
			}
			Log::getLog()->end();
			
			return array($result, $eErrorCode);
			
		}
		
		/**
		 * Return product's network element corresponding to a 
		 * aggregation level.
		 * 
		 * @param[in] integer $iProductId requested product id
		 * @param[in] string $sNetworkAggregation aggregation level requested 
		 * @param[out] NetworkElement[] $networkElements network element list
		 * @return Error Code
		 * 
		 */
		public function getAllNetworkElements($iProductId, $sNetworkAggregation){
			Log::getLog()->begin("getAllNetworkElements(".$iProductId.", ".$sNetworkAggregation.")");
			$eErrorCode = XpertApi::eNotConnected;
			$result = new NetworkElements();
			if($this->isConnected() == true){
				$db =  Database::getConnection($iProductId);
				$query = "SELECT eor_id, eor_label FROM edw_object_ref WHERE eor_obj_type ='".$sNetworkAggregation."'";
				$neList = $db->getAll($query);
				//Log::getLog()->debug($query);
				foreach($neList as $ne){
					$result->list[] = new NetworkElement($ne['eor_id'], $ne['eor_label'], $sNetworkAggregation);
				}
				$eErrorCode = XpertApi::eOk;
			}
			
			Log::getLog()->end();
			return array($result,$eErrorCode);
			
		}
		
		/**
		 * 
		 * Retourne tous les élements enfant en-dessus de l'élément parent spécifié
		 * 
		 * @param integer $iProductId id du produit 
		 * @param string $sParentName code T&A interne du parent concerné
		 * @param string $sParentAggregationLevel niveau d'aggregation du parent
		 * @param string $sChildrenAggregationLevel niveau d'aggregation de l'enfant
		 * @return la liste des elements enfants correspondant, le code d'erreur 
		 */
		public function getChildrenNetworkElements($iProductId, $sParentName, $sParentAggregationLevel, $sChildrenAggregationLevel){
			Log::getLog()->begin("getChildrenNetworkElements(".$iProductId.", ".$sParentName.", ".$sParentAggregationLevel.", ".$sChildrenAggregationLevel.")");
			$eErrorCode = XpertApi::eNotConnected;
			$result = new NetworkElements();
			if($this->isConnected() == true){
				$db =  Database::getConnection($iProductId);
				
				$query = "SELECT eor_id, eor_label FROM edw_object_ref ";
				$query .= "INNER JOIN edw_object_arc ON edw_object_arc.eoa_id = edw_object_ref.eor_id ";
				//On concatene l'arc selon les paramètres passés
				$query .= "WHERE eoa_arc_type = '".$sChildrenAggregationLevel."|s|".$sParentAggregationLevel."' AND eoa_id_parent = ".$sParentName;
							
				$neList = $db->getAll($query);
				//Log::getLog()->debug($query);
				foreach($neList as $ne){
					$result->list[] = new NetworkElement($ne['eor_id'], $ne['eor_label'], $sChildrenAggregationLevel);
				}
				$eErrorCode = XpertApi::eOk;
			}
			Log::getLog()->end();
			return array($result,$eErrorCode);
			
		}
		
		
		/**
		 * Retourne la liste des dataexports correspondantes
		 * 
		 * @param[in] $iProductId
		 * @param[in] $sFamily : famille dont on veut les exports, une chaîne vide 
		 * @param[in] $iType -1 : tous, 0: Export IHM, 1/2: Mix KPi / Corporate, 3 : API Xpert
		 * @return DataXportList, Errorcode
		 * 
		 */
		public function getDataExports($iProductId, $sFamily, $iType){
			Log::getLog()->begin("getDataExports($iProductId, $sFamily, $iType)");
			$eErrorCode = XpertApi::eNotConnected;
			$result = new APIDataExports();
			if($this->isConnected() == true){
				
				//Récupération des exports 
				$db =  Database::getConnection($iProductId);
				$query = "SELECT export_id, export_name, target_dir, target_file, field_separator, time_aggregation, network_aggregation, "; 
				$query .= "generate_hour_on_day, family, select_parents, use_code, use_code_na, add_topo_file, ";
				$query .= "add_raw_kpi_file, export_type ";
				$query .= "FROM sys_export_raw_kpi_config ";
				$query .= "WHERE on_off = 1 ";
				if($sFamily != ''){
					$query .= "AND family = '".$sFamily."' ";				
				}
				if($iType >= 0){
					$query .= "AND export_type = ".$iType;
				}				
				
				$query .= " ORDER BY export_name, time_aggregation";
				
				$exportList = $db->getAll($query);
			
				/**
				 * On parcourt ensuite chaque export pour récupéré la liste des kpi et des compteurs 
				 */
				foreach($exportList as $export){
					
					$indicators = array();
					
					//kpi
					$query = "SELECT id_ligne, kpi_name, kpi_label FROM sys_export_raw_kpi_config
					INNER JOIN sys_export_raw_kpi_data ON sys_export_raw_kpi_data.export_id = sys_export_raw_kpi_config.export_id
					INNER JOIN sys_definition_kpi ON sys_definition_kpi.id_ligne = sys_export_raw_kpi_data.raw_kpi_id
					WHERE raw_kpi_type = 'kpi' AND sys_export_raw_kpi_data.export_id = '".$export['export_id']."'";
					$kpiList = $db->getAll($query);
					foreach($kpiList as $kpi){
						$indicators[] = new Indicator($kpi['id_ligne'], $kpi["kpi_name"],$kpi["kpi_label"], "kpi");
					}
					
					//raw
					$query = "SELECT id_ligne, edw_field_name, edw_field_name_label FROM sys_export_raw_kpi_config
					INNER JOIN sys_export_raw_kpi_data ON sys_export_raw_kpi_data.export_id = sys_export_raw_kpi_config.export_id
					INNER JOIN sys_field_reference ON sys_field_reference.id_ligne = sys_export_raw_kpi_data.raw_kpi_id
					WHERE raw_kpi_type = 'raw' AND sys_export_raw_kpi_data.export_id = '".$export['export_id']."'";
					$rawList = $db->getAll($query);
					foreach($rawList as $raw){
						$indicators[] = new Indicator($raw['id_ligne'], $raw["edw_field_name"],$raw["edw_field_name_label"], "raw");
					}
					
					
					$result->list[] = new APIDataExport($export['export_id'], $export['export_name'], $export['target_dir'],
													 $export['target_file'], $export['field_separator'], $export['network_aggregation'], 
													 $export['time_aggregation'], $export['family'], $export['generate_hour_on_day'],  
													 $export['select_parents'], $export['use_code'], $export['use_code_na'], 
													 $export['add_topo_file'], $export['add_raw_kpi_file'], $export['export_type'], $indicators);
				}
				
				$eErrorCode = XpertApi::eOk;
			}
			
			Log::getLog()->end();
			return array($result, $eErrorCode);
			
		}
		
		/**
		 * Mise à jour d'un ou plusieurs DataExport
		 * 
		 * Uniquement les dataexport stocké seront automatiquement de type 3
		 *
		 * @param[in] integer $iProductId id du produit concerné
		 * @param[in] array $dataExportslist liste des DataExports à créer / mettre à jour
		 * @return Error Code 
		 */
		public function setDataExports($iProductId, $dataExports){
					
			Log::getLog()->begin("setDataExports($iProductId, \$dataExports)");
			$eErrorCode = XpertApi::eNotConnected;
			if($this->isConnected() == true){
				
				try {
					
					foreach($dataExports->list as $dataExport){
						
						
						//création de l'objet de gestion des dataexport
						$exportModel = new DataExportModel($dataExport->m_sExportId, $iProductId);
						
						//si il y une erreur c'est que le dataexport n'existe pas
						if($exportModel->getError() == true){
							
							//On le crée
							ob_start();
							$exportId = DataExportModel::create($dataExport->m_sFamily, $iProductId);			
							//il peut y avoir des soucis SQL pendant cette phase
							
							$exportModel = new DataExportModel($exportId, $iProductId);
							$sError = ob_get_contents();
							if($sError != ''){
								Log::getLog()->error($error);
								throw new Exception(XpertApi::eInternalError);
							}
							ob_end_clean();
							
							if($exportModel->getError() == true){
								Log::getLog()->error("DataExportModel Error");
								throw new Exception(XpertApi::eInternalError);
							}
						}
						
						ob_start();

						Log::getLog()->debug("\$dataExport".print_r($dataExport,true));
						
						$exportModel->setConfig('export_name', $dataExport->m_sExportName);
						$exportModel->setConfig('target_dir', $dataExport->m_sExportDir);
						$exportModel->setConfig('target_file', $dataExport->m_sFileName);
						$exportModel->setConfig('field_separator', $dataExport->m_sFieldSeparator);
						$exportModel->setConfig('time_aggregation', $dataExport->m_sTimeAggregation);						
						$exportModel->setConfig('network_aggregation', $dataExport->m_sNetworkAggregation);
						$exportModel->setConfig('family', $dataExport->m_sFamily);
						$exportModel->setConfig('generate_hour_on_day', $this->convertBoolToInt($dataExport->m_bGenerateHourOnDay));						
						$exportModel->setConfig('select_parents', $this->convertBoolToInt($dataExport->m_bShowNetworkHierarchy));
						$exportModel->setConfig('use_code', $this->convertBoolToInt($dataExport->m_bUseCodeKPInRAW));
						$exportModel->setConfig('use_code_na', $this->convertBoolToInt($dataExport->m_bUseCodeNAAndNE));
						$exportModel->setConfig('add_topo_file', $this->convertBoolToInt($dataExport->m_bAddTopoFile));
						$exportModel->setConfig('export_type', XpertAPI::iExportAPIType);
						
						$sError = ob_get_contents();
						if($sError != ''){
							Log::getLog()->error($error);
							throw new Exception(XpertApi::eInternalError);
						}
						ob_end_clean();
						
						$db =  Database::getConnection($iProductId);

						//On commence par vérifier si l'id passée existe							
						$kpis = array();
						$raws = array();
						
						foreach($dataExport->m_indicators as $indicator){
					
							if($indicator->m_eType == Indicator::eKpi){

								$query = "SELECT id_ligne FROM sys_definition_kpi WHERE id_ligne = '".$indicator->m_sId."'";
								$result = $db->getAll($query);

								if(count($result)>0){
									$kpis[] = $indicator->m_sId;
								}else{
									throw new Exception(XpertApi::eInvalidKpiOrRaw);
								}
							}elseif($indicator->m_eType == Indicator::eRaw){
								
								$query = "SELECT id_ligne FROM sys_field_reference WHERE id_ligne = '".$indicator->m_sId."'";
								$result = $db->getAll($query);

								if(count($result)>0){
									$raws[] = $indicator->m_sId;
								}else{
									throw new Exception(XpertApi::eInvalidKpiOrRaw); 
								}										
							}else{
								throw new Exception(XpertApi::eInvalidKpiOrRaw);
							}
						}
						$exportModel->setKpiList($kpis);
						$exportModel->setRawList($raws);
					}
					
					$eErrorCode = XpertApi::eOk;
					
				} catch(Exception $e){
					$eErrorCode = $e->getMessage();					
				}
			}
			Log::getLog()->end();
			return $eErrorCode;
		}
		
		/**
		 * Converti un booléen en entier.		  
		 * - true : 1
		 * - false : 0
		 * @param $bool true ou false
		 */
		function convertBoolToInt($bool){
			if($bool != ""){
				return 1;
			}else{
				return 0;
			}
		}
	} 

?>