<?php
/**
 * Classe de test PHPUnit pour la classe AstSMSC
 * 
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 août 2011) $
 * $Revision: 28130 $
 */
    require_once( dirname( __FILE__ ).'/../../php/environnement_liens.php' );
    require_once( dirname( __FILE__ ).'/../../class/sms/AstSMSC.class.php' );

    class TestAstSMSC extends PHPUnit_Framework_TestCase
    {
        public function testConstructorThrowsException()
        {
            $this->setExpectedException( 'InvalidArgumentException', NULL, 4 );
            new AstSMSC( 'HOST', 'SID', 'PWD', 0, 'STYPE');
        }

        public function testPortDefaultValue()
        {
            $smsc = new AstSMSC( 'HOST', 'SID', 'PWD' );
            $this->assertSame( AstSMSC::DEFAULT_SMSC_PORT, $smsc->getPort() );
        }

        public function testSavedValues()
        {
            $smsc = new AstSMSC( 'HOST', 'SID', 'PWD', 10, 'STYPE');
            $this->assertSame( 'HOST', $smsc->getHost() );
            $this->assertSame( 'SID', $smsc->getSystemId() );
            $this->assertSame( 'PWD', $smsc->getPassword() );
            $this->assertSame( 10, $smsc->getPort() );
            $this->assertSame( 'STYPE', $smsc->getSystemType() );
        }
    }
