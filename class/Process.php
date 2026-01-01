<?php

require_once $repertoire_physique_niveau0 . "class/DataBaseConnection.class.php";

/**
 * This class is a processus (or master)
 * @author	RBL - 14/10/2009
 */
class Process{
	
	static public $COMPUTE			= 4;
	static public $COMPUTE_HOURLY	= 13;
	
	/**
	 * Database connection
	 */
	protected $m_db;
	
	/**
	 * Processus identifier	 
	 */
	protected $m_iId;
	
	/**
	 * Processus name	 
	 */
	protected $m_sName;
	
	protected $m_compatibleProcessList;
	
	/**
	 * Constructor
	 * @param $p_iId	Processus identifier
	 * @param $p_sName	Processus name
	 */
	function Process($p_iId=0, $p_sName=""){
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->m_db = Database::getConnection();
		
		$this->m_iId 					= $p_iId;
		$this->m_sName					= $p_sName;
		$this->m_compatibleProcessList 	= array();
	}
		
	/**
	 * Return an array of processus compatibles with $p_iProcessus
	 * (sys_definition_master_compatibility is unidirectionnal) 
	 * Stock the result in m_compatibleProcessList attribut
	 * @see getCompatibleProcessList()
	 * @param integer $p_iProcessus
	 * @return integer[] A list of processus
	 */
	function checkCompatibleProcessus(){
		
		$this->m_compatibleProcessList = array();
		
		$ret = $this->m_db->executeQuery(	"SELECT sdmc.master_compatible, sdm.master_name " .
											"FROM sys_definition_master_compatibility as sdmc JOIN sys_definition_master as sdm " .
											"ON sdmc.master_compatible = sdm.master_id " .
											"WHERE sdmc.master_id = " . $this->m_iId);
		
		$result = $this->m_db->getQueryResults($ret);
		
		foreach($result as $rs){
			$this->m_compatibleProcessList[] = new Process($rs["master_compatible"], $rs["master_name"]);
		}
		
		return $this->m_compatibleProcessList;
	}	

	/**
	 * Return the list of compatibles process
	 * Don't forget to call checkProcessusCompatibles() before
	 * @see checkCompatibleProcessus()
	 * @return Array of Process objects
	 */
	function getCompatibleProcessList(){
		return $this->m_compatibleProcessList;
	}
	
	/**
	 * Check if $p_Process is compatible
	 * @param $p_Process
	 * @return boolean
	 */
	function isCompatible($p_Process){	
			
		foreach($this->m_compatibleProcessList as $process){
			if ($process->getId() == $p_Process->getId()){
				return true;
			}			
		}
		
		return false;
	}
	
	/**
	 * Return processus name
	 * @return string
	 */
	function getName(){
		return $this->m_sName;
	}
	
	/**
	 * Return processus identifier
	 * @return integer
	 */
	function getId(){
		return $this->m_iId;
	}
};

?>