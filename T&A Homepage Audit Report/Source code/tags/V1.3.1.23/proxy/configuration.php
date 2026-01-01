<?php
if(!isset($_SESSION)) session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");

// Get the user name
$database_connection = new DataBaseConnection();
$query = "SELECT login
			FROM users 
			WHERE id_user='{$_SESSION['id_user']}'
			LIMIT 1;";
$userName = $database_connection->getOne($query);

// If the user is client_user or astellia_user, he has an admin access to the homepage
$isAdmin = ($userName == 'astellia_admin' || $userName == 'astellia_user') ? 1 : 0;

$task = '';
if (isset($_POST['task'])){
	// Get task from Ext JS
	$task = $_POST['task'];
}

switch($task){
	// Initialize the user configuration files
	case 'INIT':
		initialize();
		break;
		
	// Get the version of the homepage
	case 'VERSION':
		getVersion();
		break;
		
	// Get the version of the homepage
	case 'LAST_DATE':
		getLastIntegrationDate();
		break;
	
	// See if today's or yesterday's date is integrated
	case 'EXIST_LAST_DATE':
		existLastIntegrationDate();
		break;
		
	// Get the ri value from each day
	case 'RI_VALUE':
		getRiValue();
		break;
	
	// Get the activated templates
	case 'GET_TEMPLATES':
		getTemplates();
		break;
		
	// Get the products
	case 'GET_PRODUCTS':
		getProducts();
		break;
	
	// Get the report upload tree
	case 'GET_NODES':
		getReportUploadTreeNode();
		break;
	
	// Get the dashboards for the product only
	case 'GET_DASHBOARDS':
		getDashboards();
		break;
		
	// Get the dashboards for all products
	case 'GET_DASHBOARDS_ALL':
		getDashboardsAll();
		break;
		
	// Get the alarms
	case 'GET_ALARMS':
		getAlarms();
		break;
		
	// Get the map configuration
	case 'GET_MAP_CONF':
		getMapConf();
		break;
		
	// Get the tabs
	case 'GET_TABS':
		getTabs();
		break;
	
	// Get the tabs
	case 'GET_MAPID':
		getMapId();
		break;

			
	// Add a new tab
	case 'GET_AMMAPID':
		getAmmapIds();
		break;
		
	// Save the new configuration
	case 'SAVE':
		save();
		break;

	// Save a new template
	case 'SAVE_TEMPLATE':
		save_template();
		break;

	// Save the new configuration
	case 'RESET':
		resetConfiguration();
		break;
		 
	// Load the configuration
	case 'LOAD':
		load();
		break;

	// Load the trend configuration
	case 'LOAD_TREND':
		loadTrend();
		break;

	// Make the gauge configuration
	case 'GAUGE':
		makeGaugeConfig();
		break;

	// Add a new tab
	case 'ADD_TAB':
		addTab();
		break;
		
	// Copy a tab
	case 'COPY_TAB':
		copyTab();
		break;

	// Add a new tab
	case 'DELETE_TAB':
		deleteTab();
		break;

	// Get the tabs
	case 'IS_ROAMING':
		isRoaming();
		break;
		
	default:
		echo 'failure';
		break;
}

function initialize() {
	global $isAdmin;
	$error = false;

	// Update of the folder /files
	$limitDate = mktime(0, 0, 0, date('m') , date('d') - 5, date('Y'));
	$OutputDir = '../files/';

	$handle = opendir($OutputDir);
	while(false !== ($fichier = readdir($handle))) {
		if(($fichier != '.') && ($fichier != '..')) {
			// Delete files older than 5 days
			$date = mktime(0, 0, 0, (int)substr($fichier, 4, 2), (int)substr($fichier, 6, 2), (int)substr($fichier, 0, 4));
			if(is_int($date) &&	$date < $limitDate) recursiveDelete($OutputDir.$fichier);
		}
	}

	if (!$isAdmin) {
		$userId = $_SESSION['id_user'];
		if (!is_dir('../config/'.$userId)) {
			// The user is using the application for the first time
			// subtract the umask to get the actual permission 0777 BZ: 38466
			$oldmask = umask(0);	
			// Create the user configuration folder and copy the default configuration
			mkdir('../config/'.$userId, 0777);
			copy('../config/default/homepage.xml', '../config/'.$userId.'/homepage.xml');
			chmod('../config/'.$userId.'/homepage.xml', 0777);
				
			// Create the gauges configuration folders and copy the default configurations
			mkdir('../config/'.$userId.'/gauges', 0777);
				
			// Parse the gauge folder
			$gaugeFolder = dir('../config/default/gauges');
			while (FALSE !== ($folder = $gaugeFolder->read())) {
				if ($folder == '.' || $folder == '..') {
					continue;
				}

				// Get the tab folders
				$fileStr = '../config/default/gauges/'.$folder;
				if (is_dir($fileStr) && ($folder != '.svn')) {
					// Create the tab folder for the user
					mkdir('../config/'.$userId.'/gauges/'.$folder, 0777);
						
					// Parse the tab folder to get the chart folders
					$tabFolder = dir($fileStr);
					while (FALSE !== ($file = $tabFolder->read())) {
						$sourceFile = $fileStr.'/'.$file;

						if ($file == '.' || $file == '..' || is_dir($sourceFile)) {
							continue;
						}

						$destFile = '../config/'.$userId.'/gauges/'.$folder.'/'.$file;
						copy($sourceFile, $destFile);
						chmod($destFile, 0777);
					}
					$tabFolder->close();
				} else if ($folder == 'template.xml') {
					$destFile = '../config/'.$userId.'/gauges/'.$folder;
					copy($fileStr, $destFile);
					chmod($destFile, 0777);
				}
			}
			$gaugeFolder->close();
			
			umask($oldmask);
		}
		
	}
	
	// Load the general configuration file
	$dom = new DOMDocument();
	$dom->load('../config/general.xml');
	
	// Get the gauges_ie node
	$xpath = new DOMXpath($dom);
	$nodeList = $xpath->query('/general/gauges_ie');
	if ($nodeList->item(0)->nodeValue == '1') {
		$gaugeType = '1';
	} else {
		$gaugeType = '0';
	}
	
	//Get autorefresh timer
	$nodeList = $xpath->query('/general/autorefreshtimer');
	$timer=$nodeList->item(0)->nodeValue;
	//In case of missing parameter
	if(empty($timer))$timer=10000;
	
	
	//get CellsSurveillance parameters
	
	//penalisationmode
	$nodeList = $xpath->query('/general/penalisationmode');
	if($nodeList->length>0){
		$penalisationmode=$nodeList->item(0)->nodeValue;
	}
	else{
		// Create a new node
		$penalisationNode = $dom->createElement('penalisationmode', '0');				
		$homepageNodeList = $xpath->query('/general');
		$homepageNodeList->item(0)->appendChild($penalisationNode);	
		$dom->formatOutput = true;
		$dom->save('../config/general.xml');
		$penalisationmode="0";
	}
	
	
	//reference period
	$nodeList = $xpath->query('/general/referenceperiod');
	if($nodeList->length >0){
		$referenceperiod=$nodeList->item(0)->nodeValue;
	}
	else{
		// Create a new node
		$referenceNode = $dom->createElement('referenceperiod', '3');
		$homepageNodeList = $xpath->query('/general');
		$homepageNodeList->item(0)->appendChild($referenceNode);
		$dom->formatOutput = true;
		$dom->save('../config/general.xml');
		$referenceperiod="3";
	}

	//mapId
	$nodeList = $xpath->query('/general/mapid');
	if($nodeList->length==0){
		// Create a new node
		//set to worldHigh by default
		$mapId = $dom->createElement('mapid', 'worldHigh');
		$homepageNodeList = $xpath->query('/general');
		$homepageNodeList->item(0)->appendChild($mapId);
		$dom->formatOutput = true;
		$dom->save('../config/general.xml');
	}

	echo '{"admin": '.$isAdmin.', "gauge": '.$gaugeType.', "timer": '.$timer.', "penalisationmode": '.$penalisationmode.', "referenceperiod": '.$referenceperiod.'}';
}

function getVersion() {
	global $database_connection;

	$query = 'SELECT value FROM sys_global_parameters WHERE parameters = \'homepage_c-sight_version\'';
	$version = $database_connection->getOne($query);
	
	echo $version;
}
 
