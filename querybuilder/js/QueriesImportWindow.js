/*
 * 23/08/2011 SPD1: Querybuilder V2 - Queries import window  
 */

Ext.define('Ext.ux.querybuilder.QueriesImportWindow', {
	extend: 'Ext.ux.querybuilder.QbWindow',	

	requires: [
		'Ext.form.Panel',
		'Ext.layout.container.VBox',
		'Ext.form.field.File'		
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
	    title: Ext.ux.querybuilder.locale.queriesImportWindow.title,
	    id: 'qbQueriesImportWindow',
	    layout: 'fit',
	   	width: 400,
	    height: 150,
	    closeAction: 'hide',
	    resizable: false,
	    modal: true,
	    constrainHeader: true,
	    dockedItems: [{
        	xtype: 'toolbar',        	
        	dock: 'bottom',
        	ui: 'footer',
        	layout: {
                pack: 'center'
        	},
        	items: [
        		{
        			// Import button
	            	minWidth: 80,
            		text: Ext.ux.querybuilder.locale.queriesImportWindow.importButton,            	
            		handler: function (){
            			            			
            			var form = Ext.getCmp("importQueriesForm").getForm();
            			
                		if (form.isValid()) {
                    		form.submit({
                        		url: '../php/querybuilder.php?method=importQueries',
                        		waitMsg: 'Uploading your file...',
                        		success: function() {                        			                            		
                            		Ext.getCmp('qbQueriesImportWindow').hide();                            		
                        		},
                        		failure: function(form, action) {
                        			var message;
                        			
							        switch (action.failureType) {
							            case Ext.form.action.Action.CLIENT_INVALID:
							                message = 'Form fields may not be submitted with invalid values';
							                break;
							            case Ext.form.action.Action.CONNECT_FAILURE:
							                message = 'Ajax communication failed';
							                break;
							            case Ext.form.action.Action.SERVER_INVALID:							            
							            	message = action.result.msg || Ext.ux.querybuilder.locale.queriesImportWindow.importError;
									}
							       
							       
							       // Display a description of the import process
									Ext.ux.message.publish("/app/notification", [{title:Ext.ux.querybuilder.locale.queriesImportWindow.reportTitle, iconCls: "icoNotifInfo", message: message, closeButton: true}]);
									
									// Refresh queries panel
									Ext.ux.message.publish("/queriespanel/refresh");
									
									Ext.getCmp('qbQueriesImportWindow').hide();																      
							    }
                    		});
                		}                		
            		}
            	},{
            		// Close button
	            	minWidth: 80,
            		text: Ext.ux.querybuilder.locale.queriesImportWindow.closeButton,            	
            		handler: function (){            			
            			Ext.getCmp('qbQueriesImportWindow').hide();
            			var form = Ext.getCmp("importQueriesForm").getForm();
            			//form.reset();
            		}
            	}                	
        	]
    	}]
	},	
		
	form: null,						// form
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
		me.form = me.createForm();
		  		                                                             			
        // Add items
		me.items = [
			me.form
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
	    	id: "importQueriesForm",
	    	bodyPadding: "15 5 5 5",
	        fieldDefaults: {
	            labelWidth: 55,
	            anchor: '100%'
	        },
			fieldDefaults: {
            	labelAlign: 'top'            	
        	},	        
	        layout: {
	            type: 'vbox',
	            align: 'stretch'  // Child items are stretched to full width
	        },	
	        items: [{	          
				xtype: 'fileuploadfield',
		        fieldLabel: me.cs.queriesImportWindow.label,
		        name: 'importFile',
		        id: 'queriesImportFileUpload'
	        }]
	    });
	}
	
	/* Display window
	 * Parameter:
	 * 	data - object : contains data for the element to display (id ...)
	 */ 	
	,displayWindow: function(data) {
		var me = this;
		var options = {};
					
		me.show();
	}
	
});