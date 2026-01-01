/*
 * 28/07/2011 SPD1: Querybuilder V2 - Class to create an item in the aggregation panel  
 */


Ext.define('Ext.ux.querybuilder.AggregationItem', {
	extend: 'Ext.Component',

	requires: [
		'Ext.dd.DragSource'
	],
		 
	// render XTemplate
    renderTpl: '{label}',
	
	// init parameters
	code: null,												// agg code
	label: null,											// agg label
	cls: null,												// css class to apply on this element
	pressed: false,
	type: null,												// agg. type (na, na_axe3, ta)	
	    
	/* Constructor */	
	constructor: function() {		
		// Set the main HTML tag for this component
		this.autoEl = {
			tag: 'span',
			cls: this.cls									// set the css class
		}		
		this.callParent(arguments);
	}
		
	/* Call on component render */
	,onRender: function() {
		var me = this;		
		Ext.applyIf(this.renderData, {label: this.label});	// set the label							 		      
		
		this.callParent(arguments);
   }

	/* Call after component render */
	,afterRender: function() {
		var me = this;
		
		// Disabled selection
		me.el.unselectable();
		
		// Add click event								
 		me.mon(me.el, 'click', me.onClick, me);
 		me.mon(me.el, 'contextmenu', me.onRightClick, me);
 
  		// Set item draggable
		me.dragSource = new Ext.dd.DragSource(this.id, {ddGroup: 'elementDDGroup'});
		me.dragSource.dragData = { 
            element: {
            	id: me.code,
            	type: me.type,            	
            	productName: 'N/A',
            	label: me.label,
            	name: me.code
            }		                
		}		 			 		       
		this.callParent(arguments);
   }
      
   /* Unselect item */
   ,unselect: function() {
   		this.removeCls("pressed");
   		this.pressed = false;	   		
   }
   
   /* Select item */
   ,select: function() {
   		this.addCls("pressed");
   		this.pressed = true;	   		
   }
      
   /* On click event */
   ,onClick: function() {
   		var me = this;
   		
   		// toggle css class
	   	if (!me.pressed) {
	 		me.addCls("pressed");
			this.pressed = true;
	 	}
	 	
	 	// add agg. to grid
	 	me.addToGrid();	 	 		
 	}
 	
 	/* On right click event */
 	,onRightClick: function(e) { 		 		
 		var me = this;
 		
 		// get mouse pointer position							
		var coord = e.getXY();
						
		// add defer to fix IE						
		Ext.Function.defer(function(coord) {
			// Open the context menu						
			Ext.ux.message.publish('/aggpanel/opencontextmenu', [me, coord]);
		}, 10, this, [coord]);
												
 	}
 	
 	/* Remove this element from filter panel */
 	,removeFromFilter: function() {
 		// remove aggregation from filter
		Ext.ux.message.publish('/querytab/grids/remove', [this.code]);
 	}

	/* Create JSON object from this Agg. item */
	,getAggElementObject: function() {		
		return {
 			element: {
 				id: 			this.code,
 				name:			this.code,
 				label:			this.label,
				type: 			this.type,
				productName:	'N/A'
			}
		};	
	} 	
 	/* Add this element to selected elements grid */
 	,addToGrid: function() { 		 		
 		// create agg element to add
 		var agg = this.getAggElementObject();
				
		// add aggregation to the selected elements panel
		Ext.ux.message.publish('/querytab/datagrid/add', [agg]); 		
 	}

 	/* Add this element to filter grid */
 	,addToFilter: function() { 		
		// create agg element to add
 		var agg = this.getAggElementObject();
 				
		// add aggregation to filter panel
		Ext.ux.message.publish('/querytab/filtergrid/add', [agg]); 		
 	} 	
 	
});