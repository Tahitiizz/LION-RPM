<?
/*
 * @cb 5.1.6.23
 * 
 * 19/06/2012 NSE bz 27674 : lenteur du check all
 */
?>
<?
/*
 *      @cb50406
 *
 *  30/09/2010 NSE bz 18165 : ne pas afficher les cellules virtuelles.
 *	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 *
 */
?><?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 04/09/2007 christophe : quaand il y a 0 élément, on ajout le champ caché d'id 'no_element' qui est utilisé dans la sélection des NA depuis l'interface d'édition des alarmes,
*	 cela permet de ne pas afficher le message d'erreur 'you must select at least one Network element' si il n'y a pas d'éléments dans la topo. A ce moment là all est enregistré
*	par défaut en base.
*	- 13/08/2007 christophe : ajout d'une condition check_all != 'no' car quand on fait un uncheck, la variable $_SESSION["network_element_preferences"] est vide
*	- 23/07/2007 christophe : par défaut, l'icône de sélection des éléments réseaux enfantn'est pas affichés,
			elle l'est seulement si l'élément réseau est coché.
*	- 16/07/2007 christophe : ajout d'un paramètre $selectChild qui détermine si les éléments réseaux 'enfants' doivent être sélectionnés.
*	- 11/07/2007 christophe : gestion du nouveau paramètre 'check_all' qui permet de cocher / décocher tous les éléments réseaux du niveau d'agrégation passé en paramètre
*	- 10/07/2007 christophe :
*	> si on est dans l'interface d'édition des alarmes, on stocke tous les éléments réseaux dans un tableaux de session.
*	> si on est dans l'interface d'édition des alarmes et si aucun élément réseau n'est sélectionné, au sélection tous les éléments réseaux.
*	- 21/06/2007 christophe : la liste des éléments réseaux sélecionnés dépend de  $_SESSION["network_element_preferences"]
*	et ne dépend plus $_SESSION["selecteur_general_values"]["list_of_na"]
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
*	- 13/06/2007 christophe : on limite les labels des éléments réseaux en fonction du param de sys_global_parameters
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*
*	- 27 02 2007 christophe : correction bug FS 510, quand 2 na de niveaux différents avaient le même identifiant
*	la sélection entraînait la sélection de l'autre.
*
*	- 28 02 2007 christophe : ajout d'un htmlentities pour que les caractères spéciaux s'affichent correctement.
*
*
*/
?>
<?php
/*
*	@cb21001_gsm20010@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.01
*
*	Parser version gsm_20010
*
*	- maj 27/12/2006 maxime : affichage des NA minimum d'une famille uniquement si elles sont actives
*
*
* 24-11-2006 - GH : Limitation du nombre de résultat à 20 000 éléments car sinon l'affichage est très long voire bloque IE
*
* @cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?php
/*
	Retourne la liste des network aggregation value d'une NA 'mère'  en fonction :
		d'une na
		d'une famille
*/

session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");

global $niveau0;

$na				= $_GET['na'];
$product			= $_GET['product'];
$family			= $_GET['family'];
$modeNaSelection	= $_GET['modeNaSelection'];

$debug = false;
if ($debug) {
	echo "\n<div class='debug'><table><tr><th colspan='2'>contenu de \$_GET</th></tr>";
	foreach ($_GET as $key => $val) {
		echo "\n<tr><td>$key</td><td>$val</td></tr>";
	}
	echo "\n</table></div>";
}

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection($product);

// 16/07/2007 christophe : ajout d'un paramètre $selectChild qui détermine si les éléments réseaux 'enfants' doivent être sélectionnés.
$selectChild		= $_GET["selectChild"];

// 11/07/2007 christophe : gestion du nouveau paramètre 'check_all' qui permet de cocher / décocher tous les éléments réseaux du niveau d'agrégation passé en paramètre
$check_all		= $_GET["check_all"];

$na_label		= $na . "_label";
$table_query	= get_object_ref_from_family($family,$product); // cf edw_function_family
$na_min		= get_network_aggregation_min_from_family($family,$product);

