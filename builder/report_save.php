<?php
/**
*	@cb4100@
*	- Creation SLC	 12/11/2008
*
*	Cette page sauvegarde, ou crée, un rapport
*
*	02/02/2009 GHX
*		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
*		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');


// on recupère les données passées au script
// 10:46 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];


if ($id_page != '0' && !empty($id_page)) {
	// UPDATE du rapport
	
	// on verifie qu'on a les droits d'écriture dessus
	$query = " --- on va chercher le rapport
		SELECT * FROM sys_pauto_page_name WHERE id_page='$id_page'
	";
	$report = $db->getrow($query);
	if (!allow_write($report)) {
		echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_REPORT');
		exit;
	}

	// on update le page name
	$query = " --- on update le page_name du raport
		update sys_pauto_page_name
		set page_name='{$_POST['page_name']}'
		where id_page='$id_page'
		";
	$db->execute($query);
	
	
} else {
	// INSERT nouveau rapport
	
	// on va chercher le prochain id_page
	// 10:46 30/01/2009 GHX
	// Nouveau format pour l'id_page, ce n'est plus un MAX+1
	$id_page = generateUniqId('sys_pauto_page_name');
	
	// ... dans sys_pauto_page_name
	if (($client_type == 'client') && ($user_info['profile_type'] == 'user')) {
		$temp_id_user = "'".$user_info['id_user']."'";
	} else {
		$temp_id_user = 'NULL';
	}
	$query = " --- ajoute le rapport dans sppn
		insert into sys_pauto_page_name (id_page,page_name,droit,page_type,id_user,share_it)
		values ('$id_page','{$_POST['page_name']}','$client_type','report',$temp_id_user,0)";
	$db->execute($query);
	
}

// on renvoie vers la page d'edition du GTM
header("Location: {$niveau0}builder/report.php?id_page=$id_page");
exit;

// debug
echo "<link rel='stylesheet' href='common.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/report.php?id_page=$id_page'>{$niveau0}builder/report.php?id_page=$id_page</a>";

?>
