<?php
/**
 * Classe de test général T&A
 *
 * $Author: o.jousset $
 * $Date: 2012-02-06 18:07:49 +0100 (lun., 06 fÃ©vr. 2012) $
 * $Revision: 63556 $
 *
 */

require_once( 'hi/TestHealthIndicator.class.php' ); // Pour les indicateurs de santé
require_once( 'log/TestTALogAstFile.class.php' ); // Pour les Log au format Astellia
require_once( 'api/TestApiTa.class.php' ); // Pour l'API T&A
require_once( 'alarm/TestAlarmCalculation.php' ); // Pour le calcul d'alarmes
require_once( 'alarm/TestAlarmCalculationDynamic.php' ); // Pour le calcul d'alarmes dynamiques
require_once( 'alarm/TestAlarmCalculationStatic.php' ); // Pour le calcul d'alarmes statiques
require_once( 'alarm/TestAlarmCalculationTopWorst.php' ); // Pour le calcul d'alarmes top worst
require_once( 'divparzero/TestDivParZero.class.php' ); // Pour la division par zéro
require_once( 'ArrayTools/TestArrayTools.class.php' ); // Pour les outils de manipulation de tableau
require_once( 'PartitioningActivation/TestPartitioningActivation.class.php' ); // Pour l'activation du partitioning
require_once( 'Partition/TestPartition.class.php' ); // Pour la gestion des partitions
require_once( 'SMS/TestAstPhoneNumber.class.php' ); // Pour les numéros de téléphone
require_once( 'SMS/TestAstSMS.class.php' ); // Pour les SMS
require_once( 'SMS/TestAstSMSC.class.php' ); // Pour les SMS-Center
require_once( 'SMS/TestAstSMSMessage.class.php' ); // Pour les messages SMS
require_once( 'cbCompatibility/TestCbCompatibility.class.php' ); // Pour la compatibilité master 5.1 / slave 5.0


/**
 * Class de test de type Test suite lancant tous les tests
 */
class AllTests
{
    public static function suite ()
    {
        $testSuite = new PHPUnit_Framework_TestSuite( 'Test complet Trending & Aggregation' );
        $testSuite->addTestSuite( 'TestHealthIndicator' );          /* Test Health Indicator   */
        $testSuite->addTestSuite( 'TestTALogAstFile' );             /* Test TALogAstFile       */
        $testSuite->addTestSuite( 'TestApiTA' );                    /* Test T&A API            */
        $testSuite->addTestSuite( 'TestAlarmCalculation' );         /* Test Alarm Calculation  */
        $testSuite->addTestSuite( 'TestAlarmCalculationDynamic' );  /* Test Dynamic Alarm      */
        $testSuite->addTestSuite( 'TestAlarmCalculationStatic' );   /* Test Static Alarm       */
        $testSuite->addTestSuite( 'TestAlarmCalculationTopWorst' ); /* Test Top/Worst Alarm    */
        $testSuite->addTestSuite( 'TestDivParZero' );		        /* Test division par zéro  */
        $testSuite->addTestSuite( 'TestArrayTools' );		        /* Test manipul. tableaux  */
        $testSuite->addTestSuite( 'TestPartitioningActivation' );   /* Test activ. partitioning*/
        $testSuite->addTestSuite( 'TestPartition' );                /* Test gestion partitions */
        $testSuite->addTestSuite( 'TestAstPhoneNumber' );           /* Test Phone Number       */
        $testSuite->addTestSuite( 'TestAstSMS' );                   /* Test SMS                */
        $testSuite->addTestSuite( 'TestAstSMSC' );                  /* Test SMS-Center         */
        $testSuite->addTestSuite( 'TestAstSMSMessage' );            /* Test SMS Message        */
        $testSuite->addTestSuite( 'TestCbCompatibility' );          /* Test Compatibility module*/
        return $testSuite;
    }
}
