<?php
include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/AlarmCalculation.class.php');

class TestAlarmCalculation extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->alarmCalculation = $this->getMockForAbstractClass('AlarmCalculation');
    }

    public function testFirstDayOfWeek()
    {
        $this->alarmCalculation->setFirstDayOfWeek(2);
        $this->assertEquals(2, $this->alarmCalculation->getFirstDayOfWeek());
        $this->alarmCalculation->setFirstDayOfWeek(8);
        $this->assertEquals(1, $this->alarmCalculation->getFirstDayOfWeek());
    }

    public function testNeedAlarmToGenerateQuery()
    {
        $this->setExpectedException('RuntimeException');
        $this->alarmCalculation->getCalculationQuery('day',array(),0);
    }
}
?>
