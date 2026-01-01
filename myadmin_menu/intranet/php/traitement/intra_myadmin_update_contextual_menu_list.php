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

//ces 2 variables sont récupérées de champs cachées dans le formulaire HTML
$numero_menu_contextuel = $_POST["numero_menu_contextuel"];  //numero du menu copntextuel qui doit être mis à jour selectionné
$link = $_POST["link"]; //lien pour le menu contextuel
$nom_action = $_POST["nom_action"]; //lien pour le menu contextuel
$choix = $_POST["choix_action"]; //choix pour création, modification ou suppression

switch ($choix) {
    case 0: //Mise à jour du menu contextuel
         $query="UPDATE $nom_table_menu_contextuel set url_action='$link', nom_action='$nom_action' WHERE (id=$numero_menu_contextuel)";
         pg_query($database_connection,$query);
         $file_retour=$traitement_vers_affichage."intra_myadmin_contextual_menu.php?id_action_selection=$numero_menu_contextuel";
         break;

    case 1: //insertion d'un menu contextuel
         $query="SELECT max(id) as id_max FROM $nom_table_menu_contextuel";
         $max_menu=pg_query($database_connection,$query);    //détermine l'id max
         $row = pg_fetch_array($max_menu,0);
         $id_max=$row["id_max"];
         $id_menu=$id_max+1; //on fixe l'id de manière à ce que tous les id se suivent même si on supprime un menu contextuel
         $result="INSERT INTO $nom_table_menu_contextuel (id, nom_action, url_action) VALUES ('$id_menu','$nom_action', '$link')";
         pg_query($database_connection,$query);
         $file_retour=$traitement_vers_affichage."intra_myadmin_contextual_menu.php";
        break;

    case 2:  //suppression du menu contextuel
         $query="DELETE FROM $nom_table_menu_contextuel WHERE (id=$numero_menu_contextuel)";
         pg_query($database_connection,$query);
         $file_retour=$traitement_vers_affichage."intra_myadmin_contextual_menu.php";
        break;
    }

header("location:$file_retour");
?>
