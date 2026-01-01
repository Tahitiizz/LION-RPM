Ext.define('homepage.view.charts.Map' ,{
        extend: 'Ext.panel.Panel',
        alias : 'widget.map',

	requires: ['Ext.*'],
	
	//iconCls: 'icoGraph',
	cls: 'x-panel-no-border',
	frameHeader: false,
	preventHeader: true,
	padding: '5 5 5 5',
 	modified: false,
	layout: {
	    type: 'hbox',
	    pack: 'start',
	    align: 'stretch'
	},
		
	initComponent: function(){
		var me = this;
		var style = Ext.get('homepageStyle').dom.value;	
		
		//create stores for trend and donut
		Ext.define('TimeSelectorModel', {
		    extend: 'Ext.data.Model',
		    fields: [
		        {type: 'string', name: 'id'},
		        {type: 'string', name: 'value'},
		    ]
		});
		
		//kpi selector model
		Ext.define('KpiSelectorModel', {
		    extend: 'Ext.data.Model',
		    fields: [
		        {type: 'string', name: 'group_name'},
		        {type: 'string', name: 'group_index'}
		    ]
		});
		
		this.items = [
    		{
    			html: 'Collecting data...',
    			flex:1,
    			cls: 'x-panel-no-border',
    			fill: "#fff",
    			color: style == 'access'? "white" : "black"
  		    }, 
  		    {
  		    	flex:1,
  		    	cls: 'x-panel-no-border',
  		    	fill: "#fff"
  		    }
  		  
		];
		
		this.callParent(arguments);	
	}
});

