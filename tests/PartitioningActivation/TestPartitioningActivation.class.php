<?php
include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'partitioning/class/PartitioningActivation.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'models/ProductModel.class.php');

class TestPartitioningActivation extends PHPUnit_Framework_TestCase
{
    /**
     * Setup method
     */
    public function setUp()
    {
        $this->database = Database::getConnection();
        $this->partitioningActivation = new PartitioningActivation($this->database);
    }

    /**
     * Testing method that tests postgresql parameters
     */
    public function testCheckPgParameters()
    {
        $nonConformParameters = array();
        $pgExpectedConf = parse_ini_file(REP_PHYSIQUE_NIVEAU_0.PartitioningActivation::CONF_INI_FILE);
        foreach($pgExpectedConf as $parameter => $expectedValue) {
            $value = $this->database->getOne("SHOW $parameter");
            if($value != $expectedValue)
                $nonConformParameters[$parameter] = $expectedValue;
        }

        if(count($nonConformParameters) > 0) {
            $this->assertEquals($nonConformParameters, $this->partitioningActivation->checkPgParameters());
        }
        else {
            $this->assertTrue($this->partitioningActivation->checkPgParameters());
        }
    }

    /**
     * Testing checkVersion method
     */
    public function testCheckVersion()
    {
        // Test wrong version
        $database = $this->getMock('DatabaseConnection');
        $database->expects($this->any())
                ->method('getVersion')
                ->will($this->returnValue('8.2'));

        $partitioningActivation = new PartitioningActivation($database);
        $this->assertFalse($partitioningActivation->checkVersion());

        // Test correct version
        $database = $this->getMock('DatabaseConnection');
        $database->expects($this->any())
                ->method('getVersion')
                ->will($this->returnValue('9.1'));

        $partitioningActivation = new PartitioningActivation($database);
        $this->assertTrue($partitioningActivation->checkVersion());

        // Test older version
        $database = $this->getMock('DatabaseConnection');
        $database->expects($this->any())
                ->method('getVersion')
                ->will($this->returnValue('9.3'));

        $partitioningActivation = new PartitioningActivation($database);
        $this->assertTrue($partitioningActivation->checkVersion());
    }

    /**
     * Testing Postgresql limit
     */
    public function testIsPostgresqlConfigurationLimitReached()
    {
        /**
         * More than 4 partitioned T&A
         * Max locks per transaction limit not reached
         */
        $database = $this->getMock('DataBaseConnection');
        // Mocking getDbName()
        $database->expects($this->any())
                ->method('getDbName')
                ->will($this->returnValue($this->database->getDbName()));
        // Mocking getDatabases()
        $database->expects($this->any())
                ->method('getDatabases')
                ->will($this->returnValue(array($this->database->getDbName(),
                                                'bd1','bd2','bd3','bd4')));
        // Mocking changeDatabase
        $database->expects($this->any())
                ->method('changeDatabase')
                ->will($this->returnValue(true));
        // Mocking isPartitioned
        $database->expects($this->any())
                ->method('isPartitioned')
                ->will($this->returnValue(true));
        // Mocking getOne
        $database->expects($this->any())
                ->method('getOne')
                ->will($this->returnValue(100));
        // Should return true
        $partitioningActivation = new PartitioningActivation($database);
        $this->assertTrue($partitioningActivation->isPostgresqlConfigurationLimitReached());

        /**
         * 4 partitioned T&A
         * Max locks per transaction limit reached
         */
        $database = $this->getMock('DataBaseConnection');
        // Mocking getDbName()
        $database->expects($this->any())
                ->method('getDbName')
                ->will($this->returnValue($this->database->getDbName()));
        // Mocking getDatabases()
        $database->expects($this->any())
                ->method('getDatabases')
                ->will($this->returnValue(array($this->database->getDbName(),
                                                'bd1','bd2','bd3')));
        // Mocking changeDatabase
        $database->expects($this->any())
                ->method('changeDatabase')
                ->will($this->returnValue(true));
        // Mocking isPartitioned
        $database->expects($this->any())
                ->method('isPartitioned')
                ->will($this->returnValue(true));
        // Mocking getOne
        $database->expects($this->any())
                ->method('getOne')
                ->will($this->returnValue(5000));
        // Should return true
        $partitioningActivation = new PartitioningActivation($database);
        $this->assertTrue($partitioningActivation->isPostgresqlConfigurationLimitReached());

        /**
         * 4 partitioned T&A
         * Max locks per transaction limit not reached
         */
        $database = $this->getMock('DataBaseConnection');
        // Mocking getDbName()
        $database->expects($this->any())
                ->method('getDbName')
                ->will($this->returnValue($this->database->getDbName()));
        // Mocking getDatabases()
        $database->expects($this->any())
                ->method('getDatabases')
                ->will($this->returnValue(array($this->database->getDbName(),
                                                'bd1','bd2','bd3')));
        // Mocking changeDatabase
        $database->expects($this->any())
                ->method('changeDatabase')
                ->will($this->returnValue(true));
        // Mocking isPartitioned
        $database->expects($this->any())
                ->method('isPartitioned')
                ->will($this->returnValue(true));
        // Mocking getOne
        $database->expects($this->any())
                ->method('getOne')
                ->will($this->returnValue(100));
        // Should return false
        $partitioningActivation = new PartitioningActivation($database);
        $this->assertFalse($partitioningActivation->isPostgresqlConfigurationLimitReached());
    }

