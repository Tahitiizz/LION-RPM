<?php

// 04/12/2008 - Modif. benoit : correction du bug 7751. Inclusion du fichier de configuration de l'application afin d'utiliser les constantes définies dans celui-ci

include_once dirname(__FILE__).'/../../../libconf/app_conf.php';

include_once 'AlarmModel.class.php';

/**
 * Classe AlarmList
 * 
 * Permet de lister les alarmes de l'application
 * 
 * @package Alarm
 * @author BAC b.audic@astellia.com
 * @version 1.0.0
 * @copyright 2008 Astellia
 *
 */

class AlarmList
{
	/**
	 * Type des alarmes listées
	 * @var string
	 */
	
	private $alarmType;
	
	/**
	 * Famille des alarmes listées
	 * @var string
	 */
	
	private $family;
	
	/**
	 * Tableau contenant les alarmes listées
	 *
	 * @var array
	 */
	
	private $alarmList;

	/**
	 * Instance du modèle de données des alarmes
	 *
	 * @var object
	 */
	
	private $alarmModel;

	/**
	 * Url du script
	 *
	 * @var string
	 */
	
	private $localURL;

	/**
	 * Constructeur de la classe
	 *
	 * @param string $family famille auquelle appartiennent les alarmes listées
	 * @param string $type type des alarmes listées
	 */
	
	function __construct($family = '', $type = 'static')
	{
		// Initialisation des variables de classe
		
		$this->family		= $family;
		$this->alarmType	= $type;

		// Définition d'une instance de la classe 'AlarmModel' afin de disposer des données des alarmes

		$this->alarmModel = new AlarmModel('');

		// Définition de l'url locale

		$this->setLocalURL();
	}
	
	/**
	 * Permet de définir l'url depuis laquelle le script est appelé
	 *
	 */

	private function setLocalURL()
	{		
		$request_uri = explode('/', $_SERVER["REQUEST_URI"]);
		$request_uri = implode('/', array_slice($request_uri, 0, count($request_uri)-1));

		// 04/12/2008 - Modif. benoit : correction du bug 7751. Utilisation de la constante "ADMIN_HTTP" pour définir le protocole à utiliser

		$this->localURL = ADMIN_HTTP."://".$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$request_uri."/";		
	}
	
	/**
	 * Retourne la liste des alarmes
	 *
	 * @return array la liste des alarmes
	 */

	function getAlarms()
	{
		$this->alarmList = $this->alarmModel->getAlarmList($this->family, $this->alarmType);
		
		return $this->alarmList;
	}
	
	/**
	 * Génère le code HTML de la liste des alarmes
	 *
	 */

	function generateIHM()
	{
		$html = '<div align="center" style="margin:5px"><input id="new_alarm" name="new_alarm" type="button" class="bouton" onclick="self.location.href=\''.$this->localURL.'php/alarm_setup.php?family='.$this->family.'&alarm_type='.$this->alarmType.'\'"  value="'.$this->alarmModel->translate('A_ALARM_BTN_NEW_ALARM', ''/*$this->alarmType*/).'"/></div>'; 
		
		$html .= '<fieldset>
					<legend class="texteGrisBold">&nbsp;<input type="button" class="small_puce_fieldset" style="width:10px"/>&nbsp;'.$this->alarmModel->translate('A_ALARM_ALARM_LIST').'&nbsp;</legend>
					<table id="alarm_table_list" width="100%" border="0" align="center" cellspacing="3" cellpadding="3" class="tabPrincipal">
				';

		$alarm_list = $this->getAlarms();
		
		$display_info_on_off = false;

		if (count($alarm_list) == 0) {	// Pas d'alarme définie
			
			// Note : définir "No alarm registered." en tant que message

			$html .= '<tr><td class="texteGrisBold" align="center">'.$this->alarmModel->translate('A_ALARM_NO_ALARM_REGISTRED').'</td></tr>';
		}
		else // Des alarmes existent pour la famille et le type concerné
		{
			// Definition de l'entete du tableau

			// Note : définir les titres en tant que variables

			$html .= '
						<tr>
							<td align="center"><font class="texteGrisBold">'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_ALARM_NAME').'</font></td>
							<td align="center"><font class="texteGrisBold">'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_NETWORK_LEVEL').'</font></td>
							<td align="center"><font class="texteGrisBold">'.$this->alarmModel->translate('A_ALARM_FORM_LABEL_TIME_RESOLUTION').'</font></td>
							<td colspan="2">&nbsp;</td>
						</tr>
					';

