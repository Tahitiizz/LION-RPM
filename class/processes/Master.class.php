<?php
/**
 * Class Master
 * Allows to manipulate a master process
 * 05/02/2013 BBX
 * DE Optims T&A
 */
class Master
{
    /**
     * ID of master
     * @var integer 
     */
    protected $_masterId        = null;
    
    /**
     * ID of product
     * @var type 
     */
    protected $_productId       = 0;
    
    /**
     * Stores database connection instance
     * @var DatabaseConnection objet 
     */
    protected $_database        = null;
    
    /**
     * Stores master's properties
     * @var array 
     */
    protected $_masterValues    = array();
    
    /**
     * Store masters compatibility matrix
     * @var array 
     */
    protected $_compatibility   = array();
    
    /**
     * Stores the families of master
     * @var SplObjectStorage object 
     */
    protected $_families        = null;
    
    /**
     * Current timestamp
     * @var integer 
     */
    protected $_currentTime     = 0;
    
    /**
     * path to html log file
     * @var string 
     */
    protected $_htmlLog         = '';
    
    /**
     * Constructor
     * @param integer $masterId
     * @param integer $productId
     */
    public function __construct($masterId, $productId = 0)
    {
        // Database, time and log
        $this->_productId   = (int)$productId;
        $this->_database    = Database::getConnection($this->_productId);
        $this->_currentTime = time();
        // Todo : reporter les vérifs de droits d'écriture sur ce fichier
        $this->_htmlLog     = REP_PHYSIQUE_NIVEAU_0.'file_demon/demon_'.date('Ymd', $this->_currentTime).'.html';
        
        // Saving id
        $this->_masterId = (int)$masterId;
        
        // Fetching master information
        $queryMaster = "SELECT * FROM sys_definition_master 
            WHERE master_id = ".$this->_masterId;
        $result = $this->_database->execute($queryMaster);
        $this->_masterValues = $this->_database->getQueryResults($result,1);
        
        // Fetching compatibility matrix
        $queryCompatible = "SELECT DISTINCT master_compatible
            FROM sys_definition_master_compatibility
            WHERE master_id = ".$this->_masterId;
        $result = $this->_database->execute($queryCompatible);
        while($row = $this->_database->getQueryResults($result,1)) {
            $this->_compatibility[$row['master_compatible']] = $row['master_compatible'];
        }
        
        // Fetching families
        $this->_families = new SplObjectStorage();
        $queryFamilies = "SELECT family_id
            FROM sys_definition_family 
            WHERE master_id = '{$this->_masterId}'
            AND on_off = 1
            ORDER BY ordre ASC, family_id ASC";
        $result = $this->_database->execute($queryFamilies);
        while($row = $this->_database->getQueryResults($result,1)) {
            $this->_families->attach(new Family($row['family_id'], $this->_productId));
        }
    }
    
    /**
     * Returns a value of this master
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        return $this->_masterValues[$key];
    }
    
    /**
     * Returns master's id
     * @return integer
     */
    public function getId()
    {
        return $this->_masterId;
    }
    
    /**
     * Returns true if the given master is compatible with the current master
     * @param Master $master
     * @return boolean
     */
    public function isCompatibleWith(Master $master) 
    {
        return isset($this->_compatibility[$master->getId()]);
    }
    
    /**
     * Returns the families of master
     * @return \SplObjectStorage
     */
    public function getFamilies()
    {
        return $this->_families;
    }

    /**
     * Returns complete families of master
     * @return \SplObjectStorage
     */
    public function getCompleteFamilies()
    {
        $families = new SplObjectStorage();        
        foreach($this->_families as $family) {
            if($family->isComplete()) {
                $families->attach($family);
            }
        }        
        return $families;
    }
    
    /**
     * Returns true if this master is currently running
     * @return boolean
     */
    public function isRunning()
    {
        $queryRunning = "SELECT process
            FROM sys_process_encours
            WHERE process = '{$this->_masterId}'
            AND encours = 1
            AND done = 0";
        $this->_database->execute($queryRunning);
        return ($this->_database->getNumRows() > 0);
    }
    
    /**
     * Returns true if this master is complete
     * @return boolean
     */
    public function isComplete()
    {
        $queryComplete = "SELECT process
            FROM sys_process_encours
            WHERE process = '{$this->_masterId}'
            AND encours = 0
            AND done = 1";
        $this->_database->execute($queryComplete);
        return ($this->_database->getNumRows() > 0);
    }

    /**
     * Launches the master
     */
    public function launch()
    {
        // Logging
        $this->htmlOutput("Time stamp : ".date('r')." -> Lancement master ".$this->getValue('master_name'), $this->_htmlLog);
        $mode = $this->getValue('auto') == 't' ? 'auto' : 'cron';
        $this->htmlOutput("lancement via $mode", $this->_htmlLog);

        // Launches family
        $familyLauncher = new FamilyLauncher($this, $this->_productId);
        $familyLauncher->go();
        
        $this->stop();
    }
    
    /**
     * Checks if treatment is over
     */
    public function stop()
    {
        // Logging
        $this->htmlOutput("Time stamp : " . date('r') ."->master ".$this->getValue('master_name')." terminé", $this->_htmlLog);

        $queryUpdate = "UPDATE sys_process_encours
            SET encours = 0, done = 1
            WHERE process = '{$this->_masterId}'";
        $this->_database->execute($queryUpdate);

        // Stop families
        foreach($this->_families as $family) {
            $family->stop();
        }

        // Stop master
        $this->stopMaster();
    }
    
    
    /**
     * Stopps the master
     */
    public function stopMaster()
    {
        // Deletes master's entries
        $queryStop = "DELETE FROM sys_process_encours
            WHERE process = '{$this->_masterId}'";
        $this->_database->execute($queryStop);
        // Cleaning data
        $queryAnalyze = "VACUUM ANALYZE sys_process_encours";
        $this->_database->execute($queryAnalyze);
    }
    
    /**
     * Display a message as HTML
     * @param string $text
     * @param string $fileName
     */
    protected function htmlOutput($text, $fileName = '')
    {
        $html =  "<font color='red'>";
        $html .= "<b>{$text}</b>";
        $html .= "</font><br />";
        if(empty($fileName)) echo $html;
        else file_put_contents($fileName, $html, FILE_APPEND);
    }
}
?>
