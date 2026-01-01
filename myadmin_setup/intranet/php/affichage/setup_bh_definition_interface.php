<?
/**
 * @cb50000@
 * 27/07/2009 - Copyright Astellia
 *
 * 17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
 * 27/07/2009 BBX : adaptation CB 5.0
 * 28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 * 12/08/2010 NSE DE firefox bz 16851 : ajout de id="data_value"
 * 09/11/2011 ACS BZ 24526 Display a message when saving or deleting busy hour configuration
 */
?>
<?
/*
*	@cb41000@
*
*	- maj 14/10/2008 SLC : centrage de la famille suite à ajout du DOCTYPE
*
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
*	@cb21101@
*
*	08/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.1.01
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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
/*
	01/09/2006 : MD - Creation du fichier

	- maj 07/11/2006, benoit : inclusion du script 'fenetres_volantes.js' pour pouvoir utiliser la fonction       'popalt()'

	- maj 08/03/2007, benoit : ajout du parametre "max_size" dans l'appel de la fonction 'getFieldValue()'

*/
session_start();
include_once dirname(__FILE__).'/../../../../php/environnement_liens.php';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/bh_functions.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');

// Récupération famille / produit
$family = (isset($_GET['family'])) ? $_GET['family'] : $_POST['family'];
$product = (isset($_GET['product'])) ? $_GET['product'] : $_POST['product'];
$action='';
if(isset($_POST['action']))
	$action=$_POST['action'];

// Instance de connexion à la base de données
$database = Database::getConnection($product);

// 09/11/2011 ACS BZ 24526 BEGIN Display a message when saving or deleting busy hour configuration
$saveSuccessful = false;
$deleteSuccessful = false;
$hasError = false;
$error = "";

/******************************Debut traitrement********************/

//Attention : le calcul de la BHpour une famille doit se faire si et seulement si une formule a ete definie pour cette famille
if($action=="save")
{
    // 14/10/2011 BBX
    // BZ 24714 : On test la formule de raw / kpi avant de sauvegarder
    if($_POST['data_type'] == 'KPI') {
        $kpiModel = new KpiModel();
        $result = $kpiModel->testFormula($_POST['data_value'], $database, $family, $product);
    }
    else {
        $RawModel = new RawModel();
        $result = $RawModel->testFormula($_POST['data_value'], $database, $family, $product);
    }
        
    // La formule est incorrecte
    if(!$result) {
        $hasError = true;
        $error = __T('A_SETUP_BH_FORMULA_IS_INCORRECT',$_POST['data_type']);
    }        
    // La formule est nulle
    elseif($result === 2) {
        $hasError = true;
        $error = __T('A_SETUP_BH_FORMULA_IS_NULL',$_POST['data_type']);
    }
    
    // Sauvegarde
    if (!$hasError) {
		if (!bhIsDefined($family)) {
			addBHDefinition($family,$_POST['data_type'],$_POST['data_value'],$_POST['bh_ta_compute'],$_POST['bh_na_compute'],"");
			//on active le calcul de la BH pour la famille courante
	            exec("php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/bh_management.php on $family $product");
		} 
		else 
		{
			$infos=array('bh_indicator_type'=>$_POST['data_type'],'bh_indicator_name'=>$_POST['data_value'],
						 'bh_parameter'=>$_POST['bh_ta_compute'],'bh_network_aggregation'=>$_POST['bh_na_compute']);
			updateBHDefinition($family,$infos);
		}
		$saveSuccessful = true;
	}
    // Fin BZ 24174
}
elseif($action=="delete")
{
	deleteBHDefinition($family);
	//on desactive le calcul de la BH pour la famille courante
	exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/bh_management.php off $family $product");
	$deleteSuccessful = true;
}
// 09/11/2011 ACS BZ 24526 END
/**************************Fin traitement****************************/

//recuperation de la famille
$family=$_GET['family'];

// Recuperation du label de la famille
$family_label=$family;//family_name par defaut
$family_infos=get_family_information_from_family($family,$product);//fonction de edw_function_family
if(isset($family_infos['family_label']))
	$family_label=$family_infos['family_label'];
	
// Recuperation du label du produit
$productInformation = getProductInformations($product);
$productLabel = $productInformation[$product]['sdp_label'];

//initialisation du formulaire avec ses valeurs par defaut
$bh_checked='checked="checked"';
$standard_checked='checked="checked"';

//on regarde si une BH est definie pour la famille courante
$bh_infos=getBHInfos($family);
if(count($bh_infos)>0)
{
	$indicator_type=$bh_infos['bh_indicator_type'];
	$indicator_name=$bh_infos['bh_indicator_name'];
	$raw_selected=($indicator_type=="RAW")?'selected="selected"':"";
	$kpi_selected=($indicator_type=="KPI")?'selected="selected"':"";
	$bh_checked=($bh_infos['bh_parameter']==1)?'checked="checked"':"";
	$xdbh_checked=($bh_infos['bh_parameter']!=1)?'checked="checked"':"";
	$standard_checked=($bh_infos['bh_network_aggregation']=="standard")?'checked="checked"':"";
	$aggregated_checked=($bh_infos['bh_network_aggregation']=="aggregated")?'checked="checked"':"";
}

