<?php

include_once(REP_PHYSIQUE_NIVEAU_0 . "class/create_temp_table_generic.class.php");

abstract class CreateTempTable extends CreateTempTableCB {
	/**
	 * 
	 * Définition des propriétés des familles
	 * @var ParametersList
	 */
	protected $params;
	
	/**
	 * 
	 * Connexion à la base de données
	 * @var DataBaseConnection
	 */
	protected $database;
	
	/**
	 * Constructeur.
	 * Le constructeur parent fait appel aux fonctions génériques du Composant de Base.
	 */
	public function __construct($tempTableCondition,$single_process_mode=TRUE)
	{
		parent::__construct($tempTableCondition,$single_process_mode);		
	}

	
	/**
	 * 
	 * Fonction qui défini sous forme de tableau pour chaque group de table les jointures à affectuer entre les tables contenant les données des fichiers sources d'une facon dynamqiue
	 * Pb on ne doit pas faire de jointure si on trouve qu'une table en entrée
	 * @param $param objet de type Parameter associé à une famille
	 * 
	 *  Exemple de Ericsson BSS :
	 *      - $this->jointure : cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real
	 *      - $this->specific_fields : cell,hour,day,week,month
	 */
	protected function setJoinDynamic($param){
		$level=$param->network[0];
		
		// recherche des tables temporaires w_astellia (1 table par entité)
		$query = "
		  SELECT hour,
		         count(*) AS nb
		    FROM sys_w_tables_list
		   WHERE group_table='$param->id_group_table'
		     AND network='$level'
		GROUP BY hour";
		$values = $this->database->getRow($query);
		
		if($values["nb"]==1)
		{
			// on ne fait pas de jointure s'il n'y a qu'une table en entrée
			// devrait être géré par le CB...
			displayInDemon(__METHOD__." : une seule table en entree, jointure non necessaire.");
			$this->jointure[$level] = "";
		}else{
			// 2 tableaux (= paramétrage de la jointure) que le CB va utiliser
			$this->jointure[$level] 		= "(".$param->specific_field.")";
			if(preg_match("/(.*),capture_duration,/", $param->specific_field,$match))
				$jointure=$match[1];
			$this->specific_fields[$level] 	= $jointure;
			
			// logs dans le file_demon
			displayInDemon(__METHOD__." : jointure puis specific_fields :");
			// ex : cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real
			var_dump($this->jointure);
			
			// ex :  cell,hour,day,week,month
			var_dump($this->specific_fields);
		}
	}

    
	/**
	 * Fonction qui va ajouter le vendor dans les tables de topologie
	 * s'il n'y existe pas déjà.
	 * Appellée par ./class/create_temp_table_generic.class.php 
	 * @param int $day jour traité
	 */
	public function MAJ_objectref_specific($day=""){
		//On teste si le vendor a été créé en topo
		$vendorName = get_sys_global_parameters('vendor_name');
		//on insère le vendor que s'il n'existe pas déjà (insensible à la casse de l'eor_id du vendor) ajout where not exists sinon erreur en multi process
		$query_vendor_insert  = "BEGIN WORK;
								LOCK TABLE edw_object_ref IN EXCLUSIVE MODE;
								INSERT INTO edw_object_ref (eor_date, eor_blacklisted, eor_on_off, eor_obj_type, eor_id, eor_label, eor_id_codeq) SELECT TO_CHAR(NOW(), 'YYYYMMDD'), 0, 1, 'vendor', '$vendorName', '$vendorName', NULL
								WHERE NOT EXISTS (SELECT 1 FROM edw_object_ref WHERE eor_obj_type = 'vendor' AND eor_id ILIKE '$vendorName')
								RETURNING edw_object_ref.oid
								;";
		$this->execRequeteAvecErreur($query_vendor_insert);
		$res=$this->database->getNumRows();
		$this->execRequeteAvecErreur("COMMIT WORK;");
	
		if($res==0){
			displayInDemon(__METHOD__." :: vendor '$vendorName' deja present dans edw_object_ref");
		}	
		else{ 
			displayInDemon(__METHOD__." :: vendor '$vendorName' ajoute a edw_object_ref");
		}
		return $vendorName;
	}
	
