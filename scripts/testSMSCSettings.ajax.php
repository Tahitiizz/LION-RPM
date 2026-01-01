<?php
/**
 * Script effectuant un test de connexion vers un SMS-C
 * @copyright Copyright (c) 2011, Astellia
 *
 * $Author: a.cremades $
 * $Date: 2011-11-03 09:37:29 +0100 (jeu., 03 nov. 2011) $
 * $Revision: 28395 $
 */

require_once dirname( __FILE__ )."/../php/environnement_liens.php";
require_once REP_PHYSIQUE_NIVEAU_0.'class/sms/AstSMPP.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';

// Vérification de la présence de tous les paramètres nécéssaires
if( isset( $_GET['host'] ) && isset( $_GET['port'] ) && isset( $_GET['id'] )
        && isset( $_GET['pass'] ) && isset( $_GET['type'] ) && isset( $_GET['product'] ) )
{
    $host    = $_GET['host'];
    $port    = intval( $_GET['port'] );
    $id      = $_GET['id'];
    $pass    = $_GET['pass'];
    $type    = $_GET['type'];
    $product = $_GET['product'];
}
else if( count( $argv ) === 7 )
{
    $host    = $argv[1];
    $port    = intval( $argv[2] );
    $id      = $argv[3];
    $pass    = $argv[4];
    $type    = $argv[5];
    $product = $argv[6];
}
else
{
    // Erreur improbable mais on affiche un message
    die( "Error in SMS-C settings (internal error, missing parameters)" );
}

// Si le produit à tester est un slave (bz23235)
if( ProductModel::isSlave( $product ) )
{
    $prodModel = new ProductModel( $product );
    $prodInfos = $prodModel->getValues();

    // Test si le slave est distant
    if( $prodInfos['sdp_ip_address'] != get_adr_server() )
    {
        // Etablissement d'une connexion SSH afin de tester la connexion au
        // SMS-C depuis le slave (et non depuis le master)
        try
        {
            $sshCon = new SSHConnection( $prodInfos['sdp_ip_address'], $prodInfos['sdp_ssh_user'], $prodInfos['sdp_ssh_password'], $prodInfos['sdp_ssh_port'] );
            $retArray = $sshCon->exec( "php /home/{$prodInfos['sdp_directory']}/scripts/".basename( __FILE__ )." '{$host}' '{$port}' '{$id}' '{$pass}' '{$type}' '{$product}'" );
            if( count( $retArray ) >  0 )
            {
                echo $retArray[0];
            }
            else
            {
                echo "SMS-C connection failed (no response from slave product)";
            }
        }
        catch( Exception $e )
        {
            echo "SMS-C connection failed, (SSH connection to slave product error)";
        }

        // Fin du script ici pour les slaves distants
        exit();
    }
}

try
{
    // 26/09/2011 OJT : bz23921, gestion des erreurs et d'un mode debug SMPP
    $smscObj        = new AstSMSC( $host, $id, $pass, $port, $type );
    $smppObj        = new AstSMPP( $smscObj, ( get_sys_debug( 'debug_global', $product ) == 1 ) );
    $connectionFlag = false;

    // Tentative de connexion
    if( $smppObj->bind() )
    {
        // Envoi d'une trame de test
        if( $smppObj->testLink() )
        {
            $connectionFlag = true;
            echo "SMS-C connection successful";
        }

        // Déconnexion du SMSC
        $smppObj->unbind();
    }

    // Si la tentative a échouée
    if( !$connectionFlag )
    {
        // Affichage de l'erreur avec détail de l'erreur
        echo "SMS-C connection failed ({$smppObj->getStrLastError()})";
    }
}
catch( Exception $e )
{
    echo "Error in SMS-C settings ({$e->getMessage()})";
}
