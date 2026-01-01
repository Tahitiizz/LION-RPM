<?php

/*
  13/03/2009 SPS
  - changement du lien pour l'export Excel
  17/04/2009 SPS
  - ajout du bouton pour ajouter une ligne sur les graphes
  28/04/2009 GHX
  - Prise en compte du mapping dans la recherche des �l�ments r�seaux, cf. function getNeFromSortBy()
  - Cr�ation "ReconvertEquivalentNeAxe1()" d'une fonction fait l'inserve de la fonction ConvertEquivalentNeAxe1()
  - Prise en compte du mapping dans les donn�es du graphes
  29/04/2009 GHX
  - Si un ne n'a pas de label on prend son id
  - Correction du mapping avec les combinaisons (axe1)
  - Affiche une seule fois le message "Over Time Sort : Indicator/Counter NULL or No Data Found -> Dashboard Element(s) Order by $1 DESC."
  30/04/2009 SPS
  - modification du lien pour l'ouverture de la fenetre d'ajout de ligne
  04/05/2009 SPS
  - test sur le type du graph (pie*) pour savoir si on affiche l'icone pour ajouter une ligne
  05/05/2009 GHX
  - Ajout de l'alias de la table sur le on_off sinon probl�me de colonne ambigue sur la cr�ation de la requ�te qui r�cup�re les donn�es
  - Ajout d'une condition dans le cas ou la p�riode vaut 1 car pas la peine de faire une recherche sur une plage de donn�es mais juste sur le jour voulu. cf. function setTACondition()
  06/05/2009 GHX
  - Correction d'une condition pour savoir si on r�cup�re les labels des �l�ments d'axe N
  - Modification du code pour r�cup�rer les donn�es cf. fonction getGTMElements()
  - Modification de la fonction setAxeNColumns()
  07/05/2009 GHX
  - Ajout d'un �chappement des simples cotes sinon plantage JS pour le onmouseclick en OverTime dans le cas d'un PIE
  11/05/2009 GHX
  - Message d'erreur no data found pour les PIE
  - Message d'erreur pour les PIE si pas de valeur sur le split by ou valeur nulle
  - Correction requete SQL si valeur particuli�re pour le troisieme axe
  12/05/2009 GHX
  - Modification de plusieurs condition dans le cas des familes avec axe N et un axe N en particulier
  - (suite � la modif pr�c�dente) Modification du label pour les tooltips dans le cas des familles avec axe N
  13/05/2009 GHX
  - Prise en compte d'un cas particulier sur les PIE avec le split by sur le 3ieme axe en ONE pour une valeur particuliere sur l'axe 1 (et cet axe doit �tre sup�rieur au niveau min)
  14/05/2009 GHX
  - L'id du produit en mis au d�but au lieu de la fin du tableau pour le GIS
  15/05/2009 GHX
  - Prise en compte du label du troisieme car dans certains cas on ne l'avais pas
  - Correction des titres des PIE dans le cas de familles diff�rentes avec des axes 3ieme axes diff�rents
  18/05/2009 GHX
  - Correction d'un bug au niveau du s�lecteur dans le cas d'un sort by "none"
  19/05/2009 GHX
  - Prise en compte d'une limite dans le sort by
  25/05/2009 GHX
  - Prise en compte d'une limite dans le sort by (suite et fin)
  Pour information on a 2 cas o� on applique la limite:
  CAS 1 :
  - pas de filtre de d�fini
  - tous les raw/kpi de tous les graphes/pies du dashboards sont de la m�me familles
  CAS 2 :
  - pas de filtre de d�fini
  - le sort by est sur "none"
  - tous les raw/kpi du graphe ou du PIE sont de la m�me familles
  - Modif concernant le filtre, il se fait toujours sur la date s�lectionn�e
  - Si un filtre ne retourne pas de valeur on affiche un message
  - Modif pour prendre en compte les liens vers AA
  - Ajout d'un attribut de classe qui permet de savoir si les liens sont activ�s ou non ($enabledLinkToAA)
  05/06/2009 GHX
  - Pas de limite en mode ZOOM PLUS
  26/06/2009 GHX
  - Modification de la fonction setNeAxe1SubQuery()
  07/07/2009 GHX
  - Correction du BZ10452 [REC][T&A Cb 5.0][Dashboard] : sur les GTM de type pie, le titre du GTM est mal orthographi�
  08/07/2009 GHX
  - Correction du BZ20476 [REC][T&A CB 5.0][ALCATEL][Dashboard builder] : erreurs dans la restitution des donn�es
  -> Ajout de CDATA pour les balises <text> cf function DisplayResults()
  21/07/2009 GHX
  - Correction du BZ 10358 [REC][T&A Cb 5.0][DASH]: affichage des valeurs null dans le dashboard
  - Modifications pour savoir si les PIEs ont bien des valeurs sur le split by
  - Correction du BZ 8965 [T&A 4.0][User Navigation][Network Element Preferences] : Stats par Cells affich�es alors que le network level = BSC
  - Modification d'un cas ou on est sur le niveau minimum sur ALL et qu'on a qu'une seule valeur d'afficher et qu'on clique dessus on arrive maintenant en ONE au lieu de OT
  29/07/2009 GHX
  - Correction d'un bug sur la navigation
  11/08/2009 GHX
  - Correction d'un bug avec le mapping si on avait un filtre
  14/08/2009 & 17/08/2009 GHX
  - (Evo) Prise en compte qu'il peut y avoir plusieurs KPI/RAW identiques dans un graphe
  -> Modification pour le sort by et le filtre principale (la grosse modif est dans getNe())
  -> Modifcation dans la cr�ation de la requete SQL qui r�cup�re les donn�es
  => PAS DE MODIF sur la r�cup�ration des donn�es ni sur le tableau final soit getGTMElements() et DisplayResults()
  20/08/2009 - MPR : Correction du bug 11108 :
  - On attribue le m�me nom � la pop-up du GIS que celui de GIS Supervision d�finit dans la table menu_deroulant_intranet
  25/08/2009 GHX
  - Correction du BZ 11195 [REC][T&A CB 5.0][TC#37203][TP#3][TS#TT1-CB540][DASHBOARD]: order by par d�faut des GTM non utilis�
  26/08/2009
  - Correction d'un probl�me sur la navigation (cf BZ 8965)
  17/09/2009 GHX
  - Correction du BZ 11465 [REC][T&A GSM 5.0][Dashboard] : pour SMSCenter, suppression des '+' lors de la visualisation d'un �l�ment en particulier
  -> Ajout de l'urlencode
  30/09/2009 GHX
  - Correction du BZ 11778 [REC][T&A CB 5.0][GSM]: Pb gestion des identifiants de menus.
  12:18 14/10/2009 SCT
  - Ajout de la gestion des couleurs sur les �l�ments r�seaux
  - BZ 11992 : erreur array_multisort sur PIE  split 3�me axe
  09:41 16/10/2009
  - BZ 12078 => erreur SQL lors de l'affichage en overtime "eor.color" devient "eor_color"
  27/10/2009 GHX
  - Correction du BZ 11997 [DEV][CB50][ROAMING] : probl�me de label dans graphes PIE avec �l�ments mapp�s
  -> Divers modifs dans le classes
  29/10/2009 GHX
  - Correction du BZ 12150 [T&A 5.0][Setup NE color > affichage des pie] Couleurs configur�es non affich�es en split first axis
  - Correction d'un autre bug
  29/10/2009 MPR
  - Correction du bug 6924 : ajout de la virgule apr�s le a.eor_color car erreur SQL
  29/10/2009 GHX
  - Correction du BZ 12181 [DEV][T&A Gn 5.0] Label 3�me axe � NULL
  06/11/2009 GHX
  - Correction du BZ 12631 [REC][T&A Cigale Roaming GSM][NAVIGATION]: si clique sur pie en overtime sur na min , erreur javascript
  10/11/2009 MPR
  - Correction du BZ12533 : Le lien vers AA est KO
  18/11/2009 GHX
  - Correction du BZ 12893 [DASHBOARD] Navigation KO en Overtime
  03/12/2009 BBX
  - Modification de la fonction getNeFromSortBy()
  => Si le nombre de NE est inf�rieur au nombre de NE pr�sents en topologie, et que le TOP n'est pas atteint on va chercher � r�cup�rer des NE suppl�mentaires sur la p�riode. BZ 13164
  - Ajout de la fonction fetchSortByNeValues(). BZ 13164
  04/12/2009 GHX
  - Correction du BZ 12105 [REC][T&A Cigale Roaming GSM][TC#48354][PIE]: pie avec split by 1er axe en OT , le sort by desc n'est pas pris en compte.
  -> Suppression d'un else dans la fonction getGTMElements()
  - Correction du BZ 13055 [REC][ROAMING][PIE] : Valeurs nulles troisi�me axe affich�es dans les pies
  -> Modificaiton dans le fonction setFamilyQuery()
  09/12/2009 GHX
  - Correction du BZ 12638  [REC][T&A Cigale GSM][NAVIGATION]: pas de navigation possible MSC vers LAC
  -> Modification de la fonction DisplayResults()
  18/12/2009 GHX
  - Correcion du BZ 12483 [REC][T&A ALL 5.0] : si le "SORT" porte sur un champ NULL ou sur "NONE"alors pas de graph
  -> Modification dans la fonction getGTMElements() concernant 2 conditions pour mieux faire la distinction entre les valeurs null et z�ro
  05/03/2010 BBX
  - Ajout de la m�thode "manageConnections"
  - Utilisation de la m�thode "manageConnections" au lieu de DatabaseConnection afin d'�viter les instances redondantes
  08/03/2010 BBX
  - Suppression de la m�thode "manageConnections"
  - Utilisation de la m�thode "Database::getConnection" � la place.
  23/03/2010 NSE bz 14831 :
  - suppression du param�tre data_value inutilis� dans les liens vers AA
  09/06/10 YNE/FJT : SINGLE KPI
  03/08/2010 - MPR : Correction du BZ 16967
 * Export Caddy + Export Google Earth + Display GIS
  -> Le type du graphe envoy� doit �tre "graph" et non "singleKPI"
 * S�lection multiple des �l�ments r�seau
  -> 3 cas possibles :
  -> Cas 1 : Dashboard ONE Pas de s�lection => On passe ALL
  -> Cas 2 : Dashboard ONE ou OT Single KPI avec S�lection multiple => On passe la s�lection multiple
  -> Cas 3 : Dashboard OT les graphes contiennent un seul �l�ment r�seua => On passe cet �l�ment r�seau unique
  28/12/2010 - MPR
  Correction du bz19867 : Gestion du filtre lorsque l'�l�ment 3�me axe est sp�cifi� et que le filtre raw/kpi l'est �galement
 * 18/03/2011 NSE bz 21433 : r�organisation de la requ�te setNeAxe1SubQuery pour g�rer correctement la s�lection multi-NA (s�lection d'�l�ments et de parents)
 * 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph et non pas une fois pour toute pour le Dash
 * 
 * 11/04/2011 MMT bz 21604 fonction ConvertEquivalentNeAxe1 le test dans in_array "00331" == "+331" revoit vrai, il faut forcer "00331" === "+331" utilisation du param�tre optionel strict
 *
 * 04/05/11 OJT : Ajout de la TA pour affichage des infos BH
 *
 * 06/06/2011 MMT DE 3rd Axis:
 *     - ajout de param�tre 3eme axe mapp� sur le 1er axe: $pathsAxeN, $naAbcisseAxeN
 *     - generalisation de la fonction setNeAxe1SubQuery en setNeAxeSubQuery pour utilisation 1er ET 3eme axe
 *     - refactorisation des requ�tes avec nouvelles conditions 3eme axe
 *
 * 20/06/2011 NSE merge Gis without polygons
 *
 * 04/07/2011 MMT Bz 22719 manque NEs dans selection sur multi niveaux si pas de parents sur les element selectionn�s de niveaux inferieurs
 * 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 * 09/10/2012 ACS DE GIS 3D ONLY
 * 11/10/2012 ACS BZ 29729 Condition on raw/kpi is not apply when filtering on a Network element
 * 22/05/2013 : Link to Nova Explorer
 *
 */
?>
<?php

set_time_limit(3600);

/**
 * Classe DashboardData
 *
 * Cette classe permet de r�cup�rer l'ensemble des �l�ments d'un dashboard au vue de son affichage
 *
 * @package Dashboard
 * @author BAC b.audic@astellia.com
 * @version 1.0.0
 * @copyright 2008 Astellia
 */
class DashboardData {

    // Attributs Dashboard

    private $mode;
    private $type = "default";
    private $dashId;
    private $dashName;
    public $dashElements;
    // Attributs GTM

    private $displayType;
    private $gtmElements;
    private $gtmProducts;
    private $gtmTypes;
    private $gtmNames;
    private $gtmGisInfos;
    // Attributs Selecteur : TA

    private $ta;
    private $taValue;
    private $taMin;
    private $period;
    private $isTaBH;
    private $bh;
    private $bhLabel;
    private $bhData;
    // Attributs Selecteur : NA

    private $na;
    private $naAxe1;
    private $naAbcisseAxe1;
    private $naMinAxe1;
    // 14/12/2012 BBX
    // BZ 30049 : ces variables doivent �tre des tableaux
    private $neAxe1 = array();
    private $neAxe1Init = array();
    private $neAxe1Label;
    private $equivalentNeAxe1;
    private $equivalentNeAxe1Init;
    private $naAxeN;
    private $neAxeN;
    // 06/06/2011 MMT DE 3rd Axis
    // nom du Na courrant Axe N
    private $naAbcisseAxeN;
    private $neAxeNLabel;
    private $naLabel;
    private $ne;
    // Single Kpi
    private $ne_ri;
    // Autres attributs Selecteur

    private $topOne;
    private $topOT;
    private $sortBy;
    private $sortByGTM;
    private $sortBySelector;
    private $filter;
    private $filterSelector;
    // Autres informations

    private $idMenu;
    private $ri;
    private $zoomPlus = false;
    private $gtmZoomPlus;
    private $splitType;
    private $master;
    private $masterTopo;
    private $paths;
    // 06/06/2011 MMT DE 3rd Axis. ajout paths pour 3eme axe
    private $pathsAxeN;
    private $gtmElementsGroupAndAxe;
    private $navigation = true;
    private $tabGraphColor;
    // 12:26 29/04/2009 GHX
    // Permet de savoir si le message "Over Time Sort : Indicator/Counter NULL or No Data Found -> Dashboard Element(s) Order by $1 DESC." est d�j� affich�
    private $msgOrderByAlphaAlreadyDisplay = false;
    // 17:15 14/05/2009 GHX
    // Permet de savoir si on doit afficher le troisi�me axe dans le titre du graph dans le cas ou
    // le troisieme n'est pas pr�sent dans le s�lecteur
    private $noDisplayThirdAxis = false;
    // 11:04 25/05/2009 GHX
    // Permet de savoir si le message "U_DASH_FILTER_NO_DATA" est d�j� affich�
    private $msgFilterNoDataAlreadyDisplay = false;
    // 15:23 18/05/2009 GHX
    // Cr�ation de 2 tableaux qui m�morisent les NE par GTM dans le cas d'un sort By "none" au niveau du s�lecteur
    private $neAxe1ByGTM = array();
    private $neAxe1LabelByGTM = array();
    // 18:05 19/05/2009 GHX
    // Ajout d'une limite sur le sortBy
    private $limitSortBy = '';
    // 11:56 25/05/2009 GHX
    // Permet de savoir si les liens vers AA sont activ�s ou non
    private $enabledLinkToAA = false;
    // 14:21 26/05/2009 GHX
    // Tableau contenant les liens vers AA
    private $linkToAA = array();
    //CB 5.3.1 : Link to Nova Explorer
    // Permet de savoir si les liens vers NE sont activ�s ou non
    private $enabledLinkToNE = false;
    // Tableau contenant les liens vers NE
    private $linkToNE = array();
    // 14:34 17/08/2009 GHX
    // TRUE si on a plusieurs fois le m�me KPI/RAW (code+legende) pour le sort by
    private $multiSort = false;
    // Variables sp�cifiques � la classe

    private $gtmContent;
    private $debug = false;
    // M�morise les instances de connexions ouvertes
    private static $connections = Array();
    //utile pour SingleKPI
    // Tableaux des raw/kpi utilis�s dans les graphs du GTM
    private $singleKPI_kpiID;
    private $singleKPI_rawID;
    // Champs utilis�s pour le Mode Fixed Hour
    protected $_fhNa = ''; // Label du Network Agregation 1er axe
    protected $_fhNe = ''; // Label du Network Element 1er axe
    protected $_fhNa3 = ''; // Label du Network Agregation 3eme axe
    protected $_fhNe3 = ''; // Label du Network Element 3eme axe
    protected $_fhBHType = ''; // Type de l'�l�ment utilis� pour le calcul de BH (raw, kpi)
    protected $_fhBHKpi = ''; // Label de l'�lement utilis� pour le calcul de BH

    // Constantes
    // Note : r�cup�rer la valeur de 'SEPARATOR' via 'sys_global_parameters'

    const SEPARATOR = '|s|';
    const GIS_SEPARATOR = '|@|';

    /**
     * Constructeur de la classe
     *
     */
    public function __construct() {
        
    }

    // D�finition des Setters. Pour chaque Setter, d�finir des v�rifications sur la validit� des �l�ments � d�finir

    /**
     * Active les liens vers AA
     *
     * @author GHX
     * @version CB4.1.0.00
     * @since CB4.1.0.00
     * @param boolean $enabled active les liens vers AA (default true)
     */
    public function setEnabledLinkToAA($enabled = true) {
        $this->enabledLinkToAA = $enabled;
    }

// End function setEnabledLinkToAA
    //CB 5.3.1 : Link to Nova Explorer
    public function setEnabledLinkToNE($enabled = true) {
        $this->enabledLinkToNE = $enabled;
    }

    /**
     * D�finition des valeurs de l'agr�gation temporelle
     *
     * @param string $ta nom de la ta
     * @param int $ta_value valeur de la ta
     * @param int $period valeur de la p�riode
     */
    public function setTA($ta, $ta_value, $period) {
        $this->ta = $ta;

        // On valide ici si la ta est une ta bh (busy hour) ou non

        $this->isTaBH = (!(strpos($ta, "bh") === false));

        $this->taValue = $ta_value;
        $this->period = $period;
    }

    /**
     * Initialisation des donn�es propre au Fixed Hour mode.
     *
     * @since 5.0.6.00
     * @param string  $na   Nom du Network Agregation 1er axe
     * @param string  $ne   Identifiant du Network Element 1er axe
     * @param string  $na3  Nom du Network Agregation 3eme axe
     * @param string  $ne3  Identifiant du Network Element 3eme axe
     * @param string  $fam  Identifiant de la famille
     * @param integer $prod Identifiant du produit
     * @return none
     */
    public function setFixedHourInfo($na, $ne, $na3, $ne3, $fam, $prod) {
        // Gestion des �l�ments 1er axe
        $this->_fhNa = NaModel::getAggregationLabel($na, $fam, $prod);
        if (!$this->_fhNa) {
            // Si le label n'est pas trouv�, utilisation du nom
            $this->_fhNa = $na;
        }

        $this->_fhNe = NeModel::getLabel($ne, $na, $prod);
        if (!$this->_fhNe) {
            // Si le label n'est pas trouv�, utilisation de l'identifiant
            $this->_fhNe = $ne;
        }

        // Gestion des �l�ments 3eme axe (si d�fini)
        if (strlen(trim($na3)) > 0 && strlen(trim($ne3)) > 0) {
            $this->_fhNa3 = NaModel::getAggregationLabel($na3, $fam, $prod);
            if (!$this->_fhNa3) {
                $this->_fhNa3 = $na3;
            }

            $this->_fhNe3 = NeModel::getLabel($ne3, $na3, $prod);
            if (!$this->_fhNe3) {
                $this->_fhNe3 = $ne3;
            }
        }

        // Gestion du Raw/Kpi de calcul de BH
        $famModel = new FamilyModel($fam, $prod);
        $bhInfo = $famModel->getBHInfos();
        if (count($bhInfo) > 0) {
            $this->_fhBHType = strtolower($bhInfo['bh_indicator_type']);
            if (strtolower($bhInfo['bh_indicator_type']) == 'kpi') {
                $rawKpiModel = new KpiModel();
                $field = 'kpi_name';
            } else {
                $rawKpiModel = new RawModel();
                $field = 'edw_field_name';
            }
            $bhRawKpiId = $rawKpiModel->getIdFromSpecificField($field, $bhInfo['bh_indicator_name'], Database::getConnection($prod));
            $this->_fhBHKpi = $rawKpiModel->getLabelFromId($bhRawKpiId, Database::getConnection($prod));
        }
    }

    /**
     * D�finition de la ta minimale
     *
     * @param string $ta_min nom de la ta minimale
     */
    public function setTAMin($ta_min) {
        $this->taMin = $ta_min;
    }

    /**
     * D�finition des bh
     *
     * @param array $bh liste des bh � inclure dans les requetes
     */
    public function setBH($bh) {
        $this->bh = $bh;
    }

    /**
     * D�finition des labels des bh
     *
     * @param array $bh_label liste des labels des bh
     */
    public function setBHLabel($bh_label) {
        $this->bhLabel = $bh_label;
    }

    /**
     * D�finition de la na
     *
     * @param string $na nom de la na
     */
    public function setNA($na) {
        $this->na = $na;
    }

    /**
     * D�finition de l'identifiant du menu du dashboard
     *
     * @param string $id_menu identifiant du menu
     */
    public function setIdMenu($id_menu) {
        $this->idMenu = $id_menu;
    }

    /**
     * D�finition de l'application "maitre"
     *
     * @param int $master_id identifiant de l'application "maitre"
     */
    public function setMaster($master_id) {
        $this->master = $master_id;
    }

    /**
     * D�finition de l'application "maitre" de topologie
     *
     * @param int $master_topo_id identifiant de l'application "maitre" topologie
     */
    public function setMasterTopo($master_topo_id = '') {
        if ($master_topo_id == '') {
            // Appel � la fonction 'getMasterProduct()' de "php/edw_function.php" pour d�terminer les infos du produit "master topology"

            $master_infos = getTopoMasterProduct();
            $master_topo_id = $master_infos['sdp_id'];
        }

        $this->masterTopo = $master_topo_id;
    }

    /**
     * D�finition de la na d'axe 1 (na du s�lecteur)
     *
     * @param string $na_axe1 nom de la na d'axe 1
     */
    public function setNaAxe1($na_axe1) {
        $this->naAxe1 = $na_axe1;
    }

    /**
     * D�finition de la na d'axe 1 minimale
     *
     * @param string $na_axe1_min nom de la na d'axe 1 minimale
     */
    public function setNaMinAxe1($na_axe1_min) {
        $this->naMinAxe1 = $na_axe1_min;
    }

    /**
     * D�finition des ne de la na d'axe 1
     *
     * @param array $ne_axe1 liste des ne d'axe 1. Ce tableau est de la forme : $this->neAxe1['na'][0, 1, 2, 3, ...]. Exemple : $this->neAxe1['rnc'][501, 502, 505, ...]
     */
    public function setNeAxe1($ne_axe1) {
        $this->neAxe1 = $ne_axe1;

        // On sauvegarde les ne initialement s�lectionn�es afin de pouvoir les r�utiliser dans le cas d'un appel multiple de 'getNE()'

        $this->neAxe1Init = $ne_axe1;
    }

    /**
     * Permet de d�finir le tableau d'equivalence entre les ne pr�selectionn�es de diff�rents produits.
     * Ainsi, pour un produit donn� (ex. 3) o� une ne vaut X1 et sa r�f�rence S1 on notera : $this->equivalentNeAxe1[3]['X1'] = 'S1'
     *
     * @param array $equivalent_ne_axe1 tableau d'�quivalence
     */
    public function setEquivalentNeAxe1($equivalent_ne_axe1) {
        $this->equivalentNeAxe1 = $equivalent_ne_axe1;

        // On sauvegarde les �quivalences de ne initialement d�finies afin de pouvoir les r�utiliser dans le cas d'un appel multiple de 'getNE()'

        $this->equivalentNeAxe1Init = $equivalent_ne_axe1;
    }

    /**
     * D�finition de la na d'axe 1 affich�e en abcisse des graphes ou d�finissant le split.
     *
     * @param string $na_axe1_abc nom de la na d'axe 1 abcisse
     */
    public function setNaAbcisseAxe1($na_axe1_abc) {
        $this->naAbcisseAxe1 = $na_axe1_abc;
    }

    /**
     * 06/06/2011 MMT DE 3rd Axis.
     * D�finition de la na d'axe N affich�e en abcisse des graphes ou d�finissant le split.
     * @param <type> $na_axeN_abc
     */
    public function setNaAbcisseAxeN($na_axeN_abc) {
        $this->naAbcisseAxeN = $na_axeN_abc;
    }

    /**
     * D�finition des na d'axe n r�parties par produit
     *
     * @param array $na_axe_n liste des na d'axe n. Ce tableau est de la forme : tab[id_produit][famille] = na axe n
     */
    public function setNaAxeN($na_axe_n) {
        $this->naAxeN = $na_axe_n;
    }

    /**
     * D�finition des ne d'axe n
     *
     * @param array $ne_axe_n liste des ne d'axe n. Ce tableau est de la forme : tab[id_produit][famille][na axe n] = ne axe n
     */
    public function setNeAxeN($ne_axe_n) {
        $this->neAxeN = $ne_axe_n;
    }

    /**
     * D�finition du label des ne d'axe n
     *
     * @param array $ne_axeN_label liste des labels des ne d'axe n. Ce tableau est de la forme : ...
     */
    public function setNeAxeNLabel($ne_axeN_label) {
        $this->neAxeNLabel = $ne_axeN_label;
    }

    /**
     * D�finition du label des na d'axe 1
     *
     * @param array $na_label liste des labels des na d'axe 1. Ce tableau est de la forme : ...
     */
    public function setNALabel($na_label) {
        $this->naLabel = $na_label;
    }

    /**
     * Permet de d�finir la navigation
     *
     * @param boolean $navigation navigation activ� ou non
     */
    public function setGTMNavigation($navigation) {
        $this->navigation = $navigation;
    }

    /**
     * D�finition du mode du dashboard
     *
     * @param string $mode nom du mode (overnetwork ou overtime)
     */
    public function setMode($mode) {
        $this->mode = $mode;
    }

    /**
     * Permet de r�cup�rer le type d'un GTM � partir de l'identifiant de ce dernier
     *
     * @param int $gtm_id identifiant du GTM
     * @return string nom du type du GTM (graph, pie, pie3D)
     *
     */
    public function getGTMType($gtm_id) {
        return $this->gtmTypes[$gtm_id];
    }

    /**
     * Permet de r�cup�rer le nom d'un GTM � partir de l'identifiant de ce dernier
     *
     * @param int $gtm_id identifiant du GTM
     * @return string nom du GTM
     */
    public function getGTMName($gtm_id) {
        return $this->gtmNames[$gtm_id];
    }

    /**
     * Fonction qui retourne le mode du dashboard (OverTime ou OverNetworkElement
     * @return string $mode : mode du dashboard
     */
    public function getGTMMode() {

        return $this->mode;
    }

    /**
     * Permet de r�cup�rer les informations du raw / kpi du GIS d'un GTM � partir de l'identifiant de ce dernier
     *
     * @param int $gtm_id identifiant du GTM
     * @return array informations du raw / kpi du GIS
     */
    public function getGTMGisInfos($gtm_id) {
        return $this->gtmGisInfos[$gtm_id];
    }

    /**
     * Retourne le nom du dashboard courant
     *
     * @return string le nom du dashboard courant
     */
    public function getDashName() {
        if ($this->dashName == '')
            $this->setDashName();

        return $this->dashName;
    }

    /**
     * Permet de d�finir le nom du dashboard courant
     *
     */
    private function setDashName() {

        $dash_model = new DashboardModel($this->dashId);

        $this->dashName = $dash_model->getName();
    }

