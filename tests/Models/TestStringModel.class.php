<?php

/**
 * Classe de test PHPUnit pour la gestion des modules installés
 *
 * Les règles de remplacement sont lues en base de données dans différentes tables.
 * Afin de pouvoir tester la gestion, des
 * données temporaires sont insérés en base. Le script 'tCR_log.bkp.sql' sauvegarde
 * la base actuelle et insère des données temporaires. Le script 'tCR_log.rst.sql'
 * restaure la base de données dans son état initial.
 * 
 * 
 */
include_once(dirname(__FILE__).'/../../php/environnement_liens.php');
require_once( dirname( __FILE__ ).'/../../models/StringModel.class.php' );

class TestStringModel extends PHPUnit_Framework_TestCase
{
    public function testUpdateCase(){
        $this->assertSame(StringModel::updateCase(0, (array('minuscules','MAJUSCULES','Mélange MinÈMaj','minù accentué','car#sp%'))),
                                                      array('minuscules','MAJUSCULES','Mélange MinÈMaj','minù accentué','car#sp%'));
        $this->assertSame(StringModel::updateCase(1, (array('minuscules','MAJUSCULES','Mélange MinÈMaj','minù accentué','car#sp%'))),
                                                      array('MINUSCULES','MAJUSCULES','MéLANGE MINÈMAJ','MINù ACCENTUé','CAR#SP%'));
        $this->assertSame(StringModel::updateCase(2, (array('minuscules','MAJUSCULES','Mélange MinÈMaj','minù accentué','car#sp%'))),
                                                      array('minuscules','majuscules','mélange minÈmaj','minù accentué','car#sp%'));
    }
}
?>
