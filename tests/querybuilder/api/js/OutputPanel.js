/*
 * 21/09/2011 SPD1: Output panel
 */

Ext.define('Ext.ux.queryDataDemo.OutputPanel', {
	extend: 'Ext.panel.Panel',	
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {		
		title: 'Reponse from the API',									
		height: 300,
		id: 'ouputPanel',
    	border: false,
		autoScroll: false,			
		frame: true,			
		region: 'center',	 
		layout: 'fit',     			      	       	    	                             		  	
    	items:[{
    		id: 'outputField',
			xtype: 'textarea',
			labelAlign: 'top',							
			value: ''					
	     }],
		dockedItems: [{
		    xtype: 'toolbar',
		    dock: 'bottom',
		    items: [
		       	{
		    		xtype: 'button', 
		    		text: 'Validate JSON',
		    		iconCls: 'icoTick',
		    		handler: function() {
		    			jsl.interactions.validate(Ext.getCmp('outputField').inputEl.dom);
		    		}
		    	}
		    ]
		}]	     
    },
		
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {		
		var me = this;
				
		// Apply the custom config
		Ext.apply(config, me.config);										
        
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
	
	/** Show a mask while loading query*/
	,showMask: function() {
		// Create the mask
		if (!this.loaderMask) {		
			this.loaderMask = new Ext.LoadMask(this.body, {msg:'Please wait'});
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