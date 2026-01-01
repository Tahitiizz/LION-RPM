<?php
/*
 * @cb50400
 *
 *  18/08/2010 NSE DE Firefox bz 17408 : gestion de la transparence dans la fenêtre d'information
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
	- maj 08/11/2007, benoit : ajout des arguments 'gis_width' et 'gis_height' lors de l'appel du script 'gis_manager.php'
	- maj 20/02/08 - maxime : gestion de plusieurs elements reseau presents sur le meme polygône dans data informations

	- maj 17/04/2008, benoit : correction du bug 6443
	- maj 16:48 23/05/2008 : maxime - Uniformisation du lien vers Google Earth 
	- maj 26/05/2008 - maxime : On remplace le lien vers le fichier export_gearth.php par le lien vers export_file.php (fichier intermediaire permettant d'afficher un loading
	- maj 11/06/2008 maxime : correction du bug 6829 - On voit le separateur axe3 alors que c'est la na qui doit etre affichee
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
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
		- maj 01/03/2007, benoit : ajout de variables globales definissant les parametres du controleur SVG

		- maj 01/03/2007, benoit : ajout d'une fonction de reinitialisation des parametres du controleur SVG
*/
?>
<?php

	session_start();
	
	include_once("../php/environnement_liens.php");

?>
<html>
<head>
	<TITLE>GIS</TITLE>
	<LINK REL="stylesheet" HREF="<?=NIVEAU_0?>/gis/css/gis_styles.css" TYPE="text/css">
	
<?php
	// 30/07/2007 - Modif. benoit : ajout des librairies prototype et script.aculo.us
