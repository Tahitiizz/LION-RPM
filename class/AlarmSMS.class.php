<?php
/**
 * Class permtettant de gérer l'envoi de SMS pour les alarmes
 * @copyright Copyright (c) 2011, Astellia
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */


class AlarmSMS
{
    /**
     * Objet pour la connexion à la base de données
     * @var BataBaseConnection
     */
    protected $_db;

    /**
     * Tableau comportant les TA en cours de calcul
     * @var array
     */
    protected $_timeToCalculate;

    /**
     * Tableau des resultats d'alarmes SMS (avec tous les patternes nécessaires en clés)
     * @var array
     */
    protected $_alarmsResults;

    /**
     * Nibeau de criticité minimum des alarmes
     * @var type
     */
    protected $_minLevel = 'major';

    /**
     * Retard maximum accepté des alarmes
     * @var type
     */
    protected $_maxDelay = 3;

    /**
     * Constructueur de la classe
     *
     * @param DataBaseConnection $db Ressource de connexion à la base
     * @param string Criticité minimum des alarmes
     * @param integer Retard maximum accecpté
     */
    public function __construct( DataBaseConnection $db, $minLevel = 'major', $maxDelay = 3 )
    {
        // Initialisation des variables
        $this->_db              = $db;
        $this->_timeToCalculate = get_time_to_calculate( get_sys_global_parameters( 'offset_day' ) );
        $this->_alarmsResults   = array();
        $this->_maxDelay        = $maxDelay;
        $this->_minLevel        = $minLevel;
    }

    /**
     * Retourne une liste d'identifiants d'alarme concernés par la/les TA en cours
     * Cette liste ne prend pas en compte les résultats des alarmes.
     *
     * @return array
     */
    protected function getSMSAlarms( $type )
    {
        $idAlarmsResult = array();
        $taList = array_keys( $this->_timeToCalculate );

        if( count( $taList ) > 0 )
        {
            $taListStr = "'".implode( "','", $taList )."'";
             // Construction de la requête listant les alarmes SMS pour la/les TA
             // concernées (indépendamment des résultats des alarmes)
            $query = "SELECT DISTINCT(alarm_id) FROM sys_definition_alarm_{$type}
                      WHERE time IN ({$taListStr})
                      INTERSECT SELECT DISTINCT(id_alarm) FROM sys_alarm_sms_sender 
                      WHERE alarm_type='alarm_{$type}'";
            $res = $this->_db->getAll( $query );
            if( $res != NULL )
            {
                foreach( $res as $oneAlarm )
                {
                    $idAlarmsResult []= $oneAlarm['alarm_id'];
                }
            }
        }
        return $idAlarmsResult;
    }


    /**
     * Récupère une liste d'alarmes SMS (en focntion des résultats et des critères)
     * 
     * @return array La liste des alarmes
     */
    public function getSMSAlarmsResult()
    {
        $alarmType = array( 'static', 'dynamic' );
        // 2014/09/22 NSE bz 42894: add of translation array for alarm type
        $alarmTypeEdw = array( 'static'=>'static', 'dynamic'=>'dyn_alarm' );
        
        // Pour toutes les TA
        foreach( $this->_timeToCalculate as $ta => $values )
        {
            foreach( $alarmType as $type )
            {
                $alarmDefiniton = $this->getSMSAlarms( $type );
                if( count( $alarmDefiniton ) > 0 )
                {
                    $alarmListStr = "'".implode( "','", $alarmDefiniton )."'";
                    if( is_array( $values ) )
                    {
						$ta_condition = "AND ta_value IN (".( implode( ", ", $values ) ).")";
					}
					else
					{
						$ta_condition = "AND ta_value = ".$values;
					}
                    $query = "SELECT id_alarm,COUNT(id_alarm) as nb_ala,critical_level,ta_value
                        FROM edw_alarm WHERE ta='{$ta}' {$ta_condition}
                        AND alarm_type='{$alarmTypeEdw[$type]}'
                        AND id_alarm IN ({$alarmListStr})
                        GROUP BY id_alarm,critical_level,ta_value";
              
                    $res = $this->_db->getAll( $query );
                    if( $res != NULL )
                    {
                        foreach( $res as $alarmRes )
                        {
                            $tmpArray['ALA_ID'] = $alarmRes['id_alarm'];
                            $tmpArray['ALA_TA'] = $ta;
                            $tmpArray['ALA_TYPE'] = $type;
                            $tmpArray['DATE'] = $alarmRes['ta_value'];
                            $tmpArray[$alarmRes['critical_level']] = $alarmRes['nb_ala'];
                            $this->_alarmsResults []= $tmpArray;
                        }
                    }
                }
                else
                {
                    // Aucune alarme SMS de définie pour ce type
                }
            }
        }

        // Mise à jour du tableau de résultat
        $this->formatAlarmsResults();

        // Suppression des alarmes avec criticités trop faibles ou trop anciennes
        $this->filterAlarmsResults();

        return $this->_alarmsResults;
    }

