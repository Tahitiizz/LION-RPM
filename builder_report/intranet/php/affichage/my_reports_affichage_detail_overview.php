<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
*       - 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
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
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/environnement_donnees.php");
include_once($repertoire_physique_niveau0."php/my_reports_fonction.php");
include_once($repertoire_physique_niveau0."php/edw_function_family.php");
$lien_css = $niveau0."css/global_interface.css";

// gestion multi-produit - 21/11/2008 - SLC
include_once('connect_to_product_database.php');

function affiche_over_view($type_affichage,$title)
{
global $id_user;
if ($type_affichage=="Private")
$clause_where=" and id_user=".$id_user;
print("
	<table width=100% height=100%  >
		<tr >
			<td width='100%' align=center>
				<strong><font size='2' face='Arial, Helvetica, sans-serif'>$type_affichage Overview </font>
				<table width=100% height=100%  >
					<tr>");
						if($title=="Most used queries")
							{
							$query="select * from report_builder_save  where on_off=1 ".$clause_where." order by nbr_utilisation desc";
							}

						if($title=="Last used queries")
							{
							$query="select * from report_builder_save where on_off=1 ".$clause_where." order by date_derniere_utilisation desc";
							}

						if($title=="Last created queries")
							{
							$query="select * from report_builder_save  where on_off=1 ".$clause_where." order by oid desc";
							}
						?>
						<td width='100%' align=center valign=top>
						<?afficher_tableau_resultat($query,$type_affichage,$title);?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?
}

function afficher_tableau_resultat($query,$type_affichage,$title)
{
global $id_user, $image_blank, $niveau0, $product, $db_prod;

$resultat=$db_prod->getall($query);											// on récuper la liste des requetes dans la base
$fini=false;															// vaut true il n'y a plus d autre requete a afficher
print ("
	<table class=table_with_frame_around  width=300>
		<tr><td align=center colspan=2 bgcolor=\"#AAAAAA\">
			<font class='texteGrisBold'>$title</font>
		</tr></td>");												// on affiche le titre de la statistique
for ($compteur=0;$compteur<15;$compteur++)							//on affiche que 5 requetes
	{
	if (!$fini)															// si il reste des requete
		if (!($query=$resultat[$compteur]))							//on essaye de récupérer la suivante
			$fini=true;												// si on n'y arrive pas fini=true

	if(!$fini)															// si il reste des requetes a afficher
		{
		$id_query=$query["id_query"];								// on récupére l'id de la requete
		$type_donnees=determine_type_donnees($query["requete"]);		// on détérmine le type de données concernée par la query
		$family_info=get_family_information_from_family($query["family"],$product);
		$family_label=$family_info['family_label'];
		$information_fenetre_volante=generer_fenetre_volante($id_query);	// on récupére les information necessaire a l'affichage de la fenetre volante ( titre + message)
		if($id_user==$query["id_user"])									// si la requete appartient a lutilisateur
			{
			?>
			<tr><td  align=left width=30>&nbsp;
			</td><td width='100%' align=left>
			<!-- on affiche uniquement le nom de la requete avec le type de donnée concerné
			onclick(..) : lorsque l'on clique sur la requete on affiche le resultat dans un tableau
			onMouseOver(titre,message,couleur) : on affiche une fenetre apportant des précisions sur la requete , le message peut contenir du code html
			-->
			<a href="#" onClick="opener.location='my_reports_onglet.php?show_onglet=1&product=<?=$product?>&onglet=1 & id_query=<?echo $id_query;?>';"><font class='texteGrisBold'><?echo  $query["texte"];?></font></a><font class='texteGris'><?echo "-".$family_label;?></font>
			</td></tr>
			<?
			}
		else 				// si la requete n'appartient pas a lutilisateur
			{
			?>
			<tr><td  align=left width=30>
			<!--
			la requete n'appartenant pas a l'utilisateur un affiche un petit icone + vert permettant d'un clique a ajouter la requete a sa liste
			( le clique sur l'icone rappelle la liste des requetes sauvegardé en passant comme parametre : id_query_to_add=id_query )

                        maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
                        -->
			<img align=absmiddle onmouseover="style.cursor='pointer';" src="<?=$niveau0?>images/icones/petit_plus.gif" onClick="opener.parent.frames['liste_requetes'].location='my_reports_queries_list.php?id_query_to_add=<?echo $id_query;?>&product=<?=$product?>'; ">
			</td><td width='100%' align=left>
			<!-- on affiche uniquement le nom de la requete avec le type de donnée concerné
			onclick(..) : lorsque l'on clique sur la requete on affiche le resultat dans un tableau
			onMouseOver(titre,message,couleur) : on affiche une fenetre apportant des précisions sur la requete , le message peut contenir du code html
			-->
			<a href="#" onClick="opener.location='my_reports_onglet.php?show_onglet=1&product=<?=$product?>&onglet=1&id_query=<?echo $id_query;?>'; "><font class='texteGrisBold'><?echo  $query["texte"];?></font></a><font class='texteGris'><?echo "-".$family_label;?></font>
			</td></tr>
			<?
			}
		}
	}
echo "</table>";
}
?>
<html>
<head>
<style>
{
background-color : #DDDDDD;
color : #111111;
font-family : arial;
font-size : 8pt;
border-width : 1px;
border-color : #AAAAAA;
border-style : dotted;
}
</style>
<script src="<?=$niveau0?>js/gestion_fenetre.js" ></script>
<script src="<?=$niveau0?>js/builder_reportv3.js" ></script>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">

</head>
<body  bgcolor="#EEEBE4" >
<table width=100% height=100% border=0 cellspacing="0">
	<tr height=50%>
		<td width="100%">
			<?
			affiche_over_view($type_affichage,$title);			// on affiche le tableau souhaité
			?>
		</td>
	</tr>
</table>
</body>
</html>
