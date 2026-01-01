<?php
/**
 * Script effectuant l'envoi d'un SMS de test.
 * 
 * @copyright Copyright (c) 2011, Astellia
 *
 * $Author: a.cremades $
 * $Date: 2011-11-03 09:37:29 +0100 (jeu., 03 nov. 2011) $
 * $Revision: 28395 $
 * 
 * 19/10/2011 BBX
 * Réorganisation du script pour gérer le bug BZ 24266
 */

require_once dirname(__FILE__)."/../php/environnement_liens.php";
require_once REP_PHYSIQUE_NIVEAU_0.'/class/sms/AstSMPP.class.php';
require_once REP_PHYSIQUE_NIVEAU_0.'/class/SSHConnection.class.php';

// Ajax Part
if(isset($_GET['action']))
{   
    // Identifying involved products
    if($_GET['action'] == 'identifyProducts') {
        echo implode('|',ProductModel::getProductsWithSMSC(true));
    }
    // Launching test
    elseif($_GET['action'] == 'launchTest') 
    {
        // Le paramètre GET doît être présent
        if( !isset( $_GET['phone_number'] ) )
        {
            die( 'Internal error, can not send SMS' );
        }
        // Master Information
        $masterModel = new ProductModel(ProductModel::getIdMaster());
        $masterValues = $masterModel->getValues();
        // Fetching product
        $product = $_GET['product'];
        $productModel = new ProductModel($product);
        $ProductValues = $productModel->getValues();
        // Command
        $command = 'php /home/'.$ProductValues['sdp_directory'].'/scripts/sendTestSMS.ajax.php "'.$_GET['phone_number'].'"';
        // Test Local
        if($masterValues['sdp_ip_address'] == $ProductValues['sdp_ip_address'])
        {
            $result = exec($command);
            echo $result;
        }
        // Test distant
        else
        {
            $ssh = new SSHConnection($ProductValues['sdp_ip_address'], $ProductValues['sdp_ssh_user'], $ProductValues['sdp_ssh_password'], $ProductValues['sdp_ssh_port']);
            $result = $ssh->exec($command);
            echo $result[0];
        }
    }
    // Exit
    exit;
}

// Command Line Part
if($argc > 1)
{
    // Phone number
    $number  = $argv[1];
    
    // Initialisation des variables
    $astPN      = NULL;
    $astSMSC    = NULL;
    $astSMSMsg  = NULL;
    $astSMS     = NULL;
    $smsMessage = __T( 'SMS_TEST_MESSAGE', get_sys_global_parameters( 'product_name', 'T&A application' ), get_adr_server() );
    $smscHost   = get_sys_global_parameters( 'smsc_host' );
    $smscPort   = intval( get_sys_global_parameters( 'smsc_port' ) );
    $smscSId    = get_sys_global_parameters( 'smsc_system_id' );
    $smscPasswd = get_sys_global_parameters( 'smsc_password' );
    $smscSType  = get_sys_global_parameters( 'smsc_system_type' );
    // 18/10/2011 BBX
    // BZ 24252 : On récupère également le sender
    $smsSender  = get_sys_global_parameters( 'alarm_sms_sender' );

    try
    {
        // 26/09/2011 OJT : bz23921, gestion des erreurs et d'un mode debug SMPP
        $astPN     = new AstPhoneNumber( $number );
        $astSMSMsg = new AstSMSMessage( $smsMessage );
        $astSMS    = new AstSMS();
        $astSMSC   = new AstSMSC( $smscHost, $smscSId, base64_decode( $smscPasswd ), $smscPort, $smscSType );
        $astSMPP   = new AstSMPP( $astSMSC, ( get_sys_debug( 'debug_global' ) == 1 ) );

        // 18/10/2011 BBX
        // BZ 24252 : On récupère également le sender
        // 19/04/2012 BBX
        // BZ 25888 : gestion de l'erreur sur le sender
        try {
            $astSMPP->setSender(new AstPhoneNumber( $smsSender ));
        }
        catch( Exception $s ) {
            echo __T( 'SMS_SENDER_NUMBER_PROBLEM' );
            echo __T( 'SMS_PHONE_NUMBER_ERROR' );
            exit;
        }

        // Ajout du message et du destinataire du SMS
        $astSMS->setMessage( $astSMSMsg );
        $astSMS->addRecipient( $astPN );
    }
    catch( Exception $e )
    {
        $trace = $e->getTrace();
        switch ( $trace[0]['class'] )
        {
            case "AstPhoneNumber" :
                // Le numéro de téléphone est erroné, on affiche le message standard
                echo __T( 'SMS_PHONE_NUMBER_ERROR' );
                break;

            case "AstSMSC" :
                // Si une exception intervient, le SMS-C est mal configuré
                echo "Error in SMS-C settings ({$e->getMessage()})";
                break;

            default :
                // Erreur inconnue (improbable), on affiche tout de même une erreur
                echo "Error while sending SMS (internal configuration error)";
                break;
        }
        exit;
    }

    // Toutes les variables et objets sont bien initialisés, tentative de connexion
    if( $astSMPP->bind() )
    {
        if( $astSMPP->sendMessage( $astSMS ) )
        {
            echo "ok|".__T( 'SMS_TEST_SUCCESSFULLY_SENT', $astPN->getPhoneNumber() );
        }
        else
        {
            // 27/09/2011 OJT : bz23921, détail du message d'erreur
            echo "Error while sending SMS ({$astSMPP->getStrLastError()})";
        }
        $astSMPP->unbind(); // déconnexion au SMS-C
    }
    else
    {
        // 27/09/2011 OJT : bz23921, détail du message d'erreur
        echo __T( "SMS_BINDING_ERROR", $astSMPP->getStrLastError() );
    }
}



