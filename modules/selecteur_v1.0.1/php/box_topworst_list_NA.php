<?php
/*
	20/07/2009 GHX
		- Correction du BZ 10584 [REC][Alarm history]: sélection des éléments réseau non fonctionnelle
			-> Modification des fichiers PHP appelés pour la sélection des éléments réseaux
			-> Ajout de l'appel au fichier js controls.js
			
	02/12/2009 BBX :
		- Modification du fichier pour gérer la fonction load favorites. BZ 11482

   17/08/2010 MMT
      - bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over)


*/
?>
<?php
/**
*	Ce fichier génère la boite "Network Aggregation" du sélecteur des alarmes top worst
*
*	Les différents éléments de cette boite sont :
*		- na_level
*		- 3emeaxe
*		- nelsel
*
*	@author	MPR - 19/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

// maj 19/11/2008 - MPR : Création du fichier 

// ex: $params = array('hide' => 'ta_level date hour period')
// $to_hide est une chaine qui contient tous les éléments à NE PAS afficher dans la boite.
$to_hide = ' '.$params['hide'];
$id_prod = $params['product'];

//		==========	DATA		==========
//	Les données qui servent à alimenter la boite "Network Aggregation" du selecteur

$na_levels = isset($selecteur_values[0]) ? $selecteur_values[0] : Array(); // NA levels : la liste des NA levels
$axe3_options = isset($selecteur_values[1]) ? $selecteur_values[1] : Array(); // axe3 options : liste du premier menu select axe 3

// defaults values for this box : là encore, elle sont choisies "en dur", il faudra créer les requêtes permettant de connaître ces valeurs
$defaults = array($selecteur_values[2]);

// Initialisation des valeurs par défault
$this->setDefaults($defaults);

//		==========	DISPLAY selecteur		==========
?>

<!-- on cache les messages qui doivent être affichés par les .js, et pour lesquels ont ne peut pas utiliser __T() directement dans les .js -->
<div style="display:none;" id="message_SELECTEUR_NEL_SELECTION"><?= __T('SELECTEUR_NEL_SELECTION') ?></div>
<div style="display:none;" id="message_SELECTEUR_NO_RESPONSE"><?= __T('SELECTEUR_NO_RESPONSE') ?></div>
<div style="display:none;" id="message_SELECTEUR_APPLICATION_CANT_ACCESS_TO"><?= __T('SELECTEUR_APPLICATION_CANT_ACCESS_TO') ?></div>
<div style="display:none;" id="message_SELECTEUR_RAW_KPI_FILTER"><?= __T('SELECTEUR_RAW_KPI_FILTER') ?></div>

<script type="text/javascript" src="<?=URL_SELECTEUR?>js/dashboard_NA.js"></script>
<script type="text/javascript" src="<?=URL_SELECTEUR?>js/alarmes_NA.js"></script>
<?php
/*10/04/2009 - SPS : ajout de la librairie JS networkElementSelection.js pour la fonction initNaSelection()*/
?>
<script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/networkElementSelection.js"></script>
<script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/prototype/controls.js"></script>

<script type="text/javascript">
	url_get_NA_selected	= "<?=URL_SELECTEUR?>php/get_NA_selected.php";
	url_get_NA_axe3	= "<?=URL_SELECTEUR?>php/get_NA_axe3.php";
	id_product	= "<?=$id_prod?>";
	url_selecteur_rep_php	= "<?=URL_SELECTEUR?>php/";
	url_get_NA_session	= "<?=URL_SELECTEUR?>php/get_alarmes_NA_session.php";
	family_name = "<?=$_GET['family']?>";
</script>

<!--	na_level	-->


<!--	la mise en page est différente si on a ou non les menus 3eme axe	-->
<?php if (strpos($to_hide,'3emeaxe')) { ?>
	</div>
	<div class="selecteur">
<?php } else { ?>
	<style type="text/css">
	#selecteur_na_level {float:left;margin-right:3px;}
	#selecteur_na_level_div {height:18px;}
	</style>
<?php } ?>

