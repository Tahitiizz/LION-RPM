<?php
/*
 * 	@cb41000@
 *
 * 	11/12/2008 - Copyright Astellia
 *
 * 	Composant de base version cb_4.1.0.00
 *
 * 	12/12/2008 BBX : cr�ation de l'IHM de gestion des produits
 * 	23/01/2009 GHX
 * 		- Affiche uniquement les produits activ�s dans la liste des produits qui peuvent �tre un master topo (Seul les produits activ�s peuvent �tre un master topo)
 * 		- Lorsque l'on change de master topologie, on vide le mapping sur tous les produits
 *   06/04/2009 - SPS : 
 * 		- adaptation du CSS pour IE8
 * 	14/05/2009 - SPS :
 * 		- ajout d'une methode de test pour les connexion ssh (correction bug 9671)
 * 		- modification du style pour les elements de classe testConnection 
 * 		- ajout de tests pour verifier que les ports ssh et bdd ne depassent pas le max (65536) (correction bug 9672)
 * 	15/05/2009 - SPS : 
 * 		- titre de la page entre balises h1
 * 		- modification du style
 * 	08/06/2009 - SPS : 
 * 		- on verifie si l'utilisateur a saisi qqch comme user ssh
 *
 * 	 09/07/2009 - MPR 
 * 		- Correction du bug 10502 : un admin client doit uniquement pouvoir consulter les infos des products (readonly)
 * 	04/08/2009 GHX
 * 		- Ajout de la fonction suppression d'un produit
 * 	05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290
 * 	19/08/2009 GHX
 * 		- Ajout de la suppression d'un produit
 * 	21/10/2009 GHX
 * 		- Le produit Mixed KPI ne doit pas �tre visible et ne peut �tre le master topo
 * 		- Modification de la m�thode pour d�finit le master topo
 *
 * 	27/10/2009 BBX : modification de l'expression r�guli�re de test des adresses ip. BZ 12310
 *
 * 	01/12/2009 GHX :
 * 		- Correction du BZ 12979 [CB 5.0][Test Open Office] probl�me de lenteur connexion � cause de OOo
 * 			-> Ajout d'un bouton pour le test d'OpenOffice
 * 
 * 13/07/2011 NSE bz 21027 : ne pas supprimer un produit qui ne fait plus partie des produits (soumettre 2 fois le formulaire)
 * 17/11/2011 ACS BZ 24675 Wrong display after reactivating a product
 * 28/11/2011 ACS BZ 24868 use ajax call to delete a product
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
 * 16/12/2011 ACS BZ 21944 encode special character during ajax request
 * 21/12/2011 ACS BZ 25251 port number field not accessible under IE
 * 22/12/2011 ACS BZ 25254 test performed when click on "test" different to save test
 * 
 */
?>
<?php
session_start();
include_once dirname(__FILE__) . "/../../../../php/environnement_liens.php";

// Connexion � la base de donn�es locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// Librairies et classes requises
include_once('setup_products_display.php');

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . '/intranet_top.php');
include_once(REP_PHYSIQUE_NIVEAU_0 . '/php/menu_contextuel.php');
// On appel la classe de mapping (=>Si on change de master topo, on vide tout le mapping sur tous les produits)
include_once(REP_PHYSIQUE_NIVEAU_0 . '/class/mapping/Mapping.class.php');

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contr�le d'acc�s
/* // Contr�le d'acc�s
  $userModel = new UserModel($_SESSION['id_user']);
  $query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Setup Products'";
  $result = $database->getRow($query);
  $idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
  if(!$userModel->userAuthorized($idMenu)) {
  echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
  exit;
  } */

// Messages
$A_SETUP_PRODUCTS_PRODUCT_DISABLED = __T('A_SETUP_PRODUCTS_PRODUCT_DISABLED');
$A_SETUP_PRODUCTS_MASTER_PRODUCT = __T('A_SETUP_PRODUCTS_MASTER_PRODUCT');
$A_SETUP_PRODUCTS_TOPO_MASTER_PRODUCT = __T('A_SETUP_PRODUCTS_TOPO_MASTER_PRODUCT');
$A_SETUP_PRODUCTS_ALL_MASTER_PRODUCT = __T('A_SETUP_PRODUCTS_ALL_MASTER_PRODUCT');
$A_SETUP_PRODUCTS_EDIT = __T('A_SETUP_PRODUCTS_EDIT');
$A_SETUP_PRODUCTS_SAVE_SUCCESS = __T('A_SETUP_PRODUCTS_SAVE_SUCCESS');
$A_SETUP_PRODUCTS_CANNOT_DISABLE_MASTER = __T('A_SETUP_PRODUCTS_CANNOT_DISABLE_MASTER');
$A_SETUP_PRODUCTS_FORM_LABEL = __T('A_SETUP_PRODUCTS_FORM_LABEL');
$A_SETUP_PRODUCTS_FORM_IP = __T('A_SETUP_PRODUCTS_FORM_IP');
$A_SETUP_PRODUCTS_FORM_DIRECTORY = __T('A_SETUP_PRODUCTS_FORM_DIRECTORY');
$A_SETUP_PRODUCTS_FORM_DB_NAME = __T('A_SETUP_PRODUCTS_FORM_DB_NAME');
$A_SETUP_PRODUCTS_FORM_DB_PORT = __T('A_SETUP_PRODUCTS_FORM_DB_PORT');
$A_SETUP_PRODUCTS_FORM_DB_LOGIN = __T('A_SETUP_PRODUCTS_FORM_DB_LOGIN');
$A_SETUP_PRODUCTS_CHECK_NEW = __T('A_SETUP_PRODUCTS_CHECK_NEW');
$A_SETUP_PRODUCTS_TEST_CONNECTION_OK = __T('A_SETUP_PRODUCTS_TEST_CONNECTION_OK');
$A_SETUP_PRODUCTS_TEST_CONNECTION_FAILED = __T('A_SETUP_PRODUCTS_TEST_CONNECTION_FAILED');
$A_SETUP_PRODUCTS_FORM_SSH_USER = __T('A_SETUP_PRODUCTS_FORM_SSH_USER');
$A_SETUP_PRODUCTS_FORM_SSH_PASSWORD = __T('A_SETUP_PRODUCTS_FORM_SSH_PASSWORD');
$A_SETUP_PRODUCTS_FORM_SSH_PORT = __T('A_SETUP_PRODUCTS_FORM_SSH_PORT');
$A_SETUP_PRODUCTS_CONFIRM_MASTER = __T('A_SETUP_PRODUCTS_CONFIRM_MASTER');
$A_SETUP_PRODUCTS_CB_INVALID = __T('A_SETUP_PRODUCTS_CB_INVALID');
$A_SETUP_PRODUCTS_DEFINE_MASTER = __T('A_SETUP_PRODUCTS_DEFINE_MASTER');
$A_SETUP_PRODUCTS_PRODUCT_ALREADY_EXISTS = __T('A_SETUP_PRODUCTS_PRODUCT_ALREADY_EXISTS');
// 22/09/2010 BBX BZ 18091 : correction de l'ID "A_SETUP_PRODUCTS_CONFIRM_DELETE_SLAVE".
$A_SETUP_PRODUCTS_CONFIRM_DELETE_SLAVE = __T('A_SETUP_PRODUCTS_CONFIRM_DELETE_SLAVE', 'XXXX');
$A_SETUP_PRODUCTS_TRIGRAM_3_CHARS = __T('A_SETUP_PRODUCTS_TRIGRAM_3_CHARS');
$A_SETUP_PRODUCTS_TRIGRAM_SPEC_CHARS = __T('A_SETUP_PRODUCTS_TRIGRAM_SPEC_CHARS');
$A_SETUP_PRODUCTS_TRIGRAM_NOT_UNIQUE = __T('A_SETUP_PRODUCTS_TRIGRAM_NOT_UNIQUE');
$A_SETUP_PRODUCTS_TRIGRAM_PROCESS = __T('A_SETUP_PRODUCTS_TRIGRAM_PROCESS');


