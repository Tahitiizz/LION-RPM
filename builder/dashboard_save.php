<?php
/**
*	@cb4100@
*	- Creation SLC	 04/11/2008
*
*	Cette page sauvegarde, ou crée, un dashboard
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_user & id_page & id_menu entre cote au niveau des inserts  [REFONTE CONTEXTE]
*	18/05/2009 GHX
*		- Sauvegarde de la homepage
*	19/05/2009 GHX
*		- Prise en compte de la sauvegarde de la homepage dans le cas on décoche la checkbox
*		- Modification de l'enregistrement des menus
*	25/08/2009 GHX
*		- Modifcation des paramètres passés à la fonction MenuModel::addMenu()
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');


/** Cette fonction genère le menu pour le dashboard $id_page
*
* @param string	$id_page : id du dashboard
* @param string $idMenu identifiant du menu s'il existe déjà (default 0)
* @return void	
*/
function generateMenu($id_page, $idMenuDash = 0) {
	global $db, $client_type;
	
	// on crée le menu
	if ( is_string($idMenuDash) && $idMenuDash != "" && $idMenuDash != "0" )
	{
		$myMenu = new MenuModel($idMenuDash);
	}
	else
	{
		$myMenu = new MenuModel(0);
	}
	$myMenu->setValue('libelle_menu',$_POST['page_name']);
	$myMenu->setValue('lien_menu','');
	// on va chercher les actions
	$getActions = $db->getall("select id from menu_contextuel where type_pauto='dashboard' order by ordre_menu");
	$actions = array();
	if ($getActions) {
		foreach ($getActions as $act)
			$actions[] = $act['id'];
		$myMenu->setValue('liste_action',implode('-',$actions));
	}
	$myMenu->setValue('largeur',150);
	$myMenu->setValue('hauteur',20);
	$myMenu->setValue('id_page',$id_page);
	// on specifie le droit_affichage
	if ($client_type == 'customisateur') {
		$myMenu->setValue('droit_affichage','customisateur');
	} else {
		$myMenu->setValue('droit_affichage','astellia');
	}			
	$myMenu->setValue('droit_visible',1);
	$myMenu->setValue('menu_client_default',0);
	$myMenu->setValue('is_profile_ref_user',1);
	$myMenu->setValue('id_menu_parent',$_POST['id_menu']);
	
	// 14:53 19/05/2009 GHX
	// Si le menu existe dékà un fait juste une mise à jour
	if ( is_string($idMenuDash) && $idMenuDash != "" && $idMenuDash != "0" )
	{
		// 11:42 25/08/2009 GHX
		$idMenu = $myMenu->addMenu($idMenuDash, true);
	}
	else
	{
		// 11:42 25/08/2009 GHX
		$idMenu = $myMenu->addMenu(null, true);
	}

	// on copie l'id du menu dans sys_definition_dashboard	
	$db->execute("UPDATE sys_definition_dashboard SET sdd_id_menu='$idMenu' WHERE sdd_id_page='$id_page'");

	// si le dashboard n'est pas bimode, on specifie son lien_menu
	if ($_POST['sdd_mode'] == 'overtime') {
		$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$id_page&mode=overtime&id_menu_encours=$idMenu");
		$myMenu->updateMenu();
	}
	if ($_POST['sdd_mode'] == 'overnetwork') {
		$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$id_page&mode=overnetwork&id_menu_encours=$idMenu");
		$myMenu->updateMenu();
	}

	// en bimode, il faut ajouter les sous-menu "Over Network Elements" / "Over Time"
	if ($_POST['sdd_mode'] == 'bimode') {
		$myMenu->setValue('id_menu_parent',$idMenu);
		
		// on ajoute le sous-menu 'Over Time'
		$myMenu->setValue('libelle_menu','Over Time');
		$id_menu = $myMenu->addMenu($myMenu->getValue('id_menu_parent').'.01');
		$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$id_page&mode=overtime&id_menu_encours=$id_menu");
		$myMenu->updateMenu();
		
		// on ajoute le sous-menu 'Over Network Elements'
		$myMenu->setValue('libelle_menu','Over Network Elements');
		$id_menu = $myMenu->addMenu($myMenu->getValue('id_menu_parent').'.02');
		$myMenu->setValue('lien_menu',"/dashboard_display/index.php?id_dash=$id_page&mode=overnetwork&id_menu_encours=$id_menu");
		$myMenu->updateMenu();
	}
}




// on recupère les données passées au script
$id_page	= $_POST['id_page'];
if ($id_page == '0') $id_page = '';

