<?php
/*
  28/04/2009 GHX
  - Prise en compte du mapping
  27/04/2009 - SPS
  - ajout d'un fichier js pour les fonctions d'ajout de ligne
  30/04/2009 - SPS
  - modification du style de la fenetre d'ajout de ligne
  04/05/2009 - SPS
  - ajout d'un message d'erreur pour l'ajout de ligne
  - modification du style de la fenetre
  05/05/2009 - SPS
  - ajout de l'evenement onclick sur le checkbox de suppression de ligne
  06/05/2009 - SPS
  - ajout de la gestion des couleurs pour l'ajout de ligne
  - messages, labels enregistres dans message_display.sql
  14/05/2009 GHX
  - Modification de l'appel au mapping
  25/05/2009 GHX
  - Regarde si les liens vers AA sont activés
  - Si link_to_aa dans sys_global_parameters = 1
  - Si la TA est "hour" ou "day"
  => dans ce cas on activé les liens vers AA dans la cas DashboardData (DashboardData::setEnabledLinkToAA())
  04/06/2009 GHX
  - Modif pour les index des dashboards
  05/04/2009 GHX
  - Pas d'affichage des index des dashboards en mode ZOOM PLUS
  23/06/2009 GHX
  - Pas de sauvegarde de la sélection des éléments réseaux
  26/06/2009 GHX
  - Modification pour savoir si on prend en compte la valeur $_GET['ne_axe1']

  01/07/2009 BBX : Classe indexContainer redéfinie pour IE6. BZ 10296

  06/07/2009 GHX
  - Correction du BZ10439  REC][T&A Cb 5.0][Dashboard]: le choix d'un NA ne déselectionne pas les éléments réseaux d'un NA inférieur
  10/07/2009 GHX
  - Correction du BZ 10400 [REC][T&A Cb 5.0][Dashboard] : pour un dashboard vide, affichage de warnings à propos de la connexion à la base, lors de la connexion à l'appli
  -> Dans le cas où le dashboard est mal paramétré on prend force le niveau d'agrégation
  21/07/2009 GHX
  - Correction d'un problème quand on fait reload
  - Correction du BZ 8965 [T&A 4.0][User Navigation][Network Element Preferences] : Stats par Cells affichées alors que le network level = BSC
  28/07/2009 GHX
  - Suppression de code
  - Modif concernant les préférences utilisateurs & la sélection des éléments réseaux
  29/07/2009 GHX
  - Modif du style des index des dashboards
  - Ajout du temps de traitement en mode débug
  - Correction du BZ 10439[REC][T&A Cb 5.0][Dashboard]: le choix d'un NA ne déselectionne pas les éléments réseaux d'un NA inférieur
  03/08/2009 GHX
  - Evolution activation du AUTO SCALE par défaut
  - Encodage des index des dashboards avec htmlentities
  28/08/2009 GHX
  - Ajout d'une condition
  12:03 14/10/2009 SCT
  - Ajout de l'appel à la méthode "setGTMDataNeColor" pour la gestion de la couleur sur les NE
  17:16 28/10/2009 MPR
  - Correction du bug 12319 - Les caractères : sont remplacés par des %3A (pas pris en compte après html_entitites
  30/10/2009 GHX
  - Affichage d'un message d'erreur si le dashboard est mal configuré
  17/11/2009 MPR
  - Correction BZ12805 - Mise en commentaire du mode debug
  19/11/2009 MPR
  - Extrapolation des données
  03/12/2009 GHX
  - Correction du BZ 8887 [REC][T&A Core 4.0][Performance d'affichage]: utilisation de la base impossible
  -> Si on dépasse un certain seuil on n'affiche plus les balises map des graphe (cf "max_nb_charts_onmouseover" dans sys_global_parameters)
  -> et au dessus de 50 graphes on ne les affiches plus (cf $MAX_DISPLAY_GRAPH)
  09/06/10 YNE/FJT : SINGLE KPI
  20/09/2010 NSE bz 17700 : suppression du lien dans l'index qui ne mène nul part.

  3/01/11 MMT DE Xpert 606
  ajout objets Xpert dash manager et affectation au gtmXml pour liens vers Xpert

  06/06/2011 MMT DE 3rd Axis factorisation du traitement 1er/3eme axe et utilisation des nouvelles fonctions
  27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
  22/05/2013 : Link to Nova Explorer
 *
 *
 */
?>
<?php
/**
 * 	Page affichant un sélecteur un dashboard
 *
 *  18/03/2009 - modif SPS : - ajout du nom du xml dans la variable de session
 * 						    - test de l'existence du png avant de l'enregistrer en session
 *
 *
 * 	@author	BBX - 30/10/2008
 * 	@version CB 4.1.0.0
 * 	@since	CB 4.1.0.0
 */
session_start();
include_once dirname(__FILE__) . "/../php/environnement_liens.php";

// Librairies et classes requises

require_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
require_once(MOD_SELECTEUR . "php/selecteur.class.php");
require_once(MOD_SELECTEUR . "php/SelecteurDashboard.class.php");

include_once REP_PHYSIQUE_NIVEAU_0 . 'dashboard_display/class/DashboardData.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'dashboard_display/class/GtmXml.class.php';
require_once(REP_PHYSIQUE_NIVEAU_0 . "graphe/thousand_separator.php"); // Ajout suite RQ:4893
// Inclusion nécessaire à la classe 'chartFromXML'
include_once(MOD_CHARTFROMXML . "class/graph.php");
include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");

