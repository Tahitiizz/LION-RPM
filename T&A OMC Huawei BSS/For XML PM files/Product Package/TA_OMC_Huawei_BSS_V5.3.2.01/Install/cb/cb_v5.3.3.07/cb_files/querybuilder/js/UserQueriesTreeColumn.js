/*
 * 28/07/2011 SPD1: Querybuilder V2 - Overload Ext.tree.Column' to manage current query display (bold font and pencil icon)   
 */
 
Ext.define('Ext.ux.querybuilder.UserQueriesTreeColumn', {
    extend: 'Ext.grid.column.Column',
    alias: 'widget.userqueriestreecolumn',
   
   initComponent: function() {
        var origRenderer = this.renderer || this.defaultRenderer,
            origScope    = this.scope || window;

        this.renderer = function(value, metaData, record, rowIdx, colIdx, store, view) {
        	
            var buf   = [],
                format = Ext.String.format,
                depth = record.getDepth(),
                treePrefix  = Ext.baseCSSPrefix + 'tree-',
                elbowPrefix = treePrefix + 'elbow-',
                expanderCls = treePrefix + 'expander',
                imgText     = '<img src="{1}" class="{0}" />',
                checkboxText= '<input type="button" role="checkbox" class="{0}" {1} />',
                formattedValue = origRenderer.apply(origScope, arguments),
                href = record.get('href'),
                target = record.get('hrefTarget'),
                cls = record.get('cls'),
				rec = record;
			
			// If this query is the current query change icon
			var isCurrentQueryEdited = false
           	var currentQuery = Ext.getCmp('qbRightPanel').app.currentQuery;
           	if (rec && (rec.get('queryId') == currentQuery.general.id)) {
           		isCurrentQueryEdited = true
           	}
            		
            					
            while (record) {
                if (!record.isRoot() || (record.isRoot() && view.rootVisible)) {
                    if (record.getDepth() === depth) {
                        buf.unshift(format(imgText,
                        	isCurrentQueryEdited?" qbSelectedQuery":"" +                         	             				                        	
                            treePrefix + 'icon ' + 
                            treePrefix + 'icon' + (record.get('icon') ? '-inline ' : (record.isLeaf() ? '-leaf ' : '-parent ')) +
                            (record.get('iconCls') || ''),
                            record.get('icon') || Ext.BLANK_IMAGE_URL
                        ));
                        if (record.get('checked') !== null) {
                            buf.unshift(format(
                                checkboxText,
                                (treePrefix + 'checkbox') + (record.get('checked') ? ' ' + treePrefix + 'checkbox-checked' : ''),
                                record.get('checked') ? 'aria-checked="true"' : ''
                            ));
                            if (record.get('checked')) {
                                metaData.tdCls += (' ' + Ext.baseCSSPrefix + 'tree-checked');
                            }
                        }
                        if (record.isLast()) {
                            if (record.isExpandable()) {
                                buf.unshift(format(imgText, (elbowPrefix + 'end-plus ' + expanderCls), Ext.BLANK_IMAGE_URL));
                            } else {
                                buf.unshift(format(imgText, (elbowPrefix + 'end'), Ext.BLANK_IMAGE_URL));
                            }
                            
                        } else {
                            if (record.isExpandable()) {
                                buf.unshift(format(imgText, (elbowPrefix + 'plus ' + expanderCls), Ext.BLANK_IMAGE_URL));
                            } else {
                                buf.unshift(format(imgText, (treePrefix + 'elbow'), Ext.BLANK_IMAGE_URL));
                            }
                        }
                    } else {
                        if (record.isLast() || record.getDepth() === 0) {
                            buf.unshift(format(imgText, (elbowPrefix + 'empty'), Ext.BLANK_IMAGE_URL));
                        } else if (record.getDepth() !== 0) {
                            buf.unshift(format(imgText, (elbowPrefix + 'line'), Ext.BLANK_IMAGE_URL));
                        }                      
                    }
                }
                record = record.parentNode;
            }
            if (href) {
                formattedValue = format('<a href="{0}" target="{1}">{2}</a>', href, target, formattedValue);
            }
            if (cls) {
                metaData.tdCls += ' ' + cls;
            }
                                                            
            // If this query is the current query set font weight to bold
            if (isCurrentQueryEdited) {
            	return '<b>' + buf.join("") + formattedValue + '</b>';
            } else {
            	return buf.join("") + formattedValue;	
            }
            
        };
        this.callParent(arguments);
    }

    ,defaultRenderer: function(value) {
        return value;
    }
    
    // Manage row click (highlight row when clicked) and handler function to call load query when item is clicked
    ,processEvent: function(type, view, cell, recordIndex, cellIndex, e) {   	
    	
    	// Custom click handler --> call handler function of the column item
    	var me = this, match = e.getTarget().className.match('x-grid-cell-inner'), fn;
                                               
        if (match) {        	                                
            if (type == 'click') {                
                fn = me.handler;
                if (fn) {
                	// call handler function of the column definition
                    fn.call(me.scope || me, view, recordIndex, cellIndex, e);
                }
            }            
        }
        
        // Standard click management
        return this.fireEvent.apply(this, arguments);
    }
});