<?php
/**
 * Classe gérant un message de SMS
 * @copyright Copyright (c) 2011, Astellia
 * @since 5.0.7.00
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */
class AstSMSMessage
{
    /**
     * Le message texte du SMS
     * @var string $_message
     */
    protected $_message = '';

    /**
     * Constructeur de la classe AstSMS
     *
     * @param string $msg
     */
    public function __construct( $msg )
    {
        if( $this->setMessage( $msg ) === false )
        {
            throw new InvalidArgumentException( "invalid message", 1 );
        }
    }

    /**
     * Setter du champ $_message
     *
     * @param string $msg
     * @return boolean
     */
    public function setMessage( $msg )
    {
        // Le message ne peut être 
        if( strlen( $msg ) > 0 )
        {
            $this->_message = $msg;
            return true;
        }
        return false;
    }

    /**
     * Getter du champ $_message
     * 
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }
}
