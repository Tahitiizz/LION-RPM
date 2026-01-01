<?php

/**
 *
	- maj 08/09/2008, benoit : masquage du bouton de suppression du trigger
	- maj 08/09/2008, benoit : suppression du message "(Triggers are linked using an \'AND\' condition)"
 *
 */

include_once 'AlarmModel.class.php';
include_once 'networkElementSelection.class.php';

/**
 * Classe AlarmSetup
 * 
 * Permet la création ou l'édition d'une alarme
 * 
 * @package Alarm
 * @author BAC b.audic@astellia.com
 * @version 1.0.0
 * @copyright 2008 Astellia
 *
 */

class AlarmSetup
{
	/**
	 * Identifiant de l'alarme à ajouter / modifier
	 *
	 * @var integer
	 */
	
	private $alarmId;
	
	/**
	 * Famille de l'alarme
	 *
	 * @var string
	 */
	
	private $family;
	
	/**
	 * Type de l'alarme
	 *
	 * @var string
	 */
	
	private $alarmType;
	
	/**
	 * Indique si l'alarme est nouvelle ou non
	 *
	 * @var boolean
	 */
	
	private $newAlarm;
	
	/**
	 * Liste des propriétés d'une alarme à modifier
	 *
	 * @var array
	 */
	
	private $alarmProperties;
	
	/**
	 * Liste des triggers d'une alarme à modifier
	 *
	 * @var array
	 */
	
	private $alarmTriggers;
	
	/**
	 * Liste des champs additionnels d'une alarme à modifier
	 *
	 * @var array
	 */
	
	private $alarmAdditionnals;
	
	/**
	 * Url du script appelé lors du postage du formulaire (non utilisé)
	 *
	 * @var string
	 */
	
	private $postScript;
	
	/**
	 * Instance de la classe de connexion à la base de données
	 *
	 * @var object
	 */
	
	private $alarmModel;
	
	/**
	 * Indique si les niveaux de criticité sont affichés ou non
	 *
	 * @var boolean
	 */
	
	private $showCriticity;

	/**
	 * Nombre de champs additionnels
	 *
	 * @var integer
	 */
	
	private $nbAdditionnalFields;
	
	/**
	 * Liste des types de triggers existants
	 *
	 * @var array
	 */
	
	private $triggerTypes;
	
	/**
	 * Liste des valeurs des types de triggers existants
	 *
	 * @var array
	 */
	
	private $triggerTypeValues;
	
	/**
	 * Liste des opérandes de triggers existants
	 *
	 * @var array
	 */
	
	private $triggerOperands;
	
	/**
	 * Indique si l'on affiche les champs additionnels ou non
	 *
	 * @var boolean
	 */
	
	private $displayAdditionnalFields;
	
	/**
	 * Indique si l'on peut ajpouter des triggers ou non
	 *
	 * @var boolean
	 */
	
	private $canAddTrigger;
	
	/**
	 * Liste des na
	 *
	 * @var array
	 */
	
	private $naList;
	
	/**
	 * Liste des ne (= valeurs de na)
	 *
	 * @var array
	 */
	
	private $neList;
	
	/**
	 * Constructeur de la classe
	 *
	 * @param string $family famille de l'alarme à créer / éditer
	 * @param string $type type de l'alarme à créer / éditer
	 * @param string $post_script url du script de sauvegarde de la définition de l'alarme
	 * @param integer $alarm_id identifiant de l'alarme à créer / éditer
	 * @return void
	 */

	public function __construct($family, $type, $alarm_id = '', $post_script = '')
	{
		// Initialisation des variables de classe
		
		$this->family		= $family;
		$this->alarmType	= $type;
		$this->postScript	= $post_script;

		if ($alarm_id != "") {
			$this->newAlarm = false;
			$this->alarmId = $alarm_id;
		}
		else 
		{
			$this->newAlarm = true;
		}

		// Par defaut, on masque le choix des niveaux de criticité

		$this->showCriticity = false;

		// Définition de la visibilité de l'onglet "Additionnal fields"

		$this->displayAdditionnalFields = false;

		// Définition de la possibilité d'ajouter ou non des triggers

		$this->canAddTrigger = false;

		// Définition du nombre de champs additionnels

		$this->nbAdditionnalFields = 5;

		// Définition d'une instance de la classe 'AlarmModel' afin de disposer des données des alarmes

		$this->alarmModel = new AlarmModel('');

		// Definition des na de la famille

		$this->naList = $this->alarmModel->getNetworkAgregation($this->family);

		// Définition de la liste des ne de celle-ci

		$this->neList = (($this->newAlarm == true) ? array() : $this->alarmModel->getAlarmNetworkElements($this->alarmId, $this->alarmType));

		// Définition des types de triggers, de leurs valeurs et des opérandes disponibles
		
		$this->triggerTypes			= $this->alarmModel->getTriggerTypes();
		$this->triggerTypeValues		= $this->alarmModel->getTriggerTypeValues($this->family);
		$this->triggerOperands		= $this->alarmModel->getTriggerOperands();
	}
	
