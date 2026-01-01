<?php
/*
*	@cb50000@
*
*	16/07/2009 - Copyright Astellia
*
*	IHM de gestion des Data Export - traitements ajax
*
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0.'models/DataExportModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'class/DirectoryManagement.class.php');

// Selon l'action demandée
// 28/11/2011 NSE bz 23633 : suite au changement d'appel, on reçoit maintenant les éléments en GET
// 09/05/2012 NSE reopen bz 23633 : on revient au POST pour compatibilité cb 5.0
switch($_POST['action'])
{
	// Récupère une info Raw ou KPI
	case 'getElementInfo':
		if($_POST['type'] == 'raw') {
			echo DataExportModel::getRawComment($_POST['family'],$_POST['product'],$_POST['idElem']);
		}
		else {
			echo DataExportModel::getKpiComment($_POST['family'],$_POST['product'],$_POST['idElem']);
		}
	break;
	
	// vérifie le formulaire
	case 'checkForm':
            // ajout de urldecode pour que le vérification du target file fonctionne
		$checkResult = DataExportModel::checkValues(array_map('urldecode',$_POST));
		if($checkResult === true)
			echo 'OK';
		else
			echo $checkResult;
	break;
}
?>