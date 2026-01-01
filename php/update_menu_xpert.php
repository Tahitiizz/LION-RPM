<?php
/*
	29/09/2009 GHX
		- Correction du BZ 11733 [QUA][5.0.0.9] Réapplication de contexte fait disparaitre le menu Xpert
 *  18/01/2011 NSE bz 16301 : patch produit fait disparaitre menu Xpert pour astellia_admin --> r�cup�ration de tous les profiles, y compris astellia_admin
*/
?>
<?php
/*
 * Ce script permet d'ajouter automatiquement les menus Xpert quand le répertoire xpert est présent
 *
 * @author GHX
 * @version CB 5.0.0.07
 * @since CB 5.0.0.07
 */
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_menu.php");

$label_menu_admin_xpert = 'Setup Xpert';
$url_menu_admin_xpert   = '/xpert/index.php?action=configuration';
$label_menu_user_xpert  = 'Xpert';
$url_menu_user_xpert    = '/xpert/index.php';

/*
	Ajout des menus Xpert uniquement si le répertoire xpert existe et si les menus ne sont pas déjà présent
*/
if ( file_exists(REP_PHYSIQUE_NIVEAU_0.'xpert') )
{
	$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = '{$label_menu_admin_xpert}'";
	$res = pg_query($database_connection, $query);
	
	if ( pg_num_rows($res) == 0 )
	{
		// AJOUT DU MENU XPERT EN ADMIN
		$menu = array();
		$menu["libelle_menu"] = $label_menu_admin_xpert;	// Label du menu
		$menu["droit_affichage"] = "astellia";	// laisser astellia
		$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='SETUP')"; // identifiant du menu parent (normalement, ne pas modifier)
		$menu["lien_menu"] = $url_menu_admin_xpert; // mettre ici le chemin vers le fichier Xpert (vous pouvez mettre des param dans l'URL)
		$menu["is_profile_ref_admin"]	= 1; // Ne pas changer
		$menu["position"]	= 10; // Ne pas changer
		
		$menu["droit_visible"]	= 0;
		$menu["menu_client_default"]	= 0;
		
		addMenu ($menu, $database_connection);	// Ajout du menu

		// AJOUT DU MENU XPERT EN USER
		$menu = array();
		$menu["libelle_menu"] = $label_menu_user_xpert;
		$menu["droit_affichage"] = "astellia";
		$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='INVESTIGATION')";
		$menu["lien_menu"] = $url_menu_user_xpert;
		$menu["is_profile_ref_user"] = 1;
		// 25/09/2009 BBX : modification de la construction du menu user pour ne pas être supprimé si mount contexte. BZ 11733
		//$menu["is_menu_defaut"] = 0;
		$menu["position"]	= 5;
		$menu["droit_visible"]	= 0;
		$menu["menu_client_default"]	= 0;
		addMenu ($menu, $database_connection);
	}
	else
	{
		//14:39 29/09/2009 GHX
		// Correction du BZ 11733
		// Récupère l'ID du menu Xpert Admin
		list($admin) = pg_fetch_row($res);
		
		// Récupère l'ID du menu Xpert User
		$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = '{$label_menu_user_xpert}'";
		$res = pg_query($database_connection, $query);
		list($user) = pg_fetch_row($res);
		// 18/01/2011 NSE bz 16301 : r�cup�ration de tous les profiles, y compris astellia_admin
		foreach ( ProfileModel::getAllProfiles() as $profil )
		{
			$ProfileModel = new ProfileModel($profil['id_profile']);

			if ( !$ProfileModel->isMenuInProfile($$profil['profile_type']) )
			{
				$ProfileModel->addMenuToProfile($$profil['profile_type']);
				$ProfileModel->buildProfileToMenu();
			}
		}
	}
}
?>