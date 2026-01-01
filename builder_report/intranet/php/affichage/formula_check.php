<?
/*
 * 17/11/2011 ACS BZ 22837 Can't show formula in Formula category
 * 
 * 
*	@cb50400@
*
*	Composant de base version cb_5.0.4.00
*
*	26/08/2010 NSE DE Firefox bz 17080 : formulaire Querie Builder / New Formula HS
*
*/
?><?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
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
<?php
session_start();
// This file is used to check everything when someone wants to save a new KPI or a modified equation for an existing KPI
//
// List of things to be checked:
// The name of the KPI uses only "usual" characters
// The name of the KPI already exists (then, user must confirm that he wants to update or cancel)
// In case it's a new KPI, the field "new_field" must be set to the value "1". It must not be changed otherwise
// Check syntax by executing the KPI_formula as a query
//
//
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/php2js.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/deploy_and_compute_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");

// gestion multi-produit - 21/11/2008 - SLC
$product=$_POST['product'];
include_once('connect_to_product_database.php');

// PAGE
$arborescence = 'Generic Counters';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<script src="<?=NIVEAU_0?>js/generic_counters.js"></script>
<script src="<?=NIVEAU_0?>js/verification_syntaxe_kpi.js"?></script>
<div id="container" style="width:100%;text-align:center">
<?php

// returns true if a kpi definition already exists for the name chosen (case insensitive)
// 17/11/2011 ACS BZ 22837 correct request fo filter on family
function formula_name_already_exists($formula_name) {
	
	global $family, $product;
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($product);
	$query = "SELECT count(*) FROM forum_formula WHERE lower(formula_name) = '".strtolower($formula_name)."' and family = '$family'";
	$result = $db->getOne($query);
	return $result > 0;
}

// checks if the name of the KPI uses only "usual" characters
function formula_name_is_accepted($formula_name)
{
    if (ereg("^[a-zA-Z][a-zA-Z0-9_]*$", $formula_name)) {
        return true;
    } else {
        return false;
    }
}

function refuse_save($error_message)
{
    if (strlen($error_message) > 0) { // if there is a message, display message
            ?>
		<script language="JavaScript">
		alert('<?=$error_message?>');
		</script>
		<?php
        }
        reload_kpi_builder();
}

function reload_kpi_builder($alert_msg = "")
{
	global $edw_group_table, $kpi_formula, $kpi_name, $id_ligne;
	global $family,$product;

	$kpi_formula = str_replace("::float4", "", $kpi_formula);

    ?>
		<script language="JavaScript">
			var msg="<?=trim($alert_msg)?>";
			if(msg!="")
			{
				alert(msg.toString());
			}

			parent.kpi_list.location='formula_table.php?family=<?=$family?>&product=<?=$product?>';
			parent.kpi_builder.location="formula_builder.php?family=<?=$family?>&product=<?=$product?>&generic_counter_numerateur=<?=urlencode($kpi_formula)?>&generic_counter_name=<?=urlencode($kpi_name)?>&edw_group_table=<?=urlencode($edw_group_table)?>&id_generic_counter=<?=urlencode($id_ligne)?>";
		</script>
	<?php
}

function create_kpi($kpi_name, $kpi_formula, $edw_group_table)
{
    global $id_user,$family,$product;
    $kpi_name = strtoupper($kpi_name); // save new kpis in upper case only
    $kpi_type = "float4";
    $numerator_denominator = "total";
    $new_field = "1";
    $new_date = date("Ymd");
    $on_off = "1";
    // insère l'ajout de ::float4 dans le cas de division du type x/y avec x et y
    $pattern = "[0-9]*\.?[0-9]+/[0-9]*[\.]*[0-9]+";
    $kpi_formula = ereg_replace($pattern, "\\0::float4" , $kpi_formula);
    if (strstr($edw_group_table, 'mixed') !== false) { // this line is a little tricky. It just means: "in the case of mixed kpis"
            $edw_group_table = 'edw_aland_0';
    }
    $query_create = "INSERT INTO forum_formula(formula_name,formula_equation,formula_edw_group_by,formula_creation_date,on_off,id_user,family)";
    $query_create .= " VALUES('$kpi_name','$kpi_formula','$edw_group_table','$new_date','$on_off','$id_user','$family')";

    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
    $db = Database::getConnection($product);
    $db->execute($query_create);
    reload_kpi_builder();
}

function update_kpi($id_ligne, $formula, $edw_group_table)
{
    if (strstr($edw_group_table, 'mixed') !== false) { // this line is a little tricky. It just means: "in the case of mixed kpis"
            $edw_group_table = 'edw_aland_0';
    }

    $new_date = "'" . date("Ymd") . "'";
    // insère l'ajout de ::float4 dans le cas de division du type x/y avec x et y
    $pattern = "[0-9]*\.?[0-9]+/[0-9]*[\.]*[0-9]+";
    $formula = ereg_replace($pattern, "\\0::float4" , $formula);
    $query_update = "UPDATE forum_formula SET formula_equation='$formula',formula_creation_date=$new_date,formula_edw_group_by='$edw_group_table' WHERE id_formula='$id_ligne';";
    global $product;
    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
    $db = Database::getConnection($product);
    $update_result = $db->execute($query_update); // or die ($query_update);
    reload_kpi_builder();
}

