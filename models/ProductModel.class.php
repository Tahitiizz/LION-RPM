<?php

/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 06/03/2012 NSE bz 26293 ajout de la méthode getOldCode()
 * 
 */
?><?php

/*
  18/08/2009 GHX
  - Ajout de la fonction delete() : suppression d'un produit
  - Ajout de la fonction deactivation() : désactivation d'un produit
  25/08/2009 GHX
  - Lors de la désactivation MaJ des profils users du Master
  05/10/2009 GHX
  - Ajout de la fonction getIdMaster()
  07/10/2009 GHX
  - Ajout de la fonction getIdMixedKpi()
  08/10/2009 BBX
  - Ajout de la fonction restoreOldModule()
  - Ajout de la fonction setAsDef()
  21/10/2009 GHX
  - Ajout de la fonction setAsMasterTopology()
  28/10/2009 GHX
  - Modifcation de la function deactivation() BZ 11355
  - Ajout d'un paramètre à la fonction updateProduct()
  - Modification dans les fonctions delete() et deactivation()
  09/12/2009 GHX
  - Correction du BZ 13225 [REC][MIXED-KPI][TC#51613] : erreur sur le dashboard
  -> Ajout de la fonction launchCleanTablesStructure ()
  05/03/2010 BBX
  - Ajout de la méthode "manageConnections"
  - Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'�viter les instances redondantes
  08/03/2010 BBX
  - Suppression de la méthode "manageConnections"
  - Utilisation de la méthode "Database::getConnection" à la place.
  25/03/2010 - MPR
  - Ajout de la méthode isProcessRunning
  17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les ex�cutables psql et php (PSQL_DIR et PHP_DIR)
  26/07/2010 BBX :
  - Ajout d'un trim sur le trigramme récupéré dans la méthode "generateTrigrams". BZ 16784
  28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 * 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility :
 *         - modification de la méthode isCbVersionCompatibleWithMaster() pour utiliser version_compare()
 *         - ajout de getProductLabel()
 * 25/10/2011 ACS BZ 24399 Files are not deleted on distant slave
 * 09/11/2011 MMT Bz 24482 getCBVersion ordonn� par Date pour avoir le dernier CB et non le plus grand
 * 18/11/2011 SPD1 : Ajout de la methode getProductsLabel (query builder v2) 
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
 * 20/12/2011 ACS BZ 25191 PSQL error in setup product with disabled product
 * 20/12/2011 ACS BZ 25206 Bad link to master on slave index page
 * 21/12/2011 ACS BZ 25191 PSQL error with disabled product
 * 22/12/2011 ACS BZ 24993 Add new product generate a critical log
 * 19/12/2012 GFS BZ 18111 [MIXED KPI]: pas de trigramm par défaut sur le slave
 * 22/02/2012 SPD1 add setReadOnlyUserAccess function
 * 17/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
 * 07/04/2017 RQ8103 : [AO-T&A] Add a check in Setup that T&A Gateway parent have upper version of CB in case of upgrade of a T&A slave
 */
?>
<?php

/**
 * 	Classe permettant de manipuler les produits
 * 	Travaille sur la table sys_definition_product
 *
 * 	@author	BBX - 12/12/2008
 * 	@version	CB 4.1.0.0
 * 	@since	CB 4.1.0.0
 *
 *
 */
class ProductModel {

    /**
     * Propriétés
     */
    private $idProduct = 0;
    private $database = null;
    private $productValues = Array();
    private $error = false;
    public static $lastErrorCode = 0;
    public static $lastFailedProduct = '';
    private $maintenanceFile = '';
    private $SSH = null;
    // Mémorise les instances de connexions ouvertes
    private static $connections = Array();

    // OJT DE Produit Blanc
    /** @var string Nom du module pour le produit blanc */
    const BLANK_PRODUCT_MODULE = 'bp';
    const ID_NEW_PRODUCT = -1;

    /**
     * Constructeur
     * @param : int	id produit
     */
    public function __construct($idProduct) {
        // 12/12/2011 ACS Mantis 837 DE HTTPS support
        if (empty($idProduct)) {
            $idProduct = self::getProductId();
        }

        // Sauvegarde de l'id produit
        $this->idProduct = $idProduct;
        // 22/12/2011 ACS BZ 24993 Add new product generate a critical log
        if ($this->idProduct != self::ID_NEW_PRODUCT) {
            // Connexion à la base de données
            $this->database = Database::getConnection($idProduct);
            // 20/12/2011 ACS BZ 25191 if connection to a slave failed, raise an error but retrieve data from master
            if (!$this->database->isConnectionOk()) {
                $this->error = true;
            }
            // Récupération des valeurs du produit
            if (is_numeric($idProduct)) {
                // 16/12/2010 BBX
                // Il faut se connecter impérativement au Master
                // pour recouvrir des données correctes
                // BZ 19803
                $db = Database::getConnection();
                $query = "SELECT * FROM sys_definition_product WHERE sdp_id = {$idProduct}";
                $array = $db->getRow($query);
                // Si les infos produit ne sont pas récupérées, on renvoie une erreur
                if (count($array) == 0) {
                    $this->error = true;
                } else {
                    $this->productValues = $array;
                }
            } else {
                // Si le format de l'id est incorrect, on renvoie une erreur
                $this->error = true;
            }
        }

        // 22/12/2010 BBX
        // Defines the maintenance file
        // Precision : hour
        // BZ 18510
        $this->maintenanceFile = '/tmp/' . $this->productValues['sdp_db_name'] . '_product_' . date('YmdH');
    }

    public function isError() {
        return $this->error;
    }

    /*     * **********************************************************************
     * Méthode getValues : retourne un tableau associatif contenant les paramètres du produit
     * @return : array	Tableau associatif
     * ********************************************************************** */

    public function getValues() {
        return $this->productValues;
    }

    /*     * **********************************************************************
     * Méthode setValue : ajoute une valeur à l'objet
     * @return : void()
     * ********************************************************************** */

    public function setValue($key, $value) {
        $this->productValues[$key] = $value;
    }

    /*     * **********************************************************************
     * Méthode setDatabase : modify the database of the ProductModel
     * @return : void()
     * ********************************************************************** */

    // 20/12/2011 ACS BZ 25191 setter of the database. Used when database configuration is modified
    public function setDatabase($connectionString) {
        $this->database = new DataBaseConnection($connectionString);
    }

    /**
     * Modifie la connexion à la base de données en réinitialisant celle du Master
     */
    public function setDatabaseMaster() {
        $this->database = Database::getConnection(1);
    }

    public function getDatabase() {
        return $this->database;
    }
    
  

    /************************************************************************
     * Méthode isProcessRunning : Vérifie si un process est en cours
     * @return : string/boolean (string si un process est en cours sinon false)
     * ***********************************************************************
     */
    public function isProcessRunning() {
        // 10/08/2011 BBX
        // BZ 23353 : Correction du cast
        $query = "  SELECT master_name
                        FROM sys_process_encours, sys_definition_master
                        WHERE encours = 1 AND process = master_id::text LIMIT 1";
        return $this->database->getOne($query);
    }

