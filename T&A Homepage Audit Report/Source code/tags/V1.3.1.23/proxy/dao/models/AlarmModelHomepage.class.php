<?php
/**
*	Classe permettant de récupérer les données d'une alarme statiques
*	S'appuie sur les tables edw_alarm et sys_definition_alarm_static
*	
*	Spécifique au template Cells Surveillance et Audi Report de myHomepage
*
*
*	@author	mhubert - 04/04/2013
*/



class AlarmModelHomepage
{
	
	/**
	 * 
	 * Va calculer les cellules pénalisables/pénalisées pour l'alarme concernée
	 * 
	 * @param int $sdp_ip product_id considéré
	 * @param string $alarm_id l'id de l'alarme considérée
	 * @param string $current_date la date courante
	 * @param int $ref_period le delta pour le calcul de la période de référence
	 * @param int $min_days le nombre de jours minimal pour affichage
	 * @param double $ratioforpenalisation ratio du nombre de jours dans le mois pour la pénalisation
	 * @return array $result la liste des cellules et les valeurs
	 */
	public static function calculateReferencePeriodPenalisation($sdp_id, $alarm_id, $current_date, $ref_period, $min_days, $selected_mode, $ratioforpenalisation, $nbdaysforpenalisation){
		
		$result=array();
		
		//get alarm family : bss check for bsc parent, ran check for rnc parent
		$sql="SELECT family FROM sys_definition_alarm_static WHERE alarm_id='".$alarm_id."' LIMIT 1";
		$db = Database::getConnection($sdp_id);
		
		$family= $db->getOne($sql);
		
		switch($family){
			case 'bss': $arc='cell|s|bsc';break;
			case 'cellb': $arc='cell|s|rnc';break;
			default: $arc='%';break;
		}
		
		//watch out for february month
 		//create timestamp from int date '20121201'
		$ref_date=mktime(0,0,0,substr($current_date,4,2),'01',substr($current_date,0,4));
		//string format for full current date '2012-12-01'
		$current_date_pretty=strftime("%Y-%m-%d",$ref_date);
		//string format for current date '201212'
		$current_year_month=strftime("%Y%m",$ref_date);
		//string format for ref date '201209'
		$ref_date_year_month = date('Ym',strtotime($current_date_pretty . ' -'.$ref_period.' month'));
		//string format for full ref date '2012-09-01'
		$ref_date_pretty = date('Y-m-d',strtotime($current_date_pretty . ' -'.$ref_period.' month'));
		
		//query calculation upon penalisation reference period
		$sql="SELECT m.cell_id, m.cell_label, eoar.eoar_id_parent as parent, m.in_ref_period, m.days_before_penalisation, m.days_in_default FROM 
				(SELECT a.cell_id, a.cell_label, CASE WHEN b.in_ref_period =1 THEN 1 ELSE 0 END as in_ref_period, CASE WHEN a.days_before_penalisation<1 THEN NULL ELSE a.days_before_penalisation END, a.days_in_default 
				FROM
				(
					SELECT e.cell_id, e.cell_label, count(e.days_in_default) as days_in_default,
					".($selected_mode == 1 ?"(ceil(DATE_PART('days', DATE_TRUNC('month', date '".$current_date_pretty."') 
					+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
					- DATE_TRUNC('month', date '".$current_date_pretty."'))*{$ratioforpenalisation})-count(e.cell_id)) as days_before_penalisation" : "{$nbdaysforpenalisation} - count(e.cell_id) as days_before_penalisation")." 
					FROM
					(
						SELECT na_value as cell_id, eor_label as cell_label, count(ta_value) as days_in_default
						FROM edw_alarm INNER JOIN edw_object_ref ON edw_alarm.na_value=edw_object_ref.eor_id 
						WHERE id_alarm='".$alarm_id."' AND alarm_type='static' AND ta_value::text LIKE '".$current_year_month."%' AND eor_on_off=1
						GROUP BY na_value, eor_label,ta_value 
					) e
					GROUP BY e.cell_id, e.cell_label, e.days_in_default
				) a
			LEFT JOIN
			(
				SELECT d.cell_id, 1 as in_ref_period 
				FROM
				(
					SELECT na_value as cell_id 
					FROM edw_alarm INNER JOIN edw_object_ref ON edw_alarm.na_value=edw_object_ref.eor_id 
					WHERE id_alarm='".$alarm_id."' AND alarm_type='static' AND ta_value::text LIKE '".$ref_date_year_month."%' AND eor_on_off=1 
					GROUP BY na_value 
					HAVING count(id_result) >= {$min_days} AND count(id_result) >= ".($selected_mode == 1 ?" ceil(DATE_PART('days', 
						DATE_TRUNC('month', date '".$ref_date_pretty."') 
						+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
						- DATE_TRUNC('month', date '".$ref_date_pretty."'))*{$ratioforpenalisation})" : " {$nbdaysforpenalisation}")."
				) d
				GROUP BY d.cell_id
			) b
			ON a.cell_id=b.cell_id
			WHERE a.days_in_default >= {$min_days}
			ORDER BY in_ref_period DESC, days_before_penalisation ASC, days_in_default DESC) m
		INNER JOIN edw_object_arc_ref eoar ON eoar.eoar_id=m.cell_id
		WHERE eoar.eoar_arc_type='".$arc."'
		";		
				
		
		$result= $db->getAll($sql);
		//var_dump($sql);
		if(!empty($result)){
			//remplacer les valeur nulles pour le champ days_before_penalisation par 0
			$result=array_map(array('AlarmModelHomepage','convertNullValueToZero'),$result);
		}
			
		return $result;
		
	}
	
	
	/**
	 * Calcul les donn�es pour les exports csv summary report d'audit report
	 *
	 * @param int $sdp_ip product_id consid�r�
	 * @param string $alarm_ids liste d'id d'alarms consid�r�e
	 * @param string $current_date la date courante
	 * @param int $ref_period le delta pour le calcul de la p�riode de r�f�rence
	 * @param double $ratioforpenalisation ratio du nombre de jours dans le mois pour la p�nalisation
	 * @param int $nbdaysforpenalisation nombre de jours dans le mois pour la p�nalisation
	 * @param boolean $warning demander les ne en warning (non pr�sents dans la p�riode de r�f�rence)
	 * @param boolean $penalty demander les ne en penalty (pr�sents dans la p�riode de r�f�rence)
	 * @param boolean $count compter le nombre de ne en penalty et warning
	 * @return array $result la liste des cellules et les valeurs
	 */
	public static function calculatePenaltyWarningCells($sdp_id, $alarm_ids, $current_date, $ref_period,$selected_mode, $ratioforpenalisation,$nbdaysforpenalisation,$warning=false,$penalty=false,$count=false){

		$result=array();
		
		$db = Database::getConnection($sdp_id);
		
		//watch out for february month
		//create timestamp from int date '20121201'
		$ref_date=mktime(0,0,0,substr($current_date,4,2),'01',substr($current_date,0,4));
		//string format for full current date '2012-12-01'
		$current_date_pretty=strftime("%Y-%m-%d",$ref_date);
		//string format for current date '201212'
		$current_year_month=strftime("%Y%m",$ref_date);
		//string format for ref date '201209'
		$ref_date_year_month = date('Ym',strtotime($current_date_pretty . ' -'.$ref_period.' month'));
		//string format for full ref date '2012-09-01'
		$ref_date_pretty = date('Y-m-d',strtotime($current_date_pretty . ' -'.$ref_period.' month'));
		
		
		if($penalty && $warning){
			$penaltywarningclause="";
		}
		elseif(!$penalty && $warning){
			$penaltywarningclause="AND in_ref_period IS NULL";
		}
		elseif($penalty && !$warning){
			$penaltywarningclause="AND in_ref_period=1";
		}
		
		//alarms id list
		//TODO check if empty
		$alarms=implode("','",$alarm_ids);
		$alarms="'".$alarms."'";
		
		
		//var_dump($alarms);
		
		$sql="
			SELECT a.ne_id, eor.eor_label as ne_label,a.days_in_fault, CASE WHEN b.in_ref_period =1 THEN 1 ELSE 0 END as in_ref_period FROM
			(
				SELECT na_value AS ne_id, COUNT(na_value) AS days_in_fault FROM 
				(
					SELECT na_value
					FROM edw_alarm
					WHERE alarm_type = 'static'
					AND id_alarm IN ({$alarms})
					AND ta_value::text LIKE '".$current_year_month."%'
					GROUP BY na_value, ta_value
				) AS network_element
				GROUP BY na_value
				HAVING COUNT(na_value) >= ".($selected_mode == 2 ? $nbdaysforpenalisation : "ceil(DATE_PART('days',
				DATE_TRUNC('month', date '".$current_date_pretty."')
				+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
				- DATE_TRUNC('month', date '".$current_date_pretty."'))*{$ratioforpenalisation})")."
				) a
			LEFT JOIN
			(
				SELECT na_value AS ne_id, COUNT(na_value) AS days_in_fault,1 as in_ref_period FROM 
				(
					SELECT na_value
					FROM edw_alarm
					WHERE alarm_type = 'static'
					AND id_alarm IN ({$alarms})
					AND ta_value::text like '".$ref_date_year_month."%'
					GROUP BY na_value, ta_value
				) AS network_element
				GROUP BY na_value
				HAVING COUNT(na_value) >= ".($selected_mode == 2 ? $nbdaysforpenalisation : "ceil(DATE_PART('days',
				DATE_TRUNC('month', date '".$ref_date_pretty."')
				+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
				- DATE_TRUNC('month', date '".$ref_date_pretty."'))*{$ratioforpenalisation})")."
				) b
			ON a.ne_id=b.ne_id
			INNER JOIN edw_object_ref eor ON eor.eor_id=a.ne_id
			WHERE eor_on_off=1
			{$penaltywarningclause}
			GROUP BY a.ne_id, eor.eor_label,a.days_in_fault,b.in_ref_period
			ORDER by a.ne_id ASC	
		";
		//var_dump($sql);
		$result= $db->getAll($sql);
		
		//TODO check if empty
		if($count){
			$warnings=0;
			$penalties=0;
			$penaltiesArr = array();
			$warningsArr = array();
			$dateArr = array();
			//count number of ne for each category
			foreach ($result as $row){
				if($row['in_ref_period']==1){
					$penalties++;
					$warnings++;
					$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']);
					array_push($penaltiesArr, $currentRow);
					
				}else{
					$warnings++;
					$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']); 
					array_push($warningsArr, $currentRow);
					
				}
				
			}
			$result=array("time"=>$current_year_month,"penalty"=>$penalties,"warning"=>$warnings,"csv"=>array("penalty"=>$penaltiesArr,"warning"=>$warningsArr));
		}
		/**
		if($count){
			$penaltiesArr = array();
			$warningsArr = array();
			$dateArr = array();
			foreach ($result as $row){
				if($row['in_ref_period']==1){
					
					$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']);
					array_push($penaltiesArr, $currentRow);
				}
				else{
					
					$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']); 
					array_push($warningsArr, $currentRow);
				}	
			}	
		}

		$exportcsv=array("csv"=>array("penalty"=>$penaltiesArr,"warning"=>$warningsArr));
		**/
		//var_dump($result);
		
		return $result;
		
	}

