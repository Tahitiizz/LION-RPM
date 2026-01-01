Ext.define('homepage.view.charts.GridReport' ,{
        extend: 'Ext.panel.Panel',
        alias : 'widget.gridreport',

	requires: ['Ext.chart.*'],

	iconCls: 'icoGraph',
	cls: 'x-chart-title-normal',
	padding: '5 5 5 5',
	layout: {
	    type: 'hbox',
	    pack: 'start',
	    align: 'stretch'
	},
		
	initComponent: function(){
		var me = this;
		
		this.items = [
      		{
      			html: 'Collecting data...',
      			flex:1,
      			cls: 'x-panel-no-border'
		    }
  		];	
      		
		this.callParent(arguments);	
	}
});

