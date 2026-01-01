<?php
/**
*	Ce fichier génère le header HTML de T&A
*
*	16/02/2009 - Modif. benoit : inclusion du fichier JS de gestion du caddy
* 	07/04/2009 - modif SPS : ajout du charset iso-8859-1 pour les scripts JS fenetres_volantes.js et functions_comment_graph.js (corrections erreur pour ie6)
* 	09/04/2009 - modif SPS : liste des js et css entre les balises head
* 	14/04/2009 - modif SPS : suppression du js fade_functions.js (inutilise)
*	11/10/2011 - ACS Mantis 615: DE Data reprocessing GUI
*
*	@author	BBX - 27/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*	04/03/2010 BBX : suppression de l'appel de loader.js. BZ 11686
*   23/01/2012 SPD1 : Query builder V2 - ajout d'un parametre GET pour desactiver l'emulation IE 7
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>T&A > <?php echo $arborescence; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<!-- CCT1 06/07/09 : correction bugs BZ 10391 BZ 10399 : permet d'obliger IE8 à utiliser une émulation IE7 -->

<?php
	// 23/01/12 : SPD1 - Query builder V2, add a GET parameter to disabled IE compatibility mode   
        // 20/07/2012 BBX
        // BZ 27166 : merge de la modification faite avec le CB 5.2 pour les besoin OMC (homepage)
	if (!isset($_GET['NoIEmulate']) || $_GET['NoIEmulate'] == "0") {	
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />';
	}else if($_GET['NoIEmulate'] == "2"){
		//06/10/2014 - FGD - Bug 43759 - [REC][CB 5.3.3.02][TC #TA-56768][IE 11 Compatibility] Missing family's name in Query Builder GUI
		//Emulate IE10 for query builder
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10" />';				
	}
?>	
	<!-- base href="http://<?php echo $_SERVER['HTTP_HOST'].NIVEAU_0; ?>" / -->
	<!--  22/06/2007 christophe, ajout de prototype.js, window.js et des css associés. -->
        <? /* 20/03/2012 NSE bz 26445 : utilisation de prototype 1.7 pour QueryBuilder ($NoIEmulate=1) */ ?>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/prototype<?=isset($_GET['NoIEmulate'])&&$_GET['NoIEmulate']!=0?'1_7':''?>.js'> </script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/window.js'> </script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/scriptaculous.js'> </script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/toggle_functions.js'></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/fenetres_volantes.js' charset='iso-8859-1'></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/gestion_fenetre.js'></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/ajax_functions.js'></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/functions_comment_graph.js' charset='iso-8859-1'></script>
	<script type='text/javascript' src='<?=NIVEAU_0?>js/caddy_management.js'></script>
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/prototype_window/default.css' type='text/css'/>
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/prototype_window/alphacube.css' type='text/css'/>
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/global_interface.css' type='text/css'/>
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/header2008.css' type='text/css'/>
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/menu2008.css' type='text/css'/>
	
	<link rel='stylesheet' href='<?=NIVEAU_0?>css/dataReprocess.css' type='text/css'/>
</head>

<body <?=$onload?> topmargin="0" leftmargin="0">
<!-- 25/06/2007 christophe : id permettant de se positioner en haut via un lien href  -->
<div id="haut_appli"></div>
	
