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
	include_once($repertoire_physique_niveau0."php/environnement_nom_tables.php");
	include_once($repertoire_physique_niveau0."php/menu_contextuel.php");
	include_once($repertoire_physique_niveau0."intranet_top.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
?>
<html>
<head>
	<title>Menu Management</title>
	<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" />
	<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
	<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
	<script>
		function Supp(nom, cible) {
			if (confirm(' *** Be carefull *** \n This menu will be deleted in ALL profiles.\n All sub-menus will be not accessible. \n Do you want to delete ' + nom + ' ?') ){
				window.location = cible;
			}
		}
	</script>
</head>
<?
	// NB
	// Seul le customisateur a le droit d'ajouter des menu.
	// Les menus ajouté appartiennent obligatoirement aux profils de type user avec un niveau 0.

	// Gestion de l'affichage des boutons et champs textes.
	if(isset($_GET["id_menu"])){
		$texte_bouton = "Save";
		$form_action = "intra_myadmin_update_menu.php?action=modification&id_menu=".$_GET["id_menu"];
		$input = "<input type=\"text\" name=\"name\" value=\"".$_GET["libelle_menu"]."\" style=\"width:100px\" class=\"zoneTexte\"  maxlength=\"50\"/>";
	} else {
		$texte_bouton = "Save New";
		$form_action = "intra_myadmin_update_menu.php?action=creation";
		$input = "<input type=\"text\" name=\"name\" style=\"width:100px\" class=\"zoneTexte\"  maxlength=\"50\"/>";
	}

	// Liste des menu édités par l'utilisateur.
	$query = " select * from menu_deroulant_intranet where droit_affichage='customisateur' and niveau=1 order by libelle_menu ";
	$resultat = pg_query($database_connection, $query);
	$nombre_resultat = pg_num_rows($resultat);
	$max = false;
	if($nombre_resultat >=  10) $max = true;

?>
<form name="selection_menu" method="post" action="<?=$form_action?>">
<table align="center" border="0" cellspacing="2" cellpadding="3" class="tabPrincipal">
	<tr>
	  <td align="center">
		<img align="center" src="<?=$niveau0?>images/titres/menu_management_titre.gif" border="0">
	  </td>
	</tr>
	<? if(isset($_GET["msg_erreur"])){ ?>
	<tr>
		<td class="texteRouge" align="center"><?=$msg_erreur?></td>
	</tr>
	<? } ?>
	<tr>
		<td align="center">
			<?
				if($max && !isset($_GET["id_menu"])){
					?><span class="texteRouge">Max number of menus is attaining</span><?
				} else {
					echo $input;
					?>
					<input type="submit" class="bouton" value="<?=$texte_bouton?>" onMouseOver="style.cursor='hand';" name="<?=$texte_bouton?>"/>
					<?
				}
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table cellspacing="0" cellpadding="0" align="center"><tr><td width="350px">
			<fieldset>
				<legend><img src="<?=$niveau0?>images/icones/icone_astuce.gif"/>&nbsp;</legend>
				<font class="texteGris">
					Click on a menu name to modify it.
				</font>
			</fieldset>
			</td></tr></table>
		</td>
	</tr>
	<tr>
		<td align="center">
			<a href="#down">
				<img src="<?=$niveau0?>images/icones/down.gif" align="top" border="0" onMouseOver="popalt('Bottom');style.cursor='pointer';" onMouseOut='kill()'>
			</a>
		</td>
	</tr>
	<tr>
		<td align="center">
			<table width="100%" border="0" cellspacing="0">
			<?
				// On affiche la liste des menus édités par le customisateur.
				for ($i = 0;$i < $nombre_resultat;$i++) {
					$row = pg_fetch_array($resultat, $i);
					$boolLigne = (bool) (($i % 2) == 0); // Changement de couleur de la ligne
					if ($boolLigne){
						$couleurTR= "#E4E3E3";
					} else {
						$couleurTR = "#F4F4F4";
					}
					echo "<tr bgcolor=\"".$couleurTR."\"><td class=\"texteGris\"> <a href=\"intra_myadmin_menu_management.php?id_menu=".$row["id_menu"]."&libelle_menu=".$row["libelle_menu"]."\">".$row["libelle_menu"]."</a></td>";
					echo "<td><img src=\"".$niveau0."images/icones/dustbin.gif\" border=\"0\" onMouseOver=\"popalt('Delete');style.cursor='pointer';\" onMouseOut=\"kill()\" onClick=\"Supp('".$row["libelle_menu"]."','intra_myadmin_update_menu.php?action=delete&id_menu=".$row["id_menu"]."')\"/></td></tr>";
				}
			?>
			</table>
		</td>
	</tr>
	<tr height="40">
		<td align="center">
			<a href="#top">
				<img src="<?=$niveau0?>images/icones/up.gif" align="top" border="0" onMouseOver="popalt('Top');style.cursor='pointer';" onMouseOut='kill()'>
			</a>
		</td>
	</tr>
</table>
</form>
<a name="down"></a>
</body>
</html>
