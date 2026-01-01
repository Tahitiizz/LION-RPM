Ext.define('homepage.controller.charts.Map', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.Map'
	],

	config : null,
	map:null,
	mapId: null,
	data: null,
	networks: null,
	dataDonut: null,
	dataTrend: null,
	colors:null,
	displayMode: null,
	roaming: null,
	displayed_value_mode: null,
	parent_level_selected: null,
	nes: null,
	trendTimeLevel:null,
	donutTimeLevel: null,
	trendTimeStore: null,
	donutTimeStore: null,
	KpiSelectorStore: null,
	kpiGroupIndex: null,
	isIpad: null,
	firstLoad: null,
	KpiName: null,
	
	init: function() {
		var me = this;

		this.control({
			'map': {
	        	load : this.load
	        }
	    });
				
		//get selected map from map.xml file
		Ext.Ajax.request({
			url: 'proxy/configuration.php',
			async: false,
			params: {
				task: 'GET_MAPID'
			},

			success: function(response) {
				// Decode the json into an array object
				if (response.responseText != '') {
					me.mapId = Ext.decode(response.responseText)[0];
				}				
			}
		});	
		
		Ext.create('Ext.form.Panel', {
	    //renderTo : Ext.getBody(),
	    width    : 0,
	    height   : 0,
	    id : 'hiddenPannel',
	    title    : '',

	    items    : [
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] + '_neLevelId_configMap'
	            id  : 'neLevelId_configMap'
	           
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neLevelId2_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neLevelLabel_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neLevelLabel2_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'associationResult_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neId_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neId2_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neLabel_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neLabel2_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'neProductId_configMap'
	        },
	        {
	            xtype : 'hiddenfield', 
	            //id  : tabId + '_' + config['@attributes']['id'] +'_neLevelId2_configMap'
	            id  : 'parentLevelSelected_configMap'
	        }
	        
	        
	    ]
		});
		
		me.associationStore = Ext.create('Ext.data.Store', {
        	// destroy the store if the grid is destroyed
        	id: "associationStoreMap",
        	autoDestroy: true,
        	model: 'AssociationModel',
			fields: ['date', 'value', 'NeIdAxe1',{name: 'NeIdAxe2', type: 'string', defaultValue: ""}],
        	data: {}
        });
		
		me.trendTimeStore = Ext.create('Ext.data.Store', {
        	autoLoad: true,
        	model: 'TimeSelectorModel',
			fields: ['id','value'],
        	data: [
        	       {"id":"hour","value":"Hour"},
        	       {"id":"day","value":"Day"},
        	       {"id":"day_bh","value":"Day BH"},
        	       {"id":"week","value":"Week"},
        	       {"id":"week_bh","value":"Week BH"},
        	       {"id":"month","value":"Month"},
        	       {"id":"month_bh","value":"Month BH"}
			]
        });
		
		me.donutTimeStore = Ext.create('Ext.data.Store', {
        	autoLoad: true,
        	model: 'TimeSelectorModel',
			fields: ['id','value'],
			data: [
        	       {"id":"hour","value":"Hour"},
        	       {"id":"day","value":"Day"},
        	       {"id":"day_bh","value":"Day BH"},
        	       {"id":"week","value":"Week"},
        	       {"id":"week_bh","value":"Week BH"},
        	       {"id":"month","value":"Month"},
        	       {"id":"month_bh","value":"Month BH"}
			]
        });
		
		me.KpiSelectorStore = Ext.create('Ext.data.Store', {
        	autoLoad: true,
        	model: 'KpiSelectorModel',
			fields: ['group_name',/*'group_id',*/'group_index'],
        	data: []
        });
		
		me.isIpad=Ext.get('isIpad').dom.value == 1 ? true : false;
	},
	
	load: function(config) {
		var me = this;
		//set kpi group index to zero at load
		var groupindex=0;
		
		if(typeof(config['kpi_groups']['group']).length=='undefined'){
			var saveconfig=config['kpi_groups']['group'];
			config['kpi_groups']['group']=new Array(1);
			config['kpi_groups']['group'][0]=new Array(1);
			config['kpi_groups']['group'][0]=saveconfig;
		}
		
		
		if(typeof(config['kpi_groups']['group'][groupindex]['kpis']) !== "undefined"){
			 if (typeof(config['kpi_groups']['group'][groupindex]) !== 'undefined'){
			//if(config['roaming'] != 'true'){
				current_product_id = config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'];
			}else{
				current_product_id = config['kpi_groups']['group']['kpis']['kpi_trend']['product_id'];
			}
		}else{
			//Ext.getCmp('mapKpiGrid_configChart_roaming').store.loadData([],false);
			return false;
		}

		Ext.Ajax.request({
			url: 'proxy/configuration.php',
			async:false,
			params: {
				task: 'IS_ROAMING',
				sdp_id: current_product_id		
	      	},
	
			success: function(response) {
				var statHour = response.responseText;
				if(statHour == "0"){
					me.trendTimeStore = Ext.create('Ext.data.Store', {
			        	autoLoad: true,
			        	model: 'TimeSelectorModel',
						fields: ['id','value'],
			        	data: [
			        	       {"id":"day","value":"Day"},
			        	       {"id":"week","value":"Week"},
			        	       {"id":"month","value":"Month"}
						]
			        });
		
					me.donutTimeStore = Ext.create('Ext.data.Store', {
			        	autoLoad: true,
			        	model: 'TimeSelectorModel',
						fields: ['id','value'],
						data: [
			        	       {"id":"day","value":"Day"},
			        	       {"id":"week","value":"Week"},
			        	       {"id":"month","value":"Month"}
						]
			        });
				}else{
					me.trendTimeStore = Ext.create('Ext.data.Store', {
			        	autoLoad: true,
			        	model: 'TimeSelectorModel',
						fields: ['id','value'],
			        	data: [
			        	       {"id":"hour","value":"Hour"},
			        	       {"id":"day","value":"Day"},
			        	       {"id":"day_bh","value":"Day BH"},
			        	       {"id":"week","value":"Week"},
			        	       {"id":"week_bh","value":"Week BH"},
			        	       {"id":"month","value":"Month"},
			        	       {"id":"month_bh","value":"Month BH"}
						]
			        });
					
					me.donutTimeStore = Ext.create('Ext.data.Store', {
			        	autoLoad: true,
			        	model: 'TimeSelectorModel',
						fields: ['id','value'],
						data: [
			        	       {"id":"hour","value":"Hour"},
			        	       {"id":"day","value":"Day"},
			        	       {"id":"day_bh","value":"Day BH"},
			        	       {"id":"week","value":"Week"},
			        	       {"id":"week_bh","value":"Week BH"},
			        	       {"id":"month","value":"Month"},
			        	       {"id":"month_bh","value":"Month BH"}
						]
			        });
				}
			}
		});	
		
		
		
		Ext.getCmp('neLevelId_configMap').setValue('');
		Ext.getCmp('neLevelId2_configMap').setValue('');
		
		//Ext.getCmp('parentLevelSelected_configMap').setValue('');
		
		if(config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['network_axis_number'] == '2'){
				var axis = 2;
			}else{
				var axis = 1;
			}
		me.firstLoad=true;
		/**
		var hiddenPannel = Ext.getCmp('hiddenPannel');
		if(hiddenPannel){
			hiddenPannel.destroy();
		}
		**/
		//in case config is not passed to load, get config from the object
		if (config == null) {
			config = me.config;
		} else {
			me.config = config;
		}

		
		Ext.define('AssociationModel', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'date', type: 'string'},
            {name: 'value', type: 'string'},
            {name: 'NeIdAxe1', type: 'string'},
            {name: 'NeIdAxe2', type: 'string'}
        ]
	    });

        /**
        //TODO check if we realy have to use this code
		//in only one kpi group
		if(typeof(config['kpi_groups']['group']).length=='undefined'){
			var saveconfig=config['kpi_groups']['group'];
			config['kpi_groups']['group']=new Array(1);
			config['kpi_groups']['group'][0]=new Array(1);
			config['kpi_groups']['group'][0]=saveconfig;
		}
		**/
	    
	    
		/**
		if(typeof(config['kpi_groups']['group']).length=='undefined'){
			var saveconfig=config['kpi_groups']['group'];
			config['kpi_groups']['group']=new Array(1);
			config['kpi_groups']['group'][0]=new Array(1);
			config['kpi_groups']['group'][0]=saveconfig;
		}
		**/
		
		// Get the chart
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		var map = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
		
		me.donutTimeLevel='';
		me.trendTimeLevel='';

		me.kpiGroupIndex=0;
	
		//nothing to do, index set to zero by default
		//load selectors
		me.donutTimeLevel=config['donut_time_level'];
		me.trendTimeLevel=config['trend_time_level'];

		//check mode: fullscreen or trend/donut
		me.displayMode=config['fullscreen'];
		//TODO default value if empty
		
		//check mode roaming
		me.romaing=config['roaming'];
		
		//check displayed value mode
		me.displayed_value_mode=config['displayed_value_mode'];
		
		//check parent_level_selected
		me.parent_level_selected=config['parent_level_selected'];
		//global result array
		me.data = new Array();
		//global network elements array
