<?php
/*
	17/07/2009 GHX
		- Correction d'une erreur JS
		- Passe de l'id produit dans plusieurs fonctions
	26/08/2009 GHX
		 - Correction du BZ 10913 [REC][T&A CB 5.0][TC#14356][TP#1][TS#AC25-CB40][TOP WORST LIST ALARM]: manque infos sur trigger dans detail alarme
	31/08/2009 GHX
		- Correction d'un bug si on n'a pas de KPI pour une famille (cas rare mais je suis tompé sur ce cas, pas de chance)
		- Correction du BZ 11314 [CB 5.0][ALARM MANAGEMENT] le troisieme axe n'est pas affiché dans le détail de l'alarme
		
	02/11/2011 ACS BZ 23238 Threshold value displayed in alarm details is wrong
	09/12/2011 ACS Mantis 837 DE HTTPS support
	21/12/2011 ACS BZ 25228 no access to related slave dashboard
*/
?>
<?
/*
* 	Fichier qui afiche des détails d'une alarme
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*	 maj 16/01/2009 - MPR : Ajout du paramètre produit pour récupérer le séparateur axe3
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
	- maj 14/11/2007, benoit : repercusion du bug 5267 corrigé en 3.0.0.16 pour la version 4.0.0.00
	- maj 30/11/2007, maxime : Mise à jour de l'affichage des additional field  par rapport au nouveau mode de calcul.
	- maj 12/12/2007, gwénaël : refonte totale du fichier
	- maj 10/01/2008, gwénaël : ajout du lien externe dans le PDF

	- maj 16/04/2008, benoit : correction du bug 6324
	- maj 21/04/2008, benoit : correction du bug 6325
	- maj 21/04/2008, benoit : correction du bug 6463
	- maj 26/05/2008, benjamin : La valeur de l'indicateur du "Sort condition" doit être indiquée même s'il n'y a pas de trigger. BZ6667
	- maj 26/05/2008, benajmin : limitation des résultats d'une requête itérative à la valeur de l'itération. BZ6676
	- maj 09/06/2008, benoit : correction du bug 6800
	- maj 09/06/2008, benoit : correction du bug 6858
	- maj 24/06/2008, benoit : correction du bug 6954
*
*/
?>
<?
/*
*	@cb30015@
*
*	06/11/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.15
*
	- maj 07/11/2007, benoit : (bug 5267) correction de la défintion du raw / kpi sortfield affiché, celui-ci correspondait au raw / kpi employé   par le trigger et non celui utilisé par le sortfield
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
*	- 23/04/2007 christophe :  on vat chercher le label 3ème axe dans sys_definition_gt_axe, col external_reference.
*	- 11/05/2007 Gwénaël : changement de "axe" par "Axis" dans l'affichage
*
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
  11/10/2006 xavier : modification des données envoyées dans le panier (additional details)
  12/10/2006 xavier : modification des données récupérées en méthode GET.
                      à faire : optimiser les requêtes pour ne plus avoir besoin que de l'oid de l'alarme en entrée
  25/10/2006 xavier : requêtes optimisées.
                      résolution d'un bug dans le calcul de la valeur à comparer au threshold (alarmes dynamiques)

	- maj 05/04/2006 : Gwénaël : modification pour prendre en compte le troisième axe
		- dans l'affichage du résultat de l'alarme
		- pdf :  le titre et le tableau
	- maj 13/04/2007 Gwénaël
		modification du séparateur |@| pour devenir |s|
	05/05/2015 JLG Mantis 6470 : manage dynamic alarm threshold operand (min/max)
*/

session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "class/CbCompatibility.class.php");

$debug = get_sys_debug('alarm_detail');
// couleurs utilisées pour différencier les lignes des tableaux
$couleur = array("#f0f0f0","#dddddd");

// Récupère les informations passé dans l'url ($_GET)
$oid_alarm		= $_GET['oid_alarm'];
$alarm_id			= $_GET['alarm_id'];
$edw_alarm_type	= $_GET['alarm_type'];
$family			= $_GET['family'];
$product			= $_GET['product'];
$isAlarmIterative	= false;

// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$db = Database::getConnection($product);

// 05/05/2015 JLG : mantis 6470
$canManageThresholdOperand = CbCompatibility::canManageThresholdOperand($product);

// Récupère le label de la famille
// 14:42 17/07/2009 GHX
// Ajout de l'identifiant du produit
$familyList = getFamilyList($product);
$family_label = $familyList[$family];


// Tableau contenant la liste de tous les kpi de la famille
// 14:42 17/07/2009 GHX
// Ajout de l'identifiant du produit
$listKpi = get_kpi($family, $product);
// 14:23 31/08/2009 GHX
// Correction d'un bug si on n'a pas de KPi pour la famille
if ( $listKpi == null )
	$listKpi = array();
// Tableau contenant la liste de tous les raw counter de la famille
$listRaw = get_counter($family, $product);


if ($edw_alarm_type != 'alarm_top_worst')  {
	$color_critical_level = array(
			'critical' => get_sys_global_parameters('alarm_critical_color', '#FF0000', $product),
			'major' => get_sys_global_parameters('alarm_major_color', '#fab308', $product),
			'minor' => get_sys_global_parameters('alarm_minor_color', '#f7fa08', $product)
		);
}
else {
	$color_critical_level = array(
			'-' => '#f0f0f0'
		);
}

