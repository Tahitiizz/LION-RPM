var GisControl = Class.create();
GisControl.prototype = {

	/**
	 * Constructeur de la classe
	 * 
	 * @access public
	 * @param string Nom du div de control des actions sur le GIS
	 * @param object Ensemble des noms des div cible
	 * @param object Ensemble des options du GIS
	 * @return void
	 */
	initialize: function(controller, targets, options){
		
		this.controller		= $(controller);
		this.ctrlArea		= $(targets.ctrlArea);
		this.viewArea		= $(targets.viewArea);
		this.loadArea		= $(targets.loadArea);
		this.miniArea		= targets.miniArea;
		this.scaleArea		= targets.scaleArea;
		this.processArea	= $(targets.processArea);

		this.noResultsArea	= null;

		// Variables d'entree

		this.initial_options	= options;
		this.initial_viewbox	= options.viewbox.split(/ /);
		this.viewbox			= this.initial_viewbox;
		this.topWidth			= 0;								// largeur du embed
		this.topHeight			= 0;								// hauteur du embed
		this.niveau0			= options.niveau0;
		this.tab_paliers		= options.tab_paliers.split(',');
		this.current_zoom		= options.current_zoom;
		this.max_vb				= options.view_origine.split(',');	// viewbox maximale
		this.lock_action		= false;
		this.current_action		= options.current_action;
		this.raster_path		= options.raster_path;
		this.status				= options.status;
		this.opener				= options.opener;
		this.initial_dimensions	= options.controller_dims;

		// Variables locales

		this.original_zoom;
		this.flag_click		= false;	// etat du click (appuye : true, relache : false)
		this.newrect		= "";		// rectangle de selection au format SVG
		this.startx			= 0;		// coordonnee x lors du click (sert pour le trace du rectangle de sel.)
		this.starty			= 0;		// coordonnee y lors du click (sert pour le trace du rectangle de sel.)
		this.xsvg			= 0;		// coordonnee x de la souris (apres transformation) 
		this.ysvg			= 0;		// coordonnee y de la souris (apres transformation)
		this.old_viewbox	= Array();	// sert uniquement pour l'animation
		this.move_viewbox	= true;		// determine si l'on bouge la viewbox (zoom ou deplacement) ou non
		this.init_view		= false;	// indique si l'action 'init_view' est en cours
		this.desc_in_na		= false;	// indique si l'on descend dans les niveaux de na et, dans ce cas, entraine le rechargement de la legende
		this.saved_style	= Array();	// permet de sauvegarder les styles des layers lors des modifs de leurs proprietes

		this.timer = null;				// reference au 'PeriodicalExecuter()'. Permet d'arreter le declenchement de l'action qui lui est associee via 'clearTimeout()'
		this.info_coords		= null;
		this.info_coords_copy	= null;

		this.first_load = true;

		this.miniature	= null;
		this.scale		= null;

		//this.initial_dimensions = Array(this.controller.style.width, this.controller.style.height);

		// Variables de sortie

		this.loading			= false;	// sortie et locale : determine si un loading est en cours ou non
		this.new_viewbox		= Array();	// coordonnees de la nouvelle viewbox

		if (this.status == "ok")
		{

			this.onLoad();
		}
		else
		{
			this.displayNoResults();
		}
	},

	/**
	 * Actions effectuees au chargement : construction des div de control et la vue + attache les evenements souris
	 * 
	 * @access private
	 * @return void
	 */

	onLoad: function( ) {

		// Au chargement, on met a jour la vue du GIS avec le raster genere
		
		this.updateViewArea(this.raster_path);

		// On formate les dimensions du div de visualisation des actions du controleur et on le rend visible

		this.reloadCtrlArea();

		// Si une instance de "noResultsArea" est encore presente, on la supprime

		if ($('noResultsArea') != null)
		{
			$('noResultsArea').parentNode.removeChild($('noResultsArea'));
		}

		// On ajoute les ecouteurs d'evenements
		
		Event.observe(window, 'load', this.extendEvent);

		this.mouseDownBind = this.mouseDown.bindAsEventListener( this );	
		Event.observe( this.controller, 'mousedown', this.mouseDownBind );
		
		this.mouseMoveBind = this.mouseMove.bindAsEventListener( this );	
		Event.observe( this.controller, 'mousemove', this.mouseMoveBind );
		
		this.mouseUpBind = this.mouseUp.bindAsEventListener( this );
		Event.observe( this.controller, 'mouseup', this.mouseUpBind );

		// On cree une instance de la classe de la miniature
		
		this.miniature = new MinMapControl(this.miniArea, this);

		// On cree une instance de l'echelle

		this.scale = new ScaleControl(this.scaleArea, this);
	},

	displayNoResults: function(){

		this.lock_action = true;

		// On masque le loading

		this.loadArea.style.visibility = "hidden";
		
		// On masque la miniature et l'echelle

		$(this.miniArea).innerHTML = '';
		$(this.miniArea).style.visibility = "hidden";
		$(this.scaleArea).innerHTML = '';
		$(this.scaleArea).visibility = "hidden";

		// Si une instance de "noResultsArea" est encore presente, on la supprime

		if ($('noResultsArea') != null)
		{
			$('noResultsArea').parentNode.removeChild($('noResultsArea'));
		}

		// On cree le calque "No results"

		this.noResultsArea	= Builder.node('table', {'id':'noResultsArea', 'width':'100%', 'height':'100%', 'style':'position:absolute;left:0px;top:0px;visibility:hidden'});
		var tbody			= Builder.node('tbody');
		var tr				= Builder.node('tr');
		var td				= Builder.node('td');
		var img				= Builder.node('img', {'src':'gis_icons/no_result_gis.png'});

		td.appendChild(img);
		Element.setStyle(td, {'vertical-align':'middle'});
		td.align = "center";
		tr.appendChild(td);
		tbody.appendChild(tr);
		this.noResultsArea.appendChild(tbody);

		this.controller.appendChild(this.noResultsArea);

		// Si la taille de la zone de control a des dimensions inferieures a l'image "No results", on la redimensionne

		var controllerWidth		= this.controller.offsetWidth;
		var controllerHeight	= this.controller.offsetHeight;

		if(controllerWidth < 0 || isNaN(controllerWidth)) controllerWidth = 400;
		if(controllerHeight < 0 || isNaN(controllerHeight)) controllerHeight = 400;

		if ((controllerWidth < 195) || (controllerHeight < 41))
		{
			(controllerWidth >= controllerHeight) ? ratio = controllerHeight/400 : ratio = controllerWidth/400;
			Element.setStyle(img, {'width':195*ratio, 'height':41*ratio});
		}

		Element.setStyle(this.noResultsArea, {'visibility':'visible'});
	},

	/**
	 * Reformatage la zone de selection du controleur 
	 * 
	 * @access private
	 * @return void
	 */	

	reloadCtrlArea: function() {

		// on masque le div de loading

		this.loadArea.style.visibility = "hidden";

		// on masque le div de processing

		this.processArea.style.visibility = "hidden";

		// raz des dimensions et des coordonnees

		this.ctrlArea.style.top		= "0px";
		this.ctrlArea.style.left	= "0px";
		this.ctrlArea.style.width	= "0px";
		this.ctrlArea.style.height	= "0px";

		// la zone de controle devient visible

		this.ctrlArea.style.visibility = "visible";
		// this.ctrlArea.style.visibility = "hidden";	// on masque la zone de selection
		this.loadArea.style.visibility = "hidden";	// on fait apparaitre le div de loading
	},

	/**
	 * Ensemble des actions a effectuer au click (appui)
	 * 
	 * @access private
	 * @param object Evenement souris
	 * @return void
	 */
	
	mouseDown: function( evt ) {

		// On teste si les infos d'initialisation ont bien ete definies sinon, on les redefinies
		
		if(!Event.isLeftClick(evt)) {
			this.naDesc();
			
			return;
		}
		
		if(this.lock_action) return;

		var clickCoords = this.getCurPos(evt);
		this.startx = clickCoords.x, this.starty = clickCoords.y;

		if (this.current_action == "zoom_in")
		{
			this.zoomDragRect(evt, "start");
			this.flag_click = true;
		}

		this.mouseMove( evt ); // incase the user just clicks once after already making a selection
    	Event.stop( evt );
	},

	/**
	 * Determine la position courante du curseur de la souris en fonction de la zone de visualisation
	 * 
	 * @access private
	 * @param object Evenement souris
	 * @return object coordonnees "reelles" du pointeur
	 */
	
	getCurPos: function( e ) {
		// get the offsets for the wrapper within the document
		var el = this.viewArea, wrapOffsets = Position.cumulativeOffset( el );
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

  	/**
  	 * Ensemble des actions a effectuer lors du deplacement du curseur de la souris
  	 * 
  	 * @access private
	 * @param object Evenement souris
	 * @return void
	 */
  	
	mouseMove: function( evt ) {
 
		if(this.lock_action) return;

		// 11/10/2007 - Note benoit : desactivation du timer, le reactiver avec peut etre une fonction prototype (ex. TimedObserver)

		// Initialisation du timer servant a la detection des infos des na en fonction des coordonnees de la souris

		if(this.timer != null) this.timer.stop();

		this.info_coords = this.getCurPos(evt);

		this.timer = new PeriodicalExecuter(this.searchNaInfo.bindAsEventListener(this), 1);	// Declaration du nouveau timer

		// Si le bouton de la souris est presse c'est que l'on est en cours de tracage du rectangle de selection		

		if(this.flag_click)
		{
			//var clickCoords = this.getCurPos(evt);
			//var client_x = clickCoords.x, client_y = clickCoords.y;			

			/*if(Math.abs(this.startx-client_x)>0 && Math.abs(this.starty-client_y)>0)
			{*/
				this.zoomDragRect(evt, "finish");
			/*}*/
		}
			
		Event.stop( evt ); // stop the default event (selecting images & text) in Safari & IE PC
	},

	/**
	 * Ensemble des actions a effectuer au relachement de la souris
	 * 
	 * @access private
	 * @param object Evenement souris
	 * @return void
	 */
	mouseUp : function( evt ) {

		if(!Event.isLeftClick(evt)) {
			//this.naDesc();
			return;
		}		
		if (this.lock_action) return;

		this.flag_click = false;

		var clickCoords = this.getCurPos(evt);
		var current_x = clickCoords.x, current_y = clickCoords.y;	

		// try
		// {
			if ((this.startx/current_x == 1) && (this.starty/current_y == 1))	// Cas d'un click simple (la position du curseur de depart et d'arrivee est la meme)
			{
				this.launchAction(evt);
			}
			else if (this.current_action == "zoom_in")	// Cas du trace du rectangle de selection (disponible uniquement pour l'action "zoom_in")
			{
				// 13/11/2007 - Modif. benoit : 

				// taille du div controleur

				var topWidth	= this.controller.offsetWidth;
				var topHeight	= this.controller.offsetHeight;

				// Ratio x et y de la viewbox par rapport a la taille du div controleur

				var ratioVB_x = this.viewbox[2] / topWidth;
				var ratioVB_y = this.viewbox[3] / topHeight;
			
				var new_vb = Array	(	
										(Number(this.viewbox[0]))+this.ctrlArea.offsetLeft*ratioVB_x, 
										(Number(this.viewbox[1]))+this.ctrlArea.offsetTop*ratioVB_y, 
										this.ctrlArea.offsetWidth*ratioVB_x, 
										this.ctrlArea.offsetHeight*ratioVB_y
									);

				var nextWidth	= new_vb[2];
				var nextHeight	= new_vb[3];
				
				if (Number(nextHeight) >= Number(nextWidth))
				{
					nextHeight	= (nextWidth/topWidth)*topHeight;

					var xsvg	= new_vb[0];
					var ysvg	= new_vb[1]-(Number(nextHeight)-Number(new_vb[3]))/2;					
				}
				else
				{
					nextWidth	= (nextHeight/topHeight)*topWidth;

					var xsvg	= new_vb[0]-(Number(nextWidth)-Number(new_vb[2]))/2;
					var ysvg	= new_vb[1];
				}

				// On definit le prochain zoom

				// 13/11/2007 - Modif. benoit : 

				var zoom_by_width	= Number(this.max_vb[2])/nextWidth;
				var zoom_by_height	= Number(this.max_vb[3])/nextHeight;

				// 28/12/2007 - Modif. benoit : la definition du zoom courant se fait en fonction de la largeur ou la hauteur maximale de la viewbox

				if (Number(this.max_vb[2]) > Number(this.max_vb[3]))
				{
					this.current_zoom = Number(this.max_vb[2])/nextWidth;
				}
				else
				{
					this.current_zoom = Number(this.max_vb[3])/nextHeight;
				}

				//this.current_zoom = Number(this.max_vb[2])/nextWidth;

				if(this.current_zoom > this.tab_paliers[this.tab_paliers.length-1]){
					this.current_zoom = this.tab_paliers[this.tab_paliers.length-1];
					
					nextWidthTmp	= nextWidth;
					nextHeightTmp	= nextHeight;
					nextWidth		= this.max_vb[2]/this.current_zoom;
					nextHeight		= this.max_vb[3]/this.current_zoom;
					xsvg			+= (nextWidthTmp - nextWidth)/2;
					ysvg			+= (nextHeightTmp - nextHeight)/2;
				}

				this.new_viewbox	= Array(xsvg, ysvg, nextWidth, nextHeight);
				this.viewbox		= this.new_viewbox;

				if(this.miniature != null) this.miniature.update();

				this.changeViewBox();
			}
		// }
	},

	/**
	 * Etend la classe Object() et fournit un ensemble d'informations sur l'evenement souris
	 * 
	 * @access private
	 * @return boolean
	 */

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
	},

	/**
	 * Permet la mise a jour de la zone de visualisation
	 * 
	 * @access private
	 * @param string chemin vers la nouvelle image de la zone de visualisation
	 * @return void
	 */

	updateViewArea: function(path){
		
		this.viewArea.style.backgroundImage = 'url('+path+')';
	
	},

	/**
	 * Lance une action en fonction de la valeur de l'action courante
	 * 
	 * @access private
	 * @param object Evenement souris
	 * @return void
	 */

	launchAction: function(evt){
		
		//if (current_action == undefined) current_action = window.getCurrentAction();

		switch(this.current_action)
		{
			case 'zoom_in'	:	this.zoom(evt, "in");
			break;
			case 'zoom_out'	:	this.zoom(evt, "out");
			break;
			case 'move'		:	this.move(evt);
			break;
			default		:	return;
		}
	},

	/**
	 * Trace du rectangle de selection
	 * 
	 * @access private
	 * @param object Evenement souris
	 * @param string action a effectuer
	 * @return void
	 */

	zoomDragRect: function(evt, action){

		if (action == "start")
		{
			try
			{			
				this.ctrlArea.style.left	= this.startx+"px";
				this.ctrlArea.style.top	= this.starty+"px";
			}
			catch (error){}
		}
		else
		{
			var clickCoords = this.getCurPos(evt);
			var client_x = clickCoords.x, client_y = clickCoords.y;
			
			// Nouvelle taille du rectangle de selection
			
			var width = 0, height = 0;

			if (client_x > this.startx)
			{
				width = client_x - this.startx;
			}
			else
			{
				width = this.startx - client_x;
				this.ctrlArea.style.left = this.startx - width;
			}

			if (client_y > this.starty)
			{
				height = client_y - this.starty;
			}
			else
			{
				height = this.starty - client_y;
				this.ctrlArea.style.top = this.starty - height;
			}
			
			this.ctrlArea.style.width = width+"px";
			this.ctrlArea.style.height = height+"px";
		}
	},

	/**
	 * Realise les zooms(avant, arriere)
	 * 
	 * @access private
	 * @param object Evenement souris
	 * @param string type de zoom a effectuer
	 * @return void
	 */

	zoom: function(evt, zoom_type)
	{
		if(!Event.isLeftClick(evt)) return;

		var debug = "";	// Temp

		var zoom		= 1;
		var next_zoom	= 0;
		var prec_zoom	= 0;
		
		var zoom_min_found = false;
		var zoom_max_found = false;

		// Si le zoom courant ne fait pas partie du tableau de zooms, on recherche la valeur dans le tableau la plus proche du zoom courant

		//debug += "tab_paliers : "+tab_paliers.join(',')+"\n";
		debug += "current_zoom : "+this.current_zoom+"\n";

		if(this.tab_paliers.indexOf(this.current_zoom) == -1) this.current_zoom = this.getCurrentZoom(this.current_zoom);

		//var current_zoom_next = current_zoom;
		//var current_zoom_prec = current_zoom;

		debug += "tab_paliers.indexOf : "+this.tab_paliers.indexOf(this.current_zoom)+"\n";
		debug += "current_zoom : "+this.current_zoom+"\n";

		// Definition du prochain zoom

		for (var i=0;i<this.tab_paliers.length;i++)
		{
			if (this.tab_paliers[i] == this.current_zoom)
			{
				// Recherche du zoom precedent ("zoom out")
				if(i > 0){
					prec_zoom = this.tab_paliers[i-1]/this.tab_paliers[i];
					current_zoom_prec = this.tab_paliers[i-1];
				}
				else
				{
					zoom_min_found = true;
					prec_zoom = 1;
					current_zoom_prec = this.current_zoom;
				}

				// Recherche du zoom suivant ("zoom in")
				if(i < this.tab_paliers.length-1){
					next_zoom = this.tab_paliers[i+1]/this.tab_paliers[i];
					current_zoom_next = this.tab_paliers[i+1];
				}
				else
				{
					zoom_max_found = true;
					next_zoom = 1;
					current_zoom_next = this.current_zoom;
				}
			}
		}

		if (zoom_type == "in")
		{
			zoom = next_zoom;
			this.current_zoom = current_zoom_next;
		}
		else if (zoom_type == "out")
		{
			zoom = prec_zoom;
			this.current_zoom = current_zoom_prec;
		}

		// Recuperation des coordonnees du curseur

		var clickCoords = this.getCurPos(evt);
		var xm = clickCoords.x, ym = clickCoords.y;

		// taille du div controleur

		var topWidth	= this.controller.offsetWidth;
		var topHeight	= this.controller.offsetHeight;

		// Ratio x et y de la viewbox par rapport a la taille du div controleur

		var ratioVB_x = this.viewbox[2] / topWidth;
		var ratioVB_y = this.viewbox[3] / topHeight;

		// Nouvelles tailles de la viewbox apres zoom

		if ((zoom_min_found && zoom_type == "out") || (zoom_max_found && zoom_type == "in"))
		{
			if (zoom_min_found)
			{
				var nextWidth	= this.max_vb[2];
				var nextHeight	= this.max_vb[3];

				// 13/11/2007 - Modif. benoit : 

				/*if (Number(nextWidth) >= Number(nextHeight))
				{
					var xsvg	= this.max_vb[0];
					var ysvg	= this.max_vb[1]-(Number(nextWidth)-Number(nextHeight))/2;
					nextHeight	= (nextWidth/topWidth)*topHeight;	
				}
				else
				{
					var xsvg	= this.max_vb[0]-(Number(nextHeight)-Number(nextWidth))/2;
					var ysvg	= this.max_vb[1];
					nextWidth	= (nextHeight/topHeight)*topWidth;
				}*/

				// 28/12/2007 - Modif. benoit : redefinition de tous les cas a traiter lors d'un zoom "out" en fonction des hauteurs/largeurs de la viewbox et de la zone de visualisation
				
				/*if (Number(nextWidth) > Number(nextHeight) && topWidth > topHeight)
				{
					nextWidth	= (nextHeight/topHeight)*topWidth;

					var xsvg	= this.max_vb[0]-(Number(nextWidth)-Number(this.max_vb[2]))/2;
					var ysvg	= this.max_vb[1];
				}

				if (Number(nextWidth) > Number(nextHeight) && topHeight > topWidth)
				{
					nextHeight	= (nextWidth/topWidth)*topHeight;

					var xsvg	= this.max_vb[0];
					var ysvg	= this.max_vb[1]-(Number(nextHeight)-Number(this.max_vb[3]))/2;	
				}

				if (Number(nextHeight) > Number(nextWidth) && topWidth > topHeight)
				{
					nextWidth	= (nextHeight/topHeight)*topWidth;

					var xsvg	= this.max_vb[0]-(Number(nextWidth)-Number(this.max_vb[2]))/2;
					var ysvg	= this.max_vb[1];
				}

				if (Number(nextHeight) > Number(nextWidth) && topHeight > topWidth)
				{
					nextHeight	= (nextWidth/topWidth)*topHeight;

					var xsvg	= this.max_vb[0];
					var ysvg	= this.max_vb[1]-(Number(nextHeight)-Number(this.max_vb[3]))/2;	
				}*/

				// 03/01/2008 - Modif. benoit : reformulation de la condition pour definir les dimensions de la vbox

				(Number(nextWidth) >= Number(nextHeight)) ? vbin = 1 : vbin = 0;
				(topWidth >= topHeight) ? tbin = 1 : tbin = 0;

				if (((vbin * 2 + tbin * 1) % 2) == 1)
				{
					nextWidth	= (nextHeight/topHeight)*topWidth;

					var xsvg	= this.max_vb[0]-(Number(nextWidth)-Number(this.max_vb[2]))/2;
					var ysvg	= this.max_vb[1];				
				}
				else
				{
					nextHeight	= (nextWidth/topWidth)*topHeight;

					var xsvg	= this.max_vb[0];
					var ysvg	= this.max_vb[1]-(Number(nextHeight)-Number(this.max_vb[3]))/2;				
				}
			}
			else	// zoom_max_found
			{
				var nextWidth	= this.max_vb[2]/this.current_zoom;
				var nextHeight	= this.max_vb[3]/this.current_zoom;

				// On determine les nouvelles coordonnees appliquees a la viewBox apres zoom

				var xsvg = Number(this.viewbox[0])+(xm)*ratioVB_x-(nextWidth/2);
				var ysvg = Number(this.viewbox[1])+(ym)*ratioVB_y-(nextHeight/2);		
			}
		}
		else
		{
			var nextWidth = this.viewbox[2]/zoom;
			var nextHeight = this.viewbox[3]/zoom;

			// On determine les nouvelles coordonnees appliquees a la viewBox apres zoom

			var xsvg = Number(this.viewbox[0])+(xm)*ratioVB_x-(nextWidth/2);
			var ysvg = Number(this.viewbox[1])+(ym)*ratioVB_y-(nextHeight/2);
		}

		// On modifie la viewBox

		//old_viewbox = viewbox;
		this.new_viewbox	= Array(xsvg, ysvg, nextWidth, nextHeight);
		this.viewbox		= this.new_viewbox;

		debug += "after - current_zoom : "+this.current_zoom+"\n";
		debug += "new_viewbox : "+this.new_viewbox+"\n";

		//updateMapMiniature();
		if(this.miniature != null) this.miniature.update();

		this.changeViewBox();
	},

	/**
	 * Deplacement de la zone de visualisation
	 * 
	 * @access private
	 * @param object Evenement souris
	 * @return void
	 */

	move: function(evt){

		// Recuperation des coordonnees du curseur

		var clickCoords = this.getCurPos(evt);
		var xm = clickCoords.x, ym = clickCoords.y;

		// taille du div controleur

		var topWidth	= this.controller.offsetWidth;
		var topHeight	= this.controller.offsetHeight;

		// Ratio x et y de la viewbox par rapport a la taille du div controleur

		var ratioVB_x = this.viewbox[2] / topWidth;
		var ratioVB_y = this.viewbox[3] / topHeight;

		// On determine les nouvelles coordonnees appliquees a la viewBox apres zoom

		var xsvg = Number(this.viewbox[0])+(xm)*ratioVB_x-(this.viewbox[2]/2);
		var ysvg = Number(this.viewbox[1])+(ym)*ratioVB_y-(this.viewbox[3]/2);

		// On modifie la viewBox

		this.new_viewbox	= Array(xsvg, ysvg, this.viewbox[2], this.viewbox[3]);
		this.viewbox		= this.new_viewbox;

		//updateMapMiniature();
		if(this.miniature != null) this.miniature.update();

		this.changeViewBox();
	},

	/**
	 * Permet de declencher le changement de la viewbox
	 * 
	 * @access private
	 * @return void
	 */

	changeViewBox: function(){
		
		if (!this.loading)
		{
			//window.sendStatut('Loading layers...');
			//window.setGisLoading(true);

			this.lock_action	= true;
			this.move_viewbox	= true;
			this.loading		= true;

			this.setLoadingScreen();

			new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
			{
				method: 'get',
				parameters: "action=update_vb&x="+this.new_viewbox[0]+"&width="+this.new_viewbox[2]+"&y="+this.new_viewbox[1]+"&height="+this.new_viewbox[3]+"&current_zoom="+this.current_zoom,
				onComplete : this.callback.bindAsEventListener(this)
			});
		}
	},

	/**
	 * Sauvegarde de la vue du GIS dans le caddy
	 * 
	 * @access public
	 * @return void
	 */

	sendMapToCaddy: function(){
		
		if ((this.loading == false) && (this.lock_action == false))
		{
			this.lock_action = true;

			this.ctrlArea.style.visibility = "hidden";		// on masque la zone de selection
			this.processArea.style.visibility = "visible";	// on fait apparaitre le div de processing

			new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_raster.php', 
			{
				method: 'get',
				onComplete : this.stopProcessing.bindAsEventListener(this)
			});	
		}
	},
	
	/**
	* Lien vers Google Earth
	*/
	sendMapToGearthFromGis: function(){
		
		if ((this.loading == false) && (this.lock_action == false))
		{
			this.lock_action = true;
			new Ajax.Request(this.niveau0+'gis/gis_scripts/export_gearth.php', 
			{
				method: 'get',
				parameters: "action=send_to_gearth_from_gis&x="+this.new_viewbox[0]+"&width="+this.new_viewbox[2]+"&y="+this.new_viewbox[1]+"&height="+this.new_viewbox[3]+"&current_zoom="+this.current_zoom,
				onComplete : this.stopProcessing.bindAsEventListener(this)
				
			});		
		
		}
	},
	/**
	 * Permet de delocker les actions sur le GIS et de lancer le rechargement de la zone de controle
	 * 
	 * @access private
	 * @return void
	 */

	stopProcessing: function(data){

		this.lock_action = false;
		this.reloadCtrlArea();
	},

	/**
	 * Charge un ecran d'attente pendant le chargement de donnees
	 * 
	 * @access private
	 * @return void
	 */

	setLoadingScreen: function(){

		this.ctrlArea.style.visibility = "hidden";	// on masque la zone de selection
		this.loadArea.style.visibility = "visible";	// on fait apparaitre le div de loading

	},

	/**
	 * Fonction declenchee au retour d'un appel Ajax
	 * 
	 * @access private
	 * @param object donnees retournees par le script serveur
	 * @return void
	 */

	callback: function(data){

		this.lock_action	= false;
		this.loading		= false;
		
		this.viewArea.style.backgroundImage = 'url('+data.responseText+')';

		// Dans le cas de la descente de niveau de na, on recharge les fenetres legend et layers

		if (this.desc_in_na)
		{
			this.opener.reloadLegend();
			this.opener.reloadLayers();

			this.desc_in_na = false;
		}

		// Temp.

		this.scale.update();

		this.reloadCtrlArea();	// on reformate la zone de selection du controleur

		// Si l'action a l'origine de l'appel du callback etait le rechargement de la vue initiale, 
		// on restaure la precedente action dans la fenetre du GIS

		if (this.init_view)
		{
			this.init_view = false;
			this.opener.changeAction(this.current_action);
		}
	},

	// 11/12/2007 - Modif. benoit : definition d'une fonction de callback propre a la descente de niveau de na 
	// pour integrer les modifications faites a la suite de l'integration du selecteur du GIS

	/**
	 * Fonction declenchee au retour de l'appel Ajax de descente de niveau de na
	 * 
	 * @access private
	 * @param object donnees retournees par le script serveur
	 * @return void
	 */

	callbackDescInNa: function(data){

		var params = eval('(' + data.responseText + ')');

		this.lock_action	= false;
		this.loading		= false;
		
		this.viewArea.style.backgroundImage = 'url('+params.url+')';

		// On recharge les fenetres legend et layers

		this.opener.reloadLegend();
		this.opener.reloadLayers();

		// Apres la descente de na, on met a jour la na du selecteur

		this.opener.changeNaSelecteur(params.new_na);

		this.desc_in_na = false;

		//this.scale.update();

		this.reloadCtrlArea();	// on reformate la zone de selection du controleur

	},

	/**
	 * Permet de d'ajuster la valeur du prochain zoom
	 * 
	 * @access private
	 * @param number valeur du zoom courant
	 * @return number valeur reelle du prochain zoom
	 */

	getCurrentZoom: function(actual_zoom)
	{
		var real_zoom = 1;

		if (actual_zoom >= this.tab_paliers[this.tab_paliers.length-1]) {
			real_zoom = this.tab_paliers[this.tab_paliers.length-1];
		}
		else if (actual_zoom <= this.tab_paliers[0]) {
			real_zoom = this.tab_paliers[0];
		}
		else
		{
			for (var i=0; i < (this.tab_paliers.length)-1; i++) {
				if ((actual_zoom >= this.tab_paliers[i]) && (actual_zoom <= this.tab_paliers[i+1])) {
					
					var actual_zoom_min = actual_zoom-this.tab_paliers[i];
					var actual_zoom_max = this.tab_paliers[i+1]-actual_zoom;
			
					if (actual_zoom_min <= actual_zoom_max) {
						real_zoom = this.tab_paliers[i];
					}
					else 
					{
						real_zoom = this.tab_paliers[i+1];
					}
				}
			}
		}
		return real_zoom;
	},

	/**
	 * Declenchement du chargement du niveau inferieur de la na representee dans le GIS (fonction declenchee par le click-droit)
	 * 
	 * @access private
	 * @return void
	 */

	naDesc: function(){
		if ((this.loading == false) && (this.lock_action == false))
		{
			this.desc_in_na		= true;
			this.move_viewbox	= false;
			this.lock_action	= true;

			//window.sendStatut('Loading layers...');
			//window.setGisLoading(true);
			
			this.setLoadingScreen();

			// 11/12/2007 - Modif. benoit :  au retour du script Ajax, on appele la fonction 'callbackDescInNa()' et non plus 'callback()'

			new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
			{
				method: 'get',
				parameters: "action=desc_in_na",
				onComplete: this.callbackDescInNa.bindAsEventListener(this)
			});
		
		}
	},

	/**
	 * Lance la recherche d'information sur les valeurs de na en fonction de la position du curseur de la souris.
	 * Cette fonction est declenchee lorsque le curseur de la souris est fixe pendant un laps de temps predetermine
	 * 
	 * @access private
	 * @return void
	 */

	searchNaInfo: function (){

		// On stoppe le timer pour eviter que la fonction se relance pendant la recherche des infos

		this.timer.stop();

		// On verifie que les infos que l'on va rechercher ne sont pas celles deja affichees
		// (c'est le cas si le curseur a les memes coordonnees que lors de la precedente recherche)

		var xm = this.info_coords.x, ym = this.info_coords.y;

		if (this.info_coords_copy != null && this.info_coords_copy.x == xm && this.info_coords_copy.y == ym)
		{
			return;
		}
		else
		{
			this.info_coords_copy = this.info_coords;
		}

		// On convertit les coordonnees du curseur en coordonnees GIS

		var topWidth	= this.controller.offsetWidth;
		var topHeight	= this.controller.offsetHeight;

		var ratioVB_x = this.viewbox[2] / topWidth;
		var ratioVB_y = this.viewbox[3] / topHeight;

		var xsvg = Number(this.viewbox[0])+(xm)*ratioVB_x;
		var ysvg = Number(this.viewbox[1])+(ym)*ratioVB_y;

		// Si les coordonnees sortent de la viewbox, on les limite aux coordonnees max. de celle-ci

		if(xsvg > Number(this.viewbox[0])+Number(this.viewbox[2])) xsvg = Number(this.viewbox[0])+Number(this.viewbox[2]);
		if(ysvg > Number(this.viewbox[1])+Number(this.viewbox[3])) ysvg = Number(this.viewbox[1])+Number(this.viewbox[3]);

		// On lance la recherche des informations

		this.showNaInformation('Loading infos...|| ');

		new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
		{
			method: 'get',
			parameters: "action=show_na_information&x="+xsvg+"&y="+ysvg,
			onComplete: this.callbackInfo.bindAsEventListener(this)
		});
	},

	/**
	 * Fonction declenchee lors du retour du script php de recherche d'informations sur les na
	 * 
	 * @access private
	 * @param object donnees retournees par le script serveur
	 * @return void
	 */	

	callbackInfo: function(data){
		this.showNaInformation(data.responseText);
	},

	/**
	 * Transmet des informations a afficher a la fenetre "Data Information"
	 * 
	 * @access private
	 * @param string information a transmettre
	 * @return void
	 */	

	showNaInformation: function(info){
		this.opener.updateDataInformation(info);
	},

	/**
	 * Permet de restaurer la vue initiale (bouton "Homepage")
	 * 
	 * @access public
	 * @return void
	 */	

	restoreInitalViewBox: function (){

		this.init_view = true;

		this.current_zoom	= this.initial_options.current_zoom;

		// 15/11/2007 - Modif. benoit : ajustement des valeurs de la viewbox initiale en fonction des dimensions de la vue courante et d'origine

		var new_width	= this.controller.offsetWidth * (this.initial_viewbox[2]/this.initial_dimensions[0]);
		var new_height	= this.controller.offsetHeight * (this.initial_viewbox[3]/this.initial_dimensions[1]);
		
		var new_x		= this.initial_viewbox[0]-((new_width-this.initial_viewbox[2])/2);
		var new_y		= this.initial_viewbox[1]-((new_height-this.initial_viewbox[3])/2);

		this.new_viewbox	= Array(new_x, new_y, new_width, new_height);		
		//this.new_viewbox	= Array(this.initial_viewbox[0], this.initial_viewbox[1], this.initial_viewbox[2], this.initial_viewbox[3]);
		this.viewbox		= this.new_viewbox;
		
		if(this.miniature != null) this.miniature.update();

		this.changeViewBox();
	},

	/**
	 * Lance le redimensionnement de la vue du GIS et de son contenu 
	 * 
	 * @access public
	 * @param number nouvelle largeur
	 * @param number nouvelle hauteur
	 * @return void
	 */	

	resize: function(width, height){

		if (!this.loading)
		{
			this.controller.style.width		= width;
			this.controller.style.height	= height;

			// On ne recharge la vue que si il existe des resultats

			if (this.status == "ok")
			{
				this.viewArea.style.backgroundImage = '';
					
				this.lock_action	= true;
				this.move_viewbox	= true;
				this.loading		= true;

				this.setLoadingScreen();

				new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
				{
					method: 'get',
					//parameters: "action=resize&side="+width,
					parameters: "action=resize&width="+width+"&height="+height,
					onComplete: this.callbackResize.bindAsEventListener(this)
				});
			}
		}
	},

	/**
	 * Fonction declenchee lors du retour du script php de redimensionnement de la viewbox
	 * 
	 * @access private
	 * @param object donnees retournees par le script serveur
	 * @return void
	 */	

	callbackResize: function(data){

		var data_o = eval('(' + data.responseText + ')');

		this.lock_action	= false;
		this.loading		= false;

		this.viewbox			= (data_o.viewbox).split(/ /);
		//this.initial_viewbox	= (data_o.initial_viewbox).split(/ /);
		
		this.viewArea.style.backgroundImage = 'url('+data_o.output+')';

		this.scale.update();
		this.reloadCtrlArea();	// on reformate la zone de selection du controleur
	},

	/**
	 * Ajout / Suppression de layers
	 * 
	 * @access public
	 * @param string action sur les layers a effectuer ("add" -> ajouter des layers, "remove" -> supprimer des layers)
	 * @param string liste de layers separes par des points-virgules (ex : "rnc;network")
	 * @return void
	 */	

	setLayers: function(action, layers_list){

		this.lock_action	= true;
		this.move_viewbox	= true;
		this.loading		= true;
		this.desc_in_na		= true;	// Pas le cas ici mais permet de recharger la fenetre des layers

		this.setLoadingScreen();

		var params = "";

		if (action == "add")
		{
			params = "action=add_layers&layers_added="+layers_list;
		}
		else if (action == "remove")
		{
			params = "action=remove_layers&layers_removed="+layers_list;
		}

		new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
		{
			method: 'get',
			parameters: params,
			onComplete: this.callback.bindAsEventListener(this)
		});
	},

	/**
	 * Modification des proprietes d'un layer
	 * 
	 * @access public
	 * @param string identifiant du layer a modifier
	 * @param boolean fond (background) des polygones du layer presents (true) ou non (false)
	 * @param boolean contour (border) des polygones du layer presents (true) ou non (false)
	 * @return void
	 */

	setLayersProperties: function(id_layer, background, border){

		this.lock_action	= true;
		this.move_viewbox	= false;
		this.loading		= true;

		this.setLoadingScreen();

		new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
		{
			method: 'get',
			parameters: "action=change_layers_pptes&layer="+id_layer+"&background="+background+"&border="+border,
			onComplete: this.callback.bindAsEventListener(this)
		});
	},

	/**
	 * Echange de l'ordre de deux layers
	 * 
	 * @access public
	 * @param string identifiant du premier layer
	 * @param string identifiant du second layer
	 * @return void
	 */

	setLayersOrder: function(id_layer_up, id_layer_down){

		this.lock_action	= true;
		this.move_viewbox	= false;
		this.loading		= true;
		this.desc_in_na		= true;	// Pas le cas ici mais permet de recharger la fenetre des layers

		this.setLoadingScreen();

		new Ajax.Request(this.niveau0+'gis/gis_scripts/gis_manager.php', 
		{
			method: 'get',
			parameters: "action=change_layers_order&layer_up="+id_layer_up+"&layer_down="+id_layer_down,
			onComplete: this.callback.bindAsEventListener(this)
		});
	},

	/**
	 * Destruction de l'instance de le classe et tous ses composants
	 * 
	 * @access public
	 * @return void
	 */

	destroy: function(){

		if (this.status != "ok" && this.noResultsArea != null)
		{
			this.noResultsArea.parentNode.removeChild(this.noResultsArea);
			this.noResultsArea = null;
		}

		// On remet a leurs valeurs d'origine les dimensions de la zone de controle

		this.controller.style.width		= this.initial_dimensions[0];
		this.controller.style.height	= this.initial_dimensions[1];

		// raz de la vue du GIS et de la zone de selection

		this.viewArea.style.backgroundImage = '';

		this.ctrlArea.style.visibility = "hidden";

		// on rend de nouveau visible le div de loading

		this.loadArea.style.visibility = "visible";

		// on supprime l'instance de la miniature et tous ses composants

		if(this.miniature != null)
		{
			this.miniature.destroy();
			this.miniature = null;
		}

		// on supprime l'instance de l'echelle et tous ses composants

		if(this.scale != null)
		{
			this.scale.destroy();
			this.scale = null;
		}

		// on detruit les ecouteurs d'evenements
		
		Event.stopObserving( window, 'load', this.extendEvent );
		Event.stopObserving( this.controller, 'mousedown', this.mouseDownBind );
		Event.stopObserving( this.controller, 'mousemove', this.mouseMoveBind );		
		Event.stopObserving( this.controller, 'mouseup', this.mouseUpBind );
	},

	/**
	 * Retourne l'action en cours
	 * 
	 * @access public
	 * @return string action en cours
	 */

	getLockAction : function(){	return this.lock_action;	},

	/**
	 * Retourne le statut du GIS ("ok" ou "no_results")
	 * 
	 * @access public
	 * @return string statut du GIS
	 */

	getStatus : function(){	return this.status;	},

	/**
	 * Permet de locker / delocker les actions sur le GIS
	 * 
	 * @access public
	 * @param boolean lockage (true) / delockage (false) du GIS
	 * @return void
	 */

	setLockAction: function(action){ this.lock_action = action },

	/**
	 * Permet de definir l'action courante dans le GIS
	 * 
	 * @access public
	 * @param string action courante
	 * @return void
	 */

	setCurrentAction: function(new_action){	this.current_action = new_action;	},

	/**
	 * Permet de definir la nouvelle viewbox
	 * 
	 * @access public
	 * @param string coordonnee x de la nouvelle viewbox
	 * @param string coordonnee y de la nouvelle viewbox
	 * @param string largeur de la nouvelle viewbox
	 * @param string hauteur de la nouvelle viewbox
	 * @return void
	 */

	setNewViewBox: function(x, y, width, height){
		this.new_viewbox = this.viewbox = Array(x, y, width, height);	
	}
}