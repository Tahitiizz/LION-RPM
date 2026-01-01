<?php
/**
*	on genere le selecteur de raw/kpi pour Investigation Dashboard 
*
*	Les différents éléments de cette boite sont :
*		- selection des raw/kpi
*		- filter
*
*	@author	SPS - 27/05/2008
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*/


$counters = $selecteur_values;
$maxchar = 40;


$id_product = $params['product'];
$_GET['product'] = $id_product;

$id_family = $params['family'];
$to_hide = " ".$params['hide'];

$type = "raws";
$separator = "@";
$current_selection = ""; 
$label_only = false; 
if( $this->selecteur['gis_counters_selecteur'] ){
	
	$current_selection = $this->selecteur['gis_counters_selecteur'];
		
}

//chemins vers dashboard_investigation
$url_investigation_dashboard = NIVEAU_0."dashboard_investigation/";
$mod_investigation_dashboard = REP_PHYSIQUE_NIVEAU_0."dashboard_investigation/";

//		==========	DATA		==========
//	Les données qui servent à alimenter la boite "Network Aggregation" du selecteur

// NA levels : la liste des NA levels
/*$na_levels = isset($selecteur_values[0]) ? $selecteur_values[0] : Array();
// axe3 options : liste du premier menu select axe 3
$axe3_options = isset($selecteur_values[1]) ? $selecteur_values[1] : Array();
// defaults values for this box : là encore, elle sont choisies "en dur", il faudra créer les requêtes permettant de connaître ces valeurs
$defaults = isset($selecteur_values[2]) ? $selecteur_values[2] : Array();
$this->setDefaults($defaults);
*/
//		==========	DISPLAY selecteur		==========
?>

<!-- on cache les messages qui doivent être affichés par les .js, et pour lesquels ont ne peut pas utiliser __T() directement dans les .js -->
<div style="display:none;" id="message_SELECTEUR_NEL_SELECTION"><?= __T('SELECTEUR_NEL_SELECTION') ?></div>
<div style="display:none;" id="message_SELECTEUR_NO_RESPONSE"><?= __T('SELECTEUR_NO_RESPONSE') ?></div>
<div style="display:none;" id="message_SELECTEUR_APPLICATION_CANT_ACCESS_TO"><?= __T('SELECTEUR_APPLICATION_CANT_ACCESS_TO') ?></div>
<div style="display:none;" id="message_SELECTEUR_RAW_KPI_FILTER"><?= __T('SELECTEUR_RAW_KPI_FILTER') ?></div>
<div style="display:none;" id="message_U_SELECTEUR_NO_ELEMENT"><?= __T('U_SELECTEUR_NO_ELEMENT') ?></div>


<script type="text/javascript" src="<?=URL_SELECTEUR?>js/investigation_NA.js"></script>
<?php /*url utilisee par la fonction networkElementSelectionSaveHook */?>
<div id="gis_counters_url" style="display:none;" value="<?=URL_SELECTEUR?>php/get_counters_selected.php?product=<?=$id_product?>"></div>

<!-- selecteur de raw/kpi	-->
<?php if (!strpos($to_hide,'gis_counters')) { ?>

		<!-- feuille de style pour le selecteur-->
		
		<!-- scripts js -->
		<script type="text/javascript" src="<?=$url_investigation_dashboard?>js/prototype/controls.js"></script>
		<script type="text/javascript" src="<?=$url_investigation_dashboard?>js/networkElementSelection.js"></script>
		
		<?php
		
			include_once($mod_investigation_dashboard.'class/networkElementSelection.class.php');

			// Nouvelle instance de networkElementSelection
			$neSelection = new networkElementSelection();
                        
                        // 17/01/2013 BBX
                        // DE Ne Filter
                        $neSelection->setoldVersion();
			
			$neSelection->setHtmlIdPrefix('gis_counters');
			
			// On définit le type de bouton des éléments réseau
			$neSelection->setButtonMode('checkbox');

			// Initialisation du titre de la fenêtre.
			$neSelection->setWindowTitle(__T('SELECTEUR_RAW_KPI_SELECTION'));
			
			// Debug à 0
			$neSelection->setDebug(0);
			
			// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
			$neSelection->setOpenButtonProperties('gis_counters_img', 'bt_on', 'bt_off');
			
			// On définit dans quel champ la sauvegarde sera effectuée.
			
			$neSelection->setSaveFieldProperties('gis_counters_selecteur', $this->selecteur['gis_counters_selecteur'], '|s|', 0, "selecteur[gis_counters_selecteur]");
			
			// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
			$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_counters_selected.php?separator=$separator&current_selection=$current_selection&product=$id_product");
			// On ajoute des onglets
					
			$neSelection->addTabInIHM('raw','Raw', URL_SELECTEUR."php/selecteur.ajax.php?action=14&type=raw&family=$id_family&product=$id_product",URL_SELECTEUR."php/selecteur.ajax.php?action=15&family=$id_family&product=$id_product");
			$neSelection->addTabInIHM('kpi','KPI', URL_SELECTEUR."php/selecteur.ajax.php?action=14&type=kpi&family=$id_family&product=$id_product",URL_SELECTEUR."php/selecteur.ajax.php?action=15&family=$id_family&product=$id_product");
			
			
			// Génération de l'IHM.
			$neSelection->generateIHM();	
		?>
	</div>
	<?php 
	//si le selecteur contient des valeurs, on change la classe du bouton
	if ($this->selecteur['gis_counters_selecteur']) {
		?>
	<script type="text/javascript">
		$('gis_counters_img').className = 'bt_on';
	</script>	
<?php 
	}
} 