Ext.define('homepage.controller.charts.GridReport', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.GridReport'
	],

	selectData: null,
	filtersData: null,
	networks: null,
	dataGrid: null,
	networksGrid: null,
	
	init: function() {
		var me = this;
		
		Ext.define('GridModel', {
            extend: 'Ext.data.Model',
            fields: [
                 {name: 'foo'}
            ]
        });
		
		this.control({
			'gridreport': {
	        	load : this.load
	        }
	    });
	},
	
	load: function(config, index) {	
		var me = this;
		
		if (typeof(index) != 'number') {
			// First call to load()	
						    	
			// Get the chart
			var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
			var grid = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
			
			// Set the title
			var title = config['title'];
			if (typeof(title) != 'string') title = ' ';

	    	var timeUnit = config['time']['time_unit'];
			if (timeUnit != null && (typeof(timeUnit) == 'string') && timeUnit != '') {
				var timeDate = new Date(config['time']['date']);
				if (timeUnit == 'week') {
					var week = me.getWeekNumber(timeDate);
					title += ' : ' + week[0] + ' W' + (week[1] < 10 ? '0' + week[1] : week[1]);
				} else {				
					var month = timeDate.getMonth() < 9 ? '0' + (timeDate.getMonth() + 1) : timeDate.getMonth() + 1;
					var day = timeDate.getDate() < 10 ? '0' + timeDate.getDate() : timeDate.getDate();
					title += ' : ' + timeDate.getFullYear() + '-' + month + '-' + day;
					
					if (timeUnit == 'hour') {
						var timeHour = new Date(config['time']['hour']);
						
						var hour = timeHour.getHours() < 10 ? '0' + timeHour.getHours() : timeHour.getHours();
						var minutes = timeHour.getMinutes() < 10 ? '0' + timeHour.getMinutes() : timeHour.getMinutes();
						title += ' ' + hour + ':' + minutes;
					}
				}
			}
			
			grid.setTitle(title);
			
			// Create the request generic parameters
			
			// Time parameters
	        var timeData = {};
	        timeData.id = timeUnit;
	        timeData.type = "ta";
	        timeData.order = "descending";
			
	        me.selectData = new Array(timeData);
	        
			// Counter parameters
	        var rawKpiId = config['kpis']['kpi']['id'];
	    	var rawKpiIds = rawKpiId.split('||');
	    	rawKpiIds.shift();
	    	
	        var rawKpiProductId = config['kpis']['kpi']['product_id'];
	        var rawKpiProductIds = rawKpiProductId.split('||');
	        rawKpiProductIds.shift();
	    	
	        var rawKpiType = config['kpis']['kpi']['type'];
	        var rawKpiTypes = rawKpiType.split('||');
	        rawKpiTypes.shift();    
	        	
	        for (var c = 0; c < rawKpiIds.length; c++) {
	        	var rawKpiData = {};
	            rawKpiData.id = rawKpiIds[c];        
	            rawKpiData.productId = rawKpiProductIds[c];
	            rawKpiData.type = rawKpiTypes[c]; 
	            
	            // Add the counter
	            me.selectData.push(rawKpiData);
	        }
	               
	        // Filters
	        me.filtersData = new Array();
			
			if (timeUnit == null || (typeof(timeUnit) != 'string') || timeUnit == '') {
				// Take the last value in the database
				timeFilter.id = 'maxfilter'; 
				timeFilter.type = 'sys';
				timeFilter.value = 1;
			} else {
				// Display the requested time value
				var timeDate = new Date(config['time']['date']);
				if (timeUnit == 'week') {
					var weekFilter = {};
					weekFilter.id = 'week'; 
					weekFilter.type = 'ta';
					var week = me.getWeekNumber(timeDate);
					weekFilter.value = week[0] + '' + (week[1] < 10 ? '0' + week[1] : week[1]);
					me.filtersData.push(weekFilter);
				} else {				
					var dayFilter = {};
					dayFilter.id = 'day'; 
					dayFilter.type = 'ta';
					var month = timeDate.getMonth() < 9 ? '0' + (timeDate.getMonth() + 1) : timeDate.getMonth() + 1;
					var day = timeDate.getDate() < 10 ? '0' + timeDate.getDate() : timeDate.getDate();
					dayFilter.value = timeDate.getFullYear() + '-' + month + '-' + day;
					me.filtersData.push(dayFilter);
					
					if (timeUnit == 'hour') {
						var timeHour = new Date(config['time']['hour']);
						
						var hourFilter = {};
						hourFilter.id = 'hour'; 
						hourFilter.type = 'ta';
						var hour = timeHour.getHours() < 10 ? '0' + timeHour.getHours() : timeHour.getHours();
						var minutes = timeHour.getMinutes() < 10 ? '0' + timeHour.getMinutes() : timeHour.getMinutes();
						hourFilter.value = hour + ':' + minutes;
						me.filtersData.push(hourFilter);
					}
				}
			}	
			
			// Get the networks
			me.networks = new Array();
			
			me.dataGrid = new Array();
			me.networksGrid = new Array();
			
	        var neId = config['network_elements']['ne']['id'];
	    	var neIds = neId.split('||');
	    	neIds.shift();
	    	
	        var neProductId = config['network_elements']['ne']['product_id'];
	        var neProductIds = neProductId.split('||');
	        neProductIds.shift();
	    	
	        var neLevel = config['network_elements']['ne']['network_level'];
	        var neLevels = neLevel.split('||');
	        neLevels.shift();    
	        
	        var neLabel = config['network_elements']['ne']['label'];
	        var neLabels = neLabel.split('||');
	        neLabels.shift();  
	        
	        var neLevelLabel = config['network_elements']['ne']['network_level_label'];
	        var neLevelLabels = neLevelLabel.split('||');
	        neLevelLabels.shift();  
	        
	        for (var n = 0; n < neIds.length; n++) {
	        	var ne = new Array(5);
	        	ne['id'] = neIds[n];
	        	ne['productId'] = neProductIds[n];
	        	ne['level'] = neLevels[n];
	        	ne['label'] = neLabels[n];
	        	ne['levelLabel'] = neLevelLabels[n];
	        	
	        	me.networks.push(ne);	        	
	        }
	        	        
	        me.load(config, me.networks.length - 1);
		} else {
			 var requestData = {};
	        requestData.method = "getDataAndLabels";
	        requestData.parameters = {};
	        requestData.parameters.select = {};
	        requestData.parameters.select.data = me.selectData;
	        requestData.parameters.filters = {};
			
			// Network Agregation
			var neData = {}; 
	        neData.type = 'na';
	        neData.operator = 'in';
	        neData.productId = me.networks[index]['productId'];
	        neData.value = me.networks[index]['id'];
		    neData.id = me.networks[index]['level'];	        
	        
	        var allFilters = new Array(neData);
	        allFilters = allFilters.concat(me.filtersData);
	        
	        requestData.parameters.filters.data = allFilters;
	        
	        var requestParam = {};
	        requestParam.data = Ext.encode(requestData);
	        
	        // Send the request
	        Ext.Ajax.request({
	        	url: 'proxy/dao/api/querydata/index.php',
	        	params: requestParam,

	            success: function (response) {	        	
	        		var error = false;
	        		var result = null;
	        		try {
	        			result = Ext.decode(response.responseText);
	        			if (typeof result['error'] != "undefined") {
	        				// The request send an error response
	        				error = true;
	        			} 
	        		} catch (err) {
	        			// The json is invalid 
	        			error = true;
	        		}
	        		
	        		if (index > 0) {
	        			// Get the value for the current index
	        			if (!error &&
	        				!me.inArray(me.networksGrid, result.labels[result.labels.length - 1].id)) {
	        				me.dataGrid.push(result);
	        				me.networksGrid.push(result.labels[result.labels.length - 1].id);
	        			}
	        			me.load(config, index - 1);
	        		} else {
	        			// Last call to load()
	        			if (!error &&
	        				!me.inArray(me.networksGrid, result.labels[result.labels.length - 1].id)) {
	        				// Add the last value
	        				me.dataGrid.push(result);
	        				me.networksGrid.push(result.labels[result.labels.length - 1].id);
	        			}
	        				
	        			// Get the chart
	        			var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
	        			var chart = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
	        			
        				// Create the data model
	        			var modelFields = new Array(
        					{
        						name: 'level',
	        					type: 'string'
        					},
        					{
        						name: 'network',
	        					type: 'string'
        					}
        				);
	        			var gridColumns = new Array(
        					{
        						header: 'Level',
	                      		dataIndex: 'level',
	                      		flex: 1
        					},
        					{
        						header: 'Network',
	                      		dataIndex: 'network',
	                      		flex: 1
        					}
	        			);

	        			if (me.dataGrid.length > 0) {
	        				for (var k = 0; k < me.dataGrid[0]['labels'].length - 1; k++) {	        				
		        				modelFields.push({
		        					name: me.dataGrid[0]['labels'][k]['id'],
		        					type: 'string'
		        				});
		        				
		        				gridColumns.push({
		                      		header: me.dataGrid[0]['labels'][k]['label'],
		                      		dataIndex: me.dataGrid[0]['labels'][k]['id'],
		                      		flex: 1 
		        				});
		        			}
	        			}	        			
	        			
	        			Ext.define('GridModel', {
	        	            extend: 'Ext.data.Model',
	        	            fields: [
    	                     	modelFields
	        	            ]
	        	        });
	        			
	        			// Create the data store
	        	        var gridStore = Ext.create('Ext.data.Store', {
	        	        	// destroy the store if the grid is destroyed
	        	        	autoDestroy: true,
	        	        	model: 'GridModel'
	        	        });
	        	        
	        	        // Populate the store
	        	        for (var d = 0; d < me.dataGrid.length; d++) {
	        	        	// Get the network index
	        	        	var neId = me.dataGrid[d]['labels'][me.dataGrid[d]['labels'].length - 1]['id'];
	        	        	var neIndex = -1;
	        	        	for (var n = 0; n < me.networks.length; n++) {
	        	        		if (me.networks[n]['id'] == neId) {
	        	        			neIndex = n;
	        	        			break;
	        	        		}
	        	        	}
	        	        	
	        	        	if (neIndex > -1) {
	        	        		var row = {
	        	        			level: me.networks[neIndex]['levelLabel'],
	        	        			network: me.networks[neIndex]['label']
	        	        		};
		        	        	for (var m = 0; m < modelFields.length; m++) {
	        	        			// Get the counter index in values.label
	        	        			var labelIndex = -1;
	        	        			for (var l = 0; l < me.dataGrid[d]['values']['label'].length; l++) {
	        	        				if (modelFields[m]['name'] == me.dataGrid[d]['values']['label'][l]) {
	        	        					labelIndex = l;
	        	        					break;
	        	        				}
	        	        			}
	        	        			
	        	        			// Add the value
	        	        			if (labelIndex > -1) {
	        	        				var counterValue = me.dataGrid[d]['values']['data'][0][l];
	        	        				if (counterValue == null || (typeof(counterValue) != 'string') || counterValue == '') counterValue = '-';
	        	        				row[modelFields[m]['name']] = counterValue;
	        	        			}
	        	        		}
		        	        	// Create a model instance
		                        var g = Ext.create('GridModel', row);
		                        
		                        // Insert the values
		                        gridStore.insert(gridStore.getCount(), g);
	        	        	}        	        		
        	        	}
	        	        
	        			// Destroy the wait panel
	        	        if (chart.down('panel') != null) {
	        	        	chart.down('panel').destroy();
	        	        }
	        	        
	        	        // Create the grid
	        	        var grid = Ext.create('Ext.grid.Panel', {
	            			flex:1,
	            			cls: 'x-panel-no-border',
	            			store: gridStore,
	            			columns: gridColumns
	        	        }); 
	        	        
	        	        // Add the grid
	        	        chart.add(grid);	        			
	        		}
	            }
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
	},
	
	inArray: function (array, val) {
	    var l = array.length;
	    for(var i = 0; i < l; i++) {
	        if(array[i] == val) {
	            return true;
	        }
	    }
	    return false;
	}
		
});
