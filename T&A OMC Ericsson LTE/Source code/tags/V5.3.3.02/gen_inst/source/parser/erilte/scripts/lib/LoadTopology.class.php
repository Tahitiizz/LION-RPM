<?php

/**
 * 
 * Classe principale de gestion de la topologie des éléments réseaux
 * @author g.francois
 *
 */
class LoadTopology {
	
	/**
	 * 
	 * Décrit les familles du produit (BSS, GPRS, TRX, ADJ, etc.)
	 * @var ParametersList Liste des paramètres pour chaque famille
	 */
	protected $params;
	/**
	 * 
	 * Liste les chemins absolus / noms des fichiers de topologie
	 * @var array
	 */
	protected $tempTopoDataCsvFilesArray;
	/**
	 * 
	 * Liste les handles des fichiers temporaires de topologie
	 * @var array
	 */
	protected	$tempTopoDataCsvHandlesArray;
	/**
	 * 
	 * Liste des fichiers temporaires contenant les informations sur la topologie du réseau.
	 * Il existe un tableau par famille
	 * @var array
	 */
	private $topo_files_array;
	/**
	 * 
	 * Objet de gestion de la connexion à la base de données
	 * @var DataBaseConnection
	 */
	private $database;
	/**
	 * 
	 * Nom du module relatif au produit OMC
	 * @var String
	 */
	private $parser_name;
	
	/**
	 * 
	 * Constructeur
	 * @param String $parser_name Nom du module du parser
	 * @param DatabaseConnection $db Objet gérant les connexions à la base de données
	 * @param array $params Liste des objets Parameter gérés par le produit
	 */
	public function __construct($parser_name, DataBaseConnection $db,ParametersList $params) {
		$this->database = $db;
		$this->parser_name = $parser_name;
		$this->params = $params;
	}
	
	/**
	 * 
	 * Initialise la liste des fichiers temporaires contenant les informations de la topologie
	 * @param array $topo_files_array Liste des noms des fichiers temporaires de topologie
	 */
	protected function setTopoFiles($topo_files_array) {
		$this->topo_files_array = $topo_files_array;
	}
	

	
	/**
	 * Operation fopen sur les fichiers temporaires de topo.
	 */
	public function openCsvHandles($thirdAxis=false) {

		if(!$thirdAxis){
			// ouverture des fichiers CSV temporaires "topo" à renseigner
			$this->tempTopoDataCsvFilesArray = array();
			$this->tempTopoDataCsvHandlesArray = array();
			// pour chaque famille
			foreach($this->params as $param) {
				$topoFileName = "temp_topo_" . $param->family . "_" . uniqid("") . ".topo";
	        	$topoFile =  REP_PHYSIQUE_NIVEAU_0."upload/".$topoFileName;
	        	$this->tempTopoDataCsvFilesArray[$param->family] = $topoFileName;
				$tempTopoDataCsvHandle = fopen($topoFile, 'w');
				$this->tempTopoDataCsvHandlesArray[$param->family] = $tempTopoDataCsvHandle;
			}
		}else{
			// ouverture des fichiers CSV temporaires "topo" à renseigner
			$this->tempTopo3rdAxisDataCsvFilesArray = array();
			$this->tempTopo3rdAxisDataCsvHandlesArray = array();
			// pour chaque famille
			foreach($this->params as $param) {
				$topoFileName = "temp_topo3rdAxis_" . $param->family . "_" . uniqid("") . ".topo";
	        	$topoFile =  REP_PHYSIQUE_NIVEAU_0."upload/".$topoFileName;
	        	$this->tempTopo3rdAxisDataCsvFilesArray[$param->family] = $topoFileName;
				$tempTopoDataCsvHandle = fopen($topoFile, 'w');
				$this->tempTopo3rdAxisDataCsvHandlesArray[$param->family] = $tempTopoDataCsvHandle;
			}
		}
	}


