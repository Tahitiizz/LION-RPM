<?
/*
 * @cb50401
 *
 *  06/09/2010 NSE DE Firefox bz 16865 : Drag & Drop Ko
 */
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 16/04/2008 Benjamin : création de la fonction checkRangeConflicts qui vérifie que les range n'entrent pas en conflit. BZ6253
*	- maj 16/04/2008 Benjamin : Ajout d'un contrôle sur la suppression d'un niveau de couleur + ajout message de confirmation en BDD (A_PAUTO_DATA_RANGE_BUILDER_CONFIRM_DELETE_RANGE) BZ6234
 *      - maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
 */
?>
<?
/*
*	@cb30000@
*
*	23/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.00
*
*	- maj 07/08/2007 Jérémy : 	Ajout d'une condition pour afficher l'icone de retour au choix des familles
*						Si le nombre de famille est supérieur à 1 on affiche l'icône, sinon, on la cache
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
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
	class pageframe
	- maj 23/05/2006, christophe : les options ne sont pas affichées tant qu'aucune donnés n'a été drag and       droppée. Le bouton save est affiché en haut. Ajout d'une valeur par défaut pour le fill transparence.
	- maj 15/06/2006, matthieu : Prise en compte des ADV KPI (lignes 560 a 565 et 586 a 592)
	- maj 11/10/2006, christophe : message d'erreur si les data ne sont pas conformes (sup < inf ...)
	- maj 30/10/2006, xavier : remplacement du nom des raw counters et KPIs par leur label
	- maj 28/02/2007, benoit : remplacement de "Fill transparency" par "transparency"
	- maj 28/02/2007, benoit : affichage des valeurs de transparence sous forme de pourcentage

*/

class pageframe {

	// Constructeur.
	// $affichage : permet de spécifier si on ajoute un nouvel élément ou si on l'affiche.
	function pageframe($id_element,$type = '') {
		global $niveau0, $product, $family;
		$this->id_element				= $id_element;
		$this->type					= $type;
		$this->product					= $product;
		$this->family					= $family;
		$this->fill_transparence_default	= 0.3;	// valeur par défaut de la transparence.
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->db					= Database::getConnection($this->product);
		$this->niveau0					= $niveau0;
		// echo "<pre>";print_r($this);echo "</pre>";
		$this->display_frame();
	}


	function get_max_colonne()	{ $this->position_max_colonne	= 1;	}

	function get_max_ligne()		{ $this->position_max_ligne	= 1;	}

	/**
	 *	Cette fonction affiche UN range
	 *	@param int $p c'est le numéro du range  (=1 pour le premier range affiché etc ...)
	 *	@param array $range c'est le tableau associatif contenant toutes les informations du range
	 *	@return void cette fonction ne retourne rien à proprement parler, elle ne fait que des echos
	 */
	function display_range($p,$range) {

		// options de transparence (pour l'affichage du range)
		$options = '';
		for ($i=0;$i <= 10; $i++) {
			$selected = '';
			if ((10*$i) == ($range["filled_transparence"] * 100))
				$selected = " selected='selected' ";

			$options .= "\n	<option value='". ((10*$i)/100) ."' $selected>". (10*$i) ."%</option>";
		}

		// on affiche un range
                // maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
		echo "
		<div id='range$p'>
			<fieldset>
			<legend class='{$range['class_legend_fieldset']}'>&nbsp;<img src='{$this->niveau0}images/icones/small_puce_fieldset.gif'/>&nbsp;&nbsp;<b>Range {$p}{$range['msg']}</b>&nbsp;</legend>

				<table cellpadding='0' cellspacing='2' align='left'>
				<tr>
					<td class='texteGris'>
						Stroke color :
						<input type='button' name='stroke_color_btn$p' value='' size='16' style='background-color:{$range['color']};'
							class='hexfield' onMouseOver=\"style.cursor='pointer';\"
							onclick=\"javascript:ouvrir_fenetre('{$this->niveau0}php/palette_couleurs_2.php?form_name=myForm&field_name=stroke_color_btn$p&hidden_field_name=stroke_color$p','Palette','no','no',304,100);\" />
						<input type='hidden' name='stroke_color$p' value='{$range['color']}'/>
					</td>
					<td>&nbsp;</td>
					<td class='texteGris'>
						Fill color :
						<input type='button' name='fill_color_btn$p' value='' size='16' style='background-color:{$range['filled_color']};'
							class='hexfield' onMouseOver=\"style.cursor='pointer';\"
							onclick=\"javascript:ouvrir_fenetre('{$this->niveau0}php/palette_couleurs_2.php?form_name=myForm&field_name=fill_color_btn$p&hidden_field_name=fill_color$p','Palette','no','no',304,100);\" />
							<input type='hidden' name='fill_color$p' value='{$range['filled_color']}'>
					</td>
					<td>&nbsp;</td>
					<td class='texteGrisPetit'>
						transparency :
						<select style='width=60px;' name='filled_transparence$p'>
							$options
						</select>
					</td>
				</tr>
				<tr>
					<td class='texteGris'>
						Min range :
						<input type='text' name='min_range$p' id='min_range$p' value='{$range['range_inf']}' style='width:40px;font-size:10px;'/>
					</td>
					<td>&nbsp;</td>
					<td class='texteGris'>
						Max range :
						<input type='text' name='max_range$p' id='max_range$p' value='{$range['range_sup']}' style='width:40px;font-size:10px;'/>
					</td>
					<td>&nbsp;</td>";
		if (!$range['cannot_delete']) {
			echo "	<td align='right'>
						<!-- maj 16/04/2008 Benjamin : Ajout d'un contrôle sur la suppression d'un niveau de couleur + ajout message de confirmation en BDD -->
						<a	href='delete_range.php?product=$this->product&family=$this->family&id_element=$this->id_element&type=$this->type&order={$range['range_order']}'
							onclick=\"return confirm(' ". __T('A_PAUTO_DATA_RANGE_BUILDER_CONFIRM_DELETE_RANGE') ." ');\">
							<img src='{$this->niveau0}images/icones/drop.gif' border='0' alt='Delete this range' />
						</a>
					</td> ";
		}
		echo "
					</tr>
			</table>

		</fieldset>
		</div> ";
	}

