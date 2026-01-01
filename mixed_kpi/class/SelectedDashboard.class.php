<?php
/*
	19/11/2009 GHX
		- Ajout de la fonction checkConfigGraph()
	10/12/2009 GHX
		- Correction du BZ 13205 [REC][MIXED-KPI][TC#51606] : des KPI n'apparaissent pas dans la liste des Kpi disponibles
			-> Modification dans la boucle des KPI pour récupérer le bon ID ligne dans le cas d'un KPI qui considèré comme le même
	14/12/2009 NSE 
		- bz 13360 création du menu Mixed KPI à la création du produit de Mixed Kpi
*/
?>
<?php
/**
 *
 *
 * @author GHX
 * @version CB 5.0.2.00
 * @since CB 5.0.2.00
 * @package MixedKPI
 */
class SelectedDashboard
{
	/**
	 * Identifiant du produit Mixed KPI
	 * @var int
	 */
	private $_idMK;
	
	/**
	 * Instance de DatabaseConnection
	 * @var DatabaseConnection
	 */
	private $_db;
	
	/**
	 * Instance de DatabaseConnection sur la produit Mixed KPI
	 * @var DatabaseConnection
	 */
	private $_dbMK;
	
	/**
	 * Tableau d'information sur le produit Mixed KPI
	 * @var array
	 */
	private $_infoMK;
	
	/**
	 * Identifiant du menu Mixed KPI
	 * @var string
	 */
	private $_idMenuMK;
	
	/**
	 * Liste de tous les RAW du produit Mixed KPI
	 * @var array
	 */
	private $_allRawsMixedKPI;
	
	/**
	 * Liste de tous les KPI du produit Mixed KPI
	 * @var array
	 */
	private $_allKpisMixedKPI;
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @version CB 5.0.2.00
	 * @since CB 5.0.2.00
	 * @param int $idProductMixedKpi identifiant du produit Mixed KPI
	 * @param int $idProduct : identifiant du produit sur lequel on doit se connecter (default master product)
	 */
	public function __construct ( $idProductMixedKpi, $idProduct = '' )
	{
		$this->_idMK = $idProductMixedKpi;
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$this->_db = Database::getConnection($idProduct);
		$this->_dbMK = Database::getConnection($this->_idMK);
		
		$productMod = new ProductModel($this->_idMK);
		$this->_infoMK = $productMod->getValues();
		unset($productMod);
		
		$this->createMenuMixedKPI();
		
		$rawMod = new RawModel();
		$this->_allRawsMixedKPI = $rawMod->getAll($this->_dbMK, false, 'old_id_ligne');
		unset($rawMod);
		// Récupère tous les KPIs du produit Mixed KPI
		$kpiMod = new KpiModel();
		$this->_allKpisMixedKPI = $kpiMod->getAll($this->_dbMK, false, 'old_id_ligne');
		unset($kpiMod);
	} // End function __construct
	
