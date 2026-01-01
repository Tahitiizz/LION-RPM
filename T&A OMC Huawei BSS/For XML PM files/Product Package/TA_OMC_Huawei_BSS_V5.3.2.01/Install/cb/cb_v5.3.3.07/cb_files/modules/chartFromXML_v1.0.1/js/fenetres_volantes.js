/*********************************************************************************************************************************************
Fonctions qui permettent de gérer l'affichage d'une fenêtre volante
**********************************************************************************************************************************************/

/**
*	- maj 28/08/2008 SLC : changement de class mouseover->tooltip, pour harmoniser l'apparence des tooltips
 *	- maj 13/03/2007 Gwénaël    Création du message sur la fenetre volante au moment de l'affichage du texte et suppression lorsque l'on masque le message
 *	- maj 11/05/2007 Gwénaël : modification du nom de la variable iframe en iframe_tooltip, conflit avec une autre variable qui à le même nom
 *	- maj 04/07/2007 Gwénaël : changement de nom pour le menu contextuel vers AA
 *	- maj 04/07/07 Gwénaël : optimisation mouseover sur les graphes
 *	- maj 20/07/07 Gwénaël : nouveau tooltip -> se kill tout seul comme un grand 
			-> prototype.js doit être charger avant + global_interface.css
			-> l'ancienne version est toujours opérationnelle
*	- maj 04/12/07 Maxime : On récupère les dimensions exactes du div contenant le tooltip pour ie6
*	- maj 18/03/2008 Maxime : On récupère le nom du lien vers AA dans les paramètres

			*/

// modif 11:54 25/07/2007 Gwénaël
	// pararmètre pour savoir si on affiche un tooltip d'une donnée
var __pop = false; 
//modif 26/06/2007 Gwénaël 
var linkAA = false;
var valueLinkAA = null;
var useOldTooltip = false;
//affiche un layer
window.document.write("<DIV id='topdeck' style='POSITION: absolute; VISIBILITY: visible; Z-INDEX: 100; '></DIV>");
var iframe_tooltip = null;
function pop(titre, msg, AA) {
	
	var tab;
		
	__pop = true;
	//modif 10:52 04/07/2007 Gwénaël
		// suppression des balises tables => div et utilisation du css (cf .global_interface.css)
	// ancien code avant modif du 28/08/2008 de SLC
	var content ="<div class=\"mouseover\"><div class=\"titre\">"+titre+"</div><div class=\"msg\">"+msg+"</div></div>";
	// modif du 28/08/2008 de SLC : changement de class mouseover->tooltip, pour harmoniser l'apparence des tooltips
	var content ="<div class='tooltip'><div class='content'><div class='title'>"+titre+"</div><div class='text'>"+msg+"</div></div></div>";
	
    var nomlayer = document.getElementById("topdeck");
    nomlayer.innerHTML = content;
    nomlayer.style.visibility = "visible";
    positionTip();
	
	// modif 28/06/2007 Gwénaël
	if ( AA != '' && AA != undefined ) {
		// maj 18/03/2008 Maxime : On récupère le nom du lien vers AA dans les paramètres
		tab = AA.split('|t|');
		
		var valueLinkAA = tab[0];
		var labelLinkAA = tab[1];
		
		// valueLinkAA = AA;
		linkAA = true;
		menuContextuel.add('separateurLinkAA', '', '');
		
		// modif 04/07/2007 Gwénaël
			// Changement de nom pour le item du menu " Go To Activity Report => Go To Activity Analysis "

			
		menuContextuel.add('linkAA', labelLinkAA, 'window.parent.ouvrir_fenetre(\'launchAA.php?value='+valueLinkAA+'\',\'nouvellepage\',\'yes\',\'yes\',600,350)','menu_contextuel/default.png');
	}
}

/**
 * modif 13/03/2007 Gwénaël
 *      Ajout d'une iframe afin que celle ce place sous la table pour que le message apparait au dessus des balises SELECT
 *     + modification de la balise STYLE pour la table : "z-index:2; position: absolute;"
 */