//3/01/11 MMT DE Xpert 606 ajout source Xpert
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/Xpert/XpertManager.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/Xpert/XpertDashboardManager.class.php");

// Inclusion du header
// 12/05/2010 BBX
// On déplace le test sur l'affichage du header T&A qui n'a rien à faire ici (bug l'affichage car avant balise html)
// BZ 15451
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
?>

<div id="container" style="width:100%;text-align:center">

    <?php

    /**
     * 06/06/2011 MMT DE 3rd Axis factorisation de code pour axe 1 et 3
      On fait une vérification pour savoir si on prend en compte la valeur $_GET['ne_axe1']
      sachant qu'elle doit être prise en compte une seule fois si on refait display apres un reset de la sélection des éléments ou
      qu'on fait un reload
     * @param String $neVarName $_GET and $_SESSION['TA']  base variable name for NEs
     */
    function mergeGetAndSessionNeValues($neVarName) {

        if (isset($_GET[$neVarName])) {
            if (isset($_SESSION['TA'][$neVarName . '_already_used'])) {
                if ($_GET[$neVarName] == $_SESSION['TA'][$neVarName . '_already_used']) {
                    unset($_GET[$neVarName]);
                } else {
                    $_SESSION['TA'][$neVarName . '_already_used'] = $_GET[$neVarName];
                }
            } else {
                $_SESSION['TA'][$neVarName . '_already_used'] = $_GET[$neVarName];
            }
        } else {
            unset($_SESSION['TA'][$neVarName . '_already_used']);
        }
    }

    $timeStartTreatment = microtime(true);

// Récupération des informations dashboard

    $id_dashboard = isset($_GET['id_dash']) ? $_GET['id_dash'] : 0;
    $id_menu_encours = isset($_GET['id_menu_encours']) ? $_GET['id_menu_encours'] : 0;
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'overtime';

// ** Chargement du selecteur
    $selecteur = new SelecteurDashboard($id_dashboard, $mode);

// 20/12/2010 BBX
// Si ce dashbord fait référence à des produits désactivés
// On doit intercepter le problème et n'afficher qu'un seul message
// assez explicite
// BZ 18510
    $involvedProducts = $selecteur->getInvolvedProducts();
    foreach (ProductModel::getInactiveProducts() as $p) {
        foreach ($involvedProducts as $currentProduct) {
            if ($p['sdp_id'] == $currentProduct) {
                echo '<div class="errorMsg">' . __T('A_DATABASE_PRODUCT_AUTO_DISABLED', $p['sdp_label']) .
                '<br />' . __T('U_DASHBOARD_CANT_LOAD_DASHBOARD') . '</div>';
                return;
            }
        }
    }
// Fin BZ 18510
// Si l'appel est correct, continue
    if (!$selecteur->getError()) {
        // Chargement des valeurs stockées en session
        $selecteur->loadExternalValues($_SESSION['TA']['selecteur']);

        // 17:14 26/06/2009 GHX
        // 06/06/2011 MMT DE 3rd Axis factorisation de code pour axe 1 et 3
        mergeGetAndSessionNeValues('ne_axe1');
        mergeGetAndSessionNeValues('ne_axeN');

        // Chargement des valeurs transmises par GET
        // 15:27 21/07/2009 GHX
        // Si on fait reload on ne prend pas en compte les paramètres de l'URL
        if (!isset($_GET['reload'])) {
            // 11:58 28/07/2009 GHX
            // Normalement on a des valeurs dans l'url que quand on clique sur une valeur d'un dashboard
            if (isset($_GET['ne_axe1'])) {
                $_SESSION['TA']['selecteur']['ne_axe1'] = $_GET['ne_axe1'];
                $_SESSION['TA']['network_element_preferences'] = $_GET['ne_axe1'];
            }
            // 06/06/2011 MMT DE 3rd Axis gestion de $_GET et $SESSION du selecteur sur le model 1er axe
            if (isset($_GET['ne_axeN'])) {
                $_SESSION['TA']['selecteur']['ne_axeN'] = $_GET['ne_axeN'];
                // 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
                $_SESSION['TA']['ne_axeN_preferences'] = $_SESSION['TA']['selecteur']['ne_axeN'];
            }

            $selecteur->loadExternalValues($_GET);
        }

        // Chargement des valeurs transmises par POST
        // 22/11/2011 BBX
        // BZ 24764 : correction des notices PHP
        if (isset($_POST['selecteur']))
            $selecteur->getSelecteurFromArray($_POST['selecteur']);

        // pour une raison inconnue, parfois, la ta_value pour un format week est mauvaise (on a W24/2008 au lieu de W24-2008)
        $temp_date = $selecteur->getValue('date');
        if ($selecteur->getValue('ta_level') == 'week')
            $selecteur->setValue('date', str_replace('/', '-', $temp_date));
    }
// 16/02/2009 - Modif. benoit : si l'on spécifie via l'url que le selecteur doit être masqué, on ne le construit pas
    if (!(isset($_GET['no_selection']) && ($_GET['no_selection'] == "1")))
        $selecteur->build();

// 17/11/2009 - MPR : Correction BZ12805 - Mise en commentaire du mode debug
// $selecteur->debug();
// Sauvegarde de certaines valeurs du selecteur en session (ta et na)
    $selecteur->saveToSession();
// Récupération des valeurs du selecteur
    $selecteur_values = $selecteur->getValues();

