<?php
/**
 *
 * 06/06/2011 MMT DE 3rd Axis :remplacement du selecteur 3eme axe par la widget 1er axe
 * 02/11/2011 ACS BZ 23769 Wrong format in the list of selected elements
 *
 *
*	Ce fichier génère la boite "Network Aggregation" du sélecteur pour Investigation Dashboard
*
*	Les différents éléments de cette boite sont :
*		- 3emeaxe
*		- nelsel
*
*	@author	SPS - 28/05/2009
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*/


// $to_hide est une chaine qui contient tous les éléments à NE PAS afficher dans la boite.
$to_hide = ' '.$params['hide'];
$id_prod = $params['product'];
//06/06/2011 MMT DE 3rd Axis ajout recuperation famille
$family = $_GET['family'];

$url_investigation_dashboard = NIVEAU_0."dashboard_investigation/";
$mod_investigation_dashboard = REP_PHYSIQUE_NIVEAU_0."dashboard_investigation/";

//		==========	DATA		==========
//	Les données qui servent à alimenter la boite "Network Aggregation" du selecteur

//le tableau $selecteur_values correspond au tableau que l'on donne a la creation de la boite
// NA levels : la liste des NA levels
$na_levels = isset($selecteur_values[0]) ? $selecteur_values[0] : Array();
// axe3 options : liste du premier menu select axe 3
$axe3_options = isset($selecteur_values[1]) ? $selecteur_values[1] : Array();

//		==========	DISPLAY selecteur		==========
?>
<!-- on cache les messages qui doivent être affichés par les .js, et pour lesquels ont ne peut pas utiliser __T() directement dans les .js -->
<div style="display:none;" id="message_SELECTEUR_NEL_SELECTION"><?= __T('SELECTEUR_NEL_SELECTION') ?></div>
<div style="display:none;" id="message_SELECTEUR_NO_RESPONSE"><?= __T('SELECTEUR_NO_RESPONSE') ?></div>
<div style="display:none;" id="message_SELECTEUR_APPLICATION_CANT_ACCESS_TO"><?= __T('SELECTEUR_APPLICATION_CANT_ACCESS_TO') ?></div>
<div style="display:none;" id="message_SELECTEUR_RAW_KPI_FILTER"><?= __T('SELECTEUR_RAW_KPI_FILTER') ?></div>
<div style="display:none;" id="message_U_SELECTEUR_NO_ELEMENT"><?= __T('U_SELECTEUR_NO_ELEMENT') ?></div>
<style type="text/css">
		#img_select_na { padding-top:5px; margin-left:2px; height:16px; width:20px; cursor:pointer;}
		.bt_off { background: url(<?=$niveau0?>images/icones/select_na_on.png) left no-repeat;}
		.bt_on { background: url(<?=$niveau0?>images/icones/select_na_on_ok.png) left no-repeat;}
		<?php
		// 01/12/2009 BBX : ajout de la classe qui gère le bouton favori. BZ 11482
		?>
		.favorite_icon{
			background: transparent url(<?=URL_NETWORK_ELEMENT_SELECTION?>images/star.png) no-repeat 0 0; 
			border: none;
			cursor:pointer;
			width:16px;
			height:16px;
		}
</style>
<script type="text/javascript" src="<?=URL_SELECTEUR?>js/investigation_NA.js"></script>

<?php 
/*
 * 27/05/2009 SPS variables JS*/
// 02/12/2009 BBX : ajout des variables family_name, url_selecteur_rep_php et url_get_NA_session. BZ 11482

//06/06/2011 MMT DE 3rd Axis utilisation de networkElementSelection pour gestion de l'url savehook
?>
<script type="text/javascript">
	var investigation_NA_axe3 = "<?=URL_SELECTEUR?>php/selecteur.ajax.php";
	var id_product = "<?= $id_prod?>";
	var family_name = "<?=$family?>";
	var url_selecteur_rep_php = "<?=URL_SELECTEUR?>php/";
	var url_get_NA_session	= "<?=URL_SELECTEUR?>php/get_investigation_NA_session.php";
</script>
<?php /*url utilisee par la fonction networkElementSelectionSaveHook */?>
<?php
// 22/11/2011 BBX
// BZ 23263 : pas d'élément value sur un div !
?>
<div id="investigation_nel_url" style="display:none;"><?=URL_SELECTEUR?>php/get_investigation_NA_selected.php</div>

