/*
 * 28/07/2011 SPD1: Querybuilder V2 - Extend tree store to fix ExtJS bug (by defaulty the tree flickers during refresh)  
 */

Ext.define('Ext.ux.querybuilder.QueriesStore', {
	extend: 'Ext.data.TreeStore',
    
	onProxyLoad: function(operation) {
        var me = this,
            successful = operation.wasSuccessful(),
            records = operation.getRecords(),
            node = operation.node;

        // remove old items at this step avoid items flickers	
		me.tree.getRootNode().removeAll();

        node.set('loading', false);
        if (successful) {
            records = me.fillNode(node, records);
        }
                
        me.fireEvent('read', me, operation.node, records, successful);
        me.fireEvent('load', me, operation.node, records, successful);
        //this is a callback that would have been passed to the 'read' function and is optional
        Ext.callback(operation.callback, operation.scope || me, [records, operation, successful]);
    }
});