?>
	<script type="text/javascript" src="<?=NIVEAU_0?>/gis/gis_class_extern/prototype/prototype.js"> </script>
	<script type="text/javascript" src="<?=NIVEAU_0?>/gis/gis_class_extern/prototype/scriptaculous.js"> </script>
	
	<script type="text/javascript" src="<?=NIVEAU_0?>/gis/gis_scripts/control_class.js"> </script>
	<script type="text/javascript" src="<?=NIVEAU_0?>/gis/gis_scripts/miniature_class.js"> </script>
	<script type="text/javascript" src="<?=NIVEAU_0?>/gis/gis_scripts/scale_class.js"> </script>

	<script type="text/javascript" src="<?=NIVEAU_0?>/gis/gis_scripts/fenetres_volantes.js"></script>
	<script type="text/javascript" src="<?=NIVEAU_0?>/gis/gis_scripts/gestion_fenetre.js"></script>
	
	<script type="text/javascript">

		var tabActions			= Array('zoom_in', 'zoom_out', 'move', 'initial_view');
		var default_action		= 'zoom_in';
		var action_sel			= default_action;
		var actions_authorized	= true;
		var niveau0				= '<?=NIVEAU_0?>/';
		
		var gis_control = null;	// variable d'instance de la classe 'GisControl'

		var initial_width	= 0;
		var initial_height	= 0;

		var nb_pixels_substract = 25;

		function showGIS(external_data){

			initial_width	= document.body.clientWidth-nb_pixels_substract;
			initial_height	= document.body.clientHeight-nb_pixels_substract;

			// Initialisation de la taille du controller

			$('controller').style.width		= initial_width;
			$('controller').style.height	= initial_height;

			// 08/11/2007 - Modif. benoit : ajout des arguments 'gis_width' et 'gis_height' lors de l'appel du script 'gis_manager.php'

			new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
			{
				method: 'get',
				parameters: "action=first_load&external_data="+external_data+"&gis_width="+initial_width+"&gis_height="+initial_height,
				onComplete : this.initCtrlParameters.bindAsEventListener(this)
			});
		}

		// Fonction appelee par 'gis/index.php'

		function ResizeMap(width, height){
			if(gis_control != null) gis_control.resize(width-nb_pixels_substract, height-nb_pixels_substract);
		}

		//function initCtrlParameters(tab_paliers, current_zoom, viewbox, view_origine, raster, status)

		function initCtrlParameters(data)
		{
					
			var params = eval('(' + data.responseText + ')');
			// 11/10/2007 - Modif. benoit : creation d'une nouvelle instance de la classe de control du GIS
	
			gis_control = new GisControl(	'controller', 
											{
												ctrlArea		: 'controlArea',
												viewArea		: 'viewArea',
												loadArea		: 'loadingArea',
												miniArea		: 'miniatureArea',
												scaleArea		: 'scalelineArea',
												processArea		: 'processingArea',
												scalelineInfo	: ''
											},
											{
												tab_paliers		: params.tab_paliers,
												current_zoom	: params.current_zoom,
												viewbox			: params.viewbox,
												view_origine	: params.view_origine,
												current_action	: getCurrentAction(),
												raster_path		: params.raster,
												status			: params.status,
												niveau0			: '<?=NIVEAU_0?>/',
												opener			: this,
												controller_dims	: Array(initial_width, initial_height)
											}
										);
		}

		// Fonction qui renvoie l'action en cours

		function getCurrentAction(){return action_sel;}

		// Fonction appelee par les boutons du GIS qui permet de changer d'action

		function changeAction(new_action){

			if(gis_control == null) return;

			if ((actions_authorized == false) || (isLock())) return;

			old_action = action_sel;
			action_sel = new_action;

			// On change le statut de l'image de l'action selectionnee et de toutes les autres (qui sont logiquement deselectionnees)

			document.getElementById(new_action).className = "image_sel";

			for(var i=0;i<tabActions.length;i++){
				if(tabActions[i] != new_action){
					if(document.getElementById(tabActions[i]) != null){
						document.getElementById(tabActions[i]).className = "image_nosel";
					}
				}
			}

			// Si les actions sont autorisees (le GIS a des resultats) on effectue les traitements specifiques a l'action

			if ((actions_authorized == true) && (!isLock())){
				if(new_action == "initial_view")
				{
					gis_control.restoreInitalViewBox();
				}
				else
				{
					gis_control.setCurrentAction(new_action);
				}
			}
		}

		// Fonction permettant de locker/delocker le GIS

		function lockGIS(is_lock){
			if(gis_control != null) gis_control.setLockAction(is_lock);
		}

		// Fonction permettant de determiner l'etat de blocage/deblocage du GIS

		function isLock(){
			if(gis_control == null){
				return true;
			}
			else
			{
				return gis_control.getLockAction();
			}
		}

		// Fonction permettant de determiner le statut du GIS

		function gisStatus(){
			if(gis_control == null){
				return "not exist";
			}
			else
			{
				return gis_control.getStatus();
			}
		}

		// Fonction permettant de detruire l'ensemble du GIS

		function destroyGIS(){

			// On detruit les variables de session utilisees pour le GIS

			new Ajax.Request('<?=NIVEAU_0?>/gis/gis_scripts/gis_manager.php', 
			{
				method: 'get',
				parameters: "action=destroy_all"
			});

			// On restaure l'action par defaut (pour la prochaine ouverture)

			changeAction(default_action);

			// On detruit l'instance de controle du GIS

			if (gis_control != null)
			{
				gis_control.destroy();
				gis_control = null;
			}
		}

		// Fonction permettant de mettre a jour le contenu de l'iframe 'data_info' avec les infos en cours dans le GIS
		// maj 20/02/08 - maxime : On affiche tous les elements reseau presents sur le meme polygône

		function updateDataInformation(na_text){

			var lsRegExp = /\+/g;
			na_text = unescape(na_text);

			var na_text_tab = na_text.split('||');
			var data_title	= na_text_tab[0].split(";");
			
			var aff_info = "";
			var data_content = "";
			var content_html = "";
			
			data_content_tmp = na_text_tab[1].split(";");
			
			
			na = data_content_tmp[1];
			// maj 11/06/2008 maxime : correction du bug 6829 - On voit le separateur axe3 alors que c'est la na qui doit etre affichee
			if( na == '|s|' ){
				na = data_content_tmp[2];
			}
			
			if(na_text_tab.length > 1) data_content = data_content_tmp[0];
			
			// On affiche un message indiquant que plusieurs elements reseau sont sur le meme polygone
			
			nb_titles = data_title.uniq(); // On supprime les doublons (cas du 3eme axe)
			
			if(nb_titles.length>1){
					
				aff_info = '<table  align="center" height="100%"  width="250px" cellspacing="3" style="border: 1px solid #eeeeee; " >';
				aff_info+= '<tr align="center"><td><img src="gis_icons/information.png"/></td>';
				aff_info+= '<td class="texte_commun" >This area is covered by several '+na+'</td>';
				aff_info+= '</tr></table>';
			}			

			if (data_content == "")
			{
				content_html += '<p class="texte_commun">No information available</p>\n';
			}
			else
			{
				var data_content = data_content.split('|s|');

				content_html += '<table>';
		
				for (var i=0;i < data_content.length ;i++)
				{
					if (data_content[i] != "")
					{
					
						var color = data_content[i].indexOf("#");

						if( color >=0 ){
			
							var rect_color = data_content[i].split('-');
							var stroke = rect_color[0];
							var fill = rect_color[1];
							var opacity = rect_color[2];
							var icone_color;
							
							// maj 19/02/08 : maxime - Gestion des elements reseau superposes 
							
							// On inegre la couleur du data range avant d'afficher la valeur du compteur
                            // 18/08/2010 NSE DE Firefox bz 17408 : transparence
							content_html+="<tr height='20'><td>";
							content_html+="<table width='20' height='20' cellspacing='0' style='border: 1px solid "+stroke+";background-color:"+fill+";filter:alpha(opacity="+opacity+");opacity: "+(opacity/100)+"'>";
							content_html+="<tr width='100%'><td></td></tr>";
							content_html+="</table>";
							content_html+="</td>";
							
						} else {
		
							if( in_array(data_content[i], data_title) ){

								content_html+= "<tr><td class='texte_commun' style='font-weight:bold' width = '100%' colspan='2'> &nbsp;"+data_content[i]+"</td></tr>";
							} else {
								content_html+= "<td class='texte_commun'>&nbsp;"+data_content[i]+"</td></tr><tr></tr>";
							}
						}					
					}
				}
				
				content_html += '</table>';
			}

			try
			{
				top.window.frames["data_info"].document.body.innerHTML = content_html;
				// $('infoNaSuperposeArea').style.visible = aff_info;
				$('infoNaSuperposeArea').innerHTML = aff_info;
				
			}
			catch (e){}
		}
	
		// Fonction equivalente a la fonction in_array en php
		function in_array(needle, collection, strict) {
	    
			if (strict == null) {
				strict = false;
			}
			
			var i = collection.length-1;
			
			if (i >= 0) {
				
				do {
					if (collection[i] == needle) {
						
						if (strict && typeof(collection[i]) != typeof(needle)) {
							continue;
						}
						
						return true;
					}
				} while (i--);
			}
			
			return false;
		}
		
		// Fonction permettant de recharger la legende (si celle-ci est visible)

		function reloadLegend(){

			legend_location = top.window.frames["map_legend"].location.href;

			// Quand la legende est visible, son url contient le parametre action a la valeur show

			if(legend_location.indexOf('action=show') != -1){
				// Si la legende est visible, on la recharge en lui repassant la meme url
				top.window.frames["map_legend"].location.href = legend_location;
			}
		}

		// Fonction permettant de reloader la fenetre des layers (si celle-ci est visible)

		function reloadLayers(){

			layers_location = top.window.frames["map_layers"].location.href;

			// Quand la legende est visible, son url contient le parametre action a la valeur show

			if(layers_location.indexOf('action=show') != -1){
				// Si la legende est visible, on la recharge en lui repassant la meme url
				top.window.frames["map_layers"].location.href = layers_location;
			}
		}

		// Fonction permettant de mettre a jour les proprietes des layers

		function MajLayersPptes(id_layer, background, border){
			if (gis_control != null) gis_control.setLayersProperties(id_layer, background, border);
		}

		// Fonction permettant de mettre a jour l'ordre des layers

		function MajLayersOrder(layer_up, layer_down){
			if (gis_control != null) gis_control.setLayersOrder(layer_up, layer_down);
		}

		// Fonction permettant d'ajouter ou de supprimer des layers

		function AddRemoveLayers(action, layers_list){
			if (gis_control != null) gis_control.setLayers(action, layers_list);
		}

		// Fonction permettant d'envoyer la vue du GIS en cours dans le caddy (sous la forme d'un raster)

		function sendToCaddy(){
			if ((actions_authorized == false) && (!isLock())) return;

			if (gis_control != null) gis_control.sendMapToCaddy();
		}
		
		function sendToGearth(){
			if ((actions_authorized == false) && (!isLock())) return;

			if (gis_control != null){

				// 17/04/2008 - Modif. benoit : correction du bug 6443. On utilise le script JS 'ouvrir_fenetre()' pour la generation du KML

				//gis_control.sendMapToGearthFromGis();gis/gis_scripts/export_file.php?action=export_dash_alarm&
				ouvrir_fenetre('gis_scripts/export_file.php','DisplayGIS3D','true','true', 400, 30);
			}
		}

		// Fonction permettant d'afficher/masquer l'echelle

		function showScaleLine(){

			if (isLock()) return;

			var scalelineArea = document.getElementById('scalelineArea');

			if(scalelineArea.style.visibility == "hidden"){
				scalelineArea.style.visibility = 'visible';
			}
			else
			{
				scalelineArea.style.visibility = 'hidden';
			}
		}

		// Fonction permettant d'afficher / masquer la miniature

		function showMinMap(){	

			if (isLock()) return;

			var miniatureArea = document.getElementById('miniatureArea');

			if(miniatureArea.style.visibility == "hidden"){
				miniatureArea.style.visibility = 'visible';
			}
			else
			{
				miniatureArea.style.visibility = 'hidden';
			}
		}
		
		// Fonction permettant de mettre a jour la na et sa valeur dans le selecteur

		function changeNaSelecteur(new_na){

			if (top.document.getElementById('selecteur_na_level') != null)
			{
				var na_select		= top.document.getElementById('selecteur_na_level');
				var na_list_value	= top.document.getElementById('selecteur[gis_nel_selecteur]');
				
				old_na = na_select.value;
				
				for (var i=0;i<na_select.options.length;i++)
				{
					if ((na_select.options[i].value == new_na) && (na_select.options[i].selected == false))
					{
						na_select.options[i].selected = true;

						top.document.getElementById('gis_nel_img').className = 'bt_off';
		              
					}
					else{
						na_select.options[i].selected = false;
					}
				}
				
				top.document.getElementById('gis_nel_'+new_na+'_title').style.display = 'block';
				top.document.getElementById('gis_nel_'+old_na+'_title').style.display = 'none';
			}
		}

	</script>
