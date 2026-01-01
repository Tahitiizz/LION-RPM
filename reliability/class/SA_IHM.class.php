<?php

/**
 *      @cb_v5.0.3.10
 *
 * 	Composant de base version cb_5.0.3.10
 *
 *      - maj 15/09/2010 - MPR : Correction du bz 17848
 *              -> Changement du format de la date affichée Month/Day/Year par Year/Month/Day
 *              -> On spécifie un jour par défaut sinon la fonction date("F Y") pointe sur le mois précédent du mois voulu
 * 		-> Choix de 28 comme valeur par défaut pour pouvoir l'appliquer à n'importe quel mois
 *      12/10/2010 NSE bz 18454, 18467 : gestion des cas d'affichage de l'icône du SA et du produit sélectionné
 */
?>
<?php

/**
 * Class permettant de créer et d'afficher l'IHM de Source Availability
 */
class SaIHM {

    public $ta = "day";
    public $ta_value;
    public $productid; // id du produit sélectionné
    public $products = array(); // tableau listant les différents produits
    public $connexions; // tableau des flat file connexions pour le produit en cours
    public $dates = array();
    public $mode;
    public $datas;
    public $link_to_hour;
    private $connections_name;
    private $getNumRows; // booléen définissant le lien permettant de basculer du mode day en hour
    private $_db; // connection à la base de données
    private $SA_calculate; // Class calculant les SA
    private $SA_table; // tableau
    private $SA_order; // tableau permettant de connaitre la moyenne de l'état de chaque connexion sur la période donnée

    /**
     * Constructeur de la classe SaIHM
     * - $product : id du produit
     */

    function __construct($product = 0) {
        $this->_db = Database::getConnection($product);

        // initialisation des tableaux intermédiaires
        $this->SA_table = array();
        $this->SA_order = array();

        $this->SA_calculate = new SA_Calculation();

        $this->productid = $product;

        $this->setProductsActivated();
    }

    /**
     * Fonction permettant d'initialiser la Time Aggregation attendue dans l'IHM
     * 	- $time : String définissant le type de TA, par défaut day, sinon hour
     */
    function setTa($time = "day") {
        $this->ta = $time;
    }

    /**
     * Fonction permettant d'initialiser la valeur de l'agrégation temporelle dans l'IHM
     * - $ta_value : chaine de caractère spécifiant la TA value
     */
    function setTaValue($ta_value) {
        $this->ta_value = $ta_value;
    }

    /**
     * Fonction permettant de définir les produits en cours
     */
    function setProductsActivated() {
        $query = "SELECT sdp_id, sdp_label FROM sys_definition_product WHERE sdp_on_off = 1";
        $prod_tmp = $this->_db->getAll($query);

        // Exclusion du Produit Blanc de la liste des produits
        foreach ($prod_tmp as $prod) {
            if (!ProductModel::isBlankProduct($prod['sdp_id'])) {
                $product[$prod['sdp_id']] = $prod['sdp_label'];
            }
        }
        $this->products = $product;
    }

    /**
     * Fonction vérifiant si les parsers des produits associés sont compatible
     * param $db : connexion à la base de données
     */
    function checkParserCompatible($db = "") {
        if ($db == "")
            $db = $this->_db;
        $query = "SELECT value FROM sys_global_parameters WHERE parameters='activation_source_availability'";
        return $db->getOne($query);
    }

    /**
     * Retourne la liste des produits sur lequels est activé le SA
     *
     * 29/01/2013 GFS BZ#31476 - [SUP][T&A OMC Ericsson BSS][Zain Iraq][AVP#32904] Source Availability information display with client_admin
      function productsWithSaActivated(){
      $prod_tmp = getProductInformations();
      $products = array();
      foreach($prod_tmp as $prod){
      $database = Database::getConnection($prod['sdp_id']);
      if($this->checkParserCompatible($database)){
      $products[] = $prod['sdp_id'];
      }
      }
      return $products;
      }
     */
    function isProductsExist() {
        return !empty($this->products);
    }

