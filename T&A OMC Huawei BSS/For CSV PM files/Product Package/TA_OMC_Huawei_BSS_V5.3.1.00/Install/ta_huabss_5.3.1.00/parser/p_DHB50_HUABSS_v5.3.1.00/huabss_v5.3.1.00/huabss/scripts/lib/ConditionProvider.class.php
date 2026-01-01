<?php
/**
 * 
 * Classe représentant les objets chargés de déterminer les conditions qui seront attribuées à 
 * chaque processus. 
 * 
 * Chaque condition est "composite" (elle peut correspondre à plusieurs clauses where sql) : 
 * Cf. classe FileTypeConditionExp.class.php.
 * 
 * Chaque condition donnera lieu à une création de processus, c'est pourquoi indirectement,
 * la classe ConditionProvider détermine le nombre de processus qui seront créés.  
 * 
 * Une condition permet à un processus de savoir quels fichiers sources
 * il doit traiter. Elle doit porter sur les tables sys_flat_file_uploaded_list et 
 * sys_definition_flat_file_lib. Elle est destinée à être utilisée dans la méthode 
 * DatabaseServices.class.php->getFiles. 
 * 
 * Chaque parser (ex : ASN1, XML, CSV) peut disposer de sa propre classe spécifique héritant
 * de cette classe générique.
 *  
 * @package Parser library
 *
 */

class ConditionProvider {
	
	/**
	 * Condition principale associée à un parser donné (par exemple flat_file_name= 'ASN1' pour un fichier le parser ASN1)
	 */
	public $parserCondition;
	

	
	/**
	 * Requêtes SQL
	 */
	public $dbServices;
	
	
	/**
	 * 
	 * Nombre max de processus à créer par coeur de processeur
	 */
	protected $maxNumberOfProcessesPerCore;
	
	/**
	 * 
	 * Nombre min de processus à créer par coeur de processeur
	 */
	protected $minNumberOfProcessesPerCore;
	
	/**
	 * 
	 * Expression régulière permettant de trouver un ID d'élément réseau
	 * au sein d'un nom de fichier source
	 */
	protected $templateForNE;
	
	/**
	 * 
	 * Entier représentant la part des processus associée à ConditionProvider.
	 * Exemple 1 : 
	 * 			1 = 100% si un seul parser.
	 * Exemple 2 inspiré de Ericsson NSS : 
	 * 			0.3 pour le condition provider du parser XML
	 *   		0.7 pour le condition provider du parser ASN1
	 *   
	 *  La somme des poids des différents ConditionProvider doit être égale à 1.
	 */	
	protected $parserPoids;
	
	/**
	 * 
	 * Constructeur
	 * @param DatabaseServices $dbServices
	 */
	public function ConditionProvider (DatabaseServices $dbServices){
		$this->dbServices=$dbServices;
		
		//configuration par defaut
		$this->parserCondition=NULL;// cas NULL : Tous les fichiers sont traités par ce parser
		$this->maxNumberOfProcessesPerCore=4;
		$this->minNumberOfProcessesPerCore=2;
		$this->templateForNE=NULL;
		$this->parserPoids=1;
	}
	
