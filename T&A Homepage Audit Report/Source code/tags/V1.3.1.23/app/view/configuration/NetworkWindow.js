/*
 * 28/07/2011 SPD1: Querybuilder V2 - Left panel component  
 * 06/10/2011 AVZ: Modifications for Homepage
 */

Ext.define('homepage.view.configuration.NetworkWindow', {
	extend: 'Ext.window.Window',
	alias : 'widget.networkwindow',

	requires: [
		'Ext.layout.container.Accordion',
		'Ext.tip.QuickTipManager',
		'homepage.view.configuration.SimpleFilterPanel'
	],

	id: 'networkWindow',		        
	title: 'Select network element',
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
	neList: null,				// neList
	searchDisabled: true, 		// true to disable the search (used when user click on reset button)
	
	// Which axis use the window (1st/3nd)
	axis: null,
	
	
	roaming: null,
	
	// Selected element
	chartId: null,
	neId: null,
	neLevelId: null,
	neProductId: null,
	neLabel: null,
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		if (typeof config.roaming === 'undefined') {
			me.roaming = false; 
		}else{
			me.roaming = config.roaming 
		}

		console.log(config);
		// Create filter panel
		me.filterPanel = Ext.create('homepage.view.configuration.SimpleFilterPanel', {
			win: me,
			axis: config.axis,
			roaming: false
		});
		  		  
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
        		action: 'validateNetwork'
    		},
    		{
        		text: 'Cancel',
        		action: 'cancelNetwork'
    		}
    	];
		
    	// call the superclass s constructor  
    	return this.callParent(arguments);		
	}
     	  
	/* afterRender method */
	,afterRender: function() {				
		var me = this;
    			
    	// call the superclass s constructor  
    	return this.callParent(arguments);
	}

	,resetSearch: function(axis) {
		var me = this;
		
		if (me.axis != axis) {			
			me.disableSearch();
			
			// Select 1st or 3rd axis
			var method = 'getProductsFamiliesNaLevels';
			if (axis == 3) method = 'getProductsFamiliesNa3Levels';
			
			me.filterPanel.treeStore.getProxy().url = 'proxy/querybuilder.php?method=' + method;
			me.filterPanel.treeStore.getRootNode().removeAll(); // Important! Bz 33793
			me.filterPanel.treeStore.load();
		} else {
			me.filterPanel.resetSearchForm();
			me.filterPanel.familyTree.collapseAll();
		}
	}
	
	,showWindow: function(networkLabel) {
		var me = this;

		// Update the title
		Ext.getCmp('networkWindow').setTitle('Select network element : ' + networkLabel);
		
		// Show the window
		me.show();
		
		// Hide the previous list
		me.neList.addCls('qbInvisible');
	}
	
	/* Destroy
	 * This method is call by the unload event (when user leaves querybuilder)
	 * It destroy all component of the left panel to limit the memory leaks 
	 * */     
	,destroy: function() {
		var me = this;				
									
		// Delete filter panel
		me.deleteObj(me.filterPanel);				
						
		// Delete lists
		me.deleteObj(me.neList);
				
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
	
	/* Refresh network element list according the search options */
	,refreshElements: function(searchOptions) {	
		var me = this;				
		
		// If search is disabled: do nothing (search is disabled during the search form is reseted this avoid several requests for nothing)
		if (me.searchDisabled) {
			return true;
		}
		
		var loadParameters = {			
			params: {filterOptions: Ext.encode(searchOptions)}
		};
		
		if (me.neList.body) {
			var loader = me.neList.getLoader();			
			loader.load(loadParameters);
			me.neList.removeCls('qbInvisible');
		}
		
		var filtered = searchOptions.text || me.filterPanel.isDirty ? ' <b>(filtered)</b>' : '';
				
		// update lists title
		me.neList.setTitle('Network elements' + filtered);							 																							
	}
	
	/* Create list of network elements panel */
	,createDataPanel: function() {

		var me = this;
		 
		// Create network elements list panel		
		me.neList = Ext.create('Ext.panel.Panel', {
			iconCls: 'icoBrick',
			id: 'neList',
			dataType: 'RAW',					
			title: 'Network elements',				
			loader: {
				loadMask: true,
    			url: 'proxy/ne_listhtml.php'
    		},						
			autoScroll: false				
		});

		// Init raw and kip panel as select source zones
		me.neList.on('render', me.initSelectZone(me.neList));	
			
		return Ext.create('Ext.panel.Panel', {
			flex: 1,
			height: 100,
			id: 'networkLeftElementsContainer',			   
			layout:'fit',	     				  
	    	items: [				    	    
	    		me.neList		    	
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
				var sourceEl = e.getTarget('tr', 10)/*, d*/;

				// If an element with a valid id has been found ...	
				if (sourceEl && sourceEl.id) { 				
					// Update the selected element
					me.neId = sourceEl.getAttribute('id');
					me.neLevelId = sourceEl.getAttribute('agregation');
					me.neProductId = sourceEl.getAttribute('data-product');
					me.neLabel = sourceEl.childNodes[0].innerHTML;
					me.neLevelLabel = sourceEl.childNodes[1].innerHTML;
					// Update the title
					Ext.getCmp('networkWindow').setTitle('Select network element : ' + me.neLevelLabel + ' - ' + me.neLabel);
				}							
			});
		}
	}
	
});