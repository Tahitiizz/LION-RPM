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
* 04/11/2005 : creation function get_counters_without_aggregated_function qui permet d'avoir une liste à utiliser dans les group by par exemple
*
*
*
* /

/**
 * retourne l'id du groupe dont le nom est $name
 * @param string $name
 * @return int
 */
function get_group_id($name)
{
    global $database_connection;
    $query = "select id_ligne from sys_definition_group_table
		where edw_group_table='$name'";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $group = $row[0];
    return $group;
}

/**
 * retourne la liste des compteurs actifs pour un group table
 *
 * @param text $group_table_name
 * @return array array_list
 */
function get_raw_counters($group_table_name)
{
    global $database_connection;

    $query = "SELECT distinct edw_target_field_name FROM  sys_field_reference WHERE edw_group_table='$group_table_name' and new_field!=1 and on_off=1";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $array_list[] = $row[0];

    return $array_list;
}

/**
 * retourne la liste des compteurs actifs pour un group table
 *
 * @param text $group_table_name
 * @return array array_list
 */
function get_raw_counters_aggregated($group_table_name, $aggregated)
{
    global $database_connection;

    $query = "SELECT distinct edw_target_field_name FROM sys_field_reference WHERE edw_group_table='$group_table_name' and new_field!=1 and on_off=1 and aggregated_flag='$aggregated'";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $array_list[] = $row[0];

    return $array_list;
}

/**
 * retourne les fonctions d'aggregation des compteurs ont une fonction d'aggregation
 *
 * @param text $id_group_table_name
 * @return array array_list
 */

function get_counters_with_aggregated_function($id_group_table)
{
    global $database_connection;

    $query = "SELECT distinct edw_target_field_name,edw_agregation_formula FROM  sys_field_reference WHERE edw_group_table=(SELECT edw_group_table FROM sys_definition_group_table WHERE id_ligne=$id_group_table) and new_field!=1 and on_off=1 and edw_agregation_function IS NOT NULL";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res)) {
        $array_list[$row["edw_target_field_name"]] = $row["edw_agregation_formula"];
    }
    return $array_list;
}



/**
 * retourne les compteurs qui n'ont pas de fonction d'aggregation et qui potentiellement doivent être utilisé dans les group by
 *
 * @param text $id_group_table_name
 * @return array array_list
 */

function get_counters_without_aggregated_function($id_group_table)
{
    global $database_connection;

    $query = "SELECT distinct edw_target_field_name FROM  sys_field_reference WHERE edw_group_table=(SELECT edw_group_table FROM sys_definition_group_table WHERE id_ligne=$id_group_table) and new_field!=1 and on_off=1 and edw_agregation_function IS NULL";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res)) {
        $array_list[] = $row["edw_target_field_name"];
    }
    return $array_list;
}

/**
 * retourne les informations pour les compteurs actifs d'un group table
 *
 * @param text $id_group_table_name
 * @return array array_list
 */
function get_raw_counters_all_information($id_group_table)
{
    global $database_connection;

    $query = "SELECT distinct edw_target_field_name,edw_field_type,edw_agregation_formula,default_value FROM  sys_field_reference WHERE edw_group_table=(SELECT edw_group_table FROM sys_definition_group_table WHERE id_ligne=$id_group_table) and new_field!=1 and on_off=1";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res)) {
        $array_list["edw_target_field"][] = $row["edw_target_field_name"];
        $array_list["edw_field_type"][] = $row["edw_field_type"];
        $array_list["edw_agregation_field"][] = $row["edw_agregation_formula"];
        $array_list["edw_agregation_field_with_index"][$row["edw_target_field_name"]] = $row["edw_agregation_formula"];
        $array_list["default_value"][$row["edw_target_field_name"]] = $row["default_value"];
    }
    return $array_list;
}

/**
 * retourne l'identifiant du group table à partir du label du group table.
 * Il ne doit y avaoir qu'un seul group table en résultat sinon il y a un probleme
 *
 * @param text $group_table_name
 * @return int id_group_table
 */
function get_id_group_table($group_table_name)
{
    global $database_connection;

    $query = "select id_ligne FROM  sys_definition_group_table WHERE edw_group_table='$group_table_name'";
    $res = pg_query($database_connection, $query);
    $row = pg_fetch_array($res, 0);
    $id_group_table = $row["id_ligne"];

    return $id_group_table;
}

/**
 * retourne le label du group table à partir de l'identifiant du group table
 * Il ne doit y avaoir qu'un seul group table en résultat sinon il y a un probleme
 *
 * @param int $id_group_table int
 * @global $database_connection
 */
function get_group_table_name($id_group_table)
{
    global $database_connection;

    $query = "select edw_group_table FROM sys_definition_group_table WHERE id_ligne='$id_group_table'";
    $res = pg_query($database_connection, $query);
    $row = pg_fetch_array($res, 0);
    $group_table_name = $row["edw_group_table"];

    return $group_table_name;
}

