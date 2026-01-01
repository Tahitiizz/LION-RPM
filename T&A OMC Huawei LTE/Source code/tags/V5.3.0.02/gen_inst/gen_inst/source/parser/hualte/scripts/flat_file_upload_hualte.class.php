<?php
/*
 * @package Parser_erilte
 * @author YBT
 * @version 5.3.0.00
 */

include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));

include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/Configuration.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/DatabaseServicesHuaLte.class.php");

class parser_upload extends Collect
{
	/**
	 * Constructeur qui initialise l'execution de la requête
	 */
	function __construct()
	{
		$conf = new Configuration();
		$params = $conf->getParametersList();
		$dbConnection=new DatabaseConnection();
		//$dBServices=new DatabaseServices($dbConnection);
		$dBServices=new DatabaseServicesHuaLte($dbConnection);
		parent::__construct($dBServices,$params);
		$this->system_name = get_sys_global_parameters("system_name");
		//$this->activateSourceFileByCounter();
		//$dBServices->activateGlobalCounters();
		
		
	} 
	
	
	/**
	 *  		
	 * @param String $fileName Nom du fichier
	 * @return string contenant la date au format yyyy-mm-dd hh:mm:ss
	 */
	public function getFiletime(FlatFile $flatFile){
		$fileName=$flatFile->flat_file_name;
		
		$pattern="/A([0-9]{4})([0-9]{2})([0-9]{2})\.([0-9]{2})([0-9]{2})[+,-]([0-9]{4})\-([0-9]{2})([0-9]{2})[+,-]([0-9]{4})_([[:print:]]+)\.xml/i";
		if( preg_match($pattern, $fileName, $regs)){
			$s_year=$regs[1];
			$s_month=$regs[2];
			$s_day=$regs[3];
			$s_hour=$regs[4];
			$s_minute=$regs[5];
			$e_year=$regs[1];
			$e_month=$regs[2];
			$e_day=$regs[3];
			$e_hour=$regs[7];
			$e_minute=$regs[8];
			$enodeB=$regs[10];
			
			// yyyy-mm-dd hh:mm
			$dateFromFileName = $s_year.'-'.$s_month.'-'.$s_day.' '.$s_hour.':00';
			return $dateFromFileName;
		}

	}


}

?>
