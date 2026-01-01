<?
/*
* Ce script effectue le calcul des alarmes itératives
*/
?>
<?
/*
* 
*	@cb4100@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	- maj 25/03/2009 : Répercution du bug 8006 : Calcul des alarmes itératives trop long
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
	- maj 17/01/2008, benoit : ajout d'un tableau indiquant le type reel des alarmes tel qu'il est définit dans la table 'sys_definition_alarm_exclusion' et passage de ce tableau en parametres des fonctions 'alarmsNoIteratives()' et 'alarmsIteratives()'
	- maj 17/01/2008, benoit : on vérifie que pour le calcul de l'alarme la valeur du '$time_to_calculate' ne fait pas partie des periodes d'exclusion
	- maj 17/01/2008, benoit : définition de la sous-requete qui limite les valeurs de TA à celles valides (cad non exclues)
	- maj 17/01/2008, benoit : ajout de la selection de la colonne 'discontinuous' pour les alarmes dynamiques dans la requete

	- maj 26/02/2008, benoit : dans la fonction 'alarmsIteratives()', on peut maintenant avoir plusieurs ta_value pour une ta donnée (cas de la   liste d'heures pour le compute booster). On boucle donc sur les différentes valeurs de ta de '$time_to_calculate'
	
	- maj 27/02/2008, benoit : utilisation de la fonction 'displayInDemon()' pour afficher les titres dans le demon

	- maj 03/03/2008 GHX : Ajout d'une condition sur la TA pour les alarmes itératives.

	- maj 18/03/2008, benoit : ajout de la condition "on_off=1" dans la sous-requete de sélection des alarmes
	- maj 18/03/2008, benoit : ajout de la condition "on_off=1" dans la requete de sélection des alarmes

	- maj 22/04/2008, benoit : correction du bug 6461
*/
?>
<?php
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
// include_once($repertoire_physique_niveau0 . "php/environnement_datawarehouse.php"); //necessaire car l'appel à get_time_to_calculate nécessite la variable edw_day contenue dans ce fichier
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_alarm.php");

// maj 09/03/2010 - MPR : Correction du BZ 14257
$id_prod = "";
$db = Database::getConnection($id_prod); //new DataBaseConnection();


$debug = false;
if ($debug) echo '<link rel="stylesheet" href="../css/global_interface.css" type="text/css">';

set_time_limit(3600);

$types_alarmes = array ('static'=> 'static','dynamic'=> 'dyn_alarm','top_worst'	=> 'top-worst');

// 17/01/2008 - Modif. benoit : ajout d'un tableau indiquant le type reel des alarmes tel qu'il est définit dans la table 'sys_definition_alarm_exclusion' et passage de ce tableau en parametres des fonctions 'alarmsNoIteratives()' et 'alarmsIteratives()'
$real_types = array('static'=>'alarm_static','dyn_alarm'=>'alarm_dynamic','top-worst'=>'alarm_top_worst');

// 15/06/2010 BBX : récupération de l'offset day pour le calcul itératif. BZ 16058
$offset_day = get_sys_global_parameters('offset_day');
$time_to_calculate = get_time_to_calculate($offset_day);


foreach ( $types_alarmes as $table => $type ) {
	
	// 27/02/2008 - Modif. benoit : utilisation de la fonction 'displayInDemon()' pour afficher les titres dans le demon
	displayInDemon("Calcul des alarmes itératives de type : ".$type, 'title');
	
	// 16:35 25/03/2009 - MPR : Répercution du bug 8006
	// BZ 8006 : [SUP][v4.0][7195][Tigo Sri Lanka][Alarm] : Script de calcul d'alarmes itératives est trop long
	// alarmsNoIteratives($table, $type, $real_types[$type]);
	alarmsIteratives($table, $type, $real_types[$type], $time_to_calculate);
}

/*****************************************************************************************************************/

/**
 * Met le champ visible à 1 pour les alarmes non itératives
 *
 * @global $database_connection
 * @param string $table_alarme : nom le la table
 * @param string $type_alarme : type de l'alarme
 * @param string $real_type : type réel de l'alarme (tel qu'utilisé dans 'sys_definition_alarm_exclusion')	
 */
