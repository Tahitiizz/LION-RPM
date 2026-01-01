Ext.define('homepage.view.configuration.ConfigMapAssociation' ,{
	extend: 'Ext.form.Panel',
    alias : 'widget.configmapassociation',
    
    iconCls: 'icoTabEdit',
	bodyStyle:'padding:5px 5px 0',
	defaultType: 'textfield',
    defaults: {
    	anchor: '100%'
    },
    
    title: 'Indicators configuration',
    modified: false,
	autoScroll: true,
	labelWidth: 200,
    initComponent: function() {
    	var me = this;
		var currentTab = Ext.getCmp('tabPanel').getActiveTab() == 0 ? 0 : Ext.getCmp('tabPanel').getActiveTab().getId();
		me.items = [
				{
					fieldLabel: 'Select an indicator',
					id: 'trendCounterContainer_configChart_roaming',
					xtype: 'fieldcontainer',
					layout: 'hbox',
					labelWidth: 130,
					defaults: {
						hideLabel: true,
						margin: '0 2 0 0'
					},
					items: [
						{
							id: 'trendCounterButton_configChart_roaming',
							xtype: 'button',
							width: 16,
							cls: 'x-button-counter-select',
							action: 'selectCounterTrendRoaming'
						},
						{
							id: 'trendCounterCancelButton_configChart_roaming',
							xtype: 'button',	                    
							width: 18,
							cls: 'icoCancel',
							action: 'trendCounterCancelRoaming'
						}
					]
				},
				{
		            xtype: 'gridpanel',
		            id: 'mapKpiGrid_configChart_roaming',
		            multiSelect: true,
		            viewConfig: {
		                plugins: {
		                    ptype: 'gridviewdragdrop',
		                    dragGroup: 'firstGridDDGroup',
		                    dropGroup: 'firstGridDDGroup'
		                }
		            },
		            height: 150,
		            margin: '0 0 5 0',
		            enableColumnHide: false,
		            layout: 'fit',
		            store:{ 
		            	id:'mapStore_roaming',
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
	                  		id: 'trendKpiLabel_roaming',
	                  		header: 'Selected indicator',
	                  		dataIndex: 'trendkpilabel',
	                  		flex: 3,
	                  		sortable: false
	                  	},
	                  	{
                  		xtype: 'actioncolumn',
                  		sortable: false,
                  		flex: 1,
                  		items: [{
                  			iconCls: 'icoCancel',
                  			tooltip: 'Delete',
                            handler: function(grid, rowIndex, colIndex) {
                            	
                            	if(Ext.getCmp('mapKpiGrid_configChart_roaming').getStore().data.items.length == rowIndex+1){
                            		var addKpiButton = Ext.getCmp('trendCounterButton_configChart_roaming');
									var cancelKpiButton = Ext.getCmp('trendCounterCancelButton_configChart_roaming');
									addKpiButton.setDisabled(false);
									cancelKpiButton.setDisabled(false);
                            	}
                            	Ext.getCmp('mapKpiGrid_configChart_roaming').getStore().removeAt(rowIndex); 

                            }
                        }]
                  		}
	              	],
	                listeners: {
				        'select': function(panel, record, index) {
				        	Ext.getCmp('mapField_configChart_roaming').query('.combobox,.textfield,.checkbox').forEach(function(c){c.setDisabled(false);});
				        	//Ext.getCmp('mapKpiGrid_configChart_romaing').down('#removeKpi').setDisabled(record.length);
				  			// Fill and inialize the alarm fields 	
				        	var title = record.data.groupname == "" ? 'Indicator: ' : 'Indicator: '+record.data.groupname;
				        	Ext.getCmp('mapField_configChart_roaming').originalValue = record.data.groupname;
				        	Ext.getCmp('mapField_configChart_roaming').setTitle(title);
				        	Ext.getCmp('titleField_configChart_roaming').originalValue = record.data.groupname;
				        	Ext.getCmp('titleField_configChart_roaming').setValue(record.data.groupname);
				        	Ext.getCmp('typeCombo_configChart_roaming').originalValue = record.data.trendkpifunction;
				        	Ext.getCmp('typeCombo_configChart_roaming').setValue(record.data.trendkpifunction);
				        	Ext.getCmp('trendUnitField_configChart_roaming').originalValue = record.data.trendunit;
				        	Ext.getCmp('trendUnitField_configChart_roaming').setValue(record.data.trendunit);
				        	
				        	Ext.getCmp('thresholdMinField_configChart_roaming').originalValue = record.data.lowthreshold;
				        	Ext.getCmp('thresholdMinField_configChart_roaming').setValue(record.data.lowthreshold);
				        	Ext.getCmp('thresholdMaxField_configChart_roaming').originalValue = record.data.highthreshold;
				        	Ext.getCmp('thresholdMaxField_configChart_roaming').setValue(record.data.highthreshold);
				        	Ext.getCmp('neId_configChart').setValue(record.data.roamingneid);
				        	Ext.getCmp('neId2_configChart').setValue(record.data.roamingneid2);
				        	Ext.getCmp('selected_kpi_roaming').setValue(index);
				        	
					    }
	                }
	        
				},
				{
					id: 'mapField_configChart_roaming',
		        	xtype: 'fieldset',
					title: 'No indicator selected',
					defaultType: 'textfield',
					layout: 'anchor',
					defaults: {
		    			anchor: '100%'
					},
					items: [
							{
				            xtype: 'radiogroup',
				            id: 'neAssociation',
				            fieldLabel: 'Network axe',
				            labelWidth: 120,
				            //cls:'x-form-label-no-border',
				            columns: 2,
				            items: [
				                {boxLabel: 'Axe 1', name: 'neLevelSelction',id:'axe1', inputValue:'1',checked: true},
				                {boxLabel: 'Axe 2', name: 'neLevelSelction',id:'axe2',inputValue:'2'}
				            ],
				            listeners:{
				            	'afterrender': function(combo) {
			    	    		       Ext.getCmp('parentLevelSelected_configMap').setValue(1);
								 },
				            	'change': function (radio, newval,oldval){
				            		var configChart = Ext.getCmp('configChart');
			
				            		if(newval.neLevelSelction==1){
				            			Ext.getCmp('parentLevelSelected_configMap').setValue(1);
				            		}
				            		else{
				            			Ext.getCmp('parentLevelSelected_configMap').setValue(2);
				            		}
				            		me.checkModifications();
				            	}
				            }
				        },		
						{
				            fieldLabel: 'Network level',
				            labelWidth: 120,
				            id: 'neLevel_configMapAssoction',
				            xtype: 'fieldcontainer',
				            layout: 'hbox',
				            flex:2,
				            defaults: {
				            	hideLabel: true,
				                margin: '0 2 0 0'
				            },
				            items: [
				                {
				                	id: 'neLevelSelectButton_configMapAssoction',
				                	xtype: 'button',
				                    width: 16,
				                    cls: 'x-button-counter-select',
				                    action: 'selectNeLevel'
				                },
				                {
				                	id: 'neLevelCancelButton_configMapAssoction',
				                    xtype: 'button',	                    
				                    width: 18,
				                    cls: 'icoCancel',
				                    action: 'cancelNetworkElementLevel'
				                }
				            ]
			        	},
				        
			        	{
				            fieldLabel: 'Network element',
				            labelWidth: 120,
				            id: 'ne_configMapAssoction',
				            xtype: 'fieldcontainer',
				            layout: 'hbox',
				            width: '100%',
				            //flex:4,
				            defaults: {
				            	hideLabel: true,
				                margin: '0 2 0 0'
				            },
				            items: [
				                {
				                	id: 'neSelectButton_configMapAssoction',
				                	xtype: 'button',
				                    width: 16,
				                    cls: 'x-button-counter-select',
				                    action: 'selectNe'
				                },
				                {
				                	id: 'neCancelButton_configMapAssoction',
				                    xtype: 'button',	                    
				                    width: 18,
				                    cls: 'icoCancel',
				                    action: 'cancelNetworkElement'
				                }
				            ]
			        	},
			        	{
							fieldLabel: 'Title',
							labelWidth: 120,
							id: 'titleField_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								},
								'blur': function (field) {
									if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
										var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
										
										var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
										     return (record.get('trendkpiid') == selection.data.trendkpiid);
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
			                fieldLabel: 'Unit',
			                labelWidth: 120,
			                id: 'trendUnitField_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								},
								'blur': function (field) {
									if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
										var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
										var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
										     return (record.get('trendkpiid') == selection.data.trendkpiid);
										});
										
										record=record.items[0];
										if (record != null) {
											record.data.trendunit = field.getRawValue();
										}
									}	
								}
							}
				        },
				        {
				            fieldLabel: 'Type',
				            labelWidth: 120,
				            id: 'typeCombo_configChart_roaming',
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
										var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
										var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
										     return (record.get('trendkpiid') == selection.data.trendkpiid );
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
				            xtype: 'numberfield',
				            labelWidth: 120,
				            hideTrigger: true,
							fieldLabel: 'Threshold min',
				            id: 'thresholdMinField_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								},
								'blur': function (field) {
									if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
										var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
										var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
										     return (record.get('trendkpiid') == selection.data.trendkpiid);
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
				            labelWidth: 120,
				            hideTrigger: true,
				            fieldLabel: 'Threshold max',
				            id: 'thresholdMaxField_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								},
								'blur': function (field) {
									if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
										var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
										var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
										     return (record.get('trendkpiid') == selection.data.trendkpiid);
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
				        	xtype: 'hiddenfield',
				        	id: 'trendCounterId_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								}
							}      	
				        },
				        {
				        	xtype: 'hiddenfield',
				        	id: 'trendCounterLabel_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								}
							}      	
				        },
				        {
				        	xtype: 'hiddenfield',
				        	id: 'trendCounterProductId_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								}
							}      	
				        },
				        {
				        	xtype: 'hiddenfield',
				        	id: 'trendCounterProductLabel_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								}
							}      	
				        },
				        {
				        	xtype: 'hiddenfield',
				        	id: 'trendCounterType_configChart_roaming',
							listeners: {
								'dirtychange': function() {
									me.checkModifications();
								}
							}      	
				        },
				        {
				        	xtype: 'hiddenfield',
				        	id: 'selected_kpi_roaming'
							     	
				        },
				         {
				        	xtype: 'hiddenfield',
				        	id: 'new_kpi',
				        	value: 'false'
							     	
				        },
				        
			        	/**
			        	//hiddenfield for ne ids
			       		{
		            	id: 'associateNe_configMapAssoction',
		            	text: 'associate',
		            	xtype: 'button',
		                width: 16,
		                action: 'associate'
			        	}
			        	**/
					]
				}
		];
		           
		this.callParent(arguments);
	},
	
	isRadioDirty: function(radio)
    {
		if(radio.disabled || !radio.rendered) {
		       return false;
		   }
		   return String(radio.items.items[0].getChecked()[0].inputValue) !== String(radio.items.items[0].originalValue.neLevelSelction);
    },
		
	checkModifications: function() {
		var me = this;
		
		//var dirty = me.isRadioDirty(Ext.getCmp('configMapAssociation'));
		
		var dirty = Ext.getCmp('typeCombo_configChart_roaming').isDirty() ||
			Ext.getCmp('trendUnitField_configChart_roaming').isDirty() ||
			Ext.getCmp('thresholdMinField_configChart_roaming').isDirty() ||
			Ext.getCmp('thresholdMaxField_configChart_roaming').isDirty()||
			Ext.getCmp('trendCounterId_configChart_roaming').isDirty()||
			Ext.getCmp('trendCounterProductLabel_configChart_roaming').isDirty()||
			Ext.getCmp('trendCounterProductId_configChart_roaming').isDirty()||
			Ext.getCmp('trendCounterLabel_configChart_roaming').isDirty()||
			Ext.getCmp('trendCounterType_configChart_roaming').isDirty();
		
		if (dirty) {
			me.modified = true;
			me.title=me.title.replace(/\s\*/g,"");
			me.setTitle(me.title+' *');
		} else {
			me.modified = false;
			me.title=me.title.replace(/\s\*/g,"");
			me.setTitle(me.title);
		}
	}
	
});