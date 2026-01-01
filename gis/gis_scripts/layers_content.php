<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*       maj 08/09/2010 - MPR
*       DE Firefox : Fonction permettant la gestion multi-navigateur
*/
?>
<?php

	session_start();

	include '../gis_class/gisExec.php';

?>
<HTML>
<HEAD>
<LINK REL="stylesheet" HREF="../css/gis_styles.css" TYPE="text/css">
<SCRIPT LANGUAGE="JavaScript">
<!--

	var http_request;
	var radio_selected_index;

	function updateParent(child_id, parent_node){

		if(window.top.frames["gis_window"].isLock()){
			document.getElementById(child_id).checked = !(document.getElementById(child_id).checked);
			return;
		}

		var back_child		= document.getElementById(parent_node+'_background').checked;
		var border_child	= document.getElementById(parent_node+'_border').checked;

		if((back_child+" "+border_child).indexOf("true") == -1){
			document.getElementById(parent_node).checked = false;
		}
		else
		{
			document.getElementById(parent_node).checked = true;
		}

		var onmouseover_radio = document.form_layers.onmouseover_selected;
		var onmouseover_child = false;

		if (isNaN(onmouseover_radio.length))	// 1 seul element radio
		{
			onmouseover_child = true;
		}
		else
		{
			for (var i=0;i<onmouseover_radio.length;i++)
			{
				if (onmouseover_radio[i].value == parent_node) onmouseover_child = onmouseover_radio[i].checked;
			}
		}

		window.top.frames["gis_window"].MajLayersPptes(parent_node, back_child, border_child);
	}

	function updateChild(referential, parent_id){

		if(window.top.frames["gis_window"].isLock()){
			if (document.getElementById(referential).type == "radio")
			{
				document.form_layers.onmouseover_selected[radio_selected_index].checked = true;
			}
			else
			{
				document.getElementById(referential).checked = !(document.getElementById(referential).checked);
			}
			return;
		}

		var tab_child = Array(parent_id+'_background', parent_id+'_border');
		var parent_check = document.getElementById(parent_id).checked;

		for (var i=0;i<tab_child.length;i++)
		{
			document.getElementById(tab_child[i]).checked = parent_check;
		}

		var back_child		= document.getElementById(parent_id+'_background').checked;
		var border_child	= document.getElementById(parent_id+'_border').checked;

		var onmouseover_radio = document.form_layers.onmouseover_selected;
		var onmouseover_child = false;

		if (isNaN(onmouseover_radio.length))	// 1 seul element radio
		{
			onmouseover_child = true;
		}
		else
		{
			for (var i=0;i<onmouseover_radio.length;i++)
			{
				if (onmouseover_radio[i].value == parent_id){
					onmouseover_child = onmouseover_radio[i].checked;
					radio_selected_index = i;
				}
			}
		}

		window.top.frames["gis_window"].MajLayersPptes(parent_id, back_child, border_child);
	}

	function setMouseOverLayer(layer_id) {

		//if(window.top.frames["gis_window"].isLock()) return;

		var onmouseover_radio = document.form_layers.onmouseover_selected;
		var onmouseover_child = false;

		if (isNaN(onmouseover_radio.length) == false)
		{
			for (var i=0;i<onmouseover_radio.length;i++)
			{
				if (onmouseover_radio[i].value != layer_id) onmouseover_radio[i].checked = false;
			}
		}

		sendRequest("gis_manager.php?action=set_mouseover_layer&layer_mouseover="+layer_id, "");
	}

        // maj 08/09/2010 - MPR
        // DE Firefox : Fonction permettant la gestion multi-navigateur
        function httpRequest()  {

                var req;
                if (window.XMLHttpRequest) { // Mozilla
                        req = new XMLHttpRequest();
                        if (req.overrideMimeType) { // problème firefox
                                req.overrideMimeType('text/xml');
                        }
                }
                else {
                        if (window.ActiveXObject) { // C'est Internet explorer < IE7
                                try { req = new ActiveXObject("Msxml2.XMLHTTP");
                                }
                                catch(e) {
                                        try {
                                                req = new ActiveXObject("Microsoft.XMLHTTP");
                                        }
                                        catch(e) {
                                                req = null;
                                        }
                                }
                        }
                }
                return req;
        }

	function sendRequest(request_str, action){

                // maj 08/09/2010 - MPR
                // DE Firefox : Fonction permettant la gestion multi-navigateur
                var http_request = httpRequest();
		http_request.open('GET', request_str, true);
		if(action != "") http_request.onreadystatechange = eval(action);
		http_request.send(null);

	}

	function changeLayersOrder(layer_up, layer_down){

		if(window.top.frames["gis_window"].isLock()) return;

		window.top.frames["gis_window"].MajLayersOrder(layer_up, layer_down);
	}

