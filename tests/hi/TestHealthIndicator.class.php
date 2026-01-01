<?php
/**
 * Classe de test PHPUnit pour les Indicateurs de santé
 *
 * Les indicateurs de santé sont lues en base de données dans différentes tables.
 * Afin de pouvoir tester ces indicateurs, même à partir d'un produit vide, des
 * données temporaires sont insérés en base. Le script 'hi_log.bkp.sql' sauvegarde
 * la base actuelle et insère des données temporaires. Le script 'hi_log.rst.sql'
 * restaure la base de données dans son état initial.
 * 
 * $Author: f.guillard $
 * $Date: 2015-05-06 16:05:35 +0200 (mer., 06 mai 2015) $
 * $Revision: 164701 $
 * 
 */
    include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
    require_once( dirname( __FILE__ ).'/../../class/HealthIndicator.class.php' );

    class TestHealthIndicator extends PHPUnit_Framework_TestCase
    {
        /**
         * @var HealthIndicator l'objet de base pour les indicateurs
         */
        protected $_hiObject;

        /**
         * Exécuté une fois en début de scénario
         * On insère en base les données standards pour le tests
         */
        public static function setUpBeforeClass()
        {
            exec( 'env PGPASSWORD='.Database::getConnection()->getDbpassword().' psql -U '.Database::getConnection()->getDbLogin().' -d '.Database::getConnection()->getDbName().' -f '.dirname( dirname( __FILE__ ) ).'/ressources/hi_log.bkp.sql' );
        }

        /**
         * Exécuté une fois en fin de de scénario
         * On restaure les données originales
         */
        public static function tearDownAfterClass()
        {
           exec( 'env PGPASSWORD='.Database::getConnection()->getDbpassword().' psql -U '.Database::getConnection()->getDbLogin().' -d '.Database::getConnection()->getDbName().' -f '.dirname( dirname( __FILE__ ) ).'/ressources/hi_log.rst.sql' );
        }

        /**
         * Exécuté au début de chaque test
         */
        public function setUp()
        {
            $this->_hiObject = new HealthIndicator();
        }

        public function assertPreCondition()
        {
           $this->assertTrue( $this->_hiObject->getDbConnexion() );
        }

        /**
         * Vérification du format retourné "12|1271256987"
         * Les données en base sont telles que la dernière collecte a duré 92s
         * @group perf
         */
        public function testLastCollectDuration()
        {
            $this->assertRegExp( "/^92;(12|13)[0-9]{8}$/", $this->_hiObject->getLastCollectDuration() );
        }

        /**
         * Vérification du format retourné "12|20100729_120015"
         * @group perf
         */
        public function testLastCollectDurationWithIhmMode()
        {
            $obj = new HealthIndicator( HealthIndicator::HI_CALL_MODE_IHM );
            $this->assertRegExp( "/^92;[0-9]{8}[_][0-9]{6}$/", $obj->getLastCollectDuration() );
        }

        /**
         * @group perf
         */
        public function testLastRetrieveDuration()
        {
            $this->assertRegExp( "/^1160;(12|13)[0-9]{8}$/", $this->_hiObject->getLastRetrieveDuration() );
        }

        /**
         * @group perf
         */
        public function testComputeRawDuration()
        {
            $this->assertRegExp( "/^71;(12|13)[0-9]{8}[|][0-9]+$/", $this->_hiObject->getLastComputeRawDuration() );
        }

        /**
         * @group perf
         */
        public function testLastComputeKpiDuration()
        {
            $this->assertRegExp( "/^49;(12|13)[0-9]{8}[|][0-9]+$/", $this->_hiObject->getLastComputeKpiDuration() );
        }

        /**
         * @group perf
         */
        public function testLastComputeAllDuration()
        {
            $this->assertRegExp( "/^120;(12|13)[0-9]{8}[|][0-9]+$/", $this->_hiObject->getLastComputeAllDuration() );
        }

        /**
         * @group perf
         */
        public function testNbWaitFiles()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbWaitFiles() ) );
        }

        /**
         * @group perf
         */
        public function testFamilyHistory()
        {
            $res = $this->_hiObject->getFamilyHistory();
            $this->assertTrue( is_array( $res ) );
            $this->assertTrue( count( $res ) > 0 );
            foreach( $res as $fh )
            {
                $this->assertRegExp( "/^h:[0-9]+[|]d:[0-9]+[|]w:[0-9]+[|]m:[0-9]+[;].+$/", $fh );
            }
        }

        /**
         * @group perf
         */
        public function testNbLastDayCollectedFiles()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbLastDayCollectedFiles() ) );
        }

        /**
         * @group perf
         */
        public function testNbRaw()
        {
            $res = $this->_hiObject->getNbRaw();
            $this->assertTrue( is_array( $res ) );
            $this->assertTrue( count( $res ) > 0 );
            foreach( $res as $raw )
            {
                $this->assertRegExp( "/^[0-9]+[;].+$/", $raw ) ;
            }            
        }

        /**
         * @group perf
         */
        public function testNbMappedRaw()
        {
            $res = $this->_hiObject->getNbMappedRaw();
            $this->assertTrue( is_array( $res ) );
            //$this->assertTrue( count( $res ) > 0 );
            foreach( $res as $raw )
            {
                $this->assertRegExp( "/^[0-9]+[;].+$/", $raw ) ;
            }
        }

        /**
         * @group perf
         */
        public function testNbCustomKpi()
        {
            $res = $this->_hiObject->getNbCustomKpi();
            $this->assertTrue( is_array( $res ) );
            $this->assertTrue( count( $res ) > 0 );
            foreach( $res as $raw )
            {
                $this->assertRegExp( "/^[0-9]+[;].+$/", $raw ) ;
            }
        }

        /**
         * @group perf
         */
        public function testNbClientKpi()
        {
            $res = $this->_hiObject->getNbClientKpi();
            $this->assertTrue( is_array( $res ) );
            //$this->assertTrue( count( $res ) > 0 );
            foreach( $res as $raw )
            {
                $this->assertRegExp( "/^[0-9]+[;].+$/", $raw ) ;
            }
        }

        /**
         * @group perf
         */
        public function testSAValue()
        {
            // Vérification du format de la date
            $this->assertSame( false, $this->_hiObject->getSAValues( '20112902' ) );
            $this->assertSame( false, $this->_hiObject->getSAValues( '20111131' ) );
            $this->assertSame( false, $this->_hiObject->getSAValues( '20111131' ) );
            $this->assertSame( false, $this->_hiObject->getSAValues( '2011113024' ) );
            $this->assertSame( false, $this->_hiObject->getSAValues( '20111131aa' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( date( 'Ymd' ) ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '20120229' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '2012022900' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '2012022923' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '2012022912' ) );


            // Verification du test de la granularité (insensible à la casse)
            $this->assertSame( false, $this->_hiObject->getSAValues( '20111219', 'test' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '20111219', 'day' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '20111219', 'hour' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '20111219', 'DAY' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '20111219', 'HOUR' ) );

            // Vérification du boolean
            $this->assertSame( false, $this->_hiObject->getSAValues( '20111219', 'hour', 'test' ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '20111219', 'hour', 1 ) );
            $this->assertNotSame( false, $this->_hiObject->getSAValues( '20111219', 'hour', 0 ) );
        }

        /**
         * @group alarm
         */
        public function testNbStaticAlarms()
        {
            $this->assertSame( 2, $this->_hiObject->getNbStaticAlarms() );
        }

        /**
         * @group alarm
         */
        public function testNbDynAlarms()
        {
            $this->assertSame( 1, $this->_hiObject->getNbDynAlarms() );
        }

        /**
         * @group alarm
         */
        public function testNbTwAlarms()
        {
            $this->assertSame( 3, $this->_hiObject->getNbTwAlarms() );
        }

        /**
         * @group alarm
         */
        public function testLastComputeAlarmsDuration()
        {
            $res = $this->_hiObject->getLastComputeAlarmsDuration();
            $this->assertTrue( is_array( $res ) );
            $this->assertTrue( count( $res ) > 0 );
            foreach( $res as $ad )
            {
                $this->assertRegExp( "/^[0-9]+([.,][0-9]+)?[;].+[|].+[|][0-9]+$/", $ad );
            }
        }

        /**
         * @group dataexport
         */
        public function testNbDataExports()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbDataExports() ) );
        }

        /**
         * @group dataexport
         */
        public function testLastDataExportsGenerationDuration()
        {
            $res = $this->_hiObject->getLastDataExportsGenerationDuration();
            $this->assertTrue( is_array( $res ) );
            $this->assertTrue( count( $res ) > 0 );
            foreach( $res as $de )
            {
                $this->assertRegExp( "/^[0-9]+([.,][0-9]+)?[;].+$/", $de );
            }
        }

        /**
         * @group topo
         */
        public function testNbNe()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbNe() ) );
        }

        /**
         * @group topo
         */
        public function testNbFirstAxisNe()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbFirstAxisNe() ) );
        }

        /**
         * @group topo
         */
        public function testNbThirdAxisNe()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbThirdAxisNe( ) ) );
        }

        /**
         * @group storage
         */
        public function testDiscSpace()
        {
            $res = $this->_hiObject->getDiscSpace();
            $this->assertTrue( is_array( $res ) );
            $this->assertTrue( count( $res ) > 0 );
            foreach( $res as $df )
            {
                $this->assertRegExp( "#^[0-9]+[|][0-9]+[;][/].*$#", $df );
            }
        }

        /**
         * @group other
         */
        public function testNbAccounts()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbAccounts() ) );
        }

        /**
         * @group other
         */
        public function testNbConnectedUsersLastDay()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbConnectedUsersLastDay() ) );
        }

        /**
         * @group other
         */  
        public function testNbPagesLastDay()
        {
            $this->assertTrue( is_integer( $this->_hiObject->getNbPagesLastDay() ) );
        }

        /**
         * @group file
         */
        public function testGenerateFileWithBadPath()
        {
            $this->assertFalse( $this->_hiObject->generateOutputFile( './toto/toto' ) );
        }

        /**
         * @group file
         */
        public function testGenerateFileWithGoodPath()
        {
            $this->assertTrue( $this->_hiObject->generateOutputFile( './testHi.csv' ) );
            $this->assertTrue( file_exists( './testHi.csv' ) );
            unlink( './testHi.csv' );
        }

        /**
         * @group file
         */
        public function testFileContent()
        {
            $this->_hiObject->generateOutputFile( './testHi.csv' );
            $fileContent = file( './testHi.csv' );
            $fileContentHi = array();
            $this->assertTrue( count( $fileContent ) > 0 );
            $this->assertTrue( strcmp( $fileContent[0], "indicator_names;indicator_values;indicator_informations\n" ) === 0  );

            // On récupère les indicateurs présent dans le fichier
            foreach ( $fileContent as $hi )
            {
                $fileContentHi[] = substr( $hi, 0, strpos( $hi, ';' ) );
            }

            foreach( $this->_hiObject->getAllHiNames() as $hi )
            {
                // On vérifie que tous les indicateurs sont dans le fichier
                $this->assertTrue( in_array( $hi, $fileContentHi ) );
            }
            unlink( './testHi.csv' );
        }
    }