    /*     * **********************************************************************
     * Méthode addUser : ajoute un product :
     * Sauvegarde les informations contenues dans l'objet
     * @return : void()
     * ********************************************************************** */
    public function addProduct() {
        // 19/01/2011 BBX
        // Ajout du trigramme
        // BZ 18510
        // Récupération du nouvel ID
        $query = "SELECT MAX(sdp_id)+1 as sdp_id FROM sys_definition_product";
        $result = $this->database->getRow($query);
        $newIdProduct = ($result['sdp_id'] == '') ? '1' : $result['sdp_id'];


        // 09/12/2011 ACS Mantis 837 DE HTTPS support
        $query = "INSERT INTO sys_definition_product (  sdp_id,
						        sdp_label,
						        sdp_ip_address,
							sdp_directory,
							sdp_db_name,
							sdp_db_port,
							sdp_db_login,
							sdp_db_password,
							sdp_ssh_user,
							sdp_ssh_password,
							sdp_on_off,
							sdp_master,
							sdp_master_topo,
							sdp_ssh_port,
							sdp_trigram,
							sdp_http,
							sdp_https,
							sdp_https_port)
                                    		VALUES ({$newIdProduct},
		'{$this->productValues['sdp_label']}',
		'{$this->productValues['sdp_ip_address']}',
		'{$this->productValues['sdp_directory']}',
		'{$this->productValues['sdp_db_name']}',
		{$this->productValues['sdp_db_port']},
		'{$this->productValues['sdp_db_login']}',
		'{$this->productValues['sdp_db_password']}',
		'{$this->productValues['sdp_ssh_user']}',
		'{$this->productValues['sdp_ssh_password']}',
		{$this->productValues['sdp_on_off']},
		{$this->productValues['sdp_master']},
		{$this->productValues['sdp_master_topo']},
		{$this->productValues['sdp_ssh_port']},
		'{$this->productValues['sdp_trigram']}',
		{$this->productValues['sdp_http']},
		{$this->productValues['sdp_https']},
		'{$this->productValues['sdp_https_port']}')";
        $this->database->execute($query);
        $this->idProduct = $newIdProduct;
        return $newIdProduct;
    }

    /**
     * Méthode updateUser : met à jour un produit :
     * Sauvegarde les informations contenues dans l'objet
     *
     * 	28/10/2009 GHX
     * 		- Ajout du paramètre $idProduct
     *
     * @param int $idProduct identifiant du produit sur lequel on doit faire la mise à jour (defaul le produit courant)
     * @return : void()
     */
    public function updateProduct($idProduct = null) {
        // Si on a passé un id de produit on fait la mise à jour sur celui-ci
        if ($idProduct != null) {
            $db = Database::getConnection($idProduct);
        } else { // sinon sur le produit courant
            $db = $this->database;
        }
        // Parcours des valeurs
        foreach ($this->productValues as $key => $value) {

            // 20/01/2011 BBX
            // On ne met pas à jour l'id produit
            // BZ 18510
            if ($key == 'sdp_id')
                continue;

            // Mise à jour de l'information
            $value = ($value == '') ? "NULL" : ((is_numeric($value)) ? $value : "'{$value}'");
            $query = "UPDATE sys_definition_product SET {$key} = {$value} WHERE sdp_id = {$this->idProduct}";
            $db->execute($query);
        }
    }

    /*     * **********************************************************************
     * Méthode getCBVersion : récupère la version du CB du produit
     * @return : string : CB version
     * ********************************************************************** */

    public function getCBVersion() {
        // 20/12/2011 ACS BZ 25191 Use Product model database
        // Récupération du produit
        //09/11/2011 MMT Bz 24482 ordonn� par Date pour avoir le dernier CB et non le plus grand
        $query = "SELECT item_value FROM sys_versioning WHERE item = 'cb_version'
		ORDER BY date DESC
		LIMIT 1";
        $result = $this->database->getRow($query);
        return $result['item_value'];
    }

    

    /**
     * Récupère la version du parser du produit
     * @return string parser version
     */
    public function getParserVersion() {
        // Chaine de retour
        $resultString = '';
        // 20/12/2011 ACS BZ 25191 Use Product model database
        // Récupération du produit
        $query = "SELECT item_value FROM sys_versioning WHERE item IN('parser_name')
		LIMIT 1";
        $result = $this->database->getRow($query);
        $resultString = $result['item_value'];
        // Récupération de la version
        $query = "SELECT item_value FROM sys_versioning WHERE item IN('parser_version')
		ORDER BY item_value DESC
		LIMIT 1";
        $result = $this->database->getRow($query);
        if ($result['item_value'] != '') {
            $resultString .= ' v' . $result['item_value'];
        }
        return $resultString;
    }

    /**
     * Retroune le nom de module du produit
     * @return string product module code
     * @example iu, gsm...
     */
    public function getProductName() {
        // 20/12/2011 ACS BZ 25191 Use Product model database
        // récupération du nom du produit
        $query = "SELECT value FROM sys_global_parameters
		WHERE parameters = 'module'";
        $result = $this->database->getRow($query);
        // Retour du produit
        return $result['value'];
    }

    /**
     * Retourne le label du produit
     * @return string product name
     * @example T&A Cigale GPRS
     */
    public function getProductLabel() {
        // récupération du nom du produit
        $query = "SELECT sdp_label FROM sys_definition_product
		WHERE sdp_id = {$this->idProduct}";
        $result = $this->database->getOne($query);
        // Retour du produit
        return $result;
    }

    /**
     * Passe le produit en standalone au niveau de la table sys_definition_product
     * @author BBX
     * BZ 20342
     */
    public function standalone() {
        // BEGIN
        $this->database->execute("BEGIN");
        // All products to 0
        $query = "UPDATE sys_definition_product
                SET sdp_id = 0";
        $this->database->execute($query);
        // Target product to 1
        $query = "UPDATE sys_definition_product
                SET sdp_id = 1
                WHERE sdp_db_name = '{$this->productValues['sdp_db_name']}'";
        $this->database->execute($query);
        // All products to 0 to delete
        $query = "DELETE FROM sys_definition_product
                WHERE sdp_id = 0";
        $this->database->execute($query);
        // Is master and enabled
        $query = "UPDATE sys_definition_product
                SET sdp_master = 1, sdp_master_topo = 1, sdp_on_off = 1";
        $this->database->execute($query);
        // COMMIT
        $this->database->execute("COMMIT");
    }

    /**
     * Supprime le produit slave. La suppression entraine la désactivation automatique du produit.
     * Retourne TRUE si le produit a été supprimé sinon retourne un message d'erreur
     *
     * 	27/10/2009 GHX
     * 		- Correction du BZ 12281
     *
     * @param string $filelog chemin vers le fichier de log (default pas de log)
     */
    public function delete($filelog = null) {
        // On v�rifie que le produit n'est pas le master
        $queryIdMaster = "SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 1";
        $idMaster = $this->database->getOne($queryIdMaster);
        if ($idMaster == $this->idProduct) {
            return __T('A_E_SETUP_PRODUCT_CANNOT_DELETE_MASTER');
        }

        // 22/12/2010 BBX
        // On ne fait pas la désactivation si le produit est déjà désactivé
        // BZ 18510
        // 26/01/2011 BBX
        // On ne désactivé que si le produit a déjà été déployé
        // BZ 20342
        if (self::isProductDeployedOnMaster($this->idProduct))
            $this->deactivation($filelog);

        // 21/12/2010 BBX
        // Si le Mixed KPI est activé
        // Il faut supprimer tous les compteurs faisant référence
        // au produit que nous sommes sur le point de supprimer
        // BZ 18510
        $mkP = self::getIdMixedKpi();
        if ($mkP) {
            // Connexion au Mixed KPI
            $mkDB = Database::getConnection($mkP);

            // 02/02/2011 BBX
            // BZ 20429
            // Les Kpis liés à ce produit deviennent des Kpis standards
            MixedKpiModel::setKpisAsStandard($this->idProduct);
            // Suppression des compteurs dans sys_field_reference_all
            MixedKpiModel::dropSysFieldReferenceAllCountersFromTrigram($this->productValues['sdp_trigram']);
            // FIN BZ 20429
            // 11/10/2011 BBX
            // BZ 20433 : supression de la connexion vers ce produit
            MixedKpiModel::dropProductConnection($this->idProduct);

            // Récupération des compteurs index�s par produit source
            $rawModel = new RawModel();
            foreach ($rawModel->getAll($mkDB) as $rawId => $rawInfos) {
                // Si ce compteur a comme source le produit � supprimer
                if ($rawInfos['sfr_sdp_id'] == $this->idProduct) {
                    // Suppression du compteur
                    $rawModel->drop($rawId, $mkP);
                }
            }
        }
        // Fin BZ 18510
        // Supprime le produit
        // 14:54 27/10/2009 GHX
        // Correction du BZ 12281
        // La suppression du produit doit se faire sur le master et pas sur le produit puisque c'est déjà fait lors de la désactivation
        $dbMaster = Database::getConnection(self::getIdMaster());
        $dbMaster->execute("DELETE FROM sys_definition_product WHERE sdp_id = {$this->idProduct}");

        // Si la connexion au produit à supprimer a échoué on ne tente pas de
        // le passer en standalone (bz25443)
        if (!$this->error) {
            // 26/01/2011 BBX : bz20342, repasse le Slave supprimé en son propre master
            $this->standalone();
        }
        return true;
    }