/******************************************************************************/

// Récupère le niveau de criticité du résultat à afficher ainsi que les infos nécessaires pour les alarmes itératives
$query = "SELECT id_result, critical_level, na, na_value, ta, ta_value FROM edw_alarm WHERE OID = ".$oid_alarm;
$result = $db->execute($query);
if ( $db->getNumRows() == 0 ) {
	echo "<div class='texteRougeBold' style='text-align:center'>Warning : Alarm Calculation in progress<br><br>Please, close this popup and refresh<br><br></div>";
	exit;
}

$resultCurrent = pg_fetch_object($result);
if ( $edw_alarm_type == 'alarm_top_worst' )
	$resultCurrent->critical_level = '-';

unset($query);
unset($result);

/******************************************************************************/

// Récupère les informations sur la définition de l'alarme...

$alarmCurrent = new ArrayObject();

// 09/06/2008 - Modif. benoit : correction du bug 6800. Creation d'un nouveau tableau contenant la liste des triggers par niveau de criticité. Ce tableau ne servira semble-t-il que pour les alarmes statiques où l'on peut avoir une combinaison de plusieurs triggers

$alarmInfoByCriticity = array();

switch ( $edw_alarm_type ) {
	case 'alarm_static':
		$query = "SELECT alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_value, critical_level, ";
		break;
	case 'alarm_dynamic':
		$alarm_threshold_operand_select = '';
		if ($canManageThresholdOperand) {
			$alarm_threshold_operand_select = " alarm_threshold_operand, ";
		}
		$query = "SELECT alarm_field, alarm_threshold, " . $alarm_threshold_operand_select . " alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_value, critical_level, discontinuous, ";
		break;
	case 'alarm_top_worst':
		$query = "SELECT list_sort_field, list_sort_asc_desc, alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_value, ";
		break;
}

$query .= " network, nb_iteration, period, CASE WHEN nb_iteration > 1 AND period > 1 THEN true ELSE false END AS isIterative
	FROM sys_definition_$edw_alarm_type
	WHERE alarm_id = '$alarm_id' ";

// ... en fonction du niveau de criticité à afficher pour les alarmes statiques et dynamiques
//if ( $edw_alarm_type != 'alarm_top_worst' )
//	$query .= " AND critical_level = '".$resultCurrent->critical_level."'";

// 21/04/2008 - Modif. benoit : correction du bug 6463. Ajout de la condition sur la valeur de 'list_sort_field' dans la requete de récuperation des informations de l'alarme top-worst. Sinon, si un trigger est présent, c'est la valeur de la ligne de trigger (qui ne comprend pas la définition du sort) que l'on va récuperer

if ($edw_alarm_type == 'alarm_top_worst') {
	$query .= " AND list_sort_field != ''";
}

$result = $db->execute($query);
while ( $row = pg_fetch_object($result) ) {

	if ( $row->isiterative == 't' ) {
		$row->isIterative = true;

		// 21/04/2008 - Modif. benoit : correction du bug 6325. Ajout de la condition "|| $edw_alarm_type == 'alarm_top_worst'" pour gérer les alarmes top-worst itératives

		if ( $row->critical_level == $resultCurrent->critical_level || $edw_alarm_type == 'alarm_top_worst')
			$isAlarmIterative = true;
	}
	else
		$row->isIterative = false;

	if ( !empty($row->critical_level) ){

		$alarmCurrent->offsetSet($row->critical_level, $row);

		// 09/06/2008 - Modif. benoit : correction du bug 6800. Dans le cas d'une alarme statique, on stocke la condition (operande + valeur) des triggers des différents niveaux de criticité dans le tableau '$alarmInfoByCriticity'

		if ($edw_alarm_type == "alarm_static") {
			$alarmInfoByCriticity[$row->critical_level][$row->alarm_trigger_data_field] = array('operand' => $row->alarm_trigger_operand, 'value' => $row->alarm_trigger_value);
		}
	}
	elseif ( $edw_alarm_type == 'alarm_top_worst' )
	{
		// 16:42 26/08/2009 GHX
		// Correction du BZ 10913
		// Ajout du champ critical_level sinon erreur PHP : illegal offset
		$row->critical_level = '-';
		$alarmCurrent->offsetSet('-', $row);
	}
}

//__debug($alarmCurrent, "alarmCurrent");

unset($query);
unset($result);

/******************************************************************************/

// Récupère les informations générales sur le déclenchement de l'alarme
/// 17:34 31/08/2009 GHX
// Correction du BZ 11314
// Ajout des colonnes pour le troisieme axe
$query = "SELECT DISTINCT id_result, id_alarm, ta, ta_value, na, na_value, a3, a3_value, critical_level, alarm_type, (SELECT distinct family FROM sys_definition_$edw_alarm_type WHERE alarm_id = id_alarm) as family,";

if ($edw_alarm_type == 'alarm_top_worst')
	$query .= " (SELECT distinct list_sort_asc_desc FROM sys_definition_alarm_top_worst WHERE alarm_id = id_alarm and list_sort_asc_desc is not null) as sort_by,";
