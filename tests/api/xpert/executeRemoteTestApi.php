<?php


	require_once("astellia/Log.php");
  	
	require_once("../../../api/xpert/class/APIDataExport.class.php");
	
	require_once('PHPUnit.php');
	
	/**
	 * Cette classe définie
	 * un tableau de dataexports
	 */
	class APIDataExports {
		public $list;
	}
	
	
	ini_set('soap.wsdl_cache_enabled', '0');
	ini_set('soap.wsdl_cache_ttl', '0'); 
	ini_set('soap.wsdl_cache_dir','/tmp/soapphp');
	
	/**
	 * Cette classe sert à exécuter une série de 
	 * test sur l'API Xpert. En simulant le client 
	 * distant.
	 *
	 */
	class XpertAPIRemoteTest extends PHPUnit_TestCase {
	
	//objet que l'on va tester
	private $m_client;
	
	
	/**
	 * On commence par vérifier que l'on a bien la liste 
	 * des fonctionnalités attendue.
	 * 
	 */
	function testFunctionAvailable() {
		Log::getLog()->begin("XpertAPIRemoteTest::testFunctionAvailable()");
		try {
			$functs = $this->m_client->__getFunctions();
			ob_start();
			foreach($functs as $func){
				var_dump($func);
			}
			$result = ob_get_contents();
			ob_end_clean();
			Log::getLog()->debug($result);
			
		}catch(SoapFault $e){
	    	
			Log::getLog()->error($e->__toString());
			$this->assertTrue(false);
		}	
		Log::getLog()->end();		
	}
	
	/**
	 * Test un mauvais login
	 * 
	 * puis essaye toutes les fonctions de l'API pour vérifié que l'on y a pas accès
	 * 
	 */ 
    function testToBadLogin() {
    	Log::getLog()->begin('XpertAPIRemoteTest::testToBadLogin()');
	    try {	
	    	
	    	//test connection API
			$result = $this->m_client->connection("testLogin","testPassword");	
			$this->assertTrue($result == "eNotConnected");
			
			//test getProducts
			$result = $this->m_client->getProducts(0);
					
			Log::getLog()->debug(print_r($result,true));
			
			$this->assertTrue(($result['eErrorCode'] == 'eNotConnected') && (isset($result['products']->list) == false));			
			
			//Test getFamily
	        $result = $this->m_client->getFamily(0);
	        
	        $this->assertTrue(($result['eErrorCode'] == "eNotConnected") && (isset($result['families']->list) == false));
						
			//test accès getKpiAndRaw
	        $result = $this->m_client->getKpiAndCounter(1, "efferl", false);
	        
	        Log::getLog()->debug(print_r($result,true));
	        
	        $this->assertTrue(($result['eErrorCode'] == 'eNotConnected') && (isset($result['indicators']->list) == false));
						
			$this->m_client->disconnection(0);
	    
	    }catch(SoapFault $e){
	    	
			Log::getLog()->error($e->__toString());
			$this->assertTrue(false);
		}
		Log::getLog()->end();
        
    }
    
	// Test un bon login 
    function testGoodLogin() {
    	
    	Log::getLog()->begin("XpertAPIRemoteTest::testGoodLogin()");
    	
        $this->assertTrue($this->m_client->connection('astellia_admin','astellia_admin') == "eOk");
                
		$result = $this->m_client->getProducts(12);
		
		//Log::getLog()->debug(print_r($result,true));
		
		$this->assertTrue(($result['eErrorCode'] == "eOk") 
						&& (isset($result['products']->list) == true) 
						&& (isset($result['products']->list->m_iId) == true)
						&& (isset($result['products']->list->m_sLabel) == true)
						&& (isset($result['products']->list->m_sDirectory) == true)
						&& (isset($result['products']->list->m_bMasterTopo) == true)
						&& (isset($result['products']->list->m_sSshUser) == true)
						&& (isset($result['products']->list->m_sSshPass) == true)
						&& (isset($result['products']->list->m_iProductType) == true));
		
		$errorCode = "";
		
		//test getFamily
        $result = $this->m_client->getFamily(1);
                $this->assertTrue(($result['eErrorCode'] == "eOk") && ($result['families']->list[0] == "efferl"));
        //Log::getLog()->debug(print_r($familyList->list,true));
        
        $this->assertTrue(($result['eErrorCode'] == "eOk") && ($result['families']->list[0] == "efferl"));						                
        
        //On stocke la deuxième famille pour le test à suivre 
        $secondFamily = $result['families']->list[1];
        
		//test accès getKpiAndCounter not detailled
		/////////////////////////////////////////////
        $result = $this->m_client->getKpiAndCounter(1, "efferl", false);
        
        //
        //Log::getLog()->debug("\$result['eErrorCode'] == '".$result['eErrorCode']."') && (\$result['indicators']->list[0]->m_sCode == '".$result['indicators']->list[0]->m_sCode."')");
        
        $this->assertTrue(($result['eErrorCode'] == 'eOk') 
        				  && ($result['indicators']->list[0]->m_sCode == "KPI_CODR_31A_Oc")
        				  && ($result['indicators']->list[0]->m_sAggregationFormula == "")
        				  && ($result['indicators']->list[0]->m_sComment == ""));
        
        //test accès getKpiAndCounter detailled
		/////////////////////////////////////////////
        				  
        $result = $this->m_client->getKpiAndCounter(1, $secondFamily, true);
                
        //Log::getLog()->debug(print_r($result,true));
        
	    $this->assertTrue(($result['eErrorCode']  == "eOk") 
        				&& ($result['indicators']->list[0]->m_sCode == "KPI_A_CIC_ALLOCATION_TIME")
        				&& ($result['indicators']->list[0]->m_sAggregationFormula != "")
        				&& ($result['indicators']->list[0]->m_sComment != ""));

        $stdindicatorList = $result['indicators']->list;//array();
        //On mémorise la liste des indicateurs, ça peut servir pour la suite
		/*foreach($result['indicators']->list as $indicator){
			$stdindicator = new stdClass();
			$stdindicator->m_sId = $indicator->m_sId;
			$stdindicator->m_sCode = $indicator->m_sCode;
			$stdindicator->m_sLabel =  $indicator->m_sLabel;
			$stdindicator->m_sAggregationFormula = $indicator->m_sAggregationFormula;
			$stdindicator->m_sComment = $indicator->m_sComment;
			$stdindicatorList[] = $stdindicator;
		}*/
        				
       	//test getNetworkAggregationLevels
       	/////////////////////////////////// 
        $result = $this->m_client->getNetworkAggregationLevels(1,"efferl");
        

        
        $this->assertTrue(($result['eErrorCode'] == "eOk") && ($result['networkAgregationLevels']->list[0] == "cell"));
        
		//test getAllNetworkElements
		////////////////////////////////
		
       	$result = $this->m_client->getAllNetworkElements(1,'bsc');       
        
        $this->assertTrue(($result['eErrorCode'] == "eOk") && ($result['networkElements']->list[0]->m_sCode == 3402)&& (count($result['networkElements']->list) == 8));
        
        //test getParentNetworkElements
        /////////////////////////////////
        $result = $this->m_client->getChildrenNetworkElements(1, 3402, 'bsc', 'cell');
        

        
        $this->assertTrue(($result['eErrorCode'] == "eOk") && ($result['networkElements']->list[0]->m_sCode == "20013_20451"));
      
        //test setDataExports
        $dataExport = new APIDataExports();
        $dataExport->list[] = new APIDataExport("", "test Xpert 1", "/home/upload/xpert", "test.csv" ,";" ,"cell", "hour", "efferl" ,1 ,0 ,1 ,1 ,1 ,1 ,0, $stdindicatorList);
        $dataExport->list[] = new APIDataExport("", "test Xpert 2", "/home/upload/xpert" ,"test2.csv" ,";" ,"bsc", "day", "efferl" ,1 ,0 ,1 ,1 ,1 ,1, 0, $stdindicatorList);		
       	// Log::getLog()->debug(print_r($dataExport->list[0],true));
        $result = $this->m_client->setDataExports(1,$dataExport);
                        
        $this->assertTrue($result == "eOk");
        
        //test getDataExports        
        $result = $this->m_client->getDataExports(1, "efferl",3);
     //   Log::getLog()->debug(print_r($result['APIDataExports']->list[0], true));
        
        $this->assertTrue(($result['eErrorCode'] == "eOk")       
        && (isset($result['APIDataExports']->list) == true)
        && ($result['APIDataExports']->list[0]->m_sExportId != "")
        && ($result['APIDataExports']->list[0]->m_sExportName == "test Xpert 1")        
        && ($result['APIDataExports']->list[0]->m_sExportDir == "/home/upload/xpert")
        && ($result['APIDataExports']->list[0]->m_sFileName == "test.csv")
        && ($result['APIDataExports']->list[0]->m_sFieldSeparator == ";")
        && ($result['APIDataExports']->list[0]->m_sTimeAggregation == "hour")
        && ($result['APIDataExports']->list[0]->m_sNetworkAggregation == "cell")
        && ($result['APIDataExports']->list[0]->m_sFamily == "efferl")
        && ($result['APIDataExports']->list[0]->m_bGenerateHourOnDay == true)
        && ($result['APIDataExports']->list[0]->m_bShowNetworkHierarchy == false)
        && ($result['APIDataExports']->list[0]->m_bUseCodeKPInRAW == true)
        && ($result['APIDataExports']->list[0]->m_bUseCodeNAAndNE == true)
        && ($result['APIDataExports']->list[0]->m_bAddTopoFile == true)
        && ($result['APIDataExports']->list[0]->m_iType == 3)
        && (count($result['APIDataExports']->list[0]->m_indicators) == count($stdindicatorList))        
        && ($result['APIDataExports']->list[1]->m_sExportId != "")
        && ($result['APIDataExports']->list[1]->m_sExportName == "test Xpert 2")
        && ($result['APIDataExports']->list[1]->m_sExportDir == "/home/upload/xpert")
        && ($result['APIDataExports']->list[1]->m_sFileName == "test2.csv")
        && ($result['APIDataExports']->list[1]->m_sFieldSeparator == ";")
        && ($result['APIDataExports']->list[1]->m_sTimeAggregation == "day")
        && ($result['APIDataExports']->list[1]->m_sNetworkAggregation == "bsc")
        && ($result['APIDataExports']->list[1]->m_sFamily == "efferl")
        && ($result['APIDataExports']->list[1]->m_bGenerateHourOnDay == true)
        && ($result['APIDataExports']->list[1]->m_bShowNetworkHierarchy == false)
        && ($result['APIDataExports']->list[1]->m_bUseCodeKPInRAW == true)
        && ($result['APIDataExports']->list[1]->m_bUseCodeNAAndNE == true)
        && ($result['APIDataExports']->list[1]->m_bAddTopoFile == true)
        && ($result['APIDataExports']->list[1]->m_iType == 3)
        && (count($result['APIDataExports']->list[1]->m_indicators) == count($stdindicatorList)));   
        
        //test disconnection
		$this->m_client->disconnection(0);
       	Log::getLog()->end();
    }
    
    //function 
    
	// called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    // initialise le client SOAP
    function setUp() {    
		  

		  
		// Create the client instance
		$this->m_client = new SoapClient('http://ast1816/~jerome/TA_CB_HEAD/api/xpert/index.php?wsdl',		
										array(	'location' => 'http://ast1816/~jerome/TA_CB_HEAD/api/xpert/index.php', 
												'soap_version' => SOAP_1_2,
												'style'    => SOAP_DOCUMENT,
									            'use'      => SOAP_LITERAL));
    }
	
 	// called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {

    }

}

Log::createInstance("logClient.txt");


Log::getLog()->setLevel(Log::$DEBUG);
Log::getLog()->begin("---API REMOTE TEST SUITE BEGIN---");
//Log::getLog()->debug("test api");
	
$suite  = new PHPUnit_TestSuite("XpertAPIRemoteTest");
$result = PHPUnit::run($suite);

echo $result->toString();	

Log::getLog()->end();

?>
