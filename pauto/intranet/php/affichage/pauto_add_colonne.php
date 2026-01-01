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
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
global $database_connection;

$ligne=$_GET['ligne'];
$colonne=$_GET['colonne'];
$idpage=$_GET['idpage'];

//echo "$ligne/$colonne/$idpage<br>";

     $query="select id,ligne,colonne FROM sys_pauto_config  where colonne>='$colonne' and  id_page=".$idpage;
     $result=pg_query($database_connection,$query);
	 $result_nb = pg_num_rows($result);
     //echo $query."<br>";

       for ($k = 0;$k < $result_nb;$k++){
    		  $result_array= pg_fetch_array($result, $k);
              $ligne_sup=$result_array["ligne"];
              $colonne_sup=$result_array["colonne"];
              $id_ligne=$result_array["id"];

              $new_colonne_sup=$colonne_sup+1;
              $new_frame_position=$ligne_sup.':'.$new_colonne_sup;

              $query="update sys_pauto_config set colonne='$new_colonne_sup',frame_position='$new_frame_position' where id='$id_ligne'";
              pg_query($database_connection,$query);
              //mysql_db_query('pauto',$query);
              //echo $query."<br>";
             }
header("location:pageframe.php?action=display&id_page=" . $idpage."&id_pauto=".$_GET["id_pauto"]);
?>
