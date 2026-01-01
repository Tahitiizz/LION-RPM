<?php
include_once dirname(__FILE__).'/../../php/environnement_liens.php';
include_once dirname(__FILE__).'/../class/CreateMixedKpi.class.php';

if ( file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/cronmixedkpi.txt') ) exit;

displayInDemon('initialization activation');
displayInDemon('Create cron');
CreateMixedKpi::createCron();

CreateMixedKpi::installViaCronRoot();
?>