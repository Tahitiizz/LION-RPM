/*
 * 28/07/2011 SPD1: Querybuilder V2 - "Selected element" grid  
 */

Ext.define('Ext.ux.querybuilder.DataGridPanel', {
	extend: 'Ext.ux.querybuilder.AbstractQbGridPanel',
	    
	requires: [
		'Ext.ux.querybuilder.AbstractQbGridPanel',
		'Ext.ux.querybuilder.ValidationZone'	
	],
	    
	validationZone: null, 				// Validation zone component
	 
	config: {
		id: 'qbDataGridPanel',
		title: 'test',
		border: true,
		listeners: {
			resize: function() {
//				window.setTimeout(Ext.getCmp('qbDataGridPanel').doLayout, 200);
			}
		}
	},	
			          	 
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
		
		// message subscribe
        if (!me.messageHandlers) {me.messageHandlers = [];}
		me.messageHandlers.push(Ext.ux.message.subscribe('/querytab/orderwarning', me, me.orderWarningRefresh));		               
							
        // call the superclass's constructor  
        return this.callParent(arguments);
	}   
	    	  		  
	/* Init the grid store */
	,initStore: function(currentQuery) {
		var me = this;
			
		// Data model used by the grid store	
		Ext.define('DataModel', {
    		extend: 'Ext.data.Model',
    		fields:['uid', 'id', 'type', 'label', 'name', 'familyId', 'productId', 'productName', 'function', 'order', 'group', 'visible']
		});

		// Grid store (contains data displayed in the grid)
		me.store = Ext.create('Ext.data.Store', {
    		storeId:'dataGridStore',
    		model: 'DataModel',
			data: currentQuery.select,
    		proxy: {
        		type: 'memory',
        		reader: {
            		type: 'json',
            		root: 'data'
        		}
    		}
		});

		// update current query when store is updated
		var updateCurrentQuery = function(options) {		
			// Get the store content		
			storeContent = Ext.Array.pluck(me.store.data.items, 'data');						
			
			// Set the current query object with the current store content
			currentQuery.select.data = storeContent
 			 			
 			// If we are loading a new query don't set hasChanged and don't refresh agg. panel it has been already done
 			 if (!options || !options.isLoadingQuery) {
				// Set the hasChanged flag because the query has been modified
				currentQuery.system.hasChanged = true;
								
				// Refresh aggregations panel
				Ext.ux.message.publish('/aggpanel/refresh');													
			}					
						
			// update validationZone icons									
			Ext.ux.message.publish('/validationzone/update');
										
		}
		
		// When an element is added, updated or removed from the store
		me.store.on('datachanged', updateCurrentQuery);		
		me.store.on('clear', updateCurrentQuery);
		
	}
	
	/* Create data grid */
	,createGrid: function() {
		var me = this;
		
		me.viewConf.plugins.ddGroup = 'dataGridInnerGroup';					// define a group name to manage drag & drop within the grid (to reorder)
		
		var grid = Ext.create('Ext.grid.Panel', {
			tools: [
				{
				    type:'qbwarn',
				    id: 'qbWarnTool',
				    tooltip: me.cs.dataGridPanel.orderByWarning,
				    // hidden:true,
				    handler: function(event, toolEl, panel){
				        // refresh logic
				    }
				}
			],
			region: 'center',
			listeners: {
				resize: function() {
					// ExtJS Fix !!!
					//window.setTimeout(Ext.bind(me.doLayout, me), 200);					
				},
				// FIX FOR EXT JS 4.0.7
				scrollershow: function(scroller) {
					if (scroller && scroller.scrollEl) {
						scroller.clearManagedListeners(); 
						scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
					}
				}
			},
			margin: '1 0 0 0',
			//height: 100,    	
			id: 'dataGrid',	
			viewType: 'qbGridview',
			title: Ext.ux.querybuilder.locale.dataGridPanel.title,
    		store: Ext.data.StoreManager.lookup('dataGridStore'),
    		enableColumnHide: false,
    		enableColumnMove: false,    		    	
    		frame: true,    		        	
			layout: 'fit',				
			iconCls: 'icoGrid',
			sortableColumns: false,
			viewConfig: me.viewConf,										// drag/drop to reorder list management    		    		    		    		    
    		columns: [
    			Ext.create('Ext.grid.RowNumberer'),
        		{
        			header: me.cs.dataGridPanel.labelColumn,  				// Label column (editable: textfield)
        			flex: 1,
        			dataIndex: 'label', 
        			field: 'textfield',
        			cls: 'qbEditableCell'
        		},
        		{
        			header: me.cs.dataGridPanel.nameColumn,					// Name column 
        			dataIndex: 'name'
        		},
        		{
        			header: me.cs.dataGridPanel.productColumn, 				// Product name column
        			dataIndex: 'productName'
        		},
        		{
        			header: me.cs.dataGridPanel.functionColumn, 			// Function column (editable: combobox)
        			id: 'qbDataGridFunctionColumn',
        			tdCls: 'dynamicColorColumn',
        			dataIndex: 'function',								
					field: {
		                xtype: 'combobox',		                
		                typeAhead: true,								               
		                triggerAction: 'all',
		                selectOnTab: true,
		                store: me.cs.dataGridPanel.functions,
		                lazyRender: true,
		                listClass: 'x-combo-list-small',
		                listeners: {
		                	focus: function(obj) {obj.expand()},
		                	collapse: function(obj) {obj.triggerBlur()}
		                }		                
		           },
		            cls: 'qbEditableCell'
        		},
        		{
        			header: me.cs.dataGridPanel.orderColumn, 				// Order column
        			dataIndex: 'order',
        			field: {
		                xtype: 'combobox',
		                typeAhead: true,
		                triggerAction: 'all',
		                selectOnTab: true,
		                store: me.cs.dataGridPanel.order,
		                lazyRender: true,
		                listClass: 'x-combo-list-small',
		                listeners: {
		                	focus: function(obj) {obj.expand()},
		                	collapse: function(obj) {obj.triggerBlur()}
		                }		                
		           },
		           cls: 'qbEditableCell'
        		},
        		{
        			xtype: 'checkcolumn',
        			tdCls: 'dynamicColorColumn',
        			header: me.cs.dataGridPanel.groupColumn, 				// Group column
        			dataIndex: 'group',
        			width: 40,
        			listeners: {
    					"checkchange": function() {me.app.currentQuery.system.hasChanged = true;}
    				}        				
        		},
        		{
        			xtype: 'checkcolumn',
        			header: me.cs.dataGridPanel.visibleColumn, 				// Visible column
        			dataIndex: 'visible',
        			width: 40,
					listeners: {
    					"checkchange": function() {me.app.currentQuery.system.hasChanged = true;}
    				}        				        			
        		},
        		{
        			xtype: 'actioncolumn',
        			header: me.cs.dataGridPanel.filterColumn,				// Filter column
        			width: 35,
        			items: [{        				
        				getClass: function(v, metadata, record) {						// Display info icon
		                	return "icoFilterAdd qbPointer"; 
		                },		                
		                tooltip: me.cs.dataGridPanel.tipAddToFilter,					// 'Add to filter' tooltip
		                handler: function(grid, rowIndex, colIndex) {					// Copy to filter data
		                	var data = {element: {}};
		                	var row = grid.getStore().getAt(rowIndex);
		                	data.element.id = row.get('id');
		                	data.element.type = row.get('type');
		                	data.element.productId = row.get('productId');		               
		                	data.element.productName = row.get('productName');
		                	data.element.label = row.get('label');
		                	data.element.name = row.get('name');
		                	
		                	Ext.ux.message.publish('/querytab/filtergrid/add', [data]);		                							
		                }
		            }]         			
        		},
        		{
        			xtype: 'actioncolumn',
        			header: me.cs.dataGridPanel.infoColumn,								// Info column
        			width: 30,
        			items: [{
		                getClass: function(v, metadata, record) {						// Display info icon
		                	var type = record.get('type');
		                	if (type == 'RAW' || type == 'KPI') {		                	
		                		return 'icoInfo qbPointer';
		                	} else {
		                		return 'icoInfo unavailable';
		                	}
		                },
		                tooltip: me.cs.dataGridPanel.tipGetInfo,						// 'Get info' tooltip
		                handler: function(grid, rowIndex, colIndex) {					// Open window info
		                	var data = {element: {}};
		                	var row = grid.getStore().getAt(rowIndex);
		                	if (row.get('type') == 'RAW' || row.get('type') == 'KPI') {		                	
			                	data.element.id = row.get('id');
			                	data.element.type = row.get('type');
			                	data.element.productId = row.get('productId');
			                	data.element.productName = row.get('productName');		                		                	
			                	Ext.ux.message.publish('/infowindow/display', [data]);
			                }
		                }
		            }]         			
        		},
        		{
        			xtype: 'actioncolumn',
        			header: '',												// Delete column
        			width: 40,
        			items: [{
        				getClass: function(v, metadata, record) {						// Display info icon
		                	return "icoCross qbPointer"; 
		                },		                		                
		                tooltip: me.cs.dataGridPanel.tipDeleteRow,			// 'Delete row' tooltip
		                scope: me,
		                handler: me.onDeleteClick
		            }]         			
        		}
    		],
			dockedItems: [{
                xtype: 'toolbar',											// Button toolbar
                items: [
                	{    
                		id: 'qbDistinctButton',
                		tooltip: me.cs.dataGridPanel.distinctButtonTip,
                		iconCls: 'icoPageWhiteDb',            		
                		text: me.cs.dataGridPanel.distinctButton,			// Apply distinct clause button
                		enableToggle: true,
                		handler: function(button, e) {
                			me.app.currentQuery.select.distinct = button.pressed;
							me.app.currentQuery.system.hasChanged = true;
    					}
                	},                	
                	{
                		id: 'qbDisableFunctionsButton',
                		tooltip: me.cs.dataGridPanel.disableFunctionsTip,
	                    iconCls: 'icoFx',
	                    enableToggle: true,
	                    text: me.cs.dataGridPanel.disableFunctions,			// Disable functions button
	                    scope: me,
	                    handler: function(button, e) {
	                    	me.app.currentQuery.system.hasChanged = true;
    					},
    					listeners: {
    						toggle: function(button, e) {
	    						if (button.pressed) {
		                    		// Disable function and group columns
		                    		me.dataGrid.addCls('functionsDisabled');
		                    	} else {
		                    		// Enable function and group columns
		                    		me.dataGrid.removeCls('functionsDisabled');
		                    	}
	                			me.app.currentQuery.select.disableFunctions = button.pressed;								
    						}
    					}
                	},
                	{
	                    iconCls: 'icoCross',
	                    text: me.cs.dataGridPanel.deleteAll,				// Delete all button
	                    tooltip: me.cs.dataGridPanel.deleteAllTip,
	                    scope: me,
	                    handler: me.onDeleteAllClick
                	}
                ]
            }],                         
			selType: 'cellmodel',
    		plugins: [														// Grid plugins definition
        		Ext.create('Ext.ux.querybuilder.CellGridEditor', {			// Cell editing pluging (allow editing cells)
            		clicksToEdit: 1
        		})
    		]
		});
		
		// Create a KeyMap (shortcut for ESC key)
		grid.on('afterrender', function() {				
			var map = new Ext.util.KeyMap(grid.getView().id, {				
			    key: Ext.EventObject.ESC,
			    fn: function() {	// Switch between query/preview tab
			    	Ext.ux.message.publish('/app/tabswitch');
			    },
			    scope: me		    
			});
		});
		
		// Init grid as a drop zone (to receive RAW/KPI)
		grid.on('render', function() {			
						
		    grid.dropZone = new Ext.dd.DropZone(grid.id, {
		
				ddGroup: 'elementDDGroup',
				
		        // If the mouse is over a grid row, return that node. This is
		        // provided as the "target" parameter in all "onNodeXXXX" node event handling functions
		        getTargetFromEvent: function(e) {
		            return e.getTarget(grid.getView().rowSelector);
		        },
		
		        // On entry into a target node, highlight that node.
		        onNodeEnter: function(target, dd, e, data){ 
		        	// Add highlight class		        	 
					if (Ext.isIE) {grid.removeCls('x-panel-default-framed');} 		// IE Fix Grrrr ...
		            grid.addCls('drop-highlight-class');
		        },
		
		        // On exit from a target node, unhighlight that node.
		        onNodeOut: function(target, dd, e, data){ 		        	
		        	// Remove highlight class
		        	if (Ext.isIE) {grid.addCls('x-panel-default-framed');} 			// IE Fix Grrrr ...
		        	grid.removeCls('drop-highlight-class');		            
		        },
		
		        // While over a target node, return the default drop allowed class which
		        // places a "tick" icon into the drag proxy.
		        onNodeOver: function(target, dd, e, data){ 
		            return Ext.dd.DropZone.prototype.dropAllowed;
		        },
		
				// Called on drop
		        onNodeDrop: function(target, dd, e, data){	
					// Add element into the data grid
					Ext.ux.message.publish('/querytab/datagrid/add', [data]);													      						
		            return true;
		        }
	   		 });
	   		 
	   		 // Create validationZone
	   		 me.validationZone = Ext.create('Ext.ux.querybuilder.ValidationZone', {app: me.app});
	   		 			   		 
		});				
				
		return grid;
	}
	
	/* Init wizard mode */
	,initWizardMode: function() {
		var me = this;
		
		// activate the possibility to add element from the left panel
		me.modeMessageHandlers.push(Ext.ux.message.subscribe('/querytab/datagrid/add', me, me.addElement));
	}
	
	/* Set default element values */
	,setDefaultElementValues: function(element) {		
		element.visible = true;
		element.group = false;		
		element.uid = String(new Date().getTime());			// Set the uid with a unique id based on timestamp
	}	
	
	,destroy: function() {
		var me = this;
		
		// remove validation zone component    	
    	me.deleteObj(me.validationZone);
    	
    	 // call the superclass's constructor  
        return this.callParent(arguments);			
    }
    
    /* Display/Hide the orderBy warning icon (on top right corner of the grid) */
    ,orderWarningRefresh: function(status) {    	    	
    	// Display warning
    	if (status) {    		
    		Ext.getCmp('dataGrid').getEl().addCls('qbOrderWarning');
    		//Ext.getCmp('qbWarnTool').getEl().frame('#f80').slideOut('b').slideIn();
    		Ext.getCmp('qbWarnTool').getEl().frame('#f80');
    	} else {    		
    		Ext.getCmp('dataGrid').getEl().removeCls('qbOrderWarning');
    	}
    }	
});