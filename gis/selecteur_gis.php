<?php
/**
 * 
 * @cb51
 * 
* 22/11/2011 NSE bz 24774 : ajout du produit en paramètre lors de l'appel de la méthode GisModel::getGisDisplayMode()
 */
?><?php
/**
*	Fichier qui genere le selecteur du GIS  - Appele depuis 'gis/index.php'
*/
?>
<?php
/**
 * 
 * @cb4100@
 * 
 * 	14/11/2007 - Copyright Astellia
 * 
 * 	Composant de base version cb_4.1.0.0
 *
 *	maj 16/01/2009 - MPR : On renomme le parametre gis_autoresfresh par autorefresh_delay
 *	 maj 27/08/2009 - Correction du bug 11246 : Ajout d'une valeur par défault lorsque le auto refresh est décoché
 */
?>
<?php
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *
 *
 */
?>
<?php
/*
	28/04/2009 GHX
		- Prise en compte du mapping
	27/04/2009 - SPS
		- ajout d'un fichier js pour les fonctions d'ajout de ligne
	30/04/2009 - SPS 
		- modification du style de la fenetre d'ajout de ligne
	04/05/2009 - SPS 
	  	- ajout d'un message d'erreur pour l'ajout de ligne
		- modification du style de la fenetre
	05/05/2009 - SPS 
		- ajout de l'evenement onclick sur le checkbox de suppression de ligne
	 06/05/2009 - SPS 
		- ajout de la gestion des couleurs pour l'ajout de ligne
		- messages, labels enregistres dans message_display.sql 
	14/05/2009 GHX
		- Modification de l'appel au mapping
	25/05/2009 GHX
		- Regarde si les liens vers AA sont actives
			- Si link_to_aa dans sys_global_parameters = 1
			- Si la TA est "hour" ou "day"
			=> dans ce cas on active les liens vers AA dans la cas DashboardData (DashboardData::setEnabledLinkToAA())
*/
?>
<?php
/**
*	Page affichant un selecteur un dashboard
*   
*  18/03/2009 - modif SPS : - ajout du nom du xml dans la variable de session
*						    - test de l'existence du png avant de l'enregistrer en session
*  
*
*	@author	BBX - 30/10/2008
*	@version CB 4.1.0.0
*	@since	CB 4.1.0.0
*/
	session_start();
	include_once dirname(__FILE__)."/../php/environnement_liens.php";

	// Librairies et classes requises
	include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."class/Date.class.php");
	include_once(MOD_SELECTEUR."php/selecteur.class.php");
	include_once(MOD_SELECTEUR."php/selecteurGis.class.php");

	// Inclusion du header

	// 16/02/2009 - Modif. benoit : si l'on specifie via l'url que le bandeau doit etre masque, on definit les elements bandeau (#header) et menu (#menu_container) comme non visibles
?>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/prototype.js'> </script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/window.js'> </script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/scriptaculous.js'> </script>
	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/toggle_functions.js"></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/fenetres_volantes.js' charset='iso-8859-1'></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/gestion_fenetre.js'></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/ajax_functions.js'></script>
	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/caddy_management.js"></script>
	
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/prototype_window/default.css' type='text/css'/>
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/prototype_window/alphacube.css' type='text/css'/>
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/global_interface.css' type='text/css'/>
	
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/tab-view.css" type="text/css">
	<!-- include pour l'affichage de la selection des NA. -->
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/selection_des_na_recherche.css" type="text/css">
	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/selection_des_na_recherche.js"></script>

	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/selection_des_na.js"></script>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/selection_na.css" type="text/css">

	<script type="text/javascript">
		setLinkToAjax('<?=NIVEAU_0."reporting/intranet/php/affichage/"?>');
	</script>
	<style>
		.onglet {
			position : absolute;
			left:0px;
			z-index: 10000;
		}
		.onglet_force {
			text-align:left;
		}
	</style>
	<script>
	/*
		Permet de cacher / montrer le selecteur
	*/
	function show_hide_selecteur(obj)
	{
		toggle(obj); // fonction se trouvant dans js/toggle_functions.js
		sel = document.getElementById(obj);
		if(sel.style.display == 'block'){
			document.getElementById('onglet_filter').src = "<?=NIVEAU_0?>images/boutons/onglet_hide_filter.gif";
		} else {
			document.getElementById('onglet_filter').src = "<?=NIVEAU_0?>images/boutons/onglet_show_filter.gif";
		}

		// 05/12/2007 - Modif. benoit : dans le cas du selecteur du GIS, on redimensionne la vue a chaque affichage/masquage du selecteur
		launchResizeAction();

	}
	</script>
	
