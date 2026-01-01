<?php
/**
 * 
 * Classe représentant les conditions d’éligibilité des fichiers temporaires présents avant le step execcopyQuery : en fonction des conditions qu'on lui attribue, un processus 
 * execCopyQuery enfant va savoir s'il doit traiter tel ou tel fichier temporaire. 
 *
 */
class TempFilesCondition {
	
	/**
	 * Heuree intégrées, ex : 2012122500, 2012122501
	 */
	private $hours;
	
	/**
	 * Entité considérée, au format "family_nmstable" (ex sur Ericsson BSS : bss_NICELHOEX)
	 */
	private $entity;
	

	/**
	 * Constructor
	 * @param string $entity entité considérée
	 * @param array $hours tableau des heures considérées
	 */
	public function __construct($entity,$hours) {
		$this->entity=$entity;
		$this->hours=$hours;
	}
	
	/**
	 * Méthode destinée à restreindre les heures à traiter 
	 */
	public function getFileHoursCondition() {
		return $this->hours;
	}
	
	
	/**
	 * Méthode destinée à restreindre les entités à traiter au sein de l'objet ParametersList.
	 */
	public function getFileEntityCondition() {
		return $this->entity;
	}
		
}
?>