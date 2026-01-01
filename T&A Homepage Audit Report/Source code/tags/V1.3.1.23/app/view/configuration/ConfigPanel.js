Ext.define('homepage.view.configuration.ConfigPanel' ,{
	extend: 'Ext.panel.Panel',
	alias : 'widget.configpanel',
	
	layout: {
		type: 'vbox',
		align: 'stretch',
		pack: 'start'
	},
	
	modified: false,
	
	initComponent: function() {		
		var me = this;
		
		this.items = [
			{
	            fieldLabel: 'Style',
	            id: 'styleCombo',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
				style: {
		            marginTop: '10px',
		            marginLeft: '5px',
		            marginRight: '5px'
		        },
	            store: [
	                ['classic', 'Classic'],
	                ['access', 'Access'],
	            ],
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}
	        },
			{
				id: 'configTab',
				xtype: 'configtab'
			},
			{
				id: 'configMapModeSelection',
				xtype: 'configmapmodeselection'
			},
			{
				id: 'configChart',
				xtype: 'configchart',
				flex: 1
			},
			{
				id: 'configMapAssociation',
				xtype: 'configmapassociation',
				flex: 1
			},
			
			{
				id: 'configToolbar',
				xtype: 'toolbar',
				items : [
			         {
						id: 'displayButton',
			        	iconCls: 'icoApplication',
						text: 'Display',
						action: 'display'
			         },
			         {
						id: 'saveButton',
			        	iconCls: 'icoSave',
						text: 'Save',
						action: 'save'
			         },
			         {
			        	id: 'resetButton',
						iconCls: 'icoUndo',
						text: 'Reset',
						action: 'reset'
			         }
		         ]
			}
		];
		
		this.callParent(arguments);
	},
		
	checkModifications: function() {
		var me = this;
				
		var dirty = Ext.getCmp('styleCombo').isDirty();
		
		if (dirty) {
			me.modified = true;
		} else {
			me.modified = false;
		}
	}
});

