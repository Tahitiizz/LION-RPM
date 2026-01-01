<?php
/**
* Classe principale de calcul des alarmes
*
* Permet de calculer les alarmes
* @author BBX
* @version CB 5.1.0.00
* @package Alarmes
* @since CB 5.1.0.00
*
*
*   23/07/2010 BBX
*       - BZ 16673 : pas de résultats si on calcul uniquement des alarmes dynamiques
*   27/07/2010 BBX
*       - BZ 16555 : Utilisation de $nbDates au lieu de $nbPeriods dans computeAlarm() pour le log Health Indicator
*/
?>
<?php
/**
 * Permet de calculer les alarmes
 * @package test phpDocumentor
 */
class AlarmCompute
{
    /**
    * Table de résultat des alarmes
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    const ALARM_RESULT_TABLE = 'edw_alarm';

    /**
    * Table des détails des résultat des alarmes
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    const ALARM_RESULT_DETAIL_TABLE = 'edw_alarm_detail';

    /**
    * Table des erreurs des résultat des alarmes
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    const ALARM_RESULT_ERROR_TABLE = 'edw_alarm_log_error';

    /**
    * Table de configuration des alarmes statiques
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    const ALARM_DEF_STATIC_TABLE = 'sys_definition_alarm_static';

    /**
    * Table de configuration des alarmes dynamiques
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    const ALARM_DEF_DYNAMIC_TABLE = 'sys_definition_alarm_dynamic';

    /**
    * Table de configuration des alarmes top worst
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    const ALARM_DEF_TOPWORST_TABLE = 'sys_definition_alarm_top_worst';

    /**
    * Template à remplacer par une valeur dans les résultats des alarmes
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    const ALARM_RANK_ALARM_TEMPLATE = '{RANK_ALARM}';

    /**
    * Stocke l'instance de connexion à la base de données
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var object
    */
    protected $_database;

    /**
    * Sauvegarde les périodes à calculer dans l'objet
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_periodsToCalculate = null;

    /**
    * Sauvegarde la liste des alarmes à calculer dans l'objet
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_activeAlarms;

    /**
    * Stocke les résultats de l'alarme prêts à être insérer via COPY
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_edwAlarmTable;

    /**
    * Stocke les détails des résultats de l'alarme prêts à être insérer via COPY
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_edwAlarmDetailTable;

    /**
    * Stocke les objets AlarmCalculation
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var object
    */
    protected $_calculationTypes = null;

    /**
    * Nombre maximal de résultats par date / criticité
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var integer
    */
    protected $_maxResults = 0;

    /**
    * Offset Day
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var integer
    */
    protected $_offsetDay = 0;

    /**
    * Liste des heures à traiter
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_hourToCompute = '';

    /**
    * Compute switch
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_computeSwitch = '';

    /**
    * Séparateur 3ème axe
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_separatorAxe3 = '|s|';

    /**
    * Premier jour du mois (1 = lundi)
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_firstDayOfWeek = 1;

    /**
    * Mémorise le rank d'une alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var integer
    */
    protected $_rankAlarm = null;

    /**
    * Mémorise la date courante
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_now = '';

    /**
    * Mémorise le type d'alarme courant
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_typeToSave = '';

    /**
    * Mémorise l'id de l'alarme courante
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_alarmId = '0';

    /**
    * Mémorise le nom de l'alarme courante
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_alarmName = '';

    /**
    * Mémorise le type de l'alarme courante
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_alarmType = '';

    /**
    * Mémorise la période courante
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_period = '';

    /**
    * Stocke les niveaux d'agrégation
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_networkLevels = array();

    /**
    * Compte le nombre de résultats d'alarmes
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var integer
    */
    protected $_nbResults = 0;

    /**
    * Indique si l'alarme est itérative ou non
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var boolean
    */
    protected $_isIterative = false;

    /**
    * Définit le mode débug
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var boolean
    */
    protected $_debug = false;

    /**
    * Mémorise une éventuelle limite de calcul
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var integer
    */
    protected $_limitResults = 0;

    /**
    * Constructeur
    * @since CB 5.1.0.00
    * @param integer $idProd id du produit
    */
    public function __construct($idProd = 0)
    {
        // Connexion à la base de données
        $this->_database = Database::getConnection($idProd);
        // Mémorisation des périodes à calculer
        $this->_periodsToCalculate = $this->getPeriodsToCalculate();
        // Mémorisation des alarmes à calculer
        $this->_activeAlarms = AlarmModel::getAlarms($idProd);
        // Stocke les objets AlarmCalculation
        $this->_calculationTypes = new SplObjectStorage();
        // Récupération de la date courante
	$this->_now = date('Y-m-d H:i:s');
    }

    /**
    * Passe en mode débug
    * @since CB 5.1.0.00
    * @param Boolean $debug activation / désactivation du mode débug
    */
    public function setDebug($debug = true)
    {
        $this->_debug = $debug;
    }

