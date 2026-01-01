<?
/*
*	@cb50000@
*
*	27/07/2009 - Copyright Astellia
*
*	27/07/2009 BBX : adaptation CB 5.0
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
/* 20/07/2006 - MD - creation du fichier
  *
  */
session_start();
include_once dirname(__FILE__).'/../../../../php/environnement_liens.php';
include_once(REP_PHYSIQUE_NIVEAU_0.'class/select_family.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/bh_functions.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/option_activator.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'intranet_top.php');

// Sélection du produit
if(!isset($_GET['product'])) {
	new select_family(basename(__FILE__), '', 'connection settings', false, '', 2);
	exit;
}

// 26/11/2009 BBX
// Si le produit n'est pas horaire, on ne permet pas l'accès à l'interface. BZ 13026
// 11/12/2009 : Ajout d'un lien back. BZ 13338
if(!array_key_exists('hour',getTaList('', $_GET['product']))) 
{
        // 11/10/2011 BBX
        // BZ 20664 pas de retour si monoproduit
	echo '<div class="errorMsg">'.__T('A_SETUP_BH_FEATURE_DISABLED');
        if(count(ProductModel::getActiveProducts()) > 1)
            echo ' | <a href="'.basename(__FILE__).'">Back</a></div>';
	exit;
}
// FIN BZ 13026

// Sélection famille
if(!isset($_GET['family'])) {
	new select_family(basename(__FILE__), $_SERVER['argv'][0], 'Busy Hour');
	exit;
}

// Récupération famille / produit
$family = $_GET['family'];
$product = $_GET['product'];

// Instance de connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);

?>
<script type="text/javascript" src="<?=NIVEAU_0?>js/bh.js"></script>
	<?
	if(bhIsDeployed())
	{
		// On affiche l'interface de definition de la BH
		?>
		<iframe name="setup_bh_definition_interface" src="setup_bh_definition_interface.php?family=<?=$family?>&product=<?=$product?>" leftmargin="5px" topmargin="5px" marginwidth="0" marginheight="0" frameborder="0" height="430" scrolling="auto" width="100%"></iframe>
		<?
	} 
	else 
	{
		// BH a activer (deploiement des tables en base)
		$bh_title = 'Busy Hour - Activation';
		//$bh_info="Click on the button below to activate Busy Hour";
		//$bh_info="Please, press the button below to activate busy hour";
		$bh_info = '';
		$img_title = NIVEAU_0.'images/titres/busy_hour_activation.gif';
		$img_loading = NIVEAU_0.'images/animation/indicator_snake.gif';
		// On deploie les tables pour toutes les familles
		$script = NIVEAU_0.'scripts/bh_management.php?action=deploy&family=all&product='.$product;
		$url = $_SERVER['PHP_SELF'];
		if(isset($_GET["family"]))
			$url = $_SERVER['PHP_SELF']."?family={$family}&product={$product}";

		$setup_bh_activate_ihm = new option_activator($bh_title,$img_title,$bh_info,$script,$url,$img_loading);
		$setup_bh_activate_ihm->display();
	}
	?>
	</body>
</html>
