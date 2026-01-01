<?php
/**
 * 
 * L'objet ProcessManager est unique (pattern singleton). Il est chargé de créer les processus,
 * et il sait attendre que l'ensemble des processus sont terminés.
 * @author m.diagne
 *
 */
class ProcessManager{

	/**
	 * 
	 * Objet unique créé à partir de la classe ProcessManager
	 */
	// doit etre static puisque doit etre accessible par la methode statique getInstance().
	private static $processManager;
		
	/**
	 * 
	 * Nombre de processus simultanés possibles
	 */
	static private $maxNbOfSimultaneousProcess;
	
	
	/**
	 * 
	 * Tableau de stockage des processus en cours (= liste des process tournante)
	 * @var unknown_type
	 */
	private $runningProcesses;
	
	/**
	 * 
	 * File d'attente fifo
	 */
	private $toBeProcessedQueue;
	
	/**
	 * 
	 * Liste de tous les processus terminés
	 * (nécessaire pour afficher des logs)
	 */
	private $listOfTerminatedProcess;
	
	/**
	 * 
	 * Dossier ou sont sauvegardées les variables au format sérialisé
	 */
	private $defaultVarDirectory;
	
	/**
	 * nombre de secondes à attendre avant de revérifier le statut des processus courants
	 */
	protected static $secondsToWait;
	
	/**
	 * nombre de secondes écoulées avant l' affichage de log periadiques dans le traceLog (waitEndOfAllProcess())
	 */
	protected static $logPeriod;
	
	/**
	 * 
	 * Constructeur : la classe ProcessManager est un pattern singleton
	 */
	private function ProcessManager(){
		// nom du produit (ex : eribss)
		$this->module = strtolower(get_sys_global_parameters("module"));
		
		// chemin absolu dudossier initial de travail du process créé
		$this->cwd = REP_PHYSIQUE_NIVEAU_0 . "parser/{$this->module}/scripts/";
		
		// fichier de log (file_demon) vers lequel sera redirigée la sortie du process créé
		$filedemon=REP_PHYSIQUE_NIVEAU_0.'file_demon/demon_'.date('Ymd').'.html';
		$this->descriptorspec= array(
		   0 => array("pipe", "r"),  // stdin est un pipe où le processus va lire
		   1 => array("file", $filedemon,"a"), // stdout est un pipe où le processus va écrire
		   2 => array("file", $filedemon, "a") // stderr est un fichier
		);
		
		// nombre maximum de processus que l'on souhaite avoir simultanément
		self::$maxNbOfSimultaneousProcess=self::getMaxNbOfSimultaneousProcess();
		
		//nbr de seconde à attendre par defaut
		self::$secondsToWait=2;
		
		//periode par defaut 2 min = 120s
		self::$logPeriod=240;
		
		// processus en cours
		$this->runningProcesses=array();
		
		// file d'attente des processus à créer
		$this->toBeProcessedQueue=array();
		
		// liste des processus terminés
		$this->listOfTerminatedProcess=array();
		
		// repertoire pour sauvegarder les variables sérialisées
		$this->defaultVarDirectory=REP_PHYSIQUE_NIVEAU_0 . "parser/variables/";
		
		// on crée ce repertoire si besoin
		if(!file_exists($this->defaultVarDirectory)) {
			$directoryCreated = mkdir($this->defaultVarDirectory);
			if($directoryCreated == FALSE) {
				$message="Error: unable to create the directory {$this->defaultVarDirectory}";
				sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
				displayInDemon($message,'alert');
			}
		}
	}
	
	/**
	 * 
	 * Retourne l'objet unique de la classe (pattern singleton)
	 */
	public static function getInstance(){
		if(isset(self::$processManager)){
			return self::$processManager;
		}else{
			ProcessManager::$processManager=new ProcessManager();
			return self::$processManager;
		}
	}
	
	/**
	 * 
	 * Retourne le nb de coeurs de processeur disponibles
	 */