function get_formula_info($id_ligne)
{
	global $product;
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($product);
	$query_info = "SELECT formula_name AS kpi_info FROM forum_formula WHERE id_formula='$id_ligne'";
	return $db->getone($query_info);
}

function ask_for_overwrite($decision_for_overwrite, $information)
{
	global $kpi_formula, $kpi_name, $edw_group_table, $id_ligne,$product;

	if ($decision_for_overwrite == 'yes') {
		return true;
	} elseif ($decision_for_overwrite == 'no') {
		return false;
	} else {
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db = Database::getConnection($product);
		$modify_request_query = "select texte from report_builder_save where ids_formula like ('%,\"" . $id_ligne . "\",%')  or ids_formula like ('{[\"" . $id_ligne . "\",%') or ids_formula like ('{[\"" . $id_ligne . "\"]}') or ids_formula like ('%,\"" . $id_ligne . "\"]}')";
		$modify_request = $db->getall($modify_request_query); // or die ($modify_request_query);
		foreach ($modify_request as $row) {
			$liste[] = $row["texte"];
		}
		if (count($liste) > 0) {
			$message = "It will modify the following saved query : " . implode(',', $liste);
		}
		?>
		<form name="formulaire" method="post" action="<?=$PHP_SELF?>">
			<input type="hidden" name="zone_formule_numerateur" value="<?=$kpi_formula?>">
			<input type="hidden" name="generic_counter" value="<?=$kpi_name?>">
			<input type="hidden" name="group_table_name" value="<?=$edw_group_table?>">
			<input type="hidden" name="zone_id_generic_counter" value="<?=$id_ligne?>">
			<input type="hidden" name="product" value="<?=$product?>">
		</form>
		<script language="JavaScript">
			var message="This Formula already exists. Do you want to replace it?"+"\n"+<?php echo php2js($message);
        ?>;
			var choice=confirm(message.toString());
			if (choice)
			{
				formulaire.action+='?overwrite=yes';
			} else {
				formulaire.action+='?overwrite=no';
			}
			formulaire.submit();
		</script>
		<?php
    }
}

function get_id_ligne($formula_name, $id_ligne = null)
{
	global $product;
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($product);
        // 16/08/2010 NSE DE Firefox bz 17080 remplace !== par !=
	if ($id_ligne != null) {
		$query = "SELECT formula_name FROM forum_formula WHERE id_formula=$id_ligne";
		$found_formula_name = $db->getone($query);
		if ($found_formula_name)
			if (strtolower($found_formula_name) == strtolower($formula_name))
				return $id_ligne;
	}
	
	// if id_ligne==Null or if previous search failed
	$query = "SELECT formula_name, id_formula FROM forum_formula ORDER BY formula_name";
        // 16/08/2010 NSE DE Firefox bz 17080  remplace $db_prod par $db !!
	$result = $db->getall($query);
	foreach ($result as $row) {
		$found_formula_name = $row["formula_name"];
		if (strtolower($found_formula_name) == strtolower($formula_name)) {
			$id_ligne = $row["id_formula"];
			return $id_ligne;
		}
	}
	// if we reach this part, a problem has occured (kpi_name found but no match)
}

function check_formula_in_queries($id_ligne)
{
	if ($decision_for_overwrite == 'yes') {
		return true;
	} elseif ($decision_for_overwrite == 'no') {
		return false;
	} else {
		global $product;
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db = Database::getConnection($product);

		$modify_request_query = "select texte from report_builder_save where ids_formula like ('%,\"" . $id_ligne . "\",%')  or ids_formula like ('{[\"" . $id_ligne . "\",%') or ids_formula like ('{[\"" . $id_ligne . "\"]}') or ids_formula like ('%,\"" . $id_ligne . "\"]}')";
		$modify_request = $db->getall($modify_request_query); // or die ($modify_request_query);
		foreach ($modify_request as $row) {
			$liste[] = $row["texte"];
		}
		if (count($liste) > 0) {
			$message = "You can not delete this formula as it is used in the following queries : " . implode(',', $liste);
			?>
			<script language="JavaScript">
				alert(<?=php2js($message)?>);
			</script>
			<?php
			return false;
		} else {
			return true;
		}
	}
}

function delete_kpi($id_ligne)
{
	global $product;
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($product);

	$query = "DELETE FROM forum_formula WHERE id_formula=$id_ligne";
	$result = $db->execute($query);
	if ($result) {
		$message = 'The formula has been deleted';
	} else {
		$message == 'An error has occured. The formula has not been deleted.';
	}
	return $message;
}

