/*
 * 24/10/2011 SPD1: Querybuilder V2 - graph parameters window  
 */

Ext.define('Ext.ux.querybuilder.GraphParametersWindow', {
	extend: 'Ext.ux.querybuilder.QbWindow',	

	requires: [
		'Ext.form.Panel',
		'Ext.layout.container.VBox',
		'Ext.form.field.Text'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
	    title: Ext.ux.querybuilder.locale.graphParamWindow.title,
	    id: 'qbGraphParamWindow',
	    iconCls: 'icoChartEdit',
	    constrainHeader: true,
	    layout: 'fit',
	    closeAction: 'hide',
	    resizable: false,
	    modal: true
	},	
		
	paramForm: null,				// form	
					
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		
		// Add buttons
		me.config.dockedItems = [{
                xtype: 'toolbar',
                dock: 'bottom',	
                ui: 'footer',	
                layout: {
                	pack: 'center'
        		},												// Button toolbar
                items: [
                	{	                    
	                    text: me.cs.graphParamWindow.okButton,							// Delete all button
	                    minWidth: 80,
	                    scope: me,
	                    handler: me.saveParam
                	},
                	{	                   
	                    text: me.cs.graphParamWindow.cancelButton,
	                    minWidth: 80,
	                    scope: me,
	                    handler: function() {this.hide()}
                	}                	
                ]
		}];
            
		// Apply the custom config
		Ext.apply(config, me.config);
					 
		// Create filter panel
		me.paramForm = me.createForm();
		  		                                                             			
        // Add items
		me.items = [
			me.paramForm
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
		me.deleteObj(me.paramForm);
			    								
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
	        fieldDefaults: {
	        	labelAlign: 'top'	            	            
	        },
	        layout: {
	            type: 'vbox',
	            align: 'stretch'  // Child items are stretched to full width
	        },	
	        items: [
	        	{	          
		            xtype: 'textfield',
		            id: 'graphParamName',
		            fieldLabel: me.cs.graphParamWindow.name		            		           
	        	},
	        	{	          
		            xtype: 'textfield',
		            id: 'graphParamLeftAxisLabel',
		            fieldLabel: me.cs.graphParamWindow.leftAxisLabel
	        	},
	        	{	          
					xtype: 'textfield',
					id: 'graphParamRightAxisLabel',
		            fieldLabel: me.cs.graphParamWindow.rightAxisLabel
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

		// Set graph name
		Ext.getCmp('graphParamName').setValue(me.app.currentQuery.graphParameters.name || '');
		
		// Set X axis label
		Ext.getCmp('graphParamLeftAxisLabel').setValue(me.app.currentQuery.graphParameters.leftAxisLabel || '');
		Ext.getCmp('graphParamRightAxisLabel').setValue(me.app.currentQuery.graphParameters.rightAxisLabel || '');
		
		me.show();
	}
	
	/** Save parameters in the current query object*/
	,saveParam: function() {
		this.app.currentQuery.graphParameters.name = Ext.getCmp('graphParamName').getValue();			
		this.app.currentQuery.graphParameters.leftAxisLabel = Ext.getCmp('graphParamLeftAxisLabel').getValue();		
		this.app.currentQuery.graphParameters.rightAxisLabel = Ext.getCmp('graphParamRightAxisLabel').getValue();
		this.hide();		 			
		
		// Update graph
		Ext.ux.message.publish('/graphparameterspanel/refreshgraph');
		
		// Set hasChange true (to ask saving the query when leaving)
		this.app.currentQuery.system.hasChanged = true;		
	}	
});