// maj 09/07/2009 MPR : Correction du bug 10502 : un admin client doit uniquement pouvoir consulter les infos des products (readonly)
// R�cup�ration du type de client
$client_type = getClientType($_SESSION['id_user']);
// On bloque toutes les balises html afin de passer en mode readonly pour les clients
$readonly = ( $client_type == 'client' ) ? " readonly" : "";

// 22/12/2010 BBX BZ 18510 : Set Maintenance mode
$masterModel = new ProductModel(ProductModel::getIdMaster());
$masterModel->beginMaintenance();

// A t-on demand� la red�finition du master Topo ?
if (isset($_POST['topoMaster'])) {
    // Cr�ation d'une instance de la classe de Mapping
    $mapping = new Mapping('');

    // PENSER A CREER LES PROCESS LIES AU CHANGEMENT DE MASTER TOPO
    // On s'assure que les autres produits ne sont plus master topo
    foreach (ProductModel::getProducts() as $product) {
        $ProductModel = new ProductModel($product['sdp_id']);
        $ProductModel->setValue('sdp_master_topo', '0');
        $ProductModel->updateProduct();

        // 23/01/2009 GHX
        // Si on change de master topo, on vide tout le mapping sur tous les produits
        try {
            $mapping->setProductMapped($product);
            $mapping->truncate();
        } catch (Exception $e) {
            echo '<div class="errorMsg">Truncate mapping : ' . $e->getMessage() . '</div>';
        }
    }
    // 16:07 21/10/2009 GHX
    // Modification de la m�thode pour d�finit le master topo
    // On passe le produit concern� en master topo
    ProductModel::setAsMasterTopology($_POST['topoMaster']);
}

// 22/12/2010 BBX BZ 18510 : Ends Maintenance
$masterModel->endMaintenance();

