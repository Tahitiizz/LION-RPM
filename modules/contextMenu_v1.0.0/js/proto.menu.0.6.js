/*
	28/05/2009 GHX
		- Modification du script pour pouvoir ajouter des menus de façon temporaire dans le menu contextuel

*/

/** 
 * @description		prototype.js based context menu
 * @author        Juriy Zaytsev; kangax [at] gmail [dot] com; http://thinkweb2.com/projects/prototype/
 * @version       0.6
 * @date          12/03/07
 * @requires      prototype.js 1.6
*/

if (Object.isUndefined(Proto)) {var Proto = { }}

Proto.Menu = Class.create({
	initialize: function() {
		var e = Prototype.emptyFunction;
		this.ie = Prototype.Browser.IE;
		this.options = Object.extend({
			selector: '.contextmenu',
			className: 'protoMenu',
			pageOffset: 25,
			fade: false,
			zIndex: 100,
			beforeShow: e,
			beforeHide: e,
			beforeSelect: e
		}, arguments[0] || { });
		
		// 09:37 27/05/2009 GHX
		this.menuTempToAdd = [];
		this.menuTempToDelete = [];
		
		this.shim = new Element('iframe', {
			style: 'position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);display:none',
			src: 'javascript:false;',
			frameborder: 0
		});
		
		this.options.fade = this.options.fade && !Object.isUndefined(Effect);
		this.container = new Element('div', {className: this.options.className, style: 'display:none'});
		this.list = new Element('ul');
		this.options.menuItems.each(function(item) {
			this.list.insert(
				new Element('li', {className: item.separator ? 'separator' : ''}).insert(
					item.separator 
						? '' 
						: Object.extend(new Element('a', {
							href: '#',
							title: item.name,
							className: (item.className || '') + (item.disabled ? ' disabled' : ' enabled')
						}), {_callback: item.callback})
						.observe('click', this.onClick.bind(this))
						.observe('contextmenu', Event.stop)
						.update(item.name)
				)
			)
		}.bind(this));
		$(document.body).insert(this.container.insert(this.list).observe('contextmenu', Event.stop));
		if (this.ie) {$(document.body).insert(this.shim)}
		
		document.observe('click', function(e) {
			if (this.container.visible() && !e.isRightClick()) {
				this.deleteMenuTemp();
				this.options.beforeHide(e);
				if (this.ie) this.shim.hide();
				this.container.hide();
			}
		}.bind(this));
		
		$$(this.options.selector).invoke('observe', Prototype.Browser.Opera ? 'click' : 'contextmenu', function(e){
			if (Prototype.Browser.Opera && !e.ctrlKey) {
				return;
			}
			this.show(e);
		}.bind(this));
                
                this.lastCaller = null;
	},
	show: function(e) {

            // 05/04/2012 BBX
            // BZ 26542 : Lorsque l'on effectue plusieurs cliques droits
            // On ne détruit et recréé les menus temporaires que si l'élément survolé a changé
            // Ou si la liste était préalablement vide
            nbTempMenu = this.menuTempToAdd.size();            
            newCaller = Event.element(e);
            menuHidden = this.container.getStyle('display') == 'none';
            if(menuHidden || this.lastCaller != newCaller || nbTempMenu > 0)
            {
                // >>>>>
                // 09:49 28/05/2009 GHX
                this.deleteMenuTemp();
                for (var index = 0, len = this.menuTempToAdd.size(); index < len; ++index) {
                        this.addMenu(this.menuTempToAdd[index]);
                        this.menuTempToDelete[index] = this.menuTempToAdd[index].id;
                }
                this.menuTempToAdd = [];
                // <<<<<
            }            
            this.lastCaller = newCaller;
            // Fin BZ 26542

		e.stop();
		this.options.beforeShow(e);
		var x = Event.pointer(e).x,
			y = Event.pointer(e).y,
			vpDim = document.viewport.getDimensions(),
			vpOff = document.viewport.getScrollOffsets(),
			elDim = this.container.getDimensions(),
			elOff = {
				left: ((x + elDim.width + this.options.pageOffset) > vpDim.width 
					? (vpDim.width - elDim.width - this.options.pageOffset) : x) + 'px',
				top: ((y - vpOff.top + elDim.height) > vpDim.height && (y - vpOff.top) > elDim.height 
					? (y - elDim.height) : y) + 'px'
			};
		this.container.setStyle(elOff).setStyle({zIndex: this.options.zIndex});
		if (this.ie) { 
			this.shim.setStyle(Object.extend(Object.extend(elDim, elOff), {zIndex: this.options.zIndex - 1})).show();
		}
		this.options.fade ? Effect.Appear(this.container, {duration: 0.25}) : this.container.show();
		this.event = e;
	},
	onClick: function(e) {
		e.stop();
		if (e.target._callback && !e.target.hasClassName('disabled')) {
			this.options.beforeSelect(e);
			if (this.ie) this.shim.hide();
			this.container.hide();
			e.target._callback(this.event);
		}
	},
	// 09:23 27/05/2009 GHX
	// Ajout de la fonction
	/**
	 * Supprime les menus temporaires du menus contextuel*
	 */
	deleteMenuTemp: function() {
		for (var index = 0, len = this.menuTempToDelete.size(); index < len; ++index) {
			this.deleteMenu(this.menuTempToDelete[index]);
		}
		this.menuTempToDelete = [];
	
	},
	// 17:30 26/05/2009 GHX
	// Ajout le fonction qui permet d'ajouter un menu
	/**
	 * Ajout un menu du menu contextuel
	 *
	 * @param item
	 */
	addMenu: function (item) {
		this.list.insert(
			new Element('li', {className: item.separator ? 'separator' : '', id: item.id ? item.id : ''}).insert(
				item.separator 
					? '' 
					: Object.extend(new Element('a', {
						href: '#',
						title: item.name,
						className: (item.className || '') + (item.disabled ? ' disabled' : ' enabled')
					}), {_callback: item.callback})
					.observe('click', this.onClick.bind(this))
					.observe('contextmenu', Event.stop)
					.update(item.name)
			)
		);
	},
	// 08:39 27/05/2009 GHX
	// Ajout de la fonction qui permet de supprimer un menu
	/**
	 * Supprime un menu du menu contextuel
	 *
	 * @param (string) id identifiant du menu a supprimer
	 */
	deleteMenu: function (id) {
		if ( this.list.select('li#'+id).size() == 1 )
		{
			this.list.select('li#'+id)[0].remove();
		}
	},
	// 09:25 27/05/2009 GHX
	// Ajout de la fonction
	/**
	 * Ajouter un menu pour la prochaine fois qu'on affiche le menu contextuel.
	 *
	 * @param item
	 */
	addMenuTemp: function (item) {
		this.menuTempToAdd[this.menuTempToAdd.size()] = item;
	},
	/**
	 * Supprimer un menu avant qu'il ne soit affiché dans le menu contextuel
	 *
	 * @param (string) item identifiant du menu a supprimer
	 */
	removeMenuTemp: function(item) {
		var tmp = [];
		for (var index = 0, len = this.menuTempToAdd.size(); index < len; ++index) {
			if ( this.menuTempToAdd[index].id != item )
			{
				tmp[tmp.size()] = this.menuTempToAdd[index];
			}
		}
		this.menuTempToAdd = tmp;
	}
	
})