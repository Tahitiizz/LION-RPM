<?php
/**
 * Classe qui gère des méthodes permettant de collecter des informations sur le fichier collecté
 * (date de la source, identifiant de la source, etc..)
 * 
 * @package Parser Huawei BSS 5.3
 * @author YBT
 * @version 5.3.2.00
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

	} 
	
	
	/**
	 *  		
	 * @param String $flatfile objet flatFile
	 * @return string contenant la date au format yyyy-mm-dd hh:mm:ss
	 */
	public function getFiletime(FlatFile $flatfile){

		$pattern ="/A([0-9]{4})([0-9]{2})([0-9]{2})\.([0-9]{2})([0-9]{2})[+-]{1}.*_(.*)\.xml/";
		
		// ex de nom de fichier source : A20080318.0100+0100-0115+0100_SubNetwork=ONRM_RootMo,SubNetwork=RLD01E,MeContext=Aeroporto2_UMTS_statsfile.xml
		if(preg_match($pattern, $flatfile->flat_file_name, $matches)) {		
			$s_year=$matches[1];
			$s_month=$matches[2];
			$s_day=$matches[3];
			$s_hour=$matches[4];
			$s_minute=$matches[5];
			// yyyy-mm-dd hh:mm
			$dateFromFileName = $s_year.'-'.$s_month.'-'.$s_day.' '.$s_hour.':00';
			return $dateFromFileName;
			// contrôle de validité de la date de capture trouvée dans le nom du fichier source
		}else{
			
		}
	}

}
?>