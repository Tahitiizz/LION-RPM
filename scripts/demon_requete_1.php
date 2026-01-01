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

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once("$repertoire_physique_niveau0"."php/database_connection.php");

function get_nb_encours()
{
  global $database_connection;
  $query="select count(*) from sys_requetes where encours='1'";
  $res=pg_query($database_connection,$query);
  $row=pg_fetch_row($res);
  return $row[0];
}

function get_requete()
{
  global $database_connection;
  $query="select requete, requete_id from sys_requetes where encours='0' and done='0' limit 1";
  $res=pg_query($database_connection,$query);
  while($row=pg_fetch_array($res))
    $requetes[$row[1]]=$row[0];
  return $requetes;
}

function update($requete_id,$status)
{
  global $database_connection;
  switch($status)
    {
    case "encours":
      $query="update sys_requetes set encours='1' where requete_id='$requete_id'";
      break;
    case "done":
      $query="update sys_requetes set encours='0',done='1'
              where requete_id='$requete_id'";
      break;
    }
  echo $query."<br>";
  pg_query($database_connection,$query) or die(pg_last_error());
}

function lance_requetes($requetes)
{
  global $database_connection;
  if(count($requetes)>0)
    {
      foreach($requetes as $id => $requete)
        {
          update($id,"encours");
          $reqs=unserialize($requete);
          $text.=date('r')."<br>";
          $res=pg_query($database_connection,$reqs[0]);
          if (pg_last_error() != '')
            $text.=pg_last_error()."<br>".$reqs[0]."<br>";
          else
            $text.=pg_affected_rows($res)."=".$reqs[0]."<br>";
          $text.=date('r')."<br>";

          $res2=pg_query($database_connection,$reqs[1]);
          if (pg_last_error() != '')
            $text.=pg_last_error()."<br>".$reqs[1]."<br>";
          else
            $text.=pg_affected_rows($res2)."=".$reqs[1]."<br>";
          $text.=date('r')."<br>";

          echo $text;

          update($id,"done");
        }
    }
}
sleep(2);

$nb=get_nb_encours();
if($nb>0)
        echo "nb requêtes en cours = $nb <br>";
if($nb<2)
  {
    $requete=get_requete();
    lance_requetes($requete);
  }
// switch($nb)
//   {
//   case "0":
//     $requetes=get_requetes("2");
//     lance_requetes($requetes);
//     break;
//   case "1":
//     $requete=get_requetes("1");
//     lance_requetes($requete);
//     break;
//   default:
//     break;
//   }

?>
