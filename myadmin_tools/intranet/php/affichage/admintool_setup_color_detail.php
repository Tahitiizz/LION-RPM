<?
/*
*	Ce fichier permet à l'utilisateur de gérer les couleurs des éléments réseaux
*/
?>
<?
/*
*	@cb4100@
*
*	12/10/2009 - Copyright Acurio
*
*	- 18:53 19/10/2009 SCT : BZ 12145 => Moteur de recherche non performant
*	- 18:53 19/10/2009 SCT : BZ 12144 => Moteur de recherche sensible à la casse
*	- 10:25 26/10/2009 SCT : BZ 12143 => Ergonomie de l'IHM
*	- 10:59 26/10/2009 SCT : BZ 12151 => Labels à modifier
*	- 15:54 26/10/2009 SCT : BZ 12141 => Code HTML affiché sur l'IHM
*	- 16:14 26/10/2009 SCT : BZ 12142 => Problème d'affichage des labels de NE ayant des caractères spéciaux
*  	- 15:38 28/10/2009 MPR : BZ 12209 => Ajout d'un message lorsqu'on est en multi produit
*
*	01/12/2009 BBX : Affichage du produit uniquement en multiproduits. BZ 13058
*  17/09/2010 MMT: bz 17843 DE FireFox topology setup NE colors page navigation n'est pas centré
*  14/11/2011 ACS BZ 24548 Display ne research correctly in url
*
*/
?>
<script type="text/javascript">
function verif(val1, val2, val3, color)
{
	if(document.getElementById(val1).value != document.getElementById(val2).value)
	{
		document.getElementById(val3).style.background = color;
	}
}
</script>
<style>
.tabPrincipal {
	padding:10px 10px 0 10px;
	margin:0 auto;width:700px;
}
fieldset#changeProduct div {
	text-align:center;
	margin: 4px;
}
fieldset#changeProduct div img {
	margin-left:2px;
	margin-bottom:-4px;
}
.fileArchive {
	margin-top:5px;
	margin-bottom:5px;
}
</style>

<form name="setup_color_general" method="get" action="admintool_setup_color.php">
	<input type="hidden" name="id_menu_encours" value="<?=$_GET['id_menu_encours']?>">
	<input type="hidden" name="product" value="<?=$id_prod?>">
	<div  align="center" valign="middle" style="width:600px;align:center; padding-bottom: 10px;" class="tabPrincipal" >
<?php
// 01/12/2009 BBX
// Affichage du produit uniquement en multiproduits. BZ 13058
if(count(getProductInformations()) > 1)
{
?>
		<fieldset  id="changeProduct">
		<legend class="texteGrisBold">&nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Current product&nbsp;</legend>
			<table cellspacing="2" cellpadding="2" border="0">
				<tr align="center">
					<td align="center" class="texteGris">
					<?
						// 10:50 21/08/2009 GHX
						// Ajout de l'id du produit
						// 15:38 28/10/2009 MPR
						// Suppression de l'id du produit dans la fonction getProductInformations afin de récupérer tous les produits
						$product_information = getProductInformations();
						
						echo (ucfirst($product_information[$id_prod]['sdp_label']));
					?>
					</td>
					<td align="center" valign="top" class="texteGris">
					<?
					// nettoyage de la chaîne de retour
					$urlRetour = $_SERVER['PHP_SELF']."?id_menu_encours=".$_GET['id_menu_encours'];
					?>
							<a href="<?=$urlRetour?>" target="_top">
								<img src="<?=NIVEAU_0?>images/icones/change.gif" onMouseOver="popalt('Change product');style.cursor='help';" onMouseOut='kill()' border="0"/>
							</a>
					</td>
				</tr>
			</table>
		</fieldset>
		<br/>
<?php
}
// FIN BZ 13058
?>		
		<fieldset>
			<legend class="texteGrisBold">&nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Selection&nbsp;</legend>
			<table cellpadding="0" cellspacing="0" border="0" align="center">
				<tr>
					<td valign="middle">
			<?php
			$tableauNaAllowColor = getAllowColorNA($id_prod);
			// dans le cas où il existe plusieurs NA manageables
			// 10:25 26/10/2009 SCT : BZ 12143 => Ergonomie de l'IHM
			// 10:59 26/10/2009 SCT : BZ 12151 => Labels à modifier
			if(count($tableauNaAllowColor) > 0)
			{
			?>
                        <!-- 02/09/2010 OJT Correction bz16928 pour DE Firefox, suppression des &nbsp -->
						<span class="texteGris"><li>Network aggregation to manage</li></span>
					</td>
					<td align=left valign="bottom">
						<select id="type_na_to_manage" name="na_to_manage" onChange="submit();">
			<?php
				foreach($tableauNaAllowColor AS $labelNaToManage => $valeurNaToManage)
				{
					if(!isset($_GET['na_to_manage']))
						$_GET['na_to_manage'] = $valeurNaToManage;
			?>
							<option value="<?=$valeurNaToManage?>"<?=($_GET['na_to_manage'] == $valeurNaToManage) ? " selected" : ""; ?>><?=$labelNaToManage?></option>
			<?php
				}
			?>
						</select>
					</td>
				</tr>
				<tr>
					<td valign="middle">
                        <!-- 02/09/2010 OJT Correction bz16928 pour DE Firefox, suppression des &nbsp -->
						<span class="texteGris"><li><label for="label_search" style="padding:0;margin:0;">Network Elements search</label></li></span>
					</td>
					<td align=left valign="bottom">
						<input type="text" id="label_search" name="ne_search" value="<?=stripslashes(str_replace('"', '&quot;', $_GET['ne_search']));?>" />
					</td>
				</tr>
				<tr>
					<td valign="middle"></td>
					<td align=left valign="bottom">
						<input type="submit" class="bouton" id="send_search" value="&nbsp;Search&nbsp;"/>
			<?php
			}
			// dans le cas où aucun NA n'est manageable
			else
			{
				echo '<div class="errorMsg">No Network Aggregation to manage</div>';
			}
			?>
					</td>
				</tr>
			</table>
