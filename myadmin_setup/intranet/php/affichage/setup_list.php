<?
/**
 * @cb50400@
 *
 * 02/09/2010 OJT : Correction bz17047 pour DE Firefox, modificaton des alt en title
 * 31/01/2011 NSE bz 20445 : tri des alarmes par ordre alphabétique
 */
/*
*	@cb41000@
*
*	- maj 14/10/2008 SLC : recentrage suite à ajout du DOCTYPE
*
*	07/07/2009 - SPS
*		- pour verifier que l'alarme est utilisee dans un rapport, on se connecte sur le maitre (correction bug 10397)
*/
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- 09:05 25/01/2008 Gwénaël : modif pour la récupération du paramètre client_type
*
	- maj 17/03/2008, benoit : ajout du champ "on_off" dans la requete de listage des alarmes top worst existantes
	- maj 17/03/2008, benoit : ajout d'un boolean indiquant si il existe des alarmes désactivées
	- maj 17/03/2008, benoit : ajout d'un style particulier pour les lignes d'alarmes désactivées
	- maj 17/03/2008, benoit : s'il existe des alarmes désactivées, on affiche un message précisant pourquoi les lignes des alarmes concernées    sont en orange
*
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
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- 10/07/2007 christophe : gestion de la suppression dans la table sys_definition_alarm_network_elements et affichage du pointeur sur les icônes.
* - maj 11/05/2005 Gwénaël : changement "3rd axe" par "3rd Axis"
*/
?>
<?
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00 modification d'un label sur le tooltip de l'icône de l'envoi des mail.
*
*	- 08 02 2007 christophe :
*
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?
	/*
		- maj 22 06 2006 christophe : si le client_type=="client" on ne peut pas supprimer l'alarme
		- maj 23 06 2006 stephane : remplacement de network --> agregation_label dans les listings
		- maj 28 06 2006 christophe : correction de la requête de la liste des NA/alarmes, il manquait une jointure sur les familles.
		- maj 03 07 2006 xavier : modification de la requête pour affichage.
		- maj 03 10 2006 xavier : alignement des boutons et remplacement du href par un onclick
		- maj 25/10/2006 xavier : suppression d'une alarme impossible si présente dans un rapport
		- maj 05/04/2007 Gwénaël : mise à jour de la requete qui récupèrer les alarmes pour prendre en compte le 3° axe
	*/
	/**
	 * Gère les données de paramétrage qui servent à connecter l'application
	 * aux bases de données tiers et répertoire racine qui contient des flat file
	 */
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
	//include_once("setup_alarm.class");

	$lien_css = $path_skin . "easyopt.css";
	//echo $lien_css;
	$comeback=$PHP_SELF;
	session_register("comeback");

	$product	= intval($_GET['product']);
	$family	= $_GET["family"];

	// on se connecte à la db
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($product);

	$flag_axe3 = GetAxe3($family, $product);

?>
<html>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
<script src="<?=$niveau0?>js/myadmin_omc.js"></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js"></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>

