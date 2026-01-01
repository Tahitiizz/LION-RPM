/*
	09/07/2009 GHX
		- Correction du BZ 10379 [REC][T&A CB 5.0][AFFICHAGE] : Problème d'affichage de la barre de menu avec IE 6
*/
/**
* Gestion de la barre de menu principale
*
* La barre de menu est une liste imbriquée dont le premier niveau de liste à id='menu' :
* <ol id='menu'>
*	<li>
*		<a>
*		<ol>		-- sous menu
*			<li>
*				<a>
*				<ol>	-- sous sous menu
*					<li>
*						<a>
*
* Remarque : ne pas mettre d'espace entre les tags, pour ne pas rajouter des nodes #text
*	
* 20/04/2009 - ajout SPS : pour ie6, ajout d'un return dans la fonction menuMouseWatch sinon le menu se ferme automatiquement 
*
* @author slc - skype:stef_ls
* @version CB4100
*/


/* on est obligés de surveiller la souris, parce que les onMouseOut sont trop compliqués à gérer.
* donc on efface le menu en fonction de la position de la souris car c'est plus fiable
* 
* 20/04/2009 - ajout SPS : pour ie6, ajout d'un return sinon le menu se ferme automatiquement 
*/
function menuMouseWatch(e) {
	var mouseX = Event.pointerX(e);
	var mouseY = Event.pointerY(e);
	
	// close the menu in case the mouse is away from it
	if (menuIsOpen) {
		// si la souris est au dessus du menu (pas en survol, mais au dessus verticalement) on ferme tout
		if (mouseY < Position.cumulativeOffset($('menu'))[1]) {
			menuClose();
			return;
		}
		// si la souris survole #menu
		if (mouseY < (Position.cumulativeOffset($('menu'))[1] + $('menu').getHeight() + 2)) {
			// +2px pour sécurité
			return;
		}
		// on cherche sur la souris survole un sous menu
		var enfants = $$('#menu ol');	// selection de tous les sous ou sous-sous-menus
		for (i=0 ; i<enfants.length; i++) {	// on boucle sur tous
			if (enfants[i].style.display != 'none')	{ // ceux qui sont caché ne nous interessent pas
				if ((Position.cumulativeOffset(enfants[i])[0] <= mouseX) && (mouseX <= (Position.cumulativeOffset(enfants[i])[0] + enfants[i].getWidth())) ) {	// est-ce que la souris est dans les X ?
					if ((Position.cumulativeOffset(enfants[i])[1] <= mouseY) && (mouseY <= (Position.cumulativeOffset(enfants[i])[1] + enfants[i].getHeight())) ) {	// est-ce que la souris est dans les Y ?
						// la souris est bien dans la boite du menu --> c'est bon
						return;
					}
					/* 20/04/2009 - ajout SPS : pour ie6, il faut rajouter le retour ici sinon le menu se ferme automatiquement */
					return;
				}
			}
		}
		// si on est arrivés là, c'est que la souris n'est dans aucune boite d'aucun menu --> on ferme tout
		menuClose();
	}
}
Event.observe(document, 'mousemove', menuMouseWatch);


// Ferme tous les sous et sous sous menus
function menuClose() {
	menuIsOpen = false;
	// il faut effacer tous les sous sous ... menus en partant du plus profond, pour remonter jusqu'à #menu ol
	// si on ne fait pas ça, sous IE, trop de sous menus réapparaissent quand on revient sur un menu précédemment déroulé
	
	var max_depth = 9;
	for (i=max_depth; i>0; i--) {
		css_path = '#menu';
		for (j=0; j<i; j++) {
			css_path += ' ol';
		}
		var enfants = $$(css_path);
		for (k=0 ; k< enfants.length; k++) {
			enfants[k].style.display = 'none';
		}
	}
	
	// 24/07/2009 BBX : 
	if($('iframe_hack_ie6')) {
		$('iframe_hack_ie6').remove();
	}
	if($('iframe_hack_ie6_sub')) {
		$('iframe_hack_ie6_sub').remove();
	}
}

