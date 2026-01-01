<?php

/**
 * @cb51@
 *
 * 22/09/2010 OJT : Correction bz16935 et refonte du test SSH et de l'existance du dossier TA
 * 22/12/2011 ACS BZ 25253 error message does not block save
 * 22/12/2011 ACS BZ 25254 test performed when click on "test" different to save test
 */
/*
 * 	@cb41000@
 *
 * 	11/12/2008 - Copyright Astellia
 *
 * 	Composant de base version cb_4.1.0.00
 *
 * 	12/12/2008 BBX : création du script de traitement ajax
 * 	23/01/2009 GHX :
 * 		-  Ajout d'une vérificationn sur le fait qu'on ne peut pas avoir plusieurs produits avec le même répertoire sur un même serveur
 * 		-  Ajout d'une vérificationn sur le fait qu'on ne peut pas avoir plusieurs produits avec la même base de donn�e sur un même serveur
 * 		- ajout de tous les messages d'erreurs dans la table sys_definition_messages_display pour les afficher avec la fonction __T()
 * 	14/05/2009 - SPS :
 * 		- test sur l'existence du dossier du produit (lors de l'ajout du produit) (correction bug 9668)
 * 		- test sur la connexion ssh (lors de l'ajout du produit) (correction bug 9671)
 * 		- ajout d'une methode de test des parametres de connexion ssh (sur l'ihm) (correction bug 9671)
 * 		- on verifie d'abord si le label est unique (correction bug 9663)
 * 	08/06/2009 - SPS :
 * 		- on regarde si l'utilisateur a ete saisi
 * 	12/06/2009 GHX
 * 		- Lors de l'activation d'un produit on remonte les donn�es du slave sur le master (BZ9666)
 * 	06/08/2009 MPR
 * 		- Correction du bug 10586
 * 	11/08/2009 GHX
 * 		- Modification de conditions sinon activation impossible si le produit �tait déjà ins�r� en base
 * 	12/08/2009 GHX
 * 		- Correction du BZ 10994 [REC][T&A CB 5.0][TC#37323][TP#1][SETUP Product]: permettre � un profil client de pouvoir modifier le nom de son produit
 * 		- Si le produit est sur un autre serveur, on doit obligatoirement saisir un login/passwors SSH
 * 		- Correction du BZ 11017 [REC][T&A Cb 5.0][TC#35911][TP#1][SETUP PRODUCTS]: le bouton SAVE se grise et ne se rel�che pas
 * 	19/08/2009 GHX
 * 		- (Evo) Désactivation d'un produit
 * 		- Ajout d'une condition pour ne faire l'activation que si le produit passe de OFF à ON
 *
 * 	20/08/2009 BBX : spd_ip => sdp_ip_address. BZ 11112
 *
 * 	25/08/2009 - MPR :
 * 		- Correction du bug 11215 - Contr�le sur l'existence du répertoire du produit
 * 		- Correction du bug 11216 - Contr�le sur le couple ssh login/password lorsqu'il est renseigné
 *
 * 	06/10/2009 BBX :
 * 		- On ne doit pas emp�cher plusieurs produits "def" de fonctionner ensemble (�volution Corporate / Mixed KPI)
 * 	21/10/2009 GHX
 * 		- Ajout d'un strtolower sur le label pour la vérificationn de l'unicité
 * 	28/10/2009 GHX
 * 		- Ajout de l'ID du  master pour l'update
 * 	01/12/2009 GHX
 * 		- Correction du BZ 12979 [CB 5.0][Test Open Office] probl�me de lenteur connexion à cause de OOo
 * 	03/12/2009 GHX
 * 		- Correction du BZ 11797 [REC][T&A CB 5.0][ACTIVATION][cas limite]: activation en slave d'un produit master
 * 			-> Impossible d'ajouter le produit si c'est déjà un master (on compte le nombre de ligne dans sys_definition_product du produit que l'on veut ajouter)
 * 	07/01/2010 GHX
 * 		- Maintenant la vérificationn du CB se fait uniquement sur les 2 premiers digits
 *  19/10/2010 NSE bz 19296 : trigrammes des produits install�s apr�s le mixed_kpi concat�n�s sur tous les noms de colonnes des tables de donn�es
 * 11/01/2012 SPD1 Querybuilder2 : copie des requetes querybuilder du slave vers le master
 *  19/01/2011 BBX BZ 18510 : R��criture du "case 1" : Sauvegarde / activation des produits.
 * 03/08/2011 MMT bz 22981 : ajout de la gestion de sauvegarde des trigrames pour clients admin
 *         	pour securit� cote serveur on utilise un tableau des elements interdits à la sauvegarde
 * 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : modification du message d'erreur si les versions de CB sont incompatibles
 * 28/11/2011 ACS BZ 24868 use ajax call to delete a product
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
 * 20/12/2011 ACS BZ 25195 No error message when saving with bad protocol first time
 * 20/12/2011 ACS BZ 25191 PSQL error in setup product with disabled product
 * 20/12/2011 ACS BZ 25194 New product fields not empty when adding new product
 * 17/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
 * 18/02/2015 JLG - BZ#44769 : Add http as default protocol
 * 07/04/2017 RQ8103 : [AO-T&A] Add a check in Setup that T&A Gateway parent have upper version of CB in case of upgrade of a T&A slave
 */
