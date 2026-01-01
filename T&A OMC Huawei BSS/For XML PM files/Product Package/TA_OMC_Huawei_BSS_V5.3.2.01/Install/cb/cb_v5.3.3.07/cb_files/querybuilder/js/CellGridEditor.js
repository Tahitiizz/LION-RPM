/*
 * 28/07/2011 SPD1: Querybuilder V2 - Class to manage grid cell edition  
 */

Ext.define('Ext.ux.querybuilder.CellGridEditor', {
    extend: 'Ext.grid.plugin.CellEditing',

	getEditor: function(record, column) {
    	var me = this,
            editors = me.editors,
            editorId = column.getItemId(),
            editor = editors.getByKey(editorId);

        if (editor) {
            return editor;
        } else {
            editor = column.getEditor(record);
                        
            if (!editor) {
                return false;
            }
            // Allow them to specify a CellEditor in the Column
            if (!(editor instanceof Ext.grid.CellEditor)) {
                editor = Ext.create('Ext.grid.CellEditor', {
                    editorId: editorId,
                    field: editor
                });
            }
            
            editor.parentEl = me.grid.getEditorParent();
            // editor.parentEl should be set here.
            editor.on({
                scope: me,
                specialkey: me.onSpecialKey,
                complete: me.onEditComplete,
                canceledit: me.cancelEdit
            });
            
            // Do not save editor for this column ...used for the value column, editor may be different for each rows (text field, date, hour ...)
            if (!column.doNotSaveEditor) {
            	editors.add(editor);
            } else {
            	me.editorToDelete = editor;		// After edit complete, remove this editor from the dom
            }
            return editor;
        }
    }
    
    /* on edit completed */    
    ,onEditComplete: function(column, oldValue, newValue) {    	    	    	
    	// call the superclass's constructor  
        var ret = this.callParent(arguments);
         
        if (this.editorToDelete) {
        	this.editorToDelete.destroy();
        	delete this.editorToDelete;
        }        
        
        // refresh row color       
        this.grid.getView().refreshRowColor();
             
        // Set the hasChanged flag if the value has been modified
        if (oldValue !== newValue) {
        	Ext.getCmp('qbFilterGridPanel').app.currentQuery.system.hasChanged = true;
        }
		
        return ret;
    }
    
 });

