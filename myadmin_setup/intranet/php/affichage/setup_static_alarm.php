<?
/**
 * @cb50400@
 *
 * 02/09/2010 OJT : Correction bz17047 pour DE Firefox, modificaton des alt en title
 * 31/01/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
 */
/*
*	@cb41000@
*
*	- maj 14/10/2008 SLC : recentrage suite à ajout du DOCTYPE
*	- maj 05/02/2009 SLC : gestion multi-produit, nouvelle topologie
*
*	07/07/2009 - SPS
*		- pour verifier que l'alarme est utilisee dans un rapport, on se connecte sur le maitre (correction bug 10397)
*
*/
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- 09:15 25/01/2008 Gwénaël : modif pour la récupération du paramètre client_type
*
	- maj 17/03/2008, benoit : ajout du champ "on_off" dans la requete de listage des alarmes statiques existantes
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*
*	- 27 02 2007 christophe ; correction bug FS 512, inversement des tooltiptext
*
*/
?>
<?
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	- maj 06 02 2007 christophe : gestion des trapes SNMP.
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
		- maj 03 07 2006 xavier : modification de la requête pour affichage. L'effacement d'une alarme se fait directement dans le fichier (setup_alarm_delete.php devient obsolète)
		- maj 03 10 2006 xavier : alignement des boutons et remplacement du href par un onclick
		- maj 25/10/2006 xavier : suppression d'une alarme impossible si présente dans un rapport
		- maj 05/04/2007 Gwénaël : mise à jour de la requete qui récupèrer les alarmes pour prendre en compte le 3° axe
	*/
	/**
	 * Gère les données de paramétrage qui servent à connecter l'application
	 * aux bases de données tiers et répertoire racine qui contient des flat file
	 */


$product = intval($_GET['product']);

// on se connecte à la db
$db = Database::getConnection( $product );


session_start();
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");

$comeback=$PHP_SELF;
session_register("comeback");

// Définit si le produit en cours à la fonctionnalité SMS (DE SMS)
$enableAlrmSMS = get_sys_global_parameters( 'enable_alarm_sms', 0, $product );

// Définit si les alarmes SNMP sont activées.
$SNMP = get_sys_global_parameters("snmp_activation",0,$product);

if ($SNMP) {
	$alarm_snmp = array(); // Liste des id_alarm de la trape SNMP/
	// Récupère la liste des alarmes statiques actives dans les trapes SNMP.
	$q = " SELECT id_alarm FROM sys_alarm_snmp_sender WHERE alarm_type='static' ";
	$alarms = $db->getall($q);
	if ($alarms > 0)
		foreach ($alarms as $alarm)
			$alarm_snmp[$alarm['id_alarm']] = $alarm['id_alarm'];
	unset($alarms);
}

?>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
<script src="<?=$niveau0?>js/myadmin_omc.js"></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js"></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
<script src="<?=$niveau0?>js/setup_snmp_alarm.js"></script>


<div align="center">
	<img src="<?=$niveau0?>images/titres/setup_alarm_titre.gif"/>
</div>

