Ext.define('homepage.view.configuration.ConfigTab' ,{
	extend: 'Ext.form.Panel',
    alias : 'widget.configtab',
    
    iconCls: 'icoTabEdit',
	bodyStyle:'padding:5px 5px 0',
	defaultType: 'textfield',
    defaults: {
    	anchor: '100%'
    },
    
    title: 'Tab',
    modified: false,
    
    initComponent: function() {
    	var me = this;
		me.items = [
			{
	            fieldLabel: 'Template',
	            id: 'templateCombo_configTab',
	            xtype: 'combobox',
	            forceSelection: true,
	            editable: false,
	            valueField: 'id',
	            displayField: 'label',
	            store: {
					fields: ['id', 'label'],
					proxy: {
				        type: 'ajax',
				        url : 'proxy/configuration.php',
				        extraParams: {
							task: 'GET_TEMPLATES'
						},
						actionMethods: {
					        read: 'POST'
					    },
				        reader: {
				            type: 'json',
				            root: 'template'
				        }
				    },
				    autoLoad: true
				},
	            listeners: {
					'dirtychange': function(combo, isDirty) {
						if (isDirty) {
							Ext.MessageBox.alert('Warning', 
								'If you save with a new template, the current configuration will be erased.');
						}						
						me.checkModifications();
					}
				}
	        },
	        {
				fieldLabel: 'Title',
				id: 'titleField_configTab',
				//vtype: 'alphanum',
				//restrict to alnum to avoid & and < > in xml
//				vtypeText: 'Only alphanumerical input',
				validateOnChange: false,
				validateOnBlur: false,
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}
			},
	        {
				fieldLabel: 'Index',
				id: 'indexField_configTab',
				xtype: 'numberfield',
				minValue: 1,
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}
			},
	        {
	        	xtype: 'hiddenfield',
	        	id: 'defaultTabHidden',
				listeners: {
					'dirtychange': function() {
						me.checkModifications();
					}
				}        	
	        },
	        {
				xtype: 'component',
				html: 'Tab management',
				cls:'x-form-label'
			},
	        {
				id: 'tabToolbar',
				xtype: 'toolbar',
				cls: 'simpleToolbar',
				items : [
					{
						id: 'addTabButton',
						iconCls: 'icoTabAdd',
						text: 'Add',
						action: 'add'
					 },
			         {
						id: 'copyTabButton',
						iconCls: 'icoTabCopy',
						text: 'Copy',
						action: 'copyTab'
				     },
			         {
				    	id: 'deleteTabButton',
				    	iconCls: 'icoTabDelete',
						text: 'Delete',
						action: 'deleteTab'
				     },
				     {
			        	id: 'defaultTabButton',
						iconCls: 'icoStar',
						text: 'Default',
						action: 'defaultTab',
						enableToggle: true,
		                toggleHandler: function(button, check) {
			        		Ext.getCmp('defaultTabHidden').setValue(check);
			        	}
				     }
			         
		         ]
			}
		];
		           
		this.callParent(arguments);
	},
		
	checkModifications: function() {
		var me = this;
		
		var dirty = Ext.getCmp('titleField_configTab').isDirty() || 
			Ext.getCmp('templateCombo_configTab').isDirty() ||
			Ext.getCmp('indexField_configTab').isDirty() ||
			Ext.getCmp('defaultTabHidden').isDirty();
		
		if (dirty) {
			me.modified = true;
			me.setTitle('Tab *');
		} else {
			me.modified = false;
			me.setTitle('Tab');
		}
	}
});