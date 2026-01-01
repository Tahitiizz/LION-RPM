/*
 * 28/07/2011 SPD1: Querybuilder V2 - Queries panel  
 */

Ext.define('Ext.ux.querybuilder.QueriesPanel', {
	extend: 'Ext.panel.Panel',	

	requires: [
		'Ext.layout.container.Accordion',
		'Ext.tree.Panel',
		'Ext.ux.querybuilder.QueriesStore',
		'Ext.ux.querybuilder.QbColumnAction',
		'Ext.ux.querybuilder.QueriesImportWindow'
	],
		         
    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
		id: 'qbQueriesPanel',											
		border: false,
		layout: 'accordion',		
		resizable: {handles: 's'},
		height: 300,
		listeners: {
			"resize": function() {
				// ExtJS FIX
				Ext.getCmp('qbQueriesPanel').layout.onLayout();
			}
		}				
	},	
	
	app: null,					// pointer to the application
	messageHandlers: null,		// message handler (publish/subscribe)	
	userQueriesStore: null,		// store for user queries
	publicQueriesStore: null,	// store for public queries
	userTree: null,				// user queries tree
	publicTree: null,			// public queries tree
	queriesImportWindow: null,	// queries import window
	
    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Constants shortcut	
		me.cs = Ext.ux.querybuilder.locale;
		
		// Create user queries store
		me.userQueriesStore = Ext.create('Ext.ux.querybuilder.QueriesStore', {
			clearOnLoad: false,														// don't clear items on load (items are cleared by our custom QueriesStore to fix an ExtJS bug)
			batchUpdateMode: 'complete',			
			fields: ['queryId', 'text', 'shared'],									// Specify the data we want to keep store in the store
	        proxy: {
	            type: 'ajax',
	            url: '../php/querybuilder.php?method=getQueries&from=user'			// Get my queries from the QB facade    
	        },
	        listeners: {
	        	load: function(proxy, store, queries) {	        		
	        		me.setTreeTitle(me.userTree, 0, queries.length);				// Update tree title 
	        	},	        	
	        	// FIX FOR EXT JS 4.0.7
				scrollershow: function(scroller) {									
					if (scroller && scroller.scrollEl) {						
						scroller.clearManagedListeners(); 
						scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
					}
				}	        	        	
	        }
		});
		
		// Create public queries store
		me.publicQueriesStore = Ext.create('Ext.ux.querybuilder.QueriesStore', {	// don't clear items on load (items are cleared by our custom QueriesStore to fix an ExtJS bug)
			clearOnLoad: false,
			batchUpdateMode: 'complete',
			fields: ['queryId', 'text', 'user'],									// Specify the data we want to keep store in the store
	        proxy: {
	            type: 'ajax',
	            url: '../php/querybuilder.php?method=getQueries&from=public'		// Get public queries from the QB facade    
	        },
	        listeners: {
	        	load: function(proxy, store, queries) {	        		
	        		me.setTreeTitle(me.publicTree, 0, queries.length);				// Update tree title 
	        	},
	        	// FIX FOR EXT JS 4.0.7
				scrollershow: function(scroller) {									
					if (scroller && scroller.scrollEl) {						
						scroller.clearManagedListeners(); 
						scroller.mon(scroller.scrollEl, 'scroll', scroller.onElScroll, scroller); 
					}
				}	        	
	        }
		});
		
		// create user tree		
		me.userTree = me.createUserTree();		
		
		// create public tree
		me.publicTree = me.createPublicTree();
		
		// Apply the custom config
		Ext.apply(config, me.config);
					 	  		                                                             			
        // Add items
		me.items = [
			me.userTree,
			me.publicTree			
		];								
        
        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		   
		// message subscribe
        me.messageHandlers = [];
            
        me.messageHandlers.push(Ext.ux.message.subscribe('/queriespanel/refresh', me, me.refreshQueries));                              
        me.messageHandlers.push(Ext.ux.message.subscribe('/queriespanel/export', me, me.exportQueries));               
        me.messageHandlers.push(Ext.ux.message.subscribe('/queriespanel/import', me, me.openImportQueriesWindow));
        me.messageHandlers.push(Ext.ux.message.subscribe('/queriespanel/batchcsvexport', me, me.batchCsvExport));
        
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
    	
    	// delete user queries store
    	me.deleteObj(me.userQueriesStore);
    	
    	// delete public queries store
    	me.deleteObj(me.publicQueriesStore);
    	    	
    	// delete user queries tree
    	me.deleteObj(me.userTree);
    	    	
    	// delete public queries tree
    	me.deleteObj(me.publicTree);
    			
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
	
	/* Create user tree */
	,createUserTree: function() {
		var me = this;
					
	    return Ext.create('Ext.tree.Panel', {	   
	    	id: 'qbUserTree',
	    	viewType: 'treeview',
	    	title: me.cs.queriesPanel.userQueries,
	    	backupTitle: me.cs.queriesPanel.userQueries, 	
	        store: me.userQueriesStore,
	        rootVisible: false,	        
	        useArrows: true,
	        border: false,	        
	        hideHeaders: true,	  
	        listeners: {				
				checkchange: Ext.bind(me.onUserCheckChange, me),				
				expand: function() {
					Ext.getCmp('qbUserTree').view.refresh();		// ExtJS bug fix (force view refresh when tab is activate)
				},
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
	                xtype: 'userqueriestreecolumn',					// Custom column (the current query is display in bold font weight)                
	                dataIndex: 'text',
	                flex: 1,	 
	                handler: function(view, rowIndex, colIndex) {	// click event
				        // get the record for this row (the record contains query id, name, shared value...)			                	
				        var record = view.getStore().getAt(rowIndex);
				        
						// load the clicked query
	        			Ext.ux.message.publish("/querytab/loadquery", [record.get('queryId')]);
				    }	                               	               
	            },
	            {
	            	width: 0
		            // empty column to fix ExtJS bug (column width are not ok with firefox without this column)
		        },		        
	            {
		            xtype:'qbColumnAction',							// Shared column
		            width: 20,		            		             		            
		            items: [
			            {			
			            	getTooltip: function(v, m, record) {
			            		return record.get("shared")==="true"?me.cs.queriesPanel.unshareTip:me.cs.queriesPanel.shareTip;
			            	},                			                
			                getClass: function(v, m, record) {			                				                
			                	return record.get("shared")==="true"?"public":"private";
			                },
			                handler: function(view, rowIndex, colIndex) {
			                	// get the record for this row (the record contains query id, name, shared value...)			                	
			                    var record = view.getStore().getAt(rowIndex);
			                    
			                    // set the shared value for this query
			                    me.setSharedValue(record);		                    			                    								
			                }
			            }
		            ]
		        },
	            {
		            xtype:'actioncolumn',				// Delete query column
		            width: 20, 		            
		            items: [
			            {
			                getClass: function(v, m, record) {			                				                
			                	return "icoCross";
			                },			            	
			                tooltip: me.cs.queriesPanel.deleteTip,
			                handler: function(view, rowIndex, colIndex) {
			                	// Set message box label buttons
								Ext.MessageBox.msgButtons[1].setText(me.cs.queriesPanel.okButton);
								Ext.MessageBox.msgButtons[2].setText(me.cs.queriesPanel.cancelButton);

								// Delete confirmation dialog
								Ext.MessageBox.show({title: me.cs.queriesPanel.deletePopupTitle, msg: me.cs.queriesPanel.deletePopupMessage, buttons: Ext.MessageBox.YESNO, icon: Ext.MessageBox.QUESTION, fn: function(buttonId, text) {
							     	// If yes button -> delete the query
							     	if (buttonId == 'yes') {										     					     		
										// get the record for this row (the record contains query id, name, shared value...)			                	
				                    	var record = view.getStore().getAt(rowIndex);
				                    
					                    // delete the query																
										me.deleteQuery(record);
										
										// display a notification
		    							Ext.ux.message.publish("/app/notification", [{title: me.cs.queriesPanel.deleteNotifTitle, message: me.cs.queriesPanel.deleteNotif, iconCls: "icoNotifOk"}]);
							     	}
							   	}});										
			                }
			            }
		            ]
		        }			        		        	        
            ]	    
                
		}); 	  	
	}
	
	/* Create public tree */
	,createPublicTree: function() {
		var me = this;
					
	    return Ext.create('Ext.tree.Panel', {	   
	    	id: 'qbPublicTree',
	    	viewType: 'treeview',						// Use our custom QueriesTreeView.js to add 'remove' and 'shared' icons
	    	title: me.cs.queriesPanel.publicQueries, 	
	    	backupTitle: me.cs.queriesPanel.publicQueries,
	        store: me.publicQueriesStore,
	        rootVisible: false,	        
	        useArrows: true,
	        border: false,	        
	        hideHeaders: true,
			viewConfig: {
    			loadMask: false
			},
	        listeners: {				
				checkchange: Ext.bind(me.onPublicCheckChange, me),
				expand: function() {
					Ext.getCmp('qbPublicTree').view.refresh();		// ExtJS bug fix (force view refresh when tab is activate)
				},
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
					xtype: 'userqueriestreecolumn',		// Custom column (the current query is display in bold font weight)	                
	                dataIndex: 'text',
	                flex: 1,
	                handler: function(view, rowIndex, colIndex) {	// click event
				        // get the record for this row (the record contains query id, name, shared value...)			                	
				        var record = view.getStore().getAt(rowIndex);
				        
						// load the clicked query
	        			Ext.ux.message.publish("/querytab/loadquery", [record.get('queryId')]);
	        			
				    }                    
				},		
				{
		            xtype:'qbColumnAction',							// Shared column
		            width: 30,		            		             		            
		            items: [
			            {			
			            	getTooltip: function(v, m, record) {
			            		return me.cs.queriesPanel.sharedBy+record.get('user');
			            	},                			                
			                getClass: function(v, m, record) {			                				                
			                	return "public";
			                }			                
			            }
		            ]
		        },
				{				
					width: 10
				}
			]
		}); 	  	
	}
		
	/* Refresh queries lists */
	,refreshQueries: function() {
				
		// refresh user queries
		this.userQueriesStore.load();
		
		// refresh public queries
		this.publicQueriesStore.load();
	}
	
	/* Set the shared value for a query
	 * Parameter:
	 *  - record: record from tree store, contains query id, shared value, query name ...
	 */
	,setSharedValue: function(record) {
		var me = this;
		
		// get the new shared value
		var sharedValue = (record.get('shared')==='true')?false:true;
		
		// HTTP query GET parameters
		var parameters = "&id=" + record.get('queryId') + "&shared=" + sharedValue;
		
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=setSharedValue' + parameters,		// call query builder facade		    
		    success: function(resp){		    	
		    	if (resp.responseText) {
			    	var response = Ext.decode(resp.responseText);		    	
			    		    	
			    	if (response.error) {
			    		// Display the error in the console browser
		        		Ext.ux.message.publish('/app/error', [response.error]);			    		
			    		return;
			    	}		    				    				    				   			    					    	
			    }				
				// refresh right panel queries lists
			    Ext.ux.message.publish('/queriespanel/refresh');			    
		    },
		    failure: function(response, opts) {		    					    	
			    // On error
        		Ext.ux.message.publish('/app/error', [response]);        			        			
    		}
		});		
	}
	
	/* Delete a query
	 * Parameter:
	 *  - record: record from tree store, contains query id, shared value, query name ...
	 */
	,deleteQuery: function(record) {
		var me = this;
		var id = record.get('queryId');
		
		// If user delete current query, remove id from currentQuery object to force asking a new name if the user attempt to save
		if (id == me.app.currentQuery.general.id) {
			me.app.currentQuery.general.id = '';
			me.app.currentQuery.system.hasChanged = true;
		}
		
		// HTTP query GET parameters
		var parameters = "&id=" + id;
		
		// send request to the server
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=deleteQuery' + parameters,		// call query builder facade		    
		    success: function(resp){		    	
		    	if (resp.responseText) {
			    	var response = Ext.decode(resp.responseText);		    	
			    		    	
			    	if (response.error) {
			    		// Display the error in the console browser
		        		Ext.ux.message.publish('/app/error', [response.error]);
			    	}		    				    				    				   			    					    	
			    }				
				// refresh right panel queries lists
			    Ext.ux.message.publish('/queriespanel/refresh');			    
		    }
		});		
	}
	
	/* Export selected queries from the active panel (private or public queries) */	
	,exportQueries: function() {
		var me = this;
		var queriesId = [];
		
		// Get selected user queries
		Ext.Array.forEach(me.userTree.getChecked(), function(item) {
			queriesId.push(item.data.queryId);
		});
		
		// Get selected public queries
		Ext.Array.forEach(me.publicTree.getChecked(), function(item) {
			queriesId.push(item.data.queryId);
		});

		// If not selected queries, display an error message
		if (queriesId.length == 0) {
			Ext.ux.message.publish("/app/notification", [{title: me.cs.queriesPanel.exportTitle, message: me.cs.queriesPanel.checkFirst, iconCls: "icoNotifError"}]);
			return true;	
		}		
										
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=exportQueries',				// call query builder facade
		    params: {"queriesId": queriesId.join(',')},							// Send queries ids
		    success: function(resp){
		    	var response = Ext.decode(resp.responseText);		    	
		    	
		    	// if there is an error	    	
		    	if (response.error) {
					// display the error in the console browser
	        		Ext.ux.message.publish('/app/error', [response.error]);		    		
		    	} else {													
					// Create an iframe to download the file...
					try {Ext.destroy(Ext.get('downloadIframe'));} catch(e) {}
					var el = document.createElement("iframe");
					el.setAttribute('id', 'downloadIframe');
					el.setAttribute('style', 'display:none');
					document.body.appendChild(el);
					el.setAttribute('src', 'http://'+window.location.host + response.filePath);
					
					// Display a notification message
					Ext.ux.message.publish("/app/notification", [{title: me.cs.queriesPanel.exportTitle, message: me.cs.queriesPanel.exportCompleted, iconCls: "icoNotifOk"}]);															
		    	}				
		    }				
		});
	}
	
	/** Create CSV export for each selected query */
	,batchCsvExport: function() {
		var me = this;
		var queriesId = [];
		
		// Get selected user queries
		Ext.Array.forEach(me.userTree.getChecked(), function(item) {
			queriesId.push(item.data.queryId);
		});
		
		// Get selected public queries
		Ext.Array.forEach(me.publicTree.getChecked(), function(item) {
			queriesId.push(item.data.queryId);
		});

		// If not selected queries, display an error message
		if (queriesId.length == 0) {
			Ext.ux.message.publish("/app/notification", [{title: me.cs.queriesPanel.batchExportTitle, message: me.cs.queriesPanel.checkFirst, iconCls: "icoNotifError"}]);
			return true;	
		}		
		
		// Export queries
		Ext.ux.message.publish('/downloadpanel/export', [queriesId]);										
	}
	
	/** Open queries import window */
	,openImportQueriesWindow: function() {
		var me = this;
		
		// Create the window (if not already created)
		if (!me.queriesImportWindow) {
			me.queriesImportWindow = Ext.create('Ext.ux.querybuilder.QueriesImportWindow', {});
		} 
				
		// Display window
		me.queriesImportWindow.displayWindow();
	}
	
	/** On user queries checkbox change */
	,onUserCheckChange: function(node) {
		// Get number of queries and number of checked queries		
		var nb = this.getCheckedQueries(node);
		
		// Update title
		this.setTreeTitle(this.userTree, nb.nbChecked, nb.nbQueries);	
	}
	
	/** On public queries checkbox change */	
	,onPublicCheckChange: function(node) {
		// Get number of queries and number of checked queries		
		var nb = this.getCheckedQueries(node);
		
		// Update title
		this.setTreeTitle(this.publicTree, nb.nbChecked, nb.nbQueries);		
	}	

	/** Set tree title 
	 @param tree object user or public tree
	 @param nbChecked integer number of checked queries
	 @param nbQueries integer number of queries
	*/
	,setTreeTitle: function(tree, nbChecked, nbQueries) {
		if (nbChecked != 0) {
			tree.setTitle(tree.backupTitle +' ('+nbChecked+'/'+nbQueries+')');
		} else {
			tree.setTitle(tree.backupTitle +' ('+nbQueries+')');
		}		
	}
		
	/** get checked queries */
	,getCheckedQueries: function(node) {
		// Get queries
		queries = node.parentNode.childNodes;

		var ret = {};				
		ret.nbQueries = queries.length;
		ret.nbChecked = 0;
		
		// For each query
		Ext.Array.forEach(queries, function(query) {			
			if (query.data.checked == true) {				
				ret.nbChecked++;
			}
		}, this);
		
		return ret;
	}
});