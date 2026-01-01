<?php

include_once(dirname(__FILE__)."/../../../../php/environnement_liens.php");

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));

include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
include_once(dirname(__FILE__)."/../Configuration.class.php");

//=============================================================================================
//	Script de gestion des statistiques optionnelles pour les produits BSS, UTRAN et NSS X
//	Version 1.0
//	Auteur : mhubert
//	30/01/2013 
// maj 03/05/2013 ajout de la famille rnc, correction de la durée d'historique horaire
//======================================================================================

new OptionalStat($module);


class OptionalStat{

	//version du script
	const VERSION="1.1";
	
	/**
	*
	* Objet de gestion de la connexion à la base de données
	* @var DataBaseConnection
	*/
	protected $dbConnection;
	
	/**
	 * 
	 * paramètres passés au script
	 */
	protected $options;
	
	/**
	 * 
	 * Objet ParametersList définissant les familles
	 */
	protected $params;
	
	/**
	 * 
	 * nom du module
	 */
	protected $module;
	
	/**
	 * 
	 * type de techno du produit
	 */
	protected $productType;
	
	/**
	 * 
	 * Constructeur
	 * @param string $module
	 */
	public function __construct($module){
		
		$this->init();
		
		$this->module=$module;
		
		$this->getProductType($this->module);
			
		$this->parseOptions();
	}
	
	/**
	 * 
	 * Initialise les objets nécessaires
	 */
	protected function init(){
		
		//error_reporting(E_ALL);
		set_time_limit(0);
		echo('get option \n');
		//récupération des paramètres passés
		$this->options=getopt("hle:d:");
		echo('get dbConnection \n');
		$this->dbConnection = new DatabaseConnection();
		echo('get Configuration \n');
		$conf = new Configuration();
		$this->params = $conf->getParametersList();
		
	}

	
	/**
	 * 
	 * Affiche l'entête du script
	 */ 
	protected function header(){
		
		if($this->isStat($this->productType)){
			$query = "SELECT value FROM sys_global_parameters WHERE parameters IN ('product_version', 'product_name') order by parameters;";
			$res=$this->dbConnection->executeQuery($query);
			$values = $this->dbConnection->getQueryResults($res);
			$lErreur = $this->dbConnection->getLastError();
			if($lErreur != ''){
				echo $lErreur." on ".$query."\n";
			}
			else{
				$product_name=$values[0]["value"];
				$product_version=$values[1]["value"];
			}
			
			echo "Optional statistics management ".self::VERSION." for {$product_name} V{$product_version}\n";
			
			//var_dump($this->options);
		}
		else{
			echo "There's no optional statistics for this product.\n";
			exit;
		}
	}
	
	/**
	 * 
	 * Gère l'option passée en paramètre au script
	 */ 
	protected function parseOptions(){
		// h help, l list, e enable <family>, d disable <family>
		if(count($this->options)>1 || empty($this->options)){
			echo "Error in parameters\n";
			$this->getHelp();
			exit;
		}
		if(array_key_exists("h", $this->options)){
			$this->getHelp();
			exit;
		}
		elseif(array_key_exists("l", $this->options) && $this->isStat($this->productType)){
			$this->header();
			$families=$this->getOptFamilies();
			$this->displayFamilies($families);
			exit;
		}
		elseif(array_key_exists("l", $this->options) && $this->productType==null)
			echo "No optional families for this product.\n";
		elseif(array_key_exists("e", $this->options) && $this->isStat($this->productType)){
			$this->header();
			//check if family exists before enabling
			if($this->familyExists($this->options["e"])){
				$this->enableStat($this->options["e"]);
			}
			else{
				echo "The family you passed as parameter doesn't exist!\n";
			}
			exit;
		}
		elseif(array_key_exists("d", $this->options) && $this->isStat($this->productType)){
			$this->header();
			if($this->familyExists($this->options["d"])){
				$this->disableStat($this->options["d"]);
			}
			else{
				echo "The family you passed as parameter doesn't exist!\n";
			}
			exit;	
		}	
	}
	
	/**
	 * 
	 * Affiche l'aide
	 */ 
	protected function getHelp(){
		$help="Usage : php -q optional_statistics.php\n";
		$help.="	-l list optional families\n";
		$help.="	-h display help\n";
		$help.="	-e \"family\" activate parsing for specified optional family\n";
		$help.="	-d \"family\" deactivate parsing for specified optional family\n";
		echo $help;
	}
	
