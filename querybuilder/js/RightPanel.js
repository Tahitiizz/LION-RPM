/*
 * 28/07/2011 SPD1: Querybuilder V2 - Right panel   
 */
 
 Ext.define('Ext.ux.querybuilder.RightPanel', {
	extend: 'Ext.panel.Panel',	

	requires: [
		'Ext.tip.QuickTipManager',
		'Ext.ux.querybuilder.QueriesPanel',
		'Ext.ux.querybuilder.DownloadPanel'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbRightPanel',
		title: Ext.ux.querybuilder.locale.rightPanel.title,
		iconCls: 'x-tree-icon-leaf',			
		width: 200,
		region: 'east',		
		animCollapse: false,      		
	   	tools: [
	   		{
	   			// Batch CVS Export
				type:'gear',		    	
			    hidden: false,
			    qtip: { // tooltip  		
  	  				title: Ext.ux.querybuilder.locale.rightPanel.batchCsvExportTitle,
    				text:  Ext.ux.querybuilder.locale.rightPanel.batchCsvExportDesc
				},
				// Click handler
				handler: function(event, toolEl, panel) {
					Ext.ux.message.publish('/queriespanel/batchcsvexport');	// Batch CSV Export (for selected queries)
				}		    
			},
			{
				// Queries import
				type:'import',
			    qtip: { // tooltip  		
  	  				title: Ext.ux.querybuilder.locale.rightPanel.importQueriesTitle,
    				text:  Ext.ux.querybuilder.locale.rightPanel.importQueriesDesc
				},
				// Click handler
				handler: function(event, toolEl, panel) {
					Ext.ux.message.publish('/queriespanel/import');			// Open queries import window
				}	
			},
			{
				// Queries export
				type:'export',
			    qtip: { // tooltip  		
  	  				title: Ext.ux.querybuilder.locale.rightPanel.exportQueriesTitle,
    				text:  Ext.ux.querybuilder.locale.rightPanel.exportQueriesDesc
				},
				// Click handler
				handler: function(event, toolEl, panel) {
					Ext.ux.message.publish('/queriespanel/export');			// Export selected queries
				}
			}
		],			   
	    split: true,
	    collapsible: true,          
	    border: true,                     	    	     
	    layout: {                        
        	type: 'vbox',
        	align: 'stretch'
    	},	           
		margins: '0 5 0 0'						
	},	
	
	app: null,					// pointer to the application
	messageHandlers: null,		// message handler (publish/subscribe)	
	queriesPanel: null,			// queries panel
	downloadPanel: null, 		// download panel
	
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
		me.queriesPanel = Ext.create('Ext.ux.querybuilder.QueriesPanel', {app: config.app});
		
		// Create download panel		  		                                                             	
		me.downloadPanel = Ext.create('Ext.ux.querybuilder.DownloadPanel', {app: config.app});
		
        // Add items
		me.items = [
			me.queriesPanel,
			me.downloadPanel
		];								
        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		   
		// message subscribe
        me.messageHandlers = [];        
    
  		//me.messageHandlers.push(Ext.ux.message.subscribe('/rightpanel/xxx', me, me.xxx));                               
        
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
     * It destroy all component of the right panel to limit the memory leaks 
     * */     
	,destroy: function() {
		var me = this;				
		
		// delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.messageHandlers);
    	
    	// delete queries panel
    	me.deleteObj(me.queriesPanel);
    	
    	// delete download panel
    	me.deleteObj(me.downloadPanel);    	
    			
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
	
});