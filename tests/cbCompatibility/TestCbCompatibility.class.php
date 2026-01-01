<?php
/**
 * Classe de test PHPUnit pour la gestion des modules installés
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
    require_once( dirname( __FILE__ ).'/../../class/CbCompatibility.class.php' );

    class TestCbCompatibility extends PHPUnit_Framework_TestCase
    {
        /**
         * @var _object l'objet de base pour les indicateurs
         */
        protected $_object;

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
            exec( 'env PGPASSWORD='.Database::getConnection()->getDbpassword().' psql -U '.Database::getConnection()->getDbLogin().' -d '.Database::getConnection()->getDbName().' -f '.dirname( dirname( __FILE__ ) ).'/ressources/cbCompatibility_log.bkp.sql' );
            
            // on crée la table et in y met 1 enregistrement
            //exec( 'php -q '.dirname(__FILE__).'/../../SQL/5.1.p/00_cb_compatibility.php' );
        }

        /**
         * Exécuté une fois en fin de de scénario
         * On restaure les données originales
         */
        public static function tearDownAfterClass()
        {
           exec( 'env PGPASSWORD='.Database::getConnection()->getDbpassword().' psql -U '.Database::getConnection()->getDbLogin().' -d '.Database::getConnection()->getDbName().' -f '.dirname( dirname( __FILE__ ) ).'/ressources/cbCompatibility_log.rst.sql' );
        }

        /**
         * Exécuté au début de chaque test
         */
        public function setUp()
        {
            $this->_object = new CbCompatibility();
        }


        /**
         * @group 
         */
        public function testCreate()
        {
            $leModule = array('sdma_code'=>'leCodeModule',
                                'sdma_label'=>'Le label du module',
                                'sdma_on_off'=>1,
                                'sdma_comment'=>"Un commentaire expliquant ce qu'il fait");
            $this->_object->addModule($leModule['sdma_code'],$leModule['sdma_label'],$leModule['sdma_comment']);
            $res = $this->_db->getAll("SELECT * FROM sys_definition_module_availability");
            $res = array_pop($res);
            $this->assertEquals($leModule,$res);

            // on essaie de remettre un module avec le même nom
            $this->assertFalse($this->_object->addModule($leModule['sdma_code'],$leModule['sdma_label'],$leModule['sdma_comment']));
        }

       /**
         * @group 
         */
        public function testRead(){
            // Liste
            $this->assertTrue($this->_object->addModule('module2','Le module n°2','Deuxième module de la table'));
            $res = $this->_object->getAvailableModules();
            $this->assertSame(array(array('sdma_code'=>'leCodeModule','sdma_label'=>'Le label du module'),
                                    array('sdma_code'=>'module2','sdma_label'=>'Le module n°2')), $res);
            // Code
            $this->assertTrue($this->_object->isModuleAvailable('leCodeModule'));
            $this->assertFalse($this->_object->isModuleAvailable('le CodeModule'));
            // Label
            $this->assertSame('Le label du module',$this->_object->getModuleLabel('leCodeModule'));
            $this->assertFalse($this->_object->getModuleLabel('lecodeModule'));
            // Commentaire
            $this->assertSame("Un commentaire expliquant ce qu'il fait",$this->_object->getModuleComment('leCodeModule'));
            $this->assertFalse($this->_object->getModuleLabel('lecodeModule'));
        }
        
        /**
         * @group 
         */
        public function testUpdate(){

            $this->assertTrue($this->_object->setModuleAvailability('leCodeModule',0));
            $this->assertFalse($this->_object->isModuleAvailable('leCodeModule'));
            $this->assertTrue($this->_object->isModuleAvailable('module2'));
            // Label
            $this->assertTrue($this->_object->setModuleLabel('leCodeModule',"Le nouveau label qu'il faut où? Là *."));
            $this->assertSame("Le nouveau label qu'il faut où? Là *.",$this->_object->getModuleLabel('leCodeModule'));
            $this->assertNotSame("Le nouveau label qu'il faut où? Là *.",$this->_object->getModuleLabel('module2'));
            // Commentaire
            $this->assertTrue($this->_object->setModuleComment('leCodeModule',"Le nouveau commentaire qu'il faut être? /12."));
            $this->assertSame("Le nouveau commentaire qu'il faut être? /12.",$this->_object->getModuleComment('leCodeModule'));
            $this->assertNotSame("Le nouveau commentaire qu'il faut être? /12.",$this->_object->getModuleComment('module2'));
        }
        
       

 }
