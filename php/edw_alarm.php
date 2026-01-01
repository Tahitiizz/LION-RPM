<?
/*
*	@cb502
*
*	23/02/2010 NSE
*		- remplacement de la fonctionsGetLastDayFromAcurioWeek par leur équivalent de la classe Date
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
	- maj 17/01/2008, benoit : ajout de la fonction 'getAlarmTimes()'
	- maj 17/01/2008, benoit : ajout de la fonction 'isTimeCalculationExcluded()'

	- maj 07/03/2008, benoit : mise en commentaires des appels à la fonction '__debug()'
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	maj 06/07/2007, maxime : En mode continu, on exclue les jours présents danss sys_definition_alarm_exclusion pour ta = day || ta = day_bh
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
<?
/*
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
* 02610-2006 : modification pour prise en compte de la BH
*
*
*/

/*
* Fonction get_num_day_of_week($date)
* Retourne le n° du jour de la semaine d'une date passée en paramètre (ex 2 pour mardi, ...)
* @param int $date date (ex:20070628)
*/
function get_num_day_of_week($date){
	// On récupère le nom du jour
	$time_exclusion_day = explode(";",__T('A_ALARM_DAY_OF_WEEK'));
	$num = array_keys($time_exclusion_day,$date);
	return $num[0];
}

