<?php

/**
 * Class for representing bordered part of object.
 * @package Rtf
 */
class Bordered {
    
    /**#@+ 
	 * @access private
	 */ 
  	var $borderLeft;
	
	var $borderRight;
	
	var $borderTop;
	
	var $borderBottom;	
	/**#@-*/  
	
    	
	/**
     * Sets borders of element. Internal use.     
     * @param BorderFormat $borderFormat
     * @param boolean $left If false, left border is not set (default true)
     * @param boolean $top If false, top border is not set (default true)
     * @param boolean $right If false, right border is not set (default true)
     * @param boolean $bottom If false, bottom border is not set (default true)
     * @access public    
     */	
	function setBorders(&$borderFormat, $left = true, $top = true, $right = true, $bottom = true) {			
		if (!empty($left)) {	
			$this->borderLeft = &$borderFormat;
		}
		
		if (!empty($top)) {	
			$this->borderTop = &$borderFormat;
		}
		
		if (!empty($right)) {	
			$this->borderRight = &$borderFormat;
		}
		
		if (!empty($bottom)) {	
			$this->borderBottom = &$borderFormat;
		}				
	}
	
	/** 
	 * Gets rtf code of object. Internal use.
	 * @param Rtf &$rtf
	 * @param string $type Rtf code part
	 * @access public 
	 */
	function getContent(&$rtf, $type = '\\') {	  
	  	$content = '';	
	  	$content .= !empty($this->borderLeft) && $this->borderLeft->size > 0 ? $type.'brdrl'.$this->getBorder($this->borderLeft, $rtf) : '';		
	  	$content .= !empty($this->borderRight) && $this->borderRight->size > 0 ? $type.'brdrr'.$this->getBorder($this->borderRight, $rtf) : '';
	  	$content .= !empty($this->borderTop) && $this->borderTop->size > 0 ? $type.'brdrt'.$this->getBorder($this->borderTop, $rtf) : '';
	  	$content .= !empty($this->borderBottom) && $this->borderBottom->size > 0 ? $type.'brdrb'.$this->getBorder($this->borderBottom, $rtf) : '';		  			
		return $content;
	}
	
	/** @access private */
	function getBorder(&$borderFormat, &$rtf) {	
	  	$border = $borderFormat->getNotColoredPartOfContent();
	  	
	  	if (!empty($borderFormat->color)) {		  	    
			$rtf->addColor($borderFormat->color);
		  	$border .= '\brdrcf'.$rtf->getColor($borderFormat->color);
		}
	  	
	  	return $border.' ';
	}			
}
?>