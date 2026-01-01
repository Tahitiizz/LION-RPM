<?php
/*
 *  @cb50400
 *
 *  28/07/2010 OJT : Correction bz17078
 *  20/08/2010 NSE DE Firefox bz 17383 : drag&drop non fonctionnel
 *  03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
 *  15/09/2010 - Correction du bz 17496 - MPR : Icone ne s'affiche pas
            remplacement de  $affichage_liste_deroulante[7][0] par $affichage_liste_deroulante[6][0]
 *  17/01/2011 NSE bz 20136 : perte du produit sur lequel les Query sont sauvegardées -> on ajoute en paramètre l'id_product
 *  11/02/2011 SPD1 - BZ 10797 - [REC][T&A Cb 5.0][Query Builder] : le tooltip des formules n'affiche pas les '+'
 */
?><?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
* 	- 19/11/2008 SLC : ajout de &product= dans l'url des <iframe>
*
* 	- 15/06/2009 BBX : 
		=> modification des requêtes avec id_user, ajout de quotes (champ text désormais)
		=> constantes CB 5.0
		=> Header CB 5.0
		
*	- 10/07/2009 - MPR : 
		=> Correction du BZ 10555 - Aucun raw/kpi pour les produits slave
		=> Correction du BZ 10556 - Affichage du menu contextuel
		
*	- 28/10/2009 BBX :
		=> construction de la table de données depuis les infos famille. BZ 11950
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
* 	- 08/01/2008 Gwénaël : modification de l'attribut target quand on change de famille
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

	- maj 10/05/2007, benoit : inversion du nom et du label de la valeur de la na 3eme axe dans le tableau des    elements 3eme axe

	- maj 14/05/2007, gwénaël : ajout d'un bouton pour changer de famille

	- maj 23/05/2007, benoit : encodage/decodage des valeurs des na 3eme axe pour eviter les conflits avec les    separateurs de champs

	- maj 23/05/2007, benoit : remplacement du caractere ':' dans les labels des kpis par un espace (':' est      utilisé comme separateur de champs)

	- maj 31/05/2007, benoit : dans la liste des na 3eme axe, on affiche uniquement les na qui possèdent des      valeurs et si une na n'a pas de label, on utilise sa valeur comme tel

	- maj 06/06/2007, benoit : dans la fonction 'display_level_1()', on decode le commentaire au cas où elle      contiendrait des caractères encodés (cas des labels des elements 3eme axe par exemple)

*/
?>
<?php
session_start();
// cette page est le menu de gauche du builder report
// dans un premiere temp les informations necessaire sont collectées dans la bdd
// puis les menu html sont générées
// cette page contient trois fonction  toutes dédiées directement a l'affichage :
// display_level_0
// display_level_1
// display_level_2
// $page_encours = 500;
/*
*
* 24/11/2005 : Gestion du Troisième axe pour roaming
* 			 : suppression de l'include vers environnement_donnees.php
* 27/10/2006 xavier : les trois requêtes de sélection des kpi et raw affichent le label de préférence au nom

*/
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/php2js.php");


$lien_css = $path_skin . "easyopt.css";

//edw_LoadParams();

// maj 10/07/2009 - MPR : Correcrtion du bug 10555 - Aucun raw/kpi pour les produits slave
// On récupère la valeur du module du produit concerné 
$_module = get_sys_global_parameters('module', "",$product);//$global_parameters['module'];
$family = $_GET["family"];
$flag_axe3 = false;
// $axe_information = get_axe3_information_from_family($family);   19/11/2008 - SLC - obsolète
$axe_information = getAxe3Information($family,$product);

// 28/10/2009 BBX : récupération des infos GT de la  famille. BZ 11950
$familyInformation = get_gt_info_from_family($family,$product);

// gestion multi-produit - 20/11/2008 - SLC
include_once('connect_to_product_database.php');

// $db_prod->debug = 1;
$network_min = get_network_aggregation_min_from_family($family,$product); //determine le niveau network minimum qui va être utilise pour la liste d'éléments

