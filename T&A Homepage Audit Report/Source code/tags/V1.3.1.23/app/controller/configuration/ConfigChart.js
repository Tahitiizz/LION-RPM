Ext.define('homepage.controller.configuration.ConfigChart', {
	extend: 'Ext.app.Controller',

	views: [
		'configuration.ConfigChart'
	],

	// Initialize the event handlers
    init: function() {
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
            'button[action=selectCounter]': {
                click: this.selectCounter
            },
            
            'button[action=selectCounterTrend]': {
                click: this.selectCounterTrend
            },
            
            'button[action=validateCounter]': {
                click: this.validateCounter
            },

            'button[action=cancelCounter]': {
                click: this.cancelCounter
            },
            
            'button[action=selectNetwork]': {
                click: this.selectNetwork
            },
            
            'button[action=selectNetwork2]': {
                click: this.selectNetwork
            },
            
            'button[action=validateNetwork]': {
                click: this.validateNetwork
            },

            'button[action=cancelNetwork]': {
                click: this.cancelNetwork
            },
            
            'button[action=neCancel]': {
                click: this.neCancel
            },
            
            'button[action=neCancel2]': {
                click: this.neCancel2
            },
            
            'button[action=counterCancel]': {
                click: this.counterCancel
            },
            
            'button[action=trendCounterCancel]': {
                click: this.trendCounterCancel
            },
            
            'button[action=addAlarm]': {
                click: this.addAlarm
            },
            'button[action=addGraphType]': {
                click: this.addGraphType
            },
             'button[action=addAlarm_ar]': {
                click: this.addAlarm_ar
            }
        });
    },

	selectCounter: function(button) {
		var win = Ext.getCmp('counterWindow');
        if (!win) {
        	win = Ext.create('widget.counterwindow');
    	} else {
    		win.resetSearch();
    	}
        
        win.graph = 'gauge';
        
        var config = button.up('configchart');
        win.chartId = config.id;

        var counterProductLabel = Ext.getCmp('counterProductLabel_configChart').getRawValue();
    	var counterLabel = Ext.getCmp('counterLabel_configChart').getRawValue();
        
    	var winTitle = '';
    	if (counterProductLabel != '' && counterLabel != '' && !Ext.getCmp('counterGrid_configChart').isVisible()) {
    		winTitle = counterProductLabel + ' - ' + counterLabel;
    	}
    	
        win.showWindow(winTitle);
    },
    
	selectCounterTrend: function(button) {
		var win = Ext.getCmp('counterWindow');
        if (!win) {
        	win = Ext.create('widget.counterwindow');
    	} else {
    		win.resetSearch();
    	}
        
        win.graph = 'trend';
        
        var config = button.up('configchart');
        win.chartId = config.id;

        var counterProductLabel = Ext.getCmp('trendCounterProductLabel_configChart').getRawValue();
    	var counterLabel = Ext.getCmp('trendCounterLabel_configChart').getRawValue();
        
    	var winTitle = '';
    	if (counterProductLabel != '' && counterLabel != '' && !Ext.getCmp('counterGrid_configChart').isVisible()) {
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
    		if (win.graph == 'gauge') { 
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
    		} else if (win.graph == 'trend') {
    			// Update the configuration panel
            	Ext.getCmp('trendCounterId_configChart').setValue(counterId);
            	Ext.getCmp('trendCounterType_configChart').setValue(counterType);
            	Ext.getCmp('trendCounterProductId_configChart').setValue(counterProductId);          	
            	Ext.getCmp('trendCounterProductLabel_configChart').setValue(counterProductLabel);
            	Ext.getCmp('trendCounterLabel_configChart').setValue(counterLabel);

    	    	var counterButton = Ext.getCmp('trendCounterButton_configChart');
    		}
    		
        	// Change the button aspect
			counterButton.addCls('x-button-counter-select-ok');
			counterButton.removeCls('x-button-counter-select');
			if (Ext.getCmp('counterGrid_configChart').isVisible()) {
				counterButton.setTooltip(win.counterProductName + ' - ' + win.counterName);
			} else {
				if(Ext.getCmp('tabPanel').getActiveTab().templateId=='template5'){
					counterButton.setTooltip(win.counterProductName + ' - ' + win.counterName);
				}
				else{
					counterButton.setTooltip('');

				}
			}		
    	}
    },
    
    counterCancel: function() {
		// Update the configuration panel
    	Ext.getCmp('counterId_configChart').setValue('');
    	Ext.getCmp('counterType_configChart').setValue('');
    	Ext.getCmp('counterProductId_configChart').setValue('');
    	Ext.getCmp('counterProductLabel_configChart').setValue('');
    	Ext.getCmp('counterLabel_configChart').setValue('');
    
    	// Change the button aspect
    	var counterButton = Ext.getCmp('counterButton_configChart');
		counterButton.removeCls('x-button-counter-select-ok');
		counterButton.addCls('x-button-counter-select');
		counterButton.setTooltip('');
	},
	
	trendCounterCancel: function() {		
		// Update the configuration panel
		Ext.getCmp('trendCounterId_configChart').setValue('');
    	Ext.getCmp('trendCounterType_configChart').setValue('');
    	Ext.getCmp('trendCounterProductId_configChart').setValue('');          	
    	Ext.getCmp('trendCounterProductLabel_configChart').setValue('');
    	Ext.getCmp('trendCounterLabel_configChart').setValue('');
    
    	// Change the button aspect
    	var counterButton = Ext.getCmp('trendCounterButton_configChart');
		counterButton.removeCls('x-button-counter-select-ok');
		counterButton.addCls('x-button-counter-select');
		counterButton.setTooltip('');    	
	},

	cancelCounter: function(button) {
		var win = Ext.getCmp('counterWindow');
		
		// Hide the window
		win.hide();
	},
	
	selectNetwork: function(button) {
		var win = Ext.getCmp('networkWindow');
		var axis = 1;
		
		if (button.action == 'selectNetwork2') axis = 3;
		
        if (!win) {
        	win = Ext.create('widget.networkwindow', {axis: axis});
        } else {
    		win.resetSearch(axis);
    	}
        win.roaming = false;
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
        
        var config = button.up('configchart');
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
		var roaming = win.roaming;

		if (neId != null) {
			if (axis == 3) {
				// Update the configuration panel
		    	Ext.getCmp('neId2_configChart').setValue(neId);
		    	Ext.getCmp('neLevelId2_configChart').setValue(win.neLevelId);
		    	Ext.getCmp('neProductId2_configChart').setValue(win.neProductId);
		    	Ext.getCmp('neLabel2_configChart').setValue(win.neLabel);
		    	Ext.getCmp('neLevelLabel2_configChart').setValue(win.neLevelLabel);
				if(roaming == false){
		    		var counterButton = Ext.getCmp('neButton2_configChart');
		    	}else{
		    		var counterButton = Ext.getCmp('neSelectButton_configMapAssoction');
		    	}
		    	
			} else {	// default: 1st network
				// Update the configuration panel
				if (Ext.getCmp('neGrid_configChart').isVisible()) {
    				// Several counters, displayed beneath
    				Ext.getCmp('neId_configChart').setValue(Ext.getCmp('neId_configChart').getValue() + '||' + neId);
                	Ext.getCmp('neLevelId_configChart').setValue(Ext.getCmp('neLevelId_configChart').getValue() + '||' + win.neLevelId);
                	Ext.getCmp('neProductId_configChart').setValue(Ext.getCmp('neProductId_configChart').getValue() + '||' + win.neProductId);
                	Ext.getCmp('neLabel_configChart').setValue(Ext.getCmp('neLabel_configChart').getValue() + '||' + win.neLabel);
                	Ext.getCmp('neLevelLabel_configChart').setValue(Ext.getCmp('neLevelLabel_configChart').getValue() + '||' + win.neLevelLabel);
    			
                	// Create a model instance
                    var n = Ext.create('NetworkModel', {
                        label: win.neLabel,
                        level: win.neLevelLabel
                    });
                    Ext.getCmp('neGrid_configChart').store.insert(Ext.getCmp('neGrid_configChart').store.getCount(), n);
    			} else {
    				Ext.getCmp('neId_configChart').setValue(neId);
    		    	Ext.getCmp('neLevelId_configChart').setValue(win.neLevelId);
    		    	Ext.getCmp('neProductId_configChart').setValue(win.neProductId);
    		    	Ext.getCmp('neLabel_configChart').setValue(win.neLabel);
    		    	Ext.getCmp('neLevelLabel_configChart').setValue(win.neLevelLabel);
    			}    	
		    	if(roaming == false){
		    		var counterButton = Ext.getCmp('neButton_configChart');
		    	}else{
		    		var counterButton = Ext.getCmp('neSelectButton_configMapAssoction');
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
	
	neCancel: function() {
		var win = Ext.getCmp('networkWindow');
		
		//roaming=win.roaming;
		// Update the configuration panel
    	Ext.getCmp('neId_configChart').setValue('');
    	Ext.getCmp('neLevelId_configChart').setValue('');
    	Ext.getCmp('neProductId_configChart').setValue('');
    	Ext.getCmp('neLabel_configChart').setValue('');
    	Ext.getCmp('neLevelLabel_configChart').setValue('');
    	var counterButton = Ext.getCmp('neButton_configChart');
    	
    	/**
    	// Change the button aspect for roaming mod
    	if(roaming == false){
    		var counterButton = Ext.getCmp('neButton_configChart');
    	}else{
    		var counterButton = Ext.getCmp('neSelectButton_configMapAssoction');
    	}
    	**/
		counterButton.removeCls('x-button-network-select-ok');
		counterButton.addCls('x-button-network-select');
		counterButton.setTooltip('');    	
	},
	
	neCancel2: function() {
		// Update the configuration panel
    	Ext.getCmp('neId2_configChart').setValue('');
    	Ext.getCmp('neLevelId2_configChart').setValue('');
    	Ext.getCmp('neProductId2_configChart').setValue('');
    	Ext.getCmp('neLabel2_configChart').setValue('');
    	Ext.getCmp('neLevelLabel2_configChart').setValue('');
    
    	// Change the button aspect
    	var counterButton = Ext.getCmp('neButton2_configChart');
		counterButton.removeCls('x-button-network-select-ok');
		counterButton.addCls('x-button-network-select');
		counterButton.setTooltip('');    	
	},

	cancelNetwork: function(button) {
		button.up('networkwindow').hide();
	},	

	addAlarm: function(button) {
		var combo = Ext.getCmp('AlarmCombo_configChart');
		var alarmId = combo.getValue();
		var record = combo.findRecord(combo.valueField || combo.displayField, alarmId);
		
		// Add the record to the store
		var store = Ext.getCmp('AlarmGrid_configChart').getStore();
		if (store.findRecord('id', record.data.id) == null) {
			store.add(record);
			store.sort('label', 'ASC');
			//disable prodcutCombo when one alarm has been added
			Ext.getCmp('productCombo_configChart').setDisabled(true);
		}
		
		
	},
	
	addGraphType: function(button) {
		//var combo = Ext.getCmp('graphTypeCombo_configChart');
		//var graphId = combo.getValue();
		//var record = combo.findRecord(combo.valueField || combo.displayField, graphId);
		
		// Add the record to the store
		var store = Ext.getCmp('graphsTable_configChart').getStore();
		var index = store.data.length;
		var maxIndexGraph = 0;
		for (var j = 0; j < index; j++) {
			graphId = store.data.items[j].data.id;
			pos = graphId.indexOf('_');
			//Get the graph index
			curentGraphIndex = graphId.substr(pos+1,graphId.length-pos)
			if ( curentGraphIndex >= maxIndexGraph){
				maxIndexGraph = curentGraphIndex;
			}
		}
		var maxIndexGraph = parseInt(maxIndexGraph);
		var nextIndex = maxIndexGraph+1;
		var graph = [['graph_'+nextIndex, 'Graph alarm '+nextIndex]];
		store.add(graph, false); 
		Ext.getCmp('productCombo_configChart_ar').setDisabled(true);
		
		Ext.Ajax.request({
			url: 'proxy/configuration.php',
			params: {
				task: 'GET_ALARMS',
				product: Ext.getCmp('productCombo_configChart_ar').value
			},
    
			success: function(response) {
				// Add the alarms in the combobox
				var alarms = Ext.decode(response.responseText).alarm;
				for (var i = 0; i < alarms.length; i++) {
					alarms.active=true;
				}
				var alarmComboConfig = Ext.getCmp('AlarmCombo_configChart_ar');															
				graphsStore = Ext.getStore('graphstore');
				var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
	    		//var record = graphsStore.findRecord('id', selection.data.id);
	    		//record.data.piechart = false;
				alarmComboConfig.store.loadData(alarms);
				alarmComboConfig.store.sort('label', 'ASC');
				alarmComboConfig.enable(true);
			}
		});
	},
	addAlarm_ar: function(button) {
		var combo = Ext.getCmp('AlarmCombo_configChart_ar');
		var alarmId = combo.getValue();
		graphsStore = Ext.getStore('graphstore');
		var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
	    var record = graphsStore.findRecord('id', selection.data.id);
		var Alarmsrecord = combo.findRecord(combo.valueField || combo.displayField, alarmId);
		
		// Add the record to the store
		var store = Ext.getCmp('alarmsGrid_configChart_ar').getStore();
		if(store.getCount() == 0){
			var alarmsList = "";
		}else{
			var alarmsList  = record.data.displayed_alarms;
		}

		if (store.findRecord('id', Alarmsrecord.data.id) == null) {
			store.add(Alarmsrecord);
			store.sort('label', 'ASC');
			if(alarmsList == ""){
				alarmsList = Alarmsrecord.data.id;
			}else{
				alarmsList = alarmsList+","+Alarmsrecord.data.id;
			}
			
	    	if (record != null) {
	    		record.set('displayed_alarms',alarmsList);
				record.commit();
       	    	//record.data.displayed_alarms = newAlarmsList;
           		//graphConf.bindStore(graphsStore);
	    	}
	    	
		}

	}
});