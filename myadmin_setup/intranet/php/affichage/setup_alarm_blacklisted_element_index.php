<?
/*
*	@cb51403@
*
*	01/07/2011 NSE bz 22832 : Ajout du champ pour afficher le détail sur l'élément sélectionné
*	18/11/2011 ACS BZ 24749 mapped NE does not appear in NE list
*	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
*/
?><?
/*
*	@cb41000@
*
*	- maj 14/10/2008 SLC : corrections d'affichage suite à l'ajout du DOCTYPE
*	- maj 05/02/2009 SLC : passage en multi-produit avec nouvelle topologie
*
*/
?><?
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
*/
?>
<?
/*
*	@cb21000_gsm20010@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	Parser version gsm_20010
*	- 27/12/2006 maxime : Seuls les NA actifs (on_off=1)  sont affichés dans les deux listes
*
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
<?php
/*
*
* 25/07/2006 xavier : modification du bug empêchant le passage du dernier élement de droite à gauche.
* 11/10/2006 xavier : on ne tient plus compte du on_off dans les tables edw_object_x_ref
*                     l'ordre d'affichage se fait d'abord sur le label puis sur le nom
*	- maj 13/05/2007 Gwénaël modification de la requete qui récupère les éléments pour prendre en compte le troisième axe 
*	17/02/2015 JLG bz 45818 : add id to list if label is empty
*/
?>
<?php
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "intranet_top.php");
include_once($repertoire_physique_niveau0 . "php/menu_contextuel.php");
include_once($repertoire_physique_niveau0 . "class/select_family.class.php");

global $niveau0;

// on force le choix du produit >> famille
if (!isset($_GET["family"])) {
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Blacklist');
	exit;
}

$product = intval($_GET['product']);

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection($product);

?>

<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>

<table width="778" align="center" valign=middle cellpadding="0" cellspacing="2" class="tabPrincipal">
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td align="center">
			<img src="<?=$niveau0?>images/titres/blacklist_selection_interface_roaming_titre.gif"/>
		</td>
	</tr>
	<tr>
		<td align="center" valign="middle" class="texteGris" style="padding-top:5px;padding-bottom:5px;text-align:center;">
		<?php
			// Recuperation du label du produit
			$productInformation = getProductInformations($product);
			$productLabel = $productInformation[$product]['sdp_label'];
			echo $productLabel."&nbsp;:&nbsp;";

			$family_information = get_family_information_from_family($family, $product);
			echo (ucfirst($family_information['family_label']));

			// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
			if (get_number_of_family() > 1){ ?>
				<a href="<?=$_SERVER['PHP_SELF'];?>" target="_top">
					<img src="<?=$niveau0?>images/icones/change.gif" onMouseOver="popalt('Change Product / Family');style.cursor='help';" onMouseOut='kill()' border="0"/>
				</a>
		<? 	} //fin condition sur les familles ?>
		</td>
	</tr>
	<tr>
		<td style="padding: 2px;">
<?php

$family = $_GET["family"];

$min_na = get_network_aggregation_min_from_family($family,$product);
$min_na_label = $db->getone("select agregation_label from sys_definition_network_agregation where agregation='$min_na'");

// 18/11/2011 ACS BZ 24749 mapped NE does not appear in NE list
// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
// on va chercher tous les network element de niveau mini
$nels = $db->getall("SELECT * FROM edw_object_ref WHERE eor_obj_type='$min_na' AND ".NeModel::whereClauseWithoutVirtual()." AND eor_on_off=1");

// Message d'erreur si rien trouvé
if (!$nels) {
	echo "<tr><td align='center'>
			<font style='font : normal 9pt Verdana, Arial, sans-serif; color : #585858;'><b>Error : no data found. [no data for this family]</b></font>
			</td></tr>
		</table>";
	exit;
}


// 1. si un groupe a été choisi, et que des données ont été envoyées, on traite les données

