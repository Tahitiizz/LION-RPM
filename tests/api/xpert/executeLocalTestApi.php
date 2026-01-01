<?php
/**
 * Ce fichier permet de tester en local l'API Xpert
 */
define('_CLASSPATH_', '../../../class/');

require_once('../../../api/xpert/class/Log.class.php');

require_once("../../../php/environnement_liens.php");

require_once('../../../api/xpert/class/XpertApi.class.php');

//A décommenter pour le debug : require_once('astellia/Log.php');


require_once('PHPUnit.php');

/**
 * Cette classe définie la suite de test unitaire de 
 * l'API Xpert
 *
 * A tester sur un T&A IU
 */
class XpertAPILocalTest extends PHPUnit_TestCase {
	
	private $m_client;
	
	 // constructor of the test suite
	//function __construct(){
	//	$this->PHPUnit_TestCase("XpertAPILocalTest");
	//}
	
	// Test un mauvais login 
    function testToBadLogin() {
    	    	
    	//connexion à l'API
        $this->assertTrue($this->m_client->connection('TestLogin','TestPassword') == "eNotConnected");
        
        //test accès getProduct
        $productList = array();
		$errorCode = "";
		list($productList, $errorCode) = $this->m_client->getProducts(0);    
		
		Log::getLog()->debug(print_r($productList,true));  
		
        $this->assertTrue(($errorCode == "eNotConnected") && (isset($productList->list) == false));
        
        //Test getFamily
        list($familyList, $errorCode) = $this->m_client->getFamily(0);
        
        $this->assertTrue(($errorCode == "eNotConnected") && (isset($familyList->list) == false));
        
        //test accès getIndicators
        list($indicatorList, $errorCode) = $this->m_client->getKpiAndCounter(15, "efferl", true, true, false);
        
        $this->assertTrue(($errorCode == "eNotConnected") && (isset($indicatorList->list) == false));
        
        //test accès getNetworkAggregationLevels
        
       	list($naList, $errorCode) = $this->m_client->getNetworkAggregationLevels(1,"efferl");
       	
       	$this->assertTrue(($errorCode == "eNotConnected") && (isset($naList->list) == false));
       	
       	list($parentList, $errorCode) = $this->m_client->getAllNetworkElements(1,'bsc');
       	
        //Log::getLog()->debug(print_r($parentList, true));
        
        $this->assertTrue(($errorCode == "eNotConnected") && (isset($parentList->list) == false));
        
    }
    
