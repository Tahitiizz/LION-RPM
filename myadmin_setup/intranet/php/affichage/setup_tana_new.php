<?
/*
*	@cb41000@
*
*	08/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	08/12/2008 BBX : modifications pour le CB 4.1
*	=> Utilisation de la classe DatabaseConnection
*	=> Utilisation des nouvelles constantes
*	=> Contrôle d'accès
*	=> Suppression des requête sur sys_selecteur_properties car cette table n'existe plus
*	=> Gestion du produit
*
*	17/09/2009 BBX : ajout de la fonction testNAcode() qui test le code NA avant de poster. BZ 10920
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
*	- maj 20/08/2007 Jérémy : 	Ajout de la récupération et du traitement des information du formulaire qui ont été saisi par l'utilisateur
*						Cette opération devient utile lors de retour au formulaire suite a une erreur dans le formulaire, sans enregistrement dans la base de données
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
/**
  * 02 10 2006 - MD
  * 	>> selection seulement des NA mandatory
  *	>> suppression du niveau Itself
  * - maj 22 11 2006 christophe : on affiche les NA dans l'ordre correspondant à la requête se trouvant dans sys_selecteur_properties.
  */
?>
<?
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes nécessaires
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class.phpObjectForms/lib/FormProcessor.class.php');


$product = $_GET['product'];

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Setup Network Aggregation'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Récupération des valeurs
$action=$_GET["action"];//1=new,2=update,3=info
$tana=$_GET["agregation"];
//$tana_type=$_GET["tana_type"];
$family = $_GET["family"];
$product = $_GET["product"];



// Connexion à la base de données du produit
// $database = new DatabaseConnection($product);


//20/08/2007 - Jérémy : Récupération d'information supplémentaires, et traitement des informations (ex les signes à reformater)
//Récupération des informations du formulaire
$tana_name 				= isset($_GET['tana_name']) ? $_GET['tana_name'] : "";
$tana_label_aff 		= isset($_GET['tana_label']) ? $_GET['tana_label'] : "";
$tana_source_default	= isset($_GET['tana_source_default']) ? $_GET['tana_source_default'] : "";
$tana_level_source 		= isset($_GET['tana_level_source']) ? $_GET['tana_level_source'] : "";
$tana_level_operand 	= isset($_GET['tana_level_operand']) ? $_GET['tana_level_operand'] : "";

//Récupération du signe (operand)
switch ($tana_level_operand){
	case "egal" :
		$tana_level_operand = "=";
		break;
	case "sup" :
		$tana_level_operand = ">";
		break;
	case "inf" :
		$tana_level_operand = "<";
		break;
}

//Récupération du label du NA SOURCE
$table = "sys_definition_network_agregation";
$tanatitre = "Network Aggregation";
$default_source[0] = "Make your selection";
$tana_level_agreg[0] = "Make your selection";

// Header
$arborescence = 'Network Aggregation';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>

<script type="text/javascript">
    /**
     * Test le code du NA saisi
     * 17/09/2009 BBX : vérifie le code NA. BZ 10920
     * 17/08/2010 OJT : Correction bz16848
     */
    function testNAcode()
    {
        var tanaValue = document.myForm.tana.value;
        var test1 = new RegExp("[^a-zA-Z0-9]","gi"); // Contient des caractères spéciaux
        var test2 = new RegExp("^[0-9]+","gi"); // Commence par des chiffres

        // Test sur les caractères spéciaux
        if( test1.test( tanaValue ) ){
            alert( 'The network aggregation code must contain only alphanumerical characters' );
            return false;
        }

        // Test sur les chiffres en début de code
        if( test2.test( tanaValue ) ){
            alert( 'The network aggregation code cannot begin with numeric values' );
            return false;
        }
        return true;
    }
</script>

