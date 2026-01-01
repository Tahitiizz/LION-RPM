Ext.define('homepage.controller.charts.RiGraph', {
	extend : 'Ext.app.Controller',
	views : ['charts.RiGraph'],
	init : function() {
		var me = this;
		this.control({
					'rigraph' : {
						load : this.load
					}
				});

	},
	load : function(config, time) {
		var me = this;
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		tabId += '_chart1';

		// destroy previous trend to avoid persistent data issues
		var rigraphpanel = Ext.getCmp(tabId + '_reliability_indicator');

		rigraphpanel.removeAll(true);
		rigraphpanel.destroy();

		// create new trend
		var newtrend = Ext.create('homepage.view.charts.RiGraph', {
					id : tabId + '_reliability_indicator',
					anchor : '98% none',
					theme : 'Fancy',
					iconCls : 'icoGraph',
					// height:350,
					cls : 'periodChart',
					padding : '5 5 5 25',
					title : 'Reliability Indicator',
					legend : false
				});

		// insert into auditreport
		Ext.getCmp(tabId).insert(1, newtrend);

		var trend = Ext.getCmp(tabId + '_reliability_indicator_trend');

		if(trend.legend==false){
			// Create a smart legend component to display legend items on several lines
			var legend = trend.legend = Ext.create('Ext.ux.chart.SmartLegend', {
						id : 'ri_legend',
						position : 'top',
						chart : trend,
						rebuild : true,
						boxStroke : '#000',
						boxStrokeWidth : 2

					});
		}
		
		// call to ricalc function to get the reliability indicator for each day of selected month
		me.ricalc(config['widgets']['widget'][0]['sdp_id'], time);

	},

	ricalc : function(sdp_id, time) {
		Ext.Ajax.request({
			url : 'proxy/configuration.php',
			params : {
				task : 'RI_VALUE',
				sdp_id : sdp_id,
				current_month : time
			},
			success : function(response) {
				var data = new Array();
				var error = false;
				var result = Ext.decode(response.responseText);

				if (!error) {

					var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
					tabId += '_chart1';

					var trend = Ext.getCmp(tabId
							+ '_reliability_indicator_trend');
					trend.series.get(0).setTitle('RI CAPTURE DURATION (%)');

					// Put the result datas in the data array
					if (result['data'][0]['no_value'] == 1) {
						// In case the success response return no value from
						// ajax request
						var day = result['data'][0]['day']
						var dataToAdd = new Object();
						dataToAdd.ri_value = undefined;
						dataToAdd.date = undefined;
						dataToAdd.time = undefined;
						data.push(dataToAdd);
					} else {
						for (var i = 0; i < result['data'].length; i++) {
							var day = result['data'][i]['day'];
							// In case we have no ri value for one day
							if (result['data'][i]['ri_value'] == '') {
								// Push the datas into the chart
								var dataToAdd = new Object();
								dataToAdd.ri_value = undefined;
								dataToAdd.date = day;
								dataToAdd.time = day;
							} else {
								var ri_value = parseFloat(result['data'][i]['ri_value']);
								// Push the datas into the chart
								var dataToAdd = new Object();
								dataToAdd.ri_value = ri_value;
								dataToAdd.date = day;
								dataToAdd.time = day;
							}
							data.push(dataToAdd);
						}

					}

				} else {
					Ext.Msg.alert('Error', 'Couldn\'t reach SQL server');

				}
				// load the data into the trend and redraw the components
				if(trend.legend==false){
					trend.legend.redraw();
				}
				trend.store.loadData(data);
				trend.redraw(false);

			}

		});
	}
});
