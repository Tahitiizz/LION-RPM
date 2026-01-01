<?
/*
 *	@cb50400@
 *
 *  12/08/2010 NSE bz DE Firefox 16851 : les lignes <!----------> ne sont pas supportées par Firefox, remplacement par <!-- ___ -->
 *  13/09/2010 NSE bz 17813 : simulation du clic KO sous FF, validation formulaire non fonctionnelle
 */
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 26/05/2008 Benjamin : selection d'un label TRIGGER uniquement si le TRIGGER existe. BZ6667
*	- 13:58 28/01/2008 Gwénaël : ajout d'une condition pour savoir si les 2 champs (période/nb itération) sont vides (si on ne fait rien)
*	- maj 16:40 07/12/2007 Gwénaël :  ajout d'un message d'erreur si la période est supérieur à l'historique
*	- maj 08:24 16/11/2007 Gwénaël : ajout des champs nombre d'itération et période

	- maj 11/03/2008, benoit : correction du bug 3819
	- maj 12/03/2008, benoit : correction du bug 3819
	
	- maj 09:21 28/03/2008 GHX : ajout d'une condition pour que le nombre d'itération et la période soient supérieur à 1
	
	23/07/2009 GHX
		- Correction du BZ 10511 / 10512 / 10513
			-> Suppression des accents dans les commentaires JS
*
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
/*

	- maj 03/10/2006, xavier : modification de la fonction javascript check_form.
					  résolution du bug sur le nom d'alarme.
					  affichage d'un message d'erreur lorsque le kpi/raw de tri et/ou le trigger prend une valeur non numérique.

	- maj 24/10/2006, xavier : modification de l'affichage des messages d'erreurs

	- maj 25/10/2006, xavier : message d'erreur si que opérande, que valeur, ou valeur incorrecte pour un         trigger.

	- maj 06/11/2006, benoit : limitation de la taille du label raw/kpi à 40 caractères et vérification de la     valeur du champ (label non nul)

	- maj 27/02/2007, benoit : extension de la limitation du label à 37 caractères.

	- maj 27/02/2007, benoit : ajout d'un parametre à la fonction 'getFieldValue()' indiquant le nombre de        caracteres autorisé dans les selects.

*/

include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");