if (GetAxe3($family,$product)) {
	// gestion de l'axe3 : le group table sera traité uniquement au moment de la requete avec le drag&drop du troisième axe

	$flag_axe3 = true;

	// 19/04/2007 - Modif. benoit : on utilise la fonction 'getNaLabelList()' de 'php/edw_function.php' pour determiner les na 3eme axe
	// 23/04/2007 - Modif. benoit :
	$liste_axe3_element_tmp = getNaLabelList('na_axe3', $family,$product);
	$liste_axe3_element_tmp = $liste_axe3_element_tmp[$family];

	$liste_axe3_element = array();

	// maintenant, la grande nouveauté de la nouvelle topologie 4.1, c'est que get_object_ref_from_family doit toujours renvoyer "edw_object_ref"
	$object_ref_table = get_object_ref_from_family($family,$product);

	foreach ($liste_axe3_element_tmp as $key=>$value) {

		// 31/05/2007 - Modif. benoit : on affiche uniquement les na qui possèdent des valeurs et si une na n'a pas de label, on utilise sa valeur comme tel
		$sql = "
			SELECT DISTINCT eor_id AS na_axe3,
				CASE WHEN eor_label IS NOT NULL THEN eor_label ELSE eor_id END AS na_axe3_label,
				'$value' AS na_axe3_group
			FROM $object_ref_table
			WHERE eor_obj_type='$key'
			";
		// echo "<pre>$sql</pre>";exit;

		$req = $db_prod->getall($sql);

		// 10/05/2007 - Modif. benoit : inversion du nom et du label de la valeur de la na 3eme axe dans le tableau des elements 3eme axe
		foreach ($req as $row)
			$liste_axe3_element[$key][] = array($row['na_axe3_label'], $row['na_axe3'], $row['na_axe3_group']);
	}

	// pour chaque identifiant de 3eme axe, on recupere la colonne qui sert de 3 eme axe (exenetwork pour roaming)
	// cette colonne va être envoyée au formulaire de creation de requete
	foreach ($axe_information["axe_gt_id"] as $axe_gt_id) {
		$axe_gt_id_information = get_axe3_information_from_axe_id($axe_gt_id,$product);
		$axe3[$axe_gt_id] = $axe_gt_id_information["axe_index"];
	}
} else {
	$group_table_from_family = "edw_" . $_module . "_" . $family . "_" . $axe_information['axe_gt_id'][0];

}

// CES 2 LIGNES SONT POUR DES TESTS
$group_table_from_family = "edw_" . $_module . "_" . $family . "_" . $axe_information['axe_gt_id'][0];

// 28/10/2009 BBX : construction de la table de données depuis les infos famille. BZ 11950
$group_table_from_family = $familyInformation['edw_group_table'];

// PAGE
$arborescence = 'Query Builder';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');

// maj 10/07/2009  - MPR : Correction du BZ10556 - Affichage du menu contextuel
$id_menu_encours = $_GET['id_menu_encours'];

include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");
?>
<?php
// TTMT
function display_level_0($libelle, $numero_label, $image) {
	global $family, $product, $id_menu_encours;
	?>
	<tr>
		<td>
			<a href="builder_report_queries_database.php?id_menu_encours=<?=$id_menu_encours?>&family=<?=$family?>&product=<?=$product?>&numero_label=<?=$numero_label?>">
				<img hspace="4" vspace="3" align="absmiddle" src="<?=NIVEAU_0?>images/icones/<?=$image?>" border="0">
					<font class="texteGrisPetit"><?=ucfirst($libelle)?></font>
			</a>
		</td>
	</tr>
<?php
}


// fonction qui affiche une ligne de niveau 1 = liste de tables
function display_level_1($parameters, $compteur, $image1, $image2, $flag_deploiement, $numero_label, $drag_drop) {
	global $image_blank, $family, $product, $id_menu_encours;
	if ($drag_drop == 1) {
		$array_param = explode(':', $parameters);
		if ($array_param[0] == 6) {
			$tmp = explode('|', $array_param[2]);
			$commentaire = $tmp[0];
		} else if ($array_param[0] == 7) {
			$commentaire = $array_param[3];
		} else
			$commentaire = $array_param[1];

		// 06/06/2007 - Modif. benoit : on decode le commentaire au cas où elle contiendrait des caractères encodés (cas des labels des elements 3eme axe par exemple)
		// 11/02/2011 - SPD - BZ 10797 - urldecode remplace les signes "+" par des espaces, utilisation de rawurldecode à la place
		$commentaire = rawurldecode($commentaire);

		if ($array_param[0] == 7) {
                     // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre
                     // 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
			echo "<tr><td class='texteGrisPetit'>"
				."<img src='".NIVEAU_0."images/icones/$image_blank' border='0'/>"
				."<img src='".NIVEAU_0."images/icones/$image1' border='0'/>"
				// 11/02/2011 SPD - BZ 10797 - utilisation du popalt a la place du alt
				."<img hspace='4' onmouseover=\"popalt('$commentaire');style.cursor='pointer';\" src='".NIVEAU_0."images/icones/$image2' border='0' onDragStart=\"SetupDrag(event,'$parameters');\" />"
				."<font class='texteGrisPetit'>".ucfirst(strtolower($array_param[1]))."</font></td></tr>";
		} else {
			// 23/05/2007 - Modif. benoit : decodage du nom et du label de la na dans la liste
                        // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre
                        // 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
			echo "<tr><td class='texteGrisPetit'>"
				."<img src='".NIVEAU_0."images/icones/$image_blank' border='0'/>"
				."<img src='".NIVEAU_0."images/icones/$image1' border='0'/>"
				// 11/02/2011 SPD - BZ 10797 - utilisation du popalt a la place du alt
				."<img src='".NIVEAU_0."images/icones/$image2' hspace='4' onmouseover=\"popalt('$commentaire');style.cursor='pointer';\" border='0' onDragStart=\"SetupDrag(event,'$parameters');\" />"
				."<font class='texteGrisPetit'><a name='".urldecode($array_param[1])."' class='texteGrisPetit'>".ucfirst(strtolower(urldecode($array_param[1])))."</a></font></td></tr>";
		}

	} else {
		//
		echo "<tr><td class='texteGrisPetit'>"
			."<a href='builder_report_queries_database.php?id_menu_encours=$id_menu_encours&product=$product&family=$family&numero_label=$numero_label&deploiement_en_cours=$flag_deploiement&numero_deploiement=$compteur#$parameters'>"
			."<img src='".NIVEAU_0."images/icones/$image1' border='0'/>"
			."<img src='".NIVEAU_0."images/icones/$image2' border='0' hspace='4' alt=\"$parameters\" />"
			."<font class='texteGrisPetit'><a name='$parameters' class='texteGrisPetit'>$parameters</a></font></a></td></tr>";
	}
}

