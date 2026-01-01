Ext.define('homepage.view.charts.GaugeDetails' ,{
    extend: 'Ext.panel.Panel',
    alias : 'widget.gaugedetails',
	
    layout: {
        type: 'hbox',
        pack: 'start',
        align: 'stretch'
    },
	cls: 'x-panel-no-border',
	padding: '2 2 2 2',
	flex: 3,
    
	initComponent: function() {
		this.items = [
             {
            	 id: this.id + '_infoPanel',
            	 xtype: 'panel',
            	 cls: 'x-panel-no-border',
            	 //layout: 'absolute',
            	 layout: {
            			type: 'vbox',
            			align: 'stretch',
            			pack: 'start'
            		},
            	 //height: 100,
            	 padding: '0 2 0 0',
            	 flex: 1,
            	 items: [
					{
						id: this.id + '_label1',
						xtype: 'label',
						flex: 1,
						cls: 'x-label-gauge-details',
						listeners: {
			                resize: function(label){
			                    Ext.create('Ext.tip.ToolTip',{
			                        target: label.getEl(),
			                        html: label.getEl().dom.textContent,
			                    });
			                }
			            }
					},
					{
						id: this.id + '_label2',
						xtype: 'label',
						flex: 1,
						cls: 'x-label-gauge-details',
						listeners: {
			                resize: function(label){
			                    Ext.create('Ext.tip.ToolTip',{
			                        target: label.getEl(),
			                        html: label.getEl().dom.textContent,
			                    });
			                }
			            }
					},
					{
						id: this.id + '_label3',
						xtype: 'label',
						flex: 1,
						cls: 'x-label-gauge-details',
						listeners: {
			                resize: function(label){
			                    Ext.create('Ext.tip.ToolTip',{
			                        target: label.getEl(),
			                        html: label.getEl().dom.textContent,
			                    });
			                }
			            }
						//y: 22
					}
     	        ]
             },
             {
            	id: this.id + '_value_container',
            	xtype: 'panel',
            	flex: 1,
            	layout: {
        			type: 'vbox',
        			align: 'stretch',
        			pack: 'start'
        		},
        		cls: 'x-panel-no-border',
            	items: [
            	        {
            	        	xtype: 'panel',
            	        	cls: 'x-panel-no-border',
            	        	flex: 1,
            	        },
            	        {
            	        	id: this.id + '_value',
                        	xtype: 'label',
                        	cls: 'ellipsis',
                        	style: 'text-align:right;',
                        	flex: 3,
                        	padding: '0 5 0 0',
                        	text: 'XXXX',
                        	listeners: {
    			                resize: function(label){
    			                    Ext.create('Ext.tip.ToolTip',{
    			                        target: label.getEl(),
    			                        html: label.getEl().dom.textContent,
    			                    });
    			                }
    			            }
            	        },
            	        {
            	        	xtype: 'panel',
            	        	cls: 'x-panel-no-border',
            	        	flex: 1,
            	        }
            	]
             }
		];
		
		this.callParent(arguments);
	}
});