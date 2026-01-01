/*
 * 28/07/2011 SPD1: Querybuilder V2 - Graph panel (used in preview tab)  
 */

Ext.define('Ext.ux.querybuilder.GraphPanel', {
	extend: 'Ext.panel.Panel',	
	
	requires: [
		'Ext.ux.querybuilder.GraphParametersPanel',
		'Ext.ux.querybuilder.GraphParametersWindow'		
	],
			         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbGraphPanel',
	    border: false,	   	                     	    	    
	    layout: {
    		type: 'vbox',
	    	align: 'stretch'	
	    },		                   
		autoScroll: true,
		isFresh: false,
		listeners: {
			"activate": function() {		// Refresh this panel when it is activate (execute query and display result)				
				if (!this.app.isDestroy && !this.isFresh) {				
					this.isFresh = true;
					this.refresh();
				}
			}
		}
	},	
	
	app: null,								// pointer to the application
				
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
        
        // Create graph parameter panel
        me.paramPanel = Ext.create('Ext.ux.querybuilder.GraphParametersPanel', {app: config.app});
        
        // Create image panel
        me.imagePanel = me.createImagePanel();	
                
		me.items = [   			
			me.paramPanel,
			me.imagePanel
		];
  		        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		   
		// message subscribe
        me.messageHandlers = [];        
    
  		me.messageHandlers.push(Ext.ux.message.subscribe('/graphpanel/refreshGraph', me, me.refreshImagePanel));                               
  		me.messageHandlers.push(Ext.ux.message.subscribe('/graphpanel/cancelrequest', me, me.abort));
        
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
    	
    	// delete image panel
    	me.deleteObj(me.imagePanel);
    	
    	// delete param panel
    	me.deleteObj(me.paramPanel);    	
    			
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
	
	/* Refresh panel */
	,refresh: function() {
		// Refresh param panel		
		this.paramPanel.refresh();		
	}
	
	/* Abort loading */
	,abort: function() {							
		var loader = this.imagePanel.getLoader();
		if (loader.active !== undefined) { 
			loader.abort();
		
			// Cancel SQL request
			Ext.ux.message.publish('/app/cancelsqlrequest', [loader.qbReqId]);
		}
	}
		
	/* Refresh image panel
	 * Parameter:
	 *  - graphParameters: json object, describe graph parameters 
	 */		
	,refreshImagePanel: function(graphParameters) {
		
		var loader = this.imagePanel.getLoader();
		loader.qbReqId = new Date().valueOf();
				
		// Refresh graph image		
		loader.load({
			params: {
				query: Ext.encode(this.app.currentQuery),	// Send the query in POST parameter
				graphParameters: graphParameters, 			// Send the graph parameters
				qbReqId: loader.qbReqId						// Id used to cancel SQL request if cancel button is clicked
			}			
		});
	}
	
	/* Create image panel */
	,createImagePanel: function() {
		var me = this;
		
		var pan = Ext.create('Ext.panel.Panel', {
			title: me.cs.graphPanel.graphDisplay,
		    flex: 1,		    
		    border: false,		    
		    autoScroll: true,		    		    
		    bodyCls: 'qbImagePanelBody',		            	
			tbar: [
			{
				xtype: 'button',															// Auto reload button 
			  	text: me.cs.graphPanel.autoReloadButton,
			  	iconCls: 'icoSmallReload',			  	
				enableToggle: true,
				pressed: true,
                handler: function(button, e) {												// Set auto reload option (on/off)
                	Ext.ux.message.publish('/graphparameterspanel/setautoreload', [button.pressed]);                						
    			}
			},
			{
				xtype: 'button',															// Reload button 
			  	text: me.cs.graphPanel.reload,
			  	iconCls: 'icoReload',			  	
			  	handler: function() {				  						
			  		Ext.ux.message.publish('/graphparameterspanel/refreshgraph');
				}
			},
			{
				xtype: 'button',															// Set graph name button 
			  	text: me.cs.graphPanel.setGraphName,
			  	iconCls: 'icoChartEdit',			  	
			  	handler: function() {						
			  		me.setGraphName();
				}
			},
			{
				xtype: 'button',															// Add to cart button 
			  	text: me.cs.graphPanel.basketButton,
			  	iconCls: 'icoBasket',			  	
			  	handler: function() {			  		
			  		// Get graph url
			  		var url = me.getGraphImageUrl();
			  		
			  		// If no graph ...exit
			  		if (!url) {
			  			return;
			  		}
			  		var pieces = url.split('/');
			  		var fileName = pieces[pieces.length-1];
					var name = me.app.currentQuery.general.name?me.app.currentQuery.general.name:'';
								  		
			  		// Add the graph to the cart						
  					caddy_update('../../', me.app.currentQuery.system.userId,'Query builder','graph', name + me.cs.graphPanel.from, fileName, me.cs.graphPanel.caddyTitle,'');  					
  					
  					// Display a notification
  					Ext.ux.message.publish("/app/notification", [{title: me.cs.graphPanel.graphNotifTitle, message: me.cs.graphPanel.graphAdded, iconCls: "icoNotifOk"}]);
				}
			}, {
				xtype: 'button',
				text: me.cs.graphPanel.fullscreen,
				iconCls: 'icoPicture',
				handler: Ext.bind(me.displayFullscreenGraph, me)								
			}]    		
		});
		
		pan.loader = new Ext.ComponentLoader({
			loadMask: {msg: me.cs.app.pleaseWait + '<br><button class="qbButton" onClick=\"Ext.ux.message.publish(\'/graphpanel/cancelrequest\')\">Cancel</button>'},
       		url: '../php/querybuilder.php?method=displayGraph',			// Get grid result
       		autoLoad: false,			
			scripts: true				
        });
        
        return pan;
	}  		
	
	/* Reset panel (called on 'new' query)*/
	,reset: function() {
		this.paramPanel.reset();
	}
	
	/* Open a prompt window to set the graph name */
	,setGraphName: function() {
		var me = this;
									
		// Create the window (if not already created)
		if (!me.graphParamWindow) {
			me.graphParamWindow = Ext.create('Ext.ux.querybuilder.GraphParametersWindow', {
				app: me.app,			
				height: 220,
	    		width: 350
			});				
		} 
				
		// Display window
		me.graphParamWindow.displayWindow();
	}
	
	/* Get the graph image url */
	,getGraphImageUrl: function() {
		// Get graph image
		var img = this.imagePanel.body.down('img');
		
		// If no graph displayed ...exit
		if (!img) {
			return;
		}
		
		// return graph image URL
		return img.dom.src;		
	}
	
	/* Display the graph in full screen */
	,displayFullscreenGraph: function() {

		// Get graph url
		var url = this.getGraphImageUrl();
		
		// If no graph displayed ...nohting to do
		if (!url) {
			return;
		}
		
		var div = Ext.get('qbFullScreenGraph');
		
		// Create or update the fullscreen div			
		if (!div) {
			div = Ext.core.DomHelper.append(document.body, {
				tag: 'div', 
				cls: 'fullScreenGraph', 
				id: 'qbFullScreenGraph',
				onClick: 'Ext.get(\'qbFullScreenGraph\').hide(true)'
			});						
		} else {
			Ext.get('qbFullScreenGraphImage').dom.src = url;
		}
		
		var title = this.app.currentQuery.graphParameters.name || this.app.currentQuery.general.name || '';
		document.getElementById('qbFullScreenGraph').innerHTML = '<h2>'+title+'</h2><br><br><img src=\''+url+'\' id=\'qbFullScreenGraphImage\'><br><button class=\'qbButton\'>&nbsp;&nbsp;Back&nbsp;&nbsp;</button>';
			
		Ext.get('qbFullScreenGraph').show(true);
					
	}
	
	/* Cancel request (cancel graph refresh */
	,cancelRequest: function() {
		
	}
});