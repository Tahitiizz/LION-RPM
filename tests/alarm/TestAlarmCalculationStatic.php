<?php
/*
 *
 *	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 *
*/
include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculation.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculationStatic.class.php');

class TestAlarmCalculationStatic extends PHPUnit_Framework_TestCase
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
     * @param array $triggers triggers de l'alarme
     * @return string id de l'alarme
     */
    public function generateAlarm(array $triggers)
    {
        $na = $this->na;
        if($this->axe3) $na .= '_'.$this->na3;
        // ID ALARM
        $idAlarm = generateUniqId ('sys_definition_alarm_static');
        // ALARM NAME
        $alarmName = 'Alarm Test';
        // ADDITIONNAL
        $alarmAdditional = 'counter_additional';
        // TRIGGER
        $alarmTrigger = 'counter_trigger';
        // DB
        $db = Database::getConnection();
        foreach($triggers as $i => $alarmTrigger)
        {
            // INSERT
            $query = "INSERT INTO sys_definition_alarm_static
            (alarm_id,alarm_name,alarm_trigger_data_field,alarm_trigger_operand,alarm_trigger_type,id_group_table,network,time,hn_value,family,alarm_trigger_value,internal_id,client_type,additional_field,additional_field_type,description,critical_level,nb_iteration,period,on_off)
            VALUES
            ('$idAlarm','$alarmName','counter_trigger_".($i+1)."','>=','raw',1,'$na','{$this->ta}',NULL,'{$this->family}',$alarmTrigger,NULL,'customisateur','$alarmAdditional','raw','Alarm de test','critical',1,1,1)";
            $db->execute($query);
        }
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
            WHERE alarm_id IN (SELECT alarm_id FROM sys_definition_alarm_static
                WHERE alarm_name = 'Alarm Test')
            AND type_alarm = 'static'";
        $db->execute($query);
        // DELETE
        $query = "DELETE FROM sys_definition_alarm_static WHERE alarm_name = 'Alarm Test'";
        $db->execute($query);
    }

    /**
     * Créé la table de données de test
     */
    public function generateTestTable($triggers)
    {
        $db = Database::getConnection();
        // CREATE TABLE
        $query = "CREATE TABLE edw_alarm_test (
            $this->na TEXT,
            ".($this->axe3 ? $this->na3.' TEXT,' : '')."
            $this->ta integer,
            counter_additional real";
        foreach($triggers as $i => $value)
            $query .= ",counter_trigger_".($i+1)." real";
        $query .= ")";
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
        $query = "SELECT alarm_id FROM sys_definition_alarm_static
            WHERE alarm_name = 'Alarm Test'";
        $alarmId = $db->getOne($query);
        // TOPOLOGY ELEMENTS
        shuffle($topologyElements);
        for($e = 0; $e < $nbElements; $e++)
        {
            $ne = $topologyElements[$e];
            $query = "INSERT INTO sys_definition_alarm_network_elements
                (alarm_id,type_alarm,na,na_value)
                VALUES ('$alarmId','static','$this->na','$ne')";
            $db->execute($query);
        }
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
     * Ajoute une ligne de données dans la table de données de test
     * @param array $array
     */
    public function addTestData(array $array)
    {
        // DB
        $db = Database::getConnection();
        // QUERY
         $query = "INSERT INTO edw_alarm_test
            VALUES (";

        foreach($array as $i => $value) 
        {
            if($i >= 1) $query .= ",";
            if((($this->axe3) && ($i <= 3)) || ((!$this->axe3) && ($i <= 2))) {
                $query .= "'$value'";
            }
            else {
                $query .= "$value";
            }
        }

        $query .= ")";

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
     * génère la requête de calcul
     * @param integer $nbExpectedResults nombre de résultats désirés
     * @param array $triggerParams trigger ('value' => valeur du trigger, 'min' => valeur aléatoire minimale, 'max' => valeur aléatoire maximale)
     * @return string Requête de calcul
     */
    public function getAlarmQuery($nbExpectedResults,array $triggerParams = array(), $neSelection = 0, $nbBlacklistedElements = 0)
    {
        // TRIGGER
        $triggers = array();
        foreach($triggerParams as $trigger)
            $triggers[] = $trigger['value'];

        // GENERATE ALARM
        $idAlarm = $this->generateAlarm($triggers);

        $this->dropTestTable();
        $this->generateTestTable($triggers);

        // TOPOLOGY
        $nElements = $this->getTopologyElements($this->na, $nbExpectedResults);
        if($this->axe3)
            $nElements3 = $this->getTopologyElements($this->na3, $nbExpectedResults);

        // NE SELECTION
        if($neSelection > 0) {
            $this->addNeSelection($nElements,$neSelection);
        }

        // BLACKLIST
        if($nbBlacklistedElements > 0) {
            $this->blacklistElements($nElements, $nbBlacklistedElements);
        }

        // DATA
        $date = date("YmdH",strtotime("-1 hour"));
        if($this->ta == 'day') $date = date("Ymd",strtotime("-1 day"));
        for($i = 1; $i <= $nbExpectedResults; $i++)
        {
            $array = array();
            // Cas standard 1er axe
            $array[] = $nElements[$i-1];
            // Cas si 3eme axe
            if($this->axe3) $array[] = $nElements3[$i-1];
            // Date
            $array[] = $date;
            // Champ additionnel
            $array[] = mt_rand(0, 99);
            // Triggers
            foreach($triggerParams as $trigger) {
                $array[] = mt_rand($trigger['min'], $trigger['max']);
            }
            // Insertion des valeurs
            $this->addTestData($array);
        }

        // ALARM
        $myAlarm = new AlarmModel($idAlarm, 'static');
        $this->alarmCalculation->setAlarm($myAlarm);

        // On doit calculer sur notre table de test
        $this->alarmCalculation->setTables(array('r' => 'edw_alarm_test'));

        // Récupération de la requête
        $query = $this->alarmCalculation->getCalculationQuery($this->ta,array($date),0);
        return $query;
    }

    /* TEST BEGIN */

    /**
     * Configuration des tests
     */
    public function setUp()
    {
        $this->alarmCalculation = new AlarmCalculationStatic();
    }

    /**
     * Suppression des tests
     */
    public function tearDown()
    {
        // CLEAN DB
        $this->dropTestTable();
        $this->cleanAlarm();
        $this->whiteListElements();
    }

    /**
     * Test vistant à vérifier que l'objet AlarmCalculationStatic
     * retourne bien les bon types
     */
    public function testType()
    {
        $this->assertEquals('static', $this->alarmCalculation->getType());
        $this->assertEquals('static', $this->alarmCalculation->getTypeToSave());
    }

    /**
     * Test visant à vérifier que seule une alarme static peut être calculée par
     * un objet AlarmCalculationStatic
     */
    public function testSetAlarmModel()
    {
        $myAlarm = $this->getMock('AlarmModel',array('__construct'),array('0','dynamic'));
        $this->setExpectedException('BadMethodCallException');
        $this->alarmCalculation->setAlarm($myAlarm);

        $myAlarm = $this->getMock('AlarmModel',array('__construct'),array('0','static'));
        $this->alarmCalculation->setAlarm($myAlarm);
    }

    /**
     * Test visant à vérifier qu'une alarme static saute correctement si tous
     * les triggers sont vérifiés
     */
    public function testCalculationWithTriggerThatMakesAlarmToRaise()
    {
        // INIT VALUES
        $this->getConfAlarm();

        // CLEANS ALARM
        $this->cleanAlarm();
        
        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // TRIGGERS
        for($i = 0; $i <= 4; $i++)
        {
            $triggerParams[$i]['value'] = mt_rand(0, 50);
            $triggerParams[$i]['min'] = 50;
            $triggerParams[$i]['max'] = 100;
        }

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$triggerParams);
        $result = $db->execute($query);

        // L'ALARME DOIT SAUTER DONC RETOURNER $nbExpectedResults RESUTATS
        $this->assertEquals($nbExpectedResults, $db->getNumRows());

        // CLEAN DB
        $this->dropTestTable();
        $this->cleanAlarm();
    }

    /**
     * Test visant à vérifier qu'une alarme static saute correctement si tous
     * les triggers sont vérifiés
     */
    public function testCalculationWithTriggerThatMakesAlarmNotToRaise()
    {
        // INIT VALUES
        $this->getConfAlarm();

        // CLEANS ALARM
        $this->cleanAlarm();
        
        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // TRIGGERS
        for($i = 0; $i <= 4; $i++)
        {
            $triggerParams[$i]['value'] = mt_rand(150, 200);
            $triggerParams[$i]['min'] = 50;
            $triggerParams[$i]['max'] = 100;
        }

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$triggerParams);
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
        // INIT VALUES
        $this->getConfAlarm();

        // CLEANS ALARM
        $this->cleanAlarm();

        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // TRIGGERS
        for($i = 0; $i <= 4; $i++)
        {
            $triggerParams[$i]['value'] = mt_rand(0, 50);
            $triggerParams[$i]['min'] = 50;
            $triggerParams[$i]['max'] = 100;
        }

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$triggerParams);
        $result = $db->execute($query);

        // L'ALARME DOIT SAUTER DONC RETOURNER $nbExpectedResults RESUTATS
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
        // INIT VALUES
        $this->getConfAlarm();

        // CLEANS ALARM
        $this->cleanAlarm();

        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // TRIGGERS
        for($i = 0; $i <= 4; $i++)
        {
            $triggerParams[$i]['value'] = mt_rand(0, 50);
            $triggerParams[$i]['min'] = 50;
            $triggerParams[$i]['max'] = 100;
        }

        // NE SELECTION
        $neSelection = 5;

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$triggerParams,$neSelection);
        $result = $db->execute($query);

        // L'ALARME DOIT SAUTER SUR LA SELECTION DES NE ET DONC RETOURNER $neSelection RESUTATS
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
        // INIT VALUES
        $this->getConfAlarm();

        // CLEANS ALARM
        $this->cleanAlarm();

        // DB
        $db = Database::getConnection();

        // EXPECTED RESULTS
        $nbExpectedResults = 20;

        // TRIGGERS
        for($i = 0; $i <= 4; $i++)
        {
            $triggerParams[$i]['value'] = mt_rand(0, 50);
            $triggerParams[$i]['min'] = 50;
            $triggerParams[$i]['max'] = 100;
        }

        // BLACKLIST
        $nbBlacklistedElements = 3;

        // GET QUERY
        $query = $this->getAlarmQuery($nbExpectedResults,$triggerParams,0,$nbBlacklistedElements);
        $result = $db->execute($query);

        // L'ALARME DOIT SAUTER SAUF SUR LES NE BLACKLISTES
        $this->assertEquals(17, $db->getNumRows());

        // CLEAN DB
        $this->whiteListElements();
        $this->dropTestTable();
        $this->cleanAlarm();
    }
}

?>