$get_dyn_alarm_times_cache = array();
// cette fonction genere une liste des dates / times à prendre en consideration dans la requete des alarmes dynamiques
// cette fonction possede un système de cache : tous les résultats de la fonction sont mis dans le tableau $get_dyn_alarm_times_cache
// dont les clés sont $get_dyn_alarm_times_cache[$discontinuous][$time_aggregation][$time_frame].
// ex : $get_dyn_alarm_times_cache[1]['hour'][12] = ('2005101014','2005101013',...)
function get_dyn_alarm_times($discontinuous, $time_aggregation, $time_value, $interval_value, $date_format, $time_frame, $debug, $id,$time_frame_continu="")
{
    global $get_dyn_alarm_times_cache;
    global $sql_economisee;

    if ($get_dyn_alarm_times_cache[$discontinuous][$time_aggregation][$time_frame][$id]) {
        // cette fonction a deja ete appelée avec les mêmes arguments --> on renvoie la valeur en cache
        return $get_dyn_alarm_times_cache[$discontinuous][$time_aggregation][$time_frame][$id];
    } else {
        // aucune valeur dans le cache pour cette combinaison d'argument --> on lance la fonction normalement
        global $database_connection;
        $time_values = array();

        if ($discontinuous==1) {
            // cas d'une alarme dynamique discontinue

            if ($time_aggregation == "hour") {
                // cas où la TA == 'hour'
                // maintenant = $time_value
                $unixdate = mktime(substr($time_value, 8, 2), 0, 0, substr($time_value, 4, 2), substr($time_value, 6, 2), substr($time_value, 0, 4));
                // on cherche les $time_frame derniers heures (toujours la meme) des $time_frame derniers jours
                for ($i = 1; $i <= $time_frame; $i++) {
                    $time_values[] = date('YmdH', $unixdate - $i * 24 * 3600);
                }
			} elseif ($time_aggregation == "day" || $time_aggregation == "day_bh") {
                // cas où la TA == 'day'
                // on cherche les $time_frame derniers "mardis" au format acurio
                $unixdate = mktime(6, 0, 0, substr($time_value, 4, 2), substr($time_value, 6, 2), substr($time_value, 0, 4));
                // back one day
                $unixdate = $unixdate - 24 * 60 * 60;
                $time_values[] = date('Ymd', $unixdate);
                for ($i = 1; $i < $time_frame; $i++) {
                    $time_values[] = date('Ymd', $unixdate - $i * 7 * 24 * 3600);
                }
            } else {
                echo "Error: dynamic alarm with time aggregation equal to : <strong>" . $time_aggregation . "</strong> !!!";
                return $time_values;
            }
        } else {
            // cas d'une alarme dynamique continue
            // on va chercher les dernières date en utilisant php mais avant on utilisait pgsql
            switch ($time_aggregation) {
                case "hour":
                    // cas où la TA == 'hour'
                    // maintenant = $time_value
					$unixdate = mktime(substr($time_value, 8, 2), 0, 0, substr($time_value, 4, 2), substr($time_value, 6, 2), substr($time_value, 0, 4));


						$i = 1;
						$cpt = 0;
						$msg = array();
						while($cpt<$time_frame and $i<$time_frame_continu){
							$day =  $unixdate - $i * 3600; // TimeStamp
							$hour = date('YmdH', $unixdate - $i * 3600); // Heure que l'on récupère
							// On récupère les heures exclues pour le jour donné
							$query = "SELECT distinct ta_value FROM sys_definition_alarm_exclusion
									     WHERE id_alarm = '$id' and type_alarm = 'alarm_dynamic' and ta = '".$time_aggregation."'
										 and id_parent IN (SELECT id FROM sys_definition_alarm_exclusion WHERE id_alarm = '$id' and type_alarm = 'alarm_dynamic'
										 and ta_value = '".get_num_day_of_week( date( 'l', $day) )."')";
							$res = pg_query($database_connection,$query);
							// __debug($query,"query");
							$lst_hours_excluded = array();
							while($row = pg_fetch_array($res)){
								$lst_hours_excluded[] = date('Ymd', $unixdate - $i * 3600).$row['ta_value'];
							}
							// Mode debug
							if(!in_array(date('Ymd', $unixdate - $i * 3600),$msg)){
								$msg[] = date('Ymd', $unixdate - $i * 3600);
								//__debug("<br/>id alarme => $id");
								//__debug($lst_hours_excluded,"<br/>lst_hours_excluded ".date('Ymd', $unixdate - $i * 3600));
							}

							// On insère les heures si elles ne sont pas exclues
							if(!in_array($hour,$lst_hours_excluded)){
								$time_values[] = $hour;
								$cpt++;
							}
							$i++;
						}

					//__debug($time_values,"time_values");
					//__debug($cpt,"cpt");
					//__debug($i,"i");
                    break;

                case ($time_aggregation=="day" || $time_aggregation=="day_bh"):
					$time_values = array();
					$query = "select distinct ta_value from sys_definition_alarm_exclusion
							     where id_alarm = '$id' and type_alarm = 'alarm_dynamic' and ta = '".$time_aggregation."'";
					$res = pg_query($database_connection,$query);
					$lst_day_excluded = array();
					while($row = pg_fetch_array($res)){
						$lst_days_excluded[] = $row['ta_value'];
					}
					// cas où la TA == 'day' ||ta == 'day_bh'
                    $unixdate = mktime(6, 0, 0, substr($time_value, 4, 2), substr($time_value, 6, 2), substr($time_value, 0, 4));
                    // back one day
                    $unixdate = $unixdate - 24 * 60 * 60;
					if(count($lst_days_excluded)>0){ // On prend en compte uniquement les jours qui ne sont pas exclus
						$i = 0;
						$cpt = 0;
						// $time_values[] = date('Ymd', $unixdate);
						while($cpt<$time_frame and $i<$time_frame_continu){
							// Si le jour n'est pas exclu, on l'intègre ds la liste des jours à récupérer
							if(!in_array(get_num_day_of_week(date("l",($unixdate-$i* 24 * 3600))),$lst_days_excluded)){
								$time_values[] = date('Ymd', $unixdate - $i * 24 * 3600);
								$cpt++;
							}
							$i++;
						}

					}else{
						$time_values[] = date('Ymd', $unixdate);
	                    for ($i = 1; $i < $time_frame; $i++) {
	                        $time_values[] = date('Ymd', $unixdate - $i * 24 * 3600);
	                    }
					}
					// Mode debug
						//__debug("<br/>id alarme => $id");
						//__debug($lst_days_excluded,"<br/>lst_days_excluded");
						//__debug($time_values,"<br/>time_values");

                    break;

                case ($time_aggregation=="week" || $time_aggregation=="week_bh"):
                    // cas où la TA == 'week'
                    // $time_value de la forme : 'YYYYWW'
                    // on cherche le dernier jour de cette semaine :
					// 23/02/2010 NSE : remplacement GetLastDayFromAcurioWeek($week) par Date::getLastDayFromWeek($week,$firstDayOfWeek=1)
					$last_day = Date::getLastDayFromWeek($time_value,get_sys_global_parameters('week_starts_on_monday',1));
                    // on cherche les $time_frame dernieres semaines depuis la semaine dernière
                    for ($i = 1; $i <= $time_frame; $i++) {
                        $unixdate = mktime(6, 0, 0, substr($last_day, 4, 2), substr($last_day, 6, 2) - ($i * 7), substr($last_day, 0, 4));
						// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                        $time_values[] = date('oW', $unixdate);
                    }
                    break;

                case ($time_aggregation=="month" || $time_aggregation=="month_bh") :
                    // cas où la TA == 'month'
                    // $time_value de la forme : 'YYYYMM'
                    // on cherche les $time_frame derniers mois depuis le mois dernier
                    for ($i = 1; $i <= $time_frame; $i++) {
                        $unixdate = mktime(6, 0, 0, substr($time_value, 4, 2) - $i, 1, substr($time_value, 0, 4));
                        $time_values[] = date('Ym', $unixdate);
                    }
                    break;
            }

            if ($debug) echo "&nbsp;&nbsp;&nbsp;&nbsp;<u><b>On cherche les dates considérées : </b></u><br> &nbsp;&nbsp;&nbsp;" . $query . '<br /><br />';
        }

        if ($debug) echo "<div style='color:blue;'>[$discontinuous][$time_aggregation][$time_frame] --> (" . implode(',', $time_values) . ')</div>';
        // on met la valeur en cache
        // $get_dyn_alarm_times_cache[$arguments] = $time_values;
        $get_dyn_alarm_times_cache[$discontinuous][$time_aggregation][$time_frame][$id] = $time_values;

        return $time_values;
    }
}

