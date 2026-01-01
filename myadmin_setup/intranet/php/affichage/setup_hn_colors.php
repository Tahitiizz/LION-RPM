<?php
/*
 * 	- 27/03/2008 GHX : Adaptation CB4.0.0.01
 *      - 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
 */
?>
<?php
/**
 * Script permettant de gérer la couleur pour le niveau d'aggrégation PLMN du troisième pour la famille Roaming (Parser Roaming GSM)
 * Il est possible de gérer la couleur uniquement si la table "edw_hn_color" existe.
 *
 * - modif 11/03/2007 GHX : Refonte total du script
 *
 * @author : GHX
 * @create : 11/03/2008
 */
session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/environnement_donnees.php");
include_once($repertoire_physique_niveau0."php/environnement_nom_tables.php");
include_once($repertoire_physique_niveau0."php/menu_contextuel.php");
include_once($repertoire_physique_niveau0."intranet_top.php");

/* *************************************************** */
/* Possibilité de choisir les couleurs avec le parser ROAMING */
/* *************************************************** */
$tableExist = false;
if ( get_sys_global_parameters('module') == 'roaming' )
	$tableExist = true;

/* ***************************** */
/* Sauvegarde du choix des couleurs */
/* ***************************** */

if ( isset($_POST['save']) ) {
	// on parcours tous les Home network.
	$query = "SELECT hn, color FROM edw_hn_color";
	$resultat = pg_query($database_connection, $query);
	while ( $row = pg_fetch_array($resultat) ) {
		if( $_POST["color_".$row["hn"]."_color_hidden"] != $row['color']) {
			$query_update = "UPDATE edw_hn_color SET color='".$_POST["color_".$row["hn"]."_color_hidden"]."' WHERE hn='".$row["hn"]."' ";
			pg_query($database_connection, $query_update);
		}
	}
}

/* *********************************** */
/* Récupère la liste des hn avec leur couleur */
/* *********************************** */
if ( $tableExist ) {
	$listHN = array();
	$optionsSelect = array();
	
	$query_select = 
			"
				SELECT DISTINCT t0.hn, t1.hn_label, t0.color, CASE WHEN t1.hn2 IS NULL THEN '(no group)' ELSE t1.hn2 END AS hn2
				FROM edw_hn_color t0, edw_object_1_ref t1
				WHERE t0.hn = t1.hn
				ORDER BY t1.hn_label, t0.hn
			";
	
	$result_select = pg_query($database_connection, $query_select);
		
	while ( $row = pg_fetch_array($result_select) ) {
		$listHN[] = $row;
		$optionsSelect[] = '<option value="'.$row['hn2'].'">'.$row['hn2'].'</option>';
	}
	
	$optionsSelect = array_unique($optionsSelect);
	sort($optionsSelect);
}
 
/* ********* */
/* Affichage */
/* ********* */
?>
<html>
<head>
	<title>Customize Home network colors</title>	
	<script>
	function verif(val1, val2, val3, color) {
		if(document.getElementById(val1).value != document.getElementById(val2).value){
			document.getElementById(val3).style.background = color;
		}
	}
	</script>
</head>
<body>