	// Test un bon login 
    function testGoodLogin() {
    	
    	// test connection
        $this->assertTrue($this->m_client->connection('astellia_admin','astellia_admin') == "eOk");
        
        $productList = array();
		$errorCode = "";
		list($productList, $errorCode) = $this->m_client->getProducts(0);
        
		//Log::getLog()->debug(print_r($productList->list[0]->getId(),true));
		
        $this->assertTrue(($errorCode == "eOk") && ($productList->list[0]->getId() == 1));
        
        // test getFamily
        $errorCode = "";
        list($familyList, $errorCode) = $this->m_client->getFamily(1);
        
       // Log::getLog()->debug($errorCode.print_r($familyList->list,true));
        
        $this->assertTrue(($errorCode == "eOk") && ($familyList->list[0] == "efferl"));
        
        //test accès getKpiAndCounter not detailled
        $errorCode = "";
        list($indicatorList, $errorCode) = $this->m_client->getKpiAndCounter(1, "efferl", false);       
       	
		//  Log::getLog()->debug("getIndicators : ".print_r($indicatorList, true));
        
        $this->assertTrue(($errorCode == "eOk") && ($indicatorList->list[0]->getCode() == "KPI_CODR_31A_Oc")
        					&& ($indicatorList->list[0]->getAggregationFormula() == "")
        					&& ($indicatorList->list[0]->getComment() == ""));

        //test accès à kpi and counter detailled
        $errorCode = "";
        list($indicatorList, $errorCode) = $this->m_client->getKpiAndCounter(1, $familyList->list[1], true);
        
        Log::getLog()->debug(print_r($indicatorList->list,true));               
        
        $this->assertTrue(($errorCode == "eOk") 
        					&& ($indicatorList->list[0]->getCode() == "KPI_A_CIC_ALLOCATION_TIME")
        					&& ($indicatorList->list[0]->getAggregationFormula() != "")
        					&& ($indicatorList->list[0]->getComment() != ""));
        					  
        
        
       	//test getNetworkAggregationLevels 
        list($naList, $errorCode) = $this->m_client->getNetworkAggregationLevels(1,"efferl");
        
        //Log::getLog()->debug(print_r($naList->list[0], true));
        
        $this->assertTrue(($errorCode == "eOk") && ($naList->list[0] == "cell"));
                
        //test getAllNetworkElements
       	list($parentList, $errorCode) = $this->m_client->getAllNetworkElements(1,'bsc');
       	
//        Log::getLog()->debug("getAllNetworkElements : ".print_r($parentList, true));
//        
//        Log::getLog()->debug("\$parentList->list[0]->getCode() = ".$parentList->list[0]->getCode());
//        Log::getLog()->debug("\$errorCode = ".$errorCode);
        
        $this->assertTrue(($errorCode == "eOk") && ($parentList->list[0]->getCode() == 3402)&& (count($parentList->list) == 8));
        
        //test getTimeAggregation
        list($talist, $errorCode) = $this->m_client->getTimeAggregation(1);
        
       // Log::getLog()->debug("\$talist = ".print_r($talist, true));
        
        $this->assertTrue(($errorCode == "eOk") && ($talist->list[0] == "hour"));
        
        //test getParentNetworkElements
        list($cellList, $errorCode) = $this->m_client->getChildrenNetworkElements(1, $parentList->list[0]->getCode(), 'bsc', 'cell');
        
		// Log::getLog()->debug("getParentNetworkElements = ".print_r($cellList,true));
		// Log::getLog()->debug("\$errorCode = ".$errorCode);
        
        $this->assertTrue(($errorCode == "eOk") && ($cellList->list[0]->getCode() == "20013_20451"));
       
		//on uniformise car l'SOAP ne génère que des stdClass       
        $stdIndicatorList = array();
        foreach($indicatorList->list as $indicator){
        	$stdIndicator = new stdClass();
        	$stdIndicator->m_sId = $indicator->getId();
        	$stdIndicator->m_sCode = $indicator->getCode();
        	$stdIndicator->m_sLabel = $indicator->getLabel();
        	$stdIndicator->m_eType = $indicator->getType();
        	
        	$stdIndicatorList[] = $stdIndicator;
        }
        
        //test setDataExports
        $dataExports = new APIDataExports();
        $dataExport->list[] = new stdClass();
        $dataExport->list[0]->m_sExportId = "";
        $dataExport->list[0]->m_sExportName = "test Xpert 1"; 
        $dataExport->list[0]->m_sExportDir = "/home/upload/xpert"; 
        $dataExport->list[0]->m_sFileName = "test.csv"; 
        $dataExport->list[0]->m_sFieldSeparator = ";"; 
        $dataExport->list[0]->m_sNetworkAggregation = "cell"; 
        $dataExport->list[0]->m_sTimeAggregation = "hour"; 
        $dataExport->list[0]->m_sFamily = "efferl"; 
        $dataExport->list[0]->m_bGenerateHourOnDay = 1; 
        $dataExport->list[0]->m_bShowNetworkHierarchy = 0; 
        $dataExport->list[0]->m_bUseCodeKPInRAW = 1; 
        $dataExport->list[0]->m_bUseCodeNAAndNE = 1; 
        $dataExport->list[0]->m_bAddTopoFile = 1;
        $dataExport->list[0]->m_eType = 0; 
        $dataExport->list[0]->m_indicators = $stdIndicatorList;
        $dataExport->list[] = new stdClass();          
        $dataExport->list[1]->m_sExportId = "";
        $dataExport->list[1]->m_sExportName = "test Xpert 2"; 
        $dataExport->list[1]->m_sExportDir = "/home/upload/xpert"; 
        $dataExport->list[1]->m_sFileName = "test2.csv"; 
        $dataExport->list[1]->m_sFieldSeparator = ";"; 
        $dataExport->list[1]->m_sNetworkAggregation = "bsc"; 
        $dataExport->list[1]->m_sTimeAggregation = "day"; 
        $dataExport->list[1]->m_sFamily = "efferl"; 
        $dataExport->list[1]->m_bGenerateHourOnDay = 1; 
        $dataExport->list[1]->m_bShowNetworkHierarchy = 0; 
        $dataExport->list[1]->m_bUseCodeKPInRAW = 1; 
        $dataExport->list[1]->m_bUseCodeNAAndNE = 1; 
        $dataExport->list[1]->m_bAddTopoFile = 1;
        $dataExport->list[1]->m_sType = 0; 
        $dataExport->list[1]->m_indicators = $stdIndicatorList;       
		
        //Log::getLog()->debug(print_r($dataExport->list[0], true));
       	
        $errorCode = $this->m_client->setDataExports(1,$dataExport);
        
        Log::getLog()->debug("\$errorCode = ".$errorCode);
        
        $this->assertTrue($errorCode == "eOk");
        
        //test getDataExports        
        list($dataExports, $errorCode) = $this->m_client->getDataExports(1, "efferl",3);
        
     //   Log::getLog()->debug("\$errorCode = ".$errorCode);
        Log::getLog()->debug("\$dataExports = ".print_r($dataExports->list[1],true));
        
        $this->assertTrue(($errorCode == "eOk")       
        && (isset($dataExports->list) == true)
        && ($dataExports->list[0]->getExportId() != "")
        && ($dataExports->list[0]->getExportName() == "test Xpert 1")        
        && ($dataExports->list[0]->getExportDir() == "/home/upload/xpert")
        && ($dataExports->list[0]->getFileName() == "test.csv")
        && ($dataExports->list[0]->getFieldSeparator() == ";")
        && ($dataExports->list[0]->getTimeAggregation() == "hour")
        && ($dataExports->list[0]->getNetworkAggregation() == "cell")
        && ($dataExports->list[0]->getFamily() == "efferl")
        && ($dataExports->list[0]->isGenerateHourOnDay() == true)
        && ($dataExports->list[0]->isShowNetworkHierarchy() == false)
        && ($dataExports->list[0]->isUseCodeKPInRAW() == true)
        && ($dataExports->list[0]->isUseCodeNAAndNE() == true)
        && ($dataExports->list[0]->isAddTopoFile() == true)
        && ($dataExports->list[0]->getType() == true)
        && (count($dataExports->list[0]->getIndicators()) == count($indicatorList->list))        
        && ($dataExports->list[1]->getExportId() != "")
        && ($dataExports->list[1]->getExportName() == "test Xpert 2")
        && ($dataExports->list[1]->getExportDir() == "/home/upload/xpert")
        && ($dataExports->list[1]->getFileName() == "test2.csv")
        && ($dataExports->list[1]->getFieldSeparator() == ";")
        && ($dataExports->list[1]->getTimeAggregation() == "day")
        && ($dataExports->list[1]->getNetworkAggregation() == "bsc")
        && ($dataExports->list[1]->getFamily() == "efferl")
        && ($dataExports->list[1]->isGenerateHourOnDay() == true)
        && ($dataExports->list[1]->isShowNetworkHierarchy() == false)
        && ($dataExports->list[1]->isUseCodeKPInRAW() == true)
        && ($dataExports->list[1]->isUseCodeNAAndNE() == true)
        && ($dataExports->list[1]->isAddTopoFile() == true)
        && ($dataExports->list[1]->getType() == 3)
        && (count($dataExports->list[1]->getIndicators()) == count($indicatorList->list)));
        
    }
    
	// called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
      $this->m_client = new XpertApi();
    }
	
 	// called after the test functions are executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function tearDown() {

    }   
    
}

Log::createInstance("logClient.txt");


Log::getLog()->setLevel(Log::$DEBUG);
Log::getlog()->setInstanceName("Xpert API");
Log::getLog()->begin("---API LOCAL TEST SUITE BEGIN---");
//Log::getLog()->debug("test api");


$suite  = new PHPUnit_TestSuite("XpertAPILocalTest");
$result = PHPUnit::run($suite);

echo $result -> toString();

?>