<?
	set_time_limit(3600);
	
	$module_restitution = "gis_supervision";
	$selecteur_scenario = "normal";
	$type = "page";

	$mode = "overnetwork";
	session_register("mode");

	$creator = "pauto";
	session_register("creator");

	$_GET['id_selecteur'] = 400;
	$family = $_GET['family'];
	$selecteur_open	= false;
	$autorefresh_on	= false;

	
	if (isset($_SESSION['sys_user_parameter_session'][$family]['gis_supervision'])) {
			unset($_SESSION['sys_user_parameter_session'][$family]['gis_supervision']);
	}
	
	if ($_GET['action'] == "choose_family") // Appel du GIS depuis le bouton "GIS Supervision" du bandeau
	{
		
		unset($_SESSION['external_data']);
		// maj 16/01/2009 - MPR : On renomme le parametre gis_autoresfresh par autorefresh_delay
		unset($_SESSION['autorefresh_delay']);
		
		$isset_gis_params = false;
		$selecteur_open = true;
		$autorefresh_on = true;
	}
	elseif ($_GET['action'] == "maj_params")	// Maj des parametres du selecteur
	{
		
		// maj 16/01/2009 - MPR : On renomme le parametre gis_autoresfresh par autorefresh_delay
		$_SESSION['autorefresh_delay']['on_off'] = ($_GET['autorefresh'] == "true") ? true : false;
		if($_GET['autorefresh'] == "true"){
			$autorefresh_on = true;
		}else{
			$autorefresh_on = false;
		}
		if($_GET['selecteur_open'] == "true") $selecteur_open = true;
		$isset_gis_params = false;
	}
	else	// Appel du GIS depuis un graphe ou un resultat d'alarme
	{
		// maj 16/01/2009 - MPR : On renomme le parametre gis_autoresfresh par autorefresh_delay
		unset($_SESSION['autorefresh_delay']);
		$_SESSION['external_data'] = explode('|@|', $_GET['gis_data']);
		$family = $_SESSION['external_data'][6];
		$mode = $_SESSION['external_data'][4];

		$isset_gis_params = true;
	
	}

	
	if (isset($_GET['hide_header']) && ($_GET['hide_header'] == "1")) 
	{
		echo '	<style type="text/css">
					#header {display:none} 
					#menu_container{display:none}
				</style>';
	}
	
	/**
	* Fonction qui retourne le label du raw ou kpi voulu
	*/
	function getRawKpiLabel($id_prod, $raw_kpi){
	
		$database_connection = Database::getConnection( $id_prod );
		
		if($raw_kpi['type'] == 'kpi')
		{
			$field_name = 'kpi_label';
			$table = 'sys_definition_kpi';
		}else
		{
			$field_name = 'edw_field_name_label';
			$table = 'sys_field_reference';
		}
		
		$query = "SELECT {$field_name} FROM {$table} WHERE id_ligne ILIKE '{$raw_kpi['id']}'";
		$row = $database_connection->getOne($query);
		
		if($row == ""){
			$label = $raw_kpi['name'];
		} else { 
			$label = $row;
		}
		
		return $label;
		
	}

	
	// maj MPR 20/11/2008 - MPR : Creation de la fonction getSelecteurParameters()
	/**
	* Fonction qui recupere les parametres du selecteur soit via le POST du formulaire soit via le GET d'une URL (cas sur click des boutons colapse et expand
	* @return array $tab : tableau contenant les parametres du selecteur
	*/
	function getSelecteurParameters( $defaults, $family ){
		
		if (!isset($_POST['selecteur'])) {
		
			$tab['gis_nel_selecteur'] = "";
			
			if (isset($defaults['date']))					$tab['date']					= $defaults['date'];
			if (isset($defaults['hour']))					$tab['hour']					= $defaults['hour'];
			if (isset($defaults['ta_level']))				$tab['ta_level']				= $defaults['ta_level'];
			if (isset($defaults['na_level']))				$tab['na_level']				= $defaults['na_level'];
			if (isset($defaults['axe3']))					$tab['axe3']					= $defaults['axe3'];
			if (isset($defaults['axe3_2']))					$tab['axe3_2']					= $defaults['axe3_2'];
			// if (isset($defaults['autorefresh']))					$tab['autorefresh']		= $defaults['autorefresh'];
			
			if (isset($defaults['gis_nel_selecteur']) ) 	$tab['gis_nel_selecteur']		= $defaults['gis_nel_selecteur'];
			if (isset($defaults['gis_counters_selecteur']))	$tab['gis_counters_selecteur']	= $defaults['gis_counters_selecteur'];

			// on modifie le format de $date en fonction du ta_level
			$date = $tab['date'];
			
		} else {
			$tab = $_POST['selecteur'];
			// maj 27/08/2009 - Correction du bug 11246 : Ajout d'une valeur par défault lorsque le auto refresh est décoché
			if( !isset($tab['autorefresh']) ){
				
				$tab['autorefresh'] = 0;
			}
		
		}
		
		$_SESSION['sys_user_parameter_session'][$family]['gis_supervision'] = $tab;
		
		return $tab;
	}
	