function existLastIntegrationDate(){
	$sdp_id = (empty($_POST['sdp_id']) ? 1 : $_POST['sdp_id']);
	$date = (empty($_POST['date']) ? 1 : $_POST['date']);
	$time_level = (empty($_POST['time_level']) ? "" : $_POST['time_level']);
	
	$thirdAxisProduct = false;
	
	$db = Database::getConnection($sdp_id);
	
	$query = "SELECT value FROM sys_global_parameters WHERE parameters LIKE 'module'";
	
	$module = $db->getOne($query);
	
	$query = "select MAX(axe) as maxnumberaxes FROM sys_definition_network_agregation";
	
	$result = $db->getAll($query);	
	
	if($result[0]["maxnumberaxes"] == "3"){
		$thirdAxisProduct = true;
	}
	
	if($thirdAxisProduct == false){
		$query = "SELECT family, network_aggregation_min as level FROM sys_definition_categorie WHERE main_family=1";
		$result = $db->getAll($query);	
		
		$family=$result[0]["family"];
		$level=$result[0]["level"];
	}else{
		$query = "SELECT family, network_aggregation_min as level,link_to_aa_3d_axis as linkTo3d  FROM sys_definition_categorie WHERE main_family=1";
		$result = $db->getAll($query);	
		
		$family=$result[0]["family"];
		$level=$result[0]["level"];
		$linkTo3d=$result[0]["linkTo3d"];
		if($linkTo3d == "t"){
			$query = "SELECT network_agregation from sys_definition_group_table_network WHERE network_agregation ILIKE '".$level."\_%' AND rank = -1 AND data_type = 'kpi'";
		}else{
			$query = "SELECT network_agregation from sys_definition_group_table_network WHERE network_agregation ILIKE '".$level."%' AND rank = -1 AND data_type = 'kpi'";
		}
		$result = $db->getAll($query);
		
		$level=$result[0]["network_agregation"];
		
		
	}

	if($time_level == ""){
		//SELECT MAX(day) as date FROM edw_ztebss_bss_axe1_kpi_cell_day
		$query = "SELECT COUNT(day) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day where day =".$date;
	}else{
		switch($time_level){
			case 'day';
				$query = "SELECT COUNT(day) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day where day =".$date;
			break;

			case 'day_bh';
				$query = "SELECT COUNT(day_bh) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day_bh where day =".$date;
			break;
			
			case 'week';
				$query = "SELECT COUNT(week) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_week where week =".$date;
			break;
			
			case 'week_bh';
				$query = "SELECT COUNT(week_bh) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_week_bh where week =".$date;
			break;
			
			case 'month';
				$query = "SELECT COUNT(month) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_month where month =".$date;
			break;
			
			case 'month_bh';
				$query = "SELECT COUNT(month) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_month_bh where month =".$date;
			break;
			
			case 'hour';
				$query = "SELECT COUNT(hour) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_hour where day =".$date;
				$count = $db->getOne($query);
					if($count > 0){
						$query = "SELECT hour as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_hour where day =".$date."order by hour desc limit 1";
					}
			break;
			
			default;
		        $query = "SELECT COUNT(day) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day where day =".$date;
		    break;

		}
	}
	//var_dump($query);
	$result = $db->getOne($query);
	
	echo $result;

}


/**
 * 
 * 
 * get date for last compute day
 * @param unknown_type $sdp_id
 */

function getLastIntegrationDate() {
		
	$sdp_id = (empty($_POST['sdp_id']) ? 1 : $_POST['sdp_id']);
	
	$time_level = (empty($_POST['time_level']) ? "" : $_POST['time_level']);
	
	$thirdAxisProduct = false;
	
	$db = Database::getConnection($sdp_id);
	
	$query = "SELECT value FROM sys_global_parameters WHERE parameters LIKE 'module'";
	
	$module = $db->getOne($query);
	
	$query = "select MAX(axe) as maxnumberaxes FROM sys_definition_network_agregation";
	
	$result = $db->getAll($query);	
	
	if($result[0]["maxnumberaxes"] == "3"){
		$thirdAxisProduct = true;
	}
	
	if($thirdAxisProduct == false){
		$query = "SELECT family, network_aggregation_min as level FROM sys_definition_categorie WHERE main_family=1";
		$result = $db->getAll($query);	
		
		$family=$result[0]["family"];
		$level=$result[0]["level"];
	}else{
		$query = "SELECT family, network_aggregation_min as level,link_to_aa_3d_axis as linkTo3d  FROM sys_definition_categorie WHERE main_family=1";
		$result = $db->getAll($query);	
		
		$family=$result[0]["family"];
		$level=$result[0]["level"];
		$linkTo3d=$result[0]["linkTo3d"];
		if($linkTo3d == "t"){
			$query = "SELECT network_agregation from sys_definition_group_table_network WHERE network_agregation ILIKE '".$level."\_%' AND rank = -1 AND data_type = 'kpi'";
		}else{
			$query = "SELECT network_agregation from sys_definition_group_table_network WHERE network_agregation ILIKE '".$level."%' AND rank = -1 AND data_type = 'kpi'";
		}
		
		$result = $db->getAll($query);
		
		$level=$result[0]["network_agregation"];
		
	}

	if($time_level == ""){
		//SELECT MAX(day) as date FROM edw_ztebss_bss_axe1_kpi_cell_day
		$query = "SELECT MAX(day) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day";
		
	}else{
		switch($time_level){
			case 'day';
				$query = "SELECT MAX(day) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day";
			break;

			case 'day_bh';
				$query = "SELECT MAX(day_bh) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day_bh";
			break;
			
			case 'week';
				$query = "SELECT MAX(week) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_week";
			break;
			
			case 'week_bh';
				$query = "SELECT MAX(week_bh) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_week_bh";
			break;
			
			case 'month';
				$query = "SELECT MAX(month) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_month";
			break;
			
			case 'month_bh';
				$query = "SELECT MAX(month) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_month_bh";
			break;
			
			case 'hour';
				$query = "SELECT MAX(hour) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_hour";
			break;
			
			default;
		        $query = "SELECT MAX(day) as date FROM edw_".$module."_".$family."_axe1_kpi_".$level."_day";
		    break;

		}
	}
	//var_dump($query);
	$date= $db->getOne($query);
	
	echo $date;
}

function getRiValue(){
	$sdp_id = (empty($_POST['sdp_id']) ? 1 : $_POST['sdp_id']);
	
	$db = Database::getConnection($sdp_id);

	$current_month = (empty($_POST['current_month']) ? "" : $_POST['current_month']);
	
	$query = "SELECT value FROM sys_global_parameters WHERE parameters LIKE 'module'";
	
	$module = $db->getOne($query);
	
	$query = "SELECT family, network_aggregation_min as level FROM sys_definition_categorie WHERE main_family=1";
	
	$result = $db->getAll($query);
	
	$family =$result[0]["family"];
	
	$query = "SELECT DISTINCT day, ri_capture_duration FROM edw_".$module."_".$family."_axe1_kpi_vendor_day WHERE month = '".$current_month."' ORDER BY day";
	

	$riResult = $db->getAll($query);
	
	if (count($riResult) > 0) {
		$json = '{"data":[';
		foreach ($riResult as $ri) {
			$json .= '{"day":"'.$ri['day'].'","ri_value":"'.$ri['ri_capture_duration'].'"},';
		}
		
		// Remove the last comma
		$json = substr($json, 0, -1);
		echo $json.']}';
	}
	else{
		echo '{"data":[{"day":"'.$current_month.'01","ri_value":"0","no_value":"1"}]}';
	}

	
}

function getAmmapIds(){
	$sdp_id = (empty($_POST['sdp_id']) ? 1 : $_POST['sdp_id']);
	$selected_level =(empty($_POST['selected_level']) ? 1 : $_POST['selected_level']);
	$db = Database::getConnection($sdp_id);
	
	$query = "SELECT eor_id,eor_label FROM edw_object_ref where eor_obj_type = '".$selected_level."' order by eor_id;";
	$module = $db->getOne($query);
	
	$ammapResults = $db->getAll($query);
	
	if (count($ammapResults) > 0) {
		$json = '{"data":[';
		foreach ($ammapResults as $ammapInfo) {
			$json .= '{"mapId":"'.$ammapInfo['eor_id'].'","mapLabel":"'.$ammapInfo['eor_label'].'"},';
		}
		
		// Remove the last comma
		$json = substr($json, 0, -1);
		echo $json.']}';
	}
	else{
		echo '{"data":[{"mapId":"no_data","mapLabel":"no_data"}]}';
	}

	
}
function getTemplates() {
	global $database_connection;

	$query = 'SELECT id_template, label FROM  sys_templates_list WHERE visible=1 ORDER BY label';
	$templates = $database_connection->getAll($query);

	$json = '{"template":[';
	foreach ($templates as $t) {
		$json .= '{"id":"'.$t['id_template'].'","label":"'.$t['label'].'"},';
	}

	// Remove the last comma
	if (count($templates) > 0) {
		$json = substr($json, 0, -1);
	}

	echo $json.']}';
}

