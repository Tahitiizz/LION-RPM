<?
/*
*	@cb51000@
*
*	28-06-2010 - Copyright Astellia
* 
*	Composant de base version cb_5.1.0.00
*
*/
?>
<?php

include_once REP_PHYSIQUE_NIVEAU_0.'class/Formula.class.php';

/**
	Classe définissant les opérations possibles sur une formule de Raw.
	Elle hérite de la classe Formula (qui implémente l'interface IFormula).
*/
class RawFormula extends Formula
{
	/**
    * constructeur : initialise la formule
    * @since CB 5.1.0.00
	* @param string $formula
    * @return $this
    */
	public function __construct($formula)
	{
		$this->formula = $formula;
		return $this;
	}
		
    /**
    * Vérifie la formule
    * @since CB 5.1.0.00
    * @return boolean true si la formule est correcte
    */
	public static function checkFormula(DataBaseConnection $dbConnection, $formula, $family, $idProduct)
	{
		// check spécifique aux RAW
	}
}

?>