    /**
    * Affecte un objet AlarmCalculation au compute des alarmes
    * @since CB 5.1.0.00
    * @param AlarmCalculation $obj objet AlarmCalculation
    */
    public function addCalculationType(AlarmCalculation $obj)
    {
        $this->_calculationTypes->attach($obj);
        // Répercute la configuration
        $obj->setFirstDayOfWeek($this->_firstDayOfWeek);
    }

    /**
    * Supprime un objet AlarmCalculation du compute des alarmes
    * @since CB 5.1.0.00
    * @param AlarmCalculation $obj objet AlarmCalculation
    */
    public function removeCalculationType(AlarmCalculation $obj)
    {
        $this->_calculationTypes->detach($obj);
    }

    /**
    * Supprime les résultats des alarmes supprimées et les résultats déjà existants pour les périodes à calculer
    * @since CB 5.1.0.00
    * @return boolean $execCtrl résultat du traitement
    */
    public function cleanResults()
    {
        // Variable de contrôle
        $execCtrl = true;

        // Nombre de résultats supprimés
        $nbResults = 0;

        // Démarrage de la transaction
        $this->_database->execute('BEGIN');

        // Suppression dans edw_alarm
        $query = "DELETE FROM ".self::ALARM_RESULT_TABLE."
            WHERE 
                (id_alarm NOT IN (SELECT DISTINCT alarm_id FROM ".self::ALARM_DEF_STATIC_TABLE.")
                AND alarm_type = 'static')
            OR
                (id_alarm NOT IN (SELECT DISTINCT alarm_id FROM ".self::ALARM_DEF_DYNAMIC_TABLE.")
                AND alarm_type = 'dyn_alarm')
            OR
                (id_alarm NOT IN (SELECT DISTINCT alarm_id FROM ".self::ALARM_DEF_TOPWORST_TABLE.")
                AND alarm_type = 'top-worst')";
        if(count($this->_periodsToCalculate) > 0)
            foreach($this->_periodsToCalculate as $period => $dates)
                $query .= " OR (ta = '".$period."' AND ta_value IN (".implode(',',$dates)."))";
        // Exécution
        $execCtrl = $execCtrl && (!$this->_database->execute($query) ? false : true);

        // Récupération du nombre de lignes supprimées
        if($execCtrl)
            $nbResults = $this->_database->getAffectedRows();

        // Suppression dans edw_alarm_detail
        $query = "DELETE FROM ".self::ALARM_RESULT_DETAIL_TABLE." t0
            WHERE NOT EXISTS (SELECT 1 FROM ".self::ALARM_RESULT_TABLE." WHERE id_result = t0.id_result)";
        // Exécution
        $execCtrl = $execCtrl && (!$this->_database->execute($query) ? false : true);

        // Suppression dans edw_alarm_log_error
        $query = "DELETE FROM ".self::ALARM_RESULT_ERROR_TABLE."
            WHERE 
                (id_alarm NOT IN (SELECT DISTINCT alarm_id FROM ".self::ALARM_DEF_STATIC_TABLE.")
                AND type = 'static')
            OR
                (id_alarm NOT IN (SELECT DISTINCT alarm_id FROM ".self::ALARM_DEF_DYNAMIC_TABLE.")
                AND type = 'dyn_alarm')
            OR
                (id_alarm NOT IN (SELECT DISTINCT alarm_id FROM ".self::ALARM_DEF_TOPWORST_TABLE.")
                AND type = 'top-worst')";
        if(count($this->_periodsToCalculate) > 0)
            foreach($this->_periodsToCalculate as $period => $dates)
                $query .= " OR (ta = '".$period."' AND ta_value IN (".implode(',',$dates)."))";
        // Exécution
        $execCtrl = $execCtrl && (!$this->_database->execute($query) ? false : true);

        // Test de la variable de contrôle et fin de la transaction
        if($execCtrl) $this->_database->execute('COMMIT');
        else $this->_database->execute('ROLLBACK');
        // Retour du résultat
        if(!$execCtrl) {
            return false;
        }
        else {
            return $nbResults;
        }
    }