<?php if ( $tableExist === false ) { ?>

<center><h2 class="texteGrisBold"><?php echo __T('A_SETUP_HN_COLOR_NO_COLOR'); ?></h2></center>

<?php  } else { ?>


<center><img src="<?php echo $niveau0; ?>images/titres/hn_colors.gif"/></center>
<div class="tabPrincipal" style="margin: 10px; padding:10px">
<fieldset style="margin: 10px 0 10px 0">
	<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Filter&nbsp;</legend>
	<div class="texteGris" style="border:0; margin: 10px 0 0 20px"><?php echo __T('A_SETUP_HN_COLOR_FILTER'); ?> : <select name="selectColorGrp" class="zoneTexteStyleXP" onchange="filterHN(this.value);"><option value="">ALL</option><optgroup label="--------------------"></optgroup><?php echo implode('', $optionsSelect); ?></select></div>
<br />
</fieldset>
<fieldset style="margin: 10px 0 10px 0">
	<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;<?php echo __T('A_SETUP_HN_COLOR_FIELDSET_LABEL'); ?>&nbsp;</legend>

	<form name="form_hn_colors" method="POST" action="setup_hn_colors.php">
		<div class="texteGris">
			<div style="float:left; visibility:hidden" id="chooseColorGrp">
                            <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
				&nbsp;&nbsp;<?php echo __T('A_SETUP_HN_COLOR_CHOOSE_COLOR_GROUP'); ?> :
				<input type="button" name="<?="name_grp"?>" value="" size="16" style="background-color:;" class="zoneTexteStyleXP" onMouseOver="style.cursor='pointer';" onclick="javascript:ouvrir_fenetre('<?=$niveau0?>php/palette_couleurs_2.php?form_name=form_hn_colors&field_name=name_grp&hidden_field_name=color_color_hidden','Palette','no','no',304,100);" onFocus="applyOneColorForOneGroup()" />
				<input type="hidden" name="<?="color_color_hidden"?>" value="">
				<input type="hidden" name="<?="color_color_hidden_2"?>" value="">
			</div>
			<div style="float:right; margin-left:-50%"><center><input type="submit" class="bouton" name="save" value="Save"/></center></div>
		</div>
		<br />
		<br />
		<?php
		foreach ( $listHN as $hn ) {
                        // maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
			?>
			<span style="width:500px" class="_hn_" name="<?php echo $hn['hn2'].'|s|'.$hn['hn']; ?>">
				<input type="button" name="<?="name_".$hn["hn"]?>" value="" size="16" style="background-color:<?=$hn["color"]?>;" class="zoneTexteStyleXP" onMouseOver="style.cursor='pointer';" onclick="javascript:ouvrir_fenetre('<?=$niveau0?>php/palette_couleurs_2.php?form_name=form_hn_colors&field_name=<?="name_".$hn["hn"]?>&hidden_field_name=<?="color_".$hn["hn"]."_color_hidden"?>','Palette','no','no',304,100);" onFocus="verif('<?="color_".$hn["hn"]."_color_hidden"?>','<?="color_".$hn["hn"]."_color_hidden_2"?>','<?=$hn["hn"]?>','#f7931e')" />
				<input type="hidden" name="<?="color_".$hn["hn"]."_color_hidden"?>" value="<?=$hn["color"]?>">
				<input type="hidden" name="<?="color_".$hn["hn"]."_color_hidden_2"?>" value="<?=$hn["color"]?>">
				<label name="<?=$hn["hn"]?>" id="<?=$hn["hn"]?>" class="zoneTexteStyleXPFondGris" style="border:0; margin-left:-10px"><?php echo ($hn["hn_label"] == '' ? '('.$hn["hn"].')': $hn["hn_label"]); ?></label>
			</span>
			<?php
		}
		?>
	</form>
</fieldset>
</div>

<script>
var els = document.getElementsByTagName('label');
var nb = els.length
var max = 0;
for ( var i = 0; i < nb; i++) {
	if ( max < $(els[i]).offsetWidth ) {
		max = $(els[i]).offsetWidth;
	}
}
max+=40;
var els = document.getElementsByTagName('span');
var nb = els.length
for ( var i = 0; i < nb; i++) {
	els[i].style.width = max+'px';
}


function filterHN (selectedHN) {

	if ( selectedHN == '' )
		$('chooseColorGrp').style.visibility = 'hidden';
	else
		$('chooseColorGrp').style.visibility = 'visible';
	
	$('color_color_hidden').value = '';
	
	var els = document.getElementsByTagName('span');
	var nb = els.length

	for ( var i = 0; i < nb; i++) {
		var n = els[i].name.split("|s|");
		if ( selectedHN == n[0] || selectedHN == '' )
			els[i].style.display = 'inline';
		else
			els[i].style.display = 'none';
	}
}

function applyOneColorForOneGroup () {
	var color = $('color_color_hidden').value;
	var color2 = $('color_color_hidden_2').value;
	
	if ( color == color2 )
		return;
		
	var selectedHN = $('selectColorGrp').value;
	
	var els = document.getElementsByTagName('span');
	var nb = els.length
	for ( var i = 0; i < nb; i++) {
		var n = els[i].name.split("|s|");
		if ( selectedHN == n[0] ) {
			els[i].style.display = 'inline';
			$("color_"+n[1]+"_color_hidden").value = color;
			$("name_"+n[1]).style.background = color;
		}
	}
}
</script>

<?php } // End if ($tableExist === false) ?>

</body>
</html>