<?php
/*
 *
 *	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 *
*/
include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculation.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculationDynamic.class.php');

class TestAlarmCalculationDynamic extends PHPUnit_Framework_TestCase
{
    /**
     * Récupère les informations nécessaires à la création de l'alarme de test
     */
    public function getConfAlarm()
    {
        // TA
        $ta = getTaList();
        $ta = array_keys($ta);
        $this->ta = $ta[0];
        // FAMILY
        $this->family = getFamilyFromIdGroup(1);
        // NA
        $this->na = get_network_aggregation_min_from_family($this->family);
        // AXE 3
        $this->axe3 = GetAxe3($this->family);
        if($this->axe3) $this->na3 .= get_network_aggregation_min_axe3_from_family($this->family);
    }

    /**
     * Insère l'alarme de test en base
     * @param integer $alarmThreshold valeur de seuil
     * @param integer $triggerValue valeur de trigger
     * @return string id de l'alarme
     */
    public function generateAlarm($alarmThreshold = 5, $triggerValue = 10)
    {
        $na = $this->na;
        if($this->axe3) $na .= '_'.$this->na3;
        // ID ALARM
        $idAlarm = generateUniqId ('sys_definition_alarm_dynamic');
        // ALARM NAME
        $alarmName = 'Alarm Test';
        // ALARM FIELD
        $alarmField = 'counter_field';
        // ADDITIONNAL
        $alarmAdditional = 'counter_additional';
        // TRIGGER
        $alarmTrigger = 'counter_trigger';
        // DB
        $db = Database::getConnection();
        // INSERT
        $query = "INSERT INTO sys_definition_alarm_dynamic
        (alarm_id,alarm_name,alarm_field,alarm_field_type,alarm_threshold,network,additional_field,additional_field_type,time,id_group_table,hn_value,family,alarm_time_frame,hn_to_sel,discontinuous,internal_id,client_type,alarm_trigger_data_field,alarm_trigger_type,alarm_trigger_operand,alarm_trigger_value,description,critical_level,nb_iteration,period,on_off)
        VALUES
        ('$idAlarm','$alarmName','$alarmField','raw','$alarmThreshold','$na','$alarmAdditional','raw','$this->ta',1,NULL,'$this->family',14,NULL,0,NULL,'customisateur','$alarmTrigger','raw','>=',$triggerValue,'Test Alarm','critical',1,1,1)";
        $db->execute($query);
        // ID
        return $idAlarm;
    }

    /**
     * Détruit l'alarme de test
     */
    public function cleanAlarm()
    {
        // DB
        $db = Database::getConnection();
        // NE SEL
        $query = "DELETE FROM sys_definition_alarm_network_elements
            WHERE alarm_id IN (SELECT alarm_id FROM sys_definition_alarm_dynamic
                WHERE alarm_name = 'Alarm Test')
            AND type_alarm = 'dynamic'";
        $db->execute($query);
        // DELETE
        $query = "DELETE FROM sys_definition_alarm_dynamic WHERE alarm_name = 'Alarm Test'";
        $db->execute($query);
    }

