<?
/*
*	@cb50000@
*
*	16/07/2009 - Copyright Astellia
*
*	IHM de gestion des Data Export - Génération de l'export
*
*	24/03/2010 NSE bz 14458 : 
*		- déplacer les fichiers sur le slave local ou distant si besoin
*	09/12/2011 ACS Mantis 837 DE HTTPS support
*/
?>
<?php
session_start();
// Librairies et classes requises
include_once dirname(__FILE__)."/../php/environnement_liens.php";
require_once(REP_PHYSIQUE_NIVEAU_0.'models/DataExportModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DirectoryManagement.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/topology/TopologyDownload.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DataExport.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');

// Variables reçues
$family = $_GET['family'];
$product = $_GET['product'];
$export_id = $_GET['export_id'];

/**
*	Déplace les fichiers générés vers le bon répertoire local ou distant (slave)
*	@param worksDir : répertoire dans lequel a été créé le fichier à déplacer
*	@param finalDir : répertoire final du fichier à télécharger
*	@param finalUrl : url (répertoire) du fichier à télécharger
*	@param file : nom du fichier à télécharger
*	@param destProductInfos : informations sur le produit de destination
*	@param taValue : TA Value à afficher dans le lien
*	@param id : id du lien
*	@param name : nom du lien
*	@param message : message à afficher en lien pour le téléchargement
*	@return string : la ligne à afficher dans la popup de téléchargement
**/	
function deplaceFichierEcritLien($worksDir, $finalDir, $finalUrl, $file, $destProductInfos, $taValue, $id, $name, $message){				
	// Le serveur destination a la même IP que le master
        // 06/04/2012 BBX
        // BZ 26732 : Utilisation de get_adr_server au lieu de $_SERVER['SERVER_ADDR']
	if($destProductInfos['sdp_ip_address'] == get_adr_server()) {
		// avant de copier le fichier, on s'assure que celui-ci n'existe pas déjà
		if(file_exists($finalDir.$file))
			exec('rm -rf "'.$finalDir.$file.'"');
		// on recopie le fichier
		$execCtrl &= copy($worksDir.$file, $finalDir.$file);		
		$result = '
		<tr><td>
		<li>
		<a id="'.$id.'" name="'.$name.'" href="'.NIVEAU_0.'php/force_download.php?filepath='.$finalDir.$file.'">
			<p class="texteGrisBold">
				'.$message.(!empty($taValue)?' ('.$taValue.')':'').'
			</p>
		</a>
		</li>
		</td></tr>';
	}
	// Le serveur destination (slave) est une application distante
	else 
	{
		try 
		{
			// on envoie le fichier
			$SSH = new SSHConnection($destProductInfos['sdp_ip_address'], $destProductInfos['sdp_ssh_user'], $destProductInfos['sdp_ssh_password'],$destProductInfos['sdp_ssh_port']);
			try {
				$SSH->sendFile($worksDir.$file, $finalDir.$file);
				$result = '
				<tr><td>
				<li>
				<a id="'.$id.'" name="'.$name.'" href="'.NIVEAU_0.'php/force_download.php?filepath='.$finalUrl.$file.'">
					<p class="texteGrisBold">
						'.$message.' ('.$taValue.')
					</p>
				</a>
				</li>
				</td></tr>';
			}
			catch (Exception $e) {
				$execCtrl &= false;
			}
		}
		catch (Exception $e) {
			$execCtrl &= false;
		}
	}
	return $result;
}

