<?php
/*
	13/08/2008 GHX : ajout de tous les droits sur le démons
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
    28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 *  29/12/2010 BBX :
*           - BZ 19741 : ajout d'un tracelog si process bloqué par dump_processing
*           - Maintenance de routine : utilisation de REP_PHYSIQUE_NIVEAU_0, correction des tabulations
	03/10/2012 ACS BZ 29105 display of warning during backup processus
	22/05/2013 : T&A Optimizations
 */
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
$filename = REP_PHYSIQUE_NIVEAU_0.'png_file/dump_processing.txt';
// si le fichier existe c'est qu'un process de dump est encours
$time=date('r');

$array_time=explode(" ", microtime());
$start=$array_time[1];


if (!file_exists($filename))
{
    // lancement des master
    $date = date("Ymd");
    
    // 06/12/2012 BBX
    // BZ 30834 : testing exitence of each demon files before testing
    foreach ( array("demon_$date.html","steps_$date.html") as $demon )
    {
        // 03/12/2012 BBX
        // BZ 30834 : testing file existence before trying to change it
        // and fixing chmod instruction (0777 instead of 777)
        if(file_exists(REP_PHYSIQUE_NIVEAU_0."file_demon/$demon"))
        {    
            // 16/11/2012 BBX
            // BZ 30310 : le fichier démon doit appartenir à Astellia
            chown(REP_PHYSIQUE_NIVEAU_0."file_demon/$demon", "astellia");        
            chmod(REP_PHYSIQUE_NIVEAU_0."file_demon/$demon", 0777);        
            // On informe les utilisateur si les fichiers ne sont toujours pas accessibles en écriture
            if(!is_writable(REP_PHYSIQUE_NIVEAU_0."file_demon/$demon")) {
                @unlink(REP_PHYSIQUE_NIVEAU_0."file_demon/$demon");
                sys_log_ast('Warning', 'Trending&Aggregation', 'Process check', "HTML log $demon is not writeable. It will be reset to prevent processes to get stuck.", "support_1");
            }
        }
    }
    
    // 06/12/2012 BBX
    // BZ 30834 : testing file existence before trying to change it
    // and fixing chmod instruction (0777 instead of 777)
    if(file_exists(REP_PHYSIQUE_NIVEAU_0."file_demon/steps_$date.html"))
    { 
        // 16/11/2012 BBX
        // BZ 30310 : le fichier démon doit appartenir à Astellia
        chown(REP_PHYSIQUE_NIVEAU_0."file_demon/steps_$date.html", "astellia");
        chmod(REP_PHYSIQUE_NIVEAU_0."file_demon/steps_$date.html", 0777);
        // On informe les utilisateur si les fichiers ne sont toujours pas accessibles en écriture
        if(!is_writable(REP_PHYSIQUE_NIVEAU_0."file_demon/steps_$date.html")) {
            @unlink(REP_PHYSIQUE_NIVEAU_0."file_demon/steps_$date.html");
            sys_log_ast('Warning', 'Trending&Aggregation', 'Process check', "HTML log steps_$date.html is not writeable. It will be reset to prevent processes to get stuck.", "support_1");
        }
    }

    // Dans le cas d'un Produit Blanc, uniquement le script demon_php est à exécuter.
    // Il n'est utlisé que pour l'installation du MixedKPI
    $idProduct = ProductModel::getProductFromDatabase( $DBName, $DBHost );
    if( ProductModel::isBlankProduct( $idProduct ) )
    {
        exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/demon_php.php >> ".REP_PHYSIQUE_NIVEAU_0."file_demon/demon_$date.html &");
        exit();
    }
    
    //22/03/13 - FRR1 - CB 5.3.1 Performance optimization
    if (!file_exists(REP_PHYSIQUE_NIVEAU_0.'scripts/process.php'))
    {
        sleep(3);
         // lancement des steps dans sys_crontab
         for($i = 0; $i < 4; $i++)
           {
                $array_time=explode(" ", microtime());
                $stop=$array_time[1];
                $duree=ceil($stop-$start);

                if ($duree < 45) //pour ne pas dépasser la minute
                {
                   exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/edw_master.php >> ".REP_PHYSIQUE_NIVEAU_0."file_demon/demon_$date.html");
                   exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/edw_family.php >> ".REP_PHYSIQUE_NIVEAU_0."file_demon/family_$date.html");

                   for ($j = 0;$j <3;$j++)
                         {
                          // lancement des steps dans sys_crontab
                          exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/edw_steps.php >> ".REP_PHYSIQUE_NIVEAU_0."file_demon/steps_$date.html &");
                          sleep(2);
                          exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/demon_php.php >> ".REP_PHYSIQUE_NIVEAU_0."file_demon/demon_$date.html &");
                          sleep(1);
                         }
                 }
          }
    }
    else
    {
        //22/03/13 - FRR1 - CB 5.3.1 Performance optimization

        //Launch in crontab
        $allMasterToLaunch = getAllMasterToLaunchInCrontab($idProduct);
        for($cpt=0 ; $cpt < count($allMasterToLaunch) ; $cpt++)
        {                      
            $masterToLaunch = $allMasterToLaunch[$cpt];
            $allRunningMaster = getAllRunningMaster($idProduct);
            if(count($allRunningMaster) == 0 || isCompatibleWithAll($masterToLaunch, $allRunningMaster, $idProduct))
                //Bug 34084 - [REC][CB 5.3.1.01][Installation] a strange demon file is appeared after upgrading CB to 5.3.1.01
				//launchProcess($masterToLaunch);
				launchProcess($masterToLaunch, $date);
        }
             
        //Launch auto
        while(count($masterToLaunch = getOneMasterToLaunchAuto($idProduct)) > 0)
        {
            $allRunningMaster = getAllRunningMaster($idProduct);
            if(count($allRunningMaster) == 0 || isCompatibleWithAll($masterToLaunch, $allRunningMaster, $idProduct))
                //Bug 34084 - [REC][CB 5.3.1.01][Installation] a strange demon file is appeared after upgrading CB to 5.3.1.01
				//launchProcess($masterToLaunch);
				launchProcess($masterToLaunch, $date);
        }
        
        //Bug 34049 - [REC][CB_5.3.1.01][#TA-56720][Mixed KPI]: Error during the activation of Mixed KPI product
        $result = Database::getConnection($idProduct)->execute("SELECT script FROM sys_crontab");
        while ( $row = Database::getConnection($idProduct)->getQueryResults($result, 1))
        {
			$script = $row['script'];
			Database::getConnection($idProduct)->execute("DELETE FROM sys_crontab WHERE script = '" . $script . "'");
            $cmd = "php " . REP_PHYSIQUE_NIVEAU_0 . $script . " >> ".REP_PHYSIQUE_NIVEAU_0."file_demon/demon_$date.html & echo $!";
            exec($cmd, $op);
			$pid = (int)$op[0];
			file_put_contents( REP_PHYSIQUE_NIVEAU_0 . "file_demon/demon_$date.html", 
								"<font color=blue ><b><li>Début du script " . REP_PHYSIQUE_NIVEAU_0 . $script . " (pid $pid) Time stamp : ".date('r')."</li></b></font><br />", 
								FILE_APPEND);
        }
    }
}
// 29/12/2010 BBX BZ 19741 : On informe les administrateurs de la raison du non lancement des processus
else {
	// 03/10/2012 ACS BZ 29105 display of warning during backup processus
	// Check if one warning has already been displayed during this dump execution
	if (get_sys_global_parameters("lock_dump_warning", 0, $idProduct) != 1) {
	    // Récupération du message
	    $message = __T('A_PROCESS_DUMP_PROCESSING_FILE',$filename);
	
	    // Insertion du message dans le tracelog
	    sys_log_ast('Warning', 'Trending&Aggregation', 'Process check', $message, 'support_1', '');

	    // Add a lock to prevent the display of multiple warning		
		$db = Database::getConnection($idProduct);
	    $db->execute("UPDATE sys_global_parameters SET value = '1' WHERE parameters = 'lock_dump_warning'");
	}
}


