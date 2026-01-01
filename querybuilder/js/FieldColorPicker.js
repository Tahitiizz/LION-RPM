Ext.define('Ext.ux.querybuilder.FieldColorPicker', {
    extend:'Ext.form.field.Picker',
    alias: 'widget.FieldColorPicker',
    requires: ['Ext.picker.Color'],
    
  
    initComponent : function(){
        var me = this
        me.callParent();
    },

    initValue: function() {
        var me = this;
        me.callParent();
    },

    createPicker: function() {
        var me = this;

        return Ext.create('Ext.picker.Color', {
            ownerCt: me.ownerCt,
            renderTo: document.body,
            floating: true,
            hidden: true,
            focusOnShow: true,
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
    },

    onSelect: function(m, d) {
        var me = this;
        
        me.setValue(d);
        me.fireEvent('select', me, d);
        me.collapse();
    },

    /**
     * @private
     * Sets the Date picker's value to match the current field value when expanding.
     */
    onExpand: function() {
        var me = this,
            value = me.getValue();
    },

    /**
     * @private
     * Focuses the field when collapsing the Date picker.
     */
    onCollapse: function() {
        this.focus(false, 60);
    },

    // private
    beforeBlur : function(){ 
    }

});

