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
/*
Page gérant le Report builder
	
	la page est composée de 2 frames :
		
		+ un menu deroulant a gauche : contenu_database (builder_report_queries_database.php)
		
		+ la partie central de la page contenant les onglets pour naviguer entre la création de requéte et l'affichage sous forme de tableau ou de graphe :
		gestion_equation_sql  (report_onglet.php)
*/
session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
//include_once($repertoire_physique_niveau0."php/menu_contextuel.php");
include_once($repertoire_physique_niveau0."intranet_top.php");
$lien_css=$path_skin."easyopt.css";

// gestion multi-produit - 21/11/2008 - SLC
include_once('connect_to_product_database.php');

?>
<html>
<head>
<title>My Reports</title>
<script src="<?=$niveau0?>js/gestion_fenetre.js" ></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">
</head>
<table width="100%" border="0" height="79%" cellspacing="0" cellpadding="3">	
	<tr>
		<td width="20%">
			<table height="100%" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<iframe name="liste_requetes" width="100%" height="100%" frameborder="0" src="my_reports_queries_list.php?numero_label=0&product=<?=$product?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
						</iframe>
					</td>
				</tr>
			</table>
		</td>
		<td width="80%">
			<table height="100%" width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td valign="top" align="center">
						<iframe name="my_reports_onglet" width="100%" height="100%" frameborder="0" src="my_reports_onglet.php?display=0&product=<?=$product?>" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
						</iframe>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</body>
</html>
