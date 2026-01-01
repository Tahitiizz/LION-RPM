/*
 *  18/08/2010 NSE DE Firefox bz 17384 : fenêtres mal positionnées (utilisation de getWindowWidth() et getWindowHeight())
 */

var x_gis = y_gis = 0;
var gis_width, gis_height;
var niveau0;

function showGIS(data){
	gis_window.showGIS(data);
}

function lockGIS(is_lock){

	if (window.frames["gis_window"].gisStatus() != "ok") return;

	gis_window.lockGIS(is_lock);
}

function setDivGisPosition(){

	// 05/12/2007 - Modif. benoit : prise en compte de la taille de l'onglet et du selecteur / bandeau alarme pour définir la taille de la vue du GIS

	var onglet_filter_width = 0;
	if($('onglet_filter') != null) onglet_filter_width = $('onglet_filter').width;

	var div_selecteur_top = 0;
	if($('selecteur_container') != null) div_selecteur_top = $('selecteur_container').offsetTop;
	if($('div_alarm_info') != null) div_selecteur_top = $('div_alarm_info').offsetTop;
	var div_selecteur_height = 0;
	if($('selecteur_container') != null) div_selecteur_height = $('selecteur_container').offsetHeight;
	if($('div_alarm_info') != null) div_selecteur_height = $('div_alarm_info').offsetHeight;


	gis_width	= document.body.clientWidth-onglet_filter_width;
	gis_height	= document.body.clientHeight-(div_selecteur_top+div_selecteur_height);

	positionGIS();
}

function positionGIS(){

	// 05/12/2007 - Modif. benoit : prise en compte de la taille de l'onglet et du selecteur / bandeau alarme pour définir la taille de la vue du GIS

	var onglet_filter_width = 0;
	if($('onglet_filter') != null) onglet_filter_width = $('onglet_filter').width;

	var div_selecteur_top = 0;
	if($('selecteur_container') != null) div_selecteur_top = $('selecteur_container').offsetTop;
	if($('div_alarm_info') != null) div_selecteur_top = $('div_alarm_info').offsetTop;
	var div_selecteur_height = 0;
	if($('selecteur_container') != null) div_selecteur_height = $('selecteur_container').offsetHeight;
	if($('div_alarm_info') != null) div_selecteur_height = $('div_alarm_info').offsetHeight;

	var win_width	= document.body.clientWidth-2-onglet_filter_width;
	var win_height	= document.body.clientHeight-2-(div_selecteur_top+div_selecteur_height);

	document.getElementById('gis_window').width		= win_width;
	document.getElementById('gis_window').height	= win_height;

	document.getElementById('div_content').style.width	= win_width;
	document.getElementById('div_content').style.height	= win_height;
	document.getElementById('div_content').style.top	= div_selecteur_top+div_selecteur_height;
	document.getElementById('div_content').style.left	= onglet_filter_width;

	positionSatelliteWindows(document.getElementById('legend'));
	positionSatelliteWindows(document.getElementById('layers'));
	positionSatelliteWindows(document.getElementById('data_info'));
}

function showDataInformation(){

	if (window.frames["gis_window"].isLock()) return;

	var div_data_info = document.getElementById('data_info').style;
	var data_info_title = document.getElementById('data_info_title').style;

	if(div_data_info.visibility == "hidden"){

		positionSatelliteWindows(document.getElementById('data_info'));

		div_data_info.visibility = 'visible';

		data_info_title.display = "block";

		data_info_title.left = div_data_info.left;
		data_info_title.top = div_data_info.top;
		data_info_title.width = convertEltSizeToNumber(div_data_info.width)-30;
		data_info_title.height = "20";

		setZindex('data_info_title', 'data_info');
	}
	else
	{
		div_data_info.visibility = 'hidden';
		data_info_title.display = "none";
	}
}

function showLegend(){

	if (window.frames["gis_window"].isLock()) return;

	var div_legend = document.getElementById('legend').style;
	var legend_title = document.getElementById('legend_title').style;

	if(div_legend.visibility == "hidden"){

		positionSatelliteWindows(document.getElementById('legend'));

		div_legend.visibility = 'visible';

		legend_title.display = "block";

		legend_title.left = div_legend.left;
		legend_title.top = div_legend.top;
		legend_title.width = convertEltSizeToNumber(div_legend.width)-30;
		legend_title.height = "20";

		setZindex('legend_title', 'legend');

		window.frames["map_legend"].location.href += '?action=show';
	}
	else
	{
		div_legend.visibility = 'hidden';
		legend_title.display = "none";

		var legend_location = window.frames["map_legend"].location.href;
		legend_location = legend_location.substr(0, legend_location.indexOf('?action=show'));

		window.frames["map_legend"].location.href = legend_location;
	}
}

