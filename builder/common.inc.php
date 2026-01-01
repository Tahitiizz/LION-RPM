<?php
// maj CCT1 01/12/08 fichier environnement_donnees.php supprimé, include mis en commentaire
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*/


/**
*	Tous les fichiers du GTM Builder se doivent d'inclure GTM.inc.php afin de charger l'environnement, et ouvrir la connection à la db
*
*/

session_start();
include_once("../php/environnement_liens.php");
// include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");
// maj CCT1 01/12/08 fichier environnement_donnees.php supprimé, include mis en commentaire
//include_once(REP_PHYSIQUE_NIVEAU_0."php/environnement_donnees.php");
include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$db = Database::getConnection();

$level_labels = array(
	1 => 'Astellia',
	2 => 'Admin',
	3 => 'User',
//	4 => 'My',
);
if ($user_info['profile_type'] != 'admin') $level_labels[4] = 'My';


// check nonce
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if(!isset($_POST['nonce'])) $_POST['nonce'] = null;
if(!isset($_GET['nonce'])) $_GET['nonce'] = null;

$ajax_nonce = $_POST['nonce'];
if (!$ajax_nonce) $ajax_nonce = $_GET['nonce'];
if ($ajax_nonce) {
	if ($_SESSION['nonce'] == $ajax_nonce) {
		// on reçoit deux fois le même nonce --> on a un Refresh sur une requête ajax
		$_SESSION['nonce'] = '';
		header("Location: ".$_SESSION['current_url']);
		exit;
	} else {
		// nouvelle requête ajax --> on stocke le nouveau nonce
		$_SESSION['nonce'] = $ajax_nonce;
	}
}


/**
*	Cette fonction verifie si l'utilisateur en cours a les droits d'écriture sur un GTM ou un Dashboard ou un report
*
*	@param array		$obj est le tableau associatif contenant les informations de l'objet
*	@return bool		retourne true si l'utilisateur peut modifier l'objet, false sinon.
*/
function allow_write($obj) {
	global $client_type,$user_info;
	$allow_write = true;
	$debug = "
		<div style='margin:5px;border:2px solid red;padding:2px;'>
			\$client_type = $client_type<br/>
			\$user_info['id_user'] = {$user_info['id_user']}<br/>
			\$obj['droit'] = {$obj['droit']}<br/>
			\$obj['id_user'] = {$obj['id_user']}
		</div>";
	// echo $debug;
	if ($client_type != 'customisateur') {
		if ($obj["droit"]=='customisateur') {	// les GTM astellia
			$allow_write = false;
		} else {	// les GTM "client"
			if ($user_info['profile_type']!='admin') {
				if ($user_info['id_user'] != $obj['id_user']) {
					$allow_write  = false;
				}
			}
			
		}
	}
	return $allow_write;
}

/**
*	Cette fonction n'a aucune utilité pour le fonctionnement de l'application. Elle permet juste d'enlever toutes les lignes orphelines de graph_data.
*	Je l'ai développée pour supprimer les données incohérentes que j'avais introduites durant mes développements.
*
*	@param void
*	@return void
*/
function clean_graph_data() {
	global $db;
	$query = " --- On va chercher toutes les lignes de graph_data
		select gd.id_data,spc.id_page
		from graph_data as gd
			left join sys_pauto_config as spc on spc.id = gd.id_data";
	$lignes = $db->getall($query);
	echo "<div style='margin:5px;border:2px solid red;padding:2px;'><table>";
	foreach ($lignes as $ligne) {
		echo "<tr><td>{$ligne['id_data']}</td><td>{$ligne['id_page']}</td></tr>";
		if (intval($ligne['id_page'])==0) $db->execute("delete from graph_data where id_data=".$ligne["id_data"]);
	}
	echo "</table></div>";
}
// clean_graph_data();


?>
