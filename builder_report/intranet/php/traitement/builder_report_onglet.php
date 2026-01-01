<?
/*
*	@cb41000@
*
*	20/11/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	- MaJ 20/11/2008 - SLC - ajout sur toutes les fonctions de $product='' et suppression de $database_connection
*
*
*/
?><?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/

session_start();

/*	Page gérant la partie centrale du report builder

	cette page est composée de trois onglet
		0 - création de requete : report_equation_define.php
		1 - visualisation sous forme de tableau : report_table_result.php
		2 -  visualisation sous forme de graphe : report_graph_result.php
*/

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/php2js.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");
include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
include_once($repertoire_physique_niveau0 . "php/environnement_graphe.php");
include_once($repertoire_physique_niveau0 . "php/table_generation.php");
include_once($repertoire_physique_niveau0 . "php/array_unique2.php");
include_once("report_builder_determiner_requete.php");
include_once($repertoire_physique_niveau0 . "php/menu_contextuel.php");

if ($onglet == "") $onglet = 0;
if ($show_onglet == "") $show_onglet = "0";
if (!isset($family)) {
    $family = $_POST['family'];
    if ($family == "") {
        $family = $_GET['family'];
    }
}

// gestion multi-produit - 20/11/2008 - SLC
include_once('connect_to_product_database.php');