    /**
     * Retourne le produit pour lequel on veut afficher le SA :
     *  - si on trouve des produits pour le dashboard courant
     *      - si l'un d'entre-eux a le Sa d'activé, on retourne son id
     *      - sinon, on retourne l'un des id des produits du Dash.
     *  - sinon, on retourne 0
     *
     */
    function productsToDisplaySa() {

        // 21/11/2011 BBX
        // Correction de messages "Notices" vu pendant les corrections
        if (!isset($_SESSION['id_menu_courrant']))
            $_SESSION['id_menu_courrant'] = null;

        // retrouve la liste des produits associés à ce dashboard
        $select = "SELECT sdp_id, sdp_db_name
                        FROM sys_pauto_config C, sys_definition_product P
                        WHERE P.sdp_id=C.id_product
                        AND id_page in (
                        SELECT id_page
                        FROM menu_deroulant_intranet
                        WHERE id_menu='{$_SESSION['id_menu_courrant']}')";
        $prod_tmp = $this->_db->getAll($select);

        // on vérifie que la page ne correspond pas à un menu générique
        // 07/06/2011 BBX -PARTITIONING-
        // Application des casts nécessaires
        $select = " SELECT *
                                    FROM menu_deroulant_intranet
                                    WHERE id_menu = '{$_SESSION['id_menu_courrant']}'
                                            AND id_menu_parent <> '0'
                                            AND id_page IS NULL";
        $this->_db->execute($select);
        if ($this->_db->getNumRows($select)) {
            // 21/11/2011 BBX
            // Correction de messages "Notices" vu pendant les corrections
            $condition = '';
            if ($this->productid)
                $condition = " AND sdp_id = {$this->productid}";
            $select = "SELECT sdp_id, sdp_db_name FROM sys_definition_product WHERE sdp_on_off = 1 {$condition}";
            $prod_tmp = $this->_db->getAll($select);
        }
        // si aucun produit n'est trouvé correspondant au Dash courant, on retourne 0
        // 29/01/2013 GFS BZ#31476 - [SUP][T&A OMC Ericsson BSS][Zain Iraq][AVP#32904] Source Availability information display with client_admin
        if (empty($prod_tmp) && $this->isProductsExist()) {
            reset($this->products);
            // on récupère l'id du premier élément du tableau des produits
            $productid = array_keys($this->products, current($this->products));
            // on remet le point sur le dernier élément inséré
            end($this->products);
            return $productid[0];
        }

        foreach ($prod_tmp as $prod) {
            $database = Database::getConnection($prod['sdp_id']);
            if ($this->checkParserCompatible($database)) {
                return $prod['sdp_id'];
            }
        }
        // si aucun produit n'a le SA d'activé, on retourne l'un des produits trouvés dans le Dashboard.
        return $prod_tmp[0]['sdp_id'];
    }

    /**
     * Fonction permettant de définir les produits sur lesquels le SA est activé
     * retourne le premier produit sur lequel est activé le SA (sinon renvoie 0)
     */
    function searchSaActivatedOnProduct($_condition = true) {
        if ($_condition) {
            // retrouve la liste des produits associés à ce dashboard
            $select = "SELECT sdp_id, sdp_db_name 
							FROM sys_pauto_config C, sys_definition_product P
							WHERE P.sdp_id=C.id_product
									AND id_page in (
									SELECT id_page
									FROM menu_deroulant_intranet
									WHERE id_menu='{$_SESSION['id_menu_courrant']}')";
            $prod_tmp = $this->_db->getAll($select);

            // on vérifie que la page ne correspond pas à un menu générique
            // 07/06/2011 BBX -PARTITIONING-
            // Correction des casts
            $select = " SELECT *
						FROM menu_deroulant_intranet
						WHERE id_menu = '{$_SESSION['id_menu_courrant']}'
							AND id_menu_parent <> '0'
							AND id_page IS NULL";
            $this->_db->execute($select);
            if ($this->_db->getNumRows($select)) {
                if ($this->productid)
                    $condition = " AND sdp_id = {$this->productid}";
                $select = "SELECT sdp_id, sdp_db_name FROM sys_definition_product WHERE sdp_on_off = 1 {$condition}";
                $prod_tmp = $this->_db->getAll($select);
            }
        }
        else {
            $prod_tmp = getProductInformations();
        }
        foreach ($prod_tmp as $prod) {
            $database = Database::getConnection($prod['sdp_id']);

            if ($this->checkParserCompatible($database)) {
                return $prod['sdp_id'];
            }
        }
        return 0;
    }

    /**
     * Fonction récupérant les connections à partir du modèle Connection
     * - $product_id : id du produit sélectionné
     */
    function setConnexions($product_id) {
        $query = "SELECT id_connection, connection_name, on_off FROM sys_definition_connection ORDER BY id_connection";
        $this->connexions = $this->_db->getAll($query);
    }

