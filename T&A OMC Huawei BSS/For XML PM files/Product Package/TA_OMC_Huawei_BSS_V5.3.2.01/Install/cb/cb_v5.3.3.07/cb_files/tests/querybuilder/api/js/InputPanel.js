/*
 * 21/09/2011 SPD1: Input panel
 */

Ext.define('Ext.ux.queryDataDemo.InputPanel', {
	extend: 'Ext.form.Panel',	
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {		
		title: 'JSON object to send',			
		collapsible: true,						
		height: 300,
    	border: false,
		autoScroll: false,			
		frame: true,			
		region: 'north',
		layout: 'anchor',
		defaults: {
        	anchor: '100%'
    	},
		split: 'true',      			      	       	    	                             		  	
    	items:[    						
	    {
    		id: 'inputField',    		
			xtype: 'textarea',
			height: '100%',
			labelAlign: 'top',					
			value: ''					
	    }]	 	     
    },
    	
	inputForm: null,
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {		
		var me = this;
				
		// Apply the custom config
		Ext.apply(config, me.config);										
        		
        // Add toolbar
		config.dockedItems = [{
		    xtype: 'toolbar',
		    dock: 'bottom',
		    items: [
		       	{
		    		xtype: 'button', 
		    		text: 'Validate JSON',
		    		iconCls: 'icoTick',
		    		handler: function() {
		    			jsl.interactions.validate(Ext.getCmp('inputField').inputEl.dom);
		    		}
		    	},
		        {
		        	xtype: 'button', 
		        	text: 'Send to API',
		        	iconCls: 'icoSend',
		        	handler: function() {
		        		me.onSend();
		        	}
		        }
		    ]
		}];
		        		        
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
	
	/** On send button click */
	,onSend: function() {			
		var requestParam = {};
		
		// Get input JSON object
		requestParam.data = Ext.getCmp('inputField').getValue();
		
		// Display loader
		Ext.getCmp('ouputPanel').showMask();		
				
		// Send request to the server
		Ext.Ajax.request({			
		    url: '../../../api/querydata/index.php?type=json',				// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	// Hide loader
				Ext.getCmp('ouputPanel').hideMask();
									    		    	
		    	// Load response
		    	Ext.getCmp('outputField').setValue(resp.responseText);						
		    },		    
			failure: function(resp){
		    	// Hide loader
				Ext.getCmp('ouputPanel').hideMask();
								
				// display the error message
				Ext.ux.message.publish("/app/notification", [{title: "Error", message: "Server error", iconCls: "icoNotifError"}]);
			}		    
		});
	}	
	
});