// 10/07/2007 christophe : si on fait l'édition des alarmes on stocke dans un tableau de session la liste de tous les éléments réseaux listés, on vide la variable
if ( $modeNaSelection == 'interface_edition_alarme' ) {
	$_SESSION['network_element_list'] = '';
	// 13/08/2007 christophe : ajout d'une condition check_all != 'no' car quand on fait un uncheck, la variable $_SESSION["network_element_preferences"] est vide
	if ( empty($_SESSION["network_element_preferences"]) && $check_all != 'no' )
		$check_all = 'yes';
}

// Condition ajoutée dans le where de la requete qui récupère la liste des NA
$query_case_et_condition_dashboard_na_parents = '';
$query_condition_dashboard_na_parents = '';

/*
	22/06/2007 christophe
	On récupère tous les niveaux d'agrégation > au niveau courant $na.
	>> $_SESSION["na_listed_in_na_selection"] : initialisé dans genDashboard_get_liste_na_mere_ajax.;php
	1. on parcourt ce tableau pour connaître tous les NA.
	2. on construit la partie where de la requête pour ajouter les éléments réseaux par rapport à ce qui est en session.
*/
$tab_na_listed_in_na_selection = array();
if (isset($_SESSION["na_listed_in_na_selection"]))
	foreach ($_SESSION["na_listed_in_na_selection"] as $elem)
		$tab_na_listed_in_na_selection[$elem['value']] = $elem['value'];
	


/*
	On construit un tableau contenant la liste des na à partir du tableau de session.
	- 21/06/2007 christophe : la liste des éléments réseaux sélecionnés dépend de  $_SESSION["network_element_preferences"]
	et ne dépend plus $_SESSION["selecteur_general_values"]["list_of_na"]
*/
$tab_na = "";
$tab_na_select_child = ""; // 23/07/2007 tableau stockant la liste des éléments réseaux dont on doit afficher les éléments enfants.
	
if (isset($_SESSION["network_element_preferences"]))  {
	$liste = explode('|', $_SESSION["network_element_preferences"]);
	foreach($liste as $elem) {
		$na_value_svg = explode('@', $elem);
		/*
			Le tableau est de la forme
			$tab_na[na_value] = na_value
			On remplit le tableau seulement avec les  na_value correspondants aux na sélectionnées
			pour la NA courante $na
			le format de la chaine est : na@na_value@na_label
		*/
		if ( $na_value_svg[0] == $na )
			$tab_na[$na_value_svg[1]] = $na_value_svg[1];
			
		/*
			23/07/2007 christophe :
			si il existe un 4ème paramètre et qu'il est égal à 1 on doit sélectionner les éléments réseaux enfants
			de cet élément réseau.
			format de la chaine quand on sélectionne des éléments réseaux enfants :
			na@na_value@na_label@ 0 ou 1
		*/
		if (isset($na_value_svg[3]))
			if ($na_value_svg[3] == 1)
				$tab_na_select_child[$na_value_svg[1]] = $na_value_svg[1];
		
		/*
			22/06/2007 christophe
			Si des NA parentes ont été sélectionnées, on construit la condition
		*/
		// Sélection des éléments réseaux 'enfants' si un élément réseau 'parent' a été sélectionné.	
		if ($modeNaSelection != 'interface_edition' &&  $modeNaSelection != 'interface_edition_alarme') {
			if (array_key_exists($na_value_svg[0], $tab_na_listed_in_na_selection))	{
				if (empty($query_condition_dashboard_na_parents)) {
					$query_condition_dashboard_na_parents .= ' '.$na_value_svg[0].'=\''.$na_value_svg[1].'\' ';
				} else {
					$query_condition_dashboard_na_parents .= ' OR '.$na_value_svg[0].'=\''.$na_value_svg[1].'\' ';
				}
			}
		}
	}
}


