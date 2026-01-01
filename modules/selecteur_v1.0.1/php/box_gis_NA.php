<?php
/**
*	Ce fichier genere la boite "Network Aggregation" du selecteur pour Investigation Dashboard
*
*	Les differents elements de cette boite sont :
*		- 3emeaxe
*		- nelsel
*
*	@author	SPS - 28/05/2009
*	@version	CB 5.0.0.0
*	@since	CB 5.0.0.0
*/


// $to_hide est une chaine qui contient tous les elements a NE PAS afficher dans la boite.
$to_hide = ' '.$params['hide'];
$id_prod = $params['product'];


$url_investigation_dashboard = NIVEAU_0."dashboard_investigation/";
$mod_investigation_dashboard = REP_PHYSIQUE_NIVEAU_0."dashboard_investigation/";

//		==========	DATA		==========
//	Les donnees qui servent a alimenter la boite "Network Aggregation" du selecteur

//le tableau $selecteur_values correspond au tableau que l'on donne a la creation de la boite
// NA levels : la liste des NA levels
$na_levels = isset($selecteur_values[0]) ? $selecteur_values[0] : Array();
// axe3 options : liste du premier menu select axe 3
$axe3_options = isset($selecteur_values[2]) ? $selecteur_values[2] : Array();

//		==========	DISPLAY selecteur		==========
?>

<body>
<!-- on cache les messages qui doivent etre affiches par les .js, et pour lesquels ont ne peut pas utiliser __T() directement dans les .js -->
<div style="display:none;" id="message_SELECTEUR_NEL_SELECTION"><?= __T('SELECTEUR_NEL_SELECTION') ?></div>
<div style="display:none;" id="message_SELECTEUR_NO_RESPONSE"><?= __T('SELECTEUR_NO_RESPONSE') ?></div>
<div style="display:none;" id="message_SELECTEUR_APPLICATION_CANT_ACCESS_TO"><?= __T('SELECTEUR_APPLICATION_CANT_ACCESS_TO') ?></div>
<div style="display:none;" id="message_SELECTEUR_RAW_KPI_FILTER"><?= __T('SELECTEUR_RAW_KPI_FILTER') ?></div>
<div style="display:none;" id="message_U_SELECTEUR_NO_ELEMENT"><?= __T('U_SELECTEUR_NO_ELEMENT') ?></div>
<style type="text/css">
		#img_select_na { padding-top:5px; margin-left:2px; height:16px; width:20px; cursor:pointer;}
		.bt_off { background: url(<?=$niveau0?>images/icones/select_na_on.png) left no-repeat;}
		.bt_on { background: url(<?=$niveau0?>images/icones/select_na_on_ok.png) left no-repeat;}
</style>
<script>
	var url_get_NA_axe3	= "<?=URL_SELECTEUR?>php/selecteur.ajax.php";
	var gis_nel_url = "<?=URL_SELECTEUR?>php/get_gis_NA_selected.php?labels_only=1&current_selection=<?=$_GET['current_selection']?>";
	var _dashboard_id_page = "";
	var id_product = "<?=$id_prod?>";
</script>
<script type="text/javascript" src="<?=URL_SELECTEUR?>js/gis_NA.js"></script>
    

<!--	na_level	-->
<?php if (!strpos($to_hide,'na_level')) {
    // 13/06/2013 NSE bz 34258 : filter icon under drop down list
    ?>
	<div class="selecteur" id="selecteur_na_level_div" <?php echo 'style="float:left;"';?>>
		<select id="selecteur_na_level" name="selecteur[na_level]" class="zoneTexteStyleXP"
			onchange="updateNelSelecteurGIS();resetNelSelectionGIS();">
			<?php foreach ($na_levels as $na => $na_label) { ?>
				<option value="<?php echo $na ?>" <?php if ($na == $this->selecteur['na_level']) echo "selected='selected'"; ?>><?= $na_label ?></option>
			<?php } ?>
		</select>
	</div>
<?php } 
 /*url utilisee par la fonction networkElementSelectionSaveHook */?>

 <div id="gis_nel_url" style="display:none;" value="<?=URL_SELECTEUR?>php/get_gis_NA_selected.php?product=<?=$id_prod?>&current_selection=<?=$this->selecteur['gis_nel_selecteur']?>&labels_only=1"></div>