// fonction qui affiche une ligne de niveau 2 = champs de tables ou valeurs
function display_level_2 ($im1, $parameters, $image1, $image2, $numero_label, $drag_drop, $niveau2 = "", $deploiement2 = "", $compteur = "") {
	global $image_blank, $data_type, $family, $product, $id_menu_encours;
	
	if ($drag_drop == 1) {
		$array_param = explode(':', $parameters);
                // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre
                // 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
		echo "<tr><td class='texteGrisPetit' nowrap='nowrap'>"
			."<img width='22' height='18' src='".NIVEAU_0."images/icones/$image_blank'/>"
			."<img width='24' height='18' src='".NIVEAU_0."images/icones/$im1'/>"
			."<img src='".NIVEAU_0."images/icones/$image1'/>"
			."<img hspace='4' alt=\"{$array_param[1]}\" onmouseover=\"style.cursor='pointer';\" src='".NIVEAU_0."images/icones/$image2' onDragStart=\"SetupDrag(event,'$parameters');\"/>"
			."<font class='texteGrisPetit'>{$array_param[1]}</a></font></td></tr>";
	} else {
		echo "<tr><td class='texteGrisPetit' nowrap='nowrap'>"
			."<a href='builder_report_queries_database.php?id_menu_encours=$id_menu_encours&family=$family&product=$product&deploiement_en_cours2=$deploiement2&numero_deploiement2=$niveau2&numero_label=$numero_label&deploiement_en_cours=1&numero_deploiement=$compteur#$parameters'>"
			."<img  width='24' height='18' src='".NIVEAU_0."images/icones/$image_blank' border='0'/>"
			."<img  src='".NIVEAU_0."images/icones/<?=$im1?>' border='0'/>"
			."<img hspace='4' alt='$parameters' src='".NIVEAU_0."images/icones/$image2' border='0'/>"
			."<font class='texteGrisPetit'><a name='$parameters' class='texteGrisPetit'>$parameters</a></font></a></td></tr>";
	}
}

function display_level_3($parameters, $image1, $image2, $numero_label, $im1, $im2) {
	global $image_blank, $family, $product, $id_menu_encours;
	$array_param = explode(':', $parameters);
	// 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre
        // 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
	echo "<tr><td class='texteGrisPetit'>"
		."<img src='".NIVEAU_0."images/icones/$image_blank'/>"
		."<img width='28' height='18' src='".NIVEAU_0."images/icones/$im1'/>"
		."<img  src='".NIVEAU_0."images/icones/$im2' border='0'/>"
		."<img  src='".NIVEAU_0."images/icones/$image1' border='0'/>"
		."<img hspace='4' alt='{$array_param[1]}' onmouseover=\"style.cursor='pointer';\" src='".NIVEAU_0."images/icones/$image2' onDragStart=\"SetupDrag(event,'$parameters');\">"
		."<font class='texteGrisPetit'>{$array_param[1]}</a></font></td></tr>";
}


	//
	// DEBUT DE LA PAGE
	//

?>
<script src="<?=NIVEAU_0?>/js/builder_report.js"></script>
<div id="container" style="width:100%;text-align:center">