// 17/01/2008 - Modif. benoit : ajout de la fonction 'getAlarmTimes()'

/**
 * Retourne les valeurs de TA valides (cad non exclues) d'une alarme sur un periode de temps donnée
 * 
 * @example getAlarmTimes(36, 'alarm_static', 0, 'day', '20080116', 20))
 *  
 * @param int $id_alarm identifiant de l'alarme
 * @param string $type_alarm type de l'alarme ('alarm_static', 'alarm_dynamic', 'alarm_top_worst')
 * @param int $discontinuous calcul discontinue (1) ou non (0)
 * @param string $time_aggregation TA
 * @param string $time_value valeur de la TA
 * @param int $time_frame nombre de périodes de temps à définir
 * @return array 'time_values' -> valeurs des TA valides sur la periode, 'values_continuous' -> valeurs de TA continues (true) ou non (false)
 */

function getAlarmTimes($id_alarm, $type_alarm, $discontinuous, $time_aggregation, $time_value, $time_frame)
{
	global $database_connection;	

	$time_values		= array();
	$values_continuous	= false;
	
	if ($type_alarm == "alarm_dynamic" && $discontinuous == 1)	// cas d'une alarme dynamique discontinue
	{		
		if ($time_aggregation == "day" || $time_aggregation == "day_bh") {	
			
			$unixdate = mktime(6, 0, 0, substr($time_value, 4, 2), substr($time_value, 6, 2), substr($time_value, 0, 4));
						
			for ($i = 0; $i < $time_frame; $i++) {
				$time_values[] = date('Ymd', $unixdate - $i * 7 * 24 * 3600);
			}
		} 
		else
		{
			return array('time_values' => $time_values, 'values_continuous' => $values_continuous);
		}
	} 
	else // Alarme statique, dynamique continue ou top-worst
	{
		// on va chercher les dernières date en utilisant php mais avant on utilisait pgsql
		switch ($time_aggregation) {
		
			case "hour":
				
				// Conversion de la '$time_value' en Unix timestamp
				
				$unixdate = mktime(substr($time_value, 8, 2), 0, 0, substr($time_value, 4, 2), substr($time_value, 6, 2), substr($time_value, 0, 4));
				
				// Recherche des heures exclues pour l'alarme
				
				$lst_hours_excluded = array();
				
				$sql =	 " SELECT DISTINCT parent.ta_value AS day_value, child.ta_value AS hour_value FROM sys_definition_alarm_exclusion AS child"
						." RIGHT JOIN"
						." ("
						." SELECT id, ta_value FROM sys_definition_alarm_exclusion"
						." WHERE id_alarm = '$id_alarm' AND type_alarm = '$type_alarm' AND id_parent = 0"
						." )"
						." AS parent"
						." ON child.id_parent = parent.id"
						." WHERE child.ta = '$time_aggregation' AND type_alarm = '$type_alarm'";
				
				$req = pg_query($database_connection, $sql);
								
				if (pg_num_rows($req) > 0)
				{
					while ($row = pg_fetch_array($req))
					{
						$lst_hours_excluded[$row['day_value']][] = $row['hour_value'];
					}					
				}
				else 
				{
					$values_continuous = true;
				}
								
				// Recherche des heures non exclues sur la periode '$time_frame'
				
				$i = 0;
				
				while (count($time_values) < $time_frame){
					$hour	= date('H', $unixdate - $i * 3600);
					$day	= get_num_day_of_week(date("l",($unixdate-$i* 3600)));
					
					if ((!isset($lst_hours_excluded[$day])) || (!in_array($hour, $lst_hours_excluded[$day]))) {
						$time_values[] = date('YmdH', ($unixdate-$i * 3600));
					}	
					$i += 1;
				}
			
			break;
		
			case ($time_aggregation=="day" || $time_aggregation=="day_bh"):
				
				// Conversion de la '$time_value' en Unix timestamp
				
				$unixdate = mktime(6, 0, 0, substr($time_value, 4, 2), substr($time_value, 6, 2), substr($time_value, 0, 4));				
				
				// Recherche des jours exclus pour l'alarme
				
				$lst_day_excluded = array();
				
				$sql = "SELECT DISTINCT ta_value FROM sys_definition_alarm_exclusion WHERE id_alarm = '".$id_alarm."' AND type_alarm = '".$type_alarm."' AND ta = '".$time_aggregation."'";
				$req = pg_query($database_connection, $sql);

				if (pg_num_rows($req) > 0)
				{
					while ($row = pg_fetch_array($req))
					{
						$lst_days_excluded[] = $row['ta_value'];
					}					
				}
				else 
				{
					$values_continuous = true;
				}				
								
				// Recherche des jours non exclus sur la periode '$time_frame'
				
				$i = 0;
				
				while (count($time_values) < $time_frame){
					if (!$values_continuous) {
						if (!in_array(get_num_day_of_week(date("l",($unixdate-$i* 24 * 3600))), $lst_days_excluded)) {
							$time_values[] = date('Ymd', $unixdate - $i * 24 * 3600);
						}						
					}
					else
					{
						$time_values[] = date('Ymd', $unixdate - $i * 24 * 3600);
					}
					$i += 1;
				}
			
			break;
			
			case ($time_aggregation=="week" || $time_aggregation=="week_bh") :

				// On cherche le dernier jour de la semaine courante
				
				// 23/02/2010 NSE : remplacement GetLastDayFromAcurioWeek($week) par Date::getLastDayFromWeek($week,$firstDayOfWeek=1)
				$last_day = Date::getLastDayFromWeek($time_value,get_sys_global_parameters('week_starts_on_monday',1));
				
				// On cherche les $time_frame dernieres semaines depuis la semaine courante
				
				for ($i = 0; $i < $time_frame; $i++) {
					$unixdate = mktime(6, 0, 0, substr($last_day, 4, 2), substr($last_day, 6, 2) - ($i * 7), substr($last_day, 0, 4));
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
					$time_values[] = date('oW', $unixdate);
				}
				
				$values_continuous = true;
			
			break;
				
			case ($time_aggregation=="month" || $time_aggregation=="month_bh") :

				// On cherche les $time_frame derniers mois depuis le mois courant
				
				for ($i = 0; $i < $time_frame; $i++) {
					$unixdate = mktime(6, 0, 0, substr($time_value, 4, 2) - $i, 1, substr($time_value, 0, 4));
					$time_values[] = date('Ym', $unixdate);
				}
				
				$values_continuous = true;
			
			break;
		}
	}	
	return array('time_values' => $time_values, 'values_continuous' => $values_continuous);
}


?>
