<?php
/**
 * Classe de test PHPUnit pour l'API T&A
 * 
 * $Author: f.guillard $
 * $Date: 2015-05-06 16:05:35 +0200 (mer., 06 mai 2015) $
 * $Revision: 164701 $
 * 
 */
    include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
    require_once( dirname( __FILE__ ).'/../../class/api/TrendingAggregationApi.class.php' );

    class TestApiTA extends PHPUnit_Framework_TestCase
    {
        protected $_soapClient;
        protected $_wsdlLocation;
        const PHP_REQUIRED_VERSION = '5.2.13';

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


        public function setUp()
        {
            $wsdlLocation = '';
            try
            {
                $db = Database::getConnection();
                $this->_wsdlLocation = $db->getOne( 'SELECT sdp_ip_address || \'/\' || sdp_directory
                                            FROM sys_definition_product
                                            WHERE sdp_db_name=\''.$db->getDbName().'\';' );
            }
            catch( Exception $e )
            {
                // On laissera le WSDL inchangé
            }
            $this->_soapClient = new SoapClient( 'http://'.$this->_wsdlLocation.'/api/index.php?wsdl', array( 'soap_version' => SOAP_1_2, 'style'=> SOAP_DOCUMENT, 'use'=> SOAP_LITERAL ) );
        }

        /**
         * Vérification de la version PHP
         * Vérification que le fichier WSDL à les bons droits
         */
        public function assertPreConditions()
        {
            $ss = stat( realpath( dirname( __FILE__ ).'/../../api/ta.wsdl' ) );
            $p = $ss['mode'];
            $this->assertTrue( ($p&0x0002) && ($p&0x0010) && ($p&0x0080) );
        }

        public function testPhpVersion()
        {
            $this->assertTrue( version_compare( PHP_VERSION, self::PHP_REQUIRED_VERSION ) >= 0 );
        }

        /**
         * @group connection
         */
        public function testConnectionWithBadLoginAndPassword()
        {
            $this->assertEquals( TrendingAggregationApi::eNotConnected, $this->_soapClient->connection( 'toto', 'toto' ) );
            $this->assertEquals( TrendingAggregationApi::eNotConnected, $this->_soapClient->Login( 'toto', 'toto' ) );
        }
        
        /**
         * @group connection
         */
        public function testConnectionWithGoodLoginAndPassword()
        {
            $this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->connection( 'astellia_admin', 'astellia_admin' ) );
            $this->assertEquals( TrendingAggregationApi::eAlreadyConnected, $this->_soapClient->connection( 'astellia_admin', 'astellia_admin' ) );
            $this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->disconnection() );
            $this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->Login( 'astellia_admin', 'astellia_admin' ) );
            $this->assertEquals( TrendingAggregationApi::eAlreadyConnected, $this->_soapClient->Login( 'astellia_admin', 'astellia_admin' ) );
            $this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->Logout() );
        }

        /**
         * @group right
         */
        public function testRequestWithBadConnectionLevel()
        {
            $this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->connection( 'astellia_user', 'astellia_user' ) );
            $this->assertTrue( in_array( TrendingAggregationApi::eNotAllowed, $this->_toArray( $this->_soapClient->getHealthIndicator( '' ) ) ) );
            $this->assertEquals( TrendingAggregationApi::eNotAllowed, $this->_soapClient->RetrieveLog( 0, 0, 0 ) );
            $this->assertEquals( TrendingAggregationApi::eNotAllowed, $this->_soapClient->GetVersion() );
            $this->assertTrue( in_array( TrendingAggregationApi::eNotAllowed, $this->_toArray( $this->_soapClient->GetDiskUse() ) ) );
            $this->assertTrue( in_array( TrendingAggregationApi::eNotAllowed, $this->_toArray( $this->_soapClient->GetOSInfos() ) ) );
        }

        public function testGetOsInfosRequest()
        {
            $this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->connection( 'astellia_admin', 'astellia_admin' ) );
            $this->assertSame( 2, count( $this->_toArray( $this->_soapClient->GetOSInfos() ) ) );
            $this->assertTrue( strlen( $this->_soapClient->GetVersion() ) > 0 ) ;
        }

        public function testGetVersion()
        {
            $this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->connection( 'astellia_admin', 'astellia_admin' ) );
            $this->assertTrue( strlen( $this->_soapClient->GetVersion() ) > 0 ) ;
        }

		/**
		 * @group RetieveLog
		 * Test si le RetrieveLog retourne bien une chaine de caractère encodées (bz19246)
		 */
		public function testRetrieveLogReturnAString ()
		{
			$this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->connection( 'astellia_admin', 'astellia_admin' ) );
			$this->assertTrue( is_string( $this->_soapClient->RetrieveLog( 0, 0, 0 ) ) ) ;
		}

		/**
		 * @group RetieveLog
		 * Test si le RetrieveLog retourne bien une chaine encodées en base64 (bz19246)
		 */
		public function testRetrieveLogReturnABase64EncodedString ()
		{
			$this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->connection( 'astellia_admin', 'astellia_admin' ) );
			$this->assertSame( 1, preg_match_all( "/\!/", base64_decode( $this->_soapClient->RetrieveLog( 0, 0, 0 ) ), $a ) );
		}

		/**
		 * @group RetieveLog
		 * Test si le RetrieveLog retourne bien moins d'octet que prévu
		 */
		public function testRetrieveLogReturnLessThanSpecifiedMaxSize ()
		{
			$testSizes = array( 10, 1024, 1234, 1968, 2563 );
			$this->assertEquals( TrendingAggregationApi::eOk, $this->_soapClient->connection( 'astellia_admin', 'astellia_admin' ) );
			foreach( $testSizes as $oneSizeToTest )
			{
				$this->assertLessThanOrEqual( $oneSizeToTest, strlen( $this->_soapClient->RetrieveLog( 0, 0, $oneSizeToTest ) ) );
			}
		}

        protected function _toArray( $stdClass )
        {
            foreach ( $stdClass as $key => $value )
            {
                if (is_array( $value ) ){
                    return $value;
                }
                return array( $value );
            }
        }
    }
?>
