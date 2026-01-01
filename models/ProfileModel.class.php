<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04 pour la gestion de Astellia Administrator
 * 27/04/2012 NSE bz 27026 : Création de la méthode getIdFromPaaGuid()
 */
?><?php
/*
	19/01/2009 GHX
		- ajout de fonction addMenuListToProfile
	20/01/2009 GHX
		ajout du paramètre database au constructeur pour choisir sur quelle base se connecter
	29/01/2009 GHX
		- modification de la création du id_profile. Ce n'est plus un MAX+1 mais un unique ID  [REFONTE CONTEXTE]
		- modification des requêtes SQL pour mettre id_profile entre cote  [REFONTE CONTEXTE]
	30/01/2009 GHX
		- modification des requetes SQL pour mettre les valeurs entre cote [REFONTE CONTEXTE)
	02/02/2009 GHX
		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
	07/07/2009 MPR
		- Correction du bug 9704
	16/07/2009 GHX
		- Correction du BZ 10618 [REC][T&A CB 5.0][Menu Management]: repositionnement des menus NOK dans profil user
	19/08/2009 GHX
		- Lors du déploiement des profils il faut aussi déployer la table profile_menu_position
	21/12/2009  NSE
		- bz 11235 modification du calcule de la position en utilisant les restrictions sur droit_affichage et is_profile_ref_user de menu_deroulant_intranet
	26/02/2010 MPR 
		- Correction du BZ 14525 - L'activation des menus admin pour un profile admin ne fonctionne pas 
	27/04/2010 NSE 
		- Ajout de la méthode "getProfileType"
        04/08/2010 MPR
                - Correction du BZ 15045 - Impossible de modifier un KPI astellia en astellia_admin
                                           On retourne soit customisateur soit client
 *      17/12/2010 NSE bz 19745 : on s'assure que les 2 champs provenant de MenuModel ne sont pas vides
 *      20/12/2010 MMT bz 19745 : suppression correction dans addMenuListToProfile précédente car génèrait une regression sur 19745: les menus utilisateurs disparaissent apres mise en multi produit
 *      18/01/2011 NSE bz 16301 : ajout de la méthode getAllProfiles pour récupérer tous les profiles, y compris astellia_admin
*/
?>
<?php
/**
*	Classe permettant de manipuler les profiles
*	Travaille sur la table profile,profile_menu_position, users
*
*	@author	BBX - 28/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*/
class ProfileModel
{
	/**
	* Propriétés
	*/
	private $idProfile = 0;	
	private static $database = null;
	private static $databaseParam = "";
	private $profileValues = Array();
	private $error = false;
	private $mandatory = Array('Over Time','Over Network Elements');

	/************************************************************************
	* Constructeur
	* @param : int	id profile
	* @param : int	$database prend les mêmes paramètre que DatabaseConnection
	************************************************************************/
	public function __construct($idProfile, $database = "")
	{
		// Sauvegarde de l'id user
		$this->idProfile = $idProfile;
                
		// Connexion à la base de données
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
			self::$database = Database::getConnection($database);
			self::$databaseParam = $database;
		
		// Récupération des valeurs du user
		// 29/01/2009 GHX
		// modification de la condition a cause du nouveau format de l'id
		if(is_numeric($idProfile) || is_string($idProfile) ) {
			$query = "SELECT * FROM profile WHERE id_profile = '{$idProfile}'";
			$array = self::$database->getRow($query);
			// Si les infos user ne sont pas récupérées, on renvoie une erreur
			if(count($array) == 0) {
				$this->error = true;
			}
			else {
				$this->profileValues = $array;
			}
		}
		else {
			// Si le format de l'id est incorrect, on renvoie une erreur
			$this->error = true;
		}
	}
        
        /**
         * Initalise les menus sur un profil
         * 09/09/2011 BBX BZ 16148
         */
        public function initMenus()
        {
            $condition = ($this->profileValues['profile_type'] == 'user') ? "is_profile_ref_user = 1" : "is_profile_ref_admin = 1";
            self::$database->execute("DELETE FROM profile_menu_position WHERE id_profile = '{$this->idProfile}'");
            $query = "INSERT INTO profile_menu_position
                SELECT id_menu, '{$this->idProfile}', position, id_menu_parent 
                FROM menu_deroulant_intranet
                WHERE {$condition}";
            self::$database->execute($query);
        }
	
