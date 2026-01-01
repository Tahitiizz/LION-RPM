<?php
include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/ArrayTools.class.php');

class TestArrayTools extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->arraySimpleOne = array('aaa','bbb','ccc','dDd','Eee','fff');

        $this->arraySimpleTwo = array('aaa','bbb','ccc','ddd','eee','fff');

        $this->arrayOne = array('r01' => array('nom'=>'dylan','prenom'=>'Bob','fonction'=>'Ouvrier','sexe'=>'H'),
                        'r02' => array('nom'=>'Dugros','prenom'=>'francis','fonction'=>'chef','sexe'=>'H'),
                        'r03' => array('nom'=>'Delpierre','prenom'=>'Sophie','fonction'=>'assistanTe','sexe'=>'F'),
                        'r04' => array('nom'=>'Martin','prenom'=>'martine','fonction'=>'','sexe'=>'F'));

        $this->arrayTwo = array('p01' => array('nom'=>'dylan','prenom'=>'Bob','fonction'=>'Ouvrier','sexe'=>'H'),
                        'p02' => array('nom'=>'Groniard','prenom'=>'francis','fonction'=>'chef','sexe'=>'H'),
                        'p03' => array('nom'=>'Delpierre','prenom'=>'Sandrine','fonction'=>'assistante','sexe'=>'F'),
                        'p04' => array('nom'=>'Dupont','prenom'=>'régine','fonction'=>'coordinatrice','sexe'=>'F'),
                        'p05' => array('nom'=>'Martin','prenom'=>'Martine','fonction'=>'coordinatrice','sexe'=>'F'));
    }

    public function hashResult($result)
    {
        return md5(print_r($result,1));
    }

    public function testArrayOneDimensionNonSensibleToCase()
    {
        // Hash du résultat attendu
        $expectedHash = 'afdce52055e3e2107c3fa7bed4bf10b2';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arraySimpleOne,$this->arraySimpleTwo);
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }

    public function testArrayOneDimensionSensibleToCase()
    {
        // Hash du résultat attendu
        $expectedHash = '1c825006c2eba5f331fb12569c2990f2';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arraySimpleOne,$this->arraySimpleTwo,0,array(),true);
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }

    public function testArrayOneDimensionNonSensibleToCaseWithKeys()
    {
        // Hash du résultat attendu
        $expectedHash = '1457d349ab5a4d9f95ad3236ac055f84';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arraySimpleOne,$this->arraySimpleTwo,0,array(3,5));
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }

    public function testArrayOneDimensionSensibleToCaseWithKeys()
    {
        // Hash du résultat attendu
        $expectedHash = '80b6b23972123c82dfa0fbe42521d09e';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arraySimpleOne,$this->arraySimpleTwo,0,array(3,5),true);
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }

    public function testArrayMultiDimensionNonSensibleToCase()
    {
        // Hash du résultat attendu
        $expectedHash = '2c08d653e1ee60d55cd0da551026ea56';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arrayOne,$this->arrayTwo);
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }

    public function testArrayMultiDimensionNonSensibleToCaseWithKeys()
    {
        // Hash du résultat attendu
        $expectedHash = 'b0f878da55970834258f54253765c04e';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arrayOne,$this->arrayTwo,1, array('nom','prenom'));
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }

    public function testArrayMultiDimensionSensibleToCaseWithKeys()
    {
        // Hash du résultat attendu
        $expectedHash = '4cdf48e1f6ee21393a2e77ba5375399d';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arrayOne,$this->arrayTwo,1, array('nom','prenom'),true);
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }

    public function testBadParametersNoError()
    {
        // Hash du résultat attendu
        $expectedHash = '2c08d653e1ee60d55cd0da551026ea56';
        // Appel de la fonction
        $result = ArrayTools::conditionnalComparison($this->arraySimpleOne,$this->arraySimpleTwo,12, array('tata',0), array(32));
        // Hash du résultat obtenu
        $resultHash = $this->hashResult($result);
        // Assertion
        $this->assertEquals($expectedHash, $resultHash);
    }
}
?>