function alarmsNoIteratives ( $table_alarme, $type_alarme, $real_type) {
	global $db;
	
	// 22/04/2008 - Modif. benoit : correction du bug 6461. Remise en forme de la requete pour la maj des alarmes top-worst non itératives. La requete était erronée car elle mettait à jour des alarmes itératives (celles ayant des champs additionnels car celles-ci ont des lignes de définition supplémentaires où la période et le nombre d'itérations sont nulles)
	if ($table_alarme == 'top-worst') {
		$query_update = "
			UPDATE edw_alarm
			SET visible = 1
			FROM (
				SELECT DISTINCT alarm_id
				FROM sys_definition_alarm_top_worst
				WHERE alarm_id NOT IN
					(	SELECT DISTINCT alarm_id
						FROM sys_definition_alarm_top_worst
						WHERE nb_iteration != 1
							AND nb_iteration IS NOT NULL
							AND period != 1
							AND period IS NOT NULL
							AND on_off = 1
					)
					AND on_off = 1
				) AS t0
			WHERE alarm_type = '$type_alarme'
			AND edw_alarm.id_alarm = t0.alarm_id ";
	} else {
		$query_update = "
			UPDATE edw_alarm
			SET visible = 1
			FROM (
				SELECT DISTINCT alarm_id, critical_level
				FROM sys_definition_alarm_$table_alarme
				WHERE (nb_iteration = 1)
					OR (nb_iteration IS NULL AND period IS NULL)
					AND on_off = 1
				) AS t0
			WHERE alarm_type = '$type_alarme'
				AND edw_alarm.id_alarm = t0.alarm_id
				AND edw_alarm.critical_level = t0.critical_level ";
	}
	__debug($query_update, '<br>alarmsNoIteratives ('.$type_alarme.')');
	
	$db->execute($query_update);
	echo '<br /> - Alarmes non itératives - nombre de lignes mise à jour : '.$db->getAffectedRows();
} // End function alarmsNoIteratives

/*****************************************************************************************************************/

/**
 * Met le champ visible à 1 pour les alarmes itératives si les critères de déclenchement sont corrects.
 *
 * @global $database_connection
 * @param string $table_alarme : nom le la table
 * @param string $type_alarm : type de l'alarme à calculer : static - dynamic - top_worst
 * @param string $real_type : type réel de l'alarme (tel qu'utilisé dans 'sys_definition_alarm_exclusion')	
 * @param int $ta_value : valeur de la time agregation du compute
 */
