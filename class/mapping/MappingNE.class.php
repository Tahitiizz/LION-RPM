<?php
/**
 * Cette classe permet à partir d'un tableau d'élément réseaux de connaitre le mapping associé
 *
 * @author GHX
 * @package Mapping 
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
class MappingNE
{
	/**
	 * Tableaux de toutes les connexion à la base de données
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_listDb = array();
	
	/**
	 * Tableau d'élément réseaux
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_neInit;
	
	/**
	 * Information sur le master produit 
	 *	
	 *	NOTE : ne pas confondre avec le master topologie qui peuvent être 2 produits instincts
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_masterProduct = null;
	
	/**
	 * Information sur le produit master topologie
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_masterTopology = null;
	
	/**
	 * Information sur les autres produits, contient aussi le master produit si c'est pas le master topologie
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $_othersProducts = array();
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function __construct ()
	{
		$productsInformations = getProductInformations();
		foreach ( $productsInformations as $oneProduct )
		{
			if ( $oneProduct['sdp_master'] == 1 )
			{
				$this->_masterProduct = $oneProduct;
			}
			
			if ( $oneProduct['sdp_master_topo'] == 1 )
			{
				$this->_masterTopology = $oneProduct;
			}
			else
			{
				$this->_othersProducts[$oneProduct['sdp_id']] = $oneProduct;
			}
		}
	} // End function __construct
	
	/**
	 * Définie le tableau d'élément réseaux
	 *
	 * Le format du tableau doit être le suivant : 
	 * Array (
	 *	[bsc] => Array (
	 *			[0] => bsc1,
	 *			[1] => bsc2,
	 *			...
	 *		),
	 *	[cell] => Array (
	 *			[0] => cell1,
	 *			...
	 *		),
	 *	...
	 * )
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $ne tableau d'élément réseau
	 */
	public function setNE ( $ne )
	{
		$this->_neInit = $ne;
	} // End functon setNE
	
	/**
	 *
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $ne tableau d'élément réseau
	 */
	public function setNEFromSelecteur ( $ne )
	{
		$ne1_all = explode("|s|", $ne);

		$ne1 = array();

		$nb = count($ne1_all);
		for ( $i=0; $i < $nb; $i++ )
		{
			$ne1_tmp = explode("||", $ne1_all[$i]);
			$ne1[$ne1_tmp[0]][] = $ne1_tmp[1];
		}
		
		$this->setNE($ne1);
	} // End function setNESelecteur
	
	/**
	 * Retourne le mapping correspondant si pas de multi-produit retourne FALSE
	 *
	 * Le tableau retourné est de la forme suivante : 
	 * Array (
	 *	[idProduct] => Array (
	 *			[bsc] => Array (
	 *					[eor_id_codeq] => [eor_id]
	 *					...
	 *				),
	 *			[cell] => Array (
	 *					...
	 *				)
	 *			...
	 *		),
	 *	...
	 * )
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param boolean $inverse TRUE si on inverse l'index et la valeur (c'est à dire que eor_id devient la clé et eor_id_codeq la valeur) (default FALSE)
	 * @return array
	 */
	public function getMappingByProducts ( $inverse = false )
	{
		if ( count($this->_othersProducts) == 0 )
			return false;
	
		$resultMapping = array();
		
		$queryFieldIndex = 'eor_id_codeq';
		$queryFieldValue = 'eor_id';
		if ( $inverse )
		{
			$queryFieldIndex = 'eor_id';
			$queryFieldValue = 'eor_id_codeq';
		}
		
		// Boucle sur tous les produits qui ne sont pas le master topo
		foreach ( $this->_othersProducts as $oneProduct )
		{
			// Récupère une connexion sur le produit
			$db = $this->getConnection($oneProduct['sdp_id']);
			// Boucle sur tous les types niveaux d'aggrégation
			foreach ( $this->_neInit as $na => $arrayNe )
			{
				$query = "
					SELECT
						{$queryFieldIndex} AS index,
						{$queryFieldValue} AS value
					FROM
						edw_object_ref
					WHERE
						eor_obj_type = '{$na}'
						AND eor_id_codeq IN ('".implode("','", $arrayNe)."')
					";
				
				$resultQuery = $db->execute($query);
				
				if ( $db->getNumRows() == 0 )
					continue;
				
				$resultMapping[$oneProduct['sdp_id']] = array();
				
				while ( $row = $db->getQueryResults($resultQuery, 1) )
				{
					$resultMapping[$oneProduct['sdp_id']][$na][$row['index']] = $row['value'];
				}
			}
		}
		
		return $resultMapping;
	} // End function getMappingByProduct
	
	/**
	 * Retourne le mapping correspondant à un produit si pas de multi-produit ou si l'identifiant du produit n'existe pas retourne FALSE 
	 * 
	 * Le tableau retourné est de la forme suivante : 
	 * Array (
	 *	[bsc] => Array (
	 *			[eor_id_codeq] => [eor_id]
	 *			...
	 *		),
	 *	[cell] => Array (
	 *			...
	 *		)
	 *	...
	 * )
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant d'un produit
	 * @param boolean $inverse TRUE si on inverse l'index et la valeur (c'est à dire que eor_id devient la clé et eor_id_codeq la valeur) (default FALSE)
	 * @return array
	 */
	public function getMappingForOneProduct ( $idProduct, $inverse = false )
	{
		if ( !array_key_exists($idProduct, $this->_othersProducts) )
			return false;
			
		if ( $resultMapping = $this->getMappingByProducts($inverse) )
		{
			if ( array_key_exists($idProduct, $resultMapping) )
			{
				return $resultMapping[$idProduct];
			}
		}
		
		return false;
	} // End function getMappingForOneProduct
	
	/**
	 * Retourne la connexion à une base de données d'un produit
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit on doit se connecter
	 * @return Ressource
	 */
	private function getConnection ( $idProduct )
	{
		if ( !array_key_exists($idProduct, $this->_listDb) )
		{   
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$this->_listDb[$idProduct] = Database::getConnection($idProduct);
			if ( $this->_debug & 2 )
			{
				$this->_listDb[$idProduct]->setDebug(1);
			}
		}

		return $this->_listDb[$idProduct];
	} // End function getConnection

} // End class MappingNE
?>