function getProducts () {
	global $database_connection;

	$query = 'SELECT sdp_id, sdp_label FROM sys_definition_product';
	$products = $database_connection->getAll($query);

	$json = '{"product":[';
	foreach ($products as $t) {
		$json .= '{"id":"'.$t['sdp_id'].'","label":"'.$t['sdp_label'].'"},';
	}

	// Remove the last comma
	if (count($products) > 0) {
		$json = substr($json, 0, -1);
	}

	echo $json.']}';
}

function getReportUploadTreeNode() {
	
// grab the custom params
$path = isset($_REQUEST['path'])&&$_REQUEST['path'] == 'repository' ? '../' : '../../';

$node = isset($_REQUEST['node']) ? $_REQUEST['node'] : '';
$isXml = isset($_REQUEST['isXml']);

if(strpos($node, '..') !== false){
    die('Error.');
}

$nodes = array();
$directory = $path.$node;
if (is_dir($directory)){
    $d = dir($directory);
    while($f = $d->read()){
        if($f == '.' || $f == '..' || substr($f, 0, 1) == '.') continue;

        $filename = $directory . '/' . $f;
        date_default_timezone_set('Europe/Paris');
        $lastmod = date('j M, Y, g:i a', filemtime($filename));

        if(is_dir($directory.'/'.$f)){
            $qtip = 'Type: Folder<br />Last Modified: '.$lastmod;
            $nodes[] = array(
                'text' => $f,
                'id'   => $node.'/'.$f,
                'cls'  => 'folder'
            );
        } else {
            $size = formatBytes(filesize($filename), 2);
            $qtip = 'Type: JavaScript File<br />Last Modified: '.$lastmod.'<br />Size: '.$size;
            $nodes[] = array(
                'text' => $f,
                'id'   => $node.'/'.$f,
                'leaf' => true,
                'cls'  => 'file'
            );
        }
    }
    $d->close();
}

if ($isXml) {
    $xmlDoc = new DOMDocument();
    $root = $xmlDoc->appendChild($xmlDoc->createElement("nodes"));
    foreach ($nodes as $node) {
        $xmlNode = $root->appendChild($xmlDoc->createElement("node"));
        $xmlNode->appendChild($xmlDoc->createElement("text", $node['text']));
        $xmlNode->appendChild($xmlDoc->createElement("id", $node['id']));
        $xmlNode->appendChild($xmlDoc->createElement("cls", $node['cls']));
        $xmlNode->appendChild($xmlDoc->createElement("leaf", isset($node['leaf'])));
    }
    header("Content-Type: text/xml");
    $xmlDoc->formatOutput = true;
    return $xmlDoc->saveXml();
} else {
    return json_encode($nodes);
}
	
}

function getDashboards () {
	$connection = new DataBaseConnection($_POST['product']);
	
	$query = 'SELECT sdd_id_page, page_name FROM sys_definition_dashboard, sys_pauto_page_name WHERE sdd_id_page=id_page';
	$dashboards = $connection->getAll($query);

	$json = '{"dashboard":[';
	foreach ($dashboards as $d) {
		$json .= '{"id":"'.$d['sdd_id_page'].'","label":"'.$d['page_name'].'"},';
	}

	// Remove the last comma
	if (count($dashboards) > 0) {
		$json = substr($json, 0, -1);
	}

	echo $json.']}';
}

function getDashboardsAll () {
	$connection = new DataBaseConnection(1);
	
	$query = 'SELECT sdp_id, sdp_label FROM sys_definition_product ORDER BY sdp_id';
	$products = $connection->getAll($query);
	
	$json = '{"dashboard":[';
	
	$cpt_dash=0;
	
	foreach($products as $p){
		$connection = new DataBaseConnection($p['sdp_id']);
		$query = 'SELECT sdd_id_page, page_name FROM sys_definition_dashboard, sys_pauto_page_name WHERE sdd_id_page=id_page';
		$dashboards = $connection->getAll($query);
		foreach ($dashboards as $d) {
			$cpt_dash++;
			$len=strlen($d['page_name'])+strlen($p['sdp_label'])+3;
			$json .= '{"id":"'.$d['sdd_id_page'].'","label":"'.$d['page_name'].'","sdp_id":"'.$p['sdp_id'].'","sdp_label":"'.$p['sdp_label'].'","display":"'.$d['page_name'].' - '.$p['sdp_label'].'","size":"'.$len.'"},';
		}	
	}
	
	// Remove the last comma
	if ($cpt_dash > 0) {
		$json = substr($json, 0, -1);
	}
	
	echo $json.']}';
}


function getAlarms () {
	$connection = new DataBaseConnection($_POST['product']);
	
	$query = 'SELECT alarm_id, alarm_name FROM sys_definition_alarm_static GROUP BY alarm_id,alarm_name';
	$alarms = $connection->getAll($query);

	$json = '{"alarm":[';
	foreach ($alarms as $a) {
		$json .= '{"id":"'.$a['alarm_id'].'","label":"'.$a['alarm_name'].'","grid_name":"","comment":"","dashboard":""},';
	}

	// Remove the last comma
	if (count($alarms) > 0) {
		$json = substr($json, 0, -1);
	}

	echo $json.']}';
}

