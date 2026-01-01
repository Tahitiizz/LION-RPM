<?
/*
 *	@cb50400@
 *
 *  12/08/2010 NSE bz DE Firefox 16851 : les lignes <!----------> ne sont pas support�es par Firefox, remplacement par <!-- ___ -->
 *  13/08/2010 DE Firefox bz 16905 : probl�mes de pr�sentation
 *  13/09/2010 NSE bz 17813 : simulation du clic KO sous FF
 */
?><?
/*
*	@cb41000@
*
*	- maj 14/10/2008 SLC : corrections d'affichage suite � ajout du DOCTYPE
*	20/04/2009 - SPS : ajout des id pour tous les input pour l'affichage des seuils des triggers sous ie8
*
*/
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 15/04/2008 Benjamin - Modification de la chaine en enlevant les \' et ' qui provoquent des erreurs dans $msg_threshold
*	- maj 16:40 07/12/2007 Gw�na�l :  ajout d'un message d'erreur si la p�riode est sup�rieur � l'historique
*	- maj 11:17  28/11/2007 Maxime : ajout d'une item d'information expliquant le threshold
*	- maj 08:21 16/11/2007 Gw�na�l : ajout des champs nombre d'it�ration et p�riode

	- maj 11/03/2008, benoit : correction du bug 3819
	- maj 12/03/2008, benoit : correction du bug 3819
	
	- maj 09:21 28/03/2008 GHX : ajout d'une condition pour que le nombre d'it�ration et la p�riode soient sup�rieur � 1
	- maj 11/06/2008 - Maxime : Correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticit�
	
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
							   r�solution du bug sur le nom d'alarme.
							   affichage d'un message d'erreur lorsqu'un threshold et/ou un trigger prend une valeur non num�rique.

	- maj 24/10/2006, xavier : modification de l'affichage des messages d'erreurs

	- maj 25/10/2006, xavier : message d'erreur si que op�rande, que valeur, ou valeur incorrecte pour un         trigger.

	- maj 06/11/2006, benoit : correction du message d'erreur sur la valeur non num�rique. Remplacement de        "trigger" par "threshold"

	- maj 06/11/2006, benoit : limitation de la taille du label raw/kpi � 40 caract�res et v�rification de la     valeur du champ (label non nul)

	- maj 27/02/2007, benoit : reduction de la limitation du label � 52 caract�res.

	- maj 27/02/2007, benoit : ajout d'un parametre � la fonction 'getFieldValue()' indiquant le nombre de        caracteres autoris� dans les selects.

	- maj 27/02/2007 Gw�na�l : ajout d'un message pour dire lorsqu'un trigger est s�lectionn� et n'est pas renseign�

	05/05/2015 JLG Mantis 6470 : manage dynamic alarm threshold operand (min/max)
*/

include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");
include_once($repertoire_physique_niveau0 . "class/CbCompatibility.class.php");

