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
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
	/*
		Utilisé par la classe alarmDisplayCreate_v2.class.php

		> mise à jour de la table edw_alarm, champ acknowledgment

		Si tout se passe bien, on retourne 1 sinon 0.
	*/

	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	global $database_connection;

	/*
		$mode :
			- 'NA' : on valide toutes les alarmes d'une NA.
			- 'NA-ALARM' : on valide l'une des alarmes d'une na.
			- 'ALARM' : on valide tous les résultats d'une alarme donnée.
			- 'ALARM-NA' : on valide le résultat d'une alarme pour une na_value donnée.
	*/

        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
	$db_prod = Database::getConnection($_GET['product']);

	$mode			= $_GET['mode'];
	$oid				= $_GET['oid'];
	$ta				= $_GET['ta'];
	$na				= $_GET['na'];
	$ta_value			= $_GET['ta_value'];
	$na_value			= $_GET['na_value'];
	$id_alarm			= $_GET['id_alarm'];
	$alarm_type		= $_GET['alarm_type'];
	$calculation_time	= $_GET['calculation_time'];

	switch( $mode ) {

		case 'NA':
			$query = "
				UPDATE edw_alarm
				SET acknowledgement=1
				WHERE na='$na'
					AND na_value='$na_value'
					AND calculation_time='$calculation_time'
					AND alarm_type <> 'top-worst'
			";
			break;
		case 'NA-ALARM':
		case 'ALARM-NA':
			$query = "
				UPDATE edw_alarm
				SET acknowledgement=1
				WHERE oid=$oid
			";
			break;
		case 'ALARM':
			$query = "
				UPDATE edw_alarm
				SET acknowledgement=1
				WHERE id_alarm='$id_alarm'
					AND alarm_type='$alarm_type'
					AND calculation_time='$calculation_time'
			";
			break;

	}

	$db_prod->execute($query);
	echo "ok";

?>
