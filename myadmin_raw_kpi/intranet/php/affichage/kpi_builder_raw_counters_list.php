<?php
/**
 * @cb5100@
 *
 * 06/07/2010 - Copyright Astellia
 *  - 06/07/2010 OJT : Ajout du header standard et correction bz16401
 */
?>
<?
/*
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Contrôle d'accès
*	=> Utilisation de la classe de connexion àa la base de données
*	=> Gestion du produit
*
*
*	22/07/2009 GHX
*		 - Ajout de l'id produit dans l'url qui permet de sélectionner une famille (correction du BZ 10520)
*	02/11/2009 GHX
*		- Suppression du tooltip sur le bouton change family car sinon erreur JS
*	09/12/2009 GHX
*		- Correction du BZ 13225 [[REC][MIXED-KPI] : erreur javascript dans le KPi_builder
*			-> Ajout d'un htmlentities sur la balise title qui contient le commentaire d'un compteur
*	04/02/2010 NSE 
*		- bz 13799 : erreur JS si ' dans le commentaire du compteur 
*			-> ajout du addslashes sur le commentaire et d'un trim 
*	24/02/2010 NSE bz 13799 : ajout de htmlentities sur comment
*/
?>
<?
/*
*	@cb30000@
*
*	23/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.00
*
*	- maj 07/08/2007 Jérémy : 	Ajout d'une condition pour afficher l'icone de retour au choix des familles
*						Si le nombre de famille est supérieur à 1 on affiche l'icône, sinon, on la cache
*
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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
<?
/************************************************
Affiche les raw counters par group table

- maj 23/05/2006 sls : ajout de l'appel à /js/fenetres_volantes.js
- 22/08/2006 : affichage des labels
***************************************************/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
include_once(REP_PHYSIQUE_NIVEAU_0 ."php/edw_function_family.php");

// Connexion à la base produit
$database = DataBase::getConnection( $_GET['product'] );

include_once(REP_PHYSIQUE_NIVEAU_0 . '/php/header.php' );

// Recuperation du label du produit
$productInformation = getProductInformations($product);
$productLabel = $productInformation[$product]['sdp_label'];

$family = $_GET["family"];
$family_infos=get_family_information_from_family($family,$_GET['product']);
$family_label=$family_infos['family_label'];
?>
<table cellspacing="0" cellpadding="3" border="0" class="tabPrincipal">
<tr>
<td>
<fieldset>
<legend class="texteGrisBold">
	&nbsp;<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">&nbsp;Raw Data&nbsp;
</legend>
<table width="100%" border="0" cellspacing="1" cellpadding="2">
	<tr>
		<td align="left" valign="top" class="texteGrisPetit" style="text-decoration:underline;padding-top:10px;padding-bottom:10px;">
		<? 	echo $productLabel."&nbsp;:&nbsp;".$family_label;
			// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
			if (get_number_of_family() > 1){ ?>
				<a href="kpi_builder_index.php?product=<?php echo $_GET['product']; ?>" target="_top"><!--  -->
						<img src="<?=NIVEAU_0?>images/icones/change.gif" border="0"/>
				</a>
		<? 	} //fin condition sur les familles ?>
		</td>
	</tr>
	<?/*
	<tr>
			<td align="center"><a href="kpi_builder_raw_counters_list.php?family=<?=$family?>" class="texteGrisPetit" style=" text-decoration:none; ">> Reload</a></td>
	</tr>
	*/?>
	<?
	if(isset($_GET["family"])){
			$family = $_GET["family"];
                        // 14/04/2011 BBX
                        // Correction de la correction sur le new_field. les compteurs new_field = 2 ne divent pas être présentés
                        // BZ 21834
			$query="
					SELECT distinct edw_field_name, comment,edw_field_name_label FROM sys_field_reference
					WHERE id_group_table in (
																			select id_ligne from sys_definition_group_table
																			where family='$family'
																			and visible=1
																	)
					AND visible=1 and on_off=1 and new_field=0
					ORDER BY edw_field_name_label ASC
			";
			$result=$database->execute($query);
			$nombre_resultat=$database->getNumRows();
			if ($nombre_resultat == 0){
					echo "<tr><td align=\"center\">"; // Affichage du message d'erreur.
					echo "<font style=\"font : normal 9pt Verdana, Arial, sans-serif; color : #585858;s\"><b>Error : no data found. [no data for this family]</b></font>";
					echo "</td></tr>";
					exit;
			}
			$compteur_field=0;
			foreach($database->getAll($query) as $row) {
					$field=$row['edw_field_name'];
					// 04/02/2010 NSE bz 13799 : ajout du addslashes
					// 24/02/2010 NSE bz 13799 : ajout de htmlentities sur comment
					$group_table=addslashes(htmlentities($row['comment']));
					$field_label=addslashes($row['edw_field_name_label']);
					if ($field_label!="") {
					    $display_raw=$field_label;
					}else{
						$display_raw=$field;
					}
					
					// 14:22 09/12/2009 GHX 
					// Correction du BZ 13225 [REC][MIXED-KPI][TC#51656] : affichage impossible d'un dashboard
					// Ajout du htmlentities sur le contenu de la balise title
					// 04/02/2010 NSE bz 13799 : ajout du trim sur le commentaire
					?>
					<tr height="20">
							<td colspan="2">
									<a href="#<?=$field?>" name="<?=$field?>" title="<?= ($row['comment']) ? htmlentities(trim($row['comment'])):'No description data for that field.';?>" onclick="parent.kpi_builder.add_raw_data('<?=$field?>','<?=trim($group_table)?>');">
											<font class="texteGrisPetit">
													<?=strtoupper($display_raw)?>
											</font>
									</a>
							</td>
					</tr>
					<?
					$compteur_field++;
			}
		}
	?>
	</table>
	</fieldset>
	</td>
	</tr>
	</table>
</body>
</html>
