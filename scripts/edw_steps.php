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
*	Parser version gb_1.0.0b
*/
?>
<?php
/*
*
* 28-06-2006 : suppression du sleep(10) qui ralentissait l'execution des process et l'enchainement des steps
*
*
*
*/
/**
 * c'est dans ce fichier que les steps sont gérés
 * @package master
 * @author Cyrille Gourvès
 */

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once("$repertoire_physique_niveau0"."php/database_connection.php");

/**
 * retourne le tableau des masters dont au moins un step n'est pas en cours
 * @return array tableau de masters
 */
function get_masters()
{
  global $database_connection;
  $query="select distinct master_id from sys_step_track where encours='false'";
  $res=pg_query($database_connection,$query) or die(pg_last_error());
  while($row=pg_fetch_array($res))
    $masters[]=$row[0];
  return $masters;
}

/**
 * retourne le tableau d'ids de familles correspondants au master passé en paramètre
 * @param string $master @return array tableau d'id de familles
 * @return array tableau d'ids de familles
 */
function get_families($master)
{
  global $database_connection;
  $requete="select distinct family_id from sys_step_track where master_id='$master'";
  $res=pg_query($database_connection, $requete)
    or die('erreur sur '. $requete .pg_last_error());
  while($row=pg_fetch_array($res))
    $dist_fam_ids[]=$row[0];
  return $dist_fam_ids;
}

/**
 * retourne le tableau des noms de scripts correspondant au tableau d'ids passé en paramètre
 * @param array $id
 * @return array
 */
function get_script($id)
{
  global $database_connection;
  $query="select script from sys_definition_step where step_id='$id'";
  $res=pg_query($database_connection,$query);
  while($row=pg_fetch_array($res))
    $scripts=$row[0];
  return $scripts;
}

function select_group_table($fam_id)
{
  global  $database_connection;
  $query="select id_group_table from sys_definition_family where family_id='$fam_id'";
  $res=pg_query($database_connection,$query);
  while($row=pg_fetch_array($res))
    $group=$row[0];
  return $group;
}

/**
 * insère le nom du script en dur (/path/to/script) dans la table sys_crontab
 * @param string $script
 * @param string $fam_id
 * @param string $master
 */
function ins_script_crontab($script,$fam_id,$master,$group_table)
{
  global $database_connection;
  $query="insert into sys_crontab(script,famille,master,param1)
          values ('$script','$fam_id','$master','$group_table')";
  pg_query($database_connection,$query);
}

/**
 * met à jour la table sys_step_track, met encours='true' pour le step $step de la famille $famille du master $master
 * @param string $step
 * @param string $famille
 * @param string $master
 */
function update_step_encours($step,$famille,$master)
{
  global $database_connection;
  $query="update sys_step_track set encours='true', done='false'
          where family_id='$famille' and master_id='$master' and step_id='$step'";
  pg_query($database_connection,$query);
}

/**
 * retourne le tableau des steps non traités pour la famille $famille du master $master
 * @param string $famille
 * @param string $master
 * @return array
 */
function get_steps_left($master,$famille)
{
  global $database_connection;
  $query="select step_id from sys_step_track where encours='false' and done='false'
          and master_id='$master' and family_id='$famille' order by step_order";
  $res=pg_query($database_connection,$query);
  while($row=pg_fetch_array($res))
    $ids[]=$row[0];
  return $ids;
}

/**
 * retourne un tableau à 2 dimensions indice => step_id, step_type
 * @param array $ids
 * @return 2-dimensional-array
 */
function get_infos_int_ext($ids)
{
  global $database_connection;
  for($i=0;$i<count($ids);$i++)
    {
      $query="select step_id, step_type from sys_definition_step
              where step_id='$ids[$i]'";
      $res=pg_query($database_connection,$query);
      while($row=pg_fetch_array($res))
        $infos[$i]=array("$row[0]","$row[1]");
    }
  return $infos;
}

/**
 * retourne l'ordre du step $step de la famille $famille du master $master
 * @param string $step
 * @param string $famille
 * @param string $master
 * @return int
 */
