<?
/*
*	@cb41000@
*
*	04/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	05/12/2008 BBX : refonte totale du script pour le CB 4.1.0.00
*		=> Réécriture du formulaire
*		=> utilisation des modèles "ProfileModel", "MenuModel", "DatabaseConnection"
*		=> gestion des menus par drag'n drop
*	14/05/2009 - SPS : ajout d'un test sur le nom du menu (correction bug 9592)
*	25/08/2009 - MPR : Correction du bug 9592 - On fait le check sur l'envoi du formulaire et non pas sur le click du bouton
*	21/12/2009 - NSE : Appel en double à addMenutoProfile() : déjà effectué par $MenuModel->addMenu();
*	11/02/2011 - SPD : correction BZ 14637 - "Menu management" => "Menu Management"
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/MenuModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');

// Connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
// 11/02/2011 SPD: correction BZ 14637 - "Menu management" => "Menu Management"
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Menu Management'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// ***************************
// Enregistrement des données ?
// ***************************
if(isset($_POST['menu'])) 
{
	// Mise à jour des positions des menus
	if(trim($_POST['menu']['actions'] != '')) {
		$actionList = explode('|s|',$_POST['menu']['actions']);
		foreach($actionList as $actions) {
			list($src,$dst,$prv) = explode('|',$actions);
			// Découpage des valeurs afin de séparer le préfixe de l'id
			list($pref,$idMenuSrc) =  explode('_',$src);
			list($pref,$idMenuDst) =  explode('_',$dst);
			list($pref,$idMenuPrv) =  explode('_',$prv);
			// Instanciation des Menus
			$MenuModelSrc = new MenuModel($idMenuSrc,$idProfile);
			$MenuModelPrv = new MenuModel($idMenuPrv,$idProfile);
			// Explication : "MenuModelSrc" doit se retrouver sous "MenuModelPrv".
			// Il faut donc que la position de "MenuModelSrc" soit celle de "MenuModelPrv" + 1
			$MenuValuesPrv = $MenuModelPrv->getValues();
			$MenuModelSrc->setMenuPosition($MenuValuesPrv['position']+1);
		}
	}	
	// Doit-on ajouter / modifier un menu ?
	if(trim($_POST['menu']['name']) != '') {	
		// Détermination mise à jour ou création
		$new = ($_POST['menu']['id_menu'] == '');		
		// Création d'un menu
		if($new) {
			// On vérifie que le nom de menu ne soit pas déjà utilisé
			$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = '{$_POST['menu']['name']}'";
			if(count($database->getAll($query)) > 0) {
				// Le nom existe, on affiche une erreur
				$msg_erreur = '<div class="errorMsg">'.__T('A_MENU_MANAGEMENT_NAME_ALREADY_EXISTS').'</div>';
			}
			else {
				// Instanciation d'un menu model
				$MenuModel = new MenuModel(0);
				// Ajout des données menu
				$MenuModel->setValue('niveau','1');
				$MenuModel->setValue('id_menu_parent','0');
				$MenuModel->setValue('position',$MenuModel->getUserMenuLastPosition()+1);
				$MenuModel->setValue('libelle_menu',$_POST['menu']['name']);
				$MenuModel->setValue('largeur',strlen($_POST['menu']['name'])*10);
				$MenuModel->setValue('deploiement','0');
				$MenuModel->setValue('hauteur','20');
				$MenuModel->setValue('hauteur','20');
				$MenuModel->setValue('droit_affichage','customisateur');
				$MenuModel->setValue('droit_visible','0');
				$MenuModel->setValue('menu_client_default','0');
				$MenuModel->setValue('is_profile_ref_user','1');
				// Enregistrement du menu
				$idMenu = $MenuModel->addMenu();

				// 21/12/2009 NSE Appel en double : déjà effectué par $MenuModel->addMenu();
				/*
				// Propagation du menu dans tous les profils user
				foreach(ProfileModel::getProfiles() as $profil) {
					if($profil['profile_type'] == 'user') {
						$ProfileModel = new ProfileModel($profil['id_profile']);
						// Ajout des valeurs postées
						$ProfileModel->addMenuToProfile($idMenu);
						$ProfileModel->buildProfileToMenu();
					}
				}
				*/
			}
		}
		// Mise à jour d'un menu
		else {
			// Instanciation d'un menu model
			$MenuModel = new MenuModel($_POST['menu']['id_menu']);
			if(!$MenuModel->getError()) {
				// Mise à jour du nom
				$MenuModel->setValue('libelle_menu',$_POST['menu']['name']);
				// Sauvegarde des modifications
				$MenuModel->updateMenu();
			}
			else {
				// Une erreur est survenue
				$msg_erreur = '<div class="errorMsg">'.__T('A_GENERAL_ERROR_OCCURED').'</div>';
			}
		}
	}
}

