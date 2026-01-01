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
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
//include_once($repertoire_physique_niveau0 . "php/menu_contextuel.php");
include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
include_once($repertoire_physique_niveau0 . "php/my_reports_fonction.php");

// gestion multi-produit - 21/11/2008 - SLC
include_once('connect_to_product_database.php');

// on teste en premier si la page a été appelé dans le but d'ajouter une requete à la liste des requetes des l'utilisateur
if ($id_query_to_add) {
    changer_owner_query($id_query_to_add);
} 
// FONCTIONS D'AFFICHAGE DE L'ARBORESCENCE
// fonction qui affiche une ligne de niveau 0
function display_level_0($libelle, $numero_label, $image)
{
	global $niveau0,$product;
    ?>
	<tr><td><a href="my_reports_queries_list.php?numero_label=<?=$numero_label?>&product=<?=$product?>"><img hspace="4" vspace="3" align="absmiddle" src="<?=$niveau0?>images/icones/<?=$image?>" border="0"><font class="texteGrisPetit"><?=ucfirst($libelle)?></font></a></td></tr>
	<?php
} 
// fonction qui affiche une ligne de niveau 1 = liste de tables
function display_level_1($parameters, $image1, $image2)
{
    global $image_blank, $data_type, $niveau0, $product;

    $array_param = explode(':', $parameters);
    $commentaire = $array_param[1];
    $id_query = $array_param[2];
    $information_fenetre_volante = generer_fenetre_volante($id_query);

    ?>
<tr><td nowrap><img width=22 height=18 src="<?=$niveau0?>images/icones/<?=$image_blank?>">
<img src="<?=$niveau0?>images/icones/<?=$image1?>">
<img hspace="4" alt="<?=$array_param[1]?>"  src="<?=$niveau0?>images/icones/<?=$image2?>">
<a href="#" onClick="parent.frames['my_reports_onglet'].location='my_reports_onglet.php?show_onglet=1&onglet=1&product=<?=$product?>&id_query=<?php echo $id_query;
    ?>'; " ><font class='texteGrisPetit'><?php echo $commentaire;
    ?></font></a>
</td></tr>
<?php
} 

//
// DEBUT DE LA PAGE
//
?>
<html>
<head>
<script src="<?=$niveau0?>js/builder_report.js" ></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js" ></script> 
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
</head>
<body leftmargin="0" topmargin="0">
<div id="tbl-container">
<table width="90%" align="center" border="0" cellspacing="0" cellpadding="6" class="tabPrincipal">
<tr>
	<td>
	<fieldset>
	<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/puce_fieldset.gif"/>&nbsp;Queries List&nbsp;</legend>
	   <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr> 
            <td>
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<?php
			$separateur_parametre = ":";
			$liste_query_private = array();
			// Recupère les requetes sauvegardées
			$query = "select * from report_builder_save where on_off=1 and  id_user='" . $id_user . "' order by texte" ;
			$result_query = $db_prod->getall($query);
			if ($result_query)
				foreach ($result_query as $row)
					$liste_query_private[] = implode($separateur_parametre, array(5, ucfirst($row["texte"]), $row["id_query"], '',''));
			
			// genere la matrice pour l'affichage des données à partir de toutes les données collectées
			$affichage_liste_deroulante[0][0] = 'Saved Query';
			$affichage_liste_deroulante[0][1] = $liste_query_private;
			$image_dossier_open[0] = 'dossier_violet_open.gif';
			$image_dossier[0] = 'dossier_violet.gif';
			$image_cube[0] = 'pt_cube_violet.gif';
			$image_blank = 'blank.gif';
			$image_expand_open = 'bl.gif';
			$image_expand_collapse = 'bm.gif';
			$image_ligne_verticale = 'me.gif';
			$image_ligne_verticale_fin = 'be.gif';
			// parcoure les labels de niveau 0
			foreach ($affichage_liste_deroulante as $key => $liste_niveau0) {
				// ****AFFICHAGE NIVEAU 0****
				// teste si le numéro label (lien surlequel on a cliqué) vaut la valeur du compteur
				if ($numero_label == $key and $numero_label != -1) {
					// affiche un lien dont le numero de label vaut -1 puisque l'élément est déployé
					display_level_0($liste_niveau0[0], -1, $image_dossier_open[$key]); 
					// parcoure les éléments de niveau 1
					$compteur_affichage_niveau1 = 0;
			
					foreach ($liste_niveau0[1] as $liste_niveau1) {
						// ****AFFICHAGE NIVEAU 1****
						// teste si le numéro de la table en cours correspond à la table sur laquelle l'action de expand ou collapse a été réalisée
						// $deploiement_en_cours=1 correspond à un Expand alors que -1 correspond à 1 collapse
						if ($numero_deploiement == $compteur_affichage_niveau1 and $deploiement_en_cours == 1) {
							$image_expand = $image_expand_open;
							$deploiement = -1;
						} else {
							$image_expand = $image_expand_collapse;
						} 
						if ($compteur_affichage_niveau1 < count($liste_niveau0[1])-1) {
							$im1 = "m.gif";
						} else {
							$im1 = $image_blank;
						} 
						if ($compteur_affichage_niveau1 < count($liste_niveau0[1])-1) {
							$image_niveau1_1 = $image_ligne_verticale;
						} else {
							$image_niveau1_1 = $image_ligne_verticale_fin;
						} 
						display_level_1($liste_niveau1, $image_niveau1_1, $image_cube[$key]);
						$compteur_affichage_niveau1++;
					} 
				} else {
					// affiche un lien dont le numero de label est nom vide puisque l'élément n'est pas déployé
					display_level_0($liste_niveau0[0], $key, $image_dossier[$key]);
				} 
			} 
			?>
			</table>
			</td>
		</tr>
		</table>
	</fieldset>
</td></tr>
</table>
</div>
</body>
</html>
