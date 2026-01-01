<?php

class JsonProvider {

	/**
	 *
	 * Retourne au format JSON le label correspondant a l'ID d'element reseau donne.
	 * Si aucun label n'est renseigne, l'ID est retourne.
	 * @param array $parameters
	 */
	public function getNeLabel($parameters = array()) {
		try {
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
			// Error management
		} catch (Exception $e) {
			$jsonResult = $this->getJSONErrorMessage($e);
		}
		return $jsonResult;
	}
	


	public function getData($parameters) {
		try {
			// Query data model
			$queryDataModel = new QueryDataModel();
			// $jsonResult = $queryDataModel->getJsonResponse();
			$jsonResult = $queryDataModel->getData($parameters);
			// Error management
		} catch (Exception $e) {
			$jsonResult = $this->getJSONErrorMessage($e);
		}
		return $jsonResult;
	}



	private function getJSONErrorFromException($e) {
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

}

?>