<?
	/*
		Création 04/02/2008 christophe.
		Permet d'effectuer des actions sur les PAUTO :
		- actions du bouton 'share' : permet de partager/ ne plus partager l'élément courant avec les autre utilisateurs. > $action==share
		- actions du bouton copy : seulement affiché dans le GTM et dahsboard builder, permet de créer un nouvel élément à partir
		de l'élément courant. > $action==copy
	*/
	/*
		maj 12:19 16/07/2008 - maxime : Correction du bug 7135 - Impossible de copier tous les gtm ( valeur de gis_based_on  nulle en base )  
	*/
	
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");

	global $database_connection;
	
	// On récupère les infos _GET.
	$action = 	$_GET['action'];
	$id_page = 	$_GET['id_page'];
	$id_pauto = $_GET['id_pauto'];
	$family = 	$_GET['family'];
	
	// En fonction de la valeur de $action, on effectue différents traitements.
	switch ( $action )
	{
		case 'share' :
			$q = "
				UPDATE sys_pauto_page_name
					SET share_it = (CASE WHEN share_it=1 THEN 0 ELSE 1 END)
					WHERE id_page=$id_page
			";
			pg_query($database_connection,$q);
			break;
		case 'copy' :
			switch ($id_pauto)
			{
				case 'gtm' :
					$id_page = copyGTM($id_page, $id_pauto, $_SESSION['id_user'], $database_connection, $family);
					break;
				case 'page' :	
					$id_page = copyDashboard($id_page, $id_pauto, $_SESSION['id_user'], $database_connection, $family);
					break;
			}
			break;
		default:
	}
	
	/**
	*  Permet de copier un GTM . Par défaut le nom du nouvel élément est copy of + nom élément copié
	* @param $id_page identifiant de l'élément
	* @param $id_pauto type de l'élément gtm pour les graphes et page pour les dashboard
	* @param $id_user identifiant de l'utilisateur
	* @param $database_connection handle de connection à la BDD
	* @param $family famille
	* @return int $new_id_page identifiant du nouveau GTM
	*/
	function copyGTM ($id_page, $id_pauto, $id_user, $database_connection, $family)
	{
		/*
			Il faut copier les enregistrements sur les table suivantes :
			graph_information, graph_data, sys_pauto_page_name, sys_pauto_config.
		*/
		// On créé un internal_id.
		$uniq_id = generate_acurio_uniq_id("gtm");
		save_acurio_uniq_id($uniq_id, "gtm");
		/*
			Insertion dans sys_pauto_page_name.
		*/
		$q_insert = "
			INSERT INTO sys_pauto_page_name	
				(id_page,page_name,droit,page_type,family,internal_id,id_user)
				VALUES
				(
					(SELECT MAX(id_page)+1 FROM sys_pauto_page_name),
					(SELECT 'copy of '||page_name FROM sys_pauto_page_name WHERE id_page=$id_page),
					'".getClientType($id_user)."',
					'$id_pauto',
					'$family',
					'$uniq_id',
					$id_user
				)
		";
		__debug($q_insert,"q_insert");
		
		pg_query($database_connection,$q_insert);
		
		// On récupère l'id_page du nouvel élément.
		$q = " SELECT id_page FROM sys_pauto_page_name WHERE internal_id='$uniq_id' ";
		$result = pg_query($database_connection,$q);
		$result_array = pg_fetch_array($result, 0);
		$new_id_page = $result_array['id_page'];
		
		/*
			Insertions dans la table sys_pauto_config.
		*/
		// On liste tous les éléments de la table.
		$q_list = " SELECT * FROM sys_pauto_config WHERE id_page=$id_page ";
		$result = pg_query($database_connection,$q_list);
		$result_nb = pg_num_rows($result);
		for ($k = 0; $k < $result_nb; $k++)
		{
			$result_array = pg_fetch_array($result, $k);
			$q_insert = "
				INSERT INTO sys_pauto_config 
					(id,id_page,id_elem,class_object,frame_position,id_data,ligne,colonne)
					VALUES
					(
					(SELECT MAX(id)+1 FROM sys_pauto_config),
					$new_id_page,
					'".$result_array['id_elem']."',
					'".$result_array['class_object']."',
					'".$result_array['frame_position']."',
					".$result_array['id_data'].",
					".$result_array['ligne'].",
					0
					)
			";
			pg_query($database_connection,$q_insert);
		}
		
		/*
			Insertions dans la table graph_data	
		*/
		// On récupère l'id_data qui correspond au champ gis_based_on de la table graph_information ainsi que l'id_data orderby.
		$q = " SELECT gis_based_on,orderby FROM graph_information WHERE id_page=$id_page ";
		$result_q = pg_query($database_connection,$q);
		$result_array_q = pg_fetch_array($result_q, 0);
		$gis_based_on = $result_array_q['gis_based_on'];
		$orderby = 		$result_array_q['orderby'];
		
		$tab_id_data = array(); // stocke tous les id_data insérés (permet de mettre-à-jour la colonne graph_data_list)
		
		// On liste les éléments de la table.
		$q_list = " 
			SELECT * FROM graph_data 
				WHERE id_data IN (SELECT id_data FROM sys_pauto_config WHERE id_page=$id_page) 
			";
		$result = pg_query($database_connection,$q_list);
		$result_nb = pg_num_rows($result);
		for ($k = 0; $k < $result_nb; $k++)
		{
			$result_array = pg_fetch_array($result, $k);
			$q_insert = "
				INSERT INTO graph_data 
					(data_legend,position_ordonnee,display_type,line_design,color,filled_color,data_value,
						id_data_value,data_type,is_configure,internal_id)
					VALUES
					(
					'".$result_array['data_legend']."',
					'".$result_array['position_ordonnee']."',
					'".$result_array['display_type']."',
					'".$result_array['line_design']."',
					'".$result_array['color']."',
					'".$result_array['filled_color']."',
					'".$result_array['data_value']."',
					".$result_array['id_data_value'].",
					'".$result_array['data_type']."',
					".$result_array['is_configure'].",
					'".$result_array['internal_id']."'
					)
			";
			pg_query($database_connection,$q_insert);
			
			// On récupére le dernier id_data inséré et on le stocke.
			$q_last_id = " SELECT currval('graph_data_id_data_seq') ";
			$result_q_last_id = pg_query($database_connection,$q_last_id);
			$result_array_q_last_id = pg_fetch_array($result_q_last_id, 0);
			$tab_id_data[] = $result_array_q_last_id[0];
			
			// On met-à-jour le champ id_data de la table sys_pauto_config car ce n'est pas le bon.
			$q_update = " UPDATE sys_pauto_config SET id_data=".$result_array_q_last_id[0].
				" WHERE id_data=".$result_array['id_data']. " AND id_page=$new_id_page ";
			pg_query($database_connection,$q_update);
			
			// Si l'id_data courant = $gis_based_on, c'est que l'on doit remplacer la variable $gis_based_on par sa nouvelle valeur
			
			if ( $gis_based_on == $result_array['id_data'] )
				$gis_based_on = $result_array_q_last_id[0];
			
			// Idem pour la colonne orderby de graph_information
			if ( $orderby == $result_array['id_data'] )
				$orderby = $result_array_q_last_id[0];
			
		}
		
		/*
			Insertion dans la table graph_information
		*/
		$graph_data_list = ( empty($tab_id_data) ) ? '' : implode(',',$tab_id_data) ;
		$q_insert = "
			INSERT INTO graph_information	
			(graph_name ,ordonnee_left_name ,ordonnee_right_name ,graph_height ,graph_width ,position_legende ,
				orderby ,positiongraph ,graph_title ,asc_desc ,graph_order ,object_type ,gis ,is_configure ,
				gt_categories ,gis_data_type,scale ,troubleshooting ,definition)
				
			SELECT 'copy of '||graph_name ,ordonnee_left_name ,ordonnee_right_name ,graph_height ,graph_width ,position_legende ,
				orderby ,positiongraph ,'copy of '||graph_title ,asc_desc ,graph_order ,object_type ,gis ,is_configure ,
				gt_categories ,gis_data_type,scale ,troubleshooting ,definition
			FROM graph_information WHERE id_page=$id_page
		";
		pg_query($database_connection,$q_insert);
		
		// On récupère le dernier id_graph.
		$q_last_id = " SELECT currval('graph_information_id_graph_seq') ";
		$result_q_last_id = pg_query($database_connection,$q_last_id);
		$result_array_q_last_id = pg_fetch_array($result_q_last_id, 0);
		$last_id_graph = $result_array_q_last_id[0];
		
		// maj 12:19 16/07/2008 - maxime : Correction du bug 7135 - Impossible de copier tous les gtm ( valeur de gis_based_on  nulle en base )  
		$_gis_based_on = ($gis_based_on !==null and $gis_based_on !== "")? $gis_based_on : 0;
		
		$q_update = "
			UPDATE graph_information 
				SET id_page=$new_id_page,
					gis_based_on=$_gis_based_on,
					graph_data_list='$graph_data_list'
				WHERE id_graph=$last_id_graph
		";
		__debug($q_update,"q_update");
		
		pg_query($database_connection,$q_update);
		return $new_id_page;
	}
	
	/**
	* Permet de copier un copyDashboard . Par défaut le nom du nouvel élément est copy of + nom élément copié.
	* Les dashboard copié ne sont pas en ligne par défaut.
	* @param $id_page identifiant de l'élément
	* @param $id_pauto type de l'élément gtm pour les graphes et page pour les dashboard
	* @param $id_user identifiant de l'utilisateur
	* @param $database_connection handle de connection à la BDD
	* @param $family famille
	* @return int $new_id_page identifiant du nouveau GTM
	*/
	function copyDashboard ($id_page, $id_pauto, $id_user, $database_connection, $family)
	{
		/*
			Il faut copier les enregistrements sur les table suivantes :
			sys_pauto_page_name, sys_pauto_config,
		*/
		// On créé un internal_id.
		$uniq_id = generate_acurio_uniq_id("dashboard");
		save_acurio_uniq_id($uniq_id, "dashboard");
		/*
			Insertion dans sys_pauto_page_name.
		*/
		$q_insert = "
			INSERT INTO sys_pauto_page_name	
				(id_page,page_name,droit,page_type,family,internal_id,id_user,online,id_menu,navigation_on_off,page_mode)
				VALUES
				(
					(SELECT MAX(id_page)+1 FROM sys_pauto_page_name),
					(SELECT 'copy of '||page_name FROM sys_pauto_page_name WHERE id_page=$id_page),
					'".getClientType($id_user)."',
					'$id_pauto',
					'$family',
					'$uniq_id',
					$id_user,0,0,1,
					(SELECT page_mode FROM sys_pauto_page_name WHERE id_page=$id_page)
				)
		";
		pg_query($database_connection,$q_insert);
		
		// On récupère l'id_page du nouvel élément.
		$q = " SELECT id_page FROM sys_pauto_page_name WHERE internal_id='$uniq_id' ";
		$result = pg_query($database_connection,$q);
		$result_array = pg_fetch_array($result, 0);
		$new_id_page = $result_array['id_page'];
		
		/*
			Insertions dans la table sys_pauto_config.
		*/
		// On liste tous les éléments de la table.
		$q_list = " SELECT * FROM sys_pauto_config WHERE id_page=$id_page ";
		$result = pg_query($database_connection,$q_list);
		$result_nb = pg_num_rows($result);
		for ($k = 0; $k < $result_nb; $k++)
		{
			$result_array = pg_fetch_array($result, $k);
			$q_insert = "
				INSERT INTO sys_pauto_config 
					(id,id_page,id_elem,class_object,frame_position,ligne,colonne)
					VALUES
					(
					(SELECT MAX(id)+1 FROM sys_pauto_config),
					$new_id_page,
					'".$result_array['id_elem']."',
					'".$result_array['class_object']."',
					'".$result_array['frame_position']."',
					".$result_array['ligne'].",
					0
					)
			";
			pg_query($database_connection,$q_insert);
		}
		
		/*
			Insertion dans la table sys_definition_theme
		*/
		$q_list = " SELECT * FROM sys_definition_theme WHERE id_theme=$id_page ";
		$result = pg_query($database_connection,$q_list);
		$result_nb = pg_num_rows($result);
		if ( $result_nb > 0 )
		{
			$result_array = pg_fetch_array($result, 0);
			$q_insert = "
				INSERT INTO sys_definition_theme 
					(id_theme, theme, type, id_sort_by, asc_desc, internal_id)
					VALUES
					(
					$new_id_page,
					'',
					'".$result_array['type']."',
					".$result_array['id_sort_by'].",
					'".$result_array['asc_desc']."',
					'".$result_array['internal_id']."'
					)
			";
			pg_query($database_connection,$q_insert);
		
			__debug($q_update,"q_insert2");
		}
		
			
		return $new_id_page;
	}

	
	header("location:pageframe.php?action=display&id_page=".$id_page."&id_pauto=".$_GET["id_pauto"]."&family=".$family);
?>