function getMapConf() {
	if (isset($_POST['tab'])) {
		$tab = substr($_POST['tab'], strrpos($_POST['tab'], '_') + 1);

		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
		}

		// Load the configuration file
		$dom = new DOMDocument();
		$dom->load($file);
		$xpath = new DOMXpath($dom);

		// Get the groups node
		$groupNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/widgets/widget[@id="chart1"]/kpi_groups/group');
	

		$resarray=array();
		
		foreach($groupNodeList as $group){
			
			$temptab=array();
			
			$group_name=$group->getElementsByTagName('group_name');
    		$groupname = $group_name->item(0)->nodeValue;
    		$temptab['groupname']=$groupname;
    		
    		if(empty($groupname)){
    			echo json_encode($resarray);
    			return;
    		}

			$kpis=$group->getElementsByTagName('kpis');

			foreach($kpis as $kpi){

				//trend kpi
				$kpi_trend=$kpi->getElementsByTagName('kpi_trend');
				
				$kpi_id_trend=$kpi_trend->item(0)->getElementsByTagName('kpi_id');
    			$kpi_id_trend = $kpi_id_trend->item(0)->nodeValue;
    			$temptab['trendkpiid']=$kpi_id_trend;
    			
    			$product_id_trend=$kpi_trend->item(0)->getElementsByTagName('product_id');
    			$product_id_trend = $product_id_trend->item(0)->nodeValue;
    			$temptab['trendkpiproductid']=$product_id_trend;
    			
    			$trendkpilabel=$kpi_trend->item(0)->getElementsByTagName('label');
    			$trendkpilabel = $trendkpilabel->item(0)->nodeValue;
    			$temptab['trendkpilabel']=$trendkpilabel;
    			
    			$trendkpifunction=$kpi_trend->item(0)->getElementsByTagName('function');
    			$trendkpifunction = $trendkpifunction->item(0)->nodeValue;
    			$temptab['trendkpifunction']=$trendkpifunction;
    			
    			$type_kpi=$kpi_trend->item(0)->getElementsByTagName('type');
    			$type_kpi = $type_kpi->item(0)->nodeValue;
    			$temptab['typekpi']=$type_kpi;

    			$trendkpiproductlabel=$kpi_trend->item(0)->getElementsByTagName('product_label');
    			$trendkpiproductlabel = $trendkpiproductlabel->item(0)->nodeValue;
    			$temptab['trendproductlabel']=$trendkpiproductlabel;                                
    			
    			//Roaming
    			$networkaxisnumber=$kpi_trend->item(0)->getElementsByTagName('network_axis_number');
    			$networkaxisnumber = $networkaxisnumber->item(0)->nodeValue;
    			$temptab['networkaxisnumber']=$networkaxisnumber;  
    			
    			$roamingnetworklevel=$kpi_trend->item(0)->getElementsByTagName('roaming_network_level');
    			$roamingnetworklevel = $roamingnetworklevel->item(0)->nodeValue;
    			$temptab['roamingnetworklevel']=$roamingnetworklevel;
    			
    			$roamingnetworklevel2=$kpi_trend->item(0)->getElementsByTagName('roaming_network_level2');
    			$roamingnetworklevel2 = $roamingnetworklevel2->item(0)->nodeValue;
    			$temptab['roamingnetworklevel2']=$networkaxisnumber;
    			
    			$roamingneid=$kpi_trend->item(0)->getElementsByTagName('roaming_ne_id');
    			$roamingneid = $roamingneid->item(0)->nodeValue;
    			$temptab['roamingneid']=$networkaxisnumber;  
    			
    			
    			$roamingneid2=$kpi_trend->item(0)->getElementsByTagName('roaming_ne_id2');
    			$roamingneid2 = $roamingneid2->item(0)->nodeValue;
    			$temptab['roamingneid2']=$networkaxisnumber;  
    			
    			
    			//Roaming parameters
    			$roamingaxisnumber=$kpi_trend->item(0)->getElementsByTagName('network_axis_number');
    			$roamingaxisnumber = $roamingaxisnumber->item(0)->nodeValue;
    			$temptab['roamingaxisnumber']=$roamingaxisnumber;
    			
    			$roamingnetworklevel=$kpi_trend->item(0)->getElementsByTagName('roaming_network_level');
    			$roamingnetworklevel = $roamingnetworklevel->item(0)->nodeValue;
    			$temptab['roamingnetworklevel']=$roamingnetworklevel;
    			
    			$roamingnetworklevel2=$kpi_trend->item(0)->getElementsByTagName('roaming_network_level2');
    			$roamingnetworklevel2 = $roamingnetworklevel2->item(0)->nodeValue;
    			$temptab['roamingnetworklevel2']=$roamingnetworklevel2;
    			
    			$roamingneid=$kpi_trend->item(0)->getElementsByTagName('roaming_ne_id');
    			$roamingneid = $roamingneid->item(0)->nodeValue;
    			$temptab['roamingneid']=$roamingneid;
    			
    			$roamingneid2=$kpi_trend->item(0)->getElementsByTagName('roaming_ne_id2');
    			$roamingneid2 = $roamingneid2->item(0)->nodeValue;
    			$temptab['roamingneid2']=$roamingneid2;
    			
    			//donut kpi
    			$kpi_donut=$kpi->getElementsByTagName('kpi_donut');
    			
    			$kpi_id_trend=$kpi_donut->item(0)->getElementsByTagName('kpi_id');
    			$kpi_id_trend = $kpi_id_trend->item(0)->nodeValue;
    			$temptab['donutkpiid']=$kpi_id_trend;
    			 
    			$product_id_trend=$kpi_donut->item(0)->getElementsByTagName('product_id');
    			$product_id_trend = $product_id_trend->item(0)->nodeValue;
    			$temptab['donutkpiproductid']=$product_id_trend;
    			 
    			$trendkpilabel=$kpi_donut->item(0)->getElementsByTagName('label');
    			$trendkpilabel = $trendkpilabel->item(0)->nodeValue;
    			$temptab['donutkpilabel']=$trendkpilabel;
    			
    			$type_donut=$kpi_donut->item(0)->getElementsByTagName('type');
    			$type_donut = $type_donut->item(0)->nodeValue;
    			$temptab['typekpidonut']=$type_donut;
    			 
    			$donutkpiproductlabel=$kpi_donut->item(0)->getElementsByTagName('product_label');
    			$donutkpiproductlabel = $donutkpiproductlabel->item(0)->nodeValue;
    			$temptab['donutproductlabel']=$donutkpiproductlabel;
    			
    			
    			$axis=$group->getElementsByTagName('axis_list');
    				
    			//trend
    			$axis_trend=$axis->item(0)->getElementsByTagName('axis_trend');
    				
    			$trend_unit=$axis_trend->item(0)->getElementsByTagName('unit')->item(0)->nodeValue;
    			$thresholds=$axis_trend->item(0)->getElementsByTagName('thresholds');
    			$low_threshold=$thresholds->item(0)->getElementsByTagName('low_threshold')->item(0)->nodeValue;
    			$high_threshold=$thresholds->item(0)->getElementsByTagName('high_threshold')->item(0)->nodeValue;
    				
    			$zoom=$axis_trend->item(0)->getElementsByTagName('zoom');
    			$dynamic=$zoom->item(0)->getElementsByTagName('dynamic')->item(0)->nodeValue;
    			$min_value=$zoom->item(0)->getElementsByTagName('min_value')->item(0)->nodeValue;
    			$max_value=$zoom->item(0)->getElementsByTagName('max_value')->item(0)->nodeValue;
    			
    			//donut
    			$axis_donut=$axis->item(0)->getElementsByTagName('axis_donut');
    			$donut_unit=$axis_donut->item(0)->getElementsByTagName('unit')->item(0)->nodeValue;
    				
    				
    			$temptab['trendunit']=$trend_unit;
    			$temptab['donutunit']=$donut_unit;
    			$temptab['lowthreshold']=$low_threshold;
    			$temptab['highthreshold']=$high_threshold;
    			$temptab['dynamic']=$dynamic;
    			$temptab['minvalue']=$min_value;
    			$temptab['maxvalue']=$max_value;

			}

			$resarray[]=$temptab;	
			
		}

		// Return a json representation
		//$nodeXml = $dom->saveXML($tabNodeList->item(0));
		//echo json_encode(simplexml_load_string($nodeXml));
		echo json_encode($resarray);
	}
}

function getTabs() {
	global $isAdmin;
	if ($isAdmin) {
		$file = '../config/default/homepage.xml';
	} else {
		$userId = $_SESSION['id_user'];
		$file = '../config/'.$userId.'/homepage.xml';
	}

	// Load the configuration file
	$dom = new DOMDocument();
	$dom->load($file);

	$xml = '<homepage>';

	// Get the tab nodes
	$xpath = new DOMXpath($dom);
	$tabNodeList = $xpath->query('/homepage/tab');
	if ($tabNodeList->length > 0) {
		for ($i = 0; $i < $tabNodeList->length; $i++) {
			// New DOM
			$dom2 = new DOMDocument();
			$dom2->load($file);
			$xpath2 = new DOMXpath($dom2);

			$xml .= '<tab><id>';
			$id = $tabNodeList->item($i)->getAttribute('id');
			$xml .= $id;
			$xml .= '</id><title>';
			$titleNodeList = $xpath2->query('/homepage/tab[@id="'.$id.'"]/title');
			$xml .= normalize_str($titleNodeList->item(0)->nodeValue);
			$xml .= '</title><selected>';
			$isDefault = $tabNodeList->item($i)->getAttribute('isDefault');
			if($i==0)$isDefault="false";
			$xml .= $isDefault;
			$xml .= '</selected><nbWidgets>';
			$widgetNodeList = $xpath2->query('/homepage/tab[@id="'.$id.'"]/widgets/widget');
			$xml .= $widgetNodeList->length;
			$xml .= '</nbWidgets>';
			$templateNodeList = $xpath2->query('/homepage/tab[@id="'.$id.'"]/template');
			$templateFile = '../config/templates/'.$templateNodeList->item(0)->nodeValue.'.xml';

			// Load the template file
			$dom2->load($templateFile);
			$xpath2 = new DOMXpath($dom2);
			$templateList = $xpath2->query('/template');
			$templateXml = $dom2->saveXML($templateList->item(0));

			$xml .= $templateXml;			
			$xml .= '</tab>';
		}
	}
	
	$xml .= '</homepage>';

	// Return a json representation
	echo json_encode(simplexml_load_string($xml));
}