	/************************************************************************
	* Méthode getValues : retourne un tableau associatif contenant les paramètres du profile
	* @return : array	Tableau associatif 
	************************************************************************/
	public function getValues()
	{
		return $this->profileValues;
	}
	
	/************************************************************************
	* Méthode setValue : ajoute une valeur à l'objet
	* @return : void()
	************************************************************************/
	public function setValue($key,$value)
	{
		$this->profileValues[$key] = $value;
	}

	/************************************************************************
	* Méthode getError : retourne le code d'erreur du profile
	* @return : true = pas d'erreur, false = objet inutilisable
	************************************************************************/
	public function getError() 
	{
		return $this->error;
	}
	
	/************************************************************************
	* Méthode isMenuInProfile : indique si le menu passé en paramètre fait parti du profil
	* @return : bool
	************************************************************************/
	public function isMenuInProfile($idMenu)
	{
		$query = "SELECT id_menu FROM profile_menu_position
		WHERE id_menu = '{$idMenu}' AND id_profile = '{$this->idProfile}'";
                self::$database->execute($query);
		return (self::$database->getNumRows() > 0);
	}
	
	/************************************************************************
	* Méthode getMenus : retourne les ids menu du profile
	* @return : array	tableau des ids menu
	************************************************************************/
	public function getMenus()
	{
		$arrayRetour = Array();
		$query = "SELECT DISTINCT id_menu FROM profile_menu_position WHERE id_profile = '{$this->idProfile}'";
		$result = self::$database->execute($query);
		while($menu = self::$database->getQueryResults($result,1)) {
		    $arrayRetour[] = $menu['id_menu'];
		}
		return $arrayRetour;
	}
	
	/************************************************************************
	* Méthode addMenuToProfile : ajoute un menu au profil
	* @param int	id du menu à ajouter
	* @return : void()
	************************************************************************/
	public function addMenuToProfile($idMenu)
	{
		// Instanciation d'un MenuModel
		$MenuModel = new MenuModel($idMenu);
		$MenuValues = $MenuModel->getValues();
		// Ajout dans  profile_menu_position
		// 17:53 16/07/2009 GHX
		// Correction du BZ 10618 [REC][T&A CB 5.0][Menu Management]: repositionnement des menus NOK dans profil user
		// On modifie la facon de remplir le champ position
		// 15:39 21/12/2009 NSE
		// BZ 11235 modification de façon à utiliser les restrictions sur droit_affichage et is_profile_ref_user de menu_deroulant_intranet
		//NSE récupération de la position
		
		// maj 26/02/2010 - MPR : Correction du BZ 14525 - L'activation des menus admin pour un profile admin ne fonctionne pas 
		$_condition_profile = ( $this->profileValues['profile_type'] == 'admin' ) ? " AND is_profile_ref_admin = '{$MenuValues['is_profile_ref_admin']}'": " AND is_profile_ref_user = '{$MenuValues['is_profile_ref_user']}'";
		
		$query = "SELECT 
					CASE WHEN MAX(pmp.position) IS NULL 
					THEN 1 
					ELSE MAX(pmp.position)+1 END 
				FROM 
					profile_menu_position pmp, menu_deroulant_intranet mdi 
				WHERE 
					pmp.id_menu_parent= '{$MenuValues['id_menu_parent']}' 
					AND id_profile= '{$this->idProfile}'
					AND pmp.id_menu=mdi.id_menu 
					AND droit_affichage='{$MenuValues['droit_affichage']}' 
					{$_condition_profile}";
		$position = self::$database->getOne($query);
		
		$query = "INSERT INTO profile_menu_position (id_menu,id_profile,position,id_menu_parent)
		VALUES ('{$idMenu}','{$this->idProfile}', '{$position}','{$MenuValues['id_menu_parent']}')";
		self::$database->execute($query);
		//$this->setMenuPosition($position[0],$idMenu);
		// on décale les menus
		$query = "UPDATE profile_menu_position SET position = position+1 WHERE position >= {$position}
		AND id_profile = '{$this->idProfile}' AND id_menu!='{$idMenu}'";
		self::$database->execute($query);
		// 15:39 21/12/2009 NSE fin
                
                // 08/11/2011 BBX
                // BZ 22157 : on vacuum les tables sinon les perfs se dégradent ici
                self::$database->execute("VACUUM ANALYZE menu_deroulant_intranet");
                self::$database->execute("VACUUM ANALYZE profile_menu_position");
	}
	
