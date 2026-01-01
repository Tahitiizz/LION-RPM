<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
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
*		=> utilisation de la classe JS drag-drop-tree.js afin de gérer les menus
*		=> Gestion de n niveaux de profondeur des menus
*
*	30/01/2009 GHX
*		- modification du séparateur "_" par "__" pour le drag&drop dans les id des balises li
*	14/05/2009 - SPS : 
*		- ajout d'un div pour l'edition du profil 
*		- quand l'utilisateur clique sur new, on cache la zone d'edition du profil
*
*	30/06/2009 BBX :
*		=> Ajout de la fonction getChildrenMenus qui récupère les ids des menus enfant. BZ 9821
*		=> Activation / désactivation des enfants du menu activé / désactivé. BZ 9821
*
*	07/08/2009 GHX
*		- Correction du BZ 9826 [REC][T&A CB 5.0][PROFILE MANAGEMENT]:Si l'on désactive un menu avant de le déplacé, la désactivation n'est pas pris en compte
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
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/MenuModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');

// *************
// Déclaration de la fonction qui parcoure récursivement les menus
// et récupère ses enfants, selon la configuration du profil
// @param int 	id du menu à parcourir
// @param object	objet ProfileModel
// *************
function displayChildren($idMenu,$ProfileModel) 
{
	// Récupération des valeurs du profil
	$profileValues = $ProfileModel->getValues();
	// Récupération des menus enfants
	$childrenMenus = ($profileValues['profile_type'] == 'user') ? $ProfileModel->getMenusUserEnfant($idMenu) : $ProfileModel->getMenusAdminEnfant($idMenu);
	echo '<ul>';
	// Parcours des menus
	foreach($childrenMenus as $array) {	
		$class = ($array['on_off'] == 1) ? 'enabled' : 'disabled';		
		echo '<li id="menu__'.$array['id_menu'].'">';
		echo '&nbsp;<span id="'.$array['id_menu'].'" onclick="toggleMenuActivation(this)" class="'.$class.'">'.$array['libelle_menu'].'</span>&nbsp;';
		// récupération récursive des enfants
		displayChildren($array['id_menu'],$ProfileModel);
		echo '</li>';
	}
	echo '</ul>';
}
// *************

// Connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Profile Management'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Récupération d'un éventuel id_profile
$idProfile = isset($_GET['id_profile']) ? $_GET['id_profile'] : 0;
$idProfile = isset($_POST['profile']['id_profile']) ? $_POST['profile']['id_profile'] : $idProfile;

// Instanciation de ProfileModel
$ProfileModel = new ProfileModel($idProfile);
$profileValues = Array();
if(!$ProfileModel->getError()) {
	$profileValues = $ProfileModel->getValues();
	// Récupère les menus du profil
	$profileMenus = $ProfileModel->getMenus();
	// Récupère les users du profil
	$profileUsers = $ProfileModel->getUsers();
}

// Gestion des erreurs
$msg_erreur = '';

// ******************************
// Suppression du profil
// ******************************
if(isset($_GET['action']) && ($_GET['action'] == 'delete')) {
	// On vérifie que le profil à supprimer est correct
	if(!$ProfileModel->getError()) {
		// Si aucun user, on supprime
		if(count($profileUsers) == 0) {		
			$ProfileModel->deleteProfile();
			// Redirection
			header("Location: profile_management.php");
			exit;
		}
		$userList = '(';
		// Récupération de la liste des users
		foreach($profileUsers as $user) {
			$userList .= ($userList == '(') ? $user['username'] : ','.$user['username'];
		}
		// Affichage d'un message d'erreur
		$msg_erreur = '<div class="errorMsg">'.__T('A_PROFILE_MANAGEMENT_DELETION_NOT_ALLOWED',$userList.')').'</div>';
	}
	else {
		// Affichage d'un message d'erreur
		$msg_erreur = '<div class="errorMsg">'.__T('A_PROFILE_MANAGEMENT_NO_PROFILE_SELECTED').'</div>';
	}
}

// ******************************
// Nouveau profil
// ******************************
if(isset($_GET['action']) && ($_GET['action'] == 'new')) {
	if(isset($_GET['type'])) {
		$ProfileModel->setValue('profile_type',$_GET['type']);
		// Récupère les valeurs du profile
		$profileValues = $ProfileModel->getValues();
		// Menus
		if($_GET['type'] == 'user')
			$profileMenus = MenuModel::getUserMenus();
		else
			$profileMenus = MenuModel::getAdminMenus();
		// Déclaration du tableau des users
		$profileUsers = Array();
	}
}

