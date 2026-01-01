<?php
include_once($repertoire_physique_niveau0 . "class/CbCompatibility.class.php");

/**
* Classe de calcul des alarmes dynamiques
*
* Permet de calculer les alarmes dynamiques
* @author BBX
* @version CB 5.1.0.00
* @package Alarmes
* @since CB 5.1.0.00
*
*
*   23/07/2010 BBX
*       - BZ 16673 : pas de r�sultats si on calcul uniquement des alarmes dynamiques
*	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
*	05/05/2015 JLG Mantis 6470 : manage dynamic alarm threshold operand (min/max)
*	29/05/2015 JLG BZ 48621 : [REC][CB 5.3.4.02][TC #TA-62714][Dynamic alarm] Dynamic alarm is NOT raised for the second conditions
*/
?>
<?php
/**
 * Permet de calculer les alarmes dynamiques
 * @package test phpDocumentor
 */
class AlarmCalculationDynamic extends AlarmCalculation
{
    /**
    * D�finition de la p�riode � utiliser pour le calcul dynamic
    * pour chaque agr�gation temporelle
    * @since CB 5.1.0.00
    */
    const NB_HOURS_FOR_DYN_CALCULATION  = 14;
    const NB_DAYS_FOR_DYN_CALCULATION   = 14;
    const NB_WEEKS_FOR_DYN_CALCULATION  = 14;
    const NB_MONTHS_FOR_DYN_CALCULATION = 14;

    /**
    * Stocke la liste des dates � utiliser pour le calcul dynamique
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_dynamicPeriod = array();

    /**
    * Stocke la liste des dates contenant des donn�es
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_existingDates = array();

    /**
    * Stocke la liste des dates exclues
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_excludedPeriods = array();

    /**
    * M�morise la date en cours de calcul
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_currentDate = '';
	
	/**
	 * Product id
	 *
	 * @var string
	 */
	protected $_productId = '';
	
	/**
	 * Can cb manage  threshold operand
	 * 
	 * @var boolean
	 */
	protected $_canManageThresholdOperand;

    /**
     * Default alarming behavior if false / Orange Cameroun behavior if true
     * Mantis 10833
     * @var bool
     */
    private $_switch_alarming_formula;

    /**
     * Ecart
     * Mantis 10833
     * @var string
     */
    private $_ecart;

    /**
    * Constructeur
    * @since CB 5.1.0.00
    */
    public function __construct($idProd = 0)
    {
        // Connexion � la base de donn�es du produit
        $this->_database = Database::getConnection($idProd);
		$this->_productId = $idProd;
		$this->_canManageThresholdOperand = CbCompatibility::canManageThresholdOperand($this->_productId);
        $this->_switch_alarming_formula = get_sys_global_parameters('switch_alarming_formula', false) === "true";
        $this->_ecart = $this->_switch_alarming_formula?"":"/t1.ecart";
        $log = $this->_switch_alarming_formula?'alarming formula switch activated':
            'alarming formula switch deactivated';
        displayInDemon($log);
    }

    /**
    * Retourne le type d'alarme
    * @since CB 5.1.0.00
    * @return string $typeAlarm type de l'alarme
    */
    public function getType()
    {
        return 'dynamic';
    }

    /**
    * Retourne le type d'alarme � sauvegarder dans edw_alarm
    * @since CB 5.1.0.00
    * @return string $typeAlarm type de l'alarme
    */
    public function getTypeToSave()
    {
        return 'dyn_alarm';
    }

