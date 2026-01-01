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
* 28-06-2006 : suppression du sleep(5) qui ralentissait l'enchainement des steps et donc le process
*
*
*/
/**
 * c'est dans ce fichier que les familles sont gérées
 * @package master
 * @author Cyrille Gourvès
 */
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once("$repertoire_physique_niveau0"."php/database_connection.php");

function get_masters($encours,$done)
// si (false, false) => récupère les noms de masters qui ont des familles
// qui n'ont pas encore été traitées
// sinon récupère tous les masters
{
  global $database_connection;
  if ($encours!="" && $done !="")
    $query="select distinct master_id from sys_family_track
            where encours='$encours' and done='$done'";
  else
    $query="select distinct master_id from sys_family_track";
  $res=pg_query($database_connection,$query) or die(pg_last_error());
  while($row=pg_fetch_array($res))
    $masters[]=$row[0];
  return $masters;
}


function get_infos_int_ext($ids)
// retourne un tableau à 2 dimensions indice => family_id, family_type
{
  global $database_connection;
  for($i=0;$i<count($ids);$i++)
    {
      $query="select family_id, family_type from sys_definition_family
              where family_id='$ids[$i]'";
      $res=pg_query($database_connection,$query);
      while($row=pg_fetch_array($res))
        $infos[$i]=array("$row[0]","$row[1]");
    }
  return $infos;
}



function get_steps_for_family($id)
// récupère les scripts à exécuter pour une famille donnée
{
  global $database_connection;
  $query="select step_id from sys_definition_step
          where family_id='$id' and on_off='1' order by ordre ";
  $res=pg_query($database_connection,$query) or die(pg_last_error());
  while($row=pg_fetch_array($res))
    $steps[]=$row[0];
  return $steps;
}


function ins_step_ids($ids_steps,$id_family,$master)
// insère les steps d'une famille donnée dans sys_step_track
{
  global $database_connection;
  for($s=0;$s<count($ids_steps);$s++)
    {
      $date=date('YmdHi',time());
      $query="insert into sys_step_track(step_id,family_id,master_id,step_order,encours,done,date)
              values('$ids_steps[$s]','$id_family','$master','$s','false','false','$date')";
      pg_query($database_connection,$query) or die(pg_last_error());
   }
}

function update_process_encours($master)
{
  global $database_connection;
  $query="update sys_process_encours set encours='0', done='1' where process='$master'";
  pg_query($database_connection,$query);
}

function get_families($master,$type)
{
  global $database_connection;
  switch($type)
    {
    case "all":
      $query="select family_id from sys_family_track where master_id='$master'";
      break;
    case "left":
      $query="select family_id from sys_family_track where encours='false'
              and done='false' and master_id='$master' order by family_order";
      break;
    case "encours":
      $query="select family_id from sys_family_track where encours='true'
              and done='false' and master_id='$master' order by family_order";
      break;
    case "done":
      $query="select family_id from sys_family_track where encours='false'
              and done='true' and master_id='$master' order by family_order";
      break;
    }
  $res=pg_query($database_connection,$query);
  while($row=pg_fetch_array($res))
    $ids[]=$row[0];
  return $ids;
}

function get_families_left($master)
{
  global $database_connection;
  $query="select family_id from sys_family_track where encours='false' and done='false'
          and master_id='$master' order by family_order";
  $res=pg_query($database_connection,$query);
  while($row=pg_fetch_array($res))
    $ids[]=$row[0];
  return $ids;
}



function get_ordre($famille,$master)
{
  global $database_connection;
  $query="select family_order from sys_family_track
          where family_id='$famille' and master_id='$master'";
  $res=pg_query($database_connection,$query);
  $row=pg_fetch_array($res);
  $ordre=$row[0];
  return $ordre;
}


/**
 * vérifie si la famille $family du master $master est terminée
 * @param string $family
 * @param string $master
 * @return bool
 */
function check_finished_families($master,$family)
{
  global $database_connection;
  $query1="select count(*) from sys_step_track where master_id='$master'
           and family_id='$family'";
  $res1=pg_query($database_connection,$query1);
  $row1=pg_fetch_array($res1);
  $query2="select count(*) from sys_step_track where master_id='$master'
           and family_id='$family'and done='true'";
  $res2=pg_query($database_connection,$query2);
  $row2=pg_fetch_array($res2);
  if($row1[0]==$row2[0] && $row1[0]!=0)
    return true;
  else
    return false;
}

function check_finished_previous_f( $master, $ordre)
{
  global $database_connection;
  $query="select family_id from sys_family_track where encours='true' and master_id='$master'
          and family_order < '$ordre'  ";
  $res=pg_query($database_connection,$query);
  if(pg_num_rows($res)>0)
    return false;
  else return true;
}

function check_finished_m($master)
{
  global $database_connection;
  $query1="select count(*) from sys_family_track where master_id='$master'";
  $res1=pg_query($database_connection,$query1);
  $row1=pg_fetch_array($res1);
  $query2="select count(*) from sys_family_track where master_id='$master' and done='true'";
  $res2=pg_query($database_connection,$query2);
  $row2=pg_fetch_array($res2);
  if($row1[0]==$row2[0])
    return true;
  else
    return false;
}


