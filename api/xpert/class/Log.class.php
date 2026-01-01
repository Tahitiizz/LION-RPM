<?php 

require_once(_CLASSPATH_."../php/edw_function.php");

 /**
 * Class de logging pour l'API Xpert  
 *
 * Reroutage vers le trace log de T&A / 
 * Les interfaces de la classe sont une copie conforme de astellia/Log.php 
 * dispo dans le module CVS PHP_COMMUN 
 * 
 */
class Log{			
		
	public static $ERROR = 'Critical';
	public static $WARN 	= 'Warning';
	public static $INFO 	= 'Info';		
	//Non dispo dans ce cas mais on garde pour la compatibilité
	public static $DEBUG = ''; 
	
	private $instanceName;
	
	///Instance du singleton
	private static $loggerInstance = null;

	/**
	 * Crée l'instance du logger.
	 */
	public static function createInstance(){
		Log::$loggerInstance = new Log();
		return Log::$loggerInstance;
	} 
	
	///retourne l'instance du logger
	public static function getLog(){
		if(Log::$loggerInstance == null){ 
			//si l'instance n'est pas crée on la crée par défaut
			Log::$loggerInstance = new Log();
		}
		return Log::$loggerInstance;
	}
	
	/**
	 * Constructeur
	 */
	private function __construct(){					
	}	
	
	
	function setInstanceName($name){
		$this->instanceName = $name;
	}
	
	/**
	 * 
	 * Méthode non utilisée mais gardée pour compatibilité
	 * 
	 * Positionne le niveau de trace
	 * 
	 * @param string $Level Log::$NOLOG, Log::$ERROR, Log::$WARN, Log::$INFO ou Log::$DEBUG   
	 */
	public function setLevel($Level){
	}

	/**
	 * Méthode non utilisée mais gardée pour compatibilité
	 * A appeler au debut d'une page en sécifiant le nom du fichier php
	 * Toutes les traces suivantes seront indenté jusqu'àl'appel de endPage()
	 * @see Log::endPage()
	 * @param string $PhpFile Exemple: "toto.php"
	 */	
	public function beginPage($PhpFile){		
	}
	/**
	 * Méthode non utilisée mais gardée pour compatibilité
	 * A appeler à la fin d'une page
	 */	
	public function endPage(){		
	}
	/**
	 * 
	 * A appeler au debut d'une fonction en spï¿½cifiant le nom de la fonction et les paramï¿½tres
	 * Toutes les traces suivantes seront indentï¿½ jusqu'ï¿½ l'appel de end()
	 * @see Log::end()
	 * @param string $Text Exemple: "toto('" . $str1 . "', " . $int1 . ")"
	 */	
	public function begin($Text){		
	}
	/**
	 * Méthode non utilisée mais gardée pour compatibilité
	 * A appeler a la fin d'une fonction
	 * @param string $Text Valeur de retour de la fonction
	 */	
	public function end($Text=""){		
	}
	/**
	 * 
	 * Ajoute une trace dans le fichier le trace_log T&A
	 * @param string $Level Niveau de la trace
	 * @param string $Text Trace a ajouter
	 */	
	protected function writeLine($Level, $Text, $Console=false) {	 
		$lines = split("\n", $Text);
			 
		for($i=0;$i<sizeof($lines);$i++){					
			sys_log_ast($Level, 'Trending&Aggregation', $this->instanceName, $lines[$i]);			
		}
	}
	
	/**
	 * Ajoute une trace de niveau ERROR dans le trace_log T&A
	 * @param string $Text Trace a ajouter
	 */	
	public function error($Text){		
		$this->writeLine(self::$ERROR, $Text);		
	}
	/**
	 * Ajoute une trace de niveau INFO dans le trace_log T&A
	 * @param string $Text Trace à ajouter
	 */	
	public function info($Text){
		$this->writeLine(self::$INFO, $Text);			
	}
	/**
	 * Ajoute une trace de niveau WARN dans le trace_log T&A 
	 * @param string $Text Trace à ajouter
	 */
	public function warn($Text){
		$this->writeLine(self::$WARN, $Text);					
	}
	/**
	 * Méthode non utilisée mais gardée pour compatibilité
	 * @param string $Text Trace à ajouter
	 */
	public function debug($Text){		
	}
	/**
	 * Méthode non utilisée mais gardée pour compatibilité
	 * Ferme le fichier de log
	 */
	public function close(){

	}		
}

?>