	/**
	 * Duplique un dashboard pour l'associé au produit Mixed KPI
	 *
	 * @author GHX
	 * @version CB 5.0.2.00
	 * @since CB 5.0.2.00
	 * @param string $idDashboard : identifiant du dashboard à dupliquer
	 */
	public function duplicate ( $idDashboard, $prefix )
	{
		$dashMod = new DashboardModel($idDashboard);
		try
		{
			// On génère le unique ID par rapport au nom de la base, comme ca on est sur que tous les dashboards Mixed KPI leurs ID commencent par mk.
			$newIdDashboard = $prefix.$idDashboard;
			$dashMod->duplicate($newIdDashboard, 'auto', 'Mixed KPI %s', 'Mixed KPI %2$s (%1$d)', 'customisateur', null, 1, $this->_idMenuMK);
			$dashModDuplicate = new DashboardModel($newIdDashboard);
			// Variables qui permettront de redéfinir le bon sort by du dashboard si possible
			$properties = $dashModDuplicate->getValues();
			$sortByDash = explode('@', $properties['sort_by']);
			$newIdSortBy = null;
			$newTypeSortBy = null;

			// Boucle sur tous les graphes du dashboards
			foreach ( $dashMod->getGtms() as $idGtm => $gtmName )
			{
				// Création d'une instance du GTM à dupliquer
				$gtmMod = new GTMModel($idGtm);
				$gtmProperties = $gtmMod->getGTMProperties();
				$sortByGTM = $gtmMod->getGTMSortBy();
				$typeGTM = $gtmMod->getGTMType();
				$splitByGTM = $gtmMod->getGTMSplitBy();
				$gis = $gtmMod->getGTMGisInformations();
				$familyMK = array();
				$nbElements = 0; // Compte le nombre d'éléments dans le graphe (raw et kpi)
				$nbElementsUnknown = 0; // Compte le nombre d'éléments inconnus dans le graphe
				
				try
				{
					$newIdGTM = $gtmMod->duplicate('auto', 'Mixed KPI %s', 'Mixed KPI %2$s (%1$d)', 'customisateur', null);
					$gtmModDuplicate = new GTMModel($newIdGTM);
					// Supprime la graphe qui a été dupliqué
					$dashModDuplicate->removeGTM($idGtm);
					
					/*
						Boucle sur tous les RAW du graphe
					*/
					$rawsByProductsByFamilies = $gtmMod->getGtmRawsByProduct();
					if ( count($rawsByProductsByFamilies) > 0 )
					{
						// Boucle sur les produits
						foreach ( $rawsByProductsByFamilies as $idProduct => $elementsByProducts )
						{
							// Boucle sur les familles d'un produit
							foreach ( $elementsByProducts as $family => $elementsByFamilies )
							{
								// Boucle sur les compteurs d'une famille
								foreach ( $elementsByFamilies as $idElement => $elementInfo )
								{
									$gtmModDuplicate->removeElement($elementInfo['id'], 'raw');
									$nbElements++; // On incrément le nombre d'éléments dans le graphe
									
									// Teste la présence du compteur sur le produit Mixed KPI
									if ( array_key_exists( $elementInfo['id'], $this->_allRawsMixedKPI) )
									{
										$tmp = $gtmProperties['data2'][$elementInfo['id']];
										$gtmModDuplicate->addElement($this->_allRawsMixedKPI[$elementInfo['id']]['id_ligne'], 'raw', $this->_idMK, $tmp['data_legend'], $tmp['position_ordonnee'], $tmp['display_type'], $tmp['line_design'], $tmp['color'], $tmp['filled_color']);
										
										// 10:37 10/12/2009 GHX
										$familyMK[$this->_allRawsMixedKPI[$elementInfo['id']]['edw_group_table']] = 1;
										
										// Si c'est l'élément est le sort by par défaut du graphe
										if ( $sortByGTM['id'] == $elementInfo['id'] )
										{
											$gtmModDuplicate->setSortByDefault($this->_allRawsMixedKPI[$elementInfo['id']]['id_ligne'], 'raw', $this->_idMK,  $sortByGTM['asc_desc']);
										}
										// Si l'élément est le split by par défaut du PIE
										if ( $typeGTM == 'pie3D' && $splitByGTM['split_type'] == 'first_axis' && $splitByGTM['id'] == $elementInfo['id'])
										{
											$gtmModDuplicate->setSplitFirstAxisByDefault($this->_allRawsMixedKPI[$elementInfo['id']]['id_ligne'], 'raw', $this->_idMK);
										}
										// Si l'élément est le  GIS par défaut
										if ( count($gis) > 0 )
										{
											if ( $gis['id'] == $elementInfo['id'] )
												$gtmModDuplicate->setGisByDefault($this->_allRawsMixedKPI[$elementInfo['id']]['id_ligne'], 'raw', $this->_idMK);
										}
										// Si c'est l'élément qui est le sort by par défaut du dashboard, on mémorise les infos pour bien remettre le même sort by
										if ( $sortByDash[1] == $elementInfo['id'] )
										{
											$newIdSortBy = $this->_allRawsMixedKPI[$elementInfo['id']]['id_ligne'];
											$newTypeSortBy = 'raw';
										}
									}
									else
									{
										$nbElementsUnknown++; // On incrément le nombre d'éléments inconnu pour le graphe
									}
								}
							}
						} // Fin du foreach sur la liste des compteurs du graphe
					} // Fin de la condition sur les raw
					
					/*
						Boucle sur tous les KPI du graphe
					*/
					$kpisByProductsByFamilies = $gtmMod->getGtmKpisByProduct();
					if ( count($kpisByProductsByFamilies) > 0 )
					{
						// Boucle sur les produits
						foreach ( $kpisByProductsByFamilies as $idProduct => $elementsByProducts )
						{
							// Boucle sur les familles d'un produit
							foreach ( $elementsByProducts as $family => $elementsByFamilies )
							{
								// Boucle sur les KPI d'une famille
								foreach ( $elementsByFamilies as $idElement => $elementInfo )
								{
									$gtmModDuplicate->removeElement($elementInfo['id'], 'kpi');
									$nbElements++; // On incrément le nombre d'éléments dans le graphe
									
									$addKpi = true;
									// Teste la présence du kpi sur le produit Mixed KPI
									// 11:15 10/12/2009 GHX
									// Correction du BZ 13205
									// Ajout du if
									if ( array_key_exists( $elementInfo['id'], $this->_allKpisMixedKPI) )
									{
										$familyMK[$this->_allKpisMixedKPI[$elementInfo['id']]['edw_group_table']] = 1;
									}
									elseif ( !($this->_allKpisMixedKPI[$elementInfo['id']]['sdk_sdp_id'] == $idProduct && $this->_allKpisMixedKPI[$elementInfo['id']]['sdk_product_family'] == $family) )
									{
										// Si un autre KPI sur la famille Mixed KPI a le même nom on considère que c'est le même KPI
										if ( $this->_dbMK->getOne("SELECT COUNT(kpi_name) FROM sys_definition_kpi WHERE lower(kpi_name) = '".strtolower($idElement)."' AND edw_group_table IN ('".implode("','", array_keys($familyMK))."')") == 0 )
										{
											$query = "
											SELECT 
												*
											FROM
												sys_definition_kpi AS sdk,
												sys_definition_mixedkpi AS sdmk,
												sys_definition_group_table AS sdgt
											WHERE 
												lower(sdk.kpi_name) = '".strtolower($idElement)."'
												AND sdmk.sdm_sdp_id = ".$idProduct."
												AND sdmk.sdm_family = '".$family."'
												AND sdk.edw_group_table = sdgt.edw_group_table
												AND sdmk.sdm_id = sdgt.family
											ORDER BY 
												sdk.edw_group_table
											";
											// >>>>>>>>>>
											// 11:14 10/12/2009 GHX
											// Correction du BZ 13205
											$res = $this->_dbMK->getAll($query);
											// On teste si le KPI est présent
											if ( count($res) == 0 )
											{
												$addKpi = false;
												$nbElementsUnknown++; // On incrément le nombre d'éléments inconnu pour le graphe
											}
											else
											{
												// On récupère le bon ID ligne et on prend toujours le premier ID ligne trouvé
												if ( count($familyMK) == 0 )
												{
													$elementInfo['id'] = $res[0]['old_id_ligne'];
												}
												else
												{
													foreach ( $res as $r )
													{
														if ( array_key_exists($r['edw_group_table'], $familyMK) )
														{
															$elementInfo['id'] = $r['old_id_ligne'];
															break;
														}
													}
												}
											}
											// <<<<<<<<<< 
										}
									}
									else
									{
										$addKpi = false;
										$nbElementsUnknown++; // On incrément le nombre d'éléments inconnu pour le graphe
									}
									
									// Ajout le KPI
									if ( $addKpi )
									{
										$tmp = $gtmProperties['data2'][$elementInfo['id']];
										$gtmModDuplicate->addElement($this->_allKpisMixedKPI[$elementInfo['id']]['id_ligne'], 'kpi', $this->_idMK, $tmp['data_legend'], $tmp['position_ordonnee'], $tmp['display_type'], $tmp['line_design'], $tmp['color'], $tmp['filled_color']);
										
										// Si c'est l'élément est le sort by par défaut du graphe
										if ( $sortByGTM['id'] == $elementInfo['id'] )
										{
											$gtmModDuplicate->setSortByDefault($this->_allKpisMixedKPI[$elementInfo['id']]['id_ligne'], 'kpi', $this->_idMK, $sortByGTM['asc_desc']);
										}
										// Si l'élément est le split by par défaut du PIE
										if ( $typeGTM == 'pie3D' && $splitByGTM['split_type'] == 'first_axis' && $splitByGTM['id'] == $elementInfo['id'])
										{
											$gtmModDuplicate->setSplitFirstAxisByDefault($this->_allKpisMixedKPI[$elementInfo['id']]['id_ligne'], 'kpi', $this->_idMK);
										}
										// Si l'élément est le  GIS par défaut
										if ( count($gis) > 0 )
										{
											if ( $gis['id'] == $elementInfo['id'] )
												$gtmModDuplicate->setGisByDefault($this->_allKpisMixedKPI[$elementInfo['id']]['id_ligne'], 'kpi', $this->_idMK);
										}
										// Si c'est l'élément qui est le sort by par défaut du dashboard, on mémorise les infos pour bien remettre le même sort by
										if ( $sortByDash[1] == $elementInfo['id'] )
										{
											$newIdSortBy = $this->_allKpisMixedKPI[$elementInfo['id']]['id_ligne'];
											$newTypeSortBy = 'kpi';
										}
									}
								}
							}
						} // Fin du foreach sur la liste des KPI du graphe
					} // Fin de condition des KPI
				}
				catch ( Exception $e )
				{
				}
				
				 // Si on a des éléments inconnus dans le graphe et qu'ils sont tous inconnus
				if ( $nbElements > $nbElementsUnknown )
				{
					// Ajout du graphe dans le dashboard uniquement si on a des éléments dedans
					$dashModDuplicate->addGTM($newIdGTM);
					
					// Si c'est le même élément qui est en
					if ( $newIdSortBy != null )
					{
						$dashModDuplicate->setSortByDefault($newIdSortBy, $newTypeSortBy, $newIdGTM, $this->_idMK, $properties['order']);
						$newIdSortBy = null;
						$newTypeSortBy = null;
					}
				}
				else
				{
					// Comme le graphe est vide on le supprime
					$gtmModDuplicate->delete();
				}
			} // Fin du foreach sur la liste des graphes du dashbaord
		}
		catch ( Exception $e )
		{
		}
	} // End function duplicate
	
