<?
/*
*	@cb41000@
*
*	01/12/2007 - Copyright Astellia
*
*	Composant de base version cb41000
*
*	01/12/2008 BBX : Refonte :
*		=> utilisation de la classe de donnexion à la base de données
*		=> utilisation des nouvelles constantes
*		=> Suppression des includes périmés
*		=> Utilisation de nouveau labels de messages_display
*		=> Gestion de la suppression dans ce script
*
*/
?>
<?
/*
*	@cb30000@
*
*	24/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 24/07/2007 jérémy : 	suppression de l'iframe et intégration du code source du fichier  " setup_group.php "
*						Suppression des champs INPUT pour les informations sur les utilisateurs 
*						Reformatage du tableau
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

	/*
		Gère les données de paramétrage qui servent à connecter l'application
		aux bases de données tiers et répertoire racine qui contient des flat file
	 */

session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GroupModel.class.php');

// A-t-on demandé la suppression d'un groupe ?
if(isset($_GET['action']) && ($_GET['action'] == 'delete') && isset($_GET['id_group'])) {
	$GroupModel = new GroupModel($_GET['id_group']);
	if(!$GroupModel->getError()) {
		// Suppression du groupe
		$GroupModel->deleteGroup();
		// Redirection de sécurité pour ne pas conserver les paramètres get en cas de refresh
		header("Location: setup_group_index.php");
	}
}

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

// Connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();
?>
<html>

	<head>
		<title><?=__T('A_USER_GROUP_MANAGEMENT_LABEL_INTERFACE')?></title>
	</head>
	
	<body leftmargin="0" topmargin="0">
		<table width="100%" border="0" cellspacing="0" cellpadding="10" align="center">
		
			<tr valign="middle">
			    <td align="center">
					<img align="center" src="<?=NIVEAU_0?>images/titres/group_management_titre.gif" border="0">
				</td>
			</tr>
			
			<tr valign="middle">
				<td align="center">
				
					<table width="500" align="center" valign="middle" cellpadding="15" cellspacing="0" class="tabPrincipal" >
						<tr>
							<td>
								<div align="center" style="padding:5px;">
									<input type="button" onclick="window.location='setup_group_detail.php';" class="bouton" name="parameter" value="New Group">
								</div>
						
								<?									
									$title=__T('A_USER_GROUP_MANAGEMENT_LABEL_INTERFACE_FULL');
									$resultat = GroupModel::getGroups();
									$nombre_resultats = count($resultat);
								?>
								<table border="0" align="center" cellspacing="2" cellpadding="2" >
								
								<? if ($nombre_resultats) { ?>
									<tr>
										<th class="texteGrisBold" width="250px">&nbsp;<?=__T('A_USER_GROUP_MANAGEMENT_LABEL_GROUP_NAME')?>&nbsp;</th>
										<th class="texteGrisBold">&nbsp;</th>
										<th class="texteGrisBold">&nbsp;</th>
									</tr>
								<? } else { ?>
									<tr>
										<th class="texteGrisBold" align="center" colspan="3"><?=__T('A_JS_GROUP_MANAGEMENT_NO_GROUP_CREATED')?></th>
									</tr>
								<? } ?>

								<?
									$i = 0;
									foreach ($resultat as $row) {
										$style_row = ($i%2 == 0) ? "bgcolor=#DDDDDD" : "bgcolor=#ffffff";
								?>
									<tr align="center" class="texteGris" style="padding: 2px;">
										<td <?=$style_row?> align="left"><?=$row["group_name"]?></td>
										<td <?=$style_row?>>
											<a href="setup_group_index.php?action=delete&id_group=<?=$row["id_group"]?>" onclick="return confirm('<?=__T('A_USER_GROUP_MANAGEMENT_CONFIRM_DELETION',$row["group_name"])?>');" >
												<img src="<?=NIVEAU_0?>images/icones/drop.gif" border="0" alt="Delete group">
											</a>
										</td>
										<td <?=$style_row?>>
											<a href="setup_group_detail.php?id_group=<?=$row["id_group"]?>" >
												<img src="<?=NIVEAU_0?>images/icones/A_more.gif" border="0" alt="Edit group" >
											</a>
										</td>
									</tr>
								<? 
										$i++;
									} ?>
								
								</table>
								
							</td>
						</tr>
					</table>
					
				</td>
			</tr>
		</table>
		
	</body>
	
</html>
