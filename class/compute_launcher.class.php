<?php
/**
 * 21/11/2012 BBX
 * Réécriture du script :
 * - BZ 30587 : on doit toujours se baser sur la même date dans toute la classe
 * - Utilisation de la classe de connexion à la BDD
 * - Utilisation de la classe Date
 * 
 * Avis au Merger : prendre le script tel quel lors du premier merge
 */
require_once REP_PHYSIQUE_NIVEAU_0 . "class/Scheduler.php";

/**
 * compute_launcher
 */
class compute_launcher 
{
    protected $_computeType         = "day";
    protected $_offsetDay           = 0;
    protected $_database            = null;
    protected $_dayToCompute        = null;
    protected $_hourToCompute       = null;
    protected $_masterId            = 0;
    protected $_currentTime         = 0;
    protected $_currentDay          = 0;
    protected $_hourSeparator       = '|s|';
    protected $_computeSwitch       = null;
    protected $_computeSwitchLimit  = null;

    /**
     * Constructor
     */
    public function __construct($type = "day")
    {
        $this->_computeType         = strtolower($type);
        $this->_database            = Database::getConnection();
        $this->_currentTime         = time();
        $this->_currentDay          = date('Ymd', $this->_currentTime);
        $this->_hourSeparator       = get_sys_global_parameters('sep_axe3');
        $this->_computeSwitch       = get_sys_global_parameters('compute_switch');
        $this->_computeSwitchLimit  = get_sys_global_parameters('compute_switch_limit');
        
        $scheduler          = new Scheduler();
        $cannotRun          = true;
        $launchFlag         = 0;
        
        // In case of compute day
        if( $this->_computeType == "day" )
        {
            // Checks if compute process can start with running processes
            if ($scheduler->processusCanRun(new Process(Process::$COMPUTE, "Compute"), false))
            {	
                $this->resetComputeSwitch();
                $this->_masterId = Process::$COMPUTE;
                
                $this->_dayToCompute = compute::getFirstComputableDay($this->_currentDay);
                if(!empty($this->_dayToCompute)) {
                    $this->_offsetDay = Date::getOffsetDayFromDay($this->_dayToCompute);
                    $launchFlag = 1;
                }
                
                $cannotRun = false;
            }
        }
        // In case of compute hour
        elseif ( $this->_computeType == "hour" ) 
        {
            // Checks if compute process can start with running processes
            if ($scheduler->processusCanRun(new Process(Process::$COMPUTE_HOURLY, "Compute hourly"), false))
            {
                $this->resetComputeSwitch();
                $this->_masterId = Process::$COMPUTE_HOURLY;
                
                $hourList = compute::getHoursToCompute();
                if ( !empty ($hourList) )
                {
                    $referenceHour = $hourList[0];
                    $referenceDay  = substr($referenceHour,0,-2);
                    $this->_offsetDay = Date::getDatesDiff($this->_currentDay, $referenceDay);
                    $launchFlag = 1;
                    $this->_hourToCompute   = $referenceHour;
                    $this->_dayToCompute    = $referenceDay;
                    $this->checkHoursForComputeBooster($referenceDay, $hourList);
                }
                
                $cannotRun = false;
            }
        }
        // Not managed
        else {
            displayInDemon( "Compute of type {$this->_computeType} is not implemented yet", 'alert');
        }
        
        // Treatments
        if ($launchFlag) {
            $this->updateDatabase();
            $this->launchCompute();
        } 
        // No date to treat
        else echo "Aucun element trouvé dans la table sys_to_compute<br>\n";
        
        // In case of non-compatible processes
        if ($cannotRun){			
            echo "Un ou plusieurs masters incompatible avec un compute sont en cours:<br>\n";
            foreach($scheduler->getProcessRunningList() as $process)
                echo $process->getName() . " is running<br>\n";		
        }
    }
    
