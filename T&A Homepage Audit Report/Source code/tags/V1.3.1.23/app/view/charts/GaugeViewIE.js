Ext.define('homepage.view.charts.GaugeViewIE' ,{
    extend: 'Ext.panel.Panel',
    alias : 'widget.gaugeviewie',
    
    flex: 1,
	cls: 'x-panel-no-border',
	
	layout: 'absolute',
	
	// gauges
	gaugeBackRed: null,
	gaugeBackOrange: null,
	gaugeBackGreen: null,
	gaugeLeft: null,
	gaugeRight: null,
	
	initComponent: function() {	
		var me = this;
				
		me.hide();
		
	    var store1 = Ext.create('Ext.data.JsonStore', {
	        fields: ['data'],
	        data: [{'data': 30}]
	    });
	  
	    var store2 = Ext.create('Ext.data.JsonStore', {
	        fields: ['data'],
	        data: [{'data': 60}]
	    });
	    
	    var store3 = Ext.create('Ext.data.JsonStore', {
	        fields: ['data'],
	        data: [{'data': 100}]
	    });
	    
	    var store4 = Ext.create('Ext.data.JsonStore', {
	        fields: ['data'],
	        data: [{'data': 45}]
	    });
	    
	    var store5 = Ext.create('Ext.data.JsonStore', {
	        fields: ['data'],
	        data: [{'data': 47}]    
	    });
		
	    var left = Ext.create('Ext.chart.Chart', {
	    	store: store4,
		    x: 20, 
		    y: 10,
		    width: 307,
		    height: 79,
		    axes: [{
		        type: 'gauge',
		        position: 'gauge',
		        minimum: 0,
		        maximum: 100,
		        steps: 10,
		        margin: -10,
		        label   : {renderer: emptyText}
		    }],
		    series: [{
		        type: 'gauge',
		        field: 'data',
		        donut: false,
		        colorSet: ['#FFFFFF', '#000000']
		    }]
	    });
	    
	    var right = Ext.create('Ext.chart.Chart', {
	    	store: store5,
		    x: 20, 
		    y: 10,
		    width: 307,
		    height: 79,
		    axes: [{
		        type: 'gauge',
		        position: 'gauge',
		        minimum: 0,
		        maximum: 100,
		        steps: 10,
		        margin: -10,
		        label   : {renderer: emptyText}
		    }],
		    series: [{
		        type: 'gauge',
		        field: 'data',
		        donut: false,
		        colorSet: ['transparent', '#FFFFFF']
		    }]
	    });
	    
	    var backGreen = Ext.create('Ext.chart.Chart', {
	    	store: store3,
		    x: 20, 
		    y: 10,
		    width: 307,
		    height: 79,
		    axes: [{
		        type: 'gauge',
		        position: 'gauge',
		        minimum: 0,
		        maximum: 100,
		        steps: 10,
		        margin: -10,
		        label   : {renderer: emptyText}
		    }],
		    series: [{
		        type: 'gauge',
		        field: 'data',
		        donut: 80,
		        colorSet: ['#94AE0A', 'transparent']
		    }]
	    });
	    
	    var backOrange = Ext.create('Ext.chart.Chart', {
	    	store: store2,
		    x: 20, 
		    y: 10,
		    width: 307,
		    height: 79,
		    axes: [{
		        type: 'gauge',
		        position: 'gauge',
		        minimum: 0,
		        maximum: 100,
		        steps: 10,
		        margin: -10,
		        label   : {renderer: emptyText}
		    }],
		    series: [{
		        type: 'gauge',
		        field: 'data',
		        donut: 80,
		        colorSet: ['#F49D10', 'transparent']
		    }]
	    });
	    
	    var backRed = Ext.create('Ext.chart.Chart', {
	    	cls: 'gaugeie',
	    	store: store1,
		    x: 20, 
		    y: 10,
		    width: 307,
		    height: 79,
		    background: {
		        fill: 'transparent'
		    },
		    axes: [{
		        type: 'gauge',
		        position: 'gauge',
		        minimum: 0,
		        maximum: 100,
		        steps: 7,
		        margin: 4,
		        label   : {renderer: formatText}
		    }],
		    series: [{
		        type: 'gauge',
		        field: 'data',
		        donut: 80,
		        colorSet: ['#FF0000', 'transparent']
		    }]
	    });
	    	    
		me.items = [
		    left,
		    right,
			backGreen,
			backOrange,
			backRed
		];
		
		me.gaugeBackRed = backRed;
		me.gaugeBackOrange = backOrange;
		me.gaugeBackGreen = backGreen;
		me.gaugeLeft = left;
		me.gaugeRight = right;
		
		me.callParent(arguments);
	},
	
	resizePanel: function() {	
		var me = this;
		
		var gaugewidth = me.getWidth() - 40;
		var gaugeHeight = Math.min(Math.floor(gaugewidth / 2), me.getHeight() - 15);
		
		var gaugeY = Math.floor((me.getHeight() - gaugeHeight) / 2);
		gaugeY = 10;
		
		if ((gaugewidth > 0) && (gaugeHeight > 0)) {
			// Resize the gauges
			me.gaugeBackRed.setWidth(gaugewidth);
			me.gaugeBackOrange.setWidth(gaugewidth);
			me.gaugeBackGreen.setWidth(gaugewidth);
			me.gaugeLeft.setWidth(gaugewidth);
			me.gaugeRight.setWidth(gaugewidth);
			
			me.gaugeBackRed.setHeight(gaugeHeight);
			me.gaugeBackOrange.setHeight(gaugeHeight);
			me.gaugeBackGreen.setHeight(gaugeHeight);
			me.gaugeLeft.setHeight(gaugeHeight);
			me.gaugeRight.setHeight(gaugeHeight);
				
			me.gaugeBackRed.setPosition(me.gaugeBackRed.x, gaugeY);
			me.gaugeBackOrange.setPosition(me.gaugeBackOrange.x, gaugeY);
			me.gaugeBackGreen.setPosition(me.gaugeBackGreen.x, gaugeY);
			me.gaugeLeft.setPosition(me.gaugeLeft.x, gaugeY);
			me.gaugeRight.setPosition(me.gaugeRight.x, gaugeY);
		}
	},
	
	setValues: function(value, gaugeMin, gaugeMax, warningMin, warningMax, alertMin, alertMax, failureGauge) {
		var me = this;
		me.show();
		me.resizePanel();

		// Set the color sets
		if (failureGauge) {
			me.gaugeBackOrange.series.get(0).colorSet = ['transparent', '#F49D10'];
			me.gaugeBackRed.series.get(0).colorSet = ['transparent', '#FF0000'];
		} else {
			me.gaugeBackOrange.series.get(0).colorSet = ['#F49D10', 'transparent'];
			me.gaugeBackRed.series.get(0).colorSet = ['#FF0000', 'transparent'];
		}
		
		// Set min/max
		me.gaugeBackRed.axes.get('gauge').minimum = gaugeMin;
		me.gaugeBackOrange.axes.get('gauge').minimum = gaugeMin;
		me.gaugeBackGreen.axes.get('gauge').minimum = gaugeMin;
		me.gaugeLeft.axes.get('gauge').minimum = gaugeMin;
		me.gaugeRight.axes.get('gauge').minimum = gaugeMin;
	
		me.gaugeBackRed.axes.get('gauge').maximum = gaugeMax;
		me.gaugeBackOrange.axes.get('gauge').maximum = gaugeMax;
		me.gaugeBackGreen.axes.get('gauge').maximum = gaugeMax;
		me.gaugeLeft.axes.get('gauge').maximum = gaugeMax;
		me.gaugeRight.axes.get('gauge').maximum = gaugeMax;		
		
		// Set the value
		var needleWidth = (gaugeMax - gaugeMin) / 200;
		var leftValue = parseFloat(value) - needleWidth;
		var rightValue = parseFloat(value) + needleWidth;
		
		me.gaugeLeft.store.loadData([{'data': leftValue}]);
		me.gaugeRight.store.loadData([{'data': rightValue}]);
		
		// Set the alarm levels
		me.gaugeBackGreen.store.loadData([{'data': gaugeMax}]);
		
		var orangeValue = null;
		var redValue = null;
		
		if (failureGauge) {
			orangeValue = gaugeMax;
			if (warningMax != null) {
				orangeValue = warningMin;
			}
			
			redValue = gaugeMax;
			if (alertMax != null) {
				redValue = alertMin;
			}
		} else {
			orangeValue = gaugeMin;
			if (warningMax != null) {
				orangeValue = warningMax;
			}
			
			redValue = gaugeMin;
			if (alertMax != null) {
				redValue = alertMax;
			}
		}
		
		me.gaugeBackOrange.store.loadData([{'data': orangeValue}]);
		me.gaugeBackRed.store.loadData([{'data': redValue}]);			
	}	
});

function emptyText(val) {
    return '';
}

function formatText(val) {
	if (val >= 1000000000) {
		return Math.round(val / 1000000000);
	} else if (val >= 1000000) {
		return Math.round(val / 1000000);
	} else if (val >= 1000) {
		return Math.round(val / 1000);
	} else return val;
}