//		me.networks = new Array();		
		//load kpi selector store
		var kpiselector=new Array();
		Ext.Array.each(config['kpi_groups']['group'], function(group, i) {
			me.groupData={};
			me.groupData.group_name=group.group_name;
			me.groupData.kpi_label=group.kpis.kpi_trend.label;
			me.groupData.group_index=i;
			kpiselector.push(me.groupData);
		});	
		

		
		/**
		Ext.Array.each(config['kpi_groups']['group'], function(group, index) {
				var groupData={};
				groupData.group_name=group.group_name;
				groupData.group_index=index;
				kpiselector.push(groupData);

		});	
		**/
		me.KpiSelectorStore.loadData(kpiselector);
        me.nes={};
        me.nes.ids=new Array();
        me.nes.ids2=new Array();
        me.nes.idsindex=new Array();
        me.nes.zones=new Array();
        me.nes.labels=new Array();
        me.nes.zoneslabels=new Array();
        me.nes.idvalueparent=new Array();
		
       /** 
      	Ext.Ajax.request({
			url : 'proxy/configuration.php',
			async : false,
			params : {
				task : 'GET_AMMAPID',
				sdp_id : config['kpi_groups']['group'][0]['kpis']['kpi_trend']['product_id'],
				selected_level : axis == 2 ?  neLevelAxe2 : neLevelAxe1
			},
			
			success : function(response) {
				me.ammapIds=Ext.decode(response.responseText);
				console.log(me.ammapIds);
			}
      	});
      	**/
      	
        /**
    	for (var i = 0; i < me.ammapIds['data'].length; i++) {
			//parentIdArray.push(me.ammapIds['data'][i]['mapId']);
			if(axis == 1){
				if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
					me.nes.ids2.push(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2']);
				}	
			}else{
				me.nes.ids.push(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id']);
			}
		}
       **/
        /**
		//loop through all ne to get NE id and map zone id
		Ext.Array.each(config['network_elements']['network_element'], function(ne, index) {
			me.nes.ids.push(ne.ne_id);
			me.nes.zones[ne.ne_id]=ne.map_zone_id;
		});	
		**/
		
		//TODO gestion 3eme axe en mode normal
		
		var requestData = {};
    
      
	    requestData={
	        		nelist:me.nes.ids.join(','),
	        		na:config['network_elements']['network_level'],
	        		product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
	        		order: 'asc'
	     };
	    requestParam = requestData;
		//get selected map from map.xml file
		Ext.Ajax.request({
			url: 'proxy/ne_labels.php',
			async:false,
			params: requestParam,

			success: function(response) {
				// Decode the json into an array object
				if (response.responseText != '') {
					var labelsarray=Ext.decode(response.responseText);
					for(i=0;i<labelsarray.length;i++){
						me.nes.labels.push(labelsarray[i].label);
						me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
						me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
					}
				}				
			}
		});	
		if(config['roaming'] == 'false'){
			var currentNeId = "";
			var currentNeId2 = "";
			me.blockedAxe = 1;
			//loop through all ne to get NE id and map zone id
			Ext.Array.each(config['network_elements']['network_element'], function(ne, index) {
				if(typeof(ne.ne_id2) !== 'undefined'&& typeof(ne.ne_id2) !== 'object' && ne.ne_id2 !== ''){
					if(index == 0){
						currentNeId = ne.ne_id;
						currentNeId2 = ne.ne_id2;
					}else if (index == 1){
						if(ne.ne_id == currentNeId){
							me.blockedAxe = 1;
						}else{
							me.blockedAxe = 2;
						}
					}
					me.nes.ids.push(ne.ne_id);
					me.nes.ids2.push(ne.ne_id2);
				}else{
					me.nes.ids.push(ne.ne_id);
					me.nes.zones[ne.ne_id]=ne.map_zone_id;
				}
				
			});	
			
			//If we have product with 3 network axis we have to reloop through network element to fill zones
			if(typeof(config['network_elements']['network_element'][0]['ne_id2']) !== 'undefined' && typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'object' && config['network_elements']['network_element'][0]['ne_id2'] !== ''){
				Ext.Array.each(config['network_elements']['network_element'], function(ne, index) {
					if (me.blockedAxe == 2){
						me.nes.zones[ne.ne_id]=ne.map_zone_id;
					}else{
						me.nes.zones[ne.ne_id2]=ne.map_zone_id;
					}
				});
			}

			var requestData = {};
        	if(typeof(config['network_elements']['network_element'][0]['ne_id2']) !== 'undefined' && typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'object' && config['network_elements']['network_element'][0]['ne_id2'] !== ''){
		        if(me.blockedAxe == 2){
				    requestData={
				        		nelist:me.nes.ids.join(','),
				        		na:config['network_elements']['network_level'],
				        		product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
				        		order: 'asc'
				     };
		        }else{
		        	requestData={
				        		nelist:me.nes.ids2.join(','),
				        		na:config['network_elements']['network_level2'],
				        		product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
				        		order: 'asc'
				     };
		        }
        	}else{
        		 requestData={
				        		nelist:me.nes.ids.join(','),
				        		na:config['network_elements']['network_level'],
				        		product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
				        		order: 'asc'
				 };
        	}
	        
		    requestParam = requestData;
			//get selected map from map.xml file
			Ext.Ajax.request({
				url: 'proxy/ne_labels.php',
				async:false,
				params: requestParam,
	
				success: function(response) {
					// Decode the json into an array object
					if (response.responseText != '') {
						var labelsarray=Ext.decode(response.responseText);
						for(i=0;i<labelsarray.length;i++){
							me.nes.labels.push(labelsarray[i].label);
							me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
							me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
						}
					}				
				}
			});	
		      
        }
		
        /**else{
			var neSelectData = {};
	       	var neSelectData2 = {};
	        var neSelectData3 = {};
	        var neData = {}; 
	        var neData2 = {}; 
	        //neLevelAxe1 = config['network_elements']['network_level'];
	        //neLevelAxe2 =config['network_elements']['network_level2'];
	        neLevelAxe1 = config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['roaming_network_level'];
	        neLevelAxe2 =config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['roaming_network_level2'];
			productId = config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'];
			
	        var neSelectData = {};
			neSelectData.id=neLevelAxe1;
			neSelectData.type="na";
			neSelectData.order = "Ascending";
			
			if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
				var neSelectData2 = {};
				neSelectData2.id=neLevelAxe2;
				neSelectData2.type="na_axe3";
				neSelectData2.order = "Ascending";
			}
	        
	        
	        var requestDataChild = {};
	        requestDataChild.method = 'getChild';
	        requestDataChild.parameters = {};
	        requestDataChild.parameters.father = {};
	   
	        if(axis == 2){
	        	requestDataChild.parameters.father.id = neLevelAxe2;
	        }else{
	        	requestDataChild.parameters.father.id = neLevelAxe1;
	        }
			requestDataChild.parameters.father.productId = productId;
	
	        var requestParamChild = {};
	        requestParamChild.data = Ext.encode(requestDataChild);	
			
			var selectedValueMode = config['displayed_value_mode'];
			console.log(axis);
			console.log(neLevelAxe2);
			console.log(neLevelAxe1);
			
			Ext.Ajax.request({
				url : 'proxy/configuration.php',
				async : false,
				params : {
					task : 'GET_AMMAPID',
					sdp_id : config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
					selected_level : axis == 2 ?  neLevelAxe2 : neLevelAxe1
				},
				
				success : function(response) {
					//me.ammapIds = response.responseText;
					me.ammapIds=Ext.decode(response.responseText);
					console.log(me.ammapIds);
					for (var i = 0; i < me.ammapIds['data'].length; i++) {
						//parentIdArray.push(me.ammapIds['data'][i]['mapId']);
						if(axis == 1){
							if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
								me.nes.ids2.push(me.ammapIds['data'][i]['mapId']);
							}
						}else{
							me.nes.ids.push(config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['roaming_ne_id']);
						}
					}
				}
        	});
			
			
			
			if(axis == 1){
				
				var neData = {}; 
				neData.type = 'na';
				neData.operator = 'in';
				//neData.id= neLevelAxe1;
			
				//In case we are in a roaming product with no 3rd axe
				if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
					var neData2 = {}; 
					neData2.type = 'na_axe3';
					neData2.operator = 'in';
					neData2.id = neLevelAxe2;
					neData2.value =me.nes.ids2.join(',');
				}
				
			}else{
				var neData = {}; 
				neData.type = 'na';
				neData.operator = 'in';
				neData.id= neLevelAxe1;
				neData.value= me.nes.ids.join(',');
				//value neData récupéré après
				
				var neData2 = {}; 
				neData2.type = 'na_axe3';
				neData2.operator = 'in';

			}
			
			var requestDataLabelChildren = {};
			
				Ext.Ajax.request({
		        	url: 'proxy/dao/api/querydata/index.php',
		        	params: requestParamChild,
		        	async:false,
		            success: function (response) {
						error = false;
						try {
		        			var childLevel = response.responseText;
		        			if (typeof childLevel['error'] != "undefined") {
		        				// The request send an error response
		        				error = true;
		        			}
		        		} catch (err) {
		        			// The json is invalid
		        			error = true;
		        		}if(error == false){
		        			
		        			if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
			        			var neSelectData3 = {};
								neSelectData3.id=childLevel;
								neSelectData3.type="na_axe3";
								neSelectData3.order = "";
					            if(axis == 1){
					           		neData.id = childLevel;
					           	}else{
									neData2.id = childLevel;
					            }
		        			}else{
		        				var neSelectData3 = {};
								neSelectData3.id=childLevel;
								neSelectData3.type="na";
								neSelectData3.order = "";
								neData.id = childLevel;
		        			}
		        			
							
				            // Search field
								var searchOptions = {
									text: null,			// Text field value
									products: []
								};
				            	if(axis == 1){
									//on cherche les enfant de operator
									var productItem = {
										id: productId,
										na: childLevel,
										axe: ''
									}
								}else{
									
									var productItem = {
										id: productId,
										na: childLevel,
										axe: 3
									}
								}
								searchOptions.products.push(productItem);
						        searchOptions = Ext.encode(searchOptions);
					
						        var idArray = new Array();
						        
								Ext.Ajax.request({
						        	url: 'proxy/ne_listhtml.php',
						        	async : false,
						        	params: {
					    				roaming: true,
					    				filterOptions: searchOptions
					          		},
						
						            success: function (response) {
						        			var result = Ext.decode(response.responseText).data;
										    Ext.each(result, function(value) {
											      Ext.each(value.parent_id , function(k,v){
												       	if(axis == 1){
											      			me.nes.ids.push(k);
											      			//me.nes.zones[k]=config['network_elements']['network_element'][i]['map_zone_id'];
											      			
												       	}else{
												       		me.nes.ids2.push(k);
															//me.nes.zones[k]=config['network_elements']['network_element'][i]['map_zone_id'];
												       	}
												    });
										    });
										    
										    if(axis == 1){
										    	neData.value= me.nes.ids.join(',');
										    	//Pour récupéer les labels
										    	requestDataLabelChildren={
													nelist:me.nes.ids.join(','),
													na:childLevel,
											    	product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
											    	order: 'asc'
											    };
										    }else{
										    	neData2.value= me.nes.ids2.join(',');
										    	requestDataLabelChildren={
													nelist:me.nes.ids2.join(','),
													na:childLevel,
											    	product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
											    	order: 'asc'
											    };
										    }
										    var fullmapTimeLevel = config['fullscreen_time_level'];
									    	var timeData = {};
									        timeData.id = fullmapTimeLevel;
									        timeData.type = "ta";
									        timeData.order = "Descending"; // get the last value available
									        
									        var rawKpiId = config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['kpi_id'];
						        			var rawKpiProductId =config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'];
						       				var rawKpiType = config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['type']
									        var rawKpiData = {};
									        rawKpiData.id = rawKpiId;        
									        rawKpiData.productId = rawKpiProductId;
									        rawKpiData.type = rawKpiType; 
								        	
									        // Only get the last value in the database
											
											var timeNumber = config['units_number']*config['network_elements']['network_element'].length;
											if (timeNumber == null || 
												((typeof(timeNumber) != 'string') && (typeof(timeNumber) != 'number')) ||
												timeNumber == '') {
											
												timeNumber = 20;
											}
									        
									        
									        limitFilter = {};
											limitFilter.id = 'maxfilter'; 
											limitFilter.type = 'sys';
											//limitFilter.date = '20130409';
											limitFilter.value = timeNumber;
									        
									     	if(axis == 1){
									     		if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
									     			//Case of product with 3rd axe from wich we have to get children on axe 2
									     		}else{
									     			var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData3);
								        			var filtersData = new Array(neData,limitFilter); 
									     			
									     		}
									     	}else{
									     		var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2,neSelectData3);
								        		var filtersData = new Array(neData,neData2, limitFilter); 
									     	}
								        	
									        	

									        
									        var requestData = {};
									        requestData.method = 'getDataAndLabels';
									        requestData.parameters = {};
									        if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
									        	requestData.parameters.roaming = true;
									        }else{
									        	 requestData.parameters.mapid = true;
									        }
									        requestData.parameters.select = {};
									        requestData.parameters.select.data = selectData;
									        requestData.parameters.filters = {};
									        requestData.parameters.filters.data = filtersData;
									       
									        var requestParam = {};
									        requestParam.data = Ext.encode(requestData);
									        Ext.Ajax.request({
									        	url: 'proxy/dao/api/querydata/index.php',
									        	params: requestParam,
												async: false,
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
									        		if(error == false){
									        			Ext.each(result.values, function(value) {
														      Ext.each(value.data , function(k,v){
															       	if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
															       		me.nes.zones[k[4]]=k[3];
															       	}else{
															       		me.nes.zones[k[3]]=k[2];
															       	}
															    });
													    });
	
				
									        		}
									            }
											});	
									        
								     }
								});
		        			}
		            }, failure:  function(response, options) {
	                    console.log('Error', "Communication failed");
	                }
		        });   

        } 
		var requestParamChildren = {};
		requestParamChildren = requestDataLabelChildren;
		//get selected map from map.xml file
		Ext.Ajax.request({
			url: 'proxy/ne_labels.php',
			async:false,
			params: requestParamChildren,

			success: function(response) {
				// Decode the json into an array object
				if (response.responseText != '') {
					var labelsarray=Ext.decode(response.responseText);
					for(i=0;i<labelsarray.length;i++){
						me.nes.labels.push(labelsarray[i].label);
						me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
						me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
					}
				}				
			}
		});
		**/
		// create AmMap object
		me.map = new AmCharts.AmMap();
        me.map.pathToImages = 'images/ammap/';
 
    	        //for ipad
        me.map.panEventsEnabled = true; // this line enables pinch-zooming and dragging on touch devices
        
        // Create the data provider
        var mapAreas = new Array();

        var kpilabelonmap='';
         
        //zoom to specified coordinates on first load, then user current zoom
        if((typeof(config.map_zoom.zoom_latitude)!== 'undefined')&&
        	(typeof(config.map_zoom.zoom_longitude)!== 'undefined')&&
        	(typeof(config.map_zoom.zoom_level)!== 'undefined')){
	        	
	        	var dataProvider = {
        	            mapVar: AmCharts.maps[me.mapId],
        	            areas: mapAreas,
        	            zoomLevel: config.map_zoom.zoom_level,
     					zoomLongitude: config.map_zoom.zoom_longitude,
     					zoomLatitude: config.map_zoom.zoom_latitude
        	        }; 
	        }
	        else{
	        	var dataProvider = {
        	            mapVar: AmCharts.maps[me.mapId],
        	            areas: mapAreas
        	        }; 
	        }
  
        // Pass data provider to the map object
        me.map.dataProvider = dataProvider;

        // Create areas settings
        me.map.areasSettings = {
    		autoZoom: false,
            selectedColor: "#1d97d8",
            outlineThickness: '0.1'
        };
        
        //set smallmap on top right
        me.map.smallMap = new AmCharts.SmallMap();
        
        //add legend to map
        //depending on kpi type
        var legend = new AmCharts.AmLegend();
        
        var titlelow='';
        var titleaverage='';
        var titlehigh='';
        
        
        if (config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend'].function == 'success' || config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend'].function == 'failure') {
        	titlelow='0-'+config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.low_threshold+' %';
        	titleaverage=config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.low_threshold+'-'+config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.high_threshold+' %';
        	titlehigh=config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.high_threshold+'-100 %';
        }
        else{
        	titlelow='< '+config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.low_threshold+' '+config['kpi_groups']['group'][groupindex].axis_list.axis_trend.unit;
        	titleaverage=config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.low_threshold+'-'+config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.high_threshold+' '+config['kpi_groups']['group'][0].axis_list.axis_trend.unit;
        	titlehigh='> '+config['kpi_groups']['group'][groupindex].axis_list.axis_trend.thresholds.high_threshold+' '+config['kpi_groups']['group'][groupindex].axis_list.axis_trend.unit;	  
        }
    		
        if(config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend'].function == 'failure'){
        	legend.data = [{title:titlelow, color:"#94AE0A"},{title:titleaverage, color:"#FF8C00"},{title:titlehigh, color:"#FF0000"}];

        }
        else{
        	legend.data = [{title:titlelow, color:"#FF0000"},{title:titleaverage, color:"#FF8C00"},{title:titlehigh, color:"#94AE0A"}];
        }
        legend.backgroundAlpha=0.5;
        legend.borderAlpha=1;
        legend.borderColor="#000000";
        legend.align="center";
        legend.position="absolute";
        legend.bottom=2;
        legend.fontFamily= 'Verdana';
	    legend.fontSize= 12;
        me.map.addLegend(legend);

        // Delete the previous charts
        var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
        var mapId = tabId + '_' + config['@attributes']['id'] + '_map';
        var chart = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
        //TODO voir conflit avec template gauge
        if (chart.down('panel') != null) {
        	chart.down('panel').destroy();
        	if (chart.down('panel') != null) {
	        	chart.down('panel').destroy();
	        }
        }
             
        //  Write the map to container div	       	        
        var h = chart.getHeight() - 45;	
        var kpilabelsize=11;
        
        var kpilabelx=0;
        
        //if fullscreen mode, set kpi label width accordingly
        if(me.displayMode=='true'){
        	 var w = chart.getWidth() - 10;
        	 kpilabelsize=16;
        	 kpilabelx=w*0.2;
        }
        else{
	        var w = chart.getWidth()*0.45 - 10;
	        kpilabelx=w;
        }

        //set kpi label on map
        //add kpi label on map
        me.map.addLabel(kpilabelx, 20, kpilabelonmap,'left',kpilabelsize,'black',0,1,true);

    
        //switch flex of left panel depending on displayMode
        var flexLeft=(me.displayMode=='true' ? 1 : 45); 

        if(me.displayMode=='true'){

        	//if fullscreen, calculate height and width of map div container
        	var h = chart.getHeight() - 40 - 45;	
	        var w = chart.getWidth() - 10;

        	var fullscreen = Ext.create('Ext.panel.Panel', {
        			id:tabId + '_' + config['@attributes']['id'] + '_fullscreen',
	        		flex:1,
	        		cls: 'x-panel-no-border',
	        		layout: {
        			    type: 'vbox',
        			    pack: 'start',
        			    align: 'stretch'
        			},
        			items:[
    			       {
    			    	   	xtype: 'panel',
    			    	   	//flex:1,
    			    	   	height: 40,
							cls: 'x-panel-no-border',
							layout: {
							    type: 'vbox',
							    pack: 'start',
							    align: 'stretch'
							},
							items: [
							    {
							    	id:tabId + '_' + config['@attributes']['id'] + '_selectors',
							    	xtype: 'fieldcontainer',
							    	layout:{
							    		type: 'hbox',
							    		pack: 'start',
							    	    align: 'stretch'
							    	},
							    	cls: 'x-panel-no-border',
							    	flex: 1,
							    	items:[
							    	       {
							    	    	   xtype: 'combobox',
							    	    	   id:tabId + '_' + config['@attributes']['id'] + '_kpi_selector',
							    	    	   fieldLabel: 'KPI/RAW',
							    	    	   displayField: 'kpi_label',
							    	    	   valueField: 'group_index',
							    	    	   autoSelect: true,
							    	    	   typeAhead: true,
									           margin: '0 10 0 0',
									           queryMode: 'local',
							    	    	   store: me.KpiSelectorStore,
							    	    	   labelWidth:60,
							    	    	   flex: 5,
							    	    	   listeners: {
							    	    		    afterrender: function(combo) {
							    	    		        var recordSelected = combo.getStore().getAt(groupindex);
							    	    		        combo.setValue(recordSelected.get('group_index'));
							    	    		    },
							    	    		    change: function(combo,newval,oldval) {
							    	    		        me.kpiGroupIndex=newval;	
	            			    	    		        me.loadTrend(me.config,me.kpiGroupIndex);	
							    	    		    }
							    	    		}
							    	       },
							    	       {
							    	    	   xtype: 'combobox',
							    	    	   id:tabId + '_' + config['@attributes']['id'] + '_trend_selector',
							    	    	   fieldLabel: 'Trend',
							    	    	   displayField: 'value',
							    	    	   valueField: 'id',
							    	    	   autoSelect: true,
									           queryMode: 'local',
							    	    	   store: me.trendTimeStore,
							    	    	   labelWidth:40,
							    	    	   //padding: '0 0 0 5',
							    	    	   matchFieldWidth: true,
							    	    	   minWidth:130,
							    	    	   //value: config['fullscreen_time_level'],
							    	    	   flex: 1,
							    	    	   listeners: {
							    	    		    afterrender: function(combo) {
							    	    		    	combo.setValue(config['fullscreen_time_level']);
							    	    		    	//console.log('VALUE = '+ config['fullscreen_time_level']);
							    	    		    	 me.trendTimeLevel=config['fullscreen_time_level'];
							    	    		    	 
							    	    		    },
							    	    		    change: function(combo,newval,oldval) {
							    	    		        var trigeredFromChange = true;
							    	    		        me.trendTimeLevel=newval;									    	    		        
							    	    		        me.loadTrend(me.config,me.kpiGroupIndex,trigeredFromChange);								
							    	    		    }
							    	    		}
							    	       },
							    	       
							    	]
								}
						 ]
    			       },
    			       {
				                    	id: tabId + '_' + config['@attributes']['id'] + '_map_title',
				                    	html: 'No data found.',
						    			flex:2,
						    			cls: 'x-panel-no-border',
						    			hidden: true,
						    			fill: "#fff",
						    			style: {
							                "padding-top": "30px" // when you add custom margin in IE 6...
							            }
					   },
    			       {
    			    	   	xtype: 'panel',
   	        	        	html: '<div id="' + mapId + '" style="height: ' + h + 'px; width: ' + w + 'px;"/>',
   	            			id:tabId + '_' + config['@attributes']['id']+'_map_container',
   	        	        	flex:9,
   	            			cls: 'x-panel-no-border',
   	            			listeners: {
            		            'resize': function (panel) {

            		                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
            		                var mapcont = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
            		                //take care of left panel width percentage (45 in our case)
            		                var newWidth=mapcont.getWidth() -10;
            		                var newHeight=mapcont.getHeight()-10 -40;
            		                var mapId = tabId + '_' + config['@attributes']['id'] + '_map';
            		                
            		                panel.update(
            		                		'<div id="' + mapId + '" style="height: ' + newHeight + 'px; width: ' + newWidth + 'px;"/>');
            		                me.map.write(mapId);
            		                
            		            }
            		        }
        	        	}
        			       
        			]
        	});	
        		

	        chart.add(fullscreen);
	        //write map to div container
	        me.map.write(mapId);
        }
    	        
    	        
        else{
        	//mode trend donut
	        var left = Ext.create('Ext.panel.Panel', {
	        	html: '<div id="' + mapId + '" style="height: ' + h + 'px; width: ' + w + 'px;"/>',
    			id:tabId + '_' + config['@attributes']['id']+'_map_container',
	        	flex:flexLeft,
    			cls: 'x-panel-no-border',
    			listeners: {
		            'resize': function (panel) {
		                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		                var mapcont = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
		                //take care of left panel width percentage (45 in our case)
		                var newWidth=mapcont.getWidth()*0.45 -10;
		                var newHeight=mapcont.getHeight()-10;
		                var mapId = tabId + '_' + config['@attributes']['id'] + '_map';
		                
		                panel.update(
		                		'<div id="' + mapId + '" style="height: ' + newHeight + 'px; width: ' + newWidth + 'px;"/>');
        		                me.map.write(mapId);
        		                
        		            }
        		        }
    	        }); 
        	chart.add(left);
    	    me.map.write(mapId);
 
	        // Create the period chart
	        
			// Set the colors according to the Homepage style
			var style = Ext.get('homepageStyle').dom.value;	
			var axisColor = '#000000';
			var axisLabelcolor = '#686868';
			var legendColor = '#000000';		
			if (style == 'access') {
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
	        
			// Create the data store for the trend chart
			var trendFields = [
    	         'warning', 
    	         'alert', 
    	         'kpi',
    	         'unit'
    	    ];
				
			var trendYFields = [
    	         'warning', 
    	         'alert', 
    	    ];
			var trendSeries = [
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
					showMarkers: false,
					showInLegend: false
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
					showMarkers: false,
					showInLegend: false
                }
			];
    			
    		//colors array to get both legend colors match in trend and pie
    		me.colors=new Array();	
    		//colors are defined arbitrary in CDC, colors are picked from zero to n, if we get to array end, start from beginning
    		//me.strokes=['#e41a1c','#377eb8','#4daf4a','#984ea3','#ff7f00','#ffff33','#a65628','#f781bf','#999999','#fbb4ae','#b3cde3','#ccebc5','#decbe4','#fed9a6','#ffffcc','#e5d8bd','#fddaec','#f2f2f2','#708b32','#8d8558'];
    		me.strokes=['#01BAD9','#2D9500','#C10001','#949494','#EC449B','#295E92','#5EBE90','#FF6201','#2E2E2E','#F28FBE','#03A1FC','#89C122','#FFCC01','#F9F9F9','#5B5A94','#ABAAE4','#00722D','#FF3237','#505050','#FE198E','#003368','#BEDB89','#CF5700','#000000','#87C1E7','#019E59','#C79C19','#D9DDE9','#00349A','#00BB6A','#D10039','#5A471D','#0E63FC','#03646A','#924358','#373121','#468898','#2ACC55'];
			for (var f = 0; f < me.nes.labels.length; f++) {
				var stroke=me.strokes[f%37]; 
				me.colors.push(stroke);
				//push ne id in fields
				//trendFields.push(me.networks[f]);
				trendFields.push({name :me.nes.labels[f],convert: function(value, record) { return (value=="" ? undefined : value);}});
				//trendFields.push({name :me.networks[f],convert: function(value, record) { return (isNaN(value) ? undefined : value);}});
				
				//trendYFields.push(me.networks[f]);
				trendYFields.push({name :me.nes.labels[f],convert: function(value, record) { return (value=="" ? undefined : value);}});
				//trendYFields.push({name :me.networks[f],convert: function(value, record) { return (isNaN(value) ? undefined : value);}});
				trendSeries.push({
					type: 'line',
      				axis: 'left',
      				xField: 'time',
      				title: me.nes.labels[f],
      				yField: me.nes.labels[f],
					style: {
						stroke: stroke,
						//stroke: '#18428E',
						'stroke-width': 2 
					},
					markerConfig: {
						type: 'cross',
						fill: stroke,
						radius: 2
					},
					//highlight:true,
					selectionTolerance: 5,
					tips: {
						trackMouse: true,
			        	width: 240,
			        	height: 50,
			        	dismissDelay: 0,
			        	minWidth:100,
			        	constrainPosition: true,
			        	renderer: function(storeItem, item) {
			        		var title=storeItem.get('time') + '<br/>';
			        		title+=storeItem.get('kpi') + ' ('+storeItem.get('unit')+')<br/>';
			        
			        		//custom pop with all NEs and their values
			        		for (var n = 0; n < me.nes.labels.length; n++) {
			        			if(item.series.chart.series.items[n+2].showMarkers)
			        			//title+='<font color="'+me.strokes[n%20]+'">'+me.nes.labels[n]+'</font> : '+(storeItem.get(me.nes.labels[n]) !='-1000000000'?((storeItem.get(me.nes.labels[n]).indexOf('.')!=-1 && storeItem.get(me.nes.labels[n]).indexOf('+')==-1) ? parseFloat(storeItem.get(me.nes.labels[n])).toFixed(2):storeItem.get(me.nes.labels[n])):'undefined')+'<br/>';
			        			title+='<font color="'+me.strokes[n%38]+'">'+me.nes.labels[n]+'</font> : '+(storeItem.get(me.nes.labels[n]) !='-1000000000'?(Math.floor(storeItem.get(me.nes.labels[n])) == storeItem.get(me.nes.labels[n]) && Ext.isNumeric(storeItem.get(me.nes.labels[n])) ? storeItem.get(me.nes.labels[n]) : parseFloat(storeItem.get(me.nes.labels[n])).toFixed(2) ) : 'undefined')+'<br/>';
			        		}
			        		this.setTitle(title);
			        		
			        		var measured=me.measureText(title, this.labelFont);
         	                this.setWidth(measured.width*1.1);
         	                this.setHeight(measured.height*1.1);
			        	}
			        }
				});
			}
    		
			var storeTrend = Ext.create('Ext.data.Store', {
		    	fields: trendFields,
		    	data: []
			});
			
			
			
			//TODO declare donut store in init function
			// Create the data store for the donut
			var storeDonut = Ext.create('Ext.data.Store', {
		    	fields: ['data1', 
		    	         'name', 
		    	         'kpi',
		    	         'date',
		    	         'unit'],
		    	data: []
			});
			
	    }	
        			
		if(me.displayMode!='true'){
	        var right = Ext.create('Ext.panel.Panel', {
    			flex:55,
    			cls: 'x-panel-no-border',
    			layout: {
    			    type: 'vbox',
    			    pack: 'start',
    			    align: 'stretch'
    			},
    			items: [
    			    {
    			    	id:tabId + '_' + config['@attributes']['id'] + '_selectors',
    			    	xtype: 'fieldcontainer',
    	    			maxHeight: 22,
    			    	layout:{
    			    		type: 'hbox',
    			    		pack: 'start',
    			    	    align: 'stretch'
    			    	},
    			    	//padding: '5 5 5 5',
    			    	//cls: 'x-panel-no-border',
    			    	flex: 1,
    			    	items:[
    			    	       {
    			    	    	   xtype: 'combobox',
    			    	    	   id:tabId + '_' + config['@attributes']['id'] + '_kpi_selector',
    			    	    	   fieldLabel: 'KPI/RAW',
    			    	    	   displayField: 'kpi_label',
    			    	    	   valueField: 'group_index',
    			    	    	   margin: '0 10 0 0',
    			    	    	   autoSelect: true,
    			    	    	   typeAhead: true,
    					           queryMode: 'local',
    			    	    	   store: me.KpiSelectorStore,
    			    	    	   labelWidth:70,
    			    	    	   flex: 2,
    			    	    	   listeners: {
    			    	    		    afterrender: function(combo) {
    			    	    		    	//set combo to specified kpi group index
    			    	    		        var recordSelected = combo.getStore().getAt(groupindex);
    			    	    		        combo.setValue(recordSelected.get('group_index'));
    			    	    		    },
    			    	    		    change: function(combo,newval,oldval) {
    			    	    		    	//on change, call loadTrend and loadDonut
    			    	    		        me.kpiGroupIndex=newval;
    			    	    		        var trigeredFromChange = true;
											me.kpiGroupIndex=newval;	
    			    	    		        me.loadTrend(me.config,me.kpiGroupIndex,trigeredFromChange);	
    			    	    		    }
    			    	    		}
    			    	       },
    			    	      	
    			    	       {
    			    	    	   xtype: 'combobox',
    			    	    	   id:tabId + '_' + config['@attributes']['id'] + '_trend_selector',
    			    	    	   fieldLabel: 'Trend',
    			    	    	   margin: '0 10 0 0',
    			    	    	   displayField: 'value',
    			    	    	   valueField: 'id',
    			    	    	   autoSelect: true,
    					           queryMode: 'local',
    			    	    	   store: me.trendTimeStore,
    			    	    	   labelWidth:50,
    			    	    	   matchFieldWidth: true,
				    	    	   minWidth:130,
				    	    	   maxWidth:130,
    			    	    	   //flex: 1,
    			    	    	   listeners: {
    			    	    		    afterrender: function(combo) {
    			    	    		    	combo.setValue(me.trendTimeLevel);
    			    	    		    },
    			    	    		    change: function(combo,newval,oldval) {
    			    	    		        //change donut time selector accordingly
    			    	    		        var donutvalue='';
    			    	    		        if(me.firstLoad==true){
    			    	    		        	donutvalue=me.donutTimeLevel;
    			    	    		        }
    			    	    		        else{;
    			    	    		        	if(newval=='hour'){
        			    	    		        	donutvalue='day';
        			    	    		        }else if(newval=='day' || newval=='day_bh'){
        			    	    		        	donutvalue='week';
        			    	    		        }else{
        			    	    		        	donutvalue='month';
        			    	    		        }
    			    	    		        }
    			    	    		        
    			    	    		        me.firstLoad=false;
    			    	    		        
    			    	    		        Ext.getCmp(tabId + '_' + config['@attributes']['id'] + '_donut_selector').setValue(donutvalue); 
    			    	    		        var trigeredFromChange = true;
    			    	    		        me.trendTimeLevel=newval;
    			    	    		        me.loadTrend(me.config,me.kpiGroupIndex,trigeredFromChange);

    			    	    		    }
    			    	    		}
    			    	       },
    			    	       {
    			    	    	   xtype: 'combobox',
    			    	    	   id:tabId + '_' + config['@attributes']['id'] + '_donut_selector',
    			    	    	   fieldLabel: 'Donut',
    			    	    	   displayField: 'value',
    			    	    	   valueField: 'id',
    			    	    	   autoSelect: true,
    					           queryMode: 'local',
    			    	    	   store: me.donutTimeStore,
    			    	    	   labelWidth:50,
    			    	    	   matchFieldWidth: true,
				    	    	   minWidth:130,
				    	    	   maxWidth:130,
    			    	    	   //flex: 1,
    			    	    	   listeners: {
    			    	    		    afterrender: function(combo) {           			    	    		    	
    			    	    		    	combo.setValue(me.donutTimeLevel);
    			    	    		    },
    			    	    		    change: function(combo,newval,oldval) { 
    			    	    		        me.donutTimeLevel=newval;
    			    	    		        me.loadDonut(me.config,me.kpiGroupIndex);
    			    	    		    }
    			    	    		}
    			    	       }    
    			    	]
	        		}, 
					{
						id: tabId + '_' + config['@attributes']['id'] + '_donut',
						xtype: 'chart',
						flex:3,
						store: storeDonut,
						animate: true,
//						legend: {
//							position: 'right'
//						},
						legend: false,
						insetPadding: 40,
			            theme: 'Base:gradients',
			            items: [
		                    {
		                    	id: tabId + '_' + config['@attributes']['id'] + '_donut_title',
		                    	type  : 'text',
		                    	//'text-anchor':'middle',
		                    	text  : 'Collecting Data...',
		                    	font: 'bold 16px Arial',
		                    	x : 10, //the sprite x position
		                    	y : 10  //the sprite y position
		                    }
	                    ],
						series: [
				         	{
						      	type: 'pie',
						        field: 'data1',
						        showInLegend: true,
						        donut: 30,
						        colorSet: me.colors,
						        shadowAttributes: [{
					               "stroke-opacity" :(Ext.get('homepageStyle').dom.value == 'access' ? 0 : 100)
					            }, {

					               "stroke-opacity" :(Ext.get('homepageStyle').dom.value == 'access' ? 0 : 100)

					            }, {
					                "stroke-opacity" :(Ext.get('homepageStyle').dom.value == 'access' ? 0 : 100)
					            }],
						        tips: {
						        	trackMouse: true,
						        	width: 240,
						        	height: 50,
						        	dismissDelay: 0,
						        	constrainPosition: true,
						        	renderer: function(storeItem, item) {
						        		var html=storeItem.get('name') + '<br/>' + 
				        					storeItem.get('date') + '<br/>' + 
				        					storeItem.get('kpi') + ' : ' + (storeItem.get('data1').indexOf('.')!=-1 && storeItem.get('data1').indexOf('+')==-1 ? parseFloat(storeItem.get('data1')).toFixed(2):storeItem.get('data1')) + ' ' + storeItem.get('unit');
						        		
						        		this.setTitle(html);
						        		
						        		var measured=me.measureText(html, this.labelFont);
		             	                this.setWidth(measured.width*1.1);
		             	                this.setHeight(measured.height*1.1);
						        	}
						        },
						        highlight: {
						        	segment: {
						        		margin: 20
						        	}
						        },
						        label: {
						        	field: 'name',
						        	display: 'none',
						        	contrast: true,
						        	//font: '18px Arial',
						        	renderer: function(v){
						        		return v;
						        	}
						        },
						        
						        //TODO add listener on 
//						         			listeners:{
//						         				itemmouseover : function(obj) {
//						         		        //alert(obj.storeItem.data['name'] + ' &' + obj.storeItem.data['data1']);
//						         				
//						         				var seriename = obj.storeItem.get('name');
//						         				
//						         				console.log('seriename ',seriename);
//	
//						         				//select matching serie from trend and highlight it
//						         				var trend=Ext.getCmp(tabId + '_' + config['@attributes']['id'] + '_trend');
//						         				console.log('trend ',trend);
//						         				
//						         				console.log('trend series ',trend.series);
//						         				
//
//						         				 var series = trend.series;
//						         				 l = series.length;
//						         				 
//						         				 
//						         				 //unhighlight everything
//						         				 for(var i=0;i<l;i++){
//						         					series.items[i].highlight = true;
//						         					series.items[i].unHighlightItem();
//						         					series.items[i].cleanHighlights();
//						         				 }
//					
//						         				 for(var i=0;i<l;i++){
//						         					if (seriename == series.items[i].yField) {
//						         						console.log('BINGO ',series.items[i].yField);
//						         						series.items[i].highlightItem();
//						         						//break;
//						         					}
//						         					else{
//						         						series.items[i].unHighlightItem();
//						         					}
//						         				 }
//						         				 
//						         				 //set highlight to false for everything
//						         				 for(var i=0;i<l;i++){
//						         					series.items[i].highlight = false;
//						         				 }
//						         				 
//						         		        }
//						         			}
				         	}
		         		],
					},
    	  			{
    	  				id: tabId + '_' + config['@attributes']['id'] + '_trend',
    	  				flex:7,
    	  				xtype: 'chart',
    	  				theme: 'Fancy',
    	  				animate: {
    	          			easing: 'bounceOut',
    	          			duration: 750
    	      			},
    	      			insetPadding: 40,
    	  				store: storeTrend,
//    	  				legend: {
//    	          			position: 'bottom',
//    	          			padding: 5
//    	      			},
    	  				legend: false,
    	  				shadow: false,
    	  				extraStyle: {
    	  					yAxis: {
    	  						titleRotation: 90
    	  					}
    	  				},
    	  				items: [
			                    {
			                    	id: tabId + '_' + config['@attributes']['id'] + '_trend_title',
			                    	type  : 'text',
			                    	text  : 'Collecting Data...',
			                    	font: 'bold 16px Arial',
			                    	x : 10, //the sprite x position
			                    	y : 10  //the sprite y position
			                    }
		                    ],
    	  				axes: [
    	  					{
    	          				type: 'Numeric',
    	  	    				position: 'left',
    	  						fields: trendYFields,
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
    	  						grid: true
    	  					},
    	  					{
    	  						type: 'Category',
    	          				position: 'bottom',
    	          				fields: ['time'],
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
    	  				series: trendSeries
    	  			}		        	  			
    			]
	        }); 

	        // Add the chart	
        	chart.add(right);
		}        
	},
	
	loadTrend: function(config,index,trigeredFromChange) {
		var me = this;
		var configPanel = Ext.getCmp('configPanel');
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		var configChart = Ext.getCmp('configChart');
		if (typeof trigeredFromChange === 'undefined') { trigeredFromChange = false; }
		//In some case, map components are not well hide by configMapModeSelection, so we hide them when the trend is loaded
		if(typeof configPanel  !== 'undefined'){
			if(configPanel.isVisible() == true){
				if(Ext.getCmp('mapMode').getValue().modeselection == 3){
           			Ext.getCmp('displayedValueMode_configMap').setVisible(true);
        			Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').setVisible(true);
        			Ext.getCmp('configMapAssociation').setVisible(true);
        			Ext.getCmp('defaultTrendTimeLevelCombo_configMap').setVisible(false);
            		Ext.getCmp('defaultDonutTimeLevelCombo_configMap').setVisible(false);
        			Ext.getCmp('mapKpiGrid_configChart_roaming').getView().refresh();
        			
           		}
           		//if fullscreen
           		else if(Ext.getCmp('mapMode').getValue().modeselection == 2){
        			Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').setVisible(true);
        			Ext.getCmp('displayedValueMode_configMap').setVisible(false);
        			Ext.getCmp('configMapAssociation').setVisible(false);
        			Ext.getCmp('counterContainer_configChart').setVisible(false);
        			Ext.getCmp('unitField_configChart').setVisible(false);
        			Ext.getCmp('mapKpiGrid_configChart').columns[3].hide();
        			Ext.getCmp('mapKpiGrid_configChart').getView().refresh();
        			configChart
						.down('checkboxfield[id="dynamicBox_configChart"]')
						.setVisible(false);
					configChart
						.down('numberfield[id="scaleMinField_configChart"]')
						.setVisible(false);
				
					configChart
						.down('numberfield[id="scaleMaxField_configChart"]')
						.setVisible(false);
            	}else if (Ext.getCmp('mapMode').getValue().modeselection == 1){
            		Ext.getCmp('displayedValueMode_configMap').setVisible(false);
            		Ext.getCmp('defaultTrendTimeLevelCombo_configMap').setVisible(true);
            		Ext.getCmp('defaultDonutTimeLevelCombo_configMap').setVisible(true);
            		Ext.getCmp('configMapAssociation').setVisible(false);
            		Ext.getCmp('counterContainer_configChart').setVisible(true);
        			Ext.getCmp('unitField_configChart').setVisible(true);
        			Ext.getCmp('mapKpiGrid_configChart').columns[3].show();
        			Ext.getCmp('mapKpiGrid_configChart').getView().refresh();
        			configChart
						.down('checkboxfield[id="dynamicBox_configChart"]')
						.setVisible(true);
					configChart
						.down('numberfield[id="scaleMinField_configChart"]')
						.setVisible(true);
			
					configChart
						.down('numberfield[id="scaleMaxField_configChart"]')
						.setVisible(true);
            	}
			}
		}
		// Get the chart
		if(config['fullscreen']== "false"){
			var trend = Ext.getCmp(tabId + '_' + config['@attributes']['id'] + '_trend');
			if(trigeredFromChange == false){
				var timeUnit = config['trend_time_level'];
			}else{
				var timeUnit = me.trendTimeLevel;
			}
				
		}else{
			var trend = Ext.getCmp(tabId + '_' + config['@attributes']['id'] + '_fullscreen');	
			if(trigeredFromChange == false){
				var timeUnit = config['fullscreen_time_level'];
			}else{
				var timeUnit = me.trendTimeLevel;
			}
		}
		
		me.dataTrend = new Array();
		//me.networksTrend = new Array();

    	// Time parameters
    	//var timeUnit = me.trendTimeLevel;

        var timeData = {};
        timeData.id = timeUnit;
        timeData.type = "ta";
        timeData.order = "Descending"; // get the last value available
        
         //NE parameters
       	var is3emeAxe = false;
        if(config['roaming'] == 'true' ){
        	if (config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'] !==  'undefined' && config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'] != ''){
         	is3emeAxe = true;
        	 }
        	//we empty me.nes to fill it with the informations from the new slected kpi
        	//me.nes={};
	        me.nes.ids=new Array();
	        me.nes.ids2=new Array();
	        me.nes.idsindex=new Array();
	        me.nes.zones=new Array();
	        me.nes.labels=new Array();
	        me.nes.zoneslabels=new Array();
	        me.nes.idvalueparent=new Array();
	        
	       
	        var neLevelAxe1 = typeof(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_network_level']) !== 'object' ?  config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_network_level'] : "";
	        var neLevelAxe2 = typeof(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_network_level2']) !== 'object' ?  config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_network_level2'] : "";
	        productId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'];
	        
		    if(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['network_axis_number'] == '2'){
				var axis = 2;
			}else{
				var axis = 1;
			}
	        var neSelectData = {};
	        var neSelectData2 = {};
	        var neSelectData3 = {};
	        
	        //on en profite pour remplir les hidden field avec les ne level
	        Ext.getCmp('neLevelId_configMap').setValue(neLevelAxe1);
		    Ext.getCmp('neLevelId2_configMap').setValue(neLevelAxe2);
		    Ext.getCmp('parentLevelSelected_configMap').setValue(axis);
			
	        
	      	Ext.Ajax.request({
				url : 'proxy/configuration.php',
				async : false,
				params : {
					task : 'GET_AMMAPID',
					sdp_id : config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'],
					selected_level : axis == 2 ?  neLevelAxe2 : neLevelAxe1
				},
				
				success : function(response) {
					//me.ammapIds = response.responseText;
					me.ammapIds=Ext.decode(response.responseText);
					for (var i = 0; i < me.ammapIds['data'].length; i++) {
						//parentIdArray.push(me.ammapIds['data'][i]['mapId']);
						if(axis == 1){
							if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
								me.nes.ids2.push(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2']);
							}	
						}else{
							me.nes.ids.push(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id']);
						}
					}

						var requestDataChild = {};
					        requestDataChild.method = 'getChild';
					        requestDataChild.parameters = {};
					        requestDataChild.parameters.father = {};

							if(axis == 2){
					        	requestDataChild.parameters.father.id = neLevelAxe2;
					        }else{
					        	requestDataChild.parameters.father.id = neLevelAxe1;
					        }
							requestDataChild.parameters.father.productId = productId;
					
					        var requestParamChild = {};
					        requestParamChild.data = Ext.encode(requestDataChild);	
							
		        			if(axis == 1){
								var neData = {}; 
								neData.type = 'na';
								neData.operator = 'in';
								//neData.id= neLevelAxe1;
							
								//In case we are in a roaming product with no 3rd axe
								if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
									var neData2 = {}; 
									neData2.type = 'na_axe3';
									neData2.operator = 'equals to';
									neData2.id = neLevelAxe2;
									neData2.value =me.nes.ids2[0];
								}
							
							}else{
								var neData = {}; 
								neData.type = 'na';
								neData.operator = 'in';
								neData.id= neLevelAxe1;
								neData.value= me.nes.ids.join(',');
								//value neData récupéré après
								
								var neData2 = {}; 
								neData2.type = 'na_axe3';
								neData2.operator = 'in';
			
							}

							var requestDataLabelChildren = {};
		
							Ext.Ajax.request({
					        	url: 'proxy/dao/api/querydata/index.php',
					        	params: requestParamChild,
					        	async:false,
					            success: function (response) {
									error = false;
									try {
					        			var childLevel = response.responseText;
					        			if (typeof childLevel['error'] != "undefined") {
					        				// The request send an error response
					        				error = true;
					        			}
					        		} catch (err) {
					        			// The json is invalid
					        			error = true;
					        		}if(error == false){
					        			
					        			var neSelectData = {};
										neSelectData.id=neLevelAxe1;
										neSelectData.type="na";
										neSelectData.order = "Ascending";
										
										if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
											var neSelectData2 = {};
											neSelectData2.id=neLevelAxe2;
											neSelectData2.type="na_axe3";
											neSelectData2.order = "Ascending";
										}
										
					        			/**
										if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
						        			var neSelectData3 = {};
											neSelectData3.id=childLevel;
											neSelectData3.type="na_axe3";
											neSelectData3.order = "";
								            if(axis == 1){
								           		neData.id = childLevel;
								           	}else{
												neData2.id = childLevel;
								            }
					        			}else{
					        				var neSelectData3 = {};
											neSelectData3.id=childLevel;
											neSelectData3.type="na";
											neSelectData3.order = "";
											neData.id = childLevel;
					        			}
					        			**/

										if(axis == 1){
											if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
												var neSelectData3 = {};
												neSelectData3.id= childLevel;
												neSelectData3.type="na";
												neSelectData3.order = "";
												
												neData.id = childLevel;
											}else{
												var neSelectData3 = {};
												neSelectData3.id=childLevel;
												neSelectData3.type="na";
												neSelectData3.order = "";
												neData.id = childLevel;
											}
											
										}else{
											var neSelectData3 = {};
											neSelectData3.id= childLevel;
											neSelectData3.type="na_axe3";
											neSelectData3.order = "";
											
											neData2.id = childLevel;
										}
										
							            // Search field
											var searchOptions = {
												text: null,			// Text field value
												products: []
											};
							            	if(axis == 1){
												//on cherche les enfant de operator
												var productItem = {
													id: productId,
													na: childLevel,
													axe: ''
												}
											}else{
												
												var productItem = {
													id: productId,
													na: childLevel,
													axe: 3
												}
											}
											searchOptions.products.push(productItem);
									        searchOptions = Ext.encode(searchOptions);
								
									        var idArray = new Array();
									        
									        me.currentDate =new Date();
   											var currentYear =  me.currentDate.getFullYear();
   											var currentMonth = ("0" + ( me.currentDate.getMonth() + 1)).slice(-2);
   											var currentDay = ("0" +  me.currentDate.getDate()).slice(-2)
   											
									        if(timeUnit == 'day' || timeUnit == 'day_bh'){
									        	me.currentFormatDate = ""+currentYear+currentMonth+currentDay;
									        	me.currentFormatDate.trim();
									        }else if (timeUnit == 'month' || timeUnit == 'month_bh'){
									        	me.currentFormatDate = ""+currentYear+currentMonth;
									        	me.currentFormatDate.trim();
									        }else if (timeUnit == 'week' || timeUnit == 'week_bh'){
									        	var week = me.getWeekNumber(currentYear+'-'+currentMonth+'-'+currentDay);
					        					week = week[1];
					        					me.currentFormatDate = ""+currentYear+week;
					        					me.currentFormatDate.trim();
									        }else if(timeUnit == 'hour'){
									        	me.currentFormatDate = ""+currentYear+currentMonth+currentDay;
									        	me.currentFormatDate.trim();
									        }
   											
   											Ext.Ajax.request({
												url : 'proxy/configuration.php',
												async : false,
												params : {
													task : 'EXIST_LAST_DATE',
													sdp_id : config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'],
													time_level : timeUnit,
													date: me.currentFormatDate
												},
								
												success : function(response) {
													me.countResult = response.responseText;
													if(me.countResult <= 0){ 											 
   											 			//var today = new Date();
														var yesterday = new Date( me.currentDate);

   											 			if(timeUnit == 'day' || timeUnit == 'day_bh'){
	   											 			//var previousDay = currentDay - 1;
	   														//me.previousFormatDate = ""+currentYear+currentMonth+previousDay;
   															//me.previousFormatDate.trim();
   															yesterday.setDate(me.currentDate.getDate() - 1);
   															var dd = yesterday.getDate();
															var mm = yesterday.getMonth()+1; //January is 0!
															var yyyy = yesterday.getFullYear();
															if(dd<10){dd='0'+dd} if(mm<10){mm='0'+mm} yesterday = ' '+yyyy+mm+dd; //eg 12/01/2014
															me.previousFormatDate = yesterday.trim();
   															
   											 			}else if (timeUnit == 'month' || timeUnit == 'month_bh'){
	   											 			//var previousMonth = currentMonth - 1;
	   											 			//me.previousFormatDate = ""+currentYear+previousMonth;
	   											 			yesterday.setMonth(yesterday.getMonth()-1 );
	   											 			var dd = yesterday.getDate();
															var mm = yesterday.getMonth()+1; //January is 0!
															var yyyy = yesterday.getFullYear();
															if(dd<10){dd='0'+dd} if(mm<10){mm='0'+mm} yesterday = ' '+yyyy+mm; //eg 12/01/2014
	   											 			me.previousFormatDate = yesterday.trim();
	   											 		}else if (timeUnit == 'week' || timeUnit == 'week_bh'){
												        	yesterday.setDate(me.currentDate.getDate() - 7);
   															var dd = yesterday.getDate();
															var mm = yesterday.getMonth()+1; //January is 0!
															var yyyy = yesterday.getFullYear();
															if(dd<10){dd='0'+dd} if(mm<10){mm='0'+mm};
															
												        	var week = me.getWeekNumber(yyyy+'-'+mm+'-'+dd);
								        					previousWeek = week[1];
								        					me.previousFormatDate = ''+yyyy+previousWeek;
								        					me.previousFormatDate.trim();
	   											 		}else if(timeUnit == 'hour'){
												        	yesterday.setDate(me.currentDate.getDate() - 1);
   															var dd = yesterday.getDate();
															var mm = yesterday.getMonth()+1; //January is 0!
															var yyyy = yesterday.getFullYear();
															if(dd<10){dd='0'+dd} if(mm<10){mm='0'+mm} yesterday = ' '+yyyy+mm+dd; //eg 12/01/2014
															me.previousFormatDate = yesterday.trim();
												        }
												        
												        Ext.Ajax.request({
														url : 'proxy/configuration.php',
														async : false,
														params : {
															task : 'EXIST_LAST_DATE',
															sdp_id : config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'],
															time_level : timeUnit,
															date: me.previousFormatDate
														},
										
															success : function(response) {
																me.countResultP = response.responseText;
																if(me.countResultP <= 0){
																	//on récupère la dernière date d'integration en fonction du niveau temps séléctioné
																	Ext.Ajax.request({
																		url : 'proxy/configuration.php',
																		async : false,
																		params : {
																			task : 'LAST_DATE',
																			sdp_id : config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'],
																			time_level : timeUnit
																		},
														
																		success : function(response) {
																			me.lastintegrationdate = response.responseText;
																		}
																	});
																}else{
																	if(timeUnit == 'hour'){
																		me.lastintegrationdate = me.countResultP;
																	}else{
																		me.lastintegrationdate = me.previousFormatDate;
																	}
																}
															}
														}); 
													}else{
														if(timeUnit == 'hour'){
															me.lastintegrationdate = me.countResult;
														}else{
															me.lastintegrationdate = me.currentFormatDate;
														}
													}
												}
											});
   											
											Ext.Ajax.request({
									        	url: 'proxy/ne_listhtml.php',
									        	async : false,
									        	params: {
								    				roaming: true,
								    				filterOptions: searchOptions
								          		},
									
									            success: function (response) {
									        			var result = Ext.decode(response.responseText).data;
													    Ext.each(result, function(value) {
														      Ext.each(value.parent_id , function(k,v){
															       	if(axis == 1){
														      			me.nes.ids.push(k);
														      			//me.nes.zones[k]=config['network_elements']['network_element'][i]['map_zone_id'];
														      			
															       	}else{
															       		me.nes.ids2.push(k);
																		//me.nes.zones[k]=config['network_elements']['network_element'][i]['map_zone_id'];
															       	}
															    });
													    });
													    
													    if(axis == 1){
													    	neData.value= me.nes.ids.join(',');
															requestDataLabelChildren={
																nelist:me.nes.ids.join(','),
																na:childLevel,
														    	product:config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'],
														    	order: 'asc'
														    };
													    }else{
													    	neData2.value= me.nes.ids2.join(',');
													    	requestDataLabelChildren={
																nelist:me.nes.ids2.join(','),
																na:childLevel,
														    	product:config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'],
														    	order: 'asc'
														    };
													    }
													    //var fullmapTimeLevel = config['fullscreen_time_level'];
													    var timeSelectorId = tabId + '_' + config['@attributes']['id'] + '_trend_selector';
														var fullmapTimeLevel = Ext.getCmp(timeSelectorId).value;
														var timeData = {};
												        timeData.id = fullmapTimeLevel;
												        timeData.type = "ta";
												        timeData.order = "Descending"; // get the last value available
												        
												        var rawKpiId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['kpi_id'];
									        			var rawKpiProductId =config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'];
									       				var rawKpiType = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['type']
												        var rawKpiData = {};
												        rawKpiData.id = rawKpiId;        
												        rawKpiData.productId = rawKpiProductId;
												        rawKpiData.type = rawKpiType; 
											        	
												        // Only get the last value in the database
														
														var timeNumber = config['units_number']*config['network_elements']['network_element'].length;
														if (timeNumber == null || 
															((typeof(timeNumber) != 'string') && (typeof(timeNumber) != 'number')) ||
															timeNumber == '') {
															timeNumber = 20;
														}
												        
												        
												        limitFilter = {};
														limitFilter.id = 'maxfilter'; 
														limitFilter.type = 'sys';
														//limitFilter.date = '20130409';
														limitFilter.value = timeNumber;
														
												        
												     	if(axis == 1){
												     		if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
												     			//Case of product with 3rd axe from wich we have to get children on axe 2
																var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2,neSelectData3);
																var filtersData = new Array(neData,neData2, limitFilter); 
												     		}else{
																limitFilter.date = me.lastintegrationdate;
																limitFilter.timelevel = timeUnit;
												     			var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData3);
											        			var filtersData = new Array(neData,limitFilter); 
												     		}
												     	}else{
															limitFilter.date = me.lastintegrationdate;
															limitFilter.timelevel = timeUnit;
												     		var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2,neSelectData3);
											        		var filtersData = new Array(neData,neData2, limitFilter); 
												     	}
											        	
												        var requestData = {};
												        requestData.method = 'getDataAndLabels';
												        requestData.parameters = {};
												        
														if(axis == 1){
															requestData.parameters.roaming = true;
															//requestData.parameters.mapid = true;
														}else{
															requestData.parameters.roaming = true;
														}
														
														if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ""){
															if(axis == 1){
																requestData.parameters.mapid = true;
															}else{
																requestData.parameters.roaming = true;
															}
														}else{
															requestData.parameters.mapid = true;
														}
														 
												        requestData.parameters.select = {};
												        requestData.parameters.select.data = selectData;
												        requestData.parameters.filters = {};
												        requestData.parameters.filters.data = filtersData;
												       
												        var requestParam = {};
												        requestParam.data = Ext.encode(requestData);
												        Ext.Ajax.request({
												        	url: 'proxy/dao/api/querydata/index.php',
												        	params: requestParam,
															async: false,
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
												        			console.log('erreur')
												        			
												        		}
												        		if(error == false){
																	Ext.each(result.values, function(value) {
																	      Ext.each(value.data , function(k,v){
																				if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
																		       		if(axis == 2){
																						me.nes.zones[k[4]]=k[3];
																					}else{
																						me.nes.zones[k[4]]=k[2];
																					}
																		       	}else{
																		       		me.nes.zones[k[3]]=k[2];
																		       	}
																		    });
																    });
				
							
												        		}
												            }
														});	
												        
											     }
											});
					        			}
					            }, failure:  function(response, options) {
				                    console.log('Error', "Communication failed");
				                }
					        });
					        
					        var requestParamChildren = {};
							requestParamChildren = requestDataLabelChildren;
							//get selected map from map.xml file
							Ext.Ajax.request({
								url: 'proxy/ne_labels.php',
								async:false,
								params: requestParamChildren,
					
								success: function(response) {
									// Decode the json into an array object
									if (response.responseText != '') {
										var labelsarray=Ext.decode(response.responseText);
										for(i=0;i<labelsarray.length;i++){
											me.nes.labels.push(labelsarray[i].label);
											me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
											me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
										}
									}				
								}
							});
					}
        	});
	        
	        if(axis == 1){
	        	if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
		       		neSelectData.id=neLevelAxe1;
		        	neSelectData.type="na";
		        	neSelectData.order = "Ascending";
		        	
		       		neSelectData2.id=neLevelAxe2;
		       		neSelectData2.type="na_axe3";
		       		neSelectData2.order = "Ascending";	
					
	        	}else{
	        		neSelectData.id=neLevelAxe1;
		        	neSelectData.type="na";
		        	neSelectData.order = "Ascending";
	        	}
	       	}else{
	       		neSelectData.id=neLevelAxe1;
	        	neSelectData.type="na";
	        	neSelectData.order = "Ascending";
	        
	       		neSelectData2.id=neLevelAxe2;
	       		neSelectData2.type="na_axe3";
	       		neSelectData2.order = "Ascending";
	       	}
        	
	        var requestDataChild = {};
	        requestDataChild.method = 'getChild';
	        requestDataChild.parameters = {};
	        requestDataChild.parameters.father = {};
	        if(axis == 2){
	       		requestDataChild.parameters.father.id = neSelectData2.id;
	        }else{
	        	requestDataChild.parameters.father.id = neSelectData.id;
	        }
			requestDataChild.parameters.father.productId = productId;
	
	        var requestParamChild = {};
	        requestParamChild.data = Ext.encode(requestDataChild);
			

			Ext.Ajax.request({
	        	url: 'proxy/dao/api/querydata/index.php',
	        	params: requestParamChild,
	        	async : false,
	            success: function (response) {
			       	me.levelChildId = response.responseText;
			       	if(axis == 1){
						if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
							neSelectData3.id= me.levelChildId;
							neSelectData3.type="na";
							neSelectData3.order = "";
							neSelectData3.valueType = "label";
						}else{
							neSelectData3.id=me.levelChildId;
							neSelectData3.type="na";
							neSelectData3.order = "";
						}
					}else{
						neSelectData3.id= me.levelChildId;
						neSelectData3.type="na_axe3";
						neSelectData3.order = "";
					}
	            }
	            
	        });
			
			
			 // Counter parameters
			var rawKpiId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['kpi_id'];
			var rawKpiProductId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'];
			var rawKpiType = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['type'];
			var rawKpiData = {};
			rawKpiData.id = rawKpiId;        
			rawKpiData.productId = rawKpiProductId;
			rawKpiData.type = rawKpiType;       


			// On remplie le store pour garder les id parents en memoire dans le cas ou on change un paramètre dans le panneau de conf	
        	//on veut récupérer les dernière valeurs pour les parent id
        	timeData.order = "Ascending";
        	if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ""){
        		var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2);
        	}else{
        		var selectData = new Array(timeData, rawKpiData,neSelectData);
        		
        	}
        	
        	
        	var requestDataId = {};
	        requestDataId.method = 'getDataAndLabels';
	        requestDataId.parameters = {};
	        if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ""){
	       		requestDataId.parameters.roaming = true;
	        }else{
	        	
	        	requestDataId.parameters.mapid = true
	        	
	        }
	        requestDataId.parameters.select = {};
	        requestDataId.parameters.select.data = selectData;
	        requestDataId.parameters.filters = {};
	        
	        
			
			//On récupère les id ammap dans la table edw_object_ref
			parentIdArray = new Array();
			Ext.Ajax.request({
				url : 'proxy/configuration.php',
				async : false,
				params : {
					task : 'GET_AMMAPID',
					sdp_id : config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'],
					selected_level : axis == 2 ?  neLevelAxe2 : neLevelAxe1
				},
				
				success : function(response) {
					//me.ammapIds = response.responseText;
					me.ammapIds=Ext.decode(response.responseText);
					for (var i = 0; i < me.ammapIds['data'].length; i++) {
						parentIdArray.push(me.ammapIds['data'][i]['mapId']);
					}
				}
        	});
        	
        	
        	// Only get the last value in the database
			var limitFilter = {};
			var timeNumber = config['units_number']*me.ammapIds['data'].length;
			if (timeNumber == null || 
				((typeof(timeNumber) != 'string') && (typeof(timeNumber) != 'number')) ||
				timeNumber == '') {
			
				timeNumber = 20;
			}
			
			limitFilter.id = 'maxfilter'; 
			limitFilter.type = 'sys';
			limitFilter.value = timeNumber;
			limitFilter.date = me.lastintegrationdate;
		
			limitFilter.timelevel = timeUnit;
			
	        // Network Agregation
			var neData = {}; 
	        neData.type = 'na';
	        neData.operator = 'in';
	        neData.id=neLevelAxe1;
	      
			if(axis == 1){
				neData.value=parentIdArray.join(',');
			}else{
				neData.value=me.nes.ids.join(',');
			}
	        
	    	var neId2 = null;
		    if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ""){
		    	if(axis == 2){
					var neData2 = {}; 
					var neId2 = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_network_level2'];
					neData2.type = 'na_axe3';
					neData2.operator = 'in';
					neData2.id = neLevelAxe2; //neId2
					neData2.value =parentIdArray.join(',');
				}else{
					var neData2 = {}; 
					var neId2 = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'];
					neData2.type = 'na_axe3';
					neData2.operator = 'equals to';
					neData2.id = neLevelAxe2; //neId2
					neData2.value =neId2;
				}
		        
	        }
	
	        if (neId2 != null) {
	        	 var filtersData = new Array(neData,neData2, limitFilter);
	        } else {
	        	
		         var filtersData = new Array(neData, limitFilter);
		  	}
	      
	        requestDataId.parameters.filters.data = filtersData;
	        
	        var requestParamId = {};
	        requestParamId.data = Ext.encode(requestDataId);
	        //we get parent value with this request
	        Ext.Ajax.request({
	        	url: 'proxy/dao/api/querydata/index.php',
	        	params: requestParamId,
				async : false,
	            success: function (response) {
	        		var error = false;
	        		var result = null;
	        		try {
	        			result = Ext.decode(response.responseText);
	        			if (typeof result['error'] != "undefined") {
	        				// The request send an error response
	        				error = true;
	        				trend.hide();
	        			}else{
	        				//console.log('trend = '+ tabId + '_' + config['@attributes']['id'] + '_trend');
	        				trend.show();
	        			}
	        		} catch (err) {
	        			// The json is invalid
	        			error = true;
	        			
	        		}
	        		if(error == false){
		        		//me.associationStore.loadData(result.values.data);
						for(i=0;i<result.values.data.length;i++){
							if(axis == 2){
								me.nes.idvalueparent[result.values.data[i][3]] = result.values.data[i][1];
							}else{
								me.nes.idvalueparent[result.values.data[i][2]] = result.values.data[i][1];
								
							}
						}
						//On repasse le timedata en descending
		        		timeData.order = "Descending";
	        		}
	            },
	            failure:  function(response, options) {
                    console.log('Error', "Communication failed");
                }
	        });
			
			if(axis == 1){
        		if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
        		 	var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2,neSelectData3);	
        		}else{
        			var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData3);	
        		}
        	}else{
        		 var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2,neSelectData3);	
        	}
			
			//On  récupère les label des ne pour remplir la carte et les infobulle
			var requestData = {};
			
			requestData.method = 'getDataAndLabels';
			requestData.parameters = {};
			//we don't want label but id map in return of this request 
			 if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ""){
				requestData.parameters.mapid = true;
			 }
			
			requestData.parameters.select = {};
			requestData.parameters.select.data = selectData;
			requestData.parameters.filters = {};
			
			// Only get the last value in the database
			var limitFilter = {};
			var timeNumber = config['units_number']*me.ammapIds['data'].length;
			if (timeNumber == null || 
				((typeof(timeNumber) != 'string') && (typeof(timeNumber) != 'number')) ||
				timeNumber == '') {
			
				timeNumber = 20;
			}
			
			limitFilter.id = 'maxfilter'; 
			limitFilter.type = 'sys';
			limitFilter.value = timeNumber;
			limitFilter.date = me.lastintegrationdate;
			limitFilter.timelevel = timeUnit;
			

			// Network Agregation
			var neData = {}; 
			neData.type = 'na';
			neData.operator = 'in';
			if(axis == 2){
				neData.id=config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_network_level'];
			}else{
				neData.id=me.levelChildId;
			}
			
			neData.value=me.nes.ids.join(',');
			
			var neId2 = null;
			if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ""){
		    	if(axis == 2){
					var neData2 = {}; 
					var neId2 = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_network_level2'];
					neData2.type = 'na_axe3';
					neData2.operator = 'in';
					neData2.id = me.levelChildId; //neId2
					neData2.value =me.nes.ids2.join(',');
				}else{
					var neData2 = {}; 
					var neId2 = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'];
					neData2.type = 'na_axe3';
					neData2.operator = 'equals to';
					neData2.id = neLevelAxe2; //neId2
					neData2.value =neId2;
				}
			}

			/**
			console.log("neData =");
			console.log( neData);
			console.log("limitFilter=");
			console.log( limitFilter);
			**/


			if (neId2 != null) {
				 var filtersData = new Array(neData,neData2, limitFilter);
			} else {
				 var filtersData = new Array(neData, limitFilter);
			}
			
			//var filtersData = new Array(neData, limitFilter);	        
			requestData.parameters.filters.data = filtersData;
			
			var requestParam = {};
			requestParam.data = Ext.encode(requestData);

			}else{				
		       	if(typeof config['network_elements']['network_element'][0]['ne_id2'] !== 'undefined' && typeof(config['network_elements']['network_element'][0]['ne_id2'])!= 'object' && config['network_elements']['network_element'][0]['ne_id2'] != ''){
		       	 	is3emeAxe = true;
		        }
				
		        var neLevelId_configMap = tabId + '_' + config['@attributes']['id'] + '_neLevelId_configMap';
		        var neLevelId2_configMap = tabId + '_' + config['@attributes']['id'] + '_neLevelId2_configMap';
		        var neLevelAxe1 = config['network_elements']['network_level'];
		        var neLevelAxe2 =config['network_elements']['network_level2'];
		        productId = config['network_elements']['network_element'][0]['product_id'];
				
				neLevelAxe1 = config['network_elements']['network_level'];
		        	
				Ext.getCmp('neLevelId_configMap').setValue(neLevelAxe1);
				Ext.getCmp('neLevelId2_configMap').setValue('');
				Ext.getCmp('parentLevelSelected_configMap').setValue('');
							
				var neSelectData = {};
				neSelectData.id=neLevelAxe1;
				neSelectData.type="na";
				neSelectData.order = "Ascending";
				
				if(is3emeAxe == true){
					var neLevelAxe2 =config['network_elements']['network_level2'];
					var neSelectData2 = {};
					neSelectData2.id=neLevelAxe2;
					neSelectData2.type="na_axe3";
					neSelectData2.order = "Ascending";
				}
				
				// Counter parameters
		        var rawKpiId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['kpi_id'];
		        var rawKpiProductId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'];
		        var rawKpiType = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['type'];
		        var rawKpiData = {};
		        rawKpiData.id = rawKpiId;        
		        rawKpiData.productId = rawKpiProductId;
		        rawKpiData.type = rawKpiType;   
				
				 if(is3emeAxe == true){
					var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2);	
				 }else{
					var selectData = new Array(timeData, rawKpiData,neSelectData);	
				 }
				 
			    requestParam = requestData;
				//get selected map from map.xml file
				Ext.Ajax.request({
					url: 'proxy/ne_labels.php',
					async:false,
					params: requestParam,
		
					success: function(response) {
						// Decode the json into an array object
						if (response.responseText != '') {
							var labelsarray=Ext.decode(response.responseText);
							for(i=0;i<labelsarray.length;i++){
								me.nes.labels.push(labelsarray[i].label);
								me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
								me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
							}
						}				
					}
				});	

				requestParam = requestData;
				//get selected map from map.xml file
				Ext.Ajax.request({
					url: 'proxy/ne_labels.php',
					async:false,
					params: requestParam,
				
					success: function(response) {
						// Decode the json into an array object
						if (response.responseText != '') {
							var labelsarray=Ext.decode(response.responseText);
							for(i=0;i<labelsarray.length;i++){
								me.nes.labels.push(labelsarray[i].label);
								me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
								me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
							}
						}				
					}
				});
				 
				 
				//On  récupère les label des ne pour remplir la carte et les infobulle
		        var requestData = {};
		       	
		        requestData.method = 'getDataAndLabels';
		        requestData.parameters = {};
				
		        //we don't want label but id map in return of this request 
		       	requestData.parameters.formatbh = true;
		        requestData.parameters.select = {};
		        requestData.parameters.select.data = selectData;
		        requestData.parameters.filters = {};
		        
		        // Only get the last value in the database
				var limitFilter = {};
				var timeNumber = config['units_number']*config['network_elements']['network_element'].length;
				if (timeNumber == null || 
					((typeof(timeNumber) != 'string') && (typeof(timeNumber) != 'number')) ||
					timeNumber == '') {
				
					timeNumber = 20;
				}
				
				limitFilter.id = 'maxfilter'; 
				limitFilter.type = 'sys';
				limitFilter.value = timeNumber;
				//limitFilter.date = me.lastintegrationdate;
				//limitFilter.timelevel = timeUnit;
				
		
				// Network Agregation
				var neData = {}; 
		        neData.type = 'na';
		        neData.operator = 'in';
		        if(typeof(neLevelAxe2) !== 'object' &&  neLevelAxe2 != ''  && typeof(neLevelAxe2) !== 'undefined'){
		       		neData.id=config['network_elements']['network_level'];
		        }else{
					neData.id=config['network_elements']['network_level'];
		        }
		        neData.value=me.nes.ids.join(',');
		    	var neId2 = null;
		        if (typeof(config['network_elements']['network_element'][0]['ne_id2']) !== 'undefined' && typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'object' && config['network_elements']['network_element'][0]['ne_id2'] != "") {
			        var neData2 = {}; 
			        var neId2 = config['network_elements']['network_level2'];
			        neData2.type = 'na_axe3';
			        neData2.operator = 'in';
			        if(config['roaming'] == 'true'){
			        	neData2.id = me.levelChildId; //neId2
			        }else{
			        	neData2.id = neLevelAxe2;
			        }
			        
			        neData2.value =me.nes.ids2.join(',');
			        
		        }
				/**
				console.log("neData =");
				console.log( neData);
				console.log("limitFilter=");
				console.log( limitFilter);
				**/
		
		
		        if (neId2 != null) {
		        	 var filtersData = new Array(neData,neData2, limitFilter);
		        } else {
			         var filtersData = new Array(neData, limitFilter);
			  	}
			  	
		        //var filtersData = new Array(neData, limitFilter);	        
		        requestData.parameters.filters.data = filtersData;
		        
		        var requestParam = {};
		        requestParam.data = Ext.encode(requestData);
			
		}
        //Used to prevent ajax response timeout
        Ext.Ajax.timeout = 60000;
        Ext.override(Ext.data.Connection, {timeout: 60000});
        Ext.override(Ext.data.proxy.Ajax, { timeout: 60000 });
		Ext.override(Ext.form.action.Action, { timeout: 60 });

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
        				error = true;
        				trend.hide();
        			}else{
        				trend.show();
        			}
        		} catch (err) {
        			// The json is invalid
        			console.log(err);
        			error = true;
        			
        		}finally {
					//Show the kpi combobox in case there is no data found
					if(config['fullscreen']== "true"){
						if (error == true){
							trend.show();
							var mapContainer = Ext.getCmp(tabId + '_' + config['@attributes']['id']+'_map_container');
							mapContainer.hide();
							var ErrorMessage = Ext.get(tabId + '_' + config['@attributes']['id'] + '_map_title');
							ErrorMessage.show();
							
						}else{
							var ErrorMessage = Ext.get(tabId + '_' + config['@attributes']['id'] + '_map_title');
							ErrorMessage.hide();
						}
					}
				}
        		if(error == false){
	        		me.dataTrend=result;

	    			//get zoom parameters from current map
	    			var zoomLevel=me.map.zoomLevel();
	    			var zoomLongitude=me.map.zoomLongitude();
	    			var zoomLatitude=me.map.zoomLatitude();
	    			
    				var homeZoomLevel = "";
 					var homeZoomLongitude= "";
 					var homeZoomLatitude= "";
	    			
	    			
	    			// create AmMap object
	    			me.map = new AmCharts.AmMap();
	    	        me.map.pathToImages = 'images/ammap/';
	    			
	    	        me.map.panEventsEnabled = true;
	    	        
	    			// Create the data provider
	        	    var mapAreas = new Array();
	 
	        	    var kpilabelonmap='';
	 
	        	    var date = '';
	        	    
	        	    
	        	    var refdate=me.dataTrend.values.data[0][0];
	        		
		        	if (me.trendTimeLevel == 'hour') {
	    	        	var dateT=new Date(me.dataTrend.values.data[0][0].substring(0, 4),me.dataTrend.values.data[0][0].substring(4, 6)-1,me.dataTrend.values.data[0][0].substring(6, 8),me.dataTrend.values.data[0][0].substring(8));
		        		date=Ext.Date.format(dateT,"Y/m/d H:s");
		        	}
		        	else if (me.trendTimeLevel == 'day' || me.trendTimeLevel == 'day_bh') {
	    	        	var dateT=new Date(me.dataTrend.values.data[0][0].substring(0, 4),me.dataTrend.values.data[0][0].substring(4, 6)-1,me.dataTrend.values.data[0][0].substring(6, 8));
		        		date=Ext.Date.format(dateT,"Y/m/d");
		        	} else if (me.trendTimeLevel == 'month' || me.trendTimeLevel == 'month_bh') {
	    	        	var dateT=new Date(me.dataTrend.values.data[0][0].substring(0, 4),me.dataTrend.values.data[0][0].substring(4, 6)-1);
		        		date=Ext.Date.format(dateT,"m/Y");
		        	}else if (me.trendTimeLevel == 'week'){
	    	        	var dateT=new Date(me.dataTrend.values.data[0][0].substring(0, 4));
		        		date=Ext.Date.format(dateT,"Y") + ' W' + me.dataTrend.values.data[0][0].substring(4);
		        	}else if(me.trendTimeLevel == 'week_bh'){
	        				if(me.dataTrend.values.data[0][0].length > 6){
		        				var year = me.dataTrend.values.data[0][0].substring(0,4);
								var month = me.dataTrend.values.data[0][0].substring(4,6);
								var day = me.dataTrend.values.data[0][0].substring(6,8);
	        					var week = me.getWeekNumber(year+'-'+month+'-'+day);
	        					week = week[1];
	        					date = year+' W'+week;
	        				}else{
	        					var dateT=new Date(me.dataTrend.values.data[0][0].substring(0, 4));
	        					date=Ext.Date.format(dateT,"Y") + ' W' + me.dataTrend.values.data[0][0].substring(4);
	        				}
			         }

		        	var refDateArray = Ext.Array.filter(me.dataTrend.values.data, function(item) {
							    return item;
						});
		        	/**
		        	if(me.trendTimeLevel == 'hour' || me.trendTimeLevel == 'day' || me.trendTimeLevel == 'week' || me.trendTimeLevel == 'month'){
			        	var refDateArray = Ext.Array.filter(me.dataTrend.values.data, function(item) {
							    return item[0].indexOf(refdate) != -1;
						});
		        	}else{
		        		var refDateArray = Ext.Array.filter(me.dataTrend.values.data, function(item) {
							    return item;
						});
		        	}
					**/
					
					//console.log("test");
					//console.log(me.dataTrend);
					
					var refArray = new Array();
					if(config['roaming'] == 'false'){
						for (var i = 0; i < me.nes.labels.length; i++) {
						 var le=refDateArray.length;
							 var found=-1;
							 for(var m=0;m<le;m++){
								if (typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'undefined') {
									if(refDateArray[m][3]==me.nes.labels[i]){
									  	found=m;
									}
								}else{
									if(refDateArray[m][2]==me.nes.labels[i]){
									  	found=m;
									}
								}
								if(found != -1 ){
									break;
								}
							 }
							 
							 if(found == -1){
								   refArray[me.nes.labels[i]] = "";
								  }else{
								   refArray[me.nes.labels[i]] = refDateArray[found][1];
							 }
							 
						}
					}else{
						for (var i = 0; i < me.nes.labels.length; i++) {
						 var le=refDateArray.length;
							 var found=-1;
							 for(var m=0;m<le;m++){
							  	if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
							 		var current_refarrayLabel = refDateArray[m][4];
							  	}else{
							  		var current_refarrayLabel = refDateArray[m][3];
							  	}
							  	if(current_refarrayLabel==me.nes.labels[i]){
							  		found=m;
							 	 }
							 	 if(found != -1 ){
									break;
							 	 }
							 }
							 
							 if(found == -1){
								   refArray[me.nes.labels[i]] = "";
								  }else{
								   refArray[me.nes.labels[i]] = refDateArray[found][1];
							 }
							 
						}
					}
					
					var listeCode = new Array();
					var countryPos = 0;
					
	        	    for (var i = 0; i < me.nes.labels.length; i++) {
	        	    	
	        	    	var area = new Object();
	        	       	var checkeIdParent = false;
	    	        	
	        	       	//area.id = me.nes.zones[i];
			        	area.id = me.nes.zoneslabels[me.nes.labels[i]];
			        	//group areas in the same group 
			        	//area.groupId='mygroup';
			        	
			        	//Each area has it own group
			        	area.groupId='group'+i;
			        	
        				area.rollOverOutlineColor= "gray";
        				area.outlineColor= "white";
				        area.outlineThickness= "0.1";
				        //area.outlineAlpha= "0.5";
				        
				        //area.unlistedAreasColor= "#DDDDDD";
				        //area.unlistedAreasOutlineColor= "white";
				        //area.unlistedAreasOutlineAlpha= "1";
			        	//area.unlistedAreasAlpha = "0.1"
				        
			        	// Text
			        	var unit = ' %';
			        	if (config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'other') {
			        		unit = ' ' + config['kpi_groups']['group'][index].axis_list.axis_trend.unit;
			        	}
			        	var kpivalue='';   

		        		if(refArray[me.nes.labels[i]] == ""){
		        			 kpivalue='No Data' + (unit.indexOf("[object Object]")!=-1 ? '' : unit);
		        			 //me.dataTrend.values.data[i][1]="";
		        		}
		        		else{
		        			kpivalue=(refArray[me.nes.labels[i]].indexOf('.')!=-1 && refArray[me.nes.labels[i]].indexOf('+')==-1 ? parseFloat(refArray[me.nes.labels[i]]).toFixed(2):refArray[me.nes.labels[i]]) + (unit.indexOf("[object Object]")!=-1 ? '' : unit);
		        		}
			        	//(me.dataTrend.values.data[i][1]=="" ? 'No Data' : (me.dataTrend.values.data[i][1].indexOf('.')!=-1 && me.dataTrend.values.data[i][1].indexOf('+')==-1 ? parseFloat(me.dataTrend.values.data[i][1]).toFixed(2):me.dataTrend.values.data[i][1])) + (unit.indexOf("[object Object]")!=-1 ? '' : unit)      	        	
			        	//Si on est pas sur une map roaming dans ce cas on affiche la valeur du kpi pour le ne choisi
			        	if(config['roaming'] == 'false'){
				        	area.balloonText = '[[title]] : ' +/* me.dataTrend[i].labels[1].na + ' ' +  */me.nes.labels[i] + (unit.indexOf("[object Object]")!=-1 ? '' : unit)+'\n'
				        	+ date + '\n'
			        		+ me.dataTrend.labels[0].label + ': ' + kpivalue
			        		+ (!me.isIpad ?'\n\nclick to access T&A dashboard':'\n'); 
				        	
				        	//label that will be displayed on top of map
				        	if(me.trendTimeLevel == 'hour' || me.trendTimeLevel == 'day' || me.trendTimeLevel == 'week' || me.trendTimeLevel == 'month'){
				        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+date;
				        		kpilabelonmap=date+'\n'+ config['kpi_groups']['group'][index]['group_name'];
				        	}else{
				        		
				        		if(me.trendTimeLevel == 'day_bh'){
					        		dateTitle = date.substring(0,10);
					        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+dateTitle;
					        		kpilabelonmap=dateTitle+'\n'+config['kpi_groups']['group'][index]['group_name'];
					         	}else if(me.trendTimeLevel == 'week_bh'){
			        				dateTitle = date;
					        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+dateTitle;
					        		kpilabelonmap=dateTitle+'\n'+config['kpi_groups']['group'][index]['group_name'];
					        	}else if (me.trendTimeLevel == 'month_bh'){
					        		dateTitle = date.substring(0,7);
					        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+dateTitle;
					        		kpilabelonmap=dateTitle+'\n'+config['kpi_groups']['group'][index]['group_name'];
					        	}
				        	}
				        	
				        	
			        	}
			        	//Si on est sur une map roaming on affiche la valeur du kpi pour tout les ne enfant
			        	else{
			        		var maxDisplayChild = 10;
			        		var child_value = me.nes.labels[i] + ': ' + kpivalue;
			        		var currentCodeZoneMap = me.nes.zoneslabels[me.nes.labels[i]];
							var codeZoneMap = false;
							if (currentCodeZoneMap !== '' && currentCodeZoneMap !== 'UKN' && typeof(currentCodeZoneMap) !== 'undefined'){
								if(me.inArray(listeCode,currentCodeZoneMap) === false){
									listeCode.push(currentCodeZoneMap);
									codeZoneMap = currentCodeZoneMap;
								}
							}
							
							if(codeZoneMap){
				        		//we start at 1 because we already got one value when we start the for 
				        		var numberChild = 1;
				        		//We store the the kpi value of the current child
				        		var childKpivalue = refArray[me.nes.labels[i]];
			        			if(childKpivalue != ''){
			        				var worstValue = childKpivalue;
			        			}else{
			        				var worstValue = 'none';
			        			}
			        			if(checkeIdParent == false){
				        			//we get only one time the parent id
				        			var parentValue = me.nes.idvalueparent[codeZoneMap];
				        			var checkeIdParent = true;
			        			}
			        			var indeCount = 0;
				        		
				        		
				        		for (var x = i+1; x < me.nes.labels.length; x++) {
				        			if(x+1 == me.nes.labels.length){
				        				checkeIdParent = false;
				        				var numberChild = 1;
				        			}
				        			if(typeof( me.nes.zoneslabels[me.nes.labels[x]]) !== 'undefined'){
					        			if(me.nes.zoneslabels[me.nes.labels[x]] == codeZoneMap){ 
					        				if(me.nes.zoneslabels[me.nes.labels[x+(maxDisplayChild-1)]] == codeZoneMap){
				        						if(refArray[me.nes.labels[x]].indexOf('.')!=-1 && refArray[me.nes.labels[x]].indexOf('+')==-1){
				        							numberChild++;
				        							kpivalue=(refArray[me.nes.labels[x]].indexOf('.')!=-1 && refArray[me.nes.labels[x]].indexOf('+')==-1 ? parseFloat(refArray[me.nes.labels[x]]).toFixed(2):refArray[me.nes.labels[x]]) + (unit.indexOf("[object Object]")!=-1 ? '' : unit);
				        							if(numberChild <= maxDisplayChild){
					        							child_value += '\n'+me.nes.labels[x] + ': ' + kpivalue;
					        						}
				        						}else{
				        							continue;
				        						}
				        					
				        					}else{
				        						if(refArray[me.nes.labels[x]] != ''){
						        					numberChild++;
						        					kpivalue=(refArray[me.nes.labels[x]].indexOf('.')!=-1 && refArray[me.nes.labels[x]].indexOf('+')==-1 ? parseFloat(refArray[me.nes.labels[x]]).toFixed(2):refArray[me.nes.labels[x]]) + (unit.indexOf("[object Object]")!=-1 ? '' : unit);
						        				}else{
						        					numberChild++;
						        					kpivalue='No Data' + (unit.indexOf("[object Object]")!=-1 ? '' : unit);
						        				}
						        				if(numberChild <= maxDisplayChild){
						        					child_value += '\n'+me.nes.labels[x] + ': ' + kpivalue;
						        					
						        				}
				        						
				        					}
				        					var currentChildKpiValue = refArray[me.nes.labels[x]];
					        				if(currentChildKpiValue != ''){
					        					var float_currentChildKpiValue = parseFloat(currentChildKpiValue);
					        					var float_worstValue = parseFloat(worstValue);
					        					if(worstValue != 'none'){
					        						if (config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'failure') {
						        						if(float_currentChildKpiValue > float_worstValue){
						        							worstValue = currentChildKpiValue;
						        						}
					        						}else{
					        							if(float_currentChildKpiValue < float_worstValue){
						        							worstValue = currentChildKpiValue;
						        						}
					        						}
					        					}else{
					        						worstValue = currentChildKpiValue;
					        					}
					        				}   
					        				
					        			}
				        			}
				        		}
							}
			        		
			        		if(codeZoneMap != false){
			        			//console.log(codeZoneMap);
				        		var parentValueLabel = (me.nes.idvalueparent[codeZoneMap]!=="" && typeof(me.nes.idvalueparent[codeZoneMap]!=="undefined") ? me.nes.idvalueparent[codeZoneMap] : "No Data");
				        		area.balloonText = '[[title]] : ' +/* me.dataTrend[i].labels[1].na + ' ' +  */me.dataTrend.labels[0].label + ': ' + parentValueLabel +(unit.indexOf("[object Object]")!=-1 ? '' : unit)+'\n'
					        	+ date + '\n'
				        		+ child_value
				        		+ (!me.isIpad ?'\n\nclick to access T&A dashboard':'\n'); 
				        		//label that will be displayed on top of map
					        	if(me.trendTimeLevel == 'hour' || me.trendTimeLevel == 'day' || me.trendTimeLevel == 'week' || me.trendTimeLevel == 'month'){
					        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+date;
					        		kpilabelonmap=date+'\n'+ config['kpi_groups']['group'][index]['group_name'];
					        	}else{
					        		if(me.trendTimeLevel == 'day_bh'){
						        		dateTitle = date.substring(0,10);
						        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+dateTitle;
						        		kpilabelonmap=dateTitle+'\n'+config['kpi_groups']['group'][index]['group_name'];
						         	}else if(me.trendTimeLevel == 'week_bh'){
				        				//dateTitle = date.substring(0,7);
						         		dateTitle = date
						        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+dateTitle;
						        		kpilabelonmap=dateTitle+'\n'+config['kpi_groups']['group'][index]['group_name'];
						        	}else if (me.trendTimeLevel == 'month_bh'){
						        		dateTitle = date.substring(0,7);
						        		//kpilabelonmap=me.dataTrend.labels[0].label+': '+dateTitle;
						        		kpilabelonmap=dateTitle+'\n'+config['kpi_groups']['group'][index]['group_name'];
						        	}
					        	}
				        		if(config['displayed_value_mode'] == 'worst_sub_element'){
				        			var color = '#94AE0A';
				        			//Si on a auncune valeur pour aucune des valeur enfants alors on affiche la couleur #7F7F7F dans la zone
			        				if(worstValue == 'none' || typeof(worstValue) === "undefined"){
			        					color = '#7F7F7F';
			        				}else{
			        					if (config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'failure') {
					    	        		if ( config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold) <= worstValue) {
					    	        			color = '#FF0000';
					    	        		} else if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold) <= worstValue) {
					    	        			color = '#FF8C00';
					    	        		}
					    	        	} else {
					    	        		if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold) >= worstValue) {
					    	        			color = '#FF0000';
					    	        		} else if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold) >= worstValue) {
					    	        			color = '#FF8C00';
					    	        		}
					    	        	}	  
			        				}
				        		}else{
				        			//TODO valeur du element
				        			var color = '#94AE0A';
				        			if (me.nes.idvalueparent[area.id]=="" || typeof(me.nes.idvalueparent[area.id]) === "undefined"){	
				        				color = '#7F7F7F';
				        			}
				        			if (config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'failure') {
				    	        		if(parseFloat(me.nes.idvalueparent[me.nes.zoneslabels[me.nes.labels[i]]]) != ""){
					    	        		if ( config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold) <= parseFloat(me.nes.idvalueparent[codeZoneMap])) {
					    	        			color = '#FF0000';
					    	        		} else if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold) <= parseFloat(me.nes.idvalueparent[codeZoneMap])) {
					    	        			color = '#FF8C00';
					    	        		}
				    	        		}else{
				    	        			color = '#7F7F7F';
				    	        		}
				    	        	} else {
				    	        		if(me.nes.idvalueparent[me.nes.zoneslabels[me.nes.labels[i]]] !== "" ){
					    	        		if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold) >= parseFloat(me.nes.idvalueparent[codeZoneMap])) {
					    	        			color = '#FF0000';
					    	        		} else if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold) >= parseFloat(me.nes.idvalueparent[codeZoneMap])) {
					    	        			
					    	        			color = '#FF8C00';
					    	        		}
				    	        		}else{
				    	        			color = '#7F7F7F';
				    	        		}
				    	        	}	
				        			
				        		}
					        	
						        area.color = color;		 
					        	area.selectedColor = color;
					        	area.autoZoom=false;
					        	if(!me.isIpad){
				    	        	if(config['roaming'] == 'false'){
					    	        	if(me.trendTimeLevel == 'month' || me.trendTimeLevel == 'month_bh'){
			    							currentTimeValue = me.dataTrend.values.data[0][0];
			    						}else{
			    							currentTimeValue=me.dataTrend.values.data[i][0];
			    						}
			    						area.customData={
				    	        						raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
				    	        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
				    	        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
				    	        						time_agregation:me.trendTimeLevel,
				    	        						time_value:currentTimeValue,
				    	        						network_agregation:config.network_elements.network_level,
				    	        						//network_name:me.dataTrend.values.data[i][2],
				    	        						network_name:me.nes.idsindex[me.nes.labels[i]],		    	        						
				    	        						period:config.units_number,	
				    	        					};
				    	        	}else{
				    	        		currentTimeValue = me.dataTrend.values.data[0][0];
				    	        		var network_axe_level = config['kpi_groups']['group'][index]['kpis']['kpi_trend'].roaming_network_level;
				    	        		var network_axe3_level = config['kpi_groups']['group'][index]['kpis']['kpi_trend'].roaming_network_level2;
				    	        		
				    	        		if(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['network_axis_number'] == '1'){
				    	        			if(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'] !==  'undefined' && config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'] != ''){
				    	        				area.customData={
				    	        						raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
				    	        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
				    	        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
				    	        						time_agregation:me.trendTimeLevel,
				    	        						time_value:currentTimeValue,
				    	        						network_agregation:network_axe_level,
				    	        						network_name:area.id,
				    	        						network_3_axe_agregation:network_axe3_level,
				    	        						network_3_axe_name:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].roaming_ne_id2,
				    	        						period:config.units_number
				    	        				};
				    	        			}else{
				    	        				area.customData={
				    	        						raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
				    	        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
				    	        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
				    	        						time_agregation:me.trendTimeLevel,
				    	        						time_value:currentTimeValue,
				    	        						network_agregation:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].roaming_ne_id,
				    	        						network_name:area.id,		    	        						
				    	        						period:config.units_number
				    	        				};
				    	        			}
				    	        		}else{
				    	        			area.customData={
				    	        						raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
				    	        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
				    	        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
				    	        						time_agregation:me.trendTimeLevel,
				    	        						time_value:currentTimeValue,
				    	        						network_agregation:network_axe_level,
				    	        						network_name:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].roaming_ne_id,
				    	        						network_3_axe_agregation:network_axe3_level,
				    	        						network_3_axe_name:area.id,
				    	        						period:config.units_number
				    	        				};
				    	        		}
				    	        		/**
				    	        		if(config.network_elements.parent_level_selected == 'axe2'){
				    	        			var network_axe2_id = config.network_elements.network_element[0].ne_id;
				    	        			var network_axe3_id = area.id;
				    	        		}else{
				    	        			var network_axe2_id = area.id;
				    	        			var network_axe3_id = config.kpi_groups.group[index].kpis.kpi_trend.roaming_ne_id2;
				    	        			
				    	        		}
				    	        		
				    	        		area.customData={
				    	        						raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
				    	        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
				    	        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
				    	        						time_agregation:me.trendTimeLevel,
				    	        						time_value:currentTimeValue,
				    	        						network_agregation:network_axe2_level,
				    	        						network_name:network_axe2_id,
				    	        						network_3_axe_agregation:network_axe3_level,
				    	        						network_3_axe_name:network_axe3_id,
				    	        						period:config.units_number
				    	        		};
				    	        		console.log(area.customData)
				    	        		**/
				    	        	}
				    	        	
					        	}	
					        	//TODO see if map declaration in load is necessary
					        	// Add the area	
			    	        	mapAreas.push(area);
				        	}
			        	}
			        	
			        	if(config['roaming'] == 'false'){
				        	// Color
				        	var color = '#94AE0A';
				        	if(refArray[me.nes.labels[i]]=="" || typeof(refArray[me.nes.labels[i]]) === "undefined"){
				        		color = '#7F7F7F';
				        	}
				        	else{
				        		if (config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'failure') {
			    	        		if ( config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold) < parseFloat(refArray[me.nes.labels[i]])) {
			    	        			color = '#FF0000';
			    	        		} else if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold) < parseFloat(refArray[me.nes.labels[i]])) {
			    	        			color = '#FF8C00';
			    	        		}
			    	        	} else {
			    	        		if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold) > parseFloat(refArray[me.nes.labels[i]])) {
			    	        			color = '#FF0000';
			    	        		} else if (config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold != '' && parseFloat(config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold) > parseFloat(refArray[me.nes.labels[i]])) {
			    	        			color = '#FF8C00';
			    	        		}
			    	        	}	    
				        	}
				        	
					        area.color = color;		 
				        	area.selectedColor = color;
				        	area.autoZoom=false;
				        	
				        	
				        	if(!me.isIpad){
		    	        	if(me.trendTimeLevel == 'month' || me.trendTimeLevel == 'month_bh'){
    							currentTimeValue = me.dataTrend.values.data[0][0];
    						}else{
    							currentTimeValue=me.dataTrend.values.data[i][0];
    						}
    						if(config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'] !==  'undefined' && config['kpi_groups']['group'][index]['kpis']['kpi_trend']['roaming_ne_id2'] != ''){
	    						if(me.blockedAxe == '1'){
		    						area.customData={
			    						raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
		        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
		        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
		        						time_agregation:me.trendTimeLevel,
		        						time_value:currentTimeValue,
		        						network_agregation:config.network_elements.network_level,
		        						//network_name:me.dataTrend.values.data[i][2],
		        						network_name:me.nes.idsindex[me.nes.labels[i]],
		        						network_3_axe_agregation:config.network_elements.network_level2,
					    	        	network_3_axe_name:area.id,
		        						period:config.units_number,	
	        						};
	    						}else{
	    							area.customData={
		    							raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
		        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
		        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
		        						time_agregation:me.trendTimeLevel,
		        						time_value:currentTimeValue,
		        						network_agregation:config.network_elements.network_level,
		        						//network_name:me.dataTrend.values.data[i][2],
		        						network_name:area.id,
		        						network_3_axe_agregation:config.network_elements.network_level2,
					    	        	network_3_axe_name:me.nes.idsindex[me.nes.labels[i]],
				    	        	};
	    						}
    						}else{
    							area.customData={
	        						raw_kpi:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].kpi_id,
	        						productId:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].product_id,
	        						type:config['kpi_groups']['group'][index]['kpis']['kpi_trend'].type,
	        						time_agregation:me.trendTimeLevel,
	        						time_value:currentTimeValue,
	        						network_agregation:config.network_elements.network_level,
	        						//network_name:me.dataTrend.values.data[i][2],
	        						network_name:me.nes.idsindex[me.nes.labels[i]],		    	        						
	        						period:config.units_number,	
	        					};
    						}
	    	        	
			    	        	
				        	}	
				        	//TODO see if map declaration in load is necessary
				        	
				        	// Add the area	
		    	        	mapAreas.push(area);
			        	}
	    	       }
	  				//console.log(me.nes);
	  				//console.log(refArray);
	  				//console.log(refDateArray);
	  				//console.log(listeCode);
	        	        //si non ipad, enable access to T&A dashboard on map
	    	        if(!me.isIpad){
	    	        	me.map.addListener('clickMapObject', function (event) {
	        	        	var url = 'proxy/openDashboard.php';
	        	        	
	        	        	customData=event.mapObject.customData;
		    	        	url += '?productId='+customData.productId;
		                    url += '&raw_kpi='+customData.raw_kpi;
		                    url += '&type='+customData.type;
		        			url += '&time_agregation='+customData.time_agregation;
		        			url += '&time_value='+customData.time_value;
		        			url += '&network_agregation='+customData.network_agregation;
		        			url += '&network_name='+customData.network_name;
		        			if(config['roaming'] == 'true'){
		        			url += '&network_3_axe_agregation='+customData.network_3_axe_agregation;
		        			url += '&network_3_axe_name='+customData.network_3_axe_name;
		        			}
		        			url += '&period='+customData.period;
		        			url += '&mode=overtime';
		        			url += '&overtimeonly=true';
							
		        			window.open(url, "homepage_selecteur_dashboard", "menubar=no, status=no, scrollbars=yes, width=450, height=450");
	        	        });
	    	        }
	    	        
	
	        		var dataProvider = {
	        	            mapVar: AmCharts.maps[me.mapId],
	        	            areas: mapAreas,
	        	            zoomLevel: zoomLevel,
	     					zoomLongitude: zoomLongitude,
	     					zoomLatitude: zoomLatitude
	        	        }; 
	   
	        	    // Pass data provider to the map object
	    	        me.map.dataProvider = dataProvider;
	    	        
	    	        trendTitle = me.dataTrend.labels[0].label + (trendUnit !=='' ? ' ('+trendUnit+')':'');
	    	       	if(typeof(config['home_zoom']) != 'undefined'){
		    	       	var homeZoomLevel = config.home_zoom.home_zoom_level !== "" ? config.home_zoom.home_zoom_level : zoomLevel;
     					var homeZoomLongitude= config.home_zoom.home_zoom_longitude !== "" ? config.home_zoom.home_zoom_longitude : zoomLongitude;
     					var homeZoomLatitude= config.home_zoom.home_zoom_latitude !== "" ? config.home_zoom.home_zoom_latitude : zoomLatitude;
	    	       	}
	    	        me.map.addListener("homeButtonClicked", function (event) {
				        //var info = me.map.getDevInfo();
				       	if(me.map.zoomLevel() < homeZoomLevel){
				        	me.map.zoomToLongLat(homeZoomLevel,homeZoomLongitude,homeZoomLatitude );
				       	}else{
				       		me.map.zoomToLongLat(zoomLevel,zoomLongitude,zoomLatitude );
				       		
				       	}
				    });

	    	        // Create areas settings
	    	        me.map.areasSettings = {
	    	    		autoZoom: false,
	    	            selectedColor: "#1d97d8",
	    	            outlineThickness: '0.1',
	    	            selectable: true
	    	        };
	    	        
	    	        me.map.smallMap = new AmCharts.SmallMap();
	    	        
	    	        //me.map.removeLegend();
	 
	        	        //add legend to map
	        	        //depending on kpi type
	        	        var legend = new AmCharts.AmLegend();
	        	        
	        	        var titlenodata='no data';
	        	        var titlenotmanaged='unmanaged country'
	        	        var titlelow='';
	        	        var titleaverage='';
	        	        var titlehigh='';
	        	        
	        	        
	        	        if (config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'success' || config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'failure') {
	        	        	titlelow='0-'+config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold+' %';
	        	        	titleaverage=config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold+'-'+config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold+' %';
	        	        	titlehigh=config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold+'-100 %';
	        	        }
	        	        else{
	        	        	var unitFromCfg = config['kpi_groups']['group'][index].axis_list.axis_trend.unit;
			        		if(me.empty(unitFromCfg)){
			        			currentUnit = ' ';
			        		}else{
			        			currentUnit = ' ' + config['kpi_groups']['group'][index].axis_list.axis_trend.unit;
			        		}
	        	        	titlelow='< '+config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold+' '+currentUnit;
	        	        	titleaverage=config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.low_threshold+'-'+config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold+' '+currentUnit;
	        	        	titlehigh='> '+config['kpi_groups']['group'][index].axis_list.axis_trend.thresholds.high_threshold+' '+currentUnit;	  
	        	        }
	    	        		
	        	        if(config['kpi_groups']['group'][index]['kpis']['kpi_trend'].function == 'failure'){
	        	        	legend.data = [{title:titlenotmanaged, color:"#DDDDDD"},{title:titlenodata, color:"#7F7F7F"},{title:titlelow, color:"#94AE0A"},{title:titleaverage, color:"#FF8C00"},{title:titlehigh, color:"#FF0000"}];
	
	    	        }
	    	        else{
	    	        	legend.data = [{title:titlenotmanaged, color:"#DDDDDD"},{title:titlenodata, color:"#7F7F7F"},{title:titlelow, color:"#FF0000"},{title:titleaverage, color:"#FF8C00"},{title:titlehigh, color:"#94AE0A"}];
	    	        }
	    	        
	    	        legend.backgroundAlpha=0.3;
	    	        legend.backgroundColor="#DDDEDE";
	    	        legend.borderAlpha=1;
	    	        legend.borderColor="gray";
	    	        legend.align="center";
	    	        legend.position="absolute";
	    	        legend.bottom=2;
	    	        legend.fontFamily= 'Verdana';
	    	        legend.fontSize= 12;
	    	        me.map.addLegend(legend);
	    	        
	    			
	    	        // Delete the previous charts
	    	       
	    	        var mapId = tabId + '_' + config['@attributes']['id'] + '_map';
	    	        var chart = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
	    	        var mapCont = Ext.getCmp(tabId + '_' + config['@attributes']['id']+'_map_container');
	    	        
	
	//	        	        //TODO voir conflit avec template gauge
	//	        	        if (chart.down('panel') != null) {
	//	        	        	chart.down('panel').destroy();
	//	        	        	if (chart.down('panel') != null) {
	//		        	        	chart.down('panel').destroy();
	//		        	        }
	//	        	        }
	    	        
	    	        var kpilabelsize=11;
	    	        var kpilabelx=0;
	    	        
	    	        if(me.displayMode=='true'){
	    	        	var h = chart.getHeight() - 45 - 40;
	    	        	var w = chart.getWidth() - 10;
	    	        	var per=1;
	    	        	kpilabelsize=16;
	    	        	kpilabelx=w*0.2;
	    	        }
	    	        else{
	    	        	var per = 0.45;
	    	        	var h = mapCont.getHeight() - 45;
	    	        	var w = mapCont.getWidth() *per - 10;
	    	        	kpilabelx=w*0.35;
	    	        }
	
	    	        //destroy map container
	    	        mapCont.removeAll();
	        	    mapCont.destroy();
	
	    	        //set kpi label on map
	    	        
	    	        //add kpi label on map
	    	        //me.map.clearLabels();
	        	    if(Ext.get('homepageStyle').dom.value == 'access'){
	        				var titleColor = '#fff';
	        			}else{
	        				var titleColor = '#000'
	        		}
	        	    
	    	        //me.map.addLabel(kpilabelx, 20, kpilabelonmap, 'left',kpilabelsize,'black',0,1,true);
					me.map.addTitle(kpilabelonmap,kpilabelsize,titleColor,'red',true);
					
					
					
	    	        var left = Ext.create('Ext.panel.Panel', {
	    	        	html: '<div id="' + mapId + '" style="height: ' + h + 'px; width: ' + w + 'px;"/>',
	        			id:tabId + '_' + config['@attributes']['id']+'_map_container',
	    	        	flex:45,
	        			cls: 'x-panel-no-border',
	        			listeners: {
	        		            'resize': function (panel) {
	        		                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
	        		                var mapcont = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
	        		                //take care of left panel width percentage (45 in our case)
	        		                var newWidth=mapcont.getWidth()*per -10;
	        		                var newHeight=mapcont.getHeight()-10;
	        		                if(me.displayMode=='true')newHeight-=40;
	        		                var mapId = tabId + '_' + config['@attributes']['id'] + '_map';
	        		              	if(config['fullscreen']== "true"){
	        		              		var modeBox = 'fullscreen';
	        		              	}else{
	        		              		var modeBox = 'trend_donut';
	        		              	}
	        		                var titleWidth = me.measureBoxSize(me.dataTrend.labels[0].label,modeBox)
	        		                
	        		                panel.update(
	        		                '<div id="' + mapId + '" style="height: ' + newHeight + 'px; width: ' + newWidth + 'px;"/>')
	        		                me.map.write(mapId); 
	        		               //TODO get the width from the kpi label lenght
	        		               // panel.body.insertHtml("beforeEnd", '<div id="' + mapId +'_background' + '" style="width:'+titleWidth+'px; height: 80px; position: absolute; top: 0; left: 34%; background: #DDDEDE; border-color: black; border-width: 1px;border-style: solid; opacity: 0.4;"/>');
	        		                if(modeBox == 'fullscreen'){
	        		                	panel.body.insertHtml("beforeEnd", '<div id="' + mapId +'_background' + '" style="width:55%; height: 50px; position: absolute; top: 5px; left: 23%; background: #DDDEDE; border-color: black; border-width: 1px;border-style: solid; opacity: 0.4;"/>');
	        		                }else{
	        		                	panel.body.insertHtml("beforeEnd", '<div id="' + mapId +'_background' + '" style="width:50%; height: 40px; position: absolute; top: 7px; left: 24%; background: #DDDEDE; border-color: black; border-width: 1px;border-style: solid; opacity: 0.4;"/>');
	        		                }
	        		            }
	        		            
	        		        }
	    	        }); 
	
	    	        if(me.displayMode!='true'){
	        	        chart.insert(0,left);
	    	        }
	    	        else{
	    	        	var fullscreencont = Ext.getCmp(tabId + '_' + config['@attributes']['id']+'_fullscreen');
	    	        	fullscreencont.add(left);
	    	        }
	    	        me.map.write(mapId);
	        		
	    			if(me.displayMode!='true'){
	    				        			
	        			// Create the data store
	        			var data = [];
	        			
	        			// Get the thresholds        			
	          			var configLowThreshold = parseFloat(config['kpi_groups']['group'][index]['axis_list']['axis_trend']['thresholds']['low_threshold']);
	      				var configHighThreshold = parseFloat(config['kpi_groups']['group'][index]['axis_list']['axis_trend']['thresholds']['high_threshold']);
	        			
	        			if (config['kpi_groups']['group'][index]['kpis']['kpi_trend']['function'] == 'failure') {
	      					var warning = configLowThreshold;
	  						var alert = configHighThreshold;
	      				} else {
	      					var warning = configHighThreshold;
	  						var alert = configLowThreshold
	      				}
	
	        			var trendTitle = 'KPI';
	        			var data = new Array();
	        			var dataMin = null;
	        			var dataMax = null;
	        			
	        			//limit time in trend to number of period
	        			var maxperiod=config['units_number'];
	        			
	        			var cptcurrentperiod=0;
	        			var currentperiod=me.dataTrend.values.data[0][0];
	        			
	        			for (var i = 0; i < me.dataTrend.values.data.length; i++) {
	        				
	        				//check if current data time is included in defined period for trend
		        			if(cptcurrentperiod<maxperiod){
	        					if(i+1<me.dataTrend.values.data.length){
		        					if(me.dataTrend.values.data[i+1][0]!=currentperiod){
			        					cptcurrentperiod++;
			        					currentperiod=me.dataTrend.values.data[i+1][0];
			        				}
	        					}
		        			}
		        			else{
		        				break;
		        			}

	    					var found = false;
	    					for (var t = 0; t < data.length; t++) {
	    						if (data[t].time == me.dataTrend.values.data[i][0]) {
	    							found = true;
	    							break;
	    						} 
	    					}
	    					
	    					var trendUnit='';
	        	        	if(typeof(config['kpi_groups']['group'][index]['axis_list']['axis_trend']['unit'])=='string' && config['kpi_groups']['group'][index]['axis_list']['axis_trend']['unit']!=='[object Object'){
	        	        		trendUnit=config['kpi_groups']['group'][index]['axis_list']['axis_trend']['unit'];
	        	        	}
	    					
	    					if (!found) {
	    						data[t] = new Object();
	    						data[t].time = me.dataTrend.values.data[i][0];
	    						data[t].warning = warning;
	    						data[t].alert = alert;
	    						data[t].kpi = me.dataTrend.labels[0].label;
	    						data[t].unit= trendUnit;
	    					} 
	    					var dataValue = parseFloat(me.dataTrend.values.data[i][1]);
	    					//if(me.dataTrend[i].values.data[d][1]=="")dataValue=undefined;
	    					
	    					if(isNaN(dataValue)){
	    						dataValue=undefined;
	    					}
	    					if (typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'undefined') {
	    						data[t][/*me.dataTrend[i].labels[1].na + ' ' + */me.dataTrend.values.data[i][3]] = dataValue;
	    					}else{
	    						data[t][/*me.dataTrend[i].labels[1].na + ' ' + */me.dataTrend.values.data[i][2]] = dataValue;
	    					}
	    					trendTitle = me.dataTrend.labels[0].label + (trendUnit !=='' ? ' ('+trendUnit+')':'');
	    					
	    					// Set the min and max values
		    				if(dataMin == null) dataMin = dataValue;
		    				if (dataValue < dataMin) dataMin = dataValue;
		    				
		    				if(dataMax == null) dataMax = dataValue;
		    				if (dataValue > dataMax) dataMax = dataValue;
	        			}
	        				
	        			// Sort on the date value
	        			data.sort(function(a, b) {
	        				return a.time - b.time;
	        			});

	        			// Format the date
	        			for (var i = 0; i < data.length; i++) {
	        				var date = '';
	        				
	        				if (me.trendTimeLevel == 'hour') {
		        	        	var dateT=new Date(data[i].time.substring(0, 4),data[i].time.substring(4, 6)-1,data[i].time.substring(6, 8),data[i].time.substring(8));
	        	        		date=Ext.Date.format(dateT,"Y/m/d H:s");
	        	        	} else if (me.trendTimeLevel == 'day' || me.trendTimeLevel == 'day_bh') {
		        	        	var dateT=new Date(data[i].time.substring(0, 4),data[i].time.substring(4, 6)-1,data[i].time.substring(6, 8));
	        	        		date=Ext.Date.format(dateT,"Y/m/d");
	        	        	} else if (me.trendTimeLevel == 'month'|| me.trendTimeLevel == 'month_bh') {
		        	        	//var dateT=new Date(me.dataTrend[i].values.data[i][0].substring(0, 4),me.dataTrend[i].values.data[i][0].substring(4, 6)-1);
	        	        		var dateT=new Date(data[i].time.substring(0, 4),data[i].time.substring(4, 6),data[i].time.substring(6, 8));
	        	        		date=Ext.Date.format(dateT,"m/Y");
	        	        	} else if(me.trendTimeLevel == 'week' || me.trendTimeLevel == 'week_bh'){
		        	        	var dateT=new Date(data[i].time.substring(0, 4));
	        	        		//date=Ext.Date.format(dateT,"Y") + ' W' + data[i].time.substring(0,2);
	        	        		date=Ext.Date.format(dateT,"Y") + ' W' + data[i].time.substring(4);
	        	        	} /**else if (me.trendTimeLevel == 'week_bh'){
	        	        		var year = data[i].time.substring(0,4);
								var month = data[i].time.substring(4,6);
								var day = data[i].time.substring(6,8);
            					var week = me.getWeekNumber(year+'-'+month+'-'+day);
            					week = week[1];
            					date = year+' W'+week;
	        	        		
	        	        	}**/

	        	        	data[i].time = date;
	        			}
	        			
	        			if(me.nes.labels.length == 0 ){
        					//get ne labels
							var requestData = {};
					        var groupindex=index;
					        requestData={
					        		nelist:me.nes.ids.join(','),
					        		na:config['network_elements']['network_level'],
					        		product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id'],
					        		product:'asc'
					        };
					        requestParam = requestData;

							//get selected map from map.xml file
							Ext.Ajax.request({
								url: 'proxy/ne_labels.php',
								async:false,
								params: requestParam,
					
								success: function(response) {
									// Decode the json into an array object
									if (response.responseText != '') {
										var labelsarray=Ext.decode(response.responseText);
										for(i=0;i<labelsarray.length;i++){
											me.nes.labels.push(labelsarray[i].label);
											me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
											me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
										}
									}				
								}
							});	
        				}	
	        			//check if store has at least one value to display for each ne
	        			var empty=[];
	        			//initialize tab
	        			for(var n=0;n<me.nes.labels.length;n++){
	        				empty[me.nes.labels[n]]=true;
	        			}
	        			
	        			for(d=0;d<data.length;d++){
	        				for(var n=0;n<me.nes.labels.length;n++){
	            				if(data[d][me.nes.labels[n]]!=undefined)empty[me.nes.labels[n]]=false;
	            			}
	        			}
	        			
	        			//if one serie is empty, fill it with fake data to avoid old data of this serie 
	        			for(var n=0;n<me.nes.labels.length;n++){
	        				if(empty[me.nes.labels[n]]){
	            				for(d=0;d<data.length;d++){
	            					data[d][me.nes.labels[n]]=-1000000000;
	            				}
	            			}
	        			}
	        			
	        			//if a serie in store is empty set min and max to 0-100, and load a value way above 100 it to 'hide' it
	//        			if(empty){
	//        				var fdata=[];
	//        				for(d=0;d<data.length;d++){
	//        					var nepush={};
	//                			for(var n=0;n<me.nes.ids.length;n++){
	//            					nepush[me.nes.ids[n]]=100000;
	//                			}
	//                			
	//        	        		nepush.time=data[d].time,
	//        	        		nepush.warning=data[d].warning,
	//        	        		nepush.alert=data[d].alert,
	//        	        		nepush.kpi=data[d].kpi,
	//        	        		nepush.unit=data[d].unit
	//        	        			
	//        	        		fdata.push(nepush);
	//        				}
	//        				data=fdata;
	//        			}
	        			
	        			trend.store.loadData(data);
	        			
	        			//////////////////////
	        			// Get the min and max values
	          			var chartMin = 0;
	          			var chartMax = 100;
	          			
	          			// Get the thresholds    
	          			var lowThreshold = parseFloat(config['kpi_groups']['group'][index]['axis_list']['axis_trend']['thresholds']['low_threshold']);
	      				var highThreshold = parseFloat(config['kpi_groups']['group'][index]['axis_list']['axis_trend']['thresholds']['high_threshold']);
	      				
	      				var dynamicScale = config['kpi_groups']['group'][index]['axis_list']['axis_trend']['zoom']['dynamic'] == true ||
	          				config['kpi_groups']['group'][index]['axis_list']['axis_trend']['zoom']['dynamic'] == "true";
	          			
	    	          			
	          			if (dynamicScale) {
	          				//get min and max value from data1, for all the serie, not only the last day/hour/...
		            		//left axis is always dynamic
		            		//var dataMin=trend.store.min('data1');
		            		//var dataMax=trend.store.max('data1');
		            		
	          				// Calculate the scale values
	          				var perMin = Math.ceil(dataMin / 10);
	          				var perMax = Math.ceil(dataMax / 10);
	          				
	          				var dynamicMin = Math.floor(dataMin - perMin);
	          				var dynamicMax = Math.ceil(dataMax + perMax);          				
	          				
	          				//be sure to display threshold low and high
	          				if(lowThreshold < dynamicMin) dynamicMin=  Math.floor(lowThreshold - Math.ceil(lowThreshold / 20));
	          				if(highThreshold > dynamicMax) dynamicMax=  Math.ceil(highThreshold + Math.ceil(highThreshold / 20));
	          				
	          				// If it's a rate, min is 0 and max is 100  
	          				var counterFunc = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['function'];
	          				if (counterFunc == 'failure' || counterFunc == 'success') {
	          					if (dynamicMin < 0) dynamicMin = 0;
	          					if (dynamicMax > 100) dynamicMax = 100;
	          					if (dynamicMax == 0) dynamicMax = 10;
	          				}
	          				if (counterFunc == 'other'){
	          					if (dynamicMin <= 0) dynamicMin = 0;
	    	  					if (dynamicMax == 0) dynamicMax = 10;
	          				}
	          				
	          				chartMin = dynamicMin;
	          				chartMax = dynamicMax;
	          				
	          			} else {
	          				// Get the values in the configuration for scale min and scale max
	          				var configMin = parseFloat(config['kpi_groups']['group'][index]['axis_list']['axis_trend']['zoom']['min_value']);
	          				var configMax = parseFloat(config['kpi_groups']['group'][index]['axis_list']['axis_trend']['zoom']['max_value']);
	          				
	          				// Default values are 0 -> 100
	          				if (isNaN(configMin)) configMin = 0;
	          				if (isNaN(configMax)) configMax = 100;
	          				
	          				//be sure to display threshold low and high
	          				if(lowThreshold < configMin) configMin=  Math.floor(lowThreshold - Math.ceil(lowThreshold / 20));
	          				if(highThreshold > configMax) configMax=  Math.ceil(highThreshold + Math.ceil(highThreshold / 20));
	          				
	          				// If it's a rate, min is 0 and max is 100  
	          				var counterFunc = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['function'];
	          				if (counterFunc == 'failure' || counterFunc == 'success') {
	          					if (dynamicMin < 0) dynamicMin = 0;
	          					if (dynamicMax > 100) dynamicMax = 100;
	          					if (dynamicMax == 0) dynamicMax = 10;
	          				}
	          				if (counterFunc == 'other'){
	          					if (dynamicMin <= 0) dynamicMin = 0;
	    	  					if (dynamicMax == 0) dynamicMax = 10;
	          				}
	          				
	      					chartMin = configMin;
	      					chartMax = configMax;
	          			}
		          			
	            		// Set axis min/max
	            		var axisLeft = trend.axes.get(0);
	            		
	            		//axisLeft.minimum = chartMin;
	            		axisLeft.minimum = chartMin;
	            		
	            		//axisLeft.maximum = chartMax;
	            		axisLeft.maximum = chartMax;
	            		
	            		// Set axis titles
	            		var unitLeft = config['kpi_groups']['group'][index]['axis_list']['axis_trend']['unit'];
	            		if ((unitLeft == null) || (typeof (unitLeft) != 'string')) unitLeft = '';
	            		axisLeft.title = unitLeft;
	            		
	            		//set trend title
	            		var tt = Ext.get(tabId + '_' + config['@attributes']['id'] + '_trend_title');
	        			tt.select('tspan').elements[0].innerHTML = trendTitle;
	
	        			//set trend's tile color 
	        			if(Ext.get('homepageStyle').dom.value == 'access'){
	        				tt.set({fill : '#fff'});
	        				legendColorFill = '#232D38';
	        				legendColorStroke = '#BDBDBD';
	        			}else{
	        				tt.set({fill : '#000'});
	        				legendColorFill = '#fff';
	        				legendColorStroke = 'gray';
	        			}
	        			
	        			//create legend only once
	        			if(trend.legend==false){
	        			
		        			var newlegend=trend.legend = Ext.create('Ext.ux.chart.SmartLegend', {
		    					position : 'bottom',
		    					chart : trend,
		    					rebuild : true,
		    					boxFill : legendColorFill,
		    					boxStroke : legendColorStroke,
		    					boxStrokeWidth : 2,
		    					clickable:true,
		    					showline:true,
		    					labelFont: '12px Verdana',
		    					style: Ext.get('homepageStyle').dom.value
		    				});
		        			
		        			trend.legend.redraw();
	        			}

	        			trend.redraw();
	        		}
        		}/**else{
        			var trendTitleCmp = Ext.get(tabId + '_' + config['@attributes']['id'] + '_trend_title');
        			var maptitle =  Ext.get(tabId + '_' + config['@attributes']['id'] + '_map_title');
        			maptitle.select('tspan').elements[0].innerHTML =  'No data found';
        			trend.redraw();
        		}**/
            },
            failure: function (formPanel, action) {
        			var trendTitleCmp = Ext.get(tabId + '_' + config['@attributes']['id'] + '_trend_title');
        			trendTitleCmp.select('tspan').elements[0].innerHTML =  'No data for this kpi couple';
        			trend.redraw();
           }
        });
	},
	
	loadDonut: function(config,index) {
		var me = this;
		//in only one kpi group
		if(typeof(config['kpi_groups']['group']).length=='undefined'){
			var saveconfig=config['kpi_groups']['group'];
			config['kpi_groups']['group']=new Array(1);
			config['kpi_groups']['group'][0]=new Array(1);
			config['kpi_groups']['group'][0]=saveconfig;
		}
		
		// Get the chart
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		var donut = Ext.getCmp(tabId + '_' + config['@attributes']['id'] + '_donut');
		me.dataDonut = new Array();

		// Time parameters
    	var timeUnit = me.donutTimeLevel;
        var timeData = {};
        timeData.id = timeUnit;
        timeData.type = "ta";
        timeData.order = "Descending"; // get the last value available
            
        var neSelectData = {};
        neSelectData.id=config['network_elements']['network_level'];
        neSelectData.type="na";
        //neSelectData.order = "Ascending";
        
        if(typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'undefined' && typeof(config['network_elements']['network_element'][0]['ne_id2'])!= 'object'){
        	var neSelectData2 = {};
	        neSelectData2.id=config['network_elements']['network_level2'];
	        neSelectData2.type="na_axe3";
	        //neSelectData.order = "Ascending";
        }
        
        // Counter parameters
        var rawKpiId = config['kpi_groups']['group'][index]['kpis']['kpi_donut']['kpi_id'];
        var rawKpiProductId = config['kpi_groups']['group'][index]['kpis']['kpi_donut']['product_id'];
        var rawKpiType = config['kpi_groups']['group'][index]['kpis']['kpi_donut']['type'];
        var rawKpiData = {};
        rawKpiData.id = rawKpiId;        
        rawKpiData.productId = rawKpiProductId;
        rawKpiData.type = rawKpiType;       
 		if(typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'undefined' && typeof(config['network_elements']['network_element'][0]['ne_id2'])!= 'object'){    
 			var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2);	
 		}else{
 			var selectData = new Array(timeData, rawKpiData,neSelectData);	
 		}
        
        
        
        
        var requestData = {};
        requestData.method = 'getDataAndLabels';
        requestData.parameters = {};
        requestData.parameters.select = {};
        requestData.parameters.select.data = selectData;
        requestData.parameters.filters = {};
		requestData.parameters.formatbh = true;
		
        // Only get the last value in the database
		var limitFilter = {};
		limitFilter.id = "maxfilter"; 
		limitFilter.type = "sys";
		limitFilter.value = config['network_elements']['network_element'].length;

		var neData={};
		neData.type = 'na';
        neData.operator = 'in';
        neData.id=config['network_elements']['network_level'];
        neData.value=me.nes.ids.join(',');
		
        
        var neId2 = null;
        if (typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'undefined' && typeof(config['network_elements']['network_element'][0]['ne_id2'])!= 'object') {
	        var neData2 = {}; 
	        var neId2 = config['network_elements']['network_level2'];
	        neData2.type = 'na_axe3';
	        neData2.operator = 'in';
	        neData2.id = neId2;
	        neData2.value =me.nes.ids2.join(',');
	        
        }

        if (neId2 != null) {
        	 var filtersData = new Array(neData,neData2, limitFilter);
        } else {
	         var filtersData = new Array(neData, limitFilter);
	  	}

       // var filtersData = new Array(neData, limitFilter);	        
        requestData.parameters.filters.data = filtersData;
        
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
        				donut.hide();
        			}else{
        				error = false;
        				donut.show();
        			}
        		} catch (err) {
        			// The json is invalid
        			error = true;
        			
        		}
        		if(error == false){
	        		me.dataDonut=result;
	    			// Create the data store
	    			var data = [];
	    			var dataT=[];
	    			var donutTitle = 'KPI';
	
	    			var maxtimevalue=me.dataDonut.values.data[0][0];
	    			
	    			var kpidonut='';
	    			var datedonut='';
	    			for (var i = 0; i < me.dataDonut.values.data.length; i++) {
	    				//limit to last one time unit, the max one
	    				if(me.dataDonut.values.data[i][0]!=maxtimevalue)continue;
	    				
	    				var date = '';
	    	        	if (me.donutTimeLevel == 'hour') {
	        				var dateT=new Date(me.dataDonut.values.data[i][0].substring(0, 4),me.dataDonut.values.data[i][0].substring(4, 6)-1,me.dataDonut.values.data[i][0].substring(6, 8),me.dataDonut.values.data[i][0].substring(8));
	    	        		date=Ext.Date.format(dateT,"Y/m/d H:s");
	    	        	} else if (me.donutTimeLevel == 'day' || me.donutTimeLevel == 'day_bh') {
	        				var dateT=new Date(me.dataDonut.values.data[i][0].substring(0, 4),me.dataDonut.values.data[i][0].substring(4, 6)-1,me.dataDonut.values.data[i][0].substring(6, 8));
	    	        		date=Ext.Date.format(dateT,"Y/m/d");
	    	        	} else if (me.donutTimeLevel == 'month') {
	        				var dateT=new Date(me.dataDonut.values.data[i][0].substring(0, 4),me.dataDonut.values.data[i][0].substring(4, 6)-1);
	        				date=Ext.Date.format(dateT,"m/Y");	
	    	        	} else {
	        				var dateT=new Date(me.dataDonut.values.data[i][0].substring(0, 4));
	    	        		date=Ext.Date.format(dateT,"Y") + ' W' + me.dataDonut.values.data[i][0].substring(4);
	    	        	}
	    	        	
	    	        	var donutUnit='';
	    	        	if(typeof(config['kpi_groups']['group'][index]['axis_list']['axis_donut']['unit'])=='string' && config['kpi_groups']['group'][index]['axis_list']['axis_donut']['unit']!=='[object Object'){
	    	        		donutUnit=config['kpi_groups']['group'][index]['axis_list']['axis_donut']['unit'];
	    	        	}

	    	        	donutTitle = me.dataDonut.labels[0].label + (donutUnit!='' ? ' ('+donutUnit+')' : '') +': ' + date;
	    				if (typeof(config['network_elements']['network_element'][0]['ne_id2']) != 'undefined') {
	    					dataT[me.dataDonut.values.data[i][3]]={
		    					name: me.dataDonut.values.data[i][3],
		    					data1: me.dataDonut.values.data[i][1],
		    					kpi: me.dataDonut.labels[0].label,
		    					date: date,
		    					unit: donutUnit,
	    					};
	    				}else{
	    					dataT[me.dataDonut.values.data[i][2]]={
		    					name: me.dataDonut.values.data[i][2],
		    					data1: me.dataDonut.values.data[i][1],
		    					kpi: me.dataDonut.labels[0].label,
		    					date: date,
		    					unit: donutUnit,
	    					};
	    					
	    				}

	    				kpidonut=me.dataDonut.labels[0].label;
	    				datedonut=date;
	    			}
	    			
	    			
	    			if(me.nes.labels.length == 0 ){
        					//get ne labels
							var requestData = {};
					        var groupindex=index;
					        requestData={
					        		nelist:me.nes.ids.join(','),
					        		na:config['network_elements']['network_level'],
					        		product:config['kpi_groups']['group'][groupindex]['kpis']['kpi_trend']['product_id']
					        };
					        requestParam = requestData;
					        
							//get selected map from map.xml file
							Ext.Ajax.request({
								url: 'proxy/ne_labels.php',
								async:false,
								params: requestParam,
					
								success: function(response) {
									// Decode the json into an array object
									if (response.responseText != '') {
										var labelsarray=Ext.decode(response.responseText);
										for(i=0;i<labelsarray.length;i++){
											me.nes.labels.push(labelsarray[i].label);
											me.nes.zoneslabels[labelsarray[i].label]=me.nes.zones[labelsarray[i].code];
											me.nes.idsindex[labelsarray[i].label]=labelsarray[i].code;
										}
									}				
								}
							});	
        			}
        			
	    			//TODO sort function for ne array
	    			for(var p=0;p<me.nes.labels.length;p++){
	    				if(dataT[me.nes.labels[p]]==undefined){
	    					dataT[me.nes.labels[p]]={
	    	    					name: me.nes.labels[p],
	    	    					data1: undefined,
	    	    					kpi: kpidonut,
	    	    					date: datedonut,
	    	    					unit: donutUnit
	    	    				};
	    					
	    				}
	    				
	    				data.push(dataT[me.nes.labels[p]]);
	    			}
	    			var dt = Ext.get(tabId + '_' + config['@attributes']['id'] + '_donut_title');	        			
	    			dt.select('tspan').elements[0].innerHTML = donutTitle;
	    			//set donut's tile color 
					if(Ext.get('homepageStyle').dom.value == 'access'){
        				dt.set({fill : '#fff'});
        				legendColorFill = '#232D38';
        				legendColorStroke = '#BDBDBD';
        			}else{
        				dt.set({fill : '#000'});
        				legendColorFill = '#fff';
        				legendColorStroke = 'gray';
        			}
	        			
	    			donut.store.loadData(data);	
	        		
	    			//create legend only once
	    			if(donut.legend==false){
	    				var newlegend=donut.legend = Ext.create('Ext.ux.chart.SmartLegend', {
	    					position : 'right',
	    					chart : donut,
	    					rebuild : true,
	    					boxFill : legendColorFill,
	    					boxStroke : legendColorStroke,
	    					boxStrokeWidth : 2,	
	    					itemSpacing : 5,
	    					//opacity: 0.4,
	    					clickable:true,
	    					labelFont: '12px Verdana',
	    					style : Ext.get('homepageStyle').dom.value
	    				});
	    				
	    				donut.legend.redraw();
	        			
	    			}
	    			donut.redraw();
	            }else{
	    			var donutTitleCmp = Ext.get(tabId + '_' + config['@attributes']['id'] + '_donut_title');
	    			donutTitleCmp.select('tspan').elements[0].innerHTML =  'No data for this kpi couple';
	    			//donut.redraw();	
	            }
            },
            failure: function (formPanel, action) {
    			var donutTitleCmp = Ext.get(tabId + '_' + config['@attributes']['id'] + '_donut_title');
    			donutTitleCmp.select('tspan').elements[0].innerHTML =  'No data for this kpi couple';
    			//donut.redraw();
            }
        });	
	},
	
	
	inArray: function (array, val) {
	    var l = array.length;
	    for(var i = 0; i < l; i++) {
	        if(array[i] == val) {
	            return i;
	        }
	    }
	    return false;
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
	
   empty: function(o) {
    for(var i in o) 
      if(o.hasOwnProperty(i))
        return false;
 
      return true;
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
	    weekNo = ("0" +  weekNo ).slice(-2);	   
	    // Return array of year and week number
	    return [d.getFullYear(), weekNo];
	},
	
	measureBoxSize: function(pText,mode) {
	    if(mode == 'fullscreen'){
	    	var charLength = 12;
	    }else{
	    	var charLength = 10;
	    }
	    
	    var NumberOfChar = pText.length;
	    var totallength = charLength*NumberOfChar;
	    return totallength;

	}
});
