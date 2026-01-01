<?php
/*
 * 20/06/2011 NSE : merge Gis without polygons
 */
?><?php
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *
	- maj 04/01/2008, benoit : modification des fonctions deprecated en php5
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

	include_once("../../php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
	
	include '../gis_class/gisExec.php';

?>
<HTML>
<HEAD>
	<TITLE> Add Layer(s) </TITLE>
	<LINK REL="stylesheet" HREF="../css/gis_styles.css" TYPE="text/css">
	<SCRIPT LANGUAGE="JavaScript">
	<!--

		// Fonction d'ajout des layers sélectionnés (appelée par click sur le bouton "Add")

		function selectLayers(){

			// Définition de la liste des layers à ajouter

			var layers_list = document.getElementById('layers_list').options;

			var layers_added = Array();

			for (var i=0;i<layers_list.length;i++)
			{
				if (layers_list[i].selected) layers_added.push(layers_list[i].value);
			}

			// Si la liste est non nulle, on lance l'ajout

			if (layers_added.length > 0)
			{
				var nb_layer_reseau = Number(document.getElementById('nb_layer_reseau').value);

				if (nb_layer_reseau+layers_added.length > 3)	// Le nombre de layers max. est 3
				{
					alert('Too many layers selected (maximum 3 network layers allowed)');
				}
				else
				{
					window.close();
					window.opener.top.frames["gis_window"].AddRemoveLayers("add", layers_added.join(';'));
				}
			}
		}

	//-->
	</SCRIPT>
</HEAD>

<BODY topmargin="0" leftmargin="0">

<div id="loader_container" style="display:none;position:absolute;top:60px;left:80px;z-index:2">
        <div id="loader">
                <div id="texteLoader" style="padding:5px;">Adding layer(s)...</div>

                <div id="loader_bg"><div id="progress"> </div></div>
        </div>
</div>
<iframe
	id="div_mask"
	src=""
	scrolling="no"
	frameborder="0"
	style="position:absolute; top:0px; left:0px; display:none;">
</iframe>

<?
		$gis_instance = unserialize($_SESSION['gis_exec']);

		$na			= $gis_instance->na;
		$na_base	= $gis_instance->na_base;
		$database	= $gis_instance->database_connection;	
		
		$tab_layers	= $gis_instance->tab_layers;
		$na_display	= array();

		$nb_layer_reseau = 0;

		foreach ($tab_layers as $key=>$value) {
			$na_display[] = $key;
			if (($value['type'] == "reseau") && ($key != "cone")) $nb_layer_reseau += 1;
		}
// maj 14/06/2011 - MPR : Suppression des commentaires inutiles
// maj 14/06/2011 - MPR : Utilisation du model GisModel pour récupérer
//                        -> soit le NA min soit la liste des NA de la famille

$_na = GisModel::getNaWithGIS( $gis_instance->id_prod, $gis_instance->family );
if( $_na == array( $gis_instance->na_base) )
{
    echo '<div align="center"><br /><p class="texte_commun" style="font-weight:bold">No Additional Layer<p></div>';
}
else
{
?>
<table width="90%" align="center" border="0">
    <tr><td class="texte_commun" style="font-weight:bold">Select layers to add :</td></tr>
    <tr>
	<td align="center">
            <input type="hidden" id="nb_layer_reseau" name="nb_layer_reseau" value="'.$nb_layer_reseau.'" />
            <select id="layers_list" name="layers_list" class="layer_list_style" style="width:250px" size="10" multiple>
            <?php
            foreach ( $_na as $na => $na_label )
            {
                if( $na_label != "" )
                {
				$agregation_label = $na_label;
			}
			else
			{
				$agregation_label = $na;
			}

                if ( !(in_array($na, $na_display)) )
                {
                    if ($na == $na_base)
                    {
					echo '<option value="'.$na.'">'.$na_label.' (voronoi+direction)</option>';
				}
				else
				{
					echo '<option value="'.$na.'">'.$na_label.'</option>';
				}
			}
		}
            echo '</select>';
	?>
	</td>
</tr>
<tr>
	<td align="center">
		<div id="div_button">
			<input type="button" value="Add" class="bouton" onClick="selectLayers()">
		</div>
	</td>
</tr>
</table>
<?
}
?>


</BODY>
</HTML>
