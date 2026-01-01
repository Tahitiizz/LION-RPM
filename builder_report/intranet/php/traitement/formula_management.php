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
include_once("../../../../php/environnement_liens.php");
include_once($niveau4_vers_php."database_connection.php");
include_once($niveau4_vers_php."environnement_nom_tables.php");
include_once($niveau4_vers_php."edw_function.php");

// gestion multi-produit - 21/11/2008 - SLC
include_once('../affichage/connect_to_product_database.php');

switch ($action) //$action est un parametre passe par l'URL d'appel
	{

        CASE "delete":    //mets un jours à KPI
	
		//recherche si la valeur new_field est à 1. Ce qui signifie que les scripts d'agregation ne l'ont pas encore pris en compte
		//si new_field vaut 1 alors on delete le KPI sinon on met new field à 2 et le KPI sera effacé lors de l' aggregation
		$query="SELECT new_field FROM $nom_table_generic_counter WHERE (id_ligne='$id_kpi')";
		$new_field = $db_prod->getone($query);

		if ($new_field==1) {
			$query="DELETE FROM $nom_table_generic_counter WHERE (id_ligne='$id_kpi')";
			$db_prod->execute($query);
			$query="DELETE FROM $nom_table_generic_counter WHERE (id_ligne_parent='$id_kpi')";
			$db_prod->execute($query);
		} else {
			//Au lieu de supprimer, on mets la valeur new_field à 2 et c'est le script daily qui vient effacer les données
			if(isset($edw_group_table) && $edw_group_table=='mixed') {
				$query="UPDATE $nom_table_generic_counter SET new_field=2,edw_group_table='edw_alcatel_0' WHERE (id_ligne='$id_kpi')";
			} else {
				$query="UPDATE $nom_table_generic_counter SET new_field=2 WHERE (id_ligne='$id_kpi')";
			}
			$db_prod->execute($query);
			$query="UPDATE $nom_table_generic_counter SET new_field=2 WHERE (id_ligne_parent='$id_kpi')";
			$db_prod->execute($query);
		}
		?>
		<script>
		 window.location="<?=$traitement_vers_affichage?>intra_myadmin_generic_counters_builder.php";
		 parent.kpi_list.location="<?=$traitement_vers_affichage?>intra_myadmin_generic_counters_table.php?edw_group_table=<?=$edw_group_table?>&product=<?=$product?>";
		</script>
		<?
        break;
	}

?>
