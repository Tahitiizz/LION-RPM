<?php

/**
 * 
 * Classe générique pour la collecte
 * @author g.francois
 * @see Ne pas ajouter la méthode « treatmentFileInvalid » (Cf. bug 14740).
 *
 */
abstract class Collect {
	
	/**
	 * 
	 * Accès aux services de connexion à la base de données
	 * @var DatabaseServices
	 */
	protected  $dbServices;
	/**
	 * 
	 * Tableau stockant le paramétrage des familles
	 * @var ParametersList
	 */
	protected $params;
	
	public function __construct(DatabaseServices $dbServices,ParametersList $params) {
		$this->dbServices = $dbServices;
		$this->params=$params;
	}
	
	/**
	 * Fonction qui retourne les informations de date correspondant à un fichier
	 * Si aucun argument n'est trouvé, le tableau retourné contient des valeurs NULL
	 *
	 * @param text $file chemin et nom du fichier à traiter
	 * @param text $template_name identifiant du template du fichier
	 * @param array $source_info tableau contenant les informations du fichier ('source_name' => le nom du fichier d'origine, 'heure_upload' => l'heure de téléchargement du fichier)
	 * @param string $location chemin d'accès au fichier (chemin distant)
	 * @return array $flat_file_arguments arguments du fichier (hour,day,month,year,duree de capture)
	 */
	function get_flat_file_arguments($file, $template_name, $source_info=array(), $location='')
	{
		// SLE2: pour l'instant, tous les fichiers huawei sont de la même forme : on ignore le $template_name
		// et on effectue le même traitement dans tous les cas (au lieu de faire un switch ($template_name)
		// la date/heure du fichier se trouve sur la 3e ligne (et suivantes), qui sont de la forme :
		// 2008-08-17 01:00,60,"BSC6000-VTE/Cell:LABEL=VTP - Phonehong-1, CellIndex=0",Reliable,17,0,17,[...]
		$fileName = $source_info['source_name'];
		
		$location = str_replace('//', '/',$location);
		$location=rtrim($location,"/");//bug 
		//ON RECUPERE LES 2 1ER LIGNES DU FICHIER
		//1- Contrôle que le fichier n'est pas vide
		if(filesize($file)==0){
			displayInDemon(__METHOD__." ERROR : No data in File : {$fileName}", "alert");
			sys_log_ast("Warning", $this->system_name, "Data Collect", "No data in File : {$fileName}", "support_1", "");
			return $this->getDefaultFlatFileArguments();
		}
				
		// on tente d'ouvrir le fichier
		if (($handle = fopen($file, "rt")) === false) {
			displayInDemon(__METHOD__." ERROR : lors de la tentative d'ouverture du fichier $file", "alert");
			sys_log_ast("Warning", $this->system_name, "Data Collect", "Can't open file '$file'", "support_1", "");
			return $this->getDefaultFlatFileArguments();
		}
		
		fclose($handle);
		$flatFile=new FlatFile();
		$flatFile->flat_file_location=$file;
		$flatFile->flat_file_template=$template_name;
		$flatFile->flat_file_name=$source_info['source_name'];
		$flatFile->hourOfUpload=$source_info["heure_upload"];
		// TODO : ereg_replace deprecated ?
		$flatFile->uploaded_flat_file_name = ltrim(str_replace('/', '_', $location.'_'.$source_info['source_name']),'_');
		//$flatFile->uploaded_flat_file_name=$source_info['source_name'];
		$fileTime=$this->getFiletime($flatFile);
		echo "fileTime : {$fileTime}\n";
		
		// verifions si la date est valide
		if (($timestamp = strtotime($fileTime)) === false) {
			displayInDemon(__METHOD__." ERROR : la date {$fileTime} semble etre invalide", "alert");
			sys_log_ast("Warning", $this->system_name, "Data Collect", "Invalid date found in '$file'", "support_1", "");
			return $this->getDefaultFlatFileArguments();
		}
		
		// ici, c'est bon, on y va
		$year  = date("Y", $timestamp);
		$month = $year  . date("m", $timestamp);
		$day   = $month . date("d", $timestamp);
		$hour  = $day   . date("H", $timestamp);
		$flat_file_arguments = array($hour,$day,$month,$year,3600); // matches[2]*60 == capture_duration en secondes	
		return $flat_file_arguments;
	}
	
