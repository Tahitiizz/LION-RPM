<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
*	- 17/06/2009 BBX : 
		=> modification des requêtes avec id_user, ajout de quotes (champ text désormais)
		=> constantes CB 5.0
		=> Header CB 5.0
		=> Nouvelles fonctions CB 5.0 
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb21000_gsm20010@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	Parser version gsm_20010
*
*	maj 29/12/2006 maxime: on limite la sélection des na aux na actifs ( on_off=1 )
*/
?>
<?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
/**
 * Affiche d'un côté les cellules
 * et de l'autre la liste des cellules  sélectionnées par l'utilisateur
 * qui vont être agrégées
 */

/*
  - maj 05/03/2007 Gwénaël : Possibilité d'agrandir la fenêtre
  - maj 23/02/2007 Gwénaël : récupération des id des na_min pour mettre à la place du label dans l'attribut value.
                                                    JS : sélection du bouton modify avant de le rendre inactif
  - maj 27/10/2006 xavier : affichage du label des network agregation
*/

session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");
$lien_css = $path_skin . "easyopt.css";

// gestion multi-produit - 21/11/2008 - SLC
$family = $_GET['family'];
$product = $_GET['product'];
include_once('connect_to_product_database.php');

$reference_table = get_object_ref_from_family($family,$product);
$network_aggregation_min = get_network_aggregation_min_from_family($family,$product);

if ($id_network_agregation) {
	$query = "SELECT  cell_liste,agregation_name FROM my_network_agregation where on_off = 1 and id_network_agregation=" . $id_network_agregation;
	$row = $db_prod->getrow($query);
	$cell_list = $row["cell_liste"];
	$agregation_name = $row["agregation_name"];
}

