<?php
/**
 *  Classe gérant les Log T&A transformé au format Astellia
 *
 * $Author: o.jousset $
 * $Date: 2010-09-20 15:56:44 +0200 (lun., 20 sept. 2010) $
 * $Revision: 27105 $
 */
require_once( dirname( __FILE__).'/TALog.class.php' );


abstract class TALogAst extends TALog
{
    public function __construct( DataBaseConnection $db,  $start = 0, $end = 0, $maxBytes = 0 )
    {
        parent::__construct( $db, $start, $end, $maxBytes ); // Appel du constructeur parent
        $this->formatLog(); // Plus mise au format Astellia
    }

    /**
     * Mise au format Astellia
     *
     * @access private
     * @return Boolean
     *
     * 20/09/2010 OJT : bz18050, modification du format de la date (mise au format Astellia)
     */
    private function formatLog()
    {
        $appliName = $this->_db->getOne( 'SELECT sdp_label || \'(\' || sdp_ip_address || \')\'
                                        FROM sys_definition_product
                                        WHERE sdp_db_name=\''.$this->_db->getDbName().'\';'
                                );
        $newLogArray = array();
        for( $i = 0 ; $i < count( $this->_listLog ) ; $i++  )
        {
            $newLogArray[$i]['timestamp'] = date( 'Y/m/d H:i:s', strtotime( $this->_listLog[$i]['message_date'] ) );
            $newLogArray[$i]['appli'] = $appliName;
            $newLogArray[$i]['severity'] = $this->_listLog[$i]['severity'];
            $newLogArray[$i]['msggroup'] = $this->_listLog[$i]['module'];
            $newLogArray[$i]['object'] = '';
            $newLogArray[$i]['astlog'] = str_replace( "\t", ' ', $this->_listLog[$i]['message'] );
        }
        unset( $this->_listLog );
        $this->_listLog = $newLogArray;
    }
}