</head>
<body leftmargin="0" topmargin="0">
	<table width="550px" align="center" valign="middle" cellpadding="3" cellspacing="3">
		<tr>
			<td align="center">
				<img src="<?=$niveau0?>images/titres/top_worst_titre.gif">
			</td>
		</tr>
		<tr>
			<td>
				<table width="100%" border="0" align="center" cellspacing="0" cellpadding="0" class="tabPrincipal" >
					<tr>
						<td>
						<div style="padding: 2px;text-align:center">
						   <?
							$title="Set-up List Interface";

					//
					// on supprime une liste si demandé
					//
					if ($del=='del') {
						$query_delete = "
							DELETE FROM sys_internal_id WHERE internal_id IN (SELECT internal_id
								FROM sys_definition_alarm_top_worst where alarm_id='$alarm_id')
						";
						$db->execute($query_delete);
						/*
						$del1="delete from edw_alarm_detail where id_result in
							(select id_result from edw_alarm where id_alarm=$alarm_id and alarm_type='top-worst' )";
						pg_query($database_connection,$del1);
						$del2="delete from edw_alarm where id_alarm=$alarm_id and alarm_type='top-worst' ";
						pg_query($database_connection,$del2);*/
						$db->execute("DELETE FROM sys_definition_alarm_top_worst WHERE alarm_id='$alarm_id' ");

						// - 11/07/2007 christophe : gestion de la suppression dans la table sys_definition_alarm_network_elements.
						$db->execute("DELETE FROM sys_definition_alarm_network_elements	WHERE id_alarm='$alarm_id'	AND type_alarm='alarm_top_worst' ");
						$db->execute("DELETE FROM sys_definition_alarm_exclusion			WHERE id_alarm='$alarm_id'	AND type_alarm='alarm_top_worst' ");
					}

					// 17/03/2008 - Modif. benoit : ajout du champ "on_off" dans la requete de listage des alarmes top worst existantes
					//include("header_design.php");
					//ici le contenu
					//liste les alarmes existantes
					$query = "
						SELECT DISTINCT ON (sdcl.alarm_id)
							sdcl.alarm_id,
							sdcl.alarm_name,
							sdcl.on_off,

							CASE WHEN sdcl.list_sort_field_type='raw'
							THEN
								CASE WHEN (SELECT distinct edw_field_name_label FROM sys_field_reference a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdcl.list_sort_field = edw_field_name) IS NOT NULL
								THEN (SELECT distinct edw_field_name_label FROM sys_field_reference a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdcl.list_sort_field = edw_field_name)
								ELSE sdcl.list_sort_field
								END
							ELSE
								CASE WHEN (SELECT distinct kpi_label FROM sys_definition_kpi a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdcl.list_sort_field = kpi_name) IS NOT NULL
								THEN (SELECT distinct kpi_label FROM sys_definition_kpi a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdcl.list_sort_field = kpi_name)
								ELSE sdcl.list_sort_field
								END
							END as list_sort_field,

							sdcl.list_sort_asc_desc,
							sdcl.network,
							(SELECT agregation_label FROM sys_definition_time_agregation WHERE agregation = sdcl.time) as time,
							sdcl.hn_value,
							sdcl.client_type,";
							// - modif 05/04/2007 Gwénaël : modification pour récupérer le label du 3° axe
							// utilisation d'une fonction postgres split_part pour exploser le champ network (equivalant de la fonction explode en php)
						if($flag_axe3) {
							$query .= "(SELECT agregation_label FROM sys_definition_network_agregation t0 WHERE t0.agregation=split_part(sdcl.network, '_', 1) and t0.family=sdcl.family) as agregation_label, ";
							$query .= "(SELECT agregation_label FROM sys_definition_network_agregation t0 WHERE t0.agregation=split_part(sdcl.network, '_', 2) and t0.family=sdcl.family) as agregation_label_axe3 ";
						}
						else
							$query .= "(SELECT agregation_label FROM sys_definition_network_agregation t0 WHERE t0.agregation=sdcl.network and t0.family=sdcl.family) as agregation_label ";

						$query .= "FROM sys_definition_alarm_top_worst as sdcl
								WHERE sdcl.family='$family'
									AND additional_field is null";
                        // 31/01/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
                        $query = "SELECT * FROM
                                  ( $query ) query
                                  ORDER BY alarm_name";

						$alarmes = $db->getall($query);
					?>
					<table align=center cellpadding="4">
						<tr align="center">
							<td colspan="9" align="center">
								<input type="button"
									onclick="self.location.href='setup_alarm_detail.php?product=<?=$product?>&family=<?=$family?>&alarm_type=alarm_top_worst'"
										class="bouton" name="parameter" value="New Top/Worst List">
									</td>
								</tr>
								<tr>
									<td align="center">
									<fieldset>
										<table cellspacing="2" cellpadding="2" border="0">
											<tr>
												<td align="left" class="texteGris">
												<?
													// Recuperation du label du produit
													$productInformation = getProductInformations($product);
													$productLabel = $productInformation[$product]['sdp_label'];
													echo $productLabel."&nbsp;:&nbsp;";

													// Recuperation du label de la famille
													$family_information = get_family_information_from_family($family,$product);
													echo (ucfirst($family_information['family_label']));
												?>
												</td>
												<td align="center" valign="top" class="texteGris">
												<? 	// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
													if (get_number_of_family() > 1){ ?>
														<a href="setup_list_index.php?product=<?=$product?>" target="_top">
															<img src="<?=$niveau0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0"/>
														</a>
												<? 	} //fin condition sur les familles ?>
												</td>
											</tr>
										</table>
									</fieldset>
									</td>
								</tr>
								<tr>
									<td>
									<fieldset>
									<legend class="texteGrisBold">
										&nbsp;
										<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>
										&nbsp;
										Alarm list
										&nbsp;
									</legend>
									<table cellpadding="2" cellspacing="2" border="0">
									<? if (!$alarmes) { ?>
										<tr><td class="texteGrisBold" align="center">No list registered.</td></tr>
									<? } else { ?>

										<tr style="font-size:12px;">
											<th class="texteGrisBold">Name</th>
											<th class="texteGrisBold">Sort Raw / KPI</th>
											<th class="texteGrisBold">Sort</th>
											<th class="texteGrisBold">Network Aggregation</th>
											<th class="texteGrisBold">Time resolution</th>
											<? if ($flag_axe3) { ?> <th class="texteGrisBold">3<sup>rd</sup> Axis</th><? } ?>
											<th></th>
										</tr>

									<? } ?>


		<?php

			// 17/03/2008 - Modif. benoit : ajout d'un boolean indiquant si il existe des alarmes désactivées

			$some_desactivated = false;

			if ($alarmes) {

				$style = "zoneTexteBlanche";

				foreach ($alarmes as $row) {

					// On vérifie si l'envoie des alarmes par email a été configuré.
					$query_send = "select * from sys_alarm_email_sender where id_alarm = '{$row['alarm_id']}' AND alarm_type='alarm_top_worst' ";
					$resultat_send = $db->getall($query_send);
					if ($resultat_send) {
						$send_icon = "send_vert.gif";
						$msg = "Deactivate email sending";
					} else {
						$send_icon = "send_rouge.gif";
						$msg = "Activate email sending";
					}

					if ($style == "zoneTexteBlanche")		$style = "zoneTexteStyleXPFondGris";
					else								$style = "zoneTexteBlanche";

					// 17/03/2008 - Modif. benoit : ajout d'un style particulier pour les lignes d'alarmes désactivées
					if ($row['on_off'] == 0){
						$style_desactivated	= 'style="color:orange;"';
						$some_desactivated	= true;
					} else {
						$style_desactivated = '';
					}

					echo "
						<tr class='$style' $style_desactivated>
							<td>{$row['alarm_name']}</td>
							<td>{$row['list_sort_field']}</td>
							<td>{$row['list_sort_asc_desc']}</td>
							<td align='center'>{$row['agregation_label']}</td>
							<td align='center'>{$row['time']}</td>";
					if ($flag_axe3)
						echo "\n	<td>{$row['agregation_label_axe3']}</td>";

					$display = true;
					// modif 09:05 25/01/2008 Gwénaël
					// modif pour la récupération du paramètre client_type
					if (getClientType($_SESSION['id_user']) == "client" && $row["client_type"] != "client")
						$display = false;
					/*
					*	07/07/2009 - SPS
					*		- pour verifier que l'alarme est utilisee dans un rapport, on se connecte sur le maitre (correction bug 10397)
					*/
					// 20/08/2009 BBX : on évite quand même d'écraser l'instance actuelle, on en a encore besoin :). BZ 11074
                                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
					$dbTemp = Database::getConnection();
					$query_in_report = "
						SELECT page_name
						FROM sys_pauto_page_name a, sys_pauto_config b
						WHERE a.id_page=b.id_page
							AND b.class_object='alarm_top_worst'
							AND b.id_elem='{$row['alarm_id']}' ";
					$result_in_report = $dbTemp->getall($query_in_report);

					?>

					<td>
						<?php
							if ($display) {
								if ($result_in_report) {
									$event = "";
									foreach ($result_in_report as $row_in_report)
										$event .= "\n- ".$row_in_report['page_name'];
									echo "<img src='{$niveau0}images/icones/icone_danger.gif' title=\"This top/worst list is used in these reports :$event\"/>";
								} else {
									echo "<a href='setup_list_index.php?product=$product&family=$family&del=del&alarm_id={$row['alarm_id']}'
											onclick=\"return confirm('Do you want to delete this top/worst alarm ?');\"><img src='{$niveau0}images/icones/drop.gif' title='Delete top/worst list' border='0' width='16' height='17'/></a>";
								}
							}
						?>
					</td>
					<td><a href='setup_alarm_detail.php?product=<?=$product?>&family=<?=$family?>&alarm_type=alarm_top_worst&alarm_id=<?=$row["alarm_id"]?>'><img style='cursor:pointer' src="<?=$niveau0?>images/icones/A_more.gif" border="0" title="Setup list details"></a></td>
				<td title="Send this alarm to ..." onclick="ouvrir_fenetre('setup_alarm_send_to.php?product=<?=$product?>&alarm_id=<?=$row["alarm_id"]?>&alarm_type=alarm_top_worst','Update_Alarm','yes','no',870,600)"><img style='cursor:pointer' src="<?=$niveau0?>images/icones/<?=$send_icon?>" border="0" title="<?=$msg?>"></a></td>
				</tr>
				<?
				}
			}
			?>
			</table>
		</fieldset>
		</td>
	</tr>
	<?php
	// 17/03/2008 - Modif. benoit : si il existe des alarmes désactivées, on affiche un message précisant pourquoi les lignes des alarmes concernées sont en orange
		if ($some_desactivated) {	?>
			<tr>
				<td>
					<img src="<?=$niveau0?>images/icones/i.gif" style="vertical-align:bottom"/>
					<span class="texteGris" style="font:8pt"><?=__T('A_ALARM_DESACTIVATED_INFORMATION')?></span>
				</td>
			</tr>
		<?php } ?>

		</table>
		</div>
		</td>
		</tr>
		</table>
	</td>
	</tr>
	</table>
</form>
</body>
</html>