function alarmsIteratives ( $table_alarme, $type_alarme, $real_type, $time_to_calculate ) {
	global $db;
	
	__debug('', "<br>alarmsIteratives ( $table_alarme )");
	// Récupère la liste des alarmes itératives
	$alarms = getAlarmsIteratives($table_alarme, array_keys($time_to_calculate));
	
	// si aucune alarme on quitte la fonction
	if ( $alarms === false ) {
		echo 'Aucune alarme itérative<br />';
		return;
	}
	
	foreach ($alarms as $alarm) {

		// 26/02/2008 - Modif. benoit : on peut maintenant avoir plusieurs ta_value pour une ta donnée (cas de la liste d'heures pour le compute booster). On boucle donc sur les différentes valeurs de ta dans '$time_to_calculate'
		$time_calculation = $time_to_calculate[$alarm['time']];
		// Pour pouvoir boucler, si '$time_calculation' n'est pas un tableau on le convertit
		if (!is_array($time_calculation)) $time_calculation = array($time_calculation);

		foreach ($time_calculation as $time_calc) {

			// 17/01/2008 - Modif. benoit : on vérifie que pour le calcul de l'alarme la valeur du '$time_to_calculate' ne fait pas partie des periodes d'exclusion
			if (isTimeCalculationExcluded($alarm['time'], $time_calc, $alarm['alarm_id'], $real_type) == false) {

				// 17/01/2008 - Modif. benoit : définition de la sous-requete qui limite les valeurs de TA à celles valides (cad non exclues)
				$alarm_times = getAlarmTimes($alarm['alarm_id'], $real_type, $alarm['discontinuous'], $alarm['time'], $time_calc, $alarm['period']);
				__debug($alarm_times, '$alarm_times');
				if (count($alarm_times['time_values']) > 0) {
					$time_values = $alarm_times['time_values'];
					
					if ($alarm_times['values_continuous'] == true)	// Les valeurs de TA retournées sont continues, on construit la sous-requete avec le début et la fin de la période retournée
					{			
						$sub_select_times = " AND ta_value >= ".$time_values[count($time_values)-1]." AND ta_value <= ".$time_values[0];
					}
					else // Valeurs de TA non continues
					{
						$sub_select_times = " AND ta_value IN (".implode(", ", $time_values).")";
					}			
				} else {
					$sub_select_times = "";
				}
				
				// modif 03/03/2008 GHX
				// Si, pour une heure donnée, des résultats correspondent à la période et au nombre d'itérations alors toutes les lignes de résultats de l'alarme correspondant
				// aux na et na_values toutes heures confondues vont être mises à visible.
				// Hors, il ne faudrait mettre à visible que les lignes de l'heure concernée (ou des heures de la période -> point à discuter).
				// -> Ajout de la condition sur la TA

				// 16:39 25/03/2009 - MPR : Répercution du bug 8006
				//BZ 8006 : [SUP][v4.0][7195][Tigo Sri Lanka][Alarm] : Script de calcul d'alarmes itératives est trop long
				// maintenant on met visible = 0 les alarmes itératives qui n'ont pas "sautées"
				$query = "
					UPDATE edw_alarm 
					SET visible = 0 
					FROM (
						SELECT COUNT(*), id_alarm, na, na_value
						FROM edw_alarm 
						WHERE id_alarm = '{$alarm['alarm_id']}'
							AND alarm_type = '$type_alarme'
							AND critical_level {$alarm['critical_level_query']}
							$sub_select_times
						GROUP BY id_alarm, na, na_value
						HAVING COUNT(*) < {$alarm['nb_iteration']}
					) AS t0
					WHERE edw_alarm.id_alarm = t0.id_alarm
						AND edw_alarm.na = t0.na
						AND edw_alarm.na_value = t0.na_value
						AND edw_alarm.critical_level {$alarm['critical_level_query']}
						AND edw_alarm.ta_value = {$alarm_times['time_values'][0]}
				";		
				
				$db->execute($query);
				echo "<br> - Alarme itérative {$alarm['alarm_name']} - nombre de lignes mise à jour : ".$db->getAffectedRows();
				__debug("Nb itération = {$alarm['nb_iteration']} / Période = ".$alarm['period']);
				__debug($query);	

			} else {
				echo "<br/>Pas de mise à jour de l'alarme iterative {$alarm['alarm_name']} : {$time_calculation[$i]} fait partie des valeurs excluses";
			}			
		}
		
	}




	/*
	$iterator = $listAlarms->getIterator();
	
	do {
		$alarm_current = $iterator->current();

		// 26/02/2008 - Modif. benoit : on peut maintenant avoir plusieurs ta_value pour une ta donnée (cas de la liste d'heures pour le compute booster). On boucle donc sur les différentes valeurs de ta dans '$time_to_calculate'
		$time_calculation = $time_to_calculate[$alarm_current->ta];
		// Pour pouvoir boucler, si '$time_calculation' n'est pas un tableau on le convertit
		if (!is_array($time_calculation)) $time_calculation = array($time_calculation);

		for ($i=0; $i < count($time_calculation); $i++) {

			// 17/01/2008 - Modif. benoit : on vérifie que pour le calcul de l'alarme la valeur du '$time_to_calculate' ne fait pas partie des periodes d'exclusion
		
			if (isTimeCalculationExcluded($alarm_current->ta, $time_calculation[$i], $alarm_current->id, $real_type) == false) {

				// 17/01/2008 - Modif. benoit : définition de la sous-requete qui limite les valeurs de TA à celles valides (cad non exclues)
				
				$alarm_times = getAlarmTimes($alarm_current->id, $real_type, $alarm_current->discontinuous, $alarm_current->ta, $time_calculation[$i], $alarm_current->period);
				__debug($alarm_times, '$alarm_times');
				if (count($alarm_times['time_values']) > 0) {
					$time_values = $alarm_times['time_values'];
					
					if ($alarm_times['values_continuous'] == true)	// Les valeurs de TA retournées sont continues, on construit la sous-requete avec le début et la fin de la période retournée
					{			
						$sub_select_times = " AND ta_value >= ".$time_values[count($time_values)-1]." AND ta_value <= ".$time_values[0];
					}
					else // Valeurs de TA non continues
					{
						$sub_select_times = " AND ta_value IN (".implode(", ", $time_values).")";
					}			
				} else {
					$sub_select_times = "";
				}
				
				// modif 03/03/2008 GHX
				// Si, pour une heure donnée, des résultats correspondent à la période et au nombre d'itérations alors toutes les lignes de résultats de l'alarme correspondant
				// aux na et na_values toutes heures confondues vont être mises à visible.
				// Hors, il ne faudrait mettre à visible que les lignes de l'heure concernée (ou des heures de la période -> point à discuter).
				// -> Ajout de la condition sur la TA

				$query = "
					UPDATE edw_alarm 
					SET visible = 1 
					FROM (
						SELECT COUNT(*), id_alarm, na, na_value
						FROM edw_alarm 
						WHERE id_alarm = '$alarm_current->id'
							AND alarm_type = '$type_alarme'
							AND critical_level $alarm_current->criticality_query
							$sub_select_times
						GROUP BY id_alarm, na, na_value
						HAVING COUNT(*) >= $alarm_current->nb_iteration
					) AS t0
					WHERE edw_alarm.id_alarm = t0.id_alarm
						AND edw_alarm.na = t0.na
						AND edw_alarm.na_value = t0.na_value
						AND edw_alarm.critical_level $alarm_current->criticality_query
						AND edw_alarm.ta_value = {$alarm_times['time_values'][0]}
				";		
				
				$db->execute($query);
				echo "<br> - Alarme itérative '$alarm_current->name' - nombre de lignes mise à jour : ".$db->getAffectedRows();
				__debug("Nb itération = $alarm_current->nb_iteration / Période = ".$alarm_current->period);
				__debug($query);	

			} else {
				echo "<br/>Pas de mise à jour de l'alarme iterative $alarm_current->name : {$time_calculation[$i]} fait partie des valeurs excluses";
			}			
		}
	
		$alarm_current = $iterator->next();
	} while( $iterator->valid() );
	*/

} // End function alarmsIteratives