	/**
	 * Vérifie que la configuration des graphes sont correctes
	 *
	 *	19/11/2009 GHX
	 *		- Ajout de la fonction
	 *
	 * @author GHX
	 */
	public function checkConfigGraph ()
	{
		// Cette requete va mettre toutes les sériées sur l'ordonnée de gauche pour tous les graphes
		// dont les séries ne sont que sur la droite. En effet si on n'a aucune série sur l'ordonnée de gauche mais que à droite on a une erreur JPGRAPH
		$this->_db->execute("
			UPDATE
				graph_data
			SET
				position_ordonnee = 'left'
			FROM
				sys_pauto_config
			WHERE
				id_data = id
				AND id_page IN (
					SELECT 
						id_page
					FROM 
						sys_pauto_page_name AS sppn LEFT JOIN (
						sys_pauto_config AS spc 
						LEFT JOIN graph_data AS gd on(spc.id = gd.id_data)
						) USING(id_page)
					WHERE
						sppn.page_type='gtm'
					GROUP BY
						id_page
					HAVING  
						SUM(CASE WHEN position_ordonnee = 'left' THEN 1 ELSE 0 END) = 0
						AND SUM(CASE WHEN position_ordonnee = 'right' THEN 1 ELSE 0 END) > 0
				)
			");
	} // End function checkConfigGraph
	/**
	 * Création d'un menu Mixed KPI dans lequel on sera mis les dashboards dupliquer
	 *
	 * - 14/12/2009 NSE bz 13146 modification position menu Mixed Kpi
	 *
	 * @author GHX
	 * @version CB 5.0.2.00
	 * @since CB 5.0.2.00
	 */
	private function createMenuMixedKPI ()
	{	
		$libelleMenuProduct = $this->_infoMK['sdp_label'];
		$idMenuProduct = 'mdi.'.md5($this->_infoMK['sdp_db_name']);
		
		$query = "SELECT * FROM menu_deroulant_intranet WHERE id_menu = '{$idMenuProduct}'";
		$result = $this->_db->getAll($query);
		
		if ( count($result) == 0 ) // Si le menu Mixed KPI n'existe pas
		{
			// Instanciation d'un menu model
			$MenuModel = new MenuModel(0);
			// Ajout des données menu
			$MenuModel->setValue('niveau','1');
			$MenuModel->setValue('id_menu_parent', '0');
			$MenuModel->setValue('position',$MenuModel->getUserMenuLastPosition()+1);
			$MenuModel->setValue('libelle_menu',$libelleMenuProduct);
			$MenuModel->setValue('largeur',strlen($libelleMenuProduct)*10);
			$MenuModel->setValue('deploiement','0');
			$MenuModel->setValue('hauteur','20');
			$MenuModel->setValue('hauteur','20');
			$MenuModel->setValue('droit_affichage','customisateur');
			$MenuModel->setValue('droit_visible','0');
			$MenuModel->setValue('menu_client_default','0');
			$MenuModel->setValue('is_profile_ref_user','1');
			// Enregistrement du menu produit
			$MenuModel->addMenu($idMenuProduct);
			
			// NSE bz 13146 modification position menu Mixed Kpi
			// MaJ de la table profile_menu_position pour positionner le menu après les Dash clients
			// on boucle sur tous les profils
			foreach(ProfileModel::getProfiles() as $profil)
			{
				// uniquement pour les profils utilisateurs
				if ( $profil['profile_type'] == 'user' )
				{
					// Récupération des menus utilisateurs
					$userMenus = MenuModel::getRootUserMenus();
					// on récuprère 
					$req = "SELECT MAX(position) 
								FROM profile_menu_position 
								WHERE id_profile='".$profil['id_profile']."' 
								AND id_menu != '".$idMenuProduct."' 
								AND id_menu IN ('".implode("','",$userMenus)."')";
					
					$newposition = $this->_db->getOne($req);
					
					// on instancie le profil
					$ProfileModel = new ProfileModel($profil['id_profile']);
					// on modifie la position du menu dans le profile
					$ProfileModel->setMenuPosition($newposition+1,$idMenuProduct);
				}
			}
			// NSE bz 13146 fin modif
		}
		
		$this->_idMenuMK = $idMenuProduct;
	} // End function createMenuMixedKPI
} // End class SelectedDashboard
?>