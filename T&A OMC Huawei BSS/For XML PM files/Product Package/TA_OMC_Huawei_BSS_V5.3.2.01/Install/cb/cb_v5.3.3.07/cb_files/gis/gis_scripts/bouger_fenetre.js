var appui = false;
var offsetX, offsetY;

var title_style		= new Object();
var object_move		= null;
var object_title	= null;

/*
 * 18/08/2010 NSE DE Firefox bz 17384 : la fenêtre ne bouge pas
 * Internet Explorer exposes the visible content area dimensions using document.body.offsetHeight
 * and document.body.offsetWidth, whereas Mozilla Firefox uses window.innerWidth
 * and window.innerHeight
*/
function getWindowHeight(){
    if (document.all){
        return document.body.offsetHeight;
    }
    else{
        return window.innerHeight;
    }
}

function getWindowWidth(){
    if (document.all){
        return document.body.offsetWidth;
    }
    else{
        return window.innerWidth;
    }
}

// Fonction à associer à une action "onmousedown". Initialise les infos nécessaires au déplacement

function cliquer(objet_title_id, object_move_id, evt){

	object_title	= document.getElementById(objet_title_id);
	object_move		= document.getElementById(object_move_id);

	appui = true;

	title_style.width	= object_title.style.width;
	title_style.height	= object_title.style.height;
    // 18/08/2010 NSE DE Firefox bz 17384 : event n'est pas global pour Firefox, il faut le passer en paramètre
    var xm = evt.clientX;
	var ym = evt.clientY;

	offsetX = xm - convertEltSizeToNumber(object_title.style.left);
	offsetY = ym - convertEltSizeToNumber(object_title.style.top);

	object_title.style.left		= convertEltSizeToNumber(object_move.style.left)-50;
	object_title.style.top		= convertEltSizeToNumber(object_move.style.top)-50;
	object_title.style.width	= convertEltSizeToNumber(object_move.style.width)+100;
	object_title.style.height	= 20+convertEltSizeToNumber(object_move.style.height);

}

// Fonction à associer aux actions "onmousemove" et "onmouseover". Déplace la fenêtre ciblée par la fonction 'cliquer()' 

function deplacer(event){
    // 18/08/2010 NSE DE Firefox bz 17384
	// 02/09/2014 FGD - Bug 43445 - [REC][CB 5.3.3.01][TC#TA-56804][GUI][IE 10 compatibility] The legend window cannot moved when dragging over the map
    if (document.all && event.which==undefined){
	if (event.button != 1){
		lacher();
		//lockGIS(false);
	}
    }
    else{
        if (event.which != 1){
		lacher();
		//lockGIS(false);
	}
    }

	if (appui == true)
	{
		var xm = event.clientX;
		var ym = event.clientY;

		var titleWidth = convertEltSizeToNumber(object_title.style.width);
		var titleHeight = convertEltSizeToNumber(object_title.style.height);

		var newPosX = Math.min(Math.max(0, xm - offsetX), getWindowWidth()-titleWidth);
		var newPosY = Math.min(Math.max(0, ym - offsetY), getWindowHeight()-titleHeight);

		object_title.style.left	= newPosX-50;
		object_title.style.top	= newPosY-50;

		object_move.style.left	= newPosX;
		object_move.style.top	= newPosY;
	}
}

// Fonction à associer à l'action "onmouseup". Termine le déplacement en cours

function lacher(){

	appui = false;

	if (object_title != null && object_move != null)
	{
		object_title.style.left = object_move.style.left;
		object_title.style.top = object_move.style.top;			
		object_title.style.width = convertEltSizeToNumber(object_move.style.width)-40;
		object_title.style.height = "20px";			
	
		object_title = object_move = null;
	}
}

// Fonction permettant de convertir une valeur en pixels (ex : "10px") en nombre

function convertEltSizeToNumber(elt_size){
	if (elt_size.indexOf('px')) elt_size = Number(elt_size.substr(0, elt_size.length-2));
	return elt_size;	
}