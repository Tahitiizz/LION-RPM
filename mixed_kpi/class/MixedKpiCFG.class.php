<?php
/*
	03/11/2009 GHX
		- Création du fichier .cfg même si pas de Data Export à générer sur un produit 
			-> Ceci permet de gérer le cas où on n'a plus besoin de générer des Data Exports pour un produit, ca permet de supprimer tous les Data Export du produit
 * 11/10/2011 NSE DE Bypass temporel
*/
?>
<?php
/**
 * Cette classe permet de générer les fichiers de configurations CFG pour la configuration des Data Export et des les envoyer sur les différents produits
 *
 * @author GHX
 */
class MixedKpiCFG
{
	/**
	 * Identifiant du produit Mixed KPI
	 * var int
	 */
	private $_idMK = null;
	
	/**
	 * Instance de connexion à la base Mixed KPI
	 * @var DatabaseConnection
	 */
	private $_dbMK = null;
	
	/**
	 * IP du serveur courant
	 * @var string
	 */
	private $_ipServer = null;
	
	/**
	 * Valeur de la TA minimum
	 * @var string
	 */
	private $_taMin = null;
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 */
	public function __construct ()
	{
		$this->_idMK = ProductModel::getIdMixedKpi();
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->_dbMK =  Database::getConnection($this->_idMK);
		$this->_ipServer = get_adr_server();
	} // End function __construct

	/**
	 * Définir le TA minimum pour laquel les Data Exports doivent être généré
	 *
	 * @author GHX
	 * @param string $taMin nom de la TA minimum hour ou day
	 */
	public function setTaMin ( $taMin )
	{
		$this->_taMin = $taMin;
	} // End function setTaMin
	
	/**
	 * Retourne le format du nom du fichier CFG qui est doit être générer
	 *
	 * @author GHX
	 * @return string
	 */
	public function getFormatNameCFG ()
	{
		return 'update_data_export.cfg';
	} // End function getFormatNameCFG
	