// End function delete

    /**
     * Désactive un produit
     *
     * 	27/10/2009 GHX
     * 		- Modification des exécutions sur la base de donn�es car maintenant $this->database pointe sur le produit et non sur le master
     * 		- Modifcation d'une requetre SQL
     *
     * @author GHX
     * @version CB 5.0.1.03
     * @since CB 5.0.0.06
     * @param string $filelog chemin vers le fichier de log (default pas de log)
     */
    public function deactivation($filelog = null) {
        // Récupère la liste des Graphes contient au moins un RAW ou KPI du produit à désactivér
        $gtms = GTMModel::getAllContainsIdProduct($this->idProduct);

        $log = '';

        /*
          SUPPRESSION : GRAPH / DASHBORD / RAPPORT / SCHEDULE
         */
        if (count($gtms) > 0) {
            foreach ($gtms as $idGtm => $infoGtm) {
                // Récupère la liste des dashboards qui contient le graphe
                $dashboards = DashboardModel::getDashboardFromGTM($idGtm);
                // Si le graphe est présent dans au moins un dashboard
                if (count($dashboards) > 0) {
                    foreach ($dashboards as $dashboard) {
                        $idDashboard = $dashboard['id_page'];
                        // Récupère la liste des rapports qui contient le dashoard
                        $reports = ReportModel::getReportsIdFromDashboardId($idDashboard);
                        // Si le rapport est présent dans au moins un rapport
                        if (count($reports) > 0) {
                            foreach ($reports as $report) {
                                $idReport = $report['id_page'];
                                $reportModel = new ReportModel($idReport);
                                $nameReport = $reportModel->getProperty('page_name');

                                // Récupère la liste des schedules contenant le rapport
                                $schedules = $reportModel->getSchedules();
                                if (count($schedules) > 0) {
                                    foreach ($schedules as $schedule) {
                                        $scheduleModel = new ScheduleModel($schedule['schedule_id']);
                                        // Supprime le rapport du schedule
                                        $scheduleModel->deleteReport($idReport);
                                        $log .= "\nMaster : Deletion of report '" . $nameReport . "' of schedule : " . $scheduleModel->getProperty('schedule_name');
                                        if (count($scheduleModel->getProperty('report_id')) == 0) {
                                            // Suplprime le schedule s'il est vide
                                            $scheduleModel->delete();
                                            $log .= "\nMaster : Deletion of schedule : " . $scheduleModel->getProperty('schedule_name');
                                        }
                                    }
                                }
                                // Suppression du rapport
                                if ($reportModel->delete() == true)
                                    $log .= "\nMaster : Deletion of report : " . $nameReport;
                            }
                        }
                        // Suppression du dashboard
                        $dashModel = new DashboardModel($idDashboard);
                        $dashName = $dashModel->getName();
                        if ($dashModel->delete() == true)
                            $log .= "\nMaster : Deletion of dashboard : " . $dashName;
                    }
                }
                // Suppression du graphe
                $gtmModel = new GTMModel($idGtm);
                if ($gtmModel->delete() == true)
                    $log .= "\nMaster : Deletion of gtm : " . $infoGtm['page_name'];
            }
        }

        /*
          09:08 28/10/2009 GHX
          Suppression des graphes, dashboards, rapport du slave qui ne sont pas du produit slave
         */
        // 21/12/2010 BBX
        // Ajout d'un test sur l'existance de la connexion au Slave
        // BZ 18510
        $dbTest = Database::getConnection($this->idProduct);
        if (is_resource($dbTest->getCnx())) {
            $allGtm = GTMModel::getAll();
            if (count($allGtm) > 0) {
                foreach ($allGtm as $idGtm => $infoGtm) {
                    // Récupère la liste des dashboards qui contient le graphe
                    $dashboards = DashboardModel::getDashboardFromGTM($idGtm, $this->idProduct);
                    // Si le graphe est présent dans au moins un dashboard
                    if (count($dashboards) > 0) {
                        foreach ($dashboards as $dashboard) {
                            $idDashboard = $dashboard['id_page'];
                            // Récupère la liste des rapports qui contient le dashoard
                            $reports = ReportModel::getReportsIdFromDashboardId($idDashboard, $this->idProduct);
                            // Si le rapport est présent dans au moins un rapport
                            if (count($reports) > 0) {
                                foreach ($reports as $report) {
                                    $idReport = $report['id_page'];
                                    $reportModel = new ReportModel($idReport, $this->idProduct);
                                    $nameReport = $reportModel->getProperty('page_name');

                                    // Récupère la liste des schedules contenant le rapport
                                    $schedules = $reportModel->getSchedules();
                                    if (count($schedules) > 0) {
                                        foreach ($schedules as $schedule) {
                                            $scheduleModel = new ScheduleModel($schedule['schedule_id'], $this->idProduct);
                                            // Supprime le rapport du schedule
                                            $scheduleModel->deleteReport($idReport);
                                            $log .= "\n" . $this->productValues['sdp_label'] . " : Deletion of report '" . $nameReport . "' of schedule : " . $scheduleModel->getProperty('schedule_name');
                                            if (count($scheduleModel->getProperty('report_id')) == 0) {
                                                // Suplprime le schedule s'il est vide
                                                $scheduleModel->delete();
                                                $log .= "\n" . $this->productValues['sdp_label'] . " : Deletion of schedule : " . $scheduleModel->getProperty('schedule_name');
                                            }
                                        }
                                    }
                                    // Suppression du rapport
                                    if ($reportModel->delete() == true)
                                        $log .= "\n" . $this->productValues['sdp_label'] . " : Deletion of report : " . $nameReport;
                                }
                            }
                            // Suppression du dashboard
                            $dashModel = new DashboardModel($idDashboard, 'overtime', $this->idProduct);
                            $dashName = $dashModel->getName();
                            if ($dashModel->delete() == true)
                                $log .= "\n" . $this->productValues['sdp_label'] . " : Deletion of dashboard : " . $dashName;
                        }
                    }
                    // Suppression du graphe
                    $gtmModel = new GTMModel($idGtm, $this->idProduct);
                    if ($gtmModel->delete() == true)
                        $log .= "\n" . $this->productValues['sdp_label'] . " : Deletion of gtm : " . $infoGtm['page_name'];
                }
            }
        }

        /*
          MISE A JOUR DE LA HOMEPAGE : MASTER
         */
        $dbMaster = Database::getConnection(self::getIdMaster());
        // Si le dashboard de la homepage par défaut est supprimée, on met la valeur � NULL
        $dbMaster->execute("UPDATE sys_global_parameters SET value = NULL WHERE parameters = 'id_homepage' AND value NOT IN (SELECT id_page FROM sys_pauto_page_name WHERE page_type='page')");
        if ($dbMaster->getAffectedRows() == 1) {
            $log .= "\n\nMaster : The default homepage has been deleted";
        }
        // On supprime les sélecteurs des homepages par défaut
        $dbMaster->execute("DELETE FROM sys_definition_selecteur WHERE sds_id_page not IN (SELECT id_page FROM sys_pauto_page_name WHERE page_type='page')");
        // On met à NULL les id de s�lecteurs pour les utilisateurs dont la homepage n'existe plus
        // 07/09/2011 BBX
        // Correction d'un cast
        // BZ 23650
        //10/09/2014 - FGD - Bug 43831 - [SUP][TA Gateway][AVP 47571][Zain HQ]: Patch installation on slave deactivate homepage on Master
        //Conservation des liens vers le produit homepage
        $dbMaster->execute("UPDATE users SET homepage = NULL WHERE homepage!='-1' AND homepage NOT IN (SELECT sds_id_selecteur::text FROM sys_definition_selecteur)");

        /*
          MISE A JOUR DE LA HOMEPAGE : SLAVE
         */
        // On supprime les homepages par défaut et des users sur le slave
        // 16:59 27/10/2009 GHX
        // 21/12/2010 BBX
        // Ajout d'un test sur l'existance de la connexion au Slave
        // BZ 18510
        if (is_resource($dbTest->getCnx())) {
            $this->database->execute("UPDATE sys_global_parameters SET value = NULL WHERE parameters = 'id_homepage' AND value NOT IN (SELECT id_page FROM sys_pauto_page_name WHERE page_type='page')");
            //$this->database->execute("DELETE FROM sys_definition_selecteur WHERE sds_id_selecteur IN (SELECT homepage FROM users)");
            $this->database->execute("DELETE FROM sys_definition_selecteur WHERE sds_id_page not IN (SELECT id_page FROM sys_pauto_page_name WHERE page_type='page')");
            // 07/09/2011 BBX
            // Correction d'un cast
            // BZ 23650
            $this->database->execute("UPDATE users SET homepage = NULL WHERE homepage!='-1' AND homepage NOT IN (SELECT sds_id_selecteur::text FROM sys_definition_selecteur)");
        }
        /*
          MISE A JOUR DES PROFILS USERS DU SLAVE
         */
        // Boucle sur tous les profils pour ajouter ce menu et déplace les menus dashboards si n�cessaire
        // 17:06 28/10/2009 GHX
        // Met l'ID menu parent à zéro des menus dont l'ID menu parent n'existe plus
        // 21/12/2010 BBX
        // Ajout d'un test sur l'existance de la connexion au Slave
        // BZ 18510
        if (is_resource($dbTest->getCnx())) {
            $query = "
			UPDATE
				menu_deroulant_intranet
			SET
				id_menu_parent = 0
			WHERE id_menu IN (
					SELECT
						id_menu
					FROM
						menu_deroulant_intranet
					WHERE
						id_menu_parent NOT IN (
								SELECT
									id_menu
								FROM
									menu_deroulant_intranet
								)
						AND libelle_menu NOT IN ('Over Time', 'Over Network Elements')
						AND is_profile_ref_user = 1
						AND droit_affichage != 'astellia'
				)
			";
            $this->database->execute($query);

            $userMenuSlave = MenuModel::getUserMenus($this->idProduct);
            foreach (ProfileModel::getProfiles() as $profil) {
                if ($profil['profile_type'] == 'user') {
                    $ProfileModel = new ProfileModel($profil['id_profile'], $this->idProduct);
                    $ProfileModel->removeProfileMenusFromArray($userMenuSlave);
                    $ProfileModel->addMenuListToProfile($userMenuSlave);
                    $ProfileModel->checkMandatoryMenus();
                    $ProfileModel->buildProfileToMenu();
                }
            }
            $log .= "\n\nThe users profiles have been reinitialised on the product " . $this->productValues['sdp_label'];
        }
        /*
          MISE A JOUT DES PROFILS USERS DU MASTER
          10:21 25/08/2009 GHX
         */
        // récupère la liste des menus qui non pas de sous-menu
        $query = "
			SELECT
				id_menu,
				libelle_menu,
				id_menu_parent
			FROM
				menu_deroulant_intranet
			WHERE
				id_menu NOT IN (
							SELECT
								id_menu_parent
							FROM
								menu_deroulant_intranet
							)
				AND libelle_menu NOT IN ('Over Time', 'Over Network Elements')
				AND is_profile_ref_user = 1
				AND droit_affichage != 'astellia'
				AND id_page IS NULL
			";
        $menusEmpty = $dbMaster->getAll($query);
        $menusEmptyParent = array();
        foreach (ProfileModel::getProfiles() as $profil) {
            if ($profil['profile_type'] == 'user') {
                $ProfileModel = new ProfileModel($profil['id_profile']);
                foreach ($menusEmpty as $menuEmpty) {
                    $ProfileModel->removeMenuFromProfile($menuEmpty['id_menu']);
                    $menusEmptyParent[] = $menuEmpty['id_menu_parent'];

                    $query2 = "
						SELECT
							COUNT(*),
							id_menu_parent
						FROM
							profile_menu_position
						WHERE
							 id_profile = '" . $profil['id_profile'] . "'
							AND id_menu_parent = '" . $menuEmpty['id_menu_parent'] . "'
						GROUP BY
							id_menu_parent
						";
                    $result2 = $dbMaster->getAll($query2);
                    if (count($result2) == 0) {
                        $ProfileModel->removeMenuFromProfile($menuEmpty['id_menu_parent']);
                    }
                }
                $ProfileModel->checkMandatoryMenus();
                $ProfileModel->buildProfileToMenu();
            }
        }

        /*
          SLAVE => MASTER MONO
         */
        // 21/12/2010 BBX
        // Ajout d'un test sur l'existance de la connexion au Slave
        // BZ 18510
        if (is_resource($dbTest->getCnx())) {
            // Supprime tous les autres produits du slave
            $this->database->execute("DELETE FROM sys_definition_product WHERE sdp_id != {$this->idProduct}");
            // On redéfinie le produit slave comme un mono produit, du coup il redevient le master produit et master topo
            $this->database->execute("UPDATE sys_definition_product SET sdp_on_off = 1, sdp_id = 1, sdp_master = 1, sdp_master_topo = 1");
            // On met la colonne id_produit de sys_pauto_config a 1 (normalement pas nécessaire)
            // 16:56 27/10/2009 GHX : Maintenant il est nécessaire de faire l'update
            $this->database->execute("UPDATE sys_pauto_config SET id_product = 1");
        }
        // 11:25 28/10/2009 GHX
        // Désactive le produit
        $dbMaster->execute("UPDATE sys_definition_product SET sdp_on_off = 0 WHERE sdp_id = {$this->idProduct}");

        // 21/12/2010 BBX
        // Ajout d'un test sur l'existance de la connexion au Slave
        // BZ 18510
        if (is_resource($dbTest->getCnx()))
            $log .= "\n\nIt's now possible to connect directly on the product " . $this->productValues['sdp_label'];

        // 14/12/2010 BBX
        // BZ 18510
        // Désactivation manuelle : sdp_automatic_activation doit etre 0
        $dbMaster->execute("UPDATE sys_definition_product SET sdp_automatic_activation = 0 WHERE sdp_id = {$this->idProduct}");

        // maj 17/05/2010 - MPR : Correction du bz 15469 - Les menus du produit désactivé ou supprimé apparaissent dans profile management
        $menu = new MenuModel(0);
        $id_menu_to_delete = $menu->getRootUserMenus($this->productValues['sdp_id']);
        $m = new MenuModel($id_menu_to_delete[0]);
        $m->deleteMenu();
        $log .= "\n" . "Master : Deletion of main menu " . $id_menu_to_delete[0];
        /*
          MAPPING DE LA TOPOLOGIE
         */
        // On vide tout le mapping si c'est le master topo
        if ($this->productValues['sdp_master_topo'] == 1) {
            $mapping = new Mapping('');
            $errorMapping = false;
            // On s'assure que les autres produits ne sont plus master topo
            foreach (ProductModel::getProducts() as $product) {
                try {
                    $mapping->setProductMapped($product);
                    $mapping->truncate();
                } catch (Exception $e) {
                    $errorMapping = true;
                    $log .= "\nTruncate mapping : " . $e->getMessage();
                }
            }

            if ($errorMapping === false) {
                $log .= "\n\nThe mapping has been deleted for all products and the product master is now a topology master";
            }

            // On rédéfinie le master produit comme master topo par défaut
            $query = "UPDATE sys_definition_product SET sdp_master_topo = 1 WHERE sdp_master = 1";
            $dbMaster->execute($query);
        } else { // Sinon on vide juste le mapping du slave
            try {
                $mapping = new Mapping('');
                $mapping->setProductMapped($this->idProduct);
                $mapping->truncate();
                $log .= "\n\nThe mapping has been deleted for the product " . $this->productValues['sdp_label'];
            } catch (Exception $e) {
                $log .= "\nTruncate mapping : " . $e->getMessage();
            }
        }

        // Création du fichier de log si demandé
        if ($filelog !== null && $filelog != '') {
            $pathInfod = pathinfo($filelog);
            // On vérifie que le répertoire existe
            if (file_exists($pathInfod['dirname']) && is_dir($pathInfod['dirname'])) {
                // on v�rifie qu'on a les droits d�criture sur le r�pertoire
                if (is_writable($pathInfod['dirname'])) {
                    file_put_contents($filelog, $log, FILE_APPEND);
                }
            }

            // 22/12/2010 BBX
            // Ajout des infos au tracelog
            // BZ 18510
            $message = "{$this->productValues['sdp_label']} has been disactivated. See $filelog for details.";
            sys_log_ast('Warning', 'Trending&Aggregation', 'Database', $message, 'support_1', '');
        }
    }