	function display_frame() { ?>
		<table cellpadding="2" cellspacing="2" class="tabFramePauto" width="500px" border="0">
		<div class="tree">
			<tr>
				<!-- Affichage de l'image titre de la page -->
				<td align="center" height="60px">
					<img src="<?=$this->niveau0?>images/titres/data_range_titre.gif"/>
				</td>
			</tr>
			<!-- Affichage du tableau  dynamique permettant l'insertion d'éléments par drag and drop -->
			<tr>
				<td align='center'>
					<fieldset>
						<legend class="texteGrisBold">	Your customized kpi or raw &nbsp;&nbsp;	</legend>
						<table cellspacing="2" cellpadding="0" border="0">
							<tr>
								<td align="right" style="padding:4px;">
									<?$this->display_customized_contenu();?>
								</td>
								<td align="left">
									<? 	// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
										// 10/02/2010 NSE : suppression de style.cursor='help'; pour le changement de famille.
										if (get_number_of_family(false,$this->product) > 1) { ?>
											<a href="pauto_index_range.php?product=<?=$this->product?>" target="_top">
												<img src="<?=$this->niveau0?>images/icones/change.gif" onMouseOver="popalt('Change family');" onMouseOut='kill()' border="0" />
											</a>
									<? 	} ?>
								</td>
							</tr>
						</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td>
					<!-- Rajout balise table pour forcer la taille du fieldset -->
					<table width="100%" border="0">
						<? if ($this->id_element && $this->type) { ?>
							<tr>
							<td>
							<fieldset>
								<legend><font class="texteGrisBold"> Options &nbsp;</font></legend>
								<table cellspacing="0" cellpadding="0" width="100%">
									<tr>
									<td>

											<?

											// valeurs par defaut :
											$nbRange			= 1;
											$type_element		= $this->type;
											$range_sup		= 100;
											$range_inf		= 0;
											$filled_color		= "#FFFFFF";
											$color			= "#000000";
											$filled_transparence	= 1;

											// Si il existe un enregistrement du kpi ou raw, on afficha la configuration enregistrée.
											if ($this->id_element && $this->type) {
												$query = "
													SELECT *
													FROM sys_data_range_style
													WHERE id_element='$this->id_element'
														AND data_type='$this->type'
														AND family='$this->family' ";
												$result = $this->db->getall($query);
												if ($result) {
													$nbRange			= count($result);
													$result_array		= $result[0];
													$type_element		= $result_array["data_type"];
													$range_sup		= $result_array["range_sup"];
													$range_inf		= $result_array["range_inf"];
													$filled_color		= $result_array["filled_color"];
													$color			= $result_array["color"];
													$filled_transparence	= $result_array["filled_transparence"];
												}
											}

											?>


											<form id="data_range_form" method="post" action="pauto_range_save.php?product=<?=$this->product?>&family=<?=$this->family?>&id_element=<?=$this->id_element?>&type=<?=$this->type?>" name="myForm" onsubmit="return checkRangeConflicts()">
												<table cellpadding="2" cellspacing="2" width="100%" align="left" border="0">
												<tr align="center">
													<td class="texteGrisBold" valign="middle" align="left">
														Add a range &nbsp;
														<img src="<?=$this->niveau0?>images/icones/petit_plus.gif" onClick="ajouterRange(<? echo($nbRange) ?>)">
													</td>
													<td align="right">
														<input type="submit" class="bouton" value="Save"/>
													</td>
												</tr>
												<tr>
												<td colspan="2">



														<?
														// Affichage du message d'erreur.
														if (isset($_GET["range_error"]))
															echo "<div class='texteRouge'>".$_GET["range_error"]."</div>";

														// Affichage de la liste des ranges
														if ($this->id_element && $this->type) {
															$query = "
																SELECT *
																FROM sys_data_range_style
																WHERE id_element='$this->id_element'
																	AND data_type='$this->type'
																	AND family='$this->family'
																ORDER BY range_order ASC ";
															$ranges = $this->db->getall($query);
															if ($ranges) {
																$p = 1;

																foreach ($ranges as $range) {

																	$range['class_legend_fieldset'] = 'texteGris';
																	$range['msg'] = '';

																	if ($_GET['range_id'] == $p) {
																		$range['class_legend_fieldset'] = 'texteRouge';
																		if (isset($_GET['num'])) {
																			$range['msg'] = ' [invalid numeric number] ';
																		} else {
																			$range['msg'] = ' [invalid min / max] ';
																		}
																	}

																	$this->display_range($p,$range);
																	$p++;
																}
															} else {
																$range = array(
																	'range_sup'		=> '',
																	'range_inf'		=> '',
																	'filled_color'		=> '#FFFFFF',
																	'color'			=> '#000000',
																	'filled_transparence'	=> $this->fill_transparence_default,
																	'class_legend_fieldset'	=> 'texteGris',
																	'cannot_delete'		=> 1,
																);
																$this->display_range(1,$range);
															}
														} else {
															$range = array(
																'range_sup'		=> '',
																'range_inf'		=> '',
																'filled_color'		=> '#FFFFFF',
																'color'			=> '#000000',
																'filled_transparence'	=> $this->fill_transparence_default,
																'class_legend_fieldset'	=> 'texteGris',
																'cannot_delete'		=> 1,
															);
															$this->display_range(1,$range);
														}
														?>


																</td>
															</tr>
														</table>
														</form>

										</td>
									</tr>
									<tr>
										<td colspan="4" class="texteGris" style='padding:4px;'>
											<img src="<?=$this->niveau0?>images/icones/icone_astuce.gif" align='absmiddle'/>
											Drag and drop this icon <img src="<?=$this->niveau0?>images/pauto/triangle_orange.gif"/> from the treeview.
										</td>
									</tr>
								</table>
							</fieldset>
							</td>
							</tr>
						<? } else { ?>
							<tr align="center">
								<td class="texteRouge">Drag and drop one data.</td>
							</tr>
						<? } ?>
						</table>
					</td>
				</tr>
			</div>
			</table>
	<?
	// echo $this->db->displayQueries();
	}