	/**
	 * Génère le code HTML de l'interface de création / d'édition de l'alarme
	 *
	 */

	public function generateIHM()
	{
		// Note : mettre la méthode d'envoi du formulaire en variable de session

		$html = '<form id="alarmSetupForm" name="alarmSetupForm" method="post" action="'.$this->postScript.'">';
	
		// Définition d'un champ caché contenant l'identifiant de l'alarme (en mode edition)

		$html .= '<input type="hidden" id="alarm_id" name="alarm_id" value="'.$this->alarmId.'"/>';

		// Définition de champs cachés indiquant le type et la famille de l'alarme à créer

		$html .= '<input type="hidden" id="alarm_type" name="alarm_type" value="'.$this->alarmType.'"/>';
		$html .= '<input type="hidden" id="alarm_family" name="alarm_family" value="'.$this->family.'"/>';

		$html .= '<table>';

		if (!$this->newAlarm) {
			$html .= '<tr><td align="center"><span class="texteGris"><A href="javascript:history.back()"><u>'.$this->alarmModel->translate('G_PROFILE_FORM_LINK_BACK_TO_THE_LIST').'</u></a></span></td></tr>';
		}
		
		$html .= '<tr><td>'.$this->generateAlarmPropertiesField().'</td></tr>';
		$html .= '<tr><td>'.$this->generateAlarmOnglets().'</td></tr>';

		$html .= '<tr><td align="center">';
		
		//$html .= '<input type="submit" class="bouton" style="margin:5px" name="Submit" value="Save"/>';
		$html .= '<input type="button" class="bouton" style="margin:5px" name="Submit" value="Save" onclick="checkAlarmFields()"/>';
		
		$html .= '<input type="button" class="bouton" style="margin:5px" name="Cancel" onclick="javascript:history.back()" value="Cancel"/>';

		$html .= '</table>';

		$html .= '</form>';

		echo $html;
	}
	
	/**
	 * Génére le code HTML du fieldset des propriétés de l'alarme
	 *
	 * @return string code HTML du fieldset de propriétés de l'alarme
	 */