/**
 * Returns one master to launch (mode auto)
 */
function getOneMasterToLaunchAuto($idProduct){
    
    $masterToLaunch = array();
    
    $result = Database::getConnection($idProduct)->execute("SELECT master_id, utps FROM sys_definition_master WHERE auto = 't' LIMIT 1");
    while ( $row = Database::getConnection($idProduct)->getQueryResults($result, 1) )
    {
        $masterToLaunch['master_id'] = $row['master_id'];
        $masterToLaunch['utps'] = $row['utps'];
    }
    
    return $masterToLaunch;
}


/**
 * Returns all masters to launch (mode crontab)
 */
function getAllMasterToLaunchInCrontab($idProduct){
    
    $allMasterToLaunch = array();
    
    $currentHour    = date('H', time());  
    $currentMinute  = date('i', time());
    $currentOffset  = ($currentHour * 60) + $currentMinute;
        
    $result = Database::getConnection($idProduct)->execute("SELECT master_id, on_off, offset_time, utps FROM sys_definition_master WHERE on_off != '0'");
    $i=0;
    while ( $row = Database::getConnection($idProduct)->getQueryResults($result, 1) )
    {
        if($row['on_off'] != 0 && 
                    $currentOffset >= $row['offset_time'] &&
                    ($currentOffset % $row['utps']) == $row['offset_time'] )
            { 
                $masterToLaunch = array();
                $masterToLaunch['master_id'] = $row['master_id'];
                $masterToLaunch['on_off'] = $row['on_off'];
                $masterToLaunch['offset_time'] = $row['offset_time'];
                $masterToLaunch['utps'] = $row['utps'];
                $allMasterToLaunch[$i] = $masterToLaunch;
                $i++;
            }
    }
    return $allMasterToLaunch;
}


