<?php
/**
 * Class StepLauncher
 * Manages steps launches
 * 05/02/2013 BBX
 * DE Optims T&A
 */
class StepLauncher
{
    /**
     * Stores database connection instance
     * @var DatabaseConnection objet 
     */
    protected $_database    = null;
    
    /**
     * ID of product
     * @var type 
     */
    protected $_productId   = null;
    
    /**
     * Current timestamp
     * @var integer 
     */
    protected $_currentTime = 0;
    
    /**
     * Stores the related family
     * @var Master object 
     */
    protected $_family      = null;

    /**
     * Constructor
     * @param Family $family
     * @param integer $productId
     */
    public function __construct(Family $family, $productId = 0)
    {
        // Database, time
        $this->_productId   = (int)$productId;
        $this->_database    = Database::getConnection($this->_productId);        
        $this->_currentTime = time();
        $this->_family      = $family;
    }
    
    /**
     * Steps treatment
     */
    public function go()
    {
        // Updating status
        foreach($this->_family->getSteps() as $step) {
            $step->checkStatus();
        }

        // If a step is already running
        if(count($this->_getRunningSteps()) == 0) {
            // Launching processes
            foreach($this->_getStepsToLaunch() as $stepToLaunch) {            
                //$this->log("Launching step ".$stepToLaunch->getId());            
                $stepToLaunch->launch();
            }            
        }
        /*else {
            $this->log("Waiting for current step to end...");
        }*/      
    }
    
    /**
     * Returns running steps
     * @return \SplObjectStorage
     */
    protected function _getRunningSteps()
    {
        $steps = new SplObjectStorage();        
        foreach($this->_family->getSteps() as $step) {
            if($step->isRunning()) {
                $steps->attach($step);
            }
        }        
        return $steps;
    }
    
    /**
     * Returns steps to launch
     * @return \SplObjectStorage
     */
    protected function _getStepsToLaunch()
    {
        $steps = new SplObjectStorage();        
        foreach($this->_family->getSteps() as $step) {
            if(!$step->isRunning() && !$step->isComplete()) {
                $steps->attach($step);
                return $steps;
            }
        }        
        return $steps;
    }
    
    /**
     * Displays a message
     * @param string $message
     */
    public function log($message)
    {
        echo $message."<br/>";
    }
}
?>