    /**
    * Récupère et traite les périodes à calculer
    * @since CB 5.1.0.00
    * @return array $periodsToCalculate tableau des périodes à calculer
    */
    protected function getPeriodsToCalculate()
    {
        // Tableau de retour
        $periodsToCalculate = array();
        
        // Récupération des valeurs nécessaires au compute
        $timeToCalculate    = get_time_to_calculate($this->_offsetDay);

        // S'il existe des heures à calculer
        if(!empty($timeToCalculate['hour']))
        {
            // Et qu'il existe des jours à calculer
            if(!empty($timeToCalculate['day'])) {
                // Si on est en compute booster, on récupère la liste des heures intégrées
                if($this->_computeSwitch == 'hourly') {
                    $periodsToCalculate['hour'] = explode($this->_separatorAxe3,$this->_hourToCompute);
                }
                // Sinon on récupère toutes les heures de la journée
                else {
                    for($i = 0; $i <= 23; $i++)
                        $periodsToCalculate['hour'][] = $timeToCalculate['day'].sprintf('%02d', $i);
                }
            }
            // Et qu'il n'existe pas de journées à calculer
            else {
                // On récupère la liste des heures intégrées
                $periodsToCalculate['hour'] = explode($this->_separatorAxe3,$this->_hourToCompute);
            }
        }

        // Mémorisation conditionnelle des autres périodes à calculer
        if(!empty($timeToCalculate['day']))         $periodsToCalculate['day']      = Array($timeToCalculate['day']);
        if(!empty($timeToCalculate['day_bh']))      $periodsToCalculate['day_bh']   = Array($timeToCalculate['day_bh']);
        if(!empty($timeToCalculate['week']))        $periodsToCalculate['week']     = Array($timeToCalculate['week']);
        if(!empty($timeToCalculate['week_bh']))     $periodsToCalculate['week_bh']  = Array($timeToCalculate['week_bh']);
        if(!empty($timeToCalculate['month']))       $periodsToCalculate['month']    = Array($timeToCalculate['month']);
        if(!empty($timeToCalculate['month_bh']))    $periodsToCalculate['month_bh'] = Array($timeToCalculate['month_bh']);

        // Retour des périodes
        return $periodsToCalculate;
    }

    /**
    * Retourne les périodes à calculées desquelles ont été supprimées les périodes exclues d'une alarme
    * @since CB 5.1.0.00
    * @param array $excludedPeriods tableau des périodes eclues du model Alarm
    * @return array $periodsToCalculate tableau final des périodes à calculer
    */
    public function getPeriodsToCalculateWithoutExcludedPeriods($excludedPeriods = array())
    {
        // Tableau de retour
        $periodsToCalculate = $this->_periodsToCalculate;

        // Parcours des périodes à la recherches d'éléments à supprimer
        foreach($this->_periodsToCalculate as $period => $dates)
        {
            foreach($excludedPeriods as $exclusions)
            {
                if($period == 'hour')
                {
                    foreach($dates as $hour)
                    {
                        $day = substr($hour,0,8);
                        $timestampDay = strtotime($day);
                        $numDay = date('N',$timestampDay)-1;

                        if($exclusions['day'] == $numDay)
                        {
                            $numHour = substr($hour,-2);
                            if(in_array($numHour,$exclusions['hour']))
                                unset($periodsToCalculate['hour'][array_search($hour, $periodsToCalculate['hour'])]);
                        }

                    }
                }

                if($period == 'day')
                {
                    foreach($dates as $day)
                    {
                        $timestampDay = strtotime($day);
                        $numDay = date('N',$timestampDay)-1;

                        if($exclusions['day'] == $numDay)
                            unset($periodsToCalculate['day'][array_search($day, $periodsToCalculate['day'])]);
                    }
                }
            }
        }

        // On supprime tous les offset vides
        foreach($periodsToCalculate as $period => $dates)
        {
            if(count($dates) == 0)
                unset($periodsToCalculate[$period]);
        }

        // Retour du tableau
        return $periodsToCalculate;
    }

    /**
    * Permet de tracer un dépassement de résultats
    * @since CB 5.1.0.00
    * @return boolean $execCtrl résultat des éxécutions
    */
    public function setTooManyResults($idAlarm,$typeAlarm,$period,$date,$network,$axe3,$axe3Value,$criticalLevel,$nbResults)
    {
        // Requête d'insertion dans edw_alarm_log_error
        $query = "INSERT INTO ".self::ALARM_RESULT_ERROR_TABLE."
            (id_alarm,ta,ta_value,na,nb_result,type,a3,a3_value,critical_level,calculation_time)
            VALUES ('$idAlarm','$period',$date,'$network','$nbResults','$typeAlarm','$axe3','$axe3Value','$criticalLevel','".$this->_now."')";
        $this->_database->execute($query);
        // Tracelog
        $network = !empty($axe3) ? $network.'_'.$axe3 : $network;
        $message = __T('A_ALARM_CALCULATION_MSG_TRACELOG_ALARM_NOT_INSERTED',ucfirst($this->_alarmType),$this->_alarmName,$criticalLevel,$nbResults,$network,$period,$date);
        sys_log_ast('Critical', 'Trending&Aggregation', 'Alarm Calculation', $message, 'support_1', '');
        // Affichage démon
        displayInDemon('<dd><dd><b>Trop de r&eacute;sultats pour la date "'.$date.'" et la criticit&eacute; "'.(!empty($criticalLevel) ? $criticalLevel : 'aucune').'" ('.$nbResults.')</b>','normal');
    }

