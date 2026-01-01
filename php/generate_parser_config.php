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
 * Generation d'un fichier de config pour le parser
 * Le scritp prend en entrée la liste des tables
 *      En sortie le script ecrit un fichier csv par table dont les colonnes sont :
 * colonne 1 : identifiant de ligne
 * colonne 2 : nom de la colonne
 * colonne 3 : valeur de la colonne
 * colonne 4 : type de la colonne
 * colonne 5 : nom de la table
 * colonne 6 : key si la colonne est la clé de la table
 * colonne 7 : na (ne sert pas pour l'instant)
 * colonne 8 : commentaire (ne sert pas pour l'instant)
 */
set_time_limit(3600);
include_once("environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
// ********** DEBUT DU SCRIPT *****************
$array_table_list[] = "sys_field_reference";
$array_table_keys["sys_field_reference"] = 'id_ligne';
$fp=fopen($repertoire_physique_niveau0."upload/parser_ini_file.txt","w+");
foreach ($array_table_list as $table_name) {
    $table_key = $array_table_keys[$table_name];
    $table_properties = pg_list_fields_and_properties($table_name);
    $table_field_name = $table_properties["fieldname"];
    $table_field_type = $table_properties["fieldtype"];
    $query = "SELECT " . implode(",", $table_field_name) . " FROM " . $table_name;
    $res = pg_query($database_connection, $query);
    $nombre_ligne = pg_num_rows($res);
    if ($nombre_ligne > 0) {
        for ($i = 0;$i < $nombre_ligne;$i++) {
            $row = pg_fetch_array($res,$i);
            foreach ($table_field_name as $indice => $field_name) {
                $copy_ligne = $i . ";" . $field_name . ";" . $row[$field_name] . ";" . $table_field_type[$indice] . ";" . $table_name;
                if ($field_name == $table_key) {
                    $copy_ligne .= ";key";
                } else {
                    $copy_ligne .= ";";
                }
                $copy_ligne .= ";;comment";
				fwrite($fp,$copy_ligne."\n");
            }
        }
	print $nombre_ligne." lignes générées pour la table ".$table_name."<br>";
	fclose($fp);
    } else {
        print "No data for table " . $table_name . "<br>";
    }
}

?>
