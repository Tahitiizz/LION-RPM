Ext.define('homepage.controller.charts.AlarmsGraph', {
	extend : 'Ext.app.Controller',
	views : ['charts.AlarmsGraph'],
	init : function() {
		this.control({
					'alarmsgraph' : {
						load : this.load
					}
				});
	},
	load : function(config, time, graphid) {
		var me = this;

		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		var chartsPanel = Ext.getCmp(tabId);

		//Get all the components from the AlarmsGraph view
		var trend = Ext.getCmp(graphid + '_trend');
		var grid = Ext.getCmp(graphid + '_grid');
		var donut = Ext.getCmp(graphid + '_donut');
		posChart = graphid.lastIndexOf('chart1');
		shortGraphId = graphid.substr(posChart+7,graphid.length);
		var graph_alarms_ids = new Array();
		//We set default value for history and piechart before getting them from config (homepage.xml)
		var history = 3;
		var piechart = 0;

		if(trend.legend==false){
			//Create a smart legend component to display legend items on several lines
			var legend = trend.legend = Ext.create('Ext.ux.chart.SmartLegend', {
						position : 'bottom',
						chart : trend,
						rebuild : true,
						boxFill : '#FFFFFF',
						boxStroke : '#000000',
						boxStrokeWidth : 2
					});
		}
		
		//only one graph
		if (config['widgets']['widget'][0]['graph_list']['graph'].length == undefined) {
			var obj = config['widgets']['widget'][0]['graph_list']['graph'];
			if (obj['@attributes']['id'] == shortGraphId) {
				piechart = obj.pie_chart;
				
				if(typeof(obj['alarms_display']['id'])=='string'){
					graph_alarms_ids.push(obj['alarms_display']['id']);
				}
				else{
					Ext.Object.each(obj['alarms_display']['id'],
							function(index, el) {
								graph_alarms_ids.push(el);
							});
				}
			}
		} else {
			//several graphs
			Ext.Object.each(
					config['widgets']['widget'][0]['graph_list']['graph'],
					function(index, obj) {

						if (obj['@attributes']['id'] == shortGraphId) {
							piechart = obj.pie_chart;
							
							if(typeof(obj['alarms_display']['id'])=='string'){
								graph_alarms_ids.push(obj['alarms_display']['id']);
							}
							else{
								Ext.Object.each(obj['alarms_display']['id'],
										function(index, el) {
											graph_alarms_ids.push(el);
										});
							}
						}
					});
		}

		var obj = config['widgets']['widget'][0]['graph_list']['graph'];

		//get alarms names from alarm_ids
		var graphOptions = {};
		graphOptions.sdp_id = config['widgets']['widget'][0]['sdp_id'];
		graphOptions.alarm_ids = graph_alarms_ids;

		Ext.Ajax.request({
			url : 'proxy/alarm_list.php',
			params : {
				task : 'GET_ALARMS_NAMES',
				params : {
					params : Ext.encode(graphOptions)
				}
			},

			success : function(response) {
				var alarm_names_obj = Ext.decode(response.responseText);

				var alarm_names = new Array();

				if(typeof(alarm_names_obj)=='string'){
					alarm_names.push(alarm_names_obj.alarm_name);
				}
				else{
					Ext.Object.each(alarm_names_obj, function(index, obj) {
						alarm_names.push(obj.alarm_name);
					});
				}

				var currentMonth = time;
				var alarmArrayId = graph_alarms_ids;
				var alarmArrayName = alarm_names;

				//We get history value from homepage.xml
				history = config['widgets']['widget'][0]['history'];

				//We set all the usefull variables used for getting alarms occurences 
				var alarmOptions = {};
				alarmOptions.sdp_id = config['widgets']['widget'][0]['sdp_id'];
				alarmOptions.current_month = currentMonth;
				alarmOptions.scale = history;
				alarmOptions.alarms = alarmArrayId;
				alarmOptions.alarms_name = alarmArrayName;
				Ext.Ajax.request({
					url : 'proxy/alarm_list.php',
					params : {
						task : 'GET_ALARMS_OCCURENCE_MONTH',
						params : {
							params : Ext.encode(alarmOptions)
						}
					},

					success : function(response) {

						var trenddata = new Array();
						var griddata = new Array();
						var donutdata = new Array();
						var error = false;

						//Decoding the json response
						var result = Ext.decode(response.responseText);

						if (!error) {
							var dataMin = null;
							var dataMax = null;
							var maxIndex = result['data'].length - 1;

							// Put the result datas in the data array
							for (var i = 0; i < result['data'].length; i++) {
								var month = result['data'][i]['month'];
								/**dateLabel = Ext.Date.format(Ext.Date.parse(
												month, 'Ym'), 'M-Y');**/
								var yearDate = month.substr(0, 4);
								var monthDate = month.substr(4, 6);
								var monthLabel = me.convertDateTxt(monthDate);
								dateLabel = monthLabel+'-'+yearDate;
							
								// Push the datas into the chart
								var dataToAdd = new Object();
								var donutEl = graphid + '_donut';
								me.lengedColor = Ext.getCmp(donutEl).series.items[0].colorArrayStyle;
								
								for (var j = 0; j < alarmArrayName.length; j++) {
									dataToAdd[alarmArrayName[j]] = parseFloat(result['data'][i][alarmArrayName[j]]);
								}
								dataToAdd.month = dateLabel;
								//Push data into array for trend, donut, and grid
								if (piechart && piechart != 0) {
									if (i == maxIndex) {
										for (var j = 0; j < alarmArrayName.length; j++) {
											var dataToAddToDonut = new Object();
											//on ajoute uniquement les les alarmes déclenché au moins une fois à donutdata permet de corriger
											//le bug 39961
											if(parseFloat(result['data'][i][alarmArrayName[j]])>0){
												dataToAddToDonut['data1'] = parseFloat(result['data'][i][alarmArrayName[j]]);
												dataToAddToDonut['name'] = alarmArrayName[j];
												donutdata.push(dataToAddToDonut);
												var positionColor = j;
											}
										}
										
										donut.store.loadData(donutdata);
									}
									//Dans le cas où on a un seul resultat (donut = 100%) correction bug 39961
									if(donutdata.length == 1){
										var currentColor = Ext.getCmp(donutEl).series.items[0].getLegendColor(positionColor);
										Ext.getCmp(donutEl).series.items[0].renderer = function(sprite, record, attr, index, store) {  
										    attr.fill = currentColor;  
										    return attr;  
										}; 
									}
									
									Ext.getCmp(donutEl).show();
								} else {
									var donutEl = graphid + '_donut';
									var trendEl = graphid + '_trend';
									
									Ext.apply(Ext.getCmp(trendEl), {
												flex : 1
											});
									Ext.apply(Ext.getCmp(donutEl), {
												flex : 0
											});
									Ext.getCmp(donutEl).hide();
								}

								trenddata.push(dataToAdd);
								griddata.push(dataToAdd);

								//Load data into respective store
								trend.store.loadData(trenddata);
								grid.store.loadData(griddata);

								//set dynamic scale
								var occurencemax = 30;
								for (var j = 0; j < alarmArrayName.length; j++) {
									var currentoccurence = trend.store
											.max(alarmArrayName[j]);
									if (currentoccurence > occurencemax) {
										occurencemax = currentoccurence;
									}
								}

								var perMax = Math.ceil(occurencemax / 5);
								var dynamicMax = Math.ceil(occurencemax
										+ perMax);
								var axisLeft = trend.axes.get(0);
								if (dynamicMax <= 10)
									dynamicMax = 10;
								axisLeft.maximum = dynamicMax;

								//Redraw trend and donut
								donut.redraw();
								if(trend.legend==false){
									trend.legend.redraw();
								}
								trend.redraw();

							}

						} else {
							Ext.Msg
									.alert('Error','Couldn\'t reach SQL server');

						}

					}

				});

			}
		});

	},
	convertDateTxt : function(month){
    	switch (month) {
		    case '01':
		        month_label = "Jan";
		        break;
		    case '02':
		        month_label = "Feb";
		        break;
		    case '03':
		        month_label = "Mar";
		        break;
		    case '04':
		        month_label = "Apr";
		        break;
		    case '05':
		        month_label = "May";
		        break;
		    case '06':
		        month_label = "Jun";
		        break;
		    case '07':
		        month_label = "Jul";
		        break;
		    case '08':
		        month_label = "Aug";
		        break;
	        case '09':
		        month_label = "Sep";
		        break;
	        case '10':
		        month_label = "Oct";
		        break;
	        case '11':
		        month_label = "Nov";
		        break;
		    case '12':
		        month_label = "Dec";
		        break;
		}
    return month_label;
	}
});