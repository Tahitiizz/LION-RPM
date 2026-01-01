/*
 * 28/07/2011 SPD1: Querybuilder V2 - Table panel (used in preview tab)
 */
 
Ext.define('Ext.ux.querybuilder.TablePanel', {
	extend: 'Ext.panel.Panel',	
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbTablePanel',
	    border: false,                     	    	     
	    layout: 'fit',                      
		autoScroll: true,
		isFresh: false,
		listeners: {
			"activate": function() {		// Refresh this panel when it is activate (execute query and display result)								
				if (!this.app.isDestroy && !this.isFresh) {				
					this.isFresh = true;
					this.refresh();					
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
		
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		
		// Apply the custom config
		Ext.apply(config, me.config);					 								
        
        // Create loader for this panel
        me.loader = new Ext.ComponentLoader({
			loadMask: {msg: me.cs.app.pleaseWait + '<br><button class="qbButton" onClick=\"Ext.ux.message.publish(\'/previewtab/cancelrequest\')\">Cancel</button>'},
       		url: '../php/querybuilder.php?method=getGridPreview'			// Get grid result
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
	
	/* Refresh panel */
	,refresh: function() {			
		
		var loader = this.getLoader();
		loader.qbReqId = new Date().valueOf();
		
		// Post parameters	
		var postParam = {
			params: {
				query: Ext.encode(this.app.currentQuery),	// Send the query in POST parameter
				qbReqId: loader.qbReqId				// Id used to cancel SQL request if cancel button is clicked
			}
		}
		
		// If this is a SQL query, add the server productId to the param
		if (this.app.currentQuery.general.type == 'sql') {
			postParam.params.server = Ext.getCmp('qbExecuteServerCombo').getValue();
		}
				
		this.getLoader().load(postParam);	
	}  
	
	/* Abort loading */
	,abort: function() {
		
		var loader = this.getLoader();
		
		if (loader.active !== undefined) {				
			// Abort request
			loader.abort();
			
			// Cancel SQL request
			Ext.ux.message.publish('/app/cancelsqlrequest', [loader.qbReqId]);
			
		}
	}
});