    /**
     * Fonction permettant de récupérer un tableau contenant les informations de légende sauvegardées dans sys_definition_sa_config
     * Les données sont réorganisées en fonction de la valeur min du seuil
     */
    function getColoredScale() {
        $query = "SELECT sdsc_color, sdsc_seuil_min, sdsc_seuil_max FROM sys_definition_sa_config ORDER BY sdsc_seuil_min IS NOT NULL DESC, sdsc_seuil_min DESC";
        return $this->_db->getAll($query);
    }

    /**
     * Fonction permettant de la couleur pour une valeur dans sys_definition_sa_config
     * - $value : valeur SA
     * - $default_value : booléen, si true, on retourne la valeur par défaut quand les données sont nulles
     */
    function getColor($value, $default_value = false) {
        if (!$default_value)
            $query = "SELECT sdsc_color FROM sys_definition_sa_config WHERE sdsc_seuil_min<=$value ORDER BY sdsc_seuil_min IS NOT NULL DESC, sdsc_seuil_min DESC LIMIT 1";
        else
            $query = "SELECT sdsc_color FROM sys_definition_sa_config ORDER BY sdsc_seuil_min IS NULL DESC LIMIT 1";

        return $this->_db->getOne($query);
    }

    /**
     * Fonction qui permet de vérifier si la vue hour est possible
     * $connectionIds : liste des connections à vérifier
     * return true si au moins une connexion a une vue Hour, sinon false
     */
    function checkHourView($connectionIds) {
        $link_to_hour = false;
        foreach ($connectionIds as $id_connection) {
            // uniquement si tous les data_chunks = 24 et que toutes es granulatirées = hour
            $select = " SELECT * 
						FROM sys_definition_flat_file_lib 
						WHERE on_off = 1 
							AND ((data_chunks<>24 AND granularity<>'hour') OR (data_collection_frequency='24'))
							AND id_flat_file in (
													SELECT sdsftpc_id_flat_file 
													FROM sys_definition_sa_file_type_per_connection 
													WHERE sdsftpc_id_connection in ({$id_connection})
												)
						ORDER BY id_flat_file DESC";
            $this->_db->execute($select);
            if (!$this->_db->getNumRows($select)) {
                $link_to_hour = true;
                return $link_to_hour;
            } else
                $link_to_hour = false;
        }
        return $link_to_hour;
    }

    /**
     * Cette fonction retourne un tableau de toutes les connections avec leur valeurs pour les heures spécifiées
     */
    function getSaConnexions() {
        $SA_table = array();

        // Récupération des données SA dans la base de données
        foreach ($this->connexions as $connexion) {
            $SA_table[$connexion['id_connection']] = array();
            $this->connections_name[$connexion['id_connection']] = $connexion['connection_name'];

            foreach ($this->dates as $date) {
                // récupération des données pour $date
                $query = "	SELECT sdsv_calcul_sa
							FROM sys_definition_sa_view 
							WHERE sdsv_id_connection={$connexion['id_connection']}
								AND sdsv_ta_value = '{$date}'
								AND sdsv_ta='{$this->ta}'";
                $value_hours = $this->_db->getOne($query);

                // On calcule la valeur du SA à partir des données hour
                if (is_string($value_hours))
                    $SA_table[$connexion['id_connection']][$date] = $value_hours;
                else
                    $SA_table[$connexion['id_connection']][$date] = "";
            }
        }

        return $SA_table;
    }

