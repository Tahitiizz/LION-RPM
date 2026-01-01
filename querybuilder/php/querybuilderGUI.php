<?php
/*
 * 28/07/2011 SPD1: Querybuilder V2 - GUI main page  
 */

session_start();
include_once dirname(__FILE__)."/../../intranet_top.php";
session_commit();
?>

</div>
<div id="taHeader">
<link rel="stylesheet" type="text/css" href="../../js/ext4/resources/css/ext-all-gray.css">
<link rel="stylesheet" type="text/css" href="../resources/css/QueryBuilder-sprite.css" />

<?php
	// Load builded or debug js files ?	
	include (get_sys_debug('querybuilder') == '1'?'mode_debug.inc.php':'mode_build.inc.php');
?>

<script type="text/javascript">

	// Move header and menu_container in the ta Header panel (use for fullscreen mode)
	Ext.get('taHeader').appendChild(Ext.get('header'))
	Ext.get('taHeader').appendChild(Ext.get('menu_container'))

	// Config for finding js files
	Ext.Loader.setPath({
		'Ext.ux.querybuilder': '../js',
		'Ext.ux': '../js'
	});	
	
	// Load require files
	Ext.require('Ext.ux.message');
	Ext.require('Ext.ux.querybuilder.locale');	
	
    Ext.require('Ext.ux.querybuilder.App');
	Ext.require('Ext.state.CookieProvider');
	        
    // When ready
    Ext.onReady(function () {    
    
    	// --------- FIX EXT JS 4.0.7 (FIX COMBO POSITION FOR IE) -------------
    	Ext.override(Ext.form.field.ComboBox, {
		    setHiddenValue: function(values){
		        var me = this, i;
		        if (!me.hiddenDataEl) {
		            return;
		        }
		        values = Ext.Array.from(values);
		        var dom = me.hiddenDataEl.dom,
		            childNodes = dom.childNodes,
		            input = childNodes[0],
		            valueCount = values.length,
		            childrenCount = childNodes.length;
		 
		        if (!input && valueCount > 0) {
		            me.hiddenDataEl.update(Ext.DomHelper.markup({tag:'input', type:'hidden', name:me.name + "-hidden"}));
		            childrenCount = 1;
		            input = dom.firstChild;
		        }
		        while (childrenCount > valueCount) {
		            dom.removeChild(childNodes[0]);
		            -- childrenCount;
		        }
		        while (childrenCount < valueCount) {
		            dom.appendChild(input.cloneNode(true));
		            ++ childrenCount;
		        }   
		        for (i = 0; i < valueCount; i++) {
		            childNodes[i].value = values[i];
		        }
		    }
		});
		// ---- FIX LOADER ----------
		 Ext.override(Ext.LoadMask, {
	        onHide: function() {
            	this.callParent();
        	}
    	});
    	// ---- END FIX -------------
    	
	    // Compute querybuilder path
	    var urlPath = window.location.pathname;    
	    var cookiePath = urlPath.substring(0, urlPath.lastIndexOf('/php/'));

		// Set stateful propertie by default to false (to avoid ExtJS create a lot of cookie to save state components)
		Ext.override(Ext.Component,{stateful:false});
           
    	// create the cookie provider, set path and expires parameters
	    var cp = new Ext.state.CookieProvider({
	       path: cookiePath,										   	// cookies valid only in querybuilder path
	       expires: new Date(new Date().getTime()+(1000*60*60*24*61)) 	//61 days	       
	   	});
	   	
	   	// Ajax queries timeout
	   	Ext.Ajax.timeout = 60*60000; // 1 heure
	   	
    	// State manager (to save UI settings in a cookie)
		Ext.state.Manager.setProvider(cp);
				
    	// Create querybuilder app
        var qb = new Ext.ux.querybuilder.App();
    });  
      
</script>

</body>
</html>