    /**
    * Affecte le nombre maximal de résultats
    * @since CB 5.1.0.00
    * @param integer $max nombre de résultats maximum
    */
    public function setMaxResults($max)
    {
        $this->_maxResults = abs((int)$max);
    }

    /**
    * Retourne le nombre maximal de résultats
    * @since CB 5.1.0.00
    * @return integer $max nombre de résultats maximum
    */
    public function getMaxResults()
    {
        return $this->_maxResults;
    }

    /**
    * Affecte l'offset day
    * @since CB 5.1.0.00
    * @param integer $offsetDay offset day
    */
    public function setOffsetDay($offsetDay)
    {
        $this->_offsetDay = abs((int)$offsetDay);
        // Recalcul des périodes à calculer
        $this->_periodsToCalculate = $this->getPeriodsToCalculate();
    }

    /**
    * Retourne l'offset day
    * @since CB 5.1.0.00
    * @return integer $offsetDay offset day
    */
    public function getOffsetDay()
    {
        return $this->_offsetDay;
    }

    /**
    * Affecte les heures à calculer
    * @since CB 5.1.0.00
    * @param string $hour heures à calculer
    */
    public function setHourToCompute($hour)
    {
        $this->_hourToCompute = $hour;
        // Recalcul des périodes à calculer
        $this->_periodsToCalculate = $this->getPeriodsToCalculate();
    }

    /**
    * Retourne les heures à calculer
    * @since CB 5.1.0.00
    * @return string $hourToCompute heures à calculer
    */
    public function getHourToCompute()
    {
        return $this->_hourToCompute;
    }

    /**
    * Affecte le compute switch
    * @since CB 5.1.0.00
    * @param string $computeSwitch compute switch
    */
    public function setComputeSwitch($computeSwitch)
    {
        $this->_computeSwitch = $computeSwitch;
        // Recalcul des périodes à calculer
        $this->_periodsToCalculate = $this->getPeriodsToCalculate();
    }

    /**
    * Retourne le compute switch
    * @since CB 5.1.0.00
    * @return string $computeSwitch compute switch
    */
    public function getComputeSwitch()
    {
        return $this->_computeSwitch;
    }

    /**
    * Affecte le séparateur
    * @since CB 5.1.0.00
    * @param string $separator séparateur
    */
    public function setSeparator($separator)
    {
        $this->_separatorAxe3 = $separator;
        // Recalcul des périodes à calculer
        $this->_periodsToCalculate = $this->getPeriodsToCalculate();
    }

    /**
    * Retourne le séparateur 3ème axe
    * @since CB 5.1.0.00
    * @return string $separatorAxe3 séparateur 3ème axe
    */
    public function getSeparator()
    {
        return $this->_separatorAxe3;
    }

    /**
    * Permet de définir le jour à utiliser comme début de semaine
    * @since CB 5.1.0.00
    * @param integer $firstDayOfWeek premier jours de la semaine
    */
    public function setFirstDayOfWeek($firstDayOfWeek=1)
    {
        $this->_firstDayOfWeek = abs((int)$firstDayOfWeek);
        // Répercute la valeur sur tous les AlarmCompute
        foreach($this->_calculationTypes as $obj) {
            $obj->setFirstDayOfWeek($this->_firstDayOfWeek);
        }
    }

    /**
    * Retourne le jour à utiliser comme début de semaine
    * @since CB 5.1.0.00
    * @return integer $firstDayOfWeek premier jours de la semaine
    */
    public function getFirstDayOfWeek()
    {
        return $this->_firstDayOfWeek;
    }

    /**
    * Retourne le prochain id_result à utiliser pour insérer les résultats
    * @since CB 5.1.0.00
    * @return integer $idresult id_result à utiliser
    */
    protected function getNextIdResult()
    {
        $query = "SELECT CASE WHEN MAX(id_result)+1 IS NULL 
            THEN 1 ELSE MAX(id_result)+1 END AS id_result
            FROM ".self::ALARM_RESULT_TABLE;
        return $this->_database->getOne($query);
    }

    /**
    * Calcul le rank_alarm à affecter à l'alarme en cours de calcul
    * @since CB 5.1.0.00
    * @param string $criticalLevel niveau de criticité
    */
    protected function manageRankAlarm($criticalLevel)
    {
        switch($criticalLevel)
        {
            case 'minor':
                if(empty($this->_rankAlarm))
                    $this->_rankAlarm = 1;
            break;
            case 'major':
                if(empty($this->_rankAlarm) || ($this->_rankAlarm == 1))
                    $this->_rankAlarm = 2;
            break;
            case 'critical':
                if($this->_rankAlarm != 3)
                    $this->_rankAlarm = 3;
            break;
        }
    }

