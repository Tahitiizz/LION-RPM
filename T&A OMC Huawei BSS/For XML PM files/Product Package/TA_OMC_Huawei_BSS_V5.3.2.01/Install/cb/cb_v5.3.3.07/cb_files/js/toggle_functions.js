/*
	@create 09 12 2005
	@update 29 12 2005 (ajout d'une fonction)
	@auteur christophe
	
	---------- Toggle functions ----------
	
	Permet de cacher / montrer un élément html de type container (balise DIV)
	- 17/04/2007 christophe : ajout d'un param
	- maj 13 03 2006 : ajout de la fonction toogle_clock pour gérer des contraintes spécifiques liées à l'horloge du sélecteur des graph / pie.
*/


/*
	Permet d'afficher ou non un élément HTML de type bloc (balise div)
	L'élément doit avoir par défaut le style display : block.
	obj : id de l'élément tml que l'on veut cacher / montrer
	iframe_sub : si définit c'es l'id du div contenant obj, cela permet de créer un iframe
	qui cache les balises select pour IE6
*/
var iframeSub;
function toggle(obj,iframe_sub) {
	var el = document.getElementById(obj);
	if ( el.style.display != 'none' && el.style.display != '') {
		if ( iframe_sub )
			document.getElementById(iframe_sub).removeChild(iframeSub);

		el.style.display = 'none';
	}
	else {
		el.style.display = 'block';
		if ( iframe_sub )
		{
			iframeSub = document.createElement('iframe'); 
		    iframeSub.setAttribute('frameborder', '0'); 
		    iframeSub.setAttribute('scrolling', 'no'); 
		    iframeSub.setAttribute('src', 'about:blank'); 
		    iframeSub.style.zIndex   = 0;
		    iframeSub.style.position = 'absolute';
		    iframeSub.style.filter   = 'alpha(opacity=0)'; 
			iframeSub.style.top = el.offsetTop+'px';
			iframeSub.style.left = el.offsetLeft+'px';
			widthT = el.clientWidth;
			heightT = el.clientHeight;
			iframeSub.style.width  = widthT + "px"; 
			iframeSub.style.height = heightT + "px";

			document.getElementById(iframe_sub).insertBefore(iframeSub, el);
		}
	}	
}

/*
	Permet d'afficher / cacher la zone avec l'horloge. 
	[SEULEMENT UTILE DANS LE SELECTEUR DU GRAPH/PIE]
*/

var tabCoords = new Array(); // Stock les coordonnées de la zone map de l'horloge

function toggle_clock(obj, caller) {
	var el = document.getElementById(obj);
	if ( el.style.display != 'none' && el.style.display != '') {
		el.style.display = 'none';
	}
	else {
		
		dateObject = 	new Date();
		day = 			(dateObject.getDate() < 10) ? "0"+dateObject.getDate() : dateObject.getDate() ;
		month = 		((dateObject.getMonth()+1) < 10) ? "0"+(dateObject.getMonth()+1) : dateObject.getMonth()+1 ;
		currentDate = 	day+"-"+month+"-"+dateObject.getFullYear();
		currentHour = 	dateObject.getHours();
		
		// Affichage de l'horloge am ou pm en fonction de l'heure du SELECTEUR.
		hourSelecteur = document.getElementById('hour_to_post').value;
		hourSelecteur = hourSelecteur.substr(0,2);

		if(hourSelecteur < 13){
			document.getElementById('am_clock').style.display = '';
			document.getElementById('pm_clock').style.display = 'none';
			document.getElementById('am').checked = true;
			document.getElementById('pm').checked = false;
		} else {
			document.getElementById('am_clock').style.display = 'none';
			document.getElementById('pm_clock').style.display = '';
			document.getElementById('am').checked = false;
			document.getElementById('pm').checked = true;
		}
		
		if(document.getElementById('date_selecteur').value == currentDate){
			for(i=1; i <= 24; i++){
				if(i >= currentHour){
					elem = "hour_"+i;
					tabCoords[elem] = document.getElementById(elem).coords;
					document.getElementById(elem).coords = '0,0,0';
				}
			}
		} else {
			// On remet les coordonnées initiales des area de la balise map.
			for(i=1; i < 24; i++){
				elem = "hour_"+i;
				if(document.getElementById(elem).coords == '0,0,0'){
					document.getElementById(elem).coords = tabCoords[elem];
				}
			}
		}

		// 10/08/2007 - Modif. benoit : redefinition des coordonnées x,y du div 
		// en fonction des coordonnées de l'element qui l'a appelé

		var ctl_coords = (Position.positionedOffset(caller));

		el.style.left	= Number(ctl_coords[0])-30;
		el.style.top	= Number(ctl_coords[1]);

		el.style.display = 'block';
	}	
}

/*
	Idem que la fonction précédente mais le bloc apparaît
	près de la souris.
*/
function toggle_near_mouse(obj) {
	var el = document.getElementById(obj);
	if ( el.style.display != 'none' && el.style.display != '') {
		el.style.display = 'none';
	}
	else {
		el.style.display = 'block';
	}	
	
	mouseX = window.event.clientX + document.body.scrollLeft;
	mouseY = window.event.clientY + document.body.scrollTop;
	 
	var nomlayer = el;
	
	var tpWd = nomlayer.clientWidth;
	var tpHt = nomlayer.clientHeight;
	
	var winWd = document.body.clientWidth+document.body.scrollLeft;
	var winHt = document.body.clientHeight+document.body.scrollTop;

	if ((mouseX+offX+tpWd)>winWd)
			nomlayer.style.left = mouseX-(tpWd+offX)+"px";
	else nomlayer.style.left = mouseX+offX+"px";
	if ((mouseY+offY+tpHt)>winHt)
			nomlayer.style.top = mouseY-(tpHt+offY)+"px";
	else nomlayer.style.top = mouseY+offY+"px";
}