    /**
     * Testing gatherPartitioningInformation method
     */
    public function testGatherPartitioningInformation()
    {
        $myId = ProductModel::getProductId();
        $productInfos = $this->partitioningActivation->gatherPartitioningInformation(array($myId), 'test@astellia.com');
        $this->assertTrue(is_array($productInfos));
        foreach($productInfos as $pid => $values) {
            $this->assertTrue(array_key_exists('app_name', $values));
            $this->assertTrue(array_key_exists('app_dir', $values));
            $this->assertTrue(array_key_exists('ip_address', $values));
            $this->assertTrue(array_key_exists('database_name', $values));
            $this->assertTrue(array_key_exists('ssh_user', $values));
            $this->assertTrue(array_key_exists('ssh_port', $values));
            $this->assertTrue(array_key_exists('ssh_password', $values));
            $this->assertTrue(array_key_exists('email', $values));
        }
    }

    /**
     * Testing method createPartitioningFile
     */
    public function testCreatePartitioningFile()
    {
        $this->partitioningActivation->createPartitioningFile();
        $this->assertTrue(file_exists(PartitioningActivation::PARTITIONING_FILE));
        $this->partitioningActivation->deletePartitioningFile();
    }

    /**
     * Testing method writePartitioningFile
     */
    public function testWritePartitioningFile()
    {
        $myId = ProductModel::getProductId();
        $productInfos = $this->partitioningActivation->gatherPartitioningInformation(array($myId), 'test@astellia.com');
        $this->partitioningActivation->createPartitioningFile();
        $this->partitioningActivation->writePartitioningFile($productInfos);
        $filePartitioning = file(PartitioningActivation::PARTITIONING_FILE);
        $l = 0;
        foreach($productInfos as $pid => $values) {
            $expected = array_values($values);
            $inFile = explode(',',trim($filePartitioning[$l]));
            $this->assertEquals($expected, $inFile);
            $l++;
        }
        $this->partitioningActivation->deletePartitioningFile();
    }

    /**
     * testing method checkPartitioningFile
     */
    public function testCheckPartitioningFile()
    {
        $this->partitioningActivation->createPartitioningFile();
        $this->assertTrue(PartitioningActivation::checkPartitioningFile());
        $this->partitioningActivation->deletePartitioningFile();
        $this->assertFalse($this->partitioningActivation->checkPartitioningFile());
    }

    /**
     * Testing method parsePartitioningFile
     */
    public function testParsePartitioningFile()
    {
        $myId = ProductModel::getProductId();
        $productInfos = $this->partitioningActivation->gatherPartitioningInformation(array($myId), 'test@astellia.com');
        $this->partitioningActivation->createPartitioningFile();
        $this->partitioningActivation->writePartitioningFile($productInfos);
        $fetchedInfos = $this->partitioningActivation->parsePartitioningFile();
        $this->partitioningActivation->deletePartitioningFile();
        $this->assertEquals(array_values($productInfos),array_values($fetchedInfos));
    }