    /**
    * Applique le rank alarme calculé aux valeurs à insérer
    * @param array $edwAlarmTable tableau des résultat de l'alarme
    * @return array $edwAlarmTable même tableau avec rank_alarm à jour
    * @since CB 5.1.0.00
    */
    protected function updateRankAlarm($edwAlarmTable)
    {
        // Valeur à insérer
        $rankAlarm = empty($this->_rankAlarm) ? '\\N' : $this->_rankAlarm;
        // Mise à jour
        foreach($edwAlarmTable as $date => $linesPerCriticity)
            foreach($linesPerCriticity as $criticalLevel => $values) 
                $edwAlarmTable[$date][$criticalLevel] = str_replace(self::ALARM_RANK_ALARM_TEMPLATE, $rankAlarm, $values);
        // Retour du tableau à jour
        return $edwAlarmTable;
    }

    /**
    * Réinitialise la variable de rank alarme
    * @since CB 5.1.0.00
    */
    protected function resetRankAlarm()
    {
        $this->_rankAlarm = null;
    }

    /**
    * Construit le résultat d'une alarme
    * @since CB 5.1.0.00
    * @param array $row résultat de la requête
    * @param integer $idResult id du résultat
    * @return array $alarmResult tableau de résultat
    */
    protected function fetchResults($row, $idResult)
    {
        // Tableau de résultat
        $alarmResult = array();
        // Récupération des valeurs
        $alarmResult['id_alarm']            = $this->_alarmId;
        $alarmResult['id_result']           = $idResult;
        $alarmResult['ta']                  = $this->_period;
        $alarmResult['ta_value']            = $row['ta_value'];
        $alarmResult['na']                  = $this->_networkLevels[0];
        $alarmResult['na_value']            = $row['na_value'];
        $alarmResult['a3']                  = isset($this->_networkLevels[1]) ? $this->_networkLevels[1] : '\\N';
        $alarmResult['a3_value']            = isset($row['a3_value']) ? $row['a3_value'] : '\\N';
        $alarmResult['alarm_type']          = $this->_typeToSave;
        $alarmResult['rank_alarm']          = self::ALARM_RANK_ALARM_TEMPLATE;
        $alarmResult['rank']                = '\\N';
        $alarmResult['critical_level']      = empty($row['critical_level']) ? '\\N' : $row['critical_level'];
        $alarmResult['acknowledgement']     = '0';
        $alarmResult['calculation_time']    = $this->_now;
        $alarmResult['visible']             = '1';
        // Retour de la ligne de résultat
        return $alarmResult;
    }

    /**
    * Construit les détails de résultat d'une alarme
    * @since CB 5.1.0.00
    * @param integer $idResult id du résultat
    * @param string $trigger champ trigger
    * @param string $operand opérateur
    * @param float $value résultat attendu
    * @param float $result résultat calculé
    * @param string $additionnal champ additionnel
    * @param string $type type de donnée
    * @return array $alarmResult tableau de résultat
    */
    protected function fetchResultsDetail($idResult, $trigger, $operand, $value, $result, $additionnal, $type)
    {
        // Tableau de résultat
        $alarmResult = array();
        // Récupération des valeurs
        $alarmResult['id_result']               = $idResult;
        $alarmResult['trigger']                 = trim($trigger == '') ? '\\N' : $trigger;
        $alarmResult['trigger_operand']         = trim($operand == '') ? '\\N' : $operand;
        $alarmResult['trigger_value']           = trim($value == '') ? '\\N' : $value;
        $alarmResult['value']                   = trim($result == '') ? '\\N' : $result;
        $alarmResult['additional_details']      = trim($additionnal == '') ? '\\N' : $additionnal;
        $alarmResult['field_type']              = trim($type == '') ? '\\N' : $type;
        // Retour des réultats
        return $alarmResult;
    }

    /**
    * Traitement des résultats : on vérifie la limite imposée
    * @since CB 5.1.0.00
    * @param array $edwAlarmTable tableau des résultats
    * @param array $edwAlarmDetailTable tableau des détails de résultats
    * @param array $datesTocalculate dates à calculer
    * @return array $datesTocalculate nouveau tableau de dates à calculer
    */
    protected function manageResults($edwAlarmTable,$edwAlarmDetailTable,$datesTocalculate)
    {
        // On vérifie que le nombre de résultat ne dépasse pas la limite
        foreach($edwAlarmTable as $date => $alarmsByCriticity)
        {
            foreach($alarmsByCriticity as $criticityLevel => $results)
            {
                // Doit-on limiter les résultats ?
                if($this->_limitResults > 0)
                {
                    $this->_nbResults -= count($results);
                    $this->_nbResults += 10;
                    $explodedResults = array_chunk($results, $this->_limitResults);
                    $results = $explodedResults[0];
                }

                // Nombre de résultats
                $nbDateResults = count($results);

                // Si trop de résultats
                if($nbDateResults > $this->_maxResults)
                {
                    // On affiche l'erreur
                    $this->setTooManyResults($this->_alarmId,$this->_typeToSave,$this->_period,$date,$this->_networkLevels[0],(isset($this->_networkLevels[1]) ? $this->_networkLevels[0] : ''),'',$criticityLevel,$nbDateResults);

                    // On soustrait ces résultats du compteur global
                    $this->_nbResults -= $nbDateResults;
                }
                // Si le nombre de réultats est accepté
                else
                {
                    // On ajoute les résultats dans le tableau de copie
                    $this->addResults($results);
                    if(isset($edwAlarmDetailTable[$date][$criticityLevel]))
                        $this->addResultsDetail($edwAlarmDetailTable[$date][$criticityLevel]);

                    // On supprime la date des dates à calculer
                    unset($datesTocalculate[array_search($date, $datesTocalculate)]);
                }
            }
        }
        // Retour des dates à calculer
        return $datesTocalculate;
    }

