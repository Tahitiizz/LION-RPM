<?php
/*
 * Update slave products if necessary
 * 07/12/2010 MMT
     - bz 19626 detruit elements clients du Slave pour eviter l'ecrasement des
	    changement effectués sur le Master post Mise en multi-produit au prochain montage contexte
    a l'installation du patch, netoye tous les slaves potentiels des ajout contexte clientes
 *  (graph/dash/report/schedule) qui ont deja ete remontés sur le master
 */

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."class/DataBaseConnection.class.php");
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextActivation.class.php';

//create new connection to the current product
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$dbCurrent = Database::getConnection();

// use static ContextActivation functions
$isMaster = ContextActivation::isCurrentProductMasterOrStandAlone($dbCurrent);
// only if we are the master of a multiproduct, delete all customer
if($isMaster)
{
	$slaves = $dbCurrent->getAll("SELECT * FROM sys_definition_product where sdp_master = 0");
	foreach ($slaves as $slave)
	{
		ContextActivation::removeCustomPautoItemsFromSlave($slave['sdp_id']);
	}
} else {
	//if we are in a slave, we delete just the current id
	$slaveId = ContextActivation::getCurrentProductId($dbCurrent);
	ContextActivation::removeCustomPautoItemsFromSlave($slaveId);
}

// 16/12/2010 NSE bz 19745 : on dédoublonne si des users contexte astellia sont en double
$products = $dbCurrent->getAll("SELECT sdp_id FROM sys_definition_product");
foreach ($products as $product)
{
    echo "product : ".$product['sdp_id']."\n";
    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
    $db = Database::getConnection($product['sdp_id']);
        $db->execute("DELETE  FROM users 
WHERE login IN (
  SELECT login 
  FROM 
     users 
  WHERE login IN ('client_admin', 'client_user', 'astellia_user', 'astellia_admin')
  GROUP BY login
  having count(login)>1 
)
AND user_profil NOT IN (
  SELECT id_profile FROM profile 
  WHERE profile_name='UserProfile' 
  OR profile_name='AdminProfile' 
  OR profile_name='Astellia Administrator'
)");
     echo "  Deleted ".$db->getAffectedRows()." items \n";
   $db->close();
}

$dbCurrent->close();


?>
