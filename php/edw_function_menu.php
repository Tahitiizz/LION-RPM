<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*
*	31/12/2007 christophe : ajout de fonctions permettant de grer la création / suppression / modification de menus.
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_profile entre cote  [REFONTE CONTEXTE]
*	02/02/2009 GHX
*		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
*	16/03/2009 GHX
*		- Modification de la requete pour prendre en compte le nouvel ID dans la requete d'insertion de la table profile
*	19/10/2009 GHX
*		- Modification de la fonction addMenu pour ajouter des cotes pour l'ID menu parent sinon erreur SQL
*	05/03/2010 NSE bz 14366
*		- modification de la fonction addMenu de façon à autoriser la présence du paramètre id_menu
*  22/11/2012 MMT Bz 30452 utilisation de getAll dans deleteMenu
*  02/07/2013 MGO Bz 33439 utilisation de getAll et executeQuery dans addMenu
*/
?>
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*
*	- 02 03 2007 christophe : ajout d'un contrôle.
*
*/
?>
<?
	
	/**
	* Fonction deleteMenu ($id_menu, $database_connection)
	* permet de supprimer le menu dont l'identifiant est passé en paramètre.
	* @param : tableau $menu tableau contenant tous les paramètres du nouveau menu. 
	* @param : $database_connection,  Ressource de connexion PostgreSQL $database_connection.
	*/
	function deleteMenu ($id_menu, $database_connection)
	{
	
		$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE id_menu IN ($id_menu)";
        // 22/11/2012 MMT Bz 30452 utilisation de getAll 
        $res = $database_connection->getAll($query);
        foreach ($res as $row) {
			$Menu = new MenuModel($row['id_menu']);
			$Menu->deleteMenu();
        }
            
		return true;
		/*
			Gestion des erreurs d'appel de la fonction.
		*/
		// vérification des paramètres vides.
		if ( empty($id_menu) || empty($database_connection) )
		{
			echo "deleteMenu function calling error : missing parameters.";
			return 0;
		}
		
		/*
			Suppression des enregistrements.
		*/
		// Suppression dans la table profile.
		// on récupère le contenu de la colonne profile_to_menu
		$query = " 
			SELECT profile_to_menu, id_profile FROM profile 
				WHERE id_profile IN (
					SELECT DISTINCT id_profile FROM profile_menu_position WHERE id_menu IN ($id_menu)
				)
			";
		$res = pg_query($database_connection, $query);
		$nb = pg_num_rows($res);
		for ($i = 0;$i < $nb;$i++)
		{
			$row = pg_fetch_array($res, $i);
			$tab_temp = $tab_temp2 = array();
			// on fait un explode sur la liste pour avoir la liste des id_menu dans un tableau puis un array_flip pour que les id_menu soient en index.
			//$tab_temp2 = array_flip(explode("-",$row['profile_to_menu']));
			$tab_temp2 = explode("-",$row['profile_to_menu']);
			for($p=0;$p<count($tab_temp2);$p++)
				$tab_temp[$tab_temp2[$p]]=$tab_temp2[$p];
			//$tab_temp  = $tab_temp2;
			unset($tab_temp[$id_menu]); // on supprime l'id_menu du tableau.
			// Mise-à-jour de la table profile.
			$q = " 
				UPDATE profile 
					SET profile_to_menu = '".implode("-",$tab_temp)."'
					WHERE id_profile = '".$row['id_profile']."'
				";
			pg_query($database_connection, $q);
		}
		
		
		// Suppression dans la table menu_deroulant_intranet.
		$query = "DELETE FROM menu_deroulant_intranet WHERE id_menu IN ($id_menu)";
		pg_query($database_connection, $query);

		// Suppression dans la table profile_menu_position.
		$query = "DELETE FROM profile_menu_position WHERE id_menu IN ($id_menu)";
		pg_query($database_connection, $query);
	}
	
	
	/**
	* Fonction updateMenu ($menu, $database_connection)
	* permet de mettre à jour un menu.
	* il faut au minimum la colonne id_menu et un autre paramètre
	* toutes les colonnes peuvent être mises à jour.
	* Certaines colonnes ne peuvent être mises-à-jour comme is_profile_ref_user, is_profile_ref_admin.
	* @param : tableau $menu tableau contenant tous les paramètres du menu. 
	* @param : $database_connection,  Ressource de connexion PostgreSQL $database_connection.
	*
	* 05/03/2010 NSE : erreur lors de la vérification de la présence des colonnes is_profile_ref_user et is_profile_ref_admin
	*/
	function updateMenu ($menu, $database_connection)
	{
		__debug('updateMenu');
		/*
			Gestion des erreurs d'appel de la fonction.
		*/
		// on vérifie la présence de id_menu
		if ( empty($menu["id_menu"]) )
		{
			echo "updateMenu function calling error : Id_menu parameter is needed.";
			return 0;
		}
		// on vérifie si il y a au moins un autre paramètre.
		if ( count($menu) < 2 )
		{
			echo "updateMenu function calling error : at least 2 parameters.";
			return 0;
		}
		// On vérifie si les colonnes is_profile_ref_user, is_profile_ref_admin sont présentes.
		// 05/03/2010 NSE correction : on avait 2 fois is_profile_ref_user dans le test
		if ( isset($menu["is_profile_ref_user"]) || isset($menu["is_profile_ref_admin"]) )
		{
			echo "updateMenu function calling error : is_profile_ref_user and is_profile_ref_admin prohibited.";
			return 0;
		}
		
		/*
			Construction de la requête de mise-à-jour.
		*/
		// On récupère l'identifiant du menu et on l'enlève du tableau.
		$id_menu = $menu["id_menu"];
		unset($menu["id_menu"]);
		
		$tab_update_list = array();
		foreach ( $menu as $column=>$value )
		{
			// Si il n'y a pas de parenthèse en début et fin de chaine, ce n'est pas une requête, on peut donc mettre des ''.
			if ( substr(trim($value),0,1) != "(" && substr(trim($value),-1,1) != ")" )
				$tab_update_list[] = " $column='$value' ";
			else
				$tab_update_list[] = " $column=$value ";
		}
		$update_list = implode(",",$tab_update_list);
		
		$query = "
			UPDATE menu_deroulant_intranet
				SET $update_list
				WHERE id_menu='$id_menu'
		";
		pg_query($database_connection, $query);
		
	}
	
	/**
	*
	*	30/01/2009
	*		modification SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
	*
	*
	* Fonction addMenu ($menu, $database_connection)
	* permet d'ajouter un nouveau menu.
	* La position du menu est automatique, le menu s'ajoute toujours à la suite des autres.
	* @param : tableau $menu tableau contenant tous les paramètres du nouveau menu. 
	* Ce tableau peut contenir la colonne id_menu.
	* L'index du tableau correspond on nom des colonnes. Il faut au minimum, la colonne libelle_menu et droit_affichage et id_menu_parent et
	* is_profile_ref_user ou is_profile_ref_admin qui détermine quels sont les profils dans lesquels on vat ajouter le menu.
	* Exemple pour un menu user :
	*	$menu["libelle_menu"] = "nouveau menu";
	*	$menu["droit_affichage"] = "client";
	*	$menu["id_menu_parent"] = 0;
	* 	$menu["is_profile_ref_user"] = 1;
	* Attention : vous pouvez mettre des requêtes comme valeur pour les champs.
	* La fonction détecte qu'il y a une requête quand le premier caractère est ( et le dernier  ).
	* 	$menu["libelle_menu"] = "nouveau menu";
	*	$menu["droit_affichage"] = "client";
	*	$menu["id_menu_parent"] = "(SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu='TOPOLOGY')";
	*
	* Rôle des colonnes de la table menu_deroulant_intranet :
	*	- int niveau : niveau du menu (1 ou 2, 1 par défaut si id_menu_parent = 0 sinon 2).
	*	- int position : ordre du menu dans son groupe. Par défaut le nouveau menu est placé en dernier.
	*	- int id_menu_parent : identifiant (id_menu) du menu parent.
	*	- string libelle_menu : label du menu.
	*	- string lien_menu : lien vers le fichier concerné (exemple : /php/toto.php)
	*	- string liste_action : liste des id de la table menu_contextuel pour afficher la liste des menus obtenus via un clic droit sépéra par des - (exemple : 33-87-0-92). Le 0 représente une barre à l'affichage.
	*	- int largeur : largeur en pixel, 150 par défaut.
	*	- int hauteur : hauteur en pixel, 20 paar défaut.
	*	- int id_page : identifiant du dashboard du menu venant de la table sys_pauto_page_name.
	*	- string droit_affichage : définit qui a créé ce menu. Pour les menu administrateur, c'est astellia. Pour les menu utilisateur c'est customisateur ou client.
	*	- int droit_visible : 0 par défaut, la colonne sera supprimée.
	*	- int menu_client_defaut : 0 par défaut. Si à 1, c'est dans ce menu que seront ajoutés les dahsboards clients.
	*	- int is_profile_ref_user : 0 par défaut, si à 1 ce menu sera ajouté dans les menus par défaut pour les utilisateurs.
	*	- int is_profile_ref_admin : 0 par défaut, si à 1 ce menu sera ajouté dans les menus par défaut pour les administrateurs.
	*	Les colonnes complement_lien, repertoire, deploiement et is_menu_defaut sont inutiles (certaines seront supprimées).
	* @param : $database_connection,  Ressource de connexion PostgreSQL $database_connection.
	* @return : int id_menu du noouveau menu insèré. 
	*
	* 05/03/2010 NSE bz 14366 : Le tableau menu peut contenir la colonne id_menu.
	* 02/07/2013 MGO Bz 33439 : Utilisation de getAll et executeQuery dans addMenu
	*/
	function addMenu ($menu, $database_connection)
	{
		__debug('addMenu');
		/*
			Gestion des erreurs d'appel de la fonction.
		*/
		// vérification des paramètres vides.
		if ( empty($menu["droit_affichage"]) || empty($menu["libelle_menu"]) || 
			 !isset($menu["id_menu_parent"]) || empty($database_connection) )
		{
			echo "addMenu function calling error : missing parameters.";
			return 0;
		}
		
		// 05/03/2010 NSE bz 14366 la colonne id_menu est autorisée. Si l'id n'est pas spécifié, on l'initialise.
		if ( !isset($menu["id_menu"]) || empty($menu["id_menu"]) )
		{
			// 14:23 02/02/2009 GHX
			// Appel à la fonction qui génére un unique ID
			$menu["id_menu"] = generateUniqId('menu_deroulant_intranet');
		}
		
		// 09/04/2010 BBX : Il faut supprimer le menu de element_to_delete
		$query = "DELETE FROM sys_definition_context_element_to_delete
		WHERE sdcetd_table = 'menu_deroulant_intranet'
		AND sdcetd_id = '".$menu['id_menu']."'";

		$database_connection->executeQuery($query);	
		// Il faut au moins un profil.
		/*
		if ( $menu["is_profile_ref_user"] != 1 && $menu["is_profile_ref_admin"] != 1 )
		{
			echo "addMenu function calling error : is_profile_ref_user or is_profile_ref_admin may be egal to 1.";
			return 0;
		}*/
		// Si il y a une requête pour l'id_menu_parent, on vérifie que le menu existe.
		if ( !is_numeric($menu["id_menu_parent"]) )
		{
			$res = $database_connection->getAll($menu["id_menu_parent"]);
			if (count($res) == 0)
			{
				echo "addMenu function calling error : not existing menu (query : ".$menu["id_menu_parent"].").";
				return 0;
			}
			$menu["id_menu_parent"] = $res[0]["id_menu"];
		}
			
		/*
			Paramètres par défaut (à mettre ensuite directement en base)
		*/
		if (!isset($menu["niveau"]))
		{
			$menu["niveau"] = 2;
			if ($menu["id_menu_parent"] == "0") $menu["niveau"] = 1;
		}
		
		// 15:51 19/10/2009 GHX
		// Ajout des cotes pour l'ID menu parent sinon erreur SQL
		if (!isset($menu["position"]))
			$menu["position"] = " (SELECT CASE WHEN MAX(position)>0 THEN MAX(position)+1 ELSE 1 END FROM menu_deroulant_intranet WHERE id_menu_parent='".$menu["id_menu_parent"]."') ";
		// On met deploiement à 0 seulement si c'est un menu parent.
		// 02/07/2013 MGO Bz 33439 ajout de strval sur la valeur
		if (!isset($menu["deploiement"]) && strval($menu["id_menu_parent"]) == "0") {$menu["deploiement"] = 0;}
		// 02/07/2013 MGO Bz 33439 liste_action laissée vide
		//if (!isset($menu["liste_action"])) $menu["liste_action"] = "";
		if (!isset($menu["largeur"])) $menu["largeur"] = 150;
		if (!isset($menu["hauteur"])) $menu["hauteur"] = 20;
		// 02/07/2013 MGO Bz 33439 paramètres laissés vide au lieu de 0
		/*
		if (!isset($menu["droit_visible"]))			$menu["droit_visible"] = 0;
		if (!isset($menu["menu_client_default"])) 	$menu["menu_client_default"] = 0;
		if (!isset($menu["is_profile_ref_user"])) 	$menu["is_profile_ref_user"] = 0;
		if (!isset($menu["is_profile_ref_admin"])) 	$menu["is_profile_ref_admin"] = 0;
		*/		
		
		/*
			Construction / exécution des requêtes (tables menu_deroulant_intranet, profile et profile_menu_position).
		*/
		// Insertion dans la table menu_deroulant_intranet
		$columns = $values = '';
		foreach ( $menu as $column=>$value )
		{
			$columns .= $column.',';
			// Si c'est une requête, on ne met pas les ' '
			// Si il n'y a pas de parenthèse en début et fin de chaine, ce n'est pas une requête, on peut donc mettre des ''.
			// 02/07/2013 MGO Bz 33439 correction pour autoriser des parenthèses en cours de chaîne
			if ( substr(trim($value),0,1) == "(" && substr(trim($value),-1,1) == ")" )
				$values .= $value.",";
			else
				$values .= "'".$value."',";

		}
		// On supprime la dernière virgule si il y en a une.
		if ( substr($columns,-1,1)==',' ) $columns = substr($columns,0,strlen($columns)-1);
		if ( substr($values,-1,1)==',' )  $values = substr($values,0,strlen($values)-1);
		
		// Insertion dans la table menu_deroulant_intranet.
		$query = "
			INSERT INTO menu_deroulant_intranet ($columns) VALUES ($values);
		";
		
		$insert_ok = $database_connection->executeQuery($query);
		//__debug($query);
		
		
		if ($insert_ok)
		{
			// Si les 2 champs sont à 0, on n'insère pas le menu dans profile et profile_menu_position.
			if ( $menu["is_profile_ref_user"] == 1 || $menu["is_profile_ref_admin"] == 1 )
			{
				// On définit quel est le type de profil à mettre à jours (cela peut-être tous les profils...).
				$tab_profile_type = array();
				if ( $menu["is_profile_ref_user"]==1 )  $tab_profile_type[] = "'user'";
				if ( $menu["is_profile_ref_admin"]==1 ) $tab_profile_type[] = "'admin'";
				$profile_type = implode(",",$tab_profile_type);
				//__debug($profile_type);
				
				// 16:13 16/03/2009 GHX
				// Modification de la requete pour prendre en compte le nouvel ID
				//On ajoute le menu dans la colonne profile_to_menu de la table PROFILE (cette étape sera supprimée quand la clonne n'existera plus).
				$query = "
					UPDATE profile SET
						profile_to_menu = profile_to_menu || '-' || '{$menu["id_menu"]}' 
						WHERE profile_type IN ($profile_type)
					";
				//__debug($query);
				$database_connection->executeQuery($query);
			
				// MAJ de la table profile_menu_position.
				$query_liste = " SELECT id_profile FROM profile WHERE profile_type IN ($profile_type) ";
				$res = $database_connection->getAll($query_liste);
				foreach($res as $row){
					// maj CCT1 18/06/09 : ajout de '' autour de $menu["id_menu_parent"] car le type de la colonne a changé.
					$query_insert = " 
						INSERT INTO profile_menu_position (id_menu, id_profile, position, id_menu_parent)
							VALUES (
								'".$menu["id_menu"]."' , 
								'".$row["id_profile"]."',
								".$menu["position"].", 
								'".$menu["id_menu_parent"]."'
								) 
						";
					// __debug($query_insert);
					 $database_connection->executeQuery($query_insert);

				}
			}
			// Si l'insertion s'est bien passée, on retourne l'id_menu
			// $query = " SELECT last_value FROM menu_deroulant_intranet_id_menu_seq ";
			// $res = pg_query($database_connection, $query);
			// $row = pg_fetch_array($res, 0);
			return $menu["id_menu"];
		}
		else
		{
			// si l'insertion s'est mal passée on retourne false.
			return false;
		}
		//*/
	}
	
	
	
	/**
	* Fonction getTableProperties($table_name, $database_connection)
	* retourne un tbleau contenant la liste de tous les champ et leurs propriétés d'une table
	* @param : string $table_name nom de la table.
	* @paaram : $database_connection ,Ressource de connexion PostgreSQL $database_connection.
	* @return : array tableau contenant la liste des champs et leurs
	*/
	function getTableProperties($table_name, $database_connection)
	{
		if ( !empty($table_name) && !empty($table_name) )
		{
			$tab = array();
			$res = pg_query($database_connection, "SELECT * FROM $table_name LIMIT 1");
			$i = pg_num_fields($res);
			for ($j = 0; $j < $i; $j++) 
				$tab[pg_field_name($res, $j)] = pg_field_type($res, $j);			
			return $tab;
		}
		else
		{
			echo "getTableProperties function calling error : missing parameters.";
			return 0;
		}
	}


	/**
	* Fonction check_integrite_menu($id_profile, $database_connection)
	* permet de purger des menus qui ont été mal supprimmés (suite à une migration par exemple)
	* @parameter : int $id_profile identifiant du profil courant.
	* @database_connection :  Ressource de connexion PostgreSQL $database_connection.
	*/
	function check_integrite_menu($id_profile, $database_connection)
	{

		$liste_id_menu_profil = '';	// liste des id_menu du profil courant (table profile).
		$tab_profile = array();
		$tab_profile_menu_position = array();	// liste des id_menu de la tbale profile_menu_position
		$tab_menu = array();	// liste de sid_menu de menu_deroulant_intranet.

		// On récupère la liste des menus du profil (source >> tbale profile.).
		$q = " SELECT profile_to_menu FROM profile WHERE id_profile= '$id_profile' ";
		$resultat = pg_query($database_connection, $q);
		$nb = pg_num_rows($resultat);
		if ($nb == 1)
		{
			$row = pg_fetch_array($resultat, 0);
			$liste_id_menu_profil = $row['profile_to_menu'];
		}

		/*
			 1 :
			On supprime les id_menu de profile_menu_position qui n'existe pas dans
			menu_deroulant_intranet.
		*/
		$q = "
			DELETE FROM profile_menu_position
				WHERE id_profile= '$id_profile'
				AND id_menu NOT IN
				(SELECT id_menu FROM menu_deroulant_intranet)
		";
		pg_query($database_connection, $q);


		/*
			 2 :
			On supprime les id_menu se trouvant dans le profil (colonne profile_to_menu de la table profile)
			mais qui ne sont pas dans la table profile_menu_position
		*/
		$q = "
			SELECT id_menu FROM profile_menu_position
				WHERE id_profile= '$id_profile'
		";
		$resultat = pg_query($database_connection, $q);
		$nb = pg_num_rows($resultat);
		if ($nb > 0)
		{
			for ( $i = 0;$i < $nb;$i++ ){
				$row = pg_fetch_array($resultat, $i);
				$tab_profile_menu_position[] =  $row['id_menu'];
			}
		}

		$tab_profile = explode("-",$liste_id_menu_profil);

		$t = array_diff($tab_profile,$tab_profile_menu_position);
		if ( count($t) > 0 )
		{
			$q_update = "
				UPDATE profile
					SET profile_to_menu = '".implode('-',$tab_profile_menu_position)."'
					WHERE id_profile= '$id_profile'
			";
			pg_query($database_connection, $q_update);
		}

		// On supprime les doublons de profile_menu_position
		$q = "
			SELECT count(a.id_menu) as nb,a.id_menu
				FROM profile_menu_position a
				WHERE a.id_profile= '$id_profile'
				GROUP BY a.id_menu
				HAVING count(a.id_menu) > 1
		";
		$resultat = pg_query($database_connection, $q);
		$nb = pg_num_rows($resultat);
		if ($nb > 0)
		{
			for ( $i = 0;$i < $nb;$i++ ){
				$row = pg_fetch_array($resultat, $i);
				$doublons[$row['id_menu']] =  $row['id_menu'];
			}

			foreach ( $doublons as $id_menu )
			{
				$qq = " SELECT oid,* FROM profile_menu_position WHERE id_menu='$id_menu' AND id_profile= '$id_profile' ";
				$result = pg_query($database_connection, $qq);
				$nb_result = pg_num_rows($result);
				$row = pg_fetch_array($result, 0);
				$del = " DELETE FROM profile_menu_position WHERE id_menu='$id_menu' AND id_profile= '$id_profile' AND oid = ".$row['oid'];
				pg_query($database_connection, $del);
			}
		}

	}


?>
