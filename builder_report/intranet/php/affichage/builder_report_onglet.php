<?php
/**
 *   @version cb_51001
 *
 *  23/07/2010 BBX
 *       - Adaptation du script au CB 5.1. BZ 16628
 *       - Lors du merge 5.0.X ver 5.1.X, merci de conserver cette version
 * 
 *  28/07/2010 OJT : Correction bz17078
 *  03/08/2010 OJT : Correction bz17078 (reopen)
 */


session_start();

/*	Page gérant la partie centrale du report builder

	cette page est composée de trois onglet
		0 - création de requete : report_equation_define.php
		1 - visualisation sous forme de tableau : report_table_result.php
		2 -  visualisation sous forme de graphe : report_graph_result.php
*/

include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/php2js.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/table_generation.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/array_unique2.php");
include_once("report_builder_determiner_requete.php");
// include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

if ($onglet == "") $onglet = 0;
if ($show_onglet == "") $show_onglet = "0";
if (!isset($family)) {
    $family = $_POST['family'];
    if ($family == "") {
        $family = $_GET['family'];
    }
}

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$db = Database::getConnection();

// gestion multi-produit - 20/11/2008 - SLC
include_once('connect_to_product_database.php');
?>

<?php
$onload="";
include_once REP_PHYSIQUE_NIVEAU_0.'php/header.php';
include_once REP_PHYSIQUE_NIVEAU_0.'php/loading_page.php';
?>

<!-- Gestion du style pour l'affichage des onglets-->
<style type="text/css">
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
        #interface1 { z-index:1; }
</style>

<script type="text/javascript" src="<?=NIVEAU_0?>js/builder_reportv3.js" ></script>
<script type="text/javascript">
    /**
     * Fonction qui gére le changement d'onglet
     * 28/07/2010 OJT : Correction bz17078 + optimisation du code
     * 03/08/2010 OJT : Gestion du rechargement (pb iframe)
     *
     * @param onglet Numero de l'onglet à charger
     * @param show_onglet Flag indiquant si l'onglet doit etre affiché
     */
    function TabClick( onglet, show_onglet )
    {
        var content = $$( 'div.tabContent' );
        var tabs = $$( 'td.TabCommon' );

        if( ( content.length == 3 ) && ( tabs.length == 3 ) )
        {
            for( i = 0 ; i < content.length ; i++ )
            {
                tabs[i].className = "TabBorderBottom TabCommon TabOff";
                content[i].style.display = "none";
            }
            if ( show_onglet != 1 ){
                onglet = 0;
            }
            content[onglet].style.display = "block";
            tabs[onglet].className = "TabCommon TabOn TabActiveBackground TabActiveBorderLeftRight";
        }
        else
        {
            // La page n'est peut etre pas encore bien chargé (problème iframe)
            setTimeout( 'TabClick( ' + onglet + ', ' + show_onglet + ' )', 250 );
        }
    }
    document.observe("dom:loaded", function(){TabClick(<?=$onglet?>,<?=$show_onglet?>);});

</script>

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
		 <div id=Content class="tabContent" style="display:none;">
		  <table width="98%" cellpadding="0" cellspacing="0" border="0" align="center">
		   <tr>
		    <td>
				<?
				/*
				maj 15/07/2009 - MPR : Correction du bug 10552
				
				<div class="texteGris">
					
					<a href="builder_report_index.php" target="_top"><img src="<?=//NIVEAU_0?>images/icones/change.gif" width="20" height="20" alt="change product" border="0" align="absmiddle"/></a> -->
					&nbsp;&nbsp;
					<!--<a href="builder_report_index.php" target="_top">-->
					
					<?php echo $db->getone("select sdp_label from sys_definition_product where sdp_id=".intval($product)); ?>
			
					- 
					<a href="builder_report_index.php?product=<?=$product?>" target="_top"><?php echo $db->getone("select family_label from sys_definition_categorie where family='$family'"); ?></a>
				</div>
				*/
				?>
				<?php include("builder_report_equation_define.php"); ?></td>
		   </tr>
		  </table>
		 </div>
		 <?php
// ----------------------------------------------------------
// Affichage du contenu de l'onglet : "Table Result"
// ----------------------------------------------------------
?>

	<div id=Content class="tabContent" style="display:none; padding: 5px;">
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
	<div id=Content class="tabContent" style="display:none;">
		<table width="98%" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td><?php include("builder_report_graph_result.php");?></td>
			</tr>
		</table>
	</div>

	</td>
	</tr>
</table>

</div>
</body>
</html>
