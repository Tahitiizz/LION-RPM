Ext.define('homepage.view.configuration.ConfigMapModeSelection' ,{
	extend: 'Ext.form.Panel',
    alias : 'widget.configmapmodeselection',
    
    iconCls: 'icoTabEdit',
	bodyStyle:'padding:5px 5px 0',
	defaultType: 'textfield',
    defaults: {
    	anchor: '100%'
    },
    
    title: 'Mode Selection',
    modified: false,
    
    initComponent: function() {
    	var me = this;
    	
		me.items = [
			{
	            xtype: 'radiogroup',
	            id: 'mapMode',
	            //fieldLabel: ' ',
	            vertical: true,
	            columns: 1,
	            cls:'x-form-label-no-border',
	            items: [
	                {boxLabel: 'Map, trend and donut', name: 'modeselection',id:'trend', inputValue:'1',checked: true},
	                {boxLabel: 'Fullscreen map', name: 'modeselection',id:'fullscreen',inputValue:'2'},
	                {boxLabel: 'Roaming map', name: 'modeselection',id:'roaming',inputValue:'3'}
	            ],
	            listeners:{
//	            	'dirtychange': function() {
//						me.checkModifications();
//					},
	            	'change': function (radio, newval,oldval){
	            		var configChart = Ext.getCmp('configChart');
	            		if(newval.modeselection==1){
	            			Ext.getCmp('configChart').setVisible(true);
	            			Ext.getCmp('configMapAssociation').setVisible(false);
	            			Ext.getCmp('counterContainer_configChart').setVisible(true);
	            			Ext.getCmp('unitField_configChart').setVisible(true);
	            			Ext.getCmp('defaultTrendTimeLevelCombo_configMap').setVisible(true);
	            			Ext.getCmp('defaultDonutTimeLevelCombo_configMap').setVisible(true);
	            			Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').setVisible(false);
	            			Ext.getCmp('mapKpiGrid_configChart').columns[3].show();
	            			Ext.getCmp('mapKpiGrid_configChart').getView().refresh();
	            			Ext.getCmp('displayedValueMode_configMap').setVisible(false);
	            			configChart
								.down('checkboxfield[id="dynamicBox_configChart"]')
								.setVisible(true);
							configChart
								.down('numberfield[id="scaleMinField_configChart"]')
								.setVisible(true);
					
							configChart
								.down('numberfield[id="scaleMaxField_configChart"]')
								.setVisible(true);
							/**
							if(Ext.getCmp('activate_roaming').getValue() === true){
								Ext.getCmp('displayedValueMode_configMap').setVisible(true);
								Ext.getCmp('configMapAssociation').setVisible(true);
							}else{
								Ext.getCmp('displayedValueMode_configMap').setVisible(false);
								Ext.getCmp('configMapAssociation').setVisible(false);
							}**/
							Ext.getCmp('configMapAssociation').setVisible(false);
	            		}else if (newval.modeselection==2){
	            			Ext.getCmp('configChart').setVisible(true);
	            			Ext.getCmp('configMapAssociation').setVisible(false);
	            			Ext.getCmp('counterContainer_configChart').setVisible(false);
	            			Ext.getCmp('unitField_configChart').setVisible(false);
	            			Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').setVisible(true);
	            			Ext.getCmp('defaultTrendTimeLevelCombo_configMap').setVisible(false);
	            			Ext.getCmp('defaultDonutTimeLevelCombo_configMap').setVisible(false);
	            			Ext.getCmp('displayedValueMode_configMap').setVisible(false);
	            			Ext.getCmp('mapKpiGrid_configChart').columns[3].hide();
	            			Ext.getCmp('mapKpiGrid_configChart').getView().refresh();
	            			configChart
								.down('checkboxfield[id="dynamicBox_configChart"]')
								.setVisible(false);
							configChart
								.down('numberfield[id="scaleMinField_configChart"]')
								.setVisible(false);
						
							configChart
								.down('numberfield[id="scaleMaxField_configChart"]')
								.setVisible(false);
								
							/**
							if(Ext.getCmp('activate_roaming').getValue() === true){
								Ext.getCmp('displayedValueMode_configMap').setVisible(true);
								Ext.getCmp('configMapAssociation').setVisible(true);
							}else{
								Ext.getCmp('displayedValueMode_configMap').setVisible(false);
								Ext.getCmp('configMapAssociation').setVisible(false);
							}**/
							Ext.getCmp('configMapAssociation').setVisible(false);
	            		}else if(newval.modeselection==3){
	            			Ext.getCmp('configChart').setVisible(false);
	            			Ext.getCmp('configMapAssociation').setVisible(true);
	            			Ext.getCmp('counterContainer_configChart').setVisible(false);
	            			Ext.getCmp('unitField_configChart').setVisible(false);
	            			Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').setVisible(true);
	            			Ext.getCmp('defaultTrendTimeLevelCombo_configMap').setVisible(false);
	            			Ext.getCmp('defaultDonutTimeLevelCombo_configMap').setVisible(false);	            			
	            			Ext.getCmp('mapKpiGrid_configChart_roaming').getView().refresh();
	            			Ext.getCmp('displayedValueMode_configMap').setVisible(true);
	            			configChart
								.down('checkboxfield[id="dynamicBox_configChart"]')
								.setVisible(false);
							configChart
								.down('numberfield[id="scaleMinField_configChart"]')
								.setVisible(false);
							configChart
								.down('numberfield[id="scaleMaxField_configChart"]')
								.setVisible(false);
	            			Ext.getCmp('configMapAssociation').setVisible(true);
	            		}
	            		me.checkModifications();
	            	},
	            	renderer: function(storeItem, item) {
	            		if(Ext.getCmp('configMapModeSelection').items.items[0].getValue().modeselection == "1"){
							Ext.getCmp('displayedValueMode_configMap').setVisible(false);
						}
	            	}
	            }
	        },
	        /**
	        {
		 		xtype: 'fieldcontainer',
	            fieldLabel: 'Display Sub Elements',
	            defaultType: 'checkboxfield',
	            items: [{
	                name: 'activate_roaming',
	                inputValue: '1',
	                id: 'activate_roaming',
	                visible: false,
	                listeners : {
		                'change': function(checkbox, roaming, oldValue){
		                	var configChart = Ext.getCmp('configChart');
		                	if(roaming == true){
				                	//if fullscreen
				                	if(Ext.getCmp('mapMode').getValue().modeselection == 2){
				                		Ext.getCmp('displayedValueMode_configMap').setVisible(true);
				            			Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').setVisible(true);
				            			Ext.getCmp('configMapAssociation').setVisible(true);
				                	}else{
				                		Ext.getCmp('displayedValueMode_configMap').setVisible(true);
				                		Ext.getCmp('defaultTrendTimeLevelCombo_configMap').setVisible(true);
				                		Ext.getCmp('defaultDonutTimeLevelCombo_configMap').setVisible(true);
				                		Ext.getCmp('configMapAssociation').setVisible(true);
				                	}
				               }else{
					               	//if fullscreen
				               		if(Ext.getCmp('mapMode').getValue().modeselection == 2){
				            			Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').setVisible(true);
				            			Ext.getCmp('displayedValueMode_configMap').setVisible(false);
				            			Ext.getCmp('configMapAssociation').setVisible(false);
				            			Ext.getCmp('counterContainer_configChart').setVisible(false);
				            			Ext.getCmp('unitField_configChart').setVisible(false);
				            			Ext.getCmp('mapKpiGrid_configChart').columns[3].hide();
				            			Ext.getCmp('mapKpiGrid_configChart').getView().refresh();
				            			configChart
											.down('checkboxfield[id="dynamicBox_configChart"]')
											.setVisible(false);
										configChart
											.down('numberfield[id="scaleMinField_configChart"]')
											.setVisible(false);
									
										configChart
											.down('numberfield[id="scaleMaxField_configChart"]')
											.setVisible(false);
				                	}else{
				                		Ext.getCmp('displayedValueMode_configMap').setVisible(false);
				                		Ext.getCmp('defaultTrendTimeLevelCombo_configMap').setVisible(true);
				                		Ext.getCmp('defaultDonutTimeLevelCombo_configMap').setVisible(true);
				                		Ext.getCmp('configMapAssociation').setVisible(false);
				                		Ext.getCmp('counterContainer_configChart').setVisible(true);
				            			Ext.getCmp('unitField_configChart').setVisible(true);
				            			Ext.getCmp('mapKpiGrid_configChart').columns[3].show();
				            			Ext.getCmp('mapKpiGrid_configChart').getView().refresh();
				            			configChart
											.down('checkboxfield[id="dynamicBox_configChart"]')
											.setVisible(true);
										configChart
											.down('numberfield[id="scaleMinField_configChart"]')
											.setVisible(true);
								
										configChart
											.down('numberfield[id="scaleMaxField_configChart"]')
											.setVisible(true);
				                	}
				               }
				               me.checkModifications();
				        }
		            }
	            }]
			},
			**/
	        {
	            fieldLabel: 'Displayed value mode',
	            id: 'displayedValueMode_configMap',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            store: [
	                ['element', 'Element'],
	                ['worst_sub_element', 'Worst Sub Element']
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function (field,newval,oldval) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							
						}	
					}
				}
	        },
	       	{
	            fieldLabel: 'Defaut time level',
	            id: 'defaultFullscreenTimeLevelCombo_configMap',
	            xtype: 'combobox',
	            hidden: 'true',
	            forceSelection: true,
	            editable: false,
	            store: [
	                ['hour', 'Hour'],
	                ['day', 'Day'],
					['day_bh', 'Day BH'],
					['week', 'Week'],
					['week_bh', 'Week BH'],
					['month', 'Month']
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function (field,newval,oldval) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
							
						}	
					}
				}
	        },
	        
	        {
	            fieldLabel: 'Trend defaut time level',
	            id: 'defaultTrendTimeLevelCombo_configMap',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            store: [
	                ['hour', 'Hour'],
	                ['day', 'Day'],
					['day_bh', 'Day BH'],
					['week', 'Week'],
					['week_bh', 'Week BH'],
					['month', 'Month']
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function (field,newval,oldval) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
						}	
					}
				}
	        },
	        {
	            fieldLabel: 'Donut defaut time level',
	            id: 'defaultDonutTimeLevelCombo_configMap',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            store: [
	                ['hour', 'Hour'],
	                ['day', 'Day'],
					['day_bh', 'Day BH'],
					['week', 'Week'],
					['week_bh', 'Week BH'],
					['month', 'Month']
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					},
					'change': function (field,newval,oldval) {
						if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
						}	
					}
				}
	        },
	        
		];
		           
		this.callParent(arguments);
	},
	
	isRadioDirty: function(radio)
    {
		if(radio.disabled || !radio.rendered) {
		       return false;
		   }
		   return String(radio.items.items[0].getChecked()[0].inputValue) !== String(radio.items.items[0].originalValue.modeselection);
    },
		
	checkModifications: function() {
		var me = this;
		
		var dirty = me.isRadioDirty(Ext.getCmp('configMapModeSelection')) ||
		//Ext.getCmp('activate_roaming').isDirty() ||
		Ext.getCmp('displayedValueMode_configMap').isDirty()||
		Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').isDirty();
		
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