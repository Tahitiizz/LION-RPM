/*
 * 28/09/2011 SPD1: Querybuilder V2 - Network slection window  
 */

Ext.define('Ext.ux.querybuilder.NetworkSelectionWindow', {
	extend: 'Ext.ux.querybuilder.QbWindow',

	requires: [
		'Ext.form.Panel',
		'Ext.layout.container.VBox',
		'Ext.grid.Panel',
		'Ext.form.field.Text'		
	],
	
	mainForm: null,				// The main form
	constrainHeader: true,
	neList: null,				// NE list
	favListTab: null,			// Favorites lists tab	
	valueField: null,			// value cell field
	dragZone: null,				// Drag zone
	selectedElementsGrid: null,	// Selected elements grid
	favoritesGrid: null,		// Favorite grid
	selectedElementsTab: null,	// Selected elements tab
	favList: null,				// Favorites list

    // --------------------------------------------------------------------------------
    // Custom config
	// --------------------------------------------------------------------------------
	config: {
	    title: Ext.ux.querybuilder.locale.netSelWindow.title,
	    layout: {
	    	type: 'vbox',
	    	align: 'stretch'
		},
	    closeAction: 'hide',
	    resizable: false,
	    modal: true
	},

    // --------------------------------------------------------------------------------
    // Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------

	/* Constructor */
	constructor: function(config) {

		var me = this;

		// Constants shortcut
		me.cs = Ext.ux.querybuilder.locale;

		// On hide ...
	    me.config.listeners.hide = function() {
	    	me.valueField.fireEvent('blur', me.valueField);	    
	    };

		// Apply the custom config
		Ext.apply(config, me.config);

		// Create main form
		me.searchForm = me.createSearchForm();

		// Create favorites lists
		me.favListTab = me.createFavList();

		// Create NE list
		me.neList = me.createNeList();

		// Create grids
		me.selectedElementsGrid = me.createSelectedElementsGrid();

		me.selectedElementsTab = Ext.create('Ext.panel.Panel', {
			layout: 'fit',
			cls: 'qbSelectedElementPan',
			title: me.cs.netSelWindow.selectedElements,
			iconCls: "icoWhitePage",
			items: [
				me.selectedElementsGrid
			],
			dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',														// Button toolbar
                items: [
                	{
	                    iconCls: 'icoCross',
	                    text: me.cs.netSelWindow.deleteAll,							// Delete all button
	                    scope: me,
	                    handler: me.deleteAll
                	},
                	{
	                    iconCls: 'icoAdd2Fav',
	                    text: me.cs.netSelWindow.saveToFav,							// Save to favorites button
	                    scope: me,
	                    handler: me.onSaveToFavoritesClick
                	}                	
                ]
            }]
		});
				
		// Add docked items
		me.dockedItems = [{
        	xtype: 'toolbar',        	
        	dock: 'bottom',
        	ui: 'footer',
        	layout: {
                pack: 'center'
        	},
        	items: [{
        		// Select button
	            minWidth: 80,
            	text: Ext.ux.querybuilder.locale.netSelWindow.btSelect,            	
            	handler: function () {            		
            		me.hide();      
       				// Get selected items		
					me.setValue(me.selStore.collect('code').join(','));             		     		                 		            	
            		//me.setValue(me.selStore.data.items.length != 0?Ext.encode(Ext.Array.pluck(me.selStore.data.items, 'data')):'');            		            		            		            		            	            	
            		//me.valueField.fireEvent('blur', me.valueField);            		            		            		            
            	}                	
        	},{
        		// Cancel button
	            minWidth: 80,
            	text: Ext.ux.querybuilder.locale.netSelWindow.btCancel,            	
            	handler: function (){           		
            		me.setValue(me.backupValue);
            		me.hide();            		
            		//me.valueField.fireEvent('blur', me.valueField);
            	}                	
        	}]
    	}];
    	
        // Add the form to the content
		me.items = [
			me.searchForm,
			{
				
				layout: 'accordion',
				height: 337,
				items: [					
					me.favListTab,
					me.neList,
					me.selectedElementsTab					
				]
			}
		];								

        // call the superclass's constructor  
        return this.callParent(arguments);		
    }
     
    /* Component initialization */
	,initComponent: function() {
		var me = this;
		                           
        // call the superclass's constructor  
        return this.callParent(arguments);
	}   
	  
		/* Call after component render */
	,afterRender: function() {
		var me = this;
		
		// Disabled selection
		me.el.unselectable();								
		
		// Call parent
		this.callParent(arguments);		
	}
		
    /* Destroy
     * This method is call by the unload event (when user leaves querybuilder)
     * It destroy all component of the left panel to limit the memory leaks 
     * */     
	,destroy: function() {
		var me = this;							
		
		// Delete drag zone
		me.deleteObj(me.dragZone);
		
		// Delete form
		me.deleteObj(me.searchForm);
		
		// Delete NE list
		me.deleteObj(me.neList);
		
		// Delete favorites tab				
		me.deleteObj(me.favListTab);
		
		// Delete stores
		me.deleteObj(me.selStore);
		me.deleteObj(me.favStore);
		
		// Delete selected elements grid
		me.deleteObj(me.selectedElementsGrid);		
		me.deleteObj(me.favoritesGrid);
		
		// Delete selected elements tab
		me.deleteObj(me.selectedElementsTab);
		
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
	
	/* create search form */
	,createSearchForm: function() {
		var me = this;
			
		 var form = Ext.create('Ext.form.Panel', {		 	
		 	border: false,		 	                     		 	
	        bodyStyle:'padding:10px 5px 0',
	        layout: {
	        	type: 'vbox',
	        	align: 'stretch'
	        },	        	        
	        items: [
	        	{	// Search field														
					xtype: 'textfield',
					margin: '0 0 0 0',						
					enableKeyEvents: true,
					id: 'qbNetWFilterField',
					cls: 'qbSearchField',				
					value: '',
					checkChangeBuffer: 200,							
					validateOnBlur: false,
					validator: function() {				
						Ext.bind(me.updateSearchResult, me)();		// Update search result
						return true;
					},
					fieldSubTpl: [
				        '<table width="100%"><tr><td class="qbFilterField_left">&nbsp;</td><td class="qbFilterField_center"><input id="{id}" type="{type}" ',
				        '<tpl if="name">name="{name}" </tpl>',
				        '<tpl if="tabIdx">tabIndex="{tabIdx}" </tpl>',
				        'class="{fieldCls} {typeCls}" autocomplete="off" /></td><td class="qbFilterField_right">&nbsp;</td></tr></table>',
				        {
				            compiled: true,
				            disableFormats: true
				        }
		    		],
		    		listeners: {
		    			focus: function() {							// When search field is focused, expand "List of elements" tab		    			
		    				me.neList.expand();
		    			}
		    		}		    						
				}
	        ]		
		});
		
		return Ext.create('Ext.container.Container', {						
			layout: 'fit',				
			height: 50,
			border: false,
			style: {'background': '#fff'},			
	        items: [
	            form
			]
		})			
	}
	
	
	/* Display window
	 * Parameter:
	 * 	data - object : contains data for the element to display (id ...)
	 */ 	
	,displayWindow: function(data) {											
		// Get value grid cell					
		this.valueField = data.field;
		
		// Backup value (used if user click on cancel button)
		this.backupValue = data.record.data.value;
		
		// Parameters for the getNe function
		this.searchOptions = {"na": data.record.get('id'), "text":""};
		
		// Get search field value												
		var searchFieldValue = Ext.getCmp('qbNetWFilterField').getValue();
		
		// Load NE elements
		if (searchFieldValue != '') {
			Ext.getCmp('qbNetWFilterField').setValue('');		// if search field value != '' reset it, this will reload automaticaly NE list
		} else {
			this.loadNe(this.searchOptions);					// if still empty, reload manualy NE list
		}
		
		// Load favorites
		this.loadFav();
		
		// Display window
		this.show();				
		
		// Get the focus (ESC key will be available to close it)
		this.focus();
		
		// Load values
		this.loadValues();
	}
	
	/* Create favorites lists */
	,createFavList: function() {
		var me = this;
				
		// Create favorites grid
		me.favoritesGrid = me.createFavoritesGrid();
		
		// Favorite tab
		return Ext.create('Ext.panel.Panel', {
			title: me.cs.netSelWindow.myFavorites,
			layout: 'fit',
			iconCls: "icoStar",
			items: [
				me.favoritesGrid
			]
		});						
	}
	
	/* Create NE grid */
	,createNeList: function() {
		var me = this;
					
	    return Ext.create('Ext.panel.Panel', {	   	    		    		 	    	  
	    	title: me.cs.netSelWindow.elementList,
	    	bodyCls: "netSelNeList",
	    	iconCls: "icoNetwork", 		        
	        border: false,	        	       
		    loader: {
	        	url: '../php/querybuilder.php?method=getNe'
	    	},
	    	listeners: {
	    		"afterrender": function() {	    			
	    			// Manage click on NE list
					this.mon(Ext.get(this.body), 'click', me.onNeListClick, me);
	    		}
	    	}
		}); 	  	
										
	}
	
	/* Set selected elements value */
	,setValue: function(value) {		
		this.valueField.setValue(value);		
	}	
		
	/* Create selected elements grid */
	,createSelectedElementsGrid: function() {
		var me = this;
				
		me.selectedElements = [];
		
		Ext.define('qbNetSelWinSelElModel', {
		    extend: 'Ext.data.Model',
		    fields:['label', 'code'] 		    
		});	
			
		// Create store grid		
		me.selStore = Ext.create('Ext.data.Store', {
			storeId: 'qbNetworkSelectionWindowStore',
			model: 'qbNetSelWinSelElModel',		    
		    proxy: {
	    		type: 'memory',
	    		data: me.selectedElements,
	    		reader: {
	        		type: 'json',
	        		root: 'data'	        		     	
	    		}
			}			
		});		
    		
		// Create Grid 
		return Ext.create('Ext.grid.Panel', {
		    store: Ext.data.StoreManager.lookup('qbNetworkSelectionWindowStore'),		    		    
			id: 'qbNetSelWinGrid',
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
	                dataIndex: 'label',
	                flex: 1
	           	},           	
	            {
		            xtype:'actioncolumn',				// Delete column					
					width: 50,		            		            		             		            
		            items: [
			            {			
			            	tooltip: me.cs.netSelWindow.deleteElement,
			                getClass: function(v, m, record) {			                				                
			                	return "icoCross qbPointer";
			                },
			                handler: function(grid, rowIndex, colIndex) {			
								// find record to delete
								var record = me.selStore.getAt(rowIndex);															
								me.selStore.remove(record);				

			                    // Update title
			                    me.updateSelectedElementsTitle(true);			                    				                                      			                    							
			                }
			            }
		            ]
		        }
			]
		});
	}	
	
	/* Create favorites elements grid */
	,createFavoritesGrid: function() {
		var me = this;
				
		me.favoritesList = [];
		
		Ext.define('qbNetSelWinSelFavModel', {
		    extend: 'Ext.data.Model',
		    fields:['id', 'name', 'nb'] 		    
		});	
			
		me.favList = [];
		
		// Create store grid		
		me.favStore = Ext.create('Ext.data.Store', {
			storeId: 'qbNetworkSelectionWindowFavStore',
			data: me.favList,
			model: 'qbNetSelWinSelFavModel',
			proxy: {
	            type: 'ajax',
	            url: '../php/querybuilder.php?method=getFavNeList'
	        }		
		});		
    		
		// Create Grid 
		var favG = Ext.create('Ext.grid.Panel', {
		    store: Ext.data.StoreManager.lookup('qbNetworkSelectionWindowFavStore'),
			id: 'qbNetSelWinFavGrid',			
			border: false,
		    hideHeaders: true,	    
		    columns: [		    	
				{                
	                dataIndex: 'name',
	                flex: 1
	           	},
	           	{                
	                dataIndex: 'nb',
	                renderer: function(value) {
	                	return value + (value>1?' items':' item');
	                }	                
	           	},                    	
	            {	            
		            xtype:'actioncolumn',				// Delete column
					width: 50,		            		            		             		            
		            items: [
			            {			
			            	tooltip: me.cs.netSelWindow.deleteFavorite,
			                getClass: function(v, m, record) {			                				                
			                	return "icoCross qbPointer";
			                },
			                handler: function(grid, rowIndex, colIndex) {			                				
								// find record to delete
								var record = me.favStore.getAt(rowIndex);																						
								me.deleteFavorite(record.get('id'));
			                }
			            }
		            ]
		        }
			],
			listeners: {
				// On favorite click
				"cellclick": function(view, cell, numColumn, record) {					
					// Check the clicked column: If the user did not click on the cross icon column					
					if (numColumn != 2) {
						// Load the clicked favorite
						me.getFavoriteById(record.get('id'));
					}					
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
				
		return favG;
	}	
		
	/* NE click event handler */
	,onNeListClick: function(e) {
						
		// Find the element under the mouse pointer
		var sourceEl = e.getTarget('td', 10);															
		
		// If an element with a valid id has been found ...
        if (sourceEl && sourceEl.id) {        										
			// Add the clicked element 
			this.selStore.add({"label": sourceEl.innerHTML, "code": sourceEl.id});			

			// Update selected element tab title
			this.updateSelectedElementsTitle();
        }
		
	}
	
	/* Load NE list
	 * @Param: searchOptions - object
	 * Search options Example:  {"na": "SAI", "text":"SAI_001"};
	 */
	,loadNe: function(searchOptions) {
		var options = {};
		
		// Request parameters
		options.params = {param: Ext.encode(searchOptions)};
		
		// Load the NE list -> Ajax request
		var loader = this.neList.getLoader()
		loader.loadMask = true;						// Display loader while loading...	
		loader.load(options);
	}

	/* Load favorites */
	,loadFav: function(searchOptions) {		
		this.favoritesGrid.store.load()
	}
		
	/* Update NE list */
	,updateSearchResult: function() {				
		if (this.searchOptions) {
			// Get search string
			this.searchOptions.text = Ext.getCmp('qbNetWFilterField').getValue(); 
			
			// Load NE elements
			this.loadNe(this.searchOptions);
		}
	}
	
	/* Remove selected NE */ 
	,deleteAll: function(silenceMode) {
		// Remove elements
		this.selectedElements = [];
		this.selectedElementsGrid.store.load();
		
		// Update selected elements list title
		this.updateSelectedElementsTitle(silenceMode);	
	}
	
	/* Update selected elements list title */
	,updateSelectedElementsTitle: function(silenceMode, nb) {
		var title = this.cs.netSelWindow.selectedElements
		nb = !nb?Ext.getCmp('qbNetSelWinGrid').store.data.items.length:nb;
		
		// Compute title
		if (nb != 0) {
			title = "<b>"+nb+"</b> selected elements";
		}
		
		// Set new title			
		this.selectedElementsTab.setTitle(title);
		
		// Display animation		
		if (!silenceMode) {			
			this.selectedElementsTab.el.stopAnimation().frame("#42a9e3");
		}
	}
	
	/* Load default selected elements */
	,loadValues: function() {
			
		// Remove selected elements
		this.deleteAll(true);				 	
		
		// Load values
		if (this.backupValue && this.backupValue != '') {
						
			// Expand selected elements panel			
			this.selectedElementsTab.expand();
			
			// Get label and display selected elements
			this.getElementsLabel(this.backupValue, this.searchOptions.na);
						
		} else {
			// Expand selected elements panel			
			this.neList.expand();
		}
	}
	
	/* On save to favorite */
	,onSaveToFavoritesClick: function() {

		// Get selected items		
		var items = this.selStore.collect('code').join(',');

		// If no selected elements ...nothing to do
		if (!items) {return;}	
		
		// Expand favorites tab
		this.favListTab.expand();
		
		// Open favorite save window
		this.openFavSaveWindow(items);
		
	}
	
	/* Open favorite save window
	 * Parameter:
	 *  - items: selected items
	 * Return false is the saved is cancelled by the user
	 */  	
	,openFavSaveWindow: function(items) {
		var me = this;		
				
		// Set message box label buttons
		Ext.MessageBox.msgButtons[1].setText(me.cs.netSelWindow.btSave);
		Ext.MessageBox.msgButtons[2].setText(me.cs.netSelWindow.btCancel);
		
		// Show a dialog using config options:
		Ext.MessageBox.show({
		     title: me.cs.netSelWindow.saveFavTitle,
		     msg: me.cs.netSelWindow.saveFavMessage,
		     buttons: Ext.MessageBox.YESNO,
		     prompt: true,
		     icon: Ext.MessageBox.QUESTION,		     		    
		     fn: function(buttonId, text) {
		     	// If ok button -> Save the query
		     	if (buttonId == 'yes') {
		     		
		     		// If empty filename ...re-open the save popup
		     		if (text == '') {
		     			me.openFavSaveWindow(items);
		     			return;
		     		}
		     		
					// Save favorite
					me.saveToFavorites(text, items);
		     	}
		     }		     
		});
	}
	
	/* Save to favorites
	 * @param favName String the name for this favorite
	 * @param items String selected items
	 */	
	,saveToFavorites: function(favName, items) {
		
		var me = this;		
					
		var requestParam = {
			name: favName,										// Favorite name
			items: items										// NE code list (from selected elements)
		};
												
		// send request to the server		
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=saveToFavorites',				// call query builder facade
		    params: requestParam,
		    success: function(resp){		    							    	
		    	var response = resp.responseText?Ext.decode(resp.responseText):{};		    	
		    	
		    	// If there is an error	    	
		    	if (response.error) {
		    		// Display the error in the console browser
		        	Ext.ux.message.publish('/app/error', [response.error]);		    				    	
		    	} else {
		    		// Success, reload favorite list		
		    		me.loadFav();
		    	}				
		    },
		    failure: function(response, opts) {
			    	// On error
        			Ext.ux.message.publish('/app/error', [response]);
    		}
		});	
	}
	
	/* Delete favorites
	 * @param id: favorite id
	 */	
	,deleteFavorite: function(id) {
		var me = this;
		
		// send request to the server		
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=deleteFavorite&id='+id,				// call query builder facade		
		    success: function(resp){
		    	var response = resp.responseText?Ext.decode(resp.responseText):{};		    	
		    	
		    	// If there is an error	    	
		    	if (response.error) {
		    		// Display the error in the console browser
		        	Ext.ux.message.publish('/app/error', [response.error]);		    				    	
		    	} else {
		    		// Reload favorite list
		    		me.loadFav();		
		    	}				
		    },
		    failure: function(response, opts) {
			    	// On error
        			Ext.ux.message.publish('/app/error', [response]);
    		}
		});	
	}
	
	/* Get a favorite from its id
	 * @param id the favorite id to load */
	,getFavoriteById: function(id) {
		var me = this;
		
		// send request to the server		
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=getFavoriteById&id='+id,				// call query builder facade		
		    success: function(resp){
		    	var response = resp.responseText?Ext.decode(resp.responseText):{};		    	
		    	
		    	// If there is an error	    	
		    	if (response.error) {
		    		// Display the error in the console browser
		        	Ext.ux.message.publish('/app/error', [response.error]);		    				    	
		    	} else {
		    		
					// Expand selected elements panel			
					me.selectedElementsTab.expand();
					
					// Remove current selected elements
					me.selectedElements = [];
					me.selectedElementsGrid.store.load();
					
					// Load element									
					me.selStore.add(response);						
			
					// Update selected element tab title
					me.updateSelectedElementsTitle(true);		
		    	}				
		    },
		    failure: function(response, opts) {
		    	// On error
    			Ext.ux.message.publish('/app/error', [response]);
    		}
		});	
	}
	
	/* Get a favorite from its id
	 * @param neList NE code list */
	,getElementsLabel: function(neList, na) {
		var me = this;
		
		var requestParam = {
			neList: neList				// NE code list
                        // 24/04/2012 BBX
                        // BZ 26949 : Ajout du NA
                        ,na: na                                 
		};

		// send request to the server		
		Ext.Ajax.request({
		    url: '../php/querybuilder.php?method=getNELabels',				// call query builder facade
		    params: requestParam,		
		    success: function(resp){
		    	var response = resp.responseText?Ext.decode(resp.responseText):{};		    	
		    	
		    	// If there is an error	    	
		    	if (response.error) {
		    		// Display the error in the console browser
		        	Ext.ux.message.publish('/app/error', [response.error]);		    				    	
		    	} else {
		    		
					// Expand selected elements panel			
					me.selectedElementsTab.expand();
										
					// Load element									
					me.selStore.add(response);
					
					// Update title
			        me.updateSelectedElementsTitle(true);											
		    	}				
		    },
		    failure: function(response, opts) {
		    	// On error
    			Ext.ux.message.publish('/app/error', [response]);
    		}
		});	
		
		// Update selected element tab title
		me.updateSelectedElementsTitle(true, neList.split(',').length);
	}	
});