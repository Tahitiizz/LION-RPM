Ext.define('homepage.controller.charts.Gauge', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.Gauge'
	],
	
	init: function() {
		this.control({
			'gauge': {
            	load : this.load,
            	resizeGauge : this.resize
            }
        });
    },

	load: function(config) {       	
    	var me = this;
    	var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
    	
		// Get the chart
		var gauge = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
				
		// Set the title
		var title = config['title'];
		if (typeof(title) != 'string') title = ' ';
		gauge.setTitle(title);
		
		
		var titleWidth = this.measureText(gauge.title, this.labelFont ).width;
		
		if(titleWidth>gauge.header.getWidth()-25){
			Ext.tip.QuickTipManager.register({
				target: gauge.header.id,
			    text: title,
			    dismissDelay: 2000
			});
		}
		    	
    	// Create the request parameters    	
		if ((typeof(config['time']) !== 'undefined') &&
			(typeof(config['kpis']) !== 'undefined') &&
			(typeof(config['network_elements']) !== 'undefined')) {
			
			// Time parameters
	    	var timeUnit = config['time']['time_unit'];
	        var timeData = {};
	        timeData.id = timeUnit;
	        timeData.type = "ta";
	        timeData.order = "Descending"; // get the last value available
	        
	        // Counter parameters
	        var rawKpiId = config['kpis']['kpi']['id'];
	        var rawKpiProductId = config['kpis']['kpi']['product_id'];
	        var rawKpiType = config['kpis']['kpi']['type'];
	        var rawKpiData = {};
	        rawKpiData.id = rawKpiId;        
	        rawKpiData.productId = rawKpiProductId;
	        rawKpiData.type = rawKpiType;       
	        
	        var selectData = new Array(timeData, rawKpiData);
	       
	        // Network Agregation
	        var neData = {}; 
	        var neValue = config['network_elements']['ne']['id'];
	        var neId = config['network_elements']['ne']['network_level'];
	        neData.type = 'na';
	        neData.operator = 'in';
	        neData.value = neValue;
	        neData.id = neId;
	        
	        var neId2 = null;
	        if (typeof(config['network_elements']['ne2']) != 'undefined' &&
	        	(typeof(config['network_elements']['ne2']['id']) == 'string') &&
	        	(config['network_elements']['ne2']['id'] != '')) {
		        var neData2 = {}; 
		        var neValue2 = config['network_elements']['ne2']['id'];
		        var neId2 = config['network_elements']['ne2']['network_level'];
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
			limitFilter.id = "maxfilter"; 
			limitFilter.type = "sys";
			limitFilter.value = "1";
			limitFilter.connector="and";
	        
			//get last not null value from db to avoir NaN on gauage display
			var notNullFilter ={};
			notNullFilter.id=rawKpiId;
			notNullFilter.type=rawKpiType;
			notNullFilter.productId=rawKpiProductId;
			notNullFilter.operator="is not null";
			notNullFilter.value="true";
			
	        requestData.parameters.filters = {};
	        if (neId2 != null) {
	        	var filtersData = new Array(neData, neData2, limitFilter,notNullFilter);
	        } else {
		        var filtersData = new Array(neData, limitFilter,notNullFilter);
	        }
	        requestData.parameters.filters.data = filtersData;
	        var requestParam = {};
	        requestParam.data = Ext.encode(requestData);
	    	
	        // Send the request
	        Ext.Ajax.request({
	        	url: 'proxy/dao/api/querydata/index.php',
	        	params: requestParam,

	            success: function (response) {	        	
	        		var error = false;
	        		try {
	        			var result = Ext.decode(response.responseText);
	        			if (typeof result['error'] != "undefined") {
	        				// The request send an error response
	        				error = true;
	        			} else {
	        				
	        			}
	        		} catch (err) {
	        			// The json is invalid 
	        			error = true;
	        		}
	        	        		
	        		var neLabel = '';
	        		var counterLabel = '';
	        		var dateLabel = '';
	        		
	        		var displayCounter = config['kpis']['labels_visible'] == "true" || config['kpis']['labels_visible'] == true;
	        		var displayNe = config['network_elements']['labels_visible'] == "true" || config['network_elements']['labels_visible'] == true;
	        		var displayDate = config['time']['labels_visible'] == "true" || config['time']['labels_visible'] == true;
	        		        		
	        		if (error) {
	        			// Update the gauge details
	            		if (displayCounter) {
	            			counterLabel = (typeof(rawKpiId) == 'string') && (rawKpiId != '') ? rawKpiId + ' ' : '';
	            		}
	            		
	        			if (displayNe) {
	        				neLabel = (typeof(neId) == 'string') && (neId != '') ? neId + ' ' : '';
	        				neLabel += (typeof(neValue) == 'string') && (neValue != '') ? neValue : '';
	        			}
	        			
	            		gauge.fireEvent('updateDetails', gauge.id, 'X', counterLabel, neLabel, 'No value', 'alert');
	        		} else {    
	        			// Get the datas returned       			
	        			var value = result['values']['data'][0][1];
	        			var date = result['values']['data'][0][0];
	        			
	        			if (displayCounter) {
	            			counterLabel = result['labels'][0]['label'];
	            			//ellipsis in charge of label width now
//	            			if (counterLabel.length > 20) {
//	            				counterLabel = counterLabel.substr(0, 18) + '...';
//	            			}
	            		}
	            		
	        			if (displayNe) {
	        				neLabel = (typeof(neId) == 'string') && (neId != '') ? neId + ' ' : '';
	        				neLabel += result['labels'][1]['label'];
	        				
	        				if ((typeof(neId2) == 'string') && (neId2 != '')) {
	        					neLabel += ' / ' +  neId + ' ';
	        					neLabel += result['labels'][2]['label'];
	        				}
	        				
	        				if (neLabel.length > 30) neLabel = neLabel.substr(0, 30) + '...';
	        			}
	        			
	        			if (displayDate) {
	        				// Get the time unit
	        				var timeUnit = config['time']['time_unit'];
	        				if (timeUnit == 'hour' || timeUnit == 'day_bh' || timeUnit == 'month_bh') {
	        					dateLabel = date.substring(0, 4) + '/' 
	        						+ date.substring(4, 6) + '/'
	        						+ date.substring(6, 8) + ' '
	        						+ date.substring(8) + ':00';
	        				} else if (timeUnit == 'day') {
	        					dateLabel = date.substring(0, 4) + '/' 
	    							+ date.substring(4, 6) + '/'
	    							+ date.substring(6);
	        				} else if (timeUnit == 'week') {
	        					dateLabel = date.substring(0, 4) + ' ' 
	    						+ 'W' + date.substring(4);
	        				}else if (timeUnit == 'month'){
	            				dateLabel = date.substring(0, 4) + '/' 
	        					+ date.substring(4, 6);	
	            			}else if (timeUnit == 'week_bh') {
								var year = date.substring(0,4);
								var month = date.substring(4,6);
								var month = month;
								var day = date.substring(6,8);
            					var week = me.getWeekNumber(year+'-'+month+'-'+day);
            					week = week[1];
            					dateLabel = 'W'+week  + '/' 
            					+ date.substring(0, 4) + ' - ' 
            					+ date.substring(4, 6) + '/'
            					+ date.substring(6, 8) + '-'
            					+ date.substring(8) + ':00';
	            			}
	            				        				
	        			}
	        			
	          			// Get the min and max values
	          			var gaugeMin = 0;
	          			var gaugeMax = 100;
	          			
	          			var dynamicScale = config['axis_list']['axis']['zoom']['dynamic'] == true ||
	          				config['axis_list']['axis']['zoom']['dynamic'] == "true";
	          			          			
	          			var valueNumber = parseFloat(value);
	          			valueNumber = Math.round(valueNumber * 100) / 100;
	          			
	          			if (dynamicScale) {
	          				// Calculate the scale values
	          				var numberLen = valueNumber.toString().length;
	          				if(valueNumber > 0 && valueNumber <= 20){
	          					var dynamicMin = 0;
		          				var dynamicMax = 25;  
	          				}else if(valueNumber > 20 && valueNumber <= 50){
	          					var dynamicMin = 10;
		          				var dynamicMax = 60;  
	          				}else if(valueNumber > 50 && valueNumber <= 130){
	          					var dynamicMin = 40;
		          				var dynamicMax = 140;  
	          				}else if(valueNumber > 130 && valueNumber <= 320){
	          					var dynamicMin = 100;
		          				var dynamicMax = 350;  
	          				}else if(valueNumber > 300 && valueNumber <= 1000){
	          					var dynamicMin = 300;
		          				var dynamicMax = 1000; 
	          				}else if(valueNumber > 1000 && valueNumber <= 5500){
	          					var dynamicMin = 1000;
		          				var dynamicMax = 6000;  
	          				}else if(valueNumber > 5500 && valueNumber <= 1000){
	          					var dynamicMin = 5000;
		          				var dynamicMax = 10000;  
	          				}else if(valueNumber > 10000 && valueNumber <= 55000){
	          					var dynamicMin = 10000;
		          				var dynamicMax = 60000;  
	          				}else if(valueNumber > 55000 && valueNumber <= 100000){
	          					var dynamicMin = 50000;
		          				var dynamicMax = 100000;  
	          				}else if(valueNumber > 100000 && valueNumber <= 550000){
	          					var dynamicMin = 100000;
		          				var dynamicMax = 600000;  
	          				}else if(valueNumber > 550000 && valueNumber <= 1000000){
	          					var dynamicMin = 500000;
		          				var dynamicMax = 1000000;  
	          				}else if(valueNumber > 1000000 && valueNumber <= 5500000){
	          					var dynamicMin = 1000000;
		          				var dynamicMax = 6000000;  
	          				}else if(valueNumber > 5500000 && valueNumber <= 10000000){
	          					var dynamicMin = 5000000;
		          				var dynamicMax = 10000000;  
	          				}else if(valueNumber > 10000000 && valueNumber <= 60000000){
	          					var dynamicMin = 10000000;
		          				var dynamicMax = 60000000;  
	          				}else if(valueNumber > 10000000){
	          					var per = Math.ceil(valueNumber / 2);
	          					if (per == 0) per = 10;
	          				
		          				var dynamicMin = Math.floor(valueNumber - per);
		          				var dynamicMax = Math.ceil(valueNumber + per);  
	          				}
	          				
	          				
	          				
	          				/**
	          				if(valueNumber >= 4000000){
	          					var dynamicMin = Math.floor(valueNumber - 2500000);
		          				var dynamicMax = Math.ceil(valueNumber + 2500000);  
	          					
	          				}else{
	          					var per = Math.ceil(valueNumber / 10);
	          					if (per == 0) per = 10;
	          				
		          				var dynamicMin = Math.floor(valueNumber - per);
		          				var dynamicMax = Math.ceil(valueNumber + per);  
	          				}
							**/
	          				// If it's a rate, min is 0 and max is 100  
	          				var counterFunc = config['kpis']['kpi']['function'];
	          				if (counterFunc == 'failure' || counterFunc == 'success') {
	          					if ((dynamicMin < 0) && (valueNumber >= 0)) dynamicMin = 0;
	          					if ((dynamicMax > 100) && (valueNumber <= 100)) dynamicMax = 100;
	          				} 
	          					          				
	          				gaugeMin = dynamicMin;
	          				gaugeMax = dynamicMax;
	          			} else {
	          				// Get the values in the configuration         				
	          				var configMin = parseFloat(config['axis_list']['axis']['zoom']['min_value']);
	          				var configMax = parseFloat(config['axis_list']['axis']['zoom']['max_value']);
	          				
	          				// Default values are 0 -> 100
	          				if (isNaN(configMin)) configMin = 0;
	          				if (isNaN(configMax)) configMax = 100;
	          				
	          				if(configMin < configMax) {
	          					gaugeMin = configMin;
	          					gaugeMax = configMax;
	          				}
	          			}
	          			
	          			// Get the thresholds        			
	          			var configLowThreshold = parseFloat(config['axis_list']['axis']['thresholds']['low_threshold']);
	      				var configHighThreshold = parseFloat(config['axis_list']['axis']['thresholds']['high_threshold']);
	      				
	      				var maxReached = false;
	      				
	      				// Set low area
	      				var lowAreaMin = null;
	      				var lowAreaMax = null;
	      				if (!isNaN(configLowThreshold)) {
	      					if (isNaN(configHighThreshold) || (configLowThreshold < configHighThreshold)) {
	          					if (configLowThreshold > gaugeMin) {
	          						lowAreaMin = gaugeMin; 
	          						if (configLowThreshold <= gaugeMax) {
	          							lowAreaMax = configLowThreshold;
	          						} else {
	          							lowAreaMax = gaugeMax;
	          							maxReached = true;
	          						}
	          					}
	      					}
	      				}
	      				      				
	      				// Set mid area
	      				var midAreaMin = null;
	      				var midAreaMax = null;
	      				if (!isNaN(configHighThreshold) && !maxReached) {
	      					if (isNaN(configLowThreshold) || (configLowThreshold <= configHighThreshold)) {
	      						if (lowAreaMax == null) {
	          						if (configHighThreshold > gaugeMin) {
	              						midAreaMin = gaugeMin; 
	              						if (configHighThreshold <= gaugeMax) {
	              							midAreaMax = configHighThreshold;
	              						} else {
	              							midAreaMax = gaugeMax;
	              							maxReached = true;
	              						}
	              					}
	          					} else {
	          						midAreaMin = lowAreaMax;
	          						if (configHighThreshold <= gaugeMax) {
	          							midAreaMax = configHighThreshold;
	          						} else {
	          							midAreaMax = gaugeMax;
	          							maxReached = true;
	          						}
	          					}
	      					}      					
	      				}
	      				
	      				// Set High area
	      				var highAreaMin = null;
	      				var highAreaMax = null;
	      				if (!maxReached) {
	      					highAreaMax = gaugeMax;
	      					if (midAreaMax != null) {
	      						highAreaMin = midAreaMax;
	      					} else if (lowAreaMax != null) {
	      						highAreaMin = lowAreaMax;
	      					} else {
	      						highAreaMin = gaugeMin;
	      					}
	      				}
	      				
	      				// Set the alarm levels
	      				var alertMin = null;
	      				var alertMax = null;
	      				var warningMin = midAreaMin;
	      				var warningMax = midAreaMax;
	      				var okMin = null;
	      				var okMax = null;
	  
	      				var failureGauge = false;
	      				//TODO modif pour MSL, invert when other too
	      				if (config['kpis']['kpi']['function'] == 'failure' /*|| config['kpis']['kpi']['function'] =='other'*/) {
	      					// If it's a failure rate, we invert the colors
	      					failureGauge = true;
	      					alertMin = highAreaMin;
	          				alertMax = highAreaMax;
	          				okMin = lowAreaMin;
	          				okMax = lowAreaMax;
	      				} else {
	      					alertMin = lowAreaMin;
	          				alertMax = lowAreaMax;
	          				okMin = highAreaMin;
	          				okMax = highAreaMax;
	      				}
	      				
	      				// Set the label color
	      				var labelColor = 'ok';
	      				if ((alertMin != null) && (alertMin <= valueNumber) && (alertMax >= valueNumber)) {
	      					labelColor = 'alert';
	      				} else if ((warningMin != null) && (warningMin <= valueNumber) && (warningMax >= valueNumber)) {
	      					labelColor = 'warning';
	      				}
	      				
	      				// Get the unit
	      				var unit = config['axis_list']['axis']['unit'];
	      				if (unit == null || typeof(unit) != 'string') {
	      					unit = '';  
	      				} else {
	      					unit = '\u00a0' + unit; 
	      				}
	      				var valueLabel = valueNumber;
	      				if (valueNumber >= 1000000000) {
	      					var mant = Math.round(valueNumber / 1000000);
							valueLabel = (mant / 1000) + '\u00a0B';
						} else if (valueNumber >= 1000000) {
							var mant = Math.round(valueNumber / 1000);
							valueLabel = (mant / 1000) + '\u00a0M';
						} else if (valueNumber >= 1000) {
							valueLabel = (Math.round(valueNumber) / 1000) + '\u00a0K';
						} 
	      				
	          			// Update the gauge details
	          			gauge.fireEvent('updateDetails', gauge.id, valueLabel + unit, counterLabel, neLabel, dateLabel, labelColor);
	          			if (Ext.getCmp('viewport').gaugeType == 1) {
	          				gauge.gaugeView.setValues(value, gaugeMin, gaugeMax, warningMin, warningMax, alertMin, alertMax, failureGauge);
	          			} else {
	          				// Display the default gauge
		        			Ext.Ajax.request({
		        				url: 'proxy/configuration.php',
		        				params: {
		        					task: 'GAUGE',
		        					tab: tabId,
		        					chart: config['@attributes']['id'],
		        					value: value,
		        					gaugemin: gaugeMin,
		        					gaugemax: gaugeMax,
		        					alertmin: alertMin,
		        					alertmax: alertMax,
		        					warningmin: warningMin,
		        					warningmax: warningMax,
		        					okmin: okMin, 
		        					okmax: okMax
		                  		},
		                  		success: function(path) {                    			
		                  			// Set the path
		                  			gauge.path = path.responseText;
		                  			
		                  			// Destroy the previous gauge...
		                  			gauge.down('gaugeview').destroy();
		                  			
		                  			// ...and create a new one with the new values
		                  			var chart = Ext.create('homepage.view.charts.GaugeView');                  			
		                  			gauge.add(chart);
		               
		                  			// Set the gauge size
		                  			var gaugeWidth;
		                  			var gaugeHeight;
		                  			
		                  			var ratio = chart.getWidth() / chart.getHeight();
		                  			if (ratio > 2) {
		                  				// Take the height as the referent size
		                  				gaugeWidth = chart.getHeight() * 2;
		                  				gaugeHeight = chart.getHeight();
		                  			} else {
		                  				// Take the width as the referent size
		                  				gaugeWidth = chart.getWidth();
		                  				gaugeHeight = Math.floor(chart.getWidth() / 2);
		                  				
		                  			}
		                  			
		                  			// Set the margins
		                  			var marginLeft = (chart.getWidth() - gaugeWidth) / 2;
		                  			var marginTop = (chart.getHeight() - gaugeHeight) / 2;
		                  				                  			
		                  			// Update the gauge
		                  			chart.update(
		              					'<div style="width:' + gaugeWidth + 
		              					'px; height:' + gaugeHeight + 
		              					'px; margin-left:' + marginLeft + 
		              					'px; margin-top:' + marginTop + 
		              					'px" id="div_' + gauge.id + '"></div>');
		                  			bindows.loadGaugeIntoDiv(gauge.path, 'div_' + gauge.id);
		                  		}
		        			});
	          			}
	        		}
	        	}
	        });
		}
    },
    
    resize : function(gauge) {
    	if (Ext.getCmp('viewport').gaugeType == 1) {
			gauge.gaugeView.resizePanel();
		} else {
			if (gauge.path != null) {
	    		// Destroy the previous gauge...
	    		gauge.down('gaugeview').destroy();
	    		
	    		// ...and create a new one
	    		var chart = Ext.create('homepage.view.charts.GaugeView');                  			
	  			gauge.add(chart);

	  			// Set the gauge size
	  			var gaugeWidth;
	  			var gaugeHeight;
	  			
	  			var ratio = chart.getWidth() / chart.getHeight();
	  			if (ratio > 2) {
	  				// Take the height as the referent size
	  				gaugeWidth = chart.getHeight() * 2;
	  				gaugeHeight = chart.getHeight();
	  			} else {
	  				// Take the width as the referent size
	  				gaugeWidth = chart.getWidth();
	  				gaugeHeight = Math.floor(chart.getWidth() / 2);
	  				
	  			}
	  			
	  			// Set the margins
	  			var marginLeft = (chart.getWidth() - gaugeWidth) / 2;
	  			var marginTop = (chart.getHeight() - gaugeHeight) / 2;
	  			
	  			// Update the gauge
	  			chart.update(
						'<div style="width:' + gaugeWidth + 
						'px; height:' + gaugeHeight + 
						'px; margin-left:' + marginLeft + 
						'px; margin-top:' + marginTop + 
						'px" id="div_' + gauge.id + '"></div>');
	  			bindows.loadGaugeIntoDiv(gauge.path, 'div_' + gauge.id);
	  			
	  			//resize details labels
	  			var fsize=Math.floor(Ext.getCmp(gauge.id + '_details_infoPanel').getHeight()/3-2);
	  			Ext.getCmp(gauge.id + '_details_label1').el.setStyle({'font-size': fsize+'px'});
	  			Ext.getCmp(gauge.id + '_details_label2').el.setStyle({'font-size': fsize+'px'});
	  			Ext.getCmp(gauge.id + '_details_label3').el.setStyle({'font-size': fsize+'px'});
	    	}
		}
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
