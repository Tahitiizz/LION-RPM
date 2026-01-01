Ext.define('homepage.view.charts.SummaryGraph', {
			extend : 'Ext.panel.Panel',
			alias : 'widget.summarygraph',
			requires : ['Ext.chart.*'],
			layout : 'anchor',
			iconCls : 'icoGraph',
			cls : 'periodChart',
			padding : '5 5 5 5',

			initComponent : function() {
				var t = this;

				// Set the colors according to the Homepage style
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

				// Create the data store
				var trendstore = Ext.create('Ext.data.Store', {
							fields : ['time', 'warning', 'penalty'],
							data : []
						});

				// Prevent from an ExtJS bug, turning graphbars to black
				var r = Math.floor(Math.random() * 100000)

				var col = ['url(#id' + r + ')'];

				Ext.define('Ext.chart.theme.Fancy', {
							extend : 'Ext.chart.theme.Base',

							constructor : function(config) {
								this.callParent([Ext.apply({
											//Column's graph color
											colors : ['#42BB0E', '#FF0040'],
											//Set the column's contour color
											seriesThemes : [{
														fill : "#42BB0E"
													}, {
														fill : "#FF0040"
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

				// Create the data store
				var gridstore = Ext.create('Ext.data.Store', {
							fields : ['time', 'warning', 'penalty', 'reftime'],
							data : []
						});

				var gridColumns = new Array({
							header : 'Month',
							dataIndex : 'time',
							flex : 1,
							renderer : function(date) {
								var yearDate = date.substr(0, 4);
								var monthDate = date.substr(4, 6);
								var monthLabel = t.convertDateTxt(monthDate);
								dateLabel = monthLabel+'-'+yearDate;
								return dateLabel;
								/**return Ext.Date.format(Ext.Date.parse(date,
												'Ym'), 'M-Y');**/
							}
						}, {
							header : 'Warning cells',
							dataIndex : 'warning',
							flex : 1
						}, {
							header : 'Penalty cells',
							dataIndex : 'penalty',
							flex : 1
						}, {
							header : 'Reference month',
							dataIndex : 'reftime',
							flex : 1,
							renderer : function(date) {
								var yearDate = date.substr(0, 4);
								var monthDate = date.substr(4, 6);
								var monthLabel = t.convertDateTxt(monthDate);
								dateLabel = monthLabel+'-'+yearDate;
								return dateLabel;
								/**return Ext.Date.format(Ext.Date.parse(date,
												'Ym'), 'M-Y');**/
							}
						});

				// Add the items
				this.items = [{
					id : this.id + '_trend',
					anchor : "100%",
					height : 400,
					xtype : 'chart',
					theme : 'Fancy',
					highlight : true,
					insetPadding : 5,
					store : trendstore,
					legend : false,
					shadow : false,
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
					axes : [{
								type : 'Numeric',
								position : 'left',
								fields : ['warning', 'penalty'],
								title : 'Nb of Cells',
								grid : true,
								minimum : 0,
								maximum : 100,
								label : {
									renderer : Ext.util.Format
											.numberRenderer('0')
								}
							}, {
								type : 'Category',
								position : 'bottom',
								fields : ['time'],
								title : 'Date',
								label : {
									renderer : function(date) {
										var yearDate = date.substr(0, 4);
										var monthDate = date.substr(4, 6);
										var monthLabel = t.convertDateTxt(monthDate);
										dateLabel = monthLabel+'-'+yearDate;
										return dateLabel;
										/**return Ext.Date.format(Ext.Date.parse(
														date, 'Ym'), 'M-Y');**/
									},
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
						gutter : 80,
						xField : 'time',
						yField : ['warning', 'penalty'],
						//stacked: true,
						tips : {
							trackMouse : true,
							width : 120,
							height : 60,
							renderer : function(storeItem, item) {
								var date = storeItem.get('time');
								var yearDate = date.substr(0, 4);
								var monthDate = date.substr(4, 6);
								var monthLabel = t.convertDateTxt(monthDate);
								dateLabel = monthLabel+'-'+yearDate;
										
								this.setTitle('Month : '
										+ dateLabel+ '<br>'
										/**+ Ext.Date.format(Ext.Date.parse(
														storeItem.get('time'),
														'Ym'), 'M-Y') + '<br>'**/
										+ 'Warning : '
										+ storeItem.get('warning') + '<br>'
										+ 'Penalty : '
										+ storeItem.get('penalty'));
							}
						},
						label : {
							display : 'outside',
							'text-anchor' : 'middle',
							field : ['warning', 'penalty'],
							font : 'bold 14px Arial',
							orientation : 'horizontal',
							color : '#333'
						},
						style : {
							opacity : 0.85
						}
					}]
				}, {
					title : 'Show',
					xtype : 'gridpanel',
					id : this.id + '_grid',
					anchor : "100% none",
					collapsible : true,
					collapsed : true,
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
					columns : gridColumns,
					padding : '30 50 40 50',
					store : gridstore,
					id : this.id + '_grid'
				}];

				this.callParent(arguments);
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
