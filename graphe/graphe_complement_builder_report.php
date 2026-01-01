<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
class Graph_Complement extends Graph {
    // fonction principale appelée dans le fichier PHP d'affichage d'un graphe à l'écran
    function Graph_Complement($time_agregation, $network_agregation, $graph_number, $on_the_fly, $contour)
    {
        // définti le numéro du graph, l'instance et l'ordre
        $this->time_agregation = $time_agregation;
        $this->network_agregation = $network_agregation;
        $this->graph_number = $graph_number;
        $this->graph_on_the_fly = $on_the_fly;
        $this->graph_contour = $contour;
    }
    // fonction qui génère le graphe à partir de toutes les données
    function Graph_Generation()
    {
        // collecte les informations sur le graphe
        $this->Graph_Information();
        // initialise une instance du graph issu de jpgraph
        $this->Graph_Init();
        // gère la sauvegarde du fichier image
        $this->Graph_Store();
        // collecte toutes les informations sur les données du graphe à partir de la liste des données
        $this->Graph_Data_Information();
        // crée la reqûete de collecte des données à partir des information sur les données à afficher
        $this->Graph_Data_Query_Create();
        // récupère toutes les données nécessaires au graphe
        $this->Graph_Retrieve_Data();
        // prepare tous les éléments du graphes
        if ($this->data_error != 1) {
            $this->Graph_Preparation();
            // affiche le graphe
            $this->Graph_Display();
        } else {
            $this->error_image_display($this->display_erreur);
        }
    }
    // instantie un graph de Jpgraph
    function Graph_Init()
    {
        // teste si on génere un graphe à la volée auquel cas on a pas besoin de nom de fichier
        if ($this->graph_on_the_fly == 0) {
            $this->set_graph_name();
            $this->graph = new Graph($this->largeur_graphe, $this->hauteur_graphe, $this->graph_file);
        } else {
            $this->graph = new Graph($this->largeur_graphe, $this->hauteur_graphe);
        }
        // defini le nombre de données à affciher sur le graphe
        $this->define_graph_period();
    }
    // fonction qui définit le nombre de période sur lesquelles le graph sera affiché
    function define_graph_period()
    {
        if (!isset($this->graph_period)) {
            $this->graph_period = 20;
        }
    }
    // fonction qui génere le nom d'une image ainsi que le nom complet du fichier qui sera stocké sur le disque
    function set_graph_name()
    {
        Global $id_user, $repertoire_physique_niveau0;
        // création du nom de la nouvelle image
        $id = uniqid("");
        $this->nom_image = "image_" . $this->graph_number . "_" . $id_user . "_" . $id . ".png";
        $this->graph_file = $repertoire_physique_niveau0 . "png_file/" . $this->nom_image;
    }
    // stocke le nom du nouveau graphe dans la base de données et efface l'ancien fichier dans la BDD et sur le disque
    function Graph_Store()
    {
        // 17/01/2011 BBX
        // Table user_images obsolète
        // BZ 20200
        /*
        Global $id_user, $repertoire_physique_niveau0, $database_connection;
        // vérifie s'il existe une entrée dans la base user_images de l'identifiant user et du numéro du graphe
        // afin d'effacer la précédente image et en recréer une nouvelle image mise à jour
        $query = "SELECT nom_image FROM user_images WHERE (id_user=$id_user and id_graph=" . $this->graph_number . ")";
        $resultat = pg_query($database_connection, $query);
        if ($resultat <> false) { // teste si la requete s'est bien passé
            $nombre_resultat = pg_num_rows($resultat);
            if ($nombre_resultat > 0) {
                $row = pg_fetch_array($resultat, 0);
                $nom_image = $row["nom_image"];
                if ($nom_image != "") { // si l'utilisateur n'a généré aucune image, l'information dans la base de données est vide
                    // efface l'image précédente
                    $nom_image_to_delete = $repertoire_physique_niveau0 . "png_file/" . $nom_image;
                    if (file_exists($nom_image_to_delete)) {
                        unlink($nom_image_to_delete);
                    }
                }
            } else { // s'il n'existe pas d'image alors on crée une entrée avec le couple (id_user, id_graph) dans la table
                $query = "INSERT INTO user_images (id_user, id_graph, id_instance, ordre) ";
                $query .= "VALUES ('$id_user','" . $this->graph_number . "','" . $this->graph_instance . "','" . $this->graph_order . "')";
                $resultat = pg_query($database_connection, $query);
            }
        }
        // Mets la base à jour avec le nouveau nom de l'image
        $query = "UPDATE  user_images  set nom_image='$this->nom_image' WHERE id_user='$id_user' and id_graph='" . $this->graph_number . "'";
        $resultat = pg_query($database_connection, $query);*/
    }
    // fonction qui collecte les données générale sur le graphe à afficher à partir de la table qui contient les données d'un graphe
    function Graph_Information()
    {
        global $database_connection;

        $query = "SELECT graph_data_list, abscisse_name, ordonnee_left_name, ordonnee_right_name, graph_width, graph_height, graph_comment, graph_complement_sql_join, graph_complement_sql_and, graph_comparaison_field_name, graph_abscisse_field_name, position_legende FROM graph_information where (id_graph='$this->graph_number') LIMIT 1";
        $result = pg_query($database_connection, $query);
        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_array($result, 0);

            $this->graph_data_list = $row["graph_data_list"];
            $this->nom_ordonnee_gauche = $row["ordonnee_left_name"];
            $this->nom_ordonnee_droite = $row["ordonnee_right_name"];
            $this->field_graph_comment = $row["graph_comment"]; //Champ utilisé pour le commentaire
            $this->position_legende = $row["position_legende"];
            $this->nom_abscisse = $row["abscisse_name"];
            // permet de définir la largeur et la longueur à la main
            if (!isset($this->largeur_graphe)) {
                $this->largeur_graphe = $row["graph_width"];
            }
            if (!isset($this->hauteur_graphe)) {
                $this->hauteur_graphe = $row["graph_height"];
            }
            // recherche le nom de l'agregation utilisee dans les tables de donnees a partir du nom general
            $query = "SELECT agregation_name FROM edw_definition_time_agregation WHERE agregation='$this->time_agregation'";
            $result = pg_query($database_connection, $query);
            $row = pg_fetch_array($result, 0);
            $this->time_agregation_field = $row["agregation_name"];
            // recherche le nom de l agregation utilisee dans les tables de donnees a partir du nom general
            $query = "SELECT agregation_name FROM edw_definition_network_agregation WHERE agregation='$this->network_agregation'";
            $result = pg_query($database_connection, $query);
            $row = pg_fetch_array($result, 0);
            $this->network_agregation_field = $row["agregation_name"];
        } else {
            $this->data_error = 1;
            $this->display_erreur = "There is No Graph corresponding to the Number id_graph=$this->graph_number";
        }
    }
    // fonction qui collecte les éléments pour chaque donnée qui va être affichée
    function Graph_Data_Information()
    {
        global $database_connection;

        $condition_requete = "id_data=" . str_replace(",", " or id_data=", $this->graph_data_list);
        $array_data_list = explode(",", $this->graph_data_list);

        $query = "SELECT data_name,edw_group_table, busy_hour,id_data, data_legend, position_ordonnee, display_type, line_design, data_type, color, filled_color, data_value FROM graph_data where $condition_requete";
        $result_data = pg_query($database_connection, $query);
        $this->nombre_graph_data = pg_num_rows($result_data);
        // récupère dans un tableau la liste de tous les id_data
        // l'objectif va être de les classer pour conserver l'ordre d'affichage des données
        for ($i = 0;$i < $this->nombre_graph_data;$i++) {
            $row = pg_fetch_array($result_data, $i);
            $id_data = $row["id_data"];
            $this->data_legend[$id_data] = $row["data_legend"];
            $this->legend_export_excel[$id_data] = $row["data_legend"];
            $this->data_edw_group_table[$id_data] = $row["edw_group_table"];
            $this->data_position_ordonnee[$id_data] = $row["position_ordonnee"];
            $this->data_display_type[$id_data] = $row["display_type"];
            $this->data_line_design[$id_data] = $row["line_design"];
            $this->data_color[$id_data] = $row["color"];
            $this->data_busy_hour[$id_data] = $row["busy_hour"];
            $this->data_filled_color[$id_data] = $row["filled_color"];

            if ($row["data_type"] == "rawdata") {
                $this->data_type[$id_data] = "raw";
            } else {
                $this->data_type[$id_data] = $row["data_type"];
            }

            $numero_position_affichage = array_search($id_data, $array_data_list); //determine la position d'affichage du id_data
            if ($this->data_type[$id_data] != "fixed") {
                $this->non_fixed_value_id_data_list[$numero_position_affichage] = $id_data;
                $this->data_value[$id_data] = $row["data_value"];
                if ($row["edw_group_table"] == "mixed") {
                    $this->data_table_name[$id_data] = "edw_";
                }

                if ($row["busy_hour"] == "") {
                    $this->data_table_name[$id_data] .= $row["edw_group_table"] . "_" . $this->data_type[$id_data] . "_" . strtolower($this->network_agregation) . "_" . strtolower($this->time_agregation);
                } else {
                    $this->data_table_name[$id_data] .= $row["edw_group_table"] . "_" . $this->data_type[$id_data] . "_" . strtolower($this->network_agregation) . "_" . strtolower($this->time_agregation) . "_" . $row["busy_hour"];
                }
            } else {
                $this->fixed_value_id_data_list[$numero_position_affichage] = $id_data;
            }
        }
        if (isset($this->non_fixed_value_id_data_list)) {
            ksort($this->non_fixed_value_id_data_list); //trie le tableau suivant l'ordre des cle ce qui permet de classer les id_data par ordre d'affichage
        }
        if (isset($this->fixed_value_id_data_list)) {
            ksort($this->fixed_value_id_data_list);
        }
    }
    // fonction qui definit les elements associé à l'abscisse
    function Graph_query_abscisse()
    {
        global $agregation_network_value, $selecteur_year_value, $selecteur_month_value, $selecteur_day_value, $selecteur_week_value;

        $this->graph_abscisse_field_name = $this->time_agregation_field;

        switch ($this->time_agregation_field) {
            case "hour" :
                $this->graph_abscisse_field_name = "day"; //pour hour, on fait la comparaison sur day et non pas sur  hour
                $this->graph_abscisse_comparaison_field_name = $selecteur_year_value . $selecteur_month_value . $selecteur_day_value;
                break;

            case "day" :
                $this->graph_abscisse_field_name = "day";
                $this->graph_abscisse_comparaison_field_name = $selecteur_year_value . $selecteur_month_value . $selecteur_day_value;
                break;

            case "week" :
                $this->graph_abscisse_field_name = "week";
                $this->graph_abscisse_comparaison_field_name = $selecteur_year_value . $selecteur_week_value;
                break;

            case "month" :
                $this->graph_abscisse_field_name = "month";
                $this->graph_abscisse_comparaison_field_name = $selecteur_year_value . $selecteur_month_value;
                break;
        }
        // teste si on a pré-défini l'abscisse via la valeur abscisse_field_name
        if (isset($this->abscisse_field_name)) {
            // l'abscisse correspond à la valeur pre-definie
            $this->graph_abscisse = $this->abscisse_field_name;
        } else {
            $this->graph_abscisse = $this->graph_abscisse_field_name;
        }

        if ($this->network_agregation == "cell") {
            $omc_index = $this->get_omcindex($agregation_network_value);
            $this->network_agregation_comparaison_field_name = $omc_index;
        } else {
            $this->network_agregation_comparaison_field_name = $agregation_network_value;
        }
    }
    // fonction qui retourne l'OMC index a partir d'un cellname
    function get_omcindex($cellname)
    {
        global $database_connection;

        $query = "SELECT omc_index FROM edw_object_ref WHERE cellname='$cellname' LIMIT 1";
        $recherche_cellname = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($recherche_cellname);
        if ($nombre_resultat > 0) {
            $row = pg_fetch_array($recherche_cellname, 0);
            $omc_index = $row["omc_index"];
        } else {
            $omc_index = 0;
        }

        return $omc_index;
    }
    // fonction qui formatte les valeurs des données de l'abscisse
    function Graph_Data_Abscisse_Format()
    {
        switch ($this->time_agregation_field) {
            // formatte l'heure
            case "hour" :
                $this->nom_abscisse = "Hour      ";

                $compteur_interval = 1;
                for ($i = count($this->tableau_abscisse)-1;$i >= 0;$i--) {
                    $value = $this->tableau_abscisse[$i];
                    $day_displayed = substr($value, 6, 2);
                    if ($day_displayed != $current_day) {
                        $this->tableau_abscisse[$i] = substr($value, 8, 2) . "\n" . substr($value, 6, 2) . "-" . substr($value, 4, 2);
                        if ($compteur_interval == $this->x_interval) {
                            $current_day = $day_displayed;
                            $compteur_interval = 1;
                        } else {
                            $compteur_interval++;
                        }
                    } else {
                        $this->tableau_abscisse[$i] = substr($value, 8, 2);
                    }
                }
                break;

            case "day": // formatte un jour en jj/mm/aa
                $this->nom_abscisse = "Day";

                foreach ($this->tableau_abscisse as $key => $value) {
                    $this->tableau_abscisse[$key] = substr($value, 6, 2) . "-" . substr($value, 4, 2) . "-" . substr($value, 2, 2);
                }
                break;

            case "week": // formatte un week en attribuant un numero
                $this->nom_abscisse = "      Week";

                foreach ($this->tableau_abscisse as $key => $value) {
                    $this->tableau_abscisse[$key] = substr($value, 4, 2);
                }
                break;

            case "month": // formatte un mois en attribuant un numero
                $this->nom_abscisse = "      Month";
                $this->x_orientation = 0;
                break;

            default:
                // on ne fait rien
                break;
        }
    }
    // génère la requête de retrieve des données des data
    function Graph_Data_Query_Create()
    {
        // gere l'element abscisse qui est en fait le premier champ de la query
        $this->Graph_query_abscisse();
        // PARTIE FROM
        // parcoure la liste des tables dédoublonnées pour créer la partie FROM de la query
        $liste_nom_table_dedoublonnee = array_unique($this->data_table_name);
        $compteur_nombre_table = count($liste_nom_table_dedoublonnee);
        // parcoure la liste dedoublonee des tables
        $i = 0;
        foreach ($liste_nom_table_dedoublonnee as $key => $nom_table) {
            // s'il n'y a qu'une seule table, on ne met pas d'alias
            if ($compteur_nombre_table == 1) {
                $this->data_retrieve_query_from .= " FROM " . $nom_table . " ";
            } elseif ($i == 0) {
                $this->data_retrieve_query_from .= " FROM " . $nom_table . " as table$key";
            } else {
                if ($this->time_agregation_field == "hour") {
                    $this->data_retrieve_query_from .= " JOIN " . $nom_table . " as table$key USING ($this->time_agregation_field, $this->network_agregation_field, day) ";
                } else {
                    $this->data_retrieve_query_from .= " JOIN " . $nom_table . " as table$key USING ($this->time_agregation_field, $this->network_agregation_field) ";
                }
            }
            $i++;
        }

        $this->data_retrieve_query_from = substr($this->data_retrieve_query_from, 0, -1); //enlève la dernière virgule
        // PARTIE SELECT
        if ($this->time_agregation_field == "hour") {
            $this->data_retrieve_query_select = "SELECT day||hour,"; //la concatenation permet d'avoir l'info sur le jour pour l'affichage
        } else {
            $this->data_retrieve_query_select = "SELECT " . $this->graph_abscisse . ",";
        }
        // mets dans un array la liste des data dans l'ordre tel que sauvegardé dans la BDD
        $array_data_list = explode(",", $this->graph_data_list);
        // parcoure la liste des data pour créer la partie Select de la query
        foreach ($array_data_list as $id_data) {
            // teste si la données est une donnée fixe ou pas
            if ($this->data_type[$id_data] != "fixed") {
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
        }
        // PARTIE WHERE
        $this->data_retrieve_query_where = "WHERE ";
        $this->data_retrieve_query_where .= $this->graph_abscisse . "<='" . $this->graph_abscisse_comparaison_field_name . "' AND ";
        $this->data_retrieve_query_where .= $this->network_agregation_field . "='" . $this->network_agregation_comparaison_field_name . "' ";
        if ($this->time_agregation_field == "hour") {
            $this->data_retrieve_query_where .= " ORDER BY " . $this->graph_abscisse . " DESC,hour DESC LIMIT " . $this->graph_period;
        } else {
            $this->data_retrieve_query_where .= " ORDER BY " . $this->graph_abscisse . " DESC LIMIT " . $this->graph_period;
        }
        // REQUETE COMPLETE
        // les conditions contiennent des valeurs du type $week, $cellid qu'il faut évaluer
        eval("\$this->data_retrieve_query_where = \"$this->data_retrieve_query_where\" ;") ;
        // concatène les éléments de la requête et ajoute la limite sur la période
        $this->data_retrieve_query = substr($this->data_retrieve_query_select, 0, -1) . " " . $this->data_retrieve_query_from . " " . $this->data_retrieve_query_where;
    }
    // fonction qui collecte toutes les données susceptibles d'être affichées dans un graphe
    // (données, commentaires etc...)
    function Graph_Retrieve_Data()
    {
        global $database_connection;
        // execute la requête pour récupérer toutes les données
        $resultat_retrieve_graph_data = pg_query($database_connection, $this->data_retrieve_query);
        $nombre_resultat_trouve = pg_num_rows($resultat_retrieve_graph_data);
        if ($nombre_resultat_trouve > 0) {
            // parcoure les lignes du résultat pour charger les données dans des tableaux
            for ($i = $nombre_resultat_trouve-1;$i >= 0;$i--) {
                $row = pg_fetch_array($resultat_retrieve_graph_data, $i);
                // la première donnée est toujours la donnée d'abscisse
                $this->tableau_abscisse[] = $row[0];
                // les données suivantes sont les données dans l'ordre d'affichage des data telles que saisies dans l'interface
                $j = 1;

                foreach ($this->non_fixed_value_id_data_list as $id_data) {
                    if ($row[$j] === null) {
                        $this->tableau_data[$id_data][] = 0;
                    } elseif (trim($row[$j]) != "" and $row[$j] > 0) { // teste si la valeur retrounee est differente de vide sinon on ne peut pas afficher le graphe
                        // la clé de tableau_data est l'identifinat de la donnée présent dans la BDD
                        $this->tableau_data[$id_data][] = $row[$j];
                    } else { // force a 0 les valeurs non presentes
                        $this->tableau_data[$id_data][] = 0;
                    }
                    $j++;
                }
            }
            // s'il existe des data qui contiennent des données fixes alors un complète tableau_data
            if (count($this->fixed_value_id_data_list) > 0) {
                foreach ($this->fixed_value_id_data_list as $id_data) {
                    $tab_temp = array();
                    $this->tableau_data[$id_data] = array_pad($tab_temp, $this->graph_period, $this->data_value[$id_data]);
                }
            }
            // conserve une copie du tableau des abscisses non modifiés pour l'utiliser dans la saisie des commentaires et les graphes qui s'affiche sur les labels
            $this->tableau_abscisse_non_modifie = $this->tableau_abscisse;
        } else {
            $this->data_error = 1;
            $this->display_erreur = "No Data Found";
        }
    }
    // fonction qui génère le commentaire pour un graphe
    function Graph_Retrieve_Data_Comment()
    {
        // données de environnement_graphe.php
        global $nombre_week_commentaires, $database_connection;
        global $couleur_fond_global;
        global $last_comment_pdf_export;

        $last_comment_pdf_export[$this->graph_number] = "";
        $query = "SELECT value_time_agregation,comment, date FROM edw_comments_data WHERE value_time_agregation<='$this->graph_abscisse_comparaison_field_name' and value_geographic_agregation='$this->network_agregation_comparaison_field_name' and time_agregation='$this->time_agregation' and geographic_agregation='$this->network_agregation' and id_graph_table='$this->graph_number' ORDER BY value_time_agregation DESC LIMIT $nombre_week_commentaires";
        $result_comment = pg_query($database_connection, $query);
        $nombre = pg_num_rows($result_comment);
        $tab_commentaire = "<table border=0><tr><td align=center nowrap><font size=2>" . ucfirst($this->graph_abscisse) . "</font></td><td align=center nowrap><font size=2>Comment</font></td><td align=center nowrap><font size=2>Notification</font></td></tr>";
        if ($nombre > 0) {
            $compteur = 0;
            for ($i = 0;$i < $nombre;$i++) {
                $row = pg_fetch_array($result_comment, $i);
                $comment = wordwrap(htmlspecialchars($row["comment"], ENT_QUOTES), 100, '<br>', 1);
                $valeur_temps = $row["value_time_agregation"];
                switch ($this->time_agregation) {
                    case "hourly":
                        $valeur_temps = substr($valeur_temps, -2, 2) . "-" . substr($valeur_temps, -4, 2) . "-" . substr($valeur_temps, 0, 4);
                        break;

                    case "daily":
                        $valeur_temps = substr($valeur_temps, -2, 2) . "-" . substr($valeur_temps, -4, 2) . "-" . substr($valeur_temps, 0, 4);
                        break;
                    case "weekly":
                        $valeur_temps = substr($valeur_temps, -2, 2) . "-" . substr($valeur_temps, 0, 4); ;
                        break;
                } // switch
                $date_saisie = $row["date"];
                if ($compteur == 0) {
                    $this->current_comment[0] = $valeur_temps;
                    $this->current_comment[1] = $comment;
                    $this->current_comment[2] = $date_saisie;
                    $last_comment_pdf_export[$this->graph_number] = $valeur_temps . " : " . $comment;
                    session_register("last_comment_pdf_export");
                }
                $comment = ereg_replace("\r\n", "<br>", $comment);
                $tab_commentaire .= "<tr><td nowrap><font size=1>" . $valeur_temps . "</font></td><td nowrap><font size=1>" . $comment . "</font></td><td nowrap><font size=1>" . $date_saisie . "</font></td></tr>";
                $compteur++;
            }
        }
        $tab_commentaire .= "</table>";

        return $tab_commentaire;
    }
    // fonction qui calcule l'orientation en fonction
    // de la longueur de chaine des abscisses
    function Graph_abscisse_orientation()
    {
        // il se peut qu'on definisse en dur l'orientation d'un graphe
        if (!isset($this->x_orientation)) {
            // calcule la longueur max des abscisses à affciher
            $taille_abscisse_max = 0;
            foreach ($this->tableau_abscisse as $valeur_abscisse) {
                if (strlen($valeur_abscisse) > $taille_abscisse_max) {
                    $taille_abscisse_max = strlen($valeur_abscisse);
                }
            }

            $this->abscisse_longueur_max = $taille_abscisse_max;
            switch ($taille_abscisse_max) {
                case ($taille_abscisse_max < 4):
                    $this->x_orientation = 0;
                    break;
                case ($taille_abscisse_max < 7):
                    $this->x_orientation = 20;
                    break;
                case ($taille_abscisse_max >= 7):
                    $this->x_orientation = 20;
                    break;
            } // switch
        }
    }
    // fonction qui calcule le nombre d'intervalles en fonction
    // du nombre d'abscisses à afficher
    function Graph_abscisse_interval()
    {
        // calcule l'intervalle en fonction du nombre de données d'abscisse à afficher.
        $this->nombre_abscisse_graphe = count($this->tableau_abscisse); // compte le nombre de  valeurs pour l'axe des abscisses
        if (!isset($this->x_interval)) {
            switch ($this->nombre_abscisse_graphe) {
                case ($this->nombre_abscisse_graphe < 25):
                    $this->x_interval = 1;
                    break;
                case ($this->nombre_abscisse_graphe < 50):
                    $this->x_interval = 2;
                    break;
                case ($this->nombre_abscisse_graphe < 50):
                    $this->x_interval = 3;
                    break;
                case ($this->nombre_abscisse_graphe < 120):
                    $this->x_interval = 4;
                    break;
                case ($this->nombre_abscisse_graphe >= 120):
                    $this->x_interval = 5;
                    break; ;
            }
        }
    }
    // fonction qui calcule le min et le max de chaque axe des ordonnées
    // definit la valeur min et la valeur max de chaque axe du graphe
    function Graph_min_max_yaxis()
    {
        // on fixe les valeurs min à zero par défault si ce n'est pas déjà le cas
        if (!isset($this->min_ordonnee_left)) {
            $this->min_ordonnee_left = 0;
        } else {
            $flag_min_left = false; //permet de savoir que le min_left a été determiné en dur par l'utilisateur
        }
        if (!isset($this->min_ordonnee_right)) {
            $this->min_ordonnee_right = 0;
        } else {
            $flag_min_right = false; //permet de savoir que le min_right a été determiné en dur par l'utilisateur
        }

		//Determine le MIN des données à droite et à gauche
        foreach ($this->tableau_data as $id_data => $tableau_donnees) {
            $valeur_min = min($tableau_donnees);
            if ($this->data_position_ordonnee[$id_data] == "left") {
                if ($valeur_min < $this->min_ordonnee_left && !$flag_min_left) { // recherche le min de l'ordonnee gauche pour ensuite utiliser cette valeur pour positionner correctement la marge du graphe
                    $this->min_ordonnee_left = $valeur_min;
                }
            } else {
                $this->flag_ordonnee_right = true; //information qui indique qu'on a bien une ordonnée droite à afficher
                if ($valeur_min < $this->min_ordonnee_right && !$flag_min_right) { // recherche le min de l'ordonnee droite pour ensuite utiliser cette valeur pour positionner correctement la marge du graphe
                    $this->min_ordonnee_right = $valeur_min;
                }
            }
        }
        // Determine le MAX des données à droite et à gauche(independant du calcul du Min est fait exprès)
        foreach ($this->tableau_data as $id_data => $tableau_donnees) {
            $valeur_max = max($tableau_donnees);
            if ($this->data_position_ordonnee[$id_data] == "left") {
                if ($valeur_max > $this->max_ordonnee_left) { // recherche le max de l'ordonnee gauche pour ensuite utiliser cette valeur pour positionner correctement la marge du graphe
                    $this->max_ordonnee_left = $valeur_max;
                }
            } else {
                $this->flag_ordonnee_right = true; //information qui indique qu'on a bien une ordonnée droite à afficher
                if ($valeur_max > $this->max_ordonnee_right) { // recherche le max de l'ordonnee droite pour ensuite utiliser cette valeur pour positionner correctement la marge du graphe
                    $this->max_ordonnee_right = $valeur_max;
                }
            }
        }
    }
    // fonction qui calcule le min et le max de chaque axe des ordonnées
    // definit la valeur min et la valeur max de chaque axe du graphe
    function Graph_margin_calculation()
    {
        global $marge_espace_gauche_default, $marge_espace_droit_default, $marge_espace_haut_default, $marge_espace_bas_default;
        // defini les marges par default à partir des éléments de envionnement_graphe.php
        $this->marge_espace_gauche = $marge_espace_gauche_default;
        $this->marge_espace_droit = $marge_espace_droit_default;
        $this->marge_espace_haut = $marge_espace_haut_default;
        $this->marge_espace_bas = $marge_espace_bas_default;
        // redefini les marges droite et gauche en fonction des valeurs max trouvee pour chaque ordonnee
        if ($this->no_left_ordonnee_label != 1) { // teste si on doit afficher le label de l'ordonne gauche
            switch (strlen($this->max_ordonnee_left)) {
                case (strlen($this->max_ordonnee_left) <= 3):
                    $coeff_left = 0.8;
                    break;
                case (strlen($this->max_ordonnee_left) <= 5):
                    $coeff_left = 1;
                    break;
                case (strlen($this->max_ordonnee_left) <= 7):
                    $coeff_left = 1.2;
                    break;
                case (strlen($this->max_ordonnee_left) > 7):
                    $coeff_left = 1.4;
                    break;
            } // switch
        } else {
            switch (strlen($this->max_ordonnee_left)) {
                case (strlen($this->max_ordonnee_left) <= 3):
                    $coeff_left = 0.3;
                    break;
                case (strlen($this->max_ordonnee_left) <= 5):
                    $coeff_left = 0.6;
                    break;
                case (strlen($this->max_ordonnee_left) <= 7):
                    $coeff_left = 0.8;
                    break;
                case (strlen($this->max_ordonnee_left) > 7):
                    $coeff_left = 0.9;
                    break;
            } // switch
        }

        if ($this->no_right_ordonnee_label != 1) { // teste si on doit afficher le label de l'ordonne gauche
            switch (strlen($this->max_ordonnee_right)) {
                case (strlen($this->max_ordonnee_right) <= 3):
                    $coeff_right = 0.8;
                    break;
                case (strlen($this->max_ordonnee_right) <= 5):
                    $coeff_right = 1;
                    break;
                case (strlen($this->max_ordonnee_right) <= 7):
                    $coeff_right = 1.2;
                    break;
                case (strlen($this->max_ordonnee_right) > 7):
                    $coeff_right = 1.4;
                    break;
            } // switch
        } else {
            switch (strlen($this->max_ordonnee_right)) {
                case (strlen($this->max_ordonnee_right) <= 3):
                    $coeff_right = 0.3;
                    break;
                case (strlen($this->max_ordonnee_right) <= 5):
                    $coeff_right = 0.6;
                    break;
                case (strlen($this->max_ordonnee_right) <= 7):
                    $coeff_right = 0.8;
                    break;
                case (strlen($this->max_ordonnee_right) > 7):
                    $coeff_right = 0.9;
                    break;
            } // switch
        }
        // teste si la legende est positionnee à droite du graphe
        if ($this->position_legende == "right") {
            $coeff_right = $coeff_right * 2;
        }
        // teste si on a aucune echelle de droite et la légende qui n'est pas positionnée à droite
        if (!array_search("right", $this->data_position_ordonnee) && $this->position_legende != "right") {
            $coeff_right = 0.5;
        }
        // teste si la legende est positionnee en haut du graphe
        if ($this->position_legende != "right") {
            $this->marge_espace_haut = 35;
        }

        $this->marge_espace_gauche = $this->marge_espace_gauche * $coeff_left;
        $this->marge_espace_droit = $this->marge_espace_droit * $coeff_right;
    }

    function Graph_generalities()
    {
        global $couleur_fond_graphe, $couleur_marge_graphe;

        $this->Graph_Data_Abscisse_Format();
        $this->Graph_min_max_yaxis();
        $this->Graph_abscisse_interval();
        $this->Graph_abscisse_orientation();
        $this->Graph_margin_calculation();
        // généralités sur le graphe qui sont figées
        $this->graph->SetScale("textlin");
        $this->graph->yscale->SetAutoMin($this->min_ordonnee_left);
        $this->graph->img->SetAntiAliasing();
        $this->graph->SetMarginColor($couleur_marge_graphe);
        $this->graph->SetColor($couleur_fond_graphe);
        // couleur et ticks des 2 axes des ordonnées
        $this->graph->yaxis->SetColor("slategray");
        $this->graph->yaxis->SetTickSide(SIDE_LEFT);

        if ($this->position_legende != "right") {
            $this->graph->legend->SetLayout(LEGEND_HOR);
            $this->graph->legend->Pos(0.5, 0.1, "center", "center");
        } else {
            $this->graph->legend->SetLayout(LEGEND_VER);
            $this->graph->legend->Pos(0.01, 0.5, "right", "center");
        }
        $this->graph->legend->Setshadow(false);
        // défini les marges du graphes. Par défaut, les marges sont définies dans "environnement_graphe.php"
        $this->graph->img->SetMargin($this->marge_espace_gauche, $this->marge_espace_droit, $this->marge_espace_haut, $this->marge_espace_bas);
        // paramétrage de l'axe des abscisse du graphe
        $this->graph->xaxis->title->Set($this->nom_abscisse);
        $this->graph->xaxis->SetTickLabels($this->tableau_abscisse);
        $this->graph->xaxis->SetTextLabelInterval($this->x_interval); //affcihe les labels toutes les n positions
        $this->graph->xaxis->SetTextTickInterval(1);
        $this->graph->xaxis->SetFont(FF_VERDANA, FS_NORMAL, 6);
        $this->graph->xaxis->SetLabelAngle($this->x_orientation);
        // affiche le text de l'ordonnée à gauche
        if ($this->no_left_ordonnee_label == "") {
            $txt1 = new Text($this->nom_ordonnee_gauche);
            $txt1->Pos(0.005, 0.5, "left", "center");
            $txt1->SetOrientation("90");
            $txt1->SetColor("slategray");
            $this->graph->AddText($txt1);
        }

        if ($this->flag_ordonnee_right) {
            $this->graph->SetY2Scale("lin");
            $this->graph->y2scale->SetAutoMin($this->min_ordonnee_right);
            $this->graph->y2axis->SetColor("red");
            $this->graph->y2axis->SetTickSide(SIDE_RIGHT);
            // affiche le text de l'ordonnée à droite
            if ($this->no_right_ordonnee_label == "") {
                $txt2 = new Text($this->nom_ordonnee_droite);
                $txt2->Pos(0.99, 0.5, "right", "center");
                $txt2->SetOrientation("90");
                $txt2->SetColor("red");
                $this->graph->AddText($txt2);
            }
        }
    }
    // creation d'une donnée de type "line"
    function Graph_line_creation($id_data, $compteur)
    {
        $this->plot[$compteur] = new Lineplot($this->tableau_data[$id_data]);
        // légende de la donnée avec l'identification de la scale sur laquelle est affichée la donnée
        if ($this->data_position_ordonnee[$id_data] == 'right') {
            $display_cote = ' (R)';
        } else {
            $display_cote = ' (L)';
        }
        $this->plot[$compteur]->SetLegend($this->data_legend[$id_data] . $display_cote);
        // couleur
        $this->plot[$compteur]->setcolor($this->data_color[$id_data]);
        // couleur pleine pour une ligne
        if ($this->data_filled_color[$id_data] != "") {
            if ($this->set_transparency == 1) {
                $this->plot[$compteur]->setfillcolor($this->data_filled_color[$id_data] . "@0.6");
            } else {
                $this->plot[$compteur]->setfillcolor($this->data_filled_color[$id_data]);
            }
        }
        // si la ligne correspond à une valeur fixe alors on affiche une ligne pointillée
        if ($this->data_type[$id_data] == "fixed") {
            $this->plot[$compteur]->SetStyle("dashed");
        }
        // mark pour l'affichage sur les lignes
        switch ($this->data_line_design[$id_data]) {
            case "circle" :
                $this->plot[$compteur]->mark->SetType(MARK_CIRCLE);
                $this->plot[$compteur]->mark->Setfillcolor($this->data_color[$id_data]);
                break;
            case "square" :
                $this->plot[$compteur]->mark->SetType(MARK_SQUARE);
                $this->plot[$compteur]->mark->Setfillcolor($this->data_color[$id_data]);
                break;
            case "diamond" :
                $this->plot[$compteur]->mark->SetType(MARK_DIAMOND);
                $this->plot[$compteur]->mark->Setfillcolor($this->data_color[$id_data]);
                break;
        }
        // stocke la ligne ainsi crée en fonction de son côté d'affihcage (gauche/droite)
        // et fonction de la couleur pleine ou non de la ligne
        // cela sert à gérer les priorité d'affcihage avec le display des bar
        if ($this->data_position_ordonnee[$id_data] == 'right') {
            if ($this->data_filled_color[$id_data] == "") {
                $this->line["right"][] = $this->plot[$compteur];
            } else {
                $this->line_prioritary["right"][] = $this->plot[$compteur];
            }
        } else {
            if ($this->data_filled_color[$id_data] == "") {
                $this->line["left"][] = $this->plot[$compteur];
            } else {
                $this->line_prioritary["left"][] = $this->plot[$compteur];
            }
        }
    }
    // creation d'une donnée de type bar
    function Graph_bar_creation($id_data, $compteur)
    {
        $this->plot[$compteur] = new Barplot($this->tableau_data[$id_data]);
        // légende de la donnée
        if ($this->data_position_ordonnee[$id_data] == 'right') {
            // $this->plot[$compteur]->SetAlign("center");
            $display_cote = ' (R)';
        } else {
            // $this->plot[$compteur]->SetAlign("left");
            $display_cote = ' (L)';
        }

        $this->plot[$compteur]->SetLegend($this->data_legend[$id_data] . $display_cote);
        // couleur
        $this->plot[$compteur]->setcolor($this->data_color[$id_data]);
        // couleur pleine pour une barre
        if ($this->data_filled_color[$id_data] != "") {
            if ($this->settransparency == 1) {
                $this->plot[$compteur]->setfillcolor($this->data_filled_color[$id_data] . "@0.6");
            } else {
                $this->plot[$compteur]->setfillcolor($this->data_filled_color[$id_data]);
            }
        }
        // gere les barres entre le côté droit et le côté gauche
        // ainsi que les barres cumulées et nn accumulées
        if ($this->data_position_ordonnee[$id_data] == 'right') {
            // cumul les bars plot côté droit
            if ($this->data_display_type[$id_data] == "bar") {
                $this->standard_bar["right"][] = $this->plot[$compteur];
            } else {
                $this->cumulated_bar["right"][] = $this->plot[$compteur];
            }
        } else { // côté gauche
            if ($this->data_display_type[$id_data] == "bar") {
                $this->standard_bar["left"][] = $this->plot[$compteur];
            } else {
                $this->cumulated_bar["left"][] = $this->plot[$compteur];
            }
        }
    }
    // effectue des verifications sur les données
    function Graph_verification()
    {
        // vérifie qu'il existe bien des abscisses sinon il faut en construire
        if (!isset($this->tableau_abscisse) || count($this->tableau_abscisse) == 0) {
            // prends le nombre de valeurs de la premiere donnée à affciher
            $premier_element_tableau = array_slice ($this->tableau_data, 0, 1);
            $cle_array = array_keys($premier_element_tableau);
            $nombre_valeurs = count($premier_element_tableau[$cle_array[0]]);
            for ($i = 1; $i <= $nombre_valeurs;$i++) {
                $this->tableau_abscisse[$i-1] = $i;
            }
        }
        // verifie que le nombre de valeurs pour chaque donnée du graphe correspond au nombre de valeurs d'abscisse
        foreach ($this->tableau_data as $tableau_one_data) { // verifie aue chaque donnee du tableau contient bien le meme nobre d'information que les donnees en abscisse
            if (count($tableau_one_data) != $this->nombre_abscisse_graphe) {
                $this->data_error == 1;
                $this->display_erreur = "Inconsistency data. Graph unable to be displayed correctly";
                break;
            }
        }
        // vérfie qu'il y a bien des données à affciher à gauche sur le graphe
        $array_data_list = explode(",", $this->graph_data_list);
        $flag_data_left = false;
        foreach ($array_data_list as $id_data) {
            if ($this->data_position_ordonnee[$id_data] == "left") {
                $flag_data_left = true;
                break;
            }
        }
        if (!$flag_data_left) {
            $this->data_error = 1;
            $this->display_erreur = "The graph must have at least on element on the left scale";
        }
    }
    // fonction qui prepare tous les elements du graphe pour être affichées
    function Graph_Preparation()
    {
        // effectue des vérifications sur les données à affciher
        $this->Graph_verification();
        // verifie qu'on a detecte aucune erreur lors des étapes précédentes
        if ($this->data_error != 1) {
            // fonction qui traite les généralités du graphe
            $this->Graph_generalities();
            $array_data_list = explode(",", $this->graph_data_list);
            $compteur = 0;
            // parcoure toutes les données pour créer des lignes, bar etc...
            foreach ($array_data_list as $id_data) {
                switch ($this->data_display_type[$id_data]) {
                    case "line":
                        $this->Graph_line_creation($id_data, $compteur);
                        break;
                    case "bar":
                        $this->Graph_bar_creation($id_data, $compteur);
                        break;
                    case "cumulated":
                        $this->Graph_bar_creation($id_data, $compteur);
                        break;
                } // switch
                $compteur++;
            }
            // gestion de l'ordonnancement des éléments du graphe
            $this->Graph_Preparation_Display();
        }
    }
    // fonction qui ajoute les elements au graphe en gérant les priorités (ordonnancement)
    // on affiche en premier le côté gauche (inhérent à JPGrah)
    // dans l'ordre d'affcihage, on a d'abord les lignes pleines, les barres puis les lignes vides
    function Graph_Preparation_Display()
    {
        // teste s'il y a des barres sur les 2 Y-axis afin d'utiliser un stratageme pour affcihe les barres proprement sans recouvrement
        // le stratageme consiste à ajouter des données à 0 de maniètre symétriques sur chaque axe
        if ((count($this->cumulated_bar["left"]) > 0 || count($this->standard_bar["left"]) > 0) && (count($this->cumulated_bar["right"]) > 0 || count($this->standard_bar["right"]) > 0)) {
            // determine le nombre de barres max à afficher sur l'un ou l'autres des y-axis
            $nombre_barres_left = count($this->standard_bar["left"]);
            if (count($this->cumulated_bar["left"]) > 0) { // des barres accumulées ne constituent au final qu'une seule barre
                $nombre_barres_left++;
            }
            $nombre_barres_right = count($this->standard_bar["right"]);
            if (count($this->cumulated_bar["right"]) > 0) { // des barres accumulées ne constituent au final qu'une seule barre
                $nombre_barres_right++;
            }
            $data_zero = array();
            $data_zero = array_pad($data_zero, $this->nombre_abscisse_graphe, 0);
            // mets les "zero" à l'êtreme droite du array
            for ($i = 0;$i < $nombre_barres_left + $nombre_barres_right - $nombre_barres_left;$i++) {
                $this->standard_bar["left"][] = new Barplot($data_zero);
            }
            // mets les "zero" à l'êtreme gauche du array
            for ($i = 0;$i < $nombre_barres_left + $nombre_barres_right - $nombre_barres_right;$i++) {
                if (count($this->standard_bar["right"]) == 0) {
                    $this->standard_bar["right"][] = new Barplot($data_zero);
                } else {
                    array_unshift($this->standard_bar["right"], new Barplot($data_zero));
                }
            }
        }
        // ****** COTE GAUCHE ********
        // LIGNES PLEINES
        if (count($this->line_prioritary["left"]) > 0) {
            foreach ($this->line_prioritary["left"] as $line_filled_color_left) {
                $this->graph->Add($line_filled_color_left);
                unset($line_filled_color_left);
            }
        }
        // BARRES
        // on accumule les barre si il y en a de définies ainsi
        if (count($this->cumulated_bar["left"]) > 0) {
            // $this->standard_bar["left"][] = new AccBarPlot($this->cumulated_bar["left"]);
            if (count($this->standard_bar["left"]) == 0) {
                $this->standard_bar["left"][] = new AccBarPlot($this->cumulated_bar["left"]);
            } else {
                array_unshift($this->standard_bar["left"], new AccBarPlot($this->cumulated_bar["left"]));
            }
        }
        if (count($this->standard_bar["left"]) > 0) {
            $gbplot_left = new GroupBarPlot($this->standard_bar["left"]);
            $gbplot_left->SetWidth(0.8);
            $this->graph->Add($gbplot_left);
        }
        // LIGNES NORMALES
        if (count($this->line["left"]) > 0) {
            foreach ($this->line["left"] as $line_left) {
                $this->graph->Add($line_left);
                unset($line_left);
            }
        }
        // ****** COTE DROIT ********
        // LIGNES PLEINES
        if (count($this->line_prioritary["right"]) > 0) {
            foreach ($this->line_prioritary["right"] as $line_filled_color_right) {
                $this->graph->AddY2($line_filled_color_right);
                unset($line_filled_color_right);
            }
        }
        // BARRES
        if (count($this->cumulated_bar["right"]) > 0) { // on accumule les barre si il y en a de définies ainsi
            // array_unshift($this->standard_bar["right"], new AccBarPlot($this->cumulated_bar["right"]));
            $this->standard_bar["right"][] = new AccBarPlot($this->cumulated_bar["right"]);
        }
        if (count($this->standard_bar["right"]) > 0) {
            $gbplot_right = new GroupBarPlot($this->standard_bar["right"]);
            $gbplot_right->SetWidth(0.8);
            $this->graph->AddY2($gbplot_right);
        }
        // LIGNES NORMALES
        if (count($this->line["right"]) > 0) {
            foreach ($this->line["right"] as $line_right) {
                $this->graph->AddY2($line_right);
                unset($line_right);
            }
        }
    }
    // fonction qui cree une image contenant juste un message
    function error_image_display($message)
    {
        global $couleur_marge_graphe, $couleur_fond_graphe;
        // crée une fausse image avec un texte d'erreur
        $graph_value[0] = 0;
        $graph_value[1] = 0;
        $this->graph->SetScale("textlin");
        $this->graph->yaxis->Hide();
        $this->graph->xaxis->Hide();
        $line1plot = new LinePlot($graph_value);
        $this->graph->Add($line1plot);
        // affiche le text de l'ordonnée à gauche
        $txt1 = new Text($message);
        $txt1->Pos(0.4, 0.45, "centered");
        $this->graph->AddText($txt1);
        if ($this->graph_on_the_fly != 1) { // teste si le graphe doit être généré à la volée et donc sans stockage
            if ($this->graph_contour != 0) {
                $this->Graph_Affichage_Contour_Debut();
            }
            // génère le fichier image
            $this->graph->Stroke($this->graph_file);
            echo "<img src=\"/png_file/" . $this->nom_image . "\" border=0>";
            if ($this->graph_contour != 0) {
                $this->Graph_Affichage_Contour_Fin();
            }
        } else {
            $this->graph->Stroke();
        }
    }
    // fonction qui affiche les éléments du graphe
    function Graph_Display()
    {
        // Display the graph
        if ($this->data_error != 1) {
            if ($this->graph_on_the_fly != 1) { // teste si le graphe doit être généré à la volée et donc sans stockage
                // génère le fichier image
                $this->graph->Stroke($this->graph_file);
                // teste si le graph doit avoir un contour
                if ($this->graph_contour == 1) {
                    $this->Graph_Affichage_Contour_Debut();
                }
                // affiche le graphe avec le contour ad'hoc (avec ou sans commentaire)
                echo "<img src=\"/png_file/" . $this->nom_image . "\" border='0'>";

                if ($this->graph_contour != 0) {
                    $this->Graph_Affichage_Contour_Fin();
                }
            } else {
                $this->graph->Stroke();
            }
        } else {
            $this->error_image_display($this->display_erreur);
        }
    }
    // fonction qui permet m'export excel
    function Graph_Excel_Export()
    {
        global $tableau_abscisse_export_excel, $tableau_data_export_excel, $tableau_legend_export_excel;
        // element qui sert pour l'export Excel
        if (isset($this->abscisse_field_name)) {
            $this->legend_export_excel[] = ucfirst($this->abscisse_field_name);
        } else {
            $this->legend_export_excel[] = $this->nom_abscisse;
        }
        // sauveagarde des données pour l'export vers Excel avec un identifiant pour chaque graphe qui est le numéro du graphe accolé au numéro d'ordre
        $tableau_abscisse_export_excel[$this->graph_number] = $this->tableau_abscisse;
        $tableau_data_export_excel[$this->graph_number] = $this->tableau_data;
        $tableau_legend_export_excel[$this->graph_number] = $this->legend_export_excel;
        session_register("tableau_abscisse_export_excel");
        session_register("tableau_data_export_excel");
        session_register("tableau_legend_export_excel");
    }
    // fonction qui affiche le contour du haut du graphe et le titre
    function Graph_Affichage_Contour_Debut()
    {
        global $path_skin;
        global $couleur_fond_global;

        ?>
           <table border="0" cellspacing="0" cellpadding="0">
             <tr>
                 <td>
                     <table width="100%" border="0" cellspacing="0" cellpadding="0">
                       <tr>
                           <td width="28"><img src="<?="/" . $path_skin?>fenetre2_01.gif" width=28 height=22></td>
                           <td width="250" background="<?="/" . $path_skin?>fenetre2_02.gif"><b><font class="titre_profil">
                               <?=$this->graph_title?></font></b>
                               <?php
        if ($this->export_excel != 1) { // permet d'afficher ou non le lien vers Excel
            // si la valeur est différente de 1, on l'affiche
            $num_aleatoire = rand(0, 100000); //dans la target du href, cela permet d'ouvrir plusieurs feuilles excel en meme temps


            ?>&nbsp;<a href="/php/export_excel.php?identifiant=<?=$this->graph_number . $this->graph_order?>" target='fichier_excel<?=$num_aleatoire?>'><img align="absmiddle" src="/images/icones/excel.gif" border="0"></a><?php
        }

        ?>
                           </td>
                           <td>&nbsp;</td>
                       </tr>
                     </table>
                 </td>
             </tr>
             <tr>
                 <td bgcolor="<?=$couleur_fond_global?>" height="5"></td>
             </tr>
             <tr>
                 <td>
                     <table width="100%" border="0" cellspacing="0" cellpadding="0">
                       <tr>
                           <?php
        if ($this->field_graph_comment == "yes") { // teste si le grph doit avoir un commentaire

            ?>
								<td rowspan="2" bgcolor="<?=$couleur_fond_global?>" width="5"> </td> <?php
        } else { // graph sans contour

            ?>
							<td width="5" bgcolor="<?=$couleur_fond_global?>"> </td> <?php
        }

        ?>
					        <td bgcolor="<?=$couleur_fond_global?>" align="center" valign="center">
					        <?php
    }
    // fonction qui affiche le contour du bas du graphe et d'envtuels commentaires
    function Graph_Affichage_Contour_Fin()
    {
        global $path_skin;
        global $couleur_fond_page;
        global $couleur_fond_global;
        $identifiant = $this->graph_number;
        // collecte des données des commentaires des graphes
        if ($this->field_graph_comment == "yes") {
            // teste si un champ commentaire a été sélectioné
            // et s'il possède un contour. Sans contour, on n'a pas accès aux commentaires de toute façon
            $this->tab_commentaire = $this->Graph_Retrieve_Data_Comment();

            ?>
            				</td>
            				<td rowspan="2" bgcolor="<?=$couleur_fond_global?>" width="5">
							</td>
         				</tr>
         				<tr>
         					<td bgcolor="<?=$couleur_fond_global?>" align="center" valign="center">
             					<font class="tab_message_bas"><?=$this->graph_message?>
					             <?php
            if ($this->graph_message != "") {
                print '<br>';
            }
            if ($this->current_comment != "") {
                print $this->current_comment[0] . " : " . $this->current_comment[1];
            }

            ?>
             					</font>
         					</td>
        				</tr>
      				</table>
			    </td>
			  </tr>
			  <tr align="right">
			      <td>
			          <a href="#" onmouseout="kill()" onmouseover="pop('Comments History','<?=htmlspecialchars($this->tab_commentaire, ENT_QUOTES)?>','<?=$couleur_fond_page?>')" onClick="ouvrir_fenetre('/php/graph_table_comments_pop_up.php?type=graph&identifiant=<?=$this->graph_number?>&value_time_agregation=<?=$this->graph_abscisse_comparaison_field_name?>&value_network_agregation=<?=urlencode($this->network_agregation_comparaison_field_name)?>&time_agregation=<?=$this->time_agregation?>&network_agregation=<?=$this->network_agregation?>','comment','no','no',320,190);return false;">
			          <img src="<?="/" . $path_skin?>fenetre2_09.gif" width=37 height=21 border="0" alt="Modify Comments">
			      </td>
			  </tr>
			</table><?php
        } else {

            ?>
           		  </td>
         		  <td width="5" bgcolor="<?=$couleur_fond_global?>">
				  </td>
        	  </tr>
      		</table>
    	  </td>
  		</tr>
	  </table><?php
        }
    }
} //fin de la classe graph_complement

?>
