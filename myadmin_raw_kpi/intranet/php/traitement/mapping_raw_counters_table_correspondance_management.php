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
/**
 * Gère la correspondance entre les compteurs issus de l'OMC et les compteurs
 * Easyoptima.
 * Les compteurs selectionnés sont sauvegardés
 */
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");

$date_du_jour = edw_day_format(0, "day");
// récupère le nombre de champ "Row_data" affichés
$nombre_row_data = $_POST["row_data_counter"];
// récupère le numéro de la connection
$omc_connection = $_POST["omc_connection"];
// récupère le numéro du data_type
$id_data_type = $_POST["id_data_type"];
// récupère l'identifiant de connection pour la copie d'un OMC vers un autre
// on recupere l'identifiant de la connection source aui va etre copiee sur la cible
$omc_connection_source = $_POST["copie_omc"];

$flag_new_counter = 0; //permet de savoir si des nouveaux compteurs sont intégrés

// colecte des données concernant la connection à l'OMC
$query = "SELECT connection_name, flat_file, daily_table_name FROM $nom_table_ref_connection_to_server where (id_connection='$omc_connection')";
$resultat_recherche = pg_query($database_connection, $query);
$row = pg_fetch_array($resultat_recherche, 0);
$connection_name = $row["connection_name"];
$daily_table_name = $row["daily_table_name"];
$flat_file = $row["flat_file"];

if ($omc_connection_source != -1) { // on est dans le cas d'une copie d'un OMC vers un autre
        // efface tout le mapping de lOMC cible
        $query = "DELETE FROM sys_field_reference where nms_id_connection=$omc_connection";
    pg_query($database_connection, $query);
    // selectionne tout le mapping de l'OMC source
    $query = "SELECT nms_table, nms_field_name, id_data_type, edw_group_table, edw_field_name, edw_field_type, edw_agregation_function, edw_agregation_formula, new_field, on_off  FROM sys_field_reference where (nms_id_connection='$omc_connection_source')";
    $resultat_recherche = pg_query($database_connection, $query);
    $nombre_resultat_recherche = pg_num_rows($resultat_recherche);
    $today = date("Ymd");
    for ($i = 0;$i < $nombre_resultat_recherche;$i++) {
        $row = pg_fetch_array($resultat_recherche, $i);
        $query_insert = "INSERT INTO sys_field_reference (nms_provider, nms_id_connection, id_data_type, nms_table, nms_field_name, edw_group_table, edw_field_name, edw_field_type,  edw_agregation_function, edw_agregation_formula, new_date, new_field, on_off) VALUES ('$connection_name',$omc_connection," . $row['id_data_type'] . ",'" . $row['nms_table'] . "','" . $row['nms_field_name'] . "','" . $daily_table_name . "_" . $row['id_data_type'] . "','" . $row['edw_field_name'] . "'," . $row['edw_field_type'] . "'," . $row['edw_agregation_function'] . "','" . $row['edw_agregation_formula'] . "'," . $today . "," . $row['new_field'] . "," . $row['on_off'] . ")";
        if (pg_query($database_connection, $query_insert)) {
            $flag_new_counter = 1;
        }
    }
} else {
    // collecte toutes les informations pour chaque row_data
    for ($i = 0;$i <= $nombre_row_data;$i++) {
        $nms_table = $_POST["omc_table_$i"];
        $nms_field_name = $_POST["omc_data_$i"];
        $edw_field_name = str_replace("(", " ", $_POST["easyoptima_data_$i"]);
        $edw_field_name = str_replace(")", " ", $edw_field_name);
        $edw_field_name = str_replace(" ", "_", trim($edw_field_name));
        $edw_target_field_name = strtolower($edw_field_name);
        $edw_agregation_function = $_POST["type_formule_$i"];
        $edw_aggregated_flag = $_POST["aggregated_$i"]; //chechbox qui indique si les compteurs seront aggregés ou non
        if ($edw_aggregated_flag == 'on') {
            $edw_aggregated_flag = 1;
        } else {
            $edw_aggregated_flag = 0;
        }

        if ($edw_agregation_function != "NONE") {
            $edw_agregation_formula = $edw_agregation_function . '(' . $edw_target_field_name . ')';
            $type_field = "float4";
        } else {
            $edw_agregation_formula = $edw_target_field_name;
            $edw_agregation_function = "";
            $type_field = "text";
        }

        if ($nms_field_name != "" && $edw_field_name != "") { // teste si les 2 valeurs sont non vides
                // recherche si un compteur easyoptima existe déjà pour le compteur OMC sélectionné
                // le but est d'éviter d'avoir plusieurs fois les mêmes compteurs OMC
                // on indique que le compteur a déjà été sélectionné sous le nom xxxx
                $query = "SELECT edw_field_name FROM sys_field_reference where (nms_id_connection='$omc_connection' and id_data_type=$id_data_type and nms_table='$nms_table' and nms_field_name='$nms_field_name')";
            $resultat_recherche = pg_query($database_connection, $query); ;
            if (pg_num_rows($resultat_recherche) > 0) { // un résultat a été trouvé
                    // on indique que le compteur a déjà été sélectionné sous le nom xxxx
                    $row = pg_fetch_array($resultat_recherche, 0);
                $nom_compteur_edw_trouve = $row["edw_field_name"];

                ?>
                 <script>
                  alert('The counter <?=$nms_field_name?> from the table <?=$nms_table?>\n has already been selected.\nThe easyoptima name is <?=$nom_compteur_edw_trouve?>');
                 </script><?php
            } else {
                $flag_new_counter = 1;
                // insère le compteur selectionné
                $edw_group_table = $daily_table_name . "_" . $id_data_type;
                $query = "INSERT INTO sys_field_reference (nms_provider, nms_id_connection, id_data_type, nms_table, nms_field_name, edw_group_table, edw_target_field_name,edw_field_name, edw_field_type, edw_agregation_function, edw_agregation_formula, new_date, new_field, on_off, aggregated_flag) VALUES ('$connection_name', $omc_connection, $id_data_type,'$nms_table', '$nms_field_name', '$edw_group_table','$edw_target_field_name','$edw_field_name',  '$type_field', '$edw_agregation_function', '$edw_agregation_formula',$date_du_jour,1,1,'$edw_aggregated_flag')";
                pg_query($database_connection, $query);
            }
        }
    }
    // $query="UPDATE sys_field_reference set edw_target_field_name='column_'||id_ligne where on_off=1 and new_field=1";
    // pg_query($database_connection,$query);
}
//include_once($repertoire_physique_niveau0 . "scripts/edw_clean_structure_on_the_fly.php");
// cas où des nouveaux compteurs sont ajoutés
if ($flag_new_counter == 1) {

    ?><script>alert('The counter(s) has/have been updated');</script><?php
} else {

    ?><script>alert('No counters have been updated');</script><?php
}

?>
<script>
window.location="<?=$traitement_vers_affichage?>intra_myadmin_nms_counter_correspondance_table.php?id_connection=<?=$omc_connection?>&id_data_type=<?=$id_data_type?>";
parent.easyoptima_counter.location="<?=$traitement_vers_affichage?>intra_myadmin_nms_counter_easyoptima.php?id_connection=<?=$omc_connection?>&id_data_type=<?=$id_data_type?>";
parent.provider.location="<?=$traitement_vers_affichage?>intra_myadmin_nms_counter_omc_provider.php?id_connection=<?=$omc_connection?>&id_data_type=<?=$id_data_type?>";
</script>