    /**
     * D�finition du top des GTMs du dashboard
     *
     * @param int $top valeur du top
     */
    public function setTop($top) {
        if ($this->mode == "overnetwork"/* || (!(strpos($this->type, "pie") === false)) */) {
            $this->setTopOverNetwork($top);
        } else if ($this->mode == "overtime") {
            $this->setTopOverTime($top);
        }
    }

    /**
     * D�finition du top Overnetwork. Cette m�thode est appel�e en interne par la m�thode 'setTop()'
     *
     * @param string $top valeur du top Overnetwork
     */
    private function setTopOverNetwork($top) {
        $this->topOne = $top;
    }

    /**
     * D�finition du top Overtime. Cette m�thode est appel�e en interne par la m�thode 'setTop()'
     *
     * @param string $top valeur du top Overtime
     */
    private function setTopOverTime($top) {
        $this->topOT = $top;
    }

    /**
     * Renvoie le top en cours dans le dashboard en fonction du mode d�fini pour celui-ci
     *
     * @return int valeur du top
     */
    private function getTop() {
        if ($this->mode == "overnetwork") {
            return $this->topOne;
        } else if ($this->mode == "overtime") {
            return $this->topOT;
        }
    }

    /**
     * D�finition du raw / kpi de tri
     *
     * Le format du tableau d'entr�e r�f�rencant les diff�rentes informations du raw / kpi de tri est de la forme suivante :
     * array('name' => ..., 'type' => ..., 'family' => ..., 'product' => ..., 'asc_desc' => ...)
     * Exemple : array('name' => "cs_auth_dur_max", 'type' => "kpi", 'family' => "ept", 'product' => 3, 'asc_desc' => "ASC")
     *
     * @param array $sort_by tableau contenant les informations du raw / kpi de tri
     */
    public function setSortBy($sort_by) {
        $this->sortBy = $sort_by;
    }

    /**
     * Permet de d�finir le raw / kpi de tri depuis des informations transmises via le selecteur
     *
     * @param string $sort_selector chaine de param�tres de tri du selecteur
     * @param string $sort_order ordre de tri d�fini dans le selecteur
     */
    public function setSortByFromSelector($sort_selector, $sort_order) {
        // On sauvegarde la valeur du tri du selecteur dans une variable d'instance (utilis�e ensuite dans le lien)

        $this->sortBySelector = $sort_selector . "@" . $sort_order;

        // On explose la chaine de param�tres du tri du selecteur sous la forme de variables

        list($sort_type, $sort_id, $sort_product, $sort_gtm) = explode("@", $sort_selector);

        $gtm_model = new GTMModel($sort_gtm);

        if ($sort_type == "raw") {
            $other_infos = $gtm_model->getRawInformations($sort_id, $sort_product);
        } else { // Cas des kpis
            $other_infos = $gtm_model->getKpiInformations($sort_id, $sort_product);
        }

        //$sort_label = $this->gtmElements[$sort_type][$sort_product][$other_infos['family']][$other_infos['name']]['label'];
        // 16:54 14/08/2009 GHX
        // Ajout de l'id du graph
        $this->setSortBy(array('name' => $other_infos['name'], 'label' => $other_infos['label'], 'type' => $sort_type, 'family' => $other_infos['family'], 'product' => $sort_product, 'asc_desc' => $sort_order, 'id_gtm' => $sort_gtm));
    }

    /**
     * D�finition du raw / kpi de filtre
     *
     * Le format du tableau d'entr�e r�f�rencant les diff�rentes informations du raw / kpi de filtre est de la forme suivante :
     * array('name' => ..., 'type' => ..., 'family' => ..., 'product' => ..., 'operand' => ..., 'value' => ...)
     * Exemple : array('name' => "cs_calls_ineff_cn_rns", 'type' => "kpi", 'family' => "ept", 'product' => 3, 'operand' => ">=", 'value' => 10)
     *
     * @param array $filter tableau contenant les informations du raw / kpi de filtre
     */
    public function setFilter($filter) {
        $this->filter = $filter;
    }

    /**
     * Permet de d�finir le raw / kpi de filtre, l'op�rande et la valeur depuis des informations transmises via le selecteur
     *
     * @param string $filter_selector chaine de param�tres de filtre du selecteur
     * @param string $filter_operand op�rande du filtre d�fini dans le selecteur
     * @param numeric $filter_value valeur du filtre d�finie dans le selecteur
     */
    public function setFilterFromSelector($filter_selector, $filter_operand, $filter_value) {
        // On sauvegarde la valeur du filtre du selecteur dans une variable d'instance (utilis�e ensuite dans le lien)

        $this->filterSelector = $filter_selector . "@" . $filter_operand . "@" . $filter_value;

        // On explose la chaine de param�tres du filtre du selecteur sous la forme de variables

        list($filter_type, $filter_id, $filter_product, $filter_gtm) = explode("@", $filter_selector);

        $gtm_model = new GTMModel($filter_gtm);

        if ($filter_type == "raw") {
            $other_infos = $gtm_model->getRawInformations($filter_id, $filter_product);
        } else {
            $other_infos = $gtm_model->getKpiInformations($filter_id, $filter_product);
        }

        // 11:02 17/08/2009 GHX
        // Ajout du label et de l'id du graphe
        $this->setFilter(array('name' => $other_infos['name'], 'label' => $other_infos['label'], 'type' => $filter_type, 'family' => $other_infos['family'], 'product' => $filter_product, 'operand' => $filter_operand, 'value' => $filter_value, 'id_gtm' => $filter_gtm));
    }

    /**
     * D�finition des "paths" utilis�s par les na d'axe1
     *
     * @param array $paths tableau contenant les chemins des na o� la na_source est la cl� et la na_cible la valeur
     */
    public function setPaths($paths) {
        $this->paths = $paths;
    }

    /**
     * 06/06/2011 MMT DE 3rd Axis.
     * D�finition des "paths" utilis�s par les na d'axeN
     * @param array $paths tableau contenant les chemins des na o� la na_source est la cl� et la na_cible la valeur
     */
    public function setPathsAxeN($paths) {
        $this->pathsAxeN = $paths;
    }

    /**
     * Activation / D�sactivation du d�buggage
     *
     * @param $debug boolean activer / d�sactiver le d�buggage
     * @return void
     */
    public function setDebug($debug) {
        $this->debug = $debug;
    }

    /**
     * D�finition du mode "zoom plus". Ce mode est un enfant du mode Overnetwork
     *
     * @param boolean $zoom_plus activation (true) / d�sactivation (false) du mode "zoom plus"
     * @param int $gtm_id identifiant du GTM auquel on applique le mode "zoom plus". A noter, seul ce GTM sera affich�
     * @param int $offset nombre d'�l�ments par GTM
     */
    public function setZoomPlus($zoom_plus, $gtm_id, $offset) {
        $this->zoomPlus = $zoom_plus;
        $this->gtmZoomPlus = $gtm_id;

        $this->setTop($offset);
    }

    /**
     * Renvoie les informations du raw / kpi de tri du GTM ou celui d�fini de mani�re globale
     *
     * @param int $gtm_id identifiant du GTM (optionnel)
     * @return array informations sur le raw / kpi de tri
     */
    public function getSortBy($gtm_id = '') {
        $sort_by = array();

        if (count($this->sortBy) == 0) {
            $sort_by = $this->sortByGTM[$gtm_id];
        } else {
            $sort_by = $this->sortBy;
        }
        return $sort_by;
    }

    /**
     * Renvoie les informations du raw / kpi de split d'un pie
     *
     * @param int $gtm_id identifiant du GTM (optionnel)
     * @return array informations sur le raw / kpi de split
     */
    public function getSplitBy($gtm_id = '') {
        if (!isset($this->splitType[$gtm_id])) {
            if (!(strpos($this->gtmTypes[$gtm_id], "pie") === false)) {
                $gtm_model = new GTMModel($gtm_id);

                $this->splitType[$gtm_id] = $gtm_model->getGTMSplitBy();
            }
        }

        return $this->splitType[$gtm_id];
    }

    /**
     * Retourne le s�parateur utilis� dans la classe
     *
     * @return string s�parateur utilis�
     */
    public function getSeparator() {
        return self::SEPARATOR;
    }

    /**
     * Retourne le groupe et la pr�sence / absence d'un axe N � partir d'un identifiant produit et du nom d'une famille
     *
     * @param int $product identifiant du produit
     * @param string $family nom de la famille
     * @return array
     */
    private function GetGtmElementsGroupAndAxe($product, $family) {
        if (!isset($this->gtmElementsGroupAndAxe[$product][$family])) {

            $this->setGtmElementsGroupAndAxe($product, $family);
        }

        return $this->gtmElementsGroupAndAxe[$product][$family];
    }

    /**
     * Permet de d�finir la couleur � utiliser dans le titre des GTMs
     *
     * @return string nom de la couleur � utiliser dans le titre
     */
    private function getTabGraphColor() {
        // Si la variable d'instance '$this->tabGraphColor' n'est pas d�finie, on r�cup�re sa valeur dans la table 'sys_global_parameters'

        if (!isset($this->tabGraphColor))
            $this->tabGraphColor = get_sys_global_parameters('tabgraph_color', "deeppink");

        return $this->tabGraphColor;
    }

    /**
     * Fonction qui v�rifie si on doit ou non affich� le 3�me axe dans le ou les graphes (  S'il y a au moins une famille sans 3�me axe, on inhibe celui-ci)
     */
    private function checkAxe3OnAllFamilies($gtm_id) {
        global $db;

        $find = true;

        if (!isset($db)) {
            $db = Database::getConnection(0);
        }

        $na_in_common_axe3 = getNALabelsInCommon($gtm_id, "na_axe3");

        if (is_array($na_in_common_axe3)) {
            $find = false;
        }

        $this->checkAxe3OnAllFamilies = $find;
    }

    /**
     * D�finition du groupe et de la pr�sence / absence d'un axe N � partir d'un identifiant produit et du nom d'une famille
     *
     * @param int $product identifiant du produit
     * @param string $family nom de la famille
     */
    private function setGtmElementsGroupAndAxe($product, $family) {
        // Utilisation d'une fonction de "php/edw_function.php" pour d�terminer le group_table

        $group_table_infos = GetGTInfoFromFamily($family, $product);
        $group_table = $group_table_infos['edw_group_table'];

        // Utilisation d'une fonction de "php/edw_function.php" pour d�terminer si l'axe3 est pr�sent ou non
        $axeN = GetAxe3($family, $product);

        // Stockage dans le tableau '$this->gtmElementsGroupAndAxe' des valeurs pr�cedemment d�finies

        $this->gtmElementsGroupAndAxe[$product][$family] = array('group_table' => $group_table, 'axeN' => $axeN);
    }

    /**
     * 11/10/2012 BBX
     * BZ 29653 : r��criture de la m�thode afin de g�n�rer des conditions optimales
     * Le code est simplifi� et les performances am�lior�es, notemment pour le cas indiqu� dans le bug.
     * 
     * 06/06/2011 MMT DE 3rd Axis function devenue g�n�rique pour axes 1 et N, les variables importantes sont pass�es
     * en param�tre
     *
     * D�finition de la sous-requete de recherche des valeurs de la na d'axe 1 ou N
     * cette fonction est utilis�e dans la g�n�ration des dashboards, mais �galement dans la g�n�ration des rapports
     * @param array $neAxe  liste des ne de l'axe en question. tableau est de la forme : $this->neAxe1['na'][0, 1, 2, 3, ...]. Exemple : $this->neAxe1['rnc'][501, 502, 505, ...]
     * @param int $axe numero de l'axe: 1 ou 3
     * @param String $naAbcisse nom du NA level de l'axe en question
     * @param array $paths "paths" utilis�s par les na de l'axe en question
     * @param int $product_id identifiant du produit sur lequel on cherche les ne, optionel
     * @return String sous-requete SQL
     */
    private function setNeAxeSubQuery($neAxe, $axe, $naAbcisse, $paths, $product_id = "") {
        // Definition des prefix pour les tables et alias en fonction de l'axe
        $tablePrefix = "e";
        $eorAlias = "a";
        if ($axe != 1) {
            $tablePrefix = "en";
            $eorAlias = "b";
        }

        // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
        // definition des conditions 'permanentes' li� � l'axe
        $tab_naConds = array();
        $tab_naConds[] = NeModel::whereClauseWithoutVirtual('', $naAbcisse);
        $tab_naConds[] = "$eorAlias.eor_on_off = 1";
        $tab_naConds[] = "$eorAlias.eor_obj_type = '$naAbcisse'";
        $tab_naConds[] = "$eorAlias.eor_id = $naAbcisse";

        // D�claration des variables de retour
        $condition = '';
        $tables = '';

        // Condition sur notre NA
        if (count($tab_naConds) > 0) {
            $condition .= "-- conditions permanentes \n AND " . implode(" AND \n", $tab_naConds) . "\n";
        }

        // Jointures avec la topologie
        // Gestion des parents
        $tab_conditions = array();
        foreach ($neAxe as $na => $ne) {
            // Mapping sur le premier axe
            if ($axe == 1)
                $ne = $this->ConvertEquivalentNeAxe1($ne, $na, $product_id);

            // Condition sur l'�l�ment recherch�
            if ($naAbcisse == $na)
                $tab_conditions[] = "$eorAlias.eor_id IN ('" . implode("','", $ne) . "')";
            // Condition sur les parents
            else {
                // Calcul du parent et de son enfant
                $parentNa = $na;
                $enfantNa = $paths[$na];
                // Pr�paration de la premi�re sous requ�te
                $subQuery = "'" . implode("','", $ne) . "'";
                // Calcul r�curcif des chemins d'agr�gation
                while ($parentNa != $naAbcisse) {
                    // Sous requ�tes r�curcives pour calculer les chemins
                    $subQuery = "SELECT eoar_id
                            FROM edw_object_arc_ref
                            WHERE eoar_arc_type = '$enfantNa|s|$parentNa'
                            AND eoar_id_parent IN ($subQuery)";
                    // Calcul du parent et de son enfant
                    $parentNa = $enfantNa;
                    $enfantNa = $paths[$parentNa];
                }
                // R�cup�ration des conditions qui sortent du four
                $tab_conditions[] = "$eorAlias.eor_id IN ($subQuery)";
            }
        }

        // Si on a des conditions sur la topologie, on les ajoute � la requ�te
        if (!empty($tab_conditions))
            $condition .= "\n -- conditions topologie \n AND (" . implode(" \n OR ", $tab_conditions) . ")";

        // Retour des conditions g�n�r�es par la m�thode
        return array('tables' => $tables, 'conditions' => $condition);
    }

    /**
     * 06/06/2011 MMT DE 3rd axis deplacement de tout le code dans setNeAxeSubQuery pour generalisation 1er/3eme axe
     *
     * D�finition de la sous-requete de recherche des valeurs de la na d'axe 1
     * cette fonction est utilis�e dans la g�n�ration des dashboards, mais �galement dans la g�n�ration des rapports
     * avant d'appeler cette fonction, il est imp�ratif de d�finir les relations entre les niveaux d'agr�gation parent et fils � l'aide de la fonction setPaths()
     *
     * @param int $product_id identifiant du produit sur lequel on cherche les ne d'axe 1
     * @return string la requete � executer pour d�finir les ne
     */
    private function setNeAxe1SubQuery($product_id = "") {
        return $this->setNeAxeSubQuery($this->neAxe1, 1, $this->naAbcisseAxe1, $this->paths, $product_id);
    }

    /**
     * Permet de d�finir les �l�ments d'un dashboard
     *
     * @param int $dash_id identifiant du dashobard
     */
    public function getElements($dash_id) {
        $this->dashId = $dash_id;

        $this->dashElements = array();

        $time_start = $this->microtime_float();

        // D�finition d'une nouvelle instance du mod�le de dashboard afin de r�cup�rer toutes les informations n�cessaires de celui-ci (liste des gtms par exemple)

        $dash_model = new DashboardModel($this->dashId);


        // 18:06 19/05/2009 GHX
        // On d�finit la limite sur le sort by si (CAS 1)
        //	- tous les raw/kpi de tous les graphes/pies du dashboards sont de la m�me familles
        //	- pas de filtre de d�fini
        $limitSortBy = '';
        // 10:54 05/06/2009 GHX
        // Pas de limite en ZOOM PLUS
        if ($dash_model->allGtmAreSameFamily() && $this->filterSelector == "" && !$this->zoomPlus) {
            $limitSortBy = ' LIMIT ' . $this->getTop();
        }

        // On parcours les GTMs du dashboard et l'on s�lectionne les �l�ments qui le composent (raws / kpis)
        // On passe en param�tre l'identifiant du GTM ZoomPlus (s'il existe) � la m�thode 'getGtms()' de la classe 'DashboardModel()' afin de limiter les GTMs � celui-ci uniquement

        $gtms = $dash_model->getGtms($this->gtmZoomPlus);

        $sortby_tmp = false;

        foreach ($gtms as $gtm_id => $gtm_name) {

            $gtm_model = new GTMModel($gtm_id);

            // maj 12/01/2010 - MPR : On v�rifie s'il y a des na 3�me en commun
            $this->checkAxe3OnAllFamilies($gtm_id);

            // 09:49 25/05/2009 GHX
            // On d�finit la limite sur le sort by si (CAS 2)
            //	- tous les raw/kpi du graphe ou du PIE sont de la m�me familles
            //	- pas de filtre de d�fini au niveau du s�lecteur
            //	- le sort by est sur "none"
            $this->limitSortBy = $limitSortBy;
            // 10:54 05/06/2009 GHX
            // Pas de limite en ZOOM PLUS
            if ($dash_model->allGtmAreSameFamily($gtm_id) && $this->filterSelector == "" && $this->sortBySelector == "" && $limitSortBy == "" && !$this->zoomPlus) {
                $this->limitSortBy = ' LIMIT ' . $this->getTop();
            }

            $gtm_products = $gtm_model->getGTMProducts();

            // Sauvegarde des produits d�finis dans le GTM dans la variable d'instance '$this->gtmProducts'

            $this->gtmProducts[$gtm_id] = $gtm_products;

            // Sauvegarde du type et du nom des GTMs

            $this->gtmTypes[$gtm_id] = $gtm_model->getGTMType();
            $this->gtmNames[$gtm_id] = $gtm_name;

            // D�finition des informations du raw / kpi du GIS du GTM

            $this->gtmGisInfos[$gtm_id] = $gtm_model->getGTMGisInformations();

            // D�finition des raws / kpis du GTM

            $gtm_raws = $gtm_model->getGtmRawsByProduct();
            $gtm_kpis = $gtm_model->getGtmKpisByProduct();

            // Sauvegarde des raws / kpis des GTM

            $this->gtmElements[$gtm_id] = array('raw' => $gtm_raws, 'kpi' => $gtm_kpis);

            // Si il n'existe pas de tri d�fini ("none"), on trie les �l�ments suivants le raw / kpi de tri du GTM

            if (count($this->sortBy) == 0) {
                // D�finition du tri � utiliser. Le tri propre au GTM sera conserv� dans la variable d'instance '$this->sortByGTM'

                $this->sortByGTM[$gtm_id] = $gtm_model->getGTMSortBy();

                $this->setSortBy($this->sortByGTM[$gtm_id]);

                // On restaure les valeurs initiales des ne d'axe1 et des �quivalences

                $this->setNeAxe1($this->neAxe1Init);
                $this->setEquivalentNeAxe1($this->equivalentNeAxe1Init);

                // D�finition des ne � afficher dans le GTM

                $elements_found = $this->getNE($gtm_id);

                // On "flagge" le fait que le raw / kpi de tri soit temporaire (car propre au GTM)

                $sortby_tmp = true;
            }

            // 11/02/2009 - Modif. benoit : si des �l�ments ont d�ja �t� d�finis, on ne les red�finit pas
            elseif (isset($this->neAxe1) && ($this->neAxe1 != $this->neAxe1Init)) {
                $elements_found = true;
            }

            // On d�finit les ne uniquement si aucun kpi de tri n'existe et que les ne n'ont pas �t� pr�alablement d�finies
            else {
                $elements_found = $this->getNE();
            }

            // On d�finit les valeurs des �l�ments du GTM et on les stocke dans le tableau d'elements du dashboard
            if ($elements_found != false) {
                // Dans le cas du zoom plus, on va boucler sur la g�n�ration des �l�ments du GTM en fonction de l'offset

                if ($this->zoomPlus) {
                    $nb_na = count($this->neAxe1[$this->naAbcisseAxe1]);

                    $top = $this->getTop();

                    $nb_gtms = (($nb_na > $top) ? ceil($nb_na / $top) : 1);

                    for ($i = 0; $i < $nb_gtms; $i++) {
                        $this->dashElements[$gtm_id][] = $this->getGTMElements($gtm_products, $gtm_id, $gtm_raws, $gtm_kpis, $this->neAxe1, ($i * $top));
                    }
                } else {
                    $this->dashElements[$gtm_id][] = $this->getGTMElements($gtm_products, $gtm_id, $gtm_raws, $gtm_kpis, $this->neAxe1);
                }
            } else {
                // S'il n'existe pas d'elements pour le GTM, on stocke juste l'information "No data found"

                $this->dashElements[$gtm_id][] = 'no_data';
            }

            // On supprime la valeur du tri si celui-ci est propre au GTM

            if ($sortby_tmp == true) {
                $this->sortBy = array();
                $sortby_tmp = false;
            }
        }
        // Single Kpi
        $this->singlekpi_kpi = $gtm_kpis;
        $this->singlekpi_raw = $gtm_raws;

        if ($this->debug) {

            $msg = "<span style='font-weight:bold'>"
                    . "Temps n�cessaire � la r�cup�ration des �l�ments des dashboards : " . round(($this->microtime_float() - $time_start), 4)
                    . " seconde(s)"
                    . "</span><br/>";

            echo utf8_encode($msg);
        }

        // on affiche les elements du dashboard
        // echo "<pre style='border:2px solid #9CF;padding:4px;'>";	print_r($this->dashElements); echo "</pre>";
    }

    /**
     * Fonction qui retourne toutes les cl�s, m�me pour des tableaux multidimentionnels
     * @param Array $ar
     * @return Array
     */
    public function multiarray_keys($ar) {
        $keys = array();

        // 16/02/2011 BBX
        // Ajout d'un contr�le sur la variable d'entr�e
        // BZ 20629
        if (!is_array($ar))
            return $keys;

        foreach ($ar as $k => $v) {
            $keys[] = $k;
            if (is_array($ar[$k]))
                $keys = array_merge($keys, $this->multiarray_keys($ar[$k]));
        }
        return $keys;
    }

    /**
     * Permet de r�cup�rer un label du Raw/Kpi en fonction de son ID
     * @param String $raw_kpi_type : raw ou kpi
     * @param String $raw_kpi : c'est l'id du raw/kpi pour lequel on veut r�cup�rer le label
     * @param Integer $gtmProduct : num�ro du produit
     * @return String label
     */
    public function recupKpiRawLabel($raw_kpi_type, $raw_kpi, $gtmProduct) {

        $database_connection = Database::getConnection($gtmProduct[0]);
        if ($raw_kpi_type == 'kpi') {
            $field_name = 'kpi_label';
            $table = 'sys_definition_kpi';
        } else {
            $field_name = 'edw_field_name_label';
            $table = 'sys_field_reference';
        }

        $query = "SELECT {$field_name} FROM {$table} WHERE id_ligne ILIKE '$raw_kpi'";
        $row = $database_connection->getOne($query);

        if ($row == "") {
            $label = $raw_kpi;
        } else {
            $label = $row;
        }

        return $label;
    }

