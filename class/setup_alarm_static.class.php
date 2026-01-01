<?
/*
 *	@cb50400@
 *
 *  12/08/2010 NSE bz DE Firefox 16851 : les lignes <!----------> ne sont pas supportées par Firefox, remplacement par <!-- ___ -->
 *  13/08/2010 DE Firefox bz 16905 : problèmes de présentation
 *  13/08/2010 DE Firefox bz 16907 : add Trigger + affichage trigger qd label trop long
 * 13/09/2010 NSE bz 17813 : simulation du clic KO sous FF
 */
?><?
/*
*	@cb41000@
*
*	- maj 14/10/2008 SLC : corrections d'affichage suite à ajout du DOCTYPE
*	10/04/2009 - SPS : test si existence des elements trigger_label_*
*	20/04/2009 - ajout SPS : ajout de l'id trigger_label_* pour l'affichage des seuils des triggers (critical, etc) sous ie8
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
*	- maj 16:40 07/12/2007 Gwénaël :  ajout d'un message d'erreur si la période est supérieur à l'historique
*	- maj 08:24 16/11/2007 Gwénaël : ajout des champs nombre d'itération et période

	- maj 11/03/2008, benoit : correction du bug 3819
	- maj 12/03/2008, benoit : correction du bug 3819

	- maj 28/03/2008, gwénaël : ajout d'une condition pour que le nombre d'itération et la période soient supérieur à 1
	- maj 11/06/2008, maxime : correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticité
	- maj 17/06/2008, benoit : correction du bug 6668

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

	- maj 03/10/2006, xavier :	modification de la fonction javascript check_form.
								résolution du bug sur le nom d'alarme.
								affichage d'un message d'erreur lorsqu'un trigger prend une valeur non numérique.

	- maj 11/10/2006, xavier : résolution du bug qui effaçait l'alarme lorsque l'on sauvegardait avec tous les       triggers vide.

	- maj 24/10/2006, xavier : modification de l'affichage des messages d'erreurs

	- maj 25/10/2006, xavier : message d'erreur si que opérande, que valeur, ou valeur incorrecte pour un trigger.

	- maj 06/11/2006, benoit : limitation de la taille du label raw/kpi à 40 caractères et vérification de la     valeur du champ (label non nul)

	- maj 27/02/2007, benoit : reduction de la limitation du label à 52 caractères.

	- maj 27/02/2007, benoit : ajout d'un parametre à la fonction 'getFieldValue()' indiquant le nombre de        caracteres autorisé dans les selects.

*/

include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");