// End function deactivation

    /**
     * D�finit le produit comme DEF
     *
     * @author BBX
     */
    public function setAsDef() {
        // Connexion à la base du produit (bz21126)
        $database = Database::getConnection($this->idProduct);
        $query = "UPDATE sys_global_parameters SET value = 'def' WHERE parameters = 'module'";
        $database->execute($query);
    }

    /**
     * Retourne le code module version numérique du produit
     *
     * @author BBX
     * @return int
     * @example 4 pour Gsm, 16 pour Iu...
     */
    public function getCode($nodef = false) {
        $query = "SELECT saai_interface 
                FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (value = saai_module) 
                WHERE parameters = 'module'";

        // 18/04/2012 BBX
        // BZ 21945 : ajout d'un paramètre permettan de récupérer le code produit
        // même si le produit a été transformé en Corporate
        if ($nodef) {
            $query = "SELECT saai_interface 
                FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (value = saai_module) 
                WHERE parameters IN ('old_module','module')
                ORDER BY parameters DESC
                LIMIT 1";
        }

        return $this->database->getOne($query);
    }

    /**
     * Retourne le code module version numérique du produit avant passage en Corporate
     *
     * @return int
     * @example 4 pour Gsm, 16 pour Iu...
     */
    public function getOldCode() {
        $query = "SELECT saai_interface FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (value = saai_module) WHERE parameters = 'old_module'";
        return $this->database->getOne($query);
    }

    /**
     * Restore le old module s'il existe
     * @author BBX
     */
    public function setOldModule() {
        // utilisation du bon product id et connexion à la base du produit (bz21126)
        $oldModule = get_sys_global_parameters('old_module', '', $this->idProduct);
        if ($oldModule != '') {
            $database = Database::getConnection($this->idProduct);
            $query = "UPDATE sys_global_parameters SET value = '$oldModule' WHERE parameters = 'module'";
            $database->execute($query);
        }
    }

    /**
     * Mixage des données du contexte du slave avec le master
     * 20/01/2011 BBX
     * Ajout de cette méthode à ccette classe
     * BZ 18510
     * @author GHX
     * @return string chemin vers le fichier de log
     */
    public function activationContext() {
        // Démarrage du buffer
        ob_start();

        //repertoire d'upload des contextes
        $directory = REP_PHYSIQUE_NIVEAU_0 . 'upload/context/';

        // Par pr�caution on vérifie l'existance du répertoire
        if (!file_exists($directory)) {
            mkdir($directory, 0777);
        }

        // Toujours par précaution on met tous les droits sur le r�pertoire
        @chmod($directory, 0777);

        // Traitement
        try {
            $ctxAct = new ContextActivation($this->idProduct);
            $ctxAct->setDirectory($directory);
            $ctxAct->activation();
        } catch (Exception $e) {
            echo "\n\nERROR : " . $e->getMessage();
        }

        // Fin du buffer
        $str = ob_get_contents();
        ob_end_clean();

        //ecriture des logs
        $date = date("Ymd");
        $filename = str_replace(' ', '_', $this->productValues['sdp_label']);
        $log_name = $date . "_activation_product_" . $filename . ".log";
        file_put_contents($directory . $log_name, $str, FILE_APPEND);

        return NIVEAU_0 . 'upload/context/' . $log_name;
    }

    /*     * ********************** STATIC FUNCTIONS ************************* */

    /**
     *
     * Méthode deploy : recopie la table sys_definition_product sur tous les produits
     * 20/01/2011 BBX
     * Réécriture de la méthode qui ne fonctionnait pas dans tous les cas
     * Dans le cadre du BZ 18510
     * @return : void()
     */
    public static function deployProducts() {
        // Connexion à la base de données Master
        $database = Database::getConnection(0);

        // Id Du Master
        $masterId = ProductModel::getIdMaster();

        // Récupération de la table sys_definition_product du master
        $allProducts = self::getProducts();

        // Récupération de la table sys_definition_product du Master
        foreach ($allProducts as $product) {
            // On ne traite pas le master
            if ($product['sdp_id'] == $masterId)
                continue;

            // Connexion au produit
            $slaveDb = Database::getConnection($product['sdp_id']);
            // 20/12/2011 ACS BZ 25191 Don't deploy product configuration on a slave which connection is KO
            if ($slaveDb->isConnectionOk()) {

                // On récupère les colonnes existantes dans sys_definition_product
                // Pour la Slave courant. En effet, il se peut que les colonnes
                // diff�rent si le Slave n'est pas dans la même version que le master
                $existingColumns = $slaveDb->getColumns('sys_definition_product');

                // On vide la table sys_definition_product
                $slaveDb->execute("TRUNCATE TABLE sys_definition_product");

                // Insertion des informations du master dans le table du Slave
                foreach ($allProducts as $rowToInsert) {
                    // Ajout des colonnes qui existent sur le Slave, mais
                    // pas sur le master
                    foreach ($existingColumns as $slaveColumn) {
                        // La colonne courante n'existe pas sur le Master
                        // On l'ajoute avec une valeur par défaut
                        if (!array_key_exists($slaveColumn, $rowToInsert)) {
                            // Valeur vide par défaut
                            $rowToInsert[$slaveColumn] = '';
                        }
                    }

                    // Suppression des colonnes qui existent sur le Master
                    // Et qui n'existent pas sur le Slave
                    foreach ($rowToInsert as $column => $value) {
                        // la colonne existe sur le Master, mais pas sur le Slave
                        if (!in_array($column, $existingColumns)) {
                            // On détruit l'entrée
                            unset($rowToInsert[$column]);
                        }
                    }

                    // Enfin, on traite les valeurs afin de remplacer les valeurs vides
                    // par NULL et d'ajouter des quotes autour des valeurs de type chaine
                    foreach ($rowToInsert as $column => $value) {
                        // Type de colonne
                        $fieldType = $database->getFieldType('sys_definition_product', $column);

                        // Les valeurs vides deviennent NULL ou 0
                        if (empty($value)) {
                            $rowToInsert[$column] = '0';
                            if (in_array($fieldType, array('text', 'character', 'varchar', 'bpchar'))) {
                                $rowToInsert[$column] = 'NULL';
                            }
                        }

                        // les valeurs chaines prennent des quotes
                        if (in_array($fieldType, array('text', 'character', 'varchar', 'bpchar'))) {
                            $rowToInsert[$column] = "'$value'";
                        }
                    }

                    // Finalement, on génère notre requête INSERT
                    $query = "INSERT INTO sys_definition_product ";
                    $query .= "(" . implode(', ', array_keys($rowToInsert)) . ")";
                    $query .= " VALUES (" . implode(', ', array_values($rowToInsert)) . ")";

                    // Puis on l'éxécute
                    $slaveDb->execute($query);
                }
            }
        }
    }

    /**
     * Retourne la liste des produits (sans distinction du on_off)
     *
     * @param boolean $includeBlankProduct Indique si l'on souhaite le produit blanc dans la liste
     * @return type array Liste des produits
     * 
     * OJT DE Produit Blanc
     */
    public static function getProducts($includeBlankProduct = true) {
        $whereClause = '';
        if (( $includeBlankProduct == false ) && ( ProductModel::isBlankProduct(ProductModel::getIdMaster()) )) {
            // On exclut le master si on ne veut pas du produit blanc
            $whereClause = 'WHERE sdp_master != 1';
        }
        $database = Database::getConnection(0);
        $query = "SELECT * FROM sys_definition_product {$whereClause} ORDER BY sdp_master DESC, sdp_master_topo DESC, sdp_label";
        return $database->getAll($query);
    }

    /*     * **********************************************************************
     * Méthode getActiveProducts : retourne la liste des produits activés
     * @return : array	liste des utilisateurs
     * ********************************************************************** */

    // 21/12/2011 ACS BZ 25191 Warnings displayed in Schedule menu
    public static function getActiveProducts($onlySlaves = false) {
        $whereClause = '';
        if ($onlySlaves) {
            $whereClause = "AND sdp_master = 0";
        }
        $database = Database::getConnection(0);
        $query = "SELECT * FROM sys_definition_product WHERE sdp_on_off=1 $whereClause ORDER BY sdp_master DESC, sdp_master_topo DESC, sdp_label";
        return $database->getAll($query);
    }

    /*     * **********************************************************************
     * Méthode setAsMaster : définit un produit comme maitre
     * @param : int id produit
     * @return : void()
     * ********************************************************************** */

    public static function setAsMaster($idProd) {
        $database = Database::getConnection(0);
        $query = "UPDATE sys_definition_product SET sdp_master = 1, sdp_on_off = 1 WHERE sdp_id = {$idProd}";
        $database->execute($query);
    }

    /** Get products label
     * @param [$active] : true pour récupérer seulement les produits actif
     * @return array : key: ProductId, value: product label
     * Example: 	[1] -> GSM
     * 				[2] -> TA Cigal Iu
     * @author SPD1
     * 
     */
    public static function getProductsLabel($active = false) {

        // Connexion à la base de données Master
        $database = Database::getConnection(0);

        $where = $active ? 'WHERE sdp_on_off=1' : '';

        // Get all products		
        $sql = "SELECT sdp_id, sdp_label FROM sys_definition_product $where ORDER BY sdp_master DESC, sdp_master_topo DESC, sdp_label";

        $results = $database->getAll($sql);

        $productsLabel = array();

        // Create an array with key productId and value productName
        foreach ($results as $row) {
            $productsLabel[$row['sdp_id']] = $row['sdp_label'];
        }

        return $productsLabel;
    }

    /** Get product by Id
     * @param id : the productId
     * @return array : product row
     * @author SPD1
     */
    public static function getProductById($id) {

        // Connexion à la base de données Master
        $database = Database::getConnection(0);

        // Get all products		
        $sql = "SELECT * FROM sys_definition_product WHERE sdp_id = $id";
        $results = $database->getRow($sql);

        return $results;
    }

    /**
     * Retourne l'identifiant du master
     *
     * @author GHX
     * @return int
     */
    public static function getIdMaster() {
        $database = Database::getConnection(0);
        $query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 1";
        return $database->getOne($query);
    }

// End function getIdMaster

    /**
     * Retourne l'identifiant du master topo
     *
     * @author BBX
     * @return int
     */
    public static function getIdMasterTopo() {
        $database = Database::getConnection(0);
        $query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_master_topo = 1";
        return $database->getOne($query);
    }

// End function getIdMaster

    /**
     * Retourne l'identifiant du produit Mixed KPI, si le produit Mixed KPI n'existe pas FALSE est retourné
     *
     * @author GHX
     * @return int
     */
    public static function getIdMixedKpi() {
        $database = Database::getConnection(0);
        $query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_directory ILIKE '%mixed_kpi_product'";
        $id = $database->getOne($query);
        if ($id)
            return $id;
        return false;
    }

