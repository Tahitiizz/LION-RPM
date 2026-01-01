/*
 * 28/07/2011 SPD1: Querybuilder V2 - Query tab
 */

Ext.define('Ext.ux.querybuilder.QueryTab', {
	extend: 'Ext.panel.Panel',
	         
	requires: [
		'Ext.layout.container.Anchor',
		'Ext.toolbar.Toolbar',
		'Ext.button.Button',
		'Ext.button.Split',
		'Ext.ux.querybuilder.DataGridPanel',
		'Ext.ux.querybuilder.FilterGridPanel',
		'Ext.window.MessageBox',
		'Ext.ux.querybuilder.UserQueriesTreeColumn',
		'Ext.ux.querybuilder.AggregationItem',
		'Ext.ux.querybuilder.AggPanel',
		'Ext.ux.querybuilder.ExportOptionsWindow'
	],
	
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbQueryTab',
		bodyPadding: '5 5 5 5',
	    border: false, 	        	    
	    bodyStyle: {
    		background: '#fff'
    	},       	             	    	     
	    layout: 'border',  
		title: Ext.ux.querybuilder.locale.queryTab.title,
		iconCls: 'icoWand',                    
		autoScroll: true,
      	listeners: {
       		activate: function(me) {       			
       			if (me.app) {       				
       				var type = me.app.currentQuery.general.type == 'sql'?'EditSql':me.app.currentQuery.general.type;       				
       				Ext.ux.message.publish('/querytab/changemode', [type]);	// Change mode when query tab is activate (enable left panel for wizard ...)
       				
       				// If there is a current preview query running, cancel it !
       				Ext.ux.message.publish('/previewtab/cancelrequest');       				
       			}
       		}
		}	
	},	
	
	app: null,				// pointer to the application
	aggPanel: null,			// aggregation panel
	sqlPanel: null,			// SQL panel
	dataGridPanel: null,	// data grid panel
	filterGridPanel: null,	// filter grid panel	
	toolbar: null,			// button toolbar
	messageHandlers: null,	// message handler (publish/subscribe)
	loaderMask: null,		// loader mask	
	sqlModeMessageHandlers: null, 	// Message handler for SQL mode
	exportOptionsWindow: null, 		// Export options window
	
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
		
		// Create aggregation panel
		me.aggPanel = me.createAggPanel();
				
		// Create SQL panel
		me.sqlPanel = me.createSqlPanel();
						
		// Create data grid panel
		me.dataGridPanel = Ext.create('Ext.ux.querybuilder.DataGridPanel', {app: config.app});
						
		// Create filter grid panel		
		me.filterGridPanel = Ext.create('Ext.ux.querybuilder.FilterGridPanel', {app: config.app});
						
		// Create cardPanel (containing wizard and sql panels)
		me.cardPanel = {
			xtype:'panel', 												              
	        deferredRender: false,			        
	        id: 'QueryTabPanel',
	        layout: 'card',		        		        		        
	        border: false,
	        region: 'center',	        
	        items: [
	        	{	
	        		id: 'wizardPanel',	// Wizard panel
	        		border: false,
	        		autoScroll: true,
					layout: 'border',
					items: [	        		
	        			me.aggPanel,
						me.dataGridPanel.dataGrid,
						me.filterGridPanel.dataGrid
					]					
	        	},
	        	{
	        		id: 'sqlPanel',		// SQL panel
	        		border: false,
	        		layout: 'fit',
	        		items: [
	        			me.sqlPanel
	        		]		        		
	        	}					
			]
		};								
					
		// Create button toolbar			 
		me.toolbar = me.createToolbar();
				
		me.items = [			
			me.cardPanel,
			me.toolbar
		];		 

        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		        
        // message subscribe
        me.messageHandlers = [];
        me.sqlModeMessageHandlers = [];
          
        // change mode (show/hide/edit sql) message
        me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/changemode', me, me.onChangeMode));  

        // load query message
        me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/loadquery', me, me.onLoadQuery));
        me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/loadexportedquery', me, me.onLoadExportedQuery));
        
        // refresh aggregations panel message
        me.messageHandlers.push(Ext.ux.message.subscribe('/aggpanel/refresh', me, me.onAggRefresh));
        me.messageHandlers.push(Ext.ux.message.subscribe('/aggpanel/refreshstate', me, me.onAggRefreshState));
                        
		// save window message
        me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/save', me, me.onSaveAction));
        me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/saveas', me, me.onSaveAsAction));
        
        // validation message
        me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/validationchange', me, me.onValidationChange));           
        
        // Update the SQL server list (combobox)
        me.messageHandlers.push(Ext.ux.message.subscribe('/filterpanel/productlistloaded', me, me.updateServerList));
                        
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
     * It destroy all component of the left panel to limit the memory leaks 
     * */     
	,destroy: function() {			
		var me = this;
		
		// Delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.messageHandlers);
    	
    	Ext.Array.each(me.sqlModeMessageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.sqlModeMessageHandlers);
    	 
    	// Delete loader mask
		me.deleteObj(me.loaderMask);
    	   	
		// Delete aggregation panel
		me.deleteObj(me.aggPanel);				
			
		// Delete SQL panel
		me.deleteObj(me.sqlPanel);
					
		// Delete data grid panel
		me.deleteObj(me.dataGridPanel);
		
		// Delete filter grid panel
		me.deleteObj(me.filterGridPanel);
				
		// Delete card panel			
		me.deleteObj(me.cardPanel);
		
		// Delete button toolbar
		me.deleteObj(me.toolbar);
						
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
		
	/* Create aggregation panel */
	,createAggPanel: function() {						
	    return Ext.create('Ext.ux.querybuilder.AggPanel', { });	  	     
	}
	
	/* Create SQL panel */
	,createSqlPanel: function() {
		var me = this;
		
		var produtList = Ext.create('Ext.data.Store', {
		    fields: ['name', 'id']
		});
						
	    return Ext.create('Ext.form.Panel',{	    	
	    	disabled: true,	    		        	
	    	border: true,
	    	margins: '0 0 5 0',
			autoScroll: false,			
			frame: true,			
			layout: {
    			type: 'vbox',
	    		align: 'stretch'	
	    	},		      		      			      	       	    	                             		  	
	    	items:[
	    	{
	                xtype: 'combobox',	      
	                id: 'qbExecuteServerCombo',          
	                fieldLabel: this.cs.sqlPanel.executeOn,
	                width: 300,
	                height: 25,
	                displayField: 'name',
    				valueField: 'id',				
	                queryMode: 'local',
	                forceSelection: true,	                
	                triggerAction: 'all',
	                selectOnTab: true,
	                store: produtList,
	                lazyRender: true,
	                listeners: {
	                	change: function(list, newValue, oldValue) {
	                		// Save the new value in the current query
	                		if (!me.app.currentQuery.sql) { me.app.currentQuery.sql = {};}	                		
	                		me.app.currentQuery.sql.executeOn = newValue;
	                		
							// Set hasChanged propertie to ask for saving when leaving current query, except at initialization (when oldValue = undefined), don't ask for saving when leaving the empty default query
	                		if (oldValue) {		                			                	
	                			me.app.currentQuery.system.hasChanged = true;
	                		}	                		
	                	}
	                }
        	}, {
					xtype: 'textarea',
					labelAlign: 'top',
					flex: 1,					
					value: '',
					id: 'sqlQueryField',					
					listeners: {
						dirtychange: function(field, isDirty) {			// When the textarea is modifed, set the flag hasChanged to ask for saving when leaving current query
							if (isDirty) {							
								me.app.currentQuery.system.hasChanged = true;
							}
						},
						blur: function(sqlField) {
							// Update current query object when the SQL field lost focus																		
							me.app.currentQuery.sql.query = sqlField.getValue();						
						},
						disable: function() {							
							// Fix for firefox & IE ...add the possibility to select the query in the textarea even if the field is disabled
							Ext.getCmp('sqlQueryField').inputEl.dom.setAttribute('readOnly','readonly');
							Ext.getCmp('sqlQueryField').inputEl.dom.removeAttribute('disabled');
							
							// Fix for IE 7 & IE 8
							Ext.getCmp('sqlQueryField').inputEl.dom.setAttribute('unselectable','off');
						},
						enable: function() {							
							// Fix for firefox & IE ...add the possibility to select the query in the textarea even if the field is disabled							
							Ext.getCmp('sqlQueryField').inputEl.dom.removeAttribute('readOnly');
						}
					}																													      	           		
		      }]
	    });	  	     
	}
		
	/* Create toolbar */
	,createToolbar: function() {
		
		var me = this;
		
		// Save button
		var saveButton = {
			id: 'btSave',
			iconCls: 'icoSave',
			xtype: 'splitbutton',
			text: me.cs.queryToolbar.save,
			ctCls: 'x-btn-over toolbarButtons',
			handler: function() {
				// Save action
				Ext.ux.message.publish('/querytab/save');
			},
			scope: me,				
			menu: [												// Save button dropdown menu
				{
					text: me.cs.queryToolbar.save,				// Save item
					iconCls: 'icoSave',
					handler: function() {						
						// Save action
						Ext.ux.message.publish('/querytab/save');
					},
					scope: me
				},
				{
					text: me.cs.queryToolbar.saveas,			// Save as item
					iconCls: 'icoSaveAs',
					handler: function() {
						// Save as action
						Ext.ux.message.publish('/querytab/saveas');
					},
					scope: me
				}
			]
		};
		
		// New button
		var newButton = {							
			id: 'btNew',
			iconCls: 'icoApplication',
			text:  me.cs.queryToolbar.btNew,
			ctCls: 'x-btn-over toolbarButtons',
			handler: Ext.bind(me.onNewButton, me)				// New button click
		};
		
		// Show SQL button
		var showSQLButton = {
			id: 'btShowSql',
			iconCls: 'icoShowSql',						
			text:  me.cs.queryToolbar.showSQL,
			ctCls: 'x-btn-over toolbarButtons',
			handler: function() {								// Show SQL panel
				Ext.ux.message.publish('/querytab/changemode', ['ShowSql']);
			}										
		};
				
		// Hide SQL button
		var hideSqlButton = {
			id: 'btHideSql',
			iconCls: 'icoHideSql',
			hidden: true,				
			text:  me.cs.queryToolbar.hideSQL,
			ctCls: 'x-btn-over toolbarButtons',
			handler: function() {								// Hide SQL panel
				Ext.ux.message.publish('/querytab/changemode', ['HideSql']);
			}
		};
		
		// Edit button
		var editButton = {
			id: 'btEdit',
			iconCls: 'icoEditSql',
			hidden: true,				
			text:  me.cs.queryToolbar.editSQL,
			ctCls: 'x-btn-over toolbarButtons',
			handler: Ext.bind(me.onEditButton, me)			
		};
						
		// Preview button
		var previewButton = {	
			id: 'btPreview',			
			text:  me.cs.queryToolbar.preview,
			ctCls: 'x-btn-over toolbarButtons',
			iconCls: 'icoPreviewError',
			// 20/03/2013 GFS - Bug 32731 - [SUP][5.2][AVP NA][Truphone] It should not be possible to run a query in SQL mode on T&A Gateway if there is no mixed KPI
			handler: Ext.bind(me.onPreviewButton, me),
			listeners: {
				mouseover: function(el, e) {					
					Ext.ux.message.publish('/validationzone/highlight', [true, e]);
				},
				mouseout: function() {
					Ext.ux.message.publish('/validationzone/highlight', [false]);
				}			 
			}			
		};
										
		// Export buttons
		var exportButton = {
			id: 'btExport',
			iconCls: 'icoExportError',
			xtype: 'splitbutton',
			text: me.cs.queryToolbar.btExport,
			ctCls: 'x-btn-over toolbarButtons',
			handler: Ext.bind(me.onCsvExportButton, me),
			menu: [
				{	// Export button
					id: 'btExport2',
					text: me.cs.queryToolbar.btExport,
					handler: Ext.bind(me.onCsvExportButton, me),
					iconCls: 'icoExportError'					
				},{	// Export options
					id: 'btExportOptions',
					text: me.cs.queryToolbar.exportOptions,
					handler: Ext.bind(me.onExportOptions, me),
					iconCls: 'icoOptions'
				},
				{	// SQL Export options
					id: 'btSqlExportOptions',
					iconCls: 'icoOptions',
					text: me.cs.queryToolbar.sqlExportOptions,
					disabled: true,
					hidden: true,
					handler: Ext.bind(me.onExportOptions, me)
				}
			],
			listeners: {
				mouseover: function(el, e) {					
					Ext.ux.message.publish('/validationzone/highlight', [true, e]);
				},
				mouseout: function() {
					Ext.ux.message.publish('/validationzone/highlight', [false]);
				}			 
			}								
		};

		// Default button bar
		var defaultButtonBar = new Ext.Toolbar({
			id: 'defaultButtonBar',		
			ctCls: 'queryToolbar',		
			height: 35,	
			items: [														
				newButton,	
				'-',		
				saveButton,	
				'->',				
				editButton,									 
				showSQLButton,
				hideSqlButton,
				'-',										
				previewButton,
				'-', 		
				exportButton 			
			]
		});		
		
		return Ext.create('Ext.panel.Panel',{	    		// Create a panel with the default toolbar		    	    
	    	border: false, 		 					
			layout: 'fit',
			id: 'queryToolbar',
	      	region: 'south',
	      	items: [
	      		defaultButtonBar
	      	]		  	
		});
	}	
	
	
	/* on change mode (show/hide/edit) sql
	 * Parameter:
	 *  - newMode: string (showSql, hideSql, editSql, wizard)
	 */
	,onChangeMode: function(newMode) {		
		var me = this;
				
		// compute the mode function name (onShowSQL, onHideSQL ...)
		var modeFunctionName = 'on' + newMode;
		
		// if this method exist call it
		if (me[modeFunctionName]) {
			me[modeFunctionName]();
		}
	}	
	
	/* Show sql panel (called by onChangeMode function)*/  		  
	,onShowSql: function() {
		
		// Refresh SQL query
		this.refreshSqlQuery();
						
		// Active sql card panel
		Ext.getCmp('QueryTabPanel').getLayout().setActiveItem('sqlPanel');
		
		// Hide "Show sql" button
		Ext.getCmp('btShowSql').hide();
		
		// Show "Hide sql" button
		Ext.getCmp('btHideSql').show();
		
		// Show "Edit" button
		Ext.getCmp('btEdit').show();	
					
	}
		
	/* Hide sql panel (called by onChangeMode function)*/  		  
	,onHideSql: function() {
		// Disable the possibility to add RAW/KPI to the SQL textarea
		Ext.Array.each(this.sqlModeMessageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});
						
		// Active wizard card panel
		Ext.getCmp('QueryTabPanel').getLayout().setActiveItem('wizardPanel');

		// Update title
		this.setTitle(this.cs.queryTab.title);
		
		// Update icon
		this.setIconCls('icoWand');
		
		// Hide "Hide sql" button
		Ext.getCmp('btHideSql').hide();
				
		// Show "Show sql" button
		Ext.getCmp('btShowSql').show();
		
		// Hide "Edit" button
		Ext.getCmp('btEdit').hide();
		
		// Update validationZone icons									
		Ext.ux.message.publish('/validationzone/update');
				
		// Enable display graph button
		Ext.getCmp('qbGraphButton').enable();
		
		// Export options menu
		Ext.getCmp('btSqlExportOptions').setVisible(false);
		Ext.getCmp('btExportOptions').setVisible(true);		
	}	
	
	/* Edit sql (called by onChangeMode function)*/  		  
	,onEditSql: function() {				
		
		// If we are still in SQL mode, don't do anything
		if (!Ext.getCmp('btShowSql').isVisible() && !Ext.getCmp('btHideSql').isVisible()) {
			return;
		}
		
		// Enable SQL textarea
		this.sqlPanel.enable();		
		
		// Active sql card panel
		Ext.getCmp('QueryTabPanel').getLayout().setActiveItem('sqlPanel');
				
		// Update title
		this.setTitle(this.cs.queryTab.sqlTitle);
		
		// Update icon
		this.setIconCls('icoQueryTab');
		
		// Hide "Hide sql" button
		Ext.getCmp('btHideSql').hide();
		
		// Hide "Show sql" button
		Ext.getCmp('btShowSql').hide();		
						
		// Hide "Edit" button
		Ext.getCmp('btEdit').hide();
		
		// Change current query type
		this.app.currentQuery.general.type = 'sql';
		
		// Create sql propertie in the current query
		this.app.currentQuery.sql = {
			query: Ext.getCmp('sqlQueryField').getValue(),
			executeOn: Ext.getCmp('qbExecuteServerCombo').getValue()
		};
		
		// Enable the possibility to add RAW/KPI from left panel
		Ext.Array.each(this.sqlModeMessageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});	// First unsubscribe if there is already sqlMessageHandlers from a previous query
		this.sqlModeMessageHandlers.push(Ext.ux.message.subscribe('/querytab/datagrid/add', this, this.addElementToSql));				
		this.sqlModeMessageHandlers.push(Ext.ux.message.subscribe('/sqltab/addformula', this, this.addFormulaElementToSql));
		
		//Disable display graph button (graph is disabled in SQL mode)
		Ext.getCmp('qbGraphButton').disable();
		
		// Export options menu
		Ext.getCmp('btSqlExportOptions').setVisible(true);
		Ext.getCmp('btExportOptions').setVisible(false);
				
		// reset wizard panel
		this.resetWizardPanel();
		
		// Reset preview table
		Ext.ux.message.publish('/previewtab/reset');
		
		// Disable control validation		
		Ext.ux.message.publish('/querytab/validationchange', [true]);
		
		// Set hasChange to true -> ask for saving when leaving
		this.app.currentQuery.system.hasChanged = true;	
		
	}	
		
	/* Open query save window
	 * Parameter:
	 *  - callback: function call after save
	 * Return false is the saved is cancelled by the user
	 */  	
	,openQuerySaveWindow: function(callback) {
		var me = this;		
		var currentQuery = me.app.currentQuery;
		
		// Set message box label buttons
		Ext.MessageBox.msgButtons[1].setText(me.cs.querySaveWindow.btSave);
		Ext.MessageBox.msgButtons[2].setText(me.cs.querySaveWindow.btCancel);
		
		// Show a dialog using config options:
		Ext.MessageBox.show({
		     title: me.cs.querySaveWindow.title,
		     msg: me.cs.querySaveWindow.message,
		     buttons: Ext.MessageBox.YESNO,
		     prompt: true,
		     icon: Ext.MessageBox.QUESTION,		     		    
		     fn: function(buttonId, text) {
		     	// If ok button -> Save the query
		     	if (buttonId == 'yes') {
		     		
		     		// If empty filename ...re-open the save popup
		     		if (text == '') {
		     			me.openQuerySaveWindow(callback);
		     			return;
		     		}
		     		
		     		// save the query name		     		
		     		currentQuery.general.name = text;
		     				     				     		
		     		// Save the query (overwrite: false, saveas: true)
					me.saveCurrentQuery(false, true, callback);
		     	}
		     }		     
		});
	}
	
	/* On query save action
	 * Parameter:
	 *  - callback: function called after save
	 */ 
	,onSaveAction: function(callback) {
		var me = this;		
		// If no Id, this a new query -> display save popup
		if (!me.app.currentQuery.general.id || !me.app.currentQuery.system.hasRight) {
			// Open query save window
			me.openQuerySaveWindow(callback);			
		} else {
			// Save the query
			me.saveCurrentQuery(false, false, callback);	
		}
				
	}
	
	/* On query save as action */
	,onSaveAsAction: function() {
		this.openQuerySaveWindow();
	}
	
	/* Save current query in the database
	 * Parameters
	 *  - overwrite: boolean - true to overwrite existing query
	 *  - saveas: boolean - true if it is a saveas action (save in a new query)
	 *  - callback: function call after save
	 */	 
	,saveCurrentQuery: function(overwrite, saveas, callback) {
		var me = this, requestParam = {};			
		
		// clone current query
		var query = Ext.decode(Ext.encode(app.currentQuery));
		
		// if this is a sql query
		if (me.app.currentQuery.general.type == 'sql') {
			var sqlField = Ext.getCmp('sqlQueryField');
			
			// reset dirty attribute
			sqlField.originalValue = sqlField.getValue();
			sqlField.checkDirty();
			
			delete query.select;	
			delete query.filters;
			delete query.graphParameters;
		} else {
			// If this is a wizard query
			delete query.sql;
			
			// remove some properties d'ont need to be saved
			Ext.Array.forEach(query.select.data, function(item) {
				delete item.name;
				delete item.productName;	
			});
			Ext.Array.forEach(query.filters.data, function(item) {
				delete item.name;
				delete item.productName;	
			});
		}
								
		// remove system and aggregation properties (don't need to be saved)
		query.system = null;
		delete query.system;
		
		// get the current query
		requestParam.query = Ext.encode(query);				
							
		// set overwrite parameter
		requestParam.overwrite = overwrite?'true':'false'; 
		
		// set save as parameter
		requestParam.saveas = saveas?'true':'false';
							
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=setQuery',				// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	var response = Ext.decode(resp.responseText);		    	
		    	
		    	// If there is an error	    	
		    	if (response.error) {
		    		// If a query already exist with this name
		    		if (response.error.number == me.cs.errors.queryAlreadyExist) {
		    			// Overwrite ?
		    			me.displayOverwritePopup(saveas, callback);
		    		} else {
						// Display the error in the console browser
		        		Ext.ux.message.publish('/app/error', [response.error]);
		    		}
		    		
		    	} else {
		    		// Display a notification
		    		Ext.ux.message.publish("/app/notification", [{title: me.cs.queryTab.saveTitle, message: me.cs.queryTab.saveMessage, iconCls: "icoNotifOk"}]);
		    				    				    		
		    		// set the query id
		    		me.app.currentQuery.general.id = response.id;
		    		
		    		// reset has changed propertie
		    		me.app.currentQuery.system.hasChanged = false;
		    		
		    		// refresh right panel queries lists
		    		Ext.ux.message.publish('/queriespanel/refresh');
		    		
		    		// if a callback has been supplied, call it
		    		if (callback) {		    			
		    			callback.apply(me, [true]);	
		    		}		    			
		    	}				
		    },
		    failure: function(response, opts) {
			    	// On error
        			Ext.ux.message.publish('/app/error', [response]);
    		}
		});	
	}
	
	/* Display a popup to confirm to overwrite the query
	 * Parameter:
	 *  - callback: a callback function
	 */ 	
	,displayOverwritePopup: function(saveas, callback) {
		var me = this;
		
		// Set message box label buttons
		Ext.MessageBox.msgButtons[1].setText(me.cs.queriesPanel.overwriteButton);
		Ext.MessageBox.msgButtons[2].setText(me.cs.queriesPanel.cancelButton);
		
		// Open a confirm popup
		Ext.MessageBox.show({title: me.cs.queriesPanel.overwriteTitle, msg: me.cs.queriesPanel.overwriteMessage, buttons: Ext.MessageBox.YESNO, icon: Ext.MessageBox.QUESTION, fn: function(buttonId, text) {
	     	// If yes button -> overwrite the existing query
	     	if (buttonId == 'yes') {	     								     		
                // save query with overwrite true																
				me.saveCurrentQuery(true, saveas, callback);
	     	}
	   	}});	
	}
	
	/* Load a query from a CSV export id
	 * Parameter:
	 *  exportId: string - exportId to load
	 */
	,onLoadExportedQuery: function(exportId) {
		this.onLoadQueryAjaxRequest('../php/querybuilder.php?method=getExportedQuery&id=' + exportId);		    			    
	}
	
	/* Load a query from its id
	 * Parameter:
	 *  queryId: string - query id to load
	 */ 
	,onLoadQuery: function(queryId) {
		this.onLoadQueryAjaxRequest('../php/querybuilder.php?method=getQuery&id=' + queryId);		    		
	}
		
	/* Load a query: used by onLoadExportedQuery et onLoadQuery functions
	 * Parameter:
	 *  url: string - url of the ajax request (contains the method to call and the id to load)
	 */
	,onLoadQueryAjaxRequest: function(url) {
		var me = this;
	 	
	 	// Ask for save if the current query has not been saved
		me.checkSaveStatus(function() {
			// Loader display
			me.showMask();
			
			// Send request to the server
			Ext.Ajax.request({
			    "url": url,		// call query builder facade		    
			    "success": function(resp){
			    	try {
			    		var response = Ext.decode(resp.responseText);
			    	} catch(e) {			    		
			    		// On error
        				Ext.ux.message.publish('/app/error', ['Invalid response']);        				
						me.hideMask();
						return;
			    	}		    	
			    			    	
			    	// If there is an error	    	
			    	if (response.error) {		    		
			    		// Display the error in the console browser
		        		Ext.ux.message.publish('/app/error', [response.error]);
		        		
		        		// Query not found on the server
		        		if (response.error.type == 'qb' && response.error.number == 3) {		        			
		        			Ext.ux.message.publish('/queriespanel/refresh');
		        			Ext.ux.message.publish("/app/notification", [{title: me.cs.queryTab.loadTitleError, message: me.cs.queryTab.queryDeleted, iconCls: "icoNotifError"}]);
		        		}
		        		
			    	} else {
			    		// Load query data		    		
			    		me.loadQuery(response);		    				    		
			    	}	
			    	
			    	// Hide mask (after 200ms. -> waiting just a few milliseconds, time for the browser to display all data)
			    	Ext.Function.defer(function() {this.hideMask()}, 200, me);
			    							
			    },
			    "failure": function(response, opts) {
			    	// On error
        			Ext.ux.message.publish('/app/error', [response]);
        			// Hide mask
					me.hideMask();
    			}
			});							
		});
	}
			
	/* Load a query into the GUI
	 * Parameter:
	 *  query: object - query to load
	 */ 
	,loadQuery: function(query) {		
		var me = this;
				
		// Load general		
		me.app.currentQuery.general = query.general;			
		
		// Display query tab
		Ext.getCmp('mainTabPanel').getLayout().setActiveItem(me);				
								
		if (query.general.type == 'sql') {
			// reset wizard panel
			me.resetWizardPanel();			
									
			// Load sql query			
			me.loadSqlQuery(query);			
		} else {
			// reset SQL panel
			me.resetSqlPanel();
			
			// Load wizard query
			me.loadWizardQuery(query);
		}						
				
		// Load query propeties	 						
		me.app.currentQuery.system = query.system;
		me.app.currentQuery.exportOptions = query.exportOptions;
		
		// Reset preview table
		Ext.ux.message.publish('/previewtab/reset');
		
		// Reset graph parameters	
		Ext.ux.message.publish('/graphparameterspanel/clear');
		 	
		// Load graph parameters
		Ext.ux.message.publish('/graphparameterspanel/loadparameters', [query]);		

		// refresh right panel queries lists (set the opened query in bold font weight)
		Ext.ux.message.publish('/queriespanel/refresh');

	}

	/* Load a SQL query into the GUI
	 * Parameter:
	 *  query: object - query to load
	 */
	,loadSqlQuery: function(query) {
		var me = this;
				
		// update current query with sql data
		me.app.currentQuery.sql = query.sql;
					
		// edit SQL panel			
		Ext.ux.message.publish('/querytab/changemode', ['EditSql']);
					
		// set textarea value
		var sqlField = Ext.getCmp('sqlQueryField');
		sqlField.setValue(query.sql.query);
		
		// set 'execute on' combo value
		Ext.getCmp('qbExecuteServerCombo').setValue(query.sql.executeOn||1);
				
		// Update current query object																		
		me.app.currentQuery.sql.query = query.sql.query;
								
		// reset dirty attribute		
		sqlField.originalValue = sqlField.getValue();
		sqlField.checkDirty();	
	}
		
	/* Load a wizard query into the GUI
	 * Parameter:
	 *  query: object - query to load
	 */ 
	,loadWizardQuery: function(query) {
		var me = this;
		
		// show wizard panel
		Ext.ux.message.publish('/querytab/changemode', ['HideSql']);
		
		// Disable store event	
		me.dataGridPanel.store.suspendEvents(false);
		me.filterGridPanel.store.suspendEvents(false);

		// Reset selected elements grid
		me.dataGridPanel.store.removeAll();						
		me.filterGridPanel.store.removeAll();
		
		// Load selected elements
		Ext.Function.defer(function() {				
			Ext.Array.forEach(query.select.data, function(item) {
				// Set default values for TA and NA object
				item = me.setTaNaDefaultValues(item);								
				me.dataGridPanel.store.add(item);	// add element one by one				
			}, this);
			
			Ext.Array.forEach(query.filters.data, function(item) {
				// Set default values for TA and NA object
				item = me.setTaNaDefaultValues(item);		
				me.filterGridPanel.store.add(item);	// add element one by one 
			}, this);
			
			// Enable store event 		
 			me.dataGridPanel.store.resumeEvents();
 			me.filterGridPanel.store.resumeEvents();

			// Load aggregations panel
			me.app.currentQuery.system.aggregations = query.aggregations;
			me.loadAggPanel(query.aggregations);
			 					 			
 			// Update current query
 			me.dataGridPanel.store.fireEvent('datachanged', {isLoadingQuery: true});
 			me.filterGridPanel.store.fireEvent('datachanged', {isLoadingQuery: true});
 		 	 	
 		 	// Set distinct button state
			me.app.currentQuery.select.distinct = query.select.distinct;
			Ext.getCmp('qbDistinctButton').toggle(me.app.currentQuery.select.distinct);
			
			// Set disable functions button state
			me.app.currentQuery.select.disableFunctions = query.select.disableFunctions;
			Ext.getCmp('qbDisableFunctionsButton').toggle(query.select.disableFunctions);
		 		 	
		 	// Reset sql field (disabled + cleared)
		 	me.resetSqlPanel();
		 	
			// change the color of agg. grid rows, for aggregations that are not available in the agg. panel
			me.dataGridPanel.dataGrid.getView().refreshRowColor();
			me.filterGridPanel.dataGrid.getView().refreshRowColor();
			
			// Load graph parameters
			Ext.Array.forEach(query.graphParameters.gridParameters, function(item) {
				// Add elements one by one
				Ext.getCmp('qbGraphParametersPanel').gridStore.add(item);										 
			}, this);
					 	 		 	
		}, 100, me); 			
				
	}
	
	/* Load aggregations panel
	 * Parameter:
	 *  agg : aggregations object
	 */
	,loadAggPanel: function(agg) {		
		var me = this;
		
		// remove all items from agg. panel					
		me.aggPanel.clearAgg();
					
		var items = [];
		var naInCommon, taInCommon;
		
		// If not network agg.
		if (!agg.network.na && !agg.network.na_axe3) {
			// No network aggregation, warn the user			
			me.aggPanel.displayNoNetworkAgg();
			naInCommon = false;		
		} else {
			naInCommon = true;			
			// load standard network aggregations
			if (agg.network.na) {
				Ext.Array.forEach(agg.network.na, function(na) {
					// create agg. item 			
					items.push(Ext.create('Ext.ux.querybuilder.AggregationItem', {id: 'qbAgg'+na.code, type: 'na', label: na.label, code: na.code, cls: 'qbAggItem na'}));					
				}, this);
			}
						
			// load 3th axis network aggregations
			if (agg.network.na_axe3) {
				Ext.Array.forEach(agg.network.na_axe3, function(na) {
					// create agg. item 			
					items.push(Ext.create('Ext.ux.querybuilder.AggregationItem', {id: 'qbAgg'+na.code, type: 'na_axe3', label: na.label, code: na.code, cls: 'qbAggItem na_axe3'}));										
				}, this);
			}
									
			// add items in the NA agg. panel
			if (items[0]) {
				this.aggPanel.addNa(items);
			}
			
			// update agg. item state (make pressed, agg. item found in the filter grid panel)
			me.updateAggItemsSelectedState(items);
		}
		
		items = [];
				
		// load time aggregations		
		if (agg.time && agg.time.length > 0) {	
			taInCommon = true;			
			Ext.Array.forEach(agg.time, function(ta) {
				// create agg. item 			
				items.push(Ext.create('Ext.ux.querybuilder.AggregationItem', {id: 'qbAgg'+ta.code, type: 'ta', label: ta.label, code: ta.code, cls: 'qbAggItem ta'}));										
			}, this);
		} else {
			taInCommon = false;			
			me.aggPanel.displayNoTimeAgg();
		}		
		
		// Update Na & Ta in common status
		me.aggPanel.setCommonStatus(naInCommon, taInCommon);
		
		// add items in the TA agg. panel
		if (items[0]) {
			this.aggPanel.addTa(items);
		}
		
		// update agg. item state (make pressed, agg. item found in the filter grid panel)
		me.updateAggItemsSelectedState(items);
					
		// save aggregations in the currentQuery object
		this.app.currentQuery.system.aggregations = agg;
		
		// change the color of agg. grid rows, for aggregations that are not available in the agg. panel
		me.dataGridPanel.dataGrid.getView().refreshRowColor();
		me.filterGridPanel.dataGrid.getView().refreshRowColor();			
	 		
	}
	
	/* Display a blank new query */
	,onNewButton: function() {
		var me = this;
		
		// ask for save if the current query has not been saved...
		me.checkSaveStatus(function() {
			
			me.app.currentQuery.general.type = "wizard";
						
			// reset wizard panel
			me.resetWizardPanel();

			// Active wizard card panel
			Ext.ux.message.publish('/querytab/changemode', ['HideSql']);		
									
			// reset SQL panel
			me.resetSqlPanel();			
										
			// Reset currentQuery
			var blank = me.app.getBlankQuery(); 
			me.app.currentQuery.system = blank.system;
			me.app.currentQuery.general = blank.general;
			me.app.currentQuery.exportOptions = blank.exportOptions;				
	 		me.app.currentQuery.system.hasChanged = false;
			me.app.currentQuery.graphParameters = blank.graphParameters;
			
			// Refresh right panel queries lists
			Ext.ux.message.publish('/queriespanel/refresh');
			
			// Reset preview table
			Ext.ux.message.publish('/previewtab/reset');
			
			// Reset graph parameters	
			Ext.ux.message.publish('/graphparameterspanel/clear');			
		});
	}	

	/* On edit button click */
	,onEditButton: function() {
		var me = this;		
				
		// Set message box label buttons
		Ext.MessageBox.msgButtons[1].setText(me.cs.editSqlWindow.editButton);
		Ext.MessageBox.msgButtons[2].setText(me.cs.editSqlWindow.cancelButton);
		
		// Display message box
		Ext.MessageBox.show({
			title: me.cs.editSqlWindow.title, 
			msg: me.cs.editSqlWindow.message, 
			buttons: Ext.MessageBox.YESNO, 
			icon: Ext.MessageBox.QUESTION, 
			fn: function(buttonId, text) {
		     	// If yes button -> ask for save if needed then edit sql
		     	if (buttonId == 'yes') {										     					     		
					me.checkSaveStatus(function() {											// Save
		            	Ext.ux.message.publish('/querytab/changemode', ['EditSql']);		// Edit sql
					});
		     	}
	   		}
	   	});
	   		   	
	}
			
	/* Reset SQL panel (called on new button click)*/
	,resetSqlPanel: function() {
		this.sqlPanel.disable();
		
		// Disable SQL textarea
		Ext.getCmp('sqlQueryField').setValue('')
		
		// Reset 'execute on' combo value (set it on the master)
		// 20/03/2013 GFS - Bug 32731 - [SUP][5.2][AVP NA][Truphone] It should not be possible to run a query in SQL mode on T&A Gateway if there is no mixed KPI
		Ext.getCmp('qbExecuteServerCombo').setValue(0);
	}
	
	/* Reset wizard panel (called on new button click)*/
	,resetWizardPanel: function() {
		var me = this;		

		// Disable store event	
		me.dataGridPanel.store.suspendEvents(false);
		me.filterGridPanel.store.suspendEvents(false);

		// Reset selected elements grid
		me.dataGridPanel.store.removeAll();						
		me.filterGridPanel.store.removeAll();

		// Add default filter (Max. nb. result filter)		
		me.filterGridPanel.store.add(me.app.getDefaultFilters());							
		me.app.currentQuery.filters.data = Ext.Array.pluck(me.filterGridPanel.store.data.items, 'data');
				
		// Enable store event 		
 		me.dataGridPanel.store.resumeEvents();
 		me.filterGridPanel.store.resumeEvents();

 		// Update current query
		me.dataGridPanel.store.fireEvent('datachanged', {isLoadingQuery: true});
		me.filterGridPanel.store.fireEvent('datachanged', {isLoadingQuery: true});
		
		// Refresh aggregations panel
		Ext.ux.message.publish('/aggpanel/refresh');	
										
		// Set distinct button state		
		Ext.getCmp('qbDistinctButton').toggle(false);
		
		// Set disable functions button state		
		Ext.getCmp('qbDisableFunctionsButton').toggle(false);
	}	
	
	/* Check if the query has change and open the save popup if needed
	 * The callback is called if the save is not needed or after the save if the user don't cancelled the action
	 * Parameter:
	 *  - callback: function called after save or if save is not needed
	 *  - cancelButtonLabel: string, label for cancel button "No" by default
	 */
	,checkSaveStatus: function(callback, cancelButtonLabel) {		
		var me = this;
			   		
		// if the query has changed since last save
		if (me.app.currentQuery.system.hasChanged) {
			
			// Set message box label buttons
			Ext.MessageBox.msgButtons[1].setText(me.cs.queriesPanel.saveButton);			
			Ext.MessageBox.msgButtons[2].setText(cancelButtonLabel?cancelButtonLabel:me.cs.queriesPanel.cancelButton);
		
			// ask user to save
			Ext.MessageBox.show({title: me.cs.queriesPanel.saveTitle, msg: me.cs.queriesPanel.askForSave, buttons: Ext.MessageBox.YESNO, icon: Ext.MessageBox.QUESTION, fn: function(buttonId, text) {
		     	// If yes button
		     	if (buttonId == 'yes') {	     								     		
					// save query...and call the callback if user don't cancel the save (popup cancel button)
					me.onSaveAction(callback);		
		     	} else {
		     		// the current query will not be saved -> call the callback
					callback.apply(me, [false]);
		     	}
		   	}});		   	
		} else {			
			// query has not changed, no need to save -> call the callback
			callback.apply(me, [true]);	
		}		
	}
	
	/* 20/03/2013 GFS - Bug 32731 - [SUP][5.2][AVP NA][Truphone] It should not be possible to run a query in SQL mode on T&A Gateway if there is no mixed KPI
	 * Check if the product is selected before running the SQL query
	 */
	,onPreviewButton: function() {
		var me = this;
		// Server combobox
		var serverCombo = Ext.getCmp('qbExecuteServerCombo');

		if (this.app.currentQuery.general.type == 'sql' && serverCombo.getValue() == '') {
			// Set message box label buttons
			Ext.MessageBox.msgButtons[1].setText(me.cs.queriesPanel.saveButton);
			// ask user to save
			Ext.MessageBox.show({title: me.cs.queriesPanel.previewPopupTitle, msg: me.cs.queriesPanel.previewPopupMessage, buttons: Ext.MessageBox.OK, icon: Ext.MessageBox.ERROR});
		}
		else {
			Ext.getCmp('mainTabPanel').getLayout().setActiveItem('qbPreviewTab');
		}
	}
	
	/* Refresh aggregation panel */
	,onAggRefresh: function() {		
		var me = this, requestParam = {};
		
		// If no element in the selected elements grid
		if(!me.app.currentQuery.select.data[0] && !me.app.currentQuery.filters.data[0]) {
			// clear agg. panel							
			me.aggPanel.clearAgg();
			me.app.currentQuery.system.aggregations = null;
			return false;
		}
		
		// get the current query
		requestParam.query = Ext.encode(me.app.currentQuery);											
								
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=getQueryAgg',				// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	var response = Ext.decode(resp.responseText);		    	
		    	
		    	// if there is an error	    	
		    	if (response.error) {
					// display the error in the console browser
	        		Ext.ux.message.publish('/app/error', [response.error]);		    		
		    	} else {
		    		// Load agg. panel
					me.loadAggPanel(response.aggregations);	
		    	}				
		    }
		});			
		
	}
	
	/* Refresh aggregation panel items state */
	,onAggRefreshState: function() {		
		if (this.app.currentQuery.system.aggregations) {
			this.loadAggPanel(this.app.currentQuery.system.aggregations)
		}
	}
		
	/* Update the selected state of agg. panel items */
	,updateAggItemsSelectedState: function(items) {
		// update pressed state
		Ext.Array.each(items, function(item) {
												
			// find this agg. in the data grid
			var row = this.dataGridPanel.store.getById(item.code);			
			// if it founded, selected it
			if (row) {
				item.select();
			} else {
				// find this agg. in the filter grid
				row = this.filterGridPanel.store.getById(item.code);				
				if (row) {
					// if it founded, selected it
					item.select(); 
				}	
			}
			
			
		}, this);		
	}
	
	/* Validation status change
	 * @param status boolean true if ok, false to display error icon	 
	 */
	,onValidationChange: function(status) {		
		// If all types of element have been added to the query remove yellow triangle on the preview button
		if (status) {
			Ext.getCmp('btPreview').setIconCls('icoPreview');
			Ext.getCmp('btExport').setIconCls('icoExport');
			Ext.getCmp('btExport2').setIconCls('icoExport');			
			Ext.getCmp('qbPreviewTab').setIconCls('icoPreview');			
		} else {
			Ext.getCmp('btPreview').setIconCls('icoPreviewError');
			Ext.getCmp('btExport').setIconCls('icoExportError');						
			Ext.getCmp('btExport2').setIconCls('icoExportError');
			Ext.getCmp('qbPreviewTab').setIconCls('icoPreviewError');			
		}
	}
	
	/* Refresh SQL query */
	,refreshSqlQuery: function() {
		var me = this, requestParam = {};
		
		// Display loading message ...
		Ext.getCmp('sqlQueryField').setValue('Query loading ...');
		
		// Get the current query
		requestParam.query = Ext.encode(me.app.currentQuery);											
								
		// Send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=getComputedSqlQuery',				// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	var response = Ext.decode(resp.responseText);		    	
		    	
		    	// if there is an error	    	
		    	if (response.error) {		    		
		    		// User error -> display the message
		    		if (response.error.type == 'user') {
						// display the error message
	        			Ext.getCmp('sqlQueryField').setValue(response.error.message);
	        			
	        		// System error -> don't display the message to the user (php error)
	        		} else {
	        			// display the error in the console browser
	        			console.log('Server error: ', response.error.message);
	        			
	        			// display a generic message to the user	        			
	        			Ext.getCmp('sqlQueryField').setValue('Invalid query');
	        		}		    		
		    	} else {
		    		// Load SQL query
		    		Ext.getCmp('sqlQueryField').setValue(response.query);						
		    	}				
		    }
		});
			
	}
	
	/* Add element (RAW/KPI) to SQL textarea
	 * @param: data object contains the element id to add
	 */
	,addElementToSql: function(data) {		
		var me = this;
		
		// Create JSON request object
		var requestParam = {
			id: 		data.element.id,			// Element id (ex: raws.0004.08.27.01.00001)
			type: 		data.element.type,			// Element type (RAW/KPI ...)
			product:	data.element.productId		// Product (sdp_id)
		};
								  
		// Send request to get the element from the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=getElementById',	// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	// get the response as a JSON object
		        var response = Ext.decode(resp.responseText);
		        
		        // insert the element name at the cursor position
				me.insertAtCaret(Ext.getCmp('sqlQueryField').inputId, response.name);

		        // If there is an error
		        if (response.error) {
		        	// Display the error in the console browser
		        	Ext.ux.message.publish('/app/error', [response.error]);
		        }					       
		    }
		});		
						
	}	
	
	/* Add KPI formula to SQL textarea
	 * @param: data object contains the element id to add
	 */
	,addFormulaElementToSql: function(data) {		
		var me = this;
		
		// Create JSON request object
		var requestParam = {
			id: 		data.element.id,			// Element id (ex: raws.0004.08.27.01.00001)
			type: 		data.element.type,			// Element type (RAW/KPI ...)
			product:	data.element.productId		// Product (sdp_id)
		};
								  
		// Send request to get the element from the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=getElementByIdFam',	// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	// get the response as a JSON object
		        var response = Ext.decode(resp.responseText);
		        
		        // insert the KPI formula at the cursor position
				me.insertAtCaret(Ext.getCmp('sqlQueryField').inputId, response.formula);

		        // If there is an error
		        if (response.error) {
		        	// Display the error in the console browser
		        	Ext.ux.message.publish('/app/error', [response.error]);
		        }					       
		    }
		});		
						
	}	
		
	/* Insert text in a textarea at the cursor position
	 * @param id : string textarea id
	 * @param text : text to add
	 */ 
	,insertAtCaret: function(id, text) {
		var txtarea = document.getElementById(id);
		
		// This method does not manage IE8
		if (Ext.isIE && (!Ext.isIE6 || !Ext.isIE7)) {
			txtarea.value += text;
			return;	
		}
					
		var scrollPos = txtarea.scrollTop;
		var strPos = 0;
		var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ? 
			"ff" : (document.selection ? "ie" : false ) );
		if (br == "ie") { 
			txtarea.focus();
			var range = document.selection.createRange();
			range.moveStart ('character', -txtarea.value.length);
			strPos = range.text.length;
		}
		else if (br == "ff") strPos = txtarea.selectionStart;
		
		var front = (txtarea.value).substring(0,strPos);  
		var back = (txtarea.value).substring(strPos,txtarea.value.length); 
		txtarea.value=front+text+back;
		strPos = strPos + text.length;
		if (br == "ie") { 
			txtarea.focus();
			var range = document.selection.createRange();
			range.moveStart ('character', -txtarea.value.length);
			range.moveStart ('character', strPos);
			range.moveEnd ('character', 0);
			range.select();
		}
		else if (br == "ff") {
			txtarea.selectionStart = strPos;
			txtarea.selectionEnd = strPos;
			txtarea.focus();
		}
		txtarea.scrollTop = scrollPos;
	}
	
	/** Update server list combobox
	 * @param products object product list
	 */
	,updateServerList: function(products) {						
		// 20/03/2013 GFS - Bug 32731 - [SUP][5.2][AVP NA][Truphone] It should not be possible to run a query in SQL mode on T&A Gateway if there is no mixed KPI
		var list = [{name: '', id: ''}];
		
		// Server combobox
		var serverCombo = Ext.getCmp('qbExecuteServerCombo');
		
		// Create store data for the combo
		Ext.Array.forEach(products, function(item) {
			list.push({name: item.data.text, id: item.data.elementId}); 	
		}, this);
		
		// Add the product list to the combo store
		serverCombo.store.add(list);
		
		// Select by default the first server
		serverCombo.setValue(list[0]);									
		
	}
	
	/** Show a mask while loading query*/
	,showMask: function() {
		// Create the mask
		if (!this.loaderMask) {		
			this.loaderMask = new Ext.LoadMask(Ext.getCmp('mainTabPanel').el, {msg:this.cs.app.pleaseWait});
		}
		
		// Display the mask
		this.loaderMask.show();		
	}
		
	/** Hide mask */
	,hideMask: function() {		
		// Display the mask
		this.loaderMask.hide();		
	}
	
	/** Set default values for TA or NA elements 
	* @param element object the element to set
	* @return object the element with default values */
	,setTaNaDefaultValues: function(element) {		
		if (element.type == 'ta' || element.type == 'na' || element.type == 'na_axe3') {
			element.name = element.id;
			element.productName = 'N/A';
		}				
		return element;
	}
	
	/** On CSV export button click */
	,onCsvExportButton: function() {
		var me = this;
		
		// Ask to save before export (if the query has been modified since last save)
		me.checkSaveStatus(function(isSaved) {
			// If the user choose yes to save, or it was already saved before export button click			
			if (isSaved && me.app.currentQuery.general.id) {
				Ext.ux.message.publish('/downloadpanel/export', [[me.app.currentQuery.general.id]]);
			}
		}, me.cs.downloadPanel.cancelSaveButton);
	}
	
	/** On export button click */
	,onExportOptions: function() {
		// Create the window (if not already created)
		if (!this.exportOptionsWindow) {
			this.exportOptionsWindow = Ext.create('Ext.ux.querybuilder.ExportOptionsWindow', {
				app: this.app,
				height: 290,
	    		width: 350
			});				
		} 
				
		// Display window
		this.exportOptionsWindow.displayWindow();		
	}	
});