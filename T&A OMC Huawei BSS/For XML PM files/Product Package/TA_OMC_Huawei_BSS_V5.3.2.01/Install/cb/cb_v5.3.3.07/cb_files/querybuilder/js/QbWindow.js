/*
 * 25/10/2011 SPD1: Querybuilder V2 - custom window  
 */

Ext.define('Ext.ux.querybuilder.QbWindow', {
	extend: 'Ext.window.Window',	
		         
    // --------------------------------------------------------------------------------
    // Force the window position to keep on screen
	// --------------------------------------------------------------------------------
	config: {
	    listeners: {
	    	"beforeshow": function() {
	    		if (this.rendered) {
	    			var pos = this.getPosition();
	    			if (pos[1]<1) {
	    				this.setPosition(pos[0], 0);
	    			}
	    		}
	    	}
	    }
	}	
});