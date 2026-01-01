<?php
/**
 * 
 * Classe de base représentant un element réseau. peut etre étendu pouravoir des elements réseaux spécialisés
 * @author m.diagne
 *
 */
class NetworkElement {
	/**
	 * 
	 * index non telecom présent dans les fichier sources
	 * @var unknown_type
	 */
	private $indexID;
	/**
	 * 
	 * Identifiant telecom associé
	 * @var unknown_type
	 */
	private $telecomID;
	/**
	 * 
	 * Type de NE (cell, bts,msc,...)
	 * @var unknown_type
	 */
	private $neType;
	/**
	 * 
	 * Le Label si présent
	 * @var unknown_type
	 */
	private $neLabel;
	
	public function __construct($indexID, $telecomID)
	{
		$this->indexID = $indexID;
		$this->telecomID = $telecomID;
	}
	
	//getters
	public function getTelecomId(){
		return $this->telecomID;
	}
	public function getIndexID(){
		return $this->indexID;
	}
	public function getType(){
		return $this->neType;
	}
	public function getLabel(){
		return $this->neLabel;
	}
	
	//setters
	public function setType($neType){
		$this->neType=$neType;
	}
	public function setLabel($neLabel){
		$this->neLabel=$neLabel;
	}
}
/**
 * 
 * Classe à implementer pour parser le fichier de topologie associés aux fichiers sources.
 * @author m.diagne
 *
 */
abstract class TopoParser {


	/**
	 * @var type de ficher à traiter
	 */
	public $fileType;
	/**
	 * tableau de correspondance entre l'identifant de NE présent dans le fichier et l'element réseaux telecom associé
 	 * @var topoFileId
	 */
	private static $topoFileId;
	
	/**
	 * 
	 * Liste des paramètres statiques définis pour chaque famille
	 * @var ParametersList
	 */
	protected $params;
	/**
	 * 
	 * Permet l'accès aux fonctions de requête vers la base de données
	 * @var DatabaseServices
	 */
	private $dbServices;
	/**
	 * fichier de topologie parsé
	 */
	private $topoFlatfile;
	
	/**
	 * booleen à true lorsque le fichier topo a été récuperé depuis les tables archives
	 */
	private  $topoFileFromArchive;

	

	/**
	 * 
	 * Constructeur
	 * @param DatabaseServices $dbServices Objet de gestion des requêtes SQL
	 */
	public function __construct(DatabaseServices $dbServices)
	{
		$this->dbServices = $dbServices;
		Tools::$debug = get_sys_debug('retrieve_load_data');
	}

	/**
	 * 
	 * Renvoie la liste des heures où des fichiers ont été collectés
	 */
	public function getTopoFile() {
		$this->topoFlatfile=$this->dbServices->getTopoFile($this);
		return $this->topoFlatfile;
	}	
	/**
	 * 
	 * Amorce les tâches préalables au parsing des fichiers sources
	 * @param String $hour Heure des fichiers collectés
	 * @param String $day Jour des fichiers collectés
	 * @param String $fileExtension Extension des fichiers sources
	 */
	/*public function processDbInitTasks($hour, $day, $fileExtension) {
		if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcStart("processDbInitTasks");}
		$this->dbServices->generic_create_table_w($hour, $this->params, $fileExtension);
		$hours = $this->dbServices->getFiles($hour, $fileExtension);
		if (Tools::isPerfTraceEnabled()) {Tools::debugTimeExcEnd("processDbInitTasks");}
		return $hours;
	}*/
	
	/**
	 * 
	 * Purge de la table sys_flat_file_uploaded_list pour l'heure $hour
	 * @param String $hour Heure des fichiers collectés
	 */
	public function cleanFlatFileUploadedList($hour) {
		$this->dbServices->clean_flat_file_uploaded_list($hour);
	}
			

	
	/**
	 * 
	 * Parsing du fichier Topo 
	 * @param FlatFile $flat_file Fichier collecté
	 * 
	 */
	abstract protected function parseTopoFile($flat_file);
		
	/**
	 * 
	 * Verification si le fichier topo est valide ou pas. Retourne true si le fichier est valide
	 * @param $flat_file
	 * @return boolean true or false
	 */
	abstract protected function isValidTopoFile($flat_file);
	
	
	/**
	 * 
	 * Retourne la propriété $this->params
	 * @return array Liste d'objets Parameters
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * fonction qui ajoute une correpondance entre l'index de NE présent dans le fichier et l'element telecom associé
	 * @param NetworkElement $networkElement
	 */
	public function addNewNE(NetworkElement $networkElement){
		$ne_id_in_file=$networkElement->getIndexID();
		self::$topoFileId[$ne_id_in_file]=$networkElement;
	}
	
	public function getTopoFileId() {
		return self::$topoFileId;
	}
	
	/**
	 * 
	 * Retourne l'objet NetworkElement à partir de l'id présent dans les fichiers sources
	 * @param NetworkElement $ne_id_in_file
	 */
	public static function  getTelecomId($ne_id_in_file){
		return self::$topoFileId[$ne_id_in_file];
	}
	
	/**
	 * 
	 * test si le fichier de topo a été parsé ou pas
	 */
	public static function isTopoFileParsed(){
		return isset(self::$topoFileId);

	}
	/**
	 * 
	 * Nettoyage des fichiers topos
	 * @param unknown_type $extensions
	 */
	public function clean_topo_files_uploaded(){
		$fileType = $this->fileType;
		$this->dbServices->clean_flat_file_extensions_uploaded_list($fileType);
	}
	

}
?>