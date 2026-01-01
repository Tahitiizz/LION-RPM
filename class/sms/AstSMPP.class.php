<?php
/**
 * Classe gérant le protocole SMPP (Short Message Peer to Peer)
 * 
 * @copyright Copyright (c) 2011, Astellia
 * @since 5.0.7.00
 *
 * $Author: a.cremades $
 * $Date: 2011-11-03 09:37:29 +0100 (jeu., 03 nov. 2011) $
 * $Revision: 28395 $
 */

// Inclusion des librairies
require_once dirname( __FILE__ ).'/smppclass.php';
require_once dirname( __FILE__ ).'/AstSMSC.class.php';
require_once dirname( __FILE__ ).'/AstSMS.class.php';

class AstSMPP extends SMPPClass
{
    /**
     * Numéro de l'appellant
     *
     * @var AstPhoneNumber
     */
    protected $_sender;

    /**
     * SMS-C utilisé pour la connexion
     *
     * @var AstSMSC
     */
    protected $_smsc;

    /**
     * Tableau stoquant les erreurs des trames PDU recues
     *
     * @var array
     */
    protected $_pduErrorList = array();

    /**
     * Définition des constantes d'erreurs SMPP
     */
    const ESME_RINVMSGLEN       = 0x00000001; // Mssage Length is invalid
    const ESME_RINVCMDLEN       = 0x00000002; // Command Length is invalid
    const ESME_RINVCMDID        = 0x00000003; // Invalid Command ID
    const ESME_RINVBNDSTS       = 0x00000004; // Incorrect BIND Status for given command
    const ESME_RALYBND          = 0x00000005; // ESME Already in Bound State
    const ESME_RINVPRTFLG       = 0x00000006; // Invalid Priority Flag
    const ESME_RINVREGDLVFLG    = 0x00000007; // Invalid Registered Delivery Flag
    const ESME_RSYSERR          = 0x00000008; // System Error
    const ESME_RINVSRCADR       = 0x0000000A; // Invalid Source Address
    const ESME_RINVDSTADR       = 0x0000000B; // Invalid Dest Addr
    const ESME_RINVMSGID        = 0x0000000C; // Message ID is invalid
    const ESME_RBINDFAIL        = 0x0000000D; // Bind Failed
    const ESME_RINVPASWD        = 0x0000000E; // Invalid Password
    const ESME_RINVSYSID        = 0x0000000F; // Invalid System ID
    const ESME_RCANCELFAIL      = 0x00000011; // Cancel SM Failed
    const ESME_RREPLACEFAIL     = 0x00000013; // Replace SM Failed
    const ESME_RMSGQFUL         = 0x00000014; // Message Queue Full
    const ESME_RINVSERTYP       = 0x00000015; // Invalid Service Type
    const ESME_RINVNUMDESTS     = 0x00000033; // Invalid number of destinations
    const ESME_RINVDLNAME       = 0x00000034; // Invalid Distribution List name
    const ESME_RINVDESTFLAG     = 0x00000040; // Destination flag is invalid
    const ESME_RINVSUBREP       = 0x00000042; // Invalid ?submit with replace? request
    const ESME_RINVESMCLASS     = 0x00000043; // Invalid esm_class field data
    const ESME_RCNTSUBDL        = 0x00000044; // Cannot Submit to Distribution List
    const ESME_RSUBMITFAIL      = 0x00000045; // submit_sm or submit_multi failed
    const ESME_RINVSRCTON       = 0x00000048; // Invalid Source address TON
    const ESME_RINVSRCNPI       = 0x00000049; // Invalid Source address NPI
    const ESME_RINVDSTTON       = 0x00000050; // Invalid Destination address TON
    const ESME_RINVDSTNPI       = 0x00000051; // Invalid Destination address NPI
    const ESME_RINVSYSTYP       = 0x00000053; // Invalid system_type field
    const ESME_RINVREPFLAG      = 0x00000054; // Invalid replace_if_present flag
    const ESME_RINVNUMMSGS      = 0x00000055; // Invalid number of messages
    const ESME_RTHROTTLED       = 0x00000058; // Throttling error (ESME has exceeded allowed message limits)
    const ESME_RINVSCHED        = 0x00000061; // Invalid Scheduled Delivery Time
    const ESME_RINVEXPIRY       = 0x00000062; // Invalid message validity period (Expiry time)
    const ESME_RINVDFTMSGID     = 0x00000063; // Predefined Message Invalid or Not Found
    const ESME_RX_T_APPN        = 0x00000064; // ESME Receiver Temporary App Error Code
    const ESME_RX_P_APPN        = 0x00000065; // ESME Receiver Permanent App Error Code
    const ESME_RX_R_APPN        = 0x00000066; // ESME Receiver Reject Message Error Code
    const ESME_RQUERYFAIL       = 0x00000067; // query_sm request failed
    const ESME_RINVOPTPARSTREAM = 0x000000C0; // Error in the optional part of the PDU Body.
    const ESME_ROPTPARNOTALLWD  = 0x000000C1; // Optional Parameter not allowed
    const ESME_RINVPARLEN       = 0x000000C2; // Invalid Parameter Length.
    const ESME_RMISSINGOPTPARAM = 0x000000C3; // Expected Optional Parameter missing
    const ESME_RINVOPTPARAMVAL  = 0x000000C4; // Invalid Optional Parameter Value
    const ESME_RDELIVERYFAILURE = 0x000000FE; // Delivery Failure (used for data_sm_resp)
    const ESME_RUNKNOWNERR      = 0x000000FF; // Unknown Error
    const AST_FSOCKOPEN         = 0xFFFFFFFF; // FSocket open Error