    /**
     * Créé la table de données de test
     */
    public function generateTestTable()
    {
        $db = Database::getConnection();
        // CREATE TABLE
        $query = "CREATE TABLE edw_alarm_test (
            $this->na TEXT,
            ".($this->axe3 ? $this->na3.' TEXT,' : '')."
            $this->ta integer,
            counter_field real,
            counter_additional real,
            counter_trigger real
        )";
        $db->execute($query);
    }

    /**
     * Détruit la table de données de test
     */
    public function dropTestTable()
    {
        // DB
        $db = Database::getConnection();
        // DROP
        $query = "DROP TABLE IF EXISTS edw_alarm_test";
        $db->execute($query);
    }

    /**
     * Vide la table de données de test
     */
    public function cleanTestData()
    {
        // DB
        $db = Database::getConnection();
        // DROP
        $query = "TRUNCATE edw_alarm_test";
        $db->execute($query);
    }

    /**
     * Génère une sélection d'éléments réseau pour l'alarme de test
     * @param array $topologyElements éléments réseau utilisés
     * @param integer $nbElements nombre d'éléments réseau à prendre pour la sélection
     */
    public function addNeSelection(array $topologyElements, $nbElements = 1)
    {
        // DB
        $db = Database::getConnection();
        // ID de l'alarme
        $query = "SELECT alarm_id FROM sys_definition_alarm_dynamic
            WHERE alarm_name = 'Alarm Test'";
        $alarmId = $db->getOne($query);
        // TOPOLOGY ELEMENTS
        shuffle($topologyElements);
        for($e = 0; $e < $nbElements; $e++)
        {
            $ne = $topologyElements[$e];
            $query = "INSERT INTO sys_definition_alarm_network_elements
                (alarm_id,type_alarm,na,na_value)
                VALUES ('$alarmId','dynamic','$this->na','$ne')";
            $db->execute($query);
        }
    }

    /**
     * Ajoute une ligne de données dans la table de données de test
     * @param array $array
     */
    public function addTestData(array $array)
    {
        // DB
        $db = Database::getConnection();
        // QUERY
        $query = "INSERT INTO edw_alarm_test
            ($this->na,$this->ta,counter_field,counter_additional,counter_trigger)
            VALUES
            ('{$array[0]}','{$array[1]}',{$array[2]},{$array[3]},{$array[4]})";
        if($this->axe3)
        $query = "INSERT INTO edw_alarm_test
            ($this->na,$this->na3,$this->ta,counter_field,counter_additional,counter_trigger)
            VALUES
            ('{$array[0]}','{$array[1]}','{$array[2]}',{$array[3]},{$array[4]},{$array[5]})";
        $db->execute($query);
    }

    /**
     * Récupère une liste d'éléments réseau existants en topologie
     * @param string $level
     * @param integer $nb
     * @return array éléments réseau
     */
    public function getTopologyElements($level, $nb)
    {
        // DB
        $db = Database::getConnection();
        $nElements = array();
        // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
        // QUERY
        $query = "SELECT eor_id FROM edw_object_ref
            WHERE eor_obj_type = '$level'
            AND eor_on_off = 1
            AND ".NeModel::whereClauseWithoutVirtual()."
            AND eor_blacklisted = 0
            LIMIT $nb";
        $result = $db->execute($query);
        while($row = $db->getQueryResults($result,1)) {
            $nElements[] = $row['eor_id'];
        }
        return $nElements;
    }

    /**
     * Blacklist certains éléments réseau
     * @param array $neSelection
     * @param <type> $nbBlacklistedElements
     */
    public function blacklistElements(array $neSelection, $nbBlacklistedElements = 1)
    {
        // DB
        $db = Database::getConnection();
        // TOPOLOGY ELEMENTS BLACKLIST
        shuffle($neSelection);
        for($e = 0; $e < $nbBlacklistedElements; $e++)
        {
            $ne = $neSelection[$e];
            $query = "UPDATE edw_object_ref
                SET eor_blacklisted = 1
                WHERE eor_id = '$ne'
                AND eor_obj_type = '{$this->na}'";
            $db->execute($query);
        }
    }

    /**
     * Remet les éléments réseau en liste blanche
     */
    public function whiteListElements()
    {
        // DB
        $db = Database::getConnection();
        // WHITELIST
        $query = "UPDATE edw_object_ref
            SET eor_blacklisted = 0
            WHERE eor_blacklisted = 1";
        $db->execute($query);
    }

    /**
     * génère la requête de calcul
     * @param integer $nbExpectedResults nombre de résultats désirés
     * @param float $ecartType écart type à respecter
     * @param float $moyenne moyenne à respecter
     * @param float $referenceValue valeur de référence
     * @param float $alarmThreshold valeur de seuil
     * @param array $triggerParams trigger ('value' => valeur du trigger, 'min' => valeur aléatoire minimale, 'max' => valeur aléatoire maximale)
     * @return string Requête de calcul
     */
    public function getAlarmQuery($nbExpectedResults,$ecartType,$moyenne,$referenceValue,$alarmThreshold,array $triggerParams = array(),$neSelection = 0,$nbBlacklistedElements = 0)
    {
        // INIT VALUES
        $this->getConfAlarm();

        // GENERATES ALARM
        $this->cleanAlarm();
        $triggerValue = isset($triggerParams['value']) ? $triggerParams['value'] : 10;
        $idAlarm = $this->generateAlarm($alarmThreshold,$triggerValue);

        $this->dropTestTable();
        $this->generateTestTable();

        // TOPOLOGY
        $nElements = $this->getTopologyElements($this->na, $nbExpectedResults);
        if($this->axe3)
            $nElements3 = $this->getTopologyElements($this->na3, $nbExpectedResults);

        // TRIGGER
        $triggerMinValue = isset($triggerParams['min']) ? $triggerParams['min'] : 100;
        $triggerMaxValue = isset($triggerParams['max']) ? $triggerParams['max'] : 200;

        // PERIOD FOR CALCULATION
        $periodForCalculation = AlarmCalculationDynamic::NB_HOURS_FOR_DYN_CALCULATION;
        if($this->ta == 'day') $periodForCalculation = AlarmCalculationDynamic::NB_DAYS_FOR_DYN_CALCULATION;
        $periodForCalculation += 1;

        // NE SELECTION
        if($neSelection > 0) {
            $this->addNeSelection($nElements,$neSelection);
        }

        // BLACKLIST
        if($nbBlacklistedElements > 0) {
            $this->blacklistElements($nElements, $nbBlacklistedElements);
        }

        // DATA
        $dates = array();
        // 15 périodes par défaut doivent être insérées
        // (14 pour le calcul + 1 pour les données de référence)
        for($d = 1; $d <= $periodForCalculation; $d++)
        {
            // Construction de la date à insérer
            $taValue = date("YmdH",strtotime("-$d hour"));
            if($this->ta == 'day') $taValue = date("Ymd",strtotime("-$d day"));
                 
            // On mémorise les dates générées pour une utilisation ultérieure
            $dates[] = $taValue;

            // Première date = valeur de références
            if($d == 1) {
                $fieldValue = $referenceValue;
            }
            // Autres dates = valeurs pour le calcul
            else {
                // On fait varier les valeurs pour avoir un écart type
                // mais on s'arrange pour avoir toujours la même moyenne
                $fieldValue = $moyenne - $ecartType;
                if($d%2 == 0) $fieldValue = $moyenne + $ecartType;
            }

            // On rempli la table de données de test
            for($i = 1; $i <= $nbExpectedResults; $i++)
            {
                // Cas standard 1er axe
                $array = array($nElements[$i-1],$taValue,$fieldValue,mt_rand(0, 99),mt_rand($triggerMinValue, $triggerMaxValue));
                // Cas si 3eme axe
                if($this->axe3)
                $array = array($nElements[$i-1],$nElements3[$i-1],$taValue,$fieldValue,mt_rand(0, 99),mt_rand($triggerMinValue, $triggerMaxValue));
                // Insertion des valeurs
                $this->addTestData($array);
            }
        }

        // ALARM
        $myAlarm = new AlarmModel($idAlarm, 'dynamic');
        $this->alarmCalculation->setAlarm($myAlarm);

        // On doit calculer sur notre table de test
        $this->alarmCalculation->setTables(array('r' => 'edw_alarm_test'));

        // Récupération de la requête
        $query = $this->alarmCalculation->getCalculationQuery($this->ta,array($dates[0]),0);
        return $query;
    }

    /* TEST BEGIN */

    /**
     * Configuration des tests
     */
    public function setUp()
    {
        $this->alarmCalculation = new AlarmCalculationDynamic();
    }

    /**
     * Suppression des tests
     */
    public function tearDown()
    {
        // CLEAN DB
        $this->dropTestTable();
        $this->cleanAlarm();
    }

    /**
     * Test vistant à vérifier que l'objet AlarmCalculationDynamic
     * retourne bien les bon types
     */
    public function testType()
    {
        $this->assertEquals('dynamic', $this->alarmCalculation->getType());
        $this->assertEquals('dyn_alarm', $this->alarmCalculation->getTypeToSave());
    }

    /**
     * Test visant à vérifier que seule une alarme dynamic peut être calculée par
     * un objet AlarmCalculationDynamic
     */
    public function testSetAlarmModel()
    {
        $myAlarm = $this->getMock('AlarmModel',array('__construct'),array('0','static'));
        $this->setExpectedException('BadMethodCallException');
        $this->alarmCalculation->setAlarm($myAlarm);

        $myAlarm = $this->getMock('AlarmModel',array('__construct'),array('0','dynamic'));
        $this->alarmCalculation->setAlarm($myAlarm);
    }

    /**
     * Test visant à vérifier qu'une alarme dynamique saute correctement
     * Lorsque le seuil < résultat du calcul dynamique
     */
    public function testCalculationWithAlarmThatMustRaise()
    {
        // DB
        $db = Database::getConnection();

        // On lance 20 tests avec valeurs aléatoires pour obtenir une validation pertinente
        for($t = 0; $t <= 20; $t++)
        {
            // EXPECTED RESULTS
            $nbExpectedResults = mt_rand(10, 50);

            // REFERENCE VALUE
            $referenceValue = mt_rand(1, 99);

            // ECART TYPE DESIRE
            $ecartType = mt_rand(1, 10);

            // MOYENNE DESIREE
            $moyenne = mt_rand(1, 20);

            // DYNAMIC CALCULATION
            $dynamicResult = abs(1-(abs($referenceValue - $moyenne)/$ecartType))*100;

            // POUR QUE L'ALARME SAUTE, IL FAUT QUE NOTRE ALARME EST UN SEUIL < $dynamicResult
            $alarmThreshold = $dynamicResult - 1;

            // GET QUERY
            $query = $this->getAlarmQuery($nbExpectedResults,$ecartType,$moyenne,$referenceValue,$alarmThreshold);
            $result = $db->execute($query);

            // L'ALARME DOIT SAUTER ET RENVOYER $nbExpectedResults RESUTATS
            $this->assertEquals($nbExpectedResults, $db->getNumRows());

            // CLEAN DB
            $this->dropTestTable();
            $this->cleanAlarm();
        }
    }

    /**
     * Test visant à vérifier qu'une alarme dynamique ne saute pas
     * Lorsque le seuil > résultat du calcul dynamique
     */
    public function testCalculationWithAlarmThatMustNotRaise()
    {
        // DB
        $db = Database::getConnection();

        // On lance 20 tests avec valeurs aléatoires pour obtenir une validation pertinente
        for($t = 0; $t <= 20; $t++)
        {
            // EXPECTED RESULTS
            $nbExpectedResults = mt_rand(10, 50);

            // REFERENCE VALUE
            $referenceValue = mt_rand(1, 99);

            // ECART TYPE DESIRE
            $ecartType = mt_rand(1, 10);

            // MOYENNE DESIREE
            $moyenne = mt_rand(1, 20);

            // DYNAMIC CALCULATION
            $dynamicResult = abs(1-(abs($referenceValue - $moyenne)/$ecartType))*100;

            // POUR QUE L'ALARME NE SAUTE PAS, IL FAUT QUE NOTRE ALARME EST UN SEUIL > $dynamicResult
            $alarmThreshold = $dynamicResult + 1;

            // GET QUERY
            $query = $this->getAlarmQuery($nbExpectedResults,$ecartType,$moyenne,$referenceValue,$alarmThreshold);
            $result = $db->execute($query);

            // L'ALARME NE DOIT PAS SAUTER DONC RENVOYER 0 RESUTATS
            $this->assertEquals(0, $db->getNumRows());

            // CLEAN DB
            $this->dropTestTable();
            $this->cleanAlarm();
        }
    }

    /**
     * Test visant à vérifier qu'une alarme dynamique ne saute pas
     * Lorsque le trigger n'est pas vérifié
     */
    public function testCalculationWithTriggerThatMakesAlarmNotToRaise()
    {
        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // REFERENCE VALUE
        $referenceValue = 3;

        // ECART TYPE DESIRE
        $ecartType = 5;

        // MOYENNE DESIREE
        $moyenne = 2;

        // DYNAMIC CALCULATION
        $dynamicResult = abs(1-(abs($referenceValue - $moyenne)/$ecartType))*100;

        // POUR QUE L'ALARME SAUTE, IL FAUT QUE NOTRE ALARME EST UN SEUIL < $dynamicResult
        $alarmThreshold = $dynamicResult - 1;

        // TRIGGER
        $triggerParams['value'] = 50;
        $triggerParams['min'] = 0;
        $triggerParams['max'] = 40;

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$ecartType,$moyenne,$referenceValue,$alarmThreshold,$triggerParams);
        $result = $db->execute($query);

        // L'ALARME NE DOIT PAS SAUTER DONC RETOURNER 0 RESUTATS
        $this->assertEquals(0, $db->getNumRows());

        // CLEAN DB
        $this->dropTestTable();
        $this->cleanAlarm();
    }

    /**
     * Test visant à vérifier que les champs additionnels sont bien là :)
     */
    public function testCalculationWithAdditionalFields()
    {
        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // REFERENCE VALUE
        $referenceValue = 3;

        // ECART TYPE DESIRE
        $ecartType = 5;

        // MOYENNE DESIREE
        $moyenne = 2;

        // DYNAMIC CALCULATION
        $dynamicResult = abs(1-(abs($referenceValue - $moyenne)/$ecartType))*100;

        // POUR QUE L'ALARME SAUTE, IL FAUT QUE NOTRE ALARME EST UN SEUIL < $dynamicResult
        $alarmThreshold = $dynamicResult - 1;

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$ecartType,$moyenne,$referenceValue,$alarmThreshold);
        $result = $db->execute($query);
        
        // L'ALARME DOIT SAUTER ET RENVOYER $nbExpectedResults RESUTATS
        $this->assertEquals($nbExpectedResults, $db->getNumRows());

        // PARSING RESULTS
        $additionalFieldPresent = true;
        while($row = $db->getQueryResults($result,1)) {
            if(!isset($row['counter_additional'])) $additionalFieldPresent = false;
        }

        // TEST CHAMPS ADDITIONNELS
        $this->assertTrue($additionalFieldPresent);

        // CLEAN DB
        $this->dropTestTable();
        $this->cleanAlarm();
    }

    /**
     * Test visant à vérifier la sélection des NE
     */
    public function testCalculationWithNeSelection()
    {
        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // REFERENCE VALUE
        $referenceValue = 3;

        // ECART TYPE DESIRE
        $ecartType = 5;

        // MOYENNE DESIREE
        $moyenne = 2;

        // DYNAMIC CALCULATION
        $dynamicResult = abs(1-(abs($referenceValue - $moyenne)/$ecartType))*100;

        // POUR QUE L'ALARME SAUTE, IL FAUT QUE NOTRE ALARME EST UN SEUIL < $dynamicResult
        $alarmThreshold = $dynamicResult - 1;

        // NE SELECTION
        $neSelection = 5;

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$ecartType,$moyenne,$referenceValue,$alarmThreshold,array(),$neSelection);
        $result = $db->execute($query);

        // L'ALARME DOIT SAUTER ET RENVOYER $neSelection RESUTATS
        $this->assertEquals($neSelection, $db->getNumRows());

        // CLEAN DB
        $this->dropTestTable();
        $this->cleanAlarm();
    }

    /**
     * Test visant à vérifier les NE blacklistés
     */
    public function testCalculationWithBlacklistedElements()
    {
        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // REFERENCE VALUE
        $referenceValue = 3;

        // ECART TYPE DESIRE
        $ecartType = 5;

        // MOYENNE DESIREE
        $moyenne = 2;

        // DYNAMIC CALCULATION
        $dynamicResult = abs(1-(abs($referenceValue - $moyenne)/$ecartType))*100;

        // POUR QUE L'ALARME SAUTE, IL FAUT QUE NOTRE ALARME EST UN SEUIL < $dynamicResult
        $alarmThreshold = $dynamicResult - 1;

        // NE SELECTION
        $nbBlacklistedElements = 3;

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$ecartType,$moyenne,$referenceValue,$alarmThreshold,array(),0,$nbBlacklistedElements);
        $result = $db->execute($query);

        // L'ALARME DOIT SAUTER ET RENVOYER $neSelection RESUTATS
        $this->assertEquals(17, $db->getNumRows());

        // CLEAN DB
        $this->dropTestTable();
        $this->cleanAlarm();
        $this->whiteListElements();
    }
}

?>
