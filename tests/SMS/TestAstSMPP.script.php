<?php
/**
 * Script testant la classe AstSMPP
 * @copyright Copyright (c) 2011, Astellia
 * @since 5.0.7.00
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */
require_once( dirname( __FILE__ ).'/../../class/sms/AstSMPP.class.php' );

try
{
    $mySMSC = new AstSMSC( '10.49.0.149', 'login', 'password', 5018 );
    $mySMS = new AstSMS();
}
catch( Exception $e )
{
    die( $e->getMessage() );
}
$mySMS->addRecipient( new AstPhoneNumber( '+33675697924' ) );
$mySMS->setMessage( new AstSMSMessage( 'Test' ) );

$mySMPP = new AstSMPP( $mySMSC );

if( $mySMPP->bind() )
{
    if( $mySMPP->testLink() )
    {
        if( $mySMPP->sendMessage( $mySMS ) )
        {
            echo 'Message envoyé';
            $mySMPP->unbind();
        }
        else
        {
            echo 'Erreur lors de l\'envoi';
        }
    }
    else
    {
        echo 'Erreur problème lors du test du lien';
    }
}
else
{
    echo 'Erreur problème de connexion au SMS-C';
}