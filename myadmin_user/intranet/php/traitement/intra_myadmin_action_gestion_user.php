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
/* Fonction javascript */
/* Permettant de gérer le moteur de recherche */
/* 4 pour le focus sur le champ modifié avec positionnement du curseur à la fin */

/* 1 permettant de valider le formulaire de recherche */
/* Et de connaître le champ modifié  */
?>
<script>
function focus_nom()
        {
        document.recherche.nom_rechercher.focus();
        document.recherche.nom_rechercher.value=document.recherche.nom_rechercher.value;
        }
function focus_prenom()
        {
        document.recherche.prenom_rechercher.focus();
        document.recherche.prenom_rechercher.value=document.recherche.prenom_rechercher.value;
        }
function focus_service()
        {
        document.recherche.service_rechercher.focus();
        document.recherche.service_rechercher.value=document.recherche.service_rechercher.value;
        }
function focus_email()
        {
        document.recherche.email_rechercher.focus();
        document.recherche.email_rechercher.value=document.recherche.email_rechercher.value;
        }

function valider_recherche(focus_champ)
        {
        document.recherche.focus.value=focus_champ;
        document.recherche.submit();
        }
</script>
<?
/* fonctionne permettant de créer la navigation */
function navigation($debut, $nombre_ligne, $nombre_position, $couleur_chiffre_en_cours, $couleur_autre_chiffre,$nom_rechercher,$prenom_rechercher,$service_rechercher, $email_rechercher,$classement,$recherche_users)
        {
         global $database_connection;

        $execution_recherche=pg_query($database_connection,$recherche_users);
        $nombre_users=pg_num_rows($execution_recherche);
        if($nombre_users>$nombre_ligne)
                {
                $creation_navigation="<br><table border=0 cellspacing=0 cellpadding=2 align=center><tr><td align=center>";
                /* Dans le cas ou il y a plus d'enregistrement que de position souhaitez */
                if((($debut/$nombre_ligne)>$nombre_position/2) && ($nombre_users/$nombre_ligne>$nombre_position))
                        {
                        $depart=($debut/$nombre_ligne)-($nombre_position/2);
                        $fin=($debut/$nombre_ligne)+($nombre_position/2);
                        }
                else
                        {
                        $depart=0;
                        $fin=$nombre_position;
                        }
                /* Description des éléments utilisés */
                /* $chiffre_affiche et valeur_chiffre vont nous permettre de spécifier le chiffre à afficher */
                /* $limit va nous permettre d'apliquer une variable à début et de modifier l'execution de la requête*/
                for($limit=$depart*$nombre_ligne,$chiffre_affiche=$depart;$chiffre_affiche<$fin && $limit<$nombre_users;$limit+=$nombre_ligne,$chiffre_affiche++)
                        {
                        /* Si le compteur commence après 0 on met un signe pour revenir en arrière */
                        if($chiffre_affiche==$depart && $chiffre_affiche!=0) $valeur_affiche="<<";
                        /* Si le compteur continue après le nombre de position on met un signe pour continuer en avant */
                        elseif($chiffre_affiche==$fin-1) $valeur_affiche=">>";
                        /* Sinon on affiche le numero en cours +1 car on commence de zéro */
                        else $valeur_affiche=$chiffre_affiche+1;
                        /* Si le chiffre en cour est détecté on change sa couleur */
                        if($debut==$limit) $couleur=$couleur_chiffre_en_cours;
                        else $couleur=$couleur_autre_chiffre;
                        $creation_navigation.="<a href='intra_myadmin_gestion_affichage_user.php?nom_rechercher=$nom_rechercher&prenom_rechercher=$prenom_rechercher&service_rechercher=$service_rechercher&debut=$limit&email_rechercher=$email_rechercher&classement=$classement#bottom'><font color='$couleur'>$valeur_affiche</font></a>";
                        /* On ajoute un tiret sauf si on se trouve à la fin */
                        if($chiffre_affiche<$fin-1 && $limit<$nombre_users-$nombre_ligne) $creation_navigation.=" - ";
                        }
                $creation_navigation.="</td></tr></table><br>";
                }
        return $creation_navigation;
        }