    /**
     * Testing method monitorPartitioning
     */
    public function testMonitorPartitioning()
    {
        // Preparing fake minitor file
        $fakeMonitorFile = '/tmp/'.$this->database->getDbName();

        // Preparing activation
        $myId = ProductModel::getProductId();
        $productInfos = $this->partitioningActivation->gatherPartitioningInformation(array($myId), 'test@astellia.com');
        $this->partitioningActivation->createPartitioningFile();
        $this->partitioningActivation->writePartitioningFile($productInfos);        

        // Testing running
        $expectedPercentage = 15;
        $expectedReturn = Array($productInfos[$myId]['app_name'] => $expectedPercentage);
        file_put_contents($fakeMonitorFile, time()."\n:30/30 ($expectedPercentage %) bla bla bla\n");
        $monitoring = $this->partitioningActivation->monitorPartitioning($productInfos);
        $this->assertEquals($expectedReturn,$monitoring);

        // Testing complete
        $expectedPercentage = 100;
        $expectedReturn = Array($productInfos[$myId]['app_name'] => "true");
        file_put_contents($fakeMonitorFile, time()."\n0\n");
        $monitoring = $this->partitioningActivation->monitorPartitioning($productInfos);
        $this->assertEquals($expectedReturn,$monitoring);

        // Testing failed
        $expectedReturn = Array($productInfos[$myId]['app_name'] => "false");
        file_put_contents($fakeMonitorFile, time()."\n5\n");
        $monitoring = $this->partitioningActivation->monitorPartitioning($productInfos);
        $this->assertEquals($expectedReturn,$monitoring);

        // Deleting files
        $this->partitioningActivation->deletePartitioningFile();
        unlink($fakeMonitorFile);
    }

    /**
     * Testing method fetchLogFiles
     */
    public function testFetchLogFiles()
    {
        // Preparing fake minitor file
        $fakeMonitorFile = '/tmp/'.$this->database->getDbName();
        file_put_contents($fakeMonitorFile, time()."\n:30/30 ($expectedPercentage %) bla bla bla\n");

        // Preparing activation
        $myId = ProductModel::getProductId();
        $productInfos = $this->partitioningActivation->gatherPartitioningInformation(array($myId), 'test@astellia.com');
        $this->partitioningActivation->createPartitioningFile();
        $this->partitioningActivation->writePartitioningFile($productInfos);
        $this->partitioningActivation->fetchLogFiles($productInfos);

        // Testing
        $this->assertTrue(file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/'.$this->database->getDbName()));

        // Cleaning
        $this->partitioningActivation->deletePartitioningFile();
        unlink($fakeMonitorFile);
        unlink(REP_PHYSIQUE_NIVEAU_0.'upload/'.$this->database->getDbName());
    }

    /**
     * Testing method getStartTime
     */
    public function testGetStartTime()
    {
        // Start Time
        $startTime = time();

        // Preparing fake minitor file
        $fakeMonitorFile = '/tmp/'.$this->database->getDbName();
        file_put_contents($fakeMonitorFile, $startTime."\n:30/30 (30 %) bla bla bla\n");

        // Preparing activation
        $myId = ProductModel::getProductId();
        $productInfos = $this->partitioningActivation->gatherPartitioningInformation(array($myId), 'test@astellia.com');
        $this->partitioningActivation->createPartitioningFile();
        $this->partitioningActivation->writePartitioningFile($productInfos);
        $result = $this->partitioningActivation->getStartTime($productInfos);

        // Testing
        $this->assertEquals($startTime,$result);

        // Cleaning
        $this->partitioningActivation->deletePartitioningFile();
        unlink($fakeMonitorFile);
    }

    /**
     * Testing method deletePartitioningFile
     */
    public function testDeletePartitioningFile()
    {
        // Preparing activation
        $myId = ProductModel::getProductId();
        $productInfos = $this->partitioningActivation->gatherPartitioningInformation(array($myId), 'test@astellia.com');
        $this->partitioningActivation->createPartitioningFile();
        $this->partitioningActivation->writePartitioningFile($productInfos);
        $this->partitioningActivation->deletePartitioningFile();

        // Testing
        $this->assertFalse(file_exists(PartitioningActivation::PARTITIONING_FILE));
    }

    /**
     * Testing method getMaxThreads
     */
    public function testGetMaxThreads()
    {
        $expectedResult = (int)exec('grep "processor" /proc/cpuinfo | wc -l');
        $result = PartitioningActivation::getMaxThreads();
        $this->assertEquals($expectedResult,$result);
    }
}
?>
