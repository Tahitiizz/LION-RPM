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

// gestion multi-produit - 21/11/2008 - SLC
include_once('connect_to_product_database.php');

?>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
<?php
function affiche_over_view($type_statistique, $id_user)
{
    if ($type_statistique == "Private")
        $clause_where = " and id_user=" . $id_user;
	$path = "../../../../images/icones/puce_fieldset.gif";
    print("
				<fieldset>
				<legend class='texteGrisBold' valign=middle>&nbsp;<img src='$path'/>&nbsp;$type_statistique Overview&nbsp;</legend>
				<table width=100% height=100% border=0 cellpadding=2 cellspacing=2>
					<tr>
						<td width='33%' align=center valign=top > ");
    $query = "select * from report_builder_save  where on_off=1 " . $clause_where . " order by nbr_utilisation desc";
    afficher_tableau_resultat($query, $type_statistique, 'Most used queries', 'nbr_utilisation');
    print("				</td>
						<td width='33%' align=center valign=top >");
    $query = "select * from report_builder_save where on_off=1 " . $clause_where . " order by date_derniere_utilisation desc";
    afficher_tableau_resultat($query, $type_statistique, 'Last used queries', 'date_derniere_utilisation');
    print("				</td>
						<td width='33%' align=center valign=top >");
    $query = "select * from report_builder_save  where on_off=1 " . $clause_where . " order by oid desc";
	afficher_tableau_resultat($query, $type_statistique, 'Last created queries', 'date_creation');
    print("				</td>
					</tr>
				</table>
				</fieldset>
	");
}

function afficher_tableau_resultat($query, $type_statistique, $title)
{
    global $id_user,$image_blank,$niveau0,$product,$db_prod;

    $resultat = $db_prod->getall($query);
    $fini = false; // vaut true il n'y a plus d autre requete a afficher
    print ("
	<fieldset>
	<legend class='texteGrisBold' valign=middle>&nbsp;$title&nbsp;</legend>
	<table  width=210 border=0 align=top  height=75% cellpadding=2 cellspacing=2>
		"); // on affiche le titre de la statistique
    for($compteur = 0;$compteur < 5 ;$compteur++) { // on affiche que 5 requetes
        if (!$fini) // si il reste des requete
            if (!($query = array_shift($resultat))) // on essaye de récupérer la suivante
                $fini = true; // si on n'y arrive pas fini=true
            if (!$fini) { // si il reste des requetes a afficher
                $id_query = $query["id_query"]; // on récupére l'id de la requete
                $information_fenetre_volante = generer_fenetre_volante($id_query); // on récupére les information necessaire a l'affichage de la fenetre volante ( titre + message
                //$type_donnees = determine_type_donnees($query["requete"]); // on détérmine le type de données concernée par la query
				$family_info=get_family_information_from_family($query["family"],$product);
				$family_label=$family_info['family_label'];
				if ($id_user == $query["id_user"]) { // si la requete appartient a lutilisateur

                    ?>
		<tr>
			<td width='100%' align=left>
			<!-- on affiche uniquement le nom de la requete avec le type de donnée concerné
			onclick(..) : lorsque l'on clique sur la requete on affiche le resultat dans un tableau
			onMouseOver(titre,message,couleur) : on affiche une fenetre apportant des précisions sur la requete , le message peut contenir du code html
			-->
			<a href="#" class=texteGris  onMouseOut="kill()"  onClick="self.location='my_reports_onglet.php?show_onglet=1&product=<?=$product?>&onglet=1& id_query=<?php echo $id_query;

                    ?>'; " onMouseOver="pop('<?php echo $information_fenetre_volante["titre"];

                    ?>','<?php echo $information_fenetre_volante["message"];

                    ?>','#EFEFEF')"><font class='texteGrisBold'><?php echo $query["texte"];

                    ?></font></a><font class='texteGris'><?php echo "-" . $family_label;

                    ?></font>
			</td>
		</tr>
			<?php
                } else { // si la requete n'appartient pas a lutilisateur

                    ?>
		<tr>
			<td align=left>
			<!--
			la requete n'appartenant pas a l'utilisateur un affiche un petit icone + vert permettant d'un clique a ajouter la requete a sa liste
			( le clique sur l'icone rappelle la liste des requetes sauvegardé en passant comme parametre : id_query_to_add=id_query )

                        03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
                        -->

			<img align=absmiddle onmouseover="style.cursor='pointer';" src="<?=$niveau0?>images/icones/petit_plus.gif" onClick="parent.frames['liste_requetes'].location='my_reports_queries_list.php?product=<?=$product?>&id_query_to_add=<?php echo $id_query;

                    ?>'; ">
			<!-- on affiche uniquement le nom de la requete avec le type de donnée concerné
			onclick(..) : lorsque l'on clique sur la requete on affiche le resultat dans un tableau
			onMouseOver(titre,message,couleur) : on affiche une fenetre apportant des précisions sur la requete , le message peut contenir du code html
			-->
			<a href="#"  onMouseOut="kill()"  onClick="self.location='my_reports_onglet.php?show_onglet=1&product=<?=$product?>&onglet=1& id_query=<?php echo $id_query;

                    ?>'; " onMouseOver="pop('<?php echo $information_fenetre_volante["titre"];

                    ?>','<?php echo $information_fenetre_volante["message"];

                    ?>','#EFEFEF')"><font class='texteGrisBold'><?php echo $query["texte"];

                    ?></font></a><font class='texteGris'><?php echo "-" . $type_donnees;

                    ?></font>
			</td>
		</tr>
			<?php
                }
            }
        }
        if (($query = array_shift($resultat))) { // si il reste des requetes a afficher apres les 5 ..

            ?>
		<tr>
			<td align=left width='100%'>
			<!--
			On affiche un lien permettant d ouvrir un pop up contenant plus de résultat
			-->
			<a href="#"  onClick="ouvrir_fenetre('my_reports_affichage_detail_overview.php?type_affichage=<?=$type_statistique?>&product=<?=$product?>&title=<?=$title?>','Error','yes','no',400,500);"><font class='texteGrisBold'>See more ...</font></a>
			<?php
            echo "
			</td>
		</tr>";
        }
        echo "
		<tr>
			<td>
			</td>
		</tr>
	</table></fieldset>";
    }
?>

<body  bgcolor="<?=$couleur_fond_page?>" >
<table width="100%" height="100%" border=0 cellspacing="4" cellpadding="2" >
	<tr height=100>
		<td align=center>
			<?
			affiche_over_view("Private", $id_user); // on affiche en premier les statistiques concernant les requetes personnelles de l'utilisateur
			?>
		</td>
	</tr>
	<tr height=100>
		<td align=center>
			<?
			affiche_over_view("Global", $id_user); // et ensuite les statistiques concernant toutes les requetes
			?>
		</td>
	</tr>
</table>
</body>
</html>