    /**
     * Constructeur de la classe AstSMPP
     *
     * @param AstSMSC $smsc
     */
    public function __construct( AstSMSC $smsc, $debug = false )
    {
        $this->_smsc     = $smsc;
        parent::SMPPClass();
        $this->_debug    = $debug;
    }

    /**
     * Initiliation du numéro de l'appellant
     * 
     * @param AstPhoneNumber $sender
     */
    public function setSender( AstPhoneNumber $sender )
    {
        $this->_sender = $sender;

        // Initialisation du sender dans la classe parent
        parent::SetSender( $this->_sender->getPhoneNumber() );
    }

    /**
     * Getter du champ $_sender
     * 
     * @return AstPhoneNumber
     */
    public function getSender()
    {
        return $this->_sender;
    }

    /*
     * Connexion au SMS-C
     *
     * @return boolean
     */
    public function bind()
    {
        $host  = $this->_smsc->getHost();
        $port  = $this->_smsc->getPort();
        $sId   = $this->_smsc->getSystemId();
        $pass  = $this->_smsc->getPassword();
        $sType = $this->_smsc->getSystemType();

        // Ajout du @ pour éviter les warnings PHP du fsockopen
        $bindRetVal = @parent::Start( $host, $port, $sId, $pass, $sType );

        // Si le bind echoue avec valeur de retour NULL, cela signifie que la
        // socket n'a pas pu être ouverte. Il ne s'agit pas d'une erreur PDU
        // mais l'erreur est néamoins stockée dans la liste.
        if( $bindRetVal === NULL )
        {
            $this->_pduErrorList []= self::AST_FSOCKOPEN;
        }
        return ( $bindRetVal === true );
    }

    /**
     * Déconnexion au SMS-C
     * 
     * @return boolean
     */
    public function unbind()
    {
        $unbindRetVal        = parent::End();
        $this->_pduErrorList = array();

        return ( $unbindRetVal === true );
    }

    /**
     * Test la connexion au SMS-C
     *
     * @return boolean
     */
    public function testLink()
    {
        return ( parent::TestLink() === true );
    }

    /**
     * Envoi du SMS 
     * 
     * @param AstSMS $sms 
     * @return boolean
     */
    public function sendMessage( AstSMS $sms )
    {
        $recList   = $sms->getRecipients();
        $nbRecList = $recList->count();
        $msg       = $sms->getMessage();

        // Vérification de la validité du SMS
        if( $nbRecList > 0 && $msg instanceof AstSMSMessage )
        {
            $recList->rewind();

            // Si le SMS est destiné à plusieurs destinataires
            if( $nbRecList > 1 )
            {
                // Conacaténation des numéros (avec virgule)
                $recArray = array();
                foreach( $recList as $rec )
                {
                    $recArray []= $rec->getPhoneNumber();
                }
                $list = implode( ',', $recArray );
                return ( parent::SendMulti( $list, $msg->getMessage() ) === true );
            }

            // Si il n'y a qu'un seul destinataire
            else
            {
                return ( parent::Send( $recList->current()->getPhoneNumber(), $msg->getMessage() ) === true );
            }
        }
        return false;
    }

    /**
     * Surcharge de la méthode SendPDU permettant d'effectué une gestion des
     * codes d'erreur. Cette surcharge a pour seul but de stoquer ces codes.
     */
    public function SendPDU( $command_id, $pdu )
    {
        $pduRetVal = parent::sendPDU( $command_id, $pdu );
        if( $pduRetVal !== 0 )
        {
            $this->_pduErrorList []= $pduRetVal;
        }
        return $pduRetVal;
    }

    /**
     * Retourne (et dépile) la dernière erreur trouvée dans les trames SMPP. Si
     * aucune erreur, 0 (zéro) est retournée.
     *
     * @return integer
     */
    protected function getLastError()
    {
        if( count( $this->_pduErrorList ) > 0 )
        {
            return array_pop( $this->_pduErrorList );
        }
        else
        {
            // Aucune erreur présente, on retournera 0
            return 0;
        }
    }

