<?php
/*
	17/07/2009 GHX
		- Ajout de l'id produit dans l'instance de la classe alarm pour pouvoir afficher les détails d'une alarme d'un produit slave
*/
?>
<?
/*
*	@cb41000@
*
*	04/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- maj 03/12/2008 - SLC - gestion multi-produit, suppression de $database_connection
*
*	17/06/2009 BBX :
*	=> Constantes CB 5.0
*	=> Header CB 5.0
*/
?><?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*Bugs de qualif
*	- 23/08/2007 - JL : 	- Inclusion des fichier CSS et JS pour l'affichage des Tooltip
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
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php

/*
  12/10/2006 xavier : modification du système d'envoi des données pour l'onglet résultat (variables de session)
                      modification de la requête de selection de l'alarme
  25/10/2006 xavier : modification du système d'envoi des données pour l'onglet résultat. (méthode get)
                      on envoie plus que oid_alarm, alarm_type, alarm_id et family.
*/

session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/setup_alarm.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");

// on récupère l'oid d'une ligne de calcul d'alarme
$oid_alarm=$_GET['oid_alarm'];

// 04/12/2008 - SLC - connexion à la base produit
$product = intval($_GET['product']);
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$db_prod = Database::getConnection($product);


$debug = get_sys_debug('alarm_detail');

// 12/10/2006 xavier
// on récupère les informations générales concernant l'alarme
$query = "SELECT DISTINCT
          alarm_type,
          id_alarm,

          CASE WHEN alarm_type='static'
          THEN (SELECT distinct family FROM sys_definition_alarm_static WHERE alarm_id = id_alarm LIMIT 1)
          ELSE
            CASE WHEN alarm_type='dyn_alarm'
            THEN (SELECT distinct family FROM sys_definition_alarm_dynamic WHERE alarm_id = id_alarm LIMIT 1)
            ELSE (SELECT distinct family FROM sys_definition_alarm_top_worst WHERE alarm_id = id_alarm LIMIT 1)
            END
          END as family

          FROM edw_alarm
          WHERE edw_alarm.OID=$oid_alarm";

//echo "<pre>".$query."</pre>";
$row = $db_prod->getrow($query);

// on créer un message d'erreur si aucune information ne correspond à cette oid
if (!$row)
	$errorMsg = "<div class='texteRougeBold' style='text-align:center'>Warning : Alarm Calculation in progress<br><br>Please, close this popup and refresh<br><br></div>";

// on récupère les variables
// (par défaut, alarm_type est à 'alarm_static')
$family	= $row["family"];
$alarm_id	= $row['id_alarm'];

if ($row["alarm_type"] == 'static')
	$alarm_type = 'alarm_static';
else
	if ($row["alarm_type"] == 'dyn_alarm')
		$alarm_type = 'alarm_dynamic';
	else
		$alarm_type = 'alarm_top_worst';


// on inclue la classe correspondant au type d'alarme
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/setup_".$alarm_type.".class.php");

//on enregistre le nom de la table utilisée par ce type d'alarme
$sys_definition_alarm_table = 'sys_definition_'.$alarm_type;

// determine si la famille possède un troisième axe
$flag_axe3 = GetAxe3($family,$product);

$lien_css = $path_skin . "easyopt.css";

$comeback = $PHP_SELF;
session_register("comeback");

// DEBUT PAGE
$arborescence = 'Alarm Detail';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<script type="text/javascript" src="<?=NIVEAU_0?>js/tab-view-ajax.js"></script>
<script type="text/javascript" src="<?=NIVEAU_0?>js/tab-view.js"></script>
<script type="text/javascript" src="<?=NIVEAU_0?>js/myadmin_omc.js"></script>
<link rel="stylesheet" type="text/css" href="<?=NIVEAU_0?>css/prototype_window/default.css" />
<link rel="stylesheet" type="text/css" href="<?=NIVEAU_0?>css/prototype_window/alphacube.css" />
<div id="container" style="width:100%;text-align:center">
<script>
var strictDocType = false;

/*
	17/07/2009 GHX
		- Modif : utilisation de prototype
*/
function alarm_to_panier ( _id_user, _titre, _from )
{
	new Ajax.Request("<?=NIVEAU_0?>php/alarm_to_panier.php", {
		method: 'GET',
		parameters: {'id_user': _id_user, 'titre': _titre, 'from': _from}
	});
}
</script>
<link rel="stylesheet" href="<?=NIVEAU_0?>css/tab-view.css" type="text/css" media="screen">
<style>
hr{
margin-bottom: 5px;
color: #929292;
background-color: #929292;
height: 2px;
border: 0;
}
h1{
font : bold 12pt Trebuchet MS,Verdana, Arial, sans-serif;
color : #929292;
height: 20px;
margin: 0px;
}
th{
color: #fff;
background-color : #929292;
font : normal 9pt Verdana, Arial, sans-serif;
text-align: center;
}
.entete{
color: #fff;
background-color : #929292;
font : bold 9pt Verdana, Arial, sans-serif;
text-align: center;
}
</style>
</head>
<body bgcolor="#fefefe" leftmargin="0" topmargin="0">

<table width="550px" align="center">
	<tr>
		<td>
			<?php
				// 16:14 17/07/2009 GHX
				// Ajout de l'id produit
				$alarm = new alarm($family, $alarm_id, $alarm_type,$sys_definition_alarm_table,1,$product);
				// FUTUR : quand on aura modifié les classes alarm_dynamic.class.php, alarm_static.class.php, alarm_top_worst.class.php
				// on utilisera cet appel :
				// $alarm = new alarm($family, $alarm_id, $alarm_type,$sys_definition_alarm_table,1, $product);
			?>
		</td>
	</tr>
</table>

<script>
createNewTab('alarm_tab_view','Results','','alarm_detail_results.php?oid_alarm=<?=$oid_alarm?>&alarm_type=<?=$alarm_type?>&alarm_id=<?=$alarm_id?>&family=<?=$family?>&product=<?=$product?>')
</script>
</div>
</body>
</html>