if ($edw_alarm_type == 'alarm_dynamic')
	$query .= " (SELECT distinct discontinuous FROM sys_definition_alarm_dynamic WHERE alarm_id = id_alarm and alarm_field is not null) as discontinuous,";

$query .= " (SELECT distinct alarm_name FROM sys_definition_$edw_alarm_type WHERE alarm_id = id_alarm) as alarm_name
          FROM edw_alarm
          WHERE edw_alarm.OID=$oid_alarm";

$result = $db->execute($query);

$infoDisplay = pg_fetch_object($result);
unset($query);
unset($result);

// Récupère les labels des NA
$infoDisplay->axe3 = get_axe3($family, $product);
$naLabelList = getNaLabelList('', '', $product);
// 17:40 31/08/2009 GHX
// Correction du BZ 11272
// Ajout du utf8_encode
if ( $infoDisplay->axe3 ) { //si il y a un troisième axe
	//explode la na_value pour récupére la valeur du na de l'axe1 et celle de l'axe3
	$infoDisplay->info_na = utf8_encode(getNaLabel($infoDisplay->na_value, $infoDisplay->na, $family, $product)." [".$naLabelList[$infoDisplay->na][$family]."]");
	$infoDisplay->info_na_axe3 = utf8_encode(getNaLabel($infoDisplay->a3_value, $infoDisplay->a3, $family, $product)." [".$naLabelList[$infoDisplay->a3][$family]."]");
	$infoDisplay->axe_information= get_axe3_information_from_family($family, $product);
}
else { //pas de troisuème axe
	$infoDisplay->info_na = utf8_encode(getNaLabel($infoDisplay->na_value, $infoDisplay->na, $family, $product)." [".$naLabelList[$infoDisplay->na][$family]."]");
}

// Récupère le label de la TA
$infoDisplay->info_ta = getTaValueToDisplay($infoDisplay->ta, $infoDisplay->ta_value)." [".getTaLabel($infoDisplay->ta)."]";

/******************************************************************************/

// Récupère les résultats de l'alarme à afficher
$resultDisplay = new ArrayObject();
$additionalDisplay = new ArrayObject();

$iter = $alarmCurrent->getIterator();

while( $iter->valid() ) {

	$c = $iter->current();

	// 21/04/2008 - Modif. benoit : correction du bug 6235. Ajout de la condition "&& $edw_alarm_type != 'alarm_top_worst'" pour gérer le cas des alarmes dynamiques itératives

	if ( ($c->critical_level != $resultCurrent->critical_level) && $edw_alarm_type != 'alarm_top_worst') {
		$resultDisplay->append($c);
	}
	else
	{
		if ( $c->isIterative === true ) {

			// 21/04/2008 - Modif. benoit : correction du bug 6235. Dans le cas des alarmes top-worst, on n'inclut pas la condition sur le critical_level dans la requete de sélection des résultats

			$critical_level_condition = "";

			if ($edw_alarm_type != 'alarm_top_worst') {
				$critical_level_condition = " AND ea.critical_level = '$c->critical_level' ";
			}

			// 09/06/2008 - Modif. benoit : correction du bug 6858. Dans le cas d'une alarme statique, on peut avoir plusieurs triggers définis pour l'alarme. Il faut en tenir compte dans le LIMIT de la requete de sélection des résultats de l'alarme itérative

			$nb_triggers = 1;

			if ($edw_alarm_type == "alarm_static" ) {

				// 24/06/2008 - Modif. benoit : correction du bug 6954. Reprise de la requete de définition du nombre de triggers en rajoutant une condition sur les champs additionnels afin de les inclure dans le décompte des triggers

				//$sql = "SELECT COUNT(*) AS nb_triggers FROM sys_definition_alarm_static WHERE alarm_id = ".$alarm_id." AND critical_level = '".$c->critical_level."'";

				$sql = "SELECT COUNT(*) AS nb_triggers FROM sys_definition_alarm_static WHERE alarm_id = '$alarm_id' AND (critical_level = '$c->critical_level' OR additional_field != '')";

				$req = $db->execute($sql);
				$row = pg_fetch_array($req);

				$nb_triggers = $row['nb_triggers'];
			}
			elseif ($edw_alarm_type == 'alarm_top_worst')
			{
				// Correctoin du BZ 10913
				// Si on a un trigger sur le top-worst on modif le nombre de trigger pour avoir la bonne limite
				if ( !empty($c->alarm_trigger_data_field) )
					$nb_triggers = 2;
			}

			// maj 26/05/2008 Benajmin : limitation des résultats d'une requête itérative à la valeur de l'itération. BZ6676
			//$limit = ($iter[$c->critical_level]->nb_iteration);
			$limit = ($iter[$c->critical_level]->nb_iteration)*$nb_triggers;

			$query = "
					SELECT  ta, ta_value, trigger, trigger_operand, trigger_value, value, additional_details, ead.id_result, critical_level, field_type
					FROM edw_alarm_detail ead, edw_alarm ea
					WHERE ea.ta_value <= $resultCurrent->ta_value
						AND ea.ta_value >= ".getTAMinusPeriod($resultCurrent->ta, $resultCurrent->ta_value, $c->period)."
						AND ea.id_alarm = '$alarm_id'
						AND ea.na = '$resultCurrent->na'
						AND ea.na_value = '$resultCurrent->na_value'
						$critical_level_condition
						AND ead.id_result = ea.id_result
					ORDER BY critical_level ASC, ta_value DESC
					LIMIT {$limit}";
		}
		else
		{
			// 21/04/2008 - Modif. benoit : correction du bug 6463. Définition d'une requete spécifique au type "top-worst" et aux autres types

			if ($edw_alarm_type == 'alarm_top_worst') {
				$query =	 "	SELECT  ta, ta_value, trigger, trigger_operand, trigger_value, value, additional_details,
								ead.oid, critical_level, field_type, ead.id_result
							FROM edw_alarm_detail ead, edw_alarm ea
							WHERE ea.OID = $oid_alarm
								AND ead.id_result = ea.id_result
							ORDER BY critical_level ";
			}
			else
			{
				$query =	 "	SELECT  ta, ta_value, trigger, trigger_operand, trigger_value, value,
								additional_details, ead.oid, critical_level, field_type
							FROM edw_alarm_detail ead, edw_alarm ea
							WHERE ea.OID = $oid_alarm
								AND ead.id_result = ea.id_result
								AND ea.critical_level = '$c->critical_level'
							ORDER BY critical_level ";
			}

			/*$query = "
					SELECT  ta, ta_value, trigger, trigger_operand, trigger_value, value, additional_details, ead.oid, critical_level, field_type
					FROM edw_alarm_detail ead, edw_alarm ea
					WHERE ea.OID = ".$oid_alarm."
						AND ead.id_result = ea.id_result";
			if ( $edw_alarm_type != 'alarm_top_worst' )
				$query .= " AND ea.critical_level = '".$c->critical_level."'";
			$query .= "	ORDER BY critical_level";*/
		}

		// __debug($query, '$query');
		$result = $db->execute($query);
		if ( $db->getNumRows() > 0 ) {
			while( $row = pg_fetch_object($result) ) {
//			__debug($row);
				if ( $row->field_type == 'trigger' )
					$resultDisplay->append($row);
				else
					$additionalDisplay->append($row);
			}
		}
		else
			$resultDisplay->append($c);
	}
	$iter->next();

	unset($query);
	unset($result);
}