    /**
     * Retourne un message correspondant à la dernière erreur trouvée (retourne
     * 'unknow error' si le code est inconnu).
     * 
     * @param integer $val Code d'erreur
     * @return string
     */
    public function getStrLastError()
    {
        $retStr = "";

        // Initialisation des messages d'erreurs
        switch( $this->getLastError() )
        {
            case self::ESME_RINVMSGLEN       : $retStr = "message length is invalid";break;
            case self::ESME_RINVCMDLEN       : $retStr = "command length is invalid";break;
            case self::ESME_RINVCMDID        : $retStr = "invalid command ID";break;
            case self::ESME_RINVBNDSTS       : $retStr = "incorrect BIND status for given command";break;
            case self::ESME_RALYBND          : $retStr = "ESME already in bound state";break;
            case self::ESME_RINVPRTFLG       : $retStr = "invalid priority flag";break;
            case self::ESME_RINVREGDLVFLG    : $retStr = "invalid registered delivery flag";break;
            case self::ESME_RSYSERR          : $retStr = "system error";break;
            case self::ESME_RINVSRCADR       : $retStr = "invalid source address";break;
            case self::ESME_RINVDSTADR       : $retStr = "invalid dest addr";break;
            case self::ESME_RINVMSGID        : $retStr = "message id is invalid";break;
            case self::ESME_RBINDFAIL        : $retStr = "bind failed";break;
            case self::ESME_RINVPASWD        : $retStr = "invalid password";break;
            case self::ESME_RINVSYSID        : $retStr = "invalid system id";break;
            case self::ESME_RCANCELFAIL      : $retStr = "cancel sm failed";break;
            case self::ESME_RREPLACEFAIL     : $retStr = "replace sm failed";break;
            case self::ESME_RMSGQFUL         : $retStr = "message queue full";break;
            case self::ESME_RINVSERTYP       : $retStr = "invalid service type";break;
            case self::ESME_RINVNUMDESTS     : $retStr = "invalid number of destinations";break;
            case self::ESME_RINVDLNAME       : $retStr = "invalid distribution list name";break;
            case self::ESME_RINVDESTFLAG     : $retStr = "destination flag is invalid";break;
            case self::ESME_RINVSUBREP       : $retStr = "invalid 'submit with replace' request";break;
            case self::ESME_RINVESMCLASS     : $retStr = "invalid esm_class field data";break;
            case self::ESME_RCNTSUBDL        : $retStr = "cannot submit to distribution list";break;
            case self::ESME_RSUBMITFAIL      : $retStr = "submit_sm or submit_multi failed";break;
            case self::ESME_RINVSRCTON       : $retStr = "invalid source address TON";break;
            case self::ESME_RINVSRCNPI       : $retStr = "invalid source address NPI";break;
            case self::ESME_RINVDSTTON       : $retStr = "invalid destination address TON";break;
            case self::ESME_RINVDSTNPI       : $retStr = "invalid destination address NPI";break;
            case self::ESME_RINVSYSTYP       : $retStr = "invalid system_type field";break;
            case self::ESME_RINVREPFLAG      : $retStr = "invalid replace_if_present flag";break;
            case self::ESME_RINVNUMMSGS      : $retStr = "invalid number of messages";break;
            case self::ESME_RTHROTTLED       : $retStr = "throttling error (ESME has exceeded allowed message limits)";break;
            case self::ESME_RINVSCHED        : $retStr = "invalid scheduled delivery time";break;
            case self::ESME_RINVEXPIRY       : $retStr = "invalid message validity period (expiry time)";break;
            case self::ESME_RINVDFTMSGID     : $retStr = "predefined message invalid or not found";break;
            case self::ESME_RX_T_APPN        : $retStr = "ESME receiver temporary app error code";break;
            case self::ESME_RX_P_APPN        : $retStr = "ESME receiver permanent app error code";break;
            case self::ESME_RX_R_APPN        : $retStr = "ESME receiver reject message error code";break;
            case self::ESME_RQUERYFAIL       : $retStr = "query_sm request failed";break;
            case self::ESME_RINVOPTPARSTREAM : $retStr = "error in the optional part of the pdu body.";break;
            case self::ESME_ROPTPARNOTALLWD  : $retStr = "optional parameter not allowed";break;
            case self::ESME_RINVPARLEN       : $retStr = "invalid parameter length";break;
            case self::ESME_RMISSINGOPTPARAM : $retStr = "expected optional parameter missing";break;
            case self::ESME_RINVOPTPARAMVAL  : $retStr = "invalid optional parameter value";break;
            case self::ESME_RDELIVERYFAILURE : $retStr = "delivery failure";break;
            case self::ESME_RUNKNOWNERR      : $retStr = "unknown SMPP Error";break;
            case self::AST_FSOCKOPEN         : $retStr = "unable to connect to specified Host/Port";break;
            default                          : $retStr = "unknow error";break;
        }
        return $retStr;
    }
}
