<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?php
/*
	15/06/2009 GHX
		- Correction du BZ9815 [REC][T&A Cb 5.0][MENU]: après activation d'un salve, les dash du slave ne sont pas reliés au profil créé sur le slave avant activation
			-> Modification de la fonction addMenu()
		- Correction du BZ 9696 [REC][T&A CB 5.0][ACTIVATION]: remontée sur master d'un compte identique au master.
			-> Modif dans la fonction userManagement()
		- Correction du BZ 9697 [REC][T&A CB 5.0][ACTIVATION]: remontée sur master d'un compte identique au master mais avec pwd différent
			-> Modif dans la fonction userManagement()
	24/06/2009 GHX
		- Correction du problème des labels dans menu_deroulant_intranet
	07/07/2009 MPR
		- Correction du bug 9704
	09/07/2009 GHX
		- Correction du BZ 10483 [REC][T&A CB 5.0][ACTIVATION] : Les "Report Schedule" des Slaves ne sont pas remontés sur le master
			-> Ajout de l'élément Report Schedule dans le contexte d'activation
	15/07/2009 GHX
		- Ajout d'une petite évolution sur le nom du contexte d'activation pour éviter qu'ils s'appellent tous context_activation_1.tar.bz2
		  ce qui permet d'avoir à des backup à chaque fois qu'on fait une activation
	30/07/2009 GHX
		- Correction du BZ 10356 [REC][T&A Cb 5.0][DEPLOIEMENT]: pb lorsqu'on a une config avec produit qui a plusieurs menus dashboards principaux
	11/08/2009 GHX
		- Correction du BZ 10356 REC][T&A Cb 5.0][DEPLOIEMENT]: pb lorsqu'on a une config avec produit qui a plusieurs menus dashboards principaux
			-> Manquait une colonne dans une requete SQL
			-> Problème de doublons suite à une ligne de code en trop ;)
	12/08/2009 GHX
		- Correction du BZ 11003 [REC][T&A CB 5.0][TC#36043][TP#1][Activation]: pb de profil admin default sur un compte admin du slave
		- Correction du BZ 11016 [REC][T&A CB 5.0][USER PROFILE] :Les menus du produit Slave ne sont pas correctement traité lors de l'activation du produit
	20/08/2009 GHX
		- Ajout d'une condition dans la fonction addMenu() => Correction du BZ 11182
	25/08/2009 GHX
		- Correction du BZ 11182 [REC][Contexte] Après contexte log user KO, menus user KO
			-> Modif dans la fonction addMenu() 
	29/10/2009 GHX
		- Correction du BZ 11355 [REC][T&A CB 5.0][TP#1][TC#40520][Contexte]: en cas d'import multi-produit , l'ordre Master-Slave est primordial
			-> Modif dans la fonction activation() 
			-> Modif dans la fonction addMenu() 
	10/12/2009 GHX
		- Modification de la requete SQL dans la fonction labelManagementPauto (  )
	28/07/2010 NSE
		- bz 16043 : modification des noms de menus en doublons sur le slave. On remplace "cb51_iu_Overview" par "Overview (cb51_iu)"
					 modification également du format des noms des envois de rapport et les noms de groupes
   01/12/2010 NSE
      - bz 19118 : suppression de la condition WHERE 1 = (SELECT COUNT(DISTINCT id_product) FROM sys_pauto_config)
   07/12/2010 MMT
     - bz 19626 detruit elements clients du Slave pour eviter l'ecrasement des
	    changement effectués sur le Master post Mise en multi-produit au prochain montage contexte
     - fonctions pour bz 11355 bloque l'access au menu contexte si en multiproduit et utilisateur
      non astellia_admin ou support_admin
 *  17/12/2010 NSE bz 19745 : on ne met pas dans le contexte d'activation les users si on est sur un slave (patch slave)
 * 
 * 21/05/2012 NSE bz 27144 : On n'ajoute pas le menu racine si on est sur un Gateway
*/
?>
<?php
/**
 * Classe qui gère la remonté des données d'un produit slave sur le produit master.
 * Les données remontés sont :
 *	- graphes
 *	- dashboards
 *	- rapports
 *	- users
 *	- groupes
 * Un post traitement est effectué ensuite:
 *	- création d'un menu avec le label du produit
 *	- met tous les menus dashboards du produit slave dans le menu créé précédement
 *	- MAJ des labels des graphe/dashboard/rapport/schedule/groupes si même label que le produit master
 *	- Suppression des utilisateurs doublons &MAJ des envois d'alarmes par mail et schedules
  *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @package Context
 */
class ContextActivation
{
	/**
	 * Nom du profil admin par défaut que l'on associera à un utilisateur de type admin
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	const PROFILE_NAME_ADMIN_DEFAULT = 'AdminDefault';

	/**
	 * Nom du profil user par défaut que l'on associera à un utilisateur de type user
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	const PROFILE_NAME_USER_DEFAULT = 'UserDefault';

	/**
	 * Spéficie si on doit déplacé les menus dashboards qui sont dans le menu CLIENT DASHBOARDS dans le menu du master
	 * @version CB 5.0.0.03
	 * @since CB 5.0.0.03
	 * @var bool
	 */
	const MOVE_DASHBOARD_MENU_CLIENT_DASHBOARD = false;
	