    /**
     * Fonction qui construit l'IHM de l'entête de la page (informations générales et formulaire de sélection)
     * 12/10/2010 NSE relecture specs : pas de produit sélectionné si aucun produit n'a été sélectionné !
     */
    function constructHeader() {
        $html = "<form name='changePreferences' action='#' method='get'>\n";

        // Affichage de la liste des produits
        $html .= "<div id='select'>Product: <select name='productid' onchange='document.changePreferences.submit()'>\n";
        if ($this->productid == 0)
            $html .= "	<option value='0' selected>Select product</option>\n";
        foreach ($this->products as $id_prod => $product) {
            $html .= "	<option value='{$id_prod}' " . (($id_prod == $this->productid) ? "SELECTED" : "") . ">{$product}</option>\n";
        }
        $html .= "</select>\n";
        $html .= "</div>\n";

        $html .= "<div id='title'>Source Availability</div>";

        if ($this->productid != 0) {
            // Affichage de l'option de basculement d'un format à l'autre (Hour <-> Day)
            $html .= "<div id='switch_ta'>\n";
            if ($this->ta == "hour") {
                $this->new_ta = "day";
                $title = "Back to daily view";
                $image = "bullet_back.png";

                $html .= "	<a onclick=\"changeGranul('{$this->new_ta}')\">\n";
                $html .= $title;
                $html .= "<img src='images/{$image}' border='0'>\n";
                $html .= "	</a>\n";
            }
            $html .= "	<input type='hidden' id='ta_mode' name='ta_mode' value='{$this->ta}'>\n";
            $html .= "</div>\n";

            // Affichage de la date
            $html .= "<div id='menu_day'>\n";
            $year = substr($this->ta_value, 0, 4);
            $month = substr($this->ta_value, 4, 2);
            // maj 15/09/2010 - MPR : Correction du bz 17848
            // On spécifie un jour par défaut sinon la fonction date("F Y") pointe sur le mois précédent du mois voulu
            // Choix de 28 comme valeur par défaut pour pouvoir l'appliquer à n'importe quel mois
            $day = ( substr($this->ta_value, 6, 2) == null ) ? "28" : substr($this->ta_value, 6, 2);

            // Affichage de la flèche de gauche
            if ($this->ta == "day") {
                $html .= "	<img style='margin-right:10px;' src='images/left.png' border='0' onclick=\"changedate('" . date('Ymd', mktime(0, 0, 0, $month - 1, $day, $year)) . "')\">\n";
            } else {
                $html .= "	<img style='margin-right:10px;' src='images/left.png' border='0' onclick=\"changedate('" . date('Ymd', mktime(0, 0, 0, $month, $day - 1, $year)) . "')\">\n";
            }

            if ($this->ta == "day") {
                $html .= date("F Y", mktime(0, 0, 0, $month, $day, $year));
            } else {
                $html .= date("F jS, Y", mktime(0, 0, 0, $month, $day, $year));
            }

            // Affichage de la flèche de droite
            if ($this->ta == "day") {
                // flèche pour basculer d'un mois
                if (mktime(0, 0, 0, $month, 1, $year) < mktime(0, 0, 0, date('n'), 1, date('Y')))
                    $html .= "	<img style='margin-left:10px;' src='images/right.png' border='0' onclick=\"changedate('" . date('Ymd', mktime(0, 0, 0, $month + 1, $day, $year)) . "')\">\n";
            }
            else {
                // flèche pour basculer d'une journée
                if (mktime(0, 0, 0, $month, $day, $year) < mktime(0, 0, 0, date('n'), date('d'), date('Y')))
                    $html .= "	<img style='margin-left:10px;' src='images/right.png' border='0' onclick=\"changedate('" . date('Ymd', mktime(0, 0, 0, $month, $day + 1, $year)) . "')\">\n";
            }
            // sauvegarde de l'information date dans un champ input caché
            $html .= "<input type='hidden' id='ta_value' name='ta_value' value='{$this->ta_value}'>\n";
            $html .= "</div>\n";

            // Affiche le nombre de connections présentées à l'écran
            $html .= "<div id='nb_connec'>\n";
            $html .= count($this->connexions) . " connexions";
            $html .= "</div>\n";

            $html .= "<div id='show_errors'>\n";
            // Affichage de la boite de sélection pour "show errors only"
            $html .= "	<input type='checkbox' id='show_errors_only' name='show_errors' value='2' onclick=\"checkMode('show_errors_only')\" " . (($this->mode == 2) ? "checked" : "") . ">\n";
            $html .= "	<label for='show_errors_only'> &nbsp; Show errors only</label>\n";

            // Affichage de la boite de sélection pour "show errors first"
            $html .= "	<input type='checkbox' id='show_errors_first' name='show_errors' value='3' onclick=\"checkMode('show_errors_first')\" " . (($this->mode == 3) ? "checked" : "") . ">\n";
            $html .= "	<label for='show_errors_first'> &nbsp; Show errors first</label>\n";
            $html .= "</div>\n";
        } else
            echo '<div style="height:30px;clear: both;" />';
        $html .= "</form>\n";
        return $html;
    }

