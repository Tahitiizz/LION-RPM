var MinMapControl = Class.create();
MinMapControl.prototype = {

	/**
	 * Constructeur de la classe
	 * 
	 * @access public
	 * @param 
	 * @return void
	 */	
	initialize: function(target, control){

		this.target		= $(target);
		this.control	= control;		// reference à l'instance du controleur

		// On commence par vider le contenu de la target au cas où il serait non vide au départ

		this.target.innerHTML = '';

		// Définition de la viewbox max uniformisée

		this.max_vb = Array();
		for (var i=0;i<this.control.max_vb.length;i++) this.max_vb[i] = this.control.max_vb[i];

		if (this.max_vb[2] > this.max_vb[3])	// largeur > hauteur
		{
			var vb_height = this.max_vb[3];
			this.max_vb[3] = this.max_vb[2];
			this.max_vb[1] -= (this.max_vb[3] - vb_height)/2;
		}
		else if (this.max_vb[3] > this.max_vb[2])	// hauteur > largeur
		{
			var vb_width = this.max_vb[2];
			this.max_vb[2] = this.max_vb[3];
			this.max_vb[0] -= (this.max_vb[2] - vb_width)/2;
		}

		// Génération de l'image de la miniature via l'appel du script 'gis_miniature.php' en Ajax
		
		new Ajax.Request(this.control.niveau0+'gis/gis_scripts/gis_miniature.php', 
		{
			method: 'get',
			parameters: "side=100"/*+this.target.offsetWidth*/,
			onComplete : this.generateMinRaster.bindAsEventListener(this)
		});
	},

	generateMinRaster: function(data) {

		// Définition de l'image contenant le vue globale du GIS

		this.mini_img = Builder.node('img', {'id':'mini_img', 'src':data.responseText, 'style':'width:100px;height:100px'}, "");
		this.target.appendChild(this.mini_img);

		// Definition du calque representant la vue du GIS

		this.map_view = Builder.node('div', {'style':'position:absolute;width:3%;height:3%;background-color:red;line-height:0px;font-size:0pt;display:block'}, "");
		this.target.appendChild(this.map_view);

		// Mise à jour de la vue de la map

		this.update();

		// Ajout des ecouteurs d'evenements de la miniature

		Event.observe(window, 'load', this.extendEvent);

		this.mouseDownMinBind = this.mouseDownMin.bindAsEventListener( this );	
		Event.observe( this.target, 'mousedown', this.mouseDownMinBind );
		
		this.mouseUpMinBind = this.mouseUpMin.bindAsEventListener( this );
		Event.observe( this.target, 'mouseup', this.mouseUpMinBind );

		this.target.style.visibility = 'visible';
	},

	/**
	 * Starts the drag
	 * 
	 * @access private
	 * @param obj Event
	 * @return void
	 */
	
	mouseDownMin: function( evt ) {
		
		if(!Event.isLeftClick(evt)) return;
    	Event.stop( evt );
	},

	/**
	 * Ends the crop & passes the values of the select area on to the appropriate 
	 * callback function on completion of a crop
	 * 
	 * @access private
	 * @return void
	 */
	mouseUpMin : function( evt ) {

		if(!Event.isLeftClick(evt)) return;

		var clickCoords = this.getCurPos(evt);

		// Décalage de la vue de la map pour qu'elle soit centrée

		var mini_x = clickCoords.x-this.map_view.offsetWidth/2;
		var mini_y = clickCoords.y-this.map_view.offsetHeight/2;

		Element.setStyle(this.map_view, {'left':mini_x+'px', 'top':mini_y+'px'});

		// Dimensions de la miniature

		var topWidth	= this.target.offsetWidth;
		var topHeight	= this.target.offsetHeight;

		// Ratio x et y de la viewbox par rapport à la taille du div miniature

		var ratioVB_x = this.max_vb[2] / topWidth;
		var ratioVB_y = this.max_vb[3] / topHeight;

		// Definition des nouvelles coordonnées de la vue de la carte dans la miniature

		var map_x = Number(this.max_vb[0])+Number(mini_x*ratioVB_x);
		var map_y = Number(this.max_vb[1])+Number(mini_y*ratioVB_y);

		// Decalage de la vue par rapport aux dimensions de la viewbox courante

		map_x = map_x-Number(this.control.viewbox[2]/2);
		map_y = map_y-Number(this.control.viewbox[3]/2);

		// Mise à jour de la map

		this.control.setNewViewBox(map_x, map_y, this.control.viewbox[2], this.control.viewbox[3]);
		this.control.changeViewBox();

		Event.stop( evt );
	},

	update: function() {

		// Dimensions de la miniature

		var topWidth	= this.target.offsetWidth;
		var topHeight	= this.target.offsetHeight;

		// Ratio x et y de la viewbox par rapport à la taille du div miniature

		var ratioVB_x = this.max_vb[2] / topWidth;
		var ratioVB_y = this.max_vb[3] / topHeight;

		// Definition des nouvelles coordonnées de la vue de la carte dans la miniature

		var mini_x = this.control.viewbox[0]/ratioVB_x-this.max_vb[0]/ratioVB_x;
		var mini_y = this.control.viewbox[1]/ratioVB_y-this.max_vb[1]/ratioVB_y;

		mini_x += (this.control.viewbox[2]/2)/ratioVB_x;
		mini_y += (this.control.viewbox[3]/2)/ratioVB_y;

		// Mise à jour du style de la vue de la carte

		Element.setStyle(this.map_view, {'left':(mini_x-(this.map_view.offsetWidth/2))+'px', 'top':(mini_y-(this.map_view.offsetHeight/2))+'px'});
	},

	destroy: function(){

		this.target.style.visibility = 'hidden';
		
		if (this.map_view != undefined) this.target.removeChild(this.map_view);
		if (this.mini_img != undefined) this.target.removeChild(this.mini_img);
		
		// Suppression des ecouteurs d'evenements de la miniature

		Event.stopObserving( window, 'load', this.extendEvent );
		Event.stopObserving( this.target, 'mousedown', this.mouseDownMinBind );		
		Event.stopObserving( this.target, 'mouseup', this.mouseUpMinBind );
	},

	/**
	 * Gets the current cursor position relative to the image
	 * 
	 * @access private
	 * @param obj Event
	 * @return obj x,y pixels of the cursor
	 */
	
	getCurPos: function( e ) {
		// get the offsets for the wrapper within the document
		var el = this.target, wrapOffsets = Position.cumulativeOffset( el );
		// remove any scrolling that is applied to the wrapper (this may be buggy) - don't count the scroll on the body as that won't affect us
		while( el.nodeName != 'BODY' ) {
			wrapOffsets[1] -= el.scrollTop  || 0;
			wrapOffsets[0] -= el.scrollLeft || 0;
			el = el.parentNode;
	    }		
		return curPos = { 
			x: Event.pointerX(e) - wrapOffsets[0],
			y: Event.pointerY(e) - wrapOffsets[1]
		}
	},  	

	extendEvent: function()
	{
		Object.extend
		(
			Event,
			{
				WHICH_LEFT:   (navigator.appVersion.match(/\bMSIE\b/)) ? 1 : 1,
				WHICH_RIGHT:  (navigator.appVersion.match(/\bMSIE\b/)) ? 1 : 3,
				WHICH_MIDDLE: (navigator.appVersion.match(/\bMSIE\b/)) ? 1 : 2,
				MOUSE_LEFT:   (navigator.appVersion.match(/\bMSIE\b/)) ? 1 : 0,
				MOUSE_RIGHT:  (navigator.appVersion.match(/\bMSIE\b/)) ? 2 : 2,
				MOUSE_MIDDLE: (navigator.appVersion.match(/\bMSIE\b/)) ? 4 : 1,

				isLeftClick: function(event)
				{
					return (((event.which) && (event.which == Event.WHICH_LEFT)) ||
					((event.button) && (event.button == Event.MOUSE_LEFT)));
				},

				isRightClick: function(event)
				{
					return (((event.which) && (event.which == Event.WHICH_RIGHT)) ||
					((event.button) && (event.button == Event.MOUSE_RIGHT)));
				},

				isMiddleClick: function(event)
				{
					return (((event.which) && (event.which == Event.WHICH_MIDDLE)) ||
					((event.button) && (event.button == Event.MOUSE_MIDDLE)));
				}
			}
		);
	}
}