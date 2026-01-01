<?
/*
*	@cb50000@
*
*	20/05/2009 - Copyright Acurio
*
*	Composant de base version cb_5.0.0.00
*	- 16:18 20/05/2009 SCT : BZ 9735 => [REC][T&A CB 5.0][TRACELOG]: "Data Compute" = "gsm"
*	16:00 25/06/2009 SCT => modification de l'ajout de la date dans le tracelog lors du compute Day
*/
?>
<?php
include_once dirname(__FILE__)."/../php/environnement_liens.php";

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$database = Database::getConnection();

// maj CCT1 25/02/09 : mise en commentaire de environnement_datawarehouse.php car ce fichier n'est plus utilisé.
//include_once($repertoire_physique_niveau0 . "php/environnement_datawarehouse.php");
// ajout de l'appel à la classe de date
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/Date.class.php');

// 16:59 07/07/2009 SCT : Bug 9735 => modification des messages
$system_name = get_sys_global_parameters('system_name');
// maj  27/05/2008 - maxime : On vérifie quel type de compute est lancé et en fonction de celui-ci on affiche un message dans le Tracelog
// 09/06/2011 BBX -PARTITIONING-
// Correction des casts
$query = '
	SELECT 
		master_name 
	FROM 
		sys_definition_master 
	WHERE 
		master_id::text =
			(
				SELECT 
					process 
				FROM 
					sys_process_encours 
				WHERE 
					encours = 1 
					AND done = 0 
				ORDER BY 
					oid DESC 
				LIMIT 1
			)';
$resultat = $database->getRow($query);
if(count($resultat) > 0)
{
	$masterName = $resultat['master_name'];
    // Attention il faut absolument que le nom du MAster Hourly commporte "Hourly"
    if(preg_match("/Hourly/", $masterName))
	{
        $hour_to_compute = get_sys_global_parameters("hour_to_compute");
		$message = __T('A_COMPUTE_HOURLY_STOP_INFO', substr($hour_to_compute, 6, 2), substr($hour_to_compute, 4, 2), substr($hour_to_compute, 0, 4), substr($hour_to_compute, -2).':00');
	}
	else
	{
		$edw_day = Date::getDayFromDatabaseParameters();
		$message = __T('A_COMPUTE_STOP_INFO', substr($edw_day, -2), substr($edw_day, 4, 2), substr($edw_day, 0, 4), '');
	}
}


// 15/04/2008 - Modif. benoit : correction du bug 6316. Remise dans le code de la condition ci-dessous permettant de reswitcher le mode du compute. Condition écrasée lors de la précédente livraison

if(get_sys_global_parameters('compute_switch') == "hourly")
{
	// RAZ de 'compute_switch'
	$sql = "UPDATE sys_global_parameters SET value = NULL WHERE parameters = 'compute_switch'";
	$database->execute($sql);

	// Reinitialistion du 'compute_mode'
	$sql = "UPDATE sys_global_parameters SET value = 'hourly' WHERE parameters = 'compute_mode'";
	$database->execute($sql);
}

// 16:18 20/05/2009 SCT : BZ 9735 => [REC][T&A CB 5.0][TRACELOG]: "Data Compute" = "gsm"
//$system_module=get_sys_global_parameters("module");
$system_module = __T('A_TRACELOG_MODULE_LABEL_COMPUTE');

sys_log_ast('Info', $system_name, $system_module, $message, 'support_1', '');
?>
