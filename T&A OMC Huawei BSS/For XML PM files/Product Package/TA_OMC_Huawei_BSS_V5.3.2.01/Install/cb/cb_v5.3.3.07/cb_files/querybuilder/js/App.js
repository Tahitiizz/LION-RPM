/*
 * 28/07/2011 SPD1: Querybuilder V2 - Main class  
 */


Ext.define('Ext.ux.querybuilder.App', {
	
	// Extend observable to manage custom events
    mixins: {
        observable: 'Ext.util.Observable'
    },

	// Classes needed by Querybuilder
    requires: [
        'Ext.container.Viewport',               		
		'Ext.layout.container.Border',				
		'Ext.tab.Panel',
		'Ext.ux.querybuilder.locale',
		'Ext.ux.querybuilder.LeftPanel',
		'Ext.ux.querybuilder.RightPanel',
		'Ext.ux.querybuilder.QueryTab',		
		'Ext.ux.querybuilder.PreviewTab'			
    ],
	
	// Properties
    isReady: 		false,    
	leftPanel: 		null,			// the leftPanel
	rightPanel:		null,			// the rightPanel
	queryTab:		null,			// the queryTab
	previewTab:		null,			// the previewTab
	currentQuery:	null,			// current query
	messageHandlers:null,			// message handler (publish/subscribe)
	isFullscreen: 	false,			// isFullscreen (true if fullscreen is enable)
	
	/* Constructor */
    constructor: function (config) {    	
        var me = this;
        
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		                
        // Define custom events
        me.addEvents(
            'ready',				// App is ready
            'beforeunload'			// Destroy all components before leaving
        );

		// Call observable constructor to init custom events
        me.mixins.observable.constructor.call(this, config);

		// Init App when ExtJS is ready
        if (Ext.isReady) {
            Ext.Function.defer(me.init, 10, me);
        } else {
            Ext.onReady(me.init, me);
        }
    }

	/* Init: Init the application */
    ,init: function() {
        var me = this;        
        
// used to debug in the browser console (create an 'app' global variable, use it in firebug or chrome developer tool)        
app = me;

		// for IE, declare a fake console to allow using console to debug with other browsers
		if (typeof(console)=='undefined') {
			console = {log: function() {}};			
		}
		
		// Disable right click
		Ext.fly(document.body).on('contextmenu', function (e) {e.preventDefault();});
		
        // Message subscribe
        me.messageHandlers = [];
        
        // Trace error messages from server in the console browser
        me.messageHandlers.push(Ext.ux.message.subscribe('/app/error', me, me.onError));
        
        // Notifications
        me.messageHandlers.push(Ext.ux.message.subscribe('/app/notification', me, me.notification));                
        	
        // Cancel SQL request
        me.messageHandlers.push(Ext.ux.message.subscribe('/app/cancelsqlrequest', me, me.cancelSqlRequest));        	
        		
        // ESC shortcut key : switch between Query and Preview tab
		me.messageHandlers.push(Ext.ux.message.subscribe('/app/tabswitch', me, me.tabSwitch));
		        
		// Init tooltip      	
        Ext.tip.QuickTipManager.init();
            
		// Tooltip config
		Ext.apply(Ext.tip.QuickTipManager.getQuickTip(), {
		    showDelay: 500      				// Show 500ms after entering target
		});                    
        
        // Create new blank query
        me.currentQuery = me.getBlankQuery();
        
		// Create the left panel
    	me.leftPanel = Ext.create('Ext.ux.querybuilder.LeftPanel', {
    		app: me								// Give a link to the App
    	});
    	    	
		// Create the right panel
    	me.rightPanel = Ext.create('Ext.ux.querybuilder.RightPanel', {
    		app: me								// Give a link to the App
    	});
    	    	    	
		// Create the query tab
    	me.queryTab = Ext.create('Ext.ux.querybuilder.QueryTab', {
    		app: me								// Give a link to the App
    	});
    	
		// Create the preview tab
    	me.previewTab = Ext.create('Ext.ux.querybuilder.PreviewTab', {
    		app: me								// Give a link to the App
    	});    	
    	    	  
    	// Create viewport  	
        me.viewport = Ext.create('Ext.container.Viewport', {                                             
	        layout: 'border',
	        id: 'qbViewPort',                        
	        items: [ 	        	
	            { 
					// Top : empty and hidden via CSS -> we use the T&A header instead
		    		id: 'extjsHeader',
	    	        region: 'north',
	        	    height: 110,
	        	    layout: 'fit',	        	    
	        	    items: [
	        	    	{
	        	    		bodyCls: 'qbJsHeader',
	        	    		html: Ext.query('.chemin')[0].innerHTML	        	    		
	        	    	},
		        	    {
							cls: 'qbFullScreenButton',
		        	   		xtype: 'box',	        	    	
							autoEl: {tag: 'div'},													
							listeners:{
								render: function(c) {								
									c.getEl().on('click', Ext.bind(me.onFullscreen, me));	// fullscreen icon click handler
								}
							}						
		        	    }
					]
	    		},	    					
				me.leftPanel,						// Left panel
				me.rightPanel,						// Right panel 
				{
					layout: 'card',			
					region: 'center',         		// a center region is ALWAYS required for border layout
					autoScroll: false,		               
			        deferredRender: false,			        
			        id: 'mainTabPanel',			        			        
					border: true,   
			        items: [
						me.queryTab,
						me.previewTab			
					]
				}				 
	        ]
	    });
    
		// Listen for onUnload (when the user leave querybuilder)
        Ext.EventManager.on(window, 'beforeunload', me.onUnload, me);

		// Ready !
        me.isReady = true;
        me.fireEvent('ready', me);
        
        me.currentQuery.system.hasChanged = false;
    }
    
    /* On ready */
    ,onReady : function(fn, scope) {
        if (this.isReady) {
            fn.call(scope, this);
        } else {
            this.on({
                ready: fn,
                scope: scope,
                single: true
            });
        }
    }

	/* On unload, all components should be destroyed */
    ,onUnload : function(e) {
    	var me = this;
    	
    	// destroy this application
    	me.destroy();
    	
    	// remove all listeners
    	me.clearListeners();
    }
    
    /* Destroy application */
    ,destroy: function() {
    	var me = this;
		
		// Hide everything before destroying each component one by one
		Ext.get('qbViewPort-embedded-center').addCls('notVisible');
		
		// Falg the app that the destroy has been launchedn, no action (reloading, refeshing list ...) should be done anymore
		me.isDestroy = true;
		
    	// Delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.messageHandlers);
    	
    	// Destroy tip manager    	
    	//Ext.tip.QuickTipManager.destroy();
    	
    	// Destroy left panel
    	me.deleteObj(me.leftPanel);    	
    	
    	// Destroy right panel
    	me.deleteObj(me.rightPanel);
    	    	
    	// Delete current query object
    	me.deleteObj(me.currentQuery);
    	
    	// Delete query tab
    	me.deleteObj(me.queryTab);
    	
    	// Delete preview tab
    	me.deleteObj(me.previewTab);    	    	
    	
    	// Delete viewport
    	me.deleteObj(me.viewport);
    }
    
    /* Delete an object*/
	,deleteObj: function (obj) {
		if (obj && obj.destroy) {obj.destroy();}		
		obj = null;
		delete obj;						
	}
	
    /* Create new blank JSON query */
    ,getBlankQuery: function() {
    	return {    		
    		"system": {						// System data are not saved into database
    			"hasChanged": false,		// flag to know if the query need to be saved before leaving
    			"hasRight": true			// flag to know if user can overwrite this query
    		},
    		"general": {					// General
    			"id": null,					// id in the database
    			"name": null,				// query name
    			"type": "wizard"			// type: wizard or sql
    		},					
    		"select": {
    			"distinct": false,			// apply a distinct clause ?
    			"disableFunctions": false,	// disable functions toggle button
    			"data": []					// select elements
    		},
    		"filters": {    					
    			"data": []
    		},
    		"graphParameters": {
    			"name": "",
    			"gridParameters": []
    		},
    		"exportOptions": {				// export options
    			"el": "label",				// raw & kpi (label or name)
    			"ne": "label",				// network elements (code, label or both)
    			"parentNe": false			// include parent network elements ?
    		}
    	}
    }
    
    /** Get default filters
     * @return object default filters for filter grid 
    */
    ,getDefaultFilters: function() {
    	return [{
			"id": "maxfilter",
			"name": "maxfilter",
	        "label": "Max. nb. results",
	        "type": "sys",					// system type -> this row can't be deleted
	        "productName": "N/A",
	        "connector": "AND",
	        "enable": true,
	        "familyId": "",
	        "productId": "",
	        "operator": "",
	        "value": "1000"
		}];
    }
    /* onError: an error has been received from the server */
    ,onError: function(error) {
    	var me = this;
    	
    	// Display a notification for the user
    	if (error.status) {    		
    		var message = error.status + " : " + error.statusText + "<br>" + me.cs.app.seeConsole;    		
    		Ext.ux.message.publish("/app/notification", [{title: me.cs.app.serverError, message: message, iconCls: "icoNotifError"}]);
    	}
    	// Trace error in the browser console
    	console.log(me.cs.app.serverError + ": ", error);
    }
    
    /** Simple message notification
     * param: json object
     * 	{
     * 	 	title: 'my title',
     * 		message: 'my message to display',
     * 		iconCls: 'error',							// if you want an icon
     * 		delay: 5000,								// if you want to set a delay (in second)
     * 		closeButton: true							// if you want to display a close button
     * }
     */    
    ,notification: function(param){
		var me = this;
		var icon = param.iconCls?" icon "+param.iconCls:"";
		var delay = param.delay || param.iconCls?5000:2000;				

	    
    	if(!me.msgCt){
            me.msgCt = Ext.core.DomHelper.insertFirst(document.body, {id:"msg-div"}, true);
        }

	    var createBox = function (param){	    		    	
	       var button = param.closeButton?'<p class="notifButton"><button class="qbButton" type="button" onClick="Ext.get(this).up(\'div\').ghost(\'t\', {remove: true});"> Close </button></p>':''; 
	       return '<div class="msg'+icon+'"><h3>' + param.title + '</h3><div id="msg-content">' + param.message + '</div>' + button + '</div>';
	    }
	            
        var m = Ext.core.DomHelper.append(me.msgCt, createBox(param), true);
        
        m.hide();
        if (param.closeButton) {
        	m.slideIn("t");
        } else {
        	m.slideIn("t").ghost("t", {delay: delay, remove: true});
        }
	
	}
	
	/* Fullscreen button click */
	// 18/09/2012 ACS BZ 29165 Wrong header color of the query builder in Fullscreen mode
	,onFullscreen: function() {			
		if (!this.isFullscreen) {
			Ext.getCmp('extjsHeader').setHeight(28);
			Ext.get('taHeader').addCls('qbFullScreen');
			Ext.getCmp('extjsHeader').addCls('qbFullScreen');						
			
		} else {		
			Ext.getCmp('extjsHeader').setHeight(110);
			Ext.get('taHeader').removeCls('qbFullScreen');
			Ext.getCmp('extjsHeader').removeCls('qbFullScreen');						
		}
		
		this.isFullscreen = !this.isFullscreen;
	}
	
	/* Cancel SQL request 
	 @param qbReqId string - the query id 
	*/
	,cancelSqlRequest: function(qbReqId) {
		console.log('app: Cancel qbReq - '+qbReqId);
		
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=cancelSqlRequest&id='+qbReqId,				// call query builder facade		    
		    success: function(resp){
		    	var response = Ext.decode(resp.responseText);		    	
		    	
		    	// If there is an error	    	
		    	if (response.error) {
					// Display the error in the console browser
	        		Ext.ux.message.publish('/app/error', [response.error]);
	    		}
		    },
		    failure: function(response, opts) {
			    	// On error
        			Ext.ux.message.publish('/app/error', [response]);
    		}
		});
	}
	
	/* Switch between Query and Preview tab */
	,tabSwitch: function() {
		// If query tab is displayed		
		if (Ext.getCmp('mainTabPanel').getLayout().getActiveItem().id == 'qbQueryTab') {
			if (Ext.getCmp('btHideSql').isVisible()) {
				// Hide SQL
	  			Ext.ux.message.publish('/querytab/changemode', ['HideSql']);
	  		} else {
	  			
	  			// For SQL mode, this will force update current query with the content of the SQL textarea
	  			Ext.getCmp('btPreview').focus();
	  			//Ext.getCmp('btPreview').btnEl.dom.click();
	  			// Display preview tab	  			
	    		Ext.getCmp('mainTabPanel').getLayout().setActiveItem('qbPreviewTab');	  			
	  		}
    	} else {
    		// If fullscreen graph is displayed
    		if (Ext.get('qbFullScreenGraph') && Ext.get('qbFullScreenGraph').isVisible()) {
    			// Hide fullscreen graph
    			Ext.get('qbFullScreenGraph').hide();
    		} else {
    			// Display query tab
    			Ext.getCmp('mainTabPanel').getLayout().setActiveItem('qbQueryTab');    			
    		}
    	}
	}	
});