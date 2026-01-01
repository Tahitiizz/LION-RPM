<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version iub_1.1.0b
*/
?>
<?
/*
* 04-07-2006 : suppression du LOCK TABLE et du BEGIN COMMIT
*
*
*
*/
/**
 * pour le lancement des scripts insérés dans la sys_crontab
*/

set_time_limit(45000);
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once("$repertoire_physique_niveau0"."php/database_connection.php");
include_once("$repertoire_physique_niveau0"."php/edw_function.php");

//recherche le module pour aiguiller sur le bon parser



// demarrage de la transation
//pg_query($database_connection,"BEGIN");
// on verrouille la table
//pg_query($database_connection,"LOCK TABLE sys_crontab");

// recherche de scripts a executer
$query="select oid,* from sys_crontab order by oid asc LIMIT 1";
//echo"$query\n";
$resultat=pg_query($database_connection,$query);

// si il y en a un
if (pg_num_rows($resultat) == 1)
  {
    $row=pg_fetch_array($resultat,0);
    $oid=$row["oid"];
    $script=$row["script"];

    //atttentio pour la mise a jour des step dans step_track car le nom et chemin du script dans sys_definition_step ne sont pas evalue, présence de $module
    $script_nom_dans_table_sys_definition_step=$script;
    //evaluation du script pour activer les variables dans le chemin
        $module=get_sys_global_parameters("module"); //la variable $module peut-être utilisée dans les chemins pour lancer les scripts
    eval( "\$script = \"$script\";" );

    $famille=$row["famille"];
    $master=$row["master"];
    $group_table_param=$row["param1"];
    $param2=$row["param2"];
    $param3=$row["param3"];
    $param4=$row["param4"];
    $param5=$row["param5"];
  //  echo"$oid-$script\n";

    // on s'en occupe: on delete l'entree
    $query="delete from sys_crontab  where oid=$oid";
    pg_query($database_connection,$query);
    // on valide la transation ce qui libelre le verrou
    //pg_query($database_connection,"COMMIT");
    // lancement du script
    $result_demon_php = " du script '$script' ($famille,$master,$param1,$param2,$param3,$param4,$param5) oid = $oid";
  //  echo "Start" . $result_demon_php . "\n";
    $pre_exec_microtime = getmicrotime();
    if (file_exists($repertoire_physique_niveau0.$script))
    { // test d'existance du script

		echo "<font color=blue ><b>";

		echo "<li>Début du script ".$repertoire_physique_niveau0."$script Time stamp : " . date('r') . "</font></b><br>";

		include($repertoire_physique_niveau0."$script");

        update_sys_step_track($script_nom_dans_table_sys_definition_step,$famille,$master);
        update_log(basename($script),get_name($famille,"famille"),get_name($master,"master"));
	}
	else
	{
		echo "<li><font color='#DD4400'><b>Warning!!! fichier ".$repertoire_physique_niveau0."$script NON TROUVE Time stamp : " . date('r') . "</i></b></font><br>";
	}
    $post_exec_microtime = getmicrotime();
  //  echo "Fin" . $result_demon_php . " In " . ($post_exec_microtime - $pre_exec_microtime) . " seconds\n";
  }
 else
   {
     //pg_query($database_connection,"COMMIT");
   }

function get_name($id,$type)
{
  global $database_connection;
  switch($type)
    {
    case "master":
      $query="select master_name from sys_definition_master where master_id='$id'";
      break;
    case "famille":
      $query="select family_name from sys_definition_family where family_id='$id'";
      break;
    }
  $res=pg_query($database_connection,$query);
  while($row=pg_fetch_array($res))
    $name=$row[0];
  return $name;
}

function update_log($script,$famille,$master)
{
  global $database_connection;
  //update de la table sys_process_log
  $now=date('YmdHi',time());
  $query = "INSERT INTO sys_process_log (type, master, family, script, date, encours,done)
            VALUES ('step','$master','$famille','$script','$now','false','true')";
  pg_query($database_connection, $query);
  if (pg_last_error() != '') {echo pg_last_error() . " " . $query . "<BR>\n";}
}

function update_sys_step_track($script_nom_dans_table_sys_definition_step,$famille,$master)
{
  global $database_connection;
  // 08/06/2011 BBX -PARTITIONING-
  // Correction des casts
  $query="update sys_step_track set encours='false', done='true' where
          master_id='$master' and family_id='$famille'
          and step_id in (select step_id::text from sys_definition_step where script='$script_nom_dans_table_sys_definition_step')";
 echo "<font color='#009900'><li><b>Fin du script <i>$script_nom_dans_table_sys_definition_step </i> Time stamp : " . date('r') . "</b></font><br>";
  pg_query($database_connection,$query);
}

?>
