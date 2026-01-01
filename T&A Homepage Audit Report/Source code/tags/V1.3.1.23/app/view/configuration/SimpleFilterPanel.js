/*
 * 28/07/2011 SPD1: Querybuilder V2 - "Filter" panel  
 * 06/10/2011 AVZ: Modifications for Homepage
 */

Ext.define('homepage.view.configuration.SimpleFilterPanel', {
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
		         
	id: 'qbSimpleFilterPanel',	
	flex: 1,	
	border: true,                     	    	     
	layout: {
		type: 'vbox',
		align: 'stretch'	
	},
			
	app: null,					// pointer to the application
	win: null, 					// pointer to the window
	treeStore: null,			// tree store
	familyTree: null, 			// family tree
	filterField: null,			// filter field
	resetButton: null, 			// reset button
	searchForm: null,			// search form
	isDirty: false,				// isDirty flag (true, if search options have been modified)
	
	axis: null,					// 1st or 3rd axis
		
	// --------------------------------------------------------------------------------
	// Methods extended from Ext.Panel
	// --------------------------------------------------------------------------------
	 
	/* Constructor */
	constructor: function(config) {
		
		var me = this;
		me.axis = config.axis;
		me.win = config.win;		
		me.roaming = config.roaming;
		// Create a store for the products/families tree (used by for advanced search options)	
		me.treeStore = me.createTreeStore();
		// Create the products/families tree
		me.familyTree = me.createFamilyTree();
		
		// Enable search only when treestore is loaded
		me.treeStore.on('load', function() {
			me.win.disableSearch();
			
			var products = me.familyTree.store.tree.root.childNodes; 	// get products records
			
			// for each product, 
			for (var i=0, nbProduct = products.length; i<nbProduct; i++) {
				var product = products[i];
				
				// Save the initial label
				if (!product.get('productLabel')) {
					product.set('productLabel', product.get('text'));
				}
				var families = product.childNodes;
				
				
				// for each family
				
				for (var j=0, nbFamilies = families.length; j<nbFamilies; j++) {
					var family = families[j];
					
					// Save the initial label
					if (!family.get('familyLabel')) {
						family.set('familyLabel', family.get('text'));
					}
					// Update the label
					me.updateFamilyLabel(family);
				}
				
				// Update the label
				me.updateProductLabel(product);
			}
			
			// enable search
			me.win.enableSearch();
			
	   		// Get the network elements
			me.win.refreshElements(me.getSearchOptions());
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
	
	/* Destroy
 	* This method is call by the unload event (when user leaves querybuilder)
 	* It destroy all component of the left panel to limit the memory leaks 
 	* */     
	,destroy: function() {
		var me = this;					
    	
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
		var me = this;
		var method = 'getProductsFamiliesNaLevels';
		if(me.roaming == true) method = 'getProductsFamilies';
		
		if (me.axis == 3 && me.roaming == true) method = 'getProductsFamiliesNa3LevelsRoaming';
		if (me.axis == 3 && me.roaming == false) method = 'getProductsFamiliesNa3Levels';
				
		return Ext.create('Ext.data.TreeStore', {
			fields: ['id', 'elementId', 'text'],					// Specify the data we want to keep store in the store
        	proxy: {
        		type: 'ajax',
        		url: 'proxy/querybuilder.php?method=' + method		// Get products/families from the QB facade    
        	}
		});    
	}
	        	
	/* Create tree used in advanced search options */
	,createFamilyTree: function() {
		var me = this;			
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		return Ext.create('Ext.tree.Panel', {
			scroll: false,
			viewConfig: {
				style: { overflow: 'auto', overflowX: 'hidden' }
			},
			autoLoad: false,
    		id: tabId+'_qbSimpleFamilyTree', 	
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
			id: 'qbSimpleFilterField',				
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
		 	id: 'qbSimpleFilterForm',		 	
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
	        	{	        		
	        		xtype: 'fieldcontainer',	        		
	        		layout: 'fit',
	        		flex: '1',		 
	        		id: 'qbSimpleFilterProducts',
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

	/* Tree check management */	
	,onCheckChange: function(node, checked) {	
		var me = this;
		var isProduct = node.parentNode.data.id == 'root' ? true : false;					
		
		// If the user check/uncheck a product
		if (isProduct) {
			me.productCheckboxManagement(node, checked);
		} else {
			var isFamily = node.data.leaf == false ? true : false;
			if (isFamily) {
				me.familyCheckboxManagement(node, checked);
			} else {
				me.levelCheckboxManagement(node, checked);
			}			
		}					

		// Update result and dirty flag (top left red triangle displayed when advanced options have been modified)		
		me.updateSearchResult();		
	}
	
	/* Tree product checkbox management */
	,productCheckboxManagement: function(node, checked) {	
		var me = this;
		
		// Save the original product label
		if (!node.get('productLabel')) {
			node.set('productLabel', node.get('text'));
		}
		
		// If a parent node is unchecked, uncheck all the children					
		if (!checked) {							        		
			node.eachChild(function(child){							
				child.set('checked', false);
				child.eachChild(function(level){							
					level.set('checked', false);					
				});
				me.updateFamilyLabel(child);
			});	
			
			
		}														
		
		// If a parent node is checked, check all the children					
		if (checked) {							        		
			node.eachChild(function(child){							
				child.set('checked', true);
				child.eachChild(function(level){							
					level.set('checked', true);
				});
				me.updateFamilyLabel(child);
			});	
		}
		
		// Update product label
		me.updateProductLabel(node);
	}
	
	/* Tree family checkbox management */
	,familyCheckboxManagement: function(node, checked) {
		// Get the parent (product checkbox)
		var product = node.parentNode;
		
		// If a parent node is unchecked, uncheck all the children					
		if (!checked) {							        		
			node.eachChild(function(child){							
				child.set('checked', false);
			});					            	
		}
		
		// If at least a family is checked, check all the children and check the product
		if (checked) {			
			node.eachChild(function(child){							
				child.set('checked', true);
			});	
			
			if (product.get('checked') === false) {							
				product.set('checked', true);
			}
		}	
		
		// Save the original family label
		if (!node.get('familyLabel')) {
			node.set('familyLabel', node.get('text'));
		}
		
		// Save the original product label
		if (!product.get('productLabel')) {
			product.set('productLabel', product.get('text'));
		}
		
		// update family label
		this.updateFamilyLabel(node);
		
		// update product label
		this.updateProductLabel(product);		
	}
	
	/* Tree level checkbox management */
	,levelCheckboxManagement: function(node, checked) {
		// Get the parent (product checkbox)
		var family = node.parentNode;
		var product = family.parentNode;	
		
		// If at least a level is checked, check all the children and check the product
		if (checked) {						
			if (family.get('checked') === false) {							
				family.set('checked', true);
				product.set('checked', true);
			}
		}	
		
		// Save the original family label
		if (!family.get('familyLabel')) {
			family.set('familyLabel', family.get('text'));
		}
		
		// Save the original product label
		if (!product.get('productLabel')) {
			product.set('productLabel', product.get('text'));
		}
		
		// update family label
		this.updateFamilyLabel(family);
		
		// update product label
		this.updateProductLabel(product);		
	}
	
	/* Update family label when a level checkbox is changed */
	,updateFamilyLabel: function(family) {
		var nbLevels = family.childNodes.length;		// Number of levels		
		var nbCheckedLevels = 0;						// Number of checked levels
		
		// Count number of checked families
		family.eachChild(function(child){							
			if (child.get('checked')) {
				nbCheckedLevels++;
			}
		})				            			
		
		// Update family label
		if (nbCheckedLevels != nbLevels) {
			// if at least one level is unchecked
			family.set('text', '<span>'+family.get('familyLabel')+ ' ('+nbCheckedLevels+'/'+nbLevels+' desc.)</span>');
		} else {
			// if all levels are checked
			family.set('text', family.get('familyLabel'));
		}		
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
			product.set('text', '<span>'+product.get('productLabel')+ ' ('+nbCheckedFamilies+'/'+nbFamilies+' desc.)</span>');
		} else {
			// if all families are checked
			product.set('text', product.get('productLabel'));
		}		
	}
	
	/* Check if advanced options have been modified */
	,updateDirty: function() {
		var me = this, isDirty = false;
						
		/* Check if the family tree has been modified */
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
		
		if (Ext.getCmp('qbSimpleFilterForm')) {
			// Test if advanced options have been modified
			if (me.updateDirty()) {
				Ext.getCmp('qbSimpleFilterForm').body.addCls('dirtyFlag');		// Add red flag
				me.isDirty = true;
			} else {			
				Ext.getCmp('qbSimpleFilterForm').body.removeCls('dirtyFlag');		// Remove red flag
				me.isDirty = false;
			}
		}
	}	
	
	/* Update search result and dirty flag */
	,updateSearchResult: function() {
		var me = this;		
		
		// Update red triangle flag
		me.updateDirtyFlag();
		
		// refresh network elements list
		me.win.refreshElements(me.getSearchOptions());			
		
		return true;						
	}

	,getSearchOptions: function() {
		var me = this;
		// Search field
		var searchOptions = {
			text: me.filterField.getValue(),			// Text field value
			products: []
		};
							
		// Get all products from product/family filter
		var products = me.familyTree.store.tree.root.childNodes;						
				
		// for each product, check if a family has been unchecked
		for (var i=0, nbProduct = products.length; i<nbProduct; i++) {
			var product = products[i];
			// If this product is not checked ...jump to the next one
			if (!product.get('checked')) {
				continue;
			}else{
				checkedProduct = product.get('elementId');
			}
			
			var checkedLevels = [];
			var totalLevels = 0;
			var nbCheckedLevels = 0; // Can be different of checkedLevels.length
			var nbCheckedFamilies = 0;
			var families = product.childNodes;
			
			// for each family
			var nbFamilies = families.length;
			for (var j=0; j<nbFamilies; j++) {
				var family = families[j];
				
				// If this family is not checked ...jump to the next one
				if (!family.get('checked')) {
					continue;
				}else{
					checkedFamily = family.data.id;
				}
				
				if(me.roaming == false){
					nbCheckedFamilies++;
					var levels = family.childNodes;
					
					// For each level
					var nbLevels = levels.length;
					totalLevels += nbLevels;
					for (var k=0; k < nbLevels; k++) {
						var level = levels[k];
						// If the level is checked, add it to the list
						if(level.get('checked')) {
							nbCheckedLevels++;
							if (!(level.get('elementId') in checkedLevels)) {
								checkedLevels.push(level.get('elementId'));
								
							}
						} 
					}
				}
			}
			if(me.roaming == false){
				var productItem = {
					id: product.get('elementId')					
				};
							
				// If families are not all checked, add the family list in the filter object
				if (nbCheckedFamilies != nbFamilies || totalLevels != nbCheckedLevels) {
					productItem.na = checkedLevels;
				}
				
				// If at least one family checked, add this product in the list
				if (checkedLevels.length != 0) {
					searchOptions.products.push(productItem);
				}
			}else{
				if(Ext.getCmp('neAssociation').getValue().neLevelSelction == 2){
					axis = 3;
				}else{
					axis = null;
				}
					var productItem = {
					id: product.get('elementId'),
					family: checkedFamily,
					axe: axis
				};
				
				
				searchOptions.products.push(productItem);
				
			}
		}
		return searchOptions;				
	}
	
	/* Reset search form */
	,resetSearchForm: function() {
		var me = this;
		// textfield: set empty	
		me.filterField.setValue('');								// reset textfield				
		
		var products = me.familyTree.store.tree.root.childNodes; 	// get products records
		
		// for each product, 
		for (var i=0, nbProduct = products.length; i<nbProduct; i++) {
			var product = products[i];
			product.set('checked', false);
			
			var families = product.childNodes;
			
			// for each family
			for (var j=0, nbFamilies = families.length; j<nbFamilies; j++) {
				var family = families[j];
				family.set('checked', false);
				
				var levels = family.childNodes;
				
				for (var k=0, nbLevels = levels.length; k < nbLevels; k++) {
					var level = levels[k];
					level.set('checked', false);
				}
				
				// update family label
				me.updateFamilyLabel(family);
			}
			
			// update product label
			me.updateProductLabel(product);
		}
		
		// reset dirty flag
		me.updateDirtyFlag();

		// Enable search
		me.win.searchDisabled = false;		
		
		// Get the network elements
		me.win.refreshElements(me.getSearchOptions());
	}
	
});
