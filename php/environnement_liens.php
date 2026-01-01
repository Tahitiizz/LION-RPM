<?
/*
 *	@cb51000@
 *	23/06/2010 - Copyright Astellia
 *	Composant de base version cb_5.1.0.00
 *
 *  - 30/07/2010 NSE bz 15423 : déclaration des constantes au lieu de sys global parameters pour la documentation
 *  - 03/08/2010 OJT : Suppression de la constante PHP_DIR pour éviter E_NOTICE (plus utilisée par T&A)
 */
?><?
/*
*	@cb41000@
*
*	12/11/2008 - Copyright Acurio
*
*	Composant de base version cb_4.1.0.00
* 
*	- 10/12/2008 CCT1 : ajout de la fonction __autoload, charge directement une classe de Model
* 	- 12/11/2008 BBX : ajout de l'appel à la classe de connexion à la BDD

	- 29/01/2009, modif. benoit : suppression de l'inclusion de "php/variable_global.php"
* 
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
* 
* 	- 28/12/2007 Gwénaël : restructuration du fichier dans l'optique d'une meilleure lisibilité et maintenance
* 
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
<?php
include_once dirname( __FILE__ ).'/xenv.inc';

// Depuis PHP 5.1.0 (lorsque les fonctions date/heure ont été écrites), chaque appel à une fonction date/heure
// génère une E_NOTICE si le décalage horaire n'est pas valide et/ou un message E_WARNING
// si vous utilisez des configurations système ou la variable d'environnement TZ.
// 24/06/2010 BBX : définition du timezone PHP s'il est définit dans xenv. BZ 16269
if(!empty($timezonesvr))
    date_default_timezone_set($timezonesvr);

define('NIVEAU_0', $niveau0);
define('REP_PHYSIQUE_NIVEAU_0', $repertoire_physique_niveau0);

// 17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php
// 03/08/2010 OJT : Suppression de la constante PHP_DIR pour éviter E_NOTICE (plus utilisée par T&A)
define('PSQL_DIR', $psqldir);

// 30/06/2010 NSE bz 15423 : Doc admin et doc User
define('DOC_USER', '/doc/Trending&Aggregation_UserManual.pdf');
define('DOC_ADMIN', '/doc/Trending&Aggregation_AdminManual.pdf');

include_once dirname( __FILE__ ).'/edw_function.php';

// Database
include_once(REP_PHYSIQUE_NIVEAU_0.'class/DataBaseConnection.class.php');
// 08/03/2010 BBX : ajout de la classe qui permet de gérer les instances de DataBaseConnection
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Database.class.php');

// Data providers
include_once(REP_PHYSIQUE_NIVEAU_0.'lib/data/db/AlarmDbProvider.class.php');

include_once dirname( __FILE__ ).'/xbdd.php';

// Modules
include_once (REP_PHYSIQUE_NIVEAU_0.'/modules/conf.modules.inc');

// 10/12/2008 CCT1 : ajout de la fonction __autoload, charge directement une classe de Model
// Si la fonction n'existe pas, on la charge (pose problème quand il y a plusieurs fichiers dans la même page qui inclus environnement_liens
if ( !function_exists('__autoload') )
{
	/**
	* Permet de charger automatiquement une classe du répertoire models :
	* quand une classe est appelée et qu'elle n'existe pas, son fichier est automatiquement chargé
	*
	* @param string nom de la classe
	*/
	function __autoload($class_name) 
	{
		// 30/07/2009 : gestion de la classe Date pour que celle-ci soit accessible de partout
		switch($class_name)
		{
			// Classe Date
			case 'Date':
				include_once REP_PHYSIQUE_NIVEAU_0.'class/'.$class_name . '.class.php';
			break;
			// Par défaut, appel des modèles
			default:
				include_once REP_PHYSIQUE_NIVEAU_0.'models/'.$class_name . '.class.php';
			break;
		}
	}
}

// 16/05/2011 BBX
// Checking partitioning process and locking HTTP application if process running
// 28/06/2011 BBX
// Ajout d'un controle pour ne rediriger que si le partitioning en cours concerne ce produit
// BZ 22820
include_once REP_PHYSIQUE_NIVEAU_0.'partitioning/class/PartitioningActivation.class.php';
if(PartitioningActivation::checkPartitioningFile() && isset($_SERVER['HTTP_USER_AGENT']))
{
    // Partitioning information
    $databaseMaster         = Database::getConnection();
    $partitioningActivation = new PartitioningActivation($databaseMaster);
    $productInfos           = $partitioningActivation->parsePartitioningFile();

    // If current partitioning concerns this product, let's redirect
    if($partitioningActivation->isPartitioningConcerningMe($productInfos))
        header('Location: '.NIVEAU_0.'partitioning/lock.php');
}
?>