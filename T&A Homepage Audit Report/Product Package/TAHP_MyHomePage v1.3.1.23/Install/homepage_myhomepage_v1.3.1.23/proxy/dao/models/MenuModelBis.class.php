<?php
/*
	19/01/2009 GHX
		- ajout de fonction static getValuesList
	23/01/2009 - SLC - prise en compte des profils dans l'ajout de menu, la suppression d'un menu supprime ses enfants
	30/01/2009 GHX
		- modification des requetes SQL pour mettre les valeurs entre cote [REFONTE CONTEXTE)
	02/02/2009 GHX
		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
	12/02/2009 GHX
		- modification des requetes SQL pour mettre les valeurs entre cote [REFONTE CONTEXTE)
	12/02/2009 BBX
		- modification du test du l'id profile pour gérer les nouveaux id
	26/03/2009 GHX
		- Correction d'un bug, quand on créait un menu user il était ajouté aussi dans les profils admin 
	19/05/2009 GHX
		- Correction d'une condition dans la fonction deleteMenu()
		- Ajout d'un paramètre à la fonction addMenu(), il est possible de définir le nouvel ID du menu au lieu qu'il soit généré aléatoirement
	19/08/2009 GHX
		- Ajout d'un paramètre à la fonction getUserMenus()
	25/08/2009 GHX
		- Modification de la fonction addMenu() gestion des sous-menus
	28/10/2009 GHX
		- Ajout de l'ID produit dans le constructeur
		- Modif dans la fonction deleteMenu() pour passer l'ID produit à certaines fonctions
	21/12/2009  NSE
		- bz 11235 modification du calcule de la position en utilisant les restrictions sur droit_affichage et is_profile_ref_user de menu_deroulant_intranet
	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
		
	11/04/2012 AVZ / MyHomepage
		- Ajout de la méthode "getMenuChildren" 
*/
?>
<?php
/**
*	Classe permettant de manipuler les menus
*	Travaille sur la table menu_deroulant_intranet,profile_menu_position
*
*	@author	BBX - 01/12/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*/
class MenuModelBis
{
	/**
	* Propriétés
	*/
	private $idMenu = 0;	
	private static $database = null;
	private static $databaseParam = "";
	private $menuValues = Array();
	private $error = false;
	
	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();

	/**
	 * Constructeur
	 *
	 *	28/10/2009 GHX
	 *		- Ajout du paramètre $idProduct
	 * 
	 * @param : int	id menu
	 * @param : int	id profil <optional>
	 * @param int $idProduct
	 */
	public function __construct($idMenu,$idProfile=0, $idProduct = '')
	{
		// $this->debug = true;
		
		// Sauvegarde de l'id menu
		$this->idMenu = $idMenu;
		// Connexion à la base de données
		if ( empty(self::$database) || self::$databaseParam != $idProduct )
		{
			self::$database = Database::getConnection($idProduct);
			self::$databaseParam = $idProduct;
		}
		// Récupération des valeurs du menu
		if(is_numeric($idMenu) || is_string($idMenu)) {
			// Si un id profile est saisi, on récupère les menus selon la conf du profil
			if(is_string($idProfile)) {
				$query = "SELECT m.id_menu,
				m.niveau,
				(CASE WHEN p.position IS NULL THEN m.position ELSE p.position END) AS position,
				(CASE WHEN p.id_menu_parent IS NULL THEN m.id_menu_parent ELSE p.id_menu_parent END) AS id_menu_parent,
				m.libelle_menu,
				m.lien_menu,
				m.complement_lien,
				m.liste_action,
				m.largeur,
				m.hauteur,
				m.deploiement,
				m.repertoire,
				m.id_page,
				m.droit_affichage,
				m.droit_visible,
				m.menu_client_default,
				m.is_profile_ref_user,
				m.is_menu_defaut,
				m.is_profile_ref_admin 
				FROM menu_deroulant_intranet m LEFT JOIN profile_menu_position p 
				ON (m.id_menu = p.id_menu AND p.id_profile = '{$idProfile}')
				WHERE m.id_menu = '{$idMenu}'";			
			}
			else {
				$query = "SELECT * FROM menu_deroulant_intranet WHERE id_menu = '{$idMenu}'";
			}
			$array = self::$database->getRow($query);
			// Si les infos user ne sont pas récupérées, on renvoie une erreur
			if(count($array) == 0) {
				$this->error = true;
			}
			else {
				$this->menuValues = $array;
			}
		}
		else {
			// Si le format de l'id est incorrect, on renvoie une erreur
			$this->error = true;
		}
	}
	
