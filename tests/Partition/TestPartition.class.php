<?php
include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'models/ProductModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Partition.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Date.class.php');

class TestPartition extends PHPUnit_Framework_TestCase
{
    /**
     * Instance de connexion à la bdd
     * @var object
     */
    protected $_database = null;

    /**
     * Actions à éxécuter au lancement
     */
    public function setUp()
    {
        $this->_database = DataBase::getConnection();
    }

    /**
     * Test de la méthode getDataTable
     */
    public function testgetDataTable()
    {
        $date = date('Ymd');
        $expectedDataTable = 'edw_montest_matable_mabataille';
        $partition = new Partition($expectedDataTable, $date, $this->_database);
        $dataTable = $partition->getDataTable();
        $this->assertEquals($expectedDataTable, $dataTable);
    }

    /**
     * Test de la méthode getName
     */
    public function testgetName()
    {
        $date = date('Ymd');
        $dataTable = 'edw_montest_matable_mabataille';
        $expectedPartition = $dataTable.'_'.$date;
        $partition = new Partition($dataTable, $date, $this->_database);
        $partitionName = $partition->getName();
        $this->assertEquals($expectedPartition, $partitionName);
    }

    /**
     * Test de la méthode exists
     */
    public function testexists()
    {
        $date = date('Ymd');
        $dataTable = 'edw_montest_matable_mabataille';
        $expectedPartition = $dataTable.'_'.$date;
        $partition = new Partition($dataTable, $date, $this->_database);
        $this->assertFalse($partition->exists());
        $this->_database->execute("CREATE TABLE $expectedPartition (toto integer)");
        $this->assertTrue($partition->exists());
        $this->_database->execute("DROP TABLE IF EXISTS $expectedPartition");
    }

    /**
     * Test de la méthode drop
     */
    public function testdrop()
    {
        $date = date('Ymd');
        $dataTable = 'edw_montest_matable_mabataille';
        $expectedPartition = $dataTable.'_'.$date;
        $partition = new Partition($dataTable, $date, $this->_database);
        $this->_database->execute("CREATE TABLE $expectedPartition (toto integer)");
        $this->assertTrue($partition->exists());
        $partition->drop();
        $this->assertFalse($partition->exists());
    }

    /**
     * Test de la méthode truncate
     */
    public function testtruncate()
    {
        $date = date('Ymd');
        $dataTable = 'edw_montest_matable_mabataille';
        $expectedPartition = $dataTable.'_'.$date;
        $partition = new Partition($dataTable, $date, $this->_database);
        $this->_database->execute("CREATE TABLE $expectedPartition (toto integer)");
        $this->_database->execute("INSERT INTO $expectedPartition VALUES (1)");
        $partition->truncate();
        $result = $this->_database->getOne("SELECT count(*) FROM $expectedPartition");
        $this->assertEquals("0",$result);
        $partition->drop();
    }

    /**
     * Test de la méthode create
     */
    public function testcreate()
    {
        $date = date('Ymd');
        $dataTable = 'edw_test_family_axe1_raw_na_day';
        $this->_database->execute("CREATE TABLE $dataTable (day integer, week integer, month integer)");
        $expectedPartition = $dataTable.'_'.$date;
        $partition = new Partition($dataTable, $date, $this->_database);
        $partition->create();
        $this->assertTrue($partition->exists());
        $partition->drop();
        $this->_database->execute("DROP TABLE IF EXISTS $dataTable");
    }
}

?>
