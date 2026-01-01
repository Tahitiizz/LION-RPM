Ext.ns('Ext.ux');
/**
 * @class Ext.ux.TabScrollerMenu
 * @extends Object
 * Plugin (ptype = 'tabscrollermenu') for adding a tab menu to a TabBar is the Tabs overflow.
 * @constructor
 * @param {Object} config Configuration options
 * @ptype tabscrollermenu
 */
Ext.define('Ext.ux.TabScrollerMenu', {
    alias: 'plugin.tabscrollermenu',

    uses: ['Ext.menu.Menu'],

    /**
     * @cfg {Number} pageSize How many items to allow per submenu.
     */
    pageSize: 10,
    /**
     * @cfg {Number} maxText How long should the title of each {@link Ext.menu.Item} be.
     */
    maxText: 15,
    /**
     * @cfg {String} menuPrefixText Text to prefix the submenus.
     */
    menuPrefixText: 'Items',
    constructor: function(config) {
        config = config || {};
        Ext.apply(this, config);
    },
    //private
    init: function(tabPanel) {
        var me = this;
		
        Ext.apply(tabPanel, me.parentOverrides);
        me.tabPanel = tabPanel;

        tabPanel.on({
            render: function() {
                me.tabBar = tabPanel.tabBar;
                me.layout = me.tabBar.layout;
                me.layout.overflowHandler.handleOverflow = Ext.Function.bind(me.showButton, me);
                me.layout.overflowHandler.clearOverflow = Ext.Function.createSequence(me.layout.overflowHandler.clearOverflow, me.hideButton, me);
            },
            single: true
        });
    },

    showButton: function() {
        var me = this,
            result = Ext.getClass(me.layout.overflowHandler).prototype.handleOverflow.apply(me.layout.overflowHandler, arguments);
		var style = Ext.get('homepageStyle').dom.value;
		
        if (!me.menuButton) {
            var rightButton = style == 'access'? 'tab-tabmenu-right-access' : 'tab-tabmenu-right';
            //var rightArrow = style == 'access'? 'box-scroller-right-access' : 'box-scroller-right';
            //var rightButton ='tab-tabmenu-right';
            var rightArrow = 'box-scroller-right';
            me.menuButton = me.tabBar.body.createChild({
                 cls: Ext.baseCSSPrefix + rightButton
            }, me.tabBar.body.child('.' + Ext.baseCSSPrefix + rightArrow ));
            me.menuButton.addClsOnOver(Ext.baseCSSPrefix + 'tab-tabmenu-over');
            me.menuButton.on('click', me.showTabsMenu, me);
        }
        me.menuButton.show();
        result.targetSize.width -= me.menuButton.getWidth();
        return result;
    },

    hideButton: function() {
        var me = this;
        if (me.menuButton) {
            me.menuButton.hide();
        }
    },

    /**
     * Returns an the current page size (this.pageSize);
     * @return {Number} this.pageSize The current page size.
     */
    getPageSize: function() {
        return this.pageSize;
    },
    /**
     * Sets the number of menu items per submenu "page size".
     * @param {Number} pageSize The page size
     */
    setPageSize: function(pageSize) {
        this.pageSize = pageSize;
    },
    /**
     * Returns the current maxText length;
     * @return {Number} this.maxText The current max text length.
     */
    getMaxText: function() {
        return this.maxText;
    },
    /**
     * Sets the maximum text size for each menu item.
     * @param {Number} t The max text per each menu item.
     */
    setMaxText: function(t) {
        this.maxText = t;
    },
    /**
     * Returns the current menu prefix text String.;
     * @return {String} this.menuPrefixText The current menu prefix text.
     */
    getMenuPrefixText: function() {
        return this.menuPrefixText;
    },
    /**
     * Sets the menu prefix text String.
     * @param {String} t The menu prefix text.
     */
    setMenuPrefixText: function(t) {
        this.menuPrefixText = t;
    },

    showTabsMenu: function(e) {
        var me = this;
        if (me.tabsMenu) {
            me.tabsMenu.removeAll();
        } else {
            me.tabsMenu = Ext.create('Ext.menu.Menu');
            me.tabPanel.on('destroy', me.tabsMenu.destroy, me.tabsMenu);
        }

        me.generateTabMenuItems();

        var target = Ext.get(e.getTarget());
        var xy = target.getXY();
		xy[1] += 24;
        //Y param + 24 pixels
        //xy[1] += 24;
		
        me.tabsMenu.showAt(xy);
    },

    // private
    generateTabMenuItems: function() {
        var me = this,
            tabPanel = me.tabPanel,
            curActive = tabPanel.getActiveTab(),
            totalItems = tabPanel.items.getCount(),
            pageSize = me.getPageSize(),
            numSubMenus =totalItems,
            i, x, item, start, index,menuWidth;
 
        //get tabPanel height
        //minus tabs
        totalHeight=Ext.getCmp('tabPanel').getHeight()-40;
        totalWidth=Ext.getCmp('tabPanel').getWidth();
        
        //one menu item takes approx 30px
        //see how many columns we need
        nbElemPerCol=Math.floor(totalHeight/30);
        
        
       
        //number of columns
        nbMenus=Math.ceil(totalItems / nbElemPerCol);
     
        
        if(nbMenus==1){
        	menuWidth=280;
        	totalWidth=280;
        }
        else{
        	if((280*nbMenus)>=totalWidth){
    			//reduce menuwidth to fit all columns
        		menuWidth=totalWidth/nbMenus;
        		totalWidth=nbMenus*(menuWidth);	
    		}
    		else{
    			menuWidth=280;
    			totalWidth=nbMenus*(menuWidth);
    		}
        }
        
		
        
        //set label Width in menu with setMaxText
        //me.setMaxText((totalWidth/nbMenus)*0.12);
        me.setMaxText(menuWidth*0.12);
          
        var multiColPanel = Ext.create('Ext.panel.Panel', {
            width: totalWidth-10,
            height: totalHeight,
            layout: {
                type: 'hbox',      
                align: 'stretch',
                pack: 'start', 	
            },
            defaults: {
                    xtype: 'menu',
                    floating: false,
                    border: false,
                    plain: true,
                },
                items: [
                ]
        });
              
        for(var i=0;i<nbMenus;i++){
        	itemPanel={};
        	//itemPanel.flex=1;
        	itemPanel.width=menuWidth;
        	itemPanel.items=[];

        	for (j = 0; j < numSubMenus; j++) {
        		menuindex=Math.floor(j / nbElemPerCol);
        		index = i;
        		if(menuindex!=index)continue;
        		item = tabPanel.items.get(j);
                itemPanel.items.push(me.autoGenMenuItem(item));
        	}
        	
        	multiColPanel.add(itemPanel);
        	
        	//do not add if last column
        	if(i!=nbMenus-1)
        		multiColPanel.add(
		        	{
		                xtype: 'menuseparator'
		            });
        }
        me.tabsMenu.add(multiColPanel);
    },

    // private
    autoGenMenuItem: function(item) {
    	//depending on templateId, set icon accordingly
    	var idTemplate=item.templateId.replace(/template/,"");
    	var menuicon='menu_icon_blue.png';
    	switch(idTemplate){
	    	case "4":
	    		menuicon='menu_icon_yellow.png';
	    		break;
	    	case "5":
	    		menuicon='menu_icon_green.png';
	    		break;
	    	case "6":
	    		menuicon='menu_icon_grey.png';
	    		break;
	    	case "7":
	    		menuicon='menu_icon_red.png';
	    		break;
	    	case "9":
	    		menuicon='menu_icon_orange.png';
	    		break;
	    	default:
	    		menuicon='menu_icon_blue.png';
    	}
    	
    	
        var maxText = this.getMaxText(),
            text = Ext.util.Format.ellipsis(item.title, maxText);
        return {
            text: text,  
            listeners: {
               render: function (c) {
	                Ext.create('Ext.tip.ToolTip', {
	                    target: c.getEl(),
	                    html: item.title
	                });
	                if(typeof(item.tab !== 'undefiend')){
		                if(item.tab.active === true){
				        	  var menuName = c.getEl().dom.id;
				        	  menuName = menuName+'-textEl';
				        }
	                }
               } 
            },
            handler: this.showTabFromMenu,
            scope: this,
            disabled: item.disabled,
            tabToShow: item,
            iconCls: item.iconCls,
            icon: '../homepage/extjs/src/ux/css/images/'+menuicon
        };
    },

    // private
    showTabFromMenu: function(menuItem) {
        this.tabPanel.setActiveTab(menuItem.tabToShow);
    }
});