    /**
     * Permet de formater les r�sultats de la d�finition des �l�ments du dashboard au vue de leur affichage
     *
     * @return array tableau d'�l�ments du dashboard
     */
    public function DisplayResults() {
        //print_r($this->dashElements);

        $text_color = 'color="' . $this->getTabGraphColor() . '"';

        $results = array();

        // Quelque soit le mode, la partie "date" dans le titre �tant fixe, on la d�finit en amont
        $ta_label = getTaLabel($this->ta);
        $title_ta_part = "<text><![CDATA[" . $ta_label . " = ]]></text><text " . $text_color . "><![CDATA[" . getTaValueToDisplay($this->ta, $this->taValue, "/") . "]]></text>";

        // Si le mode Fixed Hour est activ�, on ajoute des informations compl�mentaires
        if (strlen($this->_fhNa) > 0) {
            // Ajout du pr�fixe 'Fixed' � la time agregation
            $title_ta_part = str_replace($ta_label, "Fixed {$ta_label}", $title_ta_part);
            $title_ta_part .= "<text> (BH for </text>";
            $title_ta_part .= "<text {$text_color}>{$this->_fhNa} {$this->_fhNe}</text>";
            if (strlen($this->_fhNa3) > 0) {
                $title_ta_part .= "<text> - </text>";
                $title_ta_part .= "<text {$text_color}>{$this->_fhNa3} {$this->_fhNe3}</text>";
            }
            $title_ta_part .= "<text>, calculated from </text>";
            $title_ta_part .= "<text {$text_color}>{$this->_fhBHKpi} </text>";
            $title_ta_part .= "<text> {$this->_fhBHType})</text>";
        }
        $title_ta_part .= "<text> - </text>";

        // On boucle sur l'ensemble des GTMS du dashboard
        // 12:12 09/12/2009 GHX
        // Correction du BZ 12638
        $dashModel = new DashboardModel($this->dashId);
        $naAxe1Paths = $dashModel->getNaPaths(1);

        foreach ($this->dashElements as $gtm_id => $gtm_values) {
            // D�finition d'une variable locale indiquant si des r�sultats existent ou non pour le GTM

            $values_exist = true;

            // 15:20 18/05/2009 GHX
            // Comme on est dans le cas d'un sort by none au niveau du s�lecteur
            // on r�cup�re les NE par GTM
            if ($this->sortBySelector == "") {
                $this->neAxe1 = $this->neAxe1ByGTM[$gtm_id];
                $this->neAxe1Label = $this->neAxe1LabelByGTM[$gtm_id];
            }


            // Traitement des GTMs vides (pas de valeurs d�finies)
            // 22/11/2011 BBX
            // BZ 24764 : correction des notices php
            if (count($gtm_values) == 1 && (!(strpos((string) $gtm_values[0], "no_data") === false))) {
                $values_exist = false;

                // D�finition du nom du GTM (sert pour la d�finition du XML)

                $gtm_name = md5(uniqid(rand(), true));

                $results[$gtm_id][0]['name'] = $gtm_name;

                // On d�finit la valeur � afficher pour la na d'axe1. Cette valeur d�pend des ne d'origine d�finies lors de la cr�ation d'une nouvelle instance de cette classe
                $ne_axe1_value = ((count($this->neAxe1Init[$this->naAxe1]) == 1) ? $this->neAxe1Init[$this->naAxe1][0] : "ALL");
                $ne_axe1_label = (($ne_axe1_value != "ALL") ? getNELabel($this->naAxe1, $ne_axe1_value) : $ne_axe1_value);

                $na_axe1_title = $this->naLabel[$this->naAxe1] . " = <b>" . $ne_axe1_label . "</b>";

                // On d�finit la valeur � afficher pour la na d'axe N. On affiche la valeur de la na d'axe N dans le titre uniquement si il existe un seul axe N d�fini pour le produit

                $na_axeN_title = "";

                $na_axeN = $this->getNaAxeN($gtm_id);

                if (count($na_axeN) == 1 and ! $this->checkAxe3OnAllFamilies) {
                    $gtm_pdt = $this->gtmProducts[$gtm_id];

                    $ne_axeN = "ALL";

                    // Note : m�thode de s�lection de la valeur de la na d'axe N � revoir

                    for ($j = 0; $j < count($gtm_pdt); $j++) {
                        if ($this->checkNeAxeNALL($gtm_pdt[$j]) == false) {
                            if (count($this->neAxeN[$gtm_pdt[$j]]) > 0) {
                                foreach ($this->neAxeN[$gtm_pdt[$j]] as $axeN_fam => $axeN_value) {
                                    // 29/06/2011 : bz22718, gestion du label ALL sur les NE 3�me axe
                                    if (count($axeN_value[$na_axeN[0]]) === 1) {
                                        $ne_axeN = $axeN_value[$na_axeN[0]][0];
                                    } else {
                                        // Dans ce cas la valeur par d�faut ALL est laiss�e
                                    }
                                }
                            }
                        }
                    }

                    $ne_axeN_label = (($ne_axeN != "ALL" ) ? getNELabel($na_axeN, $ne_axeN) : $ne_axeN);

                    $na_axeN_title .= " - " . $this->naLabel[$na_axeN[0]] . " = <b>" . $ne_axeN_label . "</b>";
                }

                // Ajout au titre du top et des infos du raw / kpi de tri. Si '$this->sortBy' est ind�fini, il s'agit d'un tri propre au GTM. On choisi alors la valeur de '$this->sortByGTM'

                $sort_by = ((count($this->sortBy) == 0) ? $this->sortByGTM[$gtm_id] : $this->sortBy);

                $na_title .= "<text><![CDATA[ [ Top " . $top . " Order By ]]></text><text " . $text_color . "><![CDATA[" . $sort_by['label'] . " ]]></text><text><![CDATA[" . ucfirst(strtolower($sort_by['asc_desc'])) . " ]]]></text>";

                // Ajout du titre au tableau de r�sultats du GTM vide
                $results[$gtm_id][0]['title'] = $na_title;

                // D�finition de la barre de propri�t�s du GTM
                $na_infos = array('na_axe1' => $this->naAxe1,
                    'ne_axe1' => $ne_axe1_label,
                    'na_axeN' => $na_axeN,
                    'ne_axeN' => $ne_axeN_label);

                $results[$gtm_id][0]['properties'] = $this->setGTMBarProperties($gtm_id, $na_infos, $this->taValue, $gtm_name, false);

                // Ajout de la propri�t� "msg" indiquant le message � afficher dans le GTM
                // 12:07 11/05/2009 GHX
                // Si pas de donn�es � cause du split By en PIE on n'affiche pas le m�me message d'erreur
                if ($gtm_values[0] == 'no_data_splitBy') {
                    $splitBy = $this->getSplitBy($gtm_id);
                    $results[$gtm_id][0]['msg'] = __T('U_GTM_NO_DATA_FOUND_SPLIT_BY', $this->getGTMName($gtm_id), $ta_label, getTaValueToDisplay($this->ta, $this->taValue, "/"), $na_axe1_title, $na_axeN_title, $this->getTop(), $sort_by['label'], ucfirst(strtolower($sort_by['asc_desc'])), $splitBy['label']
                    );
                } elseif ((!(strpos($this->gtmTypes[$gtm_id], "pie") === false))) {
                    // 14:56 11/05/2009 GHX
                    // Message d'erreur no data found pour les PIE
                    $splitBy = $this->getSplitBy($gtm_id);
                    $labelSplitBy = $this->naLabel[$this->naAbcisseAxe1];

                    if ($splitBy['split_type'] == 'third_axis' && count($this->neAxe1Init[$this->naAxe1]) == 1 && $this->mode == 'overnetwork') {
                        $labelSplitBy = $na_axeN[0];
                    }

                    $results[$gtm_id][0]['msg'] = __T('U_GTM_NO_DATA_FOUND_PIE', $this->getGTMName($gtm_id), $ta_label, getTaValueToDisplay($this->ta, $this->taValue, "/"), $na_axe1_title, $na_axeN_title, $this->getTop(), $sort_by['label'], ucfirst(strtolower($sort_by['asc_desc'])), $splitBy['label'], $labelSplitBy
                    );
                } else {
                    $results[$gtm_id][0]['msg'] = __T('U_GTM_NO_DATA_FOUND', $this->getGTMName($gtm_id), $ta_label, getTaValueToDisplay($this->ta, $this->taValue, "/"), $na_axe1_title, $na_axeN_title, $this->getTop(), $sort_by['label'], ucfirst(strtolower($sort_by['asc_desc'])));
                }

                // Les autres infos du GTM sont d�finies comme vides

                $results[$gtm_id][0]['xaxis'] = array();
                $results[$gtm_id][0]['data'] = array();
                $results[$gtm_id][0]['link'] = array();
            }

            // Traitement du mode overnetwork
            if ($this->mode == "overnetwork" && $values_exist == true) {
                // Dans le cas du zoom plus, on a besoin du top. Pour �viter d'appeler la m�thode 'getTop()' � chaque boucle, on r�cup�re sa valeur ici
                $top = $this->getTop();

                // Th�oriquement il n'existe qu'un seul "sous-GTM" sauf dans le cas du zoomplus o� celui-ci est scind� en fonction du top
                $nb_sub_gtms = count($gtm_values);

                // D�finition de l'index du tableau de r�sultats final
                $idx = 0;

                // On boucle sur l'ensemble des "sous-gtms" d�finis
                for ($i = 0; $i < $nb_sub_gtms; $i++) {

                    $gtm_content = $gtm_values[$i];

                    if (is_array($gtm_content) === true) {
                        // D�finition du nom du GTM (sert pour d�finir le xml et le fichier image)
                        $gtm_name = md5(uniqid(rand(), true));

                        $results[$gtm_id][$idx]['name'] = $gtm_name;

                        // D�finition du titre du GTM
                        // Ajout de la date dans le titre
                        $na_title = $title_ta_part;

                        // On d�finit la valeur � afficher pour la na d'axe1. Cette valeur d�pend des ne d'origine d�finies lors de la cr�ation d'une nouvelle instance de cette classe
                        $ne_axe1_value = ((count($this->neAxe1Init[$this->naAxe1]) == 1) ? $this->neAxe1Init[$this->naAxe1][0] : "ALL");
                        $ne_axe1_label = (($ne_axe1_value != "ALL") ? $this->neAxe1Label[$this->naAxe1][$ne_axe1_value] : $ne_axe1_value);

                        // 10/04/2012 BBX
                        // BZ 26715 : en mode ONE, on n'inscrit pas ALL mais FILTERED
                        // Lorsque des NE sont s�lectionn�s
                        if (count($this->neAxe1Init[$this->naAxe1]) > 1) {
                            $ne_axe1_label = "FILTERED";
                        }

                        $na_title .= "<text><![CDATA[" . $this->naLabel[$this->naAxe1] . " = ]]></text><text " . $text_color . "><![CDATA[" . $ne_axe1_label . "]]></text>";

                        // 16:12 26/05/2009 GHX
                        // D�finition de variables pour les liens vers AA
                        $naAxe1AA = $this->naAxe1;
                        $naAxe3AA = '';
                        // D�finition de variables pour les liens vers NE
                        $naAxe1NE = $this->naAxe1;
                        $naAxe3NE = '';

                        // On d�finit la valeur � afficher pour la na d'axe N. On affiche la valeur de la na d'axe N dans le titre uniquement si il existe un seul axe N d�fini pour le produit
                        $na_axeN = $this->getNaAxeN($gtm_id);

                        if (count($na_axeN) == 1 && !$this->checkAxe3OnAllFamilies) {
                            $gtm_pdt = $this->gtmProducts[$gtm_id];
                            $ne_axeN = "ALL";
                            $filtered = false;

                            // Note : m�thode de s�lection de la valeur de la na d'axe N � revoir
                            for ($j = 0; $j < count($gtm_pdt); $j++) {
                                if ($this->checkNeAxeNALL($gtm_pdt[$j]) == false) {
                                    if (count($this->neAxeN[$gtm_pdt[$j]]) > 0) {
                                        foreach ($this->neAxeN[$gtm_pdt[$j]] as $axeN_fam => $axeN_value) {
                                            // 14/06/2011 MMT De 3rd axis in NE mode, display ALL if no element on the selected NA
                                            // 29/06/2011 : bz22718, gestion du label ALL sur les NE 3�me axe
                                            if (count($axeN_value[$na_axeN[0]]) === 1) {
                                                $ne_axeN = $axeN_value[$na_axeN[0]][0];
                                            } else {
                                                // 10/04/2012 BBX
                                                // BZ 26715 : en mode ONE, on n'inscrit pas ALL mais FILTERED
                                                // Lorsque des NE sont s�lectionn�s
                                                $filtered = true;
                                            }
                                        }
                                    }
                                }
                            }

                            $ne_axeN_label = (($ne_axeN != "ALL") ? $this->neAxeNLabel[$ne_axeN] : $ne_axeN);
                            // 10/04/2012 BBX
                            // BZ 26715 : en mode ONE, on n'inscrit pas ALL mais FILTERED
                            // Lorsque des NE sont s�lectionn�s
                            if ($filtered)
                                $ne_axeN_label = "FILTERED";
                            $naAxe3AA = $na_axeN[0];
                            //CB 5.3.1 : Link to Nova Explorer
                            $naAxe3NE = $na_axeN[0];
                            $na_title .= "<text><![CDATA[ - " . $this->naLabel[$na_axeN[0]] . " = ]]></text><text " . $text_color . "><![CDATA[" . $ne_axeN_label . "]]></text>";
                        }

                        // Ajout au titre du top et des infos du raw / kpi de tri. Si '$this->sortBy' est ind�fini, il s'agit d'un tri propre au GTM. On choisi alors la valeur de '$this->sortByGTM'
                        $sort_by = ((count($this->sortBy) == 0) ? $this->sortByGTM[$gtm_id] : $this->sortBy);

                        if ((!(strpos($this->gtmTypes[$gtm_id], "pie") === false))) {
                            $splitBy = $this->getSplitBy($gtm_id);
                            // 07/12/2009 BBX : correction du texte. BZ 13057
                            $na_title .= "<text><![CDATA[ [ Top " . $top . " Order By ]]></text><text " . $text_color . "><![CDATA[" . $sort_by['label'] . " ]]></text><text><![CDATA[" . ucfirst(strtolower($sort_by['asc_desc'])) . " split with ]]></text><text " . $text_color . "><![CDATA[" . $splitBy['label'] . "]]></text><text> ]</text>";

                            // 16:58 13/05/2009 GHX
                            // Ajout du split By sur le troisieme axe au lieu du split sur le niveau "axe1 - 1"
                            $labelSplitBy = $this->naLabel[$this->naAbcisseAxe1];

                            if ($splitBy['split_type'] == 'third_axis' && count($this->neAxe1Init[$this->naAxe1]) == 1) {
                                $labelSplitBy = $this->naLabel[$na_axeN[0]];
                            }
                            // 10:10 07/07/2009 GHX
                            // Correction du BZ10452 [REC][T&A Cb 5.0][Dashboard] : sur les GTM de type pie, le titre du GTM est mal orthographi�
                            $na_title .= "<text><![CDATA[ Split By " . $labelSplitBy . "]]></text>";
                        } else {
                            $na_title .= "<text><![CDATA[ [ Top " . $top . " Order By ]]></text><text " . $text_color . "><![CDATA[" . $sort_by['label'] . " ]]></text><text><![CDATA[" . ucfirst(strtolower($sort_by['asc_desc'])) . " ]]]></text>";
                        }

                        $results[$gtm_id][$idx]['title'] = $na_title;

                        // D�finition des valeurs qui seront affich�s en abcisse du graphe ou autour du pie
                        $naAxe1Label = $this->naAbcisseAxe1;

                        if ($this->zoomPlus) {
                            // Dans le cas du zoom plus, on limite les r�sultats � une partie des ne d�finies en fonction de l'index et du top
                            $zp_idx = $i * $top;
                            $ne = array_slice($this->neAxe1[$this->naAbcisseAxe1], $zp_idx, $top, false);
                        } else {
                            $ne = $this->neAxe1[$this->naAbcisseAxe1];
                            // 11:18 13/05/2009 GHX
                            // Dans le cas d'un PIE split by sur le 3ieme axe pour une valeur particuliere sur l'axe1 en ONE, on les valeurs des �l�ments r�seaux trouv�s si la requete sur les donn�es
                            // au lieu de ceux r�cup�r� par la fonction getNE()
                            if ((!(strpos($this->gtmTypes[$gtm_id], "pie") === false)) && (count($na_axeN) == 1) && $this->mode == 'overnetwork') {
                                $split_by = $this->getSplitBy($gtm_id);
                                if ($split_by['split_type'] == "third_axis") {
                                    $naAxe1Label = $this->naAxe1;
                                    if (isset($this->neAxe1ForPieThirsAxis[$gtm_id]))
                                        $ne = $this->neAxe1ForPieThirsAxis[$gtm_id][$this->naAxe1];
                                    else
                                        $ne = $this->neAxe1[$naAxe1Label];
                                }
                            }
                        }
                        // 04/02/2009 - Modif. benoit : correction des labels des ne qui n'�tait jamais affich�es. On utilise pour cela les tableaux '$this->neAxe1Label' et '$this->neAxeNLabel' (si aucune valeur d'axe N est sp�cifi�e)
                        // S'il n'existe qu'un seul axe N (cad que les valeurs de ne axe N sont affich�es) on s�pare les valeurs axe1/axeN qui seront affich�s dans le GTM
                        $all_ne = array();
                        if (count($na_axeN) == 1) { // Si on a du troisieme axe
                            $ne_title = array();

                            for ($j = 0; $j < count($ne); $j++) {
                                $all_ne = explode(self::SEPARATOR, $ne[$j]);

                                // 17:28 26/10/2009 GHX
                                if ($this->neAxe1Label[$all_ne[0]] == "") {
                                    $this->neAxe1Label[$naAxe1Label][$all_ne[0]] = $all_ne[0];
                                    foreach ($this->gtmProducts[$gtm_id] as $idProduct) {
                                        if ($tmpLabel = NeModel::getLabel($all_ne[0], $naAxe1Label, $idProduct)) {
                                            $this->neAxe1Label[$naAxe1Label][$all_ne[0]] = $tmpLabel;
                                            break;
                                        }
                                    }
                                }

                                // 11:33 13/05/2009 GHX
                                // $this->naAbcisseAxe1 remplac� par $naAxe1Label

                                if ($this->neAxeNLabel[$all_ne[1]] == "" && (!(strpos($this->gtmTypes[$gtm_id], "pie") === false)) && $this->mode == 'overnetwork') {
                                    $this->neAxeNLabel[$all_ne[1]] = $all_ne[1];
                                    foreach ($this->gtmProducts[$gtm_id] as $idProduct) {

                                        if ($tmpLabel = NeModel::getLabel($all_ne[1], $na_axeN[0], $idProduct)) {
                                            $this->neAxeNLabel[$all_ne[1]] = $tmpLabel;
                                            break;
                                        }
                                    }
                                }

                                $ne_title[] = $this->neAxe1Label[$naAxe1Label][$all_ne[0]] . (($this->neAxeNLabel[$all_ne[1]] != "") ? " - " . $this->neAxeNLabel[$all_ne[1]] : "");
                            }
                        } else { // Si on n'a pas de troisieme axe
                            // 17:09 25/08/2009 GHX
                            // Correction du BZ 11195
                            // Il suffit juste de r�-initialiser le tableau des labels
                            $ne_title = array();
                            // 18:11 18/05/2009 GHX
                            if (strpos($ne[0], self::SEPARATOR) === false) { // Si on n'a pas de troisieme axe
                                for ($j = 0; $j < count($ne); $j++) {
                                    $ne_title[] = $this->neAxe1Label[$this->naAbcisseAxe1][$ne[$j]];
                                }
                            } else { // Si on a un troisieme axe mais non visible dans le s�lecteur
                                for ($j = 0; $j < count($ne); $j++) {
                                    $ne_label = explode(self::SEPARATOR, $ne[$j]);
                                    $ne_title[] = $this->neAxe1Label[$this->naAbcisseAxe1][$ne_label[0]];
                                }
                            }
                        }
                        // 11:33 13/05/2009 GHX
                        // $this->naAbcisseAxe1 remplac� par $naAxe1Label
                        $results[$gtm_id][$idx]['xaxis'] = array('title' => $this->naLabel[$naAxe1Label], 'values' => $ne_title);

                        // D�finition des informations de la barre du GTM
                        // 17/02/2009 - Modif. benoit : on d�finit un tableau des infos na � transmettre � la m�thode de d�finition de la barre du GTM
                        $na_infos = array('na_axe1' => $this->naAxe1, 'ne_axe1' => $ne_axe1_value, 'na_axeN' => (($ne_axeN != '' and ! $this->checkAxe3OnAllFamilies ) ? $na_axeN[0] : ''), 'ne_axeN' => $ne_axeN, 'ne' => $ne);

                        $results[$gtm_id][$idx]['properties'] = $this->setGTMBarProperties($gtm_id, $na_infos, $this->taValue, $gtm_name);

                        // D�finition des valeurs des �l�ments
                        for ($j = 0; $j < count($ne); $j++) {
                            $results[$gtm_id][$idx]['data'][$j] = $gtm_content[$ne[$j]][$this->taValue];
                            // 14:39 08/10/2009 SCT : ajout de la couleur sur l'axe trait�
                            // conditions :
                            //	- mode PIE
                            //	- affichage des 1er et 3�me axes
                            //	- mode OVERNETWORK
                            if ((!(strpos($this->gtmTypes[$gtm_id], "pie") === false)) && (count($na_axeN) == 1)) {
                                // on recherche sur quel axe le PIE est split�
                                //	le PIE est splitt� sur le 3�me axe
                                $split_by = $this->getSplitBy($gtm_id);
                                // 16:20 29/10/2009 GHX
                                // Correction du BZ 12150
                                if ($split_by['split_type'] == "third_axis" && count($this->neAxe1Init[$this->naAxe1]) == 1) {
                                    $ne_color = explode(self::SEPARATOR, $this->neColor[$ne[$j]][$this->taValue]);
                                    $results[$gtm_id][$idx]['ne_color'][$j] = $ne_color[1];
                                }
                                //	le PIE est splitt� sur le 1e axe
                                else {
                                    $ne_color = explode(self::SEPARATOR, $this->neColor[$ne[$j]][$this->taValue]);
                                    $results[$gtm_id][$idx]['ne_color'][$j] = $ne_color[0];
                                }
                            }

                            // Contenu du lien
                            // 10/02/2009 - Modif. benoit : red�fintion de la partie na_axe1 / na_axeN des liens
                            // 16/02/2009 - Modif. benoit : on ne d�finit les liens que si la navigation est active
                            if ($this->navigation == true) {
                                // 15:38 08/12/2009 GHX
                                // Correction du BZ 12638
                                $choiceNa = false;

                                // 16:05 21/07/2009 GHX
                                // Correction du BZ 8965
                                // Si on n'est pas sur le niveau minimum et qu'on a qu'une seule valeur sur le graphe
                                // quand on cliquera sur la valeur on arrivera sur le niveau inf�rieur mais le parent de s�lectionn�
                                // 16:04 26/08/2009 GHX
                                // - Correction d'un probl�me sur la navigation (cf BZ 8965)
                                // 12:27 30/09/2009 GHX
                                // Correction du BZ 11778
                                // Ajout de la condition suivante en effet quand on est sur le na min, on n'a pas de na fils donc pas de valeur
                                if (empty($this->paths[$this->naAbcisseAxe1])) {
                                    $na_in_link = "&na_axe1=" . $this->naAbcisseAxe1;
                                } else {
                                    $na_in_link = "&na_axe1=" . $this->paths[$this->naAbcisseAxe1];

                                    // 15:38 08/12/2009 GHX
                                    // Correction du BZ 12638
                                    if (count($naAxe1Paths[$this->naAbcisseAxe1]) > 1) {
                                        $na_in_link = "&na_axe1=" . $this->naAbcisseAxe1;
                                        $choiceNa = true;
                                    }
                                }

                                // S'il n'existe qu'un seul axe N (cad que les valeurs de ne axe N sont affich�es) on s�pare les valeurs axe1/axeN
                                // Note : on consid�re qu'il n'existe qu'un seul axe N  : l'axe 3
                                $mode_link = $this->mode;

                                // SI on a du troisieme axe
                                if (count($na_axeN) == 1) {
                                    // 06/06/2011 MMT DE 3rd Axis gestion des liens intra-dashboard avec nouveau format de valeur 3eme axe
                                    $all_ne = explode(self::SEPARATOR, $ne[$j]);
                                    $curentNeAxeN = $all_ne[1];
                                    // 10:13 17/09/2009 GHX
                                    // Correction du BZ 11465
                                    // Ajout de l'urlencode sur le NE
                                    $na_in_link .= "&ne_axe1=" . $this->naAbcisseAxe1 . "||" . urlencode($ne[$j]) . "&na_axeN=" . $na_axeN[0] . "&ne_axeN=" . $na_axeN[0] . "||" . urlencode($curentNeAxeN);

                                    // 17:08 21/07/2009 GHX
                                    // Ajout de la derni�re partie de la condition
                                } else { // Si on n'a pas de troisieme axe
                                    // 10:13 17/09/2009 GHX
                                    // Correction du BZ 11465
                                    // Ajout de l'urlencode sur le NE
                                    $na_in_link .= "&ne_axe1=" . $this->naAbcisseAxe1 . "||" . urlencode($ne[$j]);
                                    // 17:08 21/07/2009 GHX
                                    // Ajout de la derni�re partie de la condition
                                }
                                if (($this->naAbcisseAxe1 == $this->naMinAxe1) && (count($ne) == 1) && count($this->neAxe1Init[$this->naAxe1]) == 1) {
                                    $mode_link = "overtime";
                                }

                                // Cr�ation du lien pour quand on clique sur la donn�e du graphe
                                // 15:38 08/12/2009 GHX
                                // Correction du BZ 12638
                                if ($choiceNa) {
                                    // Si un raw / kpi de tri du selecteur est d�fini, on inclut le tri dans le lien
                                    $sort_link = '';
                                    if ($this->sortBySelector != "")
                                        $sort_link = "&sort_by=" . $this->sortBySelector;
                                    // Si un raw / kpi de filtre du selecteur est d�fini, on inclut le tri dans le lien
                                    $filter_link = '';
                                    if ($this->filterSelector != "")
                                        $filter_link = "&filter=" . $this->filterSelector;
                                    $link = "javascript:open_window({$strEscape}'gtm_navigation.php?id_dash=" . $this->dashId . "" . urlencode($na_in_link) . "&ta=" . $this->ta . "&ta_value=" . $this->taValue . "&mode=" . $this->mode . "&top=" . $this->getTop() . "" . $sort_link . "" . $filter_link . "&id_menu_encours=" . $this->idMenu . "{$strEscape}')";
                                }
                                else {
                                    $link = "index.php?id_dash=" . $this->dashId . "" . $na_in_link . "&ta=" . $this->ta . "&ta_value=" . $this->taValue . "&mode=" . $mode_link . "&top=" . $this->getTop() . "&id_menu_encours=" . $this->idMenu;
                                    // Si un raw / kpi de tri du selecteur est d�fini, on inclut le tri dans le lien
                                    if ($this->sortBySelector != "")
                                        $link .= "&sort_by=" . $this->sortBySelector;
                                    // Si un raw / kpi de filtre du selecteur est d�fini, on inclut le tri dans le lien
                                    if ($this->filterSelector != "")
                                        $link .= "&filter=" . $this->filterSelector;
                                }

                                // Stockage du lien dans le tableau de r�sultats
                                $results[$gtm_id][$idx]['link'][$j] = $link;
                            }

                            // Si une / plusieurs bh existent, on d�finit leurs valeurs
                            if (count($this->bh) > 0) {
                                $results[$gtm_id][$idx]['bh_data'][$j] = $this->bhData[$ne[$j]][$this->taValue];
                            }

                            // 15:19 26/05/2009 GHX
                            // Si les liens vers AA sont activ�s
                            if ($this->enabledLinkToAA) {
                                // 16:55 23/10/2009 GHX
                                // Ajout de condition au cas ou l'�l�ment n'a pas de donn�es
                                if (array_key_exists($ne[$j], $this->linkToAA[$gtm_id])) {
                                    if (array_key_exists($this->taValue, $this->linkToAA[$gtm_id][$ne[$j]])) {

                                        // On boucle sur toutes les donn�es du graphes pour �valuer les variables des liens vers AA
                                        foreach ($this->linkToAA[$gtm_id][$ne[$j]][$this->taValue] as $typeAA => $subArrayAA) {
                                            foreach ($subArrayAA as $idProductAA => $idSubArrayAA) {
                                                foreach ($idSubArrayAA as $idAA => $valueAA) {
                                                    if (strpos($ne[$j], self::SEPARATOR) === false) {
                                                        $neAxe1AA = $ne[$j];
                                                        $neAxe3AA = '';
                                                    } else {
                                                        $neTmp = explode(self::SEPARATOR, $ne[$j]);
                                                        // maj 10/11/2009 MPR
                                                        // D�but Correction du BZ12533 : Le lien vers AA est KO
                                                        $neAxe1AA = $neTmp[0];
                                                        // Fin Correction du BZ12533
                                                        $neAxe3AA = $neTmp[1];
                                                    }

                                                    eval("\$this->linkToAA['$gtm_id']['$ne[$j]']['" . $this->taValue . "']['$typeAA'][$idProductAA]['$idAA'] = \"$valueAA\";");
                                                }
                                            }
                                        }
                                    }
                                    $results[$gtm_id][$idx]['link_aa'][$j] = $this->linkToAA[$gtm_id][$ne[$j]][$this->taValue];
                                }
                            }
                            //CB 5.3.1 : Link to Nova Explorer
                            // Si les liens vers NE sont activ�s
                            if ($this->enabledLinkToNE) {
                                // 16:55 23/10/2009 GHX
                                // Ajout de condition au cas ou l'�l�ment n'a pas de donn�es
                                if (array_key_exists($ne[$j], $this->linkToNE[$gtm_id])) {
                                    if (array_key_exists($this->taValue, $this->linkToNE[$gtm_id][$ne[$j]])) {

                                        // On boucle sur toutes les donn�es du graphes pour �valuer les variables des liens vers NE
                                        foreach ($this->linkToNE[$gtm_id][$ne[$j]][$this->taValue] as $typeNE => $subArrayNE) {
                                            foreach ($subArrayNE as $idProductNE => $idSubArrayNE) {
                                                foreach ($idSubArrayNE as $idNE => $valueNE) {
                                                    if (strpos($ne[$j], self::SEPARATOR) === false) {
                                                        $neAxe1NE = $ne[$j];
                                                        $neAxe3NE = '';
                                                    } else {
                                                        $neTmp = explode(self::SEPARATOR, $ne[$j]);
                                                        // maj 10/11/2009 MPR
                                                        // D�but Correction du BZ12533 : Le lien vers NE est KO
                                                        $neAxe1NE = $neTmp[0];
                                                        // Fin Correction du BZ12533
                                                        $neAxe3NE = $neTmp[1];
                                                    }

                                                    eval("\$this->linkToNE['$gtm_id']['$ne[$j]']['" . $this->taValue . "']['$typeNE'][$idProductNE]['$idNE'] = \"$valueNE\";");
                                                }
                                            }
                                        }
                                    }
                                    $results[$gtm_id][$idx]['link_ne'][$j] = $this->linkToNE[$gtm_id][$ne[$j]][$this->taValue];
                                }
                            }
                        }
                        $idx += 1;
                    }
                }
            } else if ($this->mode == "overtime" && $values_exist == true) { // Mode overtime
                // En mode overtime on a toujours un seul sous-tableau par GTM (l'index ne sert que pour le "zoomplus" qui est une particularit� du mode overnetwork)

                $gtm_content = $gtm_values[0];

                // On affiche la valeur de la na d'axe N dans le titre uniquement si il existe un seul axe N d�fini pour le produit

                $na_axeN = $this->getNaAxeN($gtm_id);

                $show_axeN_title = ((count($na_axeN) == 1) ? true : false);

                // maj 18/11/2009 MPR :  R�cup�ration des �l�ments r�seau avec / sans 3�me axe pour Extrapolation des donn�es
                $_ne = array_values($this->neAxe1);

                // On boucle sur l'ensemble des valeurs des GTMs
                $idx = 0;
                foreach ($gtm_content as $ne => $elt_values) {
                    // D�finition du nom du GTM (sert pour d�finir le xml et le fichier image)
                    $gtm_name = md5(uniqid(rand(), true));

                    $results[$gtm_id][$idx]['name'] = $gtm_name;
                    // Sing Kpi
                    //r�cup�ration des cl�s de tous les tableaux imbriqu�s
                    $tab_key = $this->multiarray_keys($elt_values);

                    // 15:13 26/05/2009 GHX
                    // D�finition de variables pour les liens vers AA
                    $naAxe1AA = '';
                    $neAxe1AA = '';
                    $naAxe3AA = '';
                    $neAxe3AA = '';
                    //CB 5.3.1 : Link to Nova Explorer
                    // D�finition de variables pour les liens vers NE
                    $naAxe1NE = '';
                    $neAxe1NE = '';
                    $naAxe3NE = '';
                    $neAxe3NE = '';

                    // D�finition du titre du GTM

                    if ($show_axeN_title) {
                        // Une seule valeur de 3�me axe est sp�cifi�e : sa valeur n'est pas pr�sente dans la ne

                        $ne_axeN = "";

                        // 16:46 14/05/2009 GHX
                        // Normalement on ne rentre plus dans c'est condition (� v�rifier)
                        // car maintenant $ne contient toujours les
                        //quand on est en SingleKPI, $ne est diff�rent, donc on rentre dans ce if
                        if (strpos($ne, self::SEPARATOR) === false) {
                            $naAxe1AA = $this->naAxe1;
                            $neAxe1AA = $ne;
                            //CB 5.3.1 : Link to Nova Explorer
                            $naAxe1NE = $this->naAxe1;
                            $neAxe1NE = $ne;
                            // Single Kpi
                            if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                //tests pour savoir si dans le singleKPI, on a � faire � un RAW ou un KPI
                                // 17/11/2011 BBX
                                // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                if (in_array("raw", $tab_key, 1)) {
                                    //r�cup�ration du label : c'est dans la variable $this->singleKPI_rawIDque l'information id_ligne est r�cup�r�e
                                    //$raw = new RawModel();
                                    //$label = $raw->getNameFromId($this->singleKPI_rawID,$db);
                                    //
									$label = $this->recupKpiRawLabel('raw', $this->singleKPI_rawID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                    $na_title = "<text><![CDATA[RAW = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                    // 17/11/2011 BBX
                                    // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                } else if (in_array("kpi", $tab_key, 1)) {
                                    // 28/11/2011 BBX
                                    // BZ 24702 : on rajoute le gtm id comme index du tableau pour r�cup�rer le KPI
                                    $label = $this->recupKpiRawLabel('kpi', $this->singleKPI_kpiID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                    $na_title = "<text><![CDATA[KPI = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                }
                            } else {
                                $na_title = "<text><![CDATA[" . $this->naLabel[$this->naAxe1] . " = ]]></text><text " . $text_color . "><![CDATA[" . $this->neAxe1Label[$this->naAbcisseAxe1][$ne] . "]]></text>";
                            }
                        } else { // Plusieurs ne 3�me axe existe et sont pr�sentes dans la ne provenant de '$gtm_content'
                            $ne_label = explode(self::SEPARATOR, $ne);

                            $naAxe1AA = $this->naAxe1;
                            $neAxe1AA = $ne_label[0];
                            $naAxe3AA = $na_axeN[0];
                            $neAxe3AA = $ne_label[1];

                            //CB 5.3.1 : Link to Nova Explorer
                            $naAxe1NE = $this->naAxe1;
                            $neAxe1NE = $ne_label[0];
                            $naAxe3NE = $na_axeN[0];
                            $neAxe3NE = $ne_label[1];

                            if ($this->noDisplayThirdAxis) {
                                // Single Kpi
                                if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                    //tests pour savoir si dans le singleKPI, on a � faire � un RAW ou un KPI
                                    // 17/11/2011 BBX
                                    // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                    if (in_array("raw", $tab_key, 1)) {
                                        // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                        $label = $this->recupKpiRawLabel('raw', $this->singleKPI_rawID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                        $na_title = "<text><![CDATA[RAW = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                        // 17/11/2011 BBX
                                        // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                    } else if (in_array("kpi", $tab_key, 1)) {
                                        // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                        $label = $this->recupKpiRawLabel('kpi', $this->singleKPI_kpiID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                        $na_title = "<text><![CDATA[KPI = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                    }
                                } else {
                                    $na_title = "<text><![CDATA[" . $this->naLabel[$this->naAxe1] . " = ]]></text><text " . $text_color . "><![CDATA[" . $this->neAxe1Label[$this->naAbcisseAxe1][$ne_label[0]] . "]]></text>";
                                }
                            } else {
                                // Single Kpi
                                if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                    //tests pour savoir si dans le singleKPI, on a � faire � un RAW ou un KPI
                                    // 17/11/2011 BBX
                                    // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                    if (in_array("raw", $tab_key, 1)) {
                                        // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                        $label = $this->recupKpiRawLabel('raw', $this->singleKPI_rawID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                        $na_title = "<text><![CDATA[RAW = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                        // 17/11/2011 BBX
                                        // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                    } else if (in_array("kpi", $tab_key, 1)) {
                                        // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                        $label = $this->recupKpiRawLabel('kpi', $this->singleKPI_kpiID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                        $na_title = "<text><![CDATA[KPI = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                    }
                                } else {
                                    $na_title = "<text><![CDATA[" . $this->naLabel[$this->naAxe1] . " = ]]></text><text " . $text_color . "><![CDATA[" . $this->neAxe1Label[$this->naAbcisseAxe1][$ne_label[0]] . "]]></text><text><![CDATA[ , " . $this->naLabel[$na_axeN[0]] . " = ]]></text><text " . $text_color . "><![CDATA[" . $this->neAxeNLabel[$ne_label[1]] . "]]></text>";
                                }
                            }
                        }
                    } else {
                        if (strpos($ne, self::SEPARATOR) === false) {
                            $naAxe1AA = $this->naAxe1;
                            $neAxe1AA = $ne;
                            //CB 5.3.1 : Link to Nova Explorer
                            $naAxe1NE = $this->naAxe1;
                            $neAxe1NE = $ne;
                            // Single Kpi
                            if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                //tests pour savoir si dans le singleKPI, on a � faire � un RAW ou un KPI
                                // 17/11/2011 BBX
                                // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                if (in_array("raw", $tab_key, 1)) {
                                    // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                    $label = $this->recupKpiRawLabel('raw', $this->singleKPI_rawID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                    $na_title = "<text><![CDATA[RAW = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                    // 17/11/2011 BBX
                                    // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                } else if (in_array("kpi", $tab_key, 1)) {
                                    // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                    $label = $this->recupKpiRawLabel('kpi', $this->singleKPI_kpiID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                    $na_title = "<text><![CDATA[KPI = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                }
                            } else {
                                $na_title = "<text><![CDATA[" . $this->naLabel[$this->naAxe1] . " = ]]></text><text " . $text_color . "><![CDATA[" . $this->neAxe1Label[$this->naAbcisseAxe1][$ne] . "]]></text>";
                            }
                        } else {
                            $ne_label = explode(self::SEPARATOR, $ne);

                            $naAxe1AA = $this->naAxe1;
                            $neAxe1AA = $ne_label[0];
                            $naAxe3AA = $na_axeN[0];
                            $neAxe3AA = $ne_label[1];
                            //CB 5.3.1 : Link to Nova Explorer
                            $naAxe1NE = $this->naAxe1;
                            $neAxe1NE = $ne_label[0];
                            $naAxe3NE = $na_axeN[0];
                            $neAxe3NE = $ne_label[1];

                            // Single Kpi
                            if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                //tests pour savoir si dans le singleKPI, on a � faire � un RAW ou un KPI
                                // 17/11/2011 BBX
                                // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                if (in_array("raw", $tab_key, 1)) {
                                    // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                    $label = $this->recupKpiRawLabel('raw', $this->singleKPI_rawID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                    $na_title = "<text><![CDATA[RAW = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                    // 17/11/2011 BBX
                                    // BZ 24702 : utilisation du mode strict pour la fonction in_array
                                } else if (in_array("kpi", $tab_key, 1)) {
                                    // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                    $label = $this->recupKpiRawLabel('kpi', $this->singleKPI_kpiID[$gtm_id], $this->gtmProducts[$gtm_id]);
                                    $na_title = "<text><![CDATA[KPI = ]]></text><text " . $text_color . "><![CDATA[$label]]></text>";
                                }
                            } else {

                                $na_title = "<text><![CDATA[" . $this->naLabel[$this->naAxe1] . " = ]]></text><text " . $text_color . "><![CDATA[" . $this->neAxe1Label[$this->naAbcisseAxe1][$ne_label[0]] . "]]></text>";
                            }
                        }
                    }