var myTooltip = null;
function popalt(msg) 
{	
	// 24/07/2009 BBX : correction du changement de comportement avec IE6
	useOldTooltip = false;
	// 08:49 14/08/2009 GHX
	// Correction du BZ 10289
	//  Ajout de la deuxieme partie de la condition
	
	/***
	* TEST NAVIGATEUR
	***/
	if( navigator.appVersion.indexOf("MSIE 6.0") != -1 || typeof(Tooltip) == 'undefined'){ 
		// permet d'utiliser l'ancien mode de fonctionnement avec l'iframe
		useOldTooltip = true;
	}
	
	/***
	* TOOL TIP PROTOYPE (IE 7 et sup)
	***/
	if ( useOldTooltip == false ) {
		try
		{
			myTooltip = new Tooltip(msg, arguments[1] || false);
			// myTooltip = new Tooltip(msg, 'titre');
			return;
		}
		catch (e){}
	}
	
	/***
	* TOOL TIP IFRAME (IE 6)
	***/
    var titre = '';
	if ( arguments[1] )
		titre = '<div class="title">'+arguments[1]+'</div>';
    var content ="<div class='tooltip'><div class='content'>"+titre+"<div class='text'>"+msg+"</div></div></div>";
    var nomlayer = document.getElementById("topdeck");

    //modif 13/03/2007 Gwénaël
        //Création de l'iframe
	iframe_tooltip = document.createElement('iframe'); //Création de l'iframe
    iframe_tooltip.setAttribute('frameborder', '1'); //Pas de bordure
    iframe_tooltip.setAttribute('scrolling', 'no'); //Pas de scrollbar
    iframe_tooltip.setAttribute('src', 'about:blank'); //Contenu vide
    iframe_tooltip.style.zIndex   = 11100;
    iframe_tooltip.style.position = 'absolute';
    iframe_tooltip.style.filter   = 'alpha(opacity=0)'; //iframe transparente    
    nomlayer.innerHTML = content;
    nomlayer.appendChild(iframe_tooltip); //Ajout de l'iframe au tooltip qu'on affiche
   
    nomlayer.style.visibility = "visible";
    positionTip();	
	
	// 23/09/2009 BBX : ajout d'une fonction autokill pour les tooltip IE6. BZ 11662
	Event.observe(document.body, 'mouseover', function(event) {
		var element = Event.element(event);
		element.observe('mouseout', function(e){
			kill();
			element.stopObserving('mouseout');
		});
		document.body.stopObserving('mouseover');
	});
}

// Modif christophe.
// Permet d'afficher un div contenant un gif "loading data".
function preload(){
    var content="<table><tr><td><img src='wait.gif'/></td></tr></table>";
    var nomlayer = document.getElementById("topdeck");
    nomlayer.innerHTML = content;
    nomlayer.style.visibility = "visible";
    positionTipScreen();
}

///////////////////////  CUSTOMIZE HERE   ////////////////////
// settings for nomlayer
// Do you want tip to move when mouse moves over link?
var offX  = 35;        // how far from mouse to show tip
var offY  = -15;

/////////////////////////////////////////////////////////////
//  donomlayer function
//                        Assembles content for nomlayer and writes it to tipDiv.
//                        Call positionTip function from here if tipFollowMouse
//                        is set to false.
//////////////////////////////////////////////////////////////
var mouseX, mouseY;
function trackMouse(evt) {
	mouseX = window.event.clientX + document.body.scrollLeft;
	mouseY = window.event.clientY + document.body.scrollTop;
	positionTip(evt);
}

/////////////////////////////////////////////////////////////
//  positionTip function
//                If tipFollowMouse set false, so trackMouse function
//                not being used, get position of mouseover event.
//                Calculations use mouseover event position,
//                offset amounts and nomlayer width to position
//                nomlayer within window space available.
/////////////////////////////////////////////////////////////
function positionTip(evt) {
    mouseX = window.event.clientX + document.body.scrollLeft;
    mouseY = window.event.clientY + document.body.scrollTop;
    
    var nomlayer = document.getElementById("topdeck");
    
    //  modif 13/03/2007 Gwénaël 
        // On récupère la taille de la table au lieu du layer topdeck car il prend en compte la taille de l'iframe qui est par défaut 300px sur 150px
    // nomlayertable width and height
	// try {
		// var nomlayertable = nomlayer.getElementsByTagName('div')[0]
		// var tpWd = nomlayertable.clientWidth;
	// }
	// catch ( e ) {
	var nomlayertable = nomlayer.getElementsByTagName('div')[0]
	var tpWd = nomlayertable.clientWidth;
	// }
    var tpHt = nomlayertable.clientHeight;
    // document area in view (subtract scrollbar width for ns)
    var winWd = document.body.clientWidth+document.body.scrollLeft;
    var winHt = document.body.clientHeight+document.body.scrollTop;
	
    // check mouse position, tip and window dimensions
    // and position the nomlayer
    if ((mouseX+offX+tpWd)>winWd)
        nomlayer.style.left = mouseX-(tpWd+offX)+"px";
		
    else 
		nomlayer.style.left =  mouseX+offX+"px";

    if ((mouseY+offY+tpHt)>winHt)
        nomlayer.style.top = mouseY-(tpHt+offY)+"px";
  
    else 
        nomlayer.style.top = mouseY+offY+"px";

    //modif 13/03/2007 Gwénaël
        //Spécifie la taille de l'iframe
	
    if(iframe_tooltip != null) {
		// modif 04/12/07 - maxime : On récupère les dimensions exactes du div contenant le tooltip
        iframe_tooltip.style.width = tpWd; //= Largeur du tooltip
        iframe_tooltip.style.height = tpHt;; //= Hauteur du tooltip 
	}
	
}

// Rajout christophe
// Permet d'afficher l'objet au mileiu de la page.
function positionTipScreen(evt) {
	var nomlayer = document.getElementById("topdeck");
	var tpWd = nomlayer.clientWidth / 2;
	var tpHt = nomlayer.clientHeight * 2;
	nomlayer.style.left = tpWd+"px";
	nomlayer.style.top = tpHt+"px";
}