/**
 * Returns all running masters
 */
function getAllRunningMaster($idProduct){
    
    $allRunningMaster = array();
    $result = Database::getConnection($idProduct)->execute("SELECT process FROM sys_process_encours WHERE encours = 1 AND done = 0");
    $i=0;
    while ( $row = Database::getConnection($idProduct)->getQueryResults($result, 1) )
    {
        $allRunningMaster[$i] = $row['process'];
        $i++;
    }

    return $allRunningMaster;
}


/**
 * Returns true if masterToLaunch is compatible with one running master
 */
function isCompatibleWithOne($masterToLaunch, $runningMaster, $idProduct){

    $result = Database::getConnection($idProduct)->execute("SELECT master_compatible FROM sys_definition_master_compatibility WHERE master_id = '" . $runningMaster . "'");
    while ( $row = Database::getConnection($idProduct)->getQueryResults($result, 1))
    {
        if($masterToLaunch['master_id'] == $row['master_compatible'])
            return true;
    }
    return false;
}


/**
 * Returns true if masterToLaunch is compatible with all running masters
 */
function isCompatibleWithAll($masterToLaunch, $allRunningMaster, $idProduct){

    foreach($allRunningMaster as $runningMaster){
        if(!isCompatibleWithOne($masterToLaunch, $runningMaster, $idProduct))
            return false;
    }
    return true;
}


/**
 * Launch the process
 */
//Bug 34084 - [REC][CB 5.3.1.01][Installation] a strange demon file is appeared after upgrading CB to 5.3.1.01
//function launchProcess($masterToLaunch){
function launchProcess($masterToLaunch, $date){
    
    exec( "php -q ".REP_PHYSIQUE_NIVEAU_0."scripts/process.php " . $masterToLaunch['master_id'] . " >> ".REP_PHYSIQUE_NIVEAU_0."file_demon/demon_$date.html &");
                
    Database::getConnection($idProduct)->execute("UPDATE sys_definition_master SET auto = false WHERE master_id = '{$masterToLaunch['master_id']}'");

    Database::getConnection($idProduct)->execute("INSERT INTO sys_process_encours (process,utps,date,encours,done) VALUES ('{$masterToLaunch['master_id']}', '".$masterToLaunch['utps']."', ".date('YmdHi',time()).", 1, 0);");
}

?>