// Gestion du survol sur les menus --> ouverture des menus
function menuOver() {
	// menu niveau 1 :  ol#menu li a
	if (this.parentNode.parentNode.id == 'menu') {
		
		// we get the position of menu item
		var x = Position.cumulativeOffset(this)[0] -1;
		var y = Position.cumulativeOffset(this)[1] + this.getHeight() +1;
		
		// we get the submenu to display
		var nextSib = this.nextSibling;
		if (nextSib) {	// on verifie qu'on a bien un sous-menu
			if (nextSib.nodeName == 'OL') {
				nextSib.style.display = 'block';
				nextSib.style.left = x+'px';
				nextSib.style.top = y+'px';
				//	alert(x+'/'+y+' '+nextSib.style.left);
				menuIsOpen = true;
			}
		
			// Corrige l'abscisse du menu si déborde à droite
			if ((Position.cumulativeOffset(nextSib)[0] + nextSib.getWidth()) > document.viewport.getWidth()) {
					x = Position.cumulativeOffset(this)[0] + this.getWidth() - nextSib.getWidth();
					nextSib.style.left = x+'px';
			}
			
		}
		
		// we hide all the others
		// on efface D'ABORD les sous sous menus (obligé de faire ça, sinon les sous sous menus ne s'effacent pas bien dans IE)
		var petitsEnfants = $$('#menu ol ol');
		for (i=0 ; i< petitsEnfants.length; i++)
				petitsEnfants[i].style.display = 'none';
	
		// on efface les sous menus
		var enfants = $$('#menu ol');
		for (i=0 ; i<enfants.length; i++)
			if (enfants[i] != nextSib)
				enfants[i].style.display = 'none';
				
				
		// 24/07/2009 BBX : IE 6 HACK
		if(navigator.appVersion.indexOf("MSIE 6.0") != -1)
		{
			// On efface le dernier frame
			if($('iframe_hack_ie6')) {
				$('iframe_hack_ie6').remove();
			}
			// On compte le nombre de sous-menus
			if(nextSib)
			{
				var frameHeight = nextSib.getHeight();
				$("menu_container").insert(new Element('<iframe src="about:blank" scrolling="no" frameborder="0" style="position:absolute;width:180px;height:'+frameHeight+'px;top:'+y+'px;left:'+x+'px;border:0;display:hidden;z-index:1"></iframe>', { id: "iframe_hack_ie6" }));
			}
		}			
				

	// sous-menu : ol#menu li ol li a
	// } else if (this.parentNode.parentNode.parentNode.parentNode.id == 'menu') {
	} else {
	
		// we get the position of the sub-menu item
		var x = this.offsetLeft + this.getWidth() - 15;
		var y = this.offsetTop + 5;

		// we get the sub sub menu to display
		if (this != this.parentNode.lastChild) {
			var nextSib = this.nextSibling;
			if (nextSib.nodeName == 'OL') {
				nextSib.style.display = 'block';
				nextSib.style.left = x+'px';
				nextSib.style.top = y+'px';
				menuIsOpen = true;
			}

			// Corrige l'abscisse du menu si déborde à droite
			if ((Position.cumulativeOffset(nextSib)[0] + nextSib.getWidth()) > document.viewport.getWidth()) {
				x = this.offsetLeft - nextSib.getWidth() +3;
				nextSib.style.left = x+'px';
			}

		}
		
		// we hide all other sub-sub-menus
		var sousMenu = this.parentNode.parentNode;
		soussousMenus = sousMenu.getElementsByTagName('OL');
		for (i=0 ; i<soussousMenus.length; i++)
			if (soussousMenus[i] != nextSib)
				soussousMenus[i].style.display = 'none';
				
		// 24/07/2009 BBX : IE 6 HACK
		if((navigator.appVersion.indexOf("MSIE 6.0") != -1) && (soussousMenus.length > 0))
		{
			// On efface le dernier frame
			if($('iframe_hack_ie6_sub')) {
				$('iframe_hack_ie6_sub').remove();
			}
			// On compte le nombre de sous-menus
			if(nextSib)
			{
				var frameHeight = nextSib.getHeight();
				$("menu_container").insert(new Element('<iframe src="about:blank" scrolling="no" frameborder="0" style="position:absolute;width:180px;height:'+frameHeight+'px;top:'+y+'px;left:'+x+'px;border:0;display:hidden;z-index:1"></iframe>', { id: "iframe_hack_ie6_sub" }));
				$('iframe_hack_ie6_sub').clonePosition(nextSib);
			}
		}	
	}
	return true;
}


/* Initialisation du menu */

// On définit la largeur du menu pour que les items de droite ne reviennent pas à la ligne
function setMenuWidth() {
	var myMenuItems = $$('ol#menu > li > a');
        // 23/03/2012 NSE bz 26490 : initialisation à 1 car il manque 1 px sous IE9 
        // (à cause d'une gestion différente du min-with avec les padding)
	menuWidth = 1;
	for (i=0; i<myMenuItems.length; i++) {
		// we get the content of the menu item
//		var label = myMenuItems[i].innerHTML;
//		var itemWidth = myMenuItems[i].getWidth();
//		var itemCSSWidth = myMenuItems[i].style.width;
//		alert(itemWidth+ ' / ' +myMenuItems[i].style.padding+ ' / ' + (label.length * 8));
//		itemWidth = min(itemWidth, (label.length * 8));
//		myMenuItems[i].style.width = itemWidth+'px';
		menuWidth += myMenuItems[i].getWidth();
	}
	$('menu').style.width = menuWidth + 'px';
}
setMenuWidth();


var menuIsOpen = false;
menuClose();	// IE a besoin de ce coup de manivelle sinon on a le bug suivant :
			// les premiers survols sur les items de la barre de menu n'effacent PAS les sous menus

// on ajoute la class 'hasSub' aux sous menus qui ont des sous-sous-menus
// pour pouvoir styler les items qui ont des sous menus
$$('#menu ol a').each(function(s) {
	if (s != s.parentNode.lastChild) {	// on verifie qu'on est pas sur le dernier enfant avant de lancer nextSibling
		nextSib = s.nextSibling;
		if (nextSib.nodeName == 'OL')
			s.addClassName('hasSub');
	}
});

// on attache la fonction menuOver au survol de tous les liens des menus / sous-menu / sous-sous-menu
$('menu').getElementsBySelector('a').each(function(s){
	s.onmouseover = menuOver;
});

// 08:49 09/07/2009 GHX
// Correction du BZ 10379 [REC][T&A CB 5.0][AFFICHAGE] : Problème d'affichage de la barre de menu avec IE 6
// On fait l'ajustement en JS car plus simple et plus rapide que de modifier le css
$$('#menu > li').each(function(s) {
	s.setStyle({float:'left'});
});