<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- 28/03/2008 christophe : répercution de la correction du bug 5667 corrigé dans le CB 3.0.1.04 / 3.0.1.14
*		> - maj 04/01/2008 christophe : correction du bug 5667, il manque des NA dans la liste dans alarm managmeent,history et my profile.
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 24/08/2007 christophe : on récupère le mode de sélection modeNaSelection.
*	- 13/08/2007 christophe : on ne remet la sélection des NA à zéro que quand on change de NA sur la même alarme, pas quand on change d'alarme.
*	- 10/07/2007 christophe : gestion de l'affichage d'une seule NA > pour l'interface des alarmes.
*	- 28/06/2007 christophe : gestion de l'affichage de toutes les NA de toutes les familles.
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
*	- 05/04/2007 christophe : modification de la requête qui vat chercher les NA afin de ne retourner
*	que les NA <> axe = 3.
*
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
		Permet de retourner la liste des Na mère à afficher pour
		la sélection des network aggregation dans le sélecteur.
		Ce fichier est seulement utilisé quand le mode de la classe genDashboardNaSelection.class.php
		est 'dashboard_normal'.
		- maj 22 11 2006 christophe : dans le fichier selecteur.class.php on stocke dans la variable de session $_SESSION['liste_na_sys_selecteur_properties']
			la liste des NA affichées dans la balise select du sélecteur.
			Sa structure est de la forme $_SESSION['liste_na_sys_selecteur_properties'][i]['value']
			$_SESSION['liste_na_sys_selecteur_properties'][i]['label']...
			En fait cette liste permet d'afficher la liste des NA à afficher dans le choix des NA dans le bon ordre.
			On fait un intersect entre ce tableau de session et la liste des NA que l'on doit afficher.
			Cette manipulation sert seulement à afficher les NA dans le même ordre que celui du sélecteur.

	*/

session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");

global $niveau0;

$product				= $_GET['product'];
$family				= $_GET['family'];
$network_aggregation	= $_GET['na'];
// 24/08/2007 christophe : on récupère le mode de sélection modeNaSelection.
$modeNaSelection		= $_GET['modeNaSelection'];

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection($product);



// mise en commentaire le 28/06 car pas utilisé
/*
if(!isset($_SESSION["na_courante_selecteur"])){
	$_SESSION["na_courante_selecteur"] = $network_aggregation;
}
if(!isset($_SESSION["famille_courante_selecteur"])){
	$_SESSION["famille_courante_selecteur"] = $family;
}
*/

/*
	FONCTIONS
*/
function parcoure_arbre($id_ligne)
{
	global $liste_agregation, $arbre,$array_result;
	$array_result[$id_ligne]["value"]	= $liste_agregation[$id_ligne]["value"];
	$array_result[$id_ligne]["label"]	= $liste_agregation[$id_ligne]["label"];
	if (count($arbre[$id_ligne]) > 0)
		foreach ($arbre[$id_ligne] as $cible)
			parcoure_arbre($cible);
}


/**
* On enlève tous les éléments de array1 ne se trouvant pas dans array2
*/
function array_intersect_advance($array1,$array2)
{
	$array_return = Array();
	$i = 0;
	foreach ($array1 as $row1)
		foreach ($array2 as $row2)
			if ($row1['value'] == $row2['value'])	{
				$array_return[$i]['value'] = $row1['value'];
				$array_return[$i]['label'] = $row1['label'];
				$i++;
			}

	return $array_return;
}
/*
	FIN FONCTIONS
*/