// End function getIdMixedKpi

    /**
     * Fonction qui récupère l'id du produit à partir du trigramme
     *
     * @param string $trigram
     * @return integer/false
     */
    public static function getIdProductFromTrigram($trigram) {
        $database = Database::getConnection(0);
        $query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_trigram = '{$trigram}'";
        $id = $database->getOne($query);
        if ($id)
            return $id;
        return false;
    }

    /**
     * Retourne l'id d'un produit en fonction d'un nom de module (iu, gsm...) La
     * recherche n'est pas sensible à la casse. Le paramètre old_module est
     * utilisé si le module d'un produit vaut 'def'.
     * Retourne false si le module est nom trouv�
     *
     * @param  string $module Nom du module ('def' ne doit pas �tre utilis�)
     * @return integer Identifiant du produit (false si module inconnu)
     */
    public static function getIdProductFromModule($module) {
        $idProduct = false;
        $products = self::getProducts();
        while ($idProduct === false && count($products) > 0) {
            $tmpProd = array_shift($products);
            $tmpMod = get_sys_global_parameters('module', 0, $tmpProd['sdp_id']);

            // Gestion du cas du module 'def'
            if ($tmpMod === 'def') {
                $tmpMod = get_sys_global_parameters('old_module', 0, $tmpProd['sdp_id']);
            }

            if ($module === $tmpMod) {
                $idProduct = intval($tmpProd['sdp_id']);
            }
        }
        return $idProduct;
    }

    /**
     * Restaure l'ancien code module sur un produit def et retourne un tableau des produits modifi�s
     *
     * @author BBX
     * @return array
     */
    // 21/12/2011 ACS BZ 25191 Warnings displayed in Context management menu
    public static function restoreOldModule() {
        // Stocke les produits modifi�s
        $productDef = Array();
        // Pour tous les produits
        foreach (self::getActiveProducts() as $product) {
            // Si le produit est de type DEF, on restaure l'ancien module
            $productType = get_sys_global_parameters('module', 0, $product['sdp_id']);
            if ($productType == 'def') {
                // On se connecte à la base produit
                $db_temp = Database::getConnection($product['sdp_id']);
                // On vérifie si le paramètre 'old_module' existe (corporate uniquement).
                $query = "SELECT value FROM sys_global_parameters WHERE parameters = 'old_module'";
                $db_temp->execute($query);
                if ($db_temp->getNumRows() > 0) {
                    $query = "UPDATE sys_global_parameters
					SET value = (SELECT value FROM sys_global_parameters WHERE parameters = 'old_module')
					WHERE parameters = 'module'";
                    $db_temp->execute($query);
                    $productDef[] = $product['sdp_id'];
                }
            }
        }
        // Retour du tableau des produits modifiés
        return $productDef;
    }

    /**
     * D�finit le produit qui devient le master topologie
     *
     * 	21/10/2009 GHX
     * 		- Ajout de la fonction
     *
     * @author GHX
     * @param string $idProduct identifiant du produit qui devient le master topologie
     */
    public static function setAsMasterTopology($idProduct) {
        $db = Database::getConnection(0);
        $db->execute("UPDATE sys_definition_product SET sdp_master_topo = 0");
        $db->execute("UPDATE sys_definition_product SET sdp_master_topo = 1 WHERE sdp_id = " . $idProduct);
        self::deployProducts();
    }

// End function setAsMasterTopology

    /**
     * Lance le script clean_tables_structure.php
     *
     * 	15:12 09/12/2009 GHX
     * 		- Cr�ation de la fonction pour corriger le bug BZ 13225
     */
    public function launchCleanTablesStructure() {
        if ($this->productValues['sdp_ip_address'] == get_adr_server() || $this->productValues['sdp_ip_address'] == '127.0.0.1' || $this->productValues['sdp_ip_address'] == 'localhost') {
            // Lancement le script en local
            exec('php -q /home/' . $this->productValues['sdp_directory'] . '/scripts/clean_tables_structure.php');
        } else {
            // Lancement du script sur le serveur distant
            try {
                $SSH = new SSHConnection($this->productValues['sdp_ip_address'], $this->productValues['sdp_ssh_user'], $this->productValues['sdp_ssh_password'], $this->productValues['sdp_ssh_port']);
                $SSH->exec('php -q /home/' . $this->productValues['sdp_directory'] . '/scripts/clean_tables_structure.php');
            } catch (Exception $e) {
                
            }
        }
    }

