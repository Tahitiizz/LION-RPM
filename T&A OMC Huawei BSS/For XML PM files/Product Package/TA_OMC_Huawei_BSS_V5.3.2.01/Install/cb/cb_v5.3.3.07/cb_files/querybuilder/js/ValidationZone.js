/*
 * 28/07/2011 SPD1: Querybuilder V2 - Class to manage icons on the top right corner of the "Selected elements" grid.  
 */

Ext.define('Ext.ux.querybuilder.ValidationZone', {
	extend: 'Ext.container.Container',	

	requires: [
		'Ext.tip.Tip'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	app: null,					// pointer to the application
	messageHandlers: null,		// message handler (publish/subscribe)
	isElementEnable: false,		// RAW/KPI icon
	isNaEnable: false,			// NA icon
	isTaEnable: false,			// TA icon
	hasNaInCommon: null,		// has na in common ? 
	hasTaInCommon: null,		// has ta in common ?
	status: null,				// validation zone status: true -> Ok, false -> there are missing elements to query can't be executed
	
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
				
		me.rawKpiTip = Ext.create('Ext.tip.Tip', {
			title: me.cs.validationZone.defaultTitle,
			cls: 'qbValidationTip',
			//html: me.getTooltipMessage(),
			width: 300,
			height: 100
		});			
        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		 				  
		// message subscribe
        me.messageHandlers = [];        
    
    	// Update message
  		me.messageHandlers.push(Ext.ux.message.subscribe('/validationzone/update', me, me.update));                               
  		me.messageHandlers.push(Ext.ux.message.subscribe('/validationzone/highlight', me, me.setHighLight));
        me.messageHandlers.push(Ext.ux.message.subscribe('/aggpanel/updatecommonstatus', me, me.setCommonStatus));
        
        // call the superclass's constructor  
        return this.callParent(arguments);
	}   
	  
	/* afterRender method */
	,afterRender: function() {				
		var me = this;
		
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
    			
    	// delete tooltip
    	me.deleteObj(me.rawKpiTip);
    	
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
	
	/* Compute the tooltip message */
	,getTooltipMessage: function() {
		var me = this;
		
		if (!this.isElementEnable || !this.isNaEnable || !this.isTaEnable) {
			var ok = ' : OK<br>', ko = ' <b>: KO</b><br>';
			return me.cs.validationZone.defaultError + me.cs.validationZone.rawKpiMessage + (this.isElementEnable?ok:ko) + me.cs.validationZone.naMessage + (this.isNaEnable?ok:ko) + me.cs.validationZone.taMessage + (this.isTaEnable?ok:ko);
		}
		
		if (!this.hasNaInCommon) {
			return "<table><tr><td>"+this.cs.validationZone.noNaInCommon+"</td></tr></table>";
		}
		
		if (!this.hasTaInCommon) {
			return "<table><tr><td>"+this.cs.validationZone.noTaInCommon+"</td></tr></table>";
		}
	}
		
	/* Update validation zone
	 * The user must at least select one RAW/KPI, one TA and one NA. 
	 */ 
	,update: function() {		
		var me = this;
				
		// if this is a SQL query, hide yellow warning icons
		if (me.app.currentQuery.general.type == 'sql') {
			me.setStatus(true);
			return;
		}
		
		// If there is at least one selected element
		if (!me.app.currentQuery.select.data.lenght > 0) {
			// If there is no TA or NA in common 
			if (!me.hasNaInCommon || !me.hasTaInCommon) {
				// Display warning icons
				me.setStatus(false);
				me.setHighLight(false);
				return;
			}
		}
		
		// Get selected elements grid data
		dataGridItems = me.app.currentQuery.select.data;
		filterGridItems = me.app.currentQuery.filters.data;
		
		// Reset all icons
		me.isElementEnable = false;
		me.isNaEnable = false;
		me.isTaEnable = false;				

		// For each items in the data grid, check item type
		Ext.Array.forEach(dataGridItems, function(item) {			
			switch(item.type) {
				case 'RAW':													
				case 'KPI':
					me.isElementEnable = true;
					break;
				
				case 'na':
					me.isNaEnable = true;												
					break;
				
				case 'ta':
					me.isTaEnable = true;
					break;
			}
		});					
			
		// For each items in the filter grid, check item type
		Ext.Array.forEach(filterGridItems, function(item) {			
			switch(item.type) {				
				case 'na':
				case 'na_axe3':
					me.isNaEnable = true;												
					break;
				
				case 'ta':
					me.isTaEnable = true;
					break;
			}
		});				
				
		// If all elements are present
		if (me.isElementEnable && me.isNaEnable && me.isTaEnable) {					
			// hide yellow triangle in the toolbar
			me.setStatus(true);					
		} else {
			// display yellow triangle in the toolbar
			me.setStatus(false);
			me.setHighLight(false);			
		}
		
	}
			
	/* Hightlight require panels (raw/kpi or agg. panel */
	,setHighLight: function(enable, e) {
		
		// If this is a SQL query, do nothing.	
		if (this.app.currentQuery.general.type == "sql") {
			return;
		}
		
		// RAW/KPI		
		if (!this.isElementEnable) {
			this.setElementHighLight(Ext.getCmp('qbLeftElementsContainer'), enable);			
		}
		
		// NA & TA
		if (!this.isNaEnable || !this.isTaEnable) {
			this.setElementHighLight(Ext.getCmp('qbAggPanel'), enable);			
		}
		
		if (!this.hasNaInCommon || !this.hasTaInCommon || !this.isNaEnable || !this.isTaEnable || !this.isElementEnable) {
			var vp = Ext.getCmp('qbViewPort');			
			if (enable) {				
				this.rawKpiTip.update(this.getTooltipMessage());
				// Display tooltip at screen center
				this.rawKpiTip.showAt([vp.width/2 - this.rawKpiTip.width/2, vp.height/2 - this.rawKpiTip.height/2]);
			} else {
				this.rawKpiTip.hide();
			}
		}		
	}
	
	/* add or remove highlight class (red border) to an element */
	,setElementHighLight: function(el, enable) {
		if (enable) {
			el.addCls("qbHighlight-class");
		} else {
			el.removeCls("qbHighlight-class");
		}		
	}
	
	/* Set na in common propertie
	 * @param hasNaCommon: boolean has na in common ?
	 * @param hasTaCommon: boolean has ta in common ?
	 */ 
	,setCommonStatus: function(hasNaCommon, hasTaCommon) {
		this.hasNaInCommon = hasNaCommon;
		this.hasTaInCommon = hasTaCommon;
		
		// Update validation zone status
		this.update();
	}
		
	/* Set validation status (warn other components to display yellow warning icons ...) */
	,setStatus: function(status) {
		if (this.status != status) {
			this.status = status;
			Ext.ux.message.publish('/querytab/validationchange', [status]);
		}
	}	
});