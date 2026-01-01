<?
/*
*	@cb41000@
*
*	- maj 14/10/2008 SLC : allignement à gauche des labels suite à ajout du DOCTYPE
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
/*
	- maj 23 05 2006 christophe : Les menu dont le champ complement_lien=='non_visible' ne peuvent être coché ou décoché.

*/
	session_start();
	include_once($repertoire_physique_niveau0."php/environnement_liens.php");
	include_once($repertoire_physique_niveau0."php/database_connection.php");
	include_once($repertoire_physique_niveau0."php/environnement_donnees.php");
	include_once($repertoire_physique_niveau0."php/environnement_nom_tables.php");
	include_once($repertoire_physique_niveau0."intranet_top.php");
	include_once($repertoire_physique_niveau0."php/menu_contextuel.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");

	$libelle_favoris = "Profile Management";
	$lien_favoris = getenv("SCRIPT_NAME"); //récupère le nom de la page




function affichage_menu($id_menu_deroule, $niveau4_vers_images, $chaine_menu_coche, $droit, $id_profile, $profile_type)
{
    global $database_connection, $nom_table_menu_deroulant, $profile_to_menu, $path_skin, $niveau0;
	?>
	<table border="0" cellspacing="0" cellpadding="2" style="text-align:left;">
	<?
	// Modifier cette requête
    //$query = "SELECT id_menu, libelle_menu, id_menu_parent, deploiement, droit_affichage, position FROM $nom_table_menu_deroulant WHERE (id_menu_parent=$id_menu_deroule) ORDER BY position ASC";
    $query = " select a.id_menu as id_menu, libelle_menu, a.id_menu_parent as id_menu_parent, deploiement, droit_affichage, b.position as position, niveau, complement_lien from menu_deroulant_intranet a, profile_menu_position b ";
	$query .= " where a.id_menu_parent='$id_menu_deroule' ";
	$query .= " and a.id_menu = b.id_menu ";
	$query .= " and id_profile ='$id_profile' ";
	$query .= " ORDER BY position ASC ";
	$resultat = 		pg_query($database_connection, $query);
    $nombre_resultat = 	pg_num_rows($resultat);
    $compteur_menu = 0;
    for ($i = 0;$i < $nombre_resultat;$i++)
	{
        $row = pg_fetch_array($resultat, $i);

		$id_menu = 			$row["id_menu"];
		$complement_lien = 	$row["complement_lien"];
		$libelle_menu = 	$row["libelle_menu"];
		eval( "\$libelle_menu = \"$libelle_menu\";" );
		$deploiement = 		$row["deploiement"];
		$id_menu_parent = 	$row["id_menu_parent"];
		$position = 		$row["position"];
		$niveau = 			$row["niveau"];

		// gestion de l'affichage des flèches up / down : si le menu est en 1ère position, alors on n'affiche pas la flèche up
		// et si le menu est en dernière position, on n'affiche pas la flèche down.
		$affiche_up_arrow = true;
		$affiche_down_arrow = true;
		if ($id_menu_parent == 0 || $niveau == 2){
			$classTexte = "texteGrisBold";
		} else {
			$classTexte = "texteGris";
		}


		if($position==1){
			$affiche_up_arrow = false;
		} else {
			// On recherche la position minimum.
			$query_min = " select min(position) as min_position from profile_menu_position where id_menu_parent = '$id_menu_parent' and id_profile = $id_profile ";
			$result_min = pg_query($database_connection, $query_min);
			$ligne = pg_fetch_array($result_min, 0);
			if($ligne["min_position"] == $position) $affiche_up_arrow = false;
			// On recherche si le menu et à la position max.
			$query_max = " select max(position) as max_position from profile_menu_position where id_menu_parent = '$id_menu_parent' and id_profile = $id_profile ";
			$result_max = pg_query($database_connection, $query_max);
			$nb = pg_num_rows($result_max);
			if($nb > 0){
				$ligne = pg_fetch_array($result_max, 0);
				if($ligne["max_position"] == $position) $affiche_down_arrow = false;
				//echo $ligne["max_position"] . " / " . $position. " / " . $id_menu_parent;
			}
		}

		// On recherche le nombre de sous_menu, car si il y en a un seul, on n'affiche pas alors les flèches.
		$query = " select * from profile_menu_position where id_menu_parent = ". $id_menu_parent . " and id_profile = ". $id_profile;
		$result= pg_query($database_connection, $query);
		if(pg_num_rows($result)==1) $affiche_down_arrow = false;

		$query = "SELECT id_menu FROM $nom_table_menu_deroulant WHERE (id_menu_parent=$id_menu)";
		$sous_menu = pg_query($database_connection, $query);
		$nombre_sous_menu = pg_num_rows($sous_menu);
		// On n'affiche seulement le smenu que l'utilisateur a le droit de voir
		// dépend de la valeur de client_type dans la table sys_global_parameters
		?>
			<tr valign="top" >
				<td width="30">
				<?php
				// affichage des images pour concenter ou déployer
				if ($deploiement == 1) { // on affiche le "-" si le menu est déployé
					?><input type="image" onclick="selection_profile.id.value=<?=$id_menu?>; selection_profile.type_stockage.value='temp'" src="<?=$niveau0?>images/icones/moins_clair.gif" align="top" border="0"><?php
				} elseif ($nombre_sous_menu > 0) { // on affiche le plus pour permettre de déployer
					?><input type="image" onclick="selection_profile.id.value=<?=$id_menu?>; selection_profile.type_stockage.value='temp'; " src="<?=$niveau0?>images/icones/plus_clair.gif" align="top" border="0"><?php
				}
				if($nombre_sous_menu == 0){
					?><img src="<?=$niveau0?>images/icones/moins_clair_transparent.gif"/><?
				}
				?>
				</td>
				<td width="250" valign="top">
					<font class="<?=$classTexte?>"><? echo $libelle_menu; ?></font>
					<?php
						if ($deploiement == 1) { // on répète le teste sur la variable déploiement car on ne peu pas faire autrement l'odre d'affichage est important
							affichage_menu($id_menu, $niveau4_vers_images, $chaine_menu_coche, $droit, $id_profile, $profile_type);
						}
					?>
				</td>
				<td>
				<?php
					// transforme la chaine en tableau. Les valeurs de la chaine sont séparées par des '-'
					$liste_chaine = explode('-', $chaine_menu_coche);
					// print $chaine_menu_coche;
					// vérifie si l'id_menu appartient à la chaine des menu cochés
					if (in_array($id_menu, $liste_chaine) || in_array($id_menu, $profile_to_menu)) { // les 3 === sont importants
							$checked = "checked";
					} else {
						$checked = "";
					}
					if ($id_menu_parent == 0){
						$up = "small_up_arrow_2.gif";
						$down="small_down_arrow_2.gif";
						$fonction_js = ""; //" onClick='verif_check($id_menu, this)' ";
					} else {
						$up = "small_up_arrow.gif";
						$down="small_down_arrow.gif";
						$fonction_js = "";
					}
					?>
						<table cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td>
								<?
									$disabled = ($complement_lien == 'non_visible') ? " disabled " : "";
									if($complement_lien == 'non_visible'){
								?>
									<input type="hidden" name="menu_<?=$id_menu?>" value="1"/>
								<?
									}
								?>
									<input class="casecoche" type="checkbox" id="<?=$id_menu_parent?>" name="menu_<?=$id_menu?>" value="1"  <?=$fonction_js?> <?=$checked?>  <?=$disabled?> >
								</td>
								<?
								// On n'affiche les flèches up / down seulement si il s'agit du profil_type = user.
								if($profile_type=="user"){
									// Si c'est un menu parent on ne donne pas la possibilité de changer la position
									//if($id_menu_parent!=0){
										if($affiche_up_arrow){
									?>
									<td>
										<a href="intra_myadmin_update_position.php?profile_type=<?=$profile_type?>&sens=up&id_profile=<?=$id_profile?>&id_menu=<?=$id_menu?>&position=<?=$position?>&id_menu_parent=<?=$id_menu_parent?>">
											<img src="<?=$niveau0."images/icones/".$up?>" border="0" onMouseOver="popalt('Up');style.cursor='pointer';" onMouseOut='kill()'/>
										</a>
									</td>
									<?
										}
										if($affiche_down_arrow){
									?>
									<td>
										<a href="intra_myadmin_update_position.php?profile_type=<?=$profile_type?>&sens=down&id_profile=<?=$id_profile?>&id_menu=<?=$id_menu?>&position=<?=$position?>&id_menu_parent=<?=$id_menu_parent?>"">
											<img src="<?=$niveau0."images/icones/".$down?>" border="0" onMouseOver="popalt('Down');style.cursor='pointer';" onMouseOut='kill()'/>
										</a>
									</td>
									<?
										}
									//}
								}
								?>
							</tr>
						</table>
				</td>
			</tr>
				<?php

	}
				?>
        </tr>
    </table>
<?php
} // Fin de la fonction
// DEBUT DE PAGE
?>
<html>
<head>
<title>Profile Management</title>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" />
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/profile_management.js"></script>
<script>
/*
	function verif_check(id, obj){
		if(obj.checked){
			document.getElementById(id).checked = true;
		} else {
			document.getElementById(id).checked = false;
		}
	}
*/
</script>
</head>
<?
	$tab = edw_loadparams(); // chargement des données de la table sys_global_parameters
	$droit = $tab["client_type"];
	if(isset($_GET["profile_type"])){
		$profile_type = $_GET["profile_type"];
	}

	// Gestion de l'affichage des boutons et chmps textes.
	if(isset($_GET["new"])){
		$texte_bouton = "Save";
		$form_action = "intra_myadmin_update_profile.php?profile_type=".$profile_type;
		$input = "<input type=\"text\" name=\"name\" style=\"width:100px\" class=\"zoneTexte\"  maxlength=\"50\"/>";
		$delete = "";
	} else {
		$texte_bouton = "New";
		$form_action ="intra_myadmin_update_profile.php?profile_type=".$profile_type;	//intra_myadmin_profile_management.php?new=true";
		$input = "";
		$delete = "<input type=\"submit\" class=\"bouton\" value=\"delete\" name=\"Delete\" onMouseOver=\"style.cursor='hand';\" onClick=\"return confirm('Delete this profile ?')\"/>";
		// Si le profil est utilisé par un utilisateur, on empêche la suppression.
		if(isset($id_profile)){
			$verif_profile = " select * from users where user_profil = ". $id_profile;
			$result = pg_query($database_connection,$verif_profile);
			$nombre_resultat=pg_num_rows($result);
			if($nombre_resultat > 0){
				$delete = "<input type=\"button\" class=\"boutonRouge\" value=\"delete\" name=\"Delete\" onMouseOver=\"popalt('Delete aborted : This profile has been already assigned.');\" onMouseOut=\"kill()\" />";
			}
		}
	}

