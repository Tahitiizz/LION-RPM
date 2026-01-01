Ext.define('homepage.controller.charts.CellsSurveillance', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.CellsSurveillance'
	],
		
	config : null,
	previous : null,
	grids : null,
	
	init: function() {
		var me = this;
		
		this.control({
			'cellssurveillance': {
	        	load : this.load
	        },
	        'button[action=loadpreviousmonth]' : {
				click : this.loadprev
			},
			'button[action=loadcurrentmonth]' : {
				click : this.loadcurrent
			}
	    });
	},
	
	loadprev: function() {
		var me = this;
		me.previous=true
		me.load(null);
	},
	
	loadcurrent: function() {
		var me = this;
		me.previous=null;
		me.load(null);
	},
	
	load: function(config) {	
		var me = this;
		
		if (config == null || Ext.isObject(config.config)) {
			config = me.config;
		} else {
			me.config = config;
		}
		
		// (Re)initialize the grids array
		me.grids = null;
		me.grids = new Array();
		
		// Get the chart
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		var chartId = tabId + '_' + config['widgets']['widget'][0]['@attributes']['id'] + '_cellssurveillancemain';
		var chart = Ext.getCmp(chartId);

		//remove all components (each call to load create all the grids)
		chart.removeAll();
		
		var gridArrayColumns = new Array(
			{
				header: 'Cell',
          		dataIndex: 'cell_id',
          		flex: 3
			},
			{
				header: 'Cell label',
          		dataIndex: 'cell_label',
          		hidden: true,
          		hideable: false,
          		//flex: 3
			},
			{
				header: 'Parent node',
          		dataIndex: 'parent',
          		flex: 3
			},
			{
				header: 'Detected in reference period',
          		dataIndex: 'in_ref_period',
          		flex: 2,
          		renderer: function (value) {
          			if (value == 1) return 'Yes';
          			else return 'No';
          		}
			},
			{
				header: 'Days in default',
          		dataIndex: 'days_in_default',
          		flex: 2
			},
			{
				header: 'Days before to be penalised',
          		dataIndex: 'days_before_penalisation',
          		flex: 2
			}
		);	
		
		/*
		
		//if the product is already set for this tab, disable the product combobox
		if(config['widgets']['widget'][0]['sdp_id']!==''){
			//if the config panel is opened
			if( typeof(Ext.getCmp('configPanel')) !== 'undefined' && !Ext.getCmp('configPanel').hidden){
				Ext.getCmp('productCombo_configChart').setDisabled(true);
				
			}
		
		}
		
		*/
		
		/*
		
		//remove disclaimer
		if ((typeof(configPanel) != 'undefined') && !configPanel.hidden){
			if(typeof(Ext.getCmp('alarmDisclaimer'))!=='undefined'){
				Ext.getCmp('configChart').remove('alarmDisclaimer');
			}
		}
		
		*/
		//get reference periof from general.xml conf file
		var refPeriod = Ext.getCmp('viewport').referenceperiod;
			
		//get ratio for penalisation
		var ratio = config['@attributes']['ratio'];
		
		//get get number of days for penalisation
		var nbdays = config['@attributes']['nbdays'];
		
		//get get the selected mode
		var selectedmode = config['@attributes']['selectedmode'];
		
		//restrain full tab config to widget config (only one widget in template 7)
		config = config['widgets']['widget'][0];
		
		
		//is static alarms defined on product
		/*
		if ((typeof(configPanel) != 'undefined') &&
	            !configPanel.hidden){
			
			//no alarms defined
			if(Ext.getCmp('counterGrid_configChart').getStore().getCount()==0){
				//a user can't save with no alarm defined on product
				
				if ((typeof(configPanel) != 'undefined') && !configPanel.hidden){
					Ext.getCmp('saveButton').setVisible(false);
					Ext.getCmp('displayButton').setVisible(false);
				
	               
					//display a disclaimer for the user
					var noAlarmsDisclaimer=Ext.create('Ext.form.Label', {														
						xtype: 'label',						
						id: 'alarmDisclaimer',
						html: '<br/>No alarms set for this product.<br/><br/>You should activate alarms first through the administration interface.',
						border: 0,
						style: {
		            	      			color: 'red'
		            	  	}
					});	
	
					var configChart=Ext.getCmp('configChart');
					configChart.add(noAlarmsDisclaimer);
					
					// hide template's fields
					//configChart.down('combobox[id="productCombo_configChart"]').setVisible(false);
					configChart.down('fieldcontainer[id="alarmBox_configChart"]').setVisible(false);
					configChart.down('gridpanel[id="AlarmGrid_configChart"]').setVisible(false);
					configChart.down('fieldset[id="alarmField_configChart"]').setVisible(false);
					configChart.down('numberfield[id="alarmRatioNumberfield_configChart"]').setVisible(false);
					configChart.down('numberfield[id="alarmDayNumberfield_configChart"]').setVisible(false);
				}
			}
		}
		else{
		*/
			//alarms defined
			
			//no alarms configured 
			//in case there's no alarms configured yet by the user
		/*	
		if(!Ext.isDefined(config['alarms']['alarm']) || typeof(config['alarms']['alarm'].length)=='undefined'){
				Ext.getCmp(tabId+'_chart1_cellssurveillancecollecting').update('No alarms configured');
			}
			else{
			*/
				//alarms configured, do request
				
				
				/*
				//show save and display button
				if ((typeof(configPanel) != 'undefined') &&
			            !configPanel.hidden){
				           Ext.getCmp('saveButton').setVisible(true);
				           Ext.getCmp('displayButton').setVisible(true);
				} 
				*/
				
				//get last integration date for the sdp_id, will be used as current_date for calculations
				var lastintegrationdate = "";
				
				Ext.Ajax.request({
		        	url: 'proxy/configuration.php',
		        	params: {
						task: 'LAST_DATE',
						sdp_id: config['sdp_id']				
		      		},
		
		            success: function (response) {
		            	lastintegrationdate = response.responseText;
		            	
		            	if(me.previous){
		            		lastintegrationdate=Ext.Date.format(Ext.Date.add(Ext.Date.parse(lastintegrationdate, 'Ymd'), Ext.Date.MONTH, -1),'Ymd');
		            	}
		            	
		            	//query parameters
		        		var alarmOptions = {};
		                alarmOptions.sdp_id = config['sdp_id'];
		                alarmOptions.current_date = lastintegrationdate;
		                alarmOptions.ref_period = refPeriod;
		                alarmOptions.min_days = config['minnumberofdays'];
		                alarmOptions.selectedmode = selectedmode;
		                alarmOptions.ratioforpenalisation = ratio;
		                alarmOptions.nbdaysforpenalisation = nbdays;
		        		//loop through alarms for this sdp_id (one sdp_id per tab)
		        		Ext.Array.each(config['alarms']['alarm'], function(alarm, index) {
		        			//store
		        			var csStore = Ext.create('Ext.data.Store', {
		        	        	// destroy the store if the grid is destroyed
		        	        	autoDestroy: true,
		        	        	model: 'CSModel',
		        				fields: ['cell_id', 'cell_label', 'in_ref_period', 'days_in_default', 'days_before_penalisation'],
		        	        	data: {}
		        	        });
		        			
		        	        alarmOptions.alarm_id = alarm.id ;
		        	        
		        	        var requestParam={};
		        	        requestParam.alarmOptions = Ext.encode(alarmOptions);	
		
		        	        //check that all parameters are set before to query, otherwise query failed
		        	        if(typeof(alarm.id.length)!=='undefined'){
		        	       
			        	        // Send the request
			        	        Ext.Ajax.request({
			        	        	url: 'proxy/alarm_list.php',
			        	        	params: {
			    						task: 'GET_CELLSSURVEILLANCE',
			    						params: {params: Ext.encode(alarmOptions)}
			    						
			    						
			    		      		},
			
			        	            success: function (response) {
			        	            	var datas = Ext.decode(response.responseText);
			        	                   	            	
			        	            	csStore.loadData(datas);
			        	            	        	            	
			        	            	// Destroy the wait panel
			        	    	        if (Ext.getCmp(tabId + '_' + config['@attributes']['id'] + '_cellssurveillancecollecting') != null) {
			        	    	        	Ext.getCmp(tabId + '_' + config['@attributes']['id'] + '_cellssurveillancecollecting').destroy();
			        	    	        }
						        	    	      
			        	    	        // Create the grid
			        	    	        var grid = Ext.create('Ext.grid.Panel', {
			        	        			title: (typeof(alarm.grid_name).length !== 'undefined' && alarm.grid_name !== '[object Object]' ? alarm.grid_name : " "),
			        	        			store: csStore,	
			        	        			columns: gridArrayColumns,
			        	        			flex: 1,
			        	        			sortableColumns: true,
			        	        			enableColumnHide: true,
			        	        			anchor: '100% none',
			        	        			padding: '10 10 0 10',
			        	        			margin: 0,
			        	        			dockedItems: [
			        			              {
			        			            	  dock: 'top',
			        			            	  layout: 'hbox',
			        			            	  border: 1,
			        			            	  style: {
			        			            	      borderStyle: 'solid',
			        			            	      borderWidth: '1px',
			        			            	      //borderColor: '#99BCE8'
			        			            	  },
			        			            	  padding: '10 10 10 10',
			        			            	  cls: 'x-panel-no-border',
			        			            	  items: [
			        			            	      {
			        			            	    	  xtype: 'image',
			        			            	    	  padding: '0 5 0 0',
			        			            	    	  src: 'images/icons/information.png'
			        			            	      },
			    			            	          {
			    			            	        	  xtype: 'label',
			    			            	        	  cls: 'label_CellsSurveillance',
			    			            	        	  html: typeof(alarm.comment).length !== 'undefined' && decodeURIComponent(alarm.comment) !== '[object Object]' ? decodeURIComponent(alarm.comment).replace(/[\n]/g, '<br/>') : ''
			    			            	          }    			            	          
			        			            	  ]
			        			              }
			        			            ],
			        			            viewConfig: {
			        			                getRowClass: function(record, index, rowParams)
			        			                {
			        			                	if (record.data.days_before_penalisation <= 0) 
			        			                		return 'redRow';
			        			                }
			        			            },
			        	        			listeners: {
			    	        		        	itemclick : function (grid, rowIndex, e) {
				        		            		var dayOfMonth = lastintegrationdate.substring(6);
				        		            		
				        		            		var params = new Array();
				        		            		params['selecteur[ta_level]'] = 'day';
				        		            		params['selecteur[date]'] = dayOfMonth + '/' + lastintegrationdate.substring(4, 6) + '/' + lastintegrationdate.substring(0, 4);
				        		            		params['selecteur[period]'] = dayOfMonth;
				        		            		params['selecteur[na_level]'] = 'cell';
				        		            		params['selecteur[nel_selecteur]'] = 'cell||' + rowIndex.data.cell_id;
				        		            		if(typeof(alarm.dashboard).length !== 'undefined' && alarm.dashboard !== '[object Object]'){
					        		            		me.postToUrl('../dashboard_display/index.php?id_dash=' + alarm.dashboard + '&mode=overtime', params, 'post');
				        		            		}
			    	        		        	}
			    	        		    	}
			        	    	        }); 	        
			        	    	        
			        	    	       // Add the grid in the grids array
			        	    	       var newGrid = {};
			        	    	       newGrid['label'] = alarm.label;
			        	    	       newGrid['grid'] = grid;
			        	    	       newGrid['alarm_id']=alarm.id;
			        	    	       me.grids.push(newGrid);
			        	    	       
			        	    	       // Last callback ?
			        	    	       // special behaviour when only one alarm defined, config['alarms']['alarm'].length is undefined
			        	    	       if (me.grids.length == config['alarms']['alarm'].length || (me.grids.length==1 && typeof(config['alarms']['alarm'].length)=='undefined')) {
			        	    	    	  me.insertGrids(chartId,config['sdp_id']);
			        	    	       }
			        	            }
			        	        });
		        			}
		        		});
		            }
		        });	
			//}	
		//}
	}, 
	
	// Add the grids from the grid array to the panel
	insertGrids: function(chartId,sdp_id) {
		var me = this;
		var chart = Ext.getCmp(chartId);
		
		//get alarms labels defined in T&A
		Ext.Ajax.request({
			url: 'proxy/configuration.php',
			params: {
				task: 'GET_ALARMS',
				product: sdp_id
			},
    
			success: function(response) {
				// Add the alarms in the combobox
				var alarms = Ext.decode(response.responseText).alarm;
				
				var alarms_array= new Array();
				Ext.Array.each(alarms, function(alarm, index) {
					alarms_array[alarm.id]=alarm.label;
				});	
					
				for (g = 0; g < me.grids.length; g++) {
					me.grids[g]['ta_label']=alarms_array[me.grids[g]['alarm_id']];
				}
				
				alarms_array=null;
				
				// Sort the grids on the alarm label
				me.grids.sort(function(a, b) {
					return a['ta_label'].localeCompare(b['ta_label']);
					
				});
				
				// Add the grids
				for (g = 0; g < me.grids.length; g++) {
					chart.add(me.grids[g]['grid']);
				}
																		
			}
		});
		
		
			
		
	},
	
	postToUrl: function (path, params, method) {
	    method = method || "post"; // Set method to post by default, if not specified.

	    // The rest of this code assumes you are not using a library.
	    // It can be made less wordy if you use one.
	    var form = document.createElement("form");
	    form.setAttribute("method", method);
	    form.setAttribute("action", path);

	    for(var key in params) {
	        if(params.hasOwnProperty(key)) {
	            var hiddenField = document.createElement("input");
	            hiddenField.setAttribute("type", "hidden");
	            hiddenField.setAttribute("name", key);
	            hiddenField.setAttribute("value", params[key]);

	            form.appendChild(hiddenField);
	         }
	    }

	    document.body.appendChild(form);
	    form.submit();
	}
});