                    $results[$gtm_id][$idx]['title'] = $title_ta_part . $na_title;

                    // D�finition des valeurs de l'axe des abcisses. Pour le mode overtime, il s'agit des valeurs de ta comprise entre la ta value d'origine et la ta value + la p�riode

                    $ta_values = getTAInterval($this->ta, $this->taValue, $this->period);

                    // Note : mettre comme cl� de tableau le label de la ta

                    $results[$gtm_id][$idx]['xaxis'] = array('title' => $ta_label, 'values' => (array_map(array($this, 'labelizeTaValue'), $ta_values)));

                    // D�finition des informations de la barre du GTM
                    // 17/02/2009 - Modif. benoit : on d�finit un tableau des infos na � transmettre � la m�thode de d�finition de la barre du GTM
                    // 22/11/2011 BBX
                    // BZ 24764 : correction des notices php
                    $ne_axe1_tmp = $ne;
                    $ne_axeN_tmp = isset($ne_axeN) ? $ne_axeN : null;

                    if (count($ne_axeN_tmp) == 1 && $ne_axeN == "") {
                        $all_ne = explode(self::SEPARATOR, $ne);

                        $ne_axe1_tmp = $all_ne[0];
                        $ne_axeN_tmp = $all_ne[1];
                    }

                    // 10:13 17/09/2009 GHX
                    // Correction du BZ 11465
                    // Ajout de l'urlencode sur le NE
                    // 07/12/2009 BBX : modification de la condition sur la na_axeN. BZ 12226
                    // Single Kpi
                    if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                        // 29/11/2010 BBX
                        // On prend soin de bien r�cup�rer des �l�ments r�seau
                        // dans le cas du Single KPI
                        // BZ 16967
                        $ne_axe1_tmp = $this->neAxe1[$this->naAxe1];
                        if (is_array($ne_axe1_tmp))
                            $ne_axe1_tmp = implode('||', $ne_axe1_tmp);
                        // Infos GIS
                        $na_infos = array('na_axe1' => $this->naAxe1, 'ne_axe1' => urlencode($ne_axe1_tmp), 'na_axeN' => $na_axeN[0], 'ne_axeN' => urlencode($ne_axeN_tmp), 'ne' => $ne);
                    }
                    else {
                        // Infos GIS
                        $na_infos = array('na_axe1' => $this->naAxe1, 'ne_axe1' => urlencode($ne_axe1_tmp), 'na_axeN' => (($ne_axeN_tmp != '') ? $na_axeN[0] : ''), 'ne_axeN' => $ne_axeN_tmp, 'ne' => $ne);
                    }
                    if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                        $this->ne_ri = $_ne[0];
                    }

                    $results[$gtm_id][$idx]['properties'] = $this->setGTMBarProperties($gtm_id, $na_infos, $this->taValue, $gtm_name);

                    // D�finition des valeurs des �l�ments
                    for ($i = 0; $i < count($ta_values); $i++) {
                        // Donn�es � afficher en abcisse (ta_values = les dates en Overtime)
                        // 22/11/2011 BBX
                        // BZ 24764 : correction des notices php
                        $results[$gtm_id][$idx]['data'][$i] = isset($elt_values[$ta_values[$i]]) ? $elt_values[$ta_values[$i]] : null;

                        // Contenu du lien
                        // 10/02/2009 - Modif. benoit : red�fintion de la partie na_axe1 / na_axeN des liens
                        // 16/02/2009 - Modif. benoit : on ne d�finit les liens que si la navigation est active

                        if ($this->navigation == true) {
                            $na_in_link = "&na_axe1=" . $this->naAbcisseAxe1;

                            // S'il n'existe qu'un seul axe N (cad que les valeurs de ne axe N sont affich�es) on s�pare les valeurs axe1/axeN
                            // Note : on consid�re qu'il n'existe qu'un seul axe N  : l'axe 3

                            if (count($na_axeN) == 1) {
                                if ($ne_axeN == "") {
                                    $all_ne = explode(self::SEPARATOR, $ne);
                                    // 10:13 17/09/2009 GHX
                                    // Correction du BZ 11465
                                    // Ajout de l'urlencode sur le NE
                                    // Single Kpi
                                    if ($this->gtmTypes[$gtm_id] == "singleKPI") {

                                        //pour les link des graphes singleKPI, l'information n'est pas pr�sente dans all_ne
                                        //il faut donc aller la chercher ailleurs.
                                        $tab_axe1_keys = array_keys($this->neAxe1Label[$this->naAbcisseAxe1]);
                                        $tab_axeN_keys = array_keys($this->neAxeNLabel);

                                        // 06/06/2011 MMT DE 3rd Axis gestion du nouveau format de selection des elements 3eme axe
                                        $na_in_link .= "&ne_axe1=" . $this->naAbcisseAxe1 . "||" . $tab_axe1_keys[$idx] . "&na_axeN=" . $na_axeN[0] . "&ne_axeN=" . $na_axeN[0] . "||" . $tab_axeN_keys[$idx];
                                    } else {
                                        // 06/06/2011 MMT DE 3rd Axis gestion du nouveau format de selection des elements 3eme axe
                                        $na_in_link .= "&ne_axe1=" . $this->naAbcisseAxe1 . "||" . urlencode($all_ne[0]) . "&na_axeN=" . $na_axeN[0] . "&ne_axeN=" . $na_axeN[0] . "||" . urlencode($all_ne[1]);
                                    }
                                } else {
                                    // 10:13 17/09/2009 GHX
                                    // Correction du BZ 11465
                                    // Ajout de l'urlencode sur le NE
                                    // 06/06/2011 MMT DE 3rd Axis gestion du nouveau format de selection des elements 3eme axe
                                    $na_in_link .= "&ne_axe1=" . $this->naAbcisseAxe1 . "||" . urlencode($ne) . "&na_axeN=" . $na_axeN[0] . "&ne_axeN=" . $na_axeN[0] . "||" . urlencode($ne_axeN);
                                }
                            } else { //axe 2
                                // Single Kpi
                                $tab_axe1_keys = array_keys($this->neAxe1Label[$this->naAbcisseAxe1]);

                                //faire un count sur les TA_Values pour connaitre le nb d'heure
                                //puis garder le 0 pour les count(ta_values) premiers
                                //prendre le 1 pour les count(ta_values) suivants
                                //idem pour le 2
                                //sachant qu'on fait �a en fonction du nb de ne s�lectionn� dans le selecteur (3 pour l'exemple).
                                if ($this->gtmTypes[$gtm_id] == "singleKPI") {

                                    $na_in_link .= "&ne_axe1=" . $this->naAbcisseAxe1 . "||" . urlencode($tab_axe1_keys[$idx]);
                                } else {
                                    // 10:13 17/09/2009 GHX
                                    // Correction du BZ 11465
                                    // Ajout de l'urlencode sur le NE
                                    $na_in_link .= "&ne_axe1=" . $this->naAbcisseAxe1 . "||" . urlencode($ne);
                                }
                            }

                            // Si un raw / kpi de tri du selecteur est d�fini, on inclut le tri dans le lien

                            $sort_link = (($this->sortBySelector != "") ? "&sort_by=" . $this->sortBySelector : "");

                            // Si un raw / kpi de filtre du selecteur est d�fini, on inclut le tri dans le lien

                            $filter_link = (($this->filterSelector != "") ? "&filter=" . $this->filterSelector : "");

                            if ($this->ta == $this->taMin) {
                                // 16:51 29/07/2009 GHX
                                // Correction d'un bug quand on est sur le niveau Hour et qu'on clique sur une donn�e d'un graphe on restait toujours en OT
                                // 10:13 17/09/2009 GHX
                                // Correction du BZ 11465
                                // Ajout de l'urlencode sur le NE
                                // 17:04 06/11/2009 GHX
                                // Correction du BZ 12631
                                // Suppression de "javascript:navigate"
                                // 18/11/2009 GHX
                                // BZ 12893
                                // Suppression de l'urlencode
                                $link = "index.php?id_dash=" . $this->dashId . "" . $na_in_link . "&ta=" . $this->ta . "&ta_value=" . $ta_values[$i] . "&mode=overnetwork&top=" . $this->getTop() . "" . $sort_link . "" . $filter_link . "&id_menu_encours=" . $this->idMenu . "";

                                // $link = "index.php?id_dash=".$this->dashId."".urlencode($na_in_link)."&ta=".$this->ta."&ta_value=".$ta_values[$i]."&mode=overnetwork&top=".$this->getTop()."".$sort_link."".$filter_link."&id_menu_encours=".$this->idMenu."";
                            } else {
                                // 14:53 07/05/2009 GHX
                                // Ajout d'un �chappement des simples cotes sinon plantage JS dans le cas d'un PIE
                                $strEscape = '';
                                if (eregi("pie", $this->getGTMType($gtm_id)))
                                    $strEscape = '\\';

                                // 10:13 17/09/2009 GHX
                                // Correction du BZ 11465
                                // Ajout de l'urlencode sur le NE
                                $link = "javascript:open_window({$strEscape}'gtm_navigation.php?id_dash=" . $this->dashId . "" . urlencode($na_in_link) . "&ta=" . $this->ta . "&ta_value=" . $ta_values[$i] . "&mode=" . $this->mode . "&top=" . $this->getTop() . "" . $sort_link . "" . $filter_link . "&id_menu_encours=" . $this->idMenu . "{$strEscape}')";
                            }

                            $results[$gtm_id][$idx]['link'][$i] = $link;
                        }

                        // Si une / plusieurs bh existent, on d�finit leurs valeurs

                        if (count($this->bh) > 0) {
                            $results[$gtm_id][$idx]['bh_data'][$i] = $this->bhData[$ne][$ta_values[$i]];

                            // 11/04/2012 BBX
                            // BZ 26740 : on r�cup�re les infos BH, m�me en Single KPI
                            if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                $results[$gtm_id][$idx]['bh_data'][$i] = $this->bhData[$tab_axe1_keys[$idx]][$ta_values[$i]];
                            }
                        }

                        // 15:19 26/05/2009 GHX
                        // Si les liens vers AA sont activ�s
                        if ($this->enabledLinkToAA) {
                            // 17:58 29/10/2009 GHX
                            // Ajout de la condition suivante quand si on un pie dans le dashboard et qu'on n'a pas de donn�es, on a une erreur
                            if (array_key_exists($ne, $this->linkToAA[$gtm_id])) {
                                if (array_key_exists($ta_values[$i], $this->linkToAA[$gtm_id][$ne])) {
                                    foreach ($this->linkToAA[$gtm_id][$ne][$ta_values[$i]] as $typeAA => $subArrayAA) {
                                        foreach ($subArrayAA as $idProductAA => $idSubArrayAA) {
                                            foreach ($idSubArrayAA as $idAA => $valueAA) {

                                                eval("\$this->linkToAA['$gtm_id']['$ne']['" . $ta_values[$i] . "']['$typeAA'][$idProductAA]['$idAA'] = \"$valueAA\";");
                                            }
                                        }
                                    }
                                }
                            }
                            // Single KPI
                            if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                $results[$gtm_id][$idx]['link_aa'][$i] = $this->linkToAA[$gtm_id];
                            } else {
                                // 22/11/2011 BBX
                                // BZ 24764 : correction des notices php
                                $results[$gtm_id][$idx]['link_aa'][$i] = isset($this->linkToAA[$gtm_id][$ne][$ta_values[$i]]) ? $this->linkToAA[$gtm_id][$ne][$ta_values[$i]] : null;
                            }
                        }
                        //CB 5.3.1 : Link to Nova Explorer
                        // Si les liens vers NE sont activ�s
                        if ($this->enabledLinkToNE) {
                            // 17:58 29/10/2009 GHX
                            // Ajout de la condition suivante quand si on un pie dans le dashboard et qu'on n'a pas de donn�es, on a une erreur
                            if (array_key_exists($ne, $this->linkToNE[$gtm_id])) {
                                if (array_key_exists($ta_values[$i], $this->linkToNE[$gtm_id][$ne])) {
                                    foreach ($this->linkToNE[$gtm_id][$ne][$ta_values[$i]] as $typeNE => $subArrayNE) {
                                        foreach ($subArrayNE as $idProductNE => $idSubArrayNE) {
                                            foreach ($idSubArrayNE as $idNE => $valueNE) {

                                                eval("\$this->linkToNE['$gtm_id']['$ne']['" . $ta_values[$i] . "']['$typeNE'][$idProductNE]['$idNE'] = \"$valueNE\";");
                                            }
                                        }
                                    }
                                }
                            }
                            // Single KPI
                            if ($this->gtmTypes[$gtm_id] == "singleKPI") {
                                $results[$gtm_id][$idx]['link_ne'][$i] = $this->linkToNE[$gtm_id];
                            } else {
                                // 22/11/2011 BBX
                                // BZ 24764 : correction des notices php
                                $results[$gtm_id][$idx]['link_ne'][$i] = isset($this->linkToNE[$gtm_id][$ne][$ta_values[$i]]) ? $this->linkToNE[$gtm_id][$ne][$ta_values[$i]] : null;
                            }
                        }
                    }

                    // 10:04 14/05/2009 GHX
                    // Si c'est un PIE on v�rifie que les donn�es du split by ne sont pas tous null ou z�ro (sinon impossible faire un joli camembert)
                    // dans ce cas on affichera un message d'erreur comme quoi il n'y a pas de donn�es sur le split by
                    if ((!(strpos($this->gtmTypes[$gtm_id], "pie") === false))) {
                        // 14:56 11/05/2009 GHX
                        // Message d'erreur no data found pour les PIE
                        $splitBy = $this->getSplitBy($gtm_id);

                        $pieIsOk = false;
                        foreach ($results[$gtm_id][$idx]['data'] as $data) {
                            if (!empty($data[$splitBy['type']][$splitBy['product']][$splitBy['id']]) && $data[$splitBy['type']][$splitBy['product']][$splitBy['id']] > 0) {
                                $pieIsOk = true;
                            }
                        }

                        if (!$pieIsOk) {
                            $labelSplitBy = $this->naLabel[$this->naAbcisseAxe1];

                            if ($splitBy['split_type'] == 'third_axis' && count($this->neAxe1Init[$this->naAxe1]) == 1 && $this->mode == 'overnetwork') {
                                $labelSplitBy = $na_axeN[0];
                            }

                            $na_axeN_title = "";
                            $na_axeN = $this->getNaAxeN($gtm_id);
                            if (count($na_axeN) == 1) {
                                $gtm_pdt = $this->gtmProducts[$gtm_id];
                                $ne_axeN = "ALL";

                                // Note : m�thode de s�lection de la valeur de la na d'axe N � revoir
                                for ($j = 0; $j < count($gtm_pdt); $j++) {
                                    if ($this->checkNeAxeNALL($gtm_pdt[$j]) == false) {
                                        if (count($this->neAxeN[$gtm_pdt[$j]]) > 0) {
                                            foreach ($this->neAxeN[$gtm_pdt[$j]] as $axeN_fam => $axeN_value) {
                                                $ne_axeN = $axeN_value[$na_axeN[0]][0];
                                            }
                                        }
                                    }
                                }

                                $ne_axeN_label = (($ne_axeN != "ALL") ? getNELabel($na_axeN, $ne_axeN) : $ne_axeN);
                                $na_axeN_title .= " - " . $this->naLabel[$na_axeN[0]] . " = <b>" . $ne_axeN_label . "</b>";
                            }

                            $sort_by = ((count($this->sortBy) == 0) ? $this->sortByGTM[$gtm_id] : $this->sortBy);

                            if ($show_axeN_title) {
                                // On d�finit le titre
                                $na_axe1_title = $this->naLabel[$this->naAxe1] . " = <b>" . $this->neAxe1Label[$this->naAbcisseAxe1][$ne_label[0]] . "</b>";
                                $na_axeN_title = " - " . $this->naLabel[$na_axeN[0]] . " = <b>" . $this->neAxeNLabel[$ne_label[1]] . "</b>";
                            } else {
                                $na_axe1_title = $this->naLabel[$this->naAxe1] . " = <b>" . $this->neAxe1Label[$this->naAbcisseAxe1][$ne] . "</b>";
                            }

                            $results[$gtm_id][$idx]['msg'] = __T('U_GTM_NO_DATA_FOUND_SPLIT_BY_OT', $this->getGTMName($gtm_id), $ta_label, getTaValueToDisplay($this->ta, $this->taValue, "/"), $na_axe1_title, $na_axeN_title, $sort_by['label'], ucfirst(strtolower($sort_by['asc_desc'])), $splitBy['label'], $splitBy['label'], $this->ta
                            );

                            // Les autres infos du GTM sont d�finies comme vides
                            $results[$gtm_id][$idx]['xaxis'] = array();
                            $results[$gtm_id][$idx]['data'] = array();
                            $results[$gtm_id][$idx]['link'] = array();
                        }
                    }

                    $results[$gtm_id][$idx]['ne'] = $_ne[0];
                    $idx++;
                }

                // maj 18/11/2009 - MPR : R�cup�ration des �l�ments r�seau/3�me axe du dashboard
            }
        }

        return $results;
    }

    /**
     * Permet de d�finir les propri�t�s de la barre de titre d'un GTM
     *
     * 13/03/2009 - modif SPS : changement du lien pour l'export Excel
     *
     * @param int $gtm_id identifiant du GTM
     * @param array $na liste d'informations sur les na axe1 et axe3
     * @param int $ta_value valeur de la ta
     * @param string $gtm_filename chemin du fichier image du GTM
     * @param boolean $data_exist des donn�es existent dans le GTM ou non
     * @return array liste des propri�t�s de la barre de titres du GTM
     */
    function setGTMBarProperties($gtm_id, $na, $ta_value, $gtm_filename, $data_exist = true) {
        $gtm_pptes = array();

        // Infos n�cessaires : id_dash, ta, ta_value, na_axe1, na_axe1_abcisse, ne, url_img_gtm, id_gtm, id_user, dash_name, gtm_name, gtm_type,
        // last_comment, parser_name, raw_kpi_gis, table_name_raw_kpi_gis, id_menu_encours, creator, scenario, mode, zoomplus, offset, ri
        // Propri�t�s du dashboard

        $dash_id = $this->dashId;
        $dash_name = $this->getDashName();
        $mode = $this->mode;

        // Propri�t�s du GTM

        $gtm_type = $this->getGTMType($gtm_id);
        $gtm_name = $this->getGTMName($gtm_id);
        $gtm_gis = $this->getGTMGisInfos($gtm_id);
        $zoom_plus = $this->zoomPlus;
        $offset = $this->getTop();
        $ri = $this->ri;

        // Propri�t�s du selecteur

        $ta = $this->ta;
        $ta_value = $this->taValue;
        $na_axe1 = $this->naAxe1;
        $na_abcisse_axe1 = $this->naAbcisseAxe1;


        $na_axe1_label = (($this->naLabel[$na['na_axe1']] == "") ? $na['na_axe1'] : $this->naLabel[$na['na_axe1']]);
        $ne_axe1_label = (($this->neAxe1Label[$na['na_axe1']][$na['ne_axe1']] == "") ? $na['ne_axe1'] : $this->neAxe1Label[$na['na_axe1']][$na['ne_axe1']]);

        // D�finition de l'index d�finissant le div qui va contenir le GTM

        $gtm_pptes['index'] = array('gtm_id' => $gtm_id, 'gtm_name' => $gtm_name, 'ne' => $na['ne_axe1'] . (($na['ne_axeN'] != "" ) ? "_" . $na['ne_axeN'] : ""), 'ne_label' => $ne_axe1_label . (($na['ne_axeN'] != "" ) ? " " . $this->neAxeNLabel[$na['ne_axeN']] : ""));

        // D�finition du titre

        $gtm_pptes['title'] = array('dash' => $dash_name . " " . ucfirst($mode), 'gtm' => $gtm_name);

        // D�finition des boutons

        $gtm_buttons = array();

        // Bouton Excel

        /* 13/03/2009 - modif SPS : changement du lien pour l'export Excel */
        $gtm_buttons['excel'] = array('link' => NIVEAU_0 . "dashboard_display/export/export_excel_from_graph.php?id_graph=" . $gtm_filename, 'img' => NIVEAU_0 . "images/graph/btn_excel.gif", 'msg' => __T('U_TOOLTIP_CADDY_EXCEL_EXPORT'));

        // Bouton Infos
        // 04/05/11 OJT : Ajout de la TA pour affichage des infos BH
        $gtm_buttons['infos'] = array('link' => "open_window('" . NIVEAU_0 . "/dashboard_display/gtm_info.php?id_gtm=" . $gtm_id . "&ta={$ta}','comment','yes','yes',950,500);return false;", 'img' => NIVEAU_0 . "images/graph/btn_info.gif", 'msg' => __T('U_DATA_INFORMATION_FIEDSET_DATA_INFORMATION'));

        // Bouton Caddy
        // Note : d�finir le titre du GTM dans 'sys_definition_messages_display'
        // Single Kpi
        if ($this->gtmTypes[$gtm_id] == "singleKPI") {
            $gtm_title = $gtm_name . " on (" . utf8_encode($ne_axe1_label) . ") [KPI] - " . getTaValueToDisplay($ta, $ta_value, "-") . " [" . getTaLabel($ta) . "]";
        } else {
            $gtm_title = $gtm_name . " on (" . utf8_encode($ne_axe1_label) . ") [" . $na_axe1_label . "] - " . getTaValueToDisplay($ta, $ta_value, "-") . " [" . getTaLabel($ta) . "]";
        }
        // Note : d�finir une m�thode d�finissant le dernier commentaire d'un GTM
        // maj 03/08/2010 - MPR : Correction du BZ 16967
        //      -> Le type du graphe envoy� doit �tre "graph" et non "singleKPI"
        $type = ($this->getGTMType($gtm_id) == 'singleKPI') ? "graph" : $this->getGTMType($gtm_id);

        // 22/11/2011 BBX
        // BZ 24764 : correction des notices php
        $caddy_params = array(NIVEAU_0, $_SESSION['id_user'], urlencode($dash_name), $type, urlencode($gtm_title), $gtm_filename . ".png", $gtm_filename, urlencode(isset($last_comment) ? $last_comment : ''));

        // Note : ajouter le message "Add to the caddy" dans 'sys_definition_messages_display'
        // 20/09/2010 BBX BZ 11945: On ajoute un id sur le bouton du caddie pour pouvoir le modifier
        $gtm_buttons['caddy'] = array('link' => "caddy_update(" . implode(",", array_map(array($this, 'labelizeValue'), $caddy_params)) . ")", 'img' => NIVEAU_0 . "images/graph/btn_cart.gif", 'msg' => "Add to the cart", 'id' => "cart_btn_$gtm_filename");

        // On n'affiche les boutons GIS et GIS 3D ssi le GIS est activ� pour le dashboard
        // maj 10/06/2011 - MPR : DEV GIS WITHOUT POLYGONS
        // On prend en compte le param�tre gis_display_mode
        // Utilisation de la m�thode linksToGisAvailable du model GisModel
        if (count($gtm_gis) > 0 && GisModel::linksToGisAvailable("dash", $na_axe1, $gtm_gis['product'])) {
            // Bouton GIS
            $module = get_sys_global_parameters('module', null, $gtm_gis['product']);

            // D�finition du nom de la table de donn�es
            $group_axe = $this->GetGtmElementsGroupAndAxe($gtm_gis['product'], $gtm_gis['family']);
            $table_data = $this->setDataTableName($group_axe['group_table'], $gtm_gis['type'], $gtm_gis['product'], $gtm_gis['family'], $group_axe['axeN']);

            // 17:47 14/05/2009 GHX
            // L'id du produit en mis au d�but au lieu de la fin du tableau
            // 09:54 15/05/2009 GHX
            // Ajout de plusieurs param�tres dans le tableau
            // maj 03/08/2010 - MPR : Correction du BZ 16967
            //      - S�lection des �l�ments r�seau multiple
            //      - 3 cas possibles :
            //          -> Cas 1 : Dashboard ONE Pas de s�lection => On passe ALL
            //          -> Cas 2 : Dashboard ONE ou OT Single KPI avec S�lection multiple => On passe la s�lection multiple
            //          -> Cas 3 : Dashboard OT les graphes contiennent un seul �l�ment r�seua => On passe cet �l�ment r�seau unique
            if ($na['ne_axe1'] == 'ALL') {
                // Cas 2 ou Cas 1
                $lst_ne_axe1 = (count($this->neAxe1Init[$na_axe1]) > 0 ) ? utf8_encode(implode("||", $this->neAxe1[$na_axe1])) : 'ALL';
            } else { // Cas 3
                $lst_ne_axe1 = $na['ne_axe1'];
            }

            // 16/02/2011 OJT : bz20622, gestion Gis3D pour NA diff�rent de NAmin
            $gis_data = array(
                $gtm_gis['product'],
                $na_axe1,
                $lst_ne_axe1,
                get_network_aggregation_min_from_family(get_main_family($gtm_gis['product']), $gtm_gis['product']),
                $type,
                '',
                $gtm_gis['family'],
                $ta,
                $ta_value,
                '',
                $module,
                '',
                $gtm_gis['type'],
                $gtm_gis['id'],
                $gtm_gis['label'],
                $gtm_gis['name'],
                $table_data
            );

            if (isset($this->naAxeN[$gtm_gis['product']][$gtm_gis['family']])) {
                $gis_data[] = $this->naAxeN[$gtm_gis['product']][$gtm_gis['family']][0];
                $gis_data[] = $na['ne_axeN'];
            }

            // 09/10/2012 ACS DE GIS 3D ONLY
            if ($gtm_gis['gis_mode'] == 1) {
                // maj 20/08/2009 - MPR : Correction du bug 11108 : On attribue le m�me nom � la pop-up du GIS que celui de GIS Supervision d�finit dans la table menu_deroulant_intranet
                $gtm_buttons['gis'] = array('link' => "ouvrir_fenetre('" . NIVEAU_0 . "gis/index.php?gis_data=" . implode(self::GIS_SEPARATOR, $gis_data) . "','MapView','yes','yes',800,400)", 'img' => NIVEAU_0 . "images/graph/btn_gis.gif", 'msg' => __T('U_TOOLTIP_DASH_ALARM_DISPLAY_GIS'));
            }

            if ($gtm_gis['gis_mode'] == 1 || $gtm_gis['gis_mode'] == 2) {
                // Bouton GIS 3D
                $gtm_buttons['gis_3d'] = array('link' => "ouvrir_fenetre('" . NIVEAU_0 . "gis/gis_scripts/export_file.php?gis_data=" . implode(self::GIS_SEPARATOR, $gis_data) . "','GoogleEarthView','yes','yes',400,110)", 'img' => NIVEAU_0 . "images/graph/btn_gis3d.gif", 'msg' => __T('U_TOOLTIP_DASH_ALARM_DISPLAY_GIS_3D'));
            }
        }

        // Bouton RI
        $id_menu_encours = '';
        $selecteur_scenario = '';

        // Note : mettre le message "Reliability Indicator" dans 'sys_definition_messages_display'

        $ne_ri = (is_array($na['ne']) ? implode(",", $na['ne']) : $na['ne']);
        // Single KPI
        if ($this->gtmTypes[$gtm_id] == "singleKPI" && $this->mode == "overtime") {
            if (isset($this->naAxeN) && isset($this->ne_ri)) {
                foreach ($this->ne_ri as $cle => $value_ne) {
                    $tab_value_ne = explode('|s|', $value_ne);
                    $tab[$tab_value_ne[0]] = $tab_value_ne[1];
                }
                if (count(array_unique($tab)) > 1) {
                    $na['ne_axeN'] = 'ALL';
                } else {
                    $na['ne_axeN'] = $tab_value_ne[1];
                }
            }
            //red�finition du $ne_ri car pas dans le m�me tableau
            $ne_ri = (is_array($this->ne_ri) ? implode(",", $this->ne_ri) : $this->ne_ri);
        }
        // 06/06/2011 MMT DE 3rd Axis gestion du nouveau format de selection des elements 3eme axe
        $gtm_buttons['ri'] = array('link' => "ouvrir_fenetre('" . NIVEAU_0 . "/dashboard_display/index_ri.php?id_menu_encours=" . $id_menu_encours . "&id_dash=" . $this->dashId . "&dash_name=" . $dash_name . "&mode=" . $this->mode . "&ri=1&na_axe1=" . $na_abcisse_axe1 . "&na_axeN=" . $na['na_axeN'] . "&ne=" . $ne_ri . "&ne_axeN=" . $na['na_axeN'] . "||" . $na['ne_axeN'] . "&ta_value=" . $ta_value . "&id_gtm=" . $gtm_id . "&xml_file=" . $gtm_filename . ".xml','RI','no','no',980,550)", 'img' => NIVEAU_0 . "images/graph/btn_ri.gif", 'msg' => "Reliability Indicator");

        // Bouton ZoomPlus

        if (($this->mode == "overnetwork") && ($this->zoomPlus === false)) {
            // Note : mettre le message "Display ALL Network Elements" dans 'sys_definition_messages_display'

            $gtm_buttons['zoom_plus'] = array('link' => "open_window('" . NIVEAU_0 . "/dashboard_display/index.php?id_menu_encours=" . $this->idMenu . "&id_dash=" . $this->dashId . "&mode=" . $this->mode . "&zoom_plus=1&id_gtm_zoomplus=" . $gtm_id . "&top=" . $this->getTop() . "&hide_header=1&no_selection=1&no_navigation=1','ZoomPlus','yes','yes',950,600)", 'img' => NIVEAU_0 . "images/graph/btn_zoomplus.gif", 'msg' => "Display ALL Network Elements");
        }

        /* 17/04/2009 - SPS : ajout du bouton pour ajouter une ligne sur les graphes
         * 30/04/2009 - SPS : modification du lien pour l'ouverture de la fenetre d'ajout de ligne
         * 04/05/2009 - SPS : test sur le type du graph (pie*) pour savoir si on affiche l'icone pour ajouter une ligne
         * */

        if (!eregi("pie", $this->getGTMType($gtm_id))) {
            $gtm_buttons['draw_line'] = array('link' => "showWindowDrawLine('" . $gtm_filename . "');", 'img' => NIVEAU_0 . "images/graph/chart_bar_edit.png", 'msg' => "Draw a line");
        }

        // Bouton Comments
        //$gtm_buttons['comment'] = array('link' => "", 'img' => NIVEAU_0."images/graph/.gif", 'msg' => __T(''));

        $gtm_pptes['buttons'] = (($data_exist) ? $gtm_buttons : array('infos' => $gtm_buttons['infos']));

        return $gtm_pptes;
    }

    private function getFamilies($raws, $kpis) {

        $families = array();

        if (count($raws) > 0) {
            foreach ($raws as $prod => $tab) {
                foreach ($tab as $family => $t) {
                    $families[$prod][] = $family;
                }
                $families[$prod] = array_unique($families[$prod]);
            }
        }

        if (count($kpis) > 0) {
            foreach ($kpis as $prod => $tab) {
                foreach ($tab as $family => $t) {
                    $families[$prod][] = $family;
                }
                $families[$prod] = array_unique($families[$prod]);
            }
        }

        return $families;
    }