// on traite les données envoyées par le formulaire
if ($submit) {
	$chosen_fields = '||'.str_replace(' ','',$chosen_fields).'||';

	// on boucle sur tous les nels pour voir s'il faut changer leur valeur de blacklisted
	foreach ($nels as $nel) {
		if (strpos($chosen_fields,"||{$nel['eor_id']}||") === false) {
			// whitelisted
			if ($nel['eor_blacklisted'] == 1)
				$db->execute("update edw_object_ref set eor_blacklisted = 0 where eor_id='{$nel['eor_id']}'");
		} else {
			// blacklisted
			if ($nel['eor_blacklisted'] == 0)
				$db->execute("update edw_object_ref set eor_blacklisted = 1 where eor_id='{$nel['eor_id']}'");
		}
	}
	
	// on va rechercher la liste des nels
	$nels = $db->getall("SELECT *	FROM edw_object_ref	WHERE eor_obj_type='$min_na'	AND eor_on_off=1");
}

// 2. si un groupe a été choisi, on affiche le formulaire de choix
$array_result	= array();	// array temp servant de tampon
$les_champs	= array();	// array contenant la liste des champs : key = eor_id , val = eor_label
$right_ids		= array();	// array simple contenant les id des champs présents dans la colonne de droite
foreach ($nels as $nel) {
	// 18/11/2011 ACS BZ 24749 mapped NE does not appear in NE list
	$neLabel = NeModel::getLabel($nel['eor_id'], $nel['eor_obj_type'], $product);
	if ($neLabel == null) $neLabel = $nel['eor_id'];
	$les_champs[$nel['eor_id']] = $neLabel;
    // 01/07/2011 NSE bz 22832 : la description
    $les_tips["'".$nel['eor_id']."'"] = htmlentities(preg_replace('/[\n\r]+/', ' ', $neLabel));
	
	if ($nel['eor_blacklisted'] == 1)
		$right_ids[] = $nel['eor_id'];
}

// Initialize the phpObjectForms class
require_once $repertoire_physique_niveau0 . "class.phpObjectForms/lib/FormProcessor.class.php";
$fp = new FormProcessor($repertoire_physique_niveau0 . "class.phpObjectForms/lib/");
// 01/07/2011 NSE bz 22832 : utilisation de FPSplitSelectWithToolTip au lieu de FPSplitSelect
$fp->importElements(array("FPButton", "FPHidden", "extra/FPSplitSelectWithToolTip"));
$fp->importLayouts(array("FPColLayout", "FPRowLayout"));
// Create the form object
$myForm = new FPForm(array(
	// "title" => 'Field selector',
	"name" => 'myForm',
	"action" => $_SERVER['PHP_SELF']."?product=$product&family=$family",
	"display_outer_table" => true,
	"table_align" => 'center',
));

$myForm->setBaseLayout(
	new FPColLayout(
		array(
			"table_padding" => 5,
			"element_align" => "center",
			"elements" => array(
				
				// champ split select
                // 01/07/2011 NSE bz 22832 : utilisation de FPSplitSelectWithToolTip au lieu de FPSplitSelect
                // et passage des paramètres liés option_tips, dbl_click et show_modify_link
				new FPSplitSelectWithToolTip(
					array(
						"name" => "chosen_fields",
						// "title" => "Fruits",
						"multiple" => true,
						"form_name" =>'myForm',
						"size" => 15,
						"options" => $les_champs,
                        "option_tips" => $les_tips,
						"left_title" => "<span class='texteGrisBold'>Authorised $min_na_label</span>",
						"right_title" => "<span class='texteGrisBold'>Blacklisted $min_na_label</span>",
						"right_ids" => $right_ids,
						"css_style" => "width:300px;",
						"table_padding" => 5,
                        "dbl_click"        => true,	// 06/11/2006 - Modif. benoit
                        "show_modify_link" => false,
					)
				),
	
				// bouton submit
				new FPRowLayout(
					array(
						"table_align" => "center",
						"table_padding" => 5,
						"elements" => array(
							new FPButton(
								array(
									"submit"	=> true,
									"name"	=> 'submit',
									"title"	=> '    Save   ',
									"css_class"	=> 'bouton',
								)
							),
						)
					)
				),
			)
		)
	)
);

$myForm->display();

?>


		</td>
	</tr>
</table>