	private function generateAlarmPropertiesField()
	{
		if (!$this->newAlarm) {
			$this->alarmProperties = $this->alarmModel->getAlarmProperties($this->alarmId, $this->family, $this->alarmType);
		}

		// Definition du conteneur des propriétés de l'alarme

		$ppties_field = '<fieldset>
								<legend class="texteGrisBold">&nbsp;'.$this->alarmModel->translate('A_ALARM_ALARM_PROPERTIES_LABEL').'&nbsp;</legend>
								<table id="alarm_properties" width="100%" border="0" align="center" cellspacing="3" cellpadding="3" class="tabPrincipal" >
							';

		// Definition d'une ligne du tableau permettant d'afficher les erreurs

		$ppties_field .= '<tr><td colspan="2" id="errors_summary" style="display:none"></td></tr>';
		
		// Definition du nom de l'alarme

		$alarm_name = ((!$this->newAlarm && $this->alarmProperties['alarm_name'] != '') ? $this->alarmProperties['alarm_name'] : "");		
		
		$ppties_field .= '<tr><td class="texteGris" width="150px"><li>'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_ALARM_NAME').'&nbsp;&nbsp;</li></td>';
		$ppties_field .= '<td><input id="alarm_name" name="alarm_name" class="zoneTexteStyleXP" style="width:400px" maxLength="120" value="'.$alarm_name.'"></td></tr>';

		// Definition du champ description de l'alarme

		$alarm_desc = ((!$this->newAlarm && $this->alarmProperties['description'] != '') ? $this->alarmProperties['description'] : "");

		$ppties_field .= '<tr><td class="texteGris" width="150px"><li>'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_ALARM_DESCRIPTION').'&nbsp;&nbsp;</li></td>';
		$ppties_field .= '<td><textarea id="alarm_description" name="alarm_description" class="zoneTexteStyleXP" style="overflow:auto;width:400px" rows="4">'.$alarm_desc.'</textarea></td></tr>';

		// Definition du champ network

		$ppties_field .= '<tr><td class="texteGris" width="150px"><li>'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_NETWORK_LEVEL').'&nbsp;&nbsp;</li></td>';
		$ppties_field .= '<td>'.$this->setNetworkAgregationSelect($this->alarmProperties['network']).'</td></tr>';

		// Definition du champ time

		$ppties_field .= '<tr><td class="texteGris" width="150px"><li>'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_TIME_RESOLUTION').'&nbsp;&nbsp;</li></td>';
		$ppties_field .= '<td>'.$this->setTimeAgregationSelect($this->alarmProperties['time']).'</td></tr>';

		// Definition du champ d'activation / désactivation de l'alarme

		$alarm_checked = ((($this->newAlarm) || ($this->alarmProperties['on_off'] == 1)) ? "checked" : "");

		$ppties_field .= '<tr><td colspan="2" class="texteGris"><li>'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_ALARM_CALCULATION_ACTIVATED').'</li>&nbsp;&nbsp;';
		$ppties_field .= '<input type="checkbox" id="alarm_activated" name="alarm_activated" value="1" '.$alarm_checked.'/></td></tr>';

		// On termine la définition du tableau et du fieldset

		$ppties_field .= '</table></fieldset>';

		return $ppties_field;
	}
	
	/**
	 * génère le code HTML des onglets de définition des triggers et des champs additionnels de l'alarme
	 *
	 * @return string le code HTML des onglets
	 */

