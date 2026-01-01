/*
 * 28/07/2011 SPD1: Querybuilder V2 - View used by grids - overload default ExtJs grid view to manage row color when a selected aggregation is no available anymore 
 */

Ext.define('Ext.ux.querybuilder.GridView', {
    extend: 'Ext.grid.View',
	alias: 'widget.qbGridview',

	/* refresh row color */
	refreshRowColor: function() {				
        var me = this, el, records;

		// if grid is not valid ...
        if (!me.rendered || me.isDestroyed) {return;}
		
		// get element
        el = me.getTargetEl();
        
        // get records
        records = me.store.getRange();
        
        var orderBy = false, isFilter = false;
        
        // get grid rows      
        var rows = this.getNodes();
        
        // get number of rows
        var rowsLn = rows.length                
                
        // get current query
        var currentQuery = Ext.getCmp('qbDataGridPanel').app.currentQuery;
        
        var i= 0, row, type, record, check;
        
        // loop on all rows
        for (; i < rowsLn; i++) {
        	
            row = rows[i];
            record = records[i]; 
            
            // If no record exit !
            if (!record) { return true }
                        
            // Checks if an order by has been set
            if (record.get('order')) {
            	orderBy = true;
            }
            
            // Check if this is the filter grid
            if (record.get('id') == 'maxfilter') {
            	isFilter = true;
            }
            
			// Check if there aggregation in common
	    	me.checkAvailableAgg(record, row);
	    	
	    	// Check if row is enable
	    	me.checkRowEnable(record, row);
	    	
	    	// color filter rows
	    	me.colorFilters(record, row);
	    		    	
		}
		
		// If an order by is set, display a warning message
		if (!isFilter) {
			if (orderBy) {			
				Ext.ux.message.publish('/querytab/orderwarning', [true]);
			} else {
				Ext.ux.message.publish('/querytab/orderwarning', [false]);
			}
		}
    }
    
    /** check if the row agg. is available in the agg list
     *  @param record: the record containing the data row
     *  @param aggList: agg. list
     *  @return true if the agg. 
     */     
    ,checkAgg: function(record, aggList) {    	
    	if (!aggList[0]) {return false;}    	    	
    	var nb = aggList.length;
    	for (var i=0; i<nb; i++) {    		
    		if (aggList[i].code == record.get('id')) {
    			return true;
    		}	
    	}    	
    	return false;
    }
    
    /** Check if a row is enable 
     @param record the current record object for this row
     @param row the current row
    */
    ,checkRowEnable: function(record, row) {    	    	
    	if (record.get('enable')===false) {
    		Ext.get(row).addCls('qbDisable');    		
    	} else {
    		Ext.get(row).removeCls('qbDisable');
    	}
    }
    
    /** Check if there is aggregation in common
     * @param record : the current record datagrid
     * @param row : the current row
     */
    ,checkAvailableAgg: function(record, row) {
    	var me = this;
	    type = record.get('type');
	    check = false;
	   
        // get current query
        var currentQuery = Ext.getCmp('qbDataGridPanel').app.currentQuery;
        
	    if (type == 'na') {				// If the row is a NA            	
	    	if (currentQuery.system.aggregations.network && currentQuery.system.aggregations.network.na) {            	
	    		check = me.checkAgg(record, currentQuery.system.aggregations.network.na);
	    	}
	    } else if (type == 'ta') {		// If the row is a TA
	    	if (currentQuery.system.aggregations.time) {            	
	    		check = me.checkAgg(record, currentQuery.system.aggregations.time);
	    	}            	
	    } else if (type == 'na_axe3') {	// If the row is a NA axe 3
	    	if (currentQuery.system.aggregations.network && currentQuery.system.aggregations.network.na_axe3) {            	
	    		check = me.checkAgg(record, currentQuery.system.aggregations.network.na_axe3);
	    	}            	            	
	    } else {
	    	check = true;
	    }
		
		if (!check) {				            	            	            
	        // if the check returns false add a css class to the row (change row color, to warn the user there is a problem)
	        Ext.get(row).addCls('rowAggNoAvailable');			                                    
	  	} else {
	  		Ext.get(row).removeCls('rowAggNoAvailable');
		}	
	}

    /** Color filter rows
     * @param record : the current record datagrid
     * @param row : the current row
     */
	,colorFilters: function(record, row) {		
		if (record.get('type') == 'sys') {
			row.className = row.className.replace('qbSystemRow', '');
	        row.className += ' qbSystemRow';
		}
	}    
 });

