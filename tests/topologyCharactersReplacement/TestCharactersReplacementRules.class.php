<?php
/**
 * Classe de test PHPUnit pour le remplacement des caractères de la topology
 *
 * Les règles de remplacement sont lues en base de données dans différentes tables.
 * Afin de pouvoir tester la gestion, des
 * données temporaires sont insérés en base. Le script 'tCR_log.bkp.sql' sauvegarde
 * la base actuelle et insère des données temporaires. Le script 'tCR_log.rst.sql'
 * restaure la base de données dans son état initial.
 * 
 * 
 */
    include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
    require_once( dirname( __FILE__ ).'/../../class/topology/TopologyCharactersReplacementRules.class.php' );

    class TestCharactersReplacementRules extends PHPUnit_Framework_TestCase
    {
        /**
         * @var charactersReplacementRules l'objet de base pour les indicateurs
         */
        protected $_cRRObject;

        protected $_db;
        
        public function  __construct() {
            $this->_db = Database::getConnection();
        }
        /**
         * Exécuté une fois en début de scénario
         * On insère en base les données standards pour le tests
         */
        public static function setUpBeforeClass()
        {
            exec( 'env PGPASSWORD='.Database::getConnection()->getDbpassword().' psql -U '.Database::getConnection()->getDbLogin().' -d '.Database::getConnection()->getDbName().' -f '.dirname( dirname( __FILE__ ) ).'/ressources/tCR_log.bkp.sql' );
        }

        /**
         * Exécuté une fois en fin de de scénario
         * On restaure les données originales
         */
        public static function tearDownAfterClass()
        {
           exec( 'env PGPASSWORD='.Database::getConnection()->getDbpassword().' psql -U '.Database::getConnection()->getDbLogin().' -d '.Database::getConnection()->getDbName().' -f '.dirname( dirname( __FILE__ ) ).'/ressources/tCR_log.rst.sql' );
        }

        /**
         * Exécuté au début de chaque test
         */
        public function setUp()
        {
            $this->_cRRObject = new TopologyCharactersReplacementRules();
        }


        /**
         * @group RulesDefinition
         */
        public function testInternalRules()
        {
            $tabChars = array('|s|','||','|t|');
            foreach ($tabChars as $char){
                $tabRules[]['from'] = $char;
                $tabRules[sizeof($tabRules)-1]['to'] = '_';
            }
            $this->assertSame($tabRules,$this->_cRRObject->getLabelRules());
        }

       /**
         * @group RulesDefinition
         */
        public function testLoadCbRules(){
            $this->_cRRObject->loadRules();
            $tabChars = array('|s|','||','|t|','\'','"', '#','\\');
            foreach ($tabChars as $char){
                $tabRules[]['from'] = $char;
                $tabRules[sizeof($tabRules)-1]['to'] = '_';
            }
            // Codes
            $this->assertSame($tabRules,$this->_cRRObject->getCodeRules());
            // Labels
            $this->assertSame($tabRules,$this->_cRRObject->getLabelRules());
        }
        
        /**
         * @group RulesDefinition
         */
        public function testLoadContextRules(){
            // on rempli la table du contexte
            $this->_db->execute("INSERT INTO sys_definition_topology_replacement_rules VALUES ('sdtrr.1', 'é', 'é', '');
INSERT INTO sys_definition_topology_replacement_rules VALUES ('sdtrr.2', 'è', 'ê', 'è');
INSERT INTO sys_definition_topology_replacement_rules VALUES ('sdtrr.3', 'ê', 'ë', 'ô');
INSERT INTO sys_definition_topology_replacement_rules VALUES ('sdtrr.4', 'Noël', 'Pâques', 'Saint Nicolas');
INSERT INTO sys_definition_topology_replacement_rules VALUES ('sdtrr.5', ':', ' ', '<noRempChar>');
INSERT INTO sys_definition_topology_replacement_rules VALUES ('sdtrr.6', '@', '', ' ');
INSERT INTO sys_definition_topology_replacement_rules VALUES ('sdtrr.7', 'à', '<noRempChar>', 'à la');");
            $this->_cRRObject->loadRules();
            $tabChars = array('|s|','||','|t|');
            foreach ($tabChars as $char){
                $tabCodesRules[]['from'] = $char;
                $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = '_';
                $tabLabelsRules[]['from'] = $char;
                $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = '_';
            }
            $tabCodesRules[]['from'] = 'é';
            $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = 'é';
            $tabLabelsRules[]['from'] = 'é';
            $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = '';
            $tabCodesRules[]['from'] = 'è';
            $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = 'ê';
            $tabLabelsRules[]['from'] = 'è';
            $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = 'è';
            $tabCodesRules[]['from'] = 'ê';
            $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = 'ë';
            $tabLabelsRules[]['from'] = 'ê';
            $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = 'ô';
            $tabCodesRules[]['from'] = 'Noël';
            $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = 'Pâques';
            $tabLabelsRules[]['from'] = 'Noël';
            $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = 'Saint Nicolas';
            $tabCodesRules[]['from'] = ':';
            $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = ' ';
           // $tabLabelsRules[]['from'] = ':';
           // $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = '<noRempChar>';
            $tabCodesRules[]['from'] = '@';
            $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = '';
            $tabLabelsRules[]['from'] = '@';
            $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = ' ';
           // $tabCodesRules[]['from'] = 'à';
           // $tabCodesRules[sizeof($tabCodesRules)-1]['to'] = '<noRempChar>';
            $tabLabelsRules[]['from'] = 'à';
            $tabLabelsRules[sizeof($tabLabelsRules)-1]['to'] = 'à la';

            // Codes
            $this->assertSame($tabCodesRules,$this->_cRRObject->getCodeRules());
            // Labels
            $this->assertSame($tabLabelsRules,$this->_cRRObject->getLabelRules());
        }
        
        public function testCheckRulesTrue(){
            $this->_cRRObject->loadRules();
            $this->assertTrue($this->_cRRObject->checkRules());
        }
        
        public function testCheckRulesFalse(){
            // on insère une règle erronnée
            $this->_db->execute("UPDATE sys_definition_topology_replacement_rules SET sdtrr_character='' WHERE sdtrr_id='sdtrr.3'");
            $this->_cRRObject->loadRules();
            $this->assertFalse($this->_cRRObject->checkRules());
        }

        public function testIgnoreRules(){
            // on vide la table du contexte
            $this->_db->execute("TRUNCATE TABLE sys_definition_topology_replacement_rules");
            // on ajoute un ; dans une règle pour l'ignorer
            $this->_db->execute("UPDATE sys_definition_topology_replacement_rules_default SET sdtrr_character='la;be' WHERE sdtrr_id='sdtrr.3'");
            $this->_cRRObject->loadRules();
            $this->_cRRObject->IgnoreRules(';');

            // on supprime une règle du tableau des attendus
            $tabChars = array('|s|','||','|t|','\'','"', '\\');
            foreach ($tabChars as $char){
                $tabRules[]['from'] = $char;
                $tabRules[sizeof($tabRules)-1]['to'] = '_';
            }
            // Codes
            $this->assertSame($tabRules,$this->_cRRObject->getCodeRules());
        }
        
        public function testApplyRulesToCode(){
            $this->_cRRObject->loadRules();
            $this->assertSame("phrase de test",$this->_cRRObject->applyCodeRules("phrase de test"));
            $this->assertSame("phrase de_test",$this->_cRRObject->applyCodeRules("phrase de\\test"));
            $this->assertSame("phrase_de_test",$this->_cRRObject->applyCodeRules("phrase'de\"test"));
        }
        public function testApplyRulesToLabel(){
            $this->_cRRObject->loadRules();
            $this->assertSame("l_expression testée",$this->_cRRObject->applyLabelRules("l'expression testée"));
            $this->assertSame("l_expression_testée",$this->_cRRObject->applyLabelRules("l'expression||testée"));
            $this->assertSame("l_expression_te_tée",$this->_cRRObject->applyLabelRules("l_expression\"te|s|tée"));
        }

 }
