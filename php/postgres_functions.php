<?php
/**
* 19-04-2007 GH : 
* 				Modification de la fonction list_fileds pour ne pas prendre en compte les colonnes du type ...pg_droppedxx
*/

function pg_query_to_php_html_array($pg_query,$select)        // returns the result of a query in a php array (or returns "false" if query fails)

{
     global $database_connection;

     //echo  "<br>dans".$pg_query;

        $pg_query_result=pg_query($database_connection,$pg_query);

        if($pg_query_result)
          {
            $nombre = pg_num_rows($pg_query_result);
            for ($i = 0;$i <$nombre;$i++)
                {
                 $row = pg_fetch_array($pg_query_result, $i);
                 for ($j=0;$j < count($select);$j++)
                      {

                       $agregation_value[$i][$j]=$row["$select[$j]"];
                      }
                }

             echo "<table>";
             echo "<tr>";
                  for ($k=0;$k<count($select);$k++)
                     {
                      echo "<td bgcolor='#B4B4B4'><font class=texteGrisBold> $select[$k] </font></td>";
                     }
                 echo "</tr>";
            for ($j=0;$j<count($agregation_value);$j++)
                {

                 echo "<tr>";
                 for ($k=0;$k<count($agregation_value[$j]);$k++)
                     {
                      $ag=$agregation_value[$j][$k];
                      echo "<td class='texteGrisPetit'> $ag </td>";
                     }
                 echo "</tr>";
               }
             echo "</table>";



        }else{

                return false;

        }

}

// fcontion qui retroune les éléments de $column_list qui sont des colonnes de $table
function check_table_in_database($table)
{
     global $database_connection;
    $query = "SELECT tablename FROM pg_tables WHERE schemaname='public' and tablename='$table'";
    $pg_check_table = pg_query($database_connection,$query);
    if (pg_num_rows($pg_check_table) > 0) {
        return true;
    } else {
        return false;
    } 
} 


// returns an array with the names of tables found in the database
function pg_list_tables($database_connection = '')
{
    $query = "SELECT tablename FROM pg_tables WHERE schemaname='public' ORDER BY tablename";
    $tables = array();
    $pg_tables = pg_query($query);
    while ($row = pg_fetch_array($pg_tables)) {
        $tables[] = $row["tablename"];
    }
    return $tables;
}
// returns an array with the names of tables found in the database
function pg_list_tables_with_condition($database_connection = '', $condition)
{
    $query = "SELECT tablename FROM pg_tables WHERE schemaname='public' $condition ORDER BY tablename";
    $tables = array();
    $pg_tables = pg_query($query);
    while ($row = pg_fetch_array($pg_tables)) {
        $tables[] = $row["tablename"];
    }
    return $tables;
}
// fcontion qui retroune les éléments de $column_list qui sont des colonnes de $table
function check_columns_in_table($column_list, $table)
{
    $list_columns_in_table = list_fields($table);
    $array_result = array_intersect($column_list, $list_columns_in_table);

    return $array_result;
}
// retourne la liste des champs d'une table
function list_fields($table)
{
    $query = "SELECT a.attname FROM pg_class c, pg_attribute a WHERE c.relname = '" . $table . "' AND a.attnum > 0 AND a.attrelid = c.oid AND a.attname NOT LIKE '..%' ORDER BY a.attnum;";
    $pg_result = pg_query($query);
    $array_result = array();
    while ($row = pg_fetch_array($pg_result)) {
        $array_result[] = $row["attname"];
    }
    return $array_result;
}
// recupere les informations des champs d une table
function pg_list_fields_and_properties($table)
{
    $query = "
                SELECT
                a.attname    AS fieldname,
                a.attnum     AS fieldnumber,
                t.typname    AS fieldtype,
                a.attlen     AS fieldlength,
                a.atttypmod  AS length_var,
                a.attnotnull AS not_null,
                a.atthasdef  AS has_default
                FROM
                pg_class c,
                pg_attribute a,
                pg_type t
                WHERE c.relname = '" . $table . "'
                AND       a.attnum > 0
                AND       a.attrelid = c.oid
                AND       a.atttypid = t.oid
                ORDER BY a.attnum;
        ";
    $pg_result = pg_query($query);
    $array_result = array();
    while ($row = pg_fetch_array($pg_result)) { // le resultat est un tableau indice par les noms des informations recuperees
        $array_result["fieldname"][] = $row["fieldname"];
        $array_result["fieldnumber"][] = $row["fieldnumber"];
        $array_result["fieldtype"][] = $row["fieldtype"];
        $array_result["fieldlength"][] = $row["fieldlength"];
        $array_result["length_var"][] = $row["length_var"];
        $array_result["not_null"][] = $row["not_null"];
        $array_result["has_default"][] = $row["has_default"];
    }

    return $array_result;
}
// returns the result of a query in a php array (or returns "false" if query fails)
function pg_query_to_php_array($pg_query)
{
    $pg_query_result = pg_query($pg_query);
    if ($pg_query_result) {
        $php_array = array();
        while ($row = pg_fetch_array($pg_query_result)) {
            $php_array[] = $row;
        }
        return $php_array;
    } else {
        return false;
    }
}
// returns a column of an array of arrays
function array_column($array, $column_key)
{
    $array_column = array();

    for($i = 0;$i < count($array);$i++) {
        $array_column[$i] = $array[$i][$column_key];
    }

    return $array_column;
}
/**
 *  Efface une table si elle existe
 * @param string $table_name : nom de la table
 */
// 12/06/2012 NSE bz 27597 : réécriture pour utiliser getConnection
function postgres_drop_table($table_name)
{
    $db = Database::getConnection(ProductModel::getProductId());
    $query = "DROP TABLE IF EXISTS {$table_name}";
    $db->execute($query) ;
    if ($db->getLastError() != '') {
        echo pg_last_error() . " " . $query . ";\n<br>";
    }
}

?>