/**
* Appel de la génération via Ajax
*/
if(isset($_GET['export']) && ($_GET['export'] == 1))
{
	// les fichiers sont créés sur le master
	// ils seront ensuite déplacés au besoin vers un slave, local ou distant
	// Récupération des infos du produit
	$destProduct = new ProductModel($product);
	$destProductInfos = $destProduct->getValues();
	// destination des fichiers
	$finalDir = '/home/'.$destProductInfos['sdp_directory'].'/upload/export_files/';
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	$finalUrl = $destProduct->getCompleteUrl('upload/export_files/');
	
	// on crée un répertoire de travail pour générer le Data Export
	// on le déplacera par la suite vers le bon endroit
	if(!is_dir(REP_PHYSIQUE_NIVEAU_0.'upload/export_files')){
		mkdir(REP_PHYSIQUE_NIVEAU_0.'upload/export_files', 0777);
	}
	$worksDir = REP_PHYSIQUE_NIVEAU_0.'upload/export_files/tmp_data_export';
	if(is_dir($worksDir)){
		exec('rm -rf "'.$worksDir.'"');
	}	
	if(mkdir($worksDir, 0777)){
		$worksDir .= '/';
		// Instanciation d'un objet DataExport
		$DataExport = new DataExport($export_id,$product);
                
                // 15/09/2011 BBX
                // BZ 22802 : On prend la dernière date avec des données
                $lastDayWithData = getLastIntegratedDay($product);
                if(!empty($lastDayWithData)) {
                    $offsetDay = Date::getDatesDiff(date('Ymd'), $lastDayWithData);
                    $DataExport->setOffsetDay($offsetDay);
                }
                // Désactivation hour to compute
                $DataExport->disableHourToCompute();
                // FIN BZ 22802
                
		// Le répertoire de destination est pour le moment sur le master
		$DataExport->setTargetDir($worksDir);
		// Let's Rock'n Roll
		$files = $DataExport->buildFiles();
		$nbFiles = 0;
		
		// Début retour HTML
		$result = '
		<fieldset style="width:90%;text-align:left;">
		<legend>&nbsp;<img src="'.NIVEAU_0.'images/icones/download.png">&nbsp;</legend>';
		
		$result .= '<table width="100%" cellspacing="0" cellpadding="0" border="0">';
		
		// Si pas de fichiers, on regarde s'il y a une erreur
		if(!$files && ($DataExport->getError() != '')) {
			$result .= '<tr><td><div class="errorMsg">'.$DataExport->getError().'</div></td></tr>';
			$nbFiles+=2;
		}

		// Data Export File
		if(isset($files['export'])) 
		{
			// Liens vers tous les fichiers générés
			foreach($files['export'] as $taValue => $file) 
			{
				$result .= deplaceFichierEcritLien($worksDir, $finalDir, $finalUrl, $file, $destProductInfos, $taValue, 'link_to_data_export_file', 'link_to_data_export_file', __T('A_TASK_SCHEDULER_DATA_EXPORT_DL_EXPORT'));
				$nbFiles++;
			}		
			$result .= '</div>';			
		}
		else
		{
			$message = 'No Data for '.$DataExport->getTaValue();
			$result .= '
			<tr><td align="center">
				<p class="texteGrisBold">
					'.$message.'
				</p>	
			</td></tr>';
			$nbFiles++;
		}
		
		// Counters file
		if(isset($files['raws']))
		{
			$result .= deplaceFichierEcritLien($worksDir, $finalDir, $finalUrl, $files['raws'], $destProductInfos, $taValue, 'link_to_counters_file', 'link_to_counters_file',__T('A_TASK_SCHEDULER_DATA_EXPORT_DL_COUNTERS'));
			$nbFiles++;
		}
		
		// KPI file
		if(isset($files['kpis']))
		{
			$result .= deplaceFichierEcritLien($worksDir, $finalDir, $finalUrl, $files['kpis'], $destProductInfos, $taValue, 'link_to_kpis_file', 'link_to_kpis_file',__T('A_TASK_SCHEDULER_DATA_EXPORT_DL_KPIS'));
			$nbFiles++;
		}	
		
		// Topo 1er axe
		if(isset($files['topo1']))
		{
			$result .= deplaceFichierEcritLien($worksDir, $finalDir, $finalUrl, $files['topo1'], $destProductInfos, $taValue, 'link_to_topo1_file', 'link_to_topo1_file',__T('A_TASK_SCHEDULER_DATA_EXPORT_DL_TOPO1'));
			$nbFiles++;
		}
		
		// Topo 3ème axe
		if(isset($files['topo3']))
		{
			$result .= deplaceFichierEcritLien($worksDir, $finalDir, $finalUrl, $files['topo3'], $destProductInfos, $taValue, 'link_to_topo3_file', 'link_to_topo3_file',__T('A_TASK_SCHEDULER_DATA_EXPORT_DL_TOPO3'));
			$nbFiles++;
		}
		if(is_dir($worksDir)){
			exec('rm -rf "'.$worksDir.'"');
		}
	}
	else{
			$result .= '
			<tr><td align="center">
				<p class="texteGrisBold">
					'."Unable to create temporary directory".'
				</p>	
			</td></tr>';
	}
	// Fin HTML
	$result .= '
		<tr><td align="center">
			<br />
			<input type="button" class="bouton" value="Close" onclick="window.close()" />
		</td></tr>
	</table>';
	
	// Resize fenêtre
	if($nbFiles > 0) 
	{
		$newHeight = 0;
		switch($nbFiles)
		{
			case 1:
				$newHeight = 20;
			break;
			case 2:
				$newHeight = 45;
			break;
			case 3:
				$newHeight = 70;
			break;
			case 4:
				$newHeight = 90;
			break;
			case 5:
				$newHeight = 125;
			break;
			default:
				$newHeight = 200;
			break;
		}		
		$result .= '
		<script type="text/javascript">
			window.resizeBy(75,('.$newHeight.'));
			window.moveBy(-35,-('.$newHeight.')/2);
		</script>';
	}

	// Affichage du résultat
	echo $result;
	
	// Fin
	exit;
}

// Header HTML
$arborescence = 'Generate Data Export';
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
	top:30%;
	left:32%;
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
li {
	height:25px;
}
</style>

<div id="container" style="width:100%;text-align:center">

<div id="loader_container">
	<div id="loader">
		<div align="center" id="texteLoader"><?=__T('A_EXPORTS_BUILDING_FILE')?>(s)...</div>
		<div id="loader_bg"><div id="progress"> </div></div>
	</div>
</div>

<div id="download_container" style="display:none;"></div>

<script type="text/javascript">
/****
* Javascript à éxécuter au chargement de la page
****/
// Si mode popup, on centre la fenêtre
if(window.opener)  {
	window.resizeTo(415,125);
	window.moveTo((Math.floor(screen.width/2)-207),(Math.floor(screen.height/2)-82));
	window.focus();
}

// Variables globales de la page
var _animation = setInterval(animate,20);
var _pos = 0;
var _dir = 2;
var _len = 0;

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
	
// Appel Ajax du script de génération
new Ajax.Request('export_file.php',{
	method:'get',
	parameters:'export=1&family=<?=$family?>&product=<?=$product?>&export_id=<?=$export_id?>',
	onSuccess:function(res) {
		// Affichage du résultat reçu	
		$('download_container').update(res.responseText);
		//Suppression de la barre de chargement
		$('loader_container').setStyle({display:'none'});
		//Affichage du lien de téléchargement
		$('download_container').setStyle({display:'block'});
		document.body.className = 'tabPrincipal';
	}
});
</script>
</body>
</html>