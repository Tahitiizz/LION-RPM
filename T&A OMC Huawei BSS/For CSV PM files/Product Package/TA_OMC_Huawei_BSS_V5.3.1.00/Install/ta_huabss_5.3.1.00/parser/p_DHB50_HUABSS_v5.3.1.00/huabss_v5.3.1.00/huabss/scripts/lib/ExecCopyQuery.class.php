<?php

/**
 * 
 * Classe chargée, via une requête COPY, de charger le contenu de fichiers temporaires (.sql)
 * dans des tables temporaires de la BDD. 
 *
 */

class ExecCopyQuery {
	/**
	 * 
	 * Propriétés des familles
	 * @var ParametersList
	 */
	public $params;
	
	/**
	 * 
	 * Fichiers temporaires à traiter
	 */
	public $tempFilesCondition;

	
	/**
	*
	* Objet de gestion de la connexion à la base de données
	* @var DataBaseConnection
	*/
	protected $dbConnection;
	
	/**
	*
	* Permet l'accès aux fonctions de requête vers la base de données
	* @var DatabaseServices
	*/
	public $dbServices;
	
	/**
	 * Constructeur.
	 */
	public function __construct(TempFilesCondition $tempFilesCondition)
	{
		// fichiers temporaires à traiter
		$this->tempFilesCondition=$tempFilesCondition;
		
		//récupération des propriétés des familles
		$this->readSerializedParam();
		
		$this->dbConnection=new DatabaseConnection();
		
		//objet DataBaseServices specifique ou générique selon getDatabaseServicesClassName()
		$this->dbServices = $this->getDatabaseServicesObject();
		
	}

	/**
	*
	* Méthode statique qui retourne la liste des entités utiles 
	* (= celles pour lesquelles des compteurs sont activés, Cf. \lib\Parser.class.php->initiateParam()).
	* Cela correspond aux fichiers temporaires en attente de copie vers les tables w_astellia.
	* @param ParametersList $paramsList objet avec les paramètres des familles
	* @param array $hours tableau des heures collectées
	* @return array $tabTempFiles
	*/
	public static function getConditions(ParametersList $paramsList,$hours=null) {
		// initialisation du résultat		
		$conditionsTab=array();
		
		// tableau temporaire listant les entités
		$tabTempFiles=array();
		
		// pour chaque famille
		foreach($paramsList AS $param) {
			// pour chaque entité de la famille courante
			foreach($param->todo as $entity=>$counters){
				//TODO MHT2 checker si au moins un fichier temporaire existe pour une des heures collectées, le niveau et le todo	
				if(Tools::tempFileExistsForEntity($param->network[0],$entity,$hours)){
					//si aucun fichier n'est trouvé pour au moins une des heures collectées, ne pas ajouter l'entité
					$tabTempFiles[]=$entity;
				}
			}
		}
		
		// dédoublonnage pour le cas où un même nom d'entité est utilisé dans plusieurs familles 
		//TODO MHT2 n'est pas possible car l'entité est composée de la famille -> inutile
		//$tabTempFiles=array_unique($tabTempFiles);
		
		// pour chaque entité, on crée un objet condition
		foreach($tabTempFiles as $entity){
			// TODO : à tester si le fichier temporaire existe :
			//   - son nom est obtenu avec  Tools::getCopyFilePath($level, $cle_entite,$hour);
			//   - le test d'existence du fichier est fait tardivement dans DatabaseServices->clean_copy_files
			//   - cela éviterait de créer des processus inutiles (10% des process sur Ericsson BSS)
			//   - cela éviterait les créations de tables temporaires, les messages « Le fichier pour le COPY n'est pas present », etc
			
			// condition d’éligibilité des fichiers temporaires
			$condition=new TempFilesCondition($entity,$hours);
			$conditionsTab[]=$condition;
		}
		unset($tabTempFiles);
		
		// log dans le file_demon
		displayIndemon("ExecCopyQuery : ".count($conditionsTab)." conditions ont été créées suite à une déclinaison par entité.");
		
		return $conditionsTab;
	}
	
	/**
	*
	* Initialise des propriétés d'objet ParameterList
	* @param array $family2Param Tableau associatif pour les paramètres field, specific_field, group_table et network
	*
	*/
	protected function readSerializedParam() {
	
		$filename=REP_PHYSIQUE_NIVEAU_0 . "parser/paramsSerialized.ser";
		// Si le fichier existe et on l'utilise (gain en perf!).
		if(file_exists($filename)){
			$paramsSerialized="";
			$handle=fopen($filename,'rt');
			if ($handle) {
			//verrou en lecture partagée (bloque jusqu'à libération du verrou exlusif s'il existe)
				flock($handle, LOCK_SH);
			while (!feof($handle)) {
			$paramsSerialized .= fgets($handle);
			}
			flock($handle, LOCK_UN);//dévérouillage
			fclose($handle);
			}else{
			$message="Error: Unable to open parameter file ($filename)";
						sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
						displayInDemon($message,'alert');
					}			
			$this->params=unserialize($paramsSerialized);
			if(($this->params==false)||($this->params=='')){
			$message="Error: Unable to unserialize parameter file ($filename)";
						sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
						displayInDemon($message,'alert');
					}
		}
		//sinon le fichier n'existe pas
		else{
			$message="Error: file $filename not found";
			sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
			displayInDemon($message,'alert');
		}
	}
	
	
	/**
	*
	* Retourne un objet DatabaseServices, éventuellement à partir d'une classe
	* fille définie côté spécifique (Cf. getDatabaseServicesClassName ci-après)
	*/
	private function getDatabaseServicesObject(){
		$databaseServicesClassName=$this->getDatabaseServicesClassName();
		try {
			$dbServicesClass = new ReflectionClass($databaseServicesClassName);
			$databaseServicesObject = $dbServicesClass->newInstance($this->dbConnection);
			return $databaseServicesObject;
		} catch (ReflectionException $ex) {
			displayInDemon("Erreur au lancement du traitement ExecCopyQuery : " . $ex->getMessage());
			return NULL;
		}
	}
	
	
	/**
	 *
	 * Côté spécifique : à redénir si besoin.
	 */
	protected function getDatabaseServicesClassName(){
		return "DatabaseServices";
	}
	
}

?>