<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
*
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
*
*	- maj 14/05/2007Gwénaël : l'initialisation du formulaire en JS est regroupé dans une fonction, appelé par la page formuale_builder.php une fois qu'elle est chargée
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

	- maj 24/04/2007, benoit : mise en commentaire du test sur le 3eme axe

*/
?>
<?php
/**
 * Affiche par type de connection les compteurs Easyoptima sélectionnés

 - maj 31/05/2006 sls : ajout de la contrainte visible=1
 - 22/08/2006 : affichage des labels

 */
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");

$lien_css = $path_skin . "easyopt.css";

// gestion multi-produit - 21/11/2008 - SLC
$family = $_GET['family'];
$product = $_GET['product'];
include_once('connect_to_product_database.php');

// 24/04/2007 - Modif. benoit : mise en commentaire du test sur le 3eme axe
$axe3_info = GetAxe3Information($family,$product);
$id_group_table = $axe3_info["id_group_table"][0];
$group_table_name = GetGTInfoFromFamily($family,$product);
$group_table_name = $group_table_name['edw_group_table'];

// DEBUT PAGE
$arborescence = 'Formula Builder';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<div id="container" style="width:100%;text-align:center">
<div id="tbl-container" class="tabPrincipal">
	<table cellpadding="4" cellspacing="1">
	<tr>
	<td>
	<fieldset>
	<legend class="texteGrisBold">
		&nbsp;
			<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">
		&nbsp;
			Counters List
		&nbsp;
	</legend>
	<table width="100%" border="0" cellpadding="0" cellspacing="1">
		<tr>
			<td>
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<?php
				if ($group_table_name != '') {
					if ($group_table_name != "mixed") {
						// si le group table vaut mixed alors, on afiche la liste des KPI non mixtes a la place des raw counters
						$query = "
							SELECT distinct edw_field_name,edw_field_name_label
							FROM sys_field_reference
							WHERE edw_group_table='$group_table_name'
								AND visible=1
							ORDER BY edw_field_name ASC";
					} else {
						$query = "
							SELECT distinct edw_field_name,edw_field_name_label
							FROM sys_field_reference
							WHERE id_data_type=$selected_data_type
								AND visible=1
							ORDER BY edw_field_name ASC";
					}
					$result = $db_prod->getall($query);
					$compteur_field = 0;
					foreach ($result as $row) {
						$field		= array_shift($row);
						$field_label	= array_shift($row);
						if ($field_label!="") {
							$display_raw=$field_label;
						}else{
							$display_raw=$field;
						}
						?>
						<tr height="20">
							<td colspan="2">
								<a href="#<?=$field?>" name="<?=$field?>" onclick="parent.kpi_builder.add_raw_data('<?=$field?>','<?=$id_group_table?>');">
									<font class="texteGris">
										&nbsp;<?=strtoupper($display_raw)?>
									</font>
								</a>
							</td>
						</tr>
						<?php
						$compteur_field++;
					}
				?>
	<script>
	function init_formula_kpi_builder () {
		parent.kpi_builder.formulaire.group_table_name.value='<?=$group_table_name?>';
		parent.kpi_builder.formulaire.zone_formule_numerateur.value="";
		parent.kpi_builder.formulaire.generic_counter.value="";
		parent.kpi_list.location='formula_table.php?family=<?=$family?>&product=<?=$product?>';
		<?php
		if ($group_table == "mixed") {
			?>
			parent.kpi_builder.formulaire.zone_formule_denominateur.disabled=true;
			<?php
		}
		?>
	}
	</script>
	<?php
}

?>
				</table>
			</td>
		</tr>
	</table>
	</div>
</div>
</body>
</html>
