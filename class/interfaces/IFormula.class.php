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
<?
/**
	Interface des formules
*/
interface IFormula
{
    /**
    * Retourne la formule
    * @since CB 5.1.0.00
    * @return string $formula
    */
	public function getFormula();
	
    /**
    * Vérifie la formule
    * @since CB 5.1.0.00
    * @return boolean true si la formule est correcte
    */
	public static function checkFormula(DataBaseConnection $dbConnection, $formula, $family, $idProduct);
	
    /**
    * Modifie la formule
    * @since CB 5.1.0.00
	* @param string $formula : la nouvelle formule
    * @return $this
    */
	public function setFormula($formula);
	
}

?>