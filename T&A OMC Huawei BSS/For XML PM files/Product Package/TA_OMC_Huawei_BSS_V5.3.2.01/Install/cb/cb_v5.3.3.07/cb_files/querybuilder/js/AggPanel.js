/*
 * 28/07/2011 SPD1: Querybuilder V2 - Aggregation panel -> "Network and time"  
 */

Ext.define('Ext.ux.querybuilder.AggPanel', {
	extend: 'Ext.panel.Panel',	
		         
	requires: [
		'Ext.ux.querybuilder.locale'		
	],
			         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbAggPanel',
		frame: true,
    	bodyPadding: '5 0 0 0',    	
    	bodyCls: 'qbAggPanelBody',
    	collapsible: true,
		animCollapse: false,
    	titleCollapse: true,
    	split: true,
    	floatable: false,	    	
    	border: false,
    	bodyStyle: {
    		background: '#fff'
    	},                	   		 		
		autoScroll: true,
		frame: true,      			      	       	    	                             
	  	margins: '0 0 0 0',
	  	height: 90,	  	
	  	//resizable: {handles: 's'},
	  	//anchor:'100%',
	  	region: 'north',
	    layout: {
	    	type: 'vbox',
	    	align: 'stretch'	
	    },
		title: Ext.ux.querybuilder.locale.netTimeAgg.title    
	},	
	
	naComp: null,				// NA component
	taComp: null,				// TA component
	contexMenuAggItem: null, 	// Agg. item clicked
	taInCommon: null,			// TA in common true/false
	naInCommon: null,			// NA in common true/false
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		
		// Apply the custom config
		Ext.apply(config, me.config);
					 					 
		// Create components
		me.naComp = me.createNaComp();
		me.taComp = me.createTaComp();		  		                                                             	
		
        // Add items
		me.items = [
			me.naComp,
			me.taComp
		];								
        
        // Context menu
		me.contextMenu = new Ext.menu.Menu({		  
		  items: [
			  {	// Add to 'selected elements' item
			  	id: 'dbAggMenuAddToSelect',
			    text: me.cs.leftPanel.addToSelected,
			    iconCls: 'icoGrid',			    
			    handler: function() {
			    	// Add element to the selected elements panel
			    	me.contexMenuAggItem.addToGrid();
			    },
			    scope: this		    		   
			  },
			  {	// Add to 'filters' item
			  	id: 'dbAggMenuAddToFilter',
			    text: me.cs.leftPanel.addToFilters,
			    iconCls: 'icoFilter',
			    handler: function() {
			    	// Add element to filter panel
			    	me.contexMenuAggItem.addToFilter();
			    },
			    scope: this		    		   
			  }
		  ]		  
		});

        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		   
		// message subscribe
        me.messageHandlers = [];        
    
  		me.messageHandlers.push(Ext.ux.message.subscribe('/aggpanel/unselectall', me, me.unselectAll));                               
  		me.messageHandlers.push(Ext.ux.message.subscribe('/aggpanel/opencontextmenu', me, me.openContextMenu));
        
        // call the superclass's constructor  
        return this.callParent(arguments);
	}   
	  
	/* afterRender method */
	,afterRender: function() {				
		var me = this;
	
//		Ext.Function.defer(function() {
			me.setDefaultIcons();
			me.setDefaultMessage();	
//		}, 2000, me);	
					
        // call the superclass's constructor  
        return this.callParent(arguments);
	}
	
    /* Destroy
     * This method is call by the unload event (when user leaves querybuilder)
     * It destroy all component of the right panel to limit the memory leaks 
     * */     
	,destroy: function() {
		var me = this;				
		
		// delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.messageHandlers);
    	
    	// delete context menu
    	me.deleteObj(me.contextMenu);
    	
    	// delete TA component
    	me.deleteObj(me.taComp);
    	
    	// delete NA component
    	me.deleteObj(me.naComp);    	
    			
        // call the superclass's constructor  
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
	
	/* Create NA component */
	,createNaComp: function() {
		return Ext.create('Ext.container.Container', {
			cls: 'qbAggContainer',
			margin: '0 0 4 0'
		});
	}
	
	/* Create TA component */
	,createTaComp: function() {
		return Ext.create('Ext.container.Container', {
			cls: 'qbAggContainer'
		});
	}
	
	/* Add items to the NA component */
	,addNa: function(items) {
		this.naComp.removeAll();			
		this.setNaIcon();
				
		this.naComp.add(items);
	}	
	
	/* Add items to the TA component */
	,addTa: function(items) {
		this.taComp.removeAll();			
		this.setTaIcon();
		
		this.taComp.add(items);		
	}
		
	/* clear Agg components */
	,clearAgg: function() {
		this.naComp.removeAll();		
		this.taComp.removeAll();
		this.naComp.getEl().removeCls('qbAggNoAgg');
		this.taComp.getEl().removeCls('qbAggNoAgg');							
	}	
		
	,setDefaultMessage: function() {		
		this.setDefaultNaMessage();		
		this.setDefaultTaMessage();
	}
	
	,setDefaultNaMessage: function() {
		// Set NA label
		box = {xtype: 'box', renderTpl: this.cs.netTimeAgg.noNa, autoEl: {tag: 'span', cls: 'qbAggNoAgg'}};		
		this.naComp.add(box);
	}
	
	,setDefaultTaMessage: function() {
		box = {xtype: 'box', renderTpl: this.cs.netTimeAgg.noTa, autoEl: {tag: 'span', cls: 'qbAggNoAgg'}};
		this.taComp.add(box);
	}
			
	/* Set default network and times Icons */
	,setDefaultIcons: function() {		
		var me = this, box;		
		
		// Set NA label
		me.setNaIcon();
						
		// Set TA label
		me.setTaIcon();
	}
	
	/* Set icon for NA */	
	,setNaIcon: function() {
		var me = this;
		
		box = Ext.create('Ext.Component', {renderTpl: '&nbsp;&nbsp;&nbsp;&nbsp;', autoEl: {tag: 'span', cls: 'qbAggLabel icoNetwork'}});;
				
		box.on('afterrender', function() {						
			Ext.tip.QuickTipManager.register({				// Register a tooltip
			    target: box.id,
			    title: me.cs.netTimeAgg.netTipTitle,
			    text: me.cs.netTimeAgg.netTipMessage
			});
		});				
		
		this.naComp.add(box);		
	}
	
	/* Set icon for TA */
	,setTaIcon: function() {
		var me = this;
		box = Ext.create('Ext.Component', {renderTpl: '&nbsp;&nbsp;&nbsp;&nbsp;', autoEl: {tag: 'span', cls: 'qbAggLabel icoTime'}});
		
		box.on('afterrender', function() {						
			Ext.tip.QuickTipManager.register({				// Register a tooltip
			    target: box.id,
			    title: me.cs.netTimeAgg.timeTipTitle,
			    text: me.cs.netTimeAgg.timeTipMessage
			});
		});
		
		this.taComp.add(box);
	}		
	
	/* unselect all agg */
	,unselectAll: function() {
		// unselect na
		Ext.Array.forEach(this.naComp.items.items, function(item) {
			if (item.unselect) {
				item.unselect();
			}
		});
		
		//unselect ta
		Ext.Array.forEach(this.taComp.items.items, function(item) {
			if (item.unselect) {
				item.unselect();
			}
		});
	}
	
	/* Open the context menu
	 * @param aggItem object the item clicked
	 * @param coord array the mouse pointer coordinate
	 */
	,openContextMenu: function(aggItem, coord) {
		// Save item clicked
		this.contexMenuAggItem = aggItem;
			
		// Open context menu						
		this.contextMenu.showAt(coord);
	}
	
	/* Warn the user when no network agg. is available */
	,displayNoNetworkAgg: function() {		
		
		// Set default message
		this.setNaIcon();
		this.setDefaultNaMessage();
		this.naComp.getEl().addCls('qbAggNoAgg');
		
		// Hightlight
		this.getEl().stopAnimation().highlight("ff0000");		
	}
	
	/* Display a message when no TA is available */
	,displayNoTimeAgg: function() {
		// Set default message
		this.setTaIcon();
		this.setDefaultTaMessage();	
		this.taComp.getEl().addCls('qbAggNoAgg');	
	}	
	
	/* Set NA & TA in common status
	 * @param hasNaInCommon: boolean has na in common ?
	 * @param hasTaInCommon: boolean has ta in common ?
	 */ 
	,setCommonStatus: function(hasNaInCommon, hasTaInCommon) {		
		if (this.naInCommon != hasNaInCommon || this.taInCommon != hasTaInCommon) {
			this.naInCommon = hasNaInCommon;
			this.taInCommon = hasTaInCommon;
			Ext.ux.message.publish("/aggpanel/updatecommonstatus", [hasNaInCommon, hasTaInCommon]);
		}		
	}
});