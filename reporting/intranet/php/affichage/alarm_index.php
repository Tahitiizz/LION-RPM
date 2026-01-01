<?
/*
*	@cb41000@
*
*	05/09/2008 - Copyright  Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- maj 05/09/2008 slc : mise en commentaire des tags <html><head><title><body> qui se trouvaient en plein milieu de page car ils cassaient le bandeau du haut
*	- maj 14/10/2008 slc : répare l'interface suite à l'ajout du DOCTYPE
*
*	- maj 07/11/2008 MPR : Modification des paramètres en entrée de la classe
*	- maj 10/11/2008 MPR : On ajoute le paramètre id_prod correspondant à l'id du produit
*	- maj 12/11/2008 MPR : On récupère les na de la famille du produit concerné 
*	- maj 12/11/2008 MPR : Génération du Sélecteur pour les alarmes Top-worst
*	- maj 14/11/2008 MPR : On supprime le paramètre $_SESSION["selecteur_general_values"] et on le remplace par $selecteur_general_values['nel_selecteur']
*	- maj 20/11/2008 - MPR : Création de la fonction getSelecteurParameters()
*	- maj 02/12/2008 - SLC - gestion multi-produit
*	 10/04/2009 - SPS : script gestion_selecteur.js plus utilise 
*
*	20/07/2009 GHX
*		- Correction du BZ 10585 [REC][Alarm history]: liens collapse all expand all non fonctionnel
*	22/07/2009 GHX
*		- Correction du BZ 10737 [REC][T&A Cb 5.0][Top/Worst List] : si on passe d'un produit à un autre, le choix des familles ne fonctionne plus
*	04/08/2009 GHX
*		- Correction du BZ 10639
*	06/08/2009 GHX
*		- Correction d'un problème quand on arrive sur Alarm Management et qu'on avait des éléments réseaux favoris
*
*	19/08/2009 BBX : On passe la variable GET au lieu de la variable de session à la fonction setListOfNetworkElement. BZ 11117
*
*	20/08/2009 GHX
*		- Correction du BZ 11084 [REC][T&A Cb 5.0][TP#1][TS#UC10-CB20][TC#3968][Alarm History] : en mode Hour, le sélecteur enlève le 0 pour les heures inférieures à 10h
*			-> Modification de la façon dont on crée l'heure sinon pas le bon format pour les heures entre 0h et 9h du matin
* 	01/09/2009 GHX
*		- Correction d'un bug JS quand on arrive sur Top/Worst List
*
*	17/09/2009 BBX : si la variable GET est vide, on utilise la variable POST pour la focntion setListOfNetworkElement BZ 10584
*	23/02/2010 NSE
*		- remplacement de la fonction GetLastDayFromAcurioWeek par leur équivalent de la classe Date
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj christophe 14/04/2008 : correction bug BZ6252, ajout d'un count sur le parcours du tableau de session sys_user_parameter_session, mise en commentaire de la seconde boucle for car cette variable de session n'existe plus.
*	- maj 12/03/2008 christophe : Si l'utilisateur a changé de mode, on supprime le précédent timestamp car celui-ci peut ne pas exister dans le nouveau mode sélectionné.
*	- maj 10/03/2008 christophe : on récupère le nouveau paramètre order_on (définit si on tri sur calculation_time ou ta_value). par défaut c'est capture time.
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 20/08/2007 christophe : correction bug si on vient d'un dash dans lequel la sélection des NA a été vidée
*	- 16/07/2007 christophe : intégration de la sélection des NA.
*
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*
*	- 01 12 2006 christophe : correction perte du menu contextuel quand on fait un sibmit sur les TA dans alarm management.
*/
?>
<?
/*
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
	/*
		Création : christophe le 01/08/2006
		Ce fichier permet d'afficher les écrans 'alarm management' et 'alarm history' des alarmes.
		Paramètres GET de la page :
			- mode : 'management' ou 'history'. (définit le mode à afficher)
			- sous_mode : pour management par exemple il y a 2 sous_modes : 'elem_reseau' ou 'condesed'.

		- maj 19 09 2006 christophe : intégration du graph pour le mode alarm history.
		- maj 26 09 2006 christophe : ajout d'un is_array l 339
		- maj 29 09 2006 christophe : suppression d'une variable de session pour corriger le bug : apparition de lé selection des na dans le sélecteur des TWCL.
		- maj 20 11 2006 christophe : quand il y a un autorefresh, les ta sélectionnées en alarm management sont conservées.
		- maj 13/04/2007 Gwénaël : prise en compte du troisième axe
	*/

	session_start();

	// Pour que la sélection des NA n'apparaissent pas.
	//$_SESSION["selecteur_general_values"]["list_of_na_mode"] = "";


	// Permet de rediriger l'utilisateur sur la page d'accueil de l'apllication si la session est perdue.
	include_once("../../../../check_session.php");

	
	// INCLUDES.
	include_once(dirname(__FILE__)	. "/../../../../php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0  . "php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0  . "class/select_family.class.php");
	include_once(REP_PHYSIQUE_NIVEAU_0  . "intranet_top.php");
	include_once(REP_PHYSIQUE_NIVEAU_0  . "class/alarmDisplayCreate.class.php");		// affichage mode management/history pour les alarmes static/dyn
	include_once(REP_PHYSIQUE_NIVEAU_0  . "class/alarmDisplayCreate_twcl.class.php");	// affichage des Top/Worst cell list.
	include_once(REP_PHYSIQUE_NIVEAU_0  . "class/alarmLogError.class.php");			// affichage des alarmes ayant trop de résultat.
	include_once(REP_PHYSIQUE_NIVEAU_0  . "class/alarmGraphHistory.class.php");			
	include_once(REP_PHYSIQUE_NIVEAU_0  . "class/genDashboardNaSelection.class.php"); 	//affichage du choix des network aggregation
	include_once(MOD_SELECTEUR	."php/selecteur.class.php");						// Classe mère du sélecteur
	include_once(MOD_SELECTEUR	."php/SelecteurTopWorstList.class.php");				// Classe du selecteur des alarmes top-worst

