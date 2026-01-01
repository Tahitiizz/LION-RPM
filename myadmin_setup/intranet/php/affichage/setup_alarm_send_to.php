<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	29/01/2009 GHX
*		- modification des requetes SQL pour mettre l'id_alarm entre cote [REFONTE CONTEXTE]
*/
?>
<?php
/**
* Gère les données de paramétrage qui servent à connecter l'application
* aux bases de données tiers et répertoire racine qui contient des flat file
*
*
* 24/05/2006 - BA :	inhibition de la selection de la Time Period. Celle-ci sera auto. selectionnée
*					à partir de la periodicité définie pour l'alarme dans 'sys_definition_alarm_static'
*
*/
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

$comeback=$PHP_SELF;
session_register("comeback");
//recup des variables potentiellement postées

// Récupération des variables.
$product		= $_GET['product'];
$alarm_id		= $_GET['alarm_id'];

// on se connecte à la db
$db = Database::getConnection( $product );


set_time_limit(15);

// on va chercher les infos de l'alarme statique
$query		= "SELECT * FROM sys_definition_{$_GET['alarm_type']} WHERE alarm_id='$alarm_id'";
$ze_alarm		= $db->getrow($query);
if (!$ze_alarm) {
	if ($_GET['alarm_type'] == 'alarm_static')		$alarm_type = 'static alarm';
	if ($_GET['alarm_type'] == 'alarm_dynamic')	$alarm_type = 'dynamic alarm';
	echo "Script Error: no $alarm_type found with alarm_id='$alarm_id' ";
	exit;
}

?>
<html>
<title>Static Alarm Send-to Set-up</title>
<script src="<?=$niveau0?>js/myadmin_omc.js"></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
<link rel="stylesheet" type="text/css" media="all" href="<?=$niveau0?>css/global_interface.css" />
</head>
<body leftmargin="0" topmargin="0">
<table width="550" align="center" valign=middle cellpadding="5" cellspacing="0">
	<tr>
		<td align="center"><img src="<?=$niveau0?>images/titres/static_alarm_setup_interface_titre.gif"/></td>
	</tr>
	<tr>
		<td style="padding:20px;" align="center">
			<table cellpadding="3" cellspacing="3" align="center" class="tabPrincipal">
			<tr>
			<td>


<?

// Initialize the phpObjectForms class
require $repertoire_physique_niveau0."class.phpObjectForms/lib/FormProcessor.class.php";
$fp = new FormProcessor($repertoire_physique_niveau0."class.phpObjectForms/lib/");
$fp->importElements(array("FPButton", "FPHidden", "FPSelect", "FPText", "FPTextField",'extra/FPSplitSelect'));
$fp->importLayouts(array("FPColLayout", "FPRowLayout", "FPGridLayout"));
$fp->importWrappers(array( "FPLeftTitleWrapper" ));
$leftWrapper = new FPLeftTitleWrapper(array());

//
// 0. Create the form object
//
	$myForm = new FPForm(array(
	//    "title" => 'Field selector',
	    "name" => 'myForm',
	    "action" => 'setup_alarm_send_to_traitement.php',
		"display_outer_table" => true,
		"table_align" => 'center',
		"enable_js_validation" => false,
	));

//
// 1. Form data structure
//
	// HIDDEN : product
	$formel_product = new FPHidden(array(
		"name" => "product",
		"value" => $product,
	));

	// HIDDEN : alarm_id
	$formel_alarm_id = new FPHidden(array(
		"name" => "alarm_id",
		"value" => $ze_alarm['alarm_id'],
	));

	// HIDDEN : alarm_type
	$formel_alarm_type = new FPHidden(array(
		"name" => "alarm_type",
		"value" => $_GET['alarm_type'],
	));

	// the split selector for groups
		// we look for the list of all the groups in the db
		$list_all_groups = array();
		$query="SELECT DISTINCT id_group,group_name FROM sys_user_group WHERE on_off=1 ORDER BY group_name ASC";
		$get_users = $db->getall($query);
		if ($get_users)
			foreach ($get_users as $current_user)
				$list_all_groups[$current_user['id_group']] = $current_user['group_name'];

		// we look for the list of subscribed groups in the db
		$list_subscribed_groups = array();
		$query = "SELECT id_group from sys_alarm_email_sender where id_alarm='{$ze_alarm['alarm_id']}' AND alarm_type='$alarm_type'";
		$get_subscribed = $db->getall($query);
		if ($get_subscribed)
			foreach ($get_subscribed as $row)
				$list_subscribed_groups[] = $row['id_group'];

		// we create the split select
		$form_element_group_selector = new FPSplitSelect(array(
			"title" => "<span class='texteGrisBold'>Group selector</span>",
			"name" => "to_groups",
			"form_name" => 'myForm',
			"multiple" => true,
			"size" => 10,
			"options" => $list_all_groups,
			"left_title" => "<span class='texteGrisBold'>Available groups</span>",
			"right_title" => "<span class='texteGrisBold'>Subscribed groups</span>",
			"right_ids" => $list_subscribed_groups,
			"css_style" => "width:300px;",
			"table_padding" => 5,
		));


	/*// SELECT : time_aggregation
		// on construit le tableau des options
		$time_options = array();
		$query = 'SELECT agregation,agregation_label
			FROM sys_definition_time_agregation
			WHERE on_off=1 and visible=1 and primaire=1
			ORDER BY agregation_rank ASC';
		$get_options = pg_query($database_connection,$query);
		while ($row = pg_fetch_array($get_options)) {
			$time_options[$row['agregation']] = $row['agregation_label'];
		}
		// on cherche si le niveau d'agregation temporelle est deja choisi
		$query 	= "SELECT time_aggregation FROM sys_alarm_email_sender WHERE id_alarm=".$ze_alarm['alarm_id'];
		$res 	= pg_query($database_connection,$query);
		if (pg_num_rows($res)) {
			$row = pg_fetch_array($res);
			$ze_alarm['time_aggregation'] = $row['time_aggregation'];
		}
		// on cree le menu select
		$formel_time_aggregation = new FPSelect(array(
			"name" => 'time_aggregation',
			"title" => "<span class=texteGris>Time period</span>",
			"multiple" => false,
			"options" => $time_options,
			"selected" => array($ze_alarm['time_aggregation']),
			"wrapper" => &$leftWrapper,
		));*/


	// SUBMIT
	$formel_submit_button = new FPButton(array(
		"submit" => true,
		"name" => 'submit',
		"title" => 'Save',
		"css_class" => 'bouton',
	));

//
// 3. Form layout
//



	$myForm->setBaseLayout(new FPColLayout(array(
		"table_align"=>'center',
		'table_padding'=>10,
		"elements" => array(
			$form_element_group_selector,
			$formel_time_aggregation,
			new FPRowLayout(array("table_align"=>'center','table_padding'=>20,"elements" => array($formel_submit_button))),
			$formel_product,
			$formel_alarm_id,
			$formel_alarm_type,
			)
	)));

// On affiche le formulaire
$myForm->display();



?>

			</td>
			</tr>
			</table>
		</td>
	</tr>
</table>

</body>
</html>