    /**
    * Calcul des alarmes
    * @since CB 5.1.0.00
    * @return boolean $execCtrl résultat des éxécutions
    */
    public function computeAlarm()
    {
        // Prépare l'objet à recevoir des résultats d'alarme
        $this->resetResults();
        $idResult = $this->getNextIdResult();

        // Bouche sur tous les AlarmCalculation
        foreach ($this->_calculationTypes as $alarmCalculation)
        {
            // Récupération du type courant
            $alarmType = $alarmCalculation->getType();

            // 23/07/2010 BBX
            // On test l'existence de $this->_activeAlarms[$alarmType] avant de l'affecter
            $allAlarms = isset($this->_activeAlarms[$alarmType]) ? $this->_activeAlarms[$alarmType] : array();
            
            // Limite de calcul
            $this->_limitResults = $alarmCalculation->getLimitResults();

            // Affichage du type en cours de calcul
            displayInDemon('<i>Alarmes '.$alarmType.'</i>','list');

            // On traite toutes les alarmes
            foreach($allAlarms as $idAlarm)
            {
                // Instance de l'alarme
                $alarmModel = new AlarmModel($idAlarm,$alarmType);
                
                // Axe 3
                $axe3 = GetAxe3($alarmModel->getValue('family'));
                
                // Niveaux d'agrégations
                $networkLevels = array($alarmModel->getValue('network'));
                if($axe3)
                    $networkLevels = explode('_',$alarmModel->getValue('network'));
                $this->_networkLevels = $networkLevels;

                // Id de l'alarme
                $this->_alarmId = $idAlarm;
                // Type de l'alarme
                $this->_alarmType = $alarmType;
                // Nom de l'alarme
                $this->_alarmName = $alarmModel->getValue('alarm_name');
                // Itération de l'alarme
                $this->_isIterative = $alarmModel->isIterative();
                // Temps avant
                $before = microtime(true);
                // Nombre de résultats
                $this->_nbResults = 0;
                // Nombre de périodes traitées
                $nbPeriods = 0;

                // 27/07/2010 BBX
                // Déclaration d'une variable qui va compter les dates traitées
                // BZ 16555
                // Nombre de dates traitées
                $nbDates = 0;

                // Périodes exclues
                $periodsToIgnore = array();
                $excludedPeriods = $alarmModel->getExcludedPeriods();
                $periodsToCalculate = $this->getPeriodsToCalculateWithoutExcludedPeriods($excludedPeriods);

                // Triggers de l'alarme
                $alarmTriggers = $alarmModel->getTriggers();
                // Champs additionnels
                $additionnalFields = $alarmModel->getAdditionalFields();
                // Seuils
                $thresholdField = $alarmModel->getThresholdField();

                // Pour toutes les périodes à calculer
                foreach($periodsToCalculate as $period => $dates)
                {
                    // Si l'alarme n'est pas sur le même niveau temporel, on ne la calcule pas
                    if($alarmModel->getValue('time') != $period)
                        continue;

                    // Période calculée
                    $this->_period = $period;

                    // Affichage de l'alarme en cours de calcul
                    displayInDemon('<dd><u>'.$this->_alarmName.' : '.$period.'</u><br />','normal');

                    // Va mémoriser les dates à calculer
                    $datesTocalculate = $dates;

                    // Type à mémoriser en base
                    $this->_typeToSave = $alarmCalculation->getTypeToSave();

                    // On affecte cette instance à la classe de calcul
                    $alarmCalculation->setAlarm($alarmModel);

                    // Tant que l'on a trop de résultats
                    // Et que l'on a pas atteind 3 recalculs (critique, majeur, mineur)
                    $nbRecalculations = 0;
                    $TooMuchResults = true;
                    while($TooMuchResults && ($nbRecalculations < 3))
                    {
                        // Reset du rank
                        $this->resetRankAlarm();
                        
                        // Va mémoriser les résultats
                        $edwAlarmTable = array();
                        $edwAlarmDetailTable = array();

                        // On récupère la requête de calcul de l'alarme
                        try {
                            $queryCalculation = $alarmCalculation->getCalculationQuery($period,$datesTocalculate,$nbRecalculations);
                        }
                        catch(Exception $e) {
                            displayInDemon($e->getMessage(),'alert');
                        }

                        // Si on a une requête à éxécuter
                        if($queryCalculation !== false)
                        {
                            // Debug de la requête
                            if($this->_debug) {
                                __debug($queryCalculation);
                            }

                            // On éxécute la requête et on récupère les résultats
                            $result = $this->_database->execute($queryCalculation);
                            while($row = $this->_database->getQueryResults($result,1))
                            {
                                // Gestion du rank alarm
                                $this->manageRankAlarm($row['critical_level']);

                                // Résultats
                                $alarmResult = $this->fetchResults($row, $idResult);
                                $edwAlarmTable[$row['ta_value']][$row['critical_level']][]  = implode("\t",$alarmResult);

                                // Valeurs des triggers
                                foreach($alarmTriggers as $criticalLevel => $triggers)
                                {
                                    // Si on est pas sur la criticité calculée, on passe
                                    if($criticalLevel != $row['critical_level'])
                                        continue;

                                    foreach($triggers as $values)
                                    {
                                        // Récupération des résultats
                                        $alarmResult = $this->fetchResultsDetail($idResult,
                                                $values['alarm_trigger_data_field'],
                                                $values['alarm_trigger_operand'],
                                                $values['alarm_trigger_value'],
                                                $row[strtolower($values['alarm_trigger_data_field'])],
                                                $row['additional_details'],
                                                'trigger');
                                        // Empilage des résultats
                                        $edwAlarmDetailTable[$row['ta_value']][$row['critical_level']][] = implode("\t",$alarmResult);
                                    }
                                }

                                // Valeurs des champs additionnels
                                foreach($additionnalFields as $type => $fields)
                                {
                                    foreach($fields as $field)
                                    {
                                        // Récupération des résultats
                                        $alarmResult = $this->fetchResultsDetail($idResult, $field, '', '',
                                                $row[strtolower($field)], '', 'additional');
                                        // Empilage des résultats
                                        $edwAlarmDetailTable[$row['ta_value']][$row['critical_level']][] = implode("\t",$alarmResult);
                                    }
                                }

                                // 23/07/2010 BBX
                                // Ajout des détails du seuil pour les alarmes dynamiques
                                // BZ 16673
                                foreach($thresholdField as $criticalLevel => $thresholds)
                                {
                                    // Si on est pas sur la criticité calculée, on passe
                                    if($criticalLevel != $row['critical_level'])
                                        continue;

                                    foreach($thresholds as $values)
                                    {
                                        // Récupération des résultats
                                        $alarmResult = $this->fetchResultsDetail($idResult,
                                                $values['alarm_field'],
                                                '>',
                                                $values['alarm_threshold'],
                                                $row['threshold_field'],
                                                $row['additional_details'],
                                                'trigger');
                                        // Empilage des résultats
                                        $edwAlarmDetailTable[$row['ta_value']][$row['critical_level']][] = implode("\t",$alarmResult);
                                    }
                                }

                                // Incrémentation de l'idResult et du nombre de résultats
                                $idResult++;
                                $this->_nbResults++;
                            }
                        }

                        // Mise à jour des ranks alarm
                        $edwAlarmTable = $this->updateRankAlarm($edwAlarmTable);

                        // On vérifie que le nombre de résultat ne dépasse pas la limite
                        $datesTocalculate = $this->manageResults($edwAlarmTable,$edwAlarmDetailTable,$datesTocalculate);

                        // Si plus de résultats, on arrête là
                        if(count($edwAlarmTable) == 0) {
                            $TooMuchResults = false;
                            $nbRecalculations = 3;
                        }
                        // Sinon
                        else {
                            // A-t-on encore trop de résultats ?
                            $TooMuchResults = (count($datesTocalculate) > 0);
                            $nbRecalculations++;
                        }
                    }

                    // Incrémentation de la variable qui compte les périodes traitées
                    $nbPeriods++;

                    // 27/07/2010 BBX
                    // On compte les dates traitées
                    // BZ 16555
                    $nbDates += count($dates);
                }

                // Si des périodes on été traitées pour cette alarme, on affiche le temps de calcul
                if($nbPeriods > 0)
                {
                    // Temps après
                    $after = microtime(true);
                    $total = round($after-$before,2);

                    // Affichage démon
                    displayInDemon('<dd><dd>'.$this->_nbResults.' r&eacute;sultats calcul&eacute;s au total en '.$total.'s','normal');

                    // Tracelog pour Health Indicator
                    // 27/07/2010 BBX
                    // Utilisation de $nbDates au lieu de $nbPeriods
                    // BZ 16555
                    $message = "Alarm ".$this->_alarmName." ($idAlarm) calculation time : $total s, $nbDates period(s)";
                    sys_log_ast('Info', 'Trending&Aggregation', '', $message, 'support_1', '');
                }
            }
        }

        // Insertion des alarmes calculées en base
        $this->copyResults();
        $this->resetResults();
    }