<!--	NELSEL : network element selecteur	-->
<?php if (!strpos($to_hide,'nelsel')) { ?>
	<div>

		<link rel="stylesheet" href="<?=$url_investigation_dashboard?>css/networkElementSelection.css" type="text/css">
		<script type="text/javascript" src="<?=$url_investigation_dashboard?>js/prototype/controls.js"></script>
		<script type="text/javascript" src="<?=$url_investigation_dashboard?>js/networkElementSelection.js"></script>
		<?php
		
			include_once($mod_investigation_dashboard.'class/networkElementSelection.class.php');

			// Nouvelle instance de networkElementSelection
			$neSelection = new networkElementSelection();
                        
                        // 16/01/2013 BBX
                        // DE Ne Filter
                        $neSelection->setoldVersion();
			
			$neSelection->setHtmlIdPrefix('gis_nel');
			
			// On definit le type de bouton des elements reseau
			$neSelection->setButtonMode('checkbox');

			// Initialisation du titre de la fenetre.
			$neSelection->setWindowTitle(__T('SELECTEUR_NEL_SELECTION'));
			
			// Debug a 0
			$neSelection->setDebug(0);
			
			// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
			$neSelection->setOpenButtonProperties('gis_nel_img', 'bt_on', 'bt_off');
			
			// On definit dans quel champ la sauvegarde sera effectuee.
			$neSelection->setSaveFieldProperties('gis_nel_selecteur', $this->selecteur['gis_nel_selecteur'], '|s|', 0, "selecteur[gis_nel_selecteur]");
			

			// Definit les proprietes du bouton View current selection content (NB : si la methode n'est pas appelee, le bouton n'est pas affiche).
			$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_gis_NA_selected.php?&product=$id_prod");
			// On ajoute des onglets
			
			foreach ($na_levels as $na => $na_label){
			
				$neSelection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=9&idT=$na&product=$id_prod",URL_SELECTEUR."php/selecteur.ajax.php?action=10&idT=$na&product=$id_prod&{$this->selecteur['na_level']}");
				
			}

			// Generation de l'IHM.
			$neSelection->generateIHM();		
		?>
	</div>
	<?php 
	if ($this->selecteur['gis_nel_selecteur']) {
		?>
	<script type="text/javascript">
		$('gis_nel_img').className = 'bt_on';
		
	</script>	
<?php 
	}
} ?>

	<script>
		updateNelSelecteurGIS();
	</script>

	<!-- 3eme Axe -->
<?php if (!strpos($to_hide,'3emeaxe')) { ?>
	<div class="selecteur" style="clear: both;">
		<select name="selecteur[axe3]" id="selecteur_axe3" name="selecteur_axe3">
			<?php foreach ($axe3_options as $value => $label) { ?>
				<option <?php if ($value == $this->selecteur['axe3']) { ?> selected="selected" <?php } ?> value="<?=$value?>"><?=$label ?></option>
			<?php } ?>
		</select>
		<select name="selecteur[axe3_2]" id="selecteur_axe3_2">
			<?php foreach ($axe3_options as $value => $label) { ?>
				<option <?php if ($value == $this->selecteur['axe3_2']) { ?> selected="selected" <?php } ?> value="<?=$value?>"><?=$label ?></option>
                 <?php } ?>
		</select>
		<input type="hidden" name="selecteur_axe3_2_hidden" id="selecteur_axe3_2_hidden" value="<?=$this->selecteur['axe3_2']?>" />
		<script type="text/javascript" src="<?=URL_SELECTEUR?>js/dashboard_NA_axe3.js"></script>
	</div>
<?php } ?>