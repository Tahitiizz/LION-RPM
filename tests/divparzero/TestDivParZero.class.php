<?php
/**
 * Classe de test PHPUnit pour la gestion de la division par zéro
 * 
 * $Author: o.jousset $
 * $Date: 2012-02-06 18:07:49 +0100 (lun., 06 fÃ©vr. 2012) $
 * $Revision: 63556 $
 * 
 */
    require_once( dirname( __FILE__ ).'/../../class/Database.class.php' );
    require_once( dirname( __FILE__ ).'/../../class/DataBaseConnection.class.php' );

    class TestDivParZero extends PHPUnit_Framework_TestCase
    {

        public function testSeveralDivisions()
        {
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$database = Database::getConnection();
			$divisions = array(
							array("SELECT (10::real)/2+2 as div",7), 
							array("SELECT (10::real)/0 as div",''),
							array("SELECT 10/2 as div",5),
							array("SELECT 2::float4+10::float4/5::float4 as div",4),
							array("SELECT (2::float4+10::float4)/6::float4 as div",2),
							array("SELECT 2+10/5 as div",4),
							array("SELECT 10/5+2 as div",4),
							array("SELECT 10::float4/5::float4+2::float4 as div",4),
							array("SELECT 10::float4/(3::float4+2::float4) as div",2),
							array("SELECT 10::float4/10::float4 as div",1),
							array("SELECT 10/0 as div",''),  // erreur PGSQL non détectée
							array("SELECT 10::float4/0::float4 as div",''),
							array("SELECT 10::real/0::real as div",''),
							array("SELECT 10::float8/0::float8 as div",''),
							array("SELECT 10::float4/10::float4 as div",1),
							array("SELECT 10::float4/10::float4 as div",1)
						);
			
			foreach($divisions as $division){
				$result = $database->getRow($division[0]);
				$this->assertEquals( $division[1], $result['div']);
			}
        }
        /**
		* dans les tests précédents, si une erreur est retournée par un requête au lieu de <null>, le test ne le voit pas
		* on scinde donc en deux cas : 
		* tests ne devant pas générer d'erreur, donc dont le résultat est un tableau
		*/
        public function testDivisionByZeroReturnsNull()
        {
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$database = Database::getConnection();
			$divisions = array(
							array("SELECT (10::real)/0 as div",''),
							array("SELECT 10::float4/0::float4 as div",''),
							array("SELECT 10::real/0::real as div",''),
							array("SELECT 10::float8/0::float8 as div",'')
						);
			
			foreach($divisions as $division){
				$result = $database->getRow($division[0]);
				// s'il n'y a pas d'erreur, le résultat est un tableau
				$this->assertTrue( is_array($result) );
				
			}
        }
		
		/**
		* et tests devant générer une erreur, donc dont le résultat n'est pas un tableau.
		*/
        public function testDivisionByZeroReturnsError()
        {
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$database = Database::getConnection();
			$divisions = array(
							array("SELECT 10/0 as div",'')  // erreur PGSQL
						);
			
			foreach($divisions as $division){
				$result = $database->getRow($division[0]);
				// si un erreur est retournée, alors le résultat n'est pas un tableau
				$this->assertTrue( !is_array($result) );
			}
        }
    }
?>
