<?php
/**
 * Explaination of BZ 30851
 * A lot of client's servers are not patched with last CB version.
 * Only gateway is up-to-date. Thus, read_only_user may not exist
 * on several slaves which could lead to errors on Query Builder.
 * So, it has been decided (CCT1, MMT, DBD) to check all slaves
 * that have a CB < 5.1.6.21 and add the right to read_only_user
 * if missing. This procedure should not slow down the page loading
 * as this treatment is quite fast.
 */

// 04/12/2012 BBX
// BZ 30851 : need some classes
require_once dirname(__FILE__).'/../php/environnement_liens.php';

$menu_encours='';
if (isset($_GET["id_menu_encours"])) {
        $menu_encours = '&id_menu_encours='.$_GET["id_menu_encours"];
}   

// 04/12/2012 BBX
// BZ 30851 : checking slaves rights
$readOnlyUser   = 'read_only_user';
$minCBVersion   = '5.1.6.21';
$logFile        = REP_PHYSIQUE_NIVEAU_0.'file_demon/update_slave_rights.log';
$logContents    = '';
$logTimestamp   = '['.date('Y-m-d H:i:s').']';
foreach(ProductModel::getActiveProducts(true) as $prod)
{
    $productModel = new ProductModel($prod['sdp_id']);
    if($productModel->getCBVersion() < $minCBVersion)
    {
        // Connection to product Database
        $localDB = Database::getConnection($prod['sdp_id']);

        // Checking if our user exists
        $query = "SELECT rolname
        FROM pg_roles 
        WHERE rolname = 'read_only_user'";
        $localDB->execute($query);
        // If not, let's create it
        if($localDB->getNumRows() == 0) {
            $logContents .= "{$logTimestamp} {$prod['sdp_label']} : {$readOnlyUser} does not exist, let's create it\n";
            $localDB->execute("CREATE ROLE $readOnlyUser NOSUPERUSER LOGIN password '$readOnlyUser';");
        }

        // Now listing data tables without rights for our user
        $query = "SELECT c.relname
        FROM pg_class c, pg_tables t
        WHERE c.relname = t.tablename
        AND t.schemaname = 'public'
        AND c.relname !~ '[0-9]+$'
        AND (array_to_string(c.relacl,',') NOT LIKE '%read_only_user%' OR c.relacl IS NULL)
        ORDER BY c.relname";
        $result = $localDB->execute($query);
        // Setting SELECT rights for read_only_user
        while($row = $localDB->getQueryResults($result, 1)) {
            $logContents .= "{$logTimestamp} {$prod['sdp_label']} : Adding {$readOnlyUser} rights on table {$row['relname']}\n";
            $localDB->execute("GRANT SELECT ON public.{$row['relname']} TO {$readOnlyUser};");
        }
    }
}

// 04/12/2012 BBX
// BZ 30851 : logging
if(!empty($logContents)) {
    file_put_contents($logFile, $logContents, FILE_APPEND);
}
//06/10/2014 - FGD - Bug 43759 - [REC][CB 5.3.3.02][TC #TA-56768][IE 11 Compatibility] Missing family's name in Query Builder GUI
//Emulate IE10 for query builder
header('Location: php/querybuilderGUI.php?NoIEmulate=2'.$menu_encours);   
?>