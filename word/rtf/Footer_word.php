<?php
/**
 * Class for creating footers of document.
 * @package Rtf
 */
class Footer_word extends Container {
  	  	
  	/**#@+ @access private */  
	var $type;	
	
	var $headery;
	/**#@-*/ 
  	
  	/**
  	 * Constructor. Internal use.
  	 * @param Rtf &$rtf
  	 * @param string $type
  	 * @access public
  	 */
  	function Footer_word(&$rtf, $type) {	    
	    $this->rtf = &$rtf;	
	    
	    switch ($type) {		  
			case 'all': 			
				$this->type = 'footer'; 
			break;
			
			case 'left': 			
				$this->type = 'footerl'; 
			break;
			
			case 'right': 			
				$this->type = 'footerr'; 
			break;
			
			case 'first': 			
				$this->type = 'footerf'; 
			break;
		}	    
	}

	
	/**
	 * Set footer position from page bottom.
	 * @param float $height
	 * @access public 
	 */
	function setPosition($height) {	  
	  	$this->footery = $height;
	}  	  	
  	  	
	/**
	 * Gets rtf code of footer. Internal use.
	 * @return string
	 * @access public 
	 */	
	function getContent() {	
	  	$content = isSet($this->footery) ? '\footery'.round(TWIPS_IN_CM * $this->footery).' ' : '';	    
		$content .= '{\\'.$this->type.' ';						
		$content .= parent::getContent();
		$content .= '\par ';
		$content .= '}';
		return $content."\r\n";
	}		
}
?>