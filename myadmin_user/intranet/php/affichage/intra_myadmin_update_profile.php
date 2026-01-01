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
		Traitement des enregistrements de profile management :
		ajout / modification, creation et suppression d'un profil.
		christophe
	*/

	session_start();

	// Gestion de l'affichage du menu.
	function affichage_menu($id_menu_deroule, $variable_coche, $_POST, $chaine_menu_coche, $profil_type)
	{
		global $database_connection, $nom_table_menu_deroulant;


		//variable coche permet de savoir si le menu de parent est déjà coché
		//Cas 1 : le menu n'est pas déployé donc on considère que tous les menus héritiers sont cochés
		//cas 2 : le menu est déployé donc les menus héritiers du niveau juste en dessous gèrent le propre statut 'coché' ou 'non coché'
		$query="SELECT id_menu, libelle_menu, id_menu_parent, is_profile_ref_admin, is_profile_ref_user,  deploiement
					FROM $nom_table_menu_deroulant
					WHERE (id_menu_parent=$id_menu_deroule) ORDER BY id_menu ASC";
		$resultat=pg_query($database_connection,$query);
		$nombre_resultat=pg_num_rows($resultat);
		for ($i=0;$i<$nombre_resultat;$i++)
		{
			$row=pg_fetch_array($resultat,$i);
			$id_menu=$row["id_menu"];
			$variable_deploiement=$row["deploiement"];
			$coche = $_POST["menu_$id_menu"];

			//si le menu est coché et que ce menu n'est pas déployé alors variable_coché est à 1 sinon 0
			if (($coche==1  && $variable_deploiement==0) || $variable_coche==1)
			{ $menu_coche=1; } else { $menu_coche=0; }

			// si la case est cochée ou que la variable cochée est à 1 alors le menu est coché
			if ($coche==1  || $variable_coche==1){
				$ajout = true;
				if($profil_type == "admin"){
					//echo $row["is_profile_ref_user"]." )) ". $row["is_profile_ref_admin"]."<br>";
					if($row["is_profile_ref_user"] == 1 && empty($row["is_profile_ref_admin"])){
						$ajout = false;
					}
					if($row["is_profile_ref_admin"] != 1) $ajout = false; // Ligne rajoutée par christophe le 26 08 2005
					// Ligne de DEBUG, enlever le commentaire pour afficher la liste des menus d'un profil de type administrateur.
					//if ($ajout) echo $row["libelle_menu"]."<br>";
				} else {
					if($row["is_profile_ref_admin"] == 1 && empty($row["is_profile_ref_user"])){
						$ajout = false;
					}
					if($row["is_profile_ref_user"] != 1) $ajout = false; // Ligne rajoutée par christophe le 26 08 2005
				}

				if($ajout) $GLOBALS["chaine_menu_coche"] .=$id_menu."-";
				//echo $row["libelle_menu"]." menu admin : ".$row["is_profile_ref_admin"]." / menu user : ".$row["is_profile_ref_user"]."<br>";
			}

			affichage_menu($id_menu, $menu_coche, $_POST, $chaine_menu_coche,$profil_type);
		}
	}
	// fin de la fonction

	include("../../../../php/environnement_liens.php");
	include($niveau4_vers_php."database_connection.php");
	include($niveau4_vers_php."environnement_nom_tables.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");

	$profile_type = $_GET["profile_type"];

	// On définit l'action à effectuer en fonction du bouton
	// sur lequel l'utilisateur a cliqué.
	if (isset($_POST["Delete"])) $action = "suppression";
	if (isset($_POST["modification"])) $action = "modification";
	if (isset($_POST["New"])) { header("location:intra_myadmin_profile_management.php?new=true&profile_type=".$profile_type); exit; }
	if (isset($_POST["name"])) $action = "creation";

	// On récupère les données venant du formulaire.
	$id_profile = $_POST["id_profile"]; 		// id du profile venant de la liste déroulante.
	$id = $_POST["id"]; 						// gestion du déploiement d'un menu / sous-menu. (champ caché)
	$type_stockage = $_POST["type_stockage"]; 	//champ qui contient la valeur temp lorsqu'on clique sur un menu pour le déroulé ou le concenter (champ caché))
	$name = $_POST["name"]; 					// nom d'un nouveau profil.


	// DEBUG
	//echo "Id profile : ".$id_profile." // id : ".$id." // Type de stockage : ".$type_stockage." // Nom saisi: ".$nom."<br>";
	//echo "Type d'action à effectuer : ".$action;
	//echo "<br>Type de profil : ".$profile_type;
	//exit;


	switch ($action) {
		// Suppression d'un profil.
		case "suppression" :
			// On récupère l'id du profil à supprimer.
			if($id_profile==""){
				header("location:intra_myadmin_profile_management.php?profile_type=".$profile_type);
				exit;
			}
			$numero_profil=split("=",$id_profile);
			$id_profile=split("&",$numero_profil[2]);
			$id_profile=$id_profile[0];
			if($id_profile != ""){
				// Avant la suppression, on vérifie qu' aucun utilisateur n'utilise ce profil,
				// sinon on  renvoie un message d'erreur.
				$query_user_exist = " select * from users where user_profil = '$id_profile' ";
				$result_user_exist=pg_query($database_connection,$query_user_exist);
				$result_nb_user_exist = pg_num_rows($result_user_exist);
				if($result_nb_user_exist>0){	// si un utilisateur utilise ce profil, on retourne un message d'erreur.
					$msg_erreur="Some users are using this profile.";
					header("location:intra_myadmin_profile_management.php?msg_erreur=".$msg_erreur."&id_profile=$id_profile&profile_type=".$profile_type);
					exit;
				}
				// On supprime le profil et tous ses enregistrements dans profile_menu_position.
				$query = " delete from profile where id_profile=".$id_profile;
				pg_query($database_connection,$query);
				$query = " delete from profile_menu_position where id_profile='$id_profile' ";
				pg_query($database_connection,$query);
			}

			header("location:intra_myadmin_profile_management.php?profile_type=".$profile_type);
			exit;
			break;

		// Création d'un nouveau profil.
		case "creation" :
			// On recherche si le nom de profil est déjà utilisé.
			if(trim($name) == ""){
				$msg_erreur="The field is empty.";
				header("location:intra_myadmin_profile_management.php?new=new&msg_erreur=".$msg_erreur."&profile_type=".$profile_type);
				exit;
			}
			$query = " select * from profile where profile_name = '$name' ";
			$result=pg_query($database_connection,$query);
			$result_nb = pg_num_rows($result);
			if($result_nb > 0){
				$msg_erreur="This name already exists.";
				header("location:intra_myadmin_profile_management.php?new=new&msg_erreur=".$msg_erreur."&profile_type=".$profile_type);
				exit;
			}

			// on cherche la liste des menus par défaut en fonction du type du profil.
			if($profile_type == "admin"){
				$default_menu = "is_profile_ref_admin";
			} else {
				$default_menu = "is_profile_ref_user";
			}
			$query_menu_defaut = " select * from menu_deroulant_intranet where $default_menu=1 ";
			$result_menu_defaut=pg_query($database_connection,$query_menu_defaut);
			$result_nb_menu_defaut = pg_num_rows($result_menu_defaut);
			// on remplit une chaine avec les id_menu par défaut.
			$chaine_defaut = "";
			for ($k = 0;$k < $result_nb_menu_defaut;$k++){
				$result_array_menu_defaut = pg_fetch_array($result_menu_defaut, $k);
				// Attention : on ne sélectionne pas les menu créé par le dashboard builder qui sont offline (ils ont un id_page et id_menu_parent=0)
				if($result_array_menu_defaut["id_menu_parent"] == 0 && $result_array_menu_defaut["id_page"] <> ''){
					// maj.
				} else {
					$chaine_defaut .= $result_array_menu_defaut["id_menu"]."-";
				}
			}
			$chaine_defaut = substr($chaine_defaut,0,-1);

			// On enregistre seulement le nom du profil et après l'utilisateur pourra configurer les menu.
			$nom_profile = $_POST["name"]; // nom du nouveau profile
			//récupère l'id max de la table profile
			$query="SELECT max(id_profile) as max_id_profile FROM $nom_table_profile";
			$result=pg_query($database_connection,$query);
			$row=pg_fetch_array($result,0);
			$max_id_profile=$row["max_id_profile"];
			$id_profile=$max_id_profile+1;
			//insère l'enregistrement dans profile.
			$query="INSERT into $nom_table_profile (id_profile,profile_name,profile_to_menu,profile_type) VALUES ('$id_profile','$nom_profile','$chaine_defaut','$profile_type')";
			pg_query($database_connection,$query);
			// On copie les id_menu de menu_deroulant_intranet dans profile_menu_position.
			$query = " select * from menu_deroulant_intranet where $default_menu=1 order by id_menu_parent, position ";
			$result=pg_query($database_connection,$query);
			$result_nb = pg_num_rows($result);

			// p position des menus parents et q position des menus enfants.
			$p = 1; $q = 1;
			for ($k = 0;$k < $result_nb;$k++){
				$result_array = pg_fetch_array($result, $k);
				$id_menu = $result_array["id_menu"];
				$position = $result_array["position"];
				$id_menu_parent = $result_array["id_menu_parent"];
				if($result_array["id_menu_parent"] == 0 && $result_array["id_page"] <> ''){
					// maj.
				} else {
					if ($result_array["id_menu_parent"] == 0){
						$position = $p;
					} else {
						$position = $q;
					}
					$query_insert = "";
					$query_insert .= " insert into profile_menu_position (id_menu, id_profile, position, id_menu_parent) ";
					$query_insert .= " values ('$id_menu', '$id_profile', '$position', '$id_menu_parent') ";
					pg_query($database_connection,$query_insert);
					if ($result_array["id_menu_parent"] == 0){
						$p++;
					} else {
						// On vérifie si on change d'id_parent car si c'est la cas on reparts de la position 0.
						$t = $k + 1;
						if($t < $result_nb){
							$result_array = pg_fetch_array($result, $t);
							$id_menu_parent_suivant = $result_array["id_menu_parent"];
							if($id_menu_parent_suivant != $id_menu_parent){
								$q=1;
							} else {
								$q++;
							}
						} else {
							$q++;
						}
					}
				}
			}

			header("location:intra_myadmin_profile_management.php?chaine_menu_coche=$chaine&select=0&id_profile=$id_profile&profile_type=".$profile_type);
			exit;
			break;

		// Modification d'un profil et déploiement d'un sous_menu.
		case "modification" :
		default :

			//echo $id . "  --  ".$type_stockage;
			//exit;
			// Gestion du déploiement d'un sous_menu.
			//************************************************
			//récupère l'id (qui est un champ caché) lorsqu'on veut deployer ou réduire l'arborescence
			if ($id!=""){
				$query="SELECT deploiement FROM $nom_table_menu_deroulant where (id_menu='$id')";
				$result=pg_query($database_connection,$query);
				$row=pg_fetch_array($result,0);
			}
			$variable_deploiement=$row["deploiement"];
			// teste si l'arboresence n'est pas déployée
			if ($variable_deploiement==0) {
				$deploiement=1; // on veut donc déployer
			} else {
				$deploiement=0; // on veut contracter l'arboresence
			}

			if ($id!=""){
				 //mets à jour la base pour le menu sur lequel on vient de cliquer
				 $query="UPDATE $nom_table_menu_deroulant set deploiement='$deploiement' where (id_menu='$id')";
				 pg_query($database_connection,$query);
			}

			//parcoure la table des menus déroulant pour affichage à l'écran
			$id_menu_initial=0;
			$variable_coche_initial=0;
			$chaine_menu_coche_initial="";
			//affichage_menu($id_menu_initial,$variable_coche_initial, $_POST, $chaine_menu_coche_initial);
			affichage_menu($id_menu_initial,$variable_coche_initial, $_POST, $chaine_menu_coche_initial,$profile_type);

			//********************************** fin gestion déploiement.

			//enlève le dernier caractère de la chaine qui est obligatoirement un "-"
			$chaine=substr($chaine_menu_coche,0,strlen($chaine_menu_coche)-1);

			//echo $chaine; exit;

			// Enregistrement et mise-à-jour des id_menu du profil.
			//*********************************************
			//stocke la chaine lorqu'on a cliqué sur SUBMIT
			//dans ce cas, l'id profile ne correspond pas au numéro mais à la valeur de la liste déroulante.
			//champ qui contient la valeur temp lorsqu'on clique sur un menu pour le déroulé ou le concenter
			if ($type_stockage!="temp") {
				$numero_profil=split("=",$id_profile);
				$id_profile=split("&",$numero_profil[2]);
				$id_profile=$id_profile[0]; //la construction fait qu'il faut prendre la valeur 2 puis 0

				if($id_profile==""){
					header("location:intra_myadmin_profile_management.php?profile_type=".$profile_type);
					exit;
				}

				$query="UPDATE $nom_table_profile set profile_to_menu='$chaine' where (id_profile='$id_profile')";
				pg_query($database_connection,$query);
				//echo $query;
			}

			//retour à l'affichage des profiles
			header("location:intra_myadmin_profile_management.php?chaine_menu_coche=$chaine&select=0&id_profile=$id_profile&profile_type=".$profile_type);
			exit;
			break;


    }

?>
