<?php
/**
 * Script effectuant la vérification d'un numéro de téléphone
 * @copyright Copyright (c) 2011, Astellia
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */

require_once dirname(__FILE__)."/../php/environnement_liens.php";
require_once REP_PHYSIQUE_NIVEAU_0.'/class/sms/AstPhoneNumber.class.php';

// Vérification du numéro de portable (DE SMS)
if( isset( $_GET['phone_number'] ) )
{
    // On instancie juste un objet AstPhoneNumber, si une erreur est présente,
    // une exception sera levée avec le message d'erreur adéquat.
    try
    {
        new AstPhoneNumber( $_GET['phone_number'] );
        die( 'ok' );
    }
    catch( Exception $e )
    {
        die( __T( SMS_PHONE_NUMBER_ERROR ) );
    }
}