	/**
	 * Génère le contenu du fichier .cfg d'un produit
	 *
	 * @author GHX
	 * @param int $idProduct identifiant du produit pour lequel on doit générer le fichier de configuration 
	 * @param string $fileCFG chemin vers le fichier de configuration que l'on doit remplir
	 */
	public function generateFileCFG ( $idProduct, $fileCFG )
	{
		if ( $idProduct == $this->_idMK )
			return;
		
		$product = new ProductModel($idProduct);
		$productInfos = $product->getValues();
		$productName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $productInfos['sdp_label']));
		
		// Récupère la liste des familles du produit pour lesquelles on doit remonter des données = générer des Data Export (1 par famille)
		$families = $this->_dbMK->getALL("
			SELECT DISTINCT
				sdm_id,
				sdm_family,
				network_aggregation_min
			FROM
				sys_definition_mixedkpi 
				LEFT JOIN sys_definition_categorie ON (sdm_id= family)
				LEFT JOIN sys_definition_group_table USING (family)
				LEFT JOIN sys_field_reference USING (edw_group_table)
			WHERE
				sdm_sdp_id = {$idProduct}
				AND sfr_sdp_id = sdm_sdp_id
				AND sdm_family = sfr_product_family
			");
		
		// 10:37 03/11/2009 GHX
		// Créer le fichier
		file_put_contents($fileCFG, "", FILE_APPEND);
		
		foreach ( $families as $family )
		{                    
			// Récupération des valeurs de l'export
			$exportName = "auto_".$productName."_".$family['sdm_id']."_".$family['sdm_family'];
			$targetDir = '/home/'. $productInfos['sdp_directory'].'/upload/export_files_mixed_kpi';
			$timeAggregation = $this->_taMin;
			$networkAggregation = $family['network_aggregation_min'];
			$suffix = '';//$family['sdm_family'].'_'.$productInfos['sdp_id'].'_mk';			
			$prefix = MixedKpiModel::getPrefix($idProduct,$family['sdm_family']);
			//$prefix = $productInfos['sdp_trigram'].'_'.$family['sdm_family'];
			
			// Affectation des valeurs à insérer
			$valuesToInsert = Array();
			$valuesToInsert[] = $family['sdm_family']; // Nom de la famille
			$valuesToInsert[] = $exportName; // Nom de l'export et nom du fichier csv (pas besoin d'ajouter le .csv il est ajouté automatiquement)
			$valuesToInsert[] = $targetDir; // Répertoire dans lequel sera créé le fichier de data export
			$valuesToInsert[] = $timeAggregation; // TA sur laquel on doit générer le data export
			$valuesToInsert[] = $networkAggregation; // NA d'axe 1 sur lequel on doit générer le data export
			$valuesToInsert[] = '';  // NA d'axe 3 sur lequel on doit générer le data export
			$valuesToInsert[] = '0'; // Si le Data Export est visible en IHM
			$valuesToInsert[] = '1'; // Si doit exporter les RAW
			$valuesToInsert[] = '0'; // Si doit exporter les KPI
			$valuesToInsert[] = $idProduct; // Id du produit
			
			// 29/03/2010 BBX : ajout de la gestion du préfixe
			$valuesToInsert[] = $suffix; // add suffix
			// 06/04/2010 BBX : On supprime le dernier "_" du préfixe. BZ 14954
			if(strrpos($prefix, '_', (strlen($prefix) - 1)) !== false)
				$prefix = substr($prefix,0,-1);
			$valuesToInsert[] = $prefix; // add prefix
			
			// Ligne à insérer dans le fichier
			$lineToInsert = implode(';',$valuesToInsert)."\n";

                        // 11/10/2011 NSE DE Bypass temporel : ajout d'une ligne pour générer le Data Export de la Ta Bypassée
                        foreach(TaModel::getAllTaForFamily($family['sdm_family'], $idProduct) as $ta){
                            // si la Ta est supérieure à la Ta Min du Corporate et différente de la Ta de l’enregistrement courant
                            if(TaModel::isTa1Greater($ta,$timeAggregation,$idProduct)>0){
                                if(TaModel::IsTABypassedForFamily($ta, $family['sdm_family'], $idProduct)==1){
                                    // on modifie le nom de l'export
                                    $valuesToInsert[1] = "auto_".$productName."_".$family['sdm_id']."_".$family['sdm_family'].'_bypass_'.$ta;
                                    // On modifie la Ta
                                    $valuesToInsert[3] = $ta;
                                    // on insère cette ligne supplémentaire dans le fichier
                                    $lineToInsert .= implode(';',$valuesToInsert)."\n";;
                                }
                            }
                        }
			file_put_contents($fileCFG, $lineToInsert, FILE_APPEND);
                        
		}		
	} // End function generateFileCFG
	
	/**
	 * Envoie le fichier de configuration vers un produit
	 *
	 * @author GHX
	 * @param int $idProduct identifiant du produit pour lequel on doit envoyer le fichier de configuration 
	 * @param string $fileCFG chemin vers le fichier de configuration à envoyer
	 * @return boolean
	 */
	public function sendFile ( $idProduct, $fileCFG )
	{
		$product = new ProductModel($idProduct);
		$productInfos = $product->getValues();
		$targetDir = '/home/'. $productInfos['sdp_directory'].'/upload/export_files_mixed_kpi/';
		
		// Si le produit est sur le même serveur
		if ( $productInfos['sdp_ip_address'] == $this->_ipServer )
		{
			// On tente de créer le répertoire export_files_mixed_kpi s'il n'existe pas
			if ( !file_exists($targetDir) )
			{
				mkdir($targetDir, 0777);
				exec('chmod 0777 "'.$targetDir.'"');
			}
			$res = @copy($fileCFG, $targetDir.basename($fileCFG));
			exec('chmod 0777 "'.$targetDir.basename($fileCFG).'"');
			return $res;
		}
		else // Le produit est sur un serveur distant
		{
			try
			{
				$ssh = new SSHConnection($productInfos['sdp_ip_address'], $productInfos['sdp_ssh_user'], $productInfos['sdp_ssh_password'], $productInfos['sdp_ssh_port']);
				if ( !$ssh->fileExists($targetDir) )
				{
					$ssh->mkdir($targetDir);
					$ssh->exec('chmod 0777 "'.$targetDir.'"');
				}
				$ssh->sendFile($fileCFG, $targetDir.basename($fileCFG));
				$ssh->exec('chmod 0777 "'.$targetDir.basename($fileCFG).'"');
				return true;
			}
			catch ( Exception $e )
			{
				return false;
			}
		}
	} // End function sendFile
	
	/**
	 * Génère pour tous les produits active un fichier de config pour la création des Data Export si nécessaire
	 *
	 * @author GHX
	 */
	public function generateAndSendForAllProducts ()
	{
		foreach ( ProductModel::getActiveProducts() as $product )
		{
			// Pas de Data Export sur le produit Mixed KPI
			if ( $product['sdp_id'] == $this->_idMK )
				continue;
                            
                        // 21/11/2011 BBX
                        // BZ 24527 : suppression du 3ème paramètre obsolète			
			$filename = REP_PHYSIQUE_NIVEAU_0.'upload/'.$this->getFormatNameCFG();
			$this->generateFileCFG($product['sdp_id'], $filename);
			// Si le fichier CFG a été créé c'est qu'il faut envoyé le fichier de config sur le produit
			if ( file_exists($filename) )
			{
				$this->sendFile($product['sdp_id'], $filename);
				@unlink($filename);
			}
		}
	} // End function generateAndSendForAllProducts
	
} // End class MixedKpiCFG
?>