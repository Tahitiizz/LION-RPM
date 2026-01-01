Ext.define('homepage.view.charts.CellsSurveillance' ,{
    extend: 'Ext.panel.Panel',
    alias : 'widget.cellssurveillance',

    requires: ['Ext.*'],

    iconCls: 'icoGraph',
	cls: 'x-chart-title-normal',
	padding: '5 5 5 5',
	preventHeader: true,
//	layout: {
//	    type: 'table',
//	    columns: 1,
//	    tableAttrs: {
//	    	style: {
//	    		width: '100%'
//            }
//        }
//	},
	layout: 'anchor',
	autoScroll: true,
//	defaults: {
//        // applied to each contained panel
//        rowspan: 1
//    },
		
	initComponent: function(){
		var me = this;
		
		Ext.define('CSModel', {
            extend: 'Ext.data.Model',
            fields: [
                {name: 'cell_id', type: 'string'},
                {name: 'cell_label', type: 'string'},
                {name: 'parent', type: 'string'},
                {name: 'in_ref_period', type: 'string'},
                {name: 'days_in_default', type: 'int'},
                {name: 'days_before_penalisation', type: 'int'}
            ]
        });
		
		this.items = [
      		{
      			//display wait message
      			html: 'Collecting data...',
      			id: this.id + '_cellssurveillancecollecting',
      			cls: 'x-panel-no-border'
		    },
		    {
		    	//selector panel
		    	id: this.id + '_selectorpanel',
      			cls: 'x-panel-no-border',
      			padding: '10 0 5 0',
      			anchor: '98% none',
      			layout: 'hbox',
      			items:[
					{
						  xtype: 'panel',
						  cls: 'x-panel-no-border',
						  flex: 2
					},
					{
						id: this.id + '_cell_previous_month',
					    xtype: 'button',
					    enableToggle: true,
					    toggleGroup : 'cellsButtonGroup',
					    text: 'Previous Month',
						flex: 1,
					    action: 'loadpreviousmonth'
					}, 
					{
						  xtype: 'panel',
						  cls: 'x-panel-no-border',
						  flex: 0.2
					},
					{
						id: this.id + '_cell_current_month',
					    xtype: 'button',
					    text: 'Current Month',
					    enableToggle: true,
					    toggleGroup : 'cellsButtonGroup',
						flex: 1,
					    action: 'loadcurrentmonth'
					},
					{
			        	  xtype: 'panel',
			        	  cls: 'x-panel-no-border',
						  flex: 2
			        },
      			]
		    },
		    {
		    	//main panel item
		    	id: this.id + '_cellssurveillancemain',
		    	anchor: 'none',
		    	layout: 'anchor',
      			cls: 'x-panel-no-border'
		    },

  		];	
      		
		this.callParent(arguments);	
	}
});

