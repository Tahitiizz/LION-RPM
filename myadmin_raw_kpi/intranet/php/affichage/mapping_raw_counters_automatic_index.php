<?
/*
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Contrôle d'accès
*	=> Utilisation de la classe de connexion àa la base de données
*	=> Gestion du produit
*
*	-maj 09/01/2008 BBX : modification de la condition afin d'accepter qu'une des deux listes soit vide. BZ 8610
*	-maj 09/01/2009 BBX : ajout d'un test pour savoir également si la colonne préfixe est remplie. BZ 8611
*	-maj 16/01/2009 MPR : Ajout du paramètre produit dans l'appel de la fonction get_sys_global_parameters
*
*	02/02/2009  GHX
*		- suppression de la colonne internal_id [REFONTE CONTEXTE]
*		- modification des requetes SQL pour mettre certaines valeurs entre cote [REFONTE CONTEXTE]
*
	18/09/2009 BBX : ajout d'un CASE WHEN sur la condition du préfixe. La colonne prefixe peut exister mais être nulle = dans ce cas le LIKE plante. BZ 11470
	
	19/11/2009 GHX
		- Prise en compte du mappage des compteurs pour les produits Mixed KPI où il faut renseigner 3 colonnes suplémentaires dans sys_field_reference
	05/01/2010 GHX
		- Correction du BZ 13363 [REC][MIXED-KPI][TC#51600] : pas de label produit / famille pour l'ajout d'un compteur
		
	15/01/2010 NSE
		- BZ 13770 : prise en compte du champ blacklisted
 * 02/08/2011 NSE bz 22264 : ne pas démapper un compteur qui n'est pas désactivé.
 * 02/08/2011 NSE bz 23247 : la désactivation du compteur a été demandée mais n'est pas encore effective (il faut lancer un retrieve avant).
 * 07/11/2011 ACS BZ 23647 info field display same data as in counter list
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
*
*	- 10/06/2008 GHX : ajout du choix des familles uniquement pour ceux dont la valeur est à 1 dans la colonne automatic_mapping de la table sys_definition_categorie
*				 modification pour prendre en compte les valeurs par défaut et du type d'agrégation pour les formules (BUG qui n'a jamais été détecté)
*
*	- 27/10/2008 BBX : refonte de l'automatic mapping afin de gérer les doublons dans els noms de compteurs. BZ 7295
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
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
*	@cb1300b_iu2000b_070706@
*
*	12/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0b
*
*	Parser version iu_2.0.0.0b
*
* fonction de mapping des compteurs dynamiques Astellia
* 25-08-2006 : creation du fichier
*/

