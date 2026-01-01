<?php
/*
	25/11/2009 GHX
		- Fixed : launch of several times the same process manually
			-> Added function getProcessusAuto()
			-> Editing functions processusCanRun() and checkRunningProcessus()
	16/12/2009 GHX
		- Fixed BZ 9936 [REC][T&A IU 4.0][COLLECTE]: lancement de deux process en même temps
*/
?>
<?php

require_once $repertoire_physique_niveau0 . "class/Process.php";

/**
 * This class is usefully for processus gestion
 * @author	RBL - 15/10/2009
 */
class Scheduler{
	
	/**
	 * Database connection
	 */
	protected $m_db;
	
	protected $m_processRunningList;
		
	/**
	 * Constructor
	 * @param Object $p_DataBaseConnection Connection to database
	 */	
	function Scheduler(){
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->m_db = Database::getConnection();
		
		$this->m_processRunningList = array();
	}
	
	/**
	 * Return an array of processus in running mode
	 * The result is saved in attribut m_processRunningList
	 *
	 *	09:01 25/11/2009 GHX
	 *		- Editing function
	 *
	 * @see getProcessRunningList()
	 * Manual runs are not forgotten	 
	 * @return Process[] A list of processus
	 */
	function checkRunningProcessus(){
		
		$this->m_processRunningList = array();
		
		//Get running process (auto)
                // 09/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$ret = $this->m_db->executeQuery(	"SELECT spe.process, sdm.master_name " .
											"FROM sys_process_encours as spe JOIN sys_definition_master as sdm " .
											"ON spe.process = sdm.master_id::text " .
											"WHERE spe.encours = 1 AND " . 
											"spe.done = 0");
		$result = $this->m_db->getQueryResults($ret);
		
		foreach($result as $rs){
			$this->m_processRunningList[] = new Process($rs["process"], $rs["master_name"]);
		}
		echo "<br>";
		return $this->m_processRunningList;
	}
	
	/**
	 * Returns an array with a list of processus launched manually or auto
	 *
	 *	09:01 25/11/2009 GHX
	 *		- Added function
	 *
	 * @author GHX
	 * @return array
	 */
	public function getProcessusAuto ()
	{
		//Get running process (manual)
		$ret = $this->m_db->executeQuery(	"SELECT master_id, master_name " .
											"FROM sys_definition_master " .
											"WHERE auto = true");
		$result = $this->m_db->getQueryResults($ret);
		
		$m_processAuto = array();
		foreach($result as $rs){
			$m_processAuto[] = new Process($rs["master_id"], $rs["master_name"]);
		}
		echo "<br>";
		return $m_processAuto;
	} // End function getProcessusAuto
	
	/**
	 * Check if a processus can run
	 *
	 *	09:01 25/11/2009 GHX
	 *		- Editing function
	 *	14:28 16/12/2009 GHX
	 *		- Editing function
	 *
	 * @param $p_Processus
	 * @param boolean $auto (default true)
	 * @return boolean
	 */
	function processusCanRun($p_Processus, $auto = true ){
		
		$p_Processus->checkCompatibleProcessus();		
		$processRunningList = $this->checkRunningProcessus();
		
		// Checking with list of processus launched
		foreach($processRunningList as $processRunning){
			if ($p_Processus->isCompatible($processRunning) == false){
				return false;
			}
		}

		// 14:27 16/12/2009 GHX
		// Correctoin du BZ 9936
		if ( $auto )
		{
			// Checking with list of processus auto
			$processAuto = $this->getProcessusAuto();
			foreach($processAuto as $processRunning){
				if ($processRunning->getId() == $p_Processus->getId()){	//processus is compatible with itself
					continue;
				}
				if ($p_Processus->isCompatible($processRunning) == false){
					return false;
				}
			}
		}
		return true;		
	}
	
	/**
	 * Return a list of process in running mode
	 * Don't forget to run checkRunningProcessus before
	 * @see checkRunningProcessus
	 * @return array of Processus objects
	 */
	function getProcessRunningList(){
		return $this->m_processRunningList;		
	}
	
};

?>