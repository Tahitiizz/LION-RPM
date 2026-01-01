<?php
/**
* Classe Abstraite de calcul des alarmes
*
* Permet de calculer les alarmes
* @author BBX
* @version CB 5.1.0.00
* @package Alarmes
* @since CB 5.1.0.00
*/
?>
<?php
/**
 * Permet de calculer les alarmes
 * @package test phpDocumentor
 */
abstract class AlarmCalculation
{
    /**
    * Stocke les niveaux de criticité à ignorer
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_criticitiesToIgnore = array();

    /**
    * Stocke les tables de données à utiliser
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_tables = array();

    /**
    * Stocke les niveaux de criticité à utiliser
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_criticities = array();

    /**
    * Définit si on utilise le 3ème axe ou pas
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var boolean
    */
    protected $_axe3 = false;

    /**
    * Stocke les niveaux d'agrégation à utiliser
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_networkLevels = array();

    /**
    * Stocke les triggers de l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_alarmTriggers = array();

    /**
    * Stocke les champs additionnels de l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_additionnalFields = array();

    /**
    * Stocke la sélection des éléménts réseau pour l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_neSelection = array();

    /**
    * Stocke le group table correspondant à l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_groupTable = '';

    /**
    * Stocke la période correspondant à l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_period = '';

    /**
    * Stocke les dates à calculer
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_dates = array();

    /**
    * Stocke un instance de la classe AlarmModel
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var object
    */
    protected $_alarmModel = null;

    /**
    * Mémorise l'id de l'alarme en cours de calcul
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $_alarmId = '0';

    /**
    * Stocke les champs de tri de l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_sortField = array();

    /**
    * Stocke les champs de seuil de l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    protected $_thresholdField = array();

    /**
    * Mémorise le premier jour de semaine à utiliser (1=lundi)
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var integer
    */
    protected $_firstDayOfWeek = 1;

    /**
    * Indique une limite au nombre de résultats à récupérer
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var integer
    */
    protected $_limitResults = 0;

    /**
    * Stocke l'instance de connexion à la base de données
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var object
    */
    protected $_database;
    
    /**
     * Liste des méthodes que doit implémenter une classe
     * Pour se définir comme classe de calcul des alarmes
     */

    /**
    * Retourne le type d'alarme
    * @since CB 5.1.0.00
    * @return string $typeAlarm type de l'alarme
    */
    abstract protected function getType();

    /**
    * Retourne le type d'alarme à sauvegarder dans edw_alarm
    * @since CB 5.1.0.00
    * @return string $typeAlarm type de l'alarme
    */
    abstract protected function getTypeToSave();

    /**
    * Retourne la partie SELECT de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $selectCalculation Partie SELECT de la requête de calcul
    */
    abstract protected function getSelectPart();

    /**
    * Retourne la partie FROM de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $fromCalculation Partie FROM de la requête de calcul
    */
    abstract protected function getFromPart();

    /**
    * Retourne les jointures de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $whereCalculation jointures de la requête de calcul
    */
    abstract protected function getJoinPart();

    /**
    * Retourne les conditions sur les données de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $conditionsCalculation conditions sur les données de la requête de calcul
    */
    abstract protected function getFieldConditionPart();

    /**
    * Retourne les conditions sur la topologie de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $topologyConditions conditions sur la topologie de la requête de calcul
    */
    abstract protected function getTopologyConditionPart();

    /**
    * Retourne les conditions sur les dates de la requête de calcul
    * @since CB 5.1.0.00
    * @param array $dates tableau des dates à calculer
    * @return string $conditionsDates conditions sur les dates de la requête de calcul
    */
    abstract protected function getTimeConditionPart($dates);

    /**
    * Retourne la partie Group By
    * @since CB 5.1.0.00
    * @return string $groupBy groupe sur la requête
    */
    abstract protected function getGroupBy();

    /**
    * Retourne la limite sur la requête
    * @since CB 5.1.0.00
    * @return string $limit tri sur la requête
    */
    abstract protected function getLimit();

