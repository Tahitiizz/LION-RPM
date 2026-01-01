/*
 * 25/10/2011 SPD1: Querybuilder V2 - export options window  
 */

Ext.define('Ext.ux.querybuilder.ExportOptionsWindow', {
	extend: 'Ext.ux.querybuilder.QbWindow',	

	requires: [
		'Ext.form.Panel',
		'Ext.layout.container.VBox',
		'Ext.form.field.Checkbox',
		'Ext.form.field.Radio'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
	    title: Ext.ux.querybuilder.locale.exportOptionsWindow.title,
	    id: 'qbExportOptionsWindow',
	    constrainHeader: true,
	    iconCls: 'icoOptions',
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
	                    text: me.cs.exportOptionsWindow.okButton,							// Delete all button
	                    minWidth: 80,
	                    scope: me,
	                    handler: me.saveOptions
                	},
                	{	                   
	                    text: me.cs.exportOptionsWindow.cancelButton,
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
			cls: 'qbExpOptionsForm',
			id: 'qbExpOptionsFormId',
	    	plain: true,
	    	border: 0,
	    	bodyPadding: 5, 	    	      
	        fieldDefaults: {
	        	labelAlign: 'top',	            
	            anchor: '100%'
	        },
	        layout: {
	            type: 'vbox',
	            align: 'stretch'  // Child items are stretched to full width
	        },	
	        items: [
	        	{	 
	        		margin: '5 0 0 0',         
		            xtype: 'radiogroup',
		            id: 'expOptKpiCountRadio',
		            defaults: {type: 'radiofield', name: 'expOptkpiandcounter'},		            
		            height: 80,
		            fieldLabel: me.cs.exportOptionsWindow.kpiAndCounters,
		            layout: 'vbox',		            		            
            		items: [
                		{
                    		boxLabel: 'Label',                    		
                    		inputValue: 'label'                    		
                		}, {
                    		boxLabel: 'Name',                    		
                    		inputValue: 'name'                    		
	        			}
	        		]
	        	}, {	         	        		 
		            xtype: 'radiogroup',
		            id: 'expOptNetElRadio',
		            defaultType: 'radiofield',
		            defaults: {type: 'radiofield', name: 'expOptnetworkelement'},	            
		            height: 85,
		            fieldLabel: me.cs.exportOptionsWindow.networkElements,
		            layout: 'vbox',
            		items: [
            			{
                    		boxLabel  : 'Label',                    		
                    		inputValue: 'label'
                		}, {
                    		boxLabel  : 'Code',                    		                    		
                    		inputValue: 'code'
                		}, {
                    		boxLabel  : 'Both',                    		
                    		inputValue: 'both'
                    	} 
	        		]
	        	},{	        			        	
                   	xtype: 'checkbox',
                   	id: 'expOptincludenetparent',
                   	boxLabel  : 'Include parent network elements',
                   	inputValue: 'include'
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
		if (me.app.currentQuery.exportOptions) {
			Ext.getCmp('expOptKpiCountRadio').setValue({'expOptkpiandcounter': me.app.currentQuery.exportOptions.el});
			Ext.getCmp('expOptNetElRadio').setValue({'expOptnetworkelement': me.app.currentQuery.exportOptions.ne});
			Ext.getCmp('expOptincludenetparent').setValue(me.app.currentQuery.exportOptions.parentNe);
		}
		me.show();
	}
	
	/** Save parameters in the current query object*/
	,saveOptions: function() {
		this.app.currentQuery.exportOptions.el = Ext.getCmp('expOptKpiCountRadio').getValue().expOptkpiandcounter;
		this.app.currentQuery.exportOptions.ne = Ext.getCmp('expOptNetElRadio').getValue().expOptnetworkelement;
		this.app.currentQuery.exportOptions.parentNe = Ext.getCmp('expOptincludenetparent').getValue();
		this.hide();		 			
				
		// Set hasChange true (to ask saving the query when leaving)
		this.app.currentQuery.system.hasChanged = true;
				
	}	
});