if ($id_page != '') {
	// UPDATE du Dashboard
	
	// on verifie qu'on a les droits d'écriture dessus
	$query = " --- on va chercher le dashboard
		SELECT * FROM sys_pauto_page_name WHERE id_page='$id_page'
	";
	$dash = $db->getrow($query);
	if (!allow_write($dash)) {
		echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_DASHBOARD');
		exit;
	}

	// on update le page name
	$query = " --- on update le page_name du dashboard
		update sys_pauto_page_name
		set page_name='{$_POST['page_name']}'
		where id_page='$id_page'
		";
	$db->execute($query);
	
	// on update les autres champs du dashboard
	$query = " --- on update le dashboard $id_page
		update sys_definition_dashboard
		set	sdd_sort_by_id			= '{$_POST['sdd_sort_by_id']}',
			sdd_sort_by_order		= '{$_POST['sdd_sort_by_order']}',
			sdd_mode				= '{$_POST['sdd_mode']}',
			sdd_is_online			= '".intval($_POST['sdd_is_online'])."',
			sdd_selecteur_default_period 			= '{$_POST['sdd_selecteur_default_period']}',
			sdd_selecteur_default_top_overnetwork	= '{$_POST['sdd_selecteur_default_top_overnetwork']}',
			sdd_selecteur_default_top_overtime	= '{$_POST['sdd_selecteur_default_top_overtime']}',
			sdd_selecteur_default_na				= '{$_POST['sdd_selecteur_default_na']}',
			sdd_selecteur_default_na_axe3		= '{$_POST['sdd_selecteur_default_na_axe3']}'
		where sdd_id_page = '$id_page'
		";
	$db->execute($query);
	
	// on supprime le menu s'il existait (ok, c'est bourrin de systématiquement supprimer le menu s'il faut le recréer après, mais la gestion des modifications de menu doublerait le nb de lignes de code)
	$myMenu_id = $db->getone("SELECT sdd_id_menu FROM sys_definition_dashboard WHERE sdd_id_page='$id_page'");
	if ($myMenu_id) {
		// on supprime le menu
		$myMenu = new MenuModel($myMenu_id);
		$myMenu->deleteMenu();
		// on supprime le menu dans sys_definition_dashboard
		// $db->execute("UPDATE sys_definition_dashboard SET sdd_id_menu=0 WHERE sdd_id_page='$id_page'");
	}

	// gestion du menu
	if ( intval($_POST['sdd_is_online']) )
		generateMenu($id_page, $myMenu_id);
}
else
{
	// INSERT nouveau Dashboard
	
	// on va chercher le prochain id_page
	// Nouveau format pour l'ID 
	// 14:23 02/02/2009 GHX
	// Appel à la fonction qui génére un unique ID
	$id_page = generateUniqId('sys_pauto_page_name');
	
	// ... dans sys_pauto_page_name
	if (($client_type == 'client') && ($user_info['profile_type'] == 'user')) {
		$query = " --- ajoute le dashboard dans sppn
			insert into sys_pauto_page_name (id_page,page_name,droit,page_type,id_user,share_it)
			values ('$id_page','{$_POST['page_name']}','$client_type','page','{$user_info['id_user']}',0)";
	} else {
		$query = " --- ajoute le dashboard dans sppn
			insert into sys_pauto_page_name (id_page,page_name,droit,page_type,share_it)
			values ('$id_page','{$_POST['page_name']}','$client_type','page',0)";
	}
	$db->execute($query);
	
	// ... dans sys_definition_dashboard
	
	// s'il y a un espace dans sdd_sort_by_id, c'est qu'on a récupéré 'You need to add a graph to your dashboard' comme valeur
	// donc on la met à ''
	if (strpos($_POST['sdd_sort_by_id'],' '))		$_POST['sdd_sort_by_id'] = '';
	
	$query = " --- ajoute le dashboard dans sys_definition_dashboard
		insert into sys_definition_dashboard
		(sdd_id_page,sdd_sort_by_id,sdd_sort_by_order,sdd_mode,sdd_is_online,
		 sdd_selecteur_default_period,sdd_selecteur_default_top_overnetwork,sdd_selecteur_default_top_overtime,sdd_selecteur_default_na,sdd_selecteur_default_na_axe3)
		values
			('$id_page',
			'{$_POST['sdd_sort_by_id']}',
			'{$_POST['sdd_sort_by_order']}',
			'{$_POST['sdd_mode']}',
			'".intval($_POST['sdd_is_online'])."',
			'{$_POST['sdd_selecteur_default_period']}',
			'{$_POST['sdd_selecteur_default_top_overnetwork']}',
			'{$_POST['sdd_selecteur_default_top_overtime']}',
			'{$_POST['sdd_selecteur_default_na']}',
			'{$_POST['sdd_selecteur_default_na_axe3']}'
			)
		";
	$db->execute($query);
	
	// gestion du menu
	if (  intval($_POST['sdd_is_online']) )
		generateMenu($id_page);
}

// 17:32 18/05/2009 GHX
// Si on a défini le dashboard comme homepage par défaut on sauvegarde dans la table sys_global_parameters
if ( isset($_POST['sdd_is_homepage']) )
{
	$db->execute("UPDATE sys_global_parameters SET value = '{$id_page}' WHERE parameters = 'id_homepage'");
	$mode_homepage = $_POST['homepage_default_mode'];
	$db->execute("UPDATE sys_global_parameters SET value = '{$mode_homepage}' WHERE parameters = 'mode_homepage'");
}
else
{
	// 11:18 19/05/2009 GHX
	// Dans le cas où on ne veut plus que le dashboard soit la homepage par défaut
	$db->execute("UPDATE sys_global_parameters SET value = CASE WHEN value = '{$id_page}' THEN NULL ELSE value END WHERE parameters = 'id_homepage'");
	$idHomepage = $db->getOne("SELECT value FROM sys_global_parameters WHERE  parameters = 'id_homepage'");
	if ( empty($idHomepage) )
	{
		$db->execute("UPDATE sys_global_parameters SET value = NULL WHERE parameters = 'mode_homepage'");	
	}
}

// on renvoie vers la page d'edition du GTM
header("Location: {$niveau0}builder/dashboard.php?id_page=$id_page");
exit;

// debug
echo "<link rel='stylesheet' href='../css/global_interface.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/dashboard.php?id_page=$id_page'>{$niveau0}builder/dashboard.php?id_page=$id_page</a>";

?>
