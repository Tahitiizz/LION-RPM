<?php
/* 
 *      @author ACS
 *
 *  25/10/2011 ACS BZ 24399 Files are not deleted on distant slave
 *  24/10/2011 ACS BZ 24348 Successful message even if no files have been deleted
 * 
 *	03-10-2011 - Copyright Astellia
 *		- Mantis 615: DE Data reprocessing IHM
 *
 */

/**
 *      
 */
class DataReprocessing
{

    /**
     * Database connection
     * @var DataBaseConnection $database
     */
    private $database;

    /**
     * Reprocessing mode (0 -> reprocessing files; 1 -> delete files)
     */
    private $mode;

    /**
     * Date(s) - array()
     */
    private $dates;

    /**
     * Connection(s) - array()
     */
    private $connections;

    /**
     * Product id
     */
    private $productId;


    /**
     * Constructeur
     */
    public function __construct(DataBaseConnection $database, $productId = '') {
		$this->database = $database;
		$this->productId = $productId;
    }

	public function getActiveConnections() {
		$connections = array();
		
        $rqConnectionList = 'SELECT id_connection, connection_name FROM sys_definition_connection WHERE on_off = 1';
        $rqResult = $this->database->execute($rqConnectionList);
        while ($row = $this->database->getQueryResults($rqResult, 1)) {
        	$connections[$row['connection_name']] = $row['id_connection'];
        }
        return $connections;
	}
    
	public function getAvailableDates($idProduct) {
		$availableDates = array();
		$archiveDayToLive = get_sys_global_parameters("flatFileArchiveDaysToLive", "", $idProduct);
		$maxArchiveDate = date('Ymd', time() - $archiveDayToLive * 3600 * 24);
		
        $rqDateList = 'SELECT distinct(day) FROM sys_flat_file_uploaded_list_archive WHERE day > '.$maxArchiveDate.' ORDER BY day DESC';
        $rqResult = $this->database->execute($rqDateList);
        while ($row = $this->database->getQueryResults($rqResult, 1)) {
        	$availableDates[$this->splitDate($row['day'])] = $row['day'];
        }
        return $availableDates;
	}
	
	private function splitDate($date) {
		return substr($date, 6, 2).'/'.substr($date, 4, 2).'/'.substr($date, 0, 4);
	}
	
	public function setMode($mode) {
		$this->mode = $mode;
	}
	
	public function getMode() {
		return $this->mode;
	}
	
	public function setDates($dates) {
		$this->dates = $dates;
	}
	
	public function getDates() {
		return $this->dates;
	}
	
	public function setConnections($connections) {
		$this->connections = $connections;
	}
	
	public function getConnections() {
		return $this->connections;
	}
	
	/**
	 * check data of the object
	 * 
	 * 0 - success
	 * 1 - pb on "mode" attribute
	 * 2 - pb on "dates" attribute
	 * 3 - pb on "connections" attribute
	 * 
	 */
	public function checkData() {
		$result = 0;
		
		// check mode
		if ($this->mode != 0 && $this->mode != 1) {
			$result = 1;
		}
		// check dates
		else if (sizeOf($this->dates) <= 0) {
			$result = 2;
		}
		// check connections
		else if ($this->mode == 1 && sizeOf($this->connections) <= 0) {
			$result = 3;
		}
		
		return $result;
	}
	
	
    // 24/10/2011 ACS BZ 24348 Successful message even if no files have been deleted
	public function process() {
		$result = 0;
		
		if ($this->mode == 0) {
			$result = $this->setReprocessForArchivesFiles();
		}
		else if ($this->mode == 1)  {
			$result = $this->removeArchiveFiles();
		}
		
		return $result;
	}
	
	
	/**
	 * retrieve targeted archive files
	 */
	public function retrieveArchivesFilesList() {
		$rqArchiveFiles = 'SELECT uploaded_flat_file_name, flat_file_location FROM sys_flat_file_uploaded_list_archive WHERE '.$this->getWhereClause();
		
        $rqResult = $this->database->execute($rqArchiveFiles);
        $archiveFiles = array();
        while ($row = $this->database->getQueryResults($rqResult, 1)) {
        	$archiveFiles[$row['uploaded_flat_file_name']] = $row['flat_file_location'];
        }
		return $archiveFiles;
	}
	
	
	public function removeArchiveFiles() {
		$archiveFiles = $this->retrieveArchivesFilesList();
		$nbArchivesFiles = sizeof($archiveFiles);
		
		// 25/10/2011 ACS BZ 24399 Files are not deleted on distant slave
		$ProductModel = new ProductModel($this->productId);
		foreach ($archiveFiles as $archiveLocation) {
			$command = "rm -f ".$archiveLocation;
			$ProductModel->execCommand($command);
		}
		
		$rqArchiveFiles = 'DELETE FROM sys_flat_file_uploaded_list_archive WHERE '.$this->getWhereClause();
		
        $this->database->execute($rqArchiveFiles);
        
        return $nbArchivesFiles;
	}
	
	private function getWhereClause() {
		$rqArchiveFilesWhere = '(';
		foreach ($this->dates as $date) {
			$rqArchiveFilesWhere .= 'day = '.$date.' OR ';
		}
		$rqArchiveFilesWhere .= "false)";

		// in case of "Delete files", filter on connections
		if ($this->mode == 1) {
			$rqArchiveFilesWhere .= " AND (";
			foreach ($this->connections as $connection) {
				$rqArchiveFilesWhere .= 'id_connection = '.$connection.' OR ';
			}
			$rqArchiveFilesWhere .= "false)";
		}
		
		return $rqArchiveFilesWhere;
	}
	
	public function setReprocessForArchivesFiles() {
		$rqArchiveFiles = 'UPDATE sys_flat_file_uploaded_list_archive SET reprocess = 1 WHERE '.$this->getWhereClause();
		
        $this->database->execute($rqArchiveFiles);
        
        return $this->database->getAffectedRows();
	}
	
	public function countStoppedProcesses() {
		$stoppedProcesses = 'SELECT count(*) FROM sys_definition_master WHERE on_off = 0 AND visible = 1';
		
		return $this->database->getOne($stoppedProcesses);
	}
	
}
?>
