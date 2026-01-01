/*
 * 28/07/2011 SPD1: Querybuilder V2 - Preview tab  
 */

Ext.define('Ext.ux.querybuilder.PreviewTab', {
	extend: 'Ext.panel.Panel',
	    
	requires: [
		'Ext.ux.querybuilder.TablePanel',
		'Ext.ux.querybuilder.GraphPanel'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbPreviewTab',
	    border: false,                     	    	     
	    layout: 'card',  
		title: Ext.ux.querybuilder.locale.previewTab.title,
		iconCls: 'icoPreviewError',                    
		autoScroll: true,
		listeners: {
       		activate: function(me) {       				
       			if (!me.app.isDestroy) {       			
       				Ext.ux.message.publish('/querytab/changemode', ['Preview']);	// Change mode (disable left panel)					
       				me.refreshPanels();												// Load panels content when 'activate' event is fired
       			}
       		},
       		deactivate: function(me) {
       			// When leaving preview panel, set isFresh properties to false, 
       			// this will force table and graph being refresh next time when the preview table will be displayed       			
       			this.tablePanel.isFresh = false;
       			this.graphPanel.isFresh = false;       			
       		}
		}					                        
	},	
	
	app: null,					// pointer to the application
	tablePanel: null,			// table panel
	graphPanel: null, 			// graph panel
	messageHandlers: null,		// message handler (publish/subscribe)
		      
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
				 
		// Create table panel
		me.tablePanel = me.createTablePanel({app: config.app});
		  		  
		// Create graph panel
		me.graphPanel = me.createGraphPanel({app: config.app});
		
		// Toolbar
		var toolbar = new Ext.Toolbar({
			//id: 'defaultButtonBar',		
			cls: 'qbToolbar',
			height: 35,
			border: false,	
			items: [		
				'->',
			  {
			  	xtype: 'button',													// show graph button		  	 
			  	text: Ext.ux.querybuilder.locale.tablePanel.back,
			  	iconCls: 'icoArrowUndo',		  	 
			  	handler: function() {
		  			Ext.getCmp('mainTabPanel').getLayout().setActiveItem('qbQueryTab');
			  	}
			  },
			  '-',		
			  {
			  	xtype: 'button',													// show graph button
			  	id: 'qbTableButton', 
			  	text: Ext.ux.querybuilder.locale.tablePanel.title,
			  	iconCls: 'icoViewList',
			  	hidden: true, 
			  	handler: function() {
		  			Ext.ux.message.publish('/previewtab/showtable');
			  	}
			  },		  
			  {
			  	xtype: 'button', 													// show table button
			  	id: 'qbGraphButton',
			  	text: Ext.ux.querybuilder.locale.graphPanel.title,
			  	iconCls: 'icoGraph',  
			  	handler: function() {
		  			Ext.ux.message.publish('/previewtab/showgraph');
			  	}		  	
			  }		  		
			]
		});
		
		me.dockedItems = [
			Ext.create('Ext.panel.Panel',{	    		// Create a panel with the default toolbar
				dock: 'bottom',		    	    
		    	border: false, 		 					
		    	height: 40,
				layout: 'fit',
		      	items: [
					toolbar
		      	]		  	
			})
		];
						
  		me.items = [			
			me.tablePanel,
			me.graphPanel
  		];
  		   	    		
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		         
		// message subscribe
        me.messageHandlers = [];
        
        // subscribe messages
        me.messageHandlers.push(Ext.ux.message.subscribe('/previewtab/showtable', me, me.showTablePanel));
        me.messageHandlers.push(Ext.ux.message.subscribe('/previewtab/showgraph', me, me.showGraphPanel));
        me.messageHandlers.push(Ext.ux.message.subscribe('/previewtab/reset', me, me.resetTab));
        me.messageHandlers.push(Ext.ux.message.subscribe('/previewtab/cancelrequest', me, me.cancelRequest));
                		               
        // call the superclass's constructor  
        return this.callParent(arguments);
	}   
	  
	/* afterRender method */
	,afterRender: function() {				
		var me = this;
		
		// Create a KeyMap (shortcut for ESC key)
		var map = new Ext.util.KeyMap(document, {			
		    key: Ext.EventObject.ESC,
		    fn: function() {		 		// Switch between query/preview tab
		    	Ext.ux.message.publish('/app/tabswitch');
		    },
		    defaultEventAction: 'preventDefault',
		    scope: me		    
		});
		
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
    									
		// Delete table panel
		me.deleteObj(me.tablePanel);				
		
		// Delete graph panel
		me.deleteObj(me.graphPanel);		
				
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
	  
	/* Create table panel */
	,createTablePanel: function(params) {
		return Ext.create('Ext.ux.querybuilder.TablePanel', params);
	}
	 
	/* Create graph panel */
	,createGraphPanel: function(params) {
		return Ext.create('Ext.ux.querybuilder.GraphPanel', params);
	}	 
	
	/* Display table tab */
	,showTablePanel: function() {		
		Ext.getCmp('qbGraphButton').setVisible(true);
		Ext.getCmp('qbTableButton').setVisible(false);
//		try {
			this.getLayout().setActiveItem(this.tablePanel);
//		} catch(e) {}
	}

	/* Display graph tab */
	,showGraphPanel: function() {
		Ext.getCmp('qbGraphButton').setVisible(false);
		Ext.getCmp('qbTableButton').setVisible(true);
		this.getLayout().setActiveItem(this.graphPanel);		
	}

	/* reset tab */
	,resetTab: function() {		
		this.showTablePanel();
		//this.graphPanel.reset();
	}		

	/* Refresh the active panels content */
	,refreshPanels: function() {									
		// Get active panel (graph or table)		
		var activePanel = this.getLayout().getActiveItem();
		
		// If the panel is loaded refresh it !
		if (activePanel && activePanel.isFresh == false) { 			
			activePanel.isFresh = true;
			activePanel.refresh();
		}						
	}
	
	/* Cancel preview request */
	,cancelRequest: function() {						
		// Abort loading preview table/graph
		this.tablePanel.abort();
		this.graphPanel.abort();
	}	
});