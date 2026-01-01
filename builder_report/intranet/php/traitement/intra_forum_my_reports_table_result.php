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
include_once($repertoire_physique_niveau0."php/environnement_donnees.php");
include_once($repertoire_physique_niveau0."php/environnement_graphe.php");
include_once($repertoire_physique_niveau0."php/table_generation.php");
include_once($repertoire_physique_niveau0."php/menu_contextuel.php");
$lien_css = $path_skin . "easyopt.css";

// gestion multi-produit - 21/11/2008 - SLC
include_once('../affichage/connect_to_product_database.php');

// initialisation des tableaux
$liste_champ = array();
$array_font_debut = array();
$array_font_fin = array();
$image = 'graphe.gif';
// AFFICHE LE TABLEAU
$liste_champ = split(",", $query_select_fields); //paramètre passé dans l'URL
$nombre_champ = count($liste_champ);
$tableau_resultat = new Tableau_HTML(0, 0);
$tableau_resultat->tableau_number = 0; //numero uniquement utilisé pour le builder report
//pour chaque champ, on ne conserve que le nom du champ et on enleve le nom de la table
foreach ($liste_champ as $key=>$valeur_champ)
		{
		 $array_valeur_champ=explode(".",$valeur_champ);
		 $entete_colonnes[$key]=$array_valeur_champ[1];		
		}

$tableau_resultat->tableau_entete_colonnes = $entete_colonnes;
$tableau_resultat->line_counter = "no";
$tableau_resultat->tableau_width = 100;
$tableau_resultat->line_height = 22;
$tableau_resultat->header_color = "FFFFFF";
$tableau_resultat->line1_color = $line1_color_default;
$tableau_resultat->line2_color = $line2_color_default;
$tableau_resultat->tableau_font_debut = array_pad($array_font_debut, $nombre_champ, $font_debut_default);
$tableau_resultat->tableau_font_fin = array_pad($array_font_fin, $nombre_champ, $font_fin_default);
$equation_query=stripslashes($equation_query);

$resultat_query = $db_prod->getall($equation_query) or die ($message_erreur_requete);
$tableau_resultat->nombre_lignes_tableau = count($resultat_query);

// transforme le résultat de la requete pour obtenir un format exploitable par la fonction table_generation
for ($i = 0;$i < $nombre_champ;$i++)
	for ($j = 0;$j < $tableau_resultat->nombre_lignes_tableau;$j++)
		$tableau_data[$i][$j] = $resultat_query[$j][$i];

$tableau_resultat->tableau_data = $tableau_data;
// sauveagarde des données pour l'export vers Excel avec un l'identifiant du graphe qui vaut 0
// on utilise egalement ces données pour générer le graph du builder Report
$tableau_legend_export_excel[$tableau_resultat->tableau_number] = $tableau_resultat->tableau_entete_colonnes;
session_register("tableau_legend_export_excel");
$tableau_data_export_excel[$tableau_resultat->tableau_number] = $tableau_resultat->tableau_data;
session_register("tableau_data_export_excel");
$tableau_abscisse_export_excel[$tableau_resultat->tableau_number] = $tableau_data_export_excel[$tableau_resultat->tableau_number][0];
session_register("tableau_abscisse_export_excel");
?>
<head>
<script src="<?=$niveau0?>js/sort_table.js"></script>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">
</head>
<html>
 <body bgcolor="<?=$couleur_fond_page?>">
  <table width="100%" border="0" cellspacing="1" cellpadding="2">
    <tr>
      <td>
          <?php 
			// Generation du tableau
			$tableau_resultat->Tableau_Generation(); 
		  ?>
     </td>
    </tr>
  </table>
 </body>
</html>