	/**
	 * Séparateur entre le label du produit slave et le label d'un élément dans le cas des doublons. Le séparateur est une chaîne de caractères donc il peut avoir plusieurs
	 * caractères (autres que cote, guillemet, virgule et point-virgule)
	 *
	 * Exemple :
	 *		SEPARATOR = '_'
	 *		on en doublons 2 graphes qui ont pour label Overview donc un vient du produit master (T&A GSM) et l'autre d'un produit slave (DTS Alcatel)
	 *		Le label du graphe venant du produit slave auront donc pour nouveau label : DTS Alcatel_Overview
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	const SEPARATOR = '_';

	/**
	 * Numéro du contexte d'activation
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_numberContext = 1;
	
	/**
	 * Répertoire dans lequel sera créé le contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_directory = null;

	/**
	 * Identifiant du produit master
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_idMaster = null;

	/**
	 * Identifiant du produit slave
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_idSlave = null;

	/**
	 * Ressource de connexion à la base de données sur le produit master
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var DatabaseConnection
	 */
	private $_dbMaster = null;

	/**
	 * Ressource de connexion à la base de données sur le produit slave
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var DatabaseConnection
	 */
	private $_dbSlave = null;

	/**
	 * Information sur le master
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_infoMaster = null;

	/**
	 * Information sur le slave
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_infoSlave = null;

	/**
         * Stocke la liste des users ajoutés dans la master
         * lors du passage en multiproduits
         * Ajouté dans le cadre de la correction du BZ 19783
         * @author BBX
         * @version CB 5.0.4.14
         * @since CB 5.0.4.14
         * @var array
         */
    private $_usersFromSlave = array();

	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idSlave identifiant du produit slave
	 */
	public function __construct ( $idSlave )
	{
		echo "\n idSlave : {$idSlave}";
		$this->_idSlave = $idSlave;

                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$this->_dbMaster = DataBase::getConnection();

		// Récupère l'identifiant du produit master
		$nbProduct = $this->_dbMaster->getOne("SELECT count(sdp_id) FROM sys_definition_product");
		if ( $nbProduct > 1 )
		{
			$this->_idMaster = $this->_dbMaster->getOne("SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 1");
		}
		else
		{
			throw new Exception(__T('A_E_CONTEXT_ACTIVATION_UNABLE_ONE_PRODUCT'));
		}
		
		echo "\n idMaster : {$this->_idMaster}";
		
		// On ne peut pas faire d'activation du master sur le master
		if ( $this->_idSlave == $this->_idMaster )
		{
			throw new Exception(__T('A_E_CONTEXT_ACTIVATION_UNABLE_ON_MASTER'));
		}

                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$this->_dbSlave = DataBase::getConnection($this->_idSlave);

		$this->_infoMaster = $this->_dbMaster->getRow("SELECT * FROM sys_definition_product WHERE sdp_id = {$this->_idMaster}");
		$this->_infoSlave = $this->_dbMaster->getRow("SELECT * FROM sys_definition_product WHERE sdp_id = {$this->_idSlave}");
	} // End function __construct

	/**
	 * Destructeur (fonction appelée automatique lors de la destruction de l'objet)
	 * Supprime le contexte d'activation
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __destruct ()
	{
		if ( file_exists("{$this->_directory}context_activation_{$this->_numberContext}.tar.bz2") )
		{
			// Supprime le contexte d'activation
			unlink("{$this->_directory}context_activation_{$this->_numberContext}.tar.bz2");
		}
	} // End function __destruct

	/**
	 * Spécifie le répertoire dans lequel se trouve le contexte où l'on peut décompresser le contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $dir chemin absolute vers le répertoire
	 */
	public function setDirectory ( $dir )
	{
		if ( !is_dir($dir) )
			throw new Exception(__T('A_E_CONTEXT_DIRECTORY_NOT_EXISTS', $dir));

		if ( !is_writable($dir) )
			throw new Exception(__T('A_E_CONTEXT_DIRECTORY_NOT_WRITEABLE', $dir));

		if ( substr($dir, -1) != '/' )
		{
			$dir .= '/';
		}
		$this->_directory = $dir;
	} // End function setDirectory

	/**
	 * Lance le mixage des données.
	 *
	 *	1. Créatoin d'un contexte d'activation
	 *	2. Monte le contexte d'activation sur le produit master
	 *	3. Post traitement
	 *		- création d'un menu avec le label du produit
	 *		- met tous les menus dashboards du produit slave dans le menu créé précédement
	 *		- MAJ des labels des graphe/dashboard/rapport/schedule/groupes si même label que le produit master
	 *		- Suppression des utilisateurs doublons & MAJ schedules
			*
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function activation ()
	{
		/*
			15:40 15/07/2009 GHX
			Evolution
		*/
		$nb = 1;
		while ( file_exists("{$this->_directory}backup_before_mount_context_context_activation_{$nb}.tar.bz2") )
		{
			$nb++;
		}
		
		$this->_numberContext = $nb;
		
		// 15:01 27/10/2009 GHX
		// Correction du BZ 11355
        // 01/12/2010 NSE bz 19118 : suppression de la condition WHERE 1 = (SELECT COUNT(DISTINCT id_product) FROM sys_pauto_config)
		$this->_dbSlave->execute("UPDATE sys_pauto_config SET id_product = {$this->_idSlave}");
		
