<?php
/*
  29/07/2009 GHX
  - Activation du autoscale pour le RI
  28/10/2009 - MPR
  - Correction du bug 12226 - Pas de RI d'afficher pour les familles 3ème axe
  18/11/2009 BBX :
  - on n'explose le network element que en overtime. BZ 12226
 *      13/01/2011 NSE bz 19924 : RI KO en ONE
 *      31/01/2011 NSE bz 20160 : No RI value for mapped Network Element
 */
?>
<?php
/**
 * 	Page affichant un sélecteur un dashboard
 *
 *
 * 	@author	BBX - 30/10/2008
 * 	@version	CB 4.1.0.0
 * 	@since	CB 4.1.0.0
 */
session_start();
include_once dirname(__FILE__) . "/../php/environnement_liens.php";

// Librairies et classes requises

require_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
require_once(MOD_SELECTEUR . "php/selecteur.class.php");
require_once(MOD_SELECTEUR . "php/SelecteurDashboard.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "graphe/thousand_separator.php"); // Ajout suite IN:3281

include_once REP_PHYSIQUE_NIVEAU_0 . 'dashboard_display/class/RiData.class.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'dashboard_display/class/GtmXml.class.php';

// Inclusion nécessaire à la classe 'chartFromXML'

include_once(MOD_CHARTFROMXML . "class/graph.php");
include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");

// Inclusion du header
// Dans le RI, on masque le header

echo '	<style type="text/css">
				#header {display:none} 
				#menu_container{display:none}
			</style>';

include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
?>

<div id="container" style="width:100%;text-align:center">

<?php
// Récupération des informations

$id_dashboard = isset($_GET['id_dash']) ? $_GET['id_dash'] : 0;
$id_gtm = isset($_GET['id_gtm']) ? $_GET['id_gtm'] : 0;
$id_menu_encours = isset($_GET['id_menu_encours']) ? $_GET['id_menu_encours'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'overtime';

$selecteur_values = $_SESSION['TA']['selecteur'];
?>

    <link rel="stylesheet" type="text/css" href="<?= NIVEAU_0 ?>css/graph_style.css"/>

<?php
// ** Chargement du selecteur (non utilisé par le RI mais permet de récupérer certaines valeurs)

$selecteur = new SelecteurDashboard($id_dashboard, $mode);

// ** Chargement du GTM du RI
// 1 - Définition des éléments du dashboard via la classe 'DashboardData'

$ri_data = new RiData();

$ri_data->setDebug(get_sys_debug('dashboard_display'));

// Définition de l'identifiant du GTM qui va permettre de construire le graphe du RI

$ri_data->setGTMId($id_gtm);

// Définition du produit maitre

$ri_data->setMasterTopo();

// Recuperation des informations du GTM à partir de son fichier XML

$ri_data->readXmlGTMSrc(REP_PHYSIQUE_NIVEAU_0 . "png_file/" . $_GET['xml_file']);

// Définition du nom du dashboard

$ri_data->setDashName($_GET['dash_name']);

// Définition des valeurs qui vont être utilisées via les setters de la classe
// 1.1 - Définition des valeurs de TA

$ta = $selecteur_values['ta'];
$ta_value = $_GET['ta_value'];
$period = $selecteur_values['period'];

$ri_data->setTA($ta, $ta_value, $period);

// Gestion de la bh

if (!(strpos($ta, "bh") === false)) {
    // Note : temporaire. On pourra avoir par la suite plusieurs bh

    $ri_data->setBH(array("bh"));
    $ri_data->setBHLabel(array('bh' => 'BH'));
}

// 1.2 - Définition des valeurs de NA
// Définition de la na d'axe1 à utiliser

$ri_data->setNaAxe1($_GET['na_axe1']);

// Définition des ne d'axe1
// maj 28/10/2009 - MPR : Correction du bug 12226 - Pas de RI d'afficher pour les familles 3ème axe
// 18/11/2009 BBX : on n'explose le network element que en overtime. BZ 12226
// 07/12/2009 BBX : on n'explose le network element que s'il contient le séparateur. BZ 12226
// 13/01/2011 NSE bz 19924 : RI KO en ONE
if (substr_count($_GET['ne'], get_sys_global_parameters("sep_axe3")) > 0) {   // 13/01/2011 NSE bz 19924 : on passe toute la liste des NE et pas uniquement le 1°
    // Rq : si des ne sont mappés, on passe ici le code mappé
    $ne = $_GET['ne'];
    $ne_array = explode(',', $_GET['ne']);
    foreach ($ne_array as $lene) {
        $lene = explode(get_sys_global_parameters("sep_axe3"), $lene);
        $ne_sans_axe3[] = $lene[0];
    }
    // on passe la liste des NE sans leur composante 3° axe
    $ne = implode(',', $ne_sans_axe3);
    // on passera la liste des NE avec leur composante 3° axe à part
    $ne_complet = $_GET['ne'];
} else {
    $ne = $_GET['ne'];
}
// BBX bz 17929
$dashModel = new DashboardModel($id_dashboard);
//$defaultNel = NeModel::getNeFromProducts($_GET['na_axe1'], $dashModel->getInvolvedProducts());
// 31/01/2011 NSE bz 20160 :
//   on recherche les éléments subissant un mapping (ceux qui ont leur colonne eor_id_codeq renseignée)
//   et non ceux qui sont des topology product network identifiers (dont l'identifiant est dans la colonne id_cod_eq d'autres éléments).
if ($equivalentNeAxe1 = NeModel::getMapped(array($_GET['na_axe1'])))
    $ri_data->setEquivalentNeAxe1($equivalentNeAxe1);
//$equivalentNeAxe1[1]['cell'] = array('4008_16031' => '107_22472');
$ri_data->setNeAxe1(explode(',', $ne));
// 13/01/2011 NSE bz 19924 : RI KO en ONE
// initialisation de la liste des NE avec leur composante 3°axe
if (isset($ne_complet))
    $ri_data->setNeAxe1AxeN(explode(',', $ne_complet));
// Note : à définir ou à supprimer
//$ri_data->setEquivalentNeAxe1(array(3 => array('rnc' => array('TAG10_306' => 'TOTO'))));
// Récupération des labels des na d'axe 1

$na_label = $selecteur->getNALevels(1);

// Définition de / des axe(s) N et de sa / ses valeurs

$axeN_path = $selecteur->getNaAndNeAxeNPath($_GET['na_axeN'], $_GET['ne_axeN']);

if (count($axeN_path['na_axeN']) > 0) {
    $ri_data->setNaAxeN($axeN_path['na_axeN']);

    // Définition de la ne d'axeN sélectionnées (si elle existe)

    if (count($axeN_path['ne_axeN']) > 0) {
        $ri_data->setNeAxeN($axeN_path['ne_axeN']);
    }

    // On complète le tableau de labels des na avec ceux de l'axe N

    $na_label = array_merge($na_label, $axeN_path['na_axeN_label']);
}

// Définition des labels axe 1 + axe N

$ri_data->setNaLabel($na_label);

// 1.3 - Définition des autres valeurs

$ri_data->setMode($mode);

// Définition des valeurs de RI

$ri_data->getValues();
$ri_values = $ri_data->DisplayResults();

//print_r($ri_values);
// ** Création du XML du GTM

$gtm_xml = new GtmXml('');

$gtm_name = $ri_values['name'];

$gtm_xml_sub = clone $gtm_xml;

$gtm_xml->setGTMTabTitle($ri_values['title']);
$gtm_xml->setGTMXAxis($ri_values['xaxis']);
$gtm_xml->setGTMData($ri_values['data']);

$gtm_xml->setGTMProperties($ri_data->getGTMProperties());

// 10:30 29/07/2009 GHX
// Activation du autoscale pour le RI
$gtm_xml->setGTMAutoScaleY(1);

// Note : la gestion de la multi bh n'est pas encore active

if (count($ri_values['bh_data']) > 0)
    $gtm_xml->setGTMBHData($ri_values['bh_data']);

$gtm_xml->Build();

if (get_sys_debug('dashboard_display')) {
    echo "<br>Nom du fichier xml : <a href=\"" . NIVEAU_0 . "png_file/{$gtm_name}.xml\" target=\"_blank\" >{$gtm_name}</a><br>";
}

$chart_url = REP_PHYSIQUE_NIVEAU_0 . 'png_file/' . $gtm_name . '.xml';

$gtm_xml->SaveXML($chart_url);

// ** Création de l'image du GTM
// On crée l'objet en chargeant le fichier de données XML

$my_gtm = new chartFromXML($chart_url);

// Modification des urls afin de stocker l'ensemble des fichiers (xml + png) dans le dossier "png_file" de l'application

$my_gtm->setBaseUrl(NIVEAU_0 . '/png_file/');

$my_gtm->setBaseDir(REP_PHYSIQUE_NIVEAU_0 . 'png_file/');
$my_gtm->setHTMLURL(NIVEAU_0);

// on charge les valeurs par défaut (depuis un autre fichier XML)

$my_gtm->loadDefaultXML(MOD_CHARTFROMXML . "class/chart_default.xml");

// test de création du cadre du GTM

echo $my_gtm->getHTMLFrame($gtm_name, $gtm_name . ".png", $ri_values['properties'], false, (count($ri_values['data']) > 0), $ri_values['msg']);

echo '<div class="spacer">&nbsp;</div>';
?>
</div>