/******************************************************************************/

// Formatage des données dans un tableau pour une restitution plus facile

$trigger = array();
// Header du tableau

if ( $isAlarmIterative === true ){
	$trigger['header'][0] = 'Date';
}

$trigger['header'][1]= 'Critical level';

if ($edw_alarm_type == 'alarm_dynamic')
{
	$trigger['header'][2] = 'Threshold raw/kpi';
	$trigger['header'][3] = 'Threshold';
	$trigger['header'][4] = 'Value';
        // Mantis 4178 : split additional details into two columns
	$trigger['header'][11] = 'Average';
	$trigger['header'][12] = 'Overrun (%)';
//	$trigger['header'][12] = 'Discontinuous';
}
elseif ($edw_alarm_type == 'alarm_top_worst')
{
	$trigger['header'][5] = 'Sort field';
	$trigger['header'][6] = 'Sort by';
	// 21/04/2008 - Modif. benoit :
	//$trigger['header'][7] = 'Value';
}

$trigger['header'][8] = 'Trigger';
$trigger['header'][9] = 'Condition';
$trigger['header'][10] = 'Value';

// Chaque boucle correspond à une ligne dans le tableau
$iter = $resultDisplay->getIterator();
$compteur = 1;
$prevIDResult = array(); // Mémorise l'id_result avec la valeur du compteur associé (sert pour les alarmes dynamiques)

// 18/04/2008 - Modif. benoit : correction du bug 6325. Remise en forme du tableau d'iterations pour les alarmes statiques et top-worst

if ($edw_alarm_type == "alarm_static" || $edw_alarm_type == "alarm_top_worst") {

	//__debug($iter, "iter before");

	$iter2 = new ArrayObject();

	$tab_id_result = array();

	while ( $iter->valid() ) {
		$c = $iter->current();

		if (isset($c->id_result)) {
			if (in_array($c->id_result, array_keys($tab_id_result))) {
				$local_id_result = $c->id_result."_".$tab_id_result[$c->id_result];
				$tab_id_result[$c->id_result] = $tab_id_result[$c->id_result] + 1;
			}
			else
			{
				$tab_id_result[$c->id_result] = 1;
				$local_id_result = $c->id_result;
			}

			$c->id_result = $local_id_result;
		}
		$iter2->append($c);
		$iter->next();
	}

	$iter = $iter2->getIterator();

	//__debug($iter, "iter after");
}

// Fin modif. benoit 18/04/2008

