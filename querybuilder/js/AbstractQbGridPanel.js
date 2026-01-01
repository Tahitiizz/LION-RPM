/*
 * 28/07/2011 SPD1: Querybuilder V2 - Abstract class to manage grids  
 */

Ext.define('Ext.ux.querybuilder.AbstractQbGridPanel', {
	extend: 'Ext.panel.Panel',
	    
	requires: [
		'Ext.grid.Panel',
		'Ext.grid.plugin.CellEditing',		
		'Ext.ux.querybuilder.CheckColumn',
		'Ext.grid.column.Action',
		'Ext.grid.RowNumberer',		
		'Ext.selection.CellModel',
		'Ext.form.field.ComboBox',
		'Ext.form.field.Text',
		'Ext.ux.querybuilder.GridView',
		'Ext.grid.plugin.DragDrop'					
	],
	     
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {				
	    border: false,
	    layout: 'fit',		    	    	        
		autoScroll: true		
	},	
		
	app: null,					// pointer to the application
	store: null,				// grid store
	dataGrid: null,				// data grid
	messageHandlers:null,		// message handlers (publish/subscribe)
	modeMessageHandlers: null, 	// message handlers specific for current mode (wizard or sql)

	// Drag drop management to reorder element within the list
	viewConf: {
		plugins: {
	        ptype: 'gridviewdragdrop',
	        dragText: 'Drag and drop to reorganize'
	    },
	    listeners: {
    		beforedrop: function (node, data, dropRec, dropPosition) {
    			var view = data.view;
    			var dragRec = view.getRecord(data.item);    			        				
    			var store = dropRec.store;    
    			
				if (dragRec && dragRec!=dropRec) {					    				
					var recordData = dragRec.data;
					store.remove(dragRec);					        			
					var pos = store.indexOf(dropRec);										
					if (dropPosition=='after') {
						pos++;
					}	        									
					store.insert(pos, recordData);					
				}        				        				        					        			
    		}			
    	}
	},
			          
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Get the app main object		
		me.app = config.app;
				
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		
		// Apply the custom config
		Ext.apply(config, me.config);
		
		// Init store grid
		me.initStore(config.app.currentQuery);
		
		// Create data grid
		me.dataGrid = me.createGrid();
		
		me.items = [
//			me.dataGrid
		];		 				 

        // call the superclass's constructor  
        return this.callParent(arguments);		
    }     	      
    	  	
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		
		// message subscribe
        if (!me.messageHandlers) {
        	 me.messageHandlers = [];
        }        						               
		me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/changemode', me, me.changeMode));		               
		me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/grids/remove', me, me.removeFromGrid));		
		
		// init wizard mode subscribe (default mode)
		me.modeMessageHandlers = [];
						
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
    	
    	// Delete mode message handlers (publish/subscribe)		
		Ext.Array.each(me.modeMessageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.modeMessageHandlers);
    	    	
		// Delete store	
		me.store.destroyStore();
		me.deleteObj(me.store);
			
		// Delete dropZone
		me.deleteObj(me.dataGrid.dropZone);
						
		// Delete data grid
		delete me.dataGrid;						
			
        // call the superclass's constructor  
        return this.callParent(arguments);				
	}     

    // --------------------------------------------------------------------------------
    // Abstract methods
	// --------------------------------------------------------------------------------
	 
	/* Abstract: Init the grid store */
	,initStore: function(currentQuery) {
    	// Init the grid store here !
	}		
		
	/* Abstract: Create data grid */
	,createGrid: function() {
		// Create the grid here !
	}
	
	/* Abstract: Init wizard mode */
	,initWizardMode: function() {
		// Init the wizard mode here !
	}
	 
	/* Delete an object*/
	,deleteObj: function (obj) {
		if (obj && obj.destroy) {obj.destroy();}		
		obj = null;
		delete obj;						
	}
	
	// --------------------------------------------------------------------------------
    // Custom methods for this component
	// --------------------------------------------------------------------------------
		
	/* Add element into the grid 
	/* Parameter: data JSON object (containing data.element.id, data.element.type, data.element.productId)*/			 	
	,addElement: function(data) {		
		var me = this;				
		
		if (data.element.type == 'RAW' || data.element.type == 'KPI') {
			me.addRawKpi(data);	// add a raw or kpi
		} else {
			me.addAggregation(data);			// add network or time aggregation
		}			
	}
	
	/* Add an aggregation into the grid */
	/* Parameter: data JSON object (containing data.element.id, data.element.type, data.element.productId)*/
	,addAggregation: function(data) {
				
		// Set element default values
		this.setDefaultElementValues(data.element);
				   				
		// add the element to the grid		
		this.store.add(data.element);
	}
	
	/* Add a raw or kpi into the grid */
	,addRawKpi: function(data) {
		var me = this;
		
		// Display loader
		me.showMask();
		
		// Create JSON request object
		var requestParam = {
			id: 		data.element.id,			// Element id (ex: raws.0004.08.27.01.00001)
			type: 		data.element.type,			// Element type (RAW/KPI ...)
			product:	data.element.productId		// Product (sdp_id)
		};
								  
		// Send request to get the element from the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=getElementById',	// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	// get the response as a JSON object
		        var response = Ext.decode(resp.responseText);
		   
		   		// Add the product name to the response
		   		response.productName = data.element.productName;
		   		
		   		// Set element default values
		   		me.setDefaultElementValues(response);
		   				   		
		   		// add the element to the grid
		   		me.store.add(response);
		        
		        // If there is an error
		        if (response.error) {
		        	// Display the error in the console browser
		        	Ext.ux.message.publish('/app/error', [response.error]);
		        }
		        
		        // Hide loader
				me.hideMask();					       
		    },
		    failure: function(response, opts) {
		    	// Hide Loader
		    	me.hideMask();
		    	
			   	// On error
        		Ext.ux.message.publish('/app/error', [response]);
    		}
		});		
	}
		
	/* Delete All button click */
	,onDeleteAllClick: function() {
		this.store.removeAll();
	}
	
	/* Delete red cross click */
	,onDeleteClick: function(grid, rowIndex, colIndex) {
		
		// find record to delete
		var record = this.store.getAt(rowIndex);
				
		var type = record.get('type');
		
		// If this is a system row type ('Max result filter' is one of them) ...do nothing, can't delete this row
		if (type == 'sys') {
			return;
		}
		
		// if it is an aggregation element, deselect it
		if (type == 'na' || type == 'na_axe3' || type == 'ta') {			
			// Refresh aggregations panel state (aggregation items pressed status)
			Ext.ux.message.publish('/aggpanel/refreshstate');				
		}
		
		this.store.remove(record);				
	}
	
	/* Change mode (wizard/sql)
	 * Parameter:
	 *  - newMode: string - the new mode ('ShowSql', 'HideSQL', 'EditSql'...)
	 */ 	
	,changeMode: function(newMode) {
		var me = this;
				
		// Delete mode message handlers (publish/subscribe)		
		Ext.Array.each(me.modeMessageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});
				
		// activate the possibility to add element from the left panel for the wizard mode only		
		if (newMode === 'HideSql' || newMode === 'wizard') {										
			me.initWizardMode();			
		}		
	}

	/* Remove an element from the grid by its id */
	,removeFromGrid: function(id) {
		// Find element in the grid store				
		var element = this.store.getById(id);
		
		// Remove the element
		if (element) { 
			this.store.remove(element);
		}
	}	
	
	/** Show a mask while loading query*/
	,showMask: function() {
		// Create the mask
		if (!this.loaderMask) {					
			this.loaderMask = new Ext.LoadMask(this.dataGrid.getEl(), {msg:this.cs.app.pleaseWait});
		}
		
		// Display the mask
		this.loaderMask.show();		
	}
		
	/** Hide mask */
	,hideMask: function() {		
		// Display the mask
		this.loaderMask.hide();		
	}
		
});