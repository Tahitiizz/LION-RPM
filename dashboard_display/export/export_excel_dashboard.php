<?php
/*
*	Ce script récupère le nom des png en session, lit les xml correspondant et cree un fichier excel d'export
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 18/03/2009
*
*
*	24/09/2009 BBX : modification du comportement des popups.
*		=> Le document s'ouvre dans la poup : la poup passe en fullscreen
*		=> Le document est téléchargé. Le lien de téléchargement passe en lien de fermeture du popup.
*	18/01/2010 NSE bz 13789 : 
*		lorsque le graph ne contient pas de données, on tente d'ouvrir un fichier n¿ÛYªçŠx?nÈ¦¦W±šYhi×â•æ¡­ç 
*		=> test avant affichage du lien de téléchargement
*  21/09/2011 MMT bz 19740 le retour de la fonction Ajax ne doit etre que le fichier lui meme ou sa plante
*  
*/
session_start();
include_once dirname(__FILE__).'/../../php/environnement_liens.php';
require_once(REP_PHYSIQUE_NIVEAU_0.'/class/PHPOdf.class.php');
require_once dirname(__FILE__)."/../class/XmlExcel.class.php";

// Mode d'export
$modeExport = isset($_GET['mode']) ? $_GET['mode'] : 'landscape';
$typeExport = "Excel";

// *********************
// Si l'export est demandé
// *********************
if(isset($_GET['export']) && ($_GET['export'] == 1)) 
{
	//on recupere le nom des images qui sont stockees dans une variable de session
	$export_buffer = $_SESSION['dashboard_export_buffer'];
	
	//si les donnees en session contiennent au moins un GTM
	if ( count($export_buffer) > 0 ) {
		
		//on recupere le nom des xml
		foreach($export_buffer['data'] as $buf) {
			$txml[] = $buf['xml'];
		}

		try {
			//21/09/2011 MMT bz 19740 capture des output potentiels (warning ou autre)
			ob_start();

			//appel de la classe qui va genere le fichier Excel
			$xmlExcel = new XmlExcel();
			//on recupere les donnees de la liste de xml
			$xmlExcel->getMultipleData($txml);
			//on sauvegarde le fichier
			$file = $xmlExcel->save();
			//21/09/2011 MMT bz 19740 le retour ne doit etre que le fichier lui meme on sa plante grave
			ob_clean();
			// Retour du chemin du fichier (si aucune Exception)
			echo base64_encode($file);

		}catch(Exception $e) {
			//on capture l'exception mais on ne recupere pas l'erreur
		}
	}
	//sinon on ne renvoie rien
	exit;
}

// *********************
// Récupère les données nécessaires
// *********************
$arborescence = 'Excel Export';
$downloadMsg = (strpos(__T('U_EXCEL_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_EXCEL_FILE_DOWNLOAD') : "Click here to download the Excel file";
// 18/01/2010 NSE  bz 13789
// a-t-on des données pour générer un exel ?
$export_buffer = $_SESSION['dashboard_export_buffer'];
//si les donnees en session contiennent au moins un GTM
if ( count($export_buffer) > 0 ) {
	// on parcours le tableau
	foreach($export_buffer['data'] as $buf) {
		// on mémorise uniquement s'il y a un fichier lié
		if(!empty($buf['xml']))
			$txml[] = $buf['xml'];
	}
	if(empty($txml))
	{
		$downloadMsg = __T('U_EXCEL_NO_DATA');
	}
}
// fin 18/01/2010 NSE  bz 13789
// *********************
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
		<?php // 18/01/2010 NSE  bz 13789
		if($downloadMsg != __T('U_EXCEL_NO_DATA')){?><a id="link_to_file" name="link_to_file" href="#" onclick="downloadFile()"><?php }?>
			<p class='texteGrisBold'>
				<?=$downloadMsg?>
			</p>
		<?php // 18/01/2010 NSE  bz 13789
		if($downloadMsg != __T('U_EXCEL_NO_DATA')){?></a><?php }?>
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
		elem.style.left = _pos;
		elem.style.width = _len;
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
new Ajax.Request('export_excel_dashboard.php',{
	method:'get',
	parameters:'export=1',
	onSuccess:function(res) {
		//Suppression de la barre de chargement
		$('loader_container').setStyle({display:'none'});
		//Affichage du lien de téléchargement
		$('download_container').setStyle({display:'block'});
		document.body.className = 'tabPrincipal';
		// Enregistrement du document
		_fileToDownload = 'export_file.php?file='+res.responseText;
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