// R�cup�ration des produits existants
$productsArray = ProductModel::getProducts();
// 15:04 21/10/2009 GHX
// On r�cup�re l'id du produit Mixed KPI car il ne doit pas �tre affich�
$idProductMixedKPI = ProductModel::getIdMixedKpi();
?>
<style>
    .productBox {
        position:relative;
        width:600px;
        margin:10px auto 0px auto;
        border:1px solid #898989;
        font-family:Arial;
        font-size:8pt;
        color:#585858;
        background-image:url(<?= NIVEAU_0 ?>images/fonds/fond_setup_products_mini.png);
        text-align:left;
        /* 06/04/2009 - modif SPS : adaptation pour IE8*/
        clear:both;
        min-height:70px;
        _height:70px;
    }
    .productBoxOff {
        position:relative;
        width:600px;
        margin:10px auto 0 auto;
        border:1px solid #898989;
        font-family:Arial;
        font-size:8pt;
        color:#CCCCCC;
        background-image:url(<?= NIVEAU_0 ?>images/fonds/fond_setup_products_mini.png);
        text-align:left;
        /* 06/04/2009 - modif SPS : adaptation pour IE8*/
        clear:both;
        min-height:70px;
        _height:70px;
    }
    .productIcone {
        float:left;
        background-image:url(<?= NIVEAU_0 ?>images/icones/database.png);
        background-repeat:no-repeat;
        width:64px;
        height:70px;
    }
    .productInfos {
        float:left;
        padding:5px;
        width:450px;
    }
    .productInfos2 {
        float:right;
        padding:5px;
        padding-left:50px;
    }
    .corner_top_left {
        position:absolute;
        /* 06/04/2009 - modif SPS : adaptation pour IE8*/
        background-color:#898989;
        width:1px;
        height:1px;
        top:-1px;
        left:-1px;
    }
    .corner_top_right {
        position:absolute;
        /* 06/04/2009 - modif SPS : adaptation pour IE8*/
        background-color:#898989;
        width:1px;
        height:1px;
        top:-1px;
        right:-1px;
    }
    .corner_bottom_right {
        position:absolute;
        /* 06/04/2009 - modif SPS : adaptation pour IE8*/
        background-color:#898989;
        width:1px;
        height:1px;
        bottom:-1px;
        right:-1px;
    }
    .corner_bottom_left {
        position:absolute;
        /* 06/04/2009 - modif SPS : adaptation pour IE8*/
        background-color:#898989;
        width:1px;
        height:1px;
        bottom:-1px;
        left:-1px;
    }
    .productInfos .label {
        font-size:12pt;
        font-weight:bold;
        text-align:left;
    }
    .productInfos .ip {
        font-size:8pt;
        font-weight:bold;
        text-align:left;
    }
    .productInfos2 .db_name {
        font-size:8pt;
        font-weight:bold;
    }
    .masterProduct {
        position:absolute;
        top:0;
        left:0;
        width:32px;
        height:32px;
        background-image:url(<?= NIVEAU_0 ?>images/icones/master.png);
    }
    .editButton {
        position:absolute;
        width:50px;
        height:50px;
        top:40px;
        right:10px;
    }
    .masterButton {
        position:absolute;
        width:50px;
        height:50px;
        top:40px;
        right:175px;
    }
    .editProduct {
        position:relative;
        width:500px;
        /*height:360px; 15/09/2010 OJT Correction bz17917, suppression height et mise du padding */
        padding-bottom:10px;
        text-align:left;
        margin:auto;
        /* 06/04/2009 - modif SPS : adaptation pour IE8*/
        clear:both;
    }
    /* 15/09/2010 OJT : Cr�ation nouveau style pour optimisation graphique */
    div.editProductLine{
        float:left;
        width:360px;
        margin-bottom: 2px;
    }
    .editProduct input {
        font-size:7pt;
        width:75px;
    }
    .editProduct label {
        font-family:Arial;
        font-size:8pt;
    }
    .disabledProduct {
        position:absolute;
        top:50px;
        left:45px;
        background-image:url(<?= NIVEAU_0 ?>images/icones/exclamation.png);
        width:16px;
        height:16px;
    }
    .formLabel {
        width:125px;
        float:left;
        /* 15/05/2009 - SPS : modif du style */
        clear:both;
    }
    .testConnection {
        cursor:pointer;
        font-size:8pt;
        text-decoration:underline;
        /* 14/05/2009 - SPS : modification du style pour les elements de classe testConnection */
        float:right;
        text-align:right;
    }

    /* 15/05/2009 - SPS : ajout du style pour la classe maxLabel*/
    .maxLabel{
        font : normal 6pt Verdana, Arial, sans-serif;
        color: #999;
        width: 130px;
    }
    /* 10:48 15/06/2009 GHX */
    .downloadLogFile {
        text-align:center;
    }
    /* 01/12/2009 GHX */
    #tabCheckOOo {
        width:85%;
        font-size:10pt;
        border-spacing: 0px;
        border-collapse: collapse; 
        margin-left:auto;
        margin-right:auto;
        text-align:center;
        margin-top:10px;
    }
    #tabCheckOOo th{
        background-color: #d4d2d2;
        color: #585858;
    }
    #tabCheckOOo th, #tabCheckOOo td {
        border: 1px solid black;
    }
    #tabCheckOOo .installedno, #tabCheckOOo .availableno {
        color:red;
    }
    #tabCheckOOo .installedyes, #tabCheckOOo .availableyes {
        color:green;
    }
</style>

