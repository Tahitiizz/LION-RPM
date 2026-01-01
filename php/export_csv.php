<?
// Export d'un fichier csv lors d'un download topology / download topology 3rd Axis 
?>
<?
/*
*	@cb41000@
*
*	24/11/2008 - Copyright Astellia
*
*
* 	maj 24/11/2008 - MPR  :  - Réécriture complète du script
*				     	  - modification de la table de référence par défault (edw_object_ref)
*			  	  	  - Gestion des coordonnées géographiques (récupération des données depuis la table edw_object_ref_parameters)
*					  - Ajout des fonctions createQuery et getAgregPath pour reconstruire les chemins complets d'agrégation
*
*	MPR : Correction du bug 9867 - On ajoute la condition sur eor_obj_type afin de ne pas mélanger les éléements réseau de niveau d'agreg <>
*		Correction du bug 9638 - Modification de la reuqête SQL qui récupère les données topologiques ( pbl cas particulier cell->bsc->msc->network non gérer avec l'ancienne requête)
*		Correction du bug 9636 - On récupère systématiquement les labels des colonnes du header (type header T&A)
*	27/08/2009 GHX
*		- Le fichier de topo se trouve toujours sur le master maintenant
*	15/10/2009 - MPR : Ajout des paramètres trx et charge
*	maj 16:32 22/02/2010 - MPR : Correction du BZ14416 : On passe le chemin complet avec /home => Utilisation du script force_download.php pour forcer l'enregistrement du fichier 
*	01/03/2010 NSE bz 14244 
*		- le paramètre de la table sys_global_parameters est activate_trx_charge_in_topo et non activate_trx_charge_into_topo
*		- uniformisation en utilisant le nom de variable activate_trx_charge_in_topo et non activate_trx_charge_into_topo pour les noms de variables
*	maj 15:45 16/03/2010 - MPR : Correction du BZ 14328 : Utilisation de REP_PHYSIQUE_NIVEAU_0 (nécessaire si le port 80 est inaccessible)
*	maj 10:43 18/03/2010 - MPR : Correction du BZ 14751 - Colonne on_off doit être la dernière		
*/
	
	session_start();
		
	include_once(dirname(__FILE__)."/environnement_liens.php");
	// include(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");

	include_once(REP_PHYSIQUE_NIVEAU_0."class/topology/TopologyDownload.class.php");
		
	unset($lst_fields);
	
	if(isset($_GET['type_header']) ){
	
		include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");

		
		$id_prod 	 = $_GET['product'];
		$family 	 = $_GET['family'];
		$type_header = $_GET['type_header'];
		$fields 	 = $_GET['fields'];
		$coordinates = $_GET['coordinates'];
		
		$naMin = get_network_aggregation_min_from_family( get_main_family($id_prod), $id_prod );
		$activate_capacity_planing = get_sys_global_parameters('activate_capacity_planing',1, $id_prod);
		// 01/03/2010 NSE bz 14244 le paramètre de sys_global_parameters est activate_trx_charge_in_topo et non activate_trx_charge_into_topo
		$activate_trx_charge_in_topo = get_sys_global_parameters('activate_trx_charge_in_topo',1, $id_prod);
		
		// 01/03/2010 NSE bz 14244 remplacement de activate_trx_charge_into_topo par activate_trx_charge_in_topo pour uniformisation
		if( $activate_capacity_planing && $activate_trx_charge_in_topo && in_array($naMin, $fields ) ){		
			$params_erlang = $_GET['paramsErlang']; 
		}

		// Récupération des infos du produit
		$infosProduct = getProductInformations($id_prod);
		$infosProd = $infosProduct[$id_prod]; 
	}

	// Ajout des paramètres trx et charge
	if( isset($params_erlang) && isset($coordinates) )
		$lst_fields = array_merge( $fields, $params_erlang, $coordinates );
	elseif( isset($coordinates) )
		$lst_fields = array_merge( $fields, $coordinates );
	elseif(  isset($params_erlang) )
		$lst_fields = array_merge( $fields, $params_erlang );
	else
		$lst_fields = $fields;

	if( in_array("eor_on_off", $lst_fields) ){
		
		$on_off_id = array_keys($lst_fields,"eor_on_off");
		// 18/03/2010 - MPR : Correction du BZ 14751 - Colonne on_off doit être la dernière
		unset($lst_fields[ $on_off_id[0] ]);
		$lst_fields[] = "on_off";
		
	}
	
	$na_type = ( $_GET['axe3']  or in_array(get_network_aggregation_min_axe3_from_family($family, $id_prod), $fields) ) ? "na_axe3" : "na";
	// modification de la table de référence par défault (edw_object_ref)
	$na_list = getNaLabelList($na_type, $family);
    
    // Connexion à la base
        // 03/08/2010 - MPR : Nettoyage du code On ne fait pas new DataBaseConnection() mais DataBase::getConnection()
	$database = DataBase::getConnection($id_prod);
	
	$tab = array();
	$lst_files = array();
	$header = array();

	// Récupération du niveau d'agrégation réseau minimum

	$net_min = ( $_GET['axe3']  or in_array(get_network_aggregation_min_axe3_from_family($family), $fields) ) ? get_network_aggregation_min_axe3_from_family( $family, $id_prod) : get_network_aggregation_min_from_family( $family, $id_prod );

	
	$topoDownload = new TopologyDownload($family, $id_prod);
	$dir = 'upload/';
	if( !( !isset( $_id ) and $id_prod !== null )){
		
		$dir = 'upload/export_files/';
		$topoDownload->setCoordsType(0);
		$topoDownload->setTargetDir( REP_PHYSIQUE_NIVEAU_0."upload/export_files/" );
		
	}
	$topoDownload->setSeparator(";");
	
	$topoDownload->setFields( $lst_fields );
	
	if($na_type == 'na') {
		
		$filepath = (!isset($name)) ? "admintool_download_topology_{$family}.csv" : $name."_topology_first_axis.csv";
		$axe3 = false;
	}else{
		$filepath = (!isset($name)) ? "admintool_download_topology_{$family}_3rd_Axis.csv" : $name."_topology_third_axis.csv";
		$axe3 = true;
	}
	
	
	$topoDownload->exportTopology($filepath,$axe3);

	// 16:56 27/08/2009 GHX
	// Le fichier de topo est toujours sur le master
	
	// maj 17/06/2009 MPR - On vérifie si on fait un download topology ou si on génère le fichier dans le data export
	if( !isset( $_id ) and $id_prod !== null ){
		// maj 16:32 22/02/2010 - MPR : Correction du BZ 14416 : On passe le chemin complet avec /home => Utilisation du script force_download.php pour forcer l'enregistrement du fichier 
		// maj 15:45 16/03/2010 - MPR : Correction du BZ 14328 : Utilisation de REP_PHYSIQUE_NIVEAU_0 (nécessaire si le port 80 est inaccessible)
		$file_path = REP_PHYSIQUE_NIVEAU_0.$dir.$filepath;
		echo $file_path;
	}

?>