	// 1) initialiser à 12
	// 2) tester si "/proc/cpuinfo" existe
	// 3) vérifier que $maxNbOfSimultaneousProcess ne reste pas égal à 0
	public static function getMaxNbOfSimultaneousProcess(){
		// inspiré de la classe « class\compute.class.php » du CB
		$defaultMaxNbOfProcess=12;
		if(file_exists('/proc/cpuinfo')){
			$maxNbOfSimultaneousProcess = 0;
			$cpuInfoLines = file('/proc/cpuinfo');
			foreach($cpuInfoLines as $line) {
				if(preg_match('/^processor/',$line)) $maxNbOfSimultaneousProcess ++;
			}
			//retourne le nombre de processeur dans /proc/cpuInfo (12 si 0
			return ($maxNbOfSimultaneousProcess==0?$defaultMaxNbOfProcess:$maxNbOfSimultaneousProcess) ;	
		}
		
		displayInDemon("warning: le fichier '/proc/cpuinfo' n'existe pas");
		return $defaultMaxNbOfProcess;

		
	}
	
	/**
	 * 
	 * Créée le processus cmd (en passant la variable $env)
	 * ou bien le stocke dans la file d'attente
	 * @param $cmd
	 * @param $env
	 */
	public function launchProcess($cmd, $env){
		// si le nb max de process simultanés souhaité n'est pas encore atteint
		if(count($this->runningProcesses)<self::$maxNbOfSimultaneousProcess){
			// creation du processus
			$process = proc_open($cmd, $this->descriptorspec, $pipes, $this->cwd);
			// si la creation s'est bien passee
			if (is_resource($process)){
				$status=proc_get_status($process);
				if($status["running"]==TRUE) {
					$pid = $status["pid"];
					// on écrit les infos dont le processus a besoin
					$isWritten=fwrite($pipes[0], serialize($env));
					if(!$isWritten) {
						$message="Error: Impossible to write to the input pipe of process $pid.";
						sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
						displayInDemon($message,'alert');
					}
					fclose($pipes[0]);
					// on met à jour la liste des processus en cours
					$this->runningProcesses[$pid]=$process;
				}else{
					displayInDemon("warning: le process vient d'etre cree mais il n'est deja plus en cours.",'alert');
				}
			}else{
				$message="Error: unable to launch command $cmd using proc_open";
				sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
				displayInDemon($message,'alert');
			}
		}else{
			// on stocke en file d'attente la demande de creation de processus
			$processParam=array("cmd" => $cmd, "parameters" => $env);
			array_push($this->toBeProcessedQueue, $processParam);
		}
	}
	