<?php
if(isset($_GET['na_to_manage']) || (isset($_GET['ne_search']) && $_GET['ne_search'] != ''))
{
	// 15:54 26/10/2009 SCT : BZ 12141 => Code HTML affiché sur l'IHM
	// remise en forme des caractères spéciaux => en encodant la double quote, cela ne pose pas de problème avec le caractère fermant de la balise form
	$texteRecherche = str_replace('"', '&quot;', $_GET['ne_search']);
?>
		</fieldset>
		<br/>
		<fieldset>
			<legend class="texteGrisBold">&nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Result&nbsp;</legend>
</form>
<? // 14/11/2011 ACS BZ 24548 Display ne research correctly in url  ?> 
<form name='setup_color' method='post' action="admintool_setup_color.php?id_menu_encours=<?=$_GET['id_menu_encours']?>&product=<?=$id_prod?>&ne_search=<?=urlencode($_GET['ne_search'])?>&na_to_manage=<?=$_GET['na_to_manage']?>&pageSearch=<?=$_GET['pageSearch']?>">
<?php
// construction du filtre de sélection des NA
$filtreSearch = array();
if(isset($_GET['na_to_manage']) && $_GET['na_to_manage'] != '')
	$filtreSearch[] = " eor_obj_type = '".$_GET['na_to_manage']."'";
// 18:52 19/10/2009 SCT : BZ 12145 => Moteur de recherche non performant
// 18:52 19/10/2009 SCT : BZ 12144 => Moteur de recherche sensible à la casse
if(isset($_GET['ne_search']) && $_GET['ne_search'] != '')
	$filtreSearch[] = " (eor_label ILIKE '".str_replace('*', '%', $_GET['ne_search'])."' OR eor_id ILIKE '".str_replace('*', '%', $_GET['ne_search'])."')";
// Nombre d'éléments par page
$nombreElementParPage = 100;
// initialisation de la page en cours
$pageEnCours = isset($_GET['pageSearch']) ? $_GET['pageSearch'] : 1;

// recherche de l'ensemble des éléments réseaux
$query_select_all = "
	SELECT DISTINCT 
		eor_label, 
		eor_id, 
		eor_obj_type, 
		eor_color
	FROM 
		edw_object_ref
	WHERE 
		".implode(' AND ', $filtreSearch);

$resultm = $database->execute($query_select_all);
$nbPageResult = $database->getNumRows();
$nbPage = ceil($nbPageResult / $nombreElementParPage);

// EN CAS D'ENREGISTREMENT
if(isset($_POST['save_button']))
{
	// initialisation d'un tableau pour mise à jour des couleurs sur le master topo
	$query_update_master = array();
	// on boucle sur les variables POST pour mettre les couleurs à jour
	foreach($_POST['color'] AS $index => $valeur)
	{
		// cas de l'enregistrement sur le produit en cours
		if($_POST['color'][$index] != $_POST['color_hidden'][$index])
		{
			$query_update = "
				UPDATE 
					edw_object_ref
				SET
					eor_color = '".$_POST['color'][$index]."'
				WHERE
					eor_id = '".$index."'
					AND eor_obj_type = '".$_GET['na_to_manage']."';";
			$database->execute($query_update);
			// dans le cas d'un élément mappé, on stocke une requête de mise à jour pour le master topo
			if(!empty($_POST['color_mapped'][$index]))
			{
				$query_update_master[] = "
					UPDATE 
						edw_object_ref
					SET
						eor_color = '".$_POST['color'][$index]."'
					WHERE
						eor_id = '".$_POST['color_mapped'][$index]."'
						AND eor_obj_type = '".$_GET['na_to_manage']."';";
			}
		}
	}
	// dans le cas où on doit mettre à jour les couleurs du master topo, on effectue le traitement ICI => une seule instance de connexion sur le master topo
	if(count($query_update_master) > 0)
	{
		// recherche du produit master topo
		$row_master_topo = getTopoMasterProduct();
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$db_master_topo = Database::getConnection($row_master_topo['sdp_id']);
		$db_master_topo->execute(implode('', $query_update_master));
	}
}

// La sélection des éléments réseaux
$listNeColor = array();
$query_select = "
	SELECT DISTINCT 
		eor_label, 
		eor_id, 
		eor_obj_type, 
		eor_color,
		eor_id_codeq
	FROM 
		edw_object_ref
	WHERE 
		".implode(' AND ', $filtreSearch)."
	ORDER BY 
		eor_label,
		eor_id
	LIMIT ".$nombreElementParPage."
	OFFSET ".($pageEnCours - 1) * $nombreElementParPage;
$req = $database->getall($query_select);
if($req)
{
	// initialisation du tableau des éléments mappés
	$tableau_element_mappes = array();
	
	foreach($req AS $row)
	{
		// 16:14 26/10/2009 SCT : BZ 12142 => Problème d'affichage des labels de NE ayant des caractères spéciaux
		// encodage du label du NE pour affichage des caractères spéciaux sur l'IHM
		$row['eor_label'] = utf8_encode($row['eor_label']);
		$listNeColor[] = $row;
		// dans le cas d'un élément mappé, on stocke l'élément afin de pouvoir aller le chercher dans une deuxième passe
		if(!empty($row['eor_id_codeq']))
			$tableau_element_mappes[] = $row['eor_id_codeq'];
	}
	// dans le cas où on trouve des éléments mappés, on effectue la recherche sur le master
	if(count($tableau_element_mappes) > 0)
	{
		// requête de recherche des éléments mappés
		$query_select_mappe = "
			SELECT 
				eor_id,
				eor_color
			FROM 
				edw_object_ref
			WHERE
				eor_id IN ('".implode("','", $tableau_element_mappes)."');";
		$row_master_topo = getTopoMasterProduct();
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$db_master_topo = Database::getConnection($row_master_topo['sdp_id']);
		$req_mappe = $db_master_topo->getall($query_select_mappe);
		if($req_mappe)
		{
			foreach($req_mappe AS $row)
			{
				// on stocke les couleurs des éléments mappés dans un tableau
				$listNeColorMappe[$row['eor_id']] = $row['eor_color'];
			}
		}
	}
}
	if(count($listNeColor) > 0)
	{
?>
			<table cellpadding="0" cellspacing="0" align="center">
				<tr>
					<td valign="middle">
<?php
					foreach($listNeColor AS $tableauInfoNe)
					{
					?>
						<span style="width:190px; float:left; margin-bottom: 1px;" class="_hn_">
							<input type="button" class="colorPickerBtn" name="color_btn[<?=$tableauInfoNe['eor_id'];?>]" id="color_btn_<?=$tableauInfoNe['eor_id'];?>" style="background-color:<?=(empty($tableauInfoNe['eor_id_codeq']) ? $tableauInfoNe['eor_color'] : ((isset($listNeColorMappe[$tableauInfoNe['eor_id_codeq']]) && !empty($listNeColorMappe[$tableauInfoNe['eor_id_codeq']])) ? $listNeColorMappe[$tableauInfoNe['eor_id_codeq']] : $tableauInfoNe['eor_color']))?>;cursor:pointer;" onFocus="new ColourPicker('color_<?=$tableauInfoNe['eor_id'];?>', 'color_btn_<?=$tableauInfoNe['eor_id'];?>');"/>
							<label name="<?=$tableauInfoNe['eor_id']?>" id="<?=$tableauInfoNe['eor_id']?>" class="zoneTexteStyleXPFondGris" style="border:0;"><?php echo ($tableauInfoNe['eor_label'] == '' ? '('.$tableauInfoNe['eor_id'].')': $tableauInfoNe['eor_label']); ?></label>
							<?php echo !empty($tableauInfoNe['eor_id_codeq']) ? '<label style="color: red;">*</label>' : ''; ?>
							<!-- 02/09/2010 OJT : Correction bz16928 pour DE Firefox, mise des hidden en dernier -->
                            <input type='hidden' name='color[<?=$tableauInfoNe['eor_id'];?>]' id='color_<?=$tableauInfoNe['eor_id'];?>' value='<?=(empty($tableauInfoNe['eor_id_codeq']) ? $tableauInfoNe['eor_color'] : ((isset($listNeColorMappe[$tableauInfoNe['eor_id_codeq']]) && !empty($listNeColorMappe[$tableauInfoNe['eor_id_codeq']])) ? $listNeColorMappe[$tableauInfoNe['eor_id_codeq']] : $tableauInfoNe['eor_color']))?>'/>
							<input type='hidden' name='color_hidden[<?=$tableauInfoNe['eor_id'];?>]' id='color_hidden_<?=$tableauInfoNe['eor_id'];?>' value='<?=$tableauInfoNe['eor_color']?>'/>
							<input type='hidden' name='color_mapped[<?=$tableauInfoNe['eor_id'];?>]' value='<?=$tableauInfoNe['eor_id_codeq']?>'/>
                        </span>
					<?php
					}
					?>
					</td>
				</tr>
				<tr>
               <!-- 17/09/2010 MMT bz 17843 DE FireFox topology setup NE colors page navigation n'est pas centré
                     utilisation de align="center" au lieu de align="middle"-->
					<td align="center">
						<?php
						// affichage des boutons de navigation par page
						if($nbPage > 1)
						{
							?>
						<span class="texteGris">&nbsp;<li>page navigation : </li></span>
							<?php
							for($i = 1; $i <= $nbPage; $i ++)
							{
								if($i != $pageEnCours)
								{
							?>
						&nbsp;<a href="admintool_setup_color.php?id_menu_encours=<?=$_GET['id_menu_encours']?>&product=<?=$id_prod?>&ne_search=<?=$texteRecherche?>&na_to_manage=<?=$_GET['na_to_manage']?>&pageSearch=<?=$i?>"><?=$i?></a>&nbsp;
							<?php
								}
								else
								{
							?>
						&nbsp;<span class="texteGris"><b><?=$i?></b></span>&nbsp;
							<?php
								}
							}
						}
						?>
					</td>
				</tr>
				<tr>
                    <!-- 02/09/2010 OJT : Correction bz16928 pour DE Firefox (middle to center) -->
					<td align="center">
						<input type="submit" class="bouton" name="save_button" id="save_button" value="&nbsp;Save&nbsp;"/>
					</td>
				</tr>
				<?
				// 15:38 28/10/2009 MPR
				// Correction du bug 12209 : Ajout d'un message lorsqu'on est en multi produit
				if( count($product_information) > 1 ){
				?>
				<tr>
					<td align="left" style="padding-top: 5px;"><label style="font-size: 9px; color: red;">* : mapped element, changes will be done on the master topology product</label></td>
				</tr>
				<?
				}
				?>
			</table>

		<?php
	}
	else
	{
		?>
			<table cellpadding="0" cellspacing="0" align="center">
				<tr>
					<td valign="middle">
						<div class="errorMsg">No Network Element matches to search criterias</div>
					</td>
				</tr>
			</table>
			<?php
	}
	?>
<?php
}
?>
		</fieldset>
	</div>
</form>


<script language="JavaScript">
</script>
