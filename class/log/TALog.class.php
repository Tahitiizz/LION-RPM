<?php
/**
 *  Classe gérant les Log T&A en base de données
 *
 * $Author: o.jousset $
 * $Date: 2012-02-06 18:07:49 +0100 (lun., 06 fÃ©vr. 2012) $
 * $Revision: 63556 $
 */

require_once( dirname( __FILE__).'/../Database.class.php' );
require_once( dirname( __FILE__).'/../DataBaseConnection.class.php' );

abstract class TALog
{
    const UNLIMITED_MAX_SIZE = 0;
    const LOG_SIZE_ESTIMATE = 100; // On estime à 100 octets la taille d'un log

    /**
     * Taille maximum des log à retourner
     *
     * @access protected
     * @var Integer
     */
    protected $_logMaxSize = 0;

    /**
     * Tableau contenant tous les logs
     *
     * @access protected
     * @var Array
     */
    protected $_listLog;

    /**
     * Objet de connexion à la base de données
     *
     * @access protected
     * @var DataBaseConnection
     */
    protected $_db = null;

    /**
     * Timestamp du début des logs demandé
     *
     * @access protected
     * @var Integer
     */
    protected $_start = 0;

    /**
     * Timestamp de la fin des logs demandé
     *
     * @access protected
     * @var Integer
     */
    protected $_end = 0;

    /**
     * Constructeur de la classe
     *
     * @throws InvalidArgumentException
     * @access public
     * @param  DataBaseConnection db
     * @param  Integer start
     * @param  Integer end
     */
    public function __construct( DataBaseConnection $db,  $start = 0, $end = 0, $maxBytes = self::UNLIMITED_MAX_SIZE )
    {
        $currentTimestamp = time();
        if( ( $start > $currentTimestamp ) || ( $end > $currentTimestamp ) ){
            throw new InvalidArgumentException( 'Start or end time cannot be in the futur', 1 );
        }

        if( ( $start === NULL && $end !== NULL ) || ( $start !== NULL && $end === NULL ) ){
            throw new InvalidArgumentException( 'Both Start and End parameter must be specified', 2 );
        }

        if( ( $start !== NULL && $end !== NULL ) && ( !is_int( $start ) || !is_int( $end ) || $start < 0 || $end < 0 ) ){
            throw new InvalidArgumentException( 'Start and End value must be positives integers', 3 );
        }

        if ( $start > $end ){
            throw new InvalidArgumentException( 'Start value cannot be greater than end value', 4 );
        }

        if( !is_int( $maxBytes ) || $maxBytes < 0 ){
            throw new InvalidArgumentException( 'Max octets value must be positive', 5 );
        }

        $this->_listLog = array();
        $this->_db = $db;
        $this->_logMaxSize = $maxBytes;

        if( $end !== NULL ){
            $this->_end = $end;
        }// Sinon la valeur par défaut est bonne

        if( $start !== NULL ){
            $this->_start = $start;
        }// Sinon la valeur par défaut est bonne

        if( $this->_logMaxSize > 0 )
        {
            $this->readLogWithMaxSize();
        }
        else
        {
            $this->readLog();
        }
    }

    /**
     * Getter de listLog
     *
     * @access private
     * @return Array
     */
    public function getListLog()
    {
        return $this->_listLog;
    }

    /**
     * Getter de start
     *
     * @access public
     * @return Integer
     */
    public function getStart()
    {
        return $this->_start;
    }

    /**
     * Getter de end
     *
     * @access public
     * @return Integer
     */
    public function getEnd()
    {
        return $this->_end;
    }

    /**
     * Getter de logMaxSize
     *
     * @access public
     * @return Integer
     */
    public function getLogMaxSize()
    {
        return $this->_logMaxSize;
    }

    /**
     * Ecriture des logs dans un flux
     * 
     * @abstract
     * @access public
     * @param  String path
     * @return Boolean
     */
    public abstract function createLog( $path = '' );

    /**
     * Lecture des logs en base de données
     *
     * @access private
     * @return Boolean
     */
    protected function readLog()
    {
        $whereClause = '';      
        if( $this->_start != 0 && $this->_end != 0 )
        {
            // 11/02/2011 : bz25499, modfification du format de l'heure en HH24
            $whereClause = 'AND to_timestamp(message_date, \'YYYY/MM/DD HH24:MI:SS\')
                                < to_timestamp(\''.date( 'Y/m/d H:i:s', $this->_end ).'\', \'YYYY/MM/DD HH24:MI:SS\')
                            AND to_timestamp(message_date, \'YYYY/MM/DD HH24:MI:SS\')
                                > to_timestamp(\''.date( 'Y/m/d H:i:s', $this->_start ).'\', \'YYYY/MM/DD HH24:MI:SS\')';

        }
        else
        {
            // Pas de filtre sur les dates, on récupère tout
        }
        $this->_listLog = $this->_db->getAll
                        (
                            'SELECT message_date,severity,application,module,message,type_message,object
                            FROM sys_log_ast
                            WHERE module != \'\' '.$whereClause.' ORDER BY message_date DESC;'
                        );
    }

    /**
     * Lecture des logs en base de données avec taille maximum
     *
     * @access private
     * @return Boolean
     */
    protected function readLogWithMaxSize()
    {
        $fetchStep = max( floor( $this->_logMaxSize / self::LOG_SIZE_ESTIMATE ), 1 );
        $whereClause = '';
        $localSize = 0;
        $localArrResult = array();
        
        if( $this->_start != 0 && $this->_end != 0 )
        {
            $whereClause = 'AND to_timestamp(message_date, \'YYYY/MM/DD HH:MI:SS\')
                                < to_timestamp(\''.date( 'Y/m/d H:i:s', $this->_end ).'\', \'YYYY/MM/DD HH:MI:SS\')
                            AND to_timestamp(message_date, \'YYYY/MM/DD HH:MI:SS\')
                                > to_timestamp(\''.date( 'Y/m/d H:i:s', $this->_start ).'\', \'YYYY/MM/DD HH:MI:SS\')';

        }
        else
        {
            // Pas de filtre sur les dates, on récupère tout
        }
        $this->_db->execute( 'BEGIN WORK;' );
        $this->_db->execute( 'DECLARE logcurs SCROLL CURSOR FOR
                                    SELECT message_date,severity,application,module,message,type_message,object
                                    FROM sys_log_ast
                                    WHERE module != \'\' '.$whereClause.' ORDER BY message_date DESC;'
                                );

        do
        {
            $localArrResult = $this->_db->getAll( 'FETCH FORWARD '.$fetchStep.' FROM logcurs;' );
            foreach( $localArrResult as $oneRes )
            {
                foreach( array_values( $oneRes ) as $value )
                {
                    $localSize += strlen( $value );
                }
            }
            $this->_listLog = array_merge( $this->_listLog, $localArrResult );
            $fetchStep = max( floor( ( $this->_logMaxSize - $localSize ) / self::LOG_SIZE_ESTIMATE ), 1 );
        }
        while( ( $localSize < $this->_logMaxSize ) && ( count( $localArrResult ) > 0 ) );

        while( $localSize > $this->_logMaxSize )
        {
            foreach( array_values( array_pop( $this->_listLog ) ) as $value )
            {
                $localSize -= strlen( $value );
            }
        }
        $this->_db->execute( 'CLOSE logcurs;' );
        $this->_db->execute( 'COMMIT WORK;' );
    }
}
