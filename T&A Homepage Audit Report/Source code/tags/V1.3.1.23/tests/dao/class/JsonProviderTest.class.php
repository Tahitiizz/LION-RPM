<?php

class JsonProviderTest {

	/**
	 *
	 * Retourne au format JSON le label correspondant a l'ID d'element reseau donne.
	 * Si aucun label n'est renseigne, l'ID est retourne.
	 * @param array $parameters
	 */
	public function getNeLabel($parameters = array()) {
		$productId = $parameters["product_id"];
		$networkLevelCode = $parameters["ne_network_level"];
		$networkId = $parameters["ne_id"];
		if(!isset($productId) || !isset($networkLevelCode) || !isset($networkId)) {
			$jsonResult = $this->getJSONErrorFromMessage("Given parameters are not valid for ".__FUNCTION__.".");
		}
		else {
			$neLabel = NeModel::getLabel($networkId, $networkLevelCode, $productId);
			// si aucun label pour cet ID dans la topologie du produit
			if($neLabel == FALSE) {
				$neLabel = $networkId;
			}
			$jsonResult = "{\"label\":\"$neLabel\"}";
		}
		return $jsonResult;
	}


	public function getKpiLabel($parameters = array()) {
		$productId = $parameters["product_id"];
		$kpiId = $parameters["kpi_id"];
		if(!isset($productId) || !isset($kpiId)) {
			$jsonResult = $this->getJSONErrorFromMessage("Given parameters are not valid for ".__FUNCTION__.".");
		}
		else {
			$kpiModel = new KpiModel();
			$kpiLabel = $kpiModel->getLabelFromId($kpiId, Database::getConnection($productId));
			// si kpi non trouve
			if(!isset($kpiLabel) || $kpiLabel==FALSE) {
				$jsonResult = $this->getJSONErrorFromMessage("No label found for KPI ID '$kpiId' in product '$productId'.");
			}
			else {
				$jsonResult = "{\"label\":\"$kpiLabel\"}";
			}
		}
		return $jsonResult;
	}

	public function getRawLabel($parameters = array()) {
		$productId = $parameters["product_id"];
		$rawId = $parameters["raw_id"];
		if(!isset($productId) || !isset($rawId)) {
			$jsonResult = $this->getJSONErrorFromMessage("Given parameters are not valid for ".__FUNCTION__.".");
		}
		else {
			$rawModel = new RawModel();
			$rawLabel = $rawModel->getLabelFromId($rawId, Database::getConnection($productId));
			// si compteur non trouve
			if(!isset($rawLabel) || $rawLabel==FALSE) {
				$jsonResult = $this->getJSONErrorFromMessage("No label found for raw counter ID '$kpiId' in product '$productId'.");
			}
			else {
				$jsonResult = "{\"label\":\"$rawLabel\"}";
			}
				
		}
		return $jsonResult;
	}

	public function getGaugeData($parameters = array()) {

		// recuperation des parametres
		if(isset($parameters["kpi_id"])) {
			$rawKpiId = $parameters["kpi_id"];
			$dataType = "KPI";
		}
		else if(isset($parameters["raw_id"])) {
			$rawKpiId = $parameters["raw_id"];
			$dataType = "RAW";
		}
		$productId = $parameters["product_id"];
		$timeLevelCode = $parameters["time_level"];
		$networkLevelCode = $parameters["ne_network_level"];
		$networkId = $parameters["ne_id"];
			
		if(!isset($dataType) || !isset($productId) || !isset($timeLevelCode) || !isset($networkLevelCode) || !isset($networkId)) {
			$jsonResult = $this->getJSONErrorFromMessage("Given parameters are not valid for ".__FUNCTION__.".");
		}
		else {
			// parametres de la query au format JSON
			$jsonQueryParameters =
<<<EOD
{
    "method":"getData",
        "parameters":{
				"select":{
					"data":[
					{
                    "id":"$timeLevelCode",
                    "type":"ta",
                    "order":"Ascending"
                	},
	                {
	                    "id":"$rawKpiId",
	                    "type":"$dataType",
	                    "productId":"$productId"
	                }
		            ]
			        },
			        "filters":{
			            "data":[
	                		{
			                    "id":"$networkLevelCode",
			                    "type":"na",
			                    "value":"$networkId"
	                		}
			            ]
			        }
				}
		}
EOD;
			$jsonResult = $this->getData($jsonQueryParameters);
		}
		return $jsonResult;
	}


	private function getData($jsonQueryParameters){
		try {
			// suppression des \t, \n, \r
			// TODO : a optimiser
			$jsonQueryParameters = str_replace("\t", "", $jsonQueryParameters);
			$jsonQueryParameters = str_replace("\n", "", $jsonQueryParameters);
			$jsonQueryParameters = str_replace("\r", "", $jsonQueryParameters);

			// suppression des "\" devant les " pour que le JSON soit considere comme valide
			$jsonQueryParameters = stripslashes($jsonQueryParameters);

			// conversion de la chaine de caractères JSON en objet PHP
			$queryObj = json_decode($jsonQueryParameters);

			if($queryObj == null) {
				$jsonResult = $this->getJSONErrorFromMessage("No result found. Please refine your query.");
			}
			else{
				// creation de l'objet Query data model
				$queryDataModel = new QueryDataModel();

				// fonction "getData" de l'objet Query data model
				$function = array($queryDataModel, "getData");

				// appel a cette fonction
				$jsonResult = call_user_func($function, $queryObj);

				// si erreur
				if(! isset($jsonResult) || $jsonResult==FALSE) {
					$jsonResult = $this->getJSONErrorFromMessage("No result found. Please refine your query.");
				}
			}
		} catch (Exception $e) {
			$jsonResult = $this->getJSONErrorFromException($e);
		}
		return $jsonResult;
	}


	public function getJSONErrorFromException($e) {
		// set default type to ERROR_SYSTEM
		$type = "unknown";
		$message = addslashes($e->getMessage());
		$number = isset($e->number)?$e->number:-1;
		return "{error: {type: '$type', number: '$number', message: '$message'}}";
	}

	public function getJSONErrorFromMessage($message) {
		$jsonResult = "{error: {type: 'unknown', number: '-1', message: '$message'}}";
		return $jsonResult;
	}


	public function getProductsFamilies() {
		$facade = new QbFacade();
		// lancement d'un echo (Cf. stdout) sur tous les couples produit / famille
		$facade->getProductsFamilies();
	}

}

?>