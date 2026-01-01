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
   $sql="UPDATE $nom_table_menu_deroulant SET libelle_menu='$libelle',lien_menu='$lien',complement_lien='$complement',liste_action='$action',largeur='$largeur',hauteur='$hauteur' WHERE id_menu='$idmenu'";
   pg_query($database_connection,$sql);
   }
?>
<html>
<head>
        <title>Insert a Menu</title>
</head>
<body onload="javascript=window.opener.location='<?=$traitement_vers_affichage?>intra_myadmin_management_menu.php'">
 <script language="JavaScript1.2">
  self.close();
 </script>
</body>
</html>
