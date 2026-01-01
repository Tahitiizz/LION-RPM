<?
/**
 *	@cb50414@
 *
 *	05/01/2011 - Copyright Astellia
 *
 *	Composant de base version cb_5.0.4.12
 *
 *	05/01/2011 12:13 SCT : BZ 19673 => Access to login page and admin page is very slow when in multiproduct with 5 products
 *      + mise à jour des gestions de connexion à la bdd : "new Databaseconnection()" par "Database::getConnection()"
 *      + déplacement de l'affichage du contenu des process de chaque famille dans le script ajax : intra_homepage_admin_ajax.inc.php
 *
 */
?>
<?
/*
*	@cb41000@
*
*	14/03/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	maj 05/11/2008 - maxime : Modification de la construction du graphe
*					     Suppression des balises <table>
*	maj 28/01/2009 - slc - affichage des partitions de tous les produits
*	
*	08/07/2009 SPS :
*		- changement de la feuille de style quand il reste moins de 10% d'espace disque dispo (correction bug 9778)
*	maj 02/12/2009 MPR : Correction du bug 10936 - On ignore la première ligne
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
<!-- 14/09/2010 OJT : Correction bz 16764 pour DE Firefox, ajout de la class 'box' au fieldset -->
<fieldset class="box">
	<legend class="texteGrisBold" style='font-size:10px;'>&nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/><?=__T('A_HOMEPAGE_ADMIN_DISK_SPACE')?></legend>
	<table class="texteGris" style="font-size:10px;">

		<?php

			foreach ( $products as $product ) 
			{
				// 06/07/2009 BBX : calcul de la coupe du label. BZ 9781
				$productLabel = homepageAdminCorrectProductLabel($product['sdp_label']);
                $imageAction  = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_plus.gif" id="system_action_'.$product['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
                if( count( $products ) == 1)
                {
                    $imageAction = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_moins.gif" id="system_action_'.$product['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
					}
                echo '<tr class="js_product_handle" name="system_'.$product['sdp_id'].'" id="system_'.$product['sdp_id'].'"><td colspan="2" class="texteGrisBoldU" style="padding-top:10px;font-size:10px;">'.$imageAction.$productLabel.'</td></tr>';
				
                // dans le cas de la famille principale, on affiche le contenu
                if( count( $products ) == 1)
                {
                    echo IntraHomepageAdmin::homepageDynamicSystemInfo($product);
						}
					}
		
		?>
		
	</table>
</fieldset>

<style type="text/css">
<?php 
/*
*	08/07/2009 SPS :
*		- changement de la feuille de style quand il reste moins de 10% d'espace disque dispo (correction bug 9778)
*/
/*tr.lessThan10 td {color:#900;}*/ 
?>
tr.lessThan10 td {background:#F33;color:#FFF;font-weight:bold;}
tr.equal0 td {background:#F33;color:#FFF;font-weight:bold;}
</style>
