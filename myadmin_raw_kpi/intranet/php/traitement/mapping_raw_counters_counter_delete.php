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
/*
 * Efface un compteur de la table de correspondance
 * Ce compteur ne sera plus collecté quotidiennement de l'OMC
 */
session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
$id_field=$_GET["id_field"];
$id_group_table=$_GET["id_group_table"];

// recherche si la valeur new_field est à 1. Ce qui signifie que les scripts d'agregation ne l'ont pas encore pris en compte
// si new_field vaut 1 alors on delete le KPI sinon on met new field à 2 et le KPI sera effacé lors de l' aggregation
$query = "SELECT edw_group_table, new_field,edw_field_name FROM sys_field_reference  WHERE id_ligne='$id_field'";
$result = pg_query($database_connection, $query);
$row = pg_fetch_array($result, 0);
$new_field = $row["new_field"];
$edw_field_name = $row["edw_field_name"];
$edw_group_table = $row["edw_group_table"];

if ($new_field == 1) { // si new field=1, on efface directement le compteur ainsi que les autres compteus d'autres OMC qui portent le même nom
        $query = "DELETE FROM sys_field_reference WHERE id_ligne='$id_field'";
    pg_query($database_connection, $query);
} else { // mets à 2 le champ new_field pour tous les compteurs qui portent le nom du compteur qui doit être effacé
    $query = "UPDATE sys_field_reference SET new_field=2 WHERE id_ligne='$id_field'";
    pg_query($database_connection, $query);
}
//include_once($repertoire_physique_niveau0."scripts/edw_clean_structure_on_the_fly.php");
?>
<script>alert('The information has been deleted');
parent.provider.location="<?=$traitement_vers_affichage?>mapping_raw_counters_external.php?id_group_table=<?=$id_group_table?>";
window.location="<?=$traitement_vers_affichage?>mapping_raw_counters_correspondance_table_view_all.php?id_group_table=<?=$id_group_table?>";
parent.easyoptima_counter.location="<?=$traitement_vers_affichage?>mapping_raw_counters_internal.php?id_group_table=<?=$id_group_table?>";

</script>