//-->
</SCRIPT>
<SCRIPT TYPE="text/javascript" src="./fenetres_volantes.js"></SCRIPT>
</HEAD>

<BODY topmargin="5" leftmargin="1">

<FORM id="form_layers" name="form_layers" ACTION="">

<?php

	if (isset($_SESSION['gis_exec'])) {

		$gis_instance = unserialize($_SESSION['gis_exec']);

		$tab_layers = $gis_instance->tab_layers;

		$tab_temp = array();
        // 20/06/2011 NSE : merge Gis without polygons
		foreach ($tab_layers as $id_layer=>$layer_content)
        {
            // maj 17/06/2011 - On n'ajoute pas le layer NA min si les polygones sont désactivés
            if( $id_layer == $gis_instance->na_base && $gis_instance->displayMode == 0 )
            {
                continue;
            }

			//if (($layer_content['type'] != "geo")/* && (count($gis_instance->tab_polygones[$id_layer]) > 0)*/){
				$tab_temp[] = array($layer_content['order'], $id_layer, $layer_content);
			//}
		}
		rsort($tab_temp);

		// Construction du tableau de layers

		echo '<table width="100%" border="0" cellspacing="0">';

		// Entete

		echo '<tr><td width="40%"><p class="texte_commun" style="margin-left:10px">Layers</p></td>';
		echo '<td class="layer_column_style" align="center" width="10%"><img src="../gis_icons/mouseover.png" width="16" height="16" onMouseOver="popalt(\'On mouse over\');" onMouseOut="kill()"></td>';
		echo '<td class="layer_column_style" align="center" width="10%"><img src="../gis_icons/background.png" width="16" height="16" onMouseOver="popalt(\'Background\');" onMouseOut="kill()"></td>';
		echo '<td class="layer_column_style" align="center" width="10%"><img src="../gis_icons/border.png" width="16" height="16" onMouseOver="popalt(\'Border\');" onMouseOut="kill()"></td>';
		echo '<td class="layer_column_style" style="border-right-width:1" align="center" width="10%"><img src="../gis_icons/on_off.png" width="16" height="16" onMouseOver="popalt(\'On/Off\');" onMouseOut="kill()"></td>';
		echo '<td style="border-right-width:1" align="center" width="10%">&nbsp;</td>';
		echo '<td style="border-right-width:1" align="center">&nbsp;</td></tr>';

		// Corps

		$tab_layers = $tab_temp;

		foreach( $tab_layers as $i => $layer ) {

           //$layer = $tab_layers[$i];

			// Attribution du label du layer (pour les na_base et les cones : intitulés spécifiques)

			switch ($layer[1]) {
				case $gis_instance->na_base	: $element_name = strtoupper($layer[1])." (voronoi)";
				break;
				case "cone"					: $element_name = strtoupper($gis_instance->na_base)." (direction)";
				break;
				default						: $element_name = strtoupper($layer[1]);
			}

			if ($layer[2]['type'] == "geo") {
				$element_name = ucfirst(strtolower($element_name));
			}

			// On distingue le layer de la na active des autres layers en mettant son libellé en italique

			$layer_designation = $layer[1];

			if(($layer_designation == $gis_instance->na) || ($layer_designation == "cone" && $gis_instance->na == $gis_instance->na_base)){
				$style_na = ";font-style:italic";
			}
			else
			{
				$style_na = "";
			}

			echo '<tr><td><p class="texte_commun" style="margin-left:10px'.$style_na.'">'.$element_name.'</p></td>';

			$layer_elements = $layer[2];

			// Affichage du bouton radio permettant de choisir le layer qui possède les infos "onmouseover"

			if ($layer_designation == $gis_instance->layer_mouseover["id"]) {
				$checked_radio = "checked";
				//echo "<script>radio_selected_index = ".$radio_index."</script>";
			}
			else
			{
				$checked_radio = "";
			}

			echo '<td class="layer_column_style">';

			//if ($layer_designation != "cone") {
				echo '<input type="radio" id="onmouseover_selected" name="onmouseover_selected" value="'.$layer_designation.'" '.$checked_radio.' onclick="setMouseOverLayer(\''.$layer_designation.'\')">';
			/*}
			else
			{
				echo "&nbsp;";
			}*/

			echo '</td>';

			// Affichage des cases à cocher indiquant la visibilité des attributs du layer

			// Case à cocher "background"

			($layer_elements['background'] == true) ? $checked = "checked" : $checked = "";

			echo '<td class="layer_column_style"><input type="checkbox" id="'.$layer_designation.'_background" name="'.$layer_designation.'_background" '.$checked.' onclick="updateParent(this.id, \''.$layer_designation.'\')"></td>';

			// Case à cocher "border"

			($layer_elements['border'] == true) ? $checked = "checked" : $checked = "";

			echo '<td class="layer_column_style"><input type="checkbox" id="'.$layer_designation.'_border" name="'.$layer_designation.'_border" '.$checked.' onclick="updateParent(this.id, \''.$layer_designation.'\')"></td>';

			// Case à cocher "on/off" (afficher/masquer "background"+"border")

			($layer_elements['background'] == false && $layer_elements['border'] == false) ? $checked = "" : $checked = "checked";

			echo '<td class="layer_column_style" style="border-right-width:1"><input type="checkbox" id="'.$layer_designation.'" name="'.$layer_designation.'" '.$checked.' onclick="updateChild(this.id, this.id)"></td>';

			// Monter/Descendre les layers

			echo '<td class="texte_commun">';

			//$layer_pos = array_search($layer_designation, $tab_designation);

			if($i == (count($tab_layers)-1)){
				$img_down = "small_down_arrow_transp.gif";
				$on_click = "";
			}
			else
			{
				$img_down = "small_down_arrow.gif";
				$on_click = "changeLayersOrder('".$tab_layers[$i+1][1]."', '".$layer_designation."')";
			}

			echo '<img src="../gis_icons/'.$img_down.'" style="cursor:pointer" onClick="'.$on_click.'" onMouseOver="popalt(\'down\');" onMouseOut="kill()">';

			if($i == 0){
				$img_up = "small_up_arrow_transp.gif";
				$on_click = "";
			}
			else
			{
				$img_up = "small_up_arrow.gif";
				$on_click = "changeLayersOrder('".$layer_designation."', '".$tab_layers[$i-1][1]."')";
			}

			echo '<img src="../gis_icons/'.$img_up.'" onClick="'.$on_click.'" style="cursor:pointer" onMouseOver="popalt(\'up\');" onMouseOut="kill()">';
			echo '</td>';

			echo '<td>';

			if ($i == 0) {
				echo '<img src="../gis_icons/layer_top.png">';
			}
			else
			{
				echo '<img src="../gis_icons/layer_back.png">';
			}

			echo '</td></tr>';

		}
		echo '<table>';
	}

?>

</FORM>

</BODY>

</HTML>
