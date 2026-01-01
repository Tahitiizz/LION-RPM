<?php
/*
 * 21/09/2011 SPD1: Page de test pour l'API query data  
 */

//session_start();
?>
<html>
	
<head>
	<title>T&A Query Data API demo's page</title>
	<link rel="stylesheet" type="text/css" href="../../../js/ext4/resources/css/ext-all.css">
	<link rel="stylesheet" type="text/css" href="./resources/demo.css" />
    
    <script type="text/javascript">
        if (typeof JSON === 'undefined') {
            document.write('<sc' + 'ript type="text/javascript" src="js/json2.js"></sc' + 'ript>');
        }
    </script>   
                 
    <script type="text/javascript" src="js/jsl.parser.js"></script>
    <script type="text/javascript" src="js/jsl.format.js"></script>
    <script type="text/javascript" src="js/jsl.interactions.js"></script>
        
	<script type="text/javascript" src="../../../js/ext4/ext-debug.js"></script>
	<script type="text/javascript" src="js/Message.js"></script>

	<script type="text/javascript">
	
		// Config for finding js files
	    Ext.Loader.setPath({
	        'Ext.ux.queryDataDemo': 'js'
	    });	
		
		// Load require files
	    Ext.require('Ext.ux.queryDataDemo.App');	
		        
	    // When ready
	    Ext.onReady(function () {                       	   			
	    	// Create querybuilder app
	        var demo = new Ext.ux.queryDataDemo.App();
	    });  
	      
	</script>
</head>

<body>
</body>

</html>
