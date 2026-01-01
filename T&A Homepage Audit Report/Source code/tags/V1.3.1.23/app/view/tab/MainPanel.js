/* 
 * Fenetre globale de l'application.
 */
Ext.define('homepage.view.tab.MainPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.mainpanel',
    
    requires : ['Ext.ux.TabScrollerMenu'],

    deviceWidth: null,
    deviceHeight: null,
    isFullscreen : true,            // Default
    resizing: false,                
    deviceOrientation: null,
    newHeight: null,
    enableResize: false,            // Resize is updated in configuration

    runner: new Ext.util.TaskRunner(),
    
    initComponent: function() {
        this.layout = {
            type: 'hbox',
            pack: 'start',
            align: 'stretch'
        };
        
        this.items = [
            {
                id: 'tabPanel',
                xtype: 'tabpanel',
                flex: 1,
                enableTabScroll: true,
                activeTab: 0,
                plugins: [{
                    ptype: 'tabscrollermenu',
                    maxText  : 15,
                    pageSize : 5
                }],
                listeners: {
                    'beforetabchange': function(panel, newCard, oldCard) {
                        if (Ext.getCmp('configurationButton').pressed &&                                    // configuration is available
                            !Ext.getCmp('configPanel').collapsed &&                                         // configuration panel is opened 
                            (Ext.getCmp('configTab').modified || Ext.getCmp('configChart').modified )) {      // some configuration has been changed
                            // Show a warning window
                            Ext.MessageBox.confirm('Warning', 
                                'The configuration has been modified. Do you wish to continue without saving?', 
                                function(response) {
                                    if (response == 'yes') {
                                        // Prevent an infinite loop
                                        Ext.getCmp('configTab').modified = false;
                                        Ext.getCmp('configChart').modified = false;
                                        panel.setActiveTab(newCard);
                                    } 
                                }
                            );
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
            }
        ];
        
        this.callParent(arguments);
    }
});
