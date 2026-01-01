<?
/*
 *  Fichier qui affiche la liste des niveaux d'agrégation exploités dans Downlaod Topology ou Download Topology Third Axis
 * 
 *  31/01/2011 OJT : Correction bz 20186 + réindentation de quelques lignes
 *	16/12/2011 ACS BZ 25158 Back button missing for 3axis slave products
*/
?>
<?
/*
*	@cb4100@
*
*	21/11/2008 - Copyright Acurio
*
*	Composant de base version cb_4.1.0.0
*
*	-  maj 21/11/2008 - MPR : Ajout de l'id du produit
*	-  maj 24/11/2008 - MPR : enregistrement  des coordonnées dans l'url
*	-  maj 27/11/2008 - MPR : Ajout du type du header
*	-  maj 29/05/2009 - MPR : Correcrtion du bug 9601 : Erreur js lorsque l'on est sur une famille secondaire
*
*	21/08/2009 GHX
*		- Ajout de l'id du produit pour la fonction get_family_information_from_family() sinon on n'a pas le nom de la famille pour le produit slave
*
*	15:18 16/10/2009 SCT
*		- BZ 12071 => erreur javascript sur la page => ajout de la condition d'existance du 3ème axe
*	 16:51 06/11/2009 MPR
		- BZ 10661 => Ajout du $ devant le nom de la variable pour envoyer sa valeur (sinon retourne null)
	15:45 26/11/2009 MPR
		- BZ
*	01/03/2010 NSE bz 14244
*		- le paramètre de la table sys_global_parameters est activate_trx_charge_in_topo et non activate_trx_charge_into_topo
*		- uniformisation en utilisant le nom de variable activate_trx_charge_in_topo et non activate_trx_charge_into_topo pour les noms de variables
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	 - maj 25/01/2008 - maxime : On remplace x et y part longitude et latitude
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
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 05/04/2007, gwénaël : modification de la méthode pour récupérer les na (appelle de la fonction 			  getNaLabelList)

	- maj 19/04/2007, christophe : si la variable axe3 est à true dans l'url, on donwload seulement la topo 3ème   de la famille et seules les na appartenant au 3ème sont listées.

	- maj 01/06/2007, benoit : affichage des infos geographiques seulement si le gis est actif

*
*/
?>
<?
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*/
?>
<?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*
*	- maj 22 11 2006 christophe : on affiche les NA dans l'ordre correspondant à la requête se trouvant dans sys_selecteur_properties.
*/
?>
<?
/*
	- maj 01/06/2006, stéphane : ajout de la ckeckbox : first_line_header

	- maj 23/08/2006, xavier : ajout des champs x, y et azimuth pour la famille principale (lignes 110 à 145)        plus petite na est automatiquement séléctionnée

	- maj 17/11/2006, benoit : correction du message d'erreur de choix d'au moins un champ ("choose" au lieu de      "chose")

	- maj , maxime : intégration du champs on_off pour chaque famille

*/

?>
<?
// Récupération des paramètre d'activation du calcul nb erlang et de l'utilisation des paramètres trx et charge dans la topo
$naMin = get_network_aggregation_min_from_family( get_main_family( $id_prod ), $id_prod );
$activate_capacity_planing = get_sys_global_parameters('activate_capacity_planing', 1,$id_prod);
// 01/03/2010 NSE bz 14244 NSE le paramètre de sys_global_parameters est activate_trx_charge_in_topo et non activate_trx_charge_into_topo
$activate_trx_charge_in_topo = get_sys_global_parameters('activate_trx_charge_in_topo', 1,$id_prod);
$family_information = get_family_information_from_family($family, $id_prod);

?>
<script type="text/javascript">
function check_all (na_min) {
	if (document.getElementById('select_all').checked == true) {
		valeurcheckbox = true;
	} else {
		valeurcheckbox = false;
	}
	for (i=0; i<document.getElementsByName('fields[]').length; i++) {

		if( document.getElementById('fields[]'+i).value != na_min ){
			document.getElementById('fields[]'+i).checked = valeurcheckbox;
		}
	}
	<?
	// maj 29/05/2009 - MPR : Correcrtion du bug 9601 : Erreur js lorsque l'on est sur une famille secondaire
	// maj 19/08/2009 - MPR : On check les coordonnées uniquement si le GIS est activé et que l'on est sur la famille principale
	// 15:17 16/10/2009 SCT : BZ 12071 => erreur javascript sur la page => ajout de la condition d'existance du 3ème axe
	if(!$axe3 && $family == get_main_family($id_prod) && get_sys_global_parameters('gis',$id_prod) ){
	?>
	for (i=0; i<document.getElementsByName('coordinates[]').length; i++) {
		document.getElementById('coordinates[]'+i).checked = valeurcheckbox;
	}
	<?
	}
	// maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Downnload Topology
	// 01/03/2010 NSE bz 14244 remplacement de activate_trx_charge_into_topo par activate_trx_charge_in_topo pour uniformisation
	if(  $activate_capacity_planing && $activate_trx_charge_in_topo  && $naMin == $family_information['network_aggregation_min'] ){
	?>
	for (i=0; i<document.getElementsByName('paramsErlang[]').length; i++) {
		document.getElementById('paramsErlang[]'+i).checked = valeurcheckbox;
	}
	<?
	}
	?>
}
</script>