// ***************************
// Suppression d'un menu ?
// ***************************
if(isset($_GET['action']) && ($_GET['action'] == 'delete')) {
	// Instanciation d'un menu model
	$MenuModel = new MenuModel($_GET['id_menu']);
	if(!$MenuModel->getError()) {
		// Suppression du menu
		$MenuModel->deleteMenu();
		// Redirection de sécurité pour éviter de renvoyer les données get
		header("Location: menu_management.php");
	}
}

// Récupération des menus user
$userMenus = MenuModel::getRootUserMenus();

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

?>
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
li span {
	cursor:pointer;
}
</style>
<script type="text/javascript" src="<?=NIVEAU_0?>js/cookie.js"></script>
<script type="text/javascript" src="<?=NIVEAU_0?>js/drag-drop-tree.js"></script>
<script type="text/javascript">
/* 14/05/2009 - SPS : ajout d'un test sur le nom du menu (correction bug 9592)*/
function checkMenu() {
	var StringExp = new RegExp(/^[\w\.\s\-]+$/);
	// 24/06/2013 GFS - Bug 32454 - [REC][IU 53001][TC#TA-62467][Menu Management] Order of the new menu is changed after another menu is deleted
	if (!empty($('menu_name').value) && !StringExp.test($('menu_name').value)) {
		alert('<?= __T('A_JS_MENU_MANAGEMENT_MENU_NAME_IS_NOT_VALID')?>');
		return false;
	}
}


/*************
* Cette fonction passe en mode édition de menu
*************/
function editMenu(id_menu,menu_name)
{
	$('menu_id_menu').value = id_menu;
	$('menu_name').value = menu_name;
}
/******
* Suppression d'un menu
******/
function deleteMenu(id_menu,nom_menu)
{
	if(confirm("<?=__T('A_MENU_MANAGEMENT_DELETE_CONFIRM')?>" + nom_menu + " ?")) {
		document.location.href = 'menu_management.php?action=delete&id_menu='+id_menu;
	}
}
</script>
<!-- Affichage du formulaire -->
<center>
	<img src="<?=NIVEAU_0?>images/titres/menu_management_titre.gif" border="0" />
	<br /><br />
	<div style="width:450px;">
<?php
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if(!isset($msg_erreur)) $msg_erreur = null;
?>
	<?=$msg_erreur?>
	<!-- maj 25/08/2009 MPR - Correction du bug 9592 - On fait le check sur l'envoi du formulaire et non pas sur le click du bouton -->
		<form action="menu_management.php" method="post" onsubmit="return checkMenu();">
			<table cellpadding="10" cellspacing="0" width="100%" class="tabPrincipal" border="0">
				<tr>
					<td align="center">
						<span class="texteGris"><?=__T('A_MENU_MANAGEMENT_MENU_NAME')?>*</span>
						<input type="text" size="30" id="menu_name" name="menu[name]" value="" />
						<br /><br />
						<div style="text-align:left;">
							<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" onclick="Effect.toggle('help_box_1', 'slide');" onmouseover="popalt('<?=__T('A_PROFILE_MANAGEMENT_HIDE_DISPLAY_HELP')?>')" />
							<div id="help_box_1" class="infoBox">
							<?=__T('A_MENU_MANAGEMENT_HELP')?>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<ul id="menu_tree">
						<?php
						foreach($userMenus as $idMenu) {
							$MenuModel = new MenuModel($idMenu);
							$MenuValues = $MenuModel->getValues();
						?>
						<li id="menu_<?=$idMenu?>">
							&nbsp;<span onclick="editMenu('<?=$idMenu?>','<?=$MenuValues['libelle_menu']?>')"><?=$MenuValues['libelle_menu']?></span>
							&nbsp;<img src="<?=NIVEAU_0?>images/icones/delete.png" style="cursor:pointer;" onclick="deleteMenu('<?=$idMenu?>','<?=$MenuValues['libelle_menu']?>')" onmouseover="popalt('<?=__T('A_MENU_MANAGEMENT_DELETE_TIP')?>')" />
						</li>
						<?php
						}
						?>
						</ul>
						<input type="hidden" id="menu_actions" name="menu[actions]" value="" />
						<input type="hidden" id="menu_id_menu" name="menu[id_menu]" value="" />
					</td>
				</tr>
				<tr>
					<td align="center">
						<?php /* 14/05/2009 - SPS : sur le clic, on verifie que le menu est correct */ ?>
						<input type="submit" class="bouton" value="Save" />
					</tr>
				</tr>
			</table>
		</form>
	</div>
</center>
<script type="text/javascript">
	//<![CDATA[
	new Axent.DragDropTree('menu_tree',
	{plusIcon : 'plus_alarme.gif',
	iconsFolder : '<?=NIVEAU_0?>images/icones/',
	minusIcon : 'moins_alarme.gif',
	addFolderIcon :true,
	folderIcon : 'application.png',
	dropAfterOverlap : 0,
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
</body>
</html>
