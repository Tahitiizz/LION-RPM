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
include_once('../affichage/connect_to_product_database.php');

//collecte des données du formulaire de choix des paramètres du graphe
$save_and_display=$_POST["info_save"] ; //information qui permet de savoir si on a cliqué sur "display" ou "Save and Display"

//valeur numérique pour l'abscisse qui permet de déterminer la donées en abscisse.
//il se peut que l'utilisateur ne choisisse aucun abscisse dans ce cas la valeur est vide

$builder_report_abscisse= $_POST["abscisse"];
if ($builder_report_abscisse=="")
	$builder_report_abscisse=10000; //valeur bidon - Important car si on garde "", un teste avec 0 donne une égalité alors que ce n'est pas le cas

//collecte les paramètres du graphe
for ($i=0;$i<$nombre_donnees_graphe;$i++) {
	$builder_report_graph_legend[$i]		= $_POST["entete$i"] ;
	$builder_report_graph_data_type[$i]	= $_POST["graphe_type$i"] ;
	$builder_report_graph_data_color[$i]	= $_POST["color_data$i"] ;
	$builder_report_graph_data_position[$i]	= $_POST["position$i"] ;
}

//teste si les données doivent être sauvegardée
if ($save_and_display=='save') {
	$query="DELETE FROM forum_data_queries where id_query='$id_query'"; //plutôt que de mettre à jour, on efface les donnée - c'est dans ce cas, plus simple
	$db_prod->execute($query);
	for ($i=0;$i<$nombre_donnees_graphe;$i++) {
		if ($builder_report_abscisse==$i)
			{ $valeur_abscisse="yes"; }
		else	{ $valeur_abscisse="no"; }
		$data_type	= $builder_report_graph_data_type[$i];
		$data_color	= $builder_report_graph_data_color[$i];
		$data_position	= $builder_report_graph_data_position[$i];
		$query="INSERT into forum_data_queries (id_query, data_name, data_abscisse, data_display_type, data_color, data_position) VALUES ('$id_query','$tableau_entete[$i]','$valeur_abscisse','$data_type','$data_color','$data_position')";
		$db_prod->execute($query);
	}
}
//utilise des variable de sessions pour les utiliser dans intra_forum_builder_report_graph_generation
session_register("builder_report_graph_legend");
session_register("builder_report_graph_data_type");
session_register("builder_report_graph_data_color");
session_register("builder_report_graph_data_position");
session_register("builder_report_abscisse");
?>