</head>
<body topmargin="0" leftmargin="0" align="center" oncontextmenu="return false">
<table id="global_table" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
	<td colspan="2">&nbsp;</td>
<tr>
<tr>
<td valign="top">
	<table width="16" border="0" cellspacing="0" cellpadding="0">
	  <tr align="right" valign="bottom">
		<td><img src="gis_icons/coin_haut_gauche2.png" width="16" height="5"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="zoom_in" name="zoom_in" src="gis_icons/zoom_in.png" width="16" height="16" class="image_sel" onMouseOver="popalt('Zoom in');" onMouseOut="kill()" onClick="changeAction(this.id)"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="zoom_out" name="zoom_out" src="gis_icons/zoom_out.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Zoom out')" onMouseOut="kill()" onClick="changeAction(this.id)"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="move" name="move" src="gis_icons/shape_square_go.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Move')" onMouseOut="kill()" onClick="changeAction(this.id)"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="initial_view" name="initial_view" src="gis_icons/house.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Initial view')" onMouseOut="kill()" onClick="changeAction(this.id)"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="minMap" name="minMap" src="gis_icons/map.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Show/Hide miniature')" onMouseOut="kill()" onClick="showMinMap()"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="legendMap" name="legendMap" src="gis_icons/legend.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Show/Hide legend')" onMouseOut="kill()" onClick="top.showLegend()"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="settings" name="settings" src="gis_icons/wrench.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Show/Hide layers')" onMouseOut="kill()" onClick="top.showLayers()"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="scale" name="scale" src="gis_icons/scale_line.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Show/Hide scaleline')" onMouseOut="kill()" onClick="showScaleLine()"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="info" name="info" src="gis_icons/information.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Show/Hide data information')" onMouseOut="kill()" onClick="top.showDataInformation()"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img id="send_to_caddy" name="send_to_caddy" src="gis_icons/icone_caddy.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Add to the cart')" onMouseOut="kill()" onClick="sendToCaddy()"></td>
	  </tr>
	  <? // maj 17:49 25/01/2008 : maxime - Ajout d'un lien vers Google Earth 
		 // maj 16:48 23/05/2008 : maxime - Uniformisation du lien vers Google Earth 
		 // maj 26/05/2008 - maxime : On remplace le lien vers le fichier export_gearth.php par le lien vers export_file.php (fichier intermediaire permettant d'afficher un loading
	  ?>
	  <tr align="right" valign="top">
		<td><img id="send_to_caddy" name="send_to_caddy" src="gis_icons/link_to_gearth.png" width="16" height="16" class="image_nosel" onMouseOver="popalt('Display GIS 3D')" onMouseOut="kill()" onClick="sendToGearth()"></td>
	  </tr>
	  <tr align="right" valign="top">
		<td><img src="gis_icons/coin_bas_gauche2.png" width="16" height="5"></td>
	  </tr>
	</table>