// HEADER Astellia
$arborescence = 'Busy Hour definition';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
	<script type="text/javascript" src="<?=NIVEAU_0?>js/bh.js"></script>
		<div style="text-align:center"><img src="<?=NIVEAU_0?>images/titres/setup_busy_hour.gif" alt="&nbsp"/></div>
		<form name="form_setup_bh_definition" action="setup_bh_definition_interface.php?family=<?=$family?>&product=<?=$product?>" method="post">
			<table align="center" border="0" cellpadding="10" cellspacing="0" width="750px" class="tabPrincipal">
				<tr>
					<td align="center" valign="top" class="texteGris" style="text-align:center;">
						<?=$productLabel?>&nbsp;:&nbsp;<?=$family_label?>
					<? 	// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
						if (get_number_of_family(false,$product) > 1){ ?>
							<a href="setup_bh_index.php?product=<?=$product?>" target="_top">
								<img src="<?=NIVEAU_0?>images/icones/change.gif" style="cursor:pointer" onMouseOver="popalt('Change family');" onMouseOut="kill()" border="0" />
							</a>
					<? 	} //fin condition sur les familles ?>
					</td>
				</tr>
				<tr>
					<td>
						<fieldset>
							<legend class="texteGrisBold">Busy Hour definition</legend>
							<table border="0" width="100%" cellspacing="0px" cellpadding="10px">
								<tr>
									<td>

									</td>
								</tr>
								<tr>
									<td class="texteGris">Based on : </td>

									<td>
										<?php

											// 08/03/2007 - Modif. benoit : ajout du parametre "max_size" dans l'appel de la fonction 'getFieldValue()'

										?>
										<select class="zoneTexteStyleXP" name="data_type" style="width:100px" onchange="getFieldValue('<?=NIVEAU_0?>',this.value.toLowerCase(),'data_value','<?=$family?>','<?=$product?>','makeSelection', 50)">
											<option value='makeSelection'>Type</option>
											<option value='KPI'  <?=$kpi_selected?> >KPI</option>
											<option value='RAW'  <?=$raw_selected?> >Raw Counter</option>
										</select>
                                        <!-- 12/08/2010 NSE DE firefox bz 16851 : ajout de id="data_value" -->
										<select class="zoneTexteStyleXP" id="data_value" name="data_value" style="width:417px">
											<option value='makeSelection'>Make a selection</option>
										</select>

									</td>
								</tr>
								<tr>
									<td class="texteGris">Time aggregation : </td>
									<td>
										<input type="radio" name="bh_ta_compute" value="1" <?=$bh_checked?>/>
										<span class="texteGris">BH</span>
										<img src="<?=NIVEAU_0?>images/icones/cercle_info.gif" alt="&nbsp;" title="Busiest hour of the day" style="cursor:pointer"/>
										<input type="radio" name="bh_ta_compute" value="3" <?=$xdbh_checked?>/>
										<span class="texteGris">3DBH</span>
										<img src="<?=NIVEAU_0?>images/icones/cercle_info.gif" alt="&nbsp;" title="Average of the three busiest hours of the day" style="cursor:pointer"/>
									</td>
								</tr>

								<tr>
									<td class="texteGris">Network&nbsp;aggregation&nbsp;: </td>
									<td>
										<input type="radio" name="bh_na_compute" value="standard" <?=$standard_checked?> />
										<span class="texteGris">Standard</span>
										<!--img src="<?=NIVEAU_0?>images/icones/cercle_info.gif" alt="&nbsp;" title="Busy hour of day" style="cursor:pointer"/-->
										<input type="radio" name="bh_na_compute" value="aggregated" <?=$aggregated_checked?> />
										<span class="texteGris">Aggregated</span>
										<!--img src="<?=NIVEAU_0?>images/icones/cercle_info.gif" alt="&nbsp;" title="Sum of network aggregation busy hour" style="cursor:pointer"/-->
									</td>
								</tr>
								<tr>
									<td align="center" colspan="2">
										<input type="button" value="Save" class="bouton" onclick="save_bh_def(this.form,this.form.elements['data_type'].value,this.form.elements['data_value'].value,this.form.elements['action'])"/>
										<input type="button" value="Delete" class="bouton" onclick="delete_bh_def(this.form,this.form.elements['action'],'<?=$family_label?>')"/>
									</td>
								</tr>
								<tr>
									<td>
										<input type="hidden" name="family" value="<?=$family?>" />
										<input type="hidden" name="product" value="<?=$product?>" />
										<input type="hidden" name="action" value="save"/>
									</td>
								</tr>
							</table>
                            <?php
                            // 14/10/2011 BBX
                            // BZ 24174 : Affichage de l'erreur en cas de problème de sauvegarde
                            // 09/11/2011 ACS BZ 24526 Display a message when saving or deleting busy hour configuration
                            if ($hasError) {
                            	echo '<div id="msg_error" class="errorMsg">'.$error.'</div>';
                            }
                            else if ($saveSuccessful) {
                            	echo '<div id="msg_info" class="okMsg">'.__T('A_SETUP_BH_SAVE_OK').'</div>';
                            }
                            else if ($deleteSuccessful) {
                            	echo '<div id="msg_info" class="okMsg">'.__T('A_SETUP_BH_RESET_OK').'</div>';
                            }
                            ?>
						</fieldset>
					</td>
				</tr>
			</table>
		</form>
		<!--pour initialiser la liste des raws / kpis si une bh est definie-->
		<?

			// 08/03/2007 - Modif. benoit : ajout du parametre "max_size" dans l'appel de la fonction 'getFieldValue()'

			if($indicator_name!=""){?>
			<script>
				getFieldValue('<?=NIVEAU_0?>','<?=strtolower($indicator_type)?>','data_value','<?=$family?>','<?=$product?>','<?=$indicator_name?>', 50);
			</script>
		<?}?>

	</body>
</html>