		/**
	 * Calcul les donn�es pour les exports csv summary report d'audit report
	 *
	 * @param int $sdp_ip product_id consid�r�
	 * @param string $alarm_ids liste d'id d'alarms consid�r�e
	 * @param string $current_date la date courante
	 * @param int $ref_period le delta pour le calcul de la p�riode de r�f�rence
	 * @param double $ratioforpenalisation ratio du nombre de jours dans le mois pour la p�nalisation
	 * @param int $nbdaysforpenalisation nombre de jours dans le mois pour la p�nalisation
	 * @param boolean $warning demander les ne en warning (non pr�sents dans la p�riode de r�f�rence)
	 * @param boolean $penalty demander les ne en penalty (pr�sents dans la p�riode de r�f�rence)
	 * @param boolean $count compter le nombre de ne en penalty et warning
	 * @return array $result la liste des cellules et les valeurs
	 */
	 public static function calculatePenaltyWarningCellsEvo($sdp_id, $alarm_ids, $current_date, $ref_period,$selected_mode, $ratioforpenalisation,$nbdaysforpenalisation,$warning=false,$penalty=false,$count=false){
		
				$result=array();
				
				$db = Database::getConnection($sdp_id);
				
				//watch out for february month
				//create timestamp from int date '20121201'
				$ref_date=mktime(0,0,0,substr($current_date,4,2),'01',substr($current_date,0,4));
				//string format for full current date '2012-12-01'
				$current_date_pretty=strftime("%Y-%m-%d",$ref_date);
				//string format for current date '201212'
				$current_year_month=strftime("%Y%m",$ref_date);
				//string format for ref date '201209'
				$ref_date_year_month = date('Ym',strtotime($current_date_pretty . ' -'.$ref_period.' month'));
				//string format for full ref date '2012-09-01'
				$ref_date_pretty = date('Y-m-d',strtotime($current_date_pretty . ' -'.$ref_period.' month'));

				//string format for ref date '201209'
				$ref_date_year_month_1 = date('Ym',strtotime($current_date_pretty . ' -1 month'));
				//string format for full ref date '2012-09-01'
				$ref_date_pretty_1 = date('Y-m-d',strtotime($current_date_pretty . ' -1 month'));

				//string format for ref date '201209'
				$ref_date_year_month_2 = date('Ym',strtotime($current_date_pretty . ' -2 month'));
				//string format for full ref date '2012-09-01'
				$ref_date_pretty_2 = date('Y-m-d',strtotime($current_date_pretty . ' -2 month'));
				
				
				if($penalty && $warning){
					$penaltywarningclause="";
				}
				elseif(!$penalty && $warning){
					$penaltywarningclause="AND in_ref_period IS NULL";
				}
				elseif($penalty && !$warning){
					$penaltywarningclause="AND in_ref_period=1";
				}
				
				//alarms id list
				//TODO check if empty
				$alarms=implode("','",$alarm_ids);
				$alarms="'".$alarms."'";
				
				
				//var_dump($alarms);
				
				$sql="
					SELECT a.ne_id, eor.eor_label as ne_label,a.days_in_fault, CASE WHEN b.in_ref_period =1 THEN 1 ELSE 0 END as in_ref_period FROM
					(
						SELECT na_value AS ne_id, COUNT(na_value) AS days_in_fault FROM 
						(
							SELECT na_value
							FROM edw_alarm
							WHERE alarm_type = 'static'
							AND id_alarm IN ({$alarms})
							AND ta_value::text LIKE '".$current_year_month."%'
							GROUP BY na_value, ta_value
						) AS network_element
						GROUP BY na_value
						HAVING COUNT(na_value) >= ".($selected_mode == 2 ? $nbdaysforpenalisation : "ceil(DATE_PART('days',
						DATE_TRUNC('month', date '".$current_date_pretty."')
						+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
						- DATE_TRUNC('month', date '".$current_date_pretty."'))*{$ratioforpenalisation})")."
						) a
					LEFT JOIN
					(
						SELECT c.ne_id, eor.eor_label as ne_label,c.days_in_fault, CASE WHEN d.in_ref_period =1 THEN 1 ELSE 0 END as in_ref_period FROM
						(
							SELECT na_value AS ne_id, COUNT(na_value) AS days_in_fault,1 as in_ref_period FROM 
							(
								SELECT na_value
								FROM edw_alarm
								WHERE alarm_type = 'static'
								AND id_alarm IN ({$alarms})
								AND ta_value::text like '".$ref_date_year_month_1."%'
								GROUP BY na_value, ta_value
							) AS network_element
							GROUP BY na_value
							HAVING COUNT(na_value) >= ".($selected_mode == 2 ? $nbdaysforpenalisation : "ceil(DATE_PART('days',
							DATE_TRUNC('month', date '".$ref_date_pretty_1."')
							+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
							- DATE_TRUNC('month', date '".$ref_date_pretty_1."'))*{$ratioforpenalisation})")."
							) c
						LEFT JOIN
						(
							
							SELECT na_value AS ne_id, COUNT(na_value) AS days_in_fault,1 as in_ref_period FROM 
							(
								SELECT na_value
								FROM edw_alarm
								WHERE alarm_type = 'static'
								AND id_alarm IN ({$alarms})
								AND ta_value::text like '".$ref_date_year_month_2."%'
								GROUP BY na_value, ta_value
							) AS network_element
							GROUP BY na_value
							HAVING COUNT(na_value) >= ".($selected_mode == 2 ? $nbdaysforpenalisation : "ceil(DATE_PART('days',
							DATE_TRUNC('month', date '".$ref_date_pretty_2."')
							+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
							- DATE_TRUNC('month', date '".$ref_date_pretty_2."'))*{$ratioforpenalisation})")."
						) d
						ON c.ne_id=d.ne_id
						INNER JOIN edw_object_ref eor ON eor.eor_id=c.ne_id
						WHERE eor_on_off=1
						{$penaltywarningclause}
						GROUP BY c.ne_id, eor.eor_label,c.days_in_fault,d.in_ref_period
						ORDER by c.ne_id 	ASC
					) b
					ON a.ne_id=b.ne_id
					INNER JOIN edw_object_ref eor ON eor.eor_id=a.ne_id
					WHERE eor_on_off=1
					{$penaltywarningclause}
					GROUP BY a.ne_id, eor.eor_label,a.days_in_fault,b.in_ref_period
					ORDER by a.ne_id ASC
				";
				//var_dump($sql);
				$result= $db->getAll($sql);
				
				//TODO check if empty
				if($count){
					$warnings=0;
					$penalties=0;
					$penaltiesArr = array();
					$warningsArr = array();
					$dateArr = array();
					//count number of ne for each category
					foreach ($result as $row){
						if($row['in_ref_period']==1){
							$penalties++;
							$warnings++;
							$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']);
							array_push($penaltiesArr, $currentRow);
							
						}else{
							$warnings++;
							$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']); 
							array_push($warningsArr, $currentRow);
							
						}
						
					}
					$result=array("time"=>$current_year_month,"penalty"=>$penalties,"warning"=>$warnings,"csv"=>array("penalty"=>$penaltiesArr,"warning"=>$warningsArr));
				}
				/**
				if($count){
					$penaltiesArr = array();
					$warningsArr = array();
					$dateArr = array();
					foreach ($result as $row){
						if($row['in_ref_period']==1){
							
							$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']);
							array_push($penaltiesArr, $currentRow);
						}
						else{
							
							$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences"=>$row['days_in_fault']); 
							array_push($warningsArr, $currentRow);
						}	
					}	
				}
		
				$exportcsv=array("csv"=>array("penalty"=>$penaltiesArr,"warning"=>$warningsArr));
				**/
				//var_dump($result);
				
				return $result;
				
		}

