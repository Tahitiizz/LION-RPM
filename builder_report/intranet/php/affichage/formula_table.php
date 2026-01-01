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

	- maj 24/04/2007, benoit : mise en commentaire du test sur le 3eme axe

*/
?>
<?/************************************************
Affiche laliste de tous les compteurs génériques batis à partir
des raw data sélectionnés dans les OMC/flat file
***************************************************/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
$lien_css = $path_skin . "easyopt.css";

// gestion multi-produit - 21/11/2008 - SLC
$family=$_GET['family'];
$product=$_GET['product'];
include_once('connect_to_product_database.php');

// 24/04/2007 - Modif. benoit : mise en commentaire du test sur le 3eme axe
$axe3_info = GetAxe3Information($family,$product);
$id_group_table = $axe3_info["id_group_table"][0];
$group_table_name = GetGTInfoFromFamily($family,$product);
$edw_group_table = $group_table_name = $group_table_name['edw_group_table'];

// PAGE
$arborescence = 'Query Builder';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<div id="container" style="width:100%;text-align:center">
   <div id="tbl-container" class="tabPrincipal">
	<table cellpadding="4" cellspacing="1" width="170px">
	<tr>
	<td>
	<fieldset>
	<legend class="texteGrisBold">
		&nbsp;
			<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">
		&nbsp;
			Formula List
		&nbsp;
	</legend>
	<table width="100%" border="0"  cellpadding="1" cellspacing="0">
		<tr>
			<td>
				<table width="100%" border="0" cellspacing="1" cellpadding="0">
					<?
					if( $edw_group_table=="mixed")
						$edw_group_table="edw_alcatel_0,edw_motorola_0";
					$query="SELECT * FROM  forum_formula WHERE on_off=1 and id_user= '".$id_user."' and formula_edw_group_by='".$edw_group_table."' order by formula_name ;";
					$result = $db_prod->getall($query);

					if (!$result) { ?>

						<tr height="20">
							<td align="center"><font class="texteGris">No Formula</font></td>
						</tr>
					<? } else {

						foreach ($result as $row) {
							$id_generic_counter		= $row["id_formula"];
							$nom_generic_counter	= $row["formula_name"];
							//$quotient=$row["quotient"];            ?????????????
							$generic_counter_numerateur = str_replace("::float4","",$row['formula_equation']);
							?>
							<tr height="20">
								<td>
									<input type="hidden" value="<?=$generic_counter_numerateur?>" name="<?=$generic_counter_numerateur?>">
									<a href="#<?=$nom_generic_counter?>" name="<?=$nom_generic_counter?>" onclick="parent.kpi_builder.affiche_equation('<?=$nom_generic_counter?>','<?=$generic_counter_numerateur?>','<?=$id_generic_counter?>','<?=$edw_group_table?>')">
										<font class="texteGris"><?=strtoupper($nom_generic_counter)?></font>
									</a>
								</td>
							</tr>
							<?
						}
					}
					?>
				</table>
			</td>
		</tr>
	</table>
	</fieldset>
	</td>
	</tr>
	</table>
	</div>
	</div>
</body>
</html>