<table width="550" border=0 align="center" cellspacing="3" cellpadding="3" class="tabPrincipal">

	<?
	// On vérifie qu'il existe des group table pour la famille spécifiée en paramètre.
	$gtables = $db->getall("SELECT *	FROM sys_definition_group_table_ref	WHERE family='$family' ");
	if (!$gtables) {	?>
		<tr>
			<td class="texteGrisBold" align="center">Error : No group table for this family.</td>
		</tr>
		<tr>
			<td align="center"><a href="setup_alarm_main.php" class="texteGris"><u>>>> Back</u></a></td>
		</tr>
		</table>
		<?
		exit;
	}


	$flag_axe3=GetAxe3($family,$product);
	?>
	<tr>
		<td style="padding: 2px;text-align:center;">

			<?
			$title="Set-up Alarm Interface";

			//
			// on supprime une alarme si demandé
			//
			if ($del=='del') {
				$db->execute("delete from sys_definition_alarm_static	where alarm_id='$alarm_id'");

				// - 10/07/2007 christophe : gestion de la suppression dans la table sys_definition_alarm_network_elements et affichage du pointeur sur les icônes.
                                // 10/05/2012 NSE bz 27145 : remplacement de id_alarm par alarm_id
				$db->execute("DELETE FROM sys_definition_alarm_network_elements	WHERE alarm_id='$alarm_id'	AND type_alarm='alarm_static'");
				$db->execute("DELETE FROM sys_definition_alarm_exclusion			WHERE id_alarm='$alarm_id'	AND type_alarm='alarm_static'");
			}


			// 17/03/2008 - Modif. benoit : ajout du champ "on_off" dans la requete de listage des alarmes statiques existantes

			// liste les alarmes existantes
			$query = "
				SELECT DISTINCT sdsa.alarm_id,
					sdsa.alarm_name,
					sdsa.network,
					(SELECT agregation_label FROM sys_definition_time_agregation WHERE agregation = sdsa.time) as time,
					sdsa.hn_value,
					sdsa.client_type, sdsa.on_off,";
					// - modif 05/04/2007 Gwénaël : modification pour récupérer le label du 3° axe
					// utilisation d'une fonction postgres split_part pour exploser le champ network (equivalant de la fonction explode en php)
				if ($flag_axe3) {
					//sous requête pour récupérer le label du na
					$query .= " (
						SELECT agregation_label
						FROM sys_definition_network_agregation t0
						WHERE t0.agregation=split_part(sdsa.network, '_', 1)
							AND t0.family=sdsa.family) as agregation_label, ";
					//sous requête pour récupérer le label de l'axe 3
					$query .= " (
						SELECT agregation_label
						FROM sys_definition_network_agregation t0
						WHERE t0.agregation=split_part(sdsa.network, '_', 2)
							AND t0.family=sdsa.family) AS agregation_label_axe3 ";
				} else
					$query .= "(
						SELECT agregation_label
						FROM sys_definition_network_agregation t0
						WHERE t0.agregation=sdsa.network
							AND t0.family=sdsa.family) AS agregation_label ";
                        // 31/01/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
			$query .= "
				FROM sys_definition_alarm_static as sdsa
				WHERE sdsa.family='$family'
				AND additional_field is null
                                ORDER BY sdsa.alarm_name";

			$alarms = $db->getall($query);
			?>

			<div align='center'>
				<form action="setup_alarm_detail.php" style="margin:10px;">
					<input type="hidden" name="product"	value="<?=$product?>"/>
					<input type="hidden" name="family"		value="<?=$family?>"/>
					<input type="hidden" name="alarm_type"	value="alarm_static"/>
					<input type="hidden" name="no_loading"	value="true"/>
					<input type="submit" class="bouton" name="parameter" value="New Static Alarm" />
				</form>

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
								if (get_number_of_family(false,$product) > 1) { ?>
									<a href="setup_static_alarm_index.php?product=<?=$product?>" target="_top">
										<img src="<?=$niveau0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0"/>
									</a>
							<? 	} //fin condition sur les familles?>
							</td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
				<legend class="texteGrisBold">
						&nbsp;
						<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>
						&nbsp;
						Alarm list
						&nbsp;
					</legend>
					<table cellpadding="2" cellspacing="2" border="0">

						<?
							// 17/03/2008 - Modif. benoit : ajout d'un boolean indiquant si il existe des alarmes désactivées
							$some_desactivated = false;
						?>

						<?	if (!$alarms) { ?>
							<tr><td class="texteGrisBold" align="center">No alarm registered.</td></tr>
						<?	} else { ?>

							<tr>
								<th class="texteGrisBold">Alarm Name</th>
								<th class="texteGrisBold">Network Level</th>
								<th class="texteGrisBold">Time Level</th>
								<? if ($flag_axe3) { ?>	<th class="texteGrisBold">3<sup>rd</sup> Axis</th>	<? } ?>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>

						<? } ?>

						<?php

						foreach ($alarms as $row)
						{
							// On vérifie si l'envoie des alarmes par email a été configuré.
							$query_send = "SELECT *
							FROM sys_alarm_email_sender
							WHERE id_alarm = '{$row['alarm_id']}'
							AND alarm_type='alarm_static'";
							$resultat_send = $db->getAll($query_send);

							if ($resultat_send) {
								$send_icon = "send_vert.gif";
								$msg = "Deactivate email sending";
							} else {
								$send_icon = "send_rouge.gif";
								$msg = "Activate email sending";
							}

							// alternance de style
							if ($style != "zoneTexteBlanche")	$style = "zoneTexteBlanche";
							else							$style = "zoneTexteStyleXPFondGris";

							// 17/03/2008 - Modif. benoit : ajout d'un style particulier pour les lignes d'alarmes désactivées
							if ($row['on_off'] == 0) {
								$style_desactivated	= 'style="color:orange;"';
								$some_desactivated	= true;
							} else
								$style_desactivated = '';

							?>
							<tr class="<?=$style?>" <?=$style_desactivated?> align='left'>
								<td><?echo $row["alarm_name"]?></td>
								<td><?echo $row["agregation_label"]?></td>
								<td><?echo $row["time"]?></td>
								<? if ($flag_axe3) { ?><td><?=$row["agregation_label_axe3"]?></td><? }

								// modif 09:14 25/01/2008 Gwénaël
								// modif pour la récupération du paramètre client_type
								if (getClientType($_SESSION['id_user']) == "client" && $row["client_type"] != "client") {
									$delete_button = '';
								} else {
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
											AND b.class_object='alarm_static'
											AND b.id_elem='{$row['alarm_id']}'";
									$result_in_report = $dbTemp->getAll($query_in_report);

									// maj 25/10/2006 xavier
									if ($result_in_report) {
										$delete_button = "<img src='{$niveau0}images/icones/icone_danger.gif' title=\"This static alarm is used in these reports :";
										foreach ($result_in_report as $row_in_report)
											$delete_button .= "\n- ".$row_in_report['page_name']."";
										$delete_button .= "\" border='0'>";
									} else {
										$delete_button = "<a
											onclick=\"return confirm('Do you want to delete this static alarm ?');\"
											href='setup_static_alarm_index.php?product=$product&family=$family&del=del&alarm_id={$row['alarm_id']}'><img
												src='{$niveau0}images/icones/drop.gif' title='Delete alarm' border='0'></a>";
									}
								}

								?>
								<td><?= $delete_button ?></td>
								<td><a href="setup_alarm_detail.php?product=<?=$product?>&family=<?=$family?>&alarm_type=alarm_static&alarm_id=<?=$row["alarm_id"]?>&no_loading=true"><img src="<?=$niveau0?>images/icones/A_more.gif" border="0" title="Setup alarm details" /></a></td>
								<td	onclick="ouvrir_fenetre('setup_alarm_send_to.php?product=<?=$product?>&alarm_id=<?=$row["alarm_id"]?>&alarm_type=alarm_static','Update_Alarm','yes','no',870,600)"><img style='cursor:pointer' src="<?=$niveau0?>images/icones/<?=$send_icon?>" border="0" title="<?=$msg?>"></td>
								<?
								if ($SNMP) {
									$send_icon	= "send_rouge_snmp.jpg";
									$action		= "add";
									$msg			= "Activate SNMP Trap";

									if (isset($alarm_snmp[$row["alarm_id"]])) {
										$send_icon = "send_vert_snmp.jpg";
										$action = "delete";
										$msg = "Deactivate SNMP Trap";
									}

									?>
									<td onclick="updateSNMPTrap(<?= $product ?>,'<?=$row["alarm_id"]?>', 'static', '<?=$action?>','img_static_<?=$row["alarm_id"]?>')"><img style='cursor:pointer' id="img_static_<?=$row["alarm_id"]?>" src="<?=$niveau0?>images/icones/<?=$send_icon?>" border="0" title="<?=$msg?>"></td>
									<?
								}

                                // Gestion de l'icone SMS (22/07/2011 OJT : DE SMS)
                                if( $enableAlrmSMS )
                                {
                                    // Icone et inifo-bulle par défaut
                                    $smsIcon        = 'send_vert_sms.jpg';
                                    $smsIconTooltip = __T( SMS_SETUP_ALARM_BUTTON_TOOLTIP_OFF );

                                    // L'envoi de SMS est-il configuré pour cette alarme
                                    $smsQuery = "SELECT count(id_alarm) FROM sys_alarm_sms_sender
                                                WHERE id_alarm='{$row['alarm_id']}'
                                                AND alarm_type='alarm_static'";

                                    if ( $db->getOne( $smsQuery ) == 0 )
                                    {
                                        $smsIcon        = 'send_rouge_sms.jpg';
                                        $smsIconTooltip = __T( SMS_SETUP_ALARM_BUTTON_TOOLTIP_ON );
                                    }
                                    echo "<td onclick=\"ouvrir_fenetre('setup_alarm_sms.php?product={$product}&alarm_id={$row["alarm_id"]}&alarm_type=alarm_static','Update_Alarm','yes','no',900,700);\">
                                            <img
                                                style='cursor:pointer'
                                                id='img_sms_{$row["alarm_id"]}'
                                                src='{$niveau0}images/icones/{$smsIcon}'
                                                border='0'
                                                title='{$smsIconTooltip}' />
                                            </td>";
                                }
                                ?>
							</tr>
					<?php } ?>
					</table>
				</fieldset>
			</div>

		<?php
			// 17/03/2008 - Modif. benoit : s'il existe des alarmes désactivées, on affiche un message précisant pourquoi les lignes des alarmes concernées sont en orange
			if ($some_desactivated) {	?>
				<div>
					<img src="<?=$niveau0?>images/icones/i.gif" style="vertical-align:bottom"/>
					<span class="texteGris" style="font:8pt"><?=__T('A_ALARM_DESACTIVATED_INFORMATION')?></span>
				</div>
		<?php } ?>


<style type="text/css">
fieldset {margin:4px; }
</style>

<?php

// $debug = true;
if ($debug) echo $db->displayQueries();

?>