/**
 * Récupère la liste des alarmes itératives  et la renvoi. Si aucune alarme n'est trouvé, la fonction renvoie false
 *
 * @global $database_connection
 * @param string $type_alarm : type de l'alarme à calculer : static - dynamic - top_worst
 * @param array ta : tableau contenant la liste des TA donc le calcule est possible
 * @param ArrayObject
 */
function getAlarmsIteratives ( $type_alarme, $ta ) {
	global $db,$debug;
	
	if ($debug) {
		echo "<div class='debug'><div class='function_call'>getAlarmsIteratives ( type_alarme=<strong>$type_alarme</strong>, ta=<strong>array(".implode(', ',$ta).")</strong> )</div>";
	}
	
	// Si le nombre d'itération et la période valent 1, l'alarme n'est pas considéré comme une alarme itérative
	// 17/01/2008 - Modif. benoit : ajout de la selection de la colonne 'discontinuous' pour les alarmes dynamiques dans la requete
	// 18/03/2008 - Modif. benoit : ajout de la condition "on_off=1" dans la requete de sélection des alarmes
	$query =	 "
		SELECT DISTINCT alarm_id, alarm_name, nb_iteration, period, "
			.($type_alarme == 'top_worst' ? 'NULL AS critical_level' : 'critical_level')
			.", time, "
			.($type_alarme != 'dynamic' ? '0 AS discontinuous' : 'discontinuous')."
		FROM sys_definition_alarm_$type_alarme
		WHERE nb_iteration > 1
			AND period > 1
			AND time IN ('". implode("','",$ta) ."')
			AND on_off = 1	";
	
	__debug($query, '$query');
	$result = $db->getall($query);
	
	if ($debug) echo "</div>";
	
	// Si aucune alarme itérative on renvoie false
	if (!$result)	return false;
	
	foreach ($result as &$row) {
		if ($type_alarme == 'top_worst')	$row['critical_level_query'] = 'IS NULL';
		else							$row['critical_level_query'] = "= '{$row['critical_level']}' ";
	}
	
	print_r($result);
	return $result;
	

	// Tableau contenant la liste des alarmes
	$list = new ArrayObject();
	
	while ( list($alarm_id, $alarm_name, $nb_iteration, $period, $criticality, $ta, $discontinuous) = array_shift($result) ) {
		$a				= new ArrayObject();
		$a->id			= $alarm_id;
		$a->name			= $alarm_name;
		$a->nb_iteration	= $nb_iteration;
		$a->period		= $period;
		$a->criticality		= $criticality;
		$a->ta			= $ta;
		// Si on est sur une alarme top/worst list
		// le champ critical_level est null
		$a->criticality_query  = ( $type_alarme == 'top_worst' ? 'IS NULL' : "= '$criticality' " );
		
		// 17/01/2007 - Modif. benoit : ajout du type "discontinous" dans le tableau
		$a->discontinuous = $discontinuous;
			
		$list->append($a);	
		unset($a);
	}
	print_r($list);
	return $list;
} // End function getAlarmsIteravites