		/**
	 * Calcul les donn�es pour les exports csv summary report d'audit report
	 *
	 * @param int $sdp_ip product_id consid�r�
	 * @param string $alarm_ids liste d'id d'alarms consid�r�e
	 * @param string $current_date la date courante
	 * @param int $ref_period le delta pour le calcul de la p�riode de r�f�rence
	 * @param double $ratioforpenalisation ratio du nombre de jours dans le mois pour la p�nalisation
	 * @param int $nbdaysforpenalisation nombre de jours dans le mois pour la p�nalisation
	 * @param boolean $warning demander les ne en warning (non pr�sents dans la p�riode de r�f�rence)
	 * @param boolean $penalty demander les ne en penalty (pr�sents dans la p�riode de r�f�rence)
	 * @param boolean $count compter le nombre de ne en penalty et warning
	 * @return array $result la liste des cellules et les valeurs
	 */
	public static function calculateIntersectionWarningCells($sdp_id, $alarm_ids, $current_date, $selected_mode, $ratioforpenalisation,$nbdaysforpenalisation,$warning=false,$penalty=false,$count=false){
				
				$result=array();
				
				$db = Database::getConnection($sdp_id);
				
				//watch out for february month
				//create timestamp from int date '20121201'
				$ref_date=mktime(0,0,0,substr($current_date,4,2),'01',substr($current_date,0,4));
				//string format for full current date '2012-12-01'
				$current_date_pretty=strftime("%Y-%m-%d",$ref_date);
				//string format for current date '201212'
				$current_year_month=strftime("%Y%m",$ref_date);
				//string format for ref date '201209'
				$first_year_month = date('Ym',strtotime($current_date_pretty . ' -1 month'));
				//string format for full ref date '2012-09-01'
				$first_date_pretty = date('Y-m-d',strtotime($current_date_pretty . ' -1 month'));

				//string format for ref date '201209'
				$second_year_month = date('Ym',strtotime($current_date_pretty . ' -2 month'));
				//string format for full ref date '2012-09-01'
				$second_date_pretty = date('Y-m-d',strtotime($current_date_pretty . ' -2 month'));
				
				
				if($penalty && $warning){
					$penaltywarningclause="";
				}
				elseif(!$penalty && $warning){
					$penaltywarningclause="AND in_ref_period IS NULL";
				}
				elseif($penalty && !$warning){
					$penaltywarningclause="AND in_ref_period=1";
				}
				
				//alarms id list
				//TODO check if empty
				$alarms=implode("','",$alarm_ids);
				$alarms="'".$alarms."'";
				
				
				//var_dump($alarms);
				
				$sql="
					SELECT a.ne_id, eor.eor_label as ne_label,a.days_in_fault as days_in_fault_m1,b.days_in_fault as days_in_fault_m2, CASE WHEN b.in_ref_period =1 THEN 1 ELSE 0 END as in_ref_period FROM
					(
						SELECT na_value AS ne_id, COUNT(na_value) AS days_in_fault FROM 
						(
							SELECT na_value
							FROM edw_alarm
							WHERE alarm_type = 'static'
							AND id_alarm IN ({$alarms})
							AND ta_value::text LIKE '".$first_year_month."%'
							GROUP BY na_value, ta_value
						) AS network_element
						GROUP BY na_value
						HAVING COUNT(na_value) >= ".($selected_mode == 2 ? $nbdaysforpenalisation : "ceil(DATE_PART('days',
						DATE_TRUNC('month', date '".$first_date_pretty."')
						+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
						- DATE_TRUNC('month', date '".$first_date_pretty."'))*{$ratioforpenalisation})")."
						) a
					LEFT JOIN
					(
						SELECT na_value AS ne_id, COUNT(na_value) AS days_in_fault,1 as in_ref_period FROM 
						(
							SELECT na_value
							FROM edw_alarm
							WHERE alarm_type = 'static'
							AND id_alarm IN ({$alarms})
							AND ta_value::text like '".$second_year_month."%'
							GROUP BY na_value, ta_value
						) AS network_element
						GROUP BY na_value
						HAVING COUNT(na_value) >= ".($selected_mode == 2 ? $nbdaysforpenalisation : "ceil(DATE_PART('days',
						DATE_TRUNC('month', date '".$second_date_pretty."')
						+ '1 MONTH'::INTERVAL + '1 HOUR'::INTERVAL
						- DATE_TRUNC('month', date '".$second_date_pretty."'))*{$ratioforpenalisation})")."
						) b
					ON a.ne_id=b.ne_id
					INNER JOIN edw_object_ref eor ON eor.eor_id=a.ne_id
					WHERE eor_on_off=1
					and in_ref_period = 1
					{$penaltywarningclause}
					GROUP BY a.ne_id, eor.eor_label,a.days_in_fault,b.days_in_fault,b.in_ref_period
					ORDER by a.ne_id ASC	
				";
				//var_dump($sql);
				$result= $db->getAll($sql);
				
				//TODO check if empty
				if($count){
					$warnings=0;
					//$penalties=0;
					//$penaltiesArr = array();
					$warningsArr = array();
					$dateArr = array();
					//count number of ne for each category
					foreach ($result as $row){
						if($row['in_ref_period']==1){
							//$penalties++;
							$warnings++;
							$currentRow = array("cell_id"=>$row['ne_id'],"cell_label"=>$row['ne_label'],"occurences_month-1"=>$row['days_in_fault_m1'],"occurences_month-2"=>$row['days_in_fault_m2']);
							array_push($warningsArr, $currentRow);
							
						}
						
					}
					$result=array("time"=>$current_year_month,"warning"=>$warnings,"csv"=>array("warning"=>$warningsArr));
				}
				
				//var_dump($result);
				
				return $result;
				
			}
	
