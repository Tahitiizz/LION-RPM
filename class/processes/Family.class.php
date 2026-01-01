<?php
/**
 * Class Family
 * Allows to manipulate a family of processes
 * 05/02/2013 BBX
 * DE Optims T&A
 */
class Family
{
    /**
     * ID of family
     * @var integer 
     */
    protected $_familyId        = null;
    
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
     * Stores family's properties
     * @var array 
     */
    protected $_familyValues    = array();
    
    /**
     * Current timestamp
     * @var integer 
     */
    protected $_currentTime     = 0;
    
    /**
     * Stores the steps of family
     * @var SplObjectStorage object 
     */
    protected $_steps           = null;
    
    /**
     * Constructor
     * @param integer $familyId
     * @param integer $productId
     */
    public function __construct($familyId, $productId = 0)
    {
        // Database, time
        $this->_productId   = (int)$productId;
        $this->_database    = Database::getConnection($this->_productId);        
        $this->_currentTime = time();
        
        // Saving id
        $this->_familyId = (int)$familyId;
        
        // Fetching family information
        $queryFam = "SELECT * FROM sys_definition_family 
            WHERE family_id = ".$this->_familyId;
        $result = $this->_database->execute($queryFam);
        $this->_familyValues = $this->_database->getQueryResults($result,1);
        
        // Fetching steps
        $this->_steps = new SplObjectStorage();
        $querySteps = "SELECT step_id FROM sys_definition_step
            WHERE family_id = '{$this->_familyId}'
            AND on_off = 1
            ORDER BY ordre ASC, step_id ASC";
        $result = $this->_database->execute($querySteps);
        while($row = $this->_database->getQueryResults($result,1)) {
            $this->_steps->attach(new Step($row['step_id'], $this->_productId));
        }
    }
    
    /**
     * Returns a value of this master
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        return $this->_familyValues[$key];
    }
    
    /**
     * Returns master's id
     * @return integer
     */
    public function getId()
    {
        return $this->_familyId;
    }
    
    /**
     * Returns the steps of the family
     * @return \SplObjectStorage
     */
    public function getSteps()
    {
        return $this->_steps;
    }
    
    /**
     * Returns true if the family is running
     * @return boolean
     */
    public function isRunning()
    {
        $queryRunning = "SELECT family_id
            FROM sys_family_track
            WHERE family_id = '{$this->_familyId}'
            AND encours = true
            AND done = false";
        $this->_database->execute($queryRunning);
        return ($this->_database->getNumRows() > 0);
    }
    
    /**
     * Returns true if the family is complete
     * @return boolean
     */
    public function isComplete()
    {
        $queryComplete = "SELECT family_id
            FROM sys_family_track
            WHERE family_id = '{$this->_familyId}'
            AND encours = false
            AND done = true";
        $this->_database->execute($queryComplete);
        return ($this->_database->getNumRows() > 0);
    }
    
    /**
     * Returns the steps of the family that are running
     * @return \SplObjectStorage
     */
    public function getRunningSteps()
    {
        $steps = new SplObjectStorage();        
        foreach($this->_steps as $step) {
            if($step->isRunning()) {
                $steps->attach($step);
            }
        }        
        return $steps;
    }
    
    /**
     * Returns the steps of the family that are complete
     * @return \SplObjectStorage
     */
    public function getCompleteSteps()
    {
        $steps = new SplObjectStorage();        
        foreach($this->_steps as $step) {
            if($step->isComplete()) {
                $steps->attach($step);
            }
        }        
        return $steps;
    }
    
    /**
     * Checks if treatment is over
     */
    public function checkStatus()
    {
        if(count($this->getCompleteSteps()) == count($this->getSteps()))
        {
            $queryUpdate = "UPDATE sys_family_track
                SET encours = false, done = true
                WHERE family_id = '{$this->_familyId}'";
            $this->_database->execute($queryUpdate);
            
            foreach($this->_steps as $step) {
                $step->stop();
            }
        }
    }
    
    /**
     * Launches the family
     */
    public function launch()
    {
        $queryLaunch = "INSERT INTO sys_family_track
            (family_id,master_id,family_order,encours,done,date)
            VALUES ('{$this->_familyId}', NULL, {$this->getValue('ordre')}, true, false, {$this->_currentTime})";
        $this->_database->execute($queryLaunch);
        
        // Launches step
        $this->update();
    }
    
    /**
     * Updates the treatment
     */
    public function update()
    {
        // Launches step
        $stepLauncher = new StepLauncher($this, $this->_productId);
        $stepLauncher->go();
    }
    
    /**
     * Stopps the treatment
     */
    public function stop()
    {
        $queryStop = "DELETE FROM sys_family_track
            WHERE family_id = '{$this->_familyId}'";
        $this->_database->execute($queryStop);
        // Cleaning data
        $queryAnalyze = "VACUUM ANALYZE sys_family_track";
        $this->_database->execute($queryAnalyze);
    }
}
?>