	private function generateAlarmOnglets()
	{
		if ($this->displayAdditionnalFields) {	
			$alarm_onglet	= '<table width="100%"><tr><td align=left><div id="alarm_tab_view">';

			$first_onglet	= '<div class="dhtmlgoodies_aTab">'.$this->alarmTriggerOnglet().'</div>';
			$second_onglet	= '<div class="dhtmlgoodies_aTab">'.$this->alarmAdditionnalFieldOnglet().'</div>';

			//$alarm_onglet  .= $this->alarmTriggerOnglet().'</div>';//$first_onglet.$second_onglet.'</div>';

			$alarm_onglet  .= $first_onglet.$second_onglet.'</div>';			
			
			// Creation des onglets via la fonction initTabs

			// Note : vérifier que le chemin vers le dossier des images "tab-view" est tjs le bon

			$trigger_onglet_label		= $this->alarmModel->translate('A_ALARM_FORM_LABEL_TRIGGER');
			$additionnal_onglet_label	= $this->alarmModel->translate('A_ALARM_FORM_LABEL_ADDITIONAL_FIELD');

			$alarm_onglet  .= '
								<script type="text/javascript">
									initTabs("alarm_tab_view", Array(\''.$trigger_onglet_label.'\', \''.$additionnal_onglet_label.'\'), 0, 600, 350, null, "images/tab-view/");
								</script>';
			
			$alarm_onglet  .= '</td></tr></table>';
		}
		else 
		{
			$alarm_onglet = '<div class="dhtmlgoodies_aTab" style="border-top:1px solid #919B9C;">'.$this->alarmTriggerOnglet().'</div>';
		}
		
		return $alarm_onglet;
	}
	
	/**
	 * Définit le code HTML de l'onglet de définition de/des trigger(s) de l'alarme
	 *
	 * @return le code HTML de l'onglet des triggers
	 */

	private function alarmTriggerOnglet()
	{
		// On determine la liste des triggers de l'alarme

		if (!$this->newAlarm) {
			$this->alarmTriggers = $this->alarmModel->getAlarmTrigger($this->alarmId, $this->family, $this->alarmType);
		}

		$trigger_onglet = '<table width="98%"><tr><td align="left">';

		$trigger_onglet .= $this->triggerFieldSet();

		$criticity_levels = $this->alarmModel->getCriticityLevels();

		for ($i=0; $i < count($criticity_levels); $i++) {
			$trigger_onglet .= $this->criticityFieldSet($criticity_levels[$i]);
		}
		
		$trigger_onglet .= '</td></tr></table>';

		return $trigger_onglet;
	}
	
	/**
	 * Définit le code HTML de l'onglet de définition des champs additionnels de l'alarme
	 *
	 * @return string le code HTML de l'onglet des champs additionnels
	 */

	private function alarmAdditionnalFieldOnglet()
	{
		// Note : mettre les libellés dans le modele

		$additionnal_onglet = '<fieldset><legend class="texteGrisBold">&nbsp;'.$this->alarmModel->translate('A_ALARM_FORM_FIELDSET_ADDITIONAL_FIELD_LIST').'&nbsp;&nbsp;</legend>';

		if (!$this->newAlarm) {
			$additionnal_fields = $this->alarmModel->getAlarmAdditionnalFields($this->alarmId, $this->family, $this->alarmType);
		}	

		for ($i=0; $i < $this->nbAdditionnalFields; $i++) {
			$additionnal_onglet .= $this->setAdditionnalFieldDiv($i+1, $additionnal_fields[$i]['type'], $additionnal_fields[$i]['id']);
		}

		$additionnal_onglet .= '</fieldset>';

		return $additionnal_onglet;
	}

	// Note : essayer de fusionner cette méthode avec 'setTriggerDiv()'

	private function setAdditionnalFieldDiv($id, $type = '', $value = '')
	{
		$type_list	= $this->triggerTypes;
		$value_list	= $this->triggerTypeValues;		
		
		// Note : mettre les libellés dans le modele

		$additional_div = '<div class="texteGris"><li>'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_ADDITIONAL_FIELD').' '.$id.'</div>';

		// Affichage de la liste déroulante de choix du type
		
		$additional_div .= '<select class="zoneTexteStyleXP" id="additional_field_type'.$id.'" name="additional_field_type'.$id.'" style="width:100px;margin-right:5px" onchange="deleteCompleteAdditionalFieldLabel('.$id.');getFieldValue(this.value,\'additional_field'.$id.'\', \''.$this->family.'\')">';

		/*if ($type == '') */$additional_div .= '<option value="makeSelection" selected>'.$this->alarmModel->translate('A_ADVANCED_CONTEXT_TYPE_LABEL').'</option>';

		for ($i=0; $i < count($type_list); $i++) {
			$additional_div .= '<option value="'.$type_list[$i]['id'].'" '.(($type_list[$i]['id'] == $type) ? "selected" : "").'>'.$type_list[$i]['label'].'</option>';
		}

		$additional_div .= '</select>';

		// Affichage de la liste déroulante de choix des valeurs fonction du type

		$additional_div .= '<select class="zoneTexteStyleXP" id="additional_field'.$id.'" name="additional_field'.$id.'" style="width:417px;margin-right:15px" onchange="showCompleteAdditionalFieldLabel('.$id.');remove_choice(this);remove_choice(this)">';

		if ($value == ''){
			$additional_div .= '<option value="makeSelection" selected>'.$this->alarmModel->translate('A_ALARM_SELECT_MAKE_A_SELECTION_LABEL').'</option>';
		}
		else
		{
			$type_value_list = $value_list[$type];

			foreach ($type_value_list as $value_id => $value_labels) {
				
				//$type_value = $type_value_list[$i];

				$additional_div .= '<option value="'.$value_id.'" label_complete="'.$value_labels['label_complete'].'" '.(($value_id == $value) ? "selected" : "").'>'.$value_labels['label'].'</option>';

				if (($value_id == $value)) {
					$trigger_selected_complete = ($value_labels['label'] != $value_labels['label_complete']) ? $value_labels['label_complete'] : "";
				}				
			}
		}

		$additional_div .= '</select>';

		// Affichage du bouton de suppression du champ additionnel

		$additional_div .= '<input type="button" class="drop_alarm" style="width:16px" onclick="deleteCompleteAdditionalFieldLabel('.$id.');vider_additional_field(\'additional_field'.$id.'\',\'additional_field_type'.$id.'\')"/>';

		if (isset($trigger_selected_complete) && $trigger_selected_complete != "") {
			$additional_div .= '<div id="additionnal_field_complete_label'.$id.'" class="texteGris" style="padding-left:105px;padding-top:5px;font-size:8pt">'.$trigger_selected_complete.'</div>';
		}

		return $additional_div;
	}
	
	/**
	 * Définit le code HTML du fieldset des triggers de l'alarme
	 *
	 * @return string le code HTML du fieldset triggers
	 */

	private function triggerFieldSet()
	{
		// Note : mettre les libellés dans le modele

		// 08/09/2008 - Modif. benoit : suppression du message "(Triggers are linked using an \'AND\' condition)"

		$trigger_field = '<fieldset><legend class="texteGrisBold">&nbsp;&nbsp;'.$this->alarmModel->translate('A_ALARM_FORM_FIELDSET_TRIGGER_LIST').'&nbsp;<span class="texteGrisPetit">'.""/*$this->alarmModel->translate('A_ALARM_NFORMATION_TRIGGER')*/.'</span>&nbsp;&nbsp;</legend>';

		// Définition du bouton permettant d'ajouter des triggers (si '$this->canAddTrigger' vaut true)

		if ($this->canAddTrigger) {

			// Note : mettre label dans le modele

			$trigger_field .= '<div align="right" style="padding-top:5px;padding-bottom:5px"><input type="button" class="bouton" value="'.$this->alarmModel->translate('A_ALARM_FORM_BTN_ADD_TRIGGER').'" onclick="add_trigger()"/></div>';
		}

		// Definition de la liste des triggers

		$triggers = array();

		if (!$this->newAlarm) {

			//$trigger_div_num = 1;

			for ($i=0; $i < count($this->alarmTriggers); $i++) 
			{
				$alarm_trigger = $this->alarmTriggers[$i];

				// La condition suivante permet d'éviter les doublons dans la définition des triggers. En effet, si un trigger est défini sur plusieurs niveaux de criticité, on a plusieurs lignes de définition dans '$this->alarmTriggers'

				if (!in_array($alarm_trigger['alarm_trigger_data_field'], $triggers)) 
				{
					$trigger_field .= '<div id="trigger_list_'.(count($triggers)+1).'" style="padding-top:5px">'.$this->setTriggerDiv((count($triggers)+1), $alarm_trigger['alarm_trigger_type'], $alarm_trigger['alarm_trigger_data_field']).'</div>';

					$triggers[] = $alarm_trigger['alarm_trigger_data_field'];
				}
			}
		}
		else
		{
			$trigger_field .= '<div id="trigger_list_1" style="padding-top:5px">'.$this->setTriggerDiv(1).'</div>';
		}

		$trigger_field .= '</fieldset>';

		return $trigger_field;
	}
	
	/**
	 * Définit le code HTML des fieldsets des niveaux de criticité des triggers de l'alarme
	 *
	 * @param array $criticity_tab tableau des niveaux de criticité de l'alarme
	 * @return string le code HTML des fieldset des niveaux de criticité
	 */

	private function criticityFieldSet($criticity_tab)
	{
		$criticity			= $criticity_tab['id'];
		$criticity_label	= $criticity_tab['label'];
		$criticity_color	= $criticity_tab['color'];
		$criticity_field	= "";
	
		if ($this->showCriticity) {
			$criticity_field .= '<fieldset style="padding-top:5px"><legend class="texteGrisBold">&nbsp;'.$criticity_label.'&nbsp;&nbsp;<span style="background-color:'.$criticity_color.';width:10px;height:10px;"></span>&nbsp;&nbsp;</legend>';			
		}
		else 
		{
			$criticity_field .= '<fieldset style="margin-top:10px;padding-top:5px">';
		}
			
		if (!$this->newAlarm){
			$criticity_values = $this->alarmModel->getAlarmCriticityValues($this->alarmId, $this->family, $criticity, $this->alarmType);

			for ($i=0; $i < count($criticity_values); $i++) {

				$criticity_id = $criticity.($i+1);
				
				$criticity_field .= '<div id="trigger_'.$criticity_id.'">'.$this->setCriticityDiv($criticity_id, $criticity, ($i+1), $criticity_values[$i]).'</div>';
			}
		}
		else 
		{
			$criticity_id = $criticity."1";
			$criticity_field .= '<div id="trigger_'.$criticity_id.'">'.$this->setCriticityDiv($criticity_id, $criticity, 1).'</div>';
		}

		$criticity_field .= '</fieldset>';

		return $criticity_field;
	}

	/**
	 * Définit le code HTML des calques des niveaux de criticité des triggers de l'alarme
	 *
	 * @param string $id identifiant du trigger de l'alarme
	 * @param string $criticity criticité du trigger
	 * @param integer $index valeur numérique du trigger
	 * @param array $values valeur du trigger pour le niveau de criticité (en mode "édition" seulement)
	 * @return code HTML du calque du niveau de criticité du trigger de l'alarme
	 */
	
	private function setCriticityDiv($id, $criticity, $index, $values = array())
	{
		$criticity_div = '';

		if (count($values) > 0) {
			$trigger_type		= $values['type'];
			$trigger_id		= $values['id'];
			$trigger_label		= $this->triggerTypeValues[$trigger_type][$trigger_id]['label'];
			$trigger_operand	= $values['operand'];
			$trigger_value		= $values['value'];
		}
		else 
		{
			$trigger_label = "No trigger selected";
			$trigger_value = "";
		}

		// Affichage du label du trigger

		$criticity_div .= '<input tabIndex="-1" class="zoneTexteStyleXP" name="trigger_label_'.$id.'" style="width:325px;border:0px" value="'.$trigger_label.'" readonly>';

		// Affichage de la liste des operandes

		$criticity_div .= '<select class="zoneTexteStyleXP" name="trigger_operand_'.$id.'" id="trigger_operand_'.$id.'" style="width:85px;margin-right:5px">';

		for ($j=0; $j < count($this->triggerOperands); $j++) {

			$selected = ((isset($trigger_operand) && $this->triggerOperands[$j] == $trigger_operand) ? "selected" : "");

			$criticity_div .= '<option value="'.$this->triggerOperands[$j].'" '.$selected.'>'.$this->triggerOperands[$j].'</option>';
		}

		$criticity_div .= '</select>';

		// Affichage de la valeur du trigger pour le niveau de criticité

		$criticity_div .= '<input class="zoneTexteStyleXP" name="trigger_value_'.$id.'" id="trigger_value_'.$id.'" style="width:80px;margin-right:15px" value="'.$trigger_value.'">';

		// Affichage du bouton de suppression

		$criticity_div .= '<input class="drop_alarm" style="width:16px;cursor:pointer" onclick="remove_critical_level(\''.$criticity.'\',\''.$index.'\')"/>';

		return $criticity_div;
	}
	
	/**
	 * Définit le code HTML des calques des triggers de l'alarme
	 *
	 * @param integer $id identifiant du trigger
	 * @param string $type type du trigger
	 * @param string $value valeur du trigger sélectionné
	 * @return string le code HTML du calque du trigger
	 */

	private function setTriggerDiv($id, $type = '', $value = '')
	{
		$type_list	= $this->triggerTypes;
		$value_list	= $this->triggerTypeValues;
		
		if($type == "") $type = $type_list[0]['id'];

		// Affichage de la liste déroulante de choix du type

		if (count($type_list) > 1)
		{
			$trigger_div = '<select class="zoneTexteStyleXP" id="trigger_type'.$id.'" name="trigger_type'.$id.'" style="width:100px;margin-right:5px" onchange="getFieldValue(this.value,\'trigger_field'.$id.'\',\''.$this->family.'\');changerLabel('.$id.');remove_all_critical_level('.$id.')">';

			$trigger_div .= '<option value="makeSelection" selected>'.$this->alarmModel->translate('A_ADVANCED_CONTEXT_TYPE_LABEL').'</option>';

			for ($i=0; $i < count($type_list); $i++) {
				$trigger_div .= '<option value="'.$type_list[$i]['id'].'" '.(($type_list[$i]['id'] == $type) ? "selected" : "").'>'.$type_list[$i]['label'].'</option>';
			}

			$trigger_div .= '</select>';			
		}
		else 
		{
			$trigger_div = '<input type="text" id="trigger_type'.$id.'" name="trigger_type'.$id.'" class="texteGris" style="border: 0pt none;margin-right:5px" size="'.(strlen($type_list[0]['label'])).'" value="'.$type_list[0]['label'].'" real_value="'.$type_list[0]['id'].'"/>';
		}

		// Affichage de la liste déroulante de choix des valeurs fonction du type

		$trigger_div .= '<select class="zoneTexteStyleXP" id="trigger_field'.$id.'" name="trigger_field'.$id.'" style="width:417px;margin-right:15px" onchange="changerLabel('.$id.');remove_all_critical_level('.$id.');remove_choice(this)">';

		if ($value == ''){
			$trigger_div .= '<option value="makeSelection" selected>'.$this->alarmModel->translate('A_ALARM_SELECT_MAKE_A_SELECTION_LABEL').'</option>';
		}
		/*else
		{*/
			$type_value_list = $value_list[$type];

			foreach ($type_value_list as $value_id => $value_labels) {
				
				//$type_value = $type_value_list[$i];

				$trigger_div .= '<option value="'.$value_id.'" label_complete="'.$value_labels['label_complete'].'" '.(($value_id == $value) ? "selected" : "").'>'.$value_labels['label'].'</option>';

				if (($value_id == $value)) {
					$trigger_selected_complete = ($value_labels['label'] != $value_labels['label_complete']) ? $value_labels['label_complete'] : "";
				}				
			}
		//}

		$trigger_div .= '</select>';

		// 08/09/2008 - Modif. benoit : masquage du bouton de suppression du trigger

		/*// Affichage du bouton de suppression du trigger

		$trigger_div .= '<input type="button" class="drop_alarm" onclick="remove_trigger('.$id.')"/>';*/

		if (isset($trigger_selected_complete) && $trigger_selected_complete != "") {
			$trigger_div .= '<div id="trigger_complete_label'.$id.'" class="texteGris" style="padding-left:105px;padding-top:5px;font-size:8pt">'.$trigger_selected_complete.'</div>';
		}

		return $trigger_div;
	}
	
	/**
	 * Définit le code HTML de la liste de sélection du niveau d'agregation réseau
	 *
	 * @param string $na identifiant du niveau d'agregation (en mode "édition" seulement)
	 * @return string le code HTML de la liste de sélection des na
	 */

	private function setNetworkAgregationSelect($na = '')
	{
		$na_select = '';
		
		// Definition des na de la famille

		//$na_list = $this->alarmModel->getNetworkAgregation($this->family);

		// Construction de la liste

		if (count($this->naList) > 0) {

			//$na_select .= '<select id="net_to_sel" name="net_to_sel" class="zoneTexteStyleXP" style="width:150px;" onChange="remove_choice(this); updateNaSelection_alarme(this); changeNaSelectionIcon(\'on\');">';

			$na_select .= '<select id="net_to_sel" name="net_to_sel" class="zoneTexteStyleXP" style="width:150px;" onChange="resetNeSelection();getNEList(this.value, \'ne_list\', \'img_select_na\', \''.$this->family.'\')">';

			if ($na == "") $na_select .= '<option value="makeSelection" selected>'.$this->alarmModel->translate('A_ALARM_SELECT_MAKE_A_SELECTION_LABEL').'</option>';

			for ($i=0; $i < count($this->naList); $i++) {

				$selected = (($na != "" && $this->naList[$i]['agregation'] == $na) ? "selected" : "");

				$na_select .= '<option value="'.$this->naList[$i]['agregation'].'" '.$selected.'>'.$this->naList[$i]['agregation_label'].'</option>';
			}
			
			$na_select .= '</select>';
		}
		else // Aucune na définie pour la famille sélectionnée
		{
			$na_select .= $this->alarmModel->translate('A_NO_NA_FOR_FAMILY_LABEL', $family);
		}

		// Construction du bouton de selection des valeurs de na

		$display_selection_na = "block";

		$na_select .= "<span id='icone_selection_des_na' style='display:<?=$display_selection_na?>; padding-left:10px;'>";
		
		// On affiche une icône verte si des éléments réseaux préférés ont déjà été choisis.
		
		$image_select_na = ((count($this->neList) > 0) ? 'bt_on' : 'bt_off');
		
		$na_select .= '<input type="button" id="img_select_na" class="'.$image_select_na.'" onclick="openNaSelection(\''.$this->alarmModel->translate('A_TITRE_FENETRE_NETWORK_ELEMENT_SELECTION_ALARMES_SETUP').'\')" style="cursor:pointer">';

		// Creation du champ caché qui va contenir les valeurs de NA

		//$na_select .= '<input type="text" id="ne_list" name="ne_list" value=""/>';

		$na_select .= '</span>';

		return $na_select;
	}

	/**
	 * Définit le code HTML de la liste de sélection du niveau d'agregation temporelle
	 *
	 * @param string $ta identifiant du niveau d'agregation (en mode "édition" seulement)
	 * @return string le code HTML de la liste de sélection des ta
	 */	
	
	private function setTimeAgregationSelect($ta = '')
	{
		$ta_select = '';
		
		// Definition des ta

		$ta_list = $this->alarmModel->getTimeAgregation();

		// Construction de la liste

		if (count($ta_list) > 0) {

			//$ta_select .= '<select id="time_to_sel" name="time_to_sel" class="zoneTexteStyleXP" style="width:150px" onclick="save_time_to_sel(this.value)" onchange="remove_choice(this);toggle_discontinuous(this.value);getTaExclusion(this.value,\'period_exclusion\');">';

			$ta_select .= '<select id="time_to_sel" name="time_to_sel" class="zoneTexteStyleXP" style="width:150px" onchange="remove_choice(this);">';

			if ($ta == "") $ta_select .= '<option value="makeSelection" selected>'.$this->alarmModel->translate('A_ALARM_SELECT_MAKE_A_SELECTION_LABEL').'</option>';

			for ($i=0; $i < count($ta_list); $i++) {

				$selected = (($ta != "" && $ta_list[$i]['agregation'] == $ta) ? "selected" : "");

				$ta_select .= '<option value="'.$ta_list[$i]['agregation'].'" '.$selected.'>'.$ta_list[$i]['agregation_label'].'</option>';
			}
			
			$ta_select .= '</select>';
		}
		else // Aucune na définie pour la famille sélectionnée
		{
			$ta_select .= $this->alarmModel->translate('A_NO_TA_LABEL');
		}
		return $ta_select;		
	}
	
	/**
	 * Définit la liste des ne disponibles
	 *
	 * @param string $btn_id identifiant du bouton de sélection des ne
	 */

	public function setNetworkElementSelection($btn_id)
	{
		$neSelection = new networkElementSelection();
		
		$neSelection->setWindowTitle($this->alarmModel->translate('A_TITRE_FENETRE_NETWORK_ELEMENT_SELECTION_ALARMES_SETUP'));

		// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
		
		$neSelection->setOpenButtonProperties($btn_id, 'bt_on', 'bt_off');
		
		$neSelection->setHtmlIdPrefix('');

		$neSelection->setSaveFieldProperties('ne_list', $this->getNeValuesFromAlarmNe(), '||', 0);
		
		// On ajoute des onglets.

		$ne_div = array();

		for ($i=0; $i < count($this->naList); $i++) {
			$neSelection->addTabInIHM($this->naList[$i]['agregation'], $this->naList[$i]['agregation_label'], 'alarm_actions_manager.php?action=get_ne&display=html&na='.$this->naList[$i]['agregation'].'&family='.$this->family);
			$ne_div[] = "'".$this->naList[$i]['agregation']."'";
		}

		//$neSelection->setViewCurrentSelectionContentButtonProperties();
		
		// Génération de l'IHM.
		
		$neSelection->generateIHM();

		// lancement d'un script JS pour déplacer la liste des ne au sein du formulaire lors du chargement de la page

		echo "<script>initNEList('ne_list', 'alarmSetupForm');showHideNE(new Array(".implode(', ', $ne_div)."))</script>";
	}
	
	/**
	 * Définit la liste détaillée des ne d'une alarme
	 *
	 * @return string la liste détaillée des ne
	 */

	private function getNeValuesFromAlarmNe()
	{		
		$real_ne_list = array();

		// Cas de la selection de tous les elements ou de tous sauf quelques-uns ("not_in" = 1)

		if (count($this->neList) > 0) {
			if (($this->neList['lst_alarm_compute']  == "all") || ($this->neList['not_in']  == 1)) {

				$real_ne_list[] = "all_".$this->alarmProperties['network'];
				
				// Dans le cas où l'on restreint la selection, on supprime de '$real_ne_list' les éléments non sélectionnés

				if ($this->neList['not_in']  == 1) {

					$ne_list = $this->alarmModel->getNE($this->alarmProperties['network'], $this->family);

					for ($i=0; $i < count($ne_list); $i++) {
						$real_ne_list[] = $ne_list[$i]['id'];
					}

					$real_ne_list = array_diff($real_ne_list, explode('||', $this->neList['lst_alarm_compute']));
				}
			}
			else 
			{
				$real_ne_list = explode('||', $this->neList['lst_alarm_compute']);
			}			
		}	
		return implode('||', $real_ne_list);
	}
}

?>