<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
*       - 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
* 
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
<?php
	/*
		- maj 09 05 2006 christophe : ajout d'un champ caché pour passer l'id de la query courrante à la fenêtre d'affichage du graph, ligne 18
		- maj 17 05 2006 christophe : coorection du bug : lorsque l'on cliquait sur le nom d'une query depuis le 'My queries' le nom de la query n'était pas touvé. ajout ligne 15 à 17
                     - maj 01/03/2007 Gwénaël : modification de l'affichage, si champ de type TA ou NA on affiche seulement la possibilité de les afficher sur 'X axis' pas de possibilité de les affichés sur le graphe
                                                                        et pour les champs de type raw/kpi seulement la possibilité de les représenter sur le graphe
	*/
// 0 étant le numero qui designe le tableau produit par le builder report
// Ce tableau n'étant pas stocké dans la base de donnée, le fichier table_generation.php nécessite pour l'export excel l'identifiant du tableau.
// On l'a donc fixé à 0 pour ne pas interférer avec d'autres identifiants
if ($builder_report->nombre_resultat_builder_report>0) {

	$liste_champ = $builder_report->liste_champ;
	$tableau_donnee_entete = $tableau_legend_export_excel[0];
	$compteur_nombre_donnees_graphe = count($tableau_donnee_entete);
	?>

	<form name="formulaire" method="post" onsubmit="return ouvrir_fenetre('','graph_result','no','no','900','460')" target="graph_result" action="builder_report_graph_result_display.php?id_query=<?=$id_query?>&product=<?=$product?>&display=1&nombre_donnees_graphe=<?=$compteur_nombre_donnees_graphe?>">
	<?php
		$id_current_query = ($id_query2 == "") ? $id_query : $id_query2; //echo $id_current_query. " id_query2=$id_query2, id_query=$id_query";
	?>
		<input type="hidden" name="id_query" id="id_query" value="<?=$id_current_query?>"/>
		<table width="100%" border="0" cellspacing="5" cellpadding="5">
			<tr align="top">
				<td colspan="5">
					<table width="100%" border="0" cellspacing="1" cellpadding="0">
						<tr align="center" valign="middle">
							<td width="15%"><font class="texteGrisBold">Data Name</font></td>
							<td width="15%"><font class="texteGrisBold">X axis</font></td>
							<td width="40%"><font class="texteGrisBold">Display Type</font></td>
							<td width="8%" align="right"><font class="texteGrisBold">Color</font></td>
							<td width="22%"><font class="texteGrisBold">Position</font></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="center" valign="top" width="26%">
					<table width="100%" border="0" cellspacing="1" cellpadding="0">
						<?php
							$i = 0;
							foreach ($tableau_donnee_entete as $donnee_entete) { ?>

								<tr>
									<td align="center">
										<input name="entete<?=$i?>" type="text" class="iform" value="<?=$donnee_entete?>" size="28" style="width:120px;">
									</td>
								</tr>

								<?php
								$i++;
							}
						?>
					</table>
				</td>
				<td align="center" valign="top" width="10%">
					<table width="100%" border="0" cellspacing="1" cellpadding="2">
						<?php
							$i = 0;
							foreach ($tableau_donnee_entete as $donnee_entete) {
								if (in_array($donnee_entete, $liste_champ['time']) || in_array($donnee_entete, $liste_champ['network'])) {
									echo "<tr align='center' valign='top'><td><input type='radio' name='abscisse' value='$i'></td></tr>";
								} else {
									echo '<tr><td>&nbsp;</td></tr>';
								}
								$i++;
							}
						?>
					</table>
				</td>
				<td align="center" valign="top" width="30%">
					<table width="100%" border="0" cellspacing="1" cellpadding="2">
						<?php
							$i = 0;
							foreach ($tableau_donnee_entete as $donnee_entete) {
								if (in_array($donnee_entete, $liste_champ['time']) || in_array($donnee_entete, $liste_champ['network'])) {	?>

									<tr align="center">
										<input type="hidden" name="graphe_type<?=$i?>" value="no">
										<td nowrap>&nbsp;</td><td>&nbsp;</td> <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
									</tr>

								<?php } else { ?>

									<tr align="center">
										<td><input type="radio" name="graphe_type<?=$i?>" value="no" checked></td>
										<td nowrap><font class='texteGris'>No Display</font></td>					
																									
										<td><input type="radio" name="graphe_type<?=$i?>" value="line"></td>
										<td><font class='texteGris'>Line</font></td>
																				
										<td><input type="radio" name="graphe_type<?=$i?>" value="bar"></td>
										<td><font class='texteGris'>Barchart</font></td>
																				
										<td><input type="radio" name="graphe_type<?=$i?>" value="cumulated"></td>
										<td><font class='texteGris'>Cumulated</font></td>
									</tr>

								<?php }
								
								$i++;
							}
						?>
					</table>
				</td>
				<td align="center" valign="top">
					<table width="100%" border="0" cellspacing="1" cellpadding="2">
						<?php
							$i = 0;
							foreach ($tableau_donnee_entete as $donnee_entete) {
								if (in_array($donnee_entete, $liste_champ['time']) || in_array($donnee_entete, $liste_champ['network'])) { ?>

									<tr align="center"><td>&nbsp;</td></tr>
								
								<?php } else { ?>

									<tr align="center">
										<td>
                                                                                        <!-- 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
											<input type="button" style="border: 1px solid #000;width:15px;height:15px;" name="color<?=$i?>" onfocus=this.blur() value="" onclick="javascript:ouvrir_fenetre('../../../../php/palette_couleurs_graphe.php?nom_zone=color<?=$i?>&nom_champ_cache=color_data<?=$i?>','Palette','no','no',304,100);" onMouseOver="style.cursor='pointer';">
											<input type="hidden" name="color_data<?=$i?>" value="#AAAAAA">
										</td>
									</tr>
								
								<?php }
							
								$i++;
							}
						?>
					</table>
				</td>
				<td align="center" valign="top">
					<table width="100%" border="0" cellspacing="1" cellpadding="2">
						<?php
							$i = 0;
							foreach ($tableau_donnee_entete as $donnee_entete) {
								if (in_array($donnee_entete, $liste_champ['time']) || in_array($donnee_entete, $liste_champ['network'])) { ?>
									
									<tr align="center"><td nowrap>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
								
								<?php } else { ?>
								
									<tr align="center">										
										<td><input type="radio" name="position<?=$i?>" value="left" checked></td>
										<td nowrap><font class='texteGris'>Y-Left</font></td>
																				
										<td><input type="radio" name="position<?=$i?>" value="right"></td>
										<td nowrap><font class='texteGris'>Y-Right</font></td>
									</tr>
								
								<?php }
								
								$i++;
							}
						?>
					</table>
				</td>
			</tr>
			<tr valign="middle">
				<td colspan="5" align="center" >
					<input type="hidden" name="info_save" value="">
					<input type="submit" onclick="formulaire.info_save.value='display';" class="bouton" name="Submit" value="Display Graph">
					<?php
						// $id_query est passé par l'URL et permet de savoir si la query est stockée dans la BBD
						// auquel cas, l'utilisateur peut sauvegarder les paramètres du graphe.
						if ($id_query == "")
							$disabled = "disabled";
					?>
				</td>
			</tr>
		</table>
	</form>

<?php } else {
	print "<center><font face=arial><b>$builder_report->nombre_resultat_builder_report Results for your query</b></font><br>";
} ?>

</body>
</html>
