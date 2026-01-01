<?
/*
 * Ce fichier permet d'activer / désactiver l'envoi de trapes SNMP d'une alarme
 * donnée = insertion / suppression dans la table sys_alarm_snmp_sender.
 * Le fichier est exécuté via une requête XMLHttpRequest du fichier js/setup_snmp_alarm.js
 * qui est utilisé dans les 3 types d'alarmes.
 *
 * Paramètres d'appel du fichier (en _GET)
 *  - alarm_id : identifiant de l'alarme.
 *  - alarm_type : type de l'alarme.
 *  - action : add/delete, ajout ou suppression
 * Paramètres de retour :
 *  - msg : mesage à afficher.
 *
 * Ce fichier est exécuté quand l'utilisateur clique sur le bouton d'ajout de
 * l'alarme dans l'envoi SNMP via le bouton en forme d'enveloppe sur les
 * interface qui listes les alarmes.
 *
 * - 06/02/2007 CCT1 : Création du fichier
 * - 08/02/2007 CCT1 : Quand on ajoute une trape, on supprime les enregistrements
 *                     de l'alarme dans la table sys_alarm_email_sender.
 * - 18/07/2011 OJT : DE SMS, les enregistrement dans sys_alarm_email_sender sont conservés.
 */

	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");

	// Récupération des variables.
	$product    = $_GET['product'];
	$alarm_id   = $_GET['alarm_id'];
	$alarm_type = $_GET['alarm_type'];
	$action     = $_GET['action'];

	// On se connecte à la db
	$db = Database::getConnection( $product );

	// Initialisation des variables.
	$msg = "";	// message de retour.

	if ( $action == "delete" )
	{
		$query_delete = "
			DELETE FROM sys_alarm_snmp_sender
				WHERE id_alarm='$alarm_id' AND alarm_type='$alarm_type'
			";
		$db->execute($query_delete);

		$msg = " Alarm deleted from SNMP trap ";
	}
	// Ajout de l'alarme.
	else
	{
        $query_add = "INSERT INTO sys_alarm_snmp_sender (id_alarm, alarm_type) VALUES ('$alarm_id' ,'$alarm_type')";
		$db->execute( $query_add );
		$msg = " Alarm included in SNMP trap ";
	}
	echo $msg; // le message est récupé par le fichier .js

