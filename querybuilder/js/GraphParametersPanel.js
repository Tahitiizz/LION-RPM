/*
 * 28/07/2011 SPD1: Querybuilder V2 - Graph parameters panel  
 */

Ext.define('Ext.ux.querybuilder.GraphParametersPanel', {
	extend: 'Ext.panel.Panel',	
	
	requires: [
		'Ext.ux.querybuilder.FieldColorPicker',
		'Ext.ux.querybuilder.CheckColumn'		
	],
			         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbGraphParametersPanel',
	    border: false,
	    frame: true,
	    margins: '5 5 5 5',
	    height: 200,	    	    
		resizable: {handles: 's'},			
		title: Ext.ux.querybuilder.locale.graphPanel.graphParameters,
	    layout: {
    		type: 'vbox',
	    	align: 'stretch'	
	    },		                   
		autoScroll: true		    
	},	
	
	app: 		null,				// pointer to the application	
	grid: 		null,				// parameters grid
	gridStore: 	null,				// parameters grid store	
	autoReload: true,				// auto reload option	
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
          
        // Create parameters grid store
        me.gridStore = me.createParametersGridStore();
                
        // Create parameters grid
        me.grid = me.createParametersGrid();
                            
		me.items = [					   		
			me.grid
		];
  		        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		   
		// message subscribe
        me.messageHandlers = [];        
  		me.messageHandlers.push(Ext.ux.message.subscribe('/graphparameterspanel/refreshgraph', me, me.refreshGraph));                               
  		me.messageHandlers.push(Ext.ux.message.subscribe('/graphparameterspanel/setautoreload', me, me.setAutoReload));
  		me.messageHandlers.push(Ext.ux.message.subscribe('/graphparameterspanel/loadparameters', me, me.loadGraphParameters));
  		me.messageHandlers.push(Ext.ux.message.subscribe('/graphparameterspanel/clear', me, me.clearStore));
        
        // call the superclass's constructor  
        return this.callParent(arguments);
	}   
	  
	/* afterRender method */
	,afterRender: function() {				
		var me = this;
			
		// SCROLL FIX FOR EXTJS 4.0.2 - 4.0.7
		me.grid.on('scrollershow', function(scroller) {
		  if (scroller && scroller.scrollEl) {
		    scroller.clearManagedListeners(); 
		    scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
		  }
		});
					
        // call the superclass's constructor  
        return this.callParent(arguments);
	}
	
    /* Destroy
     * This method is call by the unload event (when user leaves querybuilder)
     * It destroy all component of the right panel to limit the memory leaks 
     * */     
	,destroy: function() {
		var me = this;				
		
		// delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.messageHandlers);    	
    	me.deleteObj(me.gridStore);  	    	
    	me.deleteObj(me.grid);    	
    	      		
        // call the superclass's constructor  
        return this.callParent(arguments);				
	}     

    // --------------------------------------------------------------------------------
    // Custom methods for this component
	// --------------------------------------------------------------------------------
	  
	/* Delete an object*/
	,deleteObj: function (obj) {
		try{if (obj && obj.destroy) {obj.destroy();}}catch(e){}		
		obj = null;
		delete obj;						
	}	  
			
	/* Create parameters grid store */
	,createParametersGridStore: function() {
		var me = this;
			
		// Data model used by the grid store	
		Ext.define('ParametersModel', {
    		extend: 'Ext.data.Model',
    		fields:['id', 'uid', 'visible', 'label', 'alternativeText', 'graphType', 'color', 'position', 'type', 'productId']
		});

		// Grid store (contains data displayed in the grid)
		var store = Ext.create('Ext.data.Store', {
    		storeId: 'parametersGridStore',
    		model:   'ParametersModel',
    		proxy: {
        		type: 'memory',
        		reader: {
            		type: 'json',
            		root: 'data'
        		}
    		}    		
		});			
				
		return store;			
	}
					
	/* Create parameters grid */	
	,createParametersGrid: function() {
		var me = this;
								
		var grid = Ext.create('Ext.grid.Panel', {
			flex: 1,	
			border: false,					    
			id: 'qbParametersGrid',	
    		store: me.gridStore,
    		enableColumnHide: false,
    		enableColumnMove: false,    		    	    		    		        				
			sortableColumns: false,   
			listeners: {
				// FIX FOR EXT JS 4.0.7
				scrollershow: function(scroller) {					
					if (scroller && scroller.scrollEl) {
						scroller.clearManagedListeners(); 
						scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
					}
				}				
			},
    		columns: [
    			Ext.create('Ext.grid.RowNumberer'),
    			{
        			xtype: 'checkcolumn',
        			header: me.cs.parametersGrid.visible, 				// Group column
        			dataIndex: 'visible',
        			width: 40,
        			listeners: {        				
        				checkchange: function() {
        					me.saveGraphParameters();
        					if (me.autoReload) {
        						me.refreshGraph();						// Refresh graph if auto reload is enable        						
        					}
        				}
        			}
        		},
        		{
        			header: me.cs.parametersGrid.name,
        			flex: 1,
        			dataIndex: 'label'        			
        		},
        		{
        			header: me.cs.parametersGrid.alternativeText,		// Alternative text
        			cls: 'qbEditableCell',        			        			        			
        			dataIndex: 'alternativeText', 
        			field: 'textfield'
        		},        		
        		{
        			header: me.cs.parametersGrid.type, 
					cls: 'qbEditableCell',
        			dataIndex: 'graphType',
					field: {
		                xtype: 'combobox',
		                queryMode: 'local',
		                typeAhead: true,
		                triggerAction: 'all',
		                selectOnTab: true,
		                store: me.cs.parametersGrid.typeList,
		                lazyRender: true,		                
		                listeners: {
		                	focus: function(obj) {obj.expand()},
		                	collapse: function(obj) {obj.triggerBlur()}
		                }		                
		           }        			
        		},
        		{
        			header: me.cs.parametersGrid.color,
					cls: 'qbEditableCell',
        			dataIndex: 'color',
        			field: {
        				xtype: 'FieldColorPicker',
        				listeners: {
		                	focus: function(obj) {obj.expand()},
		                	collapse: function(obj) {obj.triggerBlur()}
		                }
        			},
        			renderer: function(value) {        				
        				return '<table width="100%"><tr><td align="center"><img src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" class="qbColorPic" style="background: #'+value+'"></td></tr></table>';
        			}
        		},
        		{        			
        			header: me.cs.parametersGrid.position,
					cls: 'qbEditableCell',
        			dataIndex: 'position',								
					field: {
		                xtype: 'combobox',
		                typeAhead: true,
		                triggerAction: 'all',
		                selectOnTab: true,
		                store: me.cs.parametersGrid.positionList,
		                lazyRender: true,
		                listeners: {
		                	focus: function(obj) {obj.expand()},
		                	collapse: function(obj) {obj.triggerBlur()}
		                }
		           }		            
        		}
       		],                       
			selType: 'cellmodel',
    		plugins: [														// Grid plugins definition
        		Ext.create('Ext.grid.plugin.CellEditing', {					// Cell editing pluging (allow editing cells)
            		clicksToEdit: 1,		
					listeners: {
						edit: function() {    							// Refresh graph after a cell is edited
							me.saveGraphParameters();							
							if (me.autoReload) {
        						me.refreshGraph();						// Refresh graph if auto reload is enable
        					}    						
    					}			
					}            		
        		})
    		]
		});
		
		// Create a KeyMap (shortcut for ESC key)
		grid.on('afterrender', function() {				
			var map = new Ext.util.KeyMap(grid.getView().id, {				
			    key: Ext.EventObject.ESC,
			    fn: function() {	// Switch between query/preview tab
			    	Ext.ux.message.publish('/app/tabswitch');
			    },
			    defaultEventAction: 'preventDefault',
			    scope: me		    
			});
		});
							
		return grid;
	}	
	
	/* refresh parameters */
	,refresh: function() {		
		this.refreshGrid();
		this.refreshGraph();	  
	}

	/* refresh grid parameters */
	,refreshGrid: function() {		
		var elements = this.app.currentQuery.select.data;
		var storeData = [], oldStoreContent = [];

		// Backup old store 
		Ext.Array.forEach(Ext.Array.pluck(this.gridStore.data.items, 'data'), function(item, index) {			
			oldStoreContent[item.uid] = item;
		});
		
		var newStoreElement;
		var defaultPosition = 'left';
		
		// For RAW or KPI add a line into the store
		Ext.Array.forEach(elements, function(element) {
			if(element.type == 'RAW' || element.type == 'KPI') {
								
				// If there is a backup of this element ...get it (keep color, display ...)				
				if (oldStoreContent && oldStoreContent[element.uid]) {					
					newStoreElement = oldStoreContent[element.uid];
					newStoreElement.label = element.label;		// update label									
				} else {																		
					newStoreElement = {
						"id": element.id,						
						"uid": element.uid,
						"visible": true,
						"label": element.label,						 
						"graphType": "line", 				
						"color": ((Ext.isIE?'':'000000') + Math.floor(Math.random() * 0xFFFFFF).toString(16)).substr(-6), // Generate a random color; 
						"position": defaultPosition,
						"type": element.type,
						"productId": element.productId 
					};
				}								
				storeData.push(newStoreElement);
				defaultPosition = defaultPosition=='left'?'right':'left';	// Change default position for each element
			} 
		}, this);		
		// Clear store
		this.clearStore();
				
		// Add data into the store
		this.gridStore.add(storeData);
		
		// Save parameters in the current query
		this.saveGraphParameters(true);
	}
	
	/** Clear parameters grid store */	
	,clearStore: function() {
		this.gridStore.removeAll();
		this.grid.getView().refresh(true);
	}
		
	/** Refresh graph */
	,refreshGraph: function() {		
		// Get the graph parameters
		var graphParameters = this.getGraphParameters();				
		
		// Refresh graph
		if (graphParameters) {  		
			Ext.ux.message.publish('/graphpanel/refreshGraph', [graphParameters]);
		}
	}
	
	/** Save the graph parameters in the current query object
	 * @param silence: if true, don't set current query hasChange propertie (don't ask to save)
	 */
	,saveGraphParameters: function(silence) {
				
		// Create the graph parameter object		
		var param = { 
			name: this.app.currentQuery.graphParameters.name,							// Graph name
			leftAxisLabel: this.app.currentQuery.graphParameters.leftAxisLabel,
			rightAxisLabel: this.app.currentQuery.graphParameters.rightAxisLabel,
			gridParameters: Ext.Array.pluck(this.gridStore.data.items, 'data')			// Grid parameters
		}
				
		// Save the param in the current query
		this.app.currentQuery.graphParameters = param;
				
		// Set hasChange to true -> ask for saving when leaving
		if (!silence) {		
			this.app.currentQuery.system.hasChanged = true;
		}
	}
	
	/* Load graph parameters from the curent query object */
	,loadGraphParameters: function(query) {								
		var param = query.graphParameters;
				
		if (!param) {return;}		// if no param ..exit		
		
		// Load graph name
		this.app.currentQuery.graphParameters.name = param.name;
													
		// Set axis labels
		
		this.app.currentQuery.graphParameters.leftAxisLabel = param.leftAxisLabel;
		this.app.currentQuery.graphParameters.rightAxisLabel = param.rightAxisLabel;
								
		// Set hasChange to false (flag to ask saving before leaving query)
		if (this.app.currentQuery.general.id != '') {
			this.app.currentQuery.system.hasChanged = false;
		}
	}
		
	/** Return the graph parameters as a JSON object */
	,getGraphParameters: function() {
		// Get the grid store content		
		storeContent = Ext.Array.pluck(this.gridStore.data.items, 'data');	
 
		var params = {
			name: '  '+(this.app.currentQuery.graphParameters.name || this.app.currentQuery.general.name || ''),	// User graph name or if not set, the query name
			leftAxisLabel: this.app.currentQuery.graphParameters.leftAxisLabel || '',
			rightAxisLabel: this.app.currentQuery.graphParameters.rightAxisLabel || ''			
		};
			
		params.graphData = [{"type":"no"}, {"type":"no"}];					// Do not display the two first data (NA and TA)
		var row = false;
		
		Ext.Array.forEach(storeContent, function(item) {					// For each element to display
			row = {
				type: (!item.visible)?'no':item.graphType,					// Type: line, barchart ...
				color: '#'+item.color,										// Color
				position: item.position,									// Position: left, right
				legend: item.alternativeText?item.alternativeText:item.label// Element name (legend)
			};
			params.graphData.push(row);
		}, this);
		
		if (!row) {	
			return false;
		} else {
			return {graphParameters: Ext.encode(params)};
		}
	}
	
	/** Set auto reload option
	 * Parameter:
	 *  isEnable: boolean (true to activate auto reload)
	 */
	,setAutoReload: function(isEnable) {
		this.autoReload = isEnable;
	}
	
	/** Reset parameters (clear form)*/
	,reset: function() {		
		this.gridStore.removeAll();
	}
});