<SPAN ID="topo1" STYLE="display:<?=$display_1?>">
<form name='upload_obj_ref' method='get' action='admintool_download_topology.php?family=<?=$family?>' target='_blank'">

	<!--<table width="400px" align="center" valign=middle cellpadding="8" cellspacing="0" class="tabPrincipal" border="0">
		<tr>
			<td colspan=4 align="center">-->
	<div  align="center" valign="middle" style="width:400px;align:center;border:0;" class="tabPrincipal" >
			<fieldset >
				<div class="texteGris" style="text-align:center;margin-top:4px;">
					<?
						// Recuperation du label du produit
						$productInformation = getProductInformations($product);
						$productLabel = $productInformation[$product]['sdp_label'];
						echo $productLabel."&nbsp;:&nbsp;";

						// Recuperation du label de la famille
						echo (ucfirst($family_information['family_label']));
					?>
					</td>
					<td align="center" valign="top" class="texteGris">
					<? 	// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone 
						// 16/12/2011 ACS BZ 25158 Back button missing for 3axis slave products
					?>
							<a href="<? echo(str_replace("&family=".$family, " ",$_SERVER['PHP_SELF']."?".$_SERVER['argv'][0])); ?>" target="_top">
								<img src="<?=NIVEAU_0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0" style="vertical-align:middle;"/>
							</a>
					<? //fin condition sur les familles ?>
				</div>
			</fieldset>
			<br/>
			<fieldset>
				<legend class="texteGrisBold">
					&nbsp;
					<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>
					&nbsp;
					Select Fields to Download
					&nbsp;
				</legend>
				<table cellpadding="0" cellspacing="0" align="center">
					<tr>
						<td valign="middle">
							<span class="texteGris">&nbsp;<li>first line header</li></span>
						</td>
						<td align=left valign="bottom">
						  <input type='checkbox' name='first_line_header' value='1' disabled="disabled" checked="checked">
						</td>
					</tr>
					<tr>
						<td valign="middle">
							<span class="texteGris">&nbsp;<li>type of header</li></span>
						</td>

						<td align=left valign="bottom">
							<select id="type_header">
									<!--
									Correction du bug B9885 : Inhibition de l'entête astellia
									<option value="ast" selected="selected">Astellia header</option>
									-->
									<option value="ta">T&amp;A header</option>
							</select>
						</td>
					</tr>
					<?
					// 12:17 21/09/2009 GHX
					// Ajout de l'ID produit
					$family_information = get_family_information_from_family($family, $_GET['product']);
					$na_type = ( $axe3 ) ? 'na_axe3' : 'na';
					$na_list = getNaLabelList($na_type,$family,$id_prod);
					$na_list = $na_list[$family];
					// 15:41 16/10/2009 SCT : ajout pour correction du BZ 12071
					// 16:38 06/11/2009 MPR : Appel de la fonction get_network_aggregation_min_axe3_from_family() à la place de getNAAxe3MinFromFamily() // Fonction erronée
					if($axe3)
						$na_min_axe3 = get_network_aggregation_min_axe3_from_family($family, $id_prod);

					?>
					<tr><tr/>
					<tr>
						<td colspan="2">
							<br/>
							<span class="texteGris">&nbsp;&nbsp;&nbsp;list of active aggregations :&nbsp;&nbsp;&nbsp;</span>
						</td>
						<td align="center">
							<fieldset>
							<legend class="texteGrisBold">All&nbsp;</legend>
                            <!-- 12/08/2010 OJT : Correction bz16874 pour DE Firefox -->
							<input type="checkbox" name="select_all" id="select_all"
							<?php // 15:41 16/10/2009 SCT : ajout pour correction du BZ 12071
							// maj 16:51 06/11/2009 MPR : Correction du bug 10661 - Ajout du $ devant le nom de la variable pour envoyer sa valeur (sinon retourne null)
							?>
							value="check all" onclick="check_all ('<?=(!$axe3)?$family_information['network_aggregation_min']:$na_min_axe3?>')">
							</fieldset>
						</td>
					</tr>

				<?
					$nets=array();
					$compteurChamps = 0;
					$compteurCoordinates = 0;
					$compteurParamsErlang = 0;
					// - modif 05/04/2007 : Gwénaël : modification de la méthode pour récupérer les na
					// 19/04/2007 christophe : si on n'affiche que les na 3ème axe, $axe3 est à true
					if(count($na_list) > 0){
					foreach($na_list as $na => $na_label)
					{
						if(!in_array(strtolower($na),$nets))
						{
							// 15:41 16/10/2009 SCT : ajout pour correction du BZ 12071
							//16:42 06/11/2009 MPR : Suppression du in_array - On remplace la condition
							if($na == $family_information['network_aggregation_min'] || ($axe3 && strtolower($na) == $na_min_axe3))
								$checked = true;
							else
								$checked = false;
					?>
					<tr>
						<td valign="middle">
							<span class="texteGris">&nbsp;<li><?=$na_label?></li></span>
						</td>
						<td align=left valign="bottom">
						<? if (!$checked) { ?>
							<input type='checkbox' name='fields[]' id='fields[]<?
											  echo $compteurChamps;
											  $compteurChamps++;
										   ?>' value='<?=$na?>'>
						<? } else { ?>
							<input type='checkbox' checked disabled name='fields[]' id='fields[]<?
											  echo $compteurChamps;
											  $compteurChamps++;
										   ?>' value='<?=$na?>'>
						<? } ?>
						</td>
					</tr>
					<tr>
						<td valign="middle">
							<span class="texteGris">&nbsp;<li><?=$na_label?> label</li></span>
						</td>
						<td align=left valign="bottom">
						  <input type='checkbox' name='fields[]' id='fields[]<?
										  echo $compteurChamps;
										  $compteurChamps++;
									   ?>' value='<?=$na?>_label'>
						</td>
					</tr>
				<?
							$nets[]=$na;
						}
					}
				} else {
					echo "<div class='errorMsg'>No Network Aggregation.</div>";exit;
				}


			// 19/04/2007 christophe : il n'y a pas de on off sur les 3ème axe
			// modif 31/05/2007 Gwénaël
				// la condition doit aussi prendre en compte que que les champs x/y/azimuth ne doivent pas etre afficher pour la famille principale
			if ( !$axe3 )
			{
				// 01/06/2007 - Modif. benoit : en plus de la verif sur la famille principale, on s'assure que le gis est actif

				if ($family == get_main_family($id_prod) && get_sys_global_parameters('gis',$id_prod))
				{
				 // 25/01/2008 - maxime : On remplace x et y part longitude et latitude
					?>
					<tr>
						<td colspan="2">
							<br />
							<span class="texteGris">&nbsp;&nbsp;&nbsp;Geographical information :</span>
						</td>
					</tr>
                                <!-- 31/01/2011 OJT : bz20186, mise de la colonne Azimuth en premier -->
					<tr>
						<td valign="middle">
                                                  <span class="texteGris">&nbsp;<li>Azimuth</li></span>
						</td>
						<td align=left valign="bottom">
						  <input type='checkbox' name='coordinates[]' id='coordinates[]<?
													 echo $compteurCoordinates;
													 $compteurCoordinates++;
													 ?>' value='azimuth'>
						</td>
					</tr>
					<tr>
						<td valign="middle">
                                        <span class="texteGris">&nbsp;<li>Longitude</li></span>
						</td>
						<td align=left valign="bottom">
						<!-- 24/11/2008 MPR : On enregistre les coordonnées géographique dans un autre tableau -->
                                                <!-- Ajout d'un compteur sur les coordonnées -->
						  <input type='checkbox' name='coordinates[]' id='coordinates[]<?
														 echo $compteurCoordinates;
														 $compteurCoordinates++;
														 ?>' value='longitude'>
						</td>
					</tr>
					<tr>
						<td valign="middle">
							<span class="texteGris">&nbsp;<li>Latitude</li></span>
						</td>
						<td align=left valign="bottom">
						  <input type='checkbox' name='coordinates[]' id='coordinates[]<?
														  echo $compteurCoordinates;
														  $compteurCoordinates++;
														  ?>' value='latitude'>
						</td>
					</tr>
					<?
				}
				?>
				<tr>
					<td colspan="2">
						<br />
						<span class="texteGris">&nbsp;&nbsp;&nbsp;Activation :</span>
					</td>
				</tr>
				<tr>
					<td valign="middle">
						<span class="texteGris">&nbsp;<li>on/off</li></span>
					</td>
					<td align=left valign="bottom">
					  <input type='checkbox' name='fields[]' id='fields[]<?
												 echo $compteurChamps;
												 $compteurChamps++;
												 ?>' value='eor_on_off'>
					</td>
				</tr>
				<?
				// maj 15/10/2009 - MPR : Ajout des paramètres trx et charge
				// 01/03/2010 NSE bz 14244 remplacement de activate_trx_charge_into_topo par activate_trx_charge_in_topo pour uniformisation
				if( $activate_capacity_planing && $activate_trx_charge_in_topo && $naMin == $family_information['network_aggregation_min'] ){
				?>
				<tr>
					<tr>
						<td colspan="2">
							<br />
							<span class="texteGris">&nbsp;&nbsp;&nbsp;Erlang Parameters :</span>
						</td>
					</tr>
				</tr>
				<tr>
					<td valign="middle">
						<span class="texteGris">&nbsp;<li>Trx</li></span>
					</td>
					<td align=left valign="bottom">
					  <input type='checkbox' name='paramsErlang[]' id='paramsErlang[]<?
												  echo $compteurParamsErlang;
												  $compteurParamsErlang++;
												  ?>' value='trx'>
					</td>
				</tr>
				<tr>
					<td valign="middle">
						<span class="texteGris">&nbsp;<li>Charge</li></span>
					</td>
					<td align=left valign="bottom">
					  <input type='checkbox' name='paramsErlang[]' id='paramsErlang[]<?
													  echo $compteurParamsErlang;
													  $compteurParamsErlang++;
													  ?>' value='charge'>
					</td>
				</tr>
				<?

				}
			}
			?>
			</table>
                        <div align="center">
                            <br/>
						<span class="texteGrisBold">confirm >>&nbsp;</span>
						<input type="button" class="bouton" id="send_fields" onclick="return get_Fields()" value="&nbsp;Export&nbsp;"/>
                        </div>
                        <br/>
		</fieldset>
		</div>
		<br/>