	public static function getDetailedReport($sdp_id, $alarm_ids, $current_date ){
		
		$result=array();
		$finalResult = array();
		$db = Database::getConnection($sdp_id);
		
		//watch out for february month
		//create timestamp from int date '20121201'
		$ref_date=mktime(0,0,0,substr($current_date,4,2),'01',substr($current_date,0,4));
		//string format for full current date '2012-12-01'
		$current_date_pretty=strftime("%Y-%m-%d",$ref_date);
		//string format for current date '201212'
		$current_year_month=strftime("%Y%m",$ref_date);
		//string format for ref date '201209'
		$ref_date_year_month = date('Ym',strtotime($current_date_pretty . ' -'.$ref_period.' month'));
		
		
		//alarms id list
		//TODO check if empty
		$alarms=implode("','",$alarm_ids);
		$alarms="'".$alarms."'";
		
		
		$sql="
		SELECT distinct edw_alarm.ta_value ,sys_definition_alarm_static.alarm_name, edw_alarm.na_value
		FROM edw_alarm,sys_definition_alarm_static
		WHERE  edw_alarm.id_alarm = sys_definition_alarm_static.alarm_id
		AND id_alarm IN ({$alarms})
		AND ta_value::text LIKE '".$current_year_month."%'
		ORDER BY ta_value";
		
		
		//var_dump($sql);
		$result= $db->getAll($sql);
		
		if($result){	
			//count number of ne for each category
			$header = array('Day of month','Alarm name','Cell ID');
			array_push($finalResult, $header);
			foreach ($result as $row){
					$currentRow = array($row['ta_value'],$row['alarm_name'],$row['na_value']);
					array_push($finalResult, $currentRow);
			}
			
		}
		//var_dump($finalResult);
		return $finalResult;
		
	}
	