?>
<html>
<head>
	<!-- Gestion du style pour l'affichage des onglets-->
	<style>
		.TabCommon {FONT: 12px verdana; COLOR: #CCC; PADDING: 5px; FONT-WEIGHT: bold; TEXT-ALIGN: center; HEIGHT: 18px; WIDTH: 140px;}
		.TabContent {PADDING: 5px; TEXT-ALIGN: center;}
		.TabContentBottom {PADDING: 5px; BORDER-BOTTOM: 2px outset #99ccff;}
		.TabOff {CURSOR: hand; BACKGROUND-COLOR: #E2E2E3; BORDER: 1px solid #787878;}
		.TabOn {CURSOR: default; BORDER-TOP: 2px outset #787878; COLOR: #000000;}
		.TabBorderBottom{BORDER-BOTTOM: 2px inset #D1D1D1;}
		.TabActiveBorderLeftRight{
			border-right: 2px outset #D1D1D1;
			border-left: 2px outset #D1D1D1;
		}
		.TabActiveBackground {background-image : url('../../../../images/fonds/fond_selecteur.gif');}
		.br_caption {background-color : #DDDDDD; color : #111111; font-family : arial; font-size : 8pt; border-width : 1px; border-color : #AAAAAA; border-style : dotted; }
		.hexfield { border-color:#000000;font-family: arial;border-style:groove;border-top-width: 1;border-left-width: 1;border-right-width: 1;border-bottom-width: 1;font-size:4pt}
	</style>
	<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
	<link rel="stylesheet" href="<?=$niveau0?>css/loader.css" type="text/css">
	<script type='text/javascript' src='<?=$niveau0?>js/loader.js'></script>
	<script src="<?=$niveau0?>js/gestion_fenetre.js" ></script>
	<script src="<?=$niveau0?>js/builder_reportv3.js" ></script>
	<script src="<?=$niveau0?>js/sort_table.js"></script>
	<script src="<?=$niveau0?>js/fenetres_volantes.js"></script>
	<script src="<?=$niveau0?>js/caddy_management.js"></script>
	<script language="Javascript">

		//Fonction qui gére le changement d'onglet
		function TabClick(onglet,show_onglet) {
			//truc=implode(tabs);
		
			if (show_onglet==1) {
				for (i = 0; i < Content.length; i++) {
					tabs[i].className = "TabBorderBottom TabCommon TabOff";
					Content[i].style.display = "none";
				}
				Content[onglet].style.display = "block";
				tabs[onglet].className = "TabCommon TabOn TabActiveBackground TabActiveBorderLeftRight";
		
			} else {
				for (i = 0; i < Content.length; i++) {
					tabs[i].className = "TabBorderBottom TabCommon TabOff";
					Content[i].style.display = "none";
				}
				Content[0].style.display = "block";
				tabs[0].className = "TabCommon TabOn TabActiveBackground TabActiveBorderLeftRight";
			}
		}


		//  Fonctions de gestion du loader.
		var t_id = setInterval(animate,20);
		var pos=0;
		var dir=2;
		var len=0;
		function animate() {
			var elem = document.getElementById('progress');
			if (elem != null) {
				if (pos==0) len += dir;
				if (len>32 || pos>79) pos += dir;
				if (pos>79) len -= dir;
				if (pos>79 && len==0) pos=0;
				elem.style.left = pos;
				elem.style.width = len;
			}
		}
	
		function remove_loading() {
			this.clearInterval(t_id);
			var targelem = document.getElementById('loader_container');
			targelem.style.display='none';
			targelem.style.visibility='hidden';
		}

	</script>
</head>
<body onload="TabClick(<?=$onglet?>,<?=$show_onglet?>); remove_loading();">
<style type="text/css">
#interface1 { z-index:1; }
#loader_container {text-align:left; position:absolute; top:25%; width:100%; left:40%}
#loader {
	font-family:Tahoma, Helvetica, sans;
	font-size:11px;
	color:#000000;
	background-color:#FFFFFF;
	padding:10px 0 16px 0;
	margin:0 auto;
	display:block;
	width:130px;
	border:1px solid #6A6A6A;
	text-align:left;
	z-index:2;
}
#progress {
	height:5px;
	font-size:1px;
	width:1px;
	position:relative;
	top:1px;
	left:0px;
	background-color:#9D9D94
}
#loader_bg {background-color:#EBEBE4;position:relative;top:8px;left:8px;height:7px;width:113px;font-size:1px}
</style>

<div id="loader_container" onclick="remove_loading();">
	<div id="loader">
		<div align="center" id="texteLoader">Calculating...</div>

		<div id="loader_bg"><div id="progress"> </div></div>
	</div>
</div>
<div id='interface1' style="display:inline; visibility:visible;">

<div style="position: absolute; top: 1px; left: 1px;   visibility: hidden;">
</div>


  <table cellpadding="0" cellspacing="0" border="0" align="left" width="720px">
      <tr>
          <?php

if ($show_onglet == 1) {

    ?>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(0,1);" align="center">EQUATION DEFINE</td>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(1,1);" align="center">TABLE RESULT</td>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(2,1);" align="center">GRAPH RESULT</td>
	<?php
} else {

    ?>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(0,0);" align="center">EQUATION DEFINE</td>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(1,0);" align="center">TABLE RESULT</td>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(2,0);" align="center">GRAPH RESULT</td>
	<?php
}


?>
	<td width="330" class="TabBorderBottom">&nbsp; </td>
</tr>
<tr>
		<td colspan="4" class="TabContent TabActiveBackground TabActiveBorderLeftRight">
</td>
</tr>
<tr>
	<td colspan="4" class="TabContent TabActiveBackground TabActiveBorderLeftRight TabContentBottom">
		 <?php
// ----------------------------------------------------------
// Affichage du contenu de l'onglet : "Equation Define"
// ----------------------------------------------------------

?>
		 <div id=Content style="display:none;">
		  <table width="98%" cellpadding="0" cellspacing="0" border="0" align="center">
		   <tr>
		    <td><?php include("builder_report_equation_define.php"); ?></td>
		   </tr>
		  </table>
		 </div>
		 <?php
// ----------------------------------------------------------
// Affichage du contenu de l'onglet : "Table Result"
// ----------------------------------------------------------
?>

	<div id=Content style="display:none; padding: 5px;">
		<table width="98%" cellpadding="0" cellspacing="0" border="0" align="center">
			<tr>
				<td><?php include("builder_report_table_result.php"); ?></td>
			</tr>
		</table>
	</div>
	<?php
// ----------------------------------------------------------
// Affichage du contenu de l'onglet : "Graph Result"
// ----------------------------------------------------------
?>
	<div id=Content style="display:none;">
		<table width="98%" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td><?php include("builder_report_graph_result.php");?></td>
			</tr>
		</table>
	</div>

	</td>
	</tr>
</table>

</body>
</html>
