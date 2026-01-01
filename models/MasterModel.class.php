<?php
/**
*	Classe permettant de manipuler les process
*
*	@author	BBX - 05/07/2010
*	@version	CB 5.1.0.0
*	@since	CB 5.1.0.0
*
* 11:44 13/10/2010 SCT : ajout de la méthode "getTimeToCompute" qui retourne les heures, jours, ... à computer
*/
class MasterModel
{
    /**
     * Retourne le dernier process terminé en toutes lettres
     * @param integer $pId Id du produit
     * @return string $lastProcess
     */
    public static function getLastCompleteProcess($pId = 0)
    {
        $database = Database::getConnection($pId);
        $query = "SELECT master_name FROM sys_definition_master
        WHERE master_id =
        (
                SELECT process FROM sys_process_encours
                WHERE done = 1
                ORDER BY date DESC
                LIMIT 1
        )::int";
        return trim($database->getOne($query));
    }

    /**
     * Retourne le dernier retrieve terminé
     * @param integer $pId Id du produit
     * @return array $lastRetrieve
     */
    public static function getLastCompleteRetrieve($pId = 0)
    {
        $database = Database::getConnection($pId);
        $query = "SELECT * FROM sys_process_encours
        WHERE done = 1
        AND process::int = 10
        ORDER BY date DESC
        LIMIT 1";
        return $database->getRow($query);
    }

    /**
     * Retourne le dernier compute terminé
     * @param integer $pId Id du produit
     * @return array $lastCompute
     */
    public static function getLastCompleteCompute($pId = 0)
    {
        $database = Database::getConnection($pId);
        $query = "SELECT * FROM sys_process_encours
        WHERE done = 1
        AND process::int IN (11,4,13)
        ORDER BY date DESC
        LIMIT 1";
        return $database->getRow($query);
    }

    /**
     * Retourne les proces cochés
     * @param integer $pId Id du produit
     * @return array $checkedProcesses
     */
    public static function getCheckedProcesses($pId = 0)
    {
        $checkedProcesses = array();
        $database = Database::getConnection($pId);
        $query = "SELECT master_id FROM sys_definition_master
        WHERE on_off = 1";
        $result = $database->execute($query);
        while($row = $database->getQueryResults($result,1)) {
            $checkedProcesses[] = $row['master_id'];
        }
        return $checkedProcesses;
    }

    /**
     * Retourne les processus qui tournent
     * @param integer $pId Id du produit
     * @return array $runningProcesses
     */
    public static function getRunningProcesses($pId = 0)
    {
        $runningProcesses = array();
        $database = Database::getConnection($pId);
        $query = "SELECT process FROM sys_process_encours
        WHERE encours = 1";
        $result = $database->execute($query);
        while($row = $database->getQueryResults($result,1)) {
            $runningProcesses[] = $row['process'];
        }
        return $runningProcesses;
    }

    /**
     * Retourne les éléments à computer
	 * 11:44 13/10/2010 SCT : ajout de la méthode "getTimeToCompute" qui retourne les heures, jours, ... à computer
     * @param integer $pId Id du produit
     * @return array $dateToCompute
     */
    public static function getTimeToCompute($pId = 0)
    {
        $dateToCompute = array();
		$compteurBoucle = 0;
        $database = Database::getConnection($pId);
        $query = 'SELECT CASE WHEN time_type = \'day\' THEN "day" ELSE "hour" END AS "time_to_compute", time_type FROM sys_to_compute';
        $result = $database->execute($query);
        while($row = $database->getQueryResults($result,1))
		{
            $dateToCompute[$compteurBoucle]['timeToCompute'] = $row['time_to_compute'];
			$dateToCompute[$compteurBoucle]['timeType'] = $row['time_type'];
			$compteurBoucle++;
        }
        return $dateToCompute;
    }
}
?>