	/**
	 * Attend la fin de tous les process lancés en parallele.
	 */
	public function waitEndOfAllProcess($displayLog=TRUE,$fileType="PmFile"){
		// pour savoir quand on doit afficher des logs
		$counter=0;
		// tant qu'il reste des processus en cours
		while(count($this->runningProcesses)!=0){
			// pour chaque processus en cours
			foreach ($this->runningProcesses as $pid => $process){

				$status=proc_get_status($process);
				//le processus est terminé
				if($status["running"]==FALSE){
					$return_value = proc_close($process);
					unset($this->runningProcesses[$pid]);
					$this->listOfTerminatedProcess[$pid]=$status["exitcode"];
					if($status["exitcode"]<0) {
						$message="Error: process $pid exited with a negative exitcode";
						sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
						displayInDemon($message,'alert');
					}
				}
			}
			
			// peut-on démarrer un des processus de la file d'attente ?
			while((count($this->toBeProcessedQueue)!=0)&&(count($this->runningProcesses)<self::$maxNbOfSimultaneousProcess)){
				// script et paramètres du processus à lancer
				$processParam=$this->toBeProcessedQueue[0];
				// script
				$cmd=$processParam["cmd"];
				// paramètres (ex : conditions)
				$env=$processParam["parameters"];
				// création du processus
				$process = proc_open($cmd, $this->descriptorspec, $pipes, $this->cwd);
				if (is_resource($process)){
					$status=proc_get_status($process);
					if($status["running"]==TRUE){
						$test=fwrite($pipes[0], serialize($env));fclose($pipes[0]);
						//nouveau processus ajouté
						$this->runningProcesses[$status["pid"]]=$process;
						//la file d'attente se vide (= on dépile un élément au début du tableau)
						array_shift($this->toBeProcessedQueue);
					}else displayInDemon("warning: le process vient d'etre cree mais il n'est deja plus en cours.",'alert');
				}else{
					$message="Error: unable to launch command $cmd using proc_open";
					sys_log_ast("Critical", "Trending&Aggregation", __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
					displayInDemon($message,'alert');
				}
			}
			
			
			//-----------------------------periodique logs of Processes
			// dans le tracelog, il faut afficher un message INFO
			// équivalent au message de la librairie V2, ex : 8 XML files parsed for hour 2012062504 
			// toutes les 2 minutes environ, si possible en indiquant le % de processus terminated
						
			// affichage toutes les 2 minutes
			$counter++;

			
			// si l'affichage de logs est souhaité
			// et si le temps passé est un multiple de 120 ($logPeriod) secondes (= toutes les 2 minutes)
			// alors on génère des logs
			
			if($displayLog && (($counter*self::$secondsToWait)%self::$logPeriod==0)){
				//percent=nbrdeProcessusTerminée/(nbrProcessusEnAttente+nbrProcessusCourant+nbrdeProcessusTerminée)*100				
				$percent=count($this->listOfTerminatedProcess)/(count($this->listOfTerminatedProcess)+count($this->toBeProcessedQueue)+count($this->runningProcesses))*100;
				$percent=floor($percent);
				sys_log_ast("Info", "Trending&Aggregation", __T('A_TRACELOG_MODULE_LABEL_COLLECT'),"Parsing ongoing: $percent % done" , "support_1", "");
				
				//processus en cours
				displayInDemon(count($this->runningProcesses) ." running processes",'list');
				
				//processus terminés
				if(count($this->listOfTerminatedProcess)!=0) displayInDemon(count($this->listOfTerminatedProcess) ." terminated processes",'list');
				else displayInDemon("No terminated process\n");
				
				//commandes à executer (= processus en attente)
				if(count($this->toBeProcessedQueue)>0){
					displayInDemon(count($this->toBeProcessedQueue)." commands to run",'list');
				}else displayInDemon("No commands left",'list');
				
				echo "processing ...\n";
				
				//ajustement de la variable self::$logPeriod en fonction de la durée des process
				if($counter*self::$secondsToWait>=2*60*60){//si process tourne depuis 2 heures
					self::$logPeriod=10*60;//periode de 10 min	
				}elseif ($counter*self::$secondsToWait>=1*60*60){//si process tourne depuis 1 heure
					self::$logPeriod=6*60;//periode de 6 min
				}
			}
			//-----------------------------

			// arrête l'exécution durant "$secondsToWait" secondes
			sleep(self::$secondsToWait);
			
		}
		if($displayLog && $fileType == 'PmFile')
		sys_log_ast("Info", "Trending&Aggregation", __T('A_TRACELOG_MODULE_LABEL_COLLECT'), "Parsing ongoing: 100 % done", "support_1", "");

	}
	
	/**
	 * 
	 * Sauvegarder une variable
	 * @param $varId
	 * @param $value
	 */
	public function saveVariable($varId,$value){
		$filename=$this->defaultVarDirectory . "{$varId}.ser";
		$ret=file_put_contents($filename, serialize($value)."\nVAR_END\n", FILE_APPEND | LOCK_EX);
		return $ret;
	}
	
	/**
	 * 
	 * Récupération de la valeur de la variable à partir de sa forme sérialisée
	 * @param String $varId
	 */
	public function getVariable($varId){
		
		$filename=$this->defaultVarDirectory . "{$varId}.ser";
		if(!file_exists($filename)) return NULL;
		$sLines = file($filename);
		$value=NULL;
		foreach($sLines as $line) {
			if($line=="VAR_END\n") {
				$var=unserialize($serializedVar);
				if($var!=false) {
					if($value==NULL) {
						$value=$var;
						$serializedVar="";
						continue;
					}
					
					if(!is_array($var)) $var=array($var);
					if(!is_array($value)) $value=array($value);
					$value=array_merge_recursive($value,$var);
				}
				$serializedVar="";
			}
			else $serializedVar.=$line;
		}
		
		return $value;
	}
	
	/**
	 * 
	 * Supprimme toutes les variables sérialisés (= les fichiers associés)
	 */
	public function removeSavedVariables(){
		if(!file_exists($this->defaultVarDirectory)) return;
		if ($handle = opendir($filename=$this->defaultVarDirectory)) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != "..") {
		            unlink($this->defaultVarDirectory . "$entry");
		        }
		    }
		    closedir($handle);
		}
		rmdir($this->defaultVarDirectory);
	}
	
}
?>