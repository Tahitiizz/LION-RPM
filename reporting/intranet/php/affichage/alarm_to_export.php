<?
/*
*	@cb50000@
*
*	19/06/2009 - Copyright Astellia
*
*	Composant de base version cb_5.0.0.00
*
*	@author: BBX
*.
*
*	24/09/2009 BBX : modification du comportement des popups.
*		=> Le document s'ouvre dans la poup : la poup passe en fullscreen
*		=> Le document est téléchargé. Le lien de téléchargement passe en lien de fermeture du popup.
 *
 * 03/03/2011 MMT bz 19628 : corrige building file animation sous Firefox
*/
?>
<?
session_start();
// INCLUDES.
include_once(dirname(__FILE__)	. "/../../../../php/environnement_liens.php");

// Récupère les données nécessaires
switch($_GET['file_type']) {
	// Export PDF
	case 'pdf' :
		$arborescence = 'PDF Export';
		$downloadMsg = (strpos(__T('U_PDF_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_PDF_FILE_DOWNLOAD') : "Click here to download the PDF file";
	break;
	// Export Excel
	case 'xls' :
		$arborescence = 'Excel Export';
		$downloadMsg = (strpos(__T('U_EXCEL_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_EXCEL_FILE_DOWNLOAD') : "Click here to download the Excel file";
	break;
	// Export Word
	default :
		$arborescence = 'Word Export';
		$downloadMsg = (strpos(__T('U_WORD_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_WORD_FILE_DOWNLOAD') : "Click here to download the WORD file";
	break;
}

// DEBUT PAGE
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<style type="text/css">
.entete{
	color: #fff;
	background-color : #929292;
	font : bold 9pt Verdana, Arial, sans-serif;
	text-align: center;
}
#interface1 { 
	z-index:1; 
}
#loader_container {
	position:absolute; 
	width:130px;
	top:20%;
	left:30%;
}
#loader {
	font-family:Tahoma, Helvetica, sans;
	font-size:11px;
	color:#000000;
	background-color:#FFFFFF;
	padding:10px 0 16px 0;
	margin:0 auto;
	display:block;
	width:130px;
	border:1px solid #6A6A6A;
	text-align:left;
	z-index:2;
}
#progress {
	height:5px;
	font-size:1px;
	width:1px;
	position:relative;
	top:1px;
	left:0px;
	background-color:#9D9D94
}
#loader_bg {
	background-color:#EBEBE4;
	position:relative;
	top:8px;
	left:8px;
	height:7px;
	width:113px;
	font-size:1px;
}
</style>
<div id="container" style="width:100%;text-align:center">

<div id="loader_container">
	<div id="loader">
		<div align="center" id="texteLoader"><?=__T('A_EXPORTS_BUILDING_FILE')?></div>
		<div id="loader_bg"><div id="progress"> </div></div>
	</div>
</div>

<div id="download_container" style="display:none;">
	<fieldset style="width:90%">
		<legend>&nbsp;<img src="<?=NIVEAU_0?>images/icones/download.png">&nbsp;</legend>
		<a id="link_to_file" name="link_to_file" href="#" onclick="downloadFile()">
			<p class='texteGrisBold'>
				<?=$downloadMsg?>
			</p>
		</a>
	</fieldset>
</div>

<script>
// Variables globales de la page
var _animation = setInterval(animate,20);
var _pos = 0;
var _dir = 2;
var _len = 0;
var _fileToDownload = '';
	
// Fonction d'animation
function animate()
{
	var elem = $('progress');
	if(elem != null) {
		if (_pos==0) _len += _dir;
		if (_len>32 || _pos>79) _pos += _dir;
		if (_pos>79) _len -= _dir;
		if (_pos>79 && _len==0) _pos=0;

		// 03/03/2011 MMT bz 19128 : corrige animation sous Firefox
		elem.setStyle( {left:_pos + 'px'} );
		elem.setStyle( {width:_len + 'px'} );
	}
}

// Fonction qui permet le téléchargement
function downloadFile() 
{
	setTimeout("$('link_to_file').update(\"<p class='texteGrisBold'>Click here to close the window</p>\")",1000);
	$('link_to_file').onclick = function() {
		window.close();
	}
	document.location.href = _fileToDownload;
}
	
// Appel Ajax du script de génération
// 18/09/2009 BBX : ajout de l'id produit. BZ 11632
new Ajax.Request('build_export_alarm.php',{
	method:'get',
	parameters:'type_file=<?=$_GET['file_type']?>&mode=<?=$_GET['mode']?>&sous_mode=<?=$_GET['sous_mode']?>&product=<?=$_GET['product']?>',
	onSuccess:function(res) {
		//Suppression de la barre de chargement
		$('loader_container').setStyle({display:'none'});
		//Affichage du lien de téléchargement
		$('download_container').setStyle({display:'block'});
		document.body.className = 'tabPrincipal';
		// Enregistrement du document
		_fileToDownload = res.responseText;
	}
});

// Si on redirige vers un document (ouverture dans la popup) on passe en fullscreen
Event.observe(window, 'unload', function() {
	$('link_to_file').update("<p class='texteGrisBold'>Please Wait...</p>");
	window.moveTo(0,0);
	window.resizeTo(screen.width,screen.height);
});
</script>
</div>
</body>
</html>