<?php
/*
 * 08/07/2011 MMT bz 21896: gestion du corporate lors de la migration, ajout du paramètre newNaAgName
 */

include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once dirname(__FILE__).'/../class/ChangeNaByAnotherNaInDatabaseStruture.class.php';

if ( $argc < 3 )
{
	//08/07/2011 MMT bz 21896 ajout du paramètre newNaAgName
	echo "\nERROR : No argument or missing arguments !";
	echo "\nUsage : ".$argv[0]."  family oldNa newNa [ newNaLabel [ fileLog [ formatLog [newNaAgName]]]]";
	echo "\n\t- family : name family";
	echo "\n\t- oldNa : old network aggregation code ('agregation' column)";
	echo "\n\t- newNa : new network aggregation code ('agregation' column)";
	echo "\n\t- newNaLabel : new network aggregation label (default old network aggregation label)";
	echo "\n\t- fileLog : path to log file (default not log)";
	echo "\n\t- formatLog : output format log html or text (default html)";
	echo "\n\t- newNaAgName : new network aggregation ('agregation_name' column) default newNaLabel if provided, or previous one if label omitted";
	echo "\n";
	exit;
}

try
{
	$changeNa = new ChangeNaByAnotherNaInDatabaseStruture();
	
	$changeNa->setFamily($argv[1]);
	$changeNa->setOldNa($argv[2]);
	if ( isset($argv[4]) )
	{
		//08/07/2011 MMT bz 21896 ajout du paramètre newNaAgName
		// affecte la valeure du label par defaut (pour etre compatible IU 5.0.5)
		if ( isset($argv[7]) ){
			$changeNa->setNewNa($argv[3], $argv[4],$argv[7]);
		} else {
			$changeNa->setNewNa($argv[3], $argv[4],$argv[4]);
		}
	}
	else
	{
		$changeNa->setNewNa($argv[3]);
	}
	
	if ( isset($argv[5]) )
	{
		if ( isset($argv[6]) )
		{
			$changeNa->setFileLog($argv[5], $argv[6]);
		}
		else
		{
			$changeNa->setFileLog($argv[5]);
		}
	}
	
	$changeNa->applyChange();
}
catch ( Exception $e )
{
	echo $e->getMessage();
}
?>