	/************************************************************************
	* Méthode addMenuListToProfile : ajoute une liste de menu au profil
	* @param array	tableau des id menu à ajouter
	* @return : void()
	************************************************************************/
	public function addMenuListToProfile($listIdMenu)
	{
		// 14:59 19/08/2009 GHX
		// Ajout de l'identifiant du produit dans la fonction
		$MenuValues = MenuModel::getValuesList($listIdMenu, self::$databaseParam);
		$copy = array();
		foreach ( $listIdMenu as $idMenu )
		{
		  // 20/12/2010 MMT bz 19745 : suppression correction car génèrait une regression sur 19745: les menus utilisateurs disparaissent apres mise en multi produit
			$copy[] = sprintf("%s\t%s\t%s\t%s", $idMenu, $this->idProfile, $MenuValues[$idMenu]['position'], $MenuValues[$idMenu]['id_menu_parent']);
		}
		self::$database->setTable('profile_menu_position', $copy);
	}
	
	/************************************************************************
	* Méthode removeMenuFromProfile : désactive un menu du profil
	* @param int	id du menu à retirer
	* @return : void()
	************************************************************************/
	public function removeMenuFromProfile($idMenu)
	{
		// Suppression dans  profile_menu_position
		$query = "DELETE FROM profile_menu_position
		WHERE id_menu = '{$idMenu}'
		AND id_profile = '{$this->idProfile}'";
		self::$database->execute($query);
	}
	
	/************************************************************************
	* Méthode removeProfileMenusFromArray : désactive une liste de menus du profil
	* @param array	tableau listant les id menus à désactiver
	* @return : void()
	************************************************************************/
	public function removeProfileMenusFromArray($arrayMenus)
	{
		if(count($arrayMenus) > 0) {
			// Suppression dans  profile_menu_position
			$query = "DELETE FROM profile_menu_position
			WHERE id_menu NOT IN ('".implode("','",$arrayMenus)."')
			AND id_menu NOT IN 
			(SELECT id_menu FROM menu_deroulant_intranet 
			WHERE libelle_menu IN ('".implode("','",$this->mandatory)."'))
			AND id_profile = '{$this->idProfile}'";
			self::$database->execute($query);
		}
	}
	
	/************************************************************************
	* Méthode buildProfileToMenu : créé la chaine profile_to_menu et l'ajoute à la table profile
	* @return : void()
	************************************************************************/
	public function buildProfileToMenu()
	{
		// Récupération des ids menu
		$query = "SELECT DISTINCT id_menu FROM profile_menu_position
		WHERE id_profile = '{$this->idProfile}'
		ORDER BY id_menu";
		$arrayIds = Array();
		$result = self::$database->execute($query);
		while($menu = self::$database->getQueryResults($result,1)) {
		    $arrayIds[] = $menu['id_menu'];
		}
		// Inserttion dans profile
		$query = "UPDATE profile SET profile_to_menu = '".implode('-',$arrayIds)."' WHERE id_profile = '{$this->idProfile}'";
		self::$database->execute($query);
	}
	
	/************************************************************************
	* Méthode deleteProfileMenus : supprime les menus du profil
	* @return : void()
	************************************************************************/
	public function deleteProfileMenus()
	{
		// Suppression dans profile_menu_position
		$query = "DELETE FROM profile_menu_position WHERE id_profile = '{$this->idProfile}'";
		self::$database->execute($query);
		// Suppression dans profile
		$query = "UPDATE profile SET profile_to_menu = NULL WHERE id_profile = '{$this->idProfile}'";
		self::$database->execute($query);
	}
	