Class alarm_static {
	var $univers;

	function alarm_static($family,$alarm_id,$alarm_type,$table_alarm,$allow_modif, $max_period, $ta, $product='') {

		global $debug;
		$this->debug = $debug;
		$this->debug = false;
		if ($this->debug)
			echo "<div class='debug'>new alarm_static(family=<strong>$family</strong>, alarm_id=<strong>$alarm_id</strong>, alarm_type=<strong>$alarm_type</strong>, table_alarm=<strong>$table_alarm</strong>, allow_modif=<strong>$allow_modif</strong>, max_period=<strong>$max_period</strong>, ta=<strong>$ta</strong>, product=<strong>$product</strong>);</div>";

		// connexion à la base de donnée
		$this->db = Database::getConnection($product);

		// nombre maximum de triggers
		$this->nb_max_trigger = 5;

		// récupération des droits utilisateurs
		// un administrateur en mode client ne peut pas modifier une alarme en mode customisateur
		$this->allow_modif = $allow_modif;

		// récupération des données
		$this->alarm_type	= $alarm_type;
		$this->alarm_id		= $alarm_id;
		$this->product		= $product;
		$this->family		= $family;
		$this->table_alarm	= $table_alarm;
                
                // 27/10/2011 BBX
                // BZ 24439 : correction de la récupération des triggers 
                $this->alarm_triggers   = array();
                $this->trigger_selected = array();

		// $this->nb_trigger prend le nombre de triggers de l'alarme.
		// Si l'alarme n'existe pas, $this->nb_trigger est initialisé à 1.
		// $this->trigger_selected[ordre][type] où :
		//      ordre est le numéro du trigger (compris entre 1 et $this->nb_max_trigger)
		//      type est à "0" --> type du trigger (kpi ou raw)
		//      type est à "1" --> nom du trigger
		$this->nb_trigger=0;
		$result = false;

		if ($this->alarm_id) {
			// on récupère le nombre maximum de seuil par criticité pour chaque raw ou kpi.
			// Ce résultat correspond au nombre d'occurence du raw ou kpi dans la liste des triggers.
                        // 12/04/2011 BBX
                        // Ajout des champs alarm_trigger_value et alarm_trigger_operand pour distinguer un trigger
                        // BZ 21540
                        
                        // 27/10/2011 BBX
                        // BZ 24439 : correction de la récupération des triggers    
                        $query = "
                        SELECT 
                                alarm_trigger_type, 
                                alarm_trigger_data_field, 
                                alarm_trigger_value,
                                alarm_trigger_operand, 
                                critical_level
                        FROM sys_definition_alarm_static
                        WHERE alarm_id = '{$this->alarm_id}'
                        ORDER BY alarm_trigger_type, alarm_trigger_data_field, critical_level";
                        $res = $this->db->execute($query);
                        while($row = $this->db->getQueryResults($res,1)) {
                            $this->alarm_triggers[$row['critical_level']][] = array(
                                'alarm_trigger_type' => $row['alarm_trigger_type'], 
                                'alarm_trigger_data_field' => $row['alarm_trigger_data_field'],
                                'alarm_trigger_value' => $row['alarm_trigger_value'],
                                'alarm_trigger_operand' => $row['alarm_trigger_operand']);
                        }
                        
                        $query = "
                        SELECT DISTINCT ON (alarm_trigger_type, alarm_trigger_data_field)
                                count(critical_level) AS nbocc,
                                alarm_trigger_type, 
                                alarm_trigger_data_field
                        FROM sys_definition_alarm_static
                        WHERE alarm_id = '{$this->alarm_id}'
                        GROUP BY critical_level,alarm_trigger_type, alarm_trigger_data_field
                        ORDER BY alarm_trigger_type, alarm_trigger_data_field";
                        $res = $this->db->execute($query);
                        while($row = $this->db->getQueryResults($res,1)) {
                            for($i = 1; $i <= $row['nbocc']; $i++) {
                                $this->trigger_selected[] = array(
                                    'alarm_trigger_type' => $row['alarm_trigger_type'],
                                    'alarm_trigger_data_field' => $row['alarm_trigger_data_field']);
                                $this->nb_trigger++;
                            }
                        }
		}

                if($this->nb_trigger == 0)
                    $this->nb_trigger = 1;

		// création de la liste des kpi et des raw counters
		$this->kpi	= get_kpi($this->family,$this->product);
		$this->counter	= get_counter($this->family,$this->product);

		// modif 08:25 16/11/2007 Gwen
		// Ajout des champs nb_iteration & period
		$this->max_period = $max_period;
		// modif 24/12/2007 Gwen
		// Ajout du paramètre TA
		$this->ta = $ta;
		// début de l'affichage de la partie spécifique à static alarm
		$this->display();
	}

	// on récupère les informations concernant les triggers de la static alarm.
	// ces informations sont affichées dans des champs non modifiables si $this->allow_modif est à faux.
	// sinon, les informations sont modifiables et une icone permet de vider tous les champs dépendants
	// du trigger correspondant. Un bouton permet d'ajouter un nouveau trigger dans la limite de '$this->nb_max_trigger'
	function display_info_global_bloc() {
		global $niveau0;
		if ($this->debug) echo "<div class='debug'>\$alarm_static->display_info_global_bloc();</div>";

		if ($this->allow_modif) { ?>
			<table width="100%">
				<tr>
					<td align=right>
						<input type="button" class="bouton" value="<?echo __T('A_ALARM_FORM_BTN_ADD_TRIGGER')?>" onclick="add_trigger()">
                        <!-- 12/08/2010 NSE DE Firefox bz 16851 ajout de l'attribut id -->
						<input type="hidden" id="nb_trigger" name="nb_trigger" value="<?=$this->nb_trigger?>">
					</td>
				</tr>
			</table>
			<?
		}

		for ($j =1; $j <= $this->nb_max_trigger; $j++) {  ?>
			<div id="trigger_list<?=$j?>"	<? if ($j > $this->nb_trigger) echo ' style="display:none"'; ?>>
                            <table id="tab_trigger<?=$j?>">
				<tr>
				<?php /*<!-- _______________________________ -->*/ ?>

				<?
                                // 27/10/2011 BBX
                                // BZ 24439 : correction de la récupération des triggers
                                $triggerOffet = $j-1;
				$array_trigger_type = Array('kpi','raw');
				$array_trigger_type_label = Array('KPI','Raw Counter');

				// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
				// du type du 'trigger' dans un champ texte non modifiable.
				if (!$this->allow_modif) {
                                        // 27/10/2011 BBX
                                        // BZ 24439 : correction de la récupération des triggers
					$key = array_search($this->trigger_selected[$triggerOffet]['alarm_trigger_type'], $array_trigger_type);
					?>
					<td><input class="zoneTexteStyleXPFondGris" name="trigger_type<?=$j?>" style="width:100px" value="<?=$array_trigger_type_label[$key]?>" readonly></td>
					<?
				} else {
					// sinon, on affiche la liste des choix possibles

					// 27/02/2007 - Modif. benoit : ajout d'un parametre sur la taille max du label dans 'getFieldValue()'
					?>
					<td><select class="zoneTexteStyleXP" name="trigger_type<?=$j?>" id="trigger_type<?=$j?>" style="width:100px" onchange="getFieldValue(this.value,'<?="trigger_field".$j?>','<?=$this->product?>','<?=$this->family?>', 52);changerLabel(<?=$j?>);remove_all_critical_level(<?=$j?>)">
						<option value='makeSelection'>Type</option>
						<?
						for ($i=0;$i<count($array_trigger_type);$i++) {
							$selected="";
                                                        // 27/10/2011 BBX
                                                        // BZ 24439 : correction de la récupération des triggers
							if ($array_trigger_type[$i]==$this->trigger_selected[$triggerOffet]['alarm_trigger_type'])
								$selected="selected='selected'";
							?>
							<option value='<?=$array_trigger_type[$i]?>'  <?=$selected?>><?=$array_trigger_type_label[$i]?></option>
							<?
						}
						?>
						</select>
					</td>
				<? } ?>

				<?php /*<!-- _______________________________ -->*/ ?>

				<?				
                                $array_trigger_field = array();

                                // 27/10/2011 BBX
                                // BZ 24439 : correction de la récupération des triggers
				if ($this->trigger_selected[$triggerOffet]['alarm_trigger_type'] == "kpi")
					$array_trigger_field = $this->kpi;

				if ($this->trigger_selected[$triggerOffet]['alarm_trigger_type'] == "raw")
					$array_trigger_field = $this->counter;

				// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
				// du 'trigger' dans un champ texte non modifiable.
				if (!$this->allow_modif) {

					// 06/11/2006 - Modif. benoit : limitation du label à 40 caractères
					// 27/02/2007 - Modif. benoit : reduction de la limitation à 52 caractères
					// 12/03/2008 - Modif. benoit : ajout d'une variable contenant le label complet du trigger

                                        // 27/10/2011 BBX
                                        // BZ 24439 : correction de la récupération des triggers
					$trigger_field_complete_label = $array_trigger_field[$this->trigger_selected[$triggerOffet]['alarm_trigger_data_field']];

					if (strlen($trigger_field_complete_label) > 52) {
						$trigger_field = substr($trigger_field_complete_label, 0, 52)."...";
					} else {
						$trigger_field = $trigger_field_complete_label;
					}
                                        
                                        // 14/10/2011 BBX
                                        // BZ 23288 : ajout d'un id pour firefox et autres nav
                                        // 27/10/2011 BBX
                                        // BZ 24439 : correction de la récupération des triggers
					?>
					<td>
						<input class="zoneTexteStyleXPFondGris" name="trigger_field<?=$j?>" id="trigger_field<?=$j?>" style="width:400px" value="<?=$trigger_field?>" readonly></td>
						<input name="trigger_field_value<?=$j?>" type="hidden" value="<?=$this->trigger_selected[$triggerOffet]['alarm_trigger_data_field']?>">
					<?
				} else {
					// sinon, on affiche la liste des choix possibles
                                        // 12/08/2010 NSE De Firefox : on diminue la largeur du select de façon à faire disparaître la barre de défilement horizontale
					?>
					<td>
						<select class="zoneTexteStyleXP" name="trigger_field<?=$j?>" id="trigger_field<?=$j?>" style="width:410px" onchange="changerLabel(<?=$j?>);remove_all_critical_level(<?=$j?>);remove_choice(this)">
							<?
                                                        // 27/10/2011 BBX
                                                        // BZ 24439 : correction de la récupération des triggers
							if ($this->trigger_selected[$triggerOffet]['alarm_trigger_type'] == '') {
								?>	<option value='makeSelection'>Make a selection</option> <?
							}
							foreach($array_trigger_field as $name => $label) {

								// 06/11/2006 - Modif. benoit : limitation du label à 40 caractères
								// 27/02/2007 - Modif. benoit : reduction de la limitation à 52 caractères
								if (trim($label) == "") $label = $name;

								// 11/03/2008 - Modif. benoit : ajout de l'attribut 'label_complete' à l'option de la liste des raws / kpis

								$label_complete = $label;

								if (strlen($label) > 52) $label = substr($label, 0, 52)."...";

								$selected="";
                                                                // 27/10/2011 BBX
                                                                // BZ 24439 : correction de la récupération des triggers
								if ($name==$this->trigger_selected[$triggerOffet]['alarm_trigger_data_field']) $selected="selected='selected'";
								?>
								<option value='<?=$name?>' label_complete="<?=$label_complete?>" <?=$selected?>><?=$label?></option>
								<?
							}
							?>
						</select>
					</td>
                    <!-- 12/08/2010 NSE DE Firefox : alignement de l'image avec les menus déroulants + suppression barre défilement horizontale -->
					<td><img src='<?=$niveau0?>images/icones/drop.gif' style="cursor:pointer" onclick="remove_trigger('<?=$j?>')"></td>
					<?
				}
				?>
				<?php /*<!-- _______________________________ -->*/ ?>

			</tr>

			<?php
			// 12/03/2008 - Modif. benoit : si l'on est en mode lecture seule mais que le label du field est tronqué on crée une ligne pour afficher le label complet du field
			if ((!$this->allow_modif) && ($trigger_field != $trigger_field_complete_label)) { ?>
				<tr>
					<td></td>
					<td colspan="2" class="texteGris" style="font-size:8pt"><?=$trigger_field_complete_label?></td>
				</tr>
	<?php	} ?>
		</table>
		</div>
		<?
		}
	}


	// on récupère les informations concernant les valeurs des triggers
	// de la static alarm pour un niveau de criticité '$critical_level'.
	// ces informations sont affichées dans des champs non modifiables si $this->allow_modif est à faux.
	// sinon, les informations sont modifiables et une icone permet de vider
	// les champs des triggers pour le niveau de criticité '$critical_level'.
	function display_info_trigger($critical_level) {

		global $niveau0;
		if ($this->debug) echo "<div class='debug'>\$alarm_static->display_info_trigger($critical_level);</div>";

		$query = "
			SELECT nb_iteration, period
			FROM $this->table_alarm
			WHERE alarm_id = '$this->alarm_id'
				AND critical_level = '$critical_level'
			LIMIT 1";
		$result = $this->db->getrow($query);
		$nb_iteration	= $result['nb_iteration'];
		$period		= $result['period'];

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

		// modif 16:39 07/12/2007 Gwen
		// Ajout d'un message d'erreur si la période est supérieur à l'historique
		if ( $this->max_period < $period ) {
			echo '<div class="texteGris" style="color:red; margin: 4px 0 2px 4px">'.__T('A_E_ALARM_MAX_PERIOD_EXCEEDED').'</div>';
		}
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
		<? } else {?>
			<div>
			<table>
				<tr><td id="error_<?=$critical_level?>" class="texteGris" style="color:red" colspan="2"></td></tr>
				<tr>
					<td class="texteGris">
						<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px"><?php echo __T('A_ALARM_FORM_LABEL_NB_ITERATION'); ?> :</label>
						<input class="zoneTexteStyleXP" name="nb_iteration_<?=$critical_level?>" id="nb_iteration_<?=$critical_level?>" style="width:80px" value="<?=$nb_iteration?>">
					</td>
					<td class="texteGris">
						<label tabIndex="-1" class="zoneTexteStyleXP" value="" style="border:0px">&nbsp;<?php echo __T('A_ALARM_FORM_LABEL_PERIOD'); ?> :</label>
						<input class="zoneTexteStyleXP" name="period_<?=$critical_level?>" id="period_<?=$critical_level?>" style="width:80px" value="<?=$period?>" <? if( $this->max_period != '' ) { echo "onmouseover=\"popalt('".__T('A_TOOLTIP_ALARM_MAX_PERIOD',$max_period_display)."')\""; } ?>>
					</td>
				</tr>
			</table>
			</div>
		<? }

                $lastDisplayedOffset = -1;
		for ($j =1; $j <= $this->nb_max_trigger; $j++) 
                {
                        // 27/10/2011 BBX
                        // BZ 24439 : correction de la récupération des triggers
                        $row = array();
                        $currentTrigger = isset($this->trigger_selected[$j-1]) ? $this->trigger_selected[$j-1] : array();
                        if(isset($this->alarm_triggers[$critical_level])) {                            
                            foreach($this->alarm_triggers[$critical_level] as $offset => $triggerData) {
                                if($triggerData['alarm_trigger_type'] == $currentTrigger['alarm_trigger_type'] && $triggerData['alarm_trigger_data_field'] == $currentTrigger['alarm_trigger_data_field'] && $lastDisplayedOffset != $offset) {
                                    $row = $triggerData;
                                    $lastDisplayedOffset = $offset;
                                    break;
                                }
                            }
                        }

                        /**
                        * 20/04/2009 - ajout SPS : ajout de l'id trigger_label_* pour l'affichage des seuils des triggers (critical, etc) sous ie8
                        **/
			?>
			<div id="trigger_<?=$critical_level.$j?>"<? if ($j > $this->nb_trigger) echo ' style="display:none"'; ?>>
			<table>
				<tr><td class="texteGris" width="150"><li><input tabIndex="-1" class="zoneTexteStyleXP" id="trigger_label_<?=$critical_level.$j?>" name="trigger_label_<?=$critical_level.$j?>" style="width:325px;border:0px" readonly></li></td>

				<!-- _______________________________ -->

				<? if (!$this->allow_modif) { ?>
					<td><input class="zoneTexteStyleXPFondGris" name="trigger_operand_<?=$critical_level.$j?>" style="width:85px" value="<?=$row['alarm_trigger_operand']?>" readonly></td>
				<? } else { ?>
					<td><select class="zoneTexteStyleXP" name="trigger_operand_<?=$critical_level.$j?>" id="trigger_operand_<?=$critical_level.$j?>" style="width:85px">

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
					</select></td>
				<? } ?>

				<!-- _______________________________ -->

				<? if (!$this->allow_modif) { ?>
					<td><input class="zoneTexteStyleXPFondGris" name="trigger_value_<?=$critical_level.$j?>" style="width:80px" value="<?=$row['alarm_trigger_value']?>" readonly></td>
				<? } else {
					// maj 11/06/2008 - Maxime : Correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticité
					// Appel de la fonction js remove_iteration
					?>
					<td><input class="zoneTexteStyleXP" name="trigger_value_<?=$critical_level.$j?>" id="trigger_value_<?=$critical_level.$j?>" style="width:80px" value="<?=$row['alarm_trigger_value']?>"></td>
					<td>&nbsp;&nbsp;<img src='<?=$niveau0?>images/icones/drop.gif' style="cursor:pointer" onclick="remove_critical_level('<?=$critical_level?>','<?=$j?>');remove_iteration('<?=$critical_level?>','<?=$j?>')"></td>
				<? } ?>

				<!-- _______________________________ -->

			</tr>
			</table>
			</div>
			<?
		}
	}


	// fonction qui met en page l'affichage des triggers et de leurs valeurs respectives
	// suivant les niveaux de criticités.
	function display_bloc_info_global() {
		if ($this->debug) echo "<div class='debug'>\$alarm_static->display_bloc_info_global();</div>";
		?>
		<table width=100%>
			<tr>
				<td align=left>
				<fieldset>
					<legend class="texteGrisBold">&nbsp;<?echo __T('A_ALARM_FORM_FIELDSET_TRIGGER_LIST');?>&nbsp;&nbsp;<span class="texteGrisPetit"><?echo __T('A_ALARM_NFORMATION_TRIGGER')?></span>&nbsp;&nbsp;</legend>
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
						<legend class="texteGrisBold">&nbsp;<?=ucfirst($critical_level[$i])?>&nbsp;&nbsp;<span style="background-color:<?=get_sys_global_parameters('alarm_' . $critical_level[$i] . '_color')?>;width:10px;height:10px;">&nbsp;&nbsp;</span>&nbsp;&nbsp;</legend>
						<?$this->display_info_trigger($critical_level[$i]);?>
					</fieldset>
				</td>
				</tr>		<?
			} ?>
		</table>
		<?
	}


	// contient la liste des fonctions javascript utilisées pour valider l'envoi
	// du formulaire et pour vider les triggers.
	function display() {
		if ($this->debug) echo "<div class='debug'>\$alarm_static->display();</div>";
		?>
		<table width="550" align="center" border=0 cellpadding="0" cellspacing="0">
			<tr>
				<td><?$this->display_bloc_info_global();?></td>
			</tr>
		</table>
		<script type="text/javascript">

		var ordreAffichage = new Array();
		var nombre_max = <?=$this->nb_max_trigger?>;

		/**
		*  affiche tous les champs concernant le trigger suivant de la liste 'ordreAffichage'.
		*  on ne peut afficher plus de 'nombre_max' triggers.
		*/
		function add_trigger() {
			nombre = $('nb_trigger');
			if (nombre.value < nombre_max)
				nombre.value++;
			else
				alert("Static Alarm is limited to "+nombre_max+" triggers");
			for (i=0; i < nombre_max; i++) {
				if ($('trigger_list'+ordreAffichage[i]).style.display=='none') {
					$('trigger_list'+ordreAffichage[i]).style.display='block';
					$('trigger_critical'+ordreAffichage[i]).style.display='block';
					$('trigger_major'+ordreAffichage[i]).style.display='block';
					$('trigger_minor'+ordreAffichage[i]).style.display='block';
					break;
				}
			}
		}

    // 23/08/2010 NSE DE Firefox bz 16907 add a trigger
    /**
     * Méthode permettant d'échanger deux noeuds
     */
    function swapNodes(item1,item2) {
        // We need a clone of the node we want to swap
        var itemtmp = item1.cloneNode(1);

        // We also need the parentNodes of the items we are going to swap.
        var parent = item1.parentNode;
        var parent2 = item2.parentNode;

        // First replace the second node with the copy of the first node
        // which returns a the new node
        item2 = parent2.replaceChild(itemtmp,item2);

        //Then we need to replace the first node with the new second node
        parent.replaceChild(item2,item1);

        // And finally replace the first item with it's copy so that we
        // still use the old nodes but in the new order. This is the reason
        // we don't need to update our Behaviours since we still have
        // the same nodes.
        parent2.replaceChild(item1,itemtmp);

        // Free up some memory, we don't want unused nodes in our document.
        itemtmp = null;
    }
	/**
	*  réinitialise tous les champs concernant le trigger 'numero'
	*  le trigger est désactivé et remis en file de liste 'ordreAffichage'.
	*/
	function remove_trigger(numero) {
		var nombre	= $('nb_trigger');
		var divlist	= $('trigger_list'+numero)
		var divcritical	= $('trigger_critical'+numero)
		var divmajor	= $('trigger_major'+numero)
		var divminor	= $('trigger_minor'+numero)

		// on réinitialise tous les champs concernant le trigger 'numero'
		$('trigger_field'+numero).options[0].value='makeSelection';
		$('trigger_field'+numero).options[0].text='Make a selection';
		$('trigger_field'+numero).length=1;
		$('trigger_field'+numero).selectedIndex=0;
		$('trigger_type'+numero).selectedIndex=0;
		remove_all_critical_level(numero);

		/* maj 11/06/2008 - Maxime : Correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticité
		 					Appel de la fonction js remove_iteration()
		*/
		remove_iteration('all',nombre);

		// 11/10/2006 xavier
		if (nombre.value > 1) {

			// on prend l'élément 'numero' et le place en dernier dans la file des triggers.
			trier=false;
			for (i=0; i<nombre_max; i++) {
				// 23/08/2010 NSE DE Firefox bz 16907 : on effetue l'échange que pour des noeuds différents.
				if (ordreAffichage[i] != numero) {
                                    // 23/08/2010 NSE DE Firefox : la méthode divlist.swapNode n'est pas implémentée sous Firefox. Création et utilisation d'une méthode standard
                                    swapNodes(divlist,$('trigger_list'+ordreAffichage[i]));
                                    swapNodes(divcritical,$('trigger_critical'+ordreAffichage[i]));
                                    swapNodes(divmajor,$('trigger_major'+ordreAffichage[i]));
                                    swapNodes(divminor,$('trigger_minor'+ordreAffichage[i]));
				}
			}
			trierliste(numero);

			// on ne cache le trigger 'numero' que s'il n'est pas le seul affiché
			divlist.style.display		='none';
			divcritical.style.display	='none';
			divmajor.style.display	='none';
			divminor.style.display	='none';
			nombre.value--;
		}

		changerLabel(numero);
	}

	/**
	*  vide les champs 'operand' et 'value' de tous les niveau de criticité du trigger 'numero'
	*/
	function remove_all_critical_level(numero) {
		remove_critical_level('critical',numero);
		remove_critical_level('major',numero);
		remove_critical_level('minor',numero);
	}

	/**
	*  vide les champs 'operand' et 'value' du niveau de criticité 'critical' du trigger 'numero'
	*/
	function remove_critical_level(critical,numero) {
		$('trigger_operand_'+critical+numero).selectedIndex=0;
		$('trigger_value_'+critical+numero).value='';
	}

	/**
	* Maj 11/06/2008 - Maxime : Correction du bug 6668 - On vide les champs nb_iteration_ et period_ de chaque niveau de criticité
	*
	* Vide les informations d'une alarme itérative
	*/
	function remove_iteration(critical,numero){
		if (critical == 'all') {

			// S'il ne reste plus que une seul trigger on vide les champs nb_iteration_ et period_ de chaque niveau de criticité

			if(numero.value == 1){
				$('nb_iteration_critical').value = '';
				$('period_critical').value = '';

				$('nb_iteration_major').value = '';
				$('period_major').value = '';

				$('nb_iteration_minor').value = '';
				$('period_minor').value = '';
			}

		} else {

			var trouve = false;
			var i = 0;

		/*
		* On boucle sur le nombre maximum de triggers
		**/

		while (i < 5 && !trouve) {
				i++;
				if( typeof( $('trigger_value_'+critical+i) ) !== 'undefined'){

					// Si un trigger est détecté on ressort de la boucle
					if( $('trigger_value_'+critical+i).value !== null && $('trigger_value_'+critical+i).value !== ''){
						trouve = true;
					}
				}
			}

			if( !trouve ){
				// alert('remove all');
				$('nb_iteration_'+critical).value = '';
				$('period_'+critical).value = '';
			}

		}
	}

	/**
	*  prend l'élément 'valeur' et le met en fin de la liste 'ordreAffichage'
	*/
	function trierliste (valeur) {
		var temp = new Array();
		for (i=0; i<5; i++) {
			elementCourant = ordreAffichage.pop();
			if (elementCourant != valeur) temp.push(elementCourant);
		}
		temp.reverse();
		temp.push(valeur);
		ordreAffichage = temp;
	}

	/**
	*  récupère le label du trigger pour l'afficher a gauche pour chaque niveau de criticité.
	*  si pas de raw ou kpi choisi, on affiche 'No trigger selected'.
	*/
	function changerLabel(numero) {
		<?php
		if ($this->allow_modif) {

			?>
			// 27/02/2007 - Modif. benoit
			// 11/03/2008 - Modif. benoit : ajout du traitement permettant d'afficher le label complet d'un field lorsque celui-ci est tronqué

			// On supprime l'ancienne ligne d'information sur le label (si elle existe)
                        // 13/08/2010 NSE DE Firefox : utilisation de tab_trigger pour supprimer la ligne du tableau créée
			if ($('trigger_complete_label'+numero) != null)
                            $('tab_trigger'+numero).deleteRow($('trigger_complete_label'+numero).rowIndex);

			// on récupère le label depuis une liste type 'select'
			if ($('trigger_field'+numero) != null) {
				var triggerLabel = $('trigger_field'+numero).options[$('trigger_field'+numero).selectedIndex].text;

				// Si la taille du label est supérieure a 40 caractères, on tronque le label et l'on ajoute l'information sous forme de ligne de tableau en dessous du select

				if (triggerLabel.length > 40) {
                                    // 13/08/2010 NSE DE Firefox : utilisation de tab_trigger pour ajouter une nouvelle ligne au tableau
                                    var newRow = $('tab_trigger'+numero).insertRow(-1);
					newRow.id = "trigger_complete_label"+numero;

					var newCell = newRow.insertCell(0);
					newCell.colSpan = "2";
					newCell.className = "texteGris";
					newCell.style.fontSize = "8pt";

					// Ajout du label complet dans la nouvelle colonne
                                        // 14/09/2010 BBX
                                        // Correction de la lecture du label complet
                                        // BZ 17889
                                        var triggerLabelComplet = $('trigger_field'+numero).options[$('trigger_field'+numero).selectedIndex].label_complete;
					if(typeof(triggerLabelComplet) == 'undefined')
                                            triggerLabelComplet = $('trigger_field'+numero).options[$('trigger_field'+numero).selectedIndex].readAttribute('label_complete');

					var newText = document.createTextNode(triggerLabelComplet);
					newCell.appendChild(newText);
				}
				<?php
				/* 10/04/2009 - SPS : test si existence des elements trigger_label_*
				* 20/04/2009 - SPS : $('trigger_label_critical'+numero) --> ie6 va chercher les champs avec l'id ou le name correspondant a la chaine
				*														--> ie8 va SEULEMENT chercher les champs avec l'id correspondant
				*/
    			?>
				if (($('trigger_field'+numero).value == 'makeSelection') || ($('trigger_field'+numero).value == '')) {
					triggerLabel = 'No trigger selected';
				}
				if ($('trigger_label_critical'+numero)) $('trigger_label_critical'+numero).value = triggerLabel;
    			if ($('trigger_label_major'+numero)) $('trigger_label_major'+numero).value = triggerLabel;
    			if ($('trigger_label_minor'+numero)) $('trigger_label_minor'+numero).value = triggerLabel;
			}
    			<?php
  		} else {
  			// on récupère le label depuis un champ texte
  			?>
			if ($('trigger_field'+numero) != null) {
				var triggerLabel = $('trigger_field'+numero).value;
				if ((triggerLabel == 'makeSelection')||(triggerLabel == '')) triggerLabel = 'No trigger selected';
				if ($('trigger_label_critical'+numero)) $('trigger_label_critical'+numero).value = triggerLabel;
				if ($('trigger_label_major'+numero)) $('trigger_label_major'+numero).value = triggerLabel;
				if ($('trigger_label_minor'+numero)) $('trigger_label_minor'+numero).value = triggerLabel;
  			}
  		<?php
		}
	?>
	}

	/**
	*  verifie que les champs obligatoires ont bien ete remplis.
	*  les champs facultatifs partiellement remplis seront ignores.
	*/
	function check_form () {

		// La fonction check_field_format presente dans setup_alarm.class.php
		// verifie que les champs communs aux alarmes sont biens remplis.
		var message = check_field_format($('alarm_name'));
		if (message == false) {

			// on retourne sur le premier onglet pour verification du trigger
                        // 13/09/2010 NSE bz 17813 : simulation du clic KO sous FF $('tabTabalarm_tab_view_0').click();
                        // on utilise la fonction tabClick
                        var idArray = $('tabTabalarm_tab_view_0').id.split('_');
                        showTab($('tabTabalarm_tab_view_0').parentNode.parentNode.id,idArray[idArray.length-1].replace(/[^0-9]/gi,''));

			var temoin = 0; // aucun counter ou kpi selectionne
			var suivant = 0; // s'incremente de 1 si un raw ou kpi est selectionne

			// on parcourt la liste des triggers
			for (k=0; k<ordreAffichage.length; k++) {

				var trigger_field = $('trigger_field'+ordreAffichage[suivant]);

				// si aucun raw ou kpi defini sur la premiere ligne, on la supprime
				if (trigger_field.value == 'makeSelection') {
					// 17/06/2008 - Modif. benoit : correction du bug 6668. La suppression des triggers ici ne sert a rien et provoque la suppression systematique des iterations et des periodes suite a la modification faite par MPR
					//remove_trigger(ordreAffichage[suivant]);
				} else {

					if (!suivant) temoin=1; // au moins un raw ou kpi selectionne mais aucun trigger defini
					critical = new Array('critical','major','minor')

					// on parcourt la liste de definition du trigger
					for (j=0;j<critical.length;j++) {

						// maj 03 10 2006 xavier
						trigger_value = $('trigger_value_'+critical[j]+ordreAffichage[suivant]);
						trigger_operand = $('trigger_operand_'+critical[j]+ordreAffichage[suivant]);

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
								temoin=2; // au moins un trigger defini
							} else {
								if (trigger_operand.value != 'none') {
									alert('you must enter a numeric value for this trigger');
									trigger_value.focus();
									return false;
								}
							}
						}

						// >>>>>>>>>>
						// modif 09:20 16/11/2007 Gwen
							// Ajout d'un check pour savoir si c'est des valeurs numeriques et que si l'un des champs est renseigne l'autre doit l'etre aussi
							// Ajout d'un check sur la periode pour verifier quelle ne depasse pas la valeur maximal
						if (temoin==2) {
							var p = $('period_'+critical[j]).value;
							var nbi = $('nb_iteration_'+critical[j]).value;

							if  ( p == '' && nbi == '' )
								continue;

							if ( (p != '' && nbi == '') || nbi != parseInt(nbi) ) {
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
							else if ( (p == '' && nbi != '') || p != parseInt(p) ) {
								alert('<?php echo __T('A_JS_ALARM_MAX_PERIOD_EMPTY'); ?>');
								//$('period_'+critical[j]).value = '';
								$('period_'+critical[j]).focus();
								return false;
							}
							else if ( parseInt(nbi) <= 0 ) {
								alert('<?php echo __T('A_JS_ALARM_NUMERIC_NEGATIVE'); ?>');
								//$('nb_iteration_'+critical[j]).value = '';
								$('nb_iteration_'+critical[j]).focus();
								return false;
							}
							else if ( parseInt(p) <= 0 ) {
								alert('<?php echo __T('A_JS_ALARM_NUMERIC_NEGATIVE'); ?>');
								//$('period_'+critical[j]).value = '';
								$('period_'+critical[j]).focus();
								return false;
							}
							else if ( parseInt(nbi) > parseInt(p) ) {
								alert('<?php echo __T('A_JS_ALARM_NB_ITERATION_GREATER_THAN_PERIODE'); ?>');
								$('nb_iteration_'+critical[j]).focus();
								return false;
							}

							if ( p != '' ) {
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


					suivant++;
				}
			}
			if (temoin==2) return true;

			if (temoin==1) {
				alert('No value or operand configured for the Trigger');
				$('trigger_operand_critical'+ordreAffichage[0]).focus();
			}
			if (temoin==0) {
				alert('Please, select a trigger');
				$('trigger_type'+ordreAffichage[0]).focus();
			}
		}

		// maj 24/10/2006 xavier
		if (message) alert(message);
		return false;
	}

	// on initialise l'ordre d'affichage et l'affichage a gauche des differents niveaux de criticite.
	for (i=1; i<=nombre_max; i++) {
		ordreAffichage.push(i);
		changerLabel(i);
	}

	window.focus();

</script>

<?
	}

} //fin class

?>
