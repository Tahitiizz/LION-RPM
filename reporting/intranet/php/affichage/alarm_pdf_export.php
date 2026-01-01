<?php
/*
	@cb51002@
	30/07/2010 NSE
		- bz 6654 : ajout de l'étape 'clic here to download file'
*/
?><?php
/*
	17/07/2009 GHX
		- Correction du BZ 9547 [REC][T&A CB 5.0][caddy]: pas d'export des alarmes
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?><? session_start(); ?><?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?

	// INCLUDES.
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
	include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
  include_once($repertoire_physique_niveau0 . "class/htmlTablePDF.class.php");

	global $database_connection;

	$typeExport = 'pdf';
	$modeExport = 'pdf';
	
	// NSE bz 6654 on ne génère qu'après clic here to download
	if(isset($_GET['export']) && ($_GET['export'] == 1)) 
	{
		// 16:29 17/07/2009 GHX
		// Correction du BZ 9547 
		// Mise entre cote de l'id user
	  $query = "SELECT object_id,object_title FROM sys_panier_mgt WHERE object_type='alarm_export' AND id_user='$id_user' ORDER BY object_title";
	  $result = pg_query($query);

	  if (pg_num_rows($result)>0) {

		$pdf_dir = $repertoire_physique_niveau0.get_sys_global_parameters("pdf_save_dir");
		$pdf_dir_display = $niveau0.get_sys_global_parameters("pdf_save_dir");

		$html_to_pdf = new PDF_HTML_Table();

		$html_to_pdf->generatePDF('detail','Alarm detail');
		$html_to_pdf->set_PDF_directory($pdf_dir);
		$html_to_pdf->set_PDF_file_name(generate_acurio_uniq_id("alarm_detail".$_GET['mode']).".pdf");

		while ($row = pg_fetch_array($result)) {
			$mesDetails = explode ("</table>",$row['object_id']);
			for ($i=0;$i<count($mesDetails)-1;$i++)
				if ($i==0)
					$html[]=array($row['object_title'],$mesDetails[$i]."</table>",11);
				else
				  $html[]=array('',$mesDetails[$i]."</table>",11);
		}

		//$query = "DELETE FROM sys_panier_mgt WHERE object_type='alarm_export' AND id_user=$id_user";
		//$result = pg_query($query);
		if (count($html)) $html_to_pdf->WriteHTML ($html);

		$pdf_name = $html_to_pdf->get_PDF_file_name();

		$html_to_pdf->savePDF();
		echo $pdf_dir_display.$pdf_name;
	}
	exit;
}
// NSE bz 6654 ajout de l'affichage
// *********************
// Récupère les données nécessaires
// *********************
switch($typeExport) {
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
new Ajax.Request('alarm_pdf_export.php',{
	method:'get',
	parameters:'type=<?=$typeExport?>&mode=<?=$modeExport?>&export=1',
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
