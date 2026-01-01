<?php
/**
 * Class FamilyLauncher
 * Manages families launches
 * 05/02/2013 BBX
 * DE Optims T&A
 */
class FamilyLauncher
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
     * Stores the related master
     * @var Master object 
     */
    protected $_master      = null;

    /**
     * Constructor
     * @param type $productId
     */
    public function __construct(Master $master, $productId = 0)
    {
        // Database, time and log
        $this->_productId   = (int)$productId;
        $this->_database    = Database::getConnection($this->_productId);
        $this->_currentTime = time();
        $this->_master      = $master;
    }
    
    /**
     * Families treatment
     */
    public function go()
    {
        $cptFamiliesToLaunch=0;
        while($cptFamiliesToLaunch < count($this->_master->getFamilies()))
        {
            // Monitor families
            foreach($this->_master->getFamilies() as $family) {
                $family->checkStatus();
            }

            // Running families
            $runningFamilies = $this->_getRunningFamilies();

            // Updating processes
            $running = false;
            foreach($runningFamilies as $runningFamily) {
                //$this->log("Family ".$runningFamily->getId()." is running...");
                $runningFamily->update();
                $running = true;
            }

            // If no families are running
            if(!$running) {
                // Launching processes
                foreach($this->_getFamiliesToLaunch() as $familyToLaunch) {
                    //$this->log("Launching family ".$familyToLaunch->getId()."...");
                    $familyToLaunch->launch();
                }
            }
            
           $cptFamiliesToLaunch=0;
           foreach($this->_master->getFamilies() as $family) {
                $family->checkStatus();
                if($family->isComplete()){
                    $cptFamiliesToLaunch++;
                }
            }
        }
    }
    
    /**
     * Returns running families
     * @return \SplObjectStorage
     */
    protected function _getRunningFamilies()
    {
        $families = new SplObjectStorage();        
        foreach($this->_master->getFamilies() as $family) {
            if($family->isRunning()) {
                $families->attach($family);
            }
        }        
        return $families;
    }
    
    /**
     * Returns families to launch
     * @return \SplObjectStorage
     */
    public function _getFamiliesToLaunch()
    {
        $families = new SplObjectStorage();        
        foreach($this->_master->getFamilies() as $family) {
            if(!$family->isRunning() && !$family->isComplete()) {
                $families->attach($family);
                // One family at the time
                return $families;
            }
        }        
        return $families;
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
