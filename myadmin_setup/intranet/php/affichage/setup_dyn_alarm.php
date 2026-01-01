<?
/**
 * @cb50400@
 *
 * 02/09/2010 OJT : Correction bz17047 pour DE Firefox, modificaton des alt en title
 * 31/01/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
 */
/*
*	@cb50000@
*
*	24/06/2009 - Copyright Astellia
*
*	Composant de base version cb_5.0.0.00
*
*	24/06/2009 BBX : correction de la condition d'affichage du header du tableau
*	07/07/2009 - SPS
*		- pour verifier que l'alarme est utilisee dans un rapport, on se connecte sur le maitre (correction bug 10397)
*
*	31/08/2009 GHX
		- Correction du BZ 11318 [CB 5.0][Setup Alarm Dynamic] aucun niveau d'aggrégation d'affiché sur l'index des alarmes pour les familles troisiemes sur un slave
*
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- 09:03 25/01/2008 Gwénaël : modif pour la récupération du paramètre client_type
*
	- maj 17/03/2008, benoit : ajout du champ "on_off" dans la requete de listage des alarmes dynamiques existantes
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
* 	- maj 11/05/2005 Gwénaël : changement "3rd axe" par "3rd Axis"
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
<?php
	/*
		- maj 22 06 2006 christophe : si le client_type=="client" on ne peut pas supprimer l'alarme
		- maj 23 06 2006 stephane : remplacement de network --> agregation_label dans les listings
		- maj 28 06 2006 christophe : correction de la requête de la liste des NA/alarmes, il manquait une jointure sur les familles.
		- maj 03 07 2006 xavier : modification de la requête pour affichage. on affiche plus le threshold.
		- maj 03 10 2006 xavier : alignement des boutons et remplacement du href par un onclick
		- maj 25/10/2006 xavier : suppression d'une alarme impossible si présente dans un rapport

		- maj 05/04/2007 Gwénaël : mise à jour prendre en compte le 3° axe
			- modification de la requête qui récupère les alarmes
	*/
/**
 * Gère les données de paramétrage qui servent à connecter l'application
 * aux bases de données tiers et répertoire racine qui contient des flat file
 */
$product	= intval($_GET['product']);
$family	= $_GET["family"];

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection($product);

$comeback=$PHP_SELF;
session_register('comeback');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');

// Définit si le produit en cours à la fonctionnalité SMS (DE SMS)
$enableAlrmSMS = get_sys_global_parameters( 'enable_alarm_sms', 0, $product );

// Définit si les alarmes SNMP sont activées.
$SNMP = get_sys_global_parameters("snmp_activation");

if ( $SNMP ) {
	$alarm_snmp = array(); // Liste des id_alarm de la trape SNMP/
	// Récupère la liste des alarmes statiques actives dans les trapes SNMP.
	$get_alarms = $db->getall(" SELECT id_alarm	FROM sys_alarm_snmp_sender	WHERE alarm_type='dyn_alarm' ");
	if ($get_alarms)
		foreach ($get_alarms as $row)
			$alarm_snmp[] = $row['id_alarm'];
}

?>
<script src="<?=NIVEAU_0?>js/setup_snmp_alarm.js"></script>

<table width='550' cellpadding='3' cellspacing='0' class='tabPrincipal' align='center'>
<tr>
<td colspan='3'  align='center'>