	public static function getAlarmOccurence($sdp_id,$current_month,$scale,$alarms,$alarm_name){
		$year = substr($current_month,0,4);
		$month = substr($current_month,4,2);
		$current_month_pretty = $year.'-'.$month;
		 
		$ref_month = AlarmModelHomepage::get_scale_month($scale,$current_month_pretty);
		$months = AlarmModelHomepage::get_months($ref_month, $current_month_pretty);
		//	    var_dump($months);
		$month_list_pretty = implode(',', array_map(array('AlarmModelHomepage','quote'), $months));
		//		var_dump($month_list_pretty);
		
		$alarm_list_pretty = implode(',', array_map(array('AlarmModelHomepage','quote'), $alarms));
		
		
		//		$alarm_list_pretty = substr($alarm_list_pretty,1,-1);
		//		var_dump($alarm_list_pretty);
	
		$sql=  "SELECT month,count_occurence,alarm_name,id_alarm
			  		FROM
			  		(
			    		SELECT month, 
			      		COUNT(ta_value) as count_occurence, 
			       		a.id_alarm 
			    		FROM 
			    		(
			     			SELECT na_value AS network_element,ta_value,
			        		COUNT(ta_value) AS occurence, 
			        		id_alarm AS id_alarm, 
			        		SUBSTRING(ta_value::text FOR 6) as month
			     			FROM edw_alarm 
			     			WHERE alarm_type = 'static' 
			      			AND id_alarm IN (".$alarm_list_pretty.")
			     			AND ta = (
			       				SELECT DISTINCT time AS timeref 
			       				FROM sys_definition_alarm_static
			       			) 
			      			AND na = 'cell' 
			    			GROUP BY na_value, id_alarm, SUBSTRING(ta_value::text FOR 6),ta_value
			    		) a 
			    	GROUP BY a.id_alarm, a.month
			   		) a,
			   	    (
			    		SELECT DISTINCT alarm_id, alarm_name
			    		FROM 
			     		sys_definition_alarm_static
			   		) b
			  		WHERE a.id_alarm = b.alarm_id AND
			  		b.alarm_id IN (".$alarm_list_pretty.") AND
			   		a.month IN (".$month_list_pretty.")
			  		ORDER BY
			   		a.month ASC, alarm_name";
		
		$db = Database::getConnection($sdp_id);
		$results= $db->getAll($sql);
		
		$array_month_pretty = array();
		$array_alarms = array();

		foreach($months as $month){
				//$array_month['month'] = $month;
				array_push($array_month_pretty,array('month'=>$month));
		}
		
		$outputArray = array(); // The results will be loaded into this array.
		$keysArray = array(); // The list of keys will be added here.
		foreach ($array_month_pretty as $innerArray) {
			// Iterate through your array.
			if (!in_array($innerArray['month'], $keysArray)) {
				// Check to see if this is a key that's already been used before.
				$keysArray[] = $innerArray['month']; // If the key hasn't been used before, add it into the list of keys.
				$outputArray[] = $innerArray; // Add the inner array into the output.
			}
		}
		$i = 0;
		sort($outputArray);
		//var_dump(count($results));
		
		if (count($results) > 0) {
			foreach ($outputArray  as $month){
				if(AlarmModelHomepage::search_array($month['month'], $results)) {
					foreach ($results  as $occurences){
						if($occurences['month'] == $month['month']){
							$array_alarms[$occurences['alarm_name']] = $occurences['count_occurence'];
						}	
					}
					
					//afficher 0 si on a pas de donn�es pour l'alarme sur le mois en cours
					foreach($alarm_name as $alarm){
						if(!array_key_exists($alarm, $array_alarms))$array_alarms[$alarm] = 0;
					}
					
					array_push($outputArray[$i],$array_alarms);	
					$i++;
				}
				else{
					foreach($alarm_name as $alarm){
						$array_alarms[$alarm] = 0;
					} 
					array_push($outputArray[$i],$array_alarms);	
					$i++;				
				}	
			}
		}
		else{
			foreach ($outputArray  as $month){
				foreach($alarm_name as $alarm){
					$array_alarms[$alarm] = 0;
				} 
				array_push($outputArray[$i],$array_alarms);	
				$i++;
			}
		}
		//var_dump($outputArray);
		$json = '{"data":[';
		$keys = array();
		
		foreach ($outputArray as $occurences) {
			$json .= '{"month":"'.$occurences['month'].'",';
			// ;
			// var_dump($occurences);
	
			// $keys[] = $col;
			$cols = implode(',',array_keys($occurences[0]));
			$rows = implode(',',$occurences[0]);
			$colsArray = (explode(',',$cols));
			$rowsArray = (explode(',',$rows));
			$j=0;
			$test="";
	
			foreach ($colsArray as $col){
				$test .= '"'.$col.'":"'.$rowsArray[$j].'",';
					
					
				$j++;
			}
			$test = substr($test,0,-2);
			$test .= '"';
			$json .= $test.'},';

		}
		//Remove the last comma
		$json = substr($json, 0, -1);
		
		echo $json.']}';

	}
	
