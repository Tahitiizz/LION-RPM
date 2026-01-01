<?php
/*
	10/12/2009 GHX
		- Correction du BZ 13205 [REC][MIXED-KPI][TC#51606] : des KPI n'apparaissent pas dans la liste des Kpi disponibles
			-> Modification du nom d'une variable (c'était pas la bonne)
			-> Modification de 2 if/else dans la boucle des KPI
*/
?>
<?php
// Récupère la liste de tous les dashboards sauf ceux qui contiennent des éléments Mixed KPI
$availableDashboards = DashboardModel::getAllDashboard('', ProductModel::getIdMixedKpi());
?>
<script type='text/javascript' src='js/edit_dashboard.js'></script>

<div id="container">
	<!-- titre de la page -->
	<div>
		<h1><img src="../images/titres/setup_mixed_kpi.gif" title="Setup Mixed KPI : configuration" /></h1>
	</div>
	<div class="tabPrincipal" style="width:1075px;text-align:center;padding:10px;">
	<!-- BACK -->
	<div id="BackToMainPage" class="backToMain"><a href="index.php"><?=__T('A_SETUP_MIXED_KPI_BACK_TO_MAIN')?></a></div>
	<!-- HELP -->
	<div class="remarque">
		<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" />
		<div id="help_box_1" class="infoBox">
			<fieldset style="float:right">
				<legend><?php echo __T('G_DRAWLINE_LEGEND'); ?></legend>
				<ul class="legend">
					<li class="info"><?php echo __T('G_GDR_BUILDER_USING_THE_GRAPH_BUILDER');?></li>
					<li class="dash"><?php echo __T('G_PAUTO_DAHSBOARD');?></li>
					<li class="graph"><?php echo __T('G_GDR_BUILDER_GRAPH');?></li>
					<li class="pie"><?php echo __T('G_GDR_BUILDER_PIE');?></li>
					<li class="raw"><?php echo __T('G_GDR_BUILDER_RAW_COUNTERS');?></li>
					<li class="kpi"><?php echo __T('G_GDR_BUILDER_KPI');?></li>
				</ul>
			</fieldset>
			<p>
				<?php echo __T('A_SETUP_MIXED_KPI_INFO_SETECTED_DASHBOARDS'); ?>
			</p>
			<p>
				<dt><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI'); ?></dt>
					<dd><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI_KNOWN'); ?></dd>
					<dd><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_RAW_KPI_UNKNOWN'); ?></dd>
				<dt><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD'); ?></dt>
					<dd><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_COMPLET'); ?></dd>
					<dd><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_INCOMPLET'); ?></dd>
					<dd><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS_INFO_COLOR_CODE_GPD_UNKNOWN'); ?></dd>
			</p>
		</div>
	</div>
	<br />
	
	<!-- MESSAGE -->
	<?php
	if ( isset($message) && !empty($message) )
	{
		?>
		<div class="remarque">
			<div id="message_box_1" class="okMsg">
				<?=$message?>
			</div>
		</div>
		<?php
	}
	if ( isset($messageError) && !empty($messageError) )
	{
		?>
		<div class="remarque">
			<div class="errorMsg">
				<?php echo $messageError; ?>
			</div>
		</div>
		<br />
		<?php
	}
	?>
	<form method="post" action="index.php">
		<div style="width: 1065px;">
			<fieldset>
				<legend><?php echo __T('A_SETUP_MIXED_KPI_FIELDSET_SELECT_DASHBOARDS'); ?></legend>
				<div>
				<ul>
				<?php
				// Code couleur des éléments raw, kpi, graphe, dashboard
				$color = array(
					0 => 'complet', // vert = élément raw/kpi connu sur le produit Mixed KPI, graphe complet, dashboard complet
					1 => 'incomplet', // orange = graphe incomplet, dashboard incomplet
					2 => 'unknown' // rouge = élément raw/kpi incoonu sur le produit Mixed KPI, graphe avec que des éléments inconnus, dashboard avec que des graphes qui ont que des éléments inconnus
				);
				
				$html = ''; // Code HTML qui liste tous les dashboards
				
				// Récupère les informations sur tous les produits
				$productsInformations = getProductInformations();
				
				$dbMK = $mixedKpiModel->getConnection();
				// Récupère tous les RAWs du produit Mixed KPI
				$rawMod = new RawModel();
				$allRaws = $rawMod->getAll($dbMK, false, 'old_id_ligne');
				unset($rawMod);
				// Récupère tous les KPIs du produit Mixed KPI
				$kpiMod = new KpiModel();
				$allKpis = $kpiMod->getAll($dbMK, false, 'old_id_ligne');
				unset($kpiMod);
				
				$dashboardAlreadyDuplicate = $mixedKpiModel->getListDashboardAlreadyDuplicate();
				
				foreach ( $availableDashboards as $dash )
				{
					// Si le dashboard a déjà été dupliquer on ne le réaffiche plus
					if ( in_array($dash['id_page'], $dashboardAlreadyDuplicate) )
						continue;
						
					$htmlDash = ''; // Code HTML qui liste les graphes du dashboard
					$dashMod = new DashboardModel($dash['id_page']);
					$nbGraphs = 0; // Compte le nombre graphe dans le dashboard
					$nbGraphsIncomplete = 0; // Compte le nombre graphe incomplet dans le dashboard
					$nbGraphsEmpty = 0; // Compte le nombre graphe avec que des éléments inconnus dans le dashboard
					$listIdProduct = array(); // Liste des ID des produits que l'on trouve dans le dashbaord
					
					// Boucle sur tous les graphes du dashboards
					foreach ( $dashMod->getGtms() as $idGtm => $gtmName )
					{
						$htmlGraph = ''; // Code HTML qui liste tous les éléments raw/kpi du graphe					
						$gtmMod = new GTMModel($idGtm);
						$nbElements = 0; // Compte le nombre d'éléments dans le graphe (raw et kpi)
						$nbElementsUnknown = 0; // Compte le nombre d'éléments inconnus dans le graphe
						$familyMK = array();
						
						/*
							Boucle sur tous les RAW du graphe
						*/
						$rawsByProductsByFamilies = $gtmMod->getGtmRawsByProduct();
						if ( count($rawsByProductsByFamilies) > 0 )
						{
							// Boucle sur les produits
							foreach ( $rawsByProductsByFamilies as $idProduct => $elementsByProducts )
							{
								$listIdProduct[] = $idProduct;
								// Boucle sur les familles d'un produit
								foreach ( $elementsByProducts as $family => $elementsByFamilies )
								{
									// Boucle sur les compteurs d'une famille
									foreach ( $elementsByFamilies as $idElement => $elementInfo )
									{
										// Teste la présence du compteur sur le produit Mixed KPI
										if ( !array_key_exists( $elementInfo['id'], $allRaws) )
										{
											$nbElementsUnknown++; // On incrément le nombre d'éléments inconnu pour le graphe
											// S'il n'est pas présent, il est affiché en rouge
											$styleEl = $color[2];
										}
										else
										{
											// S'il est présent, il est affiché en vert
											$styleEl = $color[0];
											// On mémorise pour quelle famille Mixed KPI est le compteur
											$familyMK[$allRaws[$elementInfo['id']]['edw_group_table']] = 1;
										}

										$htmlGraph .= '<li class="raw '.$styleEl.'">'.$elementInfo['label'].'</li>';
										$nbElements++; // On incrément le nombre d'éléments dans le graphe
									}
								}
							}
						}
						
						/*
							Boucle sur tous les KPI du graphe
						*/
						$kpisByProductsByFamilies = $gtmMod->getGtmKpisByProduct();
						if ( count($kpisByProductsByFamilies) > 0 )
						{
							// Boucle sur les produits
							foreach ( $kpisByProductsByFamilies as $idProduct => $elementsByProducts )
							{
								$listIdProduct[] = $idProduct;
								// Boucle sur les familles d'un produit
								foreach ( $elementsByProducts as $family => $elementsByFamilies )
								{
									// Boucle sur les KPI d'une famille
									foreach ( $elementsByFamilies as $idElement => $elementInfo )
									{
										// S'il est présent, il est affiché en vert
										$styleEl = $color[0];
										// Teste la présence du kpi sur le produit Mixed KPI
										// 09:49 10/12/2009 GHX
										// Correction du BZ 13205
										// Inversion du IF et du dernier ELSE
										if ( array_key_exists( $elementInfo['id'], $allKpis) )
										{
											// 10:37 10/12/2009 GHX
											// Mauvaise variable utilisé allRaws au lieu de allKpis
											$familyMK[$allKpis[$elementInfo['id']]['edw_group_table']] = 1;
										}
										elseif ( !($allKpis[$elementInfo['id']]['sdk_sdp_id'] == $idProduct && $allKpis[$elementInfo['id']]['sdk_product_family'] == $family) )
										{
											// Si un autre KPI sur la famille Mixed KPI a le même nom on considère que c'est le même KPI
											if ( $dbMK->getOne("SELECT COUNT(kpi_name) FROM sys_definition_kpi WHERE lower(kpi_name) = '".strtolower($idElement)."' AND edw_group_table IN ('".implode("','", array_keys($familyMK))."')") == 0 )
											{
												$query = "
												SELECT 
													count(*) 
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
												";
												// On teste si le KPI est présent
												if ( $dbMK->getOne($query) == 0 )
												{
													$nbElementsUnknown++; // On incrément le nombre d'éléments inconnu pour le graphe
													// S'il n'est pas présent, il est affiché en rouge
													$styleEl = $color[2];
												}
											}
										}
										else
										{
											$nbElementsUnknown++; // On incrément le nombre d'éléments inconnu pour le graphe
											// S'il n'est pas présent, il est affiché en rouge
											$styleEl = $color[2];
										}
										
										$htmlGraph .= '<li class="kpi '.$styleEl.'">'.$elementInfo['label'].'</li>';
										$nbElements++; // On incrément le nombre d'éléments dans le graphe
									}
								}
							}
						}
						
						// Définit la couleur du graphe en fonction du nombre d'élément connus
						if ( $nbElementsUnknown > 0 ) // Si on a des éléments inconnus dans le graphe
						{
							$styleColor = $color[1]; // Couleur orange le graphe à des éléments inconnus
							$nbGraphsIncomplete++; // Incrémente le nombre de graphe incomplet dans le dashboard
							if ( $nbElements == $nbElementsUnknown )
							{
								$nbGraphsEmpty++; // On incrémente le nombre de graphe avec tous les éléments inconnus du graphe
								$styleColor = $color[2]; // Couleur rouge tous les éléments raw/kpi du graphe sont inconnus
							}
						}
						else
						{
							$styleColor = $color[0]; // Couleur verte c'est que le graphe est complet
						}
	
						$htmlDash .= '<li>';
						$htmlDash .= '<img src="'.NIVEAU_0.'/builder/images/arrow_right.png" class="displayInfo" onclick="displayInfo(this, \''.$idGtm.'\')"/>';
						$htmlDash .= '<span class="'.$gtmMod->getGTMType().' '.$styleColor.'">'.$gtmName.'</span>';
						$htmlDash .= '<ul style="display:none" id="list-'.$idGtm.'">'.$htmlGraph.'</ul></li>';
						$nbGraphs++; // On incrémente le nombre de graphe dans le dashboard
					}
					
					// Définit la couleur du dashboards en fonction du nombre de graphes complets
					$disabled = '';
					if ( $nbGraphsIncomplete > 0 )
					{
						$styleColor = $color[1]; // Couleur orange le dashboard à des éléments inconnus
						if ( $nbGraphs == $nbGraphsEmpty )
						{
							$disabled = ' disabled';
							$styleColor = $color[2]; // Couleur rouge tous les éléments raw/kpi du dashboard sont inconnus
						}
					}
					else
					{
						$styleColor = $color[0]; // Couleur verte c'est que le dashboard est complet
					}
					
					// On précise de quels produits sont les éléments du dashboard
					$listIdProduct = array_unique($listIdProduct);
					$htmlProd = '';
					foreach ( $listIdProduct as $idProd )
					{
						$htmlProd .= $productsInformations[$idProd]['sdp_label'].', ';
					}
					$html .= '<li>';
					$html .= '<img src="'.NIVEAU_0.'/builder/images/arrow_right.png" class="displayInfo" onclick="displayInfo(this, \''.$dash['id_page'].'\')"/>';
					$html .= '<input type="checkbox" name="selectedDash[]" value="'.$dash['id_page'].'" id="'.$dash['id_page'].'" '.$disabled.' >&nbsp;';
					$html .= '<label class="dash '.$styleColor.'" for="'.$dash['id_page'].'">'.$dash['page_name'].'</label>&nbsp;';
					$html .= '<span>('.substr($htmlProd, 0, -2).')</span>';
					$html .= '<ul style="display:none" id="list-'.$dash['id_page'].'">'.$htmlDash.'</ul></li>';
				}
				echo '<ul class="selectedDashboard">'.$html.'</html>';
				?>
				</ul>
				<p style="text-align: center;"><input type="submit" value="Save" name="saveSelectedDashboards" class="bouton" /></p>
				</div>
			</fieldset>
		</div>
	</form>
	</div>
</div>