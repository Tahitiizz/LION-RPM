<?
/*
*	@cb41000@
*
*	01/12/2008 - Copyright Astellia
*
*	Composant de base cb41000
*
*	01/12/2008 BBX : refonte du script :
*		=> réécriture du formulaire sans la classe FPform
*		=> ajout de fonction JS pour gérer les select box et la vérification du formulaire
*		=> utilisation des nouvelles constantes
*		=> utilisation des modèles "UserModel" et "GroupModel" pour gérer les données
*		=> suppression des includes périmés
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_groupe entre cote  [REFONTE CONTEXTE]
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
*	- maj 24/07/2007 jérémy : 	Ajout de l'intranet_top
*						Modification des liens cible après traitement pour retourner sur la page  " setup_group_index.php "
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
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GroupModel.class.php');

// Connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if(!isset($_GET['id_group'])) $_GET['id_group'] = 0;

// Récupération de l'id groupe à utiliser
$idGroup = ($_GET['id_group'] != '') ? $_GET['id_group'] : 0;
$idGroup = isset($_POST['group']['id_group']) ? $_POST['group']['id_group'] : $idGroup;

// Instanciation du groupe
$GroupModel = new GroupModel($idGroup);
$groupValues = Array();
if(!$GroupModel->getError()) {
	$groupValues = $GroupModel->getValues();
}

// Gestion des erreurs
$msg_erreur = '';

// Création d'un groupe ?
if(isset($_POST['group'])) {
	// Un groupe porte-t-il déjà ce nom ?
	$query = "SELECT id_group FROM sys_user_group 
	WHERE group_name = '{$_POST['group']['group_name']}' 
	AND id_group != '{$idGroup}'";
	if(count($database->getAll($query)) > 0) {
		$msg_erreur = '<div class="errorMsg">'.__T('A_JS_GROUP_MANAGEMENT_NAME_ALREADY_USED').'</div>';
	}
	else {
		// On ajoute les valeurs au groupe
		$GroupModel->setValue('group_name',$_POST['group']['group_name']);
		$GroupModel->setValue('on_off',1);
		// Si update, en fait on supprime le groupe pour le recréer avec les nouvelles informations (l'id est conservé)
		if(!$GroupModel->getError()) {
			// Suppression du groupe
			$GroupModel->deleteGroupOnly();
		}		
		// Si nouveau groupe
		else {
			// On créé le groupe
			$GroupModel->addGroup();
		}
		// On ajoute les users
		$GroupModel->addUserList(explode('|',$_POST['group']['users']));
		// On redirige enfin sur la page d'index des groupes
		header("Location: setup_group_index.php");
	}
}

// Récupération des utilisateurs sélectionnés
$array_selected_users = $GroupModel->getUsers();
// Calcul des utilisateurs disponibles
$array_available_users = Array();
foreach(UserModel::getUsers() as $array_user) {
	$selected = false;
	foreach($array_selected_users as $array_selected) {
		if($array_selected['id_user'] == $array_user['id_user']) $selected = true;
	}
	if(!$selected) $array_available_users[] = $array_user;
}

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

?>
<script type="text/javascript">
	/****
	* 01/12/2008 BBX : permet de transvaser des éléments d'une liste à une autre
	* @param string : id_zone_maitre
	* @param string : id_zone_esclave
	* @param int : sens de transfert(1=maitre vers esclave)
	****/
	function move_elements(id_zone_maitre,id_zone_esclave,sens)
	{			
		// Fonction qui bouge les éléments
		function move(idz1,idz2)
		{
			var array_to_remove = new Array();
			for(var i = 0; i < $(idz1).options.length; i++)
			{
				if($(idz1).options[i].selected)
				{
					$(idz2)[$(idz2).options.length] = new Option($(idz1).options[i].text, $(idz1).options[i].value);						
					$(idz1).options[i] = null;
					i--;
				}
			}	
		}
		// Selon le sens
		if(sens == 1) {
			move(id_zone_maitre,id_zone_esclave);
		}
		else {
			move(id_zone_esclave,id_zone_maitre);
		}
		// Ecriture dans le champ "group_users"
		$('group_users').value = '';
		for(var i = 0; i < $(id_zone_esclave).options.length; i++)
		{
			var sep = (i == 0) ? '' : '|';
			$('group_users').value += sep+$(id_zone_esclave).options[i].value;
		}
	}
	
	/****
	* 01/12/2008 BBX : vérifie le formulaire
	****/
	function checkForm()
	{
		// Contrôle sur le nom du groupe
		var expression = new RegExp(/^[\w][\w\.\s\-]+$/);
		if(!expression.test($('group_name').value)) {
			alert('<?=__T('A_USER_GROUP_MANAGEMENT_ENTER_GROUP_NAME')?>');
			$('group_name').focus();
			return false;
		}
		// Contrôle sur les utilisateurs sélectionnés
		if($('group_selected_users').options.length == 0) {
			alert('<?=__T('A_JS_GROUP_MANAGEMENT_NO_USER_SELECTED')?>');
			return false;
		}
	}