// 14:12 10/07/2009 GHX
// Correction du BZ 10400 [REC][T&A Cb 5.0][Dashboard] : pour un dashboard vide, affichage de warnings à propos de la connexion à la base, lors de la connexion à l'appli
// Dans le cas où le dashboard est mal paramétré on prend force le niveau d'agrégation
    if (empty($selecteur_values['na_level'])) {
        $selecteur_values['na_level'] = key($selecteur->getNALevels(1));
    }

// 15:00 28/07/2009 GHX
    if (empty($selecteur_values['nel_selecteur']) && !empty($_SESSION['TA']['network_element_preferences'])) {
        $dashModel = new DashboardModel($id_dashboard);
        $na2na = $dashModel->getNa2Na();

        // Récupère la liste de tous les éléments sélectionnées
        $currentSelectionTmp = explode('|s|', $_SESSION['TA']['network_element_preferences']);
        $currentSelection = array();

        foreach ($currentSelectionTmp as $select) {
            $_ = explode('||', $select);
            // Si le niveau d'agrégation fait parti de la liste des éléments réseaux du dashboard
            if (array_key_exists($_[0], $na2na)) {
                if (in_array($_[0], $na2na[$selecteur_values['na_level']])) {
                    // Garde uniquement les éléments dont le niveau d'agrégation est visible dans la sélection des éléments réseaux
                    $currentSelection[] = $select;
                }
            }
        }

        $selecteur_values['nel_selecteur'] = implode('|s|', $currentSelection);

        unset($na2na);
        unset($select);
        unset($dashModel);
        unset($currentSelection);
        unset($currentSelectionTmp);
    }

// ** Chargement du menu contextuel
    include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

