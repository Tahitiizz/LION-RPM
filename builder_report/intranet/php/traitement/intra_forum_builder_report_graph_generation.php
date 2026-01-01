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
// ??? ça va marcher les graphs en multi-produit ?

$graph_builder_report= new Graph_Complement(0,0,0,0,0,0,0,0,0);
$graph_builder_report->largeur_graphe=950;
$graph_builder_report->hauteur_graphe=350;
$graph_builder_report->nom_abscisse="";
$graph_builder_report->nom_ordonnee_gauche="";
$graph_builder_report->nom_ordonnee_droite="";
$graph_builder_report->position_legende="top";
$graph_builder_report->x_interval=1;
$graph_builder_report->x_orientation=45;
$graph_builder_report->flag_ordonnee_right=0; //permet de savoir s'il faut créer une ordonnée droite au graphe
$graph_builder_report->flag_ordonnee_left=0; //permet de savoir s'il y a une ordonnée left au graphe
$graph_builder_report->Graph_Init();

//gestion des données
$graph_builder_report->tableau_data=$tableau_data_export_excel[0]; //0 est l'identifiant pour les données du builder report

//défini le tableau des abscisse
if ($abscisse!="")
   {
    $graph_builder_report->tableau_abscisse=$graph_builder_report->tableau_data[$builder_report_abscisse];
   }
   else
   {
    //si l'utilisateur n'a choisi aucun abscisse, on met le tableau à vide
    $graph_builder_report->tableau_abscisse=null;
   }

$graph_builder_report->Graph_generalities_and_abscisse();
$graph->legend->Pos(0.5,0.05,"center","center"); //permet de remonter la légende un peu plus au que ce qu'il y a dans graphe_complement.php
$graph_builder_report->marge_espace_bas=55;
$graph_builder_report->data_display_type=$builder_report_graph_data_type;
$graph_builder_report->data_color=$builder_report_graph_data_color;
$graph_builder_report->data_filled_color=$builder_report_graph_data_color;
//parcoure le tableau des couleurs de fond qui par defaut est egal au couleurs de contour
foreach ($graph_builder_report->data_filled_color as $key=>$value_color)
		{
		 //si on est dans le cas d'une ligne alors on supprime la couleur de fond
		 if ($graph_builder_report->data_display_type[$key]=="line") {
		    $graph_builder_report->data_filled_color[$key]=""; 
		 }						
		}
$graph_builder_report->data_position_ordonnee=$builder_report_graph_data_position;
$graph_builder_report->data_legend=$builder_report_graph_legend;
         
for ($i=0;$i<$nombre_donnees_graphe;$i++) //$nombre_donnes_graphe est une variable passée dans l'URL qui détermine combien de données seront affichées dans le graphe
     if ($i!=$builder_report_abscisse) //il ne faut pas affichée dans les données du graphe les données qui ont été identifiées par l'utilisateur comme abscisse du graphe
         $graph_builder_report->Graph_Line_bar_creation($i,$i);

$graph_builder_report->Graph_Display();
?>