<table width='100%' border='0' align='center' cellspacing='0' cellpadding='0'>
	<tr>
	<td>
	<div style="padding: 2px;">

	<?
	$title="Dynamic Alarm Setup Interface";
	// 17:18 31/08/2009 GHX
	// Correction du BZ 11318
	$flag_axe3 = get_axe3($family, $product);

	//
	// on supprime une alarme si demandé
	//
	if ($del=='del') {
		$alarm_id = $_GET['alarm_id'];
		$db->execute("delete from sys_definition_alarm_dynamic	where alarm_id='$alarm_id'");

		// - 11/07/2007 christophe : gestion de la suppression dans la table sys_definition_alarm_network_elements.
		$db->execute("DELETE FROM sys_definition_alarm_network_elements	WHERE id_alarm='$alarm_id'	AND type_alarm='alarm_dynamic' ");
		$db->execute("DELETE FROM sys_definition_alarm_exclusion			WHERE id_alarm='$alarm_id'	AND type_alarm='alarm_dynamic' ");
	}

	// 17/03/2008 - Modif. benoit : ajout du champ "on_off" dans la requete de listage des alarmes dynamiques existantes

	//ici le contenu
	//liste les alarmes existantes
	$query = "
		SELECT DISTINCT ON (sdda.alarm_id) sdda.alarm_id, sdda.alarm_name, sdda.on_off,

			CASE WHEN alarm_field_type='raw'	THEN
				CASE WHEN (SELECT distinct edw_field_name_label FROM sys_field_reference a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdda.alarm_field = edw_field_name) IS NOT NULL
				THEN (SELECT distinct edw_field_name_label FROM sys_field_reference a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdda.alarm_field = edw_field_name)
				ELSE	sdda.alarm_field
				END
			ELSE
				CASE WHEN (SELECT distinct kpi_label FROM sys_definition_kpi a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdda.alarm_field = kpi_name) IS NOT NULL
				THEN (SELECT distinct kpi_label FROM sys_definition_kpi a,sys_definition_group_table b WHERE b.edw_group_table=a.edw_group_table AND a.visible = 1 AND a.on_off=1 AND b.family='$family' AND sdda.alarm_field = kpi_name)
				ELSE sdda.alarm_field
				END
			END as alarm_field ,
			sdda.network,
			(SELECT agregation_label FROM sys_definition_time_agregation WHERE agregation = sdda.time) as time,
			sdda.hn_value,
			sdda.id_group_table,
			sdda.client_type,";

	// - modif 05/04/2007 Gwénaël : modification pour récupérer le label du 3° axe
		// utilisation d'une fonction postgres split_part pour exploser le champ network (equivalant de la fonction explode en php)
	if ($flag_axe3) {
		//sous requête pour récupérer le label du na
		$query .= " (SELECT agregation_label FROM sys_definition_network_agregation t0 WHERE t0.agregation=split_part(sdda.network, '_', 1) and t0.family=sdda.family) as agregation_label, ";
		//sous requête pour récupérer le label de l'axe 3
		$query .= " (SELECT agregation_label FROM sys_definition_network_agregation t0 WHERE t0.agregation=split_part(sdda.network, '_', 2) and t0.family=sdda.family) as agregation_label_axe3 ";
	}
	else
		$query .= " (SELECT agregation_label FROM sys_definition_network_agregation t0 WHERE t0.agregation=sdda.network and t0.family=sdda.family) as agregation_label ";


	$query .= "
		FROM sys_definition_alarm_dynamic as sdda
		WHERE sdda.family='$family'
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
				<input type="button" onclick="self.location.href='setup_alarm_detail.php?product=<?=$product?>&family=<?=$family?>&alarm_type=alarm_dynamic&no_loading=true'" class="bouton" name="parameter" value="New Dynamic Alarm" />
			</td>
		</tr>
		<tr>
			<td align="center">
				<fieldset>
					<table cellspacing="2" cellpadding="2" border="0">
						<tr>
							<td align="left" class="texteGris">
								<?php
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
								<?php if (get_number_of_family() > 1) { ?>
									<a href="setup_dyn_alarm_index.php?product=<?=$product?>" target="_top">
										<img src="<?=NIVEAU_0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0"/>
									</a>
								<? } ?>
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
					<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>
					&nbsp;
					Dynamic Alarm list
					&nbsp;
				</legend>
				<table cellpadding="2" cellspacing="2">
				<?php if (!$alarmes) { ?>
					<tr><td class="texteGrisBold" align="center">No dynamic alarm.</td></tr>
				<?php } ?>

				<?php if ($alarmes) { ?>
					<tr>
						<td class="texteGrisBold" align="center">Name</td>
						<td class="texteGrisBold" align="center">Threshold Raw / KPI</td>
						<td class="texteGrisBold" align="center">Network Aggregation</td>
						<td class="texteGrisBold" align="center"><?echo __T('A_ALARM_FORM_LABEL_TIME_RESOLUTION');?></td>
						<? if ($flag_axe3) { ?>
							<td class="texteGrisBold" align="center">3<sup>rd</sup> Axis</td>
						<? } ?>
						<td>&nbsp;</td>
					</tr>
				<? } ?>

		<script>
			// maj 03 10 2006 xavier
			function setup_delete_alarm (family,alarm_id) {
				del_alarm = confirm('Do you want to delete this dynamic alarm ?');
				if (del_alarm)
					window.location.href="setup_dyn_alarm_index.php?product=<?=$product?>&family="+family+"&del=del&alarm_id="+alarm_id;
			}
		</script>
			<?php

				// 17/03/2008 - Modif. benoit : ajout d'un boolean indiquant si il existe des alarmes désactivées
				$some_desactivated = false;

				foreach ($alarmes as $row) {

					// On vérifie si l'envoie des alarmes par email a été configuré.
					$resultat_send	= $db->getall("SELECT *	FROM sys_alarm_email_sender		WHERE id_alarm='{$row['alarm_id']}' 	AND alarm_type='alarm_dynamic' ");
					if ($resultat_send) {
						$send_icon = "send_vert.gif";
						$msg = "Deactivate email sending";
					} else {
						$send_icon = "send_rouge.gif";
						$msg = "Activate email sending";
					}

					if (is_int($i/2))		$style = "zoneTexteBlanche";
					else				$style = "zoneTexteStyleXPFondGris";

					// 17/03/2008 - Modif. benoit : ajout d'un style particulier pour les lignes d'alarmes désactivées
					if ($row['on_off'] == 0) {
						$style_desactivated	= 'style="color:orange;"';
						$some_desactivated	= true;
					} else {
						$style_desactivated = '';
					}

					?>

					<tr class="<?=$style?>" <?=$style_desactivated?>>
						<td><?=$row['alarm_name']?></td>
						<td><?=$row['alarm_field']?></td>
						<td align='center'><?=$row['agregation_label']?></td>
						<td align='center'><?=$row['time']?></td>
						<?php if ($flag_axe3) { ?><td><?=$row['agregation_label_axe3']?></td><?php }

							$display = true;
							// modif 09:03 25/01/2008 Gwénaël
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
									AND b.class_object='alarm_dynamic'
									AND b.id_elem='{$row['alarm_id']}' ";
							$result_in_report = $dbTemp->getall($query_in_report);

							// maj 25/10/2006 xavier
							if ($result_in_report) {
								$event = "icones/icone_danger.gif\" title=\"This dynamic alarm is used in these reports :";
								foreach ($result_in_report as $row_in_report)
									$event .= "\n- {$row_in_report['page_name']}";
								$event .= "\"";

							} else {
								$event= "icones/drop.gif\" title='Delete alarm' style='cursor:pointer' onclick=\"setup_delete_alarm('$family','".$row['alarm_id']."')\"";
							}
						?>

						<td><? if ($display) { ?><img src="<?=NIVEAU_0?>images/<?=$event?>" border="0"><? } ?></td>
						<td><a href="setup_alarm_detail.php?product=<?=$product?>&family=<?=$family?>&alarm_type=alarm_dynamic&alarm_id=<?=$row["alarm_id"]?>&no_loading=true"><img src="<?=NIVEAU_0?>images/icones/A_more.gif" title='Setup alarm details' border='0'></a></td>
						<td title='Send this alarm to ...' onclick="ouvrir_fenetre('setup_alarm_send_to.php?product=<?=$product?>&alarm_id=<?=$row["alarm_id"]?>&alarm_type=alarm_dynamic','Update_Alarm','yes','no',870,600)"><img style='cursor:pointer' src="<?=NIVEAU_0?>images/icones/<?=$send_icon?>" border='0' title="<?=$msg?>"></td>

						<?
						if ( $SNMP ) {

							$send_icon = 	'send_rouge_snmp.jpg';
							$action = 		'add';
							$msg = 'Activate SNMP Trap';

							if (in_array($row['alarm_id'],$alarm_snmp)) {
								$send_icon = 'send_vert_snmp.jpg';
								$action = 'delete';
								$msg = 'Deactivate SNMP Trap';
							}
						?>
						<td onclick="updateSNMPTrap(<?= $product ?>,'<?=$row['alarm_id']?>', 'dyn_alarm', '<?=$action?>','img_dyn_<?=$row['alarm_id']?>')"><img style='cursor:pointer' id="img_dyn_<?=$row["alarm_id"]?>" src="<?=NIVEAU_0?>images/icones/<?=$send_icon?>" border='0' title="<?=$msg?>"></td>
                        <?php
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
                                        AND alarm_type='alarm_dynamic'";

                            if ( $db->getOne( $smsQuery ) == 0 )
                            {
                                $smsIcon        = 'send_rouge_sms.jpg';
                                $smsIconTooltip = __T( SMS_SETUP_ALARM_BUTTON_TOOLTIP_ON );
                            }
                            echo "<td onclick=\"ouvrir_fenetre('setup_alarm_sms.php?product={$product}&alarm_id={$row["alarm_id"]}&alarm_type=alarm_dynamic','Update_Alarm','yes','no',900,700);\">
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
		</td>
	</tr>

	<?php
		// 17/03/2008 - Modif. benoit : si il existe des alarmes désactivées, on affiche un message précisant pourquoi les lignes des alarmes concernées sont en orange
		if ($some_desactivated) {	?>

			<tr>
				<td>
					<img src="<?=NIVEAU_0?>images/icones/i.gif" style="vertical-align:bottom"/>
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
