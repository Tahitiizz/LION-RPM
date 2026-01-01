Ext.define('homepage.controller.charts.AuditReport', {
	extend : 'Ext.app.Controller',

	views : ['charts.AuditReport'],

	config : null,

	graphs : null,

	init : function() {

		this.control({
					'auditreport' : {
						load : this.load
					},
					'button[action=generateAuditReport]' : {
						click : this.load
					}
				});

	},

	load : function(config) {
		var me = this;
		
		me.graphs = null;
		me.graphs = new Array();
		
		if (config == null || Ext.isObject(config.config)) {
			config = me.config;
		} else {
			me.config = config;
		}
		
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		tabId += '_chart1';
		/**
		var chartsPanel = Ext.getCmp(tabId);
		var charts = Ext.getCmp(tabId).query('chart');

		for (var c = 0; c < charts.length; c++) {
			var trendId = charts[c].id;
			var trend = Ext.getCmp(trendId);
			trend.store.sync();
		}

		var time = Ext.getCmp(tabId + '_TimeSelector_AuditReport').value;
		Ext.Date.format(time, 'Ym');
		if (time != undefined) {
			var twoDigitMonth = ((time.getMonth() + 1) >= 10) ? (time
					.getMonth() + 1) : '0' + (time.getMonth() + 1);
			var newtime = time.getFullYear() + twoDigitMonth;
			me.load(null, newtime);
		} else {
			console.log('no time selected');
			//TODO get last date
		}
		**/
		//TODO revoir les ids
		//get all the components created in the audit report view
		var auditreportRi = Ext.getCmp(tabId + '_reliability_indicator');
		var auditreportSg = Ext.getCmp(tabId + '_summary_graph');
		var reportuploadPanel = Ext.getCmp(tabId + '_report_upload');
		var auditreportPanel = Ext.getCmp(tabId + '_alarms_graph_panel');

		//Reset of all component before starting
		auditreportPanel.removeAll();

		var timeselector = Ext.getCmp(tabId + '_TimeSelector_AuditReport');
		var time = timeselector.value;

		//User has selected a date
		if (time != undefined) {
			//load ri graph		
			time = Ext.Date.format(time, 'Ym');
			auditreportRi.fireEvent('load', config, time);

			//load alarms graphs
			if (config['widgets']['widget'][0]['graph_list']['graph'].length == undefined) {
				//only one graph set in conf
				var obj = config['widgets']['widget'][0]['graph_list']['graph'];
				if (obj.type == 'alarm') {

					//get graph id with index
					var graphid = tabId+'_'+obj['@attributes']['id'];
					//create an AlarmsGraph object
					var graph_alarms_ids = new Array();
					
					
					if(typeof(obj['alarms_display']['id'])=='string'){
						graph_alarms_ids.push(obj['alarms_display']['id']);
					}
					else{
						Ext.Object.each(obj['alarms_display']['id'],
								function(index, el) {
									graph_alarms_ids.push(el);
								});
					}
										
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
									var alarm_names_obj = Ext
											.decode(response.responseText);
									var alarm_names = new Array();
									
									Ext.Object.each(alarm_names_obj, function(
													index, obj) {
												alarm_names
														.push(obj.alarm_name);
											});

									//send fieldnames to initComponent
									var graph = Ext.create(
											'homepage.view.charts.AlarmsGraph',
											{
												id : graphid,
												fieldnames : alarm_names
											});

									var newGraph = {};
									newGraph['config'] = config;
									newGraph['time'] = time;
									newGraph['graphid'] = graphid;
									newGraph['graph'] = graph;

									me.graphs.push(newGraph);

									me.insertGraphs(tabId
											+ '_alarms_graph_panel');

								}

							});

				}

			} else {
				var index = 0;
				//several graphs defined in conf
				Ext.Object.each(
						config['widgets']['widget'][0]['graph_list']['graph'],
						function(index, obj) {
							if (obj.type == 'alarm') {
								//get graph id with index
								var graphid = tabId+'_'+obj['@attributes']['id'];
								//create an AlarmsGraph object

								var graph_alarms_ids = new Array();
								
								if(typeof(obj['alarms_display']['id'])=='string'){
									graph_alarms_ids.push(obj['alarms_display']['id']);
								}
								else{
									Ext.Object.each(obj['alarms_display']['id'],
											function(index, el) {
												graph_alarms_ids.push(el);
											});
								}
								
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
										var alarm_names_obj = Ext
												.decode(response.responseText);

										var alarm_names = new Array();

										Ext.Object.each(alarm_names_obj,
												function(index, obj) {
													alarm_names
															.push(obj.alarm_name);
												});

										//send fieldnames to initComponent
										var graph = Ext
												.create(
														'homepage.view.charts.AlarmsGraph',
														{
															id : graphid,
															fieldnames : alarm_names
														});

										var newGraph = {};
										newGraph['config'] = config;
										newGraph['time'] = time;
										newGraph['graphid'] = graphid;
										newGraph['graph'] = graph;
										newGraph['index'] = index++;

										me.graphs.push(newGraph);

										if (me.graphs.length == config['widgets']['widget'][0]['graph_list']['graph'].length) {
											//load graphs
											me.insertGraphs(tabId
													+ '_alarms_graph_panel');
										}

									}

								});

							}
						});
			}

			//load summary graph
			auditreportSg.fireEvent('load', config, time);
			reportuploadPanel.fireEvent('load', config);
		}
		//First loading : no date selection, we get the date of last integration
		else {
			
			Ext.Ajax.request({
				url : 'proxy/configuration.php',
				params : {
					task : 'LAST_DATE',
					sdp_id : config['widgets']['widget'][0]['sdp_id']
				},

				success : function(response) {
					lastintegrationdate = response.responseText;
					lastintegrationdate = lastintegrationdate.substring(0, 6);

					//set time selector value
					var timepretty = Ext.Date.format(Ext.Date.parse(
									lastintegrationdate, 'Ym'), 'M Y')
					timeselector.setValue(timepretty);

					//load ri graph
					auditreportRi
							.fireEvent('load', config, lastintegrationdate);

					//create alarms graphs
					if (config['widgets']['widget'][0]['graph_list']['graph'].length == undefined) {
						//only one graph set in conf
						var obj = config['widgets']['widget'][0]['graph_list']['graph'];
						if (obj.type == 'alarm') {

							//get graph id with index
							var graphid = tabId+'_'+obj['@attributes']['id'];
							
							//create an AlarmsGraph object
							var graph_alarms_ids = new Array();
						
							if(typeof(obj['alarms_display']['id'])=='string'){
								graph_alarms_ids.push(obj['alarms_display']['id']);
							}
							else{
								Ext.Object.each(obj['alarms_display']['id'],
										function(index, el) {
											graph_alarms_ids.push(el);
										});
							}
				
							var graphOptions = {};
							graphOptions.sdp_id = config['widgets']['widget'][0]['sdp_id'];
							graphOptions.alarm_ids = graph_alarms_ids;

							Ext.Ajax.request({
										url : 'proxy/alarm_list.php',
										params : {
											task : 'GET_ALARMS_NAMES',
											params : {
												params : Ext
														.encode(graphOptions)
											}
										},

										success : function(response) {
											var alarm_names_obj = Ext
													.decode(response.responseText);

											var alarm_names = new Array();
											
											if(typeof(alarm_names_obj)=='string'){
												alarm_names
													.push(alarm_names_obj.alarm_name);
											}
											else{
												Ext.Object.each(alarm_names_obj,
														function(index, obj) {
															alarm_names
																	.push(obj.alarm_name);
														});
											}
											
											//send fieldnames to initComponent
											var graph = Ext
													.create(
															'homepage.view.charts.AlarmsGraph',
															{
																id : graphid,
																fieldnames : alarm_names
															});

											var newGraph = {};
											newGraph['config'] = config;
											newGraph['time'] = lastintegrationdate;
											newGraph['graphid'] = graphid;
											newGraph['graph'] = graph;

											me.graphs.push(newGraph);

											me.insertGraphs(tabId
													+ '_alarms_graph_panel');

										}

									});

						}

					} else {
						//several graphs defined in conf
						var index = 0;

						Ext.Object
								.each(
										config['widgets']['widget'][0]['graph_list']['graph'],
										function(index, obj) {
											if (obj.type == 'alarm') {

												//get graph id with index
												var graphid = tabId+'_'+obj['@attributes']['id'];

												//create an AlarmsGraph object
												var graph_alarms_ids = new Array();
												
												if(typeof(obj['alarms_display']['id'])=='string'){
													graph_alarms_ids.push(obj['alarms_display']['id']);
												}
												else{
													Ext.Object.each(obj['alarms_display']['id'],
															function(index, el) {
																graph_alarms_ids.push(el);
															});
												}
												
												var graphOptions = {};
												graphOptions.sdp_id = config['widgets']['widget'][0]['sdp_id'];
												graphOptions.alarm_ids = graph_alarms_ids;

												Ext.Ajax.request({
													url : 'proxy/alarm_list.php',
													params : {
														task : 'GET_ALARMS_NAMES',
														params : {
															params : Ext
																	.encode(graphOptions)
														}
													},

													success : function(response) {
														var alarm_names_obj = Ext
																.decode(response.responseText);

														var alarm_names = new Array();

														Ext.Object.each(alarm_names_obj,function(index,obj) {alarm_names.push(obj.alarm_name);});

														//send fieldnames to initComponent
														var graph = Ext.create(
																		'homepage.view.charts.AlarmsGraph',
																		{
																			id : graphid,
																			fieldnames : alarm_names
																		});

														var newGraph = {};
														newGraph['config'] = config;
														newGraph['time'] = lastintegrationdate;
														newGraph['graphid'] = graphid;
														newGraph['graph'] = graph;
														newGraph['index'] = index++;

														me.graphs
																.push(newGraph);

														if (me.graphs.length == config['widgets']['widget'][0]['graph_list']['graph'].length) {

															//load graphs
															me.insertGraphs(tabId+ '_alarms_graph_panel');
														}

													}

												});

											}
										});
					}

					//load summary graph
					auditreportSg
							.fireEvent('load', config, lastintegrationdate);
					reportuploadPanel.fireEvent('load', config)
				}
			});

		}
	},

	// Add the grids from the grid array to the panel
	insertGraphs : function(panel) {
		var me = this;
		var panel = Ext.getCmp(panel);

		me.graphs.sort(function(a, b) {
					return a['index'] > b['index'];

				});

		// Add the graphs
		for (g = 0; g < me.graphs.length; g++) {
			var graphToAdd = me.graphs[g]['graph'];

			panel.add(graphToAdd);
			graphToAdd.fireEvent('load', me.graphs[g]['config'],
					me.graphs[g]['time'], me.graphs[g]['graphid']);
		}
	},

	generateAuditReport : function(config) {
		var me = this;
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		tabId += '_chart1';
		
		var chartsPanel = Ext.getCmp(tabId);
		var charts = Ext.getCmp(tabId).query('chart');

		for (var c = 0; c < charts.length; c++) {
			var trendId = charts[c].id;
			var trend = Ext.getCmp(trendId);
			trend.store.sync();
		}

		var time = Ext.getCmp(tabId + '_TimeSelector_AuditReport').value;
		Ext.Date.format(time, 'Ym');
		if (time != undefined) {
			var twoDigitMonth = ((time.getMonth() + 1) >= 10) ? (time
					.getMonth() + 1) : '0' + (time.getMonth() + 1);
			var newtime = time.getFullYear() + twoDigitMonth;
			//auditreportRi.fireEvent('load', config, lastintegrationdate);
			//me.load(config, newtime);
			me.load(config);

		} else {
			console.log('no time selected');
			//TODO get last date
		}
	}
});
