Ext.define('homepage.controller.charts.Frame', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.Frame'
	],

	init: function() {
		this.control({
			'frame': {
	        	load : this.load
	        }
	    });
	},
	
	load: function(config) {
		var tabId = Ext.getCmp('tabPanel').getActiveTab().getId();
    	
		// Get the chart
		var frame = Ext.getCmp(tabId + '_' + config['@attributes']['id']);
				
		// Set the title
		var title = config['title'];
		if (typeof(title) != 'string') title = ' ';
		frame.setTitle(title);
		
		// Load the page
		var url = config['url'];
		
		if (typeof(url) !== 'undefined' && url != '') {
			Ext.getDom(tabId + '_' + config['@attributes']['id'] + '_frame').src = url;
		}
	}
});
