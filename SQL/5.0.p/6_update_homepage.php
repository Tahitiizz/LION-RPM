<?php
/*
*	24/02/2010 nse 14134
*		- update de la table users de façon à ne pas modifier les pages d'accueil des utilisateurs à l'application du patch
*
*/
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
//include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_menu.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");

// si une homepage est installée
if(file_exists(REP_PHYSIQUE_NIVEAU_0.'homepage/index.php') || file_exists(REP_PHYSIQUE_NIVEAU_0.'homepage/index.html')) {
	$query = "SELECT item_value
				FROM sys_versioning 
				WHERE item='cb_version'
				GROUP BY item_value, date
				HAVING ( ( SPLIT_PART(item_value,'.',3)::int = 2 AND SPLIT_PART(item_value,'.',4)::int >10) OR (SPLIT_PART(item_value,'.',3)::int >2) )
				ORDER BY date DESC
				LIMIT 1";
	$result = pg_query($database_connection,$query);
	// si le patch 5.0.2.11 ou supérieur n'a pas été appliqué
	if(pg_num_rows($result) == 0)
	{
		// on force l'affichage de la page d'accueil installée
		$query = "UPDATE users SET homepage=-1";
		$result = pg_query($database_connection,$query);
		echo 'on force l\'affichage de la page d\'accueil installée';
	}
	else
		echo 'patch déjà installé';
}
?>