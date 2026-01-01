<?
/*
*	@gsm3.0.0.00@
*
*	10:20 04/08/2009 SCT
*		- mise à niveau sur CB 5.0
*		- utilisation de la classe de connexion à la bdd
*	10:20 04/08/2009 SCT : amélioration de l'affichage du démon
*/
?>
<?php
/**
 * Fichier de lancement des parsers de chaque fichier GSM
 * 
 * 09-10-2007 SCT : Mise à niveau Iso-fonctionnelle du parseur pour le composant de base 3.0
 *
 * 16-11-2007 SCT : ajout de l'instanciation de l'objet Resellers
 * 26-11-2007 SCT : ajout de la vérification de la présence de fichiers de référence (R0x) pour l'analyse de la topo. Dans le cas du fichier Resellers, l'analyse de la topo doit rester active (les fichiers Resellers peuvent passer avant les fichiers de données)
 * 
 * @package Parser_GSM
 * @author Guillaume Houssay 
 * @version 2.0.1.01
 */

 include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");

// Connexion à la base de données locale
$database = Database::getConnection();

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));
$parser_name = strtoupper($module);
$system_name = get_sys_global_parameters("system_name");

// include des fichiers nécessaires
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/load_data_generic.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/load_data_def.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/Date.class.php");

// includes des fichiers de Topology
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyLib.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/Topology.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCheck.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCorrect.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyAddElements.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyChanges.class.php");


// modif 12/09/2007 Gwen
	// Ajout d'une condition ORDER BY pour le chargement des fichiers de topo
	// On ne charge uniquement que pour l'heure la plus ancienne

$load_topo = true;
$active_log = false;

$query = "select distinct hour from sys_flat_file_uploaded_list WHERE hour IS NOT NULL ORDER BY hour DESC";
$result = $database->execute($query);
$nombre_hour = $database->getNumRows();
if($nombre_hour > 0)
{
	while($row = $database->getQueryResults($result,1))
	{
		$start = date(time());

                $load = new load_data_def( $row["hour"] );

                // ajout 12/09/2007 Gwen
                // instanciation de l'objet pour les données de topo uniquement pour l'heure la plus ancienne
                // lance la fonction pour l'ensemble des fichiers GSM. fonction présente dans la classe "chapeau";
                $load->clean_flat_file_uploaded_list();
                
		$end = date(time());
		$duration = $end - $start;
		// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
		displayInDemon('Treatment '.$row['hour'].' - duration = '.$duration.' seconds<br />'."\n");
        } 
}
else
{
	// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
	print displayInDemon('No uploaded flat file<br>'."\n", 'alert');
	$message = 'No '.$parser_name.' files to be managed';
	sys_log_ast("Info", $system_name, __T('A_TRACELOG_MODULE_LABEL_COLLECT'), $message, "support_1", "");
} 

?>