	/**
	 * 
	 * Retourne la condition que doit vérifier les fichiers traité par ce 
	 */
	public function getParserCondition(){
		return $this->parserCondition;
	}
	/**
	 * 
	 * Retourne la liste des conditions qui seront attribuées à chaque processus.
	 */
	public function getConditions(){
		// nombre processeurs considérés comme disponible pour T&A
		// (le cas "plusieurs T&A" sur un même serveur)
		$numberOfProcessor=ProcessManager::getMaxNbOfSimultaneousProcess();
		
		// une condition par heure collectée
		$hourArrayCondition=$this->getHoursConditions($this->parserCondition);
		
		// nb d'heures collectées
		$nbOfHour=count($hourArrayCondition);

		// TODO JGU2 : générer un warning si $this->parserPoids est < 0 ou > 1.
		$minNumberOfProcesses=floor($this->minNumberOfProcessesPerCore*$numberOfProcessor*$this->parserPoids);
		$maxNumberOfProcesses=floor($this->maxNumberOfProcessesPerCore*$numberOfProcessor*$this->parserPoids);
		
		// si pas assez d’heure collectées
		if($nbOfHour<=$minNumberOfProcesses){
			//on décline par heure d'abord
			$outputArrayCondition=$this->generateDeclinedConditions($this->parserCondition, $hourArrayCondition);
			$inputArrayCondition=$outputArrayCondition;
			
			// une condition par type de fichier associé à ce parser
			$flatFileNameArrayCondition=$this->getFlatfileNamesConditions($this->parserCondition);
			
			// nb de ces types fichiers
			$nb_flat_file_names=count($flatFileNameArrayCondition);
			
			// si on a pas assez de  types de fichiers
			if(($nbOfHour*$nb_flat_file_names)<$minNumberOfProcesses){
				//on décline d'abord par flat_file_name
				$outputArrayCondition=array();
				foreach ($inputArrayCondition as $condition) {
					$outputArraytemp=$this->generateDeclinedConditions($condition,$flatFileNameArrayCondition);
					$outputArrayCondition=array_merge($outputArrayCondition,$outputArraytemp);
				}
				$inputArrayCondition=$outputArrayCondition;
				
				//si pas de découpage par NE possible
				if(!isset($this->templateForNE)) {
					displayIndemon(count($outputArrayCondition)." conditions ont été créées suite à une déclinaison par heures ($nbOfHour) et par flat_file_names ($nb_flat_file_names) - pas de découpage par NE possible -.");
					return $outputArrayCondition;
				}
				//quels NE
				$neList=$this->dbServices->findNetElemCollected($this->templateForNE,$this->parserCondition);
				$numberOfNE=count($neList);
				$NEConditions=array();


				// si le nb d’éléments nous convient
				if(($nbOfHour*$nb_flat_file_names*$numberOfNE)<$maxNumberOfProcesses){
					
					foreach ($neList as $neId) {
						$specifPattern=str_replace("(.+)", $neId, $this->templateForNE);
						$NEConditions[]=new FileTypeCondition("uploaded_flat_file_name","~*",$specifPattern);
					}
					
					$outputArrayCondition=array();
					foreach ($inputArrayCondition as $condition) {
						$outputArraytemp=$this->generateDeclinedConditions($condition,$NEConditions);
						$outputArrayCondition=array_merge($outputArrayCondition,$outputArraytemp);
					}
					displayIndemon(count($outputArrayCondition)." conditions ont été créées suite à une déclinaison par heures ($nbOfHour), par flat_file_names ($nb_flat_file_names) et par eléments réseaux ($numberOfNE).");
					return $outputArrayCondition;

				}
				// si on a trop d’éléments réseaux
				else{
					//on créer des groupes de NE pour revenir au nombre max de process autorisé
					$nbOfNEGroup=floor($maxNumberOfProcesses/($nbOfHour*$nb_flat_file_names));			
					$numberOfNEPerGroup=floor($numberOfNE/$nbOfNEGroup);
					$numberOfNELeft=$numberOfNE%$nbOfNEGroup;
					
					$NEConditions=array();
					$counter=0;
					$NEConditionPerGroup=new FileTypeConditionExp();
					foreach ($neList as $neId) {
						$specifPattern=str_replace("(.+)", $neId, $this->templateForNE);
						$cCondition=new FileTypeCondition("uploaded_flat_file_name","~*",$specifPattern);
						$cCondition->operatorInterCondition="OR";
						$counter++;
						if($counter<=($numberOfNEPerGroup*$nbOfNEGroup)){
							if(($counter%$numberOfNEPerGroup)==0){
								$NEConditionPerGroup->add($cCondition);
								$NEConditions[]=$NEConditionPerGroup;
								$NEConditionPerGroup=new FileTypeConditionExp();
							}else{
								$NEConditionPerGroup->add($cCondition);
							}
						}else{
							$cConditionexp=$NEConditions[$counter-($numberOfNEPerGroup*$nbOfNEGroup)-1];
							$cConditionexp->add($cCondition);
						}

					}
					
					$outputArrayCondition=array();
					foreach ($inputArrayCondition as $condition) {
						$outputArraytemp=$this->generateDeclinedConditions($condition,$NEConditions);
						$outputArrayCondition=array_merge($outputArrayCondition,$outputArraytemp);
					}
					displayIndemon(count($outputArrayCondition)." conditions ont été créées suite à une déclinaison par heures ($nbOfHour), par flat_file_names ($nb_flat_file_names) et par groupe d'éléments réseaux ($nbOfNEGroup).");
					return $outputArrayCondition;
				}
			}
			// si le nb de types nous convient : on crée un process par couple « heure/type »
			elseif(($nbOfHour*$nb_flat_file_names)<=$maxNumberOfProcesses){
				//décliné par heure puis par flat_file_name
				
				$outputArrayCondition=array();
				foreach ($inputArrayCondition as $condition) {
					$outputArraytemp=$this->generateDeclinedConditions($condition,$flatFileNameArrayCondition);
					$outputArrayCondition=array_merge($outputArrayCondition,$outputArraytemp);
				}
				displayIndemon(count($outputArrayCondition)." conditions ont été créées suite à une déclinaison par heures ($nbOfHour) et par flat_file_names ($nb_flat_file_names).");
				return $outputArrayCondition;
				
			}
			// si on a trop de types
			else{
				// on va créer autant de groupes de types que le nb de process max autorisé divisé par le nombre d'heure
				//car le nombre d'heure multiplie le nombre de process :
				//ex : 4heures, 7 groupes de flat_file_name => 28 process seront créés
				$nbOfFlatFileNamesGroup = floor($maxNumberOfProcesses/$nbOfHour);
				
				// nb minimum de types par groupe : en effet, certains groupes auront un type en plus, 
				// à moins que le nombre de types soit un multiple du nb de groupes de types.
				// floor : arrondit à l'entier inférieur.
				// exemple : si on a 11 types et un nb de process max égal à 3 
				//        	 alors $minNumberOfFlatFileNamesPerGroup = 3
				//			 ce qui donnera la répartition suivante :
				//					- process 1 : 4 types
				//					- process 2 : 4 types
				//					- process 3 : 3 types
				$minNumberOfFlatFileNamesPerGroup=floor($nb_flat_file_names/$nbOfFlatFileNamesGroup);

				// pour mémoriser les conditions correspondant à des groupes de types
				$flatFileGroupsConditions=array();
				
				// pour compter les types de fichiers à traiter
				$typesCounter=0;
				
				// 1er groupe de types à créer (ex : T5 OR T6 OR T7)
				$flatFileConditionsForCurrentGroup=new FileTypeConditionExp();
				
				// on parcourt les types 
				foreach ($flatFileNameArrayCondition as $cCondition) {
					
					// un "OU logique" placé entre les types situés dans un même groupe de types 
					$cCondition->operatorInterCondition="OR";
					
					// nouveau tour de la boucle sur les types
					$typesCounter++;
					
					// si on peut continuer à dispatcher équitablement les types dans les groupes de types
					if($typesCounter<=($minNumberOfFlatFileNamesPerGroup*$nbOfFlatFileNamesGroup)){						
						// "%" = modulo = reste de la division
						// si on le groupe de types courant contient désormais le nb de types attendu pour chaque groupe
						if(($typesCounter%$minNumberOfFlatFileNamesPerGroup)==0){
							// on ajoute le type courant au groupe de types courant, qui est désormais complet
							$flatFileConditionsForCurrentGroup->add($cCondition);
							// on mémorise le groupe de types ainsi complété
							$flatFileGroupsConditions[]=$flatFileConditionsForCurrentGroup;
							// prochain groupe de type à créer
							$flatFileConditionsForCurrentGroup=new FileTypeConditionExp();
						}
						// sinon, on ajoute le type courant au groupe de types courant
						else{
							$flatFileConditionsForCurrentGroup->add($cCondition);
						}
					}
					// sinon, le type courant est l'un des quelques types restant à dispatcher
					else{
						// on sélectionne un des groupes de types déjà mémorisés
						$cConditionexp=$flatFileGroupsConditions[$typesCounter-($minNumberOfFlatFileNamesPerGroup*$nbOfFlatFileNamesGroup)-1];
						// on ajoute le type courant à ce groupe
						$cConditionexp->add($cCondition);
					}
				}
				
				// résultat attendu : déclinaisons par parser, hour et groupe de types 
				$outputArrayCondition=array();
				
				// $inputArrayCondition représente les déclinaisons par parser et hour
				// => on parcourt ces couples "parser/hour" :
				foreach ($inputArrayCondition as $condition) {
					// le couple "parser/hour" courant est décliné autant de fois qu'il y a de groupes de types
					$outputArraytemp=$this->generateDeclinedConditions($condition,$flatFileGroupsConditions);
					// on merge les résultats des différents couples "parser/hour"
					$outputArrayCondition=array_merge($outputArrayCondition,$outputArraytemp);
				}
				displayIndemon(count($outputArrayCondition)." conditions ont été créées suite à une déclinaison par heures ($nbOfHour) et par groupe de flat_file_names ($nbOfFlatFileNamesGroup).");
				return $outputArrayCondition;
			}
			
		// s’il suffit de créer un process par heure collectée	
		}elseif ($nbOfHour<=$maxNumberOfProcesses){
			//on décline par heure
			$outputArrayCondition=$this->generateDeclinedConditions($this->parserCondition, $hourArrayCondition);
			
			displayIndemon(count($outputArrayCondition)." conditions ont été créées suite à une déclinaison par heures ($nbOfHour)");
			return $outputArrayCondition;
		}
		// si on a trop d’heures donc chaque process va traiter un groupe d’heures
		else{
			// nb de groupes d'heures collectées (= nb de processus à créer)
			$nbOfHoursGroup=$maxNumberOfProcesses;
			
			// nombre minimum d'heures par groupe d'heures
			$numberOfHoursPerGroup=floor($nbOfHour/$nbOfHoursGroup);	
			
			$NEConditions=array();
			$counter=0;
			$NEConditionPerGroup=new FileTypeConditionExp();
			
			// pour chaque heure collectée
			foreach ($hourArrayCondition as $cCondition) {
				$cCondition->operatorInterCondition="OR";
				$counter++;
				if($counter<=($numberOfHoursPerGroup*$nbOfHoursGroup)){
					if(($counter%$numberOfHoursPerGroup)==0){
						$NEConditionPerGroup->add($cCondition);
						$NEConditions[]=$NEConditionPerGroup;
						$NEConditionPerGroup=new FileTypeConditionExp();
					}else{
						$NEConditionPerGroup->add($cCondition);
					}
				}else{
					$cConditionexp=$NEConditions[$counter-($numberOfHoursPerGroup*$nbOfHoursGroup)-1];
					$cConditionexp->add($cCondition);
				}
			}
			
			$outputArrayCondition=$this->generateDeclinedConditions($this->parserCondition,$NEConditions);
			
			displayIndemon(count($outputArrayCondition)." conditions ont été créées suite à une déclinaison par groupe d'heures ($nbOfHoursGroup)");
			return $outputArrayCondition;
		}
		
		//END
	}
	
	
	
	
	
	
	/**
	 * 
	 * Retourne les conditions portant sur les types de fichiers et satisfaisant la 
	 * condition fournie en paramètre.
	 */
	protected function getFlatfileNamesConditions(FileTypeCondition $condition=NULL){
		$flat_file_names=$this->dbServices->getFlatfilenamesForCollectedFiles($condition);
		$conditionArray=array();
		foreach ($flat_file_names as $flat_file_name) {

			$flatFileNameCondition=new FileTypeCondition("flat_file_name", '=', $flat_file_name);
			$conditionArray[]=$flatFileNameCondition;
		}
		return $conditionArray;
	}
	
