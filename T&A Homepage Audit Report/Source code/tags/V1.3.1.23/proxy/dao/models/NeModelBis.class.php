<?php
/*
 JGU2 : modele permettant de cibler plusieurs types d'elements reseaux a la fois.
 */

class NeModelBis
{

	/** Get NE list with various parameters (NE without label are in the end of the list)
	 * @param $na string aggregation level
	 * @param $products array product list, if null -> master will be used
	 * @param $limit integer the number of max result, if null -> no no no limit !
	 * @param $labelFilter string filter on NE label
	 *
	 * TODO : ajout de famille pour filtrer les elements reseaux disposant de stats pour ces familles ?
	 *
	 */
	public static function getFilteredNe($productsAndNaTab=null, $limit=null, $labelFilter=null) {
		$result = array();
			
		// Compute query parameters
		if(!isset($productsAndNaTab) || !is_array($productsAndNaTab)) {
			var_dump('on rentre dans la creation darret vide');
			$productsAndNaTab = array();
		}
			
		// Compute limit
		$limitClause = " ";
		if(! empty($limit)) {
			$limitClause .= "LIMIT $limit";      		// SQL limit
		}

		// Compute label filter
		$labelFilterClause = " ";
		if(! empty($labelFilter)) {
			$labelFilterClause .= "AND (eor_label ILIKE '%".addslashes($labelFilter)."%' OR eor_id ILIKE '%".addslashes($labelFilter)."%') ";
		}
	
		// For each product
		foreach($productsAndNaTab as $product) {
			$p = $product->id;
			// Compute NA filter
			$naFilter = "";

			$naTab = $product->na;
			if(!empty($naTab)){
				if(!is_array($naTab)) {
					$naFilter = " AND eor_obj_type = '$naTab' ";
				}
				else if(count($naTab) == 1) {
					$naFilter = " AND eor_obj_type = '$naTab[0]' ";
				}
				else {
					$naFilter = " AND eor_obj_type in (";
					foreach($naTab as $na){
						$naFilter .= "'$na', ";
					}
					// delete last ","
					$naFilter = substr($naFilter, 0, strlen($naFilter)-2);
					$naFilter .= ") ";
				}
			}

			// Database connection
			$p = is_array($p)?$p['sdp_id']:$p;
			$db = Database::getConnection($p);

			// Query for $p product
			$query = "SELECT DISTINCT eor_id,
                			'$p' as product_id,							
							CASE WHEN eor_label IS NOT NULL THEN eor_label||' ('||eor_id||')' ELSE '('||eor_id||')' END AS eor_label,
							agregation,
							agregation_label,
							sdp_label					
						FROM edw_object_ref, sys_definition_network_agregation, sys_definition_product	
						WHERE eor_obj_type = agregation
							AND sdp_id=$p
							AND eor_id IS NOT NULL							
							$naFilter
							AND	eor_on_off=1
							AND eor_id not like 'virtual_%'	
							$labelFilterClause
						ORDER BY eor_label
						$limitClause";
						// Execute query
						$result = array_merge($result, $db->getAll($query));
		}
		//var_dump($query);
		return $result;
	}
	
	/**
	 * récupère les labels de la liste d'éléments réseaux passée
	 * @param string $nelist liste d'ids d'éléments réseaux
	 * @param string $na niveau réseau
	 * @param string $idProduct id du produit
	 */
	public static function getNeLabels($nelist, $na='', $idProduct = '', $order = null) {
		$result = array();
		
		$nelist_exploded=explode(',',$nelist);
		
		// Database connection
		$p = is_array($idProduct)?$idProduct['sdp_id']:$idProduct;
		$db = Database::getConnection($idProduct);
		
		$sql = "SELECT eor_id as code,
							CASE WHEN edw_object_ref.eor_label IS NOT NULL THEN edw_object_ref.eor_label ELSE edw_object_ref.eor_id END AS label
						FROM					
							edw_object_ref
						WHERE
							edw_object_ref.eor_id IN (".implode(",", array_map(array('self', 'labelizeValue'), $nelist_exploded)).")";
		
		if(!empty($na)) {
			$sql .= " AND eor_obj_type = '$na'";
		}
		if($order != null) {
			$sql .= " ORDER BY label $order";
		}
		$result = array_merge($result, $db->getAll($sql));
		
		return $result;
		
	} 
	
	/**
	* Permet de "labeliser" une valeur cad de l'entourer de quotes
	*
	* @author GHX
	* @version CB4.1.0.00
	* @since CB4.1.0.00
	* @param string $value la valeur à labeliser
	* @return string la valeur labelisée
	*/
	private static function labelizeValue ( $value )
	{
		return "'".$value."'";
	} // End function labelizeValue
	
	public static function getFilteredNl($productsAndNaTab=null, $limit=null, $labelFilter=null) {
		$result = array();
		// Compute query parameters
		if(!isset($productsAndNaTab) || !is_array($productsAndNaTab)) {
			$productsAndNaTab = array();
		}
		foreach($productsAndNaTab as $product) {
			$p = $product->id;
			$family = strtolower($product->family);
			$axis = $product->axe;
			// Database connection
			$p = is_array($p)?$p['sdp_id']:$p;
			$db = Database::getConnection($p);
			
			// Compute limit
			$limitClause = " ";
			if(! empty($limit)) {
				$limitClause .= "LIMIT $limit";      		// SQL limit
			}
			
			if($axis == 3){
				$whereClause = "WHERE axe=".$axis." AND family = '".$family."'";
			}else{
				$whereClause = "WHERE axe is null AND family = '".$family."'";
			}
			
			// Query for $p product
				$query = "SELECT DISTINCT
				agregation_name,
				agregation_label					
				FROM sys_definition_network_agregation	
				$whereClause
				ORDER BY agregation_name
				$limitClause";
				//var_dump($query);
				// Execute query
				$result = array_merge($result, $db->getAll($query));
		}
		return $result;
	}
}
?>