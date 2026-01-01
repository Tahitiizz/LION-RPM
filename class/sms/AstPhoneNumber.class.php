<?php
/**
 * Classe gérant un numéro de portable
 * @copyright Copyright (c) 2011, Astellia
 * @since 5.0.7.00
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */
class AstPhoneNumber
{
    /**
     * Constante définisant l'expression régulière d'un numéro de portable
     * @var string
     */
    const PHONE_NUMBER_REG_EXP = '#^([0-9 \.\+\-\(\)])+$#';

    /**
     * Numéro de téléphone
     * @var string $_phoneNumber
     */
    protected $_phoneNumber;

    /**
     * Constructeur de la classe
     *
     * @param string $phoneNumber
     */
    public function __construct( $phoneNumber )
    {
        if( $this->setPhoneNumber( $phoneNumber ) === false )
        {
            throw new InvalidArgumentException( "invalid phone number", 1 );
        }
    }

    /**
     * Setter du champ $_phoneNumber
     *
     * @param string $phoneNumber
     * @return boolean
     */
    public function setPhoneNumber( $phoneNumber )
    {
        if( preg_match( self::PHONE_NUMBER_REG_EXP, $phoneNumber ) > 0 )
        {
            $this->_phoneNumber = $phoneNumber;
            return true;
        }
        return false;
    }

    /**
     * Getter du champ $_phoneNumber
     * 
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->_phoneNumber;
    }
}
