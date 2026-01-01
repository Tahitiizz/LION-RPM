/*
 * 21/09/2011 SPD1: LeftPanel  
 */

Ext.define('Ext.ux.queryDataDemo.LeftPanel', {
	extend: 'Ext.panel.Panel',	
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		title: 'Sample queries',			
		width: 150,		
		region: 'west',		   
	    split: true,
	    collapsible: true,         
	    border: true,                     	    	     
		margins: '0 0 0 5',			
		layout: {
			type: 'vbox',
			align: 'center'
		}		
	},	
		
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {		
		var me = this;
				
		// Apply the custom config
		Ext.apply(config, me.config);										
        
        me.items = [
	       	{	       		
		    	xtype: 'button', 
		    	width: 140,
		    	text: 'Get Data 1',	
		    	margins: '10 2 2 2',	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method":"getData","parameters":{"select":{"distinct":false,"data":[{"id":"day","type":"ta","order":"Ascending"},{"id":"kpis.0016.04.00002","type":"KPI","productId":"1"},{"id":"kpis.0016.04.00003","type":"KPI","productId":"1"},{"id":"hn","name":"hn","type":"na"}]},"filters":{"data":[{"id":"day","type":"ta","operator":"Less than","value":"2011-09-16"}]}}}');
		    	}
			}, {				
		    	xtype: 'button',
				width: 140,
		    	text: 'Get data 2',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method":"getData","parameters":{"select":{"data":[{"id":"kpis.0016.06.00018","type":"KPI","label":"Critical error rate  at Mobile Station level during control command phase","productId":"1","visible":true,"function":"","order":"","group":"","productName":"TA Cigale Iu"},{"id":"cell","label":"SAI","type":"na","visible":true,"function":"","order":"","group":""},{"id":"day","label":"Day","type":"ta"},{"id":"kpis.0016.01.00088","type":"KPI","label":"PS RAB maximum duration (s)","productId":"1"},{"id":"kpis.0016.06.00008","type":"KPI","label":"UL TCP Retransmission Rate per user","productId":"1"},{"id":"week","label":"Week","type":"ta","visible":true}]},"filters":{"data":[{"id":"maxfilter","type":"sys","value":"1500"}]}}}');
		    	}
			},  {				
		    	xtype: 'button',
		    	width: 140, 
		    	text: 'Get KPI (HTML)',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method":"getKpiHtmlList","parameters":{"text":"call","products":[{"id":"1","families":["ept","paglac","roam","ho","traffic","apname"]}]}}');
		    	}
			},  {				
		    	xtype: 'button',
		    	width: 140, 
		    	text: 'Get RAW (HTML)',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method":"getRawHtmlList","parameters":{"text":"call","products":[{"id":"1","families":["ept","paglac","roam","ho","traffic","apname"]}]}}');
		    	}		    	
			},  {				
		    	xtype: 'button',
		    	width: 140, 
		    	text: 'Get KPI (JSON)',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method":"getKpiList","parameters":{"text":"call","products":[{"id":"1","families":["ept","paglac","roam","ho","traffic","apname"]}]}}');
		    	}
			},  {
		    	xtype: 'button', 
		    	width: 140,
		    	text: 'Get RAW (JSON)',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method":"getRawList","parameters":{"text":"call","products":[{"id":"1","families":["ept","paglac","roam","ho","traffic","apname"]}]}}');
		    	}		    	
			},  {
		    	xtype: 'button', 
		    	width: 140,
		    	text: 'Get NE (HTML)',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method": "getNeHtmlList", "parameters": {"text": "a","na": "cell"}}');
		    	}		    	
			},  {
		    	xtype: 'button', 
		    	width: 140,
		    	text: 'Get NE (JSON)',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method": "getNeList", "parameters": {"text": "a","na": "cell"}}');
		    	}		    	
			},  {
		    	xtype: 'button', 
		    	width: 140,
		    	text: 'Get agg. in common',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method": "getAggInCommon", "parameters": [{"id": "raws.0016.10.64.99.00002", "type":"RAW", "productId":"1"},{"id": "raws.0016.10.64.99.00008", "type":"RAW", "productId":"1"}]}');
		    	}		    	
			},  {
		    	xtype: 'button', 
		    	width: 140,
		    	text: 'Get products/fam.',	
		    	margin: 2,	    	
		    	handler: function() {		    		
		    		Ext.getCmp('inputField').setValue('{"method": "getProductsFamilies"}');
		    	}		    	
			}							
			
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