Class alarm_dynamic {
	var $univers;

	function alarm_dynamic($family,$alarm_id,$alarm_type,$table_alarm,$allow_modif, $max_period, $ta, $product = '') {

		global $debug;
		$this->debug = $debug;
		$this->debug = false;
		if ($this->debug)
			echo "<div class='debug'>new alarm_dynamic(family=<strong>$family</strong>, alarm_id=<strong>$alarm_id</strong>, alarm_type=<strong>$alarm_type</strong>, table_alarm=<strong>$table_alarm</strong>, allow_modif=<strong>$allow_modif</strong>, max_period=<strong>$max_period</strong>, ta=<strong>$ta</strong>, product=<strong>$product</strong>);</div>";
		
		// connexion � la base de donn�e
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->db = Database::getConnection($product);

		// r�cup�ration des droits utilisateurs
		// un administrateur en mode client ne peut pas modifier une alarme en mode customisateur
		$this->allow_modif = $allow_modif;

		// r�cup�ration des donn�es
		$this->alarm_type	= $alarm_type;
		$this->alarm_id		= $alarm_id;
		$this->product		= $product;
		$this->family		= $family;
		$this->table_alarm	= $table_alarm;

		// cr�ation de la liste des kpi et des raw counters
		$this->kpi		= get_kpi($this->family,$this->product);
		$this->counter	= get_counter($this->family,$this->product);

		// modif 14:50 13/11/2007 Gwen - ajout des champs nb_iteration & period
		$this->max_period = $max_period;
		// modif 24/12/2007 Gwen - ajout du param�tre TA
		$this->ta = $ta;
		// d�but de l'affichage de la partie sp�cifique � dynamic alarm
		$this->display();
	}

	// on r�cup�re les informations concernant le threshold et le trigger de la dynamic alarm
	// ces informations sont affich�es dans des champs non modifiables si $this->allow_modif est � faux.
	// sinon, les informations sont modifiables et une icone permet de vider tous les champs d�pendants
	// du threshold ou du trigger.
	function display_info_global_bloc()  {
		global $niveau0;
		$query = "
			SELECT DISTINCT ON (alarm_trigger_data_field, alarm_field)
				alarm_field, alarm_field_type, alarm_trigger_data_field, alarm_trigger_type, alarm_trigger_operand
			FROM $this->table_alarm
			WHERE alarm_id = '$this->alarm_id'
				AND additional_field is null ";
		$row = $this->db->getrow($query);
		$msg_threshold = "<table>";
		
		// maj 15/04/2008 Benjamin - Modification de la chaine en enlevant les \' et ' qui provoquent des erreurs 
		// maj 11:17  28/11/2007 Maxime - Ajout d'une item d'information � c�t� du threshold qui d�crit le nouveau mode de calcul des alarmes dynamiques
		// Le tooltip contient une description du nouveau mode de calcul illustr�e par un exemple sous forme d'image.
		$msg_threshold.= "<tr><td><img src='{$niveau0}/images/divers/threshold.gif'></td>";  
		$msg_threshold.= "<td class='texteGris'>".__T('A_ALARM_INFORMATION_ITEM_THRESHOLD')."</td></tr></table>";
		// maj 15/04/2008 Benjamin - La chaine est pure, on peut g�n�rer des \'
		$msg_threshold = addslashes($msg_threshold);
		
		// maj 15/04/2008 Benjamin - ajout de '' autour de '< ?=($msg_threshold)? >'
		// 12/03/2008 - Modif. benoit : ajout d'un identifiant et d'un nom au tableau contenant le threshold et le trigger
		?>

		<table id="trigger_list_table" name="trigger_list_table">
			<tr><td class="texteGris"><li>Threshold&nbsp;&nbsp;<img src='<?=$niveau0?>images/icones/cercle_info.gif' style="cursor:pointer" onmouseover="popalt('<?=($msg_threshold)?>','Description of Calculation mode',true)" onmouseout="kill()"/></li></td></tr>
			<tr>

				<!-- _______________________________ -->

				<?
				$array_threshold_type = Array('kpi' => 'KPI',	'raw' => 'Raw Counter');

				// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
				// du type du 'threshold' dans un champ texte non modifiable.
				if (!$this->allow_modif) {
					?>
					<td><input class="zoneTexteStyleXPFondGris" name="threshold_type" style="width:100px" value="<?=$array_threshold_type[$row['alarm_field_type']]?>" readonly></td>
					<?
				} else {
					// sinon, on affiche la liste des choix possibles

					// 27/02/2007 - Modif. benoit : ajout d'un parametre sur la taille max du label dans 'getFieldValue()'
					?>
					<td><select class="zoneTexteStyleXP" id="threshold_type" name="threshold_type" style="width:100px" onchange="getFieldValue(this.value,'threshold_field','<?=$this->product?>','<?=$this->family?>', 52);remove_all_critical_level_threshold();changerLabel('threshold')">
						<option value='makeSelection'>Type</option>
						<?
						foreach ($array_threshold_type as $threshold_type => $threshold_label) {
							$selected="";
							if ($threshold_type ==$row['alarm_field_type'])
								$selected="selected='selected'";
							?>
							<option value='<?=$threshold_type?>'  <?=$selected?>><?=$threshold_label?></option>
							<?
						}
						?>
						</select>
					</td>
				<? } ?>

				<!-- _______________________________ -->

				<?php
					$array_threshold_field = array();
					if ($row['alarm_field_type'] == "kpi") 	$array_threshold_field = $this->kpi;
					if ($row['alarm_field_type'] == "raw") 	$array_threshold_field = $this->counter;

					// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
					// du 'threshold' dans un champ texte non modifiable.
					if (!$this->allow_modif) {

						// 06/11/2006 - Modif. benoit : limitation du label � 40 caract�res
						// 27/02/2007 - Modif. benoit : reduction de la limitation � 52 caract�res
						// 12/03/2008 - Modif. benoit : ajout d'une variable contenant le label complet du threshold
						$threshold_field_complete_label = $array_threshold_field[$row['alarm_field']];

						if (strlen($threshold_field_complete_label) > 52)
								$threshold_field = substr($threshold_field_complete_label, 0, 52)."...";
						else		$threshold_field = $threshold_field_complete_label;
					
						?>

						<td><input class="zoneTexteStyleXPFondGris" id="threshold_field" name="threshold_field" style="width:400px" value="<?=$threshold_field?>" readonly></td>
							<input name="threshold_field_value" type="hidden" value="<?=$row['alarm_field']?>"> <?
					} else {  // sinon, on affiche la liste des choix possibles
                                            // 12/08/2010 NSE De Firefox : on diminue la largeur du select de fa�on � faire dispara�tre la barre de d�filement horizontale
						?>
						<td>
							<select class="zoneTexteStyleXP" id="threshold_field" name="threshold_field" style="width:410px" onchange="remove_all_critical_level_threshold();changerLabel('threshold');remove_choice(this)">
								<?
								if ($row['alarm_field'] == '')
									echo "<option value='makeSelection'>Make a selection</option>";
		
								foreach($array_threshold_field as $name => $label) {
		
									// 06/11/2006 - Modif. benoit : limitation du label � 40 caract�res
									// 27/02/2007 - Modif. benoit : reduction de la limitation � 52 caract�res
									if (trim($label) == "") $label = $name;
		
									// 11/03/2008 - Modif. benoit : ajout de l'attribut 'label_complete' � l'option de la liste des raws / kpis
									$label_complete = $label;
		
									if (strlen($label) > 52) $label = substr($label, 0, 52)."...";
		
									$selected="";
									if ($name==$row['alarm_field'])
										$selected="selected='selected'";
									
									echo "\n  <option value='$name' label_complete=\"$label_complete\" $selected>$label</option>";
								}
								?>
							</select>
						</td>
                                                <!-- 12/08/2010 NSE DE Firefox : alignement de l'image avec les menus d�roulants -->
						<td><img src='<?=$niveau0?>images/icones/drop.gif' style="cursor:pointer" onclick="remove_threshold()"></td>
					<? } ?>

					<!-- _______________________________ -->
				</tr>

				<?php
				// 12/03/2008 - Modif. benoit : si l'on est en mode lecture seule mais que le label du field est tronqu� on cr�e une ligne pour afficher le label complet du field

				if ((!$this->allow_modif) && ($threshold_field != $threshold_field_complete_label)) { ?>
					<tr>
						<td></td>
						<td colspan="2" class="texteGris" style="font-size:8pt"><?=$threshold_field_complete_label?></td>
					</tr>
				<?php } ?>

					<tr><td class="texteGris"><li>Trigger</td></tr>
					<tr>

					<!-- _______________________________ -->


				<?
				$array_trigger_type = Array('kpi' => 'KPI', 'raw' => 'Raw Counter');
				// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
				// du type du 'trigger' dans un champ texte non modifiable.
				if (!$this->allow_modif) {
					?>
					<td><input class="zoneTexteStyleXPFondGris" id="trigger_type" name="trigger_type" style="width:100px" value="<?=$array_trigger_type[$row['alarm_trigger_type']]?>" readonly></td>
					<?
				} else {
					// sinon, on affiche la liste des choix possibles
					// 27/02/2007 - Modif. benoit : ajout d'un parametre sur la taille max du label dans 'getFieldValue()'
					?>
					<td>
						<select class="zoneTexteStyleXP" id="trigger_type" name="trigger_type" style="width:100px" onchange="getFieldValue(this.value,'trigger_field','<?=$this->product?>','<?=$this->family?>', 52);remove_all_critical_level_trigger();changerLabel('trigger')">
							<option value='makeSelection'>Type</option>
							<?
							foreach ($array_trigger_type as $trigger_type => $trigger_label) {
								$selected="";
								if ($trigger_type==$row['alarm_trigger_type'])
									$selected="selected='selected'";
								echo "\n	<option value='$trigger_type'  $selected>$trigger_label</option>";
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
				if ($row['alarm_trigger_type'] == "kpi") 	$array_trigger_field = $this->kpi;
				if ($row['alarm_trigger_type'] == "raw") 	$array_trigger_field = $this->counter;

				// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
				// du 'trigger' dans un champ texte non modifiable.
				if (!$this->allow_modif) {

					// 06/11/2006 - Modif. benoit : limitation du label � 40 caract�res
					// 27/02/2007 - Modif. benoit : reduction de la limitation � 52 caract�res
					// 12/03/2008 - Modif. benoit : ajout d'une variable contenant le label complet du trigger
					$trigger_field_complete_label = $array_trigger_field[$row['alarm_trigger_data_field']];

					if (strlen($trigger_field_complete_label) > 52)
							$trigger_field = substr($trigger_field_complete_label, 0, 52)."...";
					else		$trigger_field = $trigger_field_complete_label;

					?>
					<td><input class="zoneTexteStyleXPFondGris" id="trigger_field" name="trigger_field" style="width:400px" value="<?=$trigger_field?>" readonly></td>
					<input name="trigger_field_value" type="hidden" value="<?=$row['alarm_trigger_data_field']?>">
					<?

				} else {
					// sinon, on affiche la liste des choix possibles
                                    // 12/08/2010 NSE De Firefox : on diminue la largeur du select de fa�on � faire dispara�tre la barre de d�filement horizontale
					?>
					<td>
						<select class="zoneTexteStyleXP" id="trigger_field" name="trigger_field" style="width:410px" onchange="remove_all_critical_level_trigger();changerLabel('trigger');remove_choice(this)">
							<?
							if ($row['alarm_trigger_data_field'] == '')
								echo "\n	<option value='makeSelection'>Make a selection</option>";
		
							foreach($array_trigger_field as $name => $label) {
		
								// 06/11/2006 - Modif. benoit : limitation du label � 40 caract�res
								// 27/02/2007 - Modif. benoit : reduction de la limitation � 52 caract�res
		
								if(trim($label) == "") $label = $name;
		
								// 11/03/2008 - Modif. benoit : ajout de l'attribut 'label_complete' � l'option de la liste des raws / kpis
								$label_complete = $label;
								if (strlen($label) > 52) $label = substr($label, 0, 52)."...";
		
								$selected="";
								if ($name==$row['alarm_trigger_data_field'])
									$selected="selected='selected'";
			
								echo "\n	<option value='$name' label_complete=\"$label_complete\" $selected>$label</option>";
							}
							?>
						</select>
					</td>
                                        <!-- 12/08/2010 NSE DE Firefox : alignement de l'image avec les menus d�roulants + suppression barre d�filement horizontale -->
					<td><img src='<?=$niveau0?>images/icones/drop.gif' style="cursor:pointer" onclick="remove_trigger()"></td>
					<?
				}
				?>

				<!-- _______________________________ -->

				</tr>
				
				<?php

				// 12/03/2008 - Modif. benoit : si l'on est en mode lecture seule mais que le label du field est tronqu� on cr�e une ligne pour afficher le label complet du field
				if ((!$this->allow_modif) && ($trigger_field != $trigger_field_complete_label)) { ?>
				
					<tr>
						<td></td>
						<td colspan="2" class="texteGris" style="font-size:8pt"><?=$trigger_field_complete_label?></td>
					</tr>
				
					<?php
				}
		echo "\n</table>";
	}

	// on r�cup�re les informations concernant les valeurs du threshold et du trigger
	// de la dynamic alarm pour un niveau de criticit� '$critical_level'.
	// ces informations sont affich�es dans des champs non modifiables si $this->allow_modif est � faux.
	// sinon, les informations sont modifiables et une icone permet de vider
	// les champs du threshold ou du trigger pour le niveau de criticit� '$critical_level'.
	function display_info_trigger($critical_level)
	{
		global $niveau0;

		// 05/05/2015 JLG : mantis 6470
		$canManageThresholdOperand = CbCompatibility::canManageThresholdOperand($this->product);
		$alarm_threshold_operand_select = '';
		if ($canManageThresholdOperand) {
			$alarm_threshold_operand_select = " alarm_threshold_operand, ";
		}
		$query = "
			SELECT DISTINCT ON (alarm_trigger_data_field, alarm_threshold)
				 $alarm_threshold_operand_select alarm_trigger_value, alarm_threshold, alarm_trigger_operand
			FROM $this->table_alarm
			WHERE alarm_id = '$this->alarm_id'
				AND critical_level = '$critical_level'
				AND additional_field is null ";
		$row = $this->db->getrow($query);
		// modif 08:23 16/11/2007 Gwen - ajout de 2 champs : number of iteration & period
		$query = "SELECT nb_iteration, period FROM $this->table_alarm WHERE alarm_id = '$this->alarm_id' AND critical_level = '$critical_level' LIMIT 1";
		$row2 = $this->db->getrow($query);
		$nb_iteration 	= $row2['nb_iteration'];
		$period		= $row2['period'];
	
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
	
		// modif 16:39 07/12/2007 Gwen - ajout d'un message d'erreur si la p�riode est sup�rieur � l'historique
		if ( $this->max_period < $period )
			echo '<div class="texteGris" style="color:red; margin: 4px 0 2px 4px">'.__T('A_E_ALARM_MAX_PERIOD_EXCEEDED').'</div>';

		if (!$this->allow_modif) { ?>
			<div>
			<table>
				<tr>
				<td class="texteGris">
					<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px"><?php echo __T('A_ALARM_FORM_LABEL_NB_ITERATION'); ?> :</label>
					<input class="zoneTexteStyleXPFondGris" name="nb_iteration_<?=$critical_level?>" id="nb_iteration_<?=$critical_level?>" style="width:80px" value="<?=$nb_iteration?>" readonly> 
				</td>
				<td class="texteGris">
					<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px">&nbsp;<?php echo __T('A_ALARM_FORM_LABEL_PERIOD'); ?> :</label>
					<input id="period_<?=$critical_level?>" class="zoneTexteStyleXPFondGris" name="period_<?=$critical_level?>" id="period_<?=$critical_level?>" style="width:80px" value="<?=$period?>" readonly> 
				</td>
				</tr>
			</table>
			</div>
			<?
		} else {
			?>
			<div>
			<table>
				<tr>
					<td class="texteGris">
						<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px"><?php echo __T('A_ALARM_FORM_LABEL_NB_ITERATION'); ?> :</label>
						<input class="zoneTexteStyleXP" name="nb_iteration_<?=$critical_level?>" id="nb_iteration_<?=$critical_level?>" style="width:80px" value="<?=$nb_iteration?>">
					</td>
					<td class="texteGris">
						<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px">&nbsp;<?php echo __T('A_ALARM_FORM_LABEL_PERIOD'); ?> :</label>
						<input id="period_<?=$critical_level?>" class="zoneTexteStyleXP" name="period_<?=$critical_level?>" id="period_<?=$critical_level?>" style="width:80px" value="<?=$period?>" <? if( $this->max_period != '' ) { echo "onmouseover=\"popalt('".__T('A_TOOLTIP_ALARM_MAX_PERIOD',$max_period_display)."')\""; } ?>> 
					</td>
				</tr>
			</table>
			</div>
			<?
		}
		?>

		<table>
			<tr><td class="texteGris" width="150px"><li><input tabIndex="-1" class="zoneTexteStyleXP" id="threshold_label_<?=$critical_level.$j?>" name="threshold_label_<?=$critical_level.$j?>" style="width:325px;border:0px" readonly></li></td>
				<?php /*<!-- _______________________________ -->*/ ?>
	
				<? if (!$this->allow_modif) { ?>
					<td class="texteGris"><input class="zoneTexteStyleXPFondGris" name="threshold_value_<?=$critical_level?>" style="width:80px" value="<?=$row['alarm_threshold']?>" readonly>&nbsp;%</td>
				<? } else { 
					// Maj 11/06/2008 - Maxime : Correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticit� 
					//					Appel de la fonction js remove_iteration
                                    // 13/08/2010 NSE DE Firefox : ajout de id
					?>
					<? if ($canManageThresholdOperand) { ?>
					<td>
						<select class="zoneTexteStyleXP" id="threshold_operand_<?=$critical_level?>" name="threshold_operand_<?=$critical_level?>" style="width:85px">
							  <?
							  $threshold_operand_possible[0]='both';
							  $threshold_operand_possible[1]='decrease';
							  $threshold_operand_possible[2]='increase';
							  for ($i=0;$i<count($threshold_operand_possible);$i++) {
								$selected="";
								if ($threshold_operand_possible[$i]==$row['alarm_threshold_operand'])
									$selected="selected='selected'";
								?>
								<option value='<?=$threshold_operand_possible[$i]?>'  <?=$selected?>><?=$threshold_operand_possible[$i]?></option>
								<?
							}
							?>
						</select>
					</td>
					<? }?>
					<td class="texteGris"><input class="zoneTexteStyleXP" id="threshold_value_<?=$critical_level?>" name="threshold_value_<?=$critical_level?>" style="width:60px" value="<?=$row['alarm_threshold']?>">%</td>
					<td width="30" align="right"><img src='<?=$niveau0?>images/icones/drop.gif' style="cursor:pointer" onclick="remove_critical_level_threshold('<?=$critical_level?>');remove_iteration('<?=$critical_level?>','trigger');"></td>
				<? } ?>
				<?php /*<!-- _______________________________ -->*/ ?>

			</tr>
			<tr><td class="texteGris"><li><input tabIndex="-1" class="zoneTexteStyleXP" id="trigger_label_<?=$critical_level.$j?>" name="trigger_label_<?=$critical_level.$j?>" style="width:325px;border:0px" readonly></td>
				<?php /*<!-- _______________________________ -->*/ ?>

				<? if (!$this->allow_modif) { ?>
					<td><input class="zoneTexteStyleXPFondGris" id="trigger_operand_<?=$critical_level?>" name="trigger_operand_<?=$critical_level?>" style="width:80px" value="<?=$row['alarm_trigger_operand']?>" readonly></td>
				<? } else { ?>
					<td>
						<select class="zoneTexteStyleXP" id="trigger_operand_<?=$critical_level?>" name="trigger_operand_<?=$critical_level?>" style="width:85px">
							  <?
							  $operand_possible[0]='none';
							  $operand_possible[1]='=';
							  $operand_possible[2]='<=';
							  $operand_possible[3]='>=';
							  $operand_possible[4]='<';
							  $operand_possible[5]='>';
							  for ($i=0;$i<count($operand_possible);$i++) {
								$selected="";
								if ($operand_possible[$i]==$row['alarm_trigger_operand'])
									$selected="selected='selected'";
								?>
								<option value='<?=$operand_possible[$i]?>'  <?=$selected?>><?=$operand_possible[$i]?></option>
								<?
							}
							?>
						</select>
					</td>
				<? } ?>
				<?php /*<!-- _______________________________ -->*/ ?>

				<? if (!$this->allow_modif) { ?>
					<td><input class="zoneTexteStyleXPFondGris" id="trigger_value_<?=$critical_level?>" name="trigger_value_<?=$critical_level?>" style="width:80px" value="<?=$row['alarm_trigger_value']?>" readonly></td>
				<? } else { 
					/**
					 *  Maj 11/06/2008 - Maxime : Correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticit� 
					 *			Appel de la fonction js remove_iteration
					 *	20/04/2009 - SPS : ajout de l'id trigger_value_* pour l'affichage des seuils des triggers sous ie8
					 */
					?>
					<td><input class="zoneTexteStyleXP" id="trigger_value_<?=$critical_level?>" name="trigger_value_<?=$critical_level?>" style="width:80px" value="<?=$row['alarm_trigger_value']?>"></td>
					<td align="right"><img src='<?=$niveau0?>images/icones/drop.gif' style="cursor:pointer" onclick="remove_critical_level_trigger('<?=$critical_level?>');remove_iteration('<?=$critical_level?>','threshold');"></td>
				<? } ?>
				<?php /*<!-- _______________________________ -->*/ ?>

			</tr>
		</table>	<?
	}


	// fonction qui met en page l'affichage du threshold, du trigger et de leurs valeurs respectives
	// suivant les niveaux de criticit�s.
	function display_bloc_info_global() { ?>
		<table width=100%>
			<tr>
				<td align=left>
					<fieldset>
					<legend class="texteGrisBold">&nbsp;<?echo __T('A_ALARM_FORM_FIELDSET_TRIGGER_LIST');?>&nbsp;&nbsp;</legend>
					<?$this->display_info_global_bloc();?>
					</fieldset>
				</td>
			</tr>
			<?
			$critical_level = Array('critical','major','minor');
			for ($i = 0; $i < count($critical_level); $i++) { ?>
				<tr>
					<td align=left>
					<fieldset>
						<legend class="texteGrisBold">&nbsp;<?=$critical_level[$i]?>&nbsp;&nbsp;<span style="background-color:<?=get_sys_global_parameters('alarm_' . $critical_level[$i] . '_color')?>;width:10px;height:10px;">&nbsp;&nbsp;</span>&nbsp;&nbsp;</legend>
						<?$this->display_info_trigger($critical_level[$i]);?>
					</fieldset>
					</td>
				</tr>
				<?
			}
		?>
		</table>
		<?
	}


	// contient la liste des fonctions javascript utilis�es pour valider l'envoi
	// du formulaire et pour vider le threshold et le trigger.
	function display() { ?>
		<table width="550" align="center" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td>
					<?$this->display_bloc_info_global();?>
				</td>
			</tr>
		</table>
		<script type="text/javascript">
		
			/**
			*	 Maj 11/06/2008 - Maxime : Correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticite
			*  Vide les informations d'une alarme ietrative
			*/
			function remove_iteration(critical, trigger_threshold) {
				if (critical == 'all') {
					if ( $(trigger_threshold+'_field').value == 'makeSelection'  ) {
			
						$('nb_iteration_critical').value = '';
						$('period_critical').value = '';
			
						$('nb_iteration_major').value = '';
						$('period_major').value = '';
						
						$('nb_iteration_minor').value = '';
						$('period_minor').value = '';
					}
				} else {
					if( $(trigger_threshold+'_value_'+critical).value == '' ) {
						$('nb_iteration_'+critical).value = '';
						$('period_'+critical).value = '';
					}
				}
			}
	  
			/**
			*  recupere le label du trigger ou du threshold pour l'afficher a gauche
			*  de chaque niveau de criticite.
			*  si pas de raw ou kpi choisi, on affiche 'No trigger selected' pour le trigger
			*  ou 'No threshold selected' pour le threshold.
			*/
			function changerLabel(type) {
				<?
				if ($this->allow_modif) {
					// on recupere le label depuis une liste type 'select'
					?>
					var champLabel = $(type+'_field').options[$(type+'_field').selectedIndex].text;
  
  				// 27/02/2007 - Modif. benoit :
					// 12/03/2008 - Modif. benoit : ajout du traitement permettant d'afficher le label complet du threshold ou du trigger lorsque celui-ci est tronque
					// On supprime l'ancienne ligne d'information sur le label (si elle existe)
					if ($(type+'_complete_label') != null) {
						$('trigger_list_table').deleteRow($(type+'_complete_label').rowIndex);
					}

					if (champLabel.length > 40) {
						champLabel = champLabel.substr(0, 40)+"...";
			
						var tableRef = $('trigger_list_table');	// tableau
						// Ajout de la ligne
						var row = $(type+'_field').parentNode.parentNode.rowIndex;

						var newRow = tableRef.insertRow(row+1);
						newRow.id = type+"_complete_label";
				
						// Ajout de la colonne
						newRow.insertCell(0);
						var newCell = newRow.insertCell(1);
						newCell.colSpan = "2";
						newCell.className = "texteGris";
						newCell.style.fontSize = "8pt";

						// Ajout du label complet dans la nouvelle colonne
                                                // 14/09/2010 BBX
                                                // Correction de la lecture du label complet
                                                // BZ 17889
                                                var champLabelComplet = $(type+'_field').options[$(type+'_field').selectedIndex].label_complete;
                                                if(typeof(champLabelComplet) == 'undefined')
                                                    champLabelComplet = $(type+'_field').options[$(type+'_field').selectedIndex].readAttribute('label_complete');


						var newText = document.createTextNode(champLabelComplet);
						newCell.appendChild(newText);
					}

					if ((document.getElementById(type+'_field').value == 'makeSelection')
						|| (document.getElementById(type+'_field').value == ''))
					champLabel = 'No '+type+' selected';
					$(type+'_label_critical').value = champLabel;
					$(type+'_label_major').value = champLabel;
					$(type+'_label_minor').value = champLabel;
					<?
				} else {
					// on recupere le label depuis un champ texte
					?>
					var champLabel = document.getElementById(type+'_field').value;

					// 27/02/2007 - Modif. benoit
					if (champLabel.length > 40) champLabel = champLabel.substr(0, 40)+"...";

					if ((champLabel == 'makeSelection') || (champLabel == '')) champLabel = 'No '+type+' selected';
					$(type+'_label_critical').value = champLabel;
					$(type+'_label_major').value = champLabel;
					$(type+'_label_minor').value = champLabel;
					<?
				}
				?>
				}

		// on reinitialise le choix et les valeurs du threshold
		function remove_threshold() {
			$('threshold_field').options[0].value='makeSelection';
			$('threshold_field').options[0].text='Make a selection';
			$('threshold_field').length=1;
			$('threshold_field').selectedIndex=0;
			$('threshold_type').selectedIndex=0;
			remove_all_critical_level_threshold();
			changerLabel('threshold');
		}
		
		// on reinitialise le choix et les valeurs du trigger
		function remove_trigger() {
			$('trigger_field').options[0].value='makeSelection';
			$('trigger_field').options[0].text='Make a selection';
			$('trigger_field').length=1;
			$('trigger_field').selectedIndex=0;
			$('trigger_type').selectedIndex=0;
			remove_all_critical_level_trigger();
			changerLabel('trigger');
		}
		
		// on reinitialise les valeurs du threshold pour tous les niveaux de criticite
		function remove_all_critical_level_threshold(critical) {
			remove_critical_level_threshold('critical');
			remove_critical_level_threshold('major');
			remove_critical_level_threshold('minor');
			remove_iteration('all','trigger');
		}
		
		// on reinitialise les valeurs du trigger pour tous les niveaux de criticite
		function remove_all_critical_level_trigger() {
			remove_critical_level_trigger('critical');
			remove_critical_level_trigger('major');
			remove_critical_level_trigger('minor');
			remove_iteration('all','threshold');
		}
		
		// on reinitialise les valeurs du threshold pour le niveau de criticite 'critical'
		function remove_critical_level_threshold(critical) {
			$('threshold_value_'+critical).value='';
		}
		
		// on reinitialise les valeurs du trigger pour le niveau de criticite 'critical'
		function remove_critical_level_trigger(critical) {
			$('trigger_operand_'+critical).selectedIndex=0;
			$('trigger_value_'+critical).value='';
		}

	/**
	*  verifie que les champs obligatoires ont bien ete remplis.
	*  les champs facultatifs partiellement remplis seront ignores.
	*/
	function check_form () {

		// La fonction check_field_format presente dans setup_alarm.class.php
		// verifie que les champs communs aux alarmes sont biens remplis.
		message = check_field_format(document.getElementById('alarm_name'));
		if (message == false) {

			// on retourne sur le premier onglet pour verification du trigger
			// 13/09/2010 NSE bz 17813 : simulation du clic KO sous FF $('tabTabalarm_tab_view_0').click();
                        // on utilise la fonction tabClick
                        var idArray = $('tabTabalarm_tab_view_0').id.split('_');
                        showTab($('tabTabalarm_tab_view_0').parentNode.parentNode.id,idArray[idArray.length-1].replace(/[^0-9]/gi,''));

			temoin=0; // aucun counter ou kpi selectionne

			if (document.getElementById('threshold_field').value != 'makeSelection') {

				temoin=1; // au moins un raw ou kpi selectionne mais aucun threshold defini
				critical = new Array('critical','major','minor')

				trigger_type = document.getElementById('trigger_type');
				trigger_field = document.getElementById('trigger_field');

				// on parcourt la liste de definition du threshold
				for (j=0;j<critical.length;j++) {

					// maj 03 10 2006 xavier

					threshold_value = document.getElementById('threshold_value_'+critical[j]);
					trigger_value = document.getElementById('trigger_value_'+critical[j]);
					trigger_operand = document.getElementById('trigger_operand_'+critical[j]);

					if (threshold_value.value != '') {
						// si la valeur du threshold n'est pas un nombre, on la met a null
						if (threshold_value.value != parseFloat(threshold_value.value)) {
							alert('you must enter a numeric value for this threshold');
							threshold_value.focus();
							return false;
						} else {
							temoin=2; // au moins un threshold defini

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
								}
								else
								{
									if (trigger_operand.value != 'none') {
										alert('you must enter a numeric value  for this trigger');
										trigger_value.focus();
										return false;
									}
									else { //modif 27/02/2007
										alert('Trigger not configured');
										trigger_operand.focus();
										return false;
									}
								}
							}
						}
					}
					// >>>>>>>>>>
					// modif 14:52 13/11/2007 Gwen
						// Ajout d'un check pour savoir si c'est des valeurs numeriques et que si l'un des champs est renseigne l'autre doit l'etre aussi
						// Ajout d'un check sur la periode pour verifier quelle ne depasse pas la valeur maximal
					if (temoin==2) {
						var p = $('period_'+critical[j]).value;
						var nbi = $('nb_iteration_'+critical[j]).value;
						
						if  ( p == '' && nbi == '' )
							continue;
						
						if ( (p != '' && nbi == '') || nbi != parseInt(nbi) ) { // si le champ periode est rempli le champ nombre d'iteration doit l'etre aussi
							alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_EMPTY'); ?>');
							//$('nb_iteration_'+critical[j]).value = '';
							$('nb_iteration_'+critical[j]).focus();
							return false;
						}
						else if ( parseInt(nbi) == 1 || parseInt(p) == 1 ) {
							alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_AND_PERIODE_MUST_BE_GREATHER_THAN_ONE'); ?>');
							$('nb_iteration_'+critical[j]).focus();
							return false;
						}
						else if ( (p == '' && nbi != '') || p != parseInt(p) ) { // si le champ nombre d'iteration est rempli le champ periode  doit l'etre aussi
							alert('<?php echo __T('A_JS_ALARM_MAX_PERIOD_EMPTY'); ?>');
							//$('period_'+critical[j]).value = '';
							$('period_'+critical[j]).focus();
							return false;
						}
						else if ( parseInt(nbi) <= 0 ) { // Le champ nombre d'iteration doit etre superieur a 0
							alert('<?php echo __T('A_JS_ALARM_NUMERIC_NEGATIVE'); ?>');
							//$('nb_iteration_'+critical[j]).value = '';
							$('nb_iteration_'+critical[j]).focus();
							return false;
						}
						else if ( parseInt(p) <= 0 ) { // Le champ periode doit etre superieur a 0
							alert('<?php echo __T('A_JS_ALARM_NUMERIC_NEGATIVE'); ?>');
							//$('period_'+critical[j]).value = '';
							$('period_'+critical[j]).focus();
							return false;
						}
						else if ( parseInt(nbi) > parseInt(p) ) {
							alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_GREATER_THAN_PERIODE'); ?>');
							//$('period_'+critical[j]).value = '';
							$('nb_iteration_'+critical[j]).focus();
							return false;
						}
						else if ( parseInt(nbi) > parseInt(p) ) {
							alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_GREATER_THAN_PERIODE'); ?>');
							$('nb_iteration_'+critical[j]).focus();
							return false;
						}
						if ( p != '' ) { // si le champ periode est rempli, il doit etre inferieur ou egale a la valeur de l'historique de la TA 
							if ( parseInt(p) > MAX_PERIOD[__time_to_sel] ) {
								var msg = "<?php echo __T('A_JS_ALARM_MAX_PERIOD_EXCEEDED', 'XXX'); ?>";
								msg = msg.replace(/XXX/, MAX_PERIOD[__time_to_sel]+' '+TA_LABEL[__time_to_sel]);
								alert(msg);
								$('period_'+critical[j]).focus();
								return false;
							}
						}
					}
					// <<<<<<<<<<
                }
                if (temoin==2) return true;
			}
			if (temoin==1) {
				alert('No value configured for the threshold');
				document.getElementById('threshold_value_critical').focus();
			}
			if (temoin==0) {
				alert('Please, select a threshold');
				document.getElementById('threshold_type').focus();
			}
		}
		// maj 24/10/2006 xavier
		if (message) alert(message);
		return false;
	}

    // on initialise l'affichage a gauche des differents niveaux de criticite.
    changerLabel('threshold');
    changerLabel('trigger');

    window.focus();
    </script>
      <?
  }

}//fin class
?>
