Ext.define('homepage.controller.charts.SummaryGraph', {
	extend : 'Ext.app.Controller',
	views : ['charts.SummaryGraph'],

	// Initialize the event handlers
	init : function() {
		this.control({
					'summarygraph' : {
						load : this.load
					}
				});
	},

	load : function(config, time) {

		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		tabId += '_chart1';

		//Set default value for history before getting it from config
		var history = 3;

		//get all the components created in the summary graph view
		var trend = Ext.getCmp(tabId + '_summary_graph_trend');
		var grid = Ext.getCmp(tabId + '_summary_graph_grid');

		//get reference period from general.xml conf file
		var refPeriod = Ext.getCmp('viewport').referenceperiod;

		//get the history scale from homepage.xml
		history = config['widgets']['widget'][0]['history'];

		//get ratio and nbdays for penalisation
		var selectedMode = config['@attributes']['selectedmode'];
		var ratio = config['@attributes']['ratio'];
		var nbdays = config['@attributes']['nbdays'];
		var alarm_ids = new Array();

		//TODO check if empty alarms
		//Get all alarms id from pool alarms set in config file : homepage.xml
		Ext.Object.each(config['widgets']['widget'][0]['calc_alarms']['alarm'],
				function(index, alarm) {
					alarm_ids.push(alarm.id);
				});

		if(trend.legend==false){
			// Create a smart legend component to display legend items on several lines
			var legend = trend.legend = Ext.create('Ext.ux.chart.SmartLegend', {
						id : 'summary_legend',
						position : 'top',
						chart : trend,
						rebuild : true,
						boxStrokeWidth : 2
					});
		}
		

		//create query parameters
		var alarmOptions = {};
		alarmOptions.sdp_id = config['widgets']['widget'][0]['sdp_id'];
		alarmOptions.current_date = time;
		alarmOptions.ref_period = refPeriod;
		alarmOptions.history = history;
		alarmOptions.selectedmode = selectedMode;
		alarmOptions.ratioforpenalisation = ratio;
		alarmOptions.nbdaysforpenalisation = nbdays;
		alarmOptions.alarm_ids = alarm_ids;

		//First we set a monthlist from the reference period to the current period
		Ext.Ajax.request({
			url : 'proxy/alarm_list.php',
			params : {
				task : 'GET_MONTHS_LIST',
				params : {
					params : Ext.encode(alarmOptions)
				}
			},

			success : function(response) {
				var months = Ext.decode(response.responseText);

				var monthslist = new Array();

				Ext.Object.each(months, function(index, month) {
							monthslist.push(month);
						});

				alarmOptions.current_date = monthslist;

				//For each month we calculate warning and penalty cells
				Ext.Ajax.request({
							url : 'proxy/alarm_list.php',
							params : {
								task : 'GET_WARNING_PENALTY_MONTH',
								params : {
									params : Ext.encode(alarmOptions)
								}
							},

							success : function(response) {
								var result = Ext.decode(response.responseText);
								var trenddata = new Array();
								var griddata = new Array();

								//Push data into an array
								Ext.Object.each(result, function(month, data) {
											trenddata.push(data);
											data.reftime = Ext.Date.format(Ext.Date.add(Ext.Date.parse(month.toString()+ '01','Ymd'),Ext.Date.MONTH,(-1 * refPeriod)),'Ym');
											griddata.push(data);
										});

								//Load data in grid and trend
								trend.store.loadData(trenddata);
								grid.store.loadData(griddata);

								//set dynamic scale
								var maxpenalty = trend.store.max('penalty');
								var maxwarning = trend.store.max('warning');
								var max = 100;
								(maxpenalty > maxwarning
										? max = maxpenalty
										: max = maxwarning);
								var perMax = Math.ceil(max / 5);
								var dynamicMax = Math.ceil(max + perMax);
								var axisLeft = trend.axes.get(0);
								if (dynamicMax <= 10)
									dynamicMax = 10;
								axisLeft.maximum = dynamicMax;
								trend.series.get(0).setTitle(['Warning cells',
										'Penalty cells']);

								//Redraw thr grid and the trend with loaded data
								trend.surface.removeAll();
								if(trend.legend==false){
									trend.legend.redraw();
								}
								trend.redraw();

							}
						});
			}
		});
	}
});