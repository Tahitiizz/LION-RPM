<?php
/*
	MPR
		- Ajout " object_type = 'gis_raster'" dans la condition de la première requete SQL
	31/07/2009 BBX
		- Correction du BZ  10633
	03/08/2009 GHX
		- Suppression de la condition sur la présence de la variable de session, sinon impossible de faire un export contenant uniquement des éléments du QUERY BUILDER
*/
?>
<?php
/*
*	Ce script récupère les valeurs dans le caddy et génère un export de type word ou pdf
*
*	@version CB 4.1.0.00
*	@author BBX
*	@date 24/07/2009
*
*
*	24/07/2009 BBX : Réécriture du script. BZ 10629
*		=> Ajout de la récupération des pies dans la requête
*		=> Script selon le modèle des exports des alarmes
*		=> Ajout d'un titre au document généré (Cart)
*
*	19/08/2009 BBX : augmentation du timeout de fermeture. BZ 11064
*
*	20/08/2009 BBX : on ajoute le type investigation_dashboard dans la récupération des images. BZ 11120
*
*	24/09/2009 BBX : modification du comportement des popups.
*		=> Le document s'ouvre dans la poup : la poup passe en fullscreen
*		=> Le document est téléchargé. Le lien de téléchargement passe en lien de fermeture du popup.
*  
*/
session_start();
include_once dirname(__FILE__).'/../../../../php/environnement_liens.php';
require_once(REP_PHYSIQUE_NIVEAU_0.'class/PHPOdf.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DashboardExport.class.php');

// Type d'export
$typeExport = $_GET['type'];
// Mode d'export
$modeExport = isset($_GET['mode']) ? $_GET['mode'] : 'landscape';

// *********************
// Si l'export est demandé
// *********************
if(isset($_GET['export']) && ($_GET['export'] == 1)) 
{
	// Récupération des images
	$astelliaLogo = get_sys_global_parameters('pdf_logo_dev');
	$clientLogo = get_sys_global_parameters('pdf_logo_operateur');
	
	// on recupere en base les graphiques selectionnes par l'utilisateur
	// 20/08/2009 BBX : on ajoute le type investigation_dashboard dans la récupération des images. BZ 11120
	$sql = "SELECT object_title AS titre,object_id AS image, object_type
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
	
	if(count($result) > 0)
	{		
		// Création du tableau de données d'export
		$dash_export = Array();		
		foreach($result as $i=>$gtm_values)
		{
			// 31/07/2009 BBX : récupération de l'id GTM. BZ 10633
			$query = "SELECT id_page from sys_panier_mgt p, sys_pauto_page_name n
			WHERE p.object_title ILIKE n.page_name||'%'
			AND p.object_title ILIKE '".$gtm_values['titre']."%'
			AND id_page IN (
				SELECT id_elem FROM sys_pauto_config
				WHERE id_page = (
					SELECT id_page FROM sys_pauto_page_name
					WHERE page_type = 'page'
					AND page_name = p.object_page_from
					LIMIT 1
				)
			)";
			$gtmId = $db->getOne($query);
			$GTMModel = new GTMModel($gtmId);
			// Data d'export	
			$dash_export[] = Array(	'titre' => $gtm_values['titre'], 
									'image' => REP_PHYSIQUE_NIVEAU_0.'png_file/'.$gtm_values['image'],
									'lastComment' => $GTMModel->getLastComment());
		}		
		
		$tab_data['titre']	= 'Cart';
		$tab_data['data']	= $dash_export;

		// 11:13 03/08/2009 GHX
		// Suppression de la condition ??!!
		/*18/03/2009 - modif SPS : on regarde la taille des donnees en session*/
		//si les donnees en session contiennent au moins un GTM
		// if ( count($_SESSION['dashboard_export_buffer']) > 0 ) 
		// {
			// Instanciation de la classe d'export
			$DashboardExport = new DashboardExport($tab_data,$modeExport,
													REP_PHYSIQUE_NIVEAU_0.'/upload',
													'export_'.$typeExport.'_',
													REP_PHYSIQUE_NIVEAU_0.$astelliaLogo,
													REP_PHYSIQUE_NIVEAU_0.$clientLogo,
													REP_PHYSIQUE_NIVEAU_0.'/images/icones/pdf_alarm_titre_arrow.png');

			// Export Word
			if($typeExport == 'word') {
				$filePath = $DashboardExport->wordExport();
			}
			// Export Pdf
			else {
				$filePath = $DashboardExport->pdfExport();
			}
			
			// Retour du chemin du fichier
			echo base64_encode($filePath);
		// }
	}
	//si on a pas de donnees, on ne renvoie rien
	exit;
}

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
new Ajax.Request('export_word_pdf.php',{
	method:'get',
	parameters:'type=<?=$typeExport?>&mode=<?=$modeExport?>&export=1',
	onSuccess:function(res) {
		//Suppression de la barre de chargement
		$('loader_container').setStyle({display:'none'});
		//Affichage du lien de téléchargement
		$('download_container').setStyle({display:'block'});
		document.body.className = 'tabPrincipal';
		// Enregistrement du document
		_fileToDownload = '<?=NIVEAU_0?>/dashboard_display/export/export_file.php?file='+res.responseText;
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
