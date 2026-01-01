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

// gestion multi-produit - 21/11/2008 - SLC
//include_once('../affichage/connect_to_product_database.php');


// 02/06/2006 - Modif. BA : verification de l'intégrité du nom de la liste avant sauvegarde
function save_agregation($nom , $cell_liste)
{
	global $id_user,$family,$product,$db_prod;
	$cell_liste =explode(",",$cell_liste);
        // 07/06/2011 BBX -PARTITIONING-
        // Correction échappement quotes
	$cell_liste ="(''".implode("'',''",$cell_liste)."'')";
	//$cell_liste=addslashes($cell_liste);
	$agregation_name = $nom;

	if(ereg("[^[:space:]a-zA-Z0-9*_.-]", $agregation_name)){
		echo "<script>alert('Special chars are not allowed')</script>";
	}
	else if(trim($agregation_name) == ""){
		echo "<script>alert('The name of your list is empty')</script>";
	}
	else 
	{
		$query="INSERT INTO my_network_agregation (id_user,agregation_name,cell_liste,family ) VALUES ('$id_user','$agregation_name', '$cell_liste','$family')";
		$db_prod->execute($query) or die($query);	
	}
}

// 02/06/2006 - Modif. BA : ajout de la maj de la liste de la chaine de requete dans la table 'report_builder_save' 
// 02/06/2006 - Modif. BA : verification de l'intégrité du nom de la liste avant maj

function modify_agregation($id , $nom , $cell_liste)
{
	global $id_user,$product,$db_prod;
	
	$cell_liste			= explode(",",$cell_liste);
	$cell_liste			= "(''".implode("'',''",$cell_liste)."'')";
        // 07/06/2011 BBX -PARTITIONING-
        // Correction échappement quotes
	//$cell_liste			= addslashes($cell_liste);
	$agregation_name	= $nom;

	if(ereg("[^[:space:]a-zA-Z0-9*_.-]", $agregation_name)){
		echo "<script>alert('Special chars are not allowed')</script>";
	}
	else if(trim($agregation_name) == ""){
		echo "<script>alert('The name of your list is empty')</script>";
	}
	else 
	{
		// On selectionne l'ensemble des requetes de la famille de la liste ainsi que la liste elle-même
		$sql = "
			SELECT rb_save.requete, my_na.cell_liste, rb_save.id_query
			FROM report_builder_save rb_save, my_network_agregation my_na
			WHERE my_na.id_network_agregation=$id AND my_na.family=rb_save.family";
		$req = $db_prod->getall($sql);

		// On recherche toutes les requetes où la liste à modifier est présente
		if ($req) {
			foreach ($req as $row) {
				if (!(strpos($row[0], $row[1]) === false)) {
					// Si la liste est présente, on recompose la chaine en remplacant l'ancienne liste par la nouvelle
					$requete_rb_deb = substr($row[0], 0, strpos($row[0], $row[1]));
					$requete_rb_fin = substr($row[0], strpos($row[0], $row[1])+strlen($row[1]));
	
					$new_req_rb = addslashes($requete_rb_deb.stripslashes($cell_liste).$requete_rb_fin);
	
					// On effectue la maj de la query dans la table 'report_builder_save'
					$sql2 = "UPDATE report_builder_save SET requete='$new_req_rb' WHERE id_query={$row[2]}";
					$db_prod->execute($sql2) or die($sql2);
				}
			}
		}

		// On effectue dans tous les cas, la maj de la table 'my_network_agregation'
		$query = "UPDATE my_network_agregation SET agregation_name='$agregation_name', cell_liste='$cell_liste' WHERE id_network_agregation=$id";
		$db_prod->execute($query) or die($query);
	}
}

// 02/06/2006 - Modif. BA : ajout d'une procedure de verification de la non utilisation d'une liste dans des requetes de sa famille avant suppression. Si celle-ci est utilisée alors on ne la supprime pas et on en informe l'utilisateur 
function drop_agregation($id_agregation_to_drop)
{

	global $product,$db_prod;
	
	// On selectionne l'ensemble des requetes de la famille de la liste ainsi que la liste elle-même
	$sql = "SELECT rb_save.requete, my_na.cell_liste FROM report_builder_save rb_save, my_network_agregation my_na WHERE my_na.id_network_agregation=$id_agregation_to_drop AND my_na.family=rb_save.family";
	$req = $db_prod->getall($sql);

	$list_find = false;	// Booleen indiquant la presence ou non de la liste dans au moins une des requetes
	
	if  ($req)
		foreach ($req as $row)
			if (!(strpos($row[0], $row[1]) === false))
				$list_find = true;

	if ($list_find) {	// Si la liste existe dans une des requetes, pas de suppression
		echo "<script>alert('This List is used in queries and cannot be dropped')</script>";
	}
	else 
	{
		$query = "delete  from my_network_agregation where id_network_agregation=".$id_agregation_to_drop;
		$db_prod->execute($query) or die($query);
	}
}

?>