function formula_accepted($kpi_formula, $edw_group_table)
{
	global $product;
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($product);

	// ID group table
	$queryIDGT = "SELECT id_ligne FROM sys_definition_group_table 
	WHERE edw_group_table = '$edw_group_table'";
	$result = $db->getRow($queryIDGT);
	$group_id 		= $result['id_ligne'];
	//
	$family = getFamilyFromIdGroup($group_id, $product);
	$net_min_level = get_network_aggregation_min_from_family($family,$product);
	$net_min_level_axe3 = '';
	
	// 10/05/2010 BBX
	// On doit également récupérer le NA 3ème axe s'il existe. BZ 15425
	if(GetAxe3($family, $product)) {
		$net_min_level_axe3 = '_'.get_network_aggregation_min_axe3_from_family($family,$product);
	}

	// Min TA
	$queryMinTa = "SELECT time_agregation FROM sys_definition_group_table_time
	WHERE id_group_table = $group_id 
	AND id_source = -1
	AND data_type = 'raw'";
	$result = $db->getRow($queryMinTa);
	$time_min_level 		= $result['time_agregation'];
	//

	//pour les mixed
	//$tmp = explode(",", $edw_group_table);
	//$table = implode("_raw_sai_day, ", $tmp) . "_raw_cell_day";
	$table=$edw_group_table."_raw_".$net_min_level.$net_min_level_axe3."_".$time_min_level;
	$query = "select " . $kpi_formula . " from " . $table . " limit 1";

	// 10/05/2010 BBX
	// il faut utiliser execute. BZ 15425
	$result = @$db->execute($query);

	if ($result)
		return true;
	else
	    return false;
}

// - - - - - - - - - - - - - - - - - - - -
// Main part
// - - - - - - - - - - - - - - - - - - - -
$kpi_formula		= $_POST["zone_formule_numerateur"];
$kpi_name			= $_POST["generic_counter"];
$edw_group_table	= $_POST["group_table_name"];
// ID group table
$queryIDGT = "SELECT id_ligne FROM sys_definition_group_table 
WHERE edw_group_table = '$edw_group_table'";
$result = $db_prod->getRow($queryIDGT);
$id_gt_value 		= $result['id_ligne'];
//
$family = getFamilyFromIdGroup($id_gt_value, $product);
$info_gt			= get_axe3_information_from_gt($id_gt_value,$product);
$id_ligne			= $_POST["zone_id_generic_counter"];
$decision_for_overwrite	= (isset($overwrite))? $overwrite : 'not decided';

if ($action != "delete") {
	if ($edw_group_table == "mixed")
		$edw_group_table = "edw_aland_0";
	if (formula_name_is_accepted($kpi_name)) {
		if (formula_accepted($kpi_formula, $edw_group_table)) {
			if (formula_name_already_exists($kpi_name)) {
				// if a KPI already exists with the same name (we compare on lower case because fields names of the database take only lower case names)
				$id_ligne = get_id_ligne($kpi_name, $id_ligne); // we keep it if already set; we get it from the database if not
				$information = get_formula_info($id_ligne); // description of the existing KPI with the same name
				$user_wants_to_overwrite = ask_for_overwrite($decision_for_overwrite, $information); // user must agree or refuse to overwrite the existing KPI
				// user wants to overwrite existing KPI with the same name
				$user_wants_to_overwrite='yes';
				if ($user_wants_to_overwrite) {
					// remplace pour être certain qu'on va pas avoir 2 fois ::float4 car apparemment le script passe 2 fois dans cette boucle
					// $kpi_formula=str_replace("::float4","",$kpi_formula);
					update_kpi($id_ligne, $kpi_formula, $edw_group_table);
				} else {
					// user doesn't want to overwrite existing KPI with the same name
					// refuse_save("Operation canceled.");
					reload_kpi_builder("Operation canceled.");
				}
			} else {
				// kpi name doesn't exist yet
				create_kpi($kpi_name, $kpi_formula, $edw_group_table);
				reload_kpi_builder('Formula created');
			}
		} else {
			$error_message = "Formula not accepted";
			reload_kpi_builder($error_message);
		}
	} else {
		// if kpi_name is not accepted
		$error_message = "Formula name not accepted.";
		reload_kpi_builder($error_message);
	}
} else {
	$id_ligne = get_id_ligne($kpi_name, $id_ligne); // we keep it if already set; we get it from the database if not
	$user_wants_to_delete = check_formula_in_queries($id_ligne); // user must agree or refuse to overwrite the existing KPI
	if ($user_wants_to_delete) {
		// remplace pour être certain qu'on va pas avoir 2 fois ::float4 car apparemment le script passe 2 fois dans cette boucle
		$message = delete_kpi($id_ligne);
                // 16/08/2010 NSE DE Firefox bz 17080 : on ne veut pas réafficher la formule détruite
                unset($kpi_name, $kpi_formula, $id_ligne);
		reload_kpi_builder($message);
	} else {
		reload_kpi_builder("Operation canceled.");
	}
}

?>
</div>
</body>
</html>