	/************************************************************************
	* Méthode getValues : retourne un tableau associatif contenant les paramètres du menu
	* @return : array	Tableau associatif 
	************************************************************************/
	public function getValues()
	{
		return $this->menuValues;
	}
	
	/************************************************************************
	* Méthode getValue : retourne la valeur du tableau associatif contenant les paramètres du menu
	* @return : value	valeur 
	************************************************************************/
	public function getValue($val)
	{
		return $this->menuValues[$val];
	}
	
	/************************************************************************
	* Méthode setValue : ajoute une valeur à l'objet
	* @return : void()
	************************************************************************/
	public function setValue($key,$value)
	{
		$this->menuValues[$key] = $value;
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
	* Méthode addMenu : créé un nouveau menu et retourne son identifiant
	*
	*	26/03/2009 GHX
	*		- Correction d'un bug, quand on créait un menu user il était ajouté aussi dans les profils admin 
	*	19/05/2009 GHX
	*		- Ajout du paramètre à la fonction. Permet de définir en dur l'identifiant du nouveau menu (sert pour les dashboards)
	*	25/08/2009 GHX
	*		- Gestion des sous-menus (ajout du deuxieme paramètre)
	*
	* @param string $idMenu identifiant du menu à ajouter (par défaut, l'identifiant est généré aléatoirement)
	* @param boolean $managementSubMenu TRUE si le menu qu'on ajoute peut etre dans un sous-menu dans ce cas on ajout les menus parents dans le profils (default FALSE) valable uniquement pour les profils USERS
	* @return string
	************************************************************************/
	public function addMenu( $newIdMenu = null, $managementSubMenu = false )
	{
		// 15:01 19/05/2009 GHX
		// Si on a déjà un identifiant
		if ( $newIdMenu == null )
		{
			// Calcul du nouvel id
			// 16:55 30/01/2009 GHX
			// Nouveau format pour l'identifiant d'un menu
			// 14:23 02/02/2009 GHX
			// Appel à la fonction qui génére un unique ID
			$newIdMenu = generateUniqId('menu_deroulant_intranet');
		}
		// Insertion du menu
		$query = "INSERT INTO menu_deroulant_intranet (id_menu) VALUES ('{$newIdMenu}')";
		self::$database->execute($query);
		// Sauvegarde de l'id menu dans l'objet
		$this->idMenu = $newIdMenu;
		$this->setValue('id_menu',$newIdMenu);

		// Si id_menu_parent != 0, on calcule la position et le niveau du nouveau menu
		if ($this->menuValues['id_menu_parent']!='0') {
			// on calcule le niveau
			$niveau_parent = self::$database->getone("select niveau from menu_deroulant_intranet where id_menu='".$this->menuValues['id_menu_parent']."'");
			$this->setValue('niveau',$niveau_parent+1);
			// on calcule la position
			$position = self::$database->getone("select position from menu_deroulant_intranet where id_menu_parent='{$this->menuValues['id_menu_parent']}' order by position desc limit 1");
			$position = intval($position);
			if ($this->debug)
				echo "<div class='debug'>position du nouveau menu : $position</div>";
			$this->setValue('position',$position+1);
		} else {
			$this->setValue('id_menu_parent',0);
			$this->setValue('niveau',1);
			// 15:39 21/12/2009  NSE
			// bz 11235 modification pour calculer la position en utilisant les restrictions sur 
			// droit_affichage et is_profile_ref_user de menu_deroulant_intranet
			if($this->menuValues['is_profile_ref_user']=='1' && $this->menuValues['droit_affichage']=='customisateur'){
				$query = "SELECT MAX(position) AS last_position FROM menu_deroulant_intranet
				WHERE id_menu_parent = '{$this->menuValues['id_menu_parent']}'
				AND is_profile_ref_user = 1
				AND droit_affichage='customisateur'";
				$result = self::$database->getRow($query);
				if($result['last_position'] == ''){
					$position = 0;
				}
				else{
					$position = $result['last_position'];
				}
			}
			else{
				$position = 0;
			}
			// NSE 21/12/2009 fin
		}

		// Mise à jour des champs
		// self::$database->debug = 1;
		$this->updateMenu();
		
		//  NSE 21/12/2009 bz 11235
		$this->setMenuPosition($position+1);
		// NSE 21/12/2009 fin
		
		// 15:38 26/03/2009 GHX
		// Correction d'un bug : quand on créait un menu il était ajouté dans tous les types de profils
		if ($this->menuValues['is_profile_ref_user']==1)
		{
			// MaJ de la table profile_menu_position.
			// on boucle sur tous les profils
			foreach(ProfileModel::getProfiles() as $profil)
			{
				if ( $profil['profile_type'] == 'user' )
				{
					// on instancie le profil
					$ProfileModel = new ProfileModel($profil['id_profile']);
					// on ajoute le menu au profil
					$ProfileModel->addMenuToProfile($this->idMenu);
					// 11:28 25/08/2009 GHX
					// Gestion des sous-menus
					if ($this->menuValues['id_menu_parent']!='0' && $managementSubMenu == true )
					{
						$menusProfile = $ProfileModel->getMenus();
						if ( !in_array($this->menuValues['id_menu_parent'], $menusProfile) )
						{
							$ProfileModel->addMenuToProfile($this->menuValues['id_menu_parent']);
						}
					}
					// on propage la modification
					$ProfileModel->buildProfileToMenu();
				}
			}
		}
		
		if ($this->menuValues['is_profile_ref_admin']==1)
		{
			// MaJ de la table profile_menu_position.
			// on boucle sur tous les profils
			foreach(ProfileModel::getProfiles() as $profil)
			{
				if ( $profil['profile_type'] == 'admin' )
				{
					// on instancie le profil
					$ProfileModel = new ProfileModel($profil['id_profile']);
					// on ajoute le menu au profil
					$ProfileModel->addMenuToProfile($this->idMenu);
					// on propage la modification
					$ProfileModel->buildProfileToMenu();
				}
			}
		}
		
		// echo self::$database->displayQueries();
		// Retour de l'id menu
		return $newIdMenu;
	}
	
	/************************************************************************
	* Méthode updateMenu : met à jour un menu :
	* Sauvegarde les informations contenues dans l'objet
	* @return : void()
	************************************************************************/
	public function updateMenu()
	{
		// Parcours des valeurs
		foreach($this->menuValues as $key => $value) {
			// Mise à jour de l'information
			$value = ($value === '' || $value === null) ? "NULL" : ((is_numeric($value)) ? $value : "'$value'");
			$query = "UPDATE menu_deroulant_intranet SET $key = $value WHERE id_menu = '$this->idMenu'";
			self::$database->execute($query);
		}	
	}
	
	/************************************************************************
	* Méthode deleteMenu : supprime un menu
	* @return : void()
	************************************************************************/
	public function deleteMenu()
	{
		// Avant de supprimer le menu, on va supprimer ses enfants
		// 14:39 19/05/2009 GHX
		// Modification de la condition
		if (is_string($this->idMenu)) {
			$enfants = self::$database->getall("SELECT id_menu FROM menu_deroulant_intranet WHERE id_menu_parent='".$this->idMenu."'");
			if ($enfants) {
				foreach ($enfants as $row) {
					$menuEnfant = new MenuModel($row['id_menu'], 0, self::$databaseParam);
					$menuEnfant->deleteMenu();
				}
			}
		}
		
		// Suppression dans le table menu_deroulant_intranet
		$query = "DELETE FROM menu_deroulant_intranet WHERE id_menu = '{$this->idMenu}'";
		self::$database->execute($query);
		
		// Suppression de la table profile_menu_position
		foreach(ProfileModel::getProfiles() as $profil) {
			$ProfileModel = new ProfileModel($profil['id_profile'], self::$databaseParam);
			// Si le menu fait parti de ce profil
			if($ProfileModel->isMenuInProfile($this->idMenu)) {
				// On supprime le menu
				$ProfileModel->removeMenuFromProfile($this->idMenu);
				// On propage la modification
				$ProfileModel->buildProfileToMenu();
			}
		}
	}
	
	/************************************************************************
	* Méthode setMenuPosition : change la position d'un menu
	* @param int	position
	* @param int	id du menu à bouger
	* @return : void()
	************************************************************************/
	public function setMenuPosition($p)
	{	
		// On décale toutes les autres positions
		$query = "UPDATE menu_deroulant_intranet SET position = position+1 WHERE position >= {$p}
		AND id_menu_parent = '{$this->menuValues['id_menu_parent']}'";
		self::$database->execute($query);
		// Modification de la position
		$query = "UPDATE menu_deroulant_intranet SET position = {$p} 
		WHERE id_menu = '{$this->idMenu}'";
		self::$database->execute($query);
	}
	
	/************************************************************************
	* Méthode getUserMenuLastPosition : récupère la position la plus grande des éléments du même parent
	* (Valable sur les menus User)
	* @return int	last position
	************************************************************************/
	public function getUserMenuLastPosition()
	{
		$query = "SELECT MAX(position) AS last_position FROM menu_deroulant_intranet
		WHERE id_menu_parent = '{$this->menuValues['id_menu_parent']}'
		AND is_profile_ref_user = 1";
		$result = self::$database->getRow($query);
		return ($result['last_position'] == '') ? 0 : $result['last_position'];
	}	
	
	/************************************************************************
	 * Méthode getMenuChildren : récupère les menus enfants du menu
	 ***********************************************************************/
	public function getMenuChildren() {
		$query = "SELECT id_menu, libelle_menu, lien_menu FROM menu_deroulant_intranet WHERE id_menu_parent = '{$this->idMenu}'";
		$result = self::$database->execute($query);
		$arrayResult = Array();
		while($menu = self::$database->getQueryResults($result, 1)) {
		    $arrayResult[] = Array('id_menu' => $menu['id_menu'], 'libelle_menu' => $menu['libelle_menu'], 'lien_menu' => $menu['lien_menu']);
		}
		return $arrayResult;			
	}
	
	/************************* STATIC FUNCTIONS ***************************/
	
	// Ces fonctions manipulent menu_deroulant_intranet, donc la configuration par défaut des menus.
	// Pour manipuler les menus des profils, utiliser la classe ProfileModel.class
	
	/************************************************************************
	* Méthode getUserMenus : retourne les menus User
	*
	*	19/08/2009 GHX
	*		- Ajout du paramètre
	*
	* @param int $idProduct identifiant du produit sur lequel on doit se connecter (default master product)
	* @return : array	tableau des menus
	************************************************************************/
	public static function getUserMenus( $idProduct = '' )
	{	
		// Connexion à la base de données
		$database = Database::getConnection($idProduct);
		// Récupération des menus
		$query = "SELECT DISTINCT id_menu FROM menu_deroulant_intranet 
		WHERE is_profile_ref_user = 1";
		$result = $database->execute($query);
		$arrayRetour = Array();
		while($menu = $database->getQueryResults($result,1)) {
		    $arrayRetour[] = $menu['id_menu'];
		}
		return $arrayRetour;
	}
	
	/************************************************************************
	* Méthode getRootUserMenus : retourne les menus User racine
	* @return : array	tableau des menus
	************************************************************************/
	public static function getRootUserMenus($idProd="")
	{	
                // maj 17/05/2010 - MPR : Correction du bz 15469 - Les menus du produit supprimé apparaissent dans profile management
                $condition = ($idProd == "") ? "": " AND libelle_menu = ( SELECT sdp_label FROM sys_definition_product WHERE sdp_id = {$idProd})";
		// Connexion à la base de données
		$database = Database::getConnection(0);
                // 07/06/2011 BBX -PARTITIONING-
                // Correction des casts
		// Récupération des menus
		$query = "SELECT DISTINCT id_menu, position FROM menu_deroulant_intranet 
		WHERE is_profile_ref_user = 1
		AND id_menu_parent = '0'
		AND droit_affichage != 'astellia'
                {$condition}
		ORDER BY position";
		$result = $database->execute($query);
		$arrayRetour = Array();
		while($menu = $database->getQueryResults($result,1)) {
		    $arrayRetour[] = $menu['id_menu'];
		}
		return $arrayRetour;
	}
	
	/************************************************************************
	* Méthode getAdminMenus : retourne les menus Admin
	* @return : array	tableau des menus
	************************************************************************/
	public static function getAdminMenus()
	{	
		// Connexion à la base de données
		$database = Database::getConnection(0);
		// Récupération des menus
		$query = "SELECT DISTINCT id_menu FROM menu_deroulant_intranet 
		WHERE is_profile_ref_admin = 1";
		$result = $database->execute($query);
		$arrayRetour = Array();
		while($menu = $database->getQueryResults($result,1)) {
		    $arrayRetour[] = $menu['id_menu'];
		}
		return $arrayRetour;
	}
	
	/************************************************************************
	* Méthode getIdMenuFromLabel : retourne l'id d'un menu depuis son label
	* @return : array	tableau des menus
	************************************************************************/
	public static function getIdMenuFromLabel($label='')
	{
		// Connexion à la base de données
		$database = Database::getConnection(0);
		// Récupération du menu
		$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu = '{$label}'";
		$result = $database->getRow($query);
		return $result['id_menu'];
	}
	
	/************************************************************************
	* Méthode getValuesList : retourne un tableau associatif contenant les paramètres des menus
	*
	*	19/08/2009 GHX
	*		- Ajout du parametre $idProduct
	*
	* @parray : array	tableau des id menus
	* @param int $idProduct identifiant du produit sur lequel on doit se connecter (default master product)
	* @return : array	tableau des menus
	************************************************************************/
	public static function getValuesList ($listIdMenu, $idProduct = '')
	{
		$database = Database::getConnection($idProduct);
		$query = "SELECT * FROM menu_deroulant_intranet WHERE id_menu IN ('".implode("','",$listIdMenu)."')";
		$result = $database->execute($query);

		$return = array();
		while($elem = $database->getQueryResults($result,1))
		{
		    $return[$elem['id_menu']] = $elem;
		}
		
		return $return;
	}
	
	
	/**
	 * Vérifie si la liste de ID menus passés en paramètres existent bien dans la table menu_deroulant_intranet
	 * si tous les ID existent TRUE est renvoié sinon un tableau contenant la liste des ID qui n'existent pas
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $listIdMenu tableau contenant la liste des ID menus a vérifier 
	 * @param mixed $database prend les mêmes valeurs que DataBaseConnection
	 * @return mixed
	 */
	public static function isMenuExists ( $listIdMenu, $database = "" )
	{	
		// Connexion à la base de données
		if ( empty(self::$database) || self::$databaseParam != $database )
		{
			self::$database = Database::getConnection($database);
			self::$databaseParam = $database;
		}
		
		$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE id_menu NOT IN ('".implode("','",$listIdMenu)."')";
		$result = self::$database->execute($query);

		if ( self::$database->getNumRows() == 0 )
			return true;
		
		$return = array();
		while($elem = self::$database->getQueryResults($result,1))
		{
		    $return[] = $elem['id_menu'];
		}
		
		return $return;
	} // End function isMenuExists
}