// 22/06/2007 christophe
if (empty($query_condition_dashboard_na_parents)) {
	$query_case_et_condition_dashboard_na_parents = ' 0 AS is_checked '; // aucun éléments sélectionné à partir d'un parent
} else {
	$query_case_et_condition_dashboard_na_parents = " CASE WHEN ($query_condition_dashboard_na_parents) THEN 1 ELSE 0 END AS is_checked ";
}
// 30/09/2010 NSE bz 18165 : ne pas afficher les cellules virtuelles.
// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
$query_liste = "
	SELECT eor_id as value,
		CASE WHEN eor_label IS NULL OR eor_label = ''
			THEN '(' || eor_id || ')'
			ELSE eor_label
		END as label,
		$query_case_et_condition_dashboard_na_parents
	FROM edw_object_ref
	WHERE eor_obj_type ='$na'
		AND eor_on_off = 1
        AND ".NeModel::whereClauseWithoutVirtual()."
	ORDER BY label ASC
";
$result = $db->getall($query_liste);
$nombre_resultat = count($result);

// 13/06/2007 christophe : on limite les labels des éléments réseaux en fonction du param de sys_global_parameters
$na_label_character_max = get_sys_global_parameters('na_label_character_max',30,$product);

$resultats = "";

/*
	10/07/2007 christophe
	si on est dans l'édition des alarmes, on affiche check/uncheckall
*/
if ($modeNaSelection == 'interface_edition_alarme') {
	$resultats .= "
		<div class='texteGrisPetit' style='padding:3px;'>
			<span style='cursor:pointer' onclick='chargerContenu(\"$na\",\"$family\",\"yes\",\"$product\")'>".__T('U_NA_SELECTION_LABEL_CHECK_ALL')."</span>
			-  
			<span style='cursor:pointer' onclick='chargerContenu(\"$na\",\"$family\",\"no\",\"$product\")'>".__T('U_NA_SELECTION_LABEL_UNCHECK_ALL')."</span>
		</div>
	";
}

