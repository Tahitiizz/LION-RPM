/*
 * 28/07/2011 SPD1: Querybuilder V2 - "Filters" grid  
 */

Ext.define('Ext.ux.querybuilder.FilterGridPanel', {
	extend: 'Ext.ux.querybuilder.AbstractQbGridPanel',
	    
	requires: [
		'Ext.ux.querybuilder.AbstractQbGridPanel',
		'Ext.ux.querybuilder.CellGridEditor',
		'Ext.ux.querybuilder.QbDateField',
		'Ext.ux.querybuilder.NetworkSelectionWindow'
	]
	     	
	,config: {
		id: 'qbFilterGridPanel'		
	}	
		
	,netSelWindow: null						// Network selection window
	          
	/* Constructor */
	,constructor: function(config) {		
		var me = this;
								
		// Apply the custom config
		Ext.apply(config, me.config);
			 						 
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }     	      
	 
	/* Destroy */
	,destroy: function() {

		var me = this;
				
		// Delete network selection window
		me.deleteObj(this.netSelWindow);		
		
		// Call parent
		return me.callParent(arguments);
	}
		 
	/* Init the grid store */
	,initStore: function(currentQuery) {
		var me = this;
			
		// Data model used by the grid store	
		Ext.define('FilterModel', {
    		extend: 'Ext.data.Model',
    		fields:['enable', 'id', 'type', 'label', 'name', 'familyId', 'productId', 'productName', 'operator', 'value', 'connector']
		});

		// Grid store (contains data displayed in the grid)
		me.store = Ext.create('Ext.data.Store', {
    		storeId:'filterGridStore',
    		model: 'FilterModel',
			data: currentQuery.select,
    		proxy: {
        		type: 'memory',
        		reader: {
            		type: 'json',
            		root: 'data'
        		}
    		}
		});

		// Add default filter (Max. nb. result filter)		
		me.store.add(me.app.getDefaultFilters());

		currentQuery.filters.data = Ext.Array.pluck(me.store.data.items, 'data');
			 
		// update current query when store is updated
		var updateCurrentQuery = function(options) {
									
			// Get the store content		
			storeContent = Ext.Array.pluck(me.store.data.items, 'data');
			
			// Set the content is the app currentQuery object
			currentQuery.filters.data = storeContent
				
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
	
	/* Create grid */
	,createGrid: function() {
		var me = this;
		var valueColumn = Ext.create('Ext.grid.column.Column', {
			cls: 'qbEditableCell',
			tdCls: 'qbValueCell',
			sortable: false,
			doNotSaveEditor: true,
			header: me.cs.filterGridPanel.valueColumn, 							// Value column
    		dataIndex: 'value',
    		field: 'textfield',													// Default type
    		renderer: Ext.bind(me.getValueColumnRenderer, me)					// Custom renderer
   		});
   
   		valueColumn.getEditor = function(record, defaultField) {				// Get value colum editor (textfield, date picker ...)   			
        	return me.getValueColumnEditor(record);
        }
           
        me.viewConf.plugins.ddGroup = 'filterGridInnerGroup';					// define a group name to manage drag & drop within the grid (to reorder) 
                     
		var grid = Ext.create('Ext.grid.Panel', {
			//resizable: {handles: 's'},
			//anchor: '100%',			
			split: true,
			region: 'south',			
			listeners: {
				resize: function() {
					// ExtJS Fix !!!
					// window.setTimeout(Ext.bind(me.doLayout, me), 200);
				}
				// FIX FOR EXT JS 4.0.7
				,scrollershow: function(scroller) {					
					if (scroller && scroller.scrollEl) {
						scroller.clearManagedListeners(); 
						scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
					}
				}
			},
			margin: '1 0 0 0',			
			//height: 300,
			flex: 1,    	
			id: 'filterGrid',
			viewType: 'qbGridview',	
    		store: Ext.data.StoreManager.lookup('filterGridStore'),
    		enableColumnHide: false,
    		enableColumnMove: false,    		    	
    		frame: true,        		
			layout: 'fit',
			title: Ext.ux.querybuilder.locale.filterGridPanel.title,
			iconCls: 'icoFilter',
			sortableColumns: false,
   			viewConfig: me.viewConf,													// drag/drop to reorder list management
    		columns: [
    			Ext.create('Ext.grid.RowNumberer'),
        		{
        			header: me.cs.filterGridPanel.enableColumn,  						// Enable column
        			xtype: 'checkcolumn',
        			dataIndex: 'enable',        			        		
        			width: 45,			        			
        			listeners: {
        				"checkchange": function() {        					
        					// refresh row color       
					        me.dataGrid.getView().refreshRowColor();
					        
					        // set hasChanged flag (ask for saving when leaving)
					        me.app.currentQuery.system.hasChanged = true;
        				}
        			}
        		},    			
        		{
        			header: me.cs.filterGridPanel.labelColumn,  						// Label column (editable: textfield)
        			flex: 1,
        			minWidth: 150,
        			dataIndex: 'label', 
        			field: 'textfield',
        			cls: 'qbEditableCell'
        		},
        		{
        			header: me.cs.filterGridPanel.nameColumn,							// Name column 
        			dataIndex: 'name',
        			width: 150
        		},
        		{
        			header: me.cs.filterGridPanel.productColumn, 						// Product name column
        			dataIndex: 'productName'
        		},
        		{
        			header: me.cs.filterGridPanel.operatorColumn, 						// Operator column
        			doNotSaveEditor: true,
        			dataIndex: 'operator',
        			width: 150,								
					getEditor: function(record, defaultField) {							// Get operator colum editor   			
        				return me.getOperatorColumnEditor(record);
        			},
					renderer: function(value, metadata, record) {						// For sys row, this column is empty
		            	return (record.get('type')=='sys')?me.cs.filterGridPanel.na:value;
		          	},
        			cls: 'qbEditableCell'
        		},
        		valueColumn,        		
        		{
        			header: me.cs.filterGridPanel.connectorColumn, 						// Connector column
        			dataIndex: 'connector',
        			getEditor: function(record, defaultField) {							// Get operator colum editor   			
        				return me.getConnectorColumnEditor(record);
        			},										                		                		                		            
		            cls: 'qbEditableCell',
		            renderer: function(value, metadata, record) {						// For sys row, this column is empty
		            	return (record.get('type')=='sys')?me.cs.filterGridPanel.na:value;
		            }
        		},
        		{
        			xtype: 'actioncolumn',
        			header: '',															// Delete column
        			width: 40,
        			items: [{
		                getClass: function(v, metadata, record) {						// Display a red cross except for 'sys'
		                	if (record.data.type != 'sys') {							// Max. nb. rows filter has 'sys' type -> it can't be deleted
		                		return 'icoCross qbPointer'
		                	}
		                },
		                tooltip: me.cs.filterGridPanel.tipDeleteRow,					// 'Delete row' tooltip
		                scope: me,
		                handler: me.onDeleteClick
		            }]         			
        		}
    		],
			dockedItems: [{
                xtype: 'toolbar',														// Button toolbar
                items: [                	
                	{
	                    iconCls: 'icoCross',
	                    tooltip: me.cs.filterGridPanel.deleteAllTip,
	                    text: me.cs.filterGridPanel.deleteAll,							// Delete all button
	                    scope: me,
	                    handler: function() {
	                    	me.store.removeAll();
	                    	me.dataGrid.getView().refresh(true);
	                    	
	                    	// Add default filter (Max. nb. result filter)		
							me.store.add(me.app.getDefaultFilters());							
							me.app.currentQuery.filters.data = Ext.Array.pluck(me.store.data.items, 'data');
	                    }
                	}
                ]
            }],                         
			selType: 'cellmodel',
    		plugins: [																	// Grid plugins definition
        		Ext.create('Ext.ux.querybuilder.CellGridEditor', {						// Cell editing pluging (allow editing cells) --> custom plugin to manage value field with differents editor possibility
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
			    defaultEventAction: 'preventDefault',
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
	
					// Add element into the filter grid
					Ext.ux.message.publish('/querytab/filtergrid/add', [data]);
													      						
		            return true;
		        }
	   		 });
		});				
				
		return grid;
	}
	
	/* Init wizard mode */
	,initWizardMode: function() {
		var me = this;
		
		// activate the possibility to add element from the left panel
		me.modeMessageHandlers.push(Ext.ux.message.subscribe('/querytab/filtergrid/add', me, me.addElement));
	}	
		
	/* Get the editor for the value column depend the record type
	 * Parameter:
	 *  - record : the current grid record
	 */ 	
	,getValueColumnEditor: function(record) {
		var me = this;
		var aggType = record.get('type');
		var ret;
		
		// if this is a TA
		if (aggType == 'ta') {
			var type = record.get('id');
						
			switch(type) {
				case 'day':					
				case 'day_bh':
					ret = Ext.create('Ext.ux.querybuilder.QbDateField', {format: this.cs.config.displayDateformat});					
					//ret = Ext.create('Ext.form.field.Date', {format: this.cs.config.displayDateformat});
					break;
				case 'hour':								
					ret = Ext.create('Ext.form.field.ComboBox', {
		                typeAhead: true,		                
		                triggerAction: 'all',
		                selectOnTab: true,
		                store: this.cs.filterGridPanel.hour,		   
		                listeners: {
		                	focus: function(obj) {		                				                		
		                		obj.expand();
		                		
		                		// FIX EXT JS 4.0.7 BUG ON COMBO LIST (Y POSITION FIX)
		                		window.setTimeout(function() {
		                			obj.getPicker().el.alignTo(obj.inputEl, 'tl-bl?');
		                		}, 0);
		                		
		                	},
		                	collapse: function(obj) {obj.triggerBlur()}
		                }
		          	});
		         	break;
				default:
					ret = Ext.create('Ext.form.field.Text');					 		
			}		
		// if this is a NA
		} else if (aggType == 'na' || aggType == 'na_axe3') {
			ret = me.getNaEditor(record);
		} else {
			ret = Ext.create('Ext.form.field.Text');
		}
		
		return ret;
	}
	
	/* Get the editor for the operator column
	 * Parameter:
	 *  - record : the current grid record
	 */ 
	,getOperatorColumnEditor: function(record) {
		var store, type = record.get('type');		
		
		switch(type) {
			case 'ta':			
				store = this.cs.filterGridPanel.timeOperator		// operator list for time agg.
				break; 
			case 'sys':												// For system row (ie. max. result filter) no operator	
				return null;								
			case 'na':			
			case 'na_axe3':
				store = this.cs.filterGridPanel.naOperator;			// operator list for network agg.				
				break;				
			default:
				store = this.cs.filterGridPanel.operator			// operator list for other row types
		}				
		
		return Ext.create('Ext.form.field.ComboBox', {				// create a combobox 
            typeAhead: true,		                
            triggerAction: 'all',
            selectOnTab: true,
            displayField: 'text',
    		valueField: 'value',
            store: store,
            listeners: {
            	focus: function(obj) {obj.expand()},
            	collapse: function(obj) {obj.triggerBlur()},
            	change: function(combo, newValue, oldValue, opts) {
            		// Operator change, check if the value field should be reseted            		
            		if (oldValue) {
            			if (newValue == 'Is null' || newValue == 'Is not null' || newValue == 'Is true' || newValue == 'Is false') {
		            			record.set('value', '');
		            	} else {            		
		            		if (newValue != 'In' && newValue != 'Not in') {
			            		if (oldValue == 'In' || oldValue == 'Not in') {
			            			record.set('value', '');            			            		
			            		}
			            	} else {		            		
			            		if (oldValue != 'In' && oldValue != 'Not in') {
			            			record.set('value', '');            			            		
			            		} 
			            	}
		            	}
		            }
            	}            	
            }            		                		                
      	});
      				
	}	
	
	/* Get the editor for the operator column
	 * Parameter:
	 *  - record : the current grid record
	 */ 
	,getConnectorColumnEditor: function(record) {
		var store, type = record.get('type');
				
		if (type=='sys') {
			return null;
		}	
		
		return Ext.create('Ext.form.field.ComboBox', {				// create a combobox 
            typeAhead: true,		                
            triggerAction: 'all',
            selectOnTab: true,
            displayField: 'text',
    		valueField: 'value',
            store: this.cs.filterGridPanel.connectorList,
            listeners: {
				focus: function(obj) {obj.expand()},
				collapse: function(obj) {obj.triggerBlur()}
			}      		                		                
      	});
      				
	}		
	/* Value column renderer
	 * Parameter
	 *  - value : column value
	 *  - metadata: column metadata
	 *  - record: the column store record
	 * Return:
	 *  String - the column value
	 */
	,getValueColumnRenderer: function(value, metaData, record) {								    		
		// If the current row is a TA
		if (record.get('type') == 'ta') {
			
			var type = record.get('id');
			switch(type) {
				case "day":
				case "day_bh":
					// return the date formated or the default message if the cell is empty				
					if (!value) {
						return this.formatDefaultText(this.cs.filterGridPanel.defaultDateColumnValue);
					} else if (!Ext.isDate(value) && typeof(value.split('today')[1]) !== 'undefined') {
						return value;
					} else {
						return Ext.util.Format.dateRenderer(this.cs.config.displayDateformat)(value);
					}
										
				case "hour":
					// return the hour or the default message if the cell is empty				
					return value?value:this.formatDefaultText(this.cs.filterGridPanel.defaultHourColumnValue);
				case "week":
				case "week_bh":
					// return the week or the default message if the cell is empty				
					return value?value:this.formatDefaultText(this.cs.filterGridPanel.defaultWeekColumnValue);
				case "month":
				case "month_bh":
					// return the month or the default message if the cell is empty				
					return value?value:this.formatDefaultText(this.cs.filterGridPanel.defaultMonthColumnValue);					
					
			}
		// Current row is a NA or NA axe3
		} else if (record.get('type') == 'na' || record.get('type') == 'na_axe3') {
			// For In and Not in operator, hide the value									
			if (record.data.operator == 'In' || record.data.operator == 'Not in') {
				
				// Display the number of selected items
				var nb=0;					
				nb = (record.data.value).split(',').length;
								
				if (value == 0) {
					return '';				
				} else if (value == 1) {
					return '1 item';
				} else {
					return nb+' items';
				}
			}			 											
		} else {
			// Default value for Between operator
			if (record.data.operator == 'Between' || record.data.operator == 'Not between') {
				return value?value:this.formatDefaultText(this.cs.filterGridPanel.defaultBetweenOperator);				
			}
		}

		return value;   		
	}
	
	/* format the default text */
	,formatDefaultText: function(txt, className) {
		var clsName = "qbDefaultCellText" + (className?' '+className:'');
		return "<span class=\"" + clsName + "\">" + txt + "</span>";
	}
	
	/* Set default element values */
	,setDefaultElementValues: function(element) {			
		element.connector = 'AND';
		element.enable = true;
		
		// Default operator
		if (element.type == 'na' || element.type == 'na_axe3') {				
			element.operator = 'In';			// In : for na
		} else {
			element.operator = 'Equals to';   	// Equals to : for others
		}	
	}
	
	/* Network button click*/
	,displayNetWorkSelWindow: function(field, record) {
		
		if (!this.netSelWindow) {
			this.netSelWindow = Ext.create('Ext.ux.querybuilder.NetworkSelectionWindow', {
				"id": "qbNetSelWindow",
				"height": 450,
				"width": 400
			});				
		}
		
		this.netSelWindow.displayWindow({
			"field": field,
			"record": record
		});
	}
	
	/* Get NA editor */
	,getNaEditor: function(record) {		
		var me = this;						
				
		var ret = Ext.create('Ext.form.field.Text', {				
			listeners: {				
				"focus": function(field) {
					if (record.data.operator == 'In' || record.data.operator == 'Not in') {																							
						// Display network selection window
						me.displayNetWorkSelWindow(field, record);
					}
				}
			}
		});
	
		// Overwrite onBlur method
		ret.onBlur = function(){
		    var me = this, focusCls = me.focusCls, inputEl = me.inputEl;
		    me.beforeBlur();
		    
		    if (focusCls && inputEl) {
		        inputEl.removeCls(focusCls);
		    }
		    
		    if (me.validateOnBlur) {
		        me.validate();
		    }
		    
		    me.hasFocus = false;
		    
		    // If the newtwork selection window is visible, don't send 'blur' event
		    var netSelWin = Ext.getCmp('qbNetSelWindow');
		    if (netSelWin && netSelWin.isVisible()) {
		    	return;
		    }
		    
		    me.fireEvent('blur', me);
		    me.postBlur();
		};	
		
		return ret;	
	}
});