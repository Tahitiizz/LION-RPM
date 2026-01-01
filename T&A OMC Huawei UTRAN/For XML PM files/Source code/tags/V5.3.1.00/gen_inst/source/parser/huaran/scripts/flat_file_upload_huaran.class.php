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
		$pattern ="/A([0-9]{8}).([0-9]{4})([+,-][0-9]{4}){0,1}-[0-9]{4}([+,-][0-9]{4}){0,1}_.*/";
		// ex de nom de fichier source : A20180107.0100+0200-0130+0200_MDC01RNC.xml
		if(preg_match($pattern, $flatfile->flat_file_name, $matches)) {		
			// *** date de début de capture
			$year = substr($matches[1],0,4);
			$month = substr($matches[1],4,2);
			$day = substr($matches[1],6,2);
			$startHour = substr($matches[2],0,2);
			$startMin = substr($matches[2],2,2);
			// date de début de capture au format yyyy-mm-dd hh:mm:ss
			$dateFromFileName = $year."-".$month."-".$day." ".$startHour.":".$startMin.":00";
			return $dateFromFileName;
			// contrôle de validité de la date de capture trouvée dans le nom du fichier source
		} 
	}
	
}
?>