	/**
	 * 
	 * Récupère les familles / niveau optionnels
	 */ 
	protected function getOptFamilies(){
		$optFamilies=array();
		//cas BSS ou UTRAN
		if($this->productType=="bssran"){
			foreach($this->params as $param){
				if($param->network[0]!="cell")$optFamilies[$param->family]=$this->getFamilyActivation($param->family);	
			}
		}
		//cas NSS X
		elseif($this->productType=="nssx"){
			$optFamilies["all_families"]=$this->getFamilyActivation("all_families");
		}	
		return $optFamilies;
	}
	
	/**
	 * 
	 * Renvoie l'état d'activation d'une famille optionnelle
	 * @param string $family la famille désirée
	 * @return boolean vrai si la famille optionnelle est activée 
	 */ 
	protected function getFamilyActivation($family){
		if($this->productType=="nssx"){
			//récupérer valeur du paramètre extended_topology
			$extended_topology=get_sys_global_parameters("extended_topology");
			return $extended_topology==1;
		}
		elseif($this->productType=="bssran"){
			switch($family){
				case "bssadj":
				case "adj":
					$stat=(get_sys_global_parameters("specif_enable_adjacencies")==1);
					break;
				case "bsstrx":
					$stat=(get_sys_global_parameters("specif_enable_trx")==1);
					break;
				case "iurl":
					$stat=(get_sys_global_parameters("specif_enable_iurlink")==1);
					break;
				case "iubl":
					$stat=(get_sys_global_parameters("specif_enable_iublink")==1);
					break;
				case "lac":
					$stat=(get_sys_global_parameters("specif_enable_lac")==1);
					break;
				case "rac":
					$stat=(get_sys_global_parameters("specif_enable_rac")==1);
					break;
				case "rnc":
						$stat=(get_sys_global_parameters("specif_enable_rnc")==1);
						break;
				case "nodeb":
						$stat=(get_sys_global_parameters("specif_enable_nodeb")==1);
						break;
				default:
					$stat=false;
					break;	
			}
			return $stat;
		}
	}

	/**
	 * 
	 * Affiche l'état d'activation des familles
	 * @param array $families
	 */ 
	protected function displayFamilies($families){
		if(!empty($families)){
			echo "Optional families list :\n";
			foreach($families as $family=>$stat){
				echo " level \"$family\" is ".($stat?"enabled":"disabled")."\n";	
			}
		}
		else{
			echo "No optional families!\n";
		}
	}
	
	/**
	 * 
	 * Récupère le type du produit bss utran ou nss extended
	 * @param string $module ex : huaran
	 * @return string le type du produit
	 */ 
	protected function getProductType($module){
		if(preg_match("/.*bss|ran.*/",$module,$matches)){
			$this->productType="bssran";
		}
		elseif(preg_match("/.*nssx.*/i",$module,$matches)){
			$this->productType="nssx";
		}
		else{
			$this->productType=null;
		}
	}
	
	/**
	 * 
	 * Renvoie vrai si il s'agit d'un produit comportant des stats optionnelles
	 * @param string $productType
	 */
	protected function isStat($productType){
		return ($productType=="bssran" || $productType=="nssx");
	}
	