// ******************************
// Enregistrement du  profil
// ******************************
if(isset($_POST['profile']['name'])) {
	// NOUVEAU PROFIL
	if($ProfileModel->getError()) {
		// On regarde sile nom existe déjà
		$query = "SELECT id_profile FROM profile WHERE profile_name = '{$_POST['profile']['name']}'";
		// Si un profil porte déjà ce nom
		if(count($database->getAll($query)) > 0) {
			// Affichage d'un message d'erreur
			$msg_erreur = '<div class="errorMsg">'.__T('A_PROFILE_MANAGEMENT_NAME_ALREADY_EXISTS').'</div>';
			// On revien en mode new
			$_GET['action'] = 'new';
			$_GET['type'] = $_POST['profile']['type'];
			$ProfileModel->setValue('profile_type',$_POST['profile']['type']);
			// Récupère les valeurs du profile
			$profileValues = $ProfileModel->getValues();
			// Menus
			if($_GET['type'] == 'user')
				$profileMenus = MenuModel::getUserMenus();
			else
				$profileMenus = MenuModel::getAdminMenus();
			// Déclaration du tableau des users
			$profileUsers = Array();
			// On efface le formulaire posté
			unset($_POST);
		}
		// Si un profil ne porte pas déjà ce nom
		else {
			// Ajout des valeurs postées
			$ProfileModel->setValue('profile_type',$_POST['profile']['type']);
			$ProfileModel->setValue('profile_name',$_POST['profile']['name']);
			// Création du profil
			$idProfile = $ProfileModel->addProfile();
                        // 09/09/2011 BBX
                        // BZ 16148 : on initialise la position par défaut des menus
                        $ProfileModel->initMenus();
			// Récupère les menus du profile
			$profileMenus = $ProfileModel->getMenus();
			// Récupère les users
			$profileUsers = $ProfileModel->getUsers();
                        // 23/02/2012 NSE DE Astellia Portal Lot2
                        $p = new ProductModel('');
                        $product = $p->getValues();
                        // On transmet le profile au Portail
                        // déclaration des variables nécessaires
                        //?guid_hexa='.APPLI_GUID_HEXA.'&guid_appli='.APPLI_GUID_NAME.'&appli_name='.$product['sdp_label'].'&appli_path='.$product['sdp_ip_adress'].'/'.$product['sdp_directory'].'&casIp='.CAS_SERVER
                        $guid_hexa=APPLI_GUID_HEXA;
                        $guid_appli=APPLI_GUID_NAME;
                        // 24/04/2012 BBX
                        // BZ 26955 : il y a 2 "p" à address :)
                        $appli_path=$product['sdp_ip_address'].'/'.$product['sdp_directory'];
                        $casIp=CAS_SERVER;
                        // 24/04/2012 BBX
                        // On cache le retour texte
                        ob_start();
                        include(REP_PHYSIQUE_NIVEAU_0.'scripts/generatePAAXml.php');
                        ob_end_clean();
		}
	}
	// UPDATE PROFIL
	else {
		// Modification du nom
		$ProfileModel->setValue('profile_name',$_POST['profile']['name']);
		// Enregistrement
		$ProfileModel->updateProfile();
	}
	// DANS TOUS LES CAS
	// Il faut forcer les menus "overtime" et "overnetwork" à faire parti du profil
	$ProfileModel->checkMandatoryMenus();	
	// Récupération des valeurs
	$profileValues = $ProfileModel->getValues();
	// Deploiement des modifications
	$ProfileModel->buildProfileToMenu();
}