	/**
	 * 
	 * Création des arcs entre vendor et pcu / bsc pour les produits BSS
	 * @param String $vendorName
	 */
	protected function createBssArc($vendorName){
		//AJOUT DES ARCS MANQUANTS
		foreach(array('bsc','pcu') as $na){
			$query_arcs = "BEGIN WORK;
			LOCK TABLE edw_object_arc_ref, edw_object_ref IN EXCLUSIVE MODE;
			INSERT INTO edw_object_arc_ref
				(select eor_id, '".$vendorName."', '$na|s|vendor'
				FROM edw_object_ref r
				LEFT OUTER JOIN edw_object_arc_ref a ON r.eor_id = a.eoar_id AND a.eoar_arc_type = '$na|s|vendor'
				WHERE r.eor_obj_type = '$na' AND eoar_id IS NULL)
			;COMMIT WORK;";
			$this->execRequeteAvecErreur($query_arcs);
		}
	}
	
	/**
	 * 
	 * Création des arcs entre vendor et apgwpour la famille APS
	 * @param String $vendorName
	 */
	protected function createWimArc($vendorName){
		//AJOUT DES ARCS MANQUANTS
		foreach(array('apbs') as $na){
			$query_arcs = "BEGIN WORK;
			LOCK TABLE edw_object_arc_ref, edw_object_ref IN EXCLUSIVE MODE;
			INSERT INTO edw_object_arc_ref
				(select eor_id, '".$vendorName."', '$na|s|vendor'
				FROM edw_object_ref r
				LEFT OUTER JOIN edw_object_arc_ref a ON r.eor_id = a.eoar_id AND a.eoar_arc_type = '$na|s|vendor'
				WHERE r.eor_obj_type = '$na' AND eoar_id IS NULL)
			;COMMIT WORK;";
			$this->execRequeteAvecErreur($query_arcs);
		}
	}
	
	/**
	 * 
	 * Création des arcs entre vendor / network et rnc pour la famille UTRAN
	 * @param String $vendorName
	 */
	protected function createUtranArc($vendorName){
		//AJOUT DES ARCS MANQUANTS
		foreach(array('rnc') as $na){
			$query_arcs	= "BEGIN WORK;
			LOCK TABLE edw_object_arc_ref, edw_object_ref IN EXCLUSIVE MODE;
			INSERT INTO edw_object_arc_ref 
				(select eor_id, '".$vendorName."', '$na|s|vendor' 
				FROM edw_object_ref r 
				LEFT OUTER JOIN edw_object_arc_ref a ON r.eor_id = a.eoar_id  AND a.eoar_arc_type = '$na|s|vendor'
				WHERE r.eor_obj_type = '$na' AND eoar_id IS NULL)
			;COMMIT WORK;";
			$this->execRequeteAvecErreur($query_arcs);
		}
	}
	
	/**
	*
	* Création des arcs entre vendor et apgwpour la famille LTE
	* @param String $vendorName
	*/
	protected function createLteArc($vendorName){
		//AJOUT DES ARCS MANQUANTS
		foreach(array('enodeb') as $na){
			$query_arcs = "BEGIN WORK;
			LOCK TABLE edw_object_arc_ref, edw_object_ref IN EXCLUSIVE MODE;
			INSERT INTO edw_object_arc_ref
						(select eor_id, '".$vendorName."', '$na|s|vendor'
						FROM edw_object_ref r
						LEFT OUTER JOIN edw_object_arc_ref a ON r.eor_id = a.eoar_id AND a.eoar_arc_type = '$na|s|vendor'
						WHERE r.eor_obj_type = '$na' AND eoar_id IS NULL)
			;COMMIT WORK;";
			$this->execRequeteAvecErreur($query_arcs);
		}
	}
	
