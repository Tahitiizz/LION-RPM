<?php
/*
	13/07/2009 GHX
		- Correction du BZ 10600 [REC][Investigation Dashboard]: tooltip ne correspond pas au label du raw/kpi
			-> Ajout du l'id produit passé dans l'url get_counters_selected.php

   17/08/2010 MMT
      - bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over)
   06/04/2011 MMT
 *    - bz 21725 : Size on Investigation dashboard filter window
 *    - bz 21512 : selectionne le KPI/raw selectionné dans le selecteur de NA par default dans le filtre
 *						 importe le contenu de filter_investigation_counters.ajax.php et supprime l'appel AJAX
 *
 * 06/06/2011 MMT DE 3rd Axis utilisation de networkElementSelection pour gestion de l'url savehook -> resolution de pb d'affichage firefox
*/
?>
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

$id_prod = $params['product'];
$id_family = $params['family'];

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
<?php /*url utilisee par la fonction networkElementSelectionSaveHook */

// 09:46 13/07/2009 GHX
// Correction du BZ 10600 
?>
<?php
// 22/11/2011 BBX
// BZ 23263 : pas d'élément value sur un div !
?>
<div id="investigation_counters_url" style="display:none;"><?=URL_SELECTEUR?>php/get_counters_selected.php?product=<?php echo $id_prod; ?></div>

<!-- selecteur de raw/kpi	-->
<?php if (!strpos($to_hide,'nelsel')) { ?>

		<!-- feuille de style pour le selecteur-->
		<link rel="stylesheet" href="<?=$url_investigation_dashboard?>css/networkElementSelection.css" type="text/css">
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
			
			$neSelection->setHtmlIdPrefix('investigation_counters');
			
			// On définit le type de bouton des éléments réseau
			$neSelection->setButtonMode('checkbox');

			// Initialisation du titre de la fenêtre.
			$neSelection->setWindowTitle(__T(SELECTEUR_RAW_KPI_SELECTION));
			
			// Debug à 0
			$neSelection->setDebug(0);
			
			// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
			$neSelection->setOpenButtonProperties('investigation_counters_img', 'bt_on', 'bt_off');
			
			// On définit dans quel champ la sauvegarde sera effectuée.
			
			$neSelection->setSaveFieldProperties('investigation_counters_selecteur', $this->selecteur['investigation_counters_selecteur'], '|s|', 0, "selecteur[investigation_counters_selecteur]");
			//06/06/2011 MMT DE 3rd Axis utilisation de networkElementSelection pour gestion de l'url savehook
			$neSelection->setSelectionSaveHookURL(URL_SELECTEUR."php/get_counters_selected.php?product=$id_prod");
			
			// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
			$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_counters_selected.php?na=$na&product=$id_prod");
			// On ajoute des onglets
			$neSelection->addTabInIHM('raw','Raw', URL_SELECTEUR."php/selecteur.ajax.php?action=7&type=raw&family=$id_family&product=$id_prod",URL_SELECTEUR."php/selecteur.ajax.php?action=8&family=$id_family&product=$id_prod");
			$neSelection->addTabInIHM('kpi','KPI', URL_SELECTEUR."php/selecteur.ajax.php?action=7&type=kpi&family=$id_family&product=$id_prod",URL_SELECTEUR."php/selecteur.ajax.php?action=8&family=$id_family&product=$id_prod");
			
			// Génération de l'IHM.
			$neSelection->generateIHM();	
		?>
	</div>
	<?php 
	//si le selecteur contient des valeurs, on change la classe du bouton
	if ($this->selecteur['investigation_counters_selecteur']) {
		?>
	<script type="text/javascript">
		$('investigation_counters_img').className = 'bt_on';
	</script>	
<?php 
	}
} ?>

<!-- filter -->
<div class="selecteur">
   <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(this.alt_on_over) -->
	<img id="selecteur_filter_btn" align="absmiddle" border="0"	style="cursor:pointer"
		onmouseover="popalt(this.getAttribute('alt_on_over'));"
		onmouseout="kill()"
		alt_on_over="<?= __T('SELECTEUR_RAW_KPI_FILTER') ?>"
		onclick="openKpiSelection('<?= __T('SELECTEUR_RAW_KPI_FILTER') ?>'), getCounterValues();"
		src="<?=$niveau0?>images/icones/kpi_filter_<?php if ($this->selecteur['filter_name']) { ?>on<?php } else { ?>off<?php } ?>.png" />
</div>