<table width="90%" align="center" border="0" cellspacing="0" cellpadding="6" class="tabPrincipal">
	<tr>
					<td class="texteGrisPetit" align="center">
					<?php
						// Infos produit
						$queryNbProd = "SELECT count(*) AS nbprod FROM sys_definition_product WHERE sdp_on_off = 1";
						$resultNbProd = $db_prod->getRow($queryNbProd);
						$productLabel = '';
						if($resultNbProd['nbprod'] > 1) {
							$queryProducts = "SELECT sdp_label
							FROM sys_definition_product 
							WHERE sdp_id = '$product'";
							$resultMyProd = $db_prod->getRow($queryProducts);
							$productLabel = $resultMyProd['sdp_label'];
						}	
						// 15/07/2009 MPR - Correction du bug 10552 - On réordonne l'affichage du changement de prod
						echo $productLabel.' - ';
						
						/////////////////////////
						
						$query_family = " select family_label from sys_definition_categorie where family='$family' ";
						$family_label = $db_prod->getone($query_family);
						echo $family_label;

						echo "&nbsp;";

						// MàJ 09/08/2007 - JL :  Ajout condition d'affichage de l'icone
						// modif 08/01/2008 Gwnénaël
						// modif de la cible target
						if (get_number_of_family(false,$product) > 1) {	

						?>
							<a href="builder_report_index.php?id_menu_encours=<?=$id_menu_encours?>&product=<?=$product?>" target="_parent">
								<img src="<?=NIVEAU_0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0"/>
							</a>

						<? } ?>

					</td>
				</tr>
	<tr>
		<td>
			<fieldset>
			<legend class="texteGrisBold">&nbsp;<img align="absmiddle" src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif" border="0">&nbsp;Queries List&nbsp;</legend>
			<table cellpadding="4" cellspacing="2" border="0">
			  <tr>
				<td>
				   <table width="100%" border="0" cellpadding="0" cellspacing="0">
					  <tr>
						<td>
						<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<?php
// les données sont préparées de telle sorte qu'on a l'information du drag&drop constriute de la facon suivante
// valeur=param1:param2:param3 etc...
// param1 est le paramètre qui permet de savoir de quelle données on parle (time agregation, network_agregation etc...).
// cela permettra de vérifier qu'on fait un drag&drop sur les bons cubes
// 1 : fonction (ne posede que les trois premiers arguments)
// 2 : network_agregation
// 3 : time_agregation
// 4 : kpi de type mixed,non mixed ou row
// 5 : requetes sauvegardees
// 6 : formules
// 7 : my_network_aggregation
// 8 : 3eme axe
// param2 est le libellé qui sera visisble de l'utilisateur
// parma3 est la valeur du champ qui va servir pour la requete
// parma4 est le type raw,kpi,mixed ou li'dentifiant du 3eme axe
// param5 : est le group table lorsqu'il existe (uniquement utilisé en KPI,mixed ou RAW  =>param1=4)
$seperateur_parametre = ":";
//2010/08/24 - MGD - BZ 17496 : Suppression des 'Fonctions'
// Liste des fonction :
//$param1 = 1;
//$param2 = "Sum";
//$param3 = "SUM";
//$liste_fonction[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $seperateur_parametre;
//$param2 = "Max";
//$param3 = "MAX";
//$liste_fonction[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $seperateur_parametre;
//$param2 = "Min";
//$param3 = "MIN";
//$liste_fonction[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $seperateur_parametre;
//$param2 = "Avg";
//$param3 = "AVG";
//$liste_fonction[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $seperateur_parametre;

// Recupère les nom des agregation network
/*
	Modification christophe le 22 11 2006 : l'odre des NA correspond à celui renvoyé par la requête
	dans la table sys_selecteur properties.
*/
// MaJ 20/11/2008 - SLC - remplace ce code par un appel à getNaLabelList()
/*
$query = " --- on va chercher les niveaux d'aggregation
	SELECT DISTINCT ON (agregation) agregation_label, agregation,agregation_mixed
		FROM sys_definition_network_agregation
		WHERE on_off=1 AND family='$family'
	";
$na_list = $db_prod->getall($query);
$p = 0;
foreach ($na_list as $row) {
	$param1 = 2;
	$param2 = ucfirst($row["agregation_label"]);
	$param3 = $row["agregation"];
	$param4 = $row["agregation_mixed"];
	$param5 = "network";
	if ($row["agregation_name"] == "omc_index")
		$param2 = "Cellname";
	$liste_network_agregation[$p] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
	$p++;
}
// __debug($na_list);
*/

$na_list_arr = getNaLabelList('na',$family,$product);
$na_list = array_pop($na_list_arr);

//$liste_network_agregation = array();
//foreach ($na_list as $key => $label)
//	$liste_network_agregation[] = implode($seperateur_parametre, array(2, ucfirst($label), $key, '', 'network'));