<script type="text/javascript">
    /********************
     * Lance l'edition d'un produit
     ********************/
    function editProduct(idProduct) {
        if ($('editProduct_' + idProduct).style.display == 'block') {
            $('editProduct_' + idProduct).setStyle({display: "none"});
            $('product_' + idProduct).setStyle({backgroundImage: "url('<?= NIVEAU_0 ?>images/fonds/fond_setup_products_mini.png')"});
        } else {
            $('editProduct_' + idProduct).setStyle({display: "block"});
            $('product_' + idProduct).setStyle({backgroundImage: "url('<?= NIVEAU_0 ?>images/fonds/fond_setup_products_maxi.png')"});
        }
    }

    function displayLoadIcon(idProduct) {
        // Nettoyage, reformattage des zones dynamiques
        $('infos_' + idProduct).className = '';
        $('infos_' + idProduct).update('<img src="<?= NIVEAU_0 ?>images/animation/indicator_snake.gif" />');
        $('infos_' + idProduct).setStyle({display: 'block'});
        $('download_' + idProduct).setStyle({display: "none"});

        // Condamnation du bouton save, delete, enable et disable
        deactivateButtons(idProduct, true);
    }

    function deactivateButtons(idProduct, deactivate) {
        // Restauration du bouton Save, delete, enable, disable
        $('save_button_' + idProduct).disabled = deactivate;
        $('on_off_button_' + idProduct).disabled = deactivate;
        if ($('delete_button_' + idProduct)) {
            $('delete_button_' + idProduct).disabled = deactivate;
        }
    }


    /********************
     * Sauvegarde la conf d'un produit
     // 14/12/2010 BBX
     // Modifications pour prendre en compte la nouvelle IHM
     // BZ 18510
     ********************/
    function saveProduct(idProduct) {
        // Controle des donnees saisies
        if (!checkForm(idProduct)) {
            return false;
        }

<?php
// 25/03/2010 BBX
// Contr�le trigramme si Mixed KPI
if (ProductModel::getIdMixedKpi()) {
    ?>
            if (!checkTrigrams(idProduct)) {
                return false;
            }
    <?php
}
?>

        /* 14/05/2009 - SPS : on affiche une icone de chargement*/
        displayLoadIcon(idProduct);

        // On transmet le formulaire via Ajax
        new Ajax.Request('setup_products_ajax.php?action=1', {
            method: 'post',
            parameters: $('form_' + idProduct).serialize(),
            onSuccess: function (transport) {
                var resultat = transport.responseText;
                if (resultat.substr(0, 2) == 'OK')
                {
                    // On cache les interfaces d'edition
                    editProduct(idProduct);
                    // Si nouveau produit, recuperation de l'id
                    var tabRes = resultat.split('|');
                    // 10:05 15/06/2009 GHX
                    // Modification suite a la r�cup�ration du nom du fichier de log quand on active un produit
                    // Correction du BZ 9666
                    log = tabRes[1];
                    if (tabRes.length == 3) {
                        // Et on ajoute ce produit avec son ID, apres suppression de celui a ID 0
                        $('product_' + idProduct).remove();
                        getProduct(tabRes[1]);
                        idProduct = tabRes[1];
                        log = tabRes[2];
                    }

                    // Info tout est ok
                    $('infos_' + idProduct).className = 'okMsg';
                    $('infos_' + idProduct).update('<?= $A_SETUP_PRODUCTS_SAVE_SUCCESS ?>');
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 1000);

                    // Gestion activation / desactivation
                    // 14/12/2010 BBX
                    // Modification pour prendre en compte la nouvelle IHM
                    // BZ 18510

                    // Help for most cases
                    if ($('help_auto_act_' + idProduct))
                        $('help_auto_act_' + idProduct).update('<?= __T('A_SETUP_PRODUCT_HELP_AUTO_ACT') ?>');

                    // PRODUCT IS ACTIVE
                    if ($('onOff_' + idProduct).value == '1')
                    {
                        // Active css class
                        $('product_' + idProduct).className = 'productBox';

                        // 30/12/2010 BBX : Correction de la suppression de l'icone
                        // Il s'av�re qu'il faut la supprimer plusieurs fois
                        // 20/01/2011 BBX : En fait non il suffit de tester la cr�ation de l'�l�ment
                        // Pour ne pas le cr�er plusieurs fois
                        if ($('disabled_icon_' + idProduct)) {
                            $('disabled_icon_' + idProduct).absolutize();
                            $('disabled_icon_' + idProduct).remove();
                        }

                        $('comp_status_' + idProduct).update('ON');
                        if ($('additionnal_information_' + idProduct))
                            $('additionnal_information_' + idProduct).remove();

                        // 17/11/2011 ACS BZ 24675 Wrong display after reactivating a product
                        // Checking automatic activation box if activation
                        if ($('chkbx_aa_' + idProduct) && $('askForAct_' + idProduct).value == '1') {
                            $('chkbx_aa_' + idProduct).checked = true;
                            $('chkbx_aa_' + idProduct).disabled = false;
                            $('autoAct_' + idProduct).value = '1';
                            $('lastDesac_' + idProduct).value = 0;
                        }

                        // Possibility to disable
                        $('on_off_button_' + idProduct).value = 'Disable';
                        $('on_off_button_' + idProduct).onclick = function () {
                            disableProduct(idProduct);
                        }
                    } else
                    {
                        // Incative css class
                        $('product_' + idProduct).className = 'productBoxOff';

                        // 30/12/2010 BBX BZ 19931 : Ajout d'un test sur la cr�ation de l'�lement
                        if (!$('disabled_icon_' + idProduct)) {
                            $('product_' + idProduct).insert('<div id="disabled_icon_' + idProduct + '" class="disabledProduct" onmouseover="popalt(\'<?= $A_SETUP_PRODUCTS_PRODUCT_DISABLED ?>\')"></div>');
                        }
                        $('comp_status_' + idProduct).update('OFF');

                        // Unchecking and disabling automatic activation box
                        if ($('lastDesac_' + idProduct).value == 0 || $('lastDesac_' + idProduct).value == '') {
                            if ($('chkbx_aa_' + idProduct)) {
                                $('chkbx_aa_' + idProduct).checked = false;
                                $('chkbx_aa_' + idProduct).disabled = true;
                                $('autoAct_' + idProduct).value = '0';
                                $('help_auto_act_' + idProduct).update('<?= __T('A_SETUP_PRODUCT_HELP_AUTO_ACT_RO') ?>');
                            }
                        }

                        // Possibility to enable
                        $('on_off_button_' + idProduct).value = 'Enable';
                        $('on_off_button_' + idProduct).onclick = function () {
                            enableProduct(idProduct);
                        }
                    }

                    // Mise a jour label, ip, bdd
                    $('product_label_' + idProduct).update($('label_' + idProduct).value);
                    $('product_ip_' + idProduct).update($('ip_' + idProduct).value);
                    $('product_database_' + idProduct).update('Database : ' + $('db_name_' + idProduct).value);

                    //01/07/2009 : BBX
                    // BZ 10306 : Si le produit n'est pas pr�sent dans la liste des master topo, on l'ajoute
                    // 12/12/2011 ACS DE Mantis 837 topology master is not configurable in readonly mode
<?php if ($readonly == "") { ?>
                        productInSelectList = false;
                        for (p = 0; p < $('topoMasterSelect').options.length; p++) {
                            if ($('topoMasterSelect').options[p].value == idProduct) {
                                productInSelectList = true;
                            }
                        }
                        if (!productInSelectList && idProduct != $idProductMixedKPI) {
                            $('topoMasterSelect').options[$('topoMasterSelect').options.length] = new Option($('label_' + idProduct).value, idProduct, false, false);
                        }
<?php } ?>

                    // 10:49 15/06/2009 GHX
                    // BZ9666 si on a un fichier de log on propose le telechargement de celui-ci
                    if (log != "")
                    {
                        $('download_' + idProduct).setStyle({display: "block"});
                        $('downloadLogFile_' + idProduct).writeAttribute({href: log});
                    }
                } else {
                    if ((resultat == '<?= $A_SETUP_PRODUCTS_CB_INVALID ?>') || (resultat == '<?$A_SETUP_PRODUCTS_DEFINE_MASTER?>') || (resultat == '<?$A_SETUP_PRODUCTS_PRODUCT_ALREADY_EXISTS?>')) {
                        //$('checkBox_'+idProduct).checked = false;
                        $('product_' + idProduct).className = 'productBoxOff';
                        $('product_' + idProduct).insert('<div id="disabled_icon_' + idProduct + '" class="disabledProduct" onmouseover="popalt(\'<?= $A_SETUP_PRODUCTS_PRODUCT_DISABLED ?>\')"></div>');
                    }
                    $('infos_' + idProduct).className = 'errorMsg';
                    $('infos_' + idProduct).update(resultat);
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);

                    // 20/01/2011 BBX BZ 18510 : Si une activation a �chou�e, on reste a off
                    // 09/12/2011 ACS Mantis 837 DE HTTPS support (save button still greyed out after saving the master product configuration)
                    if ($('askForAct_' + idProduct) && $('askForAct_' + idProduct).value == '1') {
                        $('onOff_' + idProduct).value = '0';
                    }
                }
                deactivateButtons(idProduct, false);

                // 20/01/2011 BBX 18510 : Mode activation termin�
                $('askForAct_' + idProduct).value = '0';
            }
        });
    }

