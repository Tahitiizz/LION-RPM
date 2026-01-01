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
include_once ($repertoire_physique_niveau0."php/environnement_liens.php");
include_once ($repertoire_physique_niveau0."php/database_connection.php");
include_once ($repertoire_physique_niveau0."php/environnement_nom_tables.php");

// gestion multi-produit - 21/11/2008 - SLC
include_once('../affichage/connect_to_product_database.php');

switch ($action) //$action est un parametre passe par l'URL d'appel
       {
        CASE "create":    //crée une formule
		$formula_name=$_POST["counter_formula"];
		$formula_equation=$_POST["zone_formule_numerateur"];
		$creation_date=date("d/m/Y"); //crée la date du jour;
		$query="INSERT INTO $nom_table_builder_report_formula (id_user, formula_type, formula_name, formula_creation_date, formula_equation) VALUES ('$id_user', 'Private', '$formula_name','$creation_date', '$formula_equation')";
		$db_prod->execute($query);
		?>
		<script>
		 window.close();
		 window.opener.location="<?=$traitement_vers_affichage?>intra_forum_builder_report_queries_database.php?numero_label=2&product=<?=$product?>";
		 </script>
		<?
        break;
		
        CASE "update":    //mets à jour la formule
		$formula_name=$_POST["counter_formula"];
		$formula_equation=$_POST["zone_formule_numerateur"];
		$modification_date=date("d/m/Y"); //crée la date du jour;
		$query="UPDATE $nom_table_builder_report_formula set formula_name='$formula_name', formula_creation_date='$modification_date', formula_equation='$formula_equation' WHERE id_formula='$id_formula'";
		print $query;
		$db_prod->execute($query);
		?>
		<script>
		 window.opener.top.contenu_database.location="<?=$traitement_vers_affichage?>intra_forum_builder_report_queries_database.php?numero_label=2&product=<?=$product?>";
		 window.close();
		 </script>
		<?
        break;
		
        CASE "drop":    //crée une formule
		$query="DELETE FROM $nom_table_builder_report_formula WHERE id_formula='$id_formula'";
		$db_prod->execute($query);
		?>
		<script>
		 window.opener.top.contenu_database.location="<?=$traitement_vers_affichage?>intra_forum_builder_report_queries_database.php?numero_label=2&product=<?=$product?>";
		 window.close();
		</script>
		<?
        break;
       }
?>
