<?php
/**
 * Classe gérant un SMS (Short Message Service)
 * @copyright Copyright (c) 2011, Astellia
 * @since 5.0.7.00
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */

// Inclusion des librairies
require_once dirname( __FILE__).'/AstPhoneNumber.class.php';
require_once dirname( __FILE__).'/AstSMSMessage.class.php';

class AstSMS
{
    /**
     * Le message du SMS
     * @var AstSMSMessage 
     */
    protected $_message;

    /**
     * Liste de destinataires du SMS (list of AstPhoneNumber)
     * @var SplObjectStorage
     */
    protected $_recipientsList;

    /**
     * Constructeur de la classe AstSMS
     */
    public function __construct()
    {
        $this->_recipientsList = new SplObjectStorage();
    }

    /**
     * Ajoute un destinataire à la liste en cours
     * 
     * @param AstPhoneNumber $pN
     */
    public function addRecipient( AstPhoneNumber $pN )
    {
        $this->_recipientsList->attach( $pN );
    }

    /**
     * Supprime toutes les entrées de la liste
     */
    public function clearRecipientsList()
    {
        $this->_recipientsList = new SplObjectStorage();
    }

    /**
     * Retourne le nombre de destinataires enregistrés
     * 
     * @return integer
     */
    public function getNbRecipients()
    {
        return $this->_recipientsList->count();
    }

    /**
     * Retourne la liste des destinataires
     * 
     * @return SplObjectStorage
     */
    public function getRecipients()
    {
        return $this->_recipientsList;
    }

    /**
     * Initialise le message du SMS
     * 
     * @param AstSMSMessage $msg
     */
    public function setMessage( AstSMSMessage $msg )
    {
        $this->_message = $msg;
    }

    /**
     * Retourne le message du SMS
     *
     * @return AstSMSMessage
     */
    public function getMessage()
    {
       return $this->_message;
    }
}
