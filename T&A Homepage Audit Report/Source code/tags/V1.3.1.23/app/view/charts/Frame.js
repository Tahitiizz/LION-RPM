Ext.define('homepage.view.charts.Frame' ,{
        extend: 'Ext.panel.Panel',
        alias : 'widget.frame',

	requires: ['Ext.chart.*'],

	iconCls: 'icoGraph',
	cls: 'x-chart-title-normal',
	padding: '5 5 5 5',
	layout: 'fit',
		
	initComponent: function(){
		var me = this;

		this.items = [
    		{
    			id: this.id + '_frame',
    			xtype : 'component',
    			autoEl : {
    				tag : 'iframe'
    			}
  		    }
		];
		
		this.callParent(arguments);	
	}
});

