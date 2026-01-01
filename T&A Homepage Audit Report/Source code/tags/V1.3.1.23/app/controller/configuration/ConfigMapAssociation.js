Ext.define('homepage.controller.configuration.ConfigMapAssociation', {
	extend: 'Ext.app.Controller',
	//alias : 'widget.configmapassociation',
	views: [
		'configuration.ConfigMapAssociation'
	],
	modified : false,
		// Initialize the event handlers
    init: function() {
    	var me=this;
    	Ext.Loader.setPath('Ext.ux', 'extjs/src/ux/');
    	Ext.require([
    	    'Ext.selection.CellModel',
		    'Ext.grid.*',
		    'Ext.data.*',
		    'Ext.util.*',
		    'Ext.state.*',
		    'Ext.form.*',
    		'Ext.ux.CheckColumn'
		]),
        this.control({
             'button[action=selectNeLevel]': {
                click: this.selectNeLevel
            },'button[action=cancelNeLevel]': {
                click: this.cancelNeLevel
            },'button[action=selectNe]': {
                click: this.selectNe
            },'button[action=cancelNetworkElementLevel]': {
            	click: this.cancelNetworkElementLevel
        	},'button[action=cancelNetworkElement]': {
            	click: this.cancelNetworkElement
   			},'button[action=validateNetworkMap]': {
                click: this.validateNetworkMap
            },'button[action=associate]': {
                click: this.associate
            },'button[action=selectCounterTrendRoaming]': {
                click: this.selectCounterTrendRoaming
            },'button[action=validateCounter]': {
                click: this.validateCounter
            },'button[action=trendCounterCancelRoaming]': {
                click: this.trendCounterCancelRoaming
            },'button[action=validateNetwork]': {
                click: this.validateNetwork
            }
            
            /**
             'button[action=selectNeLevel]': {
                click: this.cancelNeLevel
            },
             'button[action=selectNe]': {
                click: this.selectNe
            },
             'button[action=selectNeLevel]': {
                click: this.cancelNe
            }
            **/
        });
        
    },
    selectCounterTrendRoaming: function(button) {
		var win = Ext.getCmp('counterWindow');
        if (!win) {
        	win = Ext.create('widget.counterwindow');
    	} else {
    		win.resetSearch();
    	}
        
        win.graph = 'map';
        
        var config = button.up('configmapassociation');
        win.chartId = config.id;

        var counterProductLabel = Ext.getCmp('trendCounterProductLabel_configChart_roaming').getRawValue();
    	var counterLabel = Ext.getCmp('trendCounterLabel_configChart').getRawValue();
        
    	var winTitle = '';
    	if (counterProductLabel != '' && counterLabel != '' && !Ext.getCmp('counterGrid_configChart_roaming').isVisible()) {
    		winTitle = counterProductLabel + ' - ' + counterLabel;
    	}
    	
        win.showWindow(winTitle);
    },
    
    validateCounter: function(button) {
    	var win = Ext.getCmp('counterWindow');
    	// Hide the window
		win.hide();
    	// Get the new counter
    	var counterId = win.counterId;
    	var counterType = win.counterType;
    	var counterProductId = win.counterProductId;
    	var counterProductLabel = win.counterProductName;
    	var counterLabel = win.counterName;
    	
    	if (counterId != null) {
			if (win.graph == 'map') {
    			// Update the configuration panel
            	Ext.getCmp('trendCounterId_configChart_roaming').setValue(counterId);
            	Ext.getCmp('trendCounterType_configChart_roaming').setValue(counterType);
            	Ext.getCmp('trendCounterProductId_configChart_roaming').setValue(counterProductId);          	
            	Ext.getCmp('trendCounterProductLabel_configChart_roaming').setValue(counterProductLabel);
            	Ext.getCmp('trendCounterLabel_configChart_roaming').setValue(counterLabel);
            	Ext.getCmp('new_kpi').setValue(true);

    	    	var counterButton = Ext.getCmp('trendCounterButton_configChart_roaming');
    	    	
				
		    	//Roaming parameters
		    	axis = 1;
				if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 1){
					axis = 2;
				}
		    	var networkaxisnumber=axis;
		    	var roamingnetworklevel = Ext.getCmp('neLevelId_configMap').getValue();
				var roamingnetworklevel2 = axis == 2 ? Ext.getCmp('neLevelId2_configMap').getValue() : "" ;
		    	var roamingneid = axis == 2 ?  Ext.getCmp('neId_configChart').getValue() : "";
		    	var roamingneid2 = "";
		    	
		    	/**
				//be sure to have kpis selected
		    	if(Ext.getCmp('mapMode').getValue().modeselection=="2" || Ext.getCmp('mapMode').getValue().modeselection=="3"){
		    		if(kpiIdTrend==""){
		    			Ext.Msg.alert('Warning', 'Please choose a kpi before clicking the add button.');
		        		return false;
		    		}
		    	}
		    	**/
		    	// Create a model instance
		        var r = Ext.create('MapModel', {
		        	trendkpilabel: counterLabel,
		        	trendkpiid: counterId,
		        	trendkpiproductid: counterProductId,
		        	trendproductlabel: counterProductLabel,
		        	typekpi: counterType,
		        	/**
		        	networkaxisnumber: "",
					roamingnetworklevel:"",
					roamingnetworklevel2:"",
					roamingneid:"",
					roamingneid2:""
					**/
		            });
		  
		            store=Ext.getCmp('mapKpiGrid_configChart_roaming').getStore();
		        
			        var storeindex = store.getCount();   
			       // mapStore.insert(storeindex, r);
			                          
			        store.insert(storeindex, r);
			        //position onto the new record
			        var sm = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel();
			        if (store.getCount() > 0) {
			            sm.select(store.getCount()-1);
			        }
			        
			        //reset kpi selectors
					Ext.getCmp('trendCounterId_configChart_roaming').setValue('');
					//Ext.getCmp('trendCounterId_configChart').originalValue('');
			    	Ext.getCmp('trendCounterType_configChart_roaming').setValue('');
			    	//Ext.getCmp('trendCounterType_configChart').originalValue('');
			    	Ext.getCmp('trendCounterProductId_configChart_roaming').setValue('');
			    	//Ext.getCmp('trendCounterProductId_configChart').originalValue('');  
			    	Ext.getCmp('trendCounterProductLabel_configChart_roaming').setValue('');
			    	//Ext.getCmp('trendCounterProductLabel_configChart').originalValue('');
			    	Ext.getCmp('trendCounterLabel_configChart_roaming').setValue('');
			    	//Ext.getCmp('trendCounterLabel_configChart').originalValue('');
			    	Ext.getCmp('neLevelId_configMap').setValue('');
                    Ext.getCmp('neLevelId2_configMap').setValue('');
                    Ext.getCmp('parentLevelSelected_configMap').setValue(1);
			    
			    	// Change the button aspect
			    	var counterButton = Ext.getCmp('trendCounterButton_configChart_roaming');
					counterButton.removeCls('x-button-counter-select-ok');
					counterButton.addCls('x-button-counter-select');
					counterButton.setTooltip(''); 
					var addKpiButton = Ext.getCmp('trendCounterButton_configChart_roaming');
					var cancelKpiButton = Ext.getCmp('trendCounterCancelButton_configChart_roaming');
					addKpiButton.setDisabled(true);
					cancelKpiButton.setDisabled(true);
					//Ext.getCmp('mapField_configChart_roaming').setTitle('Indicator: No indicator selected');
    		}else if(win.graph == 'trend'){
    			// Update the configuration panel
            	Ext.getCmp('trendCounterId_configChart').setValue(counterId);
            	Ext.getCmp('trendCounterType_configChart').setValue(counterType);
            	Ext.getCmp('trendCounterProductId_configChart').setValue(counterProductId);          	
            	Ext.getCmp('trendCounterProductLabel_configChart').setValue(counterProductLabel);
            	Ext.getCmp('trendCounterLabel_configChart').setValue(counterLabel);

    	    	var counterButton = Ext.getCmp('trendCounterButton_configChart');
    		
    		}else if (win.graph == 'gauge') { 
    			// Update the configuration panel
    			if (Ext.getCmp('counterGrid_configChart').isVisible()) {
    				// Several counters, displayed beneath
    				Ext.getCmp('counterId_configChart').setValue(Ext.getCmp('counterId_configChart').getValue() + '||' + counterId);
                	Ext.getCmp('counterType_configChart').setValue(Ext.getCmp('counterType_configChart').getValue() + '||' + counterType);
                	Ext.getCmp('counterProductId_configChart').setValue(Ext.getCmp('counterProductId_configChart').getValue() + '||' + counterProductId);
                	Ext.getCmp('counterProductLabel_configChart').setValue(Ext.getCmp('counterProductLabel_configChart').getValue() + '||' + counterProductLabel);
                	Ext.getCmp('counterLabel_configChart').setValue(Ext.getCmp('counterLabel_configChart').getValue() + '||' + counterLabel);
    			
                	// Create a model instance
                    var n = Ext.create('CounterModel', {
                        label: counterLabel
                    });
                    Ext.getCmp('counterGrid_configChart').store.insert(Ext.getCmp('counterGrid_configChart').store.getCount(), n);
    			} else {
    				// An only counter, displayed in the tooltip
                	Ext.getCmp('counterId_configChart').setValue(counterId);
                	Ext.getCmp('counterType_configChart').setValue(counterType);
                	Ext.getCmp('counterProductId_configChart').setValue(counterProductId);
                	Ext.getCmp('counterProductLabel_configChart').setValue(counterProductLabel);
                	Ext.getCmp('counterLabel_configChart').setValue(counterLabel);
    			}
    			
    	    	var counterButton = Ext.getCmp('counterButton_configChart');
    		}
    		
        	// Change the button aspect
			//counterButton.addCls('x-button-counter-select-ok');
			//counterButton.removeCls('x-button-counter-select');
			if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
				counterButton.setTooltip(win.counterProductName + ' - ' + win.counterName);
			}
			else{
				counterButton.setTooltip('');

			}	
    	}
    },   
    trendCounterCancelRoaming: function() {		
		// Update the configuration panel
		Ext.getCmp('trendCounterId_configChart_roaming').setValue('');
    	Ext.getCmp('trendCounterType_configChart_roaming').setValue('');
    	Ext.getCmp('trendCounterProductId_configChart_roaming').setValue('');          	
    	Ext.getCmp('trendCounterProductLabel_configChart_roaming').setValue('');
    	Ext.getCmp('trendCounterLabel_configChart_roaming').setValue('');
    
    	// Change the button aspect
    	var counterButton = Ext.getCmp('trendCounterButton_configChart_roaming');
		counterButton.removeCls('x-button-counter-select-ok');
		counterButton.addCls('x-button-counter-select');
		counterButton.setTooltip('');    	
	},
	
    selectNeLevel: function(button) {
    	var win = Ext.getCmp('networkLevelWindow');
    	if(Ext.getCmp('networkWindow')){
    		Ext.getCmp('networkWindow').destroy();
    	}

		var axis = 1;
		selected_network_axis = 1;
		
		if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 2){
			axis = 3;
			selected_network_axis = 2;
		}

        if (!win) {
        	win = Ext.create('widget.networklevelwindow', {axis: axis});
        } else {
    		win.resetSearch(axis);
    	}
    	
    	win.selected_network_axis = selected_network_axis;
        if (button.action == 'selectNeLevel'){
        	win.roaming = true;
        }else{
        	win.roaming = false;
        }
    	
    	
        //reset filter on opening
        win.filterPanel.filterField.reset();
        
        if (axis == 3) {
        	win.axis = 3;
        	
        	var neLabel = Ext.getCmp('neLabel2_configChart').getRawValue();
        	var neLevelLabel = Ext.getCmp('neLevelLabel2_configChart').getRawValue();
        } else {	// default action: selectNetwork
        	win.axis = 1;
        	
        	var neLabel = Ext.getCmp('neLabel_configChart').getRawValue();
        	var neLevelLabel = Ext.getCmp('neLevelLabel_configChart').getRawValue();
        }
        
        var config = button.up('configmapassociation');
        win.chartId = config.id;
                
    	var winTitle = '';
    	if (neLabel != '' && neLevelLabel != '' && !Ext.getCmp('counterGrid_configChart').isVisible()) {
    		winTitle = neLevelLabel + ' - ' + neLabel;
    	}
    	
        win.showWindow(winTitle);
    },
    cancelNeLevel: function(button) {
		button.up('networklevelwindow').hide();
	},
	selectNe: function(button) {
		var win = Ext.getCmp('networkWindow');

    	if(Ext.getCmp('networkLevelWindow')){
    		Ext.getCmp('networkLevelWindow').destroy();
    	}
		
		var axis = 1;
		selected_network_axis = 2;
		
		//On cherche les ne de l'axe opposé à celui choisit
		if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 1){
			//définir l'axe séléctionné.
			selected_network_axis = 1;
			//définir l'axe sur lequel aller chercher les elements réseaux.
			axis = 3;
		}
		
        if (!win) {
        	win = Ext.create('widget.networkwindow', {axis: axis,roaming: true});
        } else {
    		win.resetSearch(axis);
    	}
	
        win.roaming = true;
        win.selected_network_axis = selected_network_axis;
        //reset filter on opening
        win.filterPanel.filterField.reset();
        
        if (axis == 3) {
        	win.axis = 3;
        	
        	var neLabel = Ext.getCmp('neLabel2_configChart').getRawValue();
        	var neLevelLabel = Ext.getCmp('neLevelLabel2_configChart').getRawValue();
        } else {	// default action: selectNetwork
        	win.axis = 1;
        	
        	var neLabel = Ext.getCmp('neLabel_configChart').getRawValue();
        	var neLevelLabel = Ext.getCmp('neLevelLabel_configChart').getRawValue();
        }
        
        var config = button.up('configmapassociation');
        win.chartId = config.id;
                
    	var winTitle = '';
    	if (neLabel != '' && neLevelLabel != '' && !Ext.getCmp('counterGrid_configChart').isVisible()) {
    		winTitle = neLevelLabel + ' - ' + neLabel;
    	}
    	
        win.showWindow(winTitle);
	},
	validateNetwork: function(button) {
		var win = Ext.getCmp('networkWindow');
    	// Hide the window
		win.hide();
    	
    	// Get the new network element
		var neId = win.neId;
		var axis = win.axis;
		var selected_network_axis = win.selected_network_axis;
		var roaming = win.roaming;

		if (neId != null) {
			if (selected_network_axis == 2) {
				// Update the configuration panel
		    	Ext.getCmp('neId_configMap').setValue(neId);
		    	Ext.getCmp('neLevelId_configMap').setValue(win.neLevelId);
		    	//Ext.getCmp('neProductId2_configMap').setValue(win.neProductId);
		    	Ext.getCmp('neLabel_configMap').setValue(win.neLabel);
		    	Ext.getCmp('neLevelLabel_configMap').setValue(win.neLevelLabel);
				
		    	var counterButton = Ext.getCmp('neSelectButton_configMapAssoction');
		    	
		    	var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
				var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
				     return (record.get('trendkpiid') == selection.data.trendkpiid);
				});
				
				record=record.items[0];
				if (record != null) {
					record.data.roamingnetworklevel = win.neLevelId;
					record.data.roamingneid = win.neId;
				}
		    	
		    	
			} else {	// default: 1st network
				// Update the configuration panel
				Ext.getCmp('neId2_configMap').setValue(neId);
		    	Ext.getCmp('neLevelId2_configMap').setValue(win.neLevelId);
		    	//Ext.getCmp('neProductId_configMap').setValue(win.neProductId);
		    	Ext.getCmp('neLabel2_configMap').setValue(win.neLabel);
		    	Ext.getCmp('neLevelLabel2_configMap').setValue(win.neLevelLabel);
		    	
				var counterButton = Ext.getCmp('neSelectButton_configMapAssoction');	
				
				var counterButton = Ext.getCmp('neSelectButton_configMapAssoction');
		    	
		    	var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
				var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
				     return (record.get('trendkpiid') == selection.data.trendkpiid);
				});
				
				record=record.items[0];
				if (record != null) {
					record.data.roamingnetworklevel2 = win.neLevelId;
					record.data.roamingneid2 = win.neId;
				}
			}
			// Change the button aspect
			counterButton.addCls('x-button-network-select-ok');
			counterButton.removeCls('x-button-network-select');
			if (Ext.getCmp('counterGrid_configChart').isVisible()) {
				counterButton.setTooltip(win.neLevelLabel + ' - ' + win.neLabel);
			} else {
				counterButton.setTooltip('');
			}
	    	
		}
	},
	cancelNe: function(button) {
		button.up('networkwindow').hide();
	},
	validateNetworkMap: function(button) {			
		var win = Ext.getCmp('networkLevelWindow');
    	// Hide the window
		win.hide();
    	// Get the new network element
		var neLevelId = win.neLevelId;
		var axis = win.axis;
		var roaming = win.roaming;  
		var selected_network_axis = win.selected_network_axis;
		if (neLevelId != null) {
			var counterButton = Ext.getCmp('neLevelSelectButton_configMapAssoction');
			if (selected_network_axis == 2) {
				// Update the configuration panel
		    	Ext.getCmp('neLevelId2_configMap').setValue(win.neLevelId);
				Ext.getCmp('neLevelLabel2_configMap').setValue(win.neLevelLabel);
		    	Ext.getCmp('neProductId_configMap').setValue(win.neProductId);
		    	
		    	var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
				var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
				     return (record.get('trendkpiid') == selection.data.trendkpiid);
				});
				
				record=record.items[0];
				if (record != null) {
					record.data.roamingnetworklevel2 = win.neLevelId;
					record.data.trendkpiproductid = win.neProductId;
					record.data.networkaxisnumber = Ext.getCmp('parentLevelSelected_configMap').getValue() == "1" ? "1" : "2";
				}
		    	
			} else {	// default: 1st network
		    	Ext.getCmp('neLevelId_configMap').setValue(win.neLevelId);
		    	Ext.getCmp('neLevelLabel_configMap').setValue(win.neLevelLabel);
		    	Ext.getCmp('neProductId_configMap').setValue(win.neProductId);
		    	
		    	var selection = Ext.getCmp('mapKpiGrid_configChart_roaming').getSelectionModel().getSelection()[0];
				var record=Ext.getCmp('mapKpiGrid_configChart_roaming').store.queryBy(function(record,id){
				     return (record.get('trendkpiid') == selection.data.trendkpiid);
				});
				
				record=record.items[0];
				if (record != null) {
					record.data.roamingnetworklevel = win.neLevelId;
					record.data.trendkpiproductid = win.neProductId;
					record.data.networkaxisnumber = Ext.getCmp('parentLevelSelected_configMap').getValue() == "1" ? "1" : "2";
				}
		    	
    		} 
    		// Change the button aspect
			counterButton.addCls('x-button-network-select-ok');
			counterButton.removeCls('x-button-network-select');
			
			/**
			if (Ext.getCmp('counterGrid_configMap').isVisible()) {
				counterButton.setTooltip(win.neLevelLabel + ' - ' + win.neLabel);
			} else {
				counterButton.setTooltip('');
			}
			**/
		}
	},
		cancelNetworkElementLevel: function() {
			var axis = 1;
			if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 2){
				axis = 3;
			}
			
			if(axis == 1){
		    	Ext.getCmp('neLevelId_configMap').setValue('');
		    	Ext.getCmp('neLevelLabel_configMap').setValue('');
		    	Ext.getCmp('neProductId_configMap').setValue('');
			}else{
				Ext.getCmp('neLevelId2_configMap').setValue('');
		    	Ext.getCmp('neLevelLabel2_configMap').setValue('');
		    	Ext.getCmp('neProductId_configMap').setValue('');
		    	
			}
	    
	    	// Change the button aspect
	    	var counterButton = Ext.getCmp('neLevelSelectButton_configMapAssoction');
	    	
	    	
			counterButton.removeCls('x-button-network-select-ok');
			counterButton.addCls('x-button-network-select');
			counterButton.setTooltip('');    	
		
	},
		cancelNetworkElement: function() {
			var axis = 1;
			if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 1){
				axis = 3;
			}
			if(axis = 1){
				// Update the configuration panel
		    	Ext.getCmp('neId_configChart').setValue('');
		    	Ext.getCmp('neLevelId_configChart').setValue('');
		    	Ext.getCmp('neProductId_configChart').setValue('');
		    	Ext.getCmp('neLabel_configChart').setValue('');
		    	Ext.getCmp('neLevelLabel_configChart').setValue('');
			}else{
				Ext.getCmp('neId2_configChart').setValue('');
		    	Ext.getCmp('neLevelId2_configChart').setValue('');
		    	Ext.getCmp('neProductId2_configChart').setValue('');
		    	Ext.getCmp('neLabel2_configChart').setValue('');
		    	Ext.getCmp('neLevelLabel2_configChart').setValue('');
			}
	    	// Change the button aspect
	    	var counterButton = Ext.getCmp('neSelectButton_configMapAssoction');
	    	
			counterButton.removeCls('x-button-network-select-ok');
			counterButton.addCls('x-button-network-select');
			counterButton.setTooltip('');    	
		
	},

	associate: function() {
			var me = this;
			var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
			Ext.MessageBox.show({
				msg: 'Association, please wait...',
				progressText: 'Association...',
				width:300,
				wait:true,
				waitConfig: {interval:170},
				icon:'ext-mb-download',
				animEl: 'samplebutton'
			});
			/**
			var nes={};
	        nes.ids=new Array();
			//get network elements
			//loop through all ne
		
			
			var limitFilter = {}
			limitFilter.id = 'maxfilter'; 
			limitFilter.type = 'sys';
			
			if(fullscreen){
				limitFilter.value = nes.ids.length;
			}else{
				limitFilter.value = config['units_number']*nes.ids.length;
			}
			**/
			var neLevelAxe1 = Ext.getCmp('neLevelId_configMap').getValue();
			var neLevelAxe2 = Ext.getCmp('neLevelId2_configMap').getValue();
			
			//on réucpère le paramètre qui permet de definir sur quel axe on cherche le niveau reseau du ne 1
			var axis = 1;
			if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 2){
				axis = 3;
			}

			//Case of a product with no 3rd axe
			if(axis == 1 && neLevelAxe2 == ""){
				
				var neLevelAxe2 = "";
				
				//We initiate the search option field to get the children in the selected axe
				var searchOptions = {
					text: null,			// Text field value
					products: []
				};


				var product = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.trendkpiproductid ;
				var productItem = {
					id: product,
					na: neLevelAxe1,
					axe: ''
				}
				
				searchOptions.products.push(productItem);
		        searchOptions = Ext.encode(searchOptions);
	
		        var idArray = new Array();
	
				Ext.Ajax.request({
		        	url: 'proxy/ne_listhtml.php',
		        	params: {
	    				roaming: true,
	    				filterOptions: searchOptions
	          		},
		
		            success: function (response) {
	        			var result = Ext.decode(response.responseText).data;
					
					    Ext.each(result, function(value) {
					      Ext.each(value.parent_id , function(k,v){
						        idArray.push(k);
						    });
						 }); 
						    var neIdAxe1 = idArray.join(',');
					    	
					    	
					    	// Network Agregation
							var neData = {}; 
					        neData.type = 'na';
					        neData.operator = 'in';
					        neData.id= neLevelAxe1;
					        neData.value=neIdAxe1;
					       	
				           	var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
				           	var fullmapTimeLevel = Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').getValue();
					        // Time parameters
					        var timeData = {};
					        timeData.id = fullmapTimeLevel;
					        timeData.type = "ta";
					        timeData.order = "Descending"; // get the last value available
					        
					        //NE parameters
					        var neSelectData = {};
					       	
				       		neSelectData.id=neLevelAxe1;
				       		neSelectData.type="na";
					        neSelectData.order = "Ascending";
					        
		            	
					        var rawKpiId = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.trendkpiid ;
		        			var rawKpiProductId = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.trendkpiproductid ;
		       				var rawKpiType = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.typekpi ;
					        var rawKpiData = {};
					        rawKpiData.id = rawKpiId;        
					        rawKpiData.productId = rawKpiProductId;
					        rawKpiData.type = rawKpiType;       
					        
					        var selectedValueMode = Ext.getCmp("displayedValueMode_configMap").getValue();
					       
					        var selectData = new Array(timeData, rawKpiData,neSelectData);
					        
					        var requestData = {};
					        requestData.method = 'getDataAndLabels';
					        requestData.parameters = {};
					        requestData.parameters.mapid = true;
					        //requestData.parameters.roaming = true;
					        requestData.parameters.select = {};
					        requestData.parameters.select.data = selectData;
					        requestData.parameters.filters = {};
					       	
					        // Only get the last value in the database	
							limitFilter = {};
							limitFilter.id = 'maxfilter'; 
							limitFilter.type = 'sys';
							//limitFilter.date = '20130409';
							limitFilter.value = 2000;
					    
				        	var filtersData = new Array(neData, limitFilter);
				        	
					        
					        requestData.parameters.filters.data = filtersData;
					        
					        var requestParam = {};
					        requestParam.data = Ext.encode(requestData);
							
							
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
					        				Ext.MessageBox.hide();
					        				Ext.Msg.alert('Info','Association KO');
					        			}else{
					        				Ext.MessageBox.hide();
					        				Ext.Msg.alert('Info','Association done');
					        			}
					        		} catch (err) {
					        			// The json is invalid
					        			Ext.MessageBox.hide();
					        			Ext.Msg.alert('Info','Association KO');
					        			error = true;
					        			
					        		}
					        		if(error == false){
					        			var associationStore = Ext.getStore('associationStoreMap');
					        			associationStore.loadData(result.values.data);
		
					        		}
					            }
							});	
						    
						    
					    } 
		           	});	
			}else{
			//Si axis = 1 alors network level du 1er axe est celui de configmap
			if(axis == 1){
				var neLevelAxe1 = Ext.getCmp('neLevelId_configMap').getValue();
				var neLevelAxe2 = Ext.getCmp('neLevelId2_configChart').getValue();
				var neIdAxe2 = Ext.getCmp('neId2_configChart').getValue();
				//on remplie les hiddenfield map pour pouvoir les récupérer dans le main panel
				Ext.getCmp('neLevelId2_configMap').setValue(neLevelAxe2);
				Ext.getCmp('neId2_configMap').setValue(neIdAxe2);
				
			}else{
				var neLevelAxe1 = Ext.getCmp('neLevelId_configChart').getValue();
				var neLevelAxe2 = Ext.getCmp('neLevelId2_configMap').getValue();
				var neIdAxe1 = Ext.getCmp('neId_configChart').getValue();
				//on remplie les hiddenfield map pour pouvoir les récupérer dans le main panel
				Ext.getCmp('neLevelId_configMap').setValue(neLevelAxe1);
				Ext.getCmp('neId_configMap').setValue(neIdAxe1);
				
			}
			//on cherche les enfant de operator
			// Search field
			var searchOptions = {
				text: null,			// Text field value
				products: []
			};
			/**
			var productId = Ext.getCmp('neProductId_configMap').getValue();
			var product = new Array(productId);
			**/
			//var product = Ext.getCmp('neProductId_configMap').getValue();
			var product = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.trendkpiproductid ;
			//Si on cherche le niveau reseau sur l'axe 1, alors les elemen reseau recherché seront aussi sur l'axe1
			if(axis == 1){
				//on cherche les enfant de operator
				var productItem = {
					id: product,
					na: neLevelAxe1,
					axe: ''
				}
			}else{
				
				var productItem = {
					id: product,
					na: neLevelAxe2,
					axe: 3
				}
			}
			searchOptions.products.push(productItem);
	        searchOptions = Ext.encode(searchOptions);

	        var idArray = new Array();
			{
				Ext.Ajax.request({
		        	url: 'proxy/ne_listhtml.php',
		        	params: {
	    				roaming: true,
	    				filterOptions: searchOptions
	          		},
		
		            success: function (response) {
		            	//console.log(response.responseText);
		            	//var result = response.responseText;
	        			//console.log(result);
	        			var result = Ext.decode(response.responseText).data;
					
					    Ext.each(result, function(value) {
					      Ext.each(value.parent_id , function(k,v){
						        idArray.push(k);
						    });
					    }); 
					    
					    if(axis == 1){
					    	var neIdAxe1 = idArray.join(',');
					    	var neId = Ext.getCmp('neId2_configChart').getValue();
					    	var neIdAxe2 = neId;
	
					    	
					    }else{
					    	var neIdAxe2 = idArray.join(',');
					    	var neId = Ext.getCmp('neId_configChart').getValue();
					    	var neIdAxe1 = neId;
					    }
					    
				    	// Network Agregation
						var neData = {}; 
				        neData.type = 'na';
				        neData.operator = 'in';
				        neData.id= neLevelAxe1;
				        neData.value=neIdAxe1;
				       	
				
				    	var neId2 = null;
				        var neData2 = {}; 
				        neData2.type = 'na_axe3';
				        neData2.operator = 'in';
				        neData2.id = neLevelAxe2;
				        neData2.value =neIdAxe2;
					    
			           	var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
			           	var fullmapTimeLevel = Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').getValue();
				        // Time parameters
				        var timeData = {};
				        timeData.id = fullmapTimeLevel;
				        timeData.type = "ta";
				        timeData.order = "Descending"; // get the last value available
				        
				        //NE parameters
				        var neSelectData = {};
				       	if(axis == 1){
				       		neSelectData.id=neLevelAxe2;
				       		neSelectData.type="na_axe3";
				       	}else{
				       		neSelectData.id=neLevelAxe1;
				       		neSelectData.type="na";
				       	}
				        
				        neSelectData.order = "Ascending";
				        
				        var neSelectData2 = {};
				       	if(axis == 1){
				       		neSelectData2.id=neLevelAxe1;
				       		neSelectData2.type="na";
				       	}else{
				       		neSelectData2.id=neLevelAxe2;
				       		neSelectData2.type="na_axe3";
				       	}
				        neSelectData2.order = "Ascending";
	            	
				        var rawKpiId = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.trendkpiid ;
	        			var rawKpiProductId = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.trendkpiproductid ;
	       				var rawKpiType = Ext.getCmp('mapKpiGrid_configChart').getStore().data.items[0].data.typekpi ;
				        var rawKpiData = {};
				        rawKpiData.id = rawKpiId;        
				        rawKpiData.productId = rawKpiProductId;
				        rawKpiData.type = rawKpiType;       
				        
				        var selectedValueMode = Ext.getCmp("displayedValueMode_configMap").getValue();
				       
				        var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2);
				        
				        var requestData = {};
				        requestData.method = 'getDataAndLabels';
				        requestData.parameters = {};
				        requestData.parameters.roaming = true;
				        requestData.parameters.select = {};
				        requestData.parameters.select.data = selectData;
				        requestData.parameters.filters = {};
				       	requestData.parameters.roaming = true;
				        // Only get the last value in the database	
						limitFilter = {};
						limitFilter.id = 'maxfilter'; 
						limitFilter.type = 'sys';
						//limitFilter.date = '20130409';
						limitFilter.value = 2000;
				    
			        	if(axis == 1){
			        		var filtersData = new Array(neData, limitFilter);
			        	}else{
			        		var filtersData = new Array(neData,neData2, limitFilter); 
			        	}
				        
				        requestData.parameters.filters.data = filtersData;
				        
				        var requestParam = {};
				        requestParam.data = Ext.encode(requestData);
						
						
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
				        				Ext.Msg.alert('Info','Association KO');
				        			}else{
				        				Ext.MessageBox.hide();
				        				Ext.Msg.alert('Info','Association done');
				        			}
				        		} catch (err) {
				        			// The json is invalid 
				        			error = true;
				        			
				        		}
				        		if(error == false){
				        			var associationStore = Ext.getStore('associationStoreMap');
				        			associationStore.loadData(result.values.data);
	
				        		}
				            }
						});	
	
		            }
		            
				});	
			}
		}
	}
});