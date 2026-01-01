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
include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");

$date_du_jour = edw_day_format(0, "day");
// récupère le nombre de champ "Raw_data" affichés
$nombre_raw_data = $_POST["raw_data_counter"];
// récupère le numéro du group table
$id_group_table = $_POST["id_group_table"];
$group_table_name=get_group_table_name($id_group_table);
$flag_new_counter = 0; //permet de savoir si des nouveaux compteurs sont intégrés

// collecte toutes les informations pour chaque row_data
for ($i = 0;$i <= $nombre_raw_data;$i++) {
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
        // recherche si un compteur existe déjà avec le même nom mappé
        $query = "SELECT edw_field_name FROM sys_field_reference WHERE id_group_table='$id_group_table' and nms_field_name='$nms_field_name' and edw_field_name='$edw_field_name' and nms_table='$nms_table'";
        $resultat_recherche = pg_query($database_connection, $query); ;
        if (pg_num_rows($resultat_recherche) > 0) { // un résultat a été trouvé
            // on indique que le compteur a déjà été sélectionné sous le même nom
            $row = pg_fetch_array($resultat_recherche, 0);
            $nom_compteur_edw_trouve = $row["edw_field_name"];
            ?>
                 <script>
                  alert('The counter <?=$nms_field_name?> from the table <?=$nms_table?>\n has already been selected under the name <?=$nom_compteur_edw_trouve?>');
                 </script>
			<?
        } else {
            $flag_new_counter = 1;
            // insère le compteur selectionné
            $query = "INSERT INTO sys_field_reference (nms_provider, nms_id_connection, id_group_table, nms_table, nms_field_name, edw_group_table, edw_target_field_name,edw_field_name, edw_field_type, edw_agregation_function, edw_agregation_formula, new_date, new_field, on_off, aggregated_flag) VALUES ('inutile', 0, $id_group_table,'$nms_table', '$nms_field_name', '$group_table_name','$edw_target_field_name','$edw_field_name',  '$type_field', '$edw_agregation_function', '$edw_agregation_formula',$date_du_jour,1,1,'$edw_aggregated_flag')";
            pg_query($database_connection, $query);
        }
    }
}
// $query="UPDATE sys_field_reference set edw_target_field_name='column_'||id_ligne where on_off=1 and new_field=1";
// pg_query($database_connection,$query);
// include_once($repertoire_physique_niveau0 . "scripts/edw_clean_structure_on_the_fly.php");
// cas où des nouveaux compteurs sont ajoutés
if ($flag_new_counter == 1) {

    ?><script>alert('The counter(s) has/have been updated');</script><?php
} else {

    ?><script>alert('No counters have been updated');</script><?php
}

?>
<script>
window.location="<?=$traitement_vers_affichage?>mapping_raw_counters_correspondance_table.php?id_group_table=<?=$id_group_table?>";
parent.easyoptima_counter.location="<?=$traitement_vers_affichage?>mapping_raw_counters_internal.php?id_group_table=<?=$id_group_table?>";
parent.provider.location="<?=$traitement_vers_affichage?>mapping_raw_counters_external.php?id_group_table=<?=$id_group_table?>";
</script>