?>
<?php

session_start();
include_once dirname(__FILE__) . "/../../../../php/environnement_liens.php";

include_once(REP_PHYSIQUE_NIVEAU_0 . '/class/SSHConnection.class.php');
include_once REP_PHYSIQUE_NIVEAU_0 . 'class/CbCompatibility.class.php';
// On appel la classe de mapping (=>Si on change de master topo, on vide tout le mapping sur tous les produits)
include_once(REP_PHYSIQUE_NIVEAU_0 . '/class/mapping/Mapping.class.php');
// Classe Mixed KPI pour renvoyer les CFG en cas de mise à jour de trigrammes
include_once(REP_PHYSIQUE_NIVEAU_0 . 'mixed_kpi/class/MixedKpiCFG.class.php');

// N�cessaires à l'activation d'un produit
include_once REP_PHYSIQUE_NIVEAU_0 . 'context/class/Context.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'context/class/ContextElement.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'context/class/ContextMigration.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'context/class/ContextMount.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'context/class/ContextActivation.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'context/class/ContextGenerate.class.php';

// Connexion � la base de donn�es locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// 22/12/2010 BBX
// Set Maintenance mode
// BZ 18510
$masterModel = new ProductModel(ProductModel::getIdMaster());
$masterModel->beginMaintenance();