	/**
	 * Fonction qui charge les fichiers de topo crees en utilisant les classes de Topology.
	 */
	public function load_files_topo() {
		$topo = new Topology();		
		$topo->setDbConnection($this->database->getCnx());
		$topo->setRepNiveau0(REP_PHYSIQUE_NIVEAU_0);		
		$topo->setDelimiter(';');	
		/*
		 *  Le mode sert uniquement a afficher le mode de l'upload dans le demon
		 * (et aussi en mode debugge pour remplir la table sys_topology_trace)
		 * le nom du mode peut-être change
		 */
		$topo->setMode('auto');
		/*
		 * Type de mise a jour 
		 * 1 = mise a jour des tables edw_object_X_ref qui n'ont pas de troisième axe
		 * Les autres types de mise a jour correspond aux uploads :  manuel/auto/maj familles secondaires
		 */
		$topo->setTypeMaj(1);
		// definition du produit a partir du nom du module ("HUABSS")
		$topo->setProduct($this->parser_name);		
		/*
		 * 26-11-2007 SCT : verification de la presence d'un fichier de reference
		 * Dans le cas de l'analyse des Resellers, le fichier de reference n'est pas present. La topo ne sera pas mise a jour
		 *
		 */
		$topo->nombre_fichier_reference = 0;
		if (isset($this->topo_files_array) && count($this->topo_files_array) > 0) {
			$topo->nombre_fichier_reference = count($this->topo_files_array);
		}
		// maj 16/12/2008 - MPR : Chargement des fichiers
		if(is_array($this->topo_files_array))
		foreach($this->topo_files_array AS $file) {
			displayInDemon('Chargement du fichier : '.$file.'<br>'."\n");
			// maj  11/06/2009 MPR : Correction du bug 9884
			$topo->unsetQueries();			
			$topo->setFile($file);
			$topo->load();//chargement de la topologie
			$topo->tracelogErrors();					
		}
		// suppression des fichiers temporaires
		unset($this->topo_files_array);
		$topo->__destruct();
	}
	

	/**
	 * 
	 * Ecrit les données dans les fichiers temporaires de topologie
	 * @param $topologyArray array Tableau définissant, par famille, les relations entre les differents NE. $ne_base_level_id => $topoInfo
	 */
	public function createFileTopo($topologyArray,$thirdAxis=false) {
		$topo_files_array = array();
		$topo_update_label=get_sys_global_parameters("topo_update_label");
		foreach($this->params as $param) {
			if(!$thirdAxis){
				$tempCsvHandle = $this->tempTopoDataCsvHandlesArray[$param->family];
				$topoFileName = $this->tempTopoDataCsvFilesArray[$param->family];
			}else{
				$tempCsvHandle = $this->tempTopo3rdAxisDataCsvHandlesArray[$param->family];
				$topoFileName = $this->tempTopo3rdAxisDataCsvFilesArray[$param->family];
			}
			$topoCellsArray = $topologyArray[$param->family];
			$csvLineAll="";
			//si fichier vide pour la famille, on zappe
			if(empty($topoCellsArray))continue;
			
			//update topology disable label
			if($topo_update_label==0){
				$topoHeader_tmp=array();
				if(!$thirdAxis){
					$topoHeader=explode(";", trim($param->topoHeader,"\n"));
				}else{
					$topoHeader=explode(";", trim($param->topoHeader3rdAxis,"\n"));	
				}
				
				
				foreach ($topoHeader as $header) {
					if(preg_match("/label/i", $header)) continue;
					$topoHeader_tmp[]=$header;
				}
				$topoHeader=$topoHeader_tmp;
			}
			// sinon (1 ou NULL)
			else{
				if(!$thirdAxis){
					$topoHeader=explode(";", trim($param->topoHeader,"\n"));
				}else{
					$topoHeader=explode(";", trim($param->topoHeader3rdAxis,"\n"));	
				}
				
			}
			
			$topoHeaderText=implode(";", $topoHeader)."\n";
			//pour chaque information de topologie
			foreach($topoCellsArray AS $cellId => $topoInfo) {
				
				$csvLine="";
				//meme ordre que topoHeader
				foreach ($topoHeader as $header) {
					$csvLine.=$topoInfo[$header].";";
				}
				//suppression du dernier ';'
				$csvLine=trim($csvLine,";");//substr($csvLine, 0, strlen($csvLine)-1);
				$csvLine .= "\n";
				$csvLineAll.=$csvLine;
			}
			// l'entête doit reprendre les noms des colonnes obtenus lorsqu'on utilise le meneu "Dowload topology" de l'application.
			fwrite($tempCsvHandle, $topoHeaderText);
			fwrite($tempCsvHandle, $csvLineAll);
			fclose($tempCsvHandle);
			// notre nouveau fichier de topo
        	$topo_files_array[] = $topoFileName;
		}
		unset($topologyArray);
		$this->setTopoFiles($topo_files_array);
	}
}

?>