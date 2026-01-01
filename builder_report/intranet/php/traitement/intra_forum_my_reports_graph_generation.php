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
<?php
session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/environnement_graphe.php");
include_once($repertoire_physique_niveau0."graphe/jpgraph.php");
include_once($repertoire_physique_niveau0."graphe/jpgraph_bar.php");
include_once($repertoire_physique_niveau0."graphe/jpgraph_line.php");
include_once($repertoire_physique_niveau0."graphe/graphe_complement.php");

// gestion multi-produit - 21/11/2008 - SLC
include_once('../affichage/connect_to_product_database.php');

// initialisation du graphe
$graph_my_report = new Graph_Complement(0, 1, 0, 0, 0, 1, 0); //1 = contour sur le graphe, l'image est stockée
$graph_my_report->largeur_graphe = 600;
$graph_my_report->hauteur_graphe = 180;
$graph_my_report->nom_abscisse = "";
$graph_my_report->nom_ordonnee_gauche = "";
$graph_my_report->nom_ordonnee_droite = "";
$graph_my_report->position_legende = "top";
$graph_my_report->x_interval = 1;
$graph_my_report->x_orientation = 0;
$graph_my_report->flag_ordonnee_right = 0; //permet de savoir s'il faut créer une ordonnée droite au graphe
$graph_my_report->flag_ordonnee_left = 0; //permet de savoir s'il y a une ordonnée left au graphe
$graph_my_report->export_excel = 1;
$graph_my_report->Graph_Init();
// pour les besoins de la génération du tableau, on a séparé la première colonne (considérée comme l'abscisse) du reste des autres colonnes/
// pour les beoins du graphe, on réintègre l'ensemble dans tableau_data.
$graph_my_report->tableau_data = $tableau_data_export_excel[0]; //0 est l'identifiant pour les données du rapport
array_unshift($graph_my_report->tableau_data, $tableau_abscisse_export_excel[0]);
// selectionne les paramètres du graphe
// traite d'abord le cas de l'abscisse
$query_abscisse = "SELECT data_abscisse from forum_data_queries where (id_query=$id_query)";
$resultat_abscisse = $db_prod->getall($query_abscisse);
$graph_my_report->tableau_abscisse = null;

if ($resultat_abscisse)
	foreach ($resultat_abscisse as $row_abscisse)
		if ($row_abscisse["data_abscisse"] == "yes")
			$graph_my_report->tableau_abscisse = $graph_my_report->tableau_data[$compteur_abscisse];

$graph_my_report->Graph_generalities_and_abscisse();
// traite l'ensemble des données
$query = "SELECT data_name, data_abscisse, data_display_type, data_color, data_position from forum_data_queries where (id_query=$id_query) ORDER BY id_data_query ASC";
$resultat_query = $db_prod->getall($query);

if ($resultat_query) {
	$i = 0;
	foreach ($resultat_query as $row) {
		if ($row["data_abscisse"] != "yes") { // on ne doit pas afficher la donnée qui sera l'abscisse (s'il y en a une)
			$graph_my_report->data_display_type[$i]		= $row["data_display_type"];
			$graph_my_report->data_color[$i]				= $row["data_color"];
			$graph_my_report->data_position_ordonnee[$i]	= $row["data_position"];
			$graph_my_report->data_legend[$i]				= $row["data_name"];
			$graph_my_report->Graph_Line_bar_creation($i, $i);
			$i++;
		} 
	} 
}

// Generation du graphe
$graph_my_report->Graph_Display();

?>
