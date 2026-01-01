Ext.define('homepage.view.configuration.ConfigChart' ,{
	extend: 'Ext.form.Panel',
    alias : 'widget.configchart',

	iconCls: 'icoChartEdit',
	bodyStyle:'padding:5px 5px 0',
    fieldDefaults: {
        msgTarget: 'side'
    },
    defaultType: 'textfield',
    defaults: {
    	anchor: '100%'
    },
	autoScroll: true,

	title: 'Graph',
    modified: false,
	
    initComponent: function() {
    	var me = this;
    	Ext.define('CounterModel', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'label', type: 'string'}
            ]
        });
    	
    	var currentTab = Ext.getCmp('tabPanel').getActiveTab() == 0 ? 0 : Ext.getCmp('tabPanel').getActiveTab().getId();
    	
    	var graphslist = Ext.create('Ext.data.Store', {
	        fields: ['id', 'label'],
	        data: [{
	            "id": "ri_graph",
	            "label": "RI Graph"
	        }, {
	            "id": "alarms_graph",
	            "label": "Alarms Graph"
	        }, {
	            "id": "summary_graph",
	            "label": "Summary Graph"
	        }
	        ]
   		});
        var counterStore = Ext.create('Ext.data.Store', {
        	// destroy the store if the grid is destroyed
        	autoDestroy: true,
        	model: 'CounterModel'
        });
    	
    	Ext.define('NetworkModel', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'label', type: 'string'},
                {name: 'level', type: 'string'}
            ]
        });
    	
       
        
        var neStore = Ext.create('Ext.data.Store', {
        	// destroy the store if the grid is destroyed
        	autoDestroy: true,
        	model: 'NetworkModel'
        });
        
        Ext.define('GraphModel', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'id', type: 'string'},
                {name: 'label', type: 'string', sortType:Ext.data.SortTypes.asUCText},
                {name: 'ag_name', type: 'string'},
                {name: 'displayed_alarms', type: 'string'},
                {name: 'piechart', type: 'boolean',  defaultValue: false}                
            ]
        });
        
        Ext.define('AlarmModel', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'id', type: 'string'},
                {name: 'label', type: 'string', sortType:Ext.data.SortTypes.asUCText},
                {name: 'grid_name', type: 'string'},
                {name: 'comment', type: 'string'},
                {name: 'dashboard', type: 'string'}                
            ]
        });
        
        Ext.define('AlarmModel_ar', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'id', type: 'string'},
                {name: 'label', type: 'string', sortType:Ext.data.SortTypes.asUCText}
            ]
        });
        
       Ext.define('AlarmModelDisplayed_ar', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'id', type: 'string'},
                {name: 'label', type: 'string', sortType:Ext.data.SortTypes.asUCText}, 
                {name: 'checked', type: 'boolean'}
            ]
        });
    	
        var checkedAlarmStore = Ext.create('Ext.data.Store', {
       		id: 'calcAlarmStore',
       		autoDestroy: true,
        	model: 'AlarmModel_ar'
        });
        
        var alarmStore = Ext.create('Ext.data.Store', {
        	// destroy the store if the grid is destroyed
        	autoDestroy: true,
        	model: 'AlarmModel',
			fields: ['id', 'label', 'grid_name', 'comment', 'dashboard'],
			sorters: [
			          {
			            property: 'label',
			            direction: 'ASC'
			          }
			         ], 
			sortOnLoad: true,
        	data: {}
        });
        
        Ext.define('MapModel', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'groupname', type: 'string'},
                {name: 'trendkpiid', type: 'string'},
                {name: 'trendkpiproductid', type: 'string'},
                {name: 'trendkpilabel', type: 'string'},
                {name: 'trendkpitype', type: 'string'},
                {name: 'trendkpifunction', type: 'string'},
                {name: 'typekpi', type: 'string'},
                {name: 'trendunit', type: 'string'},
                {name: 'lowthreshold', type: 'string'},
                {name: 'highthreshold', type: 'string'},
                {name: 'networkaxisnumber', type: 'string'},
                {name: 'roamingnetworklevel', type: 'string'},
                {name: 'roamingnetworklevel2', type: 'string'},
                {name: 'roamingneid', type: 'string'},
                {name: 'roamingneid2', type: 'string'},	 		
                {name: 'dynamic', type: 'string'},
                {name: 'minvalue', type: 'string'},
                {name: 'maxvalue', type: 'string'},
                {name: 'trendproductlabel', type: 'string'},
                {name: 'donutkpiid', type: 'string'},
                {name: 'typekpidonut', type: 'string'},
                {name: 'donutkpiproductid', type: 'string'},
                {name: 'donutkpilabel', type: 'string'},
                {name: 'donutproductlabel', type: 'string'},
                {name: 'donutunit', type: 'string'},        
            ]
        });
         
         var alarmStore_ar = Ext.create('Ext.data.Store', {
        	// destroy the store if the grid is destroyed
        	autoDestroy: true,
        	model: 'AlarmModelDisplayed_ar',
			fields: ['id', 'label'],
			sorters: [
			          {
			            property: 'label',
			            direction: 'ASC'
			          }
			         ], 
			sortOnLoad: true,
        	data: {}
        });
        
        var graphsStore = Ext.create('Ext.data.Store', {
        	// destroy the store if the grid is destroyed
        	id : 'graphstore',
        	autoDestroy: true,
        	model: 'GraphModel',
			fields: ['id', 'label','ag_name','displayed_alarms','piechart'],
			sorters: [
			          {
			            property: 'label',
			            direction: 'ASC'
			          }
			         ], 
			sortOnLoad: true,
        	data: {}
        });
        
       var penaltiescriteriaStore = Ext.create('Ext.data.Store', {
        	// destroy the store if the grid is destroyed
        	id: 'penaltiescriteriaStore',
        	autoDestroy: false,
        	model: 'AlarmModelDisplayed_ar',
			fields: ['id', 'label', 'checked'],
			sorters: [
			          {
			            property: 'label',
			            direction: 'ASC'
			          }
			         ], 
			sortOnLoad: true,
        	data: {}
        });
         
        //ratio number validation test
        var ratioNumberTest = /^\d{1,3}$/;
        Ext.apply(Ext.form.VTypes, {
        	ratioNumber: function(v) {
        		return ratioNumberTest.test(v);
        	},
        	ratioNumberText: 'Invalid ratio number',
        	ratioNumberMask: /[0-9]/
        });

        
		me.items = [
			// Global configuration
			{
				id: 'general_configChart',
				xtype: 'component',
				html: 'General',
				cls:'x-form-label'
			},
			{
				id: 'selectedtab_configChart',
				xtype: 'component',
				html: 'Selected tab',
				cls:'x-form-label'
			},
			{
				id: 'alarmSettings_configChart',
				xtype: 'component',
				html: 'Alarm settings',
				cls:'x-form-label'
			},
			{
				fieldLabel: 'Title',
				id: 'titleField_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'blur': function (field) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
	
							//var record = Ext.getCmp('mapKpiGrid_configChart').store.findRecord('groupname', selection.data.groupname);
							if (record != null) {
								record.data.groupname = field.getRawValue();
							}
						}	
					}
				}
			},
			{
				fieldLabel: 'Url',
				id: 'urlField_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}
			},
			{
	            fieldLabel: 'Product',
	            id: 'productCombo_configChart',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            valueField: 'id',
	            displayField: 'label',
	            store: {
					fields: [
					         {name: 'id', type: 'string'},
				             {name: 'label', type: 'string'/*, sortType:Ext.data.SortTypes.asUCText*/}
				             ],
					/*sorters: [
					          {
					            property: 'label',
					            direction: 'ASC'
					          }
					         ], 
					sortOnLoad: true,
					*/
					proxy: {
				        type: 'ajax',
				        url : 'proxy/configuration.php',
				        extraParams: {
							task: 'GET_PRODUCTS'
						},
						actionMethods: {
					        read: 'POST'
					    },
				        reader: {
				            type: 'json',
				            root: 'product'
				        }
				    },
				    autoLoad: true
	            },
				listeners: {
					'dirtychange': function(combo) {
						me.checkModifications();
						
						Ext.Ajax.request({
							url: 'proxy/configuration.php',
							params: {
								task: 'GET_ALARMS',
								product: combo.getValue()
							},
				    
							success: function(response) {
								// Add the alarms in the combobox
								var alarms = Ext.decode(response.responseText).alarm;
								var alarmCombo = Ext.getCmp('AlarmCombo_configChart');															
								alarmCombo.store.loadData(alarms);
								alarmCombo.store.sort('label', 'ASC');
								alarmCombo.enable(true);
								Ext.getCmp('addAlarmButton_configChart').enable(true);
							}
						});
					},
					'change': function(combo) {
						me.checkModifications();
						
						Ext.Ajax.request({
							url: 'proxy/configuration.php',
							params: {
								task: 'GET_ALARMS',
								product: combo.getValue()
							},
				    
							success: function(response) {
								// Add the alarms in the combobox
								var alarms = Ext.decode(response.responseText).alarm;
								var alarmCombo = Ext.getCmp('AlarmCombo_configChart');															
								alarmCombo.store.loadData(alarms);
								alarmCombo.store.sort('label', 'ASC');
								alarmCombo.enable(true);
								Ext.getCmp('addAlarmButton_configChart').enable(true);
							}
						});
					}
					
					
				}
	        },
	        {
	            fieldLabel: 'Product',
	            id: 'productCombo_configChart_ar',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            valueField: 'id',
	            displayField: 'label',
	            store: {
					fields: [
					         {name: 'id', type: 'string'},
				             {name: 'label', type: 'string'/*, sortType:Ext.data.SortTypes.asUCText*/}
				             ],
					/*sorters: [
					          {
					            property: 'label',
					            direction: 'ASC'
					          }
					         ], 
					sortOnLoad: true,
					*/
					proxy: {
				        type: 'ajax',
				        url : 'proxy/configuration.php',
				        extraParams: {
							task: 'GET_PRODUCTS'
						},
						actionMethods: {
					        read: 'POST'
					    },
				        reader: {
				            type: 'json',
				            root: 'product'
				        }
				    },
				    autoLoad: true
	            },
				listeners: {
					'dirtychange': function(combo) {
						me.checkModifications();
						if(Ext.getCmp('productCombo_configChart_ar').isDisabled() == false){
							Ext.Ajax.request({
								url: 'proxy/configuration.php',
								params: {
									task: 'GET_ALARMS',
									product: combo.getValue()
								},
					    
								success: function(response) {
									// Add the alarms in the combobox
									var alarms = Ext.decode(response.responseText).alarm;
									for (var i = 0; i < alarms.length; i++) {
										alarms.active=true;
									}
									
									var alarmCombo = Ext.getCmp('penalitiesCriteria_configChart');															
									alarmCombo.store.loadData(alarms);
									alarmCombo.store.sort('label', 'ASC');
									alarmCombo.enable(true);
									Ext.getCmp('addAlarmButton_configChart').enable(true);
								}
							});
						}
						
					},
					'change': function(combo) {
						me.checkModifications();
						if(Ext.getCmp('productCombo_configChart_ar').isDisabled() == false){
							Ext.Ajax.request({
								url: 'proxy/configuration.php',
								params: {
									task: 'GET_ALARMS',
									product: combo.getValue()
								},
					    
								success: function(response) {
									// Add the alarms in the combobox
									// Add the alarms in the combobox
									var alarms = Ext.decode(response.responseText).alarm;
									for (var i = 0; i < alarms.length; i++) {
										alarms.active=true;
									}
									
									var alarmCombo = Ext.getCmp('penalitiesCriteria_configChart');															
									alarmCombo.store.loadData(alarms);
									alarmCombo.store.sort('label', 'ASC');
									alarmCombo.enable(true);
									Ext.getCmp('addAlarmButton_configChart').enable(true);
									Ext.getCmp('addGraphTypeButton_configChart').enable(true);
								}
							});
						}
					}
				}
	        },
	        {
	            fieldLabel: 'Alarm',
	            id: 'alarmBox_configChart',
	            xtype: 'fieldcontainer',
	            layout: 'hbox',
	            items: [
	                {
			            id: 'AlarmCombo_configChart',
			            xtype: 'combobox',
			            forceSelection: true,
			            editable: false,
						disabled: true,
						flex: 3,
						margin: '0 2 0 0',
			            displayField: 'label',
			            queryMode: 'local',
		                store: Ext.create('Ext.data.Store', {
		                    fields: [
		                        {type: 'string', name: 'id'},
		                        {type: 'string', name: 'label'}
		                    ],
		                    autoLoad: false
		                }),
		                listConfig: {
		                    getInnerTpl: function() {
		                        return '<div data-qtip="{label}">{label}</div>';
		                    }
		                },
						listeners: {
							'dirtychange': function() {
								me.checkModifications();
							}
						}
			        },
	                {
	                	id: 'addAlarmButton_configChart',
	                    xtype: 'button',
	                    text: 'Add',
	    				disabled: true,
	    				flex: 1,
	                    action: 'addAlarm'
	                }
	            ]
	        },
	        {
	            xtype: 'gridpanel',
	            id: 'AlarmGrid_configChart',
	            height: 150,
	            margin: '0 0 5 0',
	            store: alarmStore,
	            columns: [
                  	{
                  		id: 'alarmLabel',
                  		header: 'Alarm',
                  		dataIndex: 'label',
                  		flex: 1                	   
                  	},
                  	{
                  		xtype: 'actioncolumn',
                  		sortable: false,
                  		width: '100%',
                  		items: [{
                  			iconCls: 'icoCancel',
                  			tooltip: 'Delete',
                            handler: function(grid, rowIndex, colIndex) {
                            	alarmStore.removeAt(rowIndex); 
                            }
                        }]
                  	}
              	],
              	listeners: {
              		'select': function(panel, record, index) {
              			// Fill and inialize the alarm fields
						Ext.getCmp('alarmField_configChart').setTitle(record.data.label);
						Ext.getCmp('alarmNameField_configChart').originalValue = record.data.grid_name;						
						Ext.getCmp('alarmNameField_configChart').setValue(record.data.grid_name);
						Ext.getCmp('alarmNameField_configChart').enable(true);
						Ext.getCmp('alarmCommentField_configChart').originalValue = record.data.comment;
						Ext.getCmp('alarmCommentField_configChart').setValue(record.data.comment);
						Ext.getCmp('alarmCommentField_configChart').enable(true);
						var dashboardRecord = Ext.getCmp('alarmDashboardCombo_configChart').findRecord('id', record.data.dashboard);
						Ext.getCmp('alarmDashboardCombo_configChart').select(dashboardRecord);
						Ext.getCmp('alarmDashboardCombo_configChart').enable(true);
					}
				}
	        },
	        {
	            xtype: 'gridpanel',
	            id: 'penalitiesCriteria_configChart',
	            height: 150,
	            margin: '0 0 5 0',
	            store: penaltiescriteriaStore,
	            columns: [
	            	{
	            		id: 'alarmId_ar',
	            		dataIndex: 'id',
	            		hidden: true,
						hideable: false
	            			
	            	},
                  	{
                  		id: 'alarmLabel_ar',
                  		header: 'Alarm',
                  		dataIndex: 'label',
                  		flex: 4                	   
                  	},
                  	
                  	{
                  		id: 'alarmCheckbox_ar',
                  		xtype: 'checkcolumn',
                  		header: 'Status',
                  		dataIndex: 'checked',
                  		flex: 1,
				        listeners: {
				            checkchange: function (column, recordIndex, checked) {
				                var store = Ext.getCmp('penalitiesCriteria_configChart').store;
				                var currentRec = store.data.items[recordIndex].data;
					            if(checked == true){
			                		checkedAlarmStore.add(currentRec);  	
					            }else{
				                	checkedAlarmStore.removeAt(recordIndex);
					            }
				                me.checkModifications(); 
				            }
				        }
                  	}
                  	
                  	/**
                  	{
                  		xtype: 'checkcolumn',
                  		sortable: false,
                  		width: '100%',
                  		items: [{
                  			tooltip: 'checkboxfield',
                            handler: function(grid, rowIndex, colIndex) {
                            	penaltiescriteriaStore.removeAt(rowIndex); 
                            }
                        }]
                  	}
                  	**/
              	],
              	listeners: {
              		'select': function(panel, record, index) {
              			// Fill and inialize the alarm fields
						Ext.getCmp('alarmField_configChart').setTitle(record.data.label);
						Ext.getCmp('alarmNameField_configChart').originalValue = record.data.grid_name;						
						Ext.getCmp('alarmNameField_configChart').setValue(record.data.grid_name);
						Ext.getCmp('alarmNameField_configChart').enable(true);
						Ext.getCmp('alarmCommentField_configChart').originalValue = record.data.comment;
						Ext.getCmp('alarmCommentField_configChart').setValue(record.data.comment);
						Ext.getCmp('alarmCommentField_configChart').enable(true);
						var dashboardRecord = Ext.getCmp('alarmDashboardCombo_configChart').findRecord('id', record.data.dashboard);
						Ext.getCmp('alarmDashboardCombo_configChart').select(dashboardRecord);
						Ext.getCmp('alarmDashboardCombo_configChart').enable(true);
					}
				}
	        }, 
	        {
	            fieldLabel: 'History',
	            id: 'HistoryDisplayed_configChart',
	            xtype: 'sliderfield',
	            minValue: 1,
	            maxValue: 10,
	            increment: 1,
	            tipText: function(thumb){
	            	return String(thumb.value);
	            },
	            listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function(field){
						field.labelEl.update('History: '+field.getValue());
						me.checkModifications();
					},
					'afterrender': function(field){
					    Ext.QuickTips.register({
					        //dismissDelay: 10000,
					        target: field.labelEl.dom,
					        text: "Number of month to display"
					    });

					    field.labelEl.dom.style.cursor = 'help';

					}
				}
	        },
	        {
				id: 'alarmField_configChart',
	        	xtype: 'fieldset',
				title: 'No alarm selected',
				defaultType: 'textfield',
				layout: 'anchor',
				defaults: {
	    			anchor: '100%'
				},
				items: [
					{
	    				fieldLabel: 'Name',
	    				id: 'alarmNameField_configChart',
	    				disabled: true,
	    				listeners: {
	    					'dirtychange': function() {
	    						me.checkModifications();
	    					},
	    					'blur': function (field) {
	    						var selection = Ext.getCmp('AlarmGrid_configChart').getSelectionModel().getSelection()[0];
	    						var record = alarmStore.findRecord('id', selection.data.id);
	    						if (record != null) {
	    							record.data.grid_name = field.getRawValue();
	    						}
	    					}
	    				}
					}, 
					{
						xtype: 'textareafield',
	    				fieldLabel: 'Comment',
	    				id: 'alarmCommentField_configChart',
	    				disabled: true,
	    				height: 45,
	    				listeners: {
	    					'dirtychange': function(field, isDirty) {
	    						me.checkModifications();
	    					},
	    					'blur': function (field) {
	    						var selection = Ext.getCmp('AlarmGrid_configChart').getSelectionModel().getSelection()[0];
	    						var record = alarmStore.findRecord('id', selection.data.id);
	    						if (record != null) {
	    							record.data.comment = field.getRawValue();
	    						}
	    					}
	    				}
					},
					{
	                    fieldLabel: 'Dashboard',
	                    id: 'alarmDashboardCombo_configChart',
	                    xtype: 'combobox',
	    	            forceSelection: true,
	    	            editable: false,
	    				disabled: true,
	    	            valueField: 'id',
	    	            displayField: 'display',
	    	            store: {
	    					fields: ['id', 'label','sdp_id','sdp_label','display','size'],
	    					proxy: {
	    				        type: 'ajax',
	    				        url : 'proxy/configuration.php',
	    				        extraParams: {
	    							task: 'GET_DASHBOARDS_ALL',
	    							product: 1
	    						},
	    						actionMethods: {
	    					        read: 'POST'
	    					    },
	    				        reader: {
	    				            type: 'json',
	    				            root: 'dashboard'
	    				        }
	    				    },
	    				    listeners:{
	    				    	'load':function(){
		    						//Ext.getCmp('alarmDashboardCombo_configChart').setSize(this.max('size')*9);
	    				    	}
	    				    },
	    				    autoLoad: true
	    	            },
	    	            listConfig: {
		                    getInnerTpl: function() {
		                        return '<div data-qtip="{display}">{display}</div>';
		                    }
		                },
	    				listeners: {
	    					'dirtychange': function(field, isDirty) {
	    						me.checkModifications();    						
	    					},
	    					'select': function (field) {
	    						var selection = Ext.getCmp('AlarmGrid_configChart').getSelectionModel().getSelection()[0];
	    						var record = alarmStore.findRecord('id', selection.data.id);
	    						if (record != null) {
	    							record.data.dashboard = field.getValue();
	    						}
	    					}
	    				}
	                }
				]
			},
			{
				id: 'mapField_configChart',
	        	xtype: 'fieldset',
				title: 'No indicators selected',
				defaultType: 'textfield',
				layout: 'anchor',
				defaults: {
	    			anchor: '100%'
				},
				items: [
				]
			},	
			{
	            fieldLabel: 'Min days',
	            id: 'alarmDayNumberfield_configChart',
	            xtype: 'sliderfield',
	            minValue: 1,
	            maxValue: 31,
	            increment: 1,
	            tipText: function(thumb){
	            	return String(thumb.value);
	            },
	            listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function(field){
						field.labelEl.update('Min days: '+field.getValue());
						me.checkModifications();
					},
					'afterrender': function(field){
					    Ext.QuickTips.register({
					        //dismissDelay: 10000,
					        target: field.labelEl.dom,
					        text: "Minimum number of days for display"
					    });

					    field.labelEl.dom.style.cursor = 'help';

					}
				}
	        },
	        {
				id: 'alltabs_configChart',
	        	xtype: 'component',
				html: 'All tabs',
				cls:'x-form-label'
			},
			{
				id: 'graphs_configChart',
	        	xtype: 'component',
				html: 'Graphs',
				cls:'x-form-label'
			},
			{
	            id: 'graphType_configChart',
	            xtype: 'fieldcontainer',
	            layout: 'hbox',
	            items: [
	                {
	                	id: 'addGraphTypeButton_configChart',
	                    xtype: 'button',
	                    text: 'Add Alarms graph',
	    				disabled: true,
	    				flex: 1,
	                    action: 'addGraphType'
	                }
	            ],
	            listeners: {
	            	click: function(){
	            		me.checkModifications();
	            	}
	            }
	        },
	        {
	            xtype: 'gridpanel',
	            id: 'graphsTable_configChart',
	            height: 150,
	            margin: '0 0 5 0',
	            store: graphsStore,
	            columns: [
                  	{
                  		id: 'alarmLabelGraphTable_ar',
                  		header: 'Graph selection',
                  		dataIndex: 'label',
                  		flex: 4                	   
                  	},
                  	{
                  		xtype: 'actioncolumn',
                  		header: 'Delete',
                  		sortable: false,
                  		flex: 1 ,
                  		width: '100%',
                  		items: [{
                  			iconCls: 'icoCancel',
                  			tooltip: 'Delete',
                            handler: function(grid, rowIndex, colIndex) {
                            	graphsStore.removeAt(rowIndex); 
                            	me.checkModifications();
                            }
                        }]
                  	}
              	],
              	listeners: {
              		'select': function(panel, record, index) {
              			var graphsStore = Ext.getStore('graphstore');
						var alarmsStore = Ext.getCmp('alarmsGrid_configChart_ar').getStore();
						alarmsStore.removeAll();
						var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
	    				var piechart = Ext.getCmp('piechart_ar');
	    				var alarmsRecord = graphsStore.findRecord('id', selection.data.id);
	    				
	    				alarms_list = alarmsRecord.data.displayed_alarms;
	    				
	    				//dot not remove last coma when only one label
	    				if(alarms_list.lastIndexOf(",")==alarms_list.length-1)
	    					alarms_list = alarms_list.substring(0,alarms_list.length - 1);
	    					
    					alarmsArray = alarms_list.split(',');
	    				
	    				//get alarms names from alarm_ids
						var graphOptions = {};
						graphOptions.sdp_id = Ext.getCmp('productCombo_configChart_ar').value;
						graphOptions.alarm_ids = alarmsArray;
						
	    				Ext.Ajax.request({
							url: 'proxy/alarm_list.php',
							params : {
								task : 'GET_ALARMS_NAMES',
								params : Ext.encode(graphOptions)
							},
							success: function(response) {
								// Add the alarms in the combobox
								var alarm_names_obj = Ext.decode(response.responseText);
				
								var alarms_array = new Array();
				
								for (var k = 0; k < alarm_names_obj.length; k++) {
								
									if(typeof(alarm_names_obj[k]) != 'undefined'){
										var obj = {
									    	id : alarm_names_obj[k].alarm_id,
											name : alarm_names_obj[k].alarm_name
									    }; 
										alarms_array.push(obj);
									}
								}
									
								for (var j = 0; j < alarms_array.length; j++) {
									alarmsStore.add({
										"id":alarms_array[j]['id'],
										"label":alarms_array[j]['name']
									});
									
								}
							}
						});
						Ext.Ajax.request({
							url: 'proxy/configuration.php',
							params: {
								task: 'GET_ALARMS',
								product: Ext.getCmp('productCombo_configChart_ar').value
							},
				    
							success: function(response) {
								// Add the alarms in the combobox
								var alarms = Ext.decode(response.responseText).alarm;
								for (var i = 0; i < alarms.length; i++) {
									alarms.active=true;
								}
								var alarmComboConfig = Ext.getCmp('AlarmCombo_configChart_ar');															
								alarmComboConfig.store.loadData(alarms);
								alarmComboConfig.store.sort('label', 'ASC');
								alarmComboConfig.enable(true);
							}
						});
						
						
						
              			// Fill and inialize the alarm fields
						Ext.getCmp('graphConfig_configChart').setTitle(record.data.label);
						//Ext.getCmp('graphTypeNameField_configChart').originalValue = record.data.id;						
						//Ext.getCmp('graphTypeNameField_configChart').setValue(record.data.id);
						Ext.getCmp('graphNameNameField_configChart').originalValue = record.data.ag_name;
						Ext.getCmp('graphNameNameField_configChart').setValue(record.data.ag_name);
						
						if(record.data.piechart == false){
							 piechart.setValue(false);
						}else{
							piechart.setValue(true);
						}
						Ext.getCmp('graphNameNameField_configChart').enable(true);
						Ext.getCmp('AlarmCombo_configChart_ar').enable(true);
						Ext.getCmp('addAlarmButton_configChart_ar').enable(true);

					}
				}
	        },
	        {
				id: 'graphConfig_configChart',
	        	xtype: 'fieldset',
				title: 'No graph selected',
				defaultType: 'textfield',
				layout: 'anchor',
				defaults: {
	    			anchor: '100%'
				},
				items: [
					/**
					{
	    				fieldLabel: 'Type',
	    				id: 'graphTypeNameField_configChart',
	    				disabled: true,
	    				listeners: {
	    					'dirtychange': function() {
	    						me.checkModifications();
	    					},
	    					'blur': function (field) {
	    						var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
	    						var record = alarmStore.findRecord('id', selection.data.id);
	    						if (record != null) {
	    						//	record.data.grid_name = field.getRawValue();
	    						}
	    					}
	    				}
					}, 
					**/
					{
	    				fieldLabel: 'Name',
	    				id: 'graphNameNameField_configChart',
	    				disabled: true,
	    				listeners: {
	    					'dirtychange': function() {
	    						me.checkModifications();
	    					},
	    					'blur': function (field) {
	    						var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
	    						var record = graphsStore.findRecord('id', selection.data.id);
	    						if (record != null) {
	    							record.data.ag_name = field.getRawValue();
	    						}
	    					}
	    				}
					}, 
					{
			            fieldLabel: 'Alarm',
			            id: 'alarmBox_configChart_ar',
			            xtype: 'fieldcontainer',
			            layout: 'hbox',
			            items: [
			                {
					            id: 'AlarmCombo_configChart_ar',
					            xtype: 'combobox',
					            forceSelection: true,
					            editable: false,
								disabled: true,
								flex: 3,
								margin: '0 2 0 0',
					            displayField: 'label',
					            queryMode: 'local',
				                store: Ext.create('Ext.data.Store', {
				                    fields: [
				                        {type: 'string', name: 'id'},
				                        {type: 'string', name: 'label'}
				                    ],
				                    autoLoad: false
				                }),
				                listConfig: {
				                    getInnerTpl: function() {
				                        return '<div data-qtip="{label}">{label}</div>';
				                    }
				                },
								listeners: {
									'dirtychange': function() {
										me.checkModifications();
									}
								}
					        },
			                {
			                	id: 'addAlarmButton_configChart_ar',
			                    xtype: 'button',
			                    text: 'Add',
			    				disabled: true,
			    				flex: 1,
			                    action: 'addAlarm_ar',
			                    listeners: {
									'click': function() {
										me.checkModifications();
									}
								}
			                }
			            ]
				     },
				     {
			            xtype: 'gridpanel',
			            id: 'alarmsGrid_configChart_ar',
			            height: 150,
			            margin: '0 0 5 0',
			            store: alarmStore_ar,
			            columns: [
		                  	{
		                  		id: 'alarmLabelGrid_ar',
		                  		header: 'Display alarms',
		                  		dataIndex: 'label',
		                  		flex: 4                	   
		                  	},
		                  	{
		                  		xtype: 'actioncolumn',
		                  		sortable: false,
		                  		header: 'Delete',
		                  		flex: 1,
		                  		items: [{
		                  			iconCls: 'icoCancel',
		                  			tooltip: 'Delete',
		                            handler: function(grid, rowIndex, colIndex) {
		                           		graphsStore = Ext.getStore('graphstore');
										var graphConf = Ext.getCmp('graphsTable_configChart');
										var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
	    								var record = graphsStore.findRecord('id', selection.data.id);
		                       	    	var alarmStore = grid.store;
		                       	    	
		                       	    	if(record != null){			
			                       	    	currentAlarmId = alarmStore.data.items[rowIndex].data.id;
			                       	    	alarmsList = record.data.displayed_alarms;
			                       	    	alarmArray = alarmsList.split(',');
												var index = alarmArray.indexOf(currentAlarmId);
												if (index > -1) {
												    alarmArray.splice(index, 1);
											}
			                       	    	newAlarmsList = alarmArray.join(',');
			                       	    	record.set('displayed_alarms',newAlarmsList);
											record.commit();
			                       	    	//record.data.displayed_alarms = newAlarmsList;
			                           		graphConf.bindStore(graphsStore);
			                           		alarmStore_ar.removeAt(rowIndex);
			                           		me.checkModifications();
		                       	    	}
		                            }
		                        }]
		                  	}
		              	]
		              
			        },
			        {
	        	 		xtype: 'fieldcontainer',
			            fieldLabel: 'Piechart',
			            defaultType: 'checkboxfield',
			            items: [{
			                name: 'piechart',
			                inputValue: '1',
			                id: 'piechart_ar',
			                handler: function (field, value) {
			                	var checked = field.getValue();
                				graphsStore = Ext.getStore('graphstore');
                				var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
								var record = graphsStore.findRecord('id', selection.data.id);
								record.set('piechart',checked);
								me.checkModifications();
			                }
			            }]
			        }
				]
			},
			{
				id: 'alarmPenalizationMode_configChart',
				fieldLabel: 'Mode',
				xtype: 'radiogroup',
           		fieldLabel: 'Penalization mode',
           			items: [{
           			id: 'alarmPenalizationModeRatio_configChart',
           			boxLabel: 'Ratio',
           			name: 'radioPenalizationMode',
           			//1 for ratio
           			inputValue: '1',
	       			listeners: {
                		change : function(cb, value,ov) {
                          if (value ) {
                           		Ext.getCmp('alarmNbDaysNumberfield_configChart').hide();
                            Ext.getCmp('alarmRatioNumberfield_configChart').show();
                          }	
                     	 }
	                }
           			},{
           			id: 'alarmPenalizationModeNbdays_configChart',
		            boxLabel: 'Days', 
		            name: 'radioPenalizationMode', 
		            //2 for number of days
		            inputValue: '2', 
		            checked: true,
		            listeners: {
                              change : function(cb, value,ov) {
                                if (value){
                                  Ext.getCmp('alarmNbDaysNumberfield_configChart').show();
                                  Ext.getCmp('alarmRatioNumberfield_configChart').hide();
                                } 
                               }
                        }
		            }]
		                
			},
			{
				id: 'alarmRatioNumberfield_configChart',
				fieldLabel: 'Ratio',
	            xtype: 'sliderfield',
	            //vtype: 'ratioNumber',
	            minValue: 0,
	            maxValue:100,
	            //decimalPrecision: 0,
	            //step:1,
	            increment: 1,
	            tipText: function(thumb){
	            	return String(thumb.value) + '%';
	            },
	            listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function(field){
						field.labelEl.update('Ratio: '+field.getValue()+'%');
						me.checkModifications();
					},
					'afterrender': function(field){
					    Ext.QuickTips.register({
					        //dismissDelay: 10000,
					        target: field.labelEl.dom,
					        text: "Ratio for penalization"
					    });

					    field.labelEl.dom.style.cursor = 'help';

					}
				}
	        },
	        {
				id: 'alarmNbDaysNumberfield_configChart',
				fieldLabel: 'Nb days',
	            xtype: 'sliderfield',
	            minValue: 0,
	            maxValue:31,
	            increment: 1,
	            /**
	            tipText: function(thumb){
	            	return String(thumb.value) + '%';
	            },
	            **/
	            listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function(field){
						field.labelEl.update('Nb days: '+field.getValue());
						me.checkModifications();
					},
					'afterrender': function(field){
					    Ext.QuickTips.register({
					        //dismissDelay: 10000,
					        target: field.labelEl.dom,
					        text: "Number of days"
					    });

					    field.labelEl.dom.style.cursor = 'help';

					}
				}
	        },
			{
	            fieldLabel: '1st network ID',
	            id: 'neContainer_configChart',
	            xtype: 'fieldcontainer',
	            layout: 'hbox',
	            defaults: {
	                hideLabel: true,
	                margin: '0 2 0 0'
	            },
	            items: [
	                {
	                	id: 'neButton_configChart',
	                    xtype: 'button',
	                    width: 16,
	                    cls: 'x-button-network-select',
	                    action: 'selectNetwork'
	                },
	                {
	                	id: 'neCancelButton_configChart',
	                    xtype: 'button',	                    
	                    width: 16,
	                    cls: 'icoCancel',
	                    action: 'neCancel'
	                }
	            ]
	        },
	        {
	            xtype: 'gridpanel',
	            id: 'neGrid_configChart',
	            height: 150,
	            margin: '0 0 5 0',
	            store: neStore,
	            columns: [
                  	{
                  		id: 'label',
                  		header: 'Label',
                  		dataIndex: 'label',
                  		flex: 2                	   
                  	},
                  	{
                  		id: 'level',
                  		header: 'Level',
                  		dataIndex: 'level',
                  		flex: 1                	   
                  	},
                  	{
                  		xtype: 'actioncolumn',
                  		sortable: false,
                  		width: 20,
                  		items: [{
                  			iconCls: 'icoCancel',
                  			tooltip: 'Delete',
                            handler: function(grid, rowIndex, colIndex) {
                            	// Get network values
                            	var neId = Ext.getCmp('neId_configChart').getValue();
                            	var neIds = neId.split('||');
                            	neIds.shift();
                            	neIds.splice(rowIndex, 1);
                            	var newValue = neIds.length > 0 ? '||' + neIds.join('||') : '';
                            	Ext.getCmp('neId_configChart').setValue(newValue);
                            	                            	
                            	var neLevelId = Ext.getCmp('neLevelId_configChart').getValue();
                            	var neLevelIds = neLevelId.split('||');
                            	neLevelIds.shift();
                            	neLevelIds.splice(rowIndex, 1);
                            	newValue = neLevelIds.length > 0 ? '||' + neLevelIds.join('||') : '';
                            	Ext.getCmp('neLevelId_configChart').setValue(newValue);
                            	
                            	var neProductId = Ext.getCmp('neProductId_configChart').getValue();
                            	var neProductIds = neProductId.split('||');
                            	neProductIds.shift();
                            	neProductIds.splice(rowIndex, 1);
                            	newValue = neProductIds.length > 0 ? '||' + neProductIds.join('||') : '';
                            	Ext.getCmp('neProductId_configChart').setValue(newValue);
                            	
                            	var neLabel = Ext.getCmp('neLabel_configChart').getValue();
                            	var neLabels = neLabel.split('||');
                            	neLabels.shift();
                            	neLabels.splice(rowIndex, 1);
                            	newValue = neLabels.length > 0 ? '||' + neLabels.join('||') : '';
                            	Ext.getCmp('neLabel_configChart').setValue(newValue);
                            	
                            	var neLevelLabel = Ext.getCmp('neLevelLabel_configChart').getValue();
                            	var neLevelLabels = neLevelLabel.split('||');
                            	neLevelLabels.shift();
                            	neLevelLabels.splice(rowIndex, 1);
                            	newValue = neLevelLabels.length > 0 ? '||' + neLevelLabels.join('||') : '';
                            	Ext.getCmp('neLevelLabel_configChart').setValue(newValue);
                            	
                            	neStore.removeAt(rowIndex); 
                            }
                        }]
                  	}
              	]
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neId_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}      	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neLevelId_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}        	
	        },
	        
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neProductId_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}	        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neLabel_configChart'
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neLevelLabel_configChart'
	        },
	        {
	            fieldLabel: '2nd network ID',
	            id: 'ne2Container_configChart',
	            xtype: 'fieldcontainer',
	            layout: 'hbox',
	            defaults: {
	                hideLabel: true,
	                margin: '0 2 0 0'
	            },
	            items: [
	                {
	                	id: 'neButton2_configChart',
	                    xtype: 'button',
	                    width: 16,
	                    cls: 'x-button-network-select',
	                    action: 'selectNetwork2'
	                },
	                {
	                	id: 'neCancelButton2_configChart',
	                    xtype: 'button',	                    
	                    width: 18,
	                    cls: 'icoCancel',
	                    action: 'neCancel2'
	                }
	            ]
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neId2_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}      	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neLevelId2_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neProductId2_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}	        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neLabel2_configChart'
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'neLevelLabel2_configChart'
	        },
			{
	            fieldLabel: 'Raw / KPI',
	            xtype: 'fieldcontainer',
	            id: 'counterContainer_configChart',
	            layout: 'hbox',
	            defaults: {
	            	hideLabel: true,
	                margin: '0 2 0 0'
	            },
	            items: [
	                {
	                	id: 'counterButton_configChart',
	                	xtype: 'button',
	                    width: 16,
	                    cls: 'x-button-counter-select',
	                    action: 'selectCounter'
	                },
	                {
	                	id: 'counterCancelButton_configChart',
	                    xtype: 'button',
	                    width: 18,
	                    cls: 'icoCancel',
	                    action: 'counterCancel'
	                }
	            ]
	        },
	        {
	            xtype: 'gridpanel',
	            id: 'mapKpiGrid_configChart',
	            multiSelect: true,
	            viewConfig: {
	                plugins: {
	                    ptype: 'gridviewdragdrop',
	                    dragGroup: 'firstGridDDGroup',
	                    dropGroup: 'firstGridDDGroup'
	                },
	            },
	            height: 150,
	            margin: '0 0 5 0',
	            enableColumnHide: false,
	            layout: 'fit',
	            store:{ 
	            	model: 'MapModel',
	            	proxy: {
				        type: 'ajax',
				        url : 'proxy/configuration.php',
				        extraParams: {
							task: 'GET_MAP_CONF',
							tab: currentTab,
						},
						actionMethods: {
					        read: 'POST'
					    },
				        reader: {
				            type: 'json',
				            root: ''
				        }
				    },
				    autoDestroy: true,
				    autoLoad: true,
	            },
	            columns: [
                   {
						id: 'groupname',
						header: 'Group Name',
						dataIndex: 'groupname',
						flex: 1, 
						hidden: true,
					},
                  	{
                  		id: 'trendKpiLabel',
                  		header: 'Trend KPI',
                  		dataIndex: 'trendkpilabel',
                  		flex: 1,
                  		sortable: false,
                  	},
                  	{
                  		id: 'trendProductLabel',
                  		header: 'Trend Product Label',
                  		dataIndex: 'trendproductlabel',
                  		flex: 1,
                  		hidden: true,
                  	},
                  	{
                  		id: 'donutKpiLabel',
                  		header: 'Donut KPI',
                  		dataIndex: 'donutkpilabel',
                  		flex: 1,
                  		sortable: false,
                  	},
                  	{
                  		id: 'donutProductLabel',
                  		header: 'Donut Product Label',
                  		dataIndex: 'donutproductlabel',
                  		flex: 1,
                  		hidden: true,
                  	},

              	],
              	tbar: [{
                    text: 'Add',
                    iconCls: 'kpis-add',
                    scope: this,
                    handler : function() {
                    	//get selected values for each kpi (donut and trend)
                    	var kpiIdTrend=Ext.getCmp('trendCounterId_configChart').getValue();
                    	var kpiProductLabelTrend=Ext.getCmp('trendCounterProductLabel_configChart').getValue();
                    	var kpiProductIdTrend=Ext.getCmp('trendCounterProductId_configChart').getValue();
                    	var kpiLabelTrend=Ext.getCmp('trendCounterLabel_configChart').getValue();
                    	var kpiTypeTrend=Ext.getCmp('trendCounterType_configChart').getValue();

                    	var kpiIdDonut=(Ext.getCmp('counterId_configChart').getValue().indexOf("||")==-1 ? Ext.getCmp('counterId_configChart').getValue() : Ext.getCmp('counterId_configChart').getValue().split('||')[1]);
                    	var kpiProductLabelDonut=(Ext.getCmp('counterProductLabel_configChart').getValue().indexOf("||")==-1 ? Ext.getCmp('counterProductLabel_configChart').getValue() : Ext.getCmp('counterProductLabel_configChart').getValue().split('||')[1]);
                    	var kpiProductIdDonut=(Ext.getCmp('counterProductId_configChart').getValue().indexOf("||")==-1 ? Ext.getCmp('counterProductId_configChart').getValue() : Ext.getCmp('counterProductId_configChart').getValue().split('||')[1]);
                    	var kpiLabelDonut=(Ext.getCmp('counterLabel_configChart').getValue().indexOf("||")==-1 ? Ext.getCmp('counterLabel_configChart').getValue() : Ext.getCmp('counterLabel_configChart').getValue().split('||')[1]);
                    	var kpiTypeDonut=(Ext.getCmp('counterType_configChart').getValue().indexOf("||")==-1 ? Ext.getCmp('counterType_configChart').getValue() : Ext.getCmp('counterType_configChart').getValue().split('||')[1]);
                    	
                    	//be sure to have kpis selected
                    	if(Ext.getCmp('mapMode').getValue().modeselection=="2"){
                    		if(kpiIdTrend==""){
                    			Ext.Msg.alert('Warning', 'Please choose indicator before adding a group.');
                        		return false;
                    		}
                    	}
                    	else{
                    		if(kpiIdTrend=="" || kpiIdDonut==""){
                        		Ext.Msg.alert('Warning', 'Please choose indicators before adding a group.');
                        		return false;
                        	}
                    	}
                    	
                    	// Create a model instance
                        var r = Ext.create('MapModel', {
                        	trendkpilabel: kpiLabelTrend,
                        	trendkpiid: kpiIdTrend,
                        	trendkpiproductid: kpiProductIdTrend,
                        	trendproductlabel: kpiProductLabelTrend,
                        	typekpi: kpiTypeTrend,
                        	donutkpilabel: kpiLabelDonut,
                        	donutkpiid: kpiIdDonut,
                        	donutkpiproductid: kpiProductIdDonut,
                        	donutproductlabel: kpiProductLabelDonut,
                        	typekpidonut: kpiTypeDonut,
                        	networkaxisnumber: "",
                			roamingnetworklevel:"",
                			roamingnetworklevel2:"",
               				roamingneid:"",
               				roamingneid2:""
                        });
              
                        store=Ext.getCmp('mapKpiGrid_configChart').getStore();
                        
                        var storeindex = store.getCount();   
                       // mapStore.insert(storeindex, r);
                                          
                        store.insert(storeindex, r);
                        
                        //position onto the new record
                        var sm = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel();
                        if (store.getCount() > 0) {
                            sm.select(store.getCount()-1);
                        }
                        
                        Ext.getCmp('mapKpiGrid_configChart').down('#removeKpi').setDisabled(false);
                        
                        //reset kpi selectors
                		Ext.getCmp('trendCounterId_configChart').setValue('');
                		//Ext.getCmp('trendCounterId_configChart').originalValue('');
                    	Ext.getCmp('trendCounterType_configChart').setValue('');
                    	//Ext.getCmp('trendCounterType_configChart').originalValue('');
                    	Ext.getCmp('trendCounterProductId_configChart').setValue('');
                    	//Ext.getCmp('trendCounterProductId_configChart').originalValue('');  
                    	Ext.getCmp('trendCounterProductLabel_configChart').setValue('');
                    	//Ext.getCmp('trendCounterProductLabel_configChart').originalValue('');
                    	Ext.getCmp('trendCounterLabel_configChart').setValue('');
                    	//Ext.getCmp('trendCounterLabel_configChart').originalValue('');
                    
                    	// Change the button aspect
                    	var counterButton = Ext.getCmp('trendCounterButton_configChart');
                		counterButton.removeCls('x-button-counter-select-ok');
                		counterButton.addCls('x-button-counter-select');
                		counterButton.setTooltip('');    
                    	
                    	//reset values
                		Ext.getCmp('counterId_configChart').setValue('');
                		//Ext.getCmp('counterId_configChart').originalValue('');
                    	Ext.getCmp('counterType_configChart').setValue('');
                    	//Ext.getCmp('counterType_configChart').originalValue('');
                    	Ext.getCmp('counterProductId_configChart').setValue('');
                    	//Ext.getCmp('counterProductId_configChart').originalValue('');
                    	Ext.getCmp('counterProductLabel_configChart').setValue('');
                    	//Ext.getCmp('counterProductLabel_configChart').originalValue('');
                    	Ext.getCmp('counterLabel_configChart').setValue('');
                    	//Ext.getCmp('counterLabel_configChart').originalValue('');
                    
                    	// Change the button aspect
                    	var counterButton = Ext.getCmp('counterButton_configChart');
                		counterButton.removeCls('x-button-counter-select-ok');
                		counterButton.addCls('x-button-counter-select');
                		counterButton.setTooltip('');    	
                        
                    }
                },
                {
                    itemId: 'removeKpi',
                    text: 'Remove',
                    iconCls: 'kpis-remove',
                    scope: this,
                    handler: function() { 
                        var sm = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel();
                        store=Ext.getCmp('mapKpiGrid_configChart').getStore();
                        store.remove(sm.getSelection());
                        if (store.getCount() > 0) {
                            sm.select(store.getCount()-1);
                        }
                        else{
                        	Ext.getCmp('mapKpiGrid_configChart').down('#removeKpi').setDisabled(true);
                        	Ext.getCmp('mapField_configChart').query('.combobox,.textfield,.checkbox').forEach(function(c){c.setDisabled(true);});
                        }
                    },
                    disabled: true
                }],
                listeners: {
//                    'selectionchange': function(view, records) {
//                    	Ext.getCmp('mapKpiGrid_configChart').down('#removeKpi').setDisabled(!records.length);
//                    	
//                    	Ext.getCmp('mapField_configChart').query('.combobox,.textfield,.checkbox').forEach(function(c){c.setDisabled(!records.length);});
//                    	
//                    	Ext.getCmp('scaleMinField_configChart').setDisabled(records[0].data.dynamic);
//		        		Ext.getCmp('scaleMaxField_configChart').setDisabled(records[0].data.dynamic);
//                    },
			        'select': function(panel, record, index) {
			        	Ext.getCmp('mapField_configChart').query('.combobox,.textfield,.checkbox').forEach(function(c){c.setDisabled(false);});
	
			        	Ext.getCmp('mapKpiGrid_configChart').down('#removeKpi').setDisabled(record.length);
			        	
//			        	//set tooltip for kpi selectors
//			        	
//			        	var trendCounterButton=Ext.getCmp('trendCounterButton_configChart');
//			        	
//			        	if (record.data.trendkpiid != '') {
//			        		trendCounterButton.addCls('x-button-counter-select-ok');
//			        		trendCounterButton.removeCls('x-button-counter-select');
//							
//			        		Ext.getCmp('trendCounterProductLabel_configChart').setValue(record.data.trendproductlabel);
//	                    	Ext.getCmp('trendCounterProductId_configChart').setValue(record.data.trendkpiproductid);
//	                    	Ext.getCmp('trendCounterLabel_configChart').setValue(record.data.trendkpilabel);
//	                    	Ext.getCmp('trendCounterType_configChart').setValue(record.data.typekpi);
//			        		
//			        		trendCounterButton.setTooltip(record.data.trendproductlabel
//									+ ' - ' + record.data.trendkpilabel);
//						} else {
//							trendCounterButton
//									.removeCls('x-button-counter-select-ok');
//							trendCounterButton.addCls('x-button-counter-select');
//							trendCounterButton.setTooltip('');
//						}
//			        	
//			        	
//			        	var counterButton=Ext.getCmp('counterButton_configChart');
//			        	
//			        	if (record.data.donutkpiid != '') {
//			        		counterButton.addCls('x-button-counter-select-ok');
//			        		counterButton.removeCls('x-button-counter-select');
//			        		
//			        		Ext.getCmp('counterProductLabel_configChart').setValue(record.data.donutproductlabel);
//	                    	Ext.getCmp('counterProductId_configChart').setValue(record.data.donutkpiproductid);
//	                    	Ext.getCmp('counterLabel_configChart').setValue(record.data.donutkpilabel);
//	                    	Ext.getCmp('counterType_configChart').setValue(record.data.typekpidonut);
//			        		
//			        		counterButton.setTooltip(record.data.donutproductlabel
//									+ ' - ' + record.data.donutkpilabel);
//						} else {
//							counterButton
//									.removeCls('x-button-counter-select-ok');
//							counterButton.addCls('x-button-counter-select');
//							counterButton.setTooltip('');
//						}
			        	
			        	
			  			// Fill and inialize the alarm fields 	
			        	Ext.getCmp('mapField_configChart').originalValue = record.data.groupname;
			        	Ext.getCmp('mapField_configChart').setTitle(record.data.groupname);
			        	Ext.getCmp('titleField_configChart').originalValue = record.data.groupname;
			        	Ext.getCmp('titleField_configChart').setValue(record.data.groupname);
			        	Ext.getCmp('typeCombo_configChart').originalValue = record.data.trendkpifunction;
			        	Ext.getCmp('typeCombo_configChart').setValue(record.data.trendkpifunction);
			        	Ext.getCmp('trendUnitField_configChart').originalValue = record.data.trendunit;
			        	Ext.getCmp('trendUnitField_configChart').setValue(record.data.trendunit);
			        	Ext.getCmp('unitField_configChart').originalValue = record.data.donutunit;
			        	Ext.getCmp('unitField_configChart').setValue(record.data.donutunit);
			        	Ext.getCmp('thresholdMinField_configChart').originalValue = record.data.lowthreshold;
			        	Ext.getCmp('thresholdMinField_configChart').setValue(record.data.lowthreshold);
			        	Ext.getCmp('thresholdMaxField_configChart').originalValue = record.data.highthreshold;
			        	Ext.getCmp('thresholdMaxField_configChart').setValue(record.data.highthreshold);
			        	Ext.getCmp('dynamicBox_configChart').originalValue = record.data.dynamic;
			        	Ext.getCmp('dynamicBox_configChart').setValue(record.data.dynamic);
		        			
	        			Ext.getCmp('scaleMinField_configChart').originalValue = record.data.minvalue;
		        		Ext.getCmp('scaleMinField_configChart').setValue(record.data.minvalue);
			        	Ext.getCmp('scaleMaxField_configChart').originalValue = record.data.maxvalue;
		        		Ext.getCmp('scaleMaxField_configChart').setValue(record.data.maxvalue);	
		        				
		        		Ext.getCmp('scaleMinField_configChart').setDisabled(record.data.dynamic);
		        		Ext.getCmp('scaleMaxField_configChart').setDisabled(record.data.dynamic);
				    }
                }
	        },
	        {
	            xtype: 'gridpanel',
	            id: 'counterGrid_configChart',
	            height: 150,
	            margin: '0 0 5 0',
	            store: counterStore,
	            columns: [
                  	{
                  		id: 'counter',
                  		header: 'Raw / KPI',
                  		dataIndex: 'label',
                  		flex: 1                	   
                  	},
                  	{
                  		xtype: 'actioncolumn',
                  		sortable: false,
                  		width: 20,
                  		items: [{
                  			iconCls: 'icoCancel',
                  			tooltip: 'Delete',
                            handler: function(grid, rowIndex, colIndex) {
                            	// Get counter values
                            	var counterId = Ext.getCmp('counterId_configChart').getValue();
                            	var counterIds = counterId.split('||');
                            	counterIds.shift();
                            	counterIds.splice(rowIndex, 1);
                            	var newValue = counterIds.length > 0 ? '||' + counterIds.join('||') : '';
                            	Ext.getCmp('counterId_configChart').setValue(newValue);
                            	                            	
                            	var counterType = Ext.getCmp('counterType_configChart').getValue();
                            	var counterTypes = counterType.split('||');
                            	counterTypes.shift();
                            	counterTypes.splice(rowIndex, 1);
                            	newValue = counterTypes.length > 0 ? '||' + counterTypes.join('||') : '';
                            	Ext.getCmp('counterType_configChart').setValue(newValue);
                            	
                            	var counterProductId = Ext.getCmp('counterProductId_configChart').getValue();
                            	var counterProductIds = counterProductId.split('||');
                            	counterProductIds.shift();
                            	counterProductIds.splice(rowIndex, 1);
                            	newValue = counterProductIds.length > 0 ? '||' + counterProductIds.join('||') : '';
                            	Ext.getCmp('counterProductId_configChart').setValue(newValue);
                            	
                            	var counterProductLabel = Ext.getCmp('counterProductLabel_configChart').getValue();
                            	var counterProductLabels = counterProductLabel.split('||');
                            	counterProductLabels.shift();
                            	counterProductLabels.splice(rowIndex, 1);
                            	newValue = counterProductLabels.length > 0 ? '||' + counterProductLabels.join('||') : '';
                            	Ext.getCmp('counterProductLabel_configChart').setValue(newValue);
                            	
                            	var counterLabel = Ext.getCmp('counterLabel_configChart').getValue();
                            	var counterLabels = counterLabel.split('||');
                            	counterLabels.shift();
                            	counterLabels.splice(rowIndex, 1);
                            	newValue = counterLabels.length > 0 ? '||' + counterLabels.join('||') : '';
                            	Ext.getCmp('counterLabel_configChart').setValue(newValue);
                            	
                            	counterStore.removeAt(rowIndex); 
                            }
                        }]
                  	}
              	]
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'counterId_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}	        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'counterType_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}	        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'counterProductId_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'counterProductLabel_configChart'
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'counterLabel_configChart'
	        },
			{
	            fieldLabel: 'Type',
	            id: 'typeCombo_configChart',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            store: [
	                ['success', 'Success %'],
	                ['failure', 'Failure %'],
					['other', 'Other']
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function (field,newval,oldval) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
							if (record != null) {
								record.data.trendkpifunction = newval;
							}
						}	
					}
				}
	        },
			{
	            fieldLabel: 'Unit',
	            id: 'unitField_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'blur': function (field) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
							if (record != null) {
								record.data.donutunit = field.getRawValue();
							}
						}	
					}
				}
	        },
			{
	            xtype: 'numberfield',
	            hideTrigger: true,
				fieldLabel: 'Threshold min',
	            id: 'thresholdMinField_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'blur': function (field) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
							if (record != null) {
								record.data.lowthreshold = field.getRawValue();
							}
						}	
					}
				}
	        },
	        {
	            xtype: 'numberfield',
	            hideTrigger: true,
	            fieldLabel: 'Threshold max',
	            id: 'thresholdMaxField_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'blur': function (field) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
							if (record != null) {
								record.data.highthreshold = field.getRawValue();
							}
						}	
					}
				}
	        },
			{
	        	boxLabel: 'Dynamic scale',
	    		id: 'dynamicBox_configChart',
	    		xtype: 'checkbox',	    		
	    		hideLabel: true,
	    		checked: true,
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function (field,newval,oldval) {
						Ext.getCmp('scaleMinField_configChart').setDisabled(newval);
		        		Ext.getCmp('scaleMaxField_configChart').setDisabled(newval);
						
		        		if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
		        			var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
		
							record=record.items[0];
							if (record != null) {
								record.data.dynamic = newval;
							}
		        		}	
					}	
				},	
	    		handler: function(me, checked) {
	        		//var configChart = me.ownerCt;
	        		
	        		var configChart=Ext.getCmp('configChart');

	        		var field = configChart.down('numberfield[id="scaleMinField_' + configChart.id + '"]');
	        		//var field = configChart.down('numberfield[id="scaleMinField_configChart"]');
	        		field.setDisabled(checked);
	
	        		var field = configChart.down('numberfield[id="scaleMaxField_' + configChart.id + '"]');
	        		//var field = configChart.down('numberfield[id="scaleMaxField_configChart"]');
	        		field.setDisabled(checked);                        		
	        	}
			},
			{
				xtype: 'numberfield',
				hideTrigger: true,
	            fieldLabel: 'Scale min',
				id: 'scaleMinField_configChart',
				disabled: true,
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'blur': function (field) {
						
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
							if (record != null) {
								record.data.minvalue = field.getRawValue();
							}
						}	
					}	
				}
	        },
			{
				xtype: 'numberfield',
				hideTrigger: true,
	            fieldLabel: 'Scale max',
				id: 'scaleMaxField_configChart',
				disabled: true,
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'blur': function (field) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
							if (record != null) {
								record.data.maxvalue = field.getRawValue();
							}
						}	
					}	
				}
	        },
	
			// Gauge configuration
			{	
				id: 'gaugeTitle_configChart',
				xtype: 'component', 
				html: 'Chart', 
				cls:'x-form-label'
			},
			{
				fieldLabel: 'Time level',
				id: 'timeUnitCombo_configChart',
	            xtype: 'combobox',
	            forceSelection: true,
				editable: false,
				store: [
					['hour', 'Hour'],
					['day', 'Day'],
					['week', 'Week'],
					['month', 'Month'],
					['day_bh', 'Day BH'],
					['month_bh', 'Month BH'],
					['week_bh', 'Week BH'],
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function(combo, newValue, oldValue) {
						if (newValue == 'hour') {
							Ext.getCmp('time_configChart').setDisabled(false);
						} else {
							Ext.getCmp('time_configChart').setDisabled(true);
						}
					}
				},
	        },
	        {
	            xtype: 'datefield',
	            id: 'date_configChart',
	            fieldLabel: 'Date',
	            format: 'Y/m/d',
	            maxValue: new Date()  // limited to the current date or prior
	        },
	        {
	            xtype: 'timefield',
	            id: 'time_configChart',
	            fieldLabel: 'Hour',
	            increment: 60
	        },
			{
				id: 'labelField_configChart',
	        	xtype: 'fieldset',
				title: 'Display labels',
				defaultType: 'checkbox',
				layout: 'anchor',
				defaults: {
	    			anchor: '100%'
				},
				items: [
					{
	    				boxLabel: 'Date',
	    				id: 'dateDisplayBox_configChart',
	    				listeners: {
	    					'dirtychange': function() {
	    						me.checkModifications();
	    					}
	    				}
					}, 
					{
	    				boxLabel: 'Network element',
	    				id: 'neDisplayBox_configChart',
	    				listeners: {
	    					'dirtychange': function() {
	    						me.checkModifications();
	    					}
	    				}
					},
					{
	                    boxLabel: 'Raw / KPI counter',
	                    id: 'counterDisplayBox_configChart',
	    				listeners: {
	    					'dirtychange': function() {
	    						me.checkModifications();
	    					}
	    				}
	                }
				]
			},
	
			// Details configuration
			{
				id: 'detailsTitle_configChart',
	            xtype: 'component',
	            html: 'Trend',
	            cls:'x-form-label'
	        },
			{
	            fieldLabel: 'Time level',
	            id: 'trendTimeUnitCombo_configChart',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            store: [
					['hour', 'Hour'],
					['day', 'Day'],
					['week', 'Week'],
					['month', 'Month'],
					['day_bh', 'Day BH'],
					['month_bh', 'Month BH'],
					['week_bh', 'Week BH'],
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}
	        },
			{
	        	xtype: 'numberfield',
	        	hideTrigger: true,
	            fieldLabel: 'Number of period',
	            id: 'trendPeriodField_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}
	        },
			{
	            fieldLabel: 'Raw / KPI',
	            id: 'trendCounterContainer_configChart',
	            xtype: 'fieldcontainer',
	            layout: 'hbox',
	            defaults: {
	            	hideLabel: true,
	                margin: '0 2 0 0'
	            },
	            items: [
	                {
	                	id: 'trendCounterButton_configChart',
	                	xtype: 'button',
	                    width: 16,
	                    cls: 'x-button-counter-select',
	                    action: 'selectCounterTrend'
	                },
	                {
	                	id: 'trendCounterCancelButton_configChart',
	                    xtype: 'button',	                    
	                    width: 18,
	                    cls: 'icoCancel',
	                    action: 'trendCounterCancel'
	                }
	            ]
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'trendCounterId_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}      	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'trendCounterType_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}	        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'trendCounterProductId_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}        	
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'trendCounterProductLabel_configChart'
	        },
	        {
	        	xtype: 'hiddenfield',
	        	id: 'trendCounterLabel_configChart'
	        },
			{
                fieldLabel: 'Unit',
                id: 'trendUnitField_configChart',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'blur': function (field) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							var selection = Ext.getCmp('mapKpiGrid_configChart').getSelectionModel().getSelection()[0];
							var record=Ext.getCmp('mapKpiGrid_configChart').store.queryBy(function(record,id){
							     return (record.get('trendkpiid') == selection.data.trendkpiid && record.get('donutkpiid') == selection.data.donutkpiid);
							});
							
							record=record.items[0];
							if (record != null) {
								record.data.trendunit = field.getRawValue();
							}
						}	
					}
				}
	        }
	    ];
	
        me.callParent(arguments);
    },
    
    checkModifications: function() {
		var me = this;
				
		var dirty = Ext.getCmp('titleField_configChart').isDirty() || 
			Ext.getCmp('urlField_configChart').isDirty() ||
			Ext.getCmp('neId_configChart').isDirty() ||
			Ext.getCmp('neLevelId_configChart').isDirty() ||
			Ext.getCmp('neProductId_configChart').isDirty() ||
			Ext.getCmp('counterType_configChart').isDirty() ||
			Ext.getCmp('counterProductId_configChart').isDirty() ||
			Ext.getCmp('typeCombo_configChart').isDirty() ||
			Ext.getCmp('unitField_configChart').isDirty() ||
			Ext.getCmp('thresholdMinField_configChart').isDirty() ||
			Ext.getCmp('thresholdMaxField_configChart').isDirty() ||
			Ext.getCmp('dynamicBox_configChart').isDirty() ||
			Ext.getCmp('scaleMinField_configChart').isDirty() ||
			Ext.getCmp('scaleMaxField_configChart').isDirty() ||
			Ext.getCmp('date_configChart').isDirty() || 
			Ext.getCmp('time_configChart').isDirty() ||
			Ext.getCmp('timeUnitCombo_configChart').isDirty() ||
			Ext.getCmp('dateDisplayBox_configChart').isDirty() ||
			Ext.getCmp('neDisplayBox_configChart').isDirty() ||
			Ext.getCmp('counterDisplayBox_configChart').isDirty() ||
			Ext.getCmp('trendTimeUnitCombo_configChart').isDirty() ||
			Ext.getCmp('trendPeriodField_configChart').isDirty() ||
			Ext.getCmp('trendCounterId_configChart').isDirty() ||
			Ext.getCmp('trendCounterType_configChart').isDirty() ||
			Ext.getCmp('trendCounterProductId_configChart').isDirty() ||
			Ext.getCmp('trendUnitField_configChart').isDirty() ||
			Ext.getCmp('alarmDayNumberfield_configChart').isDirty() ||
			Ext.getCmp('alarmRatioNumberfield_configChart').isDirty() ||
			Ext.getCmp('alarmDashboardCombo_configChart').isDirty() ||
			Ext.getCmp('alarmCommentField_configChart').isDirty() ||
			Ext.getCmp('alarmNameField_configChart').isDirty() ||
			Ext.getCmp('HistoryDisplayed_configChart').isDirty()||
			Ext.getCmp('graphNameNameField_configChart').isDirty();
		
		if (dirty) {
			me.modified = true;
			//me.setTitle('Graph *');
			me.title=me.title.replace(/\s\*/g,"");
			me.setTitle(me.title+' *');
		} else {
			me.modified = false;
			//me.setTitle('Graph');
			me.title=me.title.replace(/\s\*/g,"");
			me.setTitle(me.title);
		}
	}
});

