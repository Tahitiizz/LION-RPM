<?php
/**
 * Ce script est appelé via AJAX et permet de savoir si on des bar cumulées sur les ordonnées de gauche et droite en même temps.
 * Si oui on retourne "nok" si c'est pas le cas on retourne "ok"
 *
 *	05/08/2009 GHX
 *		- Création du script pour corriger le BZ 6038
 *
 * @author GHX
 * @version CB 5.0.0.04
 * @since CB 5.0.0.04
 */

$intranet_top_no_echo = true;
include_once('common.inc.php');

$id_page = $_POST['id_page'];

// Calcule le nombre de bar cumulées pour chaque axes des ordonnées
$query = "
	SELECT
		SUM (CASE WHEN position_ordonnee = 'left' THEN 1 ELSE 0 END) AS left,
		SUM (CASE WHEN position_ordonnee = 'right' THEN 1 ELSE 0 END) AS right
	FROM
		sys_pauto_config AS spc,
		graph_data AS gd
	WHERE 
		spc.id = gd.id_data
		AND gd.display_type = 'cumulatedbar'
		AND spc.id_page = '{$id_page}'
	";
	
$result = $db->getRow($query);

// Si on a un nombre supérieur à zéro pour les 2 axes ce N'est PAS BON
if ( $result['left'] > 0 &&  $result['right'] > 0 )
{
	echo "nok";
}
else
{
	echo "ok";
}
?>