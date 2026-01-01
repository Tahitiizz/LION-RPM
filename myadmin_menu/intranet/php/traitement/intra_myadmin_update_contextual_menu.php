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
$numero_menu = $_POST["numero_menu"] ;  //numero du menu selectionné
$nombre_action = $_POST["nombre_action"]; //nombre d'actions disponibles pour le menu déroulant

//récupère tous les identifiants des actions
$query="SELECT id FROM $nom_table_menu_contextuel";
$resultat=pg_query($database_connection,$query);
$nombre_resultat=pg_num_rows($resultat);

for ($i=0;$i<$nombre_resultat;$i++)
    {
     $row=pg_fetch_array($resultat,$i);

     $numero_action=$row["id"];
     $variable_action=$numero_action."_action";
     $variable_position=$numero_action."_position";
     $valeur_action = $_POST[$variable_action] ;  // récupère la valeur des checkbox "Actions"
     $chaine_menu_contextuel="";
     if ($valeur_action=="on")
        {
         $chaine_menu_contextuel .=$numero_action."-";
         $valeur_position = $_POST[$variable_position];
         $tab_position[]=$valeur_position;
        }
     $variable_ligne=$numero_action."_ligne";
     $valeur_ligne=$_POST[$variable_ligne] ;  // récupère la valeur des checkbox "Ligne"
     if ($valeur_ligne=="on")
        {
         $chaine_menu_contextuel .="0-";
        }

     if ($chaine_menu_contextuel<>"")
        {
         $tab_chaine_menu_contextuel[]=$chaine_menu_contextuel;
        }
    }

if (count($tab_position)>0 && count($tab_chaine_menu_contextuel)>0)
   {
    array_multisort ($tab_position, $tab_chaine_menu_contextuel); //classe les 2 tableaux en fonction de $tab_position

    foreach ($tab_chaine_menu_contextuel as $value)
            {
             $menu_contextuel .=$value;
            }

    if (substr($menu_contextuel,-1)=="-") //compare le dernier élément de la chaine
       {
        $menu_contextuel=substr($menu_contextuel,0,strlen($menu_contextuel)-1);
       }
   }
   else
   {
    $menu_contextuel='';
   }

$query="UPDATE $nom_table_menu_deroulant set liste_action='$menu_contextuel' WHERE (id_menu=$numero_menu)";
pg_query($database_connection,$query);
$file_retour=$traitement_vers_affichage."intra_myadmin_contextual_menu.php?id_menu_action=$numero_menu";
?>
<script>
window.location='<?=$file_retour?>';
</script>