</td>
<td id="td_control" class="map_style">

	<!-- div GIS (controleur + contenu) -->

	<div id="controller" class="div_controller" style="width:0px;height:0px">
		
		<!-- div de chargement -->

		<div id="loadingArea" style="position: absolute;width:100%;height:100%;visibility:visible">
			<table width="100%" height="100%">
				<tr>
					<td style="vertical-align:middle" align="center">
						<img src="gis_icons/loading_gis.png"/>
					</td>
				</tr>
			</table>
		</div>

		<!-- div de process (sert uniquement lors de la generation du raster) -->

		<div id="processingArea" style="position: absolute;width:100%;height:100%;visibility:hidden">
			<table width="100%" height="100%">
				<tr>
					<td style="vertical-align:middle" align="center">
						<img src="gis_icons/processing_gis.png"/>
					</td>
				</tr>
			</table>
		</div>

		<!-- div controleur -->

		<div id="controlArea" class="div_selection" style="top:0px;left:0px;width:0px;height:0px"><span/></div>
		<div id="viewArea" class="div_view" style="background: transparent url() no-repeat  0px 0px;"></div>

		<!-- div de la miniature -->

		<div id="miniatureArea" style="position:absolute;right:5px; bottom:5px;visibility:hidden;cursor: pointer" class="map_style"></div>

		<!-- div de l'echelle -->

		<div id="scalelineArea" style="position:absolute; float:left; bottom:5px; width:120px;visibility:visible"></div>
		<!-- div d'information  sur le onmouseover d'un polygone regroupant plusieurs elements reseau --> 
				 
		<div id="infoNaSuperposeArea" style=" position:absolute; margin-left:40%; bottom:5px; background-color:#ffffff;"></div>
		
	</div>

</td>
</tr>
<tr>
</tr>
</table>
</body>
</html>
