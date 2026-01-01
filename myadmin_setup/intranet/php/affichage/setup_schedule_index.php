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
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre schedule_id entre cote au niveau des inserts  [REFONTE CONTEXTE]
*/
?>
<?
/*
*	@cb30000@
*
*	20/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 20/07/2007 jérémy : 	suppression de l'iframe et intégration du code source du fichier setup_schedule.php
*						Suppression des champs INPUT pour les informations sur les schedules
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
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
include_once(REP_PHYSIQUE_NIVEAU_0.'php/deploy_and_compute_functions.php');

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Reporting'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/
?>
<script src="<?=NIVEAU_0?>js/myadmin_omc.js"></script>

		<table width="100%" align="center" border="0" cellpadding="10" cellspacing="3">
			<tr valign="middle">
			    <td align="center">
					<img src="<?=NIVEAU_0?>images/titres/schedule_setup_interface.gif"/>
				</td>
			</tr>
			
			<tr valign="middle">
			    <td align="center">
				
					<table width="550px" align="center" valign="middle" cellpadding="15" cellspacing="0" class="tabPrincipal">
						<tr>
							<td align="center" style="padding:15px;">
								<input 	type="button" 
										onclick="window.location='setup_schedule_detail.php'" 
										class="bouton" 
										name="parameter" 
										value="<?=__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_BTN_NEW_SCHEDULE')?>">
							</td>
						</tr>
						<tr>
							<td align="center">
								<?
								$title="Schedule Setup Interface";
								//include("header_design.php");
								//ici le contenu
								//liste les alarmes existantes
								$query = "SELECT * FROM sys_report_schedule ORDER BY schedule_name ASC";
								$resultat = $database->execute($query);
								$nombre_resultats = $database->getNumRows();
								?>
								
								<table border="0" cellspacing="2" cellpadding="2">
								<? if ($nombre_resultats) { ?>
									<tr>
										<th class="texteGrisBold" width="200px"><?=__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_SCHEDULE_NAME')?></th>
										<th class="texteGrisBold" width="100px"><?=__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_PERIOD')?></th>
										<th class="texteGrisBold">&nbsp;</th>
										<th class="texteGrisBold">&nbsp;</th>
									</tr>
								<? } else { ?>
									<tr>
										<th colspan="2" class="texteGrisBold" align="center"><?=__T('A_E_TASK_SCHEDULER_SCHEDULE_SETUP_NO_REPORT_CREATED')?></th>
									</tr>
								<? } ?>

								<?
								$i = 0;
								foreach($database->getAll($query) as $row) {
									$style_row = ($i%2 == 0) ? "bgcolor=#DDDDDD" : "bgcolor=#ffffff";
									
									switch($row['period']) {
								   		case 'hour':
											$row['period'] = 'Hourly';
										break;
								   		case 'day':
											$row['period'] = 'Daily';
										break;
								   		case 'week':
											$row['period'] = 'Weekly';
										break;
								   		case 'month':
											$row['period'] = 'Monthly';
										break;
									}
								?>
									<tr align="center" class="texteGris">
										<td <?=$style_row?>><?=$row["schedule_name"]?></td>
										<td <?=$style_row?>><?=$row["period"]?></td>
										<td <?=$style_row?>>
											<a 	onclick="return confirm('<?=__T('A_JS_TASK_SCHEDULER_SCHEDULE_SETUP_CONFIRM_DELETE_SCHEDULE',$row["schedule_name"])?>');" 
												href="setup_schedule_delete.php?schedule_id=<?=$row["schedule_id"]?>">
												<img src="<?=NIVEAU_0?>images/icones/drop.gif" border="0" alt="Delete">
											</a>
										</td>
										<td <?=$style_row?>>
											<a	title="Schedule and Subscribers"
												href="setup_schedule_detail.php?schedule_id=<?=$row["schedule_id"]?>" >
												<img src="<?=NIVEAU_0?>images/icones/A_more.gif" border="0" alt="Schedule and Subscribers" >
											</a>
										</td>
									</tr>
					       <?php 	$i++;
								} ?>
								</table>
								
								</div>
							</td>
						</tr>
					</table>

				</td>
			</tr>
		</table>
		
	</body>
	
</html>
