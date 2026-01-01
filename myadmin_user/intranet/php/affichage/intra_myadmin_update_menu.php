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
		Gestion de la création / modification et suppression de menus
		par le customisateur.
	*/

	session_start();
	include("../../../../php/environnement_liens.php");
	include($niveau4_vers_php."database_connection.php");
	include($niveau4_vers_php."environnement_nom_tables.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/traitement_chaines_de_caracteres.php");
	global $database_connection;

	// DEBUG
	//echo $_GET["action"]." ".$_GET["id_menu"];

	$action = $_GET["action"];
	$id_menu = $_GET["id_menu"];

	if(isset($_POST["name"])){
		$nom = $_POST["name"];
		$nom = renameString($nom); // fonctions qui permet d'enlever les caractères spéciaux de la chaine passée en paramètre.
	}

	// On vérifie si le champ nom est vide
	if(trim($nom) == "" && $action != "delete"){
		$msg_erreur = "empty field";
		header("location:intra_myadmin_menu_management.php?msg_erreur=$msg_erreur");
		exit;
	}
	// On recherche si le nom existe déjà.
	if($action != "delete"){
		$query_search = " select * from menu_deroulant_intranet where libelle_menu = '$nom' ";
		$result=pg_query($database_connection,$query_search);
		$nb_result= pg_num_rows($result);
		if($nb_result > 0){
			$msg_erreur = "This name already exists.";
			header("location:intra_myadmin_menu_management.php?msg_erreur=$msg_erreur");
			exit;
		}
	}

	switch ($action) {
		case 'delete' :
				// On met-à-jour toutes les positions des menus dans menu_deroulant_intranet et profile_menu_position.
				$query_update = "
									update menu_deroulant_intranet
										set position = position - 1
										where niveau = 1
										and position > (
											select position from menu_deroulant_intranet
											where id_menu = $id_menu
										)
								";
				pg_query($database_connection,$query_update);
				//echo $query_update."<br>";
				// On parcours tous les profils utilisateurs.
				$query_liste = " select * from profile where profile_type='user' ";
				$resultat = pg_query($database_connection, $query_liste);
				$nombre_resultat = pg_num_rows($resultat);
				for ($i = 0;$i < $nombre_resultat;$i++) {
					$row = pg_fetch_array($resultat, $i);
					$id_profile = $row["id_profile"];
					$query_update = "
										update profile_menu_position
											set position = position - 1
											where id_menu_parent = 0
											and position > (
												select position from profile_menu_position
												where id_menu = $id_menu and id_profile = $id_profile
											)
											and id_profile = $id_profile
									";
					pg_query($database_connection,$query_update);
				}
				//echo $query_update."<br>";
				//exit;
				$query = " delete from menu_deroulant_intranet where id_menu = $id_menu ";
				pg_query($database_connection,$query);
				// On supprime ce menu de tous les profils
				$query=" select * from profile where profile_type='user' ";
				$result=pg_query($database_connection,$query);
				$nb_result= pg_num_rows($result);
				$tab_id_menu[]=$id_menu;
				for ($j = 0;$j < $nb_result;$j++) {
					$result_array = pg_fetch_array($result, $j);
					$profil = $result_array["profile_to_menu"];
					$id_profile = $result_array["id_profile"];
					$profil = explode("-",$profil);
					$new_profil = "";
					for($i=0;$i<count($profil);$i++){
							if(!in_array($profil[$i], $tab_id_menu)){
									$new_profil.=$profil[$i]."-";
							}
					}
					$new_profil = substr($new_profil,0,-1);
					//echo "id profile : ".$id_profile." <br>new profil :".$new_profil;
					// MAJ des id_menu dans la table profile.
					$query=" update profile set profile_to_menu='$new_profil' where id_profile='$id_profile' ";
					pg_query($database_connection,$query);
					//echo "<br>".$query;
				}
				// On supprime les enregistrements de la table profile_menu_position.
				$query_delete = " delete from profile_menu_position where id_menu = ". $id_menu;
				pg_query($database_connection,$query_delete);

				// On met la colonne online à 0 et le colonne id_menu à vide dans la table sys_pauto_page_name.
				$query = " update sys_pauto_page_name set online=0, id_menu=null where id_menu='$id_menu' ";
				pg_query($database_connection,$query);

				break;


		case 'creation' :
				// On recherche la position maximale pour les menu de niveau 1 ayant comme référence is_profile_ref_user=1.
				$query = " select (max(position)+1) as max_pos from menu_deroulant_intranet where niveau=1 and is_profile_ref_user=1 ";
				$result =pg_query($database_connection,$query);
				$result_array = pg_fetch_array($result, 0);
				$position = $result_array["max_pos"];
				// On insère le menu.
				$query = " insert into menu_deroulant_intranet (niveau,position,id_menu_parent,libelle_menu,largeur,hauteur,droit_affichage,droit_visible,menu_client_default,is_profile_ref_user) ";
				$query .= " values (1,'$position',0,'$nom',150,20,'customisateur',0,0,1) ";
				pg_query($database_connection,$query);
				// On insère l'id_menu dans tous les profils de type user et dans la table profile_menu_position.
				$query = " select id_menu from menu_deroulant_intranet where libelle_menu = '$nom' ";
				$result=pg_query($database_connection,$query);
				$result_array = pg_fetch_array($result, 0);
				$id_menu = $result_array["id_menu"];
				// MAJ des profils.
				$query = " select distinct id_profile from profile_menu_position  where id_profile in (select id_profile from profile where profile_type='user')";
				$result = pg_query($database_connection, $query);
				$nb_result= pg_num_rows($result);
				for ($i = 0;$i < $nb_result;$i++) { // On insère ce nouveau menu dans tous les profils.
					$row = pg_fetch_array($result, $i);
					$id_profile = $row["id_profile"];
					// On recherche la position du menu.
					$query2 = " select (case when max(position) IS NULL THEN 1 ELSE max(position)+1 END) as max_pos from profile_menu_position where id_menu_parent=0 and id_profile='$id_profile' ";
					$result2=pg_query($database_connection,$query2);
					$result_array2 = pg_fetch_array($result2, 0);
					$position = $result_array2["max_pos"];

					$query_insert = " insert into profile_menu_position (id_menu, id_profile, position, id_menu_parent) ";
					$query_insert .= " values ('$id_menu', '$id_profile', '$position', 0) ";
					pg_query($database_connection,$query_insert);
				}
				$query = " select * from profile where profile_type='user' ";
				$result = pg_query($database_connection, $query);
				$nb_result= pg_num_rows($result);
				// on ajoute le nouvel id_menu dans les profils.
				for ($i = 0;$i < $nb_result;$i++) {
					$row = pg_fetch_array($result, $i);
					$liste_id = explode("-",$row["profile_to_menu"]);
					$id_profile = $row["id_profile"];
					//if(in_array($id_menu_parent, $liste_id)){
					$new_liste = $row["profile_to_menu"]."-".$id_menu;
					$query_update = " update profile set profile_to_menu = '$new_liste' where id_profile = '$id_profile' ";
					pg_query($database_connection,$query_update);
					//}
				}

				break;

		case 'modification' :
				$query = " update menu_deroulant_intranet set libelle_menu='$nom' where id_menu = $id_menu ";
				pg_query($database_connection,$query);
				break;
	}

	header("location:intra_myadmin_menu_management.php");
	exit;
?>
