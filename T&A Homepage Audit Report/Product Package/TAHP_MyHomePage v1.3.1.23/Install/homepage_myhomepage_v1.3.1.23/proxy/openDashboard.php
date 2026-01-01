<?php
if(!isset($_SESSION)) session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once('dao/models/RawKpiModel.class.php');
include_once('dao/models/KpiModel.class.php');
include_once('dao/models/RawModel.class.php');
include_once('dao/models/DashboardModel.class.php');
include_once('dao/models/MenuModelBis.class.php');

// récupération des informations passées en GET
$url = isset($_REQUEST["url"])? $_REQUEST["url"] : '';

$productId = isset($_REQUEST['productId'])? $_REQUEST['productId'] : '';
$raw_kpi = isset($_REQUEST['raw_kpi'])? $_REQUEST['raw_kpi'] : '';
$type = isset($_REQUEST['type'])? $_REQUEST['type'] : '';
$time_agregation = isset($_REQUEST["time_agregation"])? $_REQUEST["time_agregation"] : '';
$time_value = isset($_REQUEST["time_value"])? $_REQUEST["time_value"] : '';
$network_agregation = isset($_REQUEST["network_agregation"])? $_REQUEST["network_agregation"] : '';
$network_name = isset($_REQUEST["network_name"])? $_REQUEST["network_name"] : '';
$period=isset($_REQUEST["period"])? $_REQUEST["period"] : '20';
$network_3_axe_agregation = isset($_REQUEST["network_3_axe_agregation"])? $_REQUEST["network_3_axe_agregation"] : '';
$network_3_axe_name = isset($_REQUEST["network_3_axe_name"])? $_REQUEST["network_3_axe_name"] : '';
if ($network_3_axe_agregation != '' && $network_3_axe_name != '') {
	$network_3_axe_param = $network_3_axe_agregation."||".$network_3_axe_name;
}

$overtimeonly=isset($_REQUEST["overtimeonly"])? $_REQUEST["overtimeonly"]=="true" : false;

// initialisation du paramètre not_found permettant d'afficher un message d'erreur
$not_found = '';
$dashboard_menu = array();

if(!empty($productId)){
	$database_connection = new DataBaseConnection($productId);
	
	if($raw_kpi != '' && $type != ''){
		// recherche les GTMs associés à ce RAW / KPI
		if ($type == 'KPI') {
			$rawKpiModel = new KpiModel();
		} else {
			$rawKpiModel = new RawModel();
		}
		$graphList = $rawKpiModel->getGraphListWith($raw_kpi, $productId);
		
		if(count($graphList) > 0){
			foreach ($graphList as $graph) {
				// recherche des Dashboards associés à cette série
				$dashboardModel = new DashboardModel(0);
				$dashboardList = $dashboardModel->getDashboardFromGTM($graph['id_page']);
				
				if(count($dashboardList) > 0){
					foreach ($dashboardList as $d) {
						$dashboard = new DashboardModel($d['id_page']);
						$dashboardValues = $dashboard->getValues();
																		
						// recherche du menu
						//to get all dashboard, call on master product
						//$menu = new MenuModelBis($dashboardValues['id_menu'], 0, $productId);
						$menu = new MenuModelBis($dashboardValues['id_menu'], 0, 0);
												
						// recherche des urls (OT et ONE) dans la table menu_deroulant_intranet
						$menuChildren = $menu->getMenuChildren();
						
						if(count($menuChildren) > 0) {
							foreach ($menuChildren as $child) {
								if($overtimeonly && strpos($child["lien_menu"],'overnetwork')!=false)continue;
								$dashboard_menu[$child["lien_menu"]."&id_menu_encours=".$child["id_menu"]] = $menu->getValue('libelle_menu')." ($child[libelle_menu])";
							}
						} else
							$not_found = 'No Dashboard found';
					}
				}
				else
					$not_found = 'No Dashboard found';
			}
		}
		else
			$not_found = 'No GTM found';
	}
	else
		$not_found = 'No KPI found';
}
else
	$not_found = 'No Product found';
?>

<html>
<head>
	<title>List of dashboards</title>
	<script type="text/javascript" src="<?=$niveau0?>js/prototype/prototype.js"> </script>
	<script type="text/javascript" src="<?=$niveau0?>js/prototype/window.js"> </script>
	<script type="text/javascript" src="<?=$niveau0?>js/prototype/scriptaculous.js"> </script>
	<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css"/>
	<script>
	function redirection(url,mode){
		str_params = "url=" + url;
		str_params+= "&time_agregation=<?php echo $time_agregation; ?>";
		str_params+= "&time_value=<?php echo $time_value; ?>";
		str_params+= "&network_agregation=<?php echo $network_agregation; ?>";
		str_params+= "&network=<?php echo $network_agregation."||".$network_name; ?>";
		str_params+= "&network_3_axe_agregation=<?php echo $network_3_axe_agregation; ?>";
		str_params+= "&network_3_axe=<?php echo $network_3_axe_param; ?>";
		str_params+= "&period=<?php echo $period; ?>";
		str_params+= "&mode=" + mode;
		//prompt('', str_params);
		
		// redirection vers cette même page, afin de sauvegarder les paramètres du selecteur dans la session
		new Ajax.Request('<?=$niveau0?>homepage/proxy/function_ajax.php',
				{
					method: 'get',
					parameters: str_params,
					onSuccess: function(link){
						if(link.responseText != ''){

							console.log(link.responseText);
							window.opener.location.href = link.responseText;
							self.close();
						}
						else {
							alert('An error has occured, sorry it\'s impossible to redirect on Dashboard');
						}
					}
				});
	}
	</script>
</head>
<body bgcolor="#fefefe">

<table align="center" width="100%">
	<tr><td align="center"><img src="<?=$niveau0?>images/titres/dashboard_switch.gif"/><br />&nbsp;</td></tr>
	<tr valign='center' align='center'>
		<td align='center' valign='center'>
			<table cellpadding="3" cellspacing="1" class="tabPrincipal">
				<tr align="center">
					<td align="center">
						<table width="350px" align="center"><tr>	<td>
							<fieldset>
								<legend>&nbsp;<img src="<?=$niveau0?>images/icones/icone_astuce.gif">&nbsp;</legend>
								<div class="texteGris" style='padding:3px;'>
									List of dashboards containing this RAW/KPI.
								</div>
							</fieldset>
						</td></tr>
						</table>
					</td>
				</tr>
				<tr align='center'>
					<td align='center'>
						<table width="80%">
							<tr>
								<td>
									<fieldset>
										<legend class="texteGrisBold">
											&nbsp;
											<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif">
											&nbsp;
											Dashboards
											&nbsp;
										</legend>
										<table cellpadding="3" cellspacing="1" border=0 class="texteGris">
											<tr>
												<td>
													<?php
													if ($dashboard_menu) {
														foreach($dashboard_menu as $link => $dash)	{
															$url = urlencode($niveau0  . $link);
															parse_str($link, $args);
															$mode=$args['mode'];
															if(empty($mode))$mode='overtime';
															
																?>
																<li><a style='cursor:pointer; text-decoration:underline;' onclick="javascript:redirection('<?php echo $url; ?>','<?php echo $mode; ?>');"><?=$dash?></a></li>
															<?php 
														}
													} else {
														echo "<strong>$not_found</strong>";
													} ?>
												</td>
											</tr>
										</table>
									</fieldset>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</body>
</html>