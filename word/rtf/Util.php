<?php
/**
 * Utilities class.
 * @package Rtf
 */
class Util {	
	  
	/**
	 * Formats color code. Internal use.
	 * @param string $color Color
	 * @access public
	 * @static
	 */
	function formatColor($color) {	  
	  	if (strlen($color) == 7 & substr($color, 0, 1) == '#') {		    	    
		    return '\red'.hexdec(substr($color, 1, 2)).'\green'.hexdec(substr($color, 3, 2)).'\blue'.hexdec(substr($color, 5, 2));
		}		
	  	return $color;		  
	}
	
	/**
	 * Formats utf-8 encoded text. Internal use.
	 * @param string $str Text
	 * @access public
	 * @static	 
	 */	
	function utf8Unicode($str) {
	  	return Util::unicodeToEntitiesPreservingAscii(Util::utf8ToUnicode($str));
	}
	
	/**
	* @see http://www.randomchaos.com/documents/?source=php_and_unicode
	* @access private
	* @static
	*/
	function utf8ToUnicode($str) {        
	    $unicode = array();        
	    $values = array();
	    $lookingFor = 1;    
	    
	    for ($i = 0; $i < strlen($str); $i++ ) {
	        $thisValue = ord($str[$i]);        
	    
		    if ($thisValue < 128) {
				$unicode[] = $thisValue;
	        } else {        
	            if ( count( $values ) == 0 ) {			
					$lookingFor = ( $thisValue < 224 ) ? 2 : 3;
				}
	            
	            $values[] = $thisValue;
	            
	            if ( count( $values ) == $lookingFor ) {        
	                $number = ( $lookingFor == 3 ) ?
	                    ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
	                	( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
	                    
	                $unicode[] = $number;
	                $values = array();
	                $lookingFor = 1;        
	            }         
	        }         
	    } 
	
	    return $unicode;	
	} 
	
	/** 
	 * @access private
	 * @static
	 */
	function unicodeToEntities($unicode) {        
	    $entities = '';    
		foreach( $unicode as $value )  {	
			$entities .= '\uc0\u'.$value.' ';
	    }    
		return $entities;        
	}
	
	/** 
	 * @access private 
	 * @static
	 */
	function unicodeToEntitiesPreservingAscii($unicode) {
	    $entities = '';    
	    foreach( $unicode as $value ) {    	
			if ($value != 65279) {
		        $entities .= ( $value > 127 ) ? '\uc0\u' . $value . ' ' : chr( $value );        
		    }
	    }     
	    return $entities;    
	}
}
?>