    /**
     * Prepares hours to treat and configure compute booster if necessary
     * @param type $day
     * @param array $hourList
     */
    function checkHoursForComputeBooster($day)
    {
        // List of hours to treat
        $hourList = compute::getHoursToCompute($day);
        
        // Cas 1 :	le nombre d'heures à computer du jour courant est inférieur au seuil d'heures.
        //			On traite toutes les heures en une seule passe.
        if ((count($hourList) <= $this->_computeSwitchLimit) && ($this->_offsetDay == 0)) 
        {
            // On traite les heures du jour courant en une seule passe si le nombre d'heures est > 1. 
            // Sinon, on ne fait rien car c'est le cas "normal"
            if (count($hourList) > 1) 
            {
                echo "<font color='#3399FF'><b>Nombre d'heures du jour courant <= seuil : traitement des heures en une seule passe</b><br></font>";
                $this->_hourToCompute = implode($this->_hourSeparator, $hourList);
                $housListMsg = str_replace($day, '', $hourList);
                sort($housListMsg);

                // Tracelog
                $msg = __T('A_COMPUTE_HOURS_INTEGRATION_SINGLE_PASS',(implode(", ", array_values($housListMsg))), Date::convertAstelliaDayToUsDay($day, '-'));
                $_module = __T('A_TRACELOG_MODULE_LABEL_COMPUTE');
                sys_log_ast("Info", get_sys_global_parameters("system_name"), $_module, $msg, "support_1", "");
            }
        }

        // Cas 2 :	le nombre d'heures à computer est supérieur au seuil d'heures et le jour n'est pas le jour courant.
        //			On switche le compute d'hourly vers daily, on sauvegarde l'ancien mode et l'on stocke la liste d'heures à traiter.
        if ((count($hourList) > $this->_computeSwitchLimit) && ($this->_offsetDay != 0))
        {
            echo "<font color='#3399FF'><b>".$day." n'est pas le jour courant ET le nombre d'heures du jour > seuil : on bascule le compute mode d'hourly en daily</b><br></font>";
            $this->_hourToCompute = implode($this->_hourSeparator, $hourList);
            $housListMsg = str_replace($day, '', $hourList);
            sort($housListMsg);

            // Tracelog
            $msg = __T('A_COMPUTE_SWITCH_COMPUTE_MODE',(implode(", ", array_values($housListMsg))), Date::convertAstelliaDayToUsDay($day, '-'));
            $_module = __T('A_TRACELOG_MODULE_LABEL_COMPUTE');
            sys_log_ast("Info", get_sys_global_parameters("system_name"), $_module, $msg, "support_1", "");
            
            // Preparing Database
            $query = "BEGIN;";
            $query .= "UPDATE sys_global_parameters SET value='hourly' WHERE parameters='compute_switch';";
            $query .= "UPDATE sys_global_parameters SET value='daily' WHERE parameters='compute_mode';";
            $query .= "DELETE FROM sys_to_compute WHERE day=".$day." AND time_type='day';";
            $query .= "COMMIT;";
            $this->_database->execute($query);

            // Switching to compute Day
            $this->_masterId = 4;
        }
    }
    
    /**
     * Resets compute switch parameter
     */
    public function resetComputeSwitch()
    {
        if (trim($this->_computeSwitch) != "") 
        {
            // Restauration de la valeur de 'compute_mode' (valeur de 'compute_switch')
            $query = "UPDATE sys_global_parameters SET value='{$this->_computeSwitch}' 
                WHERE parameters = 'compute_mode'";
            $this->_database->execute($query);

            // RAZ de la valeur de 'compute_switch'
            $query = "UPDATE sys_global_parameters SET value = NULL 
                WHERE parameters = 'compute_switch'";
            $this->_database->execute($query);
        }
    }
    
    /**
     * Launches Compute process
     */
    public function launchCompute()
    {
        $query = "UPDATE sys_definition_master SET auto = 'true' 
            WHERE master_id = {$this->_masterId}";
        $this->_database->execute($query);
        echo "Lancement du master {$this->_masterId}<br>";
    }

    /**
     * Updates compute-related tables to get the compute ready to proceed
     */
    public function updateDatabase()
    {
        // Debug
        echo "<font color='#3399FF'>";
        echo "<li>Updating sys_global_parameters (offset_day = {$this->_offsetDay} | compute_processing = {$this->_computeType})</li>";        
        
        // Updating offset_day
        $query = "UPDATE sys_global_parameters
            SET value = '{$this->_offsetDay}'
            WHERE parameters = 'offset_day';";
        $this->_database->execute($query);
        
        // Updating compute_processing
        $query = "UPDATE sys_global_parameters
            SET value = '{$this->_computeType}'
            WHERE parameters = 'compute_processing';";
        $this->_database->execute($query);
        
        // Debug
        echo "<li>Setting day to compute ({$this->_dayToCompute})</li>"; 

        // Setting day to compute
        $query = "UPDATE sys_global_parameters 
            SET value = '{$this->_dayToCompute}' 
            WHERE parameters = 'day_to_compute'";
        $this->_database->execute($query);
        
        // In case of compute day
        if ( $this->_computeType == "day" )
        {       
            // Debug
            echo "<li>Cleaning sys_to_compute (day = {$this->_dayToCompute})</li>";
        
            // Cleaning sys_to_compute
            $query = "DELETE FROM sys_to_compute 
                WHERE day = {$this->_dayToCompute}
                AND time_type = 'day'";
            $this->_database->execute($query);
        }
        // In case of compute hour
        elseif ( $this->_computeType == "hour" )
        {
            // Debug
            echo "<li>Setting hour list to compute ({$this->_hourToCompute})</li>";
            
            // Setting hours to compute
            $query = "UPDATE sys_global_parameters 
                SET value = '{$this->_hourToCompute}' 
                WHERE parameters = 'hour_to_compute'";
            $this->_database->execute($query);
            
            // Debug
            echo "<li>Cleaning sys_to_compute</li>";
            
            // Cleaning sys_to_compute
            $query = "BEGIN;";
            foreach( explode($this->_hourSeparator, $this->_hourToCompute) as $hour ) {
                $query .= "DELETE FROM sys_to_compute WHERE hour = {$hour} AND time_type = 'hour';";
            }
            $query .= "COMMIT;";
            $this->_database->execute($query);
        }
        echo "</font>";
    }
}
?>
