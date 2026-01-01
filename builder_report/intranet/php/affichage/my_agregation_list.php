<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
*	- 17/06/2009 BBX : 
		=> modification des requêtes avec id_user, ajout de quotes (champ text désormais)
		=> constantes CB 5.0
		=> Header CB 5.0
		=> Nouvelles fonctions CB 5.0
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
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/menu_contextuel.php");

$lien_css = $path_skin."easyopt.css";

// gestion multi-produit - 21/11/2008 - SLC
$family=$_GET['family'];
$product=$_GET['product'];
include_once('connect_to_product_database.php');
include_once("../traitement/intra_myadmin_graph_table_data_list_update.php");

// on teste en premier si la page a été appelé dans le but d'ajouter une requete à la liste des requetes des l'utilisateur
if ($id_agregation_to_drop)
	drop_agregation($id_agregation_to_drop);

if ($name_agregation_to_add)
	save_agregation($name_agregation_to_add,$cell_list);

if ($id_quey_to_modify)
	modify_agregation($id_quey_to_modify,$name,$cell_list);


// FONCTIONS D'AFFICHAGE DE L'ARBORESCENCE
// fonction qui affiche une ligne de niveau 0
function display_level_0($libelle, $numero_label, $image)
{
	global $family, $product;
	?>
	<tr><td><a href="my_agregation_list.php?family=<?=$family?>&product=<?=$product?>&numero_label=<?=$numero_label?>"><img hspace="4" vspace="3" align="absmiddle" src="<?=NIVEAU_0?>images/icones/<?=$image?>" border="0"><font class="font_11"><?=ucfirst($libelle)?></font></a></td></tr>
	<?
} 

// fonction qui affiche une ligne de niveau 1 = liste de tables
function display_level_1($parameters, $image1, $image2)
{
	global $image_blank, $data_type,$family,$product;

	$array_param=explode(':',$parameters);
	$commentaire=$array_param[1];
	$id_network_agregation=$array_param[2];

	?>
	<tr><td nowrap><img width=22 height=18 src="<?=NIVEAU_0?>images/icones/<?=$image_blank?>">
	<img src="<?=NIVEAU_0?>images/icones/<?=$image1?>">
	<img hspace="4" alt="<?=$array_param[1]?>"  src="<?=NIVEAU_0?>images/icones/<?=$image2?>">
	<a href="#" onClick="parent.frames['contenu_graph_table'].location='my_aggregation_cell_selection.php?family=<?=$family?>&product=<?=$product?>&id_network_agregation=<?echo $id_network_agregation;?>'; " ><font class='font_11'><?echo $commentaire;?></font></a>
	</td></tr>
<?php
} 
// DEBUT DE LA PAGE
$arborescence = 'Query Builder';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<div id="container" style="width:100%;text-align:center">
<table cellpadding="3" cellspacing="2" class="tabPrincipal">
<tr>
<td>
<fieldset>
<legend class="texteGrisBold">
	&nbsp;
		<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">
	&nbsp;
		Queries List
	&nbsp;
</legend>
<table width="100%"  cellspacing="0" cellpadding="1"  align="center" border="0">
  <tr>
    <td >
	   <table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr> 
            <td>
        <table cellpadding="0" cellspacing="0" border="0" width="100%" >
<?php 
$seperateur_parametre=":";
$liste_network_agregation=array();

// Recupère les requetes sauvegardées
$query = "select * from my_network_agregation where family='$family' and on_off=1 and id_user='".$id_user."' order by agregation_name" ;
$result = $db_prod->getall($query);
if ($result)
	foreach ($result as $row)
		$liste_network_agregation[] = implode($seperateur_parametre,array(7, ucfirst($row["agregation_name"]), $row["id_network_agregation"], '', ''));

// genere la matrice pour l'affichage des données à partir de toutes les données collectées
$affichage_liste_deroulante[0][0] = 'My Network Aggregation ';
$affichage_liste_deroulante[0][1] = $liste_network_agregation;
$image_dossier_open[0] = 'dossier_violet_open.gif';
$image_dossier[0] = 'dossier_violet.gif';
$image_cube[0] = 'pt_cube_violet.gif';
$image_blank = 'blank.gif';
$image_expand_open = 'bl.gif';
$image_expand_collapse = 'bm.gif';
$image_ligne_verticale = 'me.gif';
$image_ligne_verticale_fin = 'be.gif';
// parcoure les labels de niveau 0
foreach ($affichage_liste_deroulante as $key => $liste_niveau0)
	{
    // ****AFFICHAGE NIVEAU 0****
    // teste si le numéro label (lien surlequel on a cliqué) vaut la valeur du compteur
	if ($numero_label == $key and $numero_label != -1) 
		{
		// affiche un lien dont le numero de label vaut -1 puisque l'élément est déployé
		display_level_0($liste_niveau0[0], -1, $image_dossier_open[$key]); 
		// parcoure les éléments de niveau 1
		$compteur_affichage_niveau1 = 0;
		
		foreach ($liste_niveau0[1] as $liste_niveau1)
			{
            // ****AFFICHAGE NIVEAU 1****
            // teste si le numéro de la table en cours correspond à la table sur laquelle l'action de expand ou collapse a été réalisée
            // $deploiement_en_cours=1 correspond à un Expand alors que -1 correspond à 1 collapse
			if ($numero_deploiement == $compteur_affichage_niveau1 and $deploiement_en_cours == 1) 
				{
				$image_expand = $image_expand_open;
				$deploiement = -1;				
				}
			 else
			 	{
				$image_expand = $image_expand_collapse;
				} 
			if ($compteur_affichage_niveau1 < count($liste_niveau0[1])-1) 
				{
				$im1="m.gif";
				} 
			else
				{
				$im1=$image_blank;
				} 
			if ($compteur_affichage_niveau1 < count($liste_niveau0[1])-1) 
				{
				$image_niveau1_1 = $image_ligne_verticale;
				} 
			else
				{
				$image_niveau1_1 = $image_ligne_verticale_fin;
				} 
		display_level_1($liste_niveau1,$image_niveau1_1, $image_cube[$key]);
		$compteur_affichage_niveau1++;
		} 
	}
else
		{
		// affiche un lien dont le numero de label est nom vide puisque l'élément n'est pas déployé
		display_level_0($liste_niveau0[0], $key, $image_dossier[$key]);
		} 
	} 

?>
</table>
</td></tr>
</table>
</td></tr>
</table>
</fieldset>
</td>
</tr>
</table>
</div>
</body>
</html>
