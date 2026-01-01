<?php
/**
* Classe de calcul des alarmes statiques
*
* Permet de calculer les alarmes statiques
* @author BBX
* @version CB 5.1.0.00
* @package Alarmes
* @since CB 5.1.0.00
*
*
* 23/07/2010 BBX
*   - BZ 16682 : Correction des condition des triggers
*	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
*
*/
?>
<?php
/**
 * Permet de calculer les alarmes statiques
 * @package test phpDocumentor
 */
class AlarmCalculationStatic extends AlarmCalculation
{
    /**
    * Constructeur
    * @since CB 5.1.0.00
    */
    public function __construct($idProd = 0)
    {
        // Connexion à la base de données du produit
        $this->_database = Database::getConnection($idProd);
    }
    
    /**
    * Retourne le type d'alarme
    * @since CB 5.1.0.00
    * @return string $typeAlarm type de l'alarme
    */
    public function getType()
    {
        return 'static';
    }

    /**
    * Retourne le type d'alarme à sauvegarder dans edw_alarm
    * @since CB 5.1.0.00
    * @return string $typeAlarm type de l'alarme
    */
    public function getTypeToSave()
    {
        return 'static';
    }

    /**
    * Retourne la partie SELECT de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $selectCalculation Partie SELECT de la requête de calcul
    *
    * 23/07/2010 BBX
    * Correction des condition des triggers
    * BZ 16682
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
        $selectCalculation .= ", NULL AS additional_details";

        // TRIGGERS
        foreach($this->_alarmTriggers as $criticalLevel => $triggers)
        {
            foreach($triggers as $values) {
                $prefix = $this->getPrefix($values['alarm_trigger_type']);
                $selectCalculation .= ", ".$prefix.".".$values['alarm_trigger_data_field'];
            }
        }

        // CRITICITE
        // Si triggers présent et criticité existante
        if((count($this->_alarmTriggers) > 0))
        {
            // Puis création des CASE WHEN
            $ecart = "";
            $nbTriggers = 0;
            $selectCalculation .= ",\n";
            foreach($this->_alarmTriggers as $criticalLevel => $triggers)
            {
                // Si on ne doit pas calculer cette criticité, on passe
                if(in_array($criticalLevel,$this->_criticitiesToIgnore))
                    continue;

                // Création des conditions pour chaque niveau de criticité
                $condition = '';
                foreach($triggers as $values)
                {
                    if($condition != '')
                        $condition .= " AND ";
                    
                    $prefix = substr($values['alarm_trigger_type'],0,1);
                    $condition .= $prefix.".".$values['alarm_trigger_data_field']." ".$values['alarm_trigger_operand']." ".$values['alarm_trigger_value'];
                }

                // Construction du CASE WHEN
                $ecart .= " ";
                $selectCalculation .= $ecart."CASE WHEN (".$condition.") THEN '".$values['critical_level']."' ELSE \n";
                $nbTriggers++;

            }
            $selectCalculation .= $ecart."  NULL\n";
            for($i = 0; $i < $nbTriggers; $i++) {
                $selectCalculation .= " END";
            }
            $selectCalculation .= " AS critical_level";
        }
        else
        {
            // Pas de trigger et/ou pas de criticité
            $selectCalculation .= ",\n  NULL AS critical_level";
        }

        // CHAMPS ADDITIONNELS
        foreach($this->_additionnalFields as $type => $fields)
        {
            $prefix = $this->getPrefix($type);
            foreach($fields as $field)
                $selectCalculation .= ",\n  ".$prefix.".".$field;
        }

        // Retour SELECT
        return $selectCalculation;
    }

    /**
    * Retourne la partie FROM de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $fromCalculation Partie FROM de la requête de calcul
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

        // Retour du FROM
        return $fromCalculation;
    }

    /**
    * Retourne les jointures de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $whereCalculation jointures de la requête de calcul
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
                    $whereCalculation .= "{$lastPrefix}.{$na} = {$prefix}.{$na}\nAND ";
                    $whereCalculation .= "{$lastPrefix}.".$this->_period." = {$prefix}.".$this->_period."\nAND ";
                }
                $whereCalculation .= "e{$nbT}.eor_id = {$prefix}.{$na}";
                $lastPrefix = $prefix;
            }
            $nbT++;
        }

        // Retour WHERE
        return $whereCalculation;
    }

    /**
    * Retourne les conditions sur les données de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $conditionsCalculation conditions sur les données de la requête de calcul
    *
    * 23/07/2010 BBX
    * Correction des condition des triggers
    * BZ 16682
    */
    protected function getFieldConditionPart()
    {
        // TRIGGERS
        $conditionsCalculation = '';
        if(count($this->_alarmTriggers) > 0)
        {
            $criticalConditions = '';
            foreach($this->_alarmTriggers as $criticalLevel => $triggers)
            {
                // Si on ne doit pas calculer cette criticité, on passe
                if(in_array($criticalLevel,$this->_criticitiesToIgnore))
                    continue;

                // Conditions des triggers
                $nbTriggers = 0;
                $triggersConditions = '';
                foreach($triggers as $values)
                {
                    $Op = ($nbTriggers > 0) ? " AND " : "";
                    $prefix = $this->getPrefix($values['alarm_trigger_type']);
                    $triggersConditions .= $Op.$prefix.".".$values['alarm_trigger_data_field']." ".$values['alarm_trigger_operand']." ".$values['alarm_trigger_value'];
                    $nbTriggers++;
                }

                // Triggers pour toutes les criticités
                $Op = ($criticalConditions != '') ? "OR " : " ";
                $criticalConditions .= "    $Op(".$triggersConditions.")\n";
            }

            // Construction de la condition complète
            $conditionsCalculation .= "\nAND (\n";
            $conditionsCalculation .= empty($criticalConditions) ? 'FALSE' : $criticalConditions;
            $conditionsCalculation .= ")";
        }

        // Retour conditions
        return $conditionsCalculation;
    }

