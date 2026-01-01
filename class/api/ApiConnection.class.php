<?php
/**
 *  Classe gérant la connexion à l'API
 *
 * $Author: n.stienne $
 * $Date: 2012-07-04 10:13:05 +0200 (mer., 04 juil. 2012) $
 * $Revision: 85730 $
 */

define( 'API_CONNECTION_USER', 0 );
define( 'API_CONNECTION_ADMIN', 1 );
define( 'API_CONNECTION_ASTELLIA_ADMIN', 2 );

require_once( dirname( __FILE__).'/../Database.class.php' );
require_once( dirname( __FILE__).'/../DataBaseConnection.class.php' );

class ApiConnection
{
    /** @var Boolean Etat de la connexion à l'API (TRUE ou FALSE) */
    protected $connectionState;

    /**
     * Niveau de l'utilisateur connecté
     * API_CONNECTION_USER, API_CONNECTION_ADMIN ou API_CONNECTION_ASTELLIA_ADMIN
     * @var Integer
     */
    protected $connectionLevel;

    /** @var DataBaseConnection object */
    protected $dbConnection;

    /** @var Unsigned Integer Temps maximum d'inactivité en secondes avec déconnexion */
    protected $timeoutInactivity;

    /** @var Unsigned Integer Timestamp de la dernière utilisation de la connexion */
    protected $lastConnectionUse;

    /**
     * Constructeur de la classe (initialisation des variables)
     * @param String $login Login de l'utilisateur
     * @param String $password Mot de passe
     */
    public function  __construct( $login, $password )
    {
        /** @var Array Résultat de la requète SQL */
        $sqlRes = array();

        // Initialisation des variables. Par défaut, le statut de la connexion
        // est FALSE, et le niveau est API_CONNECTION_USER
        $this->connectionState = FALSE;
        $this->connectionLevel = API_CONNECTION_USER;
        $this->timeoutInactivity = 600; // Valeur par défaut si la lecture en BDD échoue

        // Connexion à la base
        try
        {
            $this->dbConnection = Database::getConnection();

            // 02/07/2012 NSE bz 27854 : API should no more uses users table -> use of global parameters
            $sqlRes = $this->dbConnection->getAll(
                    "SELECT sgp1.parameters as login
                    FROM sys_global_parameters sgp1, sys_global_parameters sgp2
                    WHERE sgp1.parameters='api_login' AND sgp1.value='".$login."' 
                        AND sgp2.parameters='api_password' AND sgp2.value='".base64_encode( $password )."'
                    OR sgp1.parameters='api_login_admin' AND sgp1.value='".$login."' 
                        AND sgp2.parameters='api_password_admin' AND sgp2.value='".base64_encode( $password )."'
                    OR sgp1.parameters='api_login_astellia_admin' AND sgp1.value='".$login."' 
                        AND sgp2.parameters='api_password_astellia_admin' AND sgp2.value='".base64_encode( $password )."'");
            
            // Test du resultat de la requète
            if( count( $sqlRes ) === 1 )
            {
                $this->connectionState = TRUE;
                $this->lastConnectionUse = time();
                
                switch ( trim( $sqlRes[0]['login'] ) )
                {
                    case 'api_login_astellia_admin' :
                        $this->connectionLevel = API_CONNECTION_ASTELLIA_ADMIN;
                        break;

                    case 'api_login_admin' :
                        $this->connectionLevel = API_CONNECTION_ADMIN;
                        break;

                    default :
                        $this->connectionLevel = API_CONNECTION_USER;
                        break;
                }
                $this->timeoutInactivity = intval( $this->dbConnection->getOne( 'SELECT value FROM sys_global_parameters WHERE parameters=\'session_time\'' ) * 60 );
            }
            else
            {
                // Erreur de connection
            }
        }
        catch( Exception $e )
        {
            $this->dbConnection = NULL;
        }
    }

    /**
     * Getter de la propriété $connectionState
     * @return Boolean
     */
    public function getConnectionState()
    {
        return $this->connectionState;
    }

    /**
     * Getter de la propriété $connectionLevel
     * @return Integer
     */
    public function getConnectionLevel()
    {
        return $this->connectionLevel;
    }

    /**
     * Test si la connexion est toujours active (timeout)
     * 
     * Cette fonction s'occupe aussi de réarmer le compteur de 
     * durée de la session 
     * 
     * @return Boolean
     */
    public function isConnectionActiv()
    {
        if( $this->connectionState && ( time() - $this->lastConnectionUse ) < $this->timeoutInactivity )
        {
            $this->lastConnectionUse = time();
            return TRUE;
        }
        $this->connectionState = FALSE;
        return FALSE;
    }
} 
