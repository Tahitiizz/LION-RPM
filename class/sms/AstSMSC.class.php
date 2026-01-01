<?php
/**
 * Classe gérant un SMS-C (Short Message Service Center)
 * @copyright Copyright (c) 2011, Astellia
 * @since 5.0.7.00
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */
class AstSMSC
{
    /**
     * Valeur par défaut du port
     */
    const DEFAULT_SMSC_PORT = 2775;

    /**
     * Valeur maximal autorisée pour le port
     */
    const MAX_SMSC_PORT = 65535;

    /**
     * Nom d'hote ou adresse IP du serveur SMS-C
     * @var string
     */
    protected $_host;

    /**
     * Identifiant du système demandant la connexion
     * @var string
     */
    protected $_systemId;

    /**
     * Mot de passe pour la connexion
     * @var string
     */
    protected $_password;

    /**
     * Numéro du port pour la connexion
     * @var integer
     */
    protected $_port = self::DEFAULT_SMSC_PORT;

    /**
     * Type du système demandant la connexion
     * @var string
     */
    protected $_systemType = '';

    /**
     * Constructeur de la classe.
     * Attention, le message des exceptions est succeptible d'apparaître dans
     * les I.H.M. utilisateurs.
     *
     * @param string  $host  Nom d'hote ou adresse IP du serveur SMS-C
     * @param string  $sId   Identifiant du système demandant la connexion
     * @param string  $pwd   Mot de passe pour la connexion
     * @param integer $port  Numéro du port pour la connexion (optionnel)
     * @param string  $sType Type du système demandant la connexion
     */
    public function __construct( $host, $sId, $pwd, $port = self::DEFAULT_SMSC_PORT, $sType = '' )
    {
        if( $this->setHost( $host ) === false )
        {
            throw new InvalidArgumentException( "invalid host", 1 );
        }

        if( $this->setSystemId( $sId ) === false )
        {
            throw new InvalidArgumentException( "invalid system identifier", 2 );
        }

        if( $this->setPassword( $pwd ) === false )
        {
            throw new InvalidArgumentException( "invalid password", 3 );
        }

        if( $this->setPort( $port ) === false )
        {
            throw new InvalidArgumentException( "invalid port number", 4 );
        }

        if( $this->setSystemType( $sType ) === false )
        {
            throw new InvalidArgumentException( "invalid system type", 5 );
        }
    }

    /**
     * Setter du champ $_host
     * 
     * @param string $host
     * @return boolean
     */
    public function setHost( $host )
    {
        // Le nom (ou IP) du host ne peut etre vide
        if( strlen( trim( $host ) ) > 0  )
        {
            $this->_host = trim( $host );
            return true;
        }
        return false;
    }

    /**
     * Setter du champ $_port
     *
     * @param integer $port
     * @return boolean
     */
    public function setPort( $port )
    {
        if( is_int( $port ) && $port > 0 && $port <= self::MAX_SMSC_PORT )
        {
            $this->_port = $port;
            return true;
        }
        return false;
    }

    /**
     * Setter du champ $_systemId
     *
     * @param string $sId
     * @return boolean
     */
    public function setSystemId( $sId )
    {
        // Le system ID ne peut etre vide (bz23273)
        if( strlen( trim( $sId ) ) > 0  )
        {
            $this->_systemId = trim( $sId );
            return true;
        }
        return false;
    }

    /**
     * Setter du champ $_password
     *
     * @param string $pwd
     * @return boolean
     */
    public function setPassword( $pwd )
    {
        $this->_password = $pwd;
        return true;
    }

    /**
     * Setter du champ $_systemType
     *
     * @param string $sType
     * @return boolean
     */
    public function setSystemType( $sType )
    {
        $this->_systemType = $sType;
        return true;
    }

    /**
     * Getter du champ $_host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Getter du champ $_port
     *
     * @return integer
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Getter du champ $_systemId
     *
     * @return string
     */
    public function getSystemId()
    {
        return $this->_systemId;
    }

    /**
     * Getter du champ $_password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Getter du champ $_systemType
     *
     * @return string
     */
    public function getSystemType()
    {
        return $this->_systemType;
    }
}
