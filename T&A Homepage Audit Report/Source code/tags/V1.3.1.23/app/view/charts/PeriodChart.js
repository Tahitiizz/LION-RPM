Ext.define('homepage.view.charts.PeriodChart' ,{
    extend: 'Ext.panel.Panel',
    alias : 'widget.periodchart',

	requires: ['Ext.chart.*'],

	iconCls: 'icoGraph',
	cls: 'periodChart',
	layout: 'fit',
	padding: '5 5 5 5',
	
	initComponent: function() {
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
		
		// Override the chart legend in order to limit the legend width and set the style colors
		Ext.override(Ext.chart.Legend, { 			
			createItems: function() {
			
				var me = this,
			        chart = me.chart,
			        surface = chart.surface,
			        items = me.items,
			        padding = me.padding,
			        itemSpacing = me.itemSpacing,
			        spacingOffset = 2,
			        maxWidth = 0,
			        maxHeight = 0,
			        totalWidth = 0,
			        totalHeight = 0,
			        vertical = me.isVertical,
			        math = Math,
			        mfloor = math.floor,
			        mmax = math.max,
			        mmin = math.min,
			        index = 0,
			        i = 0,
			        len = items ? items.length : 0,
			        x, y, spacing, item, bbox, height, width;
				
			    //remove all legend items
			    if (len) {
			        for (; i < len; i++) {
			            items[i].destroy();
			        }
			    }
			    //empty array
			    items.length = [];
			    // Create all the item labels, collecting their dimensions and positioning each one
			    // properly in relation to the previous item			    
			    chart.series.each(function(series, i) {
			        if (series.showInLegend) {
			            Ext.each([].concat(series.yField), function(field, j) {
			            	
			            	// Override begin
			            	var text = series['title'];
			            	if (text != null) {
			            		var textWidth = t.measureText(text, this.labelFont).width;
			            		var textLimit = mfloor(surface.width / 4) - 20;			            		
			            		if (textWidth > textLimit) {
			            			var nbChar = mfloor((textWidth - textLimit) / 8) + 4;
			            			series['title'] = text.substr(0, text.length - nbChar) + '...';
			            		}
			            	}
			            	// Override end
			            		
			                item = Ext.create('Ext.chart.LegendItem', {
			                    legend: this,
			                    series: series,
			                    surface: chart.surface,
			                    yFieldIndex: j
			                });
			                bbox = item.getBBox();
			
			                //always measure from x=0, since not all markers go all the way to the left
			                width = bbox.width;
			                height = bbox.height;
			
			                if (i + j === 0) {
			                    spacing = vertical ? padding + height / 2 : padding;
			                }
			                else {
			                    spacing = itemSpacing / (vertical ? 2 : 1);
			                }
			                // Set the item's position relative to the legend box
			                item.x = mfloor(vertical ? padding : totalWidth + spacing);
			                item.y = mfloor(vertical ? totalHeight + spacing : padding + height / 2);
			
			                // Collect cumulative dimensions
			                totalWidth += width + spacing;
			                totalHeight += height + spacing;
			                maxWidth = mmax(maxWidth, width);
			                maxHeight = mmax(maxHeight, height);			
			            			                
			                items.push(item);
			            }, this);
			        }
			    }, me);			    
			    				    
			    // Store the collected dimensions for later
                maxWidth = vertical ? mfloor(surface.width / 4) : maxWidth; // overriden
			    me.width = mfloor((vertical ? maxWidth : totalWidth) + padding * 2);
			    if (vertical && items.length === 1) {
			        spacingOffset = 1;
			    }
			    me.height = mfloor((vertical ? totalHeight - spacingOffset * spacing : maxHeight) + (padding * 2));
			    me.itemHeight = maxHeight;
			},
			
			// Do not fill the legend
			createBox: function() {
		        var me = this,
		            box;

		        if (me.boxSprite) {
		            me.boxSprite.destroy();
		        }
		        
		        box = me.boxSprite = me.chart.surface.add(Ext.apply({
		            type: 'rect',
		            stroke: legendColor,
		            "stroke-width": me.boxStrokeWidth,
		            //fill: me.boxFill,
		            zIndex: me.boxZIndex
		        }, me.getBBox()));

		        box.redraw();
		    }
		});		
		
		// Override the chart legend item in order to set the style colors
		Ext.override(Ext.chart.LegendItem, { 
			createLegend: function(config) {
		        var me = this,
		            index = config.yFieldIndex,
		            series = me.series,
		            seriesType = series.type,
		            idx = me.yFieldIndex,
		            legend = me.legend,
		            surface = me.surface,
		            refX = legend.x + me.x,
		            refY = legend.y + me.y,
		            bbox, z = me.zIndex,
		            markerConfig, label, mask,
		            radius, toggle = false,
		            seriesStyle = Ext.apply(series.seriesStyle, series.style);
	
		        function getSeriesProp(name) {
		            var val = series[name];
		            return (Ext.isArray(val) ? val[idx] : val);
		        }
		        
		        label = me.add('label', surface.add({
		            type: 'text',
		            x: 20,
		            y: 0,
		            zIndex: z || 0,
		            font: legend.labelFont,
		            fill: legendColor,			// Overidden
		            text: getSeriesProp('title') || getSeriesProp('yField')
		        }));
	
		        // Line series - display as short line with optional marker in the middle
		        if (seriesType === 'line' || seriesType === 'scatter') {
		            if(seriesType === 'line') {
		                me.add('line', surface.add({
		                    type: 'path',
		                    path: 'M0.5,0.5L16.5,0.5',
		                    zIndex: z,
		                    "stroke-width": series.lineWidth,
		                    "stroke-linejoin": "round",
		                    "stroke-dasharray": series.dash,
		                    stroke: seriesStyle.stroke || '#000',
		                    style: {
		                        cursor: 'pointer'
		                    }
		                }));
		            }
		            if (series.showMarkers || seriesType === 'scatter') {
		                markerConfig = Ext.apply(series.markerStyle, series.markerConfig || {});
		                me.add('marker', Ext.chart.Shape[markerConfig.type](surface, {
		                    fill: markerConfig.fill,
		                    x: 8.5,
		                    y: 0.5,
		                    zIndex: z,
		                    radius: markerConfig.radius || markerConfig.size,
//		                    style: {
//		                        cursor: 'pointer'
//		                    }
		                }));
		            }
		        }
		        // All other series types - display as filled box
		        else {
		            me.add('box', surface.add({
		                type: 'rect',
		                zIndex: z,
		                x: 0,
		                y: 0,
		                width: 12,
		                height: 12,
		                fill: series.getLegendColor(index),
//		                style: {
//		                    cursor: 'pointer'
//		                }
		            }));
		        }
		        
		        me.setAttributes({
		            hidden: false
		        }, true);
		        
		        bbox = me.getBBox();
		        
		        mask = me.add('mask', surface.add({
		            type: 'rect',
		            x: bbox.x,
		            y: bbox.y,
		            width: bbox.width || 20,
		            height: bbox.height || 20,
		            zIndex: (z || 0) + 1000,
		            fill: '#f00',
		            opacity: 0,
//		            style: {
//		                'cursor': 'pointer'
//		            }
		        }));
	
		        //add toggle listener
		        me.on('mouseover', function() {
		            label.setStyle({
		                'font-weight': 'bold'
		            });
//		            mask.setStyle({
//		                'cursor': 'pointer'
//		            });
		            series._index = index;
		            series.highlightItem();
		        }, me);
	
		        me.on('mouseout', function() {
		            label.setStyle({
		                'font-weight': 'normal'
		            });
		            series._index = index;
		            series.unHighlightItem();
		        }, me);
		        
		        if (!series.visibleInLegend(index)) {
		            toggle = true;
		            label.setAttributes({
		               opacity: 0.5
		            }, true);
		        }
	
		        me.on('mousedown', function() {
		            if (!toggle) {
		                series.hideAll();
		                label.setAttributes({
		                    opacity: 0.5
		                }, true);
		            } else {
		                series.showAll();
		                label.setAttributes({
		                    opacity: 1
		                }, true);
		            }
		            toggle = !toggle;
		        }, me);
		        me.updatePosition({x:0, y:0}); //Relative to 0,0 at first so that the bbox is calculated correctly
		    }
		});
		
		// Create the data store
		var store = Ext.create('Ext.data.Store', {
			//convert NaN values from data1 and data2 to undefined to avoid data on graph for null values
	    	fields: [{name :'data1', type: 'float',convert: function(value, record) { return (isNaN(value) ? undefined : value);}}, 
	    	         {name :'data2', type: 'float',convert: function(value, record) { return (isNaN(value) ? undefined : value);}}, 
	    	         'date',
	    	         'time', 
	    	         'timeAxis', 
	    	         'warning', 
	    	         'alert', 
	    	         'gaugeProduct', 
	    	         'trendProduct', 
	    	         'gaugeRawKpi', 
	    	         'trendRawKpi', 
	    	         'timeAgregation', 
	    	         'networkAgregation', 
	    	         'networkName', 
	    	         'networkAxe3Agregation',
	    	         'networkAxe3Name',
	    	         'period'],
	    	data  : []
		});

	
		// Prevent from an ExtJS bug, turning graphbars to black
		var r = Math.floor(Math.random() * 100000)
		
		var col = ['url(#id' + r + ')'];

		Ext.define('Ext.chart.theme.Fancy', {
    		extend: 'Ext.chart.theme.Base',
        
    		constructor: function(config) {
    			this.callParent([Ext.apply({
        			colors: col,
        			axis: {
    					stroke: axisColor
    				},
    				axisLabelLeft: {
    					fill: axisLabelcolor
    				},
    				axisLabelRight: {
    					fill: axisLabelcolor
    				},
    				axisLabelBottom: {
    					fill: axisLabelcolor
    				},
        			axisTitleLeft: {
    					fill: axisLabelcolor,
                        font: 'normal 14px Arial'
                    },
                    axisTitleRight: {
                    	fill: axisLabelcolor,
                        font: 'normal 14px Arial'
                    }
    			}, config)]);
    		}
		});

		
		
		// Add the items
		this.items = [
			{
				id: this.id + '_periodObject',
				xtype: 'chart',
				theme: 'Fancy',
				animate: {
        			easing: 'bounceOut',
        			duration: 750
    			},
				insetPadding: 5,
				store: store,
				legend: {
        			position: 'right'
    			},
				shadow: false,
				listeners: {
		              resize: {
		                  fn: function(el) {
					          var showinlegend = el.series.items[0].showInLegend;
					          if(showinlegend == false){
					          var periodObjectId = el.id;
			                      var bboxY = Ext.getCmp(periodObjectId).legend.getBBox().y;
					              var newY = bboxY-8;
					              Ext.getCmp(periodObjectId).legend.boxSprite.setAttributes({y:newY},true);
					          }
					            
		                  }
		              }
		        },
				extraStyle: {
					yAxis: {
						titleRotation: 90
					}
				},
				
				/*
				listeners:{
					'afterrender':function(chart){
						console.log('chart beforerender');
						var myMask = new Ext.LoadMask(chart, { msg: 'Loading...'});
				        myMask.show();
				        setTimeout(function () {
				            myMask.hide();
				        }, 2000)
					}
				},
				
				*/
				gradients: [
    				{
        				'id': 'id' + r,
        				'angle': 0,
        				stops: {
        					0: {
            					color: 'rgb(0, 153, 255)'
        					},
        					100: {
            					color: 'rgb(0, 126, 194)'
        					}
        				}
    				}
				],
				axes: [
					{
        				type: 'Numeric',
	    				position: 'left',
						fields: ['data1', 'warning', 'alert'],
	    				label: {
							renderer: function(number) {
								if (typeof(number) == 'number') {
									if (number >= 1000000000) {
										var mant = Math.round(number / 1000000);
										return (mant / 1000) + ' B';
									} else if (number >= 1000000) {
										var mant = Math.round(number / 1000);
										return (mant / 1000) + ' M';
									} else if (number >= 1000) {
										return (Math.round(number) / 1000) + ' K';
									} else {
										return Math.round(number * 100) / 100;
									}
								}						
							}
                        },
                        /*
                        grid: {
                            odd: {
                                opacity: 0.5,
                                //fill: '#ddd',
                                zIndex: 0,
                                stroke: '#bbb',
                                'stroke-width': 0.5
                            }
                        }
                        */
                        
						grid: true,
					},
					{
                        type: 'Numeric',
	                    position: 'right',
	                    fields: ['data2'],
	                    label: {
							renderer: function(number) {
								if (typeof(number) == 'number') {
									if (number >= 1000000000) {
										var mant = Math.round(number / 1000000);
										return (mant / 1000) + ' B';
									} else if (number >= 1000000) {
										var mant = Math.round(number / 1000);
										return (mant / 1000) + ' M';
									} else if (number >= 1000) {
										return (Math.round(number) / 1000) + ' K';
									} else {
										return Math.round(number * 100) / 100;
									}
								}						
							}
                    	}
                	},
					{
						type: 'Category',
        				position: 'bottom',
        				fields: ['timeAxis'],
						label: {
  							rotate: { 
  								degrees: -45,
  								
  							},
  							'text-anchor': 'middle',
  							font: '10px Helvetica, sans-serif',
        					renderer: function(time) {
								if (time != null && time != '') {
									return time;
								} else {
									return '';
								}
        					}
        				}
					}
				],
				series: [
					{
                        type: 'column',
                        axis: 'right',
                        tips: {
	                        autoHide: true,
	                        closable: false,
	                        width: 180,
	                        height: 90,
	                        constrainPosition: true,
	                        anchor: 'left',
	                        anchorOffset: '20',
	                        renderer: function(storeItem, item) {
	                        	var html='<a href="#">' + 
                        		item.series.title + '<br>' +
                        		storeItem.get('time') + '<br>' + 
                        		storeItem.get('data2') + '</a>';
	                        	
                                this.setTitle(html);
                                
                                var measured=t.measureText(html, this.labelFont );
            	                //tip.width = 450;
            	                this.width=measured.width+40;
            	                //tip.height = 270;
            	                this.height=measured.height+15;
                                
                                if (typeof this.header != 'undefined') {
                                	this.header.on('click', function(e){
                                        e.stopEvent();
                                        var url = 'proxy/openDashboard.php'
                                        url += '?productId=' + storeItem.get('trendProduct');
                                        url += '&raw_kpi=' + storeItem.get('trendRawKpi');
                                        url += '&type=' + storeItem.get('trendType');
                            			url += '&time_agregation=' + storeItem.get('timeAgregation');
                            			url += '&time_value=' + storeItem.get('date');
                            			url += '&network_agregation=' + storeItem.get('networkAgregation');
                            			url += '&network_name=' + storeItem.get('networkName');
                            			url += '&period=' + storeItem.get('period');
                            			if (storeItem.get('networkAxe3Agregation') != null) {
                            			    url += '&network_3_axe_agregation=' + storeItem.get('networkAxe3Agregation');
                            			}
                            			if (storeItem.get('networkAxe3Name') != null) {
                            			    url += '&network_3_axe_name=' + storeItem.get('networkAxe3Name');
                            			}
                                        
                            			window.open(url, "homepage_selecteur_dashboard", "menubar=no, status=no, scrollbars=yes, width=450, height=450");
                                    }, this, {delegate:'a'});
                                }
	                        } 
                        },
                        xField: 'time',
                        yField: 'data2'
                    },
					{
						title: 'Warning',
                        type: 'line',
                        axis: 'left',
                        xField: 'time',
                        yField: 'warning',
                        style: {
                            stroke: '#FF8C00',
                            'stroke-width': 2,
                            'stroke-dasharray': 10
                        },
						showMarkers: false
                    },
					{
						title: 'Critical',
                        type: 'line',
                        axis: 'left',
                        xField: 'time',
                        yField: 'alert',
                        style: {
                            stroke: '#FF0000',
                            'stroke-width': 2,
                            'stroke-dasharray': 10
                        },
						showMarkers: false
                    },
					{
        				type: 'line',
        				axis: 'left',
        				tips: {
	                    	autoHide: true,
	                        closable: false,
        					width: 180,
        					height: 90,
        					anchor: 'top',
        					constrainPosition: true,
        					renderer: function(storeItem, item) {  
        						
        						var html='<a href="#">' + 
                            		item.series.title + '<br>' +
                            		storeItem.get('time') + '<br>' + 
                            		storeItem.get('data1') + '</a>';
        						
        						this.setTitle(html);
        						
        						var measured=t.measureText(html, this.labelFont );
             	                //tip.width = 450;
             	                this.width=measured.width+40;
             	                //tip.height = 270;
             	                this.height=measured.height+15;
                                
                                if (typeof this.header != 'undefined') {
                                    this.header.on('click', function(e){
                                        e.stopEvent();
                                        var url = 'proxy/openDashboard.php'
                                        url += '?productId=' + storeItem.get('gaugeProduct');
                                        url += '&raw_kpi=' + storeItem.get('gaugeRawKpi');
                                        url += '&type=' + storeItem.get('gaugeType');
                            			url += '&time_agregation=' + storeItem.get('timeAgregation');
                            			url += '&time_value=' + storeItem.get('date');
                            			url += '&network_agregation=' + storeItem.get('networkAgregation');
                            			url += '&network_name=' + storeItem.get('networkName');
                            			if (storeItem.get('networkAxe3Agregation') != null) {
                            				url += '&network_3_axe_agregation=' + storeItem.get('networkAxe3Agregation');
                            			}
                            			if (storeItem.get('networkAxe3Name') != null) {
                            				url += '&network_3_axe_name=' + storeItem.get('networkAxe3Name');
                            			}                                        
                            			window.open(url, "homepage_selecteur_dashboard", "menubar=no, status=no, scrollbars=yes, width=450, height=450");
                                    }, this, {delegate:'a'});                                	
                                }
	                        }
        				},
        				xField: 'time',
        				yField: 'data1',
						style: {
							stroke: kpiColor,
							'stroke-width': 2 
						},
						markerConfig: {
							type: 'cross',
							fill: '#000000'
						}							
    				}
				]
			}	
		];		

		this.callParent(arguments);	
	},
	
	measureText: function(pText, pStyle) {
	    var lDiv = document.createElement('lDiv');

	    document.body.appendChild(lDiv);

	    if (pStyle != null) {
	        lDiv.style = pStyle;
	    }
	    lDiv.style.position = 'absolute';
	    lDiv.style.left = -1000;
	    lDiv.style.top = -1000;

	    lDiv.innerHTML = pText;

	    var lResult = {
	        width: lDiv.clientWidth,
	        height: lDiv.clientHeight
	    };

	    document.body.removeChild(lDiv);
	    lDiv = null;

	    return lResult;
	}
	
});