</script>
<!-- Affichage du formulaire -->
<center>
	<img src="<?=NIVEAU_0?>images/titres/new_group_titre.gif" border="0" />
	<br /><br />
	<div style="width:750px;">
	<?=$msg_erreur?>
		<form action="setup_group_detail.php" method="post" onsubmit="return checkForm()">
		<input type="hidden" name="group[id_group]" value="<?=$idGroup?>" />
			<table cellpadding="10" cellspacing="0" width="100%" class="tabPrincipal" border="0">
				<tr>
					<td colspan="2" align="center">
						<a href="setup_group_index.php">
							<b><?=__T('G_PROFILE_FORM_LINK_BACK_TO_THE_LIST')?></b>
						</a>
					</td>
				</tr>
				<tr>
					<td align="left">
						<span class=texteGris><?=__T('A_USER_GROUP_MANAGEMENT_LABEL_GROUP_NAME')?>*</span>
					</td>
					<td align="left" width="600">
						<input id="group_name" type="text" size="15" name="group[group_name]" value="<?=$groupValues['group_name']?>" />
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left" style="padding-bottom:5px">
						<span class="texteGris"><b><?=__T('A_USER_GROUP_MANAGEMENT_TITLE_GROUP_MEMBERS')?></b></span>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center" style="padding-top:5px">
						<fieldset>
							<legend class=texteGrisBold>&nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif">&nbsp;<span class='texteGris'><?=__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_TITLE_USER_SELECTOR')?></span></legend>
							<table width="100%">
								<tr>
									<td align="left">
										<span class=texteGrisBold><u><?=__T('A_USER_GROUP_MANAGEMENT_LIST_NON_SUSCRIBED_USERS')?></u></span>
										<br />
										<!-- Available users list -->
										<select id="group_available_users" multiple style="width:325px;height:150px;" ondblclick="move_elements('group_available_users','group_selected_users',1)">
										<?php
										foreach($array_available_users as $array_user) {
											echo '<option value="'.$array_user['id_user'].'">'.$array_user['login'].' - '.$array_user['username'].'</option>';
										}
										?>
										</select>
										<!-- ***************** -->
									</td>
									<td valign="middle" align="center">
										<input type="button" value="->" onclick="move_elements('group_available_users','group_selected_users',1)" style="width:30px;height:25px;" />
										<input type="button" value="<-" onclick="move_elements('group_available_users','group_selected_users',2)" style="width:30px;height:25px;" />
									</td>
									<td align="left">
										<span class=texteGrisBold><u><?=__T('A_USER_GROUP_MANAGEMENT_LIST_SUSCRIBED_USERS')?></u></span>
										<br />
										<!-- Selected users list -->
										<select id="group_selected_users" multiple style="width:325px;height:150px;" ondblclick="move_elements('group_available_users','group_selected_users',2)">
										<?php	
										$selected_user_list = Array();
										foreach($array_selected_users as $array_user) {
											echo '<option value="'.$array_user['id_user'].'">'.$array_user['login'].' - '.$array_user['username'].'</option>';
											$selected_user_list[] = $array_user['id_user'];
										}
										?>
										</select>	
										<!-- ***************** -->
										<input type="hidden" id="group_users" name="group[users]" value="<?php echo implode('|',$selected_user_list); ?>" />
									</td>
								</td>
							</table>
						</fieldset>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" class="bouton" value="Save" />
					</td>
				</tr>
			</table>
		</form>
	</div>
</center>
</body>
</html>
