<?php
/**
 * Class MasterLauncher
 * Manages master launches
 * 05/02/2013 BBX
 * DE Optims T&A
 */
class MasterLauncher
{
    /**
     * ID of product
     * @var type 
     */
    protected $_productId   = null;
    
    /**
     * master to launch
     * @var Master object 
     */
    protected $_master   = null;
    
    /**
     * Constructor
     * @param type $productId
     */
    public function __construct($m,$productId = 0)
    {
        $this->_productId   = (int)$productId;
        $this->_master = new Master($m, $this->_productId);
    }
    
    /**
     * Master treatment
     */
    public function go()
    {
        $this->_master->launch();
    }
}
?>