    /**
     * Fonction qui construit la légende chronologique
     */
    function constructLegendDate() {
        $html = "";

        // récupération de la date
        $year = substr($this->ta_value, 0, 4);
        $month = substr($this->ta_value, 4, 2);
        $day = substr($this->ta_value, 6, 2);

        // récupération du nombre de colonnes à représenter (en fonction de la TA)
        if ($this->ta == "day") {
            $max_raw = date("j", mktime(0, 0, 0, $month + 1, 1, $year) - 1);
        } else {
            $max_raw = 24;
        }

        $html .= "<table id='SAHeader' border='0'>\n";
        $connex_per_date = array();

        // récupération des connexions activées
        $connex_activated = array();
        foreach ($this->connexions as $connex_tab) {
            if ($connex_tab['on_off'])
                $connex_activated[] = $connex_tab['id_connection'];
        }

        // vérification que l'on peut basculer en mode hour
        $this->link_to_hour = $this->checkHourView($connex_activated);

        // Affichage des références temporelles
        $html .= "<tr>\n";
        $html .= "	<td id='firstCol'>&nbsp;</td>\n";

        $SA_day = array();
        // Boucle sur toutes les heures/dates à représenter
        for ($raw = 0; $raw < $max_raw; $raw++) {
            // définition de la date au format base de données, et au format tel qu'il est représenté en base
            if ($this->ta == "day") {
                $date_IHM = date("d", mktime(1, 0, 0, $month, $raw + 1, $year));
                $date_DB = date("Ymd", mktime(1, 0, 0, $month, $raw + 1, $year));
            } else {
                $date_IHM = date("H", mktime($raw, 0, 0, $month, $day, $year));
                $date_DB = date("YmdH", mktime($raw, 0, 0, $month, $day, $year));
            }

            // affichage de la date
            $html .= "<td id='date'><a title='{$date_IHM}'>" . $date_IHM . "</a></td>\n";
            $this->dates[] = $date_DB; // sauvegarde des journées à afficher au format YYYYMMDD ou YYYYMMDDHH
        }

        // récupération des données pour chaque connexion et chaque date à affichée et qui est stockées en base
        $this->SA_table = $this->getSaConnexions();

        foreach ($this->dates as $date_DB) {
            $test_tab = array();

            // récupération de toutes les données sur toutes les connexions
            foreach ($this->SA_table as $id_connection => $values) {

                // création du tableau pour la date en cours
                if (!isset($connex_per_date[$date_DB]))
                    $connex_per_date[$date_DB] = array();

                if (in_array($id_connection, $connex_activated))
                    $connex_per_date[$date_DB][] = $values[$date_DB];

                $data_available = false;

                // On fait une boucle sur toutes les données pour la connexion en cours
                foreach ($values as $date => $value) {
                    // si une donnée existe, alors on doit représenter la connexion en première (dans le cas de 'show error first')
                    if ($value != "")
                        $data_available = true;
                }

                // Gestion de l'ordre d'affichage, dans le cas où l'option 'show errors first' est activé
                $this->SA_order[$id_connection] = array_sum($values) / count($this->SA_table);
                // dans le cas où on a aucune donnée valide pour la connexion, on la représente en dernière
                if ($data_available == false)
                    $this->SA_order[$id_connection] = 200;
            }
        }
        $html .= "</tr>";

        // Affichage de l'Overview
        $html .= "<tr>";
        $html .= "	<td class='firstCol'>Overview</td>";
        foreach ($this->SA_calculate->calculateSaOverview($connex_per_date) as $date => $value) {

            // formatage de la date à afficher
            // maj 15/09/2010 - MPR : Correction du bz 17848 - Changement du format de la date affichée Month/Day/Year par Year/Month/Day
            $date_to_display = substr($date, 0, 4) . "/" . substr($date, 4, 2) . "/" . substr($date, 6, 2);
            if ($this->ta == "hour")
                $date_to_display .= " " . substr($date, 8, 2) . ":00";

            // ajout de l'option onclick si on est en affichage day
            if ($this->ta == "day" && $this->link_to_hour) {
                $onclick = "onclick=\"switchDate('hour', '{$date}');\"";
                $onmouseover = "style='cursor: pointer;'";
            } else {
                $onclick = "";
                $onmouseover = "";
            }

            // Affiche la case overview correspondant au jour en cours.
            if (is_float($value) && $value >= 0)
                $html .= " <td class='date' {$onmouseover} bgcolor='" . $this->getColor($value) . "' {$onclick} alt='" . $date_to_display . " : " . $value . "%' title='" . $date_to_display . " : " . $value . "%'>&nbsp</td>\n";
            else
                $html .= " <td class='date' {$onmouseover} bgcolor='" . $this->getColor($value, true) . "' {$onclick} alt='No data available' title='No data available'>&nbsp</td>\n";
        }
        $html .= "</tr>";
        $html .= "</table>";

        return $html;
    }

