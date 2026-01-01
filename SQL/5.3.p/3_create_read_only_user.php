<?php
/*
 * Create a read only postgres user
 * @created SPD1 on 15/12/2011
 */

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."class/DataBaseConnection.class.php");
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextActivation.class.php';

// Postgres read-only user name
$readOnlyUser = "read_only_user";

// Database connection
$database = DataBase::getConnection();

// Check if the role already exists
$sql = "SELECT rolname FROM pg_roles WHERE rolname = '$readOnlyUser'";
if (!$database->getOne($sql)) {
	// Create read-only user and give rights to execute dblink method
	$sql = "CREATE ROLE $readOnlyUser NOSUPERUSER LOGIN password '$readOnlyUser'";
	$database->execute($sql);
}
	
$sql = "GRANT EXECUTE ON FUNCTION dblink_connect_u(text) TO read_only_user";
$database->execute($sql);
	
$sql = "GRANT EXECUTE ON FUNCTION dblink_connect_u(text, text) TO read_only_user";		
$database->execute($sql);	


// Grant select on each table of the database 
$sql = "SELECT 'GRANT SELECT ON ' ||TABLE_SCHEMA||'.'||TABLE_NAME||' TO $readOnlyUser;' as sql FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'public'";
$rows = $database->getAll($sql);
foreach ($rows as $grantQuery) {	
	$database->execute($grantQuery['sql']);
}
	
// close db connection
$database->close();

?>
