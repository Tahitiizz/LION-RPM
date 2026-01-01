<?
/*
*	@cb50400@
*
*	Composant de base version cb_5.0.4.00
*
*	16/08/2010 NSE : DE Firefox bz 17080 : formulaire Querie Builder / New Formula HS
*
*/
?><?php
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
*	17/06/2009 BBX :
*	=> Constantes CB 5.0
*	=> Header CB 5.0
* 
*/
?><?php
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01

	- maj 24/04/2007, benoit : mise en commentaire du test sur le 3eme axe
	- maj 14/05/2007 Gwnéaël : ajout d'un appel de fonction de la page formula_list_esayoptima.php qui permet d'initialiser ce formulaire une fois cette page chargée.

*/
?><?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
* 22-02-2006 : modification d'appel de la sauvegarde de la formule pour ne plus avoir de conflit avec l'interface des KPIs
*/
?><?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");

// gestion multi-produit - 21/11/2008 - SLC
$family=$_GET['family'];
$product=$_GET['product'];
include_once('connect_to_product_database.php');

// 24/04/2007 - Modif. benoit : mise en commentaire du test sur le 3eme axe
$axe3_info = GetAxe3Information($family,$product);
$id_group_table = $axe3_info["id_group_table"][0];
$group_table_name = GetGTInfoFromFamily($family,$product);
// 16/08/2010 NSE DE Firefox bz 17080 : on renomme la variable
$edw_group_table = $group_table_name['edw_group_table'];

// DEBUT PAGE
$arborescence = 'Formula Builder';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<script src="<?=NIVEAU_0?>js/generic_counters.js"></script>
<script src="<?=NIVEAU_0?>js/verification_syntaxe_kpi.js"></script>
<div id="container" style="width:100%;text-align:center">
<?php
$lien_css = $path_skin . "easyopt.css";
$libelle_favoris="Counter Builder";
$lien_favoris=getenv("SCRIPT_NAME"); //récupère le nom de la page
// 28/03/2011 NSE merge 5.0.5.03 sur 5.1.1.03 : ajout du center/middle
?>
<div align="center" valign="middle">
    <!-- 16/08/2010 NSE DE Firefox bz 17080 : ajout de id -->
	<form id="formulaire" name="formulaire" method="post" action="formula_check.php">
		<input type="hidden" name="product" value="<?=$product?>"/>
		<table width="*" height="100%" border="0" cellspacing="2" cellpadding="2">
			<tr valign="middle" align="center">
				<td colspan="3" align="center" valign="middle">
					<fieldset>
					<legend class="texteGris">
						&nbsp;
							<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif">
						&nbsp;
							Options
						&nbsp;
					</legend>
					<table width="*" border="0">
						<tr><?php /* 28/03/2011 NSE merge 5.0.5.03 sur 5.1.1.03 : boutons décommentés */?>
							<td>
								<input type="button" class="boutonPlat" name="n_avg" value="AVG" onclick="gestion_formule('AVG(','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_max" value="MAX" onclick="gestion_formule('MAX(','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_min" value="MIN" onclick="gestion_formule('MIN(','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_count" value="COUNT" onclick="gestion_formule('COUNT(','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_abs" value="ABS" onclick="gestion_formule('ABS(','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="text" class="zoneTexteBlanche" name="numerique_numerateur" size="10" maxlength="10">
								<input type="button" class="boutonPlat" name="n_add" value="Add" onclick="gestion_formule('add','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
						</tr>
					</table>
					<table width="*" border="0">
						<tr>
							<td>
								<input type="button" class="boutonPlat" name="n_parenthese_o" value="(" onclick="gestion_formule('(','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_plus" value="+" onclick="gestion_formule('+','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_moins" value="-" onclick="gestion_formule('-','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_multiplier" value="x" onclick="gestion_formule('*','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_diviser" value="/" onclick="gestion_formule('/','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_parenthese_f" value=")" onclick="gestion_formule(')','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_back" value="back" onclick="gestion_formule('back','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
							<td>
								<input type="button" class="boutonPlat" name="n_delete" value="delete" onclick="gestion_formule('delete','zone_formule_numerateur')">
							</td>
							<td>
								&nbsp;
							</td>
						</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td align="center" colspan="3">
					<table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabPrincipal">
						<tr>
							<td>
								<table width="100%" border="0" border="0" cellpadding="0" cellspacing="0">
									<tr>
										<td align="left" nowrap>
											<font class="texteGrisBold">Formula :</font><br>
										</td>
										<td width="100%">
											&nbsp;
										</td>
										<td align="right">
											<!--reserved for adding features above the equation field on the right-->
										</td>
										<td align="right">
											<!--reserved for adding features above the equation field on the right-->
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td>
                                <!-- 16/08/2010 NSE DE Firefox bz 17080 : ajout de id -->
								<textarea class="zoneTexteBlanche" rows="15" cols="80" id="zone_formule_numerateur" name="zone_formule_numerateur" onfocus="formulaire.save.focus();"><?=$generic_counter_numerateur?></textarea>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="center" colspan="3" nowrap>
                    <!-- 16/08/2010 NSE DE Firefox bz 17080 : ajout de id -->
					<input type="hidden" id="zone_id_generic_counter" name="zone_id_generic_counter" value="<?=$id_generic_counter?>">
					<input type="hidden" value="<?=$edw_group_table?>" id="group_table_name" name="group_table_name">
					<input type="text" id="generic_counter" name="generic_counter" class="zoneTexteBlanche" size="50" value="<?=$generic_counter_name?>">
				</td>
			<tr>
			</tr>
				<td align="center" colspan="3" nowrap>
					<input type="button" name="drop_kpi" class="bouton" value=" Drop formula" onclick="javascript:delete_formula()">
					&nbsp;&nbsp;
					<input type="button" name="reset_kpi_button" class="bouton" value=" Reset " onclick="javascript:reset_kpi()">
					&nbsp;&nbsp;
					<input type="button" name="save" class="bouton" value=" Save " onclick="javascript:save_kpi_formula()">
				</td>
			</tr>
		</table>
	</form>
	<script>parent.row_data.init_formula_kpi_builder();</script>
</div>
</body>
</html>