// ******************************
// Enregistrement des données menu
// ******************************
if(isset($_POST['menu'])) {
	// Récupération des menus actifs
	$activeMenus = ($_POST['menu']['activation'] != '') ? explode('|',$_POST['menu']['activation']) : Array();	
	
	// Désactivation des menus à désactiver
	$ProfileModel->removeProfileMenusFromArray($activeMenus);
	// Activation des menus à réactiver
	foreach($activeMenus as $idMenu) {
		if(!$ProfileModel->isMenuInProfile($idMenu)) {
			$ProfileModel->addMenuToProfile($idMenu);
		}
	}
	// Deploiement des modifications
	$ProfileModel->buildProfileToMenu();
	// Traitement des actions
	$actionList = explode('|s|',$_POST['menu']['actions']);
	
	foreach($actionList as $actions) {
		list($src,$dst,$prv) = explode('|',$actions);
		// Découpage des valeurs afin de séparer le préfixe de l'id
		list($pref,$idMenuSrc) =  explode('__',$src);
		list($pref,$idMenuDst) =  explode('__',$dst);
		list($pref,$idMenuPrv) =  explode('__',$prv);
		
		// 16:44 07/08/2009 GHX
		// Correction du BZ 9826
		if ( !in_array($idMenuSrc, $activeMenus) )
			continue;
			
		// Instanciation des Menus
		$MenuModelSrc = new MenuModel($idMenuSrc,$idProfile);
		$MenuModelDst = new MenuModel($idMenuDst,$idProfile);
		$MenuModelPrv = new MenuModel($idMenuPrv,$idProfile);
		// Explication : le menu à modifier correspond à "MenuModelSrc". 
		// Son nouveau parent se calcul depuis son voisin du dessus : "MenuModelPrv"
		// Si "MenuModelPrv" n'est pas disponible, le nouveau parent sera "MenuModelDst"
		if(!$MenuModelPrv->getError()) {
			// Récupération des informations du voisin du dessus
			$MenuValuesPrv = $MenuModelPrv->getValues();
			// Mise à jour du parent
			$ProfileModel->setNewParent($idMenuSrc,$MenuValuesPrv['id_menu_parent']);
			// Mise à jour de la position
			$ProfileModel->setMenuPosition($MenuValuesPrv['position']+1,$idMenuSrc);
		}
		elseif(!$MenuModelDst->getError()) {
			// Récupération des informations du voisin du dessus
			$MenuValuesDst = $MenuModelDst->getValues();
			// Mise à jour du parent
			$ProfileModel->setNewParent($idMenuSrc,$MenuValuesDst['id_menu']);
		}
	}
	// Suppression des objets que l'on utilise plus
	unset($MenuModelSrc);
	unset($MenuModelDst);
	unset($MenuModelPrv);
	unset($MenuToManage);
	// Récupère les menus du profile mis à jours
	$profileMenus = $ProfileModel->getMenus();
}
// ***********************************************************************************

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

?>
<script type="text/javascript" src="<?=NIVEAU_0?>js/cookie.js"></script>
<script type="text/javascript" src="<?=NIVEAU_0?>js/drag-drop-tree.js"></script>
<script type="text/javascript">
/******
* 30/06/2009 : BBX. BZ 9821
* Cette fonction permet de récupérer les ids des enfants d'un menu
* @param : id du menu parent
* @return : tableau des ids enfans ou false si pas d'enfants
******/
function getChildrenMenus(MomId)
{
	var returnArray = new Array();
	var children = $(MomId).adjacent('li');	
	
	if(children != '') {
		var subChildren = $(children[0].id).adjacent('span');
		for(s = 0; s < subChildren.length; s++) {
			returnArray.push(subChildren[s].id);
		}
	}

	if(returnArray.length > 0) {
		return returnArray;
	}
	else {
		return false;
	}
}

/******
* Cette fonction permet de sauvegarder les changements sur
* l'activation / désactivation de menus
* @param : object	element span
******/
function toggleMenuActivation(element)
{
	// 30/06/2009 BBX : récupération des enfants du menu. BZ 9821
	var children = getChildrenMenus(element.id);

	if(element.className == 'disabled') {
		element.className = 'enabled';
		if($('menu_activated').value == '') {
			var elemTab = new Array(element.id);
		}
		else {
			var elemTab = $('menu_activated').value.split('|');
			elemTab.push(element.id);
		}
		// 30/06/2009 BBX : réativation des enfants du menu. BZ 9821
		for(c = 0; c < children.length; c++) {
			elemTab.push(children[c]);
			$(children[c]).className = 'enabled';
		}
	}
	else {
		element.className = 'disabled';
		var elemTab = $('menu_activated').value.split('|');
		elemTab = elemTab.without(element.id);
		// 30/06/2009 BBX : désactivation des enfants du menu. BZ 9821
		for(c = 0; c < children.length; c++) {
			$(children[c]).className = 'disabled';
			elemTab = elemTab.without(children[c]);
		}
	}
	$('menu_activated').value = '';
	for(var i = 0; i < elemTab.length; i++) {
		var sep = (i == 0) ? '' : '|';
		$('menu_activated').value += sep+elemTab[i];
	}
}