function save() {
	if (isset($_POST['tab']) &&
	isset($_POST['chart']) &&
	isset($_POST['xml']) &&
	isset($_POST['xml2'])) {
		
		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
		}
		
		// Make a backup
		$backup = '../files/'.date('Y').date('m').date('d').date('H').date('i').'_'.$_SESSION['id_user'].'_'.$_POST['tab'].'_'.$_POST['chart'].'_'.rand().'.xml';
		copy($file, $backup);
		
		//tmp file during save
		$file_tmp=$file."_".date('Y').date('m').date('d').date('H').date('i').".tmp";
		copy($file, $file_tmp);
		
		$tab = $_POST['tab'];
		$chart = $_POST['chart'];
		$xml = stripslashes($_POST['xml']);
		$xml2 = stripslashes($_POST['xml2']);

		//replace special chars
		preg_replace('/&(?!#|lt;|gt;|&amp;|&quot;|&apos)/', " ", $xml);
		preg_replace('/&(?!#|lt;|gt;|&amp;|&quot;|&apos)/', " ", $xml2);
		//$to_replace=array("&");
		//$xml=str_replace($to_replace," ",$xml);
		//$xml2=str_replace($to_replace," ",$xml2);
		
		
		// Load the configuration file
		$dom = new DOMDocument();		
		$dom->load($file_tmp);
		

		// Get the chart node
		$xpath = new DOMXpath($dom);
		$chartNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/widgets/widget[@id="'.$chart.'"]');
			
		// Replace the configuration
		$newNode = $dom->createDocumentFragment();
		$newNode->appendXML($xml);
		$chartNodeList->item(0)->parentNode->replaceChild($newNode, $chartNodeList->item(0));

		// Get the trend chart node
		$xpath = new DOMXpath($dom);
		$chartNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/widgets/widget[@id="'.$chart.'_trend"]');

		// Append the new configuration
		$newNode = $dom->createDocumentFragment();
		$newNode->appendXML($xml2);
		$chartNodeList->item(0)->parentNode->replaceChild($newNode, $chartNodeList->item(0));

		// Change the tab title
		if (isset($_POST['title'])) {
			$titleNode = $dom->createDocumentFragment();
			$titleNode->appendXML('<title>'.$_POST['title'].'</title>');
				
			$xpath = new DOMXpath($dom);
			$titleNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/title');
			$titleNodeList->item(0)->parentNode->replaceChild($titleNode, $titleNodeList->item(0));
		}

		// Change the tab template
		if (isset($_POST['template'])) {			
			$templateNode = $dom->createDocumentFragment();
			$templateNode->appendXML('<template>'.$_POST['template'].'</template>');
				
			$xpath = new DOMXpath($dom);
			$templateNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/template');
			$templateNodeList->item(0)->parentNode->replaceChild($templateNode, $templateNodeList->item(0));
		}

		// Change the default tab
		if (!empty($_POST['selected'])) {	
			$tabNodeList = $xpath->query('/homepage/tab');
			for ($i = 0; $i < $tabNodeList->length; $i++) {
				if(($tabNodeList->item($i)->getAttribute('id') == $tab) &&
					($_POST['selected'] == 'true')) {
					$tabNodeList->item($i)->setAttribute('isDefault', 'true');
				} else {
					$tabNodeList->item($i)->setAttribute('isDefault', 'false');
				}
			}
		}
		
		// Change the seletected mode for penalization
		if (isset($_POST['selectedmode']) && $_POST['selectedmode'] != '') {	
			$modeNodeList = $xpath->query('/homepage/selectedmode');
			if ($modeNodeList->length > 0) {
				// Replace the node value
				$modeNodeList->item(0)->nodeValue = $_POST['selectedmode'];
			} else {
				// Create a new node
				$modeNode = $dom->createElement('selectedmode', $_POST['selectedmode']);				
				$homepageNodeList = $xpath->query('/homepage');
				$homepageNodeList->item(0)->appendChild($modeNode);				
			}
		}
		
		// Change the ratio
		if (isset($_POST['ratio']) &&
			$_POST['ratio'] != '') {	
			$ratioNodeList = $xpath->query('/homepage/ratioforpenalization');
		
			if ($ratioNodeList->length > 0) {
				// Replace the node value
				$ratioNodeList->item(0)->nodeValue = $_POST['ratio'];
			} else {
				// Create a new node
				$ratioNode = $dom->createElement('ratioforpenalization', $_POST['ratio']);				
				$homepageNodeList = $xpath->query('/homepage');
				$homepageNodeList->item(0)->appendChild($ratioNode);				
			}
		}
		
		// Change the nb days for penalisation
		if (isset($_POST['nbdays']) &&
		$_POST['nbdays'] != '') {
			$nbdaysNodeList = $xpath->query('/homepage/numberofdaysforpenalization');
		
			if ($nbdaysNodeList->length > 0) {
				// Replace the node value
				$nbdaysNodeList->item(0)->nodeValue = $_POST['nbdays'];
			} else {
				// Create a new node
				$nbdaysNode = $dom->createElement('numberofdaysforpenalization', $_POST['nbdays']);
				$homepageNodeList = $xpath->query('/homepage');
				$homepageNodeList->item(0)->appendChild($nbdaysNode);
			}
		}
		
		
		
		// String returned
		$return = 'success';
		
		// If the style changed, we have to reload the homepage
		if (isset($_POST['style']) &&
			$_POST['style'] != '') {	
			$styleNodeList = $xpath->query('/homepage/style');
			if ($styleNodeList->length > 0) {
				// Replace the node value
				$styleNodeList->item(0)->nodeValue = $_POST['style'];
			} else {
				// Create a new node
				$styleNode = $dom->createElement('style', $_POST['style']);				
				$homepageNodeList = $xpath->query('/homepage');
				$homepageNodeList->item(0)->appendChild($styleNode);				
			}
			
			$return = 'reload';			
		} 
		
		// If the index changed, we have to reload the homepage too
		if (isset($_POST['index']) &&
			$_POST['index'] != '') {	

			$index = $_POST['index'];
			$tabNodeList = $xpath->query('/homepage/tab');
				
			// Get the source tab
			$sourceTab = $xpath->query('/homepage/tab[@id="'.$tab.'"]');	
					
			// Create a new node
			$rawXml = $dom->saveXML($sourceTab->item(0));		
			$newNode = $dom->createDocumentFragment();
			$newNode->appendXML($rawXml);
			
			if ($index == $tabNodeList->length) {
				// Add to the end
				$homepageNodeList = $xpath->query('/homepage');
				$homepageNodeList->item(0)->appendChild($newNode);				
			} else {
				// Add before a tab				
				
				//get current tab index
				$ind=1;

				for ($i = 0; $i < $tabNodeList->length; ++$i) {
       				if($tabNodeList->item($i)->getAttribute('id')!==$tab)$ind++;
						else{
							break;
					}
    			}
				//if new index is smaller than current, index -1 else index
				if($index < $ind)$index-=1;
				$nextTab = $tabNodeList->item($index);
				$nextTab->parentNode->insertBefore($newNode, $nextTab);								
			}
				
			// Remove the previous tab node
			$sourceTab->item(0)->parentNode->removeChild($sourceTab->item(0));
			
			$return = 'reload';			
		}
		
		// Save the new configuration
		$dom->formatOutput = true;
		$dom->save($file_tmp);
		
		copy($file_tmp,$file);
		unlink($file_tmp);

		echo $return;
	}
}

function save_template() {
	if (isset($_POST['tab']) &&
	isset($_POST['template'])) {

		$tab = $_POST['tab'];
		$template = $_POST['template'];
			
		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
			$gaugeRep = '../config/default/gauges/'.$tab;
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
			$gaugeRep = '../config/'.$userId.'/gauges/'.$tab;
		}

		// Load the template file
		$templateFile = '../config/templates/'.$template.'.xml';
	  
		$dom = new DOMDocument();
		$dom->load($templateFile);

		$xpath = new DOMXpath($dom);
		$chartList = $xpath->query('/template/row/widget[@type="chart"]');
		$nbCharts = $chartList->length;
		$frameList = $xpath->query('/template/row/widget[@type="frame"]');
		$nbFrames = $frameList->length;
		$mapList = $xpath->query('/template/row/widget[@type="map"]');
		$nbMaps = $mapList->length;
		$gridList = $xpath->query('/template/row/widget[@type="grid"]');
		$nbGrids = $gridList->length;
		$gridArrayList = $xpath->query('/template/row/widget[@type="gridarray"]');
		$nbGridArrays = $gridArrayList->length;
		$graphPanelList = $xpath->query('/template/row/widget[@type="graphpanel"]');
		$nbgraphPanel = $graphPanelList->length;
		

		// Load the configuration file
		$dom->load($file);

		$xpath = new DOMXpath($dom);
		$tabNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]');
		$titleList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/title');

		// Replace the tab in the configuration file
		$xmlNode = '<tab id="'.$tab.'"><title>'.$titleList->item(0)->nodeValue.'</title><template>'.$template.'</template><widgets>';
		for ($i = 1; $i <= $nbCharts; $i++) {
			$xmlNode .= '<widget id="chart'.$i.'"><title>New chart '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New chart '.$i.'</title><function>trend</function></widget>';
		}
		for ($i = 1; $i <= $nbFrames; $i++) {
			$xmlNode .= '<widget id="chart'.$i.'"><title>New frame '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New frame '.$i.'</title><function>trend</function></widget>';
		}
		for ($i = 1; $i <= $nbMaps; $i++) {
			$xmlNode .= '<widget id="chart'.$i.'"><title>New map '.$i.'</title><function>detail</function><map_zoom><zoom_level></zoom_level><zoom_latitude></zoom_latitude><zoom_longitude></zoom_longitude></map_zoom><home_zoom><home_zoom_level></home_zoom_level><home_zoom_latitude></home_zoom_latitude><home_zoom_longitude></home_zoom_longitude></home_zoom><network_elements><network_level></network_level><network_level2></network_level2><parent_level_selected></parent_level_selected><network_element><ne_id></ne_id><ne_id2></ne_id2><product_id></product_id><map_zone_id></map_zone_id></network_element></network_elements><kpi_groups><group></group></kpi_groups></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New map '.$i.'</title><function>trend</function></widget>';
		}
		for ($i = 1; $i <= $nbGrids; $i++) {
			$xmlNode .= '<widget id="chart'.$i.'"><title>New grid '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New grid '.$i.'</title><function>trend</function></widget>';
		}
		for ($i = 1; $i <= $nbGridArrays; $i++) {
			$xmlNode .= '<widget id="chart'.$i.'"><title>New grid '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New grid '.$i.'</title><function>trend</function></widget>';
		}
		for ($i = 1; $i <= $nbgraphPanel; $i++) {
			$xmlNode .= '<widget id="chart'.$i.'"><title>New graph '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New graph '.$i.'</title><function>trend</function></widget>';
		}
		$xmlNode .= '</widgets></tab>';

		$newNode = $dom->createDocumentFragment();
		$newNode->appendXML($xmlNode);
		$tabNodeList->item(0)->parentNode->replaceChild($newNode, $tabNodeList->item(0));

		// Save the new configuration
		$dom->formatOutput = true;
		$dom->save($file);

		// Delete the gauges files repository
		echo recursiveDelete($gaugeRep);
		
		$oldmask = umask(0);	

		// Create the gauges repository
		mkdir($gaugeRep, 0777);
	  
		// Create the gauges config files
		for ($i = 1; $i <= $nbCharts; $i++) {
			$destFile = $gaugeRep.'/chart'.$i.'.xml';
			copy($gaugeRep.'/../template.xml', $destFile);
			chmod($destFile, 0777);
		}
		umask($oldmask);
		echo 'success';
	}
}

