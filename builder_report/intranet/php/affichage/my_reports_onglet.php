<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
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
	Page gérant la partie centrale du my reports

	cette page est composée de trois onglet

		Onglet 0 - overview : my_reports_over_view.php

		Onglet 1 - table resut : my_reports_table_result.php

		Onglet 2 -  graphe result : report_graph_result.php
*/
session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/php2js.php");
include_once($repertoire_physique_niveau0."php/environnement_donnees.php");
include_once($repertoire_physique_niveau0."php/my_reports_fonction.php");
include_once($repertoire_physique_niveau0."php/edw_function.php");
include_once($repertoire_physique_niveau0."php/edw_function_family.php");
include_once($repertoire_physique_niveau0."php/deploy_and_compute_functions.php");
include_once($repertoire_physique_niveau0."php/environnement_nom_tables.php");
include_once($repertoire_physique_niveau0."php/environnement_graphe.php");
include_once($repertoire_physique_niveau0."php/table_generation.php");
include_once($repertoire_physique_niveau0."php/array_unique2.php");

// gestion multi-produit - 21/11/2008 - SLC
include_once('connect_to_product_database.php');

?>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
<script src="<?=$niveau0?>js/fenetres_volantes.js" ></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js" ></script>
<script src="<?=$niveau0?>js/builder_reportv3.js" ></script>
<script src="<?=$niveau0?>js/sort_table.js"></script>
<script language="Javascript">
 //Fonction qui gére le changement d'onglet
 function TabClick(onglet,show_onglet)
{

for (i = 0; i < Content.length; i++)	// on affiche tous les onglets désactivés
	{
	tabs[i].className = "TabBorderBottom TabCommon TabOff";
	Content[i].style.display = "none";
	}
if(show_onglet==1)				// si on a le doit on affiche l'onglet sur lequel on a cliqué : nTab
	{
	Content[onglet].style.display = "block";
	tabs[onglet].className = "TabCommon TabOn TabActiveBackground TabActiveBorderLeftRight";
	}
else
	{							// si les onglets sont bloqués on affiche toujours le premier
	Content[0].style.display = "block";
	tabs[0].className = "TabCommon TabOn TabActiveBackground TabActiveBorderLeftRight";
	}
}
</script>

<?
//  on affiche par defaut le premier onglet
if($onglet=="") $onglet=0;
// on "verouille" les onglets par defaut
if($show_onglet=="") $show_onglet="0";
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
.TabActiveBorderLeftRight{BORDER-RIGHT: 2px outset #D1D1D1; BORDER-LEFT: 2px outset #D1D1D1;}
.TabActiveBackground {background-image : url('../../../../images/fonds/fond_selecteur.gif');}
.br_caption {background-color : #DDDDDD; color : #111111; font-family : arial; font-size : 8pt; border-width : 1px; border-color : #AAAAAA; border-style : dotted; }
.hexfield { border-color:#000000;font-family: arial;border-style:groove;border-top-width: 1;border-left-width: 1;border-right-width: 1;border-bottom-width: 1;font-size:4pt}
</style>
</head>
<body onload="TabClick(<?=$onglet?>,<?=$show_onglet?>);">
  <table cellpadding="0" cellspacing="0" border="0" align="center" width="720">
      <tr height=10>
	     <script>
	     </script>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(0,<?=$show_onglet;?>);" align="center"><nobr>OVERVIEW</nobr></td>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(1,<?=$show_onglet;?>);" align="center"><nobr>TABLE RESULT</nobr></td>
	<td width="130" class="TabBorderBottom TabCommon TabOff" id="tabs" onClick="TabClick(2,<?=$show_onglet;?>);" align="center"><nobr>GRAPH RESULT</nobr></td>

        <td width="330" class="TabBorderBottom">&nbsp; </td>
      </tr>
      <tr>
          <td colspan="4" class="TabContent TabActiveBackground TabActiveBorderLeftRight">

      </td>
      </tr>
      <tr>
          <td colspan="4" class="TabContent TabActiveBackground TabActiveBorderLeftRight TabContentBottom">
		 <?
		  //----------------------------------------------------------
		  // Affichage du contenu de l'onglet : "Equation Define"
		  //----------------------------------------------------------
		   ?>
		 <div id=Content style="display:none;">
		  <table width="98%" cellpadding="0" cellspacing="0" border="0" height=#>
		   <tr height=100%>
		    <td> <? include("my_reports_over_view.php"); ?>
			 </td>
		   </tr>
		  </table>
		 </div>
		 <?
		  //----------------------------------------------------------
		  // Affichage du contenu de l'onglet : "Table Result"
		  //----------------------------------------------------------
		?>
		 <div id=Content style="display:none;">
		 <table width="98%" cellpadding="0" cellspacing="0" border="0">
		 <tr height=100% valign='top'>
		 <td> <? include("my_reports_table_result.php"); ?> </td>
		 </tr>
		 </table>
		</div>
		  <?
		  //----------------------------------------------------------
		  // Affichage du contenu de l'onglet : "Graph Result"
		  //----------------------------------------------------------
		?>
		 <div id=Content style="display:none;" >
		 <table width="98%" cellpadding="0" cellspacing="0" border="0" >
		 <tr valign='top'>
		 <td><? include("builder_report_graph_result.php"); ?> </td>
		 </tr>
		 </table>
		</div>
       </td>
      </tr>
  </table>
</body>
</html>
