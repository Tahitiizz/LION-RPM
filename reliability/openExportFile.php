<?php
include_once dirname(__FILE__)."/../php/environnement_liens.php";

$typeExport = $_REQUEST['type'];
$link = $_REQUEST['url'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>T&A > </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body  topmargin="0" leftmargin="0">
<style>
body {
	background-image:url("../images/fonds/fond_selecteur.gif");
	border:1px dotted #787878;
	text-align: center;
	height: 98px;
}
#click_to_download {
	text-align: center;
}
fieldset div{
	height: 100%;
	padding: 25px;
	text-align: center;
}
a, a:link{
	color:#585858;
	font:bold 9pt Verdana,Arial,sans-serif;
	text-decoration:none;
}
</style>
<script>
	function openFile(){
		window.open('<?=$link?>', '', '');
		window.close();
	}
</script>

<div id="click_to_download">
	<fieldset style="width:90%;text-align:left;">
		<legend>&nbsp;<img src='<?=NIVEAU_0?>images/icones/download.png'>&nbsp;</legend>	
		<div>
			<a id="file_url" href="#" onclick="openFile();">
				Click here to download the <?=$typeExport?> file
			</a>
		</div>
	</fieldset>
</div>
</body>
</html>