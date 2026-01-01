/*
 * 28/07/2011 SPD1: Querybuilder V2 - Left panel component  
 * 06/10/2011 AVZ: Modifications for Homepage
 */

Ext.define('homepage.view.configuration.CounterWindow', {
	extend: 'Ext.window.Window',
	alias : 'widget.counterwindow',

	requires: [
		'Ext.layout.container.Accordion',
		'Ext.tip.QuickTipManager',
		'homepage.view.configuration.FilterPanel'
	],

	id: 'counterWindow',		        
	title: 'Select Raw / KPI',
	closable: false,
	width: 600,
	height: 350,
	modal: true,
	split: true,                          	    	     
	layout: {                        
    	type: 'hbox',
    	align: 'stretch'
	},
		
	filterPanel: null, 			// filter panel
	dataPanel: null,			// data panel
	rawList: null,				// rawList
	kpiList: null,				// kpiList
	messageHandlers: null,		// message handler (publish/subscribe)	
	searchDisabled: true, 		// true to disable the search (used when user click on reset button)
	
	// Which graph use the window (gauge/trend)
	graph: null,
	
	// Selected element
	chartId: null,
	counterId: null,
	counterName: null,
	counterType: null,
	counterProductId: null,
	counterProductName: null,
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Constants shortcut	
		me.cs = homepage.resources.locale;

		// Create filter panel
		me.filterPanel = Ext.create('homepage.view.configuration.FilterPanel');
		  		  
		// Create list of elements panel
		me.dataPanel = me.createDataPanel();                                                            	
		
		// Add items
		me.items = [
			me.filterPanel,
			me.dataPanel
		];								
        
		// Add buttons
		me.buttons = [
			{
        		text: 'OK',
        		action: 'validateCounter'
    		},
    		{
        		text: 'Cancel',
        		action: 'cancelCounter'
    		}
    	];
		
    	// call the superclass s constructor  
    	return this.callParent(arguments);		
	}
     
	/* Component initialization */
	,initComponent: function() {
		var me = this;
		 
		// message subscribe
    	me.messageHandlers = [];
    	
    	// refresh raw, kpi list (when user is doing a search)
    	me.messageHandlers.push(homepage.resources.message.subscribe('/leftpanel/search', me, me.refreshElements));
    	
    	// enable/disable search
    	me.messageHandlers.push(homepage.resources.message.subscribe('/leftpanel/enablesearch', me, me.enableSearch));
    	me.messageHandlers.push(homepage.resources.message.subscribe('/leftpanel/disablesearch', me, me.disableSearch));        
    	        
    	// call the superclass s constructor  
    	return this.callParent(arguments);
	}   
	  
	/* afterRender method */
	,afterRender: function() {				
		var me = this;
    	// call the superclass s constructor  
    	return this.callParent(arguments);
	}

	,resetSearch: function() {
		var me = this;
		me.filterPanel.resetSearchForm();
	}
	
	,showWindow: function(counterLabel) {
		var me = this;
		// Update the title
		Ext.getCmp('counterWindow').setTitle('Select Raw / KPI : ' + counterLabel);
		
		// Show the window
		me.show();
		
		// Hide the previous list
		me.rawList.addCls('qbInvisible');
		me.kpiList.addCls('qbInvisible');
	}
	
	/* Destroy
	 * This method is call by the unload event (when user leaves querybuilder)
	 * It destroy all component of the left panel to limit the memory leaks 
	 * */     
	,destroy: function() {
		var me = this;				
		
		// Delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
		me.deleteObj(me.messageHandlers);
									
		// Delete filter panel
		me.deleteObj(me.filterPanel);				
						
		// Delete lists
		me.deleteObj(me.rawList);				
		me.deleteObj(me.kpiList);
				
		// Delete data panel
		me.deleteObj(me.dataPanel);
			
    	// call the superclass s constructor  
    	return this.callParent(arguments);				
	}     

	// --------------------------------------------------------------------------------
	// Custom methods for this component
	// --------------------------------------------------------------------------------
	  
	/* Delete an object*/
	,deleteObj: function (obj) {		
		if (obj && obj.destroy) {obj.destroy();}		
		obj = null;
		delete obj;						
	}	  
	
	/* Refresh RAW and KPI list according the search options */
	,refreshElements: function(searchOptions) {		
		var me = this;				
		
		// If search is disabled: do nothing (search is disabled during the search form is reseted this avoid several requests for nothing)
		if (me.searchDisabled) {
			return true;
		}
		
		var loadParameters = {			
    		params: {filterOptions: Ext.encode(searchOptions)}
		};
		
		if (me.rawList.body) {
			var loader = me.rawList.getLoader();			
			loader.load(loadParameters);
			me.rawList.removeCls('qbInvisible');
		}

		if (me.kpiList.body) {
			var loader = me.kpiList.getLoader();
			loader.load(loadParameters);
			me.kpiList.removeCls('qbInvisible');
		}
		
		var filtered = searchOptions.text || me.filterPanel.isDirty?' <b>(filtered)</b>':'';
				
		// update lists title
		me.rawList.setTitle(me.cs.counterWindow.rawList + (Ext.Array.contains(searchOptions.types, 'RAW')?filtered:''));							 																							
		me.kpiList.setTitle(me.cs.counterWindow.kpiList + (Ext.Array.contains(searchOptions.types, 'KPI')?filtered:''));																								

	}
	
	/* Create list of elements panel */
	,createDataPanel: function() {

		var me = this;
		
		// Create RAW list panel		
		me.rawList = Ext.create('Ext.panel.Panel', {
			iconCls: 'icoBrick',
			id: 'rawList',
			dataType: 'RAW',					
			title: me.cs.counterWindow.rawList,
			loader: {
				loadMask: true,
    			url: 'proxy/raw_listhtml.php'
    		},						
			autoScroll: false				
		});

		// Create KPI list panel
		me.kpiList = Ext.create('Ext.panel.Panel', {
			iconCls: 'icoBrick',
			id: 'kpiList',
			dataType: 'KPI',			
			title: me.cs.counterWindow.kpiList,
			loader: {
				loadMask: true,
    			url: 'proxy/kpi_listhtml.php'
    		},												
			autoScroll: false				
		});

		// Init raw and kip panel as select source zones
		me.rawList.on('render', me.initSelectZone(me.rawList));		
		me.kpiList.on('render', me.initSelectZone(me.kpiList));
			
		return Ext.create('Ext.panel.Panel', {
			flex: 1,
			height: 100,
			id: 'counterLeftElementsContainer',			   
			layout:'accordion',				        		    		    		    		 
	 		layoutConfig: {         	
    			titleCollapse: true,         	 	
    			activeOnTop: true,
    			animate: false
			},		    				  
	    	items: [
	    	    me.kpiList,
	    		me.rawList  	
	    	]
		});		
	}

	/* enable search */
	,enableSearch: function() {
		this.searchDisabled = false;
	}

	/* disable search */
	,disableSearch: function() {
		this.searchDisabled = true;
	}

	/* Init select zone */
	,initSelectZone: function(list) {
		var me = this;
		
		return function() {
			list.body.on('click', function(e) {
				// Find the element under the mouse pointer
				var sourceEl = e.getTarget('tr', 10), d;
				
				// If an element with a valid id has been found ...	
				if (sourceEl && sourceEl.id) { 				
					// Update the selected element
					me.counterId = sourceEl.id;
					me.counterName = sourceEl.childNodes[0].innerHTML;
					me.counterType = list.dataType;
					me.counterProductId = sourceEl.getAttribute('data-product');
					me.counterProductName = sourceEl.childNodes[1].innerHTML;
				
					// Update the title
					Ext.getCmp('counterWindow').setTitle('Select Raw / KPI : ' + me.counterProductName + ' ' + me.counterName);
				}							
			});
		}
	}
	
});