    /**
    * Retourne le tri sur la requête
    * @since CB 5.1.0.00
    * @return string $orderBy tri sur la requête
    */
    abstract protected function getOrderBy();

    /**
    * Construit la requête de calcul
    * @since CB 5.1.0.00
    * @return string $queryCalculation requête de calcul
    */
    abstract protected function buildCalculationQuery();

    /**
     * Fin méthodes abstraites
     */

    /**
    * Affecte un objet AlarmModel à la classe
    * @since CB 5.1.0.00
    * @param object $alarmModel Instance de AlarmModel
    */
    public function setAlarm(AlarmModel $alarmModel)
    {
        if($alarmModel->getValue('alarm_type') != $this->getType())
            throw new BadMethodCallException('Calling '.$this->getType().' calculation on non '.$this->getType().' Alarm');
        $this->_alarmModel  = $alarmModel;
        $this->_alarmId     = $alarmModel->getValue('alarm_id');
        $this->_period      = $alarmModel->getValue('time');

        // Récupération des composantes de l'alarme
        $this->configureAlarmComponents();

        // Configuration des paramètres de la famille
        $this->configureFamily();

        // Récupération des tables
        $this->_tables = $this->getTables();
    }

    /**
    * Récupère le préfixe en fonction d'un type (raw ou kpi)
    * @since CB 5.1.0.00
    * @param string $type type de donnée
    * @return string $prefixe préfixe à utiliser
    */
    protected function getPrefix($type)
    {
        return ($type == 'raw') ? 'r' : 'k';
    }

    /**
    * Récupère le préfixe par défaut
    * @since CB 5.1.0.00
    * @return string $prefixe préfixe à utiliser
    */
    protected function getCommonPrefix()
    {
        $prefixes = array_keys($this->_tables);
        return $prefixes[0];
    }

    /**
    * Récupère un tableau contenant les niveaux de criticité à exclure
    * en fonction du nombre de recalculs
    * @since CB 5.1.0.00
    * @return integer $nbRecalculation nombre de recalculs
    */
    protected function getCriticitiesToIgnore($nbRecalculation)
    {
        // Criticités à ne pas calculer
        $criticitiesToIgnore = array();
        for($c = 1; $c <= $nbRecalculation; $c++) {
            if(isset($this->_criticities[$c-1]))
               $criticitiesToIgnore[] = $this->_criticities[$c-1];
        }
        return $criticitiesToIgnore;
    }

    /**
    * Retourne les tables de données à utiliser pour la requête
    * @since CB 5.1.0.00
    * @return string $tables conditions supplémentaires
    */
    protected function getTables()
    {
        // Définition des tables
        $rawTable = false;
        $kpiTable = false;

        // Champs triggers
        foreach($this->_alarmTriggers as $criticalLevel => $triggers)
        {
            foreach($triggers as $values) {
                if($values['alarm_trigger_type'] == 'raw') $rawTable = true;
                if($values['alarm_trigger_type'] == 'kpi') $kpiTable = true;
            }
        }

        // Additionnal Fields
        foreach($this->_additionnalFields as $type => $fields)
        {
            if($type == 'raw') $rawTable = true;
            if($type == 'kpi') $kpiTable = true;
        }

        // Sort Field
        if(count($this->_sortField) > 0)
        {
            if($this->_sortField['list_sort_field_type'] == 'raw') $rawTable = true;
            if($this->_sortField['list_sort_field_type'] == 'kpi') $kpiTable = true;
        }

        // Seuil
        foreach($this->_thresholdField as $criticalLevel => $thresholds)
        {
            foreach($thresholds as $values) {
                if($values['alarm_field_type'] == 'raw') $rawTable = true;
                if($values['alarm_field_type'] == 'kpi') $kpiTable = true;
            }
        }

        // Tables
        $tables = array();

        // Définition des tables
        if($rawTable) $tables[$this->getPrefix('raw')] = $this->_groupTable."_raw_".$this->_alarmModel->getValue('network')."_".$this->_period;
        if($kpiTable) $tables[$this->getPrefix('kpi')] = $this->_groupTable."_kpi_".$this->_alarmModel->getValue('network')."_".$this->_period;

        // Retour des tables
        return $tables;
    }