    /**
     * Fonction qui construit le tableau de l'IHM
     */
    function constructTable() {
        $html = "";
        $script_tab = array();
        $hide_not_hour_connect = array();

        // récupération des connexions activées
        $connex_activated = array();
        foreach ($this->connexions as $connex_tab) {
            if ($connex_tab['on_off'])
                $connex_activated[] = $connex_tab['id_connection'];
        }

        // Gestion de l'ordre d'affichage, dans le cas où l'option 'show errors first' est activé
        if ($this->mode == 2 || $this->mode == 3)
            asort($this->SA_order);

        // Affichage du tableau source availability
        $html .= "<table id=\"SA_list\" border=\"0\">";
        foreach ($this->SA_order as $id_connection => $sum) {
            // récupération des données par jour pour la connexion en cours (on est obligé de passer par cette boucle et cette assignation pour le cas show errors first
            $connexion_values = $this->SA_table[$id_connection];

            // création d'un nom id afin d'identifier chaque ligne, dans le cas où l'on ne souhaite pas visualiser cette ligne
            $id_name = str_replace(" ", "", $id_connection);

            // compteur permettant de définir si il y a au moins une erreur sur cette connexion sur la période sélectionnée
            $cpt_value = 0;
            // paramètre permettant de définir si toutes les données sont non valides pour la connexion en cours
            $data_available = false;

            $html .= "<tr id='" . $id_name . "'>\n";

            $title = "";
            $id_css = "";
            // Dans le cas d'un affichage hour, on représente une image bleue en face des connexions activées
            if ($this->ta == "day" && $this->checkHourView(array($id_connection)) && $sum > 0 && $sum != 200)
                $id_css .= " hour_values";
            if (!in_array($id_connection, $connex_activated)) {
                $id_css .= " not_activated";
                $title = "Connection disabled";
            }

            // 22/09/2010 BBX
            // Traitement du label de la connexion
            // Ajout d'un tooltip avec le label complet
            // BZ 17528
            $connexionLabel = str_replace("_", " ", $this->connections_name[$id_connection]);
            $connexionToolTip = $connexionLabel;
            if (strlen($connexionLabel) > 14)
                $connexionLabel = substr($connexionLabel, 0, 8) . "..." . substr($connexionLabel, -3);

            // Affichage du label de la connexion
            $html .= "	<td class='firstCol{$id_css}' title='{$title}' onmouseover=\"popalt('$connexionToolTip')\">" . $connexionLabel . "</td>\n";

            foreach ($this->dates as $date) {
                // calcul de la somme des données pour la connexion en cours
                $cpt_value += (float) $connexion_values[$date];

                // si la donnée existe, alors on doit représenter la connexion
                if ($connexion_values[$date] != "")
                    $data_available = true;

                // ajout de l'option onclick si on est en affichage day
                if ($this->ta == "day" && $this->checkHourView(array($id_connection))) {
                    $onclick = "onclick=\"switchDate('hour', '{$date}');\"";
                    $onmouseover = "style='cursor: pointer;'";
                } else {
                    $onclick = "";
                    $onmouseover = "";
                }

                // maj 15/09/2010 - MPR : Correction du bz 17848 - Changement du format de la date affichée Month/Day/Year par Year/Month/Day
                $date_to_display = substr($date, 0, 4) . "/" . substr($date, 4, 2) . "/" . substr($date, 6, 2);
                if ($this->ta == "hour")
                    $date_to_display .= " " . substr($date, 8, 2) . ":00";

                if (is_float($connexion_values[$date]) || is_int($connexion_values[$date]) || $connexion_values[$date] != "") {
                    $html .= " <td class=\"SA_data\" {$onmouseover} bgcolor=\"" . $this->getColor($connexion_values[$date] * 100) . "\" {$onclick} alt='" . ($connexion_values[$date] * 100) . "%' title='" . $date_to_display . " : " . ($connexion_values[$date] * 100) . "%'>&nbsp</td>\n";
                } else
                    $html .= " <td class=\"SA_data\" {$onmouseover} bgcolor=\"" . $this->getColor($connexion_values[$date] * 100, true) . "\" {$onclick} alt='" . $date_to_display . " : No data available' title='" . $date_to_display . " : No data available'>&nbsp</td>\n";
            }
            $html .= "</tr>\n";

            // 04/12/2012 BBX
            // BZ 17912 : array_sum instead of count
            // 03/09/2013 GFS - Bug 35927 - [SUP][5.3.1][#NA][Telus] : Warning : Division by zero in Source Availability
            if (count($connexion_values) && array_sum($connexion_values) != 0 && ($cpt_value / array_sum($connexion_values) == 1 || !$data_available)) {
                $script_tab[] = $id_name;
            }

            if ($this->ta == "hour" && !$this->checkHourView(array($id_connection))) {
                $hide_not_hour_connect[] = $id_name;
            }
        }
        $html .= "</table>";

        // on cache toutes les connexions qui n'ont pas de problème si l'option "show error only" est cochée
        if ($this->mode == 2 && count($script_tab)) {
            $html .= "<script>\n";
            foreach ($script_tab as $id_name) {
                $html .= "document.getElementById('" . $id_name . "').style.display = 'none';\n";
            }

            $html .= "document.getElementById('nb_connec').innerHTML = '" . (count($this->SA_table) - count($script_tab)) . " connexions';";
            $html .= "</script>\n";
        }
        // on cache les connexions qui n'ont pas de représentation hour
        if ($this->ta == "hour" && count($hide_not_hour_connect)) {
            $html .= "<script>\n";
            foreach ($hide_not_hour_connect as $id_name) {
                $html .= "document.getElementById('" . $id_name . "').style.display = 'none';\n";
            }

            $html .= "document.getElementById('nb_connec').innerHTML = '" . (count($this->SA_table) - count($hide_not_hour_connect)) . " connexions';";
            $html .= "</script>\n";
        }

        return $html;
    }

