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

// gestion multi-produit - 21/11/2008 - SLC
$family=$_GET["family"];
$product=$_GET["product"];
include_once('connect_to_product_database.php');

?>
<html>
<head>
 <title>Formula Builder</title>
</head>
<body>
<table width="100%"  height="100%" border="0" cellspacing="0" cellpadding="0"  leftmargin="5"   topmargin="5" >
  <tr valign="top">
    <td width="25%">
       <iframe name="row_data" width="100%" height="100%" frameborder="0" src="formula_list_easyoptima.php?family=<?=$family?>&product=<?=$product?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
       </iframe>
    </td>
    <td width="57%">
       <iframe name="kpi_builder" width="100%" height="100%" frameborder="0" src="formula_builder.php?family=<?=$family?>&product=<?=$product?>" scrolling="no" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" >
       </iframe>
    </td>
      <td width="18%">
       <iframe name="kpi_list" width="100%" height="100%" frameborder="0" src="formula_table.php?family=<?=$family?>&product=<?=$product?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
       </iframe>
    </td>
  </tr>
</table>
</body>
</html>
