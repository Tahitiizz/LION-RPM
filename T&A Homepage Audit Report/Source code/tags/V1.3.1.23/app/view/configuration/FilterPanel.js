/*
 * 28/07/2011 SPD1: Querybuilder V2 - "Filter" panel  
 * 06/10/2011 AVZ: Modifications for Homepage
 */

Ext.define('homepage.view.configuration.FilterPanel', {
	extend: 'Ext.panel.Panel',	

	requires: [
		'homepage.resources.locale',
		'homepage.resources.message',
		'Ext.form.Panel',
		'Ext.form.field.Text',
		'Ext.form.field.Checkbox',
		'Ext.tip.QuickTipManager',
		'Ext.data.TreeStore',
		'Ext.tree.Panel',
		'Ext.form.FieldContainer'
	],
		         
	id: 'qbFilterPanel',	
	flex: 1,	
    	border: true,                     	    	     
    	layout: {
    		type: 'vbox',
    		align: 'stretch'	
    	},
			
	app: null,				// pointer to the application
	treeStore: null,			// tree store
	familyTree: null, 			// family tree
	filterField: null,			// filter field
	resetButton: null, 			// reset button
	searchForm: null,			// search form
	isOptionsDisplayed: true,		// advanced options are displayed ?
	messageHandlers: null,			// message handler (publish/subscribe)
	isDirty: false,				// isDirty flag (true, if search options have been modified)
		
	// --------------------------------------------------------------------------------
	// Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		
		// Constants shortcut	
		me.cs = homepage.resources.locale;
		
		// Create a store for the products/families tree (used by for advanced search options)	
		me.treeStore = me.createTreeStore();
		
		// Create the products/families tree
		me.familyTree = me.createFamilyTree();
		
		// Enable search only when treestore is loaded
		me.treeStore.on('load', function() {			
			// Disable search
			homepage.resources.message.publish('/leftpanel/disablesearch');
			
			var products = me.familyTree.store.tree.root.childNodes; 	// get products records
			
			// for each product, 
			for (var i=0, nbProduct = products.length; i<nbProduct; i++) {
				var product = products[i];
				
				// Save the initial label
				if (!product.get('productLabel')) {
					product.set('productLabel', product.get('text'));
				}
				
				// Update the label
				me.updateProductLabel(product);
			}
			
			// enable search
			homepage.resources.message.publish('/leftpanel/enablesearch');
			
	   		// Get the network elements
	   		homepage.resources.message.publish('/leftpanel/search', [me.getSearchOptions()]);								
		});
		
		// Create filter field
		me.filterField = me.createFilterField();
		
		// Create reset button	
		me.resetButton = me.createResetButton();
		
		// Create searchForm 	
		me.searchForm = me.createSearchForm();
			  		                                                            			
    	// Add items			        
    	me.items = [
    		me.searchForm,
    		me.resetButton
		];
		
    	// call the superclass s constructor  
    	return this.callParent(arguments);		
	}
    
	/* Component initialization */
	,initComponent: function() {
		var me = this;
	
		// custom event		             
		me.addEvents('optionstoggle');				
			  
    	// save state on this custom event.
    	this.addStateEvents('optionstoggle');
        			       
		// message subscribe
        	me.messageHandlers = [];
        
    	// toggle advanced search options
    	me.messageHandlers.push(homepage.resources.message.subscribe('/filterpanel/toggleoptions', me, me.toggleAdvancedOptions));
    		     
    	// call the superclass s constructor  
    	return this.callParent(arguments);
	}   
	
	/* return state for this component, this method is automaticaly called when state is saved into a cookie*/  
	,getState: function() {
		var me = this;
		
		// call the superclass s constructor and receive the state object  
        	var state = this.callParent(arguments);
        
		// save into the state object if the options are displayed
		state.isOptionsDisplayed = me.isOptionsDisplayed;
		
		return state;
	}
		
	/* afterRender method */
	,afterRender: function() {		
		var me = this;
		
		// call the superclass s constructor  
        	var ret = this.callParent(arguments);
        		
		// display options according the sate
       		if (me.isOptionsDisplayed) {					// isOptionsDisplayed has been automaticaly loaded from the state cookie
       			me.showOptions();
       		} else {
       			me.hideOptions();       	       	       	       				       	
       		}       	
							
       		return ret;       	                 	       	       	       	
	}
	
    	/* Destroy
     	* This method is call by the unload event (when user leaves querybuilder)
     	* It destroy all component of the left panel to limit the memory leaks 
     	* */     
	,destroy: function() {
		var me = this;				
		
		// Delete message handlers (publish/subscribe)		
		Ext.Array.each(me.messageHandlers, function(handler) {
			homepage.resources.message.unsubscribe(handler);
		});    	
    		me.deleteObj(me.messageHandlers);    	
    	
		// Delete reset button		
		me.deleteObj(me.createResetButton);
			
		// Delete filter field
		me.deleteObj(me.filterField);		
											
		// Delete tree store		
		me.deleteObj(me.treeStore);
		
		// Delete tree
		me.deleteObj(me.familyTree);										
		
		// Delete search form	
		me.deleteObj(me.searchForm);
				
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
	  	
	/* Create store for the tree */	
	,createTreeStore: function() {
		return Ext.create('Ext.data.TreeStore', {
			fields: ['id', 'elementId', 'text'],								// Specify the data we want to keep store in the store
        	proxy: {
        		type: 'ajax',
        		url: 'proxy/querybuilder.php?method=getProductsFamilies'		// Get products/families from the QB facade    
        	}
		});    
	}
	        	
	/* Create tree used in advanced search options */
	,createFamilyTree: function() {
		var me = this;			
		return Ext.create('Ext.tree.Panel', {	
			scroll: false,
			viewConfig: {
				style: { overflow: 'auto', overflowX: 'hidden' }
			},
			autoLoad: false,   
			id: 'qbFamilyTree', 	
	    	store: me.treeStore,
	    	rootVisible: false,	        
	    	useArrows: true,
	    	border: false,
	    	bodyStyle: { border: 0 },
	    	listeners: {				
	    		checkchange: Ext.bind(me.onCheckChange, me)										    
	    	}			
		}); 	  
	}
	
	/* create reset button component */
	,createResetButton: function() {
		var me = this;
		
		// reset button
		var button = {
 			xtype: 'box',
 			cls: 'qbButtonFilterReset',
  			autoEl: {tag: 'div'},			
			listeners:{
				render: function(c) {
					c.getEl().on('click', Ext.bind(me.resetSearchForm, me));	// reset search form
				}
			}  			
		}		  	    	   
	    	    
		return Ext.create('Ext.container.Container', {			
			anchor: '100%',
			border: false,
			height: 13,
			style: {'background': '#fff'},
	        	items: [
	        		button
			]
		})							               
	}
	
	/* create filter field */
	,createFilterField: function() {
		var me = this;
		
		return Ext.create('Ext.form.field.Text', {														
			xtype: 'textfield',
			margin: '0 0 0 0',						
			enableKeyEvents: true,
			id: 'qbFilterField',				
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
    		]
    							
		});        
	}	
	
	/* create search form */
	,createSearchForm: function() {
		var me = this;
			
		 var form = Ext.create('Ext.form.Panel', {
		 	id: 'qbFilterForm',		 	
		 	border: false,		 	                     		 	
	        bodyStyle:'padding:5px 5px 0',
	        layout: {
	        	type: 'vbox',
	        	align: 'stretch'
	        },	        
	        fieldDefaults: {
	            labelAlign: 'top',
	            labelCls: 'qbFilterLabel',
	            labelSeparator: '',
	            margin: '10 0 0 10'
        	},
	        items: [
	        	me.filterField,
	        	/*
	        	{	        		
	        		xtype: 'fieldcontainer',
	        		layout: 'anchor',
	        		id: 'qbFilterElements',
		            	fieldLabel: 'Apply filter on',
		            	defaultType: 'checkboxfield',
		            	items: [
		                	{
		                		boxLabel  : 'KPI',
		                		name      : 'kpi',
		                		inputValue: '2',
		                		checked	  : true,
		                		id        : 'kpicheckbox',
		                		handler   : Ext.bind(me.updateSearchResult, me)
		                	},
		                	{		                	
		                		boxLabel  : 'Raw counters',
		                		name      : 'raw',
		                		inputValue: '1',
		                		checked	  : true,
		                		id        : 'rawcheckbox',
		                		handler   : Ext.bind(me.updateSearchResult, me)
		                	}
		            	]	        		
	        	},
	        	*/	        	
	        	{	        		
	        		xtype: 'fieldcontainer',	        		
	        		layout: 'fit',
	        		flex: '1',		 
	        		id: 'qbFilterProducts',
        			fieldLabel: 'Products',
	        		items: [
	        			me.familyTree
	        		]
	        	}
	        ]		
		});
		
		return Ext.create('Ext.container.Container', {			
			flex: '1',
			layout: 'fit',						
			border: false,
			style: {'background': '#fff'},			
	        items: [
	            form
			]
		})			
	}
	
	/* Display advanced search options */
	,showOptions: function() {
		var me = this;
		
		// enable resizer
		if (me.resizer) {
			me.resizer.enable();
		}
		
		me.setHeight(300); 
		//Ext.getCmp('qbFilterElements').show();
		Ext.getCmp('qbFilterProducts').show();
		me.isOptionsDisplayed = true;
	}
	
	/* Hide advanced search options */
	,hideOptions: function() {
		var me = this;
		
		// disable resizer
		if (me.resizer) {
			me.resizer.disable();
		}
				
		//Ext.getCmp('qbFilterElements').hide();
		Ext.getCmp('qbFilterProducts').hide();
		me.setHeight(52);
		me.isOptionsDisplayed = false;
	}
	
	/* Toggle advanced search options */
	,toggleAdvancedOptions: function() {
		var me = this;
		
		// Test if options are displayed
		if (me.isOptionsDisplayed) {
			// Hide options				
			me.hideOptions();
			
			// Fire custom event (to manage state)
			me.fireEvent('optionstoggle', me, true);			
		} else {			
			// Show options
			me.showOptions();
			
			// Fire custom event (to manage state)
			me.fireEvent('optionstoggle', me, false);			
		}		
	}	

	/* Tree check management */	
	,onCheckChange: function(node, checked) {	
		var me = this;
		var isProduct = node.parentNode.data.id == 'root'?true:false;					
		
		// If the user check/uncheck a product
		if (isProduct) {
			me.productCheckboxManagement(node, checked);
		} else {
			me.familyCheckboxManagement(node, checked);
		}					

		// Update resulta and dirty flag (top left red triangle displayed when advanced options have been modified)		
		me.updateSearchResult();		
	}
	
	/* Tree product checkbox management */
	,productCheckboxManagement: function(node, checked) {		
		
		// Save the original product label
		if (!node.get('productLabel')) {
			node.set('productLabel', node.get('text'));
		}
		
		// If a parent node is unchecked, uncheck all the children					
		if (!checked) {							        		
			node.eachChild(function(child){							
				child.set('checked', false);
			})					            	
		}														
		
		// If a parent node is checked, check all the children					
		if (checked) {							        		
			node.eachChild(function(child){							
				child.set('checked', true);
			})				            	
		}
		
		// Update product label
		this.updateProductLabel(node);
	}
	
	/* Tree family checkbox management */
	,familyCheckboxManagement: function(node, checked) {
		
		// Get the parent (product checkbox)
		var product = node.parentNode;
		
		// If at least a family is checked, check the product
		if (checked) {			
			if (product.get('checked') === false) {							
				product.set('checked', true);
			}
		}	
		
		// Save the original product label
		if (!product.get('productLabel')) {
			product.set('productLabel', product.get('text'));
		}
		
		// update product label
		this.updateProductLabel(product);
		
	}
	
	/* Update product label when a family checkbox is changed */
	,updateProductLabel: function(product) {
		var nbFamilies = product.childNodes.length;		// Number of families		
		var nbCheckedFamilies = 0;						// Number of checked families
		
		// Count number of checked families
		product.eachChild(function(child){							
			if (child.get('checked')) {
				nbCheckedFamilies++
			}
		})				            			
		
		// Update product label
		if (nbCheckedFamilies != nbFamilies) {
			// if at least one family is unchecked
			product.set('text', '<span>'+product.get('productLabel')+ ' ('+nbCheckedFamilies+'/'+nbFamilies+' fam.)</span>');
		} else {
			// if all families are checked
			product.set('text', product.get('productLabel'));
		}		
	}
	
	/* Check if advanced options have been modified */
	,updateDirty: function() {
		var me = this, isDirty = false;
		
		/* 1> Check if raw or kpi checkbox have been unchecked */
		/*
		if (!Ext.getCmp('rawcheckbox').value || !Ext.getCmp('kpicheckbox').value) {
			return true;
		}
		*/
				
		/* 2> Check if the family tree has been modified */
		var products = me.familyTree.store.tree.root.childNodes; 	// get products records
			
		// for each product, check if a family has been unchecked
		for (var i=0, nbProduct = products.length; i<nbProduct; i++) {
			var product = products[i];
			var label = product.get('productLabel');			
			if (label && product.get('text') == label) {			// if the product label has been modified, a family has been unchecked, the tree has been modified -> return true												
				return true;
			}	
		}
																				
		// nothing has been modified -> return false
		return false;
	}
	
	/* Update dirty flag (red triangle in the top left corner filter panel)*/
	,updateDirtyFlag: function() {
		var me = this;
		
		if (Ext.getCmp('qbFilterForm')) {
			// Test if advanced options have been modified
			if (me.updateDirty()) {
				Ext.getCmp('qbFilterForm').body.addCls('dirtyFlag');		// Add red flag
				me.isDirty = true;
			} else {			
				Ext.getCmp('qbFilterForm').body.removeCls('dirtyFlag');		// Remove red flag
				me.isDirty = false;
			}
		}
	}	
	
	/* Update search result and dirty flag */
	,updateSearchResult: function() {
		var me = this;		
		
		// Update red triangle flag
		me.updateDirtyFlag();
		
		// refresh raw and kpi list
		homepage.resources.message.publish('/leftpanel/search', [me.getSearchOptions()]);				
		
		return true;						
	}

	,getSearchOptions: function() {
		var me = this;
		
		// Search field
		var searchOptions = {
			text: me.filterField.getValue(),			// Text field value
			types: [],
			products: []
		};
						
		/*
		// RAW checkbow
		if (Ext.getCmp('rawcheckbox').value) {
			searchOptions.types.push('RAW');				// Add RAW in the type list
		}
		
		// KPI checkbow
		if (Ext.getCmp('kpicheckbox').value) {
			searchOptions.types.push('KPI');				// Add KPI in the type list
		}
		*/
					
		// Get all products from product/family filter
		var products = me.familyTree.store.tree.root.childNodes;						
				
		// for each product, check if a family has been unchecked
		for (var i=0, nbProduct = products.length; i<nbProduct; i++) {
			var product = products[i];
			
			// If this product is not checked ...jump to the next one
			if (!product.get('checked')) {
				continue;
			}			
			var families = product.childNodes;
			var checkedFamilies = [];
			
			// for each families
			var nbFamilies = families.length;
			for (var j=0; j<nbFamilies; j++) {
				var family = families[j];
				
				// If the family is checked, add it to the list
				if(family.get('checked')) {
					checkedFamilies.push(family.get('elementId'));		
				}												
			}
			
			var productItem = {
				id: product.get('elementId')					
			};
			
			// If families are not all checked, add the family list in the filter object
			if (checkedFamilies.length != nbFamilies) {
				productItem.families = checkedFamilies;
			}
			
			// If at least one family checked, add this product in the list
			if (checkedFamilies.length != 0) {
				searchOptions.products.push(productItem);
			}
		}
		return searchOptions;				
	}
	
	/* Reset search form */
	,resetSearchForm: function() {
		var me = this;
		
		// Disable search during the reset (this avoid unecessary requests)
		homepage.resources.message.publish('/leftpanel/disablesearch');
		
		// textfield: set empty	
		me.filterField.setValue('');												// reset textfield
		
		/*
		// RAW/KPI checkbox: checked
		Ext.getCmp('rawcheckbox').setValue(true);
		Ext.getCmp('kpicheckbox').setValue(true);
		*/
		var products = me.familyTree.store.tree.root.childNodes; 	// get products records
		
		// for each product, 
		for (var i=0, nbProduct = products.length; i<nbProduct; i++) {
			var product = products[i];
			product.set('checked', false);
			
			var families = product.childNodes;
			
			// for each family
			for (var j=0, nbFamilies = families.length; j < nbFamilies; j++) {
				var family = families[j];
				family.set('checked', false);
			}
			
			// update product label
			me.updateProductLabel(product);
		}
		
		// reset dirty flag
		me.updateDirtyFlag();			
		
		// enable search
		homepage.resources.message.publish('/leftpanel/enablesearch');
		
		// Get the network elements
   		homepage.resources.message.publish('/leftpanel/search', [me.getSearchOptions()]);
	}
	
});
