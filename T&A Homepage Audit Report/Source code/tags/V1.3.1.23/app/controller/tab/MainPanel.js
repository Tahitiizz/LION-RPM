Ext.define('homepage.controller.tab.MainPanel', {
        extend : 'Ext.app.Controller',
    
        views : ['tab.MainPanel'],
    
        // Initialize the event handlers
        init : function() {
            this.control({
                        'button[action=save]' : {
                            click : this.updateConfig
                        },
    
                        'button[action=display]' : {
                            click : this.makeConfig
                        },
    
                        'button[action=reset]' : {
                            click : this.resetConfig
                        },
    
                        'button[action=copyTab]' : {
                            click : this.copyTab
                        },
    
                        'button[action=add]' : {
                            click : this.addTab
                        },
    
                        'button[action=deleteTab]' : {
                            click : this.deleteTab
                        },
    
                        'configpanel' : {
                            makeTrendConfig : this.makeTrendConfig,
                            refreshEvent : this.refreshEvent
                        },
    
                        'gauge' : {
                            chartClick : this.loadTrendConfig
                        },
    
                        'chartspanel' : {
                            displayCharts : this.displayCharts
                            ,
                            // show: this.loadConfig
                        },
    
                        'mainpanel' : {
                            showConfig : this.showConfig,
                            refreshCharts : this.loadConfig,
                            autoRefresh : this.autoRefresh,
                            exp : this.exportTab,
                            afterrender : this.getTab,
                            afterlayout : this.resize,
                            fullscreen : this.fullscreen,
                            resizeGauges : this.resizeGauges,
                            createTab : this.createTab
                        },
    
                        'tabpanel' : {
                            tabchange : this.loadConfig
                        }
    
                    });
        },
        showConfig : function(checked) {
            var me = this;
            var configPanel = Ext.getCmp('configPanel');
    
            if (checked) {
                if (typeof(configPanel) == 'undefined') {
                    // Create the configuration panel
                    var configPanel = Ext.create(
                            'homepage.view.configuration.ConfigPanel', {
                                id : 'configPanel',
                                xtype : 'configpanel',
                                title : 'Configuration',
                                iconCls : 'icoConfiguration',
                                width : 300
                            });
    
                    Ext.getCmp('mainPanel').insert(0, configPanel);
    
                    // Hide the reset button
                    if (Ext.getCmp('viewport').isAdmin == 1) {
                        Ext.getCmp('resetButton').setVisible(false);
                    }
    
                    me.openConfiguration();
                } else {
                    configPanel.setVisible(true);
                    me.openConfiguration();
                }
            } else {
                // Prevent an infinite loop
                Ext.getCmp('configTab').modified = false;
                Ext.getCmp('configChart').modified = false;
    
                // Remove the configuration panel
                configPanel.setVisible(false);
            }
        },
        
        autoRefresh : function(checked) {
            var me = this;
            var panel = Ext.getCmp('mainPanel');
    
            if (checked) {
                // Launch the auto refresh
                var task = {
                    run : me.updateClock,
                    interval : Ext.getCmp('viewport').timer
                };
    
                panel.runner.start(task);
            } else {
                // Stop the auto refresh
                panel.runner.stopAll();
            }
        },
    
        updateClock : function() {
            var me = this;
            // Only if the configuration panel is collapsed
            if (typeof(Ext.getCmp('configPanel')) == 'undefined'
                    || Ext.getCmp('configPanel').hidden) {
                // Get the following gauge
                var tab = Ext.getCmp('tabPanel').getActiveTab();
                var tabId = tab.getId();
                var gaugeArray = Ext.getCmp(tabId).query('gauge');
                // if there aren't any gauges (template 4, 5 and 6)
                if (gaugeArray.length > 0) {
    
                    var selectedGauge = Ext.getCmp(tabId).selectedChart
                    var i = 0;
                    for (; i < gaugeArray.length; i++) {
                        if (gaugeArray[i].id == selectedGauge)
                            break;
                    }
                    var newSelectedGauge = gaugeArray[(i + 1) % gaugeArray.length].id;
    
                    // Select the new gauge
                    Ext.getCmp(tabId).selectedChart = newSelectedGauge;
    
                    // Update the graph line with a fake click event
                    gaugeArray[0].fireEvent('chartClick');
    
                    // If it's the last gauge, we change the tab
                    if ((i + 1) % gaugeArray.length == 0) {
                        var tabs = Ext.getCmp('tabPanel').items;
    
                        Ext.getCmp('tabPanel').setActiveTab(tabs.getAt([(tabs
                                .indexOf(tab) + 1)
                                % tabs.getCount()]));
                    }
                } else {            
                    if(Ext.getCmp(tabId).templateId=='template5'){
                        kpi_selector=Ext.getCmp(tabId+'_chart1_kpi_selector');
                        nbkpis=kpi_selector.store.count();
                        //var step= Math.floor(Ext.getCmp('viewport').timer/nbkpis);
                        var step= Ext.getCmp('viewport').timer;
    
                        var task = new Ext.util.DelayedTask(function(){ 
                            kpi_selector=Ext.getCmp(tabId+'_chart1_kpi_selector');
                            if(kpi_selector.getValue()<kpi_selector.store.count()-1){
                                kpi_selector.setValue(kpi_selector.getValue()+1);
                            }else{
                                //end of delayed task
                                var tabs = Ext.getCmp('tabPanel').items;
                                Ext.getCmp('tabPanel').setActiveTab(tabs.getAt([(tabs
                                        .indexOf(tab) + 1)
                                        % tabs.getCount()]));
                            }
                        });
                        task.delay(step);                           
                    }
                    else{
                        var tabs = Ext.getCmp('tabPanel').items;
                        Ext.getCmp('tabPanel').setActiveTab(tabs.getAt([(tabs
                                .indexOf(tab) + 1)
                                % tabs.getCount()]));
                    }
        
                }
            }
        },
        
        updateKpi : function(){
            if (typeof(Ext.getCmp('configPanel')) == 'undefined'
                || Ext.getCmp('configPanel').hidden) {
                // Get the following gauge
                var tab = Ext.getCmp('tabPanel').getActiveTab();
                var tabId = tab.getId();
                if(Ext.getCmp(tabId).templateId=='template5'){
                    kpi_selector=Ext.getCmp(tabId+'_chart1_kpi_selector');
                    
                    if(kpi_selector.getValue()<kpi_selector.store.count()-1){
                        kpi_selector.setValue(kpi_selector.getValue()+1);
                    }
                }
            }   
        },
    
        resizeGauges : function() {
            // If the active tab is an object
            if ((Ext.getCmp('tabPanel').getActiveTab() != null)
                    && (typeof(Ext.getCmp('tabPanel').getActiveTab()) != 'number')) {
                // Get the Ext JS component
                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
                var chartsPanel = Ext.getCmp(tabId);
    
                if (typeof(chartsPanel) != 'undefined') {
                    var gaugeArray = chartsPanel.query('gauge');
    
                    if (gaugeArray.length > 0) {
                        for (var i = 0; i < gaugeArray.length; i++) {
                            gaugeArray[i].fireEvent('resizeGauge', gaugeArray[i]);
                        }
                    }
                }
            }
        },
    
        // Resize the gauges
        openConfiguration : function() {
            this.loadConfig();
        },
    
        // Send an ajax request to load the panel configuration from the XML file
        loadConfig : function() {
            var configPanel = Ext.getCmp('configPanel');
            // If the active tab is an object
            if ((Ext.getCmp('tabPanel').getActiveTab() != null)
                    && (typeof(Ext.getCmp('tabPanel').getActiveTab()) != 'number')) {
                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
    
                if ((typeof(configPanel) != 'undefined') && !configPanel.hidden) {
                  
                    Ext.getCmp('configChart').setVisible(true);
    
                    Ext.getCmp('copyTabButton').enable();
                    Ext.getCmp('deleteTabButton').enable();
                    
                    //do not allow to set default tab for the first tab
                     if(Ext.getCmp('tabPanel').items.indexOf(Ext.getCmp('tabPanel').getActiveTab())==0){
                         Ext.getCmp('defaultTabButton').disable();
                     }
                     else{
                         Ext.getCmp('defaultTabButton').enable();
                     }
    
                    // do not allow to save and display during config load
                    Ext.getCmp('displayButton').hide();
                    Ext.getCmp('saveButton').hide();
                    Ext.getCmp('addAlarmButton_configChart').hide();
                }
    
                Ext.Ajax.request({
                            url : 'proxy/configuration.php',
                            params : {
                                task : 'LOAD',
                                tab : tabId
    
                            },
    
                            success : this.displayConfig
    
                        });
            } else {
                // no tabs, do not display configChart
                if ((typeof(configPanel) != 'undefined') && !configPanel.hidden) {
                    Ext.getCmp('configChart').setVisible(false);
    
                    // if no tabs, hide copy, delete and default button
                    Ext.getCmp('copyTabButton').disable();
                    Ext.getCmp('deleteTabButton').disable();
                    Ext.getCmp('defaultTabButton').disable();
                    
                    Ext.getCmp('configMapModeSelection').hide();
                    Ext.getCmp('configMapAssociation').hide();
                    
    
                    var style = 'classic';
                    // set combostyle value to default 'classic' value
                    configPanel.down('combobox[id="styleCombo"]').originalValue = style;
                    configPanel.down('combobox[id="styleCombo"]').setValue(style);
                }
            }
        },
    
        // Send an ajax request to load the panel configuration from the XML file
        loadConfigNoLayout : function() {
            var me = this;
            // If the active tab is an object
            if ((Ext.getCmp('tabPanel').getActiveTab() != null)
                    && (typeof(Ext.getCmp('tabPanel').getActiveTab()) != 'number')) {
                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
    
                if ((typeof(configPanel) != 'undefined') && !configPanel.hidden) {
                    Ext.getCmp('configChart').setVisible(true);
    
                    // do not allow to save and display during config load
                    Ext.getCmp('displayButton').hide();
                    Ext.getCmp('saveButton').hide();
    
                }
    
                Ext.Ajax.request({
                            url : 'proxy/configuration.php',
                            params : {
                                task : 'LOAD',
                                tab : tabId
                            },
    
                            success : function(result) {
                                me.displayConfig(result, false);
                            }
                        });
            } else {
                // no tabs, do not display configChart
                if ((typeof(configPanel) != 'undefined') && !configPanel.hidden) {
                    Ext.getCmp('configChart').setVisible(false);
    
                    var style = 'classic';
                    // set combostyle value to default 'classic' value
                    configPanel.down('combobox[id="styleCombo"]').originalValue = style;
                    configPanel.down('combobox[id="styleCombo"]').setValue(style);
    
                }
            }
        },
    
        // Send an ajax request to load the trend configuration from the XML file
        loadTrendConfig : function(gauge) {
            var me = this;
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
    
            if ((gauge == null) || (typeof(gauge) != 'string')
                    || (typeof (gauge).length == 'undefined')) {
                // We use the selected gauge
                gauge = Ext.getCmp(tabId).selectedChart;
    
                // Change the gauges title color
                var gaugeArray = Ext.getCmp(tabId).query('gauge');
                if (gaugeArray.length > 0) {
                    for (var i = 0; i < gaugeArray.length; i++) {
                        if (gaugeArray[i].id == gauge) {
                            // This gauge is the selected one
                            gaugeArray[i].removeCls('x-chart-title-normal');
                            gaugeArray[i].addCls('x-chart-title-selected');
                        } else {
                            // This gauge is not the one
                            gaugeArray[i].removeCls('x-chart-title-selected');
                            gaugeArray[i].addCls('x-chart-title-normal');
                        }
                    }
                }
    
                // Load the config in the configuration panel
                var refreshBox = Ext.getCmp('refreshBox');
                if (!refreshBox.checked) {
                    var configPanel = Ext.getCmp('configPanel');
                    if (typeof(configPanel) != 'undefined' && !configPanel.hidden) {
                        me.loadConfigNoLayout();
                    }
                }
            }
    
            Ext.Ajax.request({
                        url : 'proxy/configuration.php',
                        params : {
                            task : 'LOAD_TREND',
                            tab : tabId,
                            chart : gauge
                        },
    
                        success : function(response) {
                            // Decode the json into an array object
                            if (response.responseText != '') {
                                var config = Ext.decode(response.responseText);
                                var target = Ext.getCmp(Ext.getCmp(gauge).target);
                                if (typeof(target) != 'undefined') {
                                    var target = Ext.getCmp(Ext.getCmp(gauge).target);
                                    Ext.getCmp(target.id).fireEvent('load', config,gauge);
                                }
                            } else {
                                // call load even with empty config
                                var target = Ext.getCmp(Ext.getCmp(gauge).target);
                                if (typeof target != 'undefined')
                                    Ext.getCmp(target.id).fireEvent('load', config,gauge);
                            }
                        }
                    });
    
        },
    
        // Display the configuration from the XML file in the configuration panel
        displayConfig : function(response, layout) {
            var me = this;
           
            // Decode the json into an array object
            var config = Ext.decode(response.responseText);
    
            // Get the Ext JS components
            var configChart = Ext.getCmp('configChart');
            var configPanel = Ext.getCmp('configPanel');
            var configMap = Ext.getCmp('configMapModeSelection');
            var configMapAssociation = Ext.getCmp('configMapAssociation');
            
            var template = config['template'];
    
            if ((typeof(configPanel) != 'undefined') && !configPanel.hidden) {
                // Get the configuration for the selected chart
                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
                var selectedChart = Ext.getCmp(tabId).selectedChart;
    
                var configTab = Ext.getCmp('configTab');
    
                if (template == 'template5') {
                    if (configChart.title.indexOf('*') >= 0) {
                        configChart.setTitle('Map *');
                    } else {
                        configChart.setTitle('Indicators configuration');
                    }
                } else if (template == 'template7') {
                    if (configChart.title.indexOf('*') >= 0) {
                        configChart.setTitle('Grid *');
                    } else {
                        configChart.setTitle('Grid');
                    }
                } else {
                    if (configChart.title.indexOf('*') >= 0) {
                        configChart.setTitle('Graph *');
                    } else {
                        configChart.setTitle('Graph');
                    }
                }
    
                // Homepage style
                var style = config['@attributes']['style'];
                configPanel.down('combobox[id="styleCombo"]').originalValue = style;
                configPanel.down('combobox[id="styleCombo"]').setValue(style);
    
                // Tab title
                var title = config['title'];
                configTab.down('textfield[id="titleField_configTab"]').originalValue = title;
                configTab.down('textfield[id="titleField_configTab"]')
                        .setValue(title);
    
                // Tab template
                configTab.down('combobox[id="templateCombo_configTab"]').originalValue = template;
                configTab.down('combobox[id="templateCombo_configTab"]')
                        .setValue(template);
    
                // Tab index
                var tabIndex = Ext.getCmp('tabPanel').items.findIndex('id', tabId)
                        + 1;
                configTab.down('numberfield[id="indexField_configTab"]')
                        .setMaxValue(Ext.getCmp('tabPanel').items.length);
                configTab.down('numberfield[id="indexField_configTab"]').originalValue = tabIndex;
                configTab.down('numberfield[id="indexField_configTab"]')
                        .setValue(tabIndex);
    
                // Default tab
                var isDefault = false;
                if (config['@attributes']['isDefault'] == 'true')
                    isDefault = true;
                configTab.down('hiddenfield[id="defaultTabHidden"]').originalValue = isDefault;
                configTab.down('hiddenfield[id="defaultTabHidden"]')
                        .setValue(isDefault);
                Ext.getCmp('defaultTabButton').toggle(isDefault);
    
                /*
                 * //remove disclaimer if ((typeof(configPanel) != 'undefined') &&
                 * !configPanel.hidden){ if(typeof(Ext.getCmp('alarmDisclaimer'))!==
                 * 'undefined'){ configChart.remove('alarmDisclaimer'); } }
                 */
    
                for (var i = 0; i < config['widgets']['widget'].length; i++) {
                    if ((config['widgets']['widget'][i]['function'] == 'detail')
                            && (tabId
                                    + '_'
                                    + config['widgets']['widget'][i]['@attributes']['id'] == selectedChart)) {
    
                        var chartId = 'config'
                                + config['widgets']['widget'][i]['@attributes']['id'];
    
                        // GENERAL
    
                        // Title
                        var title = config['widgets']['widget'][i]['title'];
                        configChart.down('textfield[id="titleField_configChart"]').originalValue = title;
                        configChart.down('textfield[id="titleField_configChart"]')
                                .setValue(title);
    
                        // Url
                        var url = config['widgets']['widget'][i]['url'];
                        configChart.down('textfield[id="urlField_configChart"]').originalValue = url;
                        configChart.down('textfield[id="urlField_configChart"]')
                                .setValue(url);
                        
                        configChart
                                .down('fieldset[id="mapField_configChart"]')
                                .setVisible(false);
                        configChart
                                .down('gridpanel[id="mapKpiGrid_configChart"]')
                                .setVisible(false);
                        
                        //restore original labelEL for kpi selectors
                        configChart
                                .down('fieldcontainer[id="counterContainer_configChart"]')
                                .labelEl.update('Raw / KPI');
                        
                        configChart
                                .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                .labelEl.update('Raw / KPI');
                                
                        configChart
                                .down('textfield[id="trendUnitField_configChart"]')
                                .labelEl.update('Unit');
                        
                        configChart
                                .down('textfield[id="unitField_configChart"]')
                                .labelEl.update('Unit');
                        
                        //hide mode selection
                        Ext.getCmp('configMapModeSelection').setVisible(false);
                        Ext.getCmp('configMapAssociation').setVisible(false);
                        
                        
                        if (template == 'template4') { // Frame
                            // Hide other templates' fields
                            configChart
                                    .down('checkboxfield[id="neDisplayBox_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('combobox[id="typeCombo_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('textfield[id="unitField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('numberfield[id="thresholdMinField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('numberfield[id="thresholdMaxField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('checkboxfield[id="dynamicBox_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('numberfield[id="scaleMinField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('numberfield[id="scaleMaxField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('combobox[id="timeUnitCombo_configChart"]')
                                    .setVisible(false);
                            configChart.down('datefield[id="date_configChart"]')
                                    .setVisible(false);
                            configChart.down('timefield[id="time_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('numberfield[id="trendPeriodField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('textfield[id="trendUnitField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldcontainer[id="neContainer_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldcontainer[id="ne2Container_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldcontainer[id="counterContainer_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('gridpanel[id="counterGrid_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('component[id="gaugeTitle_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldset[id="labelField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('component[id="detailsTitle_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('gridpanel[id="counterGrid_configChart"]')
                                    .setVisible(false);
                            configChart.down('gridpanel[id="neGrid_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('combobox[id="productCombo_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldcontainer[id="alarmBox_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('gridpanel[id="AlarmGrid_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldset[id="alarmField_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('sliderfield[id="alarmDayNumberfield_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('component[id="selectedtab_configChart"]')
                                    .setVisible(false);
                            configChart.down('component[id="alltabs_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                    .setVisible(false);
                            // Show template's fields
                            configChart
                                    .down('textfield[id="titleField_configChart"]')
                                    .setVisible(true);
                            configChart
                                    .down('textfield[id="urlField_configChart"]')
                                    .setVisible(true);
                                    
                            configChart
                                    .down('fieldcontainer[id="graphType_configChart"]')
                            .setVisible(true);
                            //audit report
                            configChart
                                    .down('combobox[id="productCombo_configChart_ar"]')
                                    .setVisible(false);
                            
                            configChart
                                    .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('component[id="alarmSettings_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('gridpanel[id="graphsTable_configChart"]')
                                    .setVisible(false);         
                            configChart
                                    .down('component[id="graphs_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldset[id="graphConfig_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('fieldcontainer[id="alarmBox_configChart_ar"]')
                                    .setVisible(false);
                            configChart
                                    .down('gridpanel[id="alarmsGrid_configChart_ar"]')
                                    .setVisible(false);
                            configChart
                                    .down('button[id="addGraphTypeButton_configChart"]')
                                    .setVisible(false);
                            configChart
                                    .down('sliderfield[id="HistoryDisplayed_configChart"]')
                                    .setVisible(false);
                                    
                        } else {
                            if (template == 'template5') { // Map
                                
                                
                                Ext.getCmp('configMapModeSelection').setVisible(true);
                                
                                
                                //load map store
                                Ext.getCmp('mapKpiGrid_configChart').getStore().load({params:{ tab: Ext.getCmp('tabPanel').getActiveTab().getId()}})
                                 Ext.getCmp('mapKpiGrid_configChart_roaming').getStore().load({params:{ tab: Ext.getCmp('tabPanel').getActiveTab().getId()}})
                                
                                // Hide other templates' fields
                                configChart
                                        .down('textfield[id="urlField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="neContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="ne2Container_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="labelField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="neDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="counterGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="neGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('datefield[id="date_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('timefield[id="time_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="productCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="AlarmGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="alarmField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmDayNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="selectedtab_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="alltabs_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);
                                
                                
                                configChart
                                        .down('component[id="gaugeTitle_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="detailsTitle_configChart"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('component[id="general_configChart"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('combobox[id="productCombo_configChart_ar"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart"]')
                                            .setVisible(false);
                                
                                configChart
                                        .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                            .setVisible(false);
                                
                                configChart
                                        .down('component[id="alarmSettings_configChart"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('gridpanel[id="graphsTable_configChart"]')
                                        .setVisible(false); 
                                
                                configChart
                                        .down('component[id="graphs_configChart"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('sliderfield[id="HistoryDisplayed_configChart"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('fieldset[id="graphConfig_configChart"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart_ar"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="alarmsGrid_configChart_ar"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('button[id="addGraphTypeButton_configChart"]')
                                        .setVisible(false);
                    
                                // Show template's fields
                                
                                configChart
                                        .down('fieldset[id="mapField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('gridpanel[id="mapKpiGrid_configChart"]')
                                        .setVisible(true);
                                
                                configChart
                                        .down('textfield[id="titleField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('textfield[id="unitField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="scaleMinField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="scaleMaxField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('combobox[id="timeUnitCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="trendPeriodField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]')
                                        .setVisible(true);
                                
                                
                                configChart
                                        .down('fieldcontainer[id="counterContainer_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                        .setVisible(true);
                                
                                // Roaming value
                                roamingActivated = config['widgets']['widget'][0]['roaming'];
                                if (typeof(roamingActivated) != 'string')
                                    dateVisible = false;
                                //configMap.down('checkboxfield[id="activate_roaming"]').originalValue = roamingActivated;
                                //configMap.down('checkboxfield[id="activate_roaming"]').setValue(roamingActivated);
                                
                                        
                                //Displayed mode value
                                displayed_value = config['widgets']['widget'][0]['displayed_value_mode'];
                                if (displayed_value == '')
                                    displayed_value = "element";
                                    
                                configMap
                                        .down('combobox[id="displayedValueMode_configMap"]').originalValue = displayed_value;
                                configMap
                                        .down('combobox[id="displayedValueMode_configMap"]')
                                        .setValue(displayed_value); 
                                        
                                //fullscreen time level
                                fullscreen_time_level_default = config['widgets']['widget'][0]['fullscreen_time_level'];
                                if (fullscreen_time_level_default == '')
                                    fullscreen_time_level_default = "day";
                                    
                                configMap
                                        .down('combobox[id="defaultFullscreenTimeLevelCombo_configMap"]').originalValue = fullscreen_time_level_default;
                                configMap
                                        .down('combobox[id="defaultFullscreenTimeLevelCombo_configMap"]')
                                        .setValue(fullscreen_time_level_default);
                                    
                                //trend time level
                                trend_time_level_default = config['widgets']['widget'][0]['trend_time_level'];
                                if (fullscreen_time_level_default == '')
                                    fullscreen_time_level_default = "day";
                                
                                configMap
                                        .down('combobox[id="defaultTrendTimeLevelCombo_configMap"]').originalValue = trend_time_level_default;
                                configMap
                                        .down('combobox[id="defaultTrendTimeLevelCombo_configMap"]')
                                        .setValue(trend_time_level_default);
                                    
                                    
                                //donut time level
                                donut_time_level_default = config['widgets']['widget'][0]['donut_time_level'];
                                if (fullscreen_time_level_default == '')
                                    fullscreen_time_level_default = "week";
                                
                                configMap
                                        .down('combobox[id="defaultDonutTimeLevelCombo_configMap"]').originalValue = donut_time_level_default;
                                configMap
                                        .down('combobox[id="defaultDonutTimeLevelCombo_configMap"]')
                                        .setValue(donut_time_level_default);
                                        
    
                                //set value for mode selection
                                //var mode=config['widgets']['widget'][0]['fullscreen']=="true" ? "2" : "1";
                                if(config['widgets']['widget'][0]['fullscreen'] == "true"){
                                        if(config['widgets']['widget'][0]['roaming'] == "true"){
                                                var mode = "3";
                                        }else{
                                                var mode = "2";
                                        }
                                }else{
                                        var mode = "1";
                                }
                                
                                Ext.getCmp('configMapModeSelection').items.items[0].originalValue = {modeselection : mode};
                                Ext.getCmp('configMapModeSelection').items.items[0].setValue({modeselection : mode});
                                
                                
                                if(mode=="2"){
                                        //if fullscreen hide scale min/max and dynamic
                                            configChart
                                                .down('checkboxfield[id="dynamicBox_configChart"]')
                                                .setVisible(false);
                                            
                                            configChart
                                                .down('numberfield[id="scaleMinField_configChart"]')
                                                .setVisible(false);
                                            
                                            configChart
                                                .down('numberfield[id="scaleMaxField_configChart"]')
                                                .setVisible(false);
                                        }
                                        
                                        if(mode=="1" || mode=="2"){
                                                Ext.getCmp('configMapAssociation').setVisible(false);
                                        //set new labelEL for kpi selectors
                                        configChart
                                                .down('fieldcontainer[id="counterContainer_configChart"]')
                                                .labelEl.update('Select the donut indicator');
                                        
                                        configChart
                                                .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                                .labelEl.update('Select the trend indicator');
                                                
                                        configChart
                                                .down('textfield[id="trendUnitField_configChart"]')
                                                .labelEl.update('Trend Unit');
                                        
                                        configChart
                                                .down('textfield[id="unitField_configChart"]')
                                                .labelEl.update('Donut Unit');
                                        
                                        
                                        configChart.insert(0, Ext.getCmp('trendCounterContainer_configChart'));
            
                                    
                                        configChart.insert(1, Ext.getCmp('counterContainer_configChart'));
                                        
                                        configChart.insert(2, Ext.getCmp('mapKpiGrid_configChart'));
                                    
                                        var mapField=Ext.getCmp('mapField_configChart');
                
                                        configChart.insert(3, mapField);
                    
                                        mapField.add(Ext.getCmp('titleField_configChart'));
                                        mapField.add(Ext.getCmp('trendUnitField_configChart'));
                                        mapField.add(Ext.getCmp('typeCombo_configChart'));
                                        mapField.add(Ext.getCmp('thresholdMinField_configChart'));
                                        mapField.add(Ext.getCmp('thresholdMaxField_configChart'));
                                        mapField.add(Ext.getCmp('dynamicBox_configChart'));
                                        mapField.add(Ext.getCmp('scaleMinField_configChart'));
                                        mapField.add(Ext.getCmp('scaleMaxField_configChart'));
                                        mapField.add(Ext.getCmp('unitField_configChart'));
    
                                        
                                        
                                        //disable all field of mapField
                                        Ext.getCmp('mapField_configChart').query('.combobox,.textfield,.checkbox').forEach(function(c){c.setDisabled(true);});	
                                                            }
                                                            else if (mode=="3"){
                                                                    //load the store
                                                                    //Ext.getCmp('mapKpiGrid_configChart_roaming').getStore().load({params:{ tab: Ext.getCmp('tabPanel').getActiveTab().getId()}})
                                                              Ext.getCmp('configMapAssociation').setVisible(true);
                                                               configChart
                                            .down('checkboxfield[id="dynamicBox_configChart"]')
                                            .setVisible(false);
                                        
                                        configChart
                                            .down('numberfield[id="scaleMinField_configChart"]')
                                            .setVisible(false);
                                        
                                        configChart
                                            .down('numberfield[id="scaleMaxField_configChart"]')
                                            .setVisible(false);
                                                                    //set new labelEL for kpi selector	                            
                                        configChart.hide();
                                        configMapAssociation
                                                .down('fieldcontainer[id="trendCounterContainer_configChart_roaming"]')
                                                .labelEl.update('Select an indicator');
                                                
                                        configMapAssociation
                                                .down('textfield[id="trendUnitField_configChart_roaming"]')
                                                .labelEl.update('Unit');
                                                
                                        configMapAssociation.insert(0, Ext.getCmp('trendCounterContainer_configChart_roaming'));
            
                                    
                                        //configMapAssociation.insert(1, Ext.getCmp('counterContainer_configChart_roaming'));
                                        
                                        configMapAssociation.insert(1, Ext.getCmp('mapKpiGrid_configChart_roaming'));
                                    
                                        var mapField=Ext.getCmp('mapField_configChart_roaming');
                
                                        configMapAssociation.insert(2, mapField);
                    
                                        mapField.add(Ext.getCmp('titleField_configChart_roaming'));
                                        mapField.add(Ext.getCmp('trendUnitField_configChart_roaming'));
                                        mapField.add(Ext.getCmp('typeCombo_configChart_roaming'));
                                        mapField.add(Ext.getCmp('thresholdMinField_configChart_roaming'));
                                        mapField.add(Ext.getCmp('thresholdMaxField_configChart_roaming'));
                                        //mapField.add(Ext.getCmp('dynamicBox_configChart'));
                                        //mapField.add(Ext.getCmp('scaleMinField_configChart'));
                                        //mapField.add(Ext.getCmp('scaleMaxField_configChart'));
                                        //mapField.add(Ext.getCmp('unitField_configChart'));
    
                                        
                                        
                                        //disable all field of mapField
                                        Ext.getCmp('mapField_configChart_roaming').query('.combobox,.textfield,.checkbox').forEach(function(c){c.setDisabled(true);});
                                                            }
                                                            
                            } else if (template == 'template6') { // Grid
                                // Hide other templates' fields
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="unitField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="scaleMinField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="scaleMaxField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="urlField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="labelField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="neDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="trendPeriodField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="ne2Container_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('button[id="neCancelButton2_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="gaugeTitle_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="detailsTitle_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="productCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="AlarmGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                    .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                    .setVisible(false);
                                configChart
                                        .down('fieldset[id="alarmField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmDayNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="selectedtab_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="alltabs_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);
    
                                // Show template's fields
                                configChart
                                        .down('textfield[id="titleField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="neContainer_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('button[id="neCancelButton_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('combobox[id="timeUnitCombo_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('datefield[id="date_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('timefield[id="time_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="counterContainer_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('button[id="counterCancelButton_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('gridpanel[id="counterGrid_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('gridpanel[id="neGrid_configChart"]')
                                        .setVisible(true);
                                        
                                //audit report
                                configChart
                                        .down('combobox[id="productCombo_configChart_ar"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="alarmSettings_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="graphsTable_configChart"]')
                                        .setVisible(false);         
                                configChart
                                        .down('component[id="graphs_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="graphConfig_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart_ar"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="alarmsGrid_configChart_ar"]')
                                        .setVisible(false);
                                configChart
                                    .down('button[id="addGraphTypeButton_configChart"]')
                                    .setVisible(false);
                                configChart
                                    .down('sliderfield[id="HistoryDisplayed_configChart"]')
                                    .setVisible(false);
                            } else if (template == 'template7') { // Ceil
                                                                    // Surveillance
                                // Hide other templates' fields
                                configChart
                                        .down('textfield[id="titleField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="unitField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="scaleMinField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="scaleMaxField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="urlField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="labelField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="neDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="trendPeriodField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="ne2Container_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('button[id="neCancelButton2_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="gaugeTitle_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="detailsTitle_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="neContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('button[id="neCancelButton_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="timeUnitCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('datefield[id="date_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('timefield[id="time_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="counterContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('button[id="counterCancelButton_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="counterGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="neGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="general_configChart"]')
                                        .setVisible(false);
    
                                // Show template's fields
                                configChart
                                        .down('combobox[id="productCombo_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('gridpanel[id="AlarmGrid_configChart"]')
                                        .setVisible(true);
                                configChart
                                    .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                    .setVisible(false);
                                configChart
                                        .down('fieldset[id="alarmField_configChart"]')
                                        .setVisible(true);
                                // configChart.down('numberfield[id="alarmRatioNumberfield_configChart"]').setVisible(true);
                                configChart
                                        .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmDayNumberfield_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('component[id="selectedtab_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('component[id="alltabs_configChart"]')
                                        .setVisible(true);
                                //audit report
                                configChart
                                        .down('combobox[id="productCombo_configChart_ar"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="alarmSettings_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="graphsTable_configChart"]')
                                        .setVisible(false);         
                                configChart
                                        .down('component[id="graphs_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="graphConfig_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart_ar"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="alarmsGrid_configChart_ar"]')
                                        .setVisible(false); 
                                configChart
                                    .down('button[id="addGraphTypeButton_configChart"]')
                                    .setVisible(false);
                                configChart
                                    .down('sliderfield[id="HistoryDisplayed_configChart"]')
                                    .setVisible(false);
    
                            } else if (template == 'template9' || template == 'template10') { // AuditReport
                                // TODO conf panel management
                                // Hide everything for now on, waiting for conf
                                // panel management
                                // Hide other templates' fields
                           
                                configChart
                                        .down('textfield[id="titleField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="unitField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="scaleMinField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="scaleMaxField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="urlField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="labelField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('checkboxfield[id="neDisplayBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('numberfield[id="trendPeriodField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="ne2Container_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('button[id="neCancelButton2_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="gaugeTitle_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="detailsTitle_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="neContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('button[id="neCancelButton_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="timeUnitCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('datefield[id="date_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('timefield[id="time_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="counterContainer_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('button[id="counterCancelButton_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="counterGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="neGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="general_configChart"]')
                                        .setVisible(false);
    
                                // Show template's fields
                                configChart
                                        .down('combobox[id="productCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="productCombo_configChart_ar"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="AlarmGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="alarmField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmDayNumberfield_configChart"]')
                                        .setVisible(false);
                                // configChart.down('numberfield[id="alarmRatioNumberfield_configChart"]').setVisible(true);
                                configChart
                                        .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="selectedtab_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="alarmSettings_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('component[id="alltabs_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="graphsTable_configChart"]')
                                        .setVisible(true);          
                                configChart
                                        .down('component[id="graphs_configChart"]')
                                        .setVisible(true);
                                configChart
                                    .down('sliderfield[id="HistoryDisplayed_configChart"]')
                                    .setVisible(true);
                                configChart
                                        .down('fieldset[id="graphConfig_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart_ar"]')
                                        .setVisible(true);
                                configChart
                                        .down('gridpanel[id="alarmsGrid_configChart_ar"]')
                                        .setVisible(true);  
                                
                                //disable graphConfig_configChart items
                                Ext.getCmp('graphNameNameField_configChart').disable(true);
                                Ext.getCmp('AlarmCombo_configChart_ar').disable(true);
                                Ext.getCmp('addAlarmButton_configChart_ar').disable(true);
                                
                                //show save and display button
                                 Ext.getCmp('saveButton').show();
                                 Ext.getCmp('displayButton').show();
    
                            } else {
                                // Hide other templates' fields
                                configChart
                                        .down('textfield[id="urlField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="counterGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="neGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('datefield[id="date_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('timefield[id="time_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('combobox[id="productCombo_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="AlarmGrid_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="alarmField_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmDayNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="selectedtab_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="alltabs_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmRatioNumberfield_configChart"]')
                                        .setVisible(false);                         
    
                                // Show template's fields
                                configChart
                                        .down('textfield[id="titleField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('checkboxfield[id="neDisplayBox_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('textfield[id="unitField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="scaleMinField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="scaleMaxField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('combobox[id="timeUnitCombo_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('numberfield[id="trendPeriodField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="neContainer_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="ne2Container_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="counterContainer_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('component[id="gaugeTitle_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('fieldset[id="labelField_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('component[id="detailsTitle_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('button[id="neCancelButton_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('button[id="neCancelButton2_configChart"]')
                                        .setVisible(true);
                                configChart
                                        .down('button[id="counterCancelButton_configChart"]')
                                        .setVisible(true);
                                
                                configChart
                                        .down('component[id="general_configChart"]')
                                        .setVisible(true);
                                
                                //audit report
                                configChart
                                        .down('combobox[id="productCombo_configChart_ar"]')
                                        .setVisible(false);
                                
                                configChart
                                        .down('gridpanel[id="penalitiesCriteria_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('radiogroup[id="alarmPenalizationMode_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('sliderfield[id="alarmNbDaysNumberfield_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('component[id="alarmSettings_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="graphsTable_configChart"]')
                                        .setVisible(false);         
                                configChart
                                        .down('component[id="graphs_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldset[id="graphConfig_configChart"]')
                                        .setVisible(false);
                                configChart
                                        .down('fieldcontainer[id="alarmBox_configChart_ar"]')
                                        .setVisible(false);
                                configChart
                                        .down('gridpanel[id="alarmsGrid_configChart_ar"]')
                                        .setVisible(false);
                                configChart
                                    .down('button[id="addGraphTypeButton_configChart"]')
                                    .setVisible(false);
                                configChart
                                    .down('sliderfield[id="HistoryDisplayed_configChart"]')
                                    .setVisible(false);
                                
    
                                //set General to its original position
                                
                                configChart.insert(0, Ext.getCmp('general_configChart'));
                                
                                configChart
                                        .down('textfield[id="titleField_configChart"]')
                                        .setDisabled(false);
                                
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]')
                                        .setDisabled(false);
                                
                                configChart
                                        .down('textfield[id="titleField_configChart"]')
                                        .setDisabled(false);
                                
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]')
                                        .setDisabled(false);
                                
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]')
                                        .setDisabled(false);
                                
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]')
                                        .setDisabled(false);
                                
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]')
                                        .setDisabled(false);
                                
    
                                
                                configChart
                                        .down('textfield[id="unitField_configChart"]')
                                        .setDisabled(false);
                                
                                configChart.insert(1, Ext.getCmp('titleField_configChart'));
                                
                                configChart.insert(2, Ext.getCmp('neContainer_configChart'));
                                
                                configChart.insert(3, Ext.getCmp('ne2Container_configChart'));
                                
                                configChart.insert(4, Ext.getCmp('counterContainer_configChart'));
                                
                                configChart.insert(5, Ext.getCmp('typeCombo_configChart'));
                                
                                configChart.insert(6, Ext.getCmp('unitField_configChart'));
                                
                                configChart.insert(7, Ext.getCmp('thresholdMinField_configChart'));
                                
                                configChart.insert(8, Ext.getCmp('thresholdMaxField_configChart'));
                                
                                configChart.insert(9, Ext.getCmp('dynamicBox_configChart'));
                                
                                configChart.insert(10, Ext.getCmp('scaleMinField_configChart'));
                                
                                configChart.insert(11, Ext.getCmp('scaleMaxField_configChart'));
                                
                                // change trend unit and trend rax/ kpi to original
                                // position BZ33045
                                var trendPeriod = Ext
                                        .getCmp('trendPeriodField_configChart');
                                var insertPos = configChart.items
                                        .indexOf(trendPeriod);
                                insertPos++;
                                var trendCounter = Ext
                                        .getCmp('trendCounterContainer_configChart');
                                configChart.insert(insertPos, trendCounter);
                                insertPos++;
                                var trendUnit = Ext
                                        .getCmp('trendUnitField_configChart');
                                configChart.insert(insertPos, trendUnit);
    
                                if (template == 'template3') {
                                    // hide trend config
                                    configChart
                                            .down('component[id="detailsTitle_configChart"]')
                                            .setVisible(false);
                                    configChart
                                            .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                            .setVisible(false);
                                    configChart
                                            .down('numberfield[id="trendPeriodField_configChart"]')
                                            .setVisible(false);
                                    configChart
                                            .down('fieldcontainer[id="trendCounterContainer_configChart"]')
                                            .setVisible(false);
                                    configChart
                                            .down('textfield[id="trendUnitField_configChart"]')
                                            .setVisible(false);
                                }
                            }
    
                            if (template != 'template5'){
                            
                            
                                // Network element
                                var neId = '';
                                var neLevelId = '';
                                var neProductId = '';
                                var neLabel = '';
                                var neLevelLabel = '';
                                var neVisible = false;
                                if (typeof(config['widgets']['widget'][i]['network_elements']) !== 'undefined') {
                                    neId = config['widgets']['widget'][i]['network_elements']['ne']['id'];
                                    if (typeof(neId) != 'string')
                                        neId = '';
        
                                    neLevelId = config['widgets']['widget'][i]['network_elements']['ne']['network_level'];
                                    if (typeof(neLevelId) != 'string')
                                        neLevelId = '';
        
                                    neProductId = config['widgets']['widget'][i]['network_elements']['ne']['product_id'];
                                    if (typeof(neProductId) != 'string')
                                        neProductId = '';
        
                                    neLabel = config['widgets']['widget'][i]['network_elements']['ne']['label'];
                                    if (typeof(neLabel) != 'string')
                                        neLabel = '';
        
                                    neLevelLabel = config['widgets']['widget'][i]['network_elements']['ne']['network_level_label'];
                                    if (typeof(neLevelLabel) != 'string')
                                        neLevelLabel = '';
        
                                    neVisible = config['widgets']['widget'][i]['network_elements']['labels_visible'];
                                    if (typeof(neVisible) != 'string'
                                            || neVisible == '')
                                        neVisible = false;
                                }
                                
                                        
                                configChart.down('hiddenfield[id="neId_configChart"]').originalValue = neId;
                                configChart.down('hiddenfield[id="neId_configChart"]')
                                        .setValue(neId);
                                configChart
                                        .down('hiddenfield[id="neLevelId_configChart"]').originalValue = neLevelId;
                                configChart
                                        .down('hiddenfield[id="neLevelId_configChart"]')
                                        .setValue(neLevelId);
                                configChart
                                        .down('hiddenfield[id="neProductId_configChart"]').originalValue = neProductId;
                                configChart
                                        .down('hiddenfield[id="neProductId_configChart"]')
                                        .setValue(neProductId);
                                configChart
                                        .down('hiddenfield[id="neLabel_configChart"]').originalValue = neLabel;
                                configChart
                                        .down('hiddenfield[id="neLabel_configChart"]')
                                        .setValue(neLabel);
                                configChart
                                        .down('hiddenfield[id="neLevelLabel_configChart"]').originalValue = neLevelLabel;
                                configChart
                                        .down('hiddenfield[id="neLevelLabel_configChart"]')
                                        .setValue(neLevelLabel);
                                configChart
                                        .down('checkboxfield[id="neDisplayBox_configChart"]').originalValue = neVisible;
                                configChart
                                        .down('checkboxfield[id="neDisplayBox_configChart"]')
                                        .setValue(neVisible);
        
                                if (template == 'template6') {
                                    // Several networks, displayed in a grid
                                    var neLabel = Ext.getCmp('neLabel_configChart')
                                            .getValue();
                                    var neLabels = neLabel.split('||');
                                    neLabels.shift();
        
                                    var neLevelLabel = Ext
                                            .getCmp('neLevelLabel_configChart')
                                            .getValue();
                                    var neLevelLabels = neLevelLabel.split('||');
                                    neLevelLabels.shift();
        
                                    for (var label = 0; label < neLabels.length; label++) {
                                        // Create a model instance
                                        var c = Ext.create('NetworkModel', {
                                                    label : neLabels[label],
                                                    level : neLevelLabels[label]
                                                });
                                        Ext.getCmp('neGrid_configChart').store.insert(
                                                Ext.getCmp('neGrid_configChart').store
                                                        .getCount(), c);
                                    }
                                }
        
                                var neButton = configChart
                                        .down('button[id="neButton_configChart"]');
                                if (neId != '') {
                                    neButton.addCls('x-button-network-select-ok');
                                    neButton.removeCls('x-button-network-select');
                                    if (template == 'template6') {
                                        neButton.setTooltip('');
                                    } else {
                                        neButton.setTooltip(neLevelLabel + ' - '
                                                + neLabel);
                                    }
                                } else {
                                    neButton.removeCls('x-button-network-select-ok');
                                    neButton.addCls('x-button-network-select');
                                    neButton.setTooltip('');
                                }
        
                                // 2nd network element
                                neId = '';
                                neLevelId = '';
                                neProductId = '';
                                neLabel = '';
                                neLevelLabel = '';
                                if ((typeof(config['widgets']['widget'][i]['network_elements']) !== 'undefined')
                                        && (typeof(config['widgets']['widget'][i]['network_elements']['ne2']) !== 'undefined')) {
                                    neId = config['widgets']['widget'][i]['network_elements']['ne2']['id'];
                                    if (typeof(neId) != 'string')
                                        neId = '';
        
                                    neLevelId = config['widgets']['widget'][i]['network_elements']['ne2']['network_level'];
                                    if (typeof(neLevelId) != 'string')
                                        neLevelId = '';
        
                                    neProductId = config['widgets']['widget'][i]['network_elements']['ne2']['product_id'];
                                    if (typeof(neProductId) != 'string')
                                        neProductId = '';
        
                                    neLabel = config['widgets']['widget'][i]['network_elements']['ne2']['label'];
                                    if (typeof(neLabel) != 'string')
                                        neLabel = '';
        
                                    neLevelLabel = config['widgets']['widget'][i]['network_elements']['ne2']['network_level_label'];
                                    if (typeof(neLevelLabel) != 'string')
                                        neLevelLabel = '';
                                }
                                configChart.down('hiddenfield[id="neId2_configChart"]').originalValue = neId;
                                configChart.down('hiddenfield[id="neId2_configChart"]')
                                        .setValue(neId);
                                configChart
                                        .down('hiddenfield[id="neLevelId2_configChart"]').originalValue = neLevelId;
                                configChart
                                        .down('hiddenfield[id="neLevelId2_configChart"]')
                                        .setValue(neLevelId);
                                configChart
                                        .down('hiddenfield[id="neProductId2_configChart"]').originalValue = neProductId;
                                configChart
                                        .down('hiddenfield[id="neProductId2_configChart"]')
                                        .setValue(neProductId);
                                configChart
                                        .down('hiddenfield[id="neLabel2_configChart"]').originalValue = neLabel;
                                configChart
                                        .down('hiddenfield[id="neLabel2_configChart"]')
                                        .setValue(neLabel);
                                configChart
                                        .down('hiddenfield[id="neLevelLabel2_configChart"]').originalValue = neLevelLabel;
                                configChart
                                        .down('hiddenfield[id="neLevelLabel2_configChart"]')
                                        .setValue(neLevelLabel);
        
                                neButton = configChart
                                        .down('button[id="neButton2_configChart"]');
                                if (neId != '') {
                                    neButton.addCls('x-button-network-select-ok');
                                    neButton.removeCls('x-button-network-select');
                                    neButton.setTooltip(neLevelLabel + ' - ' + neLabel);
                                } else {
                                    neButton.removeCls('x-button-network-select-ok');
                                    neButton.addCls('x-button-network-select');
                                    neButton.setTooltip('');
                                }
        
                                var counterId = '';
                                var productId = '';
                                var type = '';
                                var counterProductLabel = '';
                                var counterLabel = '';
                                var func = 'success';
                                var counterVisible = false;
                                if (typeof(config['widgets']['widget'][i]['kpis']) !== 'undefined') {
                                    // Counter
                                    counterId = config['widgets']['widget'][i]['kpis']['kpi']['id'];
                                    if (typeof(counterId) != 'string')
                                        counterId = '';
        
                                    productId = config['widgets']['widget'][i]['kpis']['kpi']['product_id'];
                                    if (typeof(productId) != 'string')
                                        productId = '';
        
                                    type = config['widgets']['widget'][i]['kpis']['kpi']['type'];
                                    if (typeof(type) != 'string')
                                        type = '';
        
                                    counterProductLabel = config['widgets']['widget'][i]['kpis']['kpi']['product_label'];
                                    if (typeof(counterProductLabel) != 'string')
                                        counterProductLabel = '';
        
                                    counterLabel = config['widgets']['widget'][i]['kpis']['kpi']['label'];
                                    if (typeof(counterLabel) != 'string')
                                        counterLabel = '';
        
                                    // Function
                                    func = config['widgets']['widget'][i]['kpis']['kpi']['function'];
                                    if (typeof(func) != 'string')
                                        func = 'success';
        
                                    counterVisible = config['widgets']['widget'][i]['kpis']['labels_visible'];
                                }
        
                                configChart
                                        .down('hiddenfield[id="counterId_configChart"]').originalValue = counterId;
                                configChart
                                        .down('hiddenfield[id="counterId_configChart"]')
                                        .setValue(counterId);
                                configChart
                                        .down('hiddenfield[id="counterProductId_configChart"]').originalValue = productId;
                                configChart
                                        .down('hiddenfield[id="counterProductId_configChart"]')
                                        .setValue(productId);
                                configChart
                                        .down('hiddenfield[id="counterType_configChart"]').originalValue = type;
                                configChart
                                        .down('hiddenfield[id="counterType_configChart"]')
                                        .setValue(type);
                                configChart
                                        .down('hiddenfield[id="counterProductLabel_configChart"]').originalValue = counterProductLabel;
                                configChart
                                        .down('hiddenfield[id="counterProductLabel_configChart"]')
                                        .setValue(counterProductLabel);
                                configChart
                                        .down('hiddenfield[id="counterLabel_configChart"]').originalValue = counterLabel;
                                configChart
                                        .down('hiddenfield[id="counterLabel_configChart"]')
                                        .setValue(counterLabel);
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]').originalValue = func;
                                configChart
                                        .down('combobox[id="typeCombo_configChart"]')
                                        .setValue(func);
                                configChart
                                        .down('checkboxfield[id="counterDisplayBox_configChart"]').originalValue = counterVisible;
                                configChart
                                        .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                        .setValue(counterVisible);
        
                                if (template == 'template6') {
                                    // Several counters, displayed in a grid
                                    var counterLabel = Ext
                                            .getCmp('counterLabel_configChart')
                                            .getValue();
                                    var counterLabels = counterLabel.split('||');
                                    counterLabels.shift();
        
                                    for (var label = 0; label < counterLabels.length; label++) {
                                        // Create a model instance
                                        var n = Ext.create('CounterModel', {
                                                    label : counterLabels[label]
                                                });
                                        Ext.getCmp('counterGrid_configChart').store
                                                .insert(
                                                        Ext
                                                                .getCmp('counterGrid_configChart').store
                                                                .getCount(), n);
                                    }
                                }
        
                                var counterButton = configChart
                                        .down('button[id="counterButton_configChart"]');
                                if (counterId != '') {
                                    counterButton.addCls('x-button-counter-select-ok');
                                    counterButton.removeCls('x-button-counter-select');
                                    if (template == 'template6') {
                                        counterButton.setTooltip('');
                                    } else {
                                        counterButton.setTooltip(counterProductLabel
                                                + ' - ' + counterLabel);
                                    }
                                } else {
                                    counterButton
                                            .removeCls('x-button-counter-select-ok');
                                    counterButton.addCls('x-button-counter-select');
                                    counterButton.setTooltip('');
                                }
        
                                var unit = '';
                                var low_threshold = '';
                                var high_threshold = '';
                                var dynamic = false;
                                var min_value = '';
                                var max_value = '';
                                if (typeof(config['widgets']['widget'][i]['axis_list']) !== 'undefined') {
                                    // Unit
                                    unit = config['widgets']['widget'][i]['axis_list']['axis']['unit'];
                                    if (typeof(unit) != 'string')
                                        unit = '';
        
                                    // thresholds
                                    low_threshold = config['widgets']['widget'][i]['axis_list']['axis']['thresholds']['low_threshold'];
                                    if (typeof(low_threshold) != 'string')
                                        low_threshold = '';
                                    high_threshold = config['widgets']['widget'][i]['axis_list']['axis']['thresholds']['high_threshold'];
                                    if (typeof(high_threshold) != 'string')
                                        high_threshold = '';
        
                                    // Zoom
                                    dynamic = config['widgets']['widget'][i]['axis_list']['axis']['zoom']['dynamic'];
                                    if (typeof(dynamic) != 'string')
                                        dynamic = false;
                                    min_value = config['widgets']['widget'][i]['axis_list']['axis']['zoom']['min_value'];
                                    if (typeof(min_value) != 'string')
                                        min_value = '';
                                    max_value = config['widgets']['widget'][i]['axis_list']['axis']['zoom']['max_value'];
                                    if (typeof(max_value) != 'string')
                                        max_value = '';
                                }
                                configChart
                                        .down('textfield[id="unitField_configChart"]').originalValue = unit;
                                configChart
                                        .down('textfield[id="unitField_configChart"]')
                                        .setValue(unit);
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]').originalValue = low_threshold;
                                configChart
                                        .down('numberfield[id="thresholdMinField_configChart"]')
                                        .setValue(low_threshold);
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]').originalValue = high_threshold;
                                configChart
                                        .down('numberfield[id="thresholdMaxField_configChart"]')
                                        .setValue(high_threshold);
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]').originalValue = dynamic;
                                configChart
                                        .down('checkboxfield[id="dynamicBox_configChart"]')
                                        .setValue(dynamic);
                                configChart
                                        .down('numberfield[id="scaleMinField_configChart"]').originalValue = min_value;
                                configChart
                                        .down('numberfield[id="scaleMinField_configChart"]')
                                        .setValue(min_value);
                                configChart
                                        .down('numberfield[id="scaleMaxField_configChart"]').originalValue = max_value;
                                configChart
                                        .down('numberfield[id="scaleMaxField_configChart"]')
                                        .setValue(max_value);
        
                                // Specific Ceil surveillance
                                if (template == 'template7') {
                                    
                                    var penalizationNbDaysValue = '';
                                    var penalizationRatioValue = '';
                                    var productId = '';
                                    var dayNumber = '';
                                    var selectedmode = null;
                                    
                                    selectedmode = config['@attributes'].selectedmode;
                                    penalizationRatioValue = config['@attributes'].ratio * 100;
                                    penalizationNbDaysValue = config['@attributes'].nbdays ;
                                        if (selectedmode == '1'){
                                            Ext.getCmp('alarmPenalizationModeRatio_configChart').setValue(true);
                                        }
                                        else if (selectedmode == '2'){
                                            Ext.getCmp('alarmPenalizationModeNbdays_configChart').setValue(true);
                                        }
                                        
                                            
                                            
                                        if (typeof(penalizationRatioValue) != 'number')
                                            penalizationRatioValue = 50;
                                        Ext.getCmp('alarmRatioNumberfield_configChart').originalValue = penalizationRatioValue;
                                        Ext.getCmp('alarmRatioNumberfield_configChart').setValue(penalizationRatioValue);
                                        
        
                                        if (typeof(penalizationNbDaysValue) != 'string')
                                            penalizationNbDaysValue = 15;
                                        Ext.getCmp('alarmNbDaysNumberfield_configChart').originalValue = penalizationNbDaysValue;
                                        Ext.getCmp('alarmNbDaysNumberfield_configChart').setValue(penalizationNbDaysValue);
                                                    
                                
        
                                    productId = config.widgets.widget[0].sdp_id;
                                    if (typeof(productId) != 'string')
                                        productId = '';
                                    Ext.getCmp('productCombo_configChart').originalValue = productId;
                                    Ext.getCmp('productCombo_configChart')
                                            .setValue(productId);
        
                                    dayNumber = config.widgets.widget[0].minnumberofdays;
                                    // mindays default value to 3
                                    if (typeof(dayNumber) != 'string')
                                        dayNumber = '3';
                                    Ext.getCmp('alarmDayNumberfield_configChart').originalValue = dayNumber;
                                    Ext.getCmp('alarmDayNumberfield_configChart')
                                            .setValue(dayNumber);
        
                                    // refresh static alarm store
                                    var comboProduct = Ext
                                            .getCmp('productCombo_configChart');
        
                                    Ext.Ajax.request({
                                        url : 'proxy/configuration.php',
                                        params : {
                                            task : 'GET_ALARMS',
                                            product : comboProduct.getValue()
                                        },
        
                                        success : function(response) {
                                            // Add the alarms in the combobox
                                            var alarms = Ext
                                                    .decode(response.responseText).alarm;
        
                                            if (typeof(config['widgets']['widget'][0]['sdp_id']) !== 'undefined') {
                                                comboProduct.setDisabled(true);
                                            } else {
                                                comboProduct.setDisabled(false);
                                            }
        
                                            // no static alarms defined for this product
                                            // if(!Ext.isEmpty(alarms)){
                                            /*
                                             * //no product set yet
                                             * if(!Ext.isDefined(comboProduct.getValue())){
                                             * Ext.getCmp('saveButton').setVisible(true);
                                             * Ext.getCmp('displayButton').setVisible(true); }
                                             * else{
                                             * 
                                             * Ext.getCmp('saveButton').setVisible(false);
                                             * Ext.getCmp('displayButton').setVisible(false);
                                             * 
                                             * //display a disclaimer for the user var
                                             * noAlarmsDisclaimer=Ext.create('Ext.form.Label', {
                                             * xtype: 'label', id: 'alarmDisclaimer',
                                             * html: '<br/>No alarms set for this
                                             * product.<br/><br/>You should activate
                                             * alarms first through the administration
                                             * interface.', border: 0, style: { color:
                                             * 'red' } });
                                             * 
                                             * 
                                             * //var
                                             * configChart=Ext.getCmp('configChart');
                                             * configChart.add(noAlarmsDisclaimer);
                                             * 
                                             *  // hide template's fields
                                             * //configChart.down('combobox[id="productCombo_configChart"]').setVisible(false);
                                             * configChart.down('fieldcontainer[id="alarmBox_configChart"]').setVisible(false);
                                             * configChart.down('gridpanel[id="AlarmGrid_configChart"]').setVisible(false);
                                             * configChart.down('fieldset[id="alarmField_configChart"]').setVisible(false);
                                             * configChart.down('numberfield[id="alarmRatioNumberfield_configChart"]').setVisible(false);
                                             * configChart.down('numberfield[id="alarmDayNumberfield_configChart"]').setVisible(false);
                                             *  }
                                             */
                                            // }
                                            // else{
                                            /*
                                             * Ext.getCmp('saveButton').setVisible(true);
                                             * Ext.getCmp('displayButton').setVisible(true);
                                             */
        
                                            var alarmCombo = Ext
                                                    .getCmp('AlarmCombo_configChart');
        
                                            alarmCombo.store.loadData(alarms);
                                            alarmCombo.enable(true);
                                            alarmCombo.clearValue();
                                            Ext.getCmp('addAlarmButton_configChart')
                                                    .enable(true);
        
                                            // get alarms label from db, and not from
                                            // xml (in case alarm label was changed
                                            // through Setup Alarm static)
                                            var alarmsrefarray = new Array(1);
                                            Ext.Array.each(alarms, function(alarm,
                                                            index) {
                                                        alarmsrefarray[alarm.id] = alarm.label;
                                                    });
        
                                            var store = Ext
                                                    .getCmp('AlarmGrid_configChart')
                                                    .getStore();
                                            store.removeAll();
                                            var alarmsconf = []
                                                    .concat(JSON
                                                            .parse(JSON
                                                                    .stringify(config.widgets.widget[0].alarms.alarm)));
                                            // in case alarms is empty, do not load the
                                            // store with empty record
                                            if (Ext.isDefined(alarmsconf[0])
                                                    && typeof(alarmsconf[0].id.length) !== 'undefined') {
                                                for (var a = 0; a < alarmsconf.length; a++) {
                                                    alarmsconf[a].comment = decodeURIComponent(alarmsconf[a].comment) !== '[object Object]'
                                                            ? decodeURIComponent(alarmsconf[a].comment)
                                                            : '';
                                                    alarmsconf[a].grid_name = Ext
                                                            .isDefined(alarmsconf[a].grid_name)
                                                            && typeof (alarmsconf[a].grid_name).length !== 'undefined'
                                                            ? alarmsconf[a].grid_name
                                                            : '';
                                                    alarmsconf[a].label = alarmsrefarray[alarmsconf[a].id];
                                                }
                                                store.loadData(alarmsconf);
                                            }
                                            // }
        
                                            // }
        
                                        }
                                    });
        
                                    /*
                                     * var store =
                                     * Ext.getCmp('AlarmGrid_configChart').getStore();
                                     * store.removeAll(); var alarms =
                                     * [].concat(config.widgets.widget[0].alarms.alarm);
                                     * //in case alarms is empty, do not load the store
                                     * with empty record if(Ext.isDefined(alarms[0]) &&
                                     * typeof(alarms[0].id.length)!=='undefined'){
                                     * store.loadData(alarms); }
                                     */
        
                                }
                                
                                if (template == 'template9' || template == 'template10') {
                                    
                                    var penalizationNbDaysValue = '';
                                    var penalizationRatioValue = '';
                                    var productId = '';
                                    var dayNumber = '';
                                    var selectedmode = null;
                                    var gridAlarmsCalculation = Ext.getCmp('penalitiesCriteria_configChart');
                                    //Product ID
                                    productId = config.widgets.widget[0].sdp_id;
                                    if(Ext.Object.getSize(productId)!=0){
                                    //if (typeof( productId) !== 'undefined') {
                                                Ext.getCmp('productCombo_configChart_ar').setDisabled(true);
                                                Ext.getCmp('addGraphTypeButton_configChart').setDisabled(true);
                                                Ext.getCmp('AlarmCombo_configChart_ar').setDisabled(false);
                                                Ext.getCmp('addGraphTypeButton_configChart').setDisabled(false);
                                                
                                            } else {
                                                Ext.getCmp('productCombo_configChart_ar').setDisabled(false);
                                                Ext.getCmp('AlarmCombo_configChart_ar').setDisabled(true);
                                                Ext.getCmp('addGraphTypeButton_configChart').setDisabled(true);
                                                Ext.getStore('graphstore').removeAll();
                                            }
                                    if (typeof(productId) != 'string')
                                    productId = '';
                                    Ext.getCmp('productCombo_configChart_ar').originalValue = productId;
                                    Ext.getCmp('productCombo_configChart_ar').setValue(productId);
                                    
                                    
                                    
                                    var alarmsStore = Ext.getCmp('penalitiesCriteria_configChart').getStore();
                                    //Calculation alarms
                                    if(typeof(config.widgets.widget[0].calc_alarms) != 'undefined'){
                                        var calc_alarms = config.widgets.widget[0].calc_alarms.alarm;
                                        var alarmscheckedstore = Ext.getCmp('penalitiesCriteria_configChart').getStore();
                                        var alarmsArray = new Array();
                                        for (var j = 0; j < calc_alarms.length; j++) {
                                            alarmsArray.push(calc_alarms[j]['id']);
                                        }
                                        var graphOptions = {};
                                        graphOptions.sdp_id = productId;
                                        graphOptions.alarm_ids = alarmsArray;
                                        
                                        Ext.Ajax.request({
                                            url: 'proxy/alarm_list.php',
                                            async: false,
                                            params : {
                                                task : 'GET_ALARMS_NAMES',
                                                params : Ext.encode(graphOptions)
                                            },
                                            success: function(response) {
                                                // Add the alarms in the combobox
                                                var alarm_names_obj = Ext.decode(response.responseText);
                                                var loaded = false;
                                                
                                                var alarmscheckedstorelight = Ext.getStore('calcAlarmStore');
                                                var gridAlarmsCalculation = Ext.getCmp('penalitiesCriteria_configChart');
                                                var alarms_array = new Array();
                                
                                                Ext.Object.each(alarm_names_obj, function(index, obj) {
                                                    var obj = {
                                                        id : obj.alarm_id,
                                                        name : obj.alarm_name
                                                    }; 
                                                    alarms_array.push(obj);
                                                    
                                                });
                                                alarmscheckedstorelight.loadData(alarms_array);
                                                
                                                Ext.Ajax.request({
                                                    url: 'proxy/configuration.php',
                                                    params: {
                                                        task: 'GET_ALARMS',
                                                        product: productId
                                                    },
                                            
                                                    success: function(response) {
                                                        // Add the alarms in the combobox
                                                        // Add the alarms in the combobox
                                                        var alarmscheckedstore = Ext.getCmp('penalitiesCriteria_configChart').getStore();
                                                        var gridAlarmsCalculation = Ext.getCmp('penalitiesCriteria_configChart');
                                                        alarmscheckedstore.removeAll();
                                                        var alarms = Ext.decode(response.responseText).alarm;
                                                        for (var i = 0; i < alarms.length; i++) {
                                                            alarms.active=true;
                                                        }
                                                        
                                                        alarmscheckedstore.loadData(alarms);
                                                        alarmscheckedstore.sort('label', 'ASC');
                                                        //alarmscheckedstore.enable(true);
                                                        
                                                        for (var i = 0; i < alarms_array.length; i++) {
                                                            /**
                                                            var index = Ext.StoreMgr.lookup('penaltiescriteriaStore').findExact('id',alarms_array[i]['id']);
                                                            var record = Ext.StoreMgr.lookup('penaltiescriteriaStore').getAt(index);
                                                            **/
                                                            var record = alarmscheckedstore.findRecord('id',alarms_array[i]['id']);
                                                                if(record != null){
                                                                    record.set('checked',true);
                                                                    record.commit();
                                                                }
                                                        }
                                                        gridAlarmsCalculation.bindStore(alarmscheckedstore);
                                                        //gridAlarmsCalculation.reconfigure(alarmscheckedstore);
                                                        //gridAlarmsCalculation.getView().refresh();
                                                        
                                                            
                                                    }
                                                });
            
                                               }
                                            
                                        });
                                    }else{
                                        var alarmscheckedstore = Ext.getCmp('penalitiesCriteria_configChart').getStore();
                                        alarmscheckedstore.removeAll();
                                    }
                                    //History
                                    var history = config.widgets.widget[0].history;
                                    if (typeof(history) !== 'undefined') {
                                        if (typeof(history) != 'string')
                                            history = '3';
                                        Ext.getCmp('HistoryDisplayed_configChart').originalValue = history;
                                        Ext.getCmp('HistoryDisplayed_configChart').setValue(history);
                                    }
                                        
                                    selectedmode = config['@attributes'].selectedmode;
                                    penalizationRatioValue = config['@attributes'].ratio * 100;
                                    penalizationNbDaysValue = config['@attributes'].nbdays ;
                                        if (selectedmode == '1'){
                                            Ext.getCmp('alarmPenalizationModeRatio_configChart').setValue(true);
                                        }
                                        else if (selectedmode == '2'){
                                            Ext.getCmp('alarmPenalizationModeNbdays_configChart').setValue(true);
                                        }   
                                            
                                        if (typeof(penalizationRatioValue) != 'number')
                                            penalizationRatioValue = 50;
                                        Ext.getCmp('alarmRatioNumberfield_configChart').originalValue = penalizationRatioValue;
                                        Ext.getCmp('alarmRatioNumberfield_configChart').setValue(penalizationRatioValue);
        
                                        if (typeof(penalizationNbDaysValue) != 'string')
                                            penalizationNbDaysValuke = 15;
                                        Ext.getCmp('alarmNbDaysNumberfield_configChart').originalValue = penalizationNbDaysValue;
                                        Ext.getCmp('alarmNbDaysNumberfield_configChart').setValue(penalizationNbDaysValue);
                                                    
                                    
                                    
                                    //Graphs management
                                    if (typeof(config.widgets.widget[0].graph_list) !== 'undefined') {
                                        var graph_list = config.widgets.widget[0].graph_list.graph;
                                        var alarmsList = "";
                                        var graphStore = Ext.getCmp('graphsTable_configChart').getStore();
                                        graphStore.removeAll();
                                        
                                        if(Ext.Object.getSize(graph_list.alarms_display)> 0){
                                            Ext.Ajax.request({
                                                url: 'proxy/configuration.php',
                                                params: {
                                                    task: 'GET_ALARMS',
                                                    product: productId
                                                },
                                        
                                                success: function(response) {
                                                    // Add the alarms in the combobox
                                                    var alarms = Ext.decode(response.responseText).alarm;
                                                    for (var i = 0; i < alarms.length; i++) {
                                                        alarms.active=true;
                                                    }
                                                    var alarmComboConfig = Ext.getCmp('AlarmCombo_configChart_ar');                                                         
                                                    graphStore = Ext.getStore('graphstore');
                                                    var selection = Ext.getCmp('graphsTable_configChart').getSelectionModel().getSelection()[0];
                                                    //var record = graphsStore.findRecord('id', selection.data.id);
                                                    //record.data.piechart = false; 
                                                    alarmComboConfig.store.loadData(alarms);
                                                    alarmComboConfig.store.sort('label', 'ASC');
                                                    //alarmComboConfig.enable(true);
                                                }
                                            });
                                        }
                                        var alarmsStore = Ext.getCmp('alarmsGrid_configChart_ar').getStore();
                                        alarmsStore.removeAll();
                                        //if only one alarm
                                        if(Ext.Object.getSize(graph_list.alarms_display)==1){
                                            var currentAlarmsDisplayed = graph_list['alarms_display']['id'];
                                            
                                            
                                            if(typeof(currentAlarmsDisplayed)=='string'){
                                                var temp=new Array();
                                                temp.push(currentAlarmsDisplayed);
                                                currentAlarmsDisplayed=temp;
                                            }
                                            
                                            var alarmsList = "";
                                            //alarmsList = currentAlarmsDisplayed;
                                            
                                            for(var l = 0; l < currentAlarmsDisplayed.length; l++){
                                                if(alarmsList == ""){
                                                    alarmsList = currentAlarmsDisplayed[l];
                                                }else{
                                                    alarmsList = alarmsList+","+currentAlarmsDisplayed[l];
                                                }
                                            }
                                            
                                            var idGraph = graph_list['@attributes']['id'];
                                            
                                            var pos = idGraph.indexOf('_');
                                            var labelGraph = 'Graph alarm '+ idGraph.substr(pos+1,idGraph.length-pos);
                                            
                                            var obj = {};
                                            var currentGraphArray = new Array();
                                            obj.id =  idGraph;
                                            obj.label = labelGraph;
                                            obj.ag_name = graph_list['name'];
                                            obj.displayed_alarms = alarmsList;
                                            obj.piechart = graph_list['pie_chart'];
                                            
                                            currentGraphArray.push(obj);
                                            graphStore.loadData(currentGraphArray, true);
                                            graphStore.sort('label', 'ASC');
                                            alarmsStore.sort('label', 'ASC');
                                        }
                                        else{
                                            for (var k = 0; k < graph_list.length; k++) {
                                                var currentAlarmsDisplayed = graph_list[k]['alarms_display']['id'];
                                                var alarmsList = "";
                                                for(var l = 0; l < currentAlarmsDisplayed.length; l++){
                                                    if(alarmsList == ""){
                                                        alarmsList = currentAlarmsDisplayed[l];
                                                    }else{
                                                        alarmsList = alarmsList+","+currentAlarmsDisplayed[l];
                                                    }
                                                }
                                                var idGraph = graph_list[k]['@attributes']['id'];
                                                
                                                var pos = idGraph.indexOf('_');
                                                var labelGraph = 'Graph alarm '+ idGraph.substr(pos+1,idGraph.length-pos);
                                                
                                                var obj = {};
                                                var currentGraphArray = new Array();
                                                obj.id =  idGraph;
                                                obj.label = labelGraph;
                                                obj.ag_name = graph_list[k]['name'];
                                                obj.displayed_alarms = alarmsList;
                                                obj.piechart = graph_list[k]['pie_chart'];
                                                
                                                currentGraphArray.push(obj);
                                                graphStore.loadData(currentGraphArray, true);
                                                graphStore.sort('label', 'ASC');
                                                alarmsStore.sort('label', 'ASC');
                                                
                                            }
                                        }
                                    }
                                    
                                }
        
                                // GAUGE
        
                                //day as default time level
                                var time_unit = 'day';
                                var time_date = null;
                                var time_hour = null;
                                var dateVisible = false;
                                if (typeof(config['widgets']['widget'][i]['time']) !== 'undefined') {
                                    // Time unit
                                    time_unit = config['widgets']['widget'][i]['time']['time_unit'];
                                    if (typeof(time_unit) != 'string')
                                        time_unit = 'day';
        
                                    // Date
                                    dateString = config['widgets']['widget'][i]['time']['date'];
                                    if (typeof(dateString) != 'string') {
                                        time_date = null;
                                    } else {
                                        time_date = new Date(dateString);
                                    }
        
                                    // Hour
                                    hourString = config['widgets']['widget'][i]['time']['hour'];
                                    if (typeof(hourString) != 'string') {
                                        time_hour = null;
                                    } else {
                                        time_hour = new Date(hourString);
                                    }
        
                                    // Display labels
                                    dateVisible = config['widgets']['widget'][i]['time']['labels_visible'];
                                    if (typeof(dateVisible) != 'string')
                                        dateVisible = false;
                                }
        
                                configChart
                                        .down('combobox[id="timeUnitCombo_configChart"]').originalValue = time_unit;
                                configChart
                                        .down('combobox[id="timeUnitCombo_configChart"]')
                                        .setValue(time_unit);
                                configChart.down('datefield[id="date_configChart"]').originalValue = time_date;
                                configChart.down('datefield[id="date_configChart"]')
                                        .setValue(time_date);
                                configChart.down('timefield[id="time_configChart"]').originalValue = time_hour;
                                configChart.down('timefield[id="time_configChart"]')
                                        .setValue(time_hour);
                                configChart
                                        .down('checkboxfield[id="dateDisplayBox_configChart"]').originalValue = dateVisible;
                                configChart
                                        .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                        .setValue(dateVisible);
        
                                // TREND
        
                                // Get the trend widget configuration
                                var found = false;
                                //day as default time level
                                var trendTimeUnit = 'day';
                                var trendUnitsNumber = '';
                                counterId = '';
                                productId = '';
                                counterProductLabel = '';
                                counterLabel = '';
                                type = '';
                                unit = '';
                                for (var j = 0; j < config['widgets']['widget'].length; j++) {
                                    // Get the widget linked to the actual widget
                                    if (config['widgets']['widget'][j]['@attributes']['id'] == config['widgets']['widget'][i]['widget_links']) {
        
                                        // Time unit
                                        trendTimeUnit = config['widgets']['widget'][j]['time']['time_unit'];
                                        if (typeof(trendTimeUnit) != 'string')
                                            trendTimeUnit = 'day';
        
                                        // Number of period
                                        trendUnitsNumber = config['widgets']['widget'][j]['time']['units_number'];
                                        if (typeof(trendUnitsNumber) != 'string')
                                            trendUnitsNumber = '';
        
                                        if (config['widgets']['widget'][j]['kpis']['kpi'].length >= 2) {
                                            // Counter
                                            counterId = config['widgets']['widget'][j]['kpis']['kpi'][1]['id'];
                                            if (typeof(counterId) != 'string')
                                                counterId = '';
        
                                            productId = config['widgets']['widget'][j]['kpis']['kpi'][1]['product_id'];
                                            if (typeof(productId) != 'string')
                                                productId = '';
        
                                            counterProductLabel = config['widgets']['widget'][j]['kpis']['kpi'][1]['product_label'];
                                            if (typeof(counterProductLabel) != 'string')
                                                counterProductLabel = '';
        
                                            counterLabel = config['widgets']['widget'][j]['kpis']['kpi'][1]['label'];
                                            if (typeof(counterLabel) != 'string')
                                                counterLabel = '';
        
                                            type = config['widgets']['widget'][j]['kpis']['kpi'][1]['type'];
                                            if (typeof(type) != 'string')
                                                type = '';
                                        }
        
                                        if (config['widgets']['widget'][j]['axis_list']['axis'].length >= 2) {
                                            // Unit
                                            unit = config['widgets']['widget'][j]['axis_list']['axis'][1]['unit'];
                                            if (typeof(unit) != 'string')
                                                unit = '';
                                        }
        
                                        found = true;
                                        break;
                                    }
                                }
                                configChart
                                        .down('combobox[id="trendTimeUnitCombo_configChart"]').originalValue = trendTimeUnit;
                                configChart
                                        .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                        .setValue(trendTimeUnit);
                                configChart
                                        .down('numberfield[id="trendPeriodField_configChart"]').originalValue = trendUnitsNumber;
                                configChart
                                        .down('numberfield[id="trendPeriodField_configChart"]')
                                        .setValue(trendUnitsNumber);
                                configChart
                                        .down('hiddenfield[id="trendCounterId_configChart"]').originalValue = counterId;
                                configChart
                                        .down('hiddenfield[id="trendCounterId_configChart"]')
                                        .setValue(counterId);
                                configChart
                                        .down('hiddenfield[id="trendCounterProductId_configChart"]').originalValue = productId;
                                configChart
                                        .down('hiddenfield[id="trendCounterProductId_configChart"]')
                                        .setValue(productId);
                                configChart
                                        .down('hiddenfield[id="trendCounterProductLabel_configChart"]').originalValue = counterProductLabel;
                                configChart
                                        .down('hiddenfield[id="trendCounterProductLabel_configChart"]')
                                        .setValue(counterProductLabel);
                                configChart
                                        .down('hiddenfield[id="trendCounterLabel_configChart"]').originalValue = counterLabel;
                                configChart
                                        .down('hiddenfield[id="trendCounterLabel_configChart"]')
                                        .setValue(counterLabel);
                                configChart
                                        .down('hiddenfield[id="trendCounterType_configChart"]').originalValue = type;
                                configChart
                                        .down('hiddenfield[id="trendCounterType_configChart"]')
                                        .setValue(type);
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]').originalValue = unit;
                                configChart
                                        .down('textfield[id="trendUnitField_configChart"]')
                                        .setValue(unit);
        
                                var trendCounterButton = configChart
                                        .down('button[id="trendCounterButton_configChart"]');
                                if (counterId != '') {
                                    trendCounterButton
                                            .addCls('x-button-counter-select-ok');
                                    trendCounterButton
                                            .removeCls('x-button-counter-select');
                                    trendCounterButton.setTooltip(counterProductLabel
                                            + ' - ' + counterLabel);
                                } else {
                                    trendCounterButton
                                            .removeCls('x-button-counter-select-ok');
                                    trendCounterButton
                                            .addCls('x-button-counter-select');
                                    trendCounterButton.setTooltip(counterProductLabel
                                            + ' - ' + counterLabel);
                                }
                            
                            }   
                            
                        }
                    }
                }
    
                // unhide save and display buttons after config load
                Ext.getCmp('displayButton').show();
    
                // TODO remove once audit report is finished
                //if (template != 'template9') {
                    Ext.getCmp('saveButton').show();
                //}
                Ext.getCmp('addAlarmButton_configChart').show();
            }
    
            if (layout != false /* && template != 'template4' */) {
                // Get the Ext JS component
                var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
                Ext.getCmp(tabId).fireEvent('displayCharts', config);
               
            }
    
        },
        
        // Make a configuration object for the gauge from the configuration panel
        // and call displayCharts()
        makeConfig : function() {
            me = this;
            // Selected chart
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
            var selectedChart = Ext.getCmp(tabId).selectedChart;
            // Panels
            var configPanel = Ext.getCmp('configPanel');
            var configChart = Ext.getCmp('configChart');
            var configTab = Ext.getCmp('configTab');
    
            var config = new Array(3);
    
            // Tab informations
            var tabTitle = configTab.down('textfield[id="titleField_configTab"]').getValue();
            if(tabTitle != null){
                tabTitle = this.escapeXml(tabTitle);
            }
            config['title'] = tabTitle;
            var template = configTab.down('combobox[id="templateCombo_configTab"]').getValue();
            
            if(template == 'template7' || template == 'template9' || template == 'template10'){
                config['@attributes'] = new Array(4);
                config['@attributes']['id'] = configPanel.tab;
                config['@attributes']['ratio'] = (Ext.getCmp('alarmRatioNumberfield_configChart').getValue() / 100).toFixed(2);
                config['@attributes']['nbdays'] = (Ext.getCmp('alarmNbDaysNumberfield_configChart').getValue());
                var selectedMode = Ext.getCmp('alarmPenalizationMode_configChart').getValue();
                config['@attributes']['selectedmode'] = (selectedMode['radioPenalizationMode']);
            }
    
            // Charts informations
            config['widgets'] = new Array(1);
    
            var widgetsNb = 1;
            //if (template == 'template5')
                //widgetsNb = 2;
            config['widgets']['widget'] = new Array(widgetsNb);
    
            config['widgets']['widget'][0] = new Array(9);
            config['widgets']['widget'][0]['@attributes'] = new Array(1);
            config['widgets']['widget'][0]['@attributes']['id'] = selectedChart
                    .substr(selectedChart.lastIndexOf('_') + 1);
            config['widgets']['widget'][0]['function'] = 'detail';
    
            // Title
            var title = configChart.down('textfield[id="titleField_configChart"]')
                    .getValue();
            config['widgets']['widget'][0]['title'] = title != null ? title : '';
    
            // Url
            var url = configChart.down('textfield[id="urlField_configChart"]')
                    .getValue();
            config['widgets']['widget'][0]['url'] = url != null ? url : '';
    
            // Function
            var func = configChart.down('combobox[id="typeCombo_configChart"]')
                    .getValue();
            config['widgets']['widget'][0]['type'] = func != null ? func : '';
    
            if (template != 'template7' && template != 'template9' && template != 'template10' && template != 'template5') {
                config['widgets']['widget'][0]['kpis'] = new Array(2);
    
                // Raw/KPI label visible
                var counterLabelVisible = configChart
                        .down('checkboxfield[id="counterDisplayBox_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['kpis']['labels_visible'] = counterLabelVisible != null
                        ? counterLabelVisible
                        : '';
    
                config['widgets']['widget'][0]['kpis']['kpi'] = new Array(4);
    
                // Raw/KPI ID
                var counterId = configChart
                        .down('hiddenfield[id="counterId_configChart"]').getValue();
                config['widgets']['widget'][0]['kpis']['kpi']['id'] = counterId != null
                        ? counterId
                        : '';
    
                // Raw/KPI product ID
                var counterProductId = configChart
                        .down('hiddenfield[id="counterProductId_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['kpis']['kpi']['product_id'] = counterProductId != null
                        ? counterProductId
                        : '';
    
                // Raw/KPI type
                var counterType = configChart
                        .down('hiddenfield[id="counterType_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['kpis']['kpi']['type'] = counterType != null
                        ? counterType
                        : '';
    
                // Raw/KPI function
                var counterFunction = configChart
                        .down('combobox[id="typeCombo_configChart"]').getValue();
                config['widgets']['widget'][0]['kpis']['kpi']['function'] = counterFunction != null
                        ? counterFunction
                        : '';
    
                config['widgets']['widget'][0]['time'] = new Array(4);
    
                // Time label visible
                var timeLabelVisible = configChart
                        .down('checkboxfield[id="dateDisplayBox_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['time']['labels_visible'] = timeLabelVisible != null
                        ? timeLabelVisible
                        : '';
    
                // Time unit
                var timeUnit = configChart
                        .down('combobox[id="timeUnitCombo_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['time']['time_unit'] = timeUnit != null
                        ? timeUnit
                        : 'day';
    
                // Time date
                var timeDate = configChart.down('datefield[id="date_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['time']['date'] = timeDate != null
                        ? timeDate
                        : '';
    
                // Time hour
                var timeHour = configChart.down('timefield[id="time_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['time']['hour'] = timeHour != null
                        ? timeHour
                        : '';
    
                config['widgets']['widget'][0]['network_elements'] = new Array(3);
    
                // NE label visible
                var neLabelVisible = configChart
                        .down('checkboxfield[id="neDisplayBox_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['network_elements']['labels_visible'] = neLabelVisible != null
                        ? neLabelVisible
                        : '';
    
                config['widgets']['widget'][0]['network_elements']['ne'] = new Array(5);
    
                // NE ID
                var neId = configChart.down('hiddenfield[id="neId_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['network_elements']['ne']['id'] = neId != null
                        ? neId
                        : '';
    
                // NE level
                var neLevelId = configChart
                        .down('hiddenfield[id="neLevelId_configChart"]').getValue();
                config['widgets']['widget'][0]['network_elements']['ne']['network_level'] = neLevelId != null
                        ? neLevelId
                        : '';
                        
                // NE level2 template map
                var neLevelId2 = configChart
                        .down('hiddenfield[id="neLevelId2_configChart"]').getValue();
                config['widgets']['widget'][0]['network_elements']['ne']['network_level2'] = neLevelId2 != null
                        ? neLevelId2
                        : '';
    
                // NE product ID
                var neProductId = configChart
                        .down('hiddenfield[id="neProductId_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['network_elements']['ne']['product_id'] = neProductId != null
                        ? neProductId
                        : '';
    
                // NE label
                var neLabel = configChart
                        .down('hiddenfield[id="neLabel_configChart"]').getValue();
                config['widgets']['widget'][0]['network_elements']['ne']['label'] = neLabel != null
                        ? neLabel
                        : '';
    
                // NE level label
                var neLevelLabel = configChart
                        .down('hiddenfield[id="neLevelLabel_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['network_elements']['ne']['network_level_label'] = neLevelLabel != null
                        ? neLevelLabel
                        : '';
    
                config['widgets']['widget'][0]['network_elements']['ne2'] = new Array(3);
    
                // NE 2 ID
                var neId2 = configChart.down('hiddenfield[id="neId2_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['network_elements']['ne2']['id'] = neId2 != null
                        ? neId2
                        : '';
    
                // NE 2 level
                var neLevelId2 = configChart
                        .down('hiddenfield[id="neLevelId2_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['network_elements']['ne2']['network_level'] = neLevelId2 != null
                        ? neLevelId2
                        : '';
    
                // NE 2 product ID
                var neProductId2 = configChart
                        .down('hiddenfield[id="neProductId2_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['network_elements']['ne2']['product_id'] = neProductId2 != null
                        ? neProductId2
                        : '';
    
                config['widgets']['widget'][0]['axis_list'] = new Array(1);
                config['widgets']['widget'][0]['axis_list']['axis'] = new Array(3);
    
                config['widgets']['widget'][0]['axis_list'] = new Array(1);
                config['widgets']['widget'][0]['axis_list']['axis'] = new Array(3);
    
                // Axis unit
                var axisUnit = configChart
                        .down('textfield[id="unitField_configChart"]').getValue();
                config['widgets']['widget'][0]['axis_list']['axis']['unit'] = axisUnit != null
                        ? axisUnit
                        : '';
    
                // Axis thresholds
                config['widgets']['widget'][0]['axis_list']['axis']['thresholds'] = new Array(2);
                var lowThreshold = configChart
                        .down('numberfield[id="thresholdMinField_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['axis_list']['axis']['thresholds']['low_threshold'] = lowThreshold != null
                        ? lowThreshold
                        : '';
                var highThreshold = configChart
                        .down('numberfield[id="thresholdMaxField_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['axis_list']['axis']['thresholds']['high_threshold'] = highThreshold != null
                        ? highThreshold
                        : '';
    
                // Axis zoom
                config['widgets']['widget'][0]['axis_list']['axis']['zoom'] = new Array(3);
                var dynamic = configChart
                        .down('checkboxfield[id="dynamicBox_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['axis_list']['axis']['zoom']['dynamic'] = dynamic != null
                        ? dynamic
                        : '';
                var minZoom = configChart
                        .down('numberfield[id="scaleMinField_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['axis_list']['axis']['zoom']['min_value'] = minZoom != null
                        ? minZoom
                        : '';
                var maxZoom = configChart
                        .down('numberfield[id="scaleMaxField_configChart"]')
                        .getValue();
                config['widgets']['widget'][0]['axis_list']['axis']['zoom']['max_value'] = maxZoom != null
                        ? maxZoom
                        : '';
    
    
            } else if (template == 'template7') {
                // Specific cell surveillance
                config['template'] = 'template7';
    
                // Product ID
                config['widgets']['widget'][0]['sdp_id'] = Ext
                        .getCmp('productCombo_configChart').getValue();
    
                // Minimum number of days
                config['widgets']['widget'][0]['minnumberofdays'] = Ext
                        .getCmp('alarmDayNumberfield_configChart').getValue();
    
                // Alarms
                config['widgets']['widget'][0]['alarms'] = new Array(1);
    
                var alarmStore = Ext.getCmp('AlarmGrid_configChart').getStore();
                config['widgets']['widget'][0]['alarms']['alarm'] = new Array(alarmStore.data.items.length);
                for (var d = 0; d < alarmStore.data.items.length; d++) {
                    config['widgets']['widget'][0]['alarms']['alarm'][d] = new Array(5);
    
                    config['widgets']['widget'][0]['alarms']['alarm'][d].id = alarmStore.data.items[d].data.id;
                    config['widgets']['widget'][0]['alarms']['alarm'][d].label = alarmStore.data.items[d].data.label;
                    config['widgets']['widget'][0]['alarms']['alarm'][d].grid_name = alarmStore.data.items[d].data.grid_name;
                    config['widgets']['widget'][0]['alarms']['alarm'][d].comment = encodeURIComponent(alarmStore.data.items[d].data.comment);
                    config['widgets']['widget'][0]['alarms']['alarm'][d].dashboard = alarmStore.data.items[d].data.dashboard;
                }
            } else if (template == 'template9' || template == 'template10') {
                 
                // Specific audit report
                if(template == 'template9'){
                    config['template'] = 'template9';
                }else{
                    config['template'] = 'template10';
                }
                
                // Product ID
                config['widgets']['widget'][0]['sdp_id'] = Ext.getCmp('productCombo_configChart_ar').getValue();
                
                //History
                var history = Ext.getCmp('HistoryDisplayed_configChart').getValue();
                if (history  != null){
                    config['widgets']['widget'][0]['history'] = history;
                }else{
                    config['widgets']['widget'][0]['history'] = 1;
                }
                
                // Alarms calculation
                config['widgets']['widget'][0]['calc_alarms'] = new Array(1);
                var calcAlarmStore = Ext.getStore('calcAlarmStore');
                var alarmsStoreChecked = Ext.getCmp('penalitiesCriteria_configChart').getStore();
    
                config['widgets']['widget'][0]['calc_alarms']['alarm'] = new Array(calcAlarmStore.data.items.length);
                for (var d = 0; d < calcAlarmStore.data.items.length; d++) {
                    config['widgets']['widget'][0]['calc_alarms']['alarm'][d] = new Array(2);
                    config['widgets']['widget'][0]['calc_alarms']['alarm'][d].id = calcAlarmStore.data.items[d].data.id;
                    config['widgets']['widget'][0]['calc_alarms']['alarm'][d].label = calcAlarmStore.data.items[d].data.label;
                    var record = alarmsStoreChecked.findRecord('id', calcAlarmStore.data.items[d].data.id);
                       record.set('checked',true);
                       record.commit();
    
                }
                
                //Alarms Graph
                config['widgets']['widget'][0]['graph_list'] = new Array(1);
                var graphStore = Ext.getStore('graphstore');
                var piechartDisplay = 0;
                config['widgets']['widget'][0]['graph_list']['graph'] = new Array(graphStore.data.items.length);
                for (var j = 0; j < graphStore.data.items.length; j++) {
                    var alarmsList = graphStore.data.items[j].data.displayed_alarms;
                    //remove last coma from label
    
                    var arrayAlarms = alarmsList.split(',');
                    
                    if(graphStore.data.items[j].data.piechart == true){
                        piechartDisplay = 1;
                    }else{
                        piechartDisplay = 0;
                    }
                    
                    
                    config['widgets']['widget'][0]['graph_list']['graph'][j] = new Array(6);
                    config['widgets']['widget'][0]['graph_list']['graph'][j]['@attributes'] = {};
                    config['widgets']['widget'][0]['graph_list']['graph'][j]['@attributes'].id = graphStore.data.items[j].data.id;
                    config['widgets']['widget'][0]['graph_list']['graph'][j].id = graphStore.data.items[j].data.id;
                    config['widgets']['widget'][0]['graph_list']['graph'][j].label = graphStore.data.items[j].data.label;
                    config['widgets']['widget'][0]['graph_list']['graph'][j].ag_name = graphStore.data.items[j].data.ag_name;
                    config['widgets']['widget'][0]['graph_list']['graph'][j]['alarms_display'] = {};
                    config['widgets']['widget'][0]['graph_list']['graph'][j]['alarms_display'].id = arrayAlarms;
                    config['widgets']['widget'][0]['graph_list']['graph'][j].type = 'alarm';
                    config['widgets']['widget'][0]['graph_list']['graph'][j].pie_chart = piechartDisplay;
                }
                
            } else if (template == 'template5') {
            // Specific map
            config['template'] = 'template5';
    
            var result=null;        
            //get mapfield store
            //var mapStoreData = Ext.getCmp('mapKpiGrid_configChart').store.getRange();
            //var associationStore = Ext.getStore('associationStoreMap');
            var neLevelAxe1 = Ext.getCmp('neLevelId_configMap').getValue();
            var neLevelAxe2 = Ext.getCmp('neLevelId2_configMap').getValue();
            var fullscreen_time_level = Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').getValue() ;
            var trend_time_level = Ext.getCmp('defaultTrendTimeLevelCombo_configMap').getValue() ;
            var donut_time_level = Ext.getCmp('defaultDonutTimeLevelCombo_configMap').getValue() ;
           
      
                                                 
            Ext.Ajax.request({
                url: 'proxy/configuration.php',
                async:false,
                params: {
                    task: 'LOAD',
                    tab: tabId
                },
    
                success: function (response) {
                    result=Ext.decode(response.responseText);
                    var axis = 1;
                    if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 2){
                            axis = 3;
                    }
                        if(typeof(result['widgets']['widget'][0]['kpi_groups']['group']) !== 'undefined'){
                                if(typeof(result['widgets']['widget'][0]['kpi_groups']['group']).length=='undefined'){
                                                    var saveconfig=result['widgets']['widget'][0]['kpi_groups']['group'];
                                                    result['widgets']['widget'][0]['kpi_groups']['group']=new Array(1);
                                                    result['widgets']['widget'][0]['kpi_groups']['group'][0]=new Array(1);
                                                    result['widgets']['widget'][0]['kpi_groups']['group'][0]=saveconfig;
                                            }
                        }
                                    
                    //TODO modifier les valeur r�cup�r� pour donut time level et trend time level
                    config['widgets']['widget'][0]['fullscreen_time_level']=fullscreen_time_level == "" ? "day" : fullscreen_time_level;
                    config['widgets']['widget'][0]['trend_time_level']=trend_time_level == "" ? "day" : trend_time_level;
                    config['widgets']['widget'][0]['donut_time_level']=donut_time_level == "" ? "week" : donut_time_level;
                    config['widgets']['widget'][0]['roaming']=result['widgets']['widget'][0]['roaming'];
                    config['widgets']['widget'][0]['displayed_value_mode']=result['widgets']['widget'][0]['displayed_value_mode'];
                    //config['widgets']['widget'][0]['roaming']= 'true';
                    config['widgets']['widget'][0]['fullscreen']=result['widgets']['widget'][0]['fullscreen'];
                    config['widgets']['widget'][0]['function']=result['widgets']['widget'][0]['function'];
                    config['widgets']['widget'][0]['units_number']=result['widgets']['widget'][0]['units_number'];
                    config['widgets']['widget'][0]['map_id']="";
                    config['widgets']['widget'][0]['map_zoom']=new Array(3);
                    config['widgets']['widget'][0]['map_zoom'].zoom_level=result['widgets']['widget'][0]['map_zoom']['zoom_level'];
                    config['widgets']['widget'][0]['map_zoom'].zoom_latitude=result['widgets']['widget'][0]['map_zoom']['zoom_latitude'];
                    config['widgets']['widget'][0]['map_zoom'].zoom_longitude=result['widgets']['widget'][0]['map_zoom']['zoom_longitude'];
                    config['widgets']['widget'][0]['home_zoom']=new Array(3);
                    config['widgets']['widget'][0]['home_zoom'].home_zoom_level=result['widgets']['widget'][0]['home_zoom']['home_zoom_level'];
                    config['widgets']['widget'][0]['home_zoom'].home_zoom_latitude=result['widgets']['widget'][0]['home_zoom']['home_zoom_latitude'];
                    config['widgets']['widget'][0]['home_zoom'].home_zoom_longitude=result['widgets']['widget'][0]['home_zoom']['home_zoom_longitude'];
                    
                    config['widgets']['widget'][0]['network_elements']=new Array(2);
                    config['widgets']['widget'][0]['network_elements'].network_level=result['widgets']['widget'][0]['network_elements']['network_level'];
                    config['widgets']['widget'][0]['network_elements'].network_level2=result['widgets']['widget'][0]['network_elements']['network_level2'] != "" ? result['widgets']['widget'][0]['network_elements']['network_level2'] : "";
                    if(result.widgets.widget[0].network_elements.network_element==undefined){
                        config['widgets']['widget'][0]['network_elements']['network_element']=new Array(1);
                    
                        config['widgets']['widget'][0]['network_elements']['network_element'][0]=new Array(4);
                        
                        config['widgets']['widget'][0]['network_elements']['network_element'][0]['ne_id']="";               
                        config['widgets']['widget'][0]['network_elements']['network_element'][0]['ne_id2']="";
                        config['widgets']['widget'][0]['network_elements']['network_element'][0]['product_id']="";                  
                        config['widgets']['widget'][0]['network_elements']['network_element'][0]['map_zone_id']="";
                    }
                    else{
                        config['widgets']['widget'][0]['network_elements']['network_element']=new Array(result['widgets']['widget'][0]['network_elements']['network_element'].length);
                        
                        for (var d = 0; d < result['widgets']['widget'][0]['network_elements']['network_element'].length; d++) {    
                            config['widgets']['widget'][0]['network_elements']['network_element'][d]=new Array(4);
                            config['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id']=result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id'];
                            config['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id2']=result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id2'] != "" ? result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id2'] : "";                
                            config['widgets']['widget'][0]['network_elements']['network_element'][d]['product_id']=result['widgets']['widget'][0]['network_elements']['network_element'][d]['product_id'];                  
                            config['widgets']['widget'][0]['network_elements']['network_element'][d]['map_zone_id']=result['widgets']['widget'][0]['network_elements']['network_element'][d]['map_zone_id'];
            
                        }
                    }
                }
                    
                
            });
           if(result['widgets']['widget'][0]['roaming'] == "false"){
                     var mapStoreData = Ext.getCmp('mapKpiGrid_configChart').store.getRange();
                     var productId = mapStoreData[0].data.trendkpiproductid;
                         config['widgets']['widget'][0]['kpi_groups'] = new Array(1);
                            
                         config['widgets']['widget'][0]['kpi_groups']['group'] = new Array(mapStoreData.length);
            }else{
                     var mapStoreData = Ext.getCmp('mapKpiGrid_configChart_roaming').store.getRange();
                     var productId = mapStoreData[0].data.trendkpiproductid;
                         config['widgets']['widget'][0]['kpi_groups'] = new Array(1);
                            
                         config['widgets']['widget'][0]['kpi_groups']['group'] = new Array(mapStoreData.length);
            }
            
            for (var d = 0; d < mapStoreData.length; d++) {
               
                config['widgets']['widget'][0]['kpi_groups']['group'][d] = new Array(2);
    
                config['widgets']['widget'][0]['kpi_groups']['group'][d].group_name = mapStoreData[d].data.groupname;
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis=new Array(1);
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend=new Array(11);
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.kpi_id = mapStoreData[d].data.trendkpiid;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.product_id = mapStoreData[d].data.trendkpiproductid;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.type = mapStoreData[d].data.typekpi;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.function = mapStoreData[d].data.trendkpifunction;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.label = mapStoreData[d].data.trendkpilabel;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.product_label = mapStoreData[d].data.trendproductlabel;
                
                if(result['widgets']['widget'][0]['roaming'] == "true"){
                        if(d+1 == mapStoreData.length){
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.network_axis_number = result['widgets']['widget'][0]['kpi_groups']['group'][d]['kpis']['kpi_trend']['network_axis_number'];
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_ne_id = result['widgets']['widget'][0]['kpi_groups']['group'][d]['kpis']['kpi_trend']['roaming_ne_id'];
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_ne_id2 = result['widgets']['widget'][0]['kpi_groups']['group'][d]['kpis']['kpi_trend']['roaming_ne_id2'];
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_network_level = result['widgets']['widget'][0]['kpi_groups']['group'][d]['kpis']['kpi_trend']['roaming_network_level'];
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_network_level2 = result['widgets']['widget'][0]['kpi_groups']['group'][d]['kpis']['kpi_trend']['roaming_network_level2']; 
                        }else{
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.network_axis_number = mapStoreData[d].data.networkaxisnumber;
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_ne_id = mapStoreData[d].data.roamingneid;
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_ne_id2 = mapStoreData[d].data.roamingneid2;
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_network_level = mapStoreData[d].data.roamingnetworklevel;
                                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_network_level2 = mapStoreData[d].data.roamingnetworklevel2;
                        }
                }else{
                        config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.network_axis_number = "";
                        config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_ne_id = "";
                        config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_ne_id2 = "";
                        config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_network_level = "";
                        config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_trend.roaming_network_level2 = "";
                }
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_donut=new Array(5);
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_donut.kpi_id = mapStoreData[d].data.donutkpiid;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_donut.product_id = mapStoreData[d].data.donutkpiproductid;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_donut.type = mapStoreData[d].data.typekpidonut;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_donut.label = mapStoreData[d].data.donutkpilabel;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].kpis.kpi_donut.product_label = mapStoreData[d].data.donutproductlabel;
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list=new Array(1);
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend=new Array(3);
            
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.unit=mapStoreData[d].data.trendunit;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.thresholds=new Array(2);
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.thresholds.low_threshold=mapStoreData[d].data.lowthreshold;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.thresholds.high_threshold=mapStoreData[d].data.highthreshold;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.zoom=new Array(3);
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.zoom.dynamic=mapStoreData[d].data.dynamic;
                
                // Product ID
                config['widgets']['widget'][0]['sdp_id'] = Ext.getCmp('productCombo_configChart_ar').getValue();
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.zoom.min_value=mapStoreData[d].data.dynamic==true ? '' : mapStoreData[d].data.minvalue;
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_trend.zoom.max_value=mapStoreData[d].data.dynamic==true ? '' : mapStoreData[d].data.maxvalue;
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_donut=new Array(1);
                
                config['widgets']['widget'][0]['kpi_groups']['group'][d].axis_list.axis_donut.unit=mapStoreData[d].data.donutunit;
    
                }
    
                }
    
            // Update the charts
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
            
            if (template != 'template9' || template != 'template10') {
                Ext.getCmp(tabId).fireEvent('displayCharts', config);
            } else {
                // fire click event on time selectore button
                Ext.getCmp(tabId + '_chart1').query('button')[0].fireEvent('click',config);
                
            }
    
            // Make the configuration for the trend graph
            if (template != 'template3' && template != 'template4'
                    && template != 'template5' && template != 'template6'
                    && template != 'template7' && template != 'template9' && template != 'template10'){
                configPanel.fireEvent('makeTrendConfig');
            }
        },
    
        // Make a configuration object for the trend chart from the configuration
        // panel
        makeTrendConfig : function() {
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
            var selectedGauge = Ext.getCmp(tabId).selectedChart;
            
            if (selectedGauge != null || typeof selectedGauge !== 'undefined'){
                // Config Chart
                var configChart = Ext.getCmp('configChart');
    
                var config = new Array(5);
    
                // Title
                var title = configChart
                        .down('textfield[id="titleField_configChart"]').getValue();
                config['title'] = title != null ? title : '';
    
                // Counters
                config['kpis'] = new Array(1);
                config['kpis']['kpi'] = new Array(2);
    
                // Main counter
                config['kpis']['kpi'][0] = new Array(4);
    
                var counterId = configChart
                        .down('hiddenfield[id="counterId_configChart"]').getValue();
                config['kpis']['kpi'][0]['id'] = counterId != null ? counterId : '';
    
                var counterProductId = configChart
                        .down('hiddenfield[id="counterProductId_configChart"]')
                        .getValue();
                config['kpis']['kpi'][0]['product_id'] = counterProductId != null
                        ? counterProductId
                        : '';
    
                var counterType = configChart
                        .down('hiddenfield[id="counterType_configChart"]')
                        .getValue();
                config['kpis']['kpi'][0]['type'] = counterType != null
                        ? counterType
                        : '';
    
                var counterFunction = configChart
                        .down('combobox[id="typeCombo_configChart"]').getValue();
                config['kpis']['kpi'][0]['function'] = counterFunction != null
                        ? counterFunction
                        : '';
    
                // Volume counter
                config['kpis']['kpi'][1] = new Array(4);
    
                counterId = configChart
                        .down('hiddenfield[id="trendCounterId_configChart"]')
                        .getValue();
                config['kpis']['kpi'][1]['id'] = counterId != null ? counterId : '';
    
                counterProductId = configChart
                        .down('hiddenfield[id="trendCounterProductId_configChart"]')
                        .getValue();
                config['kpis']['kpi'][1]['product_id'] = counterProductId != null
                        ? counterProductId
                        : '';
    
                counterType = configChart
                        .down('hiddenfield[id="trendCounterType_configChart"]')
                        .getValue();
                config['kpis']['kpi'][1]['type'] = counterType != null
                        ? counterType
                        : '';
    
                // Time
                config['time'] = new Array(2);
    
                var timeUnit = configChart
                        .down('combobox[id="trendTimeUnitCombo_configChart"]')
                        .getValue();
                config['time']['time_unit'] = timeUnit != null ? timeUnit : 'day';
    
                var unitsNumber = configChart
                        .down('numberfield[id="trendPeriodField_configChart"]')
                        .getValue();
                config['time']['units_number'] = unitsNumber != null
                        ? unitsNumber
                        : '';
    
                // Network element
                config['network_elements'] = new Array(1);
                config['network_elements']['ne'] = new Array(3);
                config['network_elements']['ne2'] = new Array(3);
    
                var neId = configChart.down('hiddenfield[id="neId_configChart"]')
                        .getValue();
                config['network_elements']['ne']['id'] = neId != null ? neId : '';
    
                var neLevelId = configChart
                        .down('hiddenfield[id="neLevelId_configChart"]').getValue();
                config['network_elements']['ne']['network_level'] = neLevelId != null
                        ? neLevelId
                        : '';
    
                var neProductId = configChart
                        .down('hiddenfield[id="neProductId_configChart"]')
                        .getValue();
                config['network_elements']['ne']['product_id'] = neProductId != null
                        ? neProductId
                        : '';
    
                config['network_elements']['ne2'] = new Array(3);
    
                var neId = configChart.down('hiddenfield[id="neId2_configChart"]')
                        .getValue();
                config['network_elements']['ne2']['id'] = neId != null ? neId : '';
    
                var neLevelId = configChart
                        .down('hiddenfield[id="neLevelId2_configChart"]')
                        .getValue();
                config['network_elements']['ne2']['network_level'] = neLevelId != null
                        ? neLevelId
                        : '';
    
                var neProductId = configChart
                        .down('hiddenfield[id="neProductId2_configChart"]')
                        .getValue();
                config['network_elements']['ne2']['product_id'] = neProductId != null
                        ? neProductId
                        : '';
    
                // Axis
                config['axis_list'] = new Array(1);
                config['axis_list']['axis'] = new Array(2);
    
                // Main axis
                config['axis_list']['axis'][0] = new Array(3);
    
                // Axis unit
                var axisUnit = configChart
                        .down('textfield[id="unitField_configChart"]').getValue();
                config['axis_list']['axis'][0]['unit'] = axisUnit != null
                        ? axisUnit
                        : '';
    
                // Axis thresholds
                config['axis_list']['axis'][0]['thresholds'] = new Array(2);
                var lowThreshold = configChart
                        .down('numberfield[id="thresholdMinField_configChart"]')
                        .getValue();
                config['axis_list']['axis'][0]['thresholds']['low_threshold'] = lowThreshold != null
                        ? lowThreshold
                        : '';
                var highThreshold = configChart
                        .down('numberfield[id="thresholdMaxField_configChart"]')
                        .getValue();
                config['axis_list']['axis'][0]['thresholds']['high_threshold'] = highThreshold != null
                        ? highThreshold
                        : '';
    
                // Axis zoom
                config['axis_list']['axis'][0]['zoom'] = new Array(3);
                var dynamic = configChart
                        .down('checkboxfield[id="dynamicBox_configChart"]')
                        .getValue();
                config['axis_list']['axis'][0]['zoom']['dynamic'] = dynamic != null
                        ? dynamic
                        : '';
                var minZoom = configChart
                        .down('numberfield[id="scaleMinField_configChart"]')
                        .getValue();
                config['axis_list']['axis'][0]['zoom']['min_value'] = minZoom != null
                        ? minZoom
                        : '';
                var maxZoom = configChart
                        .down('numberfield[id="scaleMaxField_configChart"]')
                        .getValue();
                config['axis_list']['axis'][0]['zoom']['max_value'] = maxZoom != null
                        ? maxZoom
                        : '';
    
                // Volume axis
                config['axis_list']['axis'][1] = new Array(1);
    
                // Axis unit
                var axisUnit = configChart
                        .down('textfield[id="trendUnitField_configChart"]')
                        .getValue();
                config['axis_list']['axis'][1]['unit'] = axisUnit != null
                        ? axisUnit
                        : '';
    
                // Display the new configuration
                Ext.getCmp(Ext.getCmp(selectedGauge).target).fireEvent('load',
                        config, selectedGauge);
            }
        },
    
        // Set a configuration for each chart and display the datas
        displayCharts : function(config) {
            var me = this;
            // Get the Ext JS component
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
            var chartsPanel = Ext.getCmp(tabId);
    
            if ((typeof(chartsPanel) != 'undefined')
                    && typeof(config['widgets']) !== 'undefined') {
                if (typeof(config['widgets']['widget']) !== 'undefined') {
                    // Set the tab title
                    chartsPanel.setTitle(config['title']);
    
                    var targetsArray = new Array();
    
                    if (config['template'] == 'template7') {
                        var cellsSurveillance = chartsPanel
                                .query('cellssurveillance[id="' + tabId
                                        + '_chart1"]');
    
                        if (cellsSurveillance.length > 0) {
                            cellsSurveillance[0].fireEvent('load', config);
                        }
                    } else if (config['template'] == 'template9') {
                        var auditreport = chartsPanel.query('auditreport');
                        if (auditreport.length > 0) {
                            auditreport[0].fireEvent('load', config);
                        }
                    }else if (config['template'] == 'template10'){
                            
                            var auditreportevo = chartsPanel.query('auditreportevo');
                          
                            if (auditreportevo.length > 0) {
                                auditreportevo[0].fireEvent('load', config);
                            }   
                            
                    }else if (config['template'] == 'template5') {
                        
                        var map = chartsPanel.query('map[id="'
                                                    + tabId
                                                    + '_chart1'
                                                    + '"]');
    
                        if (map.length > 0) {
                            //only load conf from first widget
                            map[0].fireEvent('load', config['widgets']['widget'][0]);
                            
                        }
                    } else {
    
                        // Get the configuration for each chart
                        for (var i = 0; i < config['widgets']['widget'].length; i++) {
    
                            if (config['widgets']['widget'][i]['function'] == 'detail') {
                                // Get the Ext JS component
                                var gaugeArray = chartsPanel
                                        .query('gauge[id="'
                                                + tabId
                                                + '_'
                                                + config['widgets']['widget'][i]['@attributes']['id']
                                                + '"]');
    
                                if (gaugeArray.length > 0) {
                                    gaugeArray[0].fireEvent('load',
                                            config['widgets']['widget'][i]);
    
                                    // Get the selected chart (may be the default
                                    // here)
                                    var selectedGauge = chartsPanel.selectedChart;
                                    var target = Ext
                                            .getCmp(tabId
                                                    + '_'
                                                    + config['widgets']['widget'][i]['@attributes']['id']).target;
    
                                    // If the gauge is selected, display the period
                                    // chart
                                    if (tabId
                                            + '_'
                                            + config['widgets']['widget'][i]['@attributes']['id'] == selectedGauge) {
                                        // Get the trend configuration
                                        for (var j = 0; j < config['widgets']['widget'].length; j++) {
    
                                            if (config['widgets']['widget'][j]['@attributes']['id'] == config['widgets']['widget'][i]['widget_links']) {
                                                me.loadTrendConfig(null);
                                                targetsArray.push(target);
                                                break;
                                            }
                                        }
                                    } else {
                                        var found = false;
                                        for (var t = 0; t < targetsArray.length; t++) {
    
                                            if (targetsArray[t] == target)
                                                found = true;
    
                                            break;
                                        }
    
                                        if (!found) {
                                            if (config['template'] == "template2") {
                                                me
                                                        .loadTrendConfig(tabId
                                                                + '_'
                                                                + config['widgets']['widget'][i]['@attributes']['id']);
                                            } else {
                                                me.loadTrendConfig(selectedGauge);
                                            }
                                            targetsArray.push(target);
                                        }
                                    }
                                }
    
                                var frameArray = chartsPanel
                                        .query('frame[id="'
                                                + tabId
                                                + '_'
                                                + config['widgets']['widget'][i]['@attributes']['id']
                                                + '"]');
    
                                if (frameArray.length > 0) {
                                    frameArray[0].fireEvent('load',
                                            config['widgets']['widget'][i]);
                                }
    
    
    
                                var gridArray = chartsPanel
                                        .query('gridreport[id="'
                                                + tabId
                                                + '_'
                                                + config['widgets']['widget'][i]['@attributes']['id']
                                                + '"]');
    
                                if (gridArray.length > 0) {
                                    gridArray[0].fireEvent('load',
                                            config['widgets']['widget'][i]);
                                }
    
                            }
                        }
    
                    }
                }
            }
        },
    
        // Save the new configuration in the XML file
        updateConfig : function(button) {
            var me = this;
            
            // Config panel
            var panel = button.up('panel');
    
            // Disable the toolbar
            var toolbar = panel.down('toolbar');
    
            // Get the selected chart
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
    
            if (typeof(tabId) != 'undefined') {
    
                toolbar.disable();
                var selectedChart = Ext.getCmp(tabId).selectedChart;
                selectedChart = selectedChart.substr(selectedChart.lastIndexOf('_')
                        + 1);
                selectedTab = tabId.substr(tabId.lastIndexOf('_') + 1);
    
                // Get the panels config
                var configChart = Ext.getCmp('configChart');
                var configTab = Ext.getCmp('configTab');
    
                // If the template has changed
                if (Ext.getCmp('templateCombo_configTab').isDirty()) {
                    var template = configTab
                            .down('combobox[id="templateCombo_configTab"]')
                            .getValue();
    
                    Ext.Ajax.request({
                                url : 'proxy/configuration.php',
                                params : {
                                    task : 'SAVE_TEMPLATE',
                                    tab : selectedTab,
                                    template : template
                                },
    
                                success : location.reload()
                            });
                } else {
                    var tabTitle = configTab
                            .down('textfield[id="titleField_configTab"]')
                            .getValue();
                    if(tabTitle != null){
                        tabTitle = this.escapeXml(tabTitle);
                    }
                    configTab.down('textfield[id="titleField_configTab"]')
                            .resetOriginalValue();
    
                    var template = configTab
                            .down('combobox[id="templateCombo_configTab"]')
                            .getValue();
                    configTab.down('combobox[id="templateCombo_configTab"]')
                            .resetOriginalValue();
    
                    // Set the new configuration
                    var xml = '<widget id="' + selectedChart + '">';
                    // This second string is used for the trend widget configuration
                    var xml2 = '<widget id="' + selectedChart + '_trend">';
    
                    // Title
                    var titleNode = '<title>';
                    title = configChart
                            .down('textfield[id="titleField_configChart"]')
                            .getValue();
                    if(title != null){
                        title = this.escapeXml(title); 
                    }
                    configChart.down('textfield[id="titleField_configChart"]')
                            .resetOriginalValue();
                    titleNode += title != null ? title : '';
                    
                    titleNode += '</title>';
    
                    xml += titleNode;
                    xml2 += titleNode;
    
                    // Url
                    var urlNode = '<url>';
                    var url = configChart
                            .down('textfield[id="urlField_configChart"]')
                            .getValue();
                    configChart.down('textfield[id="urlField_configChart"]')
                            .resetOriginalValue();
    
                    urlNode += url != null ? url : '';
                    urlNode += '</url>';
                    xml += urlNode;
    
                    // Function
                    xml += '<function>detail</function>';
                    xml2 += '<function>trend</function>';
    
                    if (template != 'template7' && template != 'template9' && template != 'template10' && template != 'template5') {
                        // Widget link
                        xml += '<widget_links>' + selectedChart
                                + '_trend</widget_links>';
    
                        // KPIS
                        xml += '<kpis>';
                        xml2 += '<kpis>';
    
                        xml += '<labels_visible>';
                        var kpiVisible = configChart
                                .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                .getValue();
                        configChart
                                .down('checkboxfield[id="counterDisplayBox_configChart"]')
                                .resetOriginalValue();
                        xml += kpiVisible ? 'true' : 'false';
                        xml += '</labels_visible>';
    
                        // Main Kpi
                        var mainKpiNode = '<kpi>';
    
                        mainKpiNode += '<id>';
                        var kpiId = configChart
                                .down('hiddenfield[id="counterId_configChart"]')
                                .getValue();
                        configChart.down('hiddenfield[id="counterId_configChart"]')
                                .resetOriginalValue();
                        mainKpiNode += kpiId != null ? kpiId : '';
                        mainKpiNode += '</id>';
    
                        mainKpiNode += '<product_id>';
                        var kpiProductId = configChart
                                .down('hiddenfield[id="counterProductId_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="counterProductId_configChart"]')
                                .resetOriginalValue();
                        mainKpiNode += kpiProductId != null ? kpiProductId : '';
                        mainKpiNode += '</product_id>';
    
                        mainKpiNode += '<type>';
                        var type = configChart
                                .down('hiddenfield[id="counterType_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="counterType_configChart"]')
                                .resetOriginalValue();
                        mainKpiNode += type != null ? type : '';
                        mainKpiNode += '</type>';
    
                        mainKpiNode += '<function>';
                        var func = configChart
                                .down('combobox[id="typeCombo_configChart"]')
                                .getValue();
                        configChart.down('combobox[id="typeCombo_configChart"]')
                                .resetOriginalValue();
                        mainKpiNode += func != null ? func : '';
                        mainKpiNode += '</function>';
    
                        mainKpiNode += '<label>';
                        kpiLabel = configChart
                                .down('hiddenfield[id="counterLabel_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="counterLabel_configChart"]')
                                .resetOriginalValue();
                        mainKpiNode += kpiLabel != null ? kpiLabel : '';
                        mainKpiNode += '</label>';
    
                        mainKpiNode += '<product_label>';
                        var kpiProductLabel = configChart
                                .down('hiddenfield[id="counterProductLabel_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="counterProductLabel_configChart"]')
                                .resetOriginalValue();
                        mainKpiNode += kpiProductLabel != null
                                ? kpiProductLabel
                                : '';
                        mainKpiNode += '</product_label>';
    
                        mainKpiNode += '</kpi>';
    
                        xml += mainKpiNode;
                        xml2 += mainKpiNode;
    
                        // Second Kpi (volume)
                        xml2 += '<kpi>';
    
                        xml2 += '<id>';
                        kpiId = configChart
                                .down('hiddenfield[id="trendCounterId_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="trendCounterId_configChart"]')
                                .resetOriginalValue();
                        xml2 += kpiId != null ? kpiId : '';
                        xml2 += '</id>';
    
                        xml2 += '<product_id>';
                        kpiProductId = configChart
                                .down('hiddenfield[id="trendCounterProductId_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="trendCounterProductId_configChart"]')
                                .resetOriginalValue();
                        xml2 += kpiProductId != null ? kpiProductId : '';
                        xml2 += '</product_id>';
    
                        xml2 += '<type>';
                        type = configChart
                                .down('hiddenfield[id="trendCounterType_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="trendCounterType_configChart"]')
                                .resetOriginalValue();
                        xml2 += type != null ? type : '';
                        xml2 += '</type>';
    
                        xml2 += '<label>';
                        kpiLabel = configChart
                                .down('hiddenfield[id="trendCounterLabel_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="trendCounterLabel_configChart"]')
                                .resetOriginalValue();
                        xml2 += kpiLabel != null ? kpiLabel : '';
                        xml2 += '</label>';
    
                        xml2 += '<product_label>';
                        kpiProductLabel = configChart
                                .down('hiddenfield[id="trendCounterProductLabel_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="trendCounterProductLabel_configChart"]')
                                .resetOriginalValue();
                        xml2 += kpiProductLabel != null ? kpiProductLabel : '';
                        xml2 += '</product_label>';
    
                        xml2 += '</kpi>';
    
                        xml += '</kpis>';
                        xml2 += '</kpis>';
    
                        // TIME
                        xml += '<time>';
                        xml2 += '<time>';
    
                        // Time labels visible
                        xml += '<labels_visible>';
                        var dateVisible = configChart
                                .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                .getValue();
                        configChart
                                .down('checkboxfield[id="dateDisplayBox_configChart"]')
                                .resetOriginalValue();
                        xml += dateVisible ? 'true' : 'false';
                        xml += '</labels_visible>';
    
                        // Time unit
                        xml += '<time_unit>';
                        var timeUnit = configChart
                                .down('combobox[id="timeUnitCombo_configChart"]')
                                .getValue();
                        configChart
                                .down('combobox[id="timeUnitCombo_configChart"]')
                                .resetOriginalValue();
                        xml += timeUnit != null ? timeUnit : 'day';
                        xml += '</time_unit>';
    
                        // Date
                        xml += '<date>';
                        var date = configChart
                                .down('datefield[id="date_configChart"]')
                                .getValue();
                        configChart.down('datefield[id="date_configChart"]')
                                .resetOriginalValue();
                        xml += date != null ? date : '';
                        xml += '</date>';
    
                        // Hour
                        xml += '<hour>';
                        var hour = configChart
                                .down('timefield[id="time_configChart"]')
                                .getValue();
                        configChart.down('timefield[id="time_configChart"]')
                                .resetOriginalValue();
                        xml += hour != null ? hour : '';
                        xml += '</hour>';
    
                        xml2 += '<time_unit>';
                        timeUnit = configChart
                                .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                .getValue();
                        configChart
                                .down('combobox[id="trendTimeUnitCombo_configChart"]')
                                .resetOriginalValue();
                        xml2 += timeUnit != null ? timeUnit : 'day';
                        xml2 += '</time_unit>';
    
                        // Number of units
                        xml2 += '<units_number>';
                        unitsNumber = configChart
                                .down('numberfield[id="trendPeriodField_configChart"]')
                                .getValue();
                        /**
                        if(unitsNumber != null){
                            unitsNumber = this.escapeXml(unitsNumber);
                        }**/
                        configChart
                                .down('numberfield[id="trendPeriodField_configChart"]')
                                .resetOriginalValue();
                        xml2 += unitsNumber != null ? unitsNumber : '';
                        xml2 += '</units_number>';
    
                        xml += '</time>';
                        xml2 += '</time>';
    
                        // NETWORK ELEMENTS
                        xml += '<network_elements>';
                        xml2 += '<network_elements>';
    
                        xml += '<labels_visible>';
                        var neVisible = configChart
                                .down('checkboxfield[id="neDisplayBox_configChart"]')
                                .getValue();
                        configChart
                                .down('checkboxfield[id="neDisplayBox_configChart"]')
                                .resetOriginalValue();
                        xml += neVisible ? 'true' : 'false';
                        xml += '</labels_visible>';
    
                        // First network
                        var neNode = '<ne>';
    
                        neNode += '<id>';
                        var neId = configChart
                                .down('hiddenfield[id="neId_configChart"]')
                                .getValue();
                        configChart.down('hiddenfield[id="neId_configChart"]')
                                .resetOriginalValue();
                        neNode += neId != null ? neId : '';
                        neNode += '</id>';
    
                        neNode += '<network_level>';
                        var level = configChart
                                .down('hiddenfield[id="neLevelId_configChart"]')
                                .getValue();
                        configChart.down('hiddenfield[id="neLevelId_configChart"]')
                                .resetOriginalValue();
                        neNode += level != null ? level : '';
                        neNode += '</network_level>';
                        
                        
                        
    
                        neNode += '<product_id>';
                        var neProductId = configChart
                                .down('hiddenfield[id="neProductId_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="neProductId_configChart"]')
                                .resetOriginalValue();
                        neNode += neProductId != null ? neProductId : '';
                        neNode += '</product_id>';
    
                        neNode += '<label>';
                        var neLabel = configChart
                                .down('hiddenfield[id="neLabel_configChart"]')
                                .getValue();
                        configChart.down('hiddenfield[id="neLabel_configChart"]')
                                .resetOriginalValue();
                        neNode += neLabel != null ? neLabel : '';
                        neNode += '</label>';
    
                        neNode += '<network_level_label>';
                        var neLevelLabel = configChart
                                .down('hiddenfield[id="neLevelLabel_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="neLevelLabel_configChart"]')
                                .resetOriginalValue();
                        neNode += neLevelLabel != null ? neLevelLabel : '';
                        neNode += '</network_level_label>';
    
                        neNode += '</ne>';
    
                        xml += neNode;
                        xml2 += neNode;
    
                        // Second network
                        neNode = '<ne2>';
    
                        neNode += '<id>';
                        neId = configChart
                                .down('hiddenfield[id="neId2_configChart"]')
                                .getValue();
                        configChart.down('hiddenfield[id="neId2_configChart"]')
                                .resetOriginalValue();
                        neNode += neId != null ? neId : '';
                        neNode += '</id>';
    
                        neNode += '<network_level>';
                        level = configChart
                                .down('hiddenfield[id="neLevelId2_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="neLevelId2_configChart"]')
                                .resetOriginalValue();
                        neNode += level != null ? level : '';
                        neNode += '</network_level>';
    
                        neNode += '<product_id>';
                        neProductId = configChart
                                .down('hiddenfield[id="neProductId2_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="neProductId2_configChart"]')
                                .resetOriginalValue();
                        neNode += neProductId != null ? neProductId : '';
                        neNode += '</product_id>';
    
                        neNode += '<label>';
                        neLabel = configChart
                                .down('hiddenfield[id="neLabel2_configChart"]')
                                .getValue();
                        configChart.down('hiddenfield[id="neLabel2_configChart"]')
                                .resetOriginalValue();
                        neNode += neLabel != null ? neLabel : '';
                        neNode += '</label>';
    
                        neNode += '<network_level_label>';
                        neLevelLabel = configChart
                                .down('hiddenfield[id="neLevelLabel2_configChart"]')
                                .getValue();
                        configChart
                                .down('hiddenfield[id="neLevelLabel2_configChart"]')
                                .resetOriginalValue();
                        neNode += neLevelLabel != null ? neLevelLabel : '';
                        neNode += '</network_level_label>';
    
                        neNode += '</ne2>';
    
                        xml += neNode;
                        xml2 += neNode;
    
                        xml += '</network_elements>';
                        xml2 += '</network_elements>';
    
                        // AXIS LIST
                        axisListNode = '<axis_list>';
                        axisListNode += '<axis>';
    
                        // Unit
                        axisListNode += '<unit>';
                        var unit = configChart
                                .down('textfield[id="unitField_configChart"]')
                                .getValue();
                        
                        if(unit != null){
                            unit = this.escapeXml(unit);
                        }
                        
                        configChart.down('textfield[id="unitField_configChart"]')
                                .resetOriginalValue();
                        axisListNode += unit != null ? unit : '';
                        axisListNode += '</unit>';
    
                        // Thresholds
                        axisListNode += '<thresholds>';
    
                        axisListNode += '<low_threshold>';
                        var low_threshold = configChart
                                .down('numberfield[id="thresholdMinField_configChart"]')
                                .getValue();
                        configChart
                                .down('numberfield[id="thresholdMinField_configChart"]')
                                .resetOriginalValue();
                        axisListNode += low_threshold != null ? low_threshold : '';
                        axisListNode += '</low_threshold>';
    
                        axisListNode += '<high_threshold>';
                        var high_threshold = configChart
                                .down('numberfield[id="thresholdMaxField_configChart"]')
                                .getValue();
                        configChart
                                .down('numberfield[id="thresholdMaxField_configChart"]')
                                .resetOriginalValue();
                        axisListNode += high_threshold != null
                                ? high_threshold
                                : '';
                        axisListNode += '</high_threshold>';
    
                        axisListNode += '</thresholds>';
    
                        // Zoom
                        axisListNode += '<zoom>';
    
                        axisListNode += '<dynamic>';
                        var dynamic = configChart
                                .down('checkboxfield[id="dynamicBox_configChart"]')
                                .getValue();
                        configChart
                                .down('checkboxfield[id="dynamicBox_configChart"]')
                                .resetOriginalValue();
                        axisListNode += dynamic ? 'true' : 'false';
                        axisListNode += '</dynamic>';
    
                        axisListNode += '<min_value>';
                        var min_value = configChart
                                .down('numberfield[id="scaleMinField_configChart"]')
                                .getValue();
                        configChart
                                .down('numberfield[id="scaleMinField_configChart"]')
                                .resetOriginalValue();
                        axisListNode += min_value != null ? min_value : '';
                        axisListNode += '</min_value>';
    
                        axisListNode += '<max_value>';
                        var max_value = configChart
                                .down('numberfield[id="scaleMaxField_configChart"]')
                                .getValue();
                        configChart
                                .down('numberfield[id="scaleMaxField_configChart"]')
                                .resetOriginalValue();
                        axisListNode += max_value != null ? max_value : '';
                        axisListNode += '</max_value>';
    
                        axisListNode += '</zoom>';
    
                        axisListNode += '</axis>';
    
                        xml += axisListNode;
                        xml2 += axisListNode;
    
                        // Trend volume axis
                        xml2 += '<axis>';
                        xml2 += '<unit>';
                        unit = configChart
                                .down('textfield[id="trendUnitField_configChart"]')
                                .getValue();
                                
                        if(unit != null){
                            unit = this.escapeXml(unit);
                        }
                        
                        configChart
                                .down('textfield[id="trendUnitField_configChart"]')
                                .resetOriginalValue();
                        xml2 += unit != null ? unit : '';
                        xml2 += '</unit>';
                        xml2 += '</axis>';
    
                        xml += '</axis_list>';
                        xml2 += '</axis_list>';
                    } else if (template == 'template7') {
                        // Specific cell surveillance
    
                        // Product ID
                        xml += '<sdp_id>';
                        var sdp_id = Ext.getCmp('productCombo_configChart')
                                .getValue();
                        Ext.getCmp('productCombo_configChart').resetOriginalValue();
                        xml += sdp_id != null ? sdp_id : '';
                        xml += '</sdp_id>';
    
                        // Minimum number of days
                        xml += '<minnumberofdays>';
                        var minnumberofdays = Ext
                                .getCmp('alarmDayNumberfield_configChart')
                                .getValue();
                        Ext.getCmp('alarmDayNumberfield_configChart')
                                .resetOriginalValue();
                        xml += minnumberofdays != null ? minnumberofdays : '';
                        xml += '</minnumberofdays>';
    
                        // Alarms
                        xml += '<alarms>';
                        var alarmStore = Ext.getCmp('AlarmGrid_configChart')
                                .getStore();
                        for (var d = 0; d < alarmStore.data.items.length; d++) {
                            xml += '<alarm>';
    
                            xml += '<id>';
                            xml += alarmStore.data.items[d].data.id != null
                                    ? alarmStore.data.items[d].data.id
                                    : '';
                            xml += '</id>';
    
                            xml += '<label>';
                            xml += alarmStore.data.items[d].data.label != null
                                    ? alarmStore.data.items[d].data.label
                                    : '';
                            xml += '</label>';
    
                            xml += '<grid_name>';
                            xml += alarmStore.data.items[d].data.grid_name != null
                                    ? alarmStore.data.items[d].data.grid_name
                                    : '';
                            xml += '</grid_name>';
    
                            xml += '<comment>';
                            xml += alarmStore.data.items[d].data.comment != null
                                    ? encodeURIComponent(alarmStore.data.items[d].data.comment)
                                    : '';
                            xml += '</comment>';
    
                            xml += '<dashboard>';
                            xml += alarmStore.data.items[d].data.dashboard != null
                                    ? alarmStore.data.items[d].data.dashboard
                                    : '';
                            xml += '</dashboard>';
    
                            xml += '</alarm>';
    
                            Ext.getCmp('alarmNameField_configChart')
                                    .resetOriginalValue();
                            Ext.getCmp('alarmCommentField_configChart')
                                    .resetOriginalValue();
                            Ext.getCmp('alarmDashboardCombo_configChart')
                                    .resetOriginalValue();
                        }
    
                        xml += '</alarms>';
                    }
    
                    else if (template == 'template9' || template == 'template10') {
                        //Product ID
                        xml += '<sdp_id>';
                        var sdp_id = Ext.getCmp('productCombo_configChart_ar').getValue();
                        Ext.getCmp('productCombo_configChart_ar').resetOriginalValue();
                        var history = Ext.getCmp('HistoryDisplayed_configChart').getValue();
                        Ext.getCmp('HistoryDisplayed_configChart').resetOriginalValue();
                        
                        xml += sdp_id != null ? sdp_id : '';
                        xml += '</sdp_id>';
                        xml += '<history>';
                        xml +=  history != null ? history : '1';
                        xml += '</history>';
                        //Alarms
                        xml += '<calc_alarms>';
                        //var calcAlarmStore = Ext.getStore('calcAlarmStore');
                        var calcAlarmStore = Ext.getCmp('penalitiesCriteria_configChart').getStore();
                        for (var d = 0; d < calcAlarmStore.data.items.length; d++) {
                            if (calcAlarmStore.data.items[d].data.checked == true){
                                xml += '<alarm>';
    
                                xml += '<id>';
                                xml += calcAlarmStore.data.items[d].data.id != null
                                        ? calcAlarmStore.data.items[d].data.id
                                        : '';
                                xml += '</id>';
                                
                                xml += '</alarm>';
                                
                                Ext.getCmp('graphNameNameField_configChart')
                                        .resetOriginalValue();
                                Ext.getCmp('AlarmCombo_configChart_ar')
                                        .resetOriginalValue();
                            }
                            
                        }
                        xml += '</calc_alarms>';
                        xml += '<graph_list>';
                        
                        var graphsStore = Ext.getCmp('graphsTable_configChart').getStore();
                        for (var i = 0; i < graphsStore.data.items.length; i++) {
                            xml += '<graph id="'+graphsStore.data.items[i].data.id+'">';
                            
                            xml += '<name>';
                                xml += graphsStore.data.items[i].data.ag_name != null
                                ? graphsStore.data.items[i].data.ag_name
                                : '';
                            xml += '</name>';
                            
                            xml += '<type>';
                                xml += 'alarm';
                            xml += '</type>';
                            
                            xml += '<pie_chart>';
                                if(graphsStore.data.items[i].data.piechart != null && graphsStore.data.items[i].data.piechart == true){
                                     xml += '1'
                                }
                                else if (graphsStore.data.items[i].data.piechart != null && graphsStore.data.items[i].data.piechart == false){
                                     xml += '0'
                                }
                                else{
                                    xml += '0'
                                }
                            xml += '</pie_chart>';
                            
                            xml += '<alarms_display>';
                            alarms_list = graphsStore.data.items[i].data.displayed_alarms;
                            //TODO check
                            //alarms_list = alarms_list.substring(0,alarms_list.length - 1);
                            alarmsArray = alarms_list.split(',');
                            for (var j = 0; j < alarmsArray.length; j++) {
                                xml += '<id>';
                                xml += alarmsArray[j];
                                xml += '</id>';
                            }
                            xml += '</alarms_display>';
                            
                            xml += '</graph>';
                        }
                        
                        xml += '</graph_list>';
                        
                        
                    }
                    
                    else if (template == 'template5') {
                        // TODO create xml var for config
                        //get config of current tab via LOAD
                        Ext.Ajax.request({
                            url: 'proxy/configuration.php',
                            async:false,
                            params: {
                                task: 'LOAD',
                                tab: tabId
                            },
    
                            success: function (response) {
                                var result=Ext.decode(response.responseText);
                                var axis = 1;
                                    if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 2){
                                        axis = 3;
                                }
                                
                                
                                var neLevelAxe1 = Ext.getCmp('neLevelId_configMap').getValue();
                                var neLevelAxe2 = Ext.getCmp('neLevelId2_configMap').getValue();
                                    
                                var associationStore = Ext.getStore('associationStoreMap');
                                 
                                if(Ext.getCmp('configMapModeSelection').items.items[0].getValue().modeselection == "3"){
                                //if(Ext.getCmp('activate_roaming').getValue() == true){
                                    var groupdata=Ext.getCmp('mapKpiGrid_configChart_roaming').store.getRange();
                                        var productId = groupdata[0].data.trendkpiproductid;
                                        xml += '<fullscreen>';
                                    xml += Ext.getCmp('configMapModeSelection').items.items[0].getValue().modeselection =="1" ? "false" : "true";
                                    xml += '</fullscreen>';
                                    
                                    xml += '<roaming>';
                                    xml +=  "true" ;
                                    xml += '</roaming>';
                                    
                                    xml += '<displayed_value_mode>';
                                    xml += Ext.getCmp('displayedValueMode_configMap').getValue() == "element" ? "element" : "worst_sub_element";
                                    xml += '</displayed_value_mode>';
                                    
                                    xml += '<fullscreen_time_level>';
                                    var fullscreen_time_level = Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').getValue() ;
                                    xml += fullscreen_time_level == "" ? "day" : fullscreen_time_level;
                                    xml += '</fullscreen_time_level>';
                                    
                                    
                                    xml += '<trend_time_level>';
                                    xml += result['widgets']['widget'][0]['trend_time_level'] !=undefined ? result['widgets']['widget'][0]['trend_time_level'] : "day";
                                    xml += '</trend_time_level>';
                                    
                                    xml += '<donut_time_level>';
                                    //xml += result['widgets']['widget'][0]['donut_time_level'] !=undefined ? result['widgets']['widget'][0]['donut_time_level'] : "week";
                                    xml += '</donut_time_level>';
                                    
                                    xml += '<units_number>';
                                    xml += result['widgets']['widget'][0]['units_number'] != undefined ? result['widgets']['widget'][0]['units_number'] : 20;
                                    xml += '</units_number>';
                                    
                                    xml += '<map_id/>';
                                    
                                    xml += '<map_zoom>';
                                    
                                    xml += '<zoom_level>';
                                    xml += Ext.Object.getSize(result['widgets']['widget'][0]['map_zoom']['zoom_level'])!=0 ? result['widgets']['widget'][0]['map_zoom']['zoom_level'] : "";
                                    xml += '</zoom_level>';
                                    
                                    xml += '<zoom_latitude>';
                                    xml += Ext.Object.getSize(result['widgets']['widget'][0]['map_zoom']['zoom_latitude'])!=0 ? result['widgets']['widget'][0]['map_zoom']['zoom_latitude'] : "";
                                    xml += '</zoom_latitude>';
                                    
                                    xml += '<zoom_longitude>';
                                    xml += Ext.Object.getSize(result['widgets']['widget'][0]['map_zoom']['zoom_longitude'])!=0 ? result['widgets']['widget'][0]['map_zoom']['zoom_longitude'] : "";
                                    xml += '</zoom_longitude>';
                                    
                                    xml += '</map_zoom>';
                                    
                                    
                                    xml += '<home_zoom>';
                                    
                                    xml += '<home_zoom_level>';
                                    xml += Ext.Object.getSize(result['widgets']['widget'][0]['home_zoom']['home_zoom_level'])!=0 ? result['widgets']['widget'][0]['home_zoom']['home_zoom_level'] : "";
                                    xml += '</home_zoom_level>';
                                    
                                    xml += '<home_zoom_latitude>';
                                    xml += Ext.Object.getSize(result['widgets']['widget'][0]['home_zoom']['home_zoom_latitude'])!=0 ? result['widgets']['widget'][0]['home_zoom']['home_zoom_latitude'] : "";
                                    xml += '</home_zoom_latitude>';
                                    
                                    xml += '<home_zoom_longitude>';
                                    xml += Ext.Object.getSize(result['widgets']['widget'][0]['home_zoom']['home_zoom_longitude'])!=0 ? result['widgets']['widget'][0]['home_zoom']['home_zoom_longitude'] : "";
                                    xml += '</home_zoom_longitude>';
                                    
                                    xml += '</home_zoom>';
                                    
                                    xml += '<widget_links>' + selectedChart 
                                            + '_trend</widget_links>';
                                    
                                    xml += '<network_elements>';                            
                                    
    
                                    xml += '<network_level/>';
                                    
                                        xml += '<network_level2/>';
                                    
                                                xml += '<network_element>'; 
                                                        xml += '<ne_id/>';
                                                        xml += '<product_id/>';
                                                        xml += '<map_zone_id/>';
                                                xml += '</network_element>';
                                                //xml += '<parent_level_selected>';
                                                //xml += Ext.getCmp('parentLevelSelected_configMap').getValue() == "1" ? "axe1" : "axe2";
                                                //xml += '</parent_level_selected>';
    
                                    xml += '</network_elements>';
                                }else{
                                        var groupdata=Ext.getCmp('mapKpiGrid_configChart').store.getRange();
                                       
                                        xml += '<fullscreen>';
                                        xml += Ext.getCmp('configMapModeSelection').items.items[0].getValue().modeselection =="1" ? "false" : "true";
                                        xml += '</fullscreen>';
                                        
                                        xml += '<roaming>';
                                        xml += "false";
                                        xml += '</roaming>';
                                        
                                        xml += '<displayed_value_mode>';
                                        xml += "";
                                        xml += '</displayed_value_mode>';
                                        
                                        xml += '<fullscreen_time_level>';
                                        var fullscreen_time_level = Ext.getCmp('defaultFullscreenTimeLevelCombo_configMap').getValue() ;
                                        xml += fullscreen_time_level == "" ? "day" : fullscreen_time_level;
                                        xml += '</fullscreen_time_level>';
    
                                        xml += '<trend_time_level>';
                                        var trend_time_level = Ext.getCmp('defaultTrendTimeLevelCombo_configMap').getValue() ;
                                        xml += trend_time_level == "" ? "day" : trend_time_level;
                                        xml += '</trend_time_level>';
                                        
                                        xml += '<donut_time_level>';
                                        var donut_time_level = Ext.getCmp('defaultDonutTimeLevelCombo_configMap').getValue() ;
                                        xml += donut_time_level == "" ? "week" : donut_time_level;
                                        xml += '</donut_time_level>';
                                        
                                        xml += '<units_number>';
                                        xml += result['widgets']['widget'][0]['units_number'] != undefined ? result['widgets']['widget'][0]['units_number'] : 20;
                                        xml += '</units_number>';
                                        
                                        xml += '<map_id/>';
                                        
                                        xml += '<map_zoom>';
                                        
                                        xml += '<zoom_level>';
                                        xml += Ext.Object.getSize(result['widgets']['widget'][0]['map_zoom']['zoom_level'])!=0 ? result['widgets']['widget'][0]['map_zoom']['zoom_level'] : "";
                                        xml += '</zoom_level>';
                                        
                                        xml += '<zoom_latitude>';
                                        xml += Ext.Object.getSize(result['widgets']['widget'][0]['map_zoom']['zoom_latitude'])!=0 ? result['widgets']['widget'][0]['map_zoom']['zoom_latitude'] : "";
                                        xml += '</zoom_latitude>';
                                        
                                        xml += '<zoom_longitude>';
                                        xml += Ext.Object.getSize(result['widgets']['widget'][0]['map_zoom']['zoom_longitude'])!=0 ? result['widgets']['widget'][0]['map_zoom']['zoom_longitude'] : "";
                                        xml += '</zoom_longitude>';
                                        
                                        xml += '</map_zoom>';
                                        
                                        
                                            xml += '<home_zoom>';
                                            
                                            xml += '<home_zoom_level>';
                                            xml += Ext.Object.getSize(result['widgets']['widget'][0]['home_zoom']['home_zoom_level'])!=0 ? result['widgets']['widget'][0]['home_zoom']['home_zoom_level'] : "";
                                            xml += '</home_zoom_level>';
                                            
                                            xml += '<home_zoom_latitude>';
                                            xml += Ext.Object.getSize(result['widgets']['widget'][0]['home_zoom']['home_zoom_latitude'])!=0 ? result['widgets']['widget'][0]['home_zoom']['home_zoom_latitude'] : "";
                                            xml += '</home_zoom_latitude>';
                                            
                                            xml += '<home_zoom_longitude>';
                                            xml += Ext.Object.getSize(result['widgets']['widget'][0]['home_zoom']['home_zoom_longitude'])!=0 ? result['widgets']['widget'][0]['home_zoom']['home_zoom_longitude'] : "";
                                            xml += '</home_zoom_longitude>';
                                            
                                            xml += '</home_zoom>';
                                        
                                        
                                        
                                        xml += '<widget_links>' + selectedChart 
                                                + '_trend</widget_links>';
                                        
                                        xml += '<network_elements>';
                                        
                                        xml += '<network_level>';
                                        xml += Ext.Object.getSize(result['widgets']['widget'][0]['network_elements']['network_level'])!=0 ? result['widgets']['widget'][0]['network_elements']['network_level'] : "";
                                        xml += '</network_level>';
                                                                            
                                                                            if(typeof(result['widgets']['widget'][0]['network_elements']['network_level2']) !== 'undefined'){
                                                xml += '<network_level2>';
                                                xml += Ext.Object.getSize(result['widgets']['widget'][0]['network_elements']['network_level2'])!=0 ? result['widgets']['widget'][0]['network_elements']['network_level2'] : "";
                                                xml += '</network_level2>';
                                                                            }
                                                                            
                                        for (var d = 0; d < result['widgets']['widget'][0]['network_elements']['network_element'].length; d++) {
                                            xml += '<network_element>';
                                            
                                            xml += '<ne_id>';
                                            xml += result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id'] !=undefined ? result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id'] : "";
                                            xml += '</ne_id>';
                                            if(typeof(result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id2']) !== 'undefined'){
                                                    xml += '<ne_id2>';
                                                    xml += result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id2'] !=undefined ? result['widgets']['widget'][0]['network_elements']['network_element'][d]['ne_id2'] : "";
                                                    xml += '</ne_id2>';
                                            }
                                            
                                            xml += '<product_id>';
                                            xml += result['widgets']['widget'][0]['network_elements']['network_element'][d]['product_id'] !=undefined ? result['widgets']['widget'][0]['network_elements']['network_element'][d]['product_id'] : "";
                                            xml += '</product_id>';
                                            
                                            xml += '<map_zone_id>';
                                            xml += result['widgets']['widget'][0]['network_elements']['network_element'][d]['map_zone_id'] !=undefined ? result['widgets']['widget'][0]['network_elements']['network_element'][d]['map_zone_id'] : "";
                                            xml += '</map_zone_id>';
                                            
                                            xml += '</network_element>';
                                        }
                
                                    xml += '</network_elements>';
                                }
                                //create kpi_groups node
                                xml += '<kpi_groups>';
                                for (var d = 0; d < groupdata.length; d++) {
                                    var productId = groupdata[d].data.trendkpiproductid;
                                        if(groupdata[d].data.groupname != null){
                                            var group_name = me.escapeXml(groupdata[d].data.groupname);
                                    }
                                    
                                    xml += '<group>';
                                    xml += '<group_name>';
                                    xml +=  group_name
                                    xml += '</group_name>';
                                    
                                    xml += '<kpis>';
                                    
                                    xml += '<kpi_trend>';
    
                                    xml += '<kpi_id>';
                                    xml += groupdata[d].data.trendkpiid;
                                    xml += '</kpi_id>';
                                    
                                    xml += '<product_id>';
                                    xml += groupdata[d].data.trendkpiproductid;
                                    xml += '</product_id>';
                                    
                                    xml += '<type>';
                                    xml += groupdata[d].data.typekpi;
                                    xml += '</type>';
                                    
                                    xml += '<function>';
                                    xml += groupdata[d].data.trendkpifunction;
                                    xml += '</function>';
                                    
                                    xml += '<label>';
                                    xml += groupdata[d].data.trendkpilabel;
                                    xml += '</label>';
                                    
                                    xml += '<product_label>';
                                    xml += groupdata[d].data.trendproductlabel;
                                    xml += '</product_label>';
                                    
                                    if(Ext.getCmp('configMapModeSelection').items.items[0].getValue().modeselection == "3"){
                                            /**if(d === (groupdata.length-1) && Ext.getCmp('new_kpi').getValue() == "true"){
                                                    xml += '<network_axis_number>';
                                                    xml += Ext.getCmp('parentLevelSelected_configMap').getValue() == "1" ? "1" : "2";
                                                    xml += '</network_axis_number>';
                                                    
                                                    xml += '<roaming_network_level>';
                                                    xml += neLevelAxe1
                                                    xml += '</roaming_network_level>';
                                                    
                                                    xml += '<roaming_network_level2>';
                                                    xml += neLevelAxe2
                                                    xml += '</roaming_network_level2>';
    
                                                    xml += '<roaming_ne_id>';
                                                    xml +=  Ext.getCmp('parentLevelSelected_configMap').getValue() == "2" ? Ext.getCmp('neId_configChart').getValue() : "";
                                                    xml += '</roaming_ne_id>';
                                                    
                                                    xml += '<roaming_ne_id2>';
                                                    //xml +=  Ext.getCmp('parentLevelSelected_configMap').getValue() == "1" &&  Ext.getCmp('neId2_configChart').getValue()!= "" ? Ext.getCmp('neId2_configChart').getValue() : "";
                                                    xml += ""
                                                    xml += '</roaming_ne_id2>';
                                            }/else{**/
                                                    xml += '<network_axis_number>';
                                                    xml += groupdata[d].data.networkaxisnumber != "" ?  groupdata[d].data.networkaxisnumber : ""; 
                                                    xml += '</network_axis_number>';
                                                    
                                                    xml += '<roaming_network_level>';
                                                    xml += groupdata[d].data.roamingnetworklevel != "" ?  groupdata[d].data.roamingnetworklevel : ""; 
                                                    xml += '</roaming_network_level>';
                                                    
                                                    xml += '<roaming_network_level2>';
                                                    xml += groupdata[d].data.roamingnetworklevel2 != "" ?  groupdata[d].data.roamingnetworklevel2 : ""; 
                                                    xml += '</roaming_network_level2>';
                                                    
                                                    xml += '<roaming_ne_id>';
                                                    xml +=  groupdata[d].data.roamingneid != "" ?  groupdata[d].data.roamingneid : "";
                                                    xml += '</roaming_ne_id>';
                                                    
                                                    xml += '<roaming_ne_id2>';
                                                    xml +=  groupdata[d].data.roamingneid2 != "" ?  groupdata[d].data.roamingneid2 : "";
                                                    xml += '</roaming_ne_id2>';
                                           // }
                                    }
                                    
                                    xml += '</kpi_trend>';
                        
                                    xml += '<kpi_donut>';
    
                                    xml += '<kpi_id>';
                                    xml += groupdata[d].data.donutkpiid;
                                    xml += '</kpi_id>';
                                    
                                    xml += '<product_id>';
                                    xml += groupdata[d].data.donutkpiproductid;
                                    xml += '</product_id>';
                                    
                                    xml += '<type>';
                                    xml += groupdata[d].data.typekpidonut;
                                    xml += '</type>';
                                    
                                    xml += '<label>';
                                    xml += groupdata[d].data.donutkpilabel;
                                    xml += '</label>';
                                    
                                    xml += '<product_label>';
                                    xml += groupdata[d].data.donutproductlabel;
                                    xml += '</product_label>';
    
                                    xml += '</kpi_donut>';
    
                                    xml += '</kpis>';
                                    
                                    xml += '<axis_list>';
                                    
                                    xml += '<axis_trend>';
                                    
                                    xml += '<unit>';
                                    xml += groupdata[d].data.trendunit != null ? me.escapeXml(groupdata[d].data.trendunit) : '';
                                    xml += '</unit>';
                                    
                                    xml += '<thresholds>';
                                    
                                    xml += '<low_threshold>';
                                    xml += groupdata[d].data.lowthreshold != null ? groupdata[d].data.lowthreshold : '';
                                    xml += '</low_threshold>';
                                    
                                    xml += '<high_threshold>';
                                    xml += groupdata[d].data.highthreshold != null ? groupdata[d].data.highthreshold : '';
                                    xml += '</high_threshold>';
                                    
                                    xml += '</thresholds>';
                                    
                                    xml += '<zoom>';
                                    
                                    xml += '<dynamic>';
                                    xml += groupdata[d].data.dynamic;
                                    xml += '</dynamic>';
                                    
                                    xml += '<min_value>';
                                    xml += groupdata[d].data.dynamic!=true && groupdata[d].data.minvalue != null ? groupdata[d].data.minvalue : '' ;
                                    xml += '</min_value>';
                                    
                                    xml += '<max_value>';
                                    xml += groupdata[d].data.dynamic!=true && groupdata[d].data.maxvalue != null ? groupdata[d].data.maxvalue : '';
                                    xml += '</max_value>';
                                    
                                    xml += '</zoom>';
                                    
                                    xml += '</axis_trend>';
                                    
                                    xml += '<axis_donut>';
                                    
                                    xml += '<unit>';
                                    xml += groupdata[d].data.donutunit != null ? me.escapeXml(groupdata[d].data.donutunit) : '';
                                    xml += '</unit>';
                                    
                                    xml += '</axis_donut>';
                                    
                                    xml += '</axis_list>';
                                    
                                    xml += '</group>';
                                }
                                
                                xml += '</kpi_groups>';
                                
                                
                                
                                //reset original values for inputs
                                Ext.getCmp("titleField_configChart").resetOriginalValue();
                                Ext.getCmp("typeCombo_configChart").resetOriginalValue();       
                                Ext.getCmp("dynamicBox_configChart").resetOriginalValue();
                                Ext.getCmp("unitField_configChart").resetOriginalValue();
                                Ext.getCmp("thresholdMinField_configChart").resetOriginalValue();
                                Ext.getCmp("displayedValueMode_configMap").resetOriginalValue();
                                //Ext.getCmp("activate_roaming").resetOriginalValue();
                                Ext.getCmp("thresholdMaxField_configChart").resetOriginalValue();
                                Ext.getCmp("scaleMinField_configChart").resetOriginalValue();
                                Ext.getCmp("scaleMaxField_configChart").resetOriginalValue();
                                Ext.getCmp("timeUnitCombo_configChart").resetOriginalValue();
                                Ext.getCmp("trendTimeUnitCombo_configChart").resetOriginalValue();
                                Ext.getCmp("trendPeriodField_configChart").resetOriginalValue();
                                Ext.getCmp("trendUnitField_configChart").resetOriginalValue();
                                
                            }
            
                        });
    
                        
                    }
    
                    xml += '</widget>';
                    xml2 += '</widget>';
                                    
                    // If the selected tab has changed
                    var isSelected = null;
                    if (Ext.getCmp('defaultTabHidden').isDirty()) {
                        isSelected = Ext.getCmp('defaultTabHidden').getValue();
                        Ext.getCmp('defaultTabHidden').resetOriginalValue();
                    }
    
                    // If the style has changed
                    var style = null;
                    if (Ext.getCmp('styleCombo').isDirty()) {
                        style = Ext.getCmp('styleCombo').getValue();
                        Ext.getCmp('styleCombo').resetOriginalValue();
                    }
    
                    // If the index has changed
                    var index = null;
                    if (Ext.getCmp('indexField_configTab').isDirty()) {
                        index = Ext.getCmp('indexField_configTab').getValue();
                        Ext.getCmp('indexField_configTab').resetOriginalValue();
                    }
                    
                    // If the selectedmode has changed
                    var selectedmode = null;
                    //var originalmode = Ext.getCmp('alarmPenalizationMode_configChart').originalValue;
                    var currentmode = Ext.getCmp('alarmPenalizationMode_configChart').getValue();
                    currentmode = currentmode['radioPenalizationMode'];
                    selectedmode = currentmode;
                    Ext.getCmp('alarmPenalizationMode_configChart').resetOriginalValue();
                    
                    
                    
                    // If the ratio or nbdays for penalisation has changed
                    var ratio = null;
                    var nbdays = null;
                    if (Ext.getCmp('alarmRatioNumberfield_configChart').isDirty()) {
                        ratio = (Ext.getCmp('alarmRatioNumberfield_configChart')
                                .getValue() / 100).toFixed(2);
                        Ext.getCmp('alarmRatioNumberfield_configChart')
                                .resetOriginalValue();
                    }
                    if (Ext.getCmp('alarmNbDaysNumberfield_configChart').isDirty()) {
                        nbdays = (Ext.getCmp('alarmNbDaysNumberfield_configChart').getValue());
                        Ext.getCmp('alarmNbDaysNumberfield_configChart').resetOriginalValue();
                    }
                                    
                    if(Ext.getCmp('configMapModeSelection').items.items[0].getValue().modeselection == "3"){
                            var addKpiButton = Ext.getCmp('trendCounterButton_configChart_roaming');
                                            var cancelKpiButton = Ext.getCmp('trendCounterCancelButton_configChart_roaming');
                                            addKpiButton.setDisabled(false);
                                            cancelKpiButton.setDisabled(false);
                                            
                                            //TODO Remettre le radio box sur ax1
                                            
                                            //Ext.getCmp('configMapAssociation').items.items[0].setValue({neLevelSelction : "1"});
                                            //we reset filters
                                            var axis = 1;
                                            if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 2){
                                                    axis = 3;
                                            }
                                            
                                            if(axis == 1){
                                                //reset filter level
                                                Ext.getCmp('neLevelId_configMap').setValue('');
                                                Ext.getCmp('neLevelLabel_configMap').setValue('');
                                                Ext.getCmp('neProductId_configMap').setValue('');
                                                
                                                //reset filter ne
                                                Ext.getCmp('neId_configChart').setValue('');
                                                Ext.getCmp('neLevelId_configChart').setValue('');
                                                Ext.getCmp('neProductId_configChart').setValue('');
                                                Ext.getCmp('neLabel_configChart').setValue('');
                                                Ext.getCmp('neLevelLabel_configChart').setValue('');
                                            }else{
                                                    //reset filter level
                                                    Ext.getCmp('neLevelId2_configMap').setValue('');
                                                Ext.getCmp('neLevelLabel2_configMap').setValue('');
                                                Ext.getCmp('neProductId_configMap').setValue('');
                                                
                                                //reset filter ne
                                                Ext.getCmp('neId2_configChart').setValue('');
                                                Ext.getCmp('neLevelId2_configChart').setValue('');
                                                Ext.getCmp('neProductId2_configChart').setValue('');
                                                Ext.getCmp('neLabel2_configChart').setValue('');
                                                Ext.getCmp('neLevelLabel2_configChart').setValue('');
                                                
                                            }
                                
                                        // Change the button aspect
                                        var counterButton1 = Ext.getCmp('neLevelSelectButton_configMapAssoction');
                                        var counterButton2 = Ext.getCmp('neSelectButton_configMapAssoction');
                                        
                                        
                                            counterButton1.removeCls('x-button-network-select-ok');
                                            counterButton1.addCls('x-button-network-select');
                                            counterButton1.setTooltip('');
                                            
                                            counterButton2.removeCls('x-button-network-select-ok');
                                            counterButton2.addCls('x-button-network-select');
                                            counterButton2.setTooltip('');
    
                    }
                    
                    
                    var maskingAjax = new Ext.data.Connection({
                                listeners : {
                                    'beforerequest' : {
                                        fn : function(con, opt) {
                                            Ext.get(document.body)
                                                    .mask('Saving...');
                                        },
                                        scope : this
                                    },
                                    'requestcomplete' : {
                                        fn : function(con, res, opt) {
                                            Ext.get(document.body).unmask();
                                        },
                                        scope : this
                                    },
                                    'requestexception' : {
                                        fn : function(con, res, opt) {
                                            Ext.get(document.body).unmask();
                                        },
                                        scope : this
                                    }
                                }
                            });
    
                    maskingAjax.request({
                                url : 'proxy/configuration.php',
                                params : {
                                    task : 'SAVE',
                                    tab : selectedTab,
                                    selected : isSelected,
                                    style : style,
                                    chart : selectedChart,
                                    title : tabTitle,
                                    template : template,
                                    index : index,
                                    selectedmode : selectedmode,
                                    ratio : ratio,
                                    nbdays : nbdays,
                                    xml : xml,
                                    xml2 : xml2
                                },
    
                                success : function(response) {
                                    if (response.responseText == 'reload') {
                                            // Reload the homepage
                                        location.reload();
                                    } else {
                                        me.makeConfig();
                                    }
                                }
                            });
    
                    configChart.checkModifications();
                }
            }
    
            // Reable the toolbar
            toolbar.enable();
        },
    
        // Add a new tab
        addTab : function() {
            Ext.Ajax.request({
                        url : 'proxy/configuration.php',
                        params : {
                            task : 'ADD_TAB'
                        },
    
                        success : function(response) {
                            Ext.getCmp('mainPanel').fireEvent('createTab',
                                    Ext.decode(response.responseText));
                            // if only one tab
                            if (Ext.getCmp('tabPanel').items.length == 1) {
                                Ext.getCmp('mainPanel').fireEvent('refreshCharts');
                            }
                        }
                    });
        },
    
        // Copy a tab
        copyTab : function() {
            // Get the selected tab
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
            selectedTab = tabId.substr(tabId.lastIndexOf('_') + 1);
            Ext.Ajax.request({
                        url : 'proxy/configuration.php',
                        params : {
                            task : 'COPY_TAB',
                            tab : selectedTab
                        },
    
                        success : function(response) {
                            Ext.getCmp('mainPanel').fireEvent('createTab',
                                    Ext.decode(response.responseText));
                        }
                    });
        },
    
        // Ask to delete the selected tab
        deleteTab : function() {
            // Show a warning window
            var title = Ext.getCmp('tabPanel').getActiveTab().title;
            Ext.MessageBox
                    .confirm(
                            'Warning',
                            'You are going to remove the tab "'
                                    + title
                                    + '". This action will erase all the configuration for the tab and can not be reversed. Do you wish to remove it anyway?',
                            this.confirmDelete);
        },
    
        // Delete the tab
        confirmDelete : function(response) {
            if (response == 'yes') {
                var tabPanel = Ext.getCmp('tabPanel');
                var tab = tabPanel.getActiveTab();
                Ext.Ajax.request({
                            url : 'proxy/configuration.php',
                            params : {
                                task : 'DELETE_TAB',
                                tab : tab.getId()
                            },
    
                            success : function(response) {
                                var res = Ext.decode(response.responseText)
    
                                if (res.state == 'success') {
                                    tabPanel.remove(tab);
                                    // set activetab to default tab
                                    if (tabPanel.items.get(res.tab) != tab) {
                                        tabPanel.setActiveTab(parseInt(res.tab));
                                    }
    
                                    Ext.getCmp('mainPanel')
                                            .fireEvent('refreshCharts');
                                }
                            }
                        });
            }
        },
    
        // Ask to reset the configuration
        resetConfig : function() {
            // Show a warning window
            Ext.MessageBox
                    .confirm(
                            'Warning',
                            'You are going to reset the configuration. This action will erase your configuration and replace it with the default configuration. Do you wish to reset anyway?',
                            this.confirmReset);
        },
    
        // Reset the configuration
        confirmReset : function(response) {
            if (response == 'yes') {
                Ext.Ajax.request({
                            url : 'proxy/configuration.php',
                            params : {
                                task : 'RESET'
                            },
    
                            success : location.reload()
                        });
            }
        },
    
        // Export the tab as PNG
        exportTab : function(type) {
                    var me = this;
            // Get the selected tab
            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
            var tabTitle = Ext.getCmp('tabPanel').getActiveTab().title;
            var tabTemplate = Ext.getCmp('tabPanel').getActiveTab().templateId;
    
            var button = Ext.getCmp('exportButton');
            button.disable();
    
            var selectedTab = tabId.substr(tabId.lastIndexOf('_') + 1);
    
            Ext.Ajax.request({
                url : 'proxy/export.php',
                params : {
                    task : 'GET_WIDGETS',
                    tab : selectedTab
                },
    
                success : function(response) {
                    // Decode the json into an array object
                    var widgets = Ext.decode(response.responseText);
                
                    for (var i = 0; i < widgets['widget'].length; i++) {
                        // Get the chart title
                        var chart = Ext.getCmp('chartsPanel_' + selectedTab + '_'
                                + widgets['widget'][i]['id']);
                        widgets['widget'][i]['title'] = chart.title;
    
                        if (widgets['widget'][i]['type'] == 'chart') {
                            // Create a data url representation of each widget SVG
                            if (Ext.getCmp('viewport').gaugeType == 1) {
                                var svgElement = document.createElementNS(
                                        'http://www.w3.org/2000/svg', 'svg');
                                var viewIeId = 'chartsPanel_' + selectedTab + '_'
                                        + widgets['widget'][i]['id'] + '_viewie';
    
                                if (!Ext.getCmp(viewIeId).hidden) {
                                    var mergedSvg = '';
                                    for (var v = 0; v < 5; v++) {
                                        if (document.getElementById(viewIeId
                                                + '-body').childNodes[v].firstChild != null) {
                                            var svg = document
                                                    .getElementById(viewIeId
                                                            + '-body').childNodes[v] // Current
                                                                                        // layer
                                                                                        // gauge
                                            .firstChild.cloneNode(true); // SVG
    
                                            // Serialize the SVG node
                                            var serializer = new XMLSerializer();
                                            var svgString = serializer
                                                    .serializeToString(svg);
    
                                            // Delete the last </svg>
                                            svgString = svgString.substring(0,
                                                    svgString.length - 6);
    
                                            // Delete the first <svg ... />
                                            svgString = svgString.substring(
                                                    svgString.indexOf('>') + 1,
                                                    svgString.length);
    
                                            mergedSvg += svgString;
                                        }
                                    }
    
                                    var svgElement = document.createElementNS(
                                            'http://www.w3.org/2000/svg', 'svg');
                                    svgElement.innerSVG = mergedSvg;
    
                                    var pngDataURL = svgElement
                                            .toDataURL("image/png");
    
                                    widgets['widget'][i]['dataurl'] = pngDataURL;
                                }
                            } else {
                                var svgId = 'chartsPanel_' + selectedTab + '_'
                                        + widgets['widget'][i]['id'] + '-body';
    
                                if (document.getElementById(svgId).firstChild.childNodes[1].firstChild.firstChild.firstChild != null) {
                                    var svg = document.getElementById(svgId).firstChild // Box
                                                                                        // inner
                                    .childNodes[1] // Gauge view
                                    .firstChild.firstChild.firstChild // Div > Div
                                                                        // > Div
                                    .firstChild.cloneNode(true); // SVG
    
                                    var svgElement = document.createElementNS(
                                            'http://www.w3.org/2000/svg', 'svg');
                                    svgElement.appendChild(svg);
    
                                    var pngDataURL = svg.toDataURL("image/png");
    
                                    widgets['widget'][i]['dataurl'] = pngDataURL;
                                }
                            }
    
                            // Get additional informations
                            var chartValue = Ext
                                    .getCmp('chartsPanel_' + selectedTab + '_'
                                            + widgets['widget'][i]['id']
                                            + '_details_value').text;
                            widgets['widget'][i]['value'] = chartValue.replace(
                                    '\u00a0', ' ').replace('\u00a0', ' ');
                            widgets['widget'][i]['label1'] = Ext
                                    .getCmp('chartsPanel_' + selectedTab + '_'
                                            + widgets['widget'][i]['id']
                                            + '_details_label1').text;
                            widgets['widget'][i]['label2'] = Ext
                                    .getCmp('chartsPanel_' + selectedTab + '_'
                                            + widgets['widget'][i]['id']
                                            + '_details_label2').text;
                            widgets['widget'][i]['label3'] = Ext
                                    .getCmp('chartsPanel_' + selectedTab + '_'
                                            + widgets['widget'][i]['id']
                                            + '_details_label3').text;
    
                        } else if (widgets['widget'][i]['type'] == 'period') {
                            // Create a data url representation of each widget SVG
                            var svgId = 'chartsPanel_' + selectedTab + '_'
                                    + widgets['widget'][i]['id'] + '_periodObject';
    
                            var svg = document.getElementById(svgId).firstChild;
    
                            var pngDataURL = svg.toDataURL("image/png");
    
                            widgets['widget'][i]['dataurl'] = pngDataURL;
                        } else if (widgets['widget'][i]['type'] == 'map') {
                            
                            var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
                                            me.isRoaming = false;
                          
                            Ext.Ajax.request({
                                url: 'proxy/configuration.php',
                                params: {
                                    task: 'LOAD',
                                    tab: tabId
                                },
        
                                success: function (response) {
                                    var config = Ext.decode(response.responseText);
                                                                    
                                    config=config['widgets']['widget'][0];
    
                                    var fullscreen=(config.fullscreen == 'true' ? true : false);
                                                        
                                    //get index from kpi selector combo
                                    var index =Ext.getCmp(tabId+'_chart1_kpi_selector').value;
                                                            me.index= index;
                                    // Time parameters
                                    var timeUnit = Ext.getCmp(tabId+'_chart1_trend_selector').value;
                                    var timeData = {};
                                    timeData.id = timeUnit;
                                    timeData.type = "ta";
                                    timeData.order = "Descending"; // get the last value available
                                    
                                    var nbKpis = config['kpi_groups']['group'].length;
                                    
                                           if(typeof (nbKpis) !== "undefined" ){
                                            //get kpi
                                            var rawKpiId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['kpi_id'];
                                            //get product id for the kpi
                                            var rawKpiProductId = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['product_id'];
                                            //get type
                                            var rawKpiType = config['kpi_groups']['group'][index]['kpis']['kpi_trend']['type'];
                                           }else{
                                                   //get kpi
                                            var rawKpiId = config['kpi_groups']['group']['kpis']['kpi_trend']['kpi_id'];
                                            //get product id for the kpi
                                            var rawKpiProductId = config['kpi_groups']['group']['kpis']['kpi_trend']['product_id'];
                                            //get type
                                            var rawKpiType = config['kpi_groups']['group']['kpis']['kpi_trend']['type'];
                                           }
                                         
                                            // Only get the last value in the database
                                            var limitFilter = {};
                                            var timeNumber = config['units_number'];
                                            if (timeNumber == null || 
                                                ((typeof(timeNumber) != 'string') && (typeof(timeNumber) != 'number')) ||
                                                timeNumber == '') {
                                            
                                                timeNumber = 20;
                                            }
                                    
                                            me.nes={};
                                           
                                            
                                            
                                            limitFilter.id = 'maxfilter'; 
                                            limitFilter.type = 'sys';
                                            
    
                                    if(config['roaming'] == 'true' ){
                                                                            
                                                            //we empty me.nes to fill it with the informations from the new slected kpi
                                                            me.nes={};
                                                            me.nes.ids=new Array();
                                                            me.nes.ids2=new Array();
                                                            me.nes.idsindex=new Array();
                                                            me.nes.zones=new Array();
                                                            me.nes.labels=new Array();
                                                            me.nes.zoneslabels=new Array();
                                                            me.nes.idvalueparent=new Array();
                                                            me.nes.labelparent=new Array();
                                                           
                                                            var neLevelAxe1 = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_network_level'];
                                                            var neLevelAxe2 =config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_network_level2'];
                                                            
                                                            productId = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'];
                                                                
                                                                if(config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['network_axis_number'] == '2'){
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
                                                                                    sdp_id : config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'],
                                                                                    selected_level : axis == 2 ?  neLevelAxe2 : neLevelAxe1
                                                                            },
                                                                            
                                                                            success : function(response) {
                                                                                    //me.ammapIds = response.responseText;
                                                                                    me.ammapIds=Ext.decode(response.responseText);
                                                                                    for (var i = 0; i < me.ammapIds['data'].length; i++) {
                                                                                            //parentIdArray.push(me.ammapIds['data'][i]['mapId']);
                                                                                            if(axis == 1){
                                                                                                    if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
                                                                                                            me.nes.ids2.push(config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_ne_id2']);
                                                                                                    }	
                                                                                            }else{
                                                                                                    me.nes.ids.push(config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_ne_id']);
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
                                                                                                            //value neData r�cup�r� apr�s
                                                                                                            
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
                                                                                                                                                                //Pour r�cup�er les labels
                                                                                                                                                                requestDataLabelChildren={
                                                                                                                                                                            nelist:me.nes.ids.join(','),
                                                                                                                                                                            na:childLevel,
                                                                                                                                                                        product:config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'],
                                                                                                                                                                        order: 'asc'
                                                                                                                                                                };
                                                                                                                                                        }else{
                                                                                                                                                                neData2.value= me.nes.ids2.join(',');
                                                                                                                                                                requestDataLabelChildren={
                                                                                                                                                                            nelist:me.nes.ids2.join(','),
                                                                                                                                                                            na:childLevel,
                                                                                                                                                                        product:config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'],
                                                                                                                                                                        order: 'asc'
                                                                                                                                                                };
                                                                                                                                                        }
                                                                                                                                                        var fullmapTimeLevel = config['fullscreen_time_level'];
                                                                                                                                                        var timeData = {};
                                                                                                                                                    timeData.id = fullmapTimeLevel;
                                                                                                                                                    timeData.type = "ta";
                                                                                                                                                    timeData.order = "Descending"; // get the last value available
                                                                                                                                                    
                                                                                                                                                    var rawKpiId = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['kpi_id'];
                                                                                                                                                    var rawKpiProductId =config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'];
                                                                                                                                                           var rawKpiType = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['type']
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
                                                                                                                                                                            console.log('erreur')
                                                                                                                                                                            
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
                                                                                   neSelectData.id=neLevelAxe2;
                                                                            neSelectData.type="na_axe3";
                                                                            neSelectData.order = "Ascending";
                                                                            
                                                                                   neSelectData2.id=neLevelAxe1;
                                                                                   neSelectData2.type="na";
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
                                                                                   
                                                                                   if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
                                                                                           neSelectData3.id= me.levelChildId;
                                                                                    neSelectData3.type="na_axe3";
                                                                                    neSelectData3.order = "";
                                                                                   }else{
                                                                                           neSelectData3.id= me.levelChildId;
                                                                                    neSelectData3.type="na";
                                                                                    neSelectData3.order = "";
                                                                                   }
                                                                }
                                                                
                                                            });
                                                                    
                                                                    
                                                                     // Counter parameters
                                                                    var rawKpiId = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['kpi_id'];
                                                                    var rawKpiProductId = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'];
                                                                    var rawKpiType = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['type'];
                                                                    var rawKpiData = {};
                                                                    rawKpiData.id = rawKpiId;        
                                                                    rawKpiData.productId = rawKpiProductId;
                                                                    rawKpiData.type = rawKpiType;       
                                            
                                                                    
                                                                    //on r�cup�re la derni�re date d'integration en fonction du niveau temps s�l�ction�
                                                                    Ext.Ajax.request({
                                                                            url : 'proxy/configuration.php',
                                                                            async : false,
                                                                            params : {
                                                                                    task : 'LAST_DATE',
                                                                                    sdp_id : config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'],
                                                                                    time_level : timeUnit
                                                                            },
                                            
                                                                            success : function(response) {
                                                                                    me.lastintegrationdate = response.responseText;
                                                                            }
                                                                    });
                                                                    
                                                                    // On remplie le store pour garder les id parents en memoire dans le cas ou on change un param�tre dans le panneau de conf	
                                                            //on veut r�cup�rer les derni�re valeurs pour les parent id
                                                            timeData.order = "Descending";
                                                            if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ""){
                                                                    var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2);
                                                            }else{
                                                                    var selectData = new Array(timeData, rawKpiData,neSelectData);
                                                                    
                                                            }
                                                            
                                                            
                                                            var requestDataId = {};
                                                            requestDataId.method = 'getDataAndLabels';
                                                            requestDataId.parameters = {};
                                                            /**
                                                            if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ""){
                                                                           requestDataId.parameters.roaming = true;
                                                            }else{
                                                                    
                                                                    requestDataId.parameters.mapid = true
                                                                    
                                                            }
                                                            **/
                                                            requestDataId.parameters.select = {};
                                                            requestDataId.parameters.select.data = selectData;
                                                            requestDataId.parameters.filters = {};
                                                            
                                                            
                                                                    
                                                                    //On r�cup�re les id ammap dans la table edw_object_ref
                                                                    parentIdArray = new Array();
                                                                    Ext.Ajax.request({
                                                                            url : 'proxy/configuration.php',
                                                                            async : false,
                                                                            params : {
                                                                                    task : 'GET_AMMAPID',
                                                                                    sdp_id : config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['product_id'],
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
                                                            if(typeof(neLevelAxe2) !== 'object' &&  neLevelAxe2 != ''){
                                                                           neData.value=me.nes.ids.join(',');
                                                            }else{
                                                                    neData.value=parentIdArray.join(',');
                                                            }
                                                            
                                                                var neId2 = null;
                                                                if (config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['network_axis_number'] == "2") {
                                                                        var neData2 = {}; 
                                                                    var neId2 = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_network_level2'];
                                                                    neData2.type = 'na_axe3';
                                                                    neData2.operator = 'in';
                                                                    neData2.id = neLevelAxe2; //neId2
                                                                    neData2.value =parentIdArray.join(',');
                                                                    
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
                                                                                    }else{
                                                                                            error = false;
                                                                                    }
                                                                            } catch (err) {
                                                                                    // The json is invalid
                                                                                    error = true;
                                                                                    console.log(err);
                                                                                    
                                                                            }
                                                                            /**
                                                                            if(error == false){
                                                                                            for(i=0;i<result.values.data.length;i++){
                                                                                                    if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
                                                                                                    me.nes.idvalueparent[result.values.data[i][3]] = result.values.data[i][1];
                                                                                                    }else{
                                                                                                            me.nes.idvalueparent[result.values.data[i][2]] = result.values.data[i][1];
                                                                                                    }
                                                                                            }
                                                                                            //On repasse le timedata en descending
                                                                                    timeData.order = "Descending";
                                                                            }**/
                                                                            if(error == false){
                                                                                            var parentLabelArray = [];
                                                                                            for(i=0;i<result.values.data.length;i++){
                                                                                                    if(typeof neLevelAxe2 !== 'object' &&  neLevelAxe2 != ''){
                                                                                                    me.nes.idvalueparent[result.values.data[i][3]] = result.values.data[i][1];
                                                                                                    if(parentLabelArray.indexOf(result.values.data[i][3]) == -1){
                                                                                                            me.nes.labelparent.push(result.values.data[i][3]);
                                                                                                            parentLabelArray.push(result.values.data[i][3]);
                                                                                                    }
                                                                                                    }else{
                                                                                                            me.nes.idvalueparent[result.values.data[i][2]] = result.values.data[i][1];
                                                                                                            me.nes.labelparent.push(result.values.data[i][2]);
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
                                                                             var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2);	
                                                                    }else{
                                                                            var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData3);	
                                                                    }
                                                            }else{
                                                                     var selectData = new Array(timeData, rawKpiData,neSelectData,neSelectData2,neSelectData3);	
                                                            }
                                                                    
                                                                    //On  r�cup�re les label des ne pour remplir la carte et les infobulle
                                                                    me.requestData = {};
                                                                    
                                                                    me.requestData.method = 'getDataAndLabels';
                                                                    me.requestData.parameters = {};
                                                                    me.requestData.parameters.select = {};
                                                                    me.requestData.parameters.select.data = selectData;
                                                                    me.requestData.parameters.filters = {};
                                                                    
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
                                                                    //TODO param�trer la requ�te pour retourner les donn�es pour la derni�re date int�gr� en mode roaming
                                                                    if(config['fullscreen']== "true" && config['roaming']== "true"){
                                                                            limitFilter.date = me.lastintegrationdate;
                                                                            limitFilter.timelevel = timeUnit;
                                                                    }
                                            
                                                                    // Network Agregation
                                                                    var neData = {}; 
                                                                    neData.type = 'na';
                                                                    neData.operator = 'in';
                                                                    if(config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['network_axis_number'] == '2'){
                                                                            neData.id=config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_network_level'];
                                                                    }else{
                                                                            if(config['roaming'] == 'true'){
                                                                                    neData.id=me.levelChildId;
                                                                            }else{
                                                                                    neData.id=config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_network_level'];
                                                                            }
                                                                    }
                                                                    
                                                                    neData.value=me.nes.ids.join(',');
                                                                    
                                                                    var neId2 = null;
                                                                    if (config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['network_axis_number'] == '2') {
                                                                            var neData2 = {}; 
                                                                            var neId2 = config['kpi_groups']['group'][me.index]['kpis']['kpi_trend']['roaming_network_level2'];
                                                                            neData2.type = 'na_axe3';
                                                                            neData2.operator = 'in';
                                                                            if(config['roaming'] == 'true'){
                                                                                    neData2.id = me.levelChildId; //neId2
                                                                            }else{
                                                                                    neData2.id = neLevelAxe2;
                                                                            }
                                                                            
                                                                            neData2.value =me.nes.ids2.join(',');
                                                                            
                                                                    }
    
                                            
                                                                    if (neId2 != null) {
                                                                             var filtersData = new Array(neData,neData2, limitFilter);
                                                                    } else {
                                                                             var filtersData = new Array(neData, limitFilter);
                                                                    }
                                                                    
                                                                    //var filtersData = new Array(neData, limitFilter);	        
                                                                    me.requestData.parameters.filters.data = filtersData;
                                                                    
                                                            }else{
                                                                    me.nes.ids=new Array();
                                                                    me.nes.ids2=new Array();
                                            //get network elements
                                            //loop through all ne
                                                  /**
                                                                    Ext.Array.each(config['network_elements']['network_element'], function(ne, index) {
                                                me.nes.ids.push(ne.ne_id);
                                                if (typeof(config['network_elements']['network_element'][0]['ne_id2']) !== 'undefined' && config['network_elements']['network_element'][0]['ne_id2'] != "") {
                                                            me.nes.ids2.push(ne.ne_id2);
                                                            }
                                            }); 
                                            **/
                                            var currentNeId = "";
                                                                            var currentNeId2 = "";
                                                                            me.blockedAxe = 'none';
                                                                            //loop through all ne to get NE id and map zone id
                                                                            Ext.Array.each(config['network_elements']['network_element'], function(ne, index) {
                                                                                    if(typeof(ne.ne_id2) !== 'undefined' && ne.ne_id2 !== ''){
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
                                                                                    }
                                                                                    
                                                                            });
                                            
                                            if(fullscreen){
                                                limitFilter.value = me.nes.ids.length;
                                            }else{
                                                limitFilter.value = config['units_number']*me.nes.ids.length;
                                            }
                                            
                                                                    var rawKpiData = {};
                                            rawKpiData.id = rawKpiId;        
                                            rawKpiData.productId = rawKpiProductId;
                                            rawKpiData.type = rawKpiType;       
                                            var selectData = new Array(timeData, rawKpiData);   
                                            
                                            me.requestData = {};
                                            me.requestData.method = 'getDataAndLabels';
                                            me.requestData.parameters = {};
                                            me.requestData.parameters.select = {};
                                            me.requestData.parameters.select.data = selectData;
                                            me.requestData.parameters.filters = {};
                                            
                                            // Network Agregation
                                            var neData = {}; 
                                            neData.type = 'na';
                                            neData.operator = 'in';
                                            neData.id=config['network_elements']['network_level'];
                                            neData.value=me.nes.ids.join(',');
                                           
                                            var neId2 = null;
                                                                    if (typeof(config['network_elements']['network_element'][0]['ne_id2']) !== 'undefined' && config['network_elements']['network_element'][0]['ne_id2'] != "") {
                                                                            var neData2 = {}; 
                                                                            var neId2 = config['network_elements']['network_level2'];
                                                                            neData2.type = 'na_axe3';
                                                                            neData2.operator = 'in';
                                                                            neData2.id = neId2
                                                                            neData2.value =me.nes.ids2.join(',');
                                                                            
                                                                    }
                                            
                                            if (neId2 != null) {
                                                                             var filtersData = new Array(neData,neData2, limitFilter);
                                                                    } else {
                                                                             var filtersData = new Array(neData, limitFilter);
                                                                              }           
                                            me.requestData.parameters.filters.data = filtersData; 
                                                            }
                                                           
                                    var requestParam = {};
                                        requestParam.data = Ext.encode(me.requestData);
                                        Ext.Ajax.request({
                                        url: 'proxy/dao/api/querydata/index.php',
                                        params: requestParam,
                                                                            async : false,
                                        success: function (response) {
                                            result = Ext.decode(response.responseText);
                                            if(config['roaming'] == 'true' ){
                                                        Ext.each(result.values, function(value) {
                                                                                                  Ext.each(value.data , function(k,v){
                                                                                                                   if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
                                                                                                                           me.nes.zoneslabels[k[4]]=k[3];
                                                                                                                   }else{
                                                                                                                           me.nes.zoneslabels[k[3]]=k[2];
                                                                                                                   }
                                                                                                        });
                                                                                        });
                                            }
                                            //kpi label
                                            var kpi_label=result['labels'][0]['label'];
    
                                            //time
                                            var time_label=result['values']['data'][0][0];
                                            
                                            var date = '';
                                            
                                            date=result.values.data[0][0];
    
   
                                            
                                            //na level
                                            var na_label=result['labels'][1]['na'];
                                            var csvdata=[];
                                            var csvdata2=[]
                                            if(fullscreen){
                                                if(config['roaming'] == 'true' ){
                                                        me.isRoaming = true;
                                                        if(axis == 1){
                                                                csvdata.push(new Array('Hour='+date));
                                                                        csvdata.push(new Array(na_label,na2_label,kpi_label));
                                                                Ext.Array.each(nes.ids, function(ne, index) {
                                                                    csvdata.push(new Array(ne,result['values']['data'][index][1]));
                                                                }); 
                                                        }else{
                                                                var na2_label=result['labels'][2]['na_axe3'];
                                                                csvdata.push(new Array('Hour='+date));
                                                                        csvdata.push(new Array("Country","PLMN",kpi_label));
    
                                                                Ext.Array.each(me.nes.labelparent, function(ne, index) {
                                                                        Ext.each(result.values, function(value) {
                                                                                                                              Ext.each(value.data , function(k,v){
                                                                                                                                           if(typeof neLevelAxe2 !== 'object' && neLevelAxe2 != ''){
                                                                                                                                                   if(k[3] == ne){
                                                                                                                                                           var currentValue = k[1] != "" ? k[1] : "No data";	
                                                                                                                                                           if(ne.toLowerCase() != 'unknown'){
                                                                                                                                                                   csvdata.push(new Array(ne,k[4],currentValue));
                                                                                                                                                           }
                                                                                                                                                   }
                                                                                                                                           }else{
                                                                                                                                                   if(k[2] == ne){
                                                                                                                                                           var currentValue = k[1] != "" ? k[1] : "No data";
                                                                                                                                                           if(ne.toLowerCase() != 'unknown'){
                                                                                                                                                                   csvdata.push(new Array(ne,k[3],currentValue));
                                                                                                                                                           }
                                                                                                                                                   }
                                                                                                                                                   
                                                                                                                                           }
                                                                                                                            });
                                                                                                                });
                                                                })
                                                                                                                        
                                                                csvdata2.push(new Array('Hour='+date));
                                                                        csvdata2.push(new Array("Country",kpi_label));
                                                                        
                                                                        
                                                            Ext.Array.each(me.nes.labelparent, function(ne, index) {
                                                                                var currentValue = me.nes.idvalueparent[ne] != "" ? me.nes.idvalueparent[ne] : "No data";
                                                                                if(ne.toLowerCase() != 'unknown'){
                                                                                        csvdata2.push(new Array(ne,currentValue));
                                                                                }
                                                                    
                                                                }); 
                                                        }
                                                        
                                                }else{
                                                        csvdata.push(new Array('Hour='+date));
                                                        csvdata.push(new Array(na_label,kpi_label));
                                                        if(me.blockedAxe == 2){
                                                                Ext.Array.each(me.nes.ids, function(ne, index) {
                                                                    csvdata.push(new Array(ne,result['values']['data'][index][1]));
                                                                }); 
                                                        }else{
                                                                 Ext.Array.each(me.nes.ids2, function(ne, index) {
                                                                    csvdata.push(new Array(ne,result['values']['data'][index][1]));
                                                                }); 
                                                        }
                                                }
                                               
                                                
                                            }
                                            else{
                                                csvdata.push(new Array('KPI='+kpi_label));
                                                //set csv headers
                                                var headers=[];
                                                headers.push('Hour');
                                                neslength=me.nes.ids.length;
                                                for (var v = 0; v < neslength; v++) {
                                                    if(me.blockedAxe != 'none' ){
                                                            if(me.blockedAxe == 2){
                                                                       headers.push(me.nes.ids[v]);
                                                                }else{
                                                                           headers.push(me.nes.ids2[v]);
                                                                }
                                                        }else{
                                                                
                                                                headers.push(me.nes.ids[v]);
                                                        }
                                                }
                                                csvdata.push(headers);
                                                //now, put values in
                                                datalength=result.values.data.length;
                                                
                                                var datas=[];
                                                for (var k = 0; k < datalength; k++) {
                                                    if(k%neslength!=0){
                                                        data[k%neslength+1]=result.values.data[k][1];
                                                    }
                                                    else{
                                                        if(k!=0)datas.push(data);
                                                        var data=[];
                                                        data[k%neslength]=result.values.data[k][0];
                                                        data[k%neslength+1]=result.values.data[k][1];   
                                                    }
                                                   
                                                }
                                                //case when we have only one date displayed
                                                if(datas.length == 0){
                                                    datas.push(data)
                                                }
                                            }
                                            csvdata=Ext.Array.merge(csvdata,datas); 
                                            
                                            widgets['widget'][0]['csvdata'] = [csvdata];
                                                                                    widgets['widget'][0]['csvdata2'] = [csvdata2];
    
                                            var mapSvgId = 'chartsPanel_' + selectedTab + '_'
                                            + widgets['widget'][0]['id'] + '_map';
                                            
                                            //inspired from AmExport, create a canvas, render the svg to this canvas then export to image   
                                            var wrapper       = document.getElementById(mapSvgId);
                                            var svgs          = wrapper.getElementsByTagName('svg');
                                            
                                            var options       = {
                                                ignoreAnimation :   true,
                                                ignoreMouse     :   true,
                                                ignoreClear     :   true,
                                                ignoreDimensions:   true,
                                                offsetX         :   0,
                                                offsetY         :   0
                                            };
                                            var canvas        = document.createElement('canvas');
                                            var context       = canvas.getContext('2d');
                                            var counter       = {
                                                height            : 0,
                                                width             : 0
                                            }
                                            
                                            // Nasty workaround until somebody figured out to support images
                                            function removeImages(svg) {
                                                var startStr    = '<image';
                                                var stopStr     = '</image>';
                                                var stopStrAlt  = '/>';
                                                var start       = svg.indexOf(startStr);
                                                var match       = '';
                                                
                                                // Recursion
                                                if ( start != -1 ) {
                                                    var stop = svg.slice(start,start+1000).indexOf(stopStr);
                                                    if ( stop != -1 ) {
                                                        svg = removeImages(svg.slice(0,start) + svg.slice(start + stop + stopStr.length,svg.length));
                                                    } else {
                                                        stop = svg.slice(start,start+1000).indexOf(stopStrAlt);
                                                        if ( stop != -1 ) {
                                                            svg = removeImages(svg.slice(0,start) + svg.slice(start + stop + stopStr.length,svg.length));
                                                        }
                                                    }
                                                }
                                                return svg;
                                            };
                                            
                                            // Setup canvas
                                            //add legend height 45
                                            canvas.height     = wrapper.offsetHeight+45;
                                            canvas.width      = wrapper.offsetWidth;
                                            context.fillStyle = '#FFFFFF';
                                                context.fillRect(0,0,canvas.width,canvas.height);
       
                                                // Add SVGs
                                            for( i = 0; i < svgs.length; i++ ) {
                                                var container = svgs[i].parentNode;
                                                var innerHTML = removeImages(container.innerHTML); // remove images from svg until its supported
                                              
                                                options.offsetY = counter.height;
                                                
                                                counter.height += container.offsetHeight;
                                                counter.width = container.offsetWidth;
                                                
                                                canvg(canvas,innerHTML,options);
                                            }
                                            
                                            // Return output data URL
                                            var mapPngDataURL= canvas.toDataURL();
                                                                                    if(config['roaming'] == 'true' ){
                                                            widgets['widget'][0]['maptitle'] = 'MAP_Roaming_'+kpi_label+'_'+date;
                                                            widgets['widget'][0]['csvParent'] = 'MAP_Roaming_Country_'+kpi_label+'_'+date;
                                                            widgets['widget'][0]['csvChild'] = 'MAP_Roaming_PLMN_'+kpi_label+'_'+date;
                                                                                    }else{
                                                                                             widgets['widget'][0]['maptitle'] = 'MAP_Fullscreen_'+kpi_label+'_'+date;
                                                                                    }
                                            
                                            widgets['widget'][0]['mapdataurl'] = mapPngDataURL;
    
                                            if(!fullscreen){
                                                
                                                widgets['widget'][0]['maptitle'] = 'MAP_'+kpi_label+'_'+date;
                                                //donut svg
                                                var donutSvgId = 'chartsPanel_' + selectedTab + '_'
                                                + widgets['widget'][0]['id'] + '_donut';
                                                
                                                var donutSvg=document.getElementById(donutSvgId).firstChild;
                                                var donutPngDataURL = donutSvg.toDataURL("image/png");
                                                //get donut kpi label and date
                                                
                                                var donutCmp=Ext.getCmp(donutSvgId);
                                                
                                                var donut_kpi_label=donutCmp.store.getRange()[0].data['kpi'];
                                                var re = /\//gi;
                                                var donut_date=donutCmp.store.getRange()[0].data['date'].replace(re,'');
                                                                    
                                                widgets['widget'][0]['donuttitle'] = 'Donut_'+donut_kpi_label+'_'+donut_date;
                                                widgets['widget'][0]['donutdataurl'] = donutPngDataURL;
                                                
                                                //trend svg
                                                var trendSvgId = 'chartsPanel_' + selectedTab + '_'
                                                + widgets['widget'][0]['id'] + '_trend';
                                                
                                                var trendSvg=document.getElementById(trendSvgId).firstChild;
                                                var trendPngDataURL = trendSvg.toDataURL("image/png");
                                                widgets['widget'][0]['trendtitle'] = 'Trend_'+kpi_label+'_'+date;
                                                widgets['widget'][0]['trenddataurl'] = trendPngDataURL;
                                            }
                                            
    
                                            
                                            Ext.Ajax.request({
                                                url : 'proxy/export.php',
                                                params : {
                                                    task : 'EXPORT',
                                                    tab : tabTitle,
                                                    templateId : tabTemplate,
                                                    widgets : Ext.encode(widgets),
                                                    roaming : me.isRoaming
                                                    
                                                },
                    
                                                success : function(response) {
                                                    button.enable();
                                                    if (type == 'mail') {
                                                        var date = new Date();
                                                        var month = date.getMonth()+1;
                                                       
                                                        if (month < 10)
                                                            month = '0' + month;
                                                        var day = date.getDate();
                                                        if (day < 10)
                                                            day = '0' + day;
                                                        var mailSubject = '[My Homepage reporting] '
                                                                + tabTitle
                                                                + ' - '
                                                                + date.getFullYear()
                                                                + '/'
                                                                + month
                                                                + '/' + day;
                    
                                                        // Link the mail to the PDF
                                                        var mailBody = response.responseText;
                    
                                                        // Redirect to the mailto
                                                        document.location.href = 'mailto:?subject='
                                                                + mailSubject + '&body=' + mailBody;
                                                    } else if (type == 'file') {
                                                        // Open the PDF
                                                        window.open(response.responseText);
                                                    }
                                                }
                                            });
                                        }
                                    }); 
                                }
                            }); 
                        } else if (widgets['widget'][i]['type'] == 'grid') {
                            var gridId = 'chartsPanel_' + selectedTab + '_'
                                    + widgets['widget'][i]['id'];
                            var grid = Ext.getCmp(gridId).down('gridpanel');
    
                            var gridData = new Array();
    
                            // Put the headers in the result array
                            var headers = new Array();
                            for (var c = 0; c < grid.columns.length; c++) {
                                headers.push(grid.columns[c]['text']);
                            }
                            gridData.push(headers);
    
                            // Put the datas
                            for (var d = 0; d < grid.getStore().data.items.length; d++) {
                                gridData
                                        .push(grid.getStore().data.items[d]['data']);
                            }
    
                            widgets['widget'][i]['data'] = gridData;
                            widgets['widget'][i]['title'] = Ext
                                    .getCmp('chartsPanel_' + selectedTab + '_'
                                            + widgets['widget'][i]['id']).title;
                        } else if (widgets['widget'][i]['type'] == 'gridarray') {
                            var gridId = 'chartsPanel_' + selectedTab + '_'
                                    + widgets['widget'][i]['id']
                                    + '_cellssurveillancemain';
                            var grids = Ext.getCmp(gridId).items.items;
    
                            var gridData = new Array();
                            for (var g = 0; g < grids.length; g++) {
                                var newGrid = new Array();
    
                                newGrid.push(grids[g].title);
    
                                // Put the headers in the result array
                                var headers = new Array();
                                var dataIndex = new Array();
                                for (var c = 0; c < grids[g].columns.length; c++) {
                                    if (!grids[g].columns[c]['hidden']) {
                                        headers.push(grids[g].columns[c]['text']);
                                        dataIndex
                                                .push(grids[g].columns[c]['dataIndex']);
                                    }
                                }
                                newGrid.push(headers);
    
                                // Put the datas
                                for (var d = 0; d < grids[g].getStore().data.items.length; d++) {
                                    var data = new Array();
                                    for (var id = 0; id < dataIndex.length; id++) {
                                        data
                                                .push(grids[g].getStore().data.items[d]['data'][dataIndex[id]]);
                                    }
                                    newGrid.push(data);
                                }
                                gridData.push(newGrid);
                            }
                            widgets['widget'][i]['data'] = gridData;
                            widgets['widget'][i]['title'] = Ext
                                    .getCmp('chartsPanel_' + selectedTab + '_'
                                            + widgets['widget'][i]['id']).title;
                        } else if (widgets['widget'][i]['type'] == 'graphpanel') {
                          
                            // R�cup�ration du graph
                            // var svgId = 'alarms_graph_trend';
                            var chartid = 'chartsPanel_' + selectedTab + '_'
                                    + widgets['widget'][i]['id'];
                            var charts = Ext.getCmp(chartid).query('chart');
                            var chartsArray = new Array();
                            var nameChartsArray = new Array();
                            for (var c = 0; c < charts.length; c++) {
                                var svgId = charts[c].id;
                                if (svgId.indexOf('donut') != -1){
                                    if(Ext.getCmp(svgId).store.data.items.length != 0){
                                        nameChartsArray.push(svgId);
                                        var svg = document.getElementById(svgId).firstChild;
                                        var pngDataURL = svg.toDataURL("image/png");
                                        chartsArray.push(pngDataURL);
                                    }
                                }else{
                                    nameChartsArray.push(svgId);
                                    var svg = document.getElementById(svgId).firstChild;
                                    var pngDataURL = svg.toDataURL("image/png");
                                    chartsArray.push(pngDataURL);   
                                }
                                
                            }
                            widgets['widget'][i]['dataurl'] = chartsArray;
                            widgets['widget'][i]['title'] = nameChartsArray;
                            
                            // Recuperation des tableaux audit report alarms graph et summary graph
                            var grids = Ext.getCmp(chartid).query('grid');
                            var gridArray = new Array();
                            var indexAg = 1;
                            var indexSg = 1;
                            for (var c = 0; c < grids.length; c++) {
                                var gridId = grids[c].id;
                                var headerGrid = Ext.getCmp(gridId).items.items[0].headerCt.items.items;
                                var dataGrid = Ext.getCmp(gridId).items.items[0].store.data.items;
                                var headerArray = new Array();
                                var finalArray = new Array();
                                var alarmsGraphArray = new Array();
                               
                                // Si on se trouve dans le cas d'un alarms graph
                                if (gridId.indexOf("summary") == -1) {
                                    var grid_name = 'alarms_graph_grid_' + indexAg;
                                    indexAg++;
                                    for (var x = 0; x < headerGrid.length; x++) {
                                        headerArray.push(headerGrid[x].dataIndex);
                                    }
                                    finalArray.push(headerArray);
                                    for (var x = 0; x < dataGrid.length; x++) {
                                        dataObj = dataGrid[x].data;
                                        var dataArray = new Array();
                                        for (var key in dataObj) {
                                            if (key == 'month') {
                                                dataArray.unshift(dataObj[key]);
                                            } else {
                                                dataArray.push(dataObj[key]);
                                            }
                                        }
    
                                        finalArray.push(dataArray);
    
                                    }
                                    // On recupere le nombre de alarms_graph pour
                                    // pouvoir boucler dessus dans l export
                                    widgets['widget'][i]['alarms_graph_number'] = [indexAg- 1];
                                    widgets['widget'][i][grid_name] = [finalArray];
                                }
    
                                // Si on se trouve dans le cas d'un summary graph
                                else {
                                    // on r�cup�re les header � partir des cl�s du
                                    // tableau (autre methode utiliser les headerCt
                                    // de la grid)
                                    // on fixe les header en dure
                                    var headerArray = ['Month', 'Warning cells',
                                            'Penalty cells', 'Ref month']
                                    finalArray.push(headerArray);
    
                                    for (var x = 0; x < dataGrid.length; x++) {
                                        dataObj = dataGrid[x].data;
                                        var dataArray = new Array();
                                        for (var key in dataObj) {
                                            // On remet les valeur dans l'ordre des
                                            // header du tableau audit report
                                            switch (key) {
                                                case 'time' :
                                                    var dateLabel = Ext.Date.format(Ext.Date.parse(dataObj[key],'Ym'),'M-Y');
                                                    dataArray.splice(0, 0,dateLabel);
                                                    break;
                                                case 'warning' :
                                                    dataArray.splice(1, 0,dataObj[key]);
                                                    break;
                                                case 'penalty' :
                                                    dataArray.splice(2, 0,dataObj[key]);
                                                    break;
                                                case 'csv' :
                                                    break;
                                                case 'reftime' :
                                                    var refDate = Ext.Date.format(Ext.Date.parse(dataObj[key],'Ym'),'M-Y');
                                                    dataArray.splice(3, 0,refDate);
                                                    break;
                                            }
                                        }
                                        finalArray.push(dataArray);
                                    }
                                   
                                    if (gridId.indexOf("summary_graph_evo") == -1) {
                                        widgets['widget'][i]['summary_graph'] = [finalArray];
                                    }
                                    else{
                                        widgets['widget'][i]['summary_graph_evo'] = [finalArray];
                                       
                                       
                                    }    
                                }
    
                            }
                            if (gridId.indexOf("summary_graph_evo") == -1) {
                            // Creation des grids necessaire pour les csv penalty et
                            // warning
                            var summaryGraph = 'chartsPanel_' + selectedTab + '_'
                                    + widgets['widget'][i]['id']
                                    + '_summary_graph_trend'
                            var dataSummaryGraph = Ext.getCmp(summaryGraph).store.data.items;
                            var currentDate = dataSummaryGraph[0].data.time;
                            var gridDataWarning = new Array();
                            var gridDataPenalty = new Array();
                            // On crree les header de nos tableau warning et penalty
                            var headers = ['Cell_id', 'Cell_label', 'Days_in_fault'];
                            gridDataWarning.push(headers);
                            gridDataPenalty.push(headers);
    
                            // On r�cup�re le noeud qui concerne le mois courant
                            for (var c = 0; c < dataSummaryGraph.length; c++) {
                                if (dataSummaryGraph[0].data.time >= currentDate) {
                                    currentDate = dataSummaryGraph[0].data.time;
                                    var dataArray = dataSummaryGraph[c].data;
                                }
                            }
                            
    
                            // On recupere les donnees du tableau des penalties
                            for (var c = 0; c < dataArray.csv.penalty.length; c++) {
                                var cell_id = dataArray.csv.penalty[c].cell_id;
                                var cell_label = dataArray.csv.penalty[c].cell_label;
                                var occurences = dataArray.csv.penalty[c].occurences;
                                var curPen = [cell_id, cell_label, occurences];
                                gridDataPenalty.push(curPen);
                            }
    
                          
    
                            // On recupere les donnees du tableau des warning
                            for (var c = 0; c < dataArray.csv.warning.length; c++) {
                                var cell_id = dataArray.csv.warning[c].cell_id;
                                var cell_label = dataArray.csv.warning[c].cell_label;
                                var occurences = dataArray.csv.warning[c].occurences;
                                var curWarn = [cell_id, cell_label, occurences];
                                gridDataWarning.push(curWarn);
                            }
    
                            
    
                            widgets['widget'][i]['warning'] = [gridDataWarning];
                            widgets['widget'][i]['penalty'] = [gridDataPenalty];
    
                            }else{
                                    // Creation des grids necessaire pour les csv penalty et
                                    // warning
                                    // pour le template audit report evo
                            
                                    var summaryGraphEvo = 'chartsPanel_' + selectedTab + '_'
                                    + widgets['widget'][i]['id']
                                    + '_summary_graph_evo_trend';
                                    
                                    var dataSummaryGraphEvo = Ext.getCmp(summaryGraphEvo).store.data.items;
                                    var currentDateEvo = dataSummaryGraphEvo[0].data.time;
                                    
                                    var gridDataWarningEvo = new Array();
                                    var gridDataPenaltyEvo = new Array();
                                    // On crree les header de nos tableau warning et penalty
                                    var headersEvo = ['Cell_id', 'Cell_label', 'Days_in_fault'];
                                    gridDataWarningEvo.push(headersEvo);
                                    gridDataPenaltyEvo.push(headersEvo);
                                    
                                    // On r�cup�re le noeud qui concerne le mois courant  pour le template audit report evo
                                    for (var c = 0; c < dataSummaryGraphEvo.length; c++) {
                                            if (dataSummaryGraphEvo[0].data.time >= currentDateEvo) {
                                                    currentDateEvo = dataSummaryGraphEvo[0].data.time;
                                                    var dataArrayEvo = dataSummaryGraphEvo[c].data;
                                                    
                                            }
                                    }
    
                                    // On recupere les donnees du tableau des penalties pour le template audit report evo
                                    for (var c = 0; c < dataArrayEvo.csv.penalty.length; c++) {
                                            var cell_id_evo = dataArrayEvo.csv.penalty[c].cell_id;
                                            var cell_label_evo = dataArrayEvo.csv.penalty[c].cell_label;
                                            var occurences_evo = dataArrayEvo.csv.penalty[c].occurences;
                                            var curPen_evo = [cell_id_evo, cell_label_evo, occurences_evo];
                                            gridDataPenaltyEvo.push(curPen_evo);
                                    }
                                    
                                    // On recupere les donnees du tableau des warning pour le template audit report evo
                                    for (var c = 0; c < dataArrayEvo.csv.warning.length; c++) {
                                            var cell_id_evo = dataArrayEvo.csv.warning[c].cell_id;
                                            var cell_label_evo = dataArrayEvo.csv.warning[c].cell_label;
                                            var occurences_evo = dataArrayEvo.csv.warning[c].occurences;
                                            var curWarn_evo = [cell_id_evo, cell_label_evo, occurences_evo];
                                            gridDataWarningEvo.push(curWarn_evo);
                                    }
    
    
                                    widgets['widget'][i]['warning_evo'] = [gridDataWarningEvo];
                                    widgets['widget'][i]['penalty_evo'] = [gridDataPenaltyEvo];
                            }
                            
                            //TODO
                            // Detailed report
                            var tabConfId = Ext.getCmp('tabPanel').getActiveTab().getId();
                            var tabId = tabConfId + '_chart1';
                            var result = [];
                            var alarmOptions = {};
                            
    
                            Ext.Ajax.request({
                            url: 'proxy/configuration.php',
                            params: {
                                task: 'LOAD',
                                tab: tabConfId
                            },
        
                                success: function (response) {
                                    var curConfig = Ext.decode(response.responseText);
                                    var alarm_ids = new Array();
                                    Ext.Object
                                            .each(
                                                    curConfig['widgets']['widget'][0]['calc_alarms']['alarm'],
                                                    function(index, alarm) {
                                                        alarm_ids.push(alarm.id);
                                                    });
                                    var sdp_id = curConfig['widgets']['widget'][0]['sdp_id'];
                                    var timeselector = Ext.getCmp(tabId+ '_TimeSelector_AuditReport');
                                    var time = timeselector.value;
                                    time = Ext.Date.format(time, 'Ym');
                                    alarmOptions.sdp_id = sdp_id;
                                    alarmOptions.current_date = time;
                                    alarmOptions.alarm_ids = alarm_ids;
                                        
                                        Ext.Ajax.request({
                                        url : 'proxy/alarm_list.php',
                                        params : {
                                            task : 'GET_DETAILED_REPORT',
                                            params : {params : Ext.encode(alarmOptions)}
                                        },
                                        success: function (response) {
                                                var result = Ext.decode(response.responseText);
                                                widgets['widget'][0]['detailed_report'] = [result];
                                                
                                                Ext.Ajax.request({
                                                    url : 'proxy/export.php',
                                                    params : {
                                                        task : 'EXPORT',
                                                        tab : tabTitle,
                                                        templateId : tabTemplate,
                                                        sdp_id : sdp_id,
                                                        time: time,
                                                        alarm_ids : Ext.encode(alarm_ids),
                                                        config: Ext.encode(curConfig),
                                                        widgets : Ext.encode(widgets)                                                

                                                    },
                        
                                                    success : function(response) {
                                                        button.enable();
                                                        if (type == 'mail') {
                                                            var date = new Date();
                                                            var month = date.getMonth()+1;
                                                            console.log(month);
                                                            if (month < 10)
                                                                month = '0' + month;
                                                            var day = date.getDate();
                                                            if (day < 10)
                                                                day = '0' + day;
                                                            var mailSubject = '[My Homepage reporting] '
                                                                    + tabTitle
                                                                    + ' - '
                                                                    + date.getFullYear()
                                                                    + '/'
                                                                    + month
                                                                    + '/' + day;
                        
                                                            // Link the mail to the PDF
                                                            var mailBody = response.responseText;
                                                            var filePath = window.location.host+mailBody;
                                                            // Redirect to the mailto
                                                            document.location.href = 'mailto:?subject='
                                                                    + mailSubject + '&body=' + filePath;
                                                        } else if (type == 'file') {
                                                            // Open the PDF
                                                            window.open(response.responseText);
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                    
                                }
                            });
                        }
                    }
                    if((widgets['widget'][0]['type'] != 'graphpanel') && (widgets['widget'][0]['type'] != 'map')){
                        Ext.Ajax.request({
                                    url : 'proxy/export.php',
                                    params : {
                                        task : 'EXPORT',
                                        tab : tabTitle,
                                        templateId : tabTemplate,
                                        widgets : Ext.encode(widgets)
                                    },
        
                                    success : function(response) {
                                        button.enable();
                                        if (type == 'mail') {
                                            var date = new Date();
                                            var month = date.getMonth()+1;
                                            if (month < 10)
                                                month = '0' + month;
                                            var day = date.getDate();
                                            if (day < 10)
                                                day = '0' + day;
                                            var mailSubject = '[My Homepage reporting] '
                                                    + tabTitle
                                                    + ' - '
                                                    + date.getFullYear()
                                                    + '/'
                                                    + month
                                                    + '/' + day;
        
                                            // Link the mail to the PDF
                                            var mailBody = response.responseText;
        
                                            // Redirect to the mailto
                                            document.location.href = 'mailto:?subject='
                                                    + mailSubject + '&body=' + mailBody;
                                        } else if (type == 'file') {
                                            // Open the PDF
                                            window.open(response.responseText);
                                            
                                        }
                                    }
                        });
                }
                }
            });
        },
    
        // Get the tabs from the configuration file
        getTab : function(panel) {
            var me = this;
    
            Ext.Ajax.request({
                        url : 'proxy/configuration.php',
                        params : {
                            task : 'GET_TABS'
                        },
    
                        success : function(result) {
                            me.createTabs(result);
                        }
                    });
        },
    
        // Create a tab in the main panel
        createTab : function(config) {
            var tabPanel = Ext.getCmp('tabPanel');
    
            // Create the panel
            var panelId = 'chartsPanel_' + config['id'];
    
            var panel = Ext.create('homepage.view.charts.ChartsPanel', {
                        id : panelId,
                        title : config['title'],
                        templateId : config['template']['@attributes']['id']
                    });
    
            if (typeof(config['template']['row'].length) === 'undefined') {
                // tab copy, only one config['template']['row']
    
                // Create the row
                var flex = parseInt(config['template']['row']['@attributes']['rowspan']);
                var row = Ext.create('Ext.panel.Panel', {
                            layout : {
                                type : 'hbox',
                                align : 'stretch',
                                pack : 'start'
                            },
                            flex : flex,
                            cls : 'x-panel-no-border'
                        });
    
                // Create the chart for type frame, map, grid and gridarray
    
                if (config['template']['row']['widget']['@attributes']['type'] == 'frame') {
                    flex = parseInt(config['template']['row']['widget']['@attributes']['colspan']);
                    var chart = Ext.create('homepage.view.charts.Frame', {
                        id : panelId
                                + '_'
                                + config['template']['row']['widget']['@attributes']['id'],
                        title : ' ',
                        flex : flex
                    });
                } else if (config['template']['row']['widget']['@attributes']['type'] == 'map') {
                    flex = parseInt(config['template']['row']['widget']['@attributes']['colspan']);
                    var chart = Ext.create('homepage.view.charts.Map', {
                        id : panelId
                                + '_'
                                + config['template']['row']['widget']['@attributes']['id'],
                        title : ' ',
                        flex : flex
                    });
                } else if (config['template']['row']['widget']['@attributes']['type'] == 'grid') {
                    flex = parseInt(config['template']['row']['widget']['@attributes']['colspan']);
                    var chart = Ext.create('homepage.view.charts.GridReport', {
                        id : panelId
                                + '_'
                                + config['template']['row']['widget']['@attributes']['id'],
                        title : ' ',
                        flex : flex
                    });
                } else if (config['template']['row']['widget']['@attributes']['type'] == 'gridarray') {
                    flex = parseInt(config['template']['row']['widget']['@attributes']['colspan']);
                    var chart = Ext.create(
                            'homepage.view.charts.CellsSurveillance', {
                                id : panelId
                                        + '_'
                                        + config['template']['row']['widget']['@attributes']['id'],
                                flex : flex
                            });
                } else if (config['template']['row']['widget']['@attributes']['type'] == 'graphpanel') {
                    flex = parseInt(config['template']['row']['widget']['@attributes']['colspan']);
                    var chart = Ext.create('homepage.view.charts.AuditReport', {
                        id : panelId
                                + '_'
                                + config['template']['row']['widget']['@attributes']['id'],
                        flex : flex
                    });
                }
    
                row.add(chart);
                panel.add(row);
            } else {
                // Add the charts
                for (var r = 0; r < config['template']['row'].length; r++) {
                    // Create the row
                    var flex = parseInt(config['template']['row'][r]['@attributes']['rowspan']);
                    var row = Ext.create('Ext.panel.Panel', {
                                layout : {
                                    type : 'hbox',
                                    align : 'stretch',
                                    pack : 'start'
                                },
                                flex : flex,
                                cls : 'x-panel-no-border'
                            });
    
                    if (typeof(config['template']['row'][r]['widget'].length) === 'undefined') {
                        // Just 1 chart
    
                        // Create the chart
                        if (config['template']['row'][r]['widget']['@attributes']['type'] == 'chart') {
                            flex = parseInt(config['template']['row'][r]['widget']['@attributes']['colspan']);
                            var chart = Ext.create('homepage.view.charts.Gauge', {
                                id : panelId
                                        + '_'
                                        + config['template']['row'][r]['widget']['@attributes']['id'],
                                target : panelId
                                        + '_'
                                        + config['template']['row'][r]['widget']['@attributes']['target'],
                                flex : flex
                            });
                        } else if (config['template']['row'][r]['widget']['@attributes']['type'] == 'period') {
                            flex = parseInt(config['template']['row'][r]['widget']['@attributes']['colspan']);
                            var chart = Ext.create(
                                    'homepage.view.charts.PeriodChart', {
                                        id : panelId
                                                + '_'
                                                + config['template']['row'][r]['widget']['@attributes']['id'],
                                        flex : flex
                                    });
                        }
                        row.add(chart);
                    } else {
                        // Several charts
                        for (var w = 0; w < config['template']['row'][r]['widget'].length; w++) {
                            // Create the chart
                            if (config['template']['row'][r]['widget'][w]['@attributes']['type'] == 'chart') {
                                flex = parseInt(config['template']['row'][r]['widget'][w]['@attributes']['colspan']);
                                var chart = Ext.create(
                                        'homepage.view.charts.Gauge', {
                                            id : panelId
                                                    + '_'
                                                    + config['template']['row'][r]['widget'][w]['@attributes']['id'],
                                            target : panelId
                                                    + '_'
                                                    + config['template']['row'][r]['widget'][w]['@attributes']['target'],
                                            flex : flex
                                        });
                            } else if (config['template']['row'][r]['widget'][w]['@attributes']['type'] == 'period') {
                                flex = parseInt(config['template']['row'][r]['widget'][w]['@attributes']['colspan']);
                                var chart = Ext.create(
                                        'homepage.view.charts.PeriodChart', {
                                            id : panelId
                                                    + '_'
                                                    + config['template']['row'][r]['widget'][w]['@attributes']['id'],
                                            flex : flex
                                        });
                            }
                            row.add(chart);
                        }
                    }
                    panel.add(row);
                }
            }
    
            tabPanel.add(panel);
            tabPanel.setActiveTab(panel);
        },
    
        // Add chart panels to the main panel
        createTabs : function(json) {
            var me = this;
    
            var config = Ext.decode(json.responseText);
    
            var isDefault = false;
    
            if (typeof(config['tab']) !== 'undefined') {
                var tabPanel = Ext.getCmp('tabPanel');
    
                if (typeof(config['tab'].length) === 'undefined') {
                    // Just 1 tab
    
                    // Create the panel
                    var panelId = 'chartsPanel_' + config['tab']['id'];
                    var panel = Ext.create('homepage.view.charts.ChartsPanel', {
                                id : panelId,
                                title : config['tab']['title'],
                                templateId : config['tab']['template']['@attributes']['id']
                            });
    
                    // Add the charts
                    if (typeof(config['tab']['template']['row'].length) === 'undefined') {
                        // Just 1 row
    
                        // Create the row
                        var flex = parseInt(config['tab']['template']['row']['@attributes']['rowspan']);
                        var row = Ext.create('Ext.panel.Panel', {
                                    layout : {
                                        type : 'hbox',
                                        align : 'stretch',
                                        pack : 'start'
                                    },
                                    flex : flex,
                                    cls : 'x-panel-no-border'
                                });
    
                        if (typeof(config['tab']['template']['row']['widget'].length) === 'undefined') {
                            // Just 1 chart
    
                            // Create the chart
                            if (config['tab']['template']['row']['widget']['@attributes']['type'] == 'frame') {
                                flex = parseInt(config['tab']['template']['row']['widget']['@attributes']['colspan']);
                                var chart = Ext.create(
                                        'homepage.view.charts.Frame', {
                                            id : panelId
                                                    + '_'
                                                    + config['tab']['template']['row']['widget']['@attributes']['id'],
                                            title : ' ',
                                            flex : flex
                                        });
                            } else if (config['tab']['template']['row']['widget']['@attributes']['type'] == 'map') {
                                flex = parseInt(config['tab']['template']['row']['widget']['@attributes']['colspan']);
                                var chart = Ext.create('homepage.view.charts.Map',
                                        {
                                            id : panelId
                                                    + '_'
                                                    + config['tab']['template']['row']['widget']['@attributes']['id'],
                                            title : ' ',
                                            flex : flex
                                        });
                            } else if (config['tab']['template']['row']['widget']['@attributes']['type'] == 'grid') {
                                flex = parseInt(config['tab']['template']['row']['widget']['@attributes']['colspan']);
                                var chart = Ext.create(
                                        'homepage.view.charts.GridReport', {
                                            id : panelId
                                                    + '_'
                                                    + config['tab']['template']['row']['widget']['@attributes']['id'],
                                            title : ' ',
                                            flex : flex
                                        });
                            }
    
                            else if (config['tab']['template']['row']['widget']['@attributes']['type'] == 'gridarray') {
                                flex = parseInt(config['tab']['template']['row']['widget']['@attributes']['colspan']);
                                var chart = Ext.create(
                                        'homepage.view.charts.CellsSurveillance', {
                                            id : panelId
                                                    + '_'
                                                    + config['tab']['template']['row']['widget']['@attributes']['id'],
                                            title : ' ',
                                            flex : flex
                                        });
                            } else if (config['tab']['template']['row']['widget']['@attributes']['type'] == 'graphpanel') {
                                flex = parseInt(config['tab']['template']['row']['widget']['@attributes']['colspan']);
                                var chart = Ext.create(
                                        'homepage.view.charts.AuditReport', {
                                            id : panelId
                                                    + '_'
                                                    + config['tab']['template']['row']['widget']['@attributes']['id'],
                                            title : ' ',
                                            flex : flex
                                        });
                            }
    
                            if (config['tab']['template']['row']['widget']['@attributes']['id'] == 'chart1') {
                                chart.addCls('x-chart-title-selected');
                            }
                            row.add(chart);
                        } else {
                            // Several charts
    
                            // No template yet !
                        }
                        panel.add(row);
                    } else {
                        // Several rows
                        for (var r = 0; r < config['tab']['template']['row'].length; r++) {
                            // Create the row
                            var flex = parseInt(config['tab']['template']['row'][r]['@attributes']['rowspan']);
                            var row = Ext.create('Ext.panel.Panel', {
                                        layout : {
                                            type : 'hbox',
                                            align : 'stretch',
                                            pack : 'start'
                                        },
                                        flex : flex,
                                        cls : 'x-panel-no-border'
                                    });
    
                            if (typeof(config['tab']['template']['row'][r]['widget'].length) === 'undefined') {
                                // Just 1 chart
    
                                // Create the chart
                                if (config['tab']['template']['row'][r]['widget']['@attributes']['type'] == 'chart') {
                                    flex = parseInt(config['tab']['template']['row'][r]['widget']['@attributes']['colspan']);
                                    var chart = Ext.create(
                                            'homepage.view.charts.Gauge', {
                                                id : panelId
                                                        + '_'
                                                        + config['tab']['template']['row'][r]['widget']['@attributes']['id'],
                                                title : ' ',
                                                target : panelId
                                                        + '_'
                                                        + config['tab']['template']['row'][r]['widget']['@attributes']['target'],
                                                flex : flex
                                            });
                                } else if (config['tab']['template']['row'][r]['widget']['@attributes']['type'] == 'period') {
                                    flex = parseInt(config['tab']['template']['row'][r]['widget']['@attributes']['colspan']);
                                    var chart = Ext.create(
                                            'homepage.view.charts.PeriodChart', {
                                                id : panelId
                                                        + '_'
                                                        + config['tab']['template']['row'][r]['widget']['@attributes']['id'],
                                                title : ' ',
                                                flex : flex
                                            });
                                }
                                if (config['tab']['template']['row'][r]['widget']['@attributes']['id'] == 'chart1') {
                                    chart.addCls('x-chart-title-selected');
                                }
                                row.add(chart);
                            } else {
                                // Several charts
    
                                for (var w = 0; w < config['tab']['template']['row'][r]['widget'].length; w++) {
                                    // Create the chart
                                    if (config['tab']['template']['row'][r]['widget'][w]['@attributes']['type'] == 'chart') {
                                        flex = parseInt(config['tab']['template']['row'][r]['widget'][w]['@attributes']['colspan']);
                                        var chart = Ext.create(
                                                'homepage.view.charts.Gauge', {
                                                    id : panelId
                                                            + '_'
                                                            + config['tab']['template']['row'][r]['widget'][w]['@attributes']['id'],
                                                    title : ' ',
                                                    target : panelId
                                                            + '_'
                                                            + config['tab']['template']['row'][r]['widget'][w]['@attributes']['target'],
                                                    flex : flex
                                                });
                                    } else if (config['tab']['template']['row'][r]['widget'][w]['@attributes']['type'] == 'period') {
                                        flex = parseInt(config['tab']['template']['row'][r]['widget'][w]['@attributes']['colspan']);
                                       
                                        var chart = Ext.create(
                                                'homepage.view.charts.PeriodChart',
                                                {
                                                    id : panelId
                                                            + '_'
                                                            + config['tab']['template']['row'][r]['widget'][w]['@attributes']['id'],
                                                    title : ' ',
                                                    flex : flex
                                                });
                                    }
                                    if (config['tab']['template']['row'][r]['widget'][w]['@attributes']['id'] == 'chart1') {
                                        chart.addCls('x-chart-title-selected');
                                    }
                                    row.add(chart);
                                }
                            }
                            panel.add(row);
                        }
                    }
    
                    tabPanel.add(panel);
                } else {
                    // Several tabs
                    var selectedId = null;
                    for (var t = 0; t < config['tab'].length; t++) {
                        // Create the panel
                        var panelId = 'chartsPanel_' + config['tab'][t]['id'];
                        var panel = Ext.create('homepage.view.charts.ChartsPanel',
                                {
                                    id : panelId,
                                    title : config['tab'][t]['title'],
                                    templateId : config['tab'][t]['template']['@attributes']['id']
                                });
    
                        // If it's the default panel
                        if (config['tab'][t]['selected'] == 'true')
                            selectedId = panelId;
    
                        // Add the charts
                        if (typeof(config['tab'][t]['template']['row'].length) === 'undefined') {
                            // Just 1 row
    
                            // Create the row
                            var flex = parseInt(config['tab'][t]['template']['row']['@attributes']['rowspan']);
                            var row = Ext.create('Ext.panel.Panel', {
                                        layout : {
                                            type : 'hbox',
                                            align : 'stretch',
                                            pack : 'start'
                                        },
                                        flex : flex,
                                        cls : 'x-panel-no-border'
                                    });
    
                            if (typeof(config['tab'][t]['template']['row']['widget'].length) === 'undefined') {
                                // Just 1 chart
    
                                // Create the chart
                                if (config['tab'][t]['template']['row']['widget']['@attributes']['type'] == 'frame') {
                                    flex = parseInt(config['tab'][t]['template']['row']['widget']['@attributes']['colspan']);
                                    var chart = Ext.create(
                                            'homepage.view.charts.Frame', {
                                                id : panelId
                                                        + '_'
                                                        + config['tab'][t]['template']['row']['widget']['@attributes']['id'],
                                                title : ' ',
                                                flex : flex
                                            });
                                } else if (config['tab'][t]['template']['row']['widget']['@attributes']['type'] == 'map') {
                                    flex = parseInt(config['tab'][t]['template']['row']['widget']['@attributes']['colspan']);
                                    var chart = Ext.create(
                                            'homepage.view.charts.Map', {
                                                id : panelId
                                                        + '_'
                                                        + config['tab'][t]['template']['row']['widget']['@attributes']['id'],
                                                title : ' ',
                                                flex : flex
                                            });
                                } else if (config['tab'][t]['template']['row']['widget']['@attributes']['type'] == 'grid') {
                                    flex = parseInt(config['tab'][t]['template']['row']['widget']['@attributes']['colspan']);
                                    var chart = Ext.create(
                                            'homepage.view.charts.GridReport', {
                                                id : panelId
                                                        + '_'
                                                        + config['tab'][t]['template']['row']['widget']['@attributes']['id'],
                                                title : ' ',
                                                flex : flex
                                            });
                                } else if (config['tab'][t]['template']['row']['widget']['@attributes']['type'] == 'gridarray') {
                                    flex = parseInt(config['tab'][t]['template']['row']['widget']['@attributes']['colspan']);
                                    var chart = Ext
                                            .create(
                                                    'homepage.view.charts.CellsSurveillance',
                                                    {
                                                        id : panelId
                                                                + '_'
                                                                + config['tab'][t]['template']['row']['widget']['@attributes']['id'],
                                                        title : ' ',
                                                        flex : flex
                                                    });
                                } else if (config['tab'][t]['template']['row']['widget']['@attributes']['type'] == 'graphpanel') {
                                    flex = parseInt(config['tab'][t]['template']['row']['widget']['@attributes']['colspan']);
                                   
                                    var templateId = config['tab'][t]['template']['@attributes']['id'];
                                    if(templateId == "template10"){
                                        var chart = Ext.create(
                                                'homepage.view.charts.AuditReportEvo', {
                                                        id : panelId
                                                                + '_'
                                                                + config['tab'][t]['template']['row']['widget']['@attributes']['id'],
                                                        title : ' ',
                                                        flex : flex
                                        });
                                    }else{
                                        var chart = Ext.create(
                                                'homepage.view.charts.AuditReport', {
                                                        id : panelId
                                                                + '_'
                                                                + config['tab'][t]['template']['row']['widget']['@attributes']['id'],
                                                        title : ' ',
                                                        flex : flex
                                        });
                                    }
                                }
    
                                if (config['tab'][t]['template']['row']['widget']['@attributes']['id'] == 'chart1') {
                                    chart.addCls('x-chart-title-selected');
                                }
                                row.add(chart);
                            } else {
                                // Several charts
    
                                // No template yet !
                            }
                            panel.add(row);
                        } else {
                            // Several rows                     
                            for (var r = 0; r < config['tab'][t]['template']['row'].length; r++) {
                                // Create the row
                                var flex = parseInt(config['tab'][t]['template']['row'][r]['@attributes']['rowspan']);
                                var row = Ext.create('Ext.panel.Panel', {
                                            layout : {
                                                type : 'hbox',
                                                align : 'stretch',
                                                pack : 'start'
                                            },
                                            flex : flex,
                                            cls : 'x-panel-no-border'
                                        });
    
                                if (typeof(config['tab'][t]['template']['row'][r]['widget'].length) === 'undefined') {
                                    // Just 1 chart
    
                                    // Create the chart
                                    if (config['tab'][t]['template']['row'][r]['widget']['@attributes']['type'] == 'chart') {
                                        flex = parseInt(config['tab'][t]['template']['row'][r]['widget']['@attributes']['colspan']);
                                        var chart = Ext.create(
                                                'homepage.view.charts.Gauge', {
                                                    id : panelId
                                                            + '_'
                                                            + config['tab'][t]['template']['row'][r]['widget']['@attributes']['id'],
                                                    title : ' ',
                                                    target : panelId
                                                            + '_'
                                                            + config['tab'][t]['template']['row'][r]['widget']['@attributes']['target'],
                                                    flex : flex
                                                });
                                    } else if (config['tab'][t]['template']['row'][r]['widget']['@attributes']['type'] == 'period') {
                                        flex = parseInt(config['tab'][t]['template']['row'][r]['widget']['@attributes']['colspan']);
                                        var chart = Ext.create(
                                                'homepage.view.charts.PeriodChart',
                                                {
                                                    id : panelId
                                                            + '_'
                                                            + config['tab'][t]['template']['row'][r]['widget']['@attributes']['id'],
                                                    title : ' ',
                                                    flex : flex
                                                });
                                    }
                                    if (config['tab'][t]['template']['row'][r]['widget']['@attributes']['id'] == 'chart1') {
                                        chart.addCls('x-chart-title-selected');
                                    }
                                    row.add(chart);
                                } else {
                                    // Several charts
    
                                    for (var w = 0; w < config['tab'][t]['template']['row'][r]['widget'].length; w++) {
                                        // Create the chart
                                        if (config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['type'] == 'chart') {
                                            flex = parseInt(config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['colspan']);
                                            var chart = Ext.create(
                                                    'homepage.view.charts.Gauge', {
                                                        id : panelId
                                                                + '_'
                                                                + config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['id'],
                                                        title : ' ',
                                                        target : panelId
                                                                + '_'
                                                                + config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['target'],
                                                        flex : flex
                                                    });
                                        } else if (config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['type'] == 'period') {
                                            flex = parseInt(config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['colspan']);
                                            var chart = Ext
                                                    .create(
                                                            'homepage.view.charts.PeriodChart',
                                                            {
                                                                id : panelId
                                                                        + '_'
                                                                        + config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['id'],
                                                                title : ' ',
                                                                flex : flex
                                                            });
                                        }
                                        if (config['tab'][t]['template']['row'][r]['widget'][w]['@attributes']['id'] == 'chart1') {
                                            chart.addCls('x-chart-title-selected');
                                        }
                                        row.add(chart);
                                    }
                                }
                                panel.add(row);
                            }
                        }
                        tabPanel.add(panel);
                    }
    
                    if (selectedId != null) {
                        isDefault = true;
                        tabPanel.setActiveTab(selectedId);
                    } else {
                        tabPanel.setActiveTab(0);
                    }
                }
            }
    
            //Bug 34009, two calls to LOAD with a default tab
            if (isDefault) {
                me.loadConfigNoLayout();
            } else {
                me.loadConfig();
            }
        },
    
        resize : function(panel) {
            if (!panel.resizing) {
                if (panel.enableResize) {
                    // In some cases, we do authorize the component to resize itself :
                    // Homepage fullscreen, device fullscreen
                    panel.setHeight(panel.newHeight);
                    panel.enableResize = false;
                    panel.resizing = true;
                    panel.doLayout();
                } else {
                    // In most cases, we keep the initial dimensions, 
                    // to prevent the display bugs with the zoom, especially with iPad
    
                    if (panel.deviceWidth == null || panel.deviceHeight == null) {
                        // The first resize event is fired at the application launch
                        panel.deviceWidth = panel.getWidth();
                        panel.deviceHeight = panel.getHeight();
                        panel.newHeight = panel.getHeight();
                        panel.deviceOrientation = Ext.getOrientation();
                    } else {
                        // Get the previous dimensions
                        if (panel.deviceWidth != null && panel.newHeight != null) {
                            // Dirty : If height changed and width stayed, the device screen configuration changed (device fullscreen, debug)
                            if ((panel.deviceWidth == panel.getWidth())
                                    && (panel.newHeight != panel.getHeight())
                                    && !panel.enableResize) {
                                // Set the new dimensions as the new reference
                                if (panel.isFullscreen) {
                                    panel.deviceHeight = panel.getHeight();
                                    panel.newHeight = panel.getHeight();
                                } else {
                                    panel.deviceHeight = panel.getHeight() + 80;
                                    panel.newHeight = panel.getHeight();
                                }
                                panel.enableResize = true;
                            }
    
                            panel.setHeight(panel.newHeight);
                            panel.setWidth(panel.deviceWidth);
    
                            panel.resizing = true;
                            panel.doLayout();
                        }
                    }
                }
            } else {
                panel.resizing = false;
    
                // resize the gauges
                Ext.getCmp('mainPanel').fireEvent('resizeGauges');
            }
        },
    
        fullscreen : function(isFullscreen) {
            var panel = Ext.getCmp('mainPanel');
    
            if (panel.deviceWidth != null && panel.deviceHeight != null) {
                if (isFullscreen) {
                    // Back to initial size
                    panel.newHeight = panel.deviceHeight;
                    panel.isFullscreen = true;
                } else {
                    // Reduce the panel
                    panel.newHeight = panel.deviceHeight - 80;
                    panel.isFullscreen = false;
                }
    
                panel.enableResize = true;
            }
        },
    
        clone : function(srcInstance) {
            /*Si l'instance source n'est pas un objet ou qu'elle ne vaut rien c'est une feuille donc on la retourne*/
            if (typeof(srcInstance) != 'object' || srcInstance == null) {
                return srcInstance;
            }
            /*On appel le constructeur de l'instance source pour cr�e une nouvelle instance de la m�me classe*/
            var newInstance = srcInstance.constructor();
            /*On parcourt les propri�t�s de l'objet et on les recopies dans la nouvelle instance*/
            for (var i in srcInstance) {
                newInstance[i] = clone(srcInstance[i]);
            }
            /*On retourne la nouvelle instance*/
            return newInstance;
        },
        
        escapeXml : function(s) {
                        var XML_CHAR_MAP = {
                            '<': '&lt;',
                            '>': '&gt;',
                            '&': '&amp;',
                            '"': '&quot;',
                            "'": '&apos;'
                        };
                        
                        return s.replace(/[<>&"']/g, function (ch) {
                            return XML_CHAR_MAP[ch];
                        });
        }
                    
    });