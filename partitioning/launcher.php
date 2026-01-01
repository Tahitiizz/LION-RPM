<?php
/**
 * Will launch partitioning operation on a remote or local product
 */
// CLI mode only
if(isset($_SERVER['HTTP_USER_AGENT'])) {
    echo "CLI mode only\n";
    exit;
}
// At least 2 parameters expected
if($argc < 2) exit;

// Some required scripts
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';

// Parsing parameters
$mode           = empty($argv[1]) ? 'local' : $argv[1];
$app_dir        = empty($argv[2]) ? '/home/' : $argv[2];
$ip_address     = empty($argv[3]) ? '127.0.0.1' : $argv[3];
$ssh_user       = empty($argv[4]) ? 'astellia' : $argv[4];
$ssh_password   = empty($argv[5]) ? 'astellia' : $argv[5];
$ssh_port       = empty($argv[6]) ? '22' : $argv[6];
$email          = empty($argv[7]) ? '' : $argv[7];

// Forking and launching
$pid = pcntl_fork();
if ($pid == -1) {
     die('dupplication problem');
} else if ($pid) {
    // Nothing to do here
} else {
    // Calling partion script
    if($mode == 'local')
    {
        // Local launch
        exec('php /home/'.$app_dir.'/partitioning/partition.php '.$email.' >> /tmp/'.uniqid().'.log');
    }
    else
    {
        // Remote launch
        $ssh = new SSHConnection($ip_address, $ssh_user, $ssh_password, $ssh_port);
        $ssh->exec('php /home/'.$app_dir.'/partitioning/partition.php '.$email.' >> /tmp/'.uniqid().'.log');
    }
    exit;
}
?>