while ( $iter->valid() ) {
	$c = $iter->current();

	$level = $c->critical_level;

	if ( $edw_alarm_type == 'alarm_top_worst' ) $level = '-';
	$alarm_level = $alarmCurrent->offsetGet($level);

	// __debug($c, '$c');
	//__debug($prevIDResult, '$prevIDResult');
	//__debug($alarm_level, '$alarm_level');

	$hasResults = true;

	if ( !isset($c->trigger) ){
		$hasResults = false;
	}

	// 06/06/2008 - Modif. benoit : correction du bug 6800. Ajout de la condition portant sur la valeur de '$c->id_result' (différent de null) uniquement pour les alarmes statiques

	$id_result_test = true;

	if (($edw_alarm_type == "alarm_static") && ($c->id_result == "")) {
		$id_result_test = false;
	}

	if ( $hasResults === true && array_key_exists($c->id_result, $prevIDResult) && $id_result_test){

		$compteur = $prevIDResult[$c->id_result];
	}
	else
	{
		// 21/04/2008 - Modif. benoit : correction du bug 6325. On indique la criticité sur toutes les lignes et non pas uniquement sur la première

		//if ( isset($trigger[$level]) ){
		if (isset($trigger[$level][$compteur][1])){
			$trigger[$level][$compteur][1]= '';
		}
		else
		{
			$trigger[$level][$compteur][1]= UCFirst($level);
		}
	}

	if ( $isAlarmIterative === true ) {
		if ( $hasResults === true ){
			$trigger[$level][$compteur][0] = getTaValueToDisplay($c->ta, $c->ta_value);
		}
		else
		{
			$trigger[$level][$compteur][0] = '-';
		}
	}

	//__debug($trigger, '$trigger');

	if ( $edw_alarm_type == 'alarm_dynamic' ) { // *****  ALARME DYNAMIQUE ****** //
		if ( $hasResults === true )
                {
                    // 15/09/2010 BBX
                    // Valeurs par défaut pour trigger
                    // BZ 17675
                    $trigger[$level][$compteur][8] = '';
                    $trigger[$level][$compteur][9] = '';
                    $trigger[$level][$compteur][10] = '';

                    // 02/11/2011 ACS BZ 23238 Threshold value displayed in alarm details is wrong
                    // Si c'est le Threshold ...
                    if ( $c->additional_details != '' && $alarm_level->alarm_field == $c->trigger) {
                        if ( array_key_exists($alarm_level->alarm_field, $listKpi) )
                            $trigger[$level][$compteur][2] = $listKpi[$alarm_level->alarm_field];
                        else
                            $trigger[$level][$compteur][2] = $listRaw[$alarm_level->alarm_field];
						$alarm_threshold_operand_str = '';
						if ($canManageThresholdOperand) {
							$alarm_threshold_operand_str = 
								($alarm_level->alarm_threshold_operand != 'both' && $alarm_level->alarm_threshold_operand != '')?
								$alarm_level->alarm_threshold_operand . "<br/>":"";
						}
                        $trigger[$level][$compteur][3] = $alarm_threshold_operand_str . ' > '.$alarm_level->alarm_threshold.'%';
                        $trigger[$level][$compteur][4] = round($c->value, 2);
                        $ad = explode('@', $c->additional_details);
                        // Mantis 4178 : split additional details into two columns
                        $trigger[$level][$compteur][11] = round($ad[0],2);
                        $trigger[$level][$compteur][12] = round($ad[1],2);

                    }

                    // 15/09/2010 BBX
                    // Plus de else ici. On vérifie simplement si on a un trigger.
                    // BZ 17675
                    if(!empty($alarm_level->alarm_trigger_data_field)) {
                        if ( array_key_exists($alarm_level->alarm_trigger_data_field, $listKpi) )
                            $trigger[$level][$compteur][8] = $listKpi[$alarm_level->alarm_trigger_data_field];
                        else
                            $trigger[$level][$compteur][8] = $listRaw[$alarm_level->alarm_trigger_data_field];
                        $trigger[$level][$compteur][9] = $alarm_level->alarm_trigger_operand.' '.$alarm_level->alarm_trigger_value;
                        $trigger[$level][$compteur][10] = round($c->value, 2);
                    }
		}
		else {
			if ( array_key_exists($alarm_level->alarm_field, $listKpi) )
				$trigger[$level][$compteur][2] = $listKpi[$alarm_level->alarm_field];
			else
				$trigger[$level][$compteur][2] = $listRaw[$alarm_level->alarm_field];
			$alarm_threshold_operand_str = '';
			if ($canManageThresholdOperand) {
				$alarm_threshold_operand_str = 
					($alarm_level->alarm_threshold_operand != 'both' && $alarm_level->alarm_threshold_operand != '')?
					$alarm_level->alarm_threshold_operand . "<br/>":"";
			}
			$trigger[$level][$compteur][3] = $alarm_threshold_operand_str . ' > '.$alarm_level->alarm_threshold.'%';
			$trigger[$level][$compteur][4] = '';
			$trigger[$level][$compteur][11] = '';
			if ( array_key_exists($alarm_level->alarm_trigger_data_field, $listKpi) )
				$trigger[$level][$compteur][8] = $listKpi[$alarm_level->alarm_trigger_data_field];
			else
				$trigger[$level][$compteur][8] = $listRaw[$alarm_level->alarm_trigger_data_field];
			$trigger[$level][$compteur][9] = $alarm_level->alarm_trigger_operand.' '.$alarm_level->alarm_trigger_value;
			$trigger[$level][$compteur][10] = '';
		}
	}
	elseif ( $edw_alarm_type == 'alarm_top_worst' )	// *****  ALARME TOP/WORST LIST ****** //
	{
		// 21/04/2008 - Modif. benoit : correction du bug 6463. Pour les resultats top-worst, on ne conserve pas les lignes de type "%_1" crées par la boucle de correction de l'iteration

		if (!(strpos($c->id_result, '_1') === false))
		{
			unset($trigger[$level][$compteur]);
		}
		else
		{
			if ( array_key_exists($alarm_level->list_sort_field, $listKpi) ){
				$trigger[$level][$compteur][5] = $listKpi[$alarm_level->list_sort_field];
			}
			else
			{
				$trigger[$level][$compteur][5] = $listRaw[$alarm_level->list_sort_field];
			}
			$trigger[$level][$compteur][6] = $alarm_level->list_sort_asc_desc;

			// 21/04/2008 - Modif. benoit : correction du bug 6325. Ajout des conditions sur la présence de l'opérande et de sa valeur

			if ($hasResults === true && $c->trigger_operand != '' && $c->trigger_value != '')
			{
				// 21/04/2008 - Modif. benoit : correction du bug 6463. Suppression de la colonne 7 du tableau de résultats top-worst. Cette colonne indiquant la valeur du trigger est reporté en colonne 10

				//$trigger[$level][$compteur][7] = ( empty($c->value) ? '' : round($c->value, 2) );

				if (array_key_exists($c->trigger, $listKpi) ){
					$trigger[$level][$compteur][8] = $listKpi[$c->trigger];
				}
				else
				{
					$trigger[$level][$compteur][8] = $listRaw[$c->trigger];
				}

				$trigger[$level][$compteur][9] = $c->trigger_operand.' '.$c->trigger_value;
				$trigger[$level][$compteur][10] = round($c->value, 2);
			}
			else
			{
				// 21/04/2008 - Modif. benoit : correction du bug 6463. Suppression de la colonne 7 du tableau de résultats top-worst. Cette colonne indiquant la valeur du trigger est reporté en colonne 10

				//$trigger[$level][$compteur][7] = '';

				$trigger[$level][$compteur][8] = '';
				$trigger[$level][$compteur][9] = '';
				// maj 26/05/2008 Benjamin : La valeur de l'indicateur du "Sort condition" doit être indiquée même s'il n'y a pas de trigger. BZ6667
				//$trigger[$level][$compteur][10] = '';
				$trigger[$level][$compteur][10] = round($c->value, 2);
			}
		}


	}
	else // *****  ALARME STATIQUE ****** //
	{
		// Label du raw / kpi

		// 18/04/2008 - Modif. benoit : correction du bug 6325. Remplacement de '$alarm_level->alarm_trigger_data_field' par '$c->trigger'

		if ( array_key_exists($c->trigger, $listKpi) ){	// kpi
			$trigger[$level][$compteur][8] = $listKpi[$c->trigger];
		}
		else // raw
		{
			$trigger[$level][$compteur][8] = $listRaw[$c->trigger];
		}

		/*if ( array_key_exists($alarm_level->alarm_trigger_data_field, $listKpi) ){	// kpi
			$trigger[$level][$compteur][8] = $listKpi[$alarm_level->alarm_trigger_data_field];
		}
		else // raw
		{
			$trigger[$level][$compteur][8] = $listRaw[$alarm_level->alarm_trigger_data_field];
		}*/

		// Condition (operande + valeur de déclenchement)

		// 09/06/2008 - Modif. benoit : correction du bug 6800. Les informations sur les conditions du trigger proviennent maintenant du tableau '$alarmInfoByCriticity'

		//$trigger[$level][$compteur][9] = $alarm_level->alarm_trigger_operand.' '.$alarm_level->alarm_trigger_value;

		$alarm_trigger_condition = $alarmInfoByCriticity[$c->critical_level][$c->trigger];

		//$trigger[$level][$compteur][9] = $alarm_trigger_condition['operand'].' '.$alarm_trigger_condition['value'];
                // 12/04/2011 BBX
                // Utilisation de $c au lieu du tableau $alarm_trigger_condition
                // BZ 21540
                $trigger[$level][$compteur][9] = $c->trigger_operand.' '.$c->trigger_value;

		// Valeur ayant déclenchée l'alarme

		if ( $hasResults === true ){
			$trigger[$level][$compteur][10] = round($c->value, 2);
		}
		else
		{
			$trigger[$level][$compteur][10] = '';
		}
	}

	if ( $hasResults === true ){
		$prevIDResult[$c->id_result] = $compteur;
	}

	// 21/04/2008 - Modif. benoit : correction du bug 6463. Ajout de la vérification sur l'existence de '$trigger[$level][$compteur]' (consécutif au 'unset()' sur les id_result de type "%_1"

	if (isset($trigger[$level][$compteur])) {
		ksort($trigger[$level][$compteur]);
	}
	$iter->next();
	$compteur++;
}