Class alarm_list {
	var $univers;

	function alarm_list($family,$alarm_id,$alarm_type,$table_alarm,$allow_modif, $max_period, $ta, $product='') {

		global $debug;
		$this->debug = $debug;
		$this->debug = false;
		if ($this->debug)
			echo "<div class='debug'>new alarm_list(family=<strong>$family</strong>, alarm_id=<strong>$alarm_id</strong>, alarm_type=<strong>$alarm_type</strong>, table_alarm=<strong>$table_alarm</strong>, allow_modif=<strong>$allow_modif</strong>, max_period=<strong>$max_period</strong>, ta=<strong>$ta</strong>, product=<strong>$product</strong>);</div>";
		
		// connexion à la base de donnée
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->db = Database::getConnection($product);

		// récupération des droits utilisateurs
		// un administrateur en mode client ne peut pas modifier une alarme en mode customisateur
		$this->allow_modif = $allow_modif;

		// récupération des données
		$this->alarm_type	= $alarm_type;
		$this->alarm_id		= $alarm_id;
		$this->product		= $product;
		$this->family		= $family;
		$this->table_alarm	= $table_alarm;

		// création de la liste des kpi et des raw counters
		$this->kpi		= get_kpi(		$this->family,$this->product);
		$this->counter	= get_counter(	$this->family,$this->product);

		// modif 08:26 16/11/2007 Gwen - ajout des champs nb_iteration & period
		$this->max_period = $max_period;
		// modif 24/12/2007 Gwen - ajout du paramètre TA
		$this->ta = $ta;
		// début de l'affichage de la partie spécifique à Top Worst Cell List
		$this->display();
	}


	// on récupère les informations concernant le sort by et le trigger de la top/worst cell list.
	// ces informations sont affichées dans des champs non modifiables si $this->allow_modif est à faux.
	// sinon, les informations sont modifiables et une icone permet de vider les champs du sort by ou du trigger.
	function display_info_global_bloc()
	{
		global $niveau0;

		$query = "
			SELECT DISTINCT ON (alarm_trigger_data_field) *
			FROM $this->table_alarm
			WHERE alarm_id = '$this->alarm_id'
				AND additional_field is null ";
		$row = $this->db->getrow($query);

		$query2 = "SELECT nb_iteration, period FROM $this->table_alarm WHERE alarm_id='$this->alarm_id' LIMIT 1";
		$row2 = $this->db->getrow($query2);
		$nb_iteration = $row2['nb_iteration'];
		$period = $row2['period'];
	
		if ($nb_iteration == 1 && $period == 1) {
			$nb_iteration = '';
			$period = '';
		}
	
		// modif 24/412/2007 Gwen	
		switch ( $this->ta ) {
			case 'hour':
				$max_period_display = $this->max_period.' hours ('.($this->max_period/24). ' days)';
				break;
			case 'day':
			case 'day_bh':
				$max_period_display = $this->max_period.' days';
				break;
			case 'week':
			case 'week_bh':
				$max_period_display = $this->max_period.' weeks';
				break;
			case 'month':
			case 'month_bh':
				$max_period_display = $this->max_period.' months';
				break;
			default :
				$max_period_display = $this->max_period.' days';
		}
	
		// modif 16:39 07/12/2007 Gwen - ajout d'un message d'erreur si la période est supérieur à l'historique
		if ( $this->max_period < $period ) {
			echo '<div class="texteGris" style="color:red; margin: 4px 0 2px 4px">'.__T('A_E_ALARM_MAX_PERIOD_EXCEEDED').'</div>';
		}
		if (!$this->allow_modif) { ?>
			<div>
			<table>
			<tr>
				<td class="texteGris">
					<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px"><?php echo __T('A_ALARM_FORM_LABEL_NB_ITERATION'); ?> :</label>
					<input class="zoneTexteStyleXPFondGris" name="nb_iteration_" id="nb_iteration_" style="width:80px" value="<?=$nb_iteration?>" readonly>
				</td>
				<td class="texteGris">
					<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px">&nbsp;<?php echo __T('A_ALARM_FORM_LABEL_PERIOD'); ?> :</label>
					<input id="period_<?=$critical_level?>" class="zoneTexteStyleXPFondGris" name="period_" id="period_" style="width:80px" value="<?=$period?>" readonly> 
				</td>
			</tr>
			</table>
			</div>
		<? } else { ?>
			<div>
			<table>
			<tr>
				<td class="texteGris">
					<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px"><?php echo __T('A_ALARM_FORM_LABEL_NB_ITERATION'); ?> :</label>
					<input class="zoneTexteStyleXP" name="nb_iteration_" id="nb_iteration_" style="width:80px" value="<?=$nb_iteration?>">
				</td>
				<td class="texteGris">
					<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px">&nbsp;<?php echo __T('A_ALARM_FORM_LABEL_PERIOD'); ?> :</label>
					<input id="period_<?=$critical_level?>" class="zoneTexteStyleXP" name="period_" id="period_" style="width:80px" value="<?=$period?>" <? if( $this->max_period != '' ) { echo "onmouseover=\"popalt('".__T('A_TOOLTIP_ALARM_MAX_PERIOD',$max_period_display)."')\""; } ?>> 
				</td>
			</tr>
			</table>
			</div>
		<? } ?>

		<table id="trigger_list">
			<tr><td class="texteGris" colspan="2"><li>Sort condition</td></tr>

			<!-- _______________________________ -->

			<?php
				$array_sort_type = Array('kpi' => 'KPI',	'raw' => 'Raw Counter');
//				$array_sort_type_label = Array('KPI','Raw Counter');

				// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
				// du type de 'sort by' dans un champ texte non modifiable.
				if (!$this->allow_modif) {
					echo "
					<td><input class='zoneTexteStyleXPFondGris' name='sort_type' style='width:100px'
						value='{$array_sort_type[$row['list_sort_field_type']]}' readonly='readonly'/>
					</td>";
				} else {
					//sinon, on affiche la liste des choix possibles
					// 27/02/2007 - Modif. benoit : ajout d'un parametre sur la taille max du label dans 'getFieldValue()'
					// 12/03/2008 - Modif. benoit :
                    // 15/09/2010 NSE bz 17813 : ajout de l'attribut  id="sort_type"
					?>
					<tr>
						<td>
							<select class="zoneTexteStyleXP" id="sort_type" name="sort_type" style="width:100px" onchange="deleteCompleteTriggerTWLabel('sort_field');getFieldValue(this.value,'<?="sort_field"?>','<?=$this->product?>','<?=$this->family?>', 37)">
								<option value='makeSelection'>Type</option>
								<?
									foreach ($array_sort_type as $sort_type => $sort_label) {
										$selected='';
										if ($sort_type==$row['list_sort_field_type'])
											$selected="selected='selected'";
										echo "\n	<option value='$sort_type'  $selected>$sort_label</option>";
									}
								?>
							</select>
						</td>
				<? } ?>

				<!-- _______________________________ -->

			<?php
				$array_list_sort_field = array();
				if ($row['list_sort_field_type'] == "kpi") 	$array_list_sort_field = $this->kpi;
				if ($row['list_sort_field_type'] == "raw")	$array_list_sort_field = $this->counter;

				// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
				// du 'sort by' dans un champ texte non modifiable.
				if (!$this->allow_modif) {
					// 06/11/2006 - Modif. benoit : limitation du label à 40 caractères
					// 12/03/2008 - Modif. benoit : ajout d'une variable contenant le label complet du sort
					$sort_field_complete_label = $array_list_sort_field[$row['list_sort_field']];

					if (strlen($sort_field_complete_label) > 40)
							$sort_field = substr($sort_field_complete_label, 0, 40)."...";
					else		$sort_field = $sort_field_complete_label;
					?>
					<td><input class="zoneTexteStyleXPFondGris" id="sort_field" name="sort_field" style="width:300px" value="<?=$sort_field?>" readonly></td>
						<input name="sort_field_value" type="hidden" value="<?=$row['list_sort_field']?>">
					<?
				} else {
					// sinon, on affiche la liste des choix possible
					?>
					<td>
						<select class="zoneTexteStyleXP" id="sort_field" name="sort_field" style="width:300px" onchange="remove_choice(this)">
							<? if ($row['list_sort_field'] == '') { ?>
								<option value='makeSelection'>Make a selection</option>
							<? }

							foreach ($array_list_sort_field as $name => $label) {
								// 06/11/2006 - Modif. benoit : limitation du label à 40 caractères
								// 27/02/2007 - Modif. benoit : extension de la limitation à 37 caractères

								if (trim($label) == "") $label = $name;
			
								// 12/03/2008 - Modif. benoit : ajout de l'attribut 'label_complete' à l'option de la liste des raws / kpis
								$label_complete = $label;
					
								if (strlen($label) > 40) $label = substr($label, 0, 37)."...";

								$selected="";
								if ($name==$row['list_sort_field']) $selected="selected='selected'";
								
								echo "
									<option value='$name' label_complete=\"$label_complete\" $selected>$label</option> ";
							}
							?>
						</select>
					</td>

				<? } ?>

				<!-- _______________________________ -->

				<? if (!$this->allow_modif) { ?>
					<td><input class="zoneTexteStyleXPFondGris" name="sort_by" style="width:60px" value="<?=$row['list_sort_asc_desc']?>" readonly></td>
				<? } else { ?>
					<td>
						<select class="zoneTexteStyleXP" name="sort_by" style="width:60px">
							<?php
							$array_sort_by = Array('asc','desc');
							foreach ($array_sort_by as $a_sort_by) {
								$selected="";
								if ($a_sort_by==$row['list_sort_asc_desc'])
									$selected="selected='selected'";
								echo "
									<option value='$a_sort_by'  $selected>$a_sort_by</option> ";
							}
							?>
						</select>
					</td>
					<td>&nbsp;&nbsp;<img src='<?=$niveau0?>images/icones/drop.gif' style='cursor:pointer' onclick="deleteCompleteTriggerTWLabel('sort_field');remove_sort_list()"></td>
				<? } ?>

			<!-- _______________________________ -->

			<?php
			// 12/03/2008 - Modif. benoit : si l'on est en mode lecture seule mais que le label du field est tronqué on crée une ligne pour afficher le label complet du field
			if ((!$this->allow_modif) && ($sort_field != $sort_field_complete_label)) { ?>
				<tr>
					<td></td>
					<td colspan="2" class="texteGris" style="font-size:8pt"><?=$sort_field_complete_label?></td>
				</tr>
			<?php } ?>

			</tr>
			<tr><td class="texteGris" colspan="2"><li>Trigger</td></tr>
			<tr>

			<!-- _______________________________ -->

			<?php
			$array_trigger_type = Array('kpi' => 'KPI',	'raw' => 'Raw Counter');

			// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
			// du type du 'trigger' dans un champ texte non modifiable.
			if (!$this->allow_modif) {
	
				// maj 26/05/2008 Benjamin : sélection d'un label TRIGGER uniquement si le TRIGGER existe
				$label = ($row['alarm_trigger_type'] != "") ? $array_trigger_type[$row['alarm_trigger_type']] : "";
				?>
				<td><input class="zoneTexteStyleXPFondGris" name="trigger_type" style="width:100px" value="<?=$label?>" readonly></td>
				<?
			} else {
				// sinon, on affiche la liste des choix possibles
				// 27/02/2007 - Modif. benoit : ajout d'un parametre sur la taille max du label dans 'getFieldValue()'
                                // Bug 34041 - [REC][CB 5.3.1.01][Create Top/Worst List ][Firefox 21]: Counter, KPI, an operator and value are NOT deleted after user clicked on "X" button for Trigger
				?>
				
				<td>
					<select class="zoneTexteStyleXP" id="trigger_type" name="trigger_type" style="width:100px" onchange="deleteCompleteTriggerTWLabel('trigger_field');getFieldValue(this.value,'<?="trigger_field"?>','<?=$this->product?>','<?=$this->family?>', 37)">
						<option value='makeSelection'>Type</option>
							<?
							foreach ($array_trigger_type as $trigger_type => $trigger_label) {
								$selected="";
								if ($trigger_type==$row['alarm_trigger_type'])
									$selected="selected='selected'";
								echo "
									<option value='$trigger_type' $selected>$trigger_label</option> ";
							}
							?>
					</select>
				</td>
				<?
			}
			?>

			<!-- _______________________________ -->

			<?
			$array_trigger_field = array();
			if ($row['alarm_trigger_type'] == "kpi")	$array_trigger_field = $this->kpi;
			if ($row['alarm_trigger_type'] == "raw")	$array_trigger_field = $this->counter;

			// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
			// du 'trigger' dans un champ texte non modifiable.
			if (!$this->allow_modif) {
				// 06/11/2006 - Modif. benoit : limitation du label à 40 caractères
				// 12/03/2008 - Modif. benoit : ajout d'une variable contenant le label complet du trigger
				$trigger_field_complete_label = $array_trigger_field[$row['alarm_trigger_data_field']];

				if (strlen($trigger_field_complete_label) > 40)
						$trigger_field = substr($trigger_field_complete_label, 0, 40)."...";
				else		$trigger_field = $trigger_field_complete_label;
				
				?>
				<td><input class="zoneTexteStyleXPFondGris" id="trigger_field" name="trigger_field" style="width:300px" value="<?=$trigger_field?>" readonly></td>
					<input name="trigger_field_value" type="hidden" value="<?=$row['alarm_trigger_data_field']?>"/>

				<?
			} else {
				// sinon, on affiche la liste des choix possibles
				?>
				<td>
					<select class="zoneTexteStyleXP" id="trigger_field" name="trigger_field" style="width:300px" onchange="remove_choice(this)">
					<? if ($row['alarm_trigger_data_field'] == '')
						echo "
						<option value='makeSelection'>Make a selection</option> ";

						foreach($array_trigger_field as $name => $label) {
							// 06/11/2006 - Modif. benoit : limitation du label à 40 caractères
							// 27/02/2007 - Modif. benoit : extension de la limitation à 37 caractères
							if(trim($label) == "") $label = $name;
							// 11/03/2008 - Modif. benoit : ajout de l'attribut 'label_complete' à l'option de la liste des raws / kpis
							$label_complete = $label;
							if (strlen($label) > 37) $label = substr($label, 0, 37)."...";

							$selected="";
							if ($name==$row['alarm_trigger_data_field'])
								$selected="selected='selected'";
							
							echo "
								<option value='$name' label_complete=\"$label_complete\" $selected>$label</option> ";
						}
					?>
					</select>
				</td>
			<? } ?>

			<!-- _______________________________ -->

			<? if (!$this->allow_modif) { ?>
				<td><input class="zoneTexteStyleXPFondGris" name="trigger_operand" style="width:60px" value="<?=$row['alarm_trigger_operand']?>" readonly></td>
			<? } else {
                            // 15/09/2010 NSE bz 17813 : ajout de id ?>
				<td>
					<select class="zoneTexteStyleXP" id="trigger_operand" name="trigger_operand" style="width:60px">

						<?
						$operand_possible = array('none','=','<=','>=','<','>');
	
						foreach ($operand_possible as $operand) {
							
							$selected="";
							if ($operand==$row['alarm_trigger_operand'])
								$selected="selected='selected'";
							echo "\n	<option value='$operand' $selected>$operand</option>";
						}
						?>
					</select>
				</td>
			<? } ?>

			<!-- _______________________________ -->

			<? if (!$this->allow_modif) { ?>
				<td><input class="zoneTexteStyleXPFondGris" name="trigger_value" style="width:45px" value="<?=$row['alarm_trigger_value']?>" readonly></td>
			<? } else {
                                // 15/09/2010 NSE bz 17813 : ajout de id ?>
                                <td><input class="zoneTexteStyleXP" id="trigger_value" name="trigger_value" style="width:45px" value="<?=$row['alarm_trigger_value']?>"></td>
				<td><img src='<?=$niveau0?>images/icones/drop.gif' style='cursor:pointer' onclick="deleteCompleteTriggerTWLabel('trigger_field');remove_trigger_list()"></td>
			<? } ?>
		</tr>

		<?php
		// 12/03/2008 - Modif. benoit : si l'on est en mode lecture seule mais que le label du field est tronqué on crée une ligne pour afficher le label complet du field
		if ((!$this->allow_modif) && ($trigger_field != $trigger_field_complete_label)) {
			?>
			
			<tr>
				<td></td>
				<td colspan="2" class="texteGris" style="font-size:8pt"><?=$trigger_field_complete_label?></td>
			</tr>

		<?php } 

		echo "\n</table>";
	}


	// créer le fieldset de trigger list
	function display_bloc_info_global() { ?>
		<table width="100%">
			<tr>
				<td align=left>
					<fieldset>
						<legend class="texteGrisBold">&nbsp;<?echo __T('A_ALARM_FORM_FIELDSET_TRIGGER_LIST');?>&nbsp;&nbsp;</legend>
						<?$this->display_info_global_bloc();?>
					</fieldset>
				</td>
			</tr>
		</table>
		<?
	}


	// contient la liste des fonctions javascript utilisées pour valider l'envoi
	// du formulaire et pour vider le sort by et le trigger.
	function display() { ?>

		<table width="550" align="center" border=0 cellpadding="0" cellspacing="0">
			<tr><td><?$this->display_bloc_info_global();?></td></tr>
		</table>
		
		<script language="JavaScript">

		/**
		*  verifie que les champs obligatoires ont bien ete remplis.
		*  les champs facultatifs partiellement remplis seront ignores.
		*/
		function check_form () {

			// La fonction check_field_format presente dans setup_alarm.class.php
			// verifie que les champs communs aux alarmes sont biens remplis.
			message = check_field_format($('alarm_name'));
			if (message == false) {

				// on retourne sur le premier onglet pour verification du trigger
				// 13/09/2010 NSE bz 17813 : simulation du clic KO sous FF $('tabTabalarm_tab_view_0').click();
                        // on utilise la fonction tabClick
                        var idArray = $('tabTabalarm_tab_view_0').id.split('_');
                        showTab($('tabTabalarm_tab_view_0').parentNode.parentNode.id,idArray[idArray.length-1].replace(/[^0-9]/gi,''));

				temoin=0; // aucun counter ou kpi selectionne

				if ($('sort_field').value != 'makeSelection') {
					temoin=1; // au moins un raw ou kpi selectionne mais aucun sort defini
				}

				// maj 03 10 2006 xavier
				trigger_type		= $('trigger_type');
				trigger_field		= $('trigger_field');
				trigger_value		= $('trigger_value');
				trigger_operand	= $('trigger_operand');

				// maj 25/10/2006 xavier
				if (trigger_field.value != 'makeSelection') {
					if (trigger_value.value != '') {
						if (trigger_operand.value == 'none') {
							alert('you must enter an operand for this trigger');
							trigger_operand.focus();
							return false;
						}
						if (trigger_value.value != parseFloat(trigger_value.value)) {
							alert('you must enter a numeric value for this trigger');
							trigger_value.focus();
							return false;
						}
					} else {
						if (trigger_operand.value != 'none') {
							alert('you must enter a numeric value  for this trigger');
							trigger_value.focus();
							return false;
						}
					}
				}
				// >>>>>>>>>>
				// modif 08:39 16/11/2007 Gwen
				// Ajout d'un check pour savoir si c'est des valeurs numeriques et que si l'un des champs est renseigne l'autre doit l'etre aussi
				// Ajout d'un check sur la periode pour verifier quelle ne depasse pas la valeur maximal
				var p = $('period_').value;
				var nbi = $('nb_iteration_').value;
			
				// modif 13:57 28/01/2008 Gwenaël - ajout de la condition si les 2 champs sont vide on ne fait rien
				if  ( p != '' && nbi != '' ) {
					if ( parseInt(nbi) <= 0 ) {
						alert('<?php echo __T('A_JS_ALARM_NUMERIC_NEGATIVE'); ?>');
						//$('nb_iteration_').value = '';
						$('nb_iteration_').focus();
						return false;
					}
					else if ( parseInt(nbi) == 1 || parseInt(p) == 1 ) {
						alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_AND_PERIODE_MUST_BE_GREATHER_THAN_ONE'); ?>');
						$('nb_iteration_').focus();
						return false;
					}
					else if ( parseInt(p) <= 0 ) {
						alert('<?php echo __T('A_JS_ALARM_NUMERIC_NEGATIVE'); ?>');
						//$('period_').value = '';
						$('period_').focus();
						return false;
					}
					else if ( (p != '' && nbi == '') || nbi != parseInt(nbi) ) {
						alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_EMPTY'); ?>');
						//$('nb_iteration_').value = '';
						$('nb_iteration_').focus();
						return false;
					}
					else if ( (p == '' && nbi != '') || p != parseInt(p) ) {
						alert('<?php echo __T('A_JS_ALARM_MAX_PERIOD_EMPTY'); ?>');
						//$('period_').value = '';
						$('period_').focus();
						return false;
					}
					else if ( parseInt(nbi) > parseInt(p) ) {
						alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_GREATER_THAN_PERIODE'); ?>');
						$('nb_iteration_').focus();
						return false;
					}
					if ( p != '' ) {
						if ( parseInt(p) > MAX_PERIOD[__time_to_sel] ) {
							var msg = "<?php echo __T('A_JS_ALARM_MAX_PERIOD_EXCEEDED', 'XXX'); ?>";
							msg = msg.replace(/XXX/, MAX_PERIOD[__time_to_sel]+' '+TA_LABEL[__time_to_sel]);
							alert(msg);
							$('period_').focus();
							return false;
						}
					}
				}
				// <<<<<<<<<<
				if (temoin==1) return true;

				if (temoin==0) {
					alert('Please, select a sort by element');
					$('sort_type').focus();
				}
			}
			// maj 24/10/2006 xavier
			if (message) alert(message);
			return false;
		}

		// on reinitialise le choix et la valeur du 'sort by'
		function remove_sort_list() {
			$('sort_field').options[0].value='makeSelection';
			$('sort_field').options[0].text='Make a selection';
			$('sort_field').length=1;
			$('sort_field').selectedIndex=0;
			$('sort_type').selectedIndex=0;
			$('sort_by').selectedIndex=0;
		}

		// on reinitialise le choix et la valeurs du 'trigger'
		function remove_trigger_list() {
			$('trigger_field').options[0].value='makeSelection';
			$('trigger_field').options[0].text='Make a selection';
			$('trigger_field').length=1;
			$('trigger_field').selectedIndex=0;
                        //Bug 34041 - [REC][CB 5.3.1.01][Create Top/Worst List ][Firefox 21]: Counter, KPI, an operator and value are NOT deleted after user clicked on "X" button for Trigger
			$('trigger_type').options[0].value='makeSelection';
                        $('trigger_type').options[0].text='Type';
                        $('trigger_type').selectedIndex=0;
			$('trigger_operand').selectedIndex=0;
			$('trigger_value').value='';
		}

		window.focus();

		<?php
		// 12/03/2008 - Modif. benoit : 
		if ($this->allow_modif) {
			?>
			showCompleteTriggerTWLabel($('sort_field'));
			showCompleteTriggerTWLabel($('trigger_field'));
			<?php
		}
		?>

	</script>
	<?
	}

}//fin class

?>