// End function

    /**
     * Permet de r�cup�rer l'ensemble des valeurs des �l�ments d'un GTM
     *
     * @param array $gtm_products liste des produits auquels appartiennent les �l�ments du GTM
     * @param int $gtm_id identifiant du GTM
     * @param array $gtm_raws liste des raws du GTM
     * @param array $gtm_kpis liste des kpis du GTM
     * @param array $ne liste des ne s�lectionn�s (ne sert pas -> � supprimer)
     * @param int $offset valeur de l'offset (uniquement dans le cas du "zoom plus")
     * @return mixed tableau de valeurs des �l�ments du GTM ou chaine "No Data Found"
     */
    private function getGTMElements($gtm_products, $gtm_id, $gtm_raws, $gtm_kpis, $ne, $offset = '') {
        $gtm_elements = array();

        // 26/05/2009 GHX
        // Prise en compte de AA               
        if ($this->enabledLinkToAA) {
            $gtmModel = new GTMModel($gtm_id);
            $infoLinkToAA = $gtmModel->getInfoLinkToAA();
        }
        //CB 5.3.1 : Link to Nova Explorer
        if ($this->enabledLinkToNE) {
            $gtmModel = new GTMModel($gtm_id);
            $infoLinkToNE = $gtmModel->getInfoLinkToNE();
        }

        // 11:36 11/05/2009 GHX
        // Dans le cas d'un PIE on v�rifie que le splitBy poss�de bien des donn�es non nulles
        if (!(strpos($this->gtmTypes[$gtm_id], "pie") === false)) {
            $splitBy = $this->getSplitBy($gtm_id);
            ${'splitBy' . $splitBy['type']} = $splitBy['name'];
        }

        $splitByHasValues = false;
        $hasValues = false;

        // 1 - On boucle sur l'ensemble des produits du GTM
        for ($i = 0; $i < count($gtm_products); $i++) {

            // 2 - On s�lectionne les raws / kpis de chaque produit
            // 22/11/2011 BBX
            // BZ 24764 : correction des notices php
            $product_raws = isset($gtm_raws[$gtm_products[$i]]) ? $gtm_raws[$gtm_products[$i]] : null;
            $product_kpis = isset($gtm_kpis[$gtm_products[$i]]) ? $gtm_kpis[$gtm_products[$i]] : null;

            // 3 - D�finition des requetes par famille + type de donn�es et s�lection des raws / kpis � d�finir

            $queries = array();

            // Requetes raws + s�lection des raws
            if (count($product_raws) > 0) {
                foreach ($product_raws as $family => $raws) {
                    // D�finition et stockage des requetes par famille
                    $queries[$family]['raw'] = $this->setFamilyQuery(array_keys($raws), 'raw', $family, $gtm_products[$i], $gtm_id, $offset);
                }
            }

            // Requetes kpis + s�lection des kpis
            if (count($product_kpis) > 0) {
                foreach ($product_kpis as $family => $kpis) {
                    // D�finition et stockage des requetes par famille
                    $queries[$family]['kpi'] = $this->setFamilyQuery(array_keys($kpis), 'kpi', $family, $gtm_products[$i], $gtm_id, $offset);
                }
            }

            // 4.6 - Execution de la requete produit et stockage des r�sultats

            $db = Database::getConnection($gtm_products[$i]);
            $db->setDebug($this->debug);

            $time_start_foreach = microtime(true);
            foreach ($queries as $family => $query) {
                foreach ($query as $type => $sql) {
                    // 16:09 17/08/2009 GHX
                    // Il est possible que la requete soit vide dans le cas, on le sort est multiple (c�d plusieurs raw/kpi ayant le m�me code+legende dans le graphe)
                    if ($sql == '')
                        continue;

                    $row = $db->execute($sql);

                    $ne_gis = array();
                    while ($elem = $db->getQueryResults($row, 1)) {

                        $ta_value = (($this->mode == "overnetwork") ? $this->taValue : $elem[$this->ta]);

                        // 28/04/2009 GHX
                        // Reconversion du mapping dans le sens inverse de facon � avoir les valeurs affich�es dans les graphs
                        $elem['eor_id'] = $this->ReconvertEquivalentNeAxe1(array($elem['eor_id']), $this->naAbcisseAxe1, $gtm_products[$i]);

                        $elem['eor_id'] = $elem['eor_id'][0];

                        if ($ta_value != "") {
                            if ($type == 'raw') {
                                // Stockage dans le tableau des �l�ments du GTM des valeurs des raws

                                foreach ($product_raws[$family] as $raw_id => $raw_values) {
                                    $raw_key_pdt = $gtm_products[$i];
                                    $raw_key_id = $raw_values['id'];

                                    // 15:06 08/06/2009 GHX
                                    // V�rifie qu'on a des donn�es
                                    // 17:08 18/12/2009 GHX
                                    // Correction du BZ 12483
                                    // Modification de la fonction pour faire la distinction entre les valeurs null et 0
                                    if ($elem[strtolower($raw_id)] !== null) {
                                        $hasValues = true;
                                    }

                                    // 11:45 11/05/2009 GHX
                                    // Si le split a une valeur
                                    if (isset($splitByraw)) {
                                        // 11:58 21/07/2009 GHX
                                        // Modification de la condition (ajout de la deuxieme partie)
                                        if (!empty($elem[strtolower($raw_id)]) && $raw_id == $splitByraw) {
                                            $splitByHasValues = true;
                                        }
                                    }
                                    // Single KPI
                                    if ($this->gtmTypes[$gtm_id] == "singleKPI" && $this->mode == "overtime") {
                                        $elem_key = array();
                                        $elem_key = array_keys($elem);
                                        //$elem_key[1]= le kpi d�finit pour le singleKPI
                                        $gtm_elements[$elem_key[1]][$ta_value]['raw'][$raw_key_pdt][$elem['eor_id']] = $elem[strtolower($raw_id)];
                                        //on d�finit cette variable pour les singleKPI car elle contient l'information id_ligne pour r�cup�rer le label du RAW
                                        // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                        $this->singleKPI_rawID[$gtm_id] = $raw_key_id;
                                    } else {

                                        $gtm_elements[$elem['eor_id']][$ta_value]['raw'][$raw_key_pdt][$raw_key_id] = $elem[strtolower($raw_id)];
                                    }
                                    // 14:14 09/10/2009 SCT : ajout de la gestion des couleurs
                                    $this->neColor[$elem['eor_id']][$ta_value] = $this->couleurNeAxe1[$gtm_products[$i]][$this->naAbcisseAxe1][$elem['eor_id']];

                                    // Si une / plusieurs bh sont d�finis, stockage de leurs valeurs

                                    if (count($this->bh) > 0) {
                                        for ($k = 0; $k < count($this->bh); $k++) {
                                            if ($elem[strtolower($this->bh[$k])] != "") {
                                                $this->bhData[$elem['eor_id']][$ta_value]['raw'][$raw_key_pdt][$raw_key_id][$this->bhLabel[$this->bh[$k]]] = getTaValueToDisplay("hour", $elem[strtolower($this->bh[$k])], "/");
                                            }
                                        }
                                    }

                                    // 14:22 26/05/2009 GHX
                                    // Si on a les liens vers AA d'activ�
                                    if ($this->enabledLinkToAA) {
                                        $tmp = array();
                                        if (array_key_exists($raw_key_id, $infoLinkToAA['raw'])) {
                                            // 23/03/2010 NSE bz 14831 : suppression du param�tre data_value inutilis�
                                            $tmp = array(
                                                'family' => $infoLinkToAA['raw'][$raw_key_id]['family'],
                                                'ta' => $this->ta,
                                                'ta_value' => $ta_value,
                                                'na' => '{$naAxe1AA}',
                                                'ne' => '{$neAxe1AA}',
                                                'ne_axe3' => '{$naAxe3AA}',
                                                'na_axe3' => '{$neAxe3AA}',
                                                'data' => 'raw@' . $infoLinkToAA['raw'][$raw_key_id]['saafk_idkpi'],
                                                'idProduct' => $raw_key_pdt . '|t|' . $infoLinkToAA['raw'][$raw_key_id]['labelAA']
                                            );
                                        }
                                        $this->linkToAA[$gtm_id][$elem['eor_id']][$ta_value]['raw'][$raw_key_pdt][$raw_key_id] = implode(self::SEPARATOR, $tmp);
                                    }
                                    //CB 5.3.1 : Link to Nova Explorer
                                    // Si on a les liens vers NE d'activ�
                                    if ($this->enabledLinkToNE) {
                                        $tmp = array();
                                        if (array_key_exists($raw_key_id, $infoLinkToNE['raw'])) {
                                            // 23/03/2010 NSE bz 14831 : suppression du param�tre data_value inutilis�
                                            $tmp = array(
                                                'family' => $infoLinkToNE['raw'][$raw_key_id]['family'],
                                                'ta' => $this->ta,
                                                'ta_value' => $ta_value,
                                                'na' => '{$naAxe1NE}',
                                                'ne' => '{$neAxe1NE}',
                                                'ne_axe3' => '{$naAxe3NE}',
                                                'na_axe3' => '{$neAxe3NE}',
                                                'data' => 'raw@' . $infoLinkToNE['raw'][$raw_key_id]['snefk_idkpi'],
                                                'idProduct' => $raw_key_pdt . '|t|' . $infoLinkToNE['raw'][$raw_key_id]['labelNE']
                                            );
                                        }
                                        $this->linkToNE[$gtm_id][$elem['eor_id']][$ta_value]['raw'][$raw_key_pdt][$raw_key_id] = implode(self::SEPARATOR, $tmp);
                                    }
                                }
                            } else {
                                // Stockage dans le tableau des �l�ments du GTM des valeurs des kpis

                                foreach ($product_kpis[$family] as $kpi_id => $kpi_values) {
                                    $kpi_key_pdt = $gtm_products[$i];
                                    $kpi_key_id = $kpi_values['id'];

                                    // 15:07 08/06/2009 GHX
                                    // V�rifie qu'on a des donn�es
                                    // 17:08 18/12/2009 GHX
                                    // Correction du BZ 12483
                                    // Modification de la fonction pour faire la distinction entre les valeurs null et 0
                                    if ($elem[strtolower($kpi_id)] !== null) {
                                        $hasValues = true;
                                    }

                                    // 11:45 11/05/2009 GHX
                                    // Si le split a une valeur
                                    if (isset($splitBykpi)) {
                                        // 11:58 21/07/2009 GHX
                                        // Modification de la condition (ajout de la deuxieme partie)
                                        if (!empty($elem[strtolower($kpi_id)]) && $kpi_id == $splitBykpi) {
                                            $splitByHasValues = true;
                                        }
                                    }
                                    // Single KPI
                                    if ($this->gtmTypes[$gtm_id] == "singleKPI" && $this->mode == "overtime") {
                                        $elem_key = array();
                                        $elem_key = array_keys($elem);
                                        //$elem_key[1]= le kpi d�finit pour le singleKPI
                                        $gtm_elements[$elem_key[1]][$ta_value]['kpi'][$kpi_key_pdt][$elem['eor_id']] = $elem[strtolower($kpi_id)];
                                        //on d�finit cette variable pour les singleKPI car elle contient l'information id_ligne pour r�cup�rer le label du KPI
                                        // 07/07/2011 NSE bz 22886 : le Raw/Kpi est d�finit par Graph
                                        $this->singleKPI_kpiID[$gtm_id] = $kpi_key_id;
                                    } else {
                                        $gtm_elements[$elem['eor_id']][$ta_value]['kpi'][$kpi_key_pdt][$kpi_key_id] = $elem[strtolower($kpi_id)];
                                    }// 14:14 09/10/2009 SCT : ajout de la gestion des couleurs
                                    $this->neColor[$elem['eor_id']][$ta_value] = $this->couleurNeAxe1[$gtm_products[$i]][$this->naAbcisseAxe1][$elem['eor_id']];

                                    // Si une / plusieurs bh sont d�finis, stockage de leurs valeurs

                                    if (count($this->bh) > 0) {
                                        for ($k = 0; $k < count($this->bh); $k++) {
                                            if ($elem[strtolower($this->bh[$k])] != "") {
                                                $this->bhData[$elem['eor_id']][$ta_value]['kpi'][$kpi_key_pdt][$kpi_key_id][$this->bhLabel[$this->bh[$k]]] = getTaValueToDisplay("hour", $elem[strtolower($this->bh[$k])], "/");
                                            }
                                        }
                                    }

                                    // 14:22 26/05/2009 GHX
                                    // Si on a les liens vers AA d'activ�
                                    if ($this->enabledLinkToAA) {
                                        $tmp = array();
                                        if (array_key_exists($kpi_key_id, $infoLinkToAA['kpi'])) {
                                            // 23/03/2010 NSE bz 14831 : suppression du param�tre data_value inutilis�
                                            $tmp = array(
                                                'family' => $infoLinkToAA['kpi'][$kpi_key_id]['family'],
                                                'ta' => $this->ta,
                                                'ta_value' => $ta_value,
                                                'na' => '{$naAxe1AA}',
                                                'ne' => '{$neAxe1AA}',
                                                'ne_axe3' => '{$naAxe3AA}',
                                                'na_axe3' => '{$neAxe3AA}',
                                                'data' => 'kpi@' . $infoLinkToAA['kpi'][$kpi_key_id]['saafk_idkpi'] . '@' . $elem[strtolower($kpi_id)],
                                                'idProduct' => $kpi_key_pdt . '|t|' . $infoLinkToAA['kpi'][$kpi_key_id]['labelAA']
                                            );
                                        }
                                        $this->linkToAA[$gtm_id][$elem['eor_id']][$ta_value]['kpi'][$kpi_key_pdt][$kpi_key_id] = implode(self::SEPARATOR, $tmp);
                                    }
                                    //CB 5.3.1 : Link to Nova Explorer
                                    // Si on a les liens vers NE d'activ�
                                    if ($this->enabledLinkToNE) {
                                        $tmp = array();
                                        if (array_key_exists($kpi_key_id, $infoLinkToNE['kpi'])) {
                                            // 23/03/2010 NSE bz 14831 : suppression du param�tre data_value inutilis�
                                            $tmp = array(
                                                'family' => $infoLinkToNE['kpi'][$kpi_key_id]['family'],
                                                'ta' => $this->ta,
                                                'ta_value' => $ta_value,
                                                'na' => '{$naAxe1NE}',
                                                'ne' => '{$neAxe1NE}',
                                                'ne_axe3' => '{$naAxe3NE}',
                                                'na_axe3' => '{$neAxe3NE}',
                                                'data' => 'kpi@' . $infoLinkToNE['kpi'][$kpi_key_id]['snefk_idkpi'] . '@' . $elem[strtolower($kpi_id)],
                                                'idProduct' => $kpi_key_pdt . '|t|' . $infoLinkToNE['kpi'][$kpi_key_id]['labelNE']
                                            );
                                        }
                                        $this->linkToNE[$gtm_id][$elem['eor_id']][$ta_value]['kpi'][$kpi_key_pdt][$kpi_key_id] = implode(self::SEPARATOR, $tmp);
                                    }
                                }
                            }
                        }
                    } // End while getQueryResult
                }
            }
            if ($this->debug) {
                echo "<br /><b>( getGTMElements : " . round(microtime(true) - $time_start_foreach, 4) . "s)</b><br />";
            }
        } // End for gtm_products
        // 11:48 11/05/2009 GHX
        // Si pas de donn�e pour le splitBy en PIE
        if ($splitByHasValues == false && (!(strpos($this->gtmTypes[$gtm_id], "pie") === false))) {
            return 'no_data_splitBy';
        }
        // 11:30 21/07/2009 GHX
        // Correction du BZ 10358 [REC][T&A Cb 5.0][DASH]: affichage des valeurs null dans le dashboard
        elseif (count($gtm_elements) == 0 || $hasValues == false) {
            return 'no_data';
        } else {
            // 25/02/2009 - Modif. benoit : on r�organise les �l�ments du GTM et les donn�es BH afin de conserver le tri �tabli lors de la s�lection des valeurs de na

            $gtm_elements_tmp = array();
            $bh_data_tmp = array();

            // 10:47 13/05/2009 GHX
            // Gestion du cas particulier sur le PIE split by sur le troisieme axe
            $pieThirdAxis = false;

            if (isset($splitBy)) {
                if ($splitBy['split_type'] == 'third_axis' && count($this->neAxe1Init[$this->naAxe1]) == 1 && $this->mode == 'overnetwork') {
                    $pieThirdAxis = true;

                    if (!is_int($offset)) {
                        $offset = 0;
                    }

                    // 17:19 13/05/2009 GHX
                    // On applique le dur le filtre et le sort by sur les PIE en split par 3ieme axe
                    // On regarde si on peut appliquer le filtre
                    if (in_array($this->filter['product'], $gtm_products)) {
                        $type = $this->filter['type'];
                        $idProduct = $this->filter['product'];
                        $operand = ($this->filter['operand'] == '=' ? '==' : $this->filter['operand']);
                        $valueFiltre = $this->filter['value'];
                        $id = ${'product_' . $type . 's'}[$this->filter['family']][$this->filter['name']]['id'];

                        foreach ($gtm_elements as $eor_id => $infos) {

                            foreach ($infos as $taValue => $subInfos) {
                                eval("\$isNok = (" . $subInfos[$type][$idProduct][$id] . " " . $operand . " " . $valueFiltre . " ? false : true);");
                                // Suppression de la donn�e si elle ne correspond pas au filtre
                                if ($isNok) {
                                    unset($gtm_elements[$eor_id]);
                                    if (count($this->bh) > 0)
                                        $bh_data_tmp[$eor_id] = $this->bhData[$eor_id];
                                }
                            }
                        }
                    }
                    // On regarde si on peut appliquer le sortBy
                    if (in_array($this->sortBy['product'], $gtm_products)) {
                        $type = $this->sortBy['type'];
                        $idProduct = $this->sortBy['product'];
                        $asc_desc = $this->sortBy['asc_desc'] == 'asc' ? SORT_ASC : SORT_DESC;
                        $id = ${'product_' . $type . 's'}[$this->sortBy['family']][$this->sortBy['name']]['id'];

                        if (!empty($id)) {
                            $tabForSortBy = array();
                            // Boucle sur tous les NE du graphes
                            foreach ($gtm_elements as $eor_id => $infos) {

                                // Pour chaque NE on boucle sur tous les jours de donn�es que l'on a r�cup�r�
                                foreach ($infos as $taValue => $subInfos) {
                                    // Si on a une valeur
                                    if (!empty($subInfos[$type][$idProduct][$id])) {
                                        $tabForSortBy[$eor_id] = $subInfos[$type][$idProduct][$id];
                                    }
                                    // 12:20 14/10/2009 SCT : correction du bug 11992 => error multisort sur PIE avec Split 3�me axe
                                    else {//if ( $asc_desc == SORT_ASC )
                                        unset($gtm_elements[$eor_id]);
                                        if (count($this->bh) > 0)
                                            $bh_data_tmp[$eor_id] = $this->bhData[$eor_id];
                                    }
                                }
                            }
                            if (isset($tabForSortBy)) {
                                array_multisort($tabForSortBy, $asc_desc, $gtm_elements);
                                if (count($this->bh) > 0)
                                    array_multisort($tabForSortBy, $asc_desc, $bh_data_tmp);
                            }
                        }
                        else {

                            for ($i = 0; $i < count($ne[$this->naAbcisseAxe1]); $i++) {
                                $ne_elt = $ne[$this->naAbcisseAxe1][$i];

                                $gtm_elements_tmp[$ne_elt] = $gtm_elements[$ne_elt];

                                if (count($this->bh) > 0)
                                    $bh_data_tmp[$ne_elt] = $this->bhData[$ne_elt];
                            }
                        }
                    }

                    if (empty($gtm_elements_tmp)) {
                        $gtm_elements_tmp = array_slice($gtm_elements, $offset, $this->getTop(), false);

                        if (count($this->bh) > 0)
                            $bh_data_tmp = array_slice($bh_data_tmp, $offset, $this->getTop(), false);
                    }
                    $this->neAxe1ForPieThirsAxis[$gtm_id][$this->naAxe1] = array_keys($gtm_elements_tmp);
                }
                // 08:18 04/12/2009 GHX
                // Correction du BZ 12105
                // Suppression du else
            }

            if (!$pieThirdAxis) {
                for ($i = 0; $i < count($ne[$this->naAbcisseAxe1]); $i++) {
                    // 11/04/2012 BBX
                    // BZ 26740 : on r�cup�re les infos BH, m�me en Single KPI
                    $ne_elt = $ne[$this->naAbcisseAxe1][$i];
                    if (count($this->bh) > 0)
                        $bh_data_tmp[$ne_elt] = $this->bhData[$ne_elt];

                    // Single KPI
                    if ($this->gtmTypes[$gtm_id] == "singleKPI" && $this->mode == "overtime") {
                        // //$elem_key[1]= le kpi d�finit pour le singleKPI
                        $gtm_elements_tmp[$elem_key[1]] = $gtm_elements[$elem_key[1]];
                    } else {
                        $gtm_elements_tmp[$ne_elt] = $gtm_elements[$ne_elt];
                    }
                }
            }

            $gtm_elements = $gtm_elements_tmp;

            if (count($this->bh) > 0)
                $this->bhData = $bh_data_tmp;

            if ($this->debug) {
                __debug($gtm_elements, 'gtm_elements', 2);
            }
            return $gtm_elements;
        }
    }

    /**
     * D�finition de la requete � effectuer sur la table de r�f�rence pour chaque produit
     *
     * @param int $product identifiant du produit
     * @param boolean $axeN pr�sence (true) / abscence (false) de l'axe n (optionnel)
     * @return string la requete � executer sur la table de r�f�rence
     */
    private function setProductRefTable($product, $axeN = false) {
        // Si un axe N est pr�sent sur tous les produits, on d�finit une sous-requete listant les ne d'axe 1 et les ne d'axe N
        // La condition n'est valable que si tous les produits d�finissent un axe N et qu'il n'existe pas de valeur particuli�re d'axe N d�finie (= "ALL")
        //if (($axeN) && (count($this->naAxeN[$product]) > 0) && (count($this->neAxeN[$product]) == 0)) {

        $na_axeN = $this->checkNeAxeNALL($product);

        if (($axeN) && ($na_axeN != false)) {

            // D�finition des na d'axe N
            //$na_axeN = $this->naAxeN[$product];
            // On r�cup�re les valeurs de ne d�finies pr�cedemment via la m�thode 'getNE()'. Les ne doivent �tre de la forme "ne_axe1|s|ne_axeN" (pour l'instant, N vaut toujours 3 => seulement 2 axes : axe 1 et 3)
            //$ne_list = $this->neAxe1[$this->naAbcisseAxe1];

            $ne_list = $this->ConvertEquivalentNeAxe1($this->neAxe1[$this->naAbcisseAxe1], $this->naAbcisseAxe1, $product);

            // On d�finit un tableau de ne o� la na est la cl� et la ne correspondante la valeur

            $ne = array();

            for ($j = 0; $j < count($ne_list); $j++) {

                $ne_tab = explode(self::SEPARATOR, $ne_list[$j]);

                // La valeur de la na d'axe 1 est toujours le 1er �l�ment du tableau

                if (@!in_array($ne_tab[0], $ne[$this->naAbcisseAxe1])) {
                    $ne[$this->naAbcisseAxe1][] = $ne_tab[0];
                }

                // On boucle sur toutes les na d'axe N d�fini en allant chercher les ne correspondante � leur index+1 dans le tableau de ne
                //$na_axeN = $this->naAxeN[$product];

                for ($k = 0; $k < count($na_axeN); $k++) {
                    if (@!in_array($ne_tab[$k + 1], $ne[$na_axeN[$k]])) {
                        $ne[$na_axeN[$k]][] = $ne_tab[$k + 1];
                    }
                }
            }

            // D�finiton de la table de r�f�rence ou de la sous-requete suivant les types de na pr�sentes

            $index = 0;

            $columns = array();
            $ref_tables = array();
            $elements_list = array();

            foreach ($ne as $key => $value) {

                $index_sub = "A" . $index;

                $columns[] = $index_sub . ".eor_id";
                $ref_tables[] = "edw_object_ref AS " . $index_sub;

                $ne_list_str = implode(",", array_map(array($this, 'labelizeValue'), $value));

                if ($ne_list_str != "''") {
                    $elements_list[] = $index_sub . ".eor_id IN (" . $ne_list_str . ") AND " . $index_sub . ".eor_obj_type = '" . $key . "' AND " . $index_sub . ".eor_on_off = 1";
                } else {
                    // 16:33 05/05/2009 GHX
                    // Ajout de l'alias de la table sur le on_off sinon probl�me de colonne ambigue
                    $elements_list[] = $index_sub . ".eor_obj_type = '" . $key . "' AND " . $index_sub . ".eor_on_off = 1";
                }

                $index += 1;
            }

            $sql .= " (SELECT DISTINCT " . implode("||'" . self::SEPARATOR . "'||", $columns) . "  AS eor_id FROM " . implode(", ", $ref_tables)
                    . " WHERE " . implode(" AND ", $elements_list) . ") A";
        } else { // Sinon, on inclut seulement la table de r�f�rence des �l�ments d'axe 1
            $sql .= " edw_object_ref A";
        }

        return $sql;
    }

    /**
     * D�finition de la requete propre � chaque famille des �l�ments du GTM
     *
     * @param array $elements �l�ments du GTM propre � la famille
     * @param string $type type des �l�ments(raw / kpi)
     * @param string $family nom de la famille
     * @param int $product identifiant du produit
     * @param int $gtm_id identifiant du GTM
     * @param int $offset valeur de l'offset (cas du zoom plus)
     * @return string requete propre � la famille
     */
    private function setFamilyQuery($elements, $type, $family, $product, $gtm_id, $offset = '') {
        // D�finition de la liste d'�l�ments

        $elts_list = implode(", ", $elements);

        // En mode "overtime", on inclut la ta dans la liste des �l�ments s�lectionn�s

        if ($this->mode == "overtime"/* && (strpos($this->gtmTypes[$gtm_id], "pie") === false) */) {
            $elts_list .= ", " . $this->ta;
        }

        // 05/01/2009 - Modif. benoit : ajout de la / des colonne(s) bh

        if (count($this->bh) > 0) {
            $elts_list .= ", " . implode(", ", $this->bh);
        }

        // D�finition du group_table et de la pr�sence / absence de l'axe3

        $group_axe = $this->GetGtmElementsGroupAndAxe($product, $family);

        // D�finition du nom de la table de donn�es

        $data_table = $this->setDataTableName($group_axe['group_table'], $type, $product, $family, $group_axe['axeN']);

        // D�finition des champs d'axe N � r�cup�rer via la requete
        // 18:20 11/05/2009 GHX
        // Si on a une valeur particuli�re pour l'axeN, il n'apparaitra pas dans le select
        $group_axe_sort = $this->GetGtmElementsGroupAndAxe($this->sortBy['product'], $this->sortBy['family']);
        $axe_n_columns = "";

        // 12/05/2009 GHX
        // Modification de la condition pour prendre en compte les combinaisons
        if (($group_axe_sort['axeN'] == true) && (($this->sortBy['family'] == $this->filter['family']) || (count($this->filter) == 0)) && !$this->checkAxe3OnAllFamilies) {
            $axe_n_columns = $this->setAxeNColumns($product, $family, $group_axe['axeN'], false);
        }

        // D�finition de la condition sur les ta
        $ta_cond = $this->setTACondition();

        // 06/06/2011 MMT DE 3rd Axis suppression de code jamais execut�
        // D�finition de la liste des ne
        // Dans le cas du zoom plus, on r�duit le tableau de ne suivant la valeur de l'offset
        // 16:10 12/05/2009 GHX
        // Prise en compte du cas particulier sur les PIE splitBY 3ieme axe
        $sortBy = $this->getSplitBy($gtm_id);

        // 16:31 29/10/2009 GHX
        // Ajout de la derniere condition : si on a s�lectionn� un troisieme axe on consid�re que l'on ne peut pas faire de split troisieme axe (c'est plus simple a g�rer)
        // 16:18 04/12/2009 GHX
        // Correction du BZ  13055
        // Ajout d'une nouvelle condition : dans le cas ou le sort by est le m�me que le graphe que l'on traite c'est qu'on a d�j� r�cup�rer les bonnes combinaisons
        if ($sortBy["split_type"] == "third_axis" && $gtm_id != $this->sortBy['id_gtm'] && count($this->neAxe1Init[$this->naAxe1]) == 1 && $this->mode == 'overnetwork' && $axeN_cond == '') {
            $naSelect = $this->naAxe1;
            $data_table = str_replace('_' . $this->naAbcisseAxe1 . '_', '_' . $this->naAxe1 . '_', $data_table);
            $ne_tmp = $this->neAxe1Init[$this->naAxe1];
        } else {
            $naSelect = $this->naAbcisseAxe1;

            if (is_int($offset)) {
                $ne_tmp = array_slice($this->neAxe1[$this->naAbcisseAxe1], $offset, $this->getTop(), false);
            } else {
                $ne_tmp = $this->neAxe1[$this->naAbcisseAxe1];
            }
        }

        if ($axe_n_columns == "") {
            if (!(strpos($ne_tmp[0], self::SEPARATOR) === false)) {
                $this->noDisplayThirdAxis = true;
                $ne_tmp2 = array();
                foreach ($ne_tmp as $_ne) {
                    $_ne = explode(self::SEPARATOR, $_ne);
                    $ne_tmp2[] = $_ne[0];
                }
                $ne_tmp = $ne_tmp2;
            }
        }

        //$ne = $this->ConvertEquivalentNeAxe1($ne_tmp, $this->naAbcisseAxe1, $product);
        $ne = $this->ConvertEquivalentNeAxe1($ne_tmp, $naSelect, $product);
        // 11:57 17/08/2009 GHX
        if ($this->multiSort == true) {
            if (isset($this->equivalentNeAxe1[$product][$naSelect])) {
                $ne = array_intersect($ne, $this->equivalentNeAxe1[$product][$naSelect]);
            } else {
                // 16:11 17/08/2009 GHX
                // Uniquement dans le cas ou le sort by est multiple et que sur le produit $product on n'a pas besoin de r�cup�r� des �l�ments donn�es donc pas de requete SQL
                // a ex�cuter
                return '';
            }
        }

        $ne_list = implode(",", array_map(array($this, 'labelizeValue'), $ne));



        // D�finition de la requete
        // 10:11 06/05/2009 GHX
        $sql = "SELECT " . $naSelect . $axe_n_columns . " AS eor_id, " . $elts_list . " FROM " . $data_table . " WHERE " . $ta_cond;

        // Cas de la s�lection de plusieurs �l�ments d'axe N
        // 12/05/2009 GHX
        // Modification de la condition pour prendre en compte les combinaisons
        if ($group_axe['axeN']) {

            // Plusieurs �l�ments d'axe N diff�rents existent. On base la condition sur les valeurs
            // Note : factoriser cette condition
            //$ne = $this->ConvertEquivalentNeAxe1($this->neAxe1[$this->naAbcisseAxe1], $this->naAbcisseAxe1, $product);

            $ne_cond = array();

            for ($i = 0; $i < count($ne); $i++) {

                $ne_all_axe = explode(self::SEPARATOR, $ne[$i]);

                //$cond = "(".$this->naAbcisseAxe1." = '".$ne_all_axe[0]."'";
                $cond = "(" . $naSelect . " = '" . $ne_all_axe[0] . "'";

                $na_axeN = $this->naAxeN[$product][$family];

                for ($j = 0; $j < count($na_axeN); $j++) {
                    if ($ne_all_axe[$j + 1] != "")
                        $cond .= " AND " . $na_axeN[$j] . " = '" . $ne_all_axe[$j + 1] . "'";
                }

                $cond .= ")";

                $ne_cond[] = $cond;
            }

            $sql .= " AND (" . implode(' OR ', $ne_cond) . ")";
        }

        // S�lection uniquement d'�l�ments d'axe 1 ou d'un seul �l�ment d'axe N

        else {
            //$sql .= " AND ".$this->naAbcisseAxe1." IN (".$ne_list.")".$axeN_cond;
            // 22/11/2011 BBX
            // BZ 24764 : correction des notices php
            $sql .= " AND " . $naSelect . " IN (" . $ne_list . ")" . (isset($axeN_cond) ? $axeN_cond : '');
        }

        return $sql;
    }

    /**
     * Permet de d�terminer les ne communs � tous les GTMS
     *
     * @param string $gtm_id identifiant d'un graphe/pie dans le cas d'un sort pas graphe/pie et non sur l'ensemble des graphes/pies du dashboards
     * @return boolean existence de ne (true) ou non (false)
     */
    public function getNE($gtm_id = "") {
        try {
            $time_start = $this->microtime_float();

            // 1 - Avant de lancer la r�cup�ration des ne, on v�rifie que les na du raw / kpi de tri et de filtre sont compatibles

            /*
              R�gles de gestion (pour simplifier, on suppose qu'il n'y a qu'un seul axe N -> axe3) :

              -	les raws/kpis de tri/filtre portent sur la m�me famille (et donc le m�me axe3). Pas de v�rification sur la / les valeur(s) 	de na d'axe3

              -	les raws/kpis de tri/filtre portent sur une famille axe1 et une famille axe3. Il faut que la valeur d'axe3 s�lectionn�e 	soit une valeur particuli�re (ou si plusieurs valeurs que ce soit les m�mes)

              -	tous les autres cas (familles 3�me axe diff�rentes, combinaison famille axe1 / axe3 avec valeur 3eme axe "ALL") sont 		proscrites
             */

            if (count($this->filter) > 0) { // Un filtre est d�fini
                if ($this->sortBy['family'] != $this->filter['family']) { // Les familles du raw/kpi de tri et de filtre sont diff�rentes
                    // D�finition du groupe et de l'axe pour les 2 raws / kpis
                    $group_axe_sort = $this->GetGtmElementsGroupAndAxe($this->sortBy['product'], $this->sortBy['family']);
                    $group_axe_filter = $this->GetGtmElementsGroupAndAxe($this->filter['product'], $this->filter['family']);

                    if ($group_axe_sort['axeN'] != $group_axe_filter['axeN']) { // Un des 2 raws/kpis fait partie d'une famille 3�me axe
                        // On d�termine � quel produit appartient l'axe 3
                        if ($group_axe_sort['axeN'] == true) {
                            $product_of_axeN = $this->sortBy['product'];
                            $family_of_axeN = $this->sortBy['family'];
                        } else {
                            $product_of_axeN = $this->filter['product'];
                            $family_of_axeN = $this->filter['family'];
                        }

                        //$product_of_axeN = (($group_axe_sort['axeN'] == true) ? $this->sortBy['product'] : $this->filter['product']);

                        $na_axeN = $this->naAxeN[$product_of_axeN][$family_of_axeN];

                        if (count($na_axeN) == 0) { // Premi�re erreur : pas d'axeN d�fini
                            // Note : mettre les messages en base

                            throw new Exception('No value is defined for the third axis');
                        } else {
                            // On recherche s'il existe des ne pour les na d'axeN

                            $ne_axeN_find = false;

                            for ($i = 0; $i < count($na_axeN); $i++) {
                                if (count($this->neAxeN[$product_of_axeN][$family_of_axeN][$na_axeN[$i]]) > 0)
                                    $ne_axeN_find = true;
                            }

                            // Deuxi�me erreur : les 2 raws / kpis sont incompatibles car aucune valeur d'axe3 n'est s�lectionn�e

                            if (!$ne_axeN_find) {
                                throw new Exception('Raw / Kpi for the sort by and the filter are incompatibles');
                            }
                        }
                    }
                    // Note : faire peut �tre une v�rification sur l'appartenance de la na 3�me axe (v�rif. faite en amont)
                }
            }

            // 2 - On r�cup�re les ne correspondant au tri et au filtre

            $time_start_sortBy = microtime(true);

            /*
              14/08/2009 GHX
              .-> Gestion du sort by dans le cas o� plusieurs fois le m�me KPI/RAW sur un graphe (code+legende)
             */
            $saveSortBy = $this->sortBy;
            $gtmMod = new GTMModel($this->sortBy['id_gtm']);
            if ($this->sortBy['type'] == 'kpi') {
                $sameElements = $gtmMod->getSameKpis($this->sortBy['name'], $this->sortBy['label']);
            } else {
                $sameElements = $gtmMod->getSameRaws($this->sortBy['name'], $this->sortBy['label']);
            }
            // Si on a plusieurs raw ou kpi identique dans un m�me graphe et qui sont consid�r� comme un seul au niveau restitution
            if (count($sameElements) > 0 && count($this->sortBy) > 0) {
                // 14:35 17/08/2009 GHX
                $this->multiSort = true;

                // Cas d'un el�ment identique pr�sent plusieurs fois sur le dahsboard
                if ($this->sortBy['type'] == 'kpi') { // KPI
                    $ne_orderby = array();
                    foreach (array_keys($sameElements) as $sameEl) {
                        $sameEl = explode('@', $sameEl);
                        $other_infos = $gtmMod->getKpiInformations($sameEl[0], $sameEl[1]);
                        $this->sortBy['family'] = $other_infos['family'];
                        $this->sortBy['product'] = $sameEl[1];

                        foreach ($this->getNeFromSortBy() as $ne => $val) {
                            if (array_key_exists($ne, $ne_orderby)) {
                                if ($this->sortBy['asc_desc'] == 'asc') { // ASC
                                    if ($ne_orderby[$ne]['values'][$this->sortBy['name']] > $val['values'][$this->sortBy['name']]) {
                                        $ne_orderby[$ne] = $val;
                                    }
                                } else { // DESC
                                    if ($ne_orderby[$ne]['values'][$this->sortBy['name']] < $val['values'][$this->sortBy['name']]) {
                                        $ne_orderby[$ne] = $val;
                                    }
                                }
                            } else {
                                $ne_orderby[$ne] = $val;
                            }
                        }
                    }
                } else { //RAW
                    $ne_orderby = array();
                    foreach (array_keys($sameElements) as $sameEl) {
                        $sameEl = explode('@', $sameEl);
                        $other_infos = $gtmMod->getRawInformations($sameEl[0], $sameEl[1]);
                        $this->sortBy['family'] = $other_infos['family'];
                        $this->sortBy['product'] = $sameEl[1];
                        foreach ($this->getNeFromSortBy() as $ne => $val) {
                            if (array_key_exists($ne, $ne_orderby)) {
                                if ($this->sortBy['asc_desc'] == 'asc') { // ASC
                                    if ($ne_orderby[$ne]['values'][$this->sortBy['name']] > $val['values'][$this->sortBy['name']]) {
                                        $ne_orderby[$ne] = $val;
                                    }
                                } else { // DESC
                                    if ($ne_orderby[$ne]['values'][$this->sortBy['name']] < $val['values'][$this->sortBy['name']]) {
                                        $ne_orderby[$ne] = $val;
                                    }
                                }
                            } else {
                                $ne_orderby[$ne] = $val;
                            }
                        }
                    }
                }

                // Boucle pour cr�er 2 tableaux pour pouvoir trier la liste des �l�ments r�seaux r�cup�r�s
                $tmpSortNe = array();
                $tmpSortVal = array();
                foreach ($ne_orderby as $ne => $val) {
                    $tmpSortNe[] = $ne;
                    $tmpSortVal[] = $val['values'][$this->sortBy['name']];
                }

                // Restaure le sort by du s�lecteur
                $this->sortBy = $saveSortBy;

                // Tri la liste des �l�ments r�seaux en fonctions du tri du sort by du s�lecteur
                if ($this->sortBy['asc_desc'] == 'asc') {
                    array_multisort($tmpSortVal, SORT_ASC, $ne_orderby);
                } else {
                    array_multisort($tmpSortVal, SORT_DESC, $ne_orderby);
                }
            } else {
                // Cas standart
                $ne_orderby = ((count($this->sortBy) > 0) ? $this->getNeFromSortBy() : array());
            }

            /*
              14/08/2009 GHX
              .-> Gestion du filter dans le cas o� plusieurs fois le m�me KPI/RAW sur un graphe (code+legende)
             */
            $time_start_Filter = microtime(true);
            $saveFilter = $this->filter;
            $gtmMod = new GTMModel($this->filter['id_gtm']);
            if ($this->filter['type'] == 'kpi') {
                $sameElements = $gtmMod->getSameKpis($this->filter['name'], $this->filter['label']);
            } else {
                $sameElements = $gtmMod->getSameRaws($this->filter['name'], $this->filter['label']);
            }

            if (count($sameElements) > 0 && count($this->filter) > 0) {
                // Cas d'un el�ment identique pr�sent plusieurs fois sur le dahsboard
                if ($this->filter['type'] == 'kpi') { // KPI
                    $ne_filter = array();
                    foreach (array_keys($sameElements) as $sameEl) {
                        $sameEl = explode('@', $sameEl);
                        $other_infos = $gtmMod->getKpiInformations($sameEl[0], $sameEl[1]);
                        $this->filter['family'] = $other_infos['family'];
                        $this->filter['product'] = $sameEl[1];
                        $ne_filter = array_merge($ne_filter, $this->getNeFromFilter());
                    }
                } else { //RAW
                    $ne_filter = array();
                    foreach (array_keys($sameElements) as $sameEl) {
                        $sameEl = explode('@', $sameEl);
                        $other_infos = $gtmMod->getRawInformations($sameEl[0], $sameEl[1]);
                        $this->filter['family'] = $other_infos['family'];
                        $this->filter['product'] = $sameEl[1];
                        $ne_filter = array_merge($ne_filter, $this->getNeFromFilter());
                    }
                }

                // Boucle pour cr�er 2 tableaux pour pouvoir trier la liste des �l�ments r�seaux r�cup�r�s
                $tmpSortNe = array();
                $tmpSortVal = array();
                foreach ($ne_filter as $ne => $val) {
                    $tmpSortNe[$ne] = $ne;
                    $tmpSortVal[$ne] = $val['values'][$this->filter['name']];
                }

                // Restaure le sort by du s�lecteur
                $this->filter = $saveFilter;
            } else {
                // Cas standard
                $ne_filter = ((count($this->filter) > 0) ? $this->getNeFromFilter() : array());
            }

            $time_sortBy = round($time_start_Filter - $time_start_sortBy, 4);
            $time_Filter = round(microtime(true) - $time_start_Filter, 4);

            // 16:54 19/05/2009 GHX
            // Si on a un filtre il faut et qu'il n'y a pas de r�sultat alors on n'a pas d'�l�ment r�seau en commun avec le sort by
            if (count($this->filter) > 0 && count($ne_filter) == 0) {
                // 10:49 25/05/2009 GHX
                // Affichage d'un message comme quoi le filtre ne retourne pas le message
                if (!$this->msgFilterNoDataAlreadyDisplay) {
                    echo '<span class="msgOrderByAlpha">' . __T('U_DASH_FILTER_NO_DATA') . '</span>';
                    $this->msgFilterNoDataAlreadyDisplay = true;
                }
                return false;
            }

            // 3 - On met en commun les ne provenant des 2 sources. On ne prend ici que les cl�es qui correspondent � la na d'axe1

            $time_start_php = microtime(true);

            $common_ne = array_intersect_assoc($ne_orderby, $ne_filter);

            // 3.1 - Des �l�ments en commun existent, on laisse le tableau d'�l�ments tel quel
            if (count($common_ne) > 0) {
                $common_ne = (($this->zoomPlus) ? $common_ne : array_slice($common_ne, 0, $this->getTop(), true));
            }
            // 3.2 - Aucun �l�ment en commun et des �l�ments provenant du tri. On prend les valeurs des �l�ments de tri
            else if ((count($common_ne) == 0) && (count($ne_orderby) > 0)) {
                $common_ne = (($this->zoomPlus) ? $ne_orderby : array_slice($ne_orderby, 0, $this->getTop(), true));
            }
            // 3.3 - Aucun �l�ment en commun, pas de valeurs de tri mais des valeurs pour le filtre. On prend ces �l�ments
            else if ((count($common_ne) == 0) && (count($ne_filter) > 0)) {
                $common_ne = (($this->zoomPlus) ? $ne_filter : array_slice($ne_filter, 0, $this->getTop(), true));
            }
            // 3.4 - Pas d'�l�ments du tout. On r�cup�re les �l�ments de la table de donn�es du raw / kpi de tri en les triant par ordre alphab�tique
            else {
                // 12:26 29/04/2009 GHX
                // Affiche qu'une seule fois le message
                if (!$this->msgOrderByAlphaAlreadyDisplay) {
                    echo '<span class="msgOrderByAlpha">' . __T('U_DASH_ORDERBY_ALPHA', $this->naLabel[$this->naAbcisseAxe1]) . '</span>';
                    $this->msgOrderByAlphaAlreadyDisplay = true;
                }

                $common_ne = $this->getNeAlphaSort();

                // 3.5 - Si � l'issue de la r�cup�ration des �l�ments par ordre alphab�tique on a toujours aucun r�sultat,
                // on affiche un message d'erreur indiquant qu'aucune donn�e n'a pu �tre trouv�e
                if (count($common_ne) == 0) {
                    throw new Exception("No data found");
                }
            }

            // 4 - Si des �l�ments ont �t� d�finis, on met � jour la liste des ne d'axe1, de la liste des correspondances et des labels des �l�ments d'axe1
            // 4.1 - Si les 2 raws / kpis font partie de la m�me famille d'axe N
            // Note : on ne fait plus rien ici, on garde les couples qui vont par la suite servir lors des recherches des r�sultats dans les produits
            // 4.2 - Mise � jour des ne d'axe1

            if ($this->zoomPlus) {
                $this->neAxe1 = array($this->naAbcisseAxe1 => array_keys($common_ne));
                //$this->setTop(count(array_keys($common_ne)));
            } else {
                $this->neAxe1 = array($this->naAbcisseAxe1 => array_slice(array_keys($common_ne), 0, $this->getTop(), true));
            }

            // 4.2 - Mise � jour des correspondances par produit pour le produit du raw/kpi de tri et de filtre
            // On dispose d'un tri ou les produits tri et filtre sont les m�mes : on utilise les infos du tri
            if ((count($this->filter) == 0) || ($this->sortBy['product'] == $this->filter['product'])) {
                foreach ($common_ne as $ne_id => $ne_value) {
                    // 11:35 17/08/2009 GHX
                    //$this->equivalentNeAxe1[$this->sortBy['product']][$this->naAbcisseAxe1][$ne_id] = $ne_value['id'];
                    $this->equivalentNeAxe1[$ne_value['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_value['id'];
                    // 13:24 09/10/2009 SCT : ajout de la gestion des couleurs sur les NE
                    $this->couleurNeAxe1[$ne_value['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_value['color'];
                }
            }
            // On ne dispose pas de tri et les produits sont diff�rents (sous-jacent au premier cas conditionnel) : on initialise seulement le tableau d'equivalence du filtre
            else if (count($this->sortBy) == 0) {
                foreach ($common_ne as $ne_id => $ne_value) {
                    // 11:36 17/08/2009 GHX
                    //$this->equivalentNeAxe1[$this->filter['product']][$this->naAbcisseAxe1][$ne_id] = $ne_value['id'];
                    $this->equivalentNeAxe1[$ne_value['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_value['id'];
                    // 13:24 09/10/2009 SCT : ajout de la gestion des couleurs sur les NE
                    $this->couleurNeAxe1[$ne_value['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_value['color'];
                }
            }
            // Le tri et le filtre existent et les produits sont diff�rents : on initialise les 2 tableaux d'equivalence (tri et filtre)
            else {
                foreach ($common_ne as $ne_id => $ne_value) {
                    // 17:17 11/08/2009 GHX
                    // Correction d'un bug si on avait des �l�ments mapp�s et un filtre
                    // 11:36 17/08/2009 GHX
                    // $this->equivalentNeAxe1[$this->sortBy['product']][$this->naAbcisseAxe1][$ne_id] = $ne_orderby[$ne_id]['id'];
                    // $this->equivalentNeAxe1[$this->filter['product']][$this->naAbcisseAxe1][$ne_id] = $ne_filter[$ne_id]['id'];
                    $this->equivalentNeAxe1[$ne_orderby[$ne_id]['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_orderby[$ne_id]['id'];
                    $this->equivalentNeAxe1[$ne_filter[$ne_id]['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_filter[$ne_id]['id'];
                    // 13:24 09/10/2009 SCT : ajout de la gestion des couleurs sur les NE
                    $this->couleurNeAxe1[$ne_orderby[$ne_id]['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_orderby[$ne_id]['color'];
                    $this->couleurNeAxe1[$ne_filter[$ne_id]['id_product']][$this->naAbcisseAxe1][$ne_id] = $ne_filter[$ne_id]['color'];
                }
            }

            // 4.3 - D�finition des labels des �l�ments
            // On d�finit si les ne contiennent des �l�ments d'axeN

            $axeN_elements = false;

            $group_axe_sort = $this->GetGtmElementsGroupAndAxe($this->sortBy['product'], $this->sortBy['family']);

            // 04/02/2009 - Modif. benoit : correction de la condition ci-dessous o� on ne prenait pas en compte le fait que le filtre puisse �tre non d�fini
            // 12/05/2009 GHX
            // Modification de la condition
            if (($group_axe_sort['axeN'] == true) && (($this->sortBy['family'] == $this->filter['family']) || (count($this->filter) == 0))) {
                $axeN_elements = true;

                // 10:27 29/04/2009 GHX
                // R�cup�re la liste des produits qui ont un mapping
                $list_product_mapped = array_keys($this->equivalentNeAxe1);

                if ((count($this->filter) == 0) || ($this->sortBy['product'] == $this->filter['product'])) {
                    unset($list_product_mapped[array_search($this->sortBy['product'], $list_product_mapped)]);
                    if (count($list_product_mapped) > 0) {
                        foreach ($list_product_mapped as $idProductWithMapping) {
                            if (!array_key_exists($this->naAbcisseAxe1, $this->equivalentNeAxe1[$idProductWithMapping]))
                                continue;

                            foreach ($common_ne as $ne_id => $ne_value) {
                                $tmp_ne_id = explode(self::SEPARATOR, $ne_id);
                                $tmp_ne_value = explode(self::SEPARATOR, $ne_value['id']);

                                if (array_key_exists($tmp_ne_id[0], $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1])) {
                                    $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$ne_id] = $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$tmp_ne_id[0]] . self::SEPARATOR . $tmp_ne_value[1];
                                }
                            }
                        }
                    }
                } elseif (count($this->sortBy) == 0) {
                    unset($list_product_mapped[array_search($this->filter['product'], $list_product_mapped)]);
                    if (count($list_product_mapped) > 0) {
                        foreach ($list_product_mapped as $idProductWithMapping) {
                            if (!array_key_exists($this->naAbcisseAxe1, $this->equivalentNeAxe1[$idProductWithMapping]))
                                continue;

                            foreach ($common_ne as $ne_id => $ne_value) {
                                $tmp_ne_id = explode(self::SEPARATOR, $ne_id);
                                $tmp_ne_value = explode(self::SEPARATOR, $ne_value['id']);

                                if (array_key_exists($tmp_ne_id[0], $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1])) {
                                    $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$ne_id] = $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$tmp_ne_id[0]] . self::SEPARATOR . $tmp_ne_value[1];
                                }
                            }
                        }
                    }
                } else {
                    unset($list_product_mapped[array_search($this->sortBy['product'], $list_product_mapped)]);
                    if (count($list_product_mapped) > 0) {
                        foreach ($list_product_mapped as $idProductWithMapping) {
                            if (!array_key_exists($this->naAbcisseAxe1, $this->equivalentNeAxe1[$idProductWithMapping]))
                                continue;

                            foreach ($common_ne as $ne_id => $ne_value) {
                                $tmp_ne_id = explode(self::SEPARATOR, $ne_id);
                                $tmp_ne_value = explode(self::SEPARATOR, $ne_value['id']);

                                if (array_key_exists($tmp_ne_id[0], $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1])) {
                                    $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$ne_id] = $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$tmp_ne_id[0]] . self::SEPARATOR . $tmp_ne_value[1];
                                }
                            }
                        }
                    }
                    unset($list_product_mapped[array_search($this->filter['product'], $list_product_mapped)]);
                    if (count($list_product_mapped) > 0) {
                        foreach ($list_product_mapped as $idProductWithMapping) {
                            if (!array_key_exists($this->naAbcisseAxe1, $this->equivalentNeAxe1[$idProductWithMapping]))
                                continue;

                            foreach ($common_ne as $ne_id => $ne_value) {
                                $tmp_ne_id = explode(self::SEPARATOR, $ne_id);
                                $tmp_ne_value = explode(self::SEPARATOR, $ne_value['id']);

                                if (array_key_exists($tmp_ne_id[0], $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1])) {
                                    $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$ne_id] = $this->equivalentNeAxe1[$idProductWithMapping][$this->naAbcisseAxe1][$tmp_ne_id[0]] . self::SEPARATOR . $tmp_ne_value[1];
                                }
                            }
                        }
                    }
                }
            }

            // On d�finit les labels des ne
            $this->setNeLabel($common_ne, $axeN_elements, $this->sortBy['product'], $this->sortBy['family']);

            if ($this->debug) {
                $msg = "<span style='font-weight:bold'>"
                        . " Temps n�cessaire � la r�cup�ration des ne : " . round(($this->microtime_float() - $time_start), 4)
                        . " seconde(s) (Sort By : " . $time_sortBy . "s / Filter : " . $time_Filter . "s / Traitement PHP : " . round(microtime(true) - $time_start_php, 4) . "s)</span><br/>";

                echo utf8_encode($msg);
            }

            // 15:22 18/05/2009 GHX
            // Si on a un identifiant de GTM c'est qu'on est sur un sort By none au niveau du s�lecteur
            // donc on m�morise les NE par GTM
            if ($gtm_id != "") {
                $this->neAxe1ByGTM[$gtm_id] = $this->neAxe1;
                $this->neAxe1LabelByGTM[$gtm_id] = $this->neAxe1Label;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Permet de d�finir les ne en fonction du raw / kpi de tri
     *
     * @return array liste des ne li�s au raw / kpi de tri
     */
    public function getNeFromSortBy() {
        // BBX 03/12/2009. BZ 13164
        // On compte de nombre de NA en base
        $this->NumberOfNe = NeModel::getNumberOfNe($this->naAbcisseAxe1, $this->sortBy['product']);
        // Si aucun NE en topologie, on sort
        if ($this->NumberOfNe == 0)
            return Array();

        // D�finition du group_table et de la pr�sence / absence de l'axe3 du raw / kpi de tri

        $group_axe = $this->GetGtmElementsGroupAndAxe($this->sortBy['product'], $this->sortBy['family']);

        // Nombre de NE axe N
        // 05/12/2009 BBX : R�cup�ration du nombre de NE 3�me axe. BZ 13164
        $naAxeN = $this->naAxeN[$group_axe['axeN']][$this->sortBy['family']][0];
        if ($naAxeN != '') {
            $this->NumberOfNeAxeN = NeModel::getNumberOfNe($naAxeN, $this->sortBy['product']);
        }

        // D�finition du nom de la table de donn�es

        $sortby_table = $this->setDataTableName($group_axe['group_table'], $this->sortBy['type'], $this->sortBy['product'], $this->sortBy['family'], $group_axe['axeN']);

        // D�finition de la condition sur la s�lection d'�l�ments
        // 06/06/2011 MMT DE 3rd Axis gestion des conditions 3eme axe
        $na_selection_conditions = $this->setNeAxe1SubQuery($this->sortBy['product']);

        // D�finition des champs d'axe N � r�cup�rer via la requete

        $axe_n_columns = $this->setAxeNColumns($this->sortBy['product'], $this->sortBy['family'], $group_axe['axeN'], true, true);

        // 18:13 14/05/2009 GHX
        // Ajout de champ pour le SELECT
        if ($axe_n_columns != "") {
            // $axe_n_columns .= 'b.eor_label AS label_axen, b.eor_id_codeq AS codeq_axen,';
            $sortby_table .= ', edw_object_ref b';
        }

        // D�finition de la condition sur les N�mes axes (pour l'instant, seulement le 3�me axe)
        // 06/06/2011 MMT DE 3rd Axis gestion des conditions 3eme axe
        $axe_n_conditions = $this->setAxeNCondition($this->sortBy['product'], $this->sortBy['family'], $group_axe['axeN']);

        // 02/03/2009 - Modif. benoit : en mode Overtime, on d�finit pas un intervalle mais on va boucler sur l'ensemble des valeurs de ta correspondant � la p�riode
        // jusqu'� trouver des r�sultats

        $db = Database::getConnection($this->sortBy['product']);
        $db->setDebug($this->debug);

        if ($this->mode == 'overtime') {
            $ta_interval = getTAInterval($this->ta, $this->taValue, $this->period, "asc");

            $i = 0;
            $values_found = false;

            $max_nb_queries = get_sys_global_parameters('dashboard_display_max_nb_queries_overtime');

            $period = (($max_nb_queries > $this->period) ? $this->period : $max_nb_queries);

            // On execute la requete tant que le compteur est diff�rent de la p�riode (intervalle de valeurs de ta) ou que des valeurs ne sont pas d�finies

            while (($i != $period) && ($values_found === false)) {
                // 16:19 08/10/2009 SCT : modification de la requ�te => ajout de la colonne color
                // 09:22 16/10/2009 SCT : BZ 12078 => erreur SQL lors de l'affichage en overtime "eor.color" devient "eor_color"
                // 06/06/2011 MMT DE 3rd Axis refactoring des conditions 3eme axe
                $sql = "
					--- getNeFromSortBy() en mode overtime
					SELECT DISTINCT
						a.eor_id, a.eor_label, a.eor_id_codeq, a.eor_color,
						{$axe_n_columns}
						{$this->sortBy['name']}
					FROM
						edw_object_ref a,
						{$sortby_table}
						{$na_selection_conditions['tables']}
						{$axe_n_conditions['tables']}

					WHERE
						-- CONDITIONS TABLE DATA
						{$this->ta} = {$ta_interval[$i]}
						AND {$this->sortBy['name']} IS NOT NULL

						-- RECUPERATION DES NA
						{$na_selection_conditions['conditions']}
						-- RECUPERATION DES NA Ax3
						{$axe_n_conditions['conditions']}
					ORDER BY
						{$this->sortBy['name']} {$this->sortBy['asc_desc']}
					{$this->limitSortBy}
				";

                // Execution de la requete
                // 06/06/2011 MMT DE 3rd Axis utilisation de execMainQuery
                $row = $this->execMainQuery($db, $sql);
                // Si la requete retourne des r�sultats, on arrete. Sinon, on continue
                if ($db->getNumRows() > 0)
                    $values_found = true;

                $i += 1;
            }
        }
        else { // mode overnetwork
            // 16:19 08/10/2009 SCT : modification de la requ�te => ajout de la colonne color
            // 06/06/2011 MMT DE 3rd Axis refactoring des conditions 3eme axe
            $sql = "
					--- getNeFromSortBy() en mode overnetwork
					SELECT DISTINCT
						a.eor_id, a.eor_label, a.eor_id_codeq, a.eor_color,
						{$axe_n_columns}
						{$this->sortBy['name']}
					FROM
						edw_object_ref a,
						{$sortby_table}
						{$na_selection_conditions['tables']}
						{$axe_n_conditions['tables']}
					WHERE
						-- CONDITIONS TABLE DATA
						{$this->ta} = {$this->taValue}
						AND {$this->sortBy['name']} IS NOT NULL

						-- RECUPERATION DES NA
						{$na_selection_conditions['conditions']}
						-- RECUPERATION DES NA Ax3
						{$axe_n_conditions['conditions']}
					ORDER BY
						{$this->sortBy['name']} {$this->sortBy['asc_desc']}
					{$this->limitSortBy}
				";
            // Execution de la requete
            // 06/06/2011 MMT DE 3rd Axis utilisation de execMainQuery
            $row = $this->execMainQuery($db, $sql);
        }

        // BBX 03/12/2009. BZ 13164
        // Pr�paration des tableau pour stocker les r�sultats et stocker les NE rencontr�s
        $ne = array();
        $NeToExclude = array();

        // Stockage des r�sultats apr�s reformatage dans un tableau de ne
        while ($elem = $db->getQueryResults($row, 1)) {
            // NE rencontr�s (� exclure pour els requ�tes suivantes)
            $NeToExclude[] = $elem['eor_id'];
            // R�cup�ration des r�sultats trait�s
            $result = $this->fetchSortByNeValues($elem, $group_axe);
            $ne[$result[0]] = $result[1];
        }

        // Si on est en Overtime, on regarde si on doit compl�ter la liste des NE
        if ($this->mode == 'overtime') {
            // Nombre de NE :
            // 08/12/2009 BBX : on compte d�sormais �galement le nombre de NE 3eme axe. BZ 13164
            $nbNeInTopology = isset($this->NumberOfNeAxeN) ? ($this->NumberOfNeAxeN * $this->NumberOfNe) : $this->NumberOfNe;
            // Si le nombre de NE est inf�rieur au nombre de NE pr�sents en topologie, et que le TOP n'est pas atteint
            // On va chercher � r�cup�rer des NE suppl�mentaires sur la p�riode
            while (($i != $period) && (count($ne) < $nbNeInTopology) && (count($ne) < $this->topOT)) {
                // On exclu les NE d�j� r�cup�r�s
                $excludeCondition = (count($ne) > 0) ? "AND a.eor_id NOT IN ('" . implode(',', $NeToExclude) . "')" : "";

                // 16:19 08/10/2009 SCT : modification de la requ�te => ajout de la colonne color
                // 09:22 16/10/2009 SCT : BZ 12078 => erreur SQL lors de l'affichage en overtime "eor.color" devient "eor_color"
                // 06/06/2011 MMT DE 3rd Axis refactoring des conditions 3eme axe
                $sql = "
					--- getNeFromSortBy() en mode overtime
					SELECT DISTINCT
						a.eor_id, a.eor_label, a.eor_id_codeq, a.eor_color,
						{$axe_n_columns}
						{$this->sortBy['name']}
					FROM
						edw_object_ref a,
						{$sortby_table}
						{$na_selection_conditions['tables']}
						{$axe_n_conditions['tables']}
					WHERE
						-- CONDITIONS TABLE DATA
						{$this->ta} = {$ta_interval[$i]}
						AND {$this->sortBy['name']} IS NOT NULL

						-- RECUPERATION DES NA
						{$na_selection_conditions['conditions']}
						-- RECUPERATION DES NA Ax3
						{$axe_n_conditions['conditions']}
						-- EXCLUDE
						{$excludeCondition}

					ORDER BY
						{$this->sortBy['name']} {$this->sortBy['asc_desc']}
					{$this->limitSortBy}
				";

                // Execution de la requete
                // 06/06/2011 MMT DE 3rd Axis utilisation de execMainQuery
                $row = $this->execMainQuery($db, $sql);

                // Si la requete retourne des r�sultats, on les r�cup�re
                if ($db->getNumRows() > 0) {
                    while ($elem = $db->getQueryResults($row, 1)) {
                        // R�cup�ration des r�sultats trait�s
                        $result = $this->fetchSortByNeValues($elem, $group_axe);
                        $ne[$result[0]] = $result[1];
                    }
                }

                $i += 1;
            }
        }
        // FIN BZ 13164
        return $ne;
    }

    /**
     * Retourne l'id et les valeurs � placer dans le tableau des NE (liste des NE) en fonction des r�sultats d'une requ�te
     * Cr�ation de la fonction lors de la correction du bug BZ 13164
     *
     * @param Array : tableau de r�sultat
     * @param Array : tableaud des axes
     * @return array : id NE, valeurs NE
     */
    public function fetchSortByNeValues($elem, $group_axe) {
        $ne_axe1_id = (($elem['eor_id_codeq'] == "") ? $elem['eor_id'] : $elem['eor_id_codeq']);
        $ne_axe1_label = (($elem['eor_label'] == "") ? $elem['eor_id'] : $elem['eor_label']);
        // 16:19 08/10/2009 SCT : ajout d'un nouveau tableau pour la gestion des couleurs
        $ne_axe1_color = (($elem['eor_color'] == "") ? '' : $elem['eor_color']);

        // 11:33 17/08/2009 GHX
        // Ajout de l'id produit dans le tableau
        // 11:16 09/10/2009 SCT : ajout de la gestion de la couleur sur les axes
        $ne_values = array('id' => $elem['eor_id'], 'label' => $ne_axe1_label, 'values' => array($this->sortBy['name'] => $elem[strtolower($this->sortBy['name'])]), 'id_product' => $this->sortBy['product'], 'color' => $ne_axe1_color);

        // Si un/plusieurs axe(s) est/sont d�finie(s), on concatene les ne de tous les axes pour d�finir la cl� du tableau de r�sultats

        $ne_tab_key = $ne_axe1_id;

        // 12/05/2009 GHX
        // Modification de la condition pour prendre en compte les combinaisons
        if (($group_axe['axeN'] == true) && !$this->checkAxe3OnAllFamilies) {

            $na_axe_n = $this->naAxeN[$this->sortBy['product']][$this->sortBy['family']];

            for ($j = 0; $j < count($na_axe_n); $j++) {

                $ne_axe_n_codeq_label = explode(self::SEPARATOR, $elem[$na_axe_n[$j] . '_codeq_label']);

                //$ne_tab_key .= self::SEPARATOR.$elem[$na_axe_n[$j]];
                //$ne_axe_n_id	= (($ne_axe_n_codeq_label[0] == "") ? $elem[$na_axe_n[$j]] : $ne_axe_n_codeq_label[0]);
                // 15:10 26/10/2009 GHX
                // Modification de la structure du tableau
                $ne_tab_key .= self::SEPARATOR . (($ne_axe_n_codeq_label[0] == "") ? $elem[$na_axe_n[$j]] : $ne_axe_n_codeq_label[0]);
                $ne_axe_n_id = $elem[$na_axe_n[$j]];

                $ne_axe_n_label = (($ne_axe_n_codeq_label[1] == "") ? $elem[$na_axe_n[$j]] : $ne_axe_n_codeq_label[1]);
                // 11:22 09/10/2009 SCT : ajout de la couleur
                $ne_axe_n_color = $elem['hn_color'];

                $ne_values['id'] .= self::SEPARATOR . $ne_axe_n_id;
                $ne_values['label'] .= self::SEPARATOR . $ne_axe_n_label;
                // 11:22 09/10/2009 SCT : ajout de la couleur
                $ne_values['color'] .= self::SEPARATOR . $ne_axe_n_color;
            }
        }
        return Array($ne_tab_key, $ne_values);
    }

    /**
     * Permet de d�finir les ne en fonction du raw / kpi de filtre
     *
     * @return array liste des ne li�s au raw / kpi de filtre
     */
    private function getNeFromFilter() {
        // On compte de nombre de NA en base
        $this->NumberOfNe = NeModel::getNumberOfNe($this->naAbcisseAxe1, $this->filter['product']);
        // Si aucun NE en topologie, on sort
        if ($this->NumberOfNe == 0)
            return Array();

        // D�finition du group_table et de la pr�sence / absence de l'axe3 du raw / kpi de tri

        $group_axe = $this->GetGtmElementsGroupAndAxe($this->filter['product'], $this->filter['family']);

        // D�finition du nom de la table de donn�es

        $filter_table = $this->setDataTableName($group_axe['group_table'], $this->filter['type'], $this->filter['product'], $this->filter['family'], $group_axe['axeN']);

        // D�finition des champs d'axe N � r�cup�rer via la requete

        $axe_n_columns = $this->setAxeNColumns($this->filter['product'], $this->filter['family'], $group_axe['axeN'], true, true);

        // 18:13 15/05/2009 GHX
        // Ajout de champ pour le SELECT
        if ($axe_n_columns != "") {
            // $axe_n_columns .= 'b.eor_label AS label_axen, b.eor_id_codeq AS codeq_axen,';
            $filter_table .= ', edw_object_ref b';
        }

        // 06/06/2011 MMT DE 3rd Axis refactoring des conditions 1er et 3eme axe
        // D�finition de la condition sur la s�lection d'�l�ments

        $na_selection_conditions = $this->setNeAxe1SubQuery($this->filter['product']);

        // 16:27 25/06/2009 GHX
        // Si on a d�j� une condition sur les NA pas la peine de mettre celle-ci sinon impossible d'avoir des �l�ments en r�seaux
        if ($na_selection_conditions['conditions'] != "") {
            $ne_axe1_sel = '';
        }

        // D�finition de la condition sur les N�mes axes (pour l'instant, seulement le 3�me axe)
        // 06/06/2011 MMT DE 3rd Axis changement de definition de la fonction setAxeNCondition
        $axe_n_conditions = $this->setAxeNCondition($this->filter['product'], $this->filter['family'], $group_axe['axeN']);

        // D�finition de la condition sur les ta

        /* // 25/02/2009 - Modif. benoit : quelque soit le mode on a toujours la meme condition sur la valeur de ta � savoir,
          // la s�lection uniquement sur la valeur d�finie dans le s�lecteur

          $ta_cond = $this->ta." = ".$this->taValue; */

        //$ta_cond = $this->setTACondition();
        // 02/03/2009 - Modif. benoit : en mode Overtime, on d�finit pas un intervalle mais on va boucler sur l'ensemble des valeurs de ta correspondant � la p�riode
        // jusqu'� trouver des r�sultats

        $db = Database::getConnection($this->filter['product']);
        $db->setDebug($this->debug);

        // 10:44 25/05/2009 GHX
        // Le filtre se fait toujours sur la date s�lectionn� que l'on soit en mode overtime ou overnetwork
        // 12:05 09/10/2009 SCT : ajout de la couleur sur les NE
        // 17:25 29/10/2009 MPR : Correction du bug 6924 : ajout de la virgule apr�s le a.eor_color car erreur SQL
        // 06/06/2011 MMT DE 3rd Axis refactoring des conditions 3eme axe
        $sql = "
				--- getNEFromFilter()
				SELECT DISTINCT
					a.eor_id, a.eor_label, a.eor_id_codeq, a.eor_color,
					{$axe_n_columns}
					{$this->filter['name']}
				FROM
					edw_object_ref a,
					{$filter_table}
					{$na_selection_conditions['tables']}
					{$axe_n_conditions['tables']}
				WHERE
					-- CONDITIONS TABLE DATA
					{$this->ta} = {$this->taValue}
					AND {$this->filter['name']} IS NOT NULL
					AND {$this->filter['name']} {$this->filter['operand']} {$this->filter['value']}

					-- RECUPERATION DES NA
					{$na_selection_conditions['conditions']}
					-- RECUPERATION DES NA Ax3
					{$axe_n_conditions['conditions']}
			";

        $ne = array();

        // 06/06/2011 MMT DE 3rd Axis utilisation de execMainQuery
        $row = $this->execMainQuery($db, $sql);

        while ($elem = $db->getQueryResults($row, 1)) {

            $ne_axe1_id = (($elem['eor_id_codeq'] == "") ? $elem['eor_id'] : $elem['eor_id_codeq']);
            $ne_axe1_label = (($elem['eor_label'] == "") ? $elem['eor_id'] : $elem['eor_label']);
            // 16:19 08/10/2009 SCT : ajout d'un nouveau tableau pour la gestion des couleurs
            $ne_axe1_color = (($elem['eor_color'] == "") ? '' : $elem['eor_color']);

            // 11:34 17/08/2009 GHX
            // Ajout de l'id produit dans le tableau
            // 11:16 09/10/2009 SCT : ajout de la gestion de la couleur sur les axes
            $ne_values = array('id' => $elem['eor_id'], 'label' => $ne_axe1_label, 'values' => array($this->filter['name'] => $elem[strtolower($this->filter['name'])]), 'id_product' => $this->filter['product'], 'color' => $ne_axe1_color);

            // Si un/plusieurs axe(s) est/sont d�finie(s), on concatene les ne de tous les axes pour d�finir la cl� du tableau de r�sultats

            $ne_tab_key = $ne_axe1_id;
            // 28/12/2010 - MPR : Correction du bz19867
            // Gestion du filtre lorsque l'�l�ment 3�me axe est sp�cifi� et que le filtre raw/kpi l'est �galement
            // 11/10/2012 ACS BZ 29729 Condition on raw/kpi is not apply when filtering on a Network element
            if (($group_axe['axeN'] == true) && (count($this->neAxeN[$this->filter['product']][$this->filter['family']]) > 0 )) {
                $na_axeN = $this->naAxeN[$this->filter['product']][$this->filter['family']][0];
                $ne_tab_key = $ne_axe1_id . self::SEPARATOR . $elem[$na_axeN];
            } else if (($group_axe['axeN'] == true) && (count($this->neAxeN[$this->filter['product']][$this->filter['family']]) == 0)) {
                $na_axe_n = $this->naAxeN[$this->filter['product']][$this->filter['family']];

                for ($j = 0; $j < count($na_axe_n); $j++) {
                    $ne_axe_n_codeq_label = explode(self::SEPARATOR, $elem[$na_axe_n[$j] . '_codeq_label']);

                    // 16:38 26/10/2009 GHX
                    // Modification de la structure
                    $ne_tab_key .= self::SEPARATOR . (($ne_axe_n_codeq_label[0] == "") ? $elem[$na_axe_n[$j]] : $ne_axe_n_codeq_label[0]);
                    $ne_axe_n_id = $elem[$na_axe_n[$j]];

                    $ne_axe_n_label = (($ne_axe_n_codeq_label[1] == "") ? $elem[$na_axe_n[$j]] : $ne_axe_n_codeq_label[1]);
                    // 11:22 09/10/2009 SCT : ajout de la couleur
                    $ne_axe_n_color = $elem['hn_color'];

                    $ne_values['id'] .= self::SEPARATOR . $ne_axe_n_id;
                    $ne_values['label'] .= self::SEPARATOR . $ne_axe_n_label;
                    // 11:22 09/10/2009 SCT : ajout de la couleur
                    $ne_values['color'] .= self::SEPARATOR . $ne_axe_n_color;
                }
            }
            $ne[$ne_tab_key] = $ne_values;
        }
        return $ne;
    }

    /**
     * Permet de d�finir les ne en se basant sur la table de donn�es du raw / kpi de tri et en triant les �l�ments par ordre alphab�tique.
     * Cette m�thode est appel�e lorsqu'il n'existe aucun r�sultats issues du tri et du filtre
     *
     * @return array liste des ne tri�es par odre alphab�tique
     */
    private function getNeAlphaSort() {
        // On compte de nombre de NA en base
        $this->NumberOfNe = NeModel::getNumberOfNe($this->naAbcisseAxe1, $this->sortBy['product']);
        // Si aucun NE en topologie, on sort
        if ($this->NumberOfNe == 0)
            return Array();


        // D�finition du group_table et de la pr�sence / absence de l'axe3 du raw / kpi de tri

        $group_axe = $this->GetGtmElementsGroupAndAxe($this->sortBy['product'], $this->sortBy['family']);

        // D�finition du nom de la table de donn�es

        $sortby_table = $this->setDataTableName($group_axe['group_table'], $this->sortBy['type'], $this->sortBy['product'], $this->sortBy['family'], $group_axe['axeN']);

        // D�finition de la condition sur la s�lection d'�l�ments
        // 06/06/2011 MMT DE 3rd Axis refactoring des conditions 1er et 3eme axe
        $na_selection_conditions = $this->setNeAxe1SubQuery($this->sortBy['product']);

        // D�finition des champs d'axe N � r�cup�rer via la requete

        $axe_n_columns = $this->setAxeNColumns($this->sortBy['product'], $this->sortBy['family'], $group_axe['axeN'], true, true);

        // 18:13 15/05/2009 GHX
        // Ajout de champ pour le SELECT
        if ($axe_n_columns != "") {
            // $axe_n_columns .= 'b.eor_label AS label_axen, b.eor_id_codeq AS codeq_axen,';
            $sortby_table .= ', edw_object_ref b';
        }

        // On supprime la derni�re virgule de la chaine $axe_n_columns

        if ($axe_n_columns != "")
            $axe_n_columns = ", " . substr($axe_n_columns, 0, strlen($axe_n_columns) - 2);

        // D�finition de la condition sur les N�mes axes (pour l'instant, seulement le 3�me axe)
        // 06/06/2011 MMT DE 3rd Axis changement de definition de la fonction setAxeNCondition
        $axe_n_conditions = $this->setAxeNCondition($this->sortBy['product'], $this->sortBy['family'], $group_axe['axeN']);

        // D�finition de la condition sur les ta
        // En mode Overtime, on d�finit pas un intervalle mais on va boucler sur l'ensemble des valeurs de ta correspondant � la p�riode
        // jusqu'� trouver des r�sultats

        $db = Database::getConnection($this->sortBy['product']);
        $db->setDebug($this->debug);

        if ($this->mode == "overtime") {
            $ta_interval = getTAInterval($this->ta, $this->taValue, $this->period, "asc");

            $i = 0;
            $values_found = false;

            $max_nb_queries = get_sys_global_parameters('dashboard_display_max_nb_queries_overtime');

            $period = (($max_nb_queries > $this->period) ? $this->period : $max_nb_queries);

            // On execute la requete tant que le compteur est diff�rent de la p�riode (intervalle de valeurs de ta) ou que des valeurs ne sont pas d�finies

            while (($i != $period) && ($values_found === false)) {
                // 16:19 08/10/2009 SCT : modification de la requ�te => ajout de la colonne color
                // 06/06/2011 MMT DE 3rd Axis refactoring des conditions 3eme axe
                $sql = "
					--- getNeAlphaSort() en mode overtime
					SELECT DISTINCT
						a.eor_id, a.eor_label, a.eor_id_codeq, a.eor_color
						{$axe_n_columns}
					FROM
						edw_object_ref a,
						{$sortby_table}
						{$na_selection_conditions['tables']}
						{$axe_n_conditions['tables']}
					WHERE
						-- CONDITIONS TABLE DATA
						{$this->ta} = {$ta_interval[$i]}

						-- RECUPERATION DES NA
						{$na_selection_conditions['conditions']}
						-- RECUPERATION DES NA Ax3
						{$axe_n_conditions['conditions']}
					ORDER BY a.eor_id ASC
					LIMIT " . $this->getTop();

                // Execution de la requete
                // 06/06/2011 MMT DE 3rd Axis utilisation de execMainQuery
                $row = $this->execMainQuery($db, $sql);

                // Si la requete retourne des r�sultats, on arrete. Sinon, on continue
                if ($db->getNumRows() > 0)
                    $values_found = true;

                $i += 1;
            }
        }
        else { // mode overnetwork
            // 16:19 08/10/2009 SCT : modification de la requ�te => ajout de la colonne color
            $sql = "
					--- getNeAlphaSort() en mode overnetwork
					SELECT DISTINCT
						a.eor_id, a.eor_label, a.eor_id_codeq, a.eor_color
						{$axe_n_columns}
					FROM
						edw_object_ref a,
						{$sortby_table}
						{$na_selection_conditions['tables']}
						{$axe_n_conditions['tables']}
					WHERE
						-- CONDITIONS TABLE DATA
						{$this->ta} = {$this->taValue}

						-- RECUPERATION DES NA
						{$na_selection_conditions['conditions']}

						-- RECUPERATION DES NA Ax3
						{$axe_n_conditions['conditions']}

					ORDER BY eor_id ASC
					LIMIT " . $this->getTop();

            // Execution de la requete
            // 06/06/2011 MMT DE 3rd Axis utilisation de execMainQuery
            $row = $this->execMainQuery($db, $sql);
        }

        // Stockage des r�sultats apr�s reformatage dans un tableau de ne

        $ne = array();

        while ($elem = $db->getQueryResults($row, 1)) {

            $ne_axe1_id = (($elem['eor_id_codeq'] == "") ? $elem['eor_id'] : $elem['eor_id_codeq']);
            $ne_axe1_label = (($elem['eor_label'] == "") ? $elem['eor_id'] : $elem['eor_label']);
            // 16:19 08/10/2009 SCT : ajout d'un nouveau tableau pour la gestion des couleurs
            $ne_axe1_color = (($elem['eor_color'] == "") ? '' : $elem['eor_color']);

            // 11:34 17/08/2009 GHX
            // Ajout de l'id produit dans le tableau
            // 11:16 09/10/2009 SCT : ajout de la gestion de la couleur sur les axes
            $ne_values = array('id' => $elem['eor_id'], 'label' => $ne_axe1_label, 'values' => array($this->sortBy['name'] => $elem[strtolower($this->sortBy['name'])]), 'id_product' => $this->sortBy['product'], 'color' => $ne_axe1_color);

            // Si un/plusieurs axe(s) est/sont d�finie(s), on concatene les ne de tous les axes pour d�finir la cl� du tableau de r�sultats

            $ne_tab_key = $ne_axe1_id;

            // 12/05/2009 GHX
            // Modification de la condition
            if (($group_axe['axeN'] == true)) {

                $na_axe_n = $this->naAxeN[$this->sortBy['product']][$this->sortBy['family']];

                for ($j = 0; $j < count($na_axe_n); $j++) {
                    $ne_tab_key .= self::SEPARATOR . $elem[$na_axe_n[$j]];

                    $ne_axe_n_codeq_label = explode(self::SEPARATOR, $elem[$na_axe_n[$j] . '_codeq_label']);

                    $ne_axe_n_id = (($ne_axe_n_codeq_label[0] == "") ? $elem[$na_axe_n[$j]] : $ne_axe_n_codeq_label[0]);
                    $ne_axe_n_label = (($ne_axe_n_codeq_label[1] == "") ? $elem[$na_axe_n[$j]] : $ne_axe_n_codeq_label[1]);
                    // 11:22 09/10/2009 SCT : ajout de la couleur
                    $ne_axe_n_color = $elem['hn_color'];

                    $ne_values['id'] .= self::SEPARATOR . $ne_axe_n_id;
                    $ne_values['label'] .= self::SEPARATOR . $ne_axe_n_label;
                    // 11:22 09/10/2009 SCT : ajout de la couleur
                    $ne_values['color'] .= self::SEPARATOR . $ne_axe_n_color;
                }
            }
            $ne[$ne_tab_key] = $ne_values;
        }
        return $ne;
    }

    /**
     * D�finition de la condition SQL sur le / les axe(s) n
     *
     * 	18:07 14/05/2009 GHX
     * 		- Ajout du param�tre$axeNLabel
     * 06/06/2011 MMT DE 3rd Axis
     * 		- appel setNeAxeSubQuery et fusion avec gestion 1er axe
     *
     * @param int $product identifiant du produit
     * @param string $family nom de la famille
     * @param boolean $axeN pr�sence / abscence de l'axe n
     * @return string condition SQL
     */
    private function setAxeNCondition($product, $family, $axeN) {
        $ret = "";
        if ($axeN && (count($this->naAxeN[$product][$family]) > 0) && !$this->checkAxe3OnAllFamilies) {

            // 06/06/2011 MMT DE 3rd Axis
            // si pas De selction NA 3eme axe (cas dash multi famille avec diff 3eme axe)
            // on prend le premier NA dans la liste des neAxe fournis
            $neAxe = $this->neAxeN[$product][$family];
            if (empty($this->naAbcisseAxeN)) {
                $listeNAs = array_keys($neAxe);
                $naAbcisse = $listeNAs[0];
            } else {
                $naAbcisse = $this->naAbcisseAxeN;
            }

            $ret = $this->setNeAxeSubQuery($neAxe, 3, $naAbcisse, $this->pathsAxeN, $product);
        }
        return $ret;
    }

    /**
     * Permet de d�finir le nom d'une table de donn�es � partir d'un group_table, d'un type de donn�es, d'un identifiant produit
     * et de la pr�sence / absence d'un axe N
     *
     * @param string $group_table nom du groupe
     * @param string $data_type nom du type (raw / kpi)
     * @param int $product identifiant du produit
     * @param boolean $axeN pr�sence / abscence de l'axe n
     * @return string nom de la table de donn�es
     */
    private function setDataTableName($group_table, $data_type, $product, $family, $axeN = false) {
        $na_for_table = $this->naAbcisseAxe1;

        if (($axeN == true) && (count($this->naAxeN[$product][$family]) > 0)) {
            $na_for_table .= "_" . (implode("_", $this->naAxeN[$product][$family]));
        }

        return $group_table . "_" . $data_type . "_" . $na_for_table . "_" . $this->ta;
    }

    /**
     * D�finition de la condition SQL sur la TA
     *
     * @return string condition SQL sur la TA
     */
    private function setTACondition() {
        $ta_condition = "";

        // 17:16 05/05/2009 GHX
        // Ajout d'une condition dans le cas ou la p�riode vaut 1 pas la peine de faire une recherche sur une plage de donn�es
        if ($this->mode == "overtime" && $this->period > 1) {
            $ta_condition = $this->ta . " >= " . getTAMinusPeriod($this->ta, $this->taValue, $this->period) . " AND " . $this->ta . " <= " . $this->taValue;
        } else {
            $ta_condition = $this->ta . " = " . $this->taValue;
        }

        return $ta_condition;
    }

    /**
     * D�finition des colonnes d'axe n � inclure dans les requetes SQL
     * 	10:11 06/05/2009 GHX
     * 	 	- Ajout du param�tre  $splitAxeN (remplament)
     *
     * @param int $product identifiant du produit
     * @param boolean $axeN pr�sence / abscence de l'axe n
     * @param boolean $splitAxeN
     * @return string liste des colonnes d'axe n � inclure dans les requetes
     */
    private function setAxeNColumns($product, $family, $axeN = false, $splitAxeN = true, $axeNLabel = false) {
        $axe_n_columns = "";

        if (($axeN == true) && (count($this->naAxeN[$product][$family]) > 0) && !$this->checkAxe3OnAllFamilies) {
            $colums_n = array();

            for ($i = 0; $i < count($this->naAxeN[$product][$family]); $i++) {
                $column = $this->naAxeN[$product][$family][$i];
                if ($splitAxeN && $axeNLabel) {
                    $colums_n[] = "COALESCE(b.eor_id_codeq, '') || '" . self::SEPARATOR . "' || COALESCE(b.eor_label, '') AS " . $column . '_codeq_label';
                }
                $colums_n[] = $column;
            }

            // 10:11 06/05/2009 GHX
            // Prise en compte du dernier param�tre
            // Si l'on veut que les axes N soient consid�r�s comme des colonnes diff�rentes ou non
            if ($splitAxeN == true) {
                $axe_n_columns = implode(", ", $colums_n) . ", ";
                // 11:19 09/10/2009 SCT : ajout de la couleur sur les axes
                $axe_n_columns .= 'b.eor_color AS hn_color, ';
            } else {
                $axe_n_columns = "||'" . self::SEPARATOR . "'||" . implode("||'" . self::SEPARATOR . "'||", $colums_n);
            }
        }

        return $axe_n_columns;
    }

    /**
     * D�finition des labels des ne
     *
     * @param array $ne liste des ne
     * @param boolean $axeN pr�sence / abscence de l'axe n
     * @param int $product identifiant du produit
     * @param string $family nom de la famille
     */
    private function setNeLabel($ne, $axeN = false, $product = '', $family = '') {
        // On r�cup�re les identifiants des ne (cl� du tableau '$ne')
        $ne_id = array_keys($ne);
        $ne_axe1 = array();
        $ne_axe1_label = array();
        $ne_axeN = array();
        $ne_axeN_label = array();

        // Si les ne sont des �l�ments axe1/axe3, on s�pare les 2 types de ne que l'on stocke dans des tableaux distincts
        if ($axeN) {
            for ($i = 0; $i < count($ne_id); $i++) {
                $ne_id_tab = explode(self::SEPARATOR, $ne_id[$i]);
                $ne_label_tab = explode(self::SEPARATOR, $ne[$ne_id[$i]]['label']);
                $ne_id_value_tab = explode(self::SEPARATOR, $ne[$ne_id[$i]]['id']);

                // Traitement des ne d'axe 1
                $ne_axe1[$this->naAbcisseAxe1][$ne_id_tab[0]] = $ne_id_value_tab[0];
                $ne_axe1_label[$this->naAbcisseAxe1][$ne_id_tab[0]] = $ne_label_tab[0];

                // Traitement des ne d'axe N (pour l'instant, on ne traite que les ne d'axe 3)
                $ne_axeN[$ne_id_tab[1]] = $ne_id_value_tab[1];
                $ne_axeN_label[$ne_id_tab[1]] = $ne_label_tab[1];
            }
        } else { // Un seul axe ou pr�sence d'un axe N mais avec une valeur pr�cise pour chaque na d'axe N
            for ($i = 0; $i < count($ne_id); $i++) {
                // Traitement des ne d'axe 1
                $ne_axe1[$this->naAbcisseAxe1][$ne_id[$i]] = $ne[$ne_id[$i]]['id'];
                $ne_axe1_label[$this->naAbcisseAxe1][$ne_id[$i]] = $ne[$ne_id[$i]]['label'];
            }
        }

        // Recherche des valeurs des ne d'axe1 dont les id, repr�sent�es par la cl�, sont diff�rentes des id contenues dans les valeurs
        $ne_axe1_no_label = array();
        foreach ($ne_axe1[$this->naAbcisseAxe1] as $id => $id_data) {
            if ($id == $id_data) {
                // 22/11/2011 BBX
                // BZ 24764 : correction des notices php
                //$ne_axe1_label[$id] = $ne_axe1_label[$id];
                if (!isset($ne_axe1_label[$id]))
                    $ne_axe1_label[$id] = null;
            }
            else {
                $ne_axe1_no_label[] = $id;
            }
        }

        // On va chercher dans la table 'edw_object_ref' du "masterTopo" les labels des �l�ments inconnus
        if (count($ne_axe1_no_label) > 0) {
            // 11:51 26/10/2009 GHX
            // Modification des requetes SQL suivantes pour directement prendre l'ID si le label est null
            // Ceci permet d'�viter une condition PHP pour le faire
            $sql = " SELECT eor_id, COALESCE(eor_label, eor_id) AS eor_label, eor_color FROM edw_object_ref"
                    . " WHERE eor_obj_type = '" . $this->naAbcisseAxe1 . "'"
                    . " AND eor_on_off = 1"
                    . " AND eor_id IN (" . implode(", ", array_map(array($this, 'labelizeValue'), $ne_axe1_no_label)) . ")";

            $db = Database::getConnection($this->masterTopo);
            $db->setDebug($this->debug);
            $ne_label = $db->getAll($sql);

            for ($i = 0; $i < count($ne_label); $i++) {
                // 11:55 29/04/2009 GHX
                // Si pas de label on met l'identifiant de l'�l�ment r�seau
                $ne_axe1_label[$this->naAbcisseAxe1][$ne_label[$i]['eor_id']] = $ne_label[$i]['eor_label'];
            }
        }

        // Recherche de la valeur de la na d'axe 1 lorsque celle-ci est diff�rente de la na d'axe 1 affich�e en abcisse
        if ($this->naAxe1 != $this->naAbcisseAxe1) {
            $ne_axe1_select = $this->neAxe1Init[$this->naAxe1][0];

            $sql = " SELECT COALESCE(eor_label, eor_id) AS eor_label, eor_color FROM edw_object_ref"
                    . " WHERE eor_obj_type = '" . $this->naAxe1 . "'"
                    . " AND eor_on_off = 1"
                    . " AND eor_id = '" . $ne_axe1_select . "'";

            $db = Database::getConnection($product);
            $db->setDebug($this->debug);
            $row = $db->getRow($sql);

            $ne_axe1_label[$this->naAxe1][$ne_axe1_select] = $row['eor_label'];
        }

        $this->neAxe1Label = $ne_axe1_label;

        // Recherche des valeurs des ne d'axe N (ici, seulement le cas de l'axe 3 est trait�)
        if (count($ne_axeN) > 0) {
            $ne_axeN_no_label = array();
            foreach ($ne_axeN as $id => $id_data) {
                if ($id == $id_data) {
                    //$ne_axeN_label[$family][$id] = $ne_axeN_label[$id];
                    $this->neAxeNLabel[$id] = $ne_axeN_label[$id];
                } else {
                    $ne_axeN_no_label[$id] = $id;
                }
            }

            // On va chercher dans la table 'edw_object_ref' du produit axe N les labels des �l�ments inconnus
            // 17:08 06/05/2009 GHX
            // Condition incorecte
            if (count($ne_axeN_no_label) > 0) {
                $na_axeN = $this->naAxeN[$product][$family];

                $sql = " SELECT eor_id, COALESCE(eor_label, eor_id) AS eor_label, eor_color FROM edw_object_ref"
                        . " WHERE eor_obj_type = '" . $na_axeN[0] . "'"
                        . " AND eor_on_off = 1"
                        . " AND eor_id IN (" . implode(", ", array_map(array($this, 'labelizeValue'), $ne_axeN_no_label)) . ")";

                $db = Database::getConnection($this->masterTopo);
                $db->setDebug($this->debug);

                $ne_label = $db->getAll($sql);

                for ($i = 0; $i < count($ne_label); $i++) {
                    // 17:59 29/10/2009 GHX
                    //  BZ 12181
                    // On compl�te le tableau au fur et a mesure au lieu de le faire apres le for ce qui �crasait les valeurs pr�c�dentes
                    $this->neAxeNLabel[$ne_label[$i]['eor_id']] = $ne_label[$i]['eor_label'];
                }
            }
        }

        // Traitement de la s�lection d'un �l�ment unique pour un axe N
        if (($axeN == false) && (count($this->neAxeN[$product][$family]) == 1)) {
            $na_axeN = array_values($this->naAxeN[$product][$family]);
            $na_axeN = $na_axeN[0];
            $ne_axeN = $this->neAxeN[$product][$family][$na_axeN][0];

            $sql = " SELECT COALESCE(eor_label, eor_id) AS eor_label, eor_color FROM edw_object_ref"
                    . " WHERE eor_obj_type = '" . $na_axeN . "'"
                    . " AND eor_on_off = 1"
                    . " AND eor_id = '" . $ne_axeN . "'";

            $db = Database::getConnection($product);
            $db->setDebug($this->debug);
            $row = $db->getRow($sql);
            $this->neAxeNLabel[$ne_axeN] = $row['eor_label'];
        }
    }

    /**
     * Permet, dans le cadre du mapping de la topologie, de g�rer les convertions entre les valeurs de ne pr�selectionn�es et leurs valeurs effectives
     * dans les tables de donn�es du produit concern�
     *
     * @param array $ne liste de ne � �ventuellement convertir
     * @param string $na nom de la na
     * @param integer $product identifiant du produit
     * @return array liste des ne mise � jour
     */
    private function ConvertEquivalentNeAxe1($ne, $na, $product) {
        if (isset($this->equivalentNeAxe1[$product][$na])) {
            $ne_to_convert = array();
            $ne_to_convert = $this->equivalentNeAxe1[$product][$na];

            if (count($ne_to_convert) > 0) {
                // 15:46 26/10/2009 GHX
                // Convertion de tous les �l�ments du tableau en string
                foreach ($ne as $k => $v) {
                    $ne[$k] = (string) $v;
                }
                foreach ($ne_to_convert as $ne_id => $ne_codeq) {
                    // On force la valeur de recherche en string
                    // 11/04/2011 MMT bz 21604 utilisation du param�tre optionel strict de in_array
                    if (in_array((string) $ne_id, $ne, true)) {
                        // 16/02/2011 BBX
                        // On force l'�l�ment en type string
                        // sinon le "in_array" suivant plante (bug php ?)
                        // BZ 20629
                        // 11/04/2011 MMT bz 21604 utilisation du param�tre optionel strict de array_search
                        $ne[array_search((string) $ne_id, $ne, true)] = (string) $ne_codeq;
                    }
                }
            }
        }
        return $ne;
    }

    /**
     * Permet, dans le cadre du mapping de la topologie, de g�rer les convertions entre les valeurs de ne pr�selectionn�es et leurs valeurs effectives
     * dans les tables de donn�es du produit concern�
     *
     * @param array $ne liste de ne � �ventuellement convertir
     * @param string $na nom de la na
     * @param integer $product identifiant du produit
     * @return array liste des ne mise � jour
     */
    private function ReconvertEquivalentNeAxe1($ne, $na, $product) {
        if (isset($this->equivalentNeAxe1[$product][$na])) {
            $ne_to_convert = array();
            $ne_to_convert = $this->equivalentNeAxe1[$product][$na];
            if (count($ne_to_convert) > 0) {
                // 15:46 26/10/2009 GHX
                // Convertion de tous les �l�ments du tableau en string
                foreach ($ne as $k => $v) {
                    $ne[$k] = (string) $v;
                }
                foreach (array_flip($ne_to_convert) as $ne_codeq => $ne_id) {
                    // On force la valeur de recherche en string
                    // 11/04/2011 MMT bz 21604 le test dans in_array "00331" == "+331" revoit vrai, il faut forcer "00331" === "+331"
                    // utilisation du param�tre optionel strict
                    if (in_array((string) $ne_codeq, $ne, true)) {
                        // 16/02/2011 BBX
                        // On force l'�l�ment en type string
                        // sinon le "in_array" suivant plante (bug php ?)
                        // BZ 20629
                        // 11/04/2011 MMT bz 21604 utilisation du param�tre optionel strict de array_search
                        $ne[array_search((string) $ne_codeq, $ne, true)] = (string) $ne_id;
                    }
                }
            }
        }
        return $ne;
    }

    /**
     * Permet de d�finir si la valeur ALL est d�fini pour les �l�ments d'axeN
     *
     * @param int $product identifiant du produit
     *
     * @return mixed false si une valeur est sp�cifi�e, le nom de la na sinon
     */
    private function checkNeAxeNALL($product) {
        $na_axeN_all = false;

        if (isset($this->naAxeN[$product])) {
            foreach ($this->naAxeN[$product] as $family => $na_axeN) {
                // Dans le cas o� une valeur d'une des na d'axe N est non pr�cis�, cela signifie que l'on se trouve dans le cas du "ALL"

                if (count($this->neAxeN[$product][$family]) == 0) {
                    // Note : on suppose ici qu'il y a un seul axe N (=3). Modifier ce cas pour inclure plusieurs axes
                    // 06/01/2009 - Modif. benoit : on d�fini la valeur de l'axe N dans un tableau et non en tant que chaine

                    $na_axeN_all = array($na_axeN[0]);
                }
            }
        }

        return $na_axeN_all;
    }

    /**
     * Retourne la liste des na d'axe n d'un GTM donn�
     *
     * @param int $id_gtm identifiant du GTM
     * @return array liste des na d'axe n du GTM
     */
    private function getNaAxeN($id_gtm) {
        $na_axeN = array();

        if (count($this->naAxeN) > 0) {
            $gtm_model = new GTMModel($id_gtm);
            $gtm_products = $gtm_model->getGTMProducts();

            for ($i = 0; $i < count($gtm_products); $i++) {
                $axeN_families = $this->naAxeN[$gtm_products[$i]];

                if (count($axeN_families) > 0) {

                    foreach ($axeN_families as $family => $na) {
                        // Note : on suppose qu'il n'y a qu'un axe N par famille (� modifier)
                        if (!in_array($na[0], $na_axeN))
                            $na_axeN[] = $na[0];
                    }
                }
            }
        }

        return $na_axeN;
    }

    /**
     * Permet de "labeliser" une valeur cad de l'entourer de quotes
     *
     * @param numeric $value la valeur � labeliser
     * @return string la valeur labelis�e
     */
    private function labelizeValue($value) {
        return "'" . $value . "'";
    }

    /**
     * Formate une valeur de ta au format T&A
     *
     * @param int $ta_value valeur de ta � formater
     * @return string valeur de ta format�e
     */
    private function labelizeTaValue($ta_value) {
        return getTaValueToDisplay($this->ta, $ta_value, "/");
    }

    /**
     * Retourne le timestamp Unix en flottant
     *
     * @return float timestamp Unix
     */
    private function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * 06/06/2011 MMT DE 3rd Axis. permet de centraliser l'execution des requ�tes SQL et gerer la debug
     *
     * @param databaseConnection $db  current db connection obj
     * @param String $sql  SQL request
     * @return DB query resultset
     */
    private function execMainQuery($db, $sql) {

        $row = $db->execute($sql);
        if ($this->debug) {
            echo("<h3>REQUEST EXECUTED:</h3><pre>" . $sql . "</pre>ROWS FOUND :" . count($row));
        }
        return $row;
    }

}