/**
 * retourne pour l'affcihage les niveaux de time pour le group et le type passés en paramètres.
 * On ne retourne que les niveaux qui ont le flag 'visible' à 1
 * si $op=1, retourne les niveaux à déployer.
 * si $op=-1, retourne tous les niveaux déployés
 *
 * @param int $group_id
 * @param string $data_type
 * @param bool $op
 * @return array
 */
function select_time_fields_display($group_id, $data_type, $op)
{
    global $database_connection;

    $query = "select t0.time_agregation from sys_definition_group_table_time t0, sys_definition_time_agregation t1
            where data_type='$data_type'
            and id_group_table='$group_id' and t1.agregation=t0.time_agregation and t1.visible=1 and t1.on_off=1 ";
    if ($op != "-1")
        $query .= " and deploy_status='$op'";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res)) {
        $fields[] = $row[0];
    }
    return $fields;
}

/**
 * retourne les niveaux de time pour le group et le type passés en paramètres.
 * si $op=1, retourne les niveaux à déployer.
 * si $op=-1, retourne tous les niveaux déployés
 *
 * @param int $group_id
 * @param string $data_type
 * @param bool $op
 * @return array
 */
function select_time_fields($group_id, $data_type, $op)
{
    global $database_connection;

    $query = "select time_agregation from sys_definition_group_table_time
            where data_type='$data_type'
            and id_group_table='$group_id' and on_off=1 and time_agregation IN (SELECT agregation FROM sys_definition_time_agregation WHERE primaire=1)";
    if ($op != "-1")
        $query .= " and deploy_status='$op'";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res)) {
        $fields[] = $row[0];
    }
    return $fields;
}

/**
 * retourne les niveaux de network pour la famille passée en paramètres.
 *
 * @param text $family
 * @return array
 */
function select_net_fields_by_family($family)
{
    global $database_connection;

    $query = "select agregation_label, agregation from sys_definition_network_agregation where family='$family' order by agregation_rank asc";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $nets[$row["agregation"]] = $row["agregation_label"];
    return $nets;
}

/**
 * retourne les niveaux de network pour le group et le type passés en paramètres.
 * si $op=1, retourne les niveaux à déployer.
 * si $op=-1, retourne tous les niveaux déployés
 *
 * @param int $group_id
 * @param string $data_type
 * @param bool $op
 * @return array
 */
function select_net_fields($group_id, $data_type, $op)
{
    global $database_connection;

    $query = "select network_agregation from sys_definition_group_table_network
            where data_type='$data_type'
            and id_group_table='$group_id'";
    if ($op != "-1")
        $query .= " and deploy_status='$op'";
    $query .= " order by rank";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $nets[] = $row[0];
    return $nets;
}

function get_min_network_level($group_id, $data_type)
{
    global $database_connection;

    $query = "select network_agregation
          from sys_definition_group_table_network
          where id_group_table='$group_id'
          and data_type='$data_type' order by rank limit 1";
    // echo $query."<br>";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $level = $row[0];
    return $level;
}

function get_min_time_level($group_id, $data_type)
{
    global $database_connection;

    $query = "select time_agregation from sys_definition_group_table_time
              where id_group_table='$group_id' and data_type='$data_type'
              and id_source in
              (select min(id_source) from sys_definition_group_table_time
              where id_group_table='$group_id'
              and data_type='$data_type')";
    $res = pg_query($database_connection, $query);
    // echo $query."<br>";
    while ($row = pg_fetch_array($res))
    $level = $row[0];
    return $level;
}

function get_offset_day()
{
    global $database_connection;
    $query = "select value from sys_global_parameters where parameters='offset_day'";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $offset = $row[0];
    return $offset;
}

function get_min_net($group_table_param, $type)
{
    global $database_connection;
    $query = "select network_agregation
          from sys_definition_group_table_network
          where id_group_table='$group_table_param'
          and data_type='$type' order by rank limit 1";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $level = $row[0];
    return $level;
}

function get_group_name($id)
{
    global $database_connection;
    $query = "select edw_group_table from sys_definition_group_table
          where id_ligne='$id'";
    $res = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($res))
    $group = $row[0];
    return $group;
}

function get_min_time($group_table_param, $type)
{
    global $database_connection;
    $query = "select time_agregation
          from sys_definition_group_table_time
          where data_type='$type' and id_group_table='$group_table_param'
          order by id_source limit 1";
    $res = pg_query($database_connection, $query);
    $row = pg_fetch_row($res);
    return $row[0];
}

function get_table_temp_name($group_id)
{
    global $database_connection;
    // recupere le niveau network minimum
    $net_min = get_min_network_level($group_id, "raw");
    // recupere le niveau time minimum
    $time_min = get_min_time_level($group_id, "raw");
    $group = get_group_table_name($group_id);
    $table = $group . "_raw_" . $net_min . "_" . $time_min;

    $table_temp = $table . '_temp';

    return $table_temp;
}

?>
