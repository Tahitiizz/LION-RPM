<?php
	
	class NetworkElement {
		
		public function __construct($sCode, $sLabel, $sNetworkAggregation, $fAzimuth = 0.0, $fLatitude= 0.0, $m_fLongitude= 0.0){
			$this->m_sCode = $sCode;
			$this->m_sLabel = $sLabel;
			$this->m_sNetworkAggregation = $sNetworkAggregation;
			$this->m_fAzimuth = $fAzimuth;
			$this->m_fLatitude = $fLatitude;
			$this->m_fLongitude = $m_fLongitude;
		}
		
		public function getCode(){
			return $this->m_sCode;
		}			
		
		//Code interne à T&A de l'élément
		private $m_sCode;

		//Label affiché dans T&A  
		private $m_sLabel;
		
		//Niveau d'agregration de l'élement réseau
		private $m_sNetworkAggregation;
		
		//Azimuth : orientation
		private $m_fAzimuth;
		
		//Latitude
		private $m_fLatitude;
		
		//Longitude
		private $m_fLongitude;
	}
?>