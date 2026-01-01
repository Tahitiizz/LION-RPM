<?php
/*
*	Script d'export excel des graphiques du caddy (adaptation du script /dashboard_display/export/export_excel.php)
* 
* 	@version CB 4.1.0.00
*	@author SPS
*	@date 11/05/2009
*
*
*	24/09/2009 BBX : modification du comportement des popups.
*		=> Le document s'ouvre dans la poup : la poup passe en fullscreen
*		=> Le document est téléchargé. Le lien de téléchargement passe en lien de fermeture du popup.
*
*/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once REP_PHYSIQUE_NIVEAU_0."/dashboard_display/class/XmlExcel.class.php";

$typeExport = "Excel";

// *********************
// Si l'export est demandé
// *********************
if(isset($_GET['export']) && ($_GET['export'] == 1)) {

	/*on recupere en base les graphiques selectionnes par l'utilisateur*/
	// 20/08/2009 BBX : on ajoute le type investigation_dashboard dans la récupération des images. BZ 11120
	$sql = "SELECT object_id AS image 
		FROM sys_panier_mgt 
		WHERE id_user = '".$_SESSION['id_user']."' 
		AND (
		object_type = 'graph' 
		OR object_type ILIKE '%pie%' 
		OR object_type = 'gis_raster' 
		OR object_type = 'investigation_dashboard'
	)";

        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection();
	$result = $db->getAll($sql);
	$nombre_resultat = count($result);
	
	for($i=0;$i<$nombre_resultat;$i++) {
		//on recupere le nom du xml a partir du nom de l'image
		$xml = ereg_replace(".png",".xml",$result[$i]['image']);
		
		//on rajoute le chemin physique qui n'est pas stocke en base
		$txml[] = REP_PHYSIQUE_NIVEAU_0.'png_file/'.$xml;
	}

	
	//si on a des resultats
	if ( $nombre_resultat > 0 ) {
		
		try {

			//appel de la classe qui va genere le fichier Excel
			$xmlExcel = new XmlExcel();
			//on recupere les donnees de la liste de xml
			$xmlExcel->getMultipleData($txml);
			
			//on sauvegarde le fichier
			$file = $xmlExcel->save();			
	
		}catch(Exception $e) {
			//on capture l'exception mais on ne recupere pas l'erreur
		}
		
		// Retour du chemin du fichier (si aucune Exception)
		echo base64_encode($file);
	}
	//sinon on ne renvoie rien
	exit;
}

$arborescence = 'Excel Export';
$downloadMsg = (strpos(__T('U_EXCEL_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_EXCEL_FILE_DOWNLOAD') : "Click here to download the Excel file";
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
new Ajax.Request('export_excel.php',{
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