    /**
     * Fonction qui construit la légende du tableau pour l'IHM
     */
    function constructLegend() {
        $html = "<div>Legend</div>";

        $color_tab = $this->getColoredScale();

        $html .= "<table>";
        $html .= "	<tr>";
        for ($i = 0; $i < count($color_tab); $i += 2) {
            $html .= "		<td class='color' bgcolor='{$color_tab[$i]['sdsc_color']}'></td>";
            $html .= "		<td class='title'>";
            if ($color_tab[$i]['sdsc_seuil_min'] == "")
                $html .= "No data";
            elseif ($color_tab[$i]['sdsc_seuil_min'] == $color_tab[$i]['sdsc_seuil_max'])
                $html .= $color_tab[$i]['sdsc_seuil_min'] . "%";
            else
                $html .= $color_tab[$i]['sdsc_seuil_min'] . " - " . $color_tab[$i]['sdsc_seuil_max'] . "%";
            $html .= " available</td>";
        }
        if ($this->ta == "day") {
            $html .= "		<td class='color' bgcolor='#1E99DB' style='text-align:center'>H</td>";
            $html .= "		<td class='title'>Hourly values available</td>";
        } else
            $html .= "		<td colspan='2'>&nbsp;</td>\n";
        $html .= "	</tr>";

        $html .= "	<tr>";
        for ($i = 1; $i < count($color_tab) + 1; $i += 2) {
            $html .= "		<td class='color' bgcolor='{$color_tab[$i]['sdsc_color']}'></td>";
            $html .= "		<td class='title'>";
            if ($color_tab[$i]['sdsc_seuil_min'] == "")
                $html .= "No data";
            elseif ($color_tab[$i]['sdsc_seuil_min'] == $color_tab[$i]['sdsc_seuil_max'])
                $html .= $color_tab[$i]['sdsc_seuil_min'] . "%";
            else
                $html .= $color_tab[$i]['sdsc_seuil_min'] . " - " . $color_tab[$i]['sdsc_seuil_max'] . "%";
            $html .= " available</td>";
        }
        $html .= "		<td colspan='2' class='export'><img src='../images/icones/excel.gif'";
        $html .= " alt='Excel export' title='Excel Export'";
        $html .= " onclick=\"export_as_excel('{$this->productid}', '{$this->ta_value}', '{$this->ta}', '{$this->mode}', 'functions_ajax.php')\"></td>";

        $html .= "	</tr>";
        $html .= "</table>";

        return $html;
    }

