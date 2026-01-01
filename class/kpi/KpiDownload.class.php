<?php
/**
*	09/04/2010 NSE bz 14956 : on ne télécharge que les kpi visibles
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
*
*/
/**
 * Cette classe permet de générer un fichier CSV contenant la liste des KPI d'une famille pour un produit
 *
 * @author GHX
 */
class KpiDownload
{
	/**
	 * Génére un fichier csv contenant la liste des KPI
	 *
	 * @author GHX
	 * @param string $filename chemin complete vers le fichier à générer
	 * @param int $idProduct idenfiant du produit sur lequel se trouve la famille 
	 * @param string $family nom de la famille pour laquelle on veut la liste des KPI (ex: ept, traffic, efferl ...) (defaut toutes les familles
	 */
	public static function createFile ( $filename, $idProduct, $family = null )
	{
		// Requête de récupération des compteurs
		$query = "
		COPY(
			SELECT
				kpi_name,
				kpi_formula,
				kpi_label,
				edw_group_table,
				on_off,
				visible,
				pourcentage,
				comment
			FROM sys_definition_kpi 
			";
		if ( $family != null )
		{
			// 09/04/2010 NSE bz 14956 : on ne télécharge que les kpi visibles
			$query .= "
				WHERE edw_group_table = (
					SELECT edw_group_table FROM sys_definition_group_table
					WHERE family = '{$family}'
					LIMIT 1)
				AND VISIBLE=1

				ORDER BY kpi_name
			)";
		}
		$query .= " TO stdout WITH CSV HEADER DELIMITER AS ';' NULL AS ''";
		
		$product = new ProductModel($idProduct);
		$infosProduct = $product->getValues();
		
		$cmd = sprintf(
			'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
			$infosProduct['sdp_db_password'],
			$infosProduct['sdp_db_login'],
			$infosProduct['sdp_db_name'],
			$infosProduct['sdp_ip_address'],
			$query,
			$filename
		);
		exec($cmd, $r, $error);
		
		if($error)
			return false;
		
		return true;
	} // End function createFile
	
} //End class KpiDownload
?>