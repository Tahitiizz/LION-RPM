<?php

	/**
	 * Cette classe représente un kpi ou un compteur de T&A
	 * 
	 */
	class Indicator {		
		
		/*
		 * Choix de type
		 */
		const eRaw = "raw";
		const eKpi = "kpi";
		
		public function __construct($sId, $sCode, $sLabel, $eType, $sAggregationFormula = "", $sComment = ""){
			
			$this->m_sId = $sId;
			
			$this->m_sCode = $sCode;
			
			$this->m_sLabel = $sLabel;
			
			$this->m_eType = $eType;
			
			$this->m_sAggregationFormula = $sAggregationFormula;
			
			$this->m_sComment = $sComment;
			
			 
		}
		
		/*
		 * Getters
		 * 
		 */
		public function getId() { return $this->m_sId; }
		
		public function getCode() { return $this->m_sCode; }
		
		public function getLabel() { return $this->m_sLabel; }
		
		public function getType() { return $this->m_eType; }
				
		public function getAggregationFormula() { return $this->m_sAggregationFormula; }
		
		public function getComment() { return $this->m_sComment; }
		
		/**
		 * Identifiant de la base de donnée
		 * de l'indicateur.
		 */
		private $m_sId;
		
		/**
		 * Code du kpi où du compteur.
		 * Il s'agit de l'indication affiché dans 
		 * le cas ou la "use code counter..." est sélectionné 
		 * dans le dataexport.
		 * 
		 */
		private $m_sCode;
		
		/**
		 * Nom d'affichage du KPI ou du compteur
		 */
		private $m_sLabel;
		
		/**
		 * "kpi" ou "raw"
		 */
		private $m_eType;
		
		/**
		 *  Formule du kpi ou du compteur
		 */
		private $m_sAggregationFormula;  
		
		/**
		 * Commentaire du kpi ou du compteur
		 */
		private $m_sComment;
		
	} 