	/**
	 * 
	 * Active une stat optionnelle
	 * @param string $stat
	 */
	protected function enableStat($stat){
	if($this->productType=="nssx"){
			//maj de la valeur du paramètre extended_topology
			$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('extended_topology');";
			$res=$this->dbConnection->executeQuery($query);
			echo "!!!!! Warning: don’t forget to update the list of PM files to parse in Extended Mode, on administration Web interface, under \"Setup > Setup Global Parameters > Global Parameters > Extended Topology File List  !!!!!\n";
			displayInDemon("Optional statistics: the extended topology is enabled.","normal",true);
			sys_log_ast("Info", get_sys_global_parameters("system_name") , "Optional Statistics", "Optional statistics: the extended topology is enabled.", "support_1", "");
			$families=$this->getOptFamilies();
			$this->displayFamilies($families);
		}
		elseif($this->productType=="bssran"){
			switch($stat){
				case "bssadj":
				case "adj":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_adjacencies');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "bsstrx":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_trx');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "iurl":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_iurlink');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "iubl":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_iublink');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "lac":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_lac');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "rac":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_rac');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "rnc":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_rnc');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "nodeb":
					$query = "UPDATE sys_global_parameters SET value=1 WHERE parameters IN ('specif_enable_nodeb');";
					$res=$this->dbConnection->executeQuery($query);
					break;
			}
			$this->updateFamilyHistory($stat,"hour",5);
			displayInDemon("Optional statistics: the family $stat is enabled.","normal",true);
			sys_log_ast("Info", get_sys_global_parameters("system_name") , "Optional Statistics", "Optional statistics: the family $stat is enabled.", "support_1", "");
			$families=$this->getOptFamilies();
			$this->displayFamilies($families);
		}
	}
	
	/**
	*
	* Désactive une stat optionnelle
	*@param string $stat
	*/
	protected function disableStat($stat){
		if($this->productType=="nssx"){
			//maj de la valeur du paramètre extended_topology
			$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('extended_topology');";
			$res=$this->dbConnection->executeQuery($query);
			displayInDemon("Optional statistics: the extended topology is disabled.","normal",true);
			sys_log_ast("Info", get_sys_global_parameters("system_name") , "Optional Statistics", "Optional statistics: the extended topology is disabled.", "support_1", "");
			$families=$this->getOptFamilies();
			$this->displayFamilies($families);
		}
		elseif($this->productType=="bssran"){
			switch($stat){
				case "bssadj":
				case "adj":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_adjacencies');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "bsstrx":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_trx');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "iurl":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_iurlink');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "iubl":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_iublink');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "lac":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_lac');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "rac":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_rac');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "rnc":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_rnc');";
					$res=$this->dbConnection->executeQuery($query);
					break;
				case "nodeb":
					$query = "UPDATE sys_global_parameters SET value=0 WHERE parameters IN ('specif_enable_nodeb');";
					$res=$this->dbConnection->executeQuery($query);
					break;
			}
			$this->removeFamilyHistory($stat,"hour",5);
			displayInDemon("Optional statistics: the family $stat is disabled.","normal",true);
			sys_log_ast("Info", get_sys_global_parameters("system_name") , "Optional Statistics", "Optional statistics: the family $stat is disabled.", "support_1", "");
			$families=$this->getOptFamilies();
			$this->displayFamilies($families);
		}
	}
	
	/**
	 * 
	 * Mets à jour l'historique pour la famille données dans la table sys_definition_history
	 * @param string $family famille considérée
	 * @param string $ta niveau temps
	 * @param int $value valeur numérique
	 */
	protected function updateFamilyHistory($family,$ta,$value){
		$query = "SELECT family,ta,duration FROM sys_definition_history WHERE family LIKE '$family' AND ta LIKE '$ta';";
		$res=$this->dbConnection->executeQuery($query);
		$values = $this->dbConnection->getQueryResults($res);
		//ligne non présente en base, l'ajouter
		if(empty($values)){
			$query = "INSERT INTO sys_definition_history (family,ta,duration) VALUES ('$family','$ta','$value');";
			$res=$this->dbConnection->executeQuery($query);
		}
		//ligne déjà présente, l'updater
		else{
			$query = "UPDATE sys_definition_history SET duration=$value WHERE family LIKE '$family' AND ta LIKE '$ta';";
			$res=$this->dbConnection->executeQuery($query);
		}
	}
	
	/**
	 * 
	 * Retire l'historique horaire pour la famille spécifiée
	 * @param string $family
	 * @param string $ta
	 * @param int $value
	 */
	protected function removeFamilyHistory($family,$ta,$value){
		$query = "DELETE FROM sys_definition_history WHERE family LIKE '$family' AND ta LIKE '$ta' AND duration=$value;";
		$res=$this->dbConnection->executeQuery($query);
	}
	
	/**
	 * 
	 * Vérifie l'existence d'une famille pour le produit concerné
	 * @param string $family
	 * @return boolean vrai si existe
	 */
	protected function familyExists($family){
		if($this->productType=="nssx"){
			return $family=="all_families";
		}
		elseif($this->productType=="bssran"){
			foreach($this->params as $param){
				if($param->family==$family)return true;
			}
			return false;
		}
	}
}
?>