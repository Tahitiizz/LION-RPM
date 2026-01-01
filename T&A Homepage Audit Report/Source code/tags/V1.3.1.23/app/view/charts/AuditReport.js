Ext.define('homepage.view.charts.AuditReport' ,{
    extend: 'Ext.panel.Panel',
    alias : 'widget.auditreport',
    requires: ['Ext.*'],
	layout: 'anchor',
	autoScroll: true,
	
	
    initComponent: function() {
		var me = this;	
		//Creation of custom monthpicker
		Ext.define('Ext.form.field.Month', {
		    extend:'Ext.form.field.Date',
		    alias: 'widget.monthfield',
		    requires: ['Ext.picker.Month'],
		    alternateClassName: ['Ext.form.MonthField', 'Ext.form.Month'],
		    selectMonth: null,
		    createPicker: function() {
		        var me = this,
		            format = Ext.String.format;
		        return Ext.create('Ext.picker.Month', {
		            pickerField: me,
		            ownerCt: me.ownerCt,
		            renderTo: document.body,
		            floating: true,
		            hidden: true,
		            focusOnShow: true,
		            minDate: me.minValue,
		            maxDate: me.maxValue,
		            disabledDatesRE: me.disabledDatesRE,
		            disabledDatesText: me.disabledDatesText,
		            disabledDays: me.disabledDays,
		            disabledDaysText: me.disabledDaysText,
		            format: me.format,
		            showToday: me.showToday,
		            startDay: me.startDay,
		            minText: format(me.minText, me.formatDate(me.minValue)),
		            maxText: format(me.maxText, me.formatDate(me.maxValue)),
		            listeners: { 
		        select:        { scope: me,   fn: me.onSelect     }, 
		        monthdblclick: { scope: me,   fn: me.onOKClick     },    
		        yeardblclick:  { scope: me,   fn: me.onOKClick     },
		        OkClick:       { scope: me,   fn: me.onOKClick     },    
		        CancelClick:   { scope: me,   fn: me.onCancelClick }        
		            },
		            keyNavConfig: {
		                esc: function() {
		                    me.collapse();
		                }
		            }
		        });
		    },
		    onCancelClick: function() {
		        var me = this;    
		    me.selectMonth = null;
		        me.collapse();
		    },
		    onOKClick: function() {
		        var me = this;    
		    if( me.selectMonth ) {
		               me.setValue(me.selectMonth);
		            me.fireEvent('select', me, me.selectMonth);
		    }
		        me.collapse();
		    },
		    onSelect: function(m, d) {
		        var me = this;    
		    me.selectMonth = new Date(( d[0]+1 ) +'/1/'+d[1]);
		    }
		});
		//Adding each component to audit report panel
		this.items =[
		{
		layout: 'hbox',
		heigth: 100,
		cls: 'x-panel-no-border',
		padding: '15 0 10 0', 
		hideBorders: true,
        items: [
        {
        	  xtype: 'panel',
        	  cls: 'x-panel-no-border',
			  flex: 1
        },
	    {
	    	fieldLabel: 'Selected Period',
	    	//anchor: '20% none',
	    	flex: 1,
	    	labelAlign: 'left',
	    	labelWidth: 120,
	    	labelStyle: 'font-weight:bold;',
	    	//padding: '0 0 0 500',
	    	id: this.id + '_TimeField_AuditReport',
            xtype: 'fieldcontainer',
            layout: 'hbox',
            padding: '5 0 5 0', 
            items: [
                
                {
		            id: this.id + '_TimeSelector_AuditReport',
		            xtype: 'monthfield',
		            format: 'M Y',
					flex: 2
		        },
                {
                	id: this.id + '_TimeButton_AuditReport',
                    xtype: 'button',
                    text: 'Generate',
    				flex: 1,
                    action: 'generateAuditReport'
                }
                
            ]
        },
        {
        	  xtype: 'panel',
        	  cls: 'x-panel-no-border',
			  flex: 1
        }
		]},
		{
        	id: this.id + '_reliability_indicator',
        	xtype: 'rigraph',
			anchor: '98% none',
			theme: 'Fancy',
			iconCls: 'icoGraph',
			cls: 'periodChart',
			padding: '5 5 5 25',
			title:'Reliability Indicator'
		},
		{
			id: this.id + '_alarms_graph_panel',
			xtype: 'panel',
            layout: 'anchor',
			anchor: '98% none',
			iconCls: 'icoGraph',
			cls: 'periodChart',
			padding: '5 5 5 25',
			title:'Alarms Graphs'
		},
		{
			id:this.id + '_summary_graph',
			xtype: 'summarygraph',
			iconCls: 'icoGraph',
			anchor: '98% none',
			//height: 600,
			cls: 'periodChart',
			padding: '5 5 5 25',
			title:'Summary graph'
		},
		{
			id:this.id + '_report_upload',
			xtype: 'reportupload',
			iconCls: 'icoGraph',
			anchor: '98% none',
			padding: '5 5 5 25',
			title:'Report upload'
		}
	];
		
		this.callParent(arguments);
    }
 
});

