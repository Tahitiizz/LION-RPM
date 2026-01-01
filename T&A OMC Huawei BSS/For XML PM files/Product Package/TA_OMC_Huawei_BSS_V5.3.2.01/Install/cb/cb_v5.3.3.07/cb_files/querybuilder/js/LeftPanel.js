/*
 * 28/07/2011 SPD1: Querybuilder V2 - Left panel component  
 */

Ext.define('Ext.ux.querybuilder.LeftPanel', {
	extend: 'Ext.panel.Panel',	

	requires: [
		'Ext.layout.container.Accordion',
		'Ext.tip.QuickTipManager',
		'Ext.ux.querybuilder.FilterPanel',
		'Ext.ux.querybuilder.InfoWindow'
	],

	currentMode: null,		// Current mode (showSql, editSql ...)
			         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbLeftPanel',		
		title: Ext.ux.querybuilder.locale.leftPanel.title,			
		width: 200,		
		region: 'west',
		animCollapse: false,      		
	   	tools: [{
			type:'advsearch',		    	
		    hidden: false,
		    qtip: {  									// Advanced search options tip  		
    			title: Ext.ux.querybuilder.locale.leftPanel.qtAdvOptionsTitle,
    			text:  Ext.ux.querybuilder.locale.leftPanel.qtAdvOptions
			},
			handler: function(event, toolEl, panel) { 	// Show/hide advanced search option
				Ext.ux.message.publish('/filterpanel/toggleoptions');
			}		    
		}],			   
	    split: true,
	    collapsible: true,          
	    border: true,                     	    	     
	    layout: {                        
        	type: 'vbox',
        	align: 'stretch'
    	},	           
		margins: '0 0 0 5'				
	},	
	
	app: null,					// pointer to the application
	filterPanel: null, 			// filter panel
	dataPanel: null,			// data panel
	rawList: null,				// rawList
	kpiList: null,				// kpiList
	dragData: null,				// data for the selected element (left click, right click or DnD)
	contextMenu: null,			// context menu
	messageHandlers: null,		// message handler (publish/subscribe)	
	searchDisabled: true, 		// true to disable the search (used when user click on reset button)
	infoWindow: null,			// RAW/KPI info window
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		
		// Apply the custom config
		Ext.apply(config, me.config);
					 
		// Create filter panel
		me.filterPanel = Ext.create('Ext.ux.querybuilder.FilterPanel', {app: config.app});
		  		  
		// Create "list of elements" panel
		me.dataPanel = me.createDataPanel();                                                            	
		
        // Add items
		me.items = [
			me.filterPanel,
			me.dataPanel			
		];								
        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		   
		// message subscribe
        me.messageHandlers = [];
        
        // refresh raw, kpi list (when user is doing a search)
        me.messageHandlers.push(Ext.ux.message.subscribe('/leftpanel/search', me, me.refreshElements));
        
        // enable/disable search
        me.messageHandlers.push(Ext.ux.message.subscribe('/leftpanel/enablesearch', me, me.enableSearch));
        me.messageHandlers.push(Ext.ux.message.subscribe('/leftpanel/disablesearch', me, me.disableSearch));        
                
        // open context menu
        me.messageHandlers.push(Ext.ux.message.subscribe('/leftpanel/opencontextmenu', me, me.openContextMenu));
        
        // change mode (show/hide/edit sql) message
        me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/changemode', me, me.onChangeMode));
             
		// info window
        me.messageHandlers.push(Ext.ux.message.subscribe('/infowindow/display', me, me.displayInfos));             
               
        // call the superclass's constructor  
        return this.callParent(arguments);
	}   
	  
	/* afterRender method */
	,afterRender: function() {				
		var me = this;
		
        // call the superclass's constructor  
        return this.callParent(arguments);
	}
	
    /* Destroy
     * This method is call by the unload event (when user leaves querybuilder)
     * It destroy all component of the left panel to limit the memory leaks 
     * */     
	,destroy: function() {
		var me = this;				
		
		// Delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.messageHandlers);
    	
		// Delete context menu
		me.deleteObj(me.contextMenu);
									
		// Delete filter panel
		me.deleteObj(me.filterPanel);				
		
		// Delete drag zones
		me.deleteObj(me.rawList.dragZone);
		me.deleteObj(me.kpiList.dragZone);
		
		// Infos window
		me.deleteObj(me.infoWindow);
		
		// Delete lists
		me.deleteObj(me.rawList);				
		me.deleteObj(me.kpiList);
				
		// Delete data panel
		me.deleteObj(me.dataPanel);
			
        // call the superclass's constructor  
        return this.callParent(arguments);				
	}     

    // --------------------------------------------------------------------------------
    // Custom methods for this component
	// --------------------------------------------------------------------------------
	  
	/* Delete an object*/
	,deleteObj: function (obj) {		
		if (obj && obj.destroy) {obj.destroy();}		
		obj = null;
		delete obj;						
	}	  
	
	/* Refresh RAW and KPI list according the search options */
	,refreshElements: function(searchOptions) {	
		var me = this;				
		
		// If search is disabled: do nothing (search is disabled during the search form is reseted this avoid several requests for nothing)
		if (me.searchDisabled) {
			return true;
		}
		
		var loadParameters = {			
    		params: {filterOptions: Ext.encode(searchOptions)}
		};

		// load raw list
		//loadParameters.url = "raw_listhtml.php";
		
		if (me.rawList.body) {
			var loader = me.rawList.getLoader();			
			loader.load(loadParameters);
		}
		
		// load kpi list		
		if (me.kpiList.body) {
			var loader = me.kpiList.getLoader();
			loader.load(loadParameters);
		}
		
		var filtered = searchOptions.text || me.filterPanel.isDirty?' <b>'+me.cs.leftPanel.filtered+'</b>':'';
				
		// update lists title
		me.rawList.setTitle(me.cs.leftPanel.rawList + filtered);							 																							
		me.kpiList.setTitle(me.cs.leftPanel.kpiList + filtered);																								

	}
	
	/* Create "list of elements" panel */
	,createDataPanel: function() {

		var me = this;
		
		// Create RAW list panel		
		me.rawList = Ext.create('Ext.panel.Panel', {
			iconCls: 'icoBrick',
			id: 'rawList',
			dataType: 'RAW',					
			title: me.cs.leftPanel.rawList,					
			autoLoad: {url:'../php/querybuilder.php?method=getRawHtmlList'},
			loader: {
				loadMask: true,
        		url: '../php/querybuilder.php?method=getRawHtmlList'
        	},						
			autoScroll: false				
		});
					
		// Create KPI list panel
		me.kpiList = Ext.create('Ext.panel.Panel', {
			iconCls: 'icoBrick',
			id: 'kpiList',
			dataType: 'KPI',			
			title: me.cs.leftPanel.kpiList,			
			autoLoad: {url:'../php/querybuilder.php?method=getKpiHtmlList',scripts:false},
			loader: {
				loadMask: true,
        		url: '../php/querybuilder.php?method=getKpiHtmlList'
        	},												
			autoScroll: false				
		});

		// Init raw panel as a drag source zone
		me.rawList.on('render', me.initDragZone(me.rawList));		
		me.kpiList.on('render', me.initDragZone(me.kpiList));
		
		// Context menu
		me.contextMenu = new Ext.menu.Menu({		  
		  items: [
			  {		// Add to 'selected elements' item
			  	id: 'qbMenuAddToSelected',
			    text: me.cs.leftPanel.addToSelected,
			    iconCls: 'icoGrid',			    
			    handler: function() {
			    	Ext.ux.message.publish('/querytab/datagrid/add', [me.dragData]);
			    },
			    scope: this		    		   
			  },
			  {		// Add to 'filters' item
			  	id: 'qbMenuAddToFilter',
			    text: me.cs.leftPanel.addToFilters,
			    iconCls: 'icoFilter',
			    handler: function() {
			    	Ext.ux.message.publish('/querytab/filtergrid/add', [me.dragData]);
			    },
			    scope: this		    		   
			  },
			  {		// Add item
			  	id: 'qbMenuAdd',
			    text: me.cs.leftPanel.addTo,
			    iconCls: 'icoGrid',			    
			    handler: function() {
			    	Ext.ux.message.publish('/querytab/datagrid/add', [me.dragData]);
			    },
			    scope: this		    		   
			  },
			  {		// Add formula
			  	id: 'qbMenuAddFormula',
			    text: me.cs.leftPanel.addFormula,
			    iconCls: 'icoBrick',			    
			    handler: function() {			    	
			    	Ext.ux.message.publish('/sqltab/addformula', [me.dragData]);
			    },
			    scope: this		    		   
			  },			  
			  { 	// Display infos window
			  	id: 'qbMenuGetInfo',			  	
			  	text: me.cs.leftPanel.infos,
			  	iconCls: 'icoInfo',
			  	handler: Ext.bind(function() {this.displayInfos()}, this)			  	
			  }			  
		  ]		  
		});
						
		return Ext.create('Ext.panel.Panel', {
			flex: 1,
			height: 100,			
			id: 'qbLeftElementsContainer',			   
		    layout:'accordion',				        		    		    		    		 
	 		layoutConfig: {         	
        		titleCollapse: true,         	 	
        		activeOnTop: true,
        		animate: false
    		},		    				  
		    items: [				    	    
		    	me.rawList,		        
		    	me.kpiList		    	
		    ]
		});		
	}
	
	/* Init drag zone, this zone manage left and right click too */
	,initDragZone: function(list) {
		var me = this;
					
		return function() {
			list.dragZone = Ext.create('Ext.dd.DragZone', list.id, {
				ddGroup: 'elementDDGroup',
				click: false,
								
				onStartDrag: function(e) {
					// Drag starting this is not a regular click (this is a drag&drop operation)
					this.click = false;
				},
								
				onMouseUp: function() {
					// If button is release defore starting drag, this is a simple click					
					if (this.click) {
						if (this.ctrlKey) {
							// Open/Update info window
							me.dragData = this.dragData;		// Save clicked element
							me.displayInfos();					// Update or open the info window							
						} else {
							// Add the clicked element into the data grid						
							Ext.ux.message.publish('/querytab/datagrid/add', [this.dragData]);
						}
					}	
				},
				
				// Launched when user click in the list
				getDragData: function(e) {					
					this.click = true;
					this.ctrlKey = e.ctrlKey;
					
					// Find the element under the mouse pointer
					var sourceEl = e.getTarget('tr', 10), d;															
					
		            if (sourceEl && sourceEl.id) {   	// if an element with a valid id has been found ...		                
		                d = document.createElement("div");
		                d.innerHTML = sourceEl.innerHTML;
		                d.id = Ext.id();		                		                   
		                		                	                		               
		                return {
		                    sourceEl: sourceEl,
		                    repairXY: Ext.fly(sourceEl).getXY(),
		                    ddel: d,
		                    element: {
		                    	id: sourceEl.id,
		                    	type: list.dataType,
		                    	productId: sourceEl.getAttribute('data-product'),
		                    	productName: sourceEl.childNodes[1].innerHTML		// Get the product name
		                    }		                    
		                };		                		            
		            }
					
				},
				getRepairXY: function() {				// Provide coordinates for the proxy to slide back to on failed drag
	        		return this.dragData.repairXY;
	    		},
	    		/* Overwrite init function of Ext.dd.DragDrop to insert the right click handler */
				init: function(id, sGroup, config) {
       				this.initTarget(id, sGroup, config);
       				
       				// Add custom right click handler				        			        			        		
        			Ext.EventManager.on(this.id, "mousedown", this.handleMouseDown, this);
        			Ext.EventManager.on(this.id, "mousedown", this.handleRightClick, this);                			
				},
				/* Custom right click handler */
				handleRightClick: function (e, oDD) {					
					// If this is a right click
					if (e.button == 2) {
						
						// get mouse pointer position							
						var coord = e.getXY();
						
						// add defer to fix IE						
						Ext.Function.defer(function(coord) {
							// Open the context menu						
							Ext.ux.message.publish('/leftpanel/opencontextmenu', [this.dragData, coord]);
						}, 10, this, [coord]);
																																		
					}					
				}
			});
		}
		
	}
	
	/* enable search */
	,enableSearch: function() {
		this.searchDisabled = false;
	}
	
	/* disable search */
	,disableSearch: function() {
		this.searchDisabled = true;
	}
	
	/* Display infos */
	,displayInfos: function(data) {
		var me = this;
					
		// Create the window (if not already created)
		if (!me.infoWindow) {
			me.infoWindow = Ext.create('Ext.ux.querybuilder.InfoWindow', {			
				height: 310,
	    		width: 350
			});				
		} 
				
		// Display window
		me.infoWindow.displayWindow(data?data:me.dragData);
	}
	
	/* Open the context menu
	 * Parameters:
	 *    - dragData: data about the clicked element
	 * 	  - coord: position of the mouse pointer
	 */
	,openContextMenu: function(dragData, coord) {
		var me = this;
		
		// Save data of the clicked element
		me.dragData = dragData;
			
		// Show add formula item for KPI in Edit SQL mode
		if (dragData.element.type == 'KPI' && me.currentMode == 'EditSql') {
			this.contextMenu.getComponent('qbMenuAddFormula').show();
		} else {
			this.contextMenu.getComponent('qbMenuAddFormula').hide();
		}
		
		// Open context menu							
		me.contextMenu.showAt(coord);							
	}	
	
	/* Change mode (wizard/sql/preview ...)
	 * Parameter:
	 *  - newMode: string - the new mode ('ShowSql', 'HideSQL', 'EditSql', 'wizard', 'sql'...)
	 */ 	
	,onChangeMode: function(newMode) {
		var me = this;
			
		// reset items
		this.contextMenu.getComponent('qbMenuAddToSelected').hide();				// hide 'add to selected' item
		this.contextMenu.getComponent('qbMenuAddToFilter').hide();					// hide 'add to filter' item
		this.contextMenu.getComponent('qbMenuAdd').hide();							// hide 'add' item
		this.contextMenu.getComponent('qbMenuAddFormula').hide();					// hide 'add to formula' item
									
		// enable 'add to selected' and 'add to filter item' for wizard mode		
		if (newMode === 'HideSql' || newMode === 'wizard') {										
			this.contextMenu.getComponent('qbMenuAddToSelected').show();			// show 'add to selected' item
			this.contextMenu.getComponent('qbMenuAddToFilter').show();				// show 'add to filter' item			
		} else if (newMode == 'EditSql') {
			this.contextMenu.getComponent('qbMenuAdd').show();
			this.contextMenu.getComponent('qbMenuAddFormula').show();
		}
		
		me.currentMode = newMode;	
	}	
});