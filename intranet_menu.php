<?php
/**
 * 22/11/2011 BBX
 * Mise en objet du script qui était difficile à apréhender
 * Ajout d'un système de cache
 * 
 * 12/12/2011 ACS BZ 25108 Warning displayed in menu when one slave is deactivated
 * 25/05/2012 NSE bz 24000 : Problem with a cast under Postgres 9.1
 * 
 */
if(!isset($_SESSION)) session_start();
// 07/12/2010 MMT bz 11355 utilisation de fonctions statiques de ContextActivation
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextActivation.class.php';

/**
 * Class intranetMenu
 */
class intranetMenu
{
    protected $_database = null;
    protected $_userParams = array();
    protected $_nbProfilePosition = 0;
    protected $_userDashboarList = array();
    protected $_multiProduct = false;
    protected $_menusToLock = array();
    protected $_menu = null;


    /**
     * Constructeur
     * @param DatabaseConnection $database
     * @param array $userParams 
     */
    public function __construct(DatabaseConnection $database, array $userParams)
    {
        $this->_database    = $database;
        $this->_userParams  = $userParams;
        
        $this->nbProfilePosition();
        $this->getUserDashboarList();
        $this->isMultiProduct();
        $this->menusToLock();
    }
    
    /**
     * Saves lock state
     */
    public function __destruct() 
    {
        $_SESSION[ $_SESSION['id_user'] ]['menu_lock_state'] = $this->getLockState();
        $_SESSION[ $_SESSION['id_user'] ]['saved_menus']     = gzcompress($this->_menu);
    }
    
    /**
     * Returns lock state
     * @return type 
     */
    protected function getLockState()
    {
        return md5(print_r($this->_menusToLock,true));
    }
    
    /**
     * Returns last lock state
     * @return type 
     */
    protected function getLastLockState()
    {
        if(isset($_SESSION[ $_SESSION['id_user'] ]['menu_lock_state']))
            return $_SESSION[ $_SESSION['id_user'] ]['menu_lock_state'];
        return false;
    }
    
    /**
     * Permet d'ajouter une variable à l'URL
     * @param string $url
     * @param string $var
     * @return string 
     */
    protected function addGetVar($url, $var)
    {
        if ($url == '') return '';
        $url .= (strpos(' '.$url,'?')) ? '&'.$var : '?'.$var;
	return $url;
    }

    /*
     * On teste si on a des menus dans profile_menu_position.
     * On mémorise le nombre de menus
     */
    protected function nbProfilePosition()
    {        
        $query = "SELECT * FROM profile_menu_position 
            WHERE id_profile = '{$this->_userParams['user_profil']}'";
        $result = $this->_database->execute($query);
        $this->_nbProfilePosition = $this->_database->getNumRows();
    }
    
    /**
     * Retourne le résulat de la requête qui va chercher les menus
     * @param string $idMenu
     * @return result 
     */
    protected function getMenus($idMenu)
    {
        // 25/05/2012 NSE bz 24000 : Problem with a cast under Postgres 9.1
        $query = "SELECT libelle_menu, lien_menu, id_menu, complement_lien
            FROM menu_deroulant_intranet
            WHERE id_menu_parent='0'
            ORDER BY position ASC";
        if($this->_nbProfilePosition > 0) {
            $query = "SELECT libelle_menu, lien_menu, a.id_menu as id_menu, complement_lien, id_page, droit_affichage
                FROM menu_deroulant_intranet a, profile_menu_position b
                WHERE a.id_menu = b.id_menu
                        AND b.id_menu_parent='$idMenu'
                        AND id_profile ='{$this->_userParams['user_profil']}'
                ORDER BY b.position ASC";
        }
        return $this->_database->execute($query);
    }
    
    /**
     * Mémorise la liste des dashboards dans l'objet
     */
    protected function getUserDashboarList()
    {
        if ($this->_userParams['profile_type'] == 'user')
        $this->_userDashboarList = getUserDashboarList($_SESSION['id_user'],$this->_database->getCnx());
    }
    
    /**
     * Mémorise si on est sur un multiproduit
     */
    protected function isMultiProduct()
    {
        $this->_multiProduct = ContextActivation::isInMultiProduct(false);
    }
    