		/*
			1. Création du contexte de migration
		*/
		$createContext = new ContextGenerate();
		$createContext->setDebug(1);
		$createContext->setDirectory($this->_directory);
		// 15:41 22/09/2009 GHX
		// Correction du BZ 11355
		// On précise que c'est un contexte d'activation
		$createContext->setActivation();

		// Ajout les éléments suivants dans le contexte d'activation
			//-> graphes
		$createContext->addElement(new ContextElement(1));
			//-> dashboards
		$createContext->addElement(new ContextElement(2));
			//-> rapports
		$createContext->addElement(new ContextElement(3));

                // 17/12/2010 NSE bz 19745 : on ne met pas dans le contexte d'activation les users si on est sur un slave (patch slave)
                if(self::isCurrentProductMasterOrStandAlone()){
			//-> users
			$createContext->addElement(new ContextElement(4));
			//-> groupes
			$createContext->addElement(new ContextElement(5));
                }
		// 10:55 09/07/2009 GHX
		// Correction du BZ 10483 [REC][T&A CB 5.0][ACTIVATION] : Les "Report Schedule" des Slaves ne sont pas remontés sur le master
			//-> reports schedules
		$createContext->addElement(new ContextElement(23));

		// Génère le contexte en spécifiant l'ID du produit slave pour générer un contexte d'activation
		$createContext->generate('context_activation', $this->_numberContext, $this->_idSlave);

		/*
			Pré-traitement
		*/
		// 14:44 27/10/2009 GHX
		$this->_dbMaster->execute("UPDATE sys_pauto_config SET id_product = 1 WHERE id_product IS NULL");
		
		/*
			2. Monte le contexte
		*/
		$ctxMount = new ContextMount();
		$ctxMount->setDebug(1);
		$ctxMount->setDirectory($this->_directory);
		// On spécifie que c'est un contexte d'activation (inhibe certains checks et traitements)
		$ctxMount->setActivation();
		$ctxMount->extract("context_activation_{$this->_numberContext}.tar.bz2");
		$ctxMount->mount();

                // 14/01/2011 BBX
                // Récupération des Users du Slave
                // BZ 19783, 20113
                $this->_usersFromSlave = $ctxMount->getUsersFromSlave();

		/*
			3. Post traitement
		*/

		// 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de la gestion des profiles par T&A
                // 21/05/2012 NSE bz 27144 : On n'ajoute pas le menu racine si on est sur un Gateway
                if(!productModel::isBlankProduct($this->_idMaster))
                    $this->addMenu($this->_infoMaster);
		$this->addMenu($this->_infoSlave);
		$this->reinitializeProfileUser();
		
		$this->labelManagement();

		$this->userManagement();

		// Déploie les users sur tous les produits  activés
		UserModel::deployUsers();
		
		// maj 07/07/2009 - MPR : Correction du bug 9704 
		//		- Les users sauvegardés pour les alarmes systèmes ne sont pas affichés (uniquement sur produit slave)
		// 		- Pas de correspondance entre les profiles du master et des slaves
		ProfileModel::deployProfile();
		
		// Déploie les groupes sur tous les produits activés
		GroupModel::deployGroups();

		// 07/12/2010 MMT bug 19626 detruit elements clients du Slave pour eviter l'ecrasement des
		//changement effectués sur le Master post Mise en multi-produit au prochain montage contexte
		ContextActivation::removeCustomPautoItemsFromSlave($this->_infoSlave['sdp_id']);
                