function get_ordre($step,$famille,$master)
{
  global $database_connection;
  $query="select step_order from sys_step_track
          where step_id='$step' and family_id='$famille' and master_id='$master'";
  $res=pg_query($database_connection,$query);
  $row=pg_fetch_array($res);
  $ordre=$row[0];
  return $ordre;
}


/**
 * vérifie que tous les steps de la famille $famille du master $master dont l'ordre est < à $ordre sont terminés
 * @param string $famille
 * @param string $master
 * @param int $ordre
 * @return bool
 */
function check_finished_s($famille, $master, $ordre)
{
  global $database_connection;
  $query="select step_id from sys_step_track
          where encours='true' and master_id='$master' and family_id='$famille'
          and step_order < '$ordre'  ";
  $res=pg_query($database_connection,$query);
  if(pg_num_rows($res)>0)
    return false;
  else return true;
}


function update_log($type,$master, $family, $script)
{
  global $database_connection;
  //update de la table sys_process_log
  $now=date('YmdHi',time());
  $query = "INSERT INTO sys_process_log (type, master, family, script, date, encours,done)
            VALUES ('step','$master','$family','$script','$now','true','false')";
  pg_query($database_connection, $query);
  if (pg_last_error() != '')
    echo pg_last_error() . " " . $query . "<BR>\n";
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

$masters=get_masters();

for($m=0;$m<count($masters);$m++)
  {
    $fam_ids= get_families($masters[$m]);
    for($f=0;$f<count($fam_ids);$f++)
      {
        // on ne garde que les steps non traités (cad encours=false et done = false)
        $ids_steps_left=get_steps_left($masters[$m],$fam_ids[$f]);
        // on récupère les infos (internal ou external) pour les familles non traitées
        $infos_int_ext=get_infos_int_ext($ids_steps_left);

        $group_table=select_group_table($fam_ids[$f]);

        echo "scripts à exécuter pour le master $masters[$m] pour la famille $fam_ids[$f] <br>";
        for($i=0;$i<count($infos_int_ext);$i++)
          echo $infos_int_ext[$i][0]." => ".$infos_int_ext[$i][1]."\t";
        echo "<br>";
        switch($infos_int_ext[0][1])
          {
            //$infos_int_ext[0][0] = id de step
            //$infos_int_ext[0][1] = type de step

          case "internal":
            echo "le script est interne<br>";
            //on récupère l'ordre du step
            $ordre=get_ordre($infos_int_ext[0][0],$fam_ids[$f],$masters[$m]);
            //on regarde si les steps précédents sont terminés
            if(check_finished_s($fam_ids[$f],$masters[$m],$ordre))
              {
                echo "je mets le script dans la sys_crontabi<br>";
                $script=get_script($infos_int_ext[0][0]);
                ins_script_crontab($script,$fam_ids[$f],$masters[$m],$group_table);
                update_step_encours($infos_int_ext[0][0],$fam_ids[$f],$masters[$m]);
                $master_name=get_name($masters[$m],"master");
                $family_name=get_name($fam_ids[$f],"famille");
                $script_name=basename($script);
                update_log("step",$master_name,$family_name,$script_name);
              }
            else
              echo " j'attends<br>";
            break;

          case "external":
            $ordre2=get_ordre($infos_int_ext[0][0],$fam_ids[$f],$masters[$m]);
            if(check_finished_s($fam_ids[$f],$masters[$m],$ordre2))
              {
                $sc=0;
                while($infos_int_ext[$sc][1]=="external")
                  {
                    echo "le script est externe,je le mets dans la sys_crontab<br>";
                    $script=get_script($infos_int_ext[$sc][0]);
                    ins_script_crontab($script,$fam_ids[$f],$masters[$m],$group_table);
                    update_step_encours($infos_int_ext[$sc][0],$fam_ids[$f],$masters[$m]);
                    $master_name=get_name($masters[$m],"master");
                    $family_name=get_name($fam_ids[$f],"famille");
                    $script_name=basename($script);
                    update_log("step",$master_name,$family_name,$script_name);
                    $sc++;
                  }
              }
            break;
          }
      }
  }
?>