    /**
     * Fonction qui permet l'export vers Excel
     */
    function exportExcel() {
        $xml = "<chart>\n";
        $xml .= "	<properties>\n";
        $xml .= "    <tabtitle>\n";
        $xml .= "      <text></text>\n";
        $xml .= "    </tabtitle>\n";
        $xml .= "    <type>graph</type>\n";
        $xml .= "    <graph_name><![CDATA[Date = {$this->ta_value}]]></graph_name>\n";
        $xml .= "  </properties>\n";
        $xml .= "	<xaxis_labels>\n";

        // récupération de la date
        $year = substr($this->ta_value, 0, 4);
        $month = substr($this->ta_value, 4, 2);
        $day = substr($this->ta_value, 6, 2);

        // récupération du nombre de colonnes à représenter (en fonction de la TA)
        if ($this->ta == "day")
            $max_raw = date("j", mktime(0, 0, 0, $month + 1, 1, $year) - 1);
        else
            $max_raw = 24;

        // initialisation des tableaux de date
        $this->dates = array();
        $table_date_IHM = array();

        for ($raw = 0; $raw < $max_raw; $raw++) {
            if ($this->ta == "day") {
                $date = date("F jS, Y", mktime(1, 0, 0, $month, $raw + 1, $year));
                $date_DB = date("Ymd", mktime(1, 0, 0, $month, $raw + 1, $year));
            } else {
                $date = date("F jS, Y H:00", mktime($raw, 0, 0, $month, $day, $year));
                $date_DB = date("YmdH", mktime($raw, 0, 0, $month, $day, $year));
            }

            $table_date_IHM[] = $date;
            $this->dates[] = $date_DB; // sauvegarde des journées à afficher au format YYYYMMDD ou YYYYMMDDHH
        }

        // Récupération du tableau à des données SA
        $SA_table = $this->getSaConnexions();

        // Gestion de l'ordre d'affiche, dans le cas où l'option 'show errors first' est activé
        $SA_order = array();
        foreach ($SA_table as $id_connection => $connexion_values) {
            $SA_order[$id_connection] = array_sum($connexion_values) / count($SA_table);
        }
        if ($this->mode == 3)
            asort($SA_order);

        $xaxis_label = "";
        // récupération de toutes les données à afficher dans la première colonne
        foreach ($SA_order as $id_connection => $sum) {
            $xaxis_label .= "		<label>" . $this->connections_name[$id_connection] . "</label>\n";
        }

        foreach ($this->dates as $id => $date) {
            $datas .= "		<data label=\"{$table_date_IHM[$id]}\">\n";

            foreach ($SA_order as $id_connection => $sum) {
                // récupération des données par jour pour la connexion en cours (on est obligé de passer par cette boucle et cette assignation pour le cas show errors first
                $connexion_values = &$SA_table[$id_connection];

                if (is_numeric($connexion_values[$date]))
                    $datas .= "			<value>" . ($connexion_values[$date] * 100) . "%</value>\n";
                else
                    $datas .= "			<value>-</value>\n";
            }

            $datas .= "		</data>\n";
        }

        $xml .= $xaxis_label;
        $xml .= "	</xaxis_labels>\n";
        $xml .= "	<datas>\n";
        $xml .= $datas;
        $xml .= "	</datas>\n";

        $xml .= "</chart>\n";
        return $xml;
    }

    /**
     * Fonction qui permet de ré-ordonner le tableau HTML, suivant le mode
     *  1 : normal / 2 : Show Errors / 3 : Firsts Errors
     * - $mode : type d'affichage (entier)
     */
    function sortByMode($mode = 1) {
        $this->mode = $mode;
    }

    /**
     * Fonction qui retourne un tableau trié dans l'ordre des dates et contenant les valeurs de l'overview
     */
    function getAvaillableData() {
        $available_datas = array();

        foreach ($this->dates as $date) {
            // initialisation du tableau des connexions pour la date en cours
            if (is_array($available_datas[$date]))
                $available_datas[$date] = array();

            foreach ($this->getSaConnexions() as $connexion_name => $values) {
                $available_datas[$date][$connexion_name] = $values[$date];
            }
        }

        return $available_datas;
    }

}

?>