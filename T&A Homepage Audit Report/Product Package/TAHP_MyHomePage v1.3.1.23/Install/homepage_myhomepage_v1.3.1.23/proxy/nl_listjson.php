<?php

session_start();
include dirname( __FILE__ ).'/../../php/environnement_liens.php';
include_once dirname(__FILE__).'/dao/models/FamilyModel.class.php';
include_once dirname(__FILE__).'/dao/querybuilderExtJS4/class/QbFacade.class.php';
include_once dirname(__FILE__).'/dao/querybuilderExtJS4/class/GraphGenerator.class.php';

// Create the facade object
//$facade = new QbFacade();
//
//$productFamilies = call_user_func(array($facade, 'getProductsFamilies'));

echo '[{"checked": true, "expanded": true, "elementId":"1","text":"Astellia EXECUTIVE HomePage","children":[{"checked": true, "expanded": true, "elementId":"1","text":"BSS","children":[{"leaf": true,"checked": true,"elementId":"usercluster3", "text":"UserCellCluster3"},{"leaf": true, "checked": true,"elementId":"bsc", "text":"BSC"},{"leaf": true, "checked": true,"elementId":"lac", "text":"LAC"},{"leaf": true, "checked": true,"elementId":"msc", "text":"MSC"}]},{"checked": false,"elementId":"2","text":"BSS - GPRS","children":[{"leaf": true,"checked": false,"elementId":"cell", "text":"Cell"},{"leaf": true,"checked": false,"elementId":"usercluster1", "text":"UserCellCluster1"},{"leaf": true,"checked": false,"elementId":"usercluster2", "text":"UserCellCluster2"}]}]}]';

?>