<?
/*
*	@cb50417
*
*	15/02/2011 NSE DE Query Builder :
 *          - Suppression de la limitation des requêtes à 10 000 résultats
 *          - Limitation de l'affichage à 1 000 résultats
 *      21/02/2011 NSE DE Query Builder : modification police
*/
?><?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 14/05/2007 Gwénaël : amélioration de l'affichage: cellpadding vaut 3 au lieu de 0, une ligne sur deux d'une couleur différente, et ajout de bordure pour plus de visibilité
*/
?>
<?php
class Tableau_HTML {
    function Tableau_HTML($tableau_number, $flag_requete_special)
    {
        $this->tableau_number = $tableau_number;
        $this->flag_requete_special = $flag_requete_special;
        $this->tableau_nombre_ligne_resultat = $this->define_tableau_period();
    }
    // fonction qui définit le nombre de période sur lesquelles le tableau sera affiché
    function define_tableau_period()
    {
        Global $period; //variable qui si elle existe est une variable de session
        Global $limite_affichage; //variable par défaut
        if (!isset($period)) {
            $period = $limite_affichage;
        }
        $nombre_lignes_resultat = $period;

        return $period;
    }
    // fonction qui collecte les informations sur le tableau
    function Tableau_Information($numero_table)
    {
        global $database_connection;
        // selectionne les informations relatives au tableau selectionne dans le menu deroulant
        if ($numero_table != '') {
            $query = "SELECT table_width, line_height, line_counter, table_data_list, time_agregation, geographic_agregation, table_abscisse_field_name, table_comparaison_field_name, table_complement_sql_and, table_complement_sql_join, alert, alert_type, alert_status, alert_data, alert_display_data, alert_condition, alert_threshold  FROM table_information where (id_table='$numero_table')";
            $result = pg_query($database_connection, $query);
            $row = pg_fetch_array($result, 0);
            $this->tableau_width = $row["table_width"];
            $this->line_height = $row["line_height"];
            $this->line_counter = $row["line_counter"];
            $this->table_data_list = $row["table_data_list"];
            $this->time_agregation = $row["time_agregation"];
            $this->geographic_agregation = $row["geographic_agregation"];
            $this->table_abscisse_field_name = $row["table_abscisse_field_name"];
            $this->table_comparaison_field_name = $row["table_comparaison_field_name"];
            $this->table_complement_sql_and = $row["table_complement_sql_and"];
            $this->table_complement_sql_join = $row["table_complement_sql_join"];
            $this->alert = $row["alert"];
            if ($this->alert == true) {
                $this->alert_type = $row["alert_type"];
                $this->alert_data = $row["alert_data"];
                $this->alert_status = $row["alert_status"];
                $this->alert_condition = $row["alert_condition"];
                $this->alert_threshold = $row["alert_threshold"];
            }
        }
    }
    // fonction qui collecte les éléments pour chaque donnée qui va être affichée
    function Tableau_Data_Information()
    {
        global $nom_table_data, $database_connection, $font_size_default;

        $condition_requete = "id_data=" . str_replace(",", " or id_data=", $this->table_data_list);
        $array_data_list = explode(",", $this->table_data_list);
        $query = "SELECT id_data, police_bold, police_italic, police_underlined, police_color, header, provider_agregation, data_value, id_data_value, data_type, data_table_name  FROM $nom_table_data where $condition_requete";
		

        $result_data = pg_query($database_connection, $query);
        $this->nombre_table_data = pg_num_rows($result_data);
        // récupère dans un tableau la liste de tous les id_data
        // l'objectif va être de les classer pour conserver l'ordre d'affichage des données
        for ($i = 0;$i < $this->nombre_table_data;$i++) {
            $row = pg_fetch_array($result_data, $i);
            // charge un tableau contenant les id_data récupérés à priori dans n'importe quel ordre
            $resultat_id_data[] = $row["id_data"];
        }
        // boucle sur chaque élément du tableau par ordre décroissant afin d'avoir les données dans le bon ordre à l'affichage
        foreach ($array_data_list as $id_data) {
            // recherche la position de l'id_data dans le tableau résultat des id_data
            $position = array_search($id_data, $resultat_id_data);
            $row1 = pg_fetch_array($result_data, $position);
            $this->tableau_entete_colonnes[$id_data] = $row1["header"];
            $this->data_table_name[$id_data] = $row1["data_table_name"];
            $this->data_type[$id_data] = $row1["data_type"];
            $this->id_data_value[$id_data] = $row1["id_data_value"];
            $this->data_value[$id_data] = $row1["data_value"];

            $font_debut = $font_debut_default;
            $font_fin = $font_fin_default;
            // défini la police devant chaque donnée affichée (hors entete)
            if ($row1["police_bold"] == "bold") {
                $font_debut .= "<b>";
                $font_fin .= "</b>";
            }

            if ($row1["police_italic"] == "italic") {
                $font_debut .= "<i>";
                $font_fin .= "</i>";
            }

            if ($row1["police_underlined"] == "underlined") {
                $font_debut .= "<u>";
                $font_fin .= "</u>";
            }

            $font_debut .= "<font size='$font_size_default' color=" . $row1['police_color'] . ">";
            $font_fin .= "</font>";

            $this->tableau_font_debut[$id_data] = $font_debut;
            $this->tableau_font_fin[$id_data] = $font_fin;
            $i++;
        }
    }
    // génère la requête de retrieve des données des data
    function Table_Data_Query_Create()
    {
        global $hour, $day, $week, $month;
        global $cellid, $bsc, $msc, $zone, $region;
        // teste si on a pré-défini l'abscisse
        if (isset($this->predefined_data_abscisse_field_name)) {
            // l'abscisse correspond à la valeur pre-definie
            $this->table_abscisse = $this->predefined_data_abscisse_field_name;
        } else {
            // l'abscisse correspond à la première valeur puisqu'elles sont toutes égales
            $this->table_abscisse = $this->table_abscisse_field_name;
        }
        // insère en début de tableau les en-têtes la valeur du champ abscisse
        array_unshift($this->tableau_entete_colonnes, $this->table_abscisse);
        // PARTIE FROM
        $i = 0;
        // parcoure la liste des tables dédoublonnées pour créer la partie FROM de la query
        $liste_nom_table_dedoublonnee = array_unique($this->data_table_name);
        $compteur_nombre_table = count($liste_nom_table_dedoublonnee);
        // parcoure la liste dedoublonee des tables
        foreach ($liste_nom_table_dedoublonnee as $key => $nom_table) {
            // s'il n'y a qu'une seule table, on ne met pas d'alias
            if ($compteur_nombre_table == 1) {
                $this->data_retrieve_query_from .= " FROM " . $nom_table . " ";
            } elseif ($i == 0) {
                $this->data_retrieve_query_from .= " FROM " . $nom_table . " as table$key JOIN ";
            } else {
                $this->data_retrieve_query_from .= $nom_table . " as table$key ,";
            }
            $i++;
        }

        $this->data_retrieve_query_from = substr($this->data_retrieve_query_from, 0, -1); //enlève la dernière virgule
        if ($compteur_nombre_table > 1) { // on a donc plusieurs tables <>, on ajoute le join de la première valeur car à priori tous sont égaux
            $this->data_retrieve_query_from .= $this->table_complement_sql_join;
        }
        // PARTIE SELECT
        $this->data_retrieve_query_select = "SELECT $this->table_abscisse,";
        // mets dans un array la liste des data dans l'ordre tel que sauvegardé dans la BDD
        $array_data_list = explode(",", $this->table_data_list);
        // parcoure la liste des data pour créer la partie Select de la query
        foreach ($array_data_list as $id_data) {
            if ($compteur_nombre_table > 1) {
                // retrouve le nom de la table pour la data courante
                $nom_table_data_courante = $this->data_table_name[$id_data];
                // recherche la clé du tableau contenant le nom des tables dedoublonnées correspondant au nom de la table
                $cle_nom_table = array_search($nom_table_data_courante, $liste_nom_table_dedoublonnee);
                $this->data_retrieve_query_select .= "table$cle_nom_table." . $this->data_value[$id_data] . ",";
            } else {
                $this->data_retrieve_query_select .= $this->data_value[$id_data] . ",";
            }
        }
        // PARTIE WHERE
        if ($this->flag_requete_special != 1) { // Dans le cas de tableaux qui ne font pas appel à une évolution temporelle,
            // on peut créer une requête de toute pièce
            $this->data_retrieve_query_where = "WHERE ";
            // teste si le tableau doit afficher une alert / Worst List
            // auquel cas la requête est différente
            if ($this->alert == true) {
                $this->data_retrieve_query_where .= $this->table_abscisse_field_name . "=" . $this->table_comparaison_field_name . " ";
                $this->data_retrieve_query_where .= $this->table_complement_sql_and;
                // teste si on est dans la cas d'une alerte ou d'une Worst List
                if ($this->alert_type == "alert") {
                    // on ajoute une condition pour l'alerte
                    $this->data_retrieve_query_where .= " AND " . $this->data_value[$this->alert_data] . $this->alert_condition . $this->alert_threshold;
                    $this->data_retrieve_query_where .= " ORDER BY " . $this->data_value[$this->alert_data] . " DESC";
                } else {
                    $this->data_retrieve_query_where .= " ORDER BY " . $this->data_value[$this->alert_data] . " DESC";
                    // si on est dans le cas d'une alerte, il ne faut pas mettre de limites
                    $this->data_retrieve_query_where .= " LIMIT " . $this->tableau_nombre_ligne_resultat;
                }
            } else {
                $this->data_retrieve_query_where .= $this->table_abscisse . "<=" . $this->table_comparaison_field_name . " ";
                $this->data_retrieve_query_where .= $this->table_complement_sql_and;
                $this->data_retrieve_query_where .= " ORDER BY " . $this->table_abscisse . " DESC LIMIT " . $this->tableau_nombre_ligne_resultat;
            }
        } else {
            $this->data_retrieve_query_where .= $this->requete_speciale;
        }
        // REQUETE COMPLETE
        // les conditions contiennent des valeurs du type $week, $cellid qu'il faut évaluer
        eval("\$this->data_retrieve_query_where = \"$this->data_retrieve_query_where\" ;") ;
        // concatène les éléments de la requête et ajoute la limite sur la période
        $this->data_retrieve_query = substr($this->data_retrieve_query_select, 0, -1) . " " . $this->data_retrieve_query_from . " " . $this->data_retrieve_query_where;
    }
    // fonction de collecte des données du tableau
    function Tableau_Retrieve_Data()
    {
        global $tableau_legend_export_excel;
        global $tableau_abscisse_export_excel;
        global $tableau_data_export_excel;
        global $database_connection;

		if (!isset($this->data_precision)) {
		    $this->data_precision=2;
		}
        // execute la requête pour récupérer toutes les données
        $resultat_retrieve_tableau_data = pg_query($database_connection, $this->data_retrieve_query);
        $nombre_resultat_trouve = pg_num_rows($resultat_retrieve_tableau_data);
        if ($nombre_resultat_trouve > 0) {
            // parcoure les lignes du résultat pour charger les données dans des tableaux
            for ($i = $nombre_resultat_trouve-1;$i >= 0;$i--) {
                $row = pg_fetch_array($resultat_retrieve_tableau_data, $i);
                foreach($row as $key => $value) {
                    $value_temp = strip_tags($value); //dans le cas où la valeur contient des balises HTML, value_temp ne contient que la valeur sans le code HTML
					if (!is_integer($value_temp + 0) and is_numeric($value_temp)) { // petite astuce pour convertir les donnees (string) en double et integer (pour formater les doubles avec 2 decimales)
                        $new_value = number_format($value_temp, $this->data_precision, ".", ""); //mets value_temp avec 2 décimales
                        $row[$key] = str_replace($value_temp, $new_value , $row[$key]); //remplace la valeur possedant plusieurs décimales avec la valeur à 2 décimales
                    } else {
                        $row[$key] = $value;
                    }
                }
                // initialise le tableau des données avec les données d'abscisse et les données excel qui ne contiendront pas les balises HTML de mise en page
                $this->tableau_data_excel[0][] = $row[0];
                $this->tableau_data[0][] = $row[0];
                // les données suivantes sont les données dans l'ordre d'affichage des data telles que saisies dans l'interface
                $j = 1;
                $array_data_list = explode(",", $this->table_data_list);
                foreach ($array_data_list as $id_data) {
                    // la clé de tableau_data est l'identifinat de la donnée présent dans la BDD
                    // formatte la donnée avec les font selectionnées
                    $this->tableau_data_excel[$id_data][] = $row[$j];
                    $this->tableau_data[$id_data][] = $this->tableau_font_debut[$id_data] . $row[$j] . $this->tableau_font_fin[$id_data];
                    $j++;
                }
            }
            // la première donnée est toujours la donnée d'abscisse
            // les données abscisse sont donc la première colonne du tableau
            $this->tableau_abscisse = $this->tableau_data[0];
            // sauveagarde des données pour l'export vers Excel avec un identifiant pour chaque tableau qui est le numéro du tableaue accolé au numéro d'ordre
            $tableau_legend_export_excel[$this->tableau_number] = $this->tableau_entete_colonnes;
            session_register("tableau_legend_export_excel");
            $tableau_data_export_excel[$this->tableau_number] = $this->tableau_data_excel;
            session_register("tableau_data_export_excel");
            $tableau_abscisse_export_excel[$this->tableau_number] = $tableau_data_export_excel[$this->tableau_number][0];
            session_register("tableau_abscisse_export_excel");
        } else {
            $this->data_error = 1;
            $this->display_erreur = "No Data Found";
        }
    }
    // fonction qui collecte les actions relatives à l'abscisse du tableau
    function Tableau_Retrieve_Action()
    {
        global $table_actions_management;
        global $database_connection, $niveau4_vers_php;
        global $font_debut_default, $font_fin_default;
        global $selecteur_general_values;
        // teste si on est dans le cas daily ou weekly
        $daily = 0;
        if ($selecteur_day_value != "") {
            $daily = 1;
            $date_selecteur = $selecteur_general_values["year"] . $selecteur_general_values["month"] . $selecteur_general_values["day"];
        } else {
            $date_selecteur = $selecteur_general_values["year"] . $selecteur_general_values["week"];
        }
        // test si les données abscisse ne sont pas vides
        if (count($this->tableau_abscisse) > 0) {
            // parcoure tous les abscisses du tableau
            // initialise un tableau qui va contenir les valeurs des abscisses de manière unique
            foreach ($this->tableau_abscisse as $key => $valeur_abscisse) {
                // complète par défaut le tableau des pb, action  afin d'avoir une valeur pour tous les abscisses même vide
                $this->tableau_problem[$key] = "None";
                $this->tableau_action[$key] = "";
                $this->tableau_date[$key] = "";
                $this->tableau_date_action[$key] = $date_selecteur;
                // recherche toutes les actions correspondant aux critères de sélection
                // les éléments sont classé du plus récent au plus ancien
                // la requete récupère également l'historique
                $query = "SELECT action_number,network_agregation_value,today,problem,action,date FROM $table_actions_management WHERE network_agregation_value='$valeur_abscisse' and id_graph_table='$this->tableau_number' and today<='$date_selecteur' ORDER BY network_agregation_value DESC, today DESC LIMIT 10";
                $result = pg_query($database_connection, $query);
                $nombre_result_action = pg_num_rows($result);

                if ($nombre_result_action > 0) {
                    // parcoure tous les resultats dans l'ordre décroissant pour avoir le resultat le plus récent en dernier
                    for ($i = $nombre_result_action-1;$i >= 0;$i--) {
                        $row = pg_fetch_array($result, $i);
                        $action_number = $row["action_number"];
                        $problem = wordwrap($row["problem"], 45, '<br>', 1);
                        $action = wordwrap($row["action"], 45, '<br>', 1);
                        $date = $row["date"];
                        $date_action = $row["today"];
                        if ($i == 0) {
                            $this->tableau_action_number[$key] = $action_number;
                            $this->tableau_problem[$key] = $problem;
                            $this->tableau_action[$key] = $font_debut_default . $action . $font_fin_default;
                            $this->tableau_date[$key] = $font_debut_default . $date . $font_fin_default;
                            $this->tableau_date_action[$key] = $date_action;
                        }
                        // on le place ici afin que le formattage de la date soit après le stockage car le format de la date brute sert pour un test dans le lien depuis la colonne pb
                        if ($daily == 1) {
                            $date_action = substr($date_action, -2, 2) . "-" . substr($date_action, -4, 2) . "-" . substr($date_action, 0, 4);
                        } else {
                            $date_action = substr($date_action, -2, 2) . "-" . substr($date_action, 0, 4);
                        }
                        // l'historique comprend le commentaire le plus récent affiché dans le tableau
                        $this->tableau_action_number_historique[$valeur_abscisse][$i] = $action_number;
                        $this->tableau_problem_historique[$valeur_abscisse][$i] = $problem;
                        $this->tableau_action_historique[$valeur_abscisse][$i] = $action;
                        $this->tableau_date_historique[$valeur_abscisse][$i] = $date;
                        $this->tableau_date_action_historique[$valeur_abscisse][$i] = $date_action;
                    }
                }
            }
            // ajoute les problemes, actions, date au tableau des data
            $this->tableau_data[] = $this->tableau_problem;
            $this->tableau_data[] = $this->tableau_action;
            $this->tableau_data[] = $this->tableau_date;
        }
    }
    // fonction qui affiche un lien sur une colonne
    function Tableau_lien_colonne($lien_vers_fichier, $valeur_donnee, $numero_ligne, $font_debut, $font_fin)
    {
        // le paramètre passé par l'URL est toujour la donnée d'abscisse du tableau (1ère colonne)
		// <font class='texeBlanc'><a target="_top" href=lien_vers_fichier . $this->tableau_abscisse[$numero_ligne]
        ?><font class='texeBlanc'><a target="_top" href="<?=$lien_vers_fichier . $this->tableau_abscisse[$numero_ligne]?>"><?php
        print $valeur_donnee;

        ?></a></font><?php
    }
    // fonction qui génère le commentaire pour un tableau
    function Table_Data_Flying_Window($valeur_abscisse)
    {
        global $couleur_fond_page;

        $nombre_problem_historique = count($this->tableau_problem_historique[$valeur_abscisse]);
        // teste si on a un historique des problèmes
        if ($nombre_problem_historique > 0) {
            $tab_commentaire = "<table border=0 width=100% cellpadding=2><tr><td align=center nowrap><font size=2>Time</font></td><td align=center nowrap><font size=2>Problem</font></td><td align=center nowrap><font size=2>Action</font></td><td align=center nowrap><font size=2>Date</font></td></tr>";
            for ($i = 0;$i < count($this->tableau_problem_historique[$valeur_abscisse]);$i++) {
                $problem_historique = ereg_replace("\r\n", "<br>", str_replace("'", "\'", htmlentities($this->tableau_problem_historique[$valeur_abscisse][$i])));
                $action_historique = ereg_replace("\r\n", "<br>", str_replace("'", "\'", htmlentities($this->tableau_action_historique[$valeur_abscisse][$i])));
                $date_historique = $this->tableau_date_historique[$valeur_abscisse][$i];
                $date_action_historique = $this->tableau_date_action_historique[$valeur_abscisse][$i];
                $tab_commentaire .= "<tr><td nowrap><font size=1>$date_action_historique</font></td><td nowrap><font size=1>$problem_historique</font></td><td nowrap><font size=1>$action_historique</font></td><td nowrap><font size=1>$date_historique</font></td></tr>";
            }
            $tab_commentaire .= "</table>";
            // forme le javascript qui va servir à afficher la fenetre volante
            $javascript = " onMouseOut=\"kill()\" onMouseOver=\"pop('Comments History','$tab_commentaire','$couleur_fond_page')\" ";
        } else {
            $javascript = "";
        }

        return $javascript;
    }
    // fonction qui permet d'ouvrir une fenetre en cliquant sur la colonne
    function Tableau_window_colonne($window, $hauteur_fenetre, $largeur_fenetre, $valeur_donnee, $numero_colonne, $numero_ligne, $font_debut, $font_fin)
    {
        global $niveau4_vers_php;
        global $selecteur_week_value, $selecteur_day_value, $selecteur_month_value, $selecteur_year_value;
        // teste si on est dans le cas daily ou weekly
        if ($selecteur_day_value != "") {
            $date_selecteur = $selecteur_year_value . $selecteur_month_value . $selecteur_day_value;
        } else {
            $date_selecteur = $selecteur_year_value . $selecteur_week_value;
        }

        $valeur_abscisse = $this->tableau_abscisse[$numero_ligne-1];
        // on verifie si la date du commentaire affiché correspond au selecteur
        // si ce n'est pas le cas alors on met le numero de l'action à vide afin de faire un nouvel insert
        if ($this->tableau_date_action[$numero_ligne-1] != $date_selecteur) {
            $action_number = "";
        } else {
            $action_number = $this->tableau_action_number[$numero_ligne-1];
        }
        // s'il existe une action pour l'abscisse alors il suffit de l'utiliser pour connaitre à quelle action on a à faire
        if ($action_number != "" and $numero_colonne > 1) {
            $javascript = " onclick=\"ouvrir_fenetre('$window?network_agregation_value=$valeur_abscisse&tableau_number=$this->tableau_number&action_number=$action_number&date_selecteur=$date_selecteur','fenetre','yes','no','$largeur_fenetre','$hauteur_fenetre')\" ";
        } else { // l'action n'existe pas il faut renvoyer toutes les données à la fene^tre volante pour qu'elle soit créer par l'utilisateur
            // $javascript=" onclick=\"ouvrir_fenetre('$window?tableau_number=$this->tableau_number&network_agregation_value=$valeur_abscisse','fenetre','yes','no','$largeur_fenetre','$hauteur_fenetre')\" ";
            $valeur_abscisse = urlencode($valeur_abscisse);
            $javascript = " onclick=\"ouvrir_fenetre('$window$valeur_abscisse&tableau_number=$this->tableau_number&action_number=$action_number&date_selecteur=$date_selecteur','fenetre','yes','no','$largeur_fenetre','$hauteur_fenetre')\" ";
        }
        return $javascript;
    }
    // fonction qui creee un tableau a partir d'une requete SQL brute
    function sql_create_table($table_title, $retrieve_query, $wanted_fields) // template de creation des tableaux
    {
        echo "<BR>";
        echo "<table width='100%' cellpading='0' border='0'>";
        echo "        <tr>";
        echo "                <td class='font_12_b' align='center'>$table_title</td>";
        echo "        </tr>";
        echo "        <tr>";
        echo "                <td colspan='2' align='center'>";
        if (!isset($this->tableau_width)) {
            $this->tableau_width = 75;
        }
        if (!isset($this->tableau_width)) {
			$this->line_counter = "yes";
		}

        $this->tableau_entete_colonnes = array();
        for ($i = 0;$i < count($wanted_fields);$i++) {
            $this->tableau_entete_colonnes[] = $wanted_fields[$i];
            $this->table_data_list = ($i == 0)? array() : array_merge($this->table_data_list, array("$i"));
        }
        $this->table_data_list = implode(",", $this->table_data_list);
        $this->data_retrieve_query = $retrieve_query;
        $this->Tableau_Generation();
        echo "                </td>";
        echo "        </tr>";
        echo "</table>";
    }
    // fonction qui génère le tableau
    function Tableau_Generation()
    {
        global $couleur_fond_tableau, $font_debut_default, $font_fin_default;
        global $niveau4_vers_php, $niveau4_vers_niveau0, $path_skin,$niveau0;
        //global $test_temp; // pour transmettre le tableau généré au caddy.
        // la valeur 0 est utilisée pour le builder report. Le builder Report gère lui-même la requete SQL et le résultat
        // 15/02/2011 NSE DE Query Builder : suppression de la condition sur le nombre de résultats max (and $this->tableau_number < 10000)
	if ($this->tableau_number > 0 ) {
            // collecte les données générales du tableau
            $this->Tableau_Information($this->tableau_number);
            // collecte les informations sur les données du tableau
            $this->Tableau_Data_Information();
            // crée la reqûete de collecte des données à partir des information sur les données à afficher
            $this->Table_Data_Query_Create();
            // récupère toutes les données nécessaires au tableau
            $this->Tableau_Retrieve_Data();

            $this->nombre_lignes_tableau = count($this->tableau_abscisse); //nombre de lignes du tableau
        }
        // teste s'il faut afficher une liste d'action auquel cas, on récupère les éléments pour les afficher
        if ($this->show_action == 1) {
            $this->Tableau_Retrieve_Action();
            // ajoute les en-têtes des colonnes
            $this->tableau_entete_colonnes[] = "Problem";
            $this->tableau_entete_colonnes[] = "Action";
            $this->tableau_entete_colonnes[] = "Date";
        }
        // teste si le tableau contient des données. S'il n'en contient pas on n'affiche rien
        if ($this->nombre_lignes_tableau > 0) {
            // remplace dans le tableau des entetes, les _ par des espaces uniquement pour un afficgae plus propre
            $this->tableau_entete_colonnes = str_replace("_", " ", $this->tableau_entete_colonnes);
            $nombre_colonne = count($this->tableau_entete_colonnes);
            // DEBUT CONTSRUCTION DU TABLEAU

            // On stocke le tableau pour l'exporter dans le caddy.
            $val = "<table width='100%' cellspacing='0' cellpadding='0' align='center'>";
            $val.="<tr class='texteGrisBold'>";//<td align='right' width='8' bgcolor=".$couleur_fond_tableau." valign='top'>";
			$val.="<td></td>";
			//$val.="<img src='".$path_skin."coin_hg.gif' height='20' width='8'>";
            //$val.="</td>";
            ?>
         <table border="0" width="<?=$this->tableau_width?>%" cellspacing="0" cellpadding="3">
          <thead>
           <tr>
               <th align="right" width="8" bgcolor="<?=$couleur_fond_tableau?>" valign="top">
                   <?php/* Colonne de gauche du Tableau */ ?>
                   <!--<img src="<?=$path_skin?>coin_hg.gif" height="20" width="8">-->
               </th>
                    <?php


            $numero_colonne = 0;
            // Affiche l'entête "Rank" si on veut un compteur de lignes
            if ($this->line_counter == "yes") {
                $numero_colonne++;
				$val.="<td bgcolor=".$couleur_fond_tableau." nowrap align='center'>";
                //$val.="<a href=".$niveau0."php/export_excel_tab.php?u=".uniqid("")."&identifiant=".$this->tableau_number."&type=tableau' target='fichier_excel'><img align='absmiddle' src='../../../../images/icones/excel.gif' border='0'></a><b>";
                //$val.="<a href='' title=".$libelle_entete." onclick=\"this.blur(); return sortTable('offTblBdy".$this->tableau_number."',".$numero_colonne.", true);'>";
				$val .= $libelle_entete;
				$val.="Rank";
                $val.="</td>";
                ?>
               <th bgcolor="<?=$couleur_fond_tableau?>" nowrap align="center">
                   <?php // 15/02/2011 NSE DE Query Builder : Suppression du lien Excel ?>
                   <font class="texteGrisBold">Rank</font>
               </th>
                     <?php
            }
            // AFFICHE LES EN-TETE
			$nb_entete = count($this->tableau_entete_colonnes);
            foreach ($this->tableau_entete_colonnes as $libelle_entete) {
				$val.="<td bgcolor=".$couleur_fond_tableau." nowrap align='center'>";
               // 15/02/2011 NSE DE Query Builder : ajout d'un " manquant ?>
               <th bgcolor="<?=$couleur_fond_tableau?>" nowrap align="center" <?  echo 'style="border-right: #585858 solid 1px;border-top:#585858 solid 1px;border-bottom:#585858 solid 1px;';if($numero_colonne == 0 ) echo 'border-left:#585858 solid 1px;"'; else echo '"'; ?> >
                            <?php
                // 15/02/2011 NSE DE Query Builder : Suppression du lien Ms Excel

				//$val.="<a href='#'";
				//$val.=" title=".$libelle_entete.">";
				//$val.=$libelle_entete;
				//$val.=" onclick=\"this.blur(); return sortTable('offTblBdy".$this->tableau_number."',".$numero_colonne + 1.", true);\">";

				$val.=ucwords($libelle_entete)."</td>";
				?>
				<font class="texteGrisBold">&nbsp;<?=ucwords($libelle_entete)?>&nbsp;</font>
				</th>
                <?php $numero_colonne++;
            }

            ?>
               <?php
			   /* Colonne de droite du Tableau */
               	//$val.="<td width='8' bgcolor=".$couleur_fond_tableau." valign='top'>";
               	//$val.="<img src='".$path_skin."coin_hd.gif' border='0' width='8' height='20'>";
               	$val.="</tr>";
			   ?>
               <th width="8" bgcolor="<?=$couleur_fond_tableau?>" valign="top">
                    <!--<img src="<?=$path_skin?>coin_hd.gif" border="0" width="8" height="20">-->
               </th>
           </tr>
          </thead>
          <tbody id="offTblBdy<?=$this->tableau_number?>">
                <?php
            // AFFICHE LES DONNEES DU TABLEAU
            // convertit le tableau de colonnes en lignes
            // 15/02/2011 NSE DE Query Builder : nombre max de résultats affichés
            $nb_limit = get_sys_global_parameters('query_builder_nb_result_limit',1000);
            $i = 0;
            foreach ($this->tableau_data as $tableau_donnees) {
                $j = 0;
                foreach ($tableau_donnees as $valeur_donnee) {
                    $j++;
                    $this->tableau_data_ligne[$j][$i] = $valeur_donnee;
                    // 15/02/2011 NSE DE Query Builder : limitation de l'afficahge à 1000 résultats
                    if($j>$nb_limit)
                        break 1;
                }
                $i++;
            }

            $compteur_nombre_ligne = 1;
            // parcoure chaque ligne du tableau
            foreach ($this->tableau_data_ligne as $tableau_donnees) {
                if ($class == "alternateRow2") {
                    $class = "alternateRow1";
                } else {
                    $class = "alternateRow2";
                }

				$val.="<tr class='texteGris'>";
				$val.="<td>  </td>";
				//$val.="<td bgcolor=".$couleur_fond_tableau.">";
				//$val.="</td>";
				?>
				<tr class="<?=$class?>">
				<td bgcolor="<?=$couleur_fond_tableau?>"></td>
                <?php
                if ($this->line_counter == "yes") {
					$val.="<td align='center'>";
					$val.=$compteur_nombre_ligne;
					$val.="</td>";
                    ?>
					<td align="center"><?=$font_debut_default . $compteur_nombre_ligne . $font_fin_default?></td>
                    <?php 
				}
                $compteur_nombre_colonne = 1;
                // parcoure chaque colonne d'une ligne du tableau
                foreach ($tableau_donnees as $k=>$valeur_donnee) {
                    $this->Tableau_window_open = ""; //initialise la valeur au début de chaque boucle
                    $this->flying_window_data = ""; //initialise la valeur au début de chaque boucle

					$val.="<td align='center' nowrap>";
                                        // 21/02/2011 NSE DE Query Builder : modification police
                    ?>
					<td class='texteGris' align="center" nowrap style="<?echo ( $compteur_nombre_ligne%2 ? '' : 'background-color:#cccfbe;')?> text-align:center !important; border-bottom: #898989 solid 1px;border-right: #898989 solid 1px;<?if($k == 0 ) echo 'border-left:#585858 solid 1px;"'; else echo '"';?>">
                    <?php
                    // teste s'il faut afficher un lien sur la valeur de la colonne
                    if ($this->lien_depuis_tableau_vers_fichier[$compteur_nombre_colonne] != "") {
                        $this->Tableau_lien_colonne($this->lien_depuis_tableau_vers_fichier[$compteur_nombre_colonne], $valeur_donnee, $compteur_nombre_ligne-1, $font_debut, $font_fin);
                    }
                    // teste si en cliquant sur la valeur, on peut afficher une fenêtre
                    elseif ($this->fenetre_depuis_tableau[$compteur_nombre_colonne] != "" or ($this->show_action == 1 and $compteur_nombre_colonne == $nombre_colonne-2)) {
                        // on est dans le cas où on a défini la fenêtre volante à afficher
                        if ($this->fenetre_depuis_tableau[$compteur_nombre_colonne] != "") {
                            $this->Tableau_window_open = $this->Tableau_window_colonne($this->fenetre_depuis_tableau[$compteur_nombre_colonne], $this->tableau_hauteur_fenetre, $this->tableau_largeur_fenetre, $valeur_donnee, $compteur_nombre_colonne, $compteur_nombre_ligne, $font_debut, $font_fin);

                            //$val.="<a href='#'".$this->flying_window_data." ".$this->Tableau_window_open.">";
                            $val.=$valeur_donnee;
                            //$val.="</a>";

                            ?><a href="#" <?=$this->flying_window_data?> <?=$this->Tableau_window_open?>><?php
							print $font_debut_default . $valeur_donnee . $font_fin_default;
                            ?></a><?php
                        } 
						else { // on est dans le cas de l'affichage des problèmes, actions
                            $fichier_gestion_action = $niveau4_vers_php . "edw_action_management.php?network_agregation_value=";
                            $this->Tableau_window_open = $this->Tableau_window_colonne($fichier_gestion_action, 200, 550, $valeur_donnee, $compteur_nombre_colonne, $compteur_nombre_ligne, $font_debut, $font_fin);
                            $this->flying_window_data = $this->Table_Data_Flying_Window($this->tableau_abscisse[$compteur_nombre_ligne-1]);

                            //$val.="<a href='#'".$this->flying_window_data." ".$this->Tableau_window_open.">";
                            $val.= $valeur_donnee;
                            //$val.="</a>";

                            ?><a href="#" <?=$this->flying_window_data?> <?=$this->Tableau_window_open?>><?php
							print $font_debut_default . $valeur_donnee . $font_fin_default;
                            ?></a><?php
                        }
                    }
					else {
                        // on est à la première colonne pour laquelle on ne peut pas définir de couleur de fond
                        // puisque c'est la valeur de l'abscisse
                        if ($compteur_nombre_colonne == 1) {			
							print $font_debut_default . '&nbsp;'. wordwrap($valeur_donnee, 60, '<br>', 1) . '&nbsp;'. $font_fin_default;
                            $val.= wordwrap($valeur_donnee, 60, '<br>', 1);
                        } else {
							print $font_debut_default . '&nbsp;'.wordwrap($valeur_donnee, 60, '<br>', 1) .'&nbsp;'.  $font_fin_default;
							$val.= wordwrap($valeur_donnee, 60, '<br>', 1);
                        }
                    }
                    $val.="</td>";
                    ?>
					</td>
                    <?php $compteur_nombre_colonne++; //incrémente le compteur du nombre de colonnes du tableau
                }
                $compteur_nombre_ligne++;

                //$val.="<td bgcolor=".$couleur_fond_tableau.">";
                $val.="</tr>";

                ?>
				<td bgcolor="<?=$couleur_fond_tableau?>"></td>
				</tr>
				<?php 
			}//fin foreach

				//$val.="<tr height='6'>";
            ?>
          </tbody>
          <tr height="6">
                    <?php
            // rajoute une ligne en  bas du fichier. On ne fait pas un colspan afin d'utiliser le .js qui permet de classer les données du tableau
            for ($i = 1;$i <= $nombre_colonne + 2;$i++) {
            	//$val.="<td bgcolor=".$couleur_fond_tableau."></td>";
                ?>
				<td bgcolor="<?=$couleur_fond_tableau?>"></td>
                <?php
			}
			$val.="</table>";

			// Insertion du tableau dans la table sys_table_html_buffer.
			global $database_connection,$id_user;

			//$val = "<table><tr><td>BOUHHH</td></tr></table>";

			$query=" select (case when max(id_contenu) IS NULL THEN 1 ELSE max(id_contenu)+1 END) as max_id from sys_contenu_buffer";
			$result=pg_query($database_connection,$query);
			$result_array = pg_fetch_array($result, 0);
			$id_obj_temp = $result_array["max_id"];

			$val= urlencode($val);
			$query=" insert into sys_contenu_buffer (object_contenu_type,id_user,object_type,object_source,object_title,object_id,id_page,object_order,on_off,id_contenu) values ('html','$id_user','table','$val','builder report table','0','0','0','1','$id_obj_temp')";
			//echo $query;
			pg_query($database_connection,$query);

            ?>
          </tr>
       </table>
         <?php
        } else {

            ?><font class="font_12_b">No Data Found</font><?php
        }
    } //fin de la fonction
} //fin de la classe

?>