        // 25/11/2010 BBX
        // Correction du comportement de la zone de drag and drop
        // BZ 19073
	// Affiche le tableau dynamique.
	function display_customized_contenu()
        {
            // Valeurs par défaut
            $nom_element    = '';
            $contenu        = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $width          = '2';

            // On vérifie si le kpi ou raw courrant a été configuré.
            if ($this->id_element && $this->type)
            {
                switch ($this->type)
                {
				case 'kpi' :
					$query = "
						SELECT CASE WHEN kpi_label is not null THEN kpi_label ELSE kpi_name END AS kpi_name
						FROM sys_definition_kpi
						WHERE id_ligne= '$this->id_element' ";
					break;
				case 'raw' :
					$query = "
						SELECT CASE WHEN edw_field_name_label is not null THEN edw_field_name_label ELSE edw_field_name END AS edw_field_name
						FROM sys_field_reference
						WHERE id_ligne= '$this->id_element' ";
					break;
				case 'adv_kpi':
					$query = "
						SELECT CASE WHEN adv_kpi_label is not null THEN adv_kpi_label ELSE adv_kpi_name END AS adv_kpi_name
						FROM sys_definition_adv_kpi
						WHERE adv_kpi_id= '$this->id_element' ";
					break;
			}
                $nom_element    = $this->db->getone($query);
                $width          = strlen($nom_element)*1.2;
                $contenu	= "<small><img id='0:0' src='{$this->niveau0}images/pauto/Method.png' /></small>";
		}

                // 06/09/2010 NSE DE Firefox bz 16865 : remplacement de la div par un input qui supporte onDrop sous FF et Chrome + event en paramètre
            // 27/01/2011 BBX
            // Pour que le dragndrop fontionne avec firefox, le champ ne doit pas être readonly.
            // On ajoute alors onkeypress=\"return false;\" afin de ne pas pouvoir modifier le champ à la main
            // BZ 19073
		echo "$contenu <input type=\"text\"
				 onDragEnter=\"style.cursor='pointer';cancelDefault(event)\"
				 onDragOver='cancelDefault(event)'
                                 onDrop=\"handleDrop(event,'$this->family','$this->product')\"
                             style='margin-right:4px;border: 1px dotted #545454;padding:2px;'
                             value='$nom_element'
                             size='$width'
                             onkeypress=\"return false;\" />";
	}
}

?>
