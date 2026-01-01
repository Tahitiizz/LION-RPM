<?php
/**
 * 
 *  CB 5.2
 * 
 * 07/02/2012 NSE DE Astellia Portal Lot2
 * 12/03/2012 NSE bz 26292 : ajout du paramètre _contextMode indiquant une installation par script ou IHM
 * 05/06/2012 NSE bz 27152 : Création des nouvelles familles sur le Corporate
 */
?><?php
/*
	10/04/2009 GHX
		- MaJ du new_field à 1 pour les tables sys_field_reference & sys_definition_kpi
	15/04/2009 GHX
		- On force le code produit en entier
	19/06/2009 GHX
		- Prise en compte qu'il peut manquer des colonnes dans le fichier cf function checkStruture()
	  	- Suppression des contraintes NOT NULL sur les tables temporaires dans la fonction loadDataInTableTemp()
	  	- Modif pour prendre en compte les erreurs PSQL dans la fonction loadDataInTableTemp()
	24/06/2009 GHX
		- Sauvegarde des valeurs de la clé primaire dans le cas où on fait un INSERT quand la table est vide (avant uniquement si UPDATE - INSERT) ce qui permet d'éviter quelques
		  problèmes d'installe de contexte dans si le produit est migré
		- Ajout d'une condition avant de créer le l'archive du backup afin d'éviter d'avoir une erreur à la fin d'une installe en ligne de commande
		- Après avoir monté un contexte d'activation, on ajoute tous les produits slaves dans le backup

	01/07/2009 BBX :
		- suppression du commentaire SQL dans la fonction getColumns car il faisait planter la requête. BZ 10322
	10/07/2009 GHX
		- Prise en compte d'un cas particulier avec la table sys_definition_selecteur pour le champ sds_sort_by qui contient aussi l'identifiant d'un produit dans la fonction mountElement()
		- Modification d'une condition dans la fonction mountElement()
	29/07/2009 GHX
		- correction du BZ 10858
			-> si le fichier sys_definition_network_agregation.csv est présent on lance le déploiement (scripts/deploy.php)
	30/07/2009 GHX
		- Modification d'une condition dans le cas d'un montage d'un élément
	06/08/2009 - MPR :
		- Correction du bug 10945 : Tous les niveaux d'agrégation 3ème axe
	11/08/2009 GHX
		- Correction d'un problème sur une variable mal initialisée
	20/08/2009 GHX
		- Correction du BZ 11142 [REC][T&A Cb 5.0][TP#1][TS#AA2-CB50][TC#37298][Contexte] : affichage de GTM/Dashboards vides lors du montage d'un contexte avec les graphs/dashboards mais sans les utilisateurs
	25/08/2009 GHX
		- Ajout de la fonction checkMenuDashboard() & getMessage()
	31/08/2009 GHX
		- Si on a du paramétrage BH dans le contexte, on regarde si on doit déployer les tables de la BH (ajout de la fonction launchDeployBH())
	02/09/2009 GHX
		- Correction du BZ 11345 [REC][T&A CB 5.0][TC#40549][TP#1][Contexte]: à l'import d'un contexte avec alarme, perte de la liste des kpi dans gtm builder
			-> On met new_field à 1 uniquement pour les nouveaux éléments
	04/09/2009 GHX
		- Correction du BZ 11394 [REC][T&A 5.0] Kpi non deployé avec context Xpert
	22/09/2009 GHX
		- Correction du BZ 11355
			-> Prise en compte des menus parents des dashbaords, on regarde s'ils existes et check des profils users
	30/09/2009 GHX
		- Correction du BZ 9855
			-> Ajout de l'option -i pour ignorer les différences de version de postgres dans le cas d'un multi-produit sur des serveurs distant donc les versions des postgres sont différentes
	01/10/2009 GHX
		- Correction du BZ 11780 [REC][T&A CB 5.0][GSM]: Pb gestion des identifiants de menus.
	06/11/2009 GHX
		- Modification des requetes SQL entre les tables sys_aa_interface et sys_global_parameters car si on n'a pas le même case, impossible de monté le contexte
	20/11/2009 GHX
		- Modification de 2 conditions dans la fonction mountElement()
	16/12/2009 GHX
		- Correction du BZ 13354 [TESTU][CB 5.0.1.7] Limite de 1600 compteurs/famille non gérée
		- Ajout des fonctions preMountElements() et postMountElements() pour plus de lisibilité dans la fonction mount()
	21/12/2009 GHX
		- Correction du BZ 13527 [REC][T&A IU 5.0][Migration v4-v5] Mauvais positionnement des menus et absence des sous-menus du contexte
	05/03/2010 MPR
		- Correction BZ14255 - Limitation du nombre de KPIs actifs prise en compte
	05/03/2010 NSE
		- Correction du bz 14454 : on vérifie qu'on a inséré dans la table tout les enregistrements qui étaient dans le csv (CAS 1 & 2)
	19/03/2010 BBX
		- Gestion du cas Corporate : on permet désormais l'application d'un contexte produit sur un Corporate. BZ 14392
		- Pour celà, on doit :
			=> Modifier la récupération du "module". Si "old_module" existe, on l'utilise. Création de la méthode "getModuleFromProduct"
			=> Utiliser la nouvelle colonne "use_in_corporate" pour déterminer si une table doit être montée ou non. Création de la méthode "isExcludedForCorporate"
			=> Pour la table sys_definition_categorie, on mémorise la valeur des NA Min. Pour sys_global_parameters, on mémorise module et old_module. Création des méthodes "saveColumn" et "restoreColumn"
			=> Pour les tables sys_definition_network_agregation et sys_definition_time_agregation, on doit utiliser les tables "bckp"
	22/03/2010 NSE
		- bz 14790 : on ajoute un test pour vérifier que le contexte que l'on va sauvegarder n'est pas vide
	24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
        21/05/2010 MPR Correction du BZ 15560 - Réécriture de la fonction checkMenuDashboard()
                      - La fonction permet de fixer l'id_menu_parent de tous les menus dashboard racine :
                              -> En monoproduit à 0
                              -> En multiproduit, on récupère id menu racine du produit et on fixe l'id_menu_parent de tous les menus dashboard avec celui-ci
    28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
    25/06/2010 - MPR Correction du bz 16181 - Initialisation impossible le nom du paramètre était faux "activation_source_availability_value" au lieu de "activation_source_availability"
   23/09/2010 NSE bz 18077 : le gis n'est plus géré par le contexte
 * 19/10/2010 BBX
   31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
 * 21/04/2011 NSE DE Non unique Labels : ajout du cas Corporate pour la recopie des valeurs de uniq_labels de sdnab vers sdna.
 * 04/05/2011 NSE bz 22040 : valeurs de la table non recopiées.
 * 31/05/2011 NSE bz 22349 : recréation des chemins d'agrégation des NA clients déployés détruits lors du montage du contexte
 * 11/07/2011 MMT bz 22751 :lors de montage sur corporate, il faut avoir compute_mode = ta_min
 * 04/08/2011 NSE bz 22995 : on effectue la mise à jour des id_linge également sur les tables temporaires du contexte afin d'éviter leur écrasement
 * 11/10/2011 NSE DE Bypass temporel
 * 20/10/2011 NSE bz 24295 Suppression du cas Mixed Kpi qui est impossible
 * 12/12/2011 ACS BZ 24993 Can not add slave product when patched on new CB
 * 12/12/2011 NSE DE : new parameters in AA links contextual filters
 * 12/01/2011 OJT : bz25417, problème de cast sur id_menu_parent
 * 01/06/2012 NSE bz 27016 : Création des nouvelles familles sur le Corporate
 * 12/12/2012 GFS bz 30752 : Mise à jour des tables alarmes
 */
?>
<?php
/**
 * Classe qui monte un contexte en base
 *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @package Context
 */
class ContextMount
{
	/**
	 * Nombre de compteurs maximum par familles sachant que la limite de colonne dans une table pour postgres est de 1600 (enfin de 250 à 1600 en fonction des types de colonnes mais dans notre cas on atteint 1600)
	 * Il faut aussi prendre en compte les colonnes pour les NA et TA et ce gardé une marge de sécurité par exemple dans le cas où on ajoute des NA
	 * @var int
	 */
	const MAX_RAW_BY_FAMILIES = 1570;

	/**
	 * Active ou non le mode débug
	 *	- 0 : désactivé
	 *	- 1 : activé
	 *	- 2 : activé et affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection)
	 *	- 6 : activé, affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection) et aucune compression
	 *
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_debug = 1;

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
	private $_idMasterProduct = null;

	/**
	 * Identifiant du produit d'un sous-contexte faisant référence au master (contient des tables que les autres sous-contexte n'ont pas)
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_idMasterProductInContext = null;

	/**
	 * Tableau d'informations sur tous les produits
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_infoAllProducts = array();

	/**
	 * Nom du contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_context = null;

	/**
	 * Tableau contenant la liste des sous-contextes
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_subContext = array();

	/**
	 * Tableaux de toutes les connexion à la base de données
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listDb = array();

	/**
	 * Liste des tables déjà utilisés dans le contexte du produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listTables = array();

	/**
	 * Tableau contenant la liste des colonnes de chaque tables
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_tablesColumn = array();

	/**
	 * Connexion à la base de données courante
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Ressource
	 */
	private $_db = null;

	/**
	 * Identifiant du produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_idProduct = null;

	/**
	 * Information sur le produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_infoProduct = null;

	/**
	 * Nom du répertoire dans lequel se trouvera les fichiers csv du produit courant
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_directoryProduct = null;

	/**
	 * Permet de savoir si le contexte a été extrait
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var boolean
	 */
	private $_isExtracted = false;

	/**
	 * Tableau contenant les identifiants des produits présent dans le contexte qui y sont plusieurs fois
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_multiProduct = array();

	/**
	 * Tableau associant un id produit du contexte à un id des produit sur lequel on monte le contexte
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_multiProductAssoc = array();

	/**
	 * Permet de savoir si le contexte a plusieurs fois le même type de produit
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var boolean
	 */
	private $_hasMultiProduct = false;

	/**
	 * Liste des tables présentes uniquement dans le sous-contexte d'un master
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_tablesInMaster = array();

	/**
	 * Pattern pour remplacer le code produit par son ID qui est dans sys_definition_product
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var string
	 */
	private $_patternReplaceIdProduct = null;

	/**
	 * Boolean permettant de savoir si on monte un contexte d'activation.
	 * Permet d'inhiber certains traitement
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var boolean
	 */
	private $_activation = false;

	/**
	 * Boolean permettant de savoir si on monte un contexte de migration
	 * Permet d'inhiber certains traitement
	 * @version CB5.0.2.2
	 * @since CB5.0.2.2
	 * @var boolean
	 */
	private $_migration = false;

	/**
	 * Liste des tables qui doivent être ignorées quand on monte un contexte d'activation
	 * Permet d'inhiber certains traitement
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_activationTablesIgnored = array();

        /**
         * 07/02/2012 NSE DE Astellia Portal Lot2
	 * Liste des tables qui doivent être ignorées en 5.2.0 (PAAL2)
	 * Permet d'inhiber certains traitement
	 * @version CB5.2.0.07
	 * @since CB5.2.0.07
	 * @var array
	 */
	private $_tablesIgnored = array();

	/**
	 * Message a afficher à l'utilsiateur sur le montage du contexte
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @var string
	 */
	private $_messages = '';

	/**
	 * Permet de savoir si le message sur RAW/KPI est déjà affiché ou non
	 * @version CB 5.0.0.07
	 * @since CB 5.0.0.07
	 * @var string
	 */
	private static $deployRawKpi = false;


	/**
	 * Mémorise les colonnes sauvegardées, ainsi que la table source, cible et la colonne de jointure
	 * Ajouté par BBX le 16/03/2010 dans le cadre de la correction du bug 14392
	 * @version CB 5.0.1.12
	 * @since CB 5.0.1.12
	 * @var array
	 */
	private $_savedColumns = array();

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
    
    // 05/01/2012 BBX
    // BZ 25126 : va mémorise la position et le statut des menus
    protected $_menuPositions = array();
    protected $_disabledMenus = array();
    
    // 12/04/2012 BBX
    // BZ 21721 : on mémorise les homepages
    protected $_usersHomepage = array();

    /**
     * Tableau mémorisant l'état du Bypass sur les différentes familles
     * @var array 
     */
    private $_bypassBeforeMount = array();
    
    /**
     * mode d'installation du contexte (1: installation ligne de commande, 2 IHM)
     * @var int
     * 12/03/2012 NSE bz 26292 : ajout du paramètre 
     */
    private $_contextMode = 2;
    
    /**
     * Stocke les counters actifs avant le montage du contexte
     * @var array
     * 29/05/2013 GFS - Bug 33864 - [SUP][TA Cigale GSM][MTN Iran][AVP 34007]: Raw activated by customer are deactivated during the upgrade
     */
    private $_counters = array();
    
    /**
     * 29/05/2013 GFS - Bug 33864 - [SUP][TA Cigale GSM][MTN Iran][AVP 34007]: Raw activated by customer are deactivated during the upgrade
     * Definition des valeurs des flags owner de sys_field_reference 
     * @var unknown
     */
    public static $OWNER_ASTELLIA = "0";
    public static $OWNER_CUSTOMER = "1";

	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __construct ()
	{
		$db = $this->getConnection("");
		// Récupère l'identifiant du produit master
		$nbProduct = $db->getOne("SELECT count(sdp_id) FROM sys_definition_product");
		if ( $nbProduct > 1 )
		{
			$this->_idMasterProduct = $db->getOne("SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 1");
		}
		else
		{
			// S'il n'y a qu'un seul produit il est considéré comme le master
			$this->_idMasterProduct = $db->getOne("SELECT sdp_id FROM sys_definition_product");
		}

		// Récupère les informations sur tous les produits
		$this->_infoAllProducts = getProductInformations();

		// 24/03/2010 NSE bz 14815 : on ne doit pas prendre en compte le produit mixed_kpi pour les contextes
		// Boucle sur tous les produits
		foreach ( $this->_infoAllProducts as $clef => $product )
		{
			if($product['sdp_id']==ProductModel::getIdMixedKpi()){
				// on supprime le produit du tableau
				unset($this->_infoAllProducts[$clef]);
			}
		}

		// Requete qui récupère uniquement les tables des éléments qui sont dans le sous-contexte d'un master
                // 09/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$query = "
			SELECT DISTINCT
				sdc_id,
				sdct_table
			FROM
				sys_definition_context,
				sys_definition_context_table_link,
				sys_definition_context_table
			WHERE
				sdc_id = sdctl_sdc_id::text
				AND sdct_id = sdctl_sdct_id
				AND sdc_master = 1
			";
		foreach ( $db->getAll($query) as $row )
		{
			$this->_tablesInMaster[$row['sdc_id']][] = $row['sdct_table'];
		}

		$listProduct = array();

		// Boucle sur tous les produits
		foreach ( $this->_infoAllProducts as $product )
		{
			// 15/03/2010 BBX : Modification de la requête pour prendre en compte le cas Corporate. BZ 14392
			// Spécifie la base de données courante par rapport au produit
			// $db = $this->getConnection($product['sdp_id']);
			// Récupère le nom du module correspondant au produit et l'associe à son ID
			//$module = $db->getOne("SELECT saai_interface FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (lower(value) = lower(saai_module)) WHERE parameters = 'module'");
			$module = $this->getModuleFromProduct($product['sdp_id']);
			// Fin BZ 14392

			if ( $product['sdp_master'] == 1 )
			{
				$listProduct[$product['sdp_id']] = $module;
			}
			elseif ( !in_array($module, $listProduct) )
			{
				$listProduct[$product['sdp_id']] = $module;
			}
		}

		$this->_patternReplaceIdProduct = "CASE ";
		foreach ( $listProduct as $idProduct => $module )
		{
			$this->_patternReplaceIdProduct .= 'WHEN ctx.\1 = \''.$module.'\' THEN '.$idProduct.' ';
		}
		$this->_patternReplaceIdProduct .= 'ELSE ctx.\1 ';
		$this->_patternReplaceIdProduct .= "END";

		// 15:49 23/03/2009 GHX
		// Spécifie les tables qui doivent être ignorées quand on monte un contexte d'activation
		$this->_activationTablesIgnored = array(
				'sys_definition_kpi',
				'sys_field_reference'
		);
                
		// 07/02/2012 NSE DE Astellia Portal Lot2
		$this->_tablesIgnored = array('users');
	} // End function __construct

	/**
	 * Destructeur (fonction appelée automatiquement lors de la destruction de l'objet)
	 *
	 *	- supprime le répertoire qui contient le contexte extrait
	 *	- supprime les tables temporaires
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __destruct ()
	{
		if ( $this->_debug & 2 )
			return;

		/*
			1. Supprime le répertoire dans lequel on a extrait le contexte
		*/
		$rep = "{$this->_directory}ctx_mount/";
		if ( file_exists($rep) )
		{
			exec('rm -rf "'.$rep.'"');
		}

		/*
			2. Supprime les tables temporaires du contextes pour chaque produit, elles commencent toutes par "ctx_"
		*/
		foreach ( $this->_infoAllProducts as $product )
		{
			$this->dropTablesTemp($this->getConnection($product['sdp_id']));
		}