///////////// end nomlayer code ///////////////
function kill() {
	// modif 11:54 25/07/2007 Gwénaël
		// ajout d'un condition dans le cas où on doit killer le tooltip d'une donnée
	if ( myTooltip && __pop == false)
		return;
	
	__pop = false;
	
    var nomlayer = document.getElementById("topdeck");
    nomlayer.style.visibility = "hidden";

	//modif 26/06/2007 Gwénaël
		// suppression du lien vers AA si celui-ci lors que la souris quitte une donnée d'un graphe
	if ( linkAA == true ) {
		linkAA = false;
		valueLinkAA = null;
		menuContextuel.remove('separateurLinkAA');
		menuContextuel.remove('linkAA');
	}
	
    // modif 13/03/2007 Gwénaël
        // Suppression de l'iframe
    if(iframe_tooltip != null) {
        nomlayer.removeChild(iframe_tooltip); //Suppression de l'iframe du tooltip affiché
        iframe_tooltip = null;
    }
}

// modif  20/07/2007 Gwénaël
	// class JS pour le nouveau tooltip
	// le try est là au cas où prototype n'est pas chargé
try {
	Tooltip = Class.create();

	Tooltip.prototype = {
		initialize: function( txt ) {
			this.element = $('topdeck');
			this.text = Object.extend({
				content : txt,
				title: arguments[1] || false
				});
			this.createTip();
			this.showTip();
			
			},
		createTip: function() {
			this.topdeck = document.createElement('div');
			this.topdeck.className = 'tooltip';
			Element.setStyle(this.topdeck, {
				position: 'absolute'
			});
			
			this.tooltip = document.createElement('div');
			this.tooltip.className = 'content';
			// si il y a un titre on l'affiche
			if(this.text.title) {
				var title = document.createElement('div');
				title.className = 'title';
				Element.update(title, this.text.title);
				this.tooltip.appendChild(title);
			}
			// ajout du texte
			var content = document.createElement('div');
			content.className = 'text';
			Element.update(content, this.text.content);
			this.tooltip.appendChild(content);
			
			//fleche
			this.divimg = document.createElement('div');
			this.divimg.className = 'fleche';
			this.topdeck.appendChild(this.divimg);
			
			this.topdeck.appendChild(this.tooltip);
			document.body.appendChild(this.topdeck);
		},
		showTip: function(event){
			this.positionTip(event);
			this.topdeck.show();
		},
		hideTip: function(){
			this.topdeck.hide();
			var el = this.getEl();
			el.stopObserving('mouseout', this.hideTip.bind(this));
		},
		positionTip: function(){
			var el = this.getEl();
			el.observe('mouseout', this.hideTip.bind(this));
			// positionnement du tooltip par rapport au pointer de la souris
			var offsets = {'x': -15,'y': 20};
			var offsetsSave = {'x': -10,'y': 21};
			var mouse = {'x': this.getPositionMouse()['x'], 'y': this.getPositionMouse()['y']};
			var page = {'x':this.getWindowSize()['x'], 'y':this.getWindowSize()['y']};
			var tip = {'x': mouse['x'] + offsets['x'] + this.topdeck.getWidth() , 'y' : mouse['y'] + offsets['y'] + this.topdeck.getHeight()};
			
			if(tip['x']>page['x']) {
				offsets['x'] = 0-(tip['x'] - page['x'] - offsets['x']);
				if(tip['y']>page['y']) 
					offsets['y'] = 0-(this.topdeck.getHeight() + offsets['y']);
			}
			else if(tip['y']>page['y']) {
				offsets = {'x': 25,'y': 0-(tip['y'] - page['y'] - offsets['y'])};
				offsetsSave = {'x': 17,'y': 0};
			}
			// positionnement différent un fonction des offsets
			var position = {'x':mouse['x'] + offsets['x'] + this.getScroll()['x'],'y':mouse['y'] + offsets['y'] + this.getScroll()['y']};
			var position2 = {'x':mouse['x'] + offsetsSave['x'] + this.getScroll()['x'],'y':mouse['y'] + offsetsSave['y'] + this.getScroll()['y']};
			
			//positionnemet du message
			this.topdeck.setStyle({
				left: position['x']+'px',
				top: position['y']+'px'
			});
			// positionnement de la fleche
			Element.setStyle(this.divimg, {
				left: position2['x'] - position['x'] + 'px',
				top: position2['y'] - position['y'] + 'px'
			});
		},
		getEl:function() {
			return $(Event.element(window.event));
		},
		getWindowSize : function(){
			var x = self.innerWidth || (document.documentElement.clientWidth || document.body.clientWidth);
			var y = self.innerHeight || (document.documentElement.clientHeight || document.body.clientHeight);
			return {'x': x, 'y': y};
		},	
		getPositionMouse : function() {
			var x = window.event.clientX;
			var y = window.event.clientY;
			return {'x': x, 'y': y};
		},
		getScroll: function () {
			var x = document.body.scrollLeft;
			var y = document.body.scrollTop;
			return {'x': x, 'y': y}
		}
	};
}
catch(e){
	useOldTooltip = true;
}