<?php
/* 
 * 04/02/2011 MMT: correction bz 20373 : Colors for NE change each time homepage is loaded
 */

/**
 * Description of ElementColorManager
 *
 * This class is entended to make association of colors of Elements in the graphs throught the application.
 * It is responsible to keep track of this association using the session and always return the same color
 * for the same Elements until the user logs out
 *
 * The colors are chosen from a predifined list of distinctives HTML colors, once they run out, it generates
 * random colors
 *
 * WARNING!!!!!!!!! : This class is used by the homepage, be aware wheen changing the interface!
 *
 * @author m.monfort
 */
class ElementColorManager {


	// association NE => color array
	private $eltColors;
	
	// list of predifined colors
	private $staticColors = array('#0000FF','#CC0000','#000000','#009900','#FFCC00',
											'#336666','#996633','#660099','#FF6600','#66FF66',
											'#66FFCC','#FFCC99' );

	// session KEY for the NE color array
	const SESSION_KEY  = 'elementColors';

	public function __construct(){
		$this->eltColors = array();
	}

	/**
	 * return an instance of the NEColorManager class, initialized with the session Ne colors values
	 * if exist
	 * @return NEColorManager
	 */
	public static function getInstance(){
		$ret = new ElementColorManager();
		if(array_key_exists(self::SESSION_KEY, $_SESSION)){
			$sessionEltColors = unserialize($_SESSION[self::SESSION_KEY]);
			$ret->setElmentColors($sessionEltColors);
		}
		return $ret;
	}

	/**
	 * Save the current color association in the session to be used on next refresh
	 */
	private function saveColorsToSession(){
		$_SESSION[self::SESSION_KEY] = serialize($this->eltColors);
	}

	/*
	 * affect the list of NE Colors
	 */
	public function setElmentColors($eltColors){
		$this->eltColors = $eltColors;
	}


	/**
	 * Get the graph color for the given elment,
	 * return the previously affected color if exist or affect a new one and return it
	 * The colors are chosen from a predifined list of distinctives HTML colors,
	 * once they run out, it uses random colors
	 * a category can be provided so that one element assignement is made per category
	 * @param String $elementId elment ID
	 * @param String $category optional category
	 * @return string color in #RRGGBB format
	 */
	public function getElementColor($elementId,$category="default"){

		if (!array_key_exists($category, $this->eltColors)){
			$this->eltColors[$category] = array();
		}

		// look association for elt
		if(array_key_exists($elementId, $this->eltColors[$category])){
			$ret = $this->eltColors[$category][$elementId];
		} else {
			// if not found, affect a new one
			$nbAffectedColors = count($this->eltColors[$category]);
			// if still have unaffected static colors, use them
			if($nbAffectedColors < count($this->staticColors)){
				$ret = $this->staticColors[$nbAffectedColors];
			} else {
				// if not generate random one
				$ret = '#'.dechex(rand(0,255)).dechex(rand(0,255)).dechex(rand(0,255));
			}
			// save the new color in the array and save it to session
			$this->eltColors[$category][$elementId] = $ret;
			$this->saveColorsToSession();
		}
		return $ret;
	}


}
?>
