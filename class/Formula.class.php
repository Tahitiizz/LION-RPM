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

include_once REP_PHYSIQUE_NIVEAU_0.'class/interfaces/IFormula.class.php';

/**
	Classe abstraite définissant les opérations possibles sur une formule.
	Elle implémente l'interface IFormula.
*/
abstract class Formula implements IFormula
{
	/**
    * Stocke la formule
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var string
    */
    protected $formula;
		
    /**
    * Retourne la formule
    * @since CB 5.1.0.00
    * @return string $formula
    */
	public function getFormula()
	{
		return $this->formula;
	}
	
	/**
    * Vérifie la formule
    * @since CB 5.1.0.00
    * @return boolean true si la formule est correcte
    */
	public static function checkFormula(DataBaseConnection $dbConnection, $formula, $family, $idProduct)
	{
		// à implémenter
	}
	
	/**
    * Modifie la formule
    * @since CB 5.1.0.00
	* @param string $formula : la nouvelle formule
    * @return $this
    */
	public function setFormula($formula)
	{
		$this->formula = $formula;
		return $this;
	}
	
}

?>