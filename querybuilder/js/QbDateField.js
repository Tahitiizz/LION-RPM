/* Date field used in Query builder */

Ext.define('Ext.ux.querybuilder.QbDateField', {
    extend:'Ext.form.field.Date',    
    requires: ['Ext.ux.querybuilder.QbDatePicker'],    


    createPicker: function() {
        var me = this,
            format = Ext.String.format;

        return Ext.create('Ext.ux.querybuilder.QbDatePicker', {
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
                scope: me,
                select: me.onSelect
            },
            keyNavConfig: {
                esc: function() {
                    me.collapse();
                }
            }
        });
    }
    
    /* Parse date value */
    ,parseDate : function(value) {    	
        if(!value || Ext.isDate(value)){
            return value;
        }
                
       	// Offset date management
        if (value == 'today') {							// If the field contains today -> do nothing
        	this.isOffset = true;
        	return value;
        }
		if (Ext.isNumeric(value)) {						// If the field contain a number -> value = today+number  or today (if number = 0)
			this.isOffset = true;
			if (value == '0') {				
				return 'today';
			} else {
				return 'today'+(value>0?'+':'')+value;
			}
		}	
		var offset = value.split('today')[1];			// if the field contain today+number do nothing except if this is today+0 -> return today only
		if (offset && Ext.isNumeric(offset)) {
			this.isOffset = true;
			if (offset == '') {
				return 'today';
			} else {
				return value;
			}
		}		
		
		this.isOffset = false;
		
        var me = this,
            val = me.safeParse(value, me.format),
            altFormats = me.altFormats,
            altFormatsArray = me.altFormatsArray,
            i = 0,
            len;

        if (!val && altFormats) {
            altFormatsArray = altFormatsArray || altFormats.split('|');
            len = altFormatsArray.length;
            for (; i < len && !val; ++i) {
                val = me.safeParse(value, altFormatsArray[i]);
            }
        }
        return val;
    }  
    
    /**
     * Runs all of Date's validations and returns an array of any errors. Note that this first
     * runs Text's validations, so the returned array is an amalgamation of all field errors.
     * The additional validation checks are testing that the date format is valid, that the chosen
     * date is within the min and max date constraints set, that the date chosen is not in the disabledDates
     * regex and that the day chosed is not one of the disabledDays.
     * @param {Mixed} value The value to get errors for (defaults to the current field value)
     * @return {Array} All validation errors for this field
     */
    ,getErrors: function(value) {
        var me = this,
            format = Ext.String.format,
            clearTime = Ext.Date.clearTime,
            errors = [], //me.callParent(arguments),
            disabledDays = me.disabledDays,
            disabledDatesRE = me.disabledDatesRE,
            minValue = me.minValue,
            maxValue = me.maxValue,
            len = disabledDays ? disabledDays.length : 0,
            i = 0,
            svalue,
            fvalue,
            day,
            time;

        value = me.formatDate(value || me.processRawValue(me.getRawValue()));

        if (value === null || value.length < 1) { // if it's blank and textfield didn't flag it then it's valid
             return errors;
        }

        svalue = value;
        value = me.parseDate(value);
        if (!value) {
            errors.push(format(me.invalidText, svalue, me.format));
            return errors;
        }

		// If this is an offset don't check format
		if (me.isOffset) {
			return errors;
		}
		
        time = value.getTime();
        if (minValue && time < clearTime(minValue).getTime()) {
            errors.push(format(me.minText, me.formatDate(minValue)));
        }

        if (maxValue && time > clearTime(maxValue).getTime()) {
            errors.push(format(me.maxText, me.formatDate(maxValue)));
        }

        if (disabledDays) {
            day = value.getDay();

            for(; i < len; i++) {
                if (day === disabledDays[i]) {
                    errors.push(me.disabledDaysText);
                    break;
                }
            }
        }

        fvalue = me.formatDate(value);
        if (disabledDatesRE && disabledDatesRE.test(fvalue)) {
            errors.push(format(me.disabledDatesText, fvalue));
        }

        return errors;
    }      
});