/*
	10/07/2007 christophe : gestion de l'affichage d'une seule NA > pour l'interface des alarmes.
	Si la sélection des NA est utilisée dans l'interfec de création des alarmes, on n'affiche qu'un seul niveau d'agrégation
	dans la liste des NA.
*/
if ( $_SESSION["selecteur_general_values"]["list_of_na_mode"] == 'interface_edition_alarme' )
{
	if ( !isset($_SESSION["network_element_preferences_preceding_id_alarm"]) )
		$_SESSION["network_element_preferences_preceding_id_alarm"] = '';

	$liste_na_temp = getNaLabelList('na',$family,$product);
	$array_tri = Array();
	$array_tri[0]['value']	= $network_aggregation;
	$array_tri[0]['label']		= $liste_na_temp[$family][$network_aggregation];
	$_SESSION["na_listed_in_na_selection"] = $array_tri;

	$raz_session = false;

	if ( isset ($_SESSION['na_precedente_na_selection']) )
		if ( ($_SESSION['na_precedente_na_selection'] != $network_aggregation && $_SESSION['na_precedente_na_selection'] != '')
				&& ($_SESSION["network_element_preferences_preceding_id_alarm"]==$_SESSION["network_element_preferences_current_id_alarm"]))
			$raz_session = true;

	$_SESSION['na_precedente_na_selection'] = $network_aggregation;

	// 13/08/2007 christophe : on ne remet la sélection des NA à zéro que quand on change de NA sur la même alarme, pas quand on change d'alarme.
	$_SESSION["network_element_preferences_preceding_id_alarm"] = $_SESSION["network_element_preferences_current_id_alarm"];

	if ( $raz_session )
	{
		$_SESSION["selecteur_general_values"]["list_of_na"] = '';
		$_SESSION["network_element_preferences"] = '';
	}
}
else
{
	/*
		28/06/2007 christophe : gestion de l'affichage de toutes les NA de toutes les familles.
	*/
	if ( $family != 'all' )
	{
		$query = "
			SELECT DISTINCT ON (b.agregation) b.agregation, a.id_ligne,a.rank , b.agregation_label as label
			FROM sys_definition_group_table_network a , sys_definition_network_agregation b
			WHERE data_type = 'raw'
				AND id_group_table = (SELECT id_ligne FROM sys_definition_group_table WHERE family='$family' LIMIT 1)
				AND a.network_agregation LIKE '%'||b.agregation||'%'
				AND b.axe IS NULL
				AND b.family = '$family'
			ORDER BY b.agregation, a.rank
			";
		$res = $db->getall($query);
		if ($res) {
			foreach ($res as $row) {
				$liste_agregation[$row["id_ligne"]]["value"]	= $row["agregation"];
				$liste_agregation[$row["id_ligne"]]["label"]	= $row["label"];
				if ($row["agregation"] == $network_aggregation)
					$id_ligne_selected = $row["id_ligne"]; //Selectionne l'id_ligne qui correspond au niveau d'agregation selectionné
				$arbre[$row["rank"]][] = $row["id_ligne"];
			}
		}
	}
	else
	{
		/*
			28/06/2007 christophe
			Etape 1 :
			- on récupère la famille principale
			- on récupère toutes les NA de la famille principale.
			- on range ces NA dans le tableau $arbre.
			- on conserve la liste des NA de la fmaille principale dans une chaine pour les exclures dans l'étape 2
		*/
		$famille_principale = get_main_family($product); // cf edw_function_family.php
		$network_aggregation_min_famille_principale = get_network_aggregation_min_from_family($famille_principale,$product);
		$liste_na_famille_principale = ''; // On stocke les NA de la famille principale.
		$tab_na_familles = array() ;// On stocke la fmaille de chaque NA dans un tableau.
		$query = "
			SELECT DISTINCT
				t0.agregation_label, t0.agregation, t0.mandatory, t0.agregation_rank
					FROM sys_definition_network_agregation t0, sys_definition_group_table_network t1, sys_definition_group_table_ref t2
				WHERE t0.family='$famille_principale'
					AND t0.agregation IS NOT NULL AND t0.agregation<>''
					AND t0.on_off=1
					AND t0.axe IS NULL
					AND t2.family = t0.family
					AND t1.id_group_table = t2.id_ligne
					AND t0.agregation = split_part( t1.network_agregation, '_', 1)
					ORDER BY t0.mandatory asc, t0.agregation_rank desc
			";
		$res = $db->getall($query);
		if ($res) {
			foreach ($res as $row) {
				$liste_agregation[$row["agregation_rank"]]["value"] = $row["agregation"];
				$liste_agregation[$row["agregation_rank"]]["label"] = $row["agregation_label"];
				// On stocke les NA de la famille principale.
				$liste_na_famille_principale .= '\''.$row["agregation"].'\',';
				// On stocke la famille de chaque NA dans un tableau.
				$tab_na_familles[$row["agregation"]] = $famille_principale;
			}
		}

		// On supprime la dernière virgule de la variable liste_na_famille_principale
		$liste_na_famille_principale = substr($liste_na_famille_principale,0,strlen($liste_na_famille_principale)-1);

		/*
			Etape 2 :
			- on récupère toutes les NA de toutes les fmailles différentes de la famille principale sans répéter les NA déjà
			présentes dans la fmaille principale.
			- on range ces NA dans le tableau $arbre.
		*/
		$query = "
			SELECT DISTINCT
				t0.agregation_label, t0.agregation, t0.mandatory, t0.agregation_rank, t0.family
					FROM sys_definition_network_agregation t0, sys_definition_group_table_network t1, sys_definition_group_table_ref t2
				WHERE t0.family<>'$famille_principale'
					AND t0.agregation IS NOT NULL AND t0.agregation<>''
					AND t0.agregation NOT IN ($liste_na_famille_principale)
					AND t0.on_off=1
					AND t0.axe IS NULL
					AND t2.family = t0.family
					AND t1.id_group_table = t2.id_ligne
					AND t0.agregation = split_part( t1.network_agregation, '_', 1)
					ORDER BY t0.mandatory asc, t0.agregation_rank desc
			";
		$res = $db->getall($query);
		if ($res) {
			$nb_res = count($res);
			for ($i = 0;$i < $nb_res;$i++) {
				$row = $res[$i];
				/*
					$liste_agregation2[$row["agregation_rank"]+1000]
					On fait + 1000 car on vat additionner le tableau $liste_agregation2 avec un autre.
					Si 2 index sont identiques, certains éléments du tableau vont donc disparaître.
					Voilà pourquoi on fait cette manip.
				*/
				// 28/03/2008 christophe : répercution de la correction du bug 5667 corrigé dans le CB 3.0.1.04 / 3.0.1.14
				// maj 04/01/2008 christophe : correction du bug 5667, il manque des NA dans la liste dans alarm managmeent,history et my profile.
				// On remplace l'index $row["agregation_rank"] par $i car $row["agregation_rank"] peut être identique et du coup des valeurs peuvent disparaitre du tableau (c'est le cas du bug 5667)
				if ( !isset($tab_na_familles[$row["agregation"]]) )
				{
					$liste_agregation2[1000+$i]["value"] = $row["agregation"];
					$liste_agregation2[1000+$i]["label"] = $row["agregation_label"];

					// On stocke la famille de chaque NA dans un tableau.
					$tab_na_familles[$row["agregation"]] = $row["family"];
				}
			}
		}

	}

	// Plus utilisé 28/06/2007 christophe
	//$_SESSION["na_courante_selecteur"] = $network_aggregation;
	//$_SESSION["famille_courante_selecteur"] = $family;

	// 28/06/2007 christophe Si family=='all', on n'est pas dans le sélecteur donc pas de array_intersect.
	if ( $family != 'all' )
	{
		$array_result = array();
		parcoure_arbre($id_ligne_selected);
		//__debug($_SESSION['liste_na_sys_selecteur_properties'],'$_SESSION["liste_na_sys_selecteur_properties"]');
		//__debug($array_result,'$array_result');

		$array_tri = array_intersect_advance($_SESSION['liste_na_sys_selecteur_properties'],$array_result);
		//__debug($array_tri,'$array_tri');
		// 22/06/2007 christophe - on stocke la liste des niveau d'agrégation affichés dans NA sélection.
		$_SESSION["na_listed_in_na_selection"] = $array_tri;
	}
	else
	{
		// Si il n'y a qu'une seule famille ou que toutes les familles sont dépendantes de la famille principale, $liste_agregation2 est vide.
		if ( !empty($liste_agregation2) )
			$array_tri = $liste_agregation + $liste_agregation2;
		else
			$array_tri = $liste_agregation;
		$_SESSION["na_listed_in_na_selection"] = $array_tri;
	}
}	// fin if mode

	// 21/06/2007 christophe - On transforme la variable $_SESSION["network_element_preferences"] en tableau.(le format de la chaine est : na@na_value@na_label|na@na_value@na_label)
	//if (isset($_SESSION["network_element_preferences"])) {
	//if(isset($_SESSION["selecteur_general_values"]["list_of_na"]))
	if (isset($_SESSION["network_element_preferences"])) {
		$liste = explode('|', $_SESSION["network_element_preferences"]);
		//$liste = explode('|', $_SESSION["selecteur_general_values"]["list_of_na"]);
		foreach($liste as $elem) {
			$na_value_svg = explode('@', $elem);
			$tab_network_element_preferences[$na_value_svg[0]][$elem] = $elem;
		}
	}

	// Message à retourner.
	$message = "";
	// Contient la liste des éléments réseaux à sélectionner.
	$selecteur_general_values_list_of_na = '';

	// Construction de l'affichage de la liste des NA mères.
	$list_elements_reseaux = ''; // On stocke chaque élément réseau pour le transmettre au fichier javascript. Cela permet de savoir quels sont les éléments réseaux cochés.
	if ( count($array_tri) > 0 ) {
		foreach ($array_tri as $elem) {
			$row = $elem;
			$list_na .= $row['value']."@";

			// 28/06/2007
			$family_js = ($family=='all') ? $tab_na_familles[$row["value"]] : $family;

			// Titre d'une barre.
			$message .= "
				<div id='{$row['value']}_title' class='accordion_title' onclick=\"openAccordion('{$row['value']}','$family_js',$product)\">
					{$row['label']}
				</div>
			";

			// Liste des network aggregation value pour une na donnée.
			$message .= "
				<div id='{$row['value']}' class='accordion_element' style='display:none'></div>
			";

			/*
				21/06./2007 christophe.
				A partir de la liste de tous les éléments réseaux déjà choisit par l'utilisateur contenu dans $_SESSION["network_element_preferences"],
				on vat remplir la variable contenant la liste des éléments réseaux à sélectionner dans le dashboard courant.
				> on ne prend que les éléments réseaux dont le niveau d'agrégation est affiché dans l'interface 'network element selection'.
			*/
			// Si l'utilisateur a déjà enregistré des éléments réseaux pour ce NA, on les ajoute.

			if ( isset( $tab_network_element_preferences[$row['value']] ) )
			{
				foreach ($tab_network_element_preferences[$row['value']] as $key=>$val)
				{
					$temp = explode('@', $val);
					if ( empty($selecteur_general_values_list_of_na) ){
						$selecteur_general_values_list_of_na .= $val;
						$list_elements_reseaux .= $temp[1].'_'.$temp[0];
					} else {
						$selecteur_general_values_list_of_na .= '|'.$val;
						$list_elements_reseaux .= '|ss|'.$temp[1].'_'.$temp[0];
					}
				}
			}

		}

		// 24/08/2007 christophe : si on est dans le dashboard générique,
		if ( $modeNaSelection == 'dashboard_generique' && $_SESSION["selecteur_general_values"]["list_of_na"] != '')
			$list_elements_reseaux = 'ok';

		// 21/06/2007 christophe
		$message .= "
			<input type='hidden' value='$list_na' id='list_na_mere' name='list_na_mere'/>
			<input type='hidden' value='$list_elements_reseaux' id='list_elements_reseaux' name='list_elements_reseaux'/>
		";
		// 21/06/2007 christophe : on initialise la liste des éléments réseaux à afficher dans le dahsboard.
		unset($_SESSION["selecteur_general_values"]["list_of_na"]);
		$_SESSION["selecteur_general_values"]["list_of_na"] = $selecteur_general_values_list_of_na;
		//__debug($_SESSION["selecteur_general_values"]["list_of_na"],'$_SESSION["selecteur_general_values"]["list_of_na"]');
		//__debug($_SESSION["network_element_preferences_user"],'$_SESSION["network_element_preferences_user"]');
	} else {
		$message .= "No network aggregation defined.";
	}



	echo $message;
?>