    /**
    * Copie les résultats d'alarme en base
    * @since CB 5.1.0.00
    * @return boolean $execCtrl succès de la copie
    */
    protected function copyResults()
    {
        // Variable de controle
        $execCtrl = true;

        // 23/07/2010 BBX
        // Modification de la condition : on accepte désormais d'insérer des résultats de calcul sans détail
        // BZ 16673
        // Si on a des lignes à insérer
        if(!empty($this->_edwAlarmTable))
            $execCtrl &= $this->_database->setTable(self::ALARM_RESULT_TABLE,$this->_edwAlarmTable);

        if(!empty($this->_edwAlarmDetailTable))
            $execCtrl &= $this->_database->setTable(self::ALARM_RESULT_DETAIL_TABLE,$this->_edwAlarmDetailTable);

        // Retour booléen
        return $execCtrl;
    }

    /**
    * Remet à zéro les résultats à insérer
    * @since CB 5.1.0.00
    */
    protected function resetResults()
    {
        $this->_edwAlarmTable = array();
        $this->_edwAlarmDetailTable = array();
    }

    /**
    * Ajoute des résultats à la liste des résultats d'alarme
    * @param array $result tableau de lignes de résultats
    * @since CB 5.1.0.00
    */
    protected function addResults(array $results)
    {
        $this->_edwAlarmTable = array_merge($this->_edwAlarmTable,$results);
    }