    /**
    * Permet de modifier la liste des tables (pour tests)
    * @param array $tables tables à utiliser pour le calcul
    * @since CB 5.1.0.00
    */
    public function setTables(array $tables)
    {
        $this->_tables = $tables;
    }

    /**
    * Récupère et stocke le paramétrage de la famille de l'alarme
    * @since CB 5.1.0.00
    */
    protected function configureFamily()
    {
        $this->_axe3              = GetAxe3($this->_alarmModel->getValue('family'));
        $this->_networkLevels     = array($this->_alarmModel->getValue('network'));
        if($this->_axe3)
            $this->_networkLevels = explode('_',$this->_alarmModel->getValue('network'));

        // Récupération du group table
        $query = "SELECT edw_group_table
            FROM sys_definition_group_table
            WHERE family = '".$this->_alarmModel->getValue('family')."'";
        $this->_groupTable = $this->_database->getOne($query);
    }

    /**
    * Récupère et stocke les différents composants de l'alarme
    * @since CB 5.1.0.00
    */
    protected function configureAlarmComponents()
    {
        // Triggers de l'alarme
        $this->_alarmTriggers       = $this->_alarmModel->getTriggers();
        // Champs additionnels
        $this->_additionnalFields   = $this->_alarmModel->getAdditionalFields();
        // Criticités
        $this->_criticities         = $this->_alarmModel->getCriticities();
        // NE Selection
        $this->_neSelection         = $this->_alarmModel->getNetworkElementSelection();
        // Champ de tri
        $this->_sortField           = $this->_alarmModel->getSortField();
        // Champ de seuil
        $this->_thresholdField      = $this->_alarmModel->getThresholdField();
    }

    /**
    * Permet de définir le jour à utiliser comme début de semaine
    * @since CB 5.1.0.00
    * @param integer $firstDayOfWeek premier jours de la semaine
    */
    public function setFirstDayOfWeek($firstDayOfWeek=1)
    {
        if($firstDayOfWeek > 6) $firstDayOfWeek = 1;
        $this->_firstDayOfWeek = abs((int)$firstDayOfWeek);
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
    * Retourne la limite à appliquer au résultats
    * @since CB 5.1.0.00
    * @return integer $_limitResults limite de résultats
    */
    public function getLimitResults()
    {
        return $this->_limitResults;
    }

    /**
    * Récupère la requête de calcul de l'alarme
    * @since CB 5.1.0.00
    * @param string $period résolution temporelle
    * @param array $dates dates sur lesquelles filtrer
    * @param integer $nbRecalculation nombre de recalcul de l'alarme
    * @return string $queryCalculation Requête de calcul
    */
    public function getCalculationQuery($period,$dates,$nbRecalculation)
    {
        // On ne peut pas débuter le calcul sans modèle alarm
        if($this->_alarmModel == null)
            throw new RuntimeException('Object AlarmModel missing. Cannot generate query.');

        // Mémorisation des dates
        $this->_dates             = $dates;

        // Criticités à ne pas calculer
        $this->_criticitiesToIgnore = $this->getCriticitiesToIgnore($nbRecalculation);

        // Si tous les niveaux de criticités sont ignorées, il n'y a plus de raisons de lancer la requête
        if(count($this->_criticities) > 0)
            if(count($this->_criticities) == count($this->_criticitiesToIgnore))
                return false;

        // Récupération de la requête de calcul
        $queryCalculation = $this->buildCalculationQuery();
        if(empty($queryCalculation))
            $queryCalculation = false;

        // Retour de la requête de calcul
        return $queryCalculation;
    }
}
?>