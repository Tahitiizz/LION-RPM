<?php
/**
 * Classe qui gère des méthodes permettant de collecter des informations sur le fichier collecté
 * (date de la source, identifiant de la source, etc..)
 * 
 * @package Parser Huawei BSS 5.0
 * @author Stéphane Lesimple 
 * @version 5.00.00.00
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
		$this->system_name = get_sys_global_parameters("system_name");
		$dbConnection=new DatabaseConnection();
		$dBServices=new DatabaseServicesHuaBss($dbConnection);
		parent::__construct($dBServices,$params);
		//TODO MHT2 voir si motif toujours actualité avec nouveau format flat_file_name
		$dBServices->activateSourceFileByCounter("-\\\\s*","\$");
	} 
	
	
	/**
	 *  		
	 * @param String $flatfile objet flatFile
	 * @return string contenant la date au format yyyy-mm-dd hh:mm:ss
	 */
	public function getFiletime(FlatFile $flatfile){

		$pattern ="/.*_[0-9]{2}_([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})[0-9]{2}_.*/";
		$patternBSC32 ="/.*([0-9]{2})([0-9]{2})([0-9]{4})([0-9]{2})[0-9]{4}\..*/";
		// ex de nom de fichier source : A20080318.0100+0100-0115+0100_SubNetwork=ONRM_RootMo,SubNetwork=RLD01E,MeContext=Aeroporto2_UMTS_statsfile.xml
		if(preg_match($pattern, $flatfile->flat_file_name, $matches)) {		
			// *** date de début de capture
			$year = $matches[1];
			$month = $matches[2];
			$day = $matches[3];
			$startHour = $matches[4];
			// date de début de capture au format yyyy-mm-dd hh:mm:ss
			$dateFromFileName = $year."-".$month."-".$day." ".$startHour.":00:00";
			return $dateFromFileName;
			// contrôle de validité de la date de capture trouvée dans le nom du fichier source
		} elseif(preg_match($patternBSC32, $flatfile->flat_file_name, $matches)){
			
			$month = $matches[1];
			$day = $matches[2];
			$year = $matches[3];
			$startHour = $matches[4];
			// date de début de capture au format yyyy-mm-dd hh:mm:ss
			$dateFromFileName = $year."-".$month."-".$day." ".$startHour.":00:00";
			return $dateFromFileName;
			// contrôle de validité de la date de capture trouvée dans le nom du fichier source
		}
	}

}
?>