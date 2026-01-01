<?php
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once dirname(__FILE__).'/../class/ChangeNeByAnotherNeInDatabaseDatas.class.php';
include_once dirname(__FILE__).'/../php/edw_function_family.php';

if ( $argc < 3 )
{
    echo "\nERROR : No argument or missing arguments !";
    echo "\nUsage : ".$argv[0]."  family Na oldNe newNe [ newNeLabel [ fileLog [ formatLog ]]]";
    echo "\n\t- family : name family";
    echo "\n\t- Na : network aggregation name";
    echo "\n\t- oldNe : old network element name";
    echo "\n\t- newNe : new network element name";
    echo "\n\t- newNeLabel : new network element label (default old network element label)";
    echo "\n\t- fileLog : path to log file (default not log)";
    echo "\n\t- formatLog : output format log html or text (default html)";
    echo "\n";
    exit;
}

try
{
    $changeNe = new ChangeNeByAnotherNeInDatabaseDatas();

    $changeNe->setFamily($argv[1]);
    $changeNe->setNa($argv[2]);
    $changeNe->setOldNe($argv[3]);
    if(isset($argv[5])) // dans le cas d'un nouveau label renseigné
        $changeNe->setNewNe($argv[4], $argv[5]);
    else
        $changeNe->setNewNe($argv[4]);

    if(isset($argv[6])) // dans le cas d'un fichier de log renseigné
    {
        if(isset($argv[7])) // format du fichier de log
            $changeNe->setFileLog($argv[6], $argv[7]);
        else
            $changeNe->setFileLog($argv[6]);
    }

    $changeNe->applyChange();
}
catch(Exception $e)
{
    echo $e->getMessage();
}
?>