// 10/01/2011 (merge) : NSE remplacement de function onOff(idProduct)  par enableProduct(idProduct) et disableProduct(idProduct)

    /**
     * Enables a product
     **/
    function enableProduct(idProduct) {
        $('onOff_' + idProduct).value = '1';
        // 20/01/2011 BBX
        // BZ 18510
        $('askForAct_' + idProduct).value = '1';
        saveProduct(idProduct);
    }

    /**
     * Disables a product
     **/
    function disableProduct(idProduct) {
        $('onOff_' + idProduct).value = '0';
        // 20/01/2011 BBX
        // BZ 18510
        $('autoAct_' + idProduct).value = '0';
        saveProduct(idProduct);
    }

    /**
     * manages automatic activation
     **/
    function autoAct(idProduct) {
        if ($('chkbx_aa_' + idProduct).checked) {
            $('autoAct_' + idProduct).value = '1';
        } else {
            $('autoAct_' + idProduct).value = '0';
        }
    }

    /********************
     * Ajoute un produit
     ********************/
    function addProduct() {
        // On commence par regarder si un nouveau produit non sauve existe deja
        if ($('product_0')) {
            alert('<?= $A_SETUP_PRODUCTS_CHECK_NEW ?>');
            return false;
        }
        // Si tout est OK, on ajoute un nouveau produit
        new Ajax.Request('setup_products_ajax.php?action=2', {
            onSuccess: function (transport) {
                var newProd = transport.responseText;
                $('productsContainer').insert(newProd);
                editProduct('0');
            }
        });

    }

    /********************
     * Recupere un produit
     ********************/
    function getProduct(idProduct) {
        // r�cup�ration d'un produit via ajax
        new Ajax.Request('setup_products_ajax.php?action=4', {
            method: 'get',
            parameters: 'idProd=' + idProduct,
            asynchronous: false,
            onSuccess: function (transport) {
                var newProd = transport.responseText;
                $('productsContainer').insert(newProd);
            }
        });
    }

    /********************
     * Controle un formulaire produit
     ********************/
    function checkForm(idProduct)
    {
        // Motif de controle des ch�ines
        var StringExp = new RegExp(/^[\w][\w\.\s\-]+$/);
        // Motif de controle des pour les dossier
        var FolderExp = new RegExp(/^[\w][\w\.\s\-]+(\/mixed_kpi_product)?$/);//seul le dossier mixed_kpi_product est autoris�
        // Motif de contr�le des adresses IP
        // 27/10/2009 BBX : modification de l'expression r�guli�re de test des adresses ip. BZ 12310
        var IpExp = new RegExp(/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/);
        // Motif de contr�le d'un numero de port
        var PortExp = new RegExp(/^(\d{1,5})$/);
        /* 14/05/2009 - SPS : maximum pour le port*/
        var MaxPort = 65536;

        // Controle du label
        if (!StringExp.test($('label_' + idProduct).value)) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_LABEL ?>');
            $('label_' + idProduct).focus();
            return false;
        }

        // Controle de l'adresse IP 
        if (!IpExp.test($('ip_' + idProduct).value) && !StringExp.test($('ip_' + idProduct).value)) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_IP ?>');
            $('ip_' + idProduct).focus();
            return false;
        }

        // Controle du repertoire
        if (!FolderExp.test($('directory_' + idProduct).value)) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_DIRECTORY ?>');
            $('directory_' + idProduct).focus();
            return false;
        }

        // Controle du nom de la base de donnees
        if (!StringExp.test($('db_name_' + idProduct).value)) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_DB_NAME ?>');
            $('db_name_' + idProduct).focus();
            return false;
        }

        /* 14/05/2009 - SPS : ajout du test pour verifier que le port ne depasse pas le max (correction bug 9672)*/
        // Controle du port de la base de donnees
        if (!PortExp.test($('db_port_' + idProduct).value) || $('db_port_' + idProduct).value > MaxPort) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_DB_PORT ?>');
            $('db_port_' + idProduct).focus();
            return false;
        }

        // Controle du login de la base de donnees
        if (!StringExp.test($('db_login_' + idProduct).value)) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_DB_LOGIN ?>');
            $('db_login_' + idProduct).focus();
            return false;
        }

        /* 08/06/2009 - SPS : on verifie si l'utilisateur a saisi qqch comme user ssh*/
        // Controle User SSH
        if ($('ssh_user_' + idProduct).value != '' && !StringExp.test($('ssh_user_' + idProduct).value)) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_SSH_USER ?>');
            $('ssh_user_' + idProduct).focus();
            return false;
        }

        /* 08/06/2009 - SPS : on verifie si l'utilisateur a saisi qqch comme user ssh*/
        // Controle Password SSH
        if ($('ssh_user_' + idProduct).value != '' && $('ssh_password_' + idProduct).value == '') {
            alert('<?= $A_SETUP_PRODUCTS_FORM_SSH_PASSWORD ?>');
            $('ssh_password_' + idProduct).focus();
            return false;
        }

        /* 14/05/2009 - SPS : ajout du test pour verifier que le port ne depasse pas le max (correction bug 9672) */
        // Controle port SSH
        if (!PortExp.test($('ssh_port_' + idProduct).value) || $('ssh_port_' + idProduct).value > MaxPort) {
            alert('<?= $A_SETUP_PRODUCTS_FORM_SSH_PORT ?>');
            $('ssh_port_' + idProduct).focus();
            return false;
        }

        // 09/12/2011 ACS Mantis 837 DE HTTPS support
        // Control protocol port
        if ($('https_port_' + idProduct).value != '' && !PortExp.test($('https_port_' + idProduct).value)) {
            alert('<?= __T('A_SETUP_PRODUCTS_FORM_PROTOCOL_PORT') ?>');
            $('https_port_' + idProduct).focus();
            return false;
        }

        // Tout est OK
        return true;
    }

    /********************
     * Test la connexion a la base de donnees
     ********************/
    function testDbConnection(idProduct) {
        // On affiche un chenillard
        $('test_db_' + idProduct).update('<img src="<?= NIVEAU_0 ?>images/animation/indicator_snake.gif" />');
        $('test_db_' + idProduct).onclick = null;
        // Recuperation des infos de connexion
        var dbLogin = $('db_login_' + idProduct).value;
        var dbPassword = $('db_password_' + idProduct).value;
        var dbName = $('db_name_' + idProduct).value;
        var dbPort = $('db_port_' + idProduct).value;
        var dbIp = $('ip_' + idProduct).value;
        // Test de connexion via Ajax
        new Ajax.Request('setup_products_ajax.php?action=3', {
            method: 'get',
            parameters: 'dbLogin=' + encodeURIComponent(dbLogin) + '&dbPassword=' + encodeURIComponent(dbPassword) + '&dbName=' + encodeURIComponent(dbName) + '&dbPort=' + dbPort + '&dbIp=' + dbIp,
            onSuccess: function (transport) {
                if (transport.responseText != '') {
                    $('infos_' + idProduct).className = 'errorMsg';
                    $('infos_' + idProduct).update(transport.responseText);
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);
                } else {
                    $('infos_' + idProduct).className = 'okMsg';
                    $('infos_' + idProduct).update('<?= $A_SETUP_PRODUCTS_TEST_CONNECTION_OK ?>');
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 1000);
                }
                // Restauration du test
                $('test_db_' + idProduct).update('Test DB connection');
                $('test_db_' + idProduct).onclick = function () {
                    testDbConnection(idProduct);
                }
            }
        });
    }

    /**
     * test de la connexion SSH
     * 14/05/2009 - SPS (correction bug 9671)
     */
    function testSSHConnection(idProduct) {
        //on affiche une icone de chargement
        $('test_ssh_' + idProduct).update('<img src="<?= NIVEAU_0 ?>images/animation/indicator_snake.gif" />');
        $('test_ssh_' + idProduct).onclick = null;

        //on recupere les valeurs des champs necessaires
        var sshUser = $('ssh_user_' + idProduct).value;
        var sshPassword = $('ssh_password_' + idProduct).value;
        var sshIP = $('ip_' + idProduct).value;
        var sshPort = $('ssh_port_' + idProduct).value;

        //on va tester la connexion ssh
<?php // 16/12/2011 ACS BZ 21944 encode special character during ajax request   ?>
        new Ajax.Request('setup_products_ajax.php?action=6', {
            method: 'get',
            parameters: 'sshUser=' + encodeURIComponent(sshUser) + '&sshPassword=' + encodeURIComponent(sshPassword) + '&sshIP=' + sshIP + '&sshPort=' + sshPort,
            onSuccess: function (transport) {
                if (transport.responseText != '') {
                    //si on a une reponse, cela signifie que l'on a une erreur, et on va donc l'afficher
                    $('infos_' + idProduct).className = 'errorMsg';
                    $('infos_' + idProduct).update(transport.responseText);
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);
                } else {
                    //si pas de reponse => tout s'est bien passe, on affiche que c'est un succes
                    $('infos_' + idProduct).className = 'okMsg';
                    $('infos_' + idProduct).update('<?= $A_SETUP_PRODUCTS_TEST_CONNECTION_OK ?>');
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 1000);
                }
                //on reinitialise le lien pour le test
                $('test_ssh_' + idProduct).update('Test SSH connection');
                $('test_ssh_' + idProduct).onclick = function () {
                    testSSHConnection(idProduct);
                }
            }
        });
    }



    /********************
     * Passe un produit en master
     ********************/
    function setAsMaster(idProduct) {
        if (confirm('<?= $A_SETUP_PRODUCTS_CONFIRM_MASTER ?>')) {
            new Ajax.Request('setup_products_ajax.php?action=5', {
                method: 'get',
                parameters: 'idProduct=' + idProduct,
                onSuccess: function (transport) {
                    document.location.href = 'setup_products.php';
                }
            });
        }
    }

    /**
     * Delete a slave product
     *
     * @param int idProduct : product id
     * 
     * 28/11/2011 ACS : bz24868 use ajax call to delete a product
     * 11/01/2011 OJT : bz25443 ajout du label du produit en param�tre
     */
    function deleteProduct(idProduct) {
        // ask for delete confirmation
        var msgDelete = '<?php echo str_replace("'", "\'", $A_SETUP_PRODUCTS_CONFIRM_DELETE_SLAVE); ?>';
        msgDelete = msgDelete.replace('XXXX', $('label_' + idProduct).value);
        if (confirm(msgDelete)) {
            // delete confirmed

            displayLoadIcon(idProduct);

            new Ajax.Request('setup_products_ajax.php?action=deleteProduct', {
                method: 'get',
                parameters: 'idProd=' + idProduct + '&labelProd=' + $('label_' + idProduct).value,
                onSuccess: function (transport) {
                    var resp = transport.responseText.split(';');
                    // result is OK. Display message and remove this product from view
                    if (resp[0] == "OK") {
                        if (resp[1]) {
                            $('msg').className = "okMsg";
                            $('msg').style.display = "";
                            $('msg').update(resp[1]);
                            // hide message after few seconds
                            setTimeout("Effect.toggle('msg','appear')", 3000);
                        }

                        $('product_' + idProduct).remove();
                    }
                    // result is KO
                    else {
                        // display the error (if available)
                        if (resp[1]) {
                            $('infos_' + idProduct).className = "errorMsg";
                            $('infos_' + idProduct).style.display = "";
                            $('infos_' + idProduct).update(resp[1]);

                            // hide message after few seconds
                            setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 3000);
                        } else {
                            $('infos_' + idProduct).update();
                        }
                    }

                    // reactivate buttons
                    deactivateButtons(idProduct, false);
                }
            });
        }
    }


    var winOOo;
    /**
     * Check OpenOffice if is installed correctly on all servers
     *	01/12/2009 GHX
     *		- BZ 12979 [CB 5.0][Test Open Office] probleme de lenteur connexion a cause de OOo
     */
    function checkOpenOffice()
    {
        winOOo = new Window({
            className: "alphacube",
            title: "<?php echo __T('A_OPEN_OFFICE_BTN_LABEL_TEST'); ?>",
            width: 410, height: 150,
            minWidth: 410, minHeight: 100,
            resizable: false,
            minimizable: false,
            recenterAuto: false,
            maximizable: false,
            destroyOnClose: true
        });
        winOOo.setHTMLContent("<p style=\"text-align:center\"><img src=\"<?php echo NIVEAU_0; ?>images/animation/indicator_snake.gif\" /><?php echo __T("A_OPEN_OFFICE_TEST_PROGESSING") . '</p><p style=\"padding:5px;\">' . __T('G_E_OPEN_OFFICE_ERRORS_INFO') . '</p>'; ?>");
        winOOo.showCenter();

        new Ajax.Request('setup_products_ajax.php?action=checkOOo', {
            onSuccess: function (transport) {
                winOOo.setHTMLContent(transport.responseText + '<p style="text-align:center"><br /><input type="button" class="bouton" value="Close" onclick="winOOo.destroy()" /></p>');
            }
        });
    } // End function checkOpenOffice

    /**
     * V�rifie que les contraintes li�es au trigramme sont valid�es
     * 25/03/2010 BBX
     */
    function checkTrigrams(idProduct)
    {
        // R�cup�ration du trigramme
        var trigram = $('trigram_' + idProduct).value;

        // Contrainte 1
        // Le trigramme doit faire 3 caract�res
        if (trigram.length != 3)
        {
            // Affichage de l'erreur
            $('infos_' + idProduct).className = 'errorMsg';
            $('infos_' + idProduct).update('<?= $A_SETUP_PRODUCTS_TRIGRAM_3_CHARS ?>');
            $('infos_' + idProduct).setStyle({display: 'block'});
            setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);
            // Retourne false
            return false;
        }

        // Contrainte 2
        // Le trigramme ne doit contenir que des lettres
        var trigramExpression = new RegExp(/^[a-zA-Z]{3}$/);
        if (!trigramExpression.test(trigram))
        {
            // Affichage de l'erreur
            $('infos_' + idProduct).className = 'errorMsg';
            $('infos_' + idProduct).update('<?= $A_SETUP_PRODUCTS_TRIGRAM_SPEC_CHARS ?>');
            $('infos_' + idProduct).setStyle({display: 'block'});
            setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);
            // Retourne false
            return false;
        }

        // Contrainte 3
        // Le trigramme doit �tre unique
        var resultat = 'UNIQUE';
        new Ajax.Request('setup_products_ajax.php?action=checkTrigram&trigram=' + trigram + '&idProduct=' + idProduct, {
            asynchronous: false,
            onSuccess: function (transport) {
                resultat = transport.responseText;
            }
        });
        if (resultat != 'UNIQUE')
        {
            // Affichage de l'erreur
            $('infos_' + idProduct).className = 'errorMsg';
            $('infos_' + idProduct).update(resultat);
            $('infos_' + idProduct).setStyle({display: 'block'});
            setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);
            // Retourne false
            return false;
        }

        // Contrainte 4
        // Aucun process ne doit �tre lanc�
        var resultat = 'false';
        new Ajax.Request('setup_products_ajax.php?action=checkMKProcess', {
            method: 'post',
            asynchronous: false,
            onSuccess: function (transport) {
                resultat = transport.responseText;
            }
        });
        if (resultat != 'false')
        {
            // Affichage de l'erreur
            var error = '<?= __T('A_SETUP_PRODUCTS_TRIGRAM_PROCESS', '%process%') ?>';
            $('infos_' + idProduct).className = 'errorMsg';
            $('infos_' + idProduct).update(error.replace('%process%', resultat));
            $('infos_' + idProduct).setStyle({display: 'block'});
            setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);
            // Retourne false
            return false;
        }

        // Si tout est OK, retourne vrai
        return true;
    }

