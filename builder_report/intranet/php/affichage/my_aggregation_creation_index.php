<?
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
<?
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
$lien_css=$path_skin."easyopt.css";
$family=$_GET['family'];

// gestion multi-produit - 21/11/2008 - SLC
include_once('connect_to_product_database.php');

?>
<html>
<head>
<title>Network aggregation creation</title>
<script src="<?=NIVEAU_0?>js/gestion_fenetre.js" ></script>
<script src="<?=NIVEAU_0?>js/fonctions_dreamweaver.js"></script>
<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" type="text/css">
</head>
<body leftmargin="4" topmargin="2" >
<table width="100%" border="0" height="100%" cellspacing="0" cellpadding="0" align="center">
  <tr>
     <td valign="top" align="center" width="30%">
       <iframe name="network_agregation_liste" width="100%" height="100%" frameborder="0" src="my_agregation_list.php?family=<?=$family?>&product=<?=$product?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" >
       </iframe>
    </td>      
    <td valign="top" align="center" width=70% height="100%">
		<iframe name="contenu_graph_table" width="100%" height="100%" frameborder="0" src="my_aggregation_cell_selection.php?family=<?=$family?>&product=<?=$product?>&id_network_agregation=<?=$id_network_agregation?>" scrolling="no" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
		</iframe>
    </td>
  </tr>
</table>
</body>
</html>