function resetConfiguration() {
	global $isAdmin;
	if (!$isAdmin) {
		$userId = $_SESSION['id_user'];
		$dir = '../config/'.$userId;

		// Delete the user directory
		recursiveDelete($dir);

		// Initialize the user directory
		initialize();
	}
}
function load() {
	if (isset($_POST['tab'])) {
		$tab = substr($_POST['tab'], strrpos($_POST['tab'], '_') + 1);

		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
		}

		// Load the configuration file
		$dom = new DOMDocument();
		$dom->load($file);

		// Get the style		
		$xpath = new DOMXpath($dom);
		$styleNodeList = $xpath->query('/homepage/style');
		if (($styleNodeList->length > 0) &&
			($styleNodeList->item(0)->nodeValue == 'access')) {
			$style = 'access';
		} else {
			$style = 'classic';
		}
		
		//Get the selected penalization mode
		$selectedModeNode = $xpath->query('/homepage/selectedmode');
		if ($selectedModeNode->length > 0) {
			$selectedMode = $selectedModeNode->item(0)->nodeValue;
		} else {
			$selectedMode = 2;
		}
		
		// Get the ratio for penalisation (template cell surveillance)
		$ratioNodeList = $xpath->query('/homepage/ratioforpenalization');
		if ($ratioNodeList->length > 0) {
			$ratio = $ratioNodeList->item(0)->nodeValue;
		} else {
			$ratio = 0.5;
		}
		
		// Get the ratio for penalisation (template cell surveillance & audit report)
		$nbdaysNodeList = $xpath->query('/homepage/numberofdaysforpenalization');
		if ($nbdaysNodeList->length > 0) {
			$nbdays = $nbdaysNodeList->item(0)->nodeValue;
		} else {
			$nbdays = 5;
		}
		
		// Get the tab node
		$tabNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]');

		// Add the attributes
		$tabNodeList->item(0)->setAttribute('style', $style);
		$tabNodeList->item(0)->setAttribute('selectedmode', $selectedMode);
		$tabNodeList->item(0)->setAttribute('ratio', $ratio);
		$tabNodeList->item(0)->setAttribute('nbdays', $nbdays);
		
				
		// Return a json representation
		$nodeXml = $dom->saveXML($tabNodeList->item(0));
		echo json_encode(simplexml_load_string($nodeXml));
	}
}

function loadTrend() {
	if (isset($_POST['tab']) &&
	isset($_POST['chart'])) {
		$tab = substr($_POST['tab'], strrpos($_POST['tab'], '_') + 1);
		$chart = substr($_POST['chart'], strrpos($_POST['chart'], '_') + 1);

		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
		}

		// Load the configuration file
		$dom = new DOMDocument();
		$dom->load($file);

		// Get the trend id
		$xpath = new DOMXpath($dom);
		$tabNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/widgets/widget[@id="'.$chart.'"]/widget_links');

		if ($tabNodeList->length > 0) {
			$trendId = $tabNodeList->item(0)->firstChild->wholeText;
			 
			// Get the chart node
			$tabNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]/widgets/widget[@id="'.$trendId.'"]');

			// Return a json representation
			$nodeXml = $dom->saveXML($tabNodeList->item(0));
			echo json_encode(simplexml_load_string($nodeXml));
		}
	}
}

