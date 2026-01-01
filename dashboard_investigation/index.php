<?php
/*
	16/07/2009 GHX
		- Définition du autoScale à 1
		- Correction du BZ 10602 [REC][Investigation Dashboard]: manque nom famille en titre
	05/08/2009 GHX
		- Correction du BZ 10906 [REC][T&A Cb 5.0][TP#1][TS#UC16][INVESTIGATION DASHBOARD]: erreur lors du retour au choix des familles pour SAI
	12/08/2009 GHX
		- Modification de la création de l'heure pour le sélecteur, car si on se trouve sur une heure entre 00h et 09h, le sélecteur était incorrect il manquait le zéro devant
	06/06/2011 MMT
      - DE 3rd axis changement du nom de la variable 3eme axe en axe3_selecteur
	09/12/2011 ACS Mantis 837 DE HTTPS support
	22/12/2011 ACS BZ 25213 change family on Slave loose https
*/
?>
<?php
/**
 * page d'accueil d'Investigation Dashboard
 * 
 * @author SPS
 * @date 28/05/2009
 * @version CB 5.0.0.0
 * @package InvestigationDashboard 
 * @see InvestigationDashboard, InvestigationXml, InvestigationModel, SelecteurInvestigation
 **/

session_start();
include_once dirname(__FILE__)."/../php/environnement_liens.php";
require_once(REP_PHYSIQUE_NIVEAU_0."models/InvestigationModel.class.php");
require_once(REP_PHYSIQUE_NIVEAU_0."dashboard_investigation/class/InvestigationDashboard.class.php");
require_once(REP_PHYSIQUE_NIVEAU_0."dashboard_investigation/class/InvestigationXml.class.php");

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
require_once(MOD_SELECTEUR."php/selecteur.class.php");
require_once(MOD_SELECTEUR."php/SelecteurInvestigation.class.php");

include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");

include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");

$family=$_GET['family'];
$product = $_GET['product'];

/* choix de la famille et du produit (utilisation de select_family.class.php)*/
if(!isset($family) || !isset($product) ){
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Investigation Dashboard',false,'',1);
	exit;
}

/*on enleve la famille de l'url*/
// 11:53 05/08/2009 GHX
// Correction du BZ 10906

if (isset($product)) {
	//si on a choisi le produit, on l'affiche sur la page
	// 15:54 16/07/2009 GHX
	// Correction du bug BZ 10602 [REC][Investigation Dashboard]: manque nom famille en titre
	// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	// 22/12/2011 ACS BZ 25213 change family on Slave loose https
	$db = Database::getConnection($product);
	
	$ruri = ProductModel::getCompleteUrlForMasterGui($_SERVER['PHP_SELF'].'?');
	$tmpGet = $_GET;
	unset($tmpGet['family']);
	$ruri .= http_build_query($tmpGet);

	$label_product = $db->getone("select sdp_label from sys_definition_product where sdp_id='".intval($product)."' ");
	$label_family = $db->getone("select family_label from sys_definition_categorie where family='".$family."' ");
	echo "<div style='text-align:center;margin:15px' class='texteGris'>
		Actual product : <strong>".$label_product."</strong>&nbsp;-&nbsp;<strong>".$label_family."</strong>&nbsp;<a href='$ruri' onmouseover=\"popalt('Change family');\"><img src='".NIVEAU_0."images/icones/change.gif' width='20' height='20' alt='Change family' /></a>
	</div>";
}

?>
<link rel='stylesheet' href='<?=NIVEAU_0?>css/graph_style.css' type='text/css'/>

<div id="container" style="width:100%;text-align:center">

<?php
	$defaults = array(
		'ta_level'	=> 'Day',
		'date'	=> date('d/m/Y'),
		// 09:25 12/08/2009 GHX
		// Modificatoin de la création de l'heure
		'hour' 	=> (date("H", mktime(date('H')-1))).":00"
	);
	
	
	// Chargement du selecteur
	$selecteur = new SelecteurInvestigation($product,$family);

	// Si l'appel est correct, continue
	if(!$selecteur->getError())
	{
		// Chargement des valeurs stockées en session
		$selecteur->loadExternalValues($_SESSION['TA']['selecteur']);
		
		// Chargement des valeurs transmises par GET
		$selecteur->loadExternalValues($_GET);
		
		$selecteur->setDefaults($defaults);
		
		// Chargement des valeurs transmises par POST
		$selecteur->getSelecteurFromArray($_POST['selecteur']);
				
		$selecteur->build();

		// Debug
		if (get_sys_debug('dashboard_investigation')) {
			$selecteur->debug();		
		}
	}

	// Sauvegarde de certaines valeurs du selecteur en session 
	$selecteur->saveToSession();

	// Récupération des valeurs du selecteur
	$selecteur_values = $selecteur->getValues();
	
	//si on a envoye des donnees
	if (isset($_POST['selecteur'])) {
		
		try {	
			//on instancie la classe avec le produit et la famille passee en parametre
			$id = new InvestigationDashboard($product, $family);
			//mode debug
			if (get_sys_debug('dashboard_investigation')) {
				$id->setDebugMode();
			}
			//on envoie les valeurs du selecteur
			$id->setSelecteurValues($selecteur_values);
			//on recupere les donnees
			$data = $id->getData();
			
			//on envoie les donnees a la classe qui va creer le xml
			$ix = new InvestigationXml($data);
			$ix->setProduct($_GET['product']);
			$ix->setFamilyInfos($_GET['family']);
			$ix->setNaValues($selecteur_values['investigation_nel_selecteur']);

			// maj 24/11/2009  MPR : Gestion du 3ème axe
			// 06/06/2011 MMT DE 3rd axis changement du nom de la variable 3eme axe en axe3_selecteur
			if( isset($selecteur_values['axe3_selecteur'])){
				$ix->setNaAxe3($selecteur_values['axe3_selecteur']);
			}
			
			$ix->setLstRawKpis($selecteur_values['investigation_counters_selecteur']);
			$ix->setTaLevel($selecteur_values['ta_level']);
			
			
			//on definit les titres
			$ix->setGraphTitle('Investigation Dashboard');
			$ix->setXAxisTitle($selecteur_values['ta_level']);
			
			//on definit les proprietes du graph
			$props = array(
				'width' => '900', 
				'height' => '450', 
				'legend_position' => 'top', 
				'left_axis_label' => 'Values', 
				'right_axis_label' => 'fff', 
				'type' => 'graph', 
				'scale' => 'textlin'
			);
			// 10:15 16/07/2009 GHX
			// Définition des attributes des propriétés ci-dessus
			$attr = array (
				'scale' => array('autoY' => 1, 'autoY2' => 1)
			);
			$ix->setProperties($props, $attr);
			//on construit le xml
			$ix->build();
			
			//on genere l'identifiant du fichier
			$file = md5(uniqid(rand(), true));
			
			if (get_sys_debug('dashboard_investigation')) {
				echo "<p><a href=\"".NIVEAU_0."png_file/".$file.".xml\" target=\"_blank\">fichier xml genere : <b>".$file.".xml</b></a></p>";
			}
			
			//chemin du xml
			$xml = REP_PHYSIQUE_NIVEAU_0.'png_file/'.$file.'.xml';
			//on enregistre le xml cree
			$ix->saveXML($xml);
			//on genere le graph
			echo $id->generateGraph($data, $xml, 'Investigation Dashboard');
			
				
		}
		catch(Exception $ex) {
			//on renvoie une exception si on a pas selectionne un nel ET un raw/kpi
			echo "<div class=\"errorMsg\">".$ex->getMessage()."</div>";
		}
	}
?>

</div>
</body>
</html>