/*
  - 30/10/2006 xavier : modification de la requête d'insertion d'un raw counter
                        (le serial n'étant pas à jour, on se base sur le id_ligne maximum)
    - maj 28/02/2007 Gwénaël : modification d'une requete sur la mise à jour du champ on_off = 1 pour que le compteur soir supprimer lors du script clean_tables_struture.php
                                                        modification de la requete lors d'un compteur désactivé/démappé est a nouveau mappé, il est désactivé on_off = 0
														
    - maj 18/06/2008 Benjamin : ajout du message d'avertissement sur la désactivation de compteurs
    - maj 18/06/2008 Benjamin : récupération du message d'erreur de compteur utilisé dans un KPI en base de données.
    - ajout 18/06/2008 benjamin : test sur la présence du compteur dans les alarmes
    - ajout 18/06/2008 benjamin : test sur la présence du compteur dans les BH
    - ajout 18/06/2008 benjamin : test sur la présence du compteur dans les GTM
	
	10/12/2009 BBX : correction de l'url de retour sur le choix des familles. BZ 13359
	10/12/2009 BBX : On affiche le changement de famille quand on a plus d'une famille ou plus d'un produit. BZ 13352
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Connexion à la base de données locale
// 31/01/2011 BBX
// On remplace new DatabaseConnection() par Database::getConnection()
// BZ 20450
$database = Database::getConnection();

// Librairies et classes requises
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Automatic Mapping'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/
?>
<?
// Sélection produit / famille	
if(!isset($_GET["family"])){
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Automatic Mapping', false, " AND automatic_mapping = 1 ");
	exit;
}
	
$product = $_GET['product'];

// Connexion à la base de données produit
// 31/01/2011 BBX
// On remplace new DatabaseConnection() par Database::getConnection()
// BZ 20450
$database = Database::getConnection($product);
	
// modif 10:16 10/06/2008 GHX
	// Modification par rapport au Parser IU 4.0
	// 2 familles supplémentaires on des compteurs "automatique"
$query_nb_family = "SELECT COUNT(automatic_mapping) AS nb_families FROM sys_definition_categorie WHERE automatic_mapping = 1";
$result_nb_family = $database->getRow($query_nb_family);
$nb_family = $result_nb_family['nb_families'];
?>
<table width="778" align="center" valign=middle cellpadding="0" cellspacing="2" class="tabPrincipal">
	
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td align="center">
			<img src="<?=NIVEAU_0?>images/titres/counter_selection_interface_roaming_titre.gif"/>
		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<?php 
		// 10/12/2009 BBX
		// On affiche le changement de famille quand on a plus d'une famille ou plus d'un produit. BZ 13352
		if(($nb_family > 1) || (count(getProductInformations()) > 1)){
	?>
	<tr>
		<td align="center" style="padding: 0 40px 0 40px;">
			<fieldset>
			<legend class="texteGrisBold">&nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Current family&nbsp;</legend>
				<table cellspacing="2" cellpadding="2" border="0">
					<tr>
						<td align="left" class="texteGris">
						<?
							// Recuperation du label du produit
							$productInformation = getProductInformations($product);
							$productLabel = $productInformation[$product]['sdp_label'];
							echo $productLabel."&nbsp;:&nbsp;";

							// Recuperation du label de la famille
							// 22/07/2009 BBX : ajout de l'id produit. BZ 10504
							$family_information = get_family_information_from_family($family,$product);
							echo (ucfirst($family_information['family_label']));
						?>
						</td>
						<td align="center" valign="top" class="texteGris">
						<? 	// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
							// modif 08:53 13/06/2008 GHX : correction d'un petit problème impossible de retour en arrière quand on a sauvegardé
							// 10/12/2009 BBX : correction de l'url de retour sur le choix des familles. BZ 13359
							?>
								<a href="<?php echo basename(__FILE__).'?product='.$product; ?>" >
									<img src="<?=NIVEAU_0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0"/>
								</a>
						<? 	 //fin condition sur les familles ?>
						</td>
					</tr>
				</table>
			</fieldset>
			</td>
		</tr>
		<?php
			}
		?>
		<tr>
		<td style="padding: 2px;">
<?php


// modif 24/10/2008 BBX : on récupère les colonnes de la table sys_field_reference_all
$colonnes_sys_field_reference_all = Array();
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='sys_field_reference_all'";
$result = $database->execute($query);
while($array = $database->getQueryResults($result,1)) {
	$colonnes_sys_field_reference_all[] = $array['column_name'];
}
//

// ****************************************************************************************************************************************
// TRAITEMENT DES DONNES POSTEES PAR LE FORMULAIRE
// ****************************************************************************************************************************************
// 09/01/2008 BBX : modification de la condition afin d'accepter qu'une des deux listes soit vide. BZ 8610
if ((isset($_POST['mapped_counters']) && ($_POST['mapped_counters'] != "")) || (isset($_POST['existing_counters']) && ($_POST['existing_counters'] != ""))) {
    // Récupération des id des compteurs mappés
	$mapped_counters = explode('|',$_POST['mapped_counters']);
	// Récupération des id des compteurs non mappés
	$existing_counters = explode('|',$_POST['existing_counters']);
	// On récupère la liste des compteurs à mapper
	$counters_to_map = Array();
	foreach($mapped_counters as $id_counter) {
		if(substr($id_counter,0,4) == "all_") $counters_to_map[] = substr($id_counter,4);
	}
	// On récupère la liste des compteurs à démapper
	$counters_to_unmap = Array();
	foreach($existing_counters as $id_counter) {
		if(substr($id_counter,0,4) == "ref_") $counters_to_unmap[] = substr($id_counter,4);
	}
	
	//****
	// Demapping des compteurs à démapper
	//****
	foreach($counters_to_unmap as $sys_field_reference_id_ligne) {
	
		// Récupération des infos du compteur
		$query = "SELECT edw_field_name,edw_field_name_label,new_field FROM sys_field_reference WHERE id_ligne = '{$sys_field_reference_id_ligne}'";
		$infos_compteur = $database->getRow($query);
		$counter_name = $infos_compteur["edw_field_name"];
        $counter_label = $infos_compteur["edw_field_name_label"];
        $counter_new_field = $infos_compteur["new_field"];
		
		// Varible de démapping
		$unmap = true;
		
        // 02/08/2011 NSE bz 22264 : ne pas démapper un compteur qui n'est pas désactivé.
        // TEST 0 : il faut désactiver un compteur avant de le démapper.
        if($counter_new_field == 0){
            if ($counter_label != "" && $counter_name!=$counter_label) {
				printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_2",$counter_name,$counter_label));
			} else {
				printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_1",$counter_name));
			}
			$unmap = false;
        }
        // 02/08/2011 NSE bz 23247 : la désactivation du compteur a été demandée mais n'est pas encore effective (il faut lancer un retrieve avant)
        if($counter_new_field == 2){
            if ($counter_label != "" && $counter_name!=$counter_label) {
				printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_RETRIEVE_2",$counter_name,$counter_label));
			} else {
				printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_ACTIVATED_COUNTER_DEMAPPING_DISABLED_RETRIEVE_1",$counter_name));
			}
			$unmap = false;
        }
        
		// TEST 1 : compteur utilisé dans un KPI
		$query_kpi = "SELECT distinct kpi_name FROM sys_definition_kpi WHERE on_off = 1 AND lower(kpi_formula) LIKE '%".strtolower($counter_name)."%'";
		$res_kpi = $database->execute($query_kpi);
		$result_nb_kpi = $database->getNumRows();
		if ($result_nb_kpi != 0) {
			if ($counter_label != "") {
				// maj 18/06/2008 Benjamin : récupération du message d'erreur de compteur utilisé dans un KPI en base de données.
				printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_1",$counter_name,$counter_label));
			} else {
				printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_ACTIVATION_COUNTER_DEACTIVATION_DISABLED_1",$counter_name));
			}
			$unmap = false;
		}
		
		// ajout 18/06/2008 benjamin : test sur la présence du compteur dans les alarmes
		// TEST 2 : compteur présent dans une alarme
		$query_static = "SELECT alarm_name FROM sys_definition_alarm_static WHERE alarm_trigger_data_field ILIKE '{$counter_name}' OR additional_field ILIKE '{$counter_name}'";
		$query_dynamic = "SELECT alarm_name FROM sys_definition_alarm_dynamic WHERE alarm_field ILIKE '{$counter_name}' OR alarm_trigger_data_field ILIKE '{$counter_name}' OR additional_field ILIKE '{$counter_name}'";
		$query_topworst = "SELECT alarm_name FROM sys_definition_alarm_top_worst WHERE list_sort_field ILIKE '{$counter_name}' OR alarm_trigger_data_field ILIKE '{$counter_name}' OR additional_field ILIKE '{$counter_name}'";
		$result_static = $database->getRow($query_static);
		$result_dynamic = $database->getRow($query_dynamic);
		$result_topworst = $database->getRow($query_topworst);
		if(count($result_static) > 0)
		{		
			$array_alarm = $result_static;
			if ($counter_label != "") printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_ALARM_1",$counter_name,$counter_label,$array_alarm['alarm_name']));
				else printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_ALARM_2",$counter_name,$array_alarm['alarm_name']));
			$unmap = false;
		}
		elseif(count($result_dynamic) > 0)
		{
			$array_alarm = $result_dynamic;
			if ($counter_label != "") printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_ALARM_1",$counter_name,$counter_label,$array_alarm['alarm_name']));
				else printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_ALARM_2",$counter_name,$array_alarm['alarm_name']));
			$unmap = false;
		}
		elseif(count($result_topworst) > 0)
		{
			$array_alarm = $result_topworst;
			if ($counter_label != "") printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_ALARM_1",$counter_name,$counter_label,$array_alarm['alarm_name']));
				else printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_ALARM_2",$counter_name,$array_alarm['alarm_name']));
			$unmap = false;
		}
		
		// ajout 18/06/2008 benjamin : test sur la présence du compteur dans les BH
		// TEST 3 : compteur présent dans une BH
		$query = "SELECT bh_indicator_name FROM sys_definition_time_bh_formula WHERE bh_indicator_name ILIKE '{$counter_name}'";
		$result = $database->execute($query);
		if($database->getNumRows() > 0)
		{
			if ($counter_label != "") printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_BH_1",$counter_name,$counter_label));
				else printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_BH_2",$counter_name));
			$unmap = false;
		}
		
		// ajout 18/06/2008 benjamin : test sur la présence du compteur dans les GTM
		// TEST 4 : compteur présent dans un GTM		
		// Pour ce test, il faut se connecter au Master
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$db_temp = Database::getConnection();
		$query_graph_data = "
		SELECT page_name FROM sys_pauto_page_name WHERE id_page IN 
		(SELECT id_page FROM sys_pauto_config 
		WHERE class_object = 'counter' 
		AND id_product = '{$product}'
		AND id_elem = '{$sys_field_reference_id_ligne}')";
		$result_graph_data = $db_temp->execute($query_graph_data);
		if($db_temp->getNumRows() > 0)
		{
			$array_result_graph_info = $db_temp->getRow($query_graph_data);
			$graph_name = $array_result_graph_info['page_name'];
			if ($counter_label != "") printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_GTM_1",$counter_name,$counter_label));
				else printf("<span class='texteRouge'>%s</span><br />",__T("A_MAPPING_COUNTERS_COUNTER_USED_IN_GTM_2",$counter_name));
			$unmap = false;
		}		
		unset($db_temp);

		// Si tout est Ok
		if($unmap)
		{
			// modif 24/10/2008 BBX : on se base désormais l'id ligne pour répérer le compteur à modifier/supprimer. C'est l'élément le plus fiable car unique.
			// teste si le compteur a déjà été déployé ou pas
			if ($counter_new_field == 0) {
				// dans le cas du 3eme axe, l'UPDATE retournera plusieurs réseultats, ce qui est normal
				// modif 28/02/2007 Gwénaël
					// Ajout du champ on_off = 1, pour que le compteur soit pris en compte lors du script clean_tables_struture.php afin qu'il soit supprimer
				$query_update = "UPDATE sys_field_reference SET new_field=2, on_off = 1 where id_ligne = '{$sys_field_reference_id_ligne}'";
				$database->execute($query_update);
			} else {
				// dans le cas du 3eme axe, l'UPDATE retournera plusieurs réseultats, ce qui est normal
				$query_delete = "DELETE FROM sys_field_reference where id_ligne = '{$sys_field_reference_id_ligne}'";
				$database->execute($query_delete);
			}
		}
	}
	
	//****
	// Mapping des compteurs à mapper
	//****
	if(get_sys_global_parameters('maximum_mapped_counters','',$product) >= count($mapped_counters))
	{
		foreach($counters_to_map as $sys_field_reference_all_id_ligne) {
			// Récupération des infos du compteur
			$query = "SELECT * FROM sys_field_reference_all WHERE id_ligne = '{$sys_field_reference_all_id_ligne}'";
			$infos_compteur = $database->getRow($query);
			$infos_family = GetGTInfoFromFamily(getFamilyFromIdGroup($infos_compteur['id_group_table'],$product),$product);
			$infos_compteur['edw_group_table'] = $infos_family['edw_group_table'];
                        
                        // 17/02/2012 BBX
                        // BZ 26043 : on ne tronque pas le nom des compteurs sur un Corporate
                        $prefix_counter     = '';
                        $counter_new_name   = strtolower($infos_compteur['nms_field_name']);
                        $counter_label      = $infos_compteur['nms_field_name'];
                        if(!CorporateModel::isCorporate($product))
                        {
                            // Construction du nouveau nom
                            // 11:21 05/01/2010 GHX
                            // Correction d'un probleme sur l'ajout du préfix
                            $prefix_counter = (in_array('prefix_counter',$colonnes_sys_field_reference_all)) ? ( $infos_compteur['prefix_counter'] != "''" && !empty($infos_compteur['prefix_counter']) ? $infos_compteur['prefix_counter'].'_' : '' ) : '';
                            $counter_new_name = $prefix_counter.strtolower(substr(preg_replace('/[^a-zA-Z0-9]/s','_',$infos_compteur['nms_field_name']),0,40));
                            //$counter_label = $infos_compteur['nms_field_name'].(in_array('prefix_counter',$colonnes_sys_field_reference_all) ? ' ('.$infos_compteur['prefix_counter'].')' : '');
                            $counter_label = $infos_compteur['nms_field_name'].((in_array('prefix_counter',$colonnes_sys_field_reference_all) && (trim($infos_compteur['prefix_counter']) != '')) ? ' ('.$infos_compteur['prefix_counter'].')' : '');

                            // On regarde si le compteur est déjà présent dans sys_field_reference mais pas nettoyé (new_field = 2)
                            $query_clean = "SELECT edw_field_name from sys_field_reference WHERE edw_field_name LIKE '{$counter_new_name}%' AND new_field = 2";
                            $result_clean = $database->execute($query_clean);
                            if($database->getNumRows() > 0) {
                                    // Le compteur existe déjà en attente d'être supprimé. On souhaite le remapper. On passe donc le champ new_field à 0
                                    $query_update = "UPDATE sys_field_reference SET new_field = 0 WHERE edw_field_name LIKE '{$counter_new_name}%' AND new_field = 2";
                                    $database->execute($query_update);
                                    // Le traitement s'arrête là
                                    continue;
                            }

                            // On regarde si un compteur possède déjà ce nom
                            $query_check = "SELECT edw_field_name from sys_field_reference WHERE edw_field_name LIKE '{$counter_new_name}%'";
                            $result_check = $database->execute($query_check);
                            $cpt = $database->getNumRows();
                            if($cpt > 0) {
                                    // 11/12/2008 BBX : ne sert nul part. On commente.
                                    //$array_result_check = $database->getRow($query_check);
                                    $cpt = sprintf("%02d",($cpt+1));
                                    $counter_new_name .= '_'.$cpt;
                                    $counter_label .= ' '.$cpt;
                            }
                        }

			// On regarde si les colonnes d'agrégation sont présentes
			if(isset($infos_compteur['edw_agregation_function_axe1']) && isset($infos_compteur['edw_agregation_function_axe2']) && isset($infos_compteur['edw_agregation_function_axe3'])) 
			{
				// modif 11:49 01/07/2008 GHX
				// modification de la création de la formule en fonction des fonctions d'agrégation
				// avant un avait une seule fonction pour l'ensemble des axes alors que maintenant chaque axe peut avoir ca propre valeur							
				$edw_agregation_function_axe1 = $infos_compteur["edw_agregation_function_axe1"];
				$edw_agregation_function_axe2 = $infos_compteur["edw_agregation_function_axe2"];
				$edw_agregation_function_axe3 = $infos_compteur["edw_agregation_function_axe3"];							
				if ( $edw_agregation_function_axe3 == "NA" ) // il n'y a pas d'axe 3
				{
					if ( $edw_agregation_function_axe1 == $edw_agregation_function_axe2 )
					{
						$edw_agregation_function = $edw_agregation_function_axe1;
						$formula = $edw_agregation_function_axe1."(".$counter_new_name.")";
					}
					else
					{
						$edw_agregation_function = 'log';
						$formula = 'CASE WHEN $aggreg_net_ri=0 THEN '.$edw_agregation_function_axe1.'('.$counter_new_name.') ELSE '.$edw_agregation_function_axe2.'('.$counter_new_name.') END ';
					}
				}
				else // il y a un axe 3
				{
					if ( $edw_agregation_function_axe1 == $edw_agregation_function_axe2 && $edw_agregation_function_axe1 == $edw_agregation_function_axe3 )
					{
						$edw_agregation_function = $edw_agregation_function_axe1;
						$formula = $edw_agregation_function_axe1."(".$counter_new_name.")";
					}
					elseif ( $edw_agregation_function_axe2 == $edw_agregation_function_axe3 )
					{
						$edw_agregation_function = 'log';
						$formula = 'CASE WHEN $aggreg_net_ri=0 THEN '.$edw_agregation_function_axe1.'('.$counter_new_name.') ELSE '.$edw_agregation_function_axe2.'('.$counter_new_name.') END ';
					}
					elseif ( $edw_agregation_function_axe1 == $edw_agregation_function_axe2 )
					{
						$edw_agregation_function = 'log';
						$formula = 'CASE WHEN $aggreg_net_ri=2 THEN '.$edw_agregation_function_axe1.'('.$counter_new_name.') ELSE '.$edw_agregation_function_axe3.'('.$counter_new_name.') END ';
					}
					elseif ( $edw_agregation_function_axe1 == $edw_agregation_function_axe3 )
					{
						$edw_agregation_function = 'log';
						$formula = 'CASE WHEN $aggreg_net_ri=1 THEN '.$edw_agregation_function_axe2.'('.$counter_new_name.') ELSE '.$edw_agregation_function_axe1.'('.$counter_new_name.') END ';
					}
					else
					{
						$edw_agregation_function = 'log';
						$formula = 'CASE WHEN $aggreg_net_ri=0 THEN '.$edw_agregation_function_axe1.'('.$counter_new_name.') WHEN $aggreg_net_ri=1 THEN '.$edw_agregation_function_axe2.'('.$counter_new_name.') ELSE '.$edw_agregation_function_axe3.'('.$counter_new_name.') END ';
					}
				}
			} 
			else
			{
				// Cas général
				$edw_agregation_function = $infos_compteur["edw_agregation_function"];
				$formula = "{$edw_agregation_function}({$counter_new_name})";
			}

			// modif 11:30 10/06/2008 GHX
			// Ajout de la valeur par défaut						
			$default_value  = ($infos_compteur["default_value"] == "" ? "null" : "'".$infos_compteur["default_value"]."'");
			$today = date("Ymd");	

			$new_id_ligne = generateUniqId('sys_field_reference');
			// Insertion du compteur
			$query_insert = "INSERT INTO sys_field_reference 
			(nms_table,nms_field_name,edw_group_table,edw_target_field_name,edw_field_name,edw_field_type,edw_agregation_function,edw_agregation_formula,new_date,new_field,on_off,id_group_table,aggregated_flag,comment,visible,default_value,id_ligne,edw_field_name_label) 
			VALUES ('{$infos_compteur['nms_table']}','{$infos_compteur['nms_field_name']}','{$infos_compteur['edw_group_table']}','{$counter_new_name}','{$counter_new_name}','float4','{$edw_agregation_function}','{$formula}',{$today},1,0,{$infos_compteur['id_group_table']},1,'{$infos_compteur['nms_field_name']}',1,{$default_value},'{$new_id_ligne}','{$counter_label}')";
			$database->execute($query_insert);
			
			// >>>>>>>>>>
			// 09:41 19/11/2009 GHX
			// Modification nécessaire au Mixed KPI
			if ( MixedKpiModel::isMixedKpi($product) )
			{
				if ( ereg('(.*)_([^_]+)_([0-9]+)_mk', $infos_compteur['nms_field_name'], $resultInfoSFR ) )
				{
					// On va récupérer sur le produit l'id_ligne correspondant
                                        // 31/01/2011 BBX
                                        // On remplace new DatabaseConnection() par Database::getConnection()
                                        // BZ 20450
					$dbProd = Database::getConnection($resultInfoSFR[3]);
					$queryOldIdLigne = "
						SELECT sfr.id_ligne 
						FROM sys_field_reference AS sfr LEFT JOIN sys_definition_group_table USING(edw_group_table) 
						WHERE lower(sfr.edw_field_name) ='".$resultInfoSFR[1]."'
							AND family = '".$resultInfoSFR[2]."'
						";
					$oldIdLigne = $dbProd->getOne($queryOldIdLigne);
					
					// On met a jour les informations sur le compteur que l'utilisateur vient de mapper
					// 11:22 05/01/2010 GHX 13363
					$update = "
					UPDATE sys_field_reference SET
						sfr_sdp_id = ".$resultInfoSFR[3].",
						sfr_product_family = '".$resultInfoSFR[2]."',
						old_id_ligne = '".$oldIdLigne."',
						edw_field_name_label = edw_field_name ||' - '|| (SELECT sdp_label FROM sys_definition_product WHERE sdp_id = ".$resultInfoSFR[3].")
					WHERE id_ligne = '".$new_id_ligne."'
					";
					$database->execute($update);
				}
			}
			// <<<<<<<<<<
		}
	}
	elseif(count($counters_to_map) > 0) {
		// maj 16/01/2009 - MPR : Ajout du paramètre produit dans l'appel de la fonction get_sys_global_parameters
		print "<span class='texteRouge'>Too many counters were selected. The maximum allowed is set to " . get_sys_global_parameters("maximum_mapped_counters",'',$product) . ".</span><br>";
	}
}
unset($mapped_counters);
unset($existing_counters);
unset($counters_to_unmap);
unset($counters_to_map);

// ****************************************************************************************************************************************
// AFFICHAGE DU FORMULAIRE DE CHOIX AVEC A GAUCHE LES COMPTEURS DISPONIBLES NON BLACKLISTES, NON MAPPES et A DROITE LES COMPTEURS MAPPES
// ****************************************************************************************************************************************
if (!isset($_GET["family"])) {
    $family = get_main_family($product);
} else {
    $family = $_GET["family"];
}
// On récupère les id de tous les groupes qui sont de la famille roaming et qui ont le champ visible à 1.
$query_groupe = "SELECT id_ligne FROM sys_definition_group_table WHERE visible = 1 AND family = '{$family}'";
$result_groupe = $database->execute($query_groupe);
$result_nb_groupe = $database->getNumRows();
if ($result_nb_groupe == 0) { // Affichage du message d'erreur.
    echo "<tr><td align=\"center\">";
    echo "<font style=\"font : normal 9pt Verdana, Arial, sans-serif; color : #585858;s\"><b>Error : no data found. [no data for this family]</b></font>";
    echo "</td></tr>";
} 
else 
{
	// on prend le premier id_group_table trouvé car meme s'il y en a plusieurs pour une même famille, les compteurs sont les mêmes
	$result_array_groupe = $database->getRow($query_groupe);
	$id_ligne = $result_array_groupe["id_ligne"];

	// modif 24/10/2008 BBX : modification de la requête pour :
		// 1) prendre un hash comme référence, car les id des éléments sont désormais hashés pour éviter des bugs
		// 2) prendre le préfixe si celui-ci est disponible pour distinguer 2 compteurs avec le même nms_field_name	
	$columns = (in_array('prefix_counter',$colonnes_sys_field_reference_all)) ? 't0.nms_field_name, t0.prefix_counter, t1.id_ligne AS id_counter' : 't0.nms_field_name, t1.id_ligne AS id_counter';
	// 18/09/2009 BBX : ajout d'un CASE WHEN : la colonne prefixe peut exister mais être nulle = dans ce cas le LIKE plante. BZ 11470
	$condition_prefixe = (in_array('prefix_counter',$colonnes_sys_field_reference_all)) ? "AND CASE WHEN t0.prefix_counter IS NOT NULL THEN t1.edw_field_name LIKE t0.prefix_counter||'%' ELSE TRUE END" : "";
	// collecte tous les compteurs mappés de la table sys_field_reference pour lequels le nms_table est présent dans la table sys_field_reference_all
	$mapped_counters = Array();	
	// 15/01/2010 NSE BZ 13770 : prise en compte du champ blacklisted=0
    // 02/08/2011 NSE bz 23247 : le compteur dont la désactivation a été demandée mais en attende de retrieve ne doit pas apparaître dans la colonne de gauche mais dans celle de droite. Suppression de AND t1.new_field <> 2
	$query = "SELECT {$columns}	FROM sys_field_reference_all t0, sys_field_reference t1
	WHERE t0.nms_field_name = t1.nms_field_name
	AND t0.nms_table = t1.nms_table
	AND t1.id_group_table = {$id_ligne}
	AND blacklisted=0
	{$condition_prefixe}
	ORDER BY nms_field_name";
	$result = $database->execute($query);
	while ($row = $database->getQueryResults($result,1)) {
		$prefix = (isset($row["prefix_counter"]) && ($row["prefix_counter"] != '')) ? " ({$row["prefix_counter"]})" : "";
		$mapped_counters[$row["id_counter"]] = $row["nms_field_name"].$prefix;
	}
	/////////
	$columns = (in_array('prefix_counter',$colonnes_sys_field_reference_all)) ? 'nms_field_name, prefix_counter, id_ligne AS id_counter' : 'nms_field_name, id_ligne AS id_counter';
	// collecte tous les compteurs dynamiques existant non mappés
	$existing_counters = Array();
	$les_tips = Array();
	// 15/01/2010 NSE BZ 13770 : prise en compte du champ blacklisted=0
    // 07/06/2011 BBX -PARTITIONING- Correction des casts
    // 02/08/2011 NSE bz 23247 : le compteur dont la désactivation a été demandée mais en attende de retrieve ne doit pas apparaître dans la colonne de gauche mais dans celle de droite. suppression de AND t1.new_field <> 2 dans NOT IN
	$query = "SELECT {$columns}	FROM sys_field_reference_all  
	WHERE id_ligne NOT IN(
	SELECT t0.id_ligne
	FROM sys_field_reference_all t0,  sys_field_reference t1
	WHERE t0.nms_field_name = t1.nms_field_name
	AND t0.nms_table = t1.nms_table
	AND t1.id_group_table::text = '{$id_ligne}'
	{$condition_prefixe})
	AND blacklisted=0 
	AND id_group_table::text = '{$id_ligne}'
	ORDER BY nms_field_name";
	$result = $database->execute($query);
	$nombre_resultat = $database->getNumRows();
	while ($row = $database->getQueryResults($result,1)) {  
		$prefix = (isset($row["prefix_counter"]) && ($row["prefix_counter"] != '')) ? " ({$row["prefix_counter"]})" : "";
		$existing_counters[$row["id_counter"]] = $row["nms_field_name"].$prefix;
	}
}
//
// modif 24/10/2008 BBX : modification du formulaire. On n'utilise plus la librairie PHP car on ne peut pas gérer les Ids comme on veut.
//
?>
<script>
/****
* 24/10/2008 BBX : permet de transvaser des éléments d'une liste à une autre
* @param int : sens
****/
function move_elements(sens)
{			
	// Fonction qui bouge les éléments
	function move(idz1,idz2)
	{
		if($(idz1).options.selectedIndex != -1)
		{
			var array_to_remove = new Array();
			for(var i = 0; i < $(idz1).options.length; i++)
			{
				if($(idz1).options[i].selected)
				{
					$(idz2)[$(idz2).options.length] = new Option($(idz1).options[i].text, $(idz1).options[i].value);						
					$(idz1).options[i] = null;
					i--;
				}
			}
		}
	}
	// Id des zones
	var id_zone_maitre = 'counters_list';
	var id_zone_esclave = 'mapped_counters_list';
	var id_input_esclave = 'mapped_counters';
	var id_input_maitre = 'existing_counters';
	// Selon le sens
	if(sens == 1) {
		move(id_zone_maitre,id_zone_esclave);
	}
	else {
		move(id_zone_esclave,id_zone_maitre);
	}	
	// Sauvegarde des éléments de la zone esclave
	$(id_input_esclave).value = '';
	for(var i = 0; i < $(id_zone_esclave).options.length; i++)
	{
		var sep = (i == 0) ? '' : '|';
		$(id_input_esclave).value += sep+$(id_zone_esclave).options[i].value;
	}
	// Sauvegarde des éléments de la zone maîte
	$(id_input_maitre).value = '';
	for(var i = 0; i < $(id_zone_maitre).options.length; i++)
	{
		var sep = (i == 0) ? '' : '|';
		$(id_input_maitre).value += sep+$(id_zone_maitre).options[i].value;
	}
}

