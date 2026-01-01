<?php
/**
 * Classe qui gère des méthodes permettant de collecter des informations sur le fichier collecté
 * (date de la source, identifiant de la source, etc..)
 * 
 * @package Parser Huawei Utran
 * @author Matthieu HUBERT 
 * @version 5.2.0.00
 *
 */

include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));

include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/IncludeAllSpecific.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/Configuration.class.php");




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
		
		$dBServices=new DatabaseServicesHuaRan($dbConnection);
		
		parent::__construct($dBServices,$params);
		$this->activateSourceFileByCounter("-\\\\s*","\\\\s*\$");
		
	} 
	
	
	/**
	*
	* @param String $flatFile object FlatFile reprsentant le fichier source
	* @return string contenant la date au format yyyy-mm-dd hh:mm:ss
	*/
	public function getFiletime(FlatFile $flatfile){
		$dateFromFileName=false;
		$handle = fopen($flatfile->flat_file_location, "r");
		$dates=array();
		while (($data = fgetcsv($handle,1000,",")) !== FALSE) {
		    $dates[$data[0]]=1;
		}
		$firstFoundDate=true;
		foreach ($dates as $date => $useless) {
			if(preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})/", $date,$matches)){
				$year = $matches[1];
				$month = $matches[2];
				$day = $matches[3];
				$startHour = $matches[4];
				if($firstFoundDate){
					// date de début de capture au format yyyy-mm-dd hh:mm:ss
					$dateFromFileName = $year."-".$month."-".$day." ".$startHour.":00:00";
					$firstFoundDate=false;
				}else{
					$hour="$year$month$day$startHour";
					displayInDemon("File {$flatfile->flat_file_name} should also be integrated in for hour $hour");
					$this->dbServices->insertInSysList($hour,$flatfile);
				}
			}
		}
		return $dateFromFileName;
	}
	
}
?>