</form>
</span>


<script language="JavaScript">
function get_Fields(){
	// maj 21/11/2008 - MPR : Ajout de l'id du produit
	// maj 27/11/2008 - MPR : Changement d'url
	var url = '<?=NIVEAU_0?>php/download_topology.php?product=<?=$id_prod?>&family=<?=$family?>&na_type=<?=$na_type?>';
	for (i=0;i<document.getElementsByName('fields[]').length;i++) {

		if (document.getElementById('fields[]'+i).checked) {

			url += "&fields%5B%5D=" + document.getElementById('fields[]'+i).value;
		}
	}

	<?
	// maj 29/05/2009 - MPR : Correcrtion du bug 9601 : Erreur js lorsque l'on est sur une famille secondaire
	// 15:17 16/10/2009 SCT : BZ 12071 => erreur javascript sur la page => ajout de la condition d'existance du 3ème axe
	if(!$axe3 && $family == get_main_family($id_prod) ){
	?>
	// maj 24/11/2008 - MPR : enregistrement  des coordonnées dans l'url
	for (i=0;i<document.getElementsByName('coordinates[]').length;i++) {

		if (document.getElementById('coordinates[]'+i).checked) {

			url += "&coordinates%5B%5D=" + document.getElementById('coordinates[]'+i).value;

		}
	}
	<?
	}
	// maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Downnload Topology
	// 01/03/2010 NSE bz 14244 remplacement de activate_trx_charge_into_topo par activate_trx_charge_in_topo pour uniformisation
	if( $activate_capacity_planing && $activate_trx_charge_in_topo && $naMin == $family_information['network_aggregation_min'] ){
	?>

	for (i=0;i<document.getElementsByName('paramsErlang[]').length;i++) {

		if (document.getElementById('paramsErlang[]'+i).checked) {

			url += "&paramsErlang%5B%5D=" + document.getElementById('paramsErlang[]'+i).value;

		}
	}
	<?
	}
	?>
	// maj 27/11/2008 - MPR : Ajout du type du header
	url += <?=( $axe3 ) ? '"&axe3=true"+':""?>"&type_header=" + document.getElementById('type_header').value;
	<?

	?>

	open_window(url,'download_topo','yes','yes',300,30)

	// document.getElementById('send_fields').href = url;

}

</script>