// End function launchCleanTablesStructure

    /**
     * Insère des trigrammes par défaut pour les produits hors MK
     * 25/03/2010
     * @author BBX
     */
    public static function generateTrigrams() {
        // Récupération du produit Mixed KPI
        $idMixedKpi = self::getIdMixedKpi();

        // Tableau des trigrammes
        $trigrams = Array();
        $newTrigrams = Array();

        // Pour tous les produits
        $activeProducts = self::getActiveProducts();
        foreach ($activeProducts as $prod) {
            // Si on est pas sur le produit Mixed KPI
            if ($prod['sdp_id'] != $idMixedKpi) {
                // Connexion au produit
                $database = Database::getConnection($prod['sdp_id']);

                // On récupère la valeur actuelle
                $query = "SELECT sdp_trigram FROM sys_definition_product
				WHERE sdp_id = {$prod['sdp_id']}";

                // 26/07/2010 BBX
                // Ajout d'un trim sur le trigramme récupéré
                // BZ 16784
                $trigram = trim($database->getOne($query));

                // Si le trigramme est vide, on en génère un
                if (empty($trigram)) {
                    // Récupération du module
                    $query = "SELECT value
						FROM sys_global_parameters
						WHERE parameters IN ('old_module','module')
						ORDER BY parameters DESC
						LIMIT 1";
                    $module = $database->getOne($query);

                    // Génération du trigramme
                    $trigram = substr(trim($module) . 'ppp', 0, 3);
                }

                // Mémorisation du trigramme
                $trigrams[$prod['sdp_id']] = $trigram;
            }
        }

        // D�doublonnage des trigrammes
        $double = true;
        while ($double) {
            $nbDoubles = 0;
            // On parcours le tableau des trigrammes
            foreach ($trigrams as $idProd => $trigram) {
                // Et on compare toutes ses valeurs
                foreach ($trigrams as $idProdTest => $trigramTest) {
                    // Si on est sur 2 produit diff�rents
                    if ($idProd != $idProdTest) {
                        // Mais que l'on a 2 trigrammes identiques
                        if ($trigram == $trigramTest) {
                            // On modifie notre trigramme
                            $trigrams[$idProd] = substr($trigram, 0, 2) . chr(mt_rand(97, 122));
                            // On comptabilise les doublons d�tect�s
                            $nbDoubles++;
                        }
                    }
                }
            }
            // Si plus de doublons, on sort
            if ($nbDoubles == 0)
                $double = false;
        }

        // Connexion au master
        $database = Database::getConnection(0);

        // Insertion des nouveaux trigrammes en base
        foreach ($trigrams as $idProd => $trigram) {
            // 26/01/2011 BBX
            // Mise à jour plus robuste des trigrammes
            // BZ 18111
            $query = "UPDATE sys_definition_product SET sdp_trigram = '{$trigram}'
			WHERE sdp_id = '{$idProd}'
			AND (sdp_trigram IS NULL
                        OR trim(both ' ' from sdp_trigram) = '')";
            $database->execute($query);
        }

        // 26/01/2011 BBX
        // On force un trigramme vide pour les produits non traités
        // Et qui n'ont pas encore de trigramme
        // BZ 18111
        // 19/12/2012 GFS - BZ#18111 [MIXED KPI]: pas de trigramm par défaut sur le slave
        $query = "UPDATE sys_definition_product SET sdp_trigram = ''
        	WHERE (sdp_trigram IS NULL
                OR trim(both ' ' from sdp_trigram) = '')";
        $database->execute($query);
    }

    /**
     * V�rifie si un trigramme existe déjà
     * 25/03/2010
     * @author BBX
     */
    public static function trigramExists($trigram, $exclude = '') {
        // Connexion au master
        $database = Database::getConnection(0);

        // Requête
        // 26/01/2011 BBX
        // Le test doit détecter les doublons avec casse différente
        // BZ 18111
        $query = "SELECT sdp_label FROM sys_definition_product
		WHERE sdp_trigram ILIKE '{$trigram}'
		AND sdp_id != '{$exclude}'";
        $database->execute($query);

        // Retour
        return $database->getOne($query);
    }

    /**
     * D�sactive les process sur le produit courant et mémorise les process désactivés
     * 25/03/2010
     * @author BBX
     */
    public function disableProcess() {
        // Processes
        $this->processes = array();

        // Récupération des process auto
        $query = "SELECT master_id FROM sys_definition_master WHERE on_off = 1";
        $result = $this->database->execute($query);

        // Mémorisation des process
        while ($array = $this->database->getQueryResults($result, 1)) {
            $this->processes[] = $array['master_id'];
        }

        // Désactivation
        $query = "UPDATE sys_definition_master SET on_off = 0";
        $this->database->execute($query);
    }

    /**
     * Réactive les process désactivés
     * 25/03/2010
     * @author BBX
     */
    public function enableProcess() {
        if (isset($this->processes)) {
            foreach ($this->processes as $p) {
                $query = "UPDATE sys_definition_master SET on_off = 1
				WHERE master_id = {$p}";
                $this->database->execute($query);
            }
        }
    }

    /**
     * Déploiement du la modification du trigramme sur le produit Mixed KPI
     * 25/03/2010
     * @author BBX
     *
     */
    public static function updateTrigramInMixedKpiProduct($oldTrigram, $newTrigram) {
        // Id du produit Mixed KPI
        $mikedKpiId = self::getIdMixedKpi();

        // Instance MK
        $productMK = new ProductModel($mikedKpiId);

        // 1) Désactivation des Process
        $productMK->disableProcess();

        // 2) Mise à jour des DataExport
        $mixedKpiModel = new MixedKpiModel();
        $cfg = new MixedKpiCFG();
        $cfg->setTaMin($mixedKpiModel->getTaMin());
        $cfg->generateAndSendForAllProducts();

        // 28/01/2011 BBX
        // Utilisation de l'ancien trigramme
        // car on teste désormais les champs avant de sauvegarder les valeurs en base
        // à ce stade le nouveau trigramme n'est donc pas encore enregistré
        // BZ 20422
        $idProduct = self::getIdProductFromTrigram($oldTrigram);

        // 3) Mise à jour des préfixes des compteurs
        // maj 07/05/2010 MPR - Correction du bz 15316 : Ajout du paramètre idProdParent
        $result = RawModel::renameCounters($oldTrigram, $newTrigram, $oldTrigram, $newTrigram, $mikedKpiId, $idProduct);

        // 4) Réactivation des Process
        $productMK->enableProcess();

        // Retour du résultat d'éxécution
        return $result;
    }

    /**
     * Determine si un dossier est une application T&A
     * @param String $path
     * @param SSHConnection $sshConnection
     * @return Boolean
     */
    public static function isTaDirectory($path, $sshConnection = NULL) {
        // Via une connexion local
        if ($sshConnection === NULL) {
            if (!is_dir($path)) {
                self::$lastErrorCode = -1;
                return FALSE; // Le dossier n'existe pas
            }

            if (!file_exists($path . '/class/DataBaseConnection.class.php')) {
                self::$lastErrorCode = -2;
                return FALSE; // Le dossier n'est pas une application T&A
            }
            return TRUE;
        }

        // Via une connexion SSH
        if (!$sshConnection->fileExists($path)) {
            self::$lastErrorCode = -1;
            return FALSE; // Le dossier n'existe pas
        }

        if (!$sshConnection->fileExists($path . '/class/DataBaseConnection.class.php')) {
            self::$lastErrorCode = -2;
            return FALSE; // Le dossier n'est pas une application T&A
        }
        return TRUE;
    }

    /**
     *
     * @param boolean $productId id du produit concerné
     * @param boolean $testMaster test master (vrai / faux)
     * @param boolean $testSlaves test des slaves (vrai / faux)
     * @param boolean $testMk test mixed kpi (vrai / faux)
     * @return boolean résultat du test
     */
    public static function checkConnection($productId, $testMaster = true, $testSlaves = true, $testMk = true) {
        // Tableau des connexions à tester
        $connectionsToTest = array();

        // Connexion au produit concerné
        $connectionsToTest[$productId] = Database::getConnection($productId);

        // Connexion au master
        if ($testMaster) {
            $masterId = self::getIdMaster();
            $connectionsToTest[$masterId] = Database::getConnection($masterId);
        }

        // Mixed Kpi
        $mixedKpiId = self::getIdMixedKpi();
        if ($testMk && $mixedKpiId) {
            $connectionsToTest[$mixedKpiId] = Database::getConnection($mixedKpiId);
        }

        // Connection aux slaves
        if ($testSlaves) {
            foreach (self::getActiveProducts() as $row) {
                if (($row['sdp_master'] != 1) && ($row['sdp_id'] != $mixedKpiId)) {
                    $connectionsToTest[$row['sdp_id']] = Database::getConnection($row['sdp_id']);
                }
            }
        }

        // Lancement des tests
        foreach ($connectionsToTest as $pId => $database) {
            $queryTest = "SELECT 'ok'::text;";
            if ($database->getOne($queryTest) != 'ok') {
                self::$lastFailedProduct = $pId;
                return false;
            }
        }

        // Si tout est ok
        return true;
    }

    /**
     * Retourne l'id du produit courant
     */
    public static function getProductId() {
        $database = DataBase::getConnection();
        $dbName = $database->getDbName();
        $query = "SELECT sdp_id FROM sys_definition_product
            WHERE sdp_db_name = '$dbName';";
        return $database->getOne($query);
    }

    /**
     *  Quick desactivation of a product
     *  Will be used by automatic disactivation process
     *
     *  @author BBX
     *  BZ 18510
     *  @param int id product
     */
    public static function fastDesactivation($idProduct, $updateTime = true) {
        // Connects to Master
        $db = Database::getConnection();

        // Set to off
        $query = "UPDATE sys_definition_product
                SET sdp_on_off = 0,
                sdp_last_desactivation = " . ($updateTime ? time() : '0') . "
                WHERE sdp_id = " . $idProduct;

        // Executing...
        $db->execute($query);
    }

    /**
     *  Quick activation of a product
     *  Will be used by automatic disactivation process
     *
     *  @author BBX
     *  BZ 18510
     *  @param int id product
     */
    public static function fastActivation($idProduct) {
        // Connects to Master
        $db = Database::getConnection();

        // Set to on
        $query = "UPDATE sys_definition_product
                SET sdp_on_off = 1,
                sdp_last_desactivation = 0
                WHERE sdp_id = " . $idProduct;

        // Executing...
        $db->execute($query);
    }

    /**
     * Returns list of products automatically disabled
     * and that must be automatically enabled
     *
     *  @author BBX
     *  BZ 18510
     *  @return array list of products to activate
     */
    public static function getProductsToReactivate() {
        // Connects to Master
        $db = Database::getConnection();

        // Fetching delay
        $delay = get_sys_global_parameters('automatic_product_activation_delay');

        // List of products
        $products = array();
        $query = "SELECT sdp_id, sdp_label
                FROM sys_definition_product
                WHERE sdp_on_off = 0
                AND sdp_automatic_activation = 1
                AND sdp_last_desactivation IS NOT NULL
                AND sdp_last_desactivation != 0
                AND sdp_last_desactivation <= " . (time() - $delay);
        $result = $db->execute($query);

        // Listing
        while ($row = $db->getQueryResults($result, 1)) {
            $products[$row['sdp_id']] = $row['sdp_label'];
        }

        // Returning List
        return $products;
    }

    /**
     * Will return list of disabled products
     *
     * @author BBX
     * BZ 18510
     * @return array Disabled products list
     */
    public static function getInactiveProducts() {
        // Connects to Master
        $db = Database::getConnection();

        // Products to off
        $query = "SELECT * FROM sys_definition_product
                WHERE sdp_on_off = 0";
        $result = $db->execute($query);

        // Products
        $products = array();
        while ($row = $db->getQueryResults($result, 1)) {
            $products[] = $row;
        }

        // Returning list
        return $products;
    }

    /**
     * Returns a product ID from a database name / host
     * BZ 18510
     * @param string $dbName
     * @param string $host
     * @return integer
     */
    public static function getProductFromDatabase($dbName, $host) {
        // Connects to Master
        $db = Database::getConnection();

        // Products to off
        $query = "SELECT sdp_id FROM sys_definition_product
                WHERE sdp_db_name = '$dbName'
                AND sdp_ip_address = '$host'";

        // Product ID
        $product = $db->getOne($query);
        if (empty($product))
            $product = 0;

        // Returning product ID
        return $product;
    }

    /**
     * Returns a product ID from a product label
     * @param string $productLabel
     * @param string $host
     * @return integer
     */
    public static function getProductFromLabel($productLabel) {
        // Connects to Master
        $db = Database::getConnection();

        // Products to off
        $query = "SELECT sdp_id FROM sys_definition_product
                WHERE sdp_label = '$productLabel'";

        // Product ID
        $product = $db->getOne($query);
        if (empty($product))
            $product = 0;

        // Returning product ID
        return $product;
    }

    /**
     * Returns true if given product has been automatically disabled
     * @author BBX
     * BZ 18510
     * @param int $productId
     */
    public static function isAutomaticallyDisabled($productId) {
        // Connects to Master
        $db = Database::getConnection();

        // Products to off
        $query = "SELECT sdp_id FROM sys_definition_product
                WHERE sdp_id = $productId
                AND sdp_on_off = 0
                AND sdp_last_desactivation > 0";
        $db->execute($query);

        // Returning product ID
        return ($db->getNumRows() > 0);
    }

    /**
     * Updates sdp_automatic_activation value
     * @author BBX
     * BZ 18510
     * @param int $productId
     */
    public static function updateAutomaticActivationStatus($productId, $aav) {
        // Connects to Master
        $db = Database::getConnection();

        // Products to off
        $query = "UPDATE sys_definition_product
                SET sdp_automatic_activation = $aav
                WHERE sdp_id = $productId";
        $db->execute($query);
    }

    /**
     * Returns true if the given product configuration is compatible
     * with Slave status
     * BZ 19803
     * @author BBX
     * @param integer $productId
     * @return boolean
     */
    // 21/12/2011 ACS BZ 25191 PSQL error with disabled product
    public static function isSlave($productId, $db = null) {
        if ($db == null) {
            // Connects to Product
            $db = Database::getConnection($productId);
        }

        // Test number of lines in sys_definition_product
        $partOfMP = self::isProductPartOfMultiproduct($productId, $db);

        // Test master reference in sys_definition_product
        $query = "SELECT sdp_id FROM sys_definition_product
                WHERE sdp_id = $productId
                AND sdp_master = 0";
        $db->execute($query);
        $reference = $db->getNumRows();

        // Returns Slave status
        return ($partOfMP && $reference == 1);
    }

    /**
     * Returns true if elements from Slave exist
     * @param integer $productId
     * @return boolean
     */
    public static function isProductDeployedOnMaster($productId) {
        // Fetching master product id
        $masterId = ProductModel::getIdMaster();

        // Comparison with current id
        if ($productId == $masterId)
            return true;

        // Connects to master
        $db = Database::getConnection($masterId);

        // Reads elements for current id
        $query = "SELECT id_elem
                FROM sys_pauto_config
                WHERE id_product = $productId
                GROUP BY id_elem";
        $db->execute($query);

        // Returns true if elements from Slave exist
        return ($db->getNumRows() > 0);
    }

    /**
     * Returns true if product is active
     * @param integer $productId
     * @return boolean
     */
    public static function isActive($productId) {
        // Connects to Master
        $db = Database::getConnection();

        // Test number of lines in sys_definition_product
        $query = "SELECT sdp_id FROM sys_definition_product
                WHERE sdp_id = $productId
                AND sdp_on_off = 1";
        $db->execute($query);

        // Returns result
        return $db->getNumRows();
    }

    /**
     * Updates sdp_last_desactivation values
     * BZ 18510
     * @author BBX
     * @param integer $productId
     */
    public static function updateLastDesactivationValue($productId, $value = 0) {
        // Connects to Master
        $db = Database::getConnection();

        // Test number of lines in sys_definition_product
        $query = "UPDATE sys_definition_product
                SET sdp_last_desactivation = $value
                WHERE sdp_id = $productId";
        $db->execute($query);
    }

    /**
     * Retuns true if label unique
     * @param integer $productId
     * @param string $productLabel
     * @return boolean
     */
    public static function isLabelUnique($productId, $productLabel) {
        // Connects to Master
        $db = Database::getConnection();

        // Checking unicity
        $query = "SELECT sdp_id FROM sys_definition_product
                WHERE sdp_label = '$productLabel'
                AND sdp_id != $productId";
        $db->execute($query);

        // Retuns true if label unique
        return ($db->getNumRows() == 0);
    }

    /**
     * Returns true if product is part of a multiproduct
     * @param integer $productId
     * @return boolean
     */
    // 21/12/2011 ACS BZ 25191 PSQL error with disabled product
    public static function isProductPartOfMultiproduct($productId, $db = null) {
        // Connects to Product
        if ($db == null) {
            $db = Database::getConnection($productId);
        }

        // Test number of lines in sys_definition_product
        $query = "SELECT DISTINCT sdp_id FROM sys_definition_product";
        $db->execute($query);

        // Returns true if product is part of a multiproduct
        return ($db->getNumRows() > 1);
    }

    /**
     * Returns true if product is a slave
     * @param integer $productId
     * @param string $productName
     * @return boolean
     */
    public static function isSlaveFromName($productId, $productName) {
        // Connects to Product
        $db = Database::getConnection($productId);

        // Fethcing status
        $query = "SELECT sdp_master FROM sys_definition_product
                WHERE sdp_db_name = '$productName'";

        // Returns true if product is a slave
        return ($db->getOne($query) == 0);
    }

    /**
     * Returns true if the given IP responds to ping
     * @param string $ipAddress
     * @return boolean
     */
    public static function respondsToPing($ipAddress) {
        // Test ping
        $cmd = "ping -c 1 {$ipAddress} | head -n 2 | tail -n 1";
        exec($cmd, $result);
        $result[0] = trim($result[0]);

        // 18:04 12/08/2009 GHX
        // Correction du BZ 11017
        // Car la ligne peut etre vide dans ce cas c'est qu'il est possible de pingu� le serveur distant
        if (ereg('Unreachable', $result[0]) || $result[0] == '') {
            return false;
        }
        return true;
    }

    /**
     * Returns true if application directory exists
     * @param string $directory
     * @param string $ipAddress
     * @return boolean
     */
    public static function directoryExists($directory, $ipAddress) {
        if (!is_dir("/home/" . $directory)) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if directory already used
     * @param integer $productId
     * @param string $directory
     * @param string $ipAddress
     * @return boolean
     */
    public static function directoryAlreadyUsed($productId, $directory, $ipAddress) {
        // Connects to master
        $db = Database::getConnection();

        // Fetching products
        $query = "SELECT sdp_label FROM sys_definition_product
                WHERE sdp_directory = '$directory'
                AND sdp_ip_address = '$ipAddress'
                AND sdp_id != $productId";
        $label = $db->getOne($query);
        if (empty($label))
            $label = false;

        // Returns true if directory already used
        return $label;
    }

    /**
     * Returns true if database already used
     * @param integer $productId
     * @param string $database
     * @param string $ipAddress
     * @return boolean
     */
    public static function databaseAlreadyUsed($productId, $database, $ipAddress) {
        // Connects to master
        $db = Database::getConnection();

        // Fetching products
        $query = "SELECT sdp_label FROM sys_definition_product
                WHERE sdp_db_name = '$database'
                AND sdp_ip_address = '$ipAddress'
                AND sdp_id != $productId";
        $label = $db->getOne($query);
        if (empty($label))
            $label = false;

        // Returns true if database already used
        return $label;
    }

    /**
     * Removes a product from sys_definition_product on Master
     * @param integer $productId
     */
    public static function dropProduct($productId) {
        // Connects to master
        $db = Database::getConnection();
        // Removes a product
        $query = "DELETE FROM sys_definition_product WHERE sdp_id = $productId";
        $db->execute($query);
    }

    /**
     * Returns true if same Product (ie gsm) already exists
     * @param integer $productId
     * @param string $productName
     * @return boolean
     */
    public static function sameProductAlreadyExists($productId, $productName) {
        foreach (ProductModel::getProducts() as $product) {
            $productTmp = new ProductModel($product['sdp_id']);
            $productTmpValues = $productTmp->getValues();

            // 20/12/2011 ACS BZ 25191 Don't compare products if connection is KO
            if ($productTmp->isError()) {
                continue;
            }

            // 06/10/2009 BBX
            // On ne doit pas empêcher plusieurs produits "def"
            // de fonctionner ensemble (�volution Corporate / Mixed KPI)
            if ($productTmp->getProductName() == 'def')
                continue;

            // Test unicité produit
            // 27/01/2011 BBX
            // Modification de la condition, on compare avec tous les autres produits
            // Qui sont déjà déployés sur le Master
            // BZ 18510
            if (($productId != $productTmpValues['sdp_id']) && (self::isProductDeployedOnMaster($productTmpValues['sdp_id'])) && ($productName == $productTmp->getProductName())) {
                return true;
            }
        }

        // Returns true if same Product (ie gsm) already exists
        return false;
    }

    /**
     * Returns true if CB version is compatible with master
     * @param string $cbVersion
     * @return boolean
     */
    public static function isCbVersionCompatibleWithMaster($cbVersion) {
        $ProductMaster = new ProductModel(ProductModel::getIdMaster());
       
        // 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : on remplace 
        // l'utilisation de preg_match par un comparaison avec php_version_compare
        // si la version du produit à ajouter en slave est : 
        //  - inférieure à la version minimale requise
        //  - ou supérieure à la version du master
        if (version_compare($cbVersion, get_sys_global_parameters('min_slave_cb_version', '5.0.5.10', ProductModel::getIdMaster())) == -1 || version_compare($cbVersion, $ProductMaster->getCBVersion()) == 1) {
            return false;
        }
        return true;
    }
    
    /**
     * Retourne true si la version CB du produit est compatible avec la version CB de la Gateway
     * @param type $cbVersion
     * @return boolean
     */
    public function isCompatibleWithGatewayCBVersion($cbVersion) {    
        $dbMaster = Database::getConnection(self::getIdMaster());        
        $result = $dbMaster->execute("SELECT item_value FROM sys_versioning "
                . "WHERE item = 'cb_version' ORDER BY id DESC LIMIT 1");
        while( $row = $dbMaster->getQueryResults( $result, 1 ) )
        {
           $gateway_cb_version = $row['item_value'];
        }        
       
        if (version_compare($cbVersion, $gateway_cb_version) == 1) {
            return false;
        }
        return true;
        
    }
   
    /**
     * 24/09/2012 MMT DE 5.3 Delete Topology ajout de la fonction
     * @param string $version version à comparer
     * @return boolean true si la version du CB du produit est superieeur ou égale à la version passée en paramètre
     */
    public function isCbVersionGreaterOrEqualThan($version) {
        return (version_compare($this->getCBVersion(), $version) >= 0);
    }

    /**
     * Retourne l'historique maximum configuré pour une TA.
     * Un filtre sur une liste de produits et un nom de famille est possible.
     * Pour la TA 'hour', un nombre d'heures est retourné.
     *
     * @since 5.0.4.17
     * @param  string $ta        Nom de la time aggregation (par défaut 'hour')
     * @param  array  $productId Liste des produits à prendre en compte (optionnel)
     * @param  string $family    Nom de la famille à prendre en compte (optionnel)
     * @return integer
     */
    public static function getMaxHistory($ta = 'hour', array $productId = array(), $family = 'all') {
        $retVal = -1;
        $timeAgg = str_replace('_bh', '', trim(strtolower($ta))); // Gestion de TA en busy hour
        $productsList = $productId;

        // Liste des produits à intérroger
        if (count($productsList) === 0) {
            // Si aucun produit n'a été spécifié, on prend tous les produits actifs
            foreach (ProductModel::getActiveProducts() as $oneProduct) {
                $productsList[] = intval($oneProduct['sdp_id']);
            }
        }

        // Pour tous les produits déclarés
        foreach ($productsList as $oneProduct) {
            $db = Database::getConnection($oneProduct); // Connexion à la base du produit
            // Lecture des valeurs par défauts dans sys_global_parameters
            $defaultHistory = intval($db->getOne("SELECT value FROM sys_global_parameters WHERE parameters='history_{$timeAgg}';"));

            // Si un nom de famille à été spécifié...
            if ($family != 'all') {
                $famHistory = intval($db->getOne("SELECT duration FROM sys_definition_history WHERE ta = '{$timeAgg}' AND family = '{$family}';"));
                if ($famHistory == 0) {
                    // Pas d'historique propre a cette famille, on prend donc celui par défaut
                    $famHistory = $defaultHistory;
                } else {
                    // Un historique est défini pour cette famille, on annule donc celui par défaut
                    $defaultHistory = $famHistory;
                }
            }

            // Si aucun nom de famille n'a été spécifié, on réquête sans condition
            else {
                $famHistory = intval($db->getOne("SELECT max(duration) FROM sys_definition_history WHERE ta = '{$timeAgg}';"));
            }

            // On met à jour la valeur à retourner si elle est plus grande
            if (max($famHistory, $defaultHistory) > $retVal) {
                $retVal = max($famHistory, $defaultHistory);
            }
        }

        // Pour la TA hour, on passe en nombre d'heures
        if ($timeAgg == 'hour') {
            $retVal *= 24;
        }
        return $retVal;
    }

    /**
     * D�termine si un produit est un Produit Blanc
     *
     * @since  5.1.5.00
     * @param  type $idProduct Identifiant du produit à tester
     * @return boolean
     */
    public static function isBlankProduct($idProduct) {
        return ( get_sys_global_parameters('module', '', $idProduct) === self::BLANK_PRODUCT_MODULE );
    }

    /**
     * Modifie la configuration du produit en fonction du compute mode 
     * choisi. Les tables impactées sont sys_definition_master* et
     * sys_definition_time_agregation.
     *
     * @since 5.1.4.04
     * @param string $mode Nouveau compute mode ('hourly' ou 'daily')
     * @param integer $productId Identifiant du produit concern�
     * @return none
     */
    public static function changeComputeMode($mode, $productId) {
        // Connexion à la base du produit
        $db = Database::getConnection($productId);

        // Valeur � ins�rer dans la colonne visible (par défaut en erreur)
        $visible = -1;

        // Actions en fonction du compute mode
        switch (strtolower(trim($mode))) {
            case 'hourly' :
                // On rend visible les process et TA de type hour
                $visible = 1;
                break;

            case 'daily' :
                // On rend invisible les process et TA de type hour
                $visible = 0;
                break;

            default:
                // Compute mode non reconnu, on ne fait rien
                break;
        }

        if ($visible !== -1) {
            $db->execute("UPDATE sys_definition_master SET visible={$visible} WHERE master_id=12");
            $db->execute("UPDATE sys_definition_master_ref SET visible={$visible} WHERE master_id=12");
            $db->execute("UPDATE sys_definition_time_agregation SET visible={$visible} WHERE agregation='hour' OR agregation like '%_bh'");
        }
    }

    /**
     * Set Maintenance mode for current product
     * BZ 18510
     * @author BBX
     */
    public function beginMaintenance() {
        $f = fopen($this->maintenanceFile, 'w+');
        fclose($f);
    }

    /**
     * End Maintenance mode for current product
     * BZ 18510
     * @author BBX
     */
    public function endMaintenance() {
        if (file_exists($this->maintenanceFile))
            unlink($this->maintenanceFile);
    }

    /**
     * Returns true if current product is under maintenance
     * BZ 18510
     * @author BBX
     * @return boolean
     */
    public function isMaintenance() {
        return file_exists($this->maintenanceFile);
    }

    /**
     * Execute command on the product (local or distant)
     *
     * 	25/10/2011 ACS BZ 24399 Files are not deleted on distant slave
     */
    public function execCommand($command = '') {
        $result = null;
        // if product is on the server, use local execution command
        if ($this->productValues['sdp_ip_address'] == get_adr_server() || $this->productValues['sdp_ip_address'] == '127.0.0.1' || $this->productValues['sdp_ip_address'] == 'localhost') {
            exec($command, $result);
        }
        // otherwise, use distant execution command
        else {
            if (!isset($this->SSH)) {
                $this->SSH = new SSHConnection($this->productValues['sdp_ip_address'], $this->productValues['sdp_ssh_user'], $this->productValues['sdp_ssh_password'], $this->productValues['sdp_ssh_port']);
            }
            $result = $this->SSH->exec($command);
        }

        return $result;
    }

    /**
     * Returns a list of product IDs with SMSC configured
     * 18/10/2011 BBX BZ 24266
     * @param boolean reduce
     * @return array 
     */
    public static function getProductsWithSMSC($reduce = false) {
        // Final array
        $productList = array();
        foreach (self::getActiveProducts() as $prod) {
            // Test variable
            $isConfigured = true;
            // Gathering information
            $pId = $prod['sdp_id'];
            $smscHost = get_sys_global_parameters('smsc_host', '', $pId);
            $smscPort = intval(get_sys_global_parameters('smsc_port', '', $pId));
            $smscSId = get_sys_global_parameters('smsc_system_id', '', $pId);
            $smscPasswd = get_sys_global_parameters('smsc_password', '', $pId);
            $smscSType = get_sys_global_parameters('smsc_system_type', '', $pId);
            // Testing SMSC
            try {
                $astSMSC = new AstSMSC($smscHost, $smscSId, base64_decode($smscPasswd), $smscPort, $smscSType);
            }
            // Affecting false if SMSC not configured
            catch (Exception $e) {
                $isConfigured = false;
            }
            // If configured, let's add product to list
            if ($isConfigured)
                $productList[$pId] = print_r($astSMSC, 1);
        }

        // If it's asked to remove doubles (identic connections)
        if ($reduce)
            $productList = array_unique($productList);

        // Returning result
        return array_keys($productList);
    }

    // BEGIN -- 09/12/2011 ACS Mantis 837 DE HTTPS support
    /**
     * Get the complete url depending of the configuration of the product and the source of the call
     * 
     * @param $path: relative path to the target file (ex: "index.php", "php/header.php" ...)
     * @param $ipPublic: if true, return the public ip address of the server
     * @param $fromSlaveGUI: should be specified when request is created from a slave GUI (very few requests are concerned)
     * @return String complete url to the specified path
     */
    // 20/12/2011 ACS BZ 25206 Bad link to master on slave index page
    public function getCompleteUrl($path = '', $ipPublic = false, $fromSlaveGUI = false) {
        // call from a CLI script
        if (empty($_SERVER['SERVER_NAME'])) {
            $ip = $this->productValues['sdp_ip_address'];
            if ($ipPublic) {
                $ip = get_adr_server($ipPublic);
            }
            $url = ProductModel::constructUrl($this->productValues['sdp_https'] == '1', $ip, $this->productValues['sdp_https_port'], $this->productValues['sdp_directory'], $path);
        }
        // call from GUI
        else {
            // master
            if ($this->productValues['sdp_master'] == 1 && !$fromSlaveGUI) {
                $url = ProductModel::getCompleteUrlForMasterGui($path);
            }
            // slave
            else {
                // choose protocol
                // use same protocol as master if available ; otherwise, choose protocol available on slave product
                $useHTTPS = false;
                if (isset($_SERVER["HTTPS"])) {
                    $useHTTPS = true;
                }
                if ($useHTTPS && $this->productValues['sdp_https'] != '1') {
                    $useHTTPS = false;
                } elseif (!$useHTTPS && $this->productValues['sdp_http'] != '1') {
                    $useHTTPS = true;
                }

                $url = ProductModel::constructUrl($useHTTPS, $this->productValues['sdp_ip_address'], $this->productValues['sdp_https_port'], $this->productValues['sdp_directory'], $path);
            }
        }
        return $url;
    }

    /**
     * Get the complete url to a path on the current server
     * use $_SERVER information to get the url (protocol, server name, port)
     * 
     * @param path: relative path to the target file (ex: "index.php", "php/header.php" ...)
     */
    public static function getCompleteUrlForMasterGui($path = '') {
        return ProductModel::constructUrl(isset($_SERVER["HTTPS"]), $_SERVER["SERVER_NAME"], $_SERVER["SERVER_PORT"], NIVEAU_0, $path);
    }

    /**
     * Construct an url
     * 
     * @param userHTTPS: boolean => true if HTTPS protocol must be used
     * @param serverName: ip or server name
     * @param port: https port
     * @param directory: directory to the T&A application
     * @param path: relative path to the target file (ex: "index.php", "php/header.php" ...)
     * 
     */
    private static function constructUrl($useHTTPS, $serverName, $port, $directory, $path) {
        // protocol
        if ($useHTTPS) {
            $url = 'https://';
        } else {
            $url = 'http://';
        }
        // server name/ip
        $url .= $serverName;

        // port
        if ($useHTTPS && !empty($port)) {
            $url .= ':' . $port;
        }
        if (!empty($directory)) {
            // check if the path already contains the directory
            $pos = strrpos($path, $directory);
            if ($pos === false || $pos > 2) {
                if ($directory[0] != '/') {
                    $url .= '/';
                }
                $url .= $directory;
            }
        }

        // path
        if (!empty($path) && $path[0] != '/' && $url[strlen($url) - 1] != '/') {
            $url .= '/';
        }
        $url .= $path;

        return $url;
    }

    /**
     * Check that connection is working fine with specified protocols on target product
     * 
     * @param $http: true if http protocol has to be checked
     * @param $https: true if https protocol has to be checked
     * @param $https_port: https port
     * @param $productIP: IP of the server hosting the targeted T&A
     * @param $directory: directory of the targeted T&A
     */
    public static function checkProtocolConnection($http, $https, $https_port, $productIP, $directory) {
        $error = '';
        $http = ($http === true || $http == '1');
        $https = ($https === true || $https == '1');
        if (!$http && !$https) {
            $error = __T('A_SETUP_PRODUCT_NO_PROTOCOL');
        }

        if (empty($error) && $http) {
            // 23/03/2012 NSE bz 26498 : remplacement de index.php qui subit une redirection lors de l'utilisation du Portail
            $url = ProductModel::constructUrl(false, $productIP, '', $directory, "/about.php");

            if (!urlExists($url)) {
                $error = __T('A_SETUP_PRODUCT_PROTOCOL_CONN_FAILED', 'http');
            }
        }

        if (empty($error) && $https) {
            // 23/03/2012 NSE bz 26498 : remplacement de index.php qui subit une redirection lors de l'utilisation du Portail
            $url = ProductModel::constructUrl(true, $productIP, $https_port, $directory, "/about.php");

            if (!urlExists($url)) {
                if (!empty($https_port)) {
                    $error = __T('A_SETUP_PRODUCT_PROTOCOL_CONN_FAILED_PORT', 'https', $https_port);
                } else {
                    $error = __T('A_SETUP_PRODUCT_PROTOCOL_CONN_FAILED', 'https');
                }
            }
        }

        return $error;
    }

    // END -- 09/12/2011 ACS Mantis 837 DE HTTPS support

    /**
     * Retourne la liste des menus spécifiques à un produit
     * BZ 21945
     * @param type $productCode
     * @param DatabaseConnection $database
     * @return type 
     */
    public function getProductSpecificMenus() {
        $specificMenus = array();
        $productCode = sprintf("%04d", $this->getCode(true));

        $query = "SELECT id_menu
                FROM menu_deroulant_intranet
                WHERE id_menu LIKE 'menu.{$productCode}.%'
                OR id_menu LIKE 'menu.dshd.{$productCode}.%'
                GROUP BY id_menu";
        $result = $this->database->execute($query);
        while ($row = $this->database->getQueryResults($result, 1)) {
            $specificMenus[] = $row['id_menu'];
        }

        return $specificMenus;
    }

    /**
     * Give a read only access to user "read_only_user" on all tables 
     */
    public static function setReadOnlyUserAccess() {
        // Postgres read-only user name
        $readOnlyUser = "read_only_user";

        // Database connection
        $database = DataBase::getConnection();

        // Check if the role already exists
        $sql = "SELECT rolname FROM pg_roles WHERE rolname = '$readOnlyUser'";
        if (!$database->getOne($sql)) {
            // Create read-only user and give rights to execute dblink method
            $sql = "CREATE ROLE $readOnlyUser NOSUPERUSER LOGIN password '$readOnlyUser'";
            $database->execute($sql);
        }

        $sql = "GRANT EXECUTE ON FUNCTION dblink_connect_u(text) TO read_only_user";
        $database->execute($sql);

        $sql = "GRANT EXECUTE ON FUNCTION dblink_connect_u(text, text) TO read_only_user";
        $database->execute($sql);


        // Grant select on each table of the database
        // 27/01/2014 GFS - Bug 39350 - [REC][T&A Gateway 5.3.1.11][Retrieve] GRANT queries take too much time
        // Les requ�tes GRANT ne sont plus appliqu�es qu'aux tables en ayant besoin
        $sql = "SELECT 'GRANT SELECT ON ' ||schemaname||'.'||tablename||' TO $readOnlyUser;' as sql FROM pg_tables WHERE NOT has_table_privilege ('$readOnlyUser', schemaname||'.'||tablename, 'select') AND schemaname = 'public';";
        $rows = $database->getAll($sql);
        foreach ($rows as $grantQuery) {
            $database->execute($grantQuery['sql']);
        }
    }

    /**
     * 24/04/2012 BBX
     * BZ 26959
     * Cette méthode permet de définir une homepage par défaut sur le master
     * lorsque celui-ci n'en a aucune. C'est le cas pour Gateway.
     * La homepage choisiecorrespond à la première homepage valide trouvée
     * sur les slave faisant parti du multiproduit.
     */
    public static function updateDefaultHomepage() {
        // Connexion à la base de données master
        $idMaster = ProductModel::getIdMaster();
        $dbMaster = Database::getConnection($idMaster);

        // A-t-on une homepage ?
        $idHomepage = get_sys_global_parameters('id_homepage', '', $idMaster);

        // Si pas de homepage par défaut, on récupère cette du premier slave
        // qui a une valeur non nulle.
        if ($idHomepage == '') {
            // Parcours des produits sauf Gateway
            foreach (ProductModel::getProducts(false) as $product) {
                // Récupération de la homepage par défaut définie sur ce produit
                $idHomepage = get_sys_global_parameters('id_homepage', '', $product['sdp_id']);

                // Est-ce un id valide ?
                $dash = new DashboardModel($idHomepage, 'overtime', $idMaster);
                if (!$dash->getError()) {
                    // Mise à jour de la homepage
                    $query = "UPDATE sys_global_parameters
                            SET value = '$idHomepage'
                            WHERE parameters = 'id_homepage'";
                    $dbMaster->execute($query);

                    // On s'arrête ici
                    break;
                }
            }
        }
    }

    /**
     * Permet de savoir si le produit est horaire ou journalier
     * @author BBX
     * BZ 24853
     * @return boolean, true si produit horaire 
     */
    public function isHourly() {
        // Sur un produit horaire, l'agrégation hour est dispo
        $hVisible = $this->database->getOne("SELECT COUNT(agregation_rank) 
            FROM sys_definition_time_agregation 
            WHERE agregation = 'hour'
            AND on_off = 1
            AND visible = 1");
        // Sur un Corporate horaire, l'agr�gation hour est dispo
        if (CorporateModel::isCorporate($this->idProduct)) {
            $hVisible = $this->database->getOne("SELECT COUNT(agregation_rank) 
                FROM sys_definition_time_agregation_bckp 
                WHERE agregation = 'hour'
                AND on_off = 1
                AND visible = 1");
        }
        // Sur un produit horaire, le Compute Hourly est affich� dans le Task Scheduler
        $chAvailable = $this->database->getOne("SELECT COUNT(master_id) 
            FROM sys_definition_master 
            WHERE master_id = 12 AND visible = 1");
        // Produit horaire si les conditions sont r�unies
        return ($hVisible && $chAvailable);
    }

    /**
     * Restauration du dernier contexte de l'application
     * 17/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
     * 
     */
    public function restoreContext() {
        $ret = true;
        $contextName = $this->database->getOne("SELECT sdcm_file_name FROM sys_definition_context_management ORDER BY sdcm_date::timestamp DESC LIMIT 1");
        $rootAppDir = "/home/" . $this->productValues['sdp_directory'];
        $result = $this->execCommand("php " . $rootAppDir . "/context/php/context_install_sh.php " . $rootAppDir . "/upload/context/" . $contextName);
        file_put_contents(REP_PHYSIQUE_NIVEAU_0 . 'upload/context/restoreContext_' . $this->productValues['sdp_label'] . '_' . date("Ymd") . '.log', $result);
        if (substr_count(implode("\n", $result), "ERROR")) {
            $ret = false;
        }
        return $ret;
    }

}
