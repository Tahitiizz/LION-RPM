<?php
/**
 * Class Step
 * Allows to manipulate a process step
 * 05/02/2013 BBX
 * DE Optims T&A
 */
class Step
{
    /**
     * ID of step
     * @var integer 
     */
    protected $_stepId          = null;
    
    /**
     * ID of product
     * @var integer 
     */
    protected $_productId       = 0;
    
    /**
     * Stores database connection instance
     * @var DatabaseConnection objet 
     */
    protected $_database        = null;
    
    /**
     * Stores step's properties
     * @var array 
     */
    protected $_stepValues      = array();
    
    /**
     * Current timestamp
     * @var integer 
     */
    protected $_currentTime     = 0;
    
    /**
     * Path to HTML log file
     * @var string 
     */
    protected $_htmlLog         = '';
    
    /**
     * Constructor
     * @param integer $familyId
     * @param integer $productId
     */
    public function __construct($stepId, $productId = 0)
    {
        // Database, time and log        
        $this->_productId   = (int)$productId;
        $this->_database    = Database::getConnection($this->_productId);
        $this->_currentTime = time();
        // Todo : reporter les vérifs de droits d'écriture sur ce fichier
        $this->_htmlLog     = REP_PHYSIQUE_NIVEAU_0.'file_demon/demon_'.date('Ymd', $this->_currentTime).'.html';
        
        // Saving id
        $this->_stepId = (int)$stepId;
        
        // Fetching master information
        $queryStep = "SELECT * FROM sys_definition_step
            WHERE step_id = ".$this->_stepId;
        $result = $this->_database->execute($queryStep);
        $this->_stepValues = $this->_database->getQueryResults($result,1);
    }
    
    /**
     * Returns a value of this master
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        return $this->_stepValues[$key];
    }
    
    /**
     * Returns master's id
     * @return integer
     */
    public function getId()
    {
        return $this->_stepId;
    }
    
    /**
     * Returns true is step is running
     * @return boolean
     */
    public function isRunning()
    {
        $queryRunning = "SELECT step_id 
            FROM sys_step_track
            WHERE step_id = '{$this->_stepId}'
            AND encours = true
            AND done = false";
        $this->_database->execute($queryRunning);
        return ($this->_database->getNumRows() > 0);
    }
    
    /**
     * Returns true is step is complete
     * @return boolean
     */
    public function isComplete()
    {
        $queryComplete = "SELECT step_id 
            FROM sys_step_track
            WHERE step_id = '{$this->_stepId}'
            AND encours = false
            AND done = true";
        $this->_database->execute($queryComplete);
        return ($this->_database->getNumRows() > 0);
    }
    
    /**
     * Returns step's pid
     * @return integer
     */
    public function getPid()
    {
        $queryPid = "SELECT family_id as pid
            FROM sys_step_track
            WHERE step_id = '{$this->_stepId}'
            AND encours = true
            AND done = false";
        return (int)$this->_database->getOne($queryPid);
    }
    
    /**
     * Launches the step
     */
    public function launch()
    {
        // Module (iu, gsm, def...)
        $module     = get_sys_global_parameters('module', 'def', $this->_productId);
        $script     = REP_PHYSIQUE_NIVEAU_0 . $this->getValue('script');
        // This will replace "$module" with its value
        eval( "\$script = \"$script\";" );
        
        // Launcher script
        $launcher   = REP_PHYSIQUE_NIVEAU_0 . 'scripts/launch.php';
        // Command to execute
        $command    = 'php '.$launcher.' "'.$script.'" >> '.$this->_htmlLog.' 2>&1 & echo $!';
        
        // Launching process
        exec($command, $op);
        $pid = (int)$op[0];

        // Logging
        $this->htmlOutput("Début du script {$script} (pid $pid) Time stamp : ".date('r'), $this->_htmlLog);
        
        // ASTUCE DEV : On se sert de family_id pour stocker le pid
        $queryLaunch = "INSERT INTO sys_step_track
           (step_id,family_id,master_id,step_order,encours,done,date)
           VALUES ('{$this->_stepId}', '{$pid}', NULL, {$this->getValue('ordre')}, true, false, {$this->_currentTime})";
        $this->_database->execute($queryLaunch);
    }
    
    /**
     * Checks if the step is over
     */
    public function checkStatus()
    {
        $isProcessRunning = exec('ps -p '.$this->getPid().' 2>/dev/null | wc -l');
        if((int)$isProcessRunning == 1) {
            $queryComplete = "UPDATE sys_step_track
                SET encours = false, done = true
                WHERE step_id = '{$this->_stepId}' AND family_id = '".$this->getPid()."'";
            $this->_database->execute($queryComplete);
        }
    }
    
    /**
     * Stopps the step
     */
    public function stop()
    {
        $queryStop = "DELETE FROM sys_step_track 
            WHERE step_id = '{$this->_stepId}'";
        $this->_database->execute($queryStop);
        // Cleaning data
        $queryAnalyze = "VACUUM ANALYZE sys_step_track";
        $this->_database->execute($queryAnalyze);
    }
    
    /**
     * Display a message in HTML format
     * @param string $text
     * @param string $fileName
     */
    protected function htmlOutput($text, $fileName = '')
    {
        $html =  "<font color=blue ><b>";
        $html .= "<li>{$text}</li>";
        $html .= "</b></font><br />";
        if(empty($fileName)) echo $html;
        else file_put_contents($fileName, $html, FILE_APPEND);
    }
}
?>