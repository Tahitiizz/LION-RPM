<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 01/08/2007, benoit : suppression de la condition sur la famille dans la requete de selection de             l'id_univers
* 	
* 	06/04/2009 - modif SPS : ajout d'id pour les iframes (utilise par la fonction javascript)
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

session_start();

include_once($repertoire_physique_niveau0."php/environnement_liens.php");
//include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/select_family.class.php");
$lien_css=$path_skin."easyopt.css";
global $niveau0;

$product = $_GET['product'];
$family	 = $_GET['family'];

?>
<html>
<head>
<title>Graph_Table Creation</title>
<script src="<?=$niveau0?>js/gestion_fenetre.js" ></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js" ></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">
<link rel="stylesheet" href="<?=$niveau0?>css/pauto.css" type="text/css"/>
</head>
<body>
<?php

// 24/11/2010 BBX
// Mixed KPI doit désormais accéder à cette IHM
// BZ 17911
/*
// 09/12/2009 BBX : le module Data Range Builder ne doit pas être accessible sur un produit Mixed KPI. BZ 13175
if(MixedKpiModel::isMixedKpi($product)) 
{
	echo '<div class="errorMsg">'.__T('A_SETUP_BH_FEATURE_DISABLED').' | <a href="'.basename(__FILE__).'">Back</a></div>';
	exit;
}*/

// 24/11/2010 BBX
// On vérifie que sys_pauto_family est remplie
// BZ 17911
if(MixedKpiModel::isMixedKpi($product))
{
    $dbMk = Database::getConnection($product);
    $query = "SELECT * FROM sys_pauto_family";
    $dbMk->execute($query);
    if($dbMk->getNumRows() == 0)
    {
        // On remplie alors la table en fonction du master
        $dbMaster = Database::getConnection();
        $tableMaster = $dbMaster->getTable("sys_pauto_family");
        $dbMk->setTable("sys_pauto_family", $tableMaster);
    }
}

if (!isset($_GET["family"])) {
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Data range');
	exit;
}

// connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db_prod = Database::getConnection($product);

// 01/08/2007 - Modif. benoit : suppression de la condition sur la famille dans la requete de selection de l'id_univers
$query="SELECT id_univers FROM sys_pauto_univers WHERE id_pauto='data_range'";// and family='$family' ";
$id_univers = $db_prod->getone($query);

?>

	<table width="100%" border="0" height="100%" cellspacing="0" cellpadding="3" id="layoutTable">
		<tr>
			<td width="45%">
				<table height="100%" width="100%" cellspacing="0" cellpadding="0" align="left" border="0">
					<tr>
						<td><iframe id="acuriotree" name="acuriotree" width="100%" height="100%" frameborder="0" src="pautotree.php?product=<?= $product ?>&family=<?=$family?>&id_univers=<?=$id_univers?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0"></iframe></td>
					</tr>
				</table>
			</td>
			<td>
				<table height="100%" width="100%" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td valign="middle" align="center">
							<iframe id="pageframe" name="pageframe" width="100%" height="100%" frameborder="0" src="pageframe_range.php?product=<?= $product ?>&family=<?=$family?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0"></iframe>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>


<script type='text/javascript'>
	function adaptLayoutToWindow() {
		var myTableHeight = document.viewport.getHeight() - $('layoutTable').offsetTop - 10;
		$('layoutTable').style.height = myTableHeight + 'px';
		myTableHeight = myTableHeight -2;
		$('acuriotree').style.height = myTableHeight + 'px';
		$('pageframe').style.height = myTableHeight + 'px';
	}
	
	adaptLayoutToWindow();
</script>

</body>
</html>
