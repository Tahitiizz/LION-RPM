Ext.define('homepage.view.charts.ChartsPanel' ,{
        extend: 'Ext.panel.Panel',
        alias : 'widget.chartspanel',

	layout: {
		type: 'vbox',
		align: 'stretch',
		pack: 'start'
	}, 

    initComponent: function() {
		var me = this;
		
		//override behavior for x axis labels
		Ext.define('Ext.chart.axis.override.Axis', {
		    override: 'Ext.chart.axis.Axis',

		    drawHorizontalLabels: function() {
		        var  me = this,
		             labelConf = me.label,
		             floor = Math.floor,
		             max = Math.max,
		             axes = me.chart.axes,
		             position = me.position,
		             inflections = me.inflections,
		             ln = inflections.length,
		             labels = me.labels,
		             labelGroup = me.labelGroup,
		             maxHeight = 0,
		             ratio,
		             gutterY = me.chart.maxGutter[1],
		             ubbox, bbox, point, prevX, prevLabel,
		             projectedWidth = 0,
		             textLabel, attr, textRight, text,
		             label, last, x, y, i, firstLabel,step;

		         last = ln - 1;
		         //get a reference to the first text label dimensions
		         point = inflections[0];
		         firstLabel = me.getOrCreateLabel(0, me.label.renderer(labels[0]));
		         ratio = Math.floor(Math.abs(Math.sin(labelConf.rotate && (labelConf.rotate.degrees * Math.PI / 180) || 0)));

		         for (i = 0; i < ln; i++) {
		             point = inflections[i];
		             text = me.label.renderer(labels[i]);
		             textLabel = me.getOrCreateLabel(i, text);
		             bbox = textLabel._bbox;
		             maxHeight = max(maxHeight, bbox.height + me.dashSize + me.label.padding);
		             x = floor(point[0] - (ratio? bbox.height : bbox.width) / 2);
		             if (me.chart.maxGutter[0] == 0) {
		                 if (i == 0 && axes.findIndex('position', 'left') == -1) {
		                     x = point[0];
		                 }
		                 else if (i == last && axes.findIndex('position', 'right') == -1) {
		                     x = point[0] - (bbox.width/2);
		                 }
		             }
		             if (position == 'top') {
		                 y = point[1] - (me.dashSize * 2) - me.label.padding - (bbox.height / 2);
		             }
		             else {
		                 y = point[1] + (me.dashSize * 2) + me.label.padding + (bbox.height / 2);
		             }

		             textLabel.setAttributes({
		                 hidden: false,
		                 x: x+10,
		                 y: y
		             }, true);

		             //calculate step based on number of periods
		             if(ln<=31){
		            	 step=1;
		             }
		             else if(ln<=61){
		            	 step=2;
		             }
		             else if(ln<=121){
		            	 step=5;
		             }
		             else{
		            	 step=10;
		             }
		             
		             // Skip label if there isn't available minimum space
		             if (i != 0  && i != last /*&& (me.intersect(textLabel, prevLabel)
		                 || me.intersect(textLabel, firstLabel))*/) {
		            	 if(i%step!=0){
		            		 textLabel.hide(true);
		            	 }
		            	 else{
		            		continue; 
		            	 }
		             }

		             prevLabel = textLabel;
		         }

		         return maxHeight;
		     },
		     
		     drawVerticalLabels: function() {
		         var me = this,
		             inflections = me.inflections,
		             position = me.position,
		             ln = inflections.length,
		             labels = me.labels,
		             maxWidth = 0,
		             max = Math.max,
		             floor = Math.floor,
		             ceil = Math.ceil,
		             axes = me.chart.axes,
		             gutterY = me.chart.maxGutter[1],
		             ubbox, bbox, point, prevLabel,
		             projectedWidth = 0,
		             textLabel, attr, textRight, text,
		             label, last, x, y, i;

		         last = ln;
		         for (i = 0; i < last; i++) {
		             point = inflections[i];
		             text = me.label.renderer(labels[i]);
		             textLabel = me.getOrCreateLabel(i, text);
		             bbox = textLabel._bbox;

		             maxWidth = max(maxWidth, bbox.width + me.dashSize + me.label.padding);
		             y = point[1];
		             if (gutterY < bbox.height / 2) {
		                 if (i == last - 1 && axes.findIndex('position', 'top') == -1) {
		                     y = me.y - me.length + ceil(bbox.height / 2);
		                 }
		                 else if (i == 0 && axes.findIndex('position', 'bottom') == -1) {
		                     y = me.y - floor(bbox.height / 2);
		                 }
		             }
		             if (position == 'left') {
		                 x = point[0] - bbox.width - me.dashSize - me.label.padding - 2;
		             }
		             else {
		                 x = point[0] + me.dashSize + me.label.padding + 2;
		             }
		             
		             if(i == last-1)y-=5;
		             
		             textLabel.setAttributes(Ext.apply({
		                 hidden: false,
		                 x: x,
		                 y: y
		             }, me.label), true);
		             // Skip label if there isn't available minimum space
		             if (i != 0 && i != last-1 && me.intersect(textLabel, prevLabel)) {
		                 textLabel.hide(true);
		                 continue;
		             }
		             prevLabel = textLabel;
		         }

		         return maxWidth;
		     }

		});
		
		me.selectedChart = me.id + '_chart1';
		
		this.callParent(arguments);
    }
});