	/**
	 * 
	 * Renvoie un tableau de conditions (une condition pour chaque heure de fichier collecté 
	 * vérifiant $condition)
	 * @param $condition
	 */
	protected function getHoursConditions(FileTypeCondition $condition=NULL){
		$hours=$this->dbServices->getHoursCollected($condition);
		$conditionArray=array();
		foreach (array_keys($hours) as $hour) {
			$hourCondition=new FileTypeCondition("hour", '=', $hour);
			$conditionArray[]=$hourCondition;
		}
		return $conditionArray;
		
	}
	
	/**
	 * 
	 * Ajoute $condition à chacune des conditions de $inputConditionsArray.
	 * @param unknown_type $condition
	 * @param unknown_type $inputConditionsArray
	 */
	protected function generateDeclinedConditions(FileTypeCondition $condition=NULL,$inputConditionsArray){
		//si condition=NULL
		if(!isset($condition)) return $inputConditionsArray;
		
		$conditionArray=array();
		foreach ($inputConditionsArray as $currentCondition) {
			$newCondition=new FileTypeConditionExp();
			$condition->operatorInterCondition="AND";
			$newCondition->add($condition);
			$newCondition->add($currentCondition);
			$conditionArray[]=$newCondition;
		}
		//si
		if(count($conditionArray)==0) return array($condition);
		return $conditionArray;
		
	}

	
}
?>