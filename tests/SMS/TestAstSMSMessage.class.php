<?php
/**
 * Classe de test PHPUnit pour la classe AstSMSMessage
 * 
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 août 2011) $
 * $Revision: 28130 $
 */
    require_once( dirname( __FILE__ ).'/../../php/environnement_liens.php' );
    require_once( dirname( __FILE__ ).'/../../class/sms/AstSMSMessage.class.php' );

    class TestAstSMSMessage extends PHPUnit_Framework_TestCase
    {
        public function testConstructorThrowsException()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 1 );
            new AstSMSMessage( "" );
        }

        public function testWithCorrectMessage()
        {
            $str = 'the brown fox jumps over the lazy dog';
            $msgObj = new AstSMSMessage( $str );
            $this->assertEquals( $str, $msgObj->getMessage() );
        }
    }
