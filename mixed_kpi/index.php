<?php
/*
	18/11/2009 GHX
		- Ajout de l'include de la classe SSHConnection
	19/11/2009 GHX
		- Ajout de l'appel SelectedDashboard::checkConfigGraph();
	10/12/2009 BBX
		- Mise � jour du fichier "update_data_export.cfg" lors de la suppression d'une famille. BZ 13270
	10/12/2009 BBX
		- Modification du lien de retour. BZ 13172
	15/12/2009 GHX
    		- BZ 13137 [REC][T&A CB 5.0.2][Edit counters/kpi]: affichage des messages d'erreur alors qu'il n'y a pas d'erreurs
        02/04/2010 - MPR
                - DE Mixed KPI : Synchronisation des compteurs // Affichage du résultat de la synchro
 */
?>
<?php
/*
 * Cette page permet de g�rer le produit Mixed KPI
 *	- Ajout / Modification / Suppression d'une famille
 *	- Modification du niveau temporelle minimum (hour ou day)
 *
 * @author GHX & NSE
 * @date 05/10/2009
 * @version CB 5.0.1.00
 * @since CB 5.0.1.00
 */
session_start();
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once dirname(__FILE__).'/../php/edw_function.php';
include_once dirname(__FILE__).'/../php/edw_function_family.php';
include_once dirname(__FILE__).'/php/functions.php';
include_once REP_PHYSIQUE_NIVEAU_0.'intranet_top.php';
include_once dirname(__FILE__).'/class/CreateMixedKpi.class.php';
include_once dirname(__FILE__).'/class/MixedKpiCFG.class.php';
include_once dirname(__FILE__).'/class/SelectedDashboard.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/deploy.class.php';
// 18/11/2009 GHX
// Ajout de l'include de la classe
include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'models/RawKpiModel.class.php';

?>
<link rel="stylesheet" href="css/style.css" type="text/css" />
<link rel="stylesheet" href="css/alphacube_mk.css" type="text/css" />
<?php
// Si le r�pertoire "mixed_kpi_product" n'existe pas c'est qu'on n'a pas encore d�ploy� le produit Mixed KPI
if ( !file_exists(REP_PHYSIQUE_NIVEAU_0.'mixed_kpi_product') )
{
	include_once dirname(__FILE__).'/php/install.php';
	exit;
}

$mixedKpiModel = new MixedKpiModel();
$idProductMixedKPI = ProductModel::getIdMixedKpi();

