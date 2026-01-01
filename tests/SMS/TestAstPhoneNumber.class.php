<?php
/**
 * Classe de test PHPUnit pour la classe AstPhoneNumber
 * 
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 août 2011) $
 * $Revision: 28130 $
 */
    require_once( dirname( __FILE__ ).'/../../php/environnement_liens.php' );
    require_once( dirname( __FILE__ ).'/../../class/sms/AstPhoneNumber.class.php' );

    class TestAstPhoneNumber extends PHPUnit_Framework_TestCase
    {
        public function testConstructorThrowsException()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 1 );
            new AstPhoneNumber( '+33675697924p' );
        }

        public function testWithCorrectPhoneNumber()
        {
            $str = '+33675697924';
            $pN = new AstPhoneNumber( $str );
            $this->assertEquals( $str, $pN->getPhoneNumber() );
        }

        public function testWithAllAllowedCharacters()
        {
            $pN = new AstPhoneNumber( "6+56 4(2)-.4" );
        }
    }
