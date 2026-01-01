<?php
/*
	23/06/2009 GHX
		- Prise en compte d'un nouveau paramètre pour la fonction networkElementSelection::setSaveFieldProperties()
		- Appel de la nouvelle fonction networkElementSelection::setResetButtonProperties()
	28/07/2009 GHX
		- Prise en compte de l'icone de chargements des préférences utilsateurs
	30/10/2009 GHX
		- Suppression du exit sur le sélecteur n'a pas de NA sinon impossible de configurer le sélecteur d'une homepage user
	19/02/2010 NSE bug 14386 :
		- ajout d'une condition pour cacher le NA sélecteur nb NA == 0.
	29/03/2010 NSE bz 14592 :
		- ajout de la zone de texte à modifier pour le passage OT/ONE  (nombre d'éléments)
       16/08/2010 NSE DE Firefox bz 16912 : ajout de id
   17/08/2010 MMT
      - bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over)
   06/06/2011 MMT
      -DE 3rd Axis. remplace le combo de selection unique par la  widget networkElementSelection
   27/07/2011 MMT
  		-Bz 22896 add 3rd axis preferences session variable
*
*/
?>
<?php
/**
*	Ce fichier génère la boite "Network Aggregation" du sélecteur.
*
*	Les différents éléments de cette boite sont :
*		- na_level
*		- 3emeaxe
*		- nelsel
*		- top
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

// ex: $params = array('hide' => 'ta_level date hour period')

// $to_hide est une chaine qui contient tous les éléments à NE PAS afficher dans la boite.
$to_hide = ' '.$params['hide'];

//		==========	DATA		==========
//	Les données qui servent à alimenter la boite "Network Aggregation" du selecteur

// NA levels : la liste des NA levels
$na_levels = isset($selecteur_values[0]) ? $selecteur_values[0] : Array();
// na2na : le tableau permettant de savoir : pour un na_level, quels accordéons doivent être affichés dans le network element selecteur
$na2na	= isset($selecteur_values[1]) ? $selecteur_values[1] : Array();
// axe3 options : liste du premier menu select axe 3
$axe3_options = isset($selecteur_values[2]) ? $selecteur_values[2] : Array();
// defaults values for this box : là encore, elle sont choisies "en dur", il faudra créer les requêtes permettant de connaître ces valeurs
$defaults = isset($selecteur_values[3]) ? $selecteur_values[3] : Array();
$this->setDefaults($defaults);

//		==========	DISPLAY selecteur		==========
?>

<!-- on cache les messages qui doivent être affichés par les .js, et pour lesquels ont ne peut pas utiliser __T() directement dans les .js -->
<div style="display:none;" id="message_SELECTEUR_NEL_SELECTION"><?= __T('SELECTEUR_NEL_SELECTION') ?></div>
<div style="display:none;" id="message_SELECTEUR_NO_RESPONSE"><?= __T('SELECTEUR_NO_RESPONSE') ?></div>
<div style="display:none;" id="message_SELECTEUR_APPLICATION_CANT_ACCESS_TO"><?= __T('SELECTEUR_APPLICATION_CANT_ACCESS_TO') ?></div>
<div style="display:none;" id="message_SELECTEUR_RAW_KPI_FILTER"><?= __T('SELECTEUR_RAW_KPI_FILTER') ?></div>
<div style="display:none;" id="message_U_SELECTEUR_NO_ELEMENT"><?= __T('U_SELECTEUR_NO_ELEMENT') ?></div>

<script type="text/javascript" src="<?=URL_SELECTEUR?>js/dashboard_NA.js"></script>

<script type="text/javascript">
	// Correction du bug 11257 : Initialisation de l'id du produit pour na box axe3 du GIS
	id_product = '';
	url_get_NA_selected	= "<?=URL_SELECTEUR?>php/get_NA_selected.php";
	url_get_NA_axe3	= "<?=URL_SELECTEUR?>php/selecteur.ajax.php";
	url_get_NA_session	= "<?=URL_SELECTEUR?>php/get_NA_session.php";
	url_selecteur_rep_php	= "<?=URL_SELECTEUR?>php/";
	na2na = {};
	<?php	foreach ($na2na as $na => $na_list) 	echo "\nna2na['$na'] = ['".implode($na_list,"','")."'];";		?>
</script>
<!--	na_level	-->
<?php if (!strpos($to_hide,'na_level') )  { ?>
	<div class="selecteur" id="selecteur_na_level_div" <?php if(!strpos($to_hide,'nelsel') && !strpos($to_hide,'3emeaxe')) echo 'style="float:left;"';?>>
		<select id="selecteur_na_level" name="selecteur[na_level]" class="zoneTexteStyleXP"
			onchange="getNumberOfNa(this.options[this.selectedIndex].value, 'dashboardnb_elements', '<?=URL_SELECTEUR?>php/');updateNelSelecteur();">
			<?php foreach ($na_levels as $na => $na_label) { ?>
				<option value="<?php echo $na ?>" <?php if ($na == $this->selecteur['na_level']) echo "selected='selected'"; ?>><?= $na_label ?></option>
			<?php } ?>
		</select>
	</div>
<?php } 
// 06/06/2011 MMT DE 3rd Axis inclu les scripts pour networkElementSelection js/css/php si 1er ou 3eme axe est affiché
// factorisation des styles
if (!strpos($to_hide,'nelsel') || (!strpos($to_hide,'3emeaxe'))){?>

		<link rel="stylesheet" href="<?=URL_NETWORK_ELEMENT_SELECTION?>css/networkElementSelection.css" type="text/css">
		<script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/prototype/controls.js"></script>
		<script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/networkElementSelection.js"></script>
		<style type="text/css">
			#img_select_na, #img_select_na_axe3 { padding-top:5px; margin-left:2px; height:16px; width:20px; cursor:pointer;float:left;}
			.bt_off { background: url(<?=$niveau0?>images/icones/select_na_on.png) left no-repeat;}
			.bt_on { background: url(<?=$niveau0?>images/icones/select_na_on_ok.png) left no-repeat;}
		</style>
<?php
	include_once(MOD_NETWORK_ELEMENT_SELECTION.'class/networkElementSelection.class.php');
}

// on crée le NE selecteur s'il n'est pas demandé qu'il soit caché et si le nombre de NA est supérieur à 0
	if (!strpos($to_hide,'nelsel') and count($na_levels)> 0 ) { ?>
	<div>
	<!-- 16/08/2010 NSE DE Firefox : ajout float left pour éviter que le filtre ne se promène partout -->
   <!-- 04/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(this.alt_on_over) -->
		<div id="img_select_na" class="bt_<?php if ($this->selecteur['nel_selecteur']) { ?>on<?php } else { ?>off<?php } ?>"
			onmouseover="popalt(this.getAttribute('alt_on_over'));"
			onmouseout="kill()"
			alt_on_over="<?= __T('SELECTEUR_NEL_SELECTION') ?>">
		</div>
		<?php		
			// Nouvelle instance de networkElementSelection
			$neSelection = new networkElementSelection();

			// On définit le type de bouton des éléments réseau
			$neSelection->setButtonMode('checkbox');

			// Initialisation du titre de la fenêtre.
			$neSelection->setWindowTitle(__T('SELECTEUR_NEL_SELECTION'));

			// Debug à 0
			$neSelection->setDebug(0);

			// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
			$neSelection->setOpenButtonProperties('img_select_na', 'bt_on', 'bt_off');

			// 15:18 23/06/2009 GHX
			// Spécifie une fonction JS qui doit être appelé quand on clique sur reset
			$neSelection->setResetButtonProperties('resetSessionNelSelecteur()');

			// Ajout de l'icône des favoris
			$neSelection->addIcon(__T('U_SELECTEUR_LABEL_LOAD_FAVORITES'),'favorite_icon','loadFavoritesNetworkElements()');

			// On définit dans quel champ la sauvegarde sera effectuée.
			// 09:38 23/06/2009 GHX
			// Ajout d'un nouveau paramètre à la fonction
			$neSelection->setSaveFieldProperties('nel_selecteur', $this->selecteur['nel_selecteur'], '|s|', 0, "selecteur[nel_selecteur]", 'updateSessionNelSelecteur()');

			// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
			$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_NA_selected.php?na=$na");
			// On ajoute des onglets

			foreach ($na_levels as $na => $na_label)
				$neSelection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=6&idT=$na&id_page='+_dashboard_id_page+'",URL_SELECTEUR."php/selecteur.ajax.php?action=3&idT=$na");
			// Génération de l'IHM.
			$neSelection->generateIHM();
		?>
	</div>

	<script type="text/javascript">
		<?php if (!strpos($to_hide,'na_level')) { ?>
			updateNelSelecteur();
		<?php } ?>
		if ($('nel_selecteur')) {
		networkElementSelectionSaveHook();
		}
	</script>
<?php }
// 19/02/2010 NSE bug 14386 : le NA sélecteur n'est caché que si le nb de NA == 0
	elseif(count($na_levels)== 0) {
	?>
	<script>
		$('selecteur_na_level_div').style.display='none';
	</script>
	<?
	}
?>

<!-- 3eme Axe -->
<?php if (!strpos($to_hide,'3emeaxe')) {
	// 06/06/2011 MMT DE 3rd Axis. remplace le combo de selection unique par la  widget networkElementSelection
	$jsAxe3Prefix = 'axe3_'; // htmlidprefix utilisé dans les JS de networkElementSelection
	?>
	<div class="selecteur" style="float: left;clear:left">
		<select name="selecteur[axe3]" id="selecteur_axe3" onchange="updateNelSelecteur('<?=$jsAxe3Prefix?>')" >
			<?php foreach ($axe3_options as $value => $label) { ?>
				<option <?php if ($value == $this->selecteur['axe3']) { ?> selected="selected" <?php } ?> value="<?=$value?>"><?=$label ?></option>
			<?php } ?>
		</select>
	</div>
	<div>
		<div id="img_select_na_axe3" class="bt_<?php if ($this->selecteur['axe3_2']) { ?>on<?php } else { ?>off<?php } ?>"
			onmouseover="popalt(this.getAttribute('alt_on_over'));"
			onmouseout="kill()"
			alt_on_over="<?= __T('SELECTEUR_NEL_SELECTION') ?>">
		</div>

		<?php
		// Nouvelle instance de networkElementSelection
		$neAxe3Selection = new networkElementSelection();

		// On définit le type de bouton des éléments réseau
		$neAxe3Selection->setButtonMode('checkbox');

		// Initialisation du titre de la fenêtre.
		$neAxe3Selection->setWindowTitle(__T('SELECTEUR_NEL_SELECTION'));

		// Debug à 0
		$neAxe3Selection->setDebug(0);

		// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
		$neAxe3Selection->setOpenButtonProperties('img_select_na_axe3', 'bt_on', 'bt_off');

		$neAxe3Selection->setHtmlIdPrefix('axe3');

		// Spécifie une fonction JS qui doit être appelé quand on clique sur reset
		$neAxe3Selection->setResetButtonProperties('resetSessionNelSelecteurAxe3()');

		// Ajouter le code suivant pour ajouter favoris 3eme axe
		//$neAxe3Selection->addIcon(__T('U_SELECTEUR_LABEL_LOAD_FAVORITES'),'favorite_icon','loadFavoritesNetworkElementsAxe3()');

		// On définit dans quel champ la sauvegarde sera effectuée.
		// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
		$neAxe3Selection->setSaveFieldProperties('axe3_2', $this->selecteur['axe3_2'], '|s|', 0, "selecteur[axe3_2]",'updateSessionNelSelecteurAxe3()');

		// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
		$neAxe3Selection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_NA_selected.php?na=$na");
		// On ajoute des onglets

		foreach ($axe3_options as $na => $na_label)
			$neAxe3Selection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=6&idT=$na&id_page='+_dashboard_id_page+'",URL_SELECTEUR."php/selecteur.ajax.php?action=3&idT=$na");
		// Génération de l'IHM.
		$neAxe3Selection->generateIHM();

		?>
	</div>

	<script type="text/javascript">
		if ($('axe3_2')) {
			//initialisation avec les elements selectionnés
			updateNelSelecteur('<?=$jsAxe3Prefix?>');
			networkElementSelectionSaveHook('<?=$jsAxe3Prefix?>');
		}
	</script>

<!--	TOP	-->
<?php
}
if (!strpos($to_hide,'top')) {
	// 06/06/2011 MMT DE 3rd Axis  Display the 3rd axis selecteur on the right of the NA selecteur?>
	<div class="selecteur" style="clear: both;">
		<select name="selecteur[top]">
		<?php for ($i=1; $i <= $this->max_top; $i++) { ?>	<option <?php if ($i == $this->selecteur['top']) { ?> selected="selected" <?php } ?>><?php echo $i; ?></option>	<?php } ?>
		</select>
		<?php if ($params['top'] == 'TON') {
			// 29/03/2010 NSE bz 14592 : ajout de la zone de texte à modifier pour le passage OT/ONE ?>
			(<span id="dashboardnb_elements_label"><?= __T('SELECTEUR_TOP_OVER_NETWORK')?></span><span id="dashboardnb_elements"></span>)
		<?php } else { ?>
			(<span id="dashboardnb_elements_label"><?= __T('SELECTEUR_TOP_OVER_TIME')?></span><span id="dashboardnb_elements"></span>)
		<?php } ?>
	</div>

	<script type="text/javascript">
		getNumberOfNa($F('selecteur_na_level'), 'dashboardnb_elements', '<?=URL_SELECTEUR?>php/');
	</script>
<?php } ?>

<script type="text/javascript">
    // Si la méthode initFixedHourMode permettant d'initialiser le formulaire du
    // mode Fixed Hour existe, on lance cette initialisation. Cette appel doit
    // impérativement s'effectuer ici afin que les NA/NA3 soit bien chargés
    if( typeof initFixedHourMode == 'function' ){
        initFixedHourMode();
    }
</script>
