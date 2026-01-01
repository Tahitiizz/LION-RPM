Ext.define('homepage.view.charts.Gauge' ,{
        extend: 'Ext.panel.Panel',
        alias : 'widget.gauge',

	requires: ['Ext.chart.*'],

	iconCls: 'icoGraph',
	cls: 'x-chart-title-normal',
	padding: '5 5 0 5',
	layout: {
		type: 'vbox',
		align: 'stretch',
		pack: 'start'
	},
	
	gaugeView: null, // Gauge view
	
	initComponent: function(){
		var me = this;
		
		Ext.tip.QuickTipManager.init();
		
		this.path = null;

		if (Ext.getCmp('viewport').gaugeType == 1) {
			var view = Ext.create('homepage.view.charts.GaugeViewIE', {
				id: this.id + '_viewie'
			});
			this.items = [
	    		{
	    		  	id: this.id + '_details',
	  		    	xtype: 'gaugedetails',
	  		    	
	  		    },
	  		    view
	  		];
			me.gaugeView = view;
		} else {
			this.items = [
	    		{
	    		  	id: this.id + '_details',
	  		    	xtype: 'gaugedetails'
	  		    },
	  			{
	  				xtype: 'gaugeview',
	  				html: '<div id="div_' + this.id + '"></div>'
	  			}
	  		];
		}
		
		this.listeners = {
			'render': function(gauge) {
   				gauge.el.on('click', function() {
   					// If a new gauge is selected, display the period graph
   					var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
   					var chartsPanel = Ext.getCmp(tabId);
   					if (chartsPanel.selectedChart != gauge.id) {
   						if (Ext.getCmp('configurationButton').pressed &&									// configuration is available
							!Ext.getCmp('configPanel').collapsed &&											// configuration panel is opened 
							(Ext.getCmp('configTab').modified || Ext.getCmp('configChart').modified)) {		// some configuration has been changed
   						 	// Show a warning window
   							Ext.MessageBox.confirm('Warning', 
   								'The configuration has been modified. Do you wish to continue without saving?', 
   								function(response) {
   									if (response == 'yes') {
   										chartsPanel.selectedChart = gauge.id;
   			   	   	   					gauge.fireEvent('chartClick');
									}
   								}
   							);
						} else {
							chartsPanel.selectedChart = gauge.id;
   	   	   					gauge.fireEvent('chartClick');
						}  						
   					}   						
   				});
			}
		};

		this.callParent(arguments);	
	}
});

