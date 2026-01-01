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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?php
/**
 * Gère les données de paramétrage qui servent à connecter l'application
 * aux bases de données tiers et répertoire racine qui contient des flat file
 *
 * - maj 22 11 2006 christophe : on affiche les NA dans l'ordre correspondant à la requête se trouvant dans sys_selecteur_properties.
 */
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
include_once($repertoire_physique_niveau0 . "php/menu_contextuel.php");

$comebackafterdelete = $PHP_SELF;
session_register("comebackafterdelete");

$tana_type = $_GET["tana_type"];
$family = $_GET["family"];

if ($tana_type == 'na') {
    $table = "sys_definition_network_agregation";
    $tanatitre = "Network Aggregation";
    $clause_where = "where family='$family'";
}

if ($tana_type == 'ta') {
    $table = "sys_definition_time_agregation";
    $tanatitre = "Time Aggregation";
    $clause_where ="";
}

//echo "tana_type $tana_type";

set_time_limit(15);

?>
<html>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css"/>
<script src="<?=$niveau0?>js/myadmin_omc.js"></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js"></script>
</head>
<body leftmargin="0" topmargin="0">
<form name="formulaire" method="post" action="">
<table width="550px" align="center" valign="middle" cellpadding="0" cellspacing="0">
	<tr>
		<td align="center">
			<img src="<?=$niveau0?>images/titres/setup_na_interface.gif"/>
		</td>
	</tr>
	<tr>
		<td>
			<table width="100%" border="0" align="center" cellspacing="3" cellpadding="3" class="tabPrincipal">
				<tr>
					<td align="center">
						<input onclick="ouvrir_fenetre('setup_tana_new.php?action=1&tana_type=<?=$tana_type?>&family=<?=$family?>','New_na','yes','yes',550,350)"
										type="button" class="bouton" name="parameter" value="New">
					</td>
				</tr>
				<tr>
					<td align="center" class="texteGris" valign="middle">
						<fieldset>
							<div class="texteGris" style="text-align:center;">
								<?
								// Recuperation du label du produit
								$productInformation = getProductInformations($product);
								$productLabel = $productInformation[$product]['sdp_label'];
								echo $productLabel."&nbsp;:&nbsp;";

								// Recuperation du label de la famille
								$family_information = get_family_information_from_family($family, $product);
								echo (ucfirst($family_information['family_label']));

								// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
								if (get_number_of_family() > 1){ ?>
									<a href="setup_tana_index.php?tana_type=na&no_loading=yes" target="_top">
										<img src="<?=$niveau0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0" style="vertical-align:middle;"/>
									</a>
								<? 	} //fin condition sur les familles ?>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr>
					<td>
						<fieldset>
							<legend class="texteGrisBold">
											&nbsp;
											<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;
											Network aggregation list
											&nbsp;
							</legend>
							<table align="center" cellpadding="3" cellspacing="1" id="setup_connection">
							<?
							/*$query = "
							SELECT *
							FROM sys_definition_network_agregation t0
							WHERE family='$family'
							AND agregation IS NOT NULL
							AND agregation<>''
							AND on_off=1

							ORDER BY agregation_rank desc
							";//*/
							$query = " SELECT * FROM $table $clause_where  order by agregation asc ";
																	$resultat = pg_query($database_connection, $query);
																	$nombre_connection = pg_num_rows($resultat);

							$q = $p = 0;

							// maj 14:28 21/02/2008 - maxime : migration php5 les deux paramètres d'un array_merge doivent être des tableaux
							$na_list3 = array();
							for ($k = 0;$k < $nombre_connection;$k++){
								$result_array = pg_fetch_array($resultat, $k);
								if($result_array["mandatory"] == 1){
									$na_list[$p]['agregation_label'] = 	$result_array["agregation_label"];
									$na_list[$p]['agregation'] = 		$result_array["agregation"];
									$na_list[$p]['mandatory'] = 		$result_array["mandatory"];
									$na_list[$p]['source_default'] = 	$result_array["source_default"];
									$p++;
								} else {
									// On stocke dans un autre tableau les nouvelles NA non déployées.
									$na_list3[$q]['agregation_label'] = $result_array["agregation_label"];
									$na_list3[$q]['agregation'] = 		$result_array["agregation"];
									$na_list3[$q]['mandatory'] = 		$result_array["mandatory"];
									$na_list3[$q]['source_default'] = 	$result_array["source_default"];
									$q++;
								}
							}
							/*
							echo '<pre>';
							print_r($na_list3);
							echo '</pre>';
							//*/

							// On compare par rapport à la requête de sys_selecteur_properties.
							$q = "
							SELECT ssp.selection_sql as query
							FROM sys_selecteur_properties ssp, sys_object_selecteur sos
							WHERE ssp.id_selecteur = sos.object_id
							AND sos.family = '$family'
							AND ssp.properties = 'network_agregation'
							AND ssp.selection_sql <> ''
							";
							$result = pg_query($database_connection, $q);
							$nombre_resultat = pg_num_rows($result);
							if ($nombre_resultat > 0){
								$row = pg_fetch_array($result, 0);
								$query = $row['query'];
								$query = str_replace('$this->family', '$family', $query);
								eval( "\$query = \"$query\";" );
								$resultat = pg_query($database_connection, $query);
								$nb_resultat = pg_num_rows($resultat);
								if($nb_resultat > 0){
									$na_list2 = Array();
									$p = 0;
									for ($i = 0;$i < $nb_resultat;$i++){
										$row = pg_fetch_array($resultat, $i);
										foreach($na_list as $value)
										{
											if($value['agregation'] == $row['agregation'])
											{
												$na_list2[$p]['agregation_label'] = $value["agregation_label"];
												$na_list2[$p]['agregation'] = 		$value["agregation"];
												$na_list2[$p]['mandatory'] = 		$value["mandatory"];
												$na_list2[$p]['source_default'] = 	$value["source_default"];
												$p++;
											}
										}
									}
									$na_list =   array_merge($na_list2,$na_list3) ;
								}
							}
							//*/
							?>
							<input type="hidden" name="row_table" value="<?=$nombre_connection-1?>">
							<? if ($nombre_connection > 0) { ?>
							<tr>
								<th class="texteGrisBold">Name</th>
								<th class="texteGrisBold">Label</th>
								<th class="texteGrisBold">Aggregation Source</th>
							</tr>
							<? } else {
							?>
							<tr>
								<td class="texteGrisBold">No network aggregation defined for this family.</td>
							</tr>
							<?  }
								foreach($na_list as $row){
									//$row = pg_fetch_array($resultat, $i);
									$mandatory = $row["mandatory"];
									$disabled = ($mandatory == 1) ? " disabled=\"disabled\" " : " ";
							?>
							<tr>
								<td><input class="zoneTexte" name="tana_name<?=$i?>" type="text" size="20"  value="<?=$row["agregation"]?>" <?=$disabled?>></td>
								<td><input class="zoneTexte" size="20" name="tana_label" type="text"  value="<?=$row["agregation_label"]?>" <?=$disabled?>></td>
								<td align="center"><input class="zoneTexte" name="login<?=$i?>" type="text" size="10" value="<?=$row["source_default"]?>" <?=$disabled?>></td>
								<? if($mandatory != 1){ ?>
								<td align="center"><img src="<?=$niveau0?>images/icones/drop.gif" border="0" <?=$disabled?> onClick="javascript:setup_tana_delete('<?=$row["agregation"]?>','<?=$table?>','<?=$family?>')"></td>
								<? } ?>
							</tr>
							<?  }  ?>
							</table>
						</fieldset>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</body>
</html>