// 17/01/2008 - Modif. benoit : ajout de la fonction 'isTimeCalculationExcluded()'
// 06/032009 - SLC : transfert de cette fonction depuis edw_alarm.php vers ce fichier
/**
 * Determine si une valeur de temps de calcul d'alarme fait partie des valeurs exclues
 *
 * @param string $ta TA
 * @param string $time_calculation  valeur de la TA de calcul
 * @param int $alarm_id identifiant de l'alarme à calculer
 * @param string $alarm_type type d'alarme
 * @return boolean valeur exclue (true) ou non (false)
 */

function isTimeCalculationExcluded($ta, $time_calculation, $alarm_id, $alarm_type){
	
	global $db;
	$time_excluded = false;
		 
	if ($ta == "hour") {
		$unixdate	= mktime(substr($time_calculation, 8, 2), 0, 0, substr($time_calculation, 4, 2), substr($time_calculation, 6, 2), substr($time_calculation, 0, 4));
		$hour	= date('H', $unixdate);
		$day		= get_num_day_of_week(date("l", $unixdate));
		
		$sql_where = "WHERE id_alarm = '$alarm_id' AND type_alarm = '$alarm_type' ";
		
		$sql = " --- recherche des heures de calcul exclues
			SELECT COUNT(*) AS excluded
			FROM sys_definition_alarm_exclusion
			$sql_where
				AND ta = 'hour'
				AND ta_value = '$hour'
				AND id_parent = (
					SELECT id
					FROM sys_definition_alarm_exclusion
					$sql_where
						AND ta = 'day'
						AND ta_value = '$day'
					)	";
				
		$row = $db->getrow($sql);
		$time_excluded = ($row['excluded'] == 1); 

	} elseif ($ta == "day" || $ta == "day_bh") {
		$unixdate	= mktime(6, 0, 0, substr($time_calculation, 4, 2), substr($time_calculation, 6, 2), substr($time_calculation, 0, 4));
		$day		= get_num_day_of_week(date("l", $unixdate));
		
		$sql = "
			SELECT COUNT(*) AS excluded
			FROM sys_definition_alarm_exclusion
			WHERE id_alarm = '$alarm_id'
				AND type_alarm = '$alarm_type'
				AND ta = 'day'
				AND ta_value = '$day' ";
				
		$row = $db->getrow($sql);
		$time_excluded = ($row['excluded'] == 1); 		
	}
	return $time_excluded;
}



?>