<?php	if (!strpos($to_hide,'nelsel') || (!strpos($to_hide,'3emeaxe'))){?>
		<link rel="stylesheet" href="<?=$url_investigation_dashboard?>css/networkElementSelection.css" type="text/css">
		<script type="text/javascript" src="<?=$url_investigation_dashboard?>js/prototype/controls.js"></script>
		<script type="text/javascript" src="<?=$url_investigation_dashboard?>js/networkElementSelection.js"></script>
<?php }
	include_once($mod_investigation_dashboard.'class/networkElementSelection.class.php');

	//06/06/2011 MMT DE 3rd Axis précède le selecteur 1er axe par le label 'Primary'
	if (!strpos($to_hide,'nelsel')) { ?>
		<table><tr><td><?=__T('A_INVESTIGATION_DASHBOARD_1ST_AXIS_SELECTOR_LABEL')?></td>
		<td>
		<div>
			<?php
				// Nouvelle instance de networkElementSelection
				$neSelection = new networkElementSelection();

				$neSelection->setHtmlIdPrefix('investigation_nel');

				// On définit le type de bouton des éléments réseau
				$neSelection->setButtonMode('checkbox');

				// Initialisation du titre de la fenêtre.
				// 06/06/2011 MMT DE 3rd Axis précède le titre par le label 'Primary'
				$neSelection->setWindowTitle(__T('A_INVESTIGATION_DASHBOARD_1ST_AXIS_SELECTOR_LABEL')." ".__T('SELECTEUR_NEL_SELECTION'));

				// Debug à 0
				$neSelection->setDebug(0);

				// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
				$neSelection->setOpenButtonProperties('investigation_nel_img', 'bt_on', 'bt_off');

				// 02/12/2009 BBX
				// Ajout de l'icône des favoris. BZ 11482
				$neSelection->addIcon(__T('U_SELECTEUR_LABEL_LOAD_FAVORITES'),'favorite_icon','loadFavoritesNetworkElements()');

				// On définit dans quel champ la sauvegarde sera effectuée.
				$neSelection->setSaveFieldProperties('investigation_nel_selecteur', $this->selecteur['investigation_nel_selecteur'], '|s|', 0, "selecteur[investigation_nel_selecteur]");
				//06/06/2011 MMT DE 3rd Axis utilisation de networkElementSelection pour gestion de l'url savehook
				$neSelection->setSelectionSaveHookURL(URL_SELECTEUR."php/get_investigation_NA_selected.php");

				// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
				$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_investigation_NA_selected.php?na=$na&product=$id_prod");
				// On ajoute des onglets
				foreach ($na_levels as $na => $na_label)
					$neSelection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=9&idT=$na&product=$id_prod",URL_SELECTEUR."php/selecteur.ajax.php?action=10&idT=$na&product=$id_prod");
				// Génération de l'IHM.
				$neSelection->generateIHM();
			?>
				</div>
		</td></tr></table>
	<?php 
	if ($this->selecteur['investigation_nel_selecteur']) {
		?>
	<script type="text/javascript">
		$('investigation_nel_img').className = 'bt_on';
		
	</script>	
<?php 
	}
} ?>

<!-- 3eme Axe -->
<?php
//06/06/2011 MMT DE 3rd Axis remplacement du selecteur 3eme axe par la widget 1er axe
if (!strpos($to_hide,'3emeaxe')) {

	// 3rd axis label : recuparation du nom de l'axe  via get_axe3_information_from_family
	$familyInfo = get_axe3_information_from_family($family,$id_prod);
	if(array_key_exists('axe_type_label', $familyInfo)){
		$axisLabel = $familyInfo['axe_type_label'][0];
	}
	// si label axe existe on l'affiche entre parenthèse apres "secondary"
	$fullLabel = __T('A_INVESTIGATION_DASHBOARD_3RD_AXIS_SELECTOR_LABEL');
	if(!empty($axisLabel)){
		$fullLabel .= " ($axisLabel)";
	}
	// prefix des champs selection 3eme axe
	$jsAxe3Prefix = 'axe3_';
		?>
<table><tr><td><?=$fullLabel?></td>
	<td>
	<div>
		<style type="text/css">
		#img_select_na_axe3{ padding-top:5px; margin-left:2px; height:16px; width:20px; cursor:pointer;}
		.bt_off { background: url(<?=$niveau0?>images/icones/select_na_on.png) left no-repeat;}
		.bt_on { background: url(<?=$niveau0?>images/icones/select_na_on_ok.png) left no-repeat;}
		</style>
			<?php
			// Nouvelle instance de networkElementSelection
			$neAxe3Selection = new networkElementSelection();

			// On définit le type de bouton des éléments réseau
			$neAxe3Selection->setButtonMode('checkbox');

			// Initialisation du titre de la fenêtre.
			$neAxe3Selection->setWindowTitle(__T('A_INVESTIGATION_DASHBOARD_3RD_AXIS_SELECTOR_LABEL')." ".__T('SELECTEUR_NEL_SELECTION'));

			// Debug à 0
			$neAxe3Selection->setDebug(0);

			// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
			$neAxe3Selection->setOpenButtonProperties($jsAxe3Prefix.'img', 'bt_on', 'bt_off');

			$neAxe3Selection->setHtmlIdPrefix('axe3');

			// On définit dans quel champ la sauvegarde sera effectuée.
			$fieldName = $jsAxe3Prefix.'selecteur';
			$neAxe3Selection->setSaveFieldProperties($fieldName, $this->selecteur[$fieldName], '|s|', 0, "selecteur[$fieldName]");
			$neAxe3Selection->setSelectionSaveHookURL(URL_SELECTEUR."php/get_investigation_NA_selected.php");

			// 02/11/2011 ACS BZ 23769 Wrong format in the list of selected elements
			// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
			$neAxe3Selection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_investigation_NA_selected.php?na=$na&product=$id_prod");
			// On ajoute des onglets

			foreach ($axe3_options as $na => $na_label)
				$neAxe3Selection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=9&idT=$na&product=$id_prod",URL_SELECTEUR."php/selecteur.ajax.php?action=10&idT=$na&product=$id_prod");
			// Génération de l'IHM.
			$neAxe3Selection->generateIHM();
			?>
		</div>

		<?php
		// colore le selecteur en vert si selection existe
		if ($this->selecteur[$fieldName]) { ?>
		<script type="text/javascript">
			$('<?=$jsAxe3Prefix?>img').className = 'bt_on';
		</script>
<?php }?>

	</td></tr>
</table>
<?php } ?>