// PAGE
$arborescence = 'Query Builder';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<script src="<?=NIVEAU_0?>js/my_network_agregation.js"></script>
<div id="container" style="width:100%;text-align:center">
<form  name="formulaire" id="formulaire" method="post" action="#">
	<input type="hidden" name="product" value="<?=$product?>"/>

		<table cellpadding="3" cellspacing="2" class="tabPrincipal">
			<tr>
				<td>
					<fieldset>
						<legend class="texteGrisBold">
							&nbsp;<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">
							&nbsp;Network aggregation creation&nbsp;
						</legend>
						<table width="100%" height="100%" align="center" border="0" cellspacing="0" cellpadding="1">
							<tr>
								<td>

									<table width="100%" height="100%" border="0" align="center" cellspacing="2" cellpadding="3">
										<tr align="center" height=5%>
											<td width=33%>
												<font class="texteGris">List of Elements</font>
											</td>
											<td nowrap width="33%"></td>
											<td  width="33%"><font class="texteGris">List of Selected Elements</font></td>
										</tr>
										<tr height=70%>
											<td align="center" >
												<select  name="liste_data" size="28" class="zoneTexteBlanche">
													<?php
													// 27/10/2006 xavier
													// $type est passé par l'url d'appel
													// 23/02/2007 Gwénaël
													//Récupère l'identifiant du na pour l'associé au label dans l'attribut value
													if ($id_network_agregation) {
														$query = "
															SELECT distinct eor_id AS id,
																CASE WHEN eor_label IS NOT NULL THEN
																	eor_label
																ELSE
																	'(' || eor_id || ')'
																END as label
															
															FROM $reference_table
															WHERE eor_on_off = 1
																AND eor_obj_type='$network_aggregation_min'
																AND eor_id NOT IN $cell_list
															ORDER BY label ASC";
														//    $query = "SELECT distinct $network_aggregation_min FROM $reference_table where $network_aggregation_min not in " . $cell_list . " order by $network_aggregation_min asc";
													} else {
														$query = "
															SELECT distinct eor_id AS id,
																CASE WHEN eor_label IS NOT NULL THEN
																	eor_label
																ELSE
																	'(' || eor_id || ')'
																END as label
															
															FROM $reference_table
															WHERE eor_on_off = 1
																AND eor_obj_type='$network_aggregation_min'
															ORDER BY label ASC";
														//    $query = "SELECT distinct $network_aggregation_min FROM $reference_table order by $network_aggregation_min asc";
													}
													$result = $db_prod->getall($query);
													foreach ($result as $row) {
														$na_label_in_select = $row["label"];
														if (strlen($na_label_in_select) > 20)
															$na_label_in_select = substr($na_label_in_select, 0, 20) . "...";

														echo "<option  value='{$row['id']}'>$na_label_in_select</option>\n";
													}
													?>
												</select>
											</td>
											<td align="center" width="10%">
												<input type="button" onclick="add_list_table();" name="Submit" value="&gt;&gt;" class="boutonPlat"/>
												<br>
												<br>
												<br>
												<input type="button" onclick="remove_list_choice();" name="Submit2" value="&lt;&lt;" class="boutonPlat"/>
											</td>
											<td align="center" >
												<select name="choix_data" size="28" class="zoneTexteBlanche">
													<?php
													// 27/10/2006 xavier
													// Affiche les Cell
													if ($id_network_agregation) {
														// 23/02/2007 Gwénaël
														//Récupère l'identifiant du na pour l'associé au label dans l'attribut value
														$query = "
															SELECT distinct eor_id AS id,
																CASE WHEN eor_label IS NOT NULL THEN
																	eor_label
																ELSE
																	'(' || eor_id || ')'
																END as label
															FROM $reference_table
															WHERE eor_id IN $cell_list
																AND eor_obj_type='$network_aggregation_min'
															ORDER BY label ASC ";
														// $query = "SELECT distinct $network_aggregation_min FROM $reference_table where $network_aggregation_min  in " . $cell_list . " order by $network_aggregation_min asc";
														$result = $db_prod->getall($query);
														foreach ($result as $row) {
															$na_label_in_select = $row["label"];
															if (strlen($na_label_in_select) > 20)
																$na_label_in_select = substr($na_label_in_select, 0, 20) . "...";

															echo "<option  value='{$row['id']}'>$na_label_in_select</option>";
														}
													}
													?>
												</select>
											</td>
										</tr>
										<tr height="10%">
											<td align="center" colspan="4">
												<font class="texteGrisBold">Aggregation name : </font>
												<input type="text" name="agregation_name" class="zoneTexteBlanche" size="30" id="agregation_name" value="<?=trim($agregation_name)?>">
												<input type="hidden" name="transfert_liste_choix"  id="transfert_liste_choix"  value="">
												<input type="hidden" name="id_network_agregation"  id="id_network_agregation"  value="<?=$id_network_agregation?>">
											</td>
										</tr>
										<tr height="10%">
											<td align="center" colspan="4">
												<input type="submit" onclick="return validation('drop','<?=$family?>','<?=$product?>');" class="bouton" name="save" value="Drop Aggregation" style="width:160px;">&nbsp;&nbsp;&nbsp;
												<input type="submit" onclick="return collect_table_list('save','<?=$family?>','<?=$product?>');" class="bouton" name="save" value="Save Aggregation" style="width:160px;">&nbsp;&nbsp;&nbsp;
												<input type="submit" onclick="return collect_table_list('modify','<?=$family?>','<?=$product?>');" class="bouton" name="modify" id="modify" value="Modify Aggregation" style="width:160px;">
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</fieldset>
				</td>
			</tr>
		</table>
	</form>
</div>
</body>
</html>
<script>
<?php
	if (!$id_network_agregation) {
		// 23/02/2007 Gwénaël
		//Sélection du bouton modify avec de le rendre inactif (sinon erreur JS : comme le bouton est inactif impossible de lui donner le focus)
		?>
	
		document.getElementById("modify").focus();
		document.getElementById("modify").disabled=true;
		<?php
		}
	
		//modif 05/03/2007 Gwénaël
		//Posibilité d'agrandir la fenetre dans le cas où la liste dépasse de la fenêtre
	?>
	document.resizable = true;
</script>

