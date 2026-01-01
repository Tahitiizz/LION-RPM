Ext.define('homepage.controller.charts.PeriodChart', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.PeriodChart'
	],

	// Initialize the event handlers
    init: function() {
        this.control({            
            'periodchart': {
            	load: this.load            	
            }
        });
    },
	
	load: function(config, gauge) {
    		var me = this;
    		// get tabId from gauge id, because sometimes with autoRefresh tabId does not match gauge id
			//var tabId = Ext.getCmp('tabPanel').getActiveTab().getId(); 
    		var tabId = Ext.getCmp(gauge).up('panel').up('panel').id;
    		
    		var periodChart = Ext.getCmp(Ext.getCmp(gauge).target);
    		
    		var nbtrend=Ext.get(tabId).query(".periodChart").length;
    		
    		//template2 check, more than one trend
    		if(nbtrend==1)
    		{
    			//BZ35078 we now recreate the chart on every load
            	//get current tab
            	var tab=Ext.getCmp(tabId);

            	//get chart column flex
            	var colflex=periodChart.flex;

            	//get chart panel parent
            	var parent=Ext.getCmp(Ext.getCmp(gauge).target).up('panel');

            	//get chart panel parent flex
            	var rowflex=parent.flex;
            	
            	//remove chart parent panel
            	parent.removeAll(true);
            	parent.destroy();

            	//create a new row to replace remove one
            	var row = Ext.create('Ext.panel.Panel', {
            						layout: {
            							type: 'hbox',
            							align: 'stretch',
            							pack: 'start'
            						},
            						flex: rowflex,
            						cls: 'x-panel-no-border'
            					});

            	//add this row to the panel
            	row.add(Ext.create('homepage.view.charts.PeriodChart',{id:tabId+'_periodchart',flex:colflex}));
            	tab.add(row);
            	
    		} 	

    		if (typeof(config) != 'undefined' /*&& typeof(config['kpis']['kpi'][1]['id']).length != 'undefined'*/){            	//get the new component       	
            	var periodChart = Ext.getCmp(Ext.getCmp(gauge).target);
            	
	        	periodChart.setTitle(config['title']);
	        	var periodObjectId = periodChart.id + '_periodObject'; 
	    		// Create the request parameters
	        	
	        	// Time parameters
	        	if(config['time']['time_unit'] == 'day_bh'){
	        		var timeUnit = 'day_bh';
	        	}else{
	        		var timeUnit = config['time']['time_unit'];
	        	}
	        	
	            var timeData = {};
	            timeData.id = timeUnit;
	            timeData.type = "ta";
	            timeData.order = "Descending"; // get the last value available
	            
	            // Main Counter parameters
	            var rawKpiId = config['kpis']['kpi'][0]['id'];
	            var rawKpiProductId = config['kpis']['kpi'][0]['product_id'];
	            var rawKpiType = config['kpis']['kpi'][0]['type'];
	            var rawKpiData = {};
	            rawKpiData.id = rawKpiId;        
	            rawKpiData.productId = rawKpiProductId;
	            rawKpiData.type = rawKpiType;       
	            
	            var volumeRawKpiId = config['kpis']['kpi'][1]['id'];
	            if (volumeRawKpiId == null || 
	    			typeof(volumeRawKpiId) != 'string' ||
	    			volumeRawKpiId == '') {
	            	var selectData = new Array(timeData, rawKpiData);
	            	Ext.getCmp(periodObjectId).series.get(0).showInLegend = false;
	            	Ext.getCmp(periodObjectId).redraw();
	            } else {
	            	var volumeRawKpiProductId = config['kpis']['kpi'][1]['product_id'];
	                var volumeRawKpiType = config['kpis']['kpi'][1]['type'];
	                var volumeRawKpiData = {};
	                volumeRawKpiData.id = volumeRawKpiId;        
	                volumeRawKpiData.productId = volumeRawKpiProductId;
	                volumeRawKpiData.type = volumeRawKpiType;
	                
	                var selectData = new Array(timeData, rawKpiData, volumeRawKpiData);
	            }
	           
	            // Network Agregation
	            var neData = {}; 
	            var neValue = config['network_elements']['ne']['id'];
	            var neId = config['network_elements']['ne']['network_level'];
	            neData.type = 'na';
	            neData.operator = 'in';
	            neData.value = neValue;
	            neData.id = neId;
	                       
	            var neValue2 = null;
	            var neId2 = null;
		        if (typeof(config['network_elements']['ne2']) != 'undefined' &&
		        	(typeof(config['network_elements']['ne2']['id']) == 'string') &&
		        	(config['network_elements']['ne2']['id'] != '')) {
			        var neData2 = {}; 
			        neValue2 = config['network_elements']['ne2']['id'];
			        neId2 = config['network_elements']['ne2']['network_level'];
			        neData2.type = 'na_axe3';
			        neData2.operator = 'in';
			        neData2.value = neValue2;
			        neData2.id = neId2;
		        }
	            
	            var requestData = {};
	            requestData.method = "getDataAndLabels";
	            requestData.parameters = {};
	            requestData.parameters.select = {};
	            requestData.parameters.select.data = selectData;
	
	            // Only get the last value in the database
	    		var limitFilter = {};
	    		var timeNumber = config['time']['units_number'];
	    		if (timeNumber == null ||((typeof(timeNumber) != 'string') && (typeof(timeNumber) != 'number')) || timeNumber == '') {
	    			
	    			timeNumber = 14;
	    			
	    		}
	    		limitFilter.id = "maxfilter"; 
	    		limitFilter.type = "sys";
	    		limitFilter.value = timeNumber;
	    		        
	            requestData.parameters.filters = {};
	            
	            if (neId2 != null) {
		        	var filtersData = new Array(neData, neData2, limitFilter);
		        } else {
			        var filtersData = new Array(neData, limitFilter);
		        }
	                        
	            requestData.parameters.filters.data = filtersData;
	            var requestParam = {};
	            requestParam.data = Ext.encode(requestData);
	        	
	            // Send the request
	            Ext.Ajax.request({
	            	url: 'proxy/dao/api/querydata/index.php',
	            	params: requestParam,
	
	                success: function (response) { 
	            		var data = [];
	            	        		
	    	    		var error = false;
	    	    		try {
	    	    			var result = Ext.decode(response.responseText);
	    	    			if ((typeof(result['error']) != 'undefined') ||
	        					(result['values'] == undefined)) {
	    	    				// The request send an error response
	    	    				error = true;
	    	    			} else {
	    	    				
	    	    			}
	    	    		} catch (err) {
	    	    			// The json is invalid 
	    	    			error = true;
	    	    		}
	            			    		
	    	    		if (!error) {	
	    	    			var dataMin = null;
	    	    			var dataMax = null;
	    	    			
	    	    			var haveVolume = false;
	    	    			
	        				// Put the result datas in the data array
	    	    			result['values']['data'].sort();
	    	    			for (var i = 0; i < result['values']['data'].length; i++) {  				
	    	    				var date = result['values']['data'][i][0];
	    	    				var data1 = parseFloat(result['values']['data'][i][1]);
	    	    				var data2 = parseFloat(result['values']['data'][i][2]);
	    	    					  
	    	    				if (isNaN(data1)) data1 = '';
	    	    				if (isNaN(data2)) data2 = '';
	    	    				    	    				
	    	    				if (!haveVolume && typeof(data2) == 'number' && !isNaN(data2)) haveVolume = true;
	    	    				
	    	    				// Render the time tooltip label
	            				if(timeUnit == 'hour' || timeUnit == 'day_bh' || timeUnit == 'month_bh') {
	            					dateLabel = date.substring(0, 4) + '/' 
	            						+ date.substring(4, 6) + '/'
	            						+ date.substring(6, 8) + '-'
	            						+ date.substring(8) + ':00';
	            				}else if (timeUnit == 'day') {
	            					dateLabel = date.substring(0, 4) + '/' 
	        							+ date.substring(4, 6) + '/'
	        							+ date.substring(6);
	            				}else if (timeUnit == 'week') {
	            					dateLabel = date.substring(0, 4) + ' ' 
	        						+ 'W' + date.substring(4);
	            				}else if (timeUnit == 'month'){
	            					dateLabel = date.substring(0, 4) + '/' 
	        							+ date.substring(4, 6);	
	            				}else if (timeUnit == 'week_bh') {
									var year = date.substring(0,4);
									var month = date.substring(4,6);
									var day = date.substring(6,8);
	            					var week = me.getWeekNumber(year+'-'+month+'-'+day);
	            					week = week[1];
	            					dateLabel = 'W'+week  + '/' 
	            					+ date.substring(0, 4) + ' - ' 
	            					+ date.substring(4, 6) + '/'
	            					+ date.substring(6, 8) + '-'
	            					+ date.substring(8) + ':00';
	            				}
	            				
	            				// Render the time axis label
	            				var dateAxisLabel = '';
//								display all labels, chart.axis override will take care of display	            				
//	            				if (i == 0 ||														// First index
//	        						i == result['values']['data'].length - 1 ||						// Last index
//	        						i == (Math.round(result['values']['data'].length / 2) - 1)) { 	// Middle index
//	            					dateAxisLabel = dateLabel;
//	            				}
	            				
	            				dateAxisLabel = dateLabel;
	            				
	            				// Get the thresholds        			
	                  			var configLowThreshold = parseFloat(config['axis_list']['axis'][0]['thresholds']['low_threshold']);
	              				var configHighThreshold = parseFloat(config['axis_list']['axis'][0]['thresholds']['high_threshold']);
	            				
	              				//TODO modif MSL
	              				if (config['kpis']['kpi'][0]['function'] == 'failure'/* || config['kpis']['kpi'][0]['function'] == 'other'*/) {
	              					if (!isNaN(configLowThreshold) && !isNaN(configHighThreshold)){
		              					var warning = configLowThreshold;
		          						var alert = configHighThreshold;
	              					}else{
	              						var warning = '';
		          						var alert = '';
	              					}
	              				} else {
	              					if (!isNaN(configLowThreshold) && !isNaN(configHighThreshold)){
		              					var warning = configHighThreshold;
		          						var alert = configLowThreshold;
	              					}else{
	              						var warning = '';
		          						var alert = '';
	              					}
	              				}
	              				
	              				var period= config['time']['units_number'];
	              				
	              				// Push the datas into the chart
	              				var dataToAdd = new Object();
	              				/*if (data1 != '')*/ dataToAdd.data1 = data1;
	              				/*if (data2 != '')*/ dataToAdd.data2 = data2;
	              				dataToAdd.date = date;
	              				dataToAdd.time = dateLabel;
	              				dataToAdd.timeAxis = dateAxisLabel;
	              				dataToAdd.warning = warning;
	              				dataToAdd.alert = alert;
	              				dataToAdd.gaugeType = rawKpiType;
	              				dataToAdd.trendType = volumeRawKpiType;
	              				dataToAdd.gaugeProduct = rawKpiProductId;
	              				dataToAdd.trendProduct = volumeRawKpiProductId;
	              				dataToAdd.gaugeRawKpi = rawKpiId;
	              				dataToAdd.trendRawKpi = volumeRawKpiId;
	              				dataToAdd.timeAgregation = timeUnit;
	              				dataToAdd.networkAgregation = neId;
	              				dataToAdd.networkName = neValue;
	              				dataToAdd.networkAxe3Agregation = neId2;
	              				dataToAdd.networkAxe3Name = neValue2;
	              				dataToAdd.period= period;
	              				
	              				
	    	    				data.push(dataToAdd);
	    	    				
	    	    				// Set the min and max values
	    	    				if (typeof(data1) == 'number' && !isNaN(data1)) {	    					
	    	    					if(dataMin == null) dataMin = data1;
	    	    					if (data1 < dataMin) dataMin = data1;
	    	    					
	    	    					if(dataMax == null) dataMax = data1;
	    	    					if (data1 > dataMax) dataMax = data1;	    					
	    	    				}
	    	    			}    			
	    	    		} else {
	    	    			data.push({
	        					data1: undefined,
	        					data2: undefined,
	        					time: '',
	        					timeLegend: '',
	        					warning: 0,
	        					alert: 0
	        				});
	    	    		}
	            	    
	    	    		
	    	    		
	    	    		var periodObject = Ext.getCmp(Ext.getCmp(gauge).target + '_periodObject');
	            		// Load the data in the chart
	            		periodObject.store.loadData(data);
	    	    		
	    	    		// Get the min and max values

	          			var chartMin = 0;
	          			var chartMax = 100;
	          			
	          			var dynamicScale = config['axis_list']['axis'][0]['zoom']['dynamic'] == true ||
	          				config['axis_list']['axis'][0]['zoom']['dynamic'] == "true";
	    	          	
	    	          	var low_tresh = parseFloat(config['axis_list']['axis'][0]['thresholds']['low_threshold']);
	          			var high_tresh = parseFloat(config['axis_list']['axis'][0]['zoom']['high_threshold']);	

	          			if (dynamicScale) {
	          				// Calculate the scale values
	          				var perMin = Math.ceil(dataMin / 10);
	          				var perMax = Math.ceil(dataMax / 10);
	          				//var dynamicLeftMin = Math.floor(minLeftAxis - perLeftMin);
	          				//var dynamicLeftMax = Math.ceil(maxLeftAxis + perLeftMax);         
	                		
	          				var dynamicMin = Math.floor(dataMin - perMin);
	          				var dynamicMax = Math.ceil(dataMax + perMax);          				
	          				
	          				// If it's a rate, min is 0 and max is 100  
	          				var counterFunc = config['kpis']['kpi'][0]['function'];
	          				if (counterFunc == 'failure' || counterFunc == 'success') {
	          					if (dynamicMin < 0) dynamicMin = 0;
	          					if (dynamicMax > 100) dynamicMax = 100;
	          				} 

	          			} else {
	          				
	          				// Get the values in the configuration         				
	          				var configMin = parseFloat(config['axis_list']['axis'][0]['zoom']['min_value']);
	          				var configMax = parseFloat(config['axis_list']['axis'][0]['zoom']['max_value']);
	          				// Default values are 0 -> 100
	          				if (isNaN(configMin)) configMin = 0;
	          				if (isNaN(configMax)) configMax = 100;
	          				
	          				if(configMin < configMax) {
	          					chartMin = configMin;
	          					chartMax = configMax;
	          				}
	          			}
	    	    		
	            		var periodObject = Ext.getCmp(Ext.getCmp(gauge).target + '_periodObject');
	            		// Load the data in the chart
	            		periodObject.store.loadData(data);
	            		
	            		//get min and max value from data1, for all the serie, not the only day/hour/...
	            		//left axis is always dynamic
	            		var minLeftAxis=periodObject.store.min('data1');
	            		var maxLeftAxis=periodObject.store.max('data1');
	            		
	//            		if(typeof(minLeftAxis)=='undefined' && typeof(maxLeftAxis)=='undefined'){
	//            			dynamicLeftMin='';
	//            			dynamicLeftMax='';
	//            		}
	//            		else{
	            			var perLeftMin = Math.ceil(minLeftAxis / 10);
	          				var perLeftMax = Math.ceil(maxLeftAxis / 10);
							var counterFunc = config['kpis']['kpi'][0]['function'];
	                		//If treshold min lower than lowest dynamic abscisse
	                		/**
	                		if(dynamicLeftMin < chartMin){
	                			dynamicLeftMin = configLowThreshold;
	                		}else{
	                			dynamicLeftMin = Math.floor(minLeftAxis - perLeftMin);
	                		}
	                		**/
	                		/**
	                		if(dynamicLeftMax > chartMax){
	                			dynamicLeftMax = configHighThreshold;
	                		}else{
	                			dynamicLeftMax = Math.ceil(maxLeftAxis + perLeftMax);
	                		}
	          				**/
	          				
	          				
	          				//only limit when success or failure
	          				/**
	          				if (counterFunc == 'failure' || counterFunc == 'success') {
	    	      				if (dynamicLeftMin < 0) dynamicLeftMin = 0;
	    	  					if (dynamicLeftMax > 100) dynamicLeftMax = 100;
	    	  					if (dynamicLeftMax == 0) dynamicLeftMax = 10;
	          				}
	          				if (counterFunc == 'other'){
	          					if (dynamicLeftMin <= 0) dynamicLeftMin = 0;
	    	  					if (dynamicLeftMax == 0) dynamicLeftMax = 10;
	          				}
	          				**/
	            		//}

	            		// Set axis min/max
	            		var axisLeft = periodObject.axes.get(0);
	            		
	            		//axisLeft.minimum = chartMin;
	            		//axisLeft.minimum = dynamicLeftMin;
	            		
	            		//axisLeft.maximum = chartMax;
	            		//axisLeft.maximum = dynamicLeftMax;
	            		
	            		// Set axis titles
	            		var unitLeft = config['axis_list']['axis'][0]['unit'];
	            		if ((unitLeft == null) || (typeof (unitLeft) != 'string')) unitLeft = '';
	            		axisLeft.title = unitLeft;
	            		var axisRight = periodObject.axes.get(1);
	            		var unitRight = config['axis_list']['axis'][1]['unit'];
	            		if ((unitRight == null) || (typeof (unitRight) != 'string')) unitRight = '';
	            		axisRight.title = unitRight;
	            		
	            		// Set the series titles
	            		if (haveVolume) {        			
	            			var volumeSerie = periodObject.series.get(0);			// Volume
	                		volumeSerie.setTitle(result['labels'][1]['label']);
	            		} else {
	            			var volumeSerie = periodObject.series.get(0);			// Volume
	            			volumeSerie.setTitle('Volume');
	            		}
	            		
	            		var trendSerie = periodObject.series.get(3);				// Trend
	            		if ((typeof(result) !== 'undefined') &&
	    					(typeof(result['labels']) !== 'undefined')) {
	            			trendSerie.setTitle(result['labels'][0]['label']);
	            		}    		        		
	            		
	            		periodObject.redraw();
	            		
	            		if (data2 == null ||data2 == '') {	
		            		var bboxY = Ext.getCmp(periodObjectId).legend.getBBox().y;
			            	var newY = bboxY-8;
			            	Ext.getCmp(periodObjectId).legend.boxSprite.setAttributes({y:newY},true);
	            		}
	            		Ext.each(periodObject.legend.items, function(item) {
	            			item.un("mousedown", item.events.mousedown.listeners[0].fn);
	            			item.un("mouseover", item.events.mouseover.listeners[0].fn);
            			});
	            	}
	            });
        	}
        	else{
	        	//load empty graph
	    		var periodChart = Ext.getCmp(Ext.getCmp(gauge).target);
	    		periodChart.setTitle(Ext.getCmp(gauge).title);
	
	    		var periodObject = Ext.getCmp(Ext.getCmp(gauge).target + '_periodObject');
/*	

	    		var data=[];
	        		data.push({
	    				data1: '',
	    				data2: '',
	    				time: '',
	    				timeLegend: '',
	    				warning: 0,
	    				alert: 0
	    			});
	        		
	    		periodObject.store.loadData(data);
	

	    		if(nbtrend==1){
	    			var box=periodObject.chartBBox;
	    			
				    var sprite = Ext.create('Ext.draw.Sprite', {
				        		    type: 'text',
				        		    surface: periodObject.surface,
				        		    text: 'CHART NOT CONFIGURED',
				        		    font: '16px Arial',
				        		    x: (box.width-box.x)/4,
				        		    y: (box.height-box.y)/2,
				        		    width: 100,
				        		    height: 100 
				        		});    		
				    
				    sprite.show(true);
		    		periodObject.surface.add(sprite);
	    			
	    		}
	    		
*/			    
	    		periodObject.redraw();
	    		Ext.each(periodObject.legend.items, function(item) {
        			item.un("mousedown", item.events.mousedown.listeners[0].fn);
        			item.un("mouseover", item.events.mouseover.listeners[0].fn);
    			});
		  }
	},
	getWeekNumber: function(d) {
	    // Copy date so don't modify original
	    d = new Date(d);
	    d.setHours(0,0,0);
	    // Set to nearest Thursday: current date + 4 - current day number
	    // Make Sunday's day number 7
	    d.setDate(d.getDate() + 4 - (d.getDay()||7));
	    // Get first day of year
	    var yearStart = new Date(d.getFullYear(),0,1);
	    // Calculate full weeks to nearest Thursday
	    var weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7)
	    // Return array of year and week number
	    return [d.getFullYear(), weekNo];
	}
});