function showLayers(){

	if (window.frames["gis_window"].isLock()) return;

	var div_layers = document.getElementById('layers').style;
	var layers_title = document.getElementById('layers_title').style;

	if(div_layers.visibility == "hidden"){

		positionSatelliteWindows(document.getElementById('layers'));

		div_layers.visibility = 'visible';

		layers_title.display = "block";

		layers_title.left = div_layers.left;
		layers_title.top = div_layers.top;
		layers_title.width = convertEltSizeToNumber(div_layers.width)-30;
		layers_title.height = "20";

		setZindex('layers_title', 'layers');

		window.frames["map_layers"].location.href += '?action=show';
	}
	else
	{
		div_layers.visibility = 'hidden';
		layers_title.display = "none";

		var layers_location = window.frames["map_layers"].location.href;
		layers_location = layers_location.substr(0, layers_location.indexOf('?action=show'));

		window.frames["map_layers"].location.href = layers_location;
	}
}

function positionSatelliteWindows(window_object){

	var window_object_width		= convertEltSizeToNumber(window_object.style.width);
	var window_object_height	= convertEltSizeToNumber(window_object.style.height);

	var div_content = document.getElementById('div_content').style;

	var div_content_top		= convertEltSizeToNumber(div_content.top);
	var div_content_left	= convertEltSizeToNumber(div_content.left);

	window_object.style.left = div_content_left-window_object_width;
        // on vérifie que l'objet ne dépasse pas de la zone à gauche
	if ((div_content_left-window_object_width) < 0){
		window_object.style.left = div_content_left+gis_width;
	}
        // on vérifie que l'objet ne dépasse pas de la zone à droite
	if ((convertEltSizeToNumber(window_object.style.left) + convertEltSizeToNumber(div_content.width)) >= getWindowWidth())
	{
		window_object.style.left = getWindowWidth() - convertEltSizeToNumber(div_content.width);
	}

	window_object.style.top = div_content_top + ((gis_height-window_object_height)/2);
}

function setZindex(next_window_title, next_window){

	var tab_windows = Array(Array('legend_title', 'legend'), Array('layers_title', 'layers'), Array('data_info_title', 'data_info'));

	var zindex = old_zindex = document.getElementById(next_window_title).style.zIndex;
	var highest_title = null;

	for (var i=0; i<tab_windows.length;i++)
	{
		if (document.getElementById(tab_windows[i][0]).style.zIndex > zindex)
		{
			zindex			= document.getElementById(tab_windows[i][0]).style.zIndex;
			highest_title	= i;
		}
	}

	if (highest_title != null)
	{
		document.getElementById(tab_windows[highest_title][0]).style.zIndex = old_zindex;
		document.getElementById(tab_windows[highest_title][1]).style.zIndex = old_zindex-1;

		document.getElementById(next_window_title).style.zIndex = zindex;
		document.getElementById(next_window).style.zIndex = zindex-1;
	}
}

// Fonction permettant de convertir une valeur en pixels (ex : "10px") en nombre

function convertEltSizeToNumber(elt_size){
	if (elt_size.indexOf('px')) elt_size = Number(elt_size.substr(0, elt_size.length-2));
	return elt_size;
}

// Fonction permettant de replacer les titres des fenetres du GIS affichées

function setWindowsTitle(){

	var tab_windows = Array('legend', 'layers', 'data_info');

	for (var i=0;i<tab_windows.length;i++)
	{
		var div_layers = $(tab_windows[i]).style;

		if (div_layers.visibility == "visible")
		{
			var layers_title = $(tab_windows[i]+"_title").style;

			layers_title.left = div_layers.left;
			layers_title.top = div_layers.top;
			layers_title.width = convertEltSizeToNumber(div_layers.width)-30;
			layers_title.height = "20";
		}
	}
}

// 13/11/2007 - Modif. benoit : ajout des observeurs et des actions relatifs au redimensionnement de la fenêtre du GIS

var resizeTimer = null;

Event.observe( window, 'resize', doOnResize );

function doOnResize(){

	if(resizeTimer != null) resizeTimer.stop();

	resizeTimer = new PeriodicalExecuter(launchResizeAction, 0.5);
}

function launchResizeAction(){

	if(resizeTimer != null) resizeTimer.stop();

	if((gis_width != getWindowWidth()) || (gis_height != getWindowHeight())){

		if((window.frames["gis_window"].isLock() == false) || (window.frames["gis_window"].gisStatus() == "no_results")){

			gis_width	= getWindowWidth();
			gis_height	= getWindowHeight();

			setDivGisPosition();
			setWindowsTitle();

			window.frames["gis_window"].ResizeMap(gis_width, gis_height);
		}
		else
		{
			resizeTimer = new PeriodicalExecuter(launchResizeAction, 0.5);
		}
	}
}


// 16/11/2007 - Modif. benoit : ajout d'un observeur de fermeture de la fenêtre GIS et de ses actions associées

Event.observe( this, 'unload', onClose);

function onClose(){
	gis_window.destroyGIS();
}