    /**
     * Modifie le tableau des résultats en ajoutant les informations nécessaires
     * pour chaque alarme. Le tableau de sortie contiendra tous les patternes
     * nécessaires à la création du message SMS.
     *
     * Le tableau de sortie à la format suivant :
     *
     * $_alarmsResults[X][ALA_ID]
     *                   [NB_ALA]
     *                   [NB_CRI]
     *                   [NB_MAJ]
     *                   [NB_MIN]
     *                   [APP_NAME]
     *                   [ALA_TYPE]
     *                   [DATE]
     *                   [IP]
     */
    protected function formatAlarmsResults()
    {
        $ip      = get_adr_server();
        $appName = get_sys_global_parameters( 'product_name' );

        // On itère sur toutes les alarmes (passage en référence obligatoire)
        foreach( $this->_alarmsResults as $key => &$alarmDetails )
        {
            $alaModel                 = new AlarmModel( $alarmDetails['ALA_ID'], $alarmDetails['ALA_TYPE'] );
            $alarmDetails['ALA_NAME'] = $alaModel->getValue( 'alarm_name' );
            $alarmDetails['IP']       = $ip;
            $alarmDetails['APP_NAME'] = $appName;

            // Mise à jour pour les patterns NB_CRI, NB_MAJ et NB_MIN
            $alarmDetails['NB_CRI'] = 0;
            $alarmDetails['NB_MAJ'] = 0;
            $alarmDetails['NB_MIN'] = 0;
            if( isset( $alarmDetails['critical'] ) )
            {
                 $alarmDetails['NB_CRI'] = intval( $alarmDetails['critical'] );
                 unset ( $alarmDetails['critical'] );
            }
            if( isset( $alarmDetails['major'] ) )
            {
                 $alarmDetails['NB_MAJ'] = intval( $alarmDetails['major'] );
                 unset ( $alarmDetails['major'] );
            }
            if( isset( $alarmDetails['minor'] ) )
            {
                 $alarmDetails['NB_MIN'] = intval( $alarmDetails['minor'] );
                 unset ( $alarmDetails['minor'] );
            }

            $alarmDetails['NB_ALA'] = ( $alarmDetails['NB_MIN'] + $alarmDetails['NB_MAJ'] + $alarmDetails['NB_CRI'] );
        }
    }

    /**
     * Filtre les résultats d'alarmes en supprimant les entrées ne correspondant
     * pas au critères sur la criticité minimum ou le retard maximum
     */
    protected function filterAlarmsResults()
    {
        if( $this->_minLevel != 'minor' || $this->_maxDelay != -1 )
        {
            foreach( $this->_alarmsResults as $num => $alarmDetails )
            {
                // Test sur le niveau de criticité min
                if( ( $this->_minLevel == 'critical' && $alarmDetails['NB_CRI'] == 0 ) ||
                    ( $this->_minLevel == 'major' && $alarmDetails['NB_CRI'] == 0 && $alarmDetails['NB_MAJ'] == 0 )
                  )
                {
                    // Suppression de l'alarme (criticité insiffisante)
                    unset( $this->_alarmsResults[$num] );
                }

                // Test sur le retard max
                if( $this->_maxDelay != -1 )
                {
                    // Initialisation de la TA (gestion de la busy hour)
                    $ta        = str_replace( '_bh', '', $alarmDetails['ALA_TA'] );

                    // Calcul de la date limite autorisée
                    $limitDate = strtotime( "-{$this->_maxDelay} $ta" );
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                    $dates     = array( 'hour'=>'YmdH', 'day'=>'Ymd', 'week'=>'oW', 'month'=>'Ym');

                    // Si la date de l'alarme est inférieure à la date autorisée
                    if( $alarmDetails['DATE'] < date( $dates[$ta], $limitDate ) )
                    {
                        // Suppression de l'alarme (retard trop important)
                        unset( $this->_alarmsResults[$num] );
                    }  
                }
            }
        }
        else
        {
            // Si la criticité est 'minor' et le max delay à -1, on accèpte toutes
            // les alarmes
        }
    }

    /**
     * Retourne la liste des déstinataires pour une alarmes SMS
     *
     * @param string $idAlarm Identifiant de l'alarme
     * @param string $alarmType Type de l'alarme
     * @return array Tableau associatif (login=>phone_number)
     */
    public static function getAlarmRecipients( $idAlarm, $alarmType )
    {
        $db = Database::getConnection();
        $retArray = array();

        // Uniion des deux requêtes (group + user)
        $query = "SELECT phone_number,login FROM users
                    WHERE id_user IN (SELECT recipient_id FROM sys_alarm_sms_sender
                                    WHERE id_alarm='{$idAlarm}'
                                    AND alarm_type='alarm_{$alarmType}'
                                    AND recipient_type='user'
                                )
                UNION
                SELECT phone_number,login FROM users
                    WHERE id_user IN (SELECT id_user FROM sys_user_group
                                        WHERE id_group
                                        IN (SELECT recipient_id FROM sys_alarm_sms_sender
                                            WHERE id_alarm='{$idAlarm}'
                                            AND alarm_type='alarm_{$alarmType}'
                                            AND recipient_type='group')
                                            )";
        $res = $db->getAll( $query );
        if( $res != NULL )
        {
            foreach( $res as $oneRecipient )
            {
                // On évite les doublons (même user dans plusieurs groupes...)
                if( !array_key_exists( $oneRecipient['login'], $retArray ) )
                {
                    $retArray[$oneRecipient['login']] = $oneRecipient['phone_number'];
                }
            }
        }
        return $retArray;
    }
}
