<?php
	/**
	 * 
	 * Classe représentant un DataExport T&A dans l'API
	 *
	 * Elle est appellée ainsi pour qu'il n'y a pas de conflit 
	 * avec l'autre classe DataExport
	 * 
	 *
	 */
	class APIDataExport {
		
		/**
		 * Constructeur de la classe APIDataExport (classe utiliser pour représenté 
		 * un dataexport de l'API Xpert)
		 * 
		 * @param string $sExportId id de l'export
		 * @param string $sExportName nom associé de l'export
		 * @param string $sExportDir répertoire à le fichier sera généré
		 * @param string $sFileName nom du fichier correspondant à l'export
		 * @param string $sFieldSeparator séparateur utilisé dans l'export 
		 * @param string $sTimeAggregation aggregation temporel de l'export
		 * @param string $sFamily famille concerné par l'export
		 * @param boolean $bGenerateHourOnDay true : génére toute les données horaires 
		 * de la journée dans un seul fichier
		 * @param boolean $bShowNetworkHierarchy true : affiche toute la hiérarchie du réseau, 
		 * false : affiche que l'id T&A de l'élément concerné
		 * @param boolean $bUseCodeKPInRAW true : utilise les id T&A pour désigner les compteurs et les kpis
		 * false : utilise les noms
		 * @param boolean $bUseCodeNAAndNE true : utilise les id T&A pour désigner les niveaux d'agrégations et
		 * les élements réseau
		 * @param boolean $bAddTopoFile true : ajoute la topo à coté du fichier de donnée
		 * @param integer $iType : 0  IHM, 1-2 corporate / mix kpi, 3 API Xpert 
		 * @param array $indicators : tableau contenant la liste des kpis et compteurs associés
		 */
		function __construct($sExportId, $sExportName, $sExportDir, $sFileName, 
							$sFieldSeparator, $sNetworkAggregation, $sTimeAggregation, 
							$sFamily, $bGenerateHourOnDay, $bShowNetworkHierarchy, 
							$bUseCodeKPInRAW,$bUseCodeNAAndNE, $bAddTopoFile,
							$bAddRawKpiFile,$iType,$indicators){
								$this->m_sExportId = $sExportId;
								$this->m_sExportName = $sExportName;
								$this->m_sExportDir = $sExportDir;
								$this->m_sFileName = $sFileName;
								$this->m_sFieldSeparator = $sFieldSeparator;
								$this->m_sTimeAggregation = $sTimeAggregation;
								$this->m_sNetworkAggregation = $sNetworkAggregation;
								$this->m_sFamily = $sFamily;
								$this->m_bGenerateHourOnDay = $bGenerateHourOnDay;
								$this->m_bShowNetworkHierarchy = $bShowNetworkHierarchy;
								$this->m_bUseCodeKPInRAW = $bUseCodeKPInRAW;
								$this->m_bUseCodeNAAndNE = $bUseCodeNAAndNE;
								$this->m_bAddTopoFile = $bAddTopoFile;
								$this->m_bAddRawKpiFile = $bAddRawKpiFile;
								$this->m_iType = $iType;
								$this->m_indicators = $indicators;
							}
		
		function getExportId(){
			return $this->m_sExportId;
		}

		
		function getExportName(){
			return $this->m_sExportName;
		}
		
		function getExportDir(){
			return $this->m_sExportDir;
		}
		
		function getFileName(){
			return $this->m_sFileName;
		}
		
		function getFieldSeparator(){
			return $this->m_sFieldSeparator;
		}
		
		function getTimeAggregation(){
			return $this->m_sTimeAggregation;
		}
		
		function getNetworkAggregation(){
			return $this->m_sNetworkAggregation;
		}
		
		function getFamily(){
			return $this->m_sFamily;
		}
		
		function isGenerateHourOnDay(){
			return $this->m_bGenerateHourOnDay;
		}
		
		function isShowNetworkHierarchy(){
			return $this->m_bShowNetworkHierarchy;
		}
		
		function isUseCodeKPInRAW(){
			return $this->m_bUseCodeKPInRAW;
		}
		
		function isUseCodeNAAndNE(){
			return $this->m_bUseCodeNAAndNE;
		}
		
		function isAddTopoFile(){
			return $this->m_bAddTopoFile;
		}
		
		function getType(){
			return $this->m_iType;	
		}
		
		function getIndicators(){
			return $this->m_indicators;
		}
		
		/**
		 *  Id de l'export
		 */
		public $m_sExportId;
		
		/**
		 * Nom de l'export
		 */
		public $m_sExportName;
		
		/**
		 *  Répertoire ou sera généré l'export
		 */
		public $m_sExportDir;
		
		/**
		 *  Nom de l'export
		 */
		public $m_sFileName;
		
		/**
		 *  Séparateur de champ utilisé
		 */
		public $m_sFieldSeparator;
		
		/**
		 *  Niveau d'agrégation demandé
		 */
		public $m_sTimeAggregation;

		/**
		 * Niveau d'agrégation reseau correspondant à l'export
		 */
		public $m_sNetworkAggregation;
		
		/**
		 * Famille concernée par l'export
		 */
		public $m_sFamily;
		
		/**
		 * Dans le cas d'un dataexport de donnée horaire
		 * Ce booléen spécifie de généré toute les données horaires 
		 * de la journée en un seul fichier.
		 */
		public $m_bGenerateHourOnDay;
		
		/**
		 * Affiche toutes la hiérarchie réseau dans l'export
		 * (select_parent en db)
		 */
		public $m_bShowNetworkHierarchy;
		
		/**
		 * Utilise les codes des kpis et des compteurs pour l'export
		 */
		public $m_bUseCodeKPInRAW;
		
		/**
		 * Utilise les codes des Network Element et Network Aggregation 
		 * pour l'export.
		 */
		public $m_bUseCodeNAAndNE;
		
		/**
		 * Ajoute le fichier de topology à coté de l'export
		 */
		public $m_bAddTopoFile;
		
		/**
		 * Ajoute les fichiers de liste de kpi et de compteurs
		 */
		public $m_bAddRawKpiFile;
		
		/**
		 * Type de l'export
		 */
		public $m_iType;
		
		/**
		 * Tableau regroupant la liste des kpi et compteur 
		 * exportés.
		 */
		public $m_indicators;
		
		
	}