    /**
    * Retourne la partie SELECT de la requ�te de calcul
    * @since CB 5.1.0.00
    * @return string $selectCalculation Partie SELECT de la requ�te de calcul
    */
    protected function getSelectPart()
    {
        // SELECT
        $selectCalculation = "SELECT ";

        // ELEMENTS RESEAU
        $prefix = $this->getCommonPrefix();
        for($n = 0; $n < count($this->_networkLevels); $n++) {
            $label = ($n == 0) ? "na_value" : "a3_value";
            $selectCalculation .= $prefix.".".$this->_networkLevels[$n]." AS ".$label.", ";
        }
        $selectCalculation .= $prefix.".".$this->_period." AS ta_value";

        // TRIGGERS
        foreach($this->_alarmTriggers as $criticalLevel => $triggers)
        {
            foreach($triggers as $values) {
                $prefix = $this->getPrefix($values['alarm_trigger_type']);
                $selectCalculation .= ", ".$prefix.".".$values['alarm_trigger_data_field'];
            }
        }

		$commonPrefix = $this->getCommonPrefix();
        // CRITICITE
        if(count($this->_thresholdField) > 0)
        {
            $nbCaseWhen = 0;
            foreach($this->_thresholdField as $criticalLevel => $thresholds)
            {
                // Si on ne doit pas calculer cette criticit�, on passe
                if(in_array($criticalLevel,$this->_criticitiesToIgnore))
                    continue;

                foreach($thresholds as $values)
                {
					$conditionsArray = array();
                    // Calcul
                    $field = $values['alarm_field'];
                    $prefix = $this->getPrefix($values['alarm_field_type']);
                    $calcul = "ABS(1-(ABS(t1.moyenne - {$prefix}.{$field}){$this->_ecart}))";
                    $calcul .= " >= ".($values['alarm_threshold']/100);
					$conditionsArray[] = $calcul;

                    // Trigger
                    $trigger = "";
                    if(isset($this->_alarmTriggers[$criticalLevel][0]))
                    {
                        $triggerValues = $this->_alarmTriggers[$criticalLevel][0];
                        $prefix = $this->getPrefix($triggerValues['alarm_trigger_type']);
                        $trigger = "(".$prefix.".".$triggerValues['alarm_trigger_data_field']." ";
                        $trigger .= $triggerValues['alarm_trigger_operand']." ";
                        $trigger .= $triggerValues['alarm_trigger_value'].")";
						$conditionsArray[] = $trigger;
                    }

					// Threshold condition
					if ($this->_canManageThresholdOperand) {
						$alarm_threshold_operand = $values['alarm_threshold_operand'];
						$alarm_threshold_operand_is_valid = $alarm_threshold_operand != 'both' && $alarm_threshold_operand != '';
						if ($alarm_threshold_operand_is_valid) {
							$operand = ($alarm_threshold_operand == "increase")?">":"<=";
							$conditionsArray[] = "{$commonPrefix}.{$field} {$operand} t1.moyenne";
						}
					}
					
                    // Condition
                    $condition = implode(" AND ", $conditionsArray);

                    // CASE WHEN
                    if($nbCaseWhen == 0) $selectCalculation .= ",\n";
                    $selectCalculation .= " CASE WHEN (".$condition.") THEN '".$values['critical_level']."' ELSE \n";
                    $nbCaseWhen++;
                }
            }
            $selectCalculation .= " NULL\n";
            for($i = 0; $i < $nbCaseWhen; $i++)
                $selectCalculation .= " END";
            $selectCalculation .= " AS critical_level";
        }
        else
        {
            // Pas de criticit�
            $selectCalculation .= ",\n  NULL AS critical_level";
        }

        // CHAMPS ADDITIONNELS
        foreach($this->_additionnalFields as $type => $fields)
        {
            $prefix = $this->getPrefix($type);
            foreach($fields as $field)
            {
                // Si le champ n'a pas encore �t� list�, on l'ajoute
                if(!array_key_exists($field, $this->_alarmTriggers))
                $selectCalculation .= ",\n  ".$prefix.".".$field;
            }
        }

        // SEUIL
        foreach($this->_thresholdField as $criticalLevel => $thresholds)
        {
            foreach($thresholds as $values)
            {
                // S'il existe un trigger, on doit ajouter sa condition
                $trigger = "''";
                if(isset($this->_alarmTriggers[$criticalLevel][0])) {
                    $triggerValues = $this->_alarmTriggers[$criticalLevel][0];
                    $prefix = $this->getPrefix($triggerValues['alarm_trigger_type']);
                    $trigger = $prefix.".".$triggerValues['alarm_trigger_data_field'];
                }

                // Conditions de seuil
                $prefix = $this->getPrefix($values['alarm_field_type']);
                $selectCalculation .= ",\n{$prefix}.".$values['alarm_field']." AS threshold_field";
                $selectCalculation .= ",\n(".$values['alarm_threshold'].")::float AS seuil";
                $selectCalculation .= ",\nABS(1-(ABS(t1.moyenne - {$prefix}.".$values['alarm_field']."){$this->_ecart})) AS calcul";

                // 23/10/2010 BBX
                // Correction de la chaine "additionnal fields"
                // BZ 16673
                $selectCalculation .= ",\nt1.moyenne::text || '@' || (ABS(1-(ABS(t1.moyenne - {$prefix}.".$values['alarm_field']."){$this->_ecart}))*100)::text AS additional_details";
                break 2;
            }
        }
        $selectCalculation .= ",\nt1.moyenne,\nt1.ecart";

        // Retour SELECT
        return $selectCalculation;
    }

