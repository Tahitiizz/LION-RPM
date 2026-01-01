/*
 * 28/07/2011 SPD1: Querybuilder V2 - RAW/KPI getInfo window  
 */

Ext.define('Ext.ux.querybuilder.InfoWindow', {
	extend: 'Ext.ux.querybuilder.QbWindow',	

	requires: [
		'Ext.form.Panel',
		'Ext.layout.container.VBox',
		'Ext.form.field.Text',
		'Ext.form.field.TextArea'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
	    title: Ext.ux.querybuilder.locale.infoWindow.title,
	    id: 'qbInfosWindow',
	    iconCls: 'icoInfo',
	    layout: 'fit',
	    constrainHeader: true,
	    closeAction: 'hide',
	    resizable: true,
	    modal: false,
	    loader: {
        	url: '../php/querybuilder.php?method=getElementByIdFam',
        	renderer: function(loader, response, active) {
        		loader.getTarget().onDataLoaded(response);
        	}
    	}
	},	
		
	infoForm: null,					// form
	requestParam: null,				// request parameters
					
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
		me.infoForm = me.createForm();
		  		                                                             			
        // Add items
		me.items = [
			me.infoForm
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
		    	
		// Delete form
		me.deleteObj(me.infoForm);
			    								
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
	
	/* Create form */
	,createForm: function() {
		var me = this;
					
		return Ext.create('Ext.form.Panel', {
	    	plain: true,
	    	border: 0,
	    	bodyPadding: 5, 
	    	bodyCls: 'qbWindowForm',       
	        fieldDefaults: {
	            labelWidth: 55,
	            anchor: '100%'
	        },
	        layout: {
	            type: 'vbox',
	            align: 'stretch'  // Child items are stretched to full width
	        },	
	        items: [
	        	{	          
		            xtype: 'textfield',
		            fieldLabel: me.cs.infoWindow.name,
		            name: 'name',
		            id: 'infoName'
	        	},
	        	{	          
		            xtype: 'textfield',
		            fieldLabel: me.cs.infoWindow.label,
		            name: 'label',
		            id: 'infoLabel'
	        	},
	        	{	          
		            xtype: 'textfield',
		            fieldLabel: me.cs.infoWindow.product,
		            name: 'product',
		            id: 'infoProduct'
	        	},
	        	{	          
		            xtype: 'textfield',
		            fieldLabel: me.cs.infoWindow.family,
		            name: 'family',
		            id: 'infoFamily'
	        	},
				{
					xtype: 'textarea',
	        		fieldLabel: me.cs.infoWindow.description,
					labelAlign: 'top',
	        		name: 'description',
	        		id: 'infoDescription',
	        		style: 'margin: 10 0 0 0',
	        		flex: 1									// size to the remaining free space
	            },
	            {
					xtype: 'textarea',
	        		fieldLabel: me.cs.infoWindow.formula,
					labelAlign: 'top',
	        		name: 'formula',
	        		id: 'infoFormula',
	        		style: 'margin: 10 0 0 0'
	        	}	        	
	        ]
	    });
	}
	
	/* Display window
	 * Parameter:
	 * 	data - object : contains data for the element to display (id ...)
	 */ 	
	,displayWindow: function(data) {
		var me = this;
		var options = {};
		
		// If the user open two times the same element (close the window)		
		if (me.currentId == data.element.id) {
			me.currentId = null;
			me.hide();
			return;
		}
		
		// Set the request parameters
		options.params = {
			id: 		data.element.id,			// Element id (ex: raws.0004.08.27.01.00001)
			type: 		data.element.type,			// Element type (RAW/KPI ...)
			product:	data.element.productId,		// Product (sdp_id)
			productName:data.element.productName	// Product name
		};
			
		// Load the window content (make an AJAX request on querybuilder.php?method=getElementById with parameters: options.params)
		var loader = me.getLoader()
		loader.loadMask = true;						// Display loader while loading...	
		loader.load(options);
		
		me.show();
		
		// Save current display element id
		me.currentId = data.element.id;
	}
	
	/* Triggered when element info are loaded
	 *  parameter:
	 * 	 - response - string : the response (json string) 
	 */	
	,onDataLoaded: function(response) {        		
    	    	
    	// decode the JSON string to a JSON object
    	resp = Ext.decode(response.responseText);
    	
    	if (resp.type == 'RAW') {
    		// Don't display formula fields for RAW
    		Ext.getCmp('infoFormula').hide();
    	} else {
    		Ext.getCmp('infoFormula').show();
    	}
    	
    	// set the name
    	Ext.getCmp('infoName').setValue(resp.name);
    	
    	// set the label
    	Ext.getCmp('infoLabel').setValue(resp.label);
    	
    	// set the product name
    	Ext.getCmp('infoProduct').setValue(response.request.options.params.productName);
    	    	
    	// set the family
    	Ext.getCmp('infoFamily').setValue(resp.familyLabel);
    	
    	// set the family
    	Ext.getCmp('infoDescription').setValue(resp.description);
    	
    	// set the family
    	Ext.getCmp('infoFormula').setValue(resp.formula);    	    	
    	
        return true;        
	}
	
});