/******
* Cette fonction permet de gérer l'appel de la suppression d'un profil
******/
function deleteProfile()
{
	if(confirm('<?=__T('A_PROFILE_MANAGEMENT_CONFIRM_DELETION')?>')) {
		document.location.href='profile_management.php?action=delete&id_profile='+$('profile_selector').value;
	}
}

/******
* Cette fonction permet de gérer l'appel d'un nouveau profil (choix admin / user)
******/
function newProfile()
{
	$('new_profile_type_selection').style.display = 'block';
	/* 14/05/2009 - SPS : qd on clique sur new, on cache la zone d'edition du profil */
	$('edit_profile').style.display = 'none';
}

/******
* Soumet les choix de l'utilisateur et procède à la redirection vers la création d'un nouveau profil
******/
function submitNewProfile()
{
	var type = ($('radio_user_profile').checked) ? 'user' : 'admin';
	document.location.href='profile_management.php?action=new&type='+type;
}

/******
* Vérifie le formulaire avant de poster
******/
function checkForm()
{
	var expression = new RegExp(/^[\w][\w\.\s\-]+$/);
	if(!expression.test($('profile_name').value)) {
		alert('<?=__T('A_PROFILE_MANAGEMENT_ENTER_A_PROFILE_NAME')?>');
		$('profile_name').focus();
		return false;
	}
	return true;
}
</script>
<!-- Style de la page -->
<style type="text/css">
.drag-drop-tree li {
	list-style-type:none;
	padding:0;
	margin:0;
}
.drag-drop-tree ul {
	padding:0;margin:0;padding-left:20px;
}
.drag-drop-tree-node-handle {
	cursor:pointer;
}
.drag-drop-tree-dropon-node > .drag-drop-tree-node-handle {
	border-bottom:10px #1A8BD4 solid;
	border-right:10px #1A8BD4 solid;
}
.drag-drop-tree-dropafter-node > .drag-drop-tree-node-handle {
	border-right-color:transparent;
}
#menu_tree {
	font-family:Arial;
	font-size:8pt;
	font-weight:normal;
	background-color:#FFF;
	width:325px;
	padding:10px;
	border:1px solid #7F9DB9;
}
span.disabled {
	font-style:italic;
	color:#BBBBBB;
	cursor:pointer;
}
span.enabled {
	font-style:normal;
	color:#000000;
	cursor:pointer;
}
</style>
<!-- Affichage du formulaire -->
<center>
	<img src="<?=NIVEAU_0?>images/titres/user_profile_management_titre.gif" border="0" />
	<br /><br />
	<div style="width:450px;">
	<?=$msg_erreur?>
		<form action="profile_management.php" method="post" onsubmit="return checkForm()">
			<table cellpadding="10" cellspacing="0" width="100%" class="tabPrincipal" border="0">
				<tr>
					<td align="center">
					<?php
					// Si on est pas en création d'un profil, on affiche la liste de sélection des profils existans
					if(!isset($_GET['action']) || ($_GET['action'] != 'new')) {
					?>
						<select id="profile_selector" name="profile[id_profile]" onchange="if(this.selectedIndex != 0) document.location.href='profile_management.php?id_profile='+this.value;">
							<option value="0">**&nbsp;<?=__T('A_PROFILE_MANAGEMENT_LIST_TITLE')?>&nbsp;**</option>
							<?php
							foreach(ProfileModel::getProfiles() as $array_profile) {
								$selected = ($idProfile === $array_profile['id_profile']) ? ' selected' : '';
								echo '<option value="'.$array_profile['id_profile'].'"'.$selected.'>'.$array_profile['profile_name'].' ['.$array_profile['profile_type'].']</option>';
							}
							?>
						</select>
					<?php
					}
					// Si on est en mode création d'un profil, on affiche un lien de retour
					else {
					?>
						<div id="texteGris" align="center">
							<a href="profile_management.php"><b><?=__T('G_PROFILE_FORM_LINK_BACK_TO_THE_LIST')?></b></a>
						</div>
					<?php
					}
					?>
					</td>
				</tr>
				<?php
				// Si on est pas sur la création d'un nouveau profil, on affiche les boutons New et Delete
				if(!isset($_GET['action']) || ($_GET['action'] != 'new')) {
				?>
				<tr align="center">
					<td>
						<input type="button" value="New" class="bouton" onclick="newProfile()" />
						&nbsp;
						<?php
						// Si aucun user n'est lié au profil, on permet la suppression
						if(count($profileUsers) == 0) {
							echo '<input type="button" value="Delete" class="bouton" onclick="deleteProfile()" />';
						}
						// Sinon, on condamne le bouton de suppression
						else {
							echo '<input type="button" value="Delete" class="boutonRouge" onmouseover="popalt(\''.__T('A_PROFILE_MANAGEMENT_DELETION_NOT_ALLOWED').'\')" />';
						}
						?>
						<!-- Zone cachée de sélection du type d'un nouveau profil -->
						<div class="infoBox" id="new_profile_type_selection" style="display:none;margin-top:10px;">
							<input id="radio_user_profile" type="radio" name="profile_type" value="user" checked />
							<label for="radio_user_profile">User</label>
							<input id="radio_admin_profile" type="radio" name="profile_type" value="admin" />
							<label for="radio_admin_profile">Admin</label>
							&nbsp;
							<input type="button" class="bouton" value="Ok" onclick="submitNewProfile()" />
						</div>
					</td>
				</tr>
				<?php
				}
				?>
				<tr>
					<td align="left">
						<?php
						/* 14/05/2009 - SPS : ajout d'un div pour l'edition du profil */
						?>
						<div id="edit_profile">
						<?php
						// Si le type de profil est défini, on affiche : l'aide, les menus, le bouton Save
						if(($profileValues['profile_type'] == 'user') || ($profileValues['profile_type'] == 'admin')) {
						?>
						<span class="texteGris"><?=__T('A_PROFILE_MANAGEMENT_PROFILE_NAME')?>*</span>&nbsp;<input id="profile_name" type="text" name="profile[name]" size="20" value="<?=$profileValues['profile_name']?>" />
						<input type="hidden" name="profile[type]" value="<?=$_GET['type']?>" />		
						<br /><br />
						<div>
							<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" onclick="Effect.toggle('help_box_1', 'slide');" onmouseover="popalt('<?=__T('A_PROFILE_MANAGEMENT_HIDE_DISPLAY_HELP')?>')" />
							<div id="help_box_1" class="infoBox">
								<?=__T('A_PROFILE_MANAGEMENT_HELP')?>
							</div>
						</div>						
						<?php
							// Récupération des menus racines
							$rootMenus = ($profileValues['profile_type'] == 'user') ? $ProfileModel->getRootUserMenus() : $ProfileModel->getRootAdminMenus();						
							echo '<ul id="menu_tree">';
							foreach($rootMenus as $array_menu) {
								$class = ($array_menu['on_off'] == 1) ? 'enabled' : 'disabled';	
								echo '<li id="menu__'.$array_menu['id_menu'].'">&nbsp;<span id="'.$array_menu['id_menu'].'" onclick="toggleMenuActivation(this)" class="'.$class.'">'.$array_menu['libelle_menu'].'</span>';
								// Affichage de ses enfants
								displayChildren($array_menu['id_menu'],$ProfileModel);
								echo '</li>';								
							}
							echo '</ul>';						
						?>
							<script type="text/javascript">
								//<![CDATA[
								new Axent.DragDropTree('menu_tree',
								{plusIcon : 'plus_alarme.gif',
								iconsFolder : '<?=NIVEAU_0?>images/icones/',
								minusIcon : 'moins_alarme.gif',
								addFolderIcon :true,
								folderIcon : 'application.png',
								afterDropNode: function(node,dropOnNode,point) {
								    src = node.identify();
								    dst = (node.up('li') != undefined) ? node.up('li').identify() : '';
								    prv = (node.previous('li') != undefined) ? node.previous('li').identify() : '';
									action = src+'|'+dst+'|'+prv;
									if($('menu_actions').value == '') $('menu_actions').value = action;
									else $('menu_actions').value += '|s|'+action;
							    }});
								//]]>
							</script>
							<input type="hidden" name="menu[activation]" id="menu_activated" value="<?php if(count($profileMenus) > 0) echo implode("|",$profileMenus); ?>" />						
							<input type="hidden" name="menu[actions]" id="menu_actions" value="" />
							<center><input type="submit" class="bouton" value="Save" /></center>
						<?php
						}
						?>
						</div>
					</td>
				</tr>
			</table>
		</form>
	</div>
</center>
</body>
</html>