function makeGaugeConfig() {
	if (isset($_POST['tab']) &&
	isset($_POST['chart']) &&
	isset($_POST['value']) &&
	isset($_POST['gaugemin']) &&
	isset($_POST['gaugemax'])) {
	$log = '../tmp/config_gauges.log';
	$header = "---------ERROR LOG DURING GAUGES CONFIGURATION----------\r\n";
	if (is_dir('../tmp')){
		if (!file_exists($log)){
			file_put_contents($log,$header);
		}
	}else {
		$oldmask = umask(0);
		mkdir("../tmp", 0777);
		umask($oldmask);
		file_put_contents($log,$header);
		//file_put_contents($log,$additional_content, FILE_APPEND);
	}
	
		$tab = substr($_POST['tab'], strrpos($_POST['tab'], '_') + 1);
		$chart = $_POST['chart'];
		$value = $_POST['value'];
		$gaugeMin = $_POST['gaugemin'];
		$gaugeMax = $_POST['gaugemax'];
		$alertMin = isset($_POST['alertmin']) ? $_POST['alertmin'] : 0;
		$alertMax = isset($_POST['alertmax']) ? $_POST['alertmax'] : 0;
		$warningMin = isset($_POST['warningmin']) ? $_POST['warningmin'] : 0;
		$warningMax = isset($_POST['warningmax']) ? $_POST['warningmax'] : 0;
		$okMin = isset($_POST['okmin']) ? $_POST['okmin'] : 0;
		$okMax = isset($_POST['okmax']) ? $_POST['okmax'] : 0;

		global $isAdmin;
		if ($isAdmin) {
			$file = 'config/default/gauges/'.$tab.'/'.$chart.'.xml';
		} else {
			$userId = $_SESSION['id_user'];
			$file = 'config/'.$userId.'/gauges/'.$tab.'/'.$chart.'.xml';
		}
		if ($userId){
			$current_user = $userId;
		}else{
			$current_user = "default";
		}
		// Load the gauge configuration file
		//$dom->load('../'.$file);
		$dom = new DOMDocument();
		try {
			if (!$dom->load('../'.$file)) {
			    $log_current_gauge = date('Y-m-d-H:i:s')." -> confiugration de la jauge : ".$file."\r\n";
				file_put_contents($log,$log_current_gauge, FILE_APPEND);
				$log_current_user = "UTILISATEUR = ".$current_user."\r\n";
				file_put_contents($log,$log_current_user, FILE_APPEND);
				$error_loading = "ERREUR - Un probleme est survenu lors du chargement du fichier configuration\r\n";
			    file_put_contents($log,$error_loading, FILE_APPEND);
				throw new XMLParseErrorException('../'.$file);
			}
		} catch (Exception $e) {
			$error_loading =  $e->getMessage();
			$error_loading .= " in ".$e->getFile(). " on line " .$e->getLine();
			$error_loading .= "\r\n";
		 	file_put_contents($log,$error_loading, FILE_APPEND);		  
		}
		
		
		// Xpath used to parse the file
		//$xpath = new DOMXpath($dom);
		try {
			if (!$xpath = new DOMXpath($dom)) {
			    $error_parsing = "ERREUR - Un probleme est survenu lors du parsing du fichier de configuration avec DOMXpath\r\n";
			    file_put_contents($log,$error_parsing, FILE_APPEND);
			}
		} catch (Exception $e) {
		 	$error_parsing = $e->getMessage()."\r\n";
			file_put_contents($log,$error_parsing, FILE_APPEND);
		}
		
		// Change the limits
		$tabNodeList = $xpath->query('/Gauge2/Gauge2RadialRange/Gauge2RadialScale');
		if ($tabNodeList->length > 0) {
			for ($i = 0; $i < $tabNodeList->length; $i++) {
				$tabNodeList->item($i)->setAttribute('startValue', $gaugeMin);
				$tabNodeList->item($i)->setAttribute('endValue', $gaugeMax);
			}
		}else{
			$error_node1 = "Warning - Le noeud est vide \r\n";
			file_put_contents($log,$update_node1, FILE_APPEND);
		}
		
		$tabNodeList = $xpath->query('/Gauge2/Gauge2RadialRange/Gauge2RadialScale/Gauge2RadialScaleSection');
		if ($tabNodeList->length >= 4) {
			$tabNodeList->item(3)->setAttribute('startValue', $gaugeMin);
			$tabNodeList->item(3)->setAttribute('endValue', $gaugeMax);
		}else{
			$error_node2 = "tabNodeList < 4\r\n";
			file_put_contents($log,$error_node2, FILE_APPEND);
		}

		// Change the value
		$tabNodeList = $xpath->query('/Gauge2/Gauge2RadialRange/Gauge2RadialScale/Gauge2RadialNeedle');
		if ($tabNodeList->length > 0) {
			$tabNodeList->item(0)->setAttribute('value', $value);
		}else{
			$gauge_value = "gauge has no value \r\n";
			file_put_contents($log,$gauge_value, FILE_APPEND);
		}

		// Change the thresholds
		$tabNodeList = $xpath->query('/Gauge2/Gauge2RadialRange/Gauge2RadialScale/Gauge2RadialScaleSection');
		if ($tabNodeList->length >= 3) {
			// Alert area
			$tabNodeList->item(0)->setAttribute('startValue', $alertMin);
			$tabNodeList->item(0)->setAttribute('endValue', $alertMax);
			// Warning area
			$tabNodeList->item(1)->setAttribute('startValue', $warningMin);
			$tabNodeList->item(1)->setAttribute('endValue', $warningMax);
			// OK area
			$tabNodeList->item(2)->setAttribute('startValue', $okMin);
			$tabNodeList->item(2)->setAttribute('endValue', $okMax);
		}
		
		// Save the modifications
		//$dom->save('../'.$file);
		try {
			if (!$dom->save('../'.$file)) {
			    $save_current_gauge = date('Y-m-d-H:i:s')." -> sauvegarde de la jauge : ".$file."\r\n";
				file_put_contents($log,$save_current_gauge, FILE_APPEND);
				$error_save = "ERREUR - Un probleme est survenu lors de la sauvegarde du fichier de configuration\r\n";
			    file_put_contents($log,$error_save, FILE_APPEND);
			    throw new XMLParseErrorException('../'.$file);
			}
		} catch (Exception $e) {
			$error_save =  $e->getMessage();
			$error_save .= " in ".$e->getFile(). " on line " .$e->getLine();
			$error_save .= "\r\n";
		 	file_put_contents($log,$error_save, FILE_APPEND);
		}
		
		if(filesize ('../'.$file) < 50){
			$size_error = "ERREUR - La taille du fichier de configuration est de ".filesize ('../'.$file)." bytes\r\n";
			file_put_contents($log,$size_error, FILE_APPEND);
		}
		echo $file.'?'.rand(0, 10000);
	}
}

function addTab() {
	global $isAdmin;
	if ($isAdmin) {
		$file = '../config/default/homepage.xml';
		$gaugeRep = '../config/default/gauges/';
	} else {
		$userId = $_SESSION['id_user'];
		$file = '../config/'.$userId.'/homepage.xml';
		$gaugeRep = '../config/'.$userId.'/gauges/';
	}
		
	// Load the configuration file
	$dom = new DOMDocument();
	$dom->load($file);

	// Xpath used to parse the file
	$xpath = new DOMXpath($dom);

	// New tab id
	$newId = 0;
	$tabNodeList = $xpath->query('/homepage/tab');
	if ($tabNodeList->length > 0) {
		for ($i = 0; $i < $tabNodeList->length; $i++) {
			$tabId = $tabNodeList->item($i)->getAttribute('id');
			$tabId = substr($tabId, 3);
			if (intval($tabId) > $newId) $newId = intval($tabId);
		}
	}
	$newId++;
	$newTabId = 'tab'.$newId;
	$oldmask = umask(0);	
	// Create the gauges repository
	if (!file_exists($gaugeRep.$newTabId)) mkdir($gaugeRep.$newTabId, 0777);	
	umask($oldmask);
	//get first template activated
	$db = Database::getConnection(1);
	
	$query = "SELECT id_template FROM sys_templates_list WHERE visible=1 ORDER BY id_template ASC LIMIT 1";
	
	$id_template = $db->getOne($query);
		
	// Add the new tab in the configuration file
	$xmlNode = '<tab id="'.$newTabId.'"><title>New tab</title><template>'.$id_template.'</template><widgets>';
	$oldmask = umask(0);
	switch($id_template){	
		case 'template1' : 
			for ($i = 1; $i <= 8; $i++) {
				$xmlNode .= '<widget id="chart'.$i.'"><title>New chart '.$i.'</title><function>detail</function></widget>';
				$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New chart '.$i.'</title><function>trend</function></widget>';
			};
			// Create the gauges config files
			for ($i = 1; $i <= 8; $i++) {
				$destFile = $gaugeRep.$newTabId.'/chart'.$i.'.xml';
				copy($gaugeRep.'template.xml', $destFile);
				chmod($destFile, 0777);
			}
			break;
		case 'template2' :
			for ($i = 1; $i <= 3; $i++) {
				$xmlNode .= '<widget id="chart'.$i.'"><title>New chart '.$i.'</title><function>detail</function></widget>';
				$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New chart '.$i.'</title><function>trend</function></widget>';
			};
			// Create the gauges config files
			for ($i = 1; $i <= 3; $i++) {
				$destFile = $gaugeRep.$newTabId.'/chart'.$i.'.xml';
				copy($gaugeRep.'template.xml', $destFile);
				chmod($destFile, 0777);
			}
			break;
		case 'template3' :
			for ($i = 1; $i <= 20; $i++) {
				$xmlNode .= '<widget id="chart'.$i.'"><title>New chart '.$i.'</title><function>detail</function></widget>';
				$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New chart '.$i.'</title><function>trend</function></widget>';
			};
			// Create the gauges config files
			for ($i = 1; $i <= 20; $i++) {
				$destFile = $gaugeRep.$newTabId.'/chart'.$i.'.xml';
				copy($gaugeRep.'template.xml', $destFile);
				chmod($destFile, 0777);
			}
			break;
		case 'template4' :
			$i=1;
			$xmlNode .= '<widget id="chart'.$i.'"><title>New frame '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New frame '.$i.'</title><function>trend</function></widget>';
			break;
		case 'template5'	:
			$i=1;
			$xmlNode .= '<widget id="chart'.$i.'"><title>New map '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New map '.$i.'</title><function>trend</function></widget>';
			break;
		case 'template6' :		
		case 'template7' :
			$i=1;
			$xmlNode .= '<widget id="chart'.$i.'"><title>New grid '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New grid '.$i.'</title><function>trend</function></widget>';
			break;
		case 'template8' :
			for ($i = 1; $i <= 6; $i++) {
				$xmlNode .= '<widget id="chart'.$i.'"><title>New chart '.$i.'</title><function>detail</function></widget>';
				$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New chart '.$i.'</title><function>trend</function></widget>';
			};
			// Create the gauges config files
			for ($i = 1; $i <= 6; $i++) {
				$destFile = $gaugeRep.$newTabId.'/chart'.$i.'.xml';
				copy($gaugeRep.'template.xml', $destFile);
				chmod($destFile, 0777);
			}
			break;
		case 'template9' :
			$i=1;
			$xmlNode .= '<widget id="chart'.$i.'"><title>New grid '.$i.'</title><function>detail</function></widget>';
			$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New grid '.$i.'</title><function>trend</function></widget>';
		break;
		default : 
			for ($i = 1; $i <= 8; $i++) {
				$xmlNode .= '<widget id="chart'.$i.'"><title>New chart '.$i.'</title><function>detail</function></widget>';
				$xmlNode .= '<widget id="chart'.$i.'_trend"><title>New chart '.$i.'</title><function>trend</function></widget>';
			};
			// Create the gauges config files
			for ($i = 1; $i <= 8; $i++) {
				$destFile = $gaugeRep.$newTabId.'/chart'.$i.'.xml';
				copy($gaugeRep.'template.xml', $destFile);
				chmod($destFile, 0777);
			}
			break;
	}
	umask($oldmask);
	
	
	$xmlNode .= '</widgets></tab>';
	 
	$newNode = $dom->createDocumentFragment();
	$newNode->appendXML($xmlNode);
	$homepageNodeList = $xpath->query('/homepage');
	$homepageNodeList->item(0)->appendChild($newNode);

	// Save the new configuration
	$dom->formatOutput = true;
	$dom->save($file);

	// Make the return xml string
	$xmlNode = '<tab><id>'.$newTabId.'</id><title>New tab</title>';
	 
	$templateFile = '../config/templates/'.$id_template.'.xml';

	// Load the template file
	$dom->load($templateFile);
	$xpath = new DOMXpath($dom);
	$templateList = $xpath->query('/template');
	$templateXml = $dom->saveXML($templateList->item(0));

	$xmlNode .= $templateXml;
	$xmlNode .= '</tab>';
	 
	echo json_encode(simplexml_load_string($xmlNode));
}

