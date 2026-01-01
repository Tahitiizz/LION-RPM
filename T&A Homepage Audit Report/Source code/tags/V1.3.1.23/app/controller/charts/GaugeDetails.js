Ext.define('homepage.controller.charts.GaugeDetails', {
	extend: 'Ext.app.Controller',

	views: [
		'charts.GaugeDetails'
	], 
	
	init: function() {
		this.control({
			'gauge': {
			updateDetails : this.updateDetails
	        }
	    });
	},
	
	updateDetails: function(id, value, counter, ne, date, alarm) {		
		// Get the gauge details component
		var valueLabel = Ext.getCmp(id + '_details_value');
		
		// Change the value label
		valueLabel.setText(value);	
		
		// Update the value label color
		if (alarm == 'ok') {
			valueLabel.addCls('x-label-gauge-value-ok');
			valueLabel.removeCls('x-label-gauge-value-warning');
			valueLabel.removeCls('x-label-gauge-value-alert');
		} else if (alarm == 'warning') {
			valueLabel.removeCls('x-label-gauge-value-ok');
			valueLabel.addCls('x-label-gauge-value-warning');
			valueLabel.removeCls('x-label-gauge-value-alert');
		} else {
			valueLabel.removeCls('x-label-gauge-value-ok');
			valueLabel.removeCls('x-label-gauge-value-warning');
			valueLabel.addCls('x-label-gauge-value-alert');
		}
		
		// Update the information labels
		var infoPanelHeight = 2;
		
		//calculate font size for each label line depending on container height
		var fsize=Math.floor(Ext.getCmp(id + '_details_infoPanel').getHeight()/3-2);
		
		var ratio=Ext.getCmp(id + '_details_infoPanel').getWidth()/Ext.getCmp(id + '_details_infoPanel').getHeight()
		//for template 2, too big font size otherwise
		if(ratio<3)fsize*=0.7;
		
		if (counter != '') {
			//infoPanelHeight += 11;
			Ext.getCmp(id + '_details_label1').setText(counter);
			if (ne != '') {
				//infoPanelHeight += 11;
				Ext.getCmp(id + '_details_label2').setText(ne);
				if (date != '') {
					//infoPanelHeight += 11;
					Ext.getCmp(id + '_details_label3').setText(date);
				}
			} else if (date != '') {
				//infoPanelHeight += 11;
				Ext.getCmp(id + '_details_label2').setText(date);
			}
		} else {
			if (ne != '') {
				//infoPanelHeight += 11;
				Ext.getCmp(id + '_details_label1').setText(ne);
				if (date != '') {
					//infoPanelHeight += 11;
					Ext.getCmp(id + '_details_label2').setText(date);
				}
			} else if (date != '') {
				//infoPanelHeight += 11;
				Ext.getCmp(id + '_details_label1').setText(date);
			}
		}
		
		Ext.getCmp(id + '_details_label1').el.setStyle({'font-size': fsize+'px'});
		Ext.getCmp(id + '_details_label2').el.setStyle({'font-size': fsize+'px'});
		Ext.getCmp(id + '_details_label3').el.setStyle({'font-size': fsize+'px'});
		//Ext.getCmp(id + '_details_infoPanel').setHeight(infoPanelHeight);
	}
});