</script>
<!-- Affichage du formulaire -->
<br />
<form name="formulaire" action="mapping_raw_counters_automatic_index.php?family=<?=$_GET['family']?>&product=<?=$product?>" method="post">
<center>
<div style="width:800px;">
<fieldset>
	<table width="100%" cellpadding="10" cellspacing="0" border="0">
		<tr>
			<td align="right">
				<span class="texteGrisBold" style="margin-right:290px;">Counters</span><br />
				<? // 07/11/2011 ACS BZ 23647 remove the "Comment" area. ?>
				<select class="zoneTexteStyleXP" id="counters_list" multiple size="17" style="width:350px;" onfocus="$('mapped_counters_list').value = null">
					<?php
						foreach($existing_counters as $id_counter=>$nms_field_name) {
							echo '<option value="all_'.$id_counter.'">'.htmlentities($nms_field_name).'</option>';
						}
					?>
				</select>
				<input type="hidden" id="existing_counters" name="existing_counters" value="" />
			</td>
			<td align="center" valign="middle" width="30">
				<input type="button" value="->" style="width:25px;height:25px;" onclick="move_elements(1)" />
				<br />
				<input type="button" value="<-" style="width:25px;height:25px;" onclick="move_elements(2)" />
			</td>
			<td align="left">
				<span class="texteGrisBold">Selected counters</span><br />
				<? // 07/11/2011 ACS BZ 23647 remove the "Comment" area. ?>
				<select class="zoneTexteStyleXP" id="mapped_counters_list" multiple size="17" style="width:350px;" onfocus="$('counters_list').value = null">
					<?php
						foreach($mapped_counters as $id_counter=>$nms_field_name) {
							echo '<option value="ref_'.$id_counter.'">'.htmlentities($nms_field_name).'</option>';							
						}	
					?>					
				</select>
				<input type="hidden" id="mapped_counters" name="mapped_counters" value="" />
			</td>
		</tr>
	</table>
<? // 07/11/2011 ACS BZ 23647 remove the "Comment" area. ?>
</fieldset>
<br />
</div>
<input type="submit" class="bouton" value="&nbsp;Save&nbsp;" />
</center>
</form>
<!-- Fin affichage du formulaire -->
<?php

// maj 18/06/2008 Benjamin : ajout du message d'avertissement sur la désactivation de compteurs
echo '<center><span class="texteRouge">'.__T('A_MAPPING_COUNTERS_HISTORY_WARNING').'</span></center>';
?>


		</td>
	</tr>
</table>

</body>
</html>