	//permet de transformer un tableau en liste , avec chaque eleement entre quote
	public static function quote($str) {
		return sprintf("'%s'", $str);
	}
	
	
	
	public static function search_array($needle, $haystack) {
	     if(in_array($needle, $haystack)) {
	          return true;
	     }
	     foreach($haystack as $element) {
	          if(is_array($element) && AlarmModelHomepage::search_array($needle, $element))
	               return true;
	     }
	   return false;
	}
	
	/**
	 * 
	 * retourne le mois de r�f�rence par rapport au scale et au mois courant
	 * @param int $scale periode de r�f�rence en mois
	 * @param string $curmonth mois courant ex '201006'
	 * @return string chaine au format '201006'
	 */
	public static function get_scale_month($scale,$curmonth) {
		$time1  = strtotime($curmonth);
		$refmonth = strtotime((date('Y-m-d', $time1).' -'.$scale.'months'));
		return date('Y-m', $refmonth);
	}
	
	/**
	 * 
	 * retoure tous les mois entre entre deux mois (compris)
	 * @param string $refmonth '201206'
	 * @param string $curmonth '201208'
	 * @return array liste des mois en string
	 */
	
	public static function get_months($refmonth, $curmonth) {
		$time1  = strtotime($refmonth);
		$time2  = strtotime($curmonth);
		$my     = date('mY', $time2);
		//echo $my.'</br>';
		 
		$months = array(date('Ym', $time1));
		//var_dump($months).'</br>';
		$f      = '';
	
		while($time1 < $time2) {
			$time1 = strtotime((date('Y-m-d', $time1).' +15days'));
			if(date('Ym', $time1) != $f) {
				$f = date('Ym', $time1);
				if(date('mY', $time1) != $my && ($time1 < $time2))
				$months[] = date('Ym', $time1);
			}
		}
	
		$months[] = date('Ym', $time2);
		return array_unique($months);
	}
	
	
	/**
	*
	* retourne le nom des alarmes par rapport � leur id
	* @param int $sdp_id product id
	* @param array $alarm_ids id des alarmes
	* @return array noms des alarmes
	*/
	public static function get_alarms_name($sdp_id,$alarm_ids) {
		
		$result=array();
		
		$db = Database::getConnection($sdp_id);
		
		$alarms=implode("','",$alarm_ids);
		$alarms="'".$alarms."'";

		$alarm_names_ordered=array();
		
		foreach($alarm_ids as $alarm){
			
			$sql="SELECT DISTINCT alarm_id,alarm_name
						FROM sys_definition_alarm_static
						WHERE alarm_id='{$alarm}'
					";
			
			$result= $db->getAll($sql);
		
			$alarm_names_ordered[]=$result[0];
			
		}

		return $alarm_names_ordered;
	}
	
	
	/**
	 * 
	 * Remplace les valeurs nulles par z�ro
	 * @param unknown_type $n
	 */
	public static function convertNullValueToZero($row){
		
		$row["days_before_penalisation"] = ($row["days_before_penalisation"] < 1 || empty($row["days_before_penalisation"]) ? 0 : $row["days_before_penalisation"]);
		$row["cell_label"] = empty($row["cell_label"]) ? $row['cell_id'] : $row["cell_label"];
		return $row; 
	}
	
	

	
}