			// Definition du contenu
			
			for ($i=0; $i < count($alarm_list); $i++) {

				$alarm = $alarm_list[$i];
				
				// Definition de la nouvelle ligne. Le style de celle-ci est alternativement blanc (zoneTexteBlanche)et gris (zoneTexteStyleXPFondGris)

				if ($alarm['on_off'] != 1) {
					$alarm_line_style = 'style="color:orange;"';
					$display_info_on_off = true;
				}
				else 
				{
					$alarm_line_style = '';
				}

				$html .= '<tr id="alarm_line_'.$alarm['alarm_id'].'" class="'.(($i%2 == 0) ? "zoneTexteBlanche" : "zoneTexteStyleXPFondGris").'" '.$alarm_line_style.'>';

				// Definition des colonnes nom de l'alarme, na, ta

				// Note : définir le label de la na et de la ta via des fonctions 'getXXX' dans le modele

				$na_label = $this->alarmModel->getNALabel($alarm['network'], $this->family);
				$ta_label = $this->alarmModel->getTALabel($alarm['time']);

				$html .= '<td>'.$alarm['alarm_name'].'</td><td align="center">'.$na_label['agregation_label'].'</td><td align="center">'.$ta_label['agregation_label'].'</td>';

				// Definition des boutons de suppression, d'envoi d'emails, d'edition et d'envoi des trappes SNMP (facultatif)

				$delete_btn	= $this->setDeleteButton($alarm['alarm_id'], $alarm['time']);
				$edit_btn	= $this->setEditionButton($alarm['alarm_id']);
				$email_btn	= $this->setEmailButton($alarm['alarm_id'], $alarm['time'], $alarm['send_mail']);
				$snmp_btn	= $this->setSNMPButton();

				$html .= '<td colspan="2" nowrap>'.$delete_btn.$edit_btn.$email_btn.$snmp_btn.'</td>';
				
				// Fin de la ligne
				
				$html .= '</tr>';
			}
		}

		if ($display_info_on_off) {
			$html .= '<tr><td colspan="4" style="padding-top:10px"><input type="button" class="information" style="width:16px"/><span style="font: 8pt verdana, arial, helvetica;color: #585858;">&nbsp;'.$this->alarmModel->translate('A_ALARM_DESACTIVATED_INFORMATION').'</span></td></tr>';			
		}

		$html .= '</table></fieldset>';

		$html .= $this->setGroupSelectorDiv();

		echo $html;
	}
	
	/**
	 * Définit le code HTML du bouton de suppression d'une alarme de la liste
	 *
	 * @param integer $alarm_id identifiant de l'alarme à supprimer
	 * @param string $alarm_ta l'agregation temporelle de l'alarme à supprimer
	 * @return string le code HTML du bouton de suppression
	 */

	function setDeleteButton($alarm_id, $alarm_ta)
	{
		// Note : définir le message de suppression dans le modele

		//$delete_msg = "Do you want to delete this ".$this->alarmType." alarm ?";

		$delete_msg = $this->alarmModel->translate('A_PAUTO_CONFIRM_OBJECT_DELETE');
		
		return '<input type="button" class="drop_alarm" style="width:16px" onclick="openAlarmDropDialog(\''.$this->alarmModel->translate('A_ALARM_DELETE_CONFIRMATION_TITLE').'\', \''.$delete_msg.'\', '.$alarm_id.', \''.$this->alarmType.'\', \''.$this->family.'\', \''.$alarm_ta.'\')"/>';		
	}
	
	/**
	 * Définit le code HTML du bouton d'edition des propriétés d'une alarme de la liste
	 *
	 * @param integer $alarm_id identifiant de l'alarme à éditer
	 * @return string le code HTML du bouton d'edition
	 */

	function setEditionButton($alarm_id)
	{
		return '<input type="button" class="alarm_info" onclick="self.location.href=\''.$this->localURL.'php/alarm_setup.php?family='.$this->family.'&alarm_type='.$this->alarmType.'&alarm_id='.$alarm_id.'\'" style="margin-left:5px;margin-right:5px;width:16px"/>';
	}
	
	/**
	 * Définit le code HTML du bouton d'ajout de destinataires à l'alarme
	 *
	 * @param integer $alarm_id identifiant de l'alarme à éditer
	 * @param string $alarm_ta l'agregation temporelle de l'alarme à supprimer
	 * @param boolean $send_mail l'alarme est elle déja attachée à des destinataires (true) ou non (false)
	 * @return string le code HTML du bouton d'ajout de destinataire
	 */

	function setEmailButton($alarm_id, $alarm_ta, $send_mail)
	{
		// Note : définir les labels via des fonctions 'getXXXX' dans le modele

		if (!$send_mail) {
			$email_class = 'email_disabled';
			$email_alt = 'Activate email sending';
		}
		else 
		{
			$email_class = 'email_enabled';
			$email_alt = 'Desactivate email sending';					
		}
		
		//return '<div class="'.$email_class.'" border="0" alt="'.$email_msg.'" onclick="ouvrir_fenetre(\'setup_alarm_send_to.php?alarm_id='.$alarm_id.'&alarm_type=alarm_'.$this->alarmType.'\',\'Update_Alarm\',\'yes\',\'no\',870,600)"></div>';
		
		return '<input type="button" id="mail_alarm_'.$alarm_id.'" class="'.$email_class.'" style="width:16px" alt="'.$email_alt.'" onclick="showGroups(\''.$this->alarmModel->translate('A_ALARM_EMAIL_SETUP_TITLE').'\', \'div_group_select\', '.$alarm_id.', \''.$alarm_ta.'\', \''.$this->alarmType.'\')"/>';	
	}
	
	/**
	 * Définit le code HTML du bouton d'ajout de trappes SNMP à une alarme
	 *
	 * @return string le code HTML du bouton d'ajout de trappes SNMP
	 */

	function setSNMPButton()
	{
		return '';
	}
	
	/**
	 * Définit le calque de sélection des groupes de destinataires d'une alarme
	 *
	 * @return string le code HTML du calque d'ajout de groupes de destinataires
	 */

	function setGroupSelectorDiv()
	{
		$group_div = '<div id="div_group_select" class="tabPrincipal" style="display:none">';

		$group_div .= '<fieldset><legend class="texteGrisBold">&nbsp;<input type="button" class="small_puce_fieldset" style="width:10px;height:10px"/>&nbsp;<span class="texteGrisBold">'.$this->alarmModel->translate('A_ALARM_GROUP_SELECTOR').'</span></legend>';
		
		$group_div .= '<div style="margin-top:10px;margin-left:10px" align="center">';
		
		// Creation du div de sélection des groupes disponibles

		$group_div .= '<div style="float:left">';
		$group_div .= '<p class="texteGrisBold">'.$this->alarmModel->translate('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_AVAILABLE_GROUP').'</p>';
		$group_div .= '<select name="available_groups" size="10" multiple="multiple" style="width:210px;"></select>';
		$group_div .= '</div>';

		// Creation du div de transfert entre les deux groupes

		$group_div .= '	<div id="group_transfert" style="float:left;margin-right:5px;margin-left:5px">
							<p class="texteGrisBold">&nbsp;</p>
							<table height="165px" border="0" cellspacing="0" cellpadding="0" style="bottom:0px">
								<tr vertical-align="center">
									<td>
										<div><input type="button" value=" -&gt; " onclick="transfertSelectedElement(\'available_groups\', \'suscribed_groups\')" class="stdFPButton"></div>
										
										<div><input type="button" value=" &lt;- " onclick="transfertSelectedElement(\'suscribed_groups\', \'available_groups\')" class="stdFPButton"></div>
									</td>
								</tr>
							</table>
						</div>';

		// Creation du div de sélection des groupes abonnés à l'alarme

		$group_div .= '<div style="float:left">';
		$group_div .= '<p class="texteGrisBold">'.$this->alarmModel->translate('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_SUSCRIBED_GROUP').'</p>';
		$group_div .= '<select name="suscribed_groups" size="10" multiple="multiple" style="width:210px;"></select>';
		$group_div .= '</div>';

		$group_div .= '</div></fieldset>';

		$group_div .= '<div align="center" style="padding:5px"><input type="button" value="Save" onClick="saveSelectedGroup(\'suscribed_groups\')"/></div>';

		$group_div .= '</div>';
		
		return $group_div;
	}
}

?>