	/**
	 * 
	 * UPDATE sur la topo pour que les sourcecell label des ADJ soient mis à jour
	 * à partir des cell label
	 * @param String $vendorName
	 */
	protected function createAdjArc(){
		//  LOCK TABLE : attend si nécessaire que tout verrou conflictuel soit relâché, 
		// sauf si NOWAIT est spécifié.
		// Une fois obtenu, le verrou est conservé jusqu'à la fin de la transaction en cours 
		// (il n'y a pas de commande UNLOCK TABLE : les verrous sont systématiquement 
		// relâchés à la fin de la transaction.) 
		$query_label = "BEGIN WORK;
			LOCK TABLE edw_object_ref IN EXCLUSIVE MODE;
			update edw_object_ref dest
	       set eor_label=r2.eor_label
	       from edw_object_ref as r1 INNER JOIN edw_object_ref as r2 ON r1.eor_id=r2.eor_id
	       where dest.eor_obj_type='sourcecell'
	       and r2.eor_obj_type='cell'
	       and dest.eor_id=r2.eor_id
		;COMMIT WORK;";
	   	$this->execRequeteAvecErreur($query_label);
	}
	
	/**
	 * 
	 * Exécute les requêtes SQL avec gestion des erreurs
	 * @param String $sql
	 */
	private function execRequeteAvecErreur($sql){
    	$res = $this->database->execute($sql);
		if(($lErreur = $this->database->getLastError())!=''){
			displayInDemon($lErreur.'<br>'.$sql.';<br>'."\n", 'alert');
			return false;
		}else{
			if(Tools::$debug)	displayInDemon($sql."<br>\n");
			return $res;
		}
    }
    