/* Message de confirmation de suppression d'un utilisateur */
if($pas_affich_confirm!=1)
        {
        /* 1er Cas */
        if ($action_user=="Supprimer")
                {
                $action_user="";
                ?>
                <script>
                if(confirm("Delete this user ?"))
                        {
                        location.href="intra_myadmin_gestion_affichage_user.php?pas_affich_confirm=1&action_user=Supprimer&id_user_gestion=<? echo $id_user_gestion; ?>&classement=<? echo $classement;?>&nom_rechercher=<? echo $nom_rechercher; ?>&prenom_rechercher=<? echo $prenom_rechercher; ?>&service_rechercher=<? echo $service_rechercher; ?>&debut=<? echo $debut; ?>&email_rechercher=<? echo $email_rechercher;?>";
                        }
                else
                        {
                        location.href="intra_myadmin_gestion_affichage_user.php?classement=<? echo $classement;?>&nom=<? echo $nom; ?>&prenom=<? echo $prenom; ?>&service=<? echo $service; ?>&debut=<? echo $debut; ?>&email=<? echo $email;?>";
                        }
                </script>
                <?
                }
        }

/* Toutes les actions d'ajout de modification et de suppresion dans la base de donnée user sont exécuté ici*/
switch($action_user)
        {
        case "Update" :                /*********************************/
                                                /* Modification d'un utilisateur */
                                                /*********************************/
                                                /* On test que les informations de base saisies sont correct */
                                                if(($password_mngt==$confirm_password_mngt) && trim($username_mngt) && trim($login_mngt))
                                                        {
                                                        /* On modifie l'utilisateur qui a été sélectionné avec les nouvelles informations         saisies*/
                                                        $requete_modification_user="UPDATE $nom_table_users set user_prenom='$user_prenom_mngt', username='$username_mngt', user_mail='$mail_mngt', login='$login_mngt', password='$password_mngt', user_profil='$user_profil',user_agregation_network='$agregation_network', user_agregation_value='$agregation_network_value' where id_user like '$id_user_gestion'";
                                                        pg_query($database_connection,$requete_modification_user) or die ("Error, the user has not been modified");
                                                        }
                                                else
                                                        {
                                                        ?>
                                                        <script>
                                                                alert("Please check the data you have entered");
                                                                history.back(-1);
                                                        </script>
                                                        <?
                                                        }
                                                break;

        case "Save":                /**************************/
                                                /* Ajout d'un utilisateur */
                                                /**************************/

                                                /* On test que les informations de base saisies sont correct */

                                                if(($password_mngt==$confirm_password_mngt) && trim($username_mngt) && trim($login_mngt))
                                                        {
                                                        //insère l'utilisateur dans la table
                                                        $id_user_new=rand(0,80000);
                                                        $enregistrement_utilisateur="INSERT INTO $nom_table_users (id_user, username,user_prenom, user_mail,login, password, user_profil, user_agregation_network, user_agregation_value) VALUES ('$id_user_new', '$username_mngt','$user_prenom_mngt','$mail_mngt','$login_mngt','$password_mngt','$user_profil','$agregation_network','$agregation_network_value')";
                                                        pg_query($database_connection,$enregistrement_utilisateur) or die ("Error, the user has not been saved");

                                                        //mise à jour de la table qui contient les paramètres utilisateurs pour affihcer les données du sélecteur
                                                        //le but est d'initialiser une entrée dans cette table pour qu'il n'y ait pas d'erreur lors de la première connexion
                                                        $week_en_cours=ceil(date("z")/7)-1;
                                                        $day_courant=date("d");
                                                        $year_courant=date("Y");
                                                        $month_courant=date("m");
                                                        $default_period=20;
                                                        $query="INSERT into $nom_table_parametres_user (id_user, week, period, day, month, general_value, general_type, year, nombre_wcl) VALUES ('$id_user_new','$week_en_cours','$default_period','$day_courant','$month_courant','bsc','BKB01','$year_courant','10')";
                                                        pg_query($database_connection,$query);
                                                        }
                                                else
                                                        {
                                                        ?>
                                                        <script>
                                                                alert("Please check the data you have entered");
                                                                history.back(-1);
                                                        </script>
                                                        <?
                                                        }
                                                break;

        case "Supprimer" :        /********************************/
                                                /* Suppression d'un utilisateur */
                                                /********************************/

                                                $suppression_utilisateur="DELETE from $nom_table_users where id_user like '$id_user_gestion'";
                                                pg_query($database_connection,$suppression_utilisateur) or die ("User deletion failed");
                                                $query= "DELETE FROM $nom_table_parametres_user where id_user='$id_user_gestion'";
                                                pg_query($database_connection,$query);
                                                break;

        default : break;
        }
        ?>