		/*
			3. Création de l'archive contenant les sauvegardes des tables du contexte avant qu'il soit monté
		*/
		$ctxname = basename($this->_context, '.tar.bz2');
		// 08:18 24/06/2009 GHX
		// Ajout d'une condition pour éviter d'avoir une erreur lors de l'installe en ligne de commande
		if ( !file_exists("{$this->_directory}backup_before_mount_context_{$ctxname}.tar.bz2") && file_exists("{$this->_directory}backup_before_mount_context_{$ctxname}")  )
		{
			$rep = "{$this->_directory}backup_before_mount_context_{$ctxname}/";
			// Commande pour aller dans le répertoire $rep
			$cmdCd  = "cd {$rep}";
			// Commande pour créer l'archive $rep
			$cmdTar = "tar cfj ../backup_before_mount_context_{$ctxname}.tar.bz2 *";
			// Commande pour supprimer le répertoire $rep
			$cmdRm  = "rm -rf {$rep}";
			// Exécution des commandes pour créer l'archive
			exec("{$cmdCd};{$cmdTar};{$cmdRm}");
		}
	} // End function __destruct

	/**
	 * Retourne un message d'information sur le montage du contexte
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 * @return string
	 */
	public function getMessage ()
	{
		return $this->_messages;
	} // End function getMessage

	/**
	 * Spécifie le niveau de débug
	 *	- 0 : désactivé
	 *	- 1 : activé
	 *	- 2 : activé et affiche les requetes SQL (uniquement les requetes exécutées via DataBaseConnection)
	 *
	 * @author GHX
	 * @version CB 4.1.0.00
	 * @since CB 4.1.0.00
	 * @param int $level niveau de débuggage
	 */
	public function setDebug ( $level )
	{
		$this->_debug = intval($level);
	} // End function setDebug

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
	 * Spécifie que le contexte que l'on monte est un contexte d'activation
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param boolean $activation spécifie si on monte un contexte d'activation (default : true=
	 */
	public function setActivation ( $activation = true )
	{
		$this->_activation = $activation;
	} // End function setActivation

	/**
	 * Spécifie que le contexte que l'on monte est un contexte de migration
	 *
	 *	21/12/2009 GHX
	 *		- Ajout de la fonction pour corriger le BZ 1357
	 *
	 * @author GHX
	 * @version CB5.0.2.2
	 * @since CB5.0.2.2
	 * @param boolean $activation spécifie si on monte un contexte de migration (default : true)
	 */
	public function setMigration ( $migration = true )
	{
		$this->_migration = $migration;
	} // End function setActivation

     /**
      * Initialise le mode d'installation du context :
      *     1 : installation via sh
      *     2 : valeur par défaut utilisation via IHM
      * @param type $mode 
      * 12/03/2012 NSE bz 26292 : ajout du paramètre 
      */
        public function setContextMode($mode){
         $this->_contextMode = $mode;
     }

          /**
	 *  Extrait le contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $cxtname nom contexte
	 */
	public function extract ( $cxtname )
	{
		// Test de la présence du contexte
		if ( !file_exists("{$this->_directory}{$cxtname}") )
			throw new Exception(__T('A_E_CONTEXT_MOUNT_NOT_ARCHIVE', $cxtname, $this->_directory));

		$this->_context = $cxtname;

		// Création d'un dossier dans lequel on décompresse le contexte
		$rep = "{$this->_directory}ctx_mount/";
		// Par précaution si le répertoire existe on le supprime
		if ( is_dir($rep) )
		{
			exec('rm -rf "'.$rep.'"');
		}
		mkdir($rep, 0777);

		// Extrait le contexte dans le répertoire précédement créé
		if ( $this->_debug )
		{
			echo "\n\n======================================================\n";
			echo "\nExtrait le contexte '{$this->_context}' dans le répertoire '{$rep}'";
		}
		$cmdUntar = sprintf('tar xfjv "%s%s" -C "%s"',
				$this->_directory,
				$this->_context,
				$rep
			);
		exec($cmdUntar, $r, $error);
		// S'il est impossible d'extraire le contexte on lève une erreur
		if ( $error )
			throw new Exception(__T('A_E_CONTEXT_MOUNT_UNABLE_EXTRACT'));

		// Parcourt le contenu du contexte décompressé
		$listing = new DirectoryIterator($rep);

		foreach ( $listing as $subcontext )
		{
			// On ignore "." et ".."
			if ( $subcontext->isDot() )
				continue;

			if ( $this->_debug )
			{
				echo "\n  - Extrait le sous-contexte '{$subcontext->getFilename()}'";
			}

			// Extrait le sous-contexte
			$this->extractSubContext($rep, $subcontext->getFilename());
		}

		if ( count($this->_subContext) == 0 )
			throw new Exception(__T('A_E_CONTEXT_MOUNT_EMPTY', $this->_context));

		// 11:40 23/03/2009 GHX
		// Vérification des produits contenus dans le contexte uniquement si ce n'est pas un contexte d'activation
		if ( !$this->_activation )
		{
			$this->_hasMultiProduct = $this->detectMultiProduct();
		}

		$cmdChmod = sprintf('chmod 777 -R "%s"', $rep);
		exec($cmdChmod);

		$this->_isExtracted = true;
	} // End function extract

	/**
	 * Si on se trouve sur du multi-produit au sens où on a plusieurs fois le même produit.
	 * Si c'est le cas il faut renseigner quel sous-contexte va avec quel produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function detectMultiProduct ()
	{
		// Si on n'a qu'un seul sous-contexte et qu'un seul produit
		// il n'y a aucun soucis pour monter le contexte
		// if ( count($this->_subContext) == 1 && count($this->_infoAllProducts) == 1 )
			// return;

		/*
		// Si on n'a pas le même nombre de produit entre le contexte et la base,
		// on ne pourra pas monter le contexte
		if ( count($this->_subContext) != count($this->_infoAllProducts) )
			throw new Exception('Unable to mount the context because it was not the same number product in the context and the database');
		*/

		// Calcul le nombre de produit contenu dans le sous-contexte
		$nbProductInContext = array();
		foreach ( $this->_subContext as $subcontext)
		{
			$nbProductInContext[] = $subcontext['module'];
		}
		$nbProductInContext = array_count_values($nbProductInContext);

		// Calcul le nombre de produit contenu en base (c'est à dire dans la table sys_definition_product)
		$nbProductInDb = array();
		foreach ( $this->_infoAllProducts as $product)
		{
			if ( $product['sdp_on_off'] == 1 )
			{
				// 15/03/2010 BBX : Modification de la requête pour prendre en compte le cas Corporate. BZ 14392
				//$db = $this->getConnection($product['sdp_id']);
				// $nbProductInDb[] = $db->getOne("SELECT saai_interface FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (lower(value) = lower(saai_module)) WHERE parameters = 'module'");
				$nbProductInDb[] = $this->getModuleFromProduct($product['sdp_id']);
				// Fin BZ 14392
			}
		}
		$nbProductInDb = array_count_values($nbProductInDb);

		// Si on a une intersection c'est que les produits présents dans le contexte ne font pas partit des produits en base
		// donc impossible de monter le contexte
                // On monte le contexte même s'il n'y a pas les mêmes produits dans le contexte et dans la base
                // pourvu qu'il y ait au moins un sub-context en commun
		//if ( count(array_intersect_key($nbProductInContext,$nbProductInDb)) != count($nbProductInContext) )
                $contextsToMount = array_intersect_key($nbProductInContext,$nbProductInDb);
                if ( count($contextsToMount) < 1 ){
			throw new Exception(__T('A_E_CONTEXT_MOUNT_UNABLE_MOUNT'));
                }
		// On renseigne un tableau contenant la liste des types de produits qui apparaissent plusieurs fois dans le contexte
		foreach ( $nbProductInContext as $module => $nbModule )
		{
                    // s'il ne s'agit pas d'un contexte à monter, on passe au suivant
                    if(!isset($contextsToMount[$module])){
                        if ( $this->_debug ){
                            echo "\n  Le sous-contexte '{$module}' sera ignoré.";
                        }
                        // unset($nbProductInContext[$module]);
                        continue;
                    }
                    
			// Si on n'a qu'un seul produit d'un même type on passe au suivant
			if ( $nbModule == 1 )
				continue;

			foreach ( $this->_subContext as $subcontext)
			{
				if ( $module == $subcontext['module'] && $subcontext['master'] == false )
				{
					$this->_multiProduct[$subcontext['idProduct']] =  $module;
				}
			}
		}

		if ( count($this->_multiProduct) == 0 )
			return false;

		return true;
	} // End function detectMultiProduct

	/**
	 *Retourne TRUE s'il y a un du multi-produit dans ce cas il faudra renseigner quel sous-contexte va avec quel produit
	 * sinon il sera impossible de monter le contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return boolean
	 */
	public function hasMultiProduct ()
	{
		return $this->_hasMultiProduct;
	} // End function hasMultiProduct

	/**
	 * Retourne la liste des types de produits présents dans le contexte
	 *
	 *	array[idProduit] = module
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @return array
	 */
	public function getProductsInContext ()
	{
		return $this->_multiProduct;
	} // End function getProductsInContext

	/**
	 * Versionne le contexte dans la table sys_versioning
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $tables liste des tables présentes dans le sous-contexte
	 * @return boolean
	 */
	private function versioning ( $tables )
	{
		$date = date('Y_m_d_H_i');

		$this->_db->execute("INSERT INTO sys_versioning (item, item_value, item_mode, date) VALUES ('contexte', '{$this->_context}', 'contexte designer', '{$date}')");

		if ( array_key_exists('sys_versioning', $tables) )
		{
			// 12:19 18/06/2009 GHX
			// Met à jour la séquence
			$this->_db->execute("SELECT setval('public.sys_versioning_id_seq', (SELECT max(id)+1 FROM sys_versioning), true);");
			$this->_db->execute("UPDATE ctx_sys_versioning SET date = '{$date}', item_mode = 'contexte designer'");
			$this->_db->execute("
					INSERT INTO
						sys_versioning (item, item_value, item_mode, date)
					SELECT
						item,
						item_value,
						item_mode,
						date
					FROM
						ctx_sys_versioning
				");

			return true;
		}

		if ( $this->_debug )
		{
			echo "\n\n    Le fichier sys_versioning.csv n'est pas présent !";
		}

		return false;
	} // End function versioning

	/**
	 * Monte le contexte sur tous les produits
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function mount ()
	{
		// On peut monter le contexte uniquement si celui-ci a été extrait
		if ( $this->_isExtracted === false )
			throw new Exception(__T('A_E_CONTEXT_MOUNT_NOT_EXTRACT'));

		if ( $this->_debug )
		{
			echo "\n\nMonte le contexte '{$this->_context}'";
		}

		// 15:58 24/03/2009 GHX
		// Tableau contenant la liste des ID produits sur lequel on contexte a été monté ne contiendra pas l'ID du master
		$listProductMount = array();
		// 10:20 24/06/2009 GHX
		// Tableau contenant la liste des ID produits que l'on doit rajouter dans le backup
		$activationSlaveBackup = array();

		foreach ( $this->_infoAllProducts as $product )
		{
			// 11:42 23/03/2009 GHX
			// Si c'est un contexte d'activation; il est monté uniquement sur le master
			if ( $this->_activation )
			{
				if ( $product['sdp_master'] == 0 )
				{
					// 10:19 24/06/2009 GHX
					// Sauvegarde les produits slaves pour les ajouter dans le backup
					$activationSlaveBackup[] = $product;
					continue;
				}
			}

			$this->_db = $this->getConnection($product['sdp_id']);
			$this->_idProduct = $product['sdp_id'];
			$this->_infoProduct = $product;

			// 15/03/2010 BBX : Modification de la requête pour prendre en compte le cas Corporate. BZ 14392
			// $module = $this->_db->getOne("SELECT saai_interface FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (lower(value) = lower(saai_module)) WHERE parameters = 'module'");
			$module = $this->getModuleFromProduct($product['sdp_id']);
			// Fin BZ 14392

			if ( $this->_debug )
			{
				echo "\n  - Produit : ".$this->_infoProduct['sdp_label'].' [id: '.$this->_infoProduct['sdp_id'].' / module: '.$module.']';
			}

			if ( $this->_debug )
			{
				echo "\n    >> Sauvegarde des tables du contexte";
			}
			$this->backupTablesContext();

			// Supprime les tables temporaires
			$this->dropTablesTemp($this->_db);

			// Sélectionne le sous-contexte associé au produit
			$subcontext = $this->selectSubContext($module);
			if ( !$subcontext )
			{
				if ( $this->_debug )
				{
					echo "\n    AUCUN SOUS-CONTEXTE !!";
				}
				continue;
			}

			if ( $this->_debug )
			{
				echo "\n    >> Monte le sous-contexte '{$subcontext['name']}'";
			}

			// Répertoire dans lequel se trouve le sous-contexte
			$rep = "{$this->_directory}ctx_mount/{$subcontext['name']}/";
			// Parcourt tous les fichiers du sous-contexte
			$listing = new DirectoryIterator($rep);

			// Liste des tables que le sous-contexte doit renseigner
			$tables = array();
			foreach ( $listing as $file )
			{
				// On ignore "." et ".." et si c'est un dossier (normalement le sous-contexte ne contient pas de dossier mais on c'est jamais)
				if ( $file->isDot() || $file->isDir() )
					continue;

				// Si c'est le fichier md5 on l'ignore
				if ( $file->getFilename() == 'md5' )
					continue;

				// Vérifie la structure du fichier et récupère les colonnes communes avec le produit courant
				$header = $this->checkStruture($file->getFilename(), $rep);
				if ( $header )
				{
					// Charge le fichier CSV dans une table temporaire
					$table = $this->loadDataInTableTemp($file->getFilename(), $rep, $header);
					$tables[$table] = $header;
				}
			}

			if ( count($tables) == 0 )
				throw new Exception(__T('A_E_CONTEXT_MOUNT_SUBCONTEXT_EMPTY', $subcontext['name']));

			// Récupère la liste des éléments présent dans le contexte
			$listElements = $this->getElementsInSubContext($tables);
			if ( count($listElements) == 0 )
				throw new Exception(__T('A_E_CONTEXT_MOUNT_UNABLED_SELECT_ELEMENT', $subcontext['name']));

			$tablesUsed = array();

			// 15:48 16/12/2009 GHX
			// Correction du BZ 13354
                        // 21/11/2011 NSE bz 23942 : suppression du paramètre inutile $label_product
			$this->preMountElements($listElements, $tables, $tablesUsed);

			foreach ( $listElements as $element )
			{
				if ( $this->_debug )
				{
					echo "\n\n      - Monte l'élément : {$element->getLabel()}";
				}
				$this->mountElement($element, $tablesUsed, $tables);
			}
            
			// 15:48 16/12/2009 GHX
			// Ajout de la fonction pour plus de lisibilité
			$this->postMountElements($listElements, $tables, $tablesUsed);

			// 16:10 24/03/2009 GHX
			// Si c'est pas un contexte d'activation et que l'id produit est différent de celui-ci du master
			// on ajoute l'ID du produit au tableau pour dire qu'il faudra remontée les données sur le master
			if ( !$this->_activation && $this->_infoProduct['sdp_id'] != $this->_idMasterProduct )
			{
				$listProductMount[] = $this->_infoProduct['sdp_id'];
			}

                        // maj 28/05/2010 - MPR DE SOurce Availability : Activation de Source availability
                        //                  si le paramètre activation_source_availability_value est à 1
                        // maj 25/06/2010 - MPR Correction du bz 16181 - Initialisation impossible le nom du paramètre était faux "activation_source_availability_value" au lieu de "activation_source_availability"
                        $activation_SA = get_sys_global_parameters("activation_source_availability", 0, $this->_idProduct);

                        if( $activation_SA )
                        {
                            include_once(REP_PHYSIQUE_NIVEAU_0.'reliability/class/SA_Activation.class.php');
                             $sa_activation = new SA_Activation( $this->_idProduct );
                             $_debug_sa = $sa_activation->activation();

                            if ( $this->_debug )
                            {
                                    echo "\n Activation de Source Availability :";
                                    echo "\n".implode("\n",$_debug_sa);
		}
                        }

		}

		// 16:10 24/03/2009 GHX
		// Si on a des produits pour lesquels on doit remonter les données
		if ( count($listProductMount) > 0 )
		{
			foreach ( $listProductMount as $idProduct )
			{
				if ( $this->_debug )
				{
					echo "\n-----------\n";
					echo "Remonter des données du produit {$idProduct} sur le master\n";
				}
				$ctxAct = new ContextActivation($idProduct);
				$ctxAct->setDirectory($this->_directory);
				$ctxAct->activation();
			}
		}

		// 10:21 24/06/2009 GHX
		// Si on a des produits à ajouter dans le backup
		if ( count($activationSlaveBackup) > 0 )
		{
			foreach ( $activationSlaveBackup as $product )
			{
				$this->_db = $this->getConnection($product['sdp_id']);
				$this->_idProduct = $product['sdp_id'];
				$this->_infoProduct = $product;

				// 15/03/2010 BBX : Modification de la requête pour prendre en compte le cas Corporate. BZ 14392
				// $module = $this->_db->getOne("SELECT saai_interface FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (lower(value) = lower(saai_module)) WHERE parameters = 'module'");
				$module = $this->getModuleFromProduct($product['sdp_id']);
				// Fin BZ 14392

				$this->backupTablesContext();
			}
		}
	} // End function mount

	/**
	 * Retourne une instance des éléments du contexte que l'on doit monter à partir de la liste des tables présent dans un sous-contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $tables liste des tables présents dans le sous-contexte
	 * @return array
	 */
	private function getElementsInSubContext ( $tables )
	{
		$elements = array();
                // 09/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$query = "
				SELECT
					sdc_id,
					sdc_label
				FROM
					sys_definition_context,
					sys_definition_context_table_link
				WHERE
					sdc_id = sdctl_sdc_id::text
				GROUP BY
					sdc_id,
					sdc_label
				HAVING
					count(sdctl_sdc_id) = (
							SELECT
								count(sdctl_sdc_id)
							FROM
								sys_definition_context_table,
								sys_definition_context_table_link
							WHERE
								sdct_id = sdctl_sdct_id
								AND sdctl_sdc_id::text = sdc_id
								AND sdct_table IN ('".implode("','", array_keys($tables))."')
							GROUP BY
								sdctl_sdc_id
						)
				ORDER BY
					count(sdctl_sdc_id) ASC
			";

		$db = $this->getConnection("");
		$resultQuery = $db->execute($query);

		if ( $this->_debug )
		{
			echo "\n\n      - Listes des éléments trouvés dans le sous-contexte (par rapport aux fichiers présents et valides) :\n";
		}

		if ( $db->getNumRows() > 0 )
		{
			while ( $row = $db->getQueryResults($resultQuery, 1) )
			{
				if ( $this->_debug )
				{
					echo "          - {$row['sdc_label']}\n";
				}

				$elements[$row['sdc_id']] = new ContextElement($row['sdc_id']);
			}
		}
		else
		{
			if ( $this->_debug )
			{
				echo "          AUCUN !!\n";
			}
		}

		return $elements;
	} // End function getElementsInSubContext

	/**
	 * Charge un élément
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param ContextElement $element élément à charger
	 * @param array &$tablesUsed liste des tables déjà chargées
	 * @param array $tables liste des tables ayant une table temporaire
	 */
	private function mountElement ( $element, &$tablesUsed, $tables )
	{
		$tablesLinks = array();

		foreach ( $element->getTables() as $table )
		{

			if ( $this->_debug )
			{
				echo "\n          - Table : {$table}";
			}

                        // 07/02/2012 NSE DE Astellia Portal Lot2
                        // si la table est à ignorer, on passe à la table suivante
                        // 24/04/2012 BBX
                        // BZ 26944 : on ignore cette table sauf dans la cas d'une activation produit
                        // car il faut que les comptes utilisateurs des slaves puissent remonter sur le master
                        if ( !$this->_activation && in_array($table, $this->_tablesIgnored) )
                        {
                            if ( $this->_debug ) {
                                    echo ' (ignorée)';
                            }
                            continue;
                        }
                        
			// 15:51 23/03/2009 GHX
			// Dans le cas d'un contexte d'activation ...
			if ( $this->_activation )
			{
				// ... on regarde si la table est ignorée ...
				if ( in_array($table, $this->_activationTablesIgnored) )
				{
					// ... si oui on passe à la table suivante
					if ( $this->_debug )
					{
						echo ' (ignorée)';
					}
					continue;
				}
			}

			$nextTable = false;
			// Si la table déjà été traité
			if ( array_key_exists($table, $tablesUsed) )
			{
				if ( $tablesUsed[$table] == 1 )
				{
					if ( $this->_debug )
					{
						echo ' (déjà traitée)';
					}
					// On passe à la table suivante
					$nextTable = true;
				}
			}

			// Si la table n'a pas de table temporaire
			// Normalement la table temporaire doit existée
			// S'il y en n'a pas une c'est qu'il y a un problème quelque part !!!
			if ( !array_key_exists($table, $tables) )
			{
				if ( $this->_debug )
				{
					echo '\n            >> La table temporaire n\'existe pas, impossible de monter les éléments !';
				}
				continue;
			}

			$infoTable = $element->getInfoTable($table);

			// 23/09/2010 NSE bz 18077 : suppression des références aux tables du Gis

			// 16/03/2010 BBX : BZ 14392
			// Si on est sur un Corporate, il faut sauvegarder :
			// => Le NA min paramétré pour chaque famille dans sys_definition_categorie
			// => Les valeurs de module et old_module dans sys_global_parameters
			// De plus, il faut ignorer les tables définies comme à ne pas mettre à jour sur un Corporate.
			$tableQuery = $table;
			if(CorporateModel::isCorporate($this->_idProduct))
			{
				// S'il s'agit d'une table à exclure, on passe
				if($this->isExcludedForCorporate($table)) {
					if($this->_debug) {
						echo "\n			>> La table $table est ignorée car on est sur un Corporate";
					}
					continue;
				}
				// Traitement de sys_definition_categorie
				// Sauvegarde de la colonne network_aggregation_min
				if($table == 'sys_definition_categorie') {
					$this->saveColumn('sys_definition_categorie','rank','network_aggregation_min');
					if($this->_debug) {
						echo "\n			>> Il s'agit d'un produit Corporate. On sauvegarde le NA min";
					}
				}
				// Traitement de sys_global_parameters
				// Sauvegarde de la colonne value pour les valeurs "module" et "old_module"
				if($table == 'sys_global_parameters') {
					$this->saveColumn('sys_global_parameters','parameters','value',"parameters IN ('module','old_module')");
					if($this->_debug) {
						echo "\n			>> Il s'agit d'un produit Corporate. On sauvegarde les valeurs \"module\" et \"old_module\"";
					}
				}
				// Si le produit est en Corporate, il faut modifier les tables
				// sys_definition_network_agregation_bckp et sys_definition_network_agregation_bckp
				// à la place des tables sys_definition_network_agregation et sys_definition_network_agregation
				// on utilise donc désormais la variable $tableQuery pour les requêtes.
				if(in_array($table,Array('sys_definition_network_agregation','sys_definition_time_agregation')))
				{
					$tableQuery = $table.'_bckp';
					if ( $this->_debug ) {
						echo "\n			>> Il s'agit d'un produit Corporate. On va utiliser \"{$tableQuery}\"";
					}
				}
			}
			// Fin BZ 14392

			// Regarde si la table est vide ou non si oui on peut copier directement le contenu
			// de la table temporaire dans la table
			$queryIsEmpty = "
				-- Regarde si la table est vide ou non

				SELECT
					*
				FROM
					{$tableQuery}
				LIMIT 1
			";
			$resultIsEmpty = $this->_db->getRow($queryIsEmpty);

			$selectCaseWhen = preg_replace('/(\w*id_product\w*)/', $this->_patternReplaceIdProduct, $tables[$table]);

			// ******************************** //
			/*
				GESTION D'UN CAS PARTICULIER

				Cas particulier avec la table sys_definition_selecteur et le champ sds_sort_by. En effet, ce champ
				contient l'identifiant du produit. L'expression régulière précédent n'est pas appliqué il faut donc faire le faire de façon
				brutale. De plus comme ce champ contient d'autres informations que l'id produit, il ne faut pas qu'elles soient perdues.
			*/
			// On regarde si on est bien sur la bonne table
			if ( $table == 'sys_definition_selecteur' )
			{
                            // 24/05/2012 NSE bz 27173 : on met à jour id_product
                            $selectCaseWhen = $tables[$table];
				// on regarde aussi si le champ est bien présent
                                // 13/11/2012 BBX
                                // BZ 30465 / 31618 : fixing query for sys_definition_selecteur
				if ( !(strpos($tables[$table], 'sds_sort_by') === false) )
				{
					// On modifie le CASE WHEN
					// On force l'identifiant du produit en text car le champ sds_sort_by est en format text
					$tmpPatternReplaceIdProduct = ' (SELECT id_product FROM sys_pauto_config WHERE id_elem = split_part(sds_sort_by, \'@\', 2) AND id_product IS NOT NULL LIMIT 1)::text ';
					// On modifie le select
					$selectCaseWhen = preg_replace('/(sds_sort_by)/', "CASE WHEN split_part(sds_sort_by, '@', 1)='none' THEN 'none' ELSE split_part(sds_sort_by, '@', 1) || '@' || split_part(sds_sort_by, '@', 2) || '@' ||".$tmpPatternReplaceIdProduct." END", $tables[$table]);
				}
                                // également pour sds_filter_id
				if ( !(strpos($tables[$table], 'sds_filter_id') === false) )
				{
					// On modifie le CASE WHEN
					// On force l'identifiant du produit en text car le champ sds_filter_id est en format text
					$tmpPatternReplaceIdProduct = ' (SELECT id_product FROM sys_pauto_config WHERE id_elem = split_part(sds_filter_id, \'@\', 2) AND id_product IS NOT NULL LIMIT 1)::text ';
					// On modifie le select
					$selectCaseWhen = preg_replace('/(sds_filter_id)/', "CASE WHEN sds_filter_id='' THEN '' ELSE split_part(sds_filter_id, '@', 1) || '@' || split_part(sds_filter_id, '@', 2) || '@' ||".$tmpPatternReplaceIdProduct." END", $selectCaseWhen);
				}
			}
			// ******************************** //

			if ( !is_array($resultIsEmpty) )
			{
				/*
					CAS 1 : On se trouve dans le cas ou la table cible est vide donc on peut directement insérer les données dans celle-ci sans condition particulière
				*/

				// 15:33 11/08/2009 GHX
				// Récupère la liste des colonnes servant de clés primaires ou étrangères pour l'élément courant
				$columnsPkFk = $element->getColumns();
				$tmpPk = $element->getPrimaryKeys($table);

				$pk = null;
				foreach ( $tmpPk as $idColumn )
				{
					$pk = $columnsPkFk[$idColumn];
					break;
				}
				unset($tmpPk);

				// 08:04 24/06/2009 GHX
				// Si on la table a une clé primaire
				if ( $pk != null )
				{
					// Ajout d'une condition supplémentaire
					$whereSelectDefault = '';
					// 17:23 20/11/2009 GHX
					// Ajout de la deuxieme partie de la condition
					if ( $table == $element->getFirstTable() && $element->getSQLWhereSelectDefault() != '' )
					{
						$whereSelectDefault = ' WHERE '.$element->getSQLWhereSelectDefault();
					}

					// Sélectionne tous les éléments qui seront mis à jour
                                        // 03/10/2011 BBX : optimisation de la requête
                                        // BZ 22611
					$querySelect = "
						-- Sélectionne tous les éléments qui seront mis à jour
						-- et ceux qui seront insérés

						SELECT
							ctx.{$pk}
						FROM
							(SELECT {$pk} FROM ctx_{$table} {$whereSelectDefault} ) AS ctx,
							{$tableQuery}
						WHERE
							ctx.{$pk} = {$tableQuery}.{$pk}
						UNION ALL
                                                SELECT
                                                    ctx.{$pk}
                                                FROM (SELECT {$pk} FROM ctx_{$table} {$whereSelectDefault} ) AS ctx
                                                WHERE ctx.{$pk} NOT IN (SELECT {$pk} FROM {$table} );
					";

					$resultSelect = $this->_db->getAll($querySelect);
					$tablesLinks[$pk] = $resultSelect;
				}

				if ( $nextTable )
					continue;

				if ( $this->_debug )
				{
					echo "\n            >> Insert tous les éléments présent dans le sous-contexte car la table est vide";
				}

				$this->_db->execute("INSERT INTO {$tableQuery} ({$tables[$table]}) SELECT {$selectCaseWhen} FROM ctx_{$table} AS ctx");

				if ( $this->_debug )
				{
					echo ' ('.$this->_db->getAffectedRows().')';
				}

				// 05/03/2010 NSE bz 14454
				// on vérifie qu'on a bien inséré tout ce qui était dans la table temporaire
				// nombre d'insertions
				$lignesAffectees = $this->_db->getAffectedRows();
				// nombre d'éléments dans la table temporaire
				$nbEls = $this->_db->getOne("SELECT COUNT(*) FROM ctx_{$table}");
				// s'ils sont différents
				if($nbEls!=$lignesAffectees){
					// All records of the "$1.csv" file were not inserted in the $1" table
					throw new Exception(__T('A_E_CONTEXT_MOUNT_DOES_NOT_INSERT_ALL_ELEMENTS_IN_TABLE', $table));
				}

				$tablesUsed[$table] = 1;
			}
			// 17:07 09/07/2009 GHX
			// Suppression d'une partie de la condition $infoTable['sdctl_select_all'] == 1 ||
			elseif ( $element->isTruncate() ) // Si tous les éléments ont été sélectionné lors de la création du contexte
			{
				/*
					CAS 2 : On est dans le cas on toutes les données sources remplacent les données existantes
				*/

				if ( $nextTable )
					continue;

				if ( $this->_debug )
				{
					echo "\n            >> Tous les élements sont remplacés par ceux présent dans le sous-contexte";
				}

				$this->_db->execute("TRUNCATE TABLE {$tableQuery}");
				$this->_db->execute("INSERT INTO {$tableQuery} ({$tables[$table]}) SELECT {$selectCaseWhen} FROM ctx_{$table} AS ctx");
				if ( $this->_debug )
				{
					echo ' ('.$this->_db->getAffectedRows().')';
				}

				// 05/03/2010 NSE bz 14454
				// on vérifie qu'on a bien inséré tout ce qui était dans la table temporaire
				// nombre d'insertions
				$lignesAffectees = $this->_db->getAffectedRows();
				// nombre d'éléments dans la table temporaire
				$nbEls = $this->_db->getOne("SELECT COUNT(*) FROM ctx_{$table}");
				// s'ils sont différents
				if($nbEls!=$lignesAffectees){
					// All records of the "$1.csv" file were not inserted in the $1" table
					throw new Exception(__T('A_E_CONTEXT_MOUNT_DOES_NOT_INSERT_ALL_ELEMENTS_IN_TABLE', $table));
				}

				$tablesUsed[$table] = 1;
			}
			else
			{
				/*
					CAS 3 : Insertion des données sources sous certaines conditions
				*/

				// Récupère la liste des colonnes servant de clés primaires ou étrangères pour l'élément courant
				$columnsPkFk = $element->getColumns();
				$tmpPk = $element->getPrimaryKeys($table);

				$pk = null;
				foreach ( $tmpPk as $idColumn )
				{
					$pk = $columnsPkFk[$idColumn];
					break;
				}
				unset($tmpPk);

				// Liste des colonnes de la table a traiter
				$columns = explode(',', $tables[$table]);

				// Création de la condition SET pour la requête UPDATE
				$queryUpdateSet = '';
				$queryInsertWhere = '';
				foreach ( $columns as $col )
				{
					// Si c'est pas la clé primaire
					if ( $col!= $pk )
					{
						if ( substr_count('id_product', $col) )
						{
							$tmp = preg_replace('/(\w*id_product\w*)/', $this->_patternReplaceIdProduct, $col);

							$queryUpdateSet .= "{$col} = {$tmp}, ";
						}
						else
						{
							$queryUpdateSet .= "{$col} = ctx.{$col}, ";
						}
					}
				}

				// Si on n'a pas de confition on ne peut pas exécuter les requetes
				if ( $queryUpdateSet == '' )
					throw new Exception(__T('A_E_CONTEXT_MOUNT_NOT_FOUND_FIELD', $table));

				// Si on n'a pas de clé primaire
				if ( $pk == null )
				{
					/*
						CAS 3.1 : la table cible n'a pas de clé primaire de définie
					*/
					if ( $nextTable )
						continue;

					if ( $this->_debug )
					{
						echo "\n            >> 3.1 Remplace les éléments existant par ceux présents dans le contexte et insertion des nouveaux éléments";
					}

                                        // 26/07/2012 BBX
                                        // BZ 27167
                                        // WARNING MERGE : LE BLOC SUPPRIME CI-DESSUS L'A ETE INTENTIONNELEMENT
                                        // NE PAS LE REMETTRE SINON => REGRESSION
					
					// Comme la table n'a pas de clé primaire on crée une condition à partir de la table précédente
					foreach ( $infoTable['fk'] as $fk => $linkKey )
					{
						if ( array_key_exists($columnsPkFk[$linkKey], $tablesLinks)  )
						{
							// 15:41 30/07/2009 GHX
							// On a déplacé la condition ici afin de faire un continue si le tableau est vide
							if ( count($tablesLinks[$columnsPkFk[$linkKey]]) == 0 )
							{
								if ( $this->_debug )
								{
									echo ' (on passe à la table suivante car l\'insertion précédente n\'a retourné aucun résultat)';
								}
								continue 2;
							}
							$queryInsertWhere .= "({$columnsPkFk[$fk]} NOT IN (SELECT {$columnsPkFk[$fk]} FROM {$tableQuery}) OR  {$columnsPkFk[$fk]} IN (";
							foreach ( $tablesLinks[$columnsPkFk[$linkKey]] as $value )
							{
								$queryInsertWhere .= "'".$value[$columnsPkFk[$linkKey]]."', ";
							}
							$queryInsertWhere = substr($queryInsertWhere, 0, -2);
							$queryInsertWhere .= ")) AND ";
						}
					}

					// Si on n'a pas de condition c'est qu'il y a un problème de configuration (ou alors un cas particulier que l'on traite pas encore)
					if ( $queryInsertWhere == '' )
                                        {
                                           if( $table == 'graph_data' )
                                           {

                                                $queries[] = "DELETE FROM sys_pauto_config t
                                                                WHERE  t.oid < ANY (
                                                                        SELECT oid FROM sys_pauto_config t2
                                                                        WHERE  t.oid <> t2.oid
                                                                        AND t.id = t2.id AND t.id_page = t2.id_page
                                                                        AND t.id_elem=t2.id_elem)";
                                                $queries[] = "DELETE FROM sys_pauto_page_name t WHERE  t.oid < ANY (SELECT oid FROM sys_pauto_page_name t2 WHERE  t.oid <> t2.oid AND  t.id_page = t2.id_page and t.page_name=t2.page_name)";
                                           }
                                           else
                                           {
						throw new Exception(__T('A_E_CONTEXT_MOUNT_UNDEFINED_PRIMARY_KEY', $table));
                                           }
                                        }

					$queryInsertWhere = substr($queryInsertWhere, 0, -4);

                                        // 08/11/2011 BBX
                                        // BZ 22157 : Il ne faut supprimer que les éléments liés aux menus du contexte
                                        // Si on va perdre également les menus des Slaves
                                        if($table == 'profile_menu_position') {
                                            $queryInsertWhere = " id_menu IN (SELECT id_menu FROM ctx_menu_deroulant_intranet)";
                                        }
                                        // FIN BZ 22157

					// Désactive le trigger sil y en a un
					$this->_db->execute("SELECT contextEnableTrigger('{$table}', FALSE);");

					// On supprime les éléments à partir de la clé étrangère sur les éléments de la table précédente qui ont été mis à jour
					$queryDelete = "
						-- Supprime les éléments

						DELETE FROM
							{$tableQuery}
						WHERE
							{$queryInsertWhere};
						";

					$this->_db->execute($queryDelete);

					if ( $this->_debug )
					{
						echo ' (-'.$this->_db->getAffectedRows();
					}

					// Résactive le trigger
					$this->_db->execute("SELECT contextEnableTrigger('{$table}', TRUE);");

					// Création de la requête INSERT
					$queryInsert = "
						-- Requete qui insert les éléments

						INSERT INTO
							{$tableQuery} ({$tables[$table]})
						SELECT
							{$selectCaseWhen}
						FROM
							ctx_{$table} AS ctx
						WHERE
							{$queryInsertWhere}
						";

					$this->_db->execute($queryInsert);

					if ( $this->_debug )
					{
						echo ' + '.$this->_db->getAffectedRows().')';
					}
				}
				else
				{
					/*
						CAS 3.2 : la table cible a une clé primaire de définie
					*/

					// on regarde le nombre de ligne par clé primaire si on en a plusieurs on ne fait pas un UPDATE & INSERT
					// mais DELETE & INSERT
					$queryCount = "
						SELECT
							count({$pk})
						FROM
							{$tableQuery}
						GROUP BY
							{$pk}
						HAVING
							count({$pk}) > 1
						";
					$this->_db->execute($queryCount);

					// 12-12-2012 GFS - Correction du BZ#30752
					// On test l'existence de données retournées par la requête SQL
					if ( $this->_db->getNumRows() > 0 )
					{
						/*
							CAS 3.2.1 : la clé primaire n'est pas unique dans la table cible. On supprime donc les éléments donc la clé primaire est présente dans la table temporaire
									pour que ces éléments soient remplacés par ceux présents dans la table temporaires (données sources)
						*/

						if ( $nextTable )
							continue;

						if ( $this->_debug )
						{
							echo "\n            >> 3.2.1 Remplace les éléments existant par ceux présent dans le contexte et insertion des nouveaux éléments";
						}

						// Désactive le trigger sil y en a un
						$this->_db->execute("SELECT contextEnableTrigger('{$table}', FALSE);");
						$queryDelete = "
							-- Supprime les éléments

							DELETE FROM
								{$tableQuery}
							WHERE
								{$pk} IN (SELECT {$pk} FROM ctx_{$table});
							";
						$this->_db->execute($queryDelete);
						if ( $this->_debug )
						{
							echo ' ('.$this->_db->getAffectedRows();
						}
						// Résactive le trigger
						$this->_db->execute("SELECT contextEnableTrigger('{$table}', TRUE);");
					}
					else
					{
						/*
							CAS 3.2.2 : la clé primaire est unique sur la table cible. On fait donc un UPDATE des éléments dont la clé primaire est dans la table temporaire.
						*/                                           
                                            
                                                                                
                                                // 12/04/2012 BBX
                                                // BZ 21721 : on mémorise les homepages
                                                if($tableQuery == 'users')
                                                    $this->backupUsersHomepage();
                                            
						// 16:44 15/04/2009 GHX
						// Ajout d'une condition supplémentaire
						$whereSelectDefault = '';
						// 17:23 20/11/2009 GHX
						// Ajout de la deuxieme partie de la condition
						if ( $table == $element->getFirstTable() && $element->getSQLWhereSelectDefault() != '' )
						{
							$whereSelectDefault = ' WHERE '.$element->getSQLWhereSelectDefault();
						}

						// Sélectionne tous les éléments qui seront mis à jour
                                                // 03/10/2011 BBX : optimisation de la requête
                                                // BZ 22611
						$querySelect = "
							-- Sélectionne tous les éléments qui seront mis à jour
							-- et ceux qui seront insérés

							SELECT
								ctx.{$pk}
							FROM
								(SELECT {$pk} FROM ctx_{$table} {$whereSelectDefault} ) AS ctx,
								{$tableQuery}
							WHERE
								ctx.{$pk} = {$tableQuery}.{$pk}
                                                        UNION ALL
                                                        SELECT
                                                            ctx.{$pk}
                                                        FROM (SELECT {$pk} FROM ctx_{$table} {$whereSelectDefault} ) AS ctx
                                                        WHERE ctx.{$pk} NOT IN (SELECT {$pk} FROM {$table} );
						";

						$resultSelect = $this->_db->getAll($querySelect);
						$tablesLinks[$pk] = $resultSelect;

						if ( $nextTable )
							continue;

						if ( $this->_debug )
						{
							echo "\n            >> Met à jour les éléments déjà existant sinon insertion des nouveaux éléments";
						}

						// Supprime la dernière virgule
						$queryUpdateSet = substr($queryUpdateSet, 0, -2);

                                                // 19/10/2010 BBX
						// DE compteurs clients
						if($table == 'sys_field_reference')
						{
                                                        // 06/02/2014 NSE bz 39467 (dans le cadre de la DE multi-product context)
                                                        // Si la colonne owner n'existe pas, on la crée
                                                        // nécessaire car le code de la Gateway fait appel à cette colonne du slave lors du remontade du contexte.
                                                        if(!$this->_db->columnExists('sys_field_reference', 'owner'))
                                                            $this->_db->execute('ALTER TABLE sys_field_reference ADD COLUMN owner int');
                                                    
							// Récupération des compteurs client
							$clientCounters     = $this->getClientRaws();
							// Récupération des compteurs contexte
							$contextCounters    = $this->getContextRaws();
							// Champs pour détecter les doublons
							$doubleFields       = array('edw_group_table','edw_field_name','edw_target_field_name');
							// Détection des doublons
							$doublesCounters = ArrayTools::conditionnalComparison($clientCounters, $contextCounters, 1, $doubleFields);
							// Si on a des doublons on continue
							if(count($doublesCounters) > 0)
							{
								// Test des connexions aux différents produits
								if(!ProductModel::checkConnection($this->_idProduct,true,false,true))
								{
									// Récupération du label du produit bloquant
									$failedProduct = ProductModel::$lastFailedProduct;
									$failedProductLabel = 'unknown';
									foreach(ProductModel::getActiveProducts() as $product)
										if($product['sdp_id'] == $failedProduct)
											$failedProductLabel = $product['sdp_label'];

									// On lève une exception
									throw new Exception(__T('A_CONTEXT_COULD_NOT_UPDATE_COUNTERS',$failedProductLabel));
								}
								// Logging...
								echo "\n\t\t\t>> ".__T('A_CONTEXT_LOG_UPDATED_COUNTERS')."\n";
								// Traitement de tous les doublons détectés
								foreach($doublesCounters as $oldIdLigne => $newValues)
								{
									// Logging...
									echo "\t\t\t\t - ".RawModel::getLabelFromId($oldIdLigne, Database::getConnection($this->_idProduct))."\n";
									// Récupération du nouvel id
									$newIdLigne = array_shift(array_keys($newValues));
									// Mise à jour de l'identifiant
									RawModel::updateRawId($oldIdLigne,$newIdLigne,$this->_idProduct);
                                    // 04/08/2011 NSE bz 22995 : on effectue la mise à jour également sur les tables temporaires du contexte 
									RawModel::updateRawId($oldIdLigne,$newIdLigne,$this->_idProduct, 1);
									// 24/09/2014 FGD - Bug 43569 - [SUP][T&A OMC NSN UTRAN 5.3.1][AVP#48088][Videotron Canada] Deactivation and loss of history during the upgrade, for raws activated by customer
									// On met à jour l'id_ligne aussi dans le tableau sauvegardant les contextes actifs avant le montage du contexte																
									$this->_counters[$newIdLigne] = $this->_counters[$oldIdLigne];
									unset($this->_counters[$oldIdLigne]);		
								}
							}
						}
						// FIN DE compteurs clients


						// 13:32 04/09/2009 GHX
						// Correction du BZ 11394
						// On recopie dans la table temporaire les valeurs du new_field pour éviter le cas
						// où les raw ou kpi ne sont pas encore déployés
						// 01/04/2010 BBX
						// Correction du BZ 14916
						// On n'utilise la valeur "new_field" en base que pour le cas 1 en base, 0 dans le contexte
						// 08/04/2010 BBX
						// Correction du BZ 14958
						// On utilise la valeur "new_field" en base également quand base = 0, contexte = 1
						if ( $table == 'sys_definition_kpi' || $table == 'sys_field_reference' )
						{
							// 10/02/2011 BBX
							// Il ne faut pas intégrer des compteurs
							// qui n'existent pas sur l'application
							// et qui valent new_field = 2 dans le context
							// BZ 20403
							$queryCleanRawKpi = "DELETE
								FROM ctx_{$table}
								WHERE new_field = 2
								AND id_ligne NOT IN (
									SELECT id_ligne
									FROM $table
									GROUP BY id_ligne)";
							$this->_db->execute($queryCleanRawKpi);
							// FIN BZ 20403

							$queryUpdateNewField = "
								UPDATE
									ctx_{$table}
								SET
									new_field = {$table}.new_field
								FROM
									{$table}
								WHERE
									ctx_{$table}.new_field != {$table}.new_field
									AND ctx_{$table}.id_ligne = {$table}.id_ligne
									AND (({$table}.new_field = 1 AND ctx_{$table}.new_field = 0)
									OR ({$table}.new_field = 0 AND ctx_{$table}.new_field = 1))
								";
							$this->_db->execute($queryUpdateNewField);

							if ( $this->_db->getAffectedRows() > 0 )
							{
								if ( self::$deployRawKpi == false )
									$this->_messages .= '<br />'.__T('A_CONTEXT_INSTALLED_INFO');

								self::$deployRawKpi = true;
							}
						}

                                                // 11/10/2011 BBX
                                                // BZ 22814 : Dans le cas d'une activation produit c'est le master qui doit faire foi pour les comptes utilisateur
                                                if($table != 'users' || !$this->_activation)
                                                {
                                                    // 05/02/2014 NSE bz 39041 : Nettoyage lors du montage du contexte des constituants ajoutés sur l'appli à un élément
                                                    // exemple : les raw/kpi ajoutés à des graphs sont supprimés du graphs lorsque le graph est remonté par le contexte
                                                    if($table == 'sys_pauto_config' || $table == 'sys_pauto_page_name'){
                                                        // Création de la requête DELETE
                                                        $queryDelete = "
                                                                -- Requete qui supprime les constituants ajoutés aux éléments par rapport au contexte monté
                                                                DELETE FROM {$tableQuery}
                                                                WHERE {$tableQuery}.{$pk} NOT IN 
                                                                        (SELECT ctx.{$pk} FROM ctx_{$table} AS ctx)
                                                                AND {$tableQuery}.id_page IN 
                                                                        (SELECT ctx.id_page FROM ctx_{$table} AS ctx)
                                                                ";
                                                        $this->_db->execute($queryDelete);
                                                        if ( $this->_debug )
                                                        {
                                                            echo ' (-'.$this->_db->getAffectedRows();
                                                        }
                                                    }
						// Création de la requête UPDATE
						$queryUpdate = "
							-- Requete qui met à jour les éléments déjà existant

							UPDATE
								{$tableQuery}
							SET
								{$queryUpdateSet}
							FROM
								ctx_{$table} AS ctx
							WHERE
								ctx.{$pk} = {$tableQuery}.{$pk}
							";
						$this->_db->execute($queryUpdate);
                                                }

						if ( $this->_debug )
						{
							echo ' ('.$this->_db->getAffectedRows();
						}
					}

					// ******************************** //
					// 15:54 02/09/2009 GHX
					// Correction du BZ 11345
					if ( $table == 'sys_definition_kpi' || $table == 'sys_field_reference' )
					{
						$queryUpdateNewField = "
							UPDATE
								ctx_{$table}
							SET
								new_field = 1
							WHERE
								on_off = 1
								AND id_ligne NOT IN (SELECT id_ligne FROM {$table})
							";
						$this->_db->execute($queryUpdateNewField);

						if ( $this->_db->getAffectedRows() > 0 )
						{
							if ( self::$deployRawKpi == false )
								$this->_messages .= '<br />'.__T('A_CONTEXT_INSTALLED_INFO');

							self::$deployRawKpi = true;
						}
					}
					// ******************************** //

					/*
						CAS 3.2.1 &	CAS 3.2.2 : insertion des nouveaux éléments
					*/
					// Création de la requête INSERT
					$queryInsert = "
						-- Requete qui insert les nouveaux éléments

						INSERT INTO
							{$tableQuery} ({$tables[$table]})
						SELECT
							{$selectCaseWhen}
						FROM
							ctx_{$table} AS ctx
						WHERE
							{$pk} NOT IN (SELECT {$pk} FROM {$tableQuery})
						";

                                        // 14/01/2011 BBX
					// Dans le cas d'une activation, il faut récupérer les champs
					// Ajoutés dans la table users
					// BZ 19783, 20113
					if($this->_activation && $table == 'users')
					{
						// Ajout du retour des lignes insérées
						$queryInsert .= "RETURNING *";

						// Exécution de la requête
						$result = $this->_db->execute($queryInsert);

						// Récupération des lignes insérées
						while($row = $this->_db->getQueryResults($result,1)) {
							$this->_usersFromSlave[$row['id_user']] = $row;
						}
					}
					else
					{
						// Exécution de la requête
						$this->_db->execute($queryInsert);
					}

					if ( $this->_debug )
					{
						echo ' + '.$this->_db->getAffectedRows().')';
					}
				}

				$tablesUsed[$table] = 1;
			}

			// 16/03/2010 BBX : BZ 14392
			// Si on est sur un Corporate, il faut restaurer valeurs sauvées précédemment
			// => Le NA min paramétré pour chaque famille dans sys_definition_categorie
			// => Les valeurs de module et old_module dans sys_global_parameters
			if(CorporateModel::isCorporate($this->_idProduct))
			{
                            // Traitement de sys_definition_categorie
                            // Sauvegarde de la colonne network_aggregation_min
                            if($table == 'sys_definition_categorie'){
                                $this->restoreColumn('sys_definition_categorie','network_aggregation_min');
                                
                                // 05/06/2012 NSE bz 27152 : Création des nouvelles familles sur le Corporate
                                
                                // On reconstruit sys_definition_group_table_time 
                                CorporateModel::buildGroupTableTime(CorporateModel::getTaMin());

                                // Insertion des nouvelles NA
                                $query = "  INSERT INTO sys_definition_network_agregation
						SELECT * FROM sys_definition_network_agregation_bckp WHERE family NOT IN (SELECT family FROM sys_definition_network_agregation);";
                                $this->_db->execute($query);
                                 
                                // Rebuild de la table sys_definition_group_table_network
                                NaModel::buildGroupTableNetwork($this->_idProduct);    
                                
                                // Mise à jour de sys_definition_corporate
                                $query = "SELECT 
                                    gt.id_group_table AS id_group_table, 
                                    c.network_aggregation_min::text AS na_min, 
                                    n.agregation::text AS na_axe3_min, 
                                    t.time_agregation::text AS ta_min,
                                    NULL::text AS super_network
                                FROM 
                                    sys_definition_categorie c, 
                                    sys_definition_group_table_time t, 
                                    sys_definition_gt_axe gt
                                LEFT JOIN sys_definition_network_agregation_bckp n 
                                    ON (n.family = gt.family AND axe = 3 AND agregation_level = 1)
                                WHERE 
                                    c.family = gt.family
                                    AND t.id_source = -1
                                    AND t.data_type = 'raw'
                                    AND t.id_group_table = gt.id_group_table
                                    AND t.id_group_table NOT IN (SELECT id_group_table FROM sys_definition_corporate)";
                                $result = $this->_db->execute($query);

                                while($array = $this->_db->getQueryResults($result,1)) {
                                    $queryInsert = "INSERT INTO sys_definition_corporate
                                    (id_group_table,na_min,na_min_axe3,ta_min,super_network)
                                    VALUES (
                                            ".$array['id_group_table'].",
                                            '".$array['na_min']."',
                                            ".(empty($array['na_axe3_min']) ? "NULL" : "'".$array['na_axe3_min']."'").",
                                            '".$array['ta_min']."',
                                            ".(empty($array['super_network']) ? "NULL" : "'".$array['super_network']."'").")";
                                    $this->_db->execute($queryInsert);
                                    echo "                Ajout d'une nouvelle famille sur le Corporate (id_group_table : ".$array['id_group_table'].")\n";
                                }         
                            }
                                
                                
                                
                                
                                
				// Traitement de sys_global_parameters
				// Sauvegarde de la colonne value pour les valeurs "module" et "old_module"
				if($table == 'sys_global_parameters')
					$this->restoreColumn('sys_global_parameters','value');
                // 21/04/2011 NSE DE Non unique Labels
                // En Corporate, les valeurs de la colonne uniq_label de la table
                // sdnab doivent être recopiées dans la table sdna pour chaque NA
                // 04/05/2011 NSE bz 22040 : valeurs de la table non recopiées.
                if($table == 'sys_definition_network_agregation'){
                    $queryUpdateUniqLabel = "UPDATE sys_definition_network_agregation sdna
                        SET uniq_label = sdnab.uniq_label
                        FROM sys_definition_network_agregation_bckp sdnab
                        WHERE sdna.family=sdnab.family
                        AND sdna.agregation=sdnab.agregation";
                    $this->_db->execute($queryUpdateUniqLabel);
                }
                // Fin 21/04/2011 NSE DE Non unique Labels
                                
                // En Corporate, les valeurs de la colonne link_to_ne de la table
                // sdnab doivent être recopiées dans la table sdna pour chaque NA
                // 07/10/2013 MGO bz 36277 : valeurs de la table non recopiées.
                if($table == 'sys_definition_network_agregation'){
                    $queryUpdateLinkToNe = "UPDATE sys_definition_network_agregation sdna
                        SET link_to_ne = sdnab.link_to_ne
                        FROM sys_definition_network_agregation_bckp sdnab
                        WHERE sdna.family=sdnab.family
                        AND sdna.agregation=sdnab.agregation";
                    $this->_db->execute($queryUpdateLinkToNe);
                }
               }
			// Fin BZ 14392

			// 23/09/2010 NSE bz 18077 : suppression des références aux tables du Gis
     
                // 23/02/2012 NSE DE Astellia Portal Lot2
                // On transmet les profiles au Portail
                // 12/03/2012 NSE bz 26292 : si on n'est pas sur une installation
                if($table == 'profile' && $this->_contextMode==2 ){
                    // 16/03/2012 NSE bz 26419 : ajout message log + include
                    echo "\n            >> Transmission des Profiles au Portail.\n";
                    include_once REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc';
                    $p = new ProductModel('');
                    $product = $p->getValues();
                    $guid_hexa=APPLI_GUID_HEXA;
                    $guid_appli=APPLI_GUID_NAME;
                    // 22/11/2012 NSE bz 29927: 2 'd' à address
                    $appli_path=$product['sdp_ip_address'].'/'.$product['sdp_directory'];
                    $casIp=CAS_SERVER;
                    include(REP_PHYSIQUE_NIVEAU_0.'scripts/generatePAAXml.php');
                }
            }
	} // End function mountElement

	/**
	 * Retourne les informations du sous-contexte que l'on doit monter en fonction du type de produit, retourne FALSE si on peut monter aucun sous-contexte
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $module type de produit sur lequel on veut monter un contexte
	 * @return array
	 */
	private function selectSubcontext ( $module )
	{
		foreach ( $this->_subContext as $subcontext )
		{
			// 11:44 23/03/2009 GHX
			// Si c'est on contexte d'activation il n'y a qu'un seul sous-contexte et il va obligatoirement sur le produit master même si le code produit n'est pas le bon
			if ( $subcontext['module'] == $module || $this->_activation )
				return $subcontext;
		}

		return false;
	} // End function selectSubcontext

	/**
	 * Sauvegarde les tables du contextes dans un fichier SQL
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function backupTablesContext ()
	{
            // la sauvegarde doit être effectuée à partir de la base de données du produit sauvegardé et non de celle du master
		$db = $this->getConnection($this->_idProduct);

		// Création du répertoire de backup
		$ctxname = basename($this->_context, '.tar.bz2');
		// Si la sauvegarde existe on n'en fait pas d'autre
		if ( file_exists("{$this->_directory}backup_before_mount_context_{$ctxname}.tar.bz2") )
		{
			echo " (déjà présent)";
			return;
		}

		// 22/03/2010 NSE bz 14790 : on ajoute un test pour vérifier que le contexte que l'on va sauvegarder n'est pas vide
		$result = $db->execute("SELECT COUNT(*) as nb FROM users");
		$row = $db->getQueryResults($result, 1);
		if($row['nb']==0){
			echo " (contexte courant vide : pas de sauvegarde)";
			return;
		}
		// 22/03/2010 NSE bz 14790 fin

		$dir = "{$this->_directory}backup_before_mount_context_{$ctxname}/";
		if ( !is_dir($dir) )
		{
			mkdir($dir, 0777);
		}

		// Création du fichier de backup
		$filebackup = "{$dir}product_{$this->_idProduct}.sql";
		$comment = "
			--
			-- Sauvegarde des tables du contexte avant de monter le contexte '".$this->_context."'
			-- Genere le : ".date('Y-m-d H:i:s')."
			--
			-- Produit : ".$this->_infoProduct['sdp_label']."
			-- Identifiant du produit : ".$this->_infoProduct['sdp_id']."
			-- Repertoire : ".$this->_infoProduct['sdp_directory']."
			-- Base de donnees : ".$this->_infoProduct['sdp_db_name']."
			--
			";
		file_put_contents($filebackup, str_replace("\t", "", $comment), FILE_APPEND);

        // Récupération des tables contexte + sys_versioning
		// 18/11/2013 GFS - Bug 37891 - [SUP][T&A Gateway][Econet Zimbabwe][AVP 40118][Context] : Backup before mount context is corrupted
        $result = $db->execute("SELECT sdct_table FROM sys_definition_context_table WHERE sdct_table != 'sys_definition_topology_replacement_rules'
                    UNION ALL
                    SELECT 'sys_versioning' as sdct_table");                    
		while ( $row = $db->getQueryResults($result, 1) )
		{
			$table = $row['sdct_table'];
			$str =  "\nTRUNCATE TABLE {$table};\n";
			file_put_contents($filebackup, $str, FILE_APPEND);

                    // 12/12/2011 BBX
                    // BZ 24572 : utilisation de la méthode dumpTable pour dumper
                    // Afin d'éviter les problème de version de pg_dump
                    file_put_contents($filebackup, $db->dumpTable($table, false, true), FILE_APPEND);
		}

                // 15/11/2012 BBX
                // BZ 30115 : suppression du truncate

		// 12:18 18/06/2009 GHX
		// Ajout de la mise à jour de la séquence
		$str =  "\nSELECT setval('public.sys_versioning_id_seq', (SELECT max(id)+1 FROM sys_versioning), true);\n";
		file_put_contents($filebackup, $str, FILE_APPEND);

	} // End function backupTablesContext

	/**
	 * Vérifie la structure du fichier avec la table de données
	 *	Supprimer les colonnes qui ne sont plus utilisé
	 *	Supprimer l'entête
	 *
	 *	18:27 19/06/2009 GHX
	 *		- Prise en compte qu'il peut manquer des colonnes dans le fichier
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $file nom du fichier à charger
	 * @param string $directory répertoire dans lequel se trouve le fichier
	 * @return string
	 */
	private function checkStruture ( $file, $directory )
	{
		// Vérifie qu'on a bien l'extention .csv
		if ( substr_count($file, '.csv') == 0 )
			throw new Exception(__T('A_E_CONTEXT_MOUNT_UNKOWN_EXTENSION', $file));

		$table = basename($file, '.csv');

		if ( $this->_debug )
		{
			echo "\n\n      - Vérification de la structure de la table : {$table} (par rapport aux colonnes présentes dans le fichier)";
		}

		$columnsInTable = $this->getColumns($table);

		// Récupère l'entete du fichier
		$cmdGetFirstLine = sprintf(
				'sed -n \'1p\' "%s%s"',
				$directory,
				$file
			);
		exec($cmdGetFirstLine, $firstLine);
		$columnsInFile = explode("\t", trim($firstLine[0]));

		if ( empty($columnsInFile[0]) )
		{
			if ( $this->_debug )
			{
				echo "\n        >> Le fichier est vide !!";
			}
			return false;
		}

		// Récupère les colonnes présentes dans le fichier et qui n'existe pas en base
		$diff = array_diff($columnsInFile, $columnsInTable);
		if ( count($diff) > 0 )
		{
			if ( count($diff) == count($columnsInFile) )
			{
				if ( $this->_debug )
				{
					echo "\n        >> Le fichier est ignoré car aucunes colonnes ne correspondent à celles présentes en base !!";
				}
				return false;
			}

			if ( $this->_debug )
			{
				echo "\n        >> Liste des colonnes qui n'existe pas dans la table : ".implode(', ', $diff)." (supprimées du fichier)";
			}

			$columns = array_diff_assoc($columnsInFile, $diff);

			$tmpCut = '';
			foreach ( array_keys($columns) as $columnIndex )
			{
				$tmpCut .= ($columnIndex+1).',';
			}
			// Supprime la dernière virgule
			$tmpCut = substr($tmpCut, 0, -1);

			$cmd1 = sprintf(
					'cut -f%s "%s%s" >> "%s%s_tmp"; mv -f "%s%s_tmp" "%s%s"',
					$tmpCut,
					// Fichier a découpé
					$directory,
					$file,
					// Fichier temp
					$directory,
					$file,
					// Fichier temp que l'on renome
					$directory,
					$file,
					// Fichier découpé finale (= fichier temp renomé)
					$directory,
					$file
				);
			exec($cmd1);

			if ( $this->_debug & 2 )
			{
				echo "\n          {$cmd1}";
			}

			$columnsInFile = $columns;
		}

		// 17:45 19/06/2009 GHX
		// On ne prend en compte que les colonnes communes entre le fichier et la table
		// Cas où il manque des colonnes dans le fichier
		$diff2 = array_diff($columnsInTable, $columnsInFile);
		$columnsInFile = array_intersect($columnsInFile, $columnsInTable);

		if ( $this->_debug )
		{
			if ( count($columnsInFile) != count($columnsInTable) )
			{
				echo "\n        >> Liste des colonnes qui n'existe pas dans le fichier : ".implode(', ', $diff2);
			}
		}

		// Supprime l'entete du fichier
		$cmd2 = sprintf(
				'sed -i -n \'1!p\' "%s%s"',
				$directory,
				$file
			);
		exec($cmd2);

		return implode(',', $columnsInFile);
	} // End function checkStruture

	/**
	 * Insertion des données d'un fichier dans une table temporaire et retourne le nom de la table associé
	 *
	 *	18:26 19/06/2009 GHX
	 *	 	- Suppression des contraintes NOT NULL sur les tables temporaires
	 *	 	- Modif pour prendre en compte les erreurs PSQL
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $file nom du fichier à charger
	 * @param string $directory répertoire dans lequel se trouve le fichier
	 * @param string $header entête du fichier
	 * @return string
	 */
	private function loadDataInTableTemp ( $file, $directory, $header )
	{
		$table = basename($file, '.csv');
		$tableTmp = "ctx_{$table}";

		/*
			1. Création de la table temporaire
		*/
		$queryCreateTableTmp = sprintf(
				'CREATE TABLE %s (LIKE %s EXCLUDING CONSTRAINTS)',
				$tableTmp,
				$table
			);
		// Création de la commande linux qui crée la table temporaire
		$cmdCreateTableTmp = sprintf(
				'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s"',
				$this->_infoProduct['sdp_db_password'],
				$this->_infoProduct['sdp_db_login'],
				$this->_infoProduct['sdp_db_name'],
				$this->_infoProduct['sdp_ip_address'],
				$queryCreateTableTmp
			);

		if ( $this->_debug )
		{
			echo "\n      - Création de la table temporaire : {$tableTmp} [{$cmdCreateTableTmp}]";
		}

		// Exécute la commande
		exec($cmdCreateTableTmp, $r, $error);
		// S'il y a une erreur, on lève une exception
		if ( $error )
		{
			// Afin de mieux visualiser l'erreur SQL, en mode débug, on l'exécute via la classe DataBaseConnection pour récupérer l'erreur
			if ( $this->_debug )
			{
				$db = $this->getConnection($this->_idProduct);
				$db->execute($queryCreateTableTmp);
				echo "\n".$db->getLastError();
			}
			throw new Exception (__T('A_E_CONTEXT_MOUNT_UNABLE_CREATE_TABLE_TEMP', $this->_infoProduct['sdp_label']));
		}

		// 18:22 19/06/2009 GHX
		// Suppression des contraintes NOT NULL sur les colonnes de la table temporaire
		$queryDropConstraintNotNull = "ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL;";
		$db = $this->getConnection($this->_idProduct);
		foreach ( $this->getColumns($table) as $column )
		{
			$db->execute(sprintf($queryDropConstraintNotNull, $tableTmp, $column));
		}

		/*
			2. Chargement des données du fichier dans la table temporaire
		*/
		// Création de la requête SQL qui charge les données du fichier dans la table temporaire
		$queryLoadFile = sprintf(
					"COPY %s (%s) FROM stdin NULL '\"'\"''\"'\"';",
					$tableTmp,
					$header
				);
		// Création de la commande linux qui crée la table temporaire
		// >>> on fait un awk avec un begin pour renvoyer la requête SQL avant le contenu du fichier (ca permet d'éviter de modifier le fichier et de gagner une étape)
		// 17:46 19/06/2009 GHX
		// Modification de la commande afin de pouvoir récupérer les messages d'erreur PSQL qui ne sont pas récupérer par la commande exec()
		$cmdLoadFile = sprintf(
				'cat "%s%s" | awk \'BEGIN{print "%s"}{print $0}\' | env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s 2>&1',
				$directory,
				$file,
				$queryLoadFile,
				$this->_infoProduct['sdp_db_password'],
				$this->_infoProduct['sdp_db_login'],
				$this->_infoProduct['sdp_db_name'],
				$this->_infoProduct['sdp_ip_address']
			);

		if ( $this->_debug )
		{
			echo "\n      - Chargement des données : {$file} [{$cmdLoadFile}]";
		}

		// Exécute la commande
		exec($cmdLoadFile, $r2, $error2);

		// 17:46 19/06/2009 GHX
		// On regarde si on a des erreurs dans le tableau de resultat
		$r2 = implode("\n", $r2);

		// S'il y a une erreur, on lève une exception
		if ( $error2 )
		{
			throw new Exception (__T('A_E_CONTEXT_MOUNT_UNABLE_LOAD_DATA_IN_TABLE_TEMP', $this->_infoProduct['sdp_label']));
		}
		elseif ( !(strpos($r2, 'ERROR:') === false) )
		{
			if ( $this->_debug )
			{
				echo "\n\n".$r2."\n";
			}
			throw new Exception (__T('A_E_CONTEXT_MOUNT_UNABLE_LOAD_DATA_IN_TABLE_TEMP', $this->_infoProduct['sdp_label']));
		}

		// 11:17 18/06/2009 GHX
		// Ajout du nombre de lignes insérées dans la table temporaire
		if ( $this->_debug )
		{
			/*
				Compte le nombre d'élément de la table temporaire
			*/
			$querySelectCountTableTmp = sprintf(
					'SELECT COUNT(*) FROM %s',
					$tableTmp
				);
			$db = $this->getConnection($this->_idProduct);
			$nbEls = $db->getOne($querySelectCountTableTmp);
			echo " => {$nbEls} ligne".($nbEls > 0 ? 's' : '');
		}

		return $table;
	} // End function loadDataInTableTemp

	/**
	 * Extrait un sous-contexte et vérifie son contenu
	 *
	 *	15/04/2009 GHX
	 *		- On force le code produit en entier
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $directory chemin dans lequel se trouve le sous contexte
	 * @param string $subContext nom du sous-contexte
	 */
	private function extractSubContext ( $directory, $subContext )
	{
		// Vérifie qu'on a bien l'extention .tar.bz2
		if ( substr_count($subContext, '.tar.bz2') == 0 )
			throw new Exception(__T('A_E_CONTEXT_MOUNT_SUBCONTEXT_UNKOWN_EXTENSION', $subContext));

		// Récupère le nom du sous-contexte sans son extention
		$basename = basename($subContext, '.tar.bz2');
		// Création du dossier dans lequel sera extrait le sous-contexte
		$rep = "{$directory}{$basename}/";
		mkdir($rep, 0777);
		// Extrait le sous-contexte
		$cmdUntar = sprintf('tar xfjv "%s%s" -C "%s"',
				$directory,
				$subContext,
				$rep
			);
		exec($cmdUntar, $r, $error);
		if ( $error )
			throw new Exception(__T('A_E_CONTEXT_MOUNT_SUBCONTEXT_UNABLE_EXTRACT', $subContext));

		// Supprime l'archive du sous-contexte, on en n'a plus besoin
		$cmdDelete = sprintf('rm -f "%s%s"',
				$directory,
				$subContext
			);
		exec($cmdDelete);

		if ( $this->_debug )
		{
			echo "\n    - Vérification du md5 : ";
		}

		// Vérifie la présence du fichier md5
		if ( file_exists("{$rep}md5") )
		{
			// Si oui on vérifie s'il correspond bien au md5 du nom du sous-contexte
			$md5InFile = file_get_contents("{$rep}md5");
			$md5 = md5($basename);

			if ( $this->_debug )
			{
				echo "{$md5InFile} (fichier md5) == {$md5} (calculé) ? ";
			}

			if ( $md5InFile !== $md5 )
			{
				if ( $this->_debug )
				{
					echo "NOK";
				}
				throw new Exception(__T('A_E_CONTEXT_MOUNT_SUBCONTEXT_INVALID', $subContext));
			}

			if ( $this->_debug )
			{
				echo "OK";
			}
		}
		elseif ( $this->_debug )
		{
			echo "inhibé (fichier md5 non trouvé)";
		}

		if ( $this->_debug )
		{
			echo "\n    - Information sur le sous-contexte : ";
		}
		// Récupère des informations à partir du nom du sous-contexte
		if ( ereg ("^.*_([0-9]*)$", $basename, $regs) )
		{
			if ( $this->_debug )
			{
				echo "\n      - module : {$regs[1]}";
			}
			// 15/04/2009 GHX
			// On force le code produit en entier
			$this->_subContext[] = array(
					'name' => $basename,
					'module' => (int)$regs[1]
				);
		}
		else
		{
			if ( $this->_debug )
			{
				echo "\n      Impossible de récupérer le type de produit\n";
			}
			throw new Exception(__T('A_E_CONTEXT_MOUNT_SUBCONTEXT_INVALID_FORMAT', $subContext));
		}
                // 2014/01/21 bz 39100
                // On n'accepte plus le montage d'un contexte contenant un sous-contexte Gateway car il s'agit de l'ancien format
                // sauf si le contexte ne contient que ce sous-contexte, auquel cas c'est un contexte d'activation
                if ( $regs[1] == 9999 && count($this->_subContext) > 1 ){
                    if ( $this->_debug )
                    {
                        echo "\n      Sous-contexte Gateway refusé\n";
                    }
                    throw new Exception(__T('A_E_CONTEXT_MOUNT_SUBCONTEXT_INVALID', $subContext));
                }
	} // End function extractSubContext

	/**
	 * Lance le déploiement pour créer les nouveaux créer les tables pour les nouveaux niveaux d'aggrégations
	 * Cette fonction est uniquement si le fichier "sys_definition_network_agregation.csv" est présent dans le contexte
	 *
	 * 	29/07/2009 GHX
	 *		- Ajout de la fonction pour le correction du BZ 10858
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function launchDeploy ( $value )
	{
		// 06/08/2009 - MPR :
		//	- Correction du bug 10945 : Tous les niveaux d'agrégation 3ème axe
		// Mise à jour des tables de déploiement
		$queries[] = "UPDATE sys_definition_group_table
					  SET raw_deploy_status = {$value},
						  kpi_deploy_status = {$value}
				";
		$queries[] = "UPDATE sys_definition_group_table_network
					  SET deploy_status = 1
				";
		$this->_db->execute( implode(";",$queries) );

		// Création de la commande pour lancer le déploiement en fonction du produit
		$cmd = 'php -q /home/'.$this->_infoProduct['sdp_directory'].'/scripts/deploy.php';

		if ( $this->_debug )
		{
			echo "\n\n >> Lancement du script deploy.php [{$cmd}]";
		}

		// Si l'IP du produit est le même que celui du serveur sur lequel on est ...
		if ( get_adr_server() == $this->_infoProduct['sdp_ip_address'] )
		{
			// ... on lance la commande en local
			if ( $this->_debug )
			{
				echo " : en local\n";
			}

			exec($cmd);
		}
		else
		{
			// ... sinon on l'exécute via SSH
			if ( $this->_debug )
			{
				echo " : via SSH (IP : ".$this->_infoProduct['sdp_ip_address']." / LOGIN : ".$this->_infoProduct['sdp_ssh_user'].")\n";
			}

			// On include la classe de connexion SSH juste ici car on n'en a pas besoin tout le temps
			include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';

			try
			{
				//31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
				$ssh = new SSHConnection($this->_infoProduct['sdp_ip_address'], $this->_infoProduct['sdp_ssh_user'], $this->_infoProduct['sdp_ssh_password'], $this->_infoProduct['sdp_ssh_port']);
				$ssh->exec($cmd);
			}
			catch ( Exception $e )
			{
				if ( $this->_debug )
				{
					echo "\n\n  ERREUR SSH : ".$e->getMessage()."\n\n";
				}
			}
		}
	} // End function launchDeploy

	/**
	 * Déploiement des tables BH si nécessaire
	 *
	 * @author GHX
	 * @version CB 5.0.0.07
	 * @since CB 5.0.0.07
	 */
	private function launchDeployBH ()
	{
		$query = "
			SELECT
				*
			FROM
				sys_definition_group_table_time
			WHERE
				time_agregation IN (SELECT agregation FROM sys_definition_time_agregation WHERE bh_type='bh')
			";
		$result= $this->_db->execute($query);
		// Si les tables BH sont déjà déploiées on ne fait rien
		if ( $this->_db->getNumRows() > 0 )
			return;

		$query = "SELECT * FROM sys_definition_time_bh_formula";
		$result= $this->_db->execute($query);
		// Si on n'a pas de paramétrage BH on ne déploie pas les tables
		if ( $this->_db->getNumRows() == 0 )
			return;

		// Création de la commande pour lancer le déploiement de la BH en fonction du produit
		$cmd = 'php -q /home/'.$this->_infoProduct['sdp_directory'].'/scripts/bh_management.php "deploy" "all" "'.$this->_idProduct.'"';

		if ( $this->_debug )
		{
			echo "\n\n >> Lancement du script bh_management.php [{$cmd}]";
		}

		// Si l'IP du produit est le même que celui du serveur sur lequel on est ...
		if ( get_adr_server() == $this->_infoProduct['sdp_ip_address'] )
		{
			// ... on lance la commande en local
			if ( $this->_debug )
			{
				echo " : en local\n";
			}

			exec($cmd);
		}
		else
		{
			// ... sinon on l'exécute via SSH
			if ( $this->_debug )
			{
				echo " : via SSH (IP : ".$this->_infoProduct['sdp_ip_address']." / LOGIN : ".$this->_infoProduct['sdp_ssh_user'].")\n";
			}

			// On include la classe de connexion SSH juste ici car on n'en a pas besoin tout le temps
			include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';

			try
			{
				//31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
				$ssh = new SSHConnection($this->_infoProduct['sdp_ip_address'], $this->_infoProduct['sdp_ssh_user'], $this->_infoProduct['sdp_ssh_password'],$this->_infoProduct['sdp_ssh_port']);
				$ssh->exec($cmd);
			}
			catch ( Exception $e )
			{
				if ( $this->_debug )
				{
					echo "\n\n  ERREUR SSH : ".$e->getMessage()."\n\n";
				}
				return;
			}
		}

		$this->_messages .= '<br />'.__T('A_CONTEXT_INSTALLED_INFO_BH_DEPLOYED', $this->_infoProduct['sdp_label']);
	} // End function launchDeployBH

	/**
	 * Pour tous les graphes et dashboards ont vérifie s'ils sont associés à un utilisateur si oui on regarde si l'utilisateur est présent dans la table user. Si c'est pas le cas
	 * on supprime l'association du graphe ou dashboard avec cet utilisateur, et on l'associe on compte admin qui monte le contexte
	 *
	 * @author GHX
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 */
	private function checkIdUserInGraphAndDashboard ()
	{
		$query = "
			SELECT
				*
			FROM
				sys_pauto_page_name
			WHERE
				id_user IS NOT null
				AND (page_type = 'gtm' OR page_type='page')
				AND id_user NOT IN (SELECT id_user FROM users)
			";

		$result = $this->_db->execute($query);

		if ( $this->_db->getNumRows() > 0 )
		{
			$droit = getClientType($_SESSION['id_user']);

			$queryUpdate = "
				UPDATE
					sys_pauto_page_name
				SET
					id_user = null,
					share_it = 0,
					droit = '{$droit}'
				WHERE id_page IN (
								SELECT
									id_page
								FROM
									sys_pauto_page_name
								WHERE
									id_user IS NOT NULL
									AND (page_type = 'gtm' OR page_type='page')
									AND id_user NOT IN (SELECT id_user FROM users)
							)
				";
			$this->_db->execute($queryUpdate);

			if ( $this->_debug )
			{
				$user_info = getUserInfo($_SESSION['id_user']);
                                // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de user_prenom
				$user = $user_info['username'].' [login : '.$user_info['login'].']';
				echo "\n";
				while ( $row = $this->_db->getQueryResults($result, 1) )
				{
					if ( $row['page_type'] == 'page' )
					{
						echo "\n- The dashboard '";
					}
					else
					{
						echo "\n- The graph '";
					}
					echo $row['page_name']."' is now associated at user ".$user;
				}
				echo "\n";
			}
		}

	} // End function checkIdUserInGraphAndDashboard

	/**
	 * Vérifie que les menus parents des dashboars existent bien
	 *
	 *	25/08/2009 GHX : ajout de la fonction
	 *	22/09/2009 GHX : bz11355 (réécriture de la fonction)
	 *	29/03/2010 NSE : bz14648 (réécriture de la fonction) La fonction permet
     *                   de conserver le menu parent. On ne se base plus sur le
     *                   libellé du menu mais sur l'id_menu
	 *
     *  21/05/2010 MPR : bz15560 Réécriture de la fonction. La fonction permet
     *                   de fixer l'id_menu_parent de tous les menus dashboard
     *                   racine :
     *                      -> En monoproduit à 0
     *                      -> En multiproduit, on récupère id menu racine du
     *                         produit et on fixe l'id_menu_parent de tous les 
     *                         menus dashboard avec celui-ci
     *
     *
	 * @version CB 5.0.0.06
	 * @since CB 5.0.0.06
	 */
	private function checkMenuDashboard()
	{
		echo "\n>>> Mise à jour des Menus et profils";

		// Sélection des menus racines (id_parent = 0) du nouveau contexte
		$query = "SELECT id_menu,libelle_menu,id_menu_parent
                  FROM ctx_menu_deroulant_intranet
				  WHERE
                    id_menu_parent='0'
                    AND id_page is null
                    AND droit_affichage='customisateur'
                    AND is_profile_ref_user=1
                    AND lien_menu is null
                    AND id_menu ilike 'menu%'";
		$results = $this->_db->getAll($query);

        // On recherche l'id parent uniquement si on est en multiproduit (inutile en mono id_menu_parent = 0)
        $Products = getProductInformations();
        if ( count($results) > 0 && count($Products) > 1 )
		{
            // Récupère l'id du menu racine du produit concerné
            // L'id parent de chaque menus racine du contexte sera celui-ci
            // 12/01/2011 OJT : bz25417, problème de cast sur id_menu_parent
            $querySelect = "SELECT id_menu
                            FROM
                                menu_deroulant_intranet,
                                sys_definition_product
                            WHERE id_menu_parent = '0'
                            AND id_page is null
                            AND droit_affichage='customisateur'
                            AND libelle_menu = '{$this->_infoProduct['sdp_label']}'
                            LIMIT 1";

            $res = $this->_db->getOne($querySelect);
            $id_menu_parent = ($res != "") ? $res : 0;

            // On récupère les id des menus dans une liste
            $lst_menus = array();
			foreach ( $results as $menu )
			{
               $lst_menus[] = $menu['id_menu'];
            }

            // On met à jour les ID menu parents des menus dashboards pour les faires correspondre à l'ID qui se trouve en base
            // On n'utilise pas les identifiants du contexte pour pas les faires remonter dans le menu horizontal (en multiprod)
            // on met à jour l'id_menu_parent dans le contexte à appliquer
            $queryUpdate = "UPDATE ctx_menu_deroulant_intranet nouveau
                            SET id_menu_parent = '".$id_menu_parent."'
                            WHERE id_menu IN ('".implode("','",$lst_menus)."')";

			$this->_db->execute($queryUpdate);

            // 09/04/2010 BBX : On doit également mettre à jour les profils. BZ 14957
            // Mise à jour des profils
            $queryUpdate = "UPDATE ctx_profile_menu_position nouveau
                            SET id_menu_parent = '".$id_menu_parent."'
                            WHERE id_menu IN ('".implode("','",$lst_menus)."')";
            $this->_db->execute($queryUpdate);
        }
	} // End function checkMenuDashboard

	/**
	 * Check les profils users pour vérifier si tous les dashboards sont biens dans les profils
	 *
	 *	22/09/2009 GHX
	 *		- Ajout de la fonction pour corriger le BZ 11355
	 *
	 * @author GHX
	 * @version CB 5.0.0.09
	 * @since CB 5.0.0.09
	 */
	private function checkProfiles ()
	{
		$profileMenus = MenuModel::getUserMenus();
		// Boucle sur tous les profils users
		foreach ( ProfileModel::getProfiles() as $profil )
		{
			if ( $profil['profile_type'] == 'user' )
			{
				$ProfileModel = new ProfileModel($profil['id_profile']);

				foreach ( $profileMenus as $idMenu )
				{
					if ( !$ProfileModel->isMenuInProfile($idMenu) )
					{
						$ProfileModel->addMenuToProfile($idMenu);
					}
				}

				$ProfileModel->buildProfileToMenu();
			}
		}
                
                // 06/11/2012 BBX
                // BZ 29516 : Gestion des menus admin également
                $profileMenus = MenuModel::getAdminMenus();
                // Boucle sur tous les profils admin
		foreach ( ProfileModel::getProfiles() as $profil )
		{
                    if ( $profil['profile_type'] == 'admin' )
                    {
                        $ProfileModel = new ProfileModel($profil['id_profile']);

                        foreach ( $profileMenus as $idMenu )
                        {
                            if ( !$ProfileModel->isMenuInProfile($idMenu) )
                            {
                                $ProfileModel->addMenuToProfile($idMenu);
                            }
                        }

                        $ProfileModel->buildProfileToMenu();
                    }
                }
	} // End function checkProfiles

	/**
	 * Supprime les tables temporaires commencant par "ctx_"
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param Ressource $db Ressource de connexion à une base de données
	 */
	private function dropTablesTemp ( $db )
	{
		$result = $db->execute("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE 'ctx_%'");
		if ( $db->getNumRows() == 0 )
			return;

		while ( $row = $db->getQueryResults($result, 1) )
		{
			$db->execute("DROP TABLE {$row['tablename']}");
		}
	} // End function dropTablesTemp

	/**
	 * Retourne la liste des champs d'une table
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $table nom de la table
	 * @return array
	 */
	private function getColumns ( $table )
	{
		if ( !array_key_exists($table, $this->_tablesColumn) )
		{
			// 01/07/2009 BBX : suppression du commentaire SQL car il faisait planter la requête. BZ 10322
			$querySelectColumn = "
					SELECT
						a.attname as field,
						a.attnum-1 as order
					FROM
						pg_catalog.pg_attribute a
					WHERE
						a.attrelid IN (
									SELECT
										c.oid
									FROM
										pg_catalog.pg_class c
										LEFT JOIN pg_catalog.pg_namespace n
										ON n.oid = c.relnamespace
									WHERE
										c.relname ~ '^{$table}$'
										AND pg_catalog.pg_table_is_visible(c.oid)
								)
						AND a.attnum > 0
						AND NOT a.attisdropped
						AND a.attname <> 'the_geom'
					ORDER BY
						a.attnum
				";

                        // 12/12/2011 NSE DE : new parameters in AA links contextual filters
                        // on doit comparer la structure du fichier avec celle du produit sur lequel on va monter le contexte, 
                        // et non sur le master, on passe donc en paramètre l'id du produit courant
			$db = $this->getConnection($this->_idProduct);

			$resultSelectColumn = $db->execute($querySelectColumn);

			while ( $row = $db->getQueryResults($resultSelectColumn, "one") )
			{
				$this->_tablesColumn[$table][$row['order']] = $row['field'];
			}

			if ( !array_key_exists($table, $this->_tablesColumn) )
				throw new Exception(__T('A_E_CONTEXT_UNDEFINED_TABLE', $table));
		}

		return $this->_tablesColumn[$table];
	} // End function getColumns

	/**
	 * Retourne la connexion à une base de données d'un produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit on doit se connecter
	 * @return Ressource
	 */
	private function getConnection ( $idProduct )
	{
		if ( !array_key_exists($idProduct, $this->_listDb) )
		{
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
			$this->_listDb[$idProduct] = Database::getConnection($idProduct);
			if ( $this->_debug & 2 )
			{
				$this->_listDb[$idProduct]->setDebug(1);
			}
		}

		return $this->_listDb[$idProduct];
	} // End function getConnection

	/**
	 *
	 * 28/05/2013 GFS - Bug 33864 - [SUP][TA Cigale GSM][MTN Iran][AVP 34007]: Raw activated by customer are deactivated during the upgrade
	 * 24/09/2014 FGD - Bug 43569 - [SUP][T&A OMC NSN UTRAN 5.3.1][AVP#48088][Videotron Canada] Deactivation and loss of history during the upgrade, for raws activated by customer
	 */
	private function countersManagement() {
		// si ower = astellia, si actif avant et desactive après, alors on regarde si le counter est desactivable (pas utilise ailleurs)
		//			si desactivable alors owner = astellia et on_off = 0
		//			sinon owner = customer et on_off = 1
		// si owner = customer, 
		//			si actif avant alors on_off = 1 
		//			si non actif avant alors owner = astellia
		// si owner = null, alors on regarde si le counter etait actif avant la mise à jour du contexte
		//		si non actif, alors owner = astellia
		//		si actif avant et après, alors owner = astellia
		//		si actif avant et desactive après, alors  owner = customer et on_off = 1
		// Liste des counters actifs post mise a jour du contexte
		$counters = $this->getCounterStatus();
		foreach ($counters as $counter) {
			//get counter's state before mounting the context
			$preMountCounter = null;
			if(array_key_exists($counter['id_ligne'], $this->_counters)){
				$preMountCounter = $this->_counters[$counter['id_ligne']];
			}			
			
			if ($counter ['owner'] == self::$OWNER_ASTELLIA){
				//owner is astellia
				if ($preMountCounter!=null && $preMountCounter['on_off']==1){
					//counter was activated before context update	
					if ($counter['on_off'] == 0){
						//counter isn't activated after context update
						$res = RawModel::desactivateCounter($counter['id_ligne'], $counter['family'], ($counter['new_field']==2)/*force undeploy seulement si demandé dans nouveau contexte*/, $this->_idProduct) ;
						if (empty($res) || $res[0] != RawModel::$IFDOWN_COUNTER_OK) {
							// deactivation not successful
							// change user to customer
							$this->_db->execute("update sys_field_reference set new_field = 0, on_off = 1, owner = '". self::$OWNER_CUSTOMER ."' where id_ligne = '".$counter['id_ligne']."'");
						}
					}				
				}
			}else if ($counter ['owner'] == self::$OWNER_CUSTOMER) {
				//owner is customer
				if ($preMountCounter!=null && $preMountCounter['on_off']==1){
					//counter was activated before context update	
					// change on_off value to 1
					$this->_db->execute("update sys_field_reference set new_field = 0, on_off = 1 where id_ligne = '".$counter['id_ligne']."'");
				}else{
					//counter wasn't activated before context update
					//change owner to astellia
					$this->_db->execute("update sys_field_reference set owner = '". self::$OWNER_ASTELLIA ."' where id_ligne = '".$counter['id_ligne']."'");
				}
			}else{
				//owner is unknown
				if ($preMountCounter!=null && $preMountCounter['on_off']==1) {
					//counter was activated before context update	
					if ($counter['on_off'] == 1) {
						//counter is activated after context update
						// change owner value to astellia
						$this->_db->execute("update sys_field_reference set owner = '". self::$OWNER_ASTELLIA ."' where id_ligne = '".$counter['id_ligne']."'");
					}else {
						//counter isn't activated after context update
						// change owner value to customer and on_off to 1
						$this->_db->execute("update sys_field_reference set new_field = 0, on_off = 1, owner = '". self::$OWNER_CUSTOMER ."' where id_ligne = '".$counter['id_ligne']."'");						
					}
				}else {
					//counter wasn't activated before context update	
					// change owner value to astellia
					$this->_db->execute("update sys_field_reference set owner = '". self::$OWNER_ASTELLIA ."' where id_ligne = '".$counter['id_ligne']."'");
				}
			}
		}		
	}
	
	/**
	 * 28/05/2013 GFS - Bug 33864 - [SUP][TA Cigale GSM][MTN Iran][AVP 34007]: Raw activated by customer are deactivated during the upgrade
	 * @return array Retourne la liste des counters actifs
	 * Chaque element contient : $element['id_ligne'], $element['owner'], $element['family'],  $element['on_off'] et  $element['new_field']
	 * 
	 */
	private function getCounterStatus() {
		return $this->_db->getAll("select sfr.id_ligne, sfr.owner, gt.family, sfr.on_off, sfr.new_field from sys_field_reference sfr inner join sys_definition_group_table gt on gt.edw_group_table = sfr.edw_group_table");
	}
                                	
	
	/**
	 * Vérification avant de monter un contexte
	 *
	 *	15:52 16/12/2009 GHX
	 *		- Ajout de la fonction pour la correction BZ 13354
         * 21/11/2011 NSE bz 23942 : ajout du paramètre $tablesUsed
	 *
	 * @author GHX
	 * @version CB 5.0.2.2
	 * @since CB 5.0.2.2
	 * @param array $listElements liste des éléments qui seront montés
	 * @param array $tables liste des tables disponibles à partir du contexte
	 */
	private function preMountElements ( $listElements, $tables, &$tablesUsed )
	{
		// Recuperation des proprietes suivantes du counter : on_off, owner, family
		if ($this->_db->columnExists("sys_field_reference", "owner")) {
			$counters = $this->getCounterStatus();
			foreach ($counters as $counter) {
				$this->_counters[$counter['id_ligne']] = $counter;
			}
		}
		
		// 23/03/2012 BBX
        // BZ 25126 : c'est ici qu'il faut sauver la conf menu
        if ( array_key_exists('profile_menu_position', $tables)) {
        	$this->backupMenuConfig();
		}

        // Exécution de la procédure PL/PGSQL qui supprimer les éléments à supprimer
        if ( array_key_exists('sys_definition_context_element_to_delete', $tables) ) {
			$this->_db->execute('SELECT contextDeleteElementToDelete()');
            $tablesUsed['sys_definition_context_element_to_delete'] = 1;
		}

        // 17:23 16/12/2009 GHX
        // BZ 13354
        // 28/07/2010 BBX
        // Le contrôle du nombre de compteur ne doit pas se faire en mode migration
        // BZ 16592
		if ( !$this->_activation && !$this->_migration )
		{
			$errorLimitExceeded = '';
			// Si la table des compteurs est présent
			if ( array_key_exists('sys_field_reference', $tables) )
			{
				$errorLimitExceeded = $this->checkNbRawsByFamilies('sys_field_reference', 'Counters');
			}
			if ( array_key_exists('sys_definition_kpi', $tables) )
			{
				$errorLimitExceeded .= (empty($errorLimitExceeded) ? '' : '<br />' ).$this->checkNbRawsByFamilies('sys_definition_kpi', 'KPI');
			}

			if ( !empty($errorLimitExceeded) )
				throw new Exception($errorLimitExceeded);
		}

		// 10:20 22/09/2009 GHX
		// Correction du BZ 11355
		// Si on monte des dashboards, on check les ID menu parents des dashboards
		// 17:58 21/12/2009 GHX
		// Correction du BZ 13527 : ajout de la condition sur le contexte de migration
		if ( array_key_exists(2, $listElements) && !$this->_activation && !$this->_migration )
		{
			$this->checkMenuDashboard();
		}
                
		// 11/10/2011 NSE DE Bypass temporel
        // on mémorise l'état du bypass sur les familles avant montage du contexte
        if($this->_db->columnExists('sys_definition_categorie', 'ta_bypass')){
        	$query = "SELECT family, ta_bypass FROM sys_definition_categorie WHERE ta_bypass <> ''";
            $this->_bypassBeforeMount = $this->_db->getAll($query);
		}
        else {
			$this->_bypassBeforeMount = array();
		}

	} // End function preMountElements

	/**
	 * Vérifie que l'on ne dépasse pas le nombre maximum d'élément raw ou kpi pour chaque famille et retourne un message si la limite est dépassé sinon rien
	 *
	 * @author GHX
	 * @version CB 5.0.2.2
	 * @since CB 5.0.2.2
	 * @param string $table nom de la table à vérifier
	 * @param string $type Counters ou KPI (le type sera affiché en restitution en cas d'erreur
	 * @return string
	 */
	private function checkNbRawsByFamilies ( $table, $type )
	{

		// maj 04/03/2010 - MPR : Correction BZ14255 - Limitation du nombre de KPIs actifs prise en compte
		$query = "SELECT tablename FROM pg_tables WHERE tablename = 'ctx_sys_global_parameters'";
		$this->_db->execute( $query );
		// Si la table sys_global_parameters est présente dans le contexte
		// on extrait la valeur du paramètre maximum_mapped_counters dans celle-ci
		if( $this->_db->getNumRows() > 0 )
		{
			$query = "SELECT value FROM ctx_sys_global_parameters WHERE parameters = 'maximum_mapped_counters'";
			// Nom de la table à mettre à jour si la valeur est > à la limite de la constante self::MAX_RAW_BY_FAMILIES (1570 maxi)
			$table_update = "ctx_sys_global_parameters";
			$elm_msg = "this context";
		}
		// Sinon on récupère la valeur en base de données dans la table sys_global_parameters
		else
		{
			$query = "SELECT value FROM sys_global_parameters WHERE parameters = 'maximum_mapped_counters'";
			// Nom de la table à mettre à jour si la valeur est > à la limite de la constante self::MAX_RAW_BY_FAMILIES (1570 maxi)
			$table_update = "sys_global_parameters";
			$elm_msg = "database";
		}

		// Récupération de la limite définie dans le contexte ou en base de données
		$limit_ctx = $this->_db->getOne($query);
		if( $limit_ctx == false )
		{
			// Si celle-ci n'existe pas // Cas : Aucun contexte monté et chargement d'un contexte ne contenant pas sys_global_parameters
			$limit = self::MAX_RAW_BY_FAMILIES;
		}
		elseif( $limit_ctx <= self::MAX_RAW_BY_FAMILIES )
		{
			// Si la limite du contexte est < à la limite maximale autorisée (limite de PostgreSQL = 1570)
			$limit = $limit_ctx;
		}
		else
		{
			// Sinon on prend en compte la limite maximale autorisée (limite de PostgreSQL = 1570)
			$limit = self::MAX_RAW_BY_FAMILIES;

			// On met à jour la table ctx_sys_global_parameters ou sys_global_parameters
			$query_update = "UPDATE {$table_update} SET value = '".self::MAX_RAW_BY_FAMILIES."' WHERE parameters = 'maximum_mapped_counters'";
			$this->_db->execute( $query_update );

			// Message d'alert indiquant que le paramètre
			$this->_messages .= "<br />".__T('A_E_CONTEXT_MOUNT_MSG_ALERT_UPDATE_LIMIT_NB_RAW_KPI_CTX',$type, $elm_msg, $limit_ctx, self::MAX_RAW_BY_FAMILIES, $this->_infoProduct['sdp_label']);

		}

		if ( $this->_debug ) echo "\n      - Vérifie que l'on ne dépasse la limite ".self::MAX_RAW_BY_FAMILIES." ".$type." par familles : ";

		// BZ 13354
		// 02/03/2010 BBX : réécriture de la requête qui détermine si la limite des 1570 compteurs a été atteinte.
		// Cette requête permet de gérer des cas supplémentaires :
		// => la limite correspond bien au nombre de compteurs actifs par famille, même dans le cas d'une installe de base
		// => les mises à jour de compteurs ne sont pas comptabilisées sauf pour le cas ci-dessous
		// => la requête prend en compte la mise à jour d'un compteur inactif vers actif
		$queryCheckNbRaws = "
		SELECT
			COALESCE(ref.edw_group_table, c.edw_group_table) AS gt,
			t1.family_label,
			COUNT(*) AS nbraws

		FROM {$table} ref

			FULL JOIN ctx_{$table} c ON ref.id_ligne = c.id_ligne

			LEFT JOIN

			(SELECT sdgt.edw_group_table, sdc.family_label
				FROM sys_definition_group_table sdgt
				LEFT JOIN sys_definition_categorie sdc
				ON sdgt.id_ligne = sdc.rank) t1

			ON t1.edw_group_table = COALESCE(ref.edw_group_table, c.edw_group_table)

		WHERE c.on_off = 1
		OR (c.on_off IS NULL AND ref.on_off = 1)
		GROUP BY gt,t1.family_label
		HAVING COUNT(*) > ".$limit."
		LIMIT 1;";
		$nbRawByFamilies = $this->_db->getAll($queryCheckNbRaws);
		// FIN 13354

		/*
		// On compte le nombre de compteur par famille
		$nbRawByFamilies = $this->_db->getAll("
			SELECT
				COUNT(*) AS nbraws,
				family_label
			FROM
				ctx_{$table}
				LEFT JOIN sys_definition_group_table USING (edw_group_table)
				LEFT JOIN sys_definition_categorie ON (sys_definition_group_table.id_ligne = rank)
			GROUP BY family_label
		");
		*/

		if ( count($nbRawByFamilies) > 0 )
		{
			$error = '';
			foreach ( $nbRawByFamilies as $oneFamily )
			{
				if ( $this->_debug ) echo "\n        - ".$oneFamily['family_label'].' : '.$oneFamily['nbraws'];
				// 08/04/2010 BBX : On utilise la limite calculée précédemment. BZ 13354
				// On regarde si pour la famille on ne dépasse pas la limitation
				if ( (int)$oneFamily['nbraws'] > $limit )
				{
					if ( $this->_debug ) echo " >> LIMIT EXCEEDED !";
					$error .= (empty($error) ? '' : '<br />').__T('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED', $limit, $type, $oneFamily['family_label'], $oneFamily['nbraws'], $this->_infoProduct['sdp_label'] );
				}
			}
			if ( !empty($error) )
				return $error;
		}

		return null;
	} // End function checkNbRawsByFamilies

	/**
	 * Vérifications après montage d'un contexte
	 *
	 *	15:52 16/12/2009 GHX
	 *		- Ajout de la fonction pour améliorer la lisibilité de la fonction mount
	 *
	 * @author GHX
	 * @version CB 5.0.2.2
	 * @since CB 5.0.2.2
	 * @param array $listElements liste des éléments qui seront montés
	 * @param array $tables liste des tables disponibles à partir du contexte
	 * @param array $tablesUsed liste des tables qui ont été utilisés lors du montage du contexte
	 */
	private function postMountElements ( $listElements, $tables, &$tablesUsed )
	{
		// 28/05/2013 GFS - Bug 33864 - [SUP][TA Cigale GSM][MTN Iran][AVP 34007]: Raw activated by customer are deactivated during the upgrade
		$this->countersManagement();
		
		// 11:46 23/03/2009 GHX
		// Mise à jour de la table sys_versioning uniquement si c'est pas un contexte d'activation
		if ( !$this->_activation )
		{
			if ( $this->versioning($tables) )
			{
				$tablesUsed['sys_versioning'] = 1;
			}
		}

		/*
			TRAITEMENT A FAIRE SI CERTAINES SONT PRESENTES DANS LE CONTEXTE ET ONT ETE TRAITEE
		*/
		// 15:41 29/07/2009 GHX
		// Correction du BZ 10858
		// Si le fichier sys_definition_network_agregation.csv est présent on lance le déploiement
		if ( array_key_exists('sys_definition_network_agregation', $tablesUsed) )
		{
			$this->launchDeploy(1);
			// ma j 06/08/2009 - MPR :  Correction du bug 10945 : Tous les niveaux d'agrégation 3ème axe
			$this->launchDeploy(3);
		}

		// 11/07/2011 MMT bz 22751 lors de montage sur corporate, il faut avoir compute_mode = ta_min
		if ( array_key_exists('sys_global_parameters', $tablesUsed) )
		{
			if(CorporateModel::isCorporate($this->_idProduct)){
				// il faut remettre la valeure compute_mode en fonction du TA min corporate
				$ta_min = CorporateModel::getTaMin($this->_idProduct);
				if($ta_min == 'day')
				{
					$query = "UPDATE sys_global_parameters SET value = 'daily' WHERE parameters = 'compute_mode'";
				} else {
					$query = "UPDATE sys_global_parameters SET value = 'hourly' WHERE parameters = 'compute_mode'";
				}
				$this->_db->execute($query);
			}
		}
		

		// 09:21 20/08/2009 GHX
		// Correction du BZ 11142
		// Si la table sys_pauto_page_name est présent on regarde si les graphes/dashboards associés à des utilisateurs sont bien présent
		if ( array_key_exists('sys_pauto_page_name', $tablesUsed) )
		{
			$this->checkIdUserInGraphAndDashboard();
			// 10:38 22/09/2009 GHX
			// Correction du BZ 11355
			if ( !$this->_activation )
				$this->checkProfiles();
		}
		// 09:30 31/08/2009 GHX
		// Si on a du paramétrage BH dans le contexte, on déploie les tables BH
		if ( array_key_exists('sys_definition_time_bh_formula', $tablesUsed) )
		{
			$this->launchDeployBH();
		}

        if ( array_key_exists( 'sys_definition_selecteur', $tablesUsed ) )
        {
            /*
             * Mise à jour des identifiants des selecteurs. Après montage du contexte,
             * des doublons peuvent apparaîtrent. Une réinitialisation de la séquence
             * du champs de type serial et un update de toutes les lignes permet de
             * réinitialiser toutes les valeurs.
             * 14/06/2011 OJT : Correction bz22545, gestion des doublons Selecteur/User Homepage
             */
            // Recherche des doublons dans la table sys_definition_selecteur
            $duplicates = array();
            $dupRes     = $this->_db->execute( "SELECT sds_id_selecteur,COUNT(sds_id_selecteur) as nb FROM sys_definition_selecteur GROUP BY sds_id_selecteur;" );
            while( $dup = $this->_db->getQueryResults( $dupRes, 1 ) )
            {
                if( intval( $dup["nb"] ) > 1 )
                {
                    // Il y des doublons
                    $duplicates []= $dup["sds_id_selecteur"];
                }
            }

            // Si il y des doublons de détectés
            if( count( $duplicates ) > 0 )
            {
                // Pour tous les doublons, on supprime les entrées correspondantes dans user
                $inClause = implode( ',', $duplicates );
                $this->_db->execute( "UPDATE users SET homepage=null WHERE homepage IN({$inClause})" );

                // Mise à jour de la séquence PostgreSql avec la nouvelle valeur max
                $newMax = $this->_db->getOne( "SELECT max(sds_id_selecteur)+1 FROM sys_definition_selecteur;" );
                $this->_db->execute( "ALTER SEQUENCE sys_definition_selecteur_sds_id_selecteur_seq RESTART WITH {$newMax}" );

                // Mise à jour des doublons
                $this->_db->execute( "UPDATE sys_definition_selecteur SET sds_id_selecteur=nextval('sys_definition_selecteur_sds_id_selecteur_seq') WHERE sds_id_selecteur IN({$inClause})" );
            }

            // Suppression des lignes inutilisées
            // 07/09/2011 BBX
            // Correction d'un cast
            // BZ 23650
            $this->_db->execute( "DELETE FROM sys_definition_selecteur
                                    WHERE sds_report_id IS NULL
                                    AND sds_id_selecteur::text NOT IN
                                    (SELECT homepage FROM users WHERE homepage IS NOT NULL)" );

            // Mise à NULL des homepage ne pointant vers rien
            // 07/09/2011 BBX
            // Correction d'un cast
            // BZ 23650
            
            //10/09/2014 - FGD - Bug 43831 - [SUP][TA Gateway][AVP 47571][Zain HQ]: Patch installation on slave deactivate homepage on Master
            //Conservation des liens vers le produit homepage
            $this->_db->execute( "UPDATE users SET homepage=null WHERE homepage!='1'
            						AND homepage!='-1'
                                    AND homepage NOT IN (SELECT sds_id_selecteur::text FROM sys_definition_selecteur WHERE sds_report_id IS NULL)" );

        }

        // 31/05/2011 NSE bz 22349 : recréation des chemins d'agrégation des NA clients déployés détruits lors du montage du contexte
        // Si le fichier sys_definition_group_table_network.csv est présent on réactive les NA Client
        if ( array_key_exists('sys_definition_group_table_network', $tablesUsed) )
		{
            // les NA définis par le client
			$queryClientNA = "SELECT * FROM sys_definition_network_agregation
            WHERE sdna_id SIMILAR TO 'sdna.[a-z0-9]+'";
            $resultClientNA = $this->_db->execute($queryClientNA);
       		while($naInfo = $this->_db->getQueryResults($resultClientNA,1)) {
                // si le NA a été déployé
                if($this->_db->doesTableExist('edw_'.get_sys_global_parameters("module","",$this->_idProduct).'_'.$naInfo['family'].'_axe1_raw_'.$naInfo['agregation'].'_day')){
                    $familyModel = new FamilyModel($naInfo['family'], $this->_idProduct);
                    $familyInfos = $familyModel->getValues();
                    $ret = NaModel::createAgregationPath($naInfo['family'],$familyInfos,$naInfo['agregation'],$naInfo['source_default'],$naInfo['agregation_label'],$this->_idProduct);
                    echo "Recréation des chemins d'agrégation pour le NA Client '".$naInfo['agregation']."'\n";
                }
            }
		}

            // 05/01/2012 BBX
            // BZ 25126, 22157 : On restaure l'état et l'ordre des menus
            if ( array_key_exists('profile_menu_position', $tablesUsed) )
            {
		if ( $this->_debug )
                    echo "\n >> Restauration des positions / status des menus\n";

                // Restauration des positions
                $cpt = 0;
                foreach($this->_menuPositions as $menu)
				{
                    $queryUpdatePos = "UPDATE profile_menu_position
                    SET position = {$menu['position']},
                    id_menu_parent = '{$menu['id_menu_parent']}'
                    WHERE id_menu = '{$menu['id_menu']}'
                    AND id_profile = '{$menu['id_profile']}'";
                    $this->_db->execute($queryUpdatePos);
                    $cpt += $this->_db->getAffectedRows();
                }

                if($this->_debug) echo "\n\t $cpt positions restaurées\n";

                // Restauration des status
                $cpt = 0;
                foreach($this->_disabledMenus as $menu)
                {
                    $queryDisableMenu = "DELETE FROM profile_menu_position
                    WHERE id_menu = '{$menu['id_menu']}'
                    AND id_profile = '{$menu['id_profile']}'";
                    $this->_db->execute($queryDisableMenu);
                    $cpt += $this->_db->getAffectedRows();
                }

                if($this->_debug) echo "\n\t $cpt menus désactivés\n";

                // Vacuum de la table
                $this->_db->execute("VACUUM ANALYZE profile_menu_position");
            }
            // FIN BZ 25126, 22157
            
            // 12/04/2012 BBX
            // BZ 21721 : on restaure les homepage des users
            if ( array_key_exists('users', $tablesUsed) )
            {
                foreach($this->_usersHomepage as $row) {
                    $query = "UPDATE users SET homepage = '{$row['homepage']}' WHERE id_user = '{$row['id_user']}'";
                    $this->_db->execute($query);
                }
                // Vacuum de la table
                $this->_db->execute("VACUUM ANALYZE users");
            }
            // FIN BZ 21721
            
            // 18/04/2012 BBX
            // BZ 21945 : on propage les menus supprimés des slaves au master
            $this->spreadSlaveMenuDeletion();

            if ( $this->_debug )
            {
			// Normalement on ne devrait avoir aucune différence
			// S'il y en a une c'est qu'il y a un problème quelque part !!!
                // 21/11/2011 : correction Warning
                if(!empty($tables)||!empty($tablesUsed)){
			$diff = array_diff_key($tables, $tablesUsed);
			if ( count($diff) > 0 )
			{
				echo "\n\nListe des tables qui n'ont pas été utilisées lors du montage du sous-contexte : \n";
				print_r(array_keys($diff));
			}
			else
			{
                            echo "\n\nToutes les tables présentes dans le sous-contexte ont été utilisées";
			}
		}
                // 12/12/2011 ACS BZ 24993 One brace missing
                }
                else{
                    echo "\n\nToutes les tables présentes dans le sous-contexte ont été utilisées";
                }
                
            // 11/10/2011 NSE DE Bypass temporel
            // on compare l'état du bypass sur les familles avant et après montage du contexte
            if($this->_db->columnExists('sys_definition_categorie', 'ta_bypass')){
                $query = "SELECT family, ta_bypass FROM sys_definition_categorie WHERE ta_bypass <> ''";
                $bypassAfterMount = $this->_db->getAll($query);
            }
            else{
                $bypassAfterMount = array();
            }
            //array_diff($this->_bypassBeforeMount[0],$bypassAfterMount[0])
            // 16/03/2012 NSE : correction warning dans log
            if( sizeof($bypassAfterMount)!=sizeof($this->_bypassBeforeMount) 
                    || ( is_array($bypassAfterMount[0]) && sizeof(array_diff($bypassAfterMount[0],$this->_bypassBeforeMount[0]))>0 ) ) {
                if(CorporateModel::isCorporate($this->_idProduct)){
                    $this->_messages .= '<br />'.__T('A_CONTEXT_TA_BYPASS_UPDATE_CORPORATE');
                }
                // 20/10/2011 NSE bz 24295 Suppression du cas Mixed Kpi qui est impossible
            }
            
	} // End function postMountElements

	/**
	 * Indique si la table passée en paramètre doit être ignorée pour le Corporate
	 * Méthode créer dans le cadre de la correction du bug BZ 14392
	 *
	 * @author BBX
	 * @version CB 5.0.2.12
	 * @since CB 5.0.2.12
	 * @param string $table nom de la table
	 * @return bool
	 */
	private function isExcludedForCorporate($table)
	{
		// Récupération des tables à exlure
		$db = $this->getConnection('');
		$queryTables = "SELECT sdct_table
		FROM sys_definition_context_table
		WHERE use_in_corporate = 0
		AND sdct_table = '$table'";
		$db->execute($queryTables);
		// Si la tabl est à exclure, en renvoie true
		return ($db->getNumRows() == 1);
	}

	/**
	 * Retourne le code produit d'un produit donné.
	 * Cette méthode gère le cas Corporate il faut se baser sur "old_module"
	 * Méthode créer dans le cadre de la correction du bug BZ 14392
	 *
	 * @author BBX
	 * @version CB 5.0.2.12
	 * @since CB 5.0.2.12
	 * @param int $idProd id du produit
	 */
	public function getModuleFromProduct($idProd = '')
	{
		$db = $this->getConnection($idProd);
		$queryModule = "SELECT saai_interface
		FROM
		(
			SELECT parameters, value
			FROM sys_global_parameters
			WHERE parameters IN ('old_module','module')
			ORDER BY parameters DESC
			LIMIT 1
		) sgp
		LEFT JOIN sys_aa_interface
		ON (lower(sgp.value) = lower(saai_module))";
		return $db->getOne($queryModule);
	}


	/**
	 * Sauvegarde les valeurs d'une colonne dans une table temporaire
	 * Méthode créer dans le cadre de la correction du bug BZ 14392
	 *
	 * @author BBX
	 * @version CB 5.0.2.12
	 * @since CB 5.0.2.12
	 * @param string $table : nom de la table
	 * @param string $idTable : nom de la colonne PK
	 * @param string $column : nom de la colonne à sauver
	 */
	private function saveColumn($table,$idTable,$column,$condition = '')
	{
		// BDD
		$db = $this->getConnection($this->_idProduct);

		// Détermination du nom de la table temporaire
		$tempTableName = substr($table.'_'.$column.'_'.uniqid(),0,40);

		// Destruction préventive
		$queryDrop = "DROP TABLE IF EXISTS {$tempTableName}";
		$db->execute($queryDrop);

		// Condition
		$condition = (empty($condition)) ? '' : 'WHERE '.$condition;

		// Sauvegarde de la colonne
		$query = "CREATE TABLE {$tempTableName} AS
		SELECT {$idTable}, {$column}
		FROM {$table}
		{$condition}
		GROUP BY {$idTable}, {$column}
		ORDER BY {$idTable}";
		$db->execute($query);

		// Mémorise la colonne sauvegardée
		$this->_savedColumns[$table][$column] = Array($idTable,$tempTableName);
	}

	/**
	 * Restaure les valeurs d'une colonne depuis une table temporaire
	 * Méthode créer dans le cadre de la correction du bug BZ 14392
	 *
	 * @author BBX
	 * @version CB 5.0.2.12
	 * @since CB 5.0.2.12
	 * @param string $table : nom de la table
	 * @param string $column : nom de la colonne à sauver
	 */
	private function restoreColumn($table,$column)
	{
		// BDD
		$db = $this->getConnection($this->_idProduct);

		// Si la sauvegarde existe
		if(isset($this->_savedColumns[$table][$column]))
		{
			$idTable = $this->_savedColumns[$table][$column][0];
			$tempTableName = $this->_savedColumns[$table][$column][1];

			// Restauration des valeurs
			$queryRestore = "UPDATE {$table}
			SET {$column} = t0.new_value
			FROM (
				SELECT {$idTable}, {$column} AS new_value
				FROM {$tempTableName}
				GROUP BY {$idTable}, {$column}
				ORDER BY {$idTable}) t0
			WHERE t0.{$idTable} = {$table}.{$idTable}";
			$db->execute($queryRestore);

			// Suppression de la table temporaire
			$queryDrop = "DROP TABLE IF EXISTS {$tempTableName}";
			$db->execute($queryDrop);

			// Suppression de la référence à cette colonne
			unset($this->_savedColumns[$table][$column]);
		}
	}

    /**
     * Retourne la liste des utilisateurs du Slave remontés sur le master
     * lors de la mise en multiproduits
     * BZ 19783, 20113
     * @author BBX
     * @return array
     */
     public function getUsersFromSlave()
     {
        return $this->_usersFromSlave;
     }

	/**
	 * Retourne les compteurs activés par le client
	 *
	 * @author BBX
	 * @version CB 5.1.1.0
	 * @since CB 5.1.1.0
	 * @return array Tableau des compteurs client
	 */
    protected function getClientRaws()
    {
        // Connexion à la base de données
        $database = Database::getConnection($this->_idProduct);

        // Récupération des compteurs clients
        $query = "SELECT *
            FROM sys_field_reference
            WHERE id_ligne NOT IN (
                SELECT id_ligne FROM ctx_sys_field_reference
                GROUP BY id_ligne)";
        $result = $database->execute($query);

        // Création du tableau de résultat
        $clientCounters = array();
        while($row = $database->getQueryResults($result,1)) {
            // On retire l'id_ligne des valeur, car on a cette donnée en clé
            $clientCounters[$row['id_ligne']] = array_diff_key($row,array('id_ligne' => 'whatever'));
        }

            // Retour du tableau
        return $clientCounters;
    }

	/**
	 * Retourne les compteurs présents dans le contexte
	 *
	 * @author BBX
	 * @version CB 5.1.1.0
	 * @since CB 5.1.1.0
	 * @return array Tableau des compteurs contexte
	 */
    protected function getContextRaws()
    {
        // Connexion à la base de données
        $database = Database::getConnection($this->_idProduct);

        // Récupération des compteurs clients
        $query = "SELECT *
            FROM ctx_sys_field_reference";
        $result = $database->execute($query);

        // Création du tableau de résultat
        $ctxCounters = array();
        while($row = $database->getQueryResults($result,1)) {
            // On retire l'id_ligne des valeur, car on a cette donnée en clé
            $ctxCounters[$row['id_ligne']] = array_diff_key($row,array('id_ligne' => 'whatever'));
        }

        // Retour du tableau
        return $ctxCounters;
    }
    
    /**
     * Mémorise les Homepage des users 
     * BZ 21721
     */
    public function backupUsersHomepage()
    {
        // Saving custom homepages
        $this->_usersHomepage = array();
        $query = "SELECT id_user, homepage FROM users
            WHERE homepage != '' AND homepage IS NOT NULL";
        $result = $this->_db->execute($query);
        while($row = $this->_db->getQueryResults($result,1)) {
            $this->_usersHomepage[] = $row;
        }
    }

    /**
     * Mémorise la liste des menus dasactivés et la positions des menus
     * BZ 25126, 22157
     */
    public function backupMenuConfig()
    {
        // Saving positions
        $this->_menuPositions = array();
        $queryPositions = "SELECT id_menu, id_profile, id_menu_parent, position
            FROM profile_menu_position";
        $result = $this->_db->execute($queryPositions);
        while($row = $this->_db->getQueryResults($result,1)) {
            $this->_menuPositions[] = $row;
        }

        // Saving activation status
        $this->_disabledMenus = array();
        $queryStatus = "SELECT id_menu, id_profile
            FROM menu_deroulant_intranet, profile
            WHERE profile_type = CASE WHEN is_profile_ref_user = 1 THEN 'user' ELSE 'admin' END
            AND ROW(id_menu,id_profile)
            NOT IN (SELECT id_menu,id_profile FROM profile_menu_position)";
        $result = $this->_db->execute($queryStatus);
        while($row = $this->_db->getQueryResults($result,1)) {
            $this->_disabledMenus[] = $row;
        }
    }
    
    /**
     * Permet de propager les menus supprimés des Slaves sur le Master
     * 18/04/2012 BBX
     * BZ 21945
     */
    protected function spreadSlaveMenuDeletion()
    {       
        // On procède si le produit courant n'est pas le master
        if(ProductModel::getIdMaster() != $this->_idProduct)
        {
            // Connexion à la BDD du master
            $dbMaster = Database::getConnection(ProductModel::getIdMaster());
        
            // Récupération des menus et code du produit
            $productModel   = new ProductModel($this->_idProduct);
            $specificMenus  = $productModel->getProductSpecificMenus();
            $productCode    = sprintf("%04d", $productModel->getCode(true));
            
            if(count($specificMenus) > 0)
            {
                // Suppression dans menu_deroulant_intranet
                $queryDelete = "DELETE FROM menu_deroulant_intranet
                    WHERE (id_menu LIKE 'menu.{$productCode}.%'
                    OR id_menu LIKE 'menu.dshd.{$productCode}.%')
                    AND id_menu NOT IN ('".implode("','",$specificMenus)."')
                    RETURNING id_menu";
                $result = $dbMaster->execute($queryDelete);
                
                // Suppression dans profile_menu_position
                $query = "DELETE FROM profile_menu_position
                        WHERE id_menu IN (";                
                while($row = $dbMaster->getQueryResults($result,1)) {
                    $query .= "'{$row['id_menu']}',";
                }                
                $query .= "'')";               
                $result = $dbMaster->execute($query);
                
                // VACUUM
                $dbMaster->execute("VACUUM ANALYZE menu_deroulant_intranet");
                $dbMaster->execute("VACUUM ANALYZE profile_menu_position");
            }
        }
    }
} // End class ContextMount
?>