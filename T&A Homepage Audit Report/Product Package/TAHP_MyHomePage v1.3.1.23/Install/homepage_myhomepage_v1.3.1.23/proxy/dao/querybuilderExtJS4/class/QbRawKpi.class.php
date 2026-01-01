<?php
/**
 * Class to get RAW and KPI from all products
 */
class QbRawKpi {

	const RAW = 1;
	const KPI = 2;

	// Query for KPI list
	const KPI_QUERY = '--- get list of all KPIs
			select distinct on (object_libelle,kpi_name, sdk.id_ligne) sdk.id_ligne as id,
				value_type,
				sdk.id_ligne as object_id,
				sdk.id_ligne as object_id_elem_in,				
				kpi_name as object_name,
				case when kpi_label is not null then replace(kpi_label, \':\',\' \') else replace(kpi_name,\':\',\' \') end as object_libelle,
				count(sdrs.id_element) as nb_ranges,
				\'%d\' as sdp_id,
				\'%s\' as sdp_label,
				sdgt.family,
				sdc.family_label
			from sys_definition_kpi as sdk
				left outer join sys_data_range_style as sdrs on sdk.id_ligne=sdrs.id_element and sdrs.data_type=\'kpi\'  
				left outer join sys_definition_group_table as  sdgt on sdgt.edw_group_table = sdk.edw_group_table
				left outer join sys_definition_categorie as  sdc on sdc.family = sdgt.family
			where sdk.on_off=1
				and sdk.visible=1	
				%s			
			group by sdk.id_ligne,sdk.kpi_label,sdk.kpi_name,sdk.value_type,sdgt.family,sdc.family_label
			order by object_libelle, kpi_name asc, sdk.id_ligne
			';

	// Query for RAW list
	const RAW_QUERY = ' --- get list of all RAW counters
			select distinct on (object_libelle,edw_field_name) sfr.id_ligne as id,				
				sfr.id_ligne as object_id,
				sfr.id_ligne as object_id_elem_in,				
				edw_field_name as object_name,
				case when edw_field_name_label is not null then replace(edw_field_name_label,\':\',\' \') else replace(edw_field_name,\':\',\' \') end as object_libelle,
				count(sdrs.id_element) as nb_ranges,
				\'%d\' as sdp_id,
				\'%s\' as sdp_label,
				sdgt.family,
				sdc.family_label
			from sys_field_reference as sfr
				left outer join sys_data_range_style as sdrs on sfr.id_ligne=sdrs.id_element and sdrs.data_type=\'counter\'
				left outer join sys_definition_group_table as  sdgt on sdgt.edw_group_table = sfr.edw_group_table
				left outer join sys_definition_categorie as  sdc on sdc.family = sdgt.family
			where sfr.on_off=1
				and sfr.visible=1
				%s				
			group by sfr.id_ligne,sfr.edw_field_name_label,sfr.edw_field_name,sdgt.family,sdc.family_label
			order by object_libelle, edw_field_name asc
			';
		
	/* Get RAW from all products
	 * @param object the search options
	 * @return array the element list
	 */
	public function getRAWs($searchOptions) {
		return $this->getElements(self::RAW, $searchOptions);
	}

	/* Get KPI from all products
	 * @param object the search options
	 * @return array the element list
	 */
	public function getKPIs($searchOptions) {
		return $this->getElements(self::KPI, $searchOptions);
	}

	/* Get elements (RAW or KPI) from all products
	 * @param integer 1 RAW 2 KPI
	 * @param object the search options
	 * @return array the element list
	 */
	private function getElements($type, $searchOptions) {

		$filter = ''; $query = ''; $field_name = ''; $field_label = ''; $productList = array();

		// set the right query for RAW or KPI
		if ($type == self::RAW) {
			$query = self::RAW_QUERY;
			$field_name = 'edw_field_name';
			$field_label = 'edw_field_name_label';
		} else {
			$query = self::KPI_QUERY;
			$field_name = 'kpi_name';
			$field_label = 'kpi_label';
		}

		// search options management
		if ($searchOptions) {
			if ($searchOptions->text) {
				$searchOptions->text = addslashes($searchOptions->text);
				$filter = " and ($field_name ilike '%".$searchOptions->text."%' or $field_label ilike '%".$searchOptions->text."%' or comment ilike '%".$searchOptions->text."%')";
			}
				
			// Create a hashmap with products selected in the search options
			foreach ($searchOptions->products as $prod) {
				$productList[$prod->id] = $prod;
			}
		}

		// get products
		$allProducts = ProductModel::getProducts();
			
		$elements = array();

		// for each product
		foreach ($allProducts as $prod) {
				
			$productFilter = '';
				
			// Get the filter options for this product
			if ($searchOptions) {
				$productFilterOptions = $productList[$prod['sdp_id']];

				// If the product has not been found, don't include element of this product in the result
				if (!$productFilterOptions) {
					continue;
				}
			}
				
			// If there is a filter on families product
			if ($productFilterOptions->families) {
				// get the families to display
				$families = implode($productFilterOptions->families, ',');

				// add filter to display elements for theses families only
				$productFilter = " and sdc.rank in ($families)";
			}
				
			// database connection
			$db_temp = DataBase::getConnection($prod['sdp_id']);
			if( $db_temp->getCnx() )
			{
				// fill the query with current product id and label
				$q	= sprintf($query,$prod['sdp_id'],$prod['sdp_label'], $filter.$productFilter);
					
				// get the result
				$elements = array_merge($elements,$db_temp->getall($q));

				// close db connection
				$db_temp->close();
			}
			unset($db_temp);
		}

		// sort element on label $elements[i]['object_libelle']
		function compare_labels($r1,$r2) {
			return strcasecmp($r1['object_libelle'],$r2['object_libelle']);
		}

		usort($elements,"compare_labels");

		return $elements;
	}

}

?>