    /**
    * Retourne les conditions sur les dates de la requête de calcul
    * @since CB 5.1.0.00
    * @param array $dates tableau des dates à calculer
    * @return string $conditionsDates conditions sur les dates de la requête de calcul
    */
    protected function getTimeConditionPart($dates)
    {
        $prefix = $this->getCommonPrefix();
        return "\nAND {$prefix}.".$this->_period." IN (".implode(',',$dates).")";
    }

    /**
    * Retourne les conditions sur la topologie de la requête de calcul
    * @since CB 5.1.0.00
    * @return string $topologyConditions conditions sur la topologie de la requête de calcul
    */
    protected function getTopologyConditionPart()
    {
        // Topology Conditions
        $topologyConditions = '';

        // Récupération de tous les niveaux d'agrégation
        $networkElementSelection = $this->_alarmModel->getNetworkElements($this->_networkLevels[0]);
        
        // S'il y a une sélection
        if(count($networkElementSelection) > 0)
            $topologyConditions .= "AND e0.eor_id IN ('".implode("','",$networkElementSelection)."')\n";

        // Filtrage sur l'élément réseau 1er axe
        $topologyConditions .= "AND e0.eor_obj_type = '".$this->_networkLevels[0]."'\n";

        // Filtrage sur l'élément réseau 3ème axe
        if($this->_axe3)
            $topologyConditions .= "AND e1.eor_obj_type = '".$this->_networkLevels[1]."'\n";

        // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
        // On ne récupère que les éléments réseau actifs, non blacklistés et non virtuels
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
    * Retourne le tri sur la requête
    * @since CB 5.1.0.00
    * @return string $orderBy tri sur la requête
    */
    protected function getOrderBy()
    {
        return "ORDER BY ta_value, critical_level";
    }

    /**
    * Retourne la partie Group By
    * @since CB 5.1.0.00
    * @return string $groupBy groupe sur la requête
    */
    protected function getGroupBy()
    {
        return false;
    }

    /**
    * Retourne la limite sur la requête
    * @since CB 5.1.0.00
    * @return string $limit tri sur la requête
    */
    protected function getLimit()
    {
        return false;
    }

    /**
    * Construit la requête de calcul
    * @since CB 5.1.0.00
    * @return string $queryCalculation requête de calcul
    */
    protected function buildCalculationQuery()
    {
        // Construction de la requête de calcul
        $queryCalculation = $this->getSelectPart()."\n";
        $queryCalculation .= $this->getFromPart()."\n";
        $queryCalculation .= $this->getJoinPart()."\n";
        $queryCalculation .= $this->getFieldConditionPart()."\n";
        $queryCalculation .= $this->getTimeConditionPart($this->_dates)."\n";
        $queryCalculation .= $this->getTopologyConditionPart()."\n";
        $queryCalculation .= $this->getOrderBy();
        return $queryCalculation;
    }
}
?>