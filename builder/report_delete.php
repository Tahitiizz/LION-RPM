<?php
/**
*	@cb4100@
*	- Creation SLC	 13/10/2008
*
*	Cette page supprime le report
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
*	10/07/2009 GHX
*		- Suppression du paramétrage des sélecteurs des dashboards
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');

// on recupère les données envoyées au script
// 10:45 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];

// on va chercher le report pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le GTM
	SELECT * FROM sys_pauto_page_name WHERE id_page='$id_page'
";
$report = $db->getrow($query);
if (!allow_write($report)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_DELETE_THAT_REPORT');
	exit;
}

// on verifie que le report n'appartient pas à un schedule
// 22/11/2012 BBX
// BZ 30306 : correction de la requête qui regarde si le rapport est utilisé dans un schedule
$sql = " --- on va chercher les schedules auxquels appartiennent ce rapport
        SELECT * FROM sys_report_schedule WHERE string_to_array(report_id,',') @> ARRAY['$id_page']";
$schedules = $db->getall($sql);
if ($schedules) {
	echo "You cannot delete this report, because it belongs to these schedules : ";
	foreach ($schedules as $sched)
		echo "<br/><a href='../myadmin_setup/intranet/php/affichage/setup_schedule_detail.php?schedule_id={$sched['schedule_id']}'>{$sched['schedule_name']}</a>";
	exit;
}

// >>>>>>>>>
// 09:33 10/07/2009 GHX
// Suppression du paramétrage des sélecteurs des dashboards
$query = "SELECT id_elem,class_object FROM sys_pauto_config WHERE id_page = '$id_page'";
$results = $db->execute($query);
if ( $db->getNumRows() > 0 )
{
	while ( $elem = $db->getQueryResults($results, 1) )
	{
		// Si cet élément est un dashboard, il faut supprimer sa configuration sélecteur
		if($elem['class_object'] == 'page') {
			// On regarde si on a configuré un sélecteur sur ce dashboard
			$idSelecteur = SelecteurModel::getSelecteurId($id_page,$elem['id_elem']);
			if($idSelecteur != 0) {
				// Suppression du sélecteur
				$selecteur = new SelecteurModel($idSelecteur);
				$selecteur->deleteSelecteur();
			}
		}
	}
}
// <<<<<<<<<<

// on supprime les dashboards/alarms du report
$query = " --- we delete the data in sys_pauto_config
	delete from sys_pauto_config where id_page='$id_page'";
$db->execute($query);

// on supprime le report
$query = " --- we delete the GTM in sppn
	delete from sys_pauto_page_name where id_page='$id_page'";
$db->execute($query);



// on renvoie vers le report builder
header("Location: {$niveau0}builder/report.php");
exit;


// debug
echo "<link rel='stylesheet' href='common.css' type='text/css'/>";
echo $db->displayQueries();
echo "<br/><br/><a href='{$niveau0}builder/report.php'>{$niveau0}builder/report.php</a>";

?>