?>
<form name="selection_profile" method="post" action="<?=$form_action?>">
<table align="center" border="0" cellspacing="2" cellpadding="3" class="tabPrincipal">
	<tr>
	  <td align="center">
		<?
			if($profile_type=="admin"){
				$image_titre = "admin_profile_management_titre.gif";
			} else  {
				$image_titre = "user_profile_management_titre.gif";
			}
		?>
		<img align="center" src="<?=$niveau0."images/titres/".$image_titre?>" border="0">
	  </td>
	</tr>
	<? if(isset($_GET["msg_erreur"])){ ?>
	<tr>
		<td class="texteRouge" align="center"><?=$msg_erreur?></td>
	</tr>
	<? } ?>
	<tr height="50">
		<td align="center">

			<select name="id_profile" onChange="MM_jumpMenu('parent',this,0)" editable="editable" >
				<option value="#">*** Select a Profile ***</option>
		<?php
		// mets dans une liste déroulante tous les profils de profile_type spécifié.
		// /!\ On n'affiche pas les profiles dont le champ client_type='protected' :
		// on conserve au moins 1 profil que le custo et le client ne peuvent pas modifier afin d'éviter
		// de se retrouver sans profil.
		$query = "SELECT id_profile, profile_name FROM $nom_table_profile where profile_type='$profile_type' and client_type is null ORDER BY profile_name ASC";
		$resultat = pg_query($database_connection, $query);
		$nombre_resultat = pg_num_rows($resultat);
		for ($i = 0;$i < $nombre_resultat;$i++) {
			$row = pg_fetch_array($resultat, $i);
			// On n'affiche pas le profile administrator sauf si le champ client_type = acurio.
			$identifiant_profile = $row["id_profile"];
			$profile_name = $row["profile_name"];
			if ($identifiant_profile == $id_profile) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			?>
			<option value="intra_myadmin_profile_management.php?select=1&id_profile=<?=$identifiant_profile?>&profile_type=<?=$profile_type?>" <?=$selected?>><?=$profile_name?></option>
			<?php
		}
		?>
		</select>
	  </td>
	</tr>
	<? if($profile_type=="user"){ ?>
	<tr>
		<td>
			<table cellspacing="0" cellpadding="0" align="center"><tr><td width="350px">
			<fieldset>
				<legend><img src="<?=$niveau0?>images/icones/icone_astuce.gif"/>&nbsp;</legend>
				<font class="texteGris">
					Prior check / uncheck menu,sort menu.
				</font>
			</fieldset>
			</td></tr></table>
		</td>
	</tr>
	<? } ?>
	<tr>
		<td align="center">
			<? echo $input; ?>
			<input type="submit" class="bouton" value="<?=$texte_bouton?>" onMouseOver="style.cursor='hand';" name="<?=$texte_bouton?>"/>
			<? echo $delete; ?>
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
			<input type="hidden" name="id" value="">
			<input type="hidden" name="type_stockage" value="">
			<table width="100%" border="0" cellspacing="5">
			<tr>
				<td>
					<?php
					   if ($id_profile>0)
					   {

						$query = "SELECT profile_to_menu from $nom_table_profile WHERE (id_profile='$id_profile')";
						$resultat = pg_query($database_connection, $query);
						$row = pg_fetch_array($resultat, 0);

						if ($select == 1) { // cette valeur vaut 1 lorsqu'on charge la page en selectionnant un profile.
							// il faut donc aller chercher la chaine dans la base de données
							$chaine_menu_coche = $row["profile_to_menu"];
							$profile_to_menu = explode("-", $chaine_menu_coche); //sauvegarde le array contenant les identifiant des menus appartenant au profil.; Le array est stocké pour l'affichage

						} else {
							$profile_to_menu = explode("-", $row["profile_to_menu"]); //sauvegarde le array contenant les identifiant des menus appartenant au profil.; Le array est stocké pour l'affichage
						}
						$id_menu_deroule = 0;
						affichage_menu($id_menu_deroule, $niveau4_vers_images, $chaine_menu_coche, $droit, $id_profile, $profile_type); //$chaine_menu transmis dans l'url qui met à jour la base de donnée et qui recharge cette page.
						}
						else
						{
						 echo "<div align='center' class='texteGris'>No profile selected</center>";

						}

					?>
				</td>
			</tr>
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
	<tr height="40">
		<td align="center">
		<? if(!isset($_GET["new"])){ ?>
			<input type="submit" value="Submit" class="bouton" onMouseOver="style.cursor='hand';" name="modification">
		<? } ?>
		</td>
	</tr>
</table>
</form>
<a name="down"></a>
</body>
</html>