//__debug($trigger, "trigger");

/******************************************************************************/
// Champs additionnels
$compteur = 0;
$iter = $additionalDisplay->getIterator();
$additionalDetails = array();
while ( $iter->valid() ) {
	$c = $iter->current();

	if ( $isAlarmIterative === true )
		$additionalDetails[$compteur]['date'] = getTaValueToDisplay($c->ta, $c->ta_value);

	if ( array_key_exists($c->trigger, $listKpi) )
		$additionalDetails[$compteur]['field'] = $listKpi[$c->trigger];
	else
		$additionalDetails[$compteur]['field'] = $listRaw[$c->trigger];

	$additionalDetails[$compteur]['value'] = round($c->value, 2);

	$iter->next();
	$compteur++;
}

/******************************************************************************/
/******************************************************************************/
//***************************************************************************//

// Création du tableau html pour l'affichage
$tableauHTMLDisplay = '<table class="texteGris" style="text-align:center; color:black" cellpadding=3 cellspacing=1 border=0><tr>';
ksort($trigger['header']);
foreach ( $trigger['header'] as $line ) {
	$tableauHTMLDisplay .= '<td class="entete">'.$line.'</td>';
}
$tableauHTMLDisplay .= '</tr><tbody style="background-color:'.$color_critical_level[$resultCurrent->critical_level].'">';

