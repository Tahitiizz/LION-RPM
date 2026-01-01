<?php
/*
	27/11/2008 GHX
		- Création du fichier
 *
 * 21/04/2011 NSE DE Non unique Labels : on initialise l'IdProduct pour lobjet Topology
 * 16/05/2011 NSE DE Topology characters replacement : appel de la fonction
 * 07/05/2013 : ajout du caractère de séparation tabulation
*/
?>
<?php
/**
 *	Ce fichier permet de chargé un fichier de topologie où le nom du fichier est passé en paramètre du script ainsi que le délimiteur
*	
*	Using : 
*		php -q admintool_update_topology_load_file.php "nom_fichier_topo.csv" "delimiteur"
*	
*	Pour savoir s'il y a des erreurs ou non, il suffit de regardé la première ligne affichée une fois l'upload fini
*	Si la première ligne commence par
*		Cas 1 . -ERROR- c'est qu'il y a des erreurs. Chaques erreurs sont sur une lignes différents (erreurs générées par le check topo)
*		Cas 2. -OK- c'est que l'upload du fichier est ok
*		Cas 3. Si aucun dès deux premiers cas alors c'est qu'il y a eu une erreur durant l'upload (erreur sql, commande unix .. ???)
 */

include(dirname(__FILE__).'/../../../../php/environnement_liens.php');

include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/topology/TopologyLib.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/topology/Topology.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/topology/TopologyChanges.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/topology/TopologyCheck.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/topology/TopologyCorrect.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/topology/TopologyAddElements.class.php');

/*
 * Récupère les paramètres passés au script
 */
$file_name = $argv['1'];
$delimiter = $argv['2'];
$idUser    = $argv['3'];

// maj 06/07/2009 MPR : Correction du bug 10386 - Ajout du product pour se connecter à la bonne db
$product   = $argv['4'];

// maj 06/07/2009 MPR : Correction du bug 10386 - Ajout du product pour se connecter à la bonne db
$module = get_sys_global_parameters('module','',$product);

ob_start();

// 07/05/2013 : ajout du caractère de séparation tabulation
if($delimiter=='tab'){
    // on remplace toutes les tabulations du fichier par un ";"
    exec('sed -i -e "s/\\t/;/g" '.REP_PHYSIQUE_NIVEAU_0.'upload/'.$file_name);
    // le délimiteur est maintenant le ";"
    $delimiter=';';
}

// print_r("*$repertoire_physique_niveau_0*");
/*
 * Lance le module de topologie
 */
// 06/04/2011 NSE bz 21719 : passage du paramètre pour initialiser le set_time_limit à 1200
$topo = new Topology(1200);

// maj 06/07/2009 - MPR : On remplace $repertoire_physique_niveau_0 par REP_PHYSIQUE_NIVEAU_0
$topo->setRepNiveau0(REP_PHYSIQUE_NIVEAU_0);
$topo->setDbConnection($database_connection);
$topo->setDelimiter($delimiter);
$topo->setProduct($module);
// 21/04/2011 NSE DE Non unique Labels : 
$topo->setIdProduct($product); 
$topo->setMode("manuel");
$topo->setFile($file_name);
$topo->setIdUser($idUser);
$topo->init();
// 16/05/2011 NSE DE Topology characters replacement
$errorMessage = $topo->charactersReplacement();
// on ne poursuit pas si une erreur a été rencontrée
if(empty($errorMessage))
    $topo->load();

/*
 * Affichage
 */
$errors = $topo->getErrors();
if ( is_array($errors) && count($errors) > 0  ) // Affichage des messages d'erreurs
{
	echo '-ERROR-'."\n";
	foreach( $errors as $error )
	{
		echo $error."\n";
	}
}
else // Affichage des changements dans la topologie
{
	$changes = $topo->getChanges();
	echo '-OK-'."\n";
	if ( count($changes) > 0 )
	{
		$keys = array_keys($changes[0]);
		$maxResult = get_sys_global_parameters('max_nb_row_upload_topology');
		$nbLines = (count($changes) > $maxResult ? $maxResult : count($changes) );
		for ( $i = 0; $i < $nbLines; $i++ )
		{
			$change = $changes[$i];
			echo implode(';',$change);
			echo "\n";
		}
	}
}

// Encodage UTF-8 pour éviter des problèmes accents quand on lance l'exécution du script via la commande exec()
$buffer = ob_get_contents();
ob_end_clean();
echo utf8_encode($buffer);
?>