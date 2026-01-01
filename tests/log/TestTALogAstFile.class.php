<?php
/**
 * Classe de test PHPUnit pour le log fichier au format Astellia
 *
 * $Author: f.guillard $
 * $Date: 2015-05-06 16:05:35 +0200 (mer., 06 mai 2015) $
 * $Revision: 164701 $
 *
 * 20/09/2010 OJT : Mise à jour des tests suite bz18050
 *
 */
    include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
    require_once( dirname( __FILE__ ).'/../../class/log/TALogAstFile.class.php' );

    class TestTaLogAstFile extends PHPUnit_Framework_TestCase
    {
        protected $_logObj;
        protected $_start;
        protected $_end;
        protected $_db;
        const DEUX_JOURS = 86400;

        /**
         * Exécuté une fois en début de scénario
         */
        public static function setUpBeforeClass()
        {
            exec( 'env PGPASSWORD='.Database::getConnection()->getDbpassword().' psql -U '.Database::getConnection()->getDbLogin().' -d '.Database::getConnection()->getDbName().' -f '.dirname( dirname( __FILE__ ) ).'/ressources/hi_log.bkp.sql' );
        }

        /**
         * Exécuté une fois en fin de de scénario
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
            $this->_db = Database::getConnection();
            $this->_start = time() - self::DEUX_JOURS;
            $this->_end = time();

            // On demande des log pour 2 jours
            $this->_logObj = new TALogAstFile( $this->_db, $this->_start, $this->_end );
        }

        /**
         * Pré-conditions nécessaires au début de chaque test
         */
        public function assertPreConditions()
        {
           $this->assertTrue( $this->_logObj->getStart() === $this->_start );
           $this->assertTrue( $this->_logObj->getEnd() === $this->_end );
           $this->assertTrue( count( $this->_logObj->getListLog() ) > 0 );
        }

        /**
         * @group structure
         */
        public function testListLogArrayStructure()
        {
            foreach( $this->_logObj->getListLog() as $oneAstLog )
            {
                $this->assertregExp( "/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}[[:blank:]][0-9]{2}:[0-9]{2}:[0-9]{2}$/", $oneAstLog['timestamp'] );
                $this->assertRegExp( "/^.+[(][0-9]{1,3}[.][0-9]{1,3}[.][0-9]{1,3}[.][0-9]{1,3}[)]$/", $oneAstLog['appli'] );
                $this->assertRegExp( "/^(Info|Warning|Critical|Major)$/", $oneAstLog['severity'] );
                $this->assertTrue( strlen( $oneAstLog['msggroup'] ) > 0 );
                $this->assertSame( 0, strlen( $oneAstLog['object'] ) );
                $this->assertTrue( strlen( $oneAstLog['astlog'] ) > 0 );
            }
        }

        /**
         * Vérification que les dates retournées sont bien bornées
         * @group structure
         */
        public function testListLogArrayDate()
        {
            foreach( $this->_logObj->getListLog() as $oneAstLog )
            {
                $this->assertTrue( strtotime( $oneAstLog['timestamp'] ) >= $this->_start );
                $this->assertTrue( strtotime( $oneAstLog['timestamp'] ) <= $this->_end );
            }
        }

        /**
         * Vérification des InvalidArgumentException
         * @group param
         */
        public function testParameterWithStartAndEndValueInTheFutur()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 1 );
            new TALogAstFile( Database::getConnection(), 1600000000 - 86400, 1600000000 );
        }

        /**
         * Vérification des InvalidArgumentException
         * @group param
         */
        public function testParameterWithOneNullValue()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 2 );
            new TALogAstFile( Database::getConnection(), NULL, time() );
        }
            
        /**
         * Vérification des InvalidArgumentException
         * @group param
         */
        public function testParameterWithNegativeStartTime()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 3 );
            new TALogAstFile( Database::getConnection(), -63, -69, -1024 );
        }

        /**
         * Vérification des InvalidArgumentException
         * @group param
         */
        public function testParameterStartUpperThanEnd()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 4 );
            new TALogAstFile( Database::getConnection(), $this->_end, $this->_start );
        }

        /**
         * Vérification des InvalidArgumentException
         * @group param
         */
        public function testParameterWithNegativeMaxSize()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 5 );
            new TALogAstFile( Database::getConnection(), $this->_start, $this->_end, -5 );
        }

        /**
         * Vérification qu'une demande trop ancienne de log retourne bien
         * aucun resultat
         */
        public function testOldRequestWithNoLog()
        {
            // Test vieux timestamp (29/07/1984 à 16:00:00)
            $obj = new TALogAstFile( $this->_db, 459958800 - 86400, 459958800 );
            $this->assertTrue( is_array( $obj->getListLog() ) );
            $this->assertSame( 0, count( $obj->getListLog() ) );
        }

        /**
         * Vérification de l'impossibilité de créé un fichier n'importe où
         *
         * @group file
         */
        public function testGenerateFileWithBadPath()
        {
            $this->assertFalse( $this->_logObj->createLog( './toto/toto' ) );
        }

        /**
         * Vérification de la création du fichier de log par defaut
         *
         * @group file
         */
        public function testGenerateFileWithDefaultName()
        {
            exec( 'rm '.dirname( $this->_logObj->getDefaultPath() ).' -Rf' );
            $this->assertTrue( $this->_logObj->createLog() );
            $this->assertTrue( file_exists( $this->_logObj->getDefaultPath() ) );
        }

        /**
         * Vérification de la bonne purge des fichiers de logs
         * @group purge
         */
        public function testAutomaticPurgeLogFiles()
        {
            // Création du fichier avec une vielle date
            touch( $this->_logObj->getDefaultPath(), 0, 0 );
            $this->assertTrue( file_exists( $this->_logObj->getDefaultPath() ) );

            // On exécute le clean_files.php
            exec( 'php -q '.realpath( dirname( __FILE__ ).'/../../scripts/clean_files.php' ).' &>/dev/null' );

            //On vérifie que le vieux fichier n'existe plus
            $this->assertFalse( file_exists( $this->_logObj->getDefaultPath() ) );
        }

        /**
         * Vérification de la strcuture du fichier créé (regexp sur chaque ligne)
         *
         * @group file
         */
        public function testGenerateFileWithGoodPathWithDefaultMaxSize()
        {
            $this->assertTrue( $this->_logObj->createLog( './testLogAstFile.log' ) );
            $this->assertTrue( file_exists( './testLogAstFile.log' ) );
            $this->assertTrue( filesize( './testLogAstFile.log' ) <= $this->_logObj->getLogMaxSize() );
            $fileContent = file( './testLogAstFile.log' );
            $this->assertTrue( count( $fileContent ) > 0 );
            foreach ( $fileContent as $oneAstLog )
            {
                $this->assertRegExp( "/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}[[:blank:]][0-9]{2}:[0-9]{2}:[0-9]{2}[[:blank:]].+[[:blank:]](Info|Warning|Critical|Major)[[:blank:]].+[[:blank:]][[:blank:]].+$/", $oneAstLog );
            }
            unlink( './testLogAstFile.log' );
        }

        /**
         * Vérification de la limitation de la taille du fichier à 1Ko
         *
         * @group file
         */
        public function testGenerateFileWith1KoMaxSize()
        {
            // Je ne veux récupérer que 1Ko de log max
            $obj = new TALogAstFile( $this->_db, null, null, 1024 );
            $this->assertTrue( $obj->createLog( './testLogAstFile1Ko.log' ) );
            $this->assertTrue( file_exists( './testLogAstFile1Ko.log' ) );
            $this->assertTrue( filesize( './testLogAstFile1Ko.log' ) <= $this->_logObj->getLogMaxSize() );
            unlink( './testLogAstFile1Ko.log' );
        }

        /**
         * Vérification que la création d'un fichier de log sans date et sans
         * limite de taille comporte bien le même nombre de ligne qu'en
         * base de données
         *
         * @group file
         */
        public function testGenerateFileWithUnlimitedMaxSize()
        {
            // Lecture du nombre de ligne en base
            $nbline = intval( $this->_db->getOne( 'SELECT COUNT(oid) as nbline FROM sys_log_ast WHERE module != \'\'' ) );

            // Crétion de l'object sans limite
            $obj = new TALogAstFile( $this->_db, NULL, NULL, TALog::UNLIMITED_MAX_SIZE );
            $this->assertTrue( $obj->createLog( './testLogAstFileUnlimited.log' ) );
            $this->assertTrue( file_exists( './testLogAstFileUnlimited.log' ) );

            // On vérifie que le nombre de ligne du fichier correcpond on nombre de ligne en base
            $this->assertSame( $nbline, intval( exec( 'cat ./testLogAstFileUnlimited.log | wc -l' ) ) );
            unlink( './testLogAstFileUnlimited.log' );
        }

        /**
         * Vérification que la création d'un fichier de log avec date et sans
         * limite de taille comporte bien moins de ligne qu'en base de données
         *
         * @group file
         */
        public function testGenerateFileWithUnlimitedMaxSizeFor2Days()
        {
            // Lecture du nombre de ligne en base
            $nbline = intval( $this->_db->getOne( 'SELECT COUNT(oid) as nbline FROM sys_log_ast WHERE module != \'\'' ) );

            // Crétion de l'object sans limite
            $obj = new TALogAstFile( $this->_db, $this->_start, $this->_end, TALog::UNLIMITED_MAX_SIZE );
            $this->assertTrue( $obj->createLog( './testLogAstFileUnlimited.log' ) );
            $this->assertTrue( file_exists( './testLogAstFileUnlimited.log' ) );

            // On vérifie que le nombre de ligne du fichier correcpond on nombre de ligne en base
            $this->assertTrue( $nbline > intval( exec( 'cat ./testLogAstFileUnlimited.log | wc -l' ) ) );
            unlink( './testLogAstFileUnlimited.log' );
        }
    }
?>

