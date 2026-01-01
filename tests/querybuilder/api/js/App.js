/*
 * 21/09/2011 SPD1: QueryDataDemo App  
 */

Ext.define('Ext.ux.queryDataDemo.App', {
	
	// Extend observable to manage custom events
    mixins: {
        observable: 'Ext.util.Observable'
    },

	// Classes needed by Querybuilder
    requires: [
        'Ext.container.Viewport',               		
		'Ext.layout.container.Border',				
		'Ext.ux.queryDataDemo.LeftPanel',
		'Ext.ux.queryDataDemo.CenterPanel'			
    ],
	
	// Properties   
	leftPanel: 		null,			// the leftPanel
	centerPanel:	null,			// the centerPanel
	messageHandlers:null,			// message handler (publish/subscribe)
	
	/* Constructor */
    constructor: function (config) {
        var me = this;
        		                
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
        
// used to debug in the browser console        
app = me;

		// for IE, declare a fake console to allow using console to debug with other browsers
		if (typeof(console)=='undefined') {
			console = {log: function() {}};			
		}
		   
		// Init tooltip      	
        Ext.tip.QuickTipManager.init();
            
		// Tooltip config
		Ext.apply(Ext.tip.QuickTipManager.getQuickTip(), {
		    showDelay: 500      				// Show 500ms after entering target
		});                    
		
        // Message subscribe
        me.messageHandlers = [];
               
        // Notifications
        me.messageHandlers.push(Ext.ux.message.subscribe('/app/notification', me, me.notification));
        		
		// Create the left panel
    	me.leftPanel = Ext.create('Ext.ux.queryDataDemo.LeftPanel', {
    		app: me								// Give a link to the App
    	});
    	    	    	    	    	
		// Create the query tab
    	me.centerPanel = Ext.create('Ext.ux.queryDataDemo.CenterPanel', {
    		app: me								// Give a link to the App
    	});
    	    	    	  
    	// Create viewport  	
        me.viewport = Ext.create('Ext.container.Viewport', {                                             
	        layout: 'border',                        
	        items: [ 	        		        	   					
				me.leftPanel,						
				me.centerPanel
	        ]
	    });
    
		// Listen for onUnload (when the user leave querybuilder)
        Ext.EventManager.on(window, 'beforeunload', me.onUnload, me);

		// Ready !
        me.isReady = true;
        me.fireEvent('ready', me);
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
		
		// Flag the app that the destroy has been launched, no action (reloading, refeshing list ...) should be done anymore
		me.isDestroy = true;
		  
    	// Delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {Ext.ux.message.unsubscribe(handler);});    	
    	me.deleteObj(me.messageHandlers);
    			    	
    	// Destroy left panel
    	me.deleteObj(me.leftPanel);    	
    	
    	// Destroy right panel
    	me.deleteObj(me.centerPanel);
    	  
    	// Destroy tip manager
    	Ext.tip.QuickTipManager.destroy();
    	  		
    	// Delete viewport
    	me.deleteObj(me.viewport);
    }
    
    /* Delete an object*/
	,deleteObj: function (obj) {
		if (obj && obj.destroy) {obj.destroy();}		
		obj = null;
		delete obj;						
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
	       var button = param.closeButton?'<p class="notifButton"><input type="button" value=" Close " onClick="Ext.get(this).up(\'div\').ghost(\'t\', {remove: true});"></p>':''; 
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
			
});