/**
*	Page affichant un sélecteur un dashboard
*
*
*	@author	BBX - 30/10/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/
// Inclution du header

	// foreach ($_GET as $key => $val) echo "<br />_GET['$key'] = $val";

// 19/06/2009 BBX : ajout du container pour le menu contextuel
?>
<div id="container" style="width:100%;text-align:center">
	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/alarm_display.js"></script>
<?php
  /* 10/04/2009 - SPS : script gestion_selecteur.js plus utilise*/ 
  /*	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/gestion_selecteur.js"></script>*/
?>
	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/caddy_management.js"></script>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/tab-view.css" type="text/css">
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/alarm_display.css" type="text/css">

	<!-- include pour l'affichage de la sélection des NA. -->
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/selection_des_na_recherche.css" type="text/css">
	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/selection_des_na_recherche.js"></script>

	<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/selection_des_na.js"></script>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/selection_na.css" type="text/css">

	<script type="text/javascript">
		setLinkToAjax('<?=NIVEAU_0."reporting/intranet/php/affichage/"?>');
	</script>
<?

	set_time_limit(3600); // Evite un blocage de la page.

	/*
		On stocke le nom de la page courante avec ses paramètres afin de mettre des
		liens de rechargement de la page.
	*/

	// modif 02/12/2008 - SLC - gestion multi-produit
	if (!isset($_GET["product"])) {
		// 16:46 06/08/2009 GHX
		// Vide la variable de sessiion sinon on n'a pas de valeur d'affichée
		$_SESSION["selecteur_general_values"]["list_of_na"] = '';
		$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Alarm Management',false,'',2);
		exit;
	}
	// lien "change product"
	$ruri = $_SERVER["REQUEST_URI"];
	$ruri = ereg_replace("(&?product=[0-9]+)",'',$ruri);
	// 11:27 22/07/2009 GHX
	// Correction du BZ 10737 [REC][T&A Cb 5.0][Top/Worst List] : si on passe d'un produit à un autre, le choix des familles ne fonctionne plus
	// Supprime la famille de l'url
	$ruri = ereg_replace("(&?family=[a-zA-Z0-9]+&?)",'',$ruri);
	
	// Supprime nel_selecteur. BZ 11482
	$ruri = ereg_replace('nel_selecteur=','obsolete=',$ruri);	
	
	// 02/12/2009 BBX
	// Correction du grossissement de l'URL. On ne conserve que les derniers paramètres. BZ 13218
	$lastVarSerie = strrpos($ruri,'?');	
	$ruri = basename(__FILE__).substr($ruri,$lastVarSerie);
	// FIN BZ 13218	
	
	// On affiche le produit et on met un bouton de retour au choix du produit
    // 22/06/2010 OJT : Correction niveau NOTICE (ajout d'un test)
	if ( ( isset( $_GET['mode_alarme'] ) && ( $_GET['mode_alarme'] != 'twcl' ) ) || ( isset($_GET['family'] ) ) ){
		$db = Database::getConnection();
                // 13/09/2010 OJT : Correction bz16765 pour DE Firefox, mise du margin auto
		echo "<div style='width:880px;margin:5px auto 5px auto;' class='texteGris'>
				<!-- a href='$ruri'><img src='".NIVEAU_0."images/icones/change.gif' width='20' height='20' alt='Change product' align='absmiddle' /> Change product</a -->
				Actual product : <strong>".$db->getone("select sdp_label from sys_definition_product where sdp_id='".intval($_GET['product'])."' ")."</strong> <a href='$ruri'><img src='".NIVEAU_0."images/icones/change.gif' width='20' height='20' alt='Change product' /></a>
			</div>";
	}

	$_SESSION["url_alarme_courante"] = $_SERVER['PHP_SELF']."?".$_SERVER['argv'][0];
	$_SESSION["url_alarme_courante"] = explode("&periode_navigation", $_SESSION["url_alarme_courante"]);
	$_SESSION["url_alarme_courante"] = $_SESSION["url_alarme_courante"][0];
	
	
	$_SESSION["id_page_encours_alarme"] = $_GET["id_menu_encours"];
	
	if ( empty($_SESSION["order_on_prec_alarm_management"]) )
		$_SESSION["order_on_prec_alarm_management"] = 'capture_time';


	// Type d'affichage passé en paramètre, par défaut on considère que l'on affiche l'écran alarm management.
	$mode_alarme 		= (isset($_GET["mode_alarme"])) ? $_GET["mode_alarme"] : 'management';
	$sous_mode_alarme	= (isset($_GET["sous_mode"])) ? $_GET["sous_mode"] : 'elem_reseau';
	// maj 10/03/2008 christophe : on récupère le nouveau paramètre order_on (définit si on tri sur calculation_time ou ta_value). par défaut c'est capture time.
	$order_on = (isset($_GET['order_on'])) ? $_GET['order_on'] : 'capture_time';
	
	// 11:22 20/07/2009 GHX
	// Correction du BZ 10585
	// On modifie la variable $_SESSION["url_alarme_courante"] pour mettre dedans les valeurs du sélecteur
	// 16:18 04/08/2009 GHX
	// Correction du BZ 10639 
	// Modification de la condition
	if($mode_alarme != "twcl"  )
	{
		if ( count($_POST) > 0 && isset($_POST['selecteur']) )
		{
			foreach ( $_POST['selecteur'] as $k => $v )
			{
				// On ne prendre pas en compte l'heure car prise en compte avec la date
				if ( $k == 'hour' )
					continue;
				
				// On change le format de la date
				if ( $k == 'date' )
				{
					$v = ereg_replace("([0-9]*)/([0-9]*)/([0-9]*)", "\\3\\2\\1", $v);
					// On ajoute l'heure si présente dans le sélecteur
					if ( isset($_POST['selecteur']['hour']) )
					{
						$_ = explode(':', $_POST['selecteur']['hour']);
						$v .= $_[0];
					}
				}
				
				if ( ereg( "&{$k}=[^&]*", $_SESSION["url_alarme_courante"]) )
				{
					$_SESSION["url_alarme_courante"] = preg_replace("/&{$k}=[^&]*/", "&{$k}={$v}", $_SESSION["url_alarme_courante"]);
				}
				else
				{
					$_SESSION["url_alarme_courante"] .= "&{$k}={$v}";
				}
			}
		}
	}
	

	/*
		Si on est en mode history, on vérifie si l'utilisateur n'a pas cliqué sur le graph de alarme history.
		Si c'est le cas, les variables $_GET["ta_value_navigation"] et $_GET["periode_navigation"]
		sont définies.
	*/
	if($mode_alarme == "history" && isset($_GET["ta_value_navigation"]) && isset($_GET["periode_navigation"]))
		//	maj christophe 14/04/2008 : correction bug BZ6252
		//	ajout d'un count sur le parcours du tableau de session sys_user_parameter_session, mise en commentaire de la seconde boucle for car cette variable de session n'existe plus.
		for ($i=0; $i < count($_SESSION["sys_user_parameter_session"]["commune"]["commun"]); $i++)
			if ($_SESSION["sys_user_parameter_session"]["commune"]["commun"][$i]["parameter_type"] == "date")
				$_SESSION["sys_user_parameter_session"]["commune"]["commun"][$i]["parameter_value"] = $_GET["ta_value_navigation"];


	if ( $mode_alarme == 'management' ) {
		if (isset($_SESSION['id_menu_encours_alarm_management'])) {
			$page_encours = $_SESSION['id_menu_encours_alarm_management'];
		} else {
			$page_encours = $_GET["id_menu_encours"];
			$_SESSION['id_menu_encours_alarm_management'] = $page_encours;
		}
	} else {
		$page_encours = $_GET["id_menu_encours"]; // permet de trouver la liste des menus contextuels.
	}

	include_once(REP_PHYSIQUE_NIVEAU_0  . "php/menu_contextuel.php");
	

	// maj MPR 20/11/2008 - MPR : Création de la fonction getSelecteurParameters()
	/**
	* Fonction qui récupère les paramètres du sélecteur soit via le POST du formulaire soit via le GET d'une URL (cas sur click des boutons colapse et expand
	* @return array $tab : tableau contenant les paramètres du sélecteur
	*/
	function getSelecteurParameters(){
		
		if (!isset($_POST['selecteur'])) {
                    // 14/06/2013 NSE bz 34069 : perte du paramétrage du selecteur au clic sur Reload du menu contextuel
                    // on recharge le paramétrage stocké en session
                    if (!isset($_GET['date']) && isset($_SESSION["AlarmSelecteur"])) {
                        $tab = $_SESSION["AlarmSelecteur"];
                    }
                    else{
			if (isset($_GET['date']))				$tab['date']			= $_GET['date'];
			if (isset($_GET['hour']))				$tab['hour']			= $_GET['hour'];
			if (isset($_GET['ta_level']))			$tab['ta_level']		= $_GET['ta_level'];
			if (isset($_GET['na']))					$tab['na']				= $_GET['na'];
			if (isset($_GET['na_box']))				$tab['axe3']			= $_GET['na_box'];
			if (isset($_GET['nel_selecteur']))		$tab['nel_selecteur']	= $_GET['nel_selecteur'];
			if (isset($_GET['period']))				$tab['period']			= $_GET['period'];
			
			// on modifie le format de $date en fonction du ta_level
			$date = $tab['date'];
			switch ($tab['ta_level']) {
				case 'hour':
					$year	= substr($date,0,4);
					$month	= substr($date,4,2);
					$day		= substr($date,6,2);
					$tab['date']	= "$day/$month/$year";
					$tab['hour']	= substr($date,8,2).':00';
				break;
				
				case 'day' :
				case 'day_bh' :
					$year	= substr($date,0,4);
					$month	= substr($date,4,2);
					$day		= substr($date,6,2);
					$tab['date']	= "$day/$month/$year";
				break;

				case 'week' :
				case 'week_bh' :
					$year	= substr($date,0,4);
					$week	= substr($date,4,2);
					$tab['date']	= "W$week-$year";
				break;

				case 'month' :
				case 'month_bh' :
					$year	= substr($date,0,4);
					$month	= substr($date,4,2);
					$tab['date']	= "$month/$year";
				break;
			}
                    }
		} else
			$tab = $_POST['selecteur'];
		
                // 14/06/2013 NSE bz 34069 : perte du paramétrage du selecteur au clic sur Reload du menu contextuel
                // on sauvegarde le paramétrage en session (pour le conserver pour le reload)
                $_SESSION["AlarmSelecteur"] = $tab;
                
		return $tab;
	}

	
	// 24/07/2007 christophe : dans l'affichage des alarmes history et management ces valeurs sont égales car il n'y a pas de notions de familles.
	//$_SESSION["selecteur_general_values"]["list_of_na"] = $_SESSION["network_element_preferences"];

	/*
		$na : network aggregation à afficher.
		$ta : liste des time aggregation à afficher sous la forme d'une liste 'hour','day' ou 'hour' tout seul. Si vide toutes la ta n'est pas utilisée.
		$ta_value : valeur de la ta que l'on souhaite afficher (non utilisé dans alarm management).
		$display : définit le mode d'affichage des éléments  par défaut :
			- 'block' : toutes les lignes de résultats sont affichées.
			- 'none' : seules les lignes 'parentes' sont affichées.
		$timestamp :  valeur du timestamp à afficher (utilisé dans le mode alarm management).
	*/

	$na =	(isset($_GET["na_alarme"])) ? $_GET["na_alarme"] : "";
	$ta =	(isset($_GET["ta_alarme"])) ? $_GET["ta_alarme"] : "";
	$ta_value = (isset($_GET["ta_value_alarme"])) ? $_GET["ta_value_alarme"] : "";

    // OJT - 21/06/2010 Mise de la valeur par defaut à none
	$display = (isset($_GET["display_mode"])) ? $_GET["display_mode"] : "none";
	$timestamp = (isset($_GET["timestamp_alarme"])) ? $_GET["timestamp_alarme"] : "";
	
	// maj 12/03/2008 christophe : Si l'utilisateur a changé de mode, on supprime le précédent timestamp car celui-ci peut ne pas exister dans le nouveau mode sélectionné.
	if ( $_SESSION["order_on_prec_alarm_management"] != $order_on ) $timestamp= '';
	
	$_SESSION["order_on_prec_alarm_management"] = $order_on;
	
	
	/*	Gestion de l'inclusion du selecteur
		Si le mode est history ou top/worst cell list, on affiche
		le sélecteur.	*/
	if ($mode_alarme != 'management') {
		// Si la famille n'a pas été choisie on affiche le choix de la famille. >> seulement pour les TWCL
		if(!isset($_GET["family"]) && $mode_alarme == 'twcl') {
			// 11:27 22/07/2009 GHX
			// Correction du BZ 10737 [REC][T&A Cb 5.0][Top/Worst List] : si on passe d'un produit à un autre, le choix des familles ne fonctionne plus
			// Modification du premier paramètre passé à la classe 
			$select_family = new select_family($ruri, $_SERVER['argv'][0], 'Top / Worst cell list');
			exit;
		}
		

		if ($mode_alarme == 'twcl') {
		
			$family = $_GET["family"];
			// maj 10/11/2008 MPR : On ajoute le paramètre id_prod correspondant à l'id du produit
			$product = $_GET["product"];
			
	
			// maj 12/11/2008 MPR : On récupère les na de la famille du produit concerné 
			$tab_na = getNaLabelList("na",$family,$product);
			if (get_axe3($family, $product))
				$tab_na_axe3 = getNaLabelList("na_axe3",$family,$product);
			
	
			// le comportement du sélecteur est le même que celui des dahsboard.
			$_SESSION["selecteur_general_values"]["list_of_na_mode"] = 'dashboard_normal';
	
			// 20/08/2007 christophe : correction bug si on vient d'un dash dans lequel la sélection des NA a été vidée.
			if ($mode_alarme == 'history')
				$_SESSION["selecteur_general_values"]["list_of_na"] = $_SESSION["network_element_preferences"];
	
			
			$file = $PHP_SELF;
			$family = (isset($_GET["family"])) ? $_GET["family"] : "";
	
			if ($mode_alarme == 'twcl')		$mode = "alarm";
			else 							$mode = "overtime";
	
			$module_restitution = "alarm";
	
			session_register("mode");
	
			$creator = $_GET["creator"];
	
			$axe3 = GetAxe3($family,$product);
	
			$IdPage				= $_GET["page"];
			$IdPageAlarm			= $IdPage;
			$id_menu_encours_alarm	= $page_encours;
			session_register("IdPageAlarm");
	
			if (isset($_GET["affichage_header"])) 	$affichage_header = $_GET["affichage_header"];
			else								$affichage_header = 1;
	
			$selecteur_scenario = $_GET["selecteur_scenario"];
	
			// maj 12/11/2008 - MPR : Génération du Sélecteur pour les alarmes Top-worst
			//-----------------------------------------------------------------------------------------------------------------------------------------//
			// 							Génération du sélecteur									      //
			//-----------------------------------------------------------------------------------------------------------------------------------------//
			
			// axe3 options : liste du premier menu select axe 3
			$axe3_options = $tab_na_axe3[$family];
			
			// NA levels : la liste des NA levels
			$na_levels = $tab_na[$family];
	
			// defaults values for this box : là encore, elle sont choisies "en dur", il faudra créer les requêtes permettant de connaître ces valeurs
			$defaults = array();
		
			// On récupère les ta du produit concerné 
			$ta_levels = getTaLabelList($product);
		
			// $day = date("d ")-1;
			// $date = date("d/m/Y");
			// __debug($date,"date");
			
			// defaults values for this box
			// 10:44 07/08/2009 GHX
			// Modification dont on crée la date sinon on a un problème si on est sur un dès 9 premier jour du mois
			// 15:45 20/08/2009 GHX
			// Correction du BZ 11084
			// Modification dont on crée l'heure sinon on n'a pas le format pour les heures entre 0 et 9 du matin
			$defaults = array(
				'ta_level'	=> 'Day',
				'date'	=> date("d/m/Y", mktime(0, 0, 0, date('m'), date('d')-1, date('Y'))),
				'hour' 	=> date("H", mktime(date('H')-1)).':00'
			);
	
				
			$selecteur	= new SelecteurTopWorstList($product,$family);
			$array_post	= getSelecteurParameters();
			
			$selecteur->getSelecteurFromArray($array_post); // Récupération des paramètres 
			$selecteur->setNaArray( $na_levels, $axe3_options, $defaults ); // Définition des na
			$selecteur->setTaArray( $ta_levels, $defaults ); // Définition des ta
			
			// 08:48 01/09/2009 GHX
			// Ajout des 2 variables JS sinon erreur JS quand on arrive sur la page des Top/Worst
			?>
			<script>
				_dashboard_id_page='';
				id_product='';
			</script>
			<?php
			$selecteur_general_values = $selecteur->build(); // Construction du sélecteur
			
			
			//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//

			$na		= $selecteur_general_values['na'];
			$ta		= $selecteur_general_values['ta_level'];
			$ta_value	= $selecteur_general_values['date'];
			
			$na_box	= (isset($selecteur_general_values['axe3'])) ? $selecteur_general_values['axe3'] : "";
			
			// modif 13/04/2007 Gwénaël
				// récupère les infos sur le 3° axe
						
		} else {
			$na_selection = new genDashboardNaSelection('all', $database_connection, 'interface_edition', true,$product);
		}

	}
	
	// le selecteur du mode Alarm History
	if ($mode_alarme == 'history') {
		// instanciation d'un sélecteur
		$selecteur = new selecteur();

		// on alimente le selecteur avec les valeurs postées ou getées
		$sel_params = getSelecteurParameters();
		if ($sel_params)
			$selecteur->setValues($sel_params);
		
		// valeurs par defaut
		// 15:45 20/08/2009 GHX
		// Correction du BZ 11084
		// Modification dont on crée l'heure sinon on n'a pas le format pour les heures entre 0 et 9 du matin
		$defaults = array(
			"ta_level"	=> 'day',
			"period"	=> 30,
			"date"	=> date('d/m/Y'),
			"hour"	=> date("H", mktime(date('H')-1)).':00'
		);
		$selecteur->setDefaults($defaults);
		
		// on ajoute la boite "time"
		$all_ta = getTaList('',$product);

                // 14/02/2011 OJT : DE Selecteur/Historique, gestion de l'historique max pour chaque TA
                $selecteur->max_period = ProductModel::getMaxHistory( $selecteur->selecteur['ta_level'], array( $product ) );
                foreach( $all_ta as $oneTA => $oneTAValue )
                {
                    if( stripos( $oneTA, 'bh' ) === false )
                    {
                        $selecteur->max_periods[strtolower($oneTA)] = ProductModel::getMaxHistory( $oneTA, array( $product ) );
                    }
                }
		$selecteur->addBox(
			__T('SELECTEUR_TIME'),	// titre de la boite
			'dashboard_time',		// type de la boite à ajouter (fichier box_dashboard_time.php)
			array($all_ta),			// informations à donner à la boite
			array('hide' => 'autorefresh')	// paramètres à donner à la boite ['hide'], ...
		);

		// on ajoute la boite "network aggregation"
		$hide = 'top,na_level';
		if (!get_axe3($family,$product)) {
			$hide.= '3emeaxe';
		}
		// on va chercher tous les na_levels du produit
		$sql = " --- get na levels
			SELECT DISTINCT t0.agregation_label, t0.agregation, t0.mandatory, t0.agregation_rank, t0.family , t0.axe
			FROM	sys_definition_network_agregation t0,
					sys_definition_group_table_network t1,
					sys_definition_group_table_ref t2 
			WHERE t0.agregation IS NOT NULL 
				AND t0.agregation<>'' 
				AND t0.on_off=1 
				AND t1.id_group_table = t2.id_ligne
				AND t0.axe IS NULL
				AND t0.agregation = split_part( t1.network_agregation, '_', 1)
			ORDER BY t0.agregation_rank desc ";
		$db_prod = Database::getConnection( $product );
		$na_levels_all = $db_prod->getall($sql);
				
		// on reprend maintenant tous les na_levels en dédoublonant :
		$na_levels = array();
		foreach ($na_levels_all as $row)
			if (!array_key_exists($row['agregation'],$na_levels))
				$na_levels[$row['agregation']] = $row['agregation_label'];

		$box_data[] = $na_levels;
		$selecteur->addBox(
			__T('SELECTEUR_NETWORK_AGGREGATION'),	// titre de la boite
			'topworst_list_NA',						// type de la boite (fichier box_topworst_list_NA.php)
			$box_data,							// informations à donner à la boite
			array('hide' => $hide,'product'=>"$product")	// paramètres à donner à la boite ['hide'], ...
		);

		// $selecteur->dump();
		// $selecteur->postDump();
		$selecteur->display();
	}
	
	
	// Si on est en mode management, la page doit se rafraîchir toutes les x secondes.
	if ($mode_alarme == 'management')
		echo "<meta http-equiv='refresh' content='".get_sys_global_parameters("alarm_management_autorefresh")."'>";


	// 24/07/2007 christophe : dans l'affichage des alarmes history et management ces valeurs sont égales car il n'y a pas de notions de familles.
	if ($mode_alarme == 'twcl') {
		$family = $_GET["family"];
		$product = intval($_GET["product"]);
				
		// 01/08/2007 christophe : ajout de l'icône de sélection des éléments réseaux dans le sélecteur des alarmes TWCL.
		//modif 13/04/2007 Gwénaël
		// maj 07/11/2008 MPR : Modification des paramètres en entrée de la classe 

		$alarm_screen = new alarmDisplayCreate_twcl($product, $na ,$na_box , $selecteur_general_values['ta_level'], $selecteur_general_values['date'] , $display, $family);
		// 01/08/2007 christophe : intégration de la gestion de la liste des éléments réseaux.
		//maj 14/11/2008 maxime : On supprime le paramètre $_SESSION["selecteur_general_values"] et on le remplace par $selecteur_general_values['nel_selecteur']
		$alarm_screen->setListOfNetworkElement($selecteur_general_values['nel_selecteur']);
		$alarm_screen->generateDisplay();
		
		
	} else {
		
		// 16:46 06/08/2009 GHX
		// $_SESSION["selecteur_general_values"]["list_of_na"] = $_SESSION["network_element_preferences"];
		
		// 05/12/2008 - SLC - hack pour récupérer les valeurs du network element selecteur
		// $_SESSION["selecteur_general_values"]["list_of_na"] = na@na_value@na_label|na@na_value@na_label|na@na_value@na_label...
        // 22/06/2010 OJT : Correction niveau NOTICE (ajout d'un test)
		if( ( !$_SESSION["selecteur_general_values"]["list_of_na"] ) && isset( $_GET['nel_selecteur'] ) )
			$_SESSION["selecteur_general_values"]["list_of_na"] = $_GET['nel_selecteur'];
		
		// 11/12/2008 - SLC - idem, incapable de recuperer les valeurs du network element selecteur pour le mode history
        // 22/06/2010 OJT : Correction niveau NOTICE (ajout d'un test)
		if( ( !$_SESSION["selecteur_general_values"]["list_of_na"] ) && isset( $_POST['selecteur']['nel_selecteur'] ) )
			$_SESSION["selecteur_general_values"]["list_of_na"] = $_POST['selecteur']['nel_selecteur'];
		
		// Création de la sélection des NA.
		//$na_selection = new genDashboardNaSelection('all', $database_connection, 'interface_edition', true);
		$ok_to_display = true;
		if($mode_alarme == "history" && $selecteur_general_values == null ) $ok_to_display = false;
	
		if ($ok_to_display) {

			/*
				Dans alarm management, l'utilisateur change les timestamp qu'il souhaite afficher.
				Afin de conserver les time aggregation choisies par l'utilisateur quand il y a un rechargement
				automatique de la page de résultats des alarmes, on conserve les TA sélectionnées dans une variable de session.
			*/
			if(isset($_GET["cb_ta"]))
			{
				foreach($_GET["cb_ta"] as $ta_to_display) $ta .= "'".$ta_to_display."',";
				$ta = substr($ta,0,-1);
			}


			// ici on va analyser la valeur de TA reçue par le selecteur pour alarm_history
			if ($mode_alarme == 'history') {
				$ta		= $selecteur->selecteur['ta_level'];
				$period	= $selecteur->selecteur['period'];
				
				// on calcule $ta_value
				$date = $selecteur->selecteur['date'];
				switch ($ta) {
					case 'hour' :	// 12/11/2008 15:00	=> 2008111215
						$hour = substr($selecteur->selecteur['hour'], 0, 2);
						list($day,$month,$year) = explode('/',$date);
						$ta_value = $year.$month.$day.$hour;
					break;
						
					case 'day':		// 12/11/2008		=> 20081112
					case 'day_bh':			
						list($day,$month,$year) = explode('/',$date);
						$ta_value = $year.$month.$day;
					break;
						
					case 'week' :	// W46-2008		=> 200846
					case 'week_bh' :
						$week = substr($date, 1, 2);
						$year = substr($date, 4, 4);
						$ta_value = $year.$week;
					break;
						
					case 'month' :	// 03/2008 		=> 200803
					case 'month_bh' :
						list($month,$year) = explode('/',$date);
						$ta_value = $year.$month;
					break;
				}
			}	
			
			$alarm_screen = new alarmDisplayCreate($database_connection, $na, $ta, $ta_value, $display, $mode_alarme, $sous_mode_alarme, $timestamp, $product,$period);
			// echo "new <strong>alarmDisplayCreate</strong>(db_cnx=$database_connection, na=$na, ta=$ta, ta_value=$ta_value, display=$display, mode_alarme=$mode_alarme, sous_mode=$sous_mode_alarme, timestamp=$timestamp, product=$product, period=$period);";
			// maj 10/03/2008 christophe : initialisation de la variable order_on
			$alarm_screen->setOrderOn($order_on);
			
			// 19/08/2009 BBX : On passe la variable GET au lieu de la variable de session à la fonction setListOfNetworkElement. BZ 11117
			// 17/09/2009 BBX : si la variable GET est vide, on utilise la variable POST. BZ 10584
            // 22/06/2010 OJT : Correction niveau NOTICE (ajout d'un test)
			if( empty( $_GET['nel_selecteur'] ) && isset( $_POST['selecteur'] ) ) {
				$_GET['nel_selecteur'] = $_POST['selecteur']['nel_selecteur'];
			}
			
			$alarm_screen->setListOfNetworkElement($_GET['nel_selecteur']);
			$alarm_screen->period = $period -1;
			$alarm_screen->getDataToDisplay();	// On récupère les données à afficher.

			// Si c'est le mode history, je retourne le tableau contenant la liste des données pour générer le graph de cyrille.
			if ($mode_alarme == 'history') {
				$alarm_screen->getGraphDataToDisplay(); // On exécute les requête de génération des données du graph.
				$alarm_screen->generateGraph_data();	// génère la tableau graph_data.
				echo "<div>"; // le div est centré automatiquement

					if ($alarm_screen->debug) {
						echo "<u>période :</u> ".$alarm_screen->period."<br>";
						echo "<u>tableau </u>:";
							var_dump($alarm_screen->graph_data);
						echo "<br><u>parcours du tableau</u><br>";
						echo "<div>";
					}

					// Parcours du tableau graph_data.
					if (is_array($alarm_screen->graph_data)) {
						// on boucle sur les seuils
						foreach ($alarm_screen->graph_data as $seuil=>$tab_alarm_type) {
							if ($alarm_screen->debug) echo "<strong>seuil : $seuil </strong><br>";

							foreach($tab_alarm_type as $alarm_type=>$tab_na) {
								if ($alarm_screen->debug) echo "<u>$alarm_type</u> | ";

								foreach($tab_na as $na=>$tab_ta) {
									if ($alarm_screen->debug) echo "$na | ";

									foreach($tab_ta as $ta=>$tab_ta_value) {
										if ($alarm_screen->debug) echo "$ta | ";

										foreach($tab_ta_value as $ta_value=>$nb_resultats) {
											if ($alarm_screen->debug) echo "$ta_value | $nb_resultats résultats<br/>";

											// on calcule le total pour une date d'un seuil
											if (isset($nb_alarm_result_pre[$seuil][$ta_value]))
												$nb_alarm_result_pre[$seuil][$ta_value] += $nb_resultats;
											else
												$nb_alarm_result_pre[$seuil][$ta_value] = $nb_resultats;

											// on calcule le total pour un seuil
											if (isset($nb_alarm_result_total[$seuil]))
												$nb_alarm_result_total[$seuil] += $nb_resultats;
											else
												$nb_alarm_result_total[$seuil] = $nb_resultats;
											
										}
									}
								}
							}
						}
					}
					
					// Listes des dates en fonction de la période / TA / TA_VALUE
					$liste_dates = getDateList($alarm_screen->ta_value, $alarm_screen->ta, $alarm_screen->period,$ta_value);
					if($alarm_screen->debug){
						echo "<br>Tableau contenant la liste des dates :<br>";
						echo "<pre>";
							print_r($liste_dates);
						echo "</pre>";
					}

					// On comble les trous pour le graph.
					if (!isset($nb_alarm_result_pre["critical"])) 	$nb_alarm_result_pre["critical"]	= Array();
					if (!isset($nb_alarm_result_pre["major"])) 	$nb_alarm_result_pre["major"]		= Array();
					if (!isset($nb_alarm_result_pre["minor"])) 	$nb_alarm_result_pre["minor"]		= Array();

					if (is_array($nb_alarm_result_pre)) {
						foreach ($nb_alarm_result_pre as $seuil=>$tab) {
							if (is_array($liste_dates)) {
								foreach ($liste_dates as $date=>$val)
									if (!isset($tab[$date]))
										$tab[$date] = 0;
								$tab_temp[$seuil] = $tab;
							}
						}
					}
					$nb_alarm_result_pre = $tab_temp;

					// On rempli les trou pour le camembert.
					if (!isset($nb_alarm_result_total["critical"]))	$nb_alarm_result_total["critical"]	= 0;
					if (!isset($nb_alarm_result_total["major"]))	$nb_alarm_result_total["major"]	= 0;
					if (!isset($nb_alarm_result_total["minor"]))	$nb_alarm_result_total["minor"]	= 0;

					// On Tri chaque sous tableau (cad : on ré-ordonne les dates)
					if (is_array($nb_alarm_result_pre)) {
						foreach ($nb_alarm_result_pre as $seuil=>$tab) {
							ksort($tab);
							$nb_alarm_result[$seuil] = $tab;
						}
					}

					/*
					if($alarm_screen->debug){
						echo "<div style='text-align:left'>";
						echo "<strong>Tableau nb_alarm_result</strong><pre>";
						print_r($nb_alarm_result);
						echo "</pre><strong>Tableau nb_alarm_result_total</strong><pre>";
						print_r($nb_alarm_result_total);
						echo "</pre></div>";
					}
					*/

					// echo "<br />graphHist= new alarmGraphHistory(nb_alarm_result=$nb_alarm_result,nb_alarm_result_total=$nb_alarm_result_total,ta=$ta,alarm_screen->period=$alarm_screen->period,ta_value=$ta_value);";
					$graphHist= new alarmGraphHistory($nb_alarm_result,$nb_alarm_result_total,$ta,$alarm_screen->period,$ta_value);

				//echo "</div>";

			}

			echo '<br/>';
			$alarm_screen->generateDisplay();	// On affiche les tableaux des alarmes

		}

	}
	
	/*
	Fonctions
	Retourne un tableau contenant la liste de toutes les dates à afficher à partir de la date
	ta_value selon la période $period.
	*/
	function getDateList($ta_value, $ta, $period){
		switch ($ta){
			case "hour" :
				for($i=0; $i <= $period; $i++){
					$date_format = "YmdH";
					$unixdate = mktime(substr($ta_value, 8, 2), 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2), substr($ta_value, 0, 4));
					$interval_value = $i * 3600;

					$time = date($date_format, $unixdate - $interval_value);
					$tab[$time] = $time;
				}
				break;
			case "day" :
			case "day_bh" :
				for($i=0; $i <= $period; $i++){
					$date_format = "Ymd";
					$unixdate = mktime(6, 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2), substr($ta_value, 0, 4));
					$interval_value = $i * 24 * 3600;

					$time = date($date_format, $unixdate - $interval_value);
					$tab[$time] = $time;
				}
				break;
			case "week" :
			case "week_bh" :
				for($i=0; $i <= $period; $i++){
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
					$date_format = "oW";
					// On récupère le dernier jour de la semaine damandée.
					// 23/02/2010 NSE : remplacement GetLastDayFromAcurioWeek($week) par Date::getLastDayFromWeek($week,$firstDayOfWeek=1)
					$dernier_jour = Date::getLastDayFromWeek($ta_value,get_sys_global_parameters('week_starts_on_monday',1));
					$unixdate = mktime(0, 0, 0, substr($dernier_jour, 4, 2), substr($dernier_jour, 6, 2) - ($i * 7), substr($dernier_jour, 0, 4));

					$time = date($date_format, $unixdate);
					$tab[$time] = $time;
				}
				break;
			case "month" :
			case "month_bh" :
				for($i=0; $i <= $period; $i++){
					$date_format = "Ym";
					$unixdate = mktime(6, 0, 0, substr($ta_value, 4, 2) - $i, 1, substr($ta_value, 0, 4));

					$time = date($date_format, $unixdate);
					$tab[$time] = $time;
				}
				break;
		}

		return $tab;
	}

?>
		</div>
        </body>
</html>