?>	
<div id="container" style="width:100%;text-align:center">

	<?
	// if($mode != 'alarm'){
	?>
		<div class="onglet_force">
			<div class="onglet">
				<div id="onglet_filter_div" name="onglet_filter_div">
					<img src="<?=NIVEAU_0?>images/boutons/onglet_hide_filter.gif"
						onMouseOver="popalt('Show/Hide the filter');style.cursor='pointer';" onMouseOut="kill()"
						id="onglet_filter" onclick="show_hide_selecteur('selecteur_container')">
				</div>
				<div>
					<img src="<?=NIVEAU_0?>images/boutons/onglet_change_family.gif"	
						onMouseOver="popalt('Change family');style.cursor='pointer';" onMouseOut="kill()"	
						id="onglet_change_family" onclick="document.location.href='<?=NIVEAU_0?>gis/supervision.php'">
		
				</div>
			</div>
		</div>
	<?
	// }
	?>

<?php
	$timeStartTreatment = microtime(true);
	
	// Recuperation des infos de la famille + produit + module
	$family  = isset($_GET['family']) ? $_GET['family'] : $_SESSION['external_data'][6];
	
	$product = isset($_GET['product']) ? $_GET['product'] : $_SESSION['external_data'][0];
	if($product == ""){
		$product = 1;
	}
	
	$module = get_sys_global_parameters("module","", $product);

	$na_min  = get_network_aggregation_min_from_family($family, $product);
	
	$axe3_options = array();
	if (GetAxe3($family, $product)){
		$tab_na_axe3 = getNaLabelList("na_axe3",$family,$product);
		
		// axe3 options : liste du premier menu select axe 3
		$axe3_options = $tab_na_axe3[$family];
		
		$na_axe3_default = get_network_aggregation_max_from_family($family,3,$product);
		// __debug($na_axe3_default, "na axe3 max");
		$na_axe3_value_default = "ALL";
	}
	// ** Chargement du selecteur
	$selecteur	= new SelecteurGIS($product,$family);
	
	$na_value	= '';
	
    // maj 10/06/2011 - MPR : DEV GIS without Polygons
    // Controle sur le paramètre gis_display_mode
    // Si gis_display_mode = 1, on peut visualiser le GIS uniquement sur le NA min
    // 22/11/2011 NSE bz 24774 : ajout du produit en paramètre
    $gisDisplayMode = GisModel::getGisDisplayMode($product);

	// Definition des valeurs par default du selecteur
	if(isset( $_SESSION['external_data'] )){
	
		$id_raw_default = "{$_SESSION['external_data'][12]}@{$_SESSION['external_data'][13]}@{$_SESSION['external_data'][15]}";
		$na_default = $_SESSION['external_data'][1];
		$ta_default = $_SESSION['external_data'][7];
		
		if($_SESSION['external_data'][2] !== "ALL")
                {
                        // maj 03/08/2010 - MPR : Correction du BZ 16967
                        // Gestion Sélection multiple des éléments réseau
                        $na_values = array();
			foreach( explode("||", $_SESSION['external_data'][2]) as $ne )
                        {
                            $ne_label = getNELabel($na_default, $ne,  $_SESSION['external_data'][0] );
                            $na_values[] = "{$_SESSION['external_data'][1]}@{$ne}@{$ne_label}";
                        }
                        $na_value = implode("|s|",$na_values);

		}

		$Date = new Date();
	
		// maj 03/12/2009 MPR : Correction du bug 12982 : GIS month n'affiche pas de résultat
		$_ta = ( $_SESSION['external_data'][7] =='hour' ) ? "day": $_SESSION['external_data'][7];
		$date_default = $Date->getSelecteurDateFormatFromDate( $_ta, substr( $_SESSION['external_data'][8], 0, 8)  );

		$hour = substr($_SESSION['external_data'][8], 8, 2);
		$hour_default = ( $hour !=  "") ? $hour .":00": (date('H')-1).":00";
		
		if (GetAxe3($family, $product)){
			
			$na_axe3_default = $_SESSION['external_data'][17];
			$na_axe3_value_default = $_SESSION['external_data'][18];
		}
		
		
	}else{
	
		$id_raw_default =  $selecteur->getFirstRawFromFamily();
        // maj 10/06/2011 - MPR : DEV GIS without Polygons
        // Controle sur le paramètre gis_display_mode
        // Si gis_display_mode = 1, on peut visualiser le GIS uniquement sur le NA min
		$na_default = ( $gisDisplayMode == 1 ) ? get_network_aggregation_max_from_family($family,0,$product) : get_network_aggregation_min_from_family( $family,0,$product );
		$ta_default = 'day';
		$date_default = date('d/m/Y');
		$hour_default = (date('H')-1).":00";
	}

	
	// defaults values for this box
	$defaults = array(
		'ta_level'	=> $ta_default,
		'na_level'  => $na_default,
		'date'		=> $date_default,
		'hour' 		=> $hour_default,
		'gis_nel_selecteur'  	 => $na_value,
		'gis_counters_selecteur' => $id_raw_default
	);
		$defaults['axe3'] = $na_axe3_default;
		$defaults['axe3_2'] = $na_axe3_value_default;

	

		
	$array_post	= getSelecteurParameters( $defaults, $family );
	
	// __debug($array_post,"array_post");
	// die;

	// NA levels : la liste des NA levels
    // 20/06/2011 NSE : merge Gis without polygons
    if( $gisDisplayMode == 1 )
    {
	    $na_levels = getNaLabelList("na",$family,$product);
	    $na_levels = $na_levels[$family];
    }
    else
    {
        $naMin = new NaModel($na_min);
        $na_levels = array( $na_min => $naMin->getLabel($product) );
    }

	// defaults values for this box : la encore, elle sont choisies "en dur", il faudra creer les requetes permettant de connaître ces valeurs

	// On recupere les ta du produit concerne 
	$ta_levels = getTaLabelList($product);

	$selecteur->setAutoRefreshMode($autorefresh_on);
	$selecteur->getSelecteurFromArray($array_post); // Recuperation des parametres 
	$selecteur->setNaArray( $na_levels, $axe3_options, $na_default ); // Definition des na
	$selecteur->setTaArray( $ta_levels, $ta_default ); // Definition des ta
	
	
	
	$selecteur_general_values = $selecteur->build(); // Construction du selecteur

	//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//

	$tab_raw_kpi = explode("@", $selecteur_general_values['gis_counters_selecteur']);
	
	// recuperation des infos sur le raw ou kpi a prendre en compte
	$raw_kpi['type']	= $tab_raw_kpi[0];
	$raw_kpi['id'] 		= $tab_raw_kpi[1];
	$raw_kpi['name']	= $tab_raw_kpi[2];
	
	$raw_kpi['label']	= getRawKpiLabel($product, $raw_kpi);
	
	// Gestion de la na_value
	
	if($selecteur_general_values['na_value'] == ""){
		
		$na_value = "ALL";
	}else{
		
		// $na_value_tmp = explode("@",$selecteur_general_values['gis_nel_selecteur']);
		// foreach($na_value_tmp as $_n_value_tmp){
			
			// $n_value[] = ($na_value_tmp[0] == ) ? : $na_value_tmp[0];
		// }
		// $na_value = ($na_value_tmp[0] == ) ? : $na_value_tmp[0];
		$na_value = $selecteur_general_values['na_value'];
	}
	

	$na_level = ( GetAxe3($family, $product) ) ? $selecteur_general_values['na_level'] ."_" . $selecteur_general_values['axe3'] : $selecteur_general_values['na_level'];
	
	
	$table = "edw_{$module}_{$family}_axe1_{$raw_kpi['type']}_{$na_level}_{$selecteur_general_values['ta_level']}";
	
	// Definition des parametres necessaires a l'instance du GIS
	$gis_data = array(
				'product' => $product,
				'na' => $selecteur_general_values['na_level'],
				'na_value' => utf8_encode($na_value),
				'na_base' => $na_min,
				'data_type' => 'graph',
				'mode' => '',
				'family' => $family,
				'ta' => $selecteur_general_values['ta_level'],
				'ta_value' => $selecteur_general_values['date'],
				'id_data_type' => '',
				'module' => $module,
				'alarm_color' => '',
				'sort_type' => $raw_kpi['type'],
				'sort_id' => $raw_kpi['id'],
				'sort_name' => $raw_kpi['label'],
				'sort_value' => $raw_kpi['name'],
				'table_name' => $table
				
	);
	
	

	
	// Ajout de la conf 3eme axe du selecteur
	if (isset($selecteur_general_values['axe3']) && ( $selecteur_general_values['axe3'] != "")) {
		$gis_data['na_axe3'] = $selecteur_general_values['axe3'];
	}

	if (isset($selecteur_general_values['axe3_2']) && ( $selecteur_general_values['axe3_2'] != "")) {
		$gis_data['na_value_axe3'] = $selecteur_general_values['axe3_2'];
	}
	
	
	// __debug($selecteur_general_values,"GIS DATA");
	// __debug($gis_data,"GIS DATA");
	// die;