// Recupère les noms des agregations temporelles
$query = "select agregation_label,agregation from sys_definition_time_agregation where on_off=1 and visible=1 order by agregation_rank DESC";
$result_time_agregation = $db_prod->getall($query);
foreach ($result_time_agregation as $row) {
    $param1 = 3;
    $param2 = ucfirst($row["agregation_label"]);
    $param3 = $row["agregation"];
    $param4 = $row["agregation"];
    $param5 = "time";
    $liste_time_agregation[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
}

// 27/10/2006 xavier

// Patch manuel pour les busy hour
// Recupère les noms de kpi de type "non mixed"
$liste_nonmixed_kpi_name = array();
$query = "
	SELECT DISTINCT edw_group_table, kpi_name,
		CASE WHEN kpi_label IS NOT NULL THEN kpi_label ELSE kpi_name END AS label
	FROM sys_definition_kpi
	WHERE on_off=1
		AND new_field=0 
		AND numerator_denominator='total'
		AND edw_group_table='$group_table_from_family'
	ORDER BY label";
$nonmixed_kpi = $db_prod->getall($query);
foreach ($nonmixed_kpi as $row) {
	$param1 = 4;
	
	// 23/05/2007 - Modif. benoit : remplacement du caractere ':' dans les labels des kpis par un espace (':' est utilisé comme separateur de champs)
	$param2 = str_replace(':', ' ', $row["label"]);
	$param3 = strtolower($row["kpi_name"]);
	$param4 = "kpi";
	$param5 = $row["edw_group_table"];
	$liste_nonmixed_kpi[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
}

// Recupère les noms de kpi de type "mixed"
$liste_mixed_kpi_name = array();
$query = "
	SELECT distinct edw_group_table,kpi_name,
		CASE WHEN kpi_label IS NOT NULL THEN kpi_label ELSE kpi_name END AS label,
		comment
	FROM sys_definition_kpi
	WHERE on_off=1
		AND new_field=0
		AND numerator_denominator='mixed'
	ORDER BY label";
$mixed_kpi = $db_prod->getall($query);
foreach ($mixed_kpi as $row) {
	$param1 = 4;
	
	// 23/05/2007 - Modif. benoit : remplacement du caractere ':' dans les labels des kpis par un espace (':' est utilisé comme separateur de champs)
	$param2 = str_replace(':', ' ', $row["label"]);
	
	$param3 = strtolower($row["kpi_name"]);
	$param4 = "mixed";
	$param5 = $row["edw_group_table"];
	if (!in_array($param5, $liste_mixed_kpi_name))
	$liste_mixed_kpi_name[] = $param5;
	$liste_mixed_kpi[$param5][] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
}

// Recupère les noms des raw data
$liste_raw_counters_name = array();
$query = "
	SELECT distinct edw_group_table, edw_field_name,
		CASE WHEN edw_field_name_label IS NOT NULL THEN edw_field_name_label ELSE edw_field_name END AS label,
		comment
	FROM sys_field_reference
	WHERE on_off=1
		AND visible=1
		AND edw_group_table='$group_table_from_family'
	ORDER BY label";
$raw_counters = $db_prod->getall($query);
foreach ($raw_counters as $row) {
    $param1 = 4;
    $param2 = $row["label"];
    $param3 = strtolower($row["edw_field_name"]);
    $param4 = "raw";
    $param5 = $row["edw_group_table"];
    $liste_raw_counters[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
}

$liste_query_private = array();
// Recupère les requetes sauvegardées
$query = "select * from report_builder_save where on_off=1 and family='$family' and id_user='$id_user' order by texte" ;
$result_query = $db_prod->getall($query); // or die ($query);
foreach ($result_query as $row) {
    $param1 = 5;
    $param2 = ucfirst($row["texte"]);
    $param3 = $row["id_query"];
    $param4 = "";
    $param5 = $family; //passe la famille car c'est utile lorsque les requetes sont sauvegardées puis reachargées
    // 17/01/2011 NSE bz 20136 : on ajoute en paramètre l'id_product
    $param6 = $product; //passe le produit car c'est utile lorsque les requetes sont sauvegardées puis reachargées
    $liste_query_private[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5 . $seperateur_parametre . $param6;
}


$liste_formula = array();
// Recupère les fomules  sauvegardées
$query = "select * from forum_formula where on_off=1 and family='$family' and  id_user='$id_user' order by formula_name";
$result_query = $db_prod->getall($query);
foreach ($result_query as $row) {
	$param1 = 6;
	$param2 = ucfirst(strtolower($row["formula_name"]));
	$param3 = $row["formula_equation"] . "|" . $row["id_formula"];
	$tmp = $row["formula_edw_group_by"];
	if (count(explode(",", $tmp)) > 1) {
		$param4 = "mixed";
	} else {
		$param4 = "raw";
	}
	$param5 = $row["formula_edw_group_by"];
	$liste_formula[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
}


$liste_my_network = array();
// Recupère les requetes sauvegardées
$query = "select * from my_network_agregation where on_off=1 and family='$family' and id_user='$id_user' order by agregation_name" ;
$result_query = $db_prod->getall($query); // or die ($query);

foreach ($result_query as $row) {
    $cell_liste = $row["cell_liste"];
    $cell_liste = addslashes($cell_liste);
    $param1 = 7;
    $param2 = ucfirst($row["agregation_name"]);
    $param3 = $network_min;
    $param4 = $cell_liste;
    $param5 = "";
    $liste_my_network[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
}


// si on doit gérer un troisième axe
if ($flag_axe3) {

	foreach ($liste_axe3_element as $key => $axe3_element) {
        /*if ($axe3_element != '--------') {
            $array_axe3 = explode("@", $key);
            $param1 = 8;
			$param2 = addslashes($axe3_element);
            $param3 = $axe3[$array_axe3[1]];
            $param4 = $array_axe3[0]; //identifiant du 3eme axe
            //$param5 = "edw_" . $_module . "_" . $family . "_" . $array_axe3[1]; //group table

			// 20/04/2007 - Modif. benoit : utilisation de la fonction 'get_gt_name()' pour determiner le nom du group table

			$gt_info	= get_gt_info_from_family($family);
			$param5		= $gt_info['edw_group_table'];

            $liste_axe3_display[] = $param1 . $seperateur_parametre . $param2 . $seperateur_parametre . $param3 . $seperateur_parametre . $param4 . $seperateur_parametre . $param5;
        }*/

		// 23/04/2007 - Modif. benoit : on affiche toutes les valeurs des na 3eme axe et non simplement leur nom
		for ($i=0; $i < count($axe3_element); $i++) {
			if ($axe3_element != '--------') {
				$array_axe3 = explode("@", $key);
				$param1 = 8;
	
				// 24/04/2007 - Modif. benoit : on concatene le label de la na_value 3eme axe et le label de la na 3eme axe
				$param2 = addslashes($axe3_element[$i][0])." (".$axe3_element[$i][2].")";
	
				$param3 = $axe3_element[$i][1];
				$param4 = $array_axe3[0]; //identifiant du 3eme axe
	
				// 20/04/2007 - Modif. benoit : utilisation de la fonction 'get_gt_name()' pour determiner le nom du group table
				$gt_info	= get_gt_info_from_family($family,$product);
				$param5	= $gt_info['edw_group_table'];
	
				// 24/04/2007 - Modif. benoit : ajout du parametre du label de la na 3eme axe
				$param6 = $axe3_element[$i][2];
	
				// 23/05/2007 - Modif. benoit : encodage des valeurs du 3eme axe pour eviter les erreurs liées au separateur de champs
				$liste_axe3_display[] = $param1 . $seperateur_parametre . urlencode($param2) . $seperateur_parametre . urlencode($param3) . $seperateur_parametre . $param4 . $seperateur_parametre . $param5 . $seperateur_parametre . $param6;
	
				//echo $liste_axe3_display[count($liste_axe3_display)-1]."<br>**********<br>";
			}
		}
	}
}

if (is_array($liste_query_private))	sort($liste_query_private);

// genere la matrice pour l'affichage des données à partir de toutes les données collectées
//$affichage_liste_deroulante[0][0] = 'Network Aggregation';
//$affichage_liste_deroulante[0][1] = $liste_network_agregation;

//$affichage_liste_deroulante[1][0] = 'Time Aggregation';
//$affichage_liste_deroulante[1][1] = $liste_time_agregation;

//$affichage_liste_deroulante[2][0] = 'Data Type';

//$liste_data_type[0][0] = 'Raw counters';

//$liste_raw_counters2[$i] = $liste_raw_counters;
//$liste_data_type[0][1] = $liste_raw_counters;

//$liste_data_type[1][0] = 'KPI';
//$liste_data_type[1][1] = $liste_nonmixed_kpi;
/*
$liste_data_type[2][0] = 'MIXED';
$i=0;
foreach($liste_mixed_kpi_name as $name)
	{
	$liste_mixed_kpi2[$i][0]=ucfirst($name);
	$liste_mixed_kpi2[$i][1]=$liste_mixed_kpi[$name];
	$i++;
	}
$liste_data_type[2][1] = $liste_mixed_kpi2;
*/

//$affichage_liste_deroulante[2][1] = $liste_data_type;

//2010/08/24 - MGD - BZ 17496 : Suppression des 'Fonctions' => changement des indices du tableau suivant
//$affichage_liste_deroulante[3][0] = 'Function';
//$affichage_liste_deroulante[3][1] = $liste_fonction;

$affichage_liste_deroulante[3][0] = 'Saved Query';
$affichage_liste_deroulante[3][1] = $liste_query_private;
//$affichage_liste_deroulante[4][0] = 'Formula';
//$affichage_liste_deroulante[4][1] = $liste_formula;
//$affichage_liste_deroulante[5][0] = 'My Network Aggregation';
//$affichage_liste_deroulante[5][1] = $liste_my_network;

if ($flag_axe3) {

	// 19/04/2007 - Modif. benoit : definition du label du dossier 3eme axe
//	$affichage_liste_deroulante[6][0] = 'Third Axis';	// Par defaut

	$sql = "SELECT axe_type_label FROM sys_definition_gt_axe WHERE family='$family'";
	$row = $db_prod->getrow($sql);

        // maj 15/09/2010 - Correction du bz 17496 - MPR : Icone ne s'affiche pas
        //    remplacement de  $affichage_liste_deroulante[7][0] par $affichage_liste_deroulante[6][0]
//	if ($row)
//		if ($row['axe_type_label'] != "") $affichage_liste_deroulante[6][0] = $row['axe_type_label'];

//	$affichage_liste_deroulante[6][1] = $liste_axe3_display;
}

$image_dossier_open[0] = 'dossier_violet_open.gif';
$image_dossier_open[1] = 'dossier_vert_open.gif';
$image_dossier_open[2] = 'dossier_jaune_open.gif';
//$image_dossier_open[3] = 'dossier_bleu_open.gif';
$image_dossier_open[3] = 'dossier_violet_open.gif';
$image_dossier_open[4] = 'dossier_vert_open.gif';
$image_dossier_open[5] = 'dossier_violet_open.gif';

$image_dossier[0] = 'dossier_violet.gif';
$image_dossier[1] = 'dossier_vert.gif';
$image_dossier[2] = 'dossier_jaune.gif';
//$image_dossier[3] = 'dossier_bleu.gif';
$image_dossier[3] = 'dossier_violet.gif';
$image_dossier[4] = 'dossier_vert.gif';
$image_dossier[5] = 'dossier_violet.gif';

$image_cube[0] = 'pt_cube_violet.gif';
$image_cube[1] = 'pt_cube_vert.gif';
$image_cube[2] = 'pt_cube_jaune.gif';
//$image_cube[3] = 'fonction.gif'; //ou pt_cube_bleu.gif
$image_cube[3] = 'pt_cube_violet.gif';
$image_cube[4] = 'pt_cube_vert.gif';
$image_cube[5] = 'pt_cube_violet.gif';

if ($flag_axe3) {
	$image_dossier_open[6] = 'dossier_violet_open.gif';
	$image_dossier[6] = 'dossier_violet.gif';
	$image_cube[6] = 'pt_cube_violet.gif';
}
$image_blank = 'blank.gif';
$image_expand_open = 'bl.gif';
$image_expand_collapse = 'bm.gif';
$image_ligne_verticale = 'me.gif';
$image_ligne_verticale_fin = 'be.gif';

// parcoure les labels de niveau 0
foreach ($affichage_liste_deroulante as $key => $liste_niveau0) {
    // ****AFFICHAGE NIVEAU 0****
    // teste si le numéro label (lien surlequel on a cliqué) vaut la valeur du compteur
    if ($numero_label == $key and $numero_label != -1) {
        // affiche un lien dont le numero de label vaut -1 puisque l'élément est déployé
        display_level_0($liste_niveau0[0], -1, $image_dossier_open[$key]);
        // parcoure les éléments de niveau 1
        $compteur_affichage_niveau1 = 0;

        foreach ($liste_niveau0[1] as $liste_niveau1) {
            // ****AFFICHAGE NIVEAU 1****
            // teste si le numéro de la table en cours correspond à la table sur laquelle l'action de expand ou collapse a été réalisée
            // $deploiement_en_cours=1 correspond à un Expand alors que -1 correspond à 1 collapse
            if ($numero_deploiement == $compteur_affichage_niveau1 and $deploiement_en_cours == 1) {
                $image_expand = $image_expand_open;
                $deploiement = -1;
            } else {
                $image_expand = $image_expand_collapse;
                $deploiement = 1;
            }
            // teste si le niveau 1 est un array auquel cas il faut afficher un niveau 2
            if ($compteur_affichage_niveau1 < count($liste_niveau0[1])-1) {
                $im1 = "m.gif";
            } else {
                $im1 = $image_blank;
            }

            if (!is_array($liste_niveau1)) {
                $drag_drop = 1; //vaut 1 donc la valeur doit être drag&drop-able
                if ($compteur_affichage_niveau1 < count($liste_niveau0[1])-1) {
                    $image_niveau1_1 = $image_ligne_verticale;
                } else {
                    $image_niveau1_1 = $image_ligne_verticale_fin;
                }
                display_level_1($liste_niveau1, $compteur_affichage_niveau1, $image_niveau1_1, $image_cube[$key], $deploiement, $key, $drag_drop);
            } else {
                $drag_drop = 0;
                display_level_1($liste_niveau1[0], $compteur_affichage_niveau1, $image_blank, $image_expand, $deploiement, $key, $drag_drop);
                // *****AFFICHAGE NIVEAU 2*****
                // pour que l'action de dérouler les données soit réalisé, il faut que :
                // - l'action porte sur le menu
                // - et que le menu ne soit pas déjà déroulé
                if ($numero_deploiement == $compteur_affichage_niveau1 and $deploiement_en_cours == 1) {
                    $niveau2 = 0;
                    $compteur_ligne_niv2 = 1;
                    if (count($liste_niveau1[1]) > 0) {
                        foreach ($liste_niveau1[1] as $liste_niveau2) {
                            if ($numero_deploiement2 == $niveau2 and $deploiement_en_cours2 == 1) {
                                $image_expand2 = $image_expand_open;
                                $deploiement2 = -1;
                            } else {
                                $image_expand2 = $image_expand_collapse;
                                $deploiement2 = 1;
                            }

                            if ($compteur_ligne_niv2 < count($liste_niveau1[1])) {
                                $im2 = "m.gif";
                            } else {
                                $im2 = $image_blank;
                            }
                            // parcoure toutes les données et les affiche
                            if (!is_array($liste_niveau2)) {
                                $drag_drop = 1;
                                if (($compteur_ligne_niv2) < count($liste_niveau1[1])) {
                                    $image_niveau2_1 = $image_ligne_verticale;
                                } else {
                                    $image_niveau2_1 = $image_ligne_verticale_fin;
                                }

                                display_level_2($im1, $liste_niveau2, $image_niveau2_1, $image_cube[$key], $numero_labe, $drag_drop);
                            } else {
                                $drag_drop = 0;
                                // echo "<br>";
                                // echo $liste_niveau2[0];
                                // echo "<br>";
                                // display_level_2($liste_niveau2[0],$image_niveau2_1, $image_cube[$key], $numero_label,$drag_drop);
                                display_level_2($im1, $liste_niveau2[0], $image_niveau2_1, $image_expand2, $numero_label, $drag_drop, $niveau2, $deploiement2, $compteur_affichage_niveau1);
                                // display_level_1($liste_niveau1[0], $compteur_affichage_niveau1, $image_blank, $image_expand, $deploiement, $key, $drag_drop);
                                // display_level_2($liste_niveau2[0],$image_niveau2_1, $image_cube[$key], $numero_label);
                                // *****AFFICHAGE NIVEAU 3*****
                                // pour que l'action de dérouler les données soit réalisé, il faut que :
                                // - l'action porte sur le menu
                                // - et que le menu ne soit pas déjà déroulé
                                if ($numero_deploiement2 == $niveau2 and $deploiement_en_cours2 == 1) { // if ($numero_deploiement == $compteur_affichage_niveau3 and $deploiement_en_cours2 == 1)
                                    $drag_drop = 1;
                                    $compteur_ligne_niv3 = 1;
                                    foreach ($liste_niveau2[1] as $liste_niveau3) {
                                        if ($compteur_ligne_niv3 < count($liste_niveau2[1])) {
                                            $image_niveau3_1 = $image_ligne_verticale;
                                        } else {
                                            $image_niveau3_1 = $image_ligne_verticale_fin;
                                        }
                                        display_level_3($liste_niveau3, $image_niveau3_1, $image_cube[$key], $numero_label, $im1, $im2);
                                        $compteur_ligne_niv3++;
                                    }
                                }
                            }
                            $compteur_ligne_niv2++;
                            $niveau2++;
                        }
                    }
                }
            }
            $compteur_affichage_niveau1++;
        }
    } else {
        // affiche un lien dont le numero de label est nom vide puisque l'élément n'est pas déployé
        display_level_0($liste_niveau0[0], $key, $image_dossier[$key]);
    }
}

?>
			</table>
			</td>
			</tr>
			</table>
			</td>
			</tr>
			</table>
			</fieldset>
		</td>
	</tr>
</table>

<!-- ?php echo $db_prod->displayQueries(); ? -->
</div>
</body>
</html>
