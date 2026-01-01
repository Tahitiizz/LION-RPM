<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
    /**
     * @author Ilya Boyandin <ilyabo@gmx.net>
     */

    class FPHidden extends FPElement {

        function echoSource()
        {
            $this->_append(
                '<input type="hidden"'.
                    ' name="'.$this->_name.'"'.
                    ' value="'.$this->_value.'"'.
                '>'
            );
        }
    }

?>