// Réception de l'action
switch ($_GET['action']) {
    /**
     * Sauvegarde / activation des produits
     */
    case 1:
        // Par défaut, il s'agit d'une mise à jour d'un produit existant
        // Et il n'y a pas d'erreur d'activation
        $newProduct = $activationError = false;

        // D�claration du log
        $log = '';

        // 08:47 12/08/2009 GHX
        // Correctoin du BZ 10994
        $client_type = getClientType($_SESSION['id_user']);

        // Définition des tests associées à des changements de valeurs
        $saveWithoutTesting = array('sdp_automatic_activation' => '');

        // Définition des valeurs par défaut en cas de valeur nulle
        // 09/12/2011 ACS Mantis 837 DE HTTPS support
        $defaultValues = array('sdp_on_off' => '0',
            'sdp_master' => '0',
            'sdp_master_topo' => '0',
            'sdp_ssh_port' => 22,
            'sdp_http' => '1',
            'sdp_https' => '0',
            'sdp_https_port' => '');

        // Pour tous les produits à traiter
        foreach ($_POST['product'] as $idProd => $values) {


            // 09/12/2011 ACS Mantis 837 DE HTTPS support
            // retrieve protocol configurations
            if ($idProd == ProductModel::getIdMaster()) {
                // for master product, value is retrieve from radio "master_protocol"
                if ($_POST['master_protocol'] == 'https') {
                    $values['sdp_http'] = '0';
                    $values['sdp_https'] = '1';
                } else {
                    $values['sdp_http'] = '1';
                    $values['sdp_https'] = '0';
                }
            } else {
                // for slaves product, value is retrieve for each checkbox (convert 'on' to '1')
                if (isset($values['sdp_http']) && $values['sdp_http'] == 'on') {
                    $values['sdp_http'] = '1';
                } else {
                    $values['sdp_http'] = '0';
                }
                if (isset($values['sdp_https']) && $values['sdp_https'] == 'on') {
                    $values['sdp_https'] = '1';
                } else {
                    $values['sdp_https'] = '0';
                }
            }

            // JLG : BZ44769
            if ($values['sdp_http'] == '0' && $values['sdp_https'] == '0') {
                $values['sdp_http'] = '1';
            }

            // Instanciation du produit
            $ProductModel = new ProductModel($idProd);

            // 18/07/2011 BBX
            // La sauvegarde "client" doit intervenir avant la modificaion des valeurs
            // Afin de ne pas altérer d'autres valeurs que celles autorisées
            // BZ 22979
            // 03/08/2011 MMT bz 22981 ajout de la gestion de sauvegarde des trigrames pour clients admin
            // pour securité cote serveur on utilise un tableau des elements interdits à la sauvegarde

            $forbidenFields = array();
            if ($client_type == 'client') {
                // tout les champs sauf label & trigram
                // 09/12/2011 ACS Mantis 837 DE HTTPS support
                $forbidenFields = array('sdp_ip_address', 'sdp_directory', 'sdp_db_name', 'sdp_db_login',
                    'sdp_db_password', 'sdp_db_port', 'sdp_ssh_user', 'sdp_ssh_password',
                    'sdp_ssh_port', 'sdp_http', 'sdp_https', 'sdp_https_port');
            }

            // Traitement des valeurs nulles
            // affectation de valeurs par d�faut
            foreach ($defaultValues as $key => $defaultValue) {
                if (!isset($values[$key]) || ($values[$key] == '')) {
                    $values[$key] = $defaultValue;
                    $ProductModel->setValue($key, $defaultValue);
                }
            }

            /**
             * AJOUT D'UN PRODUIT
             */
            //// 03/08/2011 MMT bz 22981 securite cote serveur: client admin ne peut ajouter un produit
            if ($idProd == 0 && $client_type != 'client') {
                // Affectation des valeurs
                foreach ($values as $key => $value) {
                    $ProductModel->setValue($key, $value);
                }

                // Ajout du produit
                $idProd = $ProductModel->addProduct();

                // Information qu'il s'agit d'un nouveau produit
                $newProduct = $idProd;
            }
            /**
             * FIN AJOUT PRODUIT
             */
            // R�cup�ration des anciennes valeurs
            $oldValues = $ProductModel->getValues();

            // Traitement des valeurs non testées
            foreach (array_intersect_key($oldValues, $saveWithoutTesting) as $key => $oldValue) {
                // Sauvegarde de la valeur
                $ProductModel->setValue($key, $values[$key]);
            }
            // Master
            if ($idProd == ProductModel::getIdMaster())
                $ProductModel->setValue('sdp_master', '1');
            // Master topo
            if ($idProd == ProductModel::getIdMasterTopo())
                $ProductModel->setValue('sdp_master_topo', '1');

            // Traitement des valeurs testées
            $error = '';
            foreach (array_diff_key($oldValues, $saveWithoutTesting) as $key => $oldValue) {
                // Modification d'une valeur
                // 03/08/2011 MMT bz 22981 test si l'element n'est pas interdit à la sauvegarde
                if (($newProduct || ($oldValue != $values[$key])) && !in_array($key, $forbidenFields)) {
                    // On ne traite pas l'id
                    if ($key == 'sdp_id')
                        continue;

                    // Test de l'unicité du label
                    if (empty($error) && $key == 'sdp_label') {
                        if (!ProductModel::isLabelUnique($idProd, $values[$key])) {
                            $error = __T('A_SETUP_PRODUCTS_PRODUCT_LABEL_MUST_BE_UNIQUE');
                        }
                    }
                    // Test de l'adresse IP
                    if (empty($error) && $key == 'sdp_ip_address') {
                        if (!ProductModel::respondsToPing($values[$key])) {
                            $error = __T('A_SETUP_PRODUCTS_HOST_NOT_REACHABLE');
                        }
                    }
                    // Test de l'unicité du répertoire + existence locale
                    if (empty($error) && $key == 'sdp_directory') {
                        if ($directoryUsed = ProductModel::directoryAlreadyUsed($idProd, $values[$key], $values['sdp_ip_address'])) {
                            $error = __T('A_SETUP_PRODUCTS_DIRECTORY_ALREADY_USES', $directoryUsed);
                        }
                        if (get_adr_server() == $values['sdp_ip_address'] && !ProductModel::directoryExists($values[$key], $values['sdp_ip_address'])) {
                            $error = __T('A_SETUP_PRODUCTS_DIRECTORY_MUST_EXIST');
                        }
                    }
                    // Test unicit� base de donn�es
                    if (empty($error) && $key == 'sdp_db_name') {
                        if ($dbUsed = ProductModel::databaseAlreadyUsed($idProd, $values[$key], $values['sdp_ip_address'])) {
                            $error = __T('A_SETUP_PRODUCTS_DATABASE_ALREADY_USES', $dbUsed);
                        }
                    }
                    // Test de connexion à la base de données
                    if (empty($error) && in_array($key, array('sdp_db_name', 'sdp_db_login', 'sdp_db_password', 'sdp_db_port', 'sdp_ip_address'))) {
                        $conString = DataBaseConnection::getConnectionString($values['sdp_ip_address'], $values['sdp_db_name'], $values['sdp_db_login'], $values['sdp_db_password'], $values['sdp_db_port']);
                        if (!DataBaseConnection::testConnection($conString)) {
                            $error = __T('A_SETUP_PRODUCTS_CANNOT_CONNECT_DATABASE');
                        }
                    }

                    // Test de connexion SSH
                    if (empty($error) && in_array($key, array('sdp_ssh_user', 'sdp_ssh_password', 'sdp_ssh_port', 'sdp_ip_address', 'sdp_directory'))) {
                        if (get_adr_server() !== $values['sdp_ip_address'] && empty($values[$key])) {
                            $error = __T('A_E_SETUP_PRODUCTS_SSH_MUST_EXIST');
                        }
                        // Test paramêtres SSH (correction bz16935)
                        // Si un des deux paramêtres est rempli mais pas l'autre
                        if (( empty($values['sdp_ssh_user']) && !empty($values['sdp_ssh_password']) ) || (!empty($values['sdp_ssh_user']) && empty($values['sdp_ssh_password']) )) {
                            $error = __T('A_E_SETUP_PRODUCTS_BOTH_SSH_PARAM_MUST_EXIST');
                        }

                        // 26/01/2011 BBX : test SSH uniquement sur IP étrangère
                        // ou si les champs sont remplis. BZ 20312
                        if (get_adr_server() !== $values['sdp_ip_address']) {
                            // Test SSH et int�grit� du dossier TA
                            $dir = "/home/" . $values['sdp_directory'];

                            if (empty($values['sdp_ssh_user']) || empty($values['sdp_ssh_password'])) {
                                $error = __T('A_E_SETUP_PRODUCTS_SSH_MUST_EXIST');
                            }

                            try {
                                $ssh = new SSHConnection($values['sdp_ip_address'], $values['sdp_ssh_user'], $values['sdp_ssh_password'], $values['sdp_ssh_port']);
                                if (!ProductModel::isTaDirectory($dir, $ssh)) {
                                    switch (ProductModel::$lastErrorCode) {
                                        case -1 :
                                            $error = __T('A_SETUP_PRODUCTS_DIRECTORY_MUST_EXIST');
                                            break;

                                        case -2 :
                                            $error = __T('A_SETUP_PRODUCTS_DIRECTORY_MUST_BE_TA');
                                            break;
                                    }
                                }
                            } catch (Exception $ex) {
                                //si la connexion echoue, on affiche l'exception qui a ete levee
                                $error = $ex->getMessage();
                            }
                        }
                    }

                    // 09/12/2011 ACS Mantis 837 DE HTTPS support
                    // Test that product has HTTPS support DE if https is activated
                    if (empty($error) && $key == 'sdp_https' && $values['sdp_https'] == '1') {
                        if (!CbCompatibility::isModuleAvailable(CbCompatibility::$HTTPS_SUPPORT, $idProd)) {
                            $error = __T('A_SETUP_PRODUCT_HTTPS_NOT_SUPPORTED');
                        }
                    }

                    // 26/01/2011 BBX BZ 18111
                    // Gestion du trigramme
                    if (ProductModel::getIdMixedKpi()) {
                        // 19/10/2010 NSE bz 19296 : trigrammes concaténés
                        // On s'assure qu'il y avait bien un ancien trigramm
                        if (!empty($oldValues['sdp_trigram']) && ($oldValues['sdp_trigram'] != $values['sdp_trigram'])) {
                            if (!ProductModel::updateTrigramInMixedKpiProduct($oldValues['sdp_trigram'], $values['sdp_trigram'])) {
                                $error = __T('A_SETUP_PRODUCTS_TRIGRAM_FATAL');
                            } else {
                                // 08/04/2010 BBX
                                // On logue la modification du trigramme dans le tracelog. BZ 14970
                                sys_log_ast('Info', 'Trending&Aggregation', 'Setup Product', __T('A_SETUP_PRODUCTS_TRIGRAM_CHANGED', $oldValues['sdp_trigram'], $values['sdp_trigram'], $values['sdp_label']), 'support_1');
                            }
                        }
                    }

                    // Sauvegarde de la valeur
                    // 22/12/2011 ACS BZ 25253 error message does not block save
                    if (empty($error)) {
                        $ProductModel->setValue($key, $values[$key]);
                    } else {
                        break;
                    }
                }
            }

            // 20/12/2011 ACS BZ 25195 Test protocol even if protocol configuration has not been changed
            // Test protocol connection
            if (empty($error)) {
                $error = ProductModel::checkProtocolConnection($values['sdp_http'], $values['sdp_https'], $values['sdp_https_port'], $values['sdp_ip_address'], $values['sdp_directory']);
            }

            // Si erreur
            if (!empty($error)) {
                // On affiche l'erreur
                echo $error;
                // On enregistre tout de même les champs corrects
                $ProductModel->updateProduct(ProductModel::getIdMaster());
                // S'il s'agit d'un ajout de produit, on ne le conserve pas
                if ($newProduct)
                    ProductModel::dropProduct($idProd);
                // Et on s'arr�te l�
                $masterModel->endMaintenance();
                return;
            }

            // ********* END OF INPUT CHECKS ***********
            // 16:15 03/12/2009 GHX
            // Correction du BZ 11797
            // On ne doit pas pouvoir ajouter un produit qui est déjà master ou slave
            if ($newProduct && ProductModel::isProductPartOfMultiproduct($idProd)) {
                // Déjà Master ou Slave ?
                if (ProductModel::isSlaveFromName($idProd, $values['sdp_db_name']))
                    echo __T('A_E_SETUP_PRODUCTS_ALREADY_SLAVE');
                else
                    echo __T('A_E_SETUP_PRODUCTS_ALREADY_MASTER');
                // On ne peut pas le garder
                if (!empty($idProd))
                    ProductModel::dropProduct($idProd);
                // On ne va pas plus loin
                $masterModel->endMaintenance();
                return;
            }

            // Gestion de l'activation
            if ($values['sdp_on_off'] == 1) {
                // En cas de réactivation, on force les champs suivant
                if ($oldValues['sdp_on_off'] == 0) {
                    $ProductModel->setValue('sdp_automatic_activation', '1');
                    $ProductModel->setValue('sdp_last_desactivation', '0');
                }

                // Pour être activé, le produit le doit pas être le Master
                // du multiproduit
                if ($idProd != ProductModel::getIdMaster()) {
                    // Il doit n'avoir encore jamais été déployé
                    // sur le Master du multiproduit
                    if (!ProductModel::isProductDeployedOnMaster($idProd)) {
                        // ACTIVATION
                        $activationError = true;

                        // Test de connexion à la base (bz21203)
                        $conString = DataBaseConnection::getConnectionString($values['sdp_ip_address'], $values['sdp_db_name'], $values['sdp_db_login'], $values['sdp_db_password'], $values['sdp_db_port']);
                        if (!DataBaseConnection::testConnection($conString)) {
                            echo __T('A_SETUP_PRODUCTS_CANNOT_CONNECT_DATABASE');
                            $ProductModel->setValue('sdp_on_off', '0');
                            if ($newProduct)
                                ProductModel::dropProduct($idProd);
                        }
                        else {
                            // 20/12/2011 ACS BZ 25191 Modify product model database according to new configuration
                            $ProductModel->setDatabase($conString);
                            $productName = $ProductModel->getProductName();
                            $cbVersion = $ProductModel->getCBVersion();

                            // Test de l'unicité du produit (par exemple, on ne peut pas avoir 2 produits GSM)
                            if (ProductModel::sameProductAlreadyExists($idProd, $productName)) {
                                echo __T('A_SETUP_PRODUCTS_PRODUCT_ALREADY_EXISTS', $productName);
                                $ProductModel->setValue('sdp_on_off', '0');
                                if ($newProduct)
                                    ProductModel::dropProduct($idProd);
                            }
                            // Test de la présence d'un master (cas quasi impossible certes)
                            elseif (count(getMasterProduct()) == 0) {
                                echo __T('A_SETUP_PRODUCTS_DEFINE_MASTER');
                                $ProductModel->setValue('sdp_on_off', '0');
                                if ($newProduct)
                                    ProductModel::dropProduct($idProd);
                            }
                            // Test de la compatibilité de la version CB
                            elseif (!ProductModel::isCbVersionCompatibleWithMaster($cbVersion)) {
                                // 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : modification du message d'erreur si les versions de CB sont incompatibles
                                echo __T('A_SETUP_PRODUCTS_CB_INVALID', $masterModel->getProductLabel(), get_sys_global_parameters('min_slave_cb_version', '5.1.6.15', ProductModel::getIdMaster()), $masterModel->getCBVersion(), $cbVersion);
                                $ProductModel->setValue('sdp_on_off', '0');
                                if ($newProduct)
                                    ProductModel::dropProduct($idProd);
                            }
                            // Test de la compatibilité de la version CB du produit avec la version CB de la Gateway
                            elseif (!$ProductModel->isCompatibleWithGatewayCBVersion($cbVersion)) {
                                echo __T('A_SETUP_PRODUCTS_CB_INVALID', $masterModel->getProductLabel(), get_sys_global_parameters('min_slave_cb_version', '5.1.6.15', ProductModel::getIdMaster()), $masterModel->getCBVersion(), $cbVersion);
                                $ProductModel->setValue('sdp_on_off', '0');
                                if ($newProduct)
                                    ProductModel::dropProduct($idProd);
                            }
                            else {
                                // Pas d'erreurs
                                $activationError = false;

                                // On enregistre les champs en base
                                $ProductModel->updateProduct(ProductModel::getIdMaster());

                                // 01/08/2012 BBX
                                // BZ 27379 : récupération du module avant de le modifier...
                                $module = get_sys_global_parameters('module', '', $idProd);

                                // On restore le code produit d'origine pour l'activation
                                // Car dans le cas d'un Corporate, le code vaut "def"
                                $ProductModel->setOldModule();
                                if ($idProd != ProductModel::getIdMaster()) {
                                    $log = $ProductModel->activationContext();
                                    $message = __T('A_SETUP_PRODUCT_PRODUCT_ENABLED', $values['sdp_label'], $log);
                                    sys_log_ast('Info', 'Trending&Aggregation', 'Setup Product', $message, 'support_1');
                                }

                                // On restaure ensuite le code "def"
                                if ($module == 'def')
                                    $ProductModel->setAsDef();
                            }
                        }
                    }
                    // 01/02/2011 BBX
                    // Modification de l'ordre des tests
                    // BZ 20434
                    // 21/12/2011 ACS BZ 25191 PSQL error with disabled product
                    else {
                        // Si le produit a été désactivé automatiquement
                        // On informe les utilisateurs du statut
                        if (ProductModel::isAutomaticallyDisabled($idProd)) {
                            $conString = DataBaseConnection::getConnectionString($values['sdp_ip_address'], $values['sdp_db_name'], $values['sdp_db_login'], $values['sdp_db_password'], $values['sdp_db_port']);
                            if (!DataBaseConnection::testConnection($conString)) {
                                echo __T('A_SETUP_PRODUCTS_CANNOT_CONNECT_DATABASE');
                                // Et on s'arrête là
                                $masterModel->endMaintenance();
                                return;
                            }
                            $ProductModel->setDatabase($conString);
                        }

                        // S'il a déjà été déployé et qu'il n'a plus le statut Slave
                        // Alors ce produit a vraissemblablement été réinstallé
                        // brutallement sans suppression préalable
                        // BZ 18510
                        if (!ProductModel::isSlave($idProd, $ProductModel->getDatabase())) {
                            // Le produit a vraissemblablement été réinstallé
                            // brutallement sans suppression préalable
                            echo __T('A_SETUP_PRODUCT_NOT_A_SLAVE');
                            // S'il s'agit d'un ajout de produit, on ne le conserve pas
                            if ($newProduct)
                                ProductModel::dropProduct($idProd);
                            // Et on s'arrête là
                            $masterModel->endMaintenance();
                            return;
                        }
                    }
                }
            }

            // On enregistre les champs en base
            $ProductModel->updateProduct(ProductModel::getIdMaster());
        }

        // Déploiement
        // 14/12/2010 BBX
        // Ajout déploiement des Users
        // BZ 18510
        ProductModel::deployProducts();
        UserModel::deployUsers();

        // SPD1 10/01/12
        // Deploy querybuilder queries when a slave is activated
        if ($values['sdp_on_off'] == 1) {
            QbModel::deployQueries();
        }

        // 24/04/2012 BBX
        // BZ 26959 : mise à jour de la homepage par d�faut
        ProductModel::updateDefaultHomepage();

        // 15:02 11/08/2009  GHX
        // Modification de la condition
        if (!$activationError) {
            if ($newProduct)
                echo 'OK|' . $newProduct . '|' . $log;
            else
                echo 'OK' . '|' . $log;
        }

        break;

    // Ajout d'un nouveau produit
    // 20/12/2011 ACS BZ 25194 New product fields not empty when adding new product
    case 2:
        include_once('setup_products_display.php');
        $ProductModel = new ProductModel(ProductModel::ID_NEW_PRODUCT);
        $ProductModel->setValue('sdp_id', '0');
        displayProduct($ProductModel->getValues());
        break;

    // Tests de connexion
    case 3:
        // Récupération des informations de connexion
        $dbLogin = $_GET['dbLogin'];
        $dbPassword = $_GET['dbPassword'];
        $dbName = $_GET['dbName'];
        $dbPort = $_GET['dbPort'];
        $dbIp = $_GET['dbIp'];

        checkDatabaseConnection($masterModel, $dbLogin, $dbPassword, $dbName, $dbPort, $dbIp);
        break;

    // Récupération d'un produit existant
    case 4:
        include_once('setup_products_display.php');
        $ProductModel = new ProductModel($_GET['idProd']);
        displayProduct($ProductModel->getValues());
        break;

    // Définit un produit comme maitre
    case 5:
        ProductModel::setAsMaster($_GET['idProduct']);
        // Déploiement
        ProductModel::deployProducts();
        break;

    /* 14/05/2009 - SPS : ajout d'une methode de test des parametres de connexion ssh (correction bug 9671) */
    //test de connexion ssh
    case 6:

        $sshUser = $_GET['sshUser'];
        $sshPassword = $_GET['sshPassword'];
        $sshIP = $_GET['sshIP'];
        $sshPort = $_GET['sshPort'];

        // 09:04 12/08/2009 GHX
        // Si le produit n'est pas sur le même serveur on doit obligatoirement mettre un login/password SSH
        // car plusieurs modules utilise SSH si le produit n'est pas sur le même serveur
        if (empty($sshUser) || empty($sshPassword)) {
            echo __T('A_E_SETUP_PRODUCTS_SSH_MUST_EXIST');

            // 22/12/2010 BBX
            // Ends Maintenance
            // BZ 18510
            $masterModel->endMaintenance();

            exit;
        }

        // 18:21 12/08/2009 GHX
        // Correction du BZ 11017
        // Test ping
        $cmd = "ping -c 1 {$sshIP} | head -n 2 | tail -n 1";
        exec($cmd, $result);
        $result[0] = trim($result[0]);
        if (ereg('Unreachable', $result[0]) || $result[0] == '') {
            echo __T('A_SETUP_PRODUCTS_HOST_NOT_REACHABLE');

            // 22/12/2010 BBX
            // Ends Maintenance
            // BZ 18510
            $masterModel->endMaintenance();

            exit;
        }

        try {
            $ssh = new SSHConnection($sshIP, $sshUser, $sshPassword, $sshPort);
        } catch (Exception $ex) {
            //si la connexion echoue, on affiche l'exception qui a ete levee
            echo $ex->getMessage();

            // 22/12/2010 BBX
            // Ends Maintenance
            // BZ 18510
            $masterModel->endMaintenance();

            exit;
        }

        break;

    // 09/12/2011 ACS Mantis 837 DE HTTPS support
    // test protocol connection
    case 'checkProtocolConn':

        $productIP = $_GET['productIP'];
        $directory = $_GET['directory'];
        $http = ($_GET['http'] == "true");
        $https = ($_GET['https'] == "true");
        $https_port = $_GET['https_port'];

        $dbLogin = $_GET['dbLogin'];
        $dbPassword = $_GET['dbPassword'];
        $dbName = $_GET['dbName'];
        $dbPort = $_GET['dbPort'];

        if (checkDatabaseConnection($masterModel, $dbLogin, $dbPassword, $dbName, $dbPort, $productIP)) {

            $error = ProductModel::checkProtocolConnection($http, $https, $https_port, $productIP, $directory);

            // 22/12/2011 ACS BZ 25254 test performed when click on "test" different to save test
            if ($https) {
                $db = new DataBaseConnection(DataBaseConnection::getConnectionString($productIP, $dbName, $dbLogin, $dbPassword, $dbPort));
                if (!CbCompatibility::isModuleAvailable(CbCompatibility::$HTTPS_SUPPORT, '', $db)) {
                    $error = __T('A_SETUP_PRODUCT_HTTPS_NOT_SUPPORTED');
                }
            }

            if (!empty($error)) {
                echo $error;
            }
        }

        exit;

        break;

    /**
     * Delete a product
     *
     * 28/11/2011 ACS : bz24868 use ajax call to delete a product
     * 11/01/2011 OJT : bz25443 gestion de la suppression d'un slave down
     */
    case 'deleteProduct' :
        ob_start();
        $idProduct = $_GET['idProd'];
        $labelProd = $_GET['labelProd'];
        if (!empty($idProduct)) {
            $productModel = new ProductModel($idProduct);
            if ($productModel->isError()) {
                // Si la connexion au slave a échoué, on se connecte au master
                // l'identifiant du produit à supprimer reste bien celui du slave
                $productModel->setDatabaseMaster();
            }
            // GFS 17/01/2013 - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
            // On supprime les users qui n'ont pas de password d�finit
            UserModel::cleanUsers($idProduct);
            // On supprime le produit, il redevient indépendant
            // La suppression entraine la désactivation automatiquement
            $resultDelete = $productModel->delete(REP_PHYSIQUE_NIVEAU_0 . '/upload/delete_product_' . date('YmdHi') . '_' . $labelProd . '.log');
            if ($resultDelete === true) {
                // 29/07/2010 OJT : Correction bz15263
                $message = "OK;" . __T('A_SETUP_PRODUCT_DELETE_PRODUCT_OK', $labelProd, $labelProd);
            } else {
                $message = "KO;" . $resultDelete;
            }
        }

        $productModel->deployProducts();
        // GFS 17/01/2013 - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
        // Restauration du contexte
        if ($resultDelete === true) {
            if (!$productModel->restoreContext()) {
                $message = "KO;" . __T('A_UPLOAD_CONTEXT_DELETE_PRODUCT');
            }
        }
        ob_end_clean();
        echo $message;
        break;

    /*
      14:59 01/12/2009 GHX
      Correction du BZ 12979
     */
    case 'checkOOo':
        include REP_PHYSIQUE_NIVEAU_0 . 'class/CheckConfig.class.php';

        echo '<table id="tabCheckOOo"><tr><th>' . __T('A_OPEN_OFFICE_RESULT_LABEL_COL_IP') . '</th><th>' . __T('A_OPEN_OFFICE_RESULT_LABEL_COL_INSTALLED') . '</th><th>' . __T('A_OPEN_OFFICE_RESULT_LABEL_COL_AVAILABLE') . '</th></tr>';
        $ipChecked = array();
        foreach (ProductModel::getProducts() as $product) {
            // Si le serveur a déjà été testé on passe au suivant
            if (in_array($product['sdp_ip_address'], $ipChecked) || $product['sdp_on_off'] == 0)
                continue;

            $ipChecked[] = $product['sdp_ip_address'];

            $installed = CheckConfig::OpenOfficeInstalled($product['sdp_ip_address'], $product['sdp_ssh_user'], $product['sdp_ssh_password'], $product['sdp_ssh_port']);
            $available = false;
            if ($installed) {
                $available = CheckConfig::OpenOfficeAvailable($product['sdp_ip_address'], $product['sdp_ssh_user'], $product['sdp_ssh_password'], $product['sdp_ssh_port'], $product['sdp_directory']);
            }

            echo '<tr>';
            echo '<td class="host">' . $product['sdp_ip_address'] . '</td>';
            echo '<td class="installed' . ($installed ? 'yes' : 'no') . '">' . ($installed ? 'yes' : 'no') . '</td>';
            echo '<td class="available' . ($available ? 'yes' : 'no') . '">' . ($available ? 'yes' : 'no') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        break;

    /*     * *
     * 25/03/2010 BBX
     * Permet de contrôler si un trigramme est unique
     * */
    case 'checkTrigram' :
        $trigram = trim($_GET['trigram']);
        $exclude = trim($_GET['idProduct']);
        $result = ProductModel::trigramExists($trigram, $exclude);
        if ($result) {
            echo __T('A_SETUP_PRODUCTS_TRIGRAM_NOT_UNIQUE', $result);
        } else {
            echo 'UNIQUE';
        }
        break;

    /*     * *
     * 25/03/2010 BBX
     * Permet de contrôler si un process est lancé
     * */
    case 'checkProcess' :
        $idProduct = trim($_GET['idProduct']);
        $product = new ProductModel($idProduct);
        $result = $product->isProcessRunning();
        if ($result)
            echo $result;
        else
            echo 'false';
        break;

    /*     * *
     * 01/04/2010 BBX
     * Permet de contrôler si un process est lancé
     * */
    case 'checkMKProcess' :
        $idProduct = ProductModel::getIdMixedKpi();
        $product = new ProductModel($idProduct);
        $result = $product->isProcessRunning();
        if ($result)
            echo $result;
        else
            echo 'false';
        break;
}


// 22/12/2010 BBX
// Ends Maintenance
// BZ 18510
$masterModel->endMaintenance();

exit;

function checkDatabaseConnection($masterModel, $dbLogin, $dbPassword, $dbName, $dbPort, $dbIp) {
    // Test ping
    $cmd = "ping -c 1 {$dbIp} | head -n 2 | tail -n 1";
    exec($cmd, $result);
    // 18:21 12/08/2009 GHX
    // Correction du BZ 11017
    $result[0] = trim($result[0]);
    if (ereg('Unreachable', $result[0]) || $result[0] == '') {
        echo __T('A_SETUP_PRODUCTS_HOST_NOT_REACHABLE');

        // 22/12/2010 BBX
        // Ends Maintenance
        // BZ 18510
        $masterModel->endMaintenance();

        return false;
    }

    // Test de connexion à la base de données
    $db_test = @pg_connect("host=$dbIp port=$dbPort dbname=$dbName user=$dbLogin password=$dbPassword");
    if (!$db_test) {
        echo __T('A_SETUP_PRODUCTS_CANNOT_CONNECT_DATABASE');

        // 22/12/2010 BBX
        // Ends Maintenance
        // BZ 18510
        $masterModel->endMaintenance();

        return false;
    }

    return true;
}

?>