function copyTab() {
	if (isset($_POST['tab'])) {
		$srcTabId = $_POST['tab'];
		
		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
			$gaugeRep = '../config/default/gauges/';
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
			$gaugeRep = '../config/'.$userId.'/gauges/';
		}
		
		// Load the configuration file
		$dom = new DOMDocument();
		$dom->load($file);
	
		// Xpath used to parse the file
		$xpath = new DOMXpath($dom);
	
		// New tab id
		$newId = 0;
		$tabNodeList = $xpath->query('/homepage/tab');
		if ($tabNodeList->length > 0) {
			for ($i = 0; $i < $tabNodeList->length; $i++) {
				$tabId = $tabNodeList->item($i)->getAttribute('id');
				$tabId = substr($tabId, 3);
				if (intval($tabId) > $newId) $newId = intval($tabId);
			}
		}
		$newId++;
		$destTabId = 'tab'.$newId;
		
		// Copy the gauge repository
		recursiveCopy($gaugeRep.$srcTabId, $gaugeRep.$destTabId);
		
		// Get the source tab
		$tabNodeList = $xpath->query('/homepage/tab[@id="'.$srcTabId.'"]');	
				
		// Create a new node
		$rawXml = $dom->saveXML($tabNodeList->item(0));		
		$newNode = $dom->createDocumentFragment();
		$newNode->appendXML($rawXml);
		
		// Append the new tab in the configuration
		$homepageNodeList = $xpath->query('/homepage');
		$homepageNodeList->item(0)->appendChild($newNode);
			
		// Get the destination tab
		$tabNodeList = $xpath->query('/homepage/tab[@id="'.$srcTabId.'"]');
		
		// Change the values
		$tabNodeList->item(1)->setAttribute('id', $destTabId);
		$tabNodeList->item(1)->setAttribute('isDefault', 'false');
				
		// Save the new configuration
		$dom->formatOutput = true;
		$dom->save($file);

		// Get the template
		$templateNodeList = $xpath->query('/homepage/tab[@id="'.$srcTabId.'"]/template');
		$templateFile = '../config/templates/'.$templateNodeList->item(0)->nodeValue.'.xml';
		
		// Make the return xml string
		$xmlNode = '<tab><id>'.$destTabId.'</id><title>New tab</title>';
	
		// Load the template file
		$dom->load($templateFile);
		$xpath = new DOMXpath($dom);
		$templateList = $xpath->query('/template');
		$templateXml = $dom->saveXML($templateList->item(0));
	
		$xmlNode .= $templateXml;
		$xmlNode .= '</tab>';
		 
		echo json_encode(simplexml_load_string($xmlNode));
	}	
}

function deleteTab() {
	if (isset($_POST['tab'])) {
		$tab = substr($_POST['tab'], strrpos($_POST['tab'], '_') + 1);

		global $isAdmin;
		if ($isAdmin) {
			$file = '../config/default/homepage.xml';
			$gaugeRep = '../config/default/gauges/'.$tab;
		} else {
			$userId = $_SESSION['id_user'];
			$file = '../config/'.$userId.'/homepage.xml';
			$gaugeRep = '../config/'.$userId.'/gauges/'.$tab;
		}

		// Load the configuration file
		$dom = new DOMDocument();
		$dom->load($file);

		$xpath = new DOMXpath($dom);
		$tabNodeList = $xpath->query('/homepage/tab[@id="'.$tab.'"]');

		//get the default tab
		$tabNodeList2 = $xpath->query('/homepage/tab[@isDefault = "true"]');
		if($tabNodeList2->length>0){
			$defaultTab=$tabNodeList2->item(0)->getAttribute('id');
		}
		else{
			$defaultTab="tab1";
		}
		
		// Remove the tab
		$tabNodeList->item(0)->parentNode->removeChild($tabNodeList->item(0));
		
		// Save the new configuration
		$dom->formatOutput = true;
		$dom->save($file);

		// Delete the gauges files repository
		recursiveDelete($gaugeRep);

		$tabId=substr($defaultTab,3)-1;
		echo '{"state":"success","tab":"'.$tabId.'"}';
	}
}


function getMapId() {
	// Load the configuration file
	$dom = new DOMDocument();
	$dom->load('../config/general.xml');
	$xpath = new DOMXpath($dom);

	$mapList = $xpath->query('/general/mapid');
	$mapId = $dom->saveXML($mapList->item(0));

	echo json_encode(simplexml_load_string($mapId));
}

/* Util functions */

// Recursive delete
function recursiveDelete($str){
	if (is_file($str)) {
		return unlink($str);
	} elseif (is_dir($str)) {
		$scan = glob(rtrim($str, '/').'/*');
		foreach ($scan as $index => $path) {
			recursiveDelete($path);
		}
		return rmdir($str);
	}
}

/** fonction qui determine si le produit est de type roaming
* pour cela utilise les colonnes visible pour l agregation hour, si visible = 0 alors le produit est de type roaming
* @product: string family
* @return : 0 ou 1
*/
function isRoaming(){
	$sdp_id = (empty($_POST['sdp_id']) ? 1 : $_POST['sdp_id']);
	$db = Database::getConnection($sdp_id);
	
    $query = "SELECT visible FROM sys_definition_time_agregation WHERE agregation = 'hour'";
    $row = $db->getrow($query);
    
	if ($row) {
		echo $row['visible'];
	} else {
		echo 0;
	}
}

// Recursive copy
function recursiveCopy($src, $dst) {
	$oldmask = umask(0);
	if (file_exists($dst)) recursiveDelete($dst);
	
 	if (is_dir($src)) {
    	mkdir($dst, 0777);
    	chmod($dst, 0777);
    	$files = scandir($src);
   		foreach ($files as $file) {	
    		if ($file != '.' && $file != '..' && substr($file, 0, 1) != '.' ) {
   				recursiveCopy("$src/$file", "$dst/$file");
   			}
   		}
 	} else if (file_exists($src)) {
		copy($src, $dst);
		chmod($dst, 0777);
	}
	umask($oldmask);
}

function formatBytes($val, $digits = 3, $mode = 'SI', $bB = 'B'){ //$mode == 'SI'|'IEC', $bB == 'b'|'B'
   $si = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
   $iec = array('', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi');
   switch(strtoupper($mode)) {
       case 'SI' : $factor = 1000; $symbols = $si; break;
       case 'IEC' : $factor = 1024; $symbols = $iec; break;
       default : $factor = 1000; $symbols = $si; break;
   }
   switch($bB) {
       case 'b' : $val *= 8; break;
       default : $bB = 'B'; break;
   }
   for($i=0;$i<count($symbols)-1 && $val>=$factor;$i++)
       $val /= $factor;
   $p = strpos($val, '.');
   if($p !== false && $p > $digits) $val = round($val);
   elseif($p !== false) $val = round($val, $digits-$p);
   return round($val, $digits) . ' ' . $symbols[$i] . $bB;
}

function normalize_str($str)
{
$invalid = array('<'=>'&lt;', '>'=>'&gt;', '&'=> '&amp;','"'=> '&quot;', "'"=> '&apos;');
 
$str = str_replace(array_keys($invalid), array_values($invalid), $str);
 
return $str;
}

?>