<script type="text/javascript">
	<?php 
	/**
	* 27/05/2009 SPS : on remplit la liste deroulante du filtre avec les donnees de php/filter_investigation_counters.ajax.php
	*	19/08/2009 BBX : modification du script afin de récupérer TOUS les raws kpis de la famille. BZ 11126
	**/
	?>
	function getCounterValues() {
		//si on a selectionne des compteurs
		if ($('investigation_counters_selecteur').value != '') {
			// 07/04/2011 MMT bz 21512 selectionne par default le premier kpi/raw selectionné dans le selecteur
			var kpiRawSelection = $('investigation_counters_selecteur').value;
			// si pas de selection prealable
			if($('selecteur_filter_name').value == ''){
				//exemple format kpiRawSelection (2 selections de KPI): kpi@kpis.0016.01.00011@CS_CALLS_NB@Number of CS calls initialized|s|kpi@kpis.0016.01.00127@CS_VIDEO_RAB_SOMD_TOTAL_NB
				// on recupère le dernier KPI/raw selectionné
				var kpiRaws = kpiRawSelection.split("|s|");
				var selectedForDef = kpiRaws[kpiRaws.length-1];
				// les valeurs dans le select (selecteur_filter_name) du filtre sont toute de la forme  <kpi/raw>@<id>@<code>
				// mais comme dans l'exemple, le format d'un raw/kpi de kpiRawSelection peu contenir est parfois <kpi/raw>@<id>@<code>@<label> (trop sympa!) il faut donc extraire le label
				var parts = selectedForDef.split('@');
				if(parts.length >= 3){
					valueToSet = parts[0]+'@'+parts[1]+'@'+parts[2];
				} else {
					// on ne devrait jamais entrer ici
					valueToSet = selectedForDef;
					}
			}else{
				// sinon on selectionne la valeure precedente
				valueToSet = $('selecteur_filter_name').value;
				}
			// on affecte la valeure
			$('widget_selecteur_filter_name').value = valueToSet;
		}
		
		}
	
</script>

<!-- ===== FILTER =====	-->

<input type="hidden" id="selecteur_filter_name" name="selecteur[filter_id]"	 value="<?=$this->selecteur['filter_id']?>"		/>
<input type="hidden" id="selecteur_filter_operande"	name="selecteur[filter_operande]" value="<?=$this->selecteur['filter_operande']?>"	/>
<input type="hidden" id="selecteur_filter_value" name="selecteur[filter_value]"	value="<?=$this->selecteur['filter_value']?>"		/>

<script type="text/javascript" src="<?=URL_SELECTEUR?>js/investigation_filter.js"></script>

<div id="div_kpi_filter" style="display:none;" width="100%">
	<table cellpadding="2" cellspacing="5" align="center" width="100%" rowspan="7">
		<tr>
			<!-- filter_name -->
			<td>
				<div id="liste_selecteur_filter_name">
				<select id="widget_selecteur_filter_name" name="widget_selecteur_filter_name" class="zoneTexteStyleXP">

					<?php
					// 7/04/2011 MMT bz 21725 on supprime l'appel ajax qui generai le bug (extention de la taille du
					// select) la liste est remplie à la creation de la page
					// ajout du contenu du fichier filter_investigation_counters.ajax.php

					$investigation = new InvestigationModel($id_prod,$id_family);
					if(count($investigation->getRawKPIs()) > 0)
					{
						foreach($investigation->getRawKPIs() as $val => $name)
						{
							$v = explode("@",$val);
							$type_counter = $v[0];
							$id_counter = $v[1];
							$label_counter = $v[2]
							//07/04/2011 MMT bz 21512 si le paramètre selected egale la valeure courrante, on le selectionne
						?>
							<option value="<?=$val?>" <?php if ($val == $selected) { ?> selected="selected" <?php } ?> class="<?= $type_counter ?>"><?=$label_counter?></option>
						<?php
						}
					}else{
						?>
					<option>None</option>	
				 <?php	}?>
				</select>
				</div>
			</td>

			<!-- filter_operande	-->
			<td>
				<select id="widget_selecteur_filter_operande" name="widget_selecteur_filter_operande" size="1" class="zoneTexteStyleXP">
					<option value="none"><?= __T('SELECTEUR_NONE')?></option>
					<?php
						$operandes = array('=','&lt;=','&gt;=','&lt;','&gt;');
						foreach ($operandes as $op) {
							echo "\n<option value='$op'";
							if ($op == htmlentities($this->selecteur['filter_operande']))	echo " selected='selected'";
							echo ">$op</option>";
						}	?>
				</select>
			</td>

			<!--	filter_value	-->
			<td>
				<!-- modif 28/03/2008 christophe : correction bug BZ 3870 : on élargit la zone de saisie de la valeur du filtre.  -->
				<input id="widget_selecteur_filter_value" name="widget_selecteur_filter_value" value="<?=$this->selecteur['filter_value']?>" size="10" class="zoneTexteStyleXP"
					onKeyDown="if(event.keyCode==13){return false}" />
			</td>
		</tr>
		
		<!--	filter_title	-->
		<tr>
			<td colspan="3">
				<div id="widget_selecteur_filter_title" class="texteGrisMini"></div>
			</td>
		</tr>
		
		<!--	boutons de validation	-->
		<tr>
			<td colspan="3" align="left">
				<input type="button" value=" Ok" class="bouton" onclick="checkKpiFilterIsCorrect()">
				&nbsp;&nbsp;
				<input type='button' id='kill_filter_btn' value='Reset' class='bouton' onclick="removeKpiFilter('<?=$niveau0?>');closeKpiselection();">
			</td>
		</tr>
	</table>
</div>

<script type="text/javascript">
	<?php /*mise a jour du alt_on_over du bouton*/?>
	update_btn();
</script>