    /**
	 * Fonction qui renseigne les tableaux $this->jointure et $this->specific_fields
	 * qui seront utilisés par le CB.
	 * Ces tableaux définissent les jointures nécessaires pour la génération des tables w_edw
	 * à partir des tables w_astellia (= tables par entité).
	 * 
	 * Exemple de Ericsson BSS :
	 *      - $this->jointure : cell,hour,day,week,month,capture_duration,capture_duration_expected,capture_duration_real
	 *      - $this->specific_fields : cell,hour,day,week,month
	 * @param int $group_table_param identifiant le groupe de tables, c'est à dire la famille.
	 */
	public function get_join($group_table_param) {
		// pour les group tables générées à partir de plusieurs tables, on definit la jointure
		$param = $this->params->getWithGroupTable($group_table_param);
		$this->setJoinDynamic($param);
		if (Tools::$debug) {
			displayInDemon(__METHOD__ . " DEBUG : jointure[] puis specific_fields[]");
			var_dump($this->jointure);
			var_dump($this->specific_fields);
		}
	}
	
	
	/**
	 * 
	 * Lance le create_temp_table en mono ou multiprocessus
	 * @param String $className
	 */
    public static function execute($className) {
		
		// choix du mode : single ou multi processus
		//si le parametre n'est pas défini get_sys_global_parameters renvoie 1
    	$retrieve_single_process=get_sys_global_parameters('retrieve_single_process',0)==0?FALSE:TRUE;
		
    	// la méthode "get_group_table_from_w_table_list" du CB est redéfinie pour
    	// permettre la parallélisation au sein du create_temp_table, c'est pourquoi nous devons 
    	// vérifier qu'elle est inchangée côté CB.

    	//TODO maj si changement CB
    	//revoir la méthode de comparaison des class cb, pb car md5 change suivant chaque cb
    	//md5 du script "create_temp_table_generic.class.php" crypté du cb 5.1.6.32
//     	$create_temp_table_generic_md5_ref="e7995a1c0430e6e3adea25d02f664c8d";
    	
//     	if(md5_file(REP_PHYSIQUE_NIVEAU_0."class/create_temp_table_generic.class.php")!=$create_temp_table_generic_md5_ref){
//     		$retrieve_single_process=true;
//     		displayInDemon("warning: 'create_temp_table_generic.class.php' script has changed since the last CB, switching to single process mode");
//     	}

    	// purge des tables temporaires de topo
    	CreateTempTableCB::initTempTopoTables();
    	
    	// si cas mono processus
    	if($retrieve_single_process){
    		$CreateTempTableClass = new ReflectionClass($className);
    		//TODO passer un booléen pour le multi process sur nouvelle instance
    		$CreateTempTableImpl = $CreateTempTableClass->newInstance(NULL,TRUE);
    	}
    	// sinon (cas multi processus)
    	else{    		
    		// nous allons créer un processus enfant par condition, c'est à dire
    		// par table temporaire
    		$conditionsTab = CreateTempTable::getConditions();
    		if (count($conditionsTab) > 0) {
    			$processManager=ProcessManager::getInstance();
    			$cmd='php lib/CreateTempTableScript.php';
    			foreach ($conditionsTab as $tempTableCondition) {
    				 
    				// table temporaire à traiter
    				$env['condition']=$tempTableCondition;
    				 
    				// nom de la classe fille de CreateTempTable à considérer
    				$env['class_name']=$className;
    				 
    				// mode multiprocess
    				$env['single_process_mode']=FALSE;
    				 
    				// lancment (ou mise en file d'attente) du nouveau processus
    				$processManager->launchProcess($cmd, $env);
    			}

    			// paramètre $displayLog=FALSE car on ne souhaite pas afficher des logs régulièrement
    			$processManager->waitEndOfAllProcess(FALSE);

    			// *************************************************************
    			$day = substr($conditionsTab[0]->hour, 0, 8);
    			    			
    			//$createTempTable = new create_temp_table();
    			// => Call to undefined method create_temp_table::MAJ_objectref_specific

    			// classe spécifique à considérer 
    			$CreateTempTableClass = new ReflectionClass($className);
		
    			// Appel au constructeur de la classe spécifique à considérer.
    			// Ce n'est pas très "éléguant" mais le code CB fait beaucoup de chose dans le constructeur ...
    			// Etapes 1 et 2 : ne font rien car la table « sys_w_tables_list » a déjà été consommée, d'où le log « No Group table to manage ».
    			// Etapes 3 à 5 : le 2nd paramètre = $single_process_mode = TRUE pour que la 
    			// méthode "updateObjectRef" lance ces étapes (via un appel au code du CB).
    			$CreateTempTableImpl = $CreateTempTableClass->newInstance($tempTableCondition,TRUE);    			
    			// *************************************************************
    		}
    	}
    }
    
    
    
    /**
     * Méthode qui retourne les conditions correspondants aux tables temporaires à traiter,
     * trouvée dans la table "sys_w_tables_list".
     */
    protected static function getConditions() {
    	global $database_connection;   	
    	$conditionsTab = array();
    	// "ORDER BY hour DESC" car le jour le plus récent est utilisé dans la méthode "updateObjectRef"
    	$query = "SELECT distinct hour, group_table, network FROM sys_w_tables_list ORDER BY group_table,hour DESC";
    	$res = pg_query($database_connection, $query);
    	$nbRows = pg_num_rows($res);
    	if ($nbRows > 0) {
    		for ($i = 0;$i < $nbRows; $i++) {
    			$row = pg_fetch_array($res);
    			$hour = $row["hour"];
    			$networkMinLevel = $row["network"];
    			$id_group_table = $row["group_table"];
    			$condition = new TempTableCondition($hour, $networkMinLevel, $id_group_table);
    			$conditionsTab[] = $condition;
    		}
    	}
    	return $conditionsTab;
    }

}

?>