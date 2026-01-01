<?php
/**
 * Script effectuant le traitement des alarmes SMS.
 * 
 * /!\ Attention ce script ne doit pas comporter de exit ou tout autres arrets
 * brutaux au risques de stopper l'intégration en cours
 * 
 * @copyright Copyright (c) 2011, Astellia
 *
 * $Author: a.cremades $
 * $Date: 2011-11-03 09:37:29 +0100 (jeu., 03 nov. 2011) $
 * $Revision: 28395 $
 */

require_once dirname(__FILE__)."/../php/environnement_liens.php";
require_once $repertoire_physique_niveau0."class/AlarmSMS.class.php";
require_once $repertoire_physique_niveau0."class/sms/AstSMPP.class.php";

// On test si la fonctionnalité SMS est bien activée
if( get_sys_global_parameters( 'enable_alarm_sms', 0 ) == 1 )
{
    displayInDemon( "Alarm SMS enabled, starting treatment..." );

    // Lecture en base de la criticité minimum et du retard max
    $minLevel = get_sys_global_parameters( 'alarm_sms_minimum_severity', 'major' );
    $maxDelay = intval( get_sys_global_parameters( 'alarm_sms_maximum_delay', 3 ) );
    displayInDemon( "Minimum criticity is [{$minLevel}] with maximum delay of [{$maxDelay}] time aggregation" );

    // Récupération des alarmes à envoyer
    $alarmSMS     = new AlarmSMS( Database::getConnection(), $minLevel, $maxDelay );
    $alarmSMSList = $alarmSMS->getSMSAlarmsResult();

    // Au moins une alarme est-elle à envoyer ?
    if( ( $nbRes = count( $alarmSMSList ) ) > 0 )
    {
        displayInDemon( "{$nbRes} SMS alarms found with results, sending SMS..." );

        // Création de l'objet AstSMSC
        $smscObj = NULL;
        $host    = get_sys_global_parameters( 'smsc_host' );
        $pass    = get_sys_global_parameters( 'smsc_password' );
        $port    = intval( get_sys_global_parameters( 'smsc_port' ) );
        $sId     = get_sys_global_parameters( 'smsc_system_id' );
        $sType   = get_sys_global_parameters( 'smsc_system_type' );

        try
        {
            // 27/09/2011 OJT : bz23958, décodage du mot de passe avant création de l'objet
            $smscObj = new AstSMSC( $host, $sId, base64_decode( $pass ), $port, $sType );
        }
        catch( Exception $e )
        {
            $smscObj = NULL;
            displayInDemon( "Error in SMS-C settings ({$e->getMessage()})", 'alert' );
            sys_log_ast( "Critical", get_sys_global_parameters( "system_name" ), __T("A_TRACELOG_MODULE_LABEL_ALARM"), __T( "SMS_BINDING_ERROR", $host, $port ), "support_1", "");
        }

        // Si l'objet AstSMSC a bien été initialisé
        if( $smscObj != NULL )
        {
            // Création de l'object AstSMPP (avec un éventuel debug, bz23921)
            $smppObj = new AstSMPP( $smscObj, ( get_sys_debug( 'debug_global', $product ) == 1 ) );

            // Un sender est-il à initialiser
            $smsSender = get_sys_global_parameters( 'alarm_sms_sender' );
            if( strlen( trim ( $smsSender ) ) > 0 )
            {
                try
                {
                    $smppObj->setSender( new AstPhoneNumber( $smsSender ) );
                }
                catch( Exception $e )
                {
                    // Le numéro de portable est erroné. Ce n'est pas critique, on
                    // écrit juste un Warning
                    displayInDemon( "WARNING : SMS sender phone number is wrong ({$smsSender})", 'alert' );
                    sys_log_ast( "Warning", get_sys_global_parameters( "system_name" ), __T( "A_TRACELOG_MODULE_LABEL_ALARM" ), "WARNING : SMS sender phone number is wrong ({$smsSender})", "support_1", "");
                }
            }

            // Connexion au SMSC
            if( $smppObj->bind() == true )
            {  
                // Format du message
                $msgFormatIni = get_sys_global_parameters( "alarm_sms_format" );

                // Avant d'itérer sur toutes les alarmes, on initialise un tableau
                // contenant la liste des logins ayant eux des erreurs. Cela permet
                // de n'enregistrer qu'un seul message dans le démon et tracelog
                $loginError = array();

                // On itère sur toutes les alarmes
                foreach( $alarmSMSList as $num => $alarmDetail )
                {
                    displayInDemon( "<h3>Sending SMS for alarm [{$alarmDetail['ALA_NAME']}] ({$alarmDetail['ALA_ID']})</h3>" );

                    // Création de l'objet AstSMS
                    $smsObj = new AstSMS();

                    // Définition du message
                    $msgFormat = $msgFormatIni;
                    $msgFormat = get_sys_global_parameters( "alarm_sms_format" );
                    $msgFormat = str_replace( "[NB_ALA]", $alarmDetail['NB_ALA'], $msgFormat );
                    $msgFormat = str_replace( "[NB_CRI]", $alarmDetail['NB_CRI'], $msgFormat );
                    $msgFormat = str_replace( "[NB_MAJ]", $alarmDetail['NB_MAJ'], $msgFormat );
                    $msgFormat = str_replace( "[NB_MIN]", $alarmDetail['NB_MIN'], $msgFormat );
                    $msgFormat = str_replace( "[APP_NAME]", $alarmDetail['APP_NAME'], $msgFormat );
                    $msgFormat = str_replace( "[ALA_TYPE]", $alarmDetail['ALA_TYPE'], $msgFormat );
                    $msgFormat = str_replace( "[ALA_NAME]", $alarmDetail['ALA_NAME'], $msgFormat );
                    $msgFormat = str_replace( "[DATE]", $alarmDetail['DATE'], $msgFormat );
                    $msgFormat = str_replace( "[IP]", $alarmDetail['IP'], $msgFormat );
                    displayInDemon( "message is [$msgFormat]", 'list' );
                    $smsObj->setMessage( new AstSMSMessage( $msgFormat ) );

                    // Liste des destinataires du SMS
                    $phoneNumberList = AlarmSMS::getAlarmRecipients( $alarmDetail['ALA_ID'], $alarmDetail['ALA_TYPE'] );
                    foreach( $phoneNumberList as $login => $pN )
                    {
                    	// 17/10/2013 GFS - Bug 35358 - [SUP][T&A GSM][ AVP 35543][Zain Bahrein] : SMS sending is too fast 
                    	// Ajout d'un paramètre pour contrôler l'envoie de SMS
                    	sleep(get_sys_global_parameters( 'alarm_sms_delay', 0 ));
                        $smsObj->clearRecipientsList();
                        if( strlen( trim( $pN ) ) > 0 )
                        {
                            try
                            {
                                $smsObj->addRecipient( new AstPhoneNumber( $pN ) );
                                if( $smppObj->sendMessage( $smsObj ) == false )
                                {
                                    // 27/09/2011 OJT : bz23921, détail du message d'erreur
                                    $tmpMsg = __T( "SMS_SENDING_USER_ERROR", $login, $smppObj->getStrLastError() );
                                    displayInDemon( $tmpMsg, 'list' );
                                    sys_log_ast( "Warning", get_sys_global_parameters( "system_name" ), __T( "A_TRACELOG_MODULE_LABEL_ALARM" ), $tmpMsg, "support_1", "" );
                                }
                                else
                                {
                                    displayInDemon( "SMS sent to {$login}", 'list' );
                                }
                            }
                            catch( Exception $e )
                            {
                                // Le numéro est mauvais
                                if( !in_array( $login, $loginError ) )
                                {
                                    // 23/09/2014 NSE bz 42921 : le mail était réaffiché à la place du numéro de téléphone
                                    displayInDemon( __T( 'SMS_SENDING_WRONG_PHONE_NUMBER', $login, $pN ), 'list' );
                                    sys_log_ast( "Warning", get_sys_global_parameters( "system_name" ), __T( "A_TRACELOG_MODULE_LABEL_ALARM" ), __T( 'SMS_SENDING_WRONG_PHONE_NUMBER', $login, $pN ), "support_1", "" );
                                    $loginError []= $login;
                                }
                            }
                        }
                        else
                        {
                            if( !in_array( $login, $loginError ) )
                            {
                                displayInDemon( __T( 'SMS_SENDING_MISSING_PHONE_NUMBER', $login ), 'list' );
                                sys_log_ast( "Warning", get_sys_global_parameters( "system_name" ), __T( "A_TRACELOG_MODULE_LABEL_ALARM" ), __T( 'SMS_SENDING_MISSING_PHONE_NUMBER', $login ), "support_1", "" );
                                $loginError []= $login;
                            }
                        }
                    }
                    unset( $smsObj );
                }
                $smppObj->unbind(); // Déconnexion du SMS-C
            }
            else
            {
                // 27/09/2011 OJT : bz23921, détail du message d'erreur
                $tmpMsg = __T( "SMS_BINDING_ERROR", $smppObj->getStrLastError() );
                displayInDemon( $tmpMsg, 'alert' );
                sys_log_ast( "Critical", get_sys_global_parameters( "system_name" ), __T("A_TRACELOG_MODULE_LABEL_ALARM"), $tmpMsg, "support_1", "");
            }
        }
    }
    else
    {
         displayInDemon( "No results for SMS alarms" );
    }
}
else
{
    displayInDemon( "Alarm SMS disabled" );
}