//		==========	DISPLAY ===========)	

	// maj 16/01/2009 - MPR : On renomme le parametre gis_autoresfresh par autorefresh_delay
	if (!isset($_SESSION['autorefresh_delay'])) {	
		$_SESSION['autorefresh_delay']['on_off']			= $autorefresh_on;
		// maj 20/08/2009 - MPR : Correction du bug 11096 : On récupère la valeur du paramètre sur la base du produit concerné
		$_SESSION['autorefresh_delay']['delay']			= get_sys_global_parameters('autorefresh_delay_delay',1,$product)*60;
	}

	if (!isset($_SESSION['sys_user_parameter_session'][$family]['gis_supervision'])) 
	{
		$_SESSION['sys_user_parameter_session'][$family]['gis_supervision'] = array();
	}
	
	if ($isset_gis_params) {
	
		insert_gis_params_into_user_parameter_session($_SESSION['external_data'], $family);
		
	}
	else // Si la chaine de parametres a transmettre au GIS n'est pas defini, on la construit 
	{
		// On recupere certains des parametres a transmettre au GIS parmi les valeurs du tableau de session 'sys_user_parameter_session'
		// Pour les autres parametres, on se sert des valeurs deja definies en session ou de celles par defaut
		
		$e_data = $_SESSION['external_data'];
		session_unregister('external_data');
		if ($na_axe3 != "") $na .= "_".$na_axe3;
		if ($na_axe3_value != "") $na_value .= get_sys_global_parameters('sep_axe3','|s|',$product).$na_axe3;
	
		$na_min			= ($e_data[2] != "" ) ? $e_data[2] : get_network_aggregation_min_from_family($family,0,$product);
		$data_type		= ($e_data[3] != "" ) ? $e_data[3] : "graph";
		$gis_mode		= ($e_data[4] != "" ) ? $e_data[4] : "";
		$id_data_type	= ($e_data[8] != "" ) ? $e_data[8] : "";
		$module			= ($e_data[9] != "" ) ? $e_data[9] : get_sys_global_parameters('module',0,$product);
		$alarm_color	= ($e_data[10] != "" ) ? $e_data[10] : "";
		$table_name		= 'edw_'.$module.'_'.$family.'_axe1_'.$sort_type.'_'.$na.'_'.$ta;
		
		// Sauvegarde en session des parametres du GIS
		
		$_SESSION['external_data'] = array($na, $na_value, $na_min, $data_type, $mode, $family, $ta, $ta_value, $id_data_type, $module, $alarm_color, $sort_type, $sort_id, $sort_name, $sort_value, $table_name, $na_axe3, $na_axe3_value);
	}

	function insert_gis_params_into_user_parameter_session($gis_params, $family)
	{		
		session_unregister('sys_user_parameter_session');
		
		$sys_user_parameter_session_gis['product'] 	= $gis_params[0];
		$sys_user_parameter_session_gis['na_level'] = $gis_params[1];
		$sys_user_parameter_session_gis['na_value'] = ($gis_params[2] == 'ALL') ? "" : $gis_params[2];
		$sys_user_parameter_session_gis['ta_level'] = $gis_params[7];
		$sys_user_parameter_session_gis['data_type'] = $gis_params[4];
		$sys_user_parameter_session_gis['table_name'] = $gis_params[16];
		$sys_user_parameter_session_gis['date']	= substr($gis_params[8],0,8);
		$hour = substr($_SESSION['external_data'][8], 8, 2);
		$sys_user_parameter_session_gis['hour']	= ($hour != "") ? $hour.":00": date("i").":00";
		$sys_user_parameter_session_gis['gis_counters_selecteur'] 	 = "{$gis_params[12]}@{$gis_params[13]}@{$gis_params[15]}";
		
		if($gis_params[2] !== "ALL"){
			$ne_label	= getNELabel($gis_params[1],$gis_params[2], $gis_params[0] );
			$sys_user_parameter_session_gis['gis_nel_selecteur'] 	 = "{$gis_params[1]}@({$gis_params[2]})@{$ne_label}";
		}
	
		$_SESSION['sys_user_parameter_session'][$gis_params[6]]['gis_supervision'] = $sys_user_parameter_session_gis;
		
	}
?>