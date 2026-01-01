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
*
* @author slc - skype:stef_ls
* @version CB4100
*/


// on est obligés de surveiller la souris, parce que les onMouseOut sont trop compliqués à gérer.
// donc on efface le menu en fonction de la position de la souris car c'est plus fiable
function menuMouseWatch(e) {
	mouseX = Event.pointerX(e);
	mouseY = Event.pointerY(e);
	
	// close the menu in case the mouse is away from it
	if (menuIsOpen) {
		// si la souris est au dessus du menu (pas en survol, mais au dessus verticalement) on ferme tout
		if (mouseY < Position.cumulativeOffset($('menu'))[1]) {
			menuClose();
			return;
		}
		// si la souris survole #menu
		if (mouseY < (Position.cumulativeOffset($('menu'))[1] + $('menu').getHeight() + 2)) // +2px pour sécurité
			return;
		// on cherche sur la souris survole un sous menu
		enfants = $$('#menu ol');	// selection de tous les sous ou sous-sous-menus
			for (i=0 ; i<enfants.length; i++)	// on boucle sur tous
				if (enfants[i].style.display != 'none')	// ceux qui sont caché ne nous interessent pas
					if ((Position.cumulativeOffset(enfants[i])[0] <= mouseX) && (mouseX <= (Position.cumulativeOffset(enfants[i])[0] + enfants[i].getWidth())) )	// est-ce que la souris est dans les X ?
						if ((Position.cumulativeOffset(enfants[i])[1] <= mouseY) && (mouseY <= (Position.cumulativeOffset(enfants[i])[1] + enfants[i].getHeight())) )	// est-ce que la souris est dans les Y ?
							// la souris est bien dans la boite du menu --> c'est bon
							return;
		// si on est arrivés là, c'est que la souris n'est dans aucune boite d'aucun menu --> on ferme tout
		menuClose();
	}
}
Event.observe(document, 'mousemove', menuMouseWatch);


// Ferme tous les sous et sous sous menus
function menuClose() {
	menuIsOpen = false;
	// on efface les sous sous menus (obligé de faire ça, sinon les sous sous menus ne s'effacent pas bien dans IE)
	petitsEnfants = $$('#menu ol ol');
	for (i=0 ; i< petitsEnfants.length; i++)
		petitsEnfants[i].style.display = 'none';
	// on efface les sous menus (et les sous sous menus à nouveau)
	enfants = $$('#menu ol');	// on pourrait selectionner que '#menu > li > ol' si on avait Prototype 1.5.1
	for (i=0 ; i< enfants.length; i++)
		enfants[i].style.display = 'none';
}

// Gestion du survol sur les menus --> ouverture des menus
function menuOver() {
	// menu niveau 1 :  ol#menu li a
	if (this.parentNode.parentNode.id == 'menu') {
		
		// we get the position of menu item
		x = Position.cumulativeOffset(this)[0] -1;
		y = Position.cumulativeOffset(this)[1] + this.getHeight() +1;
		
		// we get the submenu to display
		nextSib = this.nextSibling;
		if (nextSib.nodeName == 'OL') {
			nextSib.style.display = 'block';
			nextSib.style.left = x+'px';
			nextSib.style.top = y+'px';
//			alert(x+'/'+y+' '+nextSib.style.left);
			menuIsOpen = true;
		}
		
		// Corrige l'abscisse du menu si déborde à droite
		if ((Position.cumulativeOffset(nextSib)[0] + nextSib.getWidth()) > document.viewport.getWidth()) {
				x = Position.cumulativeOffset(this)[0] + this.getWidth() - nextSib.getWidth();
				nextSib.style.left = x+'px';
		}

		// we hide all the others
		// on efface D'ABORD les sous sous menus (obligé de faire ça, sinon les sous sous menus ne s'effacent pas bien dans IE)
		petitsEnfants = $$('#menu ol ol');
		for (i=0 ; i< petitsEnfants.length; i++)
				petitsEnfants[i].style.display = 'none';
	
		// on efface les sous menus
		enfants = $$('#menu ol');
		for (i=0 ; i<enfants.length; i++)
			if (enfants[i] != nextSib)
				enfants[i].style.display = 'none';

	// sous-menu : ol#menu li ol li a
	} else if (this.parentNode.parentNode.parentNode.parentNode.id == 'menu') {
	
		// we get the position of the sub-menu item
		x = this.offsetLeft + this.getWidth() - 15;
		y = this.offsetTop + 5;

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
		sousMenu = this.parentNode.parentNode;
		soussousMenus = sousMenu.getElementsByTagName('OL');
		for (i=0 ; i<soussousMenus.length; i++)
			if (soussousMenus[i] != nextSib)
				soussousMenus[i].style.display = 'none';
	}
	
	return true;
}


/* Initialisation du menu */

// On définit la largeur du menu pour que les items de droite ne reviennent pas à la ligne
function setMenuWidth() {
	var myMenuItems = $$('ol#menu > li > a');
	menuWidth = 0;
	for (i=0; i<myMenuItems.length; i++) {
		menuWidth += myMenuItems[i].getWidth();
	}
	$('menu').style.width = menuWidth + 'px';
}
setMenuWidth();


menuIsOpen = false;
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

