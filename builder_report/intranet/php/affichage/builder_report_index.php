<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
* 	- 19/11/2008 - SLC - ajout de &product= dans l'url des <iframe>
*	- 25/11/2008 - SLC - gestion multi-produit
*	- 10/07/2009  - MPR : Correction du BZ10556 - Affichage du menu contextuel
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
* 	- 08/01/2008 Gwénaël : modification du titre dans lors du choix de la famille "Group Table" par "Query Builder"
* 
*/
?><?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?><?
//	Page gérant le Report builder
//	la page est composée de 2 frames :
//		+ un menu deroulant a gauche : contenu_database (builder_report_queries_database.php)
//		+ la partie central de la page contenant les onglets pour naviguer entre la création de requéte et l'affichage sous forme de tableau ou de graphe :
//		gestion_equation_sql  (report_onglet.php)
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");
$family=$_GET["family"];

// gestion multi-produit - 20/11/2008 - SLC
include_once('connect_to_product_database.php');

?>

<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>

<?
	// modif 08/01/2008 Gwénaël
		// Modification du 4) paramètre "Group Table" par "Query Builder"
	if(!isset($_GET["family"])){
		$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Query Builder');
		exit;
	}
	
	// maj 10/07/2009  - MPR : Correction du BZ10556 - Affichage du menu contextuel
	$id_menu_encours = $_GET['id_menu_encours'];

	// Etape 1 : Récupération des actions à insérer dans le menu contextuel
    // 25/01/2012 : bz25634, gestion des menu contextuels sur le Master
    $dbMaster = Database::getConnection( ProductModel::getIdMaster() );
	$res      = $dbMaster->getAll( "SELECT id FROM menu_contextuel WHERE type_pauto = 'query_builder'" );
	
	// Etape 2 : On insère les deux actions New Formula et New Agregation dans le menu_deroulant_intranet Query_builder
	if( count( $res ) > 0 )
    {
		foreach( $res as $k => $row )
		{
			$list_action []= $row['id'];
		}
		
		$query = "UPDATE menu_deroulant_intranet 
                    SET liste_action = '".implode("-", $list_action)."'
                    WHERE id_menu = '{$id_menu_encours}'";
		$dbMaster->execute($query);
	}	
?>

<table width="100%" border="0" height="79%" cellspacing="0" cellpadding="3" id="layoutTable">
	<tr>
		<td width="25%">
			<iframe id="contenu_database" name="contenu_database" width="100%" height="100%" frameborder="0" src="builder_report_queries_database.php?id_menu_encours=<?=$id_menu_encours?>&numero_label=0&family=<?=$family?>&product=<?=$product?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
			</iframe>
		</td>
		<td>
			<iframe id="gestion_equation_sql" name="gestion_equation_sql" align="left" width="100%" height="100%" frameborder="0" src="builder_report_onglet.php?id_menu_encours=<?=$id_menu_encours?>&family=<?=$family?>&product=<?=$product?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
			</iframe>
		</td>
	</tr>
</table>

<script type='text/javascript'>
function adaptLayoutToWindow() {
	var myTableHeight = document.viewport.getHeight() - $('layoutTable').offsetTop - 10;
	$('layoutTable').style.height = myTableHeight + 'px';
	myTableHeight = myTableHeight -2;
	$('contenu_database').style.height = myTableHeight + 'px';
	$('gestion_equation_sql').style.height = myTableHeight + 'px';
}

adaptLayoutToWindow();
</script>	

</body>
</html>
