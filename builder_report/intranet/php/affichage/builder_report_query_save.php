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
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/php2js.php");
// include_once($repertoire_physique_niveau0."php/database_connection.php");
$lien_css=$path_skin."easyopt.css";
$family=$_GET['family'];

// gestion multi-produit - 20/11/2008 - SLC
include_once('connect_to_product_database.php');

//récupère l'id de la query dans un champ caché du formulaire
//l'id_query est différent de "" si l'utilisateur a fait un Drag&Drop d'une équation
if ($id_query!="")
    $query_name=$db_prod->getone("SELECT texte FROM report_builder_save WHERE id_query='$id_query'");
?>
<html>
<head>
<title>Query Save</title>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
</head>
<body class="tabPrincipal">
<? 
$url="../traitement/intra_forum_query_saving.php";
//les variables dans les champs caches sont des variables de sessions
?>
<form name="formulaire" method="post" action="<?=$url?>">
<!-- <input type='hidden' name='data' value='<?=$data_builder_report?>'> -->
<input type='hidden' name='requete' value='<?=htmlentities($requete_builder_report,ENT_QUOTES)?>'>
<input type='hidden' name='formula_ids' value='<?=$formula_ids_builder_report?>'>
<input type='hidden' name='id_query' value='<?=$id_query?>'>
<input type='hidden' name='family' value='<?=$family?>'>
<input type='hidden' name='product' value='<?=$product?>'>
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<font class="textegrisBold">Query : </font>
		</td>
		<td>
			<input class="iform" type="text" id="query_name" name="query_name" size="50"  value="<?echo $query_name?>" style="width:250px;">
		</td>
	</tr>
	<tr>
		<td  align="center"  valign="bottom" width=100%  height=25 colspan=2>
			<input align="center" type="submit" class="bouton" value="SAVE" >
		</td>
	</tr>
</table>
<table border=0 width=100%>
<?	 
if ($id_query!="")					// on modifie une requetedeja existante ? 
	{
		?>
	<tr>
		<td align=center width=50%>
			<INPUT CHECKED TYPE=RADIO NAME="remplacer" id="new_query" VALUE=1> <font class='texteGris'>New query
		</td>
		<td align=center width=50%>
			<INPUT TYPE=RADIO  id="remplacer"  NAME="remplacer" VALUE=0> <font class='texteGris'>Modify query
		</td>
	</tr>
		<?
	}
?>
</table>
</form>
</body>
</html>
