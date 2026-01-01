<?php
/**
* Fichier permettant l'affichage IHM de Source Availability
* Date 29/04/2010
* Autor : YNE
*/
?>
<?php
 /**
  *     @cb_v5.0.3
  *
  *     maj 09/07/2010 - MPR : Correction du bz 16182 La ta value n'est pas transmise dans la fenêtre SA
  *     maj 15/09/2010 - MPR : Correction du bz 17848 Transmission de la ta value non valide en week
  *     12/10/2010 NSE bz 18454, 18467 : gestion des cas d'affichage de l'icône du SA et du produit sélectionné
  *
  */
?>
<?php
if(!isset($_SESSION)) session_start();

// inclusion des classes et des fichiers nécessaires
include_once("../php/environnement_liens.php");
include_once("./class/SA_IHM.class.php");
include_once("./class/SA_Calculation.class.php");

$productid = (isset($_REQUEST["productid"]))? $_REQUEST["productid"] : 1;
$ta_value  = (isset($_REQUEST["ta_value"]))? $_REQUEST["ta_value"] : "";

// 20/08/2012 BBX
// BZ 22764 : Si aucun produit n'est sélectionné, on prend le master
if(empty($productid)) {
    $productid = ProductModel::getIdMaster();
}

if( $ta_value == "" )
{
    // maj 09/07/2010 - MPR : Correction du bz 16182 La ta value n'est pas transmise dans la fenêtre SA
    $ta_value = (isset($_SESSION['TA']['selecteur']['ta_value'] ))?$_SESSION['TA']['selecteur']['ta_value'] : date('Ymd', mktime(0,0,0, date('m'), date('d')-1, date('Y')));
}

// Par défaut, on affiche les données day
$ta_mode = (isset($_REQUEST["ta_mode"]))? $_REQUEST["ta_mode"] : "day";

// Mode d'affichage des errors only or all
$errors = (isset($_REQUEST["show_errors"]))? $_REQUEST["show_errors"] : 1;
$first_load = (isset($_REQUEST["first_load"]))? $_REQUEST["first_load"] : 0;

if($first_load && $_SESSION['profile_type'] == 'user' && isset($_REQUEST['product_selected']) && $_REQUEST['product_selected'] != '')
	$productid = $_REQUEST['product_selected'];

$source_avail = new SaIHM($productid);

$source_avail->setConnexions($productid);
$source_avail->sortByMode($errors);

// récupère la liste des id des connections pour le produit en cours
$connect_list = array();
foreach($source_avail->connexions as $connect)
	$connect_list[] = $connect['id_connection'];


// Si l'on est connecté en user, que l'on est sur un dashboard en vue hour, et que cette vue est possible avec SA
// alors on affiche la vue Hour pour la ta_value sélectionnée dans le sélecteur
if($first_load && $_SESSION['profile_type'] == 'user' && $_SESSION['TA']['selecteur']['ta'] == 'hour' && $source_avail->checkHourView($connect_list)){
	$source_avail->setTa('hour');
	$source_avail->setTaValue(substr($_SESSION['TA']['selecteur']['ta_value'], 0, 8));
}
else{
        // maj 15/09/2010 - MPR : Correction du bz 17848
        // Transmission de la ta value non valide en week
        // 22/01/2013 BBX
        // BZ 31413 : correction de la gestion du week
        if($_SESSION['TA']['selecteur']['ta'] == 'week' && (strlen($ta_value) == 6)) {
            $ta_value = Date::getFirstDayFromWeek($_SESSION['TA']['selecteur']['ta_value']);
        }
        $source_avail->setTa($ta_mode);
        $source_avail->setTaValue($ta_value);
}

$id_product_user_page = $source_avail->searchSaActivatedOnProduct();

// Affiche les infos pour le produit en cours, redirection vers la bonne page
if($first_load && $_SESSION['profile_type'] == 'user' && $id_product_user_page!=0 && $id_product_user_page != $productid ){
	echo "<script>";
	echo "document.location.href=\"index.php?productid=".$id_product_user_page."&ta_mode=".$ta_mode."&ta_value=".$ta_value."&show_errors=".$errors."\"";
	echo "</script>";
}
?>
<html>
<head>
	<title>Source Availability</title>
	<script type="text/javascript" src="../js/prototype/prototype.js"></script>
	<script type="text/javascript" src="../js/prototype/window.js"></script>
	<script type="text/javascript" src="functions_javascript.js"></script>
	<script type="text/javascript" src="../js/fenetres_volantes.js"></script>
	<link href="./css/styles.css" rel="stylesheet" type="text/css"/>
</head>

<body>

	<div id="bloc">
		<div id="form">
			<?php echo $source_avail->constructHeader(); ?>
		</div>

		<?php // 12/10/2010 NSE bz 18454, 18467 : ajout d'une condition pour l'affichage des données
                if($source_avail->checkParserCompatible() && $productid!=0) { ?>

		<div id="table_header">
			<?php echo $source_avail->constructLegendDate(); ?>
		</div>
		<div id="table_connex">
			<?php echo $source_avail->constructTable(); ?>
		</div>
		<div id="legend">
			<?php echo $source_avail->constructLegend(); ?>
		</div>

		<?php } else { ?>
		<div id="No_data_available" align="center">No data available</div>
		<?php
				}

		?>

	</div>

</body>
</html>