<?php // BEGIN -- 09/12/2011 ACS Mantis 837 DE HTTPS support   ?>
    /**
     * Allow the configuration of the https port if https is allowed on the slave
     */
// 21/12/2011 ACS BZ 25251 port number field not accessible under IE
    function changeHttpsStatus(idProduct, isMaster) {
        if (isMaster) {
            var readonly = !$('radio_https_' + idProduct).checked;
        } else {
            var readonly = !$('chkbx_https_' + idProduct).checked;
        }
        if (readonly) {
            $('https_port_' + idProduct).value = '';
            $('https_port_' + idProduct).readOnly = true;
        } else {
            $('https_port_' + idProduct).readOnly = false;
        }
    }

    function testProtocolConnection(idProduct, isMaster) {
        // display loading icon
        $('test_protocol_' + idProduct).update('<img src="<?= NIVEAU_0 ?>images/animation/indicator_snake.gif" />');
        $('test_protocol_' + idProduct).onclick = null;
        var dbLogin = $('db_login_' + idProduct).value;
        var dbPassword = $('db_password_' + idProduct).value;
        var dbName = $('db_name_' + idProduct).value;
        var dbPort = $('db_port_' + idProduct).value;

        // retrieve needed information
        var productIP = $('ip_' + idProduct).value;
        var directory = $('directory_' + idProduct).value;
        if (isMaster) {
            var http = $('radio_http_' + idProduct).checked;
            var https = $('radio_https_' + idProduct).checked;
        } else {
            var http = $('chkbx_http_' + idProduct).checked;
            var https = $('chkbx_https_' + idProduct).checked;
        }
        var https_port = $('https_port_' + idProduct).value;

        // test protocol connection
        // 22/12/2011 ACS BZ 25254 test performed when click on "test" different to save test
        new Ajax.Request('setup_products_ajax.php?action=checkProtocolConn', {
            method: 'get',
            parameters: 'productIP=' + productIP + '&directory=' + directory + '&dbLogin=' + dbLogin + '&dbPassword=' + dbPassword + '&dbName=' + dbName + '&dbPort=' + dbPort + '&http=' + http + '&https=' + https + '&https_port=' + https_port,
            onSuccess: function (transport) {
                if (transport.responseText != '') {
                    // in case of response, it means an error occured
                    $('infos_' + idProduct).className = 'errorMsg';
                    $('infos_' + idProduct).update(transport.responseText);
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 5000);
                } else {
                    // in case of empty response, it means that connection works fine
                    $('infos_' + idProduct).className = 'okMsg';
                    $('infos_' + idProduct).update('<?= $A_SETUP_PRODUCTS_TEST_CONNECTION_OK ?>');
                    $('infos_' + idProduct).setStyle({display: 'block'});
                    setTimeout("Effect.toggle('infos_" + idProduct + "','appear')", 1000);
                }
                // reactivate link
                $('test_protocol_' + idProduct).update('<?= __T('A_SETUP_PRODUCT_PROTOCOL_TEST') ?>');
                $('test_protocol_' + idProduct).onclick = function () {
                    testProtocolConnection(idProduct, isMaster);
                }
            }
        });
    }
