<?php

	/**
	 * 
	 * Représente un produit T&A dans l'API
	 *
	 */
	class TAProduct {		
		
		/**
		 * Constructeur
		 * 
		 * @param $iId
		 * @param $sLabel
		 * @param $sIp
		 * @param $sDirectory
		 * @param $bTopoMaster
		 * @param $sSshUser
		 * @param $sSshPass
		 * @param $sSshPort
		 * @param $iProductType
		 */
		function __construct($iId, $sLabel, $sIp, $sDirectory, $bTopoMaster, $sSshUser, $sSshPass, $sSshPort, $iProductType){
			$this->m_iId = $iId;
			$this->m_sLabel = $sLabel;
			$this->m_sIp = $sIp;
			$this->m_sDirectory = $sDirectory;
			$this->m_bMasterTopo = $bTopoMaster;
			$this->m_sSshUser = $sSshUser;
			$this->m_sSshPass = $sSshPass;
			$this->m_sSshPort = $sSshPort;
			$this->m_iProductType = $iProductType;
		}
		
		public function getId() { return $this->m_iId; }
		
		/** id interne dup produit */ 
		private $m_iId;
		
		/** Label désignant le produit affiché dans l'IHM */ 
		private $m_sLabel;
		
		/** IP de la machine sur laquelle le produit est installé */
		private $m_sIp;
		
		/** Répertoire d'installation du produit */
		private $m_sDirectory;
		
		/** à true si le produit est configuré comme maître de la topologie*/
		private $m_bMasterTopo;
		
		/** login d'accès à ssh pour lire les exports de données */
		private $m_sSshUser;
		
		/** password d'accès à ssh pour lire les exports de données */
		private $m_sSshPass;
		
		/** port d'accès à ssh pour lire les exports de données */
		private $m_sSshPort;
		
		/** Id identifiant à quel produit on a affaire */
		private $m_iProductType;
		
		
		
	}

?>