    /**
    * Retourne la partie calcul dynamique, de la partie FROM de la requ�te de calcul
    * @since CB 5.1.0.00
    * @return string $fromCalculation Partie dynamique du FROM de la requ�te de calcul
    */
    protected function getFromPartDynamic()
    {
        // CALCUL DYNAMIQUE
        $fromCalculation = "";
        foreach($this->_thresholdField as $criticalLevel => $thresholds) {
            foreach($thresholds as $values) {
                $prefix = $this->getPrefix($values['alarm_field_type']);
                $table = $this->_tables[$prefix];
                $field = $values['alarm_field'];
                break;
            }
            break;
        }

        // NA
        $fromCalculation .= ", (
        SELECT ";
        $t=0;
        foreach($this->_networkLevels as $na) {
            $fromCalculation .= (($t == 0) ? '' : ',').$na;
            $t++;
        }

        // CALCUL
        $fromCalculation .= ",AVG({$field}) as moyenne,
            CASE WHEN stddev_pop({$field}) = 0 THEN NULL ELSE stddev_pop({$field}) END as ecart
	FROM {$table}
	WHERE {$this->_period} IN (".implode(',',$this->_dynamicPeriod).")
	GROUP BY ";

        // GROUP BY
        $t=0;
        foreach($this->_networkLevels as $na) {
            $fromCalculation .= (($t == 0) ? '' : ',').$na;
            $t++;
        }
        $fromCalculation .= ") t1";

        // Retour
        return $fromCalculation;
    }

    /**
    * Retourne la partie FROM de la requ�te de calcul
    * @since CB 5.1.0.00
    * @return string $fromCalculation Partie FROM de la requ�te de calcul
    */
    protected function getFromPart()
    {
        // FROM
        $fromCalculation = "FROM ";

        // TABLES DATA
        $nbT = 0;
        foreach($this->_tables as $prefix => $table) {
            $fromCalculation .= (($nbT == 0) ? "" : ", ").$table." ".$prefix;
            $nbT++;
        }

        // TABLES TOPO
        $nbT = 0;
        foreach($this->_networkLevels as $na) {
            $fromCalculation .= ", edw_object_ref e{$nbT}";
            $nbT++;
        }

        // Partie Dynamique
        $fromCalculation .= $this->getFromPartDynamic();

        // Retour du FROM
        return $fromCalculation;
    }

    /**
    * Retourne les jointures de la requ�te de calcul
    * @since CB 5.1.0.00
    * @return string $whereCalculation jointures de la requ�te de calcul
    */
    protected function getJoinPart()
    {
        // WHERE
        $whereCalculation = "";

        $nbT = 0;
        foreach($this->_networkLevels as $na)
        {
            $lastPrefix = '';
            foreach($this->_tables as $prefix => $table)
            {
                $whereCalculation .= ($whereCalculation == "") ? "WHERE " : " AND ";
                if(!empty($lastPrefix)) {
                    $whereCalculation .= "{$lastPrefix}.{$na} = {$prefix}.{$na} AND ";
                    $whereCalculation .= "{$lastPrefix}.".$this->_period." = {$prefix}.".$this->_period."\nAND ";
                }
                $whereCalculation .= "e{$nbT}.eor_id = {$prefix}.{$na}";
                $lastPrefix = $prefix;
            }
            $nbT++;
        }

        // Jointure avec calcul dynamique
        $nbT = 0;
        foreach($this->_networkLevels as $na) {
            $whereCalculation .= "\nAND e{$nbT}.eor_id = t1.{$na}";
            $nbT++;
        }

        $nbThresholds = 0;
        $thresholdConditions = '';
		$alarm_threshold_where_array = array();
		// 05/05/2015 JLG : mantis 6470
		foreach($this->_thresholdField as $criticalLevel => $thresholds)
        {
            // Si on ne doit pas calculer cette criticit�, on passe
            if(in_array($criticalLevel,$this->_criticitiesToIgnore))
                continue;

            foreach($thresholds as $values)
            {
                $prefix = $this->getPrefix($values['alarm_field_type']);
				$thresholdCondition = "ABS(1-(ABS(t1.moyenne - {$prefix}.".$values['alarm_field']."){$this->_ecart})) >= ".($values['alarm_threshold']/100);
                //$thresholdConditions .= $Op . "\n" . $thresholdCondition;
				if ($this->_canManageThresholdOperand) {
					$alarm_threshold_operand = $values['alarm_threshold_operand'];
					$alarm_threshold_operand_is_valid = $alarm_threshold_operand != 'both' && $alarm_threshold_operand != '';
					// Check alarm threshold operand
					if ($alarm_threshold_operand_is_valid) {
						$alarm_threshold_where_array[] = 
							"\n(CASE WHEN {$prefix}.".$values['alarm_field'] . " > t1.moyenne " .
							" THEN 'increase' ELSE 'decrease' END = '{$alarm_threshold_operand}') " .
							" AND " . $thresholdCondition . " ";
					}
				}
				$nbThresholds++;
            }
        }
		if (count($alarm_threshold_where_array) > 0) {
			$whereCalculation .= " AND (" . implode("OR", $alarm_threshold_where_array) . ")";
		}

        // Retour WHERE
        return $whereCalculation;
    }

