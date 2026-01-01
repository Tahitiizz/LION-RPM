<?php
/*
 * generates CVS user export file for the astellia portal in order to include all T&A users
 * in the Portal
 *
 * the output is "SUCCESS:<generated file name>"  or "ERROR:<error message>"
 *
 * This script will not be required with Portal Lot 2 and should then be removed
 *
 * 30/08/2011 MMT - DE Portal Lot1 Creation du fichier
 * 06/05/2013 NSE - DE Phone number managed by Portal
 * 
 */
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once( REP_PHYSIQUE_NIVEAU_0 . "class/Database.class.php");

// define the csv file to be generated
$csvFile = REP_PHYSIQUE_NIVEAU_0."png_file/ta_users_export_for_portal.csv";

// run the query on users, exclude default Portal users astellia_admin
// as well as expired or disabled users
$database = Database::getConnection(0);
$query = "SELECT login, username, user_prenom, user_mail, password, phone_number
			FROM users
			WHERE on_off = 1
			AND CURRENT_DATE::text < substring(date_valid::text from 1 for 4)||'-'||substring(date_valid::text from 5 for 2)||'-'||substring(date_valid::text from 7 for 2)
			AND login NOT IN ('astellia_admin')
";
$UsersToExport = $database->getAll($query);
$dbErr = $database->getLastError();

//error treatment
$error = "";
if(!empty($dbErr)){
	$error = "SQL $dbErr";
} else {
	// create CSV file
	// headers are: login; full name; email; password
	$fh = fopen($csvFile, 'w');
	if(!empty($UsersToExport)){
		foreach ($UsersToExport as $user) {

			$csvLine = array();
			$csvLine[] = $user["login"];
			# creating full name = name + firstName
			# many first and last names are identical, in that case do not duplicate
                        // on supprime l'espace si les 2 ne sont pas renseignés
			if ($user["username"] != $user["user_prenom"]) {
				$csvLine[] = $user["username"].(!empty($user["username"])&&!empty($user["user_prenom"])?' ':'').$user["user_prenom"];
			} else {
				$csvLine[] = $user["username"];
			}
			$csvLine[] = $user["user_mail"];
			# decode the password, some are clear, some are encoded base64
			$pwdDecode = base64_decode($user["password"],true);
			if($pwdDecode === false){
				$clearPwd = $user["password"];
			} else {
				$clearPwd = $pwdDecode;
			}
			# must be encoded md5 for portal
			$csvLine[] = md5($clearPwd);
			$csvLine[] = $user["phone_number"];
			// use fputcsv to handle CSV generation, using ; as delimiter and " as enclosure
			if(fputcsv($fh, $csvLine, ';','"') === false){
				$error = "CSV file generation error";
			}
		}
	} else {
		$error = "Users export empty";
	}
	fclose($fh);
}

// output management
if($error){
	echo "ERROR: $error";
} else {
	echo "SUCCESS:$csvFile";
}


?>