/**
 * nettoie la table sys_step_track des enregistrements de $master et $famille
 * @param string $famille
 * @param string $master
 */
function clean($master,$famille)
{
  global $database_connection;
  $query="delete from sys_step_track
          where master_id='$master' and family_id='$famille'";
  pg_query($database_connection,$query);
}

function update_log($type,$master,$famille)
{
  global $database_connection;
  //update de la table sys_process_log
  $now=date('YmdHi',time());
  if($type=="encours")
    // c'est une famille en cours
    $query = "INSERT INTO sys_process_log (type, master, family, date, encours,done)
            VALUES ('famille','$master','$famille','$now','true','false')";
  else
    // c'est une famille terminée
    $query = "INSERT INTO sys_process_log (type, master, family, date, encours,done)
            VALUES ('famille','$master','$famille','$now','false','true')";
  pg_query($database_connection, $query);
  if (pg_last_error() != '') {echo pg_last_error() . " " . $query . "<BR>\n";}
}

/**
 * met à jour la table sys_family_track, met done='true' pour la famille $famille du master $master
 * @param string $famille
 * @param string $master
 */
function update_families_encours($master,$famille,$type)
{
  global $database_connection;
  switch($type)
    {
    case "done":
      $query="update sys_family_track set encours='false', done='true'
            where master_id='$master' and family_id='$famille'";
      break;
    case "encours":
      $query="update sys_family_track set encours='true', done='false'
            where master_id='$master' and family_id='$famille'";
      break;
    }
  pg_query($database_connection,$query);
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

///////////////////////////////
// debut du script
//////////////////////////////
$masters_f=get_masters("false","false");
//echo "après get_masters\n";
for($m=0;$m<count($masters_f);$m++)
  {
    // on ne garde que les familles non traitées (cad encours=false et done = false)
    $ids_fam_left=get_families($masters_f[$m],"left");

    // on récupère les infos (internal ou external) pour les familles non traitées
    $infos_int_ext=get_infos_int_ext($ids_fam_left);

    /*echo "familles à exécuter pour le master $masters_f[$m] <br>";
    for($i=0;$i<count($infos_int_ext);$i++)
      echo $infos_int_ext[$i][0]." => ".$infos_int_ext[$i][1]."\t";
    echo"<br>";*/
    switch($infos_int_ext[0][1])
      {
        //$infos_int_ext[0][0] = id de famille
        //$infos_int_ext[0][1] = type de famille

      case "internal":
        //echo "la famille est interne,";
        //on récupère l'ordre de la famille
        $ordre=get_ordre($infos_int_ext[0][0],$masters_f[$m]);
        //on regarde si les familles précédentes sont terminées
        if(check_finished_previous_f($masters_f[$m],$ordre))
          {
            //echo "je lance les steps<br>";
            $steps=get_steps_for_family($infos_int_ext[0][0]);
            ins_step_ids($steps,$infos_int_ext[0][0],$masters_f[$m]);
            update_families_encours($masters_f[$m],$infos_int_ext[0][0],"encours");
            $master_name=get_name($masters_f[$m],"master");
            $family_name=get_name($infos_int_ext[0][0],"famille");
            update_log("encours",$master_name,$family_name);
          }
        //else
          //echo " j'attends<br>";
        break;


      case "external":
        $ordre=get_ordre($infos_int_ext[0][0],$masters_f[$m]);
        if(check_finished_previous_f($masters_f[$m],$ordre))
          {
            $ff=0;
            while($infos_int_ext[$ff][1]=="external")
              {
        //        echo "la famille est externe, je lance les steps<br>";
                $steps=get_steps_for_family($infos_int_ext[$ff][0]);
                ins_step_ids($steps,$infos_int_ext[$ff][0],$masters_f[$m]);
                update_families_encours($masters_f[$m],$infos_int_ext[$ff][0],"encours");
                $master_name=get_name($masters_f[$m],"master");
                $family_name=get_name($infos_int_ext[$ff][0],"famille");
                update_log("encours",$master_name,$family_name);
                $ff++;
              }
          }
        break;
      }
  }

// si tous les steps pour une famille en cours et un master donnés sont terminés,
// on déclare la famille de ce master terminée :
$masters=get_masters("","");
for($i=0;$i<count($masters);$i++)
  {
    $families=get_families($masters[$i],"encours");
    for($f=0;$f<count($families);$f++)
      {
        $var=check_finished_families($masters[$i],$families[$f]);
        if($var)
          {
            //echo "famille $families[$f] du master $masters[$i] terminée<br>";
            update_families_encours($masters[$i],$families[$f],"done");
            clean($masters[$i],$families[$f]);
            $master_name=get_name($masters[$i],"master");
            $family_name=get_name($families[$f],"famille");
            update_log("done",$master_name,$family_name);
          }
      }
  }
?>