<?php // END -- 09/12/2011 ACS Mantis 837 DE HTTPS support   ?>
</script>

<div align="center">
    <!-- 05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290 -->
    <img src="<?= NIVEAU_0 ?>images/titres/setup_product.gif"/>
</div>
<br />
<center>
    <?php
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
    if (!isset($message))
        $message = null;
    ?>    
    <table class="tabPrincipal" width="700">
        <tr><td id="msg" colspan="2"></td></tr>
        <?php if ($readonly == "") { ?>
            <tr>
                <td align="left" valign="middle" style="padding-left:45px;">
                    <input type="button" class="bouton" value="Add a product" onclick="addProduct()" />
                </td>
                <?php
                // 18/04/2011 OJT : Pas de formulaire pour un produit blanc en stadalone
                if (count($productsArray) > 1 || !ProductModel::isBlankProduct(ProductModel::getIdMaster())) {
                    ?>
                    <td align="right" valign="middle" style="padding-top:15px;padding-right:45px;">
                        <form action="setup_products.php" method="post" name="formSetupProduct" id="formSetupProduct">
                            <span class="texteGris">Change topology master : </span>
                            <?php
                            $optionsList = '';
                            $disableForm = '';
                            foreach ($productsArray as $product) {
                                // 23/01/2009 GHX Seuls les produits activ�s peuvent �tre un master topo
                                // 21/10/2009 GHX Le produit Mixed KPI ne peut pas �tre le master Topo
                                // 21/03/2011 OJT Bloquage du formulaire si le master topo est down (bz2171)
                                // 18/04/2011 OJT Exclusion du produit blanc dans la liste d�roulante
                                if ($product['sdp_on_off'] != 0 && $product['sdp_id'] != $idProductMixedKPI && !ProductModel::isBlankProduct($product['sdp_id'])) {
                                    $selected = '';
                                    if (intval($product['sdp_master_topo']) === 1) {
                                        if ($disableForm != '') {
                                            $optionsList = "<option>{$product['sdp_label']}</option>";
                                            break;
                                        }
                                        $selected = 'selected=\'selected\'';
                                    }
                                    $optionsList .= "<option value='{$product['sdp_id']}' {$selected}>{$product['sdp_label']}</option>";
                                } else if ($product['sdp_on_off'] == 0) {
                                    $disableForm = 'disabled=\'disabled\'';
                                    if (intval($product['sdp_master_topo']) === 1) {
                                        $optionsList = "<option>{$product['sdp_label']}</option>";
                                        break;
                                    }
                                }
                            }
                            ?>
                            <select <?php echo $disableForm; ?> id="topoMasterSelect" name="topoMaster">
                                <?php echo $optionsList; ?>
                            </select>
                            <input <?php echo $disableForm; ?> type="submit" class="bouton" value="Save" />
                        </form>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        <?php /* 01/12/2009 GHX BZ 12979 */ ?>
        <?php if (count($productsArray) > 0) : ?>
            <tr>
                <td colspan="2" style="padding-left:45px;">
                    <span id="checkOpenOffice">
                        <input type="button" class="bouton" value="<?php echo __T('A_OPEN_OFFICE_BTN_LABEL_TEST'); ?>" onclick="checkOpenOffice()" onmouseover="popalt('<?php echo __T('A_OPEN_OFFICE_BTN_LABEL_TEST_TOOLTIP'); ?>');"/>
                    </span>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td colspan="2" id="productsContainer" align="center" style="padding-bottom:10px;">

                <?php
                // Affichage des produits
                foreach ($productsArray as $product) {
                    /* // 15:05 21/10/2009 GHX
                      // Le produit Mixed KPI ne doit pas �tre affich�
                      if ( $product['sdp_id'] == $idProductMixedKPI )
                      continue; */
                    displayProduct($product);
                }
                ?>
            </td>
        </tr>
    </table>
</center>

</body>
</html>