    /**
     * Mémorise la liste des menus bloqués en cas de produit off
     */
    protected function menusToLock()
    {
        // 20/12/2010 BBX
        // Récupération de la liste des manus à bloquer
        // En cas de produits désactivés
        // BZ 18510
        $inactiveProducts = array();
        foreach(ProductModel::getInactiveProducts() as $p) {
            $inactiveProducts[$p['sdp_id']] = $p['sdp_label'];
        }

        if(count($inactiveProducts) > 0)
        {
            // 22/11/2011 BBX
            // BZ 20420 : Correction de la récupération des menus par produit désactivé
            foreach($inactiveProducts as $idP => $pLabel) 
            {
                // Tous les dash liés au produit courant
                $allGTM = GTMModel::getAllContainsIdProduct ( $idP, true );                
                $query = "SELECT DISTINCT id_menu, '$pLabel' AS sdp_label
                FROM
                        menu_deroulant_intranet m,
                        sys_pauto_config s
                WHERE m.id_page = s.id_page
                AND s.class_object = 'graph'
                AND m.niveau >= 2
                AND s.id_elem IN ('".implode("','",array_keys($allGTM))."')";
                $result = $this->_database->execute($query);
                while($row = $this->_database->getQueryResults($result,1)) {
                    $this->_menusToLock[$row['id_menu']] = $row['sdp_label'];
                }
            }

			// 12/12/2011 ACS BZ 25108 remove specific request for CLIENT DASHBOARD. It has been include in previous method by using GTMModel::getAllContainsIdProduct
        }
        // Fin BZ 18510
    }
    
    /**
     * Construction récursive des menus
     * @param string $idMenu
     * @return string 
     */
    public function menuRecursif($idMenu = 0) 
    {
                  // Menu cache
        if(isset($_SESSION[ $_SESSION['id_user'] ]['saved_menus']))
        {
            $lastLockState      = $this->getLastLockState();
            $currentLockState   = $this->getLockState();
            
            if($lastLockState == $currentLockState)
            {
                $this->_menu = gzuncompress($_SESSION[ $_SESSION['id_user'] ]['saved_menus']);
                return $this->_menu;
            }
        }

        // Regenerating
        $menu = '';
        $resultMenus = $this->getMenus($idMenu);
        while($row = $this->_database->getQueryResults($resultMenus,1))
        {
            // Vérif
            $displayMenu = true;
            // maj 20/02/2008 christophe : si le profil de l'utilisateur courant est user, on n'affiche pas les dahsboards des autres utilisateurs (seulement les dahsboard admin)
            if ( $this->_userParams['profile_type'] == 'user' )
                if ( $row['droit_affichage'] == 'client' && !empty($row['id_page']) )
                    if ( !isset($this->_userDashboarList[$row['id_page']]) )
                        $displayMenu = false;
 
            // L'accès au menu est réouvert à tous les admins

            if ( $displayMenu ) 
            {
                $nom = $row["libelle_menu"];

                eval( "\$nom = \"$nom\";" );//permet d'avoir des menu dynamique en fonction du module iu/roaming...
                $lien = $row["lien_menu"];

                $id_menu = $row["id_menu"];
                if ($lien!="") $lien = substr(NIVEAU_0,0,-1).$lien;

                // vérifie si le numéro du menu est dans la liste des menus du profil
                if (in_array($id_menu, $_SESSION['menu_profile'])) 
                {
                    // on ajoute id_menu_encours au lien.
                    // 16/02/2009 - Modif. benoit : correction du nom de la variable GET du menu en cours. Remplacement de 'id_menu_en_cours' par 'id_menu_encours'
                    $lien = $this->addGetVar($lien,"id_menu_encours=".$id_menu);

                    // NSE bz 17030 : Suppression de la correction du bz 9839
                    // 20/12/2010 BBX
                    // On grise et on désactive les menus liés à un produit désactivé
                    // Et on ajoute [<label produit> disabled]
                    // BZ 18510
                    if(array_key_exists($id_menu,$this->_menusToLock))
                    {
                        $lien = "#";
                        $nom = '<span style="color:#DDDDDD;font-style:italic">'.$nom.' ['.$this->_menusToLock[$id_menu].' disabled]</span>';
                        $menu .= "<li><a href='#' class='nohref'>$nom</a>";
                    }
                    else
                    {
                        // on ajoute le menu
                        if ($lien) {
                                $menu .= "<li><a href=\"$lien\">$nom</a>";
                        } else {
                                $menu .= "<li><a href='#' class='nohref'>$nom</a>";
                        }

                        // recursivité : on va chercher le sous menu
                        $sous_menu = $this->menuRecursif($id_menu);
                        if ($sous_menu) $menu .= "<ol>$sous_menu</ol>";
                    }
                    // Fin BZ 18510
                    $menu .= "</li>";
                }
            }
        }
        $this->_menu = $menu;
        return $menu;
    }
}
// Construction du menu T&A
$intranetMenu = new intranetMenu(Database::getConnection(), getUserInfo($_SESSION['id_user']));
$menu = $intranetMenu->menuRecursif(0);
echo "<ol id='menu'>$menu</ol>";
?>