    /**
    * Ajoute des détails de résultats à la liste des détails de résultats d'alarme
    * @param array $result tableau de lignes de détails résultats
    * @since CB 5.1.0.00
    */
    protected function addResultsDetail(array $resultsDetail)
    {
        $this->_edwAlarmDetailTable = array_merge($this->_edwAlarmDetailTable,$resultsDetail);
    }

    /**
    * Vacuum les tables de résultat des alarmes
    * @since CB 5.1.0.00
    * @param integer $numTable Table à vacuumer (0 = toutes)
    * @return boolean $execCtrl résultat des éxécutions
    */
    public function vacuum($numTable = 0)
    {
        // Variable de contrôle
        $execCtrl = true;
        // Tables à vacummer
        $query = array();
        if(($numTable == 0) || ($numTable == 1)) $query[] = "VACUUM ANALYSE ".self::ALARM_RESULT_TABLE;
        if(($numTable == 0) || ($numTable == 2)) $query[] = "VACUUM ANALYSE ".self::ALARM_RESULT_DETAIL_TABLE;
        if(($numTable == 0) || ($numTable == 3)) $query[] = "VACUUM ANALYSE ".self::ALARM_RESULT_ERROR_TABLE;
        // Exécution
        foreach($query as $vacuum) {
            $execCtrl = $execCtrl && (!$this->_database->execute($vacuum) ? false : true);
        }
        // Retour du résultat
        return $execCtrl;
    }

    /**
    * Vacuum les tables de résultat des alarmes
    * @since CB 5.1.0.00
    * @return boolean $execCtrl résultat des éxécutions
    */
    public function updateRank()
    {
        // Variable de contrôle
        $execCtrl = true;

        // Démarrage de la transaction
        $this->_database->execute('BEGIN');

        // Mise à jour des rank pour toutes les périodes à calculer
        foreach($this->_periodsToCalculate as $period => $dates)
        {
            // Par chance si on classe par ordre alphabetique critical, major et minor,
            // on a bien cet ordre qui correspond à l'ordre que l'on souhaite obtenir
            // comme pour les top-worst, le calculation time a été complété, dans cette requête on n'a pas besoin
            // de faire un filtre sur les alarmes NON top-worst
            $query = "UPDATE ".self::ALARM_RESULT_TABLE." e
                SET rank = t0.new_rank
                FROM (
                        SELECT ta, na, na_value,
                        CASE WHEN MIN(critical_level) = 'critical' THEN 3
                                ELSE CASE WHEN MIN(critical_level) = 'major' THEN 2
                                ELSE 1 END END AS new_rank
                        FROM ".self::ALARM_RESULT_TABLE."
                        GROUP BY ta, na, na_value
                ) t0
                WHERE calculation_time = '".$this->_now."'
                        AND e.ta = '".$period."'
                        AND e.ta = t0.ta
                        AND e.na = t0.na
                        AND e.na_value = t0.na_value
                        AND e.alarm_type != 'top-worst'";
            // Debug de la requête
            if($this->_debug) {
                __debug($query);
            }
            $execCtrl = $execCtrl && (!$this->_database->execute($query) ? false : true);
        }

        // Test de la variable de contrôle et fin de la transaction
        if($execCtrl) $this->_database->execute('COMMIT');
        else $this->_database->execute('ROLLBACK');

        // Retour du résultat
        return $execCtrl;
    }
}
?>