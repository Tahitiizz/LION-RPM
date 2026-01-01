var isie=0;
if(window.navigator.appName=="Microsoft Internet Explorer"&&window.navigator.appVersion.substring(window.navigator.appVersion.indexOf("MSIE")+5,window.navigator.appVersion.indexOf("MSIE")+8)>=5.5)
	isie=1;

// modif 27/06/2007 Gwénaël
	// Création d'une classe pour le menu contextuel
// modif 11:14 14/11/2007 Gwénaël
	// Ajout d'un icone pour les items
/**
 * Constructeur ContextMenu
 */
ContextMenu = function () {
	this.HAUTEUR_ITEM = 30;
	this.HAUTEUR_SEPARATEUR = 2;
	this.itemsMenu = new Array();
	this.oPopup = window.createPopup();
	this.html = '', this.htmlStyle = '', this.htmlJS = '';
	this.htmlIMG = new Array();
	this.config = {};
	this.isChanged = true;
	this.nbMenu = 1; // Comme à 1 puisque Refresh est toujours présent
	this.nbSeparateur = 0;
	//Ajout pas défaut le item Refresh
	// modif 11:14 14/11/2007 Gwen
		// ajout du paramètre icone
	this.itemsMenu[ 0 ] = { nom : 'Refresh', url : 'lien = window.parent.location.href; lien=lien.split(\'#\'); window.parent.location.href = lien[0];', img: 0, ref : 'refresh', icone : '' };
};
ContextMenu.prototype = {
	/**
	 * initialisation
	 */
	init : function () {
		this.htmlStyle = '';
		this.htmlStyle += '<style type="text/css">';
		this.htmlStyle += 'a:link {text-decoration:none;font-family:Verdana,Arial, sans-serif;font-size:8pt; color:#585858;}';
		this.htmlStyle += 'a:visited {text-decoration:none;font-family:Verdana,Arial, sans-serif;font-size:8pt; color:#585858;}';
		this.htmlStyle += 'table {border:1pt solid #000000; border-left:1pt solid #000000; border-top:1pt solid #000000; background-color:'+ this.config.couleur_fond_page +'; width:150px; border-collapse: collapse; border-spacing: 0; }';
		this.htmlStyle += 'td.action {font-size:8pt; border: 1pt solid '+ this.config.couleur_fond_page +'; cursor:pointer;}';
		this.htmlStyle += 'td.separator { border:1pt solid '+ this.config.couleur_fond_page +'; text-align:center }';
		this.htmlStyle += 'td.hiddenImg img{  visibility:hidden }';
		this.htmlStyle += 'span {color:#585858; font: normal 7pt Verdana;}';
		this.htmlStyle += 'img.image {border:0; width:12px; height:12px}';
		this.htmlStyle += 'img.image2 {border:1pt solid '+ this.config.couleur_fond_page +'; width=140px; height=1px;}';
		this.htmlStyle += '</style>';

		this.htmlJS = '';
		this.htmlJS +='<SCRIPT LANGUAGE="JavaScript">\n';
		this.htmlJS +='\n<'+'!--\n';
		this.htmlJS +='window.onerror=null;\n';
		this.htmlJS +='/'+' -'+'->\n';
		this.htmlJS +='</'+'SCRIPT>\n';	

		// icone par défaut si on ne le précise pas

		// 11/01/2008 - Modif. benoit : changement d'image refresh ("/menu_contextuel/menurefresh.gif" => "menu_contextuel/refresh.png")

		// 11/01/2008 - Modif. benoit : changement d'image source ("/menu_contextuel/menusource.gif" => "menu_contextuel/default.png")

		this.htmlIMG[0] = '<img class="image" src="'+ this.config.niveau4_vers_images +'/menu_contextuel/refresh.png" hspace="0" vspace="0" align="absmiddle" />';
		this.htmlIMG[1] = '<img class="image" src="'+ this.config.niveau4_vers_images +'/menu_contextuel/default.png" hspace="0" vspace="0" align="absmiddle" />';
		this.htmlIMG[2] = '<img class="image2" src="'+ this.config.niveau4_vers_images +'/menu_contextuel/pixel.gif" />';
	},

	/**
	 * Défini la configuration du menu
	 * couleur_fond_page / couleur_fond_over / niveau4_vers_images
	 */
	setConfig : function ( conf ) {
		this.config = conf;
	},

	/**
	 *Ajout un item au menu contextuel, mettre un nom vide '' pour ajouter un séparateur
	 *
	 *	- modif 11:14 14/11/2007 Gwen : ajout d'un paramètre _nom_icone
	 *
	 * @param string _ref_item : référence de l'item qui permettra notemment de le supprimer
	 * @param string _nom_action :  nom de l'item
	 * @param string _url_action : action de l'item qui sera exécuter lors du onClick
	 * @param string _nom_icone : nom de l'icone associé à l'item du menu contextuel
	 */
	add : function ( _ref_item, _nom_action, _url_action, _nom_icone ) {
		if ( _ref_item == undefined )
			_ref_item = this.itemsMenu.length;
		this.itemsMenu[ this.itemsMenu.length ] = { nom : _nom_action, url : _url_action, img: 1 , ref : _ref_item, icone : _nom_icone };
		if ( _nom_action != '' ) 
			this.nbMenu++;
		else
			this.nbSeparateur++;
		this.isChanged = true;
	},

	/**
	 * Supprime un item du menu contextuel
	 *
	 *@param string _ref_item : référence de l'item à supprimer
	 */
	remove : function ( _ref_item ) {
		var tmpItemsMenu = new Array();
		var compteur = 0;
		for ( var i = 0; i < this.itemsMenu.length; i++ ) {
			if ( this.itemsMenu[i].ref == _ref_item ) {
				if ( this.itemsMenu[i].nom != '' )
					this.nbMenu--;
				else
					this.nbSeparateur--;
				this.isChanged = true;
			}
			else
				tmpItemsMenu[compteur++] = this.itemsMenu[i];
		}
		this.itemsMenu = tmpItemsMenu;
	},

	/**
	 * Affiche le menu contextuel
	 */
	show : function ( x, y ) {
		this._createHTML();
		var oPopupBody = this.oPopup.document.body;
		oPopupBody.innerHTML = this.html;
		this.oPopup.show(x, y, 150, ( this.nbMenu * this.HAUTEUR_ITEM + this.nbSeparateur * this.HAUTEUR_SEPARATEUR ), document.body);
	},
	
	/**
	 * Masque le menu
	 */
	hide : function () {
		this.oPopup.hide();
	},

	/**
	 * @private function
	 */
	_createHTML : function () {
		// Si le menu n'a pas changé pas la même de le recréé
		if ( this.isChanged == false )
			return;
		this.isChanged = false;
		this.html = '<table height="'+ ( this.nbMenu * this.HAUTEUR_ITEM + this.nbSeparateur * this.HAUTEUR_SEPARATEUR ) +'">';
		this.html += this.htmlStyle;
		this.html +=this. htmlJS;
		//for ( var i in this.itemsMenu ) {
		for ( var i = 0; i < this.itemsMenu.length; i++ ) {
			
			this.html += '<tr>';
			if ( this.itemsMenu[i].nom == '' ) {
				this.html += '<td class="separator">';
				this.html += this.htmlIMG[2];
			}
			else { 
				this.html += '<td class="action" id="i'+i+'" onMouseOver="'+ this._onmouseover(i)+'" onMouseOut="'+ this._onmouseout(i)+'" onClick="'+this.itemsMenu[i].url+'">';
				this.html += '&nbsp;';
				// modif 11:14 14/11/2007 Gwen
					// modif pour prendre en compte une icone pour le menu contextuel
				if ( this.itemsMenu[i].icone == "" ){
					this.html += this.htmlIMG[this.itemsMenu[i].img];
				}
				else
				{
					// 11/01/2008 - Modif. benoit : le nom de l'icone stocké en base comprend maintenant l'ensemble du chemin vers celui-ci. 
					// On supprime donc la reference au dossier "menu_contextuel/"

					this.html += '<img class="image" src="'+ this.config.niveau4_vers_images+this.itemsMenu[i].icone+'" hspace="0" vspace="0" align="absmiddle" />';
				}
				
				this.html += '&nbsp;';
				this.html += '<span id="j'+i+'">'+this.itemsMenu[i].nom+'</span>';
			}
			this.html += '</td>';
			this.html += '</tr>';
		}
		this.html += '</table>';
	},

	/**
	 * @private function
	 * @param integer numID: numéro identifiant
	 */
	_onmouseover : function (numID) {
		var mouseover = 'document.all.i'+numID+'.style.background = \''+ this.config.couleur_fond_over +'\';';
		mouseover += 'document.all.i'+numID+'.style.border = \'1pt solid #737B92\';';
		mouseover += 'document.all.j'+numID+'.style.color = \'#FFFFFF\';';
		mouseover += 'document.all.j'+numID+'.style.font = \'bold 7pt Verdana\';';
		return mouseover;
	},

	/**
	 * @private function
	 * @param integer numID: numéro identifiant
	 */
	_onmouseout : function (numID) {
		var mouseout = 'document.all.i'+numID+'.style.background = \''+ this.config.couleur_fond_page +'\';';
		mouseout += 'document.all.i'+numID+'.style.border = \'1pt solid '+ this.config.couleur_fond_page +'\';';
		mouseout += 'document.all.j'+numID+'.style.color = \'#585858\';';
		mouseout += 'document.all.j'+numID+'.style.font = \'normal 7pt Verdana\';';
		return mouseout;
	}
};
var menuContextuel = new ContextMenu();