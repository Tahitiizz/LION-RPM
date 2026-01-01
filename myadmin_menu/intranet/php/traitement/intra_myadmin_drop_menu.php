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
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/environnement_nom_tables.php");
  if ($Submit)
  {
   $sql="SELECT * FROM $nom_table_menu_deroulant WHERE id_menu='$idmenu'";
   $result=pg_query($database_connection,$sql);
   $row = pg_fetch_array($result,0);
   $niveau=$row["niveau"];
   $position=$row["position"];
   $menuparent=$row["id_menu_parent"];
//   print $id_menu;
   if ($niveau==3)
  {
   //On met à jour le numéro de position des menus qui suivent le menu effacé
   $sql="UPDATE $nom_table_menu_deroulant SET position = position - 1 WHERE niveau='$niveau' and position > '$position' and id_menu_parent='$menuparent'";
   pg_query($database_connection,$sql);
   //On efface le menu choisi
   $sql="DELETE FROM $nom_table_menu_deroulant WHERE id_menu='$idmenu'";
   pg_query($database_connection,$sql);
  }
   if ($niveau==2)
  {
   //On met à jour le numéro de position des menus qui suivent le menu effacé
   $sql="UPDATE $nom_table_menu_deroulant SET position = position - 1 WHERE niveau='$niveau' and position > '$position' and id_menu_parent='$menuparent'";
   pg_query($database_connection,$sql);
   //On efface le menu choisi
   $sql="DELETE FROM $nom_table_menu_deroulant WHERE id_menu='$idmenu'";
   pg_query($database_connection,$sql);
   //On efface ses sous-menus
   $sql="DELETE FROM $nom_table_menu_deroulant WHERE id_menu_parent='$idmenu'";
   pg_query($database_connection,$sql);
  }
   if ($niveau==1)
  {
   //On met à jour le numéro de position des menus qui suivent le menu effacé
   $sql="UPDATE $nom_table_menu_deroulant SET position = position - 1 WHERE niveau='$niveau' and position > '$position' and id_menu_parent='$menuparent'";
   pg_query($database_connection,$sql);
   //Recherche des sous menus de niveau 2 se rapportant au menu
   $sql="SELECT * FROM $nom_table_menu_deroulant WHERE id_menu_parent='$idmenu'";
   $result2=pg_query($database_connection,$sql);
   $nombre_resultat=pg_num_rows($result2);
   for ($i=0;$i<$nombre_resultat;$i++)
       {
        $row = pg_fetch_array($result2,$i);
        $menuparent_niv2=$row["id_menu"];
        //On efface ses sous-menus de niveau 3
        $sql="DELETE FROM $nom_table_menu_deroulant WHERE id_menu_parent='$menuparent_niv2'";
        pg_query($database_connection,$sql);
       }
   //On efface ses sous-menus
   $sql="DELETE FROM $nom_table_menu_deroulant WHERE id_menu_parent='$idmenu'";
   pg_query($database_connection,$sql);
   //On efface le menu choisi
   $sql="DELETE FROM $nom_table_menu_deroulant WHERE id_menu='$idmenu'";
   pg_query($database_connection,$sql);
   }
  }
?>
<html>
<head>
        <title>Drop a Menu</title>
</head>
<body onload="javascript=window.opener.location='<?=$traitement_vers_affichage?>intra_myadmin_management_menu.php'">
 <script language="JavaScript1.2">
 self.close();
 </script>
</body>
</html>
