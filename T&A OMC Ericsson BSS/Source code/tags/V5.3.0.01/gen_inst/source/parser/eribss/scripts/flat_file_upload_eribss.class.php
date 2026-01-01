<?php
/**
 * @package Parser_eribss
 * @author MDE
 */

include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));

include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/IncludeAll.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/Configuration.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/DatabaseServicesEriBss.class.php");


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
		
		$dBServices=new DatabaseServicesEriBss($dbConnection);
		
		parent::__construct($dBServices,$params);
		
		$this->dbServices->updateFlatFileLibNamingTemplate();
		
		$this->system_name = get_sys_global_parameters("system_name");
		
	    //ajout pour ajuster l'heure GMT à l'heure locale
		$date_timezone=get_sys_global_parameters("specif_PM-files_timezone");
		$serversTimezone=date_default_timezone_get();
	  	$correctTimezone=date_default_timezone_set($date_timezone);
	  	$this->offset=date('Z');
	  	date_default_timezone_set($serversTimezone);
		if((!$correctTimezone)&&($date_timezone!="")){
				sys_log_ast("Critical", $system_name, "Data Collect", "Timezone configured is incorrect ", "support_1", "");
		}
		
		if($date_timezone=="") $this->offset=0;//si parametre vide!

	} 
	
	
	/**
	 *  		
	 * @param String $fileName Nom du fichier
	 * @return string contenant la date au format yyyy-mm-dd hh:mm:ss
	 */
	
	public function getFiletime(FlatFile $flatFile){
            $fileName=basename($flatFile->flat_file_location);
            $file_name=$flatFile->flat_file_name;
            // fichier ASN1
            // nom de fichier type attendu : C20110121.1000-20110121.1100_HQBSC06:1001
            $regExp_ASN1="/^[A-Z]([0-9]{4})([0-9]{2})([0-9]{2})[.]([0-9]{2})([0-9]{2})[-]([0-9]{4})([0-9]{2})([0-9]{2})[.]([0-9]{2})([0-9]{2})[_]([A-Z0-9]*)[:][A-Z]{0,1}[0-9]{4}$/";
            
            if (preg_match($regExp_ASN1, $file_name, $regs))
            {
                $s_year=$regs[1];
                $s_month=$regs[2];
                $s_day=$regs[3];
                $s_hour=$regs[4];
                $s_minute=$regs[5];
                $e_year=$regs[6];
                $e_month=$regs[7];
                $e_day=$regs[8];
                $e_hour=$regs[9];
                $e_minute=$regs[10];
                $bsc=$regs[11];
                $startHour = "{$s_year}/{$s_month}/{$s_day} {$s_hour}:{$s_minute}:00";
                $startHourTs = strtotime($startHour);   //return false si la date n'est pas valide
                $endHour = "{$e_year}/{$e_month}/{$e_day} {$e_hour}:{$e_minute}:00";
                $endHourTs = strtotime($endHour);   //return false si la date n'est pas valide
                $duration=$endHourTs-$startHourTs;
                //décalage du au timezone configuré
				if($startHourTs != false)
                $startHourTs=$startHourTs+ $this->offset;
            }

            else
            {
                displayInDemon("File {$fileName} does not match expected regular expression {$regExp}");
            }
            
	    if($startHourTs != false)
        { //Si la date est valide
        	// yyyy-mm-dd hh:mm
            $dateFromFileName  = date('Y-m-d H',$startHourTs).":00";
			return $dateFromFileName;
        }else{
        	return false;
        }
	}
	
    function get_file_type($fichier_source)
    {
        //les fichier sgw et out sont des fichiers ascii
        $type_fichier = 'FTP_BINARY';

        return $type_fichier;
    } // End function get_file_type

    public function fileTreatmentDos2Unix($file, $lib_element_naming_template) {
    	//vide
    	//pour une bonne collecte des fichiers ASN1
    }
}

?>
