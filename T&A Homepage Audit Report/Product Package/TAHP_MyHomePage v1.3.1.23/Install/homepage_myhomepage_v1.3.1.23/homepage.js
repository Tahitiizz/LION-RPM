Ext.application({
	name: 'homepage',
   	appFolder: 'app',

   	requires: [
		'homepage.view.tab.MainPanel',
		'Ext.ux.chart.SmartLegend'
	],
   		
	controllers: [
		'tab.MainPanel',
		'charts.ChartsPanel',
		'charts.Gauge',
		'charts.Frame',
		'charts.Map',
		'charts.GridReport',
		'charts.AuditReport',
		'charts.AuditReportEvo',
		'charts.RiGraph',
		'charts.SummaryGraph',
		'charts.SummaryGraphEvo',
		'charts.AlarmsGraph',
		'charts.CellsSurveillance',
		'charts.GaugeDetails',
		'charts.GaugeView',
		'charts.GaugeViewIE',
		'charts.PeriodChart',
		'charts.ReportUpload',
		'configuration.ConfigPanel',
		'configuration.ConfigTab',
		'configuration.ConfigMapModeSelection',
		'configuration.ConfigMapAssociation',
		'configuration.ConfigChart',
		'configuration.CounterWindow',
		'configuration.NetworkWindow',
		'configuration.NetworkLevelWindow'
    ],
    	
    isFullscreen: false,			// isFullscreen (true if fullscreen is enable)
    
   	launch: function() {				
		var me = this;
		
		//BZ29876
		Ext.supports.Direct2DBug = false;
		
		// Initialize the user configuration folders
		Ext.Ajax.request({
			url: 'proxy/configuration.php',
			params: {
				task: 'INIT'
			},
			
			success: function (response) {
				if (response.responseText == 'failure') {
					// Display an error message
                    Ext.create('Ext.Component', {
                        layout: 'absolute',
                        renderTo: Ext.getBody(),
						html: 'The configuration file is not available'
                    });
				} else {	
					var result = Ext.decode(response.responseText);
					me.isAdmin = result.admin;
					me.gaugeType = result.gauge;
					me.timer= result.timer;
					me.referenceperiod= result.referenceperiod;
					me.penalisationmode= result.penalisationmode;
					Ext.get('taHeader').setStyle('visibility', 'visible');
					
					// Create the application
					Ext.create('Ext.container.Viewport', {
                        id: 'viewport',
    			        layout: 'border',  
    			        isAdmin: me.isAdmin,
    			        gaugeType: me.gaugeType,
    			        timer: me.timer,
    			        referenceperiod: me.referenceperiod,
    			        penalisationmode: me.penalisationmode,
    	        		items: [
							{ 
								// Top : empty and hidden via CSS -> we use the T&A header instead
								id: 'extjsHeader',
							    region: 'north',
							    height: 30,
							    padding: '4 5 0 5',
							    layout: {
									type: 'absolute'
								},
								items: [
									{
										bodyCls: 'qbJsHeader',
										html: '<img src="images/MyHomepageSmall.png" alt="My Homepage" />'        	    		
									},
									{
										id: 'configurationButton',
						          		xtype: 'button',
										tooltip: 'Enable the configuration',
						          		iconCls: 'icoConfiguration', 
						          		cls: 'barButton',
						          		style: {
						          			right: '146px'
										},
										enableToggle: true,
						                toggleHandler: function(me, checked) {											
											Ext.getCmp('mainPanel').fireEvent('showConfig', checked);
							        	}
						          	},
									{
										id: 'exportButton',
										xtype: 'splitbutton',
										tooltip: 'Export the charts',
										iconCls: 'icoExport',
										cls: 'barButton',	
										style: {
						          			right: '106px'
										},
										menu : {
							                items: [{
							                	text: 'File', 
							                	iconCls: 'icoFile',
							                	handler: function() { 
							                		Ext.getCmp('mainPanel').fireEvent('exp', 'file'); 
						                		}
							                }, {
							                	text: 'Mail', 
							                	iconCls: 'icoMail',
							                	handler: function() { 
							                		Ext.getCmp('mainPanel').fireEvent('exp', 'mail'); 
						                		}
							                }]
										}
							        },
									{
						          		id: 'refreshBox',
						          		xtype: 'button',
										tooltip: 'Launch/stop the auto refresh',
						          		iconCls: 'icoReload', 
						          		cls: 'barButton',
						          		style: {
						          			right: '80px'
										},
						          		enableToggle: true,
						                toggleHandler: function(me, checked) {
						                    Ext.getCmp('mainPanel').fireEvent('autoRefresh', checked);
							        	}
						          	},
						          	{
						          		id: 'reloadButton',
						          		xtype: 'button',
										tooltip: 'Refresh the charts',
						          		iconCls: 'icoRotate',
						          		cls: 'barButton',
						          		style: {
						          			right: '54px'
										},
						                handler: function() {
						                	// Refresh the gauges
						          			me.onRefresh();
							        	}
						          	},
						          	{
						          		id: 'fullscreenButton',
						          		xtype: 'button',
										tooltip: 'Show/hide the T&A menu',
						          		iconCls: 'icoZoomOut',						          		
						          		cls: 'barButton',
						          		style: {
						          			right: '28px'
										},
						                handler: function() {
						                	// Fullscreen
						          			me.onFullscreen();
							        	}
						          	},
						          	{
						          		id: 'infoButton',
						          		xtype: 'button',
										tooltip: 'About My Homepage',
						          		iconCls: 'icoInfo',						          		
						          		cls: 'barButton',
						          		style: {
						          			right: '2px'
										},
										handler: function() {
											// Get the version
											Ext.Ajax.request({
												url: 'proxy/configuration.php',
												params: {
													task: 'VERSION'
												},

												success: function(response) {
													var version = response.responseText;
													var label = 'My Homepage';
													if (version != '') {
														label += ' ' + version;
													} 
													
													// Open the help box
													Ext.MessageBox.alert('About My homepage', 
														'<b>' + label + '</b>'
														+ '<br/><br/><a href="images/help.pdf" target="_blank">Click to download User documentation</a>');
												}
											});	
											
										}
						          	}
								]
							},
	               			{
	               				id: 'mainPanel',
                        		xtype: 'mainpanel',
                        		region: 'center'
	                		}
    	        		],
    	        		listeners:{
							render: function(c) {		
								// Set fullscreen as initial screen configuration
								this.isFullscreen = true;
								me.onFullscreen();
							},
							afterrender: function(c) {		
								// Remove wait message
								Ext.get('waitDiv').removeCls('waitMessage');
								Ext.get('waitDiv').addCls('waitMessageHidden');
							}
						}
        			});
					
					// Hide the config and export buttons for the iPad
					if (Ext.get('isIpad').dom.value == 1) {
						Ext.getCmp('configurationButton').getEl().hide();
						Ext.getCmp('exportButton').getEl().hide();
					}
				}
			}
		});
	},
	
	// Fullscreen button click
	onFullscreen: function() {		
		this.isFullscreen = !this.isFullscreen;
		
		Ext.getCmp('mainPanel').fireEvent('fullscreen', this.isFullscreen);
		
		if (this.isFullscreen) {
			Ext.getCmp('extjsHeader').setHeight(30);
			Ext.get('taHeader').addCls('qbFullScreen');
			Ext.getCmp('extjsHeader').addCls('qbFullScreen');	
			Ext.getCmp('configurationButton').addCls('barButtonFullscreen');
			Ext.getCmp('exportButton').addCls('barButtonFullscreen');
			Ext.getCmp('refreshBox').addCls('barButtonFullscreen');
			Ext.getCmp('reloadButton').addCls('barButtonFullscreen');
			Ext.getCmp('fullscreenButton').addCls('barButtonFullscreen');
			Ext.getCmp('infoButton').addCls('barButtonFullscreen');
		} else {		
			Ext.getCmp('extjsHeader').setHeight(110);
			Ext.get('taHeader').removeCls('qbFullScreen');
			Ext.getCmp('extjsHeader').removeCls('qbFullScreen');
			Ext.getCmp('configurationButton').removeCls('barButtonFullscreen');
			Ext.getCmp('exportButton').removeCls('barButtonFullscreen');
			Ext.getCmp('refreshBox').removeCls('barButtonFullscreen');
			Ext.getCmp('reloadButton').removeCls('barButtonFullscreen');
			Ext.getCmp('fullscreenButton').removeCls('barButtonFullscreen');
			Ext.getCmp('infoButton').removeCls('barButtonFullscreen');
		}
	},
	
	// Refresh button click
	onRefresh: function() {			
		Ext.getCmp('mainPanel').fireEvent('refreshCharts');
	}
});
