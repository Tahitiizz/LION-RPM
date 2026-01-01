<?php
if(!isset($_SESSION)) session_start();
include_once('../php/environnement_liens.php');
include_once("../intranet_top.php");
session_commit();
?>

<!--force IE9 mode -->
<script type="text/javascript">
	var el=document.createElement('meta');
	el.httpEquiv="X-UA-Compatible";
	el.content="IE=Edge";
	document.getElementsByTagName('head')[0].appendChild(el);
</script>

<!-- Functions Ext JS -->
<script	type="text/javascript" src="extjs/ext.js"></script>
<!--script type="text/javascript" src="extjs/ext-debug.js"></script-->

<!-- Functions Bindows -->
<script type="text/javascript" src="js/bindows_gauges.js"></script>

<!-- Functions Canvg -->
<script type="text/javascript" src="js/rgbcolor.js"></script>
<script type="text/javascript" src="js/canvg.js"></script>
<script type="text/javascript" src="js/svg_todataurl.js"></script>

<script type="text/javascript" src="js/innersvg.js"></script>

<!-- Functions Canvg -->
<script type="text/javascript" src="js/ammap.js"></script>
<script type="text/javascript" src="js/maps/js/worldHigh.js"></script>

<div id="waitDiv" class="waitMessage">
	<img src="images/icons/time.png">
	&nbsp;&nbsp;Please wait...
</div>

<div id="taHeader" />

<?php 
// Get the user name
$database_connection = new DataBaseConnection();
$query = "SELECT login
			FROM users 
			WHERE id_user='{$_SESSION['id_user']}'
			LIMIT 1;";
$userName = $database_connection->getOne($query);

// If the user is client_user or astellia_user, he has an admin access to the homepage
$isAdmin = ($userName == 'astellia_admin' || $userName == 'astellia_user') ? true : false;

if ($isAdmin) {
	$file = 'config/default/homepage.xml';
} else {
	$userId = $_SESSION['id_user'];
	$file = 'config/'.$userId.'/homepage.xml';
}

// Load the configuration file
$dom = new DOMDocument();
$dom->load($file);

// Get the homepage style
$xpath = new DOMXpath($dom);
$styleNodeList = $xpath->query('/homepage/style');

if (($styleNodeList->length > 0) &&
	($styleNodeList->item(0)->nodeValue == 'access')) {
	$style = 'access';
	$extCss = "extjs/resources/css/ext-all-access.css";
	$homepageCss = "css/homepage-access.css";
} else {
	$style = 'classic';
	$extCss = "extjs/resources/css/ext-all.css";
	$homepageCss = "css/homepage.css";
} 

?>

<!-- Style of the Homepage -->
<input type="hidden" id="homepageStyle" value="<?php echo $style ?>" />
<input type="hidden" id="isIpad" value="<?php echo ((bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPad') ? 1 : 0); ?>" />

<link rel="stylesheet" type="text/css" href=<?php echo $extCss ?>>
<link rel="stylesheet" type="text/css" href=<?php echo $homepageCss ?>>
<link rel="stylesheet" type="text/css" href="extjs/src/ux/css/TabScrollerMenu.css" />
<link rel="stylesheet" type="text/css" href="extjs/src/ux/css/CheckHeader.css">

<script type="text/javascript">
	// Move header and menu_container in the ta Header panel (use for fullscreen mode)
	Ext.get('taHeader').appendChild(Ext.get('header'));
	Ext.get('taHeader').appendChild(Ext.get('menu_container'));
</script>

<!-- Application script -->
<script type="text/javascript" src="app-all.js"></script>
<script type="text/javascript" src="homepage.js"></script>