foreach ( $trigger[$resultCurrent->critical_level] as $lines ) {
	$tableauHTMLDisplay .= '<tr><td>'.implode('</td><td>', $lines).'</td></tr>';
}
$tableauHTMLDisplay .= '</tbody></table>';

/******************************************************************************/
// Création d'un tableau HTML pour les champs additionnels

$tableauHTMLField = '';
if ( sizeof($additionalDetails) > 0  ) {
	$tableauHTMLField = '<table class="texteGris" style="text-align:center" cellpadding=3 cellspacing=1 border=0><tr>';
	if ( $isAlarmIterative === true )
		$tableauHTMLField .= '<td class="entete">Date</td>';
	$tableauHTMLField .= '<td class="entete">Additional field</td><td class="entete">Value</td></tr><tbody style="background-color:#f0f0f0">';
	foreach ( $additionalDetails as $values ) {
		$tableauHTMLField .= '<tr><td>'.implode('</td><td>',$values).'</td></tr>';
	}
	$tableauHTMLField .= '</tbody></table>';
}
else {
	$tableauHTMLField = '<span class="texteRougeBold">No additional field defined for this alarm</span>';
}

/******************************************************************************/
/******************************************************************************/
// Création d'un tableau HTML du résultat de l'alarme

switch ( $edw_alarm_type ) {
	case 'alarm_static'   : $alarm_type = 'static'; break;
	case 'alarm_dynamic'  : $alarm_type = 'dynamic'; break;
	case 'alarm_top_worst': $alarm_type = 'top/worst cell list'; break;
}

$headerDyn = '';
$tbodyDyn = '';
if ( $edw_alarm_type == 'alarm_dynamic' ) {
	//22/10/2014 - FGD - BZ 39414 - Simple quotes replaced by double quotes (The HTML is inserted in the database without any protection...)
	$headerDyn = '<td class="entete">Addtional details</td><td class="entete">Discontinuous</td>';
	$tbodyDyn = '<td>'.$trigger[$resultCurrent->critical_level][1][11].'</td><td>'.$alarmCurrent->offsetGet($resultCurrent->critical_level)->discontinuous.'</td>';
}

$headerAxe3 = '';
$tbodyAxe3 = '';
if ( $infoDisplay->axe3 == true) {
	$headerAxe3 = '<td class="entete">Third Axis</td>';
	$tbodyAxe3 = '<td>'.$infoDisplay->info_na_axe3.'</td>';
}

// modif 10/01/2008 Gwénaël
// ajout d'un lien dans le pdf
// 09/12/2011 ACS Mantis 837 DE HTTPS support
// 21/12/2011 ACS BZ 25228 no access to related slave dashboard
$externalLink = ProductModel::getCompleteUrlForMasterGui('?alarm_type='.$infoDisplay->alarm_type.'&alarm_id='.$infoDisplay->id_alarm.'&ta='.$infoDisplay->ta.'&ta_value='.$infoDisplay->ta_value.'&product='.$product);
if ( $infoDisplay->axe3 ) {
	// 17:36 31/08/2009 GHX
	// Correction du BZ 11314
	$externalLink .= '&na='.$infoDisplay->na.'&na_value='.$infoDisplay->na_value;
	$externalLink .= '&na_axe3='.$infoDisplay->a3.'&na_axe3_value='.$infoDisplay->a3_value;
}
else {
	$externalLink .= '&na='.$infoDisplay->na.'&na_value='.$infoDisplay->na_value;
}
$info_na = '<a href='.$externalLink.'>'.$infoDisplay->info_na.'</a>';
$tableauHTML = <<< EOF
<table class="texteGris" style="text-align:center" cellpadding=3 cellspacing=1 border=0>
	<tr>
		<td class="entete">Alarm name</td>
		<td class="entete">Family</td>
		<td class="entete">Network level</td>
		<td class="entete">Time Resolution</td>
		{$headerDyn}
		{$headerAxe3}
	</tr>
	<tr style="background-color:#f0f0f0">
		<td>{$infoDisplay->alarm_name} [{$alarm_type}]</td>
		<td>{$family_label}</td>
		<td>{$info_na}</td>
		<td>{$infoDisplay->info_ta}</td>
		{$tbodyDyn}
		{$tbodyAxe3}
	</tr>