                // 18/04/2012 BBX
                // BZ 20584 : on restaure la position du menu mixed kpi
                if(ProductModel::getIdMixedKpi())
                    $this->restoreMixedKpiMenuPosition();


	} // End function activation

	/**
	 * Gère les profiles pour les users remontés. Si l'id_profile d'un utiliseur n'existe pas, on lui crée un profil par défaut.
	 * Pour les profils admin, le nom du profil sera AdminDefault. Si ce profil n'exist pas, on le crée avec tous les menus accessibles.
	 * Il en est de même pour le profil user UserDefault.
	 *
	 * @author GHX
	 * @version CB4.1.0.0
	 * @since CB4.1.0.0
         * @deprecated since CB 5.2 : 20/02/2012 NSE DE Astellia Portal Lot2
         * 
         * 20/02/2012 NSE DE Astellia Portal Lot2 : Cette fonction ne doit plus être utilisée.
	 */
	private function profileManagement ()
	{
		// Récupère la liste des users dont leur id_profile n'existe pas
		$result = $this->_dbMaster->getAll("SELECT id_user, user_profil FROM users WHERE user_profil NOT IN (SELECT id_profile FROM profile)");
		// Si tout est OK, on ne va pas plus loin
		if ( count($result) == 0 )
			return;

		// 16:29 12/08/2009 GHX
		// Correction du BZ 11003
		// On récupère la liste des profils du slave avant la création des 2 profils par défaut
		// car quand on crée un profil, on déploit la table sur tous les slaves
		/*
			Récupère la liste des profils que sont sur le slave
		*/
		$profilSlave = $this->_dbSlave->execute("SELECT id_profile, profile_type FROM profile");
		$profiles = array();
		if ( $this->_dbSlave->getNumRows() > 0 )
		{
			while ( $row =  $this->_dbSlave->getQueryResults($profilSlave, 1) )
			{
				$profiles[$row['id_profile']] = $row['profile_type'];
			}
		}
		
		/*
			Profil : AdminDefault
		*/
		$admin = $this->_dbMaster->getOne("SELECT id_profile FROM profile WHERE profile_name = '".self::PROFILE_NAME_ADMIN_DEFAULT."'");
		// Si le profil n'existe pas on le crée
		if ( $this->_dbMaster->getNumRows() == 0 )
		{
			$ProfileModel = new ProfileModel(0);
			$ProfileModel->setValue('profile_type', 'admin');
			$ProfileModel->setValue('profile_name', self::PROFILE_NAME_ADMIN_DEFAULT);
			$admin = $ProfileModel->addProfile();
			$ProfileModel->addMenuListToProfile(MenuModel::getAdminMenus());
			$ProfileModel->checkMandatoryMenus();
			$ProfileModel->buildProfileToMenu();
		}

		/*
			Profil : UserDefault
		*/
		$user = $this->_dbMaster->getOne("SELECT id_profile FROM profile WHERE profile_name = '".self::PROFILE_NAME_USER_DEFAULT."'");
		// Si le profil n'existe pas on le crée
		if ( $this->_dbMaster->getNumRows() == 0 )
		{
			$ProfileModel = new ProfileModel(0);
			$ProfileModel->setValue('profile_type', 'user');
			$ProfileModel->setValue('profile_name', self::PROFILE_NAME_USER_DEFAULT);
			$user = $ProfileModel->addProfile();
			$ProfileModel->addMenuListToProfile(MenuModel::getUserMenus());
			$ProfileModel->checkMandatoryMenus();
			$ProfileModel->buildProfileToMenu();
		}

		/*
			Boucle sur tous les users qui n'ont pas de profils
		*/
		foreach ( $result as $row )
		{
			if ( array_key_exists($row['user_profil'], $profiles) )
			{
				// ATTENTION ; le double $ est utilisé pour l'utilisation d'une variable dynamique
				$user_profil = $$profiles[$row['user_profil']];
			}
			else // Si l'id profil n'existe pas on lui associe un profil user par défault
			{
				$user_profil = $user;
			}

			$userModel = new UserModel($row['id_user']);
			$userModel->setValue('user_profil', $user_profil);
			$userModel->setValue('password', base64_decode($row['password']));
			$userModel->updateUser(false);
		}
	} // End function profileManagement

	/**
	 * Ajouter un nouveau menu dans tous les profils users et déplace les menus dashbaords dans celui-ci
	 *
	 *	29/10/2009 GHX
	 *		- Correction du BZ 11355
	 *			-> Réécriture de la fonction (plus simple)
	 *
	 * @author GHX
	 * @version CB 5.0.1.03
	 * @since CB4.1.0.0
	 * @param array $infoProduct tableau d'information sur le produit
	 */
	private function addMenu ( $infoProduct )
	{
		$idProduct = $infoProduct['sdp_id'];
		$libelleMenuProduct = $infoProduct['sdp_label'];
		$idMenuProduct = 'mdi.'.md5($infoProduct['sdp_db_name']);
		
		echo "\n"."Met tous les menus du produit {$idProduct} dans le menu {$libelleMenuProduct}";
		
		// Récupère le menu correspondant au produit
		$query = "SELECT * FROM menu_deroulant_intranet WHERE id_menu = '{$idMenuProduct}'";
		$result = $this->_dbMaster->getAll($query);
		
		if ( count($result) == 0 ) // Si le menu produit n'existe pas
		{
			// Si c'est le produit master
			if ( $idProduct == $this->_idMaster )
			{
				// On boucle sur tous les menus de niveau 0 des users du master pour les mettre dans le menu produit du master
				foreach ( MenuModel::getRootUserMenus() as $idMenuUserRoot )
				{
					$MenuModel = new MenuModel($idMenuUserRoot);
					$MenuModel->setValue('id_menu_parent', $idMenuProduct);
					$MenuModel->updateMenu();
				}
			}
			
			// Instanciation d'un menu model
			$MenuModel = new MenuModel(0);
			// Ajout des données menu
			$MenuModel->setValue('niveau','1');
			$MenuModel->setValue('id_menu_parent', '0');
			$MenuModel->setValue('position',$MenuModel->getUserMenuLastPosition()+1);
			$MenuModel->setValue('libelle_menu',$libelleMenuProduct);
			$MenuModel->setValue('largeur',strlen($libelleMenuProduct)*10);
			$MenuModel->setValue('deploiement','0');
			$MenuModel->setValue('hauteur','20');
			$MenuModel->setValue('hauteur','20');
			$MenuModel->setValue('droit_affichage','customisateur');
			$MenuModel->setValue('droit_visible','0');
			$MenuModel->setValue('menu_client_default','0');
			$MenuModel->setValue('is_profile_ref_user','1');
			// Enregistrement du menu produit
			$MenuModel->addMenu($idMenuProduct);
			
			if ( $idProduct == $this->_idMaster )
			{
				return;
			}
		}
		elseif ( $idProduct == $this->_idMaster ) // Si le menu produit existe et que c'est le menu produit du master
		{
			return; // on quitte la fonction
		}
		
		// Si on arrive juste qu'ici c'est qu'on est sur un produit slave
		
		// On récupère la liste des dashboards du produit qu'on active avec leur ID menu et l'ID menu parent
		$queryListDashWithIdMenuParent = "
			SELECT 
				sdd_id_page,
				mdi.id_menu,
				mdi.libelle_menu,
				mdi.id_menu_parent AS id_menu_parent,
				mdi_parent.libelle_menu AS libelle_menu_parent
			FROM 
				sys_definition_dashboard AS sdd,
				menu_deroulant_intranet AS mdi
				LEFT JOIN menu_deroulant_intranet AS mdi_parent ON (mdi.id_menu_parent = mdi_parent.id_menu)
			WHERE sdd_id_page IN (
						SELECT DISTINCT 
							sppn.id_page
						FROM 
							sys_pauto_config spc,
							sys_pauto_page_name sppn,
							menu_deroulant_intranet mdi
						WHERE 
							spc.id_page = sppn.id_page
							AND sppn.id_page = mdi.id_page
							AND spc.class_object = 'graph'
							AND spc.id_product = {$idProduct}
					)
				AND sdd_id_menu = mdi.id_menu
			ORDER BY
				mdi.id_menu_parent
			";
		$listDash = $this->_dbMaster->getAll($queryListDashWithIdMenuParent);

		// Si aucun résultat on quitte la fonction
		if ( count($listDash) == 0 )
			return;
		
		$lastIdMenuParent = null;
		foreach ( $listDash as $dash )
		{
			// Si on n'a pas de libelle pour le menu parent c'est qu'il n'est pas encore présent en base et si le menu n'a pas encore été créé
			// on va donc créer le menu parent et le mettre dans le menus produits
			if ( empty($dash['libelle_menu_parent']) && $dash['id_menu_parent'] != $lastIdMenuParent)
			{
				// On récupère le libelle du menu sur le slave
				$libelleMenuParent = $this->_dbSlave->getOne("SELECT libelle_menu FROM menu_deroulant_intranet WHERE id_menu = '{$dash['id_menu_parent']}'");
				
				// Création du menu parent
				$MenuModel = new MenuModel(0);
				$MenuModel->setValue('niveau','1');
				$MenuModel->setValue('id_menu_parent', $idMenuProduct);
				$MenuModel->setValue('position',$MenuModel->getUserMenuLastPosition()+1);
				$MenuModel->setValue('libelle_menu',$libelleMenuParent);
				$MenuModel->setValue('largeur',strlen($libelleMenuParent)*10);
				$MenuModel->setValue('deploiement','0');
				$MenuModel->setValue('hauteur','20');
				$MenuModel->setValue('hauteur','20');
				$MenuModel->setValue('droit_affichage','customisateur');
				$MenuModel->setValue('droit_visible','0');
				$MenuModel->setValue('menu_client_default','0');
				$MenuModel->setValue('is_profile_ref_user','1');
				// Ajout le menu en base, on passe id du contexte pour toujours avoir le même
				$MenuModel->addMenu($dash['id_menu_parent']);
				
				// On mémorise l'id du parent menu pour éviter de le créer plusieurs
				$lastIdMenuParent = $dash['id_menu_parent'];
			}
		}
	} // End function addMenu
	
	/**
	 * Réinitialise tous les profiles USERS
	 *
	 * @author
	 * @version CB 5.0.1.0.3
	 * @since CB 5.0.1.0.3
	 */
	private function reinitializeProfileUser ()
	{
		// Ré-initialise tous les profils Users
		$userMenuSlave = MenuModel::getUserMenus();
		foreach ( ProfileModel::getProfiles() as $profil )
		{
			if ( $profil['profile_type'] == 'user' )
			{
				$ProfileModel = new ProfileModel($profil['id_profile']);
				$this->_dbMaster->execute("DELETE FROM profile_menu_position WHERE id_profile = '{$profil['id_profile']}'");
				$ProfileModel->addMenuListToProfile($userMenuSlave);
				$ProfileModel->checkMandatoryMenus();
				$ProfileModel->buildProfileToMenu();
			}
		}
	} // End function reinitializeProfileUser
	
	/**
	 * Gestion des labels sur les graphes/dashboards/rapport/schedule. Si deux éléments d'un même type, on le même label, on modifie le deuxieme label (le plus récent inséré)
	 * en ajoutant le label du produit devant (donc le label du produit slave)
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function labelManagement ()
	{
		// Les graphes
		$this->labelManagementPauto('gtm');
		// Les dashboards
		$this->labelManagementPauto('page');
		// Les rapports
		$this->labelManagementPauto('report');

		$labelSlave = $this->_infoSlave['sdp_label'];

		/*
			Les schedules (envoi de rapport)
		*/
		$schedules = $this->_dbMaster->getAll("
				SELECT
					schedule_id
				FROM
					sys_report_schedule AS srs,
					(
						SELECT
							oid
						FROM
							sys_report_schedule
						WHERE
							schedule_name IN (
											SELECT
												schedule_name
											FROM
												sys_report_schedule
											GROUP BY
												schedule_name
											HAVING
												COUNT(schedule_name) > 1
										)
						ORDER BY
							oid DESC
						LIMIT 1
					) AS t1
				WHERE
					srs.oid = t1.oid
			");

		// Si on a des doublons
		if ( count($schedules) > 0 )
		{
			// Récupère les id des schedules sur le slave
			$elementsSlave = $this->_dbSlave->execute("SELECT schedule_id FROM sys_report_schedule");

			// Si aucun schédule sur le slave, c'est que les doublons été déjà la avant la remonté des données donc on ne fait rien
			if ( $this->_dbSlave->getNumRows() > 0 )
			{
				// Liste des schédules sur le slave
				$els = array();
				while ( $row =  $this->_dbSlave->getQueryResults($elementsSlave, 1) )
				{
					$els[] = $row['schedule_id'];
				}
				// 28/07/2010 NSE - bz 16043 : modification des noms en doublons sur le slave. On remplace "[label_prod]_[nom]" par "[nom] ([label_prod])"
				$queryUpdate = "UPDATE sys_report_schedule SET schedule_name = schedule_name || ' (' || '{$labelSlave}' || ')' WHERE schedule_id = '%s'";
				foreach ( $schedules as $el )
				{
					// On vérifie que l'élément vient bien du slave, si l'élément n'est pas dans le tableau c'est que l'élément ne vient pas du produit slave que l'on vient de monté
					if ( in_array($el['schedule_id'], $els) )
					{
						$this->_dbMaster->execute(sprintf($queryUpdate, $el['schedule_id']));
					}
				}
			}
		}

		/*
			Les groupes
		*/
		// Récupère les groupes en doublons
		$groups = $this->_dbMaster->getAll("
				SELECT
					id_group
				FROM
					sys_user_group AS sug,
					(
						SELECT
							oid
						FROM
							sys_user_group
						WHERE
							group_name IN (
									select
										group_name
									from
										sys_user_group
									group by
										group_name
									having
										count(distinct id_group) > 1
								)
						ORDER BY
							oid DESC
						LIMIT 1
					) AS t1
				WHERE
					sug.oid = t1.oid
			");

		// Si on a des groupes en doublons
		if ( count($groups) > 0 )
		{
			// 28/07/2010 NSE - bz 16043 : modification des noms en doublons sur le slave. On remplace "[label_prod]_[nom]" par "[nom] ([label_prod])"
			$queryUpdate = "UPDATE sys_user_group SET group_name = group_name || ' (' || '{$labelSlave}' || ')' WHERE id_group = '%s'";
			foreach ( $groups as $group )
			{
				$this->_dbMaster->execute(sprintf($queryUpdate, $group['id_group']));
			}
		}
	} // End function labelManagement

	/**
	 * Gestion des labels sur un type d'élément de sys_pauto_page_name
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $type
	 */
	private function labelManagementPauto ( $type )
	{
		// 17:49 10/12/2009 GHX
		// Modification de la requete SQL
		$elements = $this->_dbMaster->getAll("
				SELECT
					id_page,
					page_name
				FROM
					sys_pauto_page_name AS sppn,
					(
						SELECT DISTINCT ON(page_name)
							oid
						FROM
							sys_pauto_page_name
						WHERE
							page_name IN (
											SELECT
												page_name
											FROM
												sys_pauto_page_name
											WHERE
												page_type = '{$type}'
											GROUP BY
												page_name
											HAVING
												COUNT(page_name) > 1
										)
						ORDER BY
							page_name ASC,oid DESC
					) AS t1
				WHERE
					sppn.oid = t1.oid
			");

		// Si aucun doublons, on quitte la fonction
		if ( count($elements) == 0 )
			return;

		// Récupère les id des éléments sur le slave
		$elementsSlave = $this->_dbSlave->execute("SELECT id_page FROM sys_pauto_page_name WHERE page_type = '{$type}'");

		// Si aucun type de l'élément sur le slave, c'est que les doublons été déjà la avant la remonté des données donc on ne fait rien
		if ( $this->_dbSlave->getNumRows() == 0 )
			return;

		// Liste des éléments sur le slave
		$els = array();
		while ( $row =  $this->_dbSlave->getQueryResults($elementsSlave, 1) )
		{
			$els[] = $row['id_page'];
		}

		$labelSlave = $this->_infoSlave['sdp_label'];

		// 28/07/2010 NSE - bz 16043 : modification des noms de menus en doublons sur le slave. On remplace "cb51_iu_Overview" par "Overview (cb51_iu)"
		$queryUpdate = "UPDATE sys_pauto_page_name SET page_name = page_name || ' ('||'{$labelSlave}'||')' WHERE id_page = '%s' AND page_type = '{$type}'";
		$queryUpdateMenus = "UPDATE menu_deroulant_intranet SET libelle_menu = libelle_menu || ' ('||'{$labelSlave}'||')' WHERE id_page = '%s' AND libelle_menu = '%s'";
		foreach ( $elements as $el )
		{
			// On vérifie que l'élément vient bien du slave, si l'élément n'est pas dans le tableau c'est que l'élément ne vient pas du produit slave que l'on vient de monté
			if ( in_array($el['id_page'], $els) )
			{
				$this->_dbMaster->execute(sprintf($queryUpdate, $el['id_page']));
				// 18:32 24/06/2009 GHX
				// Correction du problème des labels dans menu_deroulant_intranet
				if ( $type == 'page' )
				{
					$this->_dbMaster->execute(sprintf($queryUpdateMenus, $el['id_page'], $el['page_name']));
				}
			}
		}
	} // End function labelManagement

	/**
	 * Gestion des utilisateurs. Suppression des doublons et MAJ des envois par email des alarmes et de schedules
	 *
	 * 	Un utilisateur est considéré comme un doublons, si les champs suivants sont égaux
	 *		- user login [login]
	 *
         * 14/01/2011 BBX
         * Réécriture de la méthode pour prendre en charge les doublons dans les comptes
         * BZ 19783, 20113
         *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function userManagement ()
	{
            // Récupération des comptes utilisateur en double
            $usersWithSameLogin = UserModel::getUsersWithSameLogin($this->_idMaster);

            // Si pas de User rappatriés, rien en sert d'aller plus loin
            if(empty($this->_usersFromSlave)) {
                return false;
            }

            // Traitement
            foreach($usersWithSameLogin as $login => $accounts)
            {
                $mergeAccounts = array();

                // Tri des comptes master / Slave pour la fusion
                foreach($accounts as $userId)
                {
                    if(array_key_exists($userId, $this->_usersFromSlave)) {
                        $mergeAccounts['slave'][] = $userId;
                    }
                    else {
                        $mergeAccounts['master'] = $userId;
                    }
                }

                // Fusion des comptes
                foreach($mergeAccounts['slave'] as $slaveAccount)
                {
                    if(UserModel::mergeAccounts($mergeAccounts['master'], $slaveAccount, $this->_idMaster, $this->_idSlave)) {
                        echo "\n\t Le compte Slave '$login' a été fusionné sur le compte '$login' du Master\n";
                    }
                    else {
                        echo "\n\t ECHEC lors de la fusion du compte Slave '$login' avec le compte Master '$login'\n";
                    }
                }
            }

	} // End function userManagement

	/**
	 * 07/12/2010 MMT bug 19626 + 11355
	 * Get the Current product ID from sys_definition_product
	 * @param <DataBaseConnection> $dbConn optional DataBaseConnection object to the product DB if omitted will create a new one
	 * @return <int> return product ID
	 */
	static function getCurrentProductId($dbConn='')
	{
		if($dbConn == ''){
			// create DB connection to the current product
                        // 31/01/2011 BBX
                        // On remplace new DatabaseConnection() par Database::getConnection()
                        // BZ 20450
			$db = Database::getConnection();
		} else {
			$db = $dbConn;
		}
		$products = $db->getAll("SELECT sdp_id, sdp_directory FROM sys_definition_product");

		$ret = 0;
		foreach ($products as $product)
		{
			// to test the current product, we test on the path name to the one registered in DB
			if ($product['sdp_directory'] == trim(NIVEAU_0,'/'))
			{
				$ret = $product['sdp_id'];
				break;
			}
		}

                // 31/01/2011 BBX
                // Opas de close
                // BZ 20450
		/*
		if($dbConn == ''){
			$db->close();
		}*/

		return $ret;
	}

	/**
	 * 07/12/2010 MMT bug 19626 + 11355
	 * return true if the current product is in multi product
	 * @param <bool> $includeMkpi true if considering product + mixed KPI as a multi-product
	 * @param <DataBaseConnection> $dbConn optional  object to the product DB if omitted will create a new one
	 * @return <bool> true if the current product is in multi product
		*/
	public static function isInMultiProduct($includeMkpi,$dbConn='')
	{
		if(empty($dbConn)){
			// create DB connection to the current product
                        // 31/01/2011 BBX
                        // On remplace new DatabaseConnection() par Database::getConnection()
                        // BZ 20450
			$db = Database::getConnection();
		} else {
			$db = $dbConn;
		}
		if($includeMkpi){
			$mkpiClause = "";
		} else {
			$mkpiClause = " where sdp_db_name not like '%mixed_kpi%'";
		}

		// if multiple products in sys_definition_product, we are in MP
		$nbProducts = $db->getone("select count(sdp_id) from sys_definition_product ".$mkpiClause);
                // 31/01/2011 BBX
                // On ne ferme plus la connexion
                // BZ 20450
                /*
		if($dbConn == ''){
			$db->close();
		}*/
		return ($nbProducts > 1);
	}

	/**
	 * 07/12/2010 MMT bug 19626 + 11355
	 * return true if the current product is a standalone product or master in a multi-product
	 * @param <DataBaseConnection> $dbConn optional object to the product DB if omitted will create a new one
	 * @return <bool> true if the current product is a standalone product or master in a multi-product
	 */
	static function isCurrentProductMasterOrStandAlone($dbConn=''){
		return ContextActivation::isProductMasterOrStandAlone(ContextActivation::getCurrentProductId($dbConn),$dbConn);
	}

	/**
	 * 07/12/2010 MMT bug 19626 + 11355
	 * return true if the product associated with the given ID is a standalone product
	 * or master in a multi-product
	 * @param <int> $product_id sdp_id from sys_definition_product of the selected product
	 * @param <DataBaseConnection> $dbConn optional object to the product DB if omitted will create a new one
	 * @return <bool> true if the product is a standalone product or master in a multi-product
	 */
	static function isProductMasterOrStandAlone($product_id,$dbConn='')
		{
		if($dbConn == ''){
                        // 31/01/2011 BBX
                        // On remplace new DatabaseConnection() par Database::getConnection()
                        // BZ 20450
			$db = Database::getConnection();
		} else {
			$db = $dbConn;
		}
		$isMaster = $db->getone("SELECT sdp_master FROM sys_definition_product where sdp_id = '".$product_id."'");

                // 31/01/2011 BBX
                // Pas de close
                // BZ 20450
                /*
		if($dbConn == ''){
			$db->close();
		}*/
		return ($isMaster == 1);
	}


	/**
	 * 07/12/2010 MMT bug 19626 + 11355
	 * Remove all customer Reports/Dashboards/Graphs/Schedule from given slave
	 * @param <int> $product_id sdp_id from sys_definition_product of the selected product
	 * @param <DataBaseConnection> $dbConn optional object to the product DB if omitted will create a new one
	 * @return <bool> true if the product is a standalone product or master in a multi-product
	 */
	static function removeCustomPautoItemsFromSlave($slave_id)
	{
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$dbSlave = Database::getConnection($slave_id);

		//should not happen but still verify this
		if(!ContextActivation::isProductMasterOrStandAlone($slave_id,$dbSlave)){

			echo "\n - Removing customer Reports/Dashboards/Graphs/Schedule from slave id ".$slave_id."\n";
			// list elements to be deleted
			$toDeletePages = $dbSlave->getAll("SELECT page_name, page_type from sys_pauto_page_name WHERE droit = 'client'");
			if(count($toDeletePages) == 0){
				echo "\tNo customer element found for this product \n";
			} else {
				// log the list
				foreach ($toDeletePages as $page)
				{
					echo "\t\t".$page['page_type']." \t ".$page['page_name']."\n";
				}
				//clean tables from references to deleted items

				// 31/01/2011 MMT bz 19626
				// remove elementes from  table graph_data
				$idPagesQueryClause = "SELECT id_page FROM sys_pauto_page_name WHERE droit = 'client'";
				// clean graph_data
				$clearGraphDataSql = "DELETE FROM graph_data WHERE id_data in (
									SELECT spc.id FROM  sys_pauto_config spc, sys_pauto_page_name sppn
									WHERE spc.id_page = sppn.id_page
									AND sppn.droit = 'client')";
				$dbSlave->execute($clearGraphDataSql);
				echo "\t - Table graph_data : ".$dbSlave->getAffectedRows()." deleted rows \n";

				//clean tables from references to deleted items
				// 31/01/2011 MMT bz 19626
				// remove elementes from table graph_information and sys_definition_dashboard
				$tableToClean = array('menu_deroulant_intranet','sys_pauto_config','graph_information','sys_definition_dashboard');
				$tableToClean[] = 'sys_pauto_page_name'; // sys_pauto_page_name MUST be called LAST
				$idPagesQueryClause = "SELECT id_page FROM sys_pauto_page_name WHERE droit = 'client'";
				foreach ($tableToClean as $t)
				{
                                    // 30/11/2012 BBX
                                    // BZ 30464 : fixing sys_definition_dashboard cleaning
                                    $column = ($t == 'sys_definition_dashboard') ? 'sdd_id_page' : 'id_page';
                                    $cleanSql = "DELETE FROM ".$t." WHERE {$column} in ( ".$idPagesQueryClause.")";
                                    $dbSlave->execute($cleanSql);
                                    echo "\t - Table ".$t." : ".$dbSlave->getAffectedRows()." deleted rows \n";
				}

				// there is no safe way to remove deleted reports from schedules
				// since all schedule and reports are from customer, delete all schedules
				$repTableToClean = array('sys_report_sendmail','sys_report_schedule');
				foreach ($repTableToClean as $rt)
				{
					$cleanSql = "DELETE FROM ".$rt;
					//$cleanSql = "UPdate ".$rt." set schedule_id = schedule_id ";
					$dbSlave->execute($cleanSql);
					echo "\t - Table ".$rt." : ".$dbSlave->getAffectedRows()." deleted rows \n";
				}
			}
		} else {
			echo "ERROR : the product ".$slave_id." is not a slave! should not removing customer elements";
		}
                // 31/01/2011 BBX
                // Pas de close
                // BZ 20450
		//$dbSlave->close();
	}
        
    /**
     * Permet de restaurer la position du Menu Mixed KPI 
     * BZ 20584
     */
    protected function restoreMixedKpiMenuPosition()
    {
        $query = "UPDATE menu_deroulant_intranet
            SET id_menu_parent = '0'
            WHERE libelle_menu = 'Mixed KPI'";
        $this->_dbMaster->execute($query);
        $query = "UPDATE profile_menu_position
            SET id_menu_parent = '0'
            WHERE id_menu = (SELECT id_menu 
                FROM menu_deroulant_intranet 
                WHERE libelle_menu = 'Mixed KPI')";
        $this->_dbMaster->execute($query);
    }
} // End class ContextActivation
?>