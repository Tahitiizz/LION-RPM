<?php
/**
 * @cb5100@
 * 05/07/2010 - Copyright Astellia
 *  - Ajout de environnement_liens pour l'accès aux classes de base (bz15541)
 *
 */
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

    // 05/07/2010 OJT : Corection bz15541
    include_once(dirname(__FILE__)."/../../php/environnement_liens.php");
    // 20/06/2011 NSE : merge Gis without polygons
    include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
    include REP_PHYSIQUE_NIVEAU_0."gis/gis_class/gisExec.php";

?>
<HTML>
<HEAD>
	<TITLE> Remove Layer(s) </TITLE>
	<LINK REL="stylesheet" HREF="../css/gis_styles.css" TYPE="text/css">
	<SCRIPT LANGUAGE="JavaScript">
	<!--

		// Fonction de suppression des layers sélectionnés (appelée par click sur le bouton "Remove")

		function selectLayers(){

			// Définition de la liste des layers à supprimer

			var layers_list = document.getElementById('layers_list').options;

			var layers_removed = Array();

			for (var i=0;i<layers_list.length;i++)
			{
				if (layers_list[i].selected) layers_removed.push(layers_list[i].value);
			}

			// Si la liste de layers est non nulle, on lance la suppression

			if (layers_removed.length > 0)
			{
				window.close();
				window.opener.top.frames["gis_window"].AddRemoveLayers("remove", layers_removed.join(';'));
			}
		}

	//-->
	</SCRIPT>
</HEAD>

<BODY topmargin="0" leftmargin="0">

<div id="loader_container" style="display:none;position:absolute;top:60px;left:80px;z-index:2">
        <div id="loader">
                <div id="texteLoader" style="padding:5px;">Removing layer(s)...</div>

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
// 20/06/2011 NSE : merge Gis without polygons
		$gis_instance = unserialize($_SESSION['gis_exec']);

		$na			= $gis_instance->na;
		$na_base	= $gis_instance->na_base;
		

		$tab_layers	= $gis_instance->tab_layers;

		$na_in_gis	= array();

// 20/06/2011 NSE : merge Gis without polygons
// maj 14/05/2009 MPR - Utilisation de la fonction getNaLabelList()
$_na = GisModel::getNaWithGIS( $gis_instance->id_prod, $gis_instance->family );
if( $_na == array( $gis_instance->na_base) )
{
    echo '<div align="center"><br /><p class="texte_commun" style="font-weight:bold">No Additional Layer<p></div>';
}
else
{
		foreach ($tab_layers as $key=>$value) {
			if (($key != $na) && ($value['type'] != "geo")) $na_in_gis[] = $key;
		}
?>
<table width="90%" align="center" border="0">
<tr><td class="texte_commun" style="font-weight:bold">Select layers to remove :</td></tr>
<tr>
    <td align="center">
        <select id="layers_list" name="layers_list" class="layer_list_style" style="width:250px" size="10" multiple>
    <?php
        __debug($_na,"NA in remove");
        foreach ( $_na as $na => $na_label ) {
            if ( in_array( $na, $na_in_gis ) )
            {
                if( $na_label != "" )
                {
					$agregation_label = $na_label;
				}
				else
				{
					$agregation_label = $na;
				}

				$agregation_value = $na;

                if ($na == $na_base)
                {
					$agregation_label .= " (voronoi+direction)";
					$agregation_value .= ";cone";
				}

				// Correction du bug 10856 : Lorsque l'on supprime le na_min, on supprime voronoi + direction
				echo '<option value="'.$agregation_value.'">'.$agregation_label.'</option>';
			}
		}
        echo '</select>';
	?>
	</td>
</tr>
<tr>
	<td align="center">
		<div id="div_button">
			<input type="button" value="Remove" class="bouton" onClick="selectLayers()">
		</div>
	</td>
</tr>
</table>
<?
}
?>
</BODY>
</HTML>