	/************************************************************************
	* Méthode deleteProfile : supprime le profil
	* @return : void()
	************************************************************************/
	public function deleteProfile()
	{
            // 23/02/2012 NSE DE Astellia Portal Lot2
            $PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
            // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
            $PAAAuthentication->deleteRight(APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.'.$this->idProfile);
		// 26/08/2013 MGO bz 34649 : reset cas cache on deleteProfile
		if (PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS){
			   $PAAAuthentication->resetCasCacheUserRights($_SESSION['login'],APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME);
		}
		// Suppression de la table profile
		$query = "DELETE FROM profile WHERE id_profile = '{$this->idProfile}'";
		self::$database->execute($query);

		self::deployProfile();
		// Suppression des menus
		$this->deleteProfileMenus();
	}
	
	/************************************************************************
	* Méthode setMenuPosition : change la position d'un menu
	* @param int	position
	* @param int	id du menu à bouger
	* @return : void()
	************************************************************************/
	public function setMenuPosition($p,$idMenu)
	{	
		// On décale toutes les autres positions
		$query = "UPDATE profile_menu_position SET position = position+1 WHERE position >= {$p}
		AND id_profile = '{$this->idProfile}'";
		self::$database->execute($query);
		// On regarde si ce menu est déjà activé sur le profil
		if(!$this->isMenuInProfile($idMenu)) {
			// S'il n'est pas activé, on l'active
			$this->addMenuToProfile($idMenu);
		}
		// Modification de la position
		$query = "UPDATE profile_menu_position SET position = {$p} 
		WHERE id_menu = '{$idMenu}' AND id_profile = '{$this->idProfile}'";
		self::$database->execute($query);
	}
	
	/************************************************************************
	* Méthode getRootAdminMenus : retourne les menus Admin de niveau 0
	* avec la configuration du profile
	* @return : array	tableau des menus
	************************************************************************/
	public function getRootAdminMenus()
	{
                // 07/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$query = "SELECT DISTINCT m.id_menu,
		(CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) AS id_menu_parent,
		m.libelle_menu,m.lien_menu,
		(CASE WHEN p.id_profile IS NULL AND '{$this->idProfile}' <> '0' THEN 0 ELSE 1 END) AS on_off,
		(CASE WHEN p.position IS NULL THEN m.position ELSE p.position END) AS position
		FROM menu_deroulant_intranet m LEFT JOIN profile_menu_position p
		ON (m.id_menu = p.id_menu AND p.id_profile = '{$this->idProfile}')
		WHERE m.is_profile_ref_admin = 1
		AND (CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) = '0'
		ORDER BY position";
		return self::$database->getAll($query);
	}
	
	/************************************************************************
	* Méthode getMenusAdminEnfant : retourne les menus Enfants d'un menu
	* selon la configuration du profile
	* @param int	id du menu
	* @return : array	tableau des menus
	************************************************************************/
	public function getMenusAdminEnfant($idMenu)
	{
		$query = "SELECT DISTINCT m.id_menu,
		(CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) AS id_menu_parent,
		m.libelle_menu,m.lien_menu,
		(CASE WHEN p.id_profile IS NULL AND '{$this->idProfile}' <> '0' THEN 0 ELSE 1 END) AS on_off,
		(CASE WHEN p.position IS NULL THEN m.position ELSE p.position END) AS position
		FROM menu_deroulant_intranet m LEFT JOIN profile_menu_position p
		ON (m.id_menu = p.id_menu AND p.id_profile = '{$this->idProfile}')
		WHERE m.is_profile_ref_admin = 1
		AND (CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) = '{$idMenu}'
		ORDER BY position";
		return self::$database->getAll($query);
	}
	
	/************************************************************************
	* Méthode getRootUserMenus : retourne les menus User de niveau 0
	* avec la configuration du profile
	* @return : array	tableau des menus
	************************************************************************/
	public function getRootUserMenus()
	{
                // 07/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$query = "SELECT DISTINCT m.id_menu,
		(CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) AS id_menu_parent,
		m.libelle_menu,m.lien_menu,
		(CASE WHEN p.id_profile IS NULL AND '{$this->idProfile}' <> '0' THEN 0 ELSE 1 END) AS on_off,
		(CASE WHEN p.position IS NULL THEN m.position ELSE p.position END) AS position
		FROM menu_deroulant_intranet m LEFT JOIN profile_menu_position p
		ON (m.id_menu = p.id_menu AND p.id_profile = '{$this->idProfile}')
		WHERE m.is_profile_ref_user = 1
		AND (CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) = '0'
		ORDER BY position";
		return self::$database->getAll($query);
	}
	
	/************************************************************************
	* Méthode getMenusUserEnfant : retourne les menus Enfants d'un menu
	* selon la configuration du profile
	* @param int	id du menu
	* @return : array	tableau des menus
	************************************************************************/
	public function getMenusUserEnfant($idMenu)
	{
		$query = "SELECT DISTINCT m.id_menu,
		(CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) AS id_menu_parent,
		m.libelle_menu,m.lien_menu,
		(CASE WHEN p.id_profile IS NULL AND '{$this->idProfile}' <> '0' THEN 0 ELSE 1 END) AS on_off,
		(CASE WHEN p.position IS NULL THEN m.position ELSE p.position END) AS position
		FROM menu_deroulant_intranet m LEFT JOIN profile_menu_position p
		ON (m.id_menu = p.id_menu AND p.id_profile = '{$this->idProfile}')
		WHERE m.is_profile_ref_user = 1
		AND (CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) = '{$idMenu}'
		AND m.libelle_menu NOT IN ('Over Time','Over Network Elements')
		ORDER BY position";
		return self::$database->getAll($query);
	}
	
	/************************************************************************
	* Méthode setNewParent : modifie le parent d'un menu dans le cofiguration d'un profil
	* @param int	id du menu
	* @param int	id du parent
	* @return : void()
	************************************************************************/
	public function setNewParent($idMenu,$idMenuParent)
	{
		$query = "UPDATE profile_menu_position SET id_menu_parent = '{$idMenuParent}'
		WHERE id_menu = '{$idMenu}'
		AND id_profile = '{$this->idProfile}'";
		self::$database->execute($query);
	}
	
	/**
	* récupère la liste des users liés au profil
	* @return array
	*/
	public function getUsers()
	{
            // 23/02/2012 NSE DE Astellia Portal Lot2
            $PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
            // on récupère les utilisateurs sur le Portail
            // pour ne récupérer que la fin du guid : substr($this->idProfile, strrpos($this->idProfile, '.')+1)
            // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
            $rights[] = APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.'.$this->idProfile;
            // flag = false pour ne pas récupérer les super admin/users pour pouvoir supprimer les profiles
            $PAAusers = $PAAAuthentication->getUsersWithRights($rights,APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME,false);
            $arrayIds = Array();
            if(!empty ($PAAusers)){
		$query = "SELECT id_user, username FROM users WHERE login IN ('".  implode("', '", $PAAusers)."')";
		$arrayIds = self::$database->getAll($query);
            }
            return $arrayIds;
	}
	
	// maj 07/07/2009 - MPR : Correction du bug 9704 
	//		- Les users sauvegardés pour les alarmes systèmes ne sont pas affichés (uniquement sur produit slave)
	// 		- Pas de correspondance entre les profiles du master et des slaves
	
	/************************************************************************
	* Méthode deployUsers : Copie la table des utilisateurs sur tous les produits
	* @return : void()
	************************************************************************/	
	public static function deployProfile()
	{
		// Connexion à la base de données
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
        // 31/07/2014 GFS Bug 42536 - [SUP][T&A CB][#46480][OrangeCI] Wrong synchronization on sys_users and sys_users_groups tables.
		$database = Database::getConnection(ProductModel::getIdMaster());
		// Récupération de la table Users
		$profile_table = $database->getTable('profile');
		// On boucle sur tous les produits (sauf le maître)
		$query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 0 AND sdp_on_off = 1";
		foreach($database->getAll($query) as $array_prod) {
			// Connexion au produit
                        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
			$db_temp = Database::getConnection($array_prod['sdp_id']);
			// On vide la table Users
			$query_truncate = "TRUNCATE TABLE profile";
			$db_temp->execute($query_truncate);
			// On restaure les informations du maître
			$db_temp->setTable('profile',$profile_table);
		}
		
		// 11:52 19/08/2009 GHX
		// Il faut aussi déployer la table profile_menu_position
		$profile_table = $database->getTable('profile_menu_position');
		// On boucle sur tous les produits (sauf le maître)
		$query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 0 AND sdp_on_off = 1";
		foreach($database->getAll($query) as $array_prod) {
			// Connexion au produit
                        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
			$db_temp = Database::getConnection($array_prod['sdp_id']);
			// On vide la table Users
			$query_truncate = "TRUNCATE TABLE profile_menu_position";
			$db_temp->execute($query_truncate);
			// On restaure les informations du maître
			$db_temp->setTable('profile_menu_position',$profile_table);
		}
	}
	
	/************************************************************************
	* Méthode addProfile : ajoute un profil dans la table profile
	* @return : void()
	************************************************************************/
	public function addProfile()
	{	
		// 26/08/2013 MGO bz 34649 : reset cas cache on addProfile
		$PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
		if (PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS){
			$PAAAuthentication->resetCasCacheUserRights($_SESSION['login'],APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME);
		}
		// Détermination du nouvel id profile
		// 29/01/2009 GHX
		// Dans la refonte du contexte, l'identifiant d'un profil est unique. On ne fait plus MAX+1
		// 14:23 02/02/2009 GHX
		// Appel à la fonction qui génére un unique ID
		$newIdProfile = generateUniqId('profile');
		// Insertion du nouveau profile
		$query = "INSERT INTO profile (id_profile,profile_name,profile_to_menu,profile_type,client_type)
		VALUES ('{$newIdProfile}','{$this->profileValues['profile_name']}',NULL,'{$this->profileValues['profile_type']}',NULL)";
		self::$database->execute($query);
		// On place le nouvel id dans l'objet
		$this->idProfile = $newIdProfile;
		$this->profileValues['id_profile'] = $newIdProfile;
		
		self::deployProfile();
		// Le profil existe
		$this->error = false;
		// Retour de l'id
		return $newIdProfile;
	}
	
	/************************************************************************
	* Méthode updateProfile : met à jour le profil en base
	* @return : void()
	************************************************************************/
	public function updateProfile()
	{
		// Parcours des valeurs
		foreach($this->profileValues as $key => $value) {
			// Mise à jour de l'information
			$value = ($value == '') ? "NULL" : ((is_numeric($value)) ? $value : "'{$value}'");
			$query = "UPDATE profile SET {$key} = {$value} WHERE id_profile = '{$this->idProfile}'";
			self::$database->execute($query);
		}
		self::deployProfile();
	}
	
	/************************************************************************
	* Méthode checkMandatoryMenus : insère au profil les menus obligatoires dans le profil
	* @return : void()
	************************************************************************/
	public function checkMandatoryMenus()
	{
		// Pour le moment, les seuls menus obligatoires sont les menu user overtime et overnetwork
		if($this->profileValues['profile_type'] == 'user') {
			$query = "INSERT INTO profile_menu_position
			(SELECT DISTINCT id_menu, '{$this->idProfile}' AS id_profile, position, id_menu_parent
			FROM menu_deroulant_intranet
			WHERE libelle_menu IN ('".implode("','",$this->mandatory)."')
			AND id_menu NOT IN (SELECT id_menu FROM profile_menu_position WHERE id_profile = '{$this->idProfile}'))";
			self::$database->execute($query);
		}
	}
	
	/**
	 * Vérifie l'intégré des menus du profil et si des id_menu ne sont plus valide ont les supprimes du profil. Retourne TRUE si tout est bon sinon FALSE si des menus ont été
	 * supprimé du profil
	 *
	 *	18/03/2009 GHX
	 *		Ajout de la fonction
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return boolean
	 */
	public function checkIntegrityMenus ()
	{
		$query = "
			SELECT 
				id_menu,
				id_menu_parent
			FROM 
				profile_menu_position
			WHERE 
				id_profile = '{$this->idProfile}'
			";
		
		$result = self::$database->execute($query);
		
		// Un profil ne peut pas être vide
		if ( self::$database->getNumRows() == 0 )
			return false;
	
		$listIdMenu = array();
		$listIdMenuParent = array();
	
		while ( $row = self::$database->getQueryResults($result, 1) )
		{
			$listIdMenu[] = $row['id_menu'];
			$listIdMenuParent[$row['id_menu']] = $row['id_menu_parent'];
		}
	
		// Regarde si la liste des id_menu sont valides
		$idMenu = MenuModel::isMenuExists($listIdMenu, self::$databaseParam);
		// Regarde si la liste des id_menu_parent sont valides
		$idMenuParent = MenuModel::isMenuExists($listIdMenuParent, self::$databaseParam);
		
		$isOk = true;
		// Si c'est un tableau c'est que des id_menu ne sont plus valide
		// on les supprime alors du profile
		if ( is_array($idMenu) )
		{
			foreach ( $idMenu as $id )
			{
				$this->removeMenuFromProfile($id);
			}
			$isOk = false;
		}
		// Si c'est un tableau c'est que des id_menu_parent ne sont plus valide
		// on les supprime les id_menu qui pour id_menu_parent ceux qui ne sont plus valide
		if ( is_array($idMenuParent) )
		{
			foreach ( $listIdMenuParent as $id => $idParent )
			{
				if ( in_array($idParent, $idMenuParent) )
				{
					$this->removeMenuFromProfile($id);
				}
			}
			$isOk = false;
		}
		
		if ( !$isOk )
		{
			$this->buildProfileToMenu();
		}
		
		return $isOk;
	} // End function checkIntegrityMenus
	
	
	/************************************************************************
	* Méthode getProfileType : retroune le profile_type du profile
	* @return : string (customisateur, admin, user)
	************************************************************************/
	public function getProfileType()
	{
		$query = "SELECT profile_type, client_type FROM profile WHERE id_profile = '{$this->idProfile}'";
        // 23/07/2010 OJT : Correction bz 16742
        // 04/08/2010 MPR : Correction bz 15045
		$profile =  self::$database->getRow($query);
                // 04/08/2010 - Correction du BZ 15045 - Impossible de modifier un KPI astellia en astellia_admin
                // On retourne soit customisateur soit client
		if($profile['profile_type'] == 'admin' && $profile['client_type'] == 'protected')
			return 'customisateur';
		else
			return $profile['profile_type'];
	}


	/************************* STATIC FUNCTIONS ***************************/
	
	/************************************************************************
	* Méthode getProfiles : retourne les profiles existants
	* @return : array	tableau des profiles
	************************************************************************/
	public static function getProfiles()
	{	
                // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection();
		$query = "SELECT * FROM profile WHERE client_type IS NULL ORDER BY id_profile";
		return $database->getAll($query);
	}

        // 18/01/2011 NSE bz 16301 : récupération de tous les profiles, y compris astellia_admin
	/************************************************************************
	* Méthode getProfiles : retourne tous les profiles existants (y compris le protected : astellia_admin)
	* @return : array	tableau des profiles
	************************************************************************/
	public static function getAllProfiles()
	{
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$database = Database::getConnection();
		$query = "SELECT * FROM profile ORDER BY id_profile";
		return $database->getAll($query);
        }
        
        /**
         * Retourne la liste des id des profiles de type admin (optionnel : dont le guid est contenu dans la liste)
         * @param boolean $astellia_admin indique s'il faut retrourner également l'utilisateur 'astellia_admin'
         * @param array $guids liste optionnelle de guids
         * @return array 
         * 23/02/2012 NSE DE Astellia Portal Lot2
         */
        public static function getAdminProfiles($astellia_admin=false, $guids=array())
	{	
            $database = Database::getConnection();
            $query = "SELECT id_profile FROM profile WHERE profile_type='admin'";
            
            if(!empty($guids)){
                $guids = str_replace(APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.', '', $guids);
                $guidin = array();
                foreach ($guids as $guid) {
                    $guidin[] = "'$guid'";
                }
                $query .= " AND id_profile IN (".implode(', ',$guidin).")";
            }
            if(!$astellia_admin)
                $query .= " AND (client_type ISNULL OR client_type <> 'protected')";
            return $database->getAll($query);
	}
        
        /**
         * Retourne l'identifiant du profile AstelliaAdministrator
         * @return string identifiant du profile AstelliaAdministrator
         */
        public static function getAstelliaAdminProfile(){
            $database = Database::getConnection();
            $query = "SELECT id_profile FROM profile 
                        WHERE profile_type='admin'
                        AND client_type = 'protected'";
            return $database->getOne($query);
        }
        
        /**
         * Retourne le nom du Profile à partir du Guid du droit (le guid peut être passé avec ou sans le préfixe APPLI_GUID_HEXA)
         * @param string $guid guid du droit ou identifiant du profile
         * @return string nom du profile 
         * 23/02/2012 NSE DE Astellia Portal Lot2
         */
        public static function getNameFromRightGuid($guid){
            $database = Database::getConnection();
            // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
            $guid = str_replace(APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.', '', $guid);
            $query = "SELECT profile_name FROM profile WHERE id_profile='$guid'";
            return $database->getOne($query);
        }

        /**
         * Retourne l'id_profile à partir du Guid Portail
         * @param string $guid
         * @return string 
         */
        public static function getIdFromPaaGuid($guid){
            $database = Database::getConnection();
            $guid = str_replace(APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.', '', $guid);
            $query = "SELECT id_profile FROM profile WHERE id_profile='$guid'";
            $database->executeQuery($query);
            if($database->getNumRows()>0)
                return $guid;
            else{
                $query = "SELECT id_profile FROM profile WHERE id_profile like 'prfl.%.%".intval($guid)."'";
                return $database->getOne($query);
            }
        }
}
