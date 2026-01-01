<?php

/**
 *
	10/03/2008 - Benoit : definition de ce script de recherche du nombre de valeurs de na dans une table de reference en correction du bug 2834
 *
**/

	session_start();

	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");

	if (isset($_GET['na']) && isset($_GET['object_ref_table'])) {

		// 13/03/2008 - Modif. benoit : on stocke les précédentes recherches dans un tableau de sessions afin de pouvoir les restituer plus rapidement lors du prochain appel

		$na					= $_GET['na'];
		$object_ref_table	= $_GET['object_ref_table'];

		if (!isset($_SESSION[$object_ref_table][$na])) {

			// 13/03/2008 - Modif. benoit : modification de la requete pour accélerer la recherche du nombre d'elements

			$sql =	 " SELECT COUNT(".$_GET['na'].") AS nb_na FROM (SELECT ".$_GET['na']." FROM ".$_GET['object_ref_table']
					." WHERE ".$_GET['na']." IS NOT NULL AND ".$_GET['na']." != '' GROUP BY ".$_GET['na'].") table_count";

			$req = pg_query($database_connection, $sql);
			$row = pg_fetch_array($req);

			$_SESSION[$object_ref_table][$na] = $nb_na = $row['nb_na'];
		}
		else 
		{
			$nb_na = $_SESSION[$object_ref_table][$na];
		}

		echo "&nbsp;on ".$nb_na." element".(($nb_na > 1) ? "s" : "");
	}
	else 
	{
		echo "";	
	}
?>