    /**
    * Retourne les conditions sur les donn�es de la requ�te de calcul
    * @since CB 5.1.0.00
    * @return string $conditionsCalculation conditions sur les donn�es de la requ�te de calcul
    */
    protected function getFieldConditionPart()
    {
        // TRIGGERS
        $conditionsCalculation = '';
        if(count($this->_alarmTriggers) > 0)
        {
            $nbTriggers = 0;
            $triggersConditions = '';
            foreach($this->_alarmTriggers as $criticalLevel => $triggers)
            {
                // Si on ne doit pas calculer cette criticit�, on passe
                if(in_array($criticalLevel,$this->_criticitiesToIgnore))
                    continue;

                foreach($triggers as $values)
                {
                    $Op = ($nbTriggers > 0) ? " OR " : "    ";
                    $prefix = $this->getPrefix($values['alarm_trigger_type']);
                    $triggersConditions .= $Op.$prefix.".".$values['alarm_trigger_data_field']." ".$values['alarm_trigger_operand']." ".$values['alarm_trigger_value']."\n";
                    $nbTriggers++;
                }
            }
            $conditionsCalculation .= "\nAND (\n";
            $conditionsCalculation .= empty($triggersConditions) ? 'FALSE' : $triggersConditions;
            $conditionsCalculation .= ")";
        }

        // Retour conditions
        return $conditionsCalculation;
    }

    /**
    * Retourne les conditions sur les dates de la requ�te de calcul
    * @since CB 5.1.0.00
    * @param array $dates tableau des dates � calculer
    * @return string $conditionsDates conditions sur les dates de la requ�te de calcul
    */
    protected function getTimeConditionPart($dates)
    {
        $prefix = $this->getCommonPrefix();
        return "\nAND {$prefix}.".$this->_period." = $dates";
    }

    /**
    * Retourne les conditions sur la topologie de la requ�te de calcul
    * @since CB 5.1.0.00
    * @return string $topologyConditions conditions sur la topologie de la requ�te de calcul
    */
    protected function getTopologyConditionPart()
    {
        // Topology Conditions
        $topologyConditions = '';

        // R�cup�ration de tous les niveaux d'agr�gation
        $networkElementSelection = $this->_alarmModel->getNetworkElements($this->_networkLevels[0]);

        // S'il y a une s�lection
        if(count($networkElementSelection) > 0)
            $topologyConditions .= "AND e0.eor_id IN ('".implode("','",$networkElementSelection)."')\n";

        // Filtrage sur l'�l�ment r�seau 1er axe
        $topologyConditions .= "AND e0.eor_obj_type = '".$this->_networkLevels[0]."'\n";

        // Filtrage sur l'�l�ment r�seau 3�me axe
        if($this->_axe3)
            $topologyConditions .= "AND e1.eor_obj_type = '".$this->_networkLevels[1]."'\n";

		// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
        // On ne r�cup�re que les �l�ments r�seau actifs, non blacklist�s et non virtuels
        $limit = ($this->_axe3) ? 1 : 0;
        for($t = 0; $t <= $limit; $t++)
        {
            $topologyConditions .= "AND ".NeModel::whereClauseWithoutVirtual("e{$t}")."\n";
            $topologyConditions .= "AND e{$t}.eor_blacklisted = 0\n";
            $topologyConditions .= "AND e{$t}.eor_on_off = 1\n";
        }

        // Retour des conditions de topologie
        return $topologyConditions;
    }

    /**
    * Retourne le tri sur la requ�te
    * @since CB 5.1.0.00
    * @return string $orderBy tri sur la requ�te
    */
    protected function getOrderBy()
    {
        return "ORDER BY ta_value, critical_level";
    }

