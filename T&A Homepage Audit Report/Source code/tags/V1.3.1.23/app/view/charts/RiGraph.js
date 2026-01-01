Ext.define('homepage.view.charts.RiGraph', {
	extend : 'Ext.panel.Panel',
	alias : 'widget.rigraph',
	cls: 'periodChart',
	requires : ['Ext.*'],
	layout: 'anchor',

	initComponent : function() {
		var me = this;
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
			
		// Prevent from an ExtJS bug, turning graphbars to black
		var r = Math.floor(Math.random() * 100000)
		var col = ['url(#id' + r + ')'];
		
		Ext.define('Ext.chart.theme.Fancy', {
    		extend: 'Ext.chart.theme.Base',
        
    		constructor: function(config) {
    			this.callParent([Ext.apply({
    				//series color pre defined
        			colors: ['#b2beb5'],
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
        			
                    axisTitleRight: {
                    	fill: axisLabelcolor,
                        font: 'bold 15px Arial'
                    },
                     axisTitleBottom: {
                    	fill: axisLabelcolor,
                        font: 'bold 15px Arial'
                    },
                    axisTitleLeft: {
    					fill: axisLabelcolor,
                        font: 'bold 15px Arial'
                        
                    },
                    series: {
               			 'stroke-width': 0
            		}
    			}, config)]);
    		}
		});
		
		
		var store = Ext.create('Ext.data.Store', {
					id : 'RiStore',
					fields : ['day', 'ri_value', 'time'],
					data : []
				});
		
		
		this.items = [
  			{
				id : this.id+'_trend',
				xtype : 'chart',
				anchor: "100%",
				height : 400,
				theme : 'Fancy',
				shadow : false,
				legend: false,
				extraStyle : {
					yAxis : {
						//Change the x Axis' label rotation
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
				store : store,
				axes : [{
							
							type : 'Numeric',
							position : 'left',
							fields : ['ri_value'],
							minimum : 0,
							maximum : 100,
							title : 'Reliability (%)',
							grid : true
							
						}, {
							type : 'Category',
							position : 'bottom',
							title : 'Date',
							fields : ['time'],
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
						autoHide : true,
						width : 180,
						height : 60,
						constrainPosition : true,
						anchor : 'left',
						anchorOffset : '20',
						renderer : function(storeItem, item) {
							var date = storeItem.get('time');
							var year = date.substring(0,4);
							var month = date.substring(4,6);
							var day = date.substring(6,8);
							var prettydate = year+'/'+month+'/'+day
							this.setTitle('RI_CAPTURE_DURATION' + '<br>'+
									'Day : '+ prettydate + '<br>'+
									'Value : '+ storeItem.get('ri_value'));
						}
					},
					label : {
						display : 'outside',
						'text-anchor' : 'middle',
						field : 'ri_value',
						font : 'bold 14px Arial',
						renderer : function(sprite) {
							//We check if our column value is not empty
							if(sprite != ''){
								//we round this value and return it
								var newnumber = Math.round(sprite*Math.pow(10,0));
								return newnumber;
							}
							else{
								return '';
							}
							
							
						},
						orientation : 'horizontal',
						color : '#333'
					},
					xField : 'time',
					yField : 'ri_value',
					style: {
		                opacity: 0.85
		            }

				}]
		
  			}
  		];
		this.callParent(arguments);
	
	}
});