if ($nombre_resultat > 0 and $nombre_resultat < 20000) {
	foreach ($result as $row) {
		$value = $row["value"];
		$label = $row["label"];

		// 13/06/2007 christophe : on limite les labels des éléments réseaux en fonction du param de sys_global_parameters
		if (strlen($label) > $na_label_character_max)
			$label = substr($label, 0, $na_label_character_max)."...";

		$checked = "";
		// 22/06/2007 christophe - les enfants des NA parentes sélectionnés sont cochés et désactivées.
		if ( $row['is_checked'] && $_SESSION["selecteur_general_values"]["list_of_na_mode"] == 'dashboard_normal')
			$checked = " checked disabled ";
		
		if ($tab_na != "")
			if (isset($tab_na[$value]))
				$checked = " checked ";
		
		/*
			10/07/2007 christophe
			Si on est dans l'édition des alarmes et qu'il n'y a aucun élément réseau sélectionné,
			on coche tout par défaut et on stocke l'ensemble des éléments réseaux dans un tableau de session supplémentaire
			pour gérer la sauvegarde.
		*/
		if ($modeNaSelection == 'interface_edition_alarme')
			// on stocke la liste des éléments réseaux pour gérer la sauvegarde dans les alarmes.
			$_SESSION['network_element_list'][$value] = $value;
		
		if ($check_all == 'yes' && $modeNaSelection == 'interface_edition_alarme') {
			// on sélectionne tous les éléments réseaux.
			$checked = " checked ";
			
			if (empty($_SESSION["network_element_preferences"]))
				$_SESSION["network_element_preferences"] = $na."@".$value."@".$label;
			else
				$_SESSION["network_element_preferences"] .= "|".$na."@".$value."@".$label;
                        
                       // 19/06/2012 NSE bz 27674 : modification des variables de session utilisée en 5.1
                       // On l'ajoute à la sélection réelle
                       if(!in_array($value,(array)$_SESSION['alarmsSessionArray']['ne_selection'][$na]))
                           $_SESSION['alarmsSessionArray']['ne_selection'][$na][] = $value;
                       // On l'ajoute à la sélection courante
                       if(!in_array($value,(array)$_SESSION['alarmsSessionArray']['current_selection'][$na]))
                           $_SESSION['alarmsSessionArray']['current_selection'][$na][] = $value;
                        
                        
                        $ensession .= "|".$na;
		} else if ($check_all == 'no' && $modeNaSelection == 'interface_edition_alarme') {
			$checked = "  ";
		}
		
		
		
		/*
			16/07/2007 christophe : si $selectChild=true, on affiche l'icône de sélection des éléments réseaux 'enfants'.
			NB : si on affiche la liste des éléments réseaux du niveau minimum, on n'affiche pas l'icône de sélection des éléments 'enfants'.
			23/07/2007 christophe : par défaut, l'icône de sélection des éléments réseaux enfantn'est pas affichés,
			elle l'est seulement si l'élément réseau est coché.
		*/
		$icone_selectChild = '';
		if ($selectChild && $na != $na_min) {
			// image à afficher
			$img_select_child = 'select_child.png';
			// Par défaut l'image n'est pas affichée
			$display = 'none';
			// Message tooltip
			$alt = __T('U_MSG_ROLLOVER_ICON_SELECT_NA_CHILD_ALARM');
				
			// L'élément réseau courant : ses éléments réseaux enfants doivent être affiché, on affiche donc l'icône adéquate.
			if ($tab_na_select_child[$value] && !empty($checked))
				$img_select_child = 'unselect_child.png';

			if ( !empty($checked) )
				$display = 'block';
			
			$icone_selectChild = " 
				&nbsp; 
				<img  id='" . $value . "_" . $na . "_imgSelectChild' 
				src='".$niveau0."images/icones/".$img_select_child."' style='cursor:pointer' 
				onclick=\"selectChildren('$na','$value','".addslashes (htmlentities ($label))."')\"
				alt='".$alt."'
				style='position:absolute; padding-top:5px; display:".$display.";'
				/>
			";
		}
		// 19/06/2012 NSE bz 27674 : utlisation de manageAutomaticSelection() au lieu de SaveInSession()
		$resultats .= "
			<div>
				<input id='" . $value . "_" . $na . "' type='checkbox' $checked onclick='manageAutomaticSelection(this,\"$na\",\"$value\")'/>
				<label for='" . $value . "_" . $na . "'>" . htmlentities($label) . $icone_selectChild . "</label>
			</div>
			";
	}
	
	// 11/07/2007 chistophe : si uncheck, on vide  la variable de session.
	if ( $check_all == 'no' && $modeNaSelection == 'interface_edition_alarme' ){
		$_SESSION["network_element_preferences"] = $_SESSION["selecteur_general_values"]["list_of_na"] = '';
                // 19/06/2012 NSE bz 27674 : on vide les variables de session
               unset($_SESSION['alarmsSessionArray']['ne_selection'][$na]);
               unset($_SESSION['alarmsSessionArray']['current_selection'][$na]);
        }
	/*
		10/07/2007 christophe
		si on est dans l'interface d'édition des alarmes, et que c'est une nouvelle alarme, on sélectionne tous les éléments réseaux.
	*/
	if ($modeNaSelection == 'interface_edition_alarme' && $check_all == 'yes')
		$_SESSION["selecteur_general_values"]["list_of_na"] = $_SESSION["network_element_preferences"];
	
} elseif ($nombre_resultat > 20000) {
    $resultats .= "Too many elements ($nombre_resultat)";
} else {
    $resultats .= "No element";
	// 04/09/2007 christophe : quaand il y a 0 élément, on ajout le champ caché d'id 'no_element' qui est utilisé dans la sélection des NA depuis l'interface d'édition des alarmes,
	// cela permet de ne pas afficher le message d'erreur 'you must select at least one Network element' si il n'y a pas d'éléments dans la topo. A ce moment là all est enregistré
	// par défaut en base.
	$resultats .= "<input type='hidden' id='no_element' value='no_element' />";
}

echo $resultats;

?>
