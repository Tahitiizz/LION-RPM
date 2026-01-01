/*
 * 28/07/2011 SPD1: Querybuilder V2 - Download panel  
 */

Ext.define('Ext.ux.querybuilder.DownloadPanel', {
	extend: 'Ext.panel.Panel',	

	requires: [		
		'Ext.tree.Panel',		
		'Ext.ux.querybuilder.QbColumnAction'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbDownloadPanel',
		title: Ext.ux.querybuilder.locale.downloadPanel.title,
		iconCls: 'icoExports',										
		border: false,
		flex: '1',
		layout: 'fit'
	},	
	
	app: null,					// pointer to the application
	messageHandlers: null,		// message handler (publish/subscribe)
	downloadTree: null, 		// download tree
	downloadTreeStore: null, 	// download tree store
	refreshDelay: null,			// by default refresh after 2 seconds
	contextMenu: null,			// context menu
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		
		me.config.listeners = {
			"afterrender": function() {	    							
				// Manage click on NE list
				this.mon(Ext.get(this.body), 'contextmenu', me.onContextMenu, me);
	    	}
		}	
		
		// Apply the custom config
		Ext.apply(config, me.config);
				
		// Create download store & tree
		me.createDownloadTree();
		
        // Add items
		me.items = [
			me.downloadTree
		];								
        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;		   						
		
		// message subscribe
        me.messageHandlers = [];
            
        // export action
        me.messageHandlers.push(Ext.ux.message.subscribe('/downloadpanel/export', me, me.onExportAction));                              
        me.messageHandlers.push(Ext.ux.message.subscribe('/downloadpanel/refresh', me, me.onRefresh));
        
        // Init refresh delay
        me.resetRefreshDelay();
                
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
    	
    	// delete download tree    	
    	me.deleteObj(me.downloadTree);
    	me.deleteObj(me.downloadTreeStore);
    	
    	// Delete context menu
		me.deleteObj(me.contextMenu);
		
        // call the superclass's constructor  
        return this.callParent(arguments);				
	}     

    // --------------------------------------------------------------------------------
    // Custom methods for this component
	// --------------------------------------------------------------------------------
	  
	/* Delete an object*/
	,deleteObj: function (obj) {
		if (obj && obj.destroy) {obj.destroy();}		
		delete obj;						
	}	  
	
	/* Create download store & tree */
	,createDownloadTree: function() {
		var me = this;
		
		// Create download tree store
		this.downloadTreeStore = Ext.create('Ext.ux.querybuilder.QueriesStore', {
			clearOnLoad: false,															// don't clear items on load (items are cleared by our custom queriesStore to fix an ExtJS bug)
			batchUpdateMode: 'complete',			
			fields: ['id', 'filePath', 'state', 'iconCls', 'name', 'start_date', 'end_date', 'error_message'],		// Specify the data we want to keep store in the store
	        proxy: {
	            type: 'ajax',
	            url: '../php/querybuilder.php?method=getDownloads'						// Get my queries from the QB facade    
	        },
	        listeners: {
	        	load: Ext.bind(me.onStoreLoad, me),
	        	// FIX FOR EXT JS 4.0.7
				scrollershow: function(scroller) {					
					if (scroller && scroller.scrollEl) {
						scroller.clearManagedListeners(); 
						scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
					}
				}
	        }
		});
		
		// Create download tree
		this.downloadTree = Ext.create('Ext.tree.Panel', {	   
	    	id: 'qbDownloadTree',
			padding: '5 0 5 3',
			bodyStyle: 'border:0',	// Fix ExtJs bug (border: false -> doesn't work yet with accordion)
			border: false,
	    	viewType: 'treeview',	    	 
	        store: this.downloadTreeStore,
	        rootVisible: false,
	        useArrows: true,	        
	        hideHeaders: true,
	        listeners: {
	        	// FIX FOR EXT JS 4.0.7
				scrollershow: function(scroller) {									
					if (scroller && scroller.scrollEl) {						
						scroller.clearManagedListeners(); 
						scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
					}
				}
	        },
			columns: [
	            {
		            xtype:'qbColumnAction',						
		            width: 20,		            		             		            
		            items: [
			            {			
			            	getTooltip: function(v, m, record) {
			            		//return record.get("shared")==="true"?me.cs.queriesPanel.unshareTip:me.cs.queriesPanel.shareTip;
			            		var ret = "<table>";
			            		ret += "<tr><td colspan='2'>State: <b>"+record.get("state")+"</b></td></tr>";
			            		ret += record.get("start_date")?"<tr><td>Start:&nbsp;&nbsp;</td><td>"+record.get("start_date")+"</td></tr>":"";
			            		ret += record.get("end_date")?"<tr><td>End:&nbsp;&nbsp;</td><td>"+record.get("end_date")+"</td></tr>":"";
			            		if (record.get('error_message')) {
			            			ret += record.get("error_message")?"<tr><td>Error:&nbsp;&nbsp;</td><td>"+record.get("error_message")+"</td></tr>":"";
			            		}
			            		ret += "</table>";		
			            		return ret;			            			            		
			            	},                			                
			                getClass: function(v, m, record) {			                	
			                	return record.get("iconCls");
			                }
			            }
		            ]
		        },
				{
	                dataIndex: 'name',
	                flex: 1,
	                	                	               
	                // Manage click (call handler function)
	                processEvent: function(type, view, cell, recordIndex, cellIndex, e) {   	    	
				    	// Custom click handler --> call handler function of the column item
				    	var me = this, match = e.getTarget().className.match('x-grid-cell-inner'), fn;				                                              
				        if (match) {        	                                
				            if (type == 'click') {                
				                fn = me.handler;
				                if (fn) {
				                	// call handler function of the column definition
				                    fn.call(me.scope || me, view, recordIndex, cellIndex, e);
				                }
				            }            
				        }
				        
				        // Standard click management
				        return this.fireEvent.apply(this, arguments);
				    },
				    
					// On click handler
	                handler: Ext.bind(me.onExportClick, me) 					// Download export				    				    	               
	            },
   	            {
		            xtype:'qbColumnAction',										// 'Open query used to generate this export' Column
		            width: 20,		            		             		            
		            items: [
		            	{	// Open query icon		            	
		            		getTooltip: function(v, m, record) {			            		
			            		return me.cs.downloadPanel.openQuery;
			            	},                			                
			                getClass: function(v, m, record) {			                				                
			                	return "x-tree-icon-leaf";
			                },
			                handler: function(view, rowIndex, colIndex) {		// Handler			                	
			                	var record = view.getStore().getAt(rowIndex);
			                	// Load the query used to generate this export
			                	Ext.ux.message.publish('/querytab/loadexportedquery', [record.get('id')]);			                	
			                }
		            	}
		            ]
		        },    
	            {
		            xtype:'qbColumnAction',										// Delete column
		            width: 20,		            		             		            
		            items: [		            	
			            {			
			            	getTooltip: function(v, m, record) {			            		
			            		return me.cs.downloadPanel.deleteExport;
			            	},                			                
			                getClass: function(v, m, record) {			                				                
			                	return "icoCross";
			                },
			                handler: function(view, rowIndex, colIndex) {		// Delete export handler
			                	// Set message box label buttons			                	
								Ext.MessageBox.msgButtons[1].setText(me.cs.downloadPanel.okButton);
								Ext.MessageBox.msgButtons[2].setText(me.cs.downloadPanel.cancelButton);
			               
								// Delete confirmation dialog
								Ext.MessageBox.show({title: me.cs.downloadPanel.deletePopupTitle, msg: me.cs.downloadPanel.deletePopupMessage, buttons: Ext.MessageBox.YESNO, icon: Ext.MessageBox.QUESTION, fn: function(buttonId, text) {
							     	// If yes button -> delete the download
							     	if (buttonId == 'yes') {										     					     		
										// get the record for this row (the record contains download id...)			                	
				                    	var record = view.getStore().getAt(rowIndex);
				                    
					                    // delete the query																
										me.deleteDownload(record);										
							     	}
							   	}});										
			                }
			            }
		            ]
		        },
		        {
	            	width: 12		            
		        }
            ]
		});
		
		// Context menu
		this.contextMenu = new Ext.menu.Menu({		  
		  items: [
			  {	// Delete all			  	
			    text: me.cs.downloadPanel.deleteAll,
			    iconCls: 'icoDeleteAll',			    			   
			    handler: Ext.bind(me.onDeleteAll, me)		// on delete all click handler			    		    		  
			  }			  
		  ]		  
		});		
	}
	
	/** Export action (export button click)
	 * @Param queriesId array: queries id to export, if no queriesId export the current query
	 */
	,onExportAction: function(queriesId) {
		var me = this;
						
		// Set request post parameters (send queriesId, if not queries id list, send the current query object)
		var requestParam = { 
			param: Ext.encode({
				"queriesId": queriesId
			})
		}
		
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=csvExportQueries',				// call query builder facade
		    params: requestParam,
		    success: function(resp){
		    	var response = Ext.decode(resp.responseText);		    	
		    			    	
		    	// If there is an error	    	
		    	if (response.error) {
		    		if (response.error.type == 'user') {
						// Display an error notification
			    		Ext.ux.message.publish("/app/notification", [{title: me.cs.downloadPanel.notificationTitle, message: response.error.message, iconCls: "icoNotifError"}]);		    			
		    		} else {		    		
						// Display the error in the console browser
		        		Ext.ux.message.publish('/app/error', [response.error]);
		        	}
		    	} else {
		    		// Display a notification
		    		Ext.ux.message.publish("/app/notification", [{title: me.cs.downloadPanel.notificationTitle, message: me.cs.downloadPanel.processStarted, iconCls: "icoNotifOk"}]);		    				    				    	
		    		
		    		// Reset refresh delay
		    		window.clearTimeout(me.timeoutId);
		    		me.resetRefreshDelay();
		    					    			    		
		    		window.setTimeout(function() {
		    			// refresh exports panel		    			
		    			Ext.ux.message.publish('/downloadpanel/refresh');
		    		}, 200);
		    		
		    	}				
		    },
		    failure: function(response, opts) {
			    	// On error
        			Ext.ux.message.publish('/app/error', [response]);
    		}
		});	
	}	
	
	/* On delete all action */
	,onDeleteAll: function() {
		var me = this;
		
		// Set message box label buttons			                	
		Ext.MessageBox.msgButtons[1].setText(me.cs.downloadPanel.okButton);
		Ext.MessageBox.msgButtons[2].setText(me.cs.downloadPanel.cancelButton);
	   
		// Delete confirmation dialog
		Ext.MessageBox.show({title: me.cs.downloadPanel.deleteAllPopupTitle, msg: me.cs.downloadPanel.deleteAllPopupMessage, buttons: Ext.MessageBox.YESNO, icon: Ext.MessageBox.QUESTION, fn: function(buttonId, text) {
	     	// If yes button -> delete the download
	     	if (buttonId == 'yes') {										     					     							       
	            // delete the query																
				me.deleteAllDownload();				
	     	}
	   	}});												
	}
	
	/* Delete a download
	 * Parameter:
	 *  - record: record from tree store, contains query id ...
	 */
	,deleteDownload: function(record) {
		var me = this;
		var id = record.get('id');
				
		// HTTP query GET parameters
		var parameters = "&id=" + id;
		
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=deleteDownload' + parameters,		// call query builder facade		    
		    success: function(resp){		    	
		    	if (resp.responseText) {
			    	var response = Ext.decode(resp.responseText);		    	
			    		    	
			    	if (response.error) {
			    		// Display the error in the console browser
		        		Ext.ux.message.publish('/app/error', [response.error]);			    		
			    		return;
			    	}		    				    				    				   			    					    	
			    }				
				// refresh download panel
			    Ext.ux.message.publish('/downloadpanel/refresh');			    
		    },
		    failure: function(response, opts) {		    					    	
			    // On error
        		Ext.ux.message.publish('/app/error', [response]);        			        			
    		}
		});		
	}
	
	/* Refresh download panel */
	,onRefresh: function() {			
		this.downloadTree.store.load();
	}
	
	/* Reset refresh delay */
	,resetRefreshDelay: function() {
		this.refreshDelay = 1000;
	}
	
	/* Compute refresh delay (2,4,8 or 10 seconds) */
	,computeRefreshDelay: function() {
		this.refreshDelay = (this.refreshDelay>4000)?10000:this.refreshDelay*2;		
	}
	
	/* Refresh exports list in x seconds */
	,deferedExportRefresh: function() {
		// Compute refresh delay (refresh every 2,4,8 or 10 seconds)
		this.computeRefreshDelay();
		
		window.clearTimeout(this.timeoutId);
		this.timeoutId = window.setTimeout(function() {
			// refresh exports panel		    			
			Ext.ux.message.publish('/downloadpanel/refresh');
		}, this.refreshDelay);
	}
	
	/* On export click */
	, onExportClick: function(view, rowIndex, colIndex) {
		          			          
		var record = view.getStore().getAt(rowIndex);		
		// Launch export download if it is completed
		if (record.get('state') == 'Completed') {					        									       	               	
			// Create an iframe to download the file...
			try {Ext.destroy(Ext.get('downloadIframe'));} catch(e) {}
			var el = document.createElement("iframe");
			el.setAttribute('id', 'downloadIframe');
			el.setAttribute('style', 'display:none');
			document.body.appendChild(el);
			el.setAttribute('src', 'http://'+window.location.host + record.get('filePath'));
		}
	}
	
	/* When store has been loaded */
	,onStoreLoad: function(store) {
		
		// get server response
		var response = store.proxy.reader.jsonData;
		
		// if an export as finished, display an animation
		if (this.nbLoading  && (this.nbLoading != response.nbLoading || this.nbWaiting != response.nbWaiting)) {
			// display animation						
			Ext.getCmp('qbDownloadTree').body.frame("#42a9e3");
			this.resetRefreshDelay();
		}
		
		// save exports state
		this.nbLoading = response.nbLoading;
		this.nbWaiting = response.nbWaiting;
		
		// If there are exports still waiting or in progress ...refresh exports list in x seconds
		if (this.nbLoading > 0 || this.nbWaiting > 0) {
			this.deferedExportRefresh();
		}
				
		// Compute export panel title
		var title = Ext.ux.querybuilder.locale.downloadPanel.title;
		
		if (this.nbWaiting>0) {
			title += ' (' + this.nbLoading + '/' + (this.nbWaiting+this.nbLoading) + ' in progress)';	
		} else if (this.nbLoading>0) {
			title += ' (' + this.nbLoading + ' in progress)';
		}
							
		this.setTitle(title);
							
	}
	
	/* On context menu event */
	,onContextMenu: function(e) {		
		// Open context menu							
		this.contextMenu.showAt(e.getXY());
	}
	
	/* Delete all download */
	,deleteAllDownload: function() {
		var me = this;
		
		// Stop auto-refresh				
		window.clearTimeout(me.timeoutId);
		
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=deleteAllDownload',		// call query builder facade		    
		    success: function(resp){		    	
		    	if (resp.responseText) {
			    	var response = Ext.decode(resp.responseText);		    	
			    		    	
			    	if (response.error) {
			    		// Display the error in the console browser
		        		Ext.ux.message.publish('/app/error', [response.error]);			    		
			    		return;
			    	}		    				    				    				   			    					    	
			    }				
				// refresh download panel
			    Ext.ux.message.publish('/downloadpanel/refresh');			    
		    },
		    failure: function(response, opts) {		    					    	
			    // On error
        		Ext.ux.message.publish('/app/error', [response]);        			        			
    		}
		});		
	}
});