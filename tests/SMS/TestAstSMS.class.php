<?php
/**
 * Classe de test PHPUnit pour la classe AstSMS
 * 
 * $Author: o.jousset $
 * $Date: 2011-11-29 17:35:43 +0100 (mar., 29 nov. 2011) $
 * $Revision: 33440 $
 */
    require_once( dirname( __FILE__ ).'/../../php/environnement_liens.php' );
    require_once( dirname( __FILE__ ).'/../../class/sms/AstSMS.class.php' );

    class TestAstSMS extends PHPUnit_Framework_TestCase
    {
        /**
         * L'object AstSMS
         * @var AstSMS
         */
        protected $_sms;

        public function setUp()
        {
            $this->_sms = new AstSMS();
        }
        
        public function assertPreCondition()
        {
           $this->assertSame( 0, $this->_sms->getNbRecipients() );
        }

        /**
         * Test l'ajout de plusieurs destinataires
         */
        public function testAddNewRecipientsToList()
        {
            $this->_sms->addRecipient( new AstPhoneNumber( '+612345678' ) );
            $this->assertSame( 1, $this->_sms->getNbRecipients() );
            $this->_sms->addRecipient( new AstPhoneNumber( '+612345678' ) );
            $this->assertSame( 2, $this->_sms->getNbRecipients() );
            $this->_sms->addRecipient( new AstPhoneNumber( '+612345678' ) );
            $this->assertSame( 3, $this->_sms->getNbRecipients() );
        }

        /**
         * Test le netoyage de la liste des destinataires
         */
        public function testClearRecipientsList()
        {
            $this->_sms->addRecipient( new AstPhoneNumber( '+612345678' ) );
            $this->assertSame( 1, $this->_sms->getNbRecipients() );
            $this->_sms->clearRecipientsList();
            $this->assertSame( 0, $this->_sms->getNbRecipients() );
        }

        /**
         * Test l'ajout d'un message
         */
        public function testAddMessage()
        {
            $this->_sms->setMessage( new AstSMSMessage( 'the brown fox jumps over the lazy dog' ) );
            $this->assertSame( $this->_sms->getMessage()->getMessage(), 'the brown fox jumps over the lazy dog' );
        }
    }
