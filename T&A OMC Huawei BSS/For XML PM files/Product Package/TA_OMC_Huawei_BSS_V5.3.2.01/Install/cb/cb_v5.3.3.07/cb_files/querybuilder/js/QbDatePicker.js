/* Date picker for querybuiler*/
Ext.define('Ext.ux.querybuilder.QbDatePicker', {
    extend: 'Ext.picker.Date',

	shadow: false,
    renderTpl: [
        '<div class="{cls}" id="{id}" role="grid" title="{ariaTitle} {value:this.longDay}">',
        	'<div class="{baseCls}-picker">',
	            '<div role="presentation" class="{baseCls}-header">',
	                '<div class="{baseCls}-prev"><a id="{id}-prevEl" href="#" role="button" title="{prevText}"></a></div>',
                	'<div class="{baseCls}-month" id="{id}-middleBtnEl"></div>',
                	'<div class="{baseCls}-next"><a id="{id}-nextEl" href="#" role="button" title="{nextText}"></a></div>',
	            '</div>',
				'<table id="{id}-eventEl" class="{baseCls}-inner" cellspacing="0" role="presentation">',
	                '<thead role="presentation"><tr role="presentation">',
	                    '<tpl for="dayNames">',
	                        '<th role="columnheader" title="{.}"><span>{.:this.firstInitial}</span></th>',
	                    '</tpl>',
	                '</tr></thead>',
	                '<tbody role="presentation"><tr role="presentation">',
	                    '<tpl for="days">',
	                        '{#:this.isEndOfWeek}',
	                        '<td role="gridcell" id="{[Ext.id()]}">',
	                            '<a role="presentation" href="#" hidefocus="on" class="{parent.baseCls}-date" tabIndex="1">',
	                                '<em role="presentation"><span role="presentation"></span></em>',
	                            '</a>',
	                        '</td>',
	                    '</tpl>',
	                '</tr></tbody>',
	            '</table>',
	            '<tpl if="showToday">',
	                '<div id="{id}-footerEl" role="presentation" class="{baseCls}-footer"></div>',
	            '</tpl>',
	        '</div>',
	        '<div class="{baseCls}-offset qbHidden">',
	        	'<div class="{baseCls}-header">Date offset from today</div>',	        	
	        	'<div role="presentation" class="{baseCls}-offsetbody">',
	        		'<span style="float: right;margin-top: 3px;margin-right: 25px;">day(s)</span>',
	        	'</div>',
	        	'<div role="presentation" class="{baseCls}-footer qbOffsetPickerFooter"></div>',
	        '</div>',
		'</div>',
        {
            firstInitial: function(value) {
                return value.substr(0,1);
            },
            isEndOfWeek: function(value) {
                // convert from 1 based index to 0 based
                // by decrementing value once.
                value--;
                var end = value % 7 === 0 && value !== 0;
                return end ? '</tr><tr role="row">' : '';
            },
            longDay: function(value){
                return Ext.Date.format(value, this.longDayFormat);
            }
        }
    ]
    
     // private, inherit docs
    ,onRender : function(container, position){
    	var me = this;
    	    	
        Ext.apply(me.renderSelectors, {
			pickerEl: '.' + me.baseCls + '-picker',            
            offsetEl: '.' + me.baseCls + '-offset',
            offsetBodyEl: '.' + me.baseCls + '-offsetbody',
            offsetFooterEl: '.qbOffsetPickerFooter'
        });
            	            
    	this.callParent(arguments);    	
            	            	
    	// Offset button
    	me.offsetBtn = Ext.create('Ext.button.Button', {
            renderTo: me.footerEl,
            text: 'Offset',            
            tooltip: 'Floating date',
            handler: me.onOffsetClick,
            scope: me
        });
        
        // Offset field
        me.offsetField = Ext.create('Ext.form.field.Number', {
		    renderTo: me.offsetBodyEl,		    
			anchor: '100%',
			value: '0'			
		});   
					         
    	// Offset button
    	me.validateBtn = Ext.create('Ext.button.Button', {
    		renderTo: me.offsetFooterEl,
            text: 'Select',            
            tooltip: 'Validate selection',
            handler: me.onValidateClick,
            scope: me
        });
                
   		// Back button
    	me.cancelBtn = Ext.create('Ext.button.Button', {
    		renderTo: me.offsetFooterEl,
            text: 'Cancel',            
            tooltip: 'Back to calendar',
            handler: me.onBackClick,
            scope: me
        });
                        
        Ext.apply(me.renderSelectors, {            
            offsetEl: '.' + me.baseCls + '-offset'
        });    	
    }
    
    /* Offset button click */
    ,onOffsetClick: function() {    	
    	// Show offset pan
		this.el.addCls('qbOffsetPicker');    	
    }
    
    /**
     * Validate button click
	*/
    ,onValidateClick: function() {        
        var me = this;		
		me.fireEvent('select', me, me.offsetField.getValue());            
        me.onSelect();
        return me;        
    }
    
    /* Back button click */
    ,onBackClick: function() {
		this.el.removeCls('qbOffsetPicker');    	
    }    
});