// 16:09 30/10/2009 GHX
// Si pas de NA axe1 on affiche une erreur ici au lieu de l'afficher au beau milieu de l'IHM
    if ($selecteur_values['na_level'] == null) {
        echo "<p class='errorMsg'>" . __T('U_DASHBOARD_CANT_LOAD_DASHBOARD') . "</p>";
        // exit;
    }
    ?>

    <link rel="stylesheet" type="text/css" href="<?= NIVEAU_0 ?>css/graph_style.css"/>

    <?php
    // ** Chargement des GTMs du dashboard
    // 1 - Définition des éléments du dashboard via la classe 'DashboardData'

    $dash_data = new DashboardData();

    $dash_data->setDebug(get_sys_debug('dashboard_display'));

    // Définition du menu du dashboard

    $dash_data->setIdMenu($id_menu_encours);

    // Définition du produit maitre

    $dash_data->setMasterTopo();

    // Définition des valeurs qui vont être utilisées via les setters de la classe
    // 1.1 - Définition des valeurs de TA

    $ta = $selecteur_values['ta_level'];

    if ($ta == "hour") {
        $ta_value = $selecteur_values['date'] . " " . $selecteur_values['hour'];
    } else {
        $ta_value = $selecteur_values['date'];
    }

    $ta_value = getTaValueToDisplayReverse($ta, $ta_value, "/");

    $dash_data->setTA($ta, $ta_value, $selecteur_values['period']);

    $ta_value_reverse = getTaValueToDisplay($ta, $ta_value, "/");

    // Définition de la ta minimale

    $ta_list = $selecteur->getTaArray();
    $ta_list = array_keys($ta_list[0]);

    $dash_data->setTAMin($ta_list[0]);

    // Gestion de la bh

    if (!(strpos($ta, "bh") === false)) {
        // Note : temporaire. On pourra avoir par la suite plusieurs bh

        $dash_data->setBH(array("bh"));
        $dash_data->setBHLabel(array('bh' => 'BH'));
    }

    // 1.2 - Définition des valeurs de NA
    // Définition des chemins des na axe1
    // 18:40 29/07/2009 GHX
    // Correction du BZ 10439 [REC][T&A Cb 5.0][Dashboard]: le choix d'un NA ne déselectionne pas les éléments réseaux d'un NA inférieur
    // Voir fiche de maintenant pour plus d'explication sur la correction du bug
    // Récupère les chemins d'aggrégation du dashboards
    //06/06/2011 MMT DE 3rd Axis factorisation utilisation des nouvelles fonctions dans SelecteurDashboard
    $na_axe1_paths = $selecteur->getSourcePathToNa($selecteur_values['na_level']);

    $dash_data->setPaths($na_axe1_paths);

    // Définition de la na axe1 affiché en abcisse (par défaut, la meme que celle du sélecteur sauf si une seule valeur est sélectionnée -> cf. traitement des ne axe1 ci-dessous)
    $na_axe1_abcisse = $selecteur_values['na_level'];


    // Définition de la na d'axe1 à utiliser
    $dash_data->setNaAxe1($selecteur_values['na_level']);

    // Définition des ne d'axe1
    if (isset($selecteur_values['nel_selecteur']) && ($selecteur_values['nel_selecteur'] != "")) {
        // Si des ne d'axe1 existent, on utilise la chaine de sélection afin de déterminer les valeurs sélectionnées
        //06/06/2011 MMT DE 3rd Axis factorisation utilisation des nouvelles fonctions dans SelecteurDashboard
        $ne1 = $selecteur->getNeSelectionArrayFromStringValue($selecteur_values['nel_selecteur'], $na_axe1_abcisse);

        // Si des ne axe1 sont définies, on définit ces valeurs comme celles à utiliser lors de la construction des GTMs du dashboard
        if (count($ne1) > 0) {
            $dash_data->setNeAxe1($ne1);
        }
    }

    // Définition de la na axe1 minimale
    $na_min = array_values($na_axe1_paths);
    $dash_data->setNaMinAxe1($na_min[count($na_min) - 1]);

    // Note : à définir ou à supprimer
    // 28/04/2009 GHX
    // Prise en compte du mapping
    // 22/11/2011 BBX
    // BZ 24764 : correction des notices php
    if (!empty($ne1)) {
        if ($equivalentNeAxe1 = NeModel::getMapping($ne1)) {
            // __debug($equivalentNeAxe1,'equivalentNeAxe1');
            $dash_data->setEquivalentNeAxe1($equivalentNeAxe1);
        }
    }
    // 30/11/2010 BBX
    // Prise en compte du mapping même sans sélection de NE
    // BZ 17929
    else {
        $dashModel = new DashboardModel($id_dashboard);
        $defaultNel = NeModel::getNeFromProducts($na_axe1_abcisse, $dashModel->getInvolvedProducts());
        if ($equivalentNeAxe1 = NeModel::getMapping($defaultNel))
            $dash_data->setEquivalentNeAxe1($equivalentNeAxe1);
    }
    // Fin BZ 17929

    $dash_data->setNaAbcisseAxe1($na_axe1_abcisse);

    // Récupération des labels des na d'axe 1

    $na_label = $selecteur->getNALevels(1);

    // Définition de / des axe(s) N et de sa / ses valeurs
    // 22/11/2011 BBX
    // BZ 24764 : correction des notices php
    $axeN_path = $selecteur->getNaAndNeAxeNPath($selecteur_values['axe3'], (isset($selecteur_values['axe3_2']) ? $selecteur_values['axe3_2'] : null));

    if (count($axeN_path['na_axeN']) > 0) {
        $dash_data->setNaAxeN($axeN_path['na_axeN']);

        // cas graph multi famille, si Selecteur 3eme axe absent mais dash contient graphs sur famille 3eme axe
        //06/06/2011 MMT DE 3rd Axis factorisation utilisation des nouvelles fonctions dans SelecteurDashboard
        // et affectation au DashboardData
        if (!empty($selecteur_values['axe3'])) {
            $na_axe3_paths = $selecteur->getSourcePathToNa($selecteur_values['axe3'], 3);
            $dash_data->setNaAbcisseAxeN($selecteur_values['axe3']);
            $dash_data->setPathsAxeN($na_axe3_paths);
        }

        // Définition de la ne d'axeN sélectionnées (si elle existe)

        if (count($axeN_path['ne_axeN']) > 0) {
            $dash_data->setNeAxeN($axeN_path['ne_axeN']);
        }

        // On complète le tableau de labels des na avec ceux de l'axe N

        $na_label = array_merge($na_label, $axeN_path['na_axeN_label']);
    }

    // Définition des labels axe 1 + axe N

    $dash_data->setNaLabel($na_label);

    // 1.3 - Définition des autres valeurs

    $dash_data->setMode($mode);

    // Gestion de la limite Topover en mode Overtime (DE Selecteur/Historique, 02/2011)
    $maxTopoverOT = intval(get_sys_global_parameters('max_topover_ot', 10));
    $maxTopoverMsgDisplay = 'display:none';
    if (( $mode == 'overtime' ) && ( intval($selecteur_values['top']) > $maxTopoverOT )) {
        $selecteur_values['top'] = $maxTopoverOT;
        $maxTopoverMsgDisplay = '';
    }

    $dash_data->setTop($selecteur_values['top']);

    // Définition du mode zoom plus

    if (isset($_GET['zoom_plus']) && ($_GET['zoom_plus'] == 1) && isset($_GET['id_gtm_zoomplus'])) {
        $dash_data->setZoomPlus(true, $_GET['id_gtm_zoomplus'], $selecteur_values['top']);
    }

    if (isset($selecteur_values['sort_by']) && ($selecteur_values['sort_by'] != "none") && (str_replace('@', '', $selecteur_values['sort_by']) != "")) {
        $dash_data->setSortByFromSelector($selecteur_values['sort_by'], $selecteur_values['order']);
    }

    if (isset($selecteur_values['filter_id']) && ($selecteur_values['filter_id'] != "")) {
        $dash_data->setFilterFromSelector($selecteur_values['filter_id'], $selecteur_values['filter_operande'], $selecteur_values['filter_value']);
    }

    // 16/02/2009 - Modif. benoit : si l'on spécifie via l'url que la navigation est désactivé, on définit cette information dans la classe d'affichage des dashboards

    $dash_data->setGTMNavigation(!(isset($_GET['no_navigation']) && ($_GET['no_navigation'] == "1")));

    // 12:01 25/05/2009 GHX
    // Regarde si on doit activé les liens vers AA
    $linkToAA = get_sys_global_parameters('link_to_AA');
    if ($linkToAA == 1) {
        // On ne peut avoir les liens vers AA uniquement sur les TA
        //	- hour
        //	- day
        if ($ta == 'hour' || $ta == 'day') {
            $dash_data->setEnabledLinkToAA();
        }
    }
    //CB 5.3.1 : Link to Nova Explorer
    // Regarde si on doit activé les liens vers NE
    if (get_sys_global_parameters('url_NE') != '') {
        // On ne peut avoir les liens vers NE uniquement sur les TA
        //	- hour
        //	- day
        if ($ta == 'hour' || $ta == 'day') {
            $dash_data->setEnabledLinkToNE();
        }
    }

    // Définition des éléments
    $dash_data->getElements($id_dashboard);


    // Création des GTMs (XML -> IMG -> HTML)
    // Avant de lancer la création, on initialise le tableau d'export des GTMs
    $dash_export = array();

    // 11:06 04/06/2009 GHX
    // Initialisation
    $indexInformations = '';
    $mode = $dash_data->getGTMMode();

    // >>>>>>>>>>
    // 13:33 03/12/2009 GHX BZ 8887
    $maxNbChartsInteractivity = get_sys_global_parameters("max_nb_charts_onmouseover", 20);
    $separator = get_sys_global_parameters("thousand_separator", "NONE");
    $nbChartsInteractivity = 0;
    $msgChartsInteractivityDisplay = false; // Permet de savoir si on a déjà affiché le message pour dire que l'on n'a plus de balise map sur les graphes à partir du message
    $MAX_DISPLAY_GRAPH = 50; // Nombre maximum de graphe que l'on affiché dans un dashboard
    $graphDisplayExceeced = false; // Nombre de graphe affiché par graphe définit dans le dashboard
    $nbTotalGraph = 0; // Nombre total de graphe qui doit etre affiché dans le dashboard
    echo '<p id="errorMaxDisplayGraphExceeded" class="msgOrderByAlpha" style="display:none">' . __T('U_E_DASHBOARD_STOP_DISPLAY_GRAPH', '<span id="totalGraphs"></span>&nbsp', $MAX_DISPLAY_GRAPH) . '</p>';
    echo '<p id="infoMaxTopOverExceeded" class="texteGrisGrand" style="' . $maxTopoverMsgDisplay . '">' . __T('U_DASH_MAX_TOPOVER_OT', get_sys_global_parameters('max_topover_ot', 10)) . '</p>';
    echo '<input type="hidden" name="graph_separator" id="graph_separator" value="' . $separator . '">';
    // <<<<<<<<<<
    // On boucle sur les résultats de la définition des éléments
    // 3/01/11 MMT DE Xpert 606
    // creation Objets Xperts

    $XpertMger = XpertManager::getInstance();
    $XpertDashMger = new XpertDashboardManager($XpertMger, $selecteur_values, $mode);


    foreach (($dash_data->DisplayResults()) as $gtm_id => $gtm_values) {

        $gtm_xml = new GtmXml($gtm_id);


        // 19/11/2009 MPR - Extrapolation des données - On récupère la config du sélecteur (nécessaire pour extrapoler les données à partir d'une ta en base)
        // $gtm_xml->setGTMSortBy($selecteur_values['sort_by']);
        $gtm_xml->setGTMNa($selecteur_values['na_level']);
        if (isset($selecteur_values['axe3'])) {
            $gtm_xml->setGTMNaAxe3($selecteur_values['axe3']);
        }
        $gtm_xml->setGTMTa($selecteur_values['ta_level']);
        $gtm_xml->setGTMTaValue($selecteur_values['date']);
        // 3/01/11 MMT DE Xpert 606
        // ajout Xpert dash mger au gtmXml
        $gtm_xml->setXpertDashboardManager($XpertDashMger);

        $gtm_xml->setGTMMode($mode);

        // __debug($gtm_values,"GTM VALUES $gtm_id");
        // 14:53 04/06/2009 GHX
        // Index des dashboards
        $indexInformations .= '<div id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '" class="indexGTM">';
        $indexInformations .= '<div style="float:left"><input type="button" id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '_btn" value="" class="IndexGTMButtonUp" onclick="slideIndexContent(\'' . $gtm_values[0]['properties']['index']['gtm_id'] . '_elts\', \'' . $gtm_values[0]['properties']['index']['gtm_id'] . '_btn\')"/></div>';

        // 14:46 03/12/2009 GHX
        // BZ 8887
        $nbTotalGraph += count($gtm_values); // Compte le nombre de graphe dans le dashboard
        // Si on a dépassé le nombre de graphe autorisé, on met l'index du grpahe en rouge et on dit qu'il n'y a pas d'élément
        if ($graphDisplayExceeced) {
            // 20/09/2010 NSE bz 17700 : suppression du lien qui ne mène nul part.
            $indexInformations .= '<div><span id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '_href" class="lien_graph" style="FONT-SIZE: 7pt;color:red">' . htmlentities($gtm_values[0]['properties']['index']['gtm_name']) . '</span></div>';
            $indexInformations .= '<div id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '_elts" class="IndexElementsList" style="display:none;overflow:auto">';
            $indexInformations .= '<div id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '_list">no element</div></div></div>';
            continue;
        }

        // 14:12 03/08/2009 GHX
        // Ajout de htmlentities
        $indexInformations .= '<div><a id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '_href" href="#' . $gtm_values[0]['properties']['index']['gtm_id'] . '" class="lien_graph" style="FONT-SIZE: 7pt">' . htmlentities($gtm_values[0]['properties']['index']['gtm_name']) . '</a></div>';
        $indexInformations .= '<div id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '_elts" class="IndexElementsList" style="display:none;overflow:auto">';
        $indexInformations .= '<ul id="' . $gtm_values[0]['properties']['index']['gtm_id'] . '_list" >';

        // 14:26 03/12/2009 GHX
        // BZ 8887
        $nbGraphByGraphDefined = 0;

        for ($i = 0; $i < count($gtm_values); $i++) {

            // >>>>>>>>>>
            // 14:26 03/12/2009 GHX
            // BZ 8887
            // Si on dépasse le nombre maximum de graphe autorisé dans l'affichage on arrete
            if ($nbChartsInteractivity > $MAX_DISPLAY_GRAPH) {
                $graphDisplayExceeced = true;
                break;
            }
            // <<<<<<<<<
            // Nom des fichiers du GTM (xml et png)
            $gtm_name = $gtm_values[$i]['name'];

            // 04/06/2009 GHX
            // 29/07/2009 GHX Ajout du style word-wrap:break-word
            // 03/08/2009 GHX Ajout de htmlentities
            // 28/10/2009 MPR Correction du bug 12319 : Les caractères : sont remplacés par des %3A (pas pris en compte après html_entitites)
            // 26/06/2010 OJT Correction du bug 15206 : Les caractères + sont remplacés par des %2B (pas pris en compte après html_entitites)
            // 06/09/2010 MGD Correction du bug 17699 : utilisation de urldecode pour gérer les caractère [ et ] (et les autres...)
            $title = htmlentities(str_replace('%2B', '+', str_replace('%3A', ':', $gtm_values[$i]['properties']['index']['ne_label'])));
            $title = urldecode($title);
            $indexInformations .= '<li id="' . $gtm_values[$i]['properties']['index']['ne'] . '" style="word-wrap:break-word;"><a id="' . $gtm_values[$i]['properties']['index']['ne'] . '_href" href="#' . $gtm_values[$i]['properties']['index']['gtm_id'] . '_' . $gtm_values[$i]['properties']['index']['ne'] . '_title" class="lien_graph" style="font-size:7pt;' . ($maxNbChartsInteractivity > $nbChartsInteractivity ? '' : 'color:orange') . '">' . $title . '</a></li>';

            // Nom du gtm
            $index[$gtm_id][$i]['name'] = $gtm_name;

            if (get_sys_debug('dashboard_display')) {
                echo "<br>Nom du fichier xml : <a href=\"" . NIVEAU_0 . "png_file/{$gtm_name}.xml\" target=\"_blank\" >{$gtm_name}</a><br>";
            }

            // Création du XML

            $timeStartCreateFileXml = microtime(true);

            $gtm_xml_sub = clone $gtm_xml;

            //on définit le type du GTM (graph, pie ou singleKPI)
            $gtm_xml_sub->setGTMType($dash_data->getGTMType($gtm_id));
            // définition des éléments réseau avec/sans 3ème axe
            $_ne = $gtm_values[$i]['ne'];

            //si on est en singleKPI, on a besoin des 3 pires NE, sinon un seul.
            if ($dash_data->getGTMType($gtm_id) == "singleKPI") {
                $gtm_xml_sub->setGTMNeTab($_ne);
            } else {
                $gtm_xml_sub->setGTMNe($_ne[$i]);
            }
            //mettre un parametre dans le setGTMProperties pour singleKPi
            //pour avoir la liste des ne les pires
            $gtm_xml_sub->setGTMProperties();

            $gtm_xml_sub->setGTMTabTitle($gtm_values[$i]['title']);
            $gtm_xml_sub->setGTMXAxis($gtm_values[$i]['xaxis']);
            $gtm_xml_sub->setGTMData($gtm_values[$i]['data']);
            // 14:54 08/10/2009 SCT : ajout de la couleur du les NE dans les PIES splittés sur un axe
            // 22/11/2011 BBX
            // BZ 24764 : correction des notices php
            $gtm_xml_sub->setGTMDataNeColor(isset($gtm_values[$i]['ne_color']) ? $gtm_values[$i]['ne_color'] : null);

            // 22/11/2011 BBX
            // BZ 24764 : correction des notices php
            if (!empty($gtm_values[$i]['bh_data'])) {
                $gtm_xml_sub->setGTMBHData($gtm_values[$i]['bh_data']);
            }
            // 16:44 26/05/2009 GHX
            // Lien vers AA                        
            if (count($gtm_values[$i]['link_aa']) > 0) {
                $gtm_xml_sub->setGTMDataLinkAA($gtm_values[$i]['link_aa']);
            }

            //CB 5.3.1 : Link to Nova Explorer
            // Lien vers NE
            if (count($gtm_values[$i]['link_ne']) > 0) {
                $gtm_xml_sub->setGTMDataLinkNE($gtm_values[$i]['link_ne']);
            }

            $gtm_xml_sub->setGTMDataLink($gtm_values[$i]['link']);
            $gtm_xml_sub->setGTMSplitBy($dash_data->getSplitBy($gtm_id));

            // 11:21 03/08/2009 GHX
            // Evolution activation du AUTO SCALE par défaut
            $gtm_xml_sub->setGTMAutoScaleY(1);
            $gtm_xml_sub->setGTMAutoScaleY2(1);

            $gtm_xml_sub->Build();

            $chart_url = REP_PHYSIQUE_NIVEAU_0 . 'png_file/' . $gtm_name . '.xml';

            $gtm_xml_sub->SaveXML($chart_url);

            $timeEndCreateFileXml = microtime(true);

            // Création de l'image du GTM
            // On crée l'objet en chargeant le fichier de données XML
            $my_gtm = new chartFromXML($chart_url);

            // Modification des urls afin de stocker l'ensemble des fichiers (xml + png) dans le dossier "png_file" de l'application

            $my_gtm->setBaseUrl(NIVEAU_0 . '/png_file/');

            $my_gtm->setBaseDir(REP_PHYSIQUE_NIVEAU_0 . 'png_file/');
            $my_gtm->setHTMLURL(NIVEAU_0);

            // on charge les valeurs par défaut (depuis un autre fichier XML)

            $my_gtm->loadDefaultXML(MOD_CHARTFROMXML . "class/chart_default.xml");

            // >>>>>>>>>>
            // 13:33 03/12/2009 GHX BZ 8887
            if ($maxNbChartsInteractivity <= $nbChartsInteractivity) {
                if (!$msgChartsInteractivityDisplay)
                    echo '<p class="msgOrderByAlpha" style="text-align:center;">' . __T('U_E_DASHBOARD_NO_INTERACTIVITY') . '</p>';
                $my_gtm->setInteractivity(false);
                $msgChartsInteractivityDisplay = true;
            }
            $nbChartsInteractivity++;
            // <<<<<<<<<<
            // test de création du cadre du GTM
            // 22/11/2011 BBX
            // BZ 24764 : correction des notices php
            echo $my_gtm->getHTMLFrame($gtm_name, $gtm_name . ".png", $gtm_values[$i]['properties'], true, (count($gtm_values[$i]['data']) > 0), isset($gtm_values[$i]['msg']) ? $gtm_values[$i]['msg'] : null);

            echo '<div class="spacer">&nbsp;</div>';

            if (get_sys_debug('dashboard_display')) {
                echo "<b>Temps de generation du fichier XML : " . round($timeEndCreateFileXml - $timeStartCreateFileXml, 4) . " seconds / de l'image : " . round(microtime(true) - $timeEndCreateFileXml, 4) . "</b><br />";
            }

            // Sauvegarde du GTM dans le tableau servant aux exports Word et PDF

            /*
             * 18/03/2009 - modif SPS : - ajout du nom du xml dans la variable de session
             * 						   - test de l'existence du png avant de l'enregistrer en session
             */

            // 31/07/2009 BBX: Récupération du dernier commentaire sur le GTM. BZ 10633
            $GTMModel = new GTMModel($gtm_id);
            $lastComment = $GTMModel->getLastComment();

            // 01/04/2010 BBX. BZ 12231
            // Il faut désormais créer une entrée dans $dash_export pour tous les graphes.
            // Cependant, lorsque le grahe n'a pas de données, on initialiste "image" et "xml" à vide
            // et on passe le message affiché à la place du graphe dans "nodata"
            if (file_exists(REP_PHYSIQUE_NIVEAU_0 . 'png_file/' . $gtm_name . ".png")) {
                $dash_export[] = array('titre' => $gtm_values[$i]['properties']['title']['gtm'],
                    'image' => REP_PHYSIQUE_NIVEAU_0 . 'png_file/' . $gtm_name . ".png",
                    'xml' => REP_PHYSIQUE_NIVEAU_0 . 'png_file/' . $gtm_name . ".xml",
                    'lastComment' => $lastComment);
            } else {
                $dash_export[] = array('titre' => $gtm_values[$i]['properties']['title']['gtm'],
                    'image' => '',
                    'xml' => '',
                    'nodata' => $gtm_values[$i]['msg'],
                    'lastComment' => $lastComment);
            }
            // FIN BZ 12231
        }
        $indexInformations .= '</ul></div></div>';
    }

    //on vide la variable de session
    unset($_SESSION['dashboard_export_buffer']);

    // Sauvegarde des GTMs crées dans le tableau de sessions des exports Word et PDF
    if (count($dash_export) > 0) {
        // Modèle Dashboard
        $dashModel = new DashboardModel($id_dashboard);
        // Mise en session des valeurs nécessaires
        $_SESSION['dashboard_export_buffer']['titre'] = $dash_data->getDashName();
        $_SESSION['dashboard_export_buffer']['comment'] = $dashModel->getLastComment();
        $_SESSION['dashboard_export_buffer']['data'] = $dash_export;
        unset($dashModel);
    }
    ?>
</div>
<?php
// 05/03/2009 - Modif. benoit : definition de l'index des dashboards
?>
<style type="text/css">
    .IndexGTMButtonUp {
        border:none;
        color:#fff;
        background: transparent url('<?= NIVEAU_0 ?>/images/icones/bouton_selecteur_plus.gif') no-repeat top left;
        width: 10px;
        height: 10px;
        cursor: pointer;
    }

    .IndexGTMButtonDown {
        border:none;
        color:#fff;
        background: transparent url('<?= NIVEAU_0 ?>/images/icones/bouton_selecteur_moins.gif') no-repeat top left;
        width: 10px;
        height: 10px;
        cursor: pointer;
    }
</style>

<!--[if IE 6]>
<style type="text/css">
/* 01/07/2009 BBX : Classe indexContainer redéfinie pour IE6. BZ 10296 */
.indexContainer {
        position:absolute;
        left:0px;
        /* 11/03/2009 - Modif SPS : prise en compte de la taille de l'élément selecteur_toggle pour le top de l'élément indexContainer */
        top:expression(documentElement.scrollTop + 230);
}
</style>
<![endif]-->
<script src="<?= NIVEAU_0 ?>js/dashboard_index.js" type="text/javascript"></script>
<?php
/* 	27/04/2009 - SPS
  - ajout d'un fichier js pour les fonctions d'ajout de ligne
 */
?>

<!-- inclusion du fichier js contenant les fonctions pour l'ajout de ligne -->
<script type='text/javascript' src='<?= NIVEAU_0 ?>/js/draw_line.js'></script>

<?php
/* 04/05/2009 - SPS
 * 	- ajout de messages d'erreur pour l'ajout de ligne
 *  - modification du style de la fenetre
 * */
?>
<!-- messages d'erreur pour l'ajout d'une ligne-->
<script type='text/javascript'>
<?php if ($graphDisplayExceeced) : ?>
        $('totalGraphs').innerHTML = '<?php echo $nbTotalGraph; ?>';
        $('errorMaxDisplayGraphExceeded').style.display = 'block';
<?php endif; ?>
    var _msgAlertLineValue = '<?php echo __T('G_JS_DRAWLINE_VALUE_NOT_VALID'); ?>';
    var _msgAlertLegend = '<?php echo __T('G_JS_DRAWLINE_NO_LEGEND'); ?>';
    var _msgUpdatingGTM = '<?php echo __T('G_JS_DRAWLINE_UPDATING_GTM'); ?>';
</script>
<?php
/* 06/05/2009 - SPS
 * 	- ajout de la gestion des couleurs pour l'ajout de ligne
 *  - messages, labels enregistres dans message_display.sql
 * */
?>

<!-- feuille de style utilisee par le colorPicker -->
<link rel="stylesheet" type="text/css" href="<?= NIVEAU_0 ?>builder/common.css"/>

<!-- inclusion de la librairie pour l'affichage du colorPicker -->
<script type="text/javascript" src="<?= NIVEAU_0 ?>js/color_picker.js"></script>

<div id="color_picker_container" style="position:absolute;display:none;z-index:5000;"></div>

<!-- ajout d'une ligne sur un/tous les graphs -->
<div id="add_line" style="display:none;">
    <table style="margin-left:15px;">
        <tr>
            <td><label><?php echo __T('G_DRAWLINE_ALIGN_ON'); ?></label></td>
            <td>
                <input type="radio" name="align"  id="draw_left" value="left" checked="checked" onclick="align_line('left')"><label for="draw_left">Left</label>
                <input type="radio" name="align" id="draw_right" value="right" onclick="align_line('right')"><label id="lbl_draw_right" for="draw_right">Right</label>
                <input type="hidden" name="yaxis" id="align" value="left">
            </td>
        </tr>
        <tr>
            <td><label><?php echo __T('G_DRAWLINE_VALUE'); ?></label></td>
            <td><input type="text" class="" name="line_value" id="line_value"/></td>
        </tr>
        <tr>
            <td><label><?php echo __T('G_DRAWLINE_LEGEND'); ?></label></td>
            <td><input type="text" class="" name="legend" id="legend"/></td>
        </tr>
        <tr>
            <td><label><?php echo __T('G_DRAWLINE_COLOR'); ?></label></td>
            <td><input type="button" class="colorPickerBtn" name="fill_color_btn" id="fill_color_btn" style="background-color:#FF0000;cursor:pointer;"/>
                <input type='hidden' name='fill_color' id='fill_color' value='#FF0000'/>
            </td>
        </tr>
        <tr>
            <td><label for="update_all"><?php echo __T('G_DRAWLINE_UPDATE_ALL'); ?></label>&nbsp;<img src="<?= NIVEAU_0 ?>/images/icones/cercle_info.gif" alt="info" onmouseover="popalt('Add a line on all GTM');"/></td>
            <td><input type="checkbox" name="update_all" id="update_all"/></td>
        </tr>
        <tr>
            <td><label for="remove_line"><?php echo __T('G_DRAWLINE_REMOVE_LINES') ?></label>&nbsp;<img src="<?= NIVEAU_0 ?>/images/icones/cercle_info.gif" alt="info" onmouseover="popalt('Remove lines on all GTM');"/></td>
            <?php /* 05/05/2009 - SPS : ajout de l'evenement onclick sur le checkbox de suppression de ligne */ ?>
            <td><input type="checkbox" name="remove_line" id="remove_line" onclick="remove_line()"/></td>
        </tr>
    </table>
    <div align="right">
        <input type="hidden" id="gtm_name" name="gtm_name"/>
        <input type="submit" name="submit" value="<?php echo __T('G_DRAWLINE_BTN_UPDATE'); ?>" onclick="sendDrawLine()"/>
        <input type="reset" name="reset" value="<?php echo __T('G_DRAWLINE_BTN_CLOSE'); ?>" onclick="closeWinDrawLine()"/>
    </div>
</div>

<?php
// On n'affiche pas l'index des dashboards en mode zoom plus (en plus ca fait un bug JS)
if (!(isset($_GET['zoom_plus']) && ($_GET['zoom_plus'] == 1) && isset($_GET['id_gtm_zoomplus']))) {
    ?>
    <!-- 11:45 04/06/2009 GHX : index des dashboards -->
    <!-- 01/07/2009 BBX : réindentation -->
    <div id="index_container" class="indexContainer">
        <div id="index_dashboard_content" class="indexContent" style="display: none" >
            <?php echo $indexInformations; ?>
        </div>
        <div id="index_dashboard_bouton" class="indexButton">
            <input type="image" id="onglet_graph" onmouseover="popalt('Hide/Show Dashboard Index');" onclick="slideIndexBox()" onmouseout="kill()" src="<?php echo NIVEAU_0; ?>/images/boutons/onglet_index_on.gif"/>
        </div>
    </div>
    <?php
}

if (get_sys_debug('dashboard_display')) {
    echo "<br/><b>Temps de generation de la page : " . round(microtime(true) - $timeStartTreatment, 4) . " seconds</b>";
}
?>