<!--	NELSEL : network element selecteur	-->
<?php if (!strpos($to_hide,'nel_selecteur')) { ?>
		<style type="text/css">
		#img_select_na { height:16px; width:20px; cursor:pointer;}
		.bt_off { background: url(<?=$niveau0?>images/icones/select_na_on.png) left no-repeat;}
		.bt_on { background: url(<?=$niveau0?>images/icones/select_na_on_ok.png) left no-repeat;}
		</style>

      <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(this.alt_on_over) -->
		<div id="img_select_na" class="bt_<?php if ($this->selecteur['nel_selecteur']) { ?>on<?php } else { ?>off<?php } ?>"
			onmouseover="popalt(this.getAttribute('alt_on_over'));"
			onmouseout="kill()"
			alt_on_over="<?= __T('SELECTEUR_NEL_SELECTION') ?>"></div>

		<link rel="stylesheet" href="<?=URL_NETWORK_ELEMENT_SELECTION?>css/networkElementSelection.css" type="text/css">
		
		<?php
		
			include_once(MOD_NETWORK_ELEMENT_SELECTION.'class/networkElementSelection.class.php');
		
			$neSelection = new networkElementSelection();
			
			$neSelection->setButtonMode('checkbox');
			
			// Initialisation du titre de la fenêtre.
			$neSelection->setWindowTitle(__T('SELECTEUR_NEL_SELECTION'));
			
			$neSelection->setDebug(0);
			
			// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
			$neSelection->setOpenButtonProperties('img_select_na', 'bt_on', 'bt_off');
			
			// 02/12/2009 BBX
			// Ajout de l'icône des favoris. BZ 11482
			$neSelection->addIcon(__T('U_SELECTEUR_LABEL_LOAD_FAVORITES'),'favorite_icon','loadFavoritesNetworkElements()');
			
			// On définit dans quel champ la sauvegarde sera effectuée.
			$neSelection->setSaveFieldProperties('nel_selecteur', $this->selecteur['nel_selecteur'], '|s|', 0, 'selecteur[nel_selecteur]');	

			// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
			$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_NA_selected.php?product=$id_prod&na=$na");
			
			// On ajoute des onglets.
			// pour l'instant, on prend TOUS les onglets présents dans le menu NA_level
			foreach ($na_levels as $na => $na_label){
				
				// Ajout du paramètre id
				// 14:52 20/07/2009 GHX
				// Correction du BZ 10584
				// Modification des scripts PHP appelés
				$neSelection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=6&idN=1&idT=$na&product=$id_prod",URL_SELECTEUR."php/selecteur.ajax.php?action=5&idT=$na&product=$id_prod");
			
			}
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
<?php } ?>


<!-- 3eme Axe -->
<?php if (!strpos($to_hide,'3emeaxe')) { ?>
	<div class="selecteur">
		<select name="selecteur[axe3]" id="selecteur_axe3">
			<?php foreach ($axe3_options as $value => $label) { ?>
				<option <?php if ($value == $this->selecteur['axe3']) { ?> selected="selected" <?php } ?> value="<?=$value?>"><?=$label ?></option>
			<?php } ?>
		</select>
		
		<input type="hidden" name="selecteur_axe3_2_hidden" value="ALL" />
		<script type="text/javascript" src="<?=URL_SELECTEUR?>js/dashboard_NA_axe3.js"></script>
	</div>
<?php } ?>


<!--	TOP	-->
<?php if (!strpos($to_hide,'top')) { ?>
	<div class="selecteur">
		<select name="selecteur[top]">
		<?php for ($i=1; $i <= $this->max_top; $i++) { ?>	<option <?php if ($i == $this->selecteur['top']) { ?> selected="selected" <?php } ?>><?php echo $i; ?></option>	<?php } ?>
		</select>
		<?php if ($params['top'] == 'TON') { ?>
			(<?= __T('SELECTEUR_TOP_OVER_NETWORK') ?><span id="dashboardnb_elements"></span>)
		<?php } else { ?>
			(<?= __T('SELECTEUR_TOP_OVER_TIME') ?><span id="dashboardnb_elements"></span>)
		<?php } ?>
	</div>

	<script type="text/javascript">
		getNumberOfNa($F('selecteur_na_level'), 'edw_object_ref', 'dashboardnb_elements', '<?=URL_SELECTEUR?>php/');
	</script>
<?php }
 ?>