<table width="100%"  border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td align="center">
			<img src="<?=NIVEAU_0?>images/titres/setup_na_interface_2.gif"/>
		</td>
	</tr>
	<tr>
		<td align="center">
			<table cellpadding="0" cellspacing="0" border="0" class="tabPrincipal">
				<? if(isset($_GET["erreur"])){ ?>
				<tr>
					<td class="texteRouge" align="center" style="padding:5px">
					<? 	if ($_GET["erreur"] == "error1"){
							echo __T('A_SETUP_NETWORK_AGGREGATION_NAME_ALREADY_USED');
						} elseif ($_GET["erreur"] == "error2"){
							echo __T('A_SETUP_NETWORK_AGGREGATION_MISSING_AGGREGATION_DEFAULT_SOURCE_AND_LEVEL_OFF');
					 	} else {
							echo __T('A_SETUP_NETWORK_AGGREGATION_NAME_FORBIDDEN');
					 	} ?>

					</td>
				</tr>
				<? } ?>
				<tr valign="top" align="center" height="100%">
					<td align="left">
					<?
						$fp = new FormProcessor(REP_PHYSIQUE_NIVEAU_0."class.phpObjectForms/lib/");
						$fp->importElements(array("FPButton", "FPSelect", "FPText", "FPTextField"));
						$fp->importLayouts(array("FPColLayout", "FPRowLayout", "FPGridLayout"));
						$fp->importWrappers(array( "FPLeftTitleWrapper" ));
						$leftWrapper = new FPLeftTitleWrapper(array(
							'table_title_cell_width' => 200,
							'table_field_cell_width' => 200,
						));

						$action = "setup_tana_update.php?tana_type=na&action_encours=".$action."&old_name=".$tana."&action=1&tana=".$tana."&family=".$family."&product=".$product;
						// Propriétés du formulaire.
						$myForm = new FPForm(array(
							//    "title" => 'Field selector',
							"name" => 'myForm',
							"action" => $action,
							"display_outer_table" => true,
							"enable_js_validation" => true,
							"table_align" => 'center',
							"onsubmit" => "testNAcode()"
						));


						// Eléments du formulaire.
						//--------------------------------------------------------------------------

						$formel_na_name = new FPTextField(array(
							"title" => '<span class=texteGris>Name </span>',
							"name" => 'tana',
							"value" => $tana_name,
							"required" => true,
							"valid_RE" => FP_VALID_TITLE,
							"wrapper" => &$leftWrapper,
						));

						$formel_na_label = new FPTextField(array(
							"title" => '<span class=texteGris>Aggregation label </span>',
							"name" => 'tana_label',
							"value" => $tana_label_aff,
							"required" => true,
							"valid_RE" => FP_VALID_NAME,
							"wrapper" => &$leftWrapper,
						));

						// SELECT
						// 30/04/2009 MPR : On affiche uniquement les niveaux d'agrégation 3ème axe
						$query = "SELECT agregation,agregation_label  FROM $table where on_off=1 and family='$family' and mandatory=1 and axe IS NULL ORDER BY agregation_rank desc";

						$res = $database->getAll($query);

						foreach ($res as $row) {
							$default_source[$row["agregation"]] = $row["agregation_label"];
						}

						//20/08/2007 - Jérémy : Le Selected attend un tableau, donc on place la valeur correspondante au label mémorisé dans un tableau
						$tana_source_default_value[] = $tana_source_default;
						$formel_default_source = new FPSelect(array(
							"name" => 'tana_source_default',
							"title" => '<span class=texteGris>Aggregation Default Source </span>',
							"multiple" => false,
							"options" => $default_source,
							"selected" => $tana_source_default_value,
							"required" => true,
							"wrapper" => &$leftWrapper,
						));

						//20/08/2007 - Jérémy : Idem pour le Selected
						$tab_default_operand[] = $tana_level_operand;
						$tab_operand['='] = '=';
						$tab_operand['>'] = '>';
						$tab_operand['<'] = '<';
						$formel_operand = new FPSelect(array(
							"name" => 'tana_level_agreg_operand',
							"title" => '<span class=texteGris>Aggregation Level </span>',
							"multiple" => false,
							"options" => $tab_operand,
							"selected" => $tab_default_operand,
							"required" => true,
							"wrapper" => &$leftWrapper,
						));

						// SELECT
						$query = "SELECT agregation,agregation_label FROM $table where on_off=1 and family='$family' and mandatory=1 and axe IS NULL ORDER BY agregation_rank desc";

						foreach ($database->getAll($query) as $row) {
							$tana_level_agreg[$row["agregation"]] = $row["agregation_label"];
						}

						//20/08/2007 - Jérémy : Idem pour le Selected
						$tana_level_source_value[] = $tana_level_source;
						$formel_tana_level_agreg = new FPSelect(array(
							"name" => 'tana_level_agreg',
							"title" => '<span class=texteGris>Aggregation level of </span>',
							"multiple" => false,
							"options" => $tana_level_agreg,
							"selected" => $tana_level_source_value,
							"required" => true,
							"wrapper" => &$leftWrapper,
						));


						$formel_submit_button = new FPButton(array(
							"submit" => true,
							//"title" => '<span class=texteGrisBold>Confirm >> </span>',
							"name" => 'submit',
							"title" => 'Save',
							"css_class" => 'bouton',
							//"wrapper" => &$leftWrapper,
						));


						// Affichage du formulaire ////////////////////////////////////////////////////:::
						$form_layout_2 = new FPRowLayout(array(
							"table_padding" => 10,
							"table_align" => 'right',
							"rows" => 2,
						));
						$form_layout_2->addElement(new FPText(array("text" =>'<span class=texteGrisBold>Confirm >> </span>')));
						$form_layout_2->addElement($formel_submit_button);


						$form_grid = new FPGridLayout(array(
							"table_padding" => 5,
							"columns" => 1,
						));

						$form_grid->addElement($formel_na_name);
						$form_grid->addElement($formel_na_label);
						$form_grid->addElement($formel_default_source);
						$form_grid->addElement($formel_operand);
						$form_grid->addElement($formel_tana_level_agreg);
						$form_grid->addElement($form_layout_2);
						//$form_grid->addElement($formel_submit_button);


						$form_layout = new FPColLayout(array("elements" => array(
							$form_grid,
						)));

						$myForm->setBaseLayout($form_layout);

						// On affiche le formulaire
						$myForm->display();
					?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<script>
</body>
</html>
