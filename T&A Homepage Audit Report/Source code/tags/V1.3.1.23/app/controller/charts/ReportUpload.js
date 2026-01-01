Ext.define('homepage.controller.charts.ReportUpload', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.ReportUpload'
	],

	// Initialize the event handlers
    init: function() {
        this.control({            
            'reportupload': {
            	load: this.load            	
            }
        });
    },
    
	load: function(config) {
    	   
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
		tabId+='_report_upload';

	}  
});