</table>
EOF;

$tableauHTML .= '<table class="texteGris" style="text-align:center" cellpadding=3 cellspacing=1 border=0><tr>';
foreach ( $trigger['header'] as $line ) {
	$tableauHTML .= '<td td class="entete">'.$line.'</td>';
}
$tableauHTML .= '<tr>';

foreach ( $trigger as $critical_level => $lines ) {
	if ( $critical_level == 'header')
		continue;
	$tableauHTML .= '<tbody style="background-color:'.$color_critical_level[$critical_level].'">';
	foreach ( $lines as $line ) {
		$tableauHTML .= '<tr><td>'.implode('</td><td>', $line).'</td></tr>';
	}
	$tableauHTML .= '</tbody>';
}
$tableauHTML .= '</table>';

//modif 06/05/2007 Gwénaël
	//modification du titre du pdf pour qu'apparaisse le troisième axe
$monTitre = $infoDisplay->alarm_name.'[ '.$alarm_type.','.$family_label.']      on '.$infoDisplay->info_na.' '.($infoDisplay->axe3 ? " // " . $infoDisplay->info_na_axe3 : '').'   -   '.$infoDisplay->info_ta;

$_SESSION['alarm_to_panier'] = addslashes($tableauHTML.$tableauHTMLField);
if ($debug)
	echo "<br><br>$monTitre<br>$tableauHTML";

/******************************************************************************/
/******************************************************************************/
// AFFICHAGE DES DONNÉES

// Vérification pour savoir si l'alarme à déjà été ajouté au caddy ...
$query_existe = "SELECT object_page_from FROM sys_panier_mgt WHERE object_page_from = '$oid_alarm' and id_user='$id_user' ";
$result_existe = $db->execute($query_existe);
$disabled = '';
if ( $db->getNumRows() > 0 ) // ... si oui on désactive le bouton
	$disabled = ' disabled';
?>
<html>
	<head>
	<title>Alarm detail</title>
	<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css"/>
</head>
<body bgcolor="#fefefe">
<table width=100%>
	<tr valign=center align=center>
		<td align="center" valign="center">

			<fieldset>
				<legend class="texteGrisBoldPetit">&nbsp;General information&nbsp;&nbsp;</legend>
					<div class="texteGris" style="text-align:left; padding-top:5px; float:left; width:47%"><u>Family :</u> <?php echo $family_label; ?></div>
					<div class="texteGris" style="text-align:left; padding-top:5px; float:left; width:25%">
						<?php
						if ($edw_alarm_type != 'alarm_top_worst')
						{
							echo "<u>Critical level :</u> ".$infoDisplay->critical_level;
						}
						else
						{
							echo "<u>Sort by :</u> ".$infoDisplay->sort_by;
						}
						?>
					</div>
					<div class="texteGris" style="text-align:left; padding-top:10px; float:left; width:47%"><u>Source time :</u> <?php echo $infoDisplay->info_ta; ?></div>
					<div class="texteGris" style="text-align:left; padding-top:10px; float:right; width:53%"><u>Network aggregation :</u> <?php echo $infoDisplay->info_na; ?></div>
					<?php
						if ( $infoDisplay->axe3 ){

							// 16/04/2008 - Modif. benoit : correction du bug 6324. La variable utilisée pour indiquer le label du 3ème axe n'était pas la bonne ('$axe_information' au lieu de '$infoDisplay->axe_information')

							echo '<div class="texteGris" style="text-align:left; padding-top:10px; float:right; width:53%"><u>'.$infoDisplay->axe_information['axe_type_label'][0].':</u> '.$infoDisplay->info_na_axe3.'</div>';
						}
					?>
			</fieldset>
			<div class="texteGris" style="text-align:left; padding-top:10px; float:right; width:28%"><input type='button' class='bouton' value='Add to the Cart'<?php echo $disabled; ?> onclick="document.getElementById('panier').click();this.disabled=true"></div>
			<div class="texteGris"  style="text-align:left;padding-top:10px">
				<h1><img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif">&nbsp;
					<?php
					if ( $edw_alarm_type == 'alarm_dynamic' )
						echo "Threshold / ";
					elseif ( $edw_alarm_type == 'alarm_top_worst' )
						echo "Sort By / ";
					?>
					Trigger
				</h1>
			</div>
			<div><hr></div>
			<?php echo $tableauHTMLDisplay; ?>
			<div class="texteGris" style="text-align:left;padding-top:10px">
				<h1><img src="<?php echo $niveau0; ?>images/icones/small_puce_fieldset.gif">&nbsp;Additional fields</h1>
			</div>
			<div><hr></div>
			<div style='text-align:center'><?php echo $tableauHTMLField; ?></div>
			<?php
			/*
				13:47 17/07/2009 GHX
				- Correction d'un bug JS ajout de l'id user entre cote
			*/
			?>
			<input type='button' id='panier' style='display:none' onclick="alarm_to_panier('<?=$id_user?>','<?=urlencode(addslashes($monTitre))?>',<?=$oid_alarm?>);">
		</td>
	</tr>
</table>
</body>
</html>