    /**
    * Retourne la partie Group By
    * @since CB 5.1.0.00
    * @return string $groupBy groupe sur la requ�te
    */
    protected function getGroupBy()
    {
        return false;
    }

    /**
    * Retourne la limite sur la requ�te
    * @since CB 5.1.0.00
    * @return string $limit tri sur la requ�te
    */
    protected function getLimit()
    {
        return false;
    }

    /**
    * Retourne la liste des dates � utiliser pour le calcul dynamique
    * @since CB 5.1.0.00
    * @return string $orderBy tri sur la requ�te
    */
    protected function getDynamicPeriod()
    {
        // D�termination de l'historique � explorer
        $history = 0;
        switch ($this->_period)
        {
            case 'hour':
                $history = self::NB_HOURS_FOR_DYN_CALCULATION;
                $litteralPeriod = 'hour';
                $litteralDiscontinuePeriod = 'day';
                $timestamp = Date::getTimestampFromHour($this->_currentDate);
                $format = 'YmdH';
            break;
            case 'day':
            case 'day_bh':
                $history = self::NB_DAYS_FOR_DYN_CALCULATION;
                $litteralPeriod = 'day';
                $litteralDiscontinuePeriod = 'week';
                $timestamp = Date::getTimestampFromDay($this->_currentDate);
                $format = 'Ymd';
            break;
            case 'week':
            case 'week_bh':
                $history = self::NB_WEEKS_FOR_DYN_CALCULATION;
                $litteralPeriod = 'week';
                $litteralDiscontinuePeriod = 'month';
                $day = Date::getFirstDayFromWeek($this->_currentDate,$this->_firstDayOfWeek);
                $timestamp = Date::getTimestampFromDay($day);
				// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                $format = 'oW';
            break;
            case 'month':
            case 'month_bh':
                $history = self::NB_MONTHS_FOR_DYN_CALCULATION;
                $litteralPeriod = 'month';
                $litteralDiscontinuePeriod = 'year';
                $timestamp = Date::getTimestampFromFirstDayOfMonth($this->_currentDate);
                $format = 'YM';
            break;
            default:
                return false;
            break;
        }

        // Alarme continue ou discontinue ?
        $isDiscontinuous = ($this->_alarmModel->getValue('discontinuous') == '1');

        // Va stocker les dates calcul�es
        $datesToUse = array();

        // Si l'alarme est continue
        $step = $isDiscontinuous ? $litteralDiscontinuePeriod : $litteralPeriod;

        /**
         * Information
         * Bug PHP en question : probl�me de gestion du changement d'heure
         * lorsque PHP arrive sur une heure concern�e par le changement d'heure,
         * le timestamp n'est plus mis � jour
         */

        // Instanciation d'un objet DateTime pour naviguer dans l'historique
        $dateTime = new DateTime(date('Ymd H:i',$timestamp));

        // On commence par rechercher la date de d�part
        $lastDate = '';
        $dateDepartFound = false;
        while(!$dateDepartFound)
        {
            // Date en cours
            $currentDate = $dateTime->format($format);

            // Pour �viter un bug PHP, on test notre timestamp
            $nbStep = '1';
            if($lastDate == $currentDate)
                $nbStep = '2';

            // Si on est sorti de l'historique
            if(($currentDate < $this->getMinDate()) || ($currentDate > $this->getMaxDate()))
                return false;

            // Si la date existe, on la r�cup�re
            if($this->dateExists($currentDate) && !$this->isDateExcluded($currentDate))
                $dateDepartFound = true;

            // On descend dans l'historique
            // 28/07/2014 GFS - Bug 42236 - [SUP][5.3.1.14][NA][EastLink]Hourly Discontinuous Dynamic alarms are applied on H-1 values not on H values 
            $dateTime->modify("-$nbStep $step");

            // On m�morise le timestamp pour �viter le bug PHP
            $lastDate = $currentDate;
        }

        // La date de d�part a �t� trouv�e, on r�cup�re maintenant nos dates
        $lastDate = '';
        while(count($datesToUse) < $history)
        {
            // Date en cours
            $currentDate = $dateTime->format($format);

            // Pour �viter un bug PHP, on test notre timestamp
            $nbStep = '1';
            if($lastDate == $currentDate)
                $nbStep = '2';

            // Si on est sorti de l'historique
            if(($currentDate < $this->getMinDate()) || ($currentDate > $this->getMaxDate()))
                break;

            // Si la date existe, on la r�cup�re
            if($this->dateExists($currentDate) && !$this->isDateExcluded($currentDate))
                $datesToUse[] = $currentDate;

            // On descend dans l'historique
            $dateTime->modify("-$nbStep $step");

            // On m�morise le timestamp pour �viter le bug PHP
            $lastDate = $currentDate;
        }
        if(count($datesToUse) != $history)
            return false;
        return $datesToUse;
    }

    /**
    * D�termine si une date est exclue ou pas
    * @since CB 5.1.0.00
    * @return boolean $isDateExcluded date exclue ou pas
    */
    protected function isDateExcluded($date)
    {
        // Seules les p�riodes horaires et journali�res peuvent �tres exclues
        switch ($this->_period)
        {
            case 'hour':
                $timestamp = Date::getTimestampFromHour($date);
                $numDay = date('N',$timestamp)-1;
                foreach($this->_excludedPeriods as $exclusion) {
                    if($numDay == $exclusion['day'])
                    {
                        $hour = substr($date,-2);
                        if(in_array($hour,$exclusion['hour']))
                            return true;
                    }
                }
            break;
            case 'day':
            case 'day_bh':
                $timestamp = Date::getTimestampFromDay($date);
                $numDay = date('N',$timestamp)-1;
                foreach($this->_excludedPeriods as $exclusion) {
                    if($numDay == $exclusion['day'])
                        return true;
                }
            break;
        }
        return false;
    }

    /**
    * Retourne un tableau contenant les dates qui existent pour l'alarme
    * @since CB 5.1.0.00
    * @return array $existingDates dates existantes en base
    */
    protected function getExistingDates()
    {
        $existingDates = array();
        foreach($this->_tables as $table) {
            $query = "SELECT ".$this->_period." AS date FROM ".$table." GROUP BY ".$this->_period;
            $result = $this->_database->execute($query);
            while($row = $this->_database->getQueryResults($result,1)) {
                if(!in_array($row['date'],$existingDates))
                    $existingDates[] = $row['date'];
            }
        }
        return $existingDates;
    }

    /**
    * D�termine si une date existe en base
    * @since CB 5.1.0.00
    * @param string $date date � tester
    * @return boolean $exists existance ou non de la date
    */
    protected function dateExists($date) {
        return in_array($date,$this->_existingDates);
    }

    /**
    * R�cup�re la date la plus ancienne avec des donn�es
    * @since CB 5.1.0.00
    * @return integer $date date la plus ancienne
    */
    protected function getMinDate() {
        return min($this->_existingDates);
    }

    /**
    * R�cup�re la date la plus r�cente avec des donn�es
    * @since CB 5.1.0.00
    * @return integer $date date la plus r�cente
    */
    protected function getMaxDate() {
        return max($this->_existingDates);
    }

    /**
    * Permet de savoir si on a des donn�es en base
    * @since CB 5.1.0.00
    * @return boolean $nodata true si donn�es en base
    */
    protected function noData() {
        return (count($this->_existingDates) == 0);
    }

    /**
    * Construit la requ�te de calcul
    * @since CB 5.1.0.00
    * @return string $queryCalculation requ�te de calcul
    */
    protected function buildCalculationQuery()
    {
        // R�cup�ration des dates en base correspondant � cette p�riode
        $this->_existingDates = $this->getExistingDates();

        // P�riodes exclues
        $this->_excludedPeriods = $this->_alarmModel->getExcludedPeriods();

        // Si pas de donn�es en base
        if($this->noData())
            return false;

        // Construction de la requ�te de calcul
        $queryCalculation = '';
        foreach($this->_dates as $date)
        {
            $this->_currentDate = $date;
            $this->_dynamicPeriod = $this->getDynamicPeriod();

            // La p�riode n'est pas compl�te, on ne calcul pas
            if(!$this->_dynamicPeriod) {
                continue;
            }
            
            $query = "(";
            $query .= $this->getSelectPart()."\n";
            $query .= $this->getFromPart()."\n";
            $query .= $this->getJoinPart()."\n";
            $query .= $this->getFieldConditionPart()."\n";
            $query .= $this->getTimeConditionPart($date)."\n";
            $query .= $this->getTopologyConditionPart()."\n";
            $query .= $this->getOrderBy();
            $query .= ")";
            $queryCalculation .= ($queryCalculation == '') ? $query : "\nUNION ALL\n".$query;
        }
		
		displayInDemon("buildCalculationQuery : queryCalculation");
		displayInDemon($queryCalculation);
        return $queryCalculation;
    }
}
?>