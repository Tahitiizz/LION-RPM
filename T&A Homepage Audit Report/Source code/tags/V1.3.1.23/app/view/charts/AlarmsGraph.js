Ext.define('homepage.view.charts.AlarmsGraph', {
	extend : 'Ext.panel.Panel',
	border : 0,
	alias : 'widget.alarmsgraph',
	requires : ['Ext.*'],
	layout : 'anchor',
	iconCls : 'icoGraph',
	cls : 'periodChart',
	padding : '5 5 5 5',
	initComponent : function() {

		//get the alarms names linked to the graph
		var alarmArrayName = this.fieldnames;

		var fields_pretty = [];
		var yfield_pretty = [];

		//this two array are used for creating dynamically the store
		fields_pretty.push('month');
		for (var j = 0; j < alarmArrayName.length; j++) {
			//Array as [month,alarms_name1,alarms_name2...]
			fields_pretty.push(alarmArrayName[j]);
			//Array as [alarms_name1,alarms_name2...]
			yfield_pretty.push(alarmArrayName[j]);
		}

		var t = this;
		var style = Ext.get('homepageStyle').dom.value;
		var kpiColor = '#000000';
		var axisColor = '#000000';
		var axisLabelcolor = '#686868';
		var legendColor = '#000000';
		if (style == 'access') {
			kpiColor = '#FFFFFF';
			axisColor = '#FFFFFF';
			axisLabelcolor = '#FFFFFF';
			legendColor = '#FFFFFF';
		}

		// Prevent from an ExtJS bug, turning graphbars to black
		var r = Math.floor(Math.random() * 100000)

		var col = ['url(#id' + r + ')'];

		Ext.define('Ext.chart.theme.Fancy', {
					extend : 'Ext.chart.theme.Base',

					constructor : function(config) {
						this.callParent([Ext.apply({
									//Column's graph color
									colors : ['#e41a1c', '#377eb8', '#4daf4a',
											'#984ea3', '#ff7f00', '#ffff33',
											'#a65628', '#f781bf', '#999999',
											'#fbb4ae', '#b3cde3', '#ccebc5',
											'#decbe4', '#fed9a6', '#ffffcc',
											'#e5d8bd', '#fddaec', '#f2f2f2'],
									//Set the column's contour color
									seriesThemes : [{
												fill : "#e41a1c"
											}, {
												fill : "#377eb8"
											}, {
												fill : "#4daf4a"
											}, {
												fill : "#984ea3"
											}, {
												fill : "#ff7f00"
											}, {
												fill : "#ffff33"
											}, {
												fill : "#a65628"
											}, {
												fill : "#f781bf"
											}, {
												fill : "#999999"
											}, {
												fill : "#fbb4ae"
											}, {
												fill : "#b3cde3"
											}, {
												fill : "#ccebc5"
											}, {
												fill : "#decbe4"
											}, {
												fill : "#fed9a6"
											}, {
												fill : "#ffffcc"
											}, {
												fill : "#e5d8bd"
											}, {
												fill : "#fddaec"
											}, {
												fill : "#f2f2f2"
											}],
									axis : {
										stroke : axisColor
									},
									axisLabelLeft : {
										fill : axisLabelcolor
									},
									axisLabelRight : {
										fill : axisLabelcolor
									},
									axisLabelBottom : {
										fill : axisLabelcolor
									},
									axisTitleLeft : {
										fill : axisLabelcolor,
										font : 'bold 15px Arial'
									},
									axisTitleRight : {
										fill : axisLabelcolor,
										font : 'bold 15px Arial'
									},
									axisTitleBottom : {
										fill : axisLabelcolor,
										font : 'bold 15px Arial'
									}
								}, config)]);
					}
				});

		// Create the data store for trend
		var trendstore = Ext.create('Ext.data.Store', {
					fields : fields_pretty,
					data : {}
				});

		// Create the data store for grid
		var gridstore = Ext.create('Ext.data.Store', {
					fields : fields_pretty,
					data : []
				});

		// Create the data store for the donut
		var storeDonut = Ext.create('Ext.data.Store', {
					fields : ['data1', 'name'],
					data : []
				});

		// we add dynamically	alarms to the store
		var a = ['header', 'dataIndex', 'flex'];
		var gridColumns = new Array({
					header : 'Month',
					dataIndex : 'month',
					flex : 1

				});
		for (var j = 0; j < alarmArrayName.length; j++) {
			var obj = {};
			obj[a[0]] = alarmArrayName[j];
			obj[a[1]] = alarmArrayName[j];
			obj[a[2]] = 1;
			gridColumns.push(obj);
		}

		this.items = [{
			border : 0,
			items : [{
				xtype : 'container',
				layout : 'hbox',
				id : t.id + '_hbox',
				items : [{
					id : t.id + '_trend',
					flex : 3,
					xtype : 'chart',
					layout : 'fit',
					anchor : '100%',
					height : 400,
					theme : 'Fancy',
					shadow : false,
					legend : false,
					extraStyle : {
						yAxis : {
							titleRotation : 90
						}
					},
					gradients : [{
								'id' : 'id' + r,
								'angle' : 0,
								stops : {
									0 : {
										color : 'rgb(0, 153, 255)'
									},
									100 : {
										color : 'rgb(0, 126, 194)'
									}
								}
							}],
					store : trendstore,
					axes : [{
								title : 'Number(s)',
								type : 'Numeric',
								position : 'left',
								fields : yfield_pretty,
								minimum : 0,
								maximum : 30,
								grid : true
							}, {
								type : 'Category',
								position : 'bottom',
								title : 'Date',
								fields : ['month'],
								label : {
									rotate : {
										degrees : 315
									},
									'text-anchor': 'middle',
		  							font: '10px Helvetica, sans-serif',
								}
							}],
					series : [{
						type : 'column',
						axis : 'left',
						tips : {
							trackMouse : true,
							width : 120,
							height : 40,
							renderer : function(storeItem, item) {
								this.setTitle('Month : '
										+ storeItem.get('month') + '<br>'
										+ 'Occurence = '
										+ String(item.value[1]));
							}
						},
						label : {
							display : 'outside',
							'text-anchor' : 'middle',
							font : 'bold 14px Arial',
							field : yfield_pretty,
							orientation : 'horizontal',
							color : '#333'
						},

						xField : 'month',
						yField : yfield_pretty,
						style : {
							opacity : 0.85
						}
					}]

				}, {
					id : this.id + '_donut',
					flex : 1,
					hidden: true,
					xtype : 'chart',
					anchor : "50%",
					theme : 'Fancy',
					padding : '0 0 0 10',
					height : 300,
					store : storeDonut,
					animate : true,
					shadow : true,
					insetPadding : 40,
					series : [{
						type : 'pie',
						field : 'data1',
						showInLegend : true,
						donut : 30,
						getLegendColor : function(index) {
							return ['#e41a1c', '#377eb8', '#4daf4a', '#984ea3',
									'#ff7f00', '#ffff33', '#a65628', '#f781bf',
									'#999999', '#fbb4ae', '#b3cde3', '#ccebc5',
									'#decbe4', '#fed9a6', '#ffffcc', '#e5d8bd',
									'#fddaec', '#f2f2f2'][index % 20];
						},
						renderer : function(sprite, record, attr, index, store) {
							return Ext.apply(attr, {
										fill : ['#e41a1c', '#377eb8',
												'#4daf4a', '#984ea3',
												'#ff7f00', '#ffff33',
												'#a65628', '#f781bf',
												'#999999', '#fbb4ae',
												'#b3cde3', '#ccebc5',
												'#decbe4', '#fed9a6',
												'#ffffcc', '#e5d8bd',
												'#fddaec', '#f2f2f2'][index
												% 20]
									});
						},
						tips : {
							trackMouse : true,
							width : 240,
							height : 50,
							renderer : function(storeItem, item) {
								var total = 0;
								storeDonut.each(function(rec) {
											total += rec.get('data1');
										});

								this.setTitle(storeItem.get('name')+ ': '+ Math.round(storeItem.get('data1')/ total * 100) + '%');
								var wTip = new Ext.util.TextMetrics(this, 240);
								hei = wTip.getHeight(this.title);
								this.setHeight(hei + 10);
								wTip = null;

							}
						},
						highlight : {
							segment : {
								margin : 20
							}
						},
						label : {
							field : 'name',
							display : 'rotate',
							contrast : true,
							font : '18px Arial',
							renderer : function(item) {
								var total = 0;
								storeDonut.each(function(rec) {
											total += rec.get('data1');
										});
								if (total != 0) {
									var store = storeDonut.findRecord('name',
											item);
									var storeItem = store.get('data1');
									return Math.round(storeItem / total * 100)
											+ '%';
								} else {
									return '';

								}

							}
						}
					}]
				}]
			}, {
				xtype : 'container',
				layout : 'anchor',
				items : [{
							title : 'Show',
							id : this.id + '_grid',
							xtype : 'gridpanel',
							collapsed : true,
							collapsible : true,
							listeners : {
								expand : function(dv, record, item, index, e) {
									var gridId = dv.id;
									var grid = Ext.getCmp(gridId);
									grid.setTitle('Hide');

								},
								collapse : function(dv, record, item, index, e) {
									var gridId = dv.id;
									var grid = Ext.getCmp(gridId);
									grid.setTitle('Show');
								}
							},
							collapsible : true,
							collapsed : true,
							anchor : "100% none",
							columns : gridColumns,
							padding : '10 50 40 50',
							store : gridstore
						}]
			}]
		}];

		this.callParent(arguments);
	},

	loadConfig : function() {

	}

});
