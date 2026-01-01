<?php
/**
 * Borders formating class.
 * @package Rtf
 */
class BorderFormat {
  
	/**#@+ @access private */   
  	var $size;
  	
  	var $type;
  	
  	var $color;
  	
  	var $space;
	/**#@-*/
    
    /**
     * Constructor
     * @access public
     * @param int $size Size of border.
     * @param string $type Type of border (possible values 'single', 'dot', 'dash', 'dotdash'). Default 'single'.
     * @param string $color Colour of border (example '#ff0000')
     * @param float $space Space between borders and the paragraph. 
     */
	function BorderFormat($size = 0, $color = '', $type = '', $space = 0) {	  
	  	$this->size = $size * SPACE_IN_POINTS;
		$this->type = $type;
		$this->color = Util::formatColor($color); 		
		$this->space = round($space * TWIPS_IN_CM);
	}

	/**
	 * Gets rtf code of not colored part of border fotmat. Internal use.
	 * @access public
	 * @return string
	 */
	function getNotColoredPartOfContent() {	  
	  	return $this->GetType().'\brdrw'.$this->size.'\brsp'.$this->space;
	}

    /**
     * Gets rtf code of border format type. Internal use.
     * @access public
     * @return string
     */
	function getType() {	  
	  	switch ($this->type) {		    
		    case 'single':		    
		    	return '\brdrs';
		    break;
		    
		    case 'dot':		    
		    	return '\brdrdot';
		    break;
		    
		    case 'dash':		    	
		    	return '\brdrdash';
		    break;
		    
		    case 'dotdash':		    	
		    	return '\brdrdashd';
		    break;
		    
		    default:		    	
		    	return '\brdrs';
		    break;		    
		}
	}  	

}
?>