	/**
	 * Fonction qui génère un identifiant unique pour la source à partir des données contenues dans celle-ci
	 * Cet identifiant est utilisé pour notamment la reprise de fichiers
	 * 
	 * @param text $file chemin et nom du fichier à traiter
	 * @param text $template_name identifiant du template du fichier
	 * @param text $source_name nom d'origine du fichier collecté
	 * @return text $uniq_id argument identifiant de manière unique la source de données
	 */
	public function get_unique_identifier($file, $template_name, $source_name) {	
		return $source_name.'_'.md5_file($file);
	}
	
	/**
	 * Fonction qui va mettre à jour des informations (hour,day,flat_file_uniqid)
	 * pour les fichiers secondaires issus d'un fichier principal
	 * 
	 * Le fichier principal contient les information de date alors que les fichiers secondaires n'en contiennent pas
	 * 
	 * Le lien entre le fichier principal et les fichiers secondaires se fait par les radicaux des fichiers qui sont les mêmes
	 * utiliser uniquement pour les fichiers d'Astellia
	 * 
	 */
	public function update_time_data($repertoire_archive) {
		//cas des fichiers avec plusieurs heures par fichier. problème de perf??
		$this->dbServices->completeDuplicatedFiles();
		
		$this->dbServices->updateTimeData($repertoire_archive);
	}
	
	/**
	* Fonction qui va analyser le nom du fichier pour retourner le type de transfert FTP qui sera utilisé
	* 
	* @param string $fichier_source le nom du fichier distant
	* @return string le type de connexion FTP à utiliser
	*/
	function get_file_type($fichier_source) {
		return 'FTP_BINARY';
	}
	
	/**
	* Fonction qui va analyser le type de fichier en cours de collecte et qui va appliquer un dos2unix pour les fichiers ASCII
	*
	* 11:36 21/11/2008 SCT : dans le cas d'un fichier ZIP, on va déplacer les fichiers extraits vers le répertoire 'flat_file_zip'
	* 
	* @param string $file nom du fichier à traiter
	* @param string $lib_element_naming_template template du groupe de fichier en cours de collecte
	* @return string le type de connexion FTP à utiliser
	*/
	public function fileTreatmentDos2Unix($file, $lib_element_naming_template) {
		// SLE2: dos2unix version PHP, 10-15 fois plus rapide que exec('dos2unix')
		// d'après des benchs disponibles sur simple demande ;)
		// on n'a jamais de cas binaires/zip en Huawei BSS
		if (($content = file_get_contents($file)) === false) {
			displayInDemon(__METHOD__." ERRROR : impossible de lire le contenu du fichier $file","alert");
			return;
		}
		$content = str_replace("\r\n", "\n", $content); // n'echoue jamais
		if (file_put_contents($file, $content) === false) {
			displayInDemon(__METHOD__." ERROR : impossible d'ecrire dans le fichier $file","alert");
		}
		unset($content); // histoire que le gc fasse bien son boulot
	}
	
	/**
	 * 
	 * Définit le tableau des valeurs temporelles par défaut utilisé par le CB
	 * @return array Tableau des valeurs par défaut
	 */
	protected function getDefaultFlatFileArguments() {
		return array('NULL','NULL','NULL','NULL','-1');
	}
	
	/**
	 * 
	 * Active les fichiers sources dont au moins un compteur est activé dans le contexte, désactive les autres (qui sont connus dans sys_field_reference)
	 * @return void
	 */
	protected function activateSourceFileByCounter($before=".*",$after=".*") {		
		 $this->dbServices->activateSourceFileByCounter($before,$after);
	}
	/**
	 * 
	 * Renvoie l'heure du fichier collecté
	 * @param FlatFile $flatfile
	 * @return string contenant la date au format yyyy-mm-dd hh:mm:ss
	 */
	public abstract function getFiletime(FlatFile $flatfile);
	protected $flat_file_info;
	function setFlatFileInfo($flat_file_info){
			$this->flat_file_info = $flat_file_info;
	}
}
?>