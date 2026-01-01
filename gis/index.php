<?php
/*
 *  cb50400
 *
 *  18/08/2010 NSE DE Firefox bz 17384 : suppression du fond de la barre de titre et fond blanc pour la fenêtre
 */
?><?php
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *
	- maj 26/11/2007, benoit : insertion d'un selecteur dans le GIS
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
<?php

	session_start();

	include_once("../php/environnement_liens.php");

	// 26/11/2007 - Modif. benoit : insertion d'un selecteur dans le GIS

	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");

?>

<HTML>
<HEAD>
	<TITLE>GIS</TITLE>
	<LINK REL="stylesheet" HREF="<?=NIVEAU_0?>/gis/css/gis_styles.css" TYPE="text/css">

	<script type="text/javascript" src="<?=NIVEAU_0?>/js/prototype/prototype.js"> </script>
	<script type="text/javascript" src="<?=NIVEAU_0?>/js/prototype/window.js"> </script>
	<script type="text/javascript" src="<?=NIVEAU_0?>/js/prototype/scriptaculous.js"> </script>
	<link href="<?=NIVEAU_0?>/css/prototype_window/default.css" rel="stylesheet" type="text/css"/>
	<link href="<?=NIVEAU_0?>/css/prototype_window/alphacube.css" rel="stylesheet" type="text/css"/>
	
	<link rel="stylesheet" href="<?=NIVEAU_0?>/css/selection_des_na_recherche.css" type="text/css">
	<script language="JavaScript1.2" src="<?=NIVEAU_0?>/js/selection_des_na_recherche.js"></script>


	<script type='text/javascript' src='<?=NIVEAU_0?>/js/ajax_functions.js'></script>
	<script>
		link_to_ajax="<?=NIVEAU_0?>/reporting/intranet/php/affichage/";
	</script>
	<link rel="stylesheet" href="<?=NIVEAU_0?>/css/global_interface.css" type="text/css">
	<link rel="stylesheet" href="<?=NIVEAU_0?>/css/pauto.css" type="text/css">
	<link rel="stylesheet" href="<?=NIVEAU_0?>/css/selection_na.css" type="text/css">

	<SCRIPT TYPE="text/javascript" src="<?=NIVEAU_0?>/gis/gis_scripts/bouger_fenetre.js"></SCRIPT>

	<script>
			setLinkToAjax('<?=NIVEAU_0."reporting/intranet/php/affichage/"?>');
	</script>
	
	<SCRIPT TYPE="text/javascript" src="<?=NIVEAU_0?>/gis/gis_scripts/index_functions.js"></SCRIPT>

</HEAD>

<BODY onload="doOnLoad()">

<?php
// 29/01/2013 BBX
// DE Filtering GIS : permet de définir le mode du GIS (supervision ou depuis un graph)
$_SESSION['gis_calling_method'] = 'dash';
if(empty($_GET['gis_data'])) {
    $_SESSION['gis_calling_method'] = 'super';
}

$gis_data_tmp = explode('|@|', $_GET['gis_data']);

$product = ( isset($_GET['product']) ) ? $_GET['product'] : $product;

$database_connection = DataBase::getConnection( $product );

if ($gis_data_tmp[4] == "alarm") 
{
	
?>
	<script>
		function doOnLoad(){

			setDivGisPosition();
			// $('onglet').style.display = 'none';
			showGIS('<?=urlencode($_GET['gis_data'])?>');
		}
	</script>
<?php

	// On recupere les informations affichees dans le bandeau alarme

	switch ($gis_data_tmp[5]) {
		case 'static' :
			$alarm_type		= 'static';
			$alarm_table	= 'sys_definition_alarm_static';
		break;
		case 'dyn_alarm' :
			$alarm_type		= 'dynamic';
			$alarm_table	= 'sys_definition_alarm_dynamic';
		break;
		case 'top-worst' :
			$alarm_type		= 'top-worst';
			$alarm_table	= 'sys_definition_alarm_top_worst';
		break;
	}

	
	$sql =	 " SELECT al.alarm_name, fam.family_label FROM $alarm_table al, sys_definition_categorie fam"
			." WHERE al.alarm_id = '{$gis_data_tmp[9]}' AND al.family = fam.family LIMIT 1";

	$req = $database_connection->getAll($sql);

	if (count($req) > 0) {
		$row = $req[0];
		$alarm_name = $row['alarm_name']." [".$alarm_type.", ".$row['family_label']."]";
	}

	$alarm_time = getTaValueToDisplayV2($gis_data_tmp[7], $gis_data_tmp[8], "-");
	
	echo '<div id="div_alarm_info" style="font:normal 9pt Verdana, Arial, sans-serif;font-weight:bold;color:#929292;background-color:#FFFFFF;" align="center">Alarm name : '.$alarm_name.' - Calculation Time : '.$alarm_time.'</div>';

}
else 
{
	include 'selecteur_gis.php';

	// on creer le filtre
	// $na_selection = new genDashboardNaSelection($family, $database_connection, 'dashboard_normal');

	/*
		21/08/2007 christophe : reinitialisation de la variable $_SESSION["selecteur_general_values"]["list_of_na"] en fin de page,
		sinon cela fait planter la selection des NA dans Myprofile.
	*/
	//$_SESSION["selecteur_general_values"]["list_of_na"] = $_SESSION["network_element_preferences"];

	// include_once(REP_PHYSIQUE_NIVEAU_0 ."class/selecteurGeneralValues.class.php");
	// include_once(REP_PHYSIQUE_NIVEAU_0 ."php/selecteur.php");
	// __debug($_GET['gis_data'],"GIS data"); 
	// __debug($gis_data,"GIS data"); 

	
?>


<script>
	function doOnLoad(){
			<?
			
			if ( !$selecteur_open ){
			
				// $params = $_GET['gis_data'];
				echo "$(\"selecteur_container\").style.display = \"none\";";
				echo "$(\"onglet_filter\").src = \"".NIVEAU_0."images/boutons/onglet_show_filter.gif\";";
				
			}
			
			$params = implode("|@|",$gis_data);
			$params = urlencode( $params );
			
		
		?>
		
		setDivGisPosition();

		showGIS('<?=  $params ?>');
	}
</script>
<?php
}
?>
<style>
#selecteur_fieldset_time {	width: 195px; }
</style>
<div id="div_gis" style="z-index:101">
	<div id="div_content" style="position:absolute;left:100px;top:0px;width:0px;height:0px;z-index:102">
		<TABLE width="97%" height="100%" cellpadding="0" cellspacing="0" style="width:100%;height:100%" align="center">
			<TR>
				<TD colspan="3">
					<div id="gis_content" style="z-index:103">
					<IFRAME id="gis_window" NAME="gis_window" SRC="<?=NIVEAU_0?>/gis/gis.php" align="center" frameborder="0" scrolling="no" style="border:0">
					</IFRAME>
					</div>
				<TD>
			</TR>
		</TABLE>
	</div>
	
	<!-- DIV de la fenetre de legende (data ranges) -->
	<!-- 18/08/2010 NSE DE Firefox bz 17384 : suppression du fond de la barre de titre et fond blanc pour la fenêtre -->
	<div id="legend_title" style="position:absolute;z-index:105;cursor:move;display:none;" onmousemove="deplacer(event);" onmouseover="deplacer(event)" onmousedown="cliquer(this.id, 'legend', event);setZindex(this.id, 'legend');lockGIS(true)" onmouseup="lacher();lockGIS(false)">
	</div>
	<div id="legend" style="position:absolute;z-index:104;visibility:hidden;width:200px;height:300px;">
		<table width="200px" border="0" cellspacing="0" cellpadding="0" class="map_style">
			<tr valign="center">
				<td bgcolor="#b5b4b3" align="center" width="12%">
					<input type="image" src="<?=NIVEAU_0?>/gis/gis_icons/icone_gis_window.gif">
				</td>
				<td style="font-family: Arial, Helvetica, sans-serif;font-size: 8pt;color: #FFFFFF" bgcolor="#b5b4b3" width="78%">Legend</td>
				<td bgcolor="#b5b4b3" align="center" width="10%">
					<img src="<?=NIVEAU_0?>/gis/gis_icons/close.gif" onclick="showLegend()" style="cursor:pointer"/>
				</td>
			</tr>
			<tr>
				<td colspan="3">
					<iframe id="map_legend" name="map_legend" src="<?=NIVEAU_0?>/gis/gis_scripts/gis_legend.php" width="200" height="300" align="center" frameborder="0" scrolling="auto" style="border:0">
					</iframe>
				</td>
			</tr>
		</table>
	</div>

	<!-- DIV de la fenetre de gestion des layers -->
	<!-- 18/08/2010 NSE DE Firefox bz 17384 : suppression du fond de la barre de titre -->
	<div id="layers_title" style="position:absolute;z-index:107;cursor:move;display:none;" onmousemove="deplacer(event)" onmouseover="deplacer(event)" onmousedown="cliquer(this.id, 'layers', event);setZindex(this.id, 'layers');lockGIS(true)" onmouseup="lacher();lockGIS(false)">
	</div>
	<div id="layers" style="position:absolute;z-index:106;visibility:hidden;width:275px;height:200px;">
		<table width="275px" border="0" cellspacing="0" cellpadding="0" class="map_style">
			<tr valign="center">
				<td bgcolor="#b5b4b3" align="center" width="12%">
					<input type="image" src="<?=NIVEAU_0?>/gis/gis_icons/icone_gis_window.gif">
				</td>
				<td style="font-family: Arial, Helvetica, sans-serif;font-size: 8pt;color: #FFFFFF" bgcolor="#b5b4b3" width="78%">Layers</td>
				<td bgcolor="#b5b4b3" align="center" width="10%">
					<img src="<?=NIVEAU_0?>/gis/gis_icons/close.gif" onclick="showLayers()" style="cursor:pointer"/>
				</td>
			</tr>
			<tr>
				<td colspan="3">
					<iframe id="map_layers" name="map_layers" src="<?=NIVEAU_0?>/gis/gis_scripts/gis_layers.php" width="275" height="200" align="center" frameborder="0" scrolling="no" style="border:0">
					</iframe>
				</td>
			</tr>
		</table>
	</div>

	<!-- DIV de la fenetre d'informations de la na survolee -->
	<!-- 18/08/2010 NSE DE Firefox bz 17384 : suppression du fond de la barre de titre et fond blanc pour la fenêtre -->
	<div id="data_info_title" style="position:absolute;z-index:109;cursor:move;display:none;" onmousemove="deplacer(event)" onmouseover="deplacer(event)" onmousedown="cliquer(this.id, 'data_info', event);setZindex(this.id, 'data_info');lockGIS(true)" onmouseup="lacher();lockGIS(false)">
	</div>
	<div id="data_info" style="position:absolute;z-index:108;visibility:hidden;width:250px;height:200px;">
		<table width="200px" border="0" cellspacing="0" cellpadding="0" class="map_style">
			<tr valign="center">
				<td bgcolor="#b5b4b3" align="center" width="12%">
					<input type="image" src="<?=NIVEAU_0?>/gis/gis_icons/icone_gis_window.gif">
				</td>
				<td style="font-family: Arial, Helvetica, sans-serif;font-size: 8pt;color: #FFFFFF" bgcolor="#b5b4b3" width="78%">Data Information</td>
				<td bgcolor="#b5b4b3" align="center" width="10%">
					<img src="<?=NIVEAU_0?>/gis/gis_icons/close.gif" onclick="showDataInformation()" style="cursor:pointer"/>
				</td>
			</tr>
			<tr>
				<td colspan="3">
					<iframe id="data_info" name="data_info" src="<?=NIVEAU_0?>/gis/gis_scripts/gis_data_info.php" align="center" frameborder="0" scrolling="auto" style="border:0" width="250">
					</iframe>
				</td>
			</tr>
		</table>
	</div>
</div>
</BODY>
</HTML>
