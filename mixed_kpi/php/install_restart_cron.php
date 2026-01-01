<?php
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once dirname(__FILE__).'/../mixed_kpi/class/CreateMixedKpi.class.php';

if ( file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt') ) exit;

function writeInDemon($buffer)
{
	file_put_contents(REP_PHYSIQUE_NIVEAU_0.'file_demon/demon_'.date('Ymd').'.html', $buffer, FILE_APPEND);
}
ob_start("writeInDemon");


file_put_contents(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt', '0');
exec('chmod 777 '.REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt');


$createMK = new CreateMixedKpi();
displayInDemon('Stop process on master');
$createMK->stopProcessMaster();

// Création de la base de données du produit Mixed KPI
if ( $createMK->createDatabase() )
{
	displayInDemon('Create directory "mixed_kpi_product" and copy files');
	$createMK->createDirectory();

	displayInDemon('Product configuration');
	$createMK->configure();
	
	displayInDemon('Restart cron');
	if ( file_exists('/etc/debian_version') )
	{
		// Pour debian
		exec('/etc/init.d/cron restart');
	}
	else
	{
		// Pour redhat
		exec('/etc/init.d/crond restart');
	}
}
else
{
	displayInDemon('Activation failed : impossible to create the database', 'alert');
	file_put_contents(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt', '2');
	exec('chmod 777 '.REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt');
}

displayInDemon('Restart process on master');
$createMK->restartProcessMaster();

file_put_contents(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt', '1');
exec('chmod 777 '.REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt');

rename(REP_PHYSIQUE_NIVEAU_0.'scripts/kill_qpid.php.save', REP_PHYSIQUE_NIVEAU_0.'scripts/kill_qpid.php');
?>