if(isset($_POST['ta_min'])){
	// on enregistre la TA min
	
	if($mixedKpiModel->setTAMin($_POST['ta_min']))
	{
		$message = __T('A_SETUP_MIXED_UPDATE_TA');
		$mixedKpiModel->configureFlatFileLib();
		
		// Si on change de TA on reg�n�re les fichiers CFG par pr�caution
		// G�n�re et envoie les fichiers CFG si n�cessaire pour chaque produit
		$cfg = new MixedKpiCFG();
		$cfg->setTaMin($mixedKpiModel->getTaMin());
		$cfg->generateAndSendForAllProducts();
	}
	else
		$message = 'Error TA';
	
	$retour = 0;
	include('family_list.php');
}
elseif(isset($_GET['editna'])){
	// on �dite les NA
	$retour=1;
	include('edit_na.php');
}
elseif(isset($_GET['editraw'])){
	// on �dite les counters
	include('edit_counters.php');
	$retour=1;
}
elseif(isset($_GET['editkpi'])){
	// on �dite les KPIs
	include('edit_kpi.php');
	$retour=1;
}
elseif(isset($_POST['selectDashboards'])){
	include('edit_dashboards.php');
	$retour=1;
}
elseif(isset($_POST['deletefamily']))
{
	// on supprime la famille
	if(MixedKpiModel::deleteFamily($_POST['idFamily'],ProductModel::getIdMixedKpi()))
	{
		// 10/12/2009 BBX : BZ 13270
		// G�n�re et envoie les fichiers CFG si n�cessaire pour chaque produit
		$cfg = new MixedKpiCFG();
		$cfg->setTaMin($mixedKpiModel->getTaMin());
		$cfg->generateAndSendForAllProducts();
		// FIN BZ 13207
		// Message de confirmation de la suppression de la famille
		$message = __T('A_SETUP_MIXED_KPI_FAMILY_DELETED');
	}
	else
		$message = __T('A_E_SETUP_MIXED_KPI_FAMILY_LABEL_EMPTY');
	$retour=0;
	include('family_list.php');
}
elseif(isset($_POST['addfamily']))
{
	// ajout d'une nouvelle famille
	$retour=1;
	include('family_add.php');
}
elseif(isset($_POST['newfamily']))
{

	$messageError = '';
	
	if ( empty($_POST['familyName']) ) // Si le label de la famille est vide
	{
		$messageError = __T('A_E_SETUP_MIXED_KPI_FAMILY_LABEL_EMPTY');
	}
	else
	{
		// On supprime tous ce que n'est pas un caract�re accept�
		$familyName = preg_replace('/[^a-zA-Z0-9 _-]/', '', $_POST['familyName']);
		if ( !isset($idFamily) ) // NOUVELLE FAMILLE
		{
			if ( !FamilyModel::labelExists($familyName, ProductModel::getIdMixedKpi()) ) // On v�rifie que le label n'est pas d�j� utilis�
			{
				// Cr�ation de la famille
				if(FamilyModel::getNbFamilies(ProductModel::getIdMixedKpi())==0)
					$mainFamily = 1;
				else
					$mainFamily = 0;
				$idFamily = $mixedKpiModel->createFamily($familyName,$mainFamily);
			}
			else // Si le label est d�j� utilis�
			{
				$messageError = __T('A_E_SETUP_MIXED_KPI_FAMILY_LABEL_ALREADY_EXISTS');
			}
		}
		else // MISE A JOUR
		{
			if ( !FamilyModel::labelExists($familyName, ProductModel::getIdMixedKpi(), $idFamily) ) // On v�rifie que le label n'est pas d�j� utilis�
			{
				// Mise � jour du label
				$familyModel = new FamilyModel($idFamily, ProductModel::getIdMixedKpi());
				$familyModel->updateLabel($familyName);
			}
			else // Si le label est d�j� utilis� par une autre famille autre que la famille courante
			{
				$messageError = __T('A_E_SETUP_MIXED_KPI_FAMILY_LABEL_ALREADY_EXISTS');
			}
		}
	}
	
	// Information sur les na
	$selectedFamilies = array();
	if ( !empty($_POST['familybox']) )
	{
		foreach($_POST['familybox'] as $key => $val)
		{
			$selectedFamilies[substr($val,0,strpos($val,'-'))][] = substr($val,strpos($val,'-')+1);
		}
		if ( $idFamily )
		{
			// On v�rifie que l'on a bien des NA en communs
			if ( FamilyModel::getCommonNaBetweenFamilyAndProducts($selectedFamilies) )
			{
				// mise � jour des familles de Na de la famille
				$mixedKpiModel->updateSelectedFamilies($idFamily, $selectedFamilies);
				$mixedKpiModel->configureFlatFileLib();
			}
			else
			{
				$messageError .= (empty($messageError) ? '' : '<br>').__T('G_GDR_BUILDER_WARNING_NO_NA_LEVEL_IN_COMMON');
			}
		}
	}
	else // Si aucune famille n'est s�lectionn�e
	{
		$messageError .= (empty($messageError) ? '' : '<br>').__T('A_E_SETUP_MIXED_KPI_NO_FAMILY_SELECTED');
	}
	
	// Si tous est ok
	if ( empty($messageError) )
	{
		if ( $idFamily )
		{
			// on configure maintenant les NA
			$message = __T('A_E_SETUP_MIXED_KPI_FAMILY_UPDATED');
			$retour=1;
			include('edit_na.php');
		}
		else
		{
			$message = __T('A_E_SETUP_MIXED_KPI_NO_FAMILY_CREATION_FAILLED');
			$retour=0;
			include('family_list.php');
		}
	}
	else
	{
		$retour=1;
		include('family_add.php');
	}
	
}
elseif(isset($_GET['editFamily'])||isset($_POST['editFamilies']))
{
	// on modifie les familles de NA s�lectionn�es pour une famille et son nom
	// 02/12/2009 BBX : l'id family peut provenir de GET mais aussi de POST. BZ 13174
	$idFamily = isset($_GET['idFamily']) ? $_GET['idFamily']: $_POST['idFamily'];
	$retour=1;
	include('family_add.php');
}
elseif(isset($_POST['saveNa']))
{
	$messageError = '';
	if ( !empty($_POST['family_min']) )
	{
		// enregistrement du NA Min
		FamilyModel::updateAggregationMin($_POST['idFamily'], $idProductMixedKPI, $_POST['family_min']);
		
		// on pr�pare le tableau des agr�gations
		$tableauAgreg = array();
		$tableauAgreg[$_POST['family_min']] = $_POST['family_min'];

		if(isset($_POST['family_used']))
		{
			$naCommon = $mixedKpiModel->getCommonNaBetweenFamilyAndProducts($_POST['idFamily']);
			// On construit un tableau qui servira si on a un des NA qui n'a pas d'agr�gation source de d�finit.
			// Permet de s�lectionner le NA choisit par l'utilisateur en cas d'erreur
			$naLabelList = array();
			foreach($_POST['family_used'] as $aggregCode)
			{
				$naLabelList[$aggregCode] = array();
				if( !empty($_POST[$aggregCode.'_aggregation']) )
				{
					$naLabelList[$aggregCode][] = $_POST[$aggregCode.'_aggregation'];
					$tableauAgreg[$aggregCode] = $_POST[$aggregCode.'_aggregation'];
				}
				else
				{
					if ( $aggregCode != $_POST['family_min'] )
						$messageError .= (empty($messageError) ? '' : '<br>').__T('A_E_SETUP_MIXED_KPI_NO_NA_SOURCE_SELECTED', $naCommon[$aggregCode]);
					else
						$naLabelList[$aggregCode][] = $aggregCode;
				}	
			}
			
			// Test si les chemins d'agr�gation d�finit par l'utilisateur sont conh�rents
			$tableNaRankLevel = array();
			FamilyModel::attribueNaRankLevel($_POST['family_min'],$tableauAgreg,1,1,$tableNaRankLevel);
			if ( count($tableNaRankLevel) != count($tableauAgreg) )
			{
				$messageError .= (empty($messageError) ? '' : '<br>').__T('A_E_SETUP_MIXED_KPI_PATH_NA_INVALID');
			}
		}
		if ( empty($messageError) )
		{
			// on enregistre les NA / NAsource
			FamilyModel::updateNA($_POST['idFamily'], $idProductMixedKPI,$tableauAgreg,$_POST['family_min'],  $mixedKpiModel->getCommonNaBetweenFamilyAndProducts($_POST['idFamily']));
			FamilyModel::updateGroupTableTime($_POST['idFamily'], $idProductMixedKPI);
			$mixedKpiModel->launchDeploy();
			
			// G�n�re et envoie les fichiers CFG si n�cessaire pour chaque produit
			$cfg = new MixedKpiCFG();
			$cfg->setTaMin($mixedKpiModel->getTaMin());
			$cfg->generateAndSendForAllProducts();
		}
	}
	else
	{
		$messageError = __T('A_E_SETUP_MIXED_KPI_NO_NA_MIN_SELECTED');
	}	
	
	if ( empty($messageError) )
	{
		$message='The family has been updated with the NA';
		$retour=0;
		include('family_list.php');
	}
	else
	{
		$retour=1;
		include('edit_na.php');
	
	}
}
elseif(isset($_POST['saveCounters'])) // Enregistrement des compteurs s�lectionn�s
{
	$countersSelected = array();
	if ( !empty($_POST['hidden_selected']) )
	{
		$list = explode('|', $_POST['hidden_selected']);
		foreach ( $list as $oneCounter )
		{
			// 29/03/2010 BBX : récupération des compteur via leurs ID
			$_ = explode('-', $oneCounter);
			// $_[0] = id du produit
			// $_[1] = id du compteur
			$countersSelected[$_[0]][] = $_[1];
		}
	}
	// 11:46 15/12/2009 GHX
	// BZ 13137
	if ( count($countersSelected) > 0 )
	{
		if ( $mixedKpiModel->updateListCounters($_POST['idFamily'], $countersSelected) )
		{	// si des modifications sur la liste des Kpi s�lectionn�s ont �t� effectu�es
			$message = __T('A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS');
			
			// G�n�re et envoie les fichiers CFG si n�cessaire pour chaque produit
			$cfg = new MixedKpiCFG();
			$cfg->setTaMin($mixedKpiModel->getTaMin());
			$cfg->generateAndSendForAllProducts();
			// on r�cup�re les erreurs qui ont pu appara�tre
			$messageError = $mixedKpiModel->getErrors();
		}
		else
		{
			$messageError = $mixedKpiModel->getErrors();
			// m�me si aucune modification n'a �t� effectu�e, il a pu y avoir des erreurs (suppression impossible...)
		}
	}
	else
	{
		$messageError = __T('A_E_SETUP_MIXED_KPI_NO_RAW_SELECTED');
	}

	$retour=1;
	include('edit_counters.php');
}
elseif(isset($_POST['saveKpis'])) // Enregistrement des KPI s�lectionn�s
{
     
	$kpisSelected = array();
	if ( !empty($_POST['hidden_selected']) ){
		$list = explode('|', $_POST['hidden_selected']);
		foreach ( $list as $oneKpi )
		{
			$_ = explode('-', $oneKpi);
			// $_[0] = id du produit
			// $_[1] = id de la famille
			// $_[2] = nom du compteur
			$kpisSelected[$_[0]][$_[1]][] = $_[2];
		}
	}
	if ( $mixedKpiModel->updateListKpis($_POST['idFamily'], $kpisSelected) ){
		// si des modifications sur la liste des Kpi s�lectionn�s ont �t� effectu�es
		$message = __T('A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS');
		
		// G�n�re et envoie les fichiers CFG si n�cessaire pour chaque produit
		$cfg = new MixedKpiCFG();
		$cfg->setTaMin($mixedKpiModel->getTaMin());
		$cfg->generateAndSendForAllProducts();
		// on r�cup�re les erreurs qui ont pu appara�tre
		$messageError = $mixedKpiModel->getErrors();
	}
	else{
		$messageError = __T('A_E_SETUP_MIXED_KPI_NO_KPI_SELECTED') . '<br />' . $mixedKpiModel->getErrors();
		// m�me si aucune modification n'a �t� effectu�e, il a pu y avoir des erreurs (suppression impossible...)
		
	}
	
	$retour=1;
	include('edit_kpi.php');
}
elseif(isset($_POST['saveSelectedDashboards'])){
	if ( isset($_POST['selectedDash']) && count($_POST['selectedDash']) > 0 )
	{
		$prefix = $mixedKpiModel->getPrefixIdDashboard();
		$selectedDash = new SelectedDashboard($idProductMixedKPI);
		// Boucle sur tous les dahsboards s�lectionn�es
		foreach ( $_POST['selectedDash'] as $idDash )
		{
			$selectedDash->duplicate($idDash, $prefix);
		}
		// 16:20 19/11/2009 GHX
		// Ajout de l'appel de fonction
		$selectedDash->checkConfigGraph();
	}
	
	include('edit_dashboards.php');
	$retour=1;
}
// maj 02/04/2010 - DE Mixed KPI : Synchronisation des compteurs // Affichage du résultat de la synchro
elseif( isset($_POST['synchroResult']) )
{

    if( $_POST['synchroResult'] == "1" )
        $message = __T("A_SETUP_MIXED_KPI_EDIT_COUNTERS_SYNCHRO_IS_OK");
    else
        $messageError = __T("A_SETUP_MIXED_KPI_EDIT_COUNTERS_SYNCHRO_ERROR", $_POST['SynchroMsgError'] );

    $retour=1;

    include('edit_counters.php');
}
else{
	$retour=0;
	include('family_list.php');
}
if($retour==1){
	// 11/12/2009 BBX
	// On met le lien de retour en haut sur chaque script
	// On g�re en JS l'affichage ou non du lien. BZ 13172
	echo '<script>$("BackToMainPage").setStyle({display:"block"});</script>';
}
?>
</body>
</html>