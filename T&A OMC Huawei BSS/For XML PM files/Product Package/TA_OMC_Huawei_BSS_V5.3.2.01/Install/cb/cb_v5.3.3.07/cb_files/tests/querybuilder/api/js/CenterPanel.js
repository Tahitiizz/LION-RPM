/*
 * 21/09/2011 SPD1: CenterPanel  
 */

Ext.define('Ext.ux.queryDataDemo.CenterPanel', {
	extend: 'Ext.panel.Panel',	
		         
	requires: [
		'Ext.ux.queryDataDemo.InputPanel',
		'Ext.ux.queryDataDemo.OutputPanel'
	],

	
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {							
		region: 'center',
	    layout: 'border'
	},	
		
	inputPanel: null,
	outputPanel: null,
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {		
		var me = this;
				
		// Apply the custom config
		Ext.apply(config, me.config);										
        
		// Create input panel
		me.inputPanel = Ext.create('Ext.ux.queryDataDemo.InputPanel', {app: config.app});
		
		// Create output panel
		me.outputPanel = Ext.create('Ext.ux.queryDataDemo.OutputPanel', {app: config.app});		  		  
                                                            			
        // Add items
		me.items = [
			me.inputPanel,
			me.outputPanel
		];								
                        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
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
		
		// Delete